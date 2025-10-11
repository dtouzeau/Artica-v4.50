<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");

$users=new usersMenus();
if(!$users->AsDnsAdministrator){$tpl=new template_admin();$tpl->js_no_privileges();}


$idfrom=intval($_GET["from"]);

if($idfrom>0){
	
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT * FROM webfilter_rules WHERE ID=$idfrom";
	$ligne=$q->mysqli_fetch_array($sql);
	if(preg_match("#(.+?)\(#", $ligne["groupname"],$re)){$ligne["groupname"]=$re[1];}
	$newname=$ligne["groupname"]." (copy)";
	
	
	$zFields=$q->FIELDS_LIST_FOR_QUERY("webfilter_rules");
	
	foreach ($zFields as $Field){
		if($Field=="ID"){continue;}
		$Newfields[]="`$Field`";
		$values[]="'".$ligne[$Field]."'";
		
	}
	
	$sql="INSERT INTO webfilter_rules (".@implode(",", $Newfields).") VALUES (".@implode(",", $values).")";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		$tpl->js_mysql_alert($q->mysql_error);
		return;
	}
	
	$newruleid=$q->last_id;
	$q->QUERY_SQL("UPDATE webfilter_rules SET groupname='$newname' WHERE ID='$newruleid'");
	
	$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$idfrom";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$category=intval($ligne["category"]);
		$q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES('$newruleid','{$ligne["modeblk"]}','$category')");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	
	}
	$sql="SELECT * FROM webfilter_ipsources WHERE ruleid=$idfrom";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$ipaddr=trim($ligne["ipaddr"]);
		$q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_ipsources (ruleid,	ipaddr) VALUES('$newruleid','$ipaddr')");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	
	}
	header("content-type: application/x-javascript");
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes');";
	return;
}
$sock=new dnsfiltersocks();
$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
$zFields=$q->FIELDS_LIST_FOR_QUERY("webfilter_rules");
foreach ($zFields as $Field){
	if($Field=="ID"){continue;}
	$Newfields[]="`$Field`";
	$values[]="'".$ligne[$Field]."'";

}
$sql="INSERT INTO webfilter_rules (".@implode(",", $Newfields).") VALUES (".@implode(",", $values).")";
$newname="Default (Copy)";
$q->QUERY_SQL($sql);
if(!$q->ok){
	$tpl->js_mysql_alert($q->mysql_error);
	return;
}
$newruleid=$q->last_id;
$q->QUERY_SQL("UPDATE webfilter_rules SET groupname='$newname' WHERE ID='$newruleid'");
$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$idfrom";
$results=$q->QUERY_SQL($sql);
foreach ($results as $index=>$ligne){
	$category=intval($ligne["category"]);
	$q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES('$newruleid','{$ligne["modeblk"]}','$category')");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

}

header("content-type: application/x-javascript");
echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes');";