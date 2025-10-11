<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["static"])){config_static();exit;}
if(isset($_GET["add-virtual-port-js"])){virtual_port_add_js();exit;}
if(isset($_GET["virtual-port-popup"])){virtual_port_popup();exit;}
if(isset($_POST["ID"])){virtual_port_save();exit;}
page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_MANTICORE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_VERSION");

    $html=$tpl->page_header("{APP_MANTICORE} v$APP_MANTICORE_VERSION",ico_database,"{APP_MANTICORE_ABOUT}",
        "$page?tabs=yes","manticore","progress-manticore",false,"manticore-div");
	

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_MANTICORE} ",$html);
		echo $tpl->build_firewall();
		return true;
	}

	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{parameters}"]="$page?table=yes";
    echo $tpl->tabs_default($array);

}

function virtual_port_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MantiCoreListenPorts=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreListenPorts"));
    if(!is_array($MantiCoreListenPorts)){$MantiCoreListenPorts=array(0);}
    $ID=count($MantiCoreListenPorts);
    return $tpl->js_dialog2("{new_virtual_port} #$ID","$page?virtual-port-popup=$ID");
}

function virtual_port_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["virtual-port-popup"]);
    $MantiCoreListenPorts=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreListenPorts"));
    $btn="{apply}";
    $METHODS["mysql"]="{APP_MYSQL}";
    $METHODS["pgsql"]="{APP_POSTGRES}";
    $METHODS["mssql"]="Microsoft SQL database";
    $METHODS["odbc"]="ODBC Generic";
    
    if(!is_array($MantiCoreListenPorts)){$MantiCoreListenPorts=array(0);}
    if(!isset($MantiCoreListenPorts[$ID]["PORT"])){
        $MantiCoreListenPorts[$ID]["PORT"]=3306;
        $MantiCoreListenPorts[$ID]["MODE"]="mysql";
        $MantiCoreListenPorts[$ID]["INTERFACE"]="eth0";
        $btn="{add}";
    }
    $js_restart=js_restart();
    $security="AsDatabaseAdministrator";
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_interfaces("INTERFACE","{listen_interface}",$MantiCoreListenPorts[$ID]["INTERFACE"]);
    $form[]=$tpl->field_numeric("PORT","{listen_port}",$MantiCoreListenPorts[$ID]["PORT"]);
    $form[]=$tpl->field_array_hash($METHODS,"MODE","nonull:{method}",$MantiCoreListenPorts[$ID]["MODE"]);
    echo $tpl->form_outside("",$form,"",$btn,"dialogInstance2.close();$js_restart",$security);
    return true;
}
function virtual_port_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $MantiCoreListenPorts=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreListenPorts"));
    if(!is_array($MantiCoreListenPorts)){$MantiCoreListenPorts=array(0);}
    $MantiCoreListenPorts[$ID]=$_POST;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MantiCoreListenPorts",serialize($MantiCoreListenPorts));
    return admin_tracks_post("Create a new MantiCore virtual port");
}

function config_static():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $MantiCoreMySQLVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreMySQLVersion");
    $MantiCoreNetWorkers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreNetWorkers"));
    if($MantiCoreMySQLVersion==null){$MantiCoreMySQLVersion="5.0.37";}
    if($MantiCoreNetWorkers==0){$MantiCoreNetWorkers=1;}

    $tpl->table_form_field_text("{mysql version}",$MantiCoreMySQLVersion,ico_params);
    $tpl->table_form_field_text("{workers} ({network})",$MantiCoreNetWorkers,ico_timeout);
    $MantiCoreListenPorts=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreListenPorts"));
    if(is_array($MantiCoreListenPorts)){
        foreach ($MantiCoreListenPorts as $index=>$ligne){
           $interface=$ligne["INTERFACE"];
           if($interface==null){continue;}
           $prefix=$ligne["MODE"];
           $port=$ligne["PORT"];
            $tpl->table_form_field_text("{listen}","$prefix://$interface:$port",ico_interface);
        }

    }

    echo $tpl->table_form_compile();
    return true;
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$IPClass=new IP();

	
	$html[]="<table style='width:100%;margin-top:10px'>";
	$html[]="<tr>";
	$html[]="<td style='width:450px;vertical-align:top'>";
	$html[]="<div id='manticore-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";
    $html[]="<div id='manticore-static'></div>";
	$security="AsDatabaseAdministrator";
	$jsrestart=js_restart();

	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";

    $topbuttons[] = array("Loadjs('$page?add-virtual-port-js=yes')", ico_interface, "{new_virtual_port}");


    $APP_MANTICORE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_MANTICORE} v$APP_MANTICORE_VERSION";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{APP_MANTICORE_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="<script>";
    $html[]="LoadAjaxTiny('manticore-status','$page?status=yes');";
    $html[]="LoadAjaxTiny('manticore-static','$page?static=yes');";
    $html[]="$headsjs";
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
}

function status(){
    $statusfile     = PROGRESS_DIR."/manticore.status";
	$tpl=new template_admin();
	$page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("manticore.php?status=yes");
	$ini=new Bs_IniHandler($statusfile);
	echo $tpl->SERVICE_STATUS($ini, "APP_MANTICORE",js_restart());
}


function js_restart():string{
	$page=CurrentPageName();
    $tpl=new template_admin();
    return "LoadAjaxTiny('manticore-static','$page?static=yes');".$tpl->framework_buildjs(
        "manticore.php?restart=yes","manticore.progress",
        "manticore.log","progress-manticore",
        "LoadAjaxTiny('manticore-status','$page?status=yes');LoadAjaxTiny('manticore-static','$page?static=yes');");

}

