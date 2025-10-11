<?php

$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mailsecurity.install.progress";
$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mailsecurity.install.progress.txt";

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
	$title=$tpl->javascript_parse_text("{installing} {$_GET["filename"]}");
	$t=time();
	$ask=null;
	if($_GET["key"]<>null){
		$sock=new sockets();
		$ArticaTechNetSquidRepo=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaTechNetMailSecurityRepo")));
		
		
		$version=$ArticaTechNetSquidRepo[$_GET["key"]][$_GET["OS"]]["VERSION"];
		$filename=$ArticaTechNetSquidRepo[$_GET["key"]][$_GET["OS"]]["FILENAME"];
		$askt=$tpl->javascript_parse_text("{update2} $filename v$version ?");
		$ask="if(!confirm('$askt')){return;}";
		
	}
	
	echo "
	
	function Start$t(){
		$ask	
		YahooWinBrowseHide();		
		RTMMail('800','$page?popup=yes&key={$_GET["key"]}&OS={$_GET["OS"]}&filename=".urlencode($_GET["filename"])."','$title');
	
	}
	Start$t();";

	
	
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
    if(!is_array($array)){$array=array();}
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
if($prc==0){
echo "
function Start$time(){
		if(!RTMMailOpen()){return;}
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&key={$_GET["key"]}&OS={$_GET["OS"]}&filename=".urlencode($_GET["filename"])."');
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
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file&key={$_GET["key"]}&OS={$_GET["OS"]}&filename=".urlencode($_GET["filename"])."');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('filename','".urlencode($_GET["filename"])."');
		XHR.appendData('key','".urlencode($_GET["key"])."');
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
		RefreshTab('main_config_artica_update');
		LoadAjaxRound('messaging-security-update','mailsecurity.update.php');
		LayersTabsAllAfter();
		RTMMailHide();
		CacheOff();
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
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&key={$_GET["key"]}&OS={$_GET["OS"]}&filename=".urlencode($_GET["filename"])."');
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
	
	$sock->getFrameWork("mailsecurity.php?install-tgz=".urlencode($_GET["filename"])."&key={$_GET["key"]}&OS={$_GET["OS"]}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...<br>{$_GET["filename"]}");
	
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
	Loadjs('$page?build-js=yes&t=$t&md5file=0&key={$_GET["key"]}&OS={$_GET["OS"]}&filename=".urlencode($_GET["filename"])."');
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