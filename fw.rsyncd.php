<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["RsyncInterface"])){save_config();exit;}
if(isset($_GET["rsyncd-status"])){ServiceStatus();exit;}
if(isset($_GET["table-flat"])){TableFlat();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup_popup();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $page=CurrentPageName();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_VERSION");

    $html=$tpl->page_header("{APP_RSYNC_SERVER} v$version &raquo;&raquo; {status}",
        ico_folder,
        "{APP_RSYNC_SERVER_EXPLAIN}",
        "$page?tabs=yes","rsyncd-config","progress-rsyncd-restart",false,"table-loader-rsynd-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: RustDesk status",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();


    $jsrestart=$tpl->framework_buildjs("/rsyncd/restart",
        "rsync.install.prg", "rsync.install.log",
        "progress-rsyncd-restart",
        "LoadAjax('table-loader-rsynd-service','$page?tabs=yes');");
	
	
	
	$html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td style='vertical-align:top;width:350px'><div id='rsynd-status'></div></td>
		<td style='vertical-align:top;'>><div id='rsynd-flat'></div>";
    $html[]="</td></tr></table>";

    $js=$tpl->RefreshInterval_js("rsynd-status",$page,"rsyncd-status=yes");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html))."<script>$js;LoadAjax('rsynd-flat','$page?table-flat=yes')</script>";
}

function tableFlat(){
    $RsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncInterface"));
    $RsyncPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncPort"));
    $RsyncMaxcnx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncMaxcnx"));
    $RsyncReverseLookup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncReverseLookup"));
    if($RsyncPort==0){$RsyncPort=873;}
    if($RsyncMaxcnx==0){$RsyncMaxcnx=20;}
    $tpl=new template_admin();
    $page=CurrentPageName();

    if($RsyncInterface==""){
        $RsyncInterface="{all}";
    }
    $tpl->table_form_field_js("Loadjs('$page?popup-js=yes')");
    $tpl->table_form_field_text("{listen_interface}","$RsyncInterface:$RsyncPort",ico_nic);
    $tpl->table_form_field_text("{max_connections}","$RsyncMaxcnx",ico_performance);
    $tpl->table_form_field_bool("{reverse_lookup}",$RsyncReverseLookup,ico_computer);
    echo $tpl->table_form_compile();
}

function popup_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{general_settings}","$page?popup=yes");
}

function popup_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsrestart="LoadAjax('rsynd-flat','$page?table-flat=yes');dialogInstance2.close();".$tpl->framework_buildjs("/rsyncd/restart",
        "rsync.install.prg", "rsync.install.log",
        "progress-rsyncd-restart",
        "");

    $RsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncInterface"));
    $RsyncPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncPort"));
    $RsyncMaxcnx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncMaxcnx"));
    $RsyncReverseLookup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncReverseLookup"));
    if($RsyncPort==0){$RsyncPort=873;}
    if($RsyncMaxcnx==0){$RsyncMaxcnx=20;}

    $form[]=$tpl->field_interfaces("RsyncInterface", "{listen_interface}", $RsyncInterface);
    $form[]=$tpl->field_numeric("RsyncPort","{listen_port}",$RsyncPort);
    $form[]=$tpl->field_numeric("RsyncMaxcnx","{max_connections}",$RsyncMaxcnx,"{RsyncMaxcnx}");
    $form[]=$tpl->field_numeric("RsyncReverseLookup","{reverse_lookup}",$RsyncReverseLookup,"{RsyncReverseLookup}");
    $html[]=$tpl->form_outside("",  $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function ServiceStatus():bool{
    $sock=new sockets();
    $data=$sock->REST_API("/rsyncd/status");
    $tpl=new template_admin();

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }

    if(!property_exists($json,"Info")){
        echo $tpl->widget_rouge("{error}","REST API ERROR");
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);


    $jsRestart=$tpl->framework_buildjs("/rsyncd/restart",
        "rsync.install.prg", "rsync.install.log",
        "progress-rsyncd-restart",
        "");


    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_RSYNC_SERVER",$jsRestart);
    echo $tpl->_ENGINE_parse_body($final);
    return true;
}

function save_config(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$tpl->SAVE_POSTs();
}


function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{status}"]="$page?status=yes";
	//$array["{events}"]="fw.sshd.events.php";

	
	echo $tpl->tabs_default($array);	
	
}