<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$GLOBALS["LOGFILE"]=PROGRESS_DIR."/exec.virtuals-ip.php.html";
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	

	
	if(isset($_GET["popup"])){popup();exit;}
	
js();

function js(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{status}");
	echo "YahooWin3('998','$page?popup=yes&t=$t','$title');";
}

function popup(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?ifconfig-show=yes")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
	id='textarea$t'>".@implode("\n", $datas)."</textarea>";
}