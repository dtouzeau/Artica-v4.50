<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["wsusofflineStorageDir"])){save();exit;}
if(isset($_GET["execute-now"])){execute_now();exit;}
if(isset($_GET["title"])){title();exit;}
page();


function page(){

	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{updating_clients_with_artica}</h1>
	</div>
	
</div>
			
                            
			
<div class='row'><div id='progress-wsusofflinefiles-restart'></div>
			<div class='ibox-content'>
       	
			 	<div id='table-loader'></div>
                                    
			</div>
</div>
					
			
			
<script>
LoadAjax('table-loader','$page?table=yes');
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]="<p>{wsusoffline_howto}</p>";
	
	
	
$host=$_SERVER["SERVER_ADDR"];

$cmd[]="@echo off";
$cmd[]="";
$cmd[]="echo please wait, connecting to the wsus directory...";
$cmd[]="IF %ERRORLEVEL% == 0 ( ";
$cmd[]="	echo Running privileged";
$cmd[]=") ELSE (";
$cmd[]="	echo Not admin execution...";
$cmd[]="	exit 0";
$cmd[]=")";
$cmd[]="";
$cmd[]="net use Z: \\\\$host\wsus /persist:no";
$cmd[]="if not exist \"z:\cmd\DoUpdate.cmd\" (";
$cmd[]="	echo Missing binary in directory, disconnecting...";
$cmd[]="	net use z: /delete /y";
$cmd[]="	exit 0";
$cmd[]=")";
$cmd[]="echo Connecting success..";
$cmd[]="echo running the update service...";
$cmd[]="z:\cmd\DoUpdate.cmd /instielatest /autoreboot";
$cmd[]="echo Disconnecting...";
$cmd[]="net use z: /delete /y";
$cmd[]="echo Done...";
$cmd[]="";
$cmd[]="";
$html[]="<textarea id='wsusofflinecode' name='wsusofflinecode'>".@implode("\n", $cmd)."</textarea>";

$html[]="
<p>
<H2>{additionals_tokens}</h2>
<strong>/nobackup</strong> (Does not create a backup, only works for Windows XP) [only until WsusOffline 9.7]<br>
<strong>/verify</strong> (Checks the intengrity of Files)<br>
<strong>/instie7</strong> (Installs Internet Explorer 7, only works for Windows XP) [only until WsusOffline 9.7]<br>
<strong>/instie8</strong> (Installs Internet Explorer 8, only works for Windows XP & Vista) [only until WsusOffline 10.3.2]<br>
<strong>/instie9</strong> (Installs Internet Explorer 9, only works for Windows Vista & 7) [only until WsusOffline 10.3.2]<br>
<strong>/instie10</strong> (Installs Internet Explorer 10, only works for Windows 7) [only until WsusOffline 10.3.2]<br>
<strong>/instie11</strong> (Installs Internet Explorer 11, only works for Windows 7) [only until WsusOffline 10.3.2]<br>
<strong>/instielatest</strong> (Installs the latest version of Internet Explorer [IE8 on WinXP; IE9 on Vista; IE11 on Windows 7]) [only until WsusOffline 10.3.2]<br>
<strong>/skipieinst</strong> (Avoid mandatory installation of most recent Internet Explorer) [since WsusOffline 10.6]<br>
<strong>/updatetcerts</strong> (Updates the Root certificates, only works for 32bit/x86 systems) [only until WsusOffline 9.7]<br>
<strong>/updatecpp</strong> (Updates Microsoft C++ runtime)<br>
<strong>/updatedx</strong> (Updates Microsoft DirectX, not valid for Windows 8.x) [only until WsusOffline 9.2.1]<br>
<strong>/instmssl</strong> (Update / Install Microsoft Silverlight)<br>
<strong>/updatewmp</strong> (Installs Windows Media Player 11, only works for Windows XP) [only until WsusOffline 9.2.1]<br>
<strong>/updatetsc</strong> (Updates Remote Desktop)<br>
<strong>/instdotnet35</strong> (Installs Microsoft .NET Framework 3.5 SP1, only works for Windows XP & Vista)<br>
<strong>/instdotnet4</strong> (Installs Microsoft .NET Framework 4 on Windows XP / .NET Framework 4.6 on Windows Vista, 7, 8.x) [not valid for Windows 10]<br>
<strong>/instpsh</strong> (Installs Microsoft Powershell, only works for Windows XP & Vista, requires .NET 3.5 SP1)<br>
<strong>/instwmf</strong> (Installs Windows Managment Framework 5.0, only for Windows 7 & 8.x)<br>
<strong>/instmsse</strong> (Installs Microsoft Security Essentials, only for Vista & Windows 7)<br>
<strong>/instwd</strong> (Installs Windows Defender, only works for Windows XP) [only until WsusOffline 7.4.1]<br>
<strong>/instofc</strong> (Installs the Office File Converter, requires Office 2003, only works for Office 2003) [only until WSUSUO 9.2.1]<br>
<strong>/instofv</strong> (Installs the Office File validation, requires Office 2003 or 2007, only works for Office 2003 & 2007)<br>
<strong>/autoreboot</strong> (Reboots the computer and continues the update process, if required)<br>
<strong>/shutdown</strong> (Shuts down the computer after the end of the update process)<br>
<strong>/showlog</strong> (Shows log file after update completes)<br>
<strong>/all</strong> (Installs all updates, including those, which are installed)<br>
<strong>/excludestatics</strong> (Does not install statically defined updates)<br>
<strong>/skipdefs</strong> (Avoid mandatory installation of most recent Windows Defender and Microsoft Security Essentials definition files) [since WsusOffline 10.7.4]</p>";
$html[]="<script>";
$html[]="var editorWSUS = CodeMirror.fromTextArea(document.getElementById('wsusofflinecode'), { lineNumbers: true, matchBrackets: true });";
$html[]="</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}