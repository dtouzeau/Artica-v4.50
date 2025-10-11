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
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["summarize"])){summarize();exit;}

js();

function js(){
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{refresh_summary}");
	$page=CurrentPageName();
	
		$dateT=date("{l} {F} d",$_GET["xtime"]);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
		$title=$tpl->_ENGINE_parse_body("$dateT&raquo;{refresh_summary}");
	
	$html="
		function start$t(){
			YahooWinS('550','$page?popup=yes&xtime={$_GET["xtime"]}&t=$t','$title');
		
		}
		
		function ChProgr(xvalue){
		
			$('#progress-{$t}').progressbar({ value: xvalue});
		
		}
		
		function Start1$t(){
			ChProgr(30);
			LoadAjaxTiny('infos-$t','$page?summarize=yes&xtime={$_GET["xtime"]}&t=$t');
		}
		
		function Finish2$t(){
			$('#progress-{$t}').remove();
			
		}
		
		function Finish$t(){
			ChProgr(100);
			setTimeout('Finish2$t()',2000);
		}
		
		
	start$t();";
	
	echo $html;
}

function popup(){
	
	$t=$_GET["t"];
	
	$html="<div id='progress-$t'></div>
	<div id='infos-$t'>Please wait...</div>
	
	<script>
		$('#progress-{$t}').progressbar({ value: 10 });
		setTimeout('Start1$t()',2000);
	</script>
	
	";
	
	echo $html;
	
	
}

function summarize(){
	$t=$_GET["t"];
	$time=$_GET["xtime"];
	$dpref=date("Ymd",$time);
	$tablename="dansguardian_events_$dpref";
	//echo "$dpref, $tablename, please wait...\n";
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?summarize-day=$dpref&tablename=$tablename&MyCURLTIMEOUT=120")));
    foreach ($datas as $index=>$line){
		if(preg_match("#Memory:#i", $line)){continue; }
		if(preg_match("#Params:#i", $line)){continue; }
		echo "<div style='font-size:10px'>$line</div>";
	}
	
echo "	<script>
		
		setTimeout('Finish$t()',2000);
	</script>";

}