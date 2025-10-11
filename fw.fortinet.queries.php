<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["button"])){button_white();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}

page();
function ifisright():bool{
    $users=new usersMenus();
    if($users->AsProxyMonitor){return true;}
    if($users->AsWebStatisticsAdministrator){return true;}
    if($users->AsDnsAdministrator){return true;}
    if($users->AsFirewallManager){return true;}
    return false;
}
function zoom_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=urlencode($_GET["zoom-js"]);
    $title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
    $tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}
function zoom(){
    $country="<i class='".ico_country."'></i>&nbsp;";
    $ImgINt="<i class='".ico_nic."'></i>&nbsp;";
    $Protos[0]="HOPOPT";
    $ProtosText["0"]="IPv6 Hop-by-Hop Option";
    $Protos[1]="ICMP";
    $ProtosText["1"]="Internet Control Message Protocol";
    $Protos[2]="IGMP";
    $ProtosText["2"]="Internet Group Management Protocol";
    $Protos[6]="TCP";
    $ProtosText["6"]="Transmission Control Protocol";
    $Protos[17]="UDP";
    $ProtosText["17"]="User Datagram Protocol";
    $Protos[41]="IPv6";
    $ProtosText["41"]="Encapsulation";
    $Protos[47]="GRE";
    $ProtosText["47"]="Generic Routing Encapsulation";
    $Protos[50]="ESP";
    $ProtosText["50"]="Encapsulating Security Payload";
    $Protos[51]="AH";
    $ProtosText["51"]="Authentication Header";
    $Protos[58]="ICMPv6";
    $ProtosText["58"]="ICMP for IPv6";
    $Protos[89]="OSPF";
    $ProtosText["89"]="Open Shortest Path First";
    $Protos[3]="GGP";
    $ProtosText["3"]="Gateway-to-Gateway Protocol";
    $Protos[4]="IPv4";
    $ProtosText["4"]="IPv4 encapsulation";
    $Protos[5]="ST";
    $ProtosText["5"]="Stream Protocol";
    $Protos[8]="EGP";
    $ProtosText["8"]="Exterior Gateway Protocol";
    $Protos[9]="IGP";
    $ProtosText["9"]="Interior Gateway Protocol";
    $Protos[12]="PUP";
    $ProtosText["12"]="PARC Universal Packet";
    $Protos[27]="RDP";
    $ProtosText["27"]="Reliable Datagram Protocol";
    $Protos[88]="EIGRP";
    $ProtosText["88"]="Enhanced Interior Gateway Routing Protocol";
    $Protos[115]="L2TP";
    $ProtosText["115"]="Layer 2 Tunneling Protocol";
    $data=unserialize(base64_decode($_GET["data"]));
    $results_color["NXDOMAIN"]="#f8ac59";
    $results_color["deny"]="rgb(237, 85, 101)";
    $results_color["SERVFAIL"]="#f8ac59";
    $results_color["FORMERR"]="#f8ac59";
    $results_color["YXDOMAIN"]="#f8ac59";
    $results_color["XRRSET"]="#f8ac59";
    $results_color["accept"]="#000000";
    $results_color["close"]="#cacaca";
    $results_color["server-rst"]="#f8ac59";
    $color=$results_color[$data["action"]];
    $IpSrc=$data["srcip"];
    $Type="<div style='color:$color'>".strtoupper($data["action"])."</div>".$data["type"]."/".$data["subtype"];

    $proto=$data["proto"];
    if(isset($Protos[$proto])){
        $protocol=$Protos[$proto].":";
    }


    $results_color["client-rst"]="#f8ac59";



    $TimeText=$data["date"]." " .$data["time"]." ".$data["tz"];
    unset($data["type"]);
    unset($data["subtype"]);
    unset($data["date"]);
    unset($data["time"]);
    unset($data["tz"]);
    unset($data["eventtime"]);
    unset($data["timestamp"]);
    unset($data["action"]);


    $vtop="vertical-align: top;";
    $vcent="vertical-align: middle;";
    $f14="style='font-size:14px' nowrap";

    $html[]="<div class=ibox-content>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>";
    $html[]="    <table style='width:100%'>";
    $html[]="        <tr>";
    $html[]="            <td style='$vcent'><i class='fa-4x ".ico_computer."'></i></td>";
    $html[]="            <td style='$vtop;padding-left:15px'>";
    $html[]="                <table style='width:100%'>";
    $html[]="                <tr>";
    $html[]="                    <td style='font-size:18px'>$IpSrc</td>";
    $html[]="                </tr>";
if(isset($data["srcname"])) {
    $html[] = "                <tr>";
    $html[] = "                    <td style='font-size:12px'><i>{$data["srcname"]}</i></td>";
    $html[] = "                </tr>";
    unset($data["srcname"]);
}else{
    $html[] = "                <tr>";
    $html[] = "                    <td style='font-size:12px'><i>".gethostbyaddr($data["srcip"])."</i></td>";
    $html[] = "                </tr>";
    unset($data["srcname"]);
}
    if(isset($data["srcmac"])) {
        $html[] = "                <tr>";
        $html[] = "                    <td $f14>{$data["srcmac"]}</td>";
        $html[] = "                </tr>";
        unset($data["srcmac"]);
    }
    if(isset($data["srccountry"])) {
        $html[] = "                <tr>";
        $html[] = "                    <td $f14>$country{$data["srccountry"]}</td>";
        $html[] = "                </tr>";
        unset($data["srccountry"]);
    }
    $html[] = "                <tr>";
    $html[] = "                    <td $f14>$ImgINt{$data["internal"]}/{$data["srcintfrole"]}/{$data["srcintf"]}</td>";
    $html[] = "                </tr>";
    unset($data["internal"]);
    unset($data["srcintfrole"]);
    unset($data["srcintf"]);

    $html[]="                </table>";
    $html[]="              </td>";
    $html[]="         </tr>";
    $html[]="      </table>";
    $html[]="</td>";
    $html[]="<td style='width:1%;padding:10px;$vcent' nowrap><strong style='font-size:22px'>{$data["srcport"]}</strong>";
    $html[]="</td>";
    $html[]="<td style='width:99%;$vcent;text-align: center;padding:10px'>";
    $html[]="<div style='font-size:22px' class='center'>".strtoupper($Type)."</div>";
    $html[]="<i class='".ico_arrow_right." fa-4x'></i>";
    $html[]="</td>";
    if(isset($data["service"])){
        $protocol=$data["service"];
        unset($data["service"]);
        $protocol=strtoupper($protocol).":";
        if(preg_match("#\/[0-9]+#",$protocol)){
            unset($data["dstport"]);
            $protocol=str_replace(":","",$protocol);
        }

        $protocol=str_replace("/",":",$protocol);
    }
    unset($data["srcip"]);

    $html[]="<td style='width:1%;padding:10px;$vcent' nowrap><strong style='font-size:22px'>$protocol{$data["dstport"]}</strong>";
    $html[]="</td>";
    unset($data["srcport"]);unset($data["dstport"]);unset($data["proto"]);

    $html[]="<H2 style='margin-bottom: 20px'>{$data["devname"]}&nbsp;{$data["ip_address"]}&nbsp;{$data["devid"]}</H2>";
    $html[]="<div style='text-align:right;margin-top: -19px;margin-bottom: 30px;border-top: 1px solid #CCCCCC;font-size: 18px;'>$TimeText</div>";

    unset($data["devname"]);unset($data["ip_address"]);unset($data["devid"]);

    $html[]="<td style='width:33%'>";
    $html[]="    <table style='width:100%'>";
    $html[]="        <tr>";
    $html[]="            <td style='$vcent'><i class='fa-4x ".ico_computer."'></i></td>";
    $html[]="            <td style='$vtop;padding-left:15px'>";
    $html[]="                <table style='width:100%'>";
    $html[]="                <tr>";
    $html[]="                    <td style='font-size:18px'>{$data["dstip"]}</td>";
    $html[]="                </tr>";
    if(isset($data["dstname"])) {
        $html[] = "                <tr>";
        $html[] = "                    <td style='font-size:12px'><i>{$data["dstname"]}</i></td>";
        $html[] = "                </tr>";
        unset($data["dstname"]);
    }
    if(isset($data["dstmac"])) {
        $html[] = "                <tr>";
        $html[] = "                    <td style='font-size:14px'>{$data["dstmac"]}</td>";
        $html[] = "                </tr>";
        unset($data["dstmac"]);
    }
    if(isset($data["dstcountry"])) {
        $html[] = "                <tr>";
        $html[] = "                    <td style='font-size:14px'>$country{$data["dstcountry"]}</td>";
        $html[] = "                </tr>";
        unset($data["dstcountry"]);
    }
    $html[] = "                <tr>";
    $html[] = "                    <td $f14>$ImgINt{$data["dstintf"]}/{$data["dstintfrole"]}/</td>";
    $html[] = "                </tr>";
    unset($data["dstintf"]);
    unset($data["dstintfrole"]);
    unset($data["dstip"]);

    $html[]="                </table>";
    $html[]="              </td>";
    $html[]="         </tr>";
    $html[]="      </table>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<div style='width:60%;margin-top:30px'>";
    $html[]="<table class='table table table-bordered' class='center'>";
    foreach ($data as $key=>$val){
        $html[]="<tr>
        <td style='width:1%' nowrap=''>$key:</td>
        <td style='width:99%' nowrap=''><strong>$val</strong></td>
        </tr>";

    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html)."</table></div></div>";


}
function page(){
    $tpl=new template_admin();
    $html=$tpl->page_header("FortiGate {events}",ico_firewall,"{fortigate_explain_events}",null,"fortinet-queries",null,true);
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}


function search(){

    $time=null;
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=$tpl;


    $ipaddr=$tpl->javascript_parse_text("{ipaddr}");
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>&nbsp;</th>
        	<th>$zdate</th>
        	<th nowrap>{firewall}</th>
        	<th nowrap>IN</th>
        	<th nowrap>{ipaddr}</th>
            <th>{type}</th>
            <th>OUT</th>
            <th>{query}</th>
            <th colspan='2'>{result}</th>
            <th>{bandwidth}</th>
            
        </tr>
  	</thead>
	<tbody>
";


    $Protos[0]="HOPOPT";
$ProtosText["0"]="IPv6 Hop-by-Hop Option";
$Protos[1]="ICMP";
$ProtosText["1"]="Internet Control Message Protocol";
$Protos[2]="IGMP";
$ProtosText["2"]="Internet Group Management Protocol";
$Protos[6]="TCP";
$ProtosText["6"]="Transmission Control Protocol";
$Protos[17]="UDP";
$ProtosText["17"]="User Datagram Protocol";
$Protos[41]="IPv6";
$ProtosText["41"]="Encapsulation";
$Protos[47]="GRE";
$ProtosText["47"]="Generic Routing Encapsulation";
$Protos[50]="ESP";
$ProtosText["50"]="Encapsulating Security Payload";
$Protos[51]="AH";
$ProtosText["51"]="Authentication Header";
$Protos[58]="ICMPv6";
$ProtosText["58"]="ICMP for IPv6";
$Protos[89]="OSPF";
$ProtosText["89"]="Open Shortest Path First";
$Protos[3]="GGP";
$ProtosText["3"]="Gateway-to-Gateway Protocol";
$Protos[4]="IPv4";
$ProtosText["4"]="IPv4 encapsulation";
$Protos[5]="ST";
$ProtosText["5"]="Stream Protocol";
$Protos[8]="EGP";
$ProtosText["8"]="Exterior Gateway Protocol";
$Protos[9]="IGP";
$ProtosText["9"]="Interior Gateway Protocol";
$Protos[12]="PUP";
$ProtosText["12"]="PARC Universal Packet";
$Protos[27]="RDP";
$ProtosText["27"]="Reliable Datagram Protocol";
$Protos[88]="EIGRP";
$ProtosText["88"]="Enhanced Interior Gateway Routing Protocol";
$Protos[115]="L2TP";
$ProtosText["115"]="Layer 2 Tunneling Protocol";


    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $data=$sock->REST_API("/fortinet/collector/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    foreach ($json->Events as $line){
        if(trim($line)==null){
            continue;
        }
        $policyname="&nbsp;";
        $protocol="";
        $data=parseData($line);
        $seconds = $data["eventtime"] / 1e9;
        $Time = round($seconds);
        $devname=$data["devname"]."/".$data["vd"];
        $IpSrc=$data["srcip"].":".$data["srcport"];
        $Type=$data["type"]."/".$data["subtype"];
        $srcintfrole=$data["srcintfrole"];
        $proto=$data["proto"];
        if(isset($Protos[$proto])){
            $protocol=$Protos[$proto].":";
        }

        $srcintf="<i class='".ico_nic."'></i>&nbsp;".$data["srcintf"]."/$srcintfrole";
        $query=$protocol.$data["dstip"].":".$data["dstport"];
        $dstintfrole=$data["dstintfrole"];
        $dstintf="<i class='".ico_nic."'></i>&nbsp;".$data["dstintf"]."/$dstintfrole";
        $result=$data["action"];
        $service=$data["service"];
        $app=$data["app"];
        $duration=$data["duration"];
        $sentbyte=intval($data["sentbyte"]);
        $rcvdbyte=intval($data["rcvdbyte"]);
        $policy=$data["policytype"];
        if(isset($data["policyname"])){
            $policyname="<i><strong>( ".$data["policyname"]." )</strong></i>";
        }
        if(isset($data["srcname"])){
            $IpSrc=$IpSrc."&nbsp;(".$data["srcname"].")";
        }
        $bdw="-";
        $sum=$sentbyte+$rcvdbyte;
        if($sum>0){
            $bdw = FormatBytes($sum / 1024);
        }

        $md=md5($line);
        $lineUU=base64_encode(serialize($data));

        $xtime=strtotime($data["date"]." " .$data["time"]);

        $date=$tpl->time_to_date($xtime,true);
        $results_color["NXDOMAIN"]="#f8ac59";
        $results_color["deny"]="rgb(237, 85, 101)";
        $results_color["SERVFAIL"]="#f8ac59";
        $results_color["FORMERR"]="#f8ac59";
        $results_color["YXDOMAIN"]="#f8ac59";
        $results_color["XRRSET"]="#f8ac59";
        $results_color["accept"]="#000000";
        $results_color["close"]="#cacaca";
        $results_color["server-rst"]="#f8ac59";
        $results_color["client-rst"]="#f8ac59";

        $tooltip["deny"]="<span class='label label-danger'>{deny}</span>";
        $tooltip["REFUSED"]="<span class='label label-danger'>REFUSED</span>";
        $tooltip["SERVFAIL"]="<span class='label label-danger'>SERVFAIL</span>";
        $tooltip["accept"]="<span class='label label-primary'>{accept}</span>";
        $tooltip["close"]="<span class='label label-default'>{close}</span>";
        $tooltip["server-rst"]="<span class='label label-warning'>{reset}</span>";
        $tooltip["client-rst"]="<span class='label label-warning'>{reset}</span>";
        $tooltip["YXDOMAIN"]="<span class='label label-warning'>YXDOMAIN</span>";
        $tooltip["XRRSET"]="<span class='label label-warning'>XRRSET</span>";
        if(isset($results_color[$result])) {
            $color=$results_color[$result];

        }
        if(isset($tooltip[$result])){
            $tooltip_text=$tooltip[$result];
        }else{
            $tooltip_text="<span class='label label-default'>$result</span>";
        }



        $loupe=$tpl->icon_loupe(true,"Loadjs('$page?zoom-js=$lineUU')");
        $TDStle="style='color:$color;width:1%' nowrap";

        $html[]="<tr id='$md'>
				<td $TDStle>$loupe</span></td>
				<td $TDStle>$date</span></td>
				<td $TDStle>$devname</span></td>
				<td $TDStle>$srcintf</span></td>
				
				<td $TDStle>$IpSrc</span></td>
				<td $TDStle>$Type</span></td>
				<td $TDStle>$dstintf</span></td>
                <td $TDStle>$query</td>
                <td $TDStle >$tooltip_text</td>
                <td $TDStle >$policyname</td>
                <td $TDStle>$bdw</td>                       
 

                </tr>";

    }
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='10'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";

    $topbuttons=array();
    if($search<>"NONE") {
        $TINY_ARRAY["TITLE"] = "FortiGate {log_queries} ($search)";
    }else{
        $TINY_ARRAY["TITLE"] = "FortiGate {log_queries} ({all_events})";
    }

    $json=json_decode($sock->REST_API("/fortinet/collector/logsize"));
    $size=$json->Info;
    $Events=$json->Events;
    $fsize=FormatBytes($size/1024);
    $Events=$tpl->FormatNumber($Events);
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="<H3>{filesize}:&nbsp;$fsize&nbsp;&nbsp;|&nbsp;&nbsp;{processed_messages}:&nbsp;$Events </H3><br>{fortigate_explain_events}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body($html);



}
function parseData($log_line=""):array {
    $parsed_data = [];

// Extract the fixed-position elements
    preg_match('/^(\w+\s+\d+\s+\d+:\d+:\d+)\s+([\d.]+)\s+/', $log_line, $matches);
    $parsed_data['timestamp'] = $matches[1];
    $parsed_data['ip_address'] = $matches[2];

    preg_match_all('/(\w+)=("[^"]*"|\S+)/', $log_line, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $key = $match[1];
        $value = trim($match[2], '"');
        $parsed_data[$key] = $value;
    }

    return $parsed_data;
}