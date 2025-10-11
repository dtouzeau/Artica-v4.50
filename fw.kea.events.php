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
	if($_SESSION["KEA_SEARCH"]==null){$_SESSION["KEA_SEARCH"]="limit 200";}
    $html=$tpl->page_header("{APP_DHCP} {events}",ico_eye,"{APP_KEA_DHCPD4_TEXT}",null,"kea-events",null,true);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{APP_DHCP} {events}");
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

	
	$_SESSION["KEA_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
	$ss=urlencode(base64_encode($search["S"]));

    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}

	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
    $html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $TRCLASS=null;

    $EndPoint="/kea/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";
    $LEVELS["WARN"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["fatal"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["debug"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["trace"]="<span class='label label-default'>TRACE</span>";
    $LEVELS["success"]="<span class='label label-primary'>Success</span>";

    $KEALEVEL["DHCP4_DYNAMIC_RECONFIGURATION"]="<span class='label label-warning'>RELOAD</span>";
    $KEALEVEL["DHCP4_DYNAMIC_RECONFIGURATION_SUCCESS"]="<span class='label label-primary'>RELOAD</span>";
    $KEALEVEL["DHCP4_CONFIG_COMPLETE"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCPSRV_MEMFILE_LEASE_FILE_LOAD"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCPSRV_MEMFILE_LFC_SETUP"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCPSRV_MEMFILE_DB"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCP4_MULTI_THREADING_INFO"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["HOOKS_LIBRARY_LOADED"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["STAT_CMDS_INIT_OK"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["LEASE_CMDS_INIT_OK"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCPSRV_CFGMGR_NEW_SUBNET4"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["HOSTS_BACKENDS_REGISTERED"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCP4_RESERVATIONS_LOOKUP_FIRST_ENABLED"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["RUN_SCRIPT_LOAD"]="<span class='label label-primary'>CONF</span>";
    $KEALEVEL["DHCPSRV_CFGMGR_ADD_IFACE"]="<span class='label label-primary'>START</span>";
    $KEALEVEL["DHCPSRV_CFGMGR_SOCKET_TYPE_SELECT"]="<span class='label label-primary'>START</span>";


    $KEALEVEL["HOOKS_LIBRARY_CLOSED"]="<span class='label label-warning'>STOP</span>";
    $KEALEVEL["LEASE_CMDS_DEINIT_OK"]="<span class='label label-warning'>STOP</span>";
    $KEALEVEL["RUN_SCRIPT_UNLOAD"]="<span class='label label-warning'>STOP</span>";
    $KEALEVEL["STAT_CMDS_DEINIT_OK"]="<span class='label label-warning'>STOP</span>";



    $KEALEVEL["DHCPSRV_MEMFILE_LFC_START"]="<span class='label label-pink'>CLEAN</span>";
    $KEALEVEL["DHCPSRV_MEMFILE_LFC_EXECUTE"]="<span class='label label-pink'>CLEAN</span>";
    $KEALEVEL["DHCP4_LEASE_ALLOC"]="<span class='label label-success'>LEASE</span>";
    $KEALEVEL["COMMIT"]="<span class='label label-success'>LEASE</span>";
    $KEALEVEL["RENEW"]="<span class='label label-success'>LEASE</span>";
    $KEALEVEL["DHCP4_INIT_REBOOT"]="<span class='label label-success'>LEASE</span>";
    $KEALEVEL["DHCP4_LEASE_ADVERT"]="<span class='label label-success'>LEASE</span>";



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


		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" $td1>$datetext</td>";
        $html[]="<td class=\"$text_class\" $td1>$label</td>";
        $html[]="<td class=\"$text_class\" $td1>$service</td>";
        $html[]="<td class=\"$text_class\" $td1>$type</td>";
		$html[]="<td class=\"$text_class\" style='width:99%'>$line</a></td>";
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
    $TINY_ARRAY["TITLE"]="{APP_DHCP} {events} &laquo;{$search["S"]}&raquo; $zlines {results}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_KEA_DHCPD4_TEXT}";
    $TINY_ARRAY["URL"]="kea-events";
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

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?([a-z0-9\-]+):\s+([A-Z]+)\s+([A-Z0-9_]+)\s+(.+)#",trim($line),$re)){
        $month=$months[$re[1]];
        $day=$re[2];
        $time=$re[3];
        $date=strtotime(date("Y")."-$month-$day $time");
        $datetext=$tpl->time_to_date($date,true);
        $service=$re[4];
        $logtype=$re[5];
        $type=$re[6];
        $line=$re[7];
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
