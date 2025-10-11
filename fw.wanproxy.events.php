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
	while (list ($key,$val) = each ($data)){

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
	if(!isset($_SESSION["WANPROXY_DAEMON_SEARCH"])){$_SESSION["WANPROXY_DAEMON_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_WANPROXY} {events}</h1>
		<p>
		</div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WANPROXY_DAEMON_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
		$.address.state('/');
	    $.address.value('/wanproxy-events');	
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
	
	$search=$MAIN["TERM"];
	$MAIN["MAX"]=$MAIN["MAX"]+50;
	$sock->getFrameWork("wanproxy.php?access-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($search)."");
	
	$filename="/usr/share/artica-postfix/ressources/logs/wanproxy-access.log.tmp";
	
	

	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th nowrap>{service}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["WANPROXY_DAEMON_SEARCH"]=$_GET["search"];}
	$IP=new IP();$IPClass=$IP;
	rsort($data);


	
	foreach ($data as $line){
	if(!preg_match("#([0-9]+)\.[0-9]+\s+\[(.+?)\]\s+(.+)#", $line,$re)){continue;}
	$c++;
	$color="black";
	$date=date("Y-m-d H:i:s",$re[1]);
	$service=$re[2];
	$line=$re[3];

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

        if(preg_match("#ERR:#", $line)){$line="<span class='text-warning'>$line</span>";}
        if(preg_match("#No such file or directory#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#WARNING#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#unexpected#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#abnormally#i", $line)){$line="<span class='text-danger'>$line</span>";}
		if(preg_match("#Reconfiguring#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#Accepting (HTTP|NAT|ICP|HTCP|SNMP|SSL)#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#Ready to serve requests#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#Adding\s+#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#No route to host#i", $line)){$line="<span class='text-warning'>$line</span>";}
		if(preg_match("#helperOpenServers: Starting#i", $line)){$line="<span class='text-success'>$line</span>";}
		if(preg_match("#FATAL#i", $line)){$line=str_replace("<span class='text-warning'>", "", $line);$line=str_replace("</span>", "", $line);$line="<span class='text-danger'>$line</span>";}
		
		$html[]="<tr>
				<td nowrap width=1%>$date</span></td>
				<td nowrap  width=1%>$service</span></td>
				<td>$line</td>
				</tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";
	
	$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
