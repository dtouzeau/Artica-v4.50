<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors',1);
    ini_set('error_reporting',E_ALL);
}

$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit;}

// JS dispatchers (content-type: javascript)
if(isset($_GET["snap-js"])){snap_js();exit;}

// POST dispatchers
if(isset($_POST["build"])){snap_build();exit;}
if(isset($_POST["restore"])){snap_restore();exit;}
if(isset($_POST["delete"])){snap_delete();exit;}
if(isset($_POST["upload"])){snap_upload();exit;}
if(isset($_POST["move_post"])){snap_move();exit;}
if(isset($_POST["move_group_post"])){snap_move_group();exit;}

// GET dispatchers (HTML)
if(isset($_GET["snap-list"])){snap_list();exit;}
if(isset($_GET["download"])){snap_download();exit;}
if(isset($_GET["move-poll"])){snap_move_poll();exit;}
if(isset($_GET["move-group-poll"])){snap_move_group_poll();exit;}

// Default
snap_form();

// ── Helpers ──────────────────────────────────────────────────────────────

function aid():int{
    return intval($_GET["snap-js"] ?? $_GET["id"] ?? $_GET["snap-list"] ?? $_GET["download"] ?? 0);
}

function validate_filename(string $f):bool{
    if(strlen($f)<1) return false;
    if(preg_match('/[\/\\\]|\.\./',$f)) return false;
    return true;
}

function netagent_error($json):string{
    if(is_object($json)){return htmlspecialchars($json->Error ?? "{error}");}
    return "{error}";
}

// ── snap_js() ────────────────────────────────────────────────────────────

function snap_js():bool{
    $id=intval($_GET["snap-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog6("{snapshots}","$page?id=$id",900);

}

// ── snap_form() ──────────────────────────────────────────────────────────

function snap_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"] ?? 0);
    if($id<1){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return;}

    // Fetch agent status
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if(!is_object($status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $hostname=htmlspecialchars($status->hostname ?? "#$id");

    $h=array();

    // Toolbar
    $h[]="<div class='ibox'>";
    $h[]="<div class='ibox-title'><h5><i class='fas fa-camera'></i> {snapshots} — $hostname</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="  <button id='snap-build-btn-$id' class='btn btn-primary btn-sm' onclick=\"SnapshotBuild_$id()\">";
    $h[]="    <i class='fas fa-plus'></i> {create_a_snapshot}</button>";
    $h[]="  <button class='btn btn-info btn-sm' onclick=\"SnapshotUpload_$id()\" style='margin-left:5px'>";
    $h[]="    <i class='fas fa-upload'></i> {upload_snapshot}</button>";
    $h[]="  <div id='snap-build-result-$id' style='margin-top:8px'></div>";

    // Upload form (hidden)
    $h[]="  <div id='snap-upload-form-$id' style='display:none;margin-top:10px;padding:10px;border:1px solid #e7eaec;border-radius:4px'>";
    $h[]="    <form id='snap-upload-frm-$id' enctype='multipart/form-data'>";
    $h[]="      <input type='hidden' name='upload' value='$id'>";
    $h[]="      <div class='form-group'>";
    $h[]="        <label>{select_a_file}</label>";
    $h[]="        <input type='file' name='snapshot' accept='.tar.gz,.tgz,.gz' class='form-control'>";
    $h[]="      </div>";
    $h[]="      <button type='button' class='btn btn-success btn-sm' onclick=\"SnapshotUploadSubmit_$id()\">";
    $h[]="        <i class='fas fa-upload'></i> {upload}</button>";
    $h[]="    </form>";
    $h[]="    <div id='snap-upload-result-$id' style='margin-top:5px'></div>";
    $h[]="  </div>";

    $h[]="</div></div>";

    // Restore result area
    $h[]="<div id='snap-restore-result-$id'></div>";

    // Move result area
    $h[]="<div id='snap-move-result-$id'></div>";

    // Snapshot list area
    $h[]="<div id='snap-list-$id'></div>";
    $h[]="<script>LoadAjax('snap-list-$id','$page?snap-list=$id');</script>";

    // JS block
    $h[]=snap_js_block($id);

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── snap_list() ──────────────────────────────────────────────────────────

function snap_list(){
    $id=intval($_GET["snap-list"]);
    echo snap_list_inner($id);
}

function snap_list_inner(int $id):string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if(!is_object($status)){
        return $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
    }
    if(!isset($status->artica_snapshots)){
        return $tpl->_ENGINE_parse_body($tpl->div_info("{no_snapshots_available}"));
    }

    $snaps=$status->artica_snapshots;
    if(!is_array($snaps)||count($snaps)===0){
        return $tpl->_ENGINE_parse_body("<p class='text-muted'>{no_snapshots_available}</p>");
    }

    $t=time();
    $f=array();
    $f[]="<table id='table-$t' class='table table-stripped' data-page-size='50'>";
    $f[]="<thead><tr>";
    $f[]="  <th data-sortable='true' data-type='text'>{filename}</th>";
    $f[]="  <th data-sortable='true' data-type='text' style='text-align:right'>{filesize}</th>";
    $f[]="  <th data-sortable='true' data-type='text'>{modified}</th>";
    $f[]="  <th>{actions}</th>";
    $f[]="</tr></thead><tbody>";

    // Sort newest first by mod_time
    $sorted=array_values(array_filter((array)$snaps,'is_object'));
    usort($sorted,function($a,$b){
        $ta=strtotime($a->mod_time ?? '');
        $tb=strtotime($b->mod_time ?? '');
        return $tb-$ta;
    });

    $TRCLASS=null;
    foreach($sorted as $row){
        if(!is_object($row)) continue;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $fname=htmlspecialchars($row->filename ?? '');
        $sizeH=htmlspecialchars($row->size_human ?? '');
        $modTime='';
        if(!empty($row->mod_time)){
            try{$dt=new DateTime($row->mod_time);$modTime=$dt->format('Y-m-d H:i');}
            catch(\Exception $e){$modTime=htmlspecialchars($row->mod_time);}
        }

        $encFname=urlencode($row->filename ?? '');
        $safeFname=htmlspecialchars(json_encode($row->filename ?? ''),ENT_QUOTES);

        // Buttons
        $dlBtn="<a href='$page?download=$id&filename=$encFname' target='_blank' class='btn btn-xs btn-primary' title='{download}'>"
             ."<i class='fas fa-download'></i></a>";
        $restBtn="<button class='btn btn-xs btn-warning' onclick=\"SnapshotRestore_$id($safeFname)\" title='{restore}'>"
                ."<i class='fas fa-undo'></i></button>";
        $moveBtn="<button class='btn btn-xs btn-info' onclick=\"SnapshotMovePicker_$id($safeFname)\" title='{move_to_agent}'>"
                ."<i class='fas fa-share'></i></button>";
        $delBtn="<button class='btn btn-xs btn-danger' onclick=\"SnapshotDelete_$id($safeFname)\" title='{confirm_delete_snapshot}'>"
               ."<i class='fas fa-trash'></i></button>";

        $f[]="<tr class='$TRCLASS'>";
        $f[]="  <td><span style='font-family:monospace'>$fname</span></td>";
        $f[]="  <td style='text-align:right'>$sizeH</td>";
        $f[]="  <td>$modTime</td>";
        $f[]="  <td>$dlBtn $restBtn $moveBtn $delBtn</td>";
        $f[]="</tr>";
    }

    $f[]="</tbody>";
    $f[]="<tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $f[]="</table>";
    $f[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $f[]="$(document).ready(function(){";
    $f[]="</script>";

    return $tpl->_ENGINE_parse_body(implode("\n",$f));
}

// ── snap_download() ──────────────────────────────────────────────────────

function snap_download(){
    $id=intval($_GET["download"]);
    $filename=basename($_GET["filename"] ?? "");
    if(!validate_filename($filename)){
        http_response_code(400);
        echo "Invalid filename";
        exit();
    }

    // Direct cURL — REST_API() rejects non-JSON responses, so we bypass it for binary downloads.
    $sock="/usr/share/artica-postfix/bin/run/articarest.sock";
    $uri="http://localhost/netagents/snapshots/$id/download/".rawurlencode($filename);
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $sock);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $data=curl_exec($ch);
    $httpCode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr=curl_error($ch);
    curl_close($ch);

    if($data===false||strlen($curlErr)>0){
        http_response_code(502);
        echo "Download failed: ".htmlspecialchars($curlErr);
        exit();
    }

    if($httpCode!==200){
        http_response_code($httpCode);
        // Go returns JSON error on failure
        $check=@json_decode($data);
        if(is_object($check)&&isset($check->Error)){
            echo htmlspecialchars($check->Error);
        }else{
            echo "Download failed (HTTP $httpCode)";
        }
        exit();
    }

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: ".strlen($data));
    echo $data;
    exit();
}

// ── snap_build() ─────────────────────────────────────────────────────────

function snap_build(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_POST["build"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/snapshots/$id/build"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    // Flush status cache so subsequent polls see the new snapshot sooner
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");

    $h=array();
    $h[]="<div id='snap-build-msg-$id' class='alert alert-info'><i class='fas fa-spinner fa-spin'></i> {snapshot_build_started}</div>";

    // Poll: refresh the list every 10s, detect new snapshot by mod_time change
    $h[]="<script>";
    $h[]="(function(){";
    $h[]="  var attempts=0,maxAttempts=12;";
    $h[]="  var startedAt=new Date().toISOString();";
    $h[]="  var iv=setInterval(function(){";
    $h[]="    attempts++;";
    $h[]="    $.get('$page?snap-list=$id',function(r){";
    $h[]="      var prev=$('#snap-list-$id').html();";
    $h[]="      $('#snap-list-$id').html(r);";
    $h[]="      if(r!==prev||attempts>=maxAttempts){";
    $h[]="        clearInterval(iv);";
    $h[]="        $('#snap-build-msg-$id').remove();";
    $h[]="      }";
    $h[]="    });";
    $h[]="  },10000);";
    $h[]="})();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── snap_restore() ───────────────────────────────────────────────────────

function snap_restore(){
    $tpl=new template_admin();
    $id=intval($_POST["restore"]);
    $filename=basename($_POST["filename"] ?? "");
    if(!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/snapshots/$id/restore/".rawurlencode($filename)
    ));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    $h=array();
    $h[]="<div id='snap-restore-msg-$id' class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> {snapshot_restore_started}</div>";
    $h[]="<script>setTimeout(function(){\$('#snap-restore-msg-$id').fadeOut(400,function(){\$(this).remove();})},5000);</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── snap_upload() ────────────────────────────────────────────────────────

function snap_upload(){
    $tpl=new template_admin();
    $id=intval($_POST["upload"]);
    if(empty($_FILES["snapshot"]["tmp_name"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{select_a_file}"));
        return;
    }
    $filename=basename($_FILES["snapshot"]["name"] ?? "");
    if(!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $binary=file_get_contents($_FILES["snapshot"]["tmp_name"]);
    if($binary===false||strlen($binary)===0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_BINARY(
            "/netagents/snapshots/$id/upload?filename=".urlencode($filename),
            $binary
        )
    );

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    // Flush cache
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");

    $h=array();
    $h[]="<div class='alert alert-success'>{snapshot_uploaded}</div>";
    $h[]=snap_list_inner($id);
    echo implode("\n",$h);
}

// ── snap_delete() ────────────────────────────────────────────────────────

function snap_delete(){
    $tpl=new template_admin();
    $id=intval($_POST["delete"]);
    $filename=basename($_POST["filename"] ?? "");
    if(!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
            "/netagents/snapshots/$id/".rawurlencode($filename)
        )
    );
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    // Flush cache
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");

    echo snap_list_inner($id);
}

// ── snap_move() ──────────────────────────────────────────────────────────

function snap_move(){
    $tpl=new template_admin();
    $source_id=intval($_POST["source_id"] ?? 0);
    $dest_id=intval($_POST["dest_id"] ?? 0);
    $filename=basename($_POST["filename"] ?? "");

    if($source_id<1||$dest_id<1||!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $payload=array(
        "filename"=>$filename,
        "source_id"=>$source_id,
        "dest_id"=>$dest_id,
    );

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
            "/netagents/snapshots/$source_id/move",
            $payload
        )
    );

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    $op_id=htmlspecialchars($json->op_id ?? "");
    if(strlen($op_id)<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $h=array();
    $h[]="<div id='snap-move-status-$source_id-$op_id'>";
    $h[]="  <p><i class='fa fa-spinner fa-spin'></i> {snapshot_move_started}</p>";
    $h[]="</div>";
    $h[]=snap_move_poll_js($source_id,$dest_id,$op_id);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function snap_move_poll(){
    $op_id=trim($_GET["move-poll"] ?? "");
    if(strlen($op_id)<1){
        http_response_code(400);
        echo json_encode(array("error"=>"missing op_id"));
        return;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/snapshots/move/status/".rawurlencode($op_id));
    if(!$resp||strlen($resp)<2){
        http_response_code(404);
        echo json_encode(array("error"=>"not found"));
        return;
    }
    header("Content-Type: application/json");
    echo $resp;
}

function snap_move_poll_js(int $source_id,int $dest_id,string $op_id):string{
    $js=array();
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  var div=document.getElementById('snap-move-status-$source_id-$op_id');";
    $js[]="  if(!div) return;";
    $js[]="  var labels={";
    $js[]="    pending:'<i class=\"fa fa-spinner fa-spin\"></i> Queued...',";
    $js[]="    downloading:'<i class=\"fa fa-spinner fa-spin\"></i> Downloading from source agent...',";
    $js[]="    uploading:'<i class=\"fa fa-spinner fa-spin\"></i> Uploading to destination agent...',";
    $js[]="    refreshing:'<i class=\"fa fa-spinner fa-spin\"></i> Refreshing destination...',";
    $js[]="    completed:'<span class=\"label label-primary\" style=\"color:#fff\"><i class=\"fas fa-check\"></i> {snapshot_moved}</span>',";
    $js[]="    failed:'<span class=\"label label-danger\" style=\"color:#fff\"><i class=\"fas fa-times\"></i> {snapshot_move_failed}</span>'";
    $js[]="  };";
    $js[]="  var iv=setInterval(function(){";
    $js[]="    $.get('fw.netagents.snapshots.php?move-poll=$op_id',function(r){";
    $js[]="      try{var d=JSON.parse(r);}catch(e){return;}";
    $js[]="      var s=d.status||'pending';";
    $js[]="      var html=labels[s]||labels.pending;";
    $js[]="      if(s==='failed'&&d.error) html+=' <span class=\"text-danger\">'+d.error+'</span>';";
    $js[]="      div.innerHTML='<p>'+html+'</p>';";
    $js[]="      if(d.done===true) clearInterval(iv);";
    $js[]="    }).fail(function(xhr){";
    $js[]="      if(xhr.status===404){";
    $js[]="        div.innerHTML='<p class=\"text-muted\">{snapshot_move_expired}</p>';";
    $js[]="        clearInterval(iv);";
    $js[]="      }";
    $js[]="    });";
    $js[]="  },3000);";
    $js[]="})();";
    $js[]="</script>";
    return implode("\n",$js);
}

// ── snap_move_group() ────────────────────────────────────────────────────

function snap_move_group(){
    $tpl=new template_admin();
    $source_id=intval($_POST["source_id"] ?? 0);
    $group_id=intval($_POST["group_id"] ?? 0);
    $filename=basename($_POST["filename"] ?? "");

    if($source_id<1||$group_id<1||!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $payload=array(
        "filename"=>$filename,
        "source_id"=>$source_id,
        "group_id"=>$group_id,
    );

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
            "/netagents/snapshots/$source_id/move-group",
            $payload
        )
    );

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    $op_id=htmlspecialchars($json->op_id ?? "");
    if(strlen($op_id)<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    // Build per-agent status table
    $h=array();
    $h[]="<div id='snap-mgrp-$source_id-$op_id' class='ibox' style='margin-top:10px'>";
    $h[]="<div class='ibox-title'><h5><i class='fas fa-share-alt'></i> {snapshot_group_move}</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<table class='table table-stripped'>";
    $h[]="<thead><tr><th>{hostname}</th><th>{status}</th></tr></thead>";
    $h[]="<tbody id='snap-mgrp-tbody-$op_id'>";

    if(isset($json->agents)&&is_array($json->agents)){
        foreach($json->agents as $ag){
            if(!is_object($ag)) continue;
            $agId=intval($ag->agent_id ?? 0);
            $agHost=htmlspecialchars($ag->hostname ?? "#$agId");
            $h[]="<tr id='snap-mgrp-row-$op_id-$agId'>";
            $h[]="  <td>$agHost</td>";
            $h[]="  <td id='snap-mgrp-st-$op_id-$agId'><i class='fa fa-clock'></i> {pending}</td>";
            $h[]="</tr>";
        }
    }

    $h[]="</tbody></table>";
    $h[]="<div id='snap-mgrp-overall-$op_id'><i class='fa fa-spinner fa-spin'></i> {snapshot_move_started}</div>";
    $h[]="</div></div>";

    // Polling JS
    $h[]=snap_move_group_poll_js($source_id,$op_id);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function snap_move_group_poll(){
    $op_id=trim($_GET["move-group-poll"] ?? "");
    if(strlen($op_id)<1){
        http_response_code(400);
        echo json_encode(array("error"=>"missing op_id"));
        return;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/snapshots/move-group/status/".rawurlencode($op_id)
    );
    if(!$resp||strlen($resp)<2){
        http_response_code(404);
        echo json_encode(array("error"=>"not found"));
        return;
    }
    header("Content-Type: application/json");
    echo $resp;
}

function snap_move_group_poll_js(int $source_id,string $op_id):string{
    $js=array();
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  var statusLabels={";
    $js[]="    pending:'<i class=\"fa fa-clock\"></i> {pending}',";
    $js[]="    uploading:'<i class=\"fa fa-spinner fa-spin\"></i> {uploading}',";
    $js[]="    completed:'<span class=\"label label-primary\" style=\"color:#fff\"><i class=\"fas fa-check\"></i> {completed}</span>',";
    $js[]="    failed:'<span class=\"label label-danger\" style=\"color:#fff\"><i class=\"fas fa-times\"></i> {failed}</span>'";
    $js[]="  };";
    $js[]="  var overallLabels={";
    $js[]="    downloading:'<i class=\"fa fa-spinner fa-spin\"></i> {downloading_from_source}',";
    $js[]="    uploading:'<i class=\"fa fa-spinner fa-spin\"></i> {uploading_to_agents}',";
    $js[]="    completed:'<span class=\"label label-primary\" style=\"color:#fff\"><i class=\"fas fa-check\"></i> {snapshot_group_move_completed}</span>',";
    $js[]="    failed:'<span class=\"label label-danger\" style=\"color:#fff\"><i class=\"fas fa-times\"></i> {snapshot_group_move_failed}</span>'";
    $js[]="  };";
    $js[]="  var iv=setInterval(function(){";
    $js[]="    $.get('fw.netagents.snapshots.php?move-group-poll=$op_id',function(r){";
    $js[]="      try{var d=JSON.parse(r);}catch(e){return;}";
    $js[]="      if(d.agents){";
    $js[]="        for(var i=0;i<d.agents.length;i++){";
    $js[]="          var a=d.agents[i];";
    $js[]="          var cell=document.getElementById('snap-mgrp-st-$op_id-'+a.agent_id);";
    $js[]="          if(!cell) continue;";
    $js[]="          var lbl=statusLabels[a.status]||statusLabels.pending;";
    $js[]="          if(a.status==='failed'&&a.error) lbl+=' <span class=\"text-danger\">'+a.error+'</span>';";
    $js[]="          cell.innerHTML=lbl;";
    $js[]="        }";
    $js[]="      }";
    $js[]="      var ov=document.getElementById('snap-mgrp-overall-$op_id');";
    $js[]="      if(ov){";
    $js[]="        var olbl=overallLabels[d.status]||overallLabels.downloading;";
    $js[]="        ov.innerHTML=olbl;";
    $js[]="      }";
    $js[]="      if(d.done===true) clearInterval(iv);";
    $js[]="    }).fail(function(xhr){";
    $js[]="      if(xhr.status===404){";
    $js[]="        var ov=document.getElementById('snap-mgrp-overall-$op_id');";
    $js[]="        if(ov) ov.innerHTML='<span class=\"text-muted\">{snapshot_move_expired}</span>';";
    $js[]="        clearInterval(iv);";
    $js[]="      }";
    $js[]="    });";
    $js[]="  },3000);";
    $js[]="})();";
    $js[]="</script>";
    return implode("\n",$js);
}

// ── snap_js_block() ──────────────────────────────────────────────────────

function snap_js_block(int $id):string{
    $tpl=new template_admin();
    $page="fw.netagents.snapshots.php";
    $js=array();
    $js[]="<script>";

    // Build
    $js[]="function SnapshotBuild_$id(){";
    $js[]="  $('#snap-build-btn-$id').prop('disabled',true).html('<i class=\"fas fa-spinner fa-spin\"></i> {building}');";
    $js[]="  $.post('$page',{build:$id},function(r){";
    $js[]="    $('#snap-build-result-$id').html(r);";
    $js[]="    $('#snap-build-btn-$id').prop('disabled',false).html('<i class=\"fas fa-plus\"></i> {create_a_snapshot}');";
    $js[]="  });";
    $js[]="}";

    // Restore (swal confirmation)
    $confirm_restore=$tpl->javascript_parse_text("{confirm_restore}");
    $js[]="function SnapshotRestore_$id(filename){";
    $js[]="  swal({title:'{restore}',text:'$confirm_restore',type:'warning',";
    $js[]="    showCancelButton:true,confirmButtonColor:'#f8ac59',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'";
    $js[]="  },function(isConfirm){";
    $js[]="    if(!isConfirm) return;";
    $js[]="    $.post('$page',{restore:$id,filename:filename},function(r){";
    $js[]="      $('#snap-restore-result-$id').html(r);";
    $js[]="    });";
    $js[]="  });";
    $js[]="}";

    // Delete (swal confirmation)
    $confirm_delete_snapshot=$tpl->javascript_parse_text("{confirm_delete_snapshot}");
    $js[]="function SnapshotDelete_$id(filename){";
    $js[]="  swal({title:'$confirm_delete_snapshot',text:filename,type:'warning',";
    $js[]="    showCancelButton:true,confirmButtonColor:'#ed5565',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'";
    $js[]="  },function(isConfirm){";
    $js[]="    if(!isConfirm) return;";
    $js[]="    $.post('$page',{'delete':$id,filename:filename},function(r){";
    $js[]="      $('#snap-list-$id').html(r);";
    $js[]="    });";
    $js[]="  });";
    $js[]="}";

    // Upload: toggle form
    $js[]="function SnapshotUpload_$id(){";
    $js[]="  $('#snap-upload-form-$id').toggle();";
    $js[]="}";

    // Upload: submit via FormData
    $select_a_file=$tpl->javascript_parse_text("{select_a_file}");
    $only_tgz=$tpl->javascript_parse_text("{only_tgz}");
    $js[]="function SnapshotUploadSubmit_$id(){";
    $js[]="  var form=document.getElementById('snap-upload-frm-$id');";
    $js[]="  if(!form.snapshot.files.length){alert('$select_a_file');return;}";
    $js[]="  var f=form.snapshot.files[0];";
    $js[]="  if(!/\.(tar\.gz|tgz|gz)$/i.test(f.name)){alert('Only .tar.gz / .tgz / .gz files');return;}";
    $js[]="  var fd=new FormData(form);";
    $js[]="  $('#snap-upload-result-$id').html('<i class=\"fa fa-spinner fa-spin\"></i>');";
    $js[]="  $.ajax({url:'$page',type:'POST',data:fd,";
    $js[]="    processData:false,contentType:false,";
    $js[]="    success:function(r){";
    $js[]="      $('#snap-list-$id').html(r);";
    $js[]="      $('#snap-upload-form-$id').hide();";
    $js[]="      $('#snap-upload-result-$id').html('');";
    $js[]="    },error:function(){\$('#snap-upload-result-$id').html('<span class=\"text-danger\">{error}</span>');}";
    $js[]="  });";
    $js[]="}";

    // Move picker opener
    $js[]="function SnapshotMovePicker_$id(filename){";
    $js[]="  Loadjs('fw.netagents.snapshots.move.php?move-js=$id&filename='+encodeURIComponent(filename));";
    $js[]="}";

    $js[]="</script>";
    return $tpl->_ENGINE_parse_body($js);
}
