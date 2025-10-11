<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["unbound-performance-redis-js"])){unbound_performance_redis_js();exit;}
if(isset($_GET["unbound-performance-redis-popup"])){unbound_performance_redis_popup();exit;}
if(isset($_POST["UnboundRedisEnabled"])){save();exit;}
if(isset($_GET["unbound-status-recursors"])){unbound_status_recursors();exit;}
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
if(isset($_POST["UnboundMaxLogsize"])){save();exit;}
if(isset($_POST["UnboundAutomaticInterface"])){save();exit;}
if(isset($_POST["UnBoundCacheSize"])){save();exit;}
if(isset($_POST["UnboundDOHEnable"])){save();exit;}
if(isset($_POST["UnboundTLSEnable"])){save();exit;}
if(isset($_POST["EnableDNSRootInts"])){save();exit;}
if(isset($_POST["UnboundDisplayVersion"])){save();exit;}
if(isset($_POST["UnboundStatsCom"])){SaveGeneric();exit;}
if(isset($_GET["unbound-status"])){unbound_status();exit;}
if(isset($_GET["unbound-uninstall"])){unbound_uninstall();exit;}
if(isset($_POST["uninstall-unbound"])){exit;}
if(isset($_GET["reload-status"])){reload_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_GET["ubound-top-status"])){unbound_top_status();exit;}
if(isset($_GET["dnsdist-status"])){dnsdist_status();exit;}
if(isset($_GET["dnsdist-status-left"])){dnsdist_status_left();exit;}
if(isset($_GET["dnsdist-status-center"])){dnsdist_status_center();exit;}
if(isset($_GET["dnsdist-start-server"])){dnsdist_start_server();exit;}
if(isset($_GET["dnsdist-stop-server"])){dnsdist_stop_server();exit;}
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
page();
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
    $js = $tpl->framework_buildjs("/unbound/service/uninstall",
        "unbound.install.progress",
        "unbound.install.log", "progress-unbound-restart", "document.location.href='/dns-servers'");

    echo $tpl->js_confirm_delete("{APP_UNBOUND}", "uninstall-unbound", "yes", $js);
}

function statistics_appliance_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog7("{APP_STATISTICS_APPLIANCE}","Loadjs('$page?statistics-appliance-popup=yes')");
}
function SaveGeneric(){
    $tpl=new template_admin();
    if(isset($_POST["UnboundStatsCom"])){
        if($_POST["UnboundStatsCom"]==1){
            admin_tracks("Enable send DNS telemetry to a Statisics Appliance {$_POST["UnboundStatsComAddress"]}");
        }
    }

    $tpl->SAVE_POSTs();
}
function statistics_appliance_remove():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("unbound.php?syslog=yes",
        "dns.syslog.progress","dns.syslog.log","progress-unbound-restart","Loadjs('$page?reload-status=yes')");

    return  $tpl->js_confirm_execute("{disconnect_stats_appliance}","stats-appliance-remove","yes",$js);

}
function statistics_appliance_remove_save(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundStatsCom",0);
    admin_tracks("Disable send DNS telemetry to a Statistic Appliance service.");
}
function reload_status(){
    $page=CurrentPageName();
    $f[]="if(document.getElementById('unbound-status')){";
    $f[]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
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


   $jsrestart=$tpl->framework_buildjs("unbound.php?syslog=yes",
        "dns.syslog.progress","dns.syslog.log","progress-unbound-restart","Loadjs('$page?reload-status=yes')");


    $form[] = $tpl->field_checkbox("UnboundStatsCom", "{enable_feature}", $UnboundStatsCom,"UnboundStatsComAddress,UnboundStatsComPort,UnboundStatsComTCP,UnboundStatsComUseSSL,UnboundStatsComCertificate");
    $form[] = $tpl->field_ipv4("UnboundStatsComAddress", "{remote_syslog_server}", $UnboundStatsComAddress);
    $form[] = $tpl->field_numeric("UnboundStatsComPort","{listen_port}",$UnboundStatsComPort);
    $form[] = $tpl->field_checkbox("UnboundStatsComTCP","{enable_tcpsockets}",$UnboundStatsComTCP);
    $form[] = $tpl->field_checkbox("UnboundStatsComUseSSL", "{useSSL}", $UnboundStatsComUseSSL);
    $form[] = $tpl->field_certificate("UnboundStatsComCertificate", "{certificate}", $UnboundStatsComCertificate);

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
    $div=$_GET["pool-down"];
    header("content-type: application/x-javascript");
    echo "$('#$div').addClass('text-danger');\n";

}

function hostadd_save(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
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
        if(!$json->Status){
            $tpl->post_error($json->Error);
            return false;
        }
    }


    if(!$hosts->Save(true)){
        $tpl->post_error($hosts->mysql_error);
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
function fw_database_status_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TRCLASS=null;
    $html[]="<table id='table-fw-dbstatus' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="</thead>";
    $html[]="<tbody>";


    $RPZ_LOCAL=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZ_LOCAL")));
    $items=$RPZ_LOCAL["items"];
    $items=$tpl->FormatNumber($items);
    $cate=$tpl->time_to_date($RPZ_LOCAL["compile_time"],true);
    $size=FormatBytes($RPZ_LOCAL["rpz_size"]/1024);
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr>";
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='vertical-align: middle' width=1% nowrap><strong>{last_update}:</strong></td>";
    $html[]="<td style='vertical-align: middle;padding-left:20px' width=100% nowrap>$cate</td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='vertical-align: middle' width=1% nowrap><strong>{records}:</strong></td>";
    $html[]="<td style='vertical-align: middle;padding-left:20px' width=100% nowrap>$items</td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='vertical-align: middle' width=1% nowrap><strong>{size}:</strong></td>";
    $html[]="<td style='vertical-align: middle;padding-left:20px' width=100% nowrap>$size</td>";
    $html[]="</tr>";

    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}


function dnsdist_stop_server(){
    $page=CurrentPageName();
    $id=intval($_GET["dnsdist-stop-server"]);
    writelogs("getServer('$id'):setDown()",__FUNCTION__,__FILE__,__LINE__);
    exec("/usr/bin/dnsdist -c -C /etc/dnsdist.conf -e \"getServer('$id'):setDown()\" 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#is not advised,#",$line)){continue;}
        writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
    }
    admin_tracks("Turn DNS server id $id to Down in DNS Firewall");
    echo "LoadAjaxSilent('dnsdist-center','$page?dnsdist-status-center=yes');";
}
function dnsdist_start_server(){
    $page=CurrentPageName();
    $id=intval($_GET["dnsdist-start-server"]);
    writelogs("getServer('$id'):setUp()",__FUNCTION__,__FILE__,__LINE__);
    exec("/usr/bin/dnsdist -c -C /etc/dnsdist.conf -e \"getServer('$id'):setUp()\" 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#is not advised,#",$line)){continue;}
        writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
    }
    admin_tracks("Turn DNS server id $id to Up in DNS Firewall");
    echo "LoadAjaxSilent('dnsdist-center','$page?dnsdist-status-center=yes');";

    //getServer("6")::setUp()
    //getServer("6"):setDown()

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
    $statusF="{status}";


    if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}
    if($EnableDNSDist==1){
        $array["{status}"]="$page?dnsdist-status=yes";
        $array["{statistics}"]="fw.dnsdist.statistics.php";
        $statusF="{general_parameters}";
        $array["{APP_DNS_COLLECTOR}"]="fw.dns.collector.php";
    }


    $array[$statusF]="$page?table-start=yes";
    $array["{networks_restrictions}"]="fw.pdns.restrictions.php?tinypage-unbound=yes";
    if($EnableDNSDist==1){
        unset($array["{networks_restrictions}"]);
    }

    if($UnboundEnabled==1) {
        $array["{APP_DNS_COLLECTOR}"]="fw.dns.collector.php";
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
        $function=$_GET["function"];
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
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dnsdist_parserules($gpid,$ruleid){
    if(isset($GLOBALS["$gpid-$ruleid"])){return $GLOBALS["$gpid-$ruleid"];}
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM dnsdist_rules WHERE ID='$ruleid'");
    $f[]=$tpl->td_href($ligne["rulename"],null,"Loadjs('fw.dns.dnsdist.rules.php?rule-id-js=$ruleid')");

    VERBOSE("$gpid,$ruleid ==> {$ligne["rulename"]}",__LINE__);

    if($gpid>0) {
        $ligne = $q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups  WHERE ID='$gpid'");
        $edit_js = "Loadjs('fw.rules.items.php?groupid=$gpid&js-after=&TableLink=&RefreshTable=&ProxyPac=0&firewall=0&RefreshFunction=&fastacls=')";
        $f[] = $tpl->td_href($ligne["GroupName"], null, $edit_js);
    }
    $results=@implode(" - ",$f);
    $GLOBALS["$gpid-$ruleid"]=$results;
    return $results;

}


function dnsdist_status_center(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $dnsdis=new dnsdist_status("127.0.0.1");
    if(!$dnsdis->localhost()){
        echo $tpl->div_error($dnsdis->error);
        return false;
    }

    $html[]="<table id='table-dnsdist-backends' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{queries}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{address}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{action}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{latency}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $colspan=7;
    $TRCLASS=null;
    $jsPool=array();
    $qZones=new lib_sqlite("/home/artica/SQLITE/dns.db");
    foreach ($dnsdis->pools_array as $pool=>$array){
        $STATS=$array["STATS"];
        $SRV=$array["SERVERS"];
        $hits=intval($STATS["hits"]);
        $miss=intval($STATS["miss"]);
        $cachesize=intval($STATS["cachesize"]);
        $entries=intval($STATS["entries"]);
        $cache_rate=null;
        $poolName=$pool;


        if(preg_match("#zone([0-9]+)#",$pool,$matches)){
            $id=$matches[1];
            $ligneZone=$qZones->mysqli_fetch_array("SELECT zone FROM pdns_fwzones WHERE ID='$id'");
            $poolName=$ligneZone["zone"];
        }



        $prc_cache="{no_cache}";
        $pie="<span class='pie'>0/0</span></td>";
        if($cachesize>0){
            $prc_cache=$entries/$cachesize;
            $prc_cache=round($prc_cache*100,2)."%";
            $pie="<span class='pie'>$entries/$cachesize</span></td>";
            $sum=$hits+$miss;
            if($sum>0) {
                $prc = $hits / $sum;
                $prc = round($prc * 100, 2);
                $cache_rate = "{cache_rate}: {$prc}%&nbsp;&nbsp;";
                $cache_rate = $tpl->td_href($cache_rate,null,"Loadjs('fw.dnsdist.caches.php?pool=$pool')");
            }
        }

        $html[]="<tr>";
        $html[]="<td colspan=$colspan style='vertical-align: middle'>";
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='vertical-align: middle' width=1% nowrap>";
        $html[]="$pie";
        $html[]="</td>";
        $html[]="<td style='vertical-align: middle;padding-left:20px' width=100% nowrap>";
        $html[]="<H2 id='pool-$pool' class=''>$poolName <small id='smallpool-$pool'>$cache_rate{cache}: $prc_cache</small></h2>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</table>";
        $html[]="</td>";
        $html[]="</tr>";
        $TRCLASS=null;
        foreach ($SRV as $index=>$serv_array){
            $class=null;
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $state_ico="<span class='label'>{inactive2}</span>";
            $address=$serv_array["address"];
            $name=$serv_array["name"];
            $latency=round($serv_array["latency"],3);
            $queries=$tpl->FormatNumber($serv_array["queries"]);
            $id=$serv_array["id"];
            $state=$serv_array["state"];
            $action=$tpl->icon_run("Loadjs('$page?dnsdist-start-server=$id')");
            if(strtolower($state)=="up"){
                $action=$tpl->icon_stop("Loadjs('$page?dnsdist-stop-server=$id')");
                $state_ico="<span class='label label-primary'>{active2}</span>";
            }
            if($queries==0){
                $state_ico="<span class='label'>{not_used}</span>";
            }

            if(strtolower($state)=="down"){
                $state_ico="<span class='label label-danger'>{down}</span>";
                $class="text-danger";
                $jsPool["$('#pool-$pool').addClass('text-danger');"]=true;
                $jsPool["$('#smallpool-$pool').addClass('text-danger');"]=true;

            }
            

            
            if(preg_match("#DNS([0-9]+)-([0-9]+)#",$name,$re)){
                VERBOSE("Name: $name ---> $re[1],$re[2]",__LINE__);
                $name=dnsdist_parserules($re[1],$re[2]);

            }
            VERBOSE("Name: $name",__LINE__);
            $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='acl-$index'>";
            $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
            $html[]="<td style='vertical-align:middle' class=\"$class\" style='width:1%' nowrap><span class='$class'>$state_ico</span></td>";
            $html[]="<td style='vertical-align:middle' class=\"$class\" style='width:1%' nowrap><span class='$class'>$queries</span></td>";
            $html[]="<td style='vertical-align:middle;width:1%' nowrap><span class='$class'>$address</span></td>";
            $html[]="<td style='vertical-align:middle;width:99%' nowrap><span class='$class'>$name</span></td>";
            $html[]="<td style='vertical-align:middle;width:99%' nowrap><span class='$class'>$action</span></td>";
            $html[]="<td style='vertical-align:middle' class=\"$class\" style='width:1%' nowrap><span class='$class'>{$latency}msec</span></td>";
            $html[]="</tr>";
        }
        $html[]="<tr style='vertical-align:middle' class='' id='acl-$index'>";
        $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:99%'>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:1%'>&nbsp;&nbsp;</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='$colspan'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(\"span.pie\").peity(\"pie\",{ fill: [\"#18a689\", \"#eeeeee\"], height:32,width:32 });";

    if(count($jsPool)>0){
        foreach ($jsPool as $js=>$none){
            $html[]=$js;
        }
    }

	$html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
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
        "LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');"
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
        $html[] = "<script>LoadAjaxSilent('dnsdist-center','$page?dnsdist-status-center=yes');</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }


    $queries = $dnsdis->mainStats["queries"];
    $prc = $dnsdis->mainStats["cache_rate"];
    $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green", "fas fa-percent", "{$prc}%", "{cache_rate}"));
    $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green", "fas fa-satellite-dish", $tpl->FormatNumber($queries), "{queries}"));


    $json=json_decode($sock->REST_API("/dns/collector/status"));
    $bsiniCollector=new Bs_IniHandler();
    $bsiniCollector->loadString($json->Info);
    $jsRestartCollector=$tpl->framework_buildjs("/dns/collector/restart","dns-collector.progress",
        "dns-collector.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');");

    $html[]=$tpl->SERVICE_STATUS($bsiniCollector, "APP_DNS_COLLECTOR",$jsRestartCollector);

    $html[] = "<script>";
    $html[] = "LoadAjaxSilent('dnsdist-center','$page?dnsdist-status-center=yes');";
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

function unbound_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $UnboundRedisEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $THISEC=true;
    if($UnboundEnabled==0){
        if($EnableDNSDist==0){
            $THISEC=false;
        }
    }

    if(!$THISEC){
        echo $tpl->widget_grey("{backup_disabled}","{disabled}");
        return true;
    }


    $tpl=new template_admin();
    if($EnableDNSDist==1){
        $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
        preg_match("#^([0-9]+)\.([0-9]+)#",$APP_DNSDIST_VERSION,$re);
        $major=$re[1];
        $minor=$re[2];
        if($major==1){
            if($minor<6){
                $help_url="https://wiki.articatech.com/dns/load-balancer/upgrading";
                $js_help="s_PopUpFull('$help_url','1024','900');";
                $button=$tpl->button_autnonome("{UFDBGUARD_TITLE_2}","$js_help","fad fa-question",null,0,"btn-warning");
                $upgrade_required_software=$tpl->_ENGINE_parse_body("{upgrade_required_software}");
                $upgrade_required_software=str_replace("%s",$APP_DNSDIST_VERSION,$upgrade_required_software);
                echo $tpl->div_warning("{upgrade_required}||$upgrade_required_software||$button");
            }
        }



        $tpl=new template_admin();
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
        $bsini=new Bs_IniHandler(PROGRESS_DIR."/dnsdist.status");


        $jsRestart = $tpl->framework_buildjs("/dnsfw/service/php/restart",
            "dnsdist.restart", "dnsdist.restart.log",
            "unbound-dedicated-progress",
            "LoadAjaxSilent('unbound-status','$page?unbound-status=yes');"
        );


        $final[]=$tpl->SERVICE_STATUS($bsini, "APP_DNSDIST",$jsRestart);
        echo $tpl->_ENGINE_parse_body($final);
        return true;
    }


    $sock=new sockets();
    $json=json_decode($sock->REST_API("/unbound/status"));
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);


    $EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
    $PDNSStatsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));

    $jsRestart=$tpl->framework_buildjs("/unbound/restart","unbound.restart.progress",
        "unbound.restart.log","progress-unbound-restart","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");

    $json=json_decode($sock->REST_API("/dns/collector/status"));
    $bsiniCollector=new Bs_IniHandler();
    $bsiniCollector->loadString($json->Info);
    $jsRestartCollector=$tpl->framework_buildjs("/dns/collector/restart","dns-collector.progress",
        "dns-collector.log",
        "progress-unbound-restart","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
    $ARRAY["CMD"]="pdns.php?restart-dsc=yes";
    $ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestartDSC="Loadjs('fw.progress.php?content=$prgress&mainid=progress-unbound-restart')";


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
    $ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
    $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestartDNSCrypt="Loadjs('fw.progress.php?content=$prgress&mainid=progress-unbound-restart')";

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
    $ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
    $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestartDNSCrypt="Loadjs('fw.progress.php?content=$prgress&mainid=progress-unbound-restart')";


    $jsRedisRestart=$tpl->framework_buildjs("/unbound/redis/restart",
        "unbound-redis.progress",
        "unbound-redis.log",
        "progress-unbound-restart","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");


    $jsReconfig=$tpl->framework_buildjs("/unbound/reconfigure",
        "unbound.reconfigure.progress",
        "unbound.reconfigure.log",
        "progress-unbound-restart","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");



    $topbuttons[] = array("Loadjs('$page?config-file-js=yes')", "fas fa-file-code", "{config_file}");


    $topbuttons[] = array($jsReconfig,ico_save, "{reconfigure}");
    $topbuttons[] = array("Loadjs('$page?unbound-uninstall=yes');",ico_trash
    , "{uninstall}");

    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_UNBOUND",$jsRestart);

    if($UnboundRedisEnabled==1){
        $final[]=$tpl->SERVICE_STATUS($bsini, "UBOUND_REDIS",$jsRedisRestart);
    }

    $final[]=$tpl->SERVICE_STATUS($bsiniCollector, "APP_DNS_COLLECTOR",$jsRestartCollector);

    $final[]=stats_appliance_status();

    if($PDNSStatsEnabled==1) {
        $final[] = $tpl->SERVICE_STATUS($bsini, "APP_DSC", $jsRestartDSC);
    }


    if($EnableDNSCryptProxy==1) {
        $final[] = $tpl->SERVICE_STATUS($bsini, "APP_DNSCRYPT_PROXY", $jsRestartDNSCrypt);
    }



    $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
    $Title="{APP_UNBOUND} v{$UnboundVersion}";
    $TINY_ARRAY["TITLE"]=$Title;
    $TINY_ARRAY["ICO"]="fa fas fa-database";
    $TINY_ARRAY["EXPL"]="{didyouknow_unbound}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $final[]="<script>";
    $final[]=$jstiny;
    $final[]="</script>";

    echo $tpl->_ENGINE_parse_body($final);

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

function stats_appliance_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!syslog_comp()){
        $APP_SYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
        $btn[0]["js"] = "Loadjs('fw.system.upgrade-software.php?product=APP_SYSLOGD');";
        $btn[0]["name"] = "{upgrade}";
        $btn[0]["icon"] = "far fa-shield-check";

        $html=$tpl->widget_grey("{APP_STATISTICS_APPLIANCE}","$APP_SYSLOGD_VERSION {incompatible_system}",$btn);
        return $html;

    }


    $UnboundStatsCom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsCom"));

    if($UnboundStatsCom==1){
        $btn[0]["js"] = "Loadjs('$page?statistics-appliance-remove=yes');";
        $btn[0]["name"] = "{uninstall}";
        $btn[0]["icon"] = "far fa-shield-check";

        $UnboundStatsComAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComAddress"));
        $UnboundStatsComPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComPort"));
        $html=$tpl->widget_vert("{APP_STATISTICS_APPLIANCE}","$UnboundStatsComAddress:$UnboundStatsComPort",$btn);
    }else{
        $btn[0]["js"] = "Loadjs('$page?statistics-appliance=yes');";
        $btn[0]["name"] = "{install}";
        $btn[0]["icon"] = "far fa-shield-check";
        $html=$tpl->widget_grey("{APP_STATISTICS_APPLIANCE}","{disabled}",$btn);
    }
    return $html;

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
    $form[] = $tpl->field_checkbox("UnBoundDNSSEC",
        "{DNSSEC}", $UnBoundDNSSEC, false, "{DNSSEC_ABOUT}");
    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="";

    echo $tpl->form_outside(null, $form,"{DNSSEC_ABOUT}","{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function unbound_dnssec_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfig");
    return admin_tracks("Enable DNSSEC for DNS Cache={$_POST["UnBoundDNSSEC"]}");
}

function unbound_security_popup(){
    $EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
    $page=CurrentPageName();
    $tpl=new template_admin();

    $UnboundDisplayVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDisplayVersion"));
    $UnboundEnableQNAMEMini=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnableQNAMEMini"));
    $DisableUseCapsForID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableUseCapsForID"));
    if($DisableUseCapsForID==0){$EnableUseCapsForID=1;}else{$EnableUseCapsForID=0;}
    $EnableDNSRebindingAttacks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRebindingAttacks"));
    $UnBoundUnwantedReplyThreshold=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundUnwantedReplyThreshold"));
    if($UnBoundUnwantedReplyThreshold==0){$UnBoundUnwantedReplyThreshold="500000";}


    $form[] = $tpl->field_checkbox("UnboundDisplayVersion", "{display_servername_version}", $UnboundDisplayVersion, false, null);


    $form[] = $tpl->field_checkbox("UnboundEnableQNAMEMini", "{UnboundEnableQNAMEMini}", $UnboundEnableQNAMEMini);
    $form[] = $tpl->field_checkbox("EnableUseCapsForID", "{EnableUseCapsForID}", $EnableUseCapsForID,false,"{EnableUseCapsForID_explain}");


    $form[] = $tpl->field_checkbox("EnableDNSRebindingAttacks",
        "DNS Rebinding Prevention", $EnableDNSRebindingAttacks, false);



    $form[] = $tpl->field_numeric("UnBoundUnwantedReplyThreshold",
        "{UnBoundUnwantedReplyThreshold}", $UnBoundUnwantedReplyThreshold,  "{UnBoundUnwantedReplyThreshold_explain}");

    $form[] = $tpl->field_checkbox("EnableUnboundBlackLists", "{activate_dns_blacklists}", $EnableUnboundBlackLists, false, "{activate_dns_blacklists_explain}");

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="";

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
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
    $form[] = $tpl->field_checkbox("UnboundDOHEnable", "{DOH_ENABLE}", $UnboundDOHEnable, "UnboundDOHInterface,UnboundDOHPort,UnboundDOHURI,UnboundHttpMaxStreams,UnboundDOHBuffSize");
    $form[]=$tpl->field_interfaces("UnboundDOHInterface", "{listen_interface}", $UnboundDOHInterface);
    $form[] = $tpl->field_numeric("UnboundDOHPort", "{listen_port}", $UnboundDOHPort);
    $form[] = $tpl->field_certificate("UnboundDOHCertificate", "{certificate}", $UnboundDOHCertificate);
    $form[]=$tpl->field_text("UnboundDOHURI", "{doh_uri}", $UnboundDOHURI,true);
    $form[] = $tpl->field_numeric("UnboundHttpMaxStreams", "{max_connections}", $UnboundHttpMaxStreams);
    $form[] = $tpl->field_numeric("UnboundDOHBuffSize", "{mem_size} (MB)", $UnboundDOHBuffSize);

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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
    $form[] = $tpl->field_checkbox("UnboundTLSEnable", "{DNSOTLS_ENABLE}", $UnboundTLSEnable, "UnboundTLSCertificate,UnboundTLSPort");
    $form[] = $tpl->field_numeric("UnboundTLSPort", "{listen_port}", $UnboundTLSPort);
    $form[] = $tpl->field_certificate("UnboundTLSCertificate", "{certificate}", $UnboundTLSCertificate);

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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

    $form[]=$tpl->field_numeric("UnBoundCacheSize", "{cache_size} (MB)", $UnBoundCacheSize);
    $form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheMinTTL", "{cache-ttl} (Min)", $UnBoundCacheMinTTL);
    $form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheMAXTTL", "{cache-ttl} (Max)", $UnBoundCacheMAXTTL);
    $form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheNEGTTL", "{negquery-cache-ttl}", $UnBoundCacheNEGTTL);

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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
    $form[] = $tpl->field_checkbox("RefuseDNSRoot", "{refuse_dns_root}", $RefuseDNSRoot, false);


    if($EnableSNMPD==1){
        $form[] = $tpl->field_checkbox("EnableUnBoundSNMPD", "{enable_snmp}", $EnableUnBoundSNMPD, false);

    }

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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

    $form[] = $tpl->field_checkbox("EnableDNSRootInts", "{EnableDNSRootInts}", $EnableDNSRootInts, false, "{EnableDNSRootInts_explain}");
    $form[] = $tpl->field_checkbox("DisableLocalReservation", "{DisableLocalReserv}", $DisableLocalReservation, false, "");


    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}

function unbound_performance_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UNBOUND_OUTGOING_RANGE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UNBOUND_OUTGOING_RANGE"));
    if($UNBOUND_OUTGOING_RANGE==0){
        $UNBOUND_OUTGOING_RANGE=4096;
    }
    $form[] = $tpl->field_multiple_2("UNBOUND_OUTGOING_RANGE", "{outgoing_range}", $UNBOUND_OUTGOING_RANGE, "");
    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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
    $form[]=$tpl->field_checkbox("UnboundRedisEnabled","{UBOUND_REDIS}",$UnboundRedisEnabled,false,"{ubound_redis_explain}");

    $array_ttl[0]="{hourly}";
    $array_ttl[1]="{daily}";
    $array_ttl[2]="{weekly}";
    $array_ttl[3]="{monthly}";
    $UnBoundRedisDBTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundRedisDBTTL"));
    $form[] = $tpl->field_array_hash($array_ttl,"UnBoundRedisDBTTL","{REMOVE_DATABASE}",$UnBoundRedisDBTTL,false ,"{redis_unbound_explain}");

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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

    $form[]=$tpl->field_interfaces_choose("InComingInterfaces", "{listen_interfaces}", $InComingInterfaces);
    $form[]=$tpl->field_checkbox("ListenOnlyLoopBack","{listen_only_loopback}",$ListenOnlyLoopBack);

    $form[] = $tpl->field_checkbox("UnboundAutomaticInterface", "{interface_automatic}", $UnboundAutomaticInterface, false, "{interface_automatic_unbound}");
    $form[]=$tpl->field_interfaces("UnboundOutGoingInterface",
        "{outgoing_interface}", $UnboundOutGoingInterface);

    $jsafter[]="LoadAjax('unbound-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('fw.dns.unbound.restart.php')";

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

function table():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $EnableDNSRootInts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRootInts"));
    $RefuseDNSRoot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefuseDNSRoot"));

    $UnboundDisplayVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDisplayVersion"));
    $DNSDistSetServerPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistSetServerPolicy"));
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));
    if($DNSDistSetServerPolicy==null){$DNSDistSetServerPolicy="leastOutstanding";}
    $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");

    $UnboundMaxLogsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundMaxLogsize"));
    if($UnboundMaxLogsize==0){$UnboundMaxLogsize=500;}
    $UnboundAutomaticInterface=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundAutomaticInterface"));

    $DisableUseCapsForID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableUseCapsForID"));
    if($DisableUseCapsForID==0){$EnableUseCapsForID=1;}else{$EnableUseCapsForID=0;}

    $UnboundRedisEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));


    if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMinTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMinTTL", 3600);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMAXTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMAXTTL", 172800);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheNEGTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheNEGTTL", 3600);}

    $DOH=false;
    if(preg_match("#^([0-9]+)\.([0-9]+)#",$UnboundVersion,$re)){
        $MAJOR=intval($re[1]);
        $MINOR=intval($re[2]);
        if($MAJOR>0){
            if($MINOR>11){
                $DOH=true;
            }
        }
    }


    $ipclass=new IP();
    $UnBoundCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheSize"));
    $UnBoundCacheMinTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
    $UnBoundCacheNEGTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheNEGTTL"));
    $UnboundOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));
    $EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
    $ListenOnlyLoopBack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ListenOnlyLoopBack"));
    $UnboundEnableQNAMEMini=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnableQNAMEMini"));
    $UnBoundDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundDNSSEC"));
    $UnBoundUnwantedReplyThreshold=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundUnwantedReplyThreshold"));
    if($UnBoundUnwantedReplyThreshold==0){$UnBoundUnwantedReplyThreshold="500000";}

    $forcesafesearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoogleSafeSearchAddress"));
    if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}
    if($forcesafesearch==null){$forcesafesearch=$GLOBALS["CLASS_SOCKETS"]->gethostbyname("forcesafesearch.google.com");}
    if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}

    $UnboundTLSEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSEnable"));
    $UnboundTLSCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSCertificate"));
    $UnboundTLSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSPort"));
    if($UnboundTLSPort==0){$UnboundTLSPort=853;}

    if($UnBoundCacheMinTTL==0){$UnBoundCacheMinTTL=3600;}
    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}
    if($UnBoundCacheNEGTTL==0){$UnBoundCacheNEGTTL=3600;}



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

    $algo["chashed"]="{strict-hashed-ip}";
    $algo["roundrobin"]="{round-robin}";
    $algo["leastOutstanding"]="{leastconn}";
    $algo["firstAvailable"]="{firstAvailable}";

    if($UnBoundCacheSize==0){$UnBoundCacheSize=100;}
    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
    $EnableSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD"));
    $EnableDNSRebindingAttacks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRebindingAttacks"));
    $DisableLocalReservation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableLocalReservation"));



    $InComingInterfaces=@implode(",", $PowerDNSListenAddr);

    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $UNBOUND_OUTGOING_RANGE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UNBOUND_OUTGOING_RANGE"));
    if($UNBOUND_OUTGOING_RANGE==0){
        $UNBOUND_OUTGOING_RANGE=4096;
    }


    $UnboundEDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNS"));

    $tpl->table_form_field_js("Loadjs('$page?unbound-security-js=yes')","AsDnsAdministrator");
    $tpl->table_form_section("{security}");
    $tpl->table_form_field_bool("{display_servername_version}",$UnboundDisplayVersion,ico_shield);
    $tpl->table_form_field_bool("{UnboundEnableQNAMEMini}",$UnboundEnableQNAMEMini,ico_shield);
    $tpl->table_form_field_bool("{EnableUseCapsForID}",$EnableUseCapsForID,ico_shield);
    $tpl->table_form_field_bool("DNS Rebinding Prevention",$EnableDNSRebindingAttacks,ico_shield);
    $tpl->table_form_field_text("{UnBoundUnwantedReplyThreshold}",$tpl->FormatNumber($UnBoundUnwantedReplyThreshold),ico_max);

    $tpl->table_form_field_js("Loadjs('fw.dns.unbound.edns.php')","AsDnsAdministrator");
    if($UnboundEDNS==0){
        $tpl->table_form_field_bool("EDNS",$UnboundEDNS,ico_shield_disabled);
    }else{
        $UnboundEDNSNetworks=explode("||",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNSNetworks"));
        if(strlen(@implode(", ",$UnboundEDNSNetworks))<2){
            $tpl->table_form_field_text("EDNS","{all}",ico_shield);
        }else{
            $tpl->table_form_field_text("EDNS",@implode(", ",$UnboundEDNSNetworks),ico_shield);
        }
    }

    $tpl->table_form_field_js("Loadjs('$page?unbound-dnssec-js=yes')","AsDnsAdministrator");
    $tpl->table_form_field_bool("DNSSEC",$UnBoundDNSSEC,ico_proto);
    $tpl->table_form_field_js("Loadjs('$page?unbound-security-js=yes')","AsDnsAdministrator");
    $tpl->table_form_field_bool("{activate_dns_blacklists}",$EnableUnboundBlackLists,ico_firewall);

    $tpl->table_form_section("{dns_resolution}");
    $tpl->table_form_field_js("Loadjs('$page?unbound-globalDoms-js=yes')","AsDnsAdministrator");
    $tpl->table_form_field_bool("{EnableDNSRootInts}",$EnableDNSRootInts,ico_earth);
    $tpl->table_form_field_bool("{DisableLocalReserv}",$DisableLocalReservation,ico_earth);

    $tpl->table_form_section("{performance}");

    $tpl->table_form_field_js("Loadjs('$page?unbound-performance-redis-js=yes')","AsDnsAdministrator");
    $tpl->table_form_field_bool("{UBOUND_REDIS}",$UnboundRedisEnabled,ico_database);


    $tpl->table_form_field_js("Loadjs('$page?unbound-performance-form-js=yes&section=perf')","AsDnsAdministrator");
    $tpl->table_form_field_text("{outgoing_range}","$UNBOUND_OUTGOING_RANGE",ico_performance);
    $tpl->table_form_section("{network}");
    $UnboundAutomaticInterface_text=null;

    $tpl->table_form_field_js("Loadjs('$page?unbound-interface-js=yes')","AsDnsAdministrator");
    if($ListenOnlyLoopBack==1){
        $tpl->table_form_field_text("{listen_interface}","127.0.0.1",ico_interface);
    }else{
        if($InComingInterfaces==null){$InComingInterfaces="{all}";}
        if($UnboundOutGoingInterface==null){$UnboundOutGoingInterface="{default}";}
        if($UnboundAutomaticInterface==1){
            $UnboundAutomaticInterface_text="{interface_automatic}";
        }

        $tpl->table_form_field_text("{listen_interfaces}","$InComingInterfaces $UnboundAutomaticInterface_text",ico_interface);
        $tpl->table_form_field_text("{outgoing_interface}",$UnboundOutGoingInterface,ico_interface);

        $tpl->table_form_field_js("Loadjs('$page?unbound-doh-js=yes')","AsDnsAdministrator");
        $UnboundDOHEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHEnable"));
        if($UnboundDOHEnable==0){
            $tpl->table_form_field_bool("{DOH_ENABLE}",$UnboundDOHEnable,ico_proto);

        }else{
            $UnboundDOHPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHPort"));
            $UnboundDOHURI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHURI"));
            $UnboundDOHInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDOHInterface"));
            if($UnboundDOHInterface==null){$UnboundDOHInterface="eth0";}
            if($UnboundDOHPort==0){$UnboundDOHPort=443;}
            if($UnboundDOHURI==null){$UnboundDOHURI="/dns-query";}
            $tpl->table_form_field_bool("{DOH_ENABLE}",$UnboundDOHEnable,ico_proto);
            $tpl->table_form_field_text("{listen_interface}",
                "$UnboundDOHInterface:$UnboundDOHPort/$UnboundDOHURI",ico_interface);


        }


        $tpl->table_form_field_js("Loadjs('$page?unbound-tls-js=yes')","AsDnsAdministrator");

        if($UnboundTLSEnable==0){
            $tpl->table_form_field_bool("{DNSOTLS_ENABLE}",$UnboundTLSEnable,ico_ssl);
        }else{
            $tpl->table_form_field_bool("{DNSOTLS_ENABLE}",$UnboundTLSEnable,ico_check);
            $tpl->table_form_field_text("{listen_port}", "$UnboundTLSPort ($UnboundTLSCertificate)",ico_interface);


        }

    }


    $tpl->table_form_field_js("Loadjs('$page?unbound-cache-js=yes')","AsDnsAdministrator");
    $tpl->table_form_section("{cache}");
    $tpl->table_form_field_text("{cache_size}", "{$UnBoundCacheSize}MB",ico_database);
    $tpl->table_form_field_text("{cache-ttl}", "{$TIMES[$UnBoundCacheMinTTL]} (Min)  {$TIMES[$UnBoundCacheMAXTTL]} (Max) {$TIMES[$UnBoundCacheNEGTTL]} {negquery-cache-ttl}",ico_timeout);


    $tpl->table_form_field_js("Loadjs('$page?unbound-monitor-js=yes')","AsDnsAdministrator");
    $tpl->table_form_section("{monitor}");

    if($EnableSNMPD==0) {
        $tpl->table_form_field_text("{enable_snmp}", "{not_installed}", ico_monitor);
    }else{
        $tpl->table_form_field_bool("{enable_snmp}",$EnableUnBoundSNMPD,ico_monitor);
    }

    $jstiny=null;
    $myform=$tpl->table_form_compile();


    $html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='unbound-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>
		    <div id='ubound-top-status'></div>
		    $myform
		</td>
	</tr>
	</table>
	<script>
	$jstiny
	Loadjs('$page?unbound-status-recursors=yes');
    
	</script>	
	";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function unbound_status_recursors(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    $f[]="if (typeof window.refreshIntervalSet === 'undefined') {";
    $f[]="\twindow.refreshIntervalSet = false;";
    $f[]="window.refreshIntervalId;";
    $f[]="}\n";



    $f[]="function UnboundRefresh(){";
    $f[]="\tif(!document.getElementById('unbound-status')){";
    $f[]="\t\tclearInterval(refreshIntervalId);";
    $f[]="\t\trefreshIntervalSet=false;";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tLoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
    $f[]="\tLoadAjaxSilent('ubound-top-status','$page?ubound-top-status=yes');";
    $f[]="}";
    $f[]="if (!refreshIntervalSet) {";
    $f[]="\trefreshIntervalId=setInterval(UnboundRefresh, 5000);";
    $f[]="\trefreshIntervalSet = true;";
    $f[]="\tUnboundRefresh();";
    $f[]="}else{ UnboundRefresh();}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}


function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if(is_file($_POST["EnableUnboundBlackLists"])) {
        if ($UnboundEnabled == 0) {
            $_POST["EnableUnboundBlackLists"] = 0;
        }
    }
    $EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));

    if(isset($_POST["InComingInterfaces"])){
        $array=explode(",",$_POST["InComingInterfaces"]);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
        unset($_POST["InComingInterfaces"]);
    }

    $tpl->SAVE_POSTs();

    if(isset($_POST["EnableUnboundBlackLists"])) {
        if ($_POST["EnableUnboundBlackLists"] <> $EnableUnboundBlackLists) {
            if ($_POST["EnableUnboundBlackLists"] == 1) {
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?blacklists-enable=yes");
            } else {
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?blacklists-disable=yes");
            }
        }
    }

    $sock=new sockets();
    $sock->REST_API("/unbound/reconfig");
    $sock->REST_API("/unbound/redis/config");

    if(isset($_POST["EnableUnBoundSNMPD"])) {
        if ($_POST["EnableUnBoundSNMPD"] <> $EnableUnBoundSNMPD) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
        }
    }
}
function unbound_top_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableDNSFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    if($UnboundEnabled==0){return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/stats");
    $f=explode("\n",@file_get_contents(PROGRESS_DIR."/unbound.control.stats"));
    $UNBOUND_CONTROL=array();
    $UNBOUND_CONTROL["CACHES"]=0;
    $UNBOUND_CONTROL["QUERIES"]=0;
    $UNBOUND_CONTROL["MISS"]=0;
    $jsrefres="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');LoadAjaxSilent('unbound-status','$page?unbound-status=yes');LoadAjaxSilent('ubound-top-status','$page?ubound-top-status=yes');";
    $Thread=0;
    foreach ($f as $line){
        if(preg_match("#thread[0-9]+\.num\.queries#", $line)){$Thread++;}
        if(preg_match("#total.num.queries=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["QUERIES"]=$re[1];}
        if(preg_match("#total.num.cachehits=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["CACHES"]=$re[1];}
        if(preg_match("#total.num.cachemiss=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["MISS"]=$re[1];}
        if(preg_match("#total.num.prefetch=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["PREFETCH"]=$re[1];}
    }
    $SUM=$UNBOUND_CONTROL["CACHES"] + $UNBOUND_CONTROL["MISS"];
    $CACHE_RATE=0;
    if($SUM>0){
        $CACHE_RATE = round(($UNBOUND_CONTROL["CACHES"] / $SUM) * 100, 2);
    }
    $queries=$tpl->FormatNumber($UNBOUND_CONTROL["QUERIES"]);



    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM policies WHERE enabled=1");
    $RpzDB=intval($ligne["tcount"]);
    $button["help"]="https://wiki.articatech.com/dns/dns-cache-service";
    if($RpzDB==0){
        $FW = $tpl->widget_h("gray", "fad fa-shield-alt", "{disabled}", "{POLICIES_ZONES}",$button);
    }else{
        $FW = $tpl->widget_h("green", "fad fa-shield-alt", "$RpzDB&nbsp;{databases}", "{records} {POLICIES_ZONES}",$button);
        $RPZRealCount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZRealCount"));
        if ($RPZRealCount>50){
            $RPZRealCount=$tpl->FormatNumber($RPZRealCount);
            $FW = $tpl->widget_h("green", "fad fa-shield-alt", "$RPZRealCount", "{records} {POLICIES_ZONES}",$button);
        }
    }






    $CACHE_RATE_WIDGET= $tpl->widget_h("green", "fad fa-memory", "{$CACHE_RATE}%", "{cache_rate}",$button);
    $CACHE_RATE_QUERY= $tpl->widget_h("green", "far fa-bolt", "$queries", "{queries}",$button);



    $html[]="<div style='margin-left:10px;margin-top:5px'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$CACHE_RATE_WIDGET</td>";
    $html[]="<td style='padding-left:10px;width:33%'>$CACHE_RATE_QUERY</td>";
    $html[]="<td style='padding-left:10px;width:33%'>$FW</td>";
    $html[]="</tr>";
    $html[]="</table></div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}