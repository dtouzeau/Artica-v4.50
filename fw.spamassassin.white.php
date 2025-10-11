<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["whitelists"])){Save();exit;}
page();


function page(){
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/spamassassin.TrustedNetworks.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.TrustedNetworks.progress.log";
	$ARRAY["CMD"]="mimedefang.php?TrustedNetworks=yes";
	$ARRAY["TITLE"]="{reconfiguring}";
	$prgress=base64_encode(serialize($ARRAY));
	$mimedefang_reload="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedefangspam-restart');";
	
	
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$results=$q->QUERY_SQL("SELECT * FROM whitelists ORDER BY pattern");
	$tt=array();
	
	foreach ($results as $index=>$ligne){
		$tt[]=$ligne["pattern"];
	}
	
	if(count($tt)==0){$tt[]="127.0.0.1";$tt[]="*@articatech.com";}
	
	$form[]=$tpl->field_textareacode("whitelists", null, @implode("\n", $tt));
	
	echo $tpl->form_outside("{whitelists}", $form,"{spam_whitelists_explain}","{apply}",$mimedefang_reload,"AsPostfixAdministrator"); 
	
	
}

function Save(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$q->QUERY_SQL("DELETE FROM whitelists");
	
	$sql="CREATE TABLE IF NOT EXISTS `whitelists` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `pattern` TEXT )";
	$q->QUERY_SQL($sql);
	
	$tpl->CLEAN_POST();
	$tt=explode("\n",$_POST["whitelists"]);
	
	
	
	
	foreach ($tt as $pattern){
		$pattern=trim($pattern);
		if($pattern==null){continue;}
		$f[]="('$pattern')";
		
	}
	$sql="INSERT INTO whitelists (pattern) VALUES ".@implode(",", $f);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<hr>$sql";}
	
}

