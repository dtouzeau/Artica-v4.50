<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once('ressources/class.os.system.tools.inc');

	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==true){}else{header('location:users.index.php');exit;}
	if(isset($_GET["lighttpd-status"])){lighttpd_status();exit;}
	if(isset($_GET["lighttpd-form"])){lighttpd_form();exit;}
	if(isset($_POST["LighttpdArticaMaxProcs"])){lighttpd_save();exit;}
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div class=explain style='font-size:14px'>{ligghtpd_perf_howto}</div>
	<table style='width:99%' class=form>
	<tr>
		<td style='width:50%' valign='top'>
		
		<div id='lighttpd-form'></div></td>
		<td style='width:50%' valign='top'>
		<div style='width:100%;text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('lighttpd-status','$page?lighttpd-status=yes');")."</div>
		<div id='lighttpd-status'></div></td>
	</tr>
	</table>
	<script>
		LoadAjax('lighttpd-status','$page?lighttpd-status=yes');
		LoadAjax('lighttpd-form','$page?lighttpd-form=yes');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function lighttpd_status(){
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();	
	$ini->loadString(base64_decode($sock->getFrameWork('services.php?lighttpd-status=yes')));
	$LIGHTTPD=DAEMON_STATUS_ROUND("LIGHTTPD",$ini,null,1);
	$LIGHTTPD_PID=$ini->_params["LIGHTTPD"]["master_pid"];
	
	
	$FRAMEWORK=DAEMON_STATUS_ROUND("FRAMEWORK",$ini,null,1);	
	$FRAMEWORK_PID=$ini->_params["FRAMEWORK"]["master_pid"];
	$php_cgi_array=unserialize(base64_decode($sock->getFrameWork('services.php?php-cgi-array=yes')));
	
	
	$LIGHTTPD_AR[]="<table style='width:99%' class=form>";
	
	while (list ($pid, $array) = each ($php_cgi_array[$LIGHTTPD_PID]) ){
		$RSS=FormatBytes($array["RSS"]);
		$VM=FormatBytes($array["VM"]);
		$TTL=$array["TTL"];
		$LIGHTTPD_AR[]="<tr>
		<td><strong style='font-size:12px'>PID:$pid</strong></td>
		<td><strong style='font-size:12px'>RSS:$RSS</strong></td>
		<td><strong style='font-size:12px'>VM:$VM</strong></td>
		<td><strong style='font-size:12px'>TTL:$TTL</strong></td>
		</tr>
		";
		
	}
	$LIGHTTPD_AR[]="</table>";
	
	
	$LIGHTTPDF_AR[]="<table style='width:99%' class=form>";
	
	while (list ($pid, $array) = each ($php_cgi_array[$FRAMEWORK_PID]) ){
		$RSS=FormatBytes($array["RSS"]);
		$VM=FormatBytes($array["VM"]);
		$TTL=$array["TTL"];
		$LIGHTTPDF_AR[]="<tr>
		<td><strong style='font-size:12px'>PID:$pid</strong></td>
		<td><strong style='font-size:12px'>RSS:$RSS</strong></td>
		<td><strong style='font-size:12px'>VM:$VM</strong></td>
		<td><strong style='font-size:12px'>TTL:$TTL</strong></td>
		</tr>
		";
		
	}
	$LIGHTTPDF_AR[]="</table>";	
	
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td>$LIGHTTPD ". @implode("\n", $LIGHTTPD_AR)."</td>
	</tr>
	<tr>
		<td><hr></td>
	</tr>
	<tr>
		<td>$FRAMEWORK". @implode("\n", $LIGHTTPDF_AR)."</td>
	</tr>
	</table>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}

function lighttpd_form(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$LighttpdArticaMaxProcs=$sock->GET_INFO("LighttpdArticaMaxProcs");
	if(!is_numeric($LighttpdArticaMaxProcs)){$LighttpdArticaMaxProcs=2;}
	
	$LighttpdArticaMaxChildren=$sock->GET_INFO("LighttpdArticaMaxChildren");
	if(!is_numeric($LighttpdArticaMaxChildren)){$LighttpdArticaMaxChildren=1;}
	
	$PHP_FCGI_MAX_REQUESTS=$sock->GET_INFO("PHP_FCGI_MAX_REQUESTS");
	if(!is_numeric($PHP_FCGI_MAX_REQUESTS)){$PHP_FCGI_MAX_REQUESTS=200;}
	$t=time();
$html="
	<center id='lighttpd-form-$t'></center>
	<table style='width:99%' class=form>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{lighttp_max_proc}:</strong></td>
		<td>" . Field_text('LighttpdArticaMaxProcs',$LighttpdArticaMaxProcs,'width:60px;font-size:14px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{PHP_FCGI_CHILDREN}:</strong></td>
		<td>" . Field_text('LighttpdArticaMaxChildren',$LighttpdArticaMaxChildren,'width:60px;font-size:14px')."</td>
	</tr>	
	<tr>
		<td nowrap class=legend style='font-size:14px'>{PHP_FCGI_MAX_REQUESTS}:</strong></td>
		<td>" . Field_text('PHP_FCGI_MAX_REQUESTS',$PHP_FCGI_MAX_REQUESTS,'width:60px;font-size:14px')."</td>
	</tr>		
	
	
	
	<tr>
		<td colspan=2 align='right'><hr>
		". button("{apply}", "SaveLighttdConf()",16)."
	</tr>
</table>
<script>
var x_SaveLighttdConf= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
    setTimeout('RefreshAll$t()',80000);
	}

function RefreshAll$t(){
	LoadAjax('lighttpd-status','$page?lighttpd-status=yes');
	LoadAjax('lighttpd-form','$page?lighttpd-form=yes'); 

}

function SaveLighttdConf(){
		var XHR = new XHRConnection();
		XHR.appendData('LighttpdArticaMaxProcs',document.getElementById('LighttpdArticaMaxProcs').value);
		XHR.appendData('PHP_FCGI_MAX_REQUESTS',document.getElementById('PHP_FCGI_MAX_REQUESTS').value);
		XHR.appendData('LighttpdArticaMaxChildren',document.getElementById('LighttpdArticaMaxChildren').value);
		AnimateDiv('lighttpd-form-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveLighttdConf);
}
</script>


";


echo $tpl->_ENGINE_parse_body($html);
	
	
}
function lighttpd_save(){
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
		
	}
	
	$sock->getFrameWork("services.php?restart-lighttpd=yes");
	
}


