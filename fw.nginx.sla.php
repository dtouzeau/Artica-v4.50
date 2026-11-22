<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() : feuille partagee (le rapport par domaine s'en sert encore)
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// ca_status()/ca_grade_color()/ca_warnings() partages avec fw.nginx.slas.php
include_once(dirname(__FILE__)."/ressources/class.cloudaudit.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Audit de securite externe des sites publies (maquette
// stitch_artica_external_security_audit_redesign). Donnees : /cloud-audit/status
// et /cloud-audit/report du daemon reverse-proxy, qui relaie le rapport du
// scanner cloud-audit (un scan toutes les 4 h depuis Internet). Rien n'est
// invente : un chiffre absent du rapport s'affiche « — ».
// L'onglet Backend (journal nginx_sla) vit dans fw.nginx.slas.events.php.

if(isset($_GET["build"])){ecsa_build();exit;}
if(isset($_GET["status"])){ecsa_build();exit;}   // ancien point d'entree (onglet de fw.nginx.slas.php)
if(isset($_GET["ca-findings-js"])){ca_findings_js();exit;}
if(isset($_GET["ca-findings-popup"])){ca_findings_popup();exit;}
if(isset($_POST["ca-sync"])){ca_sync();exit;}
if(isset($_POST["ca-scan"])){ca_scan();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{ecsa_title}","fas fa-shield-halved","{ecsa_explain}","$page?build=yes",
        "reverse-sla","progress-nginx-slar",false,"table-nginx-sla");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{ecsa_title}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ── Actions ─────────────────────────────────────────────────────────────────

function ecsa_can_act():bool{
    $users=new usersMenus();
    return $users->AsWebMaster || $users->AsSystemAdministrator;
}

// Succes = aucune sortie ; erreur = texte, affiche par la page dans une SweetAlert.
function ca_sync():bool{
    $tpl=new template_admin();
    if(!ecsa_can_act()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    // Sync() cote daemon peut prendre jusqu'a 2 min (syncTimeout).
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT=150;
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/cloud-audit/sync",array());
    $j=json_decode($raw,true);
    if(!is_array($j)){echo "cloud-audit: no answer from daemon";return false;}
    admin_tracks("External security audit: list of published sites sent to the audit");
    if(strlen($j["state"]["last_sync_error"]??"")>0){echo $j["state"]["last_sync_error"];}
    return true;
}

function ca_scan():bool{
    $tpl=new template_admin();
    if(!ecsa_can_act()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/cloud-audit/scan",array());
    $j=json_decode($raw,true);
    if(!is_array($j)){echo "cloud-audit: no answer from daemon";return false;}
    if(!($j["Status"]??false)){echo $j["Error"]??"error";return false;}
    admin_tracks("External security audit: new analysis requested");
    // "already_running" est une INFORMATION : prefixe INFO: pour la page.
    if(($j["state"]??"")=="already_running"){echo "INFO:".$tpl->_ENGINE_parse_body("{ecsa_already_running}");}
    return true;
}

// ── Helpers ─────────────────────────────────────────────────────────────────

function ecsa_t($token,$a="",$b="",$c=""):string{
    if(!isset($GLOBALS["ECSA_T"])){$GLOBALS["ECSA_T"]=array();}
    if(!isset($GLOBALS["ECSA_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["ECSA_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2","%3"),array($a,$b,$c),$GLOBALS["ECSA_T"][$token]);
}

function ecsa_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function ecsa_ts($s):int{
    $s=trim((string)$s);
    if($s===""){return 0;}
    $t=strtotime($s);
    return $t===false?0:intval($t);
}

// "dans 2 h 10 min" / "il y a 3 h" ; la date exacte va en info-bulle.
function ecsa_rel($ts):string{
    $d=intval($ts)-time();
    $a=abs($d);
    if($a<60){$v=ecsa_t("ecsa_u_now");return $v;}
    if($a<3600){$v=intval($a/60)." min";}
    elseif($a<86400){$h=intval($a/3600);$m=intval(($a%3600)/60);$v="$h h".($m>0?" $m min":"");}
    else{$v=intval($a/86400)." d";}
    return $d>0?ecsa_t("ecsa_in_x",$v):ecsa_t("ecsa_x_ago",$v);
}

function ecsa_date($ts):string{
    return $ts>0?date("M j, H:i",$ts):"";
}

// Libelle en mots de la note (echelle cloud-audit A>=90 … F<50).
function ecsa_grade_word($grade):string{
    $map=array("A"=>"ecsa_g_a","B"=>"ecsa_g_b","C"=>"ecsa_g_c","D"=>"ecsa_g_d","F"=>"ecsa_g_f");
    $g=strtoupper((string)$grade);
    return isset($map[$g])?ecsa_t($map[$g]):"";
}

function ecsa_grade_class($grade):string{
    $c=ca_grade_color($grade);
    if($c=="green"){return "ok";}
    if($c=="orange"){return "warn";}
    if($c=="red"){return "err";}
    return "off";
}

// Date du scan precedent : l'identifiant cloud-audit est YYYYMMDDTHHMMSSZ-xxxxxx.
function ecsa_scanid_ts($id):int{
    if(!preg_match('#^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z#',(string)$id,$m)){return 0;}
    return intval(gmmktime(intval($m[4]),intval($m[5]),intval($m[6]),intval($m[2]),intval($m[3]),intval($m[1])));
}

// ── Modele : calcule une fois, lu par tous les widgets ─────────────────────

function ecsa_model():array{
    $m=array("error"=>"","enabled"=>true,"breaker"=>false,"breaker_until"=>0,"warnings"=>array(),
        "report"=>null,"delta"=>null,"next_scan"=>0,"running"=>false,"last_scan"=>0,"egress"=>"",
        "domains"=>array(),"notes"=>array(),"sites"=>0,"sev"=>array("critical"=>0,"high"=>0,"medium"=>0,"low"=>0,"info"=>0),
        "checked"=>0,"failed"=>0,"pending"=>0,"cert_invalid"=>0,"cert_soon"=>0,"cert_valid"=>0,"cert_min"=>-1,
        "dns_ok"=>0,"dns_bad"=>0,"dns_known"=>0);
    $st=ca_status();
    if(isset($st["_error"])){
        $m["error"]=$st["_error"]=="daemon_outdated"?"{cloud_audit_daemon_outdated}":"{cloud_audit_daemon_unreachable}";
        if(strlen($st["_detail"]??"")>0){$m["error"].=" (".ecsa_h($st["_detail"]).")";}
        return $m;
    }
    $s=isset($st["state"]) && is_array($st["state"])?$st["state"]:array();
    $m["enabled"]=!empty($st["enabled"]);
    $m["breaker"]=!empty($st["breaker_open"]);
    $m["breaker_until"]=intval($st["breaker_until"]??0);
    foreach(array("last_sync_error"=>"{cloud_audit_last_sync_error}","last_fetch_error"=>"{cloud_audit_last_fetch_error}") as $k=>$tok){
        if(strlen($s[$k]??"")>0){$m["warnings"][]=$tok.": ".ecsa_h($s[$k]);}
    }
    foreach(array("state_error","report_error") as $k){
        if(strlen($st[$k]??"")>0){$m["warnings"][]="{error}: ".ecsa_h($st[$k]);}
    }
    $m["report"]=isset($st["report"]) && is_array($st["report"])?$st["report"]:null;
    if($m["report"]!==null && isset($m["report"]["delta"]) && is_array($m["report"]["delta"]) && strlen($m["report"]["delta"]["previous_scan_id"]??"")>0){
        $m["delta"]=$m["report"]["delta"];
    }
    $m["next_scan"]=ecsa_ts($s["next_scan"]??"");
    $m["running"]=!empty($s["remote_running"]);
    $m["last_scan"]=$m["report"]!==null?ecsa_ts($m["report"]["finished_at"]??""):0;
    $m["egress"]=(string)($s["egress_ip"]??"");
    if($m["report"]!==null){
        foreach($m["sev"] as $k=>$v){$m["sev"][$k]=intval($m["report"][$k]??0);}
    }

    // Rapport detaille : site_id => domaine => bloc.
    $report=array();
    if($m["report"]!==null){
        $rep=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cloud-audit/report"),true);
        if(is_array($rep) && isset($rep["sites"]) && is_array($rep["sites"])){
            foreach($rep["sites"] as $rs){
                foreach((isset($rs["domains"]) && is_array($rs["domains"]))?$rs["domains"]:array() as $d){
                    $report[intval($rs["site_id"]??0)][(string)($d["domain"]??"")]=$d;
                }
            }
        }
    }
    $sites=isset($s["sites"]) && is_array($s["sites"])?$s["sites"]:array();
    $m["sites"]=count($sites);
    foreach($sites as $site){
        $sid=intval($site["site_id"]??0);
        $name=get_servicename($sid);
        if(strlen($site["push_error"]??"")>0){
            $m["notes"][]=array("err",ecsa_t("ecsa_note_push","<strong>".ecsa_h($name)."</strong>",ecsa_h($site["push_error"])));
        }
        foreach((isset($site["skipped"]) && is_array($site["skipped"]))?$site["skipped"]:array() as $sk){
            $m["notes"][]=array("info",ecsa_t("ecsa_note_skipped","<strong>".ecsa_h($sk["host"]??"")."</strong>",ecsa_h($name),ecsa_h($sk["reason"]??"")));
        }
        foreach((isset($site["domains"]) && is_array($site["domains"]))?$site["domains"]:array() as $dom){
            $m["domains"][]=ecsa_domain($sid,$name,$dom,$report[$sid][(string)($dom["domain"]??"")] ?? null,$m);
        }
    }
    return $m;
}

// Une ligne de domaine : etat local (DNS) + bloc du rapport. Met a jour les compteurs.
function ecsa_domain($sid,$name,$dom,$r,&$m):array{
    $d=array("sid"=>$sid,"site"=>$name,"domain"=>(string)($dom["domain"]??""),"state"=>"pending","error"=>"",
        "score"=>-1,"grade"=>"","tls"=>null,"lat"=>-1,"lat7"=>-1,"sev"=>array("critical"=>0,"high"=>0,"medium"=>0,"low"=>0,"info"=>0),
        "ips"=>(isset($dom["resolved_ips"]) && is_array($dom["resolved_ips"]))?$dom["resolved_ips"]:array(),
        "dns"=>intval($dom["matches_egress"] ?? -1),"attention"=>false);
    if($d["dns"]===1){$m["dns_ok"]++;$m["dns_known"]++;}
    elseif($d["dns"]===0){$m["dns_bad"]++;$m["dns_known"]++;$d["attention"]=true;}
    if($r!==null){
        $status=(string)($r["status"]??"");
        if($status=="completed"){$d["state"]="checked";}
        elseif($status=="failed"){$d["state"]="failed";}
        elseif($status=="blocked"){$d["state"]="blocked";}
        $d["error"]=trim((string)($r["error"]??""));
        if($d["state"]=="checked"){
            $d["score"]=intval($r["score"]??0);
            $d["grade"]=strtoupper((string)($r["grade"]??""));
            if(isset($r["tls"]) && is_array($r["tls"])){
                $d["tls"]=array("version"=>(string)($r["tls"]["version"]??""),"days"=>intval($r["tls"]["days_remaining"]??0),"valid"=>!empty($r["tls"]["certificate_valid"]));
            }
            if(isset($r["latency"]) && is_array($r["latency"])){
                $d["lat"]=intval($r["latency"]["total_ms"]??-1);
                if(isset($r["latency"]["history_7d"]) && is_array($r["latency"]["history_7d"]) && intval($r["latency"]["history_7d"]["samples"]??0)>1){
                    $d["lat7"]=intval($r["latency"]["history_7d"]["avg_ms"]??-1);
                }
            }
            foreach((isset($r["findings"]) && is_array($r["findings"]))?$r["findings"]:array() as $f){
                $k=strtolower((string)($f["severity"]??"info"));
                if(!isset($d["sev"][$k])){$k="info";}
                $d["sev"][$k]++;
            }
        }
    }
    if($d["state"]=="checked"){$m["checked"]++;}
    elseif($d["state"]=="failed" || $d["state"]=="blocked"){$m["failed"]++;$d["attention"]=true;}
    else{$m["pending"]++;}
    if($d["tls"]!==null){
        if(!$d["tls"]["valid"]){$m["cert_invalid"]++;$d["attention"]=true;}
        elseif($d["tls"]["days"]<=30){$m["cert_soon"]++;$d["attention"]=true;$m["cert_valid"]++;}
        else{$m["cert_valid"]++;}
        if($d["tls"]["valid"] && ($m["cert_min"]<0 || $d["tls"]["days"]<$m["cert_min"])){$m["cert_min"]=$d["tls"]["days"];}
    }
    // « A traiter » = urgent ou important ; les points moderes restent visibles
    // dans la ligne et le rapport, sans faire basculer le domaine.
    if($d["sev"]["critical"]+$d["sev"]["high"]>0){$d["attention"]=true;}
    return $d;
}

// Priorites « Start here » : au plus trois faits reels, du plus grave au moins grave.
function ecsa_priorities($m):array{
    $c=array();
    foreach($m["domains"] as $i=>$d){
        if($d["state"]=="failed"){$c[]=array(10,$i,"failed");}
        if($d["state"]=="blocked"){$c[]=array(9,$i,"blocked");}
        if($d["tls"]!==null && !$d["tls"]["valid"]){$c[]=array(8,$i,"cert_invalid");}
        if($d["sev"]["critical"]>0){$c[]=array(7+$d["sev"]["critical"]/100,$i,"urgent");}
        if($d["tls"]!==null && $d["tls"]["valid"] && $d["tls"]["days"]<=14){$c[]=array(6,$i,"cert_soon");}
        if($d["dns"]===0){$c[]=array(5,$i,"dns");}
        if($d["sev"]["high"]>0 && $d["sev"]["critical"]==0){$c[]=array(4+$d["sev"]["high"]/100,$i,"important");}
        if($d["tls"]!==null && $d["tls"]["valid"] && $d["tls"]["days"]>14 && $d["tls"]["days"]<=30){$c[]=array(3,$i,"cert_soon");}
    }
    usort($c,function($a,$b){ if($a[0]==$b[0]){return 0;} return $a[0]>$b[0]?-1:1; });
    $out=array();$seen=array();
    foreach($c as $x){
        if(isset($seen[$x[1]])){continue;}
        $seen[$x[1]]=true;
        $out[]=array($m["domains"][$x[1]],$x[2]);
        if(count($out)>=3){break;}
    }
    return $out;
}

// ── Contenu ─────────────────────────────────────────────────────────────────

function ecsa_build(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=ecsa_model();
    $h=array();
    $h[]="<div class='ecsa' id='ecsa-root'>";
    if($m["error"]!==""){
        $h[]="<div class='ecsa-state ecsa-state-err'><i class='fas fa-plug-circle-xmark'></i><div><h4>{ecsa_daemon_title}</h4><p>".$m["error"]."</p></div></div>";
    }elseif(!$m["enabled"]){
        $h[]=ecsa_state_disabled();
    }else{
        $h[]=ecsa_hero($m);
        $h[]=ecsa_breaker($m);
        $h[]=ecsa_warnings($m);
        $h[]=ecsa_delta($m);
        $h[]=$m["report"]===null?ecsa_first_audit():ecsa_tiles($m);
        $h[]=ecsa_start_here($m);
        $h[]=ecsa_notes($m);
        $h[]=ecsa_domains_card($m);
    }
    $h[]="</div>";

    $topbuttons=array();
    if(ecsa_can_act()){
        $topbuttons[]=array("EcsaScan();",ico_play,"{ecsa_analyze_now}");
        $topbuttons[]=array("EcsaSync();",ico_refresh,"{ecsa_send_sites}");
    }
    $TINY=array("TITLE"=>"{ecsa_title}","ICO"=>"fas fa-shield-halved","EXPL"=>"{ecsa_explain}","URL"=>"reverse-sla",
        "BUTTONS"=>$tpl->table_buttons($topbuttons));
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    echo ecsa_style().ca_findings_css().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();".ecsa_js($m)."$tiny</script>";
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function ecsa_state_disabled():string{
    return "<div class='ecsa-state'><i class='fas fa-power-off'></i><div><h4>{ecsa_disabled_title}</h4><p>{ecsa_disabled_text}</p></div></div>";
}

function ecsa_ring($score,$grade,$size=140):string{
    $pct=max(0,min(100,intval($score)));
    $r=52;$c=2*M_PI*$r;
    $dash=sprintf("%.1f %.1f",$c*$pct/100,$c);
    $cls=ecsa_grade_class($grade);
    $label=ecsa_h(ecsa_t("ecsa_ring_aria",(string)$pct,$grade));
    return "<svg class='ecsa-ring ecsa-ring-$cls' width='$size' height='$size' viewBox='0 0 120 120' role='img' aria-label=\"$label\">"
        ."<circle cx='60' cy='60' r='$r' class='ecsa-ring-bg'/>"
        .($pct>0?"<circle cx='60' cy='60' r='$r' class='ecsa-ring-fg' stroke-dasharray='$dash' transform='rotate(-90 60 60)'/>":"")
        ."<text x='60' y='60' class='ecsa-ring-g'>".ecsa_h($grade!==""?$grade:"—")."</text>"
        ."<text x='60' y='82' class='ecsa-ring-s'>$pct/100</text></svg>";
}

function ecsa_hero($m):string{
    $chips=array();
    $chips[]="<span class='ecsa-chip'><i class='fas fa-globe'></i> {ecsa_chip_outside}</span>";
    $chips[]="<span class='ecsa-chip'><i class='fas fa-feather'></i> {ecsa_chip_nothing}</span>";
    $chips[]="<span class='ecsa-chip'><i class='fas fa-shield-halved'></i> {ecsa_chip_readonly}</span>";
    if($m["running"]){
        $chips[]="<span class='ecsa-chip ecsa-chip-live'><i class='fas fa-spinner fa-spin'></i> {ecsa_chip_running}</span>";
    }elseif($m["next_scan"]>0){
        $chips[]="<span class='ecsa-chip' title='".ecsa_h(date("Y-m-d H:i",$m["next_scan"]))."'><i class='fas fa-clock'></i> ".ecsa_t("ecsa_chip_next",ecsa_rel($m["next_scan"]))."</span>";
    }
    $right="";
    if($m["report"]!==null){
        $grade=strtoupper((string)($m["report"]["grade"]??""));
        $delta="";
        if($m["delta"]!==null){
            $dc=intval($m["delta"]["score_change"]??0);
            if($dc>0){$delta="<span class='ecsa-dpill ecsa-dpill-up'><i class='fas fa-arrow-up'></i> ".ecsa_t("ecsa_delta_up","+$dc")."</span>";}
            elseif($dc<0){$delta="<span class='ecsa-dpill ecsa-dpill-down'><i class='fas fa-arrow-down'></i> ".ecsa_t("ecsa_delta_down","$dc")."</span>";}
            else{$delta="<span class='ecsa-dpill'><i class='fas fa-equals'></i> {ecsa_delta_same}</span>";}
        }
        $right="<div class='ecsa-hero-score'>".ecsa_ring(intval($m["report"]["score"]??0),$grade)
            ."<div class='ecsa-hero-score-txt'><div class='ecsa-eyebrow-s'>{ecsa_overall}</div><div class='ecsa-gword'>".ecsa_grade_word($grade)."</div>$delta</div></div>";
    }
    $btns="";
    if(ecsa_can_act()){
        $btns="<div class='ecsa-hero-btns'><button type='button' class='ecsa-btn ecsa-btn-primary' OnClick=\"EcsaScan();\"><i class='fas fa-play'></i> {ecsa_analyze_now}</button>"
            ."<button type='button' class='ecsa-btn' OnClick=\"EcsaSync();\"><i class='fas fa-arrows-rotate'></i> {ecsa_send_sites}</button></div>";
    }
    return "<div class='ecsa-hero'><div class='ecsa-hero-l'><span class='ecsa-eyebrow'>{ecsa_eyebrow}</span>"
        ."<h2>{ecsa_hero_title}</h2><p>{ecsa_hero_text}</p><div class='ecsa-chips'>".implode("",$chips)."</div></div>"
        ."<div class='ecsa-hero-r'>$right$btns</div></div>";
}

function ecsa_breaker($m):string{
    if(!$m["breaker"]){return "";}
    $when=$m["breaker_until"]>0?date("H:i",$m["breaker_until"]):"";
    $btn=ecsa_can_act()?"<button type='button' class='ecsa-btn ecsa-btn-warn' OnClick=\"EcsaSync();\">{ecsa_try_now}</button>":"";
    return "<div class='ecsa-banner ecsa-banner-warn'><i class='fas fa-cloud-bolt'></i><div class='ecsa-banner-txt'>".ecsa_t("ecsa_breaker",$when)."</div>$btn</div>";
}

function ecsa_warnings($m):string{
    if(count($m["warnings"])==0){return "";}
    $h=array();
    foreach($m["warnings"] as $w){$h[]="<div class='ecsa-banner ecsa-banner-warn'><i class='fas fa-triangle-exclamation'></i><div class='ecsa-banner-txt'>$w</div></div>";}
    return implode("",$h);
}

function ecsa_delta($m):string{
    if($m["delta"]===null){return "";}
    $d=$m["delta"];
    $res=intval($d["resolved_findings"]??0);
    $new=intval($d["new_findings"]??0);
    $per=intval($d["persistent_findings"]??0);
    $prev=ecsa_scanid_ts($d["previous_scan_id"]??"");
    $cap=$prev>0?"<div class='ecsa-delta-cap'>".ecsa_t("ecsa_delta_cap",ecsa_date($prev))."</div>":"";
    return "<div class='ecsa-card ecsa-delta'><div class='ecsa-delta-row'>"
        ."<div class='ecsa-dt ecsa-dt-ok'><span class='ecsa-dt-ico'><i class='fas fa-check'></i></span><div><strong>".ecsa_t("ecsa_dt_fixed",number_format($res))."</strong><div>{ecsa_dt_fixed_sub}</div></div></div>"
        ."<div class='ecsa-dt".($new>0?" ecsa-dt-warn":"")."'><span class='ecsa-dt-ico'><i class='fas fa-plus'></i></span><div><strong>".ecsa_t("ecsa_dt_new",number_format($new))."</strong><div>".($new>0?"{ecsa_dt_new_sub}":"{ecsa_dt_new_none}")."</div></div></div>"
        ."<div class='ecsa-dt'><span class='ecsa-dt-ico'><i class='fas fa-hourglass-half'></i></span><div><strong>".ecsa_t("ecsa_dt_open",number_format($per))."</strong><div>{ecsa_dt_open_sub}</div></div></div>"
        ."</div>$cap</div>";
}

function ecsa_first_audit():string{
    $btn=ecsa_can_act()?"<button type='button' class='ecsa-btn ecsa-btn-primary' OnClick=\"EcsaScan();\"><i class='fas fa-play'></i> {ecsa_analyze_now}</button>":"";
    return "<div class='ecsa-card ecsa-empty'><div class='ecsa-empty-ico'><i class='fas fa-satellite-dish'></i></div>"
        ."<h4>{ecsa_first_title}</h4><p>{ecsa_first_text}</p>$btn</div>";
}

function ecsa_tiles($m):string{
    return "<div class='ecsa-tiles'>".ecsa_tile_sites($m).ecsa_tile_fix($m).ecsa_tile_certs($m).ecsa_tile_egress($m)."</div>";
}

function ecsa_tile_sites($m):string{
    $n=count($m["domains"]);
    $foot="<div class='ecsa-tile-f'><span class='ecsa-ok'><i class='fas fa-circle'></i> ".ecsa_t("ecsa_n_checked",number_format($m["checked"]))."</span>";
    if($m["failed"]>0){$foot.="<span class='ecsa-err'><i class='fas fa-circle'></i> ".ecsa_t("ecsa_n_failed",number_format($m["failed"]))."</span>";}
    elseif($m["pending"]>0){$foot.="<span class='ecsa-muted'>".ecsa_t("ecsa_n_pending",number_format($m["pending"]))."</span>";}
    $foot.="</div>";
    return "<div class='ecsa-tile'><div class='ecsa-tile-h'><div class='ecsa-tile-l'>{ecsa_t_sites}</div><span class='ecsa-tile-ico'><i class='fas fa-globe'></i></span></div>"
        ."<div class='ecsa-tile-v'>".ecsa_t("ecsa_n_domains",number_format($n))."</div>"
        ."<div class='ecsa-tile-s'>".ecsa_t("ecsa_on_sites",number_format($m["sites"]))."</div>$foot</div>";
}

function ecsa_tile_fix($m):string{
    $s=$m["sev"];
    $total=$s["critical"]+$s["high"]+$s["medium"]+$s["low"];
    if($total==0){
        return "<div class='ecsa-tile'><div class='ecsa-tile-h'><div class='ecsa-tile-l'>{ecsa_t_fix}</div><span class='ecsa-tile-ico ecsa-ico-ok'><i class='fas fa-trophy'></i></span></div>"
            ."<div class='ecsa-tile-v ecsa-ok'>{ecsa_nothing_to_fix}</div><div class='ecsa-tile-s'>{ecsa_nothing_to_fix_sub}</div></div>";
    }
    $bar=array();$legend=array();
    foreach(array("critical"=>"ecsa_sev_c","high"=>"ecsa_sev_h","medium"=>"ecsa_sev_m","low"=>"ecsa_sev_l") as $k=>$tok){
        if($s[$k]==0){continue;}
        $w=sprintf("%.2f",100*$s[$k]/$total);
        $bar[]="<span class='ecsa-bar-$k' style='width:$w%'></span>";
        $legend[]="<span class='ecsa-lg-$k'>".number_format($s[$k])." ".ecsa_t($tok)."</span>";
    }
    $badge=($s["critical"]+$s["high"])>0?"<span class='ecsa-badge-warn'>{ecsa_action_required}</span>":"";
    return "<div class='ecsa-tile'><div class='ecsa-tile-h'><div class='ecsa-tile-l'>{ecsa_t_fix}</div>$badge</div>"
        ."<div class='ecsa-tile-v'>".ecsa_t("ecsa_n_findings",number_format($total))."</div>"
        ."<div class='ecsa-bar' role='img' aria-label='".ecsa_h(strip_tags(implode(", ",$legend)))."'>".implode("",$bar)."</div>"
        ."<div class='ecsa-tile-f ecsa-legend'>".implode("",$legend)."</div></div>";
}

function ecsa_tile_certs($m):string{
    $parts=array();
    if($m["cert_invalid"]>0){$parts[]=ecsa_t("ecsa_c_invalid",number_format($m["cert_invalid"]));}
    if($m["cert_soon"]>0){$parts[]=ecsa_t("ecsa_c_soon",number_format($m["cert_soon"]));}
    $warn=count($parts)>0;
    $v=$warn?implode(" · ",$parts):"{ecsa_c_all_valid}";
    $known=$m["cert_valid"]+$m["cert_invalid"];
    $sub=$known>0?ecsa_t("ecsa_c_valid_n",number_format($m["cert_valid"]),number_format($known)):"—";
    $foot=$m["cert_min"]>=0?"<div class='ecsa-tile-f'><span class='".($m["cert_min"]<=30?"ecsa-warn":"ecsa-muted")."'>".ecsa_t("ecsa_c_earliest",number_format($m["cert_min"]))."</span></div>":"";
    return "<div class='ecsa-tile'><div class='ecsa-tile-h'><div class='ecsa-tile-l'>{ecsa_t_certs}</div><span class='ecsa-tile-ico".($warn?" ecsa-ico-warn":" ecsa-ico-ok")."'><i class='fas fa-lock'></i></span></div>"
        ."<div class='ecsa-tile-v".($m["cert_invalid"]>0?" ecsa-err":"")."'>$v</div><div class='ecsa-tile-s'>$sub</div>$foot</div>";
}

function ecsa_tile_egress($m):string{
    $ip=$m["egress"]!==""?"<span class='ecsa-ipbig'>".ecsa_h($m["egress"])."</span>":"<span class='ecsa-muted'>—</span>";
    $sub=$m["dns_known"]>0?ecsa_t("ecsa_dns_n",number_format($m["dns_ok"]),number_format($m["dns_known"])):"";
    $foot=$m["dns_bad"]>0?"<div class='ecsa-tile-f'><span class='ecsa-err'>".ecsa_t("ecsa_dns_bad",number_format($m["dns_bad"]))."</span>"
        ."<a href='javascript:void(0)' class='ecsa-link' OnClick=\"EcsaFilter('attention');\">{ecsa_show_them}</a></div>":"";
    return "<div class='ecsa-tile'><div class='ecsa-tile-h'><div class='ecsa-tile-l'>{ecsa_t_egress}</div><span class='ecsa-tile-ico'><i class='fas fa-network-wired'></i></span></div>"
        ."<div class='ecsa-tile-v'>$ip</div><div class='ecsa-tile-s'>$sub</div>$foot</div>";
}

function ecsa_start_here($m):string{
    $p=ecsa_priorities($m);
    if(count($p)==0){return "";}
    $page=CurrentPageName();
    $rows=array();
    foreach($p as $x){
        list($d,$kind)=$x;
        $dom=ecsa_h($d["domain"]);
        $tag="";$text="";$ico="fas fa-circle-exclamation";$cls="err";
        switch($kind){
            case "failed": $tag=ecsa_t("ecsa_st_failed");$text=$d["error"]!==""?ecsa_h($d["error"]):ecsa_t("ecsa_sp_failed");$ico="fas fa-plug-circle-xmark";break;
            case "blocked": $tag=ecsa_t("ecsa_st_blocked");$text=ecsa_t("ecsa_sp_blocked");$ico="fas fa-hand";$cls="warn";break;
            case "cert_invalid": $tag=ecsa_t("ecsa_tag_cert");$text=ecsa_t("ecsa_sp_cert_invalid");$ico="fas fa-lock-open";break;
            case "urgent": $tag=ecsa_t("ecsa_tag_n",number_format($d["sev"]["critical"]),ecsa_t("ecsa_sev_c"));
                $text=ecsa_t("ecsa_sp_findings",number_format($d["sev"]["critical"]+$d["sev"]["high"]+$d["sev"]["medium"]));$ico="fas fa-bolt";break;
            case "important": $tag=ecsa_t("ecsa_tag_n",number_format($d["sev"]["high"]),ecsa_t("ecsa_sev_h"));
                $text=ecsa_t("ecsa_sp_findings",number_format($d["sev"]["high"]+$d["sev"]["medium"]));$ico="fas fa-bolt";$cls="warn";break;
            case "cert_soon": $tag=ecsa_t("ecsa_tag_cert");$text=ecsa_t("ecsa_sp_cert_soon",number_format($d["tls"]["days"]));$ico="fas fa-calendar-xmark";$cls="warn";break;
            case "dns": $tag=ecsa_t("ecsa_tag_dns");
                $text=ecsa_t("ecsa_sp_dns","<span class='ecsa-mono ecsa-err'>".ecsa_h(implode(", ",$d["ips"]))."</span>","<span class='ecsa-mono'>".ecsa_h($m["egress"])."</span>");$ico="fas fa-route";$cls="warn";break;
        }
        $btn="";
        if($d["state"]=="checked"){
            $js="Loadjs('$page?ca-findings-js=yes&siteid={$d["sid"]}&domain=".urlencode($d["domain"])."');";
            $btn="<button type='button' class='ecsa-btn".($kind=="urgent"||$kind=="important"?" ecsa-btn-primary":"")."' OnClick=\"$js\">{ecsa_see_report} <i class='fas fa-chevron-right'></i></button>";
        }
        $rows[]="<div class='ecsa-sh-row'><span class='ecsa-sh-ico ecsa-sh-$cls'><i class='$ico'></i></span>"
            ."<div class='ecsa-sh-body'><div><span class='ecsa-mono ecsa-strong'>$dom</span> <span class='ecsa-tag ecsa-tag-$cls'>$tag</span></div><div class='ecsa-sh-text'>$text</div></div>$btn</div>";
    }
    return "<div class='ecsa-card ecsa-sh'><div class='ecsa-sh-h'><span class='ecsa-sh-dot'></span>{ecsa_start_here}</div>".implode("",$rows)."</div>";
}

function ecsa_notes($m):string{
    if(count($m["notes"])==0){return "";}
    $h=array();
    foreach($m["notes"] as $n){
        $ico=$n[0]=="err"?"fa-triangle-exclamation":"fa-circle-minus";
        $btn=$n[0]=="err" && ecsa_can_act()?"<button type='button' class='ecsa-link' OnClick=\"EcsaSync();\">{ecsa_try_again}</button>":"";
        $h[]="<div class='ecsa-note ecsa-note-{$n[0]}'><i class='fas $ico'></i><div class='ecsa-banner-txt'>{$n[1]}</div>$btn</div>";
    }
    return "<div class='ecsa-notes'>".implode("",$h)."</div>";
}

function ecsa_sev_pills($sev):string{
    $map=array("critical"=>"ecsa_sev_c","high"=>"ecsa_sev_h","medium"=>"ecsa_sev_m","low"=>"ecsa_sev_l","info"=>"ecsa_sev_i");
    $p=array();
    foreach($map as $k=>$tok){
        if($sev[$k]==0){continue;}
        $p[]="<span class='ecsa-sp ecsa-sp-$k'>".number_format($sev[$k])." ".ecsa_t($tok)."</span>";
    }
    if($sev["critical"]+$sev["high"]+$sev["medium"]+$sev["low"]==0){
        array_unshift($p,"<span class='ecsa-sp ecsa-sp-ok'><i class='fas fa-trophy'></i> {ecsa_nothing_to_fix}</span>");
    }
    return implode(" ",$p);
}

function ecsa_row($d,$egress):string{
    $page=CurrentPageName();
    $checked=$d["state"]=="checked";
    $js="Loadjs('$page?ca-findings-js=yes&siteid={$d["sid"]}&domain=".urlencode($d["domain"])."');";
    // Note
    if($checked){
        $gc=ecsa_grade_class($d["grade"]);
        $grade="<div class='ecsa-gb ecsa-gb-$gc'><b>".ecsa_h($d["grade"])."</b><span>{$d["score"]}</span></div>";
    }elseif($d["state"]=="failed" || $d["state"]=="blocked"){
        $grade="<div class='ecsa-gb ecsa-gb-err'><b>!</b><span>&mdash;</span></div>";
    }else{
        $grade="<div class='ecsa-gb ecsa-gb-off'><b>&mdash;</b></div>";
    }
    // Chiffrement
    $tls="<span class='ecsa-muted'>&mdash;</span>";
    if($d["tls"]!==null){
        $ver=$d["tls"]["version"]!==""?ecsa_h($d["tls"]["version"]):"TLS";
        if(!$d["tls"]["valid"]){
            $tls="<div class='ecsa-err'><i class='fas fa-lock-open'></i> {ecsa_cert_invalid}</div><div class='ecsa-sub'>$ver</div>";
        }else{
            $cls=$d["tls"]["days"]<=30?" ecsa-warn":"";
            $tls="<div class='ecsa-nw'>".ecsa_t("ecsa_encrypted",$ver)."</div><div class='ecsa-sub$cls'>".($cls!==""?"<i class='fas fa-triangle-exclamation'></i> ":"").ecsa_t("ecsa_days_left",number_format($d["tls"]["days"]))."</div>";
        }
    }elseif($checked){
        $tls="<div class='ecsa-warn'>{ecsa_not_encrypted}</div>";
    }
    // Temps de reponse
    $lat="<span class='ecsa-muted'>&mdash;</span>";
    if($d["lat"]>=0){
        $lat="<div class='ecsa-mono ecsa-strong ecsa-nw'>".number_format($d["lat"])." ms</div>";
        if($d["lat7"]>=0){$lat.="<div class='ecsa-sub'>".ecsa_t("ecsa_avg7",number_format($d["lat7"]))."</div>";}
    }
    // A corriger
    $fix=$checked?ecsa_sev_pills($d["sev"]):"<span class='ecsa-muted'>&mdash;</span>";
    // DNS
    $ips=ecsa_h(implode(", ",$d["ips"]));
    if($d["dns"]===1){$dns="<div class='ecsa-ok ecsa-nw'><i class='fas fa-check'></i> <span class='ecsa-mono'>$ips</span></div><div class='ecsa-sub'>{ecsa_points_here}</div>";}
    elseif($d["dns"]===0){$dns="<div class='ecsa-err ecsa-nw'><i class='fas fa-triangle-exclamation'></i> <span class='ecsa-mono'>$ips</span></div><div class='ecsa-sub'>".ecsa_t("ecsa_expected",ecsa_h($egress))."</div>";}
    else{$dns="<span class='ecsa-muted'>{ecsa_unknown}</span>";}
    // Etat
    switch($d["state"]){
        case "checked": $st="<span class='ecsa-st ecsa-st-ok'><i class='fas fa-check'></i> {ecsa_st_checked}</span>";break;
        case "failed": $st="<span class='ecsa-st ecsa-st-err'><i class='fas fa-xmark'></i> {ecsa_st_failed}</span>".($d["error"]!==""?"<div class='ecsa-sub ecsa-err'>".ecsa_h($d["error"])."</div>":"");break;
        case "blocked": $st="<span class='ecsa-st ecsa-st-warn'><i class='fas fa-hand'></i> {ecsa_st_blocked}</span><div class='ecsa-sub'>{ecsa_st_blocked_help}</div>";break;
        default: $st="<span class='ecsa-st'><i class='fas fa-hourglass-half'></i> {ecsa_st_pending}</span>";
    }
    $act=$checked?"<button type='button' class='ecsa-btn ecsa-btn-sm' OnClick=\"$js\"><i class='fas fa-file-shield'></i> {ecsa_report}</button>":"";
    $name=$checked?"<a href='javascript:void(0)' class='ecsa-dom' OnClick=\"$js\">".ecsa_h($d["domain"])."</a>":"<span class='ecsa-dom'>".ecsa_h($d["domain"])."</span>";
    $group=!$checked?"pending":($d["attention"]?"attention":"good");
    if($d["state"]=="failed" || $d["state"]=="blocked"){$group="attention";}
    $sortScore=$checked?$d["score"]:($d["state"]=="pending"?101:-1);
    return "<tr data-g='$group' data-score='$sortScore' data-name=\"".ecsa_h(strtolower($d["domain"]))."\" class='".($d["state"]=="failed"?"ecsa-row-err":"")."'>"
        ."<td>$grade</td><td>$name<div class='ecsa-sub'>".ecsa_h($d["site"])."</div></td><td>$tls</td><td>$lat</td><td>$fix</td><td>$dns</td><td>$st</td><td class='ecsa-actions'>$act</td></tr>";
}

function ecsa_domains_card($m):string{
    $rows=array();
    $att=0;$good=0;$pend=0;
    foreach($m["domains"] as $d){
        $rows[]=ecsa_row($d,$m["egress"]);
        if($d["state"]!="checked" && $d["state"]!="failed" && $d["state"]!="blocked"){$pend++;}
        elseif($d["attention"]){$att++;}
        else{$good++;}
    }
    $n=count($m["domains"]);
    if($n==0){
        $btn=ecsa_can_act()?"<button type='button' class='ecsa-btn ecsa-btn-primary' OnClick=\"EcsaSync();\"><i class='fas fa-arrows-rotate'></i> {ecsa_send_sites}</button>":"";
        return "<div class='ecsa-card ecsa-empty'><div class='ecsa-empty-ico'><i class='fas fa-globe'></i></div><h4>{ecsa_no_domains_title}</h4><p>{ecsa_no_domains_text}</p>$btn</div>";
    }
    $ph=ecsa_h(ecsa_t("ecsa_find_domain"));
    $seg="<div class='ecsa-seg' role='radiogroup' aria-label='{ecsa_filter}'>"
        ."<button type='button' role='radio' aria-checked='true' data-f='all' OnClick=\"EcsaFilter('all');\">".ecsa_t("ecsa_f_all",(string)$n)."</button>"
        ."<button type='button' role='radio' aria-checked='false' data-f='attention' OnClick=\"EcsaFilter('attention');\">".ecsa_t("ecsa_f_attention",(string)$att)."</button>"
        ."<button type='button' role='radio' aria-checked='false' data-f='good' OnClick=\"EcsaFilter('good');\">".ecsa_t("ecsa_f_good",(string)$good)."</button>"
        ."<button type='button' role='radio' aria-checked='false' data-f='pending' OnClick=\"EcsaFilter('pending');\">".ecsa_t("ecsa_f_pending",(string)$pend)."</button></div>";
    $sort="<select class='ecsa-select' id='ecsa-sort' aria-label='{ecsa_sort}' OnChange=\"EcsaSort(this.value);\">"
        ."<option value='worst'>{ecsa_sort_worst}</option><option value='name'>{ecsa_sort_name}</option></select>";
    $foot=$m["last_scan"]>0?"<span class='ecsa-ok'><i class='fas fa-circle'></i></span> ".ecsa_t("ecsa_newest_check","<span title='".ecsa_h(date("Y-m-d H:i:s",$m["last_scan"]))."'>".ecsa_date($m["last_scan"])."</span>"):"";
    return "<div class='ecsa-card'><div class='ecsa-card-h'><div><h3>{ecsa_your_websites} <span class='ecsa-count'>".number_format($n)."</span></h3><div class='ecsa-sub'>{ecsa_your_websites_sub}</div></div>"
        ."<div class='ecsa-tools'><label class='ecsa-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='ecsa-q' placeholder=\"$ph\" aria-label=\"$ph\" OnInput=\"EcsaApply();\"></label>$seg$sort</div></div>"
        ."<div class='ecsa-tablewrap'><table class='ecsa-table' id='ecsa-table'><thead><tr>"
        ."<th>{ecsa_col_grade}</th><th>{ecsa_col_domain}</th><th>{ecsa_col_tls}</th><th>{ecsa_col_time}</th><th>{ecsa_col_fix}</th><th>{ecsa_col_dns}</th><th>{ecsa_col_status}</th><th class='ecsa-actions'>{ecsa_col_action}</th>"
        ."</tr></thead><tbody>".implode("",$rows)."</tbody></table><div class='ecsa-nomatch' id='ecsa-nomatch' hidden>{ecsa_no_match}</div></div>"
        ."<div class='ecsa-foot'><span id='ecsa-shown'>".ecsa_t("ecsa_shown",(string)$n,(string)$n)."</span><span>$foot</span></div></div>";
}

// ── Script ──────────────────────────────────────────────────────────────────

function ecsa_js($m):string{
    $page=CurrentPageName();
    $shown=json_encode(html_entity_decode(ecsa_t("ecsa_shown","%1","%2"),ENT_QUOTES,"UTF-8"));
    $info=json_encode(html_entity_decode(ecsa_t("ecsa_request_sent"),ENT_QUOTES,"UTF-8"));
    $refresh=$m["running"]?"if(window.EcsaTimer){clearTimeout(window.EcsaTimer);} window.EcsaTimer=setTimeout(function(){ if(!document.getElementById('ecsa-root')){return;} if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){return;} LoadAjaxSilent('table-nginx-sla','$page?build=yes'); },60000);":"";
    return "
window.EcsaState={f:'all'};
function EcsaReload(){ LoadAjax('table-nginx-sla','$page?build=yes'); }
function EcsaAfter(obj){
    var r=String(obj.responseText||'').trim();
    if(r.indexOf('INFO:')===0){ swal({title:'',text:r.substring(5),type:'info'}); EcsaReload(); return; }
    if(r.length>3){ swal({title:'',text:r,type:'warning'}); }
    else { swal({title:'',text:$info,type:'success',timer:2500}); }
    EcsaReload();
}
function EcsaScan(){ var x=new XHR(); x.appendData('ca-scan','yes'); x.sendAndLoad('$page','POST',EcsaAfter); }
function EcsaSync(){ var x=new XHR(); x.appendData('ca-sync','yes'); x.setLock(true); x.sendAndLoad('$page','POST',EcsaAfter); }
function EcsaFilter(f){
    window.EcsaState.f=f;
    var b=document.querySelectorAll('.ecsa-seg button');
    for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-f')===f?'true':'false');}
    EcsaApply();
    var t=document.getElementById('ecsa-table'); if(t && t.scrollIntoView){t.scrollIntoView({block:'start'});}
}
function EcsaApply(){
    var t=document.getElementById('ecsa-table'); if(!t){return;}
    var q=document.getElementById('ecsa-q'); q=q?q.value.toLowerCase().trim():'';
    var rows=t.tBodies[0].rows, n=0, f=window.EcsaState.f;
    for(var i=0;i<rows.length;i++){
        var r=rows[i], ok=(f==='all'||r.getAttribute('data-g')===f);
        if(ok && q!=='' && r.getAttribute('data-name').indexOf(q)<0){ok=false;}
        r.style.display=ok?'':'none'; if(ok){n++;}
    }
    var nm=document.getElementById('ecsa-nomatch'); if(nm){nm.hidden=(n!==0);}
    var s=document.getElementById('ecsa-shown'); if(s){s.textContent=$shown.replace('%1',n).replace('%2',rows.length);}
}
function EcsaSort(mode){
    var t=document.getElementById('ecsa-table'); if(!t){return;}
    var tb=t.tBodies[0], rows=Array.prototype.slice.call(tb.rows);
    rows.sort(function(a,b){
        if(mode==='name'){return a.getAttribute('data-name')<b.getAttribute('data-name')?-1:1;}
        return parseInt(a.getAttribute('data-score'),10)-parseInt(b.getAttribute('data-score'),10);
    });
    for(var i=0;i<rows.length;i++){tb.appendChild(rows[i]);}
}
EcsaSort('worst');
$refresh
";
}

function ecsa_style():string{
    if(isset($GLOBALS["ECSA_STYLE"])){return "";}
    $GLOBALS["ECSA_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.ecsa{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
.ecsa>div:empty{display:none}
.ecsa-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.ecsa-hero{position:relative;display:flex;flex-wrap:wrap;gap:24px 32px;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f0fdf4 0%,#ffffff 60%);border:1px solid #e2e8f0;border-radius:16px;padding:28px 32px 28px 36px;box-shadow:0 1px 2px rgba(15,23,42,.06),0 8px 24px rgba(15,23,42,.04);overflow:hidden}
.ecsa-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.ecsa-hero-l{flex:1 1 520px;min-width:0}
.ecsa-eyebrow{display:inline-block;background:$soft;color:$dark;border-radius:6px;padding:3px 10px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.ecsa-hero h2{margin:12px 0 10px 0;font-size:26px;font-weight:700;line-height:1.25;color:#0f172a}
.ecsa-hero p{margin:0;font-size:15px;line-height:1.65;color:#475569;max-width:760px}
.ecsa-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.ecsa-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:#fff;border-radius:999px;padding:4px 12px;font-size:12px;color:#334155}
.ecsa-chip i,.ecsa-chip svg{color:$accent}
.ecsa-chip-live{border-color:#bfdbfe;color:#1d4ed8}
.ecsa-chip-live i,.ecsa-chip-live svg{color:#2563eb}
.ecsa-hero-r{display:flex;flex-direction:column;align-items:flex-end;gap:16px;flex:0 0 auto}
.ecsa-hero-score{display:flex;align-items:center;gap:20px}
.ecsa-eyebrow-s{font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.ecsa-gword{font-size:22px;font-weight:700;margin:4px 0 8px 0}
.ecsa-dpill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 12px;font-size:12px;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.ecsa-dpill-up{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.ecsa-dpill-down{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.ecsa-hero-btns{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
.ecsa-ring{flex:0 0 auto}
.ecsa-ring-bg{fill:none;stroke:#e2e8f0;stroke-width:10}
.ecsa-ring-fg{fill:none;stroke-width:10;stroke-linecap:round}
.ecsa-ring-ok .ecsa-ring-fg{stroke:#10b981}.ecsa-ring-warn .ecsa-ring-fg{stroke:#f59e0b}.ecsa-ring-err .ecsa-ring-fg{stroke:#dc2626}.ecsa-ring-off .ecsa-ring-fg{stroke:#94a3b8}
.ecsa-ring-g{font-size:34px;font-weight:800;text-anchor:middle;dominant-baseline:middle;fill:#0f172a}
.ecsa-ring-s{font-size:11px;font-weight:600;text-anchor:middle;fill:#64748b}
@media (prefers-reduced-motion:no-preference){.ecsa-ring-fg{animation:ecsaRing .6s ease-out}}
@keyframes ecsaRing{from{stroke-dasharray:0 400}}
.ecsa-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:9px 16px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap;text-decoration:none}
.ecsa-btn:hover{background:#f8fafc;color:#0f172a;text-decoration:none}
.ecsa-btn:focus-visible,.ecsa-link:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ecsa-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.ecsa-btn-primary:hover{background:$dark;border-color:$dark;color:#fff}
.ecsa-btn-warn{background:#fef3c7;border-color:#fcd34d;color:#92400e}
.ecsa-btn-sm{padding:6px 12px;font-size:13px}
.ecsa-link{border:0;background:transparent;color:$dark;cursor:pointer;font-size:13px;text-decoration:underline;padding:0}
.ecsa-banner,.ecsa-note{display:flex;align-items:center;gap:12px;border-radius:12px;padding:12px 16px;font-size:14px}
.ecsa-banner-txt{flex:1;min-width:0;overflow-wrap:anywhere}
.ecsa-banner-warn{background:#fffbeb;border:1px solid #fcd34d;color:#92400e}
.ecsa-notes{display:flex;flex-direction:column;gap:8px}
.ecsa-note-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
.ecsa-note-info{background:#f8fafc;border:1px solid #e2e8f0;color:#475569}
.ecsa-state{display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px 24px;color:#334155}
.ecsa-state>i,.ecsa-state>svg{font-size:24px;color:#64748b;margin-top:2px}
.ecsa-state h4{margin:0 0 4px 0;font-size:17px;font-weight:600;color:#0f172a}
.ecsa-state p{margin:0;line-height:1.6}
.ecsa-state-err{background:#fef2f2;border-color:#fecaca}
.ecsa-state-err>i,.ecsa-state-err>svg{color:#b91c1c}
.ecsa-delta{padding:16px 18px}
.ecsa-delta-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.ecsa-dt{display:flex;gap:12px;align-items:center;border:1px solid #e2e8f0;background:#f8fafc;border-radius:10px;padding:12px 14px;font-size:13px;color:#64748b}
.ecsa-dt strong{display:block;font-size:15px;color:#0f172a}
.ecsa-dt-ico{flex:0 0 36px;height:36px;border-radius:8px;background:#e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center}
.ecsa-dt-ok{background:#f0fdf4;border-color:#bbf7d0;color:#047857}.ecsa-dt-ok .ecsa-dt-ico{background:#d1fae5;color:#047857}
.ecsa-dt-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}.ecsa-dt-warn .ecsa-dt-ico{background:#fef3c7;color:#b45309}
.ecsa-delta-cap{margin-top:8px;text-align:right;font-size:12px;color:#94a3b8}
.ecsa-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ecsa-tile{display:flex;flex-direction:column;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0}
.ecsa-tile-h{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.ecsa-tile-l{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.ecsa-tile-ico{flex:0 0 38px;height:38px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center}
.ecsa-ico-ok{background:#ecfdf5;color:#059669}.ecsa-ico-warn{background:#fffbeb;color:#d97706}
.ecsa-tile-v{margin-top:6px;font-size:24px;font-weight:700;line-height:1.2;overflow-wrap:anywhere}
.ecsa-tile-s{margin-top:4px;font-size:13px;color:#64748b}
.ecsa-tile-f{display:flex;flex-wrap:wrap;justify-content:space-between;gap:6px 12px;margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9;font-size:12px}
.ecsa-tile-f i,.ecsa-tile-f svg{font-size:7px;vertical-align:middle}
.ecsa-badge-warn{background:#fef3c7;color:#92400e;border-radius:6px;padding:2px 8px;font-size:12px;font-weight:600;white-space:nowrap}
.ecsa-bar{display:flex;height:8px;border-radius:999px;overflow:hidden;background:#e2e8f0;margin:10px 0 2px 0}
.ecsa-bar-critical{background:#dc2626}.ecsa-bar-high{background:#ea580c}.ecsa-bar-medium{background:#f59e0b}.ecsa-bar-low{background:#94a3b8}
.ecsa-legend span{font-weight:600}
.ecsa-lg-critical{color:#b91c1c}.ecsa-lg-high{color:#c2410c}.ecsa-lg-medium{color:#b45309}.ecsa-lg-low{color:#475569}
.ecsa-ipbig{display:inline-block;background:#f1f5f9;border-radius:8px;padding:2px 10px;font-family:monospace;font-size:20px}
.ecsa-sh{padding:6px 20px 8px 20px}
.ecsa-sh-h{display:flex;align-items:center;gap:10px;padding:14px 0;border-bottom:1px solid #f1f5f9;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.ecsa-sh-dot{width:12px;height:12px;border-radius:50%;background:#ef4444;box-shadow:0 0 0 4px #fee2e2}
.ecsa-sh-row{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid #f1f5f9}
.ecsa-sh-row:last-child{border-bottom:0}
.ecsa-sh-ico{flex:0 0 34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center}
.ecsa-sh-err{background:#fef2f2;color:#dc2626}.ecsa-sh-warn{background:#fffbeb;color:#d97706}
.ecsa-sh-body{flex:1;min-width:0}
.ecsa-sh-text{margin-top:3px;font-size:13px;color:#475569;overflow-wrap:anywhere}
.ecsa-tag{display:inline-block;border-radius:6px;padding:1px 8px;font-size:11px;font-weight:600;margin-left:6px}
.ecsa-tag-err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}.ecsa-tag-warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.ecsa-card-h{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;padding:18px 22px;border-bottom:1px solid #e2e8f0}
.ecsa-card-h h3{margin:0;font-size:19px;font-weight:700;display:flex;align-items:center;gap:10px}
.ecsa-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:1px 10px;font-size:12px;font-weight:600;color:#334155}
.ecsa-tools{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
.ecsa-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:10px;padding:0 12px;height:38px;width:230px;margin:0;color:#94a3b8;background:#fff}
.ecsa-search input{border:0;outline:0;flex:1;min-width:0;font-size:14px;color:#0f172a;background:transparent;height:36px}
.ecsa-search:focus-within{border-color:$accent;box-shadow:0 0 0 2px $soft}
.ecsa-seg{display:flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:3px}
.ecsa-seg button{border:0;background:transparent;border-radius:7px;padding:6px 12px;font-size:13px;color:#475569;cursor:pointer;white-space:nowrap}
.ecsa-seg button[aria-checked=true]{background:#0f172a;color:#fff;font-weight:600}
.ecsa-seg button:focus-visible{outline:2px solid $accent}
.ecsa-select{height:38px;border:1px solid #cbd5e1;border-radius:10px;padding:0 10px;font-size:13px;background:#fff;color:#0f172a}
.ecsa-tablewrap{overflow-x:auto}
.ecsa-table{width:100%;border-collapse:collapse;color:#0f172a}
.ecsa-table th{background:#f8fafc;text-align:left;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b;padding:12px 16px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.ecsa-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:14px}
.ecsa-table tbody tr:hover td{background:#f8fafc}
.ecsa-table tbody tr:last-child td{border-bottom:0}
.ecsa-row-err td{background:#fff7f7}
.ecsa-gb{width:46px;height:46px;border-radius:10px;border:1px solid;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.1}
.ecsa-gb b{font-size:16px}.ecsa-gb span{font-size:11px;font-weight:600}
.ecsa-gb-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.ecsa-gb-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}
.ecsa-gb-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.ecsa-gb-off{background:#f1f5f9;border-color:#e2e8f0;color:#94a3b8}
.ecsa-dom{font-family:monospace;font-weight:700;color:#0f172a;white-space:nowrap;text-decoration:none}
.ecsa-nw{white-space:nowrap}
a.ecsa-dom:hover{color:$dark}
.ecsa-sub{font-size:12px;color:#64748b;margin-top:2px}
.ecsa-sp{display:inline-block;border-radius:6px;padding:2px 7px;font-size:12px;font-weight:600;margin:1px 0;white-space:nowrap}
.ecsa-sp-critical{background:#fef2f2;color:#b91c1c}.ecsa-sp-high{background:#fff7ed;color:#c2410c}.ecsa-sp-medium{background:#fffbeb;color:#b45309}
.ecsa-sp-low{background:#eff6ff;color:#1d4ed8}.ecsa-sp-info{background:#f1f5f9;color:#64748b;font-weight:500}.ecsa-sp-ok{background:#ecfdf5;color:#047857}
.ecsa-st{display:inline-flex;align-items:center;gap:6px;border-radius:6px;padding:3px 9px;font-size:12px;font-weight:600;background:#f1f5f9;color:#475569;white-space:nowrap}
.ecsa-st-ok{background:#ecfdf5;color:#047857}.ecsa-st-err{background:#dc2626;color:#fff}.ecsa-st-warn{background:#fffbeb;color:#b45309}
.ecsa-actions{text-align:right;white-space:nowrap;width:1%}
.ecsa-ok{color:#059669}.ecsa-err{color:#b91c1c}.ecsa-warn{color:#b45309}.ecsa-muted{color:#94a3b8}
.ecsa-mono{font-family:monospace}.ecsa-strong{font-weight:700}
.ecsa-nomatch{padding:28px;text-align:center;color:#64748b}
.ecsa-nomatch[hidden]{display:none}
.ecsa-foot{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;padding:12px 22px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b;background:#f8fafc}
.ecsa-foot i,.ecsa-foot svg{font-size:8px}
.ecsa-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:40px 24px;gap:10px}
.ecsa-empty-ico{width:72px;height:72px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:28px}
.ecsa-empty h4{margin:6px 0 0 0;font-size:18px;font-weight:600}
.ecsa-empty p{margin:0;color:#475569;max-width:560px;line-height:1.6}
@media (max-width:1200px){.ecsa-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.ecsa-hero-r{align-items:flex-start}.ecsa-delta-row{grid-template-columns:1fr}}
</style>";
}

// ── Rapport detaille d'un domaine (inchange) ────────────────────────────────

function ca_findings_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sid=intval($_GET["siteid"]);
    $domain=$_GET["domain"];
    // Minor : js_dialog4() echo deja et rend un bool -- un echo devant doublait la sortie.
    // 880 et non 1400: la charte contraint le rapport a 54rem (864px) pour que
    // l'oeil balaie une ligne sans la perdre. js_dialogsize() mappe cette
    // valeur sur la classe .dialog880 (angular.css.php).
    $tpl->js_dialog4(htmlspecialchars($domain),"$page?ca-findings-popup=yes&siteid=$sid&domain=".urlencode($domain),880);
    return true;
}

// ────────────────────────────────────────────────────────────────────────────
// Rapport d'audit d'un domaine — charte « Vigil Sentinel »
// (/home/claude/images/stitch_audit_report_design_dialog.zip).
//
// La couleur porte ici un SENS de diagnostic et jamais une décoration: chaque
// gravité a son triplet fond/bordure/texte, et la carte reprend la bordure de
// sa gravité pour que la sévérité se lise avant le titre.
//
// DESIGN.md range critical ET high en rose; la maquette, elle, montre high en
// ambre — elle ne comporte simplement aucune ligne critique. On garde donc le
// rose pour « critical » (la classe la plus grave doit rester distincte) et
// l'ambre pour « high », ce qui satisfait les deux.
// ────────────────────────────────────────────────────────────────────────────
function ca_sev_palette():array{
    return array(
        "critical"=>array("bg"=>"#fef2f2","bd"=>"#fecaca","fg"=>"#b91c1c","dot"=>"#dc2626","tok"=>"{critical}"),
        "high"    =>array("bg"=>"#fffbeb","bd"=>"#fde68a","fg"=>"#b45309","dot"=>"#f59e0b","tok"=>"{high}"),
        "medium"  =>array("bg"=>"#f0f9ff","bd"=>"#bae6fd","fg"=>"#0369a1","dot"=>"#0ea5e9","tok"=>"{medium}"),
        "low"     =>array("bg"=>"#f1f5f9","bd"=>"#e2e8f0","fg"=>"#475569","dot"=>"#94a3b8","tok"=>"{low}"),
        "info"    =>array("bg"=>"#f8fafc","bd"=>"#e2e8f0","fg"=>"#64748b","dot"=>"#cbd5e1","tok"=>"{info}"),
    );
}

// Feuille de style du rapport. Emise UNE fois par requete et concatenee HORS
// _ENGINE_parse_body(): son moteur lit chaque corps de regle CSS comme un
// {token} et declencherait une recherche memcached par regle (CLAUDE.md).
function ca_findings_css():string{
    if(isset($GLOBALS["CA_FINDINGS_CSS_DONE"])){return "";}
    $GLOBALS["CA_FINDINGS_CSS_DONE"]=true;
    $sev="";
    foreach(ca_sev_palette() as $k=>$p){
        $sev.=".cafr-b-$k{background:{$p["bg"]};border-color:{$p["bd"]};color:{$p["fg"]}}\n";
        $sev.=".cafr-card-$k{border-color:{$p["bd"]}}\n";
        $sev.=".cafr-reco-$k{background:{$p["bg"]};border-left-color:{$p["dot"]}}\n";
        $sev.=".cafr-dot-$k{background:{$p["dot"]}}\n";
        $sev.=".cafr-pill-$k{background:{$p["bg"]};color:{$p["fg"]};border:1px solid {$p["bd"]}}\n";
    }
    // Pas de font-family importee: la console n'embarque ni Geist ni JetBrains
    // Mono. Le texte herite de la police du theme (c'est ce qui fait qu'un
    // fragment appartient a la console), et seule la pile monospace du systeme
    // est utilisee la ou la charte l'exige — badges, score, nom de domaine —
    // pour que les colonnes de gravite s'alignent verticalement.
    return "<style>
.cafr{--cafr-mono:ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace;
  background:#f8fafc;margin:-15px;padding:0;color:#0f172a;text-align:left}
.cafr *{box-sizing:border-box}
.cafr-head{background:#fff;border-bottom:1px solid #e2e8f0;padding:20px 24px 16px}
.cafr-head-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.cafr-idl{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px}
.cafr-domain{display:inline-flex;align-items:center;gap:7px;background:#ecfdf5;border:1px solid #a7f3d0;
  color:#047857;border-radius:6px;padding:4px 10px;font-family:var(--cafr-mono);font-size:12px;font-weight:600}
.cafr-domain i{width:7px;height:7px;border-radius:9999px;background:#059669;display:inline-block}
.cafr-env{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;
  border-radius:9999px;padding:3px 10px;font-size:11px;font-weight:600}
.cafr-meta{color:#64748b;font-size:11.5px;font-weight:500}
.cafr-title{margin:0;font-size:22px;line-height:28px;font-weight:600;letter-spacing:-.015em;color:#0f172a;
  display:flex;align-items:center;gap:10px}
.cafr-title .fa,.cafr-title svg{color:#059669;font-size:19px}
.cafr-score{display:flex;align-items:center;gap:14px;border:1px solid #e2e8f0;background:#fff;
  border-radius:10px;padding:10px 16px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.cafr-score-lbl{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.cafr-score-val{font-family:var(--cafr-mono);font-size:13px;color:#475569;margin-top:3px}
.cafr-score-val b{font-size:22px;font-weight:700}
.cafr-gauge{width:38px;height:38px;border-radius:9999px;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
.cafr-gauge span{width:28px;height:28px;border-radius:9999px;background:#fff;display:flex;align-items:center;
  justify-content:center;font-family:var(--cafr-mono);font-size:13px;font-weight:700}
.cafr-filters{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
  background:#fff;border-bottom:1px solid #e2e8f0;padding:12px 24px}
.cafr-tabs{display:inline-flex;gap:4px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:9px;padding:4px}
.cafr-tab{display:inline-flex;align-items:center;gap:7px;border:0;background:transparent;color:#475569;
  border-radius:6px;padding:6px 12px;font-size:13px;font-weight:600;cursor:pointer;line-height:16px;
  /* text-transform:capitalize -- les jetons partages n'ont pas la meme casse
     ({critical}='Critical' mais {medium}='medium'), et une barre d'onglets
     ou deux libelles sur quatre commencent en minuscule se lit comme un
     defaut de rendu. La casse est ici une affaire de presentation, pas de
     traduction: on ne touche pas aux jetons, qui servent ailleurs. */
  text-transform:capitalize}
.cafr-tab:hover{background:rgba(255,255,255,.7);color:#0f172a}
.cafr-tab.cafr-on{background:#fff;color:#0f172a;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.cafr-tab i{width:8px;height:8px;border-radius:9999px;display:inline-block}
.cafr-tab b{font-family:var(--cafr-mono);font-size:10px;font-weight:600;border-radius:9999px;padding:1px 6px;
  border:1px solid transparent}
.cafr-note{display:inline-flex;align-items:center;gap:7px;color:#64748b;font-family:var(--cafr-mono);font-size:11px}
.cafr-list{padding:20px 24px;display:flex;flex-direction:column;gap:14px;max-height:56vh;overflow-y:auto}
.cafr-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;
  box-shadow:0 1px 2px rgba(15,23,42,.04);transition:border-color .15s,box-shadow .15s}
.cafr-card:hover{box-shadow:0 4px 10px -2px rgba(15,23,42,.08)}
.cafr-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.cafr-card-l{display:flex;align-items:flex-start;gap:12px;min-width:0}
.cafr-badge{display:inline-block;border:1px solid;border-radius:4px;padding:2px 8px;margin-top:2px;
  font-family:var(--cafr-mono);font-size:10.5px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;flex:0 0 auto}
.cafr-name{margin:0;font-size:15px;font-weight:600;letter-spacing:-.01em;color:#0f172a;line-height:20px}
.cafr-desc{margin:4px 0 0;font-size:13px;line-height:18px;color:#475569}
.cafr-kev{display:inline-block;margin-left:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;
  border-radius:4px;padding:1px 7px;font-family:var(--cafr-mono);font-size:10px;font-weight:700;text-transform:uppercase}
.cafr-link{display:inline-flex;align-items:center;gap:5px;color:#2563eb;font-size:12px;font-weight:600;
  text-decoration:none;flex:0 0 auto;max-width:38%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cafr-link:hover{color:#1d4ed8;text-decoration:underline}
.cafr-reco{margin-top:12px;border-left:3px solid;border-radius:8px;padding:10px 14px;font-size:12.5px;line-height:18px;color:#334155}
.cafr-reco em{font-style:italic;font-weight:600;color:#475569;margin-right:6px}
.cafr-reco b{font-weight:600;color:#0f172a}
.cafr-cve{margin-top:8px;font-family:var(--cafr-mono);font-size:11px;color:#64748b}
.cafr-empty{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:34px;text-align:center;color:#64748b;font-size:14px}
.cafr-meta-row{display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;
  margin:8px 24px 0;padding-top:8px;border-top:1px dashed #e2e8f0;font-size:11.5px;color:#64748b}
.cafr-conf{display:inline-flex;align-items:center;gap:5px;padding:2px 8px;border-radius:999px;
  border:1px solid #e2e8f0;background:#f8fafc}
.cafr-conf b{font-weight:600}
.cafr-conf i{font-size:9.5px;opacity:.7}
/* La confiance se lit d'un coup d'oeil : « confirme » n'appelle pas la meme
   urgence qu'une deduction de banniere. */
.cafr-conf_confirmed{border-color:#bbf7d0;background:#f0fdf4;color:#15803d}
.cafr-conf_high_confidence{border-color:#bae6fd;background:#f0f9ff;color:#0369a1}
.cafr-conf_probable{border-color:#fde68a;background:#fffbeb;color:#b45309}
.cafr-conf_fingerprint_only{border-color:#e2e8f0;background:#f8fafc;color:#64748b}
.cafr-refs a{color:#0369a1;text-decoration:none;border-bottom:1px dotted #93c5fd}
.cafr-refs a:hover{color:#0c4a6e;border-bottom-style:solid}
.cafr-checks{margin:0 24px 20px}
.cafr-checks-h{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #e2e8f0;
  border-radius:8px;padding:8px 14px;font-size:12.5px;font-weight:600;color:#334155;cursor:pointer;text-decoration:none}
.cafr-checks-h:hover{border-color:#cbd5e1;background:#f8fafc;color:#0f172a;text-decoration:none}
.cafr-checks-t{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;
  border-radius:8px;margin-top:10px;overflow:hidden}
.cafr-checks-t th{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:9px 14px;text-align:left;
  font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#475569}
.cafr-checks-t td{border-top:1px solid #f1f5f9;padding:9px 14px;font-size:12.5px;color:#334155}
.cafr-checks-t td.cafr-m{font-family:var(--cafr-mono);font-size:11.5px;color:#475569}
$sev
@media(max-width:820px){.cafr-head-row{flex-direction:column}.cafr-link{max-width:100%}
.cafr-list{max-height:none}}
</style>";
}

// Ligne « recommandation » : le daemon peut la renvoyer vide (une observation
// neutre n'a rien a recommander) — on affiche alors {not_available} plutot
// qu'une bande vide qui se lit comme un oubli.
function ca_finding_card($f,$pal):string{
    $sev=strtolower((string)($f["severity"] ?? "info"));
    if(!isset($pal[$sev])){$sev="info";}
    $p=$pal[$sev];
    $reco=trim((string)($f["recommendation"] ?? ""));
    $desc=trim((string)($f["description"] ?? ""));

    $h ="<article class='cafr-card cafr-card-$sev' data-sev='$sev'>";
    $h.="<div class='cafr-card-top'><div class='cafr-card-l'>";
    $h.="<span class='cafr-badge cafr-b-$sev'>".htmlspecialchars($sev)."</span>";
    $h.="<div style='min-width:0'>";
    $h.="<h2 class='cafr-name'>".htmlspecialchars((string)($f["title"] ?? ""));
    if(!empty($f["known_exploited"])){$h.="<span class='cafr-kev'>{known_exploited}</span>";}
    $h.="</h2>";
    if($desc!==""){$h.="<p class='cafr-desc'>".htmlspecialchars($desc)."</p>";}
    $h.="</div></div>";

    // L'URL vient d'un scan externe: on ne rend un href que pour http(s) --
    // jamais un "javascript:" fabrique en amont. Un lien non conforme reste
    // affiche en texte, il ne disparait pas.
    $furl=(string)($f["url"] ?? "");
    if($furl!==""){
        $lbl=preg_replace('#^https?://#i','',$furl);
        $lbl=preg_replace('#/.*$#','',$lbl);
        if($lbl===""){$lbl=$furl;}
        if(preg_match('#^https?://#i',$furl)){
            $h.="<a class='cafr-link' href='".htmlspecialchars($furl)."' target='_blank' rel='noopener noreferrer' "
               ."title='".htmlspecialchars($furl)."'>".htmlspecialchars($lbl)
               ." <i class='fas fa-arrow-up-right-from-square' style='font-size:10px'></i></a>";
        }else{
            $h.="<span class='cafr-link' style='color:#64748b' title='".htmlspecialchars($furl)."'>"
               .htmlspecialchars($lbl)."</span>";
        }
    }
    $h.="</div>";

    $h.="<div class='cafr-reco cafr-reco-$sev'><em>{recommendation}:</em> <b>"
       .($reco!==""?htmlspecialchars($reco):"{not_available}")."</b></div>";

    $cve=isset($f["cve"]) && is_array($f["cve"]) ? $f["cve"] : array();
    if(count($cve)>0){
        $h.="<div class='cafr-cve'>CVE: ".htmlspecialchars(@implode(", ",$cve))."</div>";
    }

    // Confiance et references: le daemon les impose a CHAQUE controle (echelle
    // fermee, references https validees) et un test y veille -- mais elles ne
    // sortaient nulle part. Or « confirme » et « deduit d'une banniere » ne se
    // corrigent pas avec la meme urgence: sans la confiance affichee, un
    // exploitant traite les deux pareil.
    $h.=ca_finding_meta($f);

    $h.="</article>";
    return $h;
}

// ca_finding_meta rend la puce de confiance et les liens de reference.
// L'echelle est FERMEE (results.ValidConfidence): confirmed, high-confidence,
// probable, fingerprint-only. Une valeur hors echelle est affichee telle
// quelle plutot que masquee -- si le daemon en emettait une, la voir vaut
// mieux que de la perdre en silence.
function ca_finding_meta($f):string{
    $conf=strtolower(trim((string)($f["confidence"] ?? "")));
    $tok=array(
        "confirmed"        => "{confirmed}",
        "high-confidence"  => "{cloud_audit_confidence_high}",
        "probable"         => "{probable}",
        "fingerprint-only" => "{cloud_audit_confidence_fingerprint}",
    );
    $refs=isset($f["references"]) && is_array($f["references"]) ? $f["references"] : array();
    if($conf==="" && count($refs)===0){return "";}

    $h="<div class='cafr-meta-row'>";
    if($conf!==""){
        $cls=isset($tok[$conf]) ? "cafr-conf-".str_replace("-","_",$conf) : "cafr-conf_other";
        $lbl=isset($tok[$conf]) ? $tok[$conf] : htmlspecialchars($conf);
        $h.="<span class='cafr-conf $cls' title='".htmlspecialchars($conf)."'>"
           ."<i class='fas fa-crosshairs'></i>{confidence}: <b>".$lbl."</b></span>";
    }
    if(count($refs)>0){
        $h.="<span class='cafr-refs'>{references}:";
        $n=0;
        foreach($refs as $u){
            $u=trim((string)$u);
            if($u===""){continue;}
            // Meme discipline que l'URL du finding: un href seulement pour
            // http(s). Une reference non conforme reste lisible en texte,
            // elle ne disparait pas du rapport.
            $lbl=preg_replace('#^https?://#i','',$u);
            $lbl=preg_replace('#/.*$#','',$lbl);
            if($lbl===""){$lbl=$u;}
            if(preg_match('#^https?://#i',$u)){
                $h.=" <a href='".htmlspecialchars($u)."' target='_blank' rel='noopener noreferrer' "
                   ."title='".htmlspecialchars($u)."'>".htmlspecialchars($lbl)."</a>";
            }else{
                $h.=" <span style='color:#64748b' title='".htmlspecialchars($u)."'>"
                   .htmlspecialchars($lbl)."</span>";
            }
            $n++;
        }
        if($n===0){$h.=" <span style='color:#94a3b8'>{not_available}</span>";}
        $h.="</span>";
    }
    return $h."</div>";
}

function ca_findings_popup():bool{
    $tpl=new template_admin();
    $sid=intval($_GET["siteid"]);
    $domain=(string)$_GET["domain"];
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cloud-audit/report/$sid");
    $site=json_decode($raw,true);
    if(!is_array($site) || !isset($site["domains"])){echo $tpl->_ENGINE_parse_body($tpl->div_warning("{nginx_sla_frontend_no_report}"));return true;}
    $d=null;
    foreach(($site["domains"] ?? array()) as $x){if($x["domain"]==$domain){$d=$x;break;}}
    if($d===null){echo $tpl->_ENGINE_parse_body($tpl->div_warning("{nginx_sla_frontend_no_report}"));return true;}

    $pal=ca_sev_palette();
    $order=array("critical"=>0,"high"=>1,"medium"=>2,"low"=>3,"info"=>4);
    $findings=isset($d["findings"]) && is_array($d["findings"]) ? $d["findings"] : array();
    usort($findings,function($a,$b) use($order){
        $sa=isset($order[$a["severity"]])?$order[$a["severity"]]:9;
        $sb=isset($order[$b["severity"]])?$order[$b["severity"]]:9;
        return $sa <=> $sb;
    });

    // Compteurs par gravite: ils alimentent les onglets ET la note de droite.
    // Toujours initialises AVANT la boucle (plancher PHP 8: un accumulateur
    // cree dans une branche conditionnelle explose sur implode/count).
    $counts=array();
    foreach($pal as $k=>$ignored){$counts[$k]=0;}
    foreach($findings as $f){
        $s=strtolower((string)($f["severity"] ?? "info"));
        if(!isset($counts[$s])){$s="info";}
        $counts[$s]++;
    }
    $total=count($findings);

    // La date de l'analyse n'est PAS dans /cloud-audit/report/{id} (SiteResult
    // ne porte aucun horodatage) — elle vient de l'etat global. Si l'appel
    // echoue, la ligne disparait: on n'invente pas une date d'audit.
    $scanned="";
    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cloud-audit/status"),true);
    if(is_array($st) && isset($st["report"]) && is_array($st["report"]) && isset($st["report"]["finished_at"])){
        $ts=strtotime((string)$st["report"]["finished_at"]);
        if($ts!==false && $ts>0){$scanned=$tpl->time_to_date($ts,true);}
    }

    $score=intval($d["score"] ?? 0);
    $grade=strtoupper((string)($d["grade"] ?? ""));
    $gcol=ca_grade_color($grade);
    $gmap=array("green"=>"#059669","orange"=>"#d97706","red"=>"#dc2626","grey"=>"#94a3b8");
    $gc=isset($gmap[$gcol])?$gmap[$gcol]:"#94a3b8";
    $pct=$score; if($pct<0){$pct=0;} if($pct>100){$pct=100;}
    $svcname=get_servicename($sid);

    $u=substr(md5($domain.microtime(true)),0,8);   // suffixe unique: deux rapports
                                                   // peuvent coexister dans le DOM.
    $h=array();
    $h[]="<div class='cafr' id='cafr-$u'>";

    // ── En-tete: identite a gauche, score de posture a droite ───────────────
    $h[]="<div class='cafr-head'><div class='cafr-head-row'><div>";
    $h[]="<div class='cafr-idl'>";
    $h[]="<span class='cafr-domain'><i></i>".htmlspecialchars($domain)."</span>";
    if(trim($svcname)!==""){$h[]="<span class='cafr-env'>".htmlspecialchars($svcname)."</span>";}
    if($scanned!==""){$h[]="<span class='cafr-meta'>&bull; {last_scan}: ".htmlspecialchars($scanned)."</span>";}
    $h[]="</div>";
    $h[]="<h2 class='cafr-title'><i class='fas fa-shield-halved'></i>{cloud_audit_report_title}</h2>";
    $h[]="</div>";
    $h[]="<div class='cafr-score'><div>";
    $h[]="<div class='cafr-score-lbl'>{cloud_audit_posture_score}</div>";
    $h[]="<div class='cafr-score-val'><b style='color:$gc'>$score</b>/100"
        .($grade!==""?" (".htmlspecialchars($grade).")":"")."</div>";
    $h[]="</div>";
    $h[]="<div class='cafr-gauge' style='background:conic-gradient($gc ".($pct*3.6)."deg,#e2e8f0 0)'>"
        ."<span style='color:$gc'>".($grade!==""?htmlspecialchars($grade):"?")."</span></div>";
    $h[]="</div></div></div>";

    // ── Barre de filtres: une pastille par gravite REELLEMENT presente ──────
    $h[]="<div class='cafr-filters'><div class='cafr-tabs'>";
    $h[]="<button type='button' class='cafr-tab cafr-on' data-f='all'>{all}"
        ."<b style='background:#e2e8f0;color:#334155'>$total</b></button>";
    foreach($pal as $k=>$p){
        if($counts[$k]==0){continue;}
        $h[]="<button type='button' class='cafr-tab' data-f='$k'><i class='cafr-dot-$k'></i>{$p["tok"]}"
            ."<b class='cafr-pill-$k'>{$counts[$k]}</b></button>";
    }
    $h[]="</div>";
    if($total>0){
        $h[]="<div class='cafr-note'><i class='fas fa-circle-info'></i>$total {cloud_audit_need_attention}</div>";
    }
    $h[]="</div>";

    // ── Liste des observations ─────────────────────────────────────────────
    $h[]="<div class='cafr-list' id='cafr-list-$u'>";
    if($total==0){
        $h[]="<div class='cafr-empty'><i class='fas fa-shield-check' style='color:#059669;font-size:22px;display:block;margin-bottom:8px'></i>{no_findings}</div>";
    }else{
        foreach($findings as $f){$h[]=ca_finding_card($f,$pal);}
    }
    $h[]="</div>";

    // ── Controles techniques, replies par defaut ───────────────────────────
    $checks=isset($d["checks"]) && is_array($d["checks"]) ? $d["checks"] : array();
    if(count($checks)>0){
        $h[]="<div class='cafr-checks'>";
        $h[]="<a href=\"javascript:blur();\" class='cafr-checks-h' id='cafr-ct-$u'>"
            ."<i class='fas fa-list-check'></i>{checks} (".count($checks).")</a>";
        $h[]="<table class='cafr-checks-t' id='cafr-cb-$u' style='display:none'><thead><tr>";
        $h[]="<th>{id}</th><th>{category}</th><th>{status}</th><th>{impact}</th></tr></thead><tbody>";
        foreach($checks as $c){
            $h[]="<tr><td class='cafr-m'>".htmlspecialchars((string)($c["id"] ?? ""))."</td>"
                ."<td>".htmlspecialchars((string)($c["category"] ?? ""))."</td>"
                ."<td>".htmlspecialchars((string)($c["status"] ?? ""))."</td>"
                ."<td class='cafr-m'>".intval($c["score_impact"] ?? 0)."</td></tr>";
        }
        $h[]="</tbody></table></div>";
    }
    $h[]="</div>";

    // Le script est concatene APRES le parse (ses accolades seraient lues
    // comme des {token}), et il est portee sur $u: deux rapports ouverts
    // successivement ne se filtrent pas l'un l'autre.
    $js="<script>
// Deux instructions SEPAREES, et NoSpinner() gardee. Regroupees dans la meme
// IIFE, la moindre exception de la premiere emportait la seconde: les onglets
// de filtrage ne s'armaient plus, en silence (mesure au harnais). Le voile de
// chargement doit se lever meme si le filtrage echoue, et inversement.
if(typeof NoSpinner==='function'){NoSpinner();}
(function(){
 var root=document.getElementById('cafr-$u'); if(!root){return;}
 var tabs=root.querySelectorAll('.cafr-tab');
 var cards=root.querySelectorAll('.cafr-card');
 for(var i=0;i<tabs.length;i++){
  tabs[i].onclick=function(){
   var f=this.getAttribute('data-f');
   for(var j=0;j<tabs.length;j++){tabs[j].className='cafr-tab';}
   this.className='cafr-tab cafr-on';
   for(var k=0;k<cards.length;k++){
    cards[k].style.display=(f=='all'||cards[k].getAttribute('data-sev')==f)?'':'none';
   }
   return false;
  };
 }
 var t=document.getElementById('cafr-ct-$u'), b=document.getElementById('cafr-cb-$u');
 if(t&&b){t.onclick=function(){b.style.display=(b.style.display=='none')?'':'none';return false;};}
})();
</script>";

    echo ca_findings_css().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
    return true;
}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
