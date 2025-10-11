<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["status"])){systemd_networkd_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["tiny"])){Tiny();exit;}

page();
function page(){
    //
    $page=CurrentPageName();
    $raccourci="systemd-networkd";
    $tpl=new template_admin();

    $SYSTEMD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_VERSION");

    $html=$tpl->page_header("{APP_NETWORKD} v$SYSTEMD_VERSION",
        ico_params,"{APP_NETWORKD_EXPLAIN}",
        "$page?tabs=yes",$raccourci,"systemd-networkd-restart",false,"table-loader-systemd-networkd-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function restart_array():string{
    $tpl=new template_admin();

    return $tpl->framework_buildjs("/system/network/reconfigure-restart",
        "systemd.network.progress",
        "systemd.network.progress.log",
        "systemd-networkd-restart", "" );
        // apply_network_configuration
}

function search(){

    clean_xss_deep();
    $tpl = new template_admin();
    if(isset($_GET["search"])) {
        $MAIN = $tpl->format_search_protocol($_GET["search"]);
    }else{
        $MAIN["MAX"]=150;
        $MAIN["TERM"]="NONE";
    }
    $sock = new sockets();
    $search=null;
    $rp = intval($MAIN["MAX"]);
    if(isset($MAIN["TERM"])) {
        $search = trim($MAIN["TERM"]);
    }
    if(is_null($search)){
        $search="NONE";
    }
    if (strlen($search) < 3) {
        $search = "NONE";
    }
    $search = urlencode(base64_encode($search));
    $data = $sock->REST_API("/system/network/systemd/events/$rp/$search");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>" . json_last_error_msg());
    }
    if (!$json->Status) {
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }


    $html[] = "
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{service}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";



    foreach ($json->Info as $line) {
        $line = trim($line);

        $logEntry = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $Service=null;
            $message = $logEntry['MESSAGE'];
            $timestamp = $logEntry['__REALTIME_TIMESTAMP'] / 1000000;
            if(isset($logEntry['UNIT'])) {
                $Service = $logEntry['UNIT'];
            }
            $date = $tpl->time_to_date($timestamp, true);
            $textclass = "text-muted";

            if(is_null($Service)){
                if(isset($logEntry["_SYSTEMD_UNIT"])) {
                    $Service = $logEntry['_SYSTEMD_UNIT'];
                }
            }
            if(is_null($Service)){
                $Service="{unkown}";
            }
            $Service=str_replace(".service","",$Service);

            if (is_array($message)){
                unset($message);
                $message="Event is an array..";

            }

            if (preg_match("#warn#i", $message, $re)) {
                $textclass = "text-success font-bold";
            }

            if (preg_match("#(error|fatal|failed|Illegal)#i", $message, $re)) {
                $textclass = "text-danger font-bold";
            }
            if (preg_match("#(success|done)#i", $message, $re)) {
                $textclass = "text-muted font-bold";
            }

            //var_dump($ljson);


            $html[] = "<tr>
				<td style='width:1%' nowrap class='$textclass'>$date</td>
				<td style='width:1%' nowrap class='$textclass'>$Service</td>
    			<td class='$textclass'>$message</td>
				</tr>";
        }
    }

    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function systemd_networkd_status():bool{
    $tpl    = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/systemd/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }

    if(!property_exists($json,"Networkd")){
        echo $tpl->widget_rouge("{error}","{unknown}");
        return true;
    }
    $running=0;
    $status=$json->Networkd->active;
    $uptime=$json->Networkd->uptime;
    if($status=="active"){
        $running=1;
    }
    $memory=$json->Networkd->memory;
    $pid=$json->Networkd->pid;
    if(preg_match("#([0-9\.]+)M#",$memory,$re)){
        $memory=floatval($re[1])*1024;
    }

    if(!property_exists($json,"NetworkdDispatcher")){
        echo $tpl->widget_rouge("{error}","Networkd Dispatcher {missing}");
    }
    $runningDBus=0;
    $statusDBus=$json->DBus->active;
    $uptimeDBus=$json->DBus->uptime;
    if($statusDBus=="active"){
        $runningDBus=1;
    }
    $memoryDbus=$json->DBus->memory;
    $pidDbus=$json->DBus->pid;
    if(preg_match("#([0-9\.]+)M#",$memoryDbus,$re)){
        $memoryDbus=floatval($re[1])*1024;
    }
    if(property_exists($json,"NetworkdDispatcher")){
        $runningDispatcher=0;
        $statusDispatcher=$json->NetworkdDispatcher->active;
        $uptimeDispatcher=$json->NetworkdDispatcher->uptime;
        if($statusDispatcher=="active"){
            $runningDispatcher=1;
        }
        $memoryDispatcher=$json->NetworkdDispatcher->memory;
        $pidDispatcher=$json->NetworkdDispatche->pid;
        if(preg_match("#([0-9\.]+)M#",$memoryDispatcher,$re)){
            $memoryDispatcher=floatval($re[1])*1024;
        }

    }

    $l[]="[APP_DBUS]";
    $l[]="service_name=APP_DBUS";
    $l[]="service_disabled=1";
    $l[]="installed=1";
    $l[]="family=network";
    $l[]="running=$runningDBus";
    if($runningDBus==1) {
        $l[] = "master_memory=$memoryDbus";
        $l[] = "pid=$pidDbus";
        $l[] = "uptime=$uptimeDBus";
    }
    $l[]="Nograph=yes";
    $l[] = "";


    $l[]="[APP_NETWORKD]";
    $l[]="service_name=APP_NETWORKD";
    $l[]="service_disabled=1";
    $l[]="installed=1";
    $l[]="family=network";
    $l[]="running=$running";
    if($running==1) {
        $l[] = "master_memory=$memory";
        $l[] = "pid=$pid";
        $l[] = "uptime=$uptime";
    }
    $l[]="Nograph=yes";
    $l[] = "";

    $l[]="[APP_NETWORK_DISPATCHER]";
    $l[]="service_name=APP_NETWORK_DISPATCHER";
    $l[]="service_disabled=1";
    $l[]="installed=1";
    $l[]="family=network";
    $l[]="running=$runningDispatcher";
    if($runningDispatcher==1) {
        $l[] = "master_memory=$memoryDispatcher";
        $l[] = "pid=$pidDispatcher";
        $l[] = "uptime=$uptimeDispatcher";
    }
    $l[]="Nograph=yes";
    $l[] = "";




    $bsini=new Bs_IniHandler();
    $bsini->loadString(@implode("\n", $l));
    $jsRestart=restart_array();
    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_NETWORKD","");
    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_DBUS","");
    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_NETWORK_DISPATCHER","");



    echo $tpl->_ENGINE_parse_body($final);


    return true;

}
function table():bool{
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='progress-systemd-networkd-start'></div>
<script>LoadAjaxSilent('progress-systemd-networkd-start','$page?table1=yes')</script>";
    return true;

}

function table1(){

    $tpl                            = new template_admin();
    $page                           = CurrentPageName();

    $Interval=$tpl->RefreshInterval_js("systemd-networkd-status-div",$page,"status=yes",3);
    $myform=$tpl->search_block($page);

    $html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='systemd-networkd-status-div' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>
	    $Interval;Loadjs('$page?tiny=yes');
	</script>	
	";
    echo $tpl->_ENGINE_parse_body($html);
}

function Tiny():bool{
    $tpl                            = new template_admin();
    $SYSTEMD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_NETWORKD} v$SYSTEMD_VERSION";
    $TINY_ARRAY["ICO"]=ico_params;
    $TINY_ARRAY["EXPL"]="{APP_NETWORKD_EXPLAIN}";
    $TINY_ARRAY["URL"]="systemd-networkd";
    $jsrestart=restart_array();


    $jsUninstall=$tpl->framework_buildjs("/system/network/systemd/uninstall",
        "systemd.network.progress",
        "systemd.network.progress.log",
        "systemd-networkd-restart", "document.location.href='/';" );

    $topbuttons[] = array($jsrestart, ico_refresh, "{apply_network_configuration}");
    $topbuttons[] = array($jsUninstall, ico_trash, "{back_to_v1_method}");

   $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    header("content-type: application/x-javascript");
    echo $jstiny;
    return true;
}




