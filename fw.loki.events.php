<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();



function page():bool{
	$tpl=new template_admin();
	if($_SESSION["LOKI_SEARCH"]==null){$_SESSION["LOKI_SEARCH"]="limit 200";}
    $html=$tpl->page_header("{APP_LOKI} {events}",ico_eye,"{APP_LOKI_EXPLAIN}",null,"loki-events",null,true);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{APP_LOKI} {events}");
        return true;
    }
	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table():bool{
	$tpl=new template_admin();
	$t=time();
    $html[]=$tpl->_ENGINE_parse_body("
			<table id='$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["LOKI_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
	$ss=urlencode(base64_encode($search["S"]));

    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}

	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service}</th>";
    $html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $TRCLASS=null;

    $EndPoint="/grafana/loki/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $LEVELS["info"]="<span class='label label-default'>INFO</span>";
    $LEVELS["warn"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["error"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["debug"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["trace"]="<span class='label label-default'>TRACE</span>";
    $LEVELS["success"]="<span class='label label-primary'>Success</span>";

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $td1="style='width:1%' nowrap";
    foreach ($json->Logs as $line){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class="";
		if(trim($line)==null){continue;}
		if($GLOBALS['VERBOSE']){echo "FOUND $line\n";}
        $srcline=$line;


        list($datetext,$service,$type,$logtype,$line)=ParseRows($srcline);
        $line=str_replace("#012","",$line);
        $line=str_replace("#011","",$line);
        $label=$LEVELS[$logtype];
        if(isset($KEALEVEL[$type])){
            $label=$KEALEVEL[$type];
        }
        //from [Agent:192.168.96.29]
        if(preg_match("#from \[Agent:(.+?)\]#",$line,$matches)) {
            $line1A="from [Agent:$matches[1]]";
            $ico=ico_sensor;
            $line=str_replace($line1A,"&nbsp;<strong><i class='$ico'></i>&nbsp;DHCP Relay $matches[1]</strong>",$line);
        }
        if(preg_match("#Interface:([a-z0-9]+)#i",$line,$matches)) {
            $line1A="Interface:$matches[1]";
            $ico=ico_nic;
            $line=str_replace($line1A,"<strong><i class='$ico'></i>&nbsp; $matches[1]</strong>",$line);
        }
        if($type==""){
            $type="server";
        }

		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" $td1>$datetext</td>";
        $html[]="<td class=\"$text_class\" $td1>$label</td>";
        $html[]="<td class=\"$text_class\" $td1>$type</td>";
		$html[]="<td class=\"$text_class\" style='width:99%'>$line</a></td>";
        $html[]="<td class=\"$text_class\" $td1>$service</td>";
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
	$html[]="</table><div><i></i></div>";

    $zlines=count($json->Logs);
    $TINY_ARRAY["TITLE"]="{APP_LOKI} {events} &laquo;{$search["S"]}&raquo; $zlines {results}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_LOKI_EXPLAIN}";
    $TINY_ARRAY["URL"]="loki-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	$jstiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    </script>";

			echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ParseRows($line):array{
    $tpl=new template_admin();
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");


    if(preg_match('#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+msg=(.+?)\s+(module|component)=(.+)#',$line,$re)){
        $logtype = $re[1];
        $date = strtotime($re[2]);
        $datetext = $tpl->time_to_date($date, true);
        $service = $re[3];
        $line = $re[4];
        $type=$re[6];
        return array($datetext,$service,$type,$logtype,$line);
    }



    if(preg_match('#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+component=(.+?)\s+org_id=(.+?)\s+traceID=(.+?)\s+(.+?)=(.+)\s+msg="(.+?)"\s+err="(.+?)"(.*?):(.+)#',$line,$re)){
        $logtype = $re[1];
        $date = strtotime($re[2]);
        $datetext = $tpl->time_to_date($date, true);
        $service = $re[3];
        $type = $re[4];
        $org_id=$re[5];
        $traceID=$re[6];
        $key=$re[7];
        $val=$re[8];
        $line = $re[9];
        $err=$re[10];
        $msg1=$re[11];
        $msg2=$re[12];
        $line="$line $err org_id:$org_id traceID:$traceID $key:$val $msg1:$msg2";
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+component=(.+?)\s+msg=\"(.+?)\"\s+elapsed=(.+?)\s+errors=",$line,$re)) {
        $logtype = $re[1];
        $date = strtotime($re[2]);
        $datetext = $tpl->time_to_date($date, true);
        $service = $re[3];
        $type = $re[4];
        $line = $re[5];
        $elapsed = $re[6];
        $line="$line ($elapsed)";
        return array($datetext,$service,$type,$logtype,$line);
    }
    
    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+component=(.+?)\s+org_id=(.+?)\s+traceID=(.+?)\s+(.+?)=(.+?)\s+msg=\"(.+?)\"\s+err=\"(.+?)\".*?transport:(.+)#",$line,$re)){
        $logtype=$re[1];
        $date=strtotime($re[2]);
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[3];
        $type=$re[4];
        $org_id=$re[5];
        $traceID=$re[6];
        $sztype="$re[7]: $re[8]";
        $line=$re[9];
        $err=$re[10];
        $addr=$re[11];
        $line="$line $sztype Org ID:$org_id Trace id:$traceID $err transport: $addr";
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+component=(.+?)\s+org_id=(.+?)\s+traceID=(.+?)\s+msg=\"(.+?)\"\s+err=(.+?)\s+addr=(.+)$#",$line,$re)){
        $logtype=$re[1];
        $date=strtotime($re[2]);
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[3];
        $type=$re[4];
        $org_id=$re[5];
        $traceID=$re[6];
        $line=$re[7];
        $err=$re[8];
        $addr=$re[9];
        $line="$line Org ID:$org_id Trace id:$traceID $err addr:$addr";
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+component=(.+?)\s+msg=\"(.+?)\"\s+err=\"(.+?)\"\s+addr=(.+?)$#",$line,$re)){
        $logtype=$re[1];
        $date=strtotime($re[2]);
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[3];
        $type=$re[4];
        $line=$re[5];
        $err=$re[6];
        $addr=$re[7];
        $line="$line $err addr:$addr";
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+component=(.+?)\s+org_id=(.+?)\s+ traceID=(.+?)\s+(.+?)=(.+?)\s+msg=\"(.+?)\"#",$line,$re)){
        $logtype=$re[1];
        $date=strtotime($re[2]);
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[3];
        $line=$re[9];
        $type=$re[4];
        $line="Org ID:$re[5] Trace Id:$re[6] $re[7]:$re[8] $line";
        return array($datetext,$service,$type,$logtype,$line);
    }


    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+index-store=(.+?)\s+msg=\"(.+?)\"#",trim($line),$re)){
        $logtype=$re[1];
        $date=strtotime($re[2]);
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[3];
        $line=$re[5];
        $type="";
        $line="index store: $re[4] $line";
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#level=(.+?)\s+ts=(.+?)\s+caller=(.+?)\s+msg=\"(.+?)\"#",trim($line),$re)){
        $logtype=$re[1];
        $date=strtotime($re[2]);
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[3];
        $line=$re[4];
        $type="";
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?kea-artica.+?:\s+([A-Z]+)\s+([A-Z]+)\s+(.+)#",trim($line),$re)){
        $month=$months[$re[1]];
        $day=$re[2];
        $time=$re[3];
        $date=strtotime(date("Y")."-$month-$day $time");
        $datetext=$tpl->time_to_date($date,true);
        $service="artica";
        $logtype=$re[4];
        $type=$re[5];
        $line=$re[6];
        return array($datetext,$service,$type,$logtype,$line);
    }

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?kea-artica.+?:\s+([A-Z]+)\s+(.+)#",trim($line),$re)){
        $month=$months[$re[1]];
        $day=$re[2];
        $time=$re[3];
        $date=strtotime(date("Y")."-$month-$day $time");
        $datetext=$tpl->time_to_date($date,true);
        $service="artica";
        $logtype=$re[4];
        $type="-";
        $line=$re[5];
        return array($datetext,$service,$type,$logtype,$line);
    }

    return  array("-","-","-","-",$line);
}
