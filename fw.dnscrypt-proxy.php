<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["DnsCryptLocalInterface"])){save();exit;}

page();


function page(){
	$tpl=new template_admin();
	$sock=new sockets();
	$page=CurrentPageName();
	$APP_DNSCRYPT_PROXY_VERSION=$sock->GET_INFO("APP_DNSCRYPT_PROXY_VERSION");
	$DnsCryptLocalInterface=$sock->GET_INFO("DnsCryptLocalInterface");
	$DnsCryptLogLevel=intval($sock->GET_INFO("DnsCryptLogLevel"));
	if($DnsCryptLocalInterface==null){$DnsCryptLocalInterface="lo";}
	$DnsCryptLocalPort=intval($sock->GET_INFO("DnsCryptLocalPort"));
	if($DnsCryptLocalPort==0){$DnsCryptLocalPort=5353;}
	if($DnsCryptLogLevel==0){$DnsCryptLogLevel=6;}
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDNSCrypt="Loadjs('fw.progress.php?content=$prgress&mainid=progress-unbound-restart')";
	
	
	$form[]=$tpl->field_interfaces("DnsCryptLocalInterface", "{listen_interface}", $DnsCryptLocalInterface);
	$form[]=$tpl->field_numeric("DnsCryptLocalPort","{listen_port}",$DnsCryptLocalPort);
	
	
	echo $tpl->form_outside("{APP_DNSCRYPT_PROXY} v$APP_DNSCRYPT_PROXY_VERSION", $form,"{APP_DNSCRYPT_PROXY_ABOUT}","{apply}","$jsRestartDNSCrypt","AsDnsAdministrator");
	
}

function save(){
	
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
}