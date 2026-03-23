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
if(isset($_GET["reboot-js"])){reboot_js();exit;}
if(isset($_POST["reboot"])){reboot_confirm();exit;}
if(isset($_GET["group-select-js"])){group_select_js();exit;}
if(isset($_GET["group-select-popup"])){group_select_popup();exit;}
if(isset($_GET["group-selected"])){group_selected();exit;}
if(isset($_GET["blacklisted-js"])){blacklisted_js();exit;}
if(isset($_GET["comp-view-js"])){comp_view_js();exit;}
if(isset($_GET["apt-get-update-js"])){agent_action_get_update_js();exit;}
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
if(isset($_GET["agent-info-net"])){agent_info_net();exit;}
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
    if(is_object($json) && property_exists($json,"Error")) {
        return $tpl->js_error($json->Error);
    }
    return $tpl->js_error("Unknown error");
}
function agents_upgrade_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=0;
    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $id=intval($_GET["agents-upgrade-js"]);
    $agentver="";

    $gid=0;
    if(isset($_GET["gpid"])){
        $gid=intval($_GET["gpid"]);
    }
    if(isset($_GET["agentver"])){
        $agentver="&agentver=".urlencode($_GET["agentver"]);
    }

    return $tpl->js_dialog5("{update_agent} v$CurVer","$page?agents-upgrade-popup=$id&gpid=$gid$agentver",650);
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
function comp_view_js(){

    $_COOKIE["META_COMP_VIEW"]=intval($_GET["comp-view-js"]);
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "Set_Cookie('META_COMP_VIEW', {$_GET["comp-view-js"]}, '3600', '/', '', '');\n";
    echo "$function();";
}
function agents_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    if(!isset($_COOKIE["META_COMP_GROUP"])){
        $_COOKIE["META_COMP_GROUP"]=0;
    }



    if($_COOKIE["META_COMP_GROUP"]==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"));
    }else{
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/group/{$_COOKIE["META_COMP_GROUP"]}/list"));
    }
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(!isset($_COOKIE["META_COMP_VIEW"])){
        $_COOKIE["META_COMP_VIEW"]=0;
    }

    $META_COMP_VIEW=intval($_COOKIE["META_COMP_VIEW"]);
    $META_COMP_GROUP=intval($_COOKIE["META_COMP_GROUP"]);

    if(!isset($json->agents) || count($json->agents) == 0){
        $btn[]="<div style='margin:20px;text-align:right'>";
        $btn[]=$tpl->button_autnonome("wiki.articatech.com", "s_PopUpFull('https://wiki.articatech.com/artica-meta/deploy-agents','1024','900');", ico_support,
            "AsArticaMetaAdmin",335,"btn-warning");
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
    $search="";
    if(isset($_GET["search"])) {
        $search = $_GET["search"];
    }

    $f=array();
    $f[]="<table class='table table-striped table-hover' id='table-agents'>";
    $f[]="<thead><tr>";
    if($META_COMP_VIEW==0) {
        $f[]="  <th id='th-agent-type'></th>";
        $f[] = "  <th id='th-agent-status'>{status}</th>";
    }
    $f[]="  <th id='th-agent-mode'>{type}</th>";
    $f[]="  <th id='th-agent-hostname'>{hostname}</th>";
    if($META_COMP_VIEW==0) {
        $f[] = "  <th nowrap id='th-agent-lic'></th>";
    }
    $f[]="  <th nowrap id='th-agent-prodlogs'></th>";
    $f[]="  <th nowrap id='th-agent-logs'></th>";
    $f[]="  <th nowrap id='th-agent-services'></th>";
    $f[]="  <th nowrap id='th-agent-ux'></th>";
    if($META_COMP_VIEW==0) {
        $f[] = "  <th nowrap id='th-agent-os'></th>";
    }
    $f[]="  <th nowrap id='th-agent-apt'></th>";
    $f[]="  <th nowrap id='th-agent-web'></th>";
    $f[]="  <th nowrap id='th-agent-cpu'>{load}/{cpu}</th>";

    if($META_COMP_VIEW==0) {
        $f[] = "  <th nowrap id='th-agent-memory'>{memory}</th>";
        $f[] = "  <th nowrap id='th-agent-disk'>&nbsp;</th>";
        $f[] = "  <th nowrap id='th-agent-uptime'>UP</th>";
        $f[] = "  <th nowrap id='th-agent-lastseen'>{last_seen}</th>";
        $f[] = "  <th nowrap id='th-agent-flush'></th>";
        $f[] = "  <th nowrap id='th-agent-logs'></th>";
        $f[] = "  <th nowrap id='th-agent-token'></th>";

    }
    $f[] = "  <th id='th-delete'></th>";
    $f[]="</tr></thead>";
    $f[]="<tbody>";
    $wd1="style='width:1%' nowrap";

    foreach($json->agents as $agent){
        $id = $agent->id;
        $last_seen="-";

        if(strlen($search)>1){
            if(!preg_match("#$search#i",serialize($agent))){
                continue;
            }
        }

        if(isset($agent->last_seen)){
            if($agent->last_seen != "0001-01-01T00:00:00Z"){
                $date = preg_replace('/\.(\d{6})\d+/', '.$1', $agent->last_seen);
                $dt = new DateTime($date);
                $timestamp = $dt->getTimestamp();
                $last_seen=$tpl->time_to_date($timestamp,$date);
            }
        }
        $DebianOS="";
        if(is_object($agent) && property_exists($agent,"debian_version")){
            $debian_version=intval($agent->debian_version);
            if($debian_version>0){
                $DebianOS="Debian <strong>$debian_version</strong>";
            }
        }


        // Check if agent needs enrollment (pending or missing certs)
        //$needs_enroll = ($status == "pending");

        $Agents[]=$id;
        $tr_id="tr-agent-$id";
        $f[]="<tr id='$tr_id'>";
        if($META_COMP_VIEW==0) {
            $f[] = "  <td $wd1><span id='agent-$id-type'></span></td>";
            $f[] = "  <td $wd1><span id='agent-$id-status'></span></td>";
        }
        $f[]="  <td $wd1><span id='agent-$id-mode'></span></td>";
        $f[]="  <td style='width:99%'><span id='agent-$id-hostname'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-lic'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-prodlogs'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-logs'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-services'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-ux'></span></td>";
        if($META_COMP_VIEW==0) {
            $f[] = "  <td $wd1><span id='agent-$id-os'>$DebianOS</span></td>";
        }
        $f[]="  <td $wd1><span id='agent-$id-apt'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-web'></span></td>";
        $f[]="  <td $wd1><span id='agent-$id-cpu'></span></td>";
        if($META_COMP_VIEW==0) {

            $f[] = "  <td $wd1><span id='agent-$id-memory'></span></td>";
            $f[] = "  <td $wd1><span id='agent-$id-disk'></span></td>";
            $f[] = "  <td $wd1><span id='agent-$id-uptime'></span></td>";
            $f[] = "  <td $wd1>$last_seen</td>";
            $f[] = "  <td $wd1>" . $tpl->icon_refresh("Loadjs('$page?agent-flush-js=$id')","{agent_flush}") . "</td>";
            $f[] = "  <td $wd1>" . $tpl->icon_list("Loadjs('$page?agent-events-js=$id')","AsArticaMetaAdmin","{events}: <strong>Meta server</strong>") . "</td>";
            $f[] = "  <td $wd1>" . $tpl->icon_user_lock("Loadjs('$page?agent-token-js=$id');","AsArticaMetaAdmin","{enroll}") . "</td>";
        }
        $f[]="  <td $wd1>". $tpl->icon_delete("Loadjs('$page?agent-delete-js=$id&md=$tr_id');")."</td>";
        $f[]="</tr>";

    }


    if($META_COMP_VIEW==1) {

        $topbuttons[] = array("Loadjs('$page?comp-view-js=0&function=$function')",
            "fa-solid fa-up-right-and-down-left-from-center", "{extended_view}");
    }else{
        $topbuttons[] = array("Loadjs('$page?comp-view-js=1&function=$function')",
            "fa-solid fa-down-left-and-up-right-to-center", "{compact_view}");

    }
    $gps=GroupsArray();
    if(count($gps)>0){
        $gptxt="{all_groups}";
        if($META_COMP_GROUP>0){
            $gptxt=$gps[$META_COMP_GROUP]["NAME"];
        }
        $topbuttons[] = array("Loadjs('$page?group-select-js=yes&function=$function')", ico_group, $gptxt);
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
function group_selected():bool{

    $_COOKIE["META_COMP_GROUP"]=intval($_GET["group-selected"]);
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "Set_Cookie('META_COMP_GROUP', {$_GET["group-selected"]}, '3600', '/', '', '');\n";
    echo "$function();\n";
    echo "dialogInstance3.close();\n";
    return true;
}
function group_select_popup():bool{
    if(!isset($_COOKIE["META_COMP_GROUP"])){
        $_COOKIE["META_COMP_GROUP"]=0;
    }
    $t=time();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $META_COMP_GROUP=intval($_COOKIE["META_COMP_GROUP"]);
    $gps=GroupsArray();

    $tpl=new template_admin();
    $f[]="<table id='table-$t' class='table table-striped' data-page-size='50'>";
    $f[]="<thead><tr>";
    $f[]="  <th data-sortable='true' data-type='text'></th>";
    $f[]="  <th data-sortable='true' data-type='text'>{name}</th>";
    $f[]="  <th data-sortable='true' data-type='text'>{agents}</th>";
    $f[]="  <th data-sortable='true' data-type='text'>{select}</th>";
    $f[]="</tr></thead><tbody>";
    $TRCLASS="";

    if($META_COMP_GROUP>0) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $name_link = "{all_groups}";
        $countBadge = "{all}";
        $funct = "Loadjs('$page?group-selected=0&function=$function')";
        $choose = "<button class='btn btn-primary btn-xs' type='button' OnClick=\"$funct\">{select}</button>";
        $f[] = "<tr id='tr-0' class='$TRCLASS'>";
        $f[] = "  <td style='width:1%;text-align:center'><i class='fas fa-layer-group' style='color:#1ab394'></i></td>";
        $f[] = "  <td style='width:99%;'><strong>$name_link</strong></td>";
        $f[] = "  <td style='width:1%;text-align:center'>$countBadge</td>";
        $f[] = "  <td style='width:1%' nowrap>$choose</td>";
        $f[] = "</tr>";
    }
    foreach ($gps as $id=>$g) {
        if($id==$META_COMP_GROUP){
            continue;
        }

        $tr_id="tr-group-$id";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $name_link=$g["NAME"];
        $desc=$g["DESC"];
        $countBadge=$g["COUNT"];
        $funct="Loadjs('$page?group-selected=$id&function=$function')";
        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$funct\">{select}</button>";
        $f[]="<tr id='$tr_id' class='$TRCLASS'>";
        $f[]="  <td style='width:1%;text-align:center'><i class='fas fa-layer-group' style='color:#1ab394'></i></td>";
        $f[]="  <td style='width:99%;'><strong>$name_link</strong><br><small>$desc</small></td>";
        $f[]="  <td style='width:1%;text-align:center'>$countBadge</td>";
        $f[]="  <td style='width:1%' nowrap>$choose</td>";
        $f[]="</tr>";

    }
    $f[]="</tbody>";
    $f[]="</table>";
    $f[]="  </div>";
    $f[]="</div>";
    $f[]="<script>";
    $f[]="NoSpinner();\n";
    $f[]=implode("\n",$tpl->ICON_SCRIPTS);
    $f[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}



// ── Disk helper functions ─────────────────────────────────────────────────────

/** Returns [icon, label, color] for a hardware disk based on type/transport. */
function disk_type_info(array $disk): array {
    $transport = strtoupper($disk["transport"] ?? '');
    $rotational = !empty($disk["rotational"]);
    if ($transport === 'NVME') {
        return ['icon'=>'fas fa-bolt',      'label'=>'NVMe', 'color'=>'#ab7df6'];
    }
    if ($rotational) {
        return ['icon'=>'fas fa-hdd',       'label'=>'HDD',  'color'=>'#1c84c6'];
    }
    return     ['icon'=>'fas fa-microchip', 'label'=>'SSD',  'color'=>'#1ab394'];
}

/** Returns a coloured SMART status badge. */
function disk_smart_badge(bool $available, string $status): string {
    if (!$available) {
        return "<span class='badge' style='background:#676a6c;color:#fff;font-size:11px'>N/A</span>";
    }
    $s = strtoupper(trim($status));
    if ($s === 'PASSED') {
        return "<span class='badge' style='background:#1ab394;color:#fff;font-size:11px'>"
             . "<i class='fas fa-check-circle'></i>&nbsp;PASSED</span>";
    }
    if ($s === 'FAILED') {
        return "<span class='badge' style='background:#ed5565;color:#fff;font-size:11px'>"
             . "<i class='fas fa-exclamation-triangle'></i>&nbsp;FAILED</span>";
    }
    return "<span class='badge' style='background:#f8ac59;color:#fff;font-size:11px'>"
         . htmlspecialchars($s ?: '—') . "</span>";
}

/** Returns temperature string coloured green / orange / red. */
function disk_temp_str(int $temp): string {
    if ($temp <= 0) return "<span style='color:#999'>—</span>";
    $color = $temp < 40 ? '#1ab394' : ($temp < 55 ? '#f8ac59' : '#ed5565');
    return "<strong style='color:$color'>{$temp}°C</strong>";
}

/** Converts raw power-on hours to "N,NNN hrs ≈ X days/yrs" string. */
function disk_power_on_str(int $hours): string {
    if ($hours <= 0) return "<span style='color:#999'>—</span>";
    $days  = intdiv($hours, 24);
    $years = intdiv($days, 365);
    $label = number_format($hours) . " hrs";
    if ($years > 0) {
        $label .= "&nbsp;<small style='color:#999'>≈ $years yr" . ($years > 1 ? 's' : '') . "</small>";
    } elseif ($days > 0) {
        $label .= "&nbsp;<small style='color:#999'>≈ $days days</small>";
    }
    return $label;
}

/** Renders a single hardware disk as an ibox card. */
function disk_hw_card(array $disk): string {
    $t         = disk_type_info($disk);
    $name      = htmlspecialchars($disk["name"]         ?? '?');
    $model     = htmlspecialchars($disk["model"]        ?? '—');
    $vendor    = htmlspecialchars($disk["vendor"]       ?? '—');
    $serial    = htmlspecialchars($disk["serial"]       ?? '—');
    $fw        = htmlspecialchars($disk["firmware_rev"] ?? '—');
    $transport = strtoupper($disk["transport"]          ?? '');

    $sizeBytes = intval($disk["size_bytes"] ?? 0);
    $sizeStr   = $sizeBytes > 0 ? FormatBytes($sizeBytes / 1024, true) : '—';

    $lsect  = intval($disk["logical_sector_size"]  ?? 0);
    $psect  = intval($disk["physical_sector_size"] ?? 0);
    $sectStr = ($lsect > 0 && $psect > 0) ? "$lsect&nbsp;/&nbsp;$psect B" : '—';

    $smartAvail = !empty($disk["smart_available"]);
    $smartBadge = disk_smart_badge($smartAvail, $disk["smart_status"] ?? '');
    $tempStr    = disk_temp_str(intval($disk["smart_temperature"]      ?? 0));
    $powerStr   = disk_power_on_str(intval($disk["smart_power_on_hours"] ?? 0));
    $reloc      = intval($disk["smart_reallocated_sectors"] ?? 0);
    $relocStr   = $reloc > 0
        ? "<strong style='color:#ed5565'>$reloc</strong>&nbsp;<small style='color:#ed5565'><i class='fas fa-exclamation-triangle'></i></small>"
        : "<strong style='color:#1ab394'>0</strong>";

    $readBytes  = FormatBytes(intval($disk["read_bytes"]  ?? 0) / 1024, true);
    $writeBytes = FormatBytes(intval($disk["write_bytes"] ?? 0) / 1024, true);
    $readIOs    = number_format(intval($disk["read_ios"]  ?? 0));
    $writeIOs   = number_format(intval($disk["write_ios"] ?? 0));

    $typeExtra  = ($transport && $transport !== $t['label']) ? " · $transport" : '';
    $typeBadge  = "<span class='badge' style='background:{$t['color']};color:#fff;font-size:11px'>"
                . "{$t['label']}$typeExtra</span>";

    $tdLabel = "style='width:1%;white-space:nowrap;padding:5px 10px 5px 0;border-top:0;color:#888;vertical-align:middle'";
    $tdVal   = "style='padding:5px 0;border-top:0;vertical-align:middle'";

    $h = [];
    $h[] = "<div class='ibox' style='margin-bottom:18px'>";

    // ── Title bar ─────────────────────────────────────────────────────────────
    $h[] = "  <div class='ibox-title' style='padding:12px 15px'>";
    $h[] = "    <h5 style='margin:0;line-height:1.6'>";
    $h[] = "      <i class='{$t['icon']}' style='color:{$t['color']};font-size:20px;margin-right:10px;vertical-align:middle'></i>";
    $h[] = "      <strong style='font-family:monospace;font-size:15px;vertical-align:middle'>/dev/$name</strong>";
    $h[] = "      &nbsp;$typeBadge &nbsp;$smartBadge";
    $h[] = "    </h5>";
    $h[] = "    <div class='ibox-tools' style='padding-top:2px'>";
    $h[] = "      <span style='font-size:17px;font-weight:700;color:#555'>$sizeStr</span>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    // ── Body ──────────────────────────────────────────────────────────────────
    $h[] = "  <div class='ibox-content' style='padding:15px'>";
    $h[] = "    <div class='row'>";

    // ── Identity column ───────────────────────────────────────────────────────
    $h[] = "      <div class='col-md-6'>";
    $h[] = "        <table class='table table-condensed' style='margin-bottom:0;font-size:13px'><tbody>";
    foreach ([
        ['fas fa-tag',      '{model}',    $model],
        ['fas fa-building', '{vendor}',   $vendor],
        ['fas fa-barcode',  '{serial}',   "<span style='font-family:monospace'>$serial</span>"],
        ['fas fa-code',     '{firmware}', "<span style='font-family:monospace'>$fw</span>"],
        ['fas fa-ruler',    '{sectors}',  $sectStr],
    ] as [$ico, $lbl, $val]) {
        $h[] = "          <tr>";
        $h[] = "            <td $tdLabel><i class='$ico' style='width:14px;text-align:center;margin-right:5px'></i>$lbl</td>";
        $h[] = "            <td $tdVal>$val</td>";
        $h[] = "          </tr>";
    }
    $h[] = "        </tbody></table>";
    $h[] = "      </div>";

    // ── Health column ─────────────────────────────────────────────────────────
    $h[] = "      <div class='col-md-6'>";
    $h[] = "        <table class='table table-condensed' style='margin-bottom:0;font-size:13px'><tbody>";
    foreach ([
        ['fas fa-shield-alt',         '{smart_status}',      $smartBadge],
        ['fas fa-thermometer-half',   '{temperature}',       $tempStr],
        ['fas fa-clock',              '{power_on_hours}',    $powerStr],
        ['fas fa-exclamation-circle', '{reallocated_sectors}', $relocStr],
    ] as [$ico, $lbl, $val]) {
        $h[] = "          <tr>";
        $h[] = "            <td $tdLabel><i class='$ico' style='width:14px;text-align:center;margin-right:5px'></i>$lbl</td>";
        $h[] = "            <td $tdVal>$val</td>";
        $h[] = "          </tr>";
    }
    $h[] = "        </tbody></table>";
    $h[] = "      </div>";

    $h[] = "    </div>"; // row

    // ── I/O statistics bar ────────────────────────────────────────────────────
    $h[] = "    <div style='margin-top:8px;padding:9px 14px;background:#f8f9fa;border-radius:4px;"
         .      "font-size:12px;color:#676a6c;border-left:3px solid {$t['color']}'>";
    $h[] = "      <strong style='font-size:12px'>"
         .        "<i class='fas fa-chart-bar' style='margin-right:5px;color:{$t['color']}'></i>{io_stats}"
         .      "</strong>&ensp;";
    $h[] = "      <i class='fas fa-arrow-down' style='color:#1ab394'></i>&nbsp;$readBytes read&ensp;";
    $h[] = "      <i class='fas fa-arrow-up'   style='color:#1c84c6'></i>&nbsp;$writeBytes written&ensp;&middot;&ensp;";
    $h[] = "      <i class='fas fa-circle' style='color:#1ab394;font-size:8px;vertical-align:middle'></i>&nbsp;$readIOs ops read&ensp;";
    $h[] = "      <i class='fas fa-circle' style='color:#1c84c6;font-size:8px;vertical-align:middle'></i>&nbsp;$writeIOs ops written";
    $h[] = "    </div>";

    // ── Partition table ───────────────────────────────────────────────────────
    $partitions = $disk["partitions"] ?? [];
    if (!empty($partitions)) {
        $h[] = "    <div style='margin-top:15px'>";
        $h[] = "      <h6 style='color:#676a6c;margin-bottom:8px;font-size:12px;text-transform:uppercase;letter-spacing:0.5px'>";
        $h[] = "        <i class='fas fa-list' style='margin-right:5px'></i>{partitions}";
        $h[] = "      </h6>";
        $h[] = "      <table class='table table-condensed table-bordered' style='font-size:12px;margin-bottom:0'>";
        $h[] = "        <thead><tr style='background:#f8f9fa'>";
        $h[] = "          <th style='white-space:nowrap'>{device}</th>";
        $h[] = "          <th style='white-space:nowrap'>{filesystem}</th>";
        $h[] = "          <th>{mount_point}</th>";
        $h[] = "          <th style='text-align:right;white-space:nowrap'>{size}</th>";
        $h[] = "          <th>{label}</th>";
        $h[] = "          <th style='color:#aaa'>UUID</th>";
        $h[] = "        </tr></thead>";
        $h[] = "        <tbody>";
        foreach ($partitions as $part) {
            if (!is_array($part)) continue;
            $pname  = htmlspecialchars($part["name"]        ?? '');
            $pfs    = htmlspecialchars($part["filesystem"]  ?? '');
            $pmnt   = htmlspecialchars($part["mount_point"] ?? '');
            $plbl   = htmlspecialchars($part["label"]       ?? '');
            $puuid  = htmlspecialchars($part["uuid"]        ?? '');
            $psize  = intval($part["size_bytes"] ?? 0);
            $psizeStr = $psize > 0 ? FormatBytes($psize / 1024, true) : '—';
            $h[] = "          <tr>";
            $h[] = "            <td nowrap><span style='font-family:monospace'>" . ($pname ? "/dev/$pname" : '—') . "</span></td>";
            $h[] = "            <td nowrap><span style='font-family:monospace'>" . ($pfs  ?: '—') . "</span></td>";
            $h[] = "            <td><span style='font-family:monospace'>" . ($pmnt ?: '') . "</span></td>";
            $h[] = "            <td style='text-align:right' nowrap>$psizeStr</td>";
            $h[] = "            <td>$plbl</td>";
            $h[] = "            <td style='font-family:monospace;font-size:10px;color:#aaa'>$puuid</td>";
            $h[] = "          </tr>";
        }
        $h[] = "        </tbody></table>";
        $h[] = "    </div>";
    }

    $h[] = "  </div>"; // ibox-content
    $h[] = "</div>";   // ibox
    return implode("\n", $h);
}

// ─────────────────────────────────────────────────────────────────────────────

function agent_info_disk():bool{
    $id   = intval($_GET["agent-info-disk"]);
    $tpl  = new template_admin();
    $main = agent_status($id);
    $h    = [];

    // ── Hardware disk inventory (from status disks array) ─────────────────────
    $hwDisks = $main["DISKS"] ?? [];
    if (!empty($hwDisks)) {
        $h[] = "<div class='ibox'>";
        $h[] = "  <div class='ibox-title'>";
        $h[] = "    <h5><i class='fas fa-hdd' style='margin-right:8px'></i>{hardware_disks}</h5>";
        $h[] = "  </div>";
        $h[] = "  <div class='ibox-content' style='padding:15px'>";
        foreach ($hwDisks as $disk) {
            if (!is_array($disk)) continue;
            $h[] = disk_hw_card($disk);
        }
        $h[] = "  </div>";
        $h[] = "</div>";
    }

    // ── Filesystem mount-point usage ──────────────────────────────────────────
    $allHD = $main["ALLHD"] ?? [];
    if (!empty($allHD)) {
        $h[] = "<div class='ibox'>";
        $h[] = "  <div class='ibox-title'>";
        $h[] = "    <h5><i class='fas fa-folder-open' style='margin-right:8px'></i>{filesystem_usage}</h5>";
        $h[] = "  </div>";
        $h[] = "  <div class='ibox-content' style='padding:0'>";
        $h[] = "    <table class='table' style='margin-bottom:0'>";
        foreach ($allHD as $device => $info) {
            $total_bytes = intval($info["total_bytes"]);
            $used_bytes  = intval($info["used_bytes"]);
            $free_bytes  = $total_bytes - $used_bytes;
            $total_str   = FormatBytes($total_bytes / 1024);
            $used_str    = FormatBytes($used_bytes  / 1024);
            $free_str    = FormatBytes($free_bytes  / 1024);
            $pct         = $total_bytes > 0 ? intval(100 * $used_bytes / $total_bytes) : 0;
            $bar_color   = $pct > 90 ? '#ed5565' : ($pct > 75 ? '#f8ac59' : '#1ab394');
            $td = "style='border-top:0;padding:14px 12px;vertical-align:middle'";
            $h[] = "    <tr>";
            $h[] = "      <td $td style='width:1%;padding-right:18px'>";
            $h[] = "        <span class='pie'>$used_bytes/$total_bytes</span>";
            $h[] = "      </td>";
            $h[] = "      <td $td>";
            $h[] = "        <strong style='font-size:15px;font-family:monospace'>$device</strong>";
            $h[] = "        <div style='margin-top:7px'>";
            $h[] = "          <div style='height:5px;background:#e9ecef;border-radius:3px'>";
            $h[] = "            <div style='height:5px;width:$pct%;background:$bar_color;border-radius:3px'></div>";
            $h[] = "          </div>";
            $h[] = "        </div>";
            $h[] = "      </td>";
            $h[] = "      <td $td nowrap style='text-align:right'>";
            $h[] = "        <small style='color:#999;display:block'>{used}</small>";
            $h[] = "        <strong>$used_str</strong>";
            $h[] = "      </td>";
            $h[] = "      <td $td nowrap style='text-align:right'>";
            $h[] = "        <small style='color:#999;display:block'>{free}</small>";
            $h[] = "        <strong>$free_str</strong>";
            $h[] = "      </td>";
            $h[] = "      <td $td nowrap style='text-align:right'>";
            $h[] = "        <small style='color:#999;display:block'>{total}</small>";
            $h[] = "        <strong>$total_str</strong>";
            $h[] = "      </td>";
            $h[] = "      <td $td nowrap style='text-align:right;padding-left:20px'>";
            $h[] = "        <strong style='font-size:22px;color:$bar_color'>$pct%</strong>";
            $h[] = "      </td>";
            $h[] = "    </tr>";
        }
        $h[] = "    </table>";
        $h[] = "  </div>";
        $h[] = "</div>";
        $h[] = "<script>\$(\"span.pie\").peity(\"pie\",{ fill:[\"#ed5565\",\"#18a689\"],height:52,width:52 });</script>";
    }

    if (empty($h)) {
        $h[] = $tpl->div_info("{no_data}");
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}
function GroupsArray():array{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups");
    $groups=json_decode($raw);
    if(json_last_error()>JSON_ERROR_NONE){
        return array();
    }

    // Handle outFalse response
    if(is_object($groups)&&property_exists($groups,"Status")&&!$groups->Status){
        return array();
    }

    if(!is_array($groups)||count($groups)===0){
        return array();
    }
    $array=array();
    foreach($groups as $g){
        $id=intval($g->id);
        $name=htmlspecialchars($g->name);
        $desc=htmlspecialchars($g->description??"");
        $count=intval($g->agent_count??0);
        $array[$id]=array("NAME"=>$name,"DESC"=>$desc,"COUNT"=>$count);
    }
    return $array;
}


function agent_info_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-tab"]);
    $AgentArtica=new ArticaNetAgents($id);
    $Hostname=$AgentArtica->GetAgentHostname();
    $AgentArtica=AgentArtica($id);
    $token="";
    if($AgentArtica){
        $token="&artica=yes";
    }
    $array[$Hostname]="$page?agent-info-status=$id$token";
    $array["{features}"]="fw.netagents.features.php?id=$id";
    $array["{disks}"]="$page?agent-info-disk=$id$token";
    $array["{network}"]="fw.netagents.network.php?id=$id$token";
    $array["DNS"]="fw.netagents.dns.php?id=$id";
    if($AgentArtica){
        $array["Artica"]="$page?agent-info-artica=$id";
    }
    $array["Proxy"]="fw.netagents.proxy.php?id=$id";

    //$array["{agents_list}"]="$page?agents-list=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function getAgentHostname($id):string{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    if(!is_object($json)){
        return "";
    }
    if(!is_object($json) || !property_exists($json,"hostname")){
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
    $details=$sock->GetAgentSoftwares();

    $SP=intval($details["artica_service_pack"]);
    $DebVer=$details["debian_version"];



    if($SquidEnable==1){
        $tpl->table_form_field_js("Loadjs('fw.netagents.update.software.single.php?id=$id&soft=APP_SQUID,APP_SQUID6&debver=$DebVer')","AsArticaMetaAdmin");
        $tpl->table_form_field_text("{APP_SQUID}",$sock->GET_INFO("SquidRealVersion"),ico_server);
    }
    if($EnableNginx==1){
        $APP_NGINX_VERSION  = $sock->GET_INFO("APP_NGINX_VERSION");
        $tpl->table_form_field_js("Loadjs('fw.netagents.update.software.single.php?id=$id&soft=APP_NGINX&debver=$DebVer')","AsArticaMetaAdmin");
        $tpl->table_form_field_text("{APP_NGINX}",$APP_NGINX_VERSION,ico_earth);
    }

    $Enablehacluster=intval($sock->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){
        $HAPROXY_VERSION  = $sock->GET_INFO("HAPROXY_VERSION");
        $tpl->table_form_field_js("Loadjs('fw.netagents.update.software.single.php?id=$id&soft=APP_HAPROXY&debver=$DebVer')","AsArticaMetaAdmin");
        $tpl->table_form_field_text("HaCluster",$HAPROXY_VERSION,ico_sensor);
    }
    $APP_REDIS_SERVER_VERSION  = $sock->GET_INFO("APP_REDIS_SERVER_VERSION");
    $tpl->table_form_field_js("Loadjs('fw.netagents.update.software.single.php?id=$id&soft=APP_REDIS_SERVER&debver=$DebVer')","AsArticaMetaAdmin");
    $tpl->table_form_field_text("{APP_REDIS_SERVER}",$APP_REDIS_SERVER_VERSION,ico_database);


    $APP_SYSLOGD_VERSION  = $sock->GET_INFO("APP_SYSLOGD_VERSION");
    $tpl->table_form_field_js("Loadjs('fw.netagents.update.software.single.php?id=$id&soft=APP_SYSLOGD&debver=$DebVer')","AsArticaMetaAdmin");
    $tpl->table_form_field_text("{APP_RSYSLOG}",$APP_SYSLOGD_VERSION,ico_list_opt);

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
function agent_action_get_update_js():bool{
    $id=intval($_GET["apt-get-update-js"]);
    $tpl=new template_admin();

    $sock=new sockets();
    $json=json_decode($sock->REST_API_POST("/netagents/update/$id",array()),true);
    if(!isset($json["success"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["success"]){
        return $tpl->js_error($json["message"]);
    }
    $Netagent=new ArticaNetAgents($id);
    $Hostname=$Netagent->GetAgentHostname();
    admin_tracks("Meta: Running an update check on $Hostname");
    return $tpl->js_ok("$Hostname: ".$json["message"]);
}
function blacklisted_js():bool{
    $agent_id=intval($_GET["blacklisted-js"]);
    $agent=new ArticaNetAgents($agent_id);
    $Crustatus=$agent->Status();
    $AgentName=$agent->GetAgentHostname();
    $blacklisted=false;
    $blacklistedInt=0;
    if(isset($Crustatus["blacklisted"])){
        $blacklisted = $Crustatus["blacklisted"];
    }
    if($blacklisted){
        $blacklistedInt=1;
    }
    if($blacklistedInt==1){
        $blacklistedInt=0;
    }else{
        $blacklistedInt=1;
    }
    if($blacklistedInt==1) {
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/blacklist/$agent_id", array()),true);
    }else{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/netagents/blacklist/$agent_id"),true);
    }
    if(!$json["Status"]){
        $tpl=new template_admin();
        echo $tpl->js_error($json["Error"]. "#$agent_id");
        return false;
    }
    return admin_tracks("set $AgentName Blacklisted for updates to $blacklistedInt");
}

function agent_info_license($Crustatus,$id,$tpl){

    if(!isset($Crustatus["artica_license"])){
        return $tpl;
    }
    //var_dump($agentJson->artica_license);
    $license=$Crustatus["artica_license"];
    $tpl->table_form_field_js("Loadjs('fw.netagents.license.php?id=$id');");
    if($license["gold_license"]){
        $tpl->table_form_field_text("{license}","{gold_license}",ico_certificate);
        $tpl->table_form_field_js("");
        return $tpl;
    }


    if(!$license["entreprise_license"]){
        $tpl->table_form_field_bool("{license}",0,ico_certificate);
        $tpl->table_form_field_js("");
        return $tpl;

    }

    $tpl->table_form_field_text("{license}",$license["Info"]["license_status"],ico_certificate);
    $tpl->table_form_field_js("");
    return $tpl;

}
function agent_info_snapshots($Crustatus,$id,$tpl){

    $artica=false;
    if(isset($_GET["artica"])){
        $artica=true;
    }

    if(!isset($Crustatus["artica_snapshots"])){
        if($artica){
            $tpl->table_form_field_js("Loadjs('fw.netagents.snapshots.php?snap-js=$id');");
            $tpl->table_form_field_bool("{snapshots}",0,ico_file_zip);
            return $tpl;
        }
        return $tpl;
    }
    $snaps=$Crustatus["artica_snapshots"];
    if(!is_array($snaps)||count($snaps)===0){
        $tpl->table_form_field_js("Loadjs('fw.netagents.snapshots.php?snap-js=$id')");
        $tpl->table_form_field_bool("{snapshots}",0,ico_file_zip);
        return $tpl;
    }
    $tpl->table_form_field_js("Loadjs('fw.netagents.snapshots.php?snap-js=$id')");
    $tpl->table_form_field_text("{snapshots}",count($snaps),ico_file_zip);
    return $tpl;

}

function agent_info_status_apt($Crustatus,$id,$tpl){
    $page=CurrentPageName();

    $blacklisted=false;
    if(isset($Crustatus["blacklisted"])){
        $blacklisted = $Crustatus["blacklisted"];
    }

    foreach ($Crustatus["groups"] as $gps){
        if(isset($gps["blacklisted"]) && $gps["blacklisted"]){
            $tpl->table_form_field_bool("{blacklisted}",$blacklisted,ico_stop);
            $tpl->table_form_field_bool("{system_upgrade}",0,ico_stop);
            return $tpl;
        }
    }



    $tpl->table_form_field_js("Loadjs('$page?blacklisted-js=$id')");
    $tpl->table_form_field_bool("{blacklisted}",$blacklisted,ico_stop);
    $tpl->table_form_field_js("");

    if($blacklisted){
        $tpl->table_form_field_bool("{system_upgrade}",0,ico_stop);
        return $tpl;
    }

    if(!isset($Crustatus["aptget"])) {
        return $tpl;
    }
    if($Crustatus["aptget"]["apt_running"]){
        $tpl->table_form_field_text("{system_upgrade}","{running}",ico_refresh_animate);
        return $tpl;
    }

    $apts=array();
    $last_upgrade=0;

    $upgrade_required=intval($Crustatus["aptget"]["upgrade_required"]);
    if(isset($Crustatus["aptget"]["last_upgrade"])) {
        $last_upgrade = intval($Crustatus["aptget"]["last_upgrade"]);
    }
    if($upgrade_required>0){
            $apts[]="{upgradable_packages}: $upgrade_required";
            $tpl->table_form_field_js("Loadjs('fw.netagents.aptget.upgrade.php?id=$id')");
            $tpl->table_form_field_button("{upgradable_packages}","{check_updates}",ico_refresh);
            $tpl->table_form_field_js("");
        }else{
            $tpl->table_form_field_js("Loadjs('$page?apt-get-update-js=$id')");
            $tpl->table_form_field_button("{upgradable_packages}","{check_updates}",ico_refresh);
            $tpl->table_form_field_js("");
    }
    if($last_upgrade>0){
        $ll="{success}";
        if(!$Crustatus["aptget"]["last_results"]){
               $ll="<span class='text-danger'>{with_errors}</span>";
        }

        $time=$tpl->time_to_date($last_upgrade);
        $apts[]="{last_upgrade}: $time, $ll";
    }

    if(count($apts)>0){
        $tpl->table_form_field_js("Loadjs('fw.netagents.aptget.reports.php?id=$id')");
        $tpl->table_form_field_text("{system_upgrade}","<small>".@implode(", ",$apts),ico_download);
    }
    return $tpl;

}
function agent_info_status_popup():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-status-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    if(!is_object($json)){
        echo $tpl->div_error("Failed to fetch agent info");
        return true;
    }
    if(property_exists($json,"Status")) {
        if(!$json->Status){
            echo $tpl->div_error($json->Error);
            return true;
        }
    }

    $agent=new ArticaNetAgents($id);
    $Crustatus=$agent->Status();
    $artica=false;
    if(isset($_GET["artica"])){
        $artica=true;
    }
    $Duration="-";
    if(is_object($json) && property_exists($json,"last_seen")) {
        try {
            $dt = new DateTime($json->last_seen);
            $timestamp = $dt->getTimestamp();
            $Duration = distanceOfTimeInWords($timestamp, time(), true);
        } catch (\Exception $e) {
            $Duration = "-";
        }
    }

    $tpl->table_form_field_js("Loadjs('fw.netagents.hostname.php?id=$id')");
    $tpl->table_form_field_text("{hostname}","<span style='text-transform:none'>".$Crustatus["hostname"]."</span>",ico_server);
    $tpl->table_form_field_js("");
    if(isset($json->description) && strlen($json->description)>1) {
        $tpl->table_form_field_text("{description}", "<small>$json->description</small>", ico_infoi);
    }
    $gp=array();
    if(is_object($json) && property_exists($json,"groups")) {
        foreach ($json->groups as $groupName=>$groupid){
            $gp[]=$tpl->td_href($groupName,"","Loadjs('fw.netagents.groups.php?gpid=$groupid')");
        }
    }
    if(count($gp)>0){
        $tpl->table_form_field_text("{groups}","<small>".@implode(",",$gp)."</small>",ico_group);
    }else{
        $tpl->table_form_field_bool("{groups}",0,ico_group);
    }

    if(isset($json->path_prefix)) {
        $path_prefix = $json->path_prefix;
        $tpl->table_form_field_js("Loadjs('$page?agent-address-js=$id')");
        $tpl->table_form_field_text("{address}", $json->ip_address . ":" . $json->port . $path_prefix, ico_networks);
    }
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{last_seen}",$Duration,ico_timeout);
    $tpl->table_form_field_text("{registered_at}",$json->registered_at,ico_timeout);

    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");

    if (version_compare($CurVer, $json->version, '>')){
        $tpl=new template_admin();
        $ico_fleche=ico_arrow_right;
        $tpl->table_form_field_js("Loadjs('$page?agents-upgrade-js=$id')");
        $tpl->table_form_field_text("{agent_version}","$json->version&nbsp;<i class='$ico_fleche'></i>&nbsp;$CurVer",ico_infoi);
        $tpl->table_form_field_js("");
    }else{
        $tpl->table_form_field_text("{agent_version}",$json->version."&nbsp;/&nbsp;$CurVer",ico_infoi);
    }

    $tpl=agent_info_snapshots($Crustatus,$id,$tpl);
    $tpl=agent_info_status_apt($Crustatus,$id,$tpl);
    $tpl=agent_info_license($Crustatus,$id,$tpl);

if($artica){
    $Version=AgentArticaVersion($id);
    $tpl->table_form_field_text("Artica",$Version,"ico ico-artica-25");
    $jsonWeb=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/webconsole/config/$id"));

    $topbuttons[] = array("Loadjs('$page?artica-web-restart-js=$id')", ico_retweet, "{restart}");
    if(is_object($jsonWeb) && property_exists($jsonWeb,"unix_socket")){
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
    if(is_object($json->client_cert_info ?? null) && property_exists($json->client_cert_info,"dns_names")){
        if(is_array($json->client_cert_info->dns_names)) {
            $tpl->table_form_field_text("{dns_names}", @implode(",", $json->client_cert_info->dns_names), ico_server);
        }
    }

    $tpl->table_form_button("{reboot}","Loadjs('$page?reboot-js=$id')","AsArticaMetaAdmin",ico_refresh,"btn-warning");
    echo $tpl->table_form_compile();
    return true;


}


function td_Aweb($agentJson):string{
    $tpl=new template_admin();


    if($agentJson->status=="offline"){
        return $tpl->icon_browser("");
    }

    $refresh=ico_refresh($agentJson);
    if(strlen($refresh)>1){
        return "$refresh";
    }

    if(!AgentArtica($agentJson->id)){
        echo "// td_Aweb -->  $agentJson->id -> Not Artica\n";
        return "";
    }
    $ip = htmlspecialchars($agentJson->ip_address);

    return $tpl->icon_browser("s_PopUpFull('/netagents/webconsole/proxy/$agentJson->id/fw.login.php',1024,768,'Artica Web console $ip')","Artica Web Console");
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
    if(!isset($_COOKIE["META_COMP_VIEW"])){
        $_COOKIE["META_COMP_VIEW"]=0;
    }
    $META_COMP_VIEW=intval($_COOKIE["META_COMP_VIEW"]);

    $ids=explode("|",$_GET["td-all"]);
    foreach ($ids as $sid){
        $id=intval($sid);
        $jsPeity="";$status="";

        if(!isset($jsonMain[$id])){
            continue;
        }

        $hostname=base64_encode(td_hostname($jsonMain[$id]));
        if($META_COMP_VIEW==0) {
            $status = base64_encode(td_status($jsonMain[$id]));
        }
        $Art=new ArticaNetAgents($id);

        $td_memory=base64_encode(td_memory($jsonMain[$id]));
        $td_uptime=td_uptime($jsonMain[$id]);
        $type=base64_encode(td_type($jsonMain[$id]));
        $webConsole=base64_encode(td_Aweb($jsonMain[$id]));
        $td_disk=base64_encode(td_disk($jsonMain[$id]));
        $td_apt=base64_encode(td_apt($jsonMain[$id]));
        $td_ux=base64_encode(td_ux($jsonMain[$id]));
        $td_logs=base64_encode(td_logs($jsonMain[$id]));

        $td_prodlogs=base64_encode(td_prodlogs($jsonMain[$id],$Art));
        $td_services=base64_encode(td_services($jsonMain[$id]));
        $td_license=base64_encode(td_license($jsonMain[$id]));




        if($META_COMP_VIEW==0) {
            $typeMode = base64_encode($Art->LabelType());
        }else{
            $typeMode = base64_encode($Art->LabelTypeStatus($jsonMain[$id]));
        }

        $js[]="// ".__LINE__.": $id";


        if($META_COMP_VIEW==0){
            list($td_cpu,$jsPeity)=td_cpu($jsonMain[$id]);
            $td_cpu=base64_encode($td_cpu);
        }else {
            list($td_cpu,$jsPeity) =td_cpu_memory_compacted($jsonMain[$id]);
            $td_cpu=base64_encode($td_cpu);
        }

        $js[]="$('#agent-$id-services').html( base64_decode('$td_services') );";
        $js[]="$('#agent-$id-logs').html( base64_decode('$td_logs') );";
        $js[]="$('#agent-$id-prodlogs').html( base64_decode('$td_prodlogs') );";
        $js[]="$('#agent-$id-lic').html( base64_decode('$td_license') );";
        $js[]="$('#agent-$id-ux').html( base64_decode('$td_ux') );";
        $js[]="$('#agent-$id-mode').html( base64_decode('$typeMode') );";
        $js[]="$('#agent-$id-type').html( base64_decode('$type') );";
        $js[]="$('#agent-$id-hostname').html( base64_decode('$hostname') );";
        if($META_COMP_VIEW==0) {
            $js[] = "$('#agent-$id-status').html( base64_decode('$status') );";
        }
        $js[]="$('#agent-$id-cpu').html(base64_decode('$td_cpu'));";
        $js[]="$('#agent-$id-apt').html(base64_decode('$td_apt'));";
        $js[]="$('#agent-$id-disk').html(base64_decode('$td_disk'));";
        $js[]="$('#agent-$id-memory').html(base64_decode('$td_memory'));";
        $js[]="$('#agent-$id-uptime').html('$td_uptime');";
        $js[]="$('#agent-$id-web').html(base64_decode('$webConsole'));";
        if(strlen($jsPeity)>1){
            $js[]=$jsPeity;
        }
    }
    $js[]="closeAllPopoversPopup()";
    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
    return true;
}

function td_ux($agentJson):string{
    $tpl=new template_admin();
    $status = $agentJson->status;
    $id=$agentJson->id;

    if($status == "online"){
        return $tpl->icon_shell("Loadjs('fw.netagents.shell.php?id=$id')","AsArticaMetaAdmin",false,"{system_shell}");
    }
    return $tpl->icon_shell();
}
function td_license($agentJson):string{
    $tpl = new template_admin();
    $id = $agentJson->id;

    if(!isset($agentJson->artica_license)){
        return "";
    }
    //var_dump($agentJson->artica_license);
    $license=$agentJson->artica_license;
    if($license->gold_license){
        return $tpl->icon_certificate("Loadjs('fw.netagents.license.php?id=$id')","","{gold_license}");
    }


    if(!$license->entreprise_license){
        return $tpl->_ENGINE_parse_body($tpl->icon_certificate("","","Community Edition<br>{$license->Info->license_status}"));
    }
    if(!isset($license->Info->license_status)){
        return "";
    }
    return $tpl->icon_certificate("Loadjs('fw.netagents.license.php?id=$id')","",$license->Info->license_status);

}

function td_prodlogs($agentJson,$ArticaNetAgents):string{
    $tpl = new template_admin();
    $status = $agentJson->status;
    $id = $agentJson->id;

    if ($status <> "online") {
        return "";
    }

    if(isset($agentJson->http_proxy)){
        $http_proxy=$agentJson->http_proxy;
        if($http_proxy->installed){
            if(isset($http_proxy->enabled) && $http_proxy->enabled){
                return $tpl->icon_logs2("Loadjs('fw.proxy.relatime.php?meta-id-js=$id')",
                    "AsArticaMetaAdmin",false,"{APP_SQUID}: {requests}");
            }
        }
    }
    if(isset($agentJson->unbound)){
        $unbound=$agentJson->unbound;
        if($unbound->installed){
            if(isset($unbound->enabled) && $unbound->enabled){
                return "";
                //return $tpl->icon_logs2("Loadjs('fw.proxy.relatime.php?meta-id-js=$id')",
                //    "AsArticaMetaAdmin",false,"{APP_SQUID}: {requests}");
            }
        }
    }

    return "";


}
function td_logs($agentJson):string{
    $tpl = new template_admin();
    $status = $agentJson->status;
    $id = $agentJson->id;

    if ($status == "online") {
        return $tpl->icon_logs("Loadjs('fw.netagents.logs.php?id=$id')",
            "AsArticaMetaAdmin",false,"{events}");
    }
    return $tpl->icon_logs();
}
function td_services($agentJson):string{
    $tpl = new template_admin();
    if(is_null($agentJson->status)){
        return $tpl->icon_services();
    }
    $status = $agentJson->status;
    $id = $agentJson->id;

    if ($status == "online") {
        return $tpl->icon_services("Loadjs('fw.netagents.services.php?id=$id')",
            "AsArticaMetaAdmin","{manage_services}");
    }
    return $tpl->icon_services();
}
function td_status($agentJson):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $status = $agentJson->status;
    $id=$agentJson->id;
    $status_class = "label-default";
    if($status == "online"){
        $status_class = "label-primary";
        $action=AgentRefreshing($agentJson->id);
        echo "// AgentRefreshing = $action\n";
        if(strlen($action)>3){
            $status=$action;
        }

        return $tpl->_ENGINE_parse_body("<span class='label $status_class'>{{$status}}</span>");
    }
    if($status == "offline"){
        $status_class = "label-danger";
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/export/$agentJson->id"));
        if(!is_object($json) || !property_exists($json,"client_cert")){
            $status_class = "label-default";
            return $tpl->_ENGINE_parse_body($tpl->td_href("<span class='label $status_class'>{enroll}</span>",
                "","Loadjs('$page?agent-enroll=$id')"));
        }
        return $tpl->_ENGINE_parse_body("<span class='label $status_class'>{{$status}}</span>");
    }
    if($status == "pending"){
        $status_class = "label-warning";
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/export/$agentJson->id"));
        if(!is_object($json) || !property_exists($json,"client_cert")){
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
    if(is_object($json) && property_exists($json,"refreshing")){
        if($json->refreshing){
            if(!isset($json->status)){
                return "refreshing";
            }
            return strval($json->status);
        }
    }
    return "";
}
function AgentArtica($id):bool{
    if(isset($GLOBALS["AGENTARICA"][$id])){
        return $GLOBALS["AGENTARICA"][$id];
    }
    $json=AgenSoft($id);
    if(!is_object($json)){
        $GLOBALS["AGENTARICA"][$id]=false;
        return false;
    }

    if(!is_object($json) || !property_exists($json,"artica_version") ){
        $GLOBALS["AGENTARICA"][$id]=false;
        return false;
    }
    if(strlen($json->artica_version)>3){
        $GLOBALS["AGENTARICA"][$id]=true;
        return true;
    }
    $GLOBALS["AGENTARICA"][$id]=false;
    return false;
}
function AgentArticaVersion($id):string{
    $json=AgenSoft($id);
    if(!is_object($json) || !property_exists($json,"artica_version")){
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
function td_cpu_memory_compacted($agentJson):array{
    $id = $agentJson->id;
    $main=agent_status($id);
    $cpuLabel=$main["cpu"];          // "0.05 / 0.03 / 0.00" or "-"
    $memLabel=$main["mem_pct"];      // "15.7%" or "-"
    $memInt=intval($main["mem_int"] ?? 0);

    // Extract 1-min load average
    $load1=0.0;
    $loadShort="-";
    if($cpuLabel !== "-" && $cpuLabel !== 0){
        $parts=explode(" / ", $cpuLabel);
        $load1=floatval($parts[0] ?? 0);
        $loadShort=number_format($load1,2);
    }

    $memColor=$memInt>=90?"#ed5565":($memInt>=70?"#f8ac59":"#1ab394");

    $s="<div style='min-width:130px'>";
    // CPU row
    if(isset($main["cpu_pct"])) {
        $cpuInt = floatval($main["cpu_pct"] ?? 0);
        $cpuShort = round($cpuInt, 2) . "%";
        $cpuColor = $cpuInt >= 80 ? "#ed5565" : ($cpuInt >= 50 ? "#f8ac59" : "#1ab394");
        $s .= "<div style='display:flex;align-items:center;gap:5px;margin-bottom:4px'>";
        $s .= "<span style='font-size:10px;color:#999;width:26px;flex-shrink:0'>CPU</span>";
        $s .= "<div style='flex:1;background:#e8e8e8;border-radius:3px;height:8px;overflow:hidden'>";
        $s .= "<div style='width:{$cpuInt}%;height:100%;background:{$cpuColor};border-radius:3px;transition:width 0.4s ease'></div>";
        $s .= "</div>";
        $s .= "<span style='font-size:10px;color:#555;width:34px;text-align:right;flex-shrink:0'>$cpuShort</span>";
        $s .= "</div>";
    }
    // Load row (1-min load average; bar scaled: load 4.0 = 100%)
    $loadInt=min(100, intval($load1 * 25));
    $loadColor=$loadInt>=80?"#ed5565":($loadInt>=50?"#f8ac59":"#1ab394");
    $s.="<div style='display:flex;align-items:center;gap:5px;margin-bottom:4px'>";
    $s.="<span style='font-size:10px;color:#999;width:26px;flex-shrink:0'>LD</span>";
    $s.="<div style='flex:1;background:#e8e8e8;border-radius:3px;height:8px;overflow:hidden'>";
    $s.="<div style='width:{$loadInt}%;height:100%;background:{$loadColor};border-radius:3px;transition:width 0.4s ease'></div>";
    $s.="</div>";
    $s.="<span style='font-size:10px;color:#555;width:34px;text-align:right;flex-shrink:0'>$loadShort</span>";
    $s.="</div>";
    // Memory row
    $s.="<div style='display:flex;align-items:center;gap:5px'>";
    $s.="<span style='font-size:10px;color:#999;width:26px;flex-shrink:0'>MEM</span>";
    $s.="<div style='flex:1;background:#e8e8e8;border-radius:3px;height:8px;overflow:hidden'>";
    $s.="<div style='width:{$memInt}%;height:100%;background:{$memColor};border-radius:3px;transition:width 0.4s ease'></div>";
    $s.="</div>";
    $s.="<span style='font-size:10px;color:#555;width:34px;text-align:right;flex-shrink:0'>$memLabel</span>";
    $s.="</div>";
    $s.="</div>";
    $html[]="<div id='td-cpu-clickable-$id' class='pointer-cursor'>";
    $html[]=$s;
    $html[]="</div>";
    return array(@implode("",$html),"$('#td-cpu-clickable-$id').click(function () { Loadjs('fw.netagents.metrics.charts.php?agent-metrics-js=$id') } );");
}

function td_cpu($agentJson):array{
    $id = $agentJson->id;
    $main=agent_status($id);
    $html[]="<div id='td-cpu-clickable-$id' class='pointer-cursor'>".$main["cpu"]."%</div>";
    $dashjs="";
    echo "// [".__LINE__."]: Peity: ".count($main["peity"])."\n";
    if(count($main["peity"])>0){
        $peity_conf="{ width:110,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
        $html[]="<div id=\"peity-cpu-line-$id\" class='pointer-cursor'>".@implode(",",$main["peity"])."</div>";
        $dashjs="\t$(\"#peity-cpu-line-$id\").peity(\"line\",$peity_conf);
        $('#td-cpu-clickable-$id').click(function () { Loadjs('fw.netagents.metrics.charts.php?agent-metrics-js=$id') } );";
    }
    return array(implode("",$html)."</div>",$dashjs);
}
function td_cpu_graphs($id):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/cpu-history/agent/$id?limit=60"),true);
    if(!isset($json["records"])){
        return array();
    }
    $f=array();



    foreach ($json["records"] as $record){
            $percentage=round($record["percentage"],2);
            $f[]=$percentage;

    }

 return $f;
}

//aptget] => Array ( [upgrade_required] => 183 [apt_running] => [last_upgrade] => 1770662194 [last_results] => ) [cached_at] => 2026-02-09T21:00:12.148501928+01:00 )


function ico_refresh($agentJson):string{

    if($agentJson->status=="offline"){
        return "";
    }
    $AgentRefreshing=AgentRefreshing($agentJson->id);
    if(strlen($AgentRefreshing)==0){return "";}

    $ico=ico_refresh_animate;
    $id="ico-refresh-".__LINE__."-$agentJson->id";
    $tpl=new template_admin();
    list($tooltip,$none)=$tpl->Tooltips($id,"{{$AgentRefreshing}}");
    return $tpl->_ENGINE_parse_body("<i class='$ico' id='$id' $tooltip></i>");
}
function td_apt($agentJson):string{

    if($agentJson->status=="offline"){
        return "&nbsp;";
    }
    $refresh=ico_refresh($agentJson);
    if(strlen($refresh)>1){
        return "$refresh";
    }


    $main=agent_status($agentJson->id);

    if(!isset($main["aptget"]["upgrade_required"])){
        echo "// !isset aptget->upgrade_required\n";;
        return "&nbsp;";
    }
    if($main["aptget"]["apt_running"]){
        echo "//td_apt = apt_running\n";
        return "<i class='".ico_refresh_animate."'></i>";
    }
    if($main["aptget"]["upgrade_required"]>0){
        $tpl=new template_admin();
        $upgrade_required=intval($main["aptget"]["upgrade_required"]);
        $js="Loadjs('fw.netagents.aptget.upgrade.php?id=$agentJson->id')";
        return $tpl->icon_badge_bell($upgrade_required,$js,"");


    }
    return "&nbsp;";

}

function td_disk($agentJson):string{
    $tpl=new template_admin();
    $main=agent_status($agentJson->id);
    if(!isset($main["HD"]["percent_int"])){
        return "-";
    }
    $ico=ico_hd;
    $ichd="<i class='$ico'></i>&nbsp;";
    $link=$tpl->td_href($main["HD"]["percent"],"{disks}: {$main["HD"]["percent"]}",
        "Loadjs('fw.netagents.treesize.php?id=$agentJson->id')");
    $disk= "<span class='text-muted'>$ichd$link</span>";

    if($main["HD"]["percent_int"]>70){
        $disk= "<span style='font-weight:bold;color:black'>$ichd$link</span>";
    }

    if($main["HD"]["percent_int"]>80){
        $disk= "<span class='text-warning' style='font-weight:bold'>$ichd$link</span>";
    }
    if($main["HD"]["percent_int"]>90){
        $disk= "<span class='text-danger' style='font-weight:bold'>$ichd$link</span>";
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
    $bell="";
    $page=CurrentPageName();
    $ip = htmlspecialchars($agentJson->ip_address);
    $port = intval($agentJson->port);
    $tpl=new template_admin();
    $main=agent_status($id);
    $agentver="";$description="";$proxyUsers="";$ver="";
    $agentOK=true;
    $status = $agentJson->status;
    $id=$agentJson->id;

    if($status == "offline"){
        $agentOK=false;
    }


    if(AgentArtica($id) && $agentOK){
        $proxyUsers=td_proxy_curUsers($id);
        $agentver="<br><small>Artica: ".AgentArticaVersion($id)."</small>";
    }
    if(strlen(trim($agentJson->description))>2 && !is_null($agentJson->description)){
        $description=urldecode($agentJson->description);
        $description="<br><small>$description</small>";
    }

    if($agentOK) {
        $ver = td_agent_version($agentJson);
        $bell = "";
        if (is_object($agentJson) && property_exists($agentJson, "artica_bell")) {
            $bells = $agentJson->artica_bell;
            if ($bells > 0) {
                $bell = $tpl->td_href("<small class=\"label label-warning\"><i class=\"fa fa-bell\"></i> $bells</small>", "", "Loadjs('fw.netagents.bell.php?id=$id')") . "<br>";
            }
        }
    }


    // return array("hostname"=>"-","uptime"=>0,"cpu"=>0,"mem_pct"=>0);
    if(isset($main["hostname"])){
        return $tpl->td_href("<strong>{$main["hostname"]}</strong>",null,"Loadjs('$page?agent-info-js=$id')")."$proxyUsers&nbsp;$bell(<i>$ip:$port</i>)$description$agentver$ver";
    }
    return $tpl->td_href("$ip:$port",null,"Loadjs('$page?agent-info-js=$id')")."$description$agentver$ver";
}
function td_proxy_curUsers($id):string{
    $agent=new ArticaNetAgents($id);
    $SQUIDEnable=intval($agent->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        echo "// td_proxy_curUsers $id -> NO PROXY\n";
        return "";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/current/$id"),true);

    if(isset($json["Status"])) {
        if (!$json["Status"]) {
            echo "// {$json["Error"]}\n";
            return "";
        }
    }

    if(isset($json["unique_clients"])){
        $tpl=new template_admin();
        echo "// td_proxy_curUsers unique_clients [{$json["unique_clients"]}]\n";
        $users="<i class='fa-solid fa-people-group'></i>&nbsp;{$json["unique_clients"]}";
        return "&nbsp;( ".$tpl->td_href($users,"{internet_users}","Loadjs('fw.proxy.active_requests.php?id=$id')")." )";
    }
    echo "// td_proxy_curUsers unique_clients None??\n";
    return "";

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
    echo $tpl->form_outside("",$form,"{enrollment_token_explain}","{add}",$jsafter,"AsArticaMetaAdmin");
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
        return array("hostname"=>"Unknown","uptime"=>0,"cpu"=>0,"mem_pct"=>0,"HD"=>array(),
            "AgentVer"=>"0.0.0","peity"=>array(),"aptget"=>array());
    }

    if(isset($Main["Error"]) && !empty($Main["Error"])){
        VERBOSE($Main["Error"],__LINE__);
        return array("hostname"=>"Unknown","uptime"=>0,"cpu"=>0,"mem_pct"=>0,"HD"=>array(),
            "AgentVer"=>"0.0.0","peity"=>array(),"aptget"=>array());
    }

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
    $aptget=array();
    if(isset($Main["aptget"])){
        $aptget=$Main["aptget"];
    }

    $hwDisks=array();
    if(isset($Main["disks"]) && is_array($Main["disks"])){
        $hwDisks=$Main["disks"];
    }

    $GLOBALS["AGENT_STATUS"][$id]=array("hostname"=>$hostname,
        "uptime"=>$uptime,"cpu"=>$cpu,"mem_pct"=>$mem_pct,"mem_int"=>$memint,"HD"=>$HD,"ALLHD"=>$AllsHD,
        "AgentVer"=>$version,"peity"=>$peity,"aptget"=>$aptget,"DISKS"=>$hwDisks);

    if(isset($Main["cpu_pct"])){
        $GLOBALS["AGENT_STATUS"][$id]["cpu_pct"]=$Main["cpu_pct"];
    }

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
function reboot_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["reboot-js"]);
    if($id==0){
        return false;
    }
    $netagent=new ArticaNetAgents($id);
    $host=$netagent->GetAgentHostname();
    return $tpl->js_confirm_execute("{reboot} $host","reboot",$id,"dialogInstance2.close();");
}
function reboot_confirm():bool{
    $tpl=new template_admin();
    $id=intval($_POST["reboot"]);
    if($id==0){
        return false;
    }

    $netagent=new ArticaNetAgents($id);
    $host=$netagent->GetAgentHostname();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/system/$id/reboot",array());
    return admin_tracks("Meta: ask to reboot the host $host #$id");

}
function group_select_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog3("{groups}","$page?group-select-popup=yes&function=$function",550);
}

function agent_token_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id = intval($_GET["agent-token-popup"]);
    $jsafter="dialogInstance3.close();";
    echo $tpl->BigTextField("netagent-token-value|netagent-save-token:$id",
        "{enrollment_token}","{enrollment_token_explain}",
        "",$jsafter,null,null,"AsArticaMetaAdmin");
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
    $agentver="";
    $gid=0;
    if(isset($_GET["gpid"])){
        $gid=intval($_GET["gpid"]);
    }
    if(isset($_GET["agentver"])){
        $agentver="&agentver=".urlencode($_GET["agentver"]);
    }

    echo "<div id='agents_upgrade_popup'></div><script>LoadAjaxSilent('agents_upgrade_popup','$page?agents-upgrade-api=$id&gpid=$gid$agentver')</script>";
    return true;
    }
function agents_upgrade_api():bool{
    $tpl = new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $gid=0;
    $agentver="";
    if(isset($_GET["gpid"])){
        $gid=intval($_GET["gpid"]);
    }
    if(isset($_GET["agentver"])){
        $agentver="&agentver=".urlencode($_GET["agentver"]);
    }
    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $preselectedAgent = isset($_GET["agents-upgrade-api"]) ? intval($_GET["agents-upgrade-api"]) : 0;
    $agentsJson = json_decode($sock->REST_API("/netagents/list?status=online&enabled=1&gpid=$gid$agentver"));
    $f = array();
    $topbuttons[] = array("LoadAjaxSilent('agents_upgrade_popup','$page?agents-upgrade-ssh={$_GET["agents-upgrade-api"]}&gpid=$gid$agentver')", ico_terminal, "{use_ssh}");
    $topbuttons[] = array("$('.agent-checkbox').prop('checked',true);","fas fa-check-square","{select_all}");
    $topbuttons[] = array("$('.agent-checkbox').prop('checked',false);","fas fa-square","{unselect_all}");
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
            if($gid>0){
                $checked="checked";
            }
            if(strlen($agentver)>2){
                $checked="checked";
            }

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
    $agentver="";
    $gid=0;
    if(isset($_GET["gpid"])){
        $gid=intval($_GET["gpid"]);
    }
    if(isset($_GET["agentver"])){
        $agentver="&agentver=".urlencode($_GET["agentver"]);
    }


    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $preselectedAgent = isset($_GET["agents-upgrade-ssh"]) ? intval($_GET["agents-upgrade-ssh"]) : 0;
    VERBOSE("/netagents/list?enabled=1",__LINE__);
    $agentsJson = json_decode($sock->REST_API("/netagents/list?enabled=1&gpid=$gid$agentver"),true);

    $f = array();
    $topbuttons[] = array("LoadAjaxSilent('agents_upgrade_popup','$page?agents-upgrade-api=$preselectedAgent&gpid=$gid$agentver')", ico_terminal, "{use_API}");
    $topbuttons[] = array("$('.agent-checkbox').prop('checked',true);","fas fa-check-square","{select_all}");
    $topbuttons[] = array("$('.agent-checkbox').prop('checked',false);","fas fa-square","{unselect_all}");


    $f[]="<div style='margin-top:10px;margin-bottom:10px;'>";
    $f[]=$tpl->th_buttons($topbuttons);
    $f[]="</div>";
    $f[] = "<div class='form-group'>";

    $f[] = "<label>{select_agents} &raquo;&raquo; v$CurVer &raquo;&raquo; {use_ssh}</label>";
    $f[] = "<div style='max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;'>";

    if (isset($agentsJson["agents"]) && is_array($agentsJson["agents"])) {
        foreach ($agentsJson["agents"] as $agent) {


            $checked = ($preselectedAgent == $agent["id"]) ? "checked" : "";
            if($gid>0){
                $checked="checked";
            }
            if(strlen($agentver)>2){
                $checked="checked";
            }
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
        if(!is_object($json) || !property_exists($json,"upgrade_id")){
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