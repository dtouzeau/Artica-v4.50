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
	if(!isset($_SESSION["STATS_SEARCH"])){$_SESSION["STATS_SEARCH"]="50 events";}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{realtime_requests}</h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STATS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
			LoadAjax('table-loader','$page?search='+ss);
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
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squidtail-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"])."&SearchString=");
	$filename="/usr/share/artica-postfix/ressources/logs/squidtail.log.tmp";
	
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
	$server=$tpl->javascript_parse_text("{server}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>$server</th>
        	<th>$member</th>
            <th>$proto</th>
            <th colspan=2>$uri</th>
            <th>$size</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["STATS_SEARCH"]=$_GET["search"];}
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
		$xusers=array();
		
		$c++;
		$re=explode(":::", $line);
		
		if(preg_match("#^.*?\):\s+(.+)#", trim($re[0]),$rz)){$re[0]=$rz[1];}
		
		$color="black";
		$mac=trim(strtolower($re[0]));
		if($mac=="-"){$mac==null;}
		$mac=str_replace("-", ":", $mac);
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$ipaddr=trim($re[1]);
		if(!isset($GLOBALS["USER_MEM"])){$GLOBALS["USER_MEM"]=0;}
		$uid=$re[2];
		$uid2=$re[3];
		if($uid=="-"){$uid=null;}
		if($uid2=="-"){$uid2=null;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $uid2)){$uid2=null;}
		if($uid==null){ if($uid2<>null){$uid=$uid2;} }
		
		$zdate=$re[4];
		$xtime=strtotime($zdate);
		if(!is_numeric($xtime)){continue;}
		$SUFFIX_DATE=date("YmdH",$xtime);
		$logzdate=date("Y-m-d H:i:s",$xtime);
		
		
		$proto=$re[5];
		$uri=$re[6];
		$code_error=$re[8];
		$SIZE=$re[9];
		$SquidCode=$re[10];
		$UserAgent=urldecode($re[11]);
		$Forwarded=$re[12];
		$sitename=trim($re[13]);
		$hostname=trim($re[14]);
		$response_time=$re[15];
		$MimeType=trim($re[16]);
		$sni=trim($re[17]);
		$proxyname=trim($re[18]);
		$size=$SIZE;
		$uid=trim(strtolower(str_replace("%20", " ", $uid)));
		$uid=str_replace("%25", "-", $uid);
		if($uid=="-"){$uid=null;}
		$Forwarded=str_replace("%25", "", $Forwarded);
		if($sni=="-"){$sni=null;}
		
		if($logfileD->CACHEDORNOT($SquidCode)){$color="#009223";}
		$codeToString=$logfileD->codeToString($code_error);
		
		if($proto=="CONNECT"){$color="#BAB700";$proto="SSL";}
		if($code_error>399){$color="#D0080A";}
		if($code_error==307){$color="#F59C44";}
		
		if(($proto=="GET") or ($proto=="POST")){
			if(preg_match("#TCP_REDIRECT#", $SquidCode)){
				$color="#A01E1E";
			}
		}
		
		
		
		if(strpos($uid, '$')>0){
			if(substr($uid, strlen($uid)-1,1)=="$"){
				$uid=null;
			}
		}
		
		if($sni<>null){
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){$sitename=$sni;}
		}
		if($proxyname<>null){
			if(preg_match("#proxyname=(.+)#", $proxyname,$re)){
				$proxyname=$re[1];
			}
		}
		
		if($sitename=="-"){
			$h=parse_url($uri);
			if(isset($h["host"])){$sitename=$h["host"]; }
		}
		
		
		if(strpos($sitename, ":")>0){
			$XA=explode(":",$sitename);
			$sitename=$XA[0];
		}
		
		
		if($Forwarded=="unknown"){$Forwarded=null;}
		if($Forwarded=="-"){$Forwarded=null;}
		if($Forwarded=="0.0.0.0"){$Forwarded=null;}
		if($Forwarded=="255.255.255.255"){$Forwarded=null;}
		
		
		if(strlen($Forwarded)>4){
			$ipaddr=$Forwarded;
			$mac=null;
		}
		
		$ipaddr=str_replace("%25", "-", $ipaddr);
		$mac=str_replace("%25", "-", $mac);
		if($mac=="-"){$mac=null;}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		

		if(preg_match("#([0-9:a-z]+)$#", $mac,$z)){$mac=$z[1];}
		
		$xusers[]=$ipaddr;
		if($mac<>null){$xusers[]="$mac";}
		if($uid<>null){$xusers[]="$uid";}
		
		
		$SquidCode=str_replace(":HIER_DIRECT","",$SquidCode);
		
		if($SIZE>1024){$size=FormatBytes($SIZE/1024);}else{$SIZE="$SIZE Bytes";}
		$date=str_replace($today." ", "", $date);

		
		$MAINUTI=$logfileD->parseURL($uri,$CATEGORIES,$proto);
		$link=$MAINUTI["LINK"];
		$uri=$MAINUTI["URL"];
		
		$html[]="<tr>
				<td><span style='color:$color'>$logzdate</span></td>
				<td><span style='color:$color'>$proxyname</span></td>
				<td <span style='color:$color'>". @implode("&nbsp;|&nbsp;", $xusers)."</span></td>
                <td><span style='color:$color'>{$SquidCode}/$code_error/$proto</span></td>  
                <td><span style='color:$color'>$uri</span></td> 
                <td><span style='color:$color'>$link</span></td>   
                <td>{$size}</td>
                </tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";
	//$html[]="<div style='font-size:10px'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/access.log.cmd")."</div>";
	$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo @implode("\n", $html);
	
	
	
}
