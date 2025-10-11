<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{ntlm_processes} #{$_GET["cpu"]}","$page?popup=yes&cpu={$_GET["cpu"]}",890);
	
}

function popup(){
	$page=CurrentPageName();
	echo "<div id='ntlm-process-cpu-{$_GET["cpu"]}'></div>
	<script>LoadAjax('ntlm-process-cpu-{$_GET["cpu"]}','$page?table={$_GET["cpu"]}');</script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]="<table id='table-ntlm-process-cpu-{$_GET["table"]}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >PID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>RQS</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>REPLIES</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>FLAG</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>TIME</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	if(isset($_GET["makeQueryForce"])){$GLOBALS["makeQueryForce"]=true;}
	
	$cpu=$_GET["table"];
	
	$cache_manager=new cache_manager();
	$datas=explode("\n",$cache_manager->makeQuery("ntlmauthenticator"));
	if(!$cache_manager->ok){echo $tpl->FATAL_ERROR_SHOW_128("Err! $cache_manager->errstr");return;}
		
	if(count($datas)==0){
		$cachefile="/etc/artica-postfix/settings/Daemons/makeQuery_ntlmauthenticator";
		if(is_file($cachefile)){$datas=explode("\n",@file_get_contents($cachefile));}
	}
	
	$CPU_NUMBER=0;
	foreach ($datas as $num=>$ligne){
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			continue;
		}
	
		$ligne=trim($ligne);
		if(!preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(B|C|R|S|P|\s)\s+([0-9\.]+)\s+([0-9\.]+)\s+(.*)#",$ligne,$re)){continue;}
	
	
		$ID=$re[1];
		$FD=$re[2];
		$pid=$re[3];
		$rqs=$re[4];
		$rply=$re[5];
		$flags=trim($re[6]);
		$time=$re[7];
		$Offset=$re[8];
		$Request_text=$re[9];
	
		$MAIN[$CPU_NUMBER][$pid]["PID"]=$pid;
		$MAIN[$CPU_NUMBER][$pid]["RQS"]=$rqs;
		$MAIN[$CPU_NUMBER][$pid]["RPLY"]=$rply;
		$MAIN[$CPU_NUMBER][$pid]["FLAG"]=$flags;
		$MAIN[$CPU_NUMBER][$pid]["TIME"]=$time;
			
	}
	
	while (list ($pid, $ligne) = each ($MAIN[$cpu]) ){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md5=md5(serialize($ligne));
		$PID=numberFormat($ligne["PID"],0,""," ");
		$RQS=numberFormat($ligne["RQS"],0,""," ");
		$RPLY=numberFormat($ligne["RPLY"],0,""," ");
		$FLAG=$ligne["FLAG"];
		$TIME=$ligne["TIME"];
	
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td width=1%>$PID</td>";
		$html[]="<td width=33%>$RQS</td>";
		$html[]="<td width=33%>$RPLY</td>";
		$html[]="<td width=1%>$FLAG</td>";
		$html[]="<td width=1%>$TIME</td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<div style='text-align:right'>".$tpl->icon_refresh("LoadAjax('ntlm-process-cpu-{$_GET["table"]}','$page?table={$_GET["table"]}&makeQueryForce=yes');")."</td>" ;
	$html[]="</td>";
	$html[]="</tr>";	
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-ntlm-process-cpu-{$_GET["table"]}').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
