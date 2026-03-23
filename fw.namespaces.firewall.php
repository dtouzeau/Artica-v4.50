<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["rules-table"])){rules_table();exit;}
if(isset($_GET["rule-add-js"])){rule_add_js();exit;}
if(isset($_GET["rule-add-popup"])){rule_add_popup();exit;}
if(isset($_POST["rule-add-save"])){rule_add_save();exit;}
if(isset($_GET["rule-edit-js"])){rule_edit_js();exit;}
if(isset($_GET["rule-edit-popup"])){rule_edit_popup();exit;}
if(isset($_POST["rule-edit-save"])){rule_edit_save();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_GET["rule-toggle"])){rule_toggle();exit;}
if(isset($_GET["popup"])){popup();exit;}

js();
function js():bool{
    $nsid=$_GET["nsid"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{firewall}: #$nsid","$page?popup=yes&nsid=$nsid",950);

}

// ─── Page Shell ──────────────────────────────

function popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $nsid=intval($_GET["nsid"] ?? 0);
    if($nsid<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Missing namespace ID"));
        return false;
    }

    $ns_name=ns_name_by_id($nsid);
    $title="{firewall_rules}: ".htmlspecialchars($ns_name)." (#$nsid)";

    $f=array();
    $f[]="<div style='margin-top:10px'>";
    $f[]="<table style='width:100%'><tr>";
    $f[]="<td><h3 style='margin:0'><i class='fas fa-shield-alt'></i> $title</h3></td>";
    $f[]="<td style='text-align:right'>";
    $f[]="<button class='btn btn-primary btn-sm' OnClick=\"Loadjs('$page?rule-add-js=yes&nsid=$nsid');\"><i class='fas fa-plus'></i> {add}</button>";
    $f[]=" <button class='btn btn-default btn-sm' OnClick=\"LoadAjax('fw-rules-tbl','$page?rules-table=yes&nsid=$nsid');\"><i class='fas fa-sync-alt'></i></button>";
    $f[]="</td>";
    $f[]="</tr></table>";
    $f[]="</div>";
    $f[]="<div id='fw-rules-tbl'></div>";
    $f[]="<script>LoadAjax('fw-rules-tbl','$page?rules-table=yes&nsid=$nsid');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ─── Rules Table ─────────────────────────────

function rules_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $nsid=intval($_GET["nsid"] ?? 0);
    if($nsid<1){echo $tpl->_ENGINE_parse_body($tpl->div_error("Missing namespace ID"));return false;}

    $rules=ns_get_firewall_rules($nsid);
    if($rules===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: cannot load namespace config"));
        return false;
    }

    if(count($rules)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}: {firewall_rules}"));
        return true;
    }

    $td1=$tpl->table_td1prc();
    $t=time();
    $f=array();
    $f[]="<table id='tbl-fwr-$t' class='table table-striped table-hover' style='margin-top:10px'>";
    $f[]="<thead><tr>";
    $f[]="<th $td1>{enabled}</th>";
    $f[]="<th $td1>#</th>";
    $f[]="<th>{description}</th>";
    $f[]="<th $td1>{protocol}</th>";
    $f[]="<th $td1>{destination_port}</th>";
    $f[]="<th $td1>{target_port}</th>";
    $f[]="<th>{sources}</th>";
    $f[]="<th $td1>DEL</th>";
    $f[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($rules as $idx=>$rule){
        $enabled=isset($rule->enabled) ? intval($rule->enabled) : 0;
        $desc=htmlspecialchars($rule->description ?? "");
        $proto=htmlspecialchars($rule->protocol ?? "tcp");
        if(strlen($proto)==0){$proto="tcp";}
        $dst_port=intval($rule->dst_port ?? 0);
        $target_port=intval($rule->target_port ?? 0);
        if($target_port==0){$target_port=$dst_port;}
        $sources_arr=(isset($rule->sources) && is_array($rule->sources)) ? $rule->sources : array();
        $sources_str=count($sources_arr)>0
            ? htmlspecialchars(implode(", ",$sources_arr))
            : "<span style='color:#999'>{any}</span>";

        $enabled_ico=$tpl->icon_check($enabled,
            "Loadjs('$page?rule-toggle=$idx&nsid=$nsid&state=".($enabled ? "0" : "1")."')",
            null,"AsFirewallManager");

        $tr_id="tr-fwr-$idx";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $f[]="<tr class='$TRCLASS' id='$tr_id'>";
        $f[]="<td $td1>$enabled_ico</td>";
        $f[]="<td $td1>$idx</td>";
        $f[]="<td>".$tpl->td_href(strlen($desc)>0 ? $desc : "Rule #$idx","{click_to_edit}","Loadjs('$page?rule-edit-js=$idx&nsid=$nsid');","AsFirewallManager")."</td>";
        $f[]="<td $td1><span class='label' style='background:#1c84c6;color:#fff'>".strtoupper($proto)."</span></td>";
        $f[]="<td $td1><strong>$dst_port</strong></td>";
        $f[]="<td $td1>$target_port</td>";
        $f[]="<td>$sources_str</td>";
        $f[]="<td $td1>".$tpl->icon_delete("Loadjs('$page?rule-delete-js=$idx&nsid=$nsid&md=$tr_id');","AsFirewallManager")."</td>";
        $f[]="</tr>";
    }
    $f[]="</tbody></table>";
    $f[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ─── Add Rule ────────────────────────────────

function rule_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $nsid=intval($_GET["nsid"]);
    return $tpl->js_dialog2("{add} {firewall_rules}","$page?rule-add-popup=yes&nsid=$nsid",700);
}

function rule_add_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nsid=intval($_GET["nsid"]);

    $protos=array("tcp"=>"TCP","udp"=>"UDP");

    $form=array();
    $form[]=$tpl->field_hidden("rule-add-save",1);
    $form[]=$tpl->field_hidden("nsid",$nsid);
    $form[]=$tpl->field_checkbox("rule-enabled","{enabled}",1,false);
    $form[]=$tpl->field_text("rule-description","{description}","",false,"{firewall_rule_desc_explain}");
    $form[]=$tpl->field_array_hash($protos,"rule-protocol","{protocol}","tcp");
    $form[]=$tpl->field_numeric("rule-dst-port","{destination_port}",8443,true,"1-65535");
    $form[]=$tpl->field_numeric("rule-target-port","{target_port}",0,false,"{target_port_explain}");
    $form[]=$tpl->field_textarea("rule-sources","{sources}","","100%","80px",false,"{sources_explain}");

    $f=array();
    $f[]=$tpl->form_outside("{add} {firewall_rules}",$form,null,"{save}",
        "dialogInstance2.close();LoadAjax('fw-rules-tbl','$page?rules-table=yes&nsid=$nsid');","AsFirewallManager");
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function rule_add_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $nsid=intval($_POST["nsid"] ?? 0);
    if($nsid<1){echo $tpl->post_error("Missing namespace ID");return false;}

    $dst_port=intval($_POST["rule-dst-port"] ?? 0);
    if($dst_port<1 || $dst_port>65535){echo $tpl->post_error("{destination_port}: 1-65535");return false;}

    $new_rule=array(
        "enabled"=>intval($_POST["rule-enabled"] ?? 0) ? true : false,
        "description"=>trim($_POST["rule-description"] ?? ""),
        "protocol"=>trim($_POST["rule-protocol"] ?? "tcp"),
        "dst_port"=>$dst_port,
    );
    $target_port=intval($_POST["rule-target-port"] ?? 0);
    if($target_port>0 && $target_port<=65535){$new_rule["target_port"]=$target_port;}

    $sources=parse_sources($_POST["rule-sources"] ?? "");
    if(count($sources)>0){$new_rule["sources"]=$sources;}

    // Load current config, append rule, save
    $ns_cfg=ns_load_config($nsid);
    if($ns_cfg===null){echo $tpl->post_error("Cannot load namespace config");return false;}

    $rules=array();
    if(isset($ns_cfg->firewall_rules) && is_array($ns_cfg->firewall_rules)){
        $rules=json_decode(json_encode($ns_cfg->firewall_rules),true);
    }
    $rules[]=$new_rule;

    $err=ns_save_firewall_rules($nsid,$ns_cfg,$rules);
    if($err!==null){echo $tpl->post_error(htmlspecialchars($err));return false;}

    admin_tracks("Add firewall rule to namespace #$nsid: port $dst_port");
    return true;
}

// ─── Edit Rule ───────────────────────────────

function rule_edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $idx=intval($_GET["rule-edit-js"]);
    $nsid=intval($_GET["nsid"]);
    return $tpl->js_dialog1("{edit} {firewall_rules} #$idx","$page?rule-edit-popup=$idx&nsid=$nsid",700);
}

function rule_edit_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $idx=intval($_GET["rule-edit-popup"]);
    $nsid=intval($_GET["nsid"]);

    $rules=ns_get_firewall_rules($nsid);
    if($rules===null || !isset($rules[$idx])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Rule #$idx not found"));
        return false;
    }
    $rule=$rules[$idx];
    $enabled=isset($rule->enabled) ? intval($rule->enabled) : 1;
    $desc=$rule->description ?? "";
    $proto=$rule->protocol ?? "tcp";
    if(strlen($proto)==0){$proto="tcp";}
    $dst_port=intval($rule->dst_port ?? 0);
    $target_port=intval($rule->target_port ?? 0);
    $sources_arr=(isset($rule->sources) && is_array($rule->sources)) ? $rule->sources : array();
    $sources_text=implode("\n",$sources_arr);

    $protos=array("tcp"=>"TCP","udp"=>"UDP");

    $form=array();
    $form[]=$tpl->field_hidden("rule-edit-save",1);
    $form[]=$tpl->field_hidden("nsid",$nsid);
    $form[]=$tpl->field_hidden("rule-idx",$idx);
    $form[]=$tpl->field_checkbox("rule-enabled","{enabled}",$enabled,false);
    $form[]=$tpl->field_text("rule-description","{description}",$desc,false);
    $form[]=$tpl->field_array_hash($protos,"rule-protocol","{protocol}",$proto);
    $form[]=$tpl->field_numeric("rule-dst-port","{destination_port}",$dst_port,true,"1-65535");
    $form[]=$tpl->field_numeric("rule-target-port","{target_port}",$target_port,false,"{target_port_explain}");
    $form[]=$tpl->field_textarea("rule-sources","{sources}",$sources_text,"100%","80px",false,"{sources_explain}");

    $f=array();
    $f[]=$tpl->form_outside("{edit} Rule #$idx",$form,null,"{save}",
        "dialogInstance1.close();LoadAjax('fw-rules-tbl','$page?rules-table=yes&nsid=$nsid');","AsFirewallManager");
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function rule_edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $nsid=intval($_POST["nsid"] ?? 0);
    $idx=intval($_POST["rule-idx"] ?? -1);
    if($nsid<1){echo $tpl->post_error("Missing namespace ID");return false;}

    $dst_port=intval($_POST["rule-dst-port"] ?? 0);
    if($dst_port<1 || $dst_port>65535){echo $tpl->post_error("{destination_port}: 1-65535");return false;}

    $ns_cfg=ns_load_config($nsid);
    if($ns_cfg===null){echo $tpl->post_error("Cannot load namespace config");return false;}

    $rules=array();
    if(isset($ns_cfg->firewall_rules) && is_array($ns_cfg->firewall_rules)){
        $rules=json_decode(json_encode($ns_cfg->firewall_rules),true);
    }
    if(!isset($rules[$idx])){echo $tpl->post_error("Rule #$idx not found");return false;}

    $rules[$idx]=array(
        "enabled"=>intval($_POST["rule-enabled"] ?? 0) ? true : false,
        "description"=>trim($_POST["rule-description"] ?? ""),
        "protocol"=>trim($_POST["rule-protocol"] ?? "tcp"),
        "dst_port"=>$dst_port,
    );
    $target_port=intval($_POST["rule-target-port"] ?? 0);
    if($target_port>0 && $target_port<=65535){$rules[$idx]["target_port"]=$target_port;}

    $sources=parse_sources($_POST["rule-sources"] ?? "");
    if(count($sources)>0){$rules[$idx]["sources"]=$sources;}

    $err=ns_save_firewall_rules($nsid,$ns_cfg,$rules);
    if($err!==null){echo $tpl->post_error(htmlspecialchars($err));return false;}

    admin_tracks("Edit firewall rule #$idx in namespace #$nsid");
    return true;
}

// ─── Delete Rule ─────────────────────────────

function rule_delete_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $idx=intval($_GET["rule-delete-js"]);
    $nsid=intval($_GET["nsid"]);
    $md=$_GET["md"];

    header("content-type: application/x-javascript");
    $confirm_text=$tpl->javascript_parse_text("{confirm_delete_rule}");
    echo "swal({
        title: '$confirm_text #$idx',
        text: '{destination_port}: ' + document.querySelector('#$md td:nth-child(5)').innerText,
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ed5565',
        confirmButtonText: '{delete}',
        cancelButtonText: '{cancel}'
    }, function(isConfirm){
        if(!isConfirm) return;
        $.post('$page',{'rule-delete':'$idx','nsid':'$nsid'},function(r){
            if(r.indexOf('error')>-1){ document.getElementById('fw-rules-tbl').innerHTML=r; return; }
            $('#$md').fadeOut(300,function(){ $(this).remove(); });
        });
    });";
    return true;
}

function rule_delete():bool{
    $tpl=new template_admin();
    $idx=intval($_POST["rule-delete"]);
    $nsid=intval($_POST["nsid"] ?? 0);
    if($nsid<1){echo $tpl->post_error("Missing namespace ID");return false;}

    $ns_cfg=ns_load_config($nsid);
    if($ns_cfg===null){echo $tpl->post_error("Cannot load namespace config");return false;}

    $rules=array();
    if(isset($ns_cfg->firewall_rules) && is_array($ns_cfg->firewall_rules)){
        $rules=json_decode(json_encode($ns_cfg->firewall_rules),true);
    }
    if(!isset($rules[$idx])){echo $tpl->post_error("Rule #$idx not found");return false;}

    array_splice($rules,$idx,1);

    $err=ns_save_firewall_rules($nsid,$ns_cfg,$rules);
    if($err!==null){echo $tpl->post_error(htmlspecialchars($err));return false;}

    admin_tracks("Delete firewall rule #$idx from namespace #$nsid");
    return true;
}

// ─── Toggle Enable/Disable ───────────────────

function rule_toggle():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $idx=intval($_GET["rule-toggle"]);
    $nsid=intval($_GET["nsid"]);
    $state=intval($_GET["state"]);

    $ns_cfg=ns_load_config($nsid);
    if($ns_cfg===null){return $tpl->js_error("Cannot load namespace config");}

    $rules=array();
    if(isset($ns_cfg->firewall_rules) && is_array($ns_cfg->firewall_rules)){
        $rules=json_decode(json_encode($ns_cfg->firewall_rules),true);
    }
    if(!isset($rules[$idx])){return $tpl->js_error("Rule #$idx not found");}

    $rules[$idx]["enabled"]=$state ? true : false;

    $err=ns_save_firewall_rules($nsid,$ns_cfg,$rules);
    if($err!==null){return $tpl->js_error(htmlspecialchars($err));}

    admin_tracks(($state ? "Enable" : "Disable")." firewall rule #$idx in namespace #$nsid");
    header("content-type: application/x-javascript");
    echo "LoadAjax('fw-rules-tbl','$page?rules-table=yes&nsid=$nsid');\n";
    return true;
}

// ─── Helpers ─────────────────────────────────

function ns_name_by_id(int $nsid):string{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/$nsid"));
    if(is_object($json) && $json->success){
        $ns=isset($json->data->config) ? $json->data->config : $json->data;
        if(isset($ns->name) && strlen($ns->name)>0){return $ns->name;}
    }
    return "ns-$nsid";
}

function ns_load_config(int $nsid):?object{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/$nsid"));
    if(!is_object($json) || !$json->success){return null;}
    return isset($json->data->config) ? $json->data->config : $json->data;
}

function ns_get_firewall_rules(int $nsid):?array{
    $ns_cfg=ns_load_config($nsid);
    if($ns_cfg===null){return null;}
    if(!isset($ns_cfg->firewall_rules) || !is_array($ns_cfg->firewall_rules)){return array();}
    return $ns_cfg->firewall_rules;
}

function ns_save_firewall_rules(int $nsid, object $ns_cfg, array $rules):?string{
    // Build the full namespace payload preserving existing config
    $payload=array();
    $payload["name"]=$ns_cfg->name ?? "";
    $payload["enabled"]=isset($ns_cfg->enabled) ? (bool)$ns_cfg->enabled : true;

    if(isset($ns_cfg->ipv4) && is_object($ns_cfg->ipv4)){
        $ipv4=array("address"=>$ns_cfg->ipv4->address ?? "");
        if(isset($ns_cfg->ipv4->gateway) && strlen($ns_cfg->ipv4->gateway)>0){
            $ipv4["gateway"]=$ns_cfg->ipv4->gateway;
        }
        $payload["ipv4"]=$ipv4;
    }
    if(isset($ns_cfg->ipv6) && is_object($ns_cfg->ipv6)){
        $ipv6=array("address"=>$ns_cfg->ipv6->address ?? "");
        if(isset($ns_cfg->ipv6->gateway) && strlen($ns_cfg->ipv6->gateway)>0){
            $ipv6["gateway"]=$ns_cfg->ipv6->gateway;
        }
        $payload["ipv6"]=$ipv6;
    }
    if(isset($ns_cfg->host_side_ipv4) && is_object($ns_cfg->host_side_ipv4)){
        $payload["host_side_ipv4"]=array("address"=>$ns_cfg->host_side_ipv4->address ?? "");
    }
    if(isset($ns_cfg->host_side_ipv6) && is_object($ns_cfg->host_side_ipv6)){
        $payload["host_side_ipv6"]=array("address"=>$ns_cfg->host_side_ipv6->address ?? "");
    }
    if(isset($ns_cfg->nat) && is_object($ns_cfg->nat)){
        $nat=array("enabled"=>isset($ns_cfg->nat->enabled) ? (bool)$ns_cfg->nat->enabled : false);
        if(isset($ns_cfg->nat->out_interface) && strlen($ns_cfg->nat->out_interface)>0){
            $nat["out_interface"]=$ns_cfg->nat->out_interface;
        }
        $payload["nat"]=$nat;
    }
    if(isset($ns_cfg->routes) && is_array($ns_cfg->routes)){
        $payload["routes"]=json_decode(json_encode($ns_cfg->routes),true);
    }
    if(isset($ns_cfg->dns) && is_array($ns_cfg->dns)){
        $payload["dns"]=$ns_cfg->dns;
    }

    // Set the updated firewall rules
    $payload["firewall_rules"]=$rules;

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/network/namespaces/$nsid",$payload));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return strlen($err)>0 ? $err : "Unknown error";
    }
    return null;
}

function parse_sources(string $raw):array{
    $sources=array();
    $lines=preg_split('/[\s,;]+/',trim($raw));
    foreach($lines as $line){
        $line=trim($line);
        if(strlen($line)==0){continue;}
        // Validate as IP or CIDR
        if(!strpos($line,"/")){$line.="/32";}
        if(filter_var(explode("/",$line)[0],FILTER_VALIDATE_IP)){
            $parts=explode("/",$line);
            $prefix=intval($parts[1]);
            if($prefix>=0 && $prefix<=32){
                $sources[]=$line;
            }
        }
    }
    return $sources;
}
