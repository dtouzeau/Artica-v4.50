<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items"])){items();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{display_performance_status}");
	echo "YahooWin('750','$page?popup=yes','$title')";
	
	
}


function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?squidclient-infos=yes")));
    foreach ($datas as $index=>$line){
		if(preg_match("#^.+?:$#", $line)){
			$html[]="
			</div><div style='width:95%;margin-top:15px;' class=form>
			<div style='font-size:16px;font-weight:bold;width:100%;padding-right:20px;text-align:right'>$line<hr></div>";
			continue;
		}
		
		if(preg_match("#\s+(.+?):(.+?)$#", $line,$re)){
			$re[2]=trim($re[2]);
			if(preg_match("#([0-9]+).*?KB#i", $re[2],$ri)){
				$re[2]=FormatBytes($ri[1]);
			}
			$html[]="<div style='font-size:14px;'>{$re[1]}:&nbsp;<strong>{$re[2]}</strong></div>";
			continue;
		}
		if(preg_match("#\s+([0-9]+)\s+(.+?)$#", $line,$re)){
			$html[]="<div style='font-size:14px;'>{$re[2]}:&nbsp;<strong>{$re[1]}</strong></div>";
			continue;
		}		
		
	}
	$html[]="</div>";
	echo @implode("", $html);
}
	
