<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.ntpd.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["list"])){table();exit;}
if(isset($_GET["list2"])){table2();exit;}




page();
function page(){
	$tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $ntpdv_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDVersion");
    if(!$users->AsDebianSystem){die();}

    $html=$tpl->page_header("{APP_NTPD} $ntpdv_ver",
        "fa fa-clock","{ntp_servers}","$page?list=yes","ntpservers",
        "progress-ntpd-restart",false,"table-loader-ntp_servers-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NTPD} $ntpdv_ver",$html);
        echo $tpl->build_firewall();
        return;
    }

 }

//remote           refid      st t when poll reach   delay   offset  jitter
function table(){
    $page=CurrentPageName();
    echo "<div id='table-loader-ntp_servers-service2'></div>
<script>LoadAjaxSilent('table-loader-ntp_servers-service2','$page?list2=yes');</script>";
}

function table2(){
	
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new template_admin();


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/srvstatus"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }


    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

    if($HaClusterClient==0) {
        $topbuttons[] = array("Loadjs('fw.system.ntp.servers.php')", "fa fa-bars", "{ntp_servers}");
    }
    $topbuttons[] = array("LoadAjaxSilent('table-loader-ntp_servers-service2','$page?list2=yes');",ico_refresh, "{refresh}");

	
	
			$html[]="<table id='table-ntpd-servers-status' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
			$html[]="<thead>";
			$html[]="<tr>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ntp_servers}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{stratum}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{when}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{pool}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{offset}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>jitter</th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";
			
			$TRCLASS=null;
			
			$ztype["l"]="{local}";
			$ztype["u"]="Unicast";
			$ztype["m"]="Multicast";
			$ztype["b"]="Broadcast";
			$ztype["-"]="Netaddr";

    $datas=$json->Info;
    $ipClass=new IP();
	foreach ($datas as $line){
        $selected="<span class='label label-primary'>{choose}</span>";
		$status=$line->status;
        $last_rx_text="-";
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($line));
	    if($status=="+") {
            $selected="<span class='label label-info'>{selected}</span>";
        }
        if($status=="-") {
            $selected="<span class='label label-danger'>{rejected}</span>";
        }
        if($status=="?") {
            $selected="<span class='label label-default'>{unavailable}</span>";
        }
        $hostname=$line->address;
        $stratum=$line->stratum;
        $poll=pollExpToSeconds($line->poll);
        $poll_text=distanceOfTimeInWords(time(),time()+$poll);



        if($line->last_rx>0) {
            $last_rx = time() - $line->last_rx;
            $last_rx_text = distanceOfTimeInWords($last_rx, time(),true);
        }
        $jitter_s=round($line->jitter_s,3);
        $offset=round($line->offset,3);

		if($ipClass->isValid($hostname)){
            $hostname=gethostbyaddr($hostname);
		}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>$hostname</td>";
		$html[]="<td style='width:1%' nowrap>$stratum</td>";
		$html[]="<td style='width:1%' nowrap>$last_rx_text</td>";
		$html[]="<td style='width:1%' nowrap>{each} $poll_text</td>";
		$html[]="<td style='width:1%' nowrap>$selected</td>";
		$html[]="<td style='width:1%' nowrap>$offset</td>";
		$html[]="<td style='width:1%' nowrap>$jitter_s</td>";
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




    $TINY_ARRAY["TITLE"]="{ntp_servers}";
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]="{ntp_servers_status_explain}";
    $TINY_ARRAY["URL"]="ntpservers";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



	$html[]="
			<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-ntpd-servers-status').footable( { 	\"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
			$jstiny
			</script>";
		
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function pollExpToSeconds(int $pollExp): int {
    if ($pollExp < 0) {
        return 0;
    }
    // For reasonable exponents (< 31) we can use a bit‐shift safely.
    if ($pollExp <= 30) {
        return 1 << $pollExp;
    }
    // Fallback for larger exponents
    $seconds = (int) pow(2, $pollExp);
    if ($seconds < 0) {
        return 0;
    }
    return $seconds;
}