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
	if(!isset($_SESSION["ZIPROXY_DAEMON_SEARCH"])){$_SESSION["ZIPROXY_DAEMON_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_ZIPROXY} &raquo;&raquo; {events}</h1>
		<p>
		</div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["ZIPROXY_DAEMON_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	
	$search=$MAIN["TERM"];
	$MAIN["MAX"]=$MAIN["MAX"]+50;
	
	$sock->getFrameWork("squid.php?zipproxy-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($search));
	$filename="/usr/share/artica-postfix/ressources/logs/zipproxy-access.log.tmp";
	
	
	$categories=$tpl->javascript_parse_text("{categories}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
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
	$proto=$tpl->javascript_parse_text("{proto}");
	$event=$tpl->_ENGINE_parse_body("{event}");
	$reload_proxy_service=$tpl->_ENGINE_parse_body("{reload_proxy_service}");
	$compressed=$tpl->_ENGINE_parse_body("{compressed}");
	$ratio=$tpl->_ENGINE_parse_body("{ratio}");
	

	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>$zdate</th>
        	<th nowrap>$ipaddr</th>
        	<th nowrap>$proto</th>
        	<th nowrap>$uri</th>
        	<th nowrap>$size</th>
        	<th nowrap>$compressed</th>
        	<th nowrap>{$ratio}%</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["ZIPROXY_DAEMON_SEARCH"]=$_GET["search"];}
	$zcat=new squid_familysite();
	$logfileD=new logfile_daemon();
	$q=new mysql_squid_builder();
	$catz=new mysql_catz();
	$IP=new IP();$IPClass=$IP;
	$today=date("Y-m-d");
	rsort($data);
	$CATEGORIES=$MAIN["CATEGORIES"];
	$DESTINATIONS=$MAIN["DESTINATIONS"];
	$MyPage=CurrentPageName();

	
	foreach ($data as $line){
		$TR=preg_split("/[\s,]+/", $line);
		if($GLOBALS["VERBOSE"]){print_r($TR);}
		if(count($TR)<5){continue;}
		$color="black";
		$date=date("Y-m-d H:i:s",$TR[0]);
		$ip=$TR[2];
		$size=$TR[4];
		$sizeCompressed=$TR[5];
		$PROTO=$TR[6];
		$uri=$TR[7];
		$ratio=0;
		if($PROTO=="CONNECT"){$color="#BAB700";}
		if($PROTO<>"CONNECT"){
			$color="#A5A5A5";
			if($sizeCompressed<$size){
				$ratio=($sizeCompressed/$size)*100;
				$ratio=round($ratio,2);
				$color="black";
			}
		}
		
		
		if($size>1024){
			$size=FormatBytes($size/1024);}
			else{
				$size="$size Bytes";
			}
			if($sizeCompressed>1024){$sizeCompressed=FormatBytes($sizeCompressed/1024);}else{$sizeCompressed="$sizeCompressed Bytes";}
			$date=str_replace($today." ", "", $date);

		
		
		$html[]="<tr>
				<td nowrap width=1%>$date</span></td>
				<td nowrap  width=1%>$ip</span></td>
				<td nowrap  width=1%>$PROTO</span></td>
				<td>$uri</span></td>
				<td nowrap  width=1%>$size</span></td>
				<td nowrap  width=1%>$sizeCompressed</span></td>
				<td nowrap  width=1%>{$ratio}%</span></td>
				</tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
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
