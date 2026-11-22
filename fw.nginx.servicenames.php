<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsWebMaster){exit();}

if(isset($_GET["start"])){Start();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["edit-js"])){edit_js();exit;}
if(isset($_GET["edit-popup"])){edit_popup();exit;}
if(isset($_GET["host-delete"])){hosts_delete();exit;}
if(isset($_POST["redirect"])){edit_save();exit;}
if(isset($_POST["new"])){new_save();exit;}
if(isset($_POST["hosts-delete"])){hosts_delete_perform();exit;}

// Nom de la fonction JS de rafraichissement : il est recopie dans du JS, donc liste blanche.
function sn_fn():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}
// Garde serveur des ecritures : masquer un bouton ne protege rien.
function sn_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsWebMaster;
}
function sn_deny():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
    return false;
}
// Texte affiche tel quel : echappe aussi les accolades et crochets, sinon le moteur
// de traduction lit un {token} ou un [b] dans une expression reguliere.
// Traduit un token PUIS remplace ses valeurs : un str_replace sur "{token}" ne verrait
// jamais le "%s" du texte traduit.
function sn_t($k,$vars=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    return str_replace(array_keys($vars),array_values($vars),$s);
}
function sn_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}

function hosts_delete():bool{
    $ID=intval($_GET["service-id"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $value=strval($_GET["host-delete"]);
    $fn=sn_fn();
    $servicename=get_servicename($ID);
    $array["service-id"]=$ID;
    $array["value"]=$value;
    $finale=urlencode(base64_encode(serialize($array)));
    $after=$fn<>""?"$fn();":"";
    return $tpl->js_confirm_delete(req_html("$value / $servicename"),"hosts-delete",$finale,$after);
}
function edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $function=sn_fn();
    $servicename=get_servicename($ID);
    $domain=$_GET["edit-js"];
    $domainec=urlencode($domain);
    // Le titre est decode (html_entity_decode) puis rendu en HTML : double encodage.
    $title="$servicename ".req_html(req_html($domain));
    return $tpl->js_dialog2($title,"$page?edit-popup=$domainec&service-id=$ID&function=$function",750);
}
function hosts_delete_perform():bool{
    if(!sn_can_write()){return sn_deny();}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $svalue=base64_decode($_POST["hosts-delete"]);
    // Pentest 2026-11-04 : deserialisation d'une valeur postee sans restriction de classes.
    $array=unserialize($svalue,array("allowed_classes"=>false));
    if(!is_array($array)){return false;}
    $ID=intval($array["service-id"]);
    $value=strval($array["value"]);
    $MAIN=HostsToArray($ID);
    unset($MAIN[$value]);
    if(!SaveHosts($MAIN,$ID)){return false;}
    $servicename=get_servicename($ID);
    admin_tracks("Delete $value from reverse-proxy service $servicename");
    return true;
}
function new_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $function=sn_fn();
    $servicename=get_servicename($ID);
    return $tpl->js_dialog2("$servicename {new_domain}","$page?new-popup=yes&service-id=$ID&function=$function");
}
function new_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["service-id"]);
    $function=sn_fn();
    $js=array("dialogInstance2.close()");
    if($function<>""){$js[]="$function()";}
    echo $tpl->BigDomainField("services-id:$serviceid|new", "{domain}", "{servernames_explain}","",@implode(";",$js));
    return true;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
// Colonne hosts : liste "||", chaque entree "domaine" ou "domaine>cible de redirection".
function sn_hosts_from_row($row):array{
    $MAIN=array();
    $hosts=isset($row["hosts"]) ? strval($row["hosts"]) : "";
    foreach (explode("||",$hosts) as $domain){
        $domain=trim(strtolower($domain));
        if(strlen($domain)==0){continue;}
        if(preg_match("#^(.+?)>(.+)#",$domain,$re)){
            $MAIN[$re[1]]=$re[2];
            continue;
        }
        $MAIN[$domain]="";
    }
    return $MAIN;
}
function HostsToArray($serviceid):array{
    return sn_hosts_from_row(fetch_nginx_service($serviceid));
}
function ArrayToHosts($MAIN=array()):string{
    $F=array();
    foreach($MAIN as $domain=>$redirect){
        $domain=trim(strtolower(strval($domain)));
        if(strpos($domain, ";")>0){continue;}
        if($domain==""){continue;}
        $redirect=strval($redirect);
        if($redirect<>"" && $redirect<>$domain){
            $F[]="$domain>$redirect";
            continue;
        }
        $F[]=$domain;
    }
    return trim(@implode("||",$F));
}
function SaveHosts($HostsToArray=array(),$serviceid=0):bool{
    $newval=ArrayToHosts($HostsToArray);
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$serviceid/update-service", [
        "hosts" => $newval, "goodconftime" => 0
    ]),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        echo $err;return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks_post("Add/Edit domain for reverse-proxy service $servicename");
}
function new_save():bool{
    if(!sn_can_write()){return sn_deny();}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["services-id"]);
    $value=trim(strtolower($_POST["new"]));
    if($value==""){return false;}
    $MAIN=HostsToArray($serviceid);
    if(isset($MAIN[$value])){return false;}
    $MAIN[$value]="";
    return SaveHosts($MAIN,$serviceid);
}
function edit_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $servicename=get_servicename($ID);
    $Type=get_ServiceType($ID);
    $domain=$_GET["edit-popup"];
    $function=sn_fn();

    if($Type==13){
       echo $tpl->_ENGINE_parse_body($tpl->div_warning("{nginx_no_redir_option}"));
       return false;
    }
    $MAIN=HostsToArray($ID);
    // Pentest 2026-11-04 : seul un domaine reellement configure pour ce service est accepte.
    if(!is_array($MAIN) || !is_string($domain) || !array_key_exists($domain,$MAIN)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{domain}: not found"));
        return false;
    }
    $redirect=$MAIN[$domain];
    $js=array("dialogInstance2.close()");
    if($function<>""){$js[]="$function()";}
    $html[]=$tpl->BigUriField("service-id:$ID|hosts:$domain|redirect","$servicename: {redirect_uri}","{nginx_redirect_uri_explain}",$redirect,implode(";",$js));
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function edit_save():bool{
    if(!sn_can_write()){return sn_deny();}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["service-id"]);
    if(get_ServiceType($serviceid)==13){echo $tpl->_ENGINE_parse_body("{nginx_no_redir_option}");return false;}
    $MAIN=HostsToArray($serviceid);
    $domain=trim(strtolower($_POST["hosts"]));
    // Une redirection ne cree jamais un domaine : elle ne s'applique qu'a un domaine existant.
    if(!array_key_exists($domain,$MAIN)){echo "$domain: not found";return false;}
    $redirect=trim(strtolower($_POST["redirect"]));
    $MAIN[$domain]=$redirect;
    return SaveHosts($MAIN,$serviceid);
}
function get_ServiceType($ID):int{
    $row=fetch_nginx_service($ID);
    return isset($row["type"]) ? intval($row["type"]) : 0;
}

// Onglet "Server names" : un hote recharge par une fonction de rafraichissement fixe.
function Start():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["start"]);
    $fn="SnRefresh$serviceid";
    $html[]="<div id='sn-host-$serviceid' class='sn-host'></div>";
    $html[]="<script>function $fn(){LoadAjaxSilent('sn-host-$serviceid','$page?search=&service-id=$serviceid&function=$fn');}$fn();</script>";
    echo implode("\n",$html);
    return true;
}

function table():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $m=sn_model($ID);
    $html=array();
    $html[]="<div class='sn-wrap'>";
    $html[]=sn_actions($m);
    if(!$m["has_cert"]){
        $html[]=sn_cert_empty($m);
    }elseif($m["cert_error"]<>""){
        $html[]=sn_cert_error($m);
    }else{
        $html[]=sn_cert_card($m);
    }
    $html[]=sn_dynamic_banner($m);
    $html[]=sn_domains_table($m);
    $html[]=sn_help();
    $html[]="</div>";
    $html[]="<script>NoSpinner();</script>";
    echo sn_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).sn_js($ID);
    return true;
}

// Modele calcule une seule fois : service, domaines, certificat, routage dynamique.
function sn_model($ID):array{
    $row=fetch_nginx_service($ID);
    $m=array(
        "id"=>$ID,
        "fn"=>sn_fn(),
        "type"=>isset($row["type"])?intval($row["type"]):0,
        "hosts"=>sn_hosts_from_row($row),
        "has_cert"=>false,
        "cert"=>array(),
        "cert_error"=>"",
        "dyn"=>array(),
        "can_write"=>sn_can_write(),
    );
    if($m["fn"]==""){$m["fn"]="SnRefresh$ID";}
    $socksngix=new socksngix($ID);
    if(strlen(strval($socksngix->GET_INFO("ssl_certificate")))>3){
        $m["has_cert"]=true;
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/certinfo/$ID"),true);
        if(!is_array($json)){
            $m["cert_error"]="daemon unavailable";
        }elseif(empty($json["Status"])){
            $m["cert_error"]=isset($json["Error"])&&is_string($json["Error"])&&$json["Error"]<>""?$json["Error"]:"unknown error";
        }else{
            $m["cert"]=sn_cert_fields(isset($json["data"])&&is_array($json["data"])?$json["data"]:array());
        }
    }
    if(intval($socksngix->GET_INFO("ForwardServersDynamics"))==1){
        $src=trim(strval($socksngix->GET_INFO("FSDynamicsSrc")));
        $dst=trim(strval($socksngix->GET_INFO("FSDynamicsDst")));
        if(intval($socksngix->GET_INFO("FSDynamicsExt"))==1){
            $src=sn_dyn_ext($src);
            $dst=sn_dyn_ext($dst);
        }
        $m["dyn"]=array("src"=>$src,"dst"=>$dst,"md"=>md5($src.$dst));
    }
    return $m;
}
// Extension libre : la derniere etiquette du domaine devient "*".
function sn_dyn_ext($d):string{
    if(preg_match("#\.([^.]*)$#",$d,$re)){
        return substr($d,0,-strlen($re[1])-1).".*";
    }
    return $d.".*";
}
function sn_cert_fields($d):array{
    $names=array();
    if(isset($d["DNSNames"]) && is_array($d["DNSNames"])){
        foreach($d["DNSNames"] as $n){$names[]=strtolower(trim(strval($n)));}
    }
    $expire=isset($d["ExpireDate"])?intval($d["ExpireDate"]):0;
    $nb=isset($d["NotBefore"])?intval($d["NotBefore"]):0;
    $issuer=isset($d["Issuer"])?strval($d["Issuer"]):"";
    $key="";
    if(!empty($d["PublicKeyAlgorithm"])){
        $key=strval($d["PublicKeyAlgorithm"]);
        if(!empty($d["PublicKeyBits"])){$key.=" ".intval($d["PublicKeyBits"]);}
    }
    $left=$expire>0?(int)floor(($expire-time())/86400):0;
    $state="ok";
    if($expire<=time()){$state="expired";}
    elseif($left<=7){$state="crit";}
    elseif($left<=30){$state="warn";}
    return array(
        "cn"=>isset($d["CommonName"])?strval($d["CommonName"]):"",
        "issuer_cn"=>isset($d["IssuerCommonName"])?strval($d["IssuerCommonName"]):"",
        "le"=>stripos($issuer,"Let's Encrypt")!==false,
        "self"=>!empty($d["selfSigned"]),
        "names"=>$names,
        "expire"=>$expire,
        "not_before"=>$nb,
        "total"=>($nb>0 && $expire>$nb)?(int)round(($expire-$nb)/86400):0,
        "left"=>$left,
        "state"=>$state,
        "key"=>$key,
    );
}
// Un nom qu'on ne peut pas comparer a un certificat : expression reguliere, joker
// final (mail.*), catch-all, ".domaine" ou adresse IP.
function sn_is_pattern($host):bool{
    if($host==""){return true;}
    if($host[0]=="~" || $host[0]=="."){return true;}
    if(filter_var($host,FILTER_VALIDATE_IP)){return true;}
    $stars=substr_count($host,"*");
    if($stars==0){return false;}
    return !($stars==1 && substr($host,0,2)=="*.");
}
// Correspondance X.509 : nom exact, ou joker "*." qui couvre UNE seule etiquette.
function sn_cert_covers($host,$names):bool{
    foreach($names as $n){
        if($n==$host){return true;}
        if(substr($n,0,2)<>"*." || substr($host,0,2)=="*."){continue;}
        $suf=substr($n,1);
        $l=strlen($suf);
        if(strlen($host)<=$l || substr($host,-$l)<>$suf){continue;}
        if(strpos(substr($host,0,-$l),".")===false){return true;}
    }
    return false;
}
// none = pas de certificat lisible, unchecked = motif, covered / uncovered.
function sn_cover_state($host,$m):string{
    if(!$m["has_cert"] || count($m["cert"])==0){return "none";}
    if(sn_is_pattern($host)){return "unchecked";}
    return sn_cert_covers($host,$m["cert"]["names"])?"covered":"uncovered";
}
function sn_kind_icon($host):string{
    if($host<>"" && $host[0]=="~"){return "fa-solid fa-code";}
    if(strpos($host,"*")!==false){return "fa-solid fa-asterisk";}
    return "fa-solid fa-globe";
}

function sn_actions($m):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=$m["id"];$fn=$m["fn"];
    $b=array();
    $b[]=array("Loadjs('$page?new-js=yes&service-id=$ID&function=$fn');",ico_plus,"{new_domain}","AsWebMaster");
    $b[]=array("Loadjs('fw.nginx.sites.dynamics.php?service-id=$ID&function=$fn')",ico_routes,"{APP_OSPF}","AsWebMaster");
    $b[]=array("Loadjs('fw.nginx.sites.letsencrypt.php?service-id=$ID&function=$fn')",ico_certificate,"{certificate} Let's Encrypt","AsWebMaster");
    return "<div class='sn-actions'>".$tpl->th_buttons($b)."</div>";
}

function sn_cert_pill($c):string{
    if($c["state"]=="expired"){
        return "<span class='sn-pill sn-crit'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{sn_expired}</span>";
    }
    if($c["state"]=="crit"){
        return "<span class='sn-pill sn-crit'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>".sn_t("sn_expires_in",array("%s"=>$c["left"]))."</span>";
    }
    if($c["state"]=="warn"){
        return "<span class='sn-pill sn-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>".sn_t("sn_expires_in",array("%s"=>$c["left"]))."</span>";
    }
    return "<span class='sn-pill sn-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{sn_valid}</span>";
}
// Donut de validite : jours restants sur la duree totale du certificat.
function sn_cert_donut($c):string{
    $colors=array("ok"=>"#047857","warn"=>"#d97706","crit"=>"#dc2626","expired"=>"#dc2626");
    $left=max(0,$c["left"]);
    $txt=sn_t("sn_days_short",array("%s"=>$left));
    if($c["total"]<=0){
        return "<div class='sn-donut-box sn-donut-flat'><span class='sn-donut-txt'>$txt</span></div>";
    }
    $aria=sn_t("sn_days_left_of",array("%a"=>$left,"%b"=>$c["total"]));
    $v=min($left,$c["total"])."/".$c["total"];
    return "<div class='sn-donut-box' role='img' aria-label=\"$aria\"><span class='sn-donut' data-c='{$colors[$c["state"]]}'>$v</span><span class='sn-donut-txt'>$txt</span></div>";
}
function sn_cert_coverage($m):string{
    $all=0;$ok=0;
    foreach($m["hosts"] as $host=>$redirect){
        $st=sn_cover_state($host,$m);
        if($st=="covered"){$all++;$ok++;}
        if($st=="uncovered"){$all++;}
    }
    if($all==0){return "";}
    $pct=round($ok*100/$all);
    $cls=$ok==$all?"sn-bar-ok":"sn-bar-warn";
    $txt=sn_t("sn_covers",array("%a"=>$ok,"%b"=>$all));
    return "<div class='sn-cov'><span>$txt</span><span class='sn-bar' role='img' aria-label=\"$txt\"><span class='$cls' style='width:$pct%'></span></span></div>";
}
function sn_cert_card($m):string{
    $c=$m["cert"];
    $d=array();
    if($c["issuer_cn"]<>""){$d[]="{sn_issued_by} ".sn_h($c["issuer_cn"]);}
    if($c["expire"]>0){$d[]="{sn_valid_until} ".date("Y-m-d",$c["expire"]);}
    if($c["key"]<>""){$d[]=sn_h($c["key"]);}
    $le=$c["le"]?"<span class='sn-tag'>Let's Encrypt</span>":"";
    $self=$c["self"]?"<span class='sn-pill sn-neutral'><i class='fa-solid fa-signature' aria-hidden='true'></i>{sn_self_signed}</span>":"";
    $cn=$c["cn"]<>""?sn_h($c["cn"]):"-";
    $h=array();
    $h[]="<section class='sn-card sn-cert' aria-label=\"{certificate}\">";
    $h[]=sn_cert_donut($c);
    $h[]="<div class='sn-cert-mid'>";
    $h[]="<div class='sn-cert-cn'><span>$cn</span>$le</div>";
    $h[]="<div class='sn-cert-det'>".implode(" &bull; ",$d)."</div>";
    $h[]=sn_cert_coverage($m);
    $h[]="</div>";
    $h[]="<div class='sn-cert-right'>$self".sn_cert_pill($c)."</div>";
    $h[]="</section>";
    return implode("",$h);
}
function sn_cert_empty($m):string{
    $ID=$m["id"];$fn=$m["fn"];
    $btn="";
    if($m["can_write"]){
        $btn="<button type='button' class='btn abt abt-g abt-sm' OnClick=\"Loadjs('fw.nginx.sites.letsencrypt.php?service-id=$ID&function=$fn')\"><i class='".ico_certificate."' aria-hidden='true'></i><span>{certificate} Let's Encrypt</span></button>";
    }
    return "<section class='sn-card sn-cert sn-cert-empty'><span class='sn-empty-ico'><i class='fa-solid fa-lock-open' aria-hidden='true'></i></span><div class='sn-cert-mid'><div class='sn-cert-cn'>{sn_no_cert}</div></div><div class='sn-cert-right'>$btn</div></section>";
}
function sn_cert_error($m):string{
    $err=sn_h($m["cert_error"]);
    return "<section class='sn-card sn-cert sn-cert-err'><span class='sn-err-ico'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i></span><div class='sn-cert-mid'><div class='sn-cert-cn'>{sn_cert_unreadable}</div><div class='sn-cert-det'>$err</div></div></section>";
}
function sn_dynamic_banner($m):string{
    if(count($m["dyn"])==0){return "";}
    $ID=$m["id"];$fn=$m["fn"];$md=$m["dyn"]["md"];
    $src=sn_h("*.".$m["dyn"]["src"]);
    $dst=sn_h("*.".$m["dyn"]["dst"]);
    return "<div class='sn-dyn'><span class='sn-dyn-lbl'><i class='fa-solid fa-route' aria-hidden='true'></i>{APP_OSPF}</span><button type='button' class='sn-dyn-rule' OnClick=\"Loadjs('fw.nginx.sites.dynamics.php?service-id=$ID&md=$md&function=$fn');\"><span>$src</span><i class='fa-solid fa-arrow-right' aria-hidden='true'></i><span>$dst</span></button></div>";
}
function sn_redirect_button($m,$host,$redirect):string{
    $page=CurrentPageName();
    $ID=$m["id"];$fn=$m["fn"];
    $label=$redirect<>""?"{sn_edit}":"{redirect_uri}";
    $ico=$redirect<>""?"fa-solid fa-pen-to-square":"fa-solid fa-arrow-right-arrow-left";
    if($m["type"]==13){
        return "<button type='button' class='btn abt abt-g abt-sm abt-l' aria-disabled='true' data-tip=\"{sn_no_redirect}\"><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{redirect_uri}</span></button>";
    }
    if(!$m["can_write"]){
        return "<button type='button' class='btn abt abt-g abt-sm abt-l' aria-disabled='true'><i class='fa-solid fa-lock' aria-hidden='true'></i><span>$label</span></button>";
    }
    $enc=urlencode($host);
    $cls=$redirect<>""?"sn-redir-set":"";
    return "<button type='button' class='btn abt abt-g abt-sm $cls' OnClick=\"Loadjs('$page?edit-js=$enc&service-id=$ID&function=$fn');\"><i class='$ico' aria-hidden='true'></i><span>$label</span></button>";
}
function sn_delete_button($m,$host):string{
    $page=CurrentPageName();
    $ID=$m["id"];$fn=$m["fn"];
    $aria="{sn_delete} ".sn_h($host);
    if(!$m["can_write"]){
        return "<button type='button' class='btn abt abt-g abt-ico abt-l' aria-disabled='true' aria-label=\"$aria\"><i class='fa-solid fa-lock' aria-hidden='true'></i></button>";
    }
    $enc=urlencode($host);
    return "<button type='button' class='btn abt abt-g abt-ico sn-del' aria-label=\"$aria\" title=\"$aria\" OnClick=\"Loadjs('$page?host-delete=$enc&service-id=$ID&function=$fn');\"><i class='fa-solid fa-trash-can' aria-hidden='true'></i></button>";
}
function sn_cover_pill($st):string{
    if($st=="covered"){return "<span class='sn-pill sn-ok'><i class='fa-solid fa-lock' aria-hidden='true'></i>{sn_covered}</span>";}
    if($st=="uncovered"){return "<span class='sn-pill sn-warn'><i class='fa-solid fa-lock-open' aria-hidden='true'></i>{sn_not_covered}</span>";}
    if($st=="unchecked"){return "<span class='sn-pill sn-neutral'><i class='fa-solid fa-code' aria-hidden='true'></i>{sn_not_checked}</span>";}
    return "<span class='sn-dash' aria-label=\"{sn_no_cert}\">-</span>";
}
function sn_domain_row($m,$host,$redirect):string{
    $mono=($host<>"" && $host[0]=="~")?" sn-mono":"";
    $sub="";
    if($redirect<>""){
        $r=sn_h($redirect);
        $sub="<div class='sn-redir' title=\"$r\"><i class='fa-solid fa-arrow-turn-down-right' aria-hidden='true'></i>{sn_redirects_to} <span>$r</span></div>";
    }
    $h=array();
    $h[]="<tr id='sn-".md5($host)."'>";
    $h[]="<td class='sn-ico'><i class='".sn_kind_icon($host)."' aria-hidden='true'></i></td>";
    $h[]="<td class='sn-dom'><div class='sn-name$mono'>".sn_h($host)."</div>$sub</td>";
    $h[]="<td class='sn-cer'>".sn_cover_pill(sn_cover_state($host,$m))."</td>";
    $h[]="<td class='sn-red'>".sn_redirect_button($m,$host,$redirect)."</td>";
    $h[]="<td class='sn-act'>".sn_delete_button($m,$host)."</td>";
    $h[]="</tr>";
    return implode("",$h);
}
function sn_domains_empty($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];$fn=$m["fn"];
    $btn="";
    if($m["can_write"]){
        $btn="<button type='button' class='btn abt abt-s' OnClick=\"Loadjs('$page?new-js=yes&service-id=$ID&function=$fn');\"><i class='".ico_plus."' aria-hidden='true'></i><span>{new_domain}</span></button>";
    }
    return "<div class='sn-empty'><span class='sn-empty-big'><i class='fa-solid fa-globe' aria-hidden='true'></i></span><p>{sn_no_domain}</p>$btn</div>";
}
function sn_domains_table($m):string{
    $h=array();
    $h[]="<section class='sn-card sn-tbl-card' aria-label=\"{servernames}\">";
    if(count($m["hosts"])==0){
        $h[]=sn_domains_empty($m);
        $h[]="</section>";
        return implode("",$h);
    }
    $h[]="<table class='sn-tbl'><thead><tr><th class='sn-ico'></th><th class='sn-dom'>{domain}</th><th class='sn-cer'>{certificate}</th><th class='sn-red'>{redirect_uri}</th><th class='sn-act'></th></tr></thead><tbody>";
    foreach($m["hosts"] as $host=>$redirect){
        $h[]=sn_domain_row($m,strval($host),strval($redirect));
    }
    $h[]="</tbody></table>";
    $n=count($m["hosts"]);
    $h[]="<div class='sn-foot'>".($n==1?"{sn_one_domain}":sn_t("sn_domains_count",array("%s"=>$n)))."</div>";
    $h[]="</section>";
    return implode("",$h);
}
function sn_help():string{
    return "<details class='sn-card sn-help'><summary><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{sn_help_title}</span></summary><div class='sn-help-body'>{servernames_explain}<div class='sn-help-note'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span>{sn_wildcard_note}</span></div></div></details>";
}

function sn_js($ID):string{
    $ID=intval($ID);
    return "<script src='js/jquery.peity.min.js'></script><script>(function(){if(!window.jQuery||!jQuery.fn.peity){return;}"
        ."jQuery('#sn-host-$ID .sn-donut').each(function(){var e=jQuery(this);e.peity('donut',{fill:[e.data('c'),'#e2e8f0'],radius:26,innerRadius:21});});})();</script>";
}
function sn_style():string{
    $f=array();
    $f[]="<style>";
    $f[]=".sn-wrap{display:flex;flex-direction:column;gap:16px;padding:12px 0 8px;color:#0f172a;font-size:14px}";
    $f[]=".sn-wrap *{box-sizing:border-box}";
    $f[]=".sn-actions .btn-group{display:flex;flex-wrap:wrap;gap:8px}";
    $f[]=".sn-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $f[]=".sn-cert{display:flex;align-items:center;gap:16px;min-height:96px;padding:16px}";
    $f[]=".sn-cert-mid{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}";
    $f[]=".sn-cert-cn{display:flex;align-items:center;gap:8px;font-size:15px;font-weight:600;color:#0f172a;word-break:break-all}";
    $f[]=".sn-cert-det{font-size:13px;color:#475569}";
    $f[]=".sn-cert-right{display:flex;align-items:center;gap:8px;flex-shrink:0}";
    $f[]=".sn-cert-empty{border-style:dashed;border-color:#cbd5e1;box-shadow:none}";
    $f[]=".sn-cert-empty .sn-cert-cn{font-weight:500;color:#475569}";
    $f[]=".sn-empty-ico{font-size:24px;color:#94a3b8;width:56px;text-align:center}";
    $f[]=".sn-cert-err{border-color:#fecaca;background:#fef2f2}";
    $f[]=".sn-err-ico{font-size:24px;color:#b91c1c;width:56px;text-align:center}";
    $f[]=".sn-donut-box{position:relative;width:56px;height:56px;flex-shrink:0;display:flex;align-items:center;justify-content:center}";
    $f[]=".sn-donut-box svg{position:absolute;inset:0}";
    $f[]=".sn-donut-flat{border:4px solid #e2e8f0;border-radius:50%}";
    $f[]=".sn-donut-txt{position:relative;font-size:13px;font-weight:700;color:#0f172a}";
    $f[]=".sn-tag{display:inline-block;padding:1px 8px;border-radius:999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:12px;font-weight:600}";
    $f[]=".sn-cov{display:flex;align-items:center;gap:10px;font-size:13px;color:#475569}";
    $f[]=".sn-bar{display:inline-block;width:120px;height:6px;border-radius:999px;background:#e2e8f0;overflow:hidden}";
    $f[]=".sn-bar>span{display:block;height:100%;border-radius:999px}";
    $f[]=".sn-bar-ok{background:#047857}.sn-bar-warn{background:#d97706}";
    $f[]=".sn-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:600;line-height:1.3;border:1px solid transparent}";
    $f[]=".sn-ok{background:#ecfdf5;color:#047857;border-color:#a7f3d0}";
    $f[]=".sn-warn{background:#fffbeb;color:#b45309;border-color:#fde68a}";
    $f[]=".sn-crit{background:#fef2f2;color:#b91c1c;border-color:#fecaca}";
    $f[]=".sn-neutral{background:#f1f5f9;color:#475569;border-color:#e2e8f0}";
    $f[]=".sn-dash{color:#94a3b8}";
    $f[]=".sn-dyn{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:56px;padding:8px 16px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:8px}";
    $f[]=".sn-dyn-lbl{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#0e7490}";
    $f[]=".sn-dyn-rule{display:inline-flex;align-items:center;gap:12px;padding:6px 14px;background:#fff;border:1px solid #99f6e4;border-radius:6px;font-family:monospace;font-size:14px;color:#0f172a;cursor:pointer}";
    $f[]=".sn-dyn-rule svg,.sn-dyn-rule i{color:#0e7490}";
    $f[]=".sn-dyn-rule:hover{background:#ccfbf1}";
    $f[]=".sn-dyn-rule:focus-visible,.sn-help summary:focus-visible{outline:2px solid #2563eb;outline-offset:2px}";
    $f[]=".sn-tbl-card{overflow:hidden}";
    $f[]=".sn-tbl{width:100%;border-collapse:collapse;table-layout:fixed}";
    $f[]=".sn-tbl th{height:40px;padding:0 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#64748b;text-align:left}";
    $f[]=".sn-tbl td{padding:10px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle}";
    $f[]=".sn-tbl tbody tr{transition:background-color .15s}";
    $f[]=".sn-tbl tbody tr:hover{background:#f8fafc}";
    $f[]=".sn-tbl .sn-ico{width:48px;text-align:center;color:#64748b;font-size:16px}";
    $f[]=".sn-tbl .sn-dom{width:auto;min-width:0}";
    $f[]=".sn-tbl .sn-cer{width:260px}";
    $f[]=".sn-tbl .sn-red{width:150px}";
    $f[]=".sn-tbl .sn-act{width:64px;text-align:right}";
    $f[]=".sn-name{font-size:14px;font-weight:600;color:#0f172a;word-break:break-all}";
    $f[]=".sn-mono{font-family:monospace;font-weight:500}";
    $f[]=".sn-redir{display:flex;align-items:center;gap:6px;margin-top:2px;font-size:13px;color:#475569;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $f[]=".sn-redir span{font-family:monospace;overflow:hidden;text-overflow:ellipsis}";
    $f[]=".sn-tbl .sn-red .abt-g:not(.abt-l),.sn-tbl .sn-red .abt-g:not(.abt-l)>svg{color:#475569}";
    $f[]=".sn-tbl .sn-red .abt-g.sn-redir-set,.sn-tbl .sn-red .abt-g.sn-redir-set>svg{color:#2563eb}";
    $f[]=".sn-tbl .sn-del,.sn-tbl .sn-del>svg{color:#94a3b8}";
    $f[]=".sn-tbl .sn-del:hover,.sn-tbl .sn-del:focus-visible,.sn-tbl .sn-del:hover>svg,.sn-tbl .sn-del:focus-visible>svg{color:#b91c1c;background:#fef2f2}";
    $f[]=".sn-foot{height:40px;display:flex;align-items:center;padding:0 16px;font-size:13px;color:#64748b}";
    $f[]=".sn-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:160px;padding:24px;color:#475569}";
    $f[]=".sn-empty-big{font-size:40px;line-height:1;color:#cbd5e1}";
    $f[]=".sn-empty p{margin:0;font-size:14px}";
    $f[]=".sn-help summary{display:flex;align-items:center;gap:8px;padding:12px 16px;cursor:pointer;font-size:14px;font-weight:600;color:#0f172a;list-style:none}";
    $f[]=".sn-help summary::-webkit-details-marker{display:none}";
    $f[]=".sn-help summary svg,.sn-help summary i{color:#2563eb}";
    $f[]=".sn-help-body{padding:12px 16px 16px;border-top:1px solid #f1f5f9;font-size:13px;line-height:1.6;color:#475569}";
    $f[]=".sn-help-note{display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;color:#64748b}";
    $f[]=".sn-help-note svg,.sn-help-note i{color:#d97706;margin-top:3px;flex-shrink:0}";
    $f[]="@media (max-width:1199px){.sn-tbl .sn-cer{width:220px}.sn-tbl .sn-red{width:120px}}";
    $f[]="@media (prefers-reduced-motion:reduce){.sn-tbl tbody tr{transition:none}}";
    $f[]="</style>";
    return implode("",$f);
}
