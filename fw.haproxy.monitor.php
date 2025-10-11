<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{load_balancing} &nbsp;&raquo;&nbsp; {backends_status}</h1>
	<p>{APP_HAPROXY_BCKSTATS}</p>

	</div>

	</div>



	<div class='row'><div id='progress-haproxy-restart'></div>
	<div class='ibox-content'>

	<div id='table-haproxy-bckstatus'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/lb-status');	
	LoadAjaxSilent('table-haproxy-bckstatus','$page?table=yes');

	</script>";
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function stop_js(){
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("haproxy.php?stop-socket={$_GET["stop-js"]}");
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('table-haproxy-bckstatus','$page?table=yes');";

}

function start_js(){
	$page=CurrentPageName();
	$sock=new sockets();
	header("content-type: application/x-javascript");
	$sock->getFrameWork("haproxy.php?start-socket={$_GET["start-js"]}");
	echo "LoadAjaxSilent('table-haproxy-bckstatus','$page?table=yes');";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$users=new usersMenus();
	$q=new mysql();
	
	$sock=new sockets();
	$sock->getFrameWork("haproxy.php?global-stats=yes");
	$table=explode("\n",@file_get_contents(PROGRESS_DIR."/haproxy.stattus.dmp"));
	if(count($table)<2){
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return;
	}
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"LoadAjaxSilent('table-haproxy-bckstatus','$page?table=yes');\">";
	$html[]="<i class='fal fa-sync-alt'></i> {refresh} </label>";
	$html[]="</div>";
	$html[]="<table id='table-haproxy-chkbalancers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{servicename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{backends}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;&nbsp;&nbsp;&nbsp;OUT&nbsp;&nbsp;&nbsp;&nbsp;</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{requests}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='text-align:right'>{action}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	$statusmsg["UNK"]="unknown";
    $statusmsg["INI"]="initializing";
    $statusmsg["SOCKERR"]="socket error";
    $statusmsg["L4OK"]="check passed on layer 4, no upper layers testing enabled";
    $statusmsg["L4TMOUT"]="layer 1-4 timeout";
    $statusmsg["L4CON"]="layer 1-4 connection problem";
    $statusmsg["L6OK"]="check passed on layer 6";
    $statusmsg["L6TOUT"]="layer 6 (SSL) timeout";
    $statusmsg["L6RSP"]="layer 6 invalid response - protocol error";
    $statusmsg["L7OK"]="check passed on layer 7";
    $statusmsg["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
    $statusmsg["L7TOUT"]="layer 7 (HTTP/SMTP) timeout";
    $statusmsg["L7RSP"]="layer 7 invalid response - protocol error";
    $statusmsg["L7STS"]="layer 7 response error, for example HTTP 5xx";
	
	$ERR["SOCKERR"]=true;
	$ERR["L4TMOUT"]=true;
	$ERR["L4CON"]=true;
	$ERR["L6TOUT"]=true;
	$ERR["L6RSP"]=true;
	$ERR["L7TOUT"]=true;
	$ERR["L7RSP"]=true;
	$ERR["L7STS"]=true;
	$TRCLASS=null;
	$q=new mysql();
	$typof=array(0=>"frontend", 1=>"backend", 2=>"server", 3=>"socket");
	foreach ($table as $num=>$ligne){
		
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#\##", $ligne)){continue;}
		$f=explode(",", $ligne);
		$pxname=$f[0];
		$svname=$f[1];
		$qcur=$f[2];
		$qmax=$f[3];
		$scur=$f[4];
		$smax=$f[5];
		$slim=$f[6];
		$stot=$f[7];
		$bin=FormatBytes($f[8]/1024);
		$bout=FormatBytes($f[9]/1024);
		$dreq=$f[10];
		$dresp=$f[11];
		$ereq=$f[12];
		$econ=$f[13];
		$eresp=$f[14];
		$wretr=$f[15];
		$wredis=$f[16];
		$status=$f[17];
		$weight=$f[18];
		$act=$f[19];
		$bck=$f[20];
		$chkfail=$f[21];
		$chkdown=$f[22];
		$lastchg=$f[23];
		$downtime=$f[24];
		$qlimit=$f[25];
		$pid=$f[26];
		$iid=$f[27];
		$sid=$f[28];
		$throttle=$f[29];
		$lbtot=$f[30];
		$tracked=$f[31];
		$type=$typof[$f[32]];
		$rate=$f[33];
		$rate_lim=$f[34];
		$rate_max=$f[35];
		$check_status=$f[36];
		$check_code=$f[37];
		$check_duration=$f[38];
		$hrsp_1xx=intval($f[39]);
		$hrsp_2xx=intval($f[40]);
		$hrsp_3xx=intval($f[41]);
		$hrsp_4xx=intval($f[42]);
		$hrsp_5xx=intval($f[43]);
		$hrsp_other=intval($f[44]);
		$hanafail=$f[45];
		$req_rate=$f[46];
		$req_rate_max=$f[47];
		$req_tot=intval($f[48]);
		$cli_abrt=$f[49];
		$srv_abrt=$f[50];
		if(!is_numeric($req_tot)){$req_tot=0;}
		$img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";

		if(preg_match("#haproxy\.stat#",$svname)){continue;}
		
		$padding=null;
		$color="black";
		$check_status_text=$statusmsg[$check_status];
		if(isset($ERR[$check_status])){$img="<div class='label label-danger' style='display:block;padding:5px'>$check_status</div>";$color="#D20C0C";}
		$md5=md5($ligne);
		$servicename=null;
		$servicename_text=null;
		$button=null;
		$h2=null;
		$h22=null;$js=null;$ico=null;
		$backendtot=$hrsp_1xx+$hrsp_2xx+$hrsp_3xx+$hrsp_4xx+$hrsp_5xx+$hrsp_other;
		$arraySRV=base64_encode(serialize(array($pxname,$svname)));
		if($type=="frontend"){
			$h2="<H2>";
			$h22="</H2>";
			$ico="<i class='fa fa-share-alt'></i>&nbsp;";
			$FRONTEND=$pxname;}
		
		
		if($type=="server"){
			$ico="<i class='fa fa-desktop'></i>&nbsp;";
			$button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$arraySRV')\">{stop}</button>";
			if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}
				
		}
		
		if($status=="MAINT"){
			$color="#F8AC59";
			$img="<div class='label label-warning' style='display:block;padding:5px'>{maintenance}</div>";
			$button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?start-js=$arraySRV')\">{start}</button>";
			if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}
			
		}
		
		if(preg_match("#DOWN#", $status)){
			$downser="HaProxyUpserv('$arraySRV');";
			$button=null;
			$img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}</div>";
			$color="#D20C0C";
			$button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-js=$arraySRV')\">{start}</button>";
			if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}
			
		}
		if($req_tot==0){
			if($backendtot>0){$req_tot=$backendtot;}
		}
			
		if($type=="backend"){continue;}
		if($pxname=="admin_page"){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$svname_label=$svname;
		if(preg_match("#^default_(.+)#", $pxname,$re)){$servicename=$re[1];}
		
		if(preg_match("#backendid([0-9]+)$#", $svname,$re)){
			$backend_id=$re[1];
			$sql="SELECT backendname from haproxy_backends WHERE ID='$backend_id'";
			$ligne_sql=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$svname_label=$ligne_sql["backendname"];
		}
		
		if($servicename<>null){$servicename_text=$servicename;}else{$servicename_text=$pxname;}
		if($type<>"frontend"){
			if($FRONTEND==$servicename){
				$padding="padding-left:100px;";
				$js="Loadjs('fw.haproxy.backends.php?backend-js=$svname_label&servicename=$servicename');";
				$servicename_text=$tpl->td_href($svname_label,"$servicename/$svname_label",$js);}
		}
		
		
		$req_tot=FormatNumber($req_tot);
		
		$html[]="<tr class='$TRCLASS' id='$md5'>";
		$html[]="<td width=1% nowrap>$img</td>";
		$html[]="<td><strong style=';color:$color;$padding'>$h2$ico$servicename_text$h22</strong></td>";
		$html[]="<td style='width:1%;color:$color' nowrap>$svname_label ($type - $status)</a></td>";
		$html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$bin</td>";
		$html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$bout</td>";
		$html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$req_tot</td>";
		$html[]="<td style='width:1%;color:$color;padding-left:35px' nowrap>$button</td>";
		$html[]="</tr>";
	}
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-haproxy-chkbalancers').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}