<?php
// Versionnée pour le SP9 2026-10-10
// Progress bar of the FINAL step of the initial setup wizard (wiz.wizard.php step 5).
//
// Public page (no session yet: CheckSessions() lets "wiz.*" through), so it takes NOTHING
// from the request but DOM ids: the task (route, progress and log files, title, script run
// at the end) is fixed below. It used to unserialize a client array, read the progress
// file the client named, send back its AFTER/BEFORE as JavaScript, and popup() ran
// the old firewall framework route "reconfigure-progress&comand=<client>" (comand=stop stopped the
// firewall) — all without authentication.
//
// Once the wizard has been run, the page does nothing any more (see wizard_progress_state()).

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__)."/ressources/class.wizard.inc");
header("X-Content-Type-Options: nosniff");

if(wizard_progress_state()==="done"){wizard_progress_done();exit;}
if(isset($_GET["content"])){build_progress();exit;}
if(isset($_GET["startup"])){startup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){exit;}   // polled by the bar, never returned anything
header("content-type: application/x-javascript");
exit;

// The only task this page knows.
function wizard_progress_task():array{
    $dir=defined("PROGRESS_DIR")?PROGRESS_DIR:"/usr/share/artica-postfix/ressources/logs/web";
    return array(
        "PROGRESS_FILE" => "$dir/wizard.progress",
        "LOG_FILE"      => "$dir/wizard.log",
        "TITLE"         => "{build_parameters}",
        "AFTER"         => "document.location.href='/fw.login.php'",
    );
}

// "new"     : the wizard has not been run (no WIZARD_INSTALL_EXECUTED).
// "running" : articarest has started the install. It writes WIZARD_INSTALL_EXECUTED (chattr +i)
//             near the START of the install (articarest unixconsole/WizardInstall.go:105), so
//             the bar must still be able to poll: read-only, fixed files.
// "done"    : WIZARD_INSTALL_CHECK_REBOOT exists — written at the very end, after the
//             progress reached 100 (WizardInstall.go:309 then :332-335) — or the install
//             started more than an hour ago. Nothing is done any more.
function wizard_progress_state():string{
    $executed="/etc/artica-postfix/WIZARD_INSTALL_EXECUTED";
    if(!is_file($executed)){return "new";}
    if(is_file("/etc/artica-postfix/WIZARD_INSTALL_CHECK_REBOOT")){return "done";}
    $mtime=@filemtime($executed);
    if($mtime===false || $mtime<time()-3600){return "done";}
    return "running";
}

// After the wizard: no action, no file read; a bar still open goes to the logon page.
function wizard_progress_done(){
    $keys=array_slice(preg_replace('/[^A-Za-z0-9_\-]/','',array_keys(array_merge($_GET,$_POST))),0,8);
    error_log("Artica wizard: ".basename(__FILE__)." called after the setup wizard (".implode(",",$keys)."): ignored");
    header("content-type: application/x-javascript");
    echo "document.location.href='/fw.login.php';";
}

function wizard_progress_id($value):string{
    return preg_replace('/[^A-Za-z0-9_\-]/',"",(string)$value);
}

function build_progress():bool{
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$id=wizard_progress_id(isset($_GET["mainid"])?$_GET["mainid"]:"");
	$t=time();

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
	    Loadjs('$page?startup=yes&mainid=$id&myid=$myid&t=$t');
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
	$id=wizard_progress_id(isset($_GET["mainid"])?$_GET["mainid"]:"");
	$myid=wizard_progress_id(isset($_GET["myid"])?$_GET["myid"]:"");
	$t=wizard_progress_id(isset($_GET["t"])?$_GET["t"]:"");
	$ARRAY=wizard_progress_task();

    // Launches the install only on a wizard that has not been run, and only from the wizard
    // page itself (Loadjs = same-origin XHR; a link, a form or a <script> from elsewhere
    // cannot send this header). A second call (Loadjs retry) only resumes the polling.
    $xhr=isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower(trim((string)$_SERVER["HTTP_X_REQUESTED_WITH"]))==="xmlhttprequest";
    if(!$xhr){
        error_log("Artica wizard: ".basename(__FILE__)." startup without X-Requested-With: ignored");
        return true;
    }
    if(wizard_progress_state()==="new"){
        $data=json_decode($sock->REST_API("/wizard/install"));
        if(!$data->Status){
            $error=base64_decode($tpl->_ENGINE_parse_body($data->Error));
            echo "alert(base64_decode('$error'));";
            return true;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    }

	$title=$tpl->javascript_parse_text($ARRAY["TITLE"]);

	$html="
//
function Step1$t(){
	document.getElementById('prepare-server-title').innerHTML='5% $title';
	document.getElementById('barr-$myid').style.width='5%';
	document.getElementById('barr-$myid').innerHTML='5% $title';
	document.getElementById('barr-$myid').style.backgroundColor='#00d69f';
	Loadjs('$page?build-js=yes&mainid=$id&myid=$myid&t=$t');
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
	$id=wizard_progress_id(isset($_GET["mainid"])?$_GET["mainid"]:"");
	$myid=wizard_progress_id(isset($_GET["myid"])?$_GET["myid"]:"");
	$REFRESH_MENU=0;
	$t=wizard_progress_id(isset($_GET["t"])?$_GET["t"]:"");
	$md5file_get=wizard_progress_id(isset($_GET["md5file"])?$_GET["md5file"]:"");
	$ARRAY=wizard_progress_task();
	$cachefile=$ARRAY["PROGRESS_FILE"];
	$logsFile=$ARRAY["LOG_FILE"];
	$logsFileEncoded=urlencode($logsFile);
	$AFTER=$ARRAY["AFTER"];

	$Details=$tpl->_ENGINE_parse_body("&nbsp;&nbsp;<a href=\"javascript:blur()\" OnClick=\"Zoom$t()\" style=\"text-decoration:underline\">&laquo;{details}&raquo;</a>");

	$title_src=$tpl->javascript_parse_text($ARRAY["TITLE"]);
	$array=@unserialize((string)@file_get_contents($cachefile),array("allowed_classes"=>false));
	if(!is_array($array)){$array=array();}
	if(!isset($array["POURC"])){$array["POURC"]=0;}
	if(!isset($array["TEXT"])){$array["TEXT"]="";}
	$prc=intval($array["POURC"]);
	echo "// prc = $prc\n";

	$title=str_replace(array("'","\\","\r","\n"),array("\\'","","",""),$tpl->javascript_parse_text($array["TEXT"]));
	$titleEncoded=urlencode($title_src);

if($prc==0){
	echo "
	function Start$time(){
			if(!document.getElementById('$id')){return;}
			Loadjs('$page?build-js=yes&mainid=$id&myid=$myid&t=$t&md5file=$md5file_get');
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}
    $md5file="";
    if(is_file($logsFile)) {
        $md5file = md5_file($logsFile);
    }
if($md5file<>$md5file_get){
	echo "
	var xStart$time= function (obj) {
		Loadjs('$page?build-js=yes&mainid=$id&myid=$myid&t=$t&md5file=$md5file');
	}

	function Start$time(){
		if(!document.getElementById('$id')){return;}
		document.getElementById('prepare-server-title').innerHTML='$title_src: $prc% $title';
		document.getElementById('barr-$myid').style.width='$prc%';
		document.getElementById('barr-$myid').innerHTML='$title_src - $prc% $title';
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
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
	document.getElementById('prepare-server-title').innerHTML='$prc% $title';
	document.getElementById('barr-$myid').style.width='$prc%';
	document.getElementById('barr-$myid').innerHTML='$prc% $title';
	Loadjs('$page?build-js=yes&mainid=$id&myid=$myid&t=$t&md5file=$md5file_get');
}
setTimeout(\"Start$time()\",1500);
";
}
