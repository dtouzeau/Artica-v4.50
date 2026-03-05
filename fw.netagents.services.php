<?php
// Network Agents Management services page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start-point"])){start_point();exit;}
if(isset($_GET["monitored-point"])){monitored_point();exit;}
if(isset($_GET["monitored-search"])){monitored_search();exit;}
if(isset($_GET["action"])){service_action();exit;}
if(isset($_GET["refresh"])){do_refresh();exit;}
if(isset($_GET["monit-action"])){monit_action();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    return $tpl->js_dialog11("{services}","$page?tabs=$id",1024);
}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["tabs"]);
    $tabs["{monitored}"]="$page?monitored-point=$id";
    $tabs["{active2}"]="$page?start-point=$id&active=active";
    $tabs["{inactive2}"]="$page?start-point=$id&active=inactive";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->tabs_default($tabs);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function monitored_point():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["monitored-point"]);
    $html[]="<div style='margin-top:8px;'>";
    $html[]= $tpl->search_block($page,"","","monitored-table-$id","&id=$id&monitored-search=yes");
    $html[]="<div id='monitored-table-$id'></div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function start_point():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["start-point"]);
    $html[]="<div style='margin-top:8px;'>";
    $html[]= $tpl->search_block($page,"","","svc-table-$id","&id=$id&active={$_GET["active"]}");
    $html[]="<div id='svc-table-$id'></div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
// Loadjs: trigger agent services cache refresh, then reload table
function do_refresh():void{
    header("content-type: application/x-javascript");
    $id=intval($_GET["refresh"]);
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/services/$id/refresh",[]));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?addslashes($json->Error??"Unknown error"):"Failed to contact agent";
        echo "toastr.error('$err');";
        return;
    }
    if(isset($json->success)&&!$json->success){
        $err=addslashes($json->message??"Refresh failed");
        echo "toastr.error('$err');";
        return;
    }
    echo "LoadAjaxSilent('svc-table-$id','$page?search=&id=$id');";
}
// Loadjs: monit start/stop on a monitored service
function monit_action():void{
    header("content-type: application/x-javascript");
    $id=intval($_GET["id"]??0);
    $svc=trim($_GET["svc"]??"");
    $action=trim($_GET["monit-action"]??"");
    $page=CurrentPageName();
    if(!in_array($action,["start","stop"])||empty($svc)){
        echo "toastr.error('Invalid monit action');";
        return;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/services/$id/monitored/$action",
        ["service"=>$svc]
    ));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?addslashes($json->Error??"Unknown error"):"Failed to contact agent";
        echo "toastr.error('$err');";
        return;
    }
    if(!$json->success){
        $err=addslashes($json->message??"Action failed");
        echo "toastr.error('$err');";
        return;
    }
    $label=addslashes($svc);
    echo "toastr.success('Monit ".ucfirst($action)." $label OK');";
    echo "LoadAjaxSilent('monitored-table-$id','$page?search=&id=$id&monitored-search=yes');";
}

// Loadjs: start/stop/restart a named service
function service_action():void{
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    $id=intval($_GET["id"]??0);
    $svc=trim($_GET["svc"]??"");
    $action=trim($_GET["action"]??"");
    $page=CurrentPageName();
    $allowed=["start","stop","restart"];
    if(!in_array($action,$allowed)||empty($svc)){
        echo "toastr.error('Invalid action');";
        return;
    }
    $svc_enc=urlencode($svc);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/services/$id/$svc_enc/$action",[]));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?addslashes($json->Error??"Unknown error"):"Failed to contact agent";
        echo "toastr.error('$err');";
        return;
    }
    if(!$json->success){
        $err=addslashes($json->error??"Action failed");
        echo "toastr.error('$err');";
        return;
    }
    $label=addslashes($svc);
    echo "toastr.success('".ucfirst($action)." $label OK');";
    echo "$function();";
}

function monitored_search():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $search=trim($_GET["search"]??"");
    $page=CurrentPageName();

    // GET /netagents/services/{id}/monitored/status → MonitStatusResponse
    // success=false means monit not installed/reachable (not a transport error)
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/services/$id/monitored/status"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?htmlspecialchars($json->Error??"{unknown_error}"):"{failed_to_contact_agent}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        echo "<script>NoSpinner();</script>";
        return false;
    }
    if(!$json->success){
        $msg=htmlspecialchars($json->message??"{monit_not_installed}");
        echo $tpl->_ENGINE_parse_body($tpl->div_info($msg));
        echo "<script>NoSpinner();</script>";
        return true;
    }

    // services is a map[string]MonitServiceInfo → stdClass in PHP
    $services=is_object($json->services??null)?get_object_vars($json->services):[];

    // Client-side filter by name or status_label
    if(strlen($search)>=2){
        $services=array_filter($services,function($svc) use($search){
            return stripos($svc->name??"",$search)!==false
                || stripos($svc->status_label??"",$search)!==false;
        });
    }

    $html=[];

    // Server info bar (version, hostname, uptime)
    if(isset($json->server)&&is_object($json->server)){
        $srv=$json->server;
        $ver=htmlspecialchars($srv->version??"");
        $hostname=htmlspecialchars($srv->hostname??"");
        $up=format_uptime(intval($srv->uptime??0));
        $html[]="<div style='padding:5px 8px;margin-bottom:6px;background:#f5f5f5;border-left:3px solid #1c84c6;font-size:11px'>";
        $html[]="<i class='fa fa-heartbeat'></i> <strong>monit</strong> $ver";
        if($hostname) $html[]="&nbsp;&mdash; <span style='font-family:monospace'>$hostname</span>";
        if($up!=="-") $html[]="&nbsp;<small style='color:#888'>{uptime}: $up</small>";
        $html[]="&nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>".count($services)."</span>";
        $html[]="</div>";
    }

    if(empty($services)){
        $html[]=$tpl->div_info("{no_results}");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $html[]="<table class='table table-striped table-hover'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>{status}</th>";
    $html[]="<th>{services}</th>";
    $html[]="<th nowrap>{pid}</th>";
    $html[]="<th nowrap>{memory}</th>";
    $html[]="<th nowrap>{uptime}</th>";
    $html[]="<th nowrap>{action}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";
    $wd1="style='width:1%' nowrap";
    $TRCLASS=null;

    foreach($services as $name=>$svc){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $monitor=intval($svc->monitor??0);
        $status=intval($svc->status??0);
        $status_label=htmlspecialchars($svc->status_label??"unknown");
        $monitor_label=htmlspecialchars($svc->monitor_label??"");

        // Status badge: unmonitored→gray, ok(status=0)→primary, problem→danger
        if($monitor===0){
            $badge="<span class='label label-default'>{not_monitored}</span>";
        }elseif($status===0){
            $badge="<span class='label label-primary'>$status_label</span>";
        }else{
            $badge="<span class='label label-danger'>$status_label</span>";
        }

        // Name + monitor mode below
        $name_html=htmlspecialchars($name);
        if(strpos(" $name_html","APP_")>0){
            $name_html="{{$name_html}}";
        }

        $name_cell="<strong style='font-family:monospace'>$name_html</strong>";
        if($monitor_label) $name_cell.="<br><small style='color:#888'>$monitor_label</small>";

        // PID
        $pid=intval($svc->pid??0);
        $pid_cell=$pid>0?$pid:"-";
        $strong="<span class='text-muted'>";
        $strongoff="</span>";

        // Memory: mem_kb is already in kilobytes — FormatBytes() takes KB directly
        $mem_kb=intval($svc->mem_kb??0);
        if( ($mem_kb/1024)>500){
            $strong="<strong>";
            $strongoff="</strong>";
        }
        if( ($mem_kb/1024)>1200){
            $strong="<strong class='text-warning'>";
            $strongoff="</strong>";
        }
        if( ($mem_kb/1024)>2500){
            $strong="<strong class='text-danger'>";
            $strongoff="</strong>";
        }
        $mem_cell=$mem_kb>0?FormatBytes($mem_kb):"-";

        // Uptime in seconds
        $uptime_cell=format_uptime(intval($svc->uptime??0));

        // Actions: monitor=1 (monitored) → stop; otherwise → start
        $svc_enc=urlencode($name);
        $start_js="Loadjs('$page?monit-action=start&id=$id&svc=$svc_enc')";
        $stop_js="Loadjs('$page?monit-action=stop&id=$id&svc=$svc_enc')";
        $actions=$monitor===1?$tpl->icon_stop($stop_js,"AsArticaMetaAdmin"):$tpl->icon_run($start_js,"AsArticaMetaAdmin");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $wd1>$badge</td>";
        $html[]="<td>$strong$name_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$pid_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$mem_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$uptime_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$actions$strongoff</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $search=trim($_GET["search"]??"");
    $page=CurrentPageName();
    $function=$_GET["function"]??"";
    $activeRequested=$_GET["active"];

    // GET /netagents/services/{id} → ServicesResponse (no root "success" field)
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/services/$id"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?htmlspecialchars($json->Error??"{unknown_error}"):"{failed_to_contact_agent}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return false;
    }
    if(!isset($json->services)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{unknown_error}"));
        return false;
    }

    $services=$json->services;
    $total=intval($json->total_count??0);
    $running=intval($json->running_count??0);

    // Client-side filter: match name or description
    if(strlen($search)>=2){
        $services=array_values(array_filter($services,function($svc) use($search){
            return stripos($svc->name??"",$search)!==false
                || stripos($svc->description??"",$search)!==false;
        }));
    }

    $html=[];
    $lr=$json->last_refresh??"";
    if($lr && $lr!=="0001-01-01T00:00:00Z"){
        try{
            $dt=new DateTime($lr);
            $ts=htmlspecialchars($dt->format("H:i:s"));
        }catch(Exception $e){}
    }
    $html[]="</div>";

    if(empty($services)){
        $html[]=$tpl->div_info("{no_results}");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $html[]="<table class='table table-striped table-hover'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>{status}</th>";
    $html[]="<th>{services} ( {last_status}: $ts)</th>";
    $html[]="<th nowrap>{pid}</th>";
    $html[]="<th>{memory}</th>";
    $html[]="<th nowrap>{action}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";
    $wd1="style='width:1%' nowrap";
    $TRCLASS=null;



    foreach($services as $svc){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        // Status badge: failed → red, running → green, stopped/exited → gray, other → amber
        $active=($svc->active??"");
        if($active<>$activeRequested) {
            continue;
        }
        $enabled=($svc->enabled??"");

        //var_dump($svc);
        list($badge,$running)=label_Status($svc);

        // Name (monospace) + description below in gray
        $name=htmlspecialchars($svc->name??"");
        $desc=htmlspecialchars($svc->description??"");
        $name_cell="<strong style='font-family:monospace'>$name</strong>";
        if($desc) $name_cell.="<br><small style='color:#888'>$desc</small>";

        // PID: show dash when not running
        $pid=intval($svc->main_pid??0);
        $pid_cell=$pid>0?$pid:"-";

        // Memory: FormatBytes() takes kilobytes
        $mem_bytes=intval($svc->memory_bytes??0);
        $mem_cell=$mem_bytes>0?FormatBytes(intval($mem_bytes/1024)):"-";

        // CPU percentage
        $cpu=floatval($svc->cpu_percent??0);
        $cpu_cell=$cpu>0?number_format($cpu,1)."%":"-";

        // Actions: running → restart + stop; stopped → start
        $svc_enc=urlencode($svc->name??"");
        $restart_js="Loadjs('$page?action=restart&id=$id&svc=$svc_enc&function=$function')";
        $start_js="Loadjs('$page?action=start&id=$id&svc=$svc_enc&function=$function')";
        $stop_js="Loadjs('$page?action=stop&id=$id&svc=$svc_enc&function=$function')";
        if($running){
            $actions=$tpl->icon_restart($restart_js,"AsArticaMetaAdmin");
            $actions.="&nbsp;".$tpl->icon_stop($stop_js,"AsArticaMetaAdmin");
        }else{
            $actions=$tpl->icon_start($start_js,"AsArticaMetaAdmin");
        }

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $wd1>$badge</td>";
        $html[]="<td>$name_cell</td>";
        $html[]="<td $wd1>$pid_cell</td>";
        $html[]="<td $wd1>$mem_cell</td>";
        $html[]="<td $wd1>$actions</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function format_uptime(int $seconds):string{
    if($seconds<=0) return "-";
    if($seconds<60) return "{$seconds}s";
    if($seconds<3600) return intval($seconds/60)."m";
    $h=intval($seconds/3600);
    $m=intval(($seconds%3600)/60);
    return $m>0?"{$h}h {$m}m":"{$h}h";
}

function label_Status($svc):array{
    $enabled=($svc->enabled??"");
    $active=($svc->active??"");
    $status=($svc->status??"unknown");

    if($status=="running"){
        return array("<span class='label label-primary'>{running}</span>",true);
    }

    if($status=="exited"){
        return array("<span class='label label-default'>{item_executed}</span>",true);
    }

    if($enabled=="disabled"){
        return array("<span class='label label-default'>{disabled}</span>",false);
    }
    if($enabled=="static"){
        return array("<span class='label label-default'>{scheduled}</span>",false);
    }

    if($active=="failed"){
        return array("<span class='label label-danger'>{error}</span>",false);
    }



    if($status=="stopped" ||$status=="exited"){
        return array("<span class='label label-danger'>{stopped}</span>",false);
    }

    return array("<span class='label label-default'>$status ???</span>",false);
}