<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
// Lecture : AsProxyMonitor (comme les autres pages proxy) ; le POST exige AsSquidAdministrator (save()).
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["metrics"])){metrics();exit;}
// ppst-save : marqueur du formulaire (DenyDnsResolve n'est pas poste quand Meta le tient).
if(isset($_POST["DenyDnsResolve"]) || isset($_POST["ppst-save"])){save();exit;}
if(isset($_GET["main-form"])){main_settings_js();exit;}
if(isset($_GET["main-popup"])){main_settings();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $PROXYPAC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
    if($PROXYPAC_VERSION<>null){$PROXYPAC_VERSION=" v{$PROXYPAC_VERSION}";}
    $html=$tpl->page_header("{APP_PROXY_PAC}$PROXYPAC_VERSION",
        "fad fa-scroll-old",
        "{ppst_explain}",
        "$page?table=yes",
        "proxypac-status","progress-proxypac-restart",false,"table-loader-proxypac-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROXY_PAC}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function main_settings_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{main_parameters}","$page?main-popup");
    return true;
}

function main_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SessionCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacCacheTime"));
    $ProxyPacListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenInterface"));
    $ProxyPacListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenPort"));
    $ProxyPacLockScript=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScript"));
    $ProxyPacLockScriptContent=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScriptContent");
    $DenyDnsResolve=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenyDnsResolve"));
    $ProxyPacDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacDebug"));
    if($SessionCache==0){$SessionCache=10;}
    $ProxyPACSQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPACSQL"));
    $security="AsSquidAdministrator";
    if($ProxyPacListenPort==0){$ProxyPacListenPort=80;}
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $ProxyPacSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacSSL"));
    $ProxyPacCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacCertificate"));

    // Politique Meta : un reglage tenu est montre en lecture seule et n'est jamais poste
    // sous son nom (save() refuserait tout le formulaire). Marqueur : ppst-save.
    $tpl->field_hidden("ppst-save","1");
    $editable=0;
    if(proxypac_meta_holds("ProxyPacDebug")){
        $form[]=proxypac_meta_field_ro($tpl,"ProxyPacDebug","{debug}",ppst_meta_bool($ProxyPacDebug));
    }else{
        $form[]=$tpl->field_checkbox("ProxyPacDebug","{debug}",$ProxyPacDebug,false);
        $editable++;
    }

    if($EnableNginx==0) {
        if(proxypac_meta_holds("ProxyPacListenInterface")){
            $form[]=proxypac_meta_field_ro($tpl,"ProxyPacListenInterface","{listen_interface}",$ProxyPacListenInterface==""?"{all_interfaces}":proxypac_meta_h($ProxyPacListenInterface));
        }else{
            $form[] = $tpl->field_interfaces("ProxyPacListenInterface","{listen_interface}",$ProxyPacListenInterface);
            $editable++;
        }
        if(proxypac_meta_holds("ProxyPacListenPort")){
            $form[]=proxypac_meta_field_ro($tpl,"ProxyPacListenPort","{listen_port}",intval($ProxyPacListenPort));
        }else{
            $form[] = $tpl->field_numeric("ProxyPacListenPort", "{listen_port}", $ProxyPacListenPort);
            $editable++;
        }
        if(proxypac_meta_holds("ProxyPacSSL")){
            $form[]=proxypac_meta_field_ro($tpl,"ProxyPacSSL","{useSSL}",ppst_meta_bool($ProxyPacSSL));
        }else{
            $form[] = $tpl->field_checkbox("ProxyPacSSL","{useSSL}",$ProxyPacSSL,"ProxyPacCertificate");
            $editable++;
        }
        if(proxypac_meta_holds("ProxyPacCertificate")){
            $form[]=proxypac_meta_field_ro($tpl,"ProxyPacCertificate","{ssl_certificate}",proxypac_meta_h($ProxyPacCertificate));
        }else{
            $form[] = $tpl->field_certificate("ProxyPacCertificate","{ssl_certificate}",$ProxyPacCertificate);
            $editable++;
        }

    }else{
        $form[]=$tpl->div_explain("{APP_NGINX}||{webservice_using_nginx}||wiki:https://wiki.articatech.com/en/reverse-proxy/architecture/proxy-pac");
    }
    if(proxypac_meta_holds("DenyDnsResolve")){
        $form[]=proxypac_meta_field_ro($tpl,"DenyDnsResolve","{do_not_resolv_ipaddr_wpad}",ppst_meta_bool($DenyDnsResolve));
    }else{
        $form[]=$tpl->field_checkbox("DenyDnsResolve","{do_not_resolv_ipaddr_wpad}",$DenyDnsResolve,false,"{do_not_resolv_ipaddr_wpad_ex}");
        $editable++;
    }
    if(proxypac_meta_holds("ProxyPacLockScript")){
        $form[]=proxypac_meta_field_ro($tpl,"ProxyPacLockScript","{lock_script_with_this_script}",ppst_meta_bool($ProxyPacLockScript));
    }else{
        $form[]=$tpl->field_checkbox("ProxyPacLockScript","{lock_script_with_this_script}",$ProxyPacLockScript);
        $editable++;
    }
    if(proxypac_meta_holds("ProxyPacLockScriptContent")){
        $script=(string)$ProxyPacLockScriptContent;
        if(strlen($script)>500){$script=substr($script,0,500)."...";}
        $form[]=proxypac_meta_field_ro($tpl,"ProxyPacLockScriptContent","{script2}",proxypac_meta_h($script));
    }else{
        $form[]=$tpl->field_textareacode("ProxyPacLockScriptContent", "{script2}", $ProxyPacLockScriptContent);
        $editable++;
    }
    if($editable==0){proxypac_meta_lock_form($tpl);}

    $jsrestart=$tpl->framework_buildjs(
        "/proxypac/reconfigure",
        "autoconfiguration.apply.progress",
        "autoconfiguration.apply.log",
        "progress-proxypac-restart",
        "LoadAjaxTiny('proxypac-status','$page?status=yes');"
    );


    $html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",
        "dialogInstance1.close();LoadAjaxTiny('table-loader-proxypac-service','$page?table=yes');$jsrestart",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $s=ppst_settings();

    $html=array();
    $html[]="<script src='js/jquery.peity.min.js'></script>";
    $html[]=proxypac_meta_banner($tpl);
    $html[]="<div class='ppst-grid'>";
    $html[]="<div class='ppst-side'><div id='proxypac-status'></div>".ppst_listen_card($s)."</div>";
    $html[]="<div class='ppst-main'>";
    $html[]="<div id='proxypac-metrics' aria-live='polite'></div>";
    $html[]=ppst_params($tpl,$s);
    $html[]="</div>";
    $html[]="</div>";

    $jsrestart=$tpl->framework_buildjs("ppac:/build",
        "autoconfiguration.apply.progress",
        "/autoconfiguration.apply.log",
        "progress-proxypac-restart","LoadAjaxTiny('proxypac-status','$page?status=yes');"
    );
    $topbuttons=array();
    $topbuttons[]=array($jsrestart,"fa fa-save","{apply_rules}","AsSquidAdministrator");
    $topbuttons[]=array("Loadjs('fw.proxypac.simul.php')","fas fa-vial","{test_your_rules}");

    $PROXYPAC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
    if($PROXYPAC_VERSION<>null){$PROXYPAC_VERSION=" v{$PROXYPAC_VERSION}";}

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_PROXY_PAC}$PROXYPAC_VERSION";
    $TINY_ARRAY["ICO"]="fad fa-scroll-old";
    $TINY_ARRAY["EXPL"]="{ppst_explain}";
    $TINY_ARRAY["URL"]="proxypac-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    // Etat du service : 3 s (comme avant). Mesures : 30 s, /status relit 24 h d'evenements.
    $js=$tpl->RefreshInterval_js("proxypac-status",$page,"status=yes");
    $js2=$tpl->RefreshInterval_js("proxypac-metrics",$page,"metrics=yes",30);

	$html[]="<script>";
    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="LoadAjaxSilent('proxypac-metrics','$page?metrics=yes');";
    $html[]="$js;$js2;</script>";
    // Rappele par fw.proxypac.rules.php?remote-sync-js=<id>&function=PpstReload apres une synchro.
    $reload="<script>window.PpstReload=function(){if(document.getElementById('proxypac-metrics')){LoadAjaxSilent('proxypac-metrics','$page?metrics=yes');}};</script>";
	echo ppst_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).$reload;
}

/** Reglages du service, lus une fois par rendu. */
function ppst_settings():array{
    $g=function($k){return $GLOBALS["CLASS_SOCKETS"]->GET_INFO($k);};
    $port=intval($g("ProxyPacListenPort"));
    if($port==0){$port=80;}
    $users=new usersMenus();
    return array(
        "iface"=>trim((string)$g("ProxyPacListenInterface")),
        "port"=>$port,
        "ssl"=>intval($g("ProxyPacSSL")),
        "cert"=>trim((string)$g("ProxyPacCertificate")),
        "nginx"=>intval($g("EnableNginx")),
        "lock"=>intval($g("ProxyPacLockScript")),
        "debug"=>intval($g("ProxyPacDebug")),
        "noresolve"=>intval($g("DenyDnsResolve")),
        "admin"=>(bool)$users->AsSquidAdministrator,
    );
}

/** Texte saisi par un administrateur ou venu d'un serveur distant, imprime dans du HTML
 *  qui passe ensuite par _ENGINE_parse_body : les accolades et crochets seraient
 *  developpes en {token} / [url] apres l'echappement. */
function ppst_safe($text,$max=300):string{
    $text=(string)$text;
    if(strlen($text)>$max){$text=substr($text,0,$max)."...";}
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars($text,ENT_QUOTES));
}

/** Valeur 0/1 d'un reglage tenu par Meta, montre en lecture seule. */
function ppst_meta_bool($v):string{
    return intval($v)==1?"{enabled}":"{disabled}";
}

/** Carte Listen (colonne gauche) : mode d'exposition, adresse configuree, certificat. */
function ppst_listen_card($s):string{
    $edit="";
    if($s["admin"]){
        $edit="<a href='#' class='ak-link ppst-edit' aria-label=\"{ppst_edit_main}\" OnClick=\"Loadjs('".CurrentPageName()."?main-form=yes');return false;\"><i class='fa-solid fa-pen' aria-hidden='true'></i>{ppst_edit}</a>";
    }
    if($s["nginx"]==1){
        $chip="<span class='ak-chip ppst-proto'>HTTP</span>";
        $addr="127.0.0.1:9505";
        $sub="<i class='fa-solid fa-diagram-project' aria-hidden='true'></i>{ppst_behind_rp}";
    }elseif($s["ssl"]==1){
        $chip="<span class='ak-chip ak-ok ppst-proto'><i class='fa-solid fa-lock' aria-hidden='true'></i>HTTPS</span>";
        $iface=$s["iface"]==""?"{all_interfaces}":ppst_safe($s["iface"]);
        $addr="$iface:".intval($s["port"]);
        $cert=$s["cert"]==""?"&mdash;":ppst_safe($s["cert"]);
        $sub="<i class='fa-solid fa-certificate' aria-hidden='true'></i>{certificate}: $cert";
    }else{
        $chip="<span class='ak-chip ppst-proto'>HTTP</span>";
        $iface=$s["iface"]==""?"{all_interfaces}":ppst_safe($s["iface"]);
        $addr="$iface:".intval($s["port"]);
        $sub="<i class='fa-solid fa-lock-open' aria-hidden='true'></i>{ppst_http_plain}";
    }
    return "<section class='ak-card ppst-listen'><div class='ppst-ch'><h3 class='ppst-h'><i class='fa-solid fa-ear-listen' aria-hidden='true'></i>{listen}</h3>$edit</div>"
        ."<div class='ppst-lbox'><div class='ppst-lrow'>$chip<span class='ppst-addr'>$addr</span></div><div class='ppst-lsub'>$sub</div></div></section>";
}

/** Parametres principaux : table_form (lecture), chaque ligne ouvre la modale. */
function ppst_params($tpl,$s):string{
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?main-form=yes')","AsSquidAdministrator");
    $tpl->table_form_section("{main_parameters}");
    $tpl->table_form_field_bool("{ppst_debug}||{ppst_debug_explain}",$s["debug"],ico_bug);
    $tpl->table_form_field_bool("{ppst_no_resolve}||{ppst_no_resolve_explain}",$s["noresolve"],ico_proto);
    $tpl->table_form_field_bool("{lock_script_with_this_script}||{ppst_lock_explain}",$s["lock"],ico_script);
    return $tpl->table_form_compile();
}

function save(){
	$tpl=new template_admin();
    // Ecriture : AsSquidAdministrator verifie ici, pas seulement dans le formulaire.
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}

    // SAVE_POSTs ecrit TOUTE cle postee : ni le marqueur ni les champs en lecture seule.
    unset($_POST["ppst-save"]);
    $_POST=proxypac_meta_strip_ro($_POST);
    // Politique Meta : une cle tenue postee refuse tout le formulaire (rien n'est ecrit).
    $meta=proxypac_meta_refuse_keys(array_keys($_POST));
    if($meta!==""){echo $meta;return false;}
    if(count($_POST)==0){return true;}

    // SSL tenu par Meta (non poste) : le port suit la valeur en place.
    $ssl=isset($_POST["ProxyPacSSL"])?intval($_POST["ProxyPacSSL"]):intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacSSL"));
    if(isset($_POST["ProxyPacListenPort"])){
        if($ssl==1){
            if(intval($_POST["ProxyPacListenPort"])==80){
                $_POST["ProxyPacListenPort"]=443;
            }
        }else{
            if(intval($_POST["ProxyPacListenPort"])==443){
                $_POST["ProxyPacListenPort"]=80;
            }
        }
    }

	$tpl->SAVE_POSTs();
    admin_tracks_post("Saving Proxy PAC settings");
    return true;
}

function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid-autoconf.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/proxypac.restart.log";
	$ARRAY["CMD"]="/proxypac/restart";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjaxTiny('proxypac-status','$page?status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-proxypac-restart')";

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/status"));
    if (!is_object($json) || !$json->Status) {
       $err=is_object($json) && isset($json->Error)?$json->Error:"{error}";
       echo  $tpl->widget_rouge($err, "{error}");
       return false;
    }
    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
	echo $tpl->SERVICE_STATUS($ini, "APP_PROXY_PAC",$jsrestart);
    return true;
}

// ---------------------------------------------------------------------------
// Mesures (?metrics=yes) : demon proxy-pac, socket d'administration.
// ---------------------------------------------------------------------------

/** Modele unique : un appel par source, calcule une fois. */
function ppst_model():array{
    $m=array("down"=>true,"total"=>0,"uniq"=>0,"served"=>0,"dropped"=>0,
        "since"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_RQS")),
        "ts_req"=>array(),"ts_uniq"=>array(),"top"=>array(),"remote"=>array(),
        "s"=>ppst_settings(),"now"=>time());
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/status"));
    if(!is_object($j) || (isset($j->Status) && $j->Status===false)){
        // curl 28 = delai depasse : le demon tourne mais n'a pas repondu a temps.
        $m["slow"]=is_object($j) && isset($j->Error) && preg_match('#admin socket 28\b#',(string)$j->Error);
        return $m;
    }
    $m["down"]=false;
    $m["total"]=isset($j->total_requests_24h)?intval($j->total_requests_24h):0;
    $m["uniq"]=isset($j->unique_ips_24h)?intval($j->unique_ips_24h):0;
    $m["served"]=isset($j->pac_served_24h)?intval($j->pac_served_24h):0;
    $m["dropped"]=isset($j->events_dropped)?intval($j->events_dropped):0;
    $m["ts_req"]=ppst_points("/timeseries?window=24h&metric=requests");
    $m["ts_uniq"]=ppst_points("/timeseries?window=24h&metric=unique_ips");
    // rule_id 0 = "aucune regle" : on garde les cinq premieres vraies regles.
    $t=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/top-rules?window=24h&limit=6"));
    if(is_object($t) && isset($t->rows) && is_array($t->rows)){
        foreach($t->rows as $r){
            if(!is_object($r) || intval($r->rule_id)<=0){continue;}
            $m["top"][]=array("name"=>(string)$r->name,"count"=>intval($r->count),"id"=>intval($r->rule_id));
            if(count($m["top"])>=5){break;}
        }
    }
    // Regles a source distante (vide quand aucune) : ce que le demon sert reellement.
    $sy=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/sync"),true);
    if(is_array($sy) && isset($sy["rules"]) && is_array($sy["rules"])){
        foreach($sy["rules"] as $r){
            if(!is_array($r)){continue;}
            $m["remote"][]=array("id"=>intval($r["id"] ?? 0),"name"=>(string)($r["name"] ?? ""),
                "url"=>(string)($r["url"] ?? ""),"interval"=>intval($r["interval"] ?? 0),
                "source"=>(string)($r["source"] ?? ""),"last_success"=>intval($r["last_success"] ?? 0),
                "last_attempt"=>intval($r["last_attempt"] ?? 0),"last_error"=>trim((string)($r["last_error"] ?? "")),
                "size"=>intval($r["size"] ?? 0));
        }
    }
    return $m;
}

/** Valeurs d'une serie temporelle (points {ts,value}), dans l'ordre. */
function ppst_points($endpoint):array{
    $out=array();
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC($endpoint));
    if(!is_object($j) || !isset($j->points) || !is_array($j->points)){return $out;}
    foreach($j->points as $p){
        if(is_object($p) && isset($p->value)){$out[]=intval($p->value);}
    }
    return $out;
}

/** Nombre avec espace fine insecable comme separateur de milliers. */
function ppst_n($v):string{
    return number_format((float)$v,0,".","&#8239;");
}

/** Age relatif "12 min ago" ; une date dans le futur (horloge reculee) compte pour 0. */
function ppst_ago($ts,$now):string{
    $d=max(0,$now-intval($ts));
    if($d<3600){$v=max(1,intval($d/60))." {ppst_unit_min}";}
    elseif($d<86400){$v=intval($d/3600)." {ppst_unit_h}";}
    else{$v=intval($d/86400)." {ppst_unit_d}";}
    return str_replace("%s",$v,ppst_t("ppst_ago"));
}

/** Token traduit tout de suite : un libelle a %s se traduit AVANT la substitution. */
function ppst_t($token):string{
    if(!isset($GLOBALS["PPST_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["PPST_T"][$token]=$tpl->_ENGINE_parse_body("{".$token."}");
    }
    return $GLOBALS["PPST_T"][$token];
}

function ppst_size($bytes):string{
    $b=intval($bytes);
    if($b<=0){return "&mdash;";}
    if($b<1024){return "$b B";}
    if($b<1048576){return sprintf("%.1f KB",$b/1024);}
    return sprintf("%.1f MB",$b/1048576);
}

/** URL affichee sans identifiants ni requete (un jeton peut s'y trouver). */
function ppst_url($url):string{
    $p=@parse_url($url);
    if(!is_array($p) || !isset($p["host"])){return ppst_safe($url,200);}
    $out=(isset($p["scheme"])?$p["scheme"]."://":"").$p["host"].(isset($p["port"])?":".intval($p["port"]):"").(isset($p["path"])?$p["path"]:"");
    return ppst_safe($out,200);
}

function ppst_accent():array{
    $a=array("accent"=>"#0a8059","accent_soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent"])){$a["accent"]=$b["accent"];}
        if(!empty($b["accent_soft"])){$a["accent_soft"]=$b["accent_soft"];}
    }
    return $a;
}

function metrics(){
    $tpl=new template_admin();
    $m=ppst_model();
    $h=array();
    $h[]=ppst_banners($m);
    $h[]="<div class='ppst-cap24'><span><i class='fa-regular fa-clock' aria-hidden='true'></i>{ppst_last_24h}</span><span>{ppst_aggregated}</span></div>";
    $h[]="<div class='ppst-tiles'>";
    $h[]=ppst_tile_served($m);
    $h[]=ppst_tile_requests($m);
    $h[]=ppst_tile_clients($m);
    $h[]="</div>";
    $h[]=ppst_top_rules($m);
    $h[]=ppst_remote($m);
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h)).ppst_js();
}

/** Bandeaux : demon injoignable, verrou, evenements perdus, debug. Rien quand tout va bien. */
function ppst_banners($m):string{
    $s=$m["s"];
    $out=array();
    $edit=$s["admin"]?"<a href='#' class='ak-link ppst-bedit' OnClick=\"Loadjs('".CurrentPageName()."?main-form=yes');return false;\">{ppst_edit}</a>":"";
    if($m["down"]){
        $why=empty($m["slow"])?"{proxy_pac_daemon_unreachable}":"{ppst_daemon_slow}";
        $out[]="<div class='ak-banner ak-warn ppst-banner' role='status'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i><span class='ak-banner-m'>$why &ndash; {ppst_stats_na}</span></div>";
    }
    if($s["lock"]==1){
        $out[]="<div class='ak-banner ak-warn ppst-banner' role='status'><i class='fa-solid fa-lock' aria-hidden='true'></i><span class='ak-banner-m'>{ppst_lock_banner}</span>$edit</div>";
    }
    if(!$m["down"] && $m["dropped"]>0){
        $out[]="<div class='ak-banner ak-warn ppst-banner' role='status'><i class='fa-solid fa-gauge-high' aria-hidden='true'></i><span class='ak-banner-m'>".ppst_n($m["dropped"])." {ppst_events_dropped}</span></div>";
    }
    if($s["debug"]==1){
        $out[]="<div class='ak-banner ak-info ppst-banner' role='status'><i class='fa-solid fa-bug' aria-hidden='true'></i><span class='ak-banner-m'>{ppst_debug_banner}</span>$edit</div>";
    }
    return @implode("",$out);
}

/** Sparkline peity, pic par tranche de 30 min et reperes 24 h / maintenant. */
function ppst_spark($values,$stroke,$fill,$label):string{
    if(count($values)<2){return "<div class='ppst-spark-empty' aria-hidden='true'></div>";}
    $peak=ppst_n(max($values));
    return "<div class='ppst-spark' role='img' aria-label=\"$label, {ppst_last_24h}, {ppst_peak} $peak\"><span class='ppst-line' data-stroke='$stroke' data-fill='$fill'>".@implode(",",$values)."</span></div>"
        ."<div class='ppst-axis' aria-hidden='true'><span>{ppst_24h_ago}</span><span>{ppst_now}</span></div>";
}

function ppst_peak($values):string{
    if(count($values)<2){return "";}
    return "<span class='ppst-peak'>{ppst_peak_l} <strong>".ppst_n(max($values))."</strong> {ppst_per_30m}</span>";
}

function ppst_tile($cls,$head,$body,$foot):string{
    return "<section class='ak-card ppst-tile $cls'>$head$body<div class='ppst-foot'>$foot</div></section>";
}

function ppst_tile_served($m):string{
    $acc=ppst_accent();
    $total=$m["total"];$served=$m["served"];
    $v=$m["down"]?"&mdash;":ppst_n($served);
    if($m["down"] || $total<=0){
        $donut="<span class='ppst-donut-empty' aria-hidden='true'></span>";
        $cap=$m["down"]?"&mdash;":"{ppst_no_request_24h}";
        $foot="<span>&nbsp;</span>";
    }else{
        $pct=$served*100/$total;
        $donut="<span class='ppst-donut-w' role='img' aria-label=\"".sprintf("%.1f",$pct)."% {ppst_answered_by_rule}\"><span class='ppst-donut' data-fill='".htmlspecialchars($acc["accent"],ENT_QUOTES)."'>$served/$total</span><span class='ppst-donut-t'>".round($pct)."%</span></span>";
        $cap="<strong>".sprintf("%.1f",$pct)."%</strong> {ppst_answered_by_rule}";
        $foot="<span>{ppst_without_rule}: <strong>".ppst_n(max(0,$total-$served))."</strong> <span class='ppst-muted'>({ppst_direct_served})</span></span>";
    }
    $head="<div class='ppst-row ppst-row-top'><div><div class='ak-fig-l'>{pac_served}</div><span class='ppst-val'>$v</span></div>$donut</div>";
    return ppst_tile("ppst-t-srv",$head,"<div class='ppst-line2'>$cap</div>",$foot);
}

function ppst_tile_requests($m):string{
    $v=$m["down"]?"&mdash;":ppst_n($m["total"]);
    $head="<div class='ppst-row'><div class='ak-fig-l'>{requests}</div>".($m["down"]?"":ppst_peak($m["ts_req"]))."</div>"
        ."<div class='ppst-row'><span class='ppst-val'>$v</span><span class='ppst-cap'>{ppst_last_24h}</span></div>";
    $body=$m["down"]?"<div class='ppst-spark-empty' aria-hidden='true'></div>":ppst_spark($m["ts_req"],"#006a62","rgba(0,106,98,.12)","{requests}");
    return ppst_tile("ppst-t-req",$head,$body,"<span>{ppst_since_start}: <strong>".ppst_n($m["since"])."</strong></span>");
}

function ppst_tile_clients($m):string{
    $v=$m["down"]?"&mdash;":ppst_n($m["uniq"]);
    $head="<div class='ppst-row'><div class='ak-fig-l'>{unique_clients}</div>".($m["down"]?"":ppst_peak($m["ts_uniq"]))."</div>"
        ."<div class='ppst-row'><span class='ppst-val'>$v</span><span class='ppst-cap'>{ppst_distinct_ips}</span></div>";
    $body=$m["down"]?"<div class='ppst-spark-empty' aria-hidden='true'></div>":ppst_spark($m["ts_uniq"],"#0e7490","rgba(14,116,144,.12)","{unique_clients}");
    $foot="<span>&nbsp;</span>";
    if(!$m["down"] && $m["uniq"]>0){
        $foot="<span><strong>".ppst_n(round($m["total"]/$m["uniq"]))."</strong> {ppst_avg_per_client}</span>";
    }
    return ppst_tile("ppst-t-cli",$head,$body,$foot);
}

/** Cinq premieres regles sur 24 h, mesurees contre toutes les requetes. */
function ppst_top_rules($m):string{
    $link="<a href='#' class='ak-link' OnClick=\"LoadAjax('MainContent','fw.proxypac.stats.php');return false;\"><i class='fa-solid fa-chart-line' aria-hidden='true'></i>{ppst_all_statistics}</a>";
    $h="<section class='ak-card ppst-card'><div class='ppst-ch'><div><h3 class='ppst-h'><i class='fa-solid fa-ranking-star' aria-hidden='true'></i>{ppst_top_rules}</h3><div class='ppst-sub'>{ppst_top_rules_sub}</div></div>$link</div>";
    if($m["s"]["lock"]==1){
        return $h."<div class='ak-empty-c ppst-empty'><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{ppst_lock_rules}</span></div></section>";
    }
    if($m["down"]){
        return $h."<div class='ak-empty-c ppst-empty'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i><span>{ppst_stats_na}</span></div></section>";
    }
    if(count($m["top"])==0){
        return $h."<div class='ak-empty-c ppst-empty'><i class='fa-regular fa-circle-question' aria-hidden='true'></i><span>{ppst_no_rule_24h}</span></div></section>";
    }
    $rows=array();
    foreach($m["top"] as $t){
        $name=ppst_safe($t["name"]<>""?$t["name"]:"#".$t["id"],200);
        $pct=$m["total"]>0?min(100,$t["count"]*100/$m["total"]):0;
        $p=sprintf("%.1f",$pct);
        $rows[]="<li class='ppst-tr'><span class='ppst-tname' title=\"$name\"><span class='ak-dot ak-ok' aria-hidden='true'></span><span class='ppst-tn'>$name</span></span>"
            ."<span class='ak-bar ppst-tbar' role='img' aria-label=\"$p% {ppst_of_all_requests}\"><span style='width:$p%'></span></span>"
            ."<span class='ppst-tcount'>".ppst_n($t["count"])."</span><span class='ppst-tpct'>$p%</span></li>";
    }
    return $h."<ul class='ppst-tlist'>".@implode("",$rows)."</ul></section>";
}

/** Sources PAC distantes : carte affichee seulement quand au moins une regle en a une. */
function ppst_remote($m):string{
    if(count($m["remote"])==0){return "";}
    $admin=$m["s"]["admin"];
    $now=$m["now"];
    $rows=array();
    foreach($m["remote"] as $r){
        $id=intval($r["id"]);
        $name=ppst_safe($r["name"]<>""?$r["name"]:"#$id",200);
        $url=ppst_url($r["url"]);
        $every=$r["interval"]>0?ppst_n($r["interval"])." {ppst_unit_min}":"&mdash;";
        $remote=($r["source"]=="remote");
        $chip=$remote?"<span class='ak-chip ak-ok'><i class='fa-solid fa-cloud' aria-hidden='true'></i>{ppst_src_remote}</span>"
                     :"<span class='ak-chip ak-warn'><i class='fa-solid fa-house' aria-hidden='true'></i>{ppst_src_local}</span>";
        if($r["last_success"]>0){
            $last="<span title=\"".htmlspecialchars(date("Y-m-d H:i:s",$r["last_success"]),ENT_QUOTES)."\">".ppst_ago($r["last_success"],$now)."</span>";
        }else{
            $last="<span class='ppst-never'>{ppst_never}</span>";
        }
        $act="";
        if($admin){
            $act="<div class='ak-acts'><button type='button' class='ak-act' aria-label=\"{remote_pac_sync_now}\" title=\"{remote_pac_sync_now}\" OnClick=\"Loadjs('fw.proxypac.rules.php?remote-sync-js=$id&function=PpstReload');\"><i class='fa-solid fa-rotate' aria-hidden='true'></i></button></div>";
        }
        $rows[]="<tr><td><span class='ppst-rname' title=\"$name\">$name</span></td><td><span class='ak-mono ppst-url' title=\"$url\">$url</span></td>"
            ."<td class='ak-nowrap'>$every</td><td>$chip</td><td class='ak-nowrap'>$last</td><td class='ak-num ak-nowrap'>".ppst_size($r["size"])."</td><td class='ak-end'>$act</td></tr>";
        if($r["last_error"]<>""){
            $when=$r["last_attempt"]>0?ppst_ago($r["last_attempt"],$now):"";
            $still=$remote?"{remote_pac_served_copy}":"{remote_pac_served_local}";
            $rows[]="<tr class='ppst-errrow'><td colspan='7'><div class='ppst-err' role='status'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>"
                ."<span><strong>".str_replace("%s",$when,ppst_t("ppst_attempt_failed"))."</strong> ".ppst_safe($r["last_error"])." <span class='ppst-muted'>($still)</span></span></div></td></tr>";
        }
    }
    $h=array();
    $h[]="<section class='ak-card ppst-card'><div class='ppst-ch'><div><h3 class='ppst-h'><i class='fa-solid fa-cloud-arrow-down' aria-hidden='true'></i>{ppst_remote_title}</h3><div class='ppst-sub'>{ppst_remote_sub}</div></div></div>";
    $h[]="<div class='ppst-tscroll'><table class='ak-tbl ppst-rtbl'><thead><tr><th class='ppst-c-rule'>{ppst_col_rule}</th><th>{ppst_col_url}</th><th class='ppst-c-every'>{ppst_col_every}</th><th class='ppst-c-served'>{ppst_col_served}</th><th class='ppst-c-last'>{ppst_col_last}</th><th class='ak-num ppst-c-size'>{ppst_col_size}</th><th class='ak-end ppst-c-act'>".($admin?"{ppst_col_action}":"&nbsp;")."</th></tr></thead>";
    $h[]="<tbody>".@implode("",$rows)."</tbody></table></div></section>";
    return @implode("",$h);
}

/** Dessine les graphiques peity du fragment (peity est charge par table()). */
function ppst_js():string{
    return "<script>(function(){function draw(){if(!window.jQuery||!jQuery.fn.peity){return;}"
        ."jQuery('#proxypac-metrics .ppst-line').each(function(){var e=jQuery(this);e.peity('line',{width:'100%',height:40,stroke:e.data('stroke'),fill:e.data('fill'),strokeWidth:2});});"
        ."jQuery('#proxypac-metrics .ppst-donut').each(function(){var e=jQuery(this);e.peity('donut',{radius:24,innerRadius:18,fill:[e.data('fill'),'#e2e8f0']});});}"
        ."if(window.jQuery&&jQuery.fn.peity){draw();}else if(window.jQuery){jQuery.getScript('js/jquery.peity.min.js',draw);}})();</script>";
}

/** CSS de la page (hors _ENGINE_parse_body) : seulement ce que le kit ak- ne fournit pas. */
function ppst_style():string{
    $acc=ppst_accent();
    $a=$acc["accent"];
    $c=array();
    $c[]="<style>";
    $c[]=".ppst-grid{display:flex;align-items:flex-start;gap:24px;margin-top:8px}";
    $c[]=".ppst-side{flex:0 0 336px;min-width:0;display:flex;flex-direction:column;gap:16px}";
    $c[]=".ppst-side .svcst-wrap{width:100% !important}";
    $c[]=".ppst-main{flex:1 1 auto;min-width:0}";
    $c[]=".ppst-ch{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}";
    $c[]=".ppst-h{display:flex;align-items:center;gap:8px;margin:0;font-size:16px;font-weight:600;color:#0f172a}";
    $c[]=".ppst-sub{margin-top:2px;font-size:13px;color:#475569}";
    $c[]=".ppst-listen{padding:16px;box-sizing:border-box}";
    $c[]=".ppst-lbox{padding:12px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0}";
    $c[]=".ppst-lrow{display:flex;align-items:center;justify-content:space-between;gap:8px;min-width:0}";
    $c[]=".ppst-lsub{display:flex;align-items:center;gap:6px;margin-top:6px;font-size:13px;color:#475569;overflow-wrap:anywhere}";
    $c[]=".ppst-proto{font-weight:700}";
    $c[]=".ppst-addr{font-family:monospace;font-size:13px;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".ppst-banner{margin-bottom:12px;font-weight:600}.ppst-banner .ak-banner-m{flex:1 1 auto}";
    $c[]=".ppst-cap24{display:flex;justify-content:space-between;gap:12px;margin:4px 0 8px;font-size:13px;color:#475569}.ppst-cap24 span{display:inline-flex;align-items:center;gap:6px}";
    $c[]=".ppst-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px}";
    $c[]=".ppst-tile{display:flex;flex-direction:column;justify-content:space-between;min-height:168px;padding:16px;color:#334155;box-sizing:border-box}";
    $c[]=".ppst-t-srv{border-top:3px solid $a}";
    $c[]=".ppst-row{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-top:4px;min-width:0}";
    $c[]=".ppst-row-top{align-items:flex-start;margin-top:0}";
    $c[]=".ppst-val{font-size:32px;font-weight:700;line-height:1.1;color:#0f172a;font-variant-numeric:tabular-nums;white-space:nowrap}";
    $c[]=".ppst-cap{font-size:13px;color:#475569;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".ppst-peak{font-size:13px;color:#0e7490;white-space:nowrap}.ppst-peak strong{font-variant-numeric:tabular-nums}";
    $c[]=".ppst-spark{height:40px;margin:10px 0 2px}.ppst-spark svg{display:block;width:100%}";
    $c[]=".ppst-axis{display:flex;justify-content:space-between;font-size:13px;color:#64748b;margin-bottom:4px}";
    $c[]=".ppst-spark-empty{border-bottom:2px dashed #e2e8f0;height:20px;margin-bottom:24px}";
    $c[]=".ppst-line2{margin:10px 0 6px;font-size:13px;color:#475569}.ppst-line2 strong{color:#0f172a}";
    $c[]=".ppst-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:8px;border-top:1px solid #f1f5f9;font-size:13px;color:#475569;min-width:0}";
    $c[]=".ppst-foot strong{color:#0f172a;font-variant-numeric:tabular-nums}";
    $c[]=".ppst-muted{color:#64748b}";
    $c[]=".ppst-donut-w{position:relative;flex:0 0 48px;width:48px;height:48px}.ppst-donut-w svg{display:block}";
    $c[]=".ppst-donut-t{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#334155}";
    $c[]=".ppst-donut-empty{flex:0 0 48px;width:48px;height:48px;border-radius:50%;border:6px solid #e2e8f0;box-sizing:border-box}";
    $c[]=".ppst-card{padding:16px 20px;margin-bottom:16px;box-sizing:border-box}";
    $c[]=".ppst-empty{padding:20px 0}";
    $c[]=".ppst-tlist{list-style:none;margin:0;padding:0}";
    $c[]=".ppst-tr{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,3fr) 80px 60px;align-items:center;gap:16px;min-height:40px;border-top:1px solid #f1f5f9}.ppst-tr:first-child{border-top:0}";
    $c[]=".ppst-tname{display:flex;align-items:center;gap:8px;min-width:0;font-size:14px;font-weight:600;color:#0f172a}";
    $c[]=".ppst-tn{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".ppst-tbar{height:8px;border-radius:4px}";
    $c[]=".ppst-tcount{text-align:right;font-weight:600;color:#0f172a;font-variant-numeric:tabular-nums}";
    $c[]=".ppst-tpct{text-align:right;font-size:13px;color:#475569;font-variant-numeric:tabular-nums}";
    $c[]=".ppst-tscroll{overflow-x:auto;margin:0 -20px -16px}";
    $c[]=".ppst-rtbl{table-layout:fixed}.ppst-rtbl th,.ppst-rtbl td{box-sizing:border-box}.ppst-rtbl td{font-size:14px;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".ppst-rtbl th{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".ppst-rtbl .ppst-c-rule{width:22%}.ppst-rtbl .ppst-c-every{width:88px}.ppst-rtbl .ppst-c-served{width:156px}.ppst-rtbl .ppst-c-last{width:120px}.ppst-rtbl .ppst-c-size{width:84px}.ppst-rtbl .ppst-c-act{width:64px}";
    $c[]=".ppst-rtbl tr.ppst-errrow td{white-space:normal}";
    $c[]=".ppst-rname{font-weight:600;color:#0f172a}";
    $c[]=".ppst-url{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;font-size:13px}";
    $c[]=".ppst-never{color:#865300;font-weight:600}";
    $c[]=".ppst-rtbl tr.ppst-errrow td{padding-top:0}";
    $c[]=".ppst-err{display:flex;align-items:flex-start;gap:8px;padding:8px 12px;border-radius:8px;background:#ffdad6;color:#ba1a1a;font-size:13px;overflow-wrap:anywhere}";
    $c[]=".ppst-err .ppst-muted{color:#3d4947}";
    $c[]=".ppst-edit,.ppst-bedit{flex:0 0 auto}";
    $c[]="@media (prefers-reduced-motion:no-preference){#proxypac-metrics .ppst-tile,#proxypac-metrics .ppst-card{animation:ppstIn .15s ease-out}}";
    $c[]="@keyframes ppstIn{from{opacity:.6}to{opacity:1}}";
    $c[]="@media (max-width:1199px){.ppst-grid{flex-direction:column}.ppst-side{flex:none;width:100%;flex-direction:row;flex-wrap:wrap}.ppst-side>*{flex:0 0 336px;max-width:100%}.ppst-main{width:100%}}";
    $c[]="@media (max-width:1023px){.ppst-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    $c[]="</style>";
    return @implode("\n",$c);
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
