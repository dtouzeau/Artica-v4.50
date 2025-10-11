<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ID"])){Save();exit;}

js();


function js(){
	$ID=intval($_GET["ID"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$RefreshTable=$_GET["RefreshTable"];
	$sql="SELECT groupname FROM webfilter_group WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$tpl->js_dialog1("{group2}: $rulename", "$page?popup=$ID&RefreshTable=$RefreshTable");
}


function popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ID=intval($_GET["popup"]);
	$sql="SELECT * FROM webfilter_group WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$RefreshTable=base64_decode($_GET["RefreshTable"]);
	$refreshSource="LoadAjaxSilent('ufdb-rules-source','fw.ufdb.rules.sources.php?table=$ID&RefreshTable={$_GET["RefreshTable"]}');";

	
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	$localldap[3]="{remote_ladp_group}";
	$localldap[4]="{active_directory_group} ({other})";
	
	$title=$ligne["groupname"];
	$subtitle=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
	$explain="{group_explain_proxy_acls_type_{$ligne["localldap"]}}";
	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_text("groupname", "{groupname}", $ligne["groupname"],true);
	$form[]=$tpl->field_text("description", "{description}", $ligne["description"],true);


    $LINES=array();
	$sql="SELECT * FROM webfilter_members WHERE groupid={$ligne["ID"]} ORDER BY pattern";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$prefix=null;
		$enabled=intval($ligne["enabled"]);
		if($enabled==0){$prefix="#";}
		$LINES[]="$prefix{$ligne["pattern"]}";
	}
	

	$form[]=$tpl->field_textareacode("items", "{items}", @implode("\n", $LINES));
	echo $tpl->form_outside($title." <small>($subtitle)</small>", @implode("\n", $form),$explain,"{apply}","$refreshSource;dialogInstance1.close();$RefreshTable;","AsDansGuardianAdministrator");
	return true;
}

function Save(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl->CLEAN_POST();
	$ID=intval($_POST["ID"]);
	$_POST["groupname"]=$q->sqlite_escape_string2($_POST["groupname"]);
	$_POST["description"]=$q->sqlite_escape_string2($_POST["description"]);
	$sql="UPDATE webfilter_group SET `groupname`='{$_POST["groupname"]}',
	description='{$_POST["description"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$ss=array();
	$LINES=explode("\n",$_POST["items"]);
	foreach ($LINES as $line){
		$enabled=1;
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^\#(.+)$#", $line,$re)){$line=$re[1];$enabled=0;}
		$line=$q->sqlite_escape_string2($line);
		$ss[]="('$ID','$enabled','$line')";
	}
	
	$q->QUERY_SQL("DELETE FROM webfilter_members WHERE groupid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	if(count($ss)>0){
		$q->QUERY_SQL("INSERT INTO webfilter_members (groupid,enabled,pattern) VALUES ".@implode(",", $ss));
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
}

