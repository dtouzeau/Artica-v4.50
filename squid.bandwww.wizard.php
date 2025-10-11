<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once('ressources/class.system.nics.inc');
if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die("DIE " .__FILE__." Line: ".__LINE__);exit();
}

if(isset($_GET["start-0"])){start_0();exit;}

if(isset($_GET["start-1"])){start_1();exit;}
if(isset($_POST["BDW"])){save();exit;}
if(isset($_POST["BDW_FINAL"])){save();exit;}


if(isset($_GET["start-2"])){start_2();exit;}
if(isset($_POST["SUBNET"])){save();exit;}

if(isset($_GET["start-3"])){start_3();exit;}
if(isset($_POST["RANGE1"])){save();exit;}

if(isset($_GET["start-4"])){start_4();exit;}
if(isset($_POST["GATEWAY"])){save();exit;}

js();
	

function js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{bandwidth}:{wizard}");
	echo "YahooWin3('990','$page?start-0=yes','$title');";
	
	
	
	
}
	
function GetRights(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	
}

function start_0(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?start-1=yes&t=$t');
	</script>
	
	";
	echo $html;
}

function start_1(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	
	
	for($i=1;$i<101;$i++){
		$it=$i;
		if($i<10){$it="0{$i}";}
		$nics[$i]=$it;
		
	}
	
	
	
$html="<div style='font-size:26px;margin-bottom:20px'>{welcome_to_squid_bandwidth_wizard}</div>
<div style='font-size:18px;margin-bottom:20px'>{welcome_to_squid_bandwidth_wizard_1}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:26px'>{bandwidth} (Mbps):</td>
			<td>".Field_array_Hash($nics, "BDW-$t",8,"style:font-size:26px")."</td>
		</tr>
		</table>
		<div style='text-align:right;width:100%'><HR>". button("{next}","Start1$t()",30)."</div>	
</div>

<script>
var xStart1$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	LoadAjax('$t','$page?start-2=yes&t=$t');
}	

function Start1$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BDW',document.getElementById('BDW-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStart1$t);	
}
</script>
	";
echo $tpl->_ENGINE_parse_body($html);
	
}
function start_2(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$BDWWizard=unserialize($sock->GET_INFO("BDWWizard"));
	$BDW=$BDWWizard["BDW"];
	$BDW_NEW=$BDW*0.30;
	
	$BDW_FINAL=$BDW_NEW*1000;
	$BDW_FINAL=$BDW_FINAL*1000;
	$BDW_FINAL=$BDW_FINAL/8;
	
	$welcome_to_squid_bandwidth_wizard_2=$tpl->_ENGINE_parse_body("{welcome_to_squid_bandwidth_wizard_2}");
	$welcome_to_squid_bandwidth_wizard_2=str_replace("%s", $BDW_NEW, $welcome_to_squid_bandwidth_wizard_2);

$html="<div style='font-size:40px;margin-bottom:20px'>{welcome_to_squid_bandwidth_wizard} {$BDW}Mbps</div>
<div style='font-size:18px;margin-bottom:20px'>$welcome_to_squid_bandwidth_wizard_2</div>
<div style='width:98%' class=form>
	<center style='margin:50px'>".button("{apply}", "Start2$t()",50)."</center>
</div>

<script>
var xStart2$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	YahooWin3Hide();
	Loadjs('squid.bandwww.progress.php');
}

function Start2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BDW_FINAL','$BDW_FINAL');
	XHR.appendData('EnableSquidBandWidthGlobal','1');
	XHR.sendAndLoad('$page', 'POST',xStart2$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}


function save(){
	$sock=new sockets();
	
	if(isset($_POST["EnableSquidBandWidthGlobal"])){
		$sock->SET_INFO("EnableSquidBandWidthGlobal", 1);
	}
	
	$BDWWizard=unserialize($sock->GET_INFO("BDWWizard"));
	foreach ($_POST as $num=>$val){
		$BDWWizard[$num]=$val;
	}
	$sock->SaveConfigFile(serialize($BDWWizard), "BDWWizard");
}
