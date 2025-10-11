<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__) . "/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__) . "/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__) . "/ressources/class.tcpip.inc");
$users = new usersMenus();
if (!$users->AsProxyMonitor) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["search"])) {
    search();
    exit;
}
if (isset($_GET["zoom-js"])) {
    zoom_js();
    exit;
}
if (isset($_GET["zoom"])) {
    zoom();
    exit;
}

page();

function zoom_js()
{
    header("content-type: application/x-javascript");
    $tpl = new template_admin();
    $page = CurrentPageName();
    $data = urlencode($_GET["data"]);
    $title = $tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
    $tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}

function zoom()
{

    $data = unserialize(base64_decode($_GET["data"]));
    $html[] = "<div class=ibox-content>";
    $html[] = "<table class='table table table-bordered'>";
    while (list ($key, $val) = each($data)) {

        $html[] = "<tr>
		<td class=text-capitalize>$key:</td>
		<td><strong>$val</strong></td>
		</tr>";

    }

    echo @implode("", $html) . "</table></div>";

}

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $t = time();
    if (!isset($_SESSION["LB_SEARCH"])) {
        $_SESSION["LB_SEARCH"] = "50 events";
    }
    if (isset($_GET["logfile"])) {
        $addPLUS = "&logfile=" . urlencode($_GET["logfile"]);
    }

    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{realtime_requests}</h1></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["LB_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	<span class=\"input-group-btn\">
	<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
	</span>
	</div>
	</div>
	</div>
	<div class='row' id='spinner'>
	<div id='progress-firehol-restart'></div>
	<div  class='ibox-content'>
	<div class='sk-spinner sk-spinner-wave'>
	<div class='sk-rect1'></div>
	<div class='sk-rect2'></div>
	<div class='sk-rect3'></div>
	<div class='sk-rect4'></div>
	<div class='sk-rect5'></div>
	</div>


	<div id='table-loader'></div>
	</div>
	</div>
	</div>
	<script>
	function Search$t(e){
	if(!checkEnter(e) ){return;}
	ss$t();
}

function ss$t(){
		var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
		LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
}

		function Start$t(){
		var ss=document.getElementById('search-this-$t').value;
		ss$t();
}
		Start$t();
		</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function search()
{
    $time = null;
    $sock = new sockets();
    $tpl = new template_admin();
    $max = 0;
    $date = null;
    $c = 0;
    $addPLUS = null;
    if (isset($_GET["logfile"])) {
        $addPLUS = "&logfile=" . urlencode($_GET["logfile"]);
    }
    $MAIN = $tpl->format_search_protocol($_GET["search"]);
    $sock = new sockets();
    if (!isset($_GET["SearchString"])) {
        $_GET["SearchString"] = null;
    }
    if (!isset($_GET["FinderList"])) {
        $_GET["FinderList"] = null;
    }

    $sock->getFrameWork("haproxy.php?access-real=yes&rp={$MAIN["MAX"]}&query=" . urlencode($MAIN["TERM"]) . "&SearchString={$_GET["SearchString"]}&FinderList={$_GET["FinderList"]}$addPLUS");
    $filename = "/usr/share/artica-postfix/ressources/logs/haproxy.log.tmp";
    $ipaddr = $tpl->javascript_parse_text("{members}");
    $destination = $tpl->javascript_parse_text("{destinations}");
    $zdate = $tpl->_ENGINE_parse_body("{zDate}");
    $proto = $tpl->_ENGINE_parse_body("{proto}");
    $uri = $tpl->_ENGINE_parse_body("{url}");
    $duration = $tpl->_ENGINE_parse_body("{duration}");
    $size = $tpl->_ENGINE_parse_body("{size}");


    $html[] = "

	<table class=\"table table-hover\">
	<thead>
	<tr>
	<th>$zdate</th>
	<th>$ipaddr</th>
	<th>&nbsp;</th>
	<th>$proto</th>
	<th>$uri</th>
	<th>INFO/LINK</th>
	<th>$destination</th>
	<th>$size</th>
	<th>$duration</th>
	</tr>
	</thead>
	<tbody>
	";

    $data = explode("\n", @file_get_contents($filename));
    $splunkfw = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkForwarderEnabled"));

    if (count($data) > 3) {
        $_SESSION["LB_SEARCH"] = $_GET["search"];
    }
    rsort($data);
    $logfileD = new logfile_daemon();
    $months = array("Jan" => "01", "Feb" => "02", "Mar" => "03", "Apr" => "04", "May" => "05", "Jun" => "06", "Jul" => "07", "Aug" => "08", "Sep" => "09", "Oct" => "10", "Nov" => "11", "Dec" => "12");
    foreach ($data as $line) {
        if($splunkfw==1){
            $ArrayENC = array();
            $URI_column = null;
            $backend_column = null;
            $duration_column = null;
            $SERVICE_LOG = false;
            $loupe = null;
            $link = null;
            $MAINUTI = null;
            $PROTO = null;
            $FAMILYSITE = null;
            $URL = null;
            $HTTP_CODE = 0;
            $TR = preg_split("/[\s]+/", $line);


            if (count($TR) < 5) {
                continue;
            }
            $client = null;


            if (preg_match("#^haproxy\[([0-9])+]#", $TR[4])) {
                $SERVICE_LOG = true;
            }

            $stime = str_replace("[", "", $TR[6]);
            $stime = str_replace("]", "", $stime);
            $color = "#676A6C";
            if (preg_match("#^(.+?):#", $TR[5], $re)) {
                $client = $re[1];
            }
            if (preg_match("#^.*?\/(.+)#", $TR[8], $re)) {
                $backend = $re[1];
            }
            $servicename = $TR[7];


            if (!isset($TR[27])) {
                $PROTO = "TCP/UDP";
                if (intval($TR[10]) > 1024) {
                    $size = FormatBytes($TR[10] / 1024);
                } else {
                    $size = "{$TR[10]} Bytes";
                }
            } else {
                $HTTP_CODE = intval($TR[10]);
                $PROTO = $TR[27];
                $PROTO = str_replace('"', "", $PROTO);
                $size = FormatBytes($TR[11] / 1024);
            }

            if (!isset($TR[28])) {
                $link = $tpl->icon_nothing();
                $URI = null;
                $URI_column = "TCP &raquo;&raquo; $backend";;
            } else {
                $URI = $TR[28];
                $MAINUTI = $logfileD->parseURL($URI, false, $PROTO, true);
                $FAMILYSITE = $MAINUTI["FAMILYSITE"];
                $URL = $MAINUTI["URL"];
                if (filter_var('http://'.$FAMILYSITE, FILTER_VALIDATE_URL) === FALSE) {
                    $PROTO = $TR[28];
                    $PROTO = str_replace('"', "", $PROTO);
                    $URI=$TR[29];
                    $logfileD = new logfile_daemon();
                    $MAINUTI = $logfileD->parseURL($URI, false, $PROTO, true);
                    $FAMILYSITE = $MAINUTI["FAMILYSITE"];
                }
            }

            $ztimes = explode("/", $TR[9]);
            if (!isset($ztimes[3])) {
                $ztimes[3] = 0;
            }
            $duration = (intval($ztimes[2]) + intval($ztimes[3]) + intval($ztimes[4]));

            if (preg_match("#([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9:]+)#", $stime, $re)) {
                $yime = $re[4];
                $day = $re[1];
                $month = $re[2];

            }
            if ($HTTP_CODE > 399) {
                $color = "#D0080A";
            }
            if ($HTTP_CODE == 307) {
                $color = "#F59C44";
            }

            if ($SERVICE_LOG) {
                if (preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?haproxy\[([0-9]+)\]: Proxy\s+(.+?)\s+(.+)#", $line, $re)) {
                    VERBOSE("MATCHES 2", __LINE__);
                    $month = $re[1];
                    $day = $re[2];
                    $year = date("Y");
                    $yime = $re[3];
                    $client = "&nbsp;-&nbsp;";
                    $HTTP_CODE = $re[4];
                    $PROTO = "Service";
                    $URI = null;
                    $duration = 0;
                    $servicename = $re[5];
                    $URI_column = $re[6];
                    $duration_column = $tpl->icon_nothing();
                    $client = $tpl->icon_nothing();
                    $size = 0;
                }
            }
            if ($URI <> null) {
                $URI_column = $tpl->td_href($FAMILYSITE, $URI, "s_PopUp('$URI',1024,768)");
            }
            if ($backend <> null) {
                $backend_column = "$servicename/$backend";
            } else {
                $backend_column = "$servicename";
            }
            if ($duration > 0) {
                $duration_column =round($duration/1000,1)."s";
            }

            if ($HTTP_CODE == 0) {
                $HTTP_CODE = "&nbsp;";
            }
            if ($size == 0) {
                $size = $tpl->icon_nothing();
            }
        }
        else{
            $ArrayENC           = array();
            $URI_column         = null;
            $backend_column     = null;
            $duration_column    = null;
            $SERVICE_LOG        = false;
            $TR=preg_split("/[\s]+/", $line);
            if(count($TR)<5){continue;}
            $client=null;


            if(preg_match("#^haproxy\[([0-9])+]#",$TR[4])){
                $SERVICE_LOG=true;
            }

            $stime=str_replace("[", "", $TR[6]);
            $stime=str_replace("]", "", $stime);
            $color="#676A6C";

            if(preg_match("#^(.+?):#", $TR[5],$re)){$client=$re[1];}
            if(preg_match("#^.*?\/(.+)#", $TR[8],$re)){$backend=$re[1];}
            $servicename=$TR[7];
            $HTTP_CODE=intval($TR[10]);
            $PROTO=$TR[17];
            $PROTO=str_replace('"', "", $PROTO);
            $URI=$TR[18];
            $MAINUTI=$logfileD->parseURL($URI,false,$PROTO,true);
            $FAMILYSITE=$MAINUTI["FAMILYSITE"];
            $URL=$MAINUTI["URL"];
            $ztimes=explode("/",$TR[9]);
            $duration=intval($ztimes[0])+intval($ztimes[1])+intval($ztimes[2])+intval($ztimes[3]);
            $size=FormatBytes(intval($TR[11])/1024);
            if (!isset($TR[27])) {
                $PROTO = "TCP/UDP";
                if (intval($TR[10]) > 1024) {
                    $size = FormatBytes($TR[10] / 1024);
                } else {
                    $size = "{$TR[10]} Bytes";
                }
            }
            if(preg_match("#([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9:]+)#", $stime,$re)){
                $yime=$re[4];
                $day=$re[1];
                $month=$re[2];

            }
            if($HTTP_CODE>399){$color="#D0080A";}
            if($HTTP_CODE==307){$color="#F59C44";}

            if($SERVICE_LOG){
                if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?haproxy\[([0-9]+)\]: Proxy\s+(.+?)\s+(.+)#",$line,$re)){
                    $month=$re[1];
                    $day=$re[2];
                    $year=date("Y");
                    $yime=$re[3];
                    $client="&nbsp;-&nbsp;";
                    $HTTP_CODE=$re[4];
                    $PROTO="Service";
                    $URI=null;$duration=0;
                    $servicename=$re[5];
                    $URI_column=$re[6];
                    $duration_column=$tpl->icon_nothing();
                    $client=$tpl->icon_nothing();
                    $size=0;
                }
            }




            if($URI<>null){
                $URI_column=$tpl->td_href($FAMILYSITE,$URI,"s_PopUp('$URI',1024,768)");
            }
            if($backend<>null){
                $backend_column="$servicename/$backend";
            }else{
                $backend_column="$servicename";
            }
            if($duration>0) {
                $duration_column="{$duration}ms";
            }
            if (!isset($TR[28])) {
                $link = $tpl->icon_nothing();
                $URI = null;
                $URI_column = "TCP &raquo;&raquo; $backend";
                $color="#676A6C";
                $HTTP_CODE="";
            }

            if($size == 0){$size=$tpl->icon_nothing();}
        }

        $html[] = "<tr>
					<td width=1% nowrap><span style='color:$color'>$month $day | $yime</span></td>
					<td width=1% nowrap><span style='color:$color'>$client</span></td>
					<td width=1% nowrap><span style='color:$color'>{$HTTP_CODE}</span></td>
					<td width=1% nowrap><span style='color:$color'>{$PROTO}</span></td>
					<td><span style='color:$color'>$URI_column</span></td>
					<td style='width:1%;' nowrapclass=center><center>{$loupe}&nbsp;&nbsp;{$link}</center></td>
					<td width=1% nowrap>$backend_column</td>
					<td width=1% nowrap><span style='color:$color'>{$size}</span></td>
					<td width=1% nowrap><span style='color:$color'>$duration_column</span></td>
					</tr>";

    }
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='9'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</tbody></table>";
    $html[] = "<div style='font-size:10px'>" . @file_get_contents("/usr/share/artica-postfix/ressources/logs/haproxy.log.cmd") . "</div>";
    $html[] = "
					<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo @implode("\n", $html);


}