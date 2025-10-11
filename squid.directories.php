<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert')";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["varlog"])){Save();exit;}



js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$title=$tpl->javascript_parse_text("{directories}");
	$t=time();
	$html="YahooWin3('978','$page?popup=yes&t={$_GET["t"]}','$title');";
	echo $html;
}
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$confirm=$tpl->javascript_parse_text("{change_path_services_warn}");
	
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("system.php?critical-paths-locations=yes")));
	
	
$html="
<div id='id-final-$t'>
<div id='text-$t' style='font-size:18px' class=explain>{change_directories_paths_text}</div>
<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:18px'>Artica {logs_directory} (".FormatBytes($ARRAY["/var/log/artica-postfix"]["SIZE"])."):</td>
		<td class=legend style='font-size:16px'>". Field_text("varlogart-$t",$ARRAY["/var/log/artica-postfix"]["PATH"],"font-size:18px;width:95%")."</td>
		<td width=1%>". button_browse("varlogart-$t")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>Proxy {logs_directory} (".FormatBytes($ARRAY["/var/log/squid"]["SIZE"])."):</td>
		<td class=legend style='font-size:16px'>". Field_text("varlog-$t",$ARRAY["/var/log/squid"]["PATH"],"font-size:18px;width:95%")."</td>
		<td width=1%>". button_browse("varlog-$t")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{logs_backup} (".FormatBytes($ARRAY["/home/logs-backup"]["SIZE"])."):</td>
		<td class=legend style='font-size:16px'>". Field_text("logsbackup-$t",$ARRAY["/home/logs-backup"]["PATH"],"font-size:18px;width:95%")."</td>
		<td width=1%>". button_browse("logsbackup-$t")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{categories_databases}: (".FormatBytes($ARRAY["/home/artica/categories_databases"]["SIZE"]).")</td>
		<td class=legend style='font-size:16px'>". Field_text("categoriesdb-$t",$ARRAY["/home/artica/categories_databases"]["PATH"],"font-size:18px;width:95%")."</td>
		<td width=1%>". button_browse("categoriesdb-$t")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{webfiltering_databases} ICAP: (".FormatBytes($ARRAY["/home/c-icap/blacklists"]["SIZE"]).")</td>
		<td class=legend style='font-size:16px'>". Field_text("icapdb-$t",$ARRAY["/home/c-icap/blacklists"]["PATH"],"font-size:18px;width:95%")."</td>
		<td width=1%>". button_browse("icapdb-$t")."</td>
	</tr>	
				
				
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}...","Save$t()","26")."</td>
	</tr>
</table>
</div>
<script>
var x_Save$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('squid.directories.progress.php');
}
function Save$t(){
	if(!confirm('$confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('varlog',document.getElementById('varlog-$t').value);
	XHR.appendData('logsbackup',document.getElementById('logsbackup-$t').value);
	XHR.appendData('categoriesdb',document.getElementById('categoriesdb-$t').value);
	XHR.appendData('icapdb',document.getElementById('icapdb-$t').value);
	XHR.appendData('varlogart',document.getElementById('varlogart-$t').value);
	
	
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}


</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	
	$f["/var/log/squid"]=$_POST["varlog"];
	$f["/home/artica/categories_databases"]=$_POST["categoriesdb"];
	$f["/home/logs-backup"]=$_POST["logsbackup"];
	$f["/home/c-icap/blacklists"]=$_POST["icapdb"];
	$f["/var/log/artica-postfix"]=$_POST["varlogart"];
	
	@file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/ChangeDirs", @serialize($f));
	if(!is_file("/usr/share/artica-postfix/ressources/conf/upload/ChangeDirs")){echo "Failed\n";}
	
}