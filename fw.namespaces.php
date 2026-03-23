<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["health"])){health();exit;}
if(isset($_GET["apply-all"])){apply_all();exit;}
if(isset($_GET["reload-config"])){reload_config();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["tinyjs"])){tinyjs();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{network_namespaces}","fas fa-layer-group",
        "{network_namespaces_explain}","$page?tabs=yes",
        "namespaces","progress-namespaces",false,"namespaces-content");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?start=yes";
    $array["{namespaces}"]="fw.namespaces.list.php?search-form=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%;margin-top:10px;'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-status'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='center-status'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("top-status",$page,"top-status=yes");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function top_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/health"));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: ".htmlspecialchars($err)));
        $html[]="<script>";
        $html[]="LoadAjaxSilent('center-status','$page?health=yes');";
        $html[]="Loadjs('$page?tinyjs=yes')";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }


    $health=$json->data;


    $total=isset($health->total) ? intval($health->total) : 0;
    $active=isset($health->active) ? intval($health->active) : 0;
    $degraded=isset($health->degraded) ? intval($health->degraded) : 0;
    $disabled=isset($health->disabled) ? intval($health->disabled) : 0;
    $healthy=isset($health->healthy) ? $health->healthy : false;
    $last_reconcile="-";
    if(isset($health->last_reconcile) && strlen($health->last_reconcile)>4){
        try{
            $dt=new DateTime($health->last_reconcile);
            $last_reconcile=$dt->format("Y-m-d H:i:s");
        }catch(Exception $e){
            $last_reconcile=$health->last_reconcile;
        }
    }

    $total_widget=$tpl->widget_grey("{total}","{none}",array(),ico_networks);
    $degraded_widget=$tpl->widget_grey("{degraded}","{none}",array(),ico_nic);
    $health_widget=$tpl->widget_grey("{status}","{none}",array(),ico_engine);
    if($total>0){
        $btn[0]["name"] = "{reload}";
        $btn[0]["icon"] = ico_refresh;
        $btn[0]["js"] = "Loadjs('$page?reload-config=yes');";


        $total_widget=$tpl->widget_vert("{active2}/{total}","$active/$total",$btn,ico_networks);
        $btn=array();
        $btn[0]["name"] = "{reconfigure}";
        $btn[0]["icon"] = ico_save;
        $btn[0]["js"] = "Loadjs('$page?apply-all=yes');";

        if(!$healthy){
            $health_widget=$tpl->widget_rouge("{status}","{degraded}",$btn,ico_engine);
        }else{
            $health_widget=$tpl->widget_vert("{status}","{healthy}",$btn,ico_engine);
        }
        if($degraded>0){
            $degraded_widget=$tpl->widget_jaune("{degraded}",$degraded,$btn,ico_bug);
        }
    }


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:33%'>$health_widget</td>";
    $html[]="<td style='vertical-align:top;width:33%;padding-left: 5px'>$total_widget</td>";
    $html[]="<td style='vertical-align:top;width:33%;padding-left: 5px'>$degraded_widget</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('center-status','$page?health=yes');";
    $html[]="Loadjs('$page?tinyjs=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tinyjs():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    $page=CurrentPageName();

    $topbuttons[]=array("Loadjs('$page?reload-config=yes')",ico_refresh,"{reload}");
    $topbuttons[]=array("Loadjs('$page?apply-all=yes')",ico_save,"{apply}");

    $help_url="https://wiki.articatech.com/e/en/network/namespaces";
    $js_help="s_PopUpFull('$help_url','1024','900');";
    $topbuttons[] = array($js_help, ico_support, "WIKI");

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
function health():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/health"));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: ".htmlspecialchars($err)));
        return false;
    }
    $health=$json->data;
    $f=array();

    if($health->no_config){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_network_prod}"));
        return false;

    }


    if(isset($health->namespaces) && is_array($health->namespaces)){
        $f[]="<table class='table table-striped table-hover' style='margin-top:10px'>";
        $f[]="<thead>";
        $f[]="<tr>";
        $f[]="<th>{name}</th>";
        $f[]="<th style='width:1%' nowrap>{status}</th>";
        $f[]="<th style='width:1%' nowrap>{ipaddr}</th>";
        $f[]="<th style='width:1%' nowrap>NAT</th>";
        $f[]="<th style='width:1%' nowrap>Loopback</th>";
        $f[]="</tr>";
        $f[]="</thead>";
        $f[]="<tbody>";
        foreach($health->namespaces as $ns){
            $ns_name=isset($ns->name) ? htmlspecialchars($ns->name) : "#".($ns->id ?? "?");
            $ns_status=isset($ns->status) ? $ns->status : "unknown";
            $ns_addr=isset($ns->address) ? htmlspecialchars($ns->address) : "-";
            $ns_nat=(isset($ns->has_nat) && $ns->has_nat) ? "<i class='fas fa-check text-success'></i>" : "<i class='fas fa-times text-muted'></i>";
            $ns_lo=(isset($ns->lo_up) && $ns->lo_up) ? "<i class='fas fa-check text-success'></i>" : "<i class='fas fa-times text-danger'></i>";
            $status_badge="";
            switch($ns_status){
                case "active": $status_badge="<span class='label' style='background:#1ab394;color:#fff'>{active2}</span>"; break;
                case "degraded": $status_badge="<span class='label' style='background:#f8ac59;color:#fff'>{degraded}</span>"; break;
                case "disabled": $status_badge="<span class='label' style='background:#676a6c;color:#fff'>{disabled}</span>"; break;
                case "missing": $status_badge="<span class='label' style='background:#ed5565;color:#fff'>{missing}</span>"; break;
                default: $status_badge="<span class='label label-default'>$ns_status</span>"; break;
            }
            $f[]="<tr><td><strong>$ns_name</strong></td><td>$status_badge</td><td>$ns_addr</td><td>$ns_nat</td><td>$ns_lo</td></tr>";
        }
        $f[]="</tbody></table></div></div></div></div>";
    }

    $f[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function apply_all():bool{
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/config/apply",array()));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return $tpl->js_error(htmlspecialchars($err));
    }
   if(strlen($function)>1){
       echo "$function();";
   }
   return  admin_tracks("Apply all namespaces");

}

function reload_config():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/network/namespaces/config/reload",array()));
    if(!is_object($json) || !$json->success){
        $err="";
        if(is_object($json) && isset($json->error)){
            $err=is_object($json->error) ? $json->error->message : $json->error;
        }
        return $tpl->js_error(htmlspecialchars($err));
    }
    admin_tracks("Reload namespaces config from disk");
    return true;
}
