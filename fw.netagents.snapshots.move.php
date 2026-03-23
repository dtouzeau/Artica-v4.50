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

if(isset($_GET["move-js"])){move_js();exit;}

move_form();

// ── move_js() ────────────────────────────────────────────────────────────

function move_js():bool{
    $tpl=new template_admin();
    $source_id=intval($_GET["move-js"]);
    $filename=urlencode(trim($_GET["filename"] ?? ""));
    $page=CurrentPageName();
    if($source_id<1){return $tpl->js_error("source_id==0 ???");}
    return $tpl->js_dialog7("{snapshot_move_title}","$page?id=$source_id&filename=$filename",700);

}

// ── move_form() ──────────────────────────────────────────────────────────

function move_form(){
    $tpl=new template_admin();
    $source_id=intval($_GET["id"] ?? 0);
    $filename=trim($_GET["filename"] ?? "");

    if($source_id<1||strlen($filename)<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    // Fetch agent list — response is {"agents":[...], "total":N}
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"));
    if(!is_object($resp)||!isset($resp->agents)||!is_array($resp->agents)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $agents=$resp->agents;


    // Fetch groups — response is []AgentGroup directly
    $groups=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
    if(!is_array($groups)) $groups=array();

    $fname_display=htmlspecialchars(basename($filename));

    $h=array();
    $h[]="<div style='padding:15px'>";
    $h[]="<p><strong>{snapshot_filename}:</strong> <span style='font-family:monospace'>$fname_display</span></p>";

    $h[]="<div class='form-group'>";
    $h[]="  <label>{select_destination_agent}</label>";
    $h[]="  <select id='snap-move-dest-$source_id' class='form-control'>";

    // Groups optgroup
    $groupCount=0;
    if(count($groups)>0){
        $h[]="    <optgroup label='{groups}'>";
        foreach($groups as $gr){
            if(!is_object($gr)) continue;
            $grId=intval($gr->id ?? 0);
            if($grId<1) continue;
            $grName=htmlspecialchars($gr->name ?? "#$grId");
            $grCount=intval($gr->agent_count ?? 0);
            $h[]="      <option value='group:$grId'>$grName ($grCount {agents})</option>";
            $groupCount++;
        }
        $h[]="    </optgroup>";
    }

    // Agents optgroup
    $agentCount=0;
    $h[]="    <optgroup label='{agents}'>";
    foreach($agents as $ag){
        if(!is_object($ag)) continue;
        $agId=intval($ag->id ?? 0);
        if($agId===$source_id) continue;
        $agHost=htmlspecialchars($ag->hostname ?? "#$agId");
        $agIp=htmlspecialchars($ag->ip_address ?? '');
        $enabled=intval($ag->enabled ?? 1);
        if(!$ag->artica){
            continue;
        }
        if($enabled<1) continue;
        $h[]="      <option value='$agId'>$agHost ($agIp)</option>";
        $agentCount++;
    }
    $h[]="    </optgroup>";

    $h[]="  </select>";
    $h[]="</div>";

    $found=$agentCount+$groupCount;
    if($found<1){
        $h=array();
        $h[]="<div style='padding:15px'>";
        $h[]=$tpl->div_warning("{no_agents}");
        $h[]="</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $h[]="<div class='alert alert-warning' style='margin-top:10px'>";
    $h[]="  <i class='fas fa-exclamation-triangle'></i> {snapshot_restore_warning}";
    $h[]="</div>";

    $fnameJs=htmlspecialchars(json_encode($filename),ENT_QUOTES);

    $h[]="<button class='btn btn-primary' onclick=\"SnapshotMoveSubmit_$source_id()\">";
    $h[]="  <i class='fas fa-share'></i> {move_to_agent}</button>";

    $h[]="<script>";
    $h[]="function SnapshotMoveSubmit_$source_id(){";
    $h[]="  var dest=$('#snap-move-dest-$source_id').val();";
    $h[]="  if(!dest){alert('{select_destination_agent}');return;}";
    $h[]="  if(dest.indexOf('group:')===0){";
    $h[]="    var gid=dest.replace('group:','');";
    $h[]="    $.post('fw.netagents.snapshots.php',{";
    $h[]="      move_group_post:1,";
    $h[]="      source_id:$source_id,";
    $h[]="      group_id:gid,";
    $h[]="      filename:$fnameJs";
    $h[]="    },function(r){";
    $h[]="      $('#snap-move-result-$source_id').html(r);";
    $h[]="      if(typeof dialogInstance7!=='undefined') dialogInstance7.close();";
    $h[]="    });";
    $h[]="  }else{";
    $h[]="    $.post('fw.netagents.snapshots.php',{";
    $h[]="      move_post:1,";
    $h[]="      source_id:$source_id,";
    $h[]="      dest_id:dest,";
    $h[]="      filename:$fnameJs";
    $h[]="    },function(r){";
    $h[]="      $('#snap-move-result-$source_id').html(r);";
    $h[]="      if(typeof dialogInstance7!=='undefined') dialogInstance7.close();";
    $h[]="    });";
    $h[]="  }";
    $h[]="}";
    $h[]="</script>";

    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
