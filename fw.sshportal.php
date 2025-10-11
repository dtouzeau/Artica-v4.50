<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_POST["sshdportalInterface"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}

page();

function tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();


    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("sshd.php?sshportal-chown=yes");
    $array["{status}"]="$page?status=yes";
    $array["{rules}"]="fw.sshportal.acls.php?start=yes";
    $array["{remote_hosts}"]="fw.sshportal.hosts.php?start=yes";
    $array["{members}"]="fw.sshportal.users.php?start=yes";
    $array["{hosts_groups}"]="fw.sshportal.hostsgroups.php?start=yes";
    $array["{members_groups}"]="fw.sshportal.groups.php?start=yes";
    $array["{sessions}"]="fw.sshportal.events.php?start=yes";
    echo $tpl->tabs_default($array);

}
function Save(){
    $tpl        = new template_admin();
    $tpl->SAVE_POSTs();
}

function page(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $APP_SSHPORTAL_VERSION  =   $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SSHPORTAL_VERSION");


    $html=$tpl->page_header("{APP_SSHPORTAL} $APP_SSHPORTAL_VERSION",
        ico_terminal,"{APP_SSHPORTAL_EXPLAIN}","$page?tabs=yes",
            "sshportal","progress-sshdportal-restart",false,"table-loader-sshdportal-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SSHPORTAL}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}
function status(){

    $tpl                = new template_admin();
    $tpl->CLUSTER_CLI   = True;
    $page               = CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('sshd.php?sshportal-status=yes');
    $ini                = new Bs_IniHandler(PROGRESS_DIR."/sshportal.status");
    $sshdportalPort     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalPort"));
    $sshdportalInterface= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalInterface"));
    $sshdportalTimeOut  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalTimeOut"));
    $sshportalGeoIP     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshportalGeoIP"));
    $SSHPortalDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHPortalDenyCountries")));
    $sshportalLimitIP   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshportalLimitIP"));
    $sshportalDebug     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshportalDebug"));

    if($sshdportalPort==0){ $sshdportalPort=2222; }
    if(!is_array($SSHPortalDenyCountries)){$SSHPortalDenyCountries=array();}

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/sshportal.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/sshportal.log";
    $ARRAY["CMD"]="sshd.php?sshportal-restart=yes";
    $ARRAY["TITLE"]="{restart_service}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sshdportal-service','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-sshdportal-restart')";

    $html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td valign='top' style='width:350px'><center>". $tpl->SERVICE_STATUS($ini, "APP_SSHPORTAL",$jsrestart)."</center></td>
		<td valign='top'>";

    $form[]=$tpl->field_interfaces("sshdportalInterface", "nooloop:{listen_interface}", $sshdportalInterface);
    $form[]=$tpl->field_numeric("sshdportalPort","{listen_port}",$sshdportalPort);
    $form[]=$tpl->field_numeric("sshdportalTimeOut","{sessions_timeout} ({seconds})",$sshdportalTimeOut);
    $form[]=$tpl->field_checkbox("sshportalDebug","{debug_mode}",$sshportalDebug);

    $form[]=$tpl->field_section("{security}");
    $form[]=$tpl->field_checkbox("sshportalGeoIP","{enable_deny_countries}",$sshportalGeoIP);
    $form[]=$tpl->td_button("{deny_countries}", "{manage}", "Loadjs('fw.sshportal.countries.php');","<span id='dashboard-sshportal-countries'>".count($SSHPortalDenyCountries)."</span> {items}");

    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $ngx_stream_access_module=$q->COUNT_ROWS("ngx_stream_access_module")." {items}";
    $form[]=$tpl->field_checkbox("sshportalLimitIP","{enable_limit_access}",$sshportalLimitIP);
    $form[]=$tpl->td_button("{limit_access}", "{manage}", "Loadjs('fw.sshportal.ngx_stream_access_module.php');","<span id='CountOfStreamAccessModule'>$ngx_stream_access_module</span>");


    $html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
    $html[]="</td></tr></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

