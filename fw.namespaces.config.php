<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["config-view"])){config_view();exit;}
if(isset($_POST["config-validate"])){config_validate();exit;}
if(isset($_POST["config-apply"])){config_apply();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{configuration}","fas fa-file-code",
        "{namespaces_config_explain}","$page?config-view=yes",
        "namespaces-config","progress-namespaces-config",false,"config-content");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function config_view():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/config"));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: ".htmlspecialchars($err)));
        return false;
    }

    $config_text=json_encode($json->data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

    $f=array();
    $f[]="<div class='row'><div class='col-lg-12'><div class='ibox'>";
    $f[]="<div class='ibox-title'><h5>{configuration} (namespaces.json)</h5>";
    $f[]="<div class='ibox-tools'>";
    $f[]="  <button class='btn btn-sm btn-warning' OnClick=\"ns_config_validate();\"><i class='fas fa-check-circle'></i> {validate}</button>";
    $f[]="  <button class='btn btn-sm btn-primary' OnClick=\"ns_config_apply();\"><i class='fas fa-save'></i> {apply}</button>";
    $f[]="</div></div>";
    $f[]="<div class='ibox-content'>";
    $f[]="<textarea id='ns-config-editor' class='form-control' style='font-family:monospace;font-size:12px;min-height:400px;white-space:pre'>".htmlspecialchars($config_text)."</textarea>";
    $f[]="<div id='ns-config-result' style='margin-top:10px'></div>";
    $f[]="</div></div></div></div>";

    $f[]="<script>";
    $f[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $f[]="function ns_config_validate(){";
    $f[]="  var txt=document.getElementById('ns-config-editor').value;";
    $f[]="  try{ JSON.parse(txt); }catch(e){ document.getElementById('ns-config-result').innerHTML='<div class=\"alert alert-danger\">JSON syntax error: '+e.message+'</div>'; return; }";
    $f[]="  $.post('$page',{\"config-validate\":txt},function(r){ document.getElementById('ns-config-result').innerHTML=r; });";
    $f[]="};";
    $f[]="function ns_config_apply(){";
    $f[]="  var txt=document.getElementById('ns-config-editor').value;";
    $f[]="  try{ JSON.parse(txt); }catch(e){ document.getElementById('ns-config-result').innerHTML='<div class=\"alert alert-danger\">JSON syntax error: '+e.message+'</div>'; return; }";
    $f[]="  swal({title:'{confirm_action}',text:'{apply_config_warning}',type:'warning',showCancelButton:true,confirmButtonColor:'#1c84c6',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'},function(isConfirm){";
    $f[]="    if(!isConfirm) return;";
    $f[]="    $.post('$page',{\"config-apply\":txt},function(r){ document.getElementById('ns-config-result').innerHTML=r; });";
    $f[]="  });";
    $f[]="};";
    $f[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function config_validate():bool{
    $tpl=new template_admin();
    $payload=json_decode($_POST["config-validate"],true);
    if(!is_array($payload)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Invalid JSON"));
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/config/validate",$payload));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return false;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_success("{configuration} {valid}"));
    return true;
}

function config_apply():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $payload=json_decode($_POST["config-apply"],true);
    if(!is_array($payload)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Invalid JSON"));
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/config/apply",$payload));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return false;
    }

    admin_tracks("Apply namespaces config from editor");

    $result=$json->data;
    $f=array();
    if(isset($result->actions) && is_array($result->actions) && count($result->actions)>0){
        $f[]="<div class='alert alert-success'><strong>{success}</strong><ul>";
        foreach($result->actions as $action){
            $f[]="<li>".htmlspecialchars($action)."</li>";
        }
        $f[]="</ul></div>";
    }else{
        $f[]=$tpl->div_success("{success}: {no_changes}");
    }

    if(isset($result->errors) && is_array($result->errors) && count($result->errors)>0){
        $f[]="<div class='alert alert-danger'><strong>{errors}</strong><ul>";
        foreach($result->errors as $error){
            $f[]="<li>".htmlspecialchars($error)."</li>";
        }
        $f[]="</ul></div>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}
