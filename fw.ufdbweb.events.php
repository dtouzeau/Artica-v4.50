<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
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

function ifisright(){
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["WEBT_SEARCH"])){$_SESSION["WEBT_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_UFDB_HTTP}: {events} </h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WEBT_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	include_once('ressources/class.ufdbguard-tools.inc');
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;
	$max=0;$date=null;$c=0;
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock->getFrameWork("ufdbguard.php?ufdbweb-events=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));
	

	
	
	
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
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$proto=$tpl->javascript_parse_text("{proto}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");

	$today=date("Y-m-d");
	$tcp=new IP();


	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>{modules}</th>
        	<th>$ipaddr</th>
            <th>$proto</th>
            <th>{event}</th>
            
        </tr>
  	</thead>
	<tbody>
";
	
	$filename="/usr/share/artica-postfix/ressources/logs/ufdbweb.log.tmp";
	$data=explode("\n",@file_get_contents($filename));

	krsort($data);

	$logfileD=new logfile_daemon();
	$zcat=new squid_familysite();
	

	$c=0;
	foreach ($data as $line){
		$MATCHES=false;
		$line=trim($line);
		if($line==null){continue;}
	
		$c++;
		if(preg_match("#([0-9\-]+)\s+([0-9:]+),[0-9]+\s+\[([A-Z]+)\]\s+([0-9\.]+).*?\"([A-Z]+)\s+(.+?)\s+HTTP#", $line,$re)){
			$date=$re[1]." ".$re[2];
			$INFO=$re[3];
			$MODULE="REQUEST";
			$PROTO=$re[5];
			$line=$re[6];
			$ipaddr=$re[4];
			
			$MATCHES=true;
				
		}
		
		if(!$MATCHES){
			if(preg_match("#([0-9\-]+)\s+([0-9:]+),[0-9]+\s+\[([A-Z]+)\]\s+\[.*?\]\s+(.*?)\s+(.+)#", $line,$re)){
				$date=$re[1]." ".$re[2];
				$INFO=$re[3];
				$MODULE=$re[4];
				$line=$re[5];
				$PROTO="-";
				$MATCHES=true;
				$ipaddr="-";
			}
		}
	
		if(!$MATCHES){continue;}
		
		
		

		
		$html[]="<tr>
				<td nowrap width=1%><span style='color:$color'>$date</span></td>
				<td nowrap width=1%><span style='color:$color'>$INFO/$MODULE</span></td>
				<td nowrap width=1%><span style='color:$color'>$ipaddr</span></td>
                <td nowrap width=1%><span style='color:$color'>{$PROTO}</span></td>                  
                <td><span style='color:$color'>{$line}</span></td>
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
	$html[]="<div>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/ufdbweb.log.cmd")."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}

function categoryCodeTocatz($category){
	if(preg_match("#P([0-9]+)#", $category,$re)){$category=$re[1];}
	if($category==0){return "($category) Unknown(0)";}

	$catz=new mysql_catz(true);
	$categories_descriptions=$catz->categories_descriptions();
	if(!isset($categories_descriptions[$category]["categoryname"])){
		return "($category) <strong>Unkown</strong>";
	}

	$name=$categories_descriptions[$category]["categoryname"];
	$category_description=$categories_descriptions[$category]["category_description"];
	$js="Loadjs('fw.ufdb.categories.php?category-js=$category')";
	return $GLOBALS["TPLZ"]->td_href($name,$category_description,$js);
}
