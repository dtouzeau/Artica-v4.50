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
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	if(isset($_GET["prepare-js"])){cachesave_js();exit;}
	if(isset($_GET["prepare-popup"])){cachesave_popup();exit;}
	if(isset($_GET["prepare-1"])){cachesave_1();exit;}
	if(isset($_GET["prepare-2"])){cachesave_2();exit;}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["start"])){restart();exit;}
	if(isset($_GET["logs"])){logs();exit;}
	if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();




function js(){
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{reconstruct_caches}");
	$compile_squid_ask=$tpl->javascript_parse_text("{reconstruct_caches_ask}");
	$warn="if(!confirm('$compile_squid_ask')){return;}";
	
	
	
	$page=CurrentPageName();
	$html="
	
	function squid_restart_proxy_load$t(){
			$warn
			YahooWin3('998','$page?popup=yes&t=$t&setTimeout={$_GET["setTimeout"]}','$title');
		
		}
		
	function GetLogs$t(){
		Loadjs('$page?logs=yes&t=$t&setTimeout={$_GET["setTimeout"]}');
		
	}
		
	squid_restart_proxy_load$t();";
	
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$title="{PLEASE_WAIT_RECONSTRUCT_CACHES}";
	
	
	$html="
	<center style='font-size:16px;margin:10px'><div id='title-$t'>$title</div></center>
	<div style='margin:5px;padding:3px;border:1px solid #CCCCCC;width:97%;height:450px;overflow:auto' id='squid-restart'>
	</div>
	
	<script>
		LoadAjax('squid-restart','$page?start=yes&t=$t');
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restart(){
	
	$sock=new sockets();
	$t=$_GET["t"];
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->STATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	

	
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px;width:100%'><div>{proxy_clients_was_notified}</div></center>");
		return;
	}
	
	$sock->getFrameWork("squid.php?reconstruct-caches=yes");
	
	echo "
	<center id='animate-$t'>
		<img src=\"img/wait_verybig.gif\">
	</center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>
			setTimeout(\"GetLogs$t()\",1000);
	</script>";
	
	
}

function Filllogs(){
	$datas=explode("\n",@file_get_contents("ressources/logs/web/rebuild-cache.txt"));
	krsort($datas);
	echo @implode("\n", $datas);
}

function logs(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$datas=@file_get_contents("ressources/logs/web/rebuild-cache.txt");
	if(strlen($datas)<10){
		echo "Loadjs('$page?logs=yes&t=$t');";
		return;
	}
	$strlenOrg=$_GET["strlen"];
	if(!is_numeric($strlenOrg)){$strlenOrg=0;}
	$strlen=strlen($datas);
	
	if(is_numeric($_GET["setTimeout"])){
		$setTimeout="setTimeout('DefHide()','{$_GET["setTimeout"]}');";
	}
	
	if($strlenOrg<>$strlen){
		echo "
				
			function Refresh$tt(){
				if(!YahooWin3Open()){return;}
				Loadjs('$page?logs=yes&t=$t&strlen=$strlen&setTimeout={$_GET["setTimeout"]}');
			
			}
		
		
			var x_Fill$tt= function (obj) {
				var res=obj.responseText;
				if (res.length>3){
					document.getElementById('textToParseCats-$t').value=res;
						if(document.getElementById('squid-services')){
							LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
						}
					
					}
					
				
			}				
		
		
			function Fill$tt(){
				if(!YahooWin3Open()){return;}
				document.getElementById('title-$t').innerHTML='';
				document.getElementById('animate-$t').innerHTML='';
				var XHR = new XHRConnection();
		   	 	XHR.appendData('Filllogs', 'yes');
			    XHR.sendAndLoad('$page', 'POST',x_Fill$tt); 
				setTimeout(\"Refresh$tt()\",5000);
			}
				
			Fill$tt();	
		";
	}else{

		echo "function Refresh$tt(){
				if(!YahooWin3Open()){return;}
				Loadjs('$page?logs=yes&t=$t&strlen=$strlen&setTimeout={$_GET["setTimeout"]}');
			
			}
			
			function DefHide(){
				YahooWin3Hide();
			}
			
			setTimeout(\"Refresh$tt()\",3000);\n$setTimeout";
			
		
		
	}
}




?>