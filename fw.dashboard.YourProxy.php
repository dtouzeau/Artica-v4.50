<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["memory"])){memory_status();}
if(isset($_POST["none"])){exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["purge-progress"])){purge_popup_js();exit;}
if(isset($_GET["purge-popup"])){purge_popup();exit;}


start();

function purge(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{empty_dns_cache_squid_explain}","none","none","Loadjs('$page?purge-progress=yes')");
}
function purge_popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{empty_cache}","$page?purge-popup=yes");
}
function purge_popup(){
    $t=time();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.dns.purge.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.dns.purge.progress.log";
    $ARRAY["CMD"]="squid2.php?purge-dns=yes";
    $ARRAY["TITLE"]="{empty_dns_cache}";
    $ARRAY["AFTER"]="dialogInstance1.close();LoadAjax('dashboard-proxy-status','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$t')";
    $html="<div id='$t'></div><script>$jsrestart</script>";
    echo $html;
}


function start(){

    $page=CurrentPageName();
    echo "<div id='dashboard-proxy-restart'></div><div id='dashboard-proxy-status'></div><script>LoadAjax('dashboard-proxy-status','$page?page=yes');</script>";
}


function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    //$MyCurrentTime=date("YmdH");
    $memcached=new lib_memcached();
    //$value=$memcached->getKey("SquidLicenseUsers");
   // $NumBerOfUsers=FormatNumber(intval($value[$MyCurrentTime]["USERS"]));

    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}
    $ActiveDirectoryEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));


    $AD_FOUND=false;
    if($EnableKerbAuth==1) {
        $f = explode("\n", @file_get_contents("/etc/squid3/authenticate.conf"));
        foreach ($f as $line) {
            $line = trim($line);
            if (preg_match("#^auth_param (negotiate|ntlm) program#",$line)) {
                $AD_FOUND = true;
                break;
            }
        }
    }

    $SQUID_PORT_COUNTERS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_PORT_COUNTERS"));

    $server_all_requests=FormatNumber($SQUID_PORT_COUNTERS["server.all.requests"]);
    $server_all_kbytes_in=intval($SQUID_PORT_COUNTERS["server.all.kbytes_in"]);
    $server_all_kbytes_out=intval($SQUID_PORT_COUNTERS["server.all.kbytes_out"]);
    $server_all_all=$server_all_kbytes_in+$server_all_kbytes_out;
//<i class="fas fa-cogs"></i>
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?purge=yes');\"><i class='fas fa-trash-alt'></i> {empty_dns_cache} </label>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.proxy.actions.php');\"><i class='fas fa-cogs'></i> {services_operations} </label>";



    if($EnableKerbAuth==1) {
        if(!$AD_FOUND){
            $jsreconnect=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","dashboard-proxy-restart","LoadAjax('dashboard-proxy-status','$page?page=yes');");



            $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$jsreconnect;\"><i class='fas fa-link'></i>Active Directory: {reconnect}</label>";
            $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.proxy.auth.conf.php');\"><i class='fas fa-link'></i>{file_configuration}</label>";
        }
    }



    $html[]="</div>";


    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    //<i class="fab fa-windows"></i>

    if($EnableKerbAuth==1){
        if($ActiveDirectoryEmergency==0) {
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fab fa-windows", "Active Directory", "{active2}") . "</td>";
        }else{
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("red-bg", "fab fa-windows", "Active Directory", "{emergency}") . "</td>";
        }


        //<i class="fas fa-link"></i>
        if($AD_FOUND){
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("lazur-bg", "fas fa-link", "Active Directory: {link}", "{linked}") . "</td>";
        }else{
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("red-bg", "fas fa-link", "Active Directory: {link}", "{disconnected}") . "</td>";
        }
        if($LockActiveDirectoryToKerberos==1) {
            $ARRAY = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_NEGOTIATE_AUTHENTICATOR"));
            if(!is_array($ARRAY)){$ARRAY=array();}
            if(!isset($ARRAY["MAX"])){$ARRAY["MAX"]=0;}
            if(!isset($ARRAY["SENT"])){$ARRAY["SENT"]=0;}
            $MAX = $ARRAY["MAX"];
            $SENT = $ARRAY["SENT"];
            //<i class="fas fa-microchip"></i>
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fas fa-microchip", "Active Directory: {requests}", "$SENT/$MAX") . "</td>";
        }


    }

    $html[]="</tr>";









    $squidstat=new squidstat();
    if(!$squidstat->connect()){
        $html[]="<td valign='top' style='padding:2px'>";
        $html[]=$tpl->widget_style1("yellow-bg","fas fa-users-class","{members}","??");
        $html[]="</td>";
    }else {
        $data = $squidstat->makeQuery();
        $squidstat->ReturnOnlyTitle=true;
        $Members = $squidstat->makeHtmlReport($data,false,array(),"host");
        $html[]="<td valign='top' style='padding:2px'>";
        $html[]=$tpl->widget_style1("lazur-bg","fas fa-users-class","{members}","$Members");
        $html[]="</td>";
    }


    $html[]="<td style='padding:2px'>".$tpl->widget_style1("navy-bg","fas fa-cloud-showers","{requests}",$server_all_requests)."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("lazur-bg",ico_download,"{total_downloaded}",FormatBytes($server_all_all))."</td>";
    $html[]="</tr>";

    $html[]=memory_status();
    $html[]=ufdbguard_status();
    $html[]=cache_stores_status();


    $html[]="</table>";





    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function memory_status(){
    $tpl=new template_admin();
    $html[]="<tr>";

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/mgrinfo"));
    $MgrInfo=$data->Info;
    $average_http_requests=$MgrInfo->average_http_requests;
    $storage_memsize=$MgrInfo->storage_memsize;
    $storage_mem_capacity=$MgrInfo->storage_mem_capacity;

    $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
    $AVERAGE_REQUESTS=null;

    VERBOSE("AVERAGE_REQUESTS=$average_http_requests",__LINE__);
    if($SquidDisableMemoryCache==0) {
        if ($storage_memsize>0) {
            $STORAGE_MEM_SIZE = FormatBytes($storage_memsize);
            $STORAGE_MEM_PRC_USED = $storage_mem_capacity;

            $AVERAGE_REQUESTS = FormatNumber(round($average_http_requests / 60)) . "/s";
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fad fa-memory", "{squid_cache_memory} ($STORAGE_MEM_SIZE)", "{$STORAGE_MEM_PRC_USED}%") . "</td>";
        } else {
            $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("gray-bg", "fad fa-memory", "{squid_cache_memory}", "???") . "</td>";
        }
    }else{

        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("gray-bg", "fad fa-memory", "{squid_cache_memory}", "{disabled}") . "</td>";

    }
    $SQUID_IPCACHE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_IPCACHE");
    VERBOSE("SQUID_IPCACHE:".strlen($SQUID_IPCACHE)." bytes",__LINE__);
    $tbr=explode("\n",$SQUID_IPCACHE);
    $IPcacheEntriesCached=0;
    $IPcacheRequests=0;
    foreach ($tbr as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        VERBOSE("SQUID_IPCACHE:$line",__LINE__);
        if (preg_match("#IPcache Entries Cached.*?([0-9]+)#i", $line, $re)) {
            $IPcacheEntriesCached = $re[1];
            continue;
        }
        if (preg_match("#IPcache Requests.*?([0-9]+)#i", $line, $re)) {
            $IPcacheRequests = $re[1];
            continue;
        }
    }

    $IPcacheEntriesCached=FormatNumber($IPcacheEntriesCached);
    $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("lazur-bg", "fas fa-list-ul", "{dns_cache}: {items}", $IPcacheEntriesCached) . "</td>";

    if($AVERAGE_REQUESTS==null){
        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("gray-bg", "fas fa-user-clock", "{requests}/{second}", "???") . "</td>";
    }else {
        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fas fa-user-clock", "{requests}/{second}", $AVERAGE_REQUESTS) . "</td>";
    }
    if(isset($_GET["memory"])){echo @implode("\n",$html);}
    $html[]="</tr>";
return @implode("\n",$html);
}





function ufdbguard_status(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $EnableStatsCommunicator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    $BANNED=$tpl->widget_style1("gray-bg", "fas fa-eye-slash", "{banned}", "0");
    $CATEGORIZED=$tpl->widget_style1("gray-bg", "fas fa-database", "{websites_categorized}", "0");
    $DAYC=0;
    $CATEGORIZED_ITEMS=0;



    if($EnableStatsCommunicator==1) {
        try {
            $redis = new Redis();
            $redis->connect("127.0.0.1", 4322, 2);
            $DAYC = intval($redis->get("DASHBOARD.TOTAL.BLOCKED"));
            if ($DAYC > 0) {
                $DAYCT = $tpl->FormatNumber($DAYC);
                $BANNED=$tpl->widget_style1("lazur-bg", "fas fa-eye-slash", "{banned}", "$DAYCT");
            }
            $CATEGORIZED_ITEMS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DASHBOARD.TOTAL.CATEGORIZED"));
            if ($CATEGORIZED_ITEMS > 0) {
                $CATEGORIZED = $tpl->widget_style1("navy-bg", "fas fa-database", "{websites_categorized}", $tpl->FormatNumber($CATEGORIZED_ITEMS));
            }

        } catch (Exception $e) {

        }
    }



    $html[]="<tr>";
    if($EnableUfdbGuard==0) {
        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("gray-bg", "fas fa-filter", "{web_filtering}", "{inactive}") . "</td>";
        $html[] = "<td style='padding:2px'>" . $BANNED . "</td>";
        $html[] = "<td style='padding:2px'>" . $CATEGORIZED . "</td>";
        $html[] = "<tr>";
        return @implode("\n", $html);

    }
    $items=0;
    $UfdbMasterCache=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    if(!is_array($UfdbMasterCache)){$UfdbMasterCache=array();}
    foreach ($UfdbMasterCache as $category_id=>$array ){
           if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
               if ($array["official_category"] == 1) {
                   $items = $items + $array["items"];
               }
           }else{
               if ($array["free_category"] == 1) {
                   $items = $items + $array["items"];
               }
           }

    }

    $UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)){$UfdbUsedDatabases=array();}
    if(!isset($UfdbUsedDatabases["MISSING"])){$UfdbUsedDatabases["MISSING"]=array();}
    if(!is_array($UfdbUsedDatabases["MISSING"])){$UfdbUsedDatabases["MISSING"]=array();}
    $CountDeMissing=count($UfdbUsedDatabases["MISSING"]);


    if($CountDeMissing>0) {
        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("yellow-bg", "fas fa-filter", "{web_filtering}", "$CountDeMissing {missing_databases}") . "</td>";
    }else{
       $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fas fa-filter", "{web_filtering}", "{active2}") . "</td>";
    }

    $DAYC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCounter"));

        $DAYCT = FormatNumber($DAYC);

        if ($DAYC == 0) {
            $BANNED = "<td style='padding:2px'>" . $tpl->widget_style1("gray-bg", "fas fa-eye-slash", "{banned}", "0") . "</td>";
        } else {
            $BANNED = "<td style='padding:2px'>" . $tpl->widget_style1("lazur-bg", "fas fa-eye-slash", "{banned}", "$DAYCT") . "</td>";

        }
        $items_int=$items;
        $items=FormatNumber($items);

        if($items_int>50000010) {
            $CATEGORIZED = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg",
                    "fas fa-database", "{websites_categorized}", $items) . "</td>";
        }else{
            if($items_int<1000000) {
                $CATEGORIZED = "<td style='padding:2px'>" . $tpl->widget_style1("red-bg",
                        "fas fa-database", "{websites_categorized}", $items) . "</td>";
            }else {
                $CATEGORIZED = "<td style='padding:2px'>" . $tpl->widget_style1("yellow-bg",
                        "fas fa-database", "{websites_categorized}", $items) . "</td>";
            }
        }



    $html[] = "<td style='padding:2px'>" . $BANNED . "</td>";
    $html[] = "<td style='padding:2px'>" . $CATEGORIZED . "</td>";
    $html[]="</tr>";

    return @implode("\n",$html);
}

function cache_stores_status(){
    $users=new usersMenus();
    $tpl=new template_admin();
    $SquidCachesProxyEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled");
    $DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));

    $html[]="<tr>";
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-download","{disk_caching}","{license_error}")."</td>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-hdd","{disks_usage}","-")."</td>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-percentage", "{performance}","0%")."</td>";
        $html[]="</tr>";
        return @implode("\n",$html);
    }
    if($SquidCachesProxyEnabled==0){
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-download","{disk_caching}","{disabled}")."</td>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-hdd","{disks_usage}","-")."</td>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-percentage", "{performance}","0%")."</td>";
        $html[]="</tr>";
        return @implode("\n",$html);
    }
    if($DisableAnyCache==1){
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("yellow-bg","fas fa-download","{disk_caching}","{paused}")."</td>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-hdd","{disks_usage}","-")."</td>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-percentage", "{performance}","0%")."</td>";
        $html[]="</tr>";
        return @implode("\n",$html);
    }

    $q=new lib_sqlite("/home/artica/SQLITE/caches.db");
    VERBOSE("SELECT * FROM squid_caches_center WHERE enabled=1",__LINE__);
    $results=$q->QUERY_SQL("SELECT * FROM squid_caches_center WHERE enabled=1"); // AND remove=0

    if(!$q->ok){
        $results=array();
    }

    VERBOSE("RESULTS: ".count($results),__LINE__);
    $cache_size=0;
    $usedcache=0;
    foreach ($results as $index=>$ligne){
        $cache_size=$ligne["cache_size"]+$cache_size;
        $usedcache=$usedcache+$ligne["usedcache"];
    }

    $cache_size=$cache_size*1024;
    $cache_size_Text=FormatBytes($cache_size);
    $prc=0;
    if($cache_size>0) {
        $prc = round(($usedcache / $cache_size) * 100, 1);
    }
    $usedcache_text=FormatBytes($usedcache);
    $bg="lazur-bg";

    if($prc<5){
        $bg="yellow-bg";
    }

    if($prc>90){
        $bg="yellow-bg";
    }
    if($prc>95){
        $bg="red-bg";
    }

    $StorEntries=0;
    $MaximumSwapSize=null;
    $CurrentCapacity=null;
    $CurrentStoreSwapSize=null;
    $SQUID_STORE_DIR_CONTENT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_STORE_DIR");
    $SQUID_STORE_DIR=explode("\n",$SQUID_STORE_DIR_CONTENT);
    $SQUID_STORE_DIR_LENGTH=strlen($SQUID_STORE_DIR_CONTENT);
    foreach ($SQUID_STORE_DIR as $line){
        $line=trim($line);
        if($line==null){continue;}

        if(preg_match("#^Store Entries.*?([0-9]+)#i",$line,$re)){
            $StorEntries=FormatNumber(intval($re[1]));
            continue;
        }
        if(preg_match("#^Maximum Swap Size.*?([0-9]+)#i",$line,$re)){
            $MaximumSwapSize=FormatBytes($re[1]);
            continue;
        }
        if(preg_match("#^Current Store Swap Size.*?([0-9]+)#i",$line,$re)){
            $CurrentStoreSwapSize=FormatBytes($re[1]);
            continue;
        }
        if(preg_match("#^Current Capacity.*?([0-9\.]+).*?used,.*?([0-9\.]+)#i",$line,$re)){
            $CurrentCapacity=$re[1];
            $CurrentCapacity_Free=$re[2];
            continue;
        }
    }
    if($CurrentStoreSwapSize<>null){$usedcache_text=$CurrentStoreSwapSize;}
    if($MaximumSwapSize<>null){$cache_size_Text=$MaximumSwapSize;}
    if($CurrentCapacity<>null){$prc=$CurrentCapacity;}

    if($StorEntries==0){
        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("yellow-bg", "fas fa-download", "{disk_caching} &laquo;{error} $SQUID_STORE_DIR_LENGTH&raquo;", "???? {items}") . "</td>";
    }else {
        $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fas fa-download", "{disk_caching}", "$StorEntries {items}") . "</td>";
    }
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("$bg","fas fa-hdd","{capacity} $usedcache_text/$cache_size_Text","{$prc}%")."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("gray-bg","fas fa-percentage", "{performance}","0%")."</td>";
    $html[]="</tr>";
    return @implode("\n",$html);

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}