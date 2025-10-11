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
if(isset($_GET["button"])){button_white();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}


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
	if($users->AsDnsAdministrator){return true;}
	if($users->AsFirewallManager){return true;}
}

function whitelist_js(){
	$familysite=$_GET["whitelist-js"];
	$familysiteenc=urlencode($familysite);
	$md=$_GET["md"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sql="SELECT * FROM webfilter_whitelists WHERE pattern='$familysite'";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$ID=intval($ligne["ID"]);
	if($ID>0){
		$enabled=intval($ligne["enabled"]);
		if($enabled==1){
			$q->QUERY_SQL("UPDATE webfilter_whitelists SET enabled=0 WHERE ID=$ID");
			if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
		}else{
			$q->QUERY_SQL("UPDATE webfilter_whitelists SET enabled=1 WHERE ID=$ID");
			if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
		}
		$sock=new sockets();
		$sock->getFrameWork("unbound.php?reload=yes");
		header("content-type: application/x-javascript");
		echo "LoadAjaxSilent('btn-$md','$page?button=yes&md=$md&familysite=$familysiteenc')";
		
		return;
		
	}
	$sql="INSERT INTO webfilter_whitelists (pattern,enabled,`type`) VALUES('$familysite','1',1)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	$sock=new sockets();
	$sock->getFrameWork("unbound.php?reload=yes");
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('btn-$md','$page?button=yes&md=$md&familysite=$familysiteenc')";
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["WEBF_SEARCH"])){$_SESSION["WEBF_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{DNS_FILTERING}: {blocked_requests} </h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WEBF_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
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

function button_white(){
	$page=CurrentPageName();
	$familysite=$_GET["familysite"];
	$tpl=new template_admin();
	$familysiteEnc=urlencode($familysite);
	$md=$_GET["md"];
	$sql="SELECT * FROM webfilter_whitelists WHERE pattern='$familysite'";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$ID=intval($ligne["ID"]);
	$enabled=intval($ligne["enabled"]);
	
	
	$js="Loadjs('$page?whitelist-js=$familysiteEnc&md=$md')";
	if($enabled==0){
		echo $tpl->button_autnonome("{whitelist}", $js, "fas fa-thumbs-up",null,0,"btn-info","small");
		return;
	}
	
	echo $tpl->button_autnonome("{whitelisted}", $js, "fas fa-thumbs-up",null,0,"btn-primary","small");
		
	
	
	
	
}

function search(){
	include_once('ressources/class.ufdbguard-tools.inc');
	$time=null;
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;
	$max=0;$date=null;$c=0;
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock->getFrameWork("dnsfilterd.php?ufdb-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));
	

	
	
	
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
	$reload_proxy_service=$tpl->_ENGINE_parse_body("{reload_proxy_service}");
	$today=date("Y-m-d");
	$tcp=new IP();
	
	$sql="SELECT * FROM webfilter_whitelists ORDER BY pattern";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$WHITE[$ligne["pattern"]]=1;
	}

	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>$ipaddr</th>
			<th>$rulename</th>
            <th>&nbsp;</th>
            <th>$hostname</th>
          	<th>{unblock}</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$filename="/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.tmp";
	$data=explode("\n",@file_get_contents($filename));

	krsort($data);

	$logfileD=new logfile_daemon();
	$zcat=new squid_familysite();
	

	
	foreach ($data as $line){
		$TR=preg_split("/[\s]+/", $line);
		$md=md5($line);
		if(count($TR)<5){continue;}
		
		$c++;
		$color="black";
		$date=$TR[0];
		$TIME=$TR[1];
		$PID=$TR[2];
		$ALLOW=$TR[3];
		$CLIENT=$TR[4];
		$CLIENT_IP=$TR[5];
		$RULE=$TR[6];
		$CATEGORY=categoryCodeTocatz($TR[7]);
		$URI=$TR[8];
		$PROTO=$TR[9];
		
		$parse=parse_url($URI);
		$hostname=$parse["host"];
		if(!isset($parse["host"])){continue;}
		if($CLIENT==null){$CLIENT="-";}
		
		if($ALLOW=="BLOCK-LD"){$color="#DE8011";}
		if($ALLOW=="BLOCK"){$color="#D0080A";}
		if($ALLOW=="REDIR"){$color="#BAB700";}
		if($ALLOW=="PASS"){$color="#009223";}
		
		$familysite=$zcat->GetFamilySites($hostname);
		$familysiteEnc=urlencode($familysite);
		
	
		
		
		if(preg_match("#([0-9]+)\.addr#", $hostname,$re)){
			$ton=$re[1];
			$ipaddr=long2ip($ton);
			$hostname=str_replace("$ton.addr", $ipaddr , $hostname);
			$URI=str_replace("$ton.addr", $ipaddr, $URI);
		}
		if($date==$today){$date=null;}
		$URI=str_replace("http://", "", $URI);

		$js="Loadjs('$page?whitelist-js=$familysiteEnc&md=$md')";
		$bt=$tpl->button_autnonome("{whitelist}", $js, "fas fa-thumbs-up",null,0,"btn-info","small");
		
		if(isset($WHITE[$familysite])){
			$bt=$tpl->button_autnonome("{whitelisted}", $js, "fas fa-thumbs-up",null,0,"btn-primary","small");
		}
		
		
		
		$html[]="<tr id='$md'>
				<td><span style='color:$color' width=1% nowrap>$date $TIME</span></td>
				<td><span style='color:$color' width=1% nowrap>$CLIENT_IP</span></td>
				<td <span style='color:$color'>$RULE/$CATEGORY</span></td>
                <td><span style='color:$color'width=1% nowrap>{$ALLOW}</span></td>            
                <td><span style='color:$color'>$familysite <small>({$hostname})</small></span></td>
                <td><span style='color:$color' width=1% nowrap><center id='btn-$md'>$bt</center></td>

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
	$html[]="<div>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.cmd")."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo @implode("\n", $html);
	
	
	
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
