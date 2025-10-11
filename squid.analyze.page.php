<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.analyze-page.inc');
	
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	
	if(isset($_GET["test"])){test();exit;}
	
	popup();
function popup() {
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$t=time();
	$html="<div style='font-size:16px' class=explain>{analyze_page_white_perform}</div>
	<div style='width:95%;padding:15px' class=form>
	<center>
	". Field_text("test-$t",null,"font-size:22px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."
			</center>
			</div>
			<div id='results-$t'></div>
			<script>
			function Run$t(e){
			if(!checkEnter(e)){return;}
			LoadAjax('results-$t','$page?test='+document.getElementById('test-$t').value,true);
	}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

	
	function test(){
		$tpl=new templates();
		$www=$_GET["test"];
		
		
		$t=new analyze_page($www);
		$t->parse();
		echo "<div style='margin-top:10px'>";
		while (list ($ip, $line) = each ($t->results) ){
			echo "<div style='font-size:22px'>$ip</div>";
			
		}
		
		echo "</div>";
	}