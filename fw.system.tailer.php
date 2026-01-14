<?php
/**
 * Real-Time Log Tailer - WebConsole Interface
 *
 * This page provides a web interface for the Tailer module which uses logdy-core
 * to provide real-time log file tailing with a modern WebSocket-based UI.
 *
 * Features:
 * - Real-time log viewing via embedded logdy UI
 * - Add/remove log files dynamically
 * - Pre-configured log file groups (System, Proxy, Nginx, Artica)
 * - Start/stop tailing service
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

// Route handlers
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["viewer"])){viewer_tab();exit;}
if(isset($_GET["files-table"])){files_table();exit;}
if(isset($_GET["files-table-start"])){files_table_start();exit;}
if(isset($_GET["status"])){status_json();exit;}
if(isset($_GET["start"])){start_tailer();exit;}
if(isset($_GET["stop"])){stop_tailer();exit;}
if(isset($_POST["AddFile"])){add_file_perform();exit;}
if(isset($_GET["remove-file"])){remove_file_js();exit;}
if(isset($_POST["RemoveFile"])){remove_file();exit;}
if(isset($_GET["add-file-form-js"])){add_file_form_js();exit;}
if(isset($_GET["add-file-form-popup"])){add_file_form();exit;}
if(isset($_GET["status-widget"])){status_widget();exit;}
if(isset($_GET["refresh-status"])){refresh_status();exit;}
if(isset($_GET["log-files-js"])){log_files_js();exit;}

page();
// Default: show main page
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{log_viewer}",ico_eye,"{log_viewer_explain}",
        "$page?viewer=yes","log-viewer","progress-log-viewer-restart",false,
        "table-loader-log-viewer");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);
}

function page_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    return $tpl->js_dialog3("{real_time_log_tailer}", "$page?tabs=yes&function=$function", 1100, 750);
}
function log_files_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    return $tpl->js_dialog4("{log_files}", "$page?files-table-start=yes&function=$function", 990, 750);
}
function add_file_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    return $tpl->js_dialog5("{new_file}", "$page?add-file-form-popup=yes&function=$function", 650, 750);
}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    $tabs["{log_viewer}"]="$page?viewer=yes&function=$function";
    $tabs["{log_files}"]="$page?files-table=yes&function=$function";
    $html=array();
    $html[]=$tpl->tabs_default($tabs);
    echo $tpl->_ENGINE_parse_body($html);
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";

    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->div_explain("{real_time_log_tailer}||{tailer_explain}");
    $html[]="</div>";

    $tabs["{log_viewer}"]="$page?viewer=yes&function=$function";
    $tabs["{log_files}"]="$page?files-table=yes&function=$function";

    $html[]=$tpl->tabs_default($tabs);
    echo $tpl->_ENGINE_parse_body($html);
}

function viewer_tab(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    // Get tailer status
    $statusJson=$sock->REST_API("/api/tailer/status");
    $status=json_decode($statusJson, true);

    $isRunning=isset($status["running"]) && $status["running"] ? true : false;
    $filesCount=isset($status["files_count"]) ? intval($status["files_count"]) : 0;

    $html=array();

    // Status bar
    $html[]="<div style='margin-bottom:0;margin-top:-10px'>";
    $html[]="<div class='col-md-8'>";
    $html[]="<div id='tailer-status-widget'></div>";
    $html[]="</div>";
    $html[]="<div class='col-md-4 text-right'>";

    if($isRunning){
        $html[]="<button class='btn btn-default btn-sm' onclick=\"TailerClearCache();\" title='{clear_browser_cache}'><i class='fa fa-eraser'></i></button> ";
        $html[]="<button class='btn btn-default btn-sm' onclick=\"TailerFullscreen();\" id='fullscreen-btn'><i class='fa fa-expand'></i> {fullscreen}</button> ";
        $html[]="<button class='btn btn-danger btn-sm' onclick=\"TailerStop();\"><i class='fa fa-stop'></i> {stop}</button>";
    }else{
        if($filesCount > 0){
            $html[]="<button class='btn btn-success btn-sm' onclick=\"TailerStart();\"><i class='fa fa-play'></i> {start}</button>";
        }else{
            $html[]="<button class='btn btn-default btn-sm' disabled><i class='fa fa-exclamation-triangle'></i> {no_files_configured}</button>";
        }
    }
    $html[]="</div>";
    $html[]="</div>";

    // Logdy iframe viewer
    $html[]="<div class='row'>";
    $html[]="<div class='col-md-12'>";

    if($isRunning){
        // Use relative URL - nginx proxies to ArticaRest Unix socket
        $html[]="<iframe id='logdy-viewer'
            src='/tailer/'
            style='width:100%;height:850px;border:1px solid #ddd;border-radius:4px;background:#FFFFFF;'
            frameborder='0'></iframe>";

        // Help tip for first-time users
        $html[]="<div class='alert alert-info' style='margin-top:10px;'>";
        $html[]="<i class='fa fa-lightbulb-o'></i> <strong>{tip}:</strong> ";
        $html[]="{tailer_first_use_tip}";
        $html[]="</div>";
    }else{
        $html[]=$tpl->div_warning("{tailer_not_running}||{tailer_not_running_explain}");
    }

    $html[]="</div>";
    $html[]="</div>";

    $topbuttons[]=array("Loadjs('$page?log-files-js=yes')", ico_logsink,"{log_files}");
    $TINY_ARRAY["TITLE"]="{log_viewer}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{log_viewer_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Fullscreen CSS
    $html[]="<style>
    #logdy-viewer.fullscreen-mode {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 99999 !important;
        border: none !important;
        border-radius: 0 !important;
    }
    .fullscreen-exit-btn {
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 100000;
        padding: 8px 16px;
        background: #ed5565;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .fullscreen-exit-btn:hover {
        background: #ec4758;
    }
    </style>";

    // JavaScript
    $html[]="<script>
    var isFullscreen = false;

    function TailerStart(){
        Loadjs('$page?start=yes');
    }
    function TailerStop(){
        Loadjs('$page?stop=yes');
    }
    function TailerRefresh(){
        LoadAjax('tailer-status-widget','$page?status-widget=yes');
    }
    function TailerClearCache(){
        try {
            // Clear logdy storage in iframe
            var iframe = document.getElementById('logdy-viewer');
            if(iframe && iframe.contentWindow){
                iframe.contentWindow.localStorage.clear();
                iframe.contentWindow.sessionStorage.clear();
            }
        } catch(e) {}
        // Also clear parent localStorage for logdy keys
        for(var key in localStorage){
            if(key.indexOf('logdy') !== -1 || key.indexOf('tailer') !== -1){
                localStorage.removeItem(key);
            }
        }
        // Reload iframe
        var iframe = document.getElementById('logdy-viewer');
        if(iframe){
            iframe.src = iframe.src;
        }
    }
    function TailerFullscreen(){
        var iframe = document.getElementById('logdy-viewer');
        if(!iframe) return;

        if(!isFullscreen){
            // Enter fullscreen
            iframe.classList.add('fullscreen-mode');

            // Add exit button
            var exitBtn = document.createElement('button');
            exitBtn.id = 'fullscreen-exit-btn';
            exitBtn.className = 'fullscreen-exit-btn';
            exitBtn.innerHTML = '<i class=\"fa fa-compress\"></i> {exit_fullscreen}';
            exitBtn.onclick = TailerFullscreen;
            document.body.appendChild(exitBtn);

            // Update button
            document.getElementById('fullscreen-btn').innerHTML = '<i class=\"fa fa-compress\"></i> {exit_fullscreen}';
            isFullscreen = true;

            // ESC key to exit
            document.addEventListener('keydown', fullscreenEscHandler);
        } else {
            // Exit fullscreen
            iframe.classList.remove('fullscreen-mode');

            // Remove exit button
            var exitBtn = document.getElementById('fullscreen-exit-btn');
            if(exitBtn) exitBtn.remove();

            // Update button
            document.getElementById('fullscreen-btn').innerHTML = '<i class=\"fa fa-expand\"></i> {fullscreen}';
            isFullscreen = false;

            document.removeEventListener('keydown', fullscreenEscHandler);
        }
    }
    function fullscreenEscHandler(e){
        if(e.key === 'Escape' && isFullscreen){
            TailerFullscreen();
        }
    }
    LoadAjaxSilent('tailer-status-widget','$page?status-widget=yes');
    $jstiny
    </script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $html));
}

function status_widget(){
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    $statusJson=$sock->REST_API("/api/tailer/status");
    $status=json_decode($statusJson, true);

    $isRunning=isset($status["running"]) && $status["running"];
    $filesCount=isset($status["files_count"]) ? intval($status["files_count"]) : 0;

    if($isRunning){
        $statusBadge="<span class='label label-success'><i class='fa fa-check'></i> {running}</span>";
    }else{
        $statusBadge="<span class='label label-default'><i class='fa fa-stop'></i> {stopped}</span>";
    }

    $html="<div style='margin:0;padding:8px 12px;'>
        <strong>{status}:</strong> $statusBadge &nbsp;&nbsp;
        <strong>{files}:</strong> <span class='badge'>$filesCount</span>
    </div>";

    echo $tpl->_ENGINE_parse_body($html);
}
function files_table_start():bool{
    $page=CurrentPageName();
    echo "<div id='files-table-start'></div><script>LoadAjaxSilent('files-table-start','$page?files-table=yes');</script>";
    return true;
}
function files_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    // Get files list
    $filesJson=$sock->REST_API("/api/tailer/files");
    $files=json_decode($filesJson, true);
    if(!is_array($files)){$files=array();}

    // Get status
    $statusJson=$sock->REST_API("/api/tailer/status");
    $status=json_decode($statusJson, true);
    $isRunning=isset($status["running"]) && $status["running"];

    $html=array();

    // Header with add button
    $html[]="<div class='row' style='margin-bottom:15px'>";
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='margin:0'><i class='fa fa-file-text'></i> {configured_log_files}</h4>";
    $html[]="</div>";
    $html[]="<div class='col-md-6 text-right'>";
    $html[]="<button class='btn btn-primary btn-sm' onclick=\"Loadjs('$page?add-file-form-js=yes');\"><i class='fa fa-plus'></i> {new_file}</button>";
    $html[]="</div>";
    $html[]="</div>";

    if($isRunning){
        $html[]="<div class='alert alert-warning'><i class='fa fa-exclamation-triangle'></i> {tailer_running_warning}</div>";
    }

    // Files table
    $html[]="<div id='files-table-content'>";

    if(count($files)==0){
        $html[]="<div class='alert alert-info'>";
        $html[]="<i class='fa fa-info-circle'></i> {no_files_configured_explain}";
        $html[]="</div>";
    }else{
        $html[]="<table class='table table-striped table-hover'>";
        $html[]="<thead><tr>";
        $html[]="<th style='width:150px'>{alias}</th>";
        $html[]="<th style='width:100px'>{group}</th>";
        $html[]="<th>{path}</th>";
        $html[]="<th style='width:80px'>{actions}</th>";
        $html[]="</tr></thead>";
        $html[]="<tbody>";
        $s1="style='width:1%' nowrap";
        foreach($files as $file){
            $alias=isset($file["alias"]) ? htmlspecialchars($file["alias"]) : "";
            $group=isset($file["group"]) ? htmlspecialchars($file["group"]) : "";
            $path=isset($file["path"]) ? htmlspecialchars($file["path"]) : "";
            $pathB64=base64_encode($path);
            $md=md5($path);
            $groupBadge="";
            switch($group){
                case "System": $groupBadge="<span class='label label-info'>$group</span>"; break;
                case "Proxy": $groupBadge="<span class='label label-warning'>$group</span>"; break;
                case "Nginx": $groupBadge="<span class='label label-success'>$group</span>"; break;
                case "Artica": $groupBadge="<span class='label label-primary'>$group</span>"; break;
                case "DNS": $groupBadge="<span class='label label-default'>$group</span>"; break;
                case "Security": $groupBadge="<span class='label label-danger'>$group</span>"; break;
                case "Mail": $groupBadge="<span class='label label-info'>$group</span>"; break;
                default: $groupBadge="<span class='label label-default'>$group</span>"; break;
            }

            $deleteBtn=$tpl->icon_delete("Loadjs('$page?remove-file=$pathB64&md=$md');");

            $html[]="<tr id='$md'>";
            $html[]="<td $s1><strong>$alias</strong></td>";
            $html[]="<td $s1>$groupBadge</td>";
            $html[]="<td style='width:99%'><code style='font-size:11px'>$path</code></td>";
            $html[]="<td $s1>$deleteBtn</td>";
            $html[]="</tr>";
        }

        $html[]="</tbody></table>";
    }

    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $html));
}

function add_file_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $form=array();
    $form[]=$tpl->field_hidden("AddFile", 1);
    $form[]=$tpl->field_text("alias", "{alias}", "", true, "e.g., My App Log");

    $groups=array(
        "System"=>"{system2}",
        "Proxy"=>"{proxy}",
        "Nginx"=>"Nginx",
        "Artica"=>"Artica",
        "DNS"=>"DNS",
        "Security"=>"{security}",
        "Mail"=>"{mail}",
        "Custom"=>"{other}"
    );
    $form[]=$tpl->field_array_hash($groups, "group", "{group}", "Custom");
    $form[]=$tpl->field_text("path", "{file_path}", "", true, "/var/log/myapp.log");
    echo $tpl->form_outside("", $form, null, "{add}", "dialogInstance5.close();LoadAjaxSilent('files-table-start','$page?files-table=yes')",
        "AsSystemAdministrator");
    return true;
}

function add_file_perform():bool{
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $tpl->CLEAN_POST();

    $alias=isset($_POST["alias"]) ? trim($_POST["alias"]) : "";
    $group=isset($_POST["group"]) ? trim($_POST["group"]) : "Custom";
    $path=isset($_POST["path"]) ? trim($_POST["path"]) : "";

    if(empty($alias) || empty($path)){
        echo $tpl->post_error("{error}: {required_fields_missing}");
        return false;
    }

    $data=array(
        "alias"=>$alias,
        "group"=>$group,
        "path"=>$path
    );

    $result=$sock->REST_API_POST_JSON("/api/tailer/files/add", $data);
    $response=json_decode($result, true);

    if(isset($response["error"])){
        echo $tpl->post_error("{error}: ".$response["error"]);
        return false;
    }
    $sock->REST_API_POST("/api/tailer/stop", array());
    $sock->REST_API_POST("/api/tailer/start", array());

    return admin_tracks("Add a new log file to tail {$_POST["path"]} ");
}

function remove_file_js(){
    $tpl=new template_admin();
    $md=$_GET["md"];
    $pathB64=isset($_GET["remove-file"]) ? $_GET["remove-file"] : "";
    $path=base64_decode($pathB64);
    return $tpl->js_confirm_delete($path,"RemoveFile",$path,"$('#$md').remove();");
}

function remove_file():bool{
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    $path=isset($_POST["RemoveFile"]) ? trim($_POST["RemoveFile"]) : "";

    if(empty($path)){
        echo "{error}: {path_required}";
        return false;
    }

    $result=$sock->REST_API_POST("/api/tailer/files/remove", array("path"=>$path));
    $response=json_decode($result, true);

    if(isset($response["error"])){
        echo $tpl->_ENGINE_parse_body("{error}: ".$response["error"]);
        return false;
    }
    $sock->REST_API_POST("/api/tailer/stop", array());
    $sock->REST_API_POST("/api/tailer/start", array());
    return admin_tracks("Remove a file from tail {$_POST["RemoveFile"]}");
}

function start_tailer(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    $result=$sock->REST_API_POST("/api/tailer/start", array());
    $response=json_decode($result, true);
    header("content-type: application/x-javascript");
    if(isset($response["error"])){
        $tpl->js_error_stop("{error}: ".$response["error"]);
        return false;
    }

    // Refresh the viewer tab
    echo "LoadAjax('table-loader-log-viewer','$page?viewer=yes')";
    return true;
}

function stop_tailer():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    header("content-type: application/x-javascript");
    $result=$sock->REST_API_POST("/api/tailer/stop", array());
    $response=json_decode($result, true);

    if(isset($response["error"])){
        $tpl->js_error_stop("{error}: ".$response["error"]);
        return false;
    }

    // Refresh the viewer tab
    echo "LoadAjax('table-loader-log-viewer','$page?viewer=yes')";
    return true;
}

function status_json(){
    $sock=$GLOBALS["CLASS_SOCKETS"];
    header("Content-Type: application/json");
    echo $sock->REST_API("/api/tailer/status");
}

function refresh_status(){
    $page=CurrentPageName();
    echo "<script>LoadAjaxSilent('tailer-status-widget','$page?status-widget=yes');</script>";
}
