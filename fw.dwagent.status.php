<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["DWAgentKey"])){Save();exit;}
if(isset($_GET["dwservice-status"])){status();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_save();exit;}
if(isset($_GET["internal-form"])){internal_form();exit;}
if(isset($_GET["key-js"])){key_js();exit;}
if(isset($_GET["key-popup"])){key_popup();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_POST["uninstall"])){uninstall_confirm();exit;}
page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $ico="fad fa-laptop-house";

    $html=$tpl->page_header("{APP_DWAGENT}",$ico,
        "{APP_DWAGENT_EXPLAIN}","$page?table=yes",
        "dwservice-status",
    "progress-dwservice-restart",false,"table-loader-dwservice-pages");


	if(isset($_GET["main-page"])){
            $tpl=new template_admin(null,$html);
            echo $tpl->build_firewall();
            return true;
    }
	echo $tpl->_ENGINE_parse_body( $html);
    return true;
}

function reset_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{reset}","reset","yes","LoadAjax('table-loader-dwservice-pages','$page?table=yes');");
    return true;
}
function uninstall_js():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $restart=$tpl->framework_buildjs(
        "dwagent.php?uninstall=yes",
        "dwagent.progress",
        "dwagent.log",
        "progress-dwservice-restart",
        "document.location.href='/index';"

    );

    $tpl->js_confirm_delete("{APP_DWAGENT}", "uninstall","yes",$restart);
    return true;
}
function uninstall_confirm():bool{
    return admin_tracks("Uninstall DWService client...");
}

function reset_save(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentKey","xxxxxx");
}
function table_edit(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $DWAgentKey     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    $DWAgentProxy   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentProxy"));
    $json           = json_decode(@file_get_contents("/usr/share/dwagent/config.json"));
    $Enabled        = intval($json->enabled);

    if(!preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$DWAgentKey)) {
        $form[] = $tpl->field_text("DWAgentKey", "{key}", $DWAgentKey);
        $form[] = $tpl->field_checkbox("DWAgentProxy","{use_proxy_settings}",$DWAgentProxy);
        $jsRestart = rebuild_js();

        $myform = $tpl->form_outside("{parameters}", $form, null, "{apply}", $jsRestart, "AsSystemAdministrator");
    }else{
        $sform[]="<div style='margin-top:30px;'>";
        if($Enabled==0){
            $INFO= $tpl->widget_h("yellow", "fad fa-file-certificate", "{communication_error_server}", "{error}");
        }

        $sform[]="<table class='table' style='width: 70%'>";
        $sform[]="<tr>";
        $sform[]="<td style='width:1%' nowrap>{key}:</td>";
        $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap>$DWAgentKey</td>";
        $sform[]="</tr>";
        $sform[]="<tr>";
        $sform[]="<td style='width:1%' nowrap>{status}:</td>";
        if($Enabled==1){
            $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap><span class='label label-primary'>{active2}</span></td>";

        }else{
            $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap><span class='label label-danger'>{inactive2}</span></td>";
        }
        $sform[]="<tr>";
        $sform[]="<td style='width:1%' nowrap>{service}:</td>";
        $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap>$json->url_primary</td>";
        $sform[]="</tr>";

        $sform[]="<tr>";
        $sform[]="<td style='width:1%' nowrap>{use_proxy_settings}:</td>";

        if($DWAgentProxy==1){
            if(!property_exists($json,"proxy_host")){
                $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap><span class='label label-warning'>{inactive2}</span></td>";
            }else{
                $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap><span class='label label-primary'>{active2}</span></td>";
                $sform[]="<tr>";
                $sform[]="<td style='width:1%' nowrap>Proxy:</td>";
                $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap>$json->proxy_host:$json->proxy_port</td>";
                $sform[]="</tr>";

            }

        }else{
            $sform[]="<td style='width:99%;padding-left:10px;font-weight: bold' nowrap><span class='label label'>{disabled}</span></td>";
        }

        if($Enabled==0){
            $bton=$tpl->button_autnonome("{reset}",
                "Loadjs('$page?reset-js=yes');",
                "fad fa-power-off","AsSystemAdministrator",0,"btn-warning");
            $sform[]="<tr><td style='width:99%;padding-left:10px;font-weight: bold;text-align: right' colspan='2'>$bton</td></tr>";
        }


        $sform[]="</tr>";
        $sform[]="</table>";
        $myform=@implode("",$sform);

    }


    $html="<table style='width:100%'>
	<td style='vertical-align:top;width:240px'><div id='dwservice-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%;padding-left:20px'><div>$INFO</div>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('dwservice-status','$page?dwservice-status=yes');</script>
	";
    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $tpl            = new template_admin();
	$page           = CurrentPageName();
	$DWAgentKey     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    $json           = json_decode(@file_get_contents("/usr/share/dwagent/config.json"));
    $Enabled        = intval($json->enabled);

	if(preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$DWAgentKey)) {
        if($Enabled==0){
            $INFO= $tpl->widget_h("yellow", "fad fa-file-certificate", "{communication_error_server}", "{error}");
        }
    }


	$html="<table style='width:100%'>
	<td style='vertical-align:top;width:240px'><div id='dwservice-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%;padding-left:20px'><div>$INFO</div>
	    <div id='internal-form'></div>
	</td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('dwservice-status','$page?dwservice-status=yes');
	    LoadAjaxSilent('internal-form','$page?internal-form=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function key_js():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $tpl->js_dialog1("{connection}","$page?key-popup=yes");
    return true;
}
function key_popup():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $DWAgentKey     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    $DWAgentProxy   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentProxy"));
    $form[] = $tpl->field_text("DWAgentKey", "{key}", $DWAgentKey);
    $form[] = $tpl->field_checkbox("DWAgentProxy","{use_proxy_settings}",$DWAgentProxy);
    $jsRestart = rebuild_js();
    echo $tpl->form_outside("", $form, null, "{apply}",
        "dialogInstance1.close();$jsRestart", "AsSystemAdministrator");
    return true;

}
function internal_form():bool{

    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $DWAgentKey     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    $DWAgentProxy   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentProxy"));
    $json           = json_decode(@file_get_contents("/usr/share/dwagent/config.json"));
    $Enabled        = intval($json->enabled);


    $tpl->table_form_field_js("s_PopUp('https://www.dwservice.net/','1024','768')");
    $tpl->table_form_field_info("DWService","www.dwservice.net",ico_link);

    $tpl->table_form_field_js("Loadjs('$page?key-js=yes')");
    if(!preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$DWAgentKey)) {
        $tpl->table_form_field_info("{status}","{not_connected}",ico_engine_warning);
        $tpl->table_form_field_info("{key}","{not_set}",ico_key);
        $tpl->table_form_field_bool("{use_proxy_settings}",$DWAgentProxy,ico_params);
        echo $tpl->table_form_compile();
        return true;


    }
    $tpl->table_form_field_info("{key}",$DWAgentKey,ico_key);

    if($DWAgentProxy==1){
        if(!property_exists($json,"proxy_host")){
            $tpl->table_form_field_bool("{use_proxy_settings}","{inactive2}",ico_params);
        }else{
            $tpl->table_form_field_bool("{use_proxy_settings}","$json->proxy_host:$json->proxy_port",ico_params);
        }
    }
    else{
        $tpl->table_form_field_bool("{use_proxy_settings}",$DWAgentProxy,ico_params);
    }

    if($Enabled==1){
        $tpl->table_form_field_info("{status}","{active2}",ico_infoi);
    }else{
        $tpl->table_form_field_info("{status}","{inactive2}",ico_engine_warning);
    }
    echo $tpl->table_form_compile();

    if($Enabled==0){
        $topbuttons[] = array("Loadjs('$page?reset-js=yes');", ico_power_off, "{reset}");
    }

    $topbuttons[] = array("Loadjs('$page?uninstall-js=yes');",ico_trash,"{uninstall}");
    $topbuttons[] = array("LoadAjaxSilent('dwservice-status','$page?dwservice-status=yes');LoadAjaxSilent('internal-form','$page?internal-form=yes');",ico_refresh,"{refresh}");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_DWAGENT}";
    $TINY_ARRAY["ICO"]=ico_laptop_house;
    $TINY_ARRAY["EXPL"]="{APP_DWAGENT_EXPLAIN}";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$headsjs</script>";

return true;
}

function rebuild_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/dwagent.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dwagent.log";
    $ARRAY["CMD"]="dwagent.php?reconfigure=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-dwservice-pages','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-dwservice-restart')";
}
function restart_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/dwagent.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dwagent.log";
    $ARRAY["CMD"]="dwagent.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-dwservice-pages','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-dwservice-restart')";
}

function status(){
    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("dwagent.php?status=yes");
    $bsini=new Bs_IniHandler(PROGRESS_DIR."/dwagent.status");
    echo $tpl->SERVICE_STATUS($bsini, "APP_DWAGENT",$jsRestart);
}

function Save():bool{
	$tpl=new template_admin();
	if(!preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$_POST["DWAgentKey"])){
        echo $tpl->_ENGINE_parse_body("{wrong_value} {$_POST["DWAgentKey"]}");
        return false;
    }
    $tpl->SAVE_POSTs();
	return true;
}
