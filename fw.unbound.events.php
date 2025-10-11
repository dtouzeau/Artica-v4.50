<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["button"])){button_white();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}


page();




function ifisright(){
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
}



function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_UNBOUND}: {events}",ico_eye,"{dns_cache_explain}",
        "$page","unbound-events-requests","unbound-events-progress",true,"table-loader-proxy-service");

    $tpl=new template_admin("{APP_UNBOUND}: {events}",$html);

    if(isset($_GET["main-page"])){
        echo $tpl->build_firewall();
        return;
    }
	echo $tpl->_ENGINE_parse_body($html);

}



function search(){
	include_once('ressources/class.ufdbguard-tools.inc');
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;


    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));

    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}

    $EndPoint="/unbound/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);

    $LEVELS["info"]="<span class='label label-default'>INFO</span>";
    $LEVELS["warning"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["rpz"]="<span class='label label-warning'>RPZ.</span>";
    $LEVELS["error"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["fatal"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["debug"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["trace"]="<span class='label label-default'>TRACE</span>";
	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{time}</th>
        	<th nowrap>{type}</th>
			<th nowrap>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    foreach ($json->Logs as $line){
		$color="inherit";

        if(!preg_match("#(.+?)\s+unbound.*?\]\s+([a-z]+):\s+(.+)#",$line,$re)){
            continue;
        }
        $md=md5($line);
        $date=$re[1];
        $type=$re[2];
        $event=$re[3];
        if(strpos(" $line","rpz: applied [")>0){
            $type="rpz";
        }
		
		$html[]="<tr id='$md'>
				<td style='color:$color;width:1%' nowrap>$date</td>
				<td style='color:$color;width:1%' nowrap>$LEVELS[$type]</td>
				<td style='color:$color;width:99%'>$event</td>
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
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
	
	
}



function ERRORS_TO_STRING($CODE){
	
	
	if(isset($_SESSION["3PROXY_ERRORS"])){
		return $_SESSION["3PROXY_ERRORS"][$CODE];
	}
	
	$ERRORS["0"]="{success}";
	$ERRORS["1-9"]="AUTHENTICATION ERRORS";
	$ERRORS["1"]="Access denied by ACL (deny)";
	$ERRORS["2"]="Redirection (should not appear)";
	$ERRORS["3"]="No ACL found, denied by default";
	$ERRORS["4"]="auth=strong and no username in request";
	$ERRORS["5"]="auth=strong and no matching username in configuration";
	$ERRORS["6"]="User found, wrong password (cleartext)";
	$ERRORS["7"]="User found, wrong password (crypt)";
	$ERRORS["8"]="User found, wrong password (NT)";
	$ERRORS["9"]="Redirection data not found (should not appear)";
	$ERRORS["10"]="Traffic limit exceeded";
	$ERRORS["11"]="failed to create socket()";
	$ERRORS["12"]="failed to bind()";
	$ERRORS["13"]="failed to connect()";
	$ERRORS["14"]="failed to getpeername()";
	for($i=15;$i<20;$i++){$ERRORS[$i]="CONNECTION ERRORS";}
	$ERRORS["21"]="memory allocation failed";
	for($i=22;$i<30;$i++){$ERRORS[$i]="COMMON ERRORS";}
	
	$ERRORS["31"]="failed to request HTTP CONNECT proxy";
	$ERRORS["32"]="CONNECT proxy connection timed out or wrong reply";
	$ERRORS["33"]="CONNECT proxy fails to establish connection";
	$ERRORS["34"]="CONNECT proxy timed out or closed connection";
	for($i=35;$i<40;$i++){$ERRORS[$i]="CONNECT PROXY REDIRECTION ERRORS";}
	
	for($i=40;$i<50;$i++){$ERRORS[$i]="SOCKS4 PROXY REDIRECTION ERRORS";}
	for($i=50;$i<70;$i++){$ERRORS[$i]="SOCKS5 PROXY REDIRECTION ERRORS";}
	for($i=70;$i<80;$i++){$ERRORS[$i]="PARENT PROXY CONNECTION ERRORS";}
	$ERRORS["90"]="socket error or connection broken";
	$ERRORS["91"]="{TCPIP_common_failure}";
	$ERRORS["92"]="{connection_timed_out}";
	$ERRORS["93"]="{error_on_reading_data_from_server}";
	$ERRORS["94"]="{error_on_reading_data_from_client}";
	$ERRORS["95"]="timeout from bandlimin/bandlimout limitations";
	$ERRORS["96"]="error on sending data to client";
	$ERRORS["97"]="error on sending data to server";
	$ERRORS["98"]="server data limit (should not appear)";
	$ERRORS["99"]="client data limit (should not appear)";
	$ERRORS["100"]="{host_not_found}";
	
	for($i=200;$i<300;$i++){$ERRORS[$i]="UDP portmapper specific bugs";}
	for($i=300;$i<400;$i++){$ERRORS[$i]="TCP portmapper specific bugs";}
	for($i=400;$i<500;$i++){$ERRORS[$i]="SOCKS proxy specific bugs";}
	for($i=500;$i<600;$i++){$ERRORS[$i]="HTTP proxy specific bugs";}
	for($i=600;$i<700;$i++){$ERRORS[$i]="POP3 proxy specific bugs";}
	$ERRORS["999"]="NOT IMPLEMENTED";
	
	$_SESSION["3PROXY_ERRORS"]=$ERRORS;
	return $_SESSION["3PROXY_ERRORS"][$CODE];
}
