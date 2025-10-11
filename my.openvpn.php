<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["ICON_FAMILY"]="VPN";
session_start();
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.openvpn.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.tcpip.inc');
include_once('ressources/class.mysql.inc');
$users=new usersMenus();

page();


function page(){
	$tpl=new templates();
	$q=new mysql();
	$connection_name=$_SESSION["uid"];
	$connection_name=replace_accents($connection_name);
	$connection_name=str_replace("/", "-", $connection_name);
	$connection_name=str_replace('\\', "-", $connection_name);
	$connection_name=str_replace("&","",$connection_name);
	$connection_name=str_replace(",","",$connection_name);
	$connection_name=str_replace(";","",$connection_name);
	$connection_name=str_replace("%","",$connection_name);
	$connection_name=str_replace("*","",$connection_name);
	$connection_name=str_replace("ø","",$connection_name);
	$connection_name=str_replace("$","",$connection_name);
	$connection_name=str_replace("/","",$connection_name);
	$connection_name=str_replace("\\","",$connection_name);
	$connection_name=str_replace("?","",$connection_name);
	$connection_name=str_replace("µ","",$connection_name);
	$connection_name=str_replace("£","",$connection_name);
	$connection_name=str_replace(")","",$connection_name);
	$connection_name=str_replace("(","",$connection_name);
	$connection_name=str_replace("[","",$connection_name);
	$connection_name=str_replace("]","",$connection_name);
	$connection_name=str_replace("#","",$connection_name);
	$connection_name=str_replace("'","",$connection_name);
	$connection_name=str_replace("\"","",$connection_name);
	$connection_name=str_replace("+","_",$connection_name);
	
	$sql="SELECT uid,zipsize FROM `openvpn_clients` WHERE uid='{$connection_name}'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$uid_enc=urlencode($connection_name);
	$zipsize=intval($ligne["zipsize"]);
	if($zipsize==0){
		$button="<center style='margin:30px'>".button("{build_settings}","Loadjs('index.openvpn.build.client.php?uid=$uid_enc')",40)."</center>";
	}else{
		$button="<center style='margin:30px'>".button("{download_vpn_settings}","s_PopUp('index.openvpn.clients.php?download=yes&uid={$uid_enc}')",40)."</center>";
		$button=$button."<center style='margin:30px'>".button("{rebuild_settings}","Loadjs('index.openvpn.build.client.php?uid=$uid_enc')",40)."</center>";
	}
	
	
	$html="
	<div id='my.openvpn.settings'></div>		
	<div style='font-size:40px;margin-bottom:40px'>{$_SESSION["uid"]}: {my_openvpn_configuration}</div>
			
	<center style='width:98%' class=form>
		
			$button
			
			
	</center>";


	echo $tpl->_ENGINE_parse_body($html);
	
}


