<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
// Artica Meta multi-tenant : actions de l'infogerant (routes global-only du daemon : ajout
// direct, jetons, enrolement, verification de tout le parc, mises a jour SSH, adresse de
// connexion, liste noire). Refusees ici a une session au perimetre d'un tenant.
natn_provider_gate(array("agent-add-js","agent-add-popup","netagent-hostname","agent-token-js","agent-token-popup",
    "netagent-save-token","checkall","agent-enroll","agents-upgrade-ssh","agent-upgrade-ssh","agent-upgrade-sshresult",
    "agent-address-js","agent-address-popup","blacklisted-js"));
// Actions de noeud qui n'avaient aucun controle serveur : reservees aux comptes Meta.
natn_node_gate(array("reboot-js","reboot","artica-web-restart-js","agent-flush-js","articaweb-unix","articaweb-unix-off",
    "agent-delete-js","agent-delete","apt-get-update-js","agents-upgrade-api","agent-upgrade-perform","nad-refresh"));
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
if(isset($_GET["nad-refresh"])){nad_refresh();exit;}
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
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats"])){stats();exit;}
if(isset($_GET["agents-list"])){agents_list();exit;}
if(isset($_GET["agents-table"])){agents_table();exit;}
if(isset($_GET["agents-live"])){agents_live();exit;}
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
function agent_delete_js():bool{
    $tpl=new template_admin();
    $md=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md"] ?? ""));
    $id=intval($_GET["agent-delete-js"]);
    $ArticaNetAgents=new ArticaNetAgents($id);
    $hostname=$ArticaNetAgents->GetAgentHostname();
    if(strlen($hostname) < 1){
        $hostname="#$id";
    }

    return $tpl->js_confirm_delete($hostname,"agent-delete",$id,"$('#$md').remove();if(typeof NagtLiveReload==='function'){NagtLiveReload();}");
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

    $v=intval($_GET["comp-view-js"])==1?1:0;
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    header("content-type: application/x-javascript");
    // Pentest 2026-11-04 : comp-view-js etait recopie sans encodage dans la page (XSS).
    // Cookie pose sans Set_Cookie() (js/cookies.js, charge en differe par head.js) et classe
    // appliquee tout de suite : la bascule ne depend plus ni de ce script ni du rechargement,
    // qui ne fait que redessiner le bouton du bandeau (Compact <-> Extended).
    echo "(function(){document.cookie='META_COMP_VIEW=$v; path=/; max-age=31536000; SameSite=Lax';\n";
    echo "jQuery('.nagt-card').toggleClass('nagt-compact',".($v==1?"true":"false").");\n";
    if($function!=""){echo "if(typeof window['$function']==='function'){window['$function']();}\n";}
    echo "})();";
}
// Liste des agents (maquette stitch_artica_meta_agents)
// Partie fixe : synthese (emplacement), barre de filtres, TINY. Partie vivante (#nagt-live) :
// synthese + tableau, rechargee toutes les 10 s sans toucher a la barre (la recherche garde le focus).
function agents_form():bool{
    $page=CurrentPageName();
    echo "<div id='nagt-host'></div><script>
function NagtReloadAll(){ LoadAjax('nagt-host','$page?agents-table=yes'); }
NagtReloadAll();
</script>";
    return true;
}

function agents_table():bool{
    $tpl=new template_admin();
    if(!nai_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $m=nagt_model();
    $html=array();
    $html[]="<div class='nagt'>";
    $html[]="<div id='nagt-sum'></div>";
    if($m["error"]=="" && count($m["agents"])>0){ $html[]=nagt_toolbar($m); }
    $html[]="<div id='nagt-live'>";
    echo awc_table_style().nagt_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    nagt_live_render($m);
    echo "</div></div>".nagt_js().nagt_tiny($m);
    return true;
}

// ?agents-live=yes : ne rend que la partie vivante.
function agents_live():bool{
    $tpl=new template_admin();
    if(!nai_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    nagt_live_render(nagt_model());
    return true;
}

function nagt_live_render(array $m){
    $tpl=new template_admin();
    $h=array();
    if($m["error"]!=""){
        $h[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation'></i> ".htmlspecialchars($m["error"])."</div>";
    }elseif(count($m["agents"])==0){
        $h[]=nagt_empty();
    }else{
        $h[]="<div class='nagt-sum-src'>".nagt_summary($m)."</div>";
        $h[]=nagt_table($m);
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
    $att=0;
    foreach($m["agents"] as $a){ if(in_array("crit",$a["attn"])){$att++;} }
    // Le compteur voyage dans un attribut : au premier rendu, ce script s'execute avant nagt_js().
    echo "<span id='nagt-live-att' data-n='$att' hidden></span><script>if(typeof NagtLive==='function'){NagtLive();}</script>";
}

function nagt_t($k):string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body("{".$k."}");
}

function nagt_ago($ts):string{
    $d=time()-intval($ts);
    if($d<60){return nagt_t("artrepo_ago_now");}
    if($d<3600){return str_replace("%s",intval($d/60),nagt_t("artrepo_ago_min"));}
    if($d<172800){return str_replace("%s",intval($d/3600),nagt_t("artrepo_ago_hours"));}
    return str_replace("%s",intval($d/86400),nagt_t("artrepo_ago_days"));
}

function nagt_gb($bytes):string{
    $b=floatval($bytes);
    if($b<=0){return "0";}
    $g=$b/1073741824;
    return $g>=100?sprintf("%.0f",$g):sprintf("%.1f",$g);
}

// Un seul passage sur le demon : liste, puis statut / logiciels / reglages en cache par agent.
function nagt_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array("agents"=>array(),"error"=>"","group"=>0,"curver"=>trim((string)$sock->GET_INFO("DebianAgentStorageVersion")),
        "count"=>array("total"=>0,"online"=>0,"offline"=>0,"pending"=>0,"upd"=>0,"apt"=>0,"artica"=>0,"linux"=>0),"os"=>array());
    $GLOBALS["NAGT_CURVER"]=$m["curver"];
    $grp=isset($_COOKIE["META_COMP_GROUP"])?intval($_COOKIE["META_COMP_GROUP"]):0;
    $m["group"]=$grp;
    $raw=$grp>0?$sock->REST_API("/netagents/group/$grp/list"):$sock->REST_API("/netagents/list");
    $json=json_decode($raw);
    if(!is_object($json)){$m["error"]="Artica REST API: no answer";return $m;}
    if(isset($json->Status) && !$json->Status && !empty($json->Error)){$m["error"]=(string)$json->Error;return $m;}
    if(!isset($json->agents) || !is_array($json->agents)){return $m;}
    foreach($json->agents as $ag){
        $a=nagt_agent($ag,$m["curver"]);
        $m["agents"][]=$a;
        $m["count"]["total"]++;
        if(isset($m["count"][$a["state"]])){$m["count"][$a["state"]]++;}
        if(in_array("upd",$a["attn"])){$m["count"]["upd"]++;}
        if($a["apt"]>0){$m["count"]["apt"]++;}
        $m["count"][$a["artica"]?"artica":"linux"]++;
        if($a["debian"]>0){$m["os"][$a["debian"]]=true;}
    }
    krsort($m["os"]);
    usort($m["agents"],function($x,$y){ return strcasecmp($x["hostname"],$y["hostname"]); });
    return $m;
}

// Tenant d'un noeud (plus grand tenant de ses groupes, 0 = global), vu par l'infogerant seulement :
// une session de tenant ne voit que ses noeuds (0 rendu). Une seule lecture de /netagents/groups
// par requete ; lecture en echec => 0 (le daemon reste la barriere, ruling R-F1).
function nagt_group_tenants():array{
    if(isset($GLOBALS["NAGT_GROUP_TENANTS"])){return $GLOBALS["NAGT_GROUP_TENANTS"];}
    $map=array();
    if(!meta_tenant_session()){
        $groups=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
        if(is_array($groups)){
            foreach($groups as $g){
                if(is_object($g) && isset($g->id)){$map[intval($g->id)]=intval(isset($g->tenant_id)?$g->tenant_id:0);}
            }
        }
    }
    $GLOBALS["NAGT_GROUP_TENANTS"]=$map;
    return $map;
}
function nagt_agent_tenant($ag):int{
    if(!isset($ag->groups) || (!is_object($ag->groups) && !is_array($ag->groups))){return 0;}
    $map=nagt_group_tenants();
    $t=0;
    foreach((array)$ag->groups as $gid){
        $gid=intval($gid);
        if(isset($map[$gid]) && $map[$gid]>$t){$t=$map[$gid];}
    }
    return $t;
}
// Ruling R-F1 : la console web d'un noeud de tenant n'est jamais ouverte par l'infogerant a travers
// Meta (le daemon la refuse en 403) : le client a root sur ses noeuds, un script de sa console
// s'executerait dans l'origine Meta avec la session de l'infogerant. Le terminal reste disponible.
function nagt_console_allowed(array $a):bool{
    if(!$a["artica"]){return false;}
    if(meta_tenant_session()){return true;}
    return intval($a["tenant"]??0)===0;
}

// Agent en connexion inversee : son adresse enregistree est un nom factice "<uuid>.invalid", port 0.
function nagt_is_reverse(string $ip):bool{
    return substr($ip,-8)===".invalid";
}

// Premieres adresses IPv4 du LAN de l'agent, tirees de son dernier statut (network_interfaces) :
// adresses privees (RFC 1918) d'abord, puis les autres adresses globales ; jamais le loopback.
function nagt_lan_ips($st,int $max=2):array{
    if(!is_array($st) || !isset($st["network_interfaces"]) || !is_array($st["network_interfaces"])){return array();}
    $priv=array();$other=array();
    foreach($st["network_interfaces"] as $nic){
        if(!is_array($nic)){continue;}
        if(($nic["type"]??"")==="loopback" || ($nic["name"]??"")==="lo"){continue;}
        if(!isset($nic["ipv4_addresses"]) || !is_array($nic["ipv4_addresses"])){continue;}
        foreach($nic["ipv4_addresses"] as $ad){
            $ip=is_array($ad)?trim((string)($ad["address"]??"")):"";
            if($ip=="" || !filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){continue;}
            if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_RES_RANGE)){continue;}
            if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE)){$priv[$ip]=true;}else{$other[$ip]=true;}
        }
    }
    return array_slice(array_keys($priv+$other),0,$max);
}

// Adresse affichee : "ip:port", ou pour un agent inverse ses adresses LAN ("1.2.3.4, 1.2.3.5").
function nagt_display_address(string $ip,int $port,array $lan):string{
    if(!nagt_is_reverse($ip)){return "$ip:$port";}
    return count($lan)>0?implode(", ",$lan):"—";
}

function nagt_agent($ag,string $curver):array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $id=intval($ag->id);
    $a=array("id"=>$id,"ip"=>(string)$ag->ip_address,"port"=>intval($ag->port),"state"=>(string)$ag->status,
        "hostname"=>"","description"=>"","artica"=>!empty($ag->artica),"debian"=>intval(isset($ag->debian_version)?$ag->debian_version:0),
        "agentver"=>isset($ag->version)?(string)$ag->version:"","upd"=>false,"bell"=>intval(isset($ag->artica_bell)?$ag->artica_bell:0),
        "enabled"=>!isset($ag->enabled)||$ag->enabled,"blacklisted"=>!empty($ag->blacklisted),
        "artica_ver"=>isset($ag->artica_version)?(string)$ag->artica_version:"","sp"=>intval(isset($ag->artica_service_pack)?$ag->artica_service_pack:0),
        "hf"=>isset($ag->artica_hotfix)?(string)$ag->artica_hotfix:"","license"=>"","license_title"=>"",
        "last_seen"=>nai_epoch(isset($ag->last_seen)?$ag->last_seen:""),"refreshing"=>"","enroll"=>false,
        "health"=>null,"apt"=>0,"apt_running"=>false,"roles"=>array(),"proxy"=>false,"users"=>-1,"attn"=>array());
    $a["description"]=isset($ag->description)&&strlen(trim((string)$ag->description))>2?urldecode((string)$ag->description):"";
    $a["tenant"]=nagt_agent_tenant($ag);

    // Licence (agents Artica)
    if($a["artica"] && isset($ag->artica_license) && is_object($ag->artica_license)){
        $l=$ag->artica_license;
        if(!empty($l->gold_license)){$a["license"]="gold";}
        elseif(!empty($l->entreprise_license)){$a["license"]="enterprise";}
        else{$a["license"]="community";}
        $st=isset($l->Info->license_status)?(string)$l->Info->license_status:"";
        $eop=isset($l->end_of_period_text)?(string)$l->end_of_period_text:"";
        $a["license_title"]=trim($st.($eop!=""?" - $eop":""));
    }
    if($a["agentver"]!="" && $curver!="" && version_compare($curver,$a["agentver"],">")){$a["upd"]=true;}

    // Statut detaille (sante, APT) : seulement pour un agent en ligne, sinon ce sont des valeurs perimees.
    $st=json_decode($sock->REST_API("/netagents/status/$id"),true);
    $a["addr"]=nagt_display_address($a["ip"],$a["port"],array());
    if(is_array($st) && empty($st["Error"])){
        if(!empty($st["hostname"])){$a["hostname"]=(string)$st["hostname"];}
        $a["addr"]=nagt_display_address($a["ip"],$a["port"],nagt_lan_ips($st));
        if($a["state"]=="online"){
            $h=array("cpu"=>isset($st["cpu_pct"])?floatval($st["cpu_pct"]):-1,"mem"=>-1,"mem_used"=>0,"mem_total"=>0,
                "disk"=>-1,"disk_total"=>0,"load"=>"","uptime"=>isset($st["uptime"])?(string)$st["uptime"]:"","spark"=>array());
            if(isset($st["memory"]["used_percent"])){$h["mem"]=floatval($st["memory"]["used_percent"]);$h["mem_used"]=floatval($st["memory"]["used_bytes"]);$h["mem_total"]=floatval($st["memory"]["total_bytes"]);}
            if(isset($st["disk"]) && is_array($st["disk"])){
                foreach($st["disk"] as $d){ if(isset($d["mount_point"]) && $d["mount_point"]=="/"){$h["disk"]=floatval($d["used_percent"]);$h["disk_total"]=floatval($d["total_bytes"]);} }
            }
            if(isset($st["cpu_load"]["load_1"])){$h["load"]=sprintf("%.2f, %.2f, %.2f",$st["cpu_load"]["load_1"],$st["cpu_load"]["load_5"],$st["cpu_load"]["load_15"]);}
            // L'historique arrive du plus recent au plus ancien : on le remet dans l'ordre chronologique.
            $h["spark"]=array_reverse(td_cpu_graphs($id));
            $a["health"]=$h;
            if(isset($st["aptget"]) && is_array($st["aptget"])){
                $a["apt"]=intval(isset($st["aptget"]["upgrade_required"])?$st["aptget"]["upgrade_required"]:0);
                $a["apt_running"]=!empty($st["aptget"]["apt_running"]);
            }
        }
    }
    if($a["hostname"]==""){$a["hostname"]=nagt_is_reverse($a["ip"])?$a["addr"]:$a["ip"];}

    if($a["state"]=="online"){
        $r=AgentRefreshing($id);
        if(strlen($r)>3){$a["refreshing"]=$r;}
    }else{
        // Sans certificat client, l'action utile est l'inscription, pas un etat.
        $ex=json_decode($sock->REST_API("/netagents/export/$id"));
        if(!is_object($ex) || !property_exists($ex,"client_cert")){$a["enroll"]=true;}
    }

    $a["roles"]=nagt_roles($id,$a["artica"]);
    foreach($a["roles"] as $r){ if($r[0]=="proxy"){$a["proxy"]=true;} }
    if($a["proxy"] && $a["state"]=="online"){
        $cur=json_decode($sock->REST_API("/netagents/realtimedb/current/$id"),true);
        if(is_array($cur) && isset($cur["unique_clients"]) && !(isset($cur["Status"]) && !$cur["Status"])){$a["users"]=intval($cur["unique_clients"]);}
    }

    // "crit" = a traiter (filtre Needs attention, bordure de ligne) ; upd/apt ont leur propre filtre.
    if($a["state"]=="offline"){$a["attn"][]="off";$a["attn"][]="crit";}
    if($a["enroll"]){$a["attn"][]="enroll";if(!in_array("crit",$a["attn"])){$a["attn"][]="crit";}}
    if(is_array($a["health"]) && $a["health"]["disk"]>=80){$a["attn"][]="disk";if(!in_array("crit",$a["attn"])){$a["attn"][]="crit";}}
    if(is_array($a["health"]) && $a["health"]["mem"]>=90){$a["attn"][]="mem";if(!in_array("crit",$a["attn"])){$a["attn"][]="crit";}}
    if($a["upd"]){$a["attn"][]="upd";}
    if($a["apt"]>0){$a["attn"][]="apt";}
    return $a;
}

// Politique DNS Cache de chaque agent : agent_id => id de politique. Une seule passe par affichage
// (liste des politiques, puis nœuds de chaque politique dnscache) au lieu d'un appel par agent.
// Les nœuds sont ceux que voit la console de la politique : rattachés directement ou par un groupe,
// esclaves de cluster compris (gérés par leur maître) ; un nœud en attente de libération n'est
// plus couvert. Plusieurs politiques sur un nœud : la première de la liste.
function nagt_dnscache_policies():array{
    if(isset($GLOBALS["NAGT_DNSCACHE_POLICIES"])){return $GLOBALS["NAGT_DNSCACHE_POLICIES"];}
    $map=array();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $json=json_decode($sock->REST_API("/netagents/policies/list"),true);
    $list=(is_array($json) && !empty($json["Status"]) && isset($json["policies"]) && is_array($json["policies"]))?$json["policies"]:array();
    foreach($list as $p){
        if(!is_array($p) || ($p["type"] ?? "")!=="dnscache"){continue;}
        $pid=intval($p["id"] ?? 0);
        if($pid<=0){continue;}
        $nodes=json_decode($sock->REST_API("/netagents/policies/$pid/nodes"),true);
        if(!is_array($nodes) || empty($nodes["Status"])){continue;}
        $all=array_merge(is_array($nodes["nodes"] ?? null)?$nodes["nodes"]:array(),is_array($nodes["excluded"] ?? null)?$nodes["excluded"]:array());
        foreach($all as $n){
            if(!is_array($n) || !empty($n["release_pending"])){continue;}
            $aid=intval($n["agent_id"] ?? 0);
            if($aid>0 && !isset($map[$aid])){$map[$aid]=$pid;}
        }
    }
    $GLOBALS["NAGT_DNSCACHE_POLICIES"]=$map;
    return $map;
}

// Roles d'apres les reglages en cache de l'agent (memes criteres que LabelType(), sans le retour anticipe).
function nagt_roles(int $id,bool $artica):array{
    $r=array();
    $agent=new ArticaNetAgents($id);
    if(!$artica){
        $st=$agent->Status();
        if(!empty($st["dhcp3"]["exists"]) && !empty($st["dhcp3"]["installed"])){$r[]=array("dhcp","{nagt_role_dhcp}","Loadjs('fw.netagents.dhcp.php?dhcp-js=$id')");}
        // Sans Artica, Unbound est piloté par l'agent lui-même : page de l'agent.
        if(!empty($st["unbound"]["installed"])){$r[]=array("dns","{nagt_role_dns}","Loadjs('fw.netagents.unbound.php?unbound-js=$id')");}
        return $r;
    }
    if(intval($agent->GET_INFO("Enablehacluster"))==1){$r[]=array("hacluster","{nagt_role_hacluster_lb}","Loadjs('fw.netagents.hacluster.nodes.php?nodes-js=$id')");}
    // Client HaCluster : etiquette cliquable (fenetre lecture seule fw.netagents.hacluster.client.php), Squid actif ou non.
    $squid=intval($agent->GET_INFO("SQUIDEnable"))==1;
    if(intval($agent->GET_INFO("HaClusterClient"))==1){
        $r[]=array($squid?"proxy":"hacluster","{nagt_role_hacluster_client}","Loadjs('fw.netagents.hacluster.client.php?client-js=$id')");
    }elseif($squid){$r[]=array("proxy","{nagt_role_proxy}","");}
    if(intval($agent->GET_INFO("EnableDHCPServer"))==1){$r[]=array("dhcp","{nagt_role_dhcp}","Loadjs('fw.netagents.dhcp.php?dhcp-js=$id')");}
    // Avec Artica, le cache DNS se règle par la politique Meta « DNS Cache » : pas de politique, pas de lien.
    if(intval($agent->GET_INFO("UnboundEnabled"))==1){
        $pols=nagt_dnscache_policies();
        $js=isset($pols[$id])?"Loadjs('fw.netagents.policies.dnscache.php?policy-js={$pols[$id]}')":"";
        $r[]=array("dns","{nagt_role_dns}",$js);
    }
    if(intval($agent->GET_INFO("EnableDNSDist"))==1){$r[]=array("dnsdist","{nagt_role_dnsdist}","");}
    if(intval($agent->GET_INFO("EnablePostfix"))==1){$r[]=array("postfix","{nagt_role_postfix}","");}
    // ClamAV : etiquette cliquable (fenetre lecture seule fw.netagents.clamav.state.php : service, freshclam, bases).
    if(intval($agent->GET_INFO("EnableClamavDaemon"))==1){$r[]=array("clamav","{nagt_role_clamav}","Loadjs('fw.netagents.clamav.state.php?state-js=$id')");}
    // Mêmes réglages que la carte RDS Proxy de fw.netagents.features.php : installé ET actif.
    if((string)$agent->GET_INFO("APP_RDPPROXY_INSTALLED")==="1" && (string)$agent->GET_INFO("EnableRDPProxy")==="1"){
        $r[]=array("rds","{nagt_role_rds}","");
    }
    return $r;
}

function nagt_fact(string $key,string $ico,string $label,int $n,string $sub,string $tone):string{
    $cls="nagt-fact";
    if($tone!="" && $n>0){$cls.=" nagt-fact-$tone";}
    if($tone=="ok"){$cls="nagt-fact nagt-fact-ok";}
    $subh=$sub!=""?" <span class='nagt-fact-sub'>$sub</span>":"";
    return "<button type='button' class='$cls' data-fact='$key' onclick=\"NagtFact('$key')\"><span class='nagt-fact-ico'><i class='$ico'></i></span><span class='nagt-fact-txt'><span class='nagt-fact-k'>$label</span><span class='nagt-fact-v'>$n$subh</span></span></button>";
}

function nagt_summary(array $m):string{
    $c=$m["count"];
    return "<section class='nagt-sum'>"
        .nagt_fact("all","fas fa-network-wired","{nagt_agents}",$c["total"],"","")
        .nagt_fact("online","fas fa-circle-check","{online}",$c["online"],"",$c["online"]==$c["total"]?"ok":"")
        .nagt_fact("offline","fas fa-circle-xmark","{offline}",$c["offline"],"","err")
        .nagt_fact("pending","fas fa-hourglass-half","{pending}",$c["pending"],"","warn")
        .nagt_fact("upd","fas fa-arrow-up","{nagt_agent_updates}",$c["upd"],"{nagt_available}","warn")
        .nagt_fact("apt","fas fa-box-open","{nagt_apt_upgrades}",$c["apt"],"{nagt_agents_lc}","warn")
        ."</section>";
}

function nagt_toolbar(array $m):string{
    $h=array();
    $h[]="<section class='ak-tools'>";
    $h[]="<label class='ak-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='nagt-q' placeholder=\"{nagt_search_ph}\" aria-label=\"{nagt_search_ph}\" oninput='NagtApply(1)'></label>";
    $h[]="<div class='ak-seg' role='radiogroup' aria-label=\"{nagt_filter_state}\" data-k='state'>";
    foreach(array("all"=>"{nagt_all}","online"=>"{online}","offline"=>"{offline}","pending"=>"{pending}") as $k=>$l){
        $h[]="<button type='button' role='radio' aria-checked='".($k=="all"?"true":"false")."' class='ak-seg-b".($k=="all"?" on":"")."' data-v='$k' onclick=\"NagtSet('state','$k')\">$l</button>";
    }
    $h[]="</div>";
    $h[]="<button type='button' role='switch' aria-checked='false' class='nagt-attn' id='nagt-attn' onclick=\"NagtSet('attn',window.NagtState.attn==='any'?'':'any')\"><i class='fas fa-triangle-exclamation'></i> {nagt_needs_attention} <span class='nagt-attn-n' id='nagt-attn-n'>0</span></button>";
    $h[]="<div class='ak-seg' role='radiogroup' aria-label=\"{nagt_filter_type}\" data-k='type'><span class='nagt-seg-k'>{nagt_type}</span>";
    foreach(array("all"=>"{nagt_all}","artica"=>"Artica","linux"=>"Linux") as $k=>$l){
        $h[]="<button type='button' role='radio' aria-checked='".($k=="all"?"true":"false")."' class='ak-seg-b".($k=="all"?" on":"")."' data-v='$k' onclick=\"NagtSet('type','$k')\">$l</button>";
    }
    $h[]="</div>";
    if(count($m["os"])>1){
        $h[]="<div class='ak-seg' role='radiogroup' aria-label=\"{nagt_filter_os}\" data-k='os'><span class='nagt-seg-k'>OS</span>";
        $h[]="<button type='button' role='radio' aria-checked='true' class='ak-seg-b on' data-v='all' onclick=\"NagtSet('os','all')\">{nagt_all}</button>";
        foreach(array_keys($m["os"]) as $v){
            $h[]="<button type='button' role='radio' aria-checked='false' class='ak-seg-b' data-v='$v' onclick=\"NagtSet('os','$v')\">Debian $v</button>";
        }
        $h[]="</div>";
    }
    $h[]="<span class='nagt-count' id='nagt-count'></span>";
    $h[]="<button type='button' class='nagt-ico-btn' aria-label=\"{refresh}\" title=\"{refresh}\" onclick='NagtLiveReload()'><i class='fas fa-arrows-rotate'></i></button>";
    $h[]="</section>";
    return @implode("\n",$h);
}

function nagt_table(array $m):string{
    $compact=isset($_COOKIE["META_COMP_VIEW"]) && intval($_COOKIE["META_COMP_VIEW"])==1;
    $h=array();
    $h[]="<section class='nagt-card".($compact?" nagt-compact":"")."'><table class='nagt-tbl'>";
    $h[]="<thead><tr><th scope='col'>{nagt_agent}</th><th scope='col'>{nagt_state}</th><th scope='col'>{nagt_role}</th><th scope='col'>{nagt_versions}</th><th scope='col'>{nagt_health}</th><th scope='col' class='nagt-col-pend'>{nagt_pending}</th><th scope='col' class='nagt-r'>{nagt_actions}</th></tr></thead>";
    $h[]="<tbody id='nagt-rows'>";
    foreach($m["agents"] as $a){ $h[]=nagt_row($a); }
    $h[]="<tr id='nagt-nomatch' style='display:none'><td colspan='7'><div class='nagt-empty-s'><i class='fas fa-filter-circle-xmark'></i> {nagt_nomatch} <button type='button' class='ak-link' onclick='NagtClear()'>{hfx_clear}</button></div></td></tr>";
    $h[]="</tbody></table>";
    $c=$m["count"];
    $total=str_replace(array("%a","%b","%c"),array("<strong>".$c["total"]."</strong>",$c["artica"],$c["linux"]),nagt_t("nagt_total_registered"));
    $h[]="<div class='nagt-foot'><span>$total</span><span class='ak-pg' id='nagt-pager'></span></div></section>";
    return @implode("\n",$h);
}

function nagt_row(array $a):string{
    $page=CurrentPageName();
    $id=$a["id"];
    $q=strtolower($a["hostname"]." ".$a["addr"]." ".$a["description"]." ".$a["artica_ver"]." ".$a["agentver"]." ".$a["hf"]." debian ".$a["debian"]);
    $cls="nagt-row";
    if($a["state"]=="offline"){$cls.=" nagt-row-off";}
    elseif(in_array("crit",$a["attn"])){$cls.=" nagt-row-attn";}
    $data="data-state='".htmlspecialchars($a["state"],ENT_QUOTES)."' data-type='".($a["artica"]?"artica":"linux")."' data-os='{$a["debian"]}' data-attn='".implode(" ",$a["attn"])."' data-q='".htmlspecialchars($q,ENT_QUOTES)."'";
    return "<tr class='$cls' id='tr-agent-$id' $data>"
        ."<td>".nagt_cell_agent($a)."</td>"
        ."<td>".nagt_cell_state($a)."</td>"
        ."<td>".nagt_cell_roles($a)."</td>"
        ."<td>".nagt_cell_versions($a)."</td>"
        ."<td>".nagt_cell_health($a)."</td>"
        ."<td class='nagt-col-pend'>".nagt_cell_pending($a)."</td>"
        ."<td class='nagt-r'>".nagt_cell_actions($a)."</td></tr>";
}

function nagt_cell_agent(array $a):string{
    $page=CurrentPageName();
    $id=$a["id"];
    $ico=$a["artica"]?"fas fa-shield-halved":"fab fa-linux";
    $pills="";
    if($a["users"]>=0){
        $pills.=" <a href=\"javascript:blur();\" class='ak-pill ak-accent' title=\"{internet_users}\" onclick=\"Loadjs('fw.proxy.active_requests.php?id=$id')\"><i class='fas fa-people-group'></i> {$a["users"]}</a>";
    }
    if($a["bell"]>0){
        $pills.=" <a href=\"javascript:blur();\" class='ak-pill ak-warn' title=\"{nagt_notifications}\" onclick=\"Loadjs('fw.netagents.bell.php?id=$id')\"><i class='fas fa-bell'></i> {$a["bell"]}</a>";
    }
    $desc=$a["description"]!=""?"<div class='nagt-desc' title='".htmlspecialchars($a["description"],ENT_QUOTES)."'>".htmlspecialchars($a["description"])."</div>":"";
    return "<div class='nagt-ident'><span class='nagt-tile".($a["artica"]?"":" nagt-tile-linux")."'><i class='$ico'></i></span><div class='nagt-ident-t'>"
        ."<div class='nagt-host'><a href=\"javascript:blur();\" onclick=\"Loadjs('$page?agent-info-js=$id')\">".htmlspecialchars($a["hostname"])."</a>$pills</div>"
        ."<div class='ak-mono ak-muted nagt-ip'>".htmlspecialchars($a["addr"])."</div>$desc</div></div>";
}

function nagt_cell_state(array $a):string{
    return nagt_cell_state_badge($a).nagt_cell_lastseen($a);
}
function nagt_cell_state_badge(array $a):string{
    $page=CurrentPageName();
    if($a["enroll"] && meta_provider_or_sysadmin()){
        return "<button type='button' class='ak-btn' onclick=\"Loadjs('$page?agent-enroll={$a["id"]}')\"><i class='fas fa-user-lock'></i> {enroll}</button>";
    }
    $extra="";
    if(!$a["enabled"]){$extra.="<div class='nagt-sub'>{nagt_disabled}</div>";}
    if($a["blacklisted"]){$extra.="<div class='nagt-sub'>{nagt_blacklisted}</div>";}
    if($a["refreshing"]!=""){
        return "<span class='ak-pill ak-info'><i class='fas fa-arrows-rotate ak-spin'></i> {".htmlspecialchars($a["refreshing"])."}</span>$extra";
    }
    if($a["state"]=="online"){return "<span class='ak-pill ak-ok'><span class='ak-dot'></span> {online}</span>$extra";}
    if($a["state"]=="offline"){return "<span class='ak-pill ak-err'><i class='fas fa-circle-xmark'></i> {offline}</span>$extra";}
    if($a["state"]=="pending"){return "<span class='ak-pill ak-warn'><i class='fas fa-hourglass-half'></i> {pending}</span>$extra";}
    return "<span class='ak-pill ak-off'>".htmlspecialchars($a["state"])."</span>$extra";
}

function nagt_cell_roles(array $a):string{
    if(count($a["roles"])==0){
        return $a["artica"]?"<span class='ak-muted'>&mdash;</span>":"<span class='nagt-tag nagt-tag-muted'>{nagt_linux_node}</span>";
    }
    $out=array();
    foreach($a["roles"] as $r){
        $cls="nagt-tag".($r[0]=="proxy"||$r[0]=="hacluster"?" nagt-tag-acc":"").($r[0]=="clamav"?" nagt-tag-clam":"");
        $out[]=$r[2]!=""?"<a href=\"javascript:blur();\" class='$cls' onclick=\"{$r[2]}\">{$r[1]}</a>":"<span class='$cls'>{$r[1]}</span>";
    }
    return "<div class='nagt-tags'>".@implode("",$out)."</div>";
}

function nagt_cell_versions(array $a):string{
    $page=CurrentPageName();
    $h="";
    if($a["artica"] && $a["artica_ver"]!=""){
        $h.="<div class='ak-mono nagt-strong' style='white-space:nowrap'>Artica ".htmlspecialchars($a["artica_ver"])."</div>";
        $sub=array();
        if($a["sp"]>0){$sub[]="SP ".$a["sp"];}
        if(strlen($a["hf"])>3){$sub[]="HF ".htmlspecialchars($a["hf"]);}
        if(count($sub)>0){$h.="<div class='nagt-sub ak-mono'>".implode(" &middot; ",$sub)."</div>";}
    }
    if($a["agentver"]!=""){
        if($a["upd"]){
            $h.="<a href=\"javascript:blur();\" class='ak-pill ak-warn nagt-upd' title=\"{nagt_update_agent}\" onclick=\"Loadjs('$page?agents-upgrade-js={$a["id"]}')\"><i class='fas fa-arrow-up'></i> v".htmlspecialchars($a["agentver"])." &rarr; v".htmlspecialchars($GLOBALS["NAGT_CURVER"])."</a>";
        }else{
            $h.="<div class='nagt-sub'>{nagt_agent} v".htmlspecialchars($a["agentver"])."</div>";
        }
    }
    $h.=nagt_cell_system($a);
    return $h;
}

function nagt_cell_system(array $a):string{
    $id=$a["id"];
    $h=$a["debian"]>0?"<div class='nagt-sys'><span>Debian {$a["debian"]}</span>":"<div class='nagt-sys'>";
    $t=htmlspecialchars($a["license_title"],ENT_QUOTES);
    if($a["license"]=="gold"){$h.="<a href=\"javascript:blur();\" class='nagt-tag nagt-tag-acc' title='$t' onclick=\"Loadjs('fw.netagents.license.php?id=$id')\"><i class='fas fa-award'></i> Gold</a>";}
    elseif($a["license"]=="enterprise"){$h.="<a href=\"javascript:blur();\" class='nagt-tag' title='$t' onclick=\"Loadjs('fw.netagents.license.php?id=$id')\"><i class='fas fa-building'></i> Enterprise</a>";}
    elseif($a["license"]=="community"){$h.="<span class='nagt-tag nagt-tag-muted' title='$t'><i class='fas fa-users'></i> Community</span>";}
    return $h."</div>";
}

function nagt_bar_cls(float $p):string{
    if($p>=90){return "ak-err";}
    if($p>=70){return "ak-warn";}
    return "ak-ok";
}

function nagt_cell_health(array $a):string{
    $id=$a["id"];
    $h=$a["health"];
    if(!is_array($h)){return "<span class='ak-muted'>&mdash;</span>";}
    $o="<div class='nagt-health'>";
    if($h["cpu"]>=0){
        $spark=count($h["spark"])>1?"<span class='nagt-spark' aria-hidden='true'>".implode(",",array_map(function($v){return sprintf("%.2f",$v);},$h["spark"]))."</span>":"";
        $o.="<a href=\"javascript:blur();\" class='nagt-hrow nagt-cpu' title=\"{nagt_cpu_history}\" onclick=\"Loadjs('fw.netagents.metrics.charts.php?agent-metrics-js=$id')\"><span class='nagt-hk'>CPU ".round($h["cpu"])."%</span>$spark</a>";
    }
    if($h["mem"]>=0){
        $o.="<div class='nagt-hrow'><span class='nagt-hk'>MEM ".round($h["mem"])."%</span><span class='nagt-hv'>".nagt_gb($h["mem_used"])."/".nagt_gb($h["mem_total"])." GB</span></div>"
            ."<div class='ak-bar ".nagt_bar_cls($h["mem"])."' role='progressbar' aria-valuenow='".round($h["mem"])."' aria-valuemin='0' aria-valuemax='100' aria-label='MEM'><span style='width:".min(100,round($h["mem"]))."%'></span></div>";
    }
    if($h["disk"]>=0){
        $o.="<a href=\"javascript:blur();\" class='nagt-hrow' title=\"{nagt_disk_usage}\" onclick=\"Loadjs('fw.netagents.treesize.php?id=$id')\"><span class='nagt-hk'>DISK / ".round($h["disk"])."%</span><span class='nagt-hv'>".nagt_gb($h["disk_total"])." GB</span></a>"
            ."<div class='ak-bar ".nagt_bar_cls($h["disk"])."' role='progressbar' aria-valuenow='".round($h["disk"])."' aria-valuemin='0' aria-valuemax='100' aria-label='DISK /'><span style='width:".min(100,round($h["disk"]))."%'></span></div>";
    }
    $sub=array();
    if($h["load"]!=""){$sub[]="{nagt_load} ".$h["load"];}
    if($h["uptime"]!=""){$sub[]="{nagt_up} ".htmlspecialchars($h["uptime"]);}
    if(count($sub)>0){$o.="<div class='nagt-sub nagt-load'>".implode(" &middot; ",$sub)."</div>";}
    return $o."</div>";
}

function nagt_cell_pending(array $a):string{
    if($a["apt_running"]){return "<span class='ak-pill ak-info' title=\"{nagt_apt_running}\" aria-label=\"{nagt_apt_running}\"><i class='fas fa-arrows-rotate ak-spin'></i> APT</span>";}
    if($a["apt"]>0){
        $txt=htmlspecialchars(str_replace("%s",$a["apt"],nagt_t("nagt_apt_n")),ENT_QUOTES);
        return "<a href=\"javascript:blur();\" class='ak-pill ak-warn' title='$txt' aria-label='$txt' onclick=\"Loadjs('fw.netagents.aptget.upgrade.php?id={$a["id"]}')\"><i class='fas fa-box-open'></i> {$a["apt"]} APT</a>";
    }
    return "<span class='ak-muted'>&mdash;</span>";
}

function nagt_cell_lastseen(array $a):string{
    if($a["last_seen"]<=0){return "";}
    return "<div class='nagt-sub' title='".date("Y-m-d H:i:s",$a["last_seen"])."'><i class='far fa-clock'></i> ".nagt_ago($a["last_seen"])."</div>";
}

function nagt_act(string $ico,string $label,string $js,bool $on):string{
    if(!$on){return "<button type='button' class='ak-act' disabled aria-label=\"$label\" title=\"$label\"><i class='$ico'></i></button>";}
    return "<button type='button' class='ak-act' aria-label=\"$label\" title=\"$label\" onclick=\"$js\"><i class='$ico'></i></button>";
}

function nagt_cell_actions(array $a):string{
    $page=CurrentPageName();
    $id=$a["id"];
    $on=$a["state"]=="online";
    // Titre de fenetre place dans une chaine JS : caracteres d'un nom d'hote seulement.
    $ip=preg_replace('/[^A-Za-z0-9._:, -]/','',nagt_is_reverse($a["ip"])?$a["hostname"]:$a["ip"]);
    $b="";
    if(nagt_console_allowed($a)){$b.=nagt_act("fas fa-globe","{nagt_web_console}","s_PopUpFull('/netagents/webconsole/proxy/$id/fw.login.php',1024,768,'Artica Web console $ip')",$on);}
    $b.=nagt_act("fas fa-terminal","{system_shell}","Loadjs('fw.netagents.shell.php?id=$id')",$on);
    $b.=nagt_act("fas fa-gears","{nagt_services_tip}","Loadjs('fw.netagents.services.php?id=$id')",$on);
    // Barre courte (la zone de contenu fait ~1180 px) : le reste passe dans le menu.
    $menu=array();
    $dis=$on?"":" class='disabled'";
    $menu[]="<li$dis><a href=\"javascript:blur();\" onclick=\"".($on?"Loadjs('fw.netagents.logs.php?id=$id')":"")."\"><i class='fas fa-list'></i> {events}</a></li>";
    if($a["proxy"]){$menu[]="<li$dis><a href=\"javascript:blur();\" onclick=\"".($on?"Loadjs('fw.proxy.relatime.php?meta-id-js=$id')":"")."\"><i class='fas fa-chart-line'></i> {nagt_proxy_requests}</a></li>";}
    if($a["proxy"] && $a["artica"]){$menu[]="<li><a href=\"javascript:blur();\" onclick=\"Loadjs('fw.netagents.squid.php?squid-js=$id')\"><i class='fas fa-sliders'></i> {proxy_settings}</a></li>";}
    $menu[]="<li><a href=\"javascript:blur();\" onclick=\"Loadjs('$page?agent-flush-js=$id')\"><i class='fas fa-arrows-rotate'></i> {nagt_refresh_data}</a></li>";
    $menu[]="<li><a href=\"javascript:blur();\" onclick=\"Loadjs('$page?agent-events-js=$id')\"><i class='fas fa-list'></i> {nagt_meta_events}</a></li>";
    if(meta_provider_or_sysadmin()){$menu[]="<li><a href=\"javascript:blur();\" onclick=\"Loadjs('$page?agent-token-js=$id')\"><i class='fas fa-user-lock'></i> {nagt_enroll_token}</a></li>";}
    $menu[]="<li role='separator' class='divider'></li>";
    $menu[]="<li><a href=\"javascript:blur();\" class='nagt-danger' onclick=\"Loadjs('$page?agent-delete-js=$id&md=tr-agent-$id')\"><i class='fas fa-trash-can'></i> {nagt_delete_agent}</a></li>";
    $b.="<div class='dropdown nagt-more'><button type='button' class='ak-act dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label=\"{nagt_more_actions}\" title=\"{nagt_more_actions}\"><i class='fas fa-ellipsis-vertical'></i></button><ul class='dropdown-menu dropdown-menu-right'>".implode("",$menu)."</ul></div>";
    return "<div class='ak-acts'>$b</div>";
}

function nagt_empty():string{
    $page=CurrentPageName();
    return "<section class='nagt-card nagt-empty'><span class='nagt-empty-ico'><i class='fas fa-network-wired'></i></span>
<div class='nagt-empty-t'>{nagt_empty_title}</div>
<div class='nagt-empty-b'><button type='button' class='ak-btn ak-btn-primary' onclick=\"Loadjs('$page?agent-add-js=yes&function=NagtReloadAll')\"><i class='fas fa-plus'></i> {add_agent}</button>
<button type='button' class='ak-btn' onclick=\"Loadjs('fw.netagents.deploy.php?function=NagtReloadAll')\"><i class='fas fa-arrow-right'></i> {deploy_agent}</button>
<a href=\"javascript:blur();\" class='ak-link' onclick=\"s_PopUpFull('https://wiki.articatech.com/artica-meta/deploy-agents','1024','900');\">{nagt_documentation}</a></div></section>";
}

// Concatene HORS de _ENGINE_parse_body.
function nagt_js():string{
    $page=CurrentPageName();
    $L=array();
    foreach(array("showing"=>"nagt_showing","prev"=>"hfx_prev","next"=>"hfx_next") as $k=>$t){ $L[$k]=trim(str_replace(array("[br]","<br>"),"",nagt_t($t))); }
    $accent=nagt_accent();
    return "<script>
window.NagtL=".json_encode($L).";
// Une page ouverte avant la mise a jour garde l'ancien search_block, qui charge cette liste dans
// ses resultats : deux recherches empilees. La liste a la sienne, on masque celle du bloc englobant.
jQuery('.nagt').closest('.sb-stitch').find('.input-group').first().hide();
window.NagtState={q:'',state:'all',attn:'',type:'all',os:'all',page:1,size:25};
function NagtRows(){ var tb=document.getElementById('nagt-rows'); return tb?tb.rows:[]; }
function NagtApply(resetPage){
    var st=window.NagtState, rows=NagtRows(), match=[], i, r;
    if(resetPage){st.page=1;}
    var qi=document.getElementById('nagt-q'); st.q=qi?qi.value.toLowerCase().trim():'';
    for(i=0;i<rows.length;i++){
        r=rows[i]; if(r.className.indexOf('nagt-row')<0){continue;}
        var ok=true, at=' '+r.getAttribute('data-attn')+' ';
        if(st.state!=='all' && r.getAttribute('data-state')!==st.state){ok=false;}
        if(ok && st.type!=='all' && r.getAttribute('data-type')!==st.type){ok=false;}
        if(ok && st.os!=='all' && r.getAttribute('data-os')!==st.os){ok=false;}
        if(ok && st.attn==='any' && at.indexOf(' crit ')<0){ok=false;}
        if(ok && st.attn!=='' && st.attn!=='any' && at.indexOf(' '+st.attn+' ')<0){ok=false;}
        if(ok && st.q!=='' && r.getAttribute('data-q').indexOf(st.q)<0){ok=false;}
        r.style.display='none'; if(ok){match.push(r);}
    }
    var pages=Math.max(1,Math.ceil(match.length/st.size)); if(st.page>pages){st.page=pages;}
    var a=(st.page-1)*st.size, b=Math.min(match.length,st.page*st.size);
    for(i=a;i<b;i++){match[i].style.display='';}
    var nm=document.getElementById('nagt-nomatch'); if(nm){nm.style.display=match.length?'none':'';}
    var c=document.getElementById('nagt-count'), tot=0; for(i=0;i<rows.length;i++){ if(rows[i].className.indexOf('nagt-row')>=0){tot++;} }
    if(c){ c.innerHTML=NagtL.showing.replace('%s','<strong>'+match.length+'</strong>').replace('%s',tot); }
    var box=document.getElementById('nagt-pager'); if(box){
        if(pages<=1){box.innerHTML='';}else{
            var h='<a href=\"#\" data-p=\"'+(st.page-1)+'\" class=\"'+(st.page==1?'off':'')+'\">&lsaquo; '+NagtL.prev+'</a>';
            for(var p=1;p<=pages;p++){ h+='<a href=\"#\" data-p=\"'+p+'\" class=\"'+(p==st.page?'cur':'')+'\">'+p+'</a>'; }
            h+='<a href=\"#\" data-p=\"'+(st.page+1)+'\" class=\"'+(st.page==pages?'off':'')+'\">'+NagtL.next+' &rsaquo;</a>';
            box.innerHTML=h; var as=box.getElementsByTagName('a');
            for(i=0;i<as.length;i++){ as[i].onclick=function(){ var p=parseInt(this.getAttribute('data-p'),10); if(p>=1&&p<=pages&&p!=st.page){st.page=p;NagtApply(0);} return false; }; }
        }
    }
    jQuery('.ak-tools .ak-seg').each(function(){ var k=this.getAttribute('data-k'), v=st[k];
        jQuery(this).find('.ak-seg-b').each(function(){ var on=this.getAttribute('data-v')===v; this.setAttribute('aria-checked',on?'true':'false'); jQuery(this).toggleClass('on',on); }); });
    var at=document.getElementById('nagt-attn'); if(at){ at.setAttribute('aria-checked',st.attn===''?'false':'true'); jQuery(at).toggleClass('on',st.attn!==''); }
    jQuery('#nagt-sum .nagt-fact').each(function(){ var f=this.getAttribute('data-fact'); var on=(f===st.state&&f!=='all')||(f===st.attn); jQuery(this).toggleClass('sel',on); });
    if(jQuery.fn.peity){ jQuery('#nagt-live .nagt-spark').each(function(){ if(!this.getAttribute('data-done')){ this.setAttribute('data-done','1'); jQuery(this).peity('line',{width:90,height:20,fill:'rgba(16,185,129,.12)',stroke:'$accent',strokeWidth:1.5}); } }); }
}
function NagtSet(k,v){ window.NagtState[k]=v; if(k==='attn'&&v!==''){} NagtApply(1); }
function NagtFact(f){
    var st=window.NagtState;
    if(f==='all'){st.state='all';st.attn='';st.type='all';st.os='all';}
    else if(f==='online'||f==='offline'||f==='pending'){st.state=(st.state===f)?'all':f;}
    else{st.attn=(st.attn===f)?'':f;}
    NagtApply(1);
}
function NagtClear(){ var q=document.getElementById('nagt-q'); if(q){q.value='';} NagtFact('all'); }
// Appele par chaque rendu de la partie vivante.
function NagtLive(){
    var src=jQuery('#nagt-live .nagt-sum-src'); if(src.length){ jQuery('#nagt-sum').html(src.html()); src.remove(); }
    var a=document.getElementById('nagt-live-att'), n=document.getElementById('nagt-attn-n'); if(a&&n){n.textContent=a.getAttribute('data-n');}
    NagtApply(0);
    if(!jQuery.fn.peity){ var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=function(){NagtApply(0);}; document.head.appendChild(sc); }
}
function NagtLiveReload(){ if(document.getElementById('nagt-live')){ LoadAjaxSilent('nagt-live','$page?agents-live=yes'); } }
// Menu des actions : ouvert vers le haut quand il deborderait sous la fenetre (dernieres lignes).
jQuery(document).off('shown.bs.dropdown.nagt hidden.bs.dropdown.nagt')
.on('shown.bs.dropdown.nagt','.nagt-more',function(){
  var dd=jQuery(this), menu=dd.children('.dropdown-menu'); if(!menu.length){return;}
  var r=menu[0].getBoundingClientRect(), b=this.getBoundingClientRect();
  if(r.bottom>window.innerHeight-8 && b.top>r.height+8){dd.addClass('dropup');}
})
.on('hidden.bs.dropdown.nagt','.nagt-more',function(){ jQuery(this).removeClass('dropup'); });
if(window.NagtTimer){clearInterval(window.NagtTimer);}
window.NagtTimer=setInterval(function(){
    if(!document.getElementById('nagt-live')){clearInterval(window.NagtTimer);window.NagtTimer=null;return;}
    if(document.hidden){return;}
    if(jQuery('.modal.in,.modal.show').length>0){return;}
    if(jQuery('.sweet-alert.visible,.sweet-alert.showSweetAlert,.jconfirm').length>0){return;}
    if(jQuery('#nagt-live .dropdown.open').length>0){return;}
    NagtLiveReload();
},10000);
NagtLive();
</script>";
}

function nagt_accent():string{
    $accent="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){$accent=$a["accent_dark"];}
    }
    return $accent;
}

function nagt_tiny(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $compact=isset($_COOKIE["META_COMP_VIEW"]) && intval($_COOKIE["META_COMP_VIEW"])==1;
    $topbuttons=array();
    if($compact){$topbuttons[]=array("Loadjs('$page?comp-view-js=0&function=NagtReloadAll')","fa-solid fa-up-right-and-down-left-from-center","{extended_view}");}
    else{$topbuttons[]=array("Loadjs('$page?comp-view-js=1&function=NagtReloadAll')","fa-solid fa-down-left-and-up-right-to-center","{compact_view}");}
    $gps=GroupsArray();
    if(count($gps)>0){
        $gptxt="{all_groups}";
        if($m["group"]>0 && isset($gps[$m["group"]]["NAME"])){$gptxt=$gps[$m["group"]]["NAME"];}
        $topbuttons[]=array("Loadjs('$page?group-select-js=yes&function=NagtReloadAll')",ico_group,$gptxt);
    }
    if(meta_provider_or_sysadmin()){   // ajout direct et deploiement : infogerant seulement
        $topbuttons[]=array("Loadjs('$page?agent-add-js=yes&function=NagtReloadAll')",ico_plus,"{add_agent}");
        $topbuttons[]=array("Loadjs('fw.netagents.deploy.php?function=NagtReloadAll')",ico_arrow_right,"{deploy_agent}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{network_agents}";
    $TINY_ARRAY["ICO"]="fas fa-network-wired";
    $TINY_ARRAY["EXPL"]="{network_agents_explain}";
    $TINY_ARRAY["URL"]="netagents-list";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function nagt_style():string{
    if(isset($GLOBALS["NAGT_CSS"])){return "";}
    $GLOBALS["NAGT_CSS"]=true;
    // .nagt-more .dropdown-menu-right : angular/style.css (charge apres bootstrap) remet
    // .dropdown-menu{left:0}, qui annulait dropdown-menu-right : le menu s'ouvrait vers la
    // droite depuis la derniere colonne et sortait de la page (tronque).
    $accent=nagt_accent();
    $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.nagt{color:#111d23;margin-top:10px}
.nagt *{box-sizing:border-box}
.nagt-strong{font-weight:600}
.nagt-sub{font-size:13px;color:#6d7a77;margin-top:2px;line-height:17px;white-space:nowrap}
.nagt-ip{word-break:break-all;max-width:180px;font-size:12px}
.nagt-spark{font-size:0}
.nagt-sum{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:16px}
.nagt-fact{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid transparent;border-radius:10px;padding:10px 14px;text-align:left;cursor:pointer;min-width:0}
.nagt-fact:hover{border-color:#bcc9c6}
.nagt-fact.sel{border-color:$accent;box-shadow:0 0 0 1px $accent inset}
.nagt-fact:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.nagt-fact-ico{width:34px;height:34px;border-radius:8px;background:#e3f0f8;color:#3d4947;display:inline-flex;align-items:center;justify-content:center;font-size:15px;flex:0 0 auto}
.nagt-fact-txt{display:flex;flex-direction:column;min-width:0}
.nagt-fact-k{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;line-height:15px}
.nagt-fact-v{font-size:22px;font-weight:700;line-height:28px;color:#111d23}
.nagt-fact-sub{font-size:12px;font-weight:600;color:#6d7a77}
.nagt-fact-ok{background:$soft}.nagt-fact-ok .nagt-fact-ico{background:#fff;color:#00504a}
.nagt-fact-warn{background:#fff8ee;border-color:#ffddb9}.nagt-fact-warn .nagt-fact-ico{background:#ffddb9;color:#865300}.nagt-fact-warn .nagt-fact-v{color:#865300}
.nagt-fact-err{background:#fff5f4;border-color:#ffdad6}.nagt-fact-err .nagt-fact-ico{background:#ffdad6;color:#ba1a1a}.nagt-fact-err .nagt-fact-v{color:#ba1a1a}
.nagt-seg-k{font-size:12px;font-weight:600;color:#6d7a77;padding:0 6px;text-transform:uppercase}
.nagt-attn{display:inline-flex;align-items:center;gap:6px;border:1px solid #ffddb9;background:#fff8ee;color:#865300;font-size:13px;font-weight:600;padding:6px 10px;border-radius:8px;cursor:pointer;white-space:nowrap}
.nagt-attn.on{background:#865300;color:#fff;border-color:#865300}
.nagt-attn-n{background:#ffddb9;color:#865300;border-radius:999px;padding:0 7px;font-size:12px}
.nagt-attn.on .nagt-attn-n{background:#fff}
.nagt-count{margin-left:auto;font-size:13px;color:#3d4947;white-space:nowrap}
.nagt-ico-btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;color:#3d4947;cursor:pointer}
.nagt-ico-btn:hover{background:#e3f0f8}
.nagt-attn:focus-visible,.nagt-ico-btn:focus-visible,.nagt-tag:focus-visible,.nagt-fact:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.nagt-card{background:#fff;border:1px solid #bcc9c6;border-radius:12px}
.nagt-tbl{width:100%;border-collapse:collapse;text-align:left}
.nagt-tbl thead th{padding:12px 10px;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;border-bottom:1px solid #bcc9c6;white-space:nowrap}
.nagt-tbl td{padding:12px 10px;vertical-align:middle;border-top:1px solid rgba(188,201,198,.3);font-size:14px}
.nagt-tbl tbody tr:first-child td{border-top:0}
.nagt-row td:first-child{border-left:3px solid transparent}
.nagt-row-attn td:first-child{border-left-color:#f59e0b}
.nagt-row-off td:first-child{border-left-color:#ba1a1a}
.nagt-row-off{color:#6d7a77}
.nagt-row:hover{background:#fbfeff}
.nagt-r{text-align:right!important}
.nagt-ident{display:flex;align-items:center;gap:10px;min-width:180px;max-width:230px}
.nagt-sys{display:flex;align-items:center;gap:6px;margin-top:4px;font-size:13px;font-weight:600;white-space:nowrap}
.nagt-ident-t{min-width:0}
.nagt-tile{width:40px;height:40px;border-radius:8px;background:$soft;color:#00504a;display:inline-flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 auto}
.nagt-tile-linux{background:#e3f0f8;color:#3d4947}
.nagt-host{font-size:15px;font-weight:600;line-height:21px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.nagt-host>a{color:#111d23;text-decoration:none}
.nagt-host>a:hover{color:#00504a;text-decoration:underline}
.nagt-desc{font-size:13px;color:#3d4947;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nagt-upd{margin-top:4px}
@keyframes nagt-rot{to{transform:rotate(360deg)}}
.nagt .ak-tools .ak-search{width:300px}
.nagt-health .ak-bar{margin:2px 0 4px}
.nagt-tbl .ak-acts{gap:0}
.nagt-tags{display:flex;flex-direction:column;align-items:flex-start;gap:4px}
.nagt-tags .nagt-tag{white-space:normal;line-height:15px}
.nagt-more .dropdown-menu>li.disabled>a{color:#bcc9c6;cursor:default}
.nagt-tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;border-radius:4px;padding:2px 8px;background:#e3f0f8;color:#3d4947!important;text-decoration:none!important;white-space:nowrap;max-width:120px;border:1px solid #d5e0e6}
.nagt-tag-acc{background:$soft;color:#00504a!important;border-color:rgba(0,80,74,.2)}
.nagt-tag-muted{background:#f8fafc;color:#6d7a77!important;font-style:italic}
.nagt-tag-clam{background:#dbeafe;color:#1d4ed8!important;border-color:rgba(29,78,216,.2)}
a.nagt-tag:hover{filter:brightness(.95)}
.nagt-health{width:170px}
.nagt-hrow{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#3d4947!important;text-decoration:none!important;margin-top:3px}
.nagt-hk{font-weight:700;font-family:monospace}
.nagt-hv{font-family:monospace;color:#6d7a77}
.nagt-cpu{margin-top:0}
.nagt-more .dropdown-menu{border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 8px 24px rgba(15,23,42,.12);padding:6px 0;min-width:210px}
.nagt-more .dropdown-menu.dropdown-menu-right{left:auto;right:0}
.nagt-more.dropup .dropdown-menu{top:auto;bottom:100%;margin:0 0 2px}
.nagt-more .dropdown-menu>li>a{padding:7px 14px;font-size:13px;color:#111d23}
.nagt-more .dropdown-menu>li>a svg,.nagt-more .dropdown-menu>li>a i{width:16px;margin-right:6px;color:#6d7a77}
.nagt-more .dropdown-menu>li>a.nagt-danger{color:#ba1a1a}
.nagt-more .dropdown-menu>li>a.nagt-danger svg{color:#ba1a1a}
.nagt-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 16px;border-top:1px solid rgba(188,201,198,.3);font-size:13px;color:#3d4947}
.nagt-empty{text-align:center;padding:48px 24px}
.nagt-empty-ico{display:inline-flex;width:56px;height:56px;border-radius:50%;background:$soft;color:#00504a;align-items:center;justify-content:center;font-size:24px}
.nagt-empty-t{font-size:18px;font-weight:600;margin:14px 0 18px}
.nagt-empty-b{display:flex;justify-content:center;align-items:center;gap:12px}
.nagt-empty-s{text-align:center;padding:16px;color:#6d7a77}
.nagt-compact .nagt-sys,.nagt-compact .nagt-desc,.nagt-compact .nagt-load,.nagt-compact .nagt-sub{display:none}
.nagt-compact td{padding-top:8px;padding-bottom:8px}
@media (max-width:1200px){.nagt-sum{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (prefers-reduced-motion:reduce){.nagt-spin{animation:none}}
</style>";
}
// Fin de la liste des agents

function agent_info_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-js"]);
    $Hostname=getAgentHostname($id);
    return $tpl->js_dialog2("#$id: $Hostname","$page?agent-info-tab=$id",1100);
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
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    header("content-type: application/x-javascript");
    // Pentest 2026-11-04 : group-selected etait recopie sans encodage dans la page (XSS).
    echo "Set_Cookie('META_COMP_GROUP', {$_COOKIE["META_COMP_GROUP"]}, '3600', '/', '', '');\n";
    echo "$function();\n";
    echo "dialogInstance3.close();\n";
    return true;
}
// ── Choix du groupe qui filtre la liste (maquette stitch_artica_meta1, 2026-11-15) ──
// Filtre d'affichage seulement : cookie META_COMP_GROUP (1 h), aucune appartenance
// modifiee. Un modele (nagsel_model) lu une fois sur /netagents/groups, un widget
// par fonction, CSS et JS concatenes HORS de _ENGINE_parse_body.
function group_select_popup():bool{
    $tpl=new template_admin();
    $m=nagsel_model();
    $h=array();
    $h[]="<div class='nagsel' id='nagsel-root'>";
    $h[]=nagsel_intro();
    if($m["error"]!==""){$h[]=nagsel_error($m);}
    if(count($m["groups"])>6){$h[]=nagsel_search();}
    $h[]="<div class='nagsel-list' role='radiogroup' aria-label=\"{nagsel_groups_aria}\">";
    $h[]=nagsel_row_all($m);
    foreach($m["groups"] as $g){$h[]=nagsel_row($g,$m);}
    $h[]="<div class='nagsel-nomatch' id='nagsel-nomatch' style='display:none'><i class='fas fa-circle-info' aria-hidden='true'></i> {nagsel_nomatch}</div>";
    $h[]="</div>";
    if($m["error"]==="" && count($m["groups"])===0){$h[]=nagsel_empty();}
    $h[]=nagsel_footer();
    $h[]="</div>";
    echo nagsel_style().$tpl->_ENGINE_parse_body(implode("",$h))."<script>".nagsel_js()."NoSpinner();</script>";
    return true;
}

// Texte venu du demon : echappe, accolades neutralisees (jamais pris pour un {jeton}).
function nagsel_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}

function nagsel_model():array{
    $m=array("error"=>"","groups"=>array(),"current"=>0,
        "function"=>preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")));
    $m["current"]=isset($_COOKIE["META_COMP_GROUP"])?intval($_COOKIE["META_COMP_GROUP"]):0;
    $raw=trim((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
    // Aucun groupe : le demon serialise une slice Go nil, soit « null » et non « [] ».
    if($raw==="null"){return $m;}
    $json=json_decode($raw);
    if(is_object($json)){
        $err=isset($json->Error)?(string)$json->Error:"";
        if(isset($json->Status) && !$json->Status){$m["error"]=$err!==""?$err:"Artica REST API: error";}
        return $m;
    }
    if(!is_array($json)){$m["error"]="Artica REST API: no answer";return $m;}
    foreach($json as $g){
        if(!is_object($g)){continue;}
        $m["groups"][]=array(
            "id"=>intval($g->id ?? 0),
            "name"=>(string)($g->name ?? ""),
            "desc"=>trim((string)($g->description ?? "")),
            "count"=>intval($g->agent_count ?? 0),
            "cluster"=>intval($g->cluster_mode ?? 0)==1,
            "denied"=>!empty($g->blacklisted),
        );
    }
    usort($m["groups"],function($a,$b){ return strnatcasecmp($a["name"],$b["name"]); });
    // Groupe du cookie supprime depuis : aucune ligne ne serait « Current ».
    $known=false;
    foreach($m["groups"] as $g){ if($g["id"]==$m["current"]){$known=true;} }
    if(!$known){$m["current"]=0;}
    return $m;
}

function nagsel_intro():string{
    return "<div class='nagsel-intro'><span class='nagsel-ico nagsel-ico-a'><i class='fas fa-layer-group' aria-hidden='true'></i></span>"
        ."<div class='nagsel-help'>{nagsel_explain}</div></div>";
}

function nagsel_error(array $m):string{
    return "<div class='nagsel-err' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><div><b>{nagsel_read_error}</b>"
        ."<div class='nagsel-mono'>".nagsel_e($m["error"])."</div></div></div>";
}

function nagsel_search():string{
    return "<div class='nagsel-search'><span class='nagsel-search-i'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></span>"
        ."<input type='search' id='nagsel-q' class='nagsel-q' placeholder=\"{nagsel_search}\" aria-label=\"{nagsel_search}\" oninput='NagSelFilter(this.value)' autocomplete='off'>"
        ."<div class='nagsel-help nagsel-search-h'>{nagsel_search_help}</div></div>";
}

// Une ligne = une option radio cliquable. La ligne courante ferme simplement la fenetre.
function nagsel_option(int $id,bool $current,string $icon,string $title,string $sub,string $right,string $q,string $function,string $badges=""):string{
    $page=CurrentPageName();
    $js=$current?"dialogInstance3.close();":"Loadjs('$page?group-selected=$id&function=$function');";
    $cls="nagsel-row".($current?" is-cur":"");
    $cur=$current?" <span class='nagsel-tag nagsel-tag-cur'><i class='fas fa-check' aria-hidden='true'></i> {nagsel_current}</span>":"";
    return "<div class='$cls' role='radio' tabindex='0' aria-checked='".($current?"true":"false")."' data-q=\"$q\" onclick=\"$js\" onkeydown='NagSelKey(event,this)'>"
        ."<span class='nagsel-radio' aria-hidden='true'></span>"
        ."<span class='nagsel-ico'><i class='$icon' aria-hidden='true'></i></span>"
        ."<span class='nagsel-txt'><span class='nagsel-name'>$title$cur$badges</span>$sub</span>$right</div>";
}

function nagsel_row_all(array $m):string{
    return nagsel_option(0,$m["current"]===0,"fas fa-globe","{all_groups}",
        "<span class='nagsel-desc'>{nagsel_all_desc}</span>","","",$m["function"]);
}

function nagsel_badges(array $g):string{
    $b="";
    if($g["cluster"]){$b.=" <span class='nagsel-tag nagsel-tag-info' title=\"{nagsel_cluster_tip}\"><i class='fas fa-network-wired' aria-hidden='true'></i> {nagsel_cluster}</span>";}
    if($g["denied"]){$b.=" <span class='nagsel-tag nagsel-tag-err' title=\"{nagsel_denied_tip}\"><i class='fas fa-ban' aria-hidden='true'></i> {nagr_updates_denied}</span>";}
    return $b;
}

function nagsel_count(array $g):string{
    if($g["count"]<1){return "<span class='nagsel-count is-zero'>{nagsel_no_agent}</span>";}
    $w=$g["count"]==1?"{nagsel_agent}":"{nagsel_agents}";
    return "<span class='nagsel-count'><i class='fas fa-server' aria-hidden='true'></i> {$g["count"]} $w</span>";
}

function nagsel_row(array $g,array $m):string{
    $desc=$g["desc"]!==""?"<span class='nagsel-desc' title=\"".nagsel_e($g["desc"])."\">".nagsel_e($g["desc"])."</span>":"";
    if($g["count"]<1){$desc.="<span class='nagsel-desc nagsel-dim'>{nagsel_empty_list}</span>";}
    $q=nagsel_e(function_exists("mb_strtolower")?mb_strtolower($g["name"]." ".$g["desc"],"UTF-8"):strtolower($g["name"]." ".$g["desc"]));
    return nagsel_option($g["id"],$g["id"]===$m["current"],"fas fa-layer-group",
        nagsel_e($g["name"]),$desc,nagsel_count($g),$q,$m["function"],nagsel_badges($g));
}

function nagsel_empty():string{
    return "<div class='nagsel-empty'><span class='nagsel-empty-i'><i class='fas fa-layer-group' aria-hidden='true'></i></span>"
        ."<div class='nagsel-empty-t'>{nagsel_none}</div><div class='nagsel-help'>{nagsel_none_help}</div></div>";
}

function nagsel_footer():string{
    return "<div class='nagsel-foot'><span class='nagsel-help'><i class='fas fa-bolt' aria-hidden='true'></i> {nagsel_immediate}</span>"
        ."<button type='button' class='nagsel-cancel' onclick='dialogInstance3.close();'>{nagsel_cancel}</button></div>";
}

function nagsel_style():string{
    $a="#059669"; $ad="#047857"; $as="#ecfdf5";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent"])){$a=$b["accent"];}
        if(!empty($b["accent_dark"])){$ad=$b["accent_dark"];}
        if(!empty($b["accent_soft"])){$as=$b["accent_soft"];}
    }
    return "<style>
.nagsel{color:#0b1c30;font-size:13px;margin:-5px -5px 0}
.nagsel-help{font-size:13px;color:#64748b;line-height:1.5}
.nagsel-intro{display:flex;gap:14px;align-items:flex-start;padding:0 0 16px;border-bottom:1px solid #e2e8f0;margin-bottom:16px}
.nagsel-ico{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#f1f5f9;color:#475569;font-size:14px;flex:0 0 auto}
.nagsel-ico-a{width:36px;height:36px;background:$as;color:$ad;font-size:16px}
.nagsel-err{display:flex;gap:10px;align-items:flex-start;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;margin-bottom:12px}
.nagsel-mono{font-family:'JetBrains Mono',Consolas,monospace;font-size:12px;margin-top:3px;word-break:break-word}
.nagsel-search{position:relative;margin-bottom:12px}
.nagsel-search-i{position:absolute;left:12px;top:11px;color:#64748b;font-size:14px;line-height:1}
.nagsel-q{width:100%;height:40px;border:1px solid #e2e8f0;border-radius:8px;padding:0 12px 0 36px;font-size:14px;color:#0b1c30;box-sizing:border-box;outline:none}
.nagsel-q:focus{border-color:$a;box-shadow:0 0 0 2px $as}
.nagsel-search-h{margin-top:6px}
.nagsel-list{display:flex;flex-direction:column;gap:8px;max-height:52vh;overflow-y:auto;padding:2px}
.nagsel-row{display:flex;flex:0 0 auto;align-items:center;gap:12px;min-height:64px;padding:12px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;box-sizing:border-box;transition:background .12s,border-color .12s}
.nagsel-row:hover{background:#f8fafc;border-color:#cbd5e1}
.nagsel-row.is-cur{border:2px solid $a;background:$as;padding:11px 15px}
.nagsel-row:focus-visible{outline:2px solid $a;outline-offset:2px}
.nagsel-radio{width:18px;height:18px;border-radius:50%;border:2px solid #94a3b8;flex:0 0 auto;box-sizing:border-box;background:#fff}
.nagsel-row.is-cur .nagsel-radio{border-color:$a;box-shadow:inset 0 0 0 3px #fff;background:$a}
.nagsel-txt{display:flex;flex-direction:column;gap:2px;flex:1 1 auto;min-width:0}
.nagsel-name{font-size:14px;font-weight:700;color:#0b1c30;display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.nagsel-desc{font-size:13px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nagsel-dim{color:#94a3b8;font-style:italic}
.nagsel-tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:1px 8px;border-radius:6px;border:1px solid transparent;white-space:nowrap}
.nagsel-tag-cur{background:#fff;color:$ad;border-color:$a}
.nagsel-tag-info{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.nagsel-tag-err{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.nagsel-count{font-size:13px;font-weight:600;color:#334155;white-space:nowrap;flex:0 0 auto;display:inline-flex;align-items:center;gap:6px}
.nagsel-count.is-zero{color:#94a3b8;font-weight:400}
.nagsel-nomatch{color:#64748b;padding:14px 4px}
.nagsel-empty{text-align:center;padding:24px 16px 8px}
.nagsel-empty-i{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:#f1f5f9;color:#94a3b8;font-size:24px;margin-bottom:10px}
.nagsel-empty-t{font-size:14px;font-weight:700;margin-bottom:4px}
.nagsel-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0}
.nagsel-foot .nagsel-help i{color:$ad}
.nagsel-cancel{background:#fff;border:1px solid #cbd5e1;color:#334155;border-radius:8px;padding:7px 18px;font-size:13px;font-weight:600;cursor:pointer}
.nagsel-cancel:hover{background:#f8fafc}
.nagsel-cancel:focus-visible{outline:2px solid $a;outline-offset:2px}
@media (prefers-reduced-motion:reduce){.nagsel *{transition:none!important}}
</style>";
}

function nagsel_js():string{
    return "function NagSelFilter(v){v=String(v||'').toLowerCase().trim();var r=document.querySelectorAll('#nagsel-root .nagsel-row[data-q]'),n=0;
 for(var i=0;i<r.length;i++){var q=r[i].getAttribute('data-q');if(q===''){continue;}var ok=(v===''||q.indexOf(v)>-1);r[i].style.display=ok?'':'none';if(ok){n++;}}
 var z=document.getElementById('nagsel-nomatch');if(z){z.style.display=(v!==''&&n===0)?'':'none';}}
function NagSelKey(e,el){var k=e.key||'';
 if(k==='Enter'||k===' '||k==='Spacebar'){e.preventDefault();el.click();return;}
 if(k!=='ArrowDown'&&k!=='ArrowUp'){return;}e.preventDefault();
 var r=[].slice.call(document.querySelectorAll('#nagsel-root .nagsel-row')).filter(function(x){return x.style.display!=='none';});
 var i=r.indexOf(el)+(k==='ArrowDown'?1:-1);if(i>=0&&i<r.length){r[i].focus();}}
(function(){var c=document.querySelector('#nagsel-root .nagsel-row.is-cur');if(c&&c.scrollIntoView){try{c.scrollIntoView({block:'nearest'});}catch(e){}}})();
";
}



// ── Onglet Disks d'un agent (maquette stitch_agent_disks, 2026-11-07) ─────────
// Un modele (nad_model) lu une fois sur /netagents/status/{id}, un widget par
// fonction. CSS et JS concatenes HORS de _ENGINE_parse_body.

/** Traduit un jeton puis remplace ses %s dans l'ordre. */
function nad_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    $s=preg_replace('#<br\s*/?>#i'," ",str_replace("[br]"," ",$s));
    foreach($args as $a){
        $pos=strpos($s,"%s");
        if($pos===false){break;}
        $s=substr($s,0,$pos).$a.substr($s,$pos+2);
    }
    return trim($s);
}
function nad_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}
/** Octets -> "1.8 TB" (FormatBytes compte en Ko). */
function nad_bytes($bytes):string{
    $b=floatval($bytes);
    if($b<=0){return "0 B";}
    if($b<1024){return intval($b)." B";}
    return FormatBytes($b/1024,true);
}
function nad_accents():array{
    $ac=array("accent"=>"#0f766e","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(15,118,110,.12)","accent_line"=>"rgba(15,118,110,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}
/** Seuils d'occupation de la page : 75 % alerte, 90 % critique. */
function nad_level(int $pct):string{
    if($pct>90){return "crit";}
    if($pct>75){return "warn";}
    return "ok";
}
/** Type du disque : celui de l'agent s'il est fourni, sinon deduit. */
function nad_disk_type(array $d):string{
    $t=trim((string)($d["type"] ?? ""));
    $known=array("NVMe","USB","MMC","Virtual","HDD","SSD");
    if(in_array($t,$known,true)){return $t;}
    if(strtoupper((string)($d["transport"] ?? ""))==="NVME"){return "NVMe";}
    if(!empty($d["rotational"])){return "HDD";}
    return "SSD";
}
function nad_type_style(string $type):array{
    $map=array(
        "HDD"=>array("fas fa-hard-drive","#1d4ed8"),
        "SSD"=>array("fas fa-microchip","#0f766e"),
        "NVMe"=>array("fas fa-bolt","#005447"),
        "Virtual"=>array("fas fa-cloud","#475569"),
        "USB"=>array("fas fa-usb-drive","#b45309"),
        "MMC"=>array("fas fa-sd-card","#b45309"),
    );
    if(isset($map[$type])){return $map[$type];}
    return $map["SSD"];
}
/**
 * Etat SMART. L'agent ecrit FAILED des que smartctl ne renvoie pas
 * smart_status.passed, y compris pour un disque virtuel sans SMART : un FAILED
 * sans aucune mesure (temperature, heures, secteurs) est donc "sans donnee".
 */
function nad_smart_state(array $d):string{
    if(empty($d["smart_available"])){return "na";}
    $s=strtoupper(trim((string)($d["smart_status"] ?? "")));
    if($s==="PASSED"){return "pass";}
    $measured=intval($d["smart_temperature"] ?? 0)>0
        || intval($d["smart_power_on_hours"] ?? 0)>0
        || intval($d["smart_reallocated_sectors"] ?? 0)>0;
    if($s==="FAILED" && $measured){return "fail";}
    return "nodata";
}

function nad_model(int $id):array{
    $m=array("ok"=>false,"error"=>"","fs"=>array(),"disks"=>array(),"types"=>array(),
        "capacity"=>0,"smart_fail"=>array(),"smart_pass"=>0,"fullest"=>null);
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id");
    $Main=json_decode($raw,true);
    if(!is_array($Main)){
        $m["error"]="no-answer";
        return $m;
    }
    if(!empty($Main["Error"])){
        $m["error"]=(string)$Main["Error"];
        return $m;
    }
    $m["ok"]=true;

    // Occupation par point de montage. Pourcentage a la maniere de df :
    // used/(used+available), les blocs reserves a root ne comptent pas.
    $fs=array();
    if(isset($Main["disk"]) && is_array($Main["disk"])){
        foreach($Main["disk"] as $row){
            if(!is_array($row)){continue;}
            $total=floatval($row["total_bytes"] ?? 0);
            if($total<=0){continue;}
            $used=floatval($row["used_bytes"] ?? 0);
            $avail=floatval($row["available_bytes"] ?? 0);
            $den=$used+$avail;
            $pct=$den>0?intval(round(100*$used/$den)):intval($row["used_percent"] ?? 0);
            if($pct>100){$pct=100;}
            $fs[]=array("mount"=>(string)($row["mount_point"] ?? ""),
                "device"=>(string)($row["device"] ?? ""),
                "total"=>$total,"used"=>$used,"avail"=>$avail,"pct"=>$pct);
        }
    }
    usort($fs,function($a,$b){ return $b["pct"]-$a["pct"]; });
    $m["fs"]=$fs;
    if(count($fs)>0){$m["fullest"]=$fs[0];}

    $disks=array();
    if(isset($Main["disks"]) && is_array($Main["disks"])){
        foreach($Main["disks"] as $d){
            if(!is_array($d)){continue;}
            $type=nad_disk_type($d);
            $d["_type"]=$type;
            $d["_smart"]=nad_smart_state($d);
            $disks[]=$d;
            if(!isset($m["types"][$type])){$m["types"][$type]=0;}
            $m["types"][$type]++;
            $m["capacity"]+=floatval($d["size_bytes"] ?? 0);
            if($d["_smart"]=="fail"){$m["smart_fail"][]="/dev/".(string)($d["name"] ?? "?");}
            if($d["_smart"]=="pass"){$m["smart_pass"]++;}
        }
    }
    $m["disks"]=$disks;
    return $m;
}

// ── Bandeau de synthese ──────────────────────────────────────────────────────
function nad_strip_cell(string $label,string $valueHtml,string $subHtml,string $cls=""):string{
    return "<div class='nad-cell $cls'><div class='nad-cell-l'>$label</div>"
        ."<div class='nad-cell-v'>$valueHtml</div><div class='nad-cell-s'>$subHtml</div></div>";
}
function nad_cell_disks(array $m):string{
    $parts=array();
    foreach(array("HDD","SSD","NVMe","Virtual","USB","MMC") as $t){
        if(!empty($m["types"][$t])){$parts[]=$m["types"][$t]." ".$t;}
    }
    $sub=count($parts)>0?implode(" &middot; ",$parts):"&mdash;";
    return nad_strip_cell(nad_tr("nad_disks"),strval(count($m["disks"])),$sub);
}
function nad_cell_capacity(array $m):string{
    $n=count($m["disks"]);
    $val=$n>0?nad_bytes($m["capacity"]):"&mdash;";
    $sub=$n>0?nad_tr("nad_across_drives",array($n)):"&mdash;";
    return nad_strip_cell(nad_tr("nad_total_capacity"),$val,$sub);
}
function nad_cell_smart(array $m):string{
    $label=nad_tr("smart_status");
    $nf=count($m["smart_fail"]);
    if($nf>0){
        $v="<i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".nad_tr("nad_n_failed",array($nf));
        $s=nad_tr("nad_health_alert",array(nad_h(implode(", ",$m["smart_fail"]))));
        return nad_strip_cell($label,"<span class='nad-crit'>$v</span>",$s);
    }
    if($m["smart_pass"]>0){
        $v="<i class='fas fa-circle-check' aria-hidden='true'></i> ".nad_tr("nad_all_passed");
        $n=count($m["disks"]);
        $s=$m["smart_pass"]<$n
            ?nad_tr("nad_smart_partial",array($m["smart_pass"],$n))
            :nad_tr("nad_no_health_alert");
        return nad_strip_cell($label,"<span class='nad-ok'>$v</span>",$s);
    }
    $v="<i class='fas fa-circle-minus' aria-hidden='true'></i> ".nad_tr("nad_not_available");
    $s=count($m["disks"])>0?nad_tr("nad_no_smart_any"):"&mdash;";
    return nad_strip_cell($label,"<span class='nad-mute'>$v</span>",$s);
}
function nad_cell_fullest(array $m):string{
    $label=nad_tr("nad_fullest");
    $f=$m["fullest"];
    if(!is_array($f)){
        return nad_strip_cell($label,"<span class='nad-mute'>&mdash;</span>","&mdash;");
    }
    $lvl=nad_level($f["pct"]);
    $ico=$lvl=="ok"?"fa-circle-check":"fa-triangle-exclamation";
    $sub=nad_tr("nad_below_warning");
    if($lvl=="warn"){$sub=nad_tr("nad_warning_exceeded");}
    if($lvl=="crit"){$sub=nad_tr("nad_critical_exceeded");}
    $v="<i class='fas $ico' aria-hidden='true'></i> <span class='ak-mono'>".nad_h($f["mount"])."</span> &middot; {$f["pct"]}%";
    return nad_strip_cell($label,"<span class='nad-$lvl'>$v</span>",$sub);
}
function nad_refresh_btn(int $id):string{
    $page=CurrentPageName();
    $lbl=nad_tr("refresh");
    return "<button type='button' class='ak-btn nad-btn' id='nad-refresh-$id' "
        ."onclick=\"NadFlush($id,'$page')\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i> $lbl</button>";
}
function nad_strip(int $id,array $m):string{
    return "<section class='nad-strip'><div class='nad-cells'>"
        .nad_cell_disks($m).nad_cell_capacity($m).nad_cell_smart($m).nad_cell_fullest($m)
        ."</div><div class='nad-strip-act'>".nad_refresh_btn($id)."</div></section>";
}

// ── Occupation des systemes de fichiers ──────────────────────────────────────
function nad_fs_row(array $f):string{
    $lvl=nad_level($f["pct"]);
    $ico="";
    if($lvl!="ok"){$ico="<i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ";}
    $mount=nad_h($f["mount"]);
    $aria=nad_h(nad_tr("nad_usage_of",array($f["mount"])));
    $fig=function($l,$v){ return "<div class='nad-fig'><span>$l</span><strong>$v</strong></div>"; };
    return "<div class='nad-fs-row'>"
        ."<div class='nad-fs-name'><div class='ak-mono nad-strong' title='$mount'>$mount</div><div class='ak-mono nad-sub' title='".nad_h($f["device"])."'>".nad_h($f["device"])."</div></div>"
        ."<div class='nad-bar' role='meter' aria-valuemin='0' aria-valuemax='100' aria-valuenow='{$f["pct"]}' aria-label='$aria'>"
        ."<div class='nad-bar-f nad-bg-$lvl' style='width:{$f["pct"]}%'></div></div>"
        ."<div class='nad-figs'>".$fig(nad_tr("nad_used"),nad_bytes($f["used"]))
        .$fig(nad_tr("nad_available"),nad_bytes($f["avail"]))
        .$fig(nad_tr("nad_total"),nad_bytes($f["total"]))."</div>"
        ."<div class='nad-pct nad-$lvl'>$ico{$f["pct"]}%</div></div>";
}
function nad_card_fs(array $m):string{
    $n=count($m["fs"]);
    $h=array();
    $h[]="<section class='ak-card'><header class='nad-card-h'><h3><i class='fas fa-folder-open' aria-hidden='true'></i> ".nad_tr("filesystem_usage")."</h3>";
    $h[]="<span class='nad-sub'>".nad_tr("nad_fs_mounted",array($n))."</span></header>";
    if($n==0){
        $h[]="<div class='nad-empty'><i class='fas fa-circle-info' aria-hidden='true'></i> ".nad_tr("nad_fs_empty")."</div>";
    }else{
        foreach($m["fs"] as $f){$h[]=nad_fs_row($f);}
    }
    $h[]="</section>";
    return implode("",$h);
}

// ── Cartes disque ────────────────────────────────────────────────────────────
function nad_disk_head(array $d):string{
    list($ico,$color)=nad_type_style($d["_type"]);
    $transport=strtoupper(trim((string)($d["transport"] ?? "")));
    $pill=$d["_type"];
    if($transport!=="" && strtoupper($pill)!==$transport){$pill.=" &middot; ".nad_h($transport);}
    $model=trim((string)($d["model"] ?? ""));
    $size=intval($d["size_bytes"] ?? 0)>0?nad_bytes($d["size_bytes"]):"&mdash;";
    return "<div class='nad-dh'><div class='nad-dh-ico' style='color:$color;background:".nad_rgba($color,".12")."'>"
        ."<i class='$ico' aria-hidden='true'></i></div>"
        ."<div class='nad-dh-t'><div><span class='ak-mono nad-dev'>/dev/".nad_h($d["name"] ?? "?")."</span> "
        ."<span class='nad-pill' style='color:$color;border-color:".nad_rgba($color,".45")."'>$pill</span></div>"
        ."<div class='nad-sub'>".($model!==""?nad_h($model):"&mdash;")."</div></div>"
        ."<div class='nad-dh-size'>$size</div></div>";
}
/** rgba depuis un hexadecimal, sans dependre de class.brand.inc. */
function nad_rgba(string $hex,string $alpha):string{
    $h=ltrim(trim($hex),'#');
    if(!preg_match('/^[0-9a-fA-F]{6}$/',$h)){return $hex;}
    return sprintf("rgba(%d,%d,%d,%s)",hexdec(substr($h,0,2)),hexdec(substr($h,2,2)),hexdec(substr($h,4,2)),$alpha);
}
function nad_tile(string $label,string $body):string{
    return "<div class='nad-tile'><div class='nad-tile-l'>$label</div><div class='nad-tile-v'>$body</div></div>";
}
function nad_tile_smart(array $d):string{
    $s=$d["_smart"];
    if($s=="pass"){$b="<span class='ak-pill ak-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> ".nad_tr("nad_passed")."</span>";}
    elseif($s=="fail"){$b="<span class='ak-pill ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".nad_tr("nad_failed")."</span>";}
    else{
        $why=$s=="na"?nad_tr("nad_smartctl_missing"):nad_tr("nad_no_smart_data");
        $b="<span class='ak-pill ak-off nad-na' title='".nad_h($why)."'><i class='fas fa-circle-minus' aria-hidden='true'></i> ".nad_tr("nad_na")."</span>";
    }
    return nad_tile(nad_tr("nad_smart_short"),$b);
}
function nad_tile_temp(array $d):string{
    $t=intval($d["smart_temperature"] ?? 0);
    if($t<=0){return nad_tile(nad_tr("temperature"),"<span class='nad-mute'>&mdash;</span>");}
    $lvl="norm";$ico="fa-temperature-half";
    if($t>=40){$lvl="warn";$ico="fa-temperature-three-quarters";}
    if($t>=55){$lvl="crit";$ico="fa-temperature-full";}
    $warn=$lvl=="norm"?"":" <i class='fas fa-triangle-exclamation' aria-hidden='true'></i>";
    return nad_tile(nad_tr("temperature"),"<span class='nad-$lvl'><i class='fas $ico' aria-hidden='true'></i> $t &deg;C$warn</span>");
}
function nad_tile_power(array $d):string{
    $hours=intval($d["smart_power_on_hours"] ?? 0);
    if($hours<=0){return nad_tile(nad_tr("nad_power_on"),"<span class='nad-mute'>&mdash;</span>");}
    $days=intdiv($hours,24);
    $y=intdiv($days,365);
    $rd=$days%365;
    $human=$y>0?"$y y $rd d":($days>0?"$days d ".($hours%24)." h":"$hours h");
    return nad_tile(nad_tr("nad_power_on"),"<div class='nad-strong'>$human</div><div class='ak-mono nad-sub'>".number_format($hours)." h</div>");
}
function nad_tile_realloc(array $d):string{
    $label=nad_tr("nad_reallocated");
    if($d["_smart"]=="na" || $d["_smart"]=="nodata"){return nad_tile($label,"<span class='nad-mute'>&mdash;</span>");}
    $r=intval($d["smart_reallocated_sectors"] ?? 0);
    if($r>0){return nad_tile($label,"<span class='nad-crit'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".number_format($r)."</span>");}
    return nad_tile($label,"<span class='nad-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> 0</span>");
}
function nad_disk_io(array $d):string{
    $ops=nad_tr("nad_ops");
    return "<div class='nad-io'><span class='nad-io-t'>".nad_tr("nad_io_since_boot")."</span>"
        ."<span><i class='fas fa-arrow-down nad-ok' aria-hidden='true'></i> ".nad_tr("nad_read")." <strong>".nad_bytes($d["read_bytes"] ?? 0)."</strong> (".number_format(floatval($d["read_ios"] ?? 0))." $ops)</span>"
        ."<span><i class='fas fa-arrow-up nad-blue' aria-hidden='true'></i> ".nad_tr("nad_written")." <strong>".nad_bytes($d["write_bytes"] ?? 0)."</strong> (".number_format(floatval($d["write_ios"] ?? 0))." $ops)</span></div>";
}
function nad_disk_details(array $d,string $uid):string{
    $ls=intval($d["logical_sector_size"] ?? 0);
    $ps=intval($d["physical_sector_size"] ?? 0);
    $rows=array(
        array(nad_tr("nad_model"),$d["model"] ?? ""),
        array(nad_tr("nad_vendor"),$d["vendor"] ?? ""),
        array(nad_tr("serial"),$d["serial"] ?? ""),
        array(nad_tr("firmware"),$d["firmware_rev"] ?? ""),
        array(nad_tr("sectors"),($ls>0 && $ps>0)?"$ls / $ps B":""),
    );
    $dl=array();
    foreach($rows as $r){
        $v=trim((string)$r[1]);
        $dl[]="<div><dt>{$r[0]}</dt><dd class='ak-mono' title='".nad_h($v)."'>".($v!==""?nad_h($v):"&mdash;")."</dd></div>";
    }
    return "<button type='button' class='nad-toggle' aria-expanded='false' aria-controls='$uid' onclick=\"NadToggle(this)\">"
        ."<span>".nad_tr("nad_details")."</span><i class='fas fa-chevron-down' aria-hidden='true'></i></button>"
        ."<div class='nad-details' id='$uid' hidden><dl>".implode("",$dl)."</dl></div>";
}
function nad_disk_partitions(array $d):string{
    $parts=array();
    if(isset($d["partitions"]) && is_array($d["partitions"])){$parts=$d["partitions"];}
    if(count($parts)==0){return "";}
    $copy=nad_h(nad_tr("nad_copy_uuid"));
    $h=array();
    $h[]="<div class='nad-parts-t'>".nad_tr("partitions")."</div><table class='nad-parts'><thead><tr>";
    $h[]="<th>".nad_tr("nad_device")."</th><th>".nad_tr("filesystem")."</th><th>".nad_tr("nad_mount_point")."</th>";
    $h[]="<th class='nad-r'>".nad_tr("nad_size")."</th><th>".nad_tr("label")."</th><th>UUID</th></tr></thead><tbody>";
    foreach($parts as $p){
        if(!is_array($p)){continue;}
        $name=trim((string)($p["name"] ?? ""));
        $mnt=trim((string)($p["mount_point"] ?? ""));
        $uuid=trim((string)($p["uuid"] ?? ""));
        $size=intval($p["size_bytes"] ?? 0)>0?nad_bytes($p["size_bytes"]):"&mdash;";
        $mntH=$mnt!==""?"<span class='ak-mono'>".nad_h($mnt)."</span>":"<em class='nad-mute'>".nad_tr("nad_not_mounted")."</em>";
        $uuidH="&mdash;";
        if($uuid!==""){
            $short=strlen($uuid)>8?substr($uuid,0,8)."&hellip;":nad_h($uuid);
            $uuidH="<span class='ak-mono nad-sub' title='".nad_h($uuid)."'>$short</span> "
                ."<button type='button' class='nad-icobtn' aria-label='$copy' title='$copy' data-copy='".nad_h($uuid)."' onclick=\"NadCopy(this)\">"
                ."<i class='far fa-copy' aria-hidden='true'></i></button>";
        }
        $h[]="<tr><td class='ak-mono'>".($name!==""?"/dev/".nad_h($name):"&mdash;")."</td>"
            ."<td class='ak-mono'>".nad_h(($p["filesystem"] ?? "") ?: "—")."</td><td>$mntH</td>"
            ."<td class='nad-r'>$size</td><td>".nad_h($p["label"] ?? "")."</td><td class='ak-nowrap'>$uuidH</td></tr>";
    }
    $h[]="</tbody></table>";
    return implode("",$h);
}
function nad_disk_card(array $d,int $i):string{
    $fail=$d["_smart"]=="fail"?" nad-disk-fail":"";
    return "<article class='ak-card nad-disk$fail'>".nad_disk_head($d)
        ."<div class='nad-tiles'>".nad_tile_smart($d).nad_tile_temp($d).nad_tile_power($d).nad_tile_realloc($d)."</div>"
        .nad_disk_io($d).nad_disk_details($d,"nad-det-$i").nad_disk_partitions($d)."</article>";
}
// -- Plus gros repertoires (TreeSize de l'agent, deja collecte par Meta) --
// /netagents/treesize/{id}/top rend les repertoires tries par taille CUMULEE : "/" et
// les ancetres d'un gros repertoire y figurent tous (/home, /home/artica, ...). On retire
// "/" et tout repertoire dont un descendant de la liste porte au moins 90 % de la taille :
// c'est le meme espace, le descendant dit ou il est.
function nad_topdirs_model(int $id):array{
    $t=array("state"=>"none","rows"=>array(),"total"=>0,"scan"=>0,"error"=>"");
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $st=json_decode((string)$sock->REST_API("/netagents/treesize/$id/status"),true);
    if(!is_array($st)){$t["state"]="error";return $t;}
    if(isset($st["Status"]) && !$st["Status"]){$t["state"]="error";$t["error"]=(string)($st["Error"] ?? "");return $t;}
    if(empty($st["exists"])){return $t;}
    $t["scan"]=nai_epoch($st["last_modified"] ?? "");
    $latest=json_decode((string)$sock->REST_API("/netagents/treesize/$id/latest?path=/"),true);
    if(is_array($latest) && intval($latest["end_time"] ?? 0)>0){$t["scan"]=intval($latest["end_time"]);}
    $top=json_decode((string)$sock->REST_API("/netagents/treesize/$id/top?path=/&limit=40"),true);
    if(!is_array($top) || (isset($top["Status"]) && !$top["Status"])){
        $t["state"]="error";
        $t["error"]=is_array($top)?(string)($top["Error"] ?? ""):"";
        return $t;
    }
    $all=array();
    foreach((is_array($top["entries"] ?? null)?$top["entries"]:array()) as $e){
        if(!is_array($e)){continue;}
        $p=(string)($e["path"] ?? "");
        $s=floatval($e["size"] ?? 0);
        if($p===""){continue;}
        if($p==="/"){$t["total"]=$s;continue;}
        if($s>0){$all[]=array("path"=>$p,"size"=>$s);}
    }
    if(is_array($latest) && floatval($latest["total_size"] ?? 0)>0){$t["total"]=floatval($latest["total_size"]);}
    $keep=array();
    foreach($all as $a){
        $covered=false;
        foreach($all as $b){
            if($b["path"]!==$a["path"] && strpos($b["path"],rtrim($a["path"],"/")."/")===0 && $b["size"]>=0.9*$a["size"]){$covered=true;break;}
        }
        if(!$covered){$keep[]=$a;}
    }
    usort($keep,function($x,$y){ return $y["size"]<=>$x["size"]; });
    $t["rows"]=array_slice($keep,0,10);
    $t["state"]=count($t["rows"])>0?"ok":"empty";
    return $t;
}
function nad_card_topdirs(int $id,array $t):string{
    $h=array();
    $link="";
    if($t["state"]!=="none"){
        $lbl=nad_h(nad_tr("nad_topdirs_open"));
        $link="<button type='button' class='nad-icobtn nad-td-link' onclick=\"Loadjs('fw.netagents.treesize.php?id=$id')\" title='$lbl' aria-label='$lbl'><i class='fas fa-up-right-from-square' aria-hidden='true'></i></button>";
    }
    $h[]="<section class='ak-card nad-td'><header class='nad-card-h'><h3><i class='fas fa-folder-tree' aria-hidden='true'></i> ".nad_tr("nad_topdirs")."</h3>$link</header>";
    if($t["state"]=="none"){
        $h[]="<div class='nad-empty'><i class='fas fa-circle-info' aria-hidden='true'></i> ".nad_tr("nad_topdirs_none")."</div>";
    }elseif($t["state"]=="error"){
        $h[]="<div class='nad-empty'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".nad_tr("nad_topdirs_error")
            .($t["error"]!==""?"<div class='nad-sub'>".nad_h($t["error"])."</div>":"")."</div>";
    }elseif($t["state"]=="empty"){
        $h[]="<div class='nad-empty'><i class='fas fa-circle-info' aria-hidden='true'></i> ".nad_tr("nad_topdirs_empty")."</div>";
    }else{
        $max=$t["rows"][0]["size"];
        $h[]="<ol class='nad-td-list'>";
        foreach($t["rows"] as $r){
            $w=$max>0?max(1,intval(round(100*$r["size"]/$max))):0;
            $pct=$t["total"]>0?round(100*$r["size"]/$t["total"],1):-1;
            $title=nad_h($r["path"]).($pct>=0?" &middot; ".nad_h(nad_tr("nad_topdirs_share",array($pct))):"");
            $h[]="<li class='nad-td-row' title='$title'><div class='nad-td-top'><span class='ak-mono nad-td-path'>".nad_h($r["path"])."</span>"
                ."<span class='nad-td-size'>".nad_bytes($r["size"])."</span></div>"
                ."<div class='nad-td-bar' aria-hidden='true'><div class='nad-td-fill' style='width:$w%'></div></div></li>";
        }
        $h[]="</ol>";
    }
    if($t["scan"]>0){
        $h[]="<div class='nad-td-foot nad-sub'><i class='far fa-clock' aria-hidden='true'></i> ".nad_tr("nad_topdirs_scanned",array(nagt_ago($t["scan"])))
            .($t["total"]>0?" &middot; ".nad_tr("nad_topdirs_total",array(nad_bytes($t["total"]))):"")."</div>";
    }
    $h[]="</section>";
    return implode("",$h);
}

function nad_disks_section(array $m):string{
    $n=count($m["disks"]);
    $h=array();
    $h[]="<section class='nad-hw'><h3 class='nad-sec-t'><i class='fas fa-hard-drive' aria-hidden='true'></i> ".nad_tr("hardware_disks")." <span class='ak-count'>$n</span></h3>";
    // La carte des plus gros repertoires suit les disques dans la grille a deux colonnes :
    // a cote du disque quand il n'y en a qu'un (l'espace libre de l'onglet), dessous sinon.
    $h[]="<div class='nad-grid'>";
    if($n==0){
        $h[]="<div class='ak-card nad-empty'><i class='fas fa-circle-info' aria-hidden='true'></i> ".nad_tr("nad_hw_empty")."</div>";
    }else{
        $i=0;
        foreach($m["disks"] as $d){$i++;$h[]=nad_disk_card($d,$i);}
    }
    if(isset($m["topdirs"]) && is_array($m["topdirs"])){$h[]=nad_card_topdirs(intval($m["id"] ?? 0),$m["topdirs"]);}
    $h[]="</div>";
    $h[]="</section>";
    return implode("",$h);
}
function nad_error_banner(int $id,array $m):string{
    $msg=nad_tr("nad_no_status");
    $detail=$m["error"]!=="" && $m["error"]!=="no-answer"?"<div class='nad-sub'>".nad_h($m["error"])."</div>":"";
    return "<section class='nad-alert'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i>"
        ."<div><strong>$msg</strong>$detail</div>".nad_refresh_btn($id)."</section>";
}

function nad_style():string{
    $ac=nad_accents();
    $a=$ac["accent"];
    return "<style>
.nad-na{cursor:help}
.nad-root{font-size:14px;color:#111827;padding:8px 0}
.nad-root *,.nad-root *:before,.nad-root *:after{box-sizing:border-box}
.nad-strong{font-weight:600}
.nad-sub{font-size:13px;color:#6b7280}
.nad-ok{color:#15803d}.nad-warn{color:#b45309}.nad-crit{color:#b91c1c}.nad-mute{color:#6b7280}.nad-norm{color:#111827}.nad-blue{color:#1d4ed8}
.nad-bg-ok{background:#15803d}.nad-bg-warn{background:#b45309}.nad-bg-crit{background:#b91c1c}
.nad-strip{display:flex;align-items:stretch;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.06);margin-bottom:24px}
.nad-cells{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));flex:1}
.nad-cell{padding:16px 20px;border-right:1px solid #e5e7eb;min-width:0}
.nad-cell-l{font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600}
.nad-cell-v{font-size:22px;font-weight:600;margin:4px 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nad-cell-s{font-size:13px;color:#6b7280;overflow:hidden;text-overflow:ellipsis}
.nad-strip-act{display:flex;align-items:center;padding:0 20px}
.nad-root button:focus-visible{outline:2px solid $a;outline-offset:2px}
.nad-btn.nad-busy i{animation:nad-spin 1s linear infinite}
@keyframes nad-spin{to{transform:rotate(360deg)}}
.nad-card-h{display:flex;justify-content:space-between;align-items:center;height:48px;padding:0 20px;border-bottom:1px solid #e5e7eb}
.nad-card-h h3,.nad-sec-t{margin:0;font-size:16px;font-weight:600;color:#111827}
.nad-card-h h3 i,.nad-sec-t i{color:$a;margin-right:6px}
.nad-fs-row{display:flex;align-items:center;gap:24px;padding:10px 20px;min-height:56px;border-bottom:1px solid #f1f5f9}
.nad-fs-row:last-child{border-bottom:0}
.nad-fs-name{width:220px;flex:0 0 220px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nad-fs-name>div{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nad-bar{flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;min-width:80px}
.nad-bar-f{height:8px;border-radius:4px;animation:nad-grow .4s ease-out}
@keyframes nad-grow{from{width:0}}
.nad-figs{display:flex;gap:24px}
.nad-fig{width:84px;text-align:right}
.nad-fig span{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
.nad-fig strong{font-weight:600}
.nad-pct{width:76px;text-align:right;font-size:16px;font-weight:600;white-space:nowrap}
.nad-empty{padding:24px 20px;color:#6b7280}
.nad-hw{margin-top:24px}
.nad-sec-t{margin-bottom:12px}
.nad-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;align-items:start}
.nad-disk{padding:20px}
.nad-disk-fail{border-left:3px solid #b91c1c}
.nad-dh{display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid #f1f5f9}
.nad-dh-ico{width:40px;height:40px;flex:0 0 40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px}
.nad-dh-t{flex:1;min-width:0}
.nad-dh-t .nad-sub{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nad-dev{font-size:16px;font-weight:600}
.nad-pill{display:inline-block;padding:1px 8px;border:1px solid;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap}
.nad-dh-size{font-size:18px;font-weight:600;white-space:nowrap}
.nad-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}
.nad-tile{background:#f8fafc;border-radius:6px;padding:10px 12px;min-height:72px;min-width:0}
.nad-tile-l{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;margin-bottom:6px}
.nad-tile-v{font-size:15px;font-weight:600}
.nad-tile-v .nad-sub{font-weight:400}
.nad-io{display:flex;flex-wrap:wrap;gap:6px 18px;font-size:13px;color:#6b7280;padding:10px 0;border-top:1px solid #f1f5f9}
.nad-io-t{font-weight:600;color:#374151}
.nad-io strong{color:#111827}
.nad-toggle{display:flex;justify-content:space-between;align-items:center;width:100%;padding:10px 0;border:0;border-top:1px solid #f1f5f9;background:none;font-size:13px;font-weight:600;color:#374151;cursor:pointer;text-align:left}
.nad-toggle i{transition:transform .2s}
.nad-toggle[aria-expanded=true] i{transform:rotate(180deg)}
.nad-details dl{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:0 0 12px;padding:12px;background:#f8fafc;border-radius:6px}
.nad-details dt{font-size:12px;color:#6b7280;font-weight:400}
.nad-details dd{margin:0;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nad-parts-t{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;margin:8px 0 6px}
.nad-parts{width:100%;border-collapse:collapse;font-size:13px;table-layout:auto}
.nad-parts th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;background:#f8fafc}
.nad-parts td{padding:0 8px;height:36px;border-bottom:1px solid #f1f5f9;color:#111827}
.nad-parts .nad-r{text-align:right;white-space:nowrap}
.nad-icobtn{border:0;background:none;color:#6b7280;padding:2px 4px;cursor:pointer}
.nad-icobtn:hover{color:$a}
.nad-td{min-width:0}
.nad-td-link{font-size:14px}
.nad-td-list{list-style:none;margin:0;padding:12px 20px 4px}
.nad-td-row{padding:5px 0;min-width:0}
.nad-td-top{display:flex;justify-content:space-between;align-items:baseline;gap:12px;font-size:13px}
.nad-td-path{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#111827}
.nad-td-size{flex:0 0 auto;font-weight:600;white-space:nowrap}
.nad-td-bar{height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;margin-top:4px}
.nad-td-fill{height:8px;border-radius:4px;background:$a;animation:nad-grow .4s ease-out}
.nad-td-foot{padding:6px 20px 14px}
.nad-alert{display:flex;align-items:center;gap:14px;padding:16px 20px;border:1px solid rgba(185,28,28,.35);background:rgba(185,28,28,.06);border-radius:8px;color:#7f1d1d}
.nad-alert>i{font-size:20px;color:#b91c1c}
.nad-alert>div{flex:1}
@media (max-width:1200px){.nad-grid{grid-template-columns:minmax(0,1fr)}.nad-fs-name{width:160px;flex-basis:160px}.nad-figs{gap:12px}.nad-fig{width:72px}.nad-cell{padding:14px}}
@media (prefers-reduced-motion:reduce){.nad-bar-f,.nad-td-fill{animation:none}.nad-btn.nad-busy i{animation:none}.nad-toggle i{transition:none}}
</style>";
}
function nad_js(int $id):string{
    $page=CurrentPageName();
    $copied=nad_h(nad_tr("nad_copied"));
    return "<script>
function NadReload(id){ LoadAjaxSilent('nad-host-'+id,'$page?agent-info-disk='+id+'&nad-inner=yes'); }
function NadFlush(id,page){
  var b=document.getElementById('nad-refresh-'+id); if(b){b.classList.add('nad-busy');b.disabled=true;}
  Loadjs(page+'?nad-refresh='+id);
}
function NadToggle(btn){
  var t=document.getElementById(btn.getAttribute('aria-controls')); if(!t){return;}
  var open=btn.getAttribute('aria-expanded')==='true';
  btn.setAttribute('aria-expanded',open?'false':'true'); if(open){t.setAttribute('hidden','');}else{t.removeAttribute('hidden');}
}
function NadCopy(btn){
  var t=btn.getAttribute('data-copy')||''; var done=function(){ btn.setAttribute('title','$copied'); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done); return; }
  var a=document.createElement('textarea'); a.value=t; a.style.position='fixed'; a.style.opacity='0';
  document.body.appendChild(a); a.select(); try{document.execCommand('copy');done();}catch(x){} document.body.removeChild(a);
}
if(window.NadTimer){clearInterval(window.NadTimer);}
window.NadTimer=setInterval(function(){
  if(!document.getElementById('nad-host-$id')){clearInterval(window.NadTimer);window.NadTimer=null;return;}
  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){return;}
  NadReload($id);
},60000);
</script>";
}
function nad_inner(int $id):string{
    $tpl=new template_admin();
    $m=nad_model($id);
    if(!$m["ok"]){
        return $tpl->_ENGINE_parse_body(nad_error_banner($id,$m));
    }
    $m["id"]=$id;
    $m["topdirs"]=nad_topdirs_model($id);
    return $tpl->_ENGINE_parse_body(nad_strip($id,$m).nad_card_fs($m).nad_disks_section($m));
}

function agent_info_disk():bool{
    $id=intval($_GET["agent-info-disk"]);
    if(isset($_GET["nad-inner"])){
        echo nad_inner($id);
        return true;
    }
    echo nad_style()."<div class='nad-root' id='nad-host-$id'>".nad_inner($id)."</div>".nad_js($id);
    return true;
}
/** Bouton Refresh : relit l'agent et lui fait relancer son inventaire disque. */
function nad_refresh():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_GET["nad-refresh"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id"));
    if(is_object($json) && isset($json->online) && !$json->online){
        $err=isset($json->error)?(string)$json->error:"";
        echo "NadReload($id);";
        return $tpl->js_error(nad_tr("offline").": ".$err);
    }
    echo "NadReload($id);";
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
    // DNS Firewall (dnsdist) status + graphs of the node, read through Meta: Artica nodes where
    // dnsdist is installed AND enabled (dnsdist.Status() keeps both keys, reported by the agent).
    if($AgentArtica && nagt_dnsfw_active($id)){
        $array["{APP_DNS_FIREWALL}"]="fw.netagents.dnsfw.node.php?id=$id";
    }
    if($AgentArtica){
        $array["{l2_switching}"]="fw.netagents.l2.php?id=$id";
    }

    //$array["{agents_list}"]="$page?agents-list=yes";
    echo $tpl->tabs_default($array);
    return true;
}
// DNS Firewall installed and enabled on the node (artica_settings sent by the agent).
function nagt_dnsfw_active(int $id):bool{
    $agent=new ArticaNetAgents($id);
    return (string)$agent->GET_INFO("APP_DNSDIST_INSTALLED")==="1" && (string)$agent->GET_INFO("EnableDNSDist")==="1";
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
// ── Onglet « Artica » d'un agent (maquette stitch_netagents_artica) ─────────
// Données : /netagents/softwares/{id} (versions lues sur l'agent, cache 5 min) et
// /netagents/dashboard/list/updates (comparaison avec le dépôt Meta, toutes les 15 min).
function agent_artica_softs():bool{
    $id=intval($_GET["agent-info-artica"]);
    echo nart_style()."<div id='nart-host-$id'>".nart_inner($id)."</div>";
    echo "<script>$('#agent-info-status-$id').remove();</script>";
    return true;
}
function agent_artica_softs_popup():bool{
    // Pentest 2026-11-04 : agent-info-artica-popup etait recopie sans encodage dans la page (XSS).
    $id=intval($_GET["agent-info-artica-popup"]);
    echo nart_inner($id);
    return true;
}

// Logiciels suivis par netagents (trackedSoftwares de Dashboard.go) :
// code du dépôt, clé de version dans artica_settings, préfixe de version.
function nart_catalog():array{
    return array(
        array("APP_NGINX","APP_NGINX_VERSION",""),array("APP_PDNS","PDNSVersion",""),
        array("APP_DNSDIST","APP_DNSDIST_VERSION",""),array("APP_REDIS_SERVER","APP_REDIS_SERVER_VERSION",""),
        array("WP_CLIENT","WP_CLIENT_VERSION",""),array("APP_C_ICAP","CicapVersion",""),
        array("APP_SYSLOGD","APP_SYSLOGD_VERSION",""),array("APP_RDPPROXY","APP_RDPPROXY_VERSION",""),
        array("APP_CLAMAV","ClamAVDaemonVersion",""),array("APP_HAPROXY","HAPROXY_VERSION",""),
        array("APP_MONIT","APP_MONIT_VERSION",""),array("APP_NAGIOS_CLIENT","APP_NAGIOS_CLIENT_VERSION",""),
        array("APP_AUTOFS","APP_AUTOFS_VERSION",""),array("APP_GLUSTERFS","APP_GLFS_VERSION",""),
        array("APP_SAMBA","APP_SAMBA_VERSION",""),array("APP_NETDATA","APP_NETDATA_VERSION",""),
        array("APP_SQUID","SquidVersion","5."),array("APP_SQUID6","SquidVersion","6."),
    );
}

// Cles d'activation lues dans artica_settings (fichiers de settings/Daemons/ du noeud),
// verifiees dans le code articarest qui les lit (TokenEnabled du module) :
// le logiciel est "en service" des qu'une de ses cles vaut 1. Cle absente = 0, sauf
// EnableMonit (monit.Start() la pose a 1 si le fichier manque). Sans cle connue
// (APP_SYSLOGD = rsyslog du systeme, WP_CLIENT) : en service des qu'il est installe.
function nart_usage_keys():array{
    return array(
        "APP_NGINX"=>array("EnableNginx"),                         // nginx/nginx.go
        "APP_PDNS"=>array("EnablePDNS"),                           // powerdns/powerdns.go
        "APP_DNSDIST"=>array("EnableDNSDist"),                     // dnsdist/dnsdist.go
        "APP_REDIS_SERVER"=>array("EnableRedisService"),           // RedisSrv/RedisSrv.go
        "APP_C_ICAP"=>array("CicapEnabled"),                       // cicap/cicap.go
        "APP_RDPPROXY"=>array("EnableRDPProxy"),                   // RDPProxy/RDPProxy.go
        "APP_CLAMAV"=>array("EnableClamavDaemon"),                 // clamavDaemon/clamavDaemon.go
        // haproxy sert aussi l'equilibreur HaCluster et PulseReverse (HAPROXY_VERSION posee par les trois).
        "APP_HAPROXY"=>array("EnableHaProxy","Enablehacluster","EnablePulseReverse"),
        "APP_MONIT"=>array("EnableMonit"),                         // monit/monit.go
        "APP_NAGIOS_CLIENT"=>array("EnableNagiosClient"),          // NagiosClient/NagiosClient.go
        "APP_AUTOFS"=>array("AutoFSEnabled"),                      // autofs/autofs.go
        "APP_GLUSTERFS"=>array("GlusterFSEnabled"),                // glusterfs/glusterfs.go
        // winbindd (authentification NTLM du proxy) fait partie de Samba.
        "APP_SAMBA"=>array("EnableSamba","EnableWinbindd"),        // samba/samba.go, winbindd/winbindd.go
        "APP_NETDATA"=>array("NetDATAEnabled"),                    // ArNetdata/ArNetdata.go
        "APP_SQUID"=>array("SQUIDEnable"),"APP_SQUID6"=>array("SQUIDEnable"),
    );
}
// true : en service ; false : installe mais pas utilise. Sans artica_settings (agent
// ancien), rien n'est classe : tout reste "en service", comme avant.
function nart_in_use(string $code,array $set):bool{
    if(count($set)==0){return true;}
    $map=nart_usage_keys();
    if(!isset($map[$code])){return true;}
    foreach($map[$code] as $k){
        if(!isset($set[$k])){
            if($k=="EnableMonit"){return true;}
            continue;
        }
        if(intval(trim((string)$set[$k]))==1){return true;}
    }
    return false;
}

function nart_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function nart_label(string $code):string{
    $t=nagt_t($code);
    if($t==="" || $t==="{".$code."}"){return nart_h($code);}
    return $t;
}

function nart_missing(string $v):bool{
    $v=trim($v);
    return $v==="" || preg_match('/^0(\.0)*$/',$v)==1;
}

function nart_model(int $id):array{
    $m=array("id"=>$id,"ok"=>false,"version"=>"","sp"=>"","hotfix"=>"","debian"=>0,"debian_name"=>"",
        "read_at"=>0,"refreshing"=>false,"compared"=>false,"last_run"=>0,"apt"=>-1,"sp_avail"=>0,"hf_avail"=>0,
        "rows"=>array(),"n_update"=>0,"n_ok"=>0,"n_missing"=>0,"n_update_unused"=>0,"n_unused"=>0);
    $soft=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/$id"),true);
    if(!is_array($soft) || isset($soft["Status"]) || nart_missing((string)($soft["artica_version"] ?? ""))){return $m;}
    $m["ok"]=true;
    $m["version"]=(string)$soft["artica_version"];
    $m["sp"]=trim((string)($soft["artica_service_pack"] ?? ""));
    $m["hotfix"]=trim((string)($soft["artica_hotfix_version"] ?? ""));
    $m["debian"]=intval($soft["debian_version"] ?? 0);
    $m["debian_name"]=(string)($soft["debian_version_name"] ?? "");
    $m["read_at"]=nai_epoch($soft["cached_at"] ?? "");
    $m["refreshing"]=!empty($soft["refreshing"]);
    $set=(isset($soft["artica_settings"]) && is_array($soft["artica_settings"]))?$soft["artica_settings"]:array();

    $updates=array();
    $dash=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dashboard/list/updates"),true);
    if(is_array($dash) && isset($dash["Softwares"])){
        $m["compared"]=true;
        foreach(($dash["Softwares"] ?: array()) as $r){
            if(intval($r["agent_id"] ?? 0)!=$id){continue;}
            foreach(($r["updates"] ?? array()) as $u){$updates[(string)$u["app_code"]]=(string)$u["available_version"];}
        }
        $m["apt"]=0;
        foreach(($dash["AptSoftwares"] ?: array()) as $r){
            if(intval($r["agent_id"] ?? 0)==$id){$m["apt"]=intval($r["upgradable_count"] ?? 0);}
        }
        foreach(($dash["ArticaSP"] ?: array()) as $r){
            if(intval($r["agent_id"] ?? 0)==$id){$m["sp_avail"]=intval($r["available_service_pack"] ?? 0);}
        }
        foreach(($dash["ArticaHotfix"] ?: array()) as $r){
            if(intval($r["agent_id"] ?? 0)==$id){$m["hf_avail"]=intval($r["available_hotfix_count"] ?? 0);}
        }
        $sum=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dashboard/updates"),true);
        if(is_array($sum)){$m["last_run"]=nai_epoch($sum["LastRun"] ?? "");}
    }

    $squidSeen=false;
    foreach(nart_catalog() as $c){
        list($code,$key,$prefix)=$c;
        $inst=trim((string)($set[$key] ?? ""));
        $avail=$updates[$code] ?? "";
        // Squid 5 et Squid 6 partagent SquidVersion : seule la branche installée a sa ligne.
        if($prefix!==""){
            $match=!nart_missing($inst) && strpos($inst,$prefix)===0;
            if(!$match && $avail===""){continue;}
            if(!$match){$inst="";}
            $squidSeen=true;
        }
        // Sans comparaison avec le dépôt, une version installée n'est ni à jour ni en retard.
        $kind=nart_missing($inst)?"missing":($avail!==""?"update":($m["compared"]?"ok":"known"));
        $m["rows"][]=array("code"=>$code,"label"=>nart_label($code),"installed"=>$inst,"available"=>$avail,"kind"=>$kind,
            "used"=>$kind=="missing"?false:nart_in_use($code,$set));
    }
    if(!$squidSeen){
        // Squid installe d'une branche que Dashboard.go ne suit pas (7.x) : on l'affiche avec sa
        // version, sans comparaison, au lieu d'un "Squid 6 non installe" trompeur.
        $sqv=trim((string)($set["SquidVersion"] ?? ""));
        if(!nart_missing($sqv)){
            $m["rows"][]=array("code"=>"APP_SQUID","label"=>nart_label("APP_SQUID"),"installed"=>$sqv,"available"=>"","kind"=>"known",
                "untracked"=>true,"used"=>nart_in_use("APP_SQUID",$set));
        }else{
            $m["rows"][]=array("code"=>"APP_SQUID6","label"=>nart_label("APP_SQUID6"),"installed"=>"","available"=>"","kind"=>"missing","used"=>false);
        }
    }
    usort($m["rows"],function($a,$b){ return strcasecmp(strip_tags($a["label"]),strip_tags($b["label"])); });
    // Compteurs du bandeau : logiciels en service seulement ; les mises a jour des logiciels
    // installes mais non utilises sont comptees a part (n_update_unused).
    $m["n_update_unused"]=0;$m["n_unused"]=0;
    foreach($m["rows"] as $r){
        if($r["kind"]=="missing"){$m["n_missing"]++;continue;}
        if(!$r["used"]){
            $m["n_unused"]++;
            if($r["kind"]=="update"){$m["n_update_unused"]++;}
            continue;
        }
        if($r["kind"]=="update"){$m["n_update"]++;}elseif($r["kind"]=="ok"){$m["n_ok"]++;}
    }
    return $m;
}

function nart_inner(int $id):string{
    $tpl=new template_admin();
    $m=nart_model($id);
    $h=array();
    $h[]="<div class='ak nart'>";
    if(!$m["ok"]){
        $h[]=nart_no_info($id);
    }else{
        $h[]=nart_identity($m);
        $h[]="<div class='ak-figs nart-figs'>".nart_fig_updates($m).nart_fig_ok($m).nart_fig_apt($m)."</div>";
        $h[]=nart_compared_note($m);
        $h[]=nart_table($m);
    }
    $h[]="</div>";
    return $tpl->_ENGINE_parse_body(implode("\n",$h)).nart_js($m);
}

function nart_refresh_btn(int $id):string{
    return "<button type='button' class='ak-btn' OnClick=\"NartReload($id)\" aria-label='{refresh}'><i class='fas fa-rotate' aria-hidden='true'></i> {refresh}</button>";
}

function nart_no_info(int $id):string{
    return "<div class='ak-empty'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{nart_no_info}</span>".nart_refresh_btn($id)."</div>";
}

function nart_identity(array $m):string{
    $ver=preg_replace('/(\.\d+?)0+$/','$1',$m["version"]);
    $ver=preg_replace('/\.0$/','',$ver);
    $chips=array();
    if($m["sp"]!=="" && $m["sp"]!=="0"){$chips[]="<span class='ak-chip'>{service_pack} ".nart_h($m["sp"])."</span>";}
    if($m["hotfix"]!=="" && strlen($m["hotfix"])>3){$chips[]="<span class='ak-chip'>{hotfix} ".nart_h($m["hotfix"])."</span>";}
    if($m["sp_avail"]>0){
        $chips[]="<span class='ak-chip ak-warn'><i class='fas fa-arrow-up' aria-hidden='true'></i> ".str_replace("%s",intval($m["sp_avail"]),nagt_t("nart_sp_available"))."</span>";
    }
    if($m["hf_avail"]>0){
        $chips[]="<span class='ak-chip ak-warn'><i class='fas fa-arrow-up' aria-hidden='true'></i> ".str_replace("%s",intval($m["hf_avail"]),nagt_t("nart_hf_available"))."</span>";
    }
    $deb=$m["debian"]>0?"Debian ".$m["debian"].($m["debian_name"]!==""?" (".nart_h($m["debian_name"]).")":""):"";
    $read=$m["read_at"]>0?str_replace("%s",nagt_ago($m["read_at"]),nagt_t("nart_versions_read")):"";
    $busy=$m["refreshing"]?"<span class='ak-state ak-info'><i class='fas fa-spinner fa-spin' aria-hidden='true'></i> {nart_refreshing}</span>":"";
    return "<section class='ak-strip nart-id' aria-label='Artica'>"
        ."<span class='ak-ico'><i class='fas fa-cube' aria-hidden='true'></i></span>"
        ."<div class='ak-chips ak-grow'><span class='nart-t' title='".nart_h($m["version"])."'>Artica ".nart_h($ver)."</span>".implode("",$chips)."</div>"
        ."<div class='ak-strip-side'>".($deb!==""?"<span class='nart-deb'><i class='fas fa-server' aria-hidden='true'></i> $deb</span>":"")
        ."<span><i class='far fa-clock' aria-hidden='true'></i> $read</span>$busy</div>"
        .nart_refresh_btn($m["id"])
        ."</section>";
}

// $tone : "" (neutre), "ak-warn", "ak-ok".
function nart_fig(string $tone,string $ico,string $label,string $value,string $sub):string{
    return "<div class='ak-fig $tone'><div class='ak-fig-txt'><span class='ak-fig-l'>$label</span>"
        ."<span><span class='ak-fig-v'>$value</span> <span class='ak-fig-s'>$sub</span></span></div>"
        ."<span class='ak-ico'><i class='fas $ico' aria-hidden='true'></i></span></div>";
}

function nart_fig_updates(array $m):string{
    if(!$m["compared"]){return nart_fig("","fa-arrow-up-right-dots","{nart_updates_available}","—","");}
    $tone=$m["n_update"]>0?"ak-warn":"";
    $sub=$m["n_update"]>0?"{nart_newer_found}":"{nart_nothing_to_do}";
    if($m["n_update_unused"]>0){
        $sub.="<br><span class='nart-unused-n'>".str_replace("%s",intval($m["n_update_unused"]),nagt_t("nart_updates_unused"))."</span>";
    }
    return nart_fig($tone,"fa-arrow-up-right-dots","{nart_updates_available}",(string)$m["n_update"],$sub);
}

function nart_fig_ok(array $m):string{
    if(!$m["compared"]){return nart_fig("","fa-circle-check","{nart_up_to_date}","—","");}
    return nart_fig("ak-ok","fa-circle-check","{nart_up_to_date}",(string)$m["n_ok"],"{nart_same_as_repo}");
}

function nart_fig_apt(array $m):string{
    $v=$m["apt"]<0?"—":(string)$m["apt"];
    return nart_fig($m["apt"]>0?"ak-warn":"","fa-box","{nart_debian_packages}",$v,"{nart_apt_pending}");
}

function nart_compared_note(array $m):string{
    if(!$m["compared"] || $m["last_run"]==0){
        return "<p class='ak-note'><i class='fas fa-rotate' aria-hidden='true'></i> {nart_never_compared}</p>";
    }
    return "<p class='ak-note'><i class='fas fa-rotate' aria-hidden='true'></i> ".str_replace("%s",nagt_ago($m["last_run"]),nagt_t("nart_compared"))."</p>";
}

function nart_table(array $m):string{
    $up=array();$ok=array();$miss=array();$uup=array();$uok=array();
    foreach($m["rows"] as $r){
        if($r["kind"]=="missing"){$miss[]=nart_row($m,$r);continue;}
        if(empty($r["used"])){
            if($r["kind"]=="update"){$uup[]=nart_row($m,$r);}else{$uok[]=nart_row($m,$r);}
            continue;
        }
        if($r["kind"]=="update"){$up[]=nart_row($m,$r);}else{$ok[]=nart_row($m,$r);}
    }
    $head="<thead><tr><th scope='col'>{nart_software}</th><th scope='col'>{nart_installed}</th><th scope='col'>{nart_available}</th><th scope='col'><span class='sr-only'>{nart_update}</span></th></tr></thead>";
    $body=implode("",$up).implode("",$ok);
    if($body===""){$body="<tr><td colspan='4' class='ak-muted'>{nart_none_in_use}</td></tr>";}
    $h="<section class='ak-card'><div class='nart-sec-t'>{nart_in_use}</div><table class='ak-tbl nart-tbl'>$head<tbody>$body</tbody></table>";
    // Installes mais non utilises par le noeud (cle d'activation a 0) : replies, Update gardes.
    $nu=count($uup)+count($uok);
    if($nu>0){
        $badge=count($uup)>0?" <span class='ak-chip ak-warn'>".str_replace("%s",count($uup),nagt_t("nart_n_updates"))."</span>":"";
        $h.="<details class='ak-details nart-unused'><summary><span>{nart_installed_unused}</span> <span class='ak-count'>$nu</span>$badge<i class='fas fa-chevron-down ak-chev' aria-hidden='true'></i></summary>"
            ."<p class='ak-note nart-unused-help'>{nart_installed_unused_help}</p>"
            ."<table class='ak-tbl nart-tbl'><tbody>".implode("",$uup).implode("",$uok)."</tbody></table></details>";
    }
    if(count($miss)>0){
        $h.="<details class='ak-details nart-miss'><summary><span>{nart_not_installed}</span> <span class='ak-count'>".count($miss)."</span><i class='fas fa-chevron-down ak-chev' aria-hidden='true'></i></summary>"
            ."<table class='ak-tbl nart-tbl'><tbody>".implode("",$miss)."</tbody></table></details>";
    }
    return $h."</section>";
}

function nart_row(array $m,array $r):string{
    $code=nart_h($r["code"]);
    $name="<span class='ak-name'>".$r["label"]."</span><span class='ak-code'>$code</span>";
    if($r["kind"]=="missing"){
        $inst="<em class='ak-muted'>{nart_not_installed}</em>";
    }else{
        $inst="<span class='ak-mono'>".nart_h($r["installed"])."</span>";
    }
    if($r["available"]!==""){
        $avail="<span class='ak-chip ak-warn'><i class='fas fa-arrow-right' aria-hidden='true'></i> ".nart_h($r["available"])."<span class='sr-only'> {nart_update_available}</span></span>";
    }elseif($r["kind"]=="ok"){
        $avail="<span class='ak-state ak-ok'><i class='fas fa-check' aria-hidden='true'></i> {nart_up_to_date}</span>";
    }elseif(!empty($r["untracked"])){
        $avail="<span class='ak-muted'>{nart_not_tracked}</span>";
    }else{
        $avail="<span class='ak-muted'>—</span>";
    }
    $act="";
    if($r["kind"]=="update" && nai_is_admin()){
        $js="Loadjs('fw.netagents.update.software.single.php?id=".intval($m["id"])."&soft=$code&debver=".intval($m["debian"])."')";
        $act="<button type='button' class='ak-btn' OnClick=\"$js\" aria-label=\"{nart_update} ".nart_h(strip_tags($r["label"]))."\"><i class='fas fa-download' aria-hidden='true'></i> {nart_update}</button>";
    }
    return "<tr class='nart-r-{$r["kind"]}'><td>$name</td><td>$inst</td><td>$avail</td><td class='ak-end'>$act</td></tr>";
}

// Seules les regles propres a l'onglet : le reste vient du kit ak- (angular.css.php).
function nart_style():string{
    if(isset($GLOBALS["NART_STYLE"])){return "";}
    $GLOBALS["NART_STYLE"]=true;
    $c=array();
    $c[]=".nart{padding:4px 2px 8px}.nart-id{min-height:72px}.nart .ak-figs{margin-top:16px}";
    $c[]=".nart-t{font-size:18px;font-weight:600;margin-right:4px}.nart-deb{color:#0f172a;font-weight:600;font-size:14px}";
    $c[]=".nart-tbl{table-layout:fixed}.nart-tbl th:nth-child(1),.nart-tbl td:nth-child(1){width:44%}.nart-tbl th:nth-child(2),.nart-tbl td:nth-child(2){width:18%}.nart-tbl th:nth-child(3),.nart-tbl td:nth-child(3){width:22%}";
    $c[]=".nart-miss{border-top:1px solid #e2e8f0}.nart-r-missing td{color:#64748b}";
    $c[]=".nart-sec-t{padding:12px 16px 0;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#475569}";
    $c[]=".nart-unused{border-top:1px solid #e2e8f0}.nart-unused-help{margin:0;padding:4px 16px 8px}.nart-unused-n{color:#64748b;font-weight:400}";
    return "<style>".implode("\n",$c)."</style>";
}

function nart_js(array $m):string{
    $page=CurrentPageName();
    $id=intval($m["id"]);
    $again=$m["refreshing"]?"if(window.NartTimer){clearTimeout(window.NartTimer);} window.NartTimer=setTimeout(function(){ if(document.getElementById('nart-host-$id') && !document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){ NartReload($id); } },20000);":"";
    return "<script>window.NartReload=function(id){ LoadAjaxSilent('nart-host-'+id,'$page?agent-info-artica-popup='+id); };$again</script>";
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

    if(!nai_is_admin()){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    // Remontees Meta 12/11 : l'appel visait POST /netagents/update/{id}, la mise a jour de la
    // FICHE agent (nom, description, port, prefixe) : postee vide, elle effacait le nom et la
    // description et remettait le port a 28811, puis la reponse {"Status":true} sans
    // "success" s'affichait "protocol error". La bonne route lance apt-get update sur
    // l'agent, en arriere-plan, puis le rafraichissement de sa liste de paquets.
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/apt-update/$id",array()),true);
    if(!is_array($json)){
        return $tpl->js_error("{protocol_error}");
    }
    // Refus d'articarest (agent inconnu, blackliste, injoignable) : {"Status":false,"Error":"..."}.
    if(isset($json["Status"]) && !$json["Status"]){
        return $tpl->js_error((string)($json["Error"] ?? "{protocol_error}"));
    }
    // Reponse de l'agent relayee : {"success":bool,"message":"..."} (409 si apt-get tourne deja).
    if(!isset($json["success"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["success"]){
        return $tpl->js_error((string)($json["message"] ?? "{protocol_error}"));
    }
    $Netagent=new ArticaNetAgents($id);
    $Hostname=$Netagent->GetAgentHostname();
    admin_tracks("Meta: Running an update check on $Hostname");
    return $tpl->js_ok("$Hostname: {nai_apt_update_started}");
}
function blacklisted_js():bool{
    $agent_id=intval($_GET["blacklisted-js"]);
    if(!nai_is_admin()){
        $tpl=new template_admin();
        return $tpl->js_error("{ERROR_NO_PRIVS2}");
    }
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
    // La fiche agent se redessine aussitot (bascule de l'onglet d'identite).
    echo "if(typeof NaiReload==='function'){NaiReload();}";
    return admin_tracks("set $AgentName Blacklisted for updates to $blacklistedInt");
}
// ---------------------------------------------------------------------------
// Fiche agent, onglet d'identite (?agent-info-status=) — maquette Stitch
// stitch_artica_meta1. Un modele (nai_model) calcule une fois, un widget par
// carte. CSS et JS concatenes HORS de _ENGINE_parse_body.
// ---------------------------------------------------------------------------


function nai_is_admin():bool{
    $users=new usersMenus();
    return meta_can_view($users);
}

// Horodatage Go (RFC3339) -> epoch ; 0 pour le temps nul de Go ou une valeur vide.
function nai_epoch($iso):int{
    $iso=trim((string)$iso);
    if($iso=="" || strpos($iso,"0001-01-01")===0 || strpos($iso,"1970-01-01")===0){return 0;}
    $ts=strtotime($iso);
    if($ts===false){return 0;}
    return intval($ts);
}

function nai_model($id,$artica):array{
    $m=array("id"=>$id,"error"=>"","artica"=>$artica,"admin"=>nai_is_admin());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"),true);
    if(!is_array($json)){
        $m["error"]="Failed to fetch agent info";
        return $m;
    }
    if(isset($json["Status"]) && !$json["Status"]){
        $m["error"]=isset($json["Error"])?(string)$json["Error"]:"error";
        return $m;
    }
    $agent=new ArticaNetAgents($id);
    $st=$agent->Status();

    $m["hostname"]=isset($st["hostname"]) && $st["hostname"]!=""?$st["hostname"]:(string)($json["hostname"]??"");
    $m["description"]=trim((string)($json["description"]??""));
    $m["state"]=strtolower((string)($json["status"]??""));
    $m["last_seen"]=nai_epoch($json["last_seen"]??"");
    $m["registered_at"]=nai_epoch($json["registered_at"]??"");
    $m["registered_iso"]=(string)($json["registered_at"]??"");
    $rawip=(string)($json["ip_address"]??"");
    $m["address"]=nagt_is_reverse($rawip)
        ?nagt_display_address($rawip,0,nagt_lan_ips($st))
        :$rawip.":".intval($json["port"]??0).(string)($json["path_prefix"]??"");

    $m["groups"]=array();
    if(isset($json["groups"]) && is_array($json["groups"])){
        foreach($json["groups"] as $name=>$gid){$m["groups"][]=array("name"=>(string)$name,"id"=>intval($gid));}
    }

    $m["version"]=(string)($json["version"]??"");
    $m["available"]=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion"));
    $m["outdated"]=$m["available"]!="" && $m["version"]!="" && version_compare($m["available"],$m["version"],">");

    // Liste noire : celle de l'agent, ou heritee d'un groupe blackliste.
    $m["blacklisted"]=!empty($st["blacklisted"]);
    $m["bl_group"]="";
    if(isset($st["groups"]) && is_array($st["groups"])){
        foreach($st["groups"] as $g){
            if(!empty($g["blacklisted"])){$m["bl_group"]=(string)($g["group_name"]??"");$m["blacklisted"]=true;break;}
        }
    }
    $m["unmanaged"]=!empty($st["unmanaged"]);

    $m["license"]=null;
    if(isset($st["artica_license"]) && is_array($st["artica_license"])){$m["license"]=$st["artica_license"];}
    // Repartiteur HaCluster (reglage en cache, comme l'etiquette de role de la liste).
    $m["hacluster"]=$artica && intval($agent->GET_INFO("Enablehacluster"))==1;
    $m["hc_nodes"]=$m["hacluster"]?nai_hacluster_nodes_count($id):-1;
    $m["snapshots"]=-1;   // -1 = l'agent ne publie pas de base d'instantanes
    if(isset($st["artica_snapshots"])){$m["snapshots"]=is_array($st["artica_snapshots"])?count($st["artica_snapshots"]):0;}
    $m["apt"]=isset($st["aptget"]) && is_array($st["aptget"])?$st["aptget"]:null;

    $m["cert"]=null;
    if(isset($json["client_cert_info"]) && is_array($json["client_cert_info"])){
        $c=$json["client_cert_info"];
        $m["cert"]=array(
            "issuer"=>(string)($c["issuer"]??""),
            "not_before"=>nai_epoch($c["not_before"]??""),
            "not_after"=>nai_epoch($c["not_after"]??""),
            "days"=>intval($c["days_until_expiry"]??0),
            "expired"=>!empty($c["is_expired"]),
            "dns"=>isset($c["dns_names"]) && is_array($c["dns_names"])?$c["dns_names"]:array());
    }

    $m["artica_version"]="";
    $m["webconsole"]=null;
    if($artica){
        $m["artica_version"]=AgentArticaVersion($id);
        $w=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/webconsole/config/$id"),true);
        if(is_array($w)){$m["webconsole"]=$w;}
    }
    return $m;
}

// Date complete, toujours : time_to_date() n'affiche que l'heure quand le jour
// est aujourd'hui, ce qui rend une date d'expiration illisible.
function nai_date($ts):string{
    if($ts<=0){return "-";}
    return date("Y-m-d H:i",$ts);
}

// Nombre de noeuds de la ferme HaCluster : la route relaie la question au repartiteur en direct,
// la popup se recharge toutes les 5 s, donc on garde la reponse 60 s en session. -1 = inconnu.
function nai_hacluster_nodes_count($id):int{
    $key="NAI_HC_NODES_".intval($id);
    if(isset($_SESSION[$key]) && is_array($_SESSION[$key]) && (time()-intval($_SESSION[$key][0]))<60){return intval($_SESSION[$key][1]);}
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/hacluster/nodes/".intval($id)),true);
    $n=-1;
    if(is_array($j) && !empty($j["Status"]) && isset($j["nodes"]) && is_array($j["nodes"])){
        $n=0;
        foreach($j["nodes"] as $node){if(is_array($node) && intval($node["id"] ?? 0)>0){$n++;}}
    }
    $_SESSION[$key]=array(time(),$n);
    return $n;
}

function nai_e($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

// Pastille : toujours icone + mot. $kind = ok|warn|err|neutral
function nai_pill($kind,$ico,$text):string{
    $tones=array("ok"=>"ak-ok","warn"=>"ak-warn","err"=>"ak-err","neutral"=>"ak-off");
    $tone=isset($tones[$kind])?$tones[$kind]:"ak-off";
    return "<span class='ak-pill $tone'><i class='$ico' aria-hidden='true'></i>$text</span>";
}

// Ligne cle/valeur. Avec $js, toute la ligne ouvre une fenetre (chevron, clavier).
function nai_row($label,$value,$js="",$aria=""):string{
    if($js==""){
        return "<div class='nai-row'><div class='nai-lbl'>$label</div><div class='nai-val'>$value</div></div>";
    }
    return "<div class='nai-row nai-row-go' role='button' tabindex='0' aria-label=\"$aria\" OnClick=\"$js\" OnKeyDown=\"NaiKey(event,this)\">"
        ."<div class='nai-lbl'>$label</div><div class='nai-val'>$value</div>"
        ."<i class='fa-solid fa-chevron-right nai-chev' aria-hidden='true'></i></div>";
}

function nai_card($ico,$title,$body):string{
    return "<section class='nai-card'><h3 class='nai-card-h'><i class='$ico' aria-hidden='true'></i>$title</h3>$body</section>";
}

function nai_switch($on,$js,$disabled,$aria):string{
    $state=$on?"true":"false";
    $word=$on?"{nai_yes}":"{nai_no}";
    $dis=$disabled?" disabled":"";
    $click=$disabled?"":" OnClick=\"$js\"";
    return "<span class='nai-sw-wrap'><button type='button' class='ak-sw' role='switch' aria-checked='$state' aria-label=\"$aria\"$dis$click>"
        ."</button><span class='nai-sw-word'>$word</span></span>";
}

function nai_hero($m):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $states=array(
        "online"=>array("ok","fa-solid fa-circle-check","{nai_online}"),
        "offline"=>array("err","fa-solid fa-circle-xmark","{nai_offline}"),
        "pending"=>array("warn","fa-solid fa-hourglass-half","{nai_pending}"),
        "error"=>array("err","fa-solid fa-triangle-exclamation","{nai_error}"));
    $s=isset($states[$m["state"]])?$states[$m["state"]]:array("neutral","fa-solid fa-circle-question",nai_e($m["state"]==""?"-":$m["state"]));
    $seen="{nai_never}";
    if($m["last_seen"]>0){$seen=distanceOfTimeInWords($m["last_seen"],time(),true);}

    $h=array();
    $h[]="<div class='nai-hero nai-hero-{$s[0]}'>";
    $h[]="<span class='nai-hero-ico' aria-hidden='true'><i class='fa-solid fa-server'></i></span>";
    $h[]="<div class='nai-hero-id'>";
    $h[]="<div class='nai-hero-l1'><span class='nai-host'>".nai_e($m["hostname"])."</span>".nai_pill($s[0],$s[1],$s[2])
        ."<span class='nai-seen'>{nai_last_seen} $seen</span></div>";
    $l2=array();
    if($m["description"]!=""){$l2[]="<span class='nai-desc'>".nai_e($m["description"])."</span>";}
    if($m["blacklisted"]){$l2[]=nai_pill("err","fa-solid fa-ban","{nai_blacklisted}");}
    if($m["unmanaged"]){$l2[]=nai_pill("warn","fa-solid fa-user-slash","{nai_unmanaged}");}
    if($m["artica"]){$l2[]=nai_pill("neutral","fa-solid fa-cubes","Artica");}
    if(count($l2)>0){$h[]="<div class='nai-hero-l2'>".implode("",$l2)."</div>";}
    $h[]="</div>";
    if($m["admin"]){
        $h[]="<div class='nai-hero-act'>";
        $h[]="<button type='button' class='nai-ibtn' aria-label=\"{nai_edit_hostname}\" title=\"{nai_edit_hostname}\" OnClick=\"Loadjs('fw.netagents.hostname.php?id=$id')\"><i class='fa-solid fa-pencil' aria-hidden='true'></i></button>";
        $h[]="<span class='nai-vsep' aria-hidden='true'></span>";
        $h[]="<button type='button' class='ak-btn nai-btn-danger' OnClick=\"Loadjs('$page?reboot-js=$id')\"><i class='fa-solid fa-power-off' aria-hidden='true'></i>{nai_reboot}</button>";
        $h[]="</div>";
    }
    $h[]="</div>";
    return implode("",$h);
}

function nai_card_connection($m):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $tpl=new template_admin();
    $r=array();

    $addr="<span class='nai-mono'>".nai_e($m["address"])."</span>";
    if($m["admin"] && meta_provider_or_sysadmin()){
        $addr.="<button type='button' class='nai-ibtn nai-ibtn-sm' aria-label=\"{nai_edit_address}\" title=\"{nai_edit_address}\" OnClick=\"Loadjs('$page?agent-address-js=$id')\"><i class='fa-solid fa-pencil' aria-hidden='true'></i></button>";
    }
    $r[]=nai_row("{nai_address}",$addr);

    if(count($m["groups"])==0){
        $gv="<span class='ak-muted'>{nai_no_group}</span>";
    }else{
        $chips=array();
        foreach($m["groups"] as $g){
            $chips[]="<button type='button' class='nai-chip' OnClick=\"Loadjs('fw.netagents.groups.php?gpid={$g["id"]}')\"><i class='fa-solid fa-layer-group' aria-hidden='true'></i>".nai_e($g["name"])."</button>";
        }
        $gv="<span class='ak-chips'>".implode("",$chips)."</span>";
    }
    $r[]=nai_row("{nai_groups}",$gv);

    $reg="-";
    if($m["registered_at"]>0){
        $reg="<span title=\"".nai_e($m["registered_iso"])."\">".nai_date($m["registered_at"])."</span>";
    }
    $r[]=nai_row("{nai_registered_at}",$reg);

    if($m["artica"] && is_array($m["webconsole"])){
        $w=$m["webconsole"];
        $unix=isset($w["unix_socket"]) && strlen((string)$w["unix_socket"])>0;
        if($unix){
            $where="<span class='ak-tag'>{nai_unix_socket}</span><span class='nai-mono'>".nai_e($w["unix_socket"])."</span>";
        }else{
            $where="<span class='ak-tag'>TCP</span><span class='nai-mono'>".nai_e(($w["listen_addr"]??"").":".($w["listen_port"]??""))."</span>";
        }
        $v="<span class='nai-wc'>$where</span>";
        if($m["admin"]){
            // Le segment inactif ouvre la confirmation existante (articaweb-unix / -off).
            $toUnix=$unix?"":" OnClick=\"Loadjs('$page?articaweb-unix=$id')\"";
            $toTcp=$unix?" OnClick=\"Loadjs('$page?articaweb-unix-off=$id')\"":"";
            $v.="<span class='nai-seg' role='group' aria-label=\"{nai_web_console}\">"
                ."<button type='button' class='".($unix?"nai-seg-on":"")."' aria-pressed='".($unix?"true":"false")."'$toUnix>Unix</button>"
                ."<button type='button' class='".($unix?"":"nai-seg-on")."' aria-pressed='".($unix?"false":"true")."'$toTcp>TCP</button></span>";
            $v.="<button type='button' class='ak-link' OnClick=\"Loadjs('$page?artica-web-restart-js=$id')\"><i class='fa-solid fa-rotate' aria-hidden='true'></i>{nai_restart}</button>";
        }
        $r[]=nai_row("{nai_web_console}",$v);
    }
    return nai_card("fa-solid fa-network-wired","{nai_connection}",implode("",$r));
}

function nai_license_value($m):string{
    $l=$m["license"];
    if(!empty($l["gold_license"])){
        return "<span class='nai-gold'><i class='fa-solid fa-crown' aria-hidden='true'></i>{nai_gold_license}</span>";
    }
    if(empty($l["entreprise_license"])){
        return "<span class='ak-muted'>{nai_no_license}</span>";
    }
    $status=isset($l["Info"]["license_status"])?(string)$l["Info"]["license_status"]:"";
    $map=array(
        "{license_active}"=>array("ok","fa-solid fa-circle-check","{nai_license_active}"),
        "{license_expired}"=>array("err","fa-solid fa-circle-xmark","{nai_license_expired}"),
        "{grace_period}"=>array("warn","fa-solid fa-hourglass-half","{nai_license_grace}"),
        "{license_invalid}"=>array("err","fa-solid fa-triangle-exclamation","{nai_license_invalid}"));
    if(isset($map[$status])){return nai_pill($map[$status][0],$map[$status][1],$map[$status][2]);}
    return nai_pill("neutral","fa-solid fa-certificate",$status==""?"-":nai_e($status));
}

function nai_card_software($m):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $r=array();

    $v="<span class='nai-vers'><span class='ak-muted'>{nai_installed}</span> <span class='nai-mono'>".nai_e($m["version"]==""?"-":$m["version"])."</span>";
    if($m["available"]!=""){
        $v.="<span class='nai-slash' aria-hidden='true'>/</span><span class='ak-muted'>{nai_available}</span> <span class='nai-mono'>".nai_e($m["available"])."</span>";
    }
    $v.="</span>";
    if($m["outdated"]){
        $v.=nai_pill("warn","fa-solid fa-arrow-up","{nai_update_available}");
        if($m["admin"]){
            $v.="<button type='button' class='ak-btn ak-btn-primary nai-btn-sm' OnClick=\"Loadjs('$page?agents-upgrade-js=$id')\">{nai_upgrade_agent}</button>";
        }
    }elseif($m["available"]!=""){
        $v.=nai_pill("ok","fa-solid fa-check","{nai_up_to_date}");
    }
    $r[]=nai_row("{nai_agent_version}",$v);

    if($m["artica"] && $m["artica_version"]!=""){
        $r[]=nai_row("Artica","<span class='nai-mono'>".$m["artica_version"]."</span>");
    }
    if(!empty($m["hacluster"])){
        $n=intval($m["hc_nodes"]);
        if($n<0){$txt="<span class='ak-muted' title=\"{nai_hc_unknown}\">&mdash;</span>";}
        else{$txt="<strong>$n</strong> ".($n==1?"{nai_hc_client_one}":"{nai_hc_client_many}");}
        $r[]=nai_row("HaCluster",$txt,"Loadjs('fw.netagents.hacluster.nodes.php?nodes-js=$id')","HaCluster");
    }
    if(is_array($m["license"])){
        $js=$m["admin"]?"Loadjs('fw.netagents.license.php?id=$id')":"";
        $r[]=nai_row("{nai_license}",nai_license_value($m),$js,"{nai_license}");
    }
    if($m["snapshots"]>=0 || $m["artica"]){
        $n=max(0,$m["snapshots"]);
        $txt=$n==0?"<span class='ak-muted'>{nai_none}</span>":"<strong>$n</strong> ".($n==1?"{nai_snapshot_one}":"{nai_snapshot_many}");
        $js=$m["admin"]?"Loadjs('fw.netagents.snapshots.php?snap-js=$id')":"";
        $r[]=nai_row("{nai_snapshots}",$txt,$js,"{nai_snapshots}");
    }
    return nai_card("fa-solid fa-box","{nai_software}",implode("",$r));
}

function nai_card_maintenance($m):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $tpl=new template_admin();
    $r=array();
    $apt=$m["apt"];
    $check="";
    if($m["admin"]){
        $check="<button type='button' class='ak-btn nai-btn-sm' OnClick=\"Loadjs('$page?apt-get-update-js=$id')\"><i class='fa-solid fa-rotate' aria-hidden='true'></i>{nai_check_updates}</button>";
    }

    if($m["blacklisted"]){
        $r[]=nai_row("{nai_system_upgrade}","<span class='nai-blocked'><i class='fa-solid fa-ban' aria-hidden='true'></i>{nai_updates_blocked}</span>");
    }elseif(is_array($apt)){
        if(!empty($apt["apt_running"])){
            $r[]=nai_row("{nai_system_upgrade}",nai_pill("neutral","fa-solid fa-rotate ak-spin","{nai_running}"));
        }else{
            $n=intval($apt["upgrade_required"]??0);
            if($n>0){
                $v=$m["admin"]
                    ?"<button type='button' class='ak-pill ak-warn nai-pill-btn' OnClick=\"Loadjs('fw.netagents.aptget.upgrade.php?id=$id')\"><i class='fa-solid fa-box-open' aria-hidden='true'></i>$n {nai_upgradable_packages}</button>"
                    :nai_pill("warn","fa-solid fa-box-open","$n {nai_upgradable_packages}");
            }else{
                $v=nai_pill("ok","fa-solid fa-circle-check","{nai_no_upgradable}");
            }
            $r[]=nai_row("{nai_system_upgrade}",$v.$check);
        }
        $last=intval($apt["last_upgrade"]??0);
        if($last>0){
            $res=!empty($apt["last_results"])?nai_pill("ok","fa-solid fa-check","{nai_success}"):nai_pill("err","fa-solid fa-xmark","{nai_with_errors}");
            $r[]=nai_row("{nai_last_upgrade}",$tpl->time_to_date($last,true).$res,
                "Loadjs('fw.netagents.aptget.reports.php?id=$id')","{nai_last_upgrade}");
        }
    }

    // Liste noire : bascule directe (blacklisted-js), grisee si heritee d'un groupe.
    if($m["bl_group"]!=""){
        $bl=nai_switch(true,"",true,"{nai_blacklisted}")."<span class='ak-muted'>{nai_inherited_group} <strong>".nai_e($m["bl_group"])."</strong></span>";
    }else{
        $bl=nai_switch($m["blacklisted"],"Loadjs('$page?blacklisted-js=$id')",!$m["admin"] || !meta_provider_or_sysadmin(),"{nai_blacklisted}");
    }
    $r[]=nai_row("{nai_blacklisted}",$bl);

    // Gere : meme POST que la fenetre fw.netagents.unmanaged.php.
    $next=$m["unmanaged"]?1:0;
    $r[]=nai_row("{nai_managed}",nai_switch(!$m["unmanaged"],"NaiManaged($id,$next)",!$m["admin"],"{nai_managed}"));
    return nai_card("fa-solid fa-screwdriver-wrench","{nai_maintenance}",implode("",$r));
}

function nai_card_certificate($m):string{
    $tpl=new template_admin();
    $c=$m["cert"];
    if(!is_array($c)){
        return nai_card("fa-solid fa-certificate","{nai_certificate}","<div class='nai-empty'>{nai_no_cert}</div>");
    }
    // Politique fixe de netagents/certificate.go : 90 jours, renouvele a J-15.
    $validity=90;
    $renew=15;
    $days=$c["days"];
    if($c["expired"] || $days<=0){
        $kind="err";$caption="{nai_cert_expired}";$color="#ba1a1a";$days=max(0,$days);
    }elseif($days<=$renew){
        $kind="warn";$caption="{nai_cert_renewal}";$color="#865300";
    }else{
        $kind="ok";$caption="{nai_cert_healthy}";$color="#006a62";
    }
    $max=max($validity,$days);
    $donut="<div class='nai-donut nai-donut-$kind'>"
        ."<span class='nai-peity' data-color='$color'>$days/$max</span>"
        ."<span class='nai-donut-n' aria-hidden='true'><strong>$days</strong><small>{nai_days}</small></span>"
        ."<span class='sr-only'>$days {nai_days} / $max</span>"
        ."</div><div class='nai-donut-cap nai-cap-$kind'>$caption</div>";

    $r=array();
    $r[]=nai_row("{nai_issuer}",nai_e($c["issuer"]==""?"-":$c["issuer"]));
    $r[]=nai_row("{nai_not_before}",nai_date($c["not_before"]));
    $r[]=nai_row("{nai_not_after}",nai_date($c["not_after"]));
    if(count($c["dns"])>0){
        $chips=array();
        foreach($c["dns"] as $d){$chips[]="<span class='ak-tag nai-mono'>".nai_e($d)."</span>";}
        $r[]=nai_row("{nai_dns_names}","<span class='ak-chips'>".implode("",$chips)."</span>");
    }
    $body="<div class='nai-cert'><div class='nai-cert-l'>$donut</div><div class='nai-cert-r'>".implode("",$r)."</div></div>";
    return nai_card("fa-solid fa-certificate","{nai_certificate}",$body);
}

function nai_style():string{
    if(isset($GLOBALS["NAI_STYLE"])){return "";}
    $GLOBALS["NAI_STYLE"]=true;
    $soft="rgba(16,185,129,.12)";
    $accent="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$accent=$a["accent_dark"];}
    }
    return "<style>
.nai{color:#111d23;font-size:14px;padding:12px 4px}
.nai *{box-sizing:border-box}
.nai-hero{display:flex;align-items:center;gap:16px;background:#fff;border:1px solid #bcc9c6;border-radius:8px;padding:16px 20px;min-height:96px;border-left-width:4px}
.nai-hero-ok{border-left-color:#26a69a}.nai-hero-err{border-left-color:#ba1a1a}.nai-hero-warn{border-left-color:#865300}.nai-hero-neutral{border-left-color:#bcc9c6}
.nai-hero-ico{width:44px;height:44px;border-radius:8px;background:$soft;color:#00504a;display:inline-flex;align-items:center;justify-content:center;font-size:19px;flex:0 0 auto}
.nai-hero-id{flex:1 1 auto;min-width:0}
.nai-hero-l1{display:flex;align-items:center;flex-wrap:wrap;gap:8px 12px}
.nai-host{font-size:22px;font-weight:600;line-height:28px;text-transform:none;word-break:break-all}
.nai-seen{font-size:13px;color:#3d4947}
.nai-hero-l2{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:6px}
.nai-desc{font-size:13px;color:#3d4947;margin-right:4px}
.nai-hero-act{display:flex;align-items:center;gap:12px;flex:0 0 auto}
.nai-vsep{width:1px;height:24px;background:#bcc9c6}
.nai-pill-btn{cursor:pointer}.nai-pill-btn:hover{filter:brightness(.95)}
.nai-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(440px,1fr));gap:16px;margin-top:16px}
.nai-card{background:#fff;border:1px solid #bcc9c6;border-radius:8px;padding:16px 20px;margin:0;min-width:0}
.nai-card-h{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:600;color:#111d23;margin:0 0 4px;padding-bottom:12px;border-bottom:1px solid #bcc9c6;text-transform:none}
.nai-card-h svg,.nai-card-h i{color:#3d4947}
.nai-row{display:flex;align-items:center;gap:12px;padding:10px 8px;margin:0 -8px;border-top:1px solid rgba(188,201,198,.35);border-radius:6px}
.nai-card-h+.nai-row{border-top:0}
.nai-lbl{flex:0 0 140px;font-size:13px;font-weight:600;color:#3d4947}
.nai-val{flex:1 1 auto;min-width:0;display:flex;align-items:center;flex-wrap:wrap;gap:8px;font-size:14px}
.nai-row-go{cursor:pointer}
.nai-row-go:hover{background:#f3f8fa}
.nai-chev{color:#6d7a77;font-size:12px;flex:0 0 auto}
.nai-row-go:focus-visible,.nai-btn:focus-visible,.nai-ibtn:focus-visible,.nai-chip:focus-visible,.nai-seg button:focus-visible,.nai-pill-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}
.nai-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;word-break:break-all}
.nai-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;padding:3px 10px;border-radius:4px;background:#e3f0f8;color:#111d23;border:1px solid transparent;cursor:pointer}
.nai-chip:hover{border-color:#bcc9c6}
.nai-chip svg,.nai-chip i{color:#3d4947;font-size:11px}
.nai-wc{display:inline-flex;align-items:center;gap:8px;min-width:0}
.nai-seg{display:inline-flex;border:1px solid #bcc9c6;border-radius:6px;padding:2px;background:#f4f6f9}
.nai-seg button{border:0;background:transparent;font-size:13px;padding:2px 10px;border-radius:4px;color:#3d4947;cursor:pointer}
.nai-seg button.nai-seg-on{background:#fff;color:#111d23;font-weight:700;box-shadow:0 1px 2px rgba(16,24,40,.12);cursor:default}
.nai-vers{display:inline-flex;align-items:center;flex-wrap:wrap;gap:4px}
.nai-slash{color:#bcc9c6;margin:0 6px}
.nai-gold{display:inline-flex;align-items:center;gap:6px;color:#865300;font-weight:700}
.nai-blocked{display:inline-flex;align-items:center;gap:8px;color:#3d4947}
.nai-btn-sm{height:30px;padding:0 10px;font-size:13px}
.nai-btn-danger{border-color:#ba1a1a;color:#ba1a1a}
.nai-btn-danger:hover{background:#ffdad6}
.nai-ibtn{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid #bcc9c6;background:#fff;color:#3d4947;cursor:pointer}
.nai-ibtn:hover{background:#f3f8fa;color:#111d23}
.nai-ibtn-sm{width:28px;height:28px;border-color:transparent;font-size:12px}
.nai-ibtn-sm:hover{border-color:#bcc9c6}
.nai-sw-wrap{display:inline-flex;align-items:center;gap:10px}
.nai-sw-word{font-size:13px;font-weight:600;color:#3d4947}
.nai-cert{display:flex;gap:20px;align-items:flex-start}
.nai-cert-l{flex:0 0 112px;text-align:center;padding-top:10px}
.nai-cert-r{flex:1 1 auto;min-width:0}
.nai-cert-r .nai-lbl{flex-basis:110px}
.nai-cert-r .nai-row:first-child{border-top:0}
.nai-donut{position:relative;width:72px;height:72px;margin:0 auto}
.nai-donut .peity{display:block}
.nai-donut-n{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1}
.nai-donut-n strong{font-size:19px;color:#111d23}
.nai-donut-n small{font-size:13px;color:#3d4947;margin-top:2px}
.nai-donut-cap{font-size:13px;font-weight:600;margin-top:8px;line-height:17px}
.nai-cap-ok{color:#00504a}.nai-cap-warn{color:#663e00}.nai-cap-err{color:#93000a}
.nai-empty{padding:20px 0;color:#6d7a77}
@keyframes nai-spin{to{transform:rotate(360deg)}}
@media (prefers-reduced-motion:reduce){.nai-spin{animation:none}}
@media (max-width:1200px){.nai-grid{grid-template-columns:1fr}}
</style>";
}

function nai_js($m,$reloadUrl):string{
    $id=$m["id"];
    return "<script>
(function(){
 window.NaiKey=function(e,el){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); el.click(); } };
 window.NaiReload=function(){ LoadAjaxSilent('agent-info-status-$id','$reloadUrl'); };
 window.NaiManaged=function(id,val){
   $.post('fw.netagents.unmanaged.php',{managed:val,id:id}).done(function(r){
     r=String(r||'');
     if(r.indexOf('jserror:')===0){ swal({title:'Oops...',text:'<strong>'+r.substring(8)+'</strong>',html:true,type:'error'}); }
     NaiReload();
   });
 };
 function draw(){ if(!window.jQuery||!jQuery.fn.peity){return;} jQuery('#agent-info-status-$id .nai-peity').each(function(){ var c=jQuery(this).attr('data-color')||'#006a62'; jQuery(this).peity('donut',{fill:[c,'#e3e8ec'],radius:36,innerRadius:29}); }); }
 if(window.jQuery && !jQuery.fn.peity){ jQuery.getScript('js/jquery.peity.min.js',draw); } else { draw(); }
})();
</script>";
}

function agent_info_status_popup():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-info-status-popup"]);
    // Le drapeau Artica est recalcule ici : les confirmations articaweb-unix
    // rechargent ce fragment sans &artica=yes.
    $artica=isset($_GET["artica"]) || AgentArtica($id);
    $m=nai_model($id,$artica);
    if($m["error"]!=""){
        echo $tpl->div_error($m["error"]);
        return true;
    }
    $reloadUrl="$page?agent-info-status-popup=$id".($artica?"&artica=yes":"");
    $html=array();
    $html[]="<div class='nai'>";
    $html[]=nai_hero($m);
    $html[]="<div class='nai-grid'>";
    $html[]=nai_card_connection($m);
    $html[]=nai_card_software($m);
    $html[]=nai_card_maintenance($m);
    $html[]=nai_card_certificate($m);
    $html[]="</div></div>";
    echo nai_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).nai_js($m,$reloadUrl);
    return true;
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


function agent_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    return $tpl->js_dialog2("{add_agent}","$page?agent-add-popup=yes&function=$function",550);
}

function agent_add_popup():bool{
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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
    if(!nai_is_admin()){echo (new template_admin())->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
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
    $f[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
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
    $f[]="</tbody></table></div></div></div>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n", $f));
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
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    return $tpl->js_dialog3("{nagsel_title}","$page?group-select-popup=yes&function=$function",640);
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
    header("content-type: application/x-javascript");
    if(!nai_is_admin()){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $id = intval($_GET["agent-enroll"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/enroll/$id"));
    if(!is_object($json)){return $tpl->js_error("{enrollment_failed}: Artica REST API");}
    if(isset($json->Status) && $json->Status){
        $tpl->js_ok("{enrollment_success}","if(typeof NagtLiveReload==='function'){setTimeout(NagtLiveReload,2000);}");
        return admin_tracks("Enroll network agent #$id");
    }
    return $tpl->js_error("{enrollment_failed}: ".(isset($json->Error) ? $json->Error : "{unknown_error}"));
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
// ── Fenêtre « Update Agent » (maquette stitch_artica_meta_upg) ──────────────
// Deux méthodes (API, SSH) dans la même fenêtre : mêmes widgets, une seule
// fonction par widget, un modèle calculé une fois par upg_model().
function agents_upgrade_api():bool{
    echo upg_view("api");
    return true;
}
function agents_upgrade_ssh():bool{
    echo upg_view("ssh");
    return true;
}
function agents_upgrade_results():bool{
    echo upg_results("api");
    return true;
}
function agents_upgrade_ssh_results():bool{
    echo upg_results("ssh");
    return true;
}

function upg_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}
// Contexte commun aux deux vues et aux deux tableaux de suivi.
function upg_ctx():array{
    $gid=isset($_GET["gpid"])?intval($_GET["gpid"]):0;
    $agentver="";
    $qs="&gpid=$gid";
    if(isset($_GET["agentver"]) && strlen((string)$_GET["agentver"])>0){
        $agentver=(string)$_GET["agentver"];
        $qs.="&agentver=".urlencode($agentver);
    }
    return array("gid"=>$gid,"agentver"=>$agentver,"qs"=>$qs,
        "target"=>(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion"));
}
function upg_model(string $method):array{
    $c=upg_ctx();
    $key=$method=="ssh"?"agents-upgrade-ssh":"agents-upgrade-api";
    $c["method"]=$method;
    $c["pre"]=isset($_GET[$key])?intval($_GET[$key]):0;
    // API : seuls les agents en ligne et plus anciens que la cible peuvent recevoir le binaire.
    // SSH : tous les agents actifs, même hors ligne (c'est la voie de secours).
    $url="/netagents/list?enabled=1".$c["qs"];
    if($method=="api"){$url="/netagents/list?status=online&enabled=1".$c["qs"];}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($url),true);
    $agents=array();
    $list=array();
    if(is_array($json) && isset($json["agents"]) && is_array($json["agents"])){$list=$json["agents"];}
    foreach($list as $a){
        if(!is_array($a)){continue;}
        $ver=(string)($a["version"]??"");
        $older=version_compare($c["target"],$ver,'>');
        if($method=="api"){
            if(!$older){continue;}
            if(($a["status"]??"")!="online"){continue;}
        }
        $checked=intval($a["id"]??0)==$c["pre"];
        if($c["gid"]>0 || strlen($c["agentver"])>2){$checked=true;}
        $a["_older"]=$older;
        $a["_checked"]=$checked;
        $agents[]=$a;
    }
    $c["agents"]=$agents;
    return $c;
}

function upg_view(string $method):string{
    $tpl=new template_admin();
    $m=upg_model($method);
    $f=array();
    $f[]="<div class='upg-root' id='upg-root-$method' data-method='$method'>";
    $f[]=upg_head($m);
    $f[]=upg_agents_card($m);
    if($method=="ssh"){$f[]=upg_ssh_credentials();}
    $f[]=upg_action_bar($m);
    $f[]=upg_status_section($m);
    $f[]="</div>";
    return upg_style().$tpl->_ENGINE_parse_body(implode("\n",$f)).upg_js($m);
}

// 1. Bandeau : version cible + choix de la méthode.
function upg_head(array $m):string{
    return "<div class='upg-head'>"
        ."<div class='upg-target'><span class='upg-target-ico' aria-hidden='true'><i class='fas fa-arrow-up-from-bracket'></i></span>"
        ."<div><div class='upg-kicker'>{upg_target_version}</div>"
        ."<div class='upg-target-v ak-mono'>v".upg_h($m["target"])."</div></div></div>"
        .upg_method_switch($m)
        ."</div>";
}
function upg_method_switch(array $m):string{
    $page=CurrentPageName();
    $qs=$m["qs"];
    $pre=intval($m["pre"]);
    $opts=array(
        "api"=>array("fas fa-network-wired","{use_API}","agents-upgrade-api=$pre"),
        "ssh"=>array("fas fa-terminal","{use_ssh}","agents-upgrade-ssh=$pre"),
    );
    $b=array();
    foreach($opts as $k=>$o){
        $on=$k==$m["method"];
        $js=$on?"":"LoadAjaxSilent('agents_upgrade_popup','$page?{$o[2]}$qs');";
        $b[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='upg-seg".($on?" upg-seg-on":"")."'"
            .($on?" tabindex='0'":" tabindex='-1'")." OnClick=\"$js\"><i class='{$o[0]}' aria-hidden='true'></i> {$o[1]}</button>";
    }
    return "<div class='upg-method'><span class='upg-method-l' id='upg-method-l-{$m["method"]}'>{method}:</span>"
        ."<div class='upg-segs' role='radiogroup' aria-labelledby='upg-method-l-{$m["method"]}'>".implode("",$b)."</div></div>";
}

// 2. Carte de sélection des agents.
function upg_agents_card(array $m):string{
    $n=count($m["agents"]);
    $on=0;
    foreach($m["agents"] as $a){ if($a["_checked"]){$on++;} }
    $rows=array();
    foreach($m["agents"] as $a){$rows[]=upg_agent_row($a,$m);}
    $body=$n==0?upg_agents_empty($m):"<div class='upg-list' id='upg-list'>".implode("",$rows)."</div>";
    $tools="";
    if($n>0){
        $tools="<div class='upg-tools'>"
            ."<button type='button' class='ak-link' OnClick=\"UpgAll(true)\"><i class='fas fa-square-check' aria-hidden='true'></i> {upg_select_all}</button>"
            ."<span class='upg-sep' aria-hidden='true'></span>"
            ."<button type='button' class='ak-link' OnClick=\"UpgAll(false)\"><i class='far fa-square' aria-hidden='true'></i> {upg_unselect_all}</button>"
            ."</div>";
    }
    $pill=$n>0?"<span class='upg-pill ak-mono' aria-live='polite'><span id='upg-count'>$on</span> / $n {upg_selected}</span>":"";
    return "<div class='ak-card'><div class='upg-card-h'><div class='upg-card-t'>$pill</div>$tools</div>$body</div>";
}
function upg_agent_row(array $a,array $m):string{
    $id=intval($a["id"]??0);
    $host=(string)($a["hostname"]??"");
    $chk=$a["_checked"]?" checked":"";
    $cls=$a["_checked"]?" upg-row-on":"";
    return "<label class='upg-row$cls'>"
        ."<input type='checkbox' class='upg-cb' value='$id'$chk OnChange=\"UpgSync()\" aria-label=\"{upg_select} ".upg_h($host)."\">"
        ."<span class='upg-id'><span class='upg-host' title=\"".upg_h($host)."\">".upg_h($host)."</span>".upg_address($a)."</span>"
        .upg_version_chip($a,$m)
        .upg_state($a)
        ."</label>";
}
function upg_address(array $a):string{
    $ip=(string)($a["ip_address"]??"");
    if($ip==""){return "<span class='upg-ip'>—</span>";}
    if(substr($ip,-8)==".invalid"){
        return "<span class='upg-ip upg-ip-rev' title=\"".upg_h($ip)."\"><i class='fas fa-right-left' aria-hidden='true'></i> {nagr_reverse_conn}</span>";
    }
    return "<span class='upg-ip ak-mono'>".upg_h($ip)."</span>";
}
function upg_version_chip(array $a,array $m):string{
    $ver=(string)($a["version"]??"");
    if($ver==""){$ver="?";}
    if(!$a["_older"]){
        return "<span class='upg-ver ak-mono upg-ver-same' title='{upg_up_to_date}'><i class='fas fa-check' aria-hidden='true'></i> v".upg_h($ver)."</span>";
    }
    return "<span class='upg-ver ak-mono'><span class='upg-ver-old'>v".upg_h($ver)."</span>"
        ."<i class='fas fa-arrow-right' aria-hidden='true'></i><span class='upg-ver-new'>v".upg_h($m["target"])."</span></span>";
}
function upg_state(array $a):string{
    $on=($a["status"]??"")=="online";
    return "<span class='upg-state ".($on?"upg-state-ok":"upg-state-off")."'><span class='upg-dot' aria-hidden='true'></span>"
        .($on?"{online}":"{offline}")."</span>";
}
function upg_agents_empty(array $m):string{
    if($m["method"]=="ssh"){
        return "<div class='ak-empty-c upg-empty'><i class='fas fa-circle-info' aria-hidden='true'></i><div>{upg_ssh_none}</div></div>";
    }
    $title=$m["gid"]>0?"{upg_none_group}":"{upg_none_all}";
    return "<div class='ak-empty-c upg-empty'><i class='fas fa-circle-check upg-ok' aria-hidden='true'></i><div>$title</div><div class='upg-empty-s'>{upg_none_offline}</div></div>";
}

// 2b. Identifiants SSH (vue SSH seulement).
function upg_ssh_credentials():string{
    return "<div class='ak-card upg-creds'><div class='upg-card-h'><div class='upg-card-t'><h3>{upg_ssh_credentials}</h3></div></div>"
        ."<div class='upg-creds-g'>"
        ."<label class='upg-fld'><span>{username}</span><input type='text' id='upg-ssh-user' class='form-control' autocomplete='off'></label>"
        ."<label class='upg-fld'><span>{password}</span><input type='password' id='upg-ssh-password' class='form-control' autocomplete='new-password'></label>"
        ."<label class='upg-fld upg-fld-port'><span>{ssh_port}</span><input type='text' id='upg-ssh-port' class='form-control ak-mono' value='22' inputmode='numeric'></label>"
        ."</div></div>";
}

// 3. Barre d'action.
function upg_action_bar(array $m):string{
    $n=count($m["agents"]);
    if($n==0){return "";}
    $on=0;
    foreach($m["agents"] as $a){ if($a["_checked"]){$on++;} }
    $dis=$on==0?" disabled":"";
    $warn=$on==0?"":" style='display:none'";
    return "<div class='upg-bar'>"
        ."<div class='upg-warn' id='upg-warn' role='status'$warn><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> <span id='upg-warn-t'>{upg_select_one}</span></div>"
        ."<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' id='upg-push' OnClick=\"UpgPush()\"$dis><i class='fas fa-upload' aria-hidden='true'></i><span>{push_update} (<span id='upg-push-n'>$on</span>)</span></button>"
        ."</div><div id='upg-push-msg' class='upg-push-msg'></div>";
}

// 4. Suivi des mises à jour, rafraîchi par ArticaRefresh.
function upg_status_section(array $m):string{
    return "<div class='upg-status'><div class='upg-status-h'><h3>{status}</h3>"
        ."<span class='upg-auto'><i class='fas fa-rotate' aria-hidden='true'></i> {upg_auto_refresh}</span></div>"
        ."<div id='upg-status-{$m["method"]}' aria-live='polite'></div></div>";
}
function upg_scope_ids(int $gid):array{
    // La liste du démon couvre tout le parc : on la restreint au groupe ouvert.
    $ids=array();
    if($gid<1){return $ids;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list?gpid=$gid"),true);
    if(!is_array($json) || !isset($json["agents"]) || !is_array($json["agents"])){return $ids;}
    foreach($json["agents"] as $a){ if(is_array($a)){$ids[intval($a["id"]??0)]=true;} }
    return $ids;
}
function upg_results(string $method):string{
    $tpl=new template_admin();
    $c=upg_ctx();
    $url=$method=="ssh"?"/netagents/upgrade-ssh/list":"/netagents/upgrade/list";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($url),true);
    if(!is_array($json)){
        return upg_style().$tpl->div_warning("{upg_status_unavailable}");
    }
    if(isset($json["Status"]) && !$json["Status"] && strlen((string)($json["Error"]??""))>1){
        return upg_style().$tpl->div_error(upg_h($json["Error"]));
    }
    $ops=$json;
    if($method=="ssh"){$ops=isset($json["single_upgrades"]) && is_array($json["single_upgrades"])?$json["single_upgrades"]:array();}
    $scope=upg_scope_ids($c["gid"]);
    $rows=array();
    foreach($ops as $op){
        if(!is_array($op)){continue;}
        if($c["gid"]>0 && !isset($scope[intval($op["agent_id"]??0)])){continue;}
        $op["_ts"]=$tpl->GoToTimestamp((string)($op["started_at"]??""));
        $rows[]=$op;
    }
    usort($rows,function($a,$b){ return $b["_ts"]-$a["_ts"]; });
    if(count($rows)==0){
        return upg_style().$tpl->_ENGINE_parse_body("<div class='ak-card'><div class='ak-empty-c upg-empty upg-empty-sm'>{upg_no_update}</div></div>");
    }
    $f=array();
    foreach($rows as $op){$f[]=upg_status_row($op,$tpl);}
    return upg_style().$tpl->_ENGINE_parse_body("<div class='ak-card upg-ops'>".implode("",$f)."</div>");
}
function upg_status_row(array $op,$tpl):string{
    $state=(string)($op["state"]??"");
    $p=intval($op["progress"]??0);
    if($p<0){$p=0;} if($p>100){$p=100;}
    $err=(string)($op["error"]??"");
    $msg=(string)($op["message"]??"");
    $kind=upg_state_kind($state);
    $when=intval($op["_ts"]);
    $done=(string)($op["completed_at"]??"");
    $doneTxt="";
    if($kind!="run" && $kind!="wait" && $done!="" && strpos($done,"0001-01-01")!==0){
        $when=$tpl->GoToTimestamp($done);
        $doneTxt=$kind=="ok"?"{completed} ":"";
    }
    $whenTxt=$when>0?$tpl->time_to_date($when,true):"";
    $line="<div class='upg-op-msg'>".upg_h($msg)."</div>";
    if(strlen($err)>0){$line="<div class='upg-op-msg upg-op-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> ".upg_h($err)."</div>";}
    return "<div class='upg-op upg-op-$kind'>"
        ."<div class='upg-op-h'><span class='upg-op-host ak-mono'>".upg_h($op["hostname"]??"")."</span>".upg_state_badge($state)
        ."<span class='upg-op-when ak-mono'>$doneTxt".upg_h($whenTxt)."</span></div>"
        ."<div class='upg-op-bar'><div class='upg-track' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='$p'>"
        ."<div class='upg-fill' style='width:$p%'></div></div><span class='upg-op-pct ak-mono'>$p%</span></div>"
        .$line."</div>";
}
// API : pending, queued, loading, sending, completed, failed.
// SSH : pending, connecting … enabling_artica (étapes), completed, failed.
function upg_state_kind(string $s):string{
    if($s=="completed"){return "ok";}
    if($s=="failed"){return "bad";}
    if($s=="pending" || $s=="queued"){return "wait";}
    return "run";
}
function upg_state_badge(string $s):string{
    $map=array(
        "pending"=>array("fas fa-clock","{pending}"),
        "queued"=>array("fas fa-hourglass-half","{upg_queued}"),
        "loading"=>array("fas fa-spinner fa-spin","{upg_loading}"),
        "sending"=>array("fas fa-paper-plane","{upg_sending}"),
        "completed"=>array("fas fa-check","{completed}"),
        "failed"=>array("fas fa-xmark","{failed}"),
    );
    $o=isset($map[$s])?$map[$s]:array("fas fa-spinner fa-spin","{upg_in_progress}");
    $kind=upg_state_kind($s);
    $tones=array("wait"=>"ak-off","run"=>"ak-info","ok"=>"ak-ok","bad"=>"ak-err");
    $tone=isset($tones[$kind])?$tones[$kind]:"ak-off";
    return "<span class='ak-pill $tone upg-badge'><i class='{$o[0]}' aria-hidden='true'></i> {$o[1]}</span>";
}

function upg_style():string{
    if(isset($GLOBALS["UPG_STYLE"])){return "";}
    $GLOBALS["UPG_STYLE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.upg-root{--upg-a:$accent;--upg-ad:$dark;--upg-as:$soft;color:#111827;font-size:14px}
.upg-root *,.upg-ops *{box-sizing:border-box}
.upg-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:-5px -15px 16px;padding:4px 15px 16px;border-bottom:1px solid #e5e7eb}
.upg-target{display:flex;align-items:center;gap:12px;min-width:0}
.upg-target-ico{width:36px;height:36px;border-radius:8px;background:var(--upg-as);color:var(--upg-ad);display:flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 36px}
.upg-kicker{font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
.upg-target-v{font-size:16px;font-weight:600}
.upg-method{display:flex;align-items:center;gap:8px}
.upg-method-l{font-size:13px;color:#6b7280}
.upg-segs{display:inline-flex;border:1px solid #e5e7eb;border-radius:6px;padding:2px;background:#f9fafb}
.upg-seg{border:0;background:transparent;color:#374151;font-size:13px;height:30px;padding:0 12px;border-radius:4px;cursor:pointer;white-space:nowrap}
.upg-seg:hover{background:#fff}
.upg-seg-on,.upg-seg-on:hover{background:var(--upg-ad);color:#fff;cursor:default}
.upg-seg:focus-visible,.upg-push:focus-visible,.upg-cb:focus-visible{outline:2px solid var(--upg-ad);outline-offset:2px}
.upg-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:48px;padding:8px 16px;border-bottom:1px solid #e5e7eb}
.upg-card-t{display:flex;align-items:center;gap:10px}
.upg-status-h h3{margin:0;font-size:14px;font-weight:600;color:#111827}
.upg-pill{font-size:12px;color:#374151;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;padding:2px 10px;white-space:nowrap}
.upg-tools{display:flex;align-items:center;gap:10px}
.upg-sep{width:1px;height:16px;background:#e5e7eb}
.upg-list{max-height:280px;overflow-y:auto}
.upg-row{display:flex;align-items:center;gap:12px;min-height:52px;padding:6px 16px 6px 13px;margin:0;border-left:3px solid transparent;border-bottom:1px solid #f3f4f6;font-weight:400;cursor:pointer}
.upg-row:last-child{border-bottom:0}
.upg-row:hover{background:#f9fafb}
.upg-row-on{border-left-color:var(--upg-ad)}
.upg-cb{width:18px;height:18px;margin:0!important;flex:0 0 18px;accent-color:var(--upg-ad);cursor:pointer}
.upg-id{display:flex;flex-direction:column;min-width:0;flex:1 1 auto}
.upg-host{font-size:14px;font-weight:500;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.upg-ip{font-size:12px;color:#6b7280}
.upg-ver{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;font-size:12px;border:1px solid #e5e7eb;border-radius:4px;padding:2px 8px;background:#f9fafb;white-space:nowrap}
.upg-ver-old{color:#6b7280}
.upg-ver i{color:#9ca3af;font-size:10px}
.upg-ver-new{color:var(--upg-ad);font-weight:600}
.upg-ver-same{color:#047857}
.upg-ver-same i{color:#047857}
.upg-state{display:inline-flex;align-items:center;gap:6px;flex:0 0 70px;font-size:13px;color:#374151}
.upg-dot{width:8px;height:8px;border-radius:50%;background:#059669}
.upg-state-off .upg-dot{background:#9ca3af}
.upg-state-off{color:#6b7280}
.upg-empty{justify-content:center;min-height:120px;font-size:14px}
.upg-empty>.upg-ok{color:#059669}
.upg-empty-s{font-size:13px;color:#6b7280}
.upg-empty-sm{min-height:56px;padding:16px;font-size:13px;color:#6b7280}
.upg-creds{margin-top:16px}
.upg-creds-g{display:flex;gap:12px;padding:12px 16px 16px}
.upg-fld{display:flex;flex-direction:column;gap:4px;flex:1 1 0;min-width:0;margin:0;font-weight:400}
.upg-fld>span{font-size:13px;color:#374151;font-weight:500}
.upg-fld-port{flex:0 0 96px}
.upg-bar{display:flex;align-items:center;justify-content:flex-end;gap:16px;margin:16px 0 8px}
.upg-warn{font-size:13px;color:#b45309}
.upg-warn br,.upg-empty br{display:none}
.upg-push-msg .alert{margin:8px 0 0;padding:8px 12px;font-size:13px}
.upg-status{margin-top:24px}
.upg-status-h{display:flex;align-items:center;gap:12px;margin-bottom:8px}
.upg-auto{font-size:12px;color:#6b7280}
.upg-ops{max-height:380px;overflow-y:auto}
.upg-op{padding:12px 16px;border-bottom:1px solid #f3f4f6}
.upg-op:last-child{border-bottom:0}
.upg-op-h{display:flex;align-items:center;gap:8px;min-width:0}
.upg-op-host{font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.upg-op-when{margin-left:auto;font-size:12px;color:#6b7280;white-space:nowrap}
.upg-op-ok .upg-op-when{color:#047857}
.upg-badge{flex:0 0 auto;height:22px;padding:0 9px}
.upg-op-bar{display:flex;align-items:center;gap:12px;margin:8px 0 6px}
.upg-track{flex:1 1 auto;height:6px;border-radius:3px;background:#e5e7eb;overflow:hidden}
.upg-fill{height:100%;border-radius:3px;background:#2563eb;transition:width .3s ease-out}
.upg-op-wait .upg-fill{background:#9ca3af}
.upg-op-ok .upg-fill{background:#059669}
.upg-op-bad .upg-fill{background:#dc2626}
.upg-op-pct{flex:0 0 40px;text-align:right;font-size:12px;color:#374151}
.upg-op-ok .upg-op-pct{color:#047857}
.upg-op-bad .upg-op-pct{color:#b91c1c}
.upg-op-msg{font-size:12px;color:#6b7280;overflow-wrap:anywhere}
.upg-op-err{color:#b91c1c}
@media (prefers-reduced-motion:reduce){.upg-fill{transition:none}.upg-root .fa-spin,.upg-ops .fa-spin{animation:none}}
@media (max-width:620px){.upg-row{flex-wrap:wrap}.upg-ver{order:3;margin-left:30px}.upg-head{flex-wrap:wrap}}
</style>";
}

function upg_js(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $method=$m["method"];
    $qs=$m["qs"];
    $res=$method=="ssh"?"agent-upgrade-sshresult=yes":"agent-upgrade-result=yes";
    $refresh=$tpl->RefreshInterval_js("upg-status-$method",$page,"$res$qs");
    $post=$method=="ssh"
        ?"{'agent-upgrade-ssh':1,'agent_ids':ids.join(','),'username':$('#upg-ssh-user').val(),'password':$('#upg-ssh-password').val(),'port':$('#upg-ssh-port').val()}"
        :"{'agent-upgrade-perform':1,'agent_ids':ids.join(',')}";
    $sshCheck="";
    if($method=="ssh"){
        $need=trim(str_replace(array("[br]","<br>","\n","\r"),"",$tpl->javascript_parse_text("{upg_ssh_user_required}")));
        $need=str_replace(array("\\","'"),array("\\\\","\\'"),$need);
        $sshCheck="if(!$.trim($('#upg-ssh-user').val())){ $('#upg-warn-t').text('$need'); $('#upg-warn').show(); $('#upg-ssh-user').focus(); return; }";
    }
    return "<script>
function UpgSync(){
  var boxes=$('#upg-list .upg-cb'); var n=boxes.filter(':checked').length;
  boxes.each(function(){ $(this).closest('.upg-row').toggleClass('upg-row-on',this.checked); });
  $('#upg-count').text(n); $('#upg-push-n').text(n);
  var b=document.getElementById('upg-push'); if(b){ b.disabled=(n==0); }
  if(n==0){ $('#upg-warn-t').text($('#upg-warn').data('t')); $('#upg-warn').show(); } else { $('#upg-warn').hide(); }
}
function UpgAll(v){ $('#upg-list .upg-cb').prop('checked',v); UpgSync(); }
function UpgPush(){
  var ids=[]; $('#upg-list .upg-cb:checked').each(function(){ ids.push($(this).val()); });
  if(ids.length==0){ UpgSync(); return; }
  $sshCheck
  var b=document.getElementById('upg-push'); if(b){ b.disabled=true; }
  $('#upg-push-msg').html('');
  $.post('$page',$post,function(r){
    $('#upg-push-msg').html(r);
    if(b){ b.disabled=false; }
    LoadAjaxSilent('upg-status-$method','$page?$res$qs');
  });
}
(function(){ var w=$('#upg-warn'); w.data('t',$('#upg-warn-t').text()); })();
$refresh
</script>";
}
// Droit serveur : le bouton ne s'affiche pas sans droit, mais l'aiguillage repondait a toute session.
function upg_can_push():bool{
    $users=new usersMenus();
    return meta_can_push($users);   // infogerant ou tenant vers SES noeuds (le daemon filtre)
}
function upg_push_error(int $agentId,$json):string{
    $err="Unknown error";
    if(is_object($json) && property_exists($json,"Error") && strlen((string)$json->Error)>0){
        $err=(string)$json->Error;
    }
    return "<div class='alert alert-danger' role='alert'>".htmlspecialchars("#$agentId: $err",ENT_QUOTES,"UTF-8")."</div>";
}
// Les deux envois ne renvoient que les erreurs : le suivi affiche le reste.
function agents_upgrade_ssh_perform():bool{
    $tpl = new template_admin();
    if(!upg_can_push()){echo "<div class='alert alert-danger' role='alert'>".$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}")."</div>";return false;}
    $tpl->CLEAN_POST();
    $agent_ids=explode(",",(string)($_POST['agent_ids']??""));
    $username=(string)($_POST['username']??"");
    $password=(string)($_POST['password']??"");
    $port=intval($_POST['port']??22);
    if($port<1 || $port>65535){$port=22;}
    $logs=array();
    foreach ($agent_ids as $agent_id) {
        $agent_id=intval($agent_id);
        if($agent_id<1){continue;}
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/upgrade-ssh/single/$agent_id",
        array("username"=>$username,"password"=>$password,"ssh_port"=>$port)));
        if(!is_object($json) || !property_exists($json,"Status") || !$json->Status){
            echo upg_push_error($agent_id,$json);
            continue;
        }
        $ArticaNetAgents=new ArticaNetAgents($agent_id);
        $logs[]=$ArticaNetAgents->GetAgentHostname();
    }
    if(count($logs)==0){return false;}
    return admin_tracks("Upgrade Debian agents via SSH on ".implode(", ",$logs));
}

function agents_upgrade_perform():bool{
    $tpl = new template_admin();
    if(!upg_can_push()){echo "<div class='alert alert-danger' role='alert'>".$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}")."</div>";return false;}
    $tpl->CLEAN_POST();
    $agentIds = isset($_POST["agent_ids"]) ? trim($_POST["agent_ids"]) : "";
    $tb=explode(",",$agentIds);
    $sent=array();
    foreach ($tb as $agentId) {
        $agentId=intval($agentId);
        if($agentId<1){continue;}
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/upgrade/$agentId",array()));
        if(!is_object($json) || !property_exists($json,"upgrade_id")){
            echo upg_push_error($agentId,$json);
            continue;
        }
        $sent[]=$agentId;
    }
    if(count($sent)==0){return false;}
    return admin_tracks("Send order to update Debian agent to latest version to ids:".implode(",",$sent));
}
