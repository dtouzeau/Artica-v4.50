<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.ad-agent.inc");
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["backends-start"])){backends_start();exit;}
if(isset($_GET["backends"])){backends();exit;}
if(isset($_GET["backend-status"])){backend_status();exit;}
if(isset($_GET["set-backends-js"])){set_backend_js();exit;}
if(isset($_GET["set-backends-popup"])){set_backend_popup();exit;}
if(isset($_POST["backendname"])){backends_save();exit;}

if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}

if(isset($_GET["service-start"])){service_start();exit;}
if(isset($_GET["service"])){service();exit;}
if(isset($_GET["adagent-status"])){adagent_status();exit;}
if(isset($_GET["adagent-top-status"])){adagent_top_status();exit;}

if(isset($_GET["adagent-parameters-js"])){adagent_parameters_js();exit;}
if(isset($_GET["adagent-parameters-popup"])){adagent_parameters_popup();exit;}
if(isset($_POST["ADAgentMaxConn"])){save();exit;}

if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["events-status"])){events_status();exit;}
if (isset($_GET["search"])) {
    search();
    exit;
}
if(isset($_GET["clean-cache"])){clean_plugin_cache();exit;}
if(isset($_GET["backend-delete-js"])){backend_delete_js();exit;}
if(isset($_POST["backend-delete"])){backend_delete();exit;}

if(isset($_GET["backend-stats-js"])){backend_stats_js();exit;}
if(isset($_GET["backend_stats"])){backend_stats();exit;}
page();

function backend_stats_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $servicename=$_GET["servicename"];
    $backendname=$_GET["backend-stats-js"];
    $tpl->js_dialog2("{statistics} $backendname","$page?backend_stats=yes&backend=$backendname");
    return true;
}

function backend_stats(){
    $hap= new adagent_backends("ad_agent_ha",$_GET["backend"]);
    $url="http://{$hap->listen_ip}:{$hap->listen_port}/get/agent/info";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_NOBODY, true);
//    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: $hap->backendtoken"
    ));
    curl_setopt($ch, CURLOPT_INTERFACE, "{$hap->localInterface}");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
// do your curl thing here
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result,true);
    $statsdec=base64_decode($data[0]["stats"]["stats"]);
    $statsdec = json_decode($statsdec,true);
    $page=CurrentPageName();
    $tpl=new template_admin();

   $html[]="<p><b>{version} :</b>{$data[0]["version"]}</p>";
    $html[]="<p><b>{build_ver} :</b>{$data[0]["build"]}</p>";
    $html[]="<p><b>{obects_in_cache} :</b>{$data[0]["stats"]["len"]}</p>";
    $html[]="<p><b>{hits} :</b>{$statsdec["hits"]}</p>";
    $html[]="<p><b>{capacity} :</b>{$data[0]["stats"]["capacity"]}</p>";

    //$tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function backend_delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $servicename=$_GET["servicename"];
    $backendname=$_GET["backend-delete-js"];
    $value="$servicename|$backendname";

    $js[]="LoadAjaxSilent('adagent-table-start','$page?backends-start=true');";
    $tpl->js_confirm_delete("$servicename/$backendname" , "backend-delete", $value,@implode(";", $js));

}

function backend_delete(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $f=explode("|",$_POST["backend-delete"]);
    $hap=new adagent_backends($f[0], $f[1]);
    $hap->DeleteBackend();

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $adagentVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
    if(empty($adagentVersion)){
        $adagent=new ADAgent();
        $adagentVersion=$adagent->adagentversion;
    }
    $Title="{APP_ADAGENT_LBL} v{$adagentVersion}";
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $explain="{AD_GROUP_SEARCH_SIMPLE_EXPLAIN}";
    $raccourci="ad-agent-ha";



    $html=$tpl->page_header($Title,"fas fa-user-secret",$explain,"$page?tabs=yes",$raccourci,"progress-adagent-restart",false,"table-loader-adagent-servers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin($Title,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}



function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ADAGENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_INSTALLED"));
    if($ADAGENT_INSTALLED==0){
        $help_url="https://wiki.articatech.com/en/proxy-service/authentication/ad-agent#this-feature-is-not-installed";
        $js_help="s_PopUpFull('$help_url','1024','900');";
        $html[]=$tpl->div_error("{feature_not_installed}||{error_feature_not_installed}||$js_help");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==0){
        $help_url="https://wiki.articatech.com/en/proxy-service/authentication/ad-agent#this-feature-is-not-installed";
        $js_help="s_PopUpFull('$help_url','1024','900');";
        $html[]=$tpl->div_error("{feature_not_installed}||{error_feature_not_installed}||$js_help");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }


    $array["{main}"] = "$page?service-start=true";
    $array["{agents}"] = "$page?backends-start=true";
    $array["{events}"] = "$page?events-start=true";
    echo $tpl->tabs_default($array);
    return true;
}

function events_start():bool{
    $page=CurrentPageName();

    echo "<div id='adagent-table-start'></div><script>
    LoadAjax('adagent-table-start','$page?events=yes');
</script>";
    return true;
}

function events_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $jsreload="LoadAjax('adagent-table-start','$page?events-start=true');";
    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsreload\"><i class='fal fa-sync-alt'></i> {refresh} </label>";

    $bts[]="</div>";
    $adagentVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
    if(empty($adagentVersion)){
        $adagent=new ADAgent();
        $adagentVersion=$adagent->adagentversion;
    }
    $Title="{APP_ADAGENT_LBL} v{$adagentVersion}";
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $explain="{AD_GROUP_SEARCH_SIMPLE_EXPLAIN}";
    $raccourci="ad-agent-ha";


    $TINY_ARRAY["TITLE"]=$Title;
    $TINY_ARRAY["ICO"]="fas fa-user-secret";
    $TINY_ARRAY["EXPL"]="$explain";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $final[]="<script>";
    $final[]=$jstiny;
    $final[]="</script>";

    echo $tpl->_ENGINE_parse_body($final);
}

function events(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $t = time();
    if (!isset($_SESSION["LB_SEARCH"])) {
        $_SESSION["LB_SEARCH"] = "50 events";
    }
    if (isset($_GET["logfile"])) {
        $addPLUS = "&logfile=" . urlencode($_GET["logfile"]);
    }
    $html[]="<div id='events-top-status' style='margin-top:15px'></div>";
    $html[] = "

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
	LoadAjaxSilent('events-top-status','$page?events-status=yes');     
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

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    //echo $tpl->_ENGINE_parse_body($html);
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

    $sock->getFrameWork("adagent.php?access-real=yes&rp={$MAIN["MAX"]}&query=" . urlencode($MAIN["TERM"]) . "&SearchString={$_GET["SearchString"]}&FinderList={$_GET["FinderList"]}$addPLUS");
    $filename = "/usr/share/artica-postfix/ressources/logs/adagent.log.tmp";
    $ipaddr = $tpl->javascript_parse_text("{members}");
    $destination = $tpl->javascript_parse_text("{destinations}");
    $zdate = $tpl->_ENGINE_parse_body("{zDate}");
    $proto = $tpl->_ENGINE_parse_body("{proto}");
    $uri = $tpl->_ENGINE_parse_body("{url}");
    $duration = $tpl->_ENGINE_parse_body("{duration}");
    $size = $tpl->_ENGINE_parse_body("{size}");
    $status_code = $tpl->_ENGINE_parse_body("{status}");


    $html[] = "

	<table class=\"table table-hover\">
	<thead>
	<tr>
	<th>$zdate</th>
	<th>$ipaddr</th>
	<th>$proto</th>
	<th>$uri</th>
	<th>$destination</th>
		<th>$status_code</th>
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
            $size=FormatBytes($TR[11]/1024);
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
                $URI_column=$tpl->td_href(urldecode($FAMILYSITE),$URI,"s_PopUp('$URI',1024,768)");
            }
            if($backend<>null){
                $backend_column="$servicename/$backend";
            }else{
                $backend_column="$servicename";
            }
            if($duration>0) {
                $duration_column="{$duration}ms";
            }


            if($size == 0){$size=$tpl->icon_nothing();}
        }

        $html[] = "<tr>
					<td width=1% nowrap><span style='color:$color'>$month $day | $yime</span></td>
					<td width=1% nowrap><span style='color:$color'>$client</span></td>
					<td width=1% nowrap><span style='color:$color'>{$PROTO}</span></td>
					<td><span style='color:$color'>$URI_column</span></td>
					<td width=1% nowrap>$backend_column</td>
					<td width=1% nowrap><span style='color:$color'>{$HTTP_CODE}</span></td>
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
function backends_start():bool{
    $page=CurrentPageName();

    echo "<div id='adagent-table-start'></div><script>
    LoadAjax('adagent-table-start','$page?backends=yes');
</script>";
    return true;
}

function stop_js(){
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->getFrameWork("adagent.php?stop-socket={$_GET["stop-js"]}");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('adagent-table-start','$page?backends-start=true');";

}

function start_js(){
    $page=CurrentPageName();
    $sock=new sockets();
    header("content-type: application/x-javascript");
    $sock->getFrameWork("adagent.php?start-socket={$_GET["start-js"]}");
    echo "LoadAjaxSilent('adagent-table-start','$page?backends-start=true');";
}

function backends(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $users=new usersMenus();
    $q=new mysql();

    $sock=new sockets();
    $sock->getFrameWork("adagent.php?global-stats=yes");
    $table=explode("\n",@file_get_contents(PROGRESS_DIR."/adagent.stattus.dmp"));
    if(count($table)<2){
        echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
        return;
    }
    $html[]="<div id='adagent-status' style='margin-top:15px'></div>";
    $html[]="<table id='table-adagent-chkbalancers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{servicename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{backends}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;&nbsp;&nbsp;&nbsp;OUT&nbsp;&nbsp;&nbsp;&nbsp;</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{requests}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='text-align:right'>{action}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $status["UNK"]="unknown";
    $status["INI"]="initializing";
    $status["SOCKERR"]="socket error";
    $status["L4OK"]="check passed on layer 4, no upper layers testing enabled";
    $status["L4TMOUT"]="layer 1-4 timeout";
    $status["L4CON"]="layer 1-4 connection problem";
    $status["L6OK"]="check passed on layer 6";
    $status["L6TOUT"]="layer 6 (SSL) timeout";
    $status["L6RSP"]="layer 6 invalid response - protocol error";
    $status["L7OK"]="check passed on layer 7";
    $status["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
    $status["L7TOUT"]="layer 7 (HTTP/SMTP) timeout";
    $status["L7RSP"]="layer 7 invalid response - protocol error";
    $status["L7STS"]="layer 7 response error, for example HTTP 5xx";

    $ERR["SOCKERR"]=true;
    $ERR["L4TMOUT"]=true;
    $ERR["L4CON"]=true;
    $ERR["L6TOUT"]=true;
    $ERR["L6RSP"]=true;
    $ERR["L7TOUT"]=true;
    $ERR["L7RSP"]=true;
    $ERR["L7STS"]=true;
    $TRCLASS=null;
    $q=new mysql();
    $typof=array(0=>"frontend", 1=>"backend", 2=>"server", 3=>"socket");

    foreach ($table as $num=>$ligne){

        $ligne=trim($ligne);
        if($ligne==null){continue;}
        if(preg_match("#\##", $ligne)){continue;}
        $f=explode(",", $ligne);
        $pxname=$f[0];
        $svname=$f[1];
        $qcur=$f[2];
        $qmax=$f[3];
        $scur=$f[4];
        $smax=$f[5];
        $slim=$f[6];
        $stot=$f[7];
        $bin=FormatBytes($f[8]/1024);
        $bout=FormatBytes($f[9]/1024);
        $dreq=$f[10];
        $dresp=$f[11];
        $ereq=$f[12];
        $econ=$f[13];
        $eresp=$f[14];
        $wretr=$f[15];
        $wredis=$f[16];
        $status=$f[17];
        $weight=$f[18];
        $act=$f[19];
        $bck=$f[20];
        $chkfail=$f[21];
        $chkdown=$f[22];
        $lastchg=$f[23];
        $downtime=$f[24];
        $qlimit=$f[25];
        $pid=$f[26];
        $iid=$f[27];
        $sid=$f[28];
        $throttle=$f[29];
        $lbtot=$f[30];
        $tracked=$f[31];
        $type=$typof[$f[32]];
        $rate=$f[33];
        $rate_lim=$f[34];
        $rate_max=$f[35];
        $check_status=$f[36];
        $check_code=$f[37];
        $check_duration=$f[38];
        $hrsp_1xx=$f[39];
        $hrsp_2xx=$f[40];
        $hrsp_3xx=$f[41];
        $hrsp_4xx=$f[42];
        $hrsp_5xx=$f[43];
        $hrsp_other=$f[44];
        $hanafail=$f[45];
        $req_rate=$f[46];
        $req_rate_max=$f[47];
        $req_tot=intval($f[48]);
        $cli_abrt=$f[49];
        $srv_abrt=$f[50];
        $httpstatus=$f[56];
        if(!is_numeric($req_tot)){$req_tot=0;}
        $img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";

        if(preg_match("#adagent\.stat#",$svname)){continue;}

        $padding=null;
        $color="black";
        $check_status_text=$status[$check_status];
        if(isset($ERR[$check_status])){$img="<div class='label label-danger' style='display:block;padding:5px'>$check_status</div>";$color="#D20C0C";}
        $md5=md5($ligne);
        $servicename=null;
        $servicename_text=null;
        $button=null;
        $h2=null;
        $h22=null;$js=null;$ico=null;
        $backendtot=$hrsp_1xx+$hrsp_2xx+$hrsp_3xx+$hrsp_4xx+$hrsp_5xx+$hrsp_other;
        $arraySRV=base64_encode(serialize(array($pxname,$svname)));
        $delbutton="";
        $statsicon="";
//        $disable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?backend-enable-js=$backendnameenc&servicename=$servicenameenc')",null,"AsSquidAdministrator");
//        $delete=$tpl->icon_delete("Loadjs('$page?backend-delete-js=$backendnameenc&servicename=$servicenameenc&md=$md')","AsSquidAdministrator");
        if($type=="frontend"){
            $h2="<H2>";
            $h22="</H2>";
            $ico="<i class='fa fa-share-alt'></i>&nbsp;";
            $FRONTEND=$pxname;}


        if($type=="server"){
            $ico="<i class='fa fa-desktop'></i>&nbsp;";
            $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$arraySRV')\">{stop}</button>";
            if($users->AsProxyMonitor) {
                $delbutton = $tpl->icon_delete("Loadjs('$page?backend-delete-js=$svname&servicename=ad_agent_ha')","AsSquidAdministrator");
                $statsicon = $tpl->icon_stats("Loadjs('$page?backend-stats-js=$svname&servicename=ad_agent_ha')","AsSquidAdministrator");
            }
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}

        }

        if($status=="MAINT"){
            $color="#F8AC59";
            $img="<div class='label label-warning' style='display:block;padding:5px'>{maintenance}</div>";
            $button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?start-js=$arraySRV')\">{start}</button>";
            if($users->AsProxyMonitor) {
                $delbutton = $tpl->icon_delete("Loadjs('$page?backend-delete-js=$svname&servicename=ad_agent_ha')","AsSquidAdministrator");
                $statsicon="";
            }
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

        }

        if(preg_match("#DOWN#", $status)){
            $downser="HaProxyUpserv('$arraySRV');";
            $button=null;
            $img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}\n$httpstatus</div>";
            $color="#D20C0C";
            $button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-js=$arraySRV')\">{start}</button>";
            if($users->AsProxyMonitor) {
                $delbutton = $tpl->icon_delete("Loadjs('$page?backend-delete-js=$svname&servicename=ad_agent_ha')","AsSquidAdministrator");
                ;
            }
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

        }

        if($req_tot==0){
            if($backendtot>0){$req_tot=$backendtot;}
        }

        if($type=="backend"){continue;}
        if($pxname=="admin_page"){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $svname_label=$svname;

        if(preg_match("#^default_(.+)#", $pxname,$re)){$servicename=$re[1];}

        if(preg_match("#backendid([0-9]+)$#", $svname,$re)){
            $backend_id=$re[1];
            $sql="SELECT backendname from adagent_backends WHERE ID='$backend_id'";
            $ligne_sql=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
            $svname_label=$ligne_sql["backendname"];
        }

        if($servicename<>null){$servicename_text=$servicename;}else{$servicename_text=$pxname;}
        if($type<>"frontend"){
            if($FRONTEND==$servicename){
                      $padding="padding-left:100px;";
                $js="Loadjs('$page?set-backends-js=$svname_label&servicename=ad_agent_ha');";

                $servicename_text=$tpl->td_href($svname_label,"$servicename/$svname_label",$js);}
        }

        $req_tot=FormatNumber($req_tot);

        $html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td width=1% nowrap>$img</td>";
        $html[]="<td><strong style=';color:$color;$padding'>$h2$ico$servicename_text$h22</strong></td>";
        $html[]="<td style='width:1%;color:$color' nowrap>$svname_label ($type - $status)</a></td>";
        $html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$bin</td>";
        $html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$bout</td>";
        $html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$req_tot</td>";
        $html[]="<td style='width:1%;color:$color;padding-left:35px' nowrap>$button&nbsp;$delbutton&nbsp;$statsicon</td>";
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
    $html[]="
	<script>
	LoadAjaxSilent('adagent-status','$page?backend-status=yes');
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-adagent-chkbalancers').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function service_start():bool{
    $page=CurrentPageName();

    echo "<div id='adagent-table-start'></div><script>
    LoadAjax('adagent-table-start','$page?service=yes');
</script>";
    return true;
}

function service(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $hap = new adagent_multi("ad_agent_ha");
    $page=CurrentPageName();
    $ERR=null;
    $ADAgentMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentMaxConn"));
    $ADAgentCPUS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentCPUS"));
    $ADAgentMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentMemoryCache"));
    $ADAgentMaxMemoryObjects =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentMaxMemoryObjects"));
    if($ADAgentMaxMemoryObjects==0){$ADAgentMaxMemoryObjects=10000;}
    if($ADAgentMaxConn<2000){$ADAgentMaxConn=2000;}
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
            $sock=new sockets();
            $CPU_NUMBER=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
        }else{
            $CPU_NUMBER=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
        }
    }


    for($i=1;$i<$CPU_NUMBER+1;$i++){
        $s=null;
        if($i>1){$s="s";}
        $CPUz[$i]="$i {cpu}{$s}";
    }
    $ADUserCanConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AD_Agent_LBL_USER_CAN_CONNECT"));
    $AD_Agent_LBL_Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AD_Agent_LBL_Port"));
    $DynamicGroupsAclsTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicGroupsAclsTTL"));
    if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}
    $tpl->table_form_field_js("Loadjs('$page?adagent-parameters-js=yes')","AsSystemAdministrator");
    $tpl->table_form_section("{parameters}");
    $tpl->table_form_field_text("{maxconn}",$ADAgentMaxConn,"fas fa-bolt");
    $tpl->table_form_field_text("{SquidCpuNumber}",$ADAgentCPUS,"fas fa-microchip");
    $tpl->table_form_field_text("{squid_cache_memory} (MB)",$ADAgentMemoryCache,ico_database);
    $tpl->table_form_field_text("{maximum_object_size_in_memory} (bytes)",$ADAgentMaxMemoryObjects,ico_database);
    $tpl->table_form_field_text("{listen_port}",$AD_Agent_LBL_Port,"fas fa-ethernet");
    $tpl->table_form_field_bool("{ADUserCanConnect}",$ADUserCanConnect,"fas fa-user");
    $tpl->table_form_field_text("{dispatch_method2}",$hap->algo[$hap->dispatch_mode],"fas fa-scale-balanced");
    $tpl->table_form_field_text("{QUERY_GROUP_TTL_CACHE}",$DynamicGroupsAclsTTL,"fas fa-clock");

    $jstiny=null;
    $myform=$tpl->table_form_compile();
    $html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='adagent-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>
		    <div id='adagent-top-status'></div>
		    $myform
		</td>
	</tr>
	</table>
	<script>
	$jstiny
	LoadAjaxSilent('adagent-status','$page?adagent-status=yes');
	LoadAjaxSilent('adagent-top-status','$page?adagent-top-status=yes');
	
	</script>	
	";


    echo $tpl->_ENGINE_parse_body($html);
    return true;



}

function backend_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsadd="Loadjs('$page?set-backends-js=&servicename=ad_agent_ha');";

    $jsreload="LoadAjax('adagent-table-start','$page?backends-start=true');";
    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsadd\"><i class='fal fa-plus'></i> {new} </label>";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsreload\"><i class='fal fa-sync-alt'></i> {refresh} </label>";

    $bts[]="</div>";
    $adagentVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
    if(empty($adagentVersion)){
        $adagent=new ADAgent();
        $adagentVersion=$adagent->adagentversion;
    }
    $Title="{APP_ADAGENT_LBL} v{$adagentVersion}";
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $explain="{AD_GROUP_SEARCH_SIMPLE_EXPLAIN}";
    $raccourci="ad-agent-ha";


    $TINY_ARRAY["TITLE"]=$Title;
    $TINY_ARRAY["ICO"]="fas fa-user-secret";
    $TINY_ARRAY["EXPL"]="$explain";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $final[]="<script>";
    $final[]=$jstiny;
    $final[]="</script>";

    echo $tpl->_ENGINE_parse_body($final);
}

function clean_plugin_cache(){
    $page=CurrentPageName();
    $libmem=new lib_memcached();
    $keys = $libmem->allKeys();
    $regex = 'ADAGENT_.*';
    foreach($keys as $item) {
        if(preg_match('/'.$regex.'/', $item)) {
            echo "$item \n";
            $libmem->Delkey($item);
        }
    }
    echo "LoadAjaxSilent('adagent-status','$page?adagent-status=yes');";
    return true;
}
function adagent_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $sock->getFrameWork('adagent.php?main-status=yes');
    $ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/adagent.status");

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent.progress.txt";
    $ARRAY["CMD"]="adagent.php?restart=yes";
    $ARRAY["TITLE"]="{restart}";
    $ARRAY["AFTER"]="LoadAjax('adagent-table-start','$page?service=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-adagent-restart')";


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent.progress.txt";
    $ARRAY["CMD"]="adagent.php?reload=yes";
    $ARRAY["TITLE"]="{reloading}";
    $ARRAY["AFTER"]="LoadAjax('adagent-table-start','$page?service=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsreload="Loadjs('fw.progress.php?content=$prgress&mainid=progress-adagent-restart')";

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent-stop.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent-stop.progress.txt";
    $ARRAY["CMD"]="adagent.php?stop=yes";
    $ARRAY["TITLE"]="{stopping_service}";
    $ARRAY["AFTER"]="LoadAjax('adagent-table-start','$page?service=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsstop="Loadjs('fw.progress.php?content=$prgress&mainid=progress-adagent-restart')";

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent-stop.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent-stop.progress.txt";
    $ARRAY["CMD"]="adagent.php?start=yes";
    $ARRAY["TITLE"]="{starting_service}";
    $ARRAY["AFTER"]="LoadAjax('adagent-table-start','$page?service=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsstart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-adagent-restart')";
    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-warning\" OnClick=\"$jsrestart\"><i class='fas fa-refresh'></i> {restart} </label>";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsreload\"><i class='fas fa-rotate-right'></i> {reload} </label>";
    $bts[]="<label class=\"btn btn btn-danger\" OnClick=\"$jsstop\"><i class='fas fa-stop'></i> {stop} </label>";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsstart\"><i class='fas fa-play'></i> {start} </label>";
    $bts[]="</div>";

    $final[]=$tpl->SERVICE_STATUS($ini, "APP_ADAGENT_LBL",$jsrestart);
    $adagentVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
    if(empty($adagentVersion)){
        $adagent=new ADAgent();
        $adagentVersion=$adagent->adagentversion;
    }
    $Title="{APP_ADAGENT_LBL} v{$adagentVersion}";
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $explain="{AD_GROUP_SEARCH_SIMPLE_EXPLAIN}";
    $raccourci="ad-agent-ha";


    $TINY_ARRAY["TITLE"]=$Title;
    $TINY_ARRAY["ICO"]="fas fa-user-secret";
    $TINY_ARRAY["EXPL"]="$explain";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $final[]="<script>";
    $final[]=$jstiny;
    $final[]="</script>";
    $libmem=new lib_memcached();
    $keys = $libmem->allKeys();
    $regex = 'ADAGENT_.*';
    $listKeys = array();
    foreach($keys as $item) {
        if(preg_match('/'.$regex.'/', $item)) {
            array_push($listKeys,$item);
        }
    }
    $c = count($listKeys);
    $button["ico"]="fa-solid fa-trash";
    $button["name"] = "{clean}";
    $button["js"] = "Loadjs('$page?clean-cache=yes');";

    $about=$tpl->widget_h("yellow","fa-solid fa-database","$c","{cached} {items}",$button);


    echo $tpl->_ENGINE_parse_body($final);
    echo $about;

}

function adagent_top_status() {
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));


    $jsrefres="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');LoadAjaxSilent('adagent-status','$page?adagent-status=yes');LoadAjaxSilent('adagent-top-status','$page?adagent-top-status=yes');";


    if($EnableExternalACLADAgent==0){
        $jsbut=$tpl->framework_buildjs("adagent.php?install=yes","adagent.progress","adagent.log","progress-adagent-restart",$jsrefres,null,null,"AsSquidAdministrator");
        $button["name"]="{install2}";
        $button["js"]=$jsbut;
        $button["help"]="https://wiki.articatech.com/en/proxy-service/authentication/ad-agent";
        $FW = $tpl->widget_h("gray", "fas fa-user-secret", "{uninstalled}", "{APP_ADAGENT_LBL}",$button);


    }else{
        $jsbut=$tpl->framework_buildjs("adagent.php?uninstall=yes","adagent.progress","adagent.log","progress-adagent-restart",$jsrefres,null,null,"AsSquidAdministrator");
        $button["name"]="{uninstall}";
        $button["js"]=$jsbut;
        $button["help"]="https://wiki.articatech.com/en/proxy-service/authentication/ad-agent";
        $FW = $tpl->widget_h("green", "fas fa-user-secret", "{installed}", "{APP_ADAGENT_LBL}",$button);


    }






    $html[]="<div style='margin-left:10px;margin-top:5px'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td width=33% style='padding-left:10px'>$FW</td>";
    $html[]="</tr>";
    $html[]="</table></div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function set_backend_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $servicename=$_GET["servicename"];
    $backendservicename=$_GET["set-backends-js"];
    $txt="{new}";
    if(!empty($backendservicename)){
        $txt="{edit}";
    }
    $tpl->js_dialog2("$txt","$page?set-backends-popup=$backendservicename&servicename=$servicename");
    return true;
}
function set_backend_popup()
{
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $servicename    = $_GET["servicename"];
    $servicenameenc = urlencode($servicename);
    $backendname    =$_GET["set-backends-popup"];

    $smtp_disable   = 0;
    $hapServ        = new adagent_multi($servicename);
    $hapMaster      = $hapServ;
    $loadbalancetype = $hapServ->loadbalancetype;
    $servicetype_array= $hapServ->servicetype_array;
    $servicetype    = $hapServ->servicetype;
    $UseSMTPProto   = $hapServ->MainConfig["UseSMTPProto"];
    if($servicetype<4){$smtp_disable=1;}
    $IS_HTTP        = false;

    if($servicetype==0 OR $servicetype==1){$IS_HTTP=true;}

    $Connector_text = $servicetype_array[$servicetype];

    if(!is_numeric($UseSMTPProto)){$UseSMTPProto=0;}
    $hap=new adagent_backends($servicename,$backendname);
    $remove_this_backend=$tpl->javascript_parse_text("{remove_this_backend}");

    $jsafter[]="LoadAjax('adagent-table-start','$page?backends=yes');";
//    $jsafter[]="LoadAjax('backend-list','$page?table=$servicenameenc');";

    $buttonname="{apply}";
    if($backendname==null){$buttonname="{add}";$toolbox=null;}
    if(!is_numeric($hap->MainConfig["inter"])){$hap->MainConfig["inter"]=10000;}
    if(!is_numeric($hap->MainConfig["fall"])){$hap->MainConfig["fall"]=3;}
    if(!is_numeric($hap->MainConfig["rise"])){$hap->MainConfig["rise"]=2;}
    if(!is_numeric($hap->MainConfig["maxconn"])){$hap->MainConfig["maxconn"]=10000;}
    if(!is_numeric($hap->MainConfig["asSquidArtica"])){$hap->MainConfig["asSquidArtica"]=0;}
    if(!is_numeric($hap->MainConfig["UseSSL"])){$hap->MainConfig["UseSSL"]=0;}
    if(!is_numeric($hap->MainConfig["proxy_protocol"])){$hap->MainConfig["proxy_protocol"]=0;}


    $ip=new networking();
    $Interfaces=$ip->Local_interfaces();
    $Interfaces[null]="{default}";
    unset($Interfaces["lo"]);


    $form[]=$tpl->field_info("servicename", "{servicename}", $servicename,true);
    if($backendname<>null){
        $form[]=$tpl->field_info("backendname", "{backendname}", $backendname);
        $title="$servicename/$backendname ($Connector_text)";

    }else{
        $form[]=$tpl->field_text("backendname", "{backendname}", null,true);
        $title="$servicename/{new_backend} ($Connector_text)";
        $jsafter[]="dialogInstance2.close();";
    }

    $jsafter[]="if( document.getElementById('adagent-table-start') ){ LoadAjaxSilent('adagent-table-start','$page?backends-start=true');}";

    if($hap->listen_port<2){$hap->listen_port=8080;}

    $form[]=$tpl->field_array_hash($Interfaces, "localInterface", "{outgoing_address}", $hap->localInterface,false,"{haproxy_local_interface_help}");
    $form[]=$tpl->field_ipaddr("listen_ip", "{destination_address}", $hap->listen_ip,true);
    $form[]=$tpl->field_numeric("listen_port","{destination_port}", $hap->listen_port);
    $form[]=$tpl->field_text("backendntoken","{token}", $hap->backendtoken,true);
//    $form[]=$tpl->field_checkbox("proxy_protocol","{proxy_protocol}",$hap->MainConfig["proxy_protocol"],false,"{proxy_protocol_explain}");

    //$form[]=$tpl->field_checkbox("FailOverOnly","{failover_only}",$hap->MainConfig["FailOverOnly"]);


//    if($IS_HTTP) {
//        if ($hapMaster->loadbalancetype == 2) {
//            $form[] = $tpl->field_section("{HTTP_PROXY_MODE}", "{WARN_HTTP_PROXY_MODE2}");
//        } else {
//            $form[] = $tpl->field_section("{HTTP_PROXY_MODE}");
//        }
//        $form[]=$tpl->field_checkbox("UseSSL","{remote_server_use_ssl}",$hap->MainConfig["UseSSL"], false);
//        $form[] = $tpl->field_checkbox("asSquidArtica", "{artica_proxy}", $hap->MainConfig["asSquidArtica"]);
//    }

    if($servicetype==4) {
        if ($smtp_disable == 0) {
            $form[] = $tpl->field_section("{SMTP_MODE}");
            $form[] = $tpl->field_checkbox("postfix-send-proxy", "{postfix_send_proxy}", $hap->MainConfig["postfix-send-proxy"]);
        }
    }

    $form[]=$tpl->field_section("{timeouts}");
    $form[]=$tpl->field_numeric("bweight","{weight}", $hap->bweight);
    $form[]=$tpl->field_numeric("maxconn","{max_connections}", $hap->MainConfig["maxconn"]);
    $form[]=$tpl->field_numeric("inter","{check_interval} ({milliseconds})", $hap->MainConfig["inter"]);
    $form[]=$tpl->field_numeric("fall","{failed_number} ({attempts})", $hap->MainConfig["fall"]);
    $form[]=$tpl->field_numeric("rise","{success_number} ({attempts})", $hap->MainConfig["rise"]);



    $html=$tpl->form_outside($title, @implode("\n", $form),null,$buttonname,@implode("", $jsafter),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function adagent_parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{parameters}","$page?adagent-parameters-popup=yes");
    return true;
}
function adagent_parameters_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $ADAgentMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentMaxConn"));
    $ADAgentCPUS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentCPUS"));
    $ADAgentMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentMemoryCache"));
    $ADAgentMaxMemoryObjects =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAgentMaxMemoryObjects"));
    if($ADAgentMaxMemoryObjects==0){$ADAgentMaxMemoryObjects=10000;}
    if($ADAgentMaxConn<2000){$ADAgentMaxConn=2000;}
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
            $sock=new sockets();
            $CPU_NUMBER=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
        }else{
            $CPU_NUMBER=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
        }
    }


    for($i=1;$i<$CPU_NUMBER+1;$i++){
        $s=null;
        if($i>1){$s="s";}
        $CPUz[$i]="$i {cpu}{$s}";
    }
    $AD_Agent_LBL_Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AD_Agent_LBL_Port"));
    if($AD_Agent_LBL_Port==0){
        $AD_Agent_LBL_Port=8080;
    }
    $DynamicGroupsAclsTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicGroupsAclsTTL"));
    if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}
    $ttl_interval[30]="30 {seconds}";
    $ttl_interval[60]="1 {minute}";
    $ttl_interval[300]="5 {minutes}";
    $ttl_interval[600]="10 {minutes}";
    $ttl_interval[900]="15 {minutes}";
    $ttl_interval[1800]="30 {minutes}";
    $ttl_interval[3600]="1 {hour}";
    $ttl_interval[7200]="2 {hours}";
    $ttl_interval[14400]="4 {hours}";
    $ttl_interval[18000]="5 {hours}";
    $ttl_interval[86400]="1 {day}";
    $ttl_interval[172800]="2 {days}";
    $ttl_interval[259200]="3 {days}";
    $ttl_interval[432000]="5 {days}";
    $ttl_interval[604800]="1 {week}";
    $hap = new adagent_multi("ad_agent_ha");
    $ADUserCanConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AD_Agent_LBL_USER_CAN_CONNECT"));
    $form[]=$tpl->field_numeric("ADAgentMaxConn","{maxconn}",$ADAgentMaxConn,"{haproxy_maxconn}");
    $form[]=$tpl->field_array_hash($CPUz,"ADAgentCPUS","nonull:{SquidCpuNumber}",$ADAgentCPUS,false,"{haproxy_nbproc}");
    $form[]=$tpl->field_numeric("ADAgentMemoryCache","{squid_cache_memory} (MB)",$ADAgentMemoryCache,"");
    $form[]=$tpl->field_numeric("ADAgentMaxMemoryObjects","{maximum_object_size_in_memory} (bytes)",$ADAgentMaxMemoryObjects,"");
    $form[]=$tpl->field_numeric("AD_Agent_LBL_Port","{listen_port}",$AD_Agent_LBL_Port,"");
    $form[] = $tpl->field_checkbox("AD_Agent_LBL_USER_CAN_CONNECT", "{ADUserCanConnect}", $ADUserCanConnect, false, null);
    $form[] = $tpl->field_array_hash($hap->algo, "dispatch_mode", "{dispatch_method2}", $hap->dispatch_mode);
    $form[]=$tpl->field_array_hash($ttl_interval,"DynamicGroupsAclsTTL","{QUERY_GROUP_TTL_CACHE}",$DynamicGroupsAclsTTL);

    $jsafter[]="LoadAjax('adagent-table-start','$page?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/adagent.progress.txt";
    $ARRAY["CMD"]="adagent.php?reload=yes";
    $ARRAY["TITLE"]="{reloading}";
    $ARRAY["AFTER"]="LoadAjax('table-loader','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter[]="Loadjs('fw.progress.php?content=$prgress&mainid=progress-adagent-restart')";
    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsSquidAdministrator");
    return true;


}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $hap = new adagent_multi("ad_agent_ha");

    $hap->servicename="ad_agent_ha";
    $hap->servicetype=0;
    $hap->listen_ip="127.0.0.1";
    $listenaddrport=intval($_POST["AD_Agent_LBL_Port"]);
    if($listenaddrport==0){
        $listenaddrport=8080;
    }
    $hap->listen_port=$listenaddrport;
    $hap->MainConfig["http-keep-alive"]=0;
    $hap->MainConfig["contimeout"] = 4000;
    $hap->MainConfig["smtpchk_EHLO"]="";
    $hap->MainConfig["srvtimeout"] = 50000;
    $hap->MainConfig["clitimeout"] = 15000;
    $hap->MainConfig["retries"] = 3;
    $hap->MainConfig["UseCookies"] = 0;
    $hap->MainConfig["NTLM_COMPATIBILITY"] = 0;
    $hap->MainConfig["asSquidArtica"] = 0;
    $hap->MainConfig["HttpKeepAliveTimeout"] = 15000;
    $hap->MainConfig["TimeoutTunnel"] = 30000;
    $hap->MainConfig["HttpRequestTimeout"] = 50000;
    $hap->MainConfig["HttpQueueTimeout"] = 50000;
    $hap->dispatch_mode=$_POST["dispatch_mode"];
    $hap->save();
    $tpl->SAVE_POSTs();
}

function backends_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $btoken=$_POST["backendntoken"];
    $url="http://{$_POST["listen_ip"]}:{$_POST["listen_port"]}/";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: $btoken"
    ));
    curl_setopt($ch, CURLOPT_INTERFACE, "{$_POST["localInterface"]}");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
// do your curl thing here
    $result = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($http_status!="200"){
        echo "jserror:Unable to connect to {$_POST["listen_ip"]}:{$_POST["listen_port"]}, status code $http_status";
        return false;
    }



    $hap=new adagent_backends($_POST["servicename"], $_POST["backendname"]);
//    if($hap->check_duplicates($_POST["listen_ip"])){
//        echo "jserror:Duplicated IP";
//    }
    $hap->listen_ip=$_POST["listen_ip"];
    $hap->listen_port=$_POST["listen_port"];
    $hap->backendtoken=$_POST["backendntoken"];
    $hap->bweight=$_POST["bweight"];
    $hap->localInterface=$_POST["localInterface"];
    $hap->MainConfig["inter"]=$_POST["inter"];
    $hap->MainConfig["fall"]=$_POST["fall"];
    $hap->MainConfig["rise"]=$_POST["rise"];
    $hap->MainConfig["maxconn"]=$_POST["maxconn"];
    $hap->MainConfig["FailOverOnly"]=0;

    if(isset($_POST["asSquidArtica"])) {
        $hap->MainConfig["asSquidArtica"] = $_POST["asSquidArtica"];
    }
    if(isset($_POST["UseSSL"])) {
        $hap->MainConfig["UseSSL"] = $_POST["UseSSL"];
    }
    if(isset($_POST["proxy_protocol"])) {
        $hap->MainConfig["proxy_protocol"] = $_POST["proxy_protocol"];
    }
    if(isset($_POST["postfix-send-proxy"])){$hap->MainConfig["postfix-send-proxy"]=$_POST["postfix-send-proxy"];}

    $hap->save();
}


