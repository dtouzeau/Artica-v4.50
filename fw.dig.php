<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.doh-client.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
$sock=new sockets();
$tpl=new template_admin();
$users=new usersMenus();


if(isset($_POST["dohuri"])){doh_cli();exit;}
if(isset($_GET["standard"])){popup();exit;}
if(isset($_GET["popup"])){tabs();exit;}
if(isset($_POST["dns_hostname"])){tests();exit;}
if(isset($_GET["results"])){results_js();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
if(isset($_GET["doh"])){popup_doh();exit;}
if(isset($_GET["doh-cli"])){popup_doh_cli();exit;}

js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog6("{dns_simulation}", "$page?popup=yes",650);
}
function results_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog7("{dns_simulation} {results}", "$page?results-popup=yes",880);	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	if(!isset($_SESSION["dns_hostname"])){$_SESSION["dns_hostname"]="www.google.fr";}
	if($_SESSION["dns_server"]==null){$_SESSION["dns_server"]="8.8.8.8";}

	if($_SESSION["dns_interface"]==null){$_SESSION["dns_interface"]="no default";}
	if(intval($_SESSION["dns_timeout"])==0){$_SESSION["dns_timeout"]=3;}
	$form[]=$tpl->field_text("dns_hostname", "{hostname}",  $_SESSION["dns_hostname"],true);
    $form[]=$tpl->field_checkbox("PTR","{reverse_lookup}",0);
	$form[]=$tpl->field_interfaces("dns_interface", "{interface}", $_SESSION["dns_interface"]);
	$form[]=$tpl->field_ipaddr("dns_server", "{dns_server}", $_SESSION["dns_server"]);
	$form[]=$tpl->field_numeric("dns_timeout","{timeout2} ({seconds})",$_SESSION["dns_timeout"]);
	$html=$tpl->form_outside(null, $form,"{check_resolution_dns_engine}","{run}","Loadjs('$page?results=yes')");
	echo $html;
}
function popup_doh(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isset($_SESSION["dns_hostname"])){$_SESSION["dns_hostname"]="www.google.fr";}
    if(!isset($_SESSION["doh_uri"])){$_SESSION["doh_uri"]="https://cloudflare-dns.com/dns-query";}
    $form[]=$tpl->field_ipaddr("dnsipaddr", "{ipaddr}",  $_SESSION["dnsipaddr"],true);
    $form[]=$tpl->field_text("dns_hostname", "{hostname}",  $_SESSION["dns_hostname"],true);
    $form[]=$tpl->field_text("doh_uri", "{doh_uri}",  $_SESSION["doh_uri"],true);
    $html=$tpl->form_outside(null, $form,"{check_resolution_dns_engine}","{run}","");
    echo $html;
}

function doh_cli(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $fileresults=PROGRESS_DIR."/pdns.query";


    $_SESSION["doh_uri"]=$_POST["doh_uri"];
    $_SESSION["dns_hostname"]=$_POST["dns_hostname"];
    $_SESSION["CONTENT_TYPE"]=$_POST["CONTENT_TYPE"];
    $_SESSION["dnsipaddr"]=$_POST["dnsipaddr"];


/*
    $LOGS[]="DoH URL: $doccli->HTTP_URL";
    $LOGS[]="HTTP CODE: ".$doccli->CURLINFO_HTTP_CODE;
    $LOGS[]="HTTP Engine code: ".$doccli->curl_errno;
    $LOGS[]="ANSWER SECTION: {$_SESSION["dns_hostname"]}: $results";
*/
    @file_put_contents($fileresults,serialize($_POST));
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/dohquery"));

    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    echo $tpl->post_error("Success: {$_SESSION["dns_hostname"]}: [".$json->Info."]");
}

function popup_doh_cli(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isset($_SESSION["dns_hostname"])){$_SESSION["dns_hostname"]="www.google.fr";}
    if(!isset($_SESSION["doh_uri"])){$_SESSION["doh_uri"]="https://google.dns/dns-query";}
    if(!isset($_SESSION["dnsipaddr"])){$_SESSION["dnsipaddr"]="8.8.8.8";}
    $form[]=$tpl->field_ipaddr("dnsipaddr", "{ipaddr}",  $_SESSION["dnsipaddr"]);
    $form[]=$tpl->field_text("dns_hostname", "{hostname}",  $_SESSION["dns_hostname"],true);
    $form[]=$tpl->field_text("dohuri", "{doh_uri}",  $_SESSION["doh_uri"],true);
//    $form[]=$tpl->field_array_hash($ContentType,"CONTENT_TYPE","Content-Type",$_SESSION["CONTENT_TYPE"]);

    $html=$tpl->form_outside(null, $form,"{check_resolution_dns_engine}","{run}","Loadjs('$page?results=yes')");
    echo $html;

}

function tabs(){
    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();

    $array["{dns_simulation}"]="$page?standard=yes";
    $array["{doh_simulation}"]="$page?doh-cli=yes";
    $DOHServerEnabled=intval($sock->GET_INFO("DOHServerEnabled"));
    if($DOHServerEnabled==1){$array["{APP_DOH_SERVER}"]="$page?doh=yes";}
    echo $tpl->tabs_default($array);
}

function tests(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();

    if(!isset($_POST["dns_hostname"])){
        echo $tpl->post_error("dns_hostname missing");
        return false;
    }

    $payload = [
        "hostname"   => $_POST["dns_hostname"],
        "dns_server" => $_POST["dns_server"] ?? "",
        "interface"  => $_POST["dns_interface"] ?? "",
        "timeout"    => intval($_POST["dns_timeout"] ?? 3),
        "ptr"        => intval($_POST["PTR"] ?? 0) === 1,
    ];
    if(!empty($_POST["doh_uri"])){ $payload["doh_uri"] = $_POST["doh_uri"]; }
    if(!empty($_POST["type"]))   { $payload["type"]    = $_POST["type"]; }

    $json_str = $sock->REST_API_POST_JSON("/dns/dig", $payload);
    $check    = json_decode($json_str);
    if(!is_object($check)){
        echo $tpl->post_error("Invalid response from /dns/dig API");
        return false;
    }
    @file_put_contents(PROGRESS_DIR."/pdns.query", $json_str);

    $tpl->SESSION_POST();
}

/**
 * CSS propre a la page: le bandeau de rappel de la requete et l'echelle du RCODE.
 * Le reste vient de awc_table_style() (class.statistics_appliance.inc), partage.
 * Concatene autour du parsing, jamais dedans: _ENGINE_parse_body prendrait chaque
 * corps de regle CSS pour un {token}.
 */
function dig_style():string{
    return "<style>
.dig-sum{display:flex;align-items:center;gap:14px;padding:0 4px 14px 4px}
.dig-q{font-size:18px;font-weight:700;line-height:24px;color:#111d23}
.dig-qt{font-size:12px;font-weight:600;color:#3d4947;margin-left:6px}
.dig-b{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;padding:4px 9px;border-radius:4px;color:#fff;white-space:nowrap}
.dig-b-ok{background:#006a62}
.dig-b-warn{background:#865300}
.dig-b-err{background:#ba1a1a}
.dig-b-none{background:#e8ecec;color:#3d4947}
.dig-ico-ok{background:rgba(0,106,98,.10);color:#006a62}
.dig-ico-warn{background:rgba(134,83,0,.10);color:#865300}
.dig-ico-err{background:rgba(186,26,26,.10);color:#ba1a1a}
.dig-val{font-size:13px;line-height:19px;color:#111d23;word-break:break-all}
</style>";
}

/**
 * RCODE DNS -> (classe de pastille, suffixe de teinte d'icone).
 * NOERROR est le seul succes; NXDOMAIN est une reponse legitime mais negative,
 * donc ambre et non rouge -- un domaine qui n'existe pas n'est pas une panne du
 * resolveur, contrairement a SERVFAIL ou REFUSED.
 */
function dig_rcode_class($rcode):array{
    $rcode=strtoupper(trim((string)$rcode));
    if($rcode==""){return array("dig-b-none","");}
    if($rcode=="NOERROR"){return array("dig-b-ok","ok");}
    if($rcode=="NXDOMAIN"){return array("dig-b-warn","warn");}
    return array("dig-b-err","err");
}

/** Icone d'un enregistrement, choisie sur son type. */
function dig_type_icon($type):string{
    $type=strtoupper(trim((string)$type));
    if($type=="A" || $type=="AAAA"){return "fas fa-network-wired";}
    if($type=="CNAME"){return "fas fa-arrow-right-arrow-left";}
    if($type=="MX"){return "fas fa-envelope";}
    if($type=="NS"){return "fas fa-server";}
    if($type=="TXT"){return "fas fa-align-left";}
    if($type=="SOA"){return "fas fa-file-signature";}
    if($type=="PTR"){return "fas fa-arrow-turn-down";}
    if($type=="SRV"){return "fas fa-diagram-project";}
    if($type=="CAA"){return "fas fa-shield-halved";}
    return "fas fa-circle-info";
}

/**
 * Bandeau de rappel: ce qui a ete demande, a quel serveur, par quel transport,
 * en combien de temps -- et le verdict en pastille.
 * Une reponse dig se lit toujours a deux etages: le verdict d'abord, les
 * enregistrements ensuite; sans ce bandeau un tableau vide ne dit pas s'il faut
 * lire "le nom n'existe pas" ou "le resolveur n'a pas repondu".
 */
function dig_summary($json):string{
    $q       = isset($json->Question)?$json->Question:null;
    $qname   = htmlspecialchars(isset($q->Name)?(string)$q->Name:"");
    $qtype   = htmlspecialchars(isset($q->Type)?(string)$q->Type:"A");
    $server  = htmlspecialchars(isset($json->Server)?(string)$json->Server:"");
    $mode    = strtoupper(htmlspecialchars(isset($json->Mode)?(string)$json->Mode:"dns"));
    $rcode   = htmlspecialchars(isset($json->Rcode)?(string)$json->Rcode:"");
    $elapsed = intval(isset($json->QueryTimeMs)?$json->QueryTimeMs:0);

    list($bcls,$tint)=dig_rcode_class($rcode);
    $icocls=($tint=="")?"awc-ico":"awc-ico dig-ico-$tint";
    $badge=($rcode=="")?"":"<span class='dig-b $bcls'>$rcode</span>";

    $h ="<div class='dig-sum'>";
    $h.="<span class='$icocls'><i class='fas fa-magnifying-glass'></i></span>";
    $h.="<div><div class='dig-q'>$qname<span class='dig-qt'>($qtype)</span></div>";
    $h.="<div class='awc-meta'>{server}: $server &middot; {mode}: $mode &middot; {time}: {$elapsed}ms";
    if($rcode<>""){$h.=" &middot; {rcode}: $rcode";}
    $h.="</div></div>";
    $h.="<div style='margin-left:auto'>$badge</div>";
    $h.="</div>";
    return $h;
}

/**
 * Un enregistrement retourne: nom en identite (icone choisie sur le type, TTL en
 * sous-titre), type en pastille, valeur a droite.
 *
 * Aucune font-family n'est posee, pas meme sur la valeur: c'est la regle du
 * tableau awc -- heriter de la fonte du theme est ce qui fait que le tableau
 * appartient a la console. Les valeurs longues (TXT, DKIM) sont coupees par
 * word-break, pas par une chasse fixe.
 */
function dig_row($r):string{
    $name=htmlspecialchars(isset($r->Name)?(string)$r->Name:"");
    $type=htmlspecialchars(isset($r->Type)?(string)$r->Type:"");
    $ttl =intval(isset($r->TTL)?$r->TTL:0);
    $val =htmlspecialchars(isset($r->Value)?(string)$r->Value:"");
    $ico =dig_type_icon(isset($r->Type)?$r->Type:"");

    $h ="<tr>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='$ico'></i></span>";
    $h.="<span><span class='awc-name'>$name</span>";
    $h.="<div class='awc-meta'>TTL $ttl s</div>";
    $h.="</span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-tag'>$type</span></td>";
    $h.="<td><div class='dig-val'>$val</div></td>";
    $h.="</tr>";
    return $h;
}

function results_popup(){
    $tpl=new template_admin();
    $raw  = @file_get_contents(PROGRESS_DIR."/pdns.query");
    $json = json_decode($raw);

    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body("<p class='alert alert-danger m-b-sm'>{no_results}</p>");
        return;
    }
    if(!$json->Status){
        $err = htmlspecialchars(isset($json->Error)?(string)$json->Error:"");
        echo $tpl->_ENGINE_parse_body("<p class='alert alert-danger m-b-sm'>$err</p>");
        return;
    }

    $rows=array();
    if(!empty($json->Records)){
        foreach($json->Records as $r){ $rows[]=dig_row($r); }
    }
    $total=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'>";
    $html[]=dig_summary($json);
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{name}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="<th>{value}</th>";
    $html[]="</tr></thead><tbody id='dig-rows'>";
    if($total==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_records_returned}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $total / $total {records}</span>";
    $html[]="<span class='awc-pg' id='dig-pager'></span>";
    $html[]="</div></div></div>";

    // Feuilles et script concatenes AUTOUR du parsing: la regex #{(.+?)}# de
    // _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
    $foot="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo awc_table_style().dig_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(50,"dig-rows","dig-pager")
        .$foot;
}
