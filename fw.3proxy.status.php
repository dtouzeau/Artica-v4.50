<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["PrivoxyPort"])){Save();exit;}
if(isset($_GET["status"])){service_status();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}
if(isset($_GET["3proxytop"])){top_status();exit;}
if(isset($_GET["3proxycenter"])){center_status();exit;}
if(isset($_GET["uninstall"])){uninstall_ask();exit;}
if(isset($_POST["uninstall"])){uninstall_confirm();exit;}

page();


function uninstall_ask():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Uninstall3Proxy=$tpl->framework_buildjs(
        "/3proxy/uninstall",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart",
        "document.location.href='/index'");

    echo $tpl->js_confirm_execute("{disable_feature}",
        "uninstall","{disable_feature}",$Uninstall3Proxy);
    return true;
}
function uninstall_confirm():bool{
    return admin_tracks("Uninstall Universal Proxy service..");
}

function ufdbdebug_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog1("{debug_mode}", "$page?ufdbdebug-popup=yes");

}
function ufdbconf_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_configuration}", "$page?ufdbconf-popup=yes");
	
}
function center_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!is_file("img/squid/3proxy-receiv-hourly.flat.png")){
        return true;
    }
    $rands=array("hourly","day","week","month","year");

    $random=rand(0,count($rands)-1);
    $suffix2=$rands[$random];
    $imgs=array("requests","receiv","sent");
    $t=time();
    foreach($imgs as $suffix){
        $href="Loadjs('fw.rrd.php?img=3proxy-$suffix');";

        if(is_file("img/squid/3proxy-$suffix-$suffix2.flat.png")){
            echo $tpl->td_href("<div style='padding:5px'><img src='img/squid/3proxy-$suffix-$suffix2.flat.png?t=$t' alt=''></div>","", $href);
        }
    }
    return true;

}
function top_status():bool{

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/3proxy/stats"));
    $tpl=new template_admin();

    if(!$json->Status){
        return false;
    }

    $html[]="<table style='width:100%;margin-left:5px;margin-top: -8px;'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>";
    if($json->Sent>0) {
        $Sent = FormatBytes($json->Sent / 1024);
        $html[] = $tpl->widget_h("green", ico_upload, $Sent, "{sent_traffic}");
    }else{
        $html[]= $tpl->widget_h("grey", ico_upload,
            "{none}","{sent_traffic}");
    }
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>";
    if($json->Received>0) {
        $Received = FormatBytes($json->Received / 1024);
        $html[]= $tpl->widget_h("green", ico_download,
            $Received,"{received_traffic}");
    }else{
        $html[]= $tpl->widget_h("grey", ico_download,
            "{none}","{received_traffic}");
    }

    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>";
    if($json->Requests>0) {
        $Requests = $tpl->FormatNumber($json->Requests);
        $html[] = $tpl->widget_h("green", ico_clouds, $Requests, "{requests}");
    }else{
        $html[]= $tpl->widget_h("grey", ico_download,"{none}","{requests}");
    }
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function ufdbconf_popup(){
	$tpl=new template_admin();
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
    $t=array();
	foreach ($f as $line){
		if(strlen($line)>86){
			$t[]=substr($line,0,86)."...";
			continue;
		}
		
		$t[]=$line;
	}
	
	$form=$tpl->field_textareacode("xxx", null, @implode("\n", $t));
	echo $tpl->form_outside(null, $form);
}



function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");

    $html=$tpl->page_header("{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {service_status}","fas fa-globe"
    ,"{APP_3PROXY_EXPLAIN}","$page?table=yes","universal-proxy-status","progress-3proxy-restart",false,"table-3proxy-status");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_3PROXY}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function service_status():bool{
    $tpl=new template_admin();
    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/3proxy/status");
    $page=CurrentPageName();
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart3proxy=$tpl->framework_buildjs(
        "/3proxy/restart",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart");

    echo $tpl->SERVICE_STATUS($ini, "APP_3PROXY",$jsrestart3proxy);

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/redsocks/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart3proxy=$tpl->framework_buildjs(
        "/redsocks/restart",
        "redsocks.progress",
        "redsocks.progress.log",
        "progress-3proxy-restart");

    echo $tpl->SERVICE_STATUS($ini, "APP_REDSOCKS",$jsrestart3proxy);

    $InstallRedSocks=$tpl->framework_buildjs(
        "/redsocks/install",
        "redsocks.install.progress",
        "redsocks.install.progress.log",
        "progress-3proxy-restart");


    $Reload3Proxy=$tpl->framework_buildjs(
        "/3proxy/reload",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart");

    $UninstallRedSocks=$tpl->framework_buildjs(
        "/redsocks/uninstall",
        "redsocks.install.progress",
        "redsocks.install.progress.log",
        "progress-3proxy-restart");

    $Uninstall3Proxy="Loadjs('$page?uninstall=yes');";



    $EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");
    if($EnableRedSocks==0) {
        $topbuttons[] = array($InstallRedSocks, ico_cd, "{APP_REDSOCKS} {install}");
    }else{
        $topbuttons[] = array($UninstallRedSocks, ico_trash, "{APP_REDSOCKS} {uninstall}");
    }
    $topbuttons[] = array($Reload3Proxy, ico_retweet, "{reload}");
    $topbuttons[] = array($Uninstall3Proxy, ico_trash, "{uninstall}");


    $TINY_ARRAY["TITLE"]="{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {service_status}";
    $TINY_ARRAY["ICO"]="fas fa-globe";
    $TINY_ARRAY["EXPL"]="{APP_3PROXY_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $page=CurrentPageName();
    echo "\n\n\n<script>$headsjs;
    LoadAjaxSilent('3proxytop','$page?3proxytop=yes');
    LoadAjaxSilent('3proxycenter','$page?3proxycenter=yes');
    </script>";
    return true;
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align: top'>";
	$html[]="<div id='3proxystatus'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]="<div id='3proxytop'></div>";
    $html[]="<div id='3proxycenter'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $js=$tpl->RefreshInterval_js("3proxystatus",$page,"status=yes",5);
    $html[]="<script>$js</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$_POST["zipproxy_MaxSize"]=$_POST["zipproxy_MaxSize"]*1024;
	
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	
}

function Main(){
    $tpl=new template_admin();
    $page=CurrentPageName();

}
