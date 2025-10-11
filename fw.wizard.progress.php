<?php



if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
if(isset($_GET["content"])){build_progress();exit;}
if(isset($_GET["startup"])){startup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function build_progress(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ARRAY=unserialize(base64_decode($_GET["content"]));
	$id=$_GET["mainid"];
	$t=time();
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	$title=$tpl->javascript_parse_text($ARRAY["TITLE"]);
	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");
	$myid=md5(microtime());
	$htmlContent="<div class=ibox-content><h5 id=\"title-$myid\">$title</h5><div class=\"progress progress-bar-default\" id=\"main-$myid\"><div id=\"barr-$myid\" style=\"width: 2%\" aria-valuemax=\"100\" aria-valuemin=\"0\" aria-valuenow=\"5\" role=\"progressbar\" class=\"progress-bar\">2% $title2</div></div></div>";
	
	
	$html="
	function f$myid(){
		if(document.getElementById('reconfigure-service-div') ){
			document.getElementById('reconfigure-service-div').style.marginTop='0px';
			document.getElementById('reconfigure-service-div').className='';
			document.getElementById('reconfigure-service-div').innerHTML='';
		}
	
	
		document.getElementById('$id').innerHTML='$htmlContent';
	    Loadjs('$page?startup={$_GET["content"]}&mainid=$id&myid=$myid&t=$t');
	}
	
	f$myid();";
	
	
	echo $html;
	
}

function  startup(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$id=$_GET["mainid"];
	$myid=$_GET["myid"];
	$t=$_GET["t"];
	
	$ARRAY=unserialize(base64_decode($_GET["startup"]));
	$CMD="system.php?wizard-execute=yes";
	$sock->getFrameWork($CMD);
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	

	if($GLOBALS["PROGRESS_FILE"]==null){
		
		echo "document.getElementById('title-$myid').innerHTML='Progress file not set';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='Progress file not set';		
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#ED5565';";
		return;
	}
	
	$title=$tpl->javascript_parse_text($ARRAY["TITLE"]);
	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");
	
	$html="
//$CMD
function Step1$t(){
	document.getElementById('title-$myid').innerHTML='5% $title';
	document.getElementById('barr-$myid').style.width='5%';
	document.getElementById('barr-$myid').innerHTML='5% $title';
	Loadjs('$page?build-js={$_GET["startup"]}&mainid=$id&myid=$myid&t=$t');
}

setTimeout(\"Step1$t()\",1000);

			
			
";
	
	echo $html;
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$id=$_GET["mainid"];
	$myid=$_GET["myid"];
	$REFRESH_MENU=0;
	$t=$_GET["t"];
	$ARRAY=unserialize(base64_decode($_GET["build-js"]));
	$CMD=$ARRAY["CMD"];
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	$cachefile=$GLOBALS["PROGRESS_FILE"];
	$logsFile=$GLOBALS["LOG_FILE"];
	$logsFileEncoded=urlencode($logsFile);
	$BEFORE=$ARRAY["BEFORE"];
	$AFTER=$ARRAY["AFTER"];
	if(isset($ARRAY["REFRESH-MENU"])){$REFRESH_MENU=1;}
	
	$Details=$tpl->_ENGINE_parse_body("&nbsp;&nbsp;<a href=\"javascript:blur()\" OnClick=\"javascript:Zoom$t()\" style=\"text-decoration:underline\">&laquo;{details}&raquo;</a>");
	
	$title_src=$tpl->javascript_parse_text($ARRAY["TITLE"]);
	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");	
	echo "// Array of ".count($ARRAY)." elements\n";
	echo "// Cache file = $cachefile\n";
	echo "// Log file = $logsFile\n";
	echo "// CMD = $CMD\n";
	$array=unserialize(@file_get_contents($cachefile));
    if(!is_array($array)){$array=array();}
	$prc=intval($array["POURC"]);
	echo "// prc = $prc\n";
	
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	$titleEncoded=urlencode($title_src);
	
if($prc==0){
	echo "
	function Start$time(){
			if(!document.getElementById('$id')){return;}
			Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id&myid=$myid&t=$t&md5file={$_GET["md5file"]}');
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

$md5file=md5_file($logsFile);
if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
//		if(!document.getElementById('text-$t')){return;}
//		var res=obj.responseText;
//		if (res.length>3){ document.getElementById('text-$t').value=res; }		
		Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id&myid=$myid&t=$t&md5file=$md5file');
	}		
	
	function Start$time(){
		if(!document.getElementById('$id')){return;}
		document.getElementById('title-$myid').innerHTML='$title_src: {$prc}% $title';
		document.getElementById('barr-$myid').style.width='{$prc}%';
		document.getElementById('barr-$myid').innerHTML='$title_src - {$prc}% $title';
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
		if(!document.getElementById('$id')){return;}
		document.getElementById('title-$myid').innerHTML='$title_src - 100% $title{$Details}';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='$title_src: 100% $title';		
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#ED5565';
		
	}
	function Zoom$t(){ Loadjs('fw.progress.details.php?logfile=$logsFileEncoded&title=$titleEncoded'); }
	setTimeout(\"Start$time()\",1000);
	";
	return;	
	
}

if($prc==100){
	echo "
	function Start$time(){
		var REFRESH_MENU=$REFRESH_MENU;
		if(!document.getElementById('$id')){return;}
		document.getElementById('title-$myid').innerHTML='$title_src: 100% $title{$Details}';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='$title_src - 100% $title';		
		document.getElementById('barr-$myid').className='progress-bar';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#1AB394';
		if(REFRESH_MENU==1){
			uri=document.getElementById('fw-left-menus-uri').value
			LoadAjaxSilent('left-barr',uri);
		}
		$AFTER;
		
	}
	
	function Zoom$t(){ Loadjs('fw.progress.details.php?logfile=$logsFileEncoded&title=$titleEncoded'); }
	
	setTimeout(\"Start$time()\",1000);
	";	
	return;	
}

echo "	
function Start$time(){
	if(!document.getElementById('$id')){return;}
	document.getElementById('title-$myid').innerHTML='{$prc}% $title';
	document.getElementById('barr-$myid').style.width='{$prc}%';
	document.getElementById('barr-$myid').innerHTML='{$prc}% $title';
	Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id&myid=$myid&t=$t&md5file={$_GET["md5file"]}');
}
$BEFORE;
setTimeout(\"Start$time()\",1500);
";
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