<?php

$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
$GLOBALS["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{reconfiguring_firewall}");
	echo "
	YahooWinBrowseHide();		
	RTMMail('800','$page?popup=yes&comand=".urlencode($_GET["comand"])."','$title');";
	
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;
	$cachefile=$GLOBALS["PROGRESS_FILE"];
	$logsFile=$GLOBALS["LOG_FILE"];
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($cachefile));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
if($prc==0){
echo "
function Start$time(){
		if(!RTMMailOpen()){return;}
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&comand=".urlencode($_GET["comand"])."');
}
setTimeout(\"Start$time()\",1000);";
return;
}

$md5file=md5_file($logsFile);
if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
		if(!document.getElementById('text-$t')){return;}
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('text-$t').value=res;
		}		
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file&comand=".urlencode($_GET["comand"])."');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('filename','".urlencode($_GET["comand"])."');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

if($prc>100){
	
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		document.getElementById('title-$t').style.border='1px solid #C60000';
		document.getElementById('title-$t').style.color='#C60000';
		$('#progress-$t').progressbar({ value: 100 });
	}
	setTimeout(\"Start$time()\",1000);
	";
	return;	
	
}

if($prc==100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		RTMMailHide();
		if(document.getElementById('firehol-wizard')){  YahooWinHide(); }
		if(document.getElementById('proxy-dashboard')){ LoadAjaxRound('proxy-dashboard','admin.dashboard.proxy.php?proxy-dashboard=yes'); }
		if(document.getElementById('nics-infos-system')){ LoadAjaxRound('system-main-status','admin.dashboard.system.php'); }
		AjaxTopMenu('template-top-menus','admin.top.menus.php');
		if(document.getElementById('FIREHOLE_INTERFACES_TABLES')){ $('#FIREHOLE_INTERFACES_TABLES').flexReload();}
		
		
		}
	setTimeout(\"Start$time()\",1000);
	";	
	return;	
}

echo "	
function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&comand=".urlencode($_GET["comand"])."');
	}
	setTimeout(\"Start$time()\",1500);
";




//Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
		
	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$restart=null;
	
	$sock->getFrameWork("firehol.php?reconfigure-progress=yes&comand=".urlencode($_GET["comand"]));
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0&comand=".urlencode($_GET["comand"])."');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	$logsFile=$GLOBALS["LOG_FILE"];
	$t=explode("\n",@file_get_contents($logsFile));
	krsort($t);
	echo @implode("\n", $t);
	
}