<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_POST["agent-upgrade-ssh"])){agents_upgrade_ssh_perform();exit;}
if(isset($_GET["agent-upgrade-sshresult"])){agents_upgrade_ssh_results();exit;}
if(isset($_GET["agents-upgrade-ssh"])){agents_upgrade_ssh();exit;}
if(isset($_GET["agent-delete-js"])){agent_delete_js();exit;}
if(isset($_GET["agents-upgrade-api"])){agents_upgrade_api();exit;}
if(isset($_GET["agent-upgrade-result"])){agents_upgrade_results();exit;}
if(isset($_POST["agent-upgrade-perform"])){agents_upgrade_perform();exit;}
if(isset($_GET["agents-upgrade-popup"])){agents_upgrade_popup();exit;}
if(isset($_GET["agents-upgrade-js"])){agents_upgrade_js();exit;}
if(isset($_GET["artica-web-restart-js"])){artica_web_restart_js();exit;}
if(isset($_GET["agent-info-disk"])){agent_info_disk();exit;}
if(isset($_POST["agent-address-popup"])){agent_address_save();exit;}
if(isset($_GET["agent-address-js"])){agent_address_js();exit;}
if(isset($_GET["agent-address-popup"])){agent_address_popup();exit;}
if(isset($_GET["agent-flush-js"])){agent_flush_js();exit;}
if(isset($_GET["agent-info-artica-popup"])){agent_artica_softs_popup();exit;}
if(isset($_GET["agent-info-artica"])){agent_artica_softs();exit;}
if(isset($_POST["articaweb-unix"])){articaweb_unix_save();exit;}
if(isset($_GET["articaweb-unix"])){articaweb_unix();exit;}
if(isset($_GET["articaweb-unix-off"])){articaweb_unix_off();exit;}
if(isset($_POST["articaweb-unix-off"])){articaweb_unix_off_save();exit;}
if(isset($_GET["agent-info-status"])){agent_info_status();exit;}
if(isset($_GET["agent-info-status-popup"])){agent_info_status_popup();exit;}
if(isset($_GET["agent-info-tab"])){agent_info_tabs();exit;}
if(isset($_GET["agent-info-js"])){agent_info_js();exit;}
if(isset($_GET["search-form"])){agents_form();exit;}
if(isset($_GET["agent-events-search"])){agent_events_search();exit;}
if(isset($_GET["agent-events-head"])){agent_events_head();exit;}
if(isset($_GET["agent-events-js"])){events_js();exit;}
if(isset($_GET["td-all"])){td_status_all();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats"])){stats();exit;}
if(isset($_GET["agents-list"])){agents_list();exit;}
if(isset($_GET["agents-table"])){agents_table();exit;}
if(isset($_GET["agent-add-js"])){agent_add_js();exit;}
if(isset($_GET["agent-add-popup"])){agent_add_popup();exit;}
if(isset($_POST["netagent-hostname"])){agent_add_save();exit;}
if(isset($_POST["agent-delete"])){agent_delete();exit;}
if(isset($_GET["agent-ping"])){agent_ping();exit;}
if(isset($_GET["agent-token-js"])){agent_token_js();exit;}
if(isset($_GET["agent-token-popup"])){agent_token_popup();exit;}
if(isset($_POST["netagent-save-token"])){agent_token_save();exit;}
if(isset($_GET["checkall"])){check_all_agents();exit;}
if(isset($_GET["agent-enroll"])){agent_enroll();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{network_agents}",
        "fas fa-network-wired","{network_agents_explain}","$page?search-form=yes",
        "netagents-list","progress-netagents",false,"table-netagents");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{network_agents}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function agents_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,"","","","&agents-table=yes","");
    return true;
}
function agent_delete_js():bool{
    $tpl=new template_admin();
    $md=$_GET["md"];
    $id=intval($_GET["agent-delete-js"]);
    $ArticaNetAgents=new ArticaNetAgents($id);
    $hostname=$ArticaNetAgents->GetAgentHostname();
    if(strlen($hostname) < 1){
        $hostname="#$id";
    }

    return $tpl->js_confirm_delete($hostname,"agent-delete",$id,"$('#$md').remove()");
}
function agent_flush_js():bool{
    $tpl=new template_admin();
    $id=intval($_GET["agent-flush-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/".$id));
    if($json->Status){
        if(!$json->online){
            return $tpl->js_error("{offline}: $json->error");
        }
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/flush/".$id));

    return $tpl->js_ok("{success}");
}
function artica_web_restart_js():bool{
    $id=intval($_GET["artica-web-restart-js"]);
    $sock=new sockets();
    $tpl=new template_admin();
    $json=json_decode($sock->REST_API_POST("/netagents/webconsole/$id/restart",array()));
    if($json->success){
        return $tpl->js_ok($json->message. "($json->hostname)");
    }
    if(property_exists($json,"Error")) {
        return $tpl->js_error($json->Error);
    }
    return $tpl->js_error("Unknown error");
}
function agents_upgrade_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $id=intval($_GET["agents-upgrade-js"]);
    return $tpl->js_dialog5("{update_agent} v$CurVer","$page?agents-upgrade-popup=$id",650);
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?stats=yes";
    $array["{agents_list}"]="$page?agents-list=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function stats():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/stats"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    $total = isset($json->total) ? intval($json->total) : 0;
    $online = isset($json->online) ? intval($json->online) : 0;
    $offline = isset($json->offline) ? intval($json->offline) : 0;
    $pending = isset($json->pending) ? intval($json->pending) : 0;

    $f=array();
    $f[]="<div class='row'>";
    $f[]="  <div class='col-lg-3'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'><h5>{total_agents}</h5></div>";
    $f[]="      <div class='ibox-content'><h1 class='text-center'>$total</h1></div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="  <div class='col-lg-3'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'><h5>{online}</h5></div>";
    $f[]="      <div class='ibox-content'><h1 class='text-center text-success'>$online</h1></div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="  <div class='col-lg-3'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'><h5>{offline}</h5></div>";
    $f[]="      <div class='ibox-content'><h1 class='text-center text-danger'>$offline</h1></div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="  <div class='col-lg-3'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'><h5>{pending}</h5></div>";
    $f[]="      <div class='ibox-content'><h1 class='text-center text-warning'>$pending</h1></div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";

    $f[]="<div class='row'>";
    $f[]="  <div class='col-lg-12'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'>";
    $f[]="        <h5>{actions}</h5>";
    $f[]="      </div>";
    $f[]="      <div class='ibox-content'>";
    $f[]="        <button class='btn btn-primary' OnClick=\"LoadAjax('netagents-result','$page?checkall=yes');\"><i class='fas fa-sync'></i> {check_all_agents}</button>";
    $f[]="      </div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";

    $f[]="<div id='netagents-result'></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}

function agents_list():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $f=array();
    $f[]="<div class='row'>";
    $f[]="  <div class='col-lg-12'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'>";
    $f[]="        <h5>{agents_list}</h5>";
    $f[]="        <div class='ibox-tools'>";
    $f[]="          <button class='btn btn-success btn-sm' OnClick=\"Loadjs('$page?agent-add-js=yes');\"><i class='fas fa-plus'></i> {add}</button>";
    $f[]="        </div>";
    $f[]="      </div>";
    $f[]="      <div class='ibox-content' id='agents-table-container'>";
    $f[]="      </div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";
    $f[]="<script>LoadAjax('agents-table-container','$page?agents-table=yes');</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}

function agents_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(!isset($json->agents) || count($json->agents) == 0){
        $btn[]="<div style='margin:20px;text-align:right'>";
        $btn[]=$tpl->button_autnonome("wiki.articatech.com", "s_PopUpFull('https://wiki.articatech.com/artica-meta/deploy-agents','1024','900');", ico_support,
            "AsSystemAdministrator",335,"btn-warning");
        $btn[]="</div>";
        $bout=@implode("\n",$btn);
        echo $tpl->div_warning("{no_agents_configured}||{no_agent_explain}$bout");
        $topbuttons[] = array("Loadjs('$page?agent-add-js=yes&function=$function')", ico_plus, "{add_agent}");
        $topbuttons[] = array("Loadjs('fw.netagents.deploy.php?function=$function')", ico_arrow_right, "{deploy_agent}");
        $TINY_ARRAY["TITLE"]="{network_agents}";
        $TINY_ARRAY["ICO"]="fas fa-network-wired";
        $TINY_ARRAY["EXPL"]="{network_agents_explain}";
        $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
        $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        echo "<script>$headsjs</script>";
        return true;
    }
    $Agents=array();
    $f=array();
    $f[]="<table class='table table-striped table-hover' id='table-agents'>";
    $f[]="<thead><tr>";
    $f[]="  <th></th>";
    $f[]="  <th>{status}</th>";
    $f[]="  <th>{hostname}</th>";
    $f[]="  <th nowrap></th>";
    $f[]="  <th>{load}/{cpu}</th>";
    $f[]="  <th>{memory}</th>";
    $f[]="  <th>{disk}</th>";
    $f[]="  <th>UP</th>";
    $f[]="  <th nowrap>{last_seen}</th>";
    $f[]="  <th></th>";
    $f[]="  <th></th>";
    $f[]="  <th></th>";
    $f[]="  <th></th>";
    $f[]="</tr></thead>";
    $f[]="<tbody>";
    $wd1="style='width:1%' nowrap";

    foreach($json->agents as $agent){
        $id = $agent->id;
        $last_seen="-";
        if(isset($agent->last_seen)){
            if($agent->last_seen != "0001-01-01T00:00:00Z"){
                $date = preg_replace('/\.(\d{6})\d+/', '.$1', $agent->last_seen);
                $dt = new DateTime($date);
                $timestamp = $dt->getTimestamp();
                $last_seen=$tpl->time_to_date($timestamp,$date);
            }
        }


        // Check if agent needs enrollment (pending or missing certs)
        //$needs_enroll = ($status == "pending");

        $Agents[]=$id;
        $tr_id="tr-agent-$id";
        $f[]="<tr id='$tr_id'>";
        $f[]="  <td $wd1><span id='agent-$id-type'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-status'></span></td>";
        $f[]="  <td style='width:99%'><span id='agent-$id-hostname'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-web'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-cpu'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-memory'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-disk'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-uptime'></span></td>";
        $f[]="  <td $wd1>$last_seen</td>";
        $f[]="  <td $wd1>". $tpl->icon_refresh("Loadjs('$page?agent-flush-js=$id')")."</td>";
        $f[]="  <td $wd1>". $tpl->icon_list("Loadjs('$page?agent-events-js=$id')")."</td>";
        $f[]="  <td $wd1>". $tpl->icon_user_lock("Loadjs('$page?agent-token-js=$id');")."</td>";
        $f[]="  <td $wd1>". $tpl->icon_delete("Loadjs('$page?agent-delete-js=$id&md=$tr_id');")."</td>";
        $f[]="</tr>";

    }

    $topbuttons[] = array("Loadjs('$page?agent-add-js=yes&function=$function')", ico_plus, "{add_agent}");
    $topbuttons[] = array("Loadjs('fw.netagents.deploy.php?function=$function')", ico_arrow_right, "{deploy_agent}");
    $TINY_ARRAY["TITLE"]="{network_agents}";
    $TINY_ARRAY["ICO"]="fas fa-network-wired";
    $TINY_ARRAY["EXPL"]="{network_agents_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $allids=urlencode(@implode("|",$Agents));
    $f[]="</tbody></table>";
    $f[]="<script>";
    $f[]=$headsjs;
    $f[]=$tpl->RefreshInterval_Loadjs("table-agents",$page,"?td-all=$allids");
    $f[]="</script>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}
function agent_info_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-js"]);
    $Hostname=getAgentHostname($id);
    return $tpl->js_dialog2("#$id: $Hostname","$page?agent-info-tab=$id",850);
}
function agent_address_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-address-js"]);
    $Hostname=getAgentHostname($id);
    return $tpl->js_dialog3($Hostname,"$page?agent-address-popup=$id",650);
}
function agent_info_disk():bool{
    $id=intval($_GET["agent-info-disk"]);
    $html[]="<table style='width:80%;margin-top:20px;' class='table'>";
    $main=agent_status($id);
    if(!isset($main["ALLHD"])){
        return false;
    }
    $FontSize="24px";
    foreach($main["ALLHD"] as $h=>$info){
        $tpl=new template_admin();
        $total_bytes=intval($info["total_bytes"]);
        $used_bytes=intval($info["used_bytes"]);
        $total_bytes_str=FormatBytes($total_bytes/1024);
        $used_bytes_str=FormatBytes($used_bytes/1024);
        $md5hd=md5($h);
        $ss="style='width:1%;font-size:$FontSize;border-top:0px;text-transform:capitalize;padding-top:15px'";
        $ss2="style='width:99%;font-size:$FontSize;border-top:0px;text-transform:capitalize;padding-top:15px'";
        $name=basename($h);
        $html[]="<tr>";
        $html[]="<td $ss nowrap><span class='pie'>$used_bytes/$total_bytes</span></td>";
        $html[]="<td $ss2 nowrap><strong style='font-size:$FontSize'>{disk} $name</strong></td>";
        $html[]="<td $ss nowrap>{used}&nbsp;$used_bytes_str</td>";
        $html[]="<td $ss nowrap><strong style='color:#cccccc'>|</strong>&nbsp;&nbsp;{total}&nbsp;$total_bytes_str</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>$(\"span.pie\").peity(\"pie\",{ fill: [\"#ed5565\",\"#18a689\"], height:64,width:64 });</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function agent_info_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-tab"]);
    $Hostname=getAgentHostname($id);
    $AgentArtica=AgentArtica($id);
    $token="";
    if($AgentArtica){
        $token="&artica=yes";
    }
    $array[$Hostname]="$page?agent-info-status=$id$token";
    $array["{disks}"]="$page?agent-info-disk=$id$token";
    if($AgentArtica){
        $array["Artica"]="$page?agent-info-artica=$id";
    }
    //$array["{agents_list}"]="$page?agents-list=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function getAgentHostname($id):string{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    if(!is_object($json)){
        return "";
    }
    if(!property_exists($json,"hostname")){
        return "";
    }
    return $json->hostname;
}
function articaweb_unix():bool{
    $id=intval($_GET["articaweb-unix"]);
    $page=currentPageName();
    $tpl=new template_admin();
    return $tpl->js_confirm_execute("{articaweb_to_unix}","articaweb-unix",$id,
    "LoadAjaxSilent('agent-info-status-$id','$page?agent-info-status-popup=$id');");
}
function articaweb_unix_off():bool{
    $id=intval($_GET["articaweb-unix-off"]);
    $page=currentPageName();
    $tpl=new template_admin();
    return $tpl->js_confirm_execute("{articaweb_to_tcp}","articaweb-unix-off",$id,
        "LoadAjaxSilent('agent-info-status-$id','$page?agent-info-status-popup=$id');");
}


function articaweb_unix_save():bool{
    $id=intval($_POST["articaweb-unix"]);
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/articaweb/enable-unix/$id",array()));
    if(!$status->Status){
        echo $status->Error;
        return false;
    }
    return admin_tracks("Order to #$id to listens an Unix socket for Artica Web console");

}
function articaweb_unix_off_save():bool{
    $id=intval($_POST["articaweb-unix-off"]);
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/articaweb/disable-unix/$id",array()));
    if(!$status->Status){
        echo $status->Error;
        return false;
    }
    return admin_tracks("Order to #$id to listens an TCP socket for Artica Web console");

}
function agent_artica_softs():bool{
    $page=currentPageName();
    $id=intval($_GET["agent-info-artica"]);

    $tpl=new template_admin();
    echo "<div id='agent-info-artica-$id'></div>";
    $js=$tpl->RefreshInterval_js("agent-info-artica-$id",$page,"agent-info-artica-popup=$id",5);
    echo "<script>
        $('#agent-info-status-$id').remove();
        $js</script>";
    return true;
}
function agent_artica_softs_popup():bool{
    $id=$_GET["agent-info-artica-popup"];
    $tpl=new template_admin();
    $page=currentPageName();
    $sock=new ArticaNetAgents($id);
    $SquidEnable=intval($sock->GET_INFO("SQUIDEnable"));
    $EnableNginx=intval($sock->GET_INFO("EnableNginx"));
    if($SquidEnable==1){
        $tpl->table_form_field_text("{APP_SQUID}",$sock->GET_INFO("SquidRealVersion"),ico_server);
    }
    if($EnableNginx==1){
        $APP_NGINX_VERSION  = $sock->GET_INFO("APP_NGINX_VERSION");
        $tpl->table_form_field_text("{APP_NGINX}",$APP_NGINX_VERSION,ico_earth);
    }

    $Enablehacluster=intval($sock->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){
        $HAPROXY_VERSION  = $sock->GET_INFO("HAPROXY_VERSION");
        $tpl->table_form_field_text("HaCluster",$HAPROXY_VERSION,ico_sensor);
    }


    echo $tpl->table_form_compile();
    return true;
}
function agent_info_status():bool{
    $page=currentPageName();
    $id=intval($_GET["agent-info-status"]);
    $artica="";
    if(isset($_GET["artica"])){
        $artica="&artica=yes";
    }
    $tpl=new template_admin();
    echo "<div id='agent-info-status-$id'></div>";
    $js=$tpl->RefreshInterval_js("agent-info-status-$id",$page,"agent-info-status-popup=$id$artica",5);
    echo "<script>
     $('#agent-info-artica-$id').remove();
    $js</script>";
    return true;
}
function agent_address_popup():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-address-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    $form[]=$tpl->field_hidden("agent-address-popup",$id);
    $form[]=$tpl->field_text("address","{address}",$json->ip_address);
    $form[]=$tpl->field_numeric("port","{port}",$json->port);
    $form[]=$tpl->field_text("path_prefix","{path} (Reverse-proxy)",$json->path_prefix);
    $form[]=$tpl->field_checkbox("use_system_ca","{trust_reverse_proxy_CA}",$json->use_system_ca);





    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance3.close();");
    return true;
}
function agent_address_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=intval($_POST["agent-address-popup"]);
    $sock=new sockets();
    $sock->REST_API_POST("/netagents/connection/$id",
        array(
            "ip_address"=>trim($_POST["address"]),
            "port"=>intval($_POST["port"]),
            "path_prefix"=>trim($_POST["path_prefix"]),
            "use_system_ca"=>intval($_POST["use_system_ca"])
        )
    );
    $Hostname=getAgentHostname($id);
    return admin_tracks("Save Debian-agent $Hostname address to {$_POST["address"]}:{$_POST["port"]} {$_POST["path_prefix"]}");
}
function agent_info_status_popup():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-status-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    if(property_exists($json,"Status")) {
        if(!$json->Status){
            echo $tpl->div_error($json->Error);
            return true;
        }
    }


    $artica=false;
    if(isset($_GET["artica"])){
        $artica=true;
    }
    $Duration="-";
    if(property_exists($json,"last_seen")) {
        $s = preg_replace('/\.(\d{6})\d*(?=[+-]\d{2}:\d{2}$)/', '.$1', $json->last_seen);
        $offset = substr($s, -6);                 // +01:00
        $base = substr($s, 0, -6);              // 2025-... .micro
        $tz = new DateTimeZone($offset);
        $dt = new DateTime($base, $tz);
        $timestamp = $dt->getTimestamp();
        $Duration = distanceOfTimeInWords($timestamp, time(), true);
    }


    $tpl->table_form_field_text("{hostname}",$json->hostname,ico_server);
    $tpl->table_form_field_text("{description}","<small>$json->description</small>",ico_infoi);
    $gp=array();
    if(property_exists($json,"groups")) {
        foreach ($json->groups as $groupName=>$groupid){
            $gp[]=$tpl->td_href($groupName,"","Loadjs('fw.netagents.groups.php?gpid=$groupid')");
        }
    }
    if(count($gp)>0){
        $tpl->table_form_field_text("{groups}","<small>".@implode(",",$gp)."</small>",ico_group);
    }else{
        $tpl->table_form_field_bool("{groups}",0,ico_group);
    }

    $path_prefix=$json->path_prefix;
    $tpl->table_form_field_js("Loadjs('$page?agent-address-js=$id')");
    $tpl->table_form_field_text("{address}",$json->ip_address.":".$json->port.$path_prefix,ico_networks);
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{last_seen}",$Duration,ico_timeout);
    $tpl->table_form_field_text("{registered_at}",$json->registered_at,ico_timeout);
    $tpl->table_form_field_text("{agent_version}",$json->version,ico_infoi);


if($artica){
    $Version=AgentArticaVersion($id);
    $tpl->table_form_field_text("Artica",$Version,"ico ico-artica-25");
    $jsonWeb=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/webconsole/config/$id"));

    $topbuttons[] = array("Loadjs('$page?artica-web-restart-js=$id')", ico_retweet, "{restart}");
    if(property_exists($jsonWeb,"unix_socket")){
        $tpl->table_form_field_js("Loadjs('$page?articaweb-unix-off=$id')","",$topbuttons);
        $tpl->table_form_field_text("{webconsole}","Unix (<span style='text-transform: none'>$jsonWeb->unix_socket</span>)",ico_infoi);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?articaweb-unix=$id')","",$topbuttons);
        $tpl->table_form_field_text("{webconsole}",$jsonWeb->listen_addr.":".$jsonWeb->listen_port,ico_infoi);
    }

}

    $tpl->table_form_field_js("");
    $tpl->table_form_section("{certificate}");
    $tpl->table_form_field_text("{issuer}",$json->client_cert_info->issuer,ico_certificate);
    $tpl->table_form_field_text("{not_before}",$json->client_cert_info->not_before,ico_timeout);
    $tpl->table_form_field_text("{not_after}",$json->client_cert_info->not_after,ico_timeout);
    $tpl->table_form_field_text("{days_until_expiry}",$json->client_cert_info->days_until_expiry,ico_timeout);
    if(property_exists($json->client_cert_info,"dns_names")){
        if(is_array($json->client_cert_info->dns_names)) {
            $tpl->table_form_field_text("{dns_names}", @implode(",", $json->client_cert_info->dns_names), ico_server);
        }
    }
    echo $tpl->table_form_compile();
    return true;


}

function td_Aweb($agentJson):string{
    $tpl=new template_admin();
    $AgentRefreshing=AgentRefreshing($agentJson->id);

    if(strlen($AgentRefreshing)>1){
        $ico=ico_refresh_animate;
        return $tpl->_ENGINE_parse_body("<i class='$ico'></i>&nbsp;{{$AgentRefreshing}}");
    }

    if(!AgentArtica($agentJson->id)){
        echo "// td_Aweb -->  $agentJson->id -> Not Artica\n";
        return "";
    }
    $ip = htmlspecialchars($agentJson->ip_address);

    return $tpl->icon_browser("s_PopUpFull('/netagents/webconsole/proxy/$agentJson->id/fw.login.php',1024,768,'Artica Web console $ip')");
}
function td_status_all():bool{

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return false;
    }

    if(!isset($json->agents) || count($json->agents) == 0){
        return true;
    }
    $jsonMain=array();
    foreach($json->agents as $agent) {
        $id = intval($agent->id);
        $jsonMain[$id] = $agent;
    }

    $ids=explode("|",$_GET["td-all"]);
    foreach ($ids as $sid){
        $id=intval($sid);
        $hostname=base64_encode(td_hostname($jsonMain[$id]));
        $status=base64_encode(td_status($jsonMain[$id]));
        list($td_cpu,$jsPeity)=td_cpu($jsonMain[$id]);
        $td_memory=base64_encode(td_memory($jsonMain[$id]));
        $td_uptime=td_uptime($jsonMain[$id]);
        $type=base64_encode(td_type($jsonMain[$id]));
        $webConsole=base64_encode(td_Aweb($jsonMain[$id]));
        $td_disk=base64_encode(td_disk($jsonMain[$id]));
        $js[]="// ".__LINE__.": $id";
        $td_cpu=base64_encode($td_cpu);

        $js[]="$('#agent-$id-type').html( base64_decode('$type') );";
        $js[]="$('#agent-$id-hostname').html( base64_decode('$hostname') );";
        $js[]="$('#agent-$id-status').html( base64_decode('$status') );";
        $js[]="$('#agent-$id-cpu').html(base64_decode('$td_cpu'));";
        $js[]="$('#agent-$id-disk').html(base64_decode('$td_disk'));";
        $js[]="$('#agent-$id-memory').html(base64_decode('$td_memory'));";
        $js[]="$('#agent-$id-uptime').html('$td_uptime');";
        $js[]="$('#agent-$id-web').html(base64_decode('$webConsole'));";
        if(strlen($jsPeity)>1){
            $js[]=$jsPeity;
        }
    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
    return true;
}

function td_status($agentJson):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $status = $agentJson->status;
    $id=$agentJson->id;
    $status_class = "label-default";
    if($status == "online"){
        $status_class = "label-primary";
        return $tpl->_ENGINE_parse_body("<span class='label $status_class'>{{$status}}</span>");
    }
    if($status == "offline"){
        $status_class = "label-danger";
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/export/$agentJson->id"));
        if(!property_exists($json,"client_cert")){
            $status_class = "label-default";
            return $tpl->_ENGINE_parse_body($tpl->td_href("<span class='label $status_class'>{enroll}</span>",
                "","Loadjs('$page?agent-enroll=$id')"));
        }
        return $tpl->_ENGINE_parse_body("<span class='label $status_class'>{{$status}}</span>");
    }
    if($status == "pending"){
        $status_class = "label-warning";
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/export/$agentJson->id"));
        if(!property_exists($json,"client_cert")){
            $status_class = "label-default";
            return $tpl->_ENGINE_parse_body($tpl->td_href("<span class='label $status_class'>{enroll}</span>",
                "","Loadjs('$page?agent-enroll=$id')"));
        }
        return $tpl->_ENGINE_parse_body("<span class='label $status_class'>{{$status}}</span>");
    }
    return "??";

}
function AgenSoft($id){
    if(isset($GLOBALS["AGENTSOFT"][$id])){
        if(!is_object($GLOBALS["AGENTSOFT"][$id])){
            return json_encode(array());
        }
        return $GLOBALS["AGENTSOFT"][$id];
    }
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/$id"));
    if(!is_object($json)){
        return json_encode(array());
    }
    $GLOBALS["AGENTSOFT"][$id]=$json;
    return $GLOBALS["AGENTSOFT"][$id];
}
function AgentRefreshing($id):string{
    $json=AgenSoft($id);
    if(!is_object($json)){
        return "";
    }
    if(property_exists($json,"refreshing")){
        if($json->refreshing){
            return $json->status;
        }
    }
    return "";
}
function AgentArtica($id):bool{
    $json=AgenSoft($id);
    if(!is_object($json)){
        return false;
    }

    if(!property_exists($json,"artica_version") ){
        return false;
    }
    if(strlen($json->artica_version)>3){
        return true;
    }
    return false;
}
function AgentArticaVersion($id):string{
    $json=AgenSoft($id);
    if(!property_exists($json,"artica_version")){
        return "";
    }
    if(strlen($json->artica_version)<3){
        return "";
    }
    $f[]=$json->artica_version;
    if(intval($json->artica_service_pack)>0){
        $f[]="Service Pack $json->artica_service_pack";
    }
    if(strlen($json->artica_hotfix_version)>3){
        $f[]="Hotfix $json->artica_hotfix_version";
    }
    return @implode("&nbsp;",$f);
}
function td_type($agentJson):string{

    $ico="fab fa-linux";
    if(AgentArtica($agentJson->id)){
        $ico="ico ico-artica";
    }
    return "<i class='$ico'></i>";
}
function td_agent_version($agentJson):string{
    $id = $agentJson->id;
    $main=agent_status($id);

    if(!isset($main["AgentVer"])){
        echo "// AgentVer not found\n";
        return "";
    }

    $version=$main["AgentVer"];
    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $page=CurrentPageName();
    echo "// $CurVer / $version\n";
    if (version_compare($CurVer, $version, '>')){
        $tpl=new template_admin();
        return $tpl->td_href("<div style='text-align:right'><span class='label label-warning'>v$version &raquo; v$CurVer</span>","","Loadjs('$page?agents-upgrade-js=$id')");
    }
    return "";

}
function td_cpu($agentJson):array{
    $id = $agentJson->id;
    $main=agent_status($id);
    $html[]="<div id='td-cpu-clickable-$id'>".$main["cpu"]."%</div>";
    $dashjs="";
    echo "// [".__LINE__."]: Peity: ".count($main["peity"])."\n";
    if(count($main["peity"])>0){
        $peity_conf="{ width:110,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
        $html[]="<div id=\"peity-cpu-line-$id\">".@implode(",",$main["peity"])."</div>";
        $dashjs="\t$(\"#peity-cpu-line-$id\").peity(\"line\",$peity_conf);
        $('#td-cpu-clickable-$id').click(function () { Loadjs('fw.netagents.metrics.charts.php?agent-metrics-js=$id') } );";
    }
    return array(implode("",$html)."</div>",$dashjs);
}
function td_cpu_graphs($id):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/cpu-history/agent/$id?limit=60"),true);
    if(!isset($json["records"])){
        echo "// [".__LINE__."]: records: not found...\n";
        return array();
    }
    $f=array();
    echo "// [".__LINE__."]: records: ".count($json["records"])." elements..\n";


    foreach ($json["records"] as $record){
            $percentage=round($record["percentage"],2);
            $f[]=$percentage;

    }

 return $f;
}

function td_disk($agentJson):string{
    $main=agent_status($agentJson->id);
    if(!isset($main["HD"]["percent_int"])){
        return "-";
    }
    $disk= "<span class='text-muted'>{$main["HD"]["percent"]}</span>";

    if($main["HD"]["percent_int"]>70){
        $disk= "<span style='font-weight:bold;color:black'>{$main["HD"]["percent"]}</span>";
    }

    if($main["HD"]["percent_int"]>80){
        $disk= "<span class='text-warning' style='font-weight:bold'>{$main["HD"]["percent"]}</span>";
    }
    if($main["HD"]["percent_int"]>90){
        $disk= "<span class='text-danger' style='font-weight:bold'>{$main["HD"]["percent"]}</span>";
    }
    return $disk;
}
function td_uptime($agentJson):string{
    $id = $agentJson->id;
    $main=agent_status($id);
    return $main["uptime"];
}
function td_memory($agentJson):string{
    $id = $agentJson->id;
    $main=agent_status($id);
    $mem=$main["mem_pct"];
    $memint=0;
    if(isset($main["mem_int"])) {
        $memint = $main["mem_int"];
    }
    $memStr= "<span class='text-muted'>$mem</span>";

    if($memint>70){
        $memStr= "<span style='font-weight:bold;color:black'>$mem</span>";
    }

    if($memint>80){
        $memStr= "<span class='text-warning' style='font-weight:bold'>$mem</span>";
    }
    if($memint>90){
        $memStr= "<span class='text-danger' style='font-weight:bold'>$mem</span>";
    }

    return $memStr;

}
function td_hostname($agentJson):string{
    $id = $agentJson->id;
    $page=CurrentPageName();
    $ip = htmlspecialchars($agentJson->ip_address);
    $port = intval($agentJson->port);
    $tpl=new template_admin();
    $main=agent_status($id);
    $agentver="";$description="";
    if(AgentArtica($id)){
        $agentver="<br><small>Artica: ".AgentArticaVersion($id)."</small>";
    }
    if(strlen(trim($agentJson->description))>2){
        $description=urldecode($agentJson->description);
        $description="<br><small>$description</small>";
    }
    $ver=td_agent_version($agentJson);

    // return array("hostname"=>"-","uptime"=>0,"cpu"=>0,"mem_pct"=>0);
    if(isset($main["hostname"])){
        return $tpl->td_href("<strong>{$main["hostname"]}</strong>",null,"Loadjs('$page?agent-info-js=$id')")."&nbsp;(<i>$ip:$port</i>)$description$agentver$ver";
    }
    return $tpl->td_href("$ip:$port",null,"Loadjs('$page?agent-info-js=$id')")."$description$agentver$ver";
}

function agent_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{add_agent}","$page?agent-add-popup=yes&function=$function",550);
}

function agent_add_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $jsafter="dialogInstance2.close();$function();";
    $form[]=$tpl->field_text("netagent-hostname","{hostname}","");
    $form[]=$tpl->field_text("netagent-ipaddress","{ipaddr}","");
    $form[]=$tpl->field_numeric("netagent-port","{listen_port}",28811);
    $form[]=$tpl->field_text("netagent-token","{enrollment_token}","");
    $form[]=$tpl->field_text("netagent-description","{description}","");
    echo $tpl->form_outside("",$form,"{enrollment_token_explain}","{add}",$jsafter,"AsSystemAdministrator");
    return true;
}

function agent_add_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $data = array(
        "hostname" => trim($_POST["netagent-hostname"]),
        "ip_address" => trim($_POST["netagent-ipaddress"]),
        "port" => intval($_POST["netagent-port"]),
        "enrollment_token" => trim($_POST["netagent-token"]),
        "description" => trim($_POST["netagent-description"])
    );

    if(empty($data["hostname"]) || empty($data["ip_address"])){
        echo $tpl->post_error("{hostname_and_ip_required}");
        return false;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/add", $data));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
        echo $tpl->post_error("Err.".__LINE__.":".$json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return false;
    }
    if(!isset($json->agents) || count($json->agents) == 0){
        return true;
    }


    foreach($json->agents as $agent){
        $ip = htmlspecialchars($agent->ip_address);
        $port = intval($agent->port);
        if($ip==$_POST["netagent-ipaddress"] && $port==intval($_POST["netagent-port"])){
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/enroll/$agent->id");
        }
    }
    return true;
}



function agent_delete():bool{
    $id = intval($_POST["agent-delete"]);
    $ArticaNetAgents=new ArticaNetAgents($id);
    $hostname=$ArticaNetAgents->GetAgentHostname();
    if(strlen($hostname) < 1){
        $hostname="#$id";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/delete/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo json_last_error_msg();
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
       echo $json->Error;
        return false;
    }
    return admin_tracks("Remove Debian-agent: $hostname");

}



function agent_status($id):array{

    if(isset($GLOBALS["AGENT_STATUS"][$id]) && is_array($GLOBALS["AGENT_STATUS"][$id])){
        return $GLOBALS["AGENT_STATUS"][$id];
    }

    $Main=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"),true);
    if (json_last_error() > JSON_ERROR_NONE) {
        VERBOSE(json_last_error_msg(),__LINE__);
        return array("hostname"=>"Unknown","uptime"=>0,"cpu"=>0,"mem_pct"=>0,"HD"=>array(),"AgentVer"=>"0.0.0","peity"=>array());
    }

    if(isset($Main["Error"]) && !empty($Main["Error"])){
        VERBOSE($Main["Error"],__LINE__);
        return array("hostname"=>"Unknown","uptime"=>0,"cpu"=>0,"mem_pct"=>0,"HD"=>array(),
            "AgentVer"=>"0.0.0","peity"=>array());
    }



    /*object(stdClass)#5 (8) {
        ["hostname"]=> string(9) "appliance"
        ["time"]=> string(30) "2025-12-28T23:05:58.561639299Z"
        ["uptime"]=> string(7) "19h 42m"
        ["cpu_load"]=> object(stdClass)#13 (3) {
            ["load_1"]=> float(0.05)
            ["load_5"]=> float(0.03)
            ["load_15"]=> int(0) }
            ["memory"]=> object(stdClass)#12 (4) {
                ["total_bytes"]=> int(2068910080)
                ["used_bytes"]=> int(325074944)
                ["available_bytes"]=> int(1743835136)
                ["used_percent"]=> float(15.7123766346)
        }
        ["disk"]=> array(4) {
            [0]=> object(stdClass)#11 (6) {
                ["mount_point"]=> string(1) "/"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692)
            }
            [1]=> object(stdClass)#10 (6) {
                ["mount_point"]=> string(21) "/var/lib/debian-agent"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692) }
            [2]=> object(stdClass)#9 (6) {
                ["mount_point"]=> string(21) "/var/log/debian-agent"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692)
            }
            [3]=> object(stdClass)#8 (6) {
                ["mount_point"]=> string(8) "/var/tmp"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692) }
        } ["network"]=> array(1) {
            [0]=> object(stdClass)#7 (5) {
                ["interface"]=> string(6) "ens192"
                ["bytes_sent"]=> int(2490454)
                ["bytes_recv"]=> int(184754725)
                ["packets_sent"]=> int(27389)
                ["packets_recv"]=> int(599794) }
            }
            ["agent_health"]=> object(stdClass)#6 (5) {
            ["status"]=> string(7) "healthy"
            ["version"]=> string(5) "1.0.0"
            ["start_time"]=> string(30) "2025-12-28T19:04:58.462865872Z"
            ["certificate_count"]=> int(1)
            ["token_count"]=> int(0) }
    }*/
    $hostname="Unknown";
    $uptime="-";
    if(isset($Main["hostname"])){
        $hostname=$Main["hostname"];
    }
    $version="0.0.0";

    if(isset($Main["agent_health"]["version"])){
        $version=$Main["agent_health"]["version"];
    }

    if(isset($Main["uptime"])){
        $uptime=$Main["uptime"];
    }
    $cpu="-";
    $mem_pct="-";
    $memint=0;
    if(isset($Main["cpu_load"])){
        $cpu= sprintf("%.2f / %.2f / %.2f", $Main["cpu_load"]["load_1"], $Main["cpu_load"]["load_5"], $Main["cpu_load"]["load_15"]);
    }
    if(isset($Main["memory"])){
        $memint=intval($Main["memory"]["used_percent"]);
        $mem_pct= sprintf("%.1f%%", $Main["memory"]["used_percent"]);
    }

    $peity=td_cpu_graphs($id);

    $HD=array();
    $AllsHD=array();
    if(isset($Main["disk"])){
        foreach ($Main["disk"] as $disk){
            $device=$disk["device"];
            $mount_point=$disk["mount_point"];
            if($mount_point=="/"){
                $HD["device"]=$device;
                $HD["percent_int"]=intval($disk["used_percent"]);
                $HD["percent"]=sprintf("%.1f%%", $disk["used_percent"]);
            }
            $AllsHD[$device]["total_bytes"]=intval($disk["total_bytes"]);
            $AllsHD[$device]["used_bytes"]=intval($disk["used_bytes"]);
        }
    }


    $GLOBALS["AGENT_STATUS"][$id]=array("hostname"=>$hostname,
        "uptime"=>$uptime,"cpu"=>$cpu,"mem_pct"=>$mem_pct,"mem_int"=>$memint,"HD"=>$HD,"ALLHD"=>$AllsHD,
        "AgentVer"=>$version,"peity"=>$peity);
    return $GLOBALS["AGENT_STATUS"][$id];


}

function agent_ping():bool{
    $tpl=new template_admin();
    $id = intval($_GET["agent-ping"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/ping/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Status) && $json->Status){
        echo $tpl->div_ok("{agent_online}");
    }else{
        echo $tpl->div_warning("{agent_offline}");
    }
    return true;
}
function agent_events_head():bool{
    $tpl=new template_admin();
    $id = intval($_GET["agent-events-head"]);
    $page=CurrentPageName();
    echo $tpl->search_block($page,"","","","&agent-events-search=$id");
    return true;
}
function agent_events_search():bool{
    $tpl=new template_admin();
    $id = intval($_GET["agent-events-search"]);

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $MAX=intval($search["MAX"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/events/$id?limit=$MAX"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(!is_array($json) || count($json) == 0){
        echo $tpl->div_warning("{no_events}");
        return true;
    }
    $search["S"]=str_replace("*",".*?",$search["S"]);
    $f=array();
    $f[]="<table class='table table-condensed'>";
    $f[]="<thead><tr><th>{date}</th><th>{type}</th><th>{details}</th></tr></thead>";
    $f[]="<tbody>";
    foreach($json as $event){
        $date = isset($event->created_at) ? date("Y-m-d H:i:s", strtotime($event->created_at)) : "-";
        $type = isset($event->type) ? htmlspecialchars($event->type) : "-";
        $data = isset($event->data) ? htmlspecialchars($event->data) : "-";
        if(!preg_match("#{$search["S"]}#i",$data)){
            continue;
        }
        $f[]="<tr><td style='width:1%' nowrap>$date</td><td style='width:1%' nowrap>$type</td><td>$data</td></tr>";
    }
    $f[]="</tbody></table>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function events_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id = intval($_GET["agent-events-js"]);
    return $tpl->js_dialog2("{events}","$page?agent-events-head=$id",950);
}
function agent_token_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id = intval($_GET["agent-token-js"]);
    return $tpl->js_dialog3("{set_token}","$page?agent-token-popup=$id",550);
}

function agent_token_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id = intval($_GET["agent-token-popup"]);
    $jsafter="dialogInstance3.close();";
    echo $tpl->BigTextField("netagent-token-value|netagent-save-token:$id",
        "{enrollment_token}","{enrollment_token_explain}",
        "",$jsafter,null,null,"AsSystemAdministrator");
    return true;
}

function agent_token_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id = intval($_POST["netagent-save-token"]);
    $token = trim($_POST["netagent-token-value"]);

    if(empty($token)){
        echo $tpl->post_error("{token_required}");
        return false;
    }

    $data = array(
        "id" => $id,
        "token" => $token
    );

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/settoken", $data));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
        echo $tpl->post_error("Err.".__LINE__.":".$json->Error);
        return false;
    }

    return true;
}

function check_all_agents():bool{
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/checkall"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Status) && $json->Status){
        echo $tpl->div_ok("{check_started}");
    }else{
        echo $tpl->div_warning(isset($json->Error) ? $json->Error : "{unknown_error}");
    }
    return true;
}

function agent_enroll():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id = intval($_GET["agent-enroll"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/enroll/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Status) && $json->Status){
        echo $tpl->div_ok("{enrollment_success}");
        echo "<script>setTimeout(function(){LoadAjax('agents-table-container','$page?agents-table=yes');},2000);</script>";
    }else{
        echo $tpl->div_warning("{enrollment_failed}: ".(isset($json->Error) ? $json->Error : "{unknown_error}"));
    }
    return true;
}
function agents_upgrade_popup():bool{
    $page = CurrentPageName();
    $id=intval($_GET["agents-upgrade-popup"]);
    echo "<div id='agents_upgrade_popup'></div><script>LoadAjaxSilent('agents_upgrade_popup','$page?agents-upgrade-api=$id')</script>";
    return true;
    }
function agents_upgrade_api():bool{
    $tpl = new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();

    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $preselectedAgent = isset($_GET["agents-upgrade-api"]) ? intval($_GET["agents-upgrade-api"]) : 0;
    $agentsJson = json_decode($sock->REST_API("/netagents/list?status=online&enabled=1"));
    $f = array();
    $topbuttons[] = array("LoadAjaxSilent('agents_upgrade_popup','$page?agents-upgrade-ssh={$_GET["agents-upgrade-api"]}')", ico_terminal, "{use_ssh}");

    $f[]="<div style='margin-top:10px;margin-bottom:10px;'>";
    $f[]=$tpl->th_buttons($topbuttons);
    $f[]="</div>";
    $f[] = "<div class='form-group'>";

    $f[] = "<label>{select_agents} &raquo;&raquo; v$CurVer &raquo;&raquo; {use_API}</label>";
    $f[] = "<div style='max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;'>";

    if (isset($agentsJson->agents) && is_array($agentsJson->agents)) {
        foreach ($agentsJson->agents as $agent) {
            $version=$agent->version;

            if (!version_compare($CurVer, $version, '>')){
                continue;
            }

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
    $f[] = "<button class='btn btn-primary btn-lg' onclick=\"DoPushUpdateAG();\">";
    $f[] = "        <i class='fas fa-upload'></i> {push_update}";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div id='push-error' style='margin-top:10px'></div>";
    $f[] = "<div id='agntupgrpush-result' style='margin-top:40px;border-top:1px solid #cccccc;padding-top:5px'></div>";

    $js=$tpl->RefreshInterval_js("agntupgrpush-result",$page,"agent-upgrade-result=yes");
    $f[] = "<script>";
    $f[] = "function DoPushUpdateAG() {";
    $f[] = "  var agentIds = [];";
    $f[] = "  $('.agent-checkbox:checked').each(function(){ agentIds.push($(this).val()); });";
    $f[] = "  if (agentIds.length == 0) { alert('Please select at least one agent'); return; }";
    $f[] = "  $.post('$page', {";
    $f[] = "    'agent-upgrade-perform': 1,";
    $f[] = "    'agent_ids': agentIds.join(',')";
    $f[] = "  }, function(data) {";
    $f[] = "    $('#agntupgrpush-result').html(data);";
    $f[] = "  });";
    $f[] = "}";
    $f[]=$js;
    $f[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function agents_upgrade_ssh():bool{
    $tpl = new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();

    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $preselectedAgent = isset($_GET["agents-upgrade-ssh"]) ? intval($_GET["agents-upgrade-ssh"]) : 0;
    VERBOSE("/netagents/list?enabled=1",__LINE__);
    $agentsJson = json_decode($sock->REST_API("/netagents/list?enabled=1"),true);

    $f = array();
    $topbuttons[] = array("LoadAjaxSilent('agents_upgrade_popup','$page?agents-upgrade-api=$preselectedAgent')", ico_terminal, "{use_API}");

    $f[]="<div style='margin-top:10px;margin-bottom:10px;'>";
    $f[]=$tpl->th_buttons($topbuttons);
    $f[]="</div>";
    $f[] = "<div class='form-group'>";

    $f[] = "<label>{select_agents} &raquo;&raquo; v$CurVer &raquo;&raquo; {use_ssh}</label>";
    $f[] = "<div style='max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;'>";

    if (isset($agentsJson["agents"]) && is_array($agentsJson["agents"])) {
        foreach ($agentsJson["agents"] as $agent) {


            $checked = ($preselectedAgent == $agent["id"]) ? "checked" : "";
            $f[] = "<div class='checkbox'>";
            $f[] = "<label>";
            $f[] = "<input type='checkbox' class='agent-checkbox' value='{$agent["id"]}' $checked>";
            $f[] = " {$agent["hostname"]} ({$agent["ip_address"]})";
            $f[] = "</label>";
            $f[] = "</div>";
        }
    }

    $f[] = "</div>";
    $f[] = "</div>";
    $f[] = "<table style='width:100%;'>";
    $f[]="<tr>";
    $f[]="<td style='width:47%'>{username}:<input type='text' id='ssh-user' class='form-control' placeholder='{username}'></td>";
    $f[]="<td style='padding-left:5px;width:47%'>{password}:<input id='ssh-password' type='password' class='form-control' placeholder='{password}'></td>";
    $f[]="<td style='padding-left:5px;width:5%' nowrap>{ssh_port}:<input id='ssh-port' type='text' class='form-control' placeholder='{ssh_port}' value='22'></td>";
    $f[]="</tr>";
    $f[]="</table>";

    $f[] = "<div style='width:100%;padding-right:10px;text-align:right;margin-top:10px'>";
    $f[] = "<button class='btn btn-primary btn-lg' onclick=\"DoPushUpdateAGSSH();\">";
    $f[] = "        <i class='fas fa-upload'></i> {push_update} ( {use_ssh} )";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div id='push-error' style='margin-top:10px'></div>";
    $f[] = "<div id='agntupgrpushssh-result' style='margin-top:40px;border-top:1px solid #cccccc;padding-top:5px'></div>";

    $js=$tpl->RefreshInterval_js("agntupgrpushssh-result",$page,"agent-upgrade-sshresult=yes");
    $f[] = "<script>";
    $f[] = "function DoPushUpdateAGSSH() {";
    $f[] = "  var agentIds = [];";
    $f[] = "  $('.agent-checkbox:checked').each(function(){ agentIds.push($(this).val()); });";
    $f[] = "  if (agentIds.length == 0) { alert('Please select at least one agent'); return; }";
    $f[] = "  $.post('$page', {";
    $f[] = "    'agent-upgrade-ssh': 1,";
    $f[] = "    'agent_ids': agentIds.join(','),";
    $f[] = "    'username': $('#ssh-user').val(),";
    $f[] = "    'password': $('#ssh-password').val(),";
    $f[] = "    'port': $('#ssh-port').val()";
    $f[] = "  }, function(data) {";
    $f[] = "    $('#agntupgrpushssh-result').html(data);";
    $f[] = "  });";
    $f[] = "}";
    $f[]=$js;
    $f[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function agents_upgrade_ssh_perform():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $agent_ids=explode(",",$_POST['agent_ids']);
    $username=$_POST['username'];
    $password=$_POST['password'];
    $port=$_POST['port'];


    foreach ($agent_ids as $agent_id) {
        $ArticaNetAgents=new ArticaNetAgents($agent_id);
        $logs[]=$ArticaNetAgents->GetAgentHostname();
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/upgrade-ssh/single/$agent_id",
        array("username"=>$username,"password"=>$password,"ssh_port"=>$port));

    }
    return admin_tracks("Upgrade Debian agents via SSH on ".@implode(", ",$logs));
}

function agents_upgrade_perform():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $agentIds = isset($_POST["agent_ids"]) ? trim($_POST["agent_ids"]) : "";
    $tb=explode(",",$agentIds);
    foreach ($tb as $agentId) {
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/upgrade/$agentId",array()));
        if(!property_exists($json,"upgrade_id")){
          echo "<script>".$tpl->js_error("Unknown error");
          echo "</script>";
        }
        echo "<div class='alert alert-success' role='alert'>$json->upgrade_id</div>";
    }

    return admin_tracks("Send order to update Debian agent to latest version to ids:$agentIds");
}
function agents_upgrade_ssh_results():bool{
    $tpl=new template_admin();
    $sock=new sockets();

    $json=json_decode($sock->REST_API("/netagents/upgrade-ssh/list"),true);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }
    if(isset($json["Status"])){
        if(!$json["Status"]){
            if(strlen($json["Error"])>1){
                echo $tpl->div_error($json["Error"]);
                return true;
            }
        }
    }

    $f=array();
    $f[]="<table class='table table-striped table-hover'>";
    $f[]="<thead>";
    $f[]="<tr>";
    $f[]="  <th>{status}</th>";
    $f[]="  <th>{progress}</th>";
    $f[]="  <th>{date}</th>";
    $f[]="</tr>";
    $f[]="</thead>";
    $f[]="<tbody>";
    $ArticaNetAgents=new ArticaNetAgents();

    foreach ($json["single_upgrades"] as $op) {
        $id = isset($op["id"]) ? $op["id"] : "";
        $status = isset($op["state"]) ?$op["state"] : "unknown";
        $startedAt = isset($op["started_at"]) ? $op["started_at"] : "";
        $completed_at= isset($op["completed_at"]) ? $op["completed_at"] : "";
        $error = isset($op["error"]) ? $op["error"] : "";
        $progress = isset($op["progress"]) ? $op["progress"] : "";
        $hostname= isset($op["hostname"]) ? $op["hostname"] : "";

        $message=isset($op["message"]) ? $op["message"] : "";

        // Format started time
        if (!empty($startedAt)) {
            $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($startedAt), true);
        }
        $message="<div class='text-info'>$message</div>";

        $statusBadge = $ArticaNetAgents->get_status_badge($status);
        $progressBar =  $ArticaNetAgents->get_progress_bar(intval($progress), $status);
        if(strlen($error)>2){
            $message="<div class='text-danger'>$hostname: $error</div>";
        }
        if($progress>99){
            $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($completed_at), true);

        }

        $f[] = "<tr>";
        $f[] = "  <td style='width: 1%;vertical-align:top' nowrap>$statusBadge</td>";
        $f[] = "  <td style='min-width:150px;'>$progressBar$message</td>";
        $f[] = "  <td style='width: 1%;vertical-align:top' nowrap>$startedAt</td>";
        $f[] = "</tr>";
    }


    $f[]="</tbody>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;


}
function agents_upgrade_results():bool{
    $tpl=new template_admin();
    $sock=new sockets();

    $json=json_decode($sock->REST_API("/netagents/upgrade/list"),true);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }


    $f=array();
    $f[]="<table class='table table-striped table-hover'>";
    $f[]="<thead>";
    $f[]="<tr>";
    $f[]="  <th>{status}</th>";
    $f[]="  <th>{progress}</th>";
    $f[]="  <th>{date}</th>";
    $f[]="</tr>";
    $f[]="</thead>";
    $f[]="<tbody>";
    $ArticaNetAgents=new ArticaNetAgents();
    foreach ($json as $op) {
            $id = isset($op["id"]) ? $op["id"] : "";
            $status = isset($op["state"]) ?$op["state"] : "unknown";
            $startedAt = isset($op["started_at"]) ? $op["started_at"] : "";
            $error = isset($op["error"]) ? $op["error"] : "";
            $progress = isset($op["progress"]) ? $op["progress"] : "";
            $hostname= isset($op["hostname"]) ? $op["hostname"] : "";
            $completed_at= isset($op["completed_at"]) ? $op["completed_at"] : "";

            // Format started time
            if (!empty($startedAt)) {
                $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($startedAt), true);
            }

          $statusBadge = $ArticaNetAgents->get_status_badge($status);
          $progressBar =  $ArticaNetAgents->get_progress_bar(intval($progress), $status);
            if(strlen($error)>2){
                $error="<div class='text-danger'>$hostname: $error</div>";
            }
            if(strlen($progressBar)>99){
                $startedAt=$tpl->time_to_date($tpl->GoToTimestamp($completed_at), true);
            }

            $f[] = "<tr>";
            $f[] = "  <td style='width: 1%' nowrap>$statusBadge</td>";
            $f[] = "  <td style='min-width:150px;'>$progressBar$error</td>";
            $f[] = "  <td style='width: 1%' nowrap>$startedAt</td>";
            $f[] = "</tr>";
        }


    $f[]="</tbody>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}