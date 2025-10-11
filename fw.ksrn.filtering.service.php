<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["KRSN_DEBUG"])){Save();exit;}
if(isset($_GET["ksrn-status"])){status();exit;}
if(isset($_GET["emergency-enable"])){emergency_enable();exit;}
if(isset($_GET["logfile-js"])){logfile_js();exit;}
if(isset($_GET["ksrn-form-server"])){ksrn_form_server();exit;}
if(isset($_POST["TheShieldsInterface"])){ksrn_form_server_save();exit;}
if(isset($_GET["emergency-disable"])){emergency_disable();exit;}
if(isset($_GET["clean-cache"])){clean_cache();exit;}

page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $libmem=new lib_memcached();
	$KRSN_VERSION=trim($libmem->getKey("KSRN_VERSION"));
	VERBOSE("KSRN_VERSION = [$KRSN_VERSION]",__LINE__);
	if($KRSN_VERSION<>null){$KRSN_VERSION=" v$KRSN_VERSION";}

// <i class="fas fa-cog"></i>
	$html[]="
    
	<div class=\"row border-bottom white-bg dashboard-header\">
	<table style='width:100%'>
	    <tr>
	        <td valign='top' style='padding-right: 10px' nowrap><i class='fa-8x fad fa-compress-arrows-alt'></i></td>
	        <td valign='top' style='width:99%'><h1 class=ng-binding>{service_parameters}{$KRSN_VERSION} </h1></td>
        </tr>
	
    </table>
		
	</div>
	<div class='row'>
	<div id='progress-ksrn-restart'></div>";
$html[]="</div><div class='row'><div class='ibox-content'>";
	$html[]="
	<div id='table-loader-ksrn-pages'></div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/filtering-service');
	LoadAjax('table-loader-ksrn-pages','$page?table=yes');
	</script>";

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,@implode("\n",$html));echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function ksrn_form_server():bool {
    $tpl=new template_admin();
    $page=CurrentPageName();
    $TheShieldsInterface = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsInterface");
    $TheShieldsPORT = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsPORT"));
    $TheShieldDebug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldDebug"));
    $KSRNDns1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNDns1"));
    $KSRNDns2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNDns2"));
    if ($TheShieldsInterface==null){$TheShieldsInterface="lo";}
    if($TheShieldsPORT==0){$TheShieldsPORT=2004;}

    $TheShieldMaxItemsInMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldMaxItemsInMemory"));
    $TheShieldLogDNSQ=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldLogDNSQ"));
    $TheShieldServiceCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldServiceCacheTime"));
    $KSRNServerTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNServerTimeOut"));
    $TheShieldsThreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsThreads"));
    $TheShieldsMaxServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsMaxServers"));

    $TheShieldsPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsPurge"));
    $TheShieldsServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsServiceEnabled"));
    $TheShieldsUseLocalCats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));


    if($TheShieldServiceCacheTime==0){$TheShieldServiceCacheTime=84600;}
    if($TheShieldMaxItemsInMemory==0){$TheShieldMaxItemsInMemory=20000;}
    if($KSRNServerTimeOut==0){$KSRNServerTimeOut=5;}
    if($TheShieldsThreads<5){$TheShieldsThreads=5;}
    if($TheShieldsMaxServers<1){$TheShieldsMaxServers=2;}


    $form[]=$tpl->field_section("{icapserver_1}","{service_remote_explain}");
    $form[]=$tpl->field_checkbox("TheShieldsServiceEnabled","{enable_service}",$TheShieldsServiceEnabled,"TheShieldsInterface,TheShieldsPORT,TheShieldDebug,TheShieldsThreads,TheShieldsBackLog",null);
    $form[]=$tpl->field_checkbox("TheShieldDebug","{debug}",$TheShieldDebug,false,null);
    $form[]=$tpl->field_interfaces("TheShieldsInterface","{listen_interface}",$TheShieldsInterface);
    $form[]=$tpl->field_numeric("TheShieldsPORT","{listen_port}",$TheShieldsPORT);

    $form[]=$tpl->field_section("{dns_resolution}");

    $form[]=$tpl->field_checkbox("TheShieldsUseLocalCats","{UseLocalDatabase}",$TheShieldsUseLocalCats,false,"{TheShieldsUseLocalCats}");
    $form[]=$tpl->field_checkbox("TheShieldLogDNSQ","{log_queries} (DNS)",$TheShieldLogDNSQ,false,null);
    $form[]         = $tpl->field_numeric("KSRNServerTimeOut","DNS {timeout} ({seconds})", $KSRNServerTimeOut);
    $form[]=$tpl->field_ipv4("KSRNDns1", "{primary_dns}", $KSRNDns1, false,null,false);
    $form[]=$tpl->field_ipv4("KSRNDns2", "{secondary_dns}", $KSRNDns2, false,null,false);

    $form[]=$tpl->field_section("{performance}");
    $form[]=$tpl->field_numeric("TheShieldsThreads","{threads}",$TheShieldsThreads);

    $form[]=$tpl->field_numeric("TheShieldsMaxServers","{MaxServers}",$TheShieldsMaxServers,"{MaxServers_text}");


    $form[]=$tpl->field_section("{memory_caching}");

    $purge[0]="{daily}";
    $purge[1]="{weekly}";
    $purge[2]="{every} 12 {hours}";
    $purge[3]="{every} 6 {hours}";
    $purge[4]="{every} 3 {hours}";





    $form[]=$tpl->field_numeric("TheShieldMaxItemsInMemory", "{max_records_in_memory}", $TheShieldMaxItemsInMemory, false,null,false);
    $form[]=$tpl->field_numeric("TheShieldServiceCacheTime", "{max_records_time_memory} ({seconds})", $TheShieldServiceCacheTime, false,null,false);

    $form[]=$tpl->field_array_hash($purge,"TheShieldsPurge","{empty_cache}",$TheShieldsPurge);


    $priv           = "AsSystemAdministrator";
    $jsRestart      = restart_js();
    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsRestart,$priv);
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}
function ksrn_form_server_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRSN_DEBUG",$_POST["TheShieldDebug"]);
    admin_tracks_post("Saving Web-filtering engine settings");
    return true;
}

function logfile_js(){

    $tpl=new template_admin();
    echo $tpl->framework_buildjs("ksrn.php?log-file=yes","ksrn.progress","ksrn.log",
        "progress-ksrn-restart","document.location.href='/ressources/logs/web/ksrn.log.gz';");

}

function table(){
    $tpl=new template_admin();
	$page=CurrentPageName();




//restart_service_each
	$html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='ksrn-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>
	    <div id='ksrn-form-server'></div>
    </td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');
	    LoadAjaxSilent('ksrn-form-server','$page?ksrn-form-server=yes');    
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restart_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ksrn.restart";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ksrn.log";
    $ARRAY["CMD"]="ksrn.php?restart=yes";
    $ARRAY["TITLE"]="{KSRN} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrn-restart')";
}

function emergency_enable(){
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEmergency", 1);
    admin_tracks("The Shields Emergency method was enabled");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?emergency=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";

}
function emergency_disable(){
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEmergency", 0);
    admin_tracks("The Shields Emergency method was Disable");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?emergency-disable=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";

}

function local_databases_status():bool{
    $KSRN_PATTERNS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_PATTERNS"));
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $max=159;
    $current=$KSRN_PATTERNS[0];
    $percent=$current/$max;
    $percent=round($percent*100);
    $size=FormatBytes($KSRN_PATTERNS[1]/1024);
    if($percent>=100){
        $btn=null;
        echo $tpl->widget_vert("{databases} 100%",$size,$btn);
        return true;
    }
    if($percent>=70){
        $btn=null;
        echo $tpl->widget_jaune("{databases} {$percent}%",$size,$btn);
        return true;
    }
    if($percent>=0){
        $btn=null;
        echo $tpl->widget_rouge("{databases} {$percent}%",$size,$btn);
        return true;
    }
    return false;
}

function status(){
    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $page=CurrentPageName();
    $TheShieldsServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsServiceEnabled"));
    $TheShieldsUseLocalCats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?status=yes");
    $bsini = new Bs_IniHandler(PROGRESS_DIR."/ksrn.status");



    if($TheShieldsServiceEnabled==1) {
        VERBOSE("ksrn_sockets(STATS)", __LINE__);


        if (!$array["STATUS"]) {
            echo $tpl->widget_rouge($array["ERROR"], "{connection_error}");
            return false;
        } else {

            $response = $array["RESPONSE"];
            $main = unserialize($response);

            $MEMCACHE_KSRN = intval($main["MEMCACHE_KSRN"]);
            $main["THE_SHIELD_CACHE"] = $main["THE_SHIELD_CACHE"] + $MEMCACHE_KSRN + intval($main["CATEGORIES_CACHE"]);

            $THE_SHIELD_CACHE = $tpl->FormatNumber($main["THE_SHIELD_CACHE"]);
            $QUERIES = $tpl->FormatNumber($main["QUERIES"]);
            $HITS = $tpl->FormatNumber($main["HITS"]);
            $VERSION = $main["VERSION"];
            $prc = $HITS / $QUERIES;
            $prc = round($prc * 100, 2);
            if ($prc > 99) {
                $prc = 100;
            }

            $stats = "
                    <div class=\"ibox-title\">
                        <span class=\"label label-success pull-right\">$QUERIES {requests}</span>
                        <h5>{service} v$VERSION: {cache}</h5>
                    </div>
                    <div class=\"ibox-content\">
                        <h1 class=\"no-margins\">$THE_SHIELD_CACHE {items}</h1>
                    </div>
                  
                    
                    ";
        }
    }
    echo $tpl->SERVICE_STATUS($bsini, "KSRN_SERVER2", $jsRestart);
    $krsn_src=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_SRC"));
    $krsn_dst=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_DST"));

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($TheShieldsUseLocalCats==1){ local_databases_status();}

    if($SQUIDEnable==1) {
        if ($krsn_src <> $krsn_dst) {
            $btn[0]["name"] = "{fix_it}";
            $btn[0]["icon"] = ico_play;
            $btn[0]["js"] = $jsRestart;
            echo $tpl->widget_jaune("{need_update_ksrn}", "{update2}", $btn);
        }
    }



    $download_logs= $tpl->button_autnonome("{logfile}", "Loadjs('$page?logfile-js=yes')", "fas fa-eye","AsProxyMonitor","335");

    $jscache="Loadjs('$page?clean-cache=yes')";

    $disable_cache=$tpl->button_autnonome("{empty_cache}", $jscache, "fa fa-trash","AsProxyMonitor","335","btn-warning");

    $stats=$tpl->_ENGINE_parse_body($stats);
    echo    "
            <center style='margin-top:10px'>$download_logs</center>
            <div style='margin-top:10px'>$stats</div>
            <center style='margin-top:10px'>$disable_cache</center>
            
            
            
    <script>
        LoadAjaxSilent('all-ksrn-versions','fw.ksrn.client.php?all-ksrn-versions=yes');
    </script>";
    return true;

}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$KSRN_DAEMONS=base64_encode(serialize($_POST));

	if(intval($_POST["GoogleSafeEnable"])==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleSafeDisable",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleSafeDisable",0);
    }

	if(intval($_POST["CloudFlareSafeEnabgle"])==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CloudFlareSafeDisable",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CloudFlareSafeDisable",0);
    }

	if(intval($_POST["KsrnEnableAdverstising"])==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnDisableAdverstising",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnDisableAdverstising",0);
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnDisableGoogleAdServices",$_POST["KsrnDisableGoogleAdServices"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRSN_DEBUG",$_POST["KRSN_DEBUG"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleApiKey",$_POST["GoogleApiKey"]);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRSN_DEBUG",$_POST["KRSN_DEBUG"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRN_DAEMONS",$KSRN_DAEMONS);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnPornEnable",$_POST["KsrnPornEnable"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnQueryUseBackup",$_POST["KsrnQueryUseBackup"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnMixedAdultEnable",$_POST["KsrnMixedAdultEnable"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnHatredEnable",$_POST["KsrnHatredEnable"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNAsACls",$_POST["KSRNAsACls"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TheShieldsCguard",$_POST["TheShieldsCguard"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNCategoryWhite",$_POST["KSRNCategoryWhite"]);

    $tpl->SAVE_POSTs();

}
