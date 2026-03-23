<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["tinyjs"])){tinyjs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ns-add-js"])){ns_add_js();exit;}
if(isset($_GET["ns-add-popup"])){ns_add_popup();exit;}
if(isset($_POST["ns-create"])){ns_create();exit;}
if(isset($_GET["ns-edit-js"])){ns_edit_js();exit;}
if(isset($_GET["ns-edit-popup"])){ns_edit_popup();exit;}
if(isset($_POST["ns-update-id"])){ns_update();exit;}
if(isset($_GET["ns-delete-js"])){ns_delete_js();exit;}
if(isset($_POST["ns-delete"])){ns_delete();exit;}
if(isset($_GET["ns-enable"])){ns_enable();exit;}
if(isset($_GET["ns-disable"])){ns_disable();exit;}
if(isset($_GET["ns-reconcile"])){ns_reconcile();exit;}
if(isset($_GET["ns-test-js"])){ns_test_js();exit;}
if(isset($_GET["ns-test-popup"])){ns_test_popup();exit;}
if(isset($_POST["ns-test-run"])){ns_test_run();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{network_namespaces}","fas fa-layer-group",
        "{network_namespaces_explain}","$page?search-form=yes",
        "namespaces-list","progress-namespaces-list",false,"table-namespaces");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page,"","","","&table=yes","");
    return true;
}

function tinyjs():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $topbuttons[]=array("Loadjs('$page?ns-add-js=yes&function=$function')",ico_plus,"{add}");
    $topbuttons[]=array("Loadjs('fw.namespaces.php?apply-all=yes&function=$function')",ico_save,"{apply}");

    $btns=array();
    if($users->AsFirewallManager || $users->AsSystemAdministrator){
        $btns=$tpl->table_buttons($topbuttons);
    }

    $TINY_ARRAY["TITLE"]="{network_namespaces}";
    $TINY_ARRAY["ICO"]="fas fa-layer-group";
    $TINY_ARRAY["EXPL"]="{network_namespaces_explain}";
    $TINY_ARRAY["BUTTONS"]=$btns;

    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $search=null;
    $function=$_GET["function"];
    if(isset($_GET["search"])){
        $search=trim($_GET["search"]);
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/list"));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: ".htmlspecialchars($err)));
        return false;
    }

    if(!isset($json->data) || !is_array($json->data) || count($json->data)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data}"));
        echo "<script>Loadjs('$page?tinyjs=yes&function=$function');</script>";
        return true;
    }

    // Fetch metrics for all namespaces
    $metrics_map=array();
    $metrics_json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/metrics"));
    if(is_object($metrics_json) && $metrics_json->success && is_array($metrics_json->data)){
        foreach($metrics_json->data as $m){
            $mid=intval($m->id ?? 0);
            if($mid>0){$metrics_map[$mid]=$m;}
        }
    }

    $t=time();
    $td1=$tpl->table_td1prc();
    $f=array();
    $f[]="<table id='tbl-ns-$t' class='table table-striped table-hover' data-page-size='100'>";
    $f[]="<thead><tr>";
    $f[]="<th $td1>{enabled}</th>";
    $f[]="<th data-sortable=true data-type='text'>ID</th>";
    $f[]="<th data-sortable=true data-type='text'>{name}</th>";
    $f[]="<th data-sortable=true data-type='text'>IPv4</th>";
    $f[]="<th data-sortable=true data-type='text'>Host IPv4</th>";
    $f[]="<th $td1>NAT</th>";
    $f[]="<th $td1>FW</th>";
    $f[]="<th>{traffic}</th>";
    $f[]="<th $td1>{status}</th>";
    $f[]="<th $td1>{test}</th>";
    $f[]="<th $td1>{reconcile}</th>";
    $f[]="<th $td1>DEL</th>";
    $f[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($json->data as $ns){
        $id=intval($ns->ID ?? $ns->id ?? 0);
        $name=htmlspecialchars($ns->name ?? "ns-$id");
        $enabled=isset($ns->enabled) ? intval($ns->enabled) : 0;
        $exists=isset($ns->exists) ? $ns->exists : false;

        if($search!=null){
            $needle=strtolower(str_replace("*","",$search));
            if(stripos($name,$needle)===false && stripos("$id",$needle)===false){
                continue;
            }
        }

        $ns_addr="-";
        if(isset($ns->ns_addrs) && is_array($ns->ns_addrs) && count($ns->ns_addrs)>0){
            $ns_addr=htmlspecialchars($ns->ns_addrs[0]);
        }
        $host_addr="-";
        if(isset($ns->host_addrs) && is_array($ns->host_addrs) && count($ns->host_addrs)>0){
            $host_addr=htmlspecialchars($ns->host_addrs[0]);
        }
        $has_nat=(isset($ns->has_nat) && $ns->has_nat) ? "<i class='".ico_check."'></i>" : "";

        $status_badge="";
        if(!$enabled){
            $status_badge="<span class='label' style='background:#676a6c;color:#fff'>{disabled}</span>";
        }elseif($exists){
            $lo_up=isset($ns->lo_up) ? $ns->lo_up : false;
            if($lo_up){
                $status_badge="<span class='label' style='background:#1ab394;color:#fff'>{active2}</span>";
            }else{
                $status_badge="<span class='label' style='background:#f8ac59;color:#fff'>{degraded}</span>";
            }
        }else{
            $status_badge="<span class='label' style='background:#ed5565;color:#fff'>{missing}</span>";
        }

        $enabled_ico=$tpl->icon_check($enabled,
            $enabled ? "Loadjs('$page?ns-disable=$id')" : "Loadjs('$page?ns-enable=$id')",
            null,"AsFirewallManager");

        $tr_id="tr-ns-$id";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $f[]="<tr class='$TRCLASS' id='$tr_id'>";
        $f[]="<td $td1>$enabled_ico</td>";
        $f[]="<td $td1>$id</td>";
        $f[]="<td>".$tpl->td_href($name,"{click_to_edit}","Loadjs('$page?ns-edit-js=$id');","AsFirewallManager")."</td>";
        $f[]="<td>$ns_addr</td>";
        $f[]="<td>$host_addr</td>";
        $fw_link=$tpl->icon_firewall("Loadjs('fw.namespaces.firewall.php?nsid=$id')","AsFirewallManager");

        // Traffic metrics
        $traffic_cell="-";
        if(isset($metrics_map[$id])){
            $m=$metrics_map[$id];
            $rx=intval($m->rx_bytes ?? 0);
            $tx=intval($m->tx_bytes ?? 0);
            $dnat_pkts=intval($m->dnat_packets ?? 0);
            $dnat_b=intval($m->dnat_bytes ?? 0);
            if($rx>0 || $tx>0){
                $rx_fmt=FormatBytes(round($rx/1024),true);
                $tx_fmt=FormatBytes(round($tx/1024),true);
                $parts=array();
                $parts[]="<i class='fas fa-arrow-down' style='color:#1ab394'></i> $rx_fmt";
                $parts[]="<i class='fas fa-arrow-up' style='color:#1c84c6'></i> $tx_fmt";
                if($dnat_pkts>0){
                    $dnat_fmt=FormatBytes(round($dnat_b/1024),true);
                    $parts[]="<small style='color:#999'>DNAT: {$dnat_pkts}p / $dnat_fmt</small>";
                }
                $traffic_cell=implode("<br>",$parts);
            }
        }

        $f[]="<td $td1>$has_nat</td>";
        $f[]="<td $td1>$fw_link</td>";
        $f[]="<td style='white-space:nowrap;font-size:11px'>$traffic_cell</td>";
        $f[]="<td $td1>$status_badge</td>";
        $f[]="<td $td1>".$tpl->icon_run("Loadjs('$page?ns-test-js=$id');","AsFirewallManager","{test_connectivity}")."</td>";
        $f[]="<td $td1>".$tpl->icon_refresh("Loadjs('$page?ns-reconcile=$id&function=$function');","AsFirewallManager","{reconcile}")."</td>";
        $f[]="<td $td1>".$tpl->icon_delete("Loadjs('$page?ns-delete-js=$id&md=$tr_id');","AsFirewallManager")."</td>";
        $f[]="</tr>";
    }
    $f[]="</tbody>";
    $f[]="<tfoot><tr><td colspan='12'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $f[]="</table>";
    $f[]="<script>";
    $f[]="Loadjs('$page?tinyjs=yes&function=$function');";
    $f[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $f[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ─── Helpers ──────────────────────────────────

function ns_check_gateway($cidr,$gw):?string{
    $tpl=new template_admin();
    if(strlen($cidr)==0 || strlen($gw)==0){return null;}
    $parts=explode("/",$cidr);
    if(count($parts)!=2){return "invalid CIDR: $cidr";}
    $ip=ip2long($parts[0]);
    $prefix=intval($parts[1]);
    $gwl=ip2long($gw);
    if($ip===false){
        return $tpl->javascript_parse_text("{invalid_ipaddr} {gateway} $parts[0] ($cidr)");
    }
    if($gwl===false){
        return $tpl->javascript_parse_text("{invalid_ipaddr} {gateway} $gwl");
    }
    if($prefix<1){
        return $tpl->javascript_parse_text("{invalid_ipaddr} {prefix} $prefix ($cidr)");
    }
    if($prefix>30){
        return $tpl->javascript_parse_text("{invalid_ipaddr} {prefix} $prefix ($cidr)");
    }
    $mask= -1 << (32 - $prefix);
    $network=$ip & $mask;
    $gwnet=$gwl & $mask;
    if($network!==$gwnet){
        return "gateway $gw outside subnet ".long2ip($network)."/$prefix";
    }
    return null;
}

// ─── Create ───────────────────────────────────

function ns_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{add} {network_namespaces}","$page?ns-add-popup=yes&function=$function",850);
}

function ns_add_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $form=array();
    $form[]=$tpl->field_hidden("ns-create",1);
    $form[]=$tpl->field_hidden("ns-enabled",1);
    $form[]=$tpl->field_hidden("ns-id",time());
    $form[]=$tpl->field_text("ns-name","{name}","",true,"{namespace_name_explain}");
    $form[]=$tpl->field_text("ns-ipv4-addr","IPv4 {address}","",true,"CIDR: 192.168.210.2/30");
    $form[]=$tpl->field_ipv4("ns-ipv4-gw","{gateway}","",false,"192.168.210.1");
    $form[]=$tpl->field_text("ns-host-ipv4","{host_address}","",false,"CIDR: 192.168.210.1/30");
    $form[]=$tpl->field_checkbox("ns-nat-enabled","{enable_nat_compatibility}",0,false,"{namespace_nat_explain}");
    $form[]=$tpl->field_interfaces("ns-nat-iface","{physical_network_interface}","",false,"eth0");
    $form[]=$tpl->field_text("ns-dns","{dns_servers} <small>{comma_separated}</small>","",false,"8.8.8.8,8.8.4.4");
    $f=array();
    $f[]=$tpl->form_outside("",$form,null,"{create}","dialogInstance1.close();$function()","AsFirewallManager");


    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function ns_create():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $payload=array();
    $id=intval($_POST["ns-id"] ?? 0);
    if($id<1 || $id>9999){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/list"));
        $id=1;
        if(is_object($json) && isset($json->data) && is_array($json->data)){
            $used=array();
            foreach($json->data as $ns){$used[]=intval($ns->id ?? $ns->ID ?? 0);}
            while(in_array($id,$used) && $id<=9999){$id++;}
        }
    }
    $payload["id"]=$id;
    $payload["name"]=trim($_POST["ns-name"] ?? "");
    $payload["enabled"]=intval($_POST["ns-enabled"] ?? 0) ? true : false;
    $ipv4_addr=trim($_POST["ns-ipv4-addr"] ?? "");
    if(strlen($ipv4_addr)>0){
        $payload["ipv4"]=array("address"=>$ipv4_addr);
        $gw=trim($_POST["ns-ipv4-gw"] ?? "");
        if(strlen($gw)>0){$payload["ipv4"]["gateway"]=$gw;}
    }
    $host_ipv4=trim($_POST["ns-host-ipv4"] ?? "");
    if(strlen($host_ipv4)>0){$payload["host_side_ipv4"]=array("address"=>$host_ipv4);}
    $nat_enabled=intval($_POST["ns-nat-enabled"] ?? 0);
    $payload["nat"]=array("enabled"=>$nat_enabled ? true : false);
    $nat_iface=trim($_POST["ns-nat-iface"] ?? "");
    if(strlen($nat_iface)>0){$payload["nat"]["out_interface"]=$nat_iface;}
    $dns=trim($_POST["ns-dns"] ?? "");
    if(strlen($dns)>0){$payload["dns"]=array_map("trim",explode(",",$dns));}

    $gw_err=ns_check_gateway($ipv4_addr,$gw ?? "");
    if($gw_err!==null){echo $tpl->post_error($gw_err);return false;}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/create",$payload));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->post_error(htmlspecialchars($err));
        return false;
    }
    return admin_tracks("Create namespace: ".($payload["name"] ?? $payload["id"] ?? ""));
}

// ─── Edit ─────────────────────────────────────

function ns_edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["ns-edit-js"]);
    return $tpl->js_dialog1("{edit} #$id","$page?ns-edit-popup=$id",850);
}

function ns_edit_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["ns-edit-popup"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/$id"));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return false;
    }
    $ns=isset($json->data->config) ? $json->data->config : $json->data;
    $name=$ns->name ?? "";
    $enabled=isset($ns->enabled) ? intval($ns->enabled) : 1;
    $ipv4_addr=(isset($ns->ipv4) && isset($ns->ipv4->address)) ? $ns->ipv4->address : "";
    $ipv4_gw=(isset($ns->ipv4) && isset($ns->ipv4->gateway)) ? $ns->ipv4->gateway : "";
    $host_ipv4=(isset($ns->host_side_ipv4) && isset($ns->host_side_ipv4->address)) ? $ns->host_side_ipv4->address : "";
    $nat_enabled=(isset($ns->nat) && isset($ns->nat->enabled)) ? intval($ns->nat->enabled) : 0;
    $nat_iface=(isset($ns->nat) && isset($ns->nat->out_interface)) ? $ns->nat->out_interface : "";
    $dns_list="";
    if(isset($ns->dns) && is_array($ns->dns)){
        $dns_list=implode(",",$ns->dns);
    }

    $form=array();
    $form[]=$tpl->field_hidden("ns-edit-id",$id);
    $form[]=$tpl->field_hidden("ns-update-id",$id);
    $form[]=$tpl->field_text("ns-edit-name","{name}",$name,true);
    $form[]=$tpl->field_checkbox("ns-edit-enabled","{enabled}",$enabled,true);
    $form[]=$tpl->field_ipv4("ns-edit-ipv4-addr","{address}",$ipv4_addr,true,"CIDR: 192.168.210.2/30");
    $form[]=$tpl->field_text("ns-edit-ipv4-gw","{gateway}",$ipv4_gw,false);
    $form[]=$tpl->field_text("ns-edit-host-ipv4","{host_address}",$host_ipv4,false,"{ns-edit-host}");
    $form[]=$tpl->field_checkbox("ns-edit-nat-enabled","NAT {enabled}",$nat_enabled,false);
    $form[]=$tpl->field_interfaces("ns-edit-nat-iface","{physical_network_interface}",$nat_iface,false);
    $form[]=$tpl->field_text("ns-edit-dns","{dns_servers} <small>{comma_separated}</small>",$dns_list,false);
    $f=array();
    $f[]=$tpl->form_outside("{edit} #$id",$form,null,"{apply}","dialogInstance1.close();","AsFirewallManager");
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function ns_update():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=intval($_POST["ns-update-id"]);
    $payload=array();
    $payload["name"]=trim($_POST["ns-edit-name"] ?? "");
    $payload["enabled"]=intval($_POST["ns-edit-enabled"] ?? 0) ? true : false;
    $ipv4_addr=trim($_POST["ns-edit-ipv4-addr"] ?? "");
    if(strlen($ipv4_addr)>0){
        $payload["ipv4"]=array("address"=>$ipv4_addr);
        $gw=trim($_POST["ns-edit-ipv4-gw"] ?? "");
        if(strlen($gw)>0){$payload["ipv4"]["gateway"]=$gw;}
    }
    $host_ipv4=trim($_POST["ns-edit-host-ipv4"] ?? "");
    if(strlen($host_ipv4)>0){$payload["host_side_ipv4"]=array("address"=>$host_ipv4);}
    $nat_enabled=intval($_POST["ns-edit-nat-enabled"] ?? 0);
    $payload["nat"]=array("enabled"=>$nat_enabled ? true : false);
    $nat_iface=trim($_POST["ns-edit-nat-iface"] ?? "");
    if(strlen($nat_iface)>0){$payload["nat"]["out_interface"]=$nat_iface;}
    $dns=trim($_POST["ns-edit-dns"] ?? "");
    if(strlen($dns)>0){$payload["dns"]=array_map("trim",explode(",",$dns));}

    $gw_err=ns_check_gateway($ipv4_addr,$gw ?? "");
    if($gw_err!==null){echo $tpl->post_error($gw_err);return false;}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/network/namespaces/$id",$payload));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->post_error(htmlspecialchars($err));return false;
    }
    return admin_tracks("Update namespace #$id");
}

// ─── Delete ───────────────────────────────────

function ns_delete_js():bool{
    $tpl=new template_admin();
    $id=intval($_GET["ns-delete-js"]);
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("Namespace #$id","ns-delete",$id,"$('#$md').remove()");
}

function ns_delete():bool{
    $tpl=new template_admin();
    $id=intval($_POST["ns-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/network/namespaces/$id"));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return $tpl->js_error(htmlspecialchars($err));
    }
    admin_tracks("Delete namespace #$id");
    return true;
}

// ─── Enable / Disable ─────────────────────────

function ns_enable():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["ns-enable"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/$id/enable",array()));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return $tpl->js_error(htmlspecialchars($err));
    }
    admin_tracks("Enable namespace #$id");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-namespaces','$page?table=yes');\n";
    return true;
}

function ns_disable():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["ns-disable"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/$id/disable",array()));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return $tpl->js_error(htmlspecialchars($err));
    }
    admin_tracks("Disable namespace #$id");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-namespaces','$page?table=yes');\n";
    return true;
}

// ─── Reconcile ────────────────────────────────

function ns_reconcile():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["ns-reconcile"]);
    $function=$_GET["function"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/$id/reconcile",array()));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return $tpl->js_error(htmlspecialchars($err));
    }
    admin_tracks("Reconcile namespace #$id");
    header("content-type: application/x-javascript");
    echo "$function();\n";
    return true;
}

// ─── Connectivity Test ────────────────────────

function ns_test_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["ns-test-js"]);
    return $tpl->js_dialog2("{test_connectivity} #$id","$page?ns-test-popup=$id",550);
}

function ns_test_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["ns-test-popup"]);

    $f=array();
    $f[]="<div style='padding:15px'>";
    $f[]="<form id='frm-ns-test'>";
    $f[]=$tpl->field_hidden("ns-test-id",$id);
    $f[]="<table style='width:100%'><tr><td style='width:30%;font-weight:bold'>{target}</td>";
    $f[]="<td><input type='text' id='ns-test-target' name='ns-test-target' class='form-control' value='8.8.8.8' placeholder='IP {address}'></td></tr></table>";
    $f[]="</form>";
    $f[]="<div style='margin-top:10px'>";
    $f[]="<button class='btn btn-primary' OnClick=\"ns_test_run();\"><i class='fas fa-satellite-dish'></i> {test}</button>";
    $f[]="</div>";
    $f[]="<div id='ns-test-result' style='margin-top:15px'></div>";
    $f[]="</div>";

    $f[]="<script>";
    $f[]="function ns_test_run(){";
    $f[]="  var target=document.getElementById('ns-test-target').value;";
    $f[]="  if(target.length<3){return;}";
    $f[]="  document.getElementById('ns-test-result').innerHTML='<i class=\"fas fa-spinner fa-spin\"></i> {testing}...';";
    $f[]="  $.post('$page',{\"ns-test-run\":\"$id\",\"target\":target},function(r){";
    $f[]="    document.getElementById('ns-test-result').innerHTML=r;";
    $f[]="  });";
    $f[]="};";
    $f[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function ns_test_run():bool{
    $tpl=new template_admin();
    $id=intval($_POST["ns-test-run"]);
    $target=trim($_POST["target"]);
    $target=$tpl->CLEAN_BAD_XSS($target);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/network/namespaces/$id/test-connectivity?target=".urlencode($target),array()));

    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return false;
    }

    $result=$json->data;
    $ok=isset($result->success) ? $result->success : false;
    $latency=isset($result->latency) ? htmlspecialchars($result->latency) : "-";
    $res_target=isset($result->target) ? htmlspecialchars($result->target) : $target;
    $res_error=isset($result->error) ? htmlspecialchars($result->error) : "";

    if($ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_success(
            "<strong>$res_target</strong>: {success} — {latency}: <strong>$latency</strong>"));
    }else{
        echo $tpl->_ENGINE_parse_body($tpl->div_error(
            "<strong>$res_target</strong>: {failed} — $res_error"));
    }
    return true;
}
