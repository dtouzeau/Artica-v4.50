<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsSambaAdministrator){die();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["active-sessions"])){active_sessions();exit;}
if(isset($_GET["daemon-logs"])){daemon_logs();exit;}
if(isset($_GET["log-content"])){daemon_log_content();exit;}
if(isset($_GET["health"])){daemon_health();exit;}

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_SAMBA} &raquo;&raquo; {events}",
        ico_eye,
        "{samba_events_explain}",
        "$page?start=yes","samba-logs","progress-samba-logs",false,"table-samba-logs");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SAMBA} - {events}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $samba=new ArticaSamba();
    try{
        $instances=$samba->listInstances();
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    if(count($instances)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_fs_instances}"));
        return;
    }

    $options=[];
    $firstId="";
    foreach($instances as $inst){
        $id=$inst["id"] ?? "";
        $name=htmlspecialchars($inst["name"] ?? $id);
        $state=$inst["state"] ?? "stopped";
        $options[$id]="$name ($state)";
        if($firstId===""){$firstId=$id;}
    }

    $selectedId=isset($_GET["instance-id"])?$_GET["instance-id"]:$firstId;

    $html[]="<div style='margin-bottom:15px'>";
    $html[]="<label style='margin-right:10px'>{instance}:</label>";
    $html[]="<select id='samba-logs-instance-selector' class='form-control' style='width:300px;display:inline-block' OnChange=\"SambaLogsReload();\">";
    foreach($options as $oid=>$olabel){
        $sel=($oid==$selectedId)?"selected":"";
        $html[]="<option value='$oid' $sel>$olabel</option>";
    }
    $html[]="</select>";
    $html[]="</div>";

    $html[]="<div id='logs-tabs-area'></div>";
    $html[]="<script>";
    $html[]="function SambaLogsReload(){";
    $html[]="  var iid=document.getElementById('samba-logs-instance-selector').value;";
    $html[]="  LoadAjax('logs-tabs-area','$page?active-sessions=yes&instance-id='+iid);";
    $html[]="}";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

    $tabArray=[];
    $tabArray["{sessions}"]="$page?active-sessions=yes&instance-id=$selectedId";
    $tabArray["{daemon_logs}"]="$page?daemon-logs=yes&instance-id=$selectedId";
    $tabArray["{health}"]="$page?health=yes&instance-id=$selectedId";
    echo $tpl->tabs_default($tabArray);
}

// ── Active Sessions ──────────────────────────────────────────

function active_sessions(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=trim($_GET["instance-id"] ?? "");

    if(strlen($instanceId)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }

    $samba=new ArticaSamba();
    try{
        $sessions=$samba->getSessions($instanceId);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $tdStyle1="style='width:1%' nowrap";
    $t=time();
    $html[]="<table id='table-live-sessions-$t' class='footable table table-stripped' data-page-size='50'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{username}</th>";
    $html[]="<th data-sortable=true data-type='text'>{client_ip}</th>";
    $html[]="<th data-sortable=true data-type='text'>{share_name}</th>";
    $html[]="<th data-sortable=true data-type='number'>{read}</th>";
    $html[]="<th data-sortable=true data-type='number'>{write}</th>";
    $html[]="<th data-sortable=true data-type='text'>{session_start}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($sessions as $sess){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $user=htmlspecialchars($sess["user"] ?? "");
        $clientIp=htmlspecialchars($sess["client_ip"] ?? "");
        $share=htmlspecialchars($sess["share"] ?? "");
        $readBytes=samba_format_bytes($sess["read_bytes"] ?? 0);
        $writeBytes=samba_format_bytes($sess["write_bytes"] ?? 0);
        $sessionStart=htmlspecialchars($sess["session_start"] ?? "");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td><i class='".ico_member."'></i>&nbsp;$user</td>";
        $html[]="<td $tdStyle1>$clientIp</td>";
        $html[]="<td><i class='fas fa-folder'></i>&nbsp;$share</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>$readBytes</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>$writeBytes</td>";
        $html[]="<td $tdStyle1>$sessionStart</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-live-sessions-$t').footable({";
    $html[]="  \"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
    $html[]="});});</script>";

    // Auto-refresh every 5 seconds
    $html[]="<script>".$tpl->RefreshInterval_js("logs-tabs-area",$page,"active-sessions=yes&instance-id=$instanceId",5)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Daemon Logs ──────────────────────────────────────────────

function daemon_logs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=trim($_GET["instance-id"] ?? "");

    if(strlen($instanceId)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }

    $samba=new ArticaSamba();
    $logFiles=$samba->listLogFiles($instanceId);

    if(count($logFiles)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_log_files}"));
        return;
    }

    $selectedFile=isset($_GET["log-file"])?$_GET["log-file"]:$logFiles[0]["name"];

    $html[]="<div style='margin-bottom:15px'>";
    $html[]="<label style='margin-right:10px'>{log_file}:</label>";
    $html[]="<select id='samba-log-file-selector' class='form-control' style='width:400px;display:inline-block' OnChange=\"SambaLogFileReload();\">";
    foreach($logFiles as $lf){
        $fname=htmlspecialchars($lf["name"]);
        $fsize=FormatBytes($lf["size"]);
        $fdate=date("Y-m-d H:i",$lf["mtime"]);
        $sel=($lf["name"]==$selectedFile)?"selected":"";
        $html[]="<option value='".htmlspecialchars($lf["name"])."' $sel>$fname ($fsize - $fdate)</option>";
    }
    $html[]="</select>";
    $html[]="&nbsp;<button class='btn btn-default btn-sm' OnClick=\"SambaLogFileReload();\"><i class='fas fa-sync-alt'></i></button>";
    $html[]="</div>";

    $html[]="<div id='log-content-area'></div>";
    $html[]="<script>";
    $html[]="function SambaLogFileReload(){";
    $html[]="  var f=document.getElementById('samba-log-file-selector').value;";
    $html[]="  LoadAjax('log-content-area','$page?log-content=$instanceId&log-file='+encodeURIComponent(f));";
    $html[]="}";
    $html[]="SambaLogFileReload();";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function daemon_log_content(){
    $tpl=new template_admin();
    $instanceId=trim($_GET["log-content"]);
    $filename=trim($_GET["log-file"] ?? "");

    if(strlen($filename)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_log_file}"));
        return;
    }

    $samba=new ArticaSamba();
    $content=$samba->readLogFile($instanceId,$filename,500);

    if(strlen($content)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{empty_log_file}"));
        return;
    }

    $html[]="<div style='background:#2f2f2f;color:#e0e0e0;padding:15px;border-radius:4px;max-height:500px;overflow-y:auto;font-family:monospace;font-size:12px;white-space:pre-wrap;word-wrap:break-word'>";
    $html[]=htmlspecialchars($content);
    $html[]="</div>";
    $html[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Health ───────────────────────────────────────────────────

function daemon_health(){
    $tpl=new template_admin();
    $samba=new ArticaSamba();
    try{
        $health=$samba->health();
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $status=$health["status"] ?? "unknown";
    $version=$health["version"] ?? "";
    $uptime=$health["uptime"] ?? "";
    $instances=$health["instances"] ?? 0;

    $statusColor=($status==="healthy")?"#1ab394":"#ed5565";
    $statusLabel=($status==="healthy")?"{running}":"{error}";

    $tpl->table_form_field_text("{status}","<span class='label' style='background:$statusColor;color:#fff'>$statusLabel</span>","fas fa-heartbeat");
    if(strlen($version)>0){
        $tpl->table_form_field_text("{version}",$version,ico_update);
    }
    if(strlen($uptime)>0){
        $tpl->table_form_field_text("{uptime}",$uptime,"fas fa-clock");
    }
    $tpl->table_form_field_text("{instances}",strval($instances),"fas fa-layer-group");

    echo $tpl->table_form_compile();
}

// ── Helpers ──────────────────────────────────────────────────

function samba_format_bytes(int $bytes):string{
    if($bytes==0){return "0 B";}
    $units=["B","KB","MB","GB","TB"];
    $i=floor(log(abs($bytes),1024));
    if($i>=count($units)){$i=count($units)-1;}
    return number_format($bytes/pow(1024,$i),2)." ".$units[$i];
}
