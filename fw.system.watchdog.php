<?php
// Post SP10 - 13/10/2026
// Refonte Stitch 2026-11-19 (stitch_system_events_console_redesign) : journal de bord de l'appliance.
// Donnees : system_events.db (squid_admin_mysql, btmp_records) et GET /localadmins/admintracks.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
// awc_table_style() / awc_pager_js() : inclusion inconditionnelle (sinon fatal).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : scriptname est une liste de noms de scripts separes par des virgules.
if(isset($_GET["scriptname"])){$_GET["scriptname"]=preg_replace('/[^A-Za-z0-9_.,\[\]:\/-]/','',strval($_GET["scriptname"]));}

if(isset($_GET["failedloginattempts-js"])){failed_logging_js();exit;}
if(isset($_GET["licenseserver-js"])){failed_license_server_js();exit;}
if(isset($_GET["licenseserver-popup"])){failed_license_server_popup();exit;}

if(isset($_GET["build"])){wd_build();exit;}
if(isset($_GET["microstart"])){wd_events_tab(true);exit;}
if(isset($_GET["events-tab"])){wd_events_tab(false);exit;}
if(isset($_GET["events-table"])){wd_events_table();exit;}
if(isset($_GET["admins-tab"])){wd_admins_tab();exit;}
if(isset($_GET["admins-table"])){wd_admins_table();exit;}
if(isset($_GET["failed-logging"])){wd_logins_tab();exit;}
if(isset($_GET["logins-table"])){wd_logins_table();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["remove-events"])){remove_events_js();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}
if(isset($_POST["remove-events"])){remove_events_perform();exit;}
page();

// ── Helpers ─────────────────────────────────────────────────────────────────

/** Token traduit avec %1, %2... remplaces APRES traduction (cache par requete). */
function wd_t($token,$args=array()):string{
    static $cache=array();
    if(!isset($cache[$token])){
        $tpl=new template_admin();
        $s=$tpl->_ENGINE_parse_body("{".$token."}");
        $cache[$token]=preg_replace('#<br\s*/?>\s*$#i','',$s);
    }
    $s=$cache[$token];
    for($i=count($args);$i>=1;$i--){ $s=str_replace("%$i",(string)$args[$i-1],$s); }
    return $s;
}

function wd_db():lib_sqlite{
    return new lib_sqlite("/home/artica/SQLITE/system_events.db");
}

/** Rows d'une requete liee, toujours un tableau (table absente, base verrouillee...). */
function wd_rows($sql,$params=array()):array{
    $q=wd_db();
    $r=$q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok || !is_array($r)){return array();}
    return $r;
}

/** Retention reelle, memes bornes que le demon (notifs/SQLite.go) : 0 -> 7, minimum 2. */
function wd_retention():int{
    $n=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    if($n==0){$n=7;}
    if($n<2){$n=2;}
    return $n;
}

/** Age relatif lisible ; la date exacte va dans l'info-bulle. */
function wd_ago($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "—";}
    $d=time()-$ts;
    if($d<60){return wd_t("wd_ago_now");}
    if($d<3600){return wd_t("wd_ago_min",array(intval($d/60)));}
    if($d<86400){return wd_t("wd_ago_hour",array(intval($d/3600)));}
    return wd_t("wd_ago_day",array(intval($d/86400)));
}

/** Date exacte + age relatif, en span avec info-bulle. */
function wd_when($ts):string{
    $tpl=new template_admin();
    $exact=htmlspecialchars(strval($tpl->time_to_date(intval($ts),true)));
    return "<span title=\"$exact\">".wd_ago($ts)."</span>";
}

/** severite numerique -> (cle, token, icone). */
function wd_level($severity):array{
    $severity=intval($severity);
    if($severity==0){return array("crit","{wd_lvl_crit}","fas fa-circle-exclamation");}
    if($severity==1){return array("warn","{wd_lvl_warn}","fas fa-triangle-exclamation");}
    return array("info","{wd_lvl_info}","fas fa-circle-info");
}

/** Niveau demande par l'IHM -> severite SQL (-1 = tous). */
function wd_level_param($v):int{
    $map=array("crit"=>0,"warn"=>1,"info"=>2);
    $v=strval($v);
    return isset($map[$v])?$map[$v]:-1;
}

/** Scripts du proxy : le filtre « Proxy (Squid) » (liste historique de la page). */
function wd_squid_scripts():array{
    return array("exec.squid.disable.php","class.status.squid.inc","exec.squid.watchdog.php","squid-service",
        "web-filtering","squid","class.squid.automatic-tasks.inc","exec.squid.php");
}

/**
 * Decoupe l'origine d'un evenement.
 * Go : "BackupAndRestore.go[postgresqld.BackupDatabase:279]" -> module postgresqld,
 * fonction postgresqld.BackupDatabase, brut "BackupAndRestore.go:279".
 * Autre : "DebianAgent.UpdateBinary", "exec.squid.php", "RemoveWizard".
 */
function wd_origin($filename,$function,$line):array{
    $filename=strval($filename);$function=strval($function);$line=intval($line);
    if(strpos($filename,"/")!==false && strpos($filename,"[")===false){$filename=basename($filename);}
    if(preg_match('#^(.+?)\[(.+?)(?::([0-9]+))?\]$#',$filename,$re)){
        $fn=$re[2];
        $ln=isset($re[3])?intval($re[3]):$line;
        $module=$fn;
        if(strpos($module,".")!==false){$module=substr($module,0,strpos($module,"."));}
        if(strpos($module,"/")!==false){$module=substr($module,0,strpos($module,"/"));}
        return array("module"=>strtolower($module),"raw"=>$re[1].($ln>0?":$ln":""),"function"=>$fn,"file"=>$re[1]);
    }
    $module=$filename;
    if(!preg_match('#^(exec|class)\.#',$filename) && strpos($filename,".")!==false && !preg_match('#\.(go|php|inc)$#',$filename)){
        $module=substr($filename,0,strpos($filename,"."));
    }
    $fn=($function!=="" && $function!==$filename)?$function:"";
    return array("module"=>strtolower($module),"raw"=>$filename.($line>0?" L.$line":""),"function"=>$fn,"file"=>$filename);
}

/** Nom courant de la source (token), deduit du module ; repli « Artica system ». */
function wd_source_name($origin):string{
    $m=$origin["module"];
    if(stripos($origin["function"],"updatebinary")!==false){return "{wd_src_updates}";}
    $map=array(
        "postgresqld"=>"wd_src_database","apostgres"=>"wd_src_database",
        "aptget"=>"wd_src_packages","installpackage"=>"wd_src_packages","removewizard"=>"wd_src_packages",
        "debianagent"=>"wd_src_updates","articaai"=>"wd_src_updates","artica-docker"=>"wd_src_updates",
        "logrotate"=>"wd_src_cleanup",
        "samba"=>"wd_src_samba",
        "haclustertool"=>"wd_src_hacluster","hacluster"=>"wd_src_hacluster",
        "categories"=>"wd_src_webfilter","updateblacklists"=>"wd_src_webfilter","ufdbguardd"=>"wd_src_webfilter",
        "ufdbservice"=>"wd_src_webfilter","web-filtering"=>"wd_src_webfilter",
        "squid"=>"wd_src_proxy","squidstats"=>"wd_src_proxy","squiddns"=>"wd_src_proxy","goshield"=>"wd_src_proxy",
        "squid-service"=>"wd_src_proxy",
        "templates"=>"wd_src_errorpages","weberrorpage"=>"wd_src_errorpages",
        "openssh"=>"wd_src_ssh",
        "networking"=>"wd_src_network",
        "unbound"=>"wd_src_dns","dnsdist"=>"wd_src_dns","dns_servers_watchdog.go"=>"wd_src_dns",
        "nginx"=>"wd_src_reverse","reverseproxy"=>"wd_src_reverse",
        "ssl_ca_expiry_watchdog.go"=>"wd_src_certificates",
        "class.template-admin.inc"=>"wd_src_console",
        "postfix"=>"wd_src_mail",
    );
    if(isset($map[$m])){return "{".$map[$m]."}";}
    if(preg_match('#^(exec|class)\.#',$m)){
        if(strpos($m,"squid")!==false){return "{wd_src_proxy}";}
        if(preg_match('#ufdb|categor|tlse|blacklist#',$m)){return "{wd_src_webfilter}";}
        if(strpos($m,"postfix")!==false){return "{wd_src_mail}";}
        if(strpos($m,"nginx")!==false){return "{wd_src_reverse}";}
    }
    return "{wd_src_system}";
}

// ── Modele : lu une seule fois par affichage de l'en-tete ───────────────────

function wd_model():array{
    $m=array("crit"=>0,"warn"=>0,"info"=>0,"total"=>0,"series"=>array(0=>array(),1=>array(),2=>array()),
        "lastcrit"=>null,"logins"=>0,"login_hosts"=>0,"login_last"=>0,"retention"=>wd_retention(),
        "trackadmins"=>false,"squid"=>false);
    foreach(wd_rows("SELECT severity, COUNT(*) AS c FROM squid_admin_mysql GROUP BY severity") as $r){
        $c=intval($r["c"]);
        list($k)=wd_level($r["severity"]);
        $m[$k]+=$c;
        $m["total"]+=$c;
    }
    // 7 barres = 7 derniers jours calendaires, aujourd'hui compris.
    $start=mktime(0,0,0)-6*86400;
    for($s=0;$s<3;$s++){ $m["series"][$s]=array_fill(0,7,0); }
    foreach(wd_rows("SELECT severity, CAST((zDate-?)/86400 AS INTEGER) AS d, COUNT(*) AS c FROM squid_admin_mysql WHERE zDate>=? GROUP BY severity,d",array($start,$start)) as $r){
        $s=intval($r["severity"]);if($s<0||$s>2){$s=2;}
        $d=intval($r["d"]);
        if($d>=0 && $d<7){$m["series"][$s][$d]+=intval($r["c"]);}
    }
    $last=wd_rows("SELECT subject, zDate FROM squid_admin_mysql WHERE severity=0 ORDER BY zDate DESC LIMIT 1");
    if(count($last)>0){$m["lastcrit"]=$last[0];}
    $b=wd_rows("SELECT COUNT(*) AS c, COUNT(DISTINCT NULLIF(host,'')) AS h, MAX(timestamp) AS t FROM btmp_records");
    if(count($b)>0){
        $m["logins"]=intval($b[0]["c"]);
        $m["login_hosts"]=intval($b[0]["h"]);
        $t=strtotime(strval($b[0]["t"]));
        $m["login_last"]=$t?$t:0;
    }
    $m["trackadmins"]=(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TrackAdmins"))==1 && $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE());
    $m["squid"]=(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1);
    return $m;
}

// ── Style et JS ─────────────────────────────────────────────────────────────

/** Couleur d'accent de la marque (appliance en marque blanche), repli vert console. */
function wd_accent():string{
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){return $a["accent"];}
    }
    return "#006a62";
}

function wd_style():string{
    if(isset($GLOBALS["WD_STYLE"])){return "";}
    $GLOBALS["WD_STYLE"]=true;
    $accent="#006a62";$soft="rgba(16,185,129,.12)";$line="#99d5cf";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
#wd-ev-host .awc-wrap,#wd-ad-host .awc-wrap,#wd-lg-host .awc-wrap{padding:0;margin:0}
.wd-wrap{display:flex;flex-direction:column;gap:16px;margin-top:8px}
.wd-banner{background:#fff;border:1px solid #e2e8f0;border-left:4px solid $accent;border-radius:12px;padding:16px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.wd-banner h3{margin:0 0 6px 0;font-size:17px;font-weight:700;color:#111d23}
.wd-banner p{margin:0 0 12px 0;font-size:13px;line-height:1.55;color:#3d4947;max-width:980px}
.wd-chips{display:flex;flex-wrap:wrap;gap:8px}
.wd-chip{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:4px 12px;font-size:13px;color:#334155}
.wd-chip i,.wd-chip svg{color:$accent}
.wd-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
@media (max-width:1200px){.wd-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
.wd-kpi{display:flex;flex-direction:column;justify-content:space-between;min-height:116px;text-align:left;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;box-shadow:0 1px 2px rgba(15,23,42,.06);cursor:pointer;transition:border-color .15s,box-shadow .15s;width:100%}
.wd-kpi:hover{border-color:#bcc9c6}
.wd-kpi:focus-visible{outline:2px solid $accent;outline-offset:2px}
.wd-kpi[aria-pressed=true]{border-color:$accent;box-shadow:0 0 0 2px $soft}
.wd-kpi-top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.wd-kpi-num{font-size:28px;font-weight:700;line-height:1;margin-right:8px}
.wd-kpi-lbl{font-size:14px;font-weight:600;color:#111d23}
.wd-kpi-sub{font-size:13px;color:#6d7a77;margin-top:4px;line-height:1.35}
.wd-kpi-ico{font-size:20px;flex:0 0 auto}
.wd-kpi-foot{margin-top:8px;font-size:13px;font-weight:600;color:$accent}
.wd-kpi-zero .wd-kpi-num{color:#94a3b8!important}
.wd-c-crit{color:#ba1a1a}.wd-c-warn{color:#b45309}.wd-c-info{color:$accent}.wd-c-neutral{color:#334155}
.wd-spark{display:block;margin-top:8px}
.wd-alert{display:flex;align-items:center;gap:14px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px}
.wd-alert-ico{color:#ba1a1a;font-size:20px;flex:0 0 auto}
.wd-alert p{margin:0;flex:1 1 auto;font-size:14px;color:#3d4947;line-height:1.5;min-width:0}
.wd-alert strong{color:#93000a}
.wd-btn{display:inline-flex;align-items:center;gap:8px;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid #bcc9c6;background:#fff;color:#111d23;white-space:nowrap}
.wd-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}
.wd-btn-danger{border-color:#fca5a5;color:#ba1a1a}
.wd-btn-danger:hover{background:#fef2f2}
.wd-tabc{display:inline-block;margin-left:6px;min-width:22px;padding:0 7px;border-radius:999px;background:#f1f5f9;color:#3d4947;font-size:12px;font-weight:600;line-height:20px;text-align:center}
.wd-intro{margin:14px 0 4px 0}
.wd-intro h4{margin:0 0 4px 0;font-size:15px;font-weight:700;color:#111d23}
.wd-intro p{margin:0;font-size:13px;color:#3d4947;line-height:1.5;max-width:900px}
.wd-sum{display:inline-flex;align-items:center;gap:8px;margin-top:10px;font-size:13px;color:#334155;background:#f1f5f9;border-radius:8px;padding:6px 12px}
.wd-bar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin:14px 0 12px 0}
.wd-search{position:relative;flex:0 1 380px;min-width:240px}
.wd-search i,.wd-search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6d7a77}
.wd-search input{width:100%;height:38px;border:1px solid #bcc9c6;border-radius:8px;padding:0 12px 0 36px;font-size:14px;background:#fff;color:#111d23}
.wd-search input:focus{outline:2px solid $accent;outline-offset:1px;border-color:$accent}
.wd-seg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:3px}
.wd-seg button{border:0;background:transparent;border-radius:6px;padding:5px 12px;font-size:13px;color:#3d4947;cursor:pointer;white-space:nowrap}
.wd-seg button[aria-pressed=true]{background:#fff;color:#111d23;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.12)}
.wd-seg button:focus-visible,.wd-src button:focus-visible{outline:2px solid $accent;outline-offset:1px}
.wd-src{display:inline-flex;gap:8px}
.wd-src button{border:1px solid #e2e8f0;background:#fff;border-radius:999px;padding:5px 14px;font-size:13px;color:#3d4947;cursor:pointer;white-space:nowrap}
.wd-src button[aria-pressed=true]{background:$soft;border-color:$line;color:$accent;font-weight:600}
.wd-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;border:1px solid transparent;white-space:nowrap}
.wd-pill-crit{background:#ffdad6;color:#93000a;border-color:#fecaca}
.wd-pill-warn{background:#ffddb9;color:#653e00;border-color:#fcd9a8}
.wd-pill-info{background:$soft;color:$accent;border-color:$line}
.wd-ico{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;flex:0 0 32px}
.wd-ico-crit{background:#ffdad6;color:#ba1a1a}
.wd-ico-warn{background:#ffddb9;color:#865300}
.wd-ico-info{background:$soft;color:$accent}
.wd-ev-subj{font-size:14px;font-weight:600;color:#111d23;word-break:break-word}
.wd-ev-subj a{color:#111d23}
.wd-ev-subj a:hover{color:$accent;text-decoration:underline}
.wd-mono{font-family:monospace;font-size:12px;color:#6d7a77;word-break:break-all}
.wd-srcname{font-size:14px;color:#111d23}
.wd-go{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid transparent;color:#3d4947;background:transparent;cursor:pointer}
.wd-go:hover{background:#f1f5f9;border-color:#e2e8f0}
.wd-go:focus-visible{outline:2px solid $accent;outline-offset:1px}
.wd-dash{color:#94a3b8}
.wd-empty-c{text-align:center;padding:40px 16px}
.wd-empty-c .wd-empty-ico{font-size:30px;color:$accent;margin-bottom:10px}
.wd-empty-c h4{margin:0 0 6px 0;font-size:16px;font-weight:700;color:#111d23}
.wd-empty-c p{margin:0 auto 12px auto;font-size:13px;color:#3d4947;max-width:520px;line-height:1.5}
.wd-cap{margin:8px 0 0 0;font-size:13px;color:#6d7a77}
.wd-detail-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px}
.wd-detail-head h3{margin:2px 0 0 0;font-size:16px;font-weight:700;color:#111d23;line-height:1.4;word-break:break-word}
.wd-pre{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;font-size:13px;line-height:1.55;color:#111d23;max-height:340px;overflow:auto;word-break:break-word}
.wd-facts{display:grid;grid-template-columns:140px 1fr;gap:6px 16px;margin-top:14px;font-size:13px}
.wd-facts dt{color:#6d7a77;font-weight:400}
.wd-facts dd{margin:0;color:#111d23}
.wd-warnbox{margin-top:16px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;padding:12px 14px;font-size:13px;color:#3d4947;line-height:1.5}
.wd-warnbox .wd-btn{margin-top:10px}
.wd-warnbox .wd-mono{word-break:normal;white-space:nowrap}
@media (prefers-reduced-motion:reduce){.wd-kpi,.wd-btn{transition:none}}
</style>";
}

// ── Page et en-tete ─────────────────────────────────────────────────────────

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{system_events}",ico_eye,"{wd_explain}",
        "$page?build=yes","events","progress-firehol-restart",false,"wd-main");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null, $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

/** Contenu : accroche, compteurs, alerte, onglets, puis le TINY (apres les onglets). */
function wd_build(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=wd_model();

    $h=array();
    $h[]="<div class='wd-wrap'>";
    $h[]=wd_banner($m);
    $h[]="<div class='wd-kpis'>";
    $h[]=wd_kpi_level($m,0);
    $h[]=wd_kpi_level($m,1);
    $h[]=wd_kpi_level($m,2);
    $h[]=wd_kpi_logins($m);
    $h[]="</div>";
    $h[]=wd_attention($m);
    $h[]="<div id='wd-tabs'>".wd_tabs($m)."</div>";
    $h[]="</div>";

    $users=new usersMenus();
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('fw.export.sqlite.php?table=squid_admin_mysql&file=system_events');",ico_csv,"{export}");
    $topbuttons[]=array("WdRefresh();",ico_refresh,"{refresh}");
    if($users->AsSystemAdministrator){
        $topbuttons[]=array("Loadjs('$page?empty-js=yes');",ico_trash,"{wd_empty_btn}");
    }
    $TINY=array();
    $TINY["TITLE"]="{system_events}";
    $TINY["ICO"]=ico_eye;
    $TINY["EXPL"]="{wd_explain}";
    $TINY["URL"]="events";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

    $js="<script>
window.WdState={q:'',level:'',source:''};
function WdRefresh(){LoadAjax('wd-main','$page?build=yes');}
function WdTab(i){var a=$('#wd-tabs ul.nav-tabs li a').eq(i);if(a.length){a.click();}}
function WdFilter(level){
  window.WdState.level=level;
  $('.wd-kpi[data-level]').attr('aria-pressed','false');
  if(level){ $('.wd-kpi[data-level='+level+']').attr('aria-pressed','true'); }
  if(document.getElementById('wd-ev-host') && $('#wd-tabs ul.nav-tabs li').first().hasClass('active')){ WdEvApply(); return; }
  WdTab(0);
}
$tiny
</script>";
    echo wd_style().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

/** Bandeau d'accroche : ce que fait le journal, et quatre verites du produit. */
function wd_banner($m):string{
    $n=intval($m["retention"]);
    $h=array();
    $h[]="<div class='wd-banner'>";
    $h[]="<h3>{wd_banner_title}</h3>";
    $h[]="<p>".wd_t("wd_banner_text",array($n))."</p>";
    $h[]="<div class='wd-chips'>";
    $h[]="<span class='wd-chip'><i class='fas fa-pen-to-square'></i>{wd_chip_auto}</span>";
    $h[]="<span class='wd-chip'><i class='fas fa-calendar-days'></i>".wd_t("wd_chip_kept",array($n))."</span>";
    $h[]="<span class='wd-chip'><i class='fas fa-eye'></i>{wd_chip_readonly}</span>";
    $h[]="<span class='wd-chip'><i class='fas fa-user-lock'></i>{wd_chip_logins}</span>";
    $h[]="</div></div>";
    return @implode("\n",$h);
}

/** Petites barres SVG : evenements par jour sur 7 jours, hauteur relative au max de la serie. */
function wd_spark($series,$key):string{
    $colors=array("crit"=>"#ba1a1a","warn"=>"#d97706","info"=>wd_accent());
    $col=$colors[$key];
    $max=0;foreach($series as $v){if($v>$max){$max=$v;}}
    $w=7*16;$hgt=24;
    $s="<svg class='wd-spark' width='$w' height='$hgt' viewBox='0 0 $w $hgt' role='img' aria-label='".htmlspecialchars(wd_t("wd_spark_label"),ENT_QUOTES)."'>";
    $tpl=new template_admin();
    $start=mktime(0,0,0)-6*86400;
    foreach($series as $i=>$v){
        $x=$i*16;
        $bh=($max>0 && $v>0)?max(3,intval(round($v/$max*$hgt))):2;
        $y=$hgt-$bh;
        $fill=$v>0?$col:"#e2e8f0";
        $op=$v>0?sprintf("%.2f",0.45+0.55*($v/$max)):"1";
        $day=date("Y-m-d",$start+$i*86400);
        $s.="<rect x='$x' y='$y' width='11' height='$bh' rx='2' fill='$fill' fill-opacity='$op'><title>$day: $v</title></rect>";
    }
    $s.="</svg>";
    return $s;
}

/** Tuile de niveau (critique / avertissement / information) : compteur, sous-titre, 7 jours, filtre. */
function wd_kpi_level($m,$severity):string{
    list($key,,$ico)=wd_level($severity);
    $cfg=array(
        0=>array("{wd_kpi_crit}",wd_t("wd_kpi_crit_sub",array(intval($m["retention"]))),"{wd_kpi_crit_none}"),
        1=>array("{wd_kpi_warn}","{wd_kpi_warn_sub}","{wd_kpi_warn_sub}"),
        2=>array("{wd_kpi_info}","{wd_kpi_info_sub}","{wd_kpi_info_sub}"),
    );
    $count=intval($m[$key]);
    list($label,$sub,$zero)=$cfg[$severity];
    $zcls=$count==0?" wd-kpi-zero":"";
    $h ="<button type='button' class='wd-kpi$zcls' data-level='$key' aria-pressed='false' OnClick=\"WdFilter('$key')\">";
    $h.="<div class='wd-kpi-top'><div><span class='wd-kpi-num wd-c-$key'>".number_format($count)."</span>";
    $h.="<span class='wd-kpi-lbl'>$label</span>";
    $h.="<div class='wd-kpi-sub'>".($count==0?$zero:$sub)."</div></div>";
    $h.="<span class='wd-kpi-ico wd-c-$key'><i class='$ico'></i></span></div>";
    $h.=wd_spark($m["series"][$severity],$key);
    $h.="</button>";
    return $h;
}

/** Tuile des connexions refusees : nombre, adresses distinctes, derniere tentative. */
function wd_kpi_logins($m):string{
    $count=intval($m["logins"]);
    $zcls=$count==0?" wd-kpi-zero":"";
    $sub=$count==0?"{wd_kpi_logins_none}":wd_t("wd_kpi_logins_sub",array(number_format($m["login_hosts"]),wd_ago($m["login_last"])));
    $idx=$m["trackadmins"]?2:1;
    $h ="<button type='button' class='wd-kpi$zcls' OnClick=\"WdTab($idx)\">";
    $h.="<div class='wd-kpi-top'><div><span class='wd-kpi-num wd-c-neutral'>".number_format($count)."</span>";
    $h.="<span class='wd-kpi-lbl'>{wd_kpi_logins}</span>";
    $h.="<div class='wd-kpi-sub'>$sub</div></div>";
    $h.="<span class='wd-kpi-ico wd-c-neutral'><i class='fas fa-user-lock'></i></span></div>";
    $h.="<div class='wd-kpi-foot'>{wd_kpi_logins_view} <i class='fas fa-arrow-right'></i></div>";
    $h.="</button>";
    return $h;
}

/** Alerte : seulement s'il y a au moins un probleme critique. */
function wd_attention($m):string{
    $n=intval($m["crit"]);
    if($n==0 || !is_array($m["lastcrit"])){return "";}
    $tpl=new template_admin();
    $subject=trim(strip_tags($tpl->_ENGINE_parse_body(strval($m["lastcrit"]["subject"]))));
    if(strlen($subject)>160){$subject=substr($subject,0,157)."...";}
    $head=$n==1?wd_t("wd_alert_one"):wd_t("wd_alert_many",array(number_format($n)));
    $h ="<div class='wd-alert' role='alert'>";
    $h.="<span class='wd-alert-ico'><i class='fas fa-circle-exclamation'></i></span>";
    $h.="<p><strong>$head</strong> ".wd_t("wd_alert_last",array("“".htmlspecialchars($subject)."”",wd_when($m["lastcrit"]["zDate"])))."</p>";
    $h.="<button type='button' class='wd-btn wd-btn-danger' OnClick=\"WdFilter('crit')\">{wd_alert_btn} <i class='fas fa-arrow-right'></i></button>";
    $h.="</div>";
    return $h;
}

/** Onglets avec icone et compteur ; le suivi des administrateurs seulement s'il est actif. */
function wd_tabs($m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();
    $array["<i class='fas fa-list'></i> {system_events} <span class='wd-tabc'>".number_format($m["total"])."</span>"]="$page?events-tab=yes";
    if($m["trackadmins"]){
        $array["<i class='fas fa-user-shield'></i> {track_administrators}"]="$page?admins-tab=yes";
    }
    $array["<i class='fas fa-user-lock'></i> {failedloginattempts} <span class='wd-tabc'>".number_format($m["logins"])."</span>"]="$page?failed-logging=yes";
    return $tpl->tabs_default($array);
}

// ── Onglet : evenements systeme ─────────────────────────────────────────────

/** Barre de filtres + hote du tableau. $embedded : charge comme onglet d'une autre page. */
function wd_events_tab($embedded){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $scripts=strval($_GET["scriptname"] ?? "");
    $counts=array("crit"=>0,"warn"=>0,"info"=>0,"total"=>0);
    if(!$embedded){
        foreach(wd_rows("SELECT severity, COUNT(*) AS c FROM squid_admin_mysql GROUP BY severity") as $r){
            list($k)=wd_level($r["severity"]);
            $counts[$k]+=intval($r["c"]);$counts["total"]+=intval($r["c"]);
        }
    }
    $squid=(!$embedded && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1);
    $c=function($k) use($counts,$embedded){ return $embedded?"":" (".number_format($counts[$k]).")"; };

    $h=array();
    $h[]="<div class='wd-bar' role='toolbar'>";
    $h[]="<div class='wd-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='wd-ev-q' placeholder=\"{wd_search_events}\" aria-label=\"{wd_search_events}\" OnKeyUp=\"WdEvKey()\"></div>";
    $h[]="<div class='wd-seg' role='group' aria-label=\"{wd_lvl_title}\">";
    $h[]="<button type='button' data-l='' aria-pressed='true' OnClick=\"WdEvLevel('')\">{wd_seg_all}".$c("total")."</button>";
    $h[]="<button type='button' data-l='crit' aria-pressed='false' OnClick=\"WdEvLevel('crit')\">{wd_seg_crit}".$c("crit")."</button>";
    $h[]="<button type='button' data-l='warn' aria-pressed='false' OnClick=\"WdEvLevel('warn')\">{wd_seg_warn}".$c("warn")."</button>";
    $h[]="<button type='button' data-l='info' aria-pressed='false' OnClick=\"WdEvLevel('info')\">{wd_seg_info}".$c("info")."</button>";
    $h[]="</div>";
    if($squid){
        $h[]="<div class='wd-src' role='group' aria-label=\"{wd_src_title}\">";
        $h[]="<button type='button' data-s='' aria-pressed='true' OnClick=\"WdEvSource('')\">{wd_src_all}</button>";
        $h[]="<button type='button' data-s='squid' aria-pressed='false' OnClick=\"WdEvSource('squid')\">{wd_src_squidchip}</button>";
        $h[]="</div>";
    }
    $h[]="</div>";
    $h[]="<div id='wd-ev-host'></div>";

    $sc=urlencode($scripts);
    $js="<script>
if(!window.WdState){window.WdState={q:'',level:'',source:''};}
if(typeof window.WdRefresh!=='function'){window.WdRefresh=function(){WdEvApply();};}
function WdEvSync(){
  var s=window.WdState;
  $('#wd-ev-q').val(s.q||'');
  $('.wd-seg button').each(function(){ $(this).attr('aria-pressed', ($(this).attr('data-l')===(s.level||''))?'true':'false'); });
  $('.wd-src button').each(function(){ $(this).attr('aria-pressed', ($(this).attr('data-s')===(s.source||''))?'true':'false'); });
  $('.wd-kpi[data-level]').each(function(){ $(this).attr('aria-pressed', ($(this).attr('data-level')===s.level)?'true':'false'); });
}
function WdEvApply(){
  var s=window.WdState; WdEvSync();
  LoadAjaxSilent('wd-ev-host','$page?events-table=yes&scriptname=$sc&q='+encodeURIComponent(s.q||'')+'&level='+encodeURIComponent(s.level||'')+'&source='+encodeURIComponent(s.source||''));
}
function WdEvLevel(l){ window.WdState.level=l; WdEvApply(); }
function WdEvSource(x){ window.WdState.source=x; WdEvApply(); }
function WdEvKey(){
  if(window.WdEvTimer){clearTimeout(window.WdEvTimer);}
  window.WdEvTimer=setTimeout(function(){ window.WdState.q=$('#wd-ev-q').val(); WdEvApply(); },400);
}
function WdEvClear(){ window.WdState.q='';window.WdState.level='';window.WdState.source=''; WdEvApply(); }
WdEvApply();
</script>";
    echo wd_style().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

/** Tableau des evenements : requete liee, 1 500 lignes au plus, pager navigateur de 100. */
function wd_events_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=substr(trim(strval($_GET["q"] ?? "")),0,100);
    $level=wd_level_param($_GET["level"] ?? "");
    $source=strval($_GET["source"] ?? "")=="squid"?"squid":"";
    $scripts=array();
    foreach(explode(",",strval($_GET["scriptname"] ?? "")) as $s){ $s=trim($s); if($s!==""){$scripts[]=$s;} }
    if($source=="squid"){$scripts=wd_squid_scripts();}

    $where=array();$params=array();
    if($level>=0){$where[]="severity=?";$params[]=$level;}
    if(count($scripts)>0){
        $where[]="filename IN (".implode(",",array_fill(0,count($scripts),"?")).")";
        foreach($scripts as $s){$params[]=$s;}
    }
    if($q!==""){
        $like="%".str_replace(array("%","_"),array("",""),str_replace("*","%",$q))."%";
        $where[]="(subject LIKE ? OR content LIKE ? OR filename LIKE ? OR function LIKE ?)";
        array_push($params,$like,$like,$like,$like);
    }
    $max=1500;
    $sql="SELECT ID,zDate,subject,severity,function,line,filename,LENGTH(content) AS clen FROM squid_admin_mysql"
        .(count($where)?" WHERE ".implode(" AND ",$where):"")." ORDER BY zDate DESC LIMIT $max";
    $db=wd_db();
    $results=$db->QUERY_SQL_PARAMS($sql,$params);
    if(!is_array($results)){$results=array();}

    $h=array();
    if(!$db->ok && !preg_match('#no such table#i',strval($db->mysql_error))){
        $h[]="<div class='alert alert-danger'>".htmlspecialchars(strval($db->mysql_error))."</div>";
    }
    $filtered=($q!=="" || $level>=0 || $source!=="");
    $rows=array();
    foreach($results as $ligne){ $rows[]=wd_event_row($tpl,$ligne,$page); }

    if(count($rows)==0){
        $h[]=$filtered?wd_empty_nomatch($q):wd_empty_events();
        echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
        return;
    }
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    $h[]="<th>{wd_col_event}</th><th style='width:22%'>{wd_col_source}</th><th style='width:1%'>{wd_col_level}</th><th style='width:1%;text-align:center'>{wd_col_details}</th>";
    $h[]="</tr></thead><tbody id='wd-rows-events'>";
    $h[]=@implode("\n",$rows);
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>".wd_t(count($rows)>=$max?"wd_showing_latest":"wd_showing",array(number_format(count($rows))))."</span><span class='awc-pg' id='wd-pager-events'></span></div>";
    $h[]="</div></div>";
    $h[]="<script>NoSpinner();</script>";
    echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).awc_pager_js(100,"wd-rows-events","wd-pager-events");
}

/** Etat vide : invitation, pas « No data ». */
function wd_empty_events():string{
    return "<div class='awc-card'><div class='wd-empty-c'><div class='wd-empty-ico'><i class='fas fa-book-open'></i></div>"
        ."<h4>{wd_empty_title}</h4><p>{wd_empty_text}</p></div></div>";
}

/** Aucun resultat pour les filtres choisis. */
function wd_empty_nomatch($q):string{
    $title=$q!==""?wd_t("wd_nomatch_q",array("“".htmlspecialchars($q)."”")):"{wd_nomatch}";
    return "<div class='awc-card'><div class='wd-empty-c'><div class='wd-empty-ico'><i class='fas fa-filter'></i></div>"
        ."<h4>$title</h4><p>{wd_nomatch_text}</p>"
        ."<button type='button' class='wd-btn' OnClick=\"WdEvClear()\"><i class='fas fa-xmark'></i> {wd_clear_filters}</button></div></div>";
}

/** Une ligne : icone de niveau, sujet, age + fonction ; source en mots courants ; pastille ; detail. */
function wd_event_row($tpl,$ligne,$page):string{
    list($key,$label,$ico)=wd_level($ligne["severity"]);
    $id=intval($ligne["ID"]);
    $hasDetail=(intval($ligne["clen"])>0);
    $js="Loadjs('$page?ShowID-js=$id')";

    $subject=strval($ligne["subject"]);
    if(preg_match("#\/var\/lib\/ufdbartica\/([0-9]+)#",$subject,$re)){
        $zcat=new mysql_catz();
        $subject=str_replace("/var/lib/ufdbartica/{$re[1]}",strval($zcat->CategoryIntToStr($re[1])),$subject);
    }
    // Le sujet reste non echappe (comportement historique : certains evenements portent du balisage).
    $name=$hasDetail?"<a href=\"javascript:blur();\" OnClick=\"$js\">$subject</a>":$subject;

    $o=wd_origin($ligne["filename"],$ligne["function"],$ligne["line"]);
    $meta=wd_when($ligne["zDate"]);
    if($o["function"]!==""){$meta.=" &middot; {wd_function} <span class='wd-mono'>".htmlspecialchars($o["function"])."</span>";}

    $act="<span class='wd-dash' aria-hidden='true'>—</span>";
    if($hasDetail){
        $act="<button type='button' class='wd-go' aria-label=\"{wd_open_details}\" title=\"{wd_open_details}\" OnClick=\"$js\"><i class='fas fa-chevron-right'></i></button>";
    }
    $h ="<tr>";
    $h.="<td><div class='awc-ident'><span class='wd-ico wd-ico-$key'><i class='$ico'></i></span>";
    $h.="<span><div class='wd-ev-subj'>$name</div><div class='awc-meta'>$meta</div></span></div></td>";
    $h.="<td><div class='wd-srcname'>".wd_source_name($o)."</div><div class='wd-mono'>".htmlspecialchars($o["raw"])."</div></td>";
    $h.="<td><span class='wd-pill wd-pill-$key'><i class='$ico'></i> $label</span></td>";
    $h.="<td style='text-align:center'>$act</td>";
    $h.="</tr>";
    return $h;
}

// ── Detail d'un evenement ───────────────────────────────────────────────────

function ShowID_js():bool{
    $id=intval($_GET["ShowID-js"]);
    if($id<=0){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{wd_detail_title}","$page?ShowID=$id",720);
}

function ShowID(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["ShowID"]);
    $rows=wd_rows("SELECT * FROM squid_admin_mysql WHERE ID=?",array($id));
    if(count($rows)==0){
        echo $tpl->_ENGINE_parse_body("<div class='wd-empty-c'><h4>{wd_detail_gone}</h4></div>").wd_style();
        return;
    }
    $ligne=$rows[0];
    list($key,$label,$ico)=wd_level($ligne["severity"]);
    $o=wd_origin($ligne["filename"],$ligne["function"],$ligne["line"]);
    $content=nl2br($tpl->_ENGINE_parse_body(strval($ligne["content"])));
    $exact=htmlspecialchars(strval($tpl->time_to_date(intval($ligne["zDate"]),true)));

    $h=array();
    $h[]="<div class='wd-detail-head'><span class='wd-pill wd-pill-$key'><i class='$ico'></i> $label</span><h3>".strval($ligne["subject"])."</h3></div>";
    if(trim(strip_tags($content))!==""){ $h[]="<div class='wd-pre'>$content</div>"; }
    $h[]="<dl class='wd-facts'>";
    $h[]="<dt>{wd_fact_when}</dt><dd>$exact (".wd_ago($ligne["zDate"]).")</dd>";
    $h[]="<dt>{wd_col_source}</dt><dd>".wd_source_name($o)." <span class='wd-mono'>".htmlspecialchars($o["raw"])."</span></dd>";
    if($o["function"]!==""){ $h[]="<dt>{wd_function}</dt><dd><span class='wd-mono'>".htmlspecialchars($o["function"])."</span></dd>"; }
    $h[]="</dl>";
    $users=new usersMenus();
    if($users->AsSystemAdministrator){
        $h[]="<div class='wd-warnbox'>".wd_t("wd_remove_explain",array("<span class='wd-mono'>".htmlspecialchars($o["raw"])."</span>"))
            ."<br><button type='button' class='wd-btn wd-btn-danger' OnClick=\"Loadjs('$page?remove-events=$id')\"><i class='fas fa-trash-can'></i> {wd_remove_btn}</button></div>";
    }
    echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
}

/** Confirmation : supprimer tous les evenements ecrits au meme endroit (fichier + ligne). */
function remove_events_js(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
    $id=intval($_GET["remove-events"]);
    $tpl->js_dialog_confirm_action("{wd_remove_confirm}","remove-events",$id>0?strval($id):"none",
        "dialogInstance2.close();WdRefresh();");
}

/** Le fichier et la ligne sont relus depuis l'ID : jamais repris du client. */
function remove_events_perform(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $id=intval($_POST["remove-events"]);
    $rows=wd_rows("SELECT filename,line FROM squid_admin_mysql WHERE ID=?",array($id));
    if(count($rows)==0){return;}
    $filename=strval($rows[0]["filename"]);$line=intval($rows[0]["line"]);
    $q=wd_db();
    $q->QUERY_SQL_PARAMS("DELETE FROM squid_admin_mysql WHERE filename=? AND line=?",array($filename,$line));
    if(!$q->ok){echo $q->mysql_error;return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
    admin_tracks("Removed all system events with $filename filename and line $line");
}

// ── Vider le journal ────────────────────────────────────────────────────────

function empty_js(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();die();}
    $total=0;
    $r=wd_rows("SELECT COUNT(*) AS c FROM squid_admin_mysql");
    if(count($r)>0){$total=intval($r[0]["c"]);}
    $tpl->js_dialog_confirm_action(wd_t("wd_empty_confirm",array(number_format($total))),"empty","none","WdRefresh();");
}

/** DELETE et non DROP : la table garde son schema et ses index pour le demon. */
function empty_table(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $q=wd_db();
    $q->QUERY_SQL("DELETE FROM squid_admin_mysql");
    if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Empty table of system events");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
}

// ── Onglet : suivi des administrateurs ──────────────────────────────────────

function wd_admins_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='wd-intro'><h4>{wd_admins_title}</h4><p>{wd_admins_text}</p></div>";
    $h[]="<div class='wd-bar'><div class='wd-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='wd-ad-q' placeholder=\"{wd_search_admins}\" aria-label=\"{wd_search_admins}\" OnKeyUp=\"WdAdKey()\"></div></div>";
    $h[]="<div id='wd-ad-host'></div>";
    $js="<script>
function WdAdApply(){ LoadAjaxSilent('wd-ad-host','$page?admins-table=yes&q='+encodeURIComponent($('#wd-ad-q').val()||'')); }
function WdAdKey(){ if(window.WdAdTimer){clearTimeout(window.WdAdTimer);} window.WdAdTimer=setTimeout(WdAdApply,400); }
WdAdApply();
</script>";
    echo wd_style().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

/**
 * Extracts the legacy "NNN events" / "limit NNN" / "max NNN" row-count
 * directives from a search string and returns array(cleaned search text, max).
 * Filtering itself runs in articarest GET /localadmins/admintracks.
 */
function admintracks_search_params($search){
    $search=trim(strtolower($search));
    $max=200;
    if(preg_match("#([0-9]+)(\s+|=)events#i",$search,$re)){
        $search=trim(str_ireplace("{$re[1]}{$re[2]}events","",$search));
        $max=intval($re[1]);
    }
    if(preg_match("#limit(\s+|=)([0-9]+)#i",$search,$re)){
        $search=trim(str_ireplace("limit{$re[1]}{$re[2]}","",$search));
        $max=intval($re[2]);
    }
    if(preg_match("#max(\s+|=)([0-9]+)#i",$search,$re)){
        $search=trim(str_ireplace("max{$re[1]}{$re[2]}","",$search));
        $max=intval($re[2]);
    }
    if($max<1){$max=200;}
    if($max>1500){$max=1500;}
    return array($search,$max);
}

function wd_admins_table(){
    $tpl=new template_admin();
    list($searchText,$max)=admintracks_search_params(substr(strval($_GET["q"] ?? ""),0,100));
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/localadmins/admintracks?search=".urlencode($searchText)."&max=$max")),true);
    $h=array();
    $results=array();
    if(!is_array($resp) || empty($resp["success"])){
        $err=is_array($resp)?strval($resp["error"] ?? "unknown error"):"daemon unreachable";
        $h[]="<div class='alert alert-danger'>".htmlspecialchars($err)."</div>";
    }else{
        $results=is_array($resp["data"] ?? null)?$resp["data"]:array();
    }
    $rows=array();
    foreach($results as $ligne){ $rows[]=wd_admin_row($tpl,$ligne); }
    if(count($rows)==0){
        $h[]="<div class='awc-card'><div class='wd-empty-c'><div class='wd-empty-ico'><i class='fas fa-user-shield'></i></div><h4>{wd_admins_empty}</h4><p>{wd_admins_empty_text}</p></div></div>";
        echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
        return;
    }
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr><th style='width:30%'>{wd_col_admin}</th><th>{wd_col_did}</th></tr></thead><tbody id='wd-rows-admins'>";
    $h[]=@implode("\n",$rows);
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>".wd_t("wd_showing_actions",array(number_format(count($rows))))."</span><span class='awc-pg' id='wd-pager-admins'></span></div></div></div>";
    $h[]="<script>NoSpinner();</script>";
    echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).awc_pager_js(100,"wd-rows-admins","wd-pager-admins");
}

/** Une action d'administrateur : qui, quand, depuis ou, et l'operation. */
function wd_admin_row($tpl,$ligne):string{
    $meta=array();
    $meta[]=wd_when($ligne["time"] ?? 0);
    if(strlen(trim(strval($ligne["ipaddr"] ?? "")))>0){$meta[]="<span class='wd-mono'>".htmlspecialchars(strval($ligne["ipaddr"]))."</span>";}
    // L'operation reste non echappee : les traces d'audit portent du balisage volontaire (valeurs en gras).
    $operation=$tpl->_ENGINE_parse_body(strval($ligne["operation"] ?? ""));
    $h ="<tr>";
    $h.="<td><div class='awc-ident'><span class='awc-ico'><i class='".ico_user."'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars(strval($ligne["username"] ?? ""))."</span>";
    $h.="<div class='awc-meta'>".@implode(" &middot; ",$meta)."</div></span></div></td>";
    $h.="<td>$operation</td>";
    $h.="</tr>";
    return $h;
}

// ── Onglet : tentatives de connexion refusees ───────────────────────────────

function failed_logging_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{failedloginattempts}","$page?failed-logging=yes",1024);
}
function failed_license_server_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("License server","$page?licenseserver-popup=yes",800);
}
function failed_license_server_popup(){
    $tpl=new template_admin();
    $LicensingServerError=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicensingServerError"));
    echo $tpl->div_error($LicensingServerError);
}

/** Aussi ouvert en modale depuis l'icone d'alerte de la barre du haut : pas de TINY ici. */
function wd_logins_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $b=wd_rows("SELECT COUNT(*) AS c, COUNT(DISTINCT NULLIF(host,'')) AS h, MAX(timestamp) AS t FROM btmp_records");
    $n=0;$hosts=0;$last=0;
    if(count($b)>0){$n=intval($b[0]["c"]);$hosts=intval($b[0]["h"]);$t=strtotime(strval($b[0]["t"]));$last=$t?$t:0;}
    $h=array();
    $h[]="<div class='wd-intro'><h4>{wd_logins_title}</h4><p>{wd_logins_text}</p>";
    if($n>0){
        $h[]="<div class='wd-sum'><i class='fas fa-user-lock'></i>".wd_t("wd_logins_sum",array(number_format($n),number_format($hosts),wd_when($last)))."</div>";
    }
    $h[]="</div>";
    if($n==0){
        $h[]="<div class='awc-card' style='margin-top:14px'><div class='wd-empty-c'><div class='wd-empty-ico'><i class='fas fa-shield-halved'></i></div><h4>{wd_logins_empty}</h4><p>{wd_logins_empty_text}</p></div></div>";
        echo wd_style().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
        return;
    }
    $h[]="<div class='wd-bar'><div class='wd-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='wd-lg-q' placeholder=\"{wd_search_logins}\" aria-label=\"{wd_search_logins}\" OnKeyUp=\"WdLgKey()\"></div></div>";
    $h[]="<div id='wd-lg-host'></div>";
    $js="<script>
function WdLgApply(){ LoadAjaxSilent('wd-lg-host','$page?logins-table=yes&q='+encodeURIComponent($('#wd-lg-q').val()||'')); }
function WdLgKey(){ if(window.WdLgTimer){clearTimeout(window.WdLgTimer);} window.WdLgTimer=setTimeout(WdLgApply,400); }
WdLgApply();
</script>";
    echo wd_style().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

function wd_logins_table(){
    $tpl=new template_admin();
    $q=substr(trim(strtolower(strval($_GET["q"] ?? ""))),0,100);
    $where="";$params=array();
    if($q!==""){
        $s="%".str_replace(array("%","_"),"",str_replace("*","%",$q))."%";
        $where="WHERE (user LIKE ? OR terminal LIKE ? OR timestamp LIKE ? OR host LIKE ?)";
        $params=array($s,$s,$s,$s);
    }
    $results=wd_rows("SELECT user,host,terminal,timestamp FROM btmp_records $where ORDER BY timestamp DESC LIMIT 1500",$params);
    $rows=array();
    foreach($results as $ligne){ $rows[]=wd_login_row($ligne); }
    $h=array();
    if(count($rows)==0){
        $h[]="<div class='awc-card'><div class='wd-empty-c'><div class='wd-empty-ico'><i class='fas fa-filter'></i></div><h4>".wd_t("wd_nomatch_q",array("“".htmlspecialchars($q)."”"))."</h4></div></div>";
        echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
        return;
    }
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr><th>{wd_col_account}</th><th>{wd_col_from}</th><th>{wd_col_method}</th></tr></thead><tbody id='wd-rows-btmp'>";
    $h[]=@implode("\n",$rows);
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>".wd_t("wd_showing_attempts",array(number_format(count($rows))))."</span><span class='awc-pg' id='wd-pager-btmp'></span></div></div></div>";
    $h[]="<script>NoSpinner();</script>";
    echo wd_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).awc_pager_js(100,"wd-rows-btmp","wd-pager-btmp");
}

/** Methode d'acces en mots courants ; le terminal brut reste en sous-titre. */
function wd_terminal_name($terminal):string{
    $t=strtolower(strval($terminal));
    if(strpos($t,"ssh")===0){return "{wd_term_ssh}";}
    if(preg_match('#^(tty|console)#',$t)){return "{wd_term_console}";}
    return "{wd_term_other}";
}

/** Une tentative refusee : compte vise, adresse d'origine, methode. */
function wd_login_row($ligne):string{
    $ts=strtotime(strval($ligne["timestamp"]));
    $h ="<tr>";
    $h.="<td><div class='awc-ident'><span class='wd-ico wd-ico-crit'><i class='".ico_user."'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars(strval($ligne["user"]))."</span>";
    $h.="<div class='awc-meta'>".wd_when($ts?$ts:0)."</div></span></div></td>";
    $host=trim(strval($ligne["host"]));
    $h.="<td class='wd-mono' style='font-size:13px;color:#111d23' nowrap>".($host===""?"<span class='wd-dash'>—</span>":htmlspecialchars($host))."</td>";
    $h.="<td><div class='wd-srcname'>".wd_terminal_name($ligne["terminal"])."</div><div class='wd-mono'>".htmlspecialchars(strval($ligne["terminal"]))."</div></td>";
    $h.="</tr>";
    return $h;
}
