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
	if(!isset($_SESSION["CLUSTER_SEARCH"])){$_SESSION["CLUSTER_SEARCH"]="50 events";}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{cluster_events}</h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["CLUSTER_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	$sock->getFrameWork("squid.php?cluster-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"])."&SearchString={$_GET["SearchString"]}&FinderList={$_GET["FinderList"]}");
	$filename="/usr/share/artica-postfix/ressources/logs/cluster.log.tmp";
	$categories=$tpl->javascript_parse_text("{categories}");
	$ipaddr=$tpl->javascript_parse_text("{members}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$autorefresh=$tpl->javascript_parse_text("{autorefresh}");
	$hostnames=$tpl->javascript_parse_text("{hostnames}");
	$options=$tpl->javascript_parse_text("{options}");
	$all=$tpl->javascript_parse_text("{all}");
	$back=$tpl->javascript_parse_text("{back}");
	$function=$tpl->javascript_parse_text("{function}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$load=$tpl->_ENGINE_parse_body("{load}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$line=$tpl->_ENGINE_parse_body("{line}");
	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th data-sortable=true class='text-capitalize'>$zdate</th>
        	<th data-sortable=true class='text-capitalize'>$function</th>
        	<th data-sortable=true class='text-capitalize'>$line</th>
        	<th data-sortable=true class='text-capitalize'>PID</th>
			<th data-sortable=true class='text-capitalize'>$events</th>
			<th data-sortable=true class='text-capitalize'>$memory</th>
			<th data-sortable=true class='text-capitalize'>$load</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["CLUSTER_SEARCH"]=$_GET["search"];}
	
	$today=date("Y-m-d");
	rsort($data);
	$MyPage=CurrentPageName();
	$TRCLASS=null;
	
	foreach ($data as $line){
		$ArrayENC=array();
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		if(!preg_match("#(.+?)\s+\[([0-9]+)\]\s+(.+?):\s+(.+?)- function:(.+?)\s+in line:([0-9]+) Mem:(.*?)\s+Load:([0-9\.]+)#", $line,$re)){continue;}
			$color="black";
			$date=$re[1];
			$pid=$re[2];
			$process=$re[3];
			$event=$re[4];
			$function=$re[5];
			$line=$re[6];
			$memory=$re[7];
			$load=$re[8];
			$date=str_replace($today." ", "", $date);

		
		$html[]="<tr class='$TRCLASS'>
				<td><span style='color:$color'>$date</span></td>
				<td><span style='color:$color'>$function</span></td>
				<td><span style='color:$color'>$line</span></td>
				<td> <span style='color:$color'>$pid</span></td>
                <td><span style='color:$color'>$event</span></td>  
                <td><span style='color:$color'>$memory</span></td>  
                <td><span style='color:$color'>$load</span></td>                  
                </tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";
	$html[]="<div style='font-size:10px'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/cluster.log.cmd")."</div>";
	$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo @implode("\n", $html);
	
	
	
}
