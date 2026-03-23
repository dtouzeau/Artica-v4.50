<?php
// Network Agents Management — rename DB label/description and change physical hostname.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["save-rename"])){save();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    return $tpl->js_dialog11("{hostname}","$page?popup=$id",620);
}

function popup():bool{
    $id=intval($_GET["popup"]);
    $tpl=new template_admin();
    $page=CurrentPageName();

    // DB record: label + description
    $agent       = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    $label       = is_object($agent) ? htmlspecialchars($agent->hostname    ?? '') : '';
    $description = is_object($agent) ? htmlspecialchars($agent->description ?? '') : '';

    // Live status: physical OS hostname
    $status   = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    $physHost = (is_object($status) && !empty($status->hostname))
              ? htmlspecialchars($status->hostname)
              : '';

    $h   = [];
    $h[] = "<div id='rename-result-$id' style='margin-bottom:8px'></div>";


    // ── Physical hostname ────────────────────────────────────────────────────
    $h[] = "<div class='form-group' style='margin-top:10px'>";
    $h[] = "  <label><strong>{hostname}</strong></label>";
    $h[] = "  <input type='text' class='form-control input-sm' id='rename-phys-$id' value='$physHost'>";
    $h[] = "  <small class='text-muted'>{physical_hostname_text}<br>{hostname_explain}</small>";
    $h[] = "</div>";


    // ── DB label ────────────────────────────────────────────────────────────
    $h[] = "<div class='form-group'>";
    $h[] = "  <label><strong>{computer_name}</strong></label>";
    $h[] = "  <input type='text' class='form-control input-sm' id='rename-label-$id' value='$label'>";
    $h[] = "  <small class='text-muted'>{computer_name_db_explain}</small>";
    $h[] = "</div>";

    // ── Description ─────────────────────────────────────────────────────────
    $h[] = "<div class='form-group' style='margin-top:10px'>";
    $h[] = "  <label><strong>{description}</strong></label>";
    $h[] = "  <input type='text' class='form-control input-sm' id='rename-desc-$id' value='$description'>";
    $h[] = "</div>";



    $h[] = "<div style='margin-top:15px;text-align:right'>";
    $h[] = "  <button class='btn btn-default btn-sm' onclick='dialogInstance11.close()'>{cancel}</button>&nbsp;";
    $h[] = "  <button class='btn btn-primary btn-sm' id='rename-btn-$id'>";
    $h[] = "    <i class='fas fa-save'></i> {save}";
    $h[] = "  </button>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "$('#rename-btn-$id').on('click',function(){";
    $h[] = "  var lbl=$.trim($('#rename-label-$id').val());";
    $h[] = "  if(!lbl){alert('{hostname}');return;}";
    $h[] = "  var btn=$(this);";
    $h[] = "  btn.prop('disabled',true).find('i').attr('class','fas fa-spinner fa-spin');";
    $h[] = "  $.post('$page',{";
    $h[] = "    'save-rename':1,'id':$id,";
    $h[] = "    'label':lbl,";
    $h[] = "    'description':$('#rename-desc-$id').val(),";
    $h[] = "    'physical_hostname':$('#rename-phys-$id').val()";
    $h[] = "  },function(r){";
    $h[] = "    $('#rename-result-$id').html(r);";
    $h[] = "    btn.prop('disabled',false).find('i').attr('class','fas fa-save');";
    $h[] = "  });";
    $h[] = "});";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body($h);
    return true;
}

function save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $id           = intval($_POST["id"]                ?? 0);
    $label        = trim($_POST["label"]               ?? '');
    $description  = trim($_POST["description"]         ?? '');
    $physHostname = trim($_POST["physical_hostname"]   ?? '');

    if($id === 0 || $label === ''){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return false;
    }

    $errors = [];

    // 1. Update DB label + description
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/rename/$id",
        ["hostname" => $label, "description" => $description]
    ));
    if(!is_object($json)){
        $errors[] = "DB rename: no response";
    } elseif(isset($json->Status) && !$json->Status){
        $errors[] = "DB rename: " . htmlspecialchars($json->Error ?? "error");
    }

    // 2. Push physical hostname to the remote agent (only if provided)
    if($physHostname !== ''){
        $json2 = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
            "/netagents/system/$id/network",
            ["hostname" => $physHostname]
        ));
        if(!is_object($json2)){
            $errors[] = "Physical hostname: no response from agent";
        } elseif(isset($json2->success) && !$json2->success){
            $errors[] = "Physical hostname: " . htmlspecialchars($json2->error ?? "error");
        } elseif(isset($json2->Status) && !$json2->Status){
            $errors[] = "Physical hostname: " . htmlspecialchars($json2->Error ?? "error");
        }
    }

    if(!empty($errors)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(implode("<br>", $errors)));
        return false;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {saved}"));
    return admin_tracks("Artica Meta: renamed agent $id label to \"$label\""
        . ($physHostname ? ", physical hostname to \"$physHostname\"" : ""));
}
