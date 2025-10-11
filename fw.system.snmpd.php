<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["SNMPDagentAddress"])){Save();exit;}
if(isset($_GET["snmpd-status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["supporttool"])){download_support_tool();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_VERSION");
    $html=$tpl->page_header(
        "{monitor_your_system}: SNMP v{$version}",
        " fas fa-comment-check",
        "{SNMPD_ABOUT}","$page?tabs=yes",
        "/snmpd","progress-snmpd-restart"

    );
	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}/{parameters}"]="$page?table=yes";
    $array["{events}"]="fw.system.snmpd.events.php";
    echo $tpl->tabs_default($array);
}

function download_support_tool(){
    $TargetFile = "/usr/share/artica-postfix/ressources/logs/snmpd-support.tar.gz";
    header('Content-type: application/gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"snmpd-support.tar.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($TargetFile);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($TargetFile);
    unlink($TargetFile);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
	$SNMPDCommunity=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDCommunity");
	if($SNMPDCommunity==null){$SNMPDCommunity="public";}
	$SNMPDNetwork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDNetwork");
	if($SNMPDNetwork==null){$SNMPDNetwork="default";}
	$SNMPDUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDUsername"));
	$SNMPDPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDPassword"));
	$SNMPDOrganization=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDOrganization"));
	$SNMPDContact=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDContact"));
	if($SNMPDOrganization==null){$SNMPDOrganization=$WizardSavedSettings["organization"];}
	if($SNMPDContact==null){$SNMPDContact=$WizardSavedSettings["mail"];}
	$SNMPDagentAddress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDagentAddress"));
	if($SNMPDagentAddress==0){$SNMPDagentAddress=161;}
    $SNMPDInterfaceAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDInterfaceAddress"));
    $EnableProxyInSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyInSNMPD"));
    $SNMPDDisablev2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDDisablev2"));
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $SNMPDPassphrase=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDPassphrase"));



    if($SQUIDEnable==1){
        $form[]=$tpl->field_checkbox("EnableProxyInSNMPD","{monitor_proxy_service} (SNMP)",$EnableProxyInSNMPD,false,"{monitor_proxy_service_snmpd_explain}");

    }

    $form[]=$tpl->field_interfaces("SNMPDInterfaceAddress","{listen_interface}",$SNMPDInterfaceAddress);
	$form[]=$tpl->field_text("SNMPDagentAddress","{listen_port}",$SNMPDagentAddress);
    $form[]=$tpl->field_text("SNMPDOrganization", "{organization}", $SNMPDOrganization);
    $form[]=$tpl->field_text("SNMPDContact", "{system_contact}", $SNMPDContact);

    $form[]=$tpl->field_section("SNMPv1 / SNMPv2c");
    $form[]=$tpl->field_checkbox("SNMPDDisablev2","{disable}",$SNMPDDisablev2);
	$form[]=$tpl->field_text("SNMPDCommunity", "{snmp_community} (SNMPv2c)", $SNMPDCommunity,false,"{field_ipaddr_cdir_comma}");
	$form[]=$tpl->field_text("SNMPDNetwork", "{remote_snmp_console_ip} (SNMPv2c)", $SNMPDNetwork);

    $form[]=$tpl->field_section("SNMPv3 SHA/AES/authPriv");
	$form[]=$tpl->field_text("SNMPDUsername","{username}",$SNMPDUsername);
	$form[]=$tpl->field_password2("SNMPDPassword", "{password}", $SNMPDPassword);
    $form[]=$tpl->field_text("SNMPDPassphrase","{passphrase}",$SNMPDPassphrase);




    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/restart-snmpd.progress.log";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/snmpd.service.progress";
    $ARRAY["CMD"]="/snmpd/restart";
    $ARRAY["TITLE"]="{APP_SNMPD} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('snmpd-status','$page?snmpd-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-snmpd-restart')";


    $supporttool=$tpl->framework_buildjs("/snmpd/support",
        "snmpd.service.progress","restart-snmpd.progress.log","progress-snmpd-restart",
        "window.location='$page?supporttool=yes';"
    );

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_VERSION");
    $TINY_ARRAY["TITLE"]="{monitor_your_system} SNMP v$version";
    $TINY_ARRAY["ICO"]="fas fa-comment-check";
    $TINY_ARRAY["EXPL"]="{SNMPD_ABOUT}";
    $topbuttons[] = array($supporttool, ico_support, "Support Tool");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	
	$myform=$tpl->form_outside("{parameters}", $form,null,"{apply}",$jsRestart,"AsSystemAdministrator");
    $Interval=$tpl->RefreshInterval_js("snmpd-status",$page,"snmpd-status=yes",3);
//restart_service_each
	$html="<table style='width:100%;margin-top:15px'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='snmpd-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>$Interval;$headsjs</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function status(){
	$sock=new sockets();
	$data=json_decode($sock->REST_API("/snmpd/status"));
	$bsini=new Bs_IniHandler();
    $bsini->loadString($data->Info);
	$tpl=new template_admin();
	$page=CurrentPageName();
	

	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/restart-snmpd.progress.log";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/snmpd.service.progress";
    $ARRAY["CMD"]="/snmpd/restart";
	$ARRAY["TITLE"]="{APP_SNMPD} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('snmpd-status','$page?snmpd-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-snmpd-restart')";
	echo $tpl->SERVICE_STATUS($bsini, "APP_SNMPD",$jsRestart);
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

    $SNMPDDisablev2=intval($_POST["SNMPDDisablev2"]);

    if($SNMPDDisablev2==1) {
        $lengh = strlen($_POST["SNMPDPassword"]);
        if ($lengh < 8) {
            echo "jserror:" . $tpl->javascript_parse_text("{password_too_short}: 8 {items}");
            return;
        }
    }


	$tpl->SAVE_POSTs();

}
