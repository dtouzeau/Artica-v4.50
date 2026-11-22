<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["zbxv-cfg"])){zbxv_cfg();exit;}

if(isset($_GET["directive-add-js"])){zabbix_directive_add_js();exit;}
if(isset($_GET["directive-add-popup"])){zabbix_directive_add_popup();exit;}
if(isset($_GET["directive-delete-js"])){zabbix_directive_delete_js();exit;}
if(isset($_POST["zbx-directive-key"])){zabbix_directive_save();exit;}
if(isset($_POST["zbx-directive-delete"])){zabbix_directive_delete();exit;}

// Un reglage tenu par Artica Meta est poste sous un autre nom (zbx_meta_ro_field) : le dispatch
// accepte les deux noms.
if(zbx_meta_posted("ZabbixDebugLevel")){Save();exit;}
if(zbx_meta_posted("ZabbixOutGoingInterface")){Save();exit;}
if(zbx_meta_posted("ZabbixPassiveCheckEnabled")){Save();exit;}
if(zbx_meta_posted("ZabbixActiveCheckEnabled")){Save();exit;}

if(isset($_GET["zabbix-status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}



if(isset($_GET["section-outgoing-js"])){section_outgoing_js();exit;}
if(isset($_GET["section-outgoing-popup"])){section_outgoing_popup();exit;}

if(isset($_GET["section-passive-js"])){section_passive_js();exit;}
if(isset($_GET["section-passive-popup"])){section_passive_popup();exit;}

if(isset($_GET["section-active-js"])){section_active_js();exit;}
if(isset($_GET["section-active-popup"])){section_active_popup();exit;}

if(isset($_GET["section-debug-js"])){section_debug_js();exit;}
if(isset($_GET["section-debug-popup"])){section_debug_popup();exit;}

if(isset($_GET["watchdog-js"])){zabbix_watchdog_js();exit;}
if(isset($_GET["watchdog-popup"])){zabbix_watchdog_popup();exit;}
if(isset($_GET["conf-view-js"])){zabbix_conf_view_js();exit;}
if(isset($_GET["conf-view-popup"])){zabbix_conf_view_popup();exit;}

if(isset($_GET["export-metrics"])){zabbix_metrics_export();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZABBIX_AGENT_VERSION");

    $html=$tpl->page_header("{APP_ZABBIX_AGENT} v{$version}",ico_health_check,
        "{monitor_your_system}","$page?tabs=yes","zabbix","progress-zabbix-restart",false,"table-loader-zabbix-pages");

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}
function section_passive_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{passive_checks}","$page?section-passive-popup=yes");

}
function section_active_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{active_checks}","$page?section-active-popup=yes");

}
function section_outgoing_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{outgoing_interface}","$page?section-outgoing-popup=yes");

}
function section_outgoing_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ZabbixOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixOutGoingInterface"));

    $form[]=zbx_meta_fld($tpl,"field_interfaces","ZabbixOutGoingInterface","nooloop:{outgoing_interface}",$ZabbixOutGoingInterface);
    $security="AsSystemAdministrator";
    $html[]=$tpl->div_explain("{zbxv_out_title}||{zbxv_pop_outgoing}");
    $html[]=zbx_meta_form_lock($tpl,array("ZabbixOutGoingInterface"));
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_passive_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ZabbixInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixInterface"));
    $ZabbixPassiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixPassiveCheckEnabled"));
    $ZabbixListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixListenPort"));
    $ZabbixAgentPSK=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixAgentPSK"));
    $ZabbixAgentServerIP=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixAgentServerIP"));
    if($ZabbixListenPort==0){$ZabbixListenPort="10050";}


    // Un reglage tenu par Artica Meta est affiche en lecture seule ; "disable_form" seulement
    // quand la case est modifiable (sinon elle griserait des champs restes libres).
    $form[]=zbx_meta_fld($tpl,"field_checkbox","ZabbixPassiveCheckEnabled","{enable}",$ZabbixPassiveCheckEnabled,!zbx_meta_any_held(array("ZabbixInterface","ZabbixListenPort","ZabbixAgentServerIP","ZabbixAgentPSK")));
    $form[]=zbx_meta_fld($tpl,"field_interfaces","ZabbixInterface","nooloopNoDef:{listen_interface}",$ZabbixInterface);
    $form[]=zbx_meta_fld($tpl,"field_numeric","ZabbixListenPort","{listen_port}",$ZabbixListenPort);
    $form[]=zbx_meta_fld($tpl,"field_text","ZabbixAgentServerIP", "{ZabbixAgentServerIP}", $ZabbixAgentServerIP,false,"{ZabbixAgentServerIP_explain}");
    $form[]=zbx_meta_fld($tpl,"field_checkbox","ZabbixAgentPSK","TLS+PSK",$ZabbixAgentPSK,false,"{tls_psk_zabbix}");


    $security="AsSystemAdministrator";
    $html[]=$tpl->div_explain("{zbxv_passive_title}||{zbxv_pop_passive}");
    $html[]=zbx_meta_form_lock($tpl,array("ZabbixPassiveCheckEnabled","ZabbixInterface","ZabbixListenPort","ZabbixAgentServerIP","ZabbixAgentPSK"));
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function section_debug_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{log_level}","$page?section-debug-popup=yes");

}
function section_debug_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $ZabbixDebugLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixDebugLevel"));
    if($ZabbixDebugLevel==0){$ZabbixDebugLevel=3;}
    $LogLevels[1]="{critical_events}";
    $LogLevels[2]="{errors} {events}";
    $LogLevels[3]="{warning} {events}";
    $LogLevels[4]="{debug_mode}";
    $LogLevels[5]="{verbose_mode} + {debug_mode}";
    $form[]=zbx_meta_fld_hash($tpl,$LogLevels,"ZabbixDebugLevel","{log_level}",$ZabbixDebugLevel);

    $security="AsSystemAdministrator";
    $html[]=$tpl->div_explain("{zbxv_log_title}||{zbxv_pop_debug}");
    $html[]=zbx_meta_form_lock($tpl,array("ZabbixDebugLevel"));
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}



function section_active_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ZabbixActiveCheckEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixActiveCheckEnabled"));
    $ServerActiveList=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ServerActiveList"));
    $ZabbixRefreshActiveChecks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixRefreshActiveChecks"));

    if($ZabbixRefreshActiveChecks==0){$ZabbixRefreshActiveChecks=120;}
    $form[]=zbx_meta_fld($tpl,"field_checkbox","ZabbixActiveCheckEnabled","{enable}",$ZabbixActiveCheckEnabled,!zbx_meta_any_held(array("ServerActiveList","ZabbixRefreshActiveChecks")));
    $form[]=zbx_meta_fld($tpl,"field_text","ServerActiveList", "{ZabbixAgentServerIP}", $ServerActiveList,false,"{ZabbixAgentServerActiveExplain}");
    $form[]=zbx_meta_fld($tpl,"field_numeric","ZabbixRefreshActiveChecks", "{each} ({seconds})", $ZabbixRefreshActiveChecks);

    $security="AsSystemAdministrator";
    $html[]=$tpl->div_explain("{zbxv_active_title}||{zbxv_pop_active}");
    $html[]=zbx_meta_form_lock($tpl,array("ZabbixActiveCheckEnabled","ServerActiveList","ZabbixRefreshActiveChecks"));
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function section_js_restart():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return     $tpl->framework_buildjs("/zabbix/restart",
        "restart-zabbix.progress","restart-zabbix.progress.log","progress-zabbix-restart","LoadAjaxSilent('zabbix-status','$page?zabbix-status=yes');");
}

function section_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsRestart=section_js_restart();
    return "BootstrapDialog1.close();LoadAjax('zabbix-general-table','$page?table1=yes');$jsRestart";
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}/{parameters}"]="$page?table=yes";
    $array["{events}"]="fw.system.zabbix.events.php";
    echo $tpl->tabs_default($array);
}

function table(){
    $page=CurrentPageName();
    echo "<div id='zabbix-general-table'></div><script>LoadAjax('zabbix-general-table','$page?table1=yes')</script>";
}

// ---------------------------------------------------------------------------
// Colonne de droite (maquette stitch_zabbix_agent_admin_console, 2026-11-20).
// La colonne de gauche (#zabbix-status, status(), RefreshInterval_js) est
// partagee et rafraichie a part : elle n'est pas touchee ici.
// Un modele lu UNE fois (zbxv_model) alimente tous les widgets.
// ---------------------------------------------------------------------------

// Grille deux colonnes et classes encore lues par les popups (watchdog, .conf).
function zabbix_cfg_css():string{
	if(isset($GLOBALS["ZBXC_CSS_DONE"])){return "";}
	$GLOBALS["ZBXC_CSS_DONE"]=true;
	return "<style>
.zbxc-grid{display:grid;grid-template-columns:minmax(335px,360px) 1fr;gap:16px;margin-top:15px;align-items:start}
@media (max-width:900px){.zbxc-grid{grid-template-columns:1fr}}
.zbxc-sub{font-size:12px;color:#64748b;margin-top:2px;font-family:ui-monospace,Menlo,Consolas,monospace}
.zbxc-mono{font-family:ui-monospace,Menlo,Consolas,monospace}
</style>";
}

function zbxv_style():string{
	if(isset($GLOBALS["ZBXV_CSS_DONE"])){return "";}
	$GLOBALS["ZBXV_CSS_DONE"]=true;
	$accent="#005447";$dark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$accent=$a["accent"];}
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
		if(!empty($a["accent_line"])){$line=$a["accent_line"];}
	}
	return "<style>
.zbxv{display:flex;flex-direction:column;gap:24px;color:#0f172a;font-size:14px}
.zbxv *{box-sizing:border-box}
.zbxv-hero{display:flex;gap:20px;align-items:flex-start;background:#f2f6f6;border:1px solid $line;border-radius:12px;padding:24px}
.zbxv-hero-ico{flex:0 0 auto;width:48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px}
.zbxv-hero h2{font-size:20px;font-weight:600;margin:0 0 6px;color:#0f172a}
.zbxv-hero p{font-size:14px;line-height:1.6;color:#334155;margin:0}
.zbxv-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.zbxv-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:500;border:1px solid #e2e8f0;background:#fff;color:#334155}
.zbxv-chip-ok{border-color:$line;color:$dark}
.zbxv-chip-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.zbxv-health{display:flex;align-items:center;flex-wrap:wrap;gap:8px 14px;background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--ak-tsolid,#94a3b8);border-radius:12px;padding:16px 20px;font-size:13px;color:#475569}
.zbxv-health-t{font-weight:600;color:var(--ak-tfg,#334155);display:inline-flex;align-items:center;gap:8px}
.zbxv-health .zbxv-sep{color:#cbd5e1}
.zbxv-health b{color:var(--ak-tfg,#0f172a);font-weight:600}
.zbxv-alert{display:flex;gap:16px;align-items:center;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:18px 20px}
.zbxv-alert-ico{font-size:22px;color:#b91c1c;flex:0 0 auto}
.zbxv-alert-t{font-weight:600;color:#991b1b;font-size:15px}
.zbxv-alert-x{color:#7f1d1d;font-size:13px;margin-top:2px}
.zbxv-alert .zbxv-grow{flex:1 1 auto}
.zbxv-sec-h{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:12px}
.zbxv-sec-h h3{font-size:16px;font-weight:600;margin:0;color:#0f172a}
.zbxv-sec-h p{font-size:13px;color:#64748b;margin:4px 0 0;line-height:1.5}
.zbxv-conn{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media (max-width:1200px){.zbxv-conn{grid-template-columns:1fr}}
.zbxv-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;display:flex;flex-direction:column;gap:12px;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.zbxv-card-h{display:flex;align-items:flex-start;gap:14px}
.zbxv-card-ico{flex:0 0 auto;width:40px;height:40px;border-radius:10px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:17px}
.zbxv-card-tt{flex:1 1 auto;min-width:0}
.zbxv-card-t{font-size:15px;font-weight:600;color:#0f172a;line-height:1.3}
.zbxv-card-s{font-size:13px;color:#64748b;margin-top:2px}
.zbxv-txt{font-size:13px;line-height:1.6;color:#334155;margin:0}
.zbxv-ex{font-size:13px;color:#64748b;font-style:italic}
.zbxv-kv{background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:4px 14px}
.zbxv-kv-r{display:flex;align-items:baseline;justify-content:space-between;gap:16px;padding:8px 0;border-top:1px solid #eef2f6}
.zbxv-kv-r:first-child{border-top:0}
.zbxv-kv-l{font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;flex:0 0 auto}
.zbxv-kv-v{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#0f172a;text-align:right;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.zbxv-card-f{display:flex;justify-content:flex-end;align-items:center;gap:12px;margin-top:auto;padding-top:4px}
.zbxv-card-f .zbxv-note{margin-right:auto}
.zbxv-note{font-size:13px;color:#64748b}
.zbxv-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:500;white-space:nowrap;background:var(--ak-tbg,#f1f5f9);color:var(--ak-tfg,#475569)}
.zbxv-badge.ak-ok{background:#ecfdf5;color:#047857}
.zbxv-btn{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 14px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#0f172a;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none!important}
.zbxv-btn:hover{border-color:$line;background:$soft;color:$dark}
.zbxv-btn:focus-visible,.zbxv-link:focus-visible,.zbxv-mini:focus-visible{outline:2px solid $accent;outline-offset:2px}
.zbxv-btn-p{background:$accent;border-color:$accent;color:#fff!important}
.zbxv-btn-p:hover{background:$dark;border-color:$dark;color:#fff!important}
.zbxv-btn[disabled]{opacity:.55;cursor:not-allowed;background:#f8fafc;color:#64748b}
.zbxv-link{background:none;border:0;padding:4px 2px;color:$accent;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none!important;white-space:nowrap}
.zbxv-link:hover{color:$dark;text-decoration:underline!important}
.zbxv-psk{display:grid;grid-template-columns:1fr 1fr;gap:16px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:14px 16px}
@media (max-width:1200px){.zbxv-psk{grid-template-columns:1fr}}
.zbxv-psk-l{font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:6px}
.zbxv-psk-f{display:flex;gap:6px;align-items:center}
.zbxv-psk-v{flex:1 1 auto;min-width:0;height:34px;display:flex;align-items:center;padding:0 10px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.zbxv-mini{flex:0 0 auto;width:34px;height:34px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#475569;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;justify-content:center}
.zbxv-mini:hover{border-color:$line;background:$soft;color:$dark}
.zbxv-mini-ok{color:#047857;border-color:#a7f3d0}
.zbxv-list{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.zbxv-row{display:flex;align-items:flex-start;gap:16px;padding:18px 24px;border-top:1px solid #f1f5f9}
.zbxv-row:first-child{border-top:0}
.zbxv-row-ico{flex:0 0 auto;width:36px;height:36px;border-radius:10px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center;font-size:15px}
.zbxv-row-ico.ak-warn{background:#fffbeb;color:#b45309}
.zbxv-row-main{flex:1 1 auto;min-width:0}
.zbxv-row-t{font-size:14px;font-weight:600;color:#0f172a;display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
.zbxv-row-k{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;font-weight:400;color:#64748b}
.zbxv-row-x{font-size:13px;color:#475569;line-height:1.5;margin-top:3px}
.zbxv-row-v{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:8px;font-size:13px;color:#64748b}
.zbxv-val{display:inline-block;padding:3px 10px;border-radius:6px;background:#f1f5f9;color:#0f172a;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px}
.zbxv-val-t{font-family:inherit}
.zbxv-row-a{flex:0 0 auto;align-self:center;display:flex;gap:6px}
.zbxv-empty{border:1px dashed #cbd5e1;border-radius:12px;padding:28px 24px;text-align:center;background:#fff}
.zbxv-empty-ico{font-size:28px;color:#94a3b8;margin-bottom:10px}
.zbxv-empty-t{font-size:15px;font-weight:600;color:#0f172a}
.zbxv-empty-x{font-size:13px;color:#64748b;margin:6px auto 14px;max-width:520px;line-height:1.5}
.zbxv-foot{display:flex;justify-content:flex-end;gap:24px;font-size:13px}
@media (prefers-reduced-motion:no-preference){.zbxv-btn,.zbxv-mini,.zbxv-link{transition:background .15s,border-color .15s,color .15s}}
</style>";
}

// Lecture unique des reglages et des sondes du demon.
function zbxv_model():array{
	$s=$GLOBALS["CLASS_SOCKETS"];
	$m=array();
	$m["installed"]=intval($s->GET_INFO("APP_ZABBIX_AGENT_INSTALLED"))<>0;
	$lvl=intval($s->GET_INFO("ZabbixDebugLevel"));
	if($lvl<1 || $lvl>5){$lvl=3;}
	$m["loglevel"]=$lvl;
	$m["outgoing"]=trim($s->GET_INFO("ZabbixOutGoingInterface"));
	$m["passive_on"]=intval($s->GET_INFO("ZabbixPassiveCheckEnabled"))==1;
	$m["passive_srv"]=trim($s->GET_INFO("ZabbixAgentServerIP"));
	$m["passive_iface"]=trim($s->GET_INFO("ZabbixInterface"));
	$port=intval($s->GET_INFO("ZabbixListenPort"));
	if($port==0){$port=10050;}
	$m["passive_port"]=$port;
	$m["active_on"]=intval($s->GET_INFO("ZabbixActiveCheckEnabled"))==1;
	$m["active_list"]=trim($s->GET_INFO("ServerActiveList"));
	$refresh=intval($s->GET_INFO("ZabbixRefreshActiveChecks"));
	if($refresh==0){$refresh=120;}
	// Memes bornes que Build() cote daemon.
	if($refresh<60){$refresh=60;}
	if($refresh>3600){$refresh=3600;}
	$m["active_refresh"]=$refresh;
	$m["psk_on"]=intval($s->GET_INFO("ZabbixAgentPSK"))==1;
	if(!$m["installed"]){return $m;}

	// Sonde active (cachee 30 s cote daemon). "Pas de reponse" n'est pas "desactive".
	$m["probe"]="unknown";$m["probe_addr"]="";$m["probe_rtt"]=0;$m["probe_age"]=0;
	$j=json_decode($s->REST_API("/zabbix/active/check"));
	if(is_object($j) && property_exists($j,"TestedAt")){
		$age=time()-intval($j->TestedAt);
		if($age<0){$age=0;}
		$m["probe_age"]=$age;
		$reason=property_exists($j,"Info")?$j->Info:"";
		if($reason=="active_checks_disabled"){
			$m["probe"]="off";
		}elseif(property_exists($j,"Status") && $j->Status==true){
			$m["probe"]="ok";
			$m["probe_rtt"]=intval(property_exists($j,"RTTms")?$j->RTTms:0);
			$m["probe_addr"]=property_exists($j,"Address")?(string)$j->Address:"";
		}else{
			$m["probe"]="bad";
		}
	}

	// Sonde passive : le daemon tente une connexion locale sur l'ecoute de l'agent.
	$m["passive_probe"]="unknown";
	if($m["passive_on"]){
		$j=json_decode($s->REST_API("/zabbix/passive/check"));
		if(is_object($j) && property_exists($j,"TestedAt")){
			$m["passive_probe"]=(property_exists($j,"Status") && $j->Status==true)?"ok":"bad";
		}
	}

	// PSK : la cle et l'identite existent meme sur un agent v1, ou la
	// configuration generee ne chiffre rien -- IsV2 tranche.
	$m["psk"]=$m["psk_on"]?"unknown":"off";
	$m["psk_ident"]="";$m["psk_key"]="";
	$j=json_decode($s->REST_API("/zabbix/psk"));
	if(is_object($j) && property_exists($j,"IsV2")){
		if($j->IsV2!=true){
			$m["psk"]="v1";
		}elseif($m["psk_on"]){
			$ident=property_exists($j,"TLSPSKIdentity")?trim($j->TLSPSKIdentity):"";
			if(strlen($ident)>0){
				$m["psk"]="enc";
				$m["psk_ident"]=$ident;
				$m["psk_key"]=property_exists($j,"TLSPSKData")?trim($j->TLSPSKData):"";
			}
		}
	}

	// Watchdog : absent = non rapporte, jamais "desarme".
	$m["wd_reported"]=false;$m["wd_armed"]=false;$m["wd_interval"]=null;$m["wd_at"]=null;
	$j=json_decode($s->REST_API("/zabbix/watchdog"));
	if(is_object($j) && property_exists($j,"Armed")){
		$m["wd_reported"]=true;
		$m["wd_armed"]=($j->Armed==true);
		if(property_exists($j,"IntervalSeconds")){$m["wd_interval"]=intval($j->IntervalSeconds);}
		if(property_exists($j,"LastActionAt")){$m["wd_at"]=intval($j->LastActionAt);}
	}

	// Directives personnalisees : null = route muette, array() = aucune.
	$m["directives"]=null;
	$j=json_decode($s->REST_API("/zabbix/directives"));
	if(is_object($j) && property_exists($j,"Directives")){
		$m["directives"]=is_array($j->Directives)?$j->Directives:array();
	}
	return $m;
}

// Traduit d'abord, substitue ensuite : un %s substitue avant le moteur
// laisserait un jeton sans correspondance.
function zbxv_tr(string $token,$value):string{
	$tpl=new template_admin();
	return str_replace("%s",$value,$tpl->_ENGINE_parse_body("{".$token."}"));
}

function zbxv_age(int $sec):string{
	if($sec<60){return "$sec s";}
	if($sec<3600){return intval($sec/60)." min";}
	if($sec<86400){return intval($sec/3600)." h";}
	return intval($sec/86400)." d";
}

function zbxv_badge(string $tone,string $ico,string $label):string{
	return "<span class='zbxv-badge ak-$tone'><i class='$ico' aria-hidden='true'></i>$label</span>";
}

function zbxv_edit_btn(string $js,string $label="{zbxv_edit}",string $ico="fa-solid fa-pen"):string{
	return "<button type='button' class='zbxv-btn' OnClick=\"$js\"><i class='$ico' aria-hidden='true'></i>$label</button>";
}

function zbxv_hero($m):string{
	$chips=array();
	if($m["installed"]){
		if($m["wd_armed"]){
			$chips[]="<span class='zbxv-chip zbxv-chip-ok'><i class='fa-solid fa-rotate' aria-hidden='true'></i>{zbxv_chip_watchdog}</span>";
		}
		if($m["psk"]=="enc"){
			$chips[]="<span class='zbxv-chip zbxv-chip-ok'><i class='fa-solid fa-lock' aria-hidden='true'></i>{zbxv_chip_encrypted}</span>";
		}elseif($m["psk"]=="off" || $m["psk"]=="v1"){
			$chips[]="<span class='zbxv-chip zbxv-chip-warn'><i class='fa-solid fa-lock-open' aria-hidden='true'></i>{zbxv_chip_unencrypted}</span>";
		}
		$chips[]="<span class='zbxv-chip'><i class='fa-solid fa-shield-halved' aria-hidden='true'></i>{zbxv_chip_restrict}</span>";
	}
	$c=count($chips)>0?"<div class='zbxv-chips'>".@implode("",$chips)."</div>":"";
	return "<div class='zbxv-hero'>
	<div class='zbxv-hero-ico'><i class='fa-solid fa-heart-pulse' aria-hidden='true'></i></div>
	<div><h2>{zbxv_hero_title}</h2><p>{zbxv_hero_text}</p>$c</div>
	</div>";
}

// Bandeau de sante : la seule mesure vivante est la derniere sonde active.
function zbxv_health($m):string{
	$page=CurrentPageName();
	if(!$m["active_on"] && !$m["passive_on"]){
		return "<div class='zbxv-alert' role='alert'>
		<i class='fa-solid fa-circle-exclamation zbxv-alert-ico' aria-hidden='true'></i>
		<div class='zbxv-grow'><div class='zbxv-alert-t'>{zbxv_none_title}</div><div class='zbxv-alert-x'>{err_active_passive}</div></div>
		<button type='button' class='zbxv-btn zbxv-btn-p' OnClick=\"Loadjs('$page?section-active-js=yes')\"><i class='fa-solid fa-paper-plane' aria-hidden='true'></i>{zbxv_turn_on_active}</button>
		</div>";
	}
	$checked=zbxv_tr("zbxv_checked_ago",zbxv_age($m["probe_age"]));
	$sep="<span class='zbxv-sep' aria-hidden='true'>&middot;</span>";
	switch($m["probe"]){
		case "ok":
			$addr=htmlspecialchars($m["probe_addr"]);
			$rtt=zbxv_tr("zbxv_round_trip","<b>".intval($m["probe_rtt"])." ms</b>");
			return "<div class='zbxv-health ak-ok'><span class='zbxv-health-t'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{zbxv_server_reached}</span>$sep<span class='zbxv-val'>$addr</span>$sep<span>$checked</span>$sep<span>$rtt</span></div>";
		case "bad":
			return "<div class='zbxv-health ak-err'><span class='zbxv-health-t'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{zbx_probe_unreachable}</span>$sep<span>$checked</span>
			<span style='margin-left:auto'>".zbxv_edit_btn("Loadjs('$page?section-active-js=yes')","{zbxv_edit_active}")."</span></div>";
		case "off":
			return "<div class='zbxv-health ak-off'><span class='zbxv-health-t'><i class='fa-solid fa-circle-minus' aria-hidden='true'></i>{zbxv_sending_off}</span></div>";
	}
	return "<div class='zbxv-health ak-off'><span class='zbxv-health-t'><i class='fa-solid fa-circle-question' aria-hidden='true'></i>{zbx_not_reported}</span></div>";
}

function zbxv_kv(array $rows):string{
	$o=array();
	foreach($rows as $r){
		$o[]="<div class='zbxv-kv-r'><span class='zbxv-kv-l'>{$r[0]}</span><span class='zbxv-kv-v' title=\"".htmlspecialchars(strip_tags($r[1]))."\">{$r[1]}</span></div>";
	}
	return "<div class='zbxv-kv'>".@implode("",$o)."</div>";
}

function zbxv_card_active($m):string{
	$page=CurrentPageName();
	if(!$m["active_on"]){
		$badge=zbxv_badge("off","fa-solid fa-circle-minus","{zbxv_badge_off}");
	}elseif($m["probe"]=="ok"){
		$badge=zbxv_badge("ok","fa-solid fa-circle-check","{zbxv_badge_connected}");
	}elseif($m["probe"]=="bad"){
		$badge=zbxv_badge("err","fa-solid fa-circle-xmark","{zbxv_badge_unreachable}");
	}else{
		$badge=zbxv_badge("off","fa-solid fa-circle-question","{zbxv_badge_unknown}");
	}
	$list=strlen($m["active_list"])>0?htmlspecialchars(str_replace(",",", ",$m["active_list"])):"{zbxv_none}";
	$kv=zbxv_kv(array(
		array("{zbxv_lbl_server}",$list.zbx_meta_tag("ServerActiveList")),
		array("{zbxv_lbl_refresh}",zbxv_tr("zbxv_every_s",$m["active_refresh"]).zbx_meta_tag("ZabbixRefreshActiveChecks")),
	));
	$badge.=zbx_meta_tag("ZabbixActiveCheckEnabled");
	return "<div class='zbxv-card'>
	<div class='zbxv-card-h'><div class='zbxv-card-ico'><i class='fa-solid fa-paper-plane' aria-hidden='true'></i></div>
	<div class='zbxv-card-tt'><div class='zbxv-card-t'>{zbxv_active_title}</div><div class='zbxv-card-s'>{active_checks}</div></div>$badge</div>
	<p class='zbxv-txt'>{zbxv_active_text}</p>
	<div class='zbxv-ex'>{zbxv_active_example}</div>
	$kv
	<div class='zbxv-card-f'>".zbxv_edit_btn("Loadjs('$page?section-active-js=yes')")."</div>
	</div>";
}

function zbxv_card_passive($m):string{
	$page=CurrentPageName();
	if(!$m["passive_on"]){
		$badge=zbxv_badge("off","fa-solid fa-circle-minus","{zbxv_badge_off}");
	}elseif($m["passive_probe"]=="ok"){
		$badge=zbxv_badge("ok","fa-solid fa-circle-check","{zbxv_badge_listening}");
	}elseif($m["passive_probe"]=="bad"){
		$badge=zbxv_badge("err","fa-solid fa-circle-xmark","{zbxv_badge_not_answering}");
	}else{
		$badge=zbxv_badge("off","fa-solid fa-circle-question","{zbxv_badge_unknown}");
	}
	// Interface vide : Build() ecrit ListenIP=0.0.0.0, donc toutes les interfaces.
	$iface=strlen($m["passive_iface"])>0?htmlspecialchars($m["passive_iface"]):"{zbxv_all_ifaces}";
	$srv=strlen($m["passive_srv"])>0?htmlspecialchars(str_replace(",",", ",$m["passive_srv"])):"{zbxv_none}";
	$kv=zbxv_kv(array(
		array("{zbxv_lbl_listen}","$iface : ".intval($m["passive_port"]).zbx_meta_tag("ZabbixInterface").zbx_meta_tag("ZabbixListenPort")),
		array("{zbxv_lbl_allowed}",$srv.zbx_meta_tag("ZabbixAgentServerIP")),
	));
	$badge.=zbx_meta_tag("ZabbixPassiveCheckEnabled");
	return "<div class='zbxv-card'>
	<div class='zbxv-card-h'><div class='zbxv-card-ico'><i class='fa-solid fa-arrow-right-to-bracket' aria-hidden='true'></i></div>
	<div class='zbxv-card-tt'><div class='zbxv-card-t'>{zbxv_passive_title}</div><div class='zbxv-card-s'>{passive_checks}</div></div>$badge</div>
	<p class='zbxv-txt'>{zbxv_passive_text}</p>
	<div class='zbxv-ex'>{zbxv_passive_example}</div>
	$kv
	<div class='zbxv-card-f'>".zbxv_edit_btn("Loadjs('$page?section-passive-js=yes')")."</div>
	</div>";
}

function zbxv_connection($m):string{
	return "<div><div class='zbxv-sec-h'><div><h3>{zbxv_sec_connection}</h3><p>{zbxv_sec_connection_text}</p></div></div>
	<div class='zbxv-conn'>".zbxv_card_active($m).zbxv_card_passive($m)."</div></div>";
}

function zbxv_psk_field(string $id,string $label,string $shown,string $secret):string{
	// Le secret voyage dans un attribut data (echappe) ; reveler/masquer et
	// copier se font cote navigateur, sans nouvel appel.
	$data=htmlspecialchars($secret,ENT_QUOTES);
	$reveal="";
	if($shown!==htmlspecialchars($secret)){
		$reveal="<button type='button' class='zbxv-mini' aria-label=\"{zbx_reveal_key}\" title=\"{zbx_reveal_key}\" OnClick=\"ZbxvReveal('$id',this)\"><i class='fa-solid fa-eye' aria-hidden='true'></i></button>";
	}
	return "<div><div class='zbxv-psk-l'>$label</div><div class='zbxv-psk-f'>
	<span class='zbxv-psk-v' id='$id' data-secret=\"$data\" data-mask=\"".htmlspecialchars($shown,ENT_QUOTES)."\">$shown</span>$reveal
	<button type='button' class='zbxv-mini' aria-label=\"{zbxv_copy}\" title=\"{zbxv_copy}\" OnClick=\"ZbxvCopy('$id',this)\"><i class='fa-regular fa-copy' aria-hidden='true'></i></button>
	</div></div>";
}

function zbxv_card_psk($m):string{
	$page=CurrentPageName();
	$open="Loadjs('$page?section-passive-js=yes')";
	$body="";$foot="";
	switch($m["psk"]){
		case "enc":
			$badge=zbxv_badge("ok","fa-solid fa-lock","{zbxv_badge_encrypted}");
			$body="<div class='zbxv-psk'>".zbxv_psk_field("zbxv-psk-id","{zbxv_psk_identity}",htmlspecialchars($m["psk_ident"]),$m["psk_ident"])
				.zbxv_psk_field("zbxv-psk-key","{zbxv_psk_key}",str_repeat("&bull;",24),$m["psk_key"])."</div>";
			$foot="<span class='zbxv-note'>{zbxv_psk_hint}</span><button type='button' class='zbxv-link' OnClick=\"$open\">{zbxv_edit_credentials}</button>";
			break;
		case "v1":
			$badge=zbxv_badge("off","fa-solid fa-lock-open","{zbxv_badge_needs_v2}");
			$body="<p class='zbxv-txt'>{zbx_psk_v2_only}</p>";
			$foot="<button type='button' class='zbxv-btn' disabled title=\"{zbx_psk_v2_only}\"><i class='fa-solid fa-lock' aria-hidden='true'></i>{zbxv_turn_on_psk}</button>";
			break;
		case "off":
			$badge=zbxv_badge("warn","fa-solid fa-lock-open","{zbxv_badge_unencrypted}");
			$body="<p class='zbxv-txt'>{zbxv_psk_off_text}</p>";
			$foot="<button type='button' class='zbxv-btn zbxv-btn-p' OnClick=\"$open\"><i class='fa-solid fa-lock' aria-hidden='true'></i>{zbxv_turn_on_psk}</button>";
			break;
		default:
			$badge=zbxv_badge("off","fa-solid fa-circle-question","{zbxv_badge_unknown}");
			$body="<p class='zbxv-txt'>{zbx_not_reported}</p>";
			$foot=zbxv_edit_btn($open);
	}
	$badge.=zbx_meta_tag("ZabbixAgentPSK");
	return "<div class='zbxv-card'>
	<div class='zbxv-card-h'><div class='zbxv-card-ico'><i class='fa-solid fa-lock' aria-hidden='true'></i></div>
	<div class='zbxv-card-tt'><div class='zbxv-card-t'>{zbxv_psk_title}</div><div class='zbxv-card-s'>TLS + PSK (pre-shared key)</div></div>$badge</div>
	<p class='zbxv-txt'>{zbxv_psk_text}</p>
	$body
	<div class='zbxv-card-f'>$foot</div>
	</div>";
}

function zbxv_row(string $ico,string $tone,string $title,string $key,string $explain,string $values,string $action):string{
	$t=$tone<>""?" ak-$tone":"";
	$k=$key<>""?"<span class='zbxv-row-k'>".htmlspecialchars($key)."</span>":"";
	return "<div class='zbxv-row'><div class='zbxv-row-ico$t'><i class='$ico' aria-hidden='true'></i></div>
	<div class='zbxv-row-main'><div class='zbxv-row-t'>$title$k</div><div class='zbxv-row-x'>$explain</div><div class='zbxv-row-v'>$values</div></div>
	<div class='zbxv-row-a'>$action</div></div>";
}

function zbxv_row_watchdog($m):string{
	$page=CurrentPageName();
	$explain=($m["wd_interval"]===null || $m["wd_interval"]<=0)?"{zbx_watchdog_explain}":zbxv_tr("zbxv_wd_every",intval($m["wd_interval"]));
	if(!$m["wd_reported"]){
		$v=zbxv_badge("off","fa-solid fa-circle-question","{zbx_not_reported}");
	}elseif($m["wd_armed"]){
		$v=zbxv_badge("ok","fa-solid fa-circle-check","{zbxv_badge_armed}");
	}else{
		$v=zbxv_badge("off","fa-solid fa-circle-minus","{zbxv_badge_disarmed}");
	}
	// L'horodatage vit dans un tmpfs : "depuis le dernier demarrage".
	if($m["wd_at"]!==null){
		if($m["wd_at"]>0){
			$v.="<span title=\"".date("Y-m-d H:i:s",$m["wd_at"])."\">".zbxv_tr("zbxv_wd_last",date("Y-m-d H:i",$m["wd_at"]))."</span>";
		}else{
			$v.="<span>{zbx_watchdog_never}</span>";
		}
	}
	return zbxv_row("fa-solid fa-rotate","","{zbxv_wd_title}","Watchdog",$explain,$v,
		"<button type='button' class='zbxv-link' OnClick=\"Loadjs('$page?watchdog-js=yes')\">{zbxv_details}</button>");
}

function zbxv_row_outgoing($m):string{
	$page=CurrentPageName();
	$v=strlen($m["outgoing"])>0?"<span class='zbxv-val'>".htmlspecialchars($m["outgoing"])."</span>":"<span class='zbxv-val zbxv-val-t'>{zbxv_default}</span>";
	$v.=zbx_meta_tag("ZabbixOutGoingInterface");
	return zbxv_row("fa-solid fa-network-wired","","{zbxv_out_title}","SourceIP","{zbxv_out_text}",$v,
		"<button type='button' class='zbxv-link' OnClick=\"Loadjs('$page?section-outgoing-js=yes')\">{zbxv_change}</button>");
}

function zbxv_row_loglevel($m):string{
	$page=CurrentPageName();
	$labels=array(1=>"{critical_events}",2=>"{zbxv_lvl_errors}",3=>"{zbxv_lvl_warnings}",4=>"{debug_mode}",5=>"{zbxv_lvl_verbose}");
	$lvl=$m["loglevel"];
	$tone="";$explain="{zbxv_log_text}";
	$v="<span class='zbxv-val zbxv-val-t'>{$labels[$lvl]}</span>";
	if($lvl>=4){
		$tone="warn";
		$explain="{zbxv_log_debug_warn}";
		$v=zbxv_badge("warn","fa-solid fa-triangle-exclamation",$labels[$lvl]);
	}
	$v.=zbx_meta_tag("ZabbixDebugLevel");
	return zbxv_row("fa-solid fa-file-lines",$tone,"{zbxv_log_title}","LogLevel = $lvl",$explain,$v,
		"<button type='button' class='zbxv-link' OnClick=\"Loadjs('$page?section-debug-js=yes')\">{zbxv_change}</button>");
}

function zbxv_tuning($m):string{
	return "<div><div class='zbxv-sec-h'><div><h3>{zbxv_sec_tuning}</h3></div></div>
	<div class='zbxv-list'>".zbxv_row_watchdog($m).zbxv_row_outgoing($m).zbxv_row_loglevel($m)."</div></div>";
}

// Explication d'une cle de la liste blanche (jeton zbx_dir_<cle> que le
// demon renvoie aussi dans Allowed[].Explain).
function zbxv_directive_explain(string $key):string{
	$known=array("listenbacklog","hostinterface","denykey","alias","heartbeatfrequency","statusport","plugintimeout");
	$k=strtolower($key);
	if(!in_array($k,$known)){return "";}
	return "{zbx_dir_$k}";
}

function zbxv_directive_row($d):string{
	$page=CurrentPageName();
	$id=(string)$d->Id;
	if(!preg_match('/^[0-9a-f]{12}$/',$id)){$id="";}
	$key=(string)$d->Key;
	$val=(string)$d->Value;
	if(!empty($d->meta)){
		// Directive ecrite par Artica Meta : lecture seule, aucune action.
		return zbxv_row("fa-solid fa-lock","",
			"<span class='zbxv-val'>".htmlspecialchars("$key = $val")."</span>","",zbxv_directive_explain($key),
			zbxv_badge("ok","fa-solid fa-lock","Artica Meta"),"");
	}
	if(!empty($d->masked)){
		$v=zbxv_badge("warn","fa-solid fa-eye-slash","{zbx_meta_masked}");
		$tone="warn";
	}elseif($d->Shadowed==true){
		$v=zbxv_badge("warn","fa-solid fa-triangle-exclamation","{zbx_directive_shadowed}");
		$tone="warn";
	}else{
		$v=zbxv_badge("ok","fa-solid fa-circle-check","{zbxv_badge_applied}");
		$tone="";
	}
	$act="<button type='button' class='zbxv-mini' aria-label=\"{zbxv_edit}\" title=\"{zbxv_edit}\" OnClick=\"Loadjs('$page?directive-add-js=yes')\"><i class='fa-solid fa-pen' aria-hidden='true'></i></button>";
	if($id<>""){
		$act.="<button type='button' class='zbxv-mini' aria-label=\"{zbxv_delete}\" title=\"{zbxv_delete}\" OnClick=\"Loadjs('$page?directive-delete-js=$id')\"><i class='fa-solid fa-trash' aria-hidden='true'></i></button>";
	}
	return zbxv_row("fa-solid fa-sliders",$tone,"<span class='zbxv-val'>".htmlspecialchars("$key = $val")."</span>","",
		zbxv_directive_explain($key),$v,$act);
}

function zbxv_directives($m):string{
	$page=CurrentPageName();
	$add="<button type='button' class='zbxv-btn' OnClick=\"Loadjs('$page?directive-add-js=yes')\"><i class='fa-solid fa-plus' aria-hidden='true'></i>{zbx_add_directive}</button>";
	$head="<div class='zbxv-sec-h'><div><h3>{zbxv_sec_advanced}</h3><p>{zbxv_sec_advanced_text}</p></div>$add</div>";
	if($m["directives"]===null){
		return "<div>$head<div class='zbxv-list'><div class='zbxv-row'><span class='zbxv-note'>{zbx_not_reported}</span></div></div></div>";
	}
	if(count($m["directives"])==0){
		return "<div>$head<div class='zbxv-empty'><div class='zbxv-empty-ico'><i class='fa-solid fa-sliders' aria-hidden='true'></i></div>
		<div class='zbxv-empty-t'>{zbxv_dir_empty_title}</div><div class='zbxv-empty-x'>{zbxv_dir_empty_text}</div>
		<button type='button' class='zbxv-btn zbxv-btn-p' OnClick=\"Loadjs('$page?directive-add-js=yes')\"><i class='fa-solid fa-plus' aria-hidden='true'></i>{zbx_add_directive}</button></div></div>";
	}
	// Directives d'Artica Meta a part, en lecture seule ; les locales masquees restent dans la liste locale.
	$rows=array();$meta=array();
	foreach($m["directives"] as $d){
		if(!empty($d->meta)){$meta[]=zbxv_directive_row($d);continue;}
		$rows[]=zbxv_directive_row($d);
	}
	$out="<div>$head";
	if(count($rows)>0){
		$out.="<div class='zbxv-list'>".@implode("",$rows)."</div>";
	}elseif(count($meta)>0){
		$out.="<div class='zbxv-list'><div class='zbxv-row'><span class='zbxv-note'>{zbxv_dir_empty_title}</span></div></div>";
	}
	$out.="</div>";
	if(count($meta)>0){
		$out.="<div><div class='zbxv-sec-h'><div><h3><i class='fa-solid fa-lock' aria-hidden='true'></i> {zbx_meta_dir_title}</h3><p>{zbx_meta_dir_text}</p></div></div>
		<div class='zbxv-list'>".@implode("",$meta)."</div></div>";
	}
	return $out;
}

function zbxv_foot():string{
	$page=CurrentPageName();
	return "<div class='zbxv-foot'>
	<button type='button' class='zbxv-link' OnClick=\"Loadjs('$page?conf-view-js=yes')\"><i class='fa-solid fa-file-code' aria-hidden='true'></i> {zbx_view_raw_conf}</button>
	<a class='zbxv-link' href='$page?export-metrics=yes'><i class='fa-solid fa-download' aria-hidden='true'></i> {zbx_export_metrics}</a>
	</div>";
}

function zbxv_not_installed():string{
	return "<div class='zbxv-empty'><div class='zbxv-empty-ico'><i class='fa-solid fa-box-open' aria-hidden='true'></i></div>
	<div class='zbxv-empty-t'>{zbxv_ni_title}</div><div class='zbxv-empty-x'>{zbx_not_installed} {zbxv_ni_text}</div></div>";
}

// Copier / reveler et rafraichissement de la colonne de droite. La sonde est
// cachee 30 s par le demon : un rechargement par minute suffit. Saute quand
// une modale ou une SweetAlert est ouverte, s'arrete quand l'hote disparait.
function zbxv_js():string{
	$page=CurrentPageName();
	return "<script>
function ZbxvReveal(id,b){var e=document.getElementById(id);if(!e){return;}
 if(e.getAttribute('data-shown')==='1'){e.innerHTML=e.getAttribute('data-mask');e.setAttribute('data-shown','0');}
 else{e.textContent=e.getAttribute('data-secret');e.setAttribute('data-shown','1');}}
function ZbxvCopied(b){if(!b){return;}b.classList.add('zbxv-mini-ok');setTimeout(function(){b.classList.remove('zbxv-mini-ok');},1500);}
function ZbxvCopy(id,b){var e=document.getElementById(id);if(!e){return;}var t=e.getAttribute('data-secret')||'';
 if(navigator.clipboard && window.isSecureContext){navigator.clipboard.writeText(t).then(function(){ZbxvCopied(b);});return;}
 var a=document.createElement('textarea');a.value=t;a.style.position='fixed';a.style.opacity='0';document.body.appendChild(a);a.select();
 try{document.execCommand('copy');ZbxvCopied(b);}catch(x){}document.body.removeChild(a);}
if(window.ZbxvTimer){clearTimeout(window.ZbxvTimer);}
function ZbxvTick(){
 if(!document.getElementById('zbxv-cfg')){window.ZbxvTimer=null;return;}
 var busy=document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.sweet-overlay[style*=block]');
 if(!busy){LoadAjaxSilent('zbxv-cfg','$page?zbxv-cfg=yes');return;}
 window.ZbxvTimer=setTimeout(ZbxvTick,60000);}
window.ZbxvTimer=setTimeout(ZbxvTick,60000);
</script>";
}

// Contenu de la colonne de droite (premier rendu et rafraichissement).
function zbxv_content():string{
	$tpl=new template_admin();
	$m=zbxv_model();
	$w=array();
	$w[]=zbxv_hero($m);
	$w[]=zbx_meta_banner();
	if(!$m["installed"]){
		$w[]=zbxv_not_installed();
	}else{
		$w[]=zbxv_health($m);
		$w[]=zbxv_connection($m);
		$w[]=zbxv_card_psk($m);
		$w[]=zbxv_tuning($m);
		$w[]=zbxv_directives($m);
		$w[]=zbxv_foot();
	}
	return zbxv_style().$tpl->_ENGINE_parse_body("<div class='zbxv'>".@implode("\n",$w)."</div>").zbxv_js();
}

function zbxv_cfg(){
	echo zbxv_content();
}

// Task 8: garde de privilege serveur. Passer un privilege a form_outside ne
// controle que l'affichage: le bloc de dispatch en tete de page repond
// toujours a n'importe quelle session authentifiee, d'ou l'appel explicite
// dans chaque handler de mutation.
function zabbix_require_admin():bool{
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		header("HTTP/1.1 403 Forbidden");
		echo "Access denied";
		return false;
	}
	return true;
}

function zabbix_directive_add_js():bool{
	$tpl=new template_admin();$page=CurrentPageName();
	return $tpl->js_dialog2("{zbx_add_directive}","$page?directive-add-popup=yes",600);
}

function zabbix_directive_add_popup():bool{
	$tpl=new template_admin();
	$j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/zabbix/directives"));
	// "La route n'a rien renvoye" (daemon muet, route absente) n'est pas
	// "la fonction a repondu et il ne reste rien a proposer" -- ce sont deux
	// causes distinctes du meme ecran vide.
	if(!is_object($j) || !property_exists($j,"Allowed")){
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("{zbx_not_reported}"));return true;
	}
	if(!is_array($j->Allowed) || count($j->Allowed)==0){
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("{zbx_no_directive_available}"));return true;
	}
	$hash=array();
	$help=array();
	foreach($j->Allowed as $spec){
		$hash[$spec->Key]=$spec->Key;
		// Explain est un jeton zbx_dir_<cle> renvoye par le demon : on ne
		// l'interpole que s'il a cette forme.
		$ex=property_exists($spec,"Explain")?(string)$spec->Explain:"";
		if(!preg_match('/^zbx_dir_[a-z0-9]+$/',$ex)){$ex="";}
		$help[]="<li style='margin-bottom:4px'><span class='zbxc-mono' style='font-weight:600'>".htmlspecialchars($spec->Key)."</span>".($ex<>""?" &mdash; {".$ex."}":"")."</li>";
	}
	$intro=$tpl->div_explain("{zbx_add_directive}||{zbxv_dir_pop}<ul style='margin:8px 0 0;padding-left:18px'>".@implode("",$help)."</ul>");
	echo zabbix_cfg_css().$tpl->_ENGINE_parse_body($intro);
	$form=array();
	$form[]=$tpl->field_array_hash($hash,"zbx-directive-key","{zbx_directive_key}",null,true);
	$form[]=$tpl->field_text("zbx-directive-value","{zbx_directive_value}","",true);
	$after="dialogInstance2.close();LoadAjax('zabbix-general-table','".CurrentPageName()."?table1=yes');".section_js_restart();
	echo $tpl->_ENGINE_parse_body($tpl->form_outside(null,@implode("\n",$form),null,"{apply}",$after,"AsSystemAdministrator"));
	return true;
}

function zabbix_directive_save(){
	if(!zabbix_require_admin()){return;}
	$tpl=new template_admin();
	$key=trim($_POST["zbx-directive-key"]);
	$value=trim($_POST["zbx-directive-value"]);
	$payload=array("Key"=>$key,"Value"=>$value);
	$j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/zabbix/directives",$payload));
	if(!is_object($j) || $j->Status!=true){
		$err="{error}";
		if(is_object($j) && property_exists($j,"Error")){$err=$j->Error;}
		// $err peut rester le jeton "{error}" (aucune erreur exploitable
		// rendue par le daemon): sans passage par _ENGINE_parse_body(),
		// l'alerte affichait les accolades litterales.
		echo $tpl->_ENGINE_parse_body($err);   // seule l'erreur est echo'ee
		return;
	}
	admin_tracks_post("Zabbix: added directive $key");
	// succes: RIEN n'est echo'e
}

function zabbix_directive_delete_js(){
	if(!zabbix_require_admin()){return;}
	// Contrat strict cote daemon: sha256(cle+"="+valeur) tronque a 12 hex.
	// js_confirm_delete() interpole $id BRUT dans une chaine JS a guillemets
	// simples (XHR.appendData('$field','$value')) evaluee via $.globalEval() -
	// une liste blanche de format est ici plus sure qu'un echappement HTML,
	// qui ne protege rien dans un corps de script.
	$id=$_GET["directive-delete-js"];
	if(!is_string($id) || !preg_match('/^[0-9a-f]{12}$/',$id)){exit;}
	$tpl=new template_admin();
	$after="LoadAjax('zabbix-general-table','".CurrentPageName()."?table1=yes');".section_js_restart();
	$tpl->js_confirm_delete("{zbx_custom_directive}","zbx-directive-delete",$id,$after);
}

function zabbix_directive_delete(){
	if(!zabbix_require_admin()){return;}
	$tpl=new template_admin();
	// La validation de format (contrat cote daemon: sha256(cle+"="+valeur)
	// tronque a 12 hex) etait posee sur zabbix_directive_delete_js(), la
	// porte qui OUVRE la confirmation -- pas sur celle qui EXECUTE la
	// suppression. Pas d'elevation de privilege (la garde admin s'execute
	// avant), mais la garde de forme etait sur la mauvaise porte.
	$id=trim($_POST["zbx-directive-delete"]);
	if(!is_string($id) || !preg_match('/^[0-9a-f]{12}$/',$id)){
		echo $tpl->_ENGINE_parse_body("{error}");
		return;
	}
	$j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/zabbix/directives/".urlencode($id)));
	if(!is_object($j) || $j->Status!=true){
		$err="{error}";
		if(is_object($j) && property_exists($j,"Error")){$err=$j->Error;}
		// meme raison qu'en sauvegarde: le jeton doit passer par le moteur
		// de traduction avant d'etre echo'e.
		echo $tpl->_ENGINE_parse_body($err);
		return;
	}
	admin_tracks_post("Zabbix: deleted directive $id");
}

function zabbix_watchdog_js():bool{
	$tpl=new template_admin();$page=CurrentPageName();
	return $tpl->js_dialog2("{zbx_watchdog}","$page?watchdog-popup=yes",620);
}
function zabbix_watchdog_popup():bool{
	$tpl=new template_admin();
	$j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/zabbix/watchdog"));
	if(!is_object($j) || !property_exists($j,"Armed")){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return true;
	}
	$armed=($j->Armed==true)?1:0;
	$tpl->table_form_field_bool("{zbx_watchdog_armed}",$armed,"fas fa-rotate");
	// IntervalSeconds: absent → {zbx_not_reported}; present → affiche la valeur
	if(property_exists($j,"IntervalSeconds")){
		$tpl->table_form_field_text("{each} ({seconds})",intval($j->IntervalSeconds),"fas fa-clock");
	}else{
		$tpl->table_form_field_text("{each} ({seconds})","{zbx_not_reported}","fas fa-clock");
	}
	// SystemdRestart: absent → {zbx_not_reported}; present (even empty) → htmlspecialchars value
	if(property_exists($j,"SystemdRestart")){
		$tpl->table_form_field_text("systemd Restart=",htmlspecialchars($j->SystemdRestart),"fas fa-gear");
	}else{
		$tpl->table_form_field_text("systemd Restart=","{zbx_not_reported}","fas fa-gear");
	}
	// SystemdUnit: absent → {zbx_not_reported}; present (even empty) → htmlspecialchars value
	if(property_exists($j,"SystemdUnit")){
		$tpl->table_form_field_text("Unit",htmlspecialchars($j->SystemdUnit),"fas fa-file-lines");
	}else{
		$tpl->table_form_field_text("Unit","{zbx_not_reported}","fas fa-file-lines");
	}
	// LastActionAt: absent → {zbx_not_reported}; present and 0 → {zbx_watchdog_never}
	if(!property_exists($j,"LastActionAt")){
		$tpl->table_form_field_text("{date}","{zbx_not_reported}","fas fa-clock-rotate-left");
	}else{
		$at=intval($j->LastActionAt);
		if($at>0){
			$actionText=property_exists($j,"LastActionText")?$j->LastActionText:"";
			$tpl->table_form_field_text("{date}",date("Y-m-d H:i:s",$at)." &mdash; <span class='zbxc-mono'>".htmlspecialchars($actionText)."</span>","fas fa-clock-rotate-left");
		}else{
			$tpl->table_form_field_text("{date}","{zbx_watchdog_never}","fas fa-clock-rotate-left");
		}
	}
	echo zabbix_cfg_css().$tpl->_ENGINE_parse_body($tpl->table_form_compile());
	return true;
}
function zabbix_conf_view_js():bool{
	$tpl=new template_admin();$page=CurrentPageName();
	return $tpl->js_dialog2("zabbix_agentd.conf","$page?conf-view-popup=yes",900);
}
function zabbix_conf_view_popup():bool{
	$tpl=new template_admin();
	$j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/zabbix/config"));
	if(!is_object($j) || $j->Status!=true){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{zbx_conf_not_found}"));return true;
	}
	$body=htmlspecialchars($j->Content);
	$path=htmlspecialchars($j->Path);
	$html="<div style='padding:8px'>
	<div class='zbxc-sub' style='margin-bottom:8px'>$path &middot; ".intval($j->Size)." bytes &middot; ".date("Y-m-d H:i",intval($j->Mtime))."</div>
	<pre class='zbxc-mono' style='max-height:60vh;overflow:auto;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12px'>$body</pre>
	</div>";
	echo zabbix_cfg_css().$tpl->_ENGINE_parse_body($html);
	return true;
}

function zabbix_metrics_export(){
	// La session est verifiee AVANT tout en-tete: sur session invalide,
	// class.sessions.inc emet une page HTML a META-refresh, que le navigateur
	// enregistrerait sous le nom zabbix-metrics.txt.
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		header("HTTP/1.1 403 Forbidden");
		echo "Access denied";
		return;
	}
	// REST_API() json_decode toute reponse et ne peut donc pas porter un
	// document; ici on veut le JSON, mais avec un timeout plus long que les
	// 13 s par defaut d'un GET -- d'ou le cURL direct sur la socket.
	$ch=curl_init("http://localhost/zabbix/metrics");
	curl_setopt($ch,CURLOPT_UNIX_SOCKET_PATH,"/usr/share/artica-postfix/bin/run/articarest.sock");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_TIMEOUT,60);
	$raw=curl_exec($ch);
	curl_close($ch);

	$j=json_decode($raw);
	// La garde teste Status AVANT toute autre chose, et avant le moindre
	// en-tete: un refus de securite (degradation de privileges impossible,
	// binaire absent, permission refusee) porte Status:false MAIS un Content
	// qui EXISTE (vide). Tester uniquement property_exists("Content") faisait
	// passer ce refus pour un export reussi de "0 metrique" -- plausible et
	// faux, et precisement l'incident que le refus existe pour rendre visible.
	if(!is_object($j) || !property_exists($j,"Status") || $j->Status!=true){
		$err="zabbix metrics export failed";
		if(is_object($j) && property_exists($j,"Error") && strlen($j->Error)>0){$err=$j->Error;}
		header("HTTP/1.1 502 Bad Gateway");
		header("Content-Type: text/plain; charset=utf-8");
		echo "$err\n";
		return;
	}
	if(!property_exists($j,"Content")){
		header("HTTP/1.1 502 Bad Gateway");
		header("Content-Type: text/plain; charset=utf-8");
		echo "zabbix metrics export failed\n";
		return;
	}
	$header="# Artica Zabbix agent metrics export\n";
	// Omit lines whose fields are absent, rather than write default values.
	if(property_exists($j,"Binary")){
		$header.="# binary: ".$j->Binary."\n";
	}
	if(property_exists($j,"GeneratedAt")){
		$header.="# generated: ".date("Y-m-d H:i:s",intval($j->GeneratedAt))."\n";
	}
	if(property_exists($j,"Count")){
		$header.="# items: ".intval($j->Count)."\n";
	}
	// Truncated absent → treat as "not truncated" (safe fallback). If present and true,
	// include the warning; DurationMs omitted if absent (no conditional output there).
	if(property_exists($j,"Truncated") && $j->Truncated==true){
		$durationMs=property_exists($j,"DurationMs")?intval($j->DurationMs):0;
		$header.="# WARNING: truncated after ".$durationMs."ms, the export is INCOMPLETE\n";
	}
	header("Content-Type: text/plain; charset=utf-8");
	header("X-Content-Type-Options: nosniff");
	header("Content-Security-Policy: default-src 'none'");
	header("Content-Disposition: attachment; filename=\"zabbix-metrics-".date("Ymd-His").".txt\"");
	echo $header."\n".$j->Content;
}

function table1(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Colonne de gauche inchangee : #zabbix-status + son rafraichissement.
	$Interval=$tpl->RefreshInterval_js("zabbix-status",$page,"zabbix-status=yes",3);
	$html="<div class='zbxc-grid'>
	<div><div id='zabbix-status'></div></div>
	<div id='zbxv-cfg'>".zbxv_content()."</div>
	</div>
	<script>$Interval</script>";
	echo zabbix_cfg_css().$html;
}


function ZabbixStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/zabbix/status");
    $json=json_decode($data);
    $page       = CurrentPageName();
    $jsrestart=section_js_restart();

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($ini, "APP_ZABBIX_AGENT",$jsrestart);
}

function activeCheckStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/zabbix/active/check");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());

    }
    if (!$json->Status) {
        return $tpl->widget_rouge("Zabbix {active_checks} {error}", $json->Info);

    }
    if ($json->Info=="active_checks_disabled"){
        return $tpl->widget_grey("Zabbix {active_checks}","{disabled}",null,ico_lock);

    }
    return $tpl->widget_vert("Zabbix {active_checks}","$json->Info OK",null,ico_nic);
}

function status():bool{
    include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
    $tpl=new template_admin();
    echo ZabbixStatus();
    echo "<div style='margin-top:10px'>&nbsp;</div>";
    echo activeCheckStatus();
    echo "<div style='margin-top:10px'>&nbsp;</div>";
    $tpl        = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/zabbix/passive/check");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo  $tpl->widget_rouge("Zabbix {passive_checks} {error}",json_last_error_msg());
        return true;
    }
    if (!$json->Status) {
        echo $tpl->widget_rouge("{error}", $json->Info);
        return true;
    }
    if ($json->Info=="passive_checks_disabled"){
        echo $tpl->widget_grey("Zabbix {passive_checks}","{disabled}",null,ico_lock);
        return true;
    }
    echo $tpl->widget_vert("Zabbix {passive_checks}","$json->Info OK",null,ico_nic);
    return true;
}

function Save(){
	$tpl=new template_admin();
	// /setinfo refuse TOUT l'enregistrement quand une cle tenue par Artica Meta est postee.
	$_POST=zbx_meta_strip_post($_POST);
	$tpl->SAVE_POSTs();

}

// ---------------------------------------------------------------------------
// Verrou "gere par Artica Meta" (politique zabbix, 2026-11-21, spec netagents-zabbix-policy section 6).
// Le noeud ecrit ZabbixMetaManaged (0|1) et ZabbixMetaManagedInfo, JSON {keys:[reglages tenus],
// fingerprint} (articarest zabbix/meta_lock.go). Seuls les reglages que la politique DEFINIT sont
// tenus ; information illisible alors que le drapeau est pose : tout le catalogue est tenu (echec
// ferme, comme HeldSettingKeys). Le demon refuse une cle tenue sur /setinfo : le grisage ne protege
// rien, il evite seulement un refus de tout l'enregistrement. PHP 7.3.
// ---------------------------------------------------------------------------

// Copie de ZabbixMeta.Catalog (articarest zabbix/ZabbixMeta/meta.go), pour l'echec ferme.
function zbx_meta_catalog_keys():array{
	return array("ZabbixPassiveCheckEnabled","ZabbixInterface","ZabbixListenPort","ZabbixAgentServerIP","ZabbixAgentPSK",
		"ZabbixActiveCheckEnabled","ServerActiveList","ZabbixRefreshActiveChecks","ZabbixOutGoingInterface","ZabbixDebugLevel");
}

// Reglages tenus, lus une fois par requete. null = ZabbixMetaManaged != 1.
function zbx_meta_keys(){
	if(array_key_exists("ZBX_META_KEYS",$GLOBALS)){return $GLOBALS["ZBX_META_KEYS"];}
	$GLOBALS["ZBX_META_KEYS"]=null;
	if(trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixMetaManaged"))!=="1"){return null;}
	$raw=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZabbixMetaManagedInfo"));
	$j=($raw==="")?null:json_decode($raw,true);
	$keys=array();
	if(!is_array($j) || (isset($j["keys"]) && !is_array($j["keys"]))){
		$keys=zbx_meta_catalog_keys();
	}else{
		foreach((isset($j["keys"]) && is_array($j["keys"]))?$j["keys"]:array() as $k){
			if(!is_scalar($k)){$keys=zbx_meta_catalog_keys();break;}
			$k=trim((string)$k);
			if($k!==""){$keys[]=$k;}
		}
	}
	$GLOBALS["ZBX_META_KEYS"]=$keys;
	return $keys;
}

function zbx_meta_held($key):bool{
	$keys=zbx_meta_keys();
	return $keys!==null && in_array((string)$key,$keys,true);
}

function zbx_meta_any_held(array $keys):bool{
	foreach($keys as $k){if(zbx_meta_held($k)){return true;}}
	return false;
}

// Prefixe des champs tenus montres en lecture seule : le nom poste n'est PAS la cle.
function zbx_meta_ro_prefix():string{ return "ZbxMetaRO"; }

function zbx_meta_posted($key):bool{
	return isset($_POST[$key]) || isset($_POST[zbx_meta_ro_prefix().$key]);
}

function zbx_meta_ro_field($tpl,$key,$label,$shown):string{
	$name=zbx_meta_ro_prefix().preg_replace('/[^A-Za-z0-9_]/','',(string)$key);
	return $tpl->field_info($name,$label,$shown,"<i class='fa-solid fa-lock'></i>&nbsp;{zbx_meta_locked}");
}

// Valeur affichee : echappee, accolades et crochets neutralises (ni {token} ni [br]).
function zbx_meta_h($v):string{
	return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

// Champ d'un reglage : tenu = lecture seule ; sinon $tpl->$method($key,...$args).
// $args[0] = libelle (prefixes nooloop: etc. retires a l'affichage), $args[1] = valeur.
function zbx_meta_fld($tpl,$method,$key,...$args):string{
	if(!zbx_meta_held($key)){
		return (string)call_user_func_array(array($tpl,$method),array_merge(array($key),$args));
	}
	$label=isset($args[0])?preg_replace('/^[A-Za-z]+:/','',(string)$args[0]):$key;
	$value=isset($args[1])?$args[1]:"";
	if($method==="field_checkbox"){
		$shown=(intval($value)==1)?"{enabled}":"{disabled}";
	}elseif($method==="field_interfaces" && trim((string)$value)===""){
		$shown=$key==="ZabbixOutGoingInterface"?"{zbxv_default}":"{zbxv_all_ifaces}";
	}else{
		$shown=trim((string)$value)===""?"-":zbx_meta_h($value);
	}
	return zbx_meta_ro_field($tpl,$key,$label,$shown);
}

function zbx_meta_fld_hash($tpl,array $hash,$key,$label,$value,...$rest):string{
	if(!zbx_meta_held($key)){
		return (string)call_user_func_array(array($tpl,"field_array_hash"),array_merge(array($hash,$key,$label,$value),$rest));
	}
	$shown=isset($hash[$value])?(string)$hash[$value]:zbx_meta_h($value);
	return zbx_meta_ro_field($tpl,$key,$label,$shown);
}

// Formulaire dont tous les reglages sont tenus : bouton grise. Rend le bandeau quand au moins un
// reglage est tenu ("" sinon).
function zbx_meta_form_lock($tpl,array $keys):string{
	if(!zbx_meta_any_held($keys)){return "";}
	$all=true;
	foreach($keys as $k){if(!zbx_meta_held($k)){$all=false;}}
	if($all){
		$tpl->FORM_LOCKED=true;
		$tpl->this_form_locked_explain="<i class='fa-solid fa-lock'></i>&nbsp;{zbx_meta_locked}";
	}
	return "<div class='alert alert-info' style='margin:10px 0'><i class='fa-solid fa-lock'></i>&nbsp;{zbx_meta_managed_form}</div>";
}

// Retire de $post les champs en lecture seule, les reglages tenus et les cles du verrou.
function zbx_meta_strip_post(array $post):array{
	$p=zbx_meta_ro_prefix();
	foreach(array_keys($post) as $k){
		$k=(string)$k;
		if(strpos($k,$p)===0 || strpos($k,"ZabbixMetaManaged")===0 || zbx_meta_held($k)){unset($post[$k]);}
	}
	return $post;
}

// Pastille "Artica Meta" d'un reglage tenu ("" sinon), dans les cartes de la colonne de droite.
function zbx_meta_tag($key):string{
	if(!zbx_meta_held($key)){return "";}
	return " <span class='zbxv-badge ak-info' title=\"{zbx_meta_locked}\"><i class='fa-solid fa-lock' aria-hidden='true'></i>Artica Meta</span>";
}

// Bandeau "gere par Artica Meta" de la colonne de droite ("" quand rien n'est tenu).
function zbx_meta_banner():string{
	$keys=zbx_meta_keys();
	if($keys===null){return "";}
	return "<div class='zbxv-health ak-info'><span class='zbxv-health-t'><i class='fa-solid fa-lock' aria-hidden='true'></i>{zbx_meta_managed}</span>"
		."<span>{zbx_meta_managed_explain}</span><span class='zbxv-val'>".count($keys)." {netagents_pp_settings_defined_lc}</span></div>";
}
