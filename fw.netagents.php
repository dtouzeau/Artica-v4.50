<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$tpl_deny=new template_admin();
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");

// Selecteur du tenant actif (spec 2026-11-13 5.7) : toute session Meta. L'ecriture de
// META_TENANT est controlee COTE SERVEUR par natn_select_tenant() contre META_SCOPE.
if(isset($_POST["meta-tenant-select"])){flt_tenant_select();exit;}
if(isset($_GET["meta-tenant-js"])){flt_tenant_js();exit;}
if(isset($_GET["meta-tenant-popup"])){flt_tenant_popup();exit;}

if(isset($_GET["agents-upgrade-list-js"])){agents_upgrade_list_js();exit;}
if(isset($_GET["agents-upgrade-list-popup"])){agents_upgrade_list_popup();exit;}
if(isset($_GET["agents-upgrade-list-apt-status"])){agents_upgrade_list_apt_status();exit;}

if(isset($_GET["card-repo"])){card_repository();exit;}
if(isset($_GET["card-versions"])){card_agent_versions();exit;}
if(isset($_GET["card-debian"])){card_debian_versions();exit;}
if(isset($_GET["card-updates"])){card_update_tasks();exit;}
if(isset($_GET["fleet-table"])){fleet_table();exit;}
if(isset($_GET["server-certificate-js"])){flt_server_certificate_js();exit;}
if(isset($_GET["server-certificate-popup-body"])){flt_server_certificate_popup(false);exit;}
if(isset($_GET["server-certificate-popup"])){flt_server_certificate_popup();exit;}
if(isset($_GET["server-certificate-view"])){flt_server_certificate_view();exit;}
if(isset($_GET["server-certificate-renew-js"])){flt_server_certificate_renew_js();exit;}
if(isset($_GET["top-meta-status"])){top_meta_status();exit;}
if(isset($_GET["agent-add-js"])){agent_add_js();exit;}
if(isset($_GET["agent-add-popup"])){agent_add_popup();exit;}
if(isset($_GET["agent-ping"])){agent_ping();exit;}
if(isset($_GET["agent-token-js"])){agent_token_js();exit;}
// Ecritures : la garde est cote serveur, masquer un bouton n'en est pas une.
if(isset($_POST["netagent-hostname"])){if(!flt_is_admin()){echo $tpl_deny->post_error("{ERROR_NO_PRIVS}");exit;}agent_add_save();exit;}
if(isset($_GET["agent-delete"])){if(!flt_is_admin() && !meta_can_view()){exit;}agent_delete();exit;}
if(isset($_POST["netagent-save-token"])){if(!flt_is_admin()){echo $tpl_deny->post_error("{ERROR_NO_PRIVS}");exit;}agent_token_save();exit;}
if(isset($_GET["checkall"])){if(!flt_is_admin()){exit;}check_all_agents();exit;}
if(isset($_GET["agent-enroll"])){if(!flt_is_admin()){exit;}agent_enroll();exit;}
if(isset($_GET["start"])){start();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{network_agents}",
        "fas fa-network-wired","{network_agents_explain}","$page?start=yes",
        "netagents","progress-netagents",false,"table-netagents");

    $html=$tpl->page_header("{artica_meta}",
        ico_ameta,"{network_agents_explain}","$page?start=yes",
        "meta-server","progress-netagents",false,"table-netagents");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{network_agents}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $stats=flt_api("/netagents/stats");
    $online=intval($stats["online"] ?? 0);

    $html=array();
    $html[]="<div class='flt-wrap'>";
    $html[]=natn_no_tenant_banner();
    $html[]="<div class='flt-row' id='flt-kpis'>".flt_kpis_html()."</div>";
    $html[]="<div class='flt-row'>";
    // Tenant : ni partition du depot, ni tableau de bord des versions (global-only).
    $tenantView=meta_tenant_session();
    $cards=$tenantView?array("flt-card-versions"):array("flt-card-repo","flt-card-versions","flt-card-debian","flt-card-updates");
    foreach($cards as $id){
        $html[]="<div class='flt-card' id='$id'></div>";
    }
    $html[]="</div>";
    $html[]="<div class='flt-tcard'>";
    $html[]="<div class='flt-th'>";
    $html[]="<div><h3>{fleet_overview} <span class='ak-count'>$online {fleet_connected}</span></h3>";
    $html[]="<p>{fleet_overview_explain}</p></div>";
    $html[]="<div class='flt-tools'>";
    $html[]="<div class='flt-search'><span class='flt-sico'><i class='fas fa-magnifying-glass'></i></span>";
    $html[]="<input type='text' id='flt-q' placeholder=\"{fleet_filter}\" OnKeyUp='FltFilter()'></div>";
    $html[]="<button type='button' class='flt-btn' OnClick=\"LoadAjax('MainContent','fw.netagents.list.php')\">{fleet_view_all} <i class='fas fa-chevron-right'></i></button>";
    $html[]="</div></div>";
    $html[]="<div id='flt-table'></div>";
    $html[]="</div>";
    $html[]="</div>";

    // 1. Deployer : choix de la methode (jeton, SSH, client mTLS, compte) puis sa fenetre.
    // 2. Rafraichir. 3. Liste « Meta server » : certificat serveur, parametres du service.
    // Les identites mTLS restent dans le menu de gauche ({mtls_clients}).
    $topbuttons=array();
    $provider=meta_provider_or_sysadmin();
    if($provider){$topbuttons[]=array("Loadjs('fw.netagents.deploy.methods.php?js=yes')",ico_plus,"{deploy_agent}","","primary");}
    $topbuttons[]=array("LoadAjax('table-netagents','$page?start=yes')",ico_refresh,"{refresh}");
    if(count(natn_session_tenant_ids())>1){$topbuttons[]=array("Loadjs('$page?meta-tenant-js=yes')","fas fa-building","{meta_active_tenant}");}
    if($provider){
        $topbuttons["SUB_TITLE"]="{nadep_meta_server}";
        $topbuttons["SUB"]=array(
            "{server_certificate}"=>"fas fa-certificate||Loadjs('$page?server-certificate-js=yes')",
            "{service_parameters}"=>"fas fa-sliders||Loadjs('fw.netagents.meta.parameters.php?js=yes')",
            "{meta_tenants}"=>"fas fa-building||LoadAjax('MainContent','fw.netagents.tenants.php')",
        );
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{artica_meta}";
    $TINY_ARRAY["ICO"]=ico_ameta;
    $TINY_ARRAY["EXPL"]="{network_agents_explain}";
    $TINY_ARRAY["URL"]="meta-server";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $script=array();
    $script[]="<script>";
    $script[]="function FltFilter(){";
    $script[]="  clearTimeout(window.__fltTimer);";
    $script[]="  window.__fltTimer=setTimeout(function(){";
    $script[]="    var e=document.getElementById('flt-q');";
    $script[]="    LoadAjaxSilent('flt-table','$page?fleet-table=yes&q='+encodeURIComponent(e?e.value:''));";
    $script[]="  },350);";
    $script[]="}";
    foreach($cards as $card){
        $script[]="LoadAjaxSilent('$card','$page?".substr($card,4)."=yes');";
    }
    $script[]="LoadAjaxSilent('flt-table','$page?fleet-table=yes');";
    $script[]=$tpl->RefreshInterval_js("flt-kpis",$page,"top-meta-status=yes",10);
    $script[]=$tiny;
    $script[]="</script>";

    // La feuille de style ne traverse jamais _ENGINE_parse_body() (une recherche memcached par regle).
    echo flt_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$script);
    return true;
}

// ── Kit flt-* (maquette stitch_fleet_status_supervision_console) ─────────────

function flt_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function flt_style():string{
    $ac=flt_accents();
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    $c=array();
    $c[]=".flt-wrap,.flt-wrap *{box-sizing:border-box}";
    $c[]=".flt-wrap{font-family:Inter,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#1e293b;margin-top:10px}";
    $c[]=".flt-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}";
    $c[]="@media(max-width:1400px){.flt-row{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    $c[]="@media(max-width:760px){.flt-row{grid-template-columns:minmax(0,1fr)}}";
    // Tuiles de mesure
    $c[]=".flt-kpi{position:relative;overflow:hidden;border-radius:12px;padding:20px;border:1px solid #e2e8f0;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.05);display:flex;flex-direction:column;justify-content:space-between;min-height:172px}";
    $c[]=".flt-kpi.ok{background:linear-gradient(135deg,#fff 40%,$as);border-color:$al}";
    $c[]=".flt-kpi.warn{background:linear-gradient(135deg,#fff 40%,#fffbeb);border-color:#fcd34d}";
    $c[]=".flt-kpi.err{background:linear-gradient(135deg,#fff 40%,#fff1f2);border-color:#fda4af}";
    $c[]=".flt-kpi.dark{background:linear-gradient(135deg,#0f172a,#1e293b);border-color:#1e293b;color:#fff;box-shadow:0 4px 6px -1px rgba(0,0,0,.08)}";
    $c[]=".flt-head{display:flex;align-items:center;justify-content:space-between;gap:8px;position:relative;z-index:1}";
    $c[]=".flt-lbl{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b}";
    $c[]=".flt-kpi.dark .flt-lbl{color:#cbd5e1}";
    $c[]=".flt-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;flex:0 0 10px}";
    $c[]=".flt-kpi.ok .flt-dot{background:$a}.flt-kpi.warn .flt-dot{background:#f59e0b}.flt-kpi.err .flt-dot{background:#e11d48}";
    $c[]=".flt-dot.pulse{animation:fltPulse 2s infinite}";
    $c[]="@keyframes fltPulse{0%{box-shadow:0 0 0 0 $al}70%{box-shadow:0 0 0 8px rgba(0,0,0,0)}100%{box-shadow:0 0 0 0 rgba(0,0,0,0)}}";
    $c[]=".flt-chip{font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px;border:1px solid #e2e8f0;background:#f1f5f9;color:#475569;white-space:nowrap}";
    $c[]=".flt-kpi.ok .flt-chip{color:$ad;background:$as;border-color:$al}";
    $c[]=".flt-kpi.warn .flt-chip{color:#b45309;background:#fef3c7;border-color:#fcd34d}";
    $c[]=".flt-kpi.err .flt-chip{color:#be123c;background:#ffe4e6;border-color:#fda4af}";
    $c[]=".flt-kpi.dark .flt-chip{font-family:'JetBrains Mono',monospace;font-size:10px;border-radius:4px;text-transform:uppercase;background:rgba(20,184,166,.2);color:#5eead4;border-color:rgba(20,184,166,.3)}";
    $c[]=".flt-kpi.dark .flt-chip.st-warn{background:rgba(245,158,11,.2);color:#fcd34d;border-color:rgba(245,158,11,.35)}";
    $c[]=".flt-kpi.dark .flt-chip.st-err{background:rgba(225,29,72,.22);color:#fda4af;border-color:rgba(225,29,72,.4)}";
    $c[]=".flt-mid{display:flex;align-items:flex-end;justify-content:space-between;gap:10px;margin:14px 0;position:relative;z-index:1}";
    $c[]=".flt-val{font-size:44px;line-height:1;font-weight:900;letter-spacing:-.02em;color:#0f172a;font-variant-numeric:tabular-nums}";
    $c[]=".flt-kpi.dark .flt-val{color:#fff;font-size:32px}";
    $c[]=".flt-sub{font-size:12px;font-weight:600;color:$ad;margin-top:6px}";
    $c[]=".flt-kpi.dark .flt-sub{color:#94a3b8;font-weight:500}";
    $c[]=".flt-side{font-size:12px;color:#64748b;font-weight:500;text-align:right}";
    $c[]=".flt-side strong{color:#334155;font-family:'JetBrains Mono',monospace}";
    $c[]=".flt-tag{font-size:12px;font-weight:600;padding:3px 8px;border-radius:4px;background:$as;color:$ad;white-space:nowrap}";
    $c[]=".flt-tag.err{background:#ffe4e6;color:#be123c}";
    $c[]=".flt-foot{position:relative;z-index:1;border-top:1px solid rgba(15,23,42,.08);padding-top:8px;display:flex;justify-content:space-between;gap:8px;font-size:12px;color:#64748b}";
    $c[]=".flt-foot strong{color:#0f172a;font-weight:600}";
    $c[]=".flt-kpi.dark .flt-foot{border-color:rgba(148,163,184,.25);color:#94a3b8}";
    $c[]=".flt-good{color:$ad;font-weight:600}.flt-kpi.dark .flt-good{color:#34d399}";
    $c[]=".flt-bad{color:#be123c;font-weight:600}.flt-kpi.dark .flt-bad{color:#fda4af}";
    $c[]=".flt-wm{position:absolute;right:-12px;bottom:-12px;width:112px;height:112px;pointer-events:none;opacity:.05;color:#64748b}";
    $c[]=".flt-kpi.ok .flt-wm{color:$a}.flt-kpi.err .flt-wm{color:#e11d48}.flt-kpi.warn .flt-wm{color:#f59e0b}";
    $c[]=".flt-gauge{position:relative;width:52px;height:52px;flex:0 0 52px}";
    $c[]=".flt-gauge>svg{width:52px;height:52px;transform:rotate(-90deg)}";
    $c[]=".flt-gauge .ck{position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;font-size:18px}";
    // Cartes en anneau
    $c[]=".flt-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05);display:flex;flex-direction:column;min-height:430px}";
    $c[]=".flt-ch{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}";
    $c[]=".flt-ch h3{margin:0;font-size:14px;font-weight:700;color:#0f172a;line-height:1.4}";
    $c[]=".flt-mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:4px;white-space:nowrap;max-width:50%;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".flt-ok{font-size:11px;font-weight:600;color:$ad;background:$as;border:1px solid $al;padding:2px 8px;border-radius:4px;white-space:nowrap}";
    $c[]=".flt-donut{position:relative;width:176px;height:176px;margin:22px auto}";
    $c[]=".flt-donut svg{width:100%;height:100%;transform:rotate(-90deg)}";
    $c[]=".flt-center{position:absolute;top:0;left:0;right:0;bottom:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:0 30px}";
    $c[]=".flt-center b{font-size:26px;line-height:1.15;font-weight:900;color:#0f172a;font-variant-numeric:tabular-nums}";
    $c[]=".flt-center b.hl{color:#e11d48}";
    $c[]=".flt-center span{font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.04em;margin-top:2px}";
    $c[]=".flt-center em{font-style:normal;font-size:13px;color:#475569;margin-top:2px}";
    $c[]=".flt-legend{margin-top:auto;border-top:1px solid #f1f5f9;padding-top:12px;font-size:13px}";
    $c[]=".flt-li{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:4px 6px;margin:0 -6px;border-radius:4px}";
    $c[]=".flt-li.click{cursor:pointer}.flt-li.click:hover{background:#f8fafc}";
    $c[]=".flt-li .k{display:flex;align-items:center;gap:8px;color:#475569;font-weight:500;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".flt-li .sw{width:12px;height:12px;border-radius:50%;flex:0 0 12px}";
    $c[]=".flt-li .v{font-weight:600;color:#0f172a;font-variant-numeric:tabular-nums;white-space:nowrap}";
    $c[]=".flt-li.mute .k,.flt-li.mute .v{color:#cbd5e1}";
    $c[]=".flt-nodata{margin:auto;text-align:center;color:#94a3b8;font-size:13px;padding:30px 0}";
    $c[]=".flt-nodata .ic{font-size:40px;margin-bottom:10px;color:#cbd5e1}";
    // Tableau
    $c[]=".flt-tcard{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden}";
    $c[]=".flt-th{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px;flex-wrap:wrap}";
    $c[]=".flt-th h3{font-size:18px;font-weight:700;margin:0;color:#0f172a;display:flex;align-items:center;gap:10px;flex-wrap:wrap}";
    $c[]=".flt-th p{margin:4px 0 0;font-size:13px;color:#64748b}";
    $c[]=".flt-tools{display:flex;gap:10px;align-items:center;flex-wrap:wrap}";
    $c[]=".flt-search{position:relative}";
    $c[]=".flt-sico{position:absolute;left:11px;top:0;bottom:0;display:flex;align-items:center;color:#94a3b8;font-size:13px;pointer-events:none}";
    $c[]=".flt-search input{height:36px;width:260px;max-width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px 0 32px;font-size:13px;color:#1e293b;background:#fff;outline:none}";
    $c[]=".flt-search input:focus{border-color:$a;box-shadow:0 0 0 3px $as}";
    $c[]=".flt-btn{height:36px;padding:0 14px;border-radius:8px;border:1px solid $al;background:$as;color:$ad;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:8px;cursor:pointer}";
    $c[]=".flt-btn:hover{filter:brightness(.97)}";
    $c[]=".flt-scroll{overflow-x:auto}";
    $c[]="table.flt-table{width:100%;border-collapse:collapse;font-size:13px;color:#1e293b}";
    $c[]=".flt-table th{padding:12px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;white-space:nowrap}";
    $c[]=".flt-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#1e293b}";
    $c[]=".flt-table tr:hover td{background:#f8fafc}";
    $c[]=".flt-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;border:1px solid #e2e8f0;white-space:nowrap}";
    $c[]=".flt-pill:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}";
    $c[]=".flt-pill.on{color:$ad;background:$as;border-color:$al}";
    $c[]=".flt-pill.off{color:#be123c;background:#fff1f2;border-color:#fecdd3}";
    $c[]=".flt-pill.pend{color:#b45309;background:#fffbeb;border-color:#fde68a}";
    $c[]=".flt-pill.dis{color:#64748b;background:#f1f5f9;border-color:#e2e8f0}";
    $c[]=".flt-host{font-weight:600;color:#0f172a;cursor:pointer}.flt-host:hover{text-decoration:underline}";
    $c[]=".flt-hsub{font-size:12px;color:#94a3b8;margin-top:2px}";
    $c[]=".flt-bell{display:inline-block;margin-left:6px;font-size:11px;font-weight:700;color:#b45309;background:#fef3c7;border-radius:999px;padding:0 7px}";
    $c[]=".flt-ip{font-family:'JetBrains Mono',monospace;font-size:12.5px;white-space:nowrap}";
    $c[]=".flt-os{white-space:nowrap}";
    $c[]=".flt-os small{display:block;font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:600;color:$ad;margin-top:2px}";
    $c[]=".flt-os small.agent{color:#005447}";
    $c[]=".flt-bar{display:flex;align-items:center;gap:10px;min-width:140px}";
    $c[]=".flt-bar .tr{flex:1 1 auto;height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;max-width:90px}";
    $c[]=".flt-bar .fi{height:100%;border-radius:999px}";
    $c[]=".flt-bar .n{font-family:'JetBrains Mono',monospace;font-size:12px;white-space:nowrap}";
    $c[]=".flt-act{display:flex;gap:4px;justify-content:flex-end}";
    $c[]=".flt-act a{width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;color:#64748b;cursor:pointer;font-size:15px}";
    $c[]=".flt-act a:hover{background:#f1f5f9;color:#0f172a}";
    $c[]=".flt-tf{display:flex;justify-content:space-between;gap:12px;padding:14px 20px;background:#f8fafc;font-size:12.5px;color:#64748b;flex-wrap:wrap}";
    $c[]=".flt-tf strong{color:#334155}";
    $c[]=".flt-tempty{padding:40px 20px;text-align:center;color:#94a3b8;font-size:13px;border-top:1px solid #e2e8f0}";
    return "<style>".@implode("\n",$c)."</style>";
}

// Lecture JSON memorisee pour la requete : plusieurs widgets d'une meme reponse lisent la meme route.
function flt_api(string $path):array{
    if(isset($GLOBALS["FLT_API"][$path])){
        return $GLOBALS["FLT_API"][$path];
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path),true);
    if(!is_array($json)){
        $json=array();
    }
    $GLOBALS["FLT_API"][$path]=$json;
    return $json;
}

function flt_agents():array{
    $json=flt_api("/netagents/list");
    if(!isset($json["agents"]) || !is_array($json["agents"])){
        return array();
    }
    return $json["agents"];
}

// Horodatage Go (RFC 3339, valeur zero "0001-01-01T00:00:00Z") -> epoch, 0 si absent.
function flt_ts($value):int{
    if(!is_string($value) || $value==""){
        return 0;
    }
    $t=strtotime($value);
    if($t===false || $t<946684800){
        return 0;
    }
    return $t;
}

function flt_ago(int $ts):string{
    if($ts==0){
        return "{never}";
    }
    $d=time()-$ts;
    if($d<0){$d=0;}
    if($d<60){return "$d s";}
    if($d<3600){return intval($d/60)." min";}
    if($d<86400){return intval($d/3600)." h";}
    return intval($d/86400)." {fleet_days}";
}

function flt_pct(float $part,float $total):float{
    if($total<=0){
        return 0.0;
    }
    return $part*100/$total;
}

function flt_pct_txt(float $pct):string{
    if($pct>0 && $pct<1){
        return "<1%";
    }
    return round($pct,1)."%";
}

function flt_server_svg():string{
    return "<svg class='flt-wm' fill='currentColor' viewBox='0 0 24 24'><rect height='7' rx='2' width='20' x='2' y='3'></rect><rect height='7' rx='2' width='20' x='2' y='14'></rect></svg>";
}

function flt_kpi(string $tone,string $dotClass,string $label,string $chip,string $value,string $sub,string $side,string $footLeft,string $footRight,string $chipClass=""):string{
    $h=array();
    $h[]="<div class='flt-kpi $tone'>";
    $h[]="<div class='flt-head'><span class='flt-lbl'>";
    if($dotClass!="none"){
        $h[]="<span class='flt-dot $dotClass'></span>";
    }
    $h[]="$label</span>";
    if($chip!=""){
        $h[]="<span class='flt-chip $chipClass'>$chip</span>";
    }
    $h[]="</div>";
    $h[]="<div class='flt-mid'><div><div class='flt-val'>$value</div>";
    if($sub!=""){
        $h[]="<div class='flt-sub'>$sub</div>";
    }
    $h[]="</div>$side</div>";
    if($tone!="dark"){
        $h[]=flt_server_svg();
    }
    $h[]="<div class='flt-foot'><span>$footLeft</span><span>$footRight</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

function flt_kpis_html():string{
    $stats=flt_api("/netagents/stats");
    $agents=flt_agents();
    return widget_total($stats,$agents).widget_online($stats,$agents).widget_offline($stats,$agents).widget_meta_certificate();
}

function widget_total(array $stats,array $agents):string{
    $total=intval($stats["total"] ?? 0);
    $enabled=intval($stats["enabled"] ?? 0);
    $recent=0;$artica=0;
    $limit=time()-30*86400;
    foreach($agents as $a){
        if(flt_ts($a["registered_at"] ?? "")>=$limit){$recent++;}
        if(!empty($a["artica"])){$artica++;}
    }
    $linux=count($agents)-$artica;
    $tone=$total>0?"ok":"";
    $side="<div class='flt-side'><strong>$enabled</strong> {enabled}</div>";
    return flt_kpi($tone,"","{total_agents}","+$recent {fleet_last_30_days}",strval($total),"",$side,
        "{fleet_artica_appliances}: <strong>$artica</strong>",
        "{fleet_debian_agents}: <strong>$linux</strong>");
}

function widget_online(array $stats,array $agents):string{
    $online=intval($stats["online"] ?? 0);
    $total=intval($stats["total"] ?? 0);
    $pending=intval($stats["pending"] ?? 0);
    $last=0;
    foreach($agents as $a){
        if(($a["status"] ?? "")!="online"){continue;}
        $t=flt_ts($a["last_seen"] ?? "");
        if($t>$last){$last=$t;}
    }
    $pct=round(flt_pct($online,$total));
    $value=strval($online);
    $tone="ok";
    if($total==0){$tone="";$value="0";}
    elseif($online==0){$tone="err";$value="{fleet_none}";}
    elseif($online<$total){$tone="warn";}
    else{$value="{fleet_all}";}
    $side="<div class='flt-side'>{pending}: <strong>$pending</strong></div>";
    return flt_kpi($tone,"pulse","{online}","$pct% {fleet_reachable}",$value,"$online {fleet_active_agents}",$side,
        "{fleet_last_contact}: <strong>".flt_ago($last)."</strong>",
        "<span class='flt-good'>{fleet_refresh_minute}</span>");
}

function widget_offline(array $stats,array $agents):string{
    $offline=intval($stats["offline"] ?? 0);
    $total=intval($stats["total"] ?? 0);
    $disabled=$total-intval($stats["enabled"] ?? 0);
    $bell=0;$blacklisted=0;$oldest=0;
    foreach($agents as $a){
        $bell+=intval($a["artica_bell"] ?? 0);
        if(!empty($a["blacklisted"])){$blacklisted++;}
        if(($a["status"] ?? "")=="offline"){
            $t=flt_ts($a["last_seen"] ?? "");
            if($t>0 && ($oldest==0 || $t<$oldest)){$oldest=$t;}
        }
    }
    if($offline==0){
        $tone="";
        $value="{fleet_none}";
        $side="<span class='flt-tag'>{fleet_no_outage}</span>";
    }else{
        $tone="err";
        $value=$offline==$total?"{fleet_all}":strval($offline);
        $side="<span class='flt-tag err'>{fleet_down_for} ".flt_ago($oldest)."</span>";
    }
    $bellTxt=$bell>0?"<span class='flt-bad'>$bell</span>":"<strong>0</strong>";
    return flt_kpi($tone,"","{offline}","$disabled {disabled}",$value,"",$side,
        "{fleet_alerts}: $bellTxt",
        "{blacklisted}: <strong>$blacklisted</strong>");
}

// Inspect the public leaf certificate actually served by this console. TLS
// verification is disabled only for inspection so an expired cert stays visible;
// this connection sends no request, credentials, enrollment token or client key.
function flt_meta_certificate():array{
    if(isset($GLOBALS["FLT_META_CERTIFICATE"])){return $GLOBALS["FLT_META_CERTIFICATE"];}
    $GLOBALS["FLT_META_CERTIFICATE"]=array();
    if(!function_exists("openssl_x509_parse")){return array();}
    $port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    if($port<1 ||$port>65535){$port=9000;}
    $address=$_SERVER["SERVER_ADDR"] ?? "127.0.0.1";
    if(filter_var($address,FILTER_VALIDATE_IP)===false){$address="127.0.0.1";}
    $peer=$_SERVER["SERVER_NAME"] ?? "";
    $options=array("capture_peer_cert"=>true,"verify_peer"=>false,"verify_peer_name"=>false,"allow_self_signed"=>true);
    if(is_string($peer) &&filter_var($peer,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)!==false){$options["peer_name"]=$peer;$options["SNI_enabled"]=true;}
    if(strpos($address,":")!==false){$address="[$address]";}
    $context=stream_context_create(array("ssl"=>$options));
    $stream=@stream_socket_client("ssl://$address:$port",$errno,$message,2,STREAM_CLIENT_CONNECT,$context);
    if($stream===false){return array();}
    $params=stream_context_get_params($stream);fclose($stream);
    $certificate=$params["options"]["ssl"]["peer_certificate"] ?? null;
    if($certificate===null){return array();}
    $pem="";if(!openssl_x509_export($certificate,$pem)){return array();}
    $parsed=@openssl_x509_parse($certificate);
    if(!is_array($parsed) ||empty($parsed["validTo_time_t"]) ||empty($parsed["validFrom_time_t"])){return array();}
    $result=array("pem"=>$pem,"expires_at"=>intval($parsed["validTo_time_t"]),"not_before"=>intval($parsed["validFrom_time_t"]),"name"=>(string)($parsed["subject"]["CN"] ?? ""));
    $GLOBALS["FLT_META_CERTIFICATE"]=$result;return $result;
}
function widget_meta_certificate(?array $certificate=null,?int $now=null):string{
    if($certificate===null){$certificate=flt_meta_certificate();}
    if($now===null){$now=time();}
    $expiry=intval($certificate["expires_at"] ?? 0);
    if($expiry<=0){
        return flt_kpi("warn","","{fleet_meta_certificate}","{unknown}","—","{fleet_meta_certificate_unavailable}","","HTTPS · mTLS","");
    }
    $remaining=$expiry-$now;$tone="ok";$label="{fleet_meta_certificate}";$chip="{valid}";
    $days=max(0,intval(ceil($remaining/86400)));$sub="{fleet_meta_certificate_days_left}";
    if($remaining<=0){
        $tone="err";$label="{fleet_meta_certificate_expired}";$chip="{expired}";
        $days=max(0,intval(ceil(-$remaining/86400)));$sub="{fleet_meta_certificate_days_overdue}";
    }elseif(intval($certificate["not_before"] ?? 0)>$now){
        $tone="err";$chip="{invalid}";$days=0;$sub="{fleet_meta_certificate_not_yet_valid}";
    }elseif($remaining<5*86400){
        $tone="warn";$label="{fleet_meta_certificate_expiring}";$chip="&lt; 5 {fleet_days}";
    }
    $name=htmlspecialchars((string)($certificate["name"] ?? ""),ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8");
    return flt_kpi($tone,"",$label,$chip,(string)$days,$sub,"",
        "{expires}: <strong>".gmdate("Y-m-d H:i",$expiry)." UTC</strong>",$name);
}

function flt_server_certificate_allowed(bool $manage=false):bool{
    $users=new usersMenus();
    // Certificat du serveur Meta : jamais pour une session au perimetre d'un tenant.
    $allowed=!meta_tenant_session() && (!empty($users->AsSystemAdministrator)||!empty($users->AsCertifsManager)||(!$manage&&meta_is_provider($users)));
    if($allowed){return true;}
    http_response_code(403);echo (new template_admin())->post_error("{ERROR_NO_PRIVS}");return false;
}
// Resolve the configured certificate's database alias, not the leaf CN: the two
// can differ. A public fingerprint match prevents renewal of a different entry.
function flt_server_certificate_record(array $certificate):?array{
    $name=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName");
    if(!is_string($name)||$name===""||$name==="__DEFAULT__"||empty($certificate["pem"])){return null;}
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $db=new CertificatesDB();$row=$db->getByCommonName($name);
    if(!is_object($row)||intval($row->ID ?? 0)<1){return null;}
    $actual=@openssl_x509_fingerprint($certificate["pem"],"sha256");
    if(!is_string($actual)){return null;}
    foreach(array("crt","SquidCert") as $field){
        $pem=str_replace("\\n","\n",(string)($row->$field ?? ""));
        $stored=@openssl_x509_fingerprint($pem,"sha256");
        if(is_string($stored)&&hash_equals($actual,$stored)){
            return array("id"=>intval($row->ID),"name"=>$name,"letsencrypt"=>intval($row->UseLetsEncrypt ?? 0)===1);
        }
    }
    return null;
}
function flt_server_certificate_js():bool{
    if(!flt_server_certificate_allowed()){return false;}
    $tpl=new template_admin();$page=CurrentPageName();
    $tpl->js_dialog2("{server_certificate}","$page?server-certificate-popup=yes",950);return true;
}
function flt_server_certificate_popup(bool $wrap=true):bool{
    if(!flt_server_certificate_allowed()){return false;}
    header("Cache-Control: no-store");$tpl=new template_admin();$page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.certificates.view.inc");
    $certificate=flt_meta_certificate();$record=flt_server_certificate_record($certificate);$users=new usersMenus();
    $manage=!empty($users->AsSystemAdministrator)||!empty($users->AsCertifsManager);
    if($wrap){echo "<div id='flt-server-certificate-popup'>";}
    if($record===null){echo $tpl->_ENGINE_parse_body($tpl->div_warning("{fleet_meta_certificate_unmanaged}"));}
    $buttons=array();
    if($manage){
        $security=!empty($users->AsSystemAdministrator)?"AsSystemAdministrator":"AsCertifsManager";
        if($record!==null){$buttons[]=$tpl->button_autnonome("{fleet_meta_renew_certificate}","Loadjs('$page?server-certificate-renew-js=yes')",ico_refresh,$security,0,"btn-warning");}
        $buttons[]=$tpl->button_autnonome("{certificates}","LoadAjax('MainContent','fw.certificates-center.php')","fas fa-certificate",$security,0,"btn-default");
    }
    echo $tpl->_ENGINE_parse_body("<div style='display:flex;justify-content:flex-end;gap:8px;margin-bottom:15px'>".implode("",$buttons)."</div>");
    echo cert_view_tabs(array("overview"=>"$page?server-certificate-view=overview","key"=>"$page?server-certificate-view=key","raw"=>"$page?server-certificate-view=raw"));
    if($wrap){echo '</div>';}
    echo '<script>window.FltServerCertificateRefresh=function(){if(document.getElementById("flt-kpis")){LoadAjaxSilent("flt-kpis","'.$page.'?top-meta-status=yes");}if(document.getElementById("flt-server-certificate-popup")){LoadAjaxSilent("flt-server-certificate-popup","'.$page.'?server-certificate-popup-body=yes");}};</script>';
    return true;
}
function flt_server_certificate_view():bool{
    if(!flt_server_certificate_allowed()){return false;}
    header("Cache-Control: no-store");$view=$_GET["server-certificate-view"] ?? "";
    if(!in_array($view,array("overview","key","raw"),true)){http_response_code(400);return false;}
    include_once(dirname(__FILE__)."/ressources/class.certificates.view.inc");
    $certificate=flt_meta_certificate();$pem=$certificate["pem"] ?? "";$error="";$parsed=cert_view_parse_pem($pem,$error);
    if($error!==""){echo $error;return false;}
    $uid="meta-server";
    if($view==="overview"){echo cert_view_css().cert_view_overview_html($pem,$parsed,$uid);}
    elseif($view==="key"){echo cert_view_css().cert_view_key_html($pem,$parsed,$uid);}
    else{echo cert_view_css().cert_view_raw_html($pem,$parsed,$uid);}
    return true;
}
function flt_server_certificate_renew_js():bool{
    if(!flt_server_certificate_allowed(true)){return false;}
    header("Cache-Control: no-store");$tpl=new template_admin();$record=flt_server_certificate_record(flt_meta_certificate());
    if($record===null){echo $tpl->post_error("{fleet_meta_certificate_unmanaged}");return false;}
    if($record["letsencrypt"]){
        $target="fw.certificates-center.letsencrypt.php?".http_build_query(array("ID"=>$record["id"],"function"=>"FltServerCertificateRefresh","function2"=>base64_encode("FltServerCertificateRefresh();")),"","&",PHP_QUERY_RFC3986);
    }else{
        $target="fw.certificates-center.php?".http_build_query(array("certificate-js"=>$record["name"],"function"=>"FltServerCertificateRefresh"),"","&",PHP_QUERY_RFC3986);
    }
    // Open the existing workflow. Issuance is performed only after its explicit
    // confirmation; displaying certificate details never triggers a renewal.
    echo "Loadjs(".json_encode($target,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).");";return true;
}

// ── Cartes en anneau : SVG calcule ici, aucune dependance a Chart.js ─────────

function flt_palette():array{
    $ac=flt_accents();
    return array("#e11d48",$ac["accent"],"#0ea5e9","#005447","#f59e0b","#94a3b8");
}

// $segments = array(array(valeur,couleur),...) ; cercle de circonference 100 (r=15.9155).
function flt_donut(array $segments,string $center):string{
    $total=0.0;
    foreach($segments as $s){$total+=floatval($s[0]);}
    $h=array();
    $h[]="<div class='flt-donut'><svg viewBox='0 0 36 36'>";
    $h[]="<circle cx='18' cy='18' r='15.9155' fill='none' stroke='#f1f5f9' stroke-width='3.6'></circle>";
    $offset=0.0;
    if($total>0){
        foreach($segments as $s){
            $p=floatval($s[0])*100/$total;
            if($p<=0){continue;}
            $p1=round($p,3);$rest=round(100-$p,3);$off=round(-$offset,3);
            $h[]="<circle cx='18' cy='18' r='15.9155' fill='none' stroke='{$s[1]}' stroke-width='3.8' stroke-dasharray='$p1 $rest' stroke-dashoffset='$off'></circle>";
            $offset+=$p;
        }
    }
    $h[]="</svg><div class='flt-center'>$center</div></div>";
    return @implode("",$h);
}

function flt_legend_row(string $color,string $label,string $value,string $js="",bool $mute=false):string{
    $cls="flt-li";
    if($mute){$cls.=" mute";}
    $on="";
    if($js!=""){
        $cls.=" click";
        $on=" OnClick=\"$js\"";
    }
    if($mute){$color="#e2e8f0";}
    return "<div class='$cls'$on><span class='k'><span class='sw' style='background:$color'></span>$label</span><span class='v'>$value</span></div>";
}

function flt_card(string $title,string $chip,string $body,array $legend):string{
    $h=array();
    $h[]="<div class='flt-ch'><h3>$title</h3>$chip</div>";
    $h[]=$body;
    if(count($legend)>0){
        $h[]="<div class='flt-legend'>".@implode("",$legend)."</div>";
    }
    return @implode("",$h);
}

function flt_nodata(string $ico,string $text):string{
    return "<div class='flt-nodata'><div class='ic'><i class='$ico'></i></div>$text</div>";
}

function flt_gb(float $bytes):string{
    return FormatBytes($bytes/1024,true);
}

function card_repository():bool{
    $tpl=new template_admin();
    $json=flt_api("/articarepos/sync/status");
    $path=htmlspecialchars($json["repo_path"] ?? "/home/artica/artica-repository");
    $part=$json["repo_part"] ?? array();
    $partTotal=floatval($part["total_bytes"] ?? 0);
    $partUsed=floatval($part["used_bytes"] ?? 0);
    $avail=floatval($part["available_bytes"] ?? 0);
    $repo=floatval($json["repo_size_bytes"] ?? 0);
    $chip="<span class='flt-mono' title=\"$path\">$path</span>";
    if($partTotal<=0){
        echo $tpl->_ENGINE_parse_body(flt_card("{fleet_repo_partition}",$chip,flt_nodata("fas fa-hard-drive","{fleet_no_data}"),array()));
        return true;
    }
    $other=max(0,$partUsed-$repo);
    $sum=$repo+$other+$avail;
    $pal=flt_palette();
    $usedPct=floatval($part["usage_percent"] ?? flt_pct($partUsed,$partTotal));
    $center="<b>".round($usedPct)."%</b><span>{fleet_used}</span>";
    $legend=array();
    $legend[]=flt_legend_row($pal[0],"{fleet_repository}",flt_gb($repo)." (".flt_pct_txt(flt_pct($repo,$sum)).")");
    if($other>0){
        $legend[]=flt_legend_row($pal[5],"{fleet_other_data}",flt_gb($other)." (".flt_pct_txt(flt_pct($other,$sum)).")");
    }
    $legend[]=flt_legend_row($pal[1],"{fleet_free_space}",flt_gb($avail)." (".flt_pct_txt(flt_pct($avail,$sum)).")");
    $body=flt_donut(array(array($avail,$pal[1]),array($repo,$pal[0]),array($other,$pal[5])),$center);
    echo $tpl->_ENGINE_parse_body(flt_card("{fleet_repo_partition}",$chip,$body,$legend));
    return true;
}

function card_agent_versions():bool{
    $tpl=new template_admin();
    $json=flt_api("/netagents/packages/stats");
    $versions=array();
    if(isset($json["agents_versions"]) && is_array($json["agents_versions"])){
        foreach($json["agents_versions"] as $ver=>$count){
            if(intval($count)>0){$versions[strval($ver)]=intval($count);}
        }
    }
    if(count($versions)==0){
        echo $tpl->_ENGINE_parse_body(flt_card("{fleet_agent_versions}","",flt_nodata("fas fa-code-branch","{fleet_no_data}"),array()));
        return true;
    }
    uksort($versions,function($a,$b){ return version_compare($b,$a); });
    $total=array_sum($versions);
    $latest=array_keys($versions)[0];
    $latestPct=round(flt_pct($versions[$latest],$total));
    $chip="<span class='flt-ok'>$latestPct% {fleet_on_latest}</span>";
    $colors=array("#0ea5e9","#005447","#f59e0b","#e11d48","#94a3b8");
    $segments=array();$legend=array();$i=0;$others=0;
    foreach($versions as $ver=>$count){
        if($i>=4){$others+=$count;continue;}
        $color=$colors[$i];
        $segments[]=array($count,$color);
        $js="Loadjs('fw.netagents.list.php?agents-upgrade-js=0&agentver=".urlencode($ver)."')";
        $legend[]=flt_legend_row($color,"{agent} ".htmlspecialchars($ver),"$count (".flt_pct_txt(flt_pct($count,$total)).")",$js);
        $i++;
    }
    if($others>0){
        $segments[]=array($others,$colors[4]);
        $legend[]=flt_legend_row($colors[4],"{fleet_others}","$others (".flt_pct_txt(flt_pct($others,$total)).")");
    }
    $center="<b>$total</b><span>{fleet_total_nodes}</span>";
    echo $tpl->_ENGINE_parse_body(flt_card("{fleet_agent_versions}",$chip,flt_donut($segments,$center),$legend));
    return true;
}

function flt_debian_name(int $v):string{
    $names=array(9=>"Stretch",10=>"Buster",11=>"Bullseye",12=>"Bookworm",13=>"Trixie",14=>"Forky");
    return isset($names[$v])?$names[$v]:"";
}

function card_debian_versions():bool{
    $tpl=new template_admin();
    $json=flt_api("/netagents/dashboard/updates");
    $versions=array();
    if(isset($json["DebianVersions"]) && is_array($json["DebianVersions"])){
        foreach($json["DebianVersions"] as $row){
            $v=intval($row["debian_version"] ?? 0);
            $n=intval($row["count"] ?? 0);
            if($v==0 || $n==0){continue;}
            $versions[$v]=($versions[$v] ?? 0)+$n;
        }
    }
    $chip="<span class='flt-mono'>Linux</span>";
    if(count($versions)==0){
        echo $tpl->_ENGINE_parse_body(flt_card("{fleet_debian_versions}",$chip,flt_nodata("fab fa-linux","{fleet_no_data}"),array()));
        return true;
    }
    arsort($versions);
    $total=array_sum($versions);
    $top=array_keys($versions)[0];
    $topName=flt_debian_name($top);
    $center="<b class='hl'>Debian</b><em>v$top".($topName!=""?"<br>($topName)":"")."</em>";
    $colors=array("#e11d48",flt_accents()["accent"],"#0ea5e9","#005447","#94a3b8");
    $segments=array();$legend=array();$i=0;
    foreach($versions as $v=>$n){
        $color=$colors[min($i,4)];
        $segments[]=array($n,$color);
        $name=flt_debian_name($v);
        $legend[]=flt_legend_row($color,"Debian $v".($name!=""?" ($name)":""),"$n (".flt_pct_txt(flt_pct($n,$total)).")");
        $i++;
    }
    echo $tpl->_ENGINE_parse_body(flt_card("{fleet_debian_versions}",$chip,flt_donut($segments,$center),$legend));
    return true;
}

function card_update_tasks():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=flt_api("/netagents/dashboard/updates");
    $stats=flt_api("/netagents/stats");
    $agentsWith=intval($stats["agents_with_upgrade"] ?? 0);
    $pal=flt_palette();
    $cats=array(
        array("Softwares","{softwares_updates}",intval($json["TotalSoftwares"] ?? 0),$pal[1]),
        array("AptSoftwares","{apt_packages}",intval($json["TotalAptSoftwares"] ?? 0),$pal[0]),
        array("ArticaSP","{service_packs}",intval($json["TotalArticaSP"] ?? 0),$pal[3]),
        array("ArticaHotfix","{hotfixes}",intval($json["TotalArticaHotfix"] ?? 0),$pal[4]),
    );
    $total=0;
    foreach($cats as $c){$total+=$c[2];}
    $chip="<span class='flt-mono'>$agentsWith {agents}</span>";
    if($total==0){
        $ac=flt_accents();
        $body="<div class='flt-nodata'><div class='ic' style='color:{$ac["accent"]}'><i class='fas fa-circle-check'></i></div>{fleet_no_update}</div>";
        echo $tpl->_ENGINE_parse_body(flt_card("{Update_tasks_by_topic}",$chip,$body,array()));
        return true;
    }
    $segments=array();$legend=array();
    foreach($cats as $c){
        $segments[]=array($c[2],$c[3]);
        $js=$c[2]>0?"Loadjs('$page?agents-upgrade-list-js=yes&category={$c[0]}')":"";
        $legend[]=flt_legend_row($c[3],$c[1],"{$c[2]} (".flt_pct_txt(flt_pct($c[2],$total)).")",$js,$c[2]==0);
    }
    $center="<b>$total</b><span>{fleet_tasks_total}</span>";
    echo $tpl->_ENGINE_parse_body(flt_card("{Update_tasks_by_topic}",$chip,flt_donut($segments,$center),$legend));
    return true;
}

// ── Apercu de la flotte ───────────────────────────────────────────────────────

// Ce qui demande l'attention d'abord : hors ligne, en attente, alertes, puis le reste.
function flt_rank(array $a):int{
    if(empty($a["enabled"])){return 4;}
    $st=$a["status"] ?? "";
    if($st=="offline"){return 0;}
    if($st!="online"){return 1;}
    if(intval($a["artica_bell"] ?? 0)>0){return 2;}
    return 3;
}

function flt_live(int $id):array{
    $json=flt_api("/netagents/status/$id");
    $out=array("cpu"=>-1.0,"mem_used"=>0.0,"mem_total"=>0.0);
    if(isset($json["cpu_pct"])){
        $out["cpu"]=floatval($json["cpu_pct"]);
    }
    if(isset($json["memory"]) && is_array($json["memory"])){
        $out["mem_used"]=floatval($json["memory"]["used_bytes"] ?? 0);
        $out["mem_total"]=floatval($json["memory"]["total_bytes"] ?? 0);
    }
    return $out;
}

function flt_bar(float $pct,string $label):string{
    $pct=max(0,min(100,$pct));
    $color=flt_accents()["accent"];
    if($pct>=80){$color="#e11d48";}elseif($pct>=50){$color="#f59e0b";}
    $w=round($pct,1);
    return "<div class='flt-bar'><span class='tr'><span class='fi' style='display:block;width:$w%;background:$color'></span></span><span class='n'>$label</span></div>";
}

function flt_status_pill(array $a):string{
    if(empty($a["enabled"])){return "<span class='flt-pill dis'>{disabled}</span>";}
    $st=$a["status"] ?? "";
    if($st=="online"){return "<span class='flt-pill on'>{online}</span>";}
    if($st=="offline"){return "<span class='flt-pill off'>{offline}</span>";}
    return "<span class='flt-pill pend'>{pending}</span>";
}

// Agent en connexion inversee : adresse enregistree factice "<uuid>.invalid" (port 0).
// On affiche alors les adresses LAN de son dernier statut (meme source que fw.netagents.list.php).
function flt_display_ip(array $a):string{
    $ip=trim(strval($a["ip_address"] ?? ""));
    if(substr($ip,-8)!==".invalid"){return $ip;}
    $st=flt_api("/netagents/status/".intval($a["id"] ?? 0));
    $priv=array();$other=array();
    if(isset($st["network_interfaces"]) && is_array($st["network_interfaces"])){
        foreach($st["network_interfaces"] as $nic){
            if(!is_array($nic)){continue;}
            if(($nic["type"] ?? "")==="loopback" || ($nic["name"] ?? "")==="lo"){continue;}
            if(!isset($nic["ipv4_addresses"]) || !is_array($nic["ipv4_addresses"])){continue;}
            foreach($nic["ipv4_addresses"] as $ad){
                $v=is_array($ad)?trim(strval($ad["address"] ?? "")):"";
                if($v=="" || !filter_var($v,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){continue;}
                if(!filter_var($v,FILTER_VALIDATE_IP,FILTER_FLAG_NO_RES_RANGE)){continue;}
                if(!filter_var($v,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE)){$priv[$v]=true;}else{$other[$v]=true;}
            }
        }
    }
    $lan=array_slice(array_keys($priv+$other),0,2);
    return count($lan)>0?implode(", ",$lan):"-";
}

function flt_row(array $a):string{
    $id=intval($a["id"] ?? 0);
    $host=htmlspecialchars($a["hostname"] ?? "");
    if($host==""){$host="#$id";}
    $sub=array();
    $desc=trim($a["description"] ?? "");
    if($desc!=""){$sub[]=htmlspecialchars($desc);}
    if(isset($a["groups"]) && is_array($a["groups"])){
        foreach(array_keys($a["groups"]) as $g){$sub[]=htmlspecialchars($g);}
    }
    $bell=intval($a["artica_bell"] ?? 0);
    $bellHtml=$bell>0?"<span class='flt-bell' title='{fleet_alerts}'><i class='fas fa-bell'></i> $bell</span>":"";
    $info="Loadjs('fw.netagents.list.php?agent-info-js=$id')";

    $deb=intval($a["debian_version"] ?? 0);
    $os=$deb>0?"Debian $deb ".flt_debian_name($deb):"<span class='ak-muted'>{fleet_os_unknown}</span>";
    if(!empty($a["artica"]) && ($a["artica_version"] ?? "")!=""){
        $ver="Artica v".htmlspecialchars($a["artica_version"]);
        if(intval($a["artica_service_pack"] ?? 0)>0){$ver.=" SP".intval($a["artica_service_pack"]);}
        $os.="<small>$ver</small>";
    }else{
        $agentVer=trim(strval($a["version"] ?? ""));
        $os.="<small class='agent'>{agent} ".($agentVer!=""?"v".htmlspecialchars($agentVer):"-")."</small>";
    }

    $cpu="<span class='ak-muted'>-</span>";
    $mem="<span class='ak-muted'>-</span>";
    if(!empty($a["enabled"]) && ($a["status"] ?? "")=="online"){
        $live=flt_live($id);
        if($live["cpu"]>=0){
            $cpu=flt_bar($live["cpu"],round($live["cpu"])."%");
        }
        if($live["mem_total"]>0){
            $mem=flt_bar(flt_pct($live["mem_used"],$live["mem_total"]),
                round($live["mem_used"]/1073741824,1)." / ".round($live["mem_total"]/1073741824,1)." G");
        }
    }

    $ip=htmlspecialchars(flt_display_ip($a));
    $h=array();
    $h[]="<tr>";
    $h[]="<td>".flt_status_pill($a)."</td>";
    $h[]="<td><span class='flt-host' OnClick=\"$info\">$host</span>$bellHtml";
    if(count($sub)>0){$h[]="<div class='flt-hsub'>".@implode(" &bull; ",$sub)."</div>";}
    $h[]="</td>";
    $h[]="<td class='flt-ip'>$ip</td>";
    $h[]="<td class='flt-os'>$os</td>";
    $h[]="<td>$cpu</td>";
    $h[]="<td>$mem</td>";
    $h[]="<td style='white-space:nowrap'>".flt_ago(flt_ts($a["last_seen"] ?? ""))."</td>";
    $h[]="<td><div class='flt-act'>";
    $h[]="<a title='{details}' OnClick=\"$info\"><i class='fas fa-gear'></i></a>";
    $h[]="<a title='{metrics}' OnClick=\"Loadjs('fw.netagents.metrics.charts.php?agent-metrics-js=$id')\"><i class='fas fa-arrow-right'></i></a>";
    $h[]="</div></td>";
    $h[]="</tr>";
    return @implode("",$h);
}

function fleet_table():bool{
    $tpl=new template_admin();
    $agents=flt_agents();
    $q=trim(strval($_GET["q"] ?? ""));
    $total=count($agents);

    if($total==0){
        echo $tpl->_ENGINE_parse_body("<div class='flt-tempty'>{no_agents_configured}</div>");
        return true;
    }

    $matched=array();
    foreach($agents as $a){
        if($q!=""){
            $hay=($a["hostname"] ?? "")." ".flt_display_ip($a)." ".($a["ip_address"] ?? "")." ".($a["description"] ?? "");
            if(stripos($hay,$q)===false){continue;}
        }
        $matched[]=$a;
    }
    usort($matched,function($x,$y){
        $r=flt_rank($x)-flt_rank($y);
        if($r!=0){return $r;}
        return strcasecmp($x["hostname"] ?? "",$y["hostname"] ?? "");
    });
    $shown=array_slice($matched,0,10); // apercu : la liste complete est fw.netagents.list.php

    $h=array();
    if(count($shown)==0){
        $h[]="<div class='flt-tempty'>{fleet_no_match}</div>";
    }else{
        $h[]="<div class='flt-scroll'><table class='flt-table'><thead><tr>";
        $h[]="<th>{status}</th><th>{fleet_col_host}</th><th>{ipaddr}</th><th>{fleet_col_os}</th>";
        $h[]="<th>{fleet_col_cpu}</th><th>{memory}</th><th>{fleet_last_contact}</th><th style='text-align:right'>{actions}</th>";
        $h[]="</tr></thead><tbody>";
        foreach($shown as $a){$h[]=flt_row($a);}
        $h[]="</tbody></table></div>";
    }
    $h[]="<div class='flt-tf'><span>{fleet_shown}: <strong>".count($shown)."</strong> / <strong>".count($matched)."</strong></span>";
    $h[]="<span>{fleet_mtls_note}</span></div>";
    echo $tpl->_ENGINE_parse_body(@implode("",$h));
    return true;
}

function top_meta_status():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(flt_kpis_html());
    return true;
}

// Ajout direct, jeton, verification de tout le parc, enrolement : infogerant (routes global-only).
function flt_is_admin():bool{
    $users=new usersMenus();
    return meta_provider_or_sysadmin($users);
}

function flt_tenant_js():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(count(natn_session_tenant_ids())<2){return $tpl->js_error("{ERROR_NO_PRIVS}");}
    $page=CurrentPageName();
    return $tpl->js_dialog2("{meta_active_tenant}","$page?meta-tenant-popup=yes",520);
}

function flt_tenant_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js="<script>window.NatnSelect=function(id){\$.post('$page',{'meta-tenant-select':id},function(r){"
        ."if(String(r).length>3){swal({title:'Oops...',text:String(r).replace(/^jserror:/,''),html:true,type:'error'});return;}"
        ."document.location.reload();});};</script>";
    echo $tpl->_ENGINE_parse_body(natn_selector_popup_html()).$js;
    return true;
}

// Ecrit META_TENANT seulement si l'id appartient a META_SCOPE. N'ecrit RIEN en cas de succes.
function flt_tenant_select():bool{
    $tpl=new template_admin();
    $id=(string)($_POST["meta-tenant-select"] ?? "");
    if(!natn_select_tenant($id)){
        admin_tracks("Refused: selection of Artica Meta tenant ".preg_replace('/[^0-9]/','',$id)." (not in the session scope)");
        echo $tpl->post_error("{meta_tenant_not_yours}");
        return false;
    }
    unset($_SESSION["META_TENANT_NAMES"]);
    return admin_tracks("Selected Artica Meta tenant #".intval($id));
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
function AgentArtica($id):bool{
    $json=AgenSoft($id);
    if(!is_object($json)){
        echo "// AgentArtica -> $id is_object false\n";
        return false;
    }

    if(!property_exists($json,"artica_version") ){
        echo "// AgentArtica -> $id not property_exists\n";
        return false;
    }
    if(strlen($json->artica_version)>3){
        echo "// artica_version -> $id -> $json->artica_version > 3 OK\n";
        return true;
    }
    return false;
}
function AgentArticaVersion($id):string{
    $json=AgenSoft($id);
    if(!is_object($json) || !property_exists($json,"artica_version")){
        echo "// AgentArticaVersion -> $id not property_exists\n";
        return "";
    }
    if(strlen($json->artica_version)<3){
        echo "// artica_version -> $json->artica_version < 3\n";
        return "";
    }
    $f[]=$json->artica_version;
    if(intval($json->artica_service_pack)>0){
        $f[]="Service Pack $json->artica_service_pack";
    }
    if(strlen($json->artica_hotfix_version)>3){
        $f[]="Hotfix $json->artica_hotfix_version";
    }
    echo "// AgentArticaVersion ".__LINE__." -> ".count($f)."\n";
    return @implode("&nbsp;",$f);
}
function td_type($agentJson):string{
    $tpl=new template_admin();
    $ico="fab fa-linux";
    if(AgentArtica($agentJson->id)){
        $ico="ico ico-artica";
    }
    return "<i class='$ico'></i>";
}



function agent_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{add_agent}","$page?agent-add-popup=yes",550);
}

function agent_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsafter="dialogInstance2.close();LoadAjaxSilent('flt-table','$page?fleet-table=yes');";

    $form[]=$tpl->field_text("netagent-hostname","{hostname}","");
    $form[]=$tpl->field_text("netagent-ipaddress","{ipaddr}","");
    $form[]=$tpl->field_numeric("netagent-port","{listen_port}",28811);
    $form[]=$tpl->field_text("netagent-token","{enrollment_token}","");
    $form[]=$tpl->field_text("netagent-description","{description}","");
    echo $tpl->form_outside("{add_agent}",$form,"{enrollment_token_explain}","{add}",$jsafter,"AsSystemAdministrator");
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
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id = intval($_GET["agent-delete"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/delete/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
        echo $tpl->div_warning($json->Error);
        return false;
    }

    return true;
}



function agent_status($id):array{

    if(isset($GLOBALS["AGENT_STATUS"][$id])){
        return $GLOBALS["AGENT_STATUS"][$id];
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        VERBOSE(json_last_error_msg(),__LINE__);
        return array("hostname"=>"-","uptime"=>0,"cpu"=>0,"mem_pct"=>0);
    }

    if(isset($json->Error) && !empty($json->Error)){
        VERBOSE($json->Error,__LINE__);
        return array("hostname"=>"-","uptime"=>0,"cpu"=>0,"mem_pct"=>0);
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

    $hostname = isset($json->hostname) ? htmlspecialchars($json->hostname) : "-";
    $uptime = isset($json->uptime) ? htmlspecialchars($json->uptime) : "-";
    $cpu = isset($json->cpu_load) ? sprintf("%.2f / %.2f / %.2f", $json->cpu_load->load_1, $json->cpu_load->load_5, $json->cpu_load->load_15) : "-";
    $mem_pct = isset($json->memory) ? sprintf("%.1f%%", $json->memory->used_percent) : "-";

    $GLOBALS["AGENT_STATUS"][$id]=array("hostname"=>$hostname,"uptime"=>$uptime,"cpu"=>$cpu,"mem_pct"=>$mem_pct);
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
        echo $tpl->div_explain("{agent_online}");
    }else{
        echo $tpl->div_warning("{agent_offline}");
    }
    return true;
}

function agent_token_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id = intval($_GET["agent-token-js"]);
    return $tpl->js_dialog3("{set_token}","$page?agent-token-popup=$id",550);
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
        echo $tpl->js_ok("{enrollment_success}");
        echo "<script>setTimeout(function(){LoadAjaxSilent('flt-table','$page?fleet-table=yes');},2000);</script>";
    }else{
        echo $tpl->div_warning("{enrollment_failed}: ".(isset($json->Error) ? $json->Error : "{unknown_error}"));
    }
    return true;
}

// ── Dashboard Updates List Table ─────────────────────────────────────────────
function agents_upgrade_list_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category=trim($_GET["category"] ?? "");

    $titles=[
        "Softwares"=>"{softwares_updates}",
        "AptSoftwares"=>"{apt_packages}",
        "ArticaSP"=>"{service_packs}",
        "ArticaHotfix"=>"{hotfixes}"
    ];
    $title=$titles[$category];
    return $tpl->js_dialog2("{Update_tasks_by_topic}: $title","$page?agents-upgrade-list-popup=yes&category=$category",950);
}
function agents_upgrade_list_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category=trim($_GET["category"] ?? "");

    $validCategories=["Softwares","AptSoftwares","ArticaSP","ArticaHotfix"];
    if(!in_array($category,$validCategories)){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{invalid_category}"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dashboard/list/updates"));
    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("Failed to fetch dashboard list"));
        return;
    }
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning(htmlspecialchars($json->Error ?? "Unknown error")));
        return;
    }

    $titles=[
        "Softwares"=>"{softwares_updates}",
        "AptSoftwares"=>"{apt_packages}",
        "ArticaSP"=>"{service_packs}",
        "ArticaHotfix"=>"{hotfixes}"
    ];

    $t=time();
    $tdStyle1="style='width:1%' nowrap";
    $tdStyle1R="style='width:1%;text-align:right' nowrap";
    $TRCLASS=null;
    $colspan=4;
    $icoDeb=ico_debian;
    $icopk=ico_package;
    $filter="true";
    $agentsjs=array();

    switch($category){
        case "Softwares":
            $items=isset($json->Softwares)?$json->Softwares:[];
            $colspan=4;

            $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text'>Debian</th>";
            $html[]="<th data-sortable=true data-type='text'>{updates}</th>";
            $html[]="<th>{details}</th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                $TRCLASS=null;
                $hostname=htmlspecialchars($item->hostname ?? "");
                $dv=intval($item->debian_version ?? 0);
                $cnt=intval($item->updates_count ?? 0);
                $id=intval($item->agent_id ?? 0);
                $details=[];
                if($item->debian_version==0){
                    continue;
                }
                $TRCLASS=null;
                if(isset($item->updates) && is_array($item->updates)){
                    foreach($item->updates as $u){
                        $app=htmlspecialchars($u->app_code ?? "");
                        $inst=htmlspecialchars($u->installed_version ?? "");
                        $avail=htmlspecialchars($u->available_version ?? "");
                        $appText=$tpl->_ENGINE_parse_body("{{$app}}");
                        $appKey=$app;

                        if($appKey=="APP_SQUID"){
                            $appKey="APP_SQUID,APP_SQUID6";
                        }
                        $appjs=$tpl->td_href($appText,"","Loadjs('fw.netagents.update.software.single.php?id=$id&soft=$appKey&debver=$dv')");
                        $details[]="<i class='$icopk'></i>&nbsp;<span style='font-family:monospace'>$appjs</span>: $inst &rarr; <strong>$avail</strong>";
                    }
                }
                $detailStr=implode("<br>",$details);
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='fas fa-server'></i>&nbsp;$hostname</td>";
                $html[]="<td $tdStyle1><i class='$icoDeb fa-2x'></i>&nbsp;<span style='font-size:18px'>$dv</span></td>";
                $html[]="<td $tdStyle1R><span class='badge' style='background:#d90654;color:#fff;font-size:18px'>$cnt</span></td>";
                $html[]="<td>$detailStr</td>";
                $html[]="</tr>";
            }
            break;

        case "AptSoftwares":

            $items=isset($json->AptSoftwares)?$json->AptSoftwares:[];

            $ico_down=ico_download;
            $button_update_all=$tpl->_ENGINE_parse_body($tpl->button_autnonome("{upgrade_all}",
                "Loadjs('fw.netagents.packages.fleet.php?launch-js=yes&action=upgrade-all&group=0')",ico_list));
            $filter="false";
            $colspan=5;
            // La balise s'ecrivait "<div'>" -- une apostrophe egaree dans le nom.
                $html[]="<div>$button_update_all</div>";
            $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text' nowrap></th>";
            $html[]="<th data-sortable=true data-type='text'>Debian</th>";
            $html[]="<th data-sortable=true data-type='text' nowrap>{packages}</th>";
            $html[]="<th data-sortable=true data-type='text' nowrap></th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                $hostname=htmlspecialchars($item->hostname ?? "");
                $dv=intval($item->debian_version ?? 0);
                $cnt=intval($item->upgradable_count ?? 0);
                $agent_id=intval($item->agent_id ?? 0);
                if($item->debian_version==0){
                    continue;
                }
                $hostname=$tpl->td_href("$hostname","","Loadjs('fw.netagents.aptget.upgrade.php?id=$agent_id')");
                $TRCLASS=null;

                $CountLabel=$tpl->td_href(
                    "<span class='badge' style='background:#1ab394;color:#fff;font-size:18px'>$cnt</span>","",
                    "Loadjs('fw.netagents.aptget.upgrade.php?id=$agent_id')");

                $upgJs="Loadjs('fw.netagents.aptget.upgrade.php?upgrade-all-js=yes&id=$agent_id')";
                $upgrade="<button OnClick=\"$upgJs\" class='btn btn-primary' type='button'><i class='$ico_down'></i>&nbsp;{upgrade}</button>";
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='fas fa-server fa-2x'></i>&nbsp;<span style='font-size:18px'>$hostname</span><span id='pkg-result-$agent_id'></span></td>";
                $html[]="<td $tdStyle1><span id='apt-status-$agent_id'></span></td>";
                $html[]="<td $tdStyle1><i class='$icoDeb fa-2x'></i>&nbsp;&nbsp;<span style='font-size:18px'>$dv</span></td>";
                $html[]="<td $tdStyle1>$CountLabel</td>";
                $html[]="<td $tdStyle1><span id='apt-button-$agent_id'>$upgrade</span></td>";
                $agentsjs[]=$agent_id;
                $html[]="</tr>";
            }

            break;

        case "ArticaSP":
            $items=isset($json->ArticaSP)?$json->ArticaSP:[];
            $colspan=4;
            $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text'>{version}</th>";
            $html[]="<th data-sortable=true data-type='text'>{current} SP</th>";
            $html[]="<th data-sortable=true data-type='number'>{available} SP</th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                $TRCLASS=null;
                $hostname=htmlspecialchars($item->hostname ?? "");
                $ver=htmlspecialchars($item->artica_version ?? "");
                $curSP=htmlspecialchars($item->current_service_pack ?? "0");
                $availSP=intval($item->available_service_pack ?? 0);
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='ico ico-artica'></i>&nbsp;$hostname</td>";
                $html[]="<td $tdStyle1>$ver</td>";
                $html[]="<td $tdStyle1>$curSP</td>";
                $html[]="<td $tdStyle1><span class='badge' style='background:#a383d5;color:#fff'>$availSP</span></td>";
                $html[]="</tr>";
            }

            break;

        case "ArticaHotfix":
            $items=isset($json->ArticaHotfix)?$json->ArticaHotfix:[];
            $colspan=4;
            $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text'>{version}</th>";
            $html[]="<th data-sortable=true data-type='text'>{current} Hotfix</th>";
            $html[]="<th data-sortable=true data-type='number'>{available} Hotfixes</th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                $TRCLASS=null;
                $hostname=htmlspecialchars($item->hostname ?? "");
                $ver=htmlspecialchars($item->artica_version ?? "");
                $curHF=htmlspecialchars($item->current_hotfix ?? "0");
                $availHF=intval($item->available_hotfix_count ?? 0);
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='ico ico-artica'></i>&nbsp;$hostname</td>";
                $html[]="<td $tdStyle1>$ver</td>";
                $html[]="<td $tdStyle1>$curHF</td>";
                $html[]="<td $tdStyle1><span class='badge' style='background:#f8ac59;color:#fff'>$availHF</span></td>";
                $html[]="</tr>";
            }

            break;
    }
    // Les quatre branches du switch ouvrent chacune leur tableau et partagent
    // cette fermeture: une seule s'execute par requete. L'ancien pied -- une
    // <ul class='pagination'> dans un <tfoot> -- etait rempli par footable.
    $html[]="</tbody></table></div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    if(count($agentsjs)>0){
        $html[]=$tpl->RefreshInterval_Loadjs("table-upd-$t",$page,"agents-upgrade-list-apt-status=".implode("-",$agentsjs),5);
    }

    $html[]="</script>";

    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
}
function agents_upgrade_list_apt_status(){
    $tpl=new template_admin();
    $agentids=intval($_GET["apt-status"]);
    $ico=ico_refresh_animate;
    $js=array();
    $agents=explode("-",$agentids);
    foreach($agents as $agent_id){
        $agent=new ArticaNetAgents($agent_id);
        $Crustatus=$agent->Status();
        if($Crustatus["aptget"]["apt_running"]){
            $js[]="if( document.getElementById('apt-status-$agent_id') ){";
            $js[]="document.getElementById('apt-status-$agent_id').innerHTML=\"<i class='$ico'></i>\";";
            $js[]="document.getElementById('apt-button-$agent_id').innerHTML=\"\";";
            $js[]="}";
            $tpl->table_form_field_text("{system_upgrade}","{running}",ico_refresh_animate);
            return $tpl;
        }

    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
}

