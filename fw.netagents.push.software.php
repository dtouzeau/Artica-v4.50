<?php
/**
 * Artica Meta console
 * Tools to push any file from the repository by giving the type and the file path
 */
$GLOBALS["ALLOWED_TYPES"]=array("official"=>true, "servicepack"=>true, "hotfix"=>true, "software"=>true);
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';

if(isset($_GET["byagent"])){byagent();exit;}
if(isset($_GET["byagent-search"])){byagent_search();exit;}
if(isset($_POST["push-to-agents"])){push_to_agents();exit;}
if(isset($_GET["bygroups"])){bygroups();exit;}
if(isset($_GET["bygroups-search"])){bygroups_search();exit;}
if(isset($_POST["push-to-group"])){push_to_group();exit;}
if(isset($_GET["tasks"])){tasks_list();exit;}
if(isset($_GET["tasks-search"])){tasks_search();exit;}
if(isset($_GET["task-details"])){task_details();exit;}
if(isset($_GET["task-details-popup"])){task_details_popup();exit;}
if(isset($_GET["task-delete"])){task_delete();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
js();

function js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $type=$_GET["type"];
    $path=isset($_GET["path"]) ? $_GET["path"] : (isset($_GET["f"]) ? $_GET["f"] : "");
    $pathEnc=urlencode($path);
    if(!isset($GLOBALS["ALLOWED_TYPES"][$type])){
        return $tpl->js_error("{type} $type {invalid}");
    }
    $fname=basename($path);
    return $tpl->js_dialog7("{update_software_packages} $fname - {{$type}}", "$page?tabs=yes&type=$type&path=$pathEnc", 800);
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=urlencode($_GET["path"]);
    $array["{agents}"]="$page?byagent=yes&type=$type&path=$path";
    $array["{groups}"]="$page?bygroups=yes&type=$type&path=$path";
    $array["{tasks}"]="$page?tasks=yes&type=$type&path=$path";
    echo $tpl->tabs_default($array);
    return true;
}

// ==================== By Agent Tab ====================

function byagent(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=urlencode($_GET["path"]);
    $t=time();
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,null,null,"agents-list-$t","&byagent-search=yes&type=$type&path=$path");
    $html[]="</div>";
    $html[]="<div id='agents-list-$t'></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function byagent_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=$_GET["path"];
    $pathEnc=urlencode($path);
    $search=isset($_GET["search"]) ? trim($_GET["search"]) : "";
    $t=time();
    $ServicePackNum=0;
    $sock=new sockets();
    $agentsJson=json_decode($sock->REST_API("/netagents/alist?status=online&enabled=1"));

    if($type=="servicepack"){
        $basename=basename($path);
        if(preg_match("#ArticaP([0-9]+)\.tgz#",$basename,$matches)){
            $ServicePackNum=intval($matches[1]);
        }
    }

    $html=array();
    $html[]="<div style='margin-bottom:10px'>";
    $html[]="<a href='javascript:void(0)' onclick=\"$('.agent-checkbox').prop('checked',true);\" style='margin-right:15px'><i class='fas fa-check-square'></i> {select_all}</a>";
    $html[]="<a href='javascript:void(0)' onclick=\"$('.agent-checkbox').prop('checked',false);\"><i class='far fa-square'></i> {deselect_all}</a>";
    $html[]="</div>";

    $html[]="<table id='table-agents-$t' class='table table-stripped' data-page-size='100' data-paging='true'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true data-type='text'>{version}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $count=0;
    $TRCLASS=null;
    if(isset($agentsJson->agents) && is_array($agentsJson->agents)){
        foreach($agentsJson->agents as $agent){
            if($search!=""){
                $searchLower=strtolower($search);
                if(strpos(strtolower($agent->hostname),$searchLower)===false
                    && strpos(strtolower($agent->ip_address),$searchLower)===false){
                    continue;
                }
            }
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $id=$agent->id;
            $hostname=$agent->hostname;
            $ip=$agent->ip_address;
            $version=isset($agent->artica_version) ? $agent->artica_version : "-";
            $hotfix=isset($agent->artica_hotfix) ? $agent->artica_hotfix : "-";
            if(strlen($hotfix)>2){
                $hotfix=" {hotfix} $hotfix";
            }
            if($ServicePackNum >0){
                if(isset($agent->artica_service_pack)){
                    if($agent->artica_service_pack==$ServicePackNum){
                        continue;
                    }
                    if($agent->artica_service_pack>$ServicePackNum){
                        continue;
                    }
                }
            }


            $sp=isset($agent->artica_service_pack) && $agent->artica_service_pack > 0 ? " SP{$agent->artica_service_pack}" : "";
            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td style='width:1%'><input type='checkbox' class='agent-checkbox' value='$id'></td>";
            $html[]="<td style='width:1%' nowrap>$hostname - $ip</td>";
            $html[]="<td style='width:99%' nowrap>$version$sp$hotfix</td>";
            $html[]="</tr>";
            $count++;
        }
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='3'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    if($count==0){
        $html[]=$tpl->div_warning("{no_agents_online}");
    }

    $html[]="<div style='width:100%;padding-right:10px;text-align:right;margin-top:10px'>";
    $html[]="<button class='btn btn-primary btn-lg' onclick=\"DoPushToAgents$t();\" >";
    $html[]="<i class='fas fa-upload'></i> {push_update}";
    $html[]="</button>";
    $html[]="</div>";
    $html[]="<div id='push-agents-result-$t' style='margin-top:10px'></div>";

    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="function DoPushToAgents$t(){";
    $html[]="  var ids=[];";
    $html[]="  $('.agent-checkbox:checked').each(function(){ ids.push($(this).val()); });";
    $html[]="  if(ids.length==0){ alert('{select_at_least_one_agent}'); return; }";
    $html[]="  $.post('$page',{";
    $html[]="    'push-to-agents':1,";
    $html[]="    'type':'$type',";
    $html[]="    'path':'$pathEnc',";
    $html[]="    'agent_ids':ids.join(',')";
    $html[]="  },function(data){ $('#push-agents-result-$t').html(data); });";
    $html[]="}";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function push_to_agents(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $type=isset($_POST["type"]) ? trim($_POST["type"]) : "";
    $path=isset($_POST["path"]) ? trim($_POST["path"]) : "";
    $agentIds=isset($_POST["agent_ids"]) ? trim($_POST["agent_ids"]) : "";

    if(empty($path) || empty($agentIds)){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{missing_parameters}"));
        return;
    }

    $json=json_decode($sock->REST_API_POST("/articarepos/push",array(
        "agent_ids"=>$agentIds,
        "filename"=>basename($path),
        "type"=>$type,
        "sourcefile"=>$path
    )));

    if(isset($json->Status) && $json->Status){
        $msg=isset($json->message) ? $json->message : "{success}";
        echo $tpl->_ENGINE_parse_body($tpl->div_success($msg));
    }else{
        $err=isset($json->Error) ? $json->Error : "{error} ";
        echo $tpl->_ENGINE_parse_body($tpl->div_error("$path<hr>$err"));
    }
}

// ==================== By Groups Tab ====================

function bygroups(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=urlencode($_GET["path"]);
    $t=time();
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,null,null,"groups-list-$t","&bygroups-search=yes&type=$type&path=$path");
    $html[]="</div>";
    $html[]="<div id='groups-list-$t'></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function bygroups_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=$_GET["path"];
    $pathEnc=urlencode($path);
    $search=isset($_GET["search"]) ? trim($_GET["search"]) : "";
    $t=time();

    $sock=new sockets();
    $groupsJson=json_decode($sock->REST_API("/netagents/groups"));

    $html=array();
    $html[]="<div style='margin-bottom:10px'>";
    $html[]="<a href='javascript:void(0)' onclick=\"$('.group-checkbox').prop('checked',true);\" style='margin-right:15px'><i class='fas fa-check-square'></i> {select_all}</a>";
    $html[]="<a href='javascript:void(0)' onclick=\"$('.group-checkbox').prop('checked',false);\"><i class='far fa-square'></i> {deselect_all}</a>";
    $html[]="</div>";

    $html[]="<table id='table-groups-$t' class='table table-stripped' data-page-size='100' data-paging='true'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{group_name}</th>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true data-type='number'>{agents}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $count=0;
    $TRCLASS=null;
    if(is_array($groupsJson)){
        foreach($groupsJson as $group){
            if($search!=""){
                $searchLower=strtolower($search);
                if(strpos(strtolower($group->name),$searchLower)===false
                    && strpos(strtolower($group->description),$searchLower)===false){
                    continue;
                }
            }
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $id=$group->id;
            $name=$group->name;
            $desc=isset($group->description) ? $group->description : "";
            $agentCount=isset($group->agent_count) ? intval($group->agent_count) : 0;
            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td style='width:1%'><input type='checkbox' class='group-checkbox' value='$id'></td>";
            $html[]="<td>$name</td>";
            $html[]="<td>$desc</td>";
            $html[]="<td style='width:1%' nowrap><span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span></td>";
            $html[]="</tr>";
            $count++;
        }
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    if($count==0){
        $html[]=$tpl->div_warning("{no_groups_found}");
    }

    $html[]="<div style='width:100%;padding-right:10px;text-align:right;margin-top:10px'>";
    $html[]="<button class='btn btn-primary btn-lg' onclick=\"DoPushToGroups$t();\">";
    $html[]="<i class='fas fa-upload'></i> {push_update}";
    $html[]="</button>";
    $html[]="</div>";
    $html[]="<div id='push-groups-result-$t' style='margin-top:10px'></div>";

    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="function DoPushToGroups$t(){";
    $html[]="  var ids=[];";
    $html[]="  $('.group-checkbox:checked').each(function(){ ids.push($(this).val()); });";
    $html[]="  if(ids.length==0){ alert('{select_at_least_one_group}'); return; }";
    $html[]="  var done=0; var total=ids.length;";
    $html[]="  $('#push-groups-result-$t').html('<i class=\"fas fa-spinner fa-spin\"></i> {pushing}... 0/'+total);";
    $html[]="  for(var i=0;i<ids.length;i++){";
    $html[]="    (function(gid){";
    $html[]="      $.post('$page',{";
    $html[]="        'push-to-group':1,";
    $html[]="        'type':'$type',";
    $html[]="        'path':'$pathEnc',";
    $html[]="        'group_id':gid";
    $html[]="      },function(data){";
    $html[]="        done++;";
    $html[]="        if(done>=total){ $('#push-groups-result-$t').html(data); }";
    $html[]="        else{ $('#push-groups-result-$t').html('<i class=\"fas fa-spinner fa-spin\"></i> {pushing}... '+done+'/'+total); }";
    $html[]="      });";
    $html[]="    })(ids[i]);";
    $html[]="  }";
    $html[]="}";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function push_to_group(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $type=isset($_POST["type"]) ? trim($_POST["type"]) : "";
    $path=isset($_POST["path"]) ? trim($_POST["path"]) : "";
    $groupId=isset($_POST["group_id"]) ? intval($_POST["group_id"]) : 0;

    if(empty($path) || $groupId==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{missing_parameters}"));
        return;
    }


    $filename=basename($path);

    $json=json_decode($sock->REST_API_POST("/articarepos/push/group/$groupId",array(
        "filename"=>$filename,
        "type"=>$type,
        "sourcefile"=>$path
    )));

    if(isset($json->Status) && $json->Status){
        $msg=isset($json->message) ? $json->message : "{success}";
        echo $tpl->_ENGINE_parse_body($tpl->div_success($msg));
    }else{
        $err=isset($json->error) ? $json->error : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
    }
}

// ==================== Tasks Tab ====================

function tasks_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=urlencode($_GET["path"]);
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]="<div id='push-tasks-list'></div>";
    $html[]="<script>LoadAjax('push-tasks-list','$page?tasks-search=yes&type=$type&path=$path');</script>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function tasks_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $path=$_GET["path"];
    $pathEnc=urlencode($path);

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/articarepos/push/status"));

    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->div_warning(json_last_error_msg());
        return;
    }

    if(!isset($json->operations) || !is_array($json->operations) || count($json->operations)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_push_operations}"));
        return;
    }

    $hasRunning=false;
    $w="style='width:1%' nowrap";

    $html=array();
    $html[]="<table class='table table-striped table-hover'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{status}</th>";
    $html[]="<th>{file}</th>";
    $html[]="<th>{success}</th>";
    $html[]="<th>{failed2}</th>";
    $html[]="<th>{date}</th>";
    $html[]="<th></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach($json->operations as $op){
        $id=isset($op->id) ? $op->id : "";
        $status=isset($op->status) ? $op->status : "unknown";
        $filename=isset($op->filename) ? basename($op->filename) : "-";
        $successCount=isset($op->success_count) ? intval($op->success_count) : 0;
        $failCount=isset($op->fail_count) ? intval($op->fail_count) : 0;
        $startedAt=isset($op->started_at) ? $op->started_at : "";
        $completedAt=isset($op->completed_at) ? $op->completed_at : "";
        $groupName=isset($op->group_name) && !empty($op->group_name) ? " ({$op->group_name})" : "";

        if($status=="running" || $status=="pending"){ $hasRunning=true; }

        if(!empty($completedAt)){
            $dateStr=$tpl->time_to_date($tpl->GoToTimestamp($completedAt),true);
        }elseif(!empty($startedAt)){
            $dateStr=$tpl->time_to_date($tpl->GoToTimestamp($startedAt),true);
        }else{
            $dateStr="-";
        }

        $statusBadge=_get_status_badge($status);

        $buttons="<button class='btn btn-info btn-xs' onclick=\"Loadjs('$page?task-details=$id');\"><i class='fas fa-eye'></i></button>";
        if($status=="completed" || $status=="failed"){
            $buttons.=" <button class='btn btn-danger btn-xs' onclick=\"Loadjs('$page?task-delete=$id&type=$type&path=$pathEnc');\"><i class='fas fa-trash'></i></button>";
        }

        $html[]="<tr>";
        $html[]="<td $w>$statusBadge</td>";
        $html[]="<td style='width:99%'>$filename$groupName</td>";
        $html[]="<td $w class='text-success'><strong>$successCount</strong></td>";
        $html[]="<td $w class='text-danger'><strong>$failCount</strong></td>";
        $html[]="<td $w>$dateStr</td>";
        $html[]="<td $w>$buttons</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";

    if($hasRunning){
        $html[]="<script>setTimeout(function(){ LoadAjax('push-tasks-list','$page?tasks-search=yes&type=$type&path=$pathEnc'); },5000);</script>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function task_delete(){
    $tpl=new template_admin();
    $sock=new sockets();
    $id=$_GET["task-delete"];
    $type=isset($_GET["type"]) ? $_GET["type"] : "";
    $path=isset($_GET["path"]) ? $_GET["path"] : "";
    $pathEnc=urlencode($path);

    header("content-type: application/x-javascript");
    $json=json_decode($sock->REST_API("/articarepos/push/status/$id"));
    if(!isset($json->operation)){
        echo "alert('Operation not found');";
        return;
    }
    $op=$json->operation;
    $status=isset($op->status) ? $op->status : "";
    if($status!="completed" && $status!="failed"){
        echo "alert('Cannot delete a running operation');";
        return;
    }

    $sock->REST_API("/articarepos/push-software/delete/$id");
    $page=CurrentPageName();
    echo "LoadAjax('push-tasks-list','$page?tasks-search=yes&type=$type&path=$pathEnc');";
}

function task_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["task-details"];
    return $tpl->js_dialog11("{operation_details}","$page?task-details-popup=$id",800);
}

function task_details_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $id=$_GET["task-details-popup"];

    $json=json_decode($sock->REST_API("/articarepos/push/status/$id"));
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(!isset($json->operation)){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{operation_not_found}"));
        return false;
    }

    $op=$json->operation;
    $status=isset($op->status) ? $op->status : "unknown";
    $filename=isset($op->filename) ? $op->filename : "-";
    $total=isset($op->total_agents) ? intval($op->total_agents) : 0;
    $completed=isset($op->completed) ? intval($op->completed) : 0;
    $successCount=isset($op->success_count) ? intval($op->success_count) : 0;
    $failCount=isset($op->fail_count) ? intval($op->fail_count) : 0;
    $startedAt=isset($op->started_at) ? $op->started_at : "";
    $completedAt=isset($op->completed_at) ? $op->completed_at : "";
    $groupName=isset($op->group_name) && !empty($op->group_name) ? $op->group_name : "-";
    $groupId=isset($op->group_id) ? intval($op->group_id) : 0;

    if(!empty($startedAt)){
        $startedAt=$tpl->time_to_date($tpl->GoToTimestamp($startedAt),true);
    }
    if(!empty($completedAt)){
        $completedAt=$tpl->time_to_date($tpl->GoToTimestamp($completedAt),true);
    }else{
        $completedAt="-";
    }

    $statusBadge=_get_status_badge($status);
    $w="style='width:1%' nowrap";

    $f=array();
    $f[]="<div class='row'>";
    $f[]="<div class='col-lg-12'>";
    $f[]="<div class='panel panel-default'>";
    $f[]="<div class='panel-heading'><strong>{operation_summary}</strong></div>";
    $f[]="<div class='panel-body'>";
    $f[]="<table class='table table-condensed'>";
    $f[]="<tr><td $w><strong>{status}:</strong></td><td style='width:99%'>$statusBadge</td></tr>";
    $f[]="<tr><td $w><strong>{file}:</strong></td><td nowrap>$filename</td></tr>";
    if($groupId>0){
        $f[]="<tr><td $w><strong>{group}:</strong></td><td>$groupName (ID: $groupId)</td></tr>";
    }
    $f[]="<tr><td $w><strong>{agents}:</strong></td><td>$completed / $total</td></tr>";
    $f[]="<tr><td $w><strong>{success}:</strong></td><td class='text-success'><strong>$successCount</strong></td></tr>";
    $f[]="<tr><td $w><strong>{failed}:</strong></td><td class='text-danger'><strong>$failCount</strong></td></tr>";
    $f[]="<tr><td $w><strong>{started}:</strong></td><td>$startedAt</td></tr>";
    $f[]="<tr><td $w><strong>{completed_at}:</strong></td><td>$completedAt</td></tr>";
    $f[]="</table>";
    $f[]="</div></div></div></div>";

    if(isset($op->results) && is_array($op->results) && count($op->results)>0){
        $f[]="<div class='row'>";
        $f[]="<div class='col-lg-12'>";
        $f[]="<div class='panel panel-default'>";
        $f[]="<div class='panel-heading'><strong>{agent_results}</strong></div>";
        $f[]="<div class='panel-body' style='max-height:400px;overflow-y:auto;'>";
        $f[]="<table class='table table-striped table-condensed'>";
        $f[]="<thead><tr><th>ID</th><th>{hostname}</th><th>{result}</th><th>{message}</th></tr></thead>";
        $f[]="<tbody>";

        foreach($op->results as $result){
            $agentId=isset($result->agent_id) ? intval($result->agent_id) : 0;
            $hostname=isset($result->hostname) ? $result->hostname : "Agent #$agentId";
            $success=isset($result->success) && $result->success;
            $message=isset($result->message) ? $result->message : "-";

            $rowClass=$success ? "" : "danger";
            $resultIcon=$success ? "<i class='fas fa-check text-success'></i>" : "<i class='fas fa-times text-danger'></i>";

            $f[]="<tr class='$rowClass'>";
            $f[]="<td $w>$agentId</td>";
            $f[]="<td $w>$hostname</td>";
            $f[]="<td $w>$resultIcon</td>";
            $f[]="<td style='width:99%'>$message</td>";
            $f[]="</tr>";
        }

        $f[]="</tbody></table>";
        $f[]="</div></div></div></div>";
    }

    if($status=="running" || $status=="pending"){
        $f[]="<div id='operation-refresh-$id'></div>";
        $f[]="<script>setTimeout(function(){ LoadAjax('operation-refresh-$id','$page?task-details-popup=$id'); },2000);</script>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ==================== Helpers ====================

function _get_status_badge(string $status):string{
    switch($status){
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
