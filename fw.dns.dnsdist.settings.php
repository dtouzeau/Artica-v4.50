<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["DNSDisteDNS"])){dnsdist_edns_save();exit;}
if(isset($_POST["DNSDistQps"])){save();exit;}
if(isset($_POST["UnboundMaxLogsize"])){save();exit;}
if(isset($_POST["PowerDNSListenAddr"])){save();exit;}
if(isset($_POST["DNSDistCheckInterval"])){save();exit;}
if(isset($_POST["UnBoundCacheSize"])){save();exit;}
if(isset($_POST["UnboundMaxLogsize"])){save();exit;}
if(isset($_POST["DNSDISTDynamicBlocks"])){save();exit;}
if(isset($_GET["dnsdist-status-left"])){left_status();exit;}
if(isset($_GET["EnforceUserDNSTTL-js"])){EnforceUserDNSTTL_js();exit;}
if(isset($_GET["EnforceUserDNSTTL-popup"])){EnforceUserDNSTTL_popup();exit;}
if(isset($_POST["EnableEnforceUserDNSTTL"])){EnforceUserDNSTTL_save();exit;}

if(isset($_GET["dnsdist-cache-js"])){dnsdist_cache_js();exit;}
if(isset($_GET["dnsdist-cache-popup"])){dnsdist_cache_popup();exit;}
if(isset($_GET["dnsdist-monitor-js"])){dnsdist_monitor_js();exit;}
if(isset($_GET["dnsdist-security-js"])){dnsdist_security_js();exit;}
if(isset($_GET["dnsdist-security-popup"])){dnsdist_security_popup();exit;}
if(isset($_GET["dnsdist-interface-js"])){dnsdist_interfaces_js();exit;}
if(isset($_GET["dnsdist-interface-popup"])){dnsdist_interfaces_popup();exit;}
if(isset($_GET["dnsdist-ha-js"])){dnsdist_ha_js();exit;}
if(isset($_GET["dnsdist-ha-popup"])){dnsdist_ha_popup();exit;}
if(isset($_GET["dnsdist-dynblock-js"])){dnsdist_dynblock_js();exit;}
if(isset($_GET["dnsdist-dynblock-popup"])){dnsdist_dynblock_popup();exit;}
if(isset($_GET["dnsdist-eDNS-js"])){dnsdist_edns_js();exit;}
if(isset($_GET["dnsdist-eDNS-popup"])){dnsdist_edns_popup();exit;}

table();

function save():bool{
    $tpl=new template_admin();

    if(isset($_POST["EnableCategories"])){
        if(intval($_POST["EnableCategories"])==0){
            $_POST["DNSDistDisableCategories"]=1;
        }else{
            $_POST["DNSDistDisableCategories"]=0;
        }
        unset($_POST["EnableCategories"]);
    }

    if(isset($_POST["DNSDistCheckInterval"])){
        if(intval($_POST["DNSDistCheckInterval"])<2){
            $languageF=$tpl->_ENGINE_parse_body("{check_interval} ({seconds})");
            echo "jserror:$languageF < 2";
            return false;
        }
    }

    if(isset($_POST["DNSDistCheckTimeout"])){
        if(intval($_POST["DNSDistCheckTimeout"])<3){
            $languageF=$tpl->_ENGINE_parse_body("{timeout} ({seconds})");
            echo "jserror:$languageF < 3";
            return false;
        }
    }



    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/dnsfw/service/php/restart");

    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");
    }

    return admin_tracks("Save DNS Firewall Main Configuration");

}

function dnsdist_security_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{security}","$page?dnsdist-security-popup=yes");
}
function dnsdist_interfaces_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{interfaces}","$page?dnsdist-interface-popup=yes");

}
function dnsdist_interfaces_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UnboundOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));
    $DNSDISTReusePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTReusePort"));
    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
    $InComingInterfaces=@implode(",", $PowerDNSListenAddr);


   $form[]=$tpl->field_interfaces_choose("PowerDNSListenAddr", "{listen_interfaces}", $InComingInterfaces);
   $form[]=$tpl->field_checkbox("DNSDISTReusePort","SO_REUSEPORT",$DNSDISTReusePort,false);

   $form[]=$tpl->field_interfaces("UnboundOutGoingInterface",
        "{outgoing_interface}", $UnboundOutGoingInterface);



    $jsafter[]="if( document.getElementById('dnsdist-table-start')){";
    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');}";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=restart_js();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");

    return true;
}
function dnsdist_cache_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{cache}","$page?dnsdist-cache-popup=yes");

}
function EnforceUserDNSTTL_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{enforce_user_dns_ttl}","$page?EnforceUserDNSTTL-popup=yes");
}
function EnforceUserDNSTTL_save():bool{
    $DisableEnforceUserDNSTTL=1;
    if($_POST["EnableEnforceUserDNSTTL"]==1){
        $DisableEnforceUserDNSTTL=0;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableEnforceUserDNSTTL",$DisableEnforceUserDNSTTL);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnforceUserDNSTTL",intval($_POST["EnforceUserDNSTTL"]));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/php/restart");
    return admin_tracks("Save DNS Firewall Enforce User DNS TTL to {$_POST["EnforceUserDNSTTL"]}");
}
function EnforceUserDNSTTL_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DisableEnforceUserDNSTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableEnforceUserDNSTTL"));
    $EnforceUserDNSTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceUserDNSTTL"));
    $Enabled=0;
    if($DisableEnforceUserDNSTTL==0){
        $Enabled=1;
    }
    if($EnforceUserDNSTTL==0){
        $EnforceUserDNSTTL=3600;
    }
    $form[]=$tpl->field_checkbox("EnableEnforceUserDNSTTL","{enable_feature}",$Enabled,true);
    $form[]=$tpl->field_numeric("EnforceUserDNSTTL", "{cache-ttl} ({seconds})", $EnforceUserDNSTTL);
    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    $jsafter[]="dialogInstance2.close()";


    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function dnsdist_cache_popup():bool{
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


    $setStaleCacheEntriesTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("setStaleCacheEntriesTTL"));
    if($setStaleCacheEntriesTTL==0){$setStaleCacheEntriesTTL=16600;}
    if($UnBoundCacheSize==0){$UnBoundCacheSize=100;}

    $form[]=$tpl->field_numeric("UnBoundCacheSize", "{cache_size} (MB)", $UnBoundCacheSize);
    $form[]=$tpl->field_numeric("setStaleCacheEntriesTTL", "{setStaleCacheEntriesTTL} ({seconds})", $setStaleCacheEntriesTTL,"{setStaleCacheEntriesTTL_explain}");
    $form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheMinTTL", "{cache-ttl} (Min)", $UnBoundCacheMinTTL);
    $form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheMAXTTL", "{cache-ttl} (Max)", $UnBoundCacheMAXTTL);
    $form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheNEGTTL", "{negquery-cache-ttl}", $UnBoundCacheNEGTTL);

    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=restart_js();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function dnsdist_ha_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{service_parameters}","$page?dnsdist-ha-popup=yes");
    return true;
}
function dnsdist_dynblock_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("Dynamic Blocks","$page?dnsdist-dynblock-popup=yes");
    return true;
}
function dnsdist_edns_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{eDNS_support}","$page?dnsdist-eDNS-popup=yes",550);
    return true;
}

function dnsdist_edns_popup():bool{
    $tpl=new template_admin();

    $DNSDisteDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDisteDNS"));
    $DNSDisteDNS32=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDisteDNS32"));
    $DNSDisteDNS24=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDisteDNS24"));

    $form[]=$tpl->field_checkbox("DNSDisteDNS","{enable}",$DNSDisteDNS,true);
    $form[]=$tpl->field_checkbox("DNSDisteDNS32","{enable} {network} /32",$DNSDisteDNS32);
    $form[]=$tpl->field_checkbox("DNSDisteDNS24","{enable} {network} /24",$DNSDisteDNS24);

    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=restart_js();
    echo $tpl->form_outside(null, $form,"{eDNS_support_dnsdist}","{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}
function dnsdist_edns_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks("Set eDNS support on DNS Firewall to {$_POST["DNSDisteDNS"]}");
}

function dnsdist_dynblock_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $DNSDISTDynamicBlocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicBlocks"));
    $DNSDISTDynamicMaxReq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicMaxReq"));
    $DNSDISTDynamicMaxSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicMaxSec"));
    $DNSDISTDynamicBlockSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicBlockSec"));
    $DNSDISTDynamicWhite=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicWhite"));

    if($DNSDISTDynamicWhite==null){$DNSDISTDynamicWhite="127.0.0.0/8,192.168.0.0/16,10.0.0.0/16";}
    if($DNSDISTDynamicMaxSec==0){$DNSDISTDynamicMaxSec=20;}
    if($DNSDISTDynamicMaxReq==0){$DNSDISTDynamicMaxReq=50;}
    if($DNSDISTDynamicBlockSec==0){$DNSDISTDynamicBlockSec=60;}
    if($DNSDISTDynamicMaxSec<5){$DNSDISTDynamicMaxSec=10;}
    if($DNSDISTDynamicMaxSec<5){$DNSDISTDynamicMaxSec=10;}
    if($DNSDISTDynamicBlockSec<2){$DNSDISTDynamicBlockSec=2;}

    $form[]=$tpl->field_checkbox("DNSDISTDynamicBlocks","{enable}",$DNSDISTDynamicBlocks,true);
    $form[]=$tpl->field_numeric("DNSDISTDynamicMaxReq","{max_requests}",$DNSDISTDynamicMaxReq);
    $form[]=$tpl->field_numeric("DNSDISTDynamicMaxSec","{during} ({seconds})",$DNSDISTDynamicMaxSec);
    $form[]=$tpl->field_numeric("DNSDISTDynamicBlockSec","{then_block_during} ({seconds})",$DNSDISTDynamicBlockSec);
    $form[]=$tpl->field_text("DNSDISTDynamicWhite","{deny_access_except}",$DNSDISTDynamicWhite);

    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=restart_js();

    echo $tpl->form_outside(null, $form,"{dnsdist_dynblock}","{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}

function dnsdist_ha_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $algo["chashed"]="{strict-hashed-ip}";
    $algo["roundrobin"]="{round-robin}";
    $algo["leastOutstanding"]="{leastconn}";
    $algo["firstAvailable"]="{firstAvailable}";


    $DNSDistSetServerPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistSetServerPolicy"));



    if($DNSDistSetServerPolicy==null){$DNSDistSetServerPolicy="leastOutstanding";}
    $DNSDistCheckName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckName"));
    $DNSDistCheckInterval=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistMaxCheckFailures=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
    $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));
    if(intval($DNSDistCheckTimeout)==0){$DNSDistCheckTimeout=1;}
    if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
    if(intval($DNSDistCheckInterval)==0){$DNSDistCheckInterval=1;}
    if(intval($DNSDistMaxCheckFailures)==0){$DNSDistMaxCheckFailures=3;}
    $setServFailWhenNoServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("setServFailWhenNoServer"));
    $DNSDistDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDebug"));
    if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}
    if($DNSDistCheckInterval<2){$DNSDistCheckInterval=2;}

    $form[]=$tpl->field_checkbox("DNSDistDebug","{debug_mode}",$DNSDistDebug,false,"");

    $form[]=$tpl->field_array_hash($algo,"DNSDistSetServerPolicy","{APP_HAPROXY_SERVICE} ({method})",$DNSDistSetServerPolicy);

    $form[]=$tpl->field_checkbox("setServFailWhenNoServer","{setServFailWhenNoServer}",$setServFailWhenNoServer,false,"{setServFailWhenNoServer_explain}");
    $form[]=$tpl->field_text("DNSDistCheckName", "{check_addr}", $DNSDistCheckName,true);
    $form[]=$tpl->field_numeric("DNSDistCheckTimeout", "{timeout} ({seconds})", $DNSDistCheckTimeout);
    $form[]=$tpl->field_numeric("DNSDistCheckInterval", "{check_interval} ({seconds})", $DNSDistCheckInterval);
    $form[]=$tpl->field_numeric("DNSDistMaxCheckFailures", "{failed_number} ({attempts})", $DNSDistMaxCheckFailures);

    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=restart_js();

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");

    return true;
}

function dnsdist_security_popup():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();

    $DNSDistQps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistQps"));
    $MaxQPSIPRule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MaxQPSIPRule"));
    $DNSDistBlockMalware=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistBlockMalware"));

    $EnableCategories=1;
    $DNSDistDisableCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDisableCategories"));
    if($DNSDistDisableCategories==1){$EnableCategories=0;}

    $jsafter[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]=restart_js();


    $form[]=$tpl->field_checkbox("EnableCategories","{find_categories}",$EnableCategories,false,"{EnableCategoriesDNSDIST}");
    $form[]=$tpl->field_checkbox("DNSDistBlockMalware","{blks_dns_malware}",$DNSDistBlockMalware);
    $form[]=$tpl->field_numeric("DNSDistQps","{maxqps} (0={unlimited})",$DNSDistQps);
    $form[]=$tpl->field_numeric("MaxQPSIPRule","{MaxQPSIPRule} (0={unlimited})",$MaxQPSIPRule);

    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;
}


function dnsdist_monitor_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{monitoring}","$page?dnsdist-monitor-popup=yes");
    return true;
}
function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DNSDistSetServerPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistSetServerPolicy"));
    if($DNSDistSetServerPolicy==null){$DNSDistSetServerPolicy="leastOutstanding";}

    $DNSDISTDynamicBlocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicBlocks"));
    $DNSDISTReusePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTReusePort"));

    if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMinTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMinTTL", 3600);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMAXTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMAXTTL", 172800);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheNEGTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheNEGTTL", 3600);}




    $ipclass=new IP();
    $UnBoundCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheSize"));
    $UnBoundCacheMinTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
    $UnBoundCacheNEGTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheNEGTTL"));
    $UnboundOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));
    $setStaleCacheEntriesTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("setStaleCacheEntriesTTL"));

    $DisableEnforceUserDNSTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableEnforceUserDNSTTL"));
    $EnforceUserDNSTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceUserDNSTTL"));

    $forcesafesearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoogleSafeSearchAddress"));
    if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}
    if($forcesafesearch==null){$forcesafesearch=$GLOBALS["CLASS_SOCKETS"]->gethostbyname("forcesafesearch.google.com");}
    if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}

    $DNSDistCheckName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckName"));
    $DNSDistCheckInterval=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistMaxCheckFailures=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
    $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));
    $DNSDistDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDebug"));
    if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}
    if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
    if(intval($DNSDistCheckInterval)==0){$DNSDistCheckInterval=1;}
    if(intval($DNSDistMaxCheckFailures)==0){$DNSDistMaxCheckFailures=3;}
    if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}
    if($DNSDistCheckInterval<2){$DNSDistCheckInterval=2;}
    $DNSDisteDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDisteDNS"));

    $setServFailWhenNoServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("setServFailWhenNoServer"));
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

    $InComingInterfaces=@implode(",", $PowerDNSListenAddr);



    $DNSDistBlockMalware=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistBlockMalware"));
    $DNSDistQps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistQps"));
    $MaxQPSIPRule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MaxQPSIPRule"));
    if($DNSDistQps==0){$DNSDistQps="{unlimited}";}
    if($MaxQPSIPRule==0){$MaxQPSIPRule="{unlimited}";}


    $tpl->table_form_section("{security}");
    $tpl->table_form_field_js("Loadjs('fw.pdns.restrictions.php?ViaSpopup=yes')","AsDnsAdministrator");
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sql="SELECT * FROM pdns_restricts";
    $results = $q->QUERY_SQL($sql);
    $ACLREST=array();
    if(count($results)==0) {
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";
        $ACLREST[] = "127.0.0.0/8";
    }else {
        $c=0;
        foreach ($results as $index => $ligne) {
            $c++;
            if($c>4){
                $ACLREST[]="...";
                break;
            }
            $ACLREST[]=$ligne["address"];
        }
    }
    $tpl->table_form_field_text("{networks_restrictions}",@implode(", ",$ACLREST),ico_shield);
    $tpl->table_form_field_js("Loadjs('$page?dnsdist-security-js=yes')","AsDnsAdministrator");
    $tpl->table_form_field_text("{limits}","<small>{maxqps} $DNSDistQps, {MaxQPSIPRule} $MaxQPSIPRule</small>",ico_max);
    $tpl->table_form_field_bool("{blks_dns_malware}",$DNSDistBlockMalware,ico_bug);

    $EnableCategories=1;
    $DNSDistDisableCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDisableCategories"));
    if($DNSDistDisableCategories==1){$EnableCategories=0;}
    $tpl->table_form_field_bool("{find_categories}",$EnableCategories,ico_books);


    $tpl->table_form_field_js("Loadjs('$page?dnsdist-dynblock-js=yes')","AsDnsAdministrator");
    if($DNSDISTDynamicBlocks==0){
        $tpl->table_form_field_bool("Dynamic Blocks",$DNSDISTDynamicBlocks,ico_shield);
    }else{
        $DNSDISTDynamicMaxReq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicMaxReq"));
        $DNSDISTDynamicMaxSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicMaxSec"));
        $DNSDISTDynamicBlockSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicBlockSec"));
        $DNSDISTDynamicWhite=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicWhite"));

        if($DNSDISTDynamicWhite==null){$DNSDISTDynamicWhite="127.0.0.0/8,192.168.0.0/16,10.0.0.0/16";}
        if($DNSDISTDynamicMaxSec==0){$DNSDISTDynamicMaxSec=20;}
        if($DNSDISTDynamicMaxReq==0){$DNSDISTDynamicMaxReq=50;}
        if($DNSDISTDynamicBlockSec==0){$DNSDISTDynamicBlockSec=60;}
        if($DNSDISTDynamicMaxSec<5){$DNSDISTDynamicMaxSec=10;}
        if($DNSDISTDynamicMaxSec<5){$DNSDISTDynamicMaxSec=10;}
        if($DNSDISTDynamicBlockSec<2){$DNSDISTDynamicBlockSec=2;}

        $tpl->table_form_field_text("Dynamic Blocks","<small>{max_requests}: $DNSDISTDynamicMaxReq {during} $DNSDISTDynamicMaxReq {seconds} {then_block_during} $DNSDISTDynamicBlockSec {seconds} {deny_access_except} $DNSDISTDynamicWhite</small>",ico_shield);
    }





    $tpl->table_form_field_js("Loadjs('$page?dnsdist-interface-js=yes')");
    $tpl->table_form_section("{network}");
    $REUSE_PORT="";
    if($DNSDISTReusePort==1){
        $REUSE_PORT=" (SO_REUSEPORT)";
    }

    if($InComingInterfaces==null){$InComingInterfaces="{all}";}
    if($UnboundOutGoingInterface==null){$UnboundOutGoingInterface="{default}";}
    $tpl->table_form_field_text("{listen_interfaces}",$InComingInterfaces.$REUSE_PORT,ico_interface);
    $tpl->table_form_field_text("{outgoing_interface}",$UnboundOutGoingInterface,ico_interface);

    $tpl->table_form_field_js("Loadjs('$page?dnsdist-ha-js=yes')");
    $tpl->table_form_section("{service_parameters}");

    $tpl->table_form_field_bool("{debug_mode}",$DNSDistDebug,ico_bug);
    $tpl->table_form_field_bool("{setServFailWhenNoServer}",$setServFailWhenNoServer,ico_timeout);
    $tpl->table_form_field_text("{APP_HAPROXY_SERVICE}","{method}: ". $algo[$DNSDistSetServerPolicy],ico_exchange);

    $tpl->table_form_field_js("Loadjs('$page?dnsdist-eDNS-js=yes')");
    $tpl->table_form_field_bool("{eDNS_support}",$DNSDisteDNS,ico_computer);



    $tpl->table_form_field_text("{check_addr}",
        "<small>$DNSDistCheckName {timeout} {$DNSDistCheckTimeout}s {check_interval} {$DNSDistCheckInterval}s {failed_number} $DNSDistMaxCheckFailures {attempts}</small>"
        ,ico_engine_warning);



    $tpl->table_form_section("{cache}");

     if($EnforceUserDNSTTL==0){
         $EnforceUserDNSTTL=3600;
     }
    $tpl->table_form_field_js("Loadjs('$page?EnforceUserDNSTTL-js=yes')");
    if($DisableEnforceUserDNSTTL==0){
        $tpl->table_form_field_text("{enforce_user_dns_ttl}","$EnforceUserDNSTTL {seconds}",ico_timeout);
    }else{
        $tpl->table_form_field_bool("{enforce_user_dns_ttl}",0,ico_timeout);
    }

    $tpl->table_form_field_js("Loadjs('$page?dnsdist-cache-js=yes')");
    if($setStaleCacheEntriesTTL==0){
        $setStaleCacheEntriesTTL=16600;
        $setStaleCacheEntriesTTL_text=distanceOfTimeInWords(time(),time()+$setStaleCacheEntriesTTL);

    }
    $tpl->table_form_field_text("{setStaleCacheEntriesTTL}", "$setStaleCacheEntriesTTL_text",ico_timeout);
    $tpl->table_form_field_text("{cache_size}", "{$UnBoundCacheSize}MB",ico_database);
    $tpl->table_form_field_text("{cache-ttl}", "<small>{$TIMES[$UnBoundCacheMinTTL]} (Min),  {$TIMES[$UnBoundCacheMAXTTL]} (Max),  {negquery-cache-ttl} {$TIMES[$UnBoundCacheNEGTTL]}</small>",ico_timeout);



    $jstiny=null;

$error=CountOfInterfaces();
    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}: {general_parameters}";
    $TINY_ARRAY["ICO"]="fab fa-gripfire";
    $TINY_ARRAY["EXPL"]="$error{APP_DNSDIST_EXPLAIN2}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $myform=$tpl->table_form_compile();


    $html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'>
		    <div id='dnsdist-left' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>
		    <div id='ubound-top-status'></div>
		    $myform
		 </td>
	</tr>
	</table>
	<script>
	$jstiny
	LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');
	</script>	
	";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function CountOfInterfaces():string{
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
    return "<p class='text-danger'><strong>". $tpl->td_href("{no_listen_interfaces_defined}",null,"Loadjs('fw.dns.dnsdist.settings.php?dnsdist-interface-js=yes');")."</strong></p>";
}
function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $f[]="if( document.getElementById('progress-unbound-restart')){";
    $f[]=$tpl->framework_buildjs("/dnsfw/service/php/restart",
        "dnsdist.restart","dnsdist.restart.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');"
    );
    $f[]="}";
    return @implode("",$f);

}

function left_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
    $bsini=new Bs_IniHandler(PROGRESS_DIR."/dnsdist.status");



    $html[]=$tpl->SERVICE_STATUS($bsini, "APP_DNSDIST",restart_js());

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dns/collector/status"));
    $bsiniCollector=new Bs_IniHandler();
    $bsiniCollector->loadString($json->Info);
    $jsRestartCollector=$tpl->framework_buildjs("/dns/collector/restart","dns-collector.progress",
        "dns-collector.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');;");

    $html[]=$tpl->SERVICE_STATUS($bsiniCollector, "APP_DNS_COLLECTOR",$jsRestartCollector);



    echo $tpl->_ENGINE_parse_body($html);
    return true;
}