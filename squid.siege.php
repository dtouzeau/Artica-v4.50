<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


if(isset($_POST["USE_LOCAL_PROXY"])){Save();exit;}
if(isset($_POST["report-delete"])){delete();exit;}

page();



function request_tool_step1(){
	header("content-type: application/x-javascript");
	$sock=new sockets();
	@unlink("ressources/support/request.tar.gz");
	$uri=url_decode_special_tool($_GET["uri"]);
	$uri=urlencode($uri);
	$sock->getFrameWork("squid.php?request-package-full=yes&uri=$uri");	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{please_wait}...");
	echo "
	$('#progress-report-$t').progressbar({ value: 5 });
	document.getElementById('title-$t').innerHTML='$title';
	Loadjs('$page?request-package-progress=yes&t={$_GET["t"]}',false);
	";	
	
}

function support_tool_step1(){
	header("content-type: application/x-javascript");
	$sock=new sockets();
	@unlink("ressources/support/support.tar.gz");
	$sock->getFrameWork("squid.php?support-package-full=yes");
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{please_wait}...");
	echo "
	$('#report-$t').progressbar({ value: 5 });
	document.getElementById('title-$t').innerHTML='$title';		
	Loadjs('$page?support-package-progress=yes&t={$_GET["t"]}',false);
	";	
	
}

function delete(){
	@unlink(PROGRESS_DIR."/siege.report.txt");
	
}



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSiegeConfig")));
	if(!is_numeric($ARRAY["GRAB_URLS"])){$ARRAY["GRAB_URLS"]=0;}
	if(!is_numeric($ARRAY["USE_LOCAL_PROXY"])){$ARRAY["USE_LOCAL_PROXY"]=1;}
	if(!is_numeric($ARRAY["SESSIONS"])){$ARRAY["SESSIONS"]=150;}
	if(!is_numeric($ARRAY["MAX_TIME"])){$ARRAY["MAX_TIME"]=30;}
	
	$REPORT=unserialize(@file_get_contents(PROGRESS_DIR."/siege.report.txt"));
	if(isset($REPORT["START_TIME"])){
		$duration=distanceOfTimeInWords($REPORT["START_TIME"],$REPORT["STOP_TIME"]);
		$ttime=$tpl->time_to_date($REPORT["STOP_TIME"],true);
		unset($REPORT["START_TIME"]);
		unset($REPORT["STOP_TIME"]);
		
		$f[]="<div style='width:98%' class=form><table style='width:100%'><tr><td style='font-size:26px' nowrap colsspan=2>{last_report}:</td></tr>";

		$f[]="
		<tr>
		<td class=legend style='font-size:22px' nowrap>{date}:</td>
		<td  style='font-size:22px'>$ttime</td>
		</tr>";		
		
		$f[]="
				
		<tr>
		<td class=legend style='font-size:22px' nowrap>{duration}:</td>
		<td  style='font-size:22px'>$duration</td>
		</tr>";
		while (list ($num, $line) = each ($REPORT)){
			
			$f[]="
			<tr>
				<td class=legend style='font-size:22px' nowrap>$num:</td>
				<td  style='font-size:22px'>$line</td>
			</tr>";
		}
		$f[]="
		<td style='font-size:26px' nowrap colspan=2 align='right'>". imgtootltip("delete-42.png","{delete}","ReportDelete()")."</td></tr>		
		</table></div><p>&nbsp;</p>";
	}
	
	
	if(count($f)>0){$report_text=@implode("\n", $f);}
	$t=time();
	$html="
			
	<div style='font-size:32px;margin-bottom:30px'>Stress Tool</div>
	<div style='font-size:18px;margin-bottom:30px' class=explain>{squid_siege_explain} </div>
			$report_text
<div style='width:98%' class=form>
			
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px' nowrap>{get_url_from_lastlogs}:</td>
			<td>". Field_checkbox_design("GRAB_URLS", 1,$ARRAY["GRAB_URLS"])."</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:22px'>{use_local_proxy}:</td>
			<td>". Field_checkbox_design("USE_LOCAL_PROXY", 1,$ARRAY["USE_LOCAL_PROXY"],"USE_LOCAL_PROXY_CHECK()")."</td>
		</tr>		
		<tr>
			<td class=legend style='vertical-align:middle;font-size:22px'>{remote_proxy}:</td>
			<td style='width:70%;vertical-align:middle;'>
				". Field_text("REMOTE_PROXY",$ARRAY["REMOTE_PROXY"],"font-size:22px;width:300px;")."
			</td>
		</tr>			
		<tr>
			<td class=legend style='vertical-align:middle;font-size:22px'>{remote_port}:</td>
			<td style='width:70%;vertical-align:middle;'>
				". Field_text("REMOTE_PROXY_PORT",$ARRAY["REMOTE_PROXY_PORT"],"font-size:22px;width:110px;")."
			</td>
		</tr>
		<tr>
			<td class=legend style='vertical-align:middle;font-size:22px'>{username}:</td>
			<td style='vertical-align:middle;font-size:22px'>
				". Field_text("USERNAME",$ARRAY["USERNAME"],"font-size:22px;width:300px")."
			</td>
		</tr>	
		<tr>
			<td class=legend style='vertical-align:middle;font-size:22px'>{password}:</td>
			<td >
				". Field_password("PASSWORD",$ARRAY["PASSWORD"],"font-size:22px;width:300px;")."
			</td>
		</tr>																
		<tr>
			<td class=legend style='vertical-align:middle;font-size:22px'>{simulate}:</td>
			<td style='vertical-align:middle;font-size:22px'>
				". Field_text("SESSIONS",$ARRAY["SESSIONS"],"font-size:22px;width:110px;")."&nbsp;{members}
			</td>
		</tr>	
		<tr>
			<td class=legend style='vertical-align:middle;font-size:22px' nowrap>{MaxExecutionTime}:</td>
			<td style='vertical-align:middle;font-size:22px'>
				". Field_text("MAX_TIME",$ARRAY["MAX_TIME"],"font-size:22px;width:110px;")."&nbsp;{seconds}
			</td>
		</tr>	
		<tr>
			<td colspan=2 align='right' style='padding-top:20px;font-size:36px'>
						<hr>". button("{launch_test}","Submit$t(true)",36)."&nbsp;|&nbsp;". button("{apply}","Submit$t(false)",36)."</td>
		</tr>
	</table>
	</div>
<div style='margin-bottom:50px'>&nbsp;</div>
<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
	}
	var xSubmit2$t= function (obj) {
		var results=obj.responseText;
		Loadjs('squid.siege.progress.php');
	}	
	var xDelete$t= function (obj) {
		var results=obj.responseText;
		RefreshTab('debug_squid_config');
	}	
	
	function ReportDelete(){
		var XHR = new XHRConnection();	
		XHR.appendData('report-delete','yes');
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	


	function Submit$t(run){
		var XHR = new XHRConnection();	
		
		if(document.getElementById('GRAB_URLS').checked){XHR.appendData('GRAB_URLS',1);}else{XHR.appendData('GRAB_URLS',0);}
		if(document.getElementById('USE_LOCAL_PROXY').checked){XHR.appendData('USE_LOCAL_PROXY',1);}else{XHR.appendData('USE_LOCAL_PROXY',0);}
		XHR.appendData('REMOTE_PROXY',document.getElementById('REMOTE_PROXY').value);
		XHR.appendData('REMOTE_PROXY_PORT',document.getElementById('REMOTE_PROXY_PORT').value);
		
		XHR.appendData('USERNAME',encodeURIComponent(document.getElementById('USERNAME').value));
		XHR.appendData('PASSWORD',encodeURIComponent(document.getElementById('PASSWORD').value));
		
		
		XHR.appendData('SESSIONS',document.getElementById('SESSIONS').value);
		XHR.appendData('MAX_TIME',document.getElementById('MAX_TIME').value);
		if(!run){
			XHR.sendAndLoad('$page', 'POST',xSubmit$t);
		}else{
			XHR.sendAndLoad('$page', 'POST',xSubmit2$t);
		}
	}
	
	
	function USE_LOCAL_PROXY_CHECK(){
		document.getElementById('REMOTE_PROXY').disabled=true;
		document.getElementById('REMOTE_PROXY_PORT').disabled=true;
		if(!document.getElementById('USE_LOCAL_PROXY').checked){
			document.getElementById('REMOTE_PROXY').disabled=false;
			document.getElementById('REMOTE_PROXY_PORT').disabled=false;		
		}
	
	}
	USE_LOCAL_PROXY_CHECK();
	LoadAjax('request-$t','$page?request-tool-status=yes',true);
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidSiegeConfig");
}



function website_tool_post(){
	
	$sock=new sockets();
	$datas=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($datas, "WebSiteAnalysis");
	$sock->getFrameWork("squid.php?WebSiteAnalysis=yes");
	
}

function website_tool_report(){
	
	echo "<textarea style='width:100%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' 
	id='c-icap-error-page'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/curl.trace")."</textarea>";
	
}