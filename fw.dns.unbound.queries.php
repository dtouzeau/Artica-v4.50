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

function ifisright(){
    $users=new usersMenus();
    if($users->AsProxyMonitor){return true;}
    if($users->AsWebStatisticsAdministrator){return true;}
    if($users->AsDnsAdministrator){return true;}
    if($users->AsFirewallManager){return true;}
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


    $data=json_decode(base64_decode($_GET["data"]));
    $html[]="<div class=ibox-content>";
    $html[]="<table class='table table table-bordered'>";
    $html[]=parseJsonData($data);
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html)."</table></div>";


}
function page(){
    $tpl=new template_admin();
    $html=$tpl->page_header("{DNS_QUERIES}",ico_eye,"{dnslogs_explain}",null,"dns-queries",null,true);
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

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock->getFrameWork("unbound.php?requests=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));

    $ipaddr=$tpl->javascript_parse_text("{ipaddr}");
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>&nbsp;</th>
        	<th>$zdate</th>
        	<th nowrap>$ipaddr</th>
        	<th>{client}</th>
            <th nowrap>{dns_server}</th>
            <th>{type}</th>
			<th>{result}</th>
            <th>{query}</th>
            <th>{results}</th>
            <th>{latency}</th>
            
        </tr>
  	</thead>
	<tbody>
";

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/dns/collector/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    foreach ($json->Events as $line){
        if(trim($line)==null){
            continue;
        }
        $data=json_decode($line);
        if(is_null($data)){
            continue;
        }
        if($GLOBALS["VERBOSE"]) {
            var_dump($data);
        }
        if(!property_exists($data,"dns.qname")) {
            continue;
        }
        $md=md5($line);
        $lineUU=base64_encode($line);
        $IpSrcEDNS="-";
        $query=$data->{"dns.qname"};
        $type=$data->{"dns.qtype"};
        $Time=strtotime($data->{"dnstap.timestamp-rfc3339ns"});
        $date=$tpl->time_to_date($Time,true);
        $IpSrc=$data->{"network.query-ip"};
        $IpDst=$data->{"network.response-ip"};

        $IpDst=str_replace("0.0.0.0","",$IpDst);
        $IpDst=str_replace("127.0.0.1","{local}",$IpDst);

        $IpSrc=str_replace("0.0.0.0","",$IpSrc);
        $IpSrc=str_replace("127.0.0.1","{local}",$IpSrc);

        $DnsServHostname=$data->{"dnstap.identity"};
        if(property_exists($data,"edns.options.0.data")) {
            $IpSrcEDNS = $data->{"edns.options.0.data"};
        }
       // $result=$data->{"dnstap.policy-action"};
        $Latency=$data->{"dnstap.latency"};
        $result=$data->{"dns.rcode"};
        $ANSWERS=array();
        for($i=0;$i<10;$i++){
            $key="dns.resource-records.an.$i.rdata";
            $key2="dns.resource-records.an.$i.rdatatype";
            if(!property_exists($data,$key)){
                VERBOSE("$key doesn't exists [".$data->{$key}."]",__LINE__);
                break;
            }
            $ANSWERS[]=$data->{$key}."(".$data->{$key2}.")";
        }

        $results_color["NXDOMAIN"]="#f8ac59";
        $results_color["REFUSED"]="rgb(237, 85, 101)";
        $results_color["SERVFAIL"]="#f8ac59";
        $results_color["FORMERR"]="#f8ac59";
        $results_color["YXDOMAIN"]="#f8ac59";
        $results_color["XRRSET"]="#f8ac59";
        $results_color["NOERROR"]="#000000";


        $tooltip["NXDOMAIN"]="<span class='label label-warning'>NXDOMAIN</span>";
        $tooltip["REFUSED"]="<span class='label label-danger'>REFUSED</span>";
        $tooltip["SERVFAIL"]="<span class='label label-danger'>SERVFAIL</span>";
        $tooltip["NOERROR"]="<span class='label label-primary'>NOERROR</span>";
        $tooltip["FORMERR"]="<span class='label label-warning'>FORMERR</span>";
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

        $DnsServHostnameTR=explode(".",$DnsServHostname);
        if(strlen($IpDst)>3) {
            $IpDst = "$DnsServHostnameTR[0] ($IpDst)";
        }else{
            $IpDst = "$DnsServHostnameTR[0]";
        }


        $html[]="<tr id='$md'>
				<td style='color:$color' width=1% nowrap>$loupe</span></td>
				<td style='color:$color' width=1% nowrap>$date</span></td>
				<td style='color:$color' width=1% nowrap>$IpSrcEDNS</span></td>
				<td style='color:$color' width=1% nowrap>$IpSrc</span></td>
				<td style='color:$color' width=1% nowrap>$IpDst</span></td>
				<td style='color:$color' width=1% nowrap>$type</span></td>
                <td style='color:$color' width=1% nowrap>$tooltip_text</span></td>     
                <td style='color:$color' width=50%>$query</td>
                <td style='color:$color' width=50%>".@implode(", ",$ANSWERS)."</td>
                <td style='color:$color' width=1% nowrap>$Latency</td>                       
 

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
        $TINY_ARRAY["TITLE"] = "{DNS_QUERIES} ($search)";
    }else{
        $TINY_ARRAY["TITLE"] = "{DNS_QUERIES} ({all_events})";
    }

    $json=json_decode($sock->REST_API("/dns/collector/logsize"));
    $size=$json->Info;
    $fsize=FormatBytes($size/1024);

    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="<strong>{filesize}:$fsize</strong><br>{dnslogs_explain}";
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
function parseJsonData($data, $prefix = '') {
    $f=array();
    if (is_array($data) || is_object($data)) {
        foreach ($data as $key => $value) {
            $fullKey = $prefix === '' ? $key : "$prefix.$key";
            if (is_array($value) || is_object($value)) {

                $f[]=parseJsonData($value, $fullKey);
            } else {
                if(strlen($value)==0){
                    continue;
                }
                if($value=="-"){
                    continue;
                }
                if($fullKey=="dnstap.version"){
                    continue;
                }
                $f[]="<tr><td style='width:1%' nowrap>$fullKey:</td>";
                $f[]="<td style='width:99%'><strong>$value</strong></td>";
                $f[]="</tr>";
            }
        }
    }
    return @implode("",$f);
}