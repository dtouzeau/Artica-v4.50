<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfw.views.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Reglages du schema du lot 3 par PUT /unbound/api/settings/{groupe} (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
include_once(dirname(__FILE__)."/ressources/class.unbound.settings.inc");
// Verrou d'une politique Meta dnscache : champs tenus en lecture seule, refus cote serveur.
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Constats non bloquants (avertissements) d'un reglage applique par l'API.
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
if(isset($_GET["unbound-performance-redis-js"])){unbound_performance_redis_js();exit;}
if(isset($_GET["unbound-performance-redis-popup"])){unbound_performance_redis_popup();exit;}
if(isset($_POST["UnboundRedisEnabled"])){save();exit;}
if(isset($_POST["SaveSecurity"])){save();exit;}

if(isset($_GET["unbound-security-js"])){unbound_security_js();exit;}

if(isset($_GET["unbound-dnssec-js"])){unbound_dnssec_js();exit;}
if(isset($_GET["unbound-dnssec-popup"])){unbound_dnssec_popup();exit;}
if(isset($_POST["UnBoundDNSSEC"])){unbound_dnssec_save();exit;}
if(isset($_GET["unbound-security-popup"])){unbound_security_popup();exit;}

if(isset($_GET["unbound-interface-js"])){unbound_interfaces_js();exit;}
if(isset($_GET["unbound-interface-popup"])){unbound_interfaces_popup();exit;}

if(isset($_GET["unbound-performance-form-js"])){unbound_performance_js();exit;}
if(isset($_GET["unbound-performance-form-popup"])){unbound_performance_popup();exit;}

if(isset($_GET["unbound-globalDoms-js"])){unbound_globaldoms_js();exit;}
if(isset($_GET["unbound-globalDoms-popup"])){unbound_globaldoms_popup();exit;}

if(isset($_GET["unbound-doh-js"])){unbound_doh_js();exit;}
if(isset($_GET["unbound-doh-popup"])){unbound_doh_popup();exit;}

if(isset($_GET["unbound-tls-js"])){unbound_tls_js();exit;}
if(isset($_GET["unbound-tls-popup"])){unbound_tls_popup();exit;}

if(isset($_GET["unbound-cache-js"])){unbound_cache_js();exit;}
if(isset($_GET["unbound-cache-popup"])){unbound_cache_popup();exit;}

if(isset($_GET["unbound-monitor-js"])){unbound_monitor_js();exit;}
if(isset($_GET["unbound-monitor-popup"])){unbound_monitor_popup();exit;}
if(isset($_GET["refresh-dnsdist-left-status-start"])){dnsdist_status_left_refresh_start();exit;}
if(isset($_GET["refresh-dnsdist-left-status"])){dnsdist_status_left_refresh();exit;}


if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}

if(isset($_POST["EnableDNSRootInts"])){save();exit;}
if(isset($_POST["DisableLocalReservation"])){save();exit;}
if(isset($_POST["UnboundMaxLogsize"])){save();exit;}
if(isset($_POST["UnboundAutomaticInterface"])){save();exit;}
if(isset($_POST["UnBoundCacheSize"])){save();exit;}
if(isset($_POST["UnboundDOHEnable"])){save();exit;}
if(isset($_POST["UnboundTLSEnable"])){save();exit;}
// Popup {monitoring} : aucune de ses cles n'etait dispatchee, le POST tombait
// dans page() et la modale affichait la page au lieu d'enregistrer.
if(isset($_POST["RefuseDNSRoot"])){save();exit;}

if(isset($_POST["UnboundDisplayVersion"])){save();exit;}
if(isset($_POST["UnboundStatsCom"])){SaveGeneric();exit;}
if(isset($_GET["unbound-status"])){unbst_live();exit;}
if(isset($_GET["unbound-status-left"])){unbst_left();exit;}
if(isset($_GET["unbst-quality"])){unbst_quality_live();exit;}
if(isset($_GET["unbst-chart"])){unbst_chart();exit;}
if(isset($_GET["unbound-uninstall"])){unbound_uninstall();exit;}
if(isset($_POST["uninstall-unbound"])){exit;}
if(isset($_GET["reload-status"])){reload_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_GET["dnsdist-status"])){dnsdist_status();exit;}
if(isset($_GET["dnsdist-status-left"])){dnsdist_status_left();exit;}
if(isset($_GET["dnsdist-status-center"])){dnsdist_status_center();exit;}
// Serveur dnsdist up/down/auto : *-js ouvre la confirmation, *-popup la rend, puis POST.
if(isset($_GET["dnsdist-server-js"])){dnsdist_server_js();exit;}
if(isset($_GET["dnsdist-server-popup"])){dnsdist_server_popup();exit;}
if(isset($_POST["dnsdist-server-id"])){dnsdist_server_save();exit;}
if(isset($_GET["fw-status"])){fw_database_status_js();exit;}
if(isset($_GET["fw-status-popup"])){fw_database_status_popup();exit;}
if(isset($_GET["addhost-js"])){hostadd_js();exit;}
if(isset($_GET["addhost-popup"])){hostadd_popup();exit;}
if(isset($_POST["addhost"])){hostadd_save();exit;}
if(isset($_GET["statistics-appliance"])){statistics_appliance_js();exit;}
if(isset($_GET["statistics-appliance-popup"])){statistics_appliance_popup();exit;}
if(isset($_GET["statistics-appliance-remove"])){statistics_appliance_remove();exit;}
if(isset($_POST["stats-appliance-remove"])){statistics_appliance_remove_save();exit;}
if(isset($_GET["pool-down"])){pool_down();exit;}
if(isset($_POST["UNBOUND_OUTGOING_RANGE"])){save();exit;}
// Un champ tenu par une politique Meta est poste sous UnboundMetaRO<cle> : quand c'etait la
// cle d'aiguillage de son popup, le reste du formulaire doit quand meme etre enregistre.
if(unbm_dispatch_ro()){exit;}
page();
/** Aiguillage d'un formulaire dont la cle d'aiguillage est en lecture seule (verrou Meta). */
function unbm_dispatch_ro():bool{
    foreach(array_keys($_POST) as $k){
        if(strpos((string)$k,"UnboundMetaRO")!==0){continue;}
        $key=substr((string)$k,strlen("UnboundMetaRO"));
        if(strpos($key,"UnboundStatsCom")===0){SaveGeneric();return true;}
        save();
        return true;
    }
    return false;
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
    $Title="{APP_UNBOUND} v{$UnboundVersion}";
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $explain="{didyouknow_unbound}";
    $raccourci="dns-cache";

    if($EnableDNSDist==1){
        $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
        $Title="{APP_DNSDIST} v{$APP_DNSDIST_VERSION}";
        $explain="{APP_DNSDIST_EXPLAIN2}";
        $raccourci="dns-firewall";
    }

    $html=$tpl->page_header($Title,"fa fas fa-database",$explain,"$page?tabs=yes",$raccourci,"progress-unbound-restart",false,"table-loader-dns-servers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function hostadd_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   $tpl->js_dialog7("{new_host_resolve}","Loadjs('$page?addhost-popup=yes')");
}
function unbound_uninstall(){

    $tpl = new template_admin();
    // Desinstaller unbound : privilege verifie ici ET porte par la tache
    // signee ($privileges), que fw.progress.php reverifie.
    if(!unbound_settings_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS2}");return; }
    $js = $tpl->framework_buildjs("/unbound/service/uninstall",
        "unbound.install.progress",
        "unbound.install.log", "progress-unbound-restart", "document.location.href='/dns-servers'",null,null,"AsDnsAdministrator");

    echo $tpl->js_confirm_delete("{APP_UNBOUND}", "uninstall-unbound", "yes", $js);
}

function statistics_appliance_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog7("{APP_STATISTICS_APPLIANCE}","Loadjs('$page?statistics-appliance-popup=yes')");
}
function SaveGeneric(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // Hors schema de l'API : /setinfo comme avant, mais sous privilege, refuse
    // sur un esclave, et pour les seules cles du formulaire (SAVE_POSTs
    // ecrivait toute cle postee).
    if(!unbound_settings_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $refusal=unbound_settings::legacy_refusal(new unbound_api());
    if($refusal!==""){ echo $tpl->post_error($refusal);return false; }
    $keys=array("UnboundStatsCom","UnboundStatsComAddress","UnboundStatsComPort","UnboundStatsComTCP","UnboundStatsComUseSSL","UnboundStatsComCertificate");
    // /setinfo ne connait pas le verrou Meta : une cle tenue refuse tout le formulaire.
    $meta=unbound_meta_refuse_keys(array_intersect(array_keys($_POST),$keys));
    if($meta!==""){ echo $meta;return false; }
    $split=unbound_settings::split($_POST,$keys);
    $e=unbound_settings::setinfo($split["legacy"]);
    if($e!==""){ echo $tpl->post_error($e);return false; }
    if(isset($_POST["UnboundStatsCom"])){
        if($_POST["UnboundStatsCom"]==1){
            admin_tracks("Enable send DNS telemetry to a Statisics Appliance {$_POST["UnboundStatsComAddress"]}");
        }
    }
    return true;
}

/** Controle cote serveur de chaque handler de reglage : form_outside ne fait que masquer le bouton. */
function unbound_settings_can_write():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

/** Le JavaScript qui montre, sans bloquer, les constats d'un reglage applique (session). */
function api_notices_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
        return $tpl->javascript_parse_text($s,0,true);
    });
    // Une cle hors schema appliquee par le redemarrage console a change (save()).
    if(session_status()===PHP_SESSION_ACTIVE && !empty($_SESSION["UNBOUND_SETTINGS_RESTART"])){
        unset($_SESSION["UNBOUND_SETTINGS_RESTART"]);
        echo "\nLoadjs('fw.dns.unbound.restart.php');";
    }
}

function unbound_settings_notices_call():string{
    return "Loadjs('".CurrentPageName()."?api-notices-js=yes')";
}
function statistics_appliance_remove():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("/unbound/syslog/build",
        "dns.syslog.progress","dns.syslog.log","progress-unbound-restart","Loadjs('$page?reload-status=yes')",null,null,"AsDnsAdministrator");

    return  $tpl->js_confirm_execute("{disconnect_stats_appliance}","stats-appliance-remove","yes",$js);

}
function statistics_appliance_remove_save(){
    if(!unbound_settings_can_write()){ echo (new template_admin())->post_error("{ERROR_NO_PRIVS2}");return false; }
    $refusal=unbound_settings::legacy_refusal(new unbound_api());
    if($refusal!==""){ echo (new template_admin())->post_error($refusal);return false; }
    $meta=unbound_meta_refuse_keys(array("UnboundStatsCom"));
    if($meta!==""){ echo $meta;return false; }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundStatsCom",0);
    admin_tracks("Disable send DNS telemetry to a Statistic Appliance service.");
}
function reload_status(){
    $page=CurrentPageName();
    $f[]="if(document.getElementById('unbound-status')){";
    $f[]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('unbound-status-left')){";
    $f[]="LoadAjaxSilent('unbound-status-left','$page?unbound-status-left=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('dnsdist-left')){";
    $f[]="LoadAjax('dnsdist-left','$page?dnsdist-status-left=yes');";
    $f[]="}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);

}

function statistics_appliance_popup(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UnboundStatsCom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsCom"));
    $UnboundStatsComAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComAddress"));
    $UnboundStatsComPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComPort"));
    $UnboundStatsComTCP=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComTCP"));
    $UnboundStatsComUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComUseSSL"));
    $UnboundStatsComCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComCertificate"));
    if($UnboundStatsComPort==0){$UnboundStatsComPort=514;}


   $jsrestart=$tpl->framework_buildjs("/unbound/syslog/build",
        "dns.syslog.progress","dns.syslog.log","progress-unbound-restart","Loadjs('$page?reload-status=yes')",null,null,"AsDnsAdministrator");


    $form[] = unbound_meta_key_locked("UnboundStatsCom")?unbound_meta_field_ro($tpl,"UnboundStatsCom","{enable_feature}",unbm_onoff($UnboundStatsCom)):$tpl->field_checkbox("UnboundStatsCom", "{enable_feature}", $UnboundStatsCom,"UnboundStatsComAddress,UnboundStatsComPort,UnboundStatsComTCP,UnboundStatsComUseSSL,UnboundStatsComCertificate");
    $form[] = unbound_meta_key_locked("UnboundStatsComAddress")?unbound_meta_field_ro($tpl,"UnboundStatsComAddress","{remote_syslog_server}",$UnboundStatsComAddress):$tpl->field_ipv4("UnboundStatsComAddress", "{remote_syslog_server}", $UnboundStatsComAddress);
    $form[] = unbound_meta_key_locked("UnboundStatsComPort")?unbound_meta_field_ro($tpl,"UnboundStatsComPort","{listen_port}",$UnboundStatsComPort):$tpl->field_numeric("UnboundStatsComPort","{listen_port}",$UnboundStatsComPort);
    $form[] = unbound_meta_key_locked("UnboundStatsComTCP")?unbound_meta_field_ro($tpl,"UnboundStatsComTCP","{enable_tcpsockets}",unbm_onoff($UnboundStatsComTCP)):$tpl->field_checkbox("UnboundStatsComTCP","{enable_tcpsockets}",$UnboundStatsComTCP);
    $form[] = unbound_meta_key_locked("UnboundStatsComUseSSL")?unbound_meta_field_ro($tpl,"UnboundStatsComUseSSL","{useSSL}",unbm_onoff($UnboundStatsComUseSSL)):$tpl->field_checkbox("UnboundStatsComUseSSL", "{useSSL}", $UnboundStatsComUseSSL);
    $form[] = unbound_meta_key_locked("UnboundStatsComCertificate")?unbound_meta_field_ro($tpl,"UnboundStatsComCertificate","{certificate}",$UnboundStatsComCertificate):$tpl->field_certificate("UnboundStatsComCertificate", "{certificate}", $UnboundStatsComCertificate);
    if(unbm_all_locked(array("UnboundStatsCom","UnboundStatsComAddress","UnboundStatsComPort","UnboundStatsComTCP","UnboundStatsComUseSSL","UnboundStatsComCertificate"))){unbound_meta_lock_form($tpl);}

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px' valign='top'><div id='nc-status'></div></td>";
    $html[]="<td style='width:100%' valign='top'>";
    $html[]=$tpl->form_outside(null,$form,"{send_syslog_articastats}",
        "{apply}",
        "dialogInstance7.close();$jsrestart",
        "AsDnsAdministrator");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);

}

function hostadd_popup(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;

    $form[]=$tpl->field_text("addhost","{hostname}",null,true);
    $form[]=$tpl->field_ipaddr("ipaddr","{ipaddr}",null,true);

    echo $tpl->form_outside(null,$form,
        "{new_host_resolve_explain}","{add}","dialogInstance7.close();","AsDnsAdministrator");

}

function pool_down(){
    // Reflete dans un selecteur jQuery : liste blanche.
    $div=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["pool-down"]);
    header("content-type: application/x-javascript");
    echo "$('#$div').addClass('text-danger');\n";

}

function hostadd_save(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    if(!unbound_settings_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $refusal=(new unbound_api())->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false; }
    $hostname=$_POST["addhost"];
    $ipaddr=$_POST["ipaddr"];
    $hosts=new hosts("$hostname|$ipaddr");
    $hosts->hostname=$hostname;
    $hosts->ipaddr=$ipaddr;
    $hosts->dnsfixed=1;

    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));

    if($EnableDNSDist==1){
        $sock=new sockets();
        $json=json_decode($sock->REST_API("/dnsfw/addhost/$hostname/$ipaddr"));
        if(!is_object($json) || !$json->Status){
            // L'erreur n'etait pas affichee : un echec passait pour un succes.
            echo $tpl->post_error(is_object($json)?(string)$json->Error:"/dnsfw/addhost: {error}");
            return false;
        }
    }


    if(!$hosts->Save(true)){
        echo $tpl->post_error($hosts->mysql_error);
        return false;
    }
    writelogs("Adding $hostname ip $ipaddr",__FUNCTION__,__FILE__,__LINE__);
    admin_tracks("Add a new host to resolve in DNS Firewall $hostname ($ipaddr)");
    return true;

}


function config_file_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
    $tpl->js_dialog1("{APP_UNBOUND} >> {config_file}", "$page?config-file-popup=yes");

}
function fw_database_status_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $tpl->js_dialog2("{APP_DNS_FIREWALL}: {status}","$page?fw-status-popup=yes",500);

}
/**
 * L'etat de la base du pare-feu DNS: trois valeurs, donc la convention « flat »
 * (table_form_*) et non un tableau. Un tableau a deux colonnes sans en-tete
 * n'est pas une liste d'entites, c'est un panneau d'etat -- et le rendu flat lui
 * donne ce qui manquait: une pastille d'icone par ligne et un alignement stable.
 *
 * Les trois lignes portaient chacune un attribut style EN DOUBLE (le second,
 * qui fixait les largeurs, etait ignore par le navigateur) et etaient precedees
 * d'un <tr> orphelin, jamais ferme.
 */
function fw_database_status_popup(){
    $tpl=new template_admin();

    // RPZ_LOCAL vient de Valkey. unserialize() rend false sur une valeur absente
    // ou tronquee, et false["items"] est un avertissement PHP 8 suivi d'une
    // valeur nulle: la fenetre affichait alors trois lignes vides sans rien
    // dire.
    $raw=base64_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZ_LOCAL"),true);
    $RPZ_LOCAL=($raw===false)?false:@unserialize($raw,array("allowed_classes"=>false));
    if(!is_array($RPZ_LOCAL)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{APP_DNS_FIREWALL}: {no_data}"));
        return;
    }

    $items=$tpl->FormatNumber(intval($RPZ_LOCAL["items"] ?? 0));
    $cate=$tpl->time_to_date(intval($RPZ_LOCAL["compile_time"] ?? 0),true);
    $size=FormatBytes(intval($RPZ_LOCAL["rpz_size"] ?? 0)/1024);

    // Aucun ouvreur d'edition: ce panneau est en lecture seule. table_form_field_js("")
    // desarme explicitement le crayon, qui reste sinon arme d'un appel precedent.
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{last_update}",htmlspecialchars($cate),ico_clock);
    $tpl->table_form_field_text("{records}",htmlspecialchars($items),ico_database);
    $tpl->table_form_field_text("{size}",htmlspecialchars($size),ico_max);

    echo $tpl->table_form_compile();
}


function config_file_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $sock->getFrameWork("sshd.php?config-file=yes");
    $data=@file_get_contents("/etc/unbound/unbound.conf");
    $form[]=$tpl->field_textareacode("configfile", null, $data);



    echo $tpl->form_outside("{config_file}", @implode("", $form),"{display_generated_configuration_file}",null,"","AsSystemAdministrator");

}




function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $MUNIN=false;
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $EnableDebianAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDebianAgent"));
    $statusF="{status}";


    if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}
    if($EnableDNSDist==1){
        $array["{status}"]="$page?dnsdist-status=yes";
        $statusF="{general_parameters}";

    }


    $array[$statusF]="$page?table-start=yes";
    if($EnableDebianAgent==0 ){
        $array["{networks_restrictions}"] = "fw.pdns.restrictions.php?tinypage-unbound=yes";
    }
    if($EnableDNSDist==1){
        unset($array["{networks_restrictions}"]);
    }

    if($UnboundEnabled==1) {
        $array["{cache}"]="fw.dns.unbound.cache.php";
    }


    $EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));

    if($EnableDNSCryptProxy==1){
        $array["{APP_DNSCRYPT_PROXY}"]="fw.dnscrypt-proxy.php";
    }
    if($EnableDNSDist==1) {
        $array["DNS-over-HTTPS (DoH)"]="fw.dnsdist.doh.php";

    }


    if($UnboundEnabled==1) {
        if ($MUNIN) {
            $array["{statistics}"] = "fw.unbound.statistics.php";
        }
    }
    echo $tpl->tabs_default($array);

}
function dnsdist_status_tiny():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $function="";
    if(isset($_GET["function"])){
        // Reflete dans du JavaScript (OnClick, Loadjs) : liste blanche.
        $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
    }
    if($users->AsDnsAdministrator) {
        $topbuttons[] = array("Loadjs('$page?addhost-js=yes&function=$function');", "fad fa-desktop", "{new_host_resolve}");
    }
    $topbuttons[] = array("LoadAjaxSilent('MainContent','fw.dns.dnsdist.rules.php');", "fab fa-gripfire", "{firewall_rules}");


    if($users->AsDnsAdministrator) {
        $topbuttons[] = array("Loadjs('fw.dnsdist.statistics.php?restart-js=yes&function=$function&id=dnsdist-restart-bybutton');", ico_refresh, "{restart_service}");
    }
    $error=dnsdist_CountOfInterfaces();
    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_DNSDIST} v$APP_DNSDIST_VERSION";
    $TINY_ARRAY["ICO"]="fab fa-gripfire";
    $TINY_ARRAY["EXPL"]="$error{APP_DNSDIST_EXPLAIN2}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    return $jstiny;
}
function dnsdist_CountOfInterfaces():string{
    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
    $Count=0;
    foreach ($PowerDNSListenAddr as $interface){
        $interface=trim($interface);
        if (strlen($interface)<3){
            continue;
        }
        $Count++;
    }
    if($Count>0){return "";}
    $tpl=new template_admin();
    return "<p class='text-danger'><strong>".$tpl->td_href("{no_listen_interfaces_defined}",null,"Loadjs('fw.dns.dnsdist.settings.php?dnsdist-interface-js=yes');")."</strong></p>";
}
function dnsdist_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $warn="";
    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION<12) {
        $SOFT_NO_LONGER_SUPPORT = $tpl->_ENGINE_parse_body("{SOFT_NO_LONGER_SUPPORT}");
        $SOFT_NO_LONGER_SUPPORT = str_replace("<br>","",$SOFT_NO_LONGER_SUPPORT);
        $SOFT_NO_LONGER_SUPPORT = str_replace("%p", "{APP_DNSDIST}", $SOFT_NO_LONGER_SUPPORT);
        $SOFT_NO_LONGER_SUPPORT = str_replace("%deb", "<strong>Debian 12</strong>", $SOFT_NO_LONGER_SUPPORT);
        $warn="<p class='text-warning' style='margin: 5px'>$SOFT_NO_LONGER_SUPPORT</p>";
    }

    $html[]="$warn<div id='dnsdist-restart-bybutton' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";
    $html[]="<td style='width:337px;vertical-align: top'><div id='dnsdist-left' style='min-width: 337px !important'></div></td>";
    $html[]="<td style='padding-left:20px;vertical-align:top'>";
    $html[]="<div id='dnsdist-center' style=''></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    //<i class="fad fa-desktop"></i>


    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="LoadAjax('dnsdist-left','$page?dnsdist-status-left=yes');";
    $html[]="LoadAjaxSilent('dnsdist-center','$page?dnsdist-status-center=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
// ---------------------------------------------------------------------------
// dnsdist : pools et serveurs amont (zone centrale de l'onglet {status}).
// Le rendu et les appels a /dnsdist/status-center et /dnsdist/server/{verb} vivent dans
// ressources/class.dnsfw.views.inc (partage avec la fenetre Meta d'un noeud) : ici, le cablage.
// ---------------------------------------------------------------------------

function dnsdist_status_center(){
    $v=new dnsfw_views("local");
    $v->can_edit=unbound_settings_can_write();
    echo $v->status_center(CurrentPageName(),"dnsdist-center");
    return true;
}

// --- Confirmation Up / Down / Auto -----------------------------------------

function dnsdist_server_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!unbound_settings_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS2}");return; }
    $v=new dnsfw_views("local");
    echo $v->server_js(CurrentPageName(),intval($_GET["dnsdist-server-js"]),(string)($_GET["action"] ?? ""));
}

function dnsdist_server_popup(){
    $tpl=new template_admin();
    if(!unbound_settings_can_write()){ echo $tpl->div_error("{ERROR_NO_PRIVS2}");return; }
    $v=new dnsfw_views("local");
    echo $v->server_popup(CurrentPageName(),"dnsdist-center",intval($_GET["dnsdist-server-popup"]),(string)($_GET["action"] ?? ""));
}

function dnsdist_server_save(){
    $tpl=new template_admin();
    if(!unbound_settings_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $v=new dnsfw_views("local");
    $act=(string)($_POST["dnsdist-server-action"] ?? "");
    if(!$v->server_action_by_id(CurrentPageName(),"dnsdist-center",$act,intval($_POST["dnsdist-server-id"]))){
        echo $tpl->post_error($v->error);
        return false;
    }
    return true;
}

function dnsdist_status_left():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();

    $sock = new sockets();
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_error("ARTICA REST API ERROR||" . json_last_error_msg());
    } else {
        if (!$json->Status) {
            echo $tpl->div_error("ARTICA REST API ERROR||" .$json->Error);
        }
    }

    $bsini = new Bs_IniHandler(PROGRESS_DIR . "/dnsdist.status");
    $jsRestart = $tpl->framework_buildjs("/dnsfw/service/php/restart",
        "dnsdist.restart", "dnsdist.restart.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');",null,null,"AsDnsAdministrator"
    );

    $APP_DNSDIST_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    preg_match("#^([0-9]+)\.([0-9]+)#", $APP_DNSDIST_VERSION, $re);
    $major = $re[1];
    $minor = $re[2];
    if ($major == 1) {
        if ($minor < 6) {
            $help_url = "https://wiki.articatech.com/dns/load-balancer/upgrading";
            $js_help = "s_PopUpFull('$help_url','1024','900');";
            $tpl = new template_admin();
            $upgrade_required_software = $tpl->_ENGINE_parse_body("{upgrade_required_software}");
            $upgrade_required_software = str_replace("%s", $APP_DNSDIST_VERSION, $upgrade_required_software);
            $html[] = $tpl->div_error("{upgrade_required}||$upgrade_required_software)||$js_help");

        }
    }


    $html[] = $tpl->SERVICE_STATUS($bsini, "APP_DNSDIST", $jsRestart);


    $dnsdis = new dnsdist_status("127.0.0.1");
    if (!$dnsdis->generic_stats()) {
        $html[] = $tpl->div_error("DNS API ERROR||$dnsdis->error");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }


    $queries = $dnsdis->mainStats["queries"];
    $prc = $dnsdis->mainStats["cache_rate"];
    $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green", "fas fa-percent", "{$prc}%", "{cache_rate}"));
    $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green", "fas fa-satellite-dish", $tpl->FormatNumber($queries), "{queries}"));

    $html[] = "<script>";
    // Le centre (pools et serveurs) a son propre minuteur (ddsc_js) : le recharger ici a
    // chaque tour de 3 s faisait clignoter tout le tableau.
    $html[] = "Loadjs('$page?refresh-dnsdist-left-status-start=yes');";


    $html[] = "</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dnsdist_status_left_refresh_start():bool{
    $page=CurrentPageName();
    $html[] = "function RefresDNSDist(){";
    $html[] = "\tLoadjs('$page?refresh-dnsdist-left-status=yes');";
    $html[] = "}";
    $html[] = "if (typeof window.myDnsDistInterval == 'undefined') {";
    $html[] = "\twindow.myDnsDistInterval = null;";
    $html[] = "}";
    $html[] = "if (window.myDnsDistInterval === null) {";
    $html[] = "\t\twindow.myDnsDistInterval = setInterval(RefresDNSDist, 3000);";
    $html[] = "}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$html);
    return true;
}


function dnsdist_status_left_refresh():bool{
    $page=CurrentPageName();
    $f[]="if( document.getElementById('dnsdist-left') ){";
    $f[]="\tLoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes')";
    $f[]="}else{";
    $f[]="\tclearInterval(window.myDnsDistInterval);";
    $f[]="\twindow.myDnsDistInterval = null;";
    $f[]="}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}

// On a load-balancer serving DNS for the farm, the local DNS cache is replaced
// by HaCluster DNS (port 53). Showing "disabled" here made operators reinstall
// the cache, which cut DNS each time: show the service that really answers.
function unbound_farm_dns_status():bool{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"))<>1){return false;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"))<>1){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $button=$tpl->button_autnonome("{APP_HACLUSTER_DNS}","document.location.href='/hacluster-status';","fas fa-network-wired",null,0,"btn-primary");
    $html[]=$tpl->div_explain("{APP_HACLUSTER_DNS}||{dns_cache_replaced_by_hacluster_dns}||$button");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/dns/status"));
    if(!$json || !$json->Status){
        $err=($json && property_exists($json,"Error"))?$json->Error:"{error}";
        $html[]=$tpl->widget_rouge($err,"{APP_HACLUSTER_DNS}");
    }else{
        $jsrestart=$tpl->framework_buildjs("/hacluster/server/dns/restart",
            "haclusterdns.progress","haclusterdns.progress.txt","progress-unbound-restart",
            "UnbstReload();",null,null,"AsDnsAdministrator");
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini,"APP_HACLUSTER_DNS",$jsrestart);
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function syslog_comp():bool{
    $APP_SYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
    $tb=explode(".",$APP_SYSLOGD_VERSION);
    $MAJOR=intval($tb[0]);
    $MINOR=intval($tb[1]);
    if($MAJOR<8){return false;}
    if($MINOR<2208){return false;}
    return true;
}

function unbound_security_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{security}","$page?unbound-security-popup=yes");
    return true;
}
function unbound_dnssec_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{DNSSEC}","$page?unbound-dnssec-popup=yes");
    return true;
}

function unbound_dnssec_popup(){
    $UnBoundDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundDNSSEC"));
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(unbound_meta_key_locked("UnBoundDNSSEC")){
        $form[]=unbound_meta_field_ro($tpl,"UnBoundDNSSEC","{DNSSEC}",unbm_onoff($UnBoundDNSSEC));
        unbound_meta_lock_form($tpl);
    }else{
        $form[] = $tpl->field_checkbox("UnBoundDNSSEC",
            "{DNSSEC}", $UnBoundDNSSEC, false, "{DNSSEC_ABOUT}");
    }
    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=unbound_settings_notices_call();
    $jsafter[]="";

    echo $tpl->form_outside(null, $form,"{DNSSEC_ABOUT}","{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function unbound_dnssec_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!unbound_settings_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $meta=unbound_meta_refuse_keys(array("UnBoundDNSSEC"));
    if($meta!==""){ echo $meta;return false; }
    // Groupe security de l'API : verifie par unbound-checkconf, recharge, prouve.
    $split=unbound_settings::split(array("UnBoundDNSSEC"=>$_POST["UnBoundDNSSEC"]),array());
    $res=unbound_settings::apply(new unbound_api(),$split["groups"]);
    if(!$res["ok"]){ echo $tpl->post_error($res["error"]);return false; }
    unbound_settings::queue($res);
    return admin_tracks("Enable DNSSEC for DNS Cache={$_POST["UnBoundDNSSEC"]}");
}

function unbound_security_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $UnboundDisplayVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDisplayVersion"));
    $UnboundEnableQNAMEMini=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnableQNAMEMini"));
    // unbound (Configurator.go) et le schema de l'API lisent EnableUseCapsForID ;
    // DisableUseCapsForID n'est ecrit par personne : la case affichait toujours 1.
    $EnableUseCapsForID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUseCapsForID"));
    $EnableDNSRebindingAttacks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRebindingAttacks"));
    $UnBoundUnwantedReplyThreshold=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundUnwantedReplyThreshold"));
    if($UnBoundUnwantedReplyThreshold==0){$UnBoundUnwantedReplyThreshold="500000";}

    // Chaque bascule POSTe une cle : celles du schema passent par l'API, qui
    // peut refuser (409, 422). UnbSecResp() montre le refus et recharge les
    // cases sur les valeurs reelles ; le tableau est rafraichi dans tous les cas.
    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]=unbound_settings_notices_call();
    //$jsafter[]="dialogInstance2.close()";
    $jsafter[]="";
    $reload="if(t.length>3){LoadAjaxSilent('unbound-security-popup-div','$page?unbound-security-popup=yes&inner=yes');}";
    $respjs=unbound_settings::big_after_js("UnbSecResp",$reload.@implode(";",$jsafter));
    $jjafter="UnbSecResp(resp);";


    $metaBanner=unbound_meta_banner($tpl,array("UnboundDisplayVersion","UnboundAccessControl","UnboundEnableQNAMEMini","EnableUseCapsForID","EnableDNSRebindingAttacks","UnBoundUnwantedReplyThreshold"));
    if($metaBanner!==""){$html[]=$metaBanner;}
    $html[]=$tpl->BigCircleCheckbox("UnboundDisplayVersion|SaveSecurity:yes","{display_servername_version}","{dns_display_version}",$UnboundDisplayVersion,$jjafter,null,unbound_meta_key_locked("UnboundDisplayVersion"));


    $UnboundAccessControl=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundAccessControl"));


    $html[]=$tpl->BigCircleCheckbox("UnboundAccessControl|SaveSecurity:yes","{access_control}","{access_control_dns_explain}",$UnboundAccessControl,$jjafter,null,unbound_meta_key_locked("UnboundAccessControl"));

    $html[]=$tpl->BigCircleCheckbox("UnboundEnableQNAMEMini|SaveSecurity:yes","{UnboundEnableQNAMEMini}","{UnboundEnableQNAMEMini_explain}",$UnboundEnableQNAMEMini,$jjafter,null,unbound_meta_key_locked("UnboundEnableQNAMEMini"));

    $html[]=$tpl->BigCircleCheckbox("EnableUseCapsForID|SaveSecurity:yes","{EnableUseCapsForID}","{EnableUseCapsForID_explain}",$EnableUseCapsForID,$jjafter,null,unbound_meta_key_locked("EnableUseCapsForID"));

    $html[]=$tpl->BigCircleCheckbox("EnableDNSRebindingAttacks|SaveSecurity:yes","DNS Rebinding Prevention","{DNSRebindingPrevention_explain}",$EnableDNSRebindingAttacks,$jjafter,null,unbound_meta_key_locked("EnableDNSRebindingAttacks"));

    if(unbound_meta_key_locked("UnBoundUnwantedReplyThreshold")){
        $html[]=unbm_ro_line("{UnBoundUnwantedReplyThreshold}",$UnBoundUnwantedReplyThreshold);
    }else{
        $html[]=$tpl->BigFieldIntegerCheckbox("UnBoundUnwantedReplyThreshold|SaveSecurity:yes",
            "{UnBoundUnwantedReplyThreshold}","{UnBoundUnwantedReplyThreshold_explain}",$UnBoundUnwantedReplyThreshold,null,null,$jjafter);
    }

    // Le <script> est concatene APRES l'analyse (accolades lues comme {jetons}).
    $script="<script>$respjs</script>";
    if(isset($_GET["inner"])){
        echo $tpl->_ENGINE_parse_body($html).$script;
        return true;
    }
    echo "<div id='unbound-security-popup-div'>".$tpl->_ENGINE_parse_body($html)."</div>".$script;

    return true;

}
function unbound_doh_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{DOH_WEB_SERVICE}","$page?unbound-doh-popup=yes");
    return true;
}
function unbound_doh_popup():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $UnboundDOHEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHEnable"));
    $UnboundDOHPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHPort"));
    $UnboundDOHInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHInterface"));
    $UnboundDOHURI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHURI"));
    $UnboundDOHCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHCertificate"));
    $UnboundHttpMaxStreams=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundHttpMaxStreams"));
    $UnboundDOHBuffSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHBuffSize"));

    if($UnboundDOHBuffSize==0){$UnboundDOHBuffSize=4;}
    if($UnboundDOHInterface==null){$UnboundDOHInterface="eth0";}
    if($UnboundDOHPort==0){$UnboundDOHPort=443;}
    if($UnboundDOHURI==null){$UnboundDOHURI="/dns-query";}
    if($UnboundHttpMaxStreams==0){$UnboundHttpMaxStreams=250;}
    $form[] = $tpl->field_section("{DOH_WEB_SERVICE}", "{UNBOUND_DOH_EXPLAIN}");
    $form[] = unbound_meta_key_locked("UnboundDOHEnable")?unbound_meta_field_ro($tpl,"UnboundDOHEnable","{DOH_ENABLE}",unbm_onoff($UnboundDOHEnable)):$tpl->field_checkbox("UnboundDOHEnable", "{DOH_ENABLE}", $UnboundDOHEnable, "UnboundDOHInterface,UnboundDOHPort,UnboundDOHURI,UnboundHttpMaxStreams,UnboundDOHBuffSize");
    $form[]=unbound_meta_key_locked("UnboundDOHInterface")?unbound_meta_field_ro($tpl,"UnboundDOHInterface","{listen_interface}",$UnboundDOHInterface):$tpl->field_interfaces("UnboundDOHInterface", "{listen_interface}", $UnboundDOHInterface);
    $form[] = unbound_meta_key_locked("UnboundDOHPort")?unbound_meta_field_ro($tpl,"UnboundDOHPort","{listen_port}",$UnboundDOHPort):$tpl->field_numeric("UnboundDOHPort", "{listen_port}", $UnboundDOHPort);
    $form[] = $tpl->field_certificate("UnboundDOHCertificate", "{certificate}", $UnboundDOHCertificate);
    $form[]=unbound_meta_key_locked("UnboundDOHURI")?unbound_meta_field_ro($tpl,"UnboundDOHURI","{doh_uri}",$UnboundDOHURI):$tpl->field_text("UnboundDOHURI", "{doh_uri}", $UnboundDOHURI,true);
    $form[] = unbound_meta_key_locked("UnboundHttpMaxStreams")?unbound_meta_field_ro($tpl,"UnboundHttpMaxStreams","{max_connections}",$UnboundHttpMaxStreams):$tpl->field_numeric("UnboundHttpMaxStreams", "{max_connections}", $UnboundHttpMaxStreams);
    $form[] = unbound_meta_key_locked("UnboundDOHBuffSize")?unbound_meta_field_ro($tpl,"UnboundDOHBuffSize","{mem_size} (MB)",$UnboundDOHBuffSize):$tpl->field_numeric("UnboundDOHBuffSize", "{mem_size} (MB)", $UnboundDOHBuffSize);

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}

function unbound_tls_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{DNSOTLS}","$page?unbound-tls-popup=yes");
    return true;
}

function unbound_tls_popup():bool{
    $UnboundTLSEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSEnable"));
    $UnboundTLSCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSCertificate"));
    $UnboundTLSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSPort"));
    if($UnboundTLSPort==0){$UnboundTLSPort=853;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[] = $tpl->field_section("{DNSOTLS}", "{DNSOTLS_EXPLAIN}");
    $form[] = unbound_meta_key_locked("UnboundTLSEnable")?unbound_meta_field_ro($tpl,"UnboundTLSEnable","{DNSOTLS_ENABLE}",unbm_onoff($UnboundTLSEnable)):$tpl->field_checkbox("UnboundTLSEnable", "{DNSOTLS_ENABLE}", $UnboundTLSEnable, "UnboundTLSCertificate,UnboundTLSPort");
    $form[] = unbound_meta_key_locked("UnboundTLSPort")?unbound_meta_field_ro($tpl,"UnboundTLSPort","{listen_port}",$UnboundTLSPort):$tpl->field_numeric("UnboundTLSPort", "{listen_port}", $UnboundTLSPort);
    $form[] = unbound_meta_key_locked("UnboundTLSCertificate")?unbound_meta_field_ro($tpl,"UnboundTLSCertificate","{certificate}",$UnboundTLSCertificate):$tpl->field_certificate("UnboundTLSCertificate", "{certificate}", $UnboundTLSCertificate);
    if(unbm_all_locked(array("UnboundTLSEnable","UnboundTLSPort","UnboundTLSCertificate"))){unbound_meta_lock_form($tpl);}

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function unbound_cache_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{cache}","$page?unbound-cache-popup=yes");
    return true;
}
function unbound_cache_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TIMES[-1]="{not_used}";
    $TIMES[10]="10 {seconds}";
    $TIMES[20]="20 {seconds}";
    $TIMES[30]="30 {seconds}";
    $TIMES[60]="1 {minute}";
    $TIMES[300]="5 {minutes}";
    $TIMES[900]="15 {minutes}";
    $TIMES[1800]="30 {minutes}";
    $TIMES[3600]="1 {hour}";
    $TIMES[7200]="2 {hours}";
    $TIMES[10800]="3 {hours}";
    $TIMES[14400]="4 {hours}";
    $TIMES[28800]="8 {hours}";
    $TIMES[57600]="16 {hours}";
    $TIMES[86400]="1 {day}";
    $TIMES[172800]="2 {days}";
    $TIMES[604800]="7 {days}";
    $UnBoundCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheSize"));
    $UnBoundCacheMinTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
    $UnBoundCacheNEGTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheNEGTTL"));

    $form[]=unbound_meta_key_locked("UnBoundCacheSize")?unbound_meta_field_ro($tpl,"UnBoundCacheSize","{cache_size} (MB)",$UnBoundCacheSize):$tpl->field_numeric("UnBoundCacheSize", "{cache_size} (MB)", $UnBoundCacheSize);
    $form[]=unbound_meta_key_locked("UnBoundCacheMinTTL")?unbound_meta_field_ro($tpl,"UnBoundCacheMinTTL","{cache-ttl} (Min)",unbm_label($TIMES,$UnBoundCacheMinTTL)):$tpl->field_array_hash($TIMES, "UnBoundCacheMinTTL", "{cache-ttl} (Min)", $UnBoundCacheMinTTL);
    $form[]=unbound_meta_key_locked("UnBoundCacheMAXTTL")?unbound_meta_field_ro($tpl,"UnBoundCacheMAXTTL","{cache-ttl} (Max)",unbm_label($TIMES,$UnBoundCacheMAXTTL)):$tpl->field_array_hash($TIMES, "UnBoundCacheMAXTTL", "{cache-ttl} (Max)", $UnBoundCacheMAXTTL);
    $form[]=unbound_meta_key_locked("UnBoundCacheNEGTTL")?unbound_meta_field_ro($tpl,"UnBoundCacheNEGTTL","{negquery-cache-ttl}",unbm_label($TIMES,$UnBoundCacheNEGTTL)):$tpl->field_array_hash($TIMES, "UnBoundCacheNEGTTL", "{negquery-cache-ttl}", $UnBoundCacheNEGTTL);
    if(unbm_all_locked(array("UnBoundCacheSize","UnBoundCacheMinTTL","UnBoundCacheMAXTTL","UnBoundCacheNEGTTL"))){unbound_meta_lock_form($tpl);}

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function unbound_monitor_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{monitoring}","$page?unbound-monitor-popup=yes");
    return true;
}
function unbound_monitor_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));
    $EnableSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD"));
    $UnboundMaxLogsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundMaxLogsize"));
    if($UnboundMaxLogsize==0){$UnboundMaxLogsize=500;}

    $RefuseDNSRoot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefuseDNSRoot"));
    $form[] = unbound_meta_key_locked("RefuseDNSRoot")?unbound_meta_field_ro($tpl,"RefuseDNSRoot","{refuse_dns_root}",unbm_onoff($RefuseDNSRoot)):$tpl->field_checkbox("RefuseDNSRoot", "{refuse_dns_root}", $RefuseDNSRoot, false);


    if($EnableSNMPD==1){
        $form[] = $tpl->field_checkbox("EnableUnBoundSNMPD", "{enable_snmp}", $EnableUnBoundSNMPD, false);

    }
    if($EnableSNMPD!=1 && unbound_meta_key_locked("RefuseDNSRoot")){unbound_meta_lock_form($tpl);}

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}

function unbound_performance_js():bool {
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{performance}","$page?unbound-performance-form-popup=yes");
    return true;
}
function unbound_globaldoms_js():bool {
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{dns_resolution}","$page?unbound-globalDoms-popup=yes");
    return true;
}
function unbound_globaldoms_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableDNSRootInts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRootInts"));
    $DisableLocalReservation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableLocalReservation"));

    // Cles hors schema (/setinfo) : le handler peut tout de meme refuser
    // (privilege, esclave) ; UnbDomResp() le montre au lieu de l'avaler.
    $reload="if(t.length>3){LoadAjaxSilent('unbound-globaldoms-popup-div','$page?unbound-globalDoms-popup=yes&inner=yes');}";
    $respjs=unbound_settings::big_after_js("UnbDomResp",$reload."LoadAjax('unbound-table-start','$page?table=yes');");

    $form[] =$tpl->BigCircleCheckbox("EnableDNSRootInts","{EnableDNSRootInts}",
        "{EnableDNSRootInts_explain}",
        $EnableDNSRootInts,"UnbDomResp(resp);",null,unbound_meta_key_locked("EnableDNSRootInts"));

    $form[] =$tpl->BigCircleCheckbox("DisableLocalReservation","{DisableLocalReserv}",
        "{DisableLocalReservation_explain}",
        $DisableLocalReservation,"UnbDomResp(resp);",null,unbound_meta_key_locked("DisableLocalReservation"));

//    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
//    $jsafter[]="dialogInstance2.close()";
    $script="<script>$respjs</script>";
    if(isset($_GET["inner"])){
        echo $tpl->_ENGINE_parse_body($form).$script;
        return true;
    }
    echo "<div id='unbound-globaldoms-popup-div'>".$tpl->_ENGINE_parse_body($form)."</div>".$script;
    return true;
}

function unbound_performance_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UNBOUND_OUTGOING_RANGE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UNBOUND_OUTGOING_RANGE"));
    if($UNBOUND_OUTGOING_RANGE==0){
        $UNBOUND_OUTGOING_RANGE=4096;
    }
    if(unbound_meta_key_locked("UNBOUND_OUTGOING_RANGE")){
        $form[]=unbound_meta_field_ro($tpl,"UNBOUND_OUTGOING_RANGE","{outgoing_range}",$UNBOUND_OUTGOING_RANGE);
        unbound_meta_lock_form($tpl);
    }else{
        $form[] = $tpl->field_multiple_2("UNBOUND_OUTGOING_RANGE", "{outgoing_range}", $UNBOUND_OUTGOING_RANGE, "");
    }
    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function unbound_interfaces_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{interfaces}","$page?unbound-interface-popup=yes");
    return true;
}
function unbound_performance_redis_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{UBOUND_REDIS}","$page?unbound-performance-redis-popup=yes");
    return true;
}
function unbound_performance_redis_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UnboundRedisEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));
    $form[]=unbound_meta_key_locked("UnboundRedisEnabled")?unbound_meta_field_ro($tpl,"UnboundRedisEnabled","{UBOUND_REDIS}",unbm_onoff($UnboundRedisEnabled)):$tpl->field_checkbox("UnboundRedisEnabled","{UBOUND_REDIS}",$UnboundRedisEnabled,false,"{ubound_redis_explain}");

    $array_ttl[0]="{hourly}";
    $array_ttl[1]="{daily}";
    $array_ttl[2]="{weekly}";
    $array_ttl[3]="{monthly}";
    $UnBoundRedisDBTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundRedisDBTTL"));
    $form[] = unbound_meta_key_locked("UnBoundRedisDBTTL")?unbound_meta_field_ro($tpl,"UnBoundRedisDBTTL","{REMOVE_DATABASE}",unbm_label($array_ttl,$UnBoundRedisDBTTL)):$tpl->field_array_hash($array_ttl,"UnBoundRedisDBTTL","{REMOVE_DATABASE}",$UnBoundRedisDBTTL,false ,"{redis_unbound_explain}");
    if(unbm_all_locked(array("UnboundRedisEnabled","UnBoundRedisDBTTL"))){unbound_meta_lock_form($tpl);}

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,"{ubound_redis_explain}","{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;

}
function unbound_interfaces_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UnboundOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));

    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));

    $InComingInterfaces=@implode(",", $PowerDNSListenAddr);
    $ListenOnlyLoopBack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ListenOnlyLoopBack"));
    $UnboundAutomaticInterface=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundAutomaticInterface"));

    // InComingInterfaces est poste sous la cle PowerDNSListenAddr (save()).
    $form[]=unbound_meta_key_locked("PowerDNSListenAddr")?unbound_meta_field_ro($tpl,"PowerDNSListenAddr","{listen_interfaces}",$InComingInterfaces):$tpl->field_interfaces_choose("InComingInterfaces", "{listen_interfaces}", $InComingInterfaces);
    $form[]=unbound_meta_key_locked("ListenOnlyLoopBack")?unbound_meta_field_ro($tpl,"ListenOnlyLoopBack","{listen_only_loopback}",unbm_onoff($ListenOnlyLoopBack)):$tpl->field_checkbox("ListenOnlyLoopBack","{listen_only_loopback}",$ListenOnlyLoopBack);

    $form[] = unbound_meta_key_locked("UnboundAutomaticInterface")?unbound_meta_field_ro($tpl,"UnboundAutomaticInterface","{interface_automatic}",unbm_onoff($UnboundAutomaticInterface)):$tpl->field_checkbox("UnboundAutomaticInterface", "{interface_automatic}",
        $UnboundAutomaticInterface, false, "{interface_automatic_unbound}");
    $form[]=unbound_meta_key_locked("UnboundOutGoingInterface")?unbound_meta_field_ro($tpl,"UnboundOutGoingInterface","{outgoing_interface}",$UnboundOutGoingInterface):$tpl->field_interfaces("UnboundOutGoingInterface",
        "{outgoing_interface}", $UnboundOutGoingInterface);
    if(unbm_all_locked(array("PowerDNSListenAddr","ListenOnlyLoopBack","UnboundAutomaticInterface","UnboundOutGoingInterface"))){unbound_meta_lock_form($tpl);}

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    // Les cles du schema sont appliquees (et prouvees) par l'API : plus de
    // redemarrage systematique. api_notices_js() ne le relance que si une cle
    // hors schema qui en dependait a change (unbound_settings_restart_keys).
    $jsafter[]=unbound_settings_notices_call();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");

    return true;
}

function table_start():bool{
    $page=CurrentPageName();
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));

    if($EnableDNSDist==1){
        echo "<div id='dnsdist-table-start'></div><script>
    LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');
</script>";
        return true;
    }

    echo "<div id='unbound-table-start'></div><script>
    LoadAjax('unbound-table-start','$page?table=yes');
</script>";
    return true;
}

// ===== Status tab (maquette stitch_unbound_settings) =====
// table() rend la page une fois (bandeau vivant, graphique, cartes de reglages) ;
// ?unbound-status=yes ne rend que la partie vivante, rechargee toutes les 5 s.

function unbst_tr(string $tok,array $rep=array()):string{
    if(!isset($GLOBALS["UNBST_TPL"])){$GLOBALS["UNBST_TPL"]=new template_admin();}
    $txt=$GLOBALS["UNBST_TPL"]->_ENGINE_parse_body("{".$tok."}");
    return str_replace(array_keys($rep),array_values($rep),$txt);
}
function unbst_colors():array{
    $c=array("accent"=>"#10b981","soft"=>"rgba(16,185,129,.12)","dark"=>"#0d9488");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$c["accent"]=$a["accent"];}
        if(!empty($a["accent_soft"])){$c["soft"]=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$c["dark"]=$a["accent_dark"];}
    }
    return $c;
}
function unbst_compact($n):string{
    $n=floatval($n);
    if($n>=1000000000){return round($n/1000000000,1)." G";}
    if($n>=1000000){return round($n/1000000,1)." M";}
    if($n>=100000){return round($n/1000)." k";}
    if($n>=10000){return round($n/1000,1)." k";}
    return number_format($n,0,".",",");
}
// Duree lisible : 90 -> "1 min 30 s", 273600 -> "3 d 4 h". Deux unites au plus.
function unbst_duration($sec):string{
    $sec=intval($sec);
    if($sec<0){return "{not_used}";}
    if($sec<60){return "$sec s";}
    $u=array(array(86400,"d"),array(3600,"h"),array(60,"min"),array(1,"s"));
    $out=array();
    foreach($u as $x){
        if(count($out)==2){break;}
        $q=intdiv($sec,$x[0]);
        if($q>0){$out[]="$q {$x[1]}";$sec-=$q*$x[0];}
        elseif(count($out)>0){break;}
    }
    return implode(" ",$out);
}
function unbst_v(array $m,string $k):float{
    return isset($m["stats"][$k])?floatval($m["stats"][$k]):0.0;
}
function unbst_can_edit():bool{
    if(isset($GLOBALS["UNBST_CAN_EDIT"])){return $GLOBALS["UNBST_CAN_EDIT"];}
    $GLOBALS["UNBST_CAN_EDIT"]=unbound_settings_can_write() && !$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked();
    return $GLOBALS["UNBST_CAN_EDIT"];
}

// --- Donnees -----------------------------------------------------------------

// unbound-control stats_noreset : le demon renvoie les compteurs dans sa reponse
// ("stats"). Un articarest anterieur au 2026-11-04 ne fait qu'ecrire le fichier :
// on ne le relit alors qu'a defaut.
function unbst_stats(&$ok=false):array{
    $ok=false;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/stats"),true);
    if(!is_array($json) || empty($json["Status"])){return array();}
    $out=array();
    if(isset($json["stats"]) && is_array($json["stats"])){
        foreach($json["stats"] as $k=>$v){$out[(string)$k]=floatval($v);}
    }else{
        $raw=@file_get_contents(PROGRESS_DIR."/unbound.control.stats");
        if($raw===false){return array();}
        foreach(explode("\n",$raw) as $line){
            $eq=strpos($line,"=");
            if($eq===false || $eq==0){continue;}
            $out[trim(substr($line,0,$eq))]=floatval(trim(substr($line,$eq+1)));
        }
    }
    $ok=isset($out["total.num.queries"]);
    return $out;
}
// Echantillons de 5 min (10 min au-dela de 48 h) : incoming, outgoing, cached.
function unbst_history(string $range):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/metrics?range=$range"),true);
    if(!is_array($json) || empty($json["success"]) || !isset($json["data"]) || !is_array($json["data"])){return array();}
    return $json["data"];
}
// Regroupe les echantillons en $max paquets au plus (sommes), pour garder un graphique leger.
function unbst_downsample(array $samples,int $max):array{
    $n=count($samples);
    if($n<=$max){return $samples;}
    $step=intval(ceil($n/$max));
    $out=array();
    for($i=0;$i<$n;$i+=$step){
        $b=array("timestamp"=>intval($samples[$i]["timestamp"]),"incoming"=>0,"outgoing"=>0,"cached"=>0);
        for($j=$i;$j<min($n,$i+$step);$j++){
            $b["incoming"]+=intval($samples[$j]["incoming"]);
            $b["outgoing"]+=intval($samples[$j]["outgoing"]);
            $b["cached"]+=intval($samples[$j]["cached"]);
        }
        $out[]=$b;
    }
    return $out;
}
// Sparkline des requetes sur 24 h, gardee 60 s en session (le bandeau se recharge toutes les 5 s).
function unbst_spark():string{
    if(isset($_SESSION["UNBST_SPARK"]) && is_array($_SESSION["UNBST_SPARK"]) && (time()-intval($_SESSION["UNBST_SPARK"]["t"]))<60){
        return (string)$_SESSION["UNBST_SPARK"]["v"];
    }
    $vals=array();
    foreach(unbst_downsample(unbst_history("24h"),48) as $s){$vals[]=intval($s["incoming"]);}
    $v=count($vals)>1?implode(",",$vals):"";
    $_SESSION["UNBST_SPARK"]=array("t"=>time(),"v"=>$v);
    return $v;
}
function unbst_rpz():array{
    $count=0;
    $list=acls_api_get("/rpz/policies");
    if(is_array($list)){
        foreach($list as $p){ if(!empty($p["enabled"])){$count++;} }
    }
    return array("count"=>$count,"records"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZRealCount")));
}
function unbst_model():array{
    $m=array();
    $m["enabled"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"))==1;
    $m["stats"]=array();
    $m["stats_ok"]=false;
    if(!$m["enabled"]){return $m;}
    $ok=false;
    $m["stats"]=unbst_stats($ok);
    $m["stats_ok"]=$ok;
    $m["rpz"]=unbst_rpz();
    $m["spark"]=$ok?unbst_spark():"";
    return $m;
}

// --- Partie vivante (?unbound-status=yes) ----------------------------------------

// Colonne de gauche (?unbound-status-left=yes) : widgets de service traditionnels,
// le cache DNS puis chaque service compagnon actif.
function unbst_left():bool{
    $tpl=new template_admin();
    if(unbst_gi("UnboundEnabled")!=1){
        // Le cache DNS peut etre remplace par le DNS du cluster HA.
        if(unbound_farm_dns_status()){return true;}
        echo $tpl->SERVICE_STATUS_DISABLED("{APP_UNBOUND}");
        return true;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/status"));
    if(!$json || !isset($json->Info)){
        $err=($json && isset($json->Error))?$json->Error:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge($err,"{APP_UNBOUND}"));
        return true;
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo unbst_left_unbound($ini).unbst_left_companions($ini);
    return true;
}
function unbst_left_unbound($ini):string{
    $tpl=new template_admin();
    $js=$tpl->framework_buildjs("/unbound/restart","unbound.restart.progress",
        "unbound.restart.log","progress-unbound-restart","UnbstReload();",null,null,"AsDnsAdministrator");
    return $tpl->SERVICE_STATUS($ini,"APP_UNBOUND",$js);
}
// Un widget par service compagnon actif ; absent de l'etat du demon = rien.
function unbst_left_companions($ini):string{
    $tpl=new template_admin();
    $out="";
    if(unbst_gi("UnboundRedisEnabled")==1){
        $js=$tpl->framework_buildjs("/unbound/redis/restart","unbound-redis.progress","unbound-redis.log",
            "progress-unbound-restart","UnbstReload();",null,null,"AsDnsAdministrator");
        $out.=$tpl->SERVICE_STATUS($ini,"UBOUND_REDIS",$js);
    }
    if(unbst_gi("PDNSStatsEnabled")==1){
        $out.=$tpl->SERVICE_STATUS($ini,"APP_DSC",unbst_legacy_restart("PDNS_DSC"));
    }
    if(unbst_gi("EnableDNSCryptProxy")==1){
        $out.=$tpl->SERVICE_STATUS($ini,"APP_DNSCRYPT_PROXY",unbst_legacy_restart("DNSCRYPT"));
    }
    return $out;
}

// Colonne principale (?unbound-status=yes) : Statistics Appliance et indicateurs.
function unbst_live():bool{
    $tpl=new template_admin();
    $m=unbst_model();
    if(!$m["enabled"]){return true;}
    $html=array();
    $html[]="<div class='unbst-pills'>".unbst_pill_stats_appliance()."</div>";
    if(!$m["stats_ok"]){
        $html[]="<div class='ak-banner ak-warn unbst-unavail'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {unbst_stats_unavailable}</div>";
    }else{
        $html[]="<div class='ak-grid-4 unbst-kpis'>".unbst_kpi_cache($m).unbst_kpi_queries($m).unbst_kpi_recursion($m).unbst_kpi_rpz($m)."</div>";
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$html))
        ."<script>if(typeof UnbstPeity==='function'){UnbstPeity();}</script>";
    return true;
}
// La bande de qualite des reponses vit sous le graphique : elle a son propre hote.
function unbst_quality_live():bool{
    $tpl=new template_admin();
    $m=unbst_model();
    if(!$m["enabled"] || !$m["stats_ok"]){return true;}
    echo $tpl->_ENGINE_parse_body(unbst_quality($m));
    return true;
}

function unbst_legacy_restart(string $which):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsDnsAdministrator")){return "Loadjs('fw.progress.php?badprivs=yes')";}
    $ARRAY=array();
    if($which=="PDNS_DSC"){
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
        $ARRAY["CMD"]="pdns.php?restart-dsc=yes";
        $ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
    }else{
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
        $ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
        $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
    }
    $ARRAY["AFTER"]="UnbstReload();";
    $ARRAY["PRIV"]="AsDnsAdministrator";
    return "Loadjs('fw.progress.php?".fw_progress_args_b64(base64_encode(serialize($ARRAY)))."&mainid=progress-unbound-restart')";
}
function unbst_pill_stats_appliance():string{
    $page=CurrentPageName();
    if(!syslog_comp()){
        $cls="warn"; $word="{unbst_incompatible}";
        $link="<a href='#' class='unbst-link' onclick=\"Loadjs('fw.system.upgrade-software.php?product=APP_SYSLOGD');return false;\">{unbst_upgrade}</a>";
    }elseif(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsCom"))==1){
        $cls="ok";
        $addr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComAddress")).":".trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComPort"));
        $word="<span class='ak-mono'>".htmlspecialchars($addr)."</span>";
        $link="<a href='#' class='unbst-link' onclick=\"Loadjs('$page?statistics-appliance-remove=yes');return false;\">{unbst_uninstall}</a>";
    }else{
        $cls="off"; $word="{unbst_disabled}";
        $link="<a href='#' class='unbst-link' onclick=\"Loadjs('$page?statistics-appliance=yes');return false;\">{unbst_install}</a>";
    }
    return "<div class='unbst-pill-svc ".unbst_tone($cls)."'><span class='ak-dot' aria-hidden='true'></span>"
        ."<span class='unbst-pill-n'>{APP_STATISTICS_APPLIANCE}</span><span class='unbst-pill-w'>$word</span>$link</div>";
}

function unbst_kpi_cache(array $m):string{
    $hits=unbst_v($m,"total.num.cachehits");
    $miss=unbst_v($m,"total.num.cachemiss");
    $sum=$hits+$miss;
    if($sum<=0){
        return "<div class='ak-fig unbst-kpi'><div class='ak-fig-txt'><div class='ak-fig-l'>{cache_rate}</div>"
            ."<div class='unbst-kpi-v'>&mdash;</div><div class='ak-fig-s unbst-kpi-s'>{unbst_no_query}</div></div></div>";
    }
    $rate=round($hits/$sum*100,1);
    if($rate>=60){$cls="ok";$word="{unbst_good}";$ico="fa-check";$col="";}
    elseif($rate>=30){$cls="warn";$word="{unbst_low}";$ico="fa-minus";$col="#d97706";}
    else{$cls="err";$word="{unbst_very_low}";$ico="fa-triangle-exclamation";$col="#dc2626";}
    $sub=unbst_tr("unbst_hits_misses",array("%h"=>unbst_compact($hits),"%m"=>unbst_compact($miss)));
    return "<div class='ak-fig unbst-kpi'><div class='ak-fig-txt'><div class='ak-fig-l'>{cache_rate}</div>"
        ."<div class='unbst-kpi-v'>$rate&nbsp;% <span class='ak-pill ".unbst_tone($cls)."'><i class='fas $ico' aria-hidden='true'></i> $word</span></div>"
        ."<div class='ak-fig-s unbst-kpi-s'>$sub</div></div>"
        ."<div class='unbst-kpi-g'><span class='unbst-donut' data-c='$col'>".sprintf("%.1f",$rate)."/100</span></div></div>";
}
function unbst_kpi_queries(array $m):string{
    $q=unbst_compact(unbst_v($m,"total.num.queries"));
    $sub=unbst_tr("unbst_since_start",array("%s"=>unbst_duration(unbst_v($m,"time.up"))));
    $spark=$m["spark"]!=""?"<div class='unbst-kpi-g'><span class='unbst-spark'>{$m["spark"]}</span></div>":"";
    return "<div class='ak-fig unbst-kpi'><div class='ak-fig-txt'><div class='ak-fig-l'>{queries}</div>"
        ."<div class='unbst-kpi-v'>$q</div><div class='ak-fig-s unbst-kpi-s'>$sub</div></div>$spark</div>";
}
function unbst_kpi_recursion(array $m):string{
    if(unbst_v($m,"total.num.recursivereplies")<=0){
        return "<div class='ak-fig unbst-kpi'><div class='ak-fig-txt'><div class='ak-fig-l'>{unbst_recursion_time}</div>"
            ."<div class='unbst-kpi-v'>&mdash;</div><div class='ak-fig-s unbst-kpi-s'>{unbst_no_recursion}</div></div></div>";
    }
    $avg=round(unbst_v($m,"total.recursion.time.avg")*1000);
    $med=round(unbst_v($m,"total.recursion.time.median")*1000);
    $sub=unbst_tr("unbst_median",array("%s"=>"$med ms"));
    return "<div class='ak-fig unbst-kpi'><div class='ak-fig-txt'><div class='ak-fig-l'>{unbst_recursion_time}</div>"
        ."<div class='unbst-kpi-v'>$avg&nbsp;ms <span class='unbst-kpi-u'>{unbst_average}</span></div>"
        ."<div class='ak-fig-s unbst-kpi-s ak-mono'>$sub</div></div>"
        ."<div class='unbst-kpi-g'><span class='ak-ico'><i class='fas fa-clock-rotate-left' aria-hidden='true'></i></span></div></div>";
}
function unbst_kpi_rpz(array $m):string{
    $manage="<a href='#' class='unbst-link' onclick=\"LoadAjaxSilent('MainContent','fw.pdns.rpz.php');return false;\">{unbst_manage} <i class='fas fa-arrow-up-right-from-square' aria-hidden='true'></i></a>";
    $n=intval($m["rpz"]["count"]);
    if($n==0){
        return "<div class='ak-fig unbst-kpi unbst-kpi-off'><div class='ak-fig-txt'><div class='ak-fig-l'>{POLICIES_ZONES}</div>"
            ."<div class='unbst-kpi-v'><i class='fas fa-shield-halved' aria-hidden='true'></i> {unbst_disabled}</div><div class='ak-fig-s unbst-kpi-s'>$manage</div></div></div>";
    }
    $dbs=unbst_tr("unbst_rpz_databases",array("%s"=>(string)$n));
    $rec=intval($m["rpz"]["records"]);
    if($rec>50){
        $v=unbst_tr("unbst_rpz_records",array("%s"=>unbst_compact($rec)));
        $s=$dbs;
    }else{
        $v=$dbs; $s="";
    }
    return "<div class='ak-fig unbst-kpi'><div class='ak-fig-txt'><div class='ak-fig-l'>{POLICIES_ZONES}</div>"
        ."<div class='unbst-kpi-v'>$v</div><div class='ak-fig-s unbst-kpi-s'>$s</div></div>"
        ."<div class='unbst-kpi-g unbst-kpi-col'><span class='ak-ico ak-ok'><i class='fas fa-shield-halved' aria-hidden='true'></i></span>$manage</div></div>";
}

// Codes de reponse cumules depuis le demarrage (extended-statistics: yes).
function unbst_quality(array $m):string{
    $total=0.0;
    foreach($m["stats"] as $k=>$v){ if(strpos($k,"num.answer.rcode.")===0){$total+=$v;} }
    $items=array(
        array("NOERROR","fa-check","ok",unbst_v($m,"num.answer.rcode.NOERROR")),
        array("NXDOMAIN","fa-circle-question","neutral",unbst_v($m,"num.answer.rcode.NXDOMAIN")),
        array("SERVFAIL","fa-triangle-exclamation","neutral",unbst_v($m,"num.answer.rcode.SERVFAIL")),
        array("{unbst_dnssec_bogus}","fa-shield-xmark","neutral",unbst_v($m,"num.answer.bogus")),
    );
    $html=array();
    foreach($items as $i=>$x){
        $pct=$total>0?$x[3]/$total*100:0;
        $cls=$x[2];
        if($i==2 && $pct>1){$cls="err";}
        if($i==3 && $x[3]>0){$cls="err";}
        $p=$total>0?(($pct>0 && $pct<0.01)?"&lt;0.01%":round($pct,2)."%"):"&mdash;";
        $html[]="<div class='unbst-q unbst-$cls'><span class='unbst-q-ico'><i class='fas {$x[1]}' aria-hidden='true'></i></span>"
            ."<div><div class='unbst-q-n'>{$x[0]} <span class='unbst-q-p'>$p</span></div><div class='unbst-q-v ak-mono'>".number_format($x[3],0,".",",")."</div></div></div>";
    }
    $mem=unbst_v($m,"mem.cache.rrset")+unbst_v($m,"mem.cache.message");
    $html[]="<div class='unbst-q unbst-q-mem'><span class='unbst-q-ico'><i class='fas fa-memory' aria-hidden='true'></i></span>"
        ."<div><div class='unbst-q-n unbst-up'>{unbst_cache_memory}</div><div class='unbst-q-v'><b>".FormatBytes($mem/1024)."</b> <span class='unbst-q-p'>{unbst_rrset_msg}</span></div></div></div>";
    return "<div class='unbst-quality'>".implode("",$html)."</div>";
}

// --- Graphique d'activite (?unbst-chart=yes&range=) -------------------------------

function unbst_range():string{
    $r=isset($_GET["range"])?(string)$_GET["range"]:"24h";
    if(!in_array($r,array("1h","24h","7d","30d"),true)){$r="24h";}
    return $r;
}
function unbst_chart():bool{
    $tpl=new template_admin();
    $range=unbst_range();
    $samples=unbst_downsample(unbst_history($range),288);
    $c=unbst_colors();
    $seg=array();
    foreach(array("1h","24h","7d","30d") as $r){
        $on=$r==$range?" on":"";
        $pressed=$r==$range?"true":"false";
        $seg[]="<button type='button' class='ak-seg-b$on' aria-pressed='$pressed' onclick=\"UnbstChartLoad('$r')\">$r</button>";
    }
    $legend="<span class='unbst-lg'><span class='unbst-lg-line'></span>{unbst_serie_queries}</span>"
        ."<span class='unbst-lg'><span class='unbst-lg-area'></span>{unbst_serie_cached}</span>"
        ."<span class='unbst-lg'><span class='unbst-lg-grey'></span>{unbst_serie_recursive}</span>";
    $head="<div class='unbst-chart-h'><div class='unbst-chart-t'><h3>{unbst_last_$range}</h3>$legend</div>"
        ."<div class='ak-seg' role='group' aria-label='{unbst_period}'>".implode("",$seg)."</div></div>";
    if(count($samples)<2){
        $head=str_replace($legend,"",$head);
        echo $tpl->_ENGINE_parse_body($head."<div class='ak-empty-c unbst-chart-empty'><i class='fas fa-chart-line' aria-hidden='true'></i><span>{unbst_no_history}</span></div>");
        return true;
    }
    $fmt=($range=="1h"||$range=="24h")?"H:i":"d/m H:i";
    $L=array();$Q=array();$C=array();$R=array();
    foreach($samples as $s){
        $L[]=date($fmt,intval($s["timestamp"]));
        $Q[]=intval($s["incoming"]);
        $C[]=intval($s["cached"]);
        $R[]=intval($s["outgoing"]);
    }
    $labels=array(
        "q"=>unbst_tr("unbst_serie_queries"),
        "c"=>unbst_tr("unbst_serie_cached"),
        "r"=>unbst_tr("unbst_serie_recursive"),
    );
    $data=json_encode(array("l"=>$L,"q"=>$Q,"c"=>$C,"r"=>$R,"n"=>$labels,"acc"=>$c["accent"],"soft"=>$c["soft"]));
    echo $tpl->_ENGINE_parse_body($head."<div class='unbst-chart-b'><canvas id='unbst-canvas' aria-label='{unbst_chart_aria}' role='img'></canvas></div>");
    echo "<script>(function(d){
 if(typeof Chart==='undefined'){return;}
 var el=document.getElementById('unbst-canvas'); if(!el){return;}
 if(window.UnbstChart){try{window.UnbstChart.destroy();}catch(e){}}
 window.UnbstChart=new Chart(el,{type:'line',
  data:{labels:d.l,datasets:[
   {label:d.n.q,data:d.q,borderColor:d.acc,borderWidth:2,fill:false,tension:.3,pointRadius:0},
   {label:d.n.c,data:d.c,borderColor:d.acc,borderWidth:1,borderDash:[3,3],backgroundColor:d.soft,fill:'origin',tension:.3,pointRadius:0},
   {label:d.n.r,data:d.r,borderColor:'#9ca3af',borderWidth:1.5,fill:false,tension:.3,pointRadius:0}]},
  options:{animation:false,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
   plugins:{legend:{display:false}},
   scales:{x:{grid:{display:false},ticks:{maxTicksLimit:8,autoSkip:true,maxRotation:0,color:'#6b7280'}},
           y:{beginAtZero:true,grid:{color:'#f3f4f6'},ticks:{maxTicksLimit:4,color:'#6b7280'}}}}});
})($data);</script>";
    return true;
}

// --- Cartes de reglages (rendues une fois, rechargees apres chaque modale) -------------

// Etat de service -> ton du kit ak- (unk et off : neutre).
function unbst_tone(string $cls):string{
    $t=array("ok"=>"ak-ok","err"=>"ak-err","warn"=>"ak-warn");
    return isset($t[$cls])?$t[$cls]:"ak-off";
}
function unbst_onoff($v):string{
    if(intval($v)==1){return "<span class='ak-pill ak-ok'><i class='fas fa-check' aria-hidden='true'></i> {unbst_on}</span>";}
    return "<span class='ak-pill ak-off'><i class='fas fa-minus' aria-hidden='true'></i> {unbst_off}</span>";
}
function unbst_row(string $label,string $value,string $editJs="",string $editLabel=""):string{
    $pen="";
    if($editJs!="" && unbst_can_edit()){
        $aria=htmlspecialchars(unbst_tr("unbst_edit_aria",array("%s"=>$editLabel!=""?$editLabel:strip_tags(unbst_tr(trim($label,"{}"))))),ENT_COMPAT);
        $pen="<button type='button' class='ak-act' aria-label=\"$aria\" title=\"$aria\" onclick=\"$editJs\"><i class='fas fa-pen' aria-hidden='true'></i></button>";
    }
    return "<div class='unbst-row'><span class='unbst-row-l'>$label</span><span class='unbst-row-v'>$value$pen</span></div>";
}
function unbst_card(string $ico,string $titleTok,string $editJs,array $rows):string{
    $edit="";
    if($editJs!="" && unbst_can_edit()){
        $aria=htmlspecialchars(unbst_tr("unbst_edit_aria",array("%s"=>strip_tags(unbst_tr($titleTok)))),ENT_COMPAT);
        $edit="<button type='button' class='ak-link' aria-label=\"$aria\" onclick=\"$editJs\"><i class='fas fa-pen' aria-hidden='true'></i> {unbst_edit}</button>";
    }
    return "<section class='ak-card unbst-card'><header class='unbst-card-h'><span class='unbst-card-ico'><i class='fas $ico' aria-hidden='true'></i></span>"
        ."<h3>{".$titleTok."}</h3>$edit</header><div class='unbst-card-b'>".implode("",$rows)."</div></section>";
}
function unbst_gi(string $k):int{ return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($k)); }

function unbst_card_security():string{
    $page=CurrentPageName();
    $thr=unbst_gi("UnBoundUnwantedReplyThreshold");
    if($thr==0){$thr=500000;}
    $rows=array();
    $rows[]=unbst_row("{access_control}",unbst_onoff(unbst_gi("UnboundAccessControl")));
    $rows[]=unbst_row("{display_servername_version}",unbst_onoff(unbst_gi("UnboundDisplayVersion")));
    $rows[]=unbst_row("{UnboundEnableQNAMEMini}",unbst_onoff(unbst_gi("UnboundEnableQNAMEMini")));
    // unbound (Configurator.go) lit EnableUseCapsForID ; DisableUseCapsForID n'est ecrit par personne.
    $rows[]=unbst_row("{EnableUseCapsForID}",unbst_onoff(unbst_gi("EnableUseCapsForID")));
    $rows[]=unbst_row("DNS Rebinding Prevention",unbst_onoff(unbst_gi("EnableDNSRebindingAttacks")));
    $rows[]=unbst_row("{UnBoundUnwantedReplyThreshold}","<span class='ak-mono'>".number_format($thr,0,".",",")."</span>");
    if(unbst_gi("UnboundEDNS")==0){
        $edns=unbst_onoff(0);
    }else{
        // Liste saisie dans fw.dns.unbound.edns.php : echappee, les {tokens} n'y passent pas.
        $txt=implode(", ",explode("||",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNSNetworks")));
        $edns=strlen($txt)<2?"<b>{all}</b>":"<span class='ak-mono'>".htmlspecialchars($txt)."</span>";
    }
    $rows[]=unbst_row("EDNS",$edns,"Loadjs('fw.dns.unbound.edns.php')","EDNS");
    $rows[]=unbst_row("DNSSEC",unbst_onoff(unbst_gi("UnBoundDNSSEC")),"Loadjs('$page?unbound-dnssec-js=yes')","DNSSEC");
    return unbst_card("fa-shield","security","Loadjs('$page?unbound-security-js=yes')",$rows);
}
// Filtrage AAAA (spec 019) : un echec de lecture est montre tel quel, jamais pris pour "desactive".
function unbst_card_aaaa():string{
    $api=new unbound_api();
    $r=$api->get("/unbound/api/aaaa-filter");
    $aaaaPage="fw.dns.unbound.blockaaaa.php";
    $slave=unbst_gi("PowerDNSEnableClusterSlave")==1;
    $rows=array();
    if(!$r["ok"]){
        $rows[]="<div class='ak-banner ak-err unbst-note'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".htmlspecialchars(unbound_api::error_message($r))."</div>";
        return unbst_card("fa-filter","filter_aaaa","",$rows);
    }
    $d=is_array($r["data"])?$r["data"]:array();
    $mode=isset($d["mode"])?(string)$d["mode"]:"off";
    $total=intval(isset($d["domains_total"])?$d["domains_total"]:0);
    if($mode=="all"){$label="<b>{all_domains}</b>";}
    elseif($mode=="list"){$label="<b>{domains_list} ($total)</b>";}
    else{$label=unbst_onoff(0);}
    $link="<a href='#' class='unbst-link unbst-ml' onclick=\"LoadAjax('MainContent','$aaaaPage?main-page=yes');return false;\">{domains_list}</a>";
    $rows[]=unbst_row("{unbst_filtering_mode}",$label.$link);
    $skipped=(isset($d["skipped"]) && is_array($d["skipped"]))?$d["skipped"]:array();
    if(count($skipped)>0){
        $n=array();
        foreach($skipped as $s){$n[]=htmlspecialchars((string)$s);}
        $rows[]="<div class='ak-banner ak-warn unbst-note'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> <b>{filter_aaaa_skipped}:</b> <span class='ak-mono'>".implode(", ",$n)."</span></div>";
    }
    // Mode all : la zone transparent d'un nom local exempte tout son sous-arbre.
    $exempt=(isset($d["exempt"]) && is_array($d["exempt"]))?$d["exempt"]:array();
    if($mode=="all" && count($exempt)>0){
        $n=array();
        foreach(array_slice($exempt,0,20) as $s){$n[]=htmlspecialchars((string)$s);}
        if(count($exempt)>20){$n[]="&hellip; (".count($exempt).")";}
        $rows[]="<div class='ak-banner ak-info unbst-note'><i class='fas fa-circle-info' aria-hidden='true'></i> <b>{filter_aaaa_exempt}:</b> <span class='ak-mono'>".implode(", ",$n)."</span></div>";
    }
    return unbst_card("fa-filter","filter_aaaa",$slave?"":"Loadjs('$aaaaPage?mode-js=yes')",$rows);
}
function unbst_card_resolution():string{
    $page=CurrentPageName();
    $rows=array();
    $rows[]=unbst_row("{EnableDNSRootInts}",unbst_onoff(unbst_gi("EnableDNSRootInts")));
    $rows[]=unbst_row("{DisableLocalReserv}",unbst_onoff(unbst_gi("DisableLocalReservation")));
    return unbst_card("fa-earth-americas","dns_resolution","Loadjs('$page?unbound-globalDoms-js=yes')",$rows);
}
function unbst_card_performance():string{
    $page=CurrentPageName();
    $range=unbst_gi("UNBOUND_OUTGOING_RANGE");
    if($range==0){$range=4096;}
    $rows=array();
    $rows[]=unbst_row("{UBOUND_REDIS}",unbst_onoff(unbst_gi("UnboundRedisEnabled")),"Loadjs('$page?unbound-performance-redis-js=yes')");
    $rows[]=unbst_row("{outgoing_range}","<span class='ak-mono'>$range</span>");
    return unbst_card("fa-gauge-high","performance","Loadjs('$page?unbound-performance-form-js=yes&section=perf')",$rows);
}
function unbst_card_network():string{
    $page=CurrentPageName();
    $rows=array();
    if(unbst_gi("ListenOnlyLoopBack")==1){
        $rows[]=unbst_row("{listen_interface}","<span class='ak-mono'>127.0.0.1</span>");
        $rows[]="<div class='ak-banner ak-info unbst-note'><i class='fas fa-circle-info' aria-hidden='true'></i> {unbst_loopback_only}</div>";
        return unbst_card("fa-ethernet","unbst_network","Loadjs('$page?unbound-interface-js=yes')",$rows);
    }
    $in=trim(implode(", ",array_filter(explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr"))),"strlen")));
    $inHtml=$in==""?"<b>{all}</b>":"<span class='ak-mono'>".htmlspecialchars($in)."</span>";
    if(unbst_gi("UnboundAutomaticInterface")==1){$inHtml.=" <span class='ak-tag'>{interface_automatic}</span>";}
    $rows[]=unbst_row("{listen_interfaces}",$inHtml);
    $out=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));
    $rows[]=unbst_row("{outgoing_interface}",$out==""?"<b>{unbst_default}</b>":"<span class='ak-mono'>".htmlspecialchars($out)."</span>");
    if(unbst_gi("UnboundDOHEnable")==0){
        $doh=unbst_onoff(0);
    }else{
        $port=unbst_gi("UnboundDOHPort"); if($port==0){$port=443;}
        $uri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHURI")); if($uri==""){$uri="/dns-query";}
        $nic=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHInterface")); if($nic==""){$nic="eth0";}
        $doh="<span class='ak-mono'>".htmlspecialchars("$nic:$port$uri")."</span>";
    }
    $rows[]=unbst_row("{DOH_ENABLE}",$doh,"Loadjs('$page?unbound-doh-js=yes')");
    if(unbst_gi("UnboundTLSEnable")==0){
        $tls=unbst_onoff(0);
    }else{
        $port=unbst_gi("UnboundTLSPort"); if($port==0){$port=853;}
        $cert=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSCertificate"));
        $tls="<span class='ak-mono'>$port</span>".($cert!=""?" &middot; ".htmlspecialchars($cert):"");
    }
    $rows[]=unbst_row("{DNSOTLS_ENABLE}",$tls,"Loadjs('$page?unbound-tls-js=yes')");
    return unbst_card("fa-ethernet","unbst_network","Loadjs('$page?unbound-interface-js=yes')",$rows);
}
// La taille du cache d'Unbound est calculee par le demon d'apres la RAM :
// UnBoundCacheSize ne sert qu'a dnsdist, la carte ne l'affiche donc plus.
function unbst_card_cache():string{
    $page=CurrentPageName();
    $min=unbst_gi("UnBoundCacheMinTTL");
    $max=unbst_gi("UnBoundCacheMAXTTL"); if($max==0){$max=172800;}
    $neg=unbst_gi("UnBoundCacheNEGTTL"); if($neg==0){$neg=30;}
    $v="<span class='ak-mono'>".unbst_tr("unbst_ttl",array("%min"=>unbst_duration($min),"%max"=>unbst_duration($max),"%neg"=>unbst_duration($neg)))."</span>";
    $rows=array(unbst_row("{cache-ttl}",$v));
    return unbst_card("fa-database","cache","Loadjs('$page?unbound-cache-js=yes')",$rows);
}
function unbst_card_monitor():string{
    $page=CurrentPageName();
    if(unbst_gi("EnableSNMPD")==0){
        $v="<span class='ak-pill ak-off'><i class='fas fa-minus' aria-hidden='true'></i> {not_installed}</span>";
    }else{
        $v=unbst_onoff(unbst_gi("EnableUnBoundSNMPD"));
    }
    return unbst_card("fa-heart-pulse","monitor","Loadjs('$page?unbound-monitor-js=yes')",array(unbst_row("{enable_snmp}",$v)));
}
function unbst_meta_banner():string{
    if(!$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked()){return "";}
    return "<div class='ak-banner ak-info unbst-meta'><i class='fas fa-lock' aria-hidden='true'></i> <b>Artica Meta</b> &middot; {block_by_meta}</div>";
}
// --- Verrou Meta dnscache : aides d'affichage des popups ------------------------

/** Cles de reglages que cette page affiche et ecrit (bandeau du verrou Meta). */
function unbm_page_keys():array{
    return array("UnBoundCacheMinTTL","UnBoundCacheMAXTTL","UnBoundCacheNEGTTL","UnBoundCacheSize",
        "UnBoundDNSSEC","UnboundAccessControl","UnboundEnableQNAMEMini","EnableUseCapsForID","EnableDNSRebindingAttacks",
        "RefuseDNSRoot","UnBoundUnwantedReplyThreshold","UnboundDisplayVersion",
        "UnboundEDNS","UnboundEDNSNetworks","ClientSubnetAlwaysForward",
        "PowerDNSListenAddr","ListenOnlyLoopBack","UnboundAutomaticInterface",
        "UnboundTLSEnable","UnboundTLSCertificate","UnboundTLSPort","UnboundDOHEnable","UnboundDOHInterface","UnboundDOHPort",
        "UnboundHttpMaxStreams","UnboundDOHBuffSize","UnboundDOHURI","UnboundOutGoingInterface","UNBOUND_OUTGOING_RANGE",
        "UnboundRedisEnabled","UnBoundRedisDBTTL","EnableDNSRootInts","DisableLocalReservation",
        "UnboundStatsCom","UnboundStatsComAddress","UnboundStatsComPort","UnboundStatsComTCP","UnboundStatsComUseSSL",
        "UnboundStatsComCertificate","UnboundMaxLogsize");
}
function unbm_onoff($v):string{ return intval($v)==1?"{enabled}":"{disabled}"; }
function unbm_label(array $hash,$v):string{ return isset($hash[$v])?(string)$hash[$v]:(string)$v; }
function unbm_all_locked(array $keys):bool{ return count(unbound_meta_locked_keys($keys))==count($keys); }
/** Ligne en lecture seule d'une liste de cartes Big* (hors form_outside). */
function unbm_ro_line(string $label,$value):string{
    return "<div class='alert alert-warning' style='margin:10px 0'><i class='fa-solid fa-lock'></i>&nbsp;<strong>$label</strong> : "
        .unbound_meta_h($value)."<div>{unbound_meta_managed_locked}</div></div>";
}

function unbst_settings_grid():string{
    return "<div class='unbst-sec-h'><h2>{unbst_config_groups}</h2><p>{unbst_config_groups_explain}</p></div>"
        .unbst_meta_banner()
        .unbound_meta_banner(new template_admin(),unbm_page_keys())
        ."<div class='ak-grid-2 unbst-grid'>".unbst_card_security().unbst_card_aaaa().unbst_card_resolution()
        .unbst_card_performance().unbst_card_network().unbst_card_cache().unbst_card_monitor()."</div>";
}

// --- Page (?table=yes) ---------------------------------------------------------

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    if(!artica_setting_exists("UnBoundCacheMinTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMinTTL", 3600);}
    if(!artica_setting_exists("UnBoundCacheMAXTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMAXTTL", 172800);}
    if(!artica_setting_exists("UnBoundCacheNEGTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheNEGTTL", 3600);}
    unset($_SESSION["UNBST_SPARK"]);

    $enabled=unbst_gi("UnboundEnabled")==1;
    $html=array();
    $html[]="<div class='unbst ak'><div class='unbst-cols'>";
    $html[]="<div class='unbst-left' id='unbound-status-left'></div>";
    $html[]="<div class='unbst-main'>";
    $html[]="<div id='unbound-status'></div>";
    if($enabled){
        $html[]="<div class='ak-card unbst-chart' id='unbst-chart'></div>";
        $html[]="<div id='unbst-quality'></div>";
    }
    $html[]=unbst_settings_grid();
    $html[]="</div></div></div>";

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?config-file-js=yes')","fas fa-file-code","{config_file}");
    if(unbst_gi("EnableDebianAgent")==0){
        $jsReconfig=$tpl->framework_buildjs("/unbound/reconfigure","unbound.reconfigure.progress","unbound.reconfigure.log",
            "progress-unbound-restart","UnbstReload();",null,null,"AsDnsAdministrator");
        $topbuttons[]=array($jsReconfig,ico_save,"{unbst_reconfigure}");
    }
    $topbuttons[]=array("Loadjs('$page?unbound-uninstall=yes');",ico_trash,"{unbst_uninstall}");
    $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_UNBOUND} v$UnboundVersion";
    $TINY_ARRAY["ICO"]="fa fas fa-database";
    $TINY_ARRAY["EXPL"]="{didyouknow_unbound}";
    $TINY_ARRAY["URL"]="dns-cache";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo unbst_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).unbst_js($enabled)."<script>$tiny</script>";
    return true;
}

// Minuteur unique de 5 s (saute si une fenetre est ouverte ou l'onglet cache) ;
// le graphique se recharge toutes les 5 min, au rythme des echantillons.
function unbst_js(bool $enabled):string{
    $page=CurrentPageName();
    $c=unbst_colors();
    $js=<<<'JS'
<script>
(function(){
 window.UnbstPeity=function(){
  var go=function(){
   jQuery('#unbound-status .unbst-donut').each(function(){
    var e=jQuery(this), c=e.data('c')||'__ACC__';
    e.peity('donut',{fill:[c,'#e5e7eb'],radius:24,innerRadius:18});
   });
   jQuery('#unbound-status .unbst-spark').peity('line',{fill:'__SOFT__',stroke:'__ACC__',strokeWidth:2,width:120,height:36});
  };
  if(typeof jQuery==='undefined'){return;}
  if(jQuery.fn.peity){go();return;}
  jQuery.ajax({url:'js/jquery.peity.min.js',dataType:'script',cache:true}).done(go);
 };
 window.UnbstReload=function(){
  if(document.getElementById('unbound-status-left')){LoadAjaxSilent('unbound-status-left','__PAGE__?unbound-status-left=yes');}
  if(document.getElementById('unbound-status')){LoadAjaxSilent('unbound-status','__PAGE__?unbound-status=yes');}
  if(document.getElementById('unbst-quality')){LoadAjaxSilent('unbst-quality','__PAGE__?unbst-quality=yes');}
 };
 window.UnbstRange=window.UnbstRange||'24h';
 window.UnbstChartLoad=function(r){
  if(r){window.UnbstRange=r;}
  if(document.getElementById('unbst-chart')){LoadAjaxSilent('unbst-chart','__PAGE__?unbst-chart=yes&range='+encodeURIComponent(window.UnbstRange));}
 };
 if(window.UnbstTimer){clearInterval(window.UnbstTimer);}
 window.UnbstTick=0;
 window.UnbstTimer=setInterval(function(){
  if(!document.getElementById('unbound-status')){clearInterval(window.UnbstTimer);window.UnbstTimer=null;return;}
  if(document.hidden || document.querySelector('.modal.in, .modal.show, .sweet-alert.visible')){return;}
  UnbstReload();
  window.UnbstTick++;
  if(window.UnbstTick%60===0){UnbstChartLoad();}
 },5000);
 UnbstReload();
 if(__ENABLED__){UnbstChartLoad();}
})();
</script>
JS;
    return str_replace(array("__PAGE__","__ACC__","__SOFT__","__ENABLED__"),array($page,$c["accent"],$c["soft"],$enabled?"true":"false"),$js);
}

function unbst_style():string{
    if(isset($GLOBALS["UNBST_STYLE"])){return "";}
    $GLOBALS["UNBST_STYLE"]=true;
    $c=unbst_colors();
    $S=$c["soft"]; $D=$c["dark"];
    // Carte, tuiles, pastilles, points, boutons, segmente, bandeaux : kit ak- (angular.css.php).
    return "<style>
.unbst{color:#111827;font-size:13px;margin-top:12px}
.unbst-up{text-transform:uppercase;letter-spacing:.04em;font-size:12px;color:#6b7280}
.unbst a:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}
.unbst-cols{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start}
.unbst-left{flex:0 0 350px;max-width:100%;min-width:0}
.unbst-main{flex:1 1 600px;min-width:0}
.unbst-pills{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 16px}
.unbst-pill-svc{display:inline-flex;align-items:center;gap:8px;height:32px;padding:0 6px 0 12px;border-radius:16px;background:#fff;border:1px solid #e5e7eb;font-size:13px}
.unbst-pill-n{font-weight:500}.unbst-pill-w{color:#6b7280}
.unbst-pill-svc.ak-err .unbst-pill-w{color:#b91c1c;font-weight:600}
.unbst-pill-svc .unbst-link{padding-right:8px}
.unbst-link{color:$D;font-weight:600;text-decoration:none;white-space:nowrap}.unbst-link:hover{text-decoration:underline}
.unbst-unavail{margin-bottom:16px}
.unbst-kpis{margin-bottom:16px}
.unbst-kpi{min-height:120px}
.unbst-kpi-v{font-size:28px;font-weight:600;margin:0;white-space:nowrap;display:flex;align-items:center;gap:8px}
.unbst-kpi-off .unbst-kpi-v{font-size:20px;color:#6b7280}
.unbst-kpi-u{font-size:13px;font-weight:400;color:#6b7280}
.unbst-kpi-s{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.unbst-kpi-g{flex:none;display:flex;align-items:center}
.unbst-kpi-col{flex-direction:column;align-items:flex-end;justify-content:space-between;align-self:stretch}
.unbst-chart{padding:16px 20px;margin-bottom:16px;min-height:300px}
.unbst-chart-h{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:12px}
.unbst-chart-t{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.unbst-chart-t h3{font-size:15px;font-weight:600;margin:0}
.unbst-lg{display:inline-flex;align-items:center;gap:6px;color:#4b5563;font-size:13px}
.unbst-lg-line{width:14px;height:3px;border-radius:2px;background:var(--ak-accent)}
.unbst-lg-area{width:12px;height:12px;border-radius:2px;background:var(--ak-soft);border:1px dashed var(--ak-accent)}
.unbst-lg-grey{width:14px;height:2px;background:#9ca3af}
.unbst-chart-b{position:relative;height:240px}
.unbst-chart-empty{height:240px;padding:0;justify-content:center}
.unbst-quality{display:flex;align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 20px;margin-bottom:24px;min-height:72px}
.unbst-q{display:flex;align-items:center;gap:12px;flex:1;min-width:0}
.unbst-q-mem{flex:none;padding-left:20px;border-left:1px solid #e5e7eb}
.unbst-q-ico{width:32px;height:32px;border-radius:50%;background:#f3f4f6;color:#6b7280;display:flex;align-items:center;justify-content:center;font-size:13px;flex:none}
.unbst-q.unbst-ok .unbst-q-ico{background:#dcfce7;color:#16a34a}.unbst-q.unbst-err .unbst-q-ico{background:#fee2e2;color:#dc2626}
.unbst-q-n{font-size:13px;font-weight:600}.unbst-q-p{font-size:12px;font-weight:400;color:#6b7280}
.unbst-q.unbst-ok .unbst-q-p{color:#15803d}.unbst-q.unbst-err .unbst-q-n,.unbst-q.unbst-err .unbst-q-p{color:#b91c1c}
.unbst-q-v{font-size:13px;color:#374151;margin-top:2px}
.unbst-sec-h h2{font-size:18px;font-weight:600;margin:0 0 4px}
.unbst-sec-h p{color:#6b7280;margin:0 0 14px}
.unbst-card{padding:16px 20px}
.unbst-card-h{display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #e5e7eb}
.unbst-card-h h3{font-size:16px;font-weight:600;margin:0;flex:1}
.unbst-card-ico{width:30px;height:30px;border-radius:8px;background:$S;color:$D;display:flex;align-items:center;justify-content:center;font-size:13px;flex:none}
.unbst-row{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:9px 0;border-bottom:1px solid #f3f4f6;min-height:42px}
.unbst-row:last-child{border-bottom:0}
.unbst-row-l{color:#374151}
.unbst-row-v{display:flex;align-items:center;gap:6px;text-align:right;flex-wrap:wrap;justify-content:flex-end;min-width:0;word-break:break-word}
.unbst-ml{margin-left:8px}
.unbst-note{margin:10px 0 4px;font-size:13px;line-height:1.5}
.unbst-meta{margin:0 0 14px}
@media (max-width:1200px){.unbst-grid{grid-template-columns:1fr}.unbst-quality{flex-wrap:wrap;gap:12px}}
</style>";
}


/**
 * Cles hors schema de l'API que les popups de cette page ecrivent encore sur
 * /setinfo (liste fermee : toute autre cle postee est ignoree).
 */
function unbound_settings_legacy_keys():array{
    return array("UnboundDisplayVersion","UnBoundCacheSize","EnableDNSRootInts",
        "DisableLocalReservation","EnableUnBoundSNMPD","UnboundMaxLogsize","UnboundDOHCertificate","UnboundDOHURI");
}

/**
 * Cles hors schema que l'ancien popup appliquait par le redemarrage console
 * (fw.dns.unbound.restart.php, qui leve aussi la pause) : il n'est relance que
 * si l'une d'elles change.
 */
function unbound_settings_restart_keys():array{
    // Ronde 1 : aucune cle hors schema de ces popups n'agit sur unbound par un
    // redemarrage (UnBoundCacheSize : dnsdist ; SNMP : /snmpd/restart ; DoH
    // certificat/URI : non lus, le DoH sert le certificat DoT). Liste vide :
    // le redemarrage console n'est plus jamais rejoue apres un reglage.
    return array();
}

/**
 * Enregistrement des popups de reglages.
 *  - Cles du schema du lot 3 : PUT /unbound/api/settings/{groupe}, groupe par
 *    groupe, seulement ce qui change ; un refus (400, 409 dont l'esclave avec
 *    le maitre, 422 de unbound-checkconf, 500 avec retour arriere) s'affiche et
 *    rien d'autre n'est ecrit. Les avertissements s'affichent sans bloquer.
 *  - Cles hors schema : /setinfo et /unbound/reconfig comme avant, apres le
 *    controle de privilege et le refus d'un esclave.
 */
function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!unbound_settings_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }

    if(isset($_POST["SaveSecurity"])){
        unset($_POST["SaveSecurity"]);
    }

    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));

    $post=$_POST;
    if(isset($post["InComingInterfaces"])){
        // Liste a virgules du formulaire -> groupe listen, champ interfaces.
        $post["PowerDNSListenAddr"]=$post["InComingInterfaces"];
        unset($post["InComingInterfaces"]);
    }
    // Cles tenues par une politique Meta : refus avant toute ecriture (les cles hors
    // schema partent sur /setinfo, que le verrou du daemon ne couvre pas).
    $meta=unbound_meta_refuse_keys(array_keys($post));
    if($meta!==""){ echo $meta;return false; }
    $split=unbound_settings::split($post,unbound_settings_legacy_keys());
    $legacy=$split["legacy"];
    $api=new unbound_api();

    if(count($legacy)>0){
        $refusal=unbound_settings::legacy_refusal($api);
        if($refusal!==""){ echo $tpl->post_error($refusal);return false; }
    }

    $res=unbound_settings::apply($api,$split["groups"]);
    if(!$res["ok"]){ echo $tpl->post_error($res["error"]);return false; }
    unbound_settings::queue($res);
    if(count($res["applied"])>0){
        admin_tracks("DNS cache settings applied through the API: ".implode(", ",$res["applied"]));
    }

    if(count($legacy)==0){return true;}

    $restart=false;
    foreach(unbound_settings_restart_keys() as $k){
        if(isset($legacy[$k]) && (string)$legacy[$k]!==(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($k)){$restart=true;}
    }
    $e=unbound_settings::setinfo($legacy);
    if($e!==""){ echo $tpl->post_error($e);return false; }
    $track=array();
    foreach($legacy as $k=>$v){$track[]="$k: $v";}
    admin_tracks("DNS cache settings saved: ".implode(", ",$track));

    $sock=new sockets();
    $sock->REST_API("/unbound/reconfig");

    if(isset($legacy["EnableUnBoundSNMPD"])) {
        if ($legacy["EnableUnBoundSNMPD"] <> $EnableUnBoundSNMPD) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
        }
    }
    if($restart && session_status()===PHP_SESSION_ACTIVE){
        // Le redemarrage reste celui de la console (leve aussi la pause).
        $_SESSION["UNBOUND_SETTINGS_RESTART"]=1;
    }
    return true;
}
