<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}


	if(isset($_GET["compile-rules-js"])){compile_rules_js();exit;}
	if(isset($_GET["compile-rules-perform"])){	compile_rules_perform();exit;}
function compile_rules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$mailman=$tpl->_ENGINE_parse_body("{APP_OPENLDAP}::{compile_rules}");
	$html="YahooWinBrowse('750','$page?compile-rules-perform=yes','$mailman::$cmd');";
	echo $html;		
	
}
function compile_rules_perform(){
	$sock=new sockets();
    $t=time();
	$datas=base64_decode($sock->getFrameWork("services.php?reload-openldap-tenir=yes"));
	echo "
	<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:13px' id='textToParseCats$t'>$datas</textarea>
<script>
	
</script>
		
	";
	
}