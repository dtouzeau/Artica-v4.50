<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_POST["configfile"])){config_file_save();exit;}
if(isset($_GET["table-main"])){table_main();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_PHP_REVERSE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PHP_REVERSE_VERSION");

    $html=$tpl->page_header("{APP_PHP_REVERSE} v$APP_PHP_REVERSE_VERSION","fa-brands fa-php","{APP_PHP_REVERSE_ABOUT}","$page?table=yes","phpreverse",
        "progress-phpreverse-restart",false,"table-loader-phpreverse-service");
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_PHP_REVERSE} v$APP_PHP_REVERSE_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table():bool{
	$tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:220px;vertical-align:top'><div id='phpreverse-service'></div></td>";
    $html[]="<td style='width:78%;vertical-align:top'>";
    $html[]="<div id='phpreverse-table'>";
	$html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('phpreverse-service','$page?status=yes');";
    $html[]="LoadAjax('phpreverse-table','$page?table-main=yes');";
    $html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function table_main(){
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();

    $html[]="<table class='table table-striped' style='width:70%'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>{instance}</th>";
    $html[]="<th>{enabled}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    $results=$q->QUERY_SQL("SELECT * FROM phpreverse");
    $TRCLASS=null;

    //instancename text NOT NULL DEFAULT '',
    //	enabled INTEGER NOT NULL DEFAULT 1,
    //	pm TEXT NOT NULL default 'dynamic',
    //    max_children INTEGER NOT NULL DEFAULT 35,
    //    start_servers INTEGER NOT NULL DEFAULT 2,
    //    min_spare_servers INTEGER NOT NULL DEFAULT 1,
    //    max_spare_servers INTEGER NOT NULL DEFAULT 10,
    //    process_idle_timeout INTEGER NOT NULL DEFAULT 10,
    //    max_requests INTEGER NOT NULL DEFAULT 500,
    //    phpinivals TEXT NOT NULL DEFAULT '')`)

    foreach($results as $ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $instancename=$ligne["instancename"];
        $pm=$ligne["pm"];
        $max_children=$ligne["max_children"];
        $start_servers=$ligne["start_servers"];
        $min_spare_servers=$ligne["min_spare_servers"];
        $max_spare_servers=$ligne["max_spare_servers"];
        $process_idle_timeout=$ligne["process_idle_timeout"];
        $max_requests=$ligne["max_requests"];
        $phpinivals=$ligne["phpinivals"];

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap>$instancename</td>";
        $html[]="<td width=1% nowrap>{$zstatus[$ligne["block"]]}</td>";
        $html[]="<td width=1% nowrap>$dbuser</td>";
        $html[]="<td  width=1% nowrap>$userip</td>";
        $html[]="<td>$query<br><small>$reason</small></td>";
        $html[]="</tr>";
    }


}

function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();

    $data = $sock->REST_API("/phpreverse/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

	$btn_config=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')", "fas fa-file-code","AsSystemAdministrator",335);
	echo $tpl->SERVICE_STATUS($ini, "APP_PHP_REVERSE","").$tpl->_ENGINE_parse_body($btn_config);
	
	
}

function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{APP_DHCP} >> {config_file}", "$page?config-file-popup=yes",1000);

}
function config_file_save(){

	$data=url_decode_special_tool($_POST["configfile"]);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config", $data);
}

function config_file_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("dhcpd.php?config-file=yes");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config");
	$form[]=$tpl->field_textareacode("configfile", null, $data);


	

	echo $tpl->form_outside("{config_file}", @implode("", $form),null,"{apply}","","AsSystemAdministrator");

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}