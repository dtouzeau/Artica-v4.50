<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.builder.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["popup-new"])){popup_new();exit;}
if(isset($_GET["js-new"])){js_new();exit;}
if(isset($_POST["object-new"])){create_object();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{firewall_objects} ({link_object})", "$page?popup=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}",650);
}
function js_new(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{firewall_objects} ({new_object})", "$page?popup-new=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}",650);
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$ID=intval($_GET["ruleid"]);
	$direction=intval($_GET["direction"]);
	$btname="{link_object}";
	$title="{link_object}";
	
	$results=$q->QUERY_SQL("SELECT ID,GroupName,GroupType FROM webfilters_sqgroups ORDER BY GroupName");
	if(!$q->ok){echo $q->mysql_error_html(false);}
	$c=0;
	foreach ($results as $index=>$ligne){
		if(!isset($q->acl_GroupType_iptables[$ligne["GroupType"]])){continue;}
		$c++;
		$MAIN[$ligne["ID"]]=$tpl->_ENGINE_parse_body("{$ligne["GroupName"]}: {{$ligne["GroupType"]}}");
	
	}
	
	if($c==0){
		echo "<script>dialogInstance6.close();Loadjs('$page?js-new=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}')</script>";
		return;
	}
	
	$backjs=base64_decode($_GET["refresh-function"]);
	$tpl->field_hidden("object-link", $ID);
	if(isset($_GET["direction"])){ $tpl->field_hidden("direction", $direction);}
	$form[]=$tpl->field_array_hash($MAIN,"gpid","{object}",null,true);
	$tpl->form_add_button("{new_object}","Loadjs('$page?js-new=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}')");
	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs,"AsFirewallManager");
}

function popup_new(){
	$page=CurrentPageName();
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$title="{new_object}";
	$btname="{add}";
	$backjs="dialogInstance6.close();".base64_decode($_GET["refresh-function"]);
	
	$tpl->field_hidden("object-new", "yes");
	if(isset($_GET["direction"])){ $tpl->field_hidden("direction", $direction);}
	if(isset($_GET["table-link"])){ $tpl->field_hidden("table", $_GET["table-link"]);}
	if(isset($_GET["ruleid"])){ $tpl->field_hidden("ruleid", $_GET["ruleid"]);}
	$form[]=$tpl->field_text("GroupName","{groupname}","{new_group}");
	$form[]=$tpl->field_array_hash($q->acl_GroupType_iptables,"GroupType","{type}",null,true);
	$tpl->form_add_button("{cancel}","dialogInstance6.close()");
	$html=$tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs,"AsFirewallManager");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
	echo $html;
	
}

function create_object(){
	$direction=0;
	$table="firewallfilter_sqacllinks";
	$GroupName=url_decode_special_tool($_POST["GroupName"]);
	$GroupName=utf8_decode($GroupName);
	$GroupName=mysql_escape_string2($GroupName);
	$GroupType=$_POST["GroupType"];
    if(isset($_POST["table"])){$table=$_POST["table"];}
    if(isset($_POST["direction"])){$direction=$_POST["direction"];}
	
	$sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`)
	VALUES ('$GroupName','$GroupType','1','','','0',0);";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sqladd);
	if(!$q->ok){echo "jserror:$q->mysql_error Line:".__LINE__;return;}
	

	$aclid=intval($_POST["ruleid"]);
	$gpid=$q->last_id;

	writelogs("$GroupName: last GPID=$gpid",__FUNCTION__,__FILE__,__LINE__);

	$md5=md5($aclid.$gpid.$direction);
	$sql="INSERT OR IGNORE INTO $table (zmd5,aclid,gpid,zOrder,direction) VALUES('$md5','$aclid','$gpid',1,$direction)";

	if(!$q->FIELD_EXISTS($table, "direction")) {$q->QUERY_SQL("ALTER TABLE `$table` ADD `direction` smallint(1) NOT NULL DEFAULT 0");}

    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "jserror:$q->mysql_error Line:".__LINE__;return;}
}


