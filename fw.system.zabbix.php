<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}

if(isset($_POST["ZabbixDebugLevel"])){Save();exit;}
if(isset($_POST["ZabbixOutGoingInterface"])){Save();exit;}
if(isset($_POST["ZabbixPassiveCheckEnabled"])){Save();exit;}
if(isset($_POST["ZabbixActiveCheckEnabled"])){Save();exit;}

if(isset($_GET["zabbix-status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}



if(isset($_GET["section-outgoing-js"])){section_outgoing_js();exit;}
if(isset($_GET["section-outgoing-popup"])){section_outgoing_popup();exit;}

if(isset($_GET["section-passive-js"])){section_passive_js();exit;}
if(isset($_GET["section-passive-popup"])){section_passive_popup();exit;}

if(isset($_GET["section-active-js"])){section_active_js();exit;}
if(isset($_GET["section-active-popup"])){section_active_popup();exit;}

if(isset($_GET["section-debug-js"])){section_debug_js();exit;}
if(isset($_GET["section-debug-popup"])){section_debug_popup();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZABBIX_AGENT_VERSION");

    $html=$tpl->page_header("{APP_ZABBIX_AGENT} v{$version}",ico_health_check,
        "{monitor_your_system}","$page?tabs=yes","zabbix","progress-zabbix-restart",false,"table-loader-zabbix-pages");

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}
function section_passive_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{passive_checks}","$page?section-passive-popup=yes");

}
function section_active_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{active_checks}","$page?section-active-popup=yes");

}
function section_outgoing_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{outgoing_interface}","$page?section-outgoing-popup=yes");

}
function section_outgoing_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ZabbixOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixOutGoingInterface"));

    $form[]=$tpl->field_interfaces("ZabbixOutGoingInterface","nooloop:{outgoing_interface}",$ZabbixOutGoingInterface);
    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_passive_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ZabbixInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixInterface"));
    $ZabbixPassiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixPassiveCheckEnabled"));
    $ZabbixListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixListenPort"));
    $ZabbixAgentServerIP=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixAgentServerIP"));
    if($ZabbixListenPort==0){$ZabbixListenPort="10050";}


    $form[]=$tpl->field_checkbox("ZabbixPassiveCheckEnabled","{enable}",$ZabbixPassiveCheckEnabled,true);
    $form[]=$tpl->field_interfaces("ZabbixInterface","nooloopNoDef:{listen_interface}",$ZabbixInterface);
    $form[]=$tpl->field_numeric("ZabbixListenPort","{listen_port}",$ZabbixListenPort);
    $form[]=$tpl->field_text("ZabbixAgentServerIP", "{ZabbixAgentServerIP}", $ZabbixAgentServerIP,false,"{ZabbixAgentServerIP_explain}");



    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function section_debug_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{log_level}","$page?section-debug-popup=yes");

}
function section_debug_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $ZabbixDebugLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixDebugLevel"));
    if($ZabbixDebugLevel==0){$ZabbixDebugLevel=3;}
    $LogLevels[1]="{critical_events}";
    $LogLevels[2]="{errors} {events}";
    $LogLevels[3]="{warning} {events}";
    $LogLevels[4]="{debug_mode}";
    $LogLevels[5]="{verbose_mode} + {debug_mode}";
    $form[]=$tpl->field_array_hash($LogLevels,"ZabbixDebugLevel","{log_level}",$ZabbixDebugLevel);

    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}



function section_active_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ZabbixActiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixActiveCheckEnabled"));
    $ServerActiveList=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ServerActiveList"));
    $ZabbixRefreshActiveChecks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixRefreshActiveChecks"));

    if($ZabbixRefreshActiveChecks==0){$ZabbixRefreshActiveChecks=120;}
    $form[]=$tpl->field_checkbox("ZabbixActiveCheckEnabled","{enable}",$ZabbixActiveCheckEnabled,true);
    $form[]=$tpl->field_text("ServerActiveList", "{ZabbixAgentServerIP}", $ServerActiveList,false,"{ZabbixAgentServerActiveExplain}");
    $form[]=$tpl->field_numeric("ZabbixRefreshActiveChecks", "{each} ({seconds})", $ZabbixRefreshActiveChecks);

    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function section_js_restart():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return     $tpl->framework_buildjs("zabbix.php?restart=yes",
        "restart-zabbix.progress","restart-zabbix.progress.log","progress-zabbix-restart","LoadAjaxSilent('zabbix-status','$page?zabbix-status=yes');");
}

function section_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsRestart=section_js_restart();
    return "BootstrapDialog1.close();LoadAjax('zabbix-general-table','$page?table1=yes');$jsRestart";
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}/{parameters}"]="$page?table=yes";
    $array["{events}"]="fw.system.zabbix.events.php";
    echo $tpl->tabs_default($array);
}

function table(){
    $page=CurrentPageName();
    echo "<div id='zabbix-general-table'></div><script>LoadAjax('zabbix-general-table','$page?table1=yes')</script>";
}

function table1(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();


    $ZabbixInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixInterface"));
    $ZabbixOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixOutGoingInterface"));
    $ZabbixPassiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixPassiveCheckEnabled"));
    $ZabbixListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixListenPort"));
    $ZabbixAgentServerIP=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixAgentServerIP"));
    $ZabbixActiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixActiveCheckEnabled"));
    $ServerActiveList=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ServerActiveList"));
    if($ZabbixListenPort==0){$ZabbixListenPort="10050";}
    $ZabbixRefreshActiveChecks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixRefreshActiveChecks"));
    if($ZabbixRefreshActiveChecks==0){$ZabbixRefreshActiveChecks=120;}

    $ZabbixDebugLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixDebugLevel"));
    if($ZabbixDebugLevel==0){$ZabbixDebugLevel=3;}
    $LogLevels[1]="{critical_events}";
    $LogLevels[2]="{errors} {events}";
    $LogLevels[3]="{warning} {events}";
    $LogLevels[4]="{debug_mode}";
    $LogLevels[5]="{verbose_mode} + {debug_mode}";



    $tpl->table_form_field_js("Loadjs('$page?section-debug-js=yes')");
    $tpl->table_form_field_text("{log_level}",$LogLevels[$ZabbixDebugLevel],ico_bug);
    $tpl->table_form_field_js("Loadjs('$page?section-outgoing-js=yes')");
    if($ZabbixOutGoingInterface==null){
        $tpl->table_form_field_bool("{outgoing_interface}",0,ico_interface);
    }else{
        $tpl->table_form_field_text("{outgoing_interface}",$ZabbixOutGoingInterface,ico_interface);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-passive-js=yes')");

    if($ZabbixPassiveCheckEnabled==0){
        $tpl->table_form_field_bool("{passive_checks}",0,ico_health_check);

    }else{
        $fleche="&nbsp;&nbsp;<i class='fa fa-arrow-right'></i>&nbsp;&nbsp;";
        $tpl->table_form_field_text("{passive_checks}","$ZabbixAgentServerIP$fleche$ZabbixInterface:$ZabbixListenPort",ico_health_check);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-active-js=yes')");



 if($ZabbixActiveCheckEnabled==0) {
     $tpl->table_form_field_bool("{active_checks}", 0, ico_health_check);
 }else{
     $fleche="&nbsp;&nbsp;<i class='fa fa-arrow-right'></i>&nbsp;&nbsp;";
     if($ZabbixRefreshActiveChecks<60){$ZabbixRefreshActiveChecks=60;}
     if($ZabbixRefreshActiveChecks>3600){$ZabbixRefreshActiveChecks=3600;}
     $tpl->table_form_field_text("{active_checks}","$fleche$ServerActiveList {each} $ZabbixRefreshActiveChecks {seconds}",ico_health_check);
 }


    if($ZabbixPassiveCheckEnabled==0){
        if($ZabbixActiveCheckEnabled==0){
            $error= $tpl->div_error("{err_active_passive}");
        }
    }

    $myform=$tpl->table_form_compile();


    $Interval=$tpl->RefreshInterval_js("zabbix-status",$page,"zabbix-status=yes",3);

//restart_service_each
    $html="<table style='width:100%;margin-top:15px'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='zabbix-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>$error$myform</td>
	</tr>
	</table>
	<script>$Interval</script>
	";
    echo $tpl->_ENGINE_parse_body($html);

}


function ZabbixStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/zabbix/status");
    $json=json_decode($data);
    $page       = CurrentPageName();
    $jsrestart=section_js_restart();

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($ini, "APP_ZABBIX_AGENT",$jsrestart);
}

function status():bool{
    include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
	$tpl=new template_admin();
	echo ZabbixStatus();
    echo "<div style='margin-top:10px'>&nbsp;</div>";
    $ZabbixInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixInterface"));
    $ZabbixPassiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixPassiveCheckEnabled"));
    $ZabbixListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixListenPort"));



    if($ZabbixPassiveCheckEnabled==1){
        $nic=new networking();
        if($ZabbixInterface==null){$ZabbixInterface="eth0";}
        $Local_interfaces=$nic->Local_interfaces(false,true);


        $ListenAddress=$Local_interfaces[$ZabbixInterface];
        if($ZabbixListenPort==0){$ZabbixListenPort="10050";}


        $fp=@fsockopen($ListenAddress, $ZabbixListenPort, $errno, $errstr, 2);
        if(!$fp){
            echo $tpl->widget_rouge("$ListenAddress:$ZabbixListenPort","$errstr",null,ico_nic);
            return true;
        }
        echo $tpl->widget_vert("$ListenAddress:$ZabbixListenPort","OK",null,ico_nic);
        fclose($fp);

    }

    return true;
}

function Save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();

}
