<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.greensql.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["agroupid-start"])) {table_start();exit;}
if(isset($_GET["search"])) {table();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new green_sql();
	
	$agroupid=$_GET["agroupid"];
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT db_name,proxyid FROM alert_group WHERE agroupid='$agroupid'"));
	$db_name=$ligne["db_name"];
	$proxyid=intval($ligne["proxyid"]);
	$proxyname=$q->GET_PROXY_NAME($proxyid);
	
	
	$title="$proxyname >> $db_name";
	$tpl->js_dialog2($title, "$page?agroupid-start=$agroupid");
}

function table_start(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$agroupid=$_GET["agroupid"]=$_GET["agroupid-start"];
	$html[]=$tpl->search_block($page,"mysql_green","alert","greensql-alerts2","agroupid=$agroupid");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new green_sql();
	$t=time();


	$html[]="<table id='table-greensql-alerts' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' nowrap>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{query}/{explain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),array());
	if($search["MAX"]==0){$search["MAX"]=150;}
	$sql="SELECT * FROM alert {$search["Q"]}ORDER BY event_time DESC LIMIT 0,{$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br>$sql");return;}

	$zstatus[0]="<span class='label label-warning'>{warning}</span>";
	$zstatus[1]="<span class='label label-danger'>{block}</span>";
	$zstatus[2]="<span class='label label-danger'>{high_risk}</span>";
	$zstatus[3]="<span class='label label-info'>{info}</span>";
	$zstatus[4]="<span class='label label-warning'>{error}</span>";


	$TRCLASS=null;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$date=strtotime($ligne["event_time"]);
		$date_text=$tpl->time_to_date($date,true);
		$risk=$ligne["risk"];
		$dbuser=$ligne["dbuser"];
		$userip=$ligne["userip"];
		$query=$ligne["query"];
		$reason=$ligne["reason"];

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap>{$date_text}</td>";
		$html[]="<td width=1% nowrap>{$zstatus[$ligne["block"]]}</td>";
		$html[]="<td width=1% nowrap>$dbuser</td>";
		$html[]="<td  width=1% nowrap>$userip</td>";
		$html[]="<td>$query<br><small>$reason</small></td>";
		$html[]="</tr>";

	}


	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div><small>$sql</small></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-greensql-alerts').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}