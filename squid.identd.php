<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<H1>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H1>";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["SquidEnableIdentdServiceOnly"])){SquidEnableIdentdService();exit;}
	
	
	
tabs();


function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$SquidEnableIdentdService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdService"));
	$SquidEnableIdentdServiceOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdServiceOnly"));
	$SquidEnableIdentTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentTimeout"));
	if($SquidEnableIdentTimeout==0){$SquidEnableIdentTimeout=3;}
	
	
	if($SquidEnableIdentdService==0){
		$html="<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{activate_identd_lookup}","Loadjs('squid.identd.enable.php')",40)."
						<div style='margin-top:20px;font-size:20px'>{squid_identd_daemon_explain}</div>
			</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	$p2=Paragraphe_switch_img("{allow_only_identified_members}", "{allow_only_identified_members_explain}",
	"SquidEnableIdentdServiceOnly-$t",$SquidEnableIdentdServiceOnly,null,1405);
	
	
	$html="<div style='padding:30px;width:95%' class=form>
	<div style='font-size:40px'>{identd_server}</div>
	<div style='font-size:18px;margin-bottom:30px'>{squid_identd_daemon_explain}</div>
	<center style='margin:50px;padding:50px;width:86%'>
				".button("{identd_server}: {disable_feature}","Loadjs('squid.identd.disable.php')",40)."
	</center>
	$p2
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{timeout2} ({seconds}):</td>
		<td>". Field_text("SquidEnableIdentTimeout-$t",$SquidEnableIdentTimeout,"font-size:22px;width:110px")."</td>
	</tr>
	</table>
	
	<div style='margin-top:15px;text-align:right'><hr>". button("{apply}","Submit$t();",40)."</div>
	
		<div style='margin-top:20px;font-size:32px'>{microsoft_windows_softwares}</div>
		<ul style='margin-top:20px'>
			<li style='font-size:18px'><a href='http://articatech.net/download/retina-scan-0.3.0.exe' style='text-decoration:underline'>Retina Scan inetd</a></li>
			<li style='font-size:18px'><a href='http://rndware.info/products/windows-ident-server.html' style='text-decoration:underline'>rndware Windows Ident Server</a></li>
		</ul>
	
	</div>		
<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		Loadjs('squid.global.wl.center.progress.php');
		
	}


	function Submit$t(){
		var XHR = new XHRConnection();	
		XHR.appendData('SquidEnableIdentTimeout',document.getElementById('SquidEnableIdentTimeout-$t').value);
		XHR.appendData('SquidEnableIdentdServiceOnly',document.getElementById('SquidEnableIdentdServiceOnly-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);	
	}
</script>			
			
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function SquidEnableIdentdService(){
	$sock=new sockets();
	$sock->SET_INFO("SquidEnableIdentdServiceOnly", $_POST["SquidEnableIdentdServiceOnly"]);
	$sock->SET_INFO("SquidEnableIdentTimeout", $_POST["SquidEnableIdentTimeout"]);
	
}


function tabs(){
	$sock=new sockets();
	$tpl=new templates();
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	
	
	if(!isset($COMPILATION_PARAMS["enable-ident-lookups"])){
		echo "<div id='squid-identd-upd-error'></div>".FATAL_ERROR_SHOW_128("{error_squid_ident_not_compiled}<center>
				".button("{update2}","Loadjs('squid.compilation.status.php');",32)."
				

				
				</center>");
		return;
	
	}
	
	$SquidEnableIdentdService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdService"));

	
	if($SquidEnableIdentdService==0){
		$html="<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{activate_identd_lookup}","Loadjs('squid.identd.enable.php')",40)."
						<div style='margin-top:20px;font-size:20px;text-align:left'>{squid_identd_daemon_explain}</div>
						
		<div style='margin-top:20px;font-size:32px;text-align:left'>{microsoft_windows_softwares}</div>
		<ul style='margin-top:20px;text-align:left'>
			<li style='font-size:18px'><a href='http://articatech.net/download/retina-scan-0.3.0.exe' style='text-decoration:underline'>Retina Scan inetd</a></li>
			<li style='font-size:18px'><a href='http://rndware.info/products/windows-ident-server.html' style='text-decoration:underline'>rndware Windows Ident Server</a></li>
		</ul>
	
	</div>
			</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	
	}
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["status"]='{status}';
	$array["networks"]='{networks}';
	$sock=new sockets();
	
	
	
	foreach ($array as $num=>$ligne){
	
		if($num=="networks"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.identd.network.php\" style='font-size:26px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:26px'><span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
	}
	echo build_artica_tabs($html, "debug_identd_config",1490)."<script>LeftDesign('users-white-256.png');</script>";
	
	
	
}