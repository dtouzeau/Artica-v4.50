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
if(isset($_GET["ksrn-settings"])){main_settings();exit;}
if(isset($_GET["section-shields-js"])){section_shields_js();exit;}

page();

function clean_cache(){

    return true;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{reputation_services} &laquo;The Shields&raquo;",
        "fa fa-shield","{the_shields_explain}",
        "$page?table=yes",
        "the-shields-service",
        "progress-ksrn-restart",false,
        "table-loader-ksrn-pages");



	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}

function logfile_js(){

    $tpl=new template_admin();
    echo $tpl->framework_buildjs("ksrn.php?log-file=yes","ksrn.progress","ksrn.log",
        "progress-ksrn-restart","document.location.href='/ressources/logs/web/ksrn.log.gz';");

}

function table():bool{
    $page=CurrentPageName();
    echo "<div id='ksrn-settings'></div>
    <script>LoadAjaxSilent('ksrn-settings','$page?ksrn-settings=yes')</script>";
    return true;

}


function section_shields(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $priv           = "AsSystemAdministrator";
    $KsrnPornEnable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnPornEnable"));
    $KsrnMixedAdultEnable   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnMixedAdultEnable"));
    $KsrnHatredEnable       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnHatredEnable"));
    $KsrnQueryIPAddr        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnQueryIPAddr"));

    $KsrnQueryUseBackup     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnQueryUseBackup"));
    $KsrnDisableAdverstising= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableAdverstising"));
    $KsrnDisableGoogleAdServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableGoogleAdServices"));

    if($KsrnDisableAdverstising==0){
        $KsrnEnableAdverstising=1;
    }else{
        $KsrnEnableAdverstising=0;
    }

    $form[]         = $tpl->field_section("Threats Shield","{threats_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnAllEnable","{KsrnAllEnable}",1,false,"{KsrnAllEnable}",true);

    $form[]         = $tpl->field_section("Privacy Shield","{privacy_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnDisableGoogleAdServices","{allow}: Google Ad Services",$KsrnDisableGoogleAdServices);
    $form[]         = $tpl->field_checkbox("KsrnEnableAdverstising","{KsrnEnableAdverstising}",$KsrnEnableAdverstising);

    $form[]         = $tpl->field_section("Inappropriate Shield","{inappropriate_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnPornEnable","{KsrnPornEnable}",$KsrnPornEnable);
    $form[]         = $tpl->field_checkbox("KsrnMixedAdultEnable","{KsrnMixedAdultEnable}",$KsrnMixedAdultEnable);
    $form[]         = $tpl->field_checkbox("KsrnHatredEnable","{KsrnHatredEnable}",$KsrnHatredEnable);

    $jsRestart ="Loadjs('fw.go.shield.server.php?restart-go-shield-server=yes')";
    echo  $tpl->form_outside(null, $form,null,"{apply}",$jsRestart,$priv);
    return true;

}

function table_old(){


	$tpl=new template_admin();
	$page=CurrentPageName();


    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    if($KSRNEnable==0){
        echo $tpl->div_warning("{ksrn_error_disabled}");
        return true;
    }


	$libmem                 = new lib_memcached();
	$KRSN_DEBUG             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KRSN_DEBUG"));
	$KsrnPornEnable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnPornEnable"));
    $KsrnMixedAdultEnable   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnMixedAdultEnable"));
    $KsrnHatredEnable       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnHatredEnable"));
    $KsrnQueryIPAddr        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnQueryIPAddr"));

    $KsrnQueryUseBackup     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnQueryUseBackup"));
    $KsrnDisableAdverstising= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableAdverstising"));





	$kInfos         = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}

    $KsrnDisableGoogleAdServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableGoogleAdServices"));
    $KSRNCategoryWhite=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNCategoryWhite"));


    $form[]         = $tpl->field_section("Threats Shield","{threats_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnAllEnable","{KsrnAllEnable}",1,false,"{KsrnAllEnable}",true);

    $form[]         = $tpl->field_section("Privacy Shield","{privacy_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnDisableGoogleAdServices","{allow}: Google Ad Services",$KsrnDisableGoogleAdServices);
    $form[]         = $tpl->field_checkbox("KsrnEnableAdverstising","{KsrnEnableAdverstising}",$KsrnEnableAdverstising);

    $form[]         = $tpl->field_section("Inappropriate Shield","{inappropriate_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnPornEnable","{KsrnPornEnable}",$KsrnPornEnable);
    $form[]         = $tpl->field_checkbox("KsrnMixedAdultEnable","{KsrnMixedAdultEnable}",$KsrnMixedAdultEnable);
    $form[]         = $tpl->field_checkbox("KsrnHatredEnable","{KsrnHatredEnable}",$KsrnHatredEnable);


    $form[]         = $tpl->field_section("{general_parameters}");


    $SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
    $KSRNAsACls      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNAsACls"));
    $form[]=$tpl->field_checkbox("TheShieldsCguard","{TheShieldsCguard}",$TheShieldsCguard,false,"{TheShieldsCguard_explain}");

    $form[]=$tpl->field_categories_list("KSRNCategoryWhite","{global_whitelists} ({category})",$KSRNCategoryWhite);

    if($SQUIDACLsEnabled==0){
        $form[]=$tpl->field_checkbox("KSRNAsACls","{act_as_acl_module}",0,false,"{act_as_acl_module_explain}",true);
    }else{
        $form[]=$tpl->field_checkbox("KSRNAsACls","{act_as_acl_module}",$KSRNAsACls,false,"{act_as_acl_module_explain}");
    }

    $form[]=$tpl->field_checkbox("KRSN_DEBUG","{debug}",$KRSN_DEBUG,false);
    $form[]         = $tpl->field_checkbox("KsrnQueryIPAddr","{KsrnQueryIPAddr}",$KsrnQueryIPAddr);


    $priv           = "AsSystemAdministrator";
    $jsRestart ="Loadjs('fw.go.shield.server.php?restart-go-shield-server=yes')";
    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsRestart,$priv);


//restart_service_each
	$html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='ksrn-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>
	    <div id='ksrn-form-server'></div>$myform</td>
	</tr>
	</table>
	";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
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



function status(){
    $tpl            = new template_admin();

    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?status=yes");
    $bsini = new Bs_IniHandler(PROGRESS_DIR."/ksrn.status");

    VERBOSE("ksrn_sockets(STATS)",__LINE__);
    $array=$GLOBALS["CLASS_SOCKETS"]->ksrn_sockets("STATS");

    if(!$array["STATUS"]){
        echo $tpl->widget_rouge($array["ERROR"],"{connection_error}");
        return false;
    }else {

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
                        <h5>{KSRN_SERVER} v$VERSION: {cache}</h5>
                    </div>
                    <div class=\"ibox-content\">
                        <h1 class=\"no-margins\">$THE_SHIELD_CACHE {items}</h1>
                    </div>
                  
                    
                    ";
    }


    $jsRestart ="Loadjs('fw.go.shield.server.php?restart-go-shield-server=yes')";
    echo $tpl->SERVICE_STATUS($bsini, "KSRN_SERVER", $jsRestart);
    $krsn_src=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_SRC"));
    $krsn_dst=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_DST"));


    if($krsn_src<>$krsn_dst){
        $btn[0]["name"]="{fix_it}";
        $btn[0]["icon"]=ico_play;
        $btn[0]["js"]=$jsRestart;
        echo $tpl->widget_jaune("{need_update_ksrn}","{update2}",$btn);
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



}
