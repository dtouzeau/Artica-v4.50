<?php
if(posix_getuid()<>0){session_start();if(!isset($_SESSION["uid"])){if(isset($_GET["js"])){echo "document.location.href='logoff.php';";die("DIE " .__FILE__." Line: ".__LINE__);}}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.mysql.inc");

if($GLOBALS["VERBOSE"]){echo @implode("\n", $_GET);}

if(isset($_GET["install_status"])){install_status();exit;}
if(posix_getuid()<>0){
	if(!isset($_SESSION["uid"])){if(!isset($_GET["js"])){echo "document.location.href='logoff.php';";die("DIE " .__FILE__." Line: ".__LINE__);}}
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
}

if(isset($_GET["popup"])){popup();exit();}
if(isset($_GET["transactions-history"])){transaction_history();exit;}
js();


function js(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{mandatories_packages}");
	$mandatories_packages_ask=$tpl->javascript_parse_text("{mandatories_packages_ask}");
	$html="
	
		function start$t(){
			if(confirm('$mandatories_packages_ask')){
				RTMMail('650','$page?popup=yes','$title');
			}
		
		}
	
	 start$t();";
	
	echo $html;
	
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$sock->getFrameWork("services.php?setup-ubuntu=yes");
	$t=time();
	$html="<div id='$t' style='width:100%;min-height:450px;height:450px;overflow:auto'>
		<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		</div>
		
		<script>
			function TransactionSeupUbuntuSearch(){
				if(!RTMMailOpen()){return;}
				LoadAjax('$t','$page?transactions-history=yes&t=$t');
			}
			
			setTimeout('TransactionSeupUbuntuSearch()',3000);
		</script>	
		";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function transaction_history(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$file=PROGRESS_DIR."/setup-ubuntu.log";
	if(!is_file($file)){
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		<script>setTimeout('TransactionSeupUbuntuSearch()',3000);</script>");
		return;
		
	}
	
	$f=explode("\n",@file_get_contents($file));
	if(count($f)<2){
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		<script>setTimeout('TransactionSeupUbuntuSearch()',3000);</script>");
		return;
				
	}
	krsort($f);
	foreach ($f as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$ta[]="<div><code style='font-size:12px'>".htmlentities($ligne)."</code></div>";
	}
	
	echo @implode("", $ta)."<script>setTimeout('TransactionSeupUbuntuSearch()',5000);</script>";
	
}
