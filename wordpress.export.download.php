<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsWebMaster==false){echo "alert('No privs');";die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["download"])){download_js();exit;}
if(isset($_GET["download-file"])){download_file();exit;}



js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{download} {$_GET["servername"]}");
	$servername=$_GET["servername"];
	$servername_enc=urlencode($servername);
	echo "
	YahooWinBrowseHide();		
	RTMMail('800','$page?popup=yes&servername=$servername_enc','$title');";
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$servername_enc=urlencode($servername);
	$html="
	<center style='margin:50px'>". button("$servername.tar.gz","Loadjs('$page?download=$servername_enc')",26)."</center>		
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function download_js(){
	header("content-type: application/x-javascript");
	$servername=$_GET["download"];
	$servername_enc=urlencode($servername);
	$page=CurrentPageName();
	$filepathenc=urlencode("/home/artica/wordress-exported/$servername.tar.gz");
	$sock=new sockets();
	$data=trim($sock->getFrameWork("system.php?copytocache=$filepathenc"));
	if(strlen($data)>3){
		echo "alert('$data')";
		return;
	}
	echo "
	RTMMailHide();		
	window.location.href = '$page?download-file=$servername_enc.tar.gz';";
	//echo "s_PopUp('$page?download=$filepathenc',1,1,'');";
}

function download_file(){
	$file=basename($_GET["download-file"]);
	$path="/usr/share/artica-postfix/ressources/logs/$file";
	$sock=new sockets();
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".urlencode(base64_encode($path))));
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	@unlink($path);
}