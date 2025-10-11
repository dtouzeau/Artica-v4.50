
<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($argv[1]=="verbose"){echo "Verbosed\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
		
	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator) {
		header("content-type: application/x-javascript");
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
		}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["stats-mv"])){stats_mv();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{rebuild_caches}");
	$rebuild_caches_warn=$tpl->javascript_parse_text("{rebuild_caches_warn}");
	
	$html="
		if(confirm('$rebuild_caches_warn')){
			YahooWin6('600','$page?popup=yes&uuid={$_GET["uuid"]}','$title');
	
		}";
	echo $html;
	
	
	
	
}

function popup(){
	
	$t=time();$page=CurrentPageName();$tpl=new templates();
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE table cachestatus");
	$sock=new sockets();
	$tpl=new templates();
	$operation_launched_in_background=$tpl->javascript_parse_text("{operation_launched_in_background}");
	$sock->getFrameWork("squid.php?rebuild-caches=yes");	
	
	
	
	$html="
	<div style='width:100%' id='$t'>
	<center>
		<p style='font-size:18px'>$operation_launched_in_background...</p>
		<img src='img/wait_verybig_mini_red.gif'>
	</center>
	</div>
	<script>
		function Refresh$t(){
			if(YahooWin6Open()){
				LoadAjax('$t','$page?stats-mv=yes&t=$t&uuid={$_GET["uuid"]}');
			}	
		}
	
	
	setTimeout(\"Refresh$t()\",5000);
	</script>					
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function stats_mv(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();$tpl=new templates();
	$db=$_GET["db"];
	
	$datas=@file_get_contents("ressources/logs/web/rebuild-cache.txt");
	if(strlen($datas)<100){
		$html="<center><p style='font-size:18px'>{please_wait}:...</p>
		<img src='img/wait_verybig_mini_red.gif'></center>
		</center>
		<script>
		function Refresh$tt(){
			if(YahooWin6Open()){
			LoadAjax('$t','$page?stats-mv=yes&db={$_GET["db"]}&uuid={$_GET["uuid"]}');
			}
		}
	if(YahooWin6Open()){
		setTimeout(\"Refresh$tt()\",5000);
	}
	</script>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	

	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'>$datas</textarea>
	<script>
	function Refresh$tt(){
		if(YahooWin6Open()){
			LoadAjax('squid-caches-status','squid.caches32.php?squid-caches-status=yes&uuid={$_GET["uuid"]}')
			LoadAjax('$t','$page?stats-mv=yes&t=$t&uuid={$_GET["uuid"]}');
		}
	}
		if(YahooWin6Open()){
			setTimeout(\"Refresh$tt()\",15000);
		}
	</script>
	
	";	
	
}
	
	
	

