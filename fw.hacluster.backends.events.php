<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["sysevnts"])){events_backend_js();exit;}
if(isset($_GET["sysevnts-form"])){events_backend_page();exit;}
if(isset($_GET["sysevnts-search"])){events_backend_search();exit;}



function events_backend_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $HostID     = intval($_GET["sysevnts"]);
    $tpl->js_dialog3("{events}: #$HostID","$page?sysevnts-form=$HostID",850);
}
function events_backend_page(){
    $ID=intval($_GET["sysevnts-form"]);
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t=time();

    $html[]="<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["HACLUSTER_BACKENDS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	    </span>
      	    </div>
      	    <div id='table-$t'></div>";

    $html[]="<script>";
    $html[]="function Search$t(e){";
    $html[]="if(!checkEnter(e) ){return;}";
    $html[]="ss$t();";
    $html[]="}";

    $html[]="function ss$t(){";
    $html[]="var ss=encodeURIComponent(document.getElementById('search-this-$t').value);";
    $html[]="LoadAjax('table-$t','$page?sysevnts-search='+ss+'&hostid=$ID');";
    $html[]="}";

    $html[]="function Start$t(){";
    $html[]="var ss=document.getElementById('search-this-$t').value;";
    $html[]="ss$t();";
    $html[]="}";
    $html[]="Start$t();";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}
function events_backend_search(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $MAIN   = $tpl->format_search_protocol($_GET["sysevnts-search"]);
    $HostID = intval($_GET["hostid"]);

    $rp = intval($MAIN["MAX"]);
    if ($rp <= 0)   { $rp = 200; }
    if ($rp > 1000) { $rp = 1000; }
    $search = trim($MAIN["TERM"]);

    // HostID == hacluster_backends.ID == API node_id, so query by-id.
    $url  = "/haclient/events/by-id/" . $HostID . "?limit=" . $rp;
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API($url);
    $json = json_decode($data);

    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}<hr>" . json_last_error_msg()));
        return;
    }
    if (!is_object($json) || empty($json->success)) {
        $err = (is_object($json) && isset($json->error)) ? $json->error : "API error";
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}<hr>" . htmlspecialchars($err)));
        return;
    }

    $severityCL[0]="label-danger";
    $severityCL[1]="label-warning";
    $severityCL[2]="label-primary";

    $severityTX[0]="text-danger";
    $severityTX[1]="text-warning";
    $severityTX[2]="text-primary";

    $sevName = [0=>"DEBUG", 1=>"INFO", 2=>"WARN", 3=>"ERROR", 4=>"FATAL"];

    $events = (isset($json->data->events) && is_array($json->data->events)) ? $json->data->events : [];

    // API has no LIKE filter on msg/func; apply substring match client-side.
    if (strlen($search) >= 3) {
        $events = array_values(array_filter($events, function($e) use ($search) {
            return stripos($e->msg ?? "", $search) !== false
                || stripos($e->func ?? "", $search) !== false;
        }));
    }

    if (count($events) > 3) {
        $_SESSION["HACLUSTER_BACKENDS_SEARCH"] = $_GET["sysevnts-search"];
    }

    $date_text   = $tpl->_ENGINE_parse_body("{date}");
    $events_text = $tpl->_ENGINE_parse_body("{events}");

    $html = [];
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>{level}</th>
        	<th>{function}</th>
        	<th>$events_text</th>
        </tr>
  	</thead>
	<tbody>
";

    foreach ($events as $event){
        $level = intval($event->level ?? 0);
        if ($level < 0) { $level = 0; }
        if ($level > 4) { $level = 4; }

        // Map API level (0..4) into the 3-bucket severity arrays.
        if     ($level >= 3) { $sev = 0; }   // error / fatal
        elseif ($level == 2) { $sev = 1; }   // warn
        else                 { $sev = 2; }   // debug / info

        $clsLabel = $severityCL[$sev];
        $clsText  = $severityTX[$sev];
        $name     = $sevName[$level];

        $xtime = intval($event->timestamp ?? 0);
        $FTime = $xtime > 0 ? date("Y-m-d H:i:s", $xtime) : "-";
        $curDate = date("Y-m-d");
        $FTime = trim(str_replace($curDate, "", $FTime));

        // Escape braces so _ENGINE_parse_body doesn't treat user payload as a translation key.
        $func = str_replace(["{","}"], ["&#123;","&#125;"], htmlspecialchars($event->func ?? ""));
        $msg  = str_replace(["{","}"], ["&#123;","&#125;"], htmlspecialchars($event->msg  ?? ""));

        $html[]="<tr>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap><span class='label $clsLabel' style='color:#fff'>$name</span></td>
				<td style='width:1%' nowrap><span style='font-family:monospace'>$func</span></td>
				<td><span class='$clsText'>$msg</span></td>
				</tr>";
    }

    $html[]="</tbody></table>";

    $total = intval($json->data->total ?? count($events));
    $shown = count($events);
    $html[]="<div class='text-muted small' style='padding:5px 0'>Showing $shown of $total event(s)</div>";

    echo $tpl->_ENGINE_parse_body($html);
}
