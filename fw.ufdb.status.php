<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ufdbconf"])){ufdbconf_js();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}
if(isset($_GET["line-status"])){line_status();exit;}
if(isset($_GET["ufdb-service-status"])){services_status();exit;}
page();

function ufdbdebug_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{debug_mode}", "$page?ufdbdebug-popup=yes");

}
function ufdbconf_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_configuration}", "$page?ufdbconf-popup=yes");
	
}
function isUfdbLinked(){
return true;
}


function ufdbdebug_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$UfdbDebugAll=intval(($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll")));
	
	if($UfdbDebugAll==1){$UfdbDebugAll=0;}else{$UfdbDebugAll=1;}
	$sock->SET_INFO("UfdbDebugAll", $UfdbDebugAll);
	
    $jsrestart=$tpl->framework_buildjs("/ufdb/compile",
        "dansguardian2.mainrules.progress",
        "dansguardian2.mainrules.progress.log",
        "progress-ufdbs-status",
        "dialogInstance1.close();LoadAjax('table-ufdbstatus','$page?table=yes');"
    );




    echo "<div class='row'><div id='progress-ufdbs-status'></div>
	<script>$jsrestart</script>		
	";
	
	
}


function ufdbconf_popup(){
	$tpl=new template_admin();
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	
	foreach ($f as $line){
		if(strlen($line)>86){
			$t[]=substr($line,0,86)."...";
			continue;
		}
		
		$t[]=$line;
	}
	
	$form=$tpl->field_textareacode("xxx", null, @implode("\n", $t));
	echo $tpl->form_outside(null, $form,null,null);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $explain="{ufdbgdb_explain}";
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $explain=$tpl->div_error("{warn_ufdbguard_no_license}");
    }
    $UFDBDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UFDBDaemonVersion');

    $html=$tpl->page_header("{APP_UFDBGUARD}  v$UFDBDaemonVersion",
        "fab fa-soundcloud",
        $explain,"$page?table=yes","webfiltering-status",
        "progress-ppcategories-restart",false,"table-ufdbstatus"
    );


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{APP_UFDBGUARD} &raquo;&raquo; {service_status}");
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function jstiny(){
    $tpl=new template_admin();
    $UFDBDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UFDBDaemonVersion');
    $explain="{ufdbgdb_explain}";
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $explain=$tpl->div_error("{warn_ufdbguard_no_license}");
    }

    $TINY_ARRAY["TITLE"]="{APP_UFDBGUARD}  v$UFDBDaemonVersion";
    $TINY_ARRAY["ICO"]="fab fa-soundcloud";
    $TINY_ARRAY["EXPL"]=$explain;
    $TINY_ARRAY["BUTTONS"]=top_buttons();
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function top_buttons(){
    $page=CurrentPageName();
    $tpl=new template_admin();



    $turnemergency=$tpl->framework_buildjs("/ufdbclient/emergency/on",
        "ufdb.urgency.disable.progress",
        "ufdb.urgency.disable.progress.txt",
        "progress-ppcategories-restart");


    //fa fa-bell-slash //<i class="far fa-sync-alt"></i>
    $SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));
    $UfdbDebugAll=intval(($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll")));

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    if($SquidUFDBUrgency==1) {
        $btns[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('fw.ufdb.emergency.remove.php');\">
	<i class='fa fa-bell-slash'></i> {disable_emergency_mode} </label>";
    }else{
        $btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"$turnemergency;\">
	<i class='fa fa-bell'></i> {urgency_mode} </label>";

    }

    $btns[] = "<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('table-ufdbstatus','$page?table=yes');\"><i class='far fa-sync-alt'></i> {refresh} </label>";

    if($UfdbDebugAll==0){
        $btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?ufdbdebug=yes');\">
	<i class='fa fa-bell'></i> {debug_on} </label>";

    }else{
        $btns[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?ufdbdebug=yes');\">
	<i class='fa fa-bell'></i> {debug_off} </label>";
    }

    $btns[] = "<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?ufdbconf=yes');\"><i class='fa fa-wrench'></i> {file_configuration} </label>";


    $jsrestart=$tpl->framework_buildjs("/ufdb/compile",
        "dansguardian2.mainrules.progress",
        "dansguardian2.mainrules.progress.log",
        "progress-ppcategories-restart",
        "LoadAjax('table-loader-ufdblight-service','fw.ufdb.settings.php?tabs=yes');"
    );
    $icvor=ico_retweet;
    $btns[] = "<label class=\"btn btn-warning\" OnClick=\"$jsrestart\"><i class='$icvor'></i> {build_web_filtering_rules} </label>";
    $btns[] = "</div>";

        return @implode("",$btns);
}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div id='ufdb-line-status'></div>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align:top'>";
    $html[]="<div id='ufdb-service-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='table-loader-ufdblight-service'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";

    $js=$tpl->RefreshInterval_js("ufdb-service-status",$page, "ufdb-service-status=yes");

    $html[]="LoadAjax('ufdb-line-status','$page?line-status=yes');";
    $html[]="$js";
    $html[]="LoadAjax('table-loader-ufdblight-service','fw.ufdb.settings.php?tabs=yes');";
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function line_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    $UfdbCountOfDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCountOfDatabases"));
    if(!$UfdbUsedDatabases){
        $UfdbUsedDatabases=array();
        $UfdbUsedDatabases["MISSING"]=array();
        $UfdbUsedDatabases["INSTALLED"]=array();
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbUsedDatabases",serialize($UfdbUsedDatabases));
    }
    if(!$UfdbCountOfDatabases){
        $UfdbCountOfDatabases=array();
        $UfdbCountOfDatabases["ART"]=0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbCountOfDatabases",serialize($UfdbCountOfDatabases));
    }
    if(!isset($UfdbUsedDatabases["MISSING"])){
        $UfdbUsedDatabases["MISSING"]=array();
    }


    $CountDeMissing = ($UfdbUsedDatabases["MISSING"] != NULL ? count($UfdbUsedDatabases["MISSING"]) : 0);
    $CountDeInstalled = ($UfdbUsedDatabases["INSTALLED"] != NULL ? count($UfdbUsedDatabases["INSTALLED"]) : 0);

    $SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));
    $SquidUFDBUrgencyLastEvents=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgencyLastEvents");
    $UfdUsersUsed=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdUsersUsed"));
    $isUfdbLinked=isUfdbLinked();
    $ART=intval($UfdbCountOfDatabases["ART"]);
    $html[]="<table style='padding:2px' width='100%'>";

    if($SquidUFDBUrgency==0) {
        if (!$isUfdbLinked) {
            $html[]="<td style='padding:2px' width='33%'>";
            $html[] = $tpl->widget_style1("red-bg", "fas fa-unlink", "{local_proxy_service_not_linked}", "!!");
            $html[]="</td>";
        }
    }

    if($SquidUFDBUrgency==1) {
        $html[]="<td style='padding:2px' width='33%'>";
        $html[] = $tpl->widget_style1("red-bg",ico_emergency,  "$SquidUFDBUrgencyLastEvents","{proxy_in_webfiltering_emergency_mode}");
        $html[]="</td>";
    }


    $html[]="<td style='padding:2px' width='33%'>";
    if($ART==0) {
        $html[] = $tpl->widget_style1("yellow-bg", "fas fa-database", "{artica_databases} {websites_categorized}:", "0");
    }else{
        $ART=FormatNumber($ART);
        $html[] = $tpl->widget_style1("navy-bg", "fas fa-database", "{artica_databases} {websites_categorized}:", "$ART");
    }
    $html[]="</td>";


    $html[]="<td style='padding:2px' width='33%'>";
    if(count($UfdbUsedDatabases["MISSING"])>0){
        $html[]=$tpl->widget_style1("red-bg","fas fa-engine-warning","{missing_databases}",$CountDeMissing);

    }else{

        if($CountDeInstalled==0) {
            $html[]=$tpl->widget_style1("yellow-bg", "fas fa-database", "{used_databases}", "{none}");

        }else{
            $html[]=$tpl->widget_style1("navy-bg", "fas fa-database", "{used_databases}", $CountDeInstalled);
        }

    }
    $html[]="</td>";

    if(isset($UfdUsersUsed["NUMBER_OF_USERS"])){
        if($UfdUsersUsed["NUMBER_OF_USERS"]==0){
            $html[]="<td style='padding:2px' width='33%'>";
            $html[]=$tpl->widget_style1("yellow-bg", "fas fa-users", "{filtered_users}", "{no_users_in_database}") ;
            $html[]="</td>";

        }else {
            $Users=FormatNumber($UfdUsersUsed["NUMBER_OF_USERS"]);
            $TTL=$UfdUsersUsed["TTL"];
            $TT2=$UfdUsersUsed["REFRESH"];
            $html[]="<td style='padding:2px' width='33%'>";
            $html[] =  $tpl->widget_style1("navy-bg", "fas fa-users", "{filtered_users}", "$Users {members}<div style='margin-top:-10px;'><span style='font-size:14px'>{ttl}: {$TTL}mn/{$TT2}mn</span></div>");
            $html[]="</td>";
        }


    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function services_status(){
    $tpl=new template_admin();

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/status");

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

    $jsrestart=$tpl->framework_buildjs(
        "/ufdb/restart",
        "ufdb.restart.progress",
        "ufdb.restart.log",
        "progress-ppcategories-restart");

    $jsrestart2=$tpl->framework_buildjs(
        "/ufdbclient/restart",
        "ufdb.client.restart.progress",
        "ufdb.client.restart.progress.log",
        "progress-ppcategories-restart");

    $html[]=$tpl->SERVICE_STATUS($ini, "APP_UFDBGUARD",$jsrestart);

    echo $tpl->_ENGINE_parse_body($html);
    echo "<script>".jstiny()."</script>";

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}