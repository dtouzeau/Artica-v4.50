<?php



if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__)."/ressources/class.wizard.inc");
if(isset($_GET["content"])){build_progress();exit;}
if(isset($_GET["startup"])){startup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function build_progress():bool{
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ARRAY=unserialize(base64_decode($_GET["content"]));
	$id=$_GET["mainid"];
	$t=time();
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];

	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");
	$myid=md5(microtime());
	$htmlContent="<div class=\"progress progress-bar-default\" id=\"main-$myid\" style=\"height: 60px\"><div id=\"barr-$myid\" style=\"width: 2%\" aria-valuemax=\"100\" aria-valuemin=\"0\" aria-valuenow=\"5\" role=\"progressbar\" class=\"progress-bar\">2% $title2</div></div>";
	
	
	$html="
	function f$myid(){
		if(document.getElementById('reconfigure-service-div') ){
			document.getElementById('reconfigure-service-div').style.marginTop='0px';
			document.getElementById('reconfigure-service-div').className='';
			document.getElementById('reconfigure-service-div').innerHTML='';
		}
	
	    if(!document.getElementById('$id')){alert('$id nor found');}
		document.getElementById('$id').innerHTML='$htmlContent';
		document.getElementById('barr-$myid').style.backgroundColor='#00d69f';
	    Loadjs('$page?startup={$_GET["content"]}&mainid=$id&myid=$myid&t=$t');
	}
	
	f$myid();";
	
	
	echo $html;
	return true;
}

function  startup():bool{
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$id=$_GET["mainid"];
	$myid=$_GET["myid"];
	$t=$_GET["t"];
	
	$ARRAY=unserialize(base64_decode($_GET["startup"]));

	$data=json_decode($sock->REST_API("/wizard/install"));
    if(!$data->Status){
        $error=base64_decode($tpl->_ENGINE_parse_body($data->Error));
        echo "alert(base64_decode('$error'));";
        return true;
    }

	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	

	if($GLOBALS["PROGRESS_FILE"]==null){
		
		echo "document.getElementById('prepare-server-title').innerHTML='Progress file not set';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='Progress file not set';		
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
        document.getElementById('barr-$myid').style.backgroundColor='#00d69f';
		document.getElementById('prepare-server-title').style.color='#ED5565';";
		return true;
	}
	
	$title=$tpl->javascript_parse_text($ARRAY["TITLE"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
	
	$html="
//
function Step1$t(){
	document.getElementById('prepare-server-title').innerHTML='5% $title';
	document.getElementById('barr-$myid').style.width='5%';
	document.getElementById('barr-$myid').innerHTML='5% $title';
	document.getElementById('barr-$myid').style.backgroundColor='#00d69f';
	Loadjs('$page?build-js={$_GET["startup"]}&mainid=$id&myid=$myid&t=$t');
}

setTimeout(\"Step1$t()\",1000);

			
			
";
	
	echo $html;
	return true;
}


function buildjs(){
	$time=time();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
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
    if(!isset($ARRAY["BEFORE"])){$ARRAY["BEFORE"]="";}
    if(!isset($ARRAY["AFTER"])){$ARRAY["AFTER"]="";}
	$BEFORE=$ARRAY["BEFORE"];
	$AFTER=$ARRAY["AFTER"];
	if(isset($ARRAY["REFRESH-MENU"])){$REFRESH_MENU=1;}
	
	$Details=$tpl->_ENGINE_parse_body("&nbsp;&nbsp;<a href=\"javascript:blur()\" OnClick=\"Zoom$t()\" style=\"text-decoration:underline\">&laquo;{details}&raquo;</a>");

	$title_src=$tpl->javascript_parse_text($ARRAY["TITLE"]);
	echo "// Array of ".count($ARRAY)." elements\n";
	echo "// Cache file = $cachefile\n";
	echo "// Log file = $logsFile\n";
	echo "// CMD = $CMD\n";
	$array=unserialize(@file_get_contents($cachefile));
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
    $md5file="";
    if(is_file($logsFile)) {
        $md5file = md5_file($logsFile);
    }
if($md5file<>$_GET["md5file"]){
    if(!isset($_GET["comand"])){
        $_GET["comand"]="";
    }
	echo "
	var xStart$time= function (obj) {
//		if(!document.getElementById('text-$t')){return;}
//		var res=obj.responseText;
//		if (res.length>3){ document.getElementById('text-$t').value=res; }		
		Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id&myid=$myid&t=$t&md5file=$md5file');
	}		
	
	function Start$time(){
		if(!document.getElementById('$id')){return;}
		document.getElementById('prepare-server-title').innerHTML='$title_src: $prc% $title';
		document.getElementById('barr-$myid').style.width='$prc%';
		document.getElementById('barr-$myid').innerHTML='$title_src - $prc% $title';
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
		document.getElementById('prepare-server-title').innerHTML='$title_src - 100% $title$Details';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='$title_src: 100% $title';		
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('prepare-server-title').style.color='#ED5565';
		
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
		document.getElementById('prepare-server-title').innerHTML='$title_src: 100% $title{$Details}';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='$title_src - 100% $title';		
		document.getElementById('barr-$myid').className='progress-bar';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('prepare-server-title').style.color='#1AB394';
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
if(strlen($BEFORE)>2){$BEFORE="$BEFORE;";}
echo "	
function Start$time(){
	if(!document.getElementById('$id')){return;}
	document.getElementById('prepare-server-title').innerHTML='$prc% $title';
	document.getElementById('barr-$myid').style.width='$prc%';
	document.getElementById('barr-$myid').innerHTML='$prc% $title';
	Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id&myid=$myid&t=$t&md5file={$_GET["md5file"]}');
}
$BEFORE
setTimeout(\"Start$time()\",1500);
";
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();

	
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

function Filllogs():bool{
    if(!isset($GLOBALS["LOG_FILE"])){return "";}
	$logsFile=$GLOBALS["LOG_FILE"];
	$t=explode("\n",@file_get_contents($logsFile));
	krsort($t);
	echo @implode("\n", $t);
	return true;
}