<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}


if(isset($_GET["graph"])){graphs();exit;}
popup();


function popup(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["day"]="{day}";
	$array["week"]="{week}";
	$array["month"]="{month}";
	
	
	foreach ($array as $num=>$ligne){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?graph=yes&hostid={$_GET["hostid"]}&t=$num\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_stats_yorels_{$_GET["hostid"]} style='width:100%;height:auto;overflow:auto'>
		<ul style='font-size:16px'>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_stats_yorels_{$_GET["hostid"]}').tabs();
			
			
			});
		</script>";


	
	
}
function graphs(){
$t=$_GET["t"];
if($t=='day'){$day='id=tab_current';$title=$t;$t="1$t";}
if($t=='week'){$week='id=tab_current';$t="2$t";}
if($t=='month'){$month='id=tab_current';$t="3$t";}	
$hostid=$_GET["hostid"];


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("content-type:text/html");
$md=md5(date('Ymdhis'));

	
	
	$html="
<input type='hidden' id='t' value='$t'>
<div class='explain' style='font-size:14px'>{system_perfomances_text}</div
<table style='width:600px' align=center>
<tr>
<td valign='top'>
	<center style='margin:4px'>
		<H5>{cpu_stat}</H5>
		<img src='logs/web/$hostid/01cpu-$t.png'>
	</center>
	<center style='margin:4px'>
		<H5>{mem_stat}</H5>
		<img src='logs/web/$hostid/03mem-$t.png'>
	</center>
	<center style='margin:4px'>
		<H5>{load_stat}</H5>
		<img src='logs/web/$hostid/02loadavg-$t.png'>
	</center>
	<center style='margin:4px'>
		<H5>{proc_stat}</H5>
		<img src='logs/web/$hostid/06proc-$t.png'>
	</center>					
</td>
</tr>
</table>	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}