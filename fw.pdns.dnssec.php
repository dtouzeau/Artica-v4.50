<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
	$page=CurrentPageName();
	$domain_id=intval($_GET["domain_id"]);
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$domainame=$q->GetDomainName($domain_id);
	$tpl->js_dialog6("$domainame: {DNSSEC}", "$page?popup=yes&domain_id=$domain_id",1370);
	
}


function popup(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$q=new mysql_pdns();
	$sql="SELECT * FROM pdnsutil_dnssec WHERE domain_id=$domain_id";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$domainame=$q->GetDomainName($domain_id);
	$form[]=$tpl->field_textareacode("non65464", null, $ligne["content"]);
	echo $tpl->form_outside($domainame." {DNSSEC}", $form,null,"NO");
	
}