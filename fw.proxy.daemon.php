<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}

page();

function zoom_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$data=urlencode($_GET["data"]);
	$title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
	$tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}
function zoom(){

	$data=unserialize(base64_decode($_GET["data"]));
$html[]="<div class=ibox-content>";
$html[]="<table class='table table table-bordered'>";
    foreach ($data as $key=>$val){
		$html[]="<tr>
		<td class=text-capitalize>$key:</td>
		<td><strong>$val</strong></td>
		</tr>";

	}

	echo @implode("", $html)."</table></div>";

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $addPLUS=null;
	if(!isset($_SESSION["PROXY_DAEMON_SEARCH"])){$_SESSION["PROXY_DAEMON_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{proxy_events}</h1>
		<p>{proxy_events_explain}
		</div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PROXY_DAEMON_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-firehol-restart'></div>
		<div  class='ibox-content'>
		<div class='sk-spinner sk-spinner-wave'>
			<div class='sk-rect1'></div>
			<div class='sk-rect2'></div>
			<div class='sk-rect3'></div>
			<div class='sk-rect4'></div>
			<div class='sk-rect5'></div>
		</div>
		
		
			<div id='table-loader'></div>
		</div>
	</div>
	</div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock=new sockets();
	
	$search=base64_encode($MAIN["TERM"]);
	$MAIN["MAX"]=$MAIN["MAX"]+50;
	$sock->getFrameWork("squid.php?cachelogs=$search&rp={$MAIN["MAX"]}");
	$filename=PROGRESS_DIR."/squid-cache.log";
	
	
	$categories=$tpl->javascript_parse_text("{categories}");
	$ipaddr=$tpl->javascript_parse_text("{members}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$autorefresh=$tpl->javascript_parse_text("{autorefresh}");
	$hostnames=$tpl->javascript_parse_text("{hostnames}");
	$options=$tpl->javascript_parse_text("{options}");
	$all=$tpl->javascript_parse_text("{all}");
	$back=$tpl->javascript_parse_text("{back}");
	$destination=$tpl->javascript_parse_text("{destinations}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";

	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["PROXY_DAEMON_SEARCH"]=$_GET["search"];}
	$zcat=new squid_familysite();
	$logfileD=new logfile_daemon();
	$q=new mysql_squid_builder();
	$catz=new mysql_catz();
	$IP=new IP();$IPClass=$IP;
	$today=date("Y-m-d");
	rsort($data);
	$MyPage=CurrentPageName();

	
	foreach ($data as $line){
	$date="&nbsp;";
	$datetext="&nbsp;";
		if(preg_match("#^([0-9\.\/\s+:]+)\s+#",$line,$re)){
			$line=str_replace($re[1],"",$line);
			$date=strtotime($re[1]);
			$datetext=$tpl->time_to_date($date,true);
		
			
		}
		if(preg_match("#already defined. Ignoring#i", $line)){continue;}
		if(preg_match("#Socket created at#i", $line)){continue;}
		if(preg_match("#Sending .*? messages from#i", $line)){continue;}
		if(preg_match("#Stop sending#i", $line)){continue;}
		if(preg_match("#Set Current#i", $line)){continue;}
		if(preg_match("#Pinger exiting#i", $line)){continue;}
		if(preg_match("#Logfile: closing log#i", $line)){continue;}
		if(preg_match("#Logfile: opening#i", $line)){continue;}
		if(preg_match("#Startup: Initializ#i", $line)){continue;}
		if(preg_match("#Processing Configuration File#i", $line)){continue;}
		if(preg_match("#You should probably remove#i", $line)){continue;}
		if(preg_match("#is ignored to keep splay#i", $line)){continue;}
		if(preg_match("#is a subnetwork of#i", $line)){continue;}
		if(preg_match("#violates HTTP#i", $line)){continue;}
		if(preg_match("#empty ACL#i", $line)){continue;}
        if(preg_match("#can't initialize#",$line)){$line="<span class='text-danger'>$line</span>";}
		if(preg_match("#No such file or directory#i", $line)){$line="<span class='text-warning'>$line</span>";}
        if(preg_match("#TCP connection to .*?failed#",$line)){$line="<span class='text-danger'>$line</span>";}
		if(preg_match("#WARNING#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#unexpected#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#abnormally#i", $line)){$line="<span class='text-danger'>$line</span>";}
		if(preg_match("#Reconfiguring#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#Accepting (HTTP|NAT|ICP|HTCP|SNMP|SSL)#i", $line)){$line="<span class='text-success'>$line</span>";}
        if(preg_match("#(received type 1 NTLM token|Unspecified GSS failure)#i", $line)){$line="<span class='font-bold text-danger'>$line</span>";}
        if(strpos($line,"Detected REVIVED")>0){$line="<span class='text-warning'>$line</span>";}
        if(strpos($line,"Detected DEAD")>0){$line="<span class='text-danger'>$line</span>";}
        if(strpos($line,"failure while accepting a TLS connection")>0){$line="<span class='text-warning'>$line</span>";}

		if(preg_match("#Ready to serve requests#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#Adding\s+#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#No route to host#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#helperOpenServers: Starting#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#FATAL#i", $line)){$line=str_replace("<span class='text-warning'>", "", $line);$line=str_replace("</span>", "", $line);$line="<span class='text-danger'>$line</span>";}
		if(preg_match("#temporarily unavailable#i", $line)){$line=str_replace("<span class='text-warning'>", "", $line);$line=str_replace("</span>", "", $line);$line="<span class='text-danger'>$line</span>";}
		$html[]="<tr>
				<td>$datetext</span></td>
				<td>$line</td>
				</tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";
	$html[]="<div style='font-size:10px'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/access.log.cmd")."</div>";
	$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo @implode("\n", $html);
	
	
	
}
