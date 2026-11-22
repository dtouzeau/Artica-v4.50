<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsDebianSystem){exit();}

if(isset($_GET["list"])){table();exit;}
if(isset($_GET["list2"])){table2();exit;}
page();

// Maquette stitch_ntp_server_status_lists (2026-11-11). Données :
//   /chrony/srvstatus : sources de chrony (chronyd/sources.go, chronyc -c [-N] sources)
//   /chrony/status    : INI du service ([APP_NTPD] running, master_pid, master_time)
//   /chrony/servers   : liste configurée (utilisée seulement si NTPDUseSpecifiedServers=1)

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDVersion");
    $html=$tpl->page_header("{APP_NTPD} $ver","fa fa-clock","{ntp_servers}","$page?list=yes","ntpservers",
        "progress-ntpd-restart",false,"table-loader-ntp_servers-service");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NTPD} $ver",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Hôte rechargé + TINY (émis une seule fois).
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if(!ntpst_cluster_client()){
        $topbuttons[]=array("Loadjs('fw.system.ntp.servers.php')","fa fa-bars","{ntp_servers}");
    }
    $topbuttons[]=array("NtpstReload();",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{ntp_servers}";
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]="{ntpst_explain}";
    $TINY_ARRAY["URL"]="ntpservers";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    echo ntpst_style()."<div class='ak ntpst' id='table-loader-ntp_servers-service2'></div>".ntpst_js();
    echo "<script>NtpstReload();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function table2(){
    $tpl=new template_admin();
    $m=ntpst_model();
    $html=array();
    if(!$m["ok"]){
        $html[]="<div class='ak-banner ak-err'><i class='fa-solid fa-circle-exclamation'></i><span class='ak-banner-m'>".htmlspecialchars($m["error"])."</span></div>";
        echo $tpl->_ENGINE_parse_body($html)."<script>NoSpinner();</script>";
        return;
    }
    $html[]=ntpst_banner($m);
    $html[]=ntpst_kpis($m);
    $html[]=ntpst_table($m);
    $html[]=ntpst_absent($m);
    $html[]=ntpst_legend();
    echo $tpl->_ENGINE_parse_body($html)."<script>NoSpinner();NtpstArm();</script>";
}

// ---------------------------------------------------------------- modèle

function ntpst_cluster_client():bool{
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"))==1;
}

// Etats de chrony : libellé, icône, ton, rang de tri.
function ntpst_states():array{
    return array(
        "*"=>array("label"=>"{ntpst_st_sync}","ico"=>"fa-solid fa-star","tone"=>"ak-ok ak-solid","rank"=>0,"help"=>"{ntpst_hl_sync}"),
        "+"=>array("label"=>"{ntpst_st_comb}","ico"=>"fa-solid fa-check","tone"=>"ak-ok","rank"=>1,"help"=>"{ntpst_hl_comb}"),
        "-"=>array("label"=>"{ntpst_st_notcomb}","ico"=>"fa-solid fa-minus","tone"=>"ak-off","rank"=>2,"help"=>"{ntpst_hl_notcomb}"),
        "~"=>array("label"=>"{ntpst_st_var}","ico"=>"fa-solid fa-wave-square","tone"=>"ak-warn","rank"=>3,"help"=>"{ntpst_hl_var}"),
        "x"=>array("label"=>"{ntpst_st_false}","ico"=>"fa-solid fa-ban","tone"=>"ak-err","rank"=>4,"help"=>"{ntpst_hl_false}"),
        "?"=>array("label"=>"{ntpst_st_unreach}","ico"=>"fa-solid fa-plug-circle-xmark","tone"=>"ak-warn","rank"=>5,"help"=>"{ntpst_hl_unreach}"),
    );
}

function ntpst_service():array{
    $s=array("known"=>false,"running"=>false,"pid"=>0,"uptime"=>0);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/status"));
    if(!is_object($json) || empty($json->Status) || !isset($json->Info)){return $s;}
    $ini=@parse_ini_string((string)$json->Info,true,INI_SCANNER_RAW);
    if(!is_array($ini) || !is_array($ini["APP_NTPD"] ?? null)){return $s;}
    $a=$ini["APP_NTPD"];
    $s["known"]=true;
    $s["running"]=intval($a["running"] ?? 0)==1;
    $s["pid"]=intval($a["master_pid"] ?? 0);
    $s["uptime"]=intval($a["master_time"] ?? 0);
    return $s;
}

function ntpst_model():array{
    $m=array("ok"=>true,"error"=>"","rows"=>array(),"sel"=>null,"reachable"=>0,"unreach"=>0,"rejected"=>0,
        "absent"=>array(),"cluster"=>ntpst_cluster_client(),
        "version"=>trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDVersion")));
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/srvstatus"));
    if(!is_object($json) || empty($json->Status)){
        $m["ok"]=false;
        $m["error"]=is_object($json)?(string)($json->Error ?? ""):"";
        if($m["error"]==""){$m["error"]="{ntpst_no_answer}";}
        return $m;
    }
    $m["service"]=ntpst_service();
    $states=ntpst_states();
    $named=false;
    $i=0;
    foreach((is_array($json->Info ?? null)?$json->Info:array()) as $p){
        if(!is_object($p)){continue;}
        $st=(string)($p->status ?? "?");
        if(!isset($states[$st])){$st="?";}
        $name=trim((string)($p->name ?? ""));
        if($name!=""){$named=true;}
        $addr=trim((string)($p->address ?? ""));
        $ip=trim((string)($p->ip ?? ""));
        $reach=intval($p->reach ?? 0);
        // error_s : daemon corrigé ; jitter_s : même valeur, nom d'avant.
        $err=isset($p->error_s)?floatval($p->error_s):floatval($p->jitter_s ?? 0);
        // Ancien parseur (sans error_s) : un 0 y voulait dire « pas lu » (unité
        // ms/ns, LastRx « 35m »), jamais une mesure. On l'affiche comme absent.
        $nodata=!isset($p->error_s) && floatval($p->offset_s ?? 0)==0 && $err==0;
        $row=array("i"=>$i++,"st"=>$st,"mode"=>(string)($p->mode ?? "^"),
            "name"=>$name!=""?$name:$addr,"ip"=>$ip,
            "stratum"=>intval($p->stratum ?? 0),"poll"=>intval($p->poll ?? 0),"reach"=>$reach,
            "last_rx"=>($nodata && intval($p->last_rx ?? 0)==0)?-1:intval($p->last_rx ?? -1),
            "offset"=>floatval($p->offset_s ?? 0),"error"=>$err,"nodata"=>$nodata);
        $m["rows"][]=$row;
        if($reach>0){$m["reachable"]++;}
        if($st=="?"){$m["unreach"]++;}
        if($st=="x" || $st=="~"){$m["rejected"]++;}
        if($st=="*"){$m["sel"]=$row;}
    }
    // Source synchronisée en tête, puis l'ordre de gravité, puis l'ordre de chrony.
    usort($m["rows"],function($a,$b)use($states){
        $d=$states[$a["st"]]["rank"]-$states[$b["st"]]["rank"];
        return $d!=0?$d:$a["i"]-$b["i"];
    });
    $m["absent"]=ntpst_absent_list($m["rows"],$named,$m["cluster"]);
    return $m;
}

// Serveurs de la liste configurée que chrony ne suit pas. Seulement quand cette
// liste est réellement celle de chrony.conf, et quand le daemon donne les noms
// configurés (chronyc -N) : un nom inverse ne se compare à rien.
function ntpst_absent_list(array $rows,bool $named,bool $cluster):array{
    if(!$named || $cluster){return array();}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"))!=1){return array();}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/servers"));
    if(!is_object($json) || empty($json->Status) || !is_array($json->Servers ?? null)){return array();}
    $seen=array();
    foreach($rows as $r){$seen[strtolower($r["name"])]=true; if($r["ip"]!=""){$seen[strtolower($r["ip"])]=true;}}
    $out=array();
    foreach($json->Servers as $s){
        $n=trim((string)($s->Server ?? ""));
        if($n=="" || isset($seen[strtolower($n)])){continue;}
        $out[]=$n;
    }
    return $out;
}

// ---------------------------------------------------------------- formats

function ntpst_num(float $v):string{
    $a=abs($v);
    if($a<10){return rtrim(rtrim(sprintf("%.2f",$v),"0"),".");}
    if($a<100){return rtrim(rtrim(sprintf("%.1f",$v),"0"),".");}
    return sprintf("%.0f",$v);
}

// Durée en secondes vers la meilleure unité (ns, µs, ms, s).
function ntpst_secs(float $s,bool $signed):string{
    $a=abs($s);
    if($a<1e-6){$v=$s*1e9;$u="ns";}
    elseif($a<1e-3){$v=$s*1e6;$u="&micro;s";}
    elseif($a<1){$v=$s*1e3;$u="ms";}
    else{$v=$s;$u="s";}
    $txt=ntpst_num($v)." $u";
    if($signed){$txt=($s<0?"&minus;":"+").ltrim($txt,"-");}
    return $txt;
}

function ntpst_span(int $sec):string{
    if($sec<120){return "$sec s";}
    if($sec<7200){return round($sec/60)." min";}
    if($sec<172800){return round($sec/3600)." h";}
    return round($sec/86400)." d";
}

function ntpst_ago(int $sec):string{
    if($sec<0){return "<span class='ak-muted'>&mdash;</span>";}
    return str_replace("%s",ntpst_span($sec),ntpst_tr("{ntpst_ago}"));
}

function ntpst_every(int $exp):string{
    if($exp<0 || $exp>30){return "<span class='ak-muted'>&mdash;</span>";}
    $sec=1<<$exp;
    $txt=$sec<600?"$sec s":ntpst_span($sec);
    return str_replace("%s",$txt,ntpst_tr("{ntpst_every}"));
}

// Traduire AVANT de remplacer %s : le moteur ne reconnaît plus un token modifié.
function ntpst_tr(string $token):string{
    if(!isset($GLOBALS["NTPST_TR"][$token])){
        $GLOBALS["NTPST_TR"][$token]=(new template_admin())->_ENGINE_parse_body($token);
    }
    return $GLOBALS["NTPST_TR"][$token];
}

function ntpst_offset_tone(float $s):string{
    $a=abs($s);
    if($a>1){return "ak-err";}
    if($a>0.1){return "ak-warn";}
    return "";
}

// ---------------------------------------------------------------- widgets

function ntpst_banner(array $m):string{
    $svc=$m["service"];
    $right="<span class='ntpst-upd'>";
    if($m["version"]!=""){$right.="<span class='ak-tag ak-off'>chrony ".htmlspecialchars($m["version"])."</span>";}
    $right.="<span class='ak-note'>{ntpst_updated} <span class='ak-mono'>".date("H:i:s")."</span></span></span>";
    if($svc["known"] && !$svc["running"]){
        return "<div class='ak-banner ak-err ntpst-banner'><i class='fa-solid fa-circle-xmark'></i><span class='ak-banner-m'><strong>{ntpst_stopped}</strong></span>$right</div>";
    }
    $sel=$m["sel"];
    if($sel===null){
        $msg=count($m["rows"])==0?"{ntpst_no_source}":"{ntpst_not_sync}";
        return "<div class='ak-banner ak-warn ntpst-banner'><i class='fa-solid fa-triangle-exclamation'></i><span class='ak-banner-m'><strong>$msg</strong></span>$right</div>";
    }
    $chips=array();
    $chips[]="<span class='ak-tag'>{ntpst_stratum} {$sel["stratum"]}</span>";
    $chips[]="<span class='ak-tag'>{ntpst_offset} <span class='ak-mono'>".ntpst_secs($sel["offset"],true)."</span>&nbsp;&plusmn; ".ntpst_secs($sel["error"],false)."</span>";
    $chips[]="<span class='ak-tag'><i class='fa-regular fa-clock'></i> {ntpst_last_response} ".ntpst_ago($sel["last_rx"])."</span>";
    return "<div class='ak-banner ak-ok ntpst-banner'><i class='fa-solid fa-circle-check'></i>"
        ."<span class='ak-banner-m'><strong>{ntpst_sync_with} ".htmlspecialchars($sel["name"])."</strong>"
        ."<span class='ntpst-chips'>".implode("",$chips)."</span></span>$right</div>";
}

function ntpst_kpis(array $m):string{
    $h=array("<div class='ak-grid-4 ntpst-kpis'>");
    $h[]=ntpst_kpi_sources($m);
    $h[]=ntpst_kpi_reference($m);
    $h[]=ntpst_kpi_unreach($m);
    $h[]=ntpst_kpi_rejected($m);
    $h[]="</div>";
    return implode("",$h);
}

function ntpst_fig(string $tone,string $label,string $value,string $sub,string $ico):string{
    return "<div class='ak-card ak-fig $tone'><div class='ak-fig-txt'><span class='ak-fig-l'>$label</span>"
        ."<span class='ak-fig-v'>$value</span><span class='ak-fig-s'>$sub</span></div>"
        ."<span class='ak-ico ak-ico-sq $tone'><i class='$ico'></i></span></div>";
}

function ntpst_kpi_sources(array $m):string{
    $n=count($m["rows"]);
    $sub=str_replace("%s",$m["reachable"],ntpst_tr("{ntpst_n_reachable}"));
    return ntpst_fig($n>0 && $m["reachable"]==0?"ak-warn":"","{ntpst_sources}",(string)$n,$sub,"fa-solid fa-sitemap");
}

function ntpst_kpi_reference(array $m):string{
    if($m["sel"]===null){
        return ntpst_fig("ak-off","{ntpst_reference}","&mdash;","{ntpst_none_selected}","fa-solid fa-star");
    }
    return ntpst_fig("ak-ok","{ntpst_reference}","{ntpst_stratum} {$m["sel"]["stratum"]}",htmlspecialchars($m["sel"]["name"]),"fa-solid fa-star");
}

function ntpst_kpi_unreach(array $m):string{
    return ntpst_fig($m["unreach"]>0?"ak-warn":"","{ntpst_unreachable}",(string)$m["unreach"],"{ntpst_st_unreach_sub}","fa-solid fa-plug-circle-xmark");
}

function ntpst_kpi_rejected(array $m):string{
    return ntpst_fig($m["rejected"]>0?"ak-err":"","{ntpst_rejected}",(string)$m["rejected"],"{ntpst_rejected_sub}","fa-solid fa-ban");
}

function ntpst_table(array $m):string{
    $h=array();
    $h[]="<div class='ak-card ntpst-card'><div class='ak-card-h ntpst-card-h'><div><div class='ak-sec-t'>{ntpst_sources_t}</div>";
    $h[]="<div class='ak-note'>{ntpst_sources_sub}</div></div>".ntpst_daemon($m)."</div>";
    if(count($m["rows"])==0){
        $btn=$m["cluster"]?"":"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('fw.system.ntp.servers.php')\"><i class='fa-solid fa-bars'></i> {ntp_servers}</button>";
        $h[]="<div class='ak-empty-c'><i class='fa-regular fa-clock'></i><strong>{ntpst_empty_t}</strong><span>{ntpst_empty}</span>$btn</div></div>";
        return implode("",$h);
    }
    $h[]="<table class='ak-tbl ntpst-tbl'><thead><tr>";
    $h[]=ntpst_th("{ntpst_th_state}","state");
    $h[]=ntpst_th("{ntpst_th_server}","server");
    $h[]=ntpst_th("{ntpst_stratum}","stratum");
    $h[]="<th>{ntpst_th_reach}</th><th>{ntpst_last_response}</th><th>{ntpst_th_poll}</th>";
    $h[]=ntpst_th("{ntpst_offset}","offset");
    $h[]="<th>{ntpst_th_error}</th></tr></thead><tbody id='ntpst-rows'>";
    foreach($m["rows"] as $r){$h[]=ntpst_row($r);}
    $h[]="</tbody></table>";
    $h[]="<div class='ntpst-foot'>".str_replace("%s",count($m["rows"]),ntpst_tr("{ntpst_showing}"))."</div></div>";
    return implode("\n",$h);
}

function ntpst_th(string $label,string $key):string{
    return "<th aria-sort='none' data-k='$key'><button type='button' class='ntpst-sort' OnClick=\"NtpstSort('$key',this)\">$label <i class='fa-solid fa-sort'></i></button></th>";
}

function ntpst_daemon(array $m):string{
    $out=array();
    if($m["cluster"]){$out[]="<span class='ak-note'><i class='fa-solid fa-circle-nodes'></i> {ntpst_cluster_managed}</span>";}
    $svc=$m["service"];
    if($svc["known"] && $svc["running"]){
        $t="chronyd";
        if($svc["pid"]>0){$t.=" &middot; PID {$svc["pid"]}";}
        if($svc["uptime"]>0){$t.=" &middot; {ntpst_uptime} ".ntpst_span($svc["uptime"]);}
        $out[]="<span class='ak-tag ak-off'>$t</span>";
    }
    return "<div class='ntpst-daemon'>".implode("",$out)."</div>";
}

function ntpst_row(array $r):string{
    $states=ntpst_states();
    $s=$states[$r["st"]];
    $cls=$r["st"]=="*"?" class='ntpst-sel'":"";
    $known=$r["st"]!="?";
    return "<tr$cls data-state='".$s["rank"]."' data-server=\"".htmlspecialchars(strtolower($r["name"]))."\" data-stratum='{$r["stratum"]}' data-offset='".($known?abs($r["offset"]):99999)."'>"
        ."<td class='ak-nowrap'><span class='ak-pill {$s["tone"]}'><i class='{$s["ico"]}'></i> {$s["label"]} <span class='ntpst-sym'>({$r["st"]})</span></span></td>"
        ."<td>".ntpst_cell_server($r)."</td>"
        ."<td class='ak-nowrap'>".ntpst_cell_stratum($r)."</td>"
        ."<td class='ak-nowrap'>".ntpst_cell_reach($r["reach"])."</td>"
        ."<td class='ak-nowrap'>".ntpst_ago($r["last_rx"])."</td>"
        ."<td class='ak-nowrap'>".ntpst_every($r["poll"])."</td>"
        ."<td class='ak-nowrap'>".ntpst_cell_offset($r)."</td>"
        ."<td class='ak-nowrap ak-muted'>".($known && $r["last_rx"]>=0 && !$r["nodata"]?"&plusmn; ".ntpst_secs($r["error"],false):"&mdash;")."</td>"
        ."</tr>";
}

function ntpst_cell_server(array $r):string{
    $modes=array("^"=>"{ntpst_mode_server}","="=>"{ntpst_mode_peer}","#"=>"{ntpst_mode_ref}");
    $mode=$modes[$r["mode"]] ?? $modes["^"];
    $h="<span class='ak-name'>".htmlspecialchars($r["name"])."</span>";
    $sub=$mode;
    if($r["ip"]!="" && strcasecmp($r["ip"],$r["name"])!=0){$sub.=" &middot; <span class='ak-mono'>".htmlspecialchars($r["ip"])."</span>";}
    return $h."<span class='ntpst-sub'>$sub</span>";
}

function ntpst_cell_stratum(array $r):string{
    if($r["st"]=="?" && $r["stratum"]==0){return "<span class='ak-muted'>&mdash;</span>";}
    $hint="";
    if($r["stratum"]==1 && $r["mode"]!="#"){$hint=" <span class='ntpst-primary'>({ntpst_primary})</span>";}
    return "<span class='ak-mono'>{$r["stratum"]}</span>$hint";
}

// Registre de chrony : bit 0 = dernier sondage. Affiché du plus ancien (gauche)
// au plus récent (droite).
function ntpst_cell_reach(int $reach):string{
    $cells="";
    $ok=0;
    for($b=7;$b>=0;$b--){
        $on=($reach>>$b)&1;
        if($on){$ok++;}
        $cells.="<span class='ntpst-bit".($on?" on":"")."'></span>";
    }
    $label=str_replace("%s",$ok,ntpst_tr("{ntpst_reach_aria}"));
    $tone=$ok==8?"ntpst-r-ok":($ok==0?"ntpst-r-none":"ntpst-r-part");
    return "<span class='ntpst-reach $tone' role='img' aria-label=\"".htmlspecialchars(strip_tags($label))."\" title=\"".htmlspecialchars(strip_tags($label))."\">"
        ."<span class='ntpst-bits'>$cells</span><span class='ak-mono ntpst-rn'>$ok/8</span></span>";
}

function ntpst_cell_offset(array $r):string{
    if($r["st"]=="?" || $r["last_rx"]<0 || $r["nodata"]){return "<span class='ak-muted'>&mdash;</span>";}
    $tone=ntpst_offset_tone($r["offset"]);
    return "<span class='ak-chip ntpst-off $tone'>".ntpst_secs($r["offset"],true)."</span>";
}

function ntpst_absent(array $m):string{
    if(count($m["absent"])==0){return "";}
    $chips="";
    foreach($m["absent"] as $n){$chips.="<span class='ak-tag ak-warn'><i class='fa-regular fa-circle-xmark'></i> <span class='ak-mono'>".htmlspecialchars($n)."</span></span>";}
    return "<div class='ak-banner ak-warn ntpst-absent'><i class='fa-solid fa-triangle-exclamation'></i><span class='ak-banner-m'>"
        ."<strong>{ntpst_absent_t}</strong><span class='ntpst-absent-x'>{ntpst_absent}</span><span class='ak-chips'>$chips</span></span></div>";
}

function ntpst_legend():string{
    $h=array("<details class='ak-card ak-details ntpst-legend' id='ntpst-legend' OnToggle=\"NtpstLegend(this)\"><summary>");
    $h[]="<i class='fa-solid fa-circle-question'></i><span>{ntpst_legend_t}</span><i class='fa-solid fa-chevron-down ak-chev'></i></summary><div class='ntpst-leg'>";
    foreach(ntpst_states() as $sym=>$s){
        $h[]="<div class='ntpst-leg-i'><span class='ak-pill {$s["tone"]}'><i class='{$s["ico"]}'></i> {$s["label"]} ($sym)</span><span>{$s["help"]}</span></div>";
    }
    $h[]="<div class='ntpst-leg-i'><strong>{ntpst_stratum}</strong><span>{ntpst_hl_stratum}</span></div>";
    $h[]="<div class='ntpst-leg-i'><strong>{ntpst_th_reach}</strong><span>{ntpst_hl_reach}</span></div>";
    $h[]="</div></details>";
    return implode("",$h);
}

// ---------------------------------------------------------------- CSS / JS

function ntpst_style():string{
    if(isset($GLOBALS["NTPST_STYLE"])){return "";}
    $GLOBALS["NTPST_STYLE"]=true;
    $c=array();
    $c[]=".ntpst{margin-top:10px}.ntpst>*+*{margin-top:16px}";
    $c[]=".ntpst-banner{align-items:flex-start;padding:14px 16px}.ntpst-banner>i,.ntpst-banner>svg{margin-top:3px;font-size:18px}";
    $c[]=".ntpst-banner .ak-banner-m strong{font-size:15px}.ntpst-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}";
    $c[]=".ntpst-banner .ntpst-chips .ak-tag{background:#fff;color:var(--ak-text)}";
    $c[]=".ntpst-upd{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex:0 0 auto}";
    $c[]=".ntpst-card-h{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:16px 18px}";
    $c[]=".ntpst-daemon{display:flex;flex-direction:column;align-items:flex-end;gap:6px}";
    $c[]=".ntpst-tbl td{padding-top:12px;padding-bottom:12px}.ntpst-tbl th{white-space:normal;vertical-align:bottom}.ntpst-sort{white-space:nowrap}.ntpst-card{overflow-x:auto}.ntpst-tbl th,.ntpst-tbl td{padding-left:14px;padding-right:14px}";
    $c[]=".ntpst-tbl .ak-name,.ntpst-sub{white-space:nowrap}";
    $c[]=".ntpst-sort{border:0;background:none;padding:0;font:inherit;color:inherit;letter-spacing:inherit;text-transform:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:6px}";
    $c[]=".ntpst-sort:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}.ntpst-sort svg{opacity:.45}";
    $c[]=".ntpst-sel td:first-child{box-shadow:inset 3px 0 0 var(--ak-fill)}";
    $c[]=".ntpst-sym{font-family:monospace;opacity:.8}.ntpst-sub{display:block;font-size:13px;color:var(--ak-muted)}.ntpst-primary{color:var(--ak-fill)}";
    $c[]=".ntpst-reach{display:inline-flex;align-items:center;gap:8px}.ntpst-bits{display:inline-flex;gap:2px}";
    $c[]=".ntpst-bit{width:12px;height:12px;border-radius:2px;border:1px solid #cbd5e1;background:#fff;box-sizing:border-box}";
    $c[]=".ntpst-bit.on{background:var(--ak-fill);border-color:var(--ak-fill)}";
    $c[]=".ntpst-r-ok .ntpst-rn{color:var(--ak-fill)}.ntpst-r-part .ntpst-rn{color:#865300}.ntpst-r-none .ntpst-rn{color:#ba1a1a}";
    $c[]=".ntpst-off{font-size:13px}.ntpst-off.ak-warn,.ntpst-off.ak-err{background:var(--ak-tbg);color:var(--ak-tfg)}";
    $c[]=".ntpst-foot{padding:12px 18px;border-top:1px solid var(--ak-border);font-size:13px;color:var(--ak-text2)}";
    $c[]=".ntpst-absent{align-items:flex-start}.ntpst-absent-x{display:block;margin:4px 0 8px}";
    $c[]=".ntpst-leg{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 24px;padding:16px 18px}";
    $c[]=".ntpst-leg-i{display:flex;flex-direction:column;align-items:flex-start;gap:4px;font-size:13px;color:var(--ak-text2)}";
    $c[]="@media(max-width:1200px){.ntpst-leg{grid-template-columns:1fr}}";
    return "<style>".implode("\n",$c)."</style>";
}

// Rechargement toutes les 60 s (chrony interroge au mieux toutes les 64 s),
// suspendu pendant une modale, arrêté quand l'onglet est quitté.
function ntpst_js():string{
    $page=CurrentPageName();
    return "<script>
window.NtpstS=window.NtpstS||{k:'',dir:1,legend:false};
function NtpstReload(){ LoadAjaxSilent('table-loader-ntp_servers-service2','$page?list2=yes'); }
function NtpstArm(){
  if(window.NtpstTimer){clearTimeout(window.NtpstTimer);}
  if(NtpstS.k){ NtpstApplySort(); }
  var l=document.getElementById('ntpst-legend'); if(l && NtpstS.legend){ l.open=true; }
  window.NtpstTimer=setTimeout(function(){
    if(!document.getElementById('table-loader-ntp_servers-service2')){return;}
    if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){ NtpstArm(); return; }
    NtpstReload();
  },60000);
}
function NtpstLegend(d){ NtpstS.legend=d.open; }
function NtpstSort(k,btn){
  if(NtpstS.k===k){ NtpstS.dir=-NtpstS.dir; } else { NtpstS.k=k; NtpstS.dir=1; }
  NtpstApplySort();
}
function NtpstApplySort(){
  var tb=document.getElementById('ntpst-rows'); if(!tb){return;}
  var k=NtpstS.k, dir=NtpstS.dir, rows=[].slice.call(tb.rows);
  rows.sort(function(a,b){
    var x=a.getAttribute('data-'+k), y=b.getAttribute('data-'+k);
    if(k!=='server'){ x=parseFloat(x); y=parseFloat(y); }
    return (x<y?-1:(x>y?1:0))*dir;
  });
  for(var i=0;i<rows.length;i++){ tb.appendChild(rows[i]); }
  var ths=document.querySelectorAll('.ntpst-tbl th[data-k]');
  for(var j=0;j<ths.length;j++){ ths[j].setAttribute('aria-sort', ths[j].getAttribute('data-k')===k?(dir>0?'ascending':'descending'):'none'); }
}
</script>";
}
