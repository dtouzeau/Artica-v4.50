<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["download"])){download();exit;}
if(isset($_GET["kill"])){kill_session();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_POST["connection_name"])){buildconfig();exit;}
page();



function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $html=$tpl->page_header("{APP_OPENVPN} v$OpenVPNVersion {sessions}",
        ico_users,"{display_current_sessions}","$page?main-start=yes",
        "openvpn-sessions","progress-openvpn-restart",false,"table-connections");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function main_start():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&main=yes");
    return true;
}
function findlocalIP($ROUTING,$CommonName):string{
    foreach ($ROUTING as $json){
		if($json->CommonName==$CommonName) {
            return $json->VirtualAddress;
        }
		
	}
	
	return "";
}
function main(){
	$tpl=new template_admin();
    $date=$tpl->javascript_parse_text("{connection_date}");
	$userid=$tpl->javascript_parse_text("{member}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$BytesReceived=$tpl->javascript_parse_text("{received}");
	$BytesSent=$tpl->javascript_parse_text("{sended}");
	$local_ip_address=$tpl->javascript_parse_text("{local_ip_address}");



	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$userid}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$local_ip_address}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$BytesReceived}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$BytesSent}</th>";
	$html[]="<th data-sortable=false data-type='html'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$xtpl=new templates();

/*
    if(!isset($GLOBALS["CONNECTIONS"][$uid])){
        $ligne2=$q->mysqli_fetch_array("SELECT connection FROM memberlinks WHERE username='$uid'");
        print_r($ligne2);
        if(!$q->ok){
            echo $q->mysql_error;
        }
        $GLOBALS["CONNECTIONS"][$uid]=$ligne2["connection"];

    }
*/
    $sock=new sockets();
    $data=$sock->REST_API("/openvpn/session/scan");


    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());

    }
    if(!$json->Status){
        $tpl->div_error($json->Error);
    }
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $Users=$json->List->Users;
	$Routing=$json->List->Routing;
    $Sessions=0;
    $TotalBytes=0;
    $search=$_GET["search"];
	foreach ($Users as $object){
    	$CommonName=$object->CommonName;
        $TotalBytes=$TotalBytes+($object->BytesReceived+$object->BytesSent);

		$BytesReceived=FormatBytes($object->BytesReceived/1024);
		$BytesSent=FormatBytes($object->BytesSent/1024);
		$ConnectedSince= strtotime($object->ConnectedSince);
		$ConnectedSinceDate=$xtpl->time_to_date($ConnectedSince,true);
		$ConnectedSinceT=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($ConnectedSince,time()));
        $publicip=$object->RealAddress;
		$localip=findlocalIP($Routing,$CommonName);
        // Pentest 2026-11-04 : CommonName (certificat client, donnee externe) etait concatene dans la requete.
        $res=$q->QUERY_SQL_PARAMS("SELECT connection FROM memberlinks WHERE username=?",array((string)$CommonName));
        $ligne=(is_array($res) && isset($res[0]))?$res[0]:array("connection"=>"");
        $ConnectionName=$ligne["connection"];
        if(strlen($search)>0){
            if(!preg_match("#$search#","$publicip $CommonName $ConnectionName")){
                continue;
            }
        }



        $Sessions++;
		$md=md5(serialize($object));
		$cnenc=base64_encode($CommonName);
		$killicon="<a href=\"javascript:blur();\" OnClick=\"OpenVPNKill('$cnenc')\" title='{disconnect}'><i class='fa fa-times-circle' style='color:#C0392B;font-size:16px'></i></a>";
		// Pentest 2026-11-04 : CommonName/adresses/connexion (donnee externe) affiches sans encodage (XSS stockee).
		$CommonName_h=req_html($CommonName);
		$ConnectionName_h=req_html($ConnectionName);
		$localip_h=req_html($localip);
		$publicip_h=req_html($publicip);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><span class='fa fa-clock'></span>&nbsp;{$ConnectedSinceDate} ($ConnectedSinceT)</td>";
		$html[]="<td><span class='fa fa-user'></span>&nbsp;{$CommonName_h} <strong>$ConnectionName_h</strong></td>";
		$html[]="<td><span class='fa fa-desktop'></span>&nbsp;$localip_h</td>";
		$html[]="<td><span class='fa fa-desktop'></span>&nbsp;{$publicip_h}</td>";
		$html[]="<td><span class='fa fa-tachometer'></span>&nbsp;{$BytesReceived}</td>";
		$html[]="<td><span class='fa fa-tachometer'></span>&nbsp;{$BytesSent}</td>";
		$html[]="<td style='text-align:center'>$killicon</td>";

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

    $bytes=FormatBytes($TotalBytes/1024);
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $TINY_ARRAY["TITLE"]="{APP_OPENVPN} $Sessions {sessions} / $bytes";
    $TINY_ARRAY["ICO"]=ico_users;
    $TINY_ARRAY["EXPL"]="{display_current_sessions}";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $page=CurrentPageName();
    $search_js=addslashes($search);
    $confirm_disconnect=$tpl->javascript_parse_text("{disconnect} {this_member} ?");
    $html[]="<div id='openvpn-kill-status' style='display:none'></div>";
    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": {\"enabled\": false}, \"sorting\": {
	\"enabled\": true } } ); });
	function OpenVPNKill(cn){
		if(!confirm('$confirm_disconnect')){return;}
		LoadAjaxSilent('openvpn-kill-status','$page?kill='+encodeURIComponent(cn));
		setTimeout(function(){LoadAjaxSilent('table-connections','$page?main=yes&search=$search_js');},1500);
	}
	if(typeof window.OpenVPNSessionsTimer!='undefined'){clearInterval(window.OpenVPNSessionsTimer);}
	window.OpenVPNSessionsTimer=setInterval(function(){
		if(document.getElementById('table-connections')){
			LoadAjaxSilent('table-connections','$page?main=yes&search=$search_js');
		}else{clearInterval(window.OpenVPNSessionsTimer);}
	},15000);
	</script>";

	echo @implode("\n", $html);
}

function kill_session(){
	// $_GET["kill"] is the base64-encoded common-name; the daemon REST handler base64-decodes it.
	$cnB64=$_GET["kill"];
	$sock=new sockets();
	$sock->REST_API("/openvpn/session/kill/".urlencode($cnB64));
}

