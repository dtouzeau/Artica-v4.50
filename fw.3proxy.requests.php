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

		$GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?reload=yes");
		header("content-type: application/x-javascript");
		echo "LoadAjaxSilent('btn-$md','$page?button=yes&md=$md&familysite=$familysiteenc')";
		
		return;
		
	}
	$sql="INSERT INTO webfilter_whitelists (pattern,enabled,`type`) VALUES('$familysite','1',1)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?reload=yes");
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('btn-$md','$page?button=yes&md=$md&familysite=$familysiteenc')";
	
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_3PROXY}: {requests}",ico_eye,"{APP_3PROXY_EXPLAIN}",
        "$page?tabs=yes","universal-proxy-requests","universal-proxy-progress",true,"table-loader-proxy-service");

    $tpl=new template_admin("{APP_3PROXY}: {requests}",$html);

    if(isset($_GET["main-page"])){
        echo $tpl->build_firewall();
        return;
    }
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
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;


    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));

    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}

    $EndPoint="/3proxy/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);


	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{time}</th>
        	<th nowrap>{service2}</th>
			<th nowrap>{local_port}</th>
            <th nowrap>{status}</th>
            <th nowrap>{source}</th>
          	<th nowrap>{destination}</th>
			<th nowrap>{size}</th>
			<th nowrap>{requests}</th>
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

        $json=json_decode($line);
        $destination_port_text=null;
		$destination_proto=null;
		$time=$json->timestamp;
		$service=$json->proxy_type;
		$service_port=$json->proxy_port;
		$errorInt=intval($json->event);
		$ERROR=ERRORS_TO_STRING($errorInt);
		$username=$json->username;
		$srcip=$json->client_ip;
		$destination=$json->remote_ip;
		$destination_port=$json->remote_port;
		$bytesSentToTarget=$json->bytes_sent;
		$bytesReceivedFromTarget=$json->bytes_received;
        $URL="";
        $arrayURI=parse_url($json->url);

        $zuri=array();
        if($arrayURI) {
            if (isset($arrayURI["scheme"])) {
                $zuri[] = $arrayURI["scheme"] . "//";
            }
            if (isset($arrayURI["host"])) {
                $zuri[] = $arrayURI["host"];
            }
            if (isset($arrayURI["port"])) {
                $zuri[] = ":" . $arrayURI["port"];
            }
            if (isset($arrayURI["path"])) {
                if(strlen($arrayURI["path"])>1) {
                    $zuri[] = "/" . $arrayURI["path"];
                }
            }
            if (count($zuri) > 0) {
                $URL = @implode("", $zuri);
            }
        }

		if($username=="-"){
            $username="";
        }

		$DatasTotal=$bytesSentToTarget+$bytesReceivedFromTarget;
		$DatasTotal=FormatBytes($DatasTotal/1024);
		
		if($destination_port>0){
			$destination_port_text=":{$destination_port}";
		}
		
		$md=md5($line);
		
		if(preg_match("#([A-Z]+)\s+(.+?)\s+HTTP#", $URL,$rz)){
			$URL=$rz[2];
			$destination_proto=" ({$rz[1]})";
			if($rz[1]=="CONNECT"){
				$color="#BAB700";
				$destination_proto="  ({$rz[1]}/SSL)";
			}
			
			if(preg_match("#(.+?):443#", $URL,$rz)){
				$URL="https://{$rz[1]}";
			}
			
		}
		
		if($destination=="0.0.0.0"){
			$color="#BFBFBF";
		}
		
		if($errorInt>0){
			$color="#D0080A";
		}
		$zdate=$tpl->time_to_date($time,true);
        $suser=array();
        $suser[]=$srcip;
        if(strlen($username)>1){
            $suser[]=$username;
        }
		
		$html[]="<tr id='$md'>
				<td style='color:$color;width:1%' nowrap>$zdate</td>
				<td style='color:$color;width:1%' nowrap>$service</td>
				<td style='color:$color;width:1%' nowrap>$service_port</td>
				<td style='color:$color;width:1%' nowrap>$ERROR</td>
                <td style='color:$color;width:1%' nowrap>".@implode("/",$suser)."</td>            
                <td style='color:$color'>$destination$destination_port_text$destination_proto</td>
                <td style='color:$color;width:1%' nowrap>$DatasTotal</td>
                <td style='color:$color;width:1%' nowrap>$URL</td>
                </tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
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
