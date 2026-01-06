<?php
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';

if(isset($_GET["operation-details"])){operation_details();exit;}
if(isset($_GET["operation-details-popup"])){operation_details_popup();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["push-update"])){push();exit;}
if(isset($_GET["status-table"])){status_table();exit;}

function js():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $pkey=$_GET["js"];
    //js=$name&filename=$filenameEnc
    $filenameEnc=$_GET["filename"];
    $baseName=basename($filenameEnc);
    $filePath=urlencode($filenameEnc);
    if(!preg_match("#/softwares/debian([0-9]+)/(.+?)/(.+?)\.tar\.gz#",$filenameEnc,$re)){
        return $tpl->js_error("Invalid filename ".basename($filenameEnc));
    }
    $Deb=$re[1];
    $pkey=$re[2];
    $version=$re[3];
    return $tpl->js_dialog10("{push_update} Debian $Deb {{$pkey}} $version", "$page?tabs=$pkey&filename=$filePath", 700);
}
function tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $filename=$_GET["filename"];
    if(!preg_match("#/softwares/debian([0-9]+)/(.+?)/(.+?)\.tar\.gz#",$filename,$re)){
        echo $tpl->div_error("Invalid filename ".basename($filename));
        return false;
    }
    $filenameEnc=urlencode($_GET["filename"]);
    $tabs = array();
    $tabs["{agents}"] = "$page?popup=$filenameEnc";
    $tabs["{groups}"] = "$page?groups=$filenameEnc";
    echo $tpl->tabs_default($tabs)."</div>";
    return true;

}
function popup():bool{
    $filename=$_GET["popup"];
    $tpl = new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    if(!preg_match("#/softwares/debian([0-9]+)/(.+?)/(.+?)\.tar\.gz#",$filename,$re)){
       echo $tpl->div_error("Invalid filename ".basename($filename));
       return false;
    }
    $Deb=$re[1];
    $pkey=$re[2];
    $version=$re[3];
    $preselectedAgent = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    $agentsJson = json_decode($sock->REST_API("/netagents/alist?status=online&enabled=1"));
    $f = array();
    $f[] = "<div class='form-group' style='margin-top:10px'>";
    $f[] = "<label>{select_agents} &raquo;&raquo; {{$pkey}} v$version Debian $Deb</label>";
    $f[] = "<div style='max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;'>";

    if (isset($agentsJson->agents) && is_array($agentsJson->agents)) {
        foreach ($agentsJson->agents as $agent) {
            if ($agent->status != "online") continue;
            $checked = ($preselectedAgent == $agent->id) ? "checked" : "";
            $f[] = "<div class='checkbox'>";
            $f[] = "<label>";
            $f[] = "<input type='checkbox' class='agent-checkbox' value='$agent->id' $checked>";
            $f[] = " $agent->hostname ($agent->ip_address)";
            $f[] = "</label>";
            $f[] = "</div>";
        }
    }

    $f[] = "</div>";
    $f[] = "</div>";
    $f[] = "<div style='width:100%;padding-right:10px;text-align:right;'>";
    $f[] = "<button class='btn btn-primary btn-lg' onclick=\"DoPushUpdateSofts();\">";
    $f[] = "<i class='fas fa-upload'></i> {push_update}";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div id='push-error' style='margin-top:10px'></div>";
    $f[] = "<div id='push-result' style='margin-top:40px;border-top:1px solid #cccccc;padding-top:5px'></div>";
    // JavaScript
    $filenameVal = !empty($filename) ? $filename : "'+$('#push-filename').val()+'";
    $typeVal = !empty($type) ? $type : "'+$('#push-filename option:selected').data('type')+'";
    $js=$tpl->RefreshInterval_js("push-result",$page,"status-table=yes");
    $f[] = "<script>";
    $f[] = "function DoPushUpdateSofts() {";
    $f[] = "  var agentIds = [];";
    $f[] = "  $('.agent-checkbox:checked').each(function(){ agentIds.push($(this).val()); });";
    $f[] = "  if (agentIds.length == 0) { alert('Please select at least one agent'); return; }";
    $f[] = "  $.post('$page', {";
    $f[] = "    'push-update': 1,";
    $f[] = "    'filename': '$filenameVal',";
    $f[] = "    'type': '$typeVal',";
    $f[] = "    'agent_ids': agentIds.join(',')";
    $f[] = "  }, function(data) {";
    $f[] = "    $('#push-error').html(data);";
    $f[] = "  });";
    $f[] = "}";
    $f[]=$js;
    $f[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function push():bool{

    $tpl = new template_admin();
    $sock = new sockets();
    $filename = isset($_POST["filename"]) ? trim($_POST["filename"]) : "";
    if(!preg_match("#/softwares/debian([0-9]+)/(.+?)/(.+?)\.tar\.gz#",$filename,$re)){
        echo $tpl->div_error("Invalid filename ".basename($filename));
        return false;
    }

    $Deb=$re[1];
    $pkey=$re[2];

    $agentIds = isset($_POST["agent_ids"]) ? trim($_POST["agent_ids"]) : "";

    $ArticaLocalRepoPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLocalRepoPath");
    if(strlen($ArticaLocalRepoPath)<4){
        $ArticaLocalRepoPath="/home/artica/artica-repository";
    }

    if (empty($filename) || empty($agentIds)) {
        echo $tpl->div_warning("{missing_parameters}");
        return false;
    }

    $json = json_decode($sock->REST_API_POST("/articarepos/push-software", array(
        "filename" => $ArticaLocalRepoPath.$filename,
        "debian-version" => $Deb,
        "product"=>$pkey,
        "agent_ids" => $agentIds
    )));

    return true;
}

function status_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();

    $json=json_decode($sock->REST_API("/articarepos/push-software/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if (!isset($json->operations) || !is_array($json->operations) || count($json->operations) == 0) {
        return true;
    }

    $f=array();
    $f[]="<table class='table table-striped table-hover'>";
    $f[]="<thead>";
    $f[]="<tr>";
    $f[]="  <th>{status}</th>";
    $f[]="  <th>{file}</th>";
    $f[]="  <th>{success}</th>";
    $f[]="  <th>{failed2}</th>";
    $f[]="  <th>{date}</th>";
    $f[]="  <th></th>";
    $f[]="</tr>";
    $f[]="</thead>";
    $f[]="<tbody>";

    $w="style='width:1%' nowrap";
    foreach ($json->operations as $op) {
        $id = isset($op->id) ? $op->id : "";
        $status = isset($op->status) ? $op->status : "unknown";
        $filename = isset($op->filename) ? basename($op->filename) : "-";
        //var_dump($op);

        $successCount = isset($op->success_count) ? intval($op->success_count) : 0;
        $failCount = isset($op->fail_count) ? intval($op->fail_count) : 0;
        $startedAt = isset($op->started_at) ? $op->started_at : "";
        $completed_at = isset($op->completed_at) ? $op->completed_at : "";
        $groupName = isset($op->group_name) && !empty($op->group_name) ? " ({$op->group_name})" : "";

        // Format started time
        if (!empty($startedAt)) {
            $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($startedAt),true);
        }
        if (!empty($completed_at)) {
            $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($completed_at),true);
        }

        $statusBadge=get_status_badge($status);

        $f[]="<tr>";
        // $f[]="  <td><code>$id</code></td>";
        $f[]="  <td $w>$statusBadge</td>";
        $f[]="  <td style='width:99%'>$filename$groupName</td>";
        $f[]="  <td $w class='text-success'><strong>$successCount</strong></td>";
        $f[]="  <td $w class='text-danger'><strong>$failCount</strong></td>";
        $f[]="  <td $w>$startedAt</td>";
        $f[]="  <td $w><button class='btn btn-info btn-xs' OnClick=\"Loadjs('$page?operation-details=$id');\"><i class='fas fa-eye'></i> {details}</button></td>";
        $f[]="</tr>";
    }

    $f[]="</tbody>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function get_status_badge(string $status):string {
    switch ($status) {
        case "pending":
            return "<span class='label label-default'><i class='fas fa-clock'></i> {pending}</span>";
        case "running":
            return "<span class='label label-info'><i class='fas fa-spinner fa-spin'></i> {running}</span>";
        case "completed":
            return "<span class='label label-success'><i class='fas fa-check'></i> {completed}</span>";
        case "failed":
            return "<span class='label label-danger'><i class='fas fa-times'></i> {failed}</span>";
        default:
            return "<span class='label label-default'>$status</span>";
    }
}
function operation_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["operation-details"];
    return $tpl->js_dialog11("{operation_details}","$page?operation-details-popup=$id",800);
}
function operation_details_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $id=$_GET["operation-details-popup"];

    $json=json_decode($sock->REST_API("/articarepos/push-software/status/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if (!isset($json->operation)) {
        echo $tpl->div_warning("{operation_not_found}");
        return false;
    }

    $op = $json->operation;
    $status = isset($op->status) ? $op->status : "unknown";
    $filename = isset($op->filename) ? $op->filename : "-";
    $total = isset($op->total_agents) ? intval($op->total_agents) : 0;
    $completed = isset($op->completed) ? intval($op->completed) : 0;
    $successCount = isset($op->success_count) ? intval($op->success_count) : 0;
    $failCount = isset($op->fail_count) ? intval($op->fail_count) : 0;
    $startedAt = isset($op->started_at) ? $op->started_at : "";
    $completedAt = isset($op->completed_at) ? $op->completed_at : "";
    $groupName = isset($op->group_name) && !empty($op->group_name) ? $op->group_name : "-";
    $groupId = isset($op->group_id) ? intval($op->group_id) : 0;

    // Format times
    if (!empty($startedAt)) {
        $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($startedAt),true);
    }

    if (!empty($completedAt) ) {
        $completedAt = $tpl->time_to_date($tpl->GoToTimestamp($completedAt),true);
    } else {
        $completedAt = "-";
    }


    $statusBadge = get_status_badge($status);
    $w="style='width:1%' nowrap";

    $f=array();
    // Summary section
    $f[]="<div class='row'>";
    $f[]="  <div class='col-lg-12'>";
    $f[]="    <div class='panel panel-default'>";
    $f[]="      <div class='panel-heading'><strong>{operation_summary}</strong></div>";
    $f[]="      <div class='panel-body'>";
    $f[]="        <table class='table table-condensed'>";
    $f[]="          <tr><td $w><strong>ID:</strong></td><td style='width:99%'><code>$id</code></td></tr>";
    $f[]="          <tr><td $w><strong>{status}:</strong></td><td>$statusBadge</td></tr>";
    $f[]="          <tr><td $w><strong>{file}:</strong></td><td nowrap>$filename</td></tr>";
    if ($groupId > 0) {
        $f[]="          <tr><td $w><strong>{group}:</strong></td><td>$groupName (ID: $groupId)</td></tr>";
    }

    $f[]="          <tr><td $w><strong>{agents}:</strong></td><td>$completed / $total</td></tr>";
    $f[]="          <tr><td $w><strong>{success}:</strong></td><td class='text-success'><strong>$successCount</strong></td></tr>";
    $f[]="          <tr><td $w><strong>{failed}:</strong></td><td class='text-danger'><strong>$failCount</strong></td></tr>";
    $f[]="          <tr><td $w><strong>{started}:</strong></td><td>$startedAt</td></tr>";
    $f[]="          <tr><td $w><strong>{completed_at}:</strong></td><td>$completedAt</td></tr>";
    $f[]="        </table>";
    $f[]="      </div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";

    // Results section
    if (isset($op->results) && is_array($op->results) && count($op->results) > 0) {
        $f[]="<div class='row'>";
        $f[]="  <div class='col-lg-12'>";
        $f[]="    <div class='panel panel-default'>";
        $f[]="      <div class='panel-heading'><strong>{agent_results}</strong></div>";
        $f[]="      <div class='panel-body' style='max-height:400px; overflow-y:auto;'>";
        $f[]="        <table class='table table-striped table-condensed'>";
        $f[]="          <thead><tr><th>ID</th><th>{hostname}</th><th>{result}</th><th>{message}</th></tr></thead>";
        $f[]="          <tbody>";



        foreach ($op->results as $result) {
            $agentId = isset($result->agent_id) ? intval($result->agent_id) : 0;
            $hostname = isset($result->hostname) ? $result->hostname : "Agent #$agentId";
            $success = isset($result->success) && $result->success;
            $message = isset($result->message) ? $result->message : "-";

            $rowClass = $success ? "" : "danger";
            $resultIcon = $success ? "<i class='fas fa-check text-success'></i>" : "<i class='fas fa-times text-danger'></i>";

            $f[]="          <tr class='$rowClass'>";
            $f[]="            <td $w>$agentId</td>";
            $f[]="            <td $w>$hostname</td>";
            $f[]="            <td $w>$resultIcon</td>";
            $f[]="            <td style='width:99%'>$message</td>";
            $f[]="          </tr>";
        }

        $f[]="          </tbody>";
        $f[]="        </table>";
        $f[]="      </div>";
        $f[]="    </div>";
        $f[]="  </div>";
        $f[]="</div>";
    }

    // Auto-refresh if running
    if ($status == "running" || $status == "pending") {
        $f[]="<div id='operation-refresh-$id'></div>";
        $f[]="<script>setTimeout(function(){ LoadAjax('operation-refresh-$id','$page?operation-details-popup=$id'); }, 2000);</script>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}