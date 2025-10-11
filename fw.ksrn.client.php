<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["SavePost"])){Save();exit;}
if(isset($_GET["ksrn-status"])){status();exit;}
if(isset($_GET["emergency-enable"])){emergency_enable();exit;}
if(isset($_GET["logfile-js"])){logfile_js();exit;}
if(isset($_GET["ksrn-form-server"])){ksrn_form_server();exit;}
if(isset($_GET["all-ksrn-versions"])){all_ksrn_versions();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $libmem=new lib_memcached();
	$KRSN_VERSION=trim($libmem->getKey("ACL_FIRST_VERSION"));
    $KsrnClientVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnClientVersion");
	VERBOSE("KSRN_VERSION = [$KRSN_VERSION] / $KsrnClientVersion",__LINE__);
    if($KsrnClientVersion<>null){$KRSN_VERSION=$KsrnClientVersion;}


	if($KRSN_VERSION<>null){$KRSN_VERSION=" v$KRSN_VERSION";}



	$html[]="
    
	<div class=\"row border-bottom white-bg dashboard-header\">
	<table style='width:100%'>
	    <tr>
	        <td valign='top' style='padding-right: 10px;width:1%' nowrap><i class='fa-8x fad fa-exchange-alt'></i></td>
	        <td valign='top style='width:99%'><h1 class=ng-binding>{connector}{$KRSN_VERSION} </h1><p>
	        <span id='all-ksrn-versions'></span></p>
	        </td>
        </tr>
	
    </table>
		
	</div>
	<div class='row'>
	<div id='progress-ksrnclient-restart'></div>";
$html[]="</div><div class='row'><div class='ibox-content'>";
	$html[]="
	<div id='table-loader-ksrn-pages'></div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/filtering-client');
	LoadAjax('table-loader-ksrn-pages','$page?table=yes');
	</script>";

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,@implode("\n",$html));echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}



function logfile_js(){

    $tpl=new template_admin();
    echo $tpl->framework_buildjs("ksrn.php?log-file=yes","ksrn.progress","ksrn.log",
        "progress-ksrnclient-restart","document.location.href='/ressources/logs/web/ksrn.log.gz';");

}

function all_ksrn_versions(){
    $KsrnClientVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnClientVersion");
    $THE_SHIELD_SERVICE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("THE_SHIELD_SERVICE_VERSION");
    $KSRN_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_VERSION");
    $CATEGORIZE_CLASS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CATEGORIZE_CLASS_VERSION");
    echo "Client: <span class=font-bold>$KsrnClientVersion</span>, Server: <span class=font-bold>$THE_SHIELD_SERVICE_VERSION</span>, Server library: <span class=font-bold>$KSRN_VERSION</span>, Categorize library: <span class=font-bold>$CATEGORIZE_CLASS_VERSION</span><br>";
}

function table(){


	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$page=CurrentPageName();
    $kInfos         = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}
    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_DAEMONS"));
    if(!isset($SquidClientParams["ksrn_param_daemon_children"])){$SquidClientParams["ksrn_param_daemon_children"]=10;}
    if(!isset($SquidClientParams["ksrn_param_daemon_startup"])){$SquidClientParams["ksrn_param_daemon_startup"]=2;}
    if(!isset($SquidClientParams["ksrn_param_daemon_idle"])){$SquidClientParams["ksrn_param_daemon_idle"]=1;}

    if(!isset($SquidClientParams["ksrn_param_daemon_negative_ttl"])){$SquidClientParams["ksrn_param_daemon_negative_ttl"]=360;}
    if(!isset($SquidClientParams["ksrn_param_daemon_positive_ttl"])){$SquidClientParams["ksrn_param_daemon_positive_ttl"]=360;}

    if(!is_numeric($SquidClientParams["ksrn_param_daemon_children"])){$SquidClientParams["ksrn_param_daemon_children"]=10;}
    if(!is_numeric($SquidClientParams["ksrn_param_daemon_startup"])){$SquidClientParams["ksrn_param_daemon_startup"]=2;}
    if(!is_numeric($SquidClientParams["ksrn_param_daemon_idle"])){$SquidClientParams["ksrn_param_daemon_idle"]=1;}

    if(!is_numeric($SquidClientParams["ksrn_param_daemon_negative_ttl"])){$SquidClientParams["ksrn_param_daemon_negative_ttl"]=360;}
    if(!is_numeric($SquidClientParams["ksrn_param_daemon_positive_ttl"])){$SquidClientParams["ksrn_param_daemon_positive_ttl"]=360;}

    if(!isset($SquidClientParams["ksrn_param_daemon_concurrency"])){$SquidClientParams["ksrn_param_daemon_concurrency"]=500;}
    if($SquidClientParams["ksrn_param_daemon_concurrency"]<100){$SquidClientParams["ksrn_param_daemon_concurrency"]=100;}


    $start_up[1]=1;
    $start_up[2]=2;
    $start_up[3]=3;
    $start_up[4]=4;
    $start_up[5]=5;
    $start_up[10]=10;
    $start_up[15]=15;
    $start_up[20]=20;
    $start_up[25]=25;
    $start_up[30]=30;
    $start_up[35]=35;
    $start_up[40]=40;
    $start_up[45]=45;
    $start_up[50]=50;
    $start_up[55]=55;
    $start_up[60]=60;
    $start_up[65]=65;
    $start_up[70]=70;
    $start_up[80]=80;
    $start_up[85]=85;
    $start_up[90]=90;
    $start_up[100]=100;
    $start_up[150]=150;
    $start_up[200]=200;
    $start_up[300]=300;
    $start_up[400]=300;
    $start_up[500]=500;
    $start_up[600]=600;
    $start_up[700]=700;
    $start_up[800]=800;
    $start_up[900]=900;
    $start_up[1000]=1000;
    $start_up[1500]=1500;
    $ttl_interval[30]="30 {seconds}";
    $ttl_interval[60]="1 {minute}";
    $ttl_interval[300]="5 {minutes}";
    $ttl_interval[600]="10 {minutes}";
    $ttl_interval[900]="15 {minutes}";
    $ttl_interval[1800]="30 {minutes}";
    $ttl_interval[3600]="1 {hour}";
    $ttl_interval[7200]="2 {hours}";
    $ttl_interval[14400]="4 {hours}";
    $ttl_interval[18000]="5 {hours}";
    $ttl_interval[86400]="1 {day}";
    $ttl_interval[172800]="2 {days}";
    $ttl_interval[259200]="3 {days}";
    $ttl_interval[432000]="5 {days}";
    $ttl_interval[604800]="1 {week}";

    $KSRNRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemote"));
    $KSRNRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemoteAddr"));
    $KSRNRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemotePort"));
    $KSRNClientTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNClientTimeOut"));
    $KSRNClientCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNClientCacheTime"));
    $NetCoreSomaxConn=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("net.core.somaxconn"));
    $MacToUidPHP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidPHP"));
    $KSRNOnlyCategorization=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNOnlyCategorization"));


    $CategoriesCacheRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheRemote"));
    $CategoriesCacheRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheRemoteAddr"));
    $CategoriesCacheRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheRemotePort"));
    if($CategoriesCacheRemotePort==0){$CategoriesCacheRemotePort=2214;}
    $UfdbGuardWebFilteringCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardWebFilteringCacheTime"));
    if($UfdbGuardWebFilteringCacheTime==0){$UfdbGuardWebFilteringCacheTime=300;}
    //net.core.somaxconn=

    $ExternalAclFirstDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ExternalAclFirstDebug"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    if($KSRNRemotePort==0){$KSRNRemotePort=2004;}
    if($KSRNClientTimeOut==0){$KSRNClientTimeOut=5;}

    $jsRestart      = restart_js();
        $tpl->field_hidden("SavePost","yes");
    $form[]         = $tpl->field_section("{infrastructure}");
    $form[]         = $tpl->field_checkbox("ExternalAclFirstDebug","{debug}",$ExternalAclFirstDebug,false);
    if($MacToUidPHP==0) {
        $form[] = $tpl->field_checkbox("KSRNOnlyCategorization", "{KSRNOnlyCategorization}", $KSRNOnlyCategorization);
    }

    $form[]         = $tpl->field_numeric("NetCoreSomaxConn","{acl_maxconn}", $NetCoreSomaxConn);
    $form[]         = $tpl->field_numeric("UfdbGuardWebFilteringCacheTime","{ttl_cache_webfiltering} ({seconds})", $UfdbGuardWebFilteringCacheTime,"{ttl_cache_webfiltering_explain}");




    if($MacToUidPHP==0) {
        $form[]         = $tpl->field_section("{use_remote_appliance}");
        $form[] = $tpl->field_checkbox("KSRNRemote", "{enable}", $KSRNRemote, "KSRNRemoteAddr,KSRNRemotePort,KSRNClientTimeOut");
        $form[] = $tpl->field_text("KSRNRemoteAddr", "{remote_server_address}", $KSRNRemoteAddr);
        $form[] = $tpl->field_numeric("KSRNRemotePort", "{remote_server_port}", $KSRNRemotePort);
        $form[] = $tpl->field_numeric("KSRNClientTimeOut","{timeout} ({seconds})", $KSRNClientTimeOut);
    }

    $form[]         = $tpl->field_section("{APP_CATEGORIES_CACHE}");
    $form[]=$tpl->field_checkbox("CategoriesCacheRemote","{UseRemoteServer}",$CategoriesCacheRemote,"CategoriesCacheRemoteAddr,CategoriesCacheRemotePort");
    $form[]=$tpl->field_ipv4("CategoriesCacheRemoteAddr", "{remote_address}", $CategoriesCacheRemoteAddr);
    $form[]=$tpl->field_numeric("CategoriesCacheRemotePort","{remote_port}",$CategoriesCacheRemotePort);





    $form[]=$tpl->field_section("{local_database}");
    $TheShieldsUseLocalCats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));
    $form[]=$tpl->field_checkbox("TheShieldsUseLocalCats","{UseLocalDatabase}",$TheShieldsUseLocalCats,false,"{TheShieldsUseLocalCats}");

    if($KSRNClientCacheTime==0){$KSRNClientCacheTime=1800;}

    if($SQUIDEnable==1) {

        $form[] = $tpl->field_section("{performance}");
        if($MacToUidPHP==0) {
            $form[] = $tpl->field_array_hash($ttl_interval, "KSRNClientCacheTime", "{TTL_CACHE}", $KSRNClientCacheTime);
        }

        $form[] = $tpl->field_array_hash($start_up, "ksrn_param_daemon_children", "{CHILDREN_MAX}", $SquidClientParams["ksrn_param_daemon_children"]);

        $form[] = $tpl->field_numeric("ksrn_param_daemon_concurrency", "{CHILDREN_CONCURRENCY}", $SquidClientParams["ksrn_param_daemon_concurrency"]);



        $form[] = $tpl->field_array_hash($start_up, "ksrn_param_daemon_startup", "{CHILDREN_STARTUP}", $SquidClientParams["ksrn_param_daemon_startup"]);
        $form[] = $tpl->field_array_hash($start_up, "ksrn_param_daemon_idle", "{CHILDREN_IDLE}", $SquidClientParams["ksrn_param_daemon_idle"]);
        $form[] = $tpl->field_numeric("ksrn_param_daemon_positive_ttl", "{POSITIVE_CACHE_TTL} ({seconds})",
            $SquidClientParams["ksrn_param_daemon_positive_ttl"]);
        $form[] = $tpl->field_numeric("ksrn_param_daemon_negative_ttl", "{NEGATIVE_CACHE_TTL} ({seconds})",
            $SquidClientParams["ksrn_param_daemon_negative_ttl"]);
    }

    $priv           = "AsSystemAdministrator";
    $form_disabled  = false;
    $jsRestart      = restart_js();
    if($SQUIDEnable==0) {
            $form_disabled = true;
    }
    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsRestart,$priv,false,$form_disabled);


//restart_service_each
	$html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='ksrn-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("ksrn.php?restart-client=yes","ksrn.client.progress","ksrn.client.log","progress-ksrnclient-restart","LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes')");
    //LoadAjaxSilent('ksrn-status','fw.ksrn.client.php?ksrn-status=yes')
}

function emergency_enable(){
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEmergency", 1);
    admin_tracks("The SRN Emergency method was enabled");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?emergency=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";

}

function status_dns_firewall(){
    $tpl            = new template_admin();
    $EnableDNSFirewall=0;
    if($EnableDNSFirewall==0){
        echo $tpl->widget_grey("{APP_SQUID} {disabled}","{KSRN_CLIENT}");
        return false;
    }

    $KSRNRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemote"));
    $KSRNRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemoteAddr"));
    $KSRNRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemotePort"));
    if($KSRNRemote==1){
        $fp = fsockopen($KSRNRemoteAddr,$KSRNRemotePort,$errno,$errstr,1);
        if(!$fp){
            echo $tpl->widget_rouge($errstr."<br>$KSRNRemoteAddr:$KSRNRemotePort","{connection_error}");
            return false;
        }
        @fclose($fp);
    }

    echo $tpl->widget_vert("{APP_DNS_FIREWALL}","{active2}");
    return true;
}

function status(){
    $tpl            = new template_admin();
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $KSRNEmergency  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEmergency"));
    $jsRestart      = restart_js();
    $page=CurrentPageName();

    if($SQUIDEnable==0){return status_dns_firewall();}


    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?status=yes");
    $bsini = new Bs_IniHandler(PROGRESS_DIR."/ksrn.status");

    if($KSRNEmergency==1){
            $btn[0]["name"]="{disable_emergency_mode}";
            $btn[0]["icon"]=ico_play;
            $btn[0]["js"]=$jsRestart;
            echo $tpl->widget_rouge("{emergency_mode}","{emergency_mode}",$btn);
            return false;
        }

    $MacToUidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidUrgency"));

    if($MacToUidUrgency==1){
        $btn[0]["name"]="{disable_emergency_mode}";
        $btn[0]["icon"]=ico_play;
        $btn[0]["js"]="Loadjs('fw.proxy.emergency.MacToUid.php')";
        echo $tpl->widget_rouge("{proxy_in_MacToUid_emergency_mode}","{emergency_mode}",$btn);
        return false;

    }


    $KSRNRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemote"));
    $KSRNRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemoteAddr"));
    $KSRNRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemotePort"));
    if($KSRNRemote==1){
        $fp = fsockopen($KSRNRemoteAddr,$KSRNRemotePort,$errno,$errstr,1);
        if(!$fp){
            echo $tpl->widget_rouge($errstr."<br>$KSRNRemoteAddr:$KSRNRemotePort","{connection_error}");
            return false;
        }
        @fclose($fp);
    }

    $KsrnClientVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnClientVersion");
    $refresh="LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";
    echo $tpl->SERVICE_STATUS($bsini, "KSRN_CLIENT",restart_js(),$KsrnClientVersion,$refresh);

    $enable_emergency= $tpl->button_autnonome("{enable_emergency_mode}", "Loadjs('$page?emergency-enable=yes')", "fa fa-bell","AsProxyMonitor","335");
        echo $tpl->_ENGINE_parse_body("<center style='margin-top:10px'>$enable_emergency</center>");



    $download_logs= $tpl->button_autnonome("{logfile}", "Loadjs('$page?logfile-js=yes')", "fas fa-eye","AsProxyMonitor","335");
    echo    "
            <center style='margin-top:10px'>$download_logs</center>
            <script>
                LoadAjaxSilent('all-ksrn-versions','$page?all-ksrn-versions=yes');
            </script>
            ";
    return true;

}

function Save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $KSRNRemote=intval($_POST["KSRNRemote"]);
    $KSRNRemoteAddr=trim($_POST["KSRNRemoteAddr"]);
    $KSRNRemotePort=intval($_POST["KSRNRemotePort"]);
    $KSRNOnlyCategorization=intval($_POST["KSRNOnlyCategorization"]);
    if($KSRNOnlyCategorization==1){
        $KSRNRemote=0;
    }

    if($KSRNRemote==1){
        $fp = fsockopen($KSRNRemoteAddr,$KSRNRemotePort,$errno,$errstr,1);
        if(!$fp){
            echo "jserror:".$tpl->javascript_parse_text("$KSRNRemoteAddr:$KSRNRemotePort {connection_error}")."<br> $errstr";
            return false;
        }
    }
    $NetCoreSomaxConn=$_POST["NetCoreSomaxConn"];
    if($NetCoreSomaxConn>65535){$NetCoreSomaxConn=65535;}
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.core.somaxconn",$NetCoreSomaxConn);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");


	$tpl->SAVE_POSTs();
    $SquidClientParams=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRN_DAEMONS",$SquidClientParams);
    return true;
}
