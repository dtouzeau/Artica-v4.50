<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");

if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["samba-daemon-status"])){samba_daemon_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SambaInterfaces"])){save_config();exit;}
if(isset($_POST["SambaWorkgroup"])){save_config();exit;}
if(isset($_GET["promote"])){promote();exit;}
// Module de synchronisation : la page fw.samba.syncs.php annoncait « module
// desactive » sans offrir aucun moyen de l'activer. Le voici.
if(isset($_GET["sync-js"])){sync_js();exit;}
if(isset($_GET["sync-popup"])){sync_popup();exit;}
if(isset($_POST["sync-enabled"])){sync_enabled_save();exit;}



page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    $html=$tpl->page_header("{APP_SAMBA} {$version}","fa fa-folder","{SAMBA_MAIN_PARAMS_TEXT}",
    "$page?tabs=yes","samba","progress-samba-restart",false,"table-loader-samba-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SAMBA} {$version}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function service_status(){
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/samba/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($data->Info);

    $jsrestart=$tpl->framework_buildjs("/samba/restart",
        "samba.restart.progress","samba.restart.log","progress-samba-restart");

    $f[]=$tpl->SERVICE_STATUS($ini, "APP_SAMBA",$jsrestart);
    echo $tpl->_ENGINE_parse_body($f);
}
function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$PromoteSamba=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PromoteSamba"));
	// Tuile du service a gauche (rafraichie), carte de synthese Artica File Sharing a droite.
	// L'ancien appel getFrameWork('samba.php?global-status=yes') et la tuile NMBD calculee
	// mais jamais affichee ont ete retires : le framework n'existe plus cote serveur.
	$html[]="<div class='smbp-layout' style='display:flex;gap:20px;align-items:flex-start;margin-top:20px'>
		<div style='width:350px;flex:none'><div id='main-samba-status'></div></div>
		<div style='flex:1;min-width:0'><div id='zPromote'></div></div></div>";
	$html[]="<script>";
	if($PromoteSamba==0){
		$html[]="LoadAjax('zPromote','$page?promote=true');";
	}
	$html[]=$tpl->RefreshInterval_js("main-samba-status",$page,"service-status=yes");
	$html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function promote(){
    $tpl=new template_admin();
    $m=smbp_model();
    $h=array();
    $h[]="<div class='smbp'>";
    $h[]=smbp_engine($m);
    if($m["error"]===""){
        $h[]=smbp_instances($m);
        $h[]="<div class='smbp-mods'>".smbp_sync($m).smbp_agent($m)."</div>";
    }
    $h[]="</div>";
    echo smbp_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).smbp_js();
    return true;
}

function smbp_model():array{
    $m=array("error"=>"","version"=>"","samba"=>"","instances"=>null,"sync"=>null,"agent"=>null);
    $samba=new ArticaSamba();
    try{
        $hl=$samba->health();
        $m["version"]=(string)($hl["version"] ?? "");
        $m["samba"]=(string)($hl["samba_version"] ?? "");
    }catch(\Exception $e){
        $m["error"]=$e->getMessage();
        return $m;
    }
    try{
        $l=$samba->listInstances();
        $list=isset($l["instances"]) && is_array($l["instances"])?$l["instances"]:$l;
        $m["instances"]=array();
        foreach($list as $i){if(is_array($i) && isset($i["name"])){$m["instances"][]=$i;}}
    }catch(\Exception $e){$m["instances"]=null;}
    try{$m["sync"]=$samba->syncsStatus();}catch(\Exception $e){$m["sync"]=null;}
    try{$m["agent"]=$samba->agentSettings();}catch(\Exception $e){$m["agent"]=null;}
    return $m;
}

function smbp_t(string $k,array $args=array()):string{
    $tpl=new template_admin();
    $s=trim(preg_replace('/\s+/',' ',str_replace(array("[br]","<br>")," ",$tpl->_ENGINE_parse_body("{".$k."}"))));
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function smbp_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}

// Bouton qui ouvre une autre page de la console dans la zone principale, comme le menu de gauche.
function smbp_goto(string $page,string $label,string $ico):string{
    return "<button type='button' class='smbp-btn2' OnClick=\"LoadAjaxSilent('MainContent','$page');\">$label <i class='fas $ico' aria-hidden='true'></i></button>";
}

function smbp_engine(array $m):string{
    $h=array();
    $h[]="<section class='smbp-card smbp-engine'><div class='smbp-eh'><span class='smbp-tile'><i class='fas fa-folder-tree' aria-hidden='true'></i></span>"
        ."<span class='smbp-et'>{smbp_title}</span>";
    if($m["version"]!==""){$h[]="<span class='smbp-mchip'>{smbp_daemon} ".smbp_h($m["version"])."</span>";}
    if($m["samba"]!==""){$h[]="<span class='smbp-mchip'>Samba ".smbp_h($m["samba"])."</span>";}
    $h[]="<span class='smbp-grow'></span>";
    $h[]=$m["error"]===""?"<span class='smbp-pill smbp-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {smbp_reachable}</span>"
        :"<span class='smbp-pill smbp-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {smbp_unreachable}</span>";
    $h[]="</div>";
    if($m["error"]!==""){
        $h[]="<div class='smbp-ban smbp-ban-err' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span>".smbp_t("smbp_unreachable_msg",array(smbp_h($m["error"])))."</span></div>";
    }
    $h[]="<p class='smbp-intro'>{smbp_intro}</p>";
    $h[]="<button type='button' class='smbp-more' aria-expanded='false' aria-controls='smbp-about' OnClick=\"SmbpMore(this);\">{smbp_learn_more} <i class='fas fa-chevron-down' aria-hidden='true'></i></button>";
    $h[]="<div id='smbp-about' class='smbp-about' hidden>{about_articafile_share}</div>";
    $h[]="</section>";
    return implode("",$h);
}

function smbp_state(string $st):array{
    $map=array("running"=>array("ok","{smbp_st_running}"),"starting"=>array("warn","{smbp_st_starting}"),
        "stopped"=>array("off","{smbp_st_stopped}"),"failed"=>array("err","{smbp_st_failed}"));
    return $map[$st] ?? array("off",smbp_h($st));
}

function smbp_mode(string $mode):string{
    $map=array("standalone"=>"{smbp_mode_standalone}","ad_dc"=>"{smbp_mode_addc}","domain_member"=>"{smbp_mode_member}");
    return $map[$mode] ?? smbp_h($mode);
}

function smbp_instances(array $m):string{
    $open=smbp_goto("fw.samba.instances2.php","{smbp_open_instances}","fa-arrow-right");
    $h=array();
    $h[]="<section class='smbp-card'>";
    if(!is_array($m["instances"])){
        $h[]="<div class='smbp-ch'><span class='smbp-ct'><i class='fas fa-server' aria-hidden='true'></i> {instances}</span><span class='smbp-grow'></span>$open</div>";
        $h[]="<div class='smbp-ban smbp-ban-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span>{smbp_instances_error}</span></div></section>";
        return implode("",$h);
    }
    $c=array("running"=>0,"failed"=>0,"stopped"=>0);
    foreach($m["instances"] as $i){
        $st=(string)($i["state"] ?? "");
        if(isset($c[$st])){$c[$st]++;}
    }
    $n=count($m["instances"]);
    $chip=function($k,$cls,$v){
        return "<span class='smbp-cnt smbp-$cls".($v==0?" dim":"")."'><i class='fas fa-circle' aria-hidden='true'></i> ".smbp_t("smbp_n_$k",array($v))."</span>";
    };
    $h[]="<div class='smbp-ch'><span class='smbp-ct'><i class='fas fa-server' aria-hidden='true'></i> {instances}</span><span class='smbp-badge'>$n</span>"
        .$chip("running","ok",$c["running"]).$chip("failed","err",$c["failed"]).$chip("stopped","off",$c["stopped"])
        ."<span class='smbp-grow'></span>$open</div>";
    $h[]="<div class='smbp-help'>{smbp_instances_help}</div>";
    if($n==0){
        $h[]="<div class='smbp-empty'><i class='fas fa-server' aria-hidden='true'></i> {smbp_no_instance}</div></section>";
        return implode("",$h);
    }
    $h[]="<div class='smbp-list'>";
    $shown=0;
    foreach($m["instances"] as $i){
        if($shown==6){break;}
        $shown++;
        $x=smbp_state((string)($i["state"] ?? ""));
        $auto=!empty($i["auto_start"])?"<span class='smbp-tag'>{smbp_auto_start}</span>":"";
        $ip=trim((string)($i["ip_address"] ?? ""));
        $h[]="<div class='smbp-row'><span class='smbp-st smbp-$x[0]'><i class='fas fa-circle' aria-hidden='true'></i> {$x[1]}</span>"
            ."<span class='smbp-iname'>".smbp_h($i["name"])."</span><span class='smbp-grow'></span>"
            ."<span class='smbp-mode'>".smbp_mode((string)($i["mode"] ?? ""))."</span>"
            ."<span class='smbp-ip'>".($ip!==""?smbp_h($ip):"&mdash;")."</span><span class='smbp-tagw'>$auto</span></div>";
    }
    if($n>6){$h[]="<div class='smbp-moreline'>".smbp_t("smbp_more",array($n-6))."</div>";}
    $h[]="</div></section>";
    return implode("",$h);
}

function smbp_onoff(bool $on):string{
    return $on?"<span class='smbp-pill smbp-ok'><i class='fas fa-circle' aria-hidden='true'></i> {smbp_on}</span>"
        :"<span class='smbp-pill smbp-off'><i class='fas fa-power-off' aria-hidden='true'></i> {smbp_off}</span>";
}

function smbp_kv(string $k,string $v):string{
    return "<div class='smbp-kv'><span class='smbp-k'>$k</span><span class='smbp-v'>$v</span></div>";
}

function smbp_sync(array $m):string{
    $page=CurrentPageName();
    $s=$m["sync"];
    $btn="<button type='button' class='smbp-btn2' OnClick=\"Loadjs('$page?sync-js=yes');\">{smbp_sync_settings}</button>";
    $h=array();
    $h[]="<section class='smbp-card'>";
    $h[]="<div class='smbp-ch'><span class='smbp-tile smbp-tile-s'><i class='fas fa-arrows-rotate' aria-hidden='true'></i></span><span class='smbp-ct'>{sync_module}</span><span class='smbp-grow'></span>"
        .(is_array($s)?smbp_onoff(!empty($s["enabled"])):"")."$btn</div>";
    $h[]="<div class='smbp-help'>{smbp_sync_help}</div>";
    if(!is_array($s)){
        $h[]="<div class='smbp-muted'>{smbp_sync_unavailable}</div></section>";
        return implode("",$h);
    }
    $inst=!empty($s["installed"]);
    $rc=$inst?"<span class='smbp-mono'>".smbp_h(trim((string)($s["version"] ?? "")))."</span>"
        :"<span class='smbp-warnt'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {not_installed}</span>";
    $h[]=smbp_kv("rclone",$rc);
    $pr=is_array($s["protocols"] ?? null)?$s["protocols"]:array();
    $chips=array();
    foreach($pr as $p){$chips[]="<span class='smbp-mchip'>".smbp_h($p)."</span>";}
    $h[]=smbp_kv("{smbp_protocols}",count($chips)?"<span class='smbp-chips'>".implode("",$chips)."</span>":"&mdash;");
    $h[]=smbp_kv("{smbp_rules}",smbp_t("smbp_rules_line",array(intval($s["rules"] ?? 0),intval($s["scheduled_rules"] ?? 0),intval($s["unscheduled_rules"] ?? 0))));
    $run=intval($s["running_syncs"] ?? 0);
    $h[]=smbp_kv("{smbp_running_now}",$run>0?"<span class='smbp-okt'><i class='fas fa-circle' aria-hidden='true'></i> ".smbp_t("smbp_n_active",array($run))."</span>":"0");
    if(!$inst){$h[]="<div class='smbp-ban smbp-ban-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{smbp_rclone_missing}</span></div>";}
    $h[]="</section>";
    return implode("",$h);
}

// Date Go (RFC 3339) -> array(timestamp, jours restants) ; null si absente ou illisible.
function smbp_expiry($iso){
    $iso=trim((string)$iso);
    if($iso==="" || strpos($iso,"0001-01-01")===0){return null;}
    try{$d=new DateTime($iso);}catch(\Exception $e){return null;}
    $ts=$d->getTimestamp();
    return array($ts,intval(floor(($ts-time())/86400)));
}

function smbp_cert_cell($e):string{
    if(!is_array($e)){return "&mdash;";}
    list($ts,$days)=$e;
    $date=date("Y-m-d",$ts);
    if($days<0){return "<span class='smbp-errt'><i class='fas fa-circle-xmark' aria-hidden='true'></i> ".smbp_t("smbp_expired_on",array($date))."</span>";}
    if($days<30){return "<span class='smbp-warnt'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".smbp_t("smbp_expires_days",array($date,$days))."</span>";}
    return "<span class='smbp-mono'>".smbp_t("smbp_expires",array($date))."</span>";
}

function smbp_agent(array $m):string{
    $a=$m["agent"];
    $btn=smbp_goto("fw.samba.agents.php","{smbp_agent_settings}","fa-arrow-right");
    $h=array();
    $h[]="<section class='smbp-card'>";
    $h[]="<div class='smbp-ch'><span class='smbp-tile smbp-tile-s'><i class='fas fa-user-shield' aria-hidden='true'></i></span><span class='smbp-ct'>{windows_agent}</span><span class='smbp-grow'></span>"
        .(is_array($a)?smbp_onoff(!empty($a["enabled"])):"")."$btn</div>";
    $h[]="<div class='smbp-help'>{smbp_agent_help}</div>";
    if(!is_array($a)){
        $h[]="<div class='smbp-muted'>{smbp_agent_unavailable}</div></section>";
        return implode("",$h);
    }
    $on=!empty($a["enabled"]);
    $up=!empty($a["listening"]);
    $addrs=is_array($a["listen_addresses"] ?? null)?$a["listen_addresses"]:array();
    if(!$on){$where="<span class='smbp-muted'>{smbp_off}</span>";}
    elseif(!$up || count($addrs)==0){$where="<span class='smbp-errt'><i class='fas fa-circle-xmark' aria-hidden='true'></i> {stopped}</span>";}
    else{$where="<span class='smbp-mono'>".smbp_h(implode(", ",$addrs))."</span>";}
    $h[]=smbp_kv("{listening_on}",$where);
    $srv=smbp_expiry($a["server_cert_expires_at"] ?? "");
    $ca=smbp_expiry($a["ca_expires_at"] ?? "");
    $h[]=smbp_kv("{smbp_server_cert}",smbp_cert_cell($srv));
    $h[]=smbp_kv("{smbp_ca}",smbp_cert_cell($ca));
    $err=trim((string)($a["last_error"] ?? ""));
    if($err!==""){$h[]=smbp_kv("{smbp_last_error}","<span class='smbp-errt'>".smbp_h($err)."</span>");}
    if($on && is_array($srv) && $srv[1]<30){
        $k=$srv[1]<0?"smbp_cert_expired_ban":"smbp_cert_expiring_ban";
        $h[]="<div class='smbp-ban ".($srv[1]<0?"smbp-ban-err":"smbp-ban-warn")."'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>".smbp_t($k,array(date("Y-m-d",$srv[0])))."</span></div>";
    }
    $h[]="</section>";
    return implode("",$h);
}

function smbp_js():string{
    return "<script>
window.SmbpMore=function(b){
  var on=b.getAttribute('aria-expanded')!=='true', d=document.getElementById('smbp-about');
  b.setAttribute('aria-expanded',on?'true':'false'); b.classList.toggle('open',on); if(d){d.hidden=!on;}
};
</script>";
}

function smbp_style():string{
    $ac=array("accent"=>"#0E7C66","accent_dark"=>"#0b6654","accent_soft"=>"rgba(14,124,102,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $ac["accent"]=$b["accent"];$ac["accent_dark"]=$b["accent_dark"];$ac["accent_soft"]=$b["accent_soft"];
    }
    $A=$ac["accent"];$AD=$ac["accent_dark"];$AS=$ac["accent_soft"];
    return "<style>
.smbp{font-size:13px;color:#111827;display:flex;flex-direction:column;gap:16px}
.smbp *{box-sizing:border-box}
.smbp-grow{flex:1}
.smbp-card{background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.05);min-width:0}
.smbp-eh,.smbp-ch{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.smbp-tile{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:$AS;color:$AD;flex:none}
.smbp-tile-s{width:32px;height:32px;border-radius:6px;background:#F3F4F6;color:$AD}
.smbp-et{font-size:15px;font-weight:600}
.smbp-ct{font-size:15px;font-weight:600;display:inline-flex;align-items:center;gap:8px}
.smbp-ct>svg,.smbp-ct>i{color:$AD}
.smbp-mono,.smbp-mchip,.smbp-ip{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}
.smbp-mchip{display:inline-block;padding:1px 8px;border-radius:4px;background:#F3F4F6;border:1px solid #E5E7EB;color:#374151;white-space:nowrap}
.smbp-chips{display:inline-flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.smbp-pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
.smbp-pill svg,.smbp-pill i{font-size:9px}
.smbp-pill.smbp-ok{background:#ECFDF5;color:#047857;border:1px solid #A7F3D0}
.smbp-pill.smbp-err{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA}
.smbp-pill.smbp-off{background:#F3F4F6;color:#6B7280;border:1px solid #E5E7EB}
.smbp-intro{margin:10px 0 4px;color:#4B5563;line-height:1.5}
.smbp-more{border:0;background:transparent;color:$AD;font-weight:600;padding:2px 0;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.smbp-more svg{transition:transform .15s}.smbp-more.open svg{transform:rotate(180deg)}
.smbp-about{margin-top:8px;padding:12px;border-radius:6px;background:#F9FAFB;color:#374151;line-height:1.55}
.smbp-about[hidden]{display:none}
.smbp-ban{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border-radius:0 6px 6px 0;margin-top:10px;line-height:1.45}
.smbp-ban>svg,.smbp-ban>i{flex:none;margin-top:2px}
.smbp-ban-err{background:#FEF2F2;border-left:4px solid #DC2626;color:#7F1D1D}
.smbp-ban-warn{background:#FFFBEB;border-left:4px solid #D97706;color:#92400E}
.smbp-badge{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:22px;padding:0 7px;border-radius:999px;background:#F3F4F6;font-weight:600;font-size:12px}
.smbp-cnt{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
.smbp-cnt svg,.smbp-cnt i{font-size:7px}
.smbp-cnt.smbp-ok{background:#ECFDF5;color:#047857}.smbp-cnt.smbp-err{background:#FEF2F2;color:#B91C1C}.smbp-cnt.smbp-off{background:#F3F4F6;color:#6B7280}
.smbp-cnt.dim{opacity:.5}
.smbp-help{font-size:12px;color:#6B7280;margin:6px 0 10px;line-height:1.45}
.smbp-btn2{display:inline-flex;align-items:center;gap:8px;height:32px;padding:0 12px;border:1px solid #E5E7EB;border-radius:6px;background:#fff;color:#374151;font-weight:600;font-size:12px;cursor:pointer;white-space:nowrap}
.smbp-btn2:hover{background:#F9FAFB}
.smbp-list{border-top:1px solid #F1F5F9}
.smbp-row{display:flex;align-items:center;gap:14px;padding:8px 4px;border-bottom:1px solid #F1F5F9}
.smbp-st{display:inline-flex;align-items:center;gap:6px;width:92px;font-weight:500;flex:none}
.smbp-st svg,.smbp-st i{font-size:8px}
.smbp-st.smbp-ok{color:#047857}.smbp-st.smbp-err{color:#B91C1C}.smbp-st.smbp-warn{color:#B45309}.smbp-st.smbp-off{color:#6B7280}
.smbp-iname{font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.smbp-mode{color:#6B7280;white-space:nowrap}
.smbp-ip{color:#374151;white-space:nowrap;min-width:120px;text-align:right}
.smbp-tagw{width:76px;flex:none;text-align:right}
.smbp-tag{display:inline-block;padding:1px 8px;border-radius:999px;background:#ECFDF5;color:#047857;font-size:11px;font-weight:600;white-space:nowrap}
.smbp-moreline,.smbp-empty,.smbp-muted{color:#6B7280;font-size:12px;padding:8px 4px}
.smbp-mods{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.smbp-kv{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;border-top:1px solid #F1F5F9}
.smbp-k{color:#4B5563;flex:none}
.smbp-v{text-align:right;min-width:0;overflow-wrap:anywhere}
.smbp-okt{color:#047857;font-weight:600}.smbp-warnt{color:#B45309;font-weight:600}.smbp-errt{color:#B91C1C;font-weight:600}
.smbp-okt svg{font-size:8px}
.smbp button:focus-visible{outline:2px solid $A;outline-offset:2px}
@media (prefers-reduced-motion:reduce){.smbp-more svg{transition:none}}
@media (max-width:1200px){.smbp-mods{grid-template-columns:minmax(0,1fr)}}
</style>";
}

// Modale d'activation du module de synchronisation. js_dialog1 : elle s'ouvre
// depuis la carte de la page, pas depuis une autre modale.
function sync_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{sync_module}","$page?sync-popup=yes",620);
}

function sync_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $samba=new ArticaSamba();
    $enabled=0; $installed=true; $err="";
    try{
        $st=$samba->syncsStatus();
        $enabled=!empty($st["enabled"])?1:0;
        $installed=!empty($st["installed"]);
    }catch(\RuntimeException $e){ $err=$e->getMessage(); }

    $html=array();
    $html[]=$tpl->div_explain("{sync_module}||{sync_module_enable_explain}");
    if($err!==""){
        $html[]=$tpl->div_error(htmlspecialchars($err));
        echo $tpl->_ENGINE_parse_body($html);
        return;
    }
    // rclone absent : l'activation est permise (elle se conserve), mais elle ne
    // fera rien tourner — le dire ICI vaut mieux que de laisser l'operateur
    // activer puis chercher pourquoi aucune regle ne part.
    if(!$installed){
        $html[]=$tpl->div_explain("{sync_rclone_missing}||{sync_rclone_missing_explain}");
    }
    echo $tpl->_ENGINE_parse_body($html);
    // Le widget est concatene APRES le parse : il porte son propre <script>.
    echo $tpl->BigCircleCheckbox("sync-enabled","{sync_module}","{sync_module_enable_explain}",$enabled,
        "dialogInstance1.close();LoadAjaxSilent('zPromote','$page?promote=true');");
}

// Le BigCircleCheckbox poste {sync-enabled: 0|1} sur cette page et ne lit que
// le succes HTTP : il n'affiche AUCUN corps de reponse. Le retour d'information
// vient donc du rechargement de la carte, qui relit l'etat du demon — si
// l'enregistrement a echoue, la carte reste sur « inactif », ce qui est vrai.
function sync_enabled_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $users=new usersMenus();
    if(!$users->AsSambaAdministrator){ echo $tpl->post_error("{ERROR_NO_PRIVS}"); return; }
    $on=intval($_POST["sync-enabled"])==1;
    $samba=new ArticaSamba();
    try{
        // saveSyncSettings FUSIONNE : n'envoyer que la cle voulue, et surtout pas
        // $_POST en entier — elle leve InvalidArgumentException sur toute cle
        // inconnue (jeton CSRF compris), ce qui donnerait une page blanche.
        $samba->saveSyncSettings(array("enabled"=>$on));
    }catch(\Exception $e){
        echo $tpl->post_error($e->getMessage());
        return;
    }
    admin_tracks("File sync module ".($on?"enabled":"disabled"));
}


function save_config(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	foreach($_POST as $num=>$val){
		$sock->SET_INFO("$num", $val);
	}

}


function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $array["{status}"]="$page?status=yes";
	echo $tpl->tabs_default($array);

}

function samba_daemon_status(){

}