<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_REQUEST["GenerateProgress"])){GenerateProgress();exit;}
if(isset($_POST["connection_name"])){buildconfig();exit;}
page();




function page(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");


    $html=$tpl->page_header("{APP_OPENVPN} v.$OpenVPNVersion",
        "fas fa-link","{connections} ({events})","$page?main-start=yes",
        "openvpn-connections","progress-openvpn-restart",false,"table-connections");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function main_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&main=yes");
    return true;
}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();
    if(!isset($_GET["search"])){$_GET["search"]="";}
	$date=$tpl->javascript_parse_text("{connection_date}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$userid=$tpl->javascript_parse_text("{members}");
	$events=$tpl->javascript_parse_text("{events}");
	$Items_text=$tpl->_ENGINE_parse_body("{items}");

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{$ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{connection}/{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$events}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$_SESSION["OPENVPN_SEARCH2"]=$_GET["search"];
    $sql="SELECT * FROM openvpn_cnx ORDER BY zdate DESC LIMIT 250";
	if(strlen($_GET["search"])>1){
        $search="*{$_GET["search"]}*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM openvpn_cnx WHERE (action LIKE '$search' OR ipaddr LIKE '$search' OR uid LIKE '$search' ) ORDER BY zdate DESC LIMIT 250";
    }
	
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";}
	
	$array["update"]="fas fa-sync-alt";
	$array["add"]="fa-link";
	$array["del"]="fa-unlink";
    $array["delete"]="fa-unlink";
    $array["auth_success"]="fad fa-sign-in";
    $array["auth_failed"]="fad fa-ban";

	
	$arrayT["add"]=$tpl->_ENGINE_parse_body("{logging}");
	$arrayT["del"]=$tpl->_ENGINE_parse_body("{logoff}");
    $arrayT["delete"]=$tpl->_ENGINE_parse_body("{logoff}");
    $arrayT["auth_success"]=$tpl->_ENGINE_parse_body("{is_authenticated}");
    $arrayT["auth_failed"]=$tpl->_ENGINE_parse_body("{failed_login}");

$sepclabel="style='min-width: 104px;display: table-cell;padding: 6px;'";
    $IconActions["auth_failed"]="<span class='label label-danger' $sepclabel>{failed}</span>";
    $IconActions["auth_success"]="<span class='label label-primary' $sepclabel>{is_authenticated}</span>";
    $IconActions["add"]="<span class='label label-primary' $sepclabel>{connected}</span>";
    $IconActions["del"]="<span class='label label-warning' $sepclabel>{disconnected}</span>";
    $IconActions["delete"]="<span class='label label-warning' $sepclabel>{disconnected}</span>";

    $width1="style='vertical-align:middle;width:1%' class='center' nowrap";
    $widthL="style='vertical-align:middle;width:1%' class='left' nowrap";

	
	$tpl2=new templates();
	foreach($results as $index=>$ligne) {
        $uid=$ligne["uid"];
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$date=$tpl2->time_to_date($ligne["ztime"],true);
		$md=md5(serialize($ligne));
		$fuser="fa-user";
		if($ligne["uid"]==null){$ligne["uid"]="&nbsp;-&nbsp;";$fuser="fa-user-o";}
        if(!isset($arrayT[$ligne["action"]])){$arrayT[$ligne["action"]]=$ligne["action"];}
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $widthL>{$IconActions[$ligne["action"]]}</td>";
        $html[]="<td $widthL><span class='fa fa-clock'> </span>&nbsp;{$date}</td>";
		$html[]="<td $widthL><span class='fa fa-desktop' ></span>&nbsp;{$ligne["ipaddr"]}</td>";
		$html[]="<td nowrap><span class='fa $fuser' ></span>&nbsp;{$ligne["uid"]}</td>";
		$html[]="<td $widthL><span class='fa {$array[$ligne["action"]]}' ></span>&nbsp;{$arrayT[$ligne["action"]]}</td>";

		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $html[]="<script>";
	$html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": {\"enabled\": true } } ); });";
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
