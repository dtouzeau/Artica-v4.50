<?php
// fw.nginx.DecisionIP.honeypot.php — DecisionIP honeypot: decoy ports that no real service
// uses; any address touching one is banned on this server (3 h by default, decisionip.conf
// template) and reported to DecisionIP.
//
// Stitch redesign 2026-11-21 (stitch_honeypot_console_redesign). REAL DATA ONLY:
//   GET  /decisionip/config               honeypot.honeypot_enabled, ports.port ("<n> <name>")
//   GET  /decisionip/honeypot/stats       window_seconds, generated_at, ports[{port, ips[{ip,attempts}]}]
//   GET  /system/network/openports        who listens on each port (decisionip = the trap itself)
//   GET  /decisionip/honeypot/enable|disable, POST|DELETE /decisionip/honeypot/ports
// The left column (decision-ip-honeypot-status, service_status(), CurrentIPset()) is shared
// and refreshed by its own timer through honeypot-top: kept exactly as it was.
// One model (hpot_model) feeds every widget; one widget = one function.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["honeypot-top"])){honeypot_top();exit;}
if(isset($_GET["hp-host"])){hpot_host();exit;}
if(isset($_GET["tiny"])){tiny();exit;}
if(isset($_GET["enable-js"])){hpot_enable_js();exit;}
if(isset($_GET["disable-js"])){hpot_disable_js();exit;}
if(isset($_GET["delete-port"])){hpot_delete_js();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["port"])){port_save();exit;}
if(isset($_POST["hpot-enable"])){hpot_enable_save();exit;}
if(isset($_POST["hpot-disable"])){hpot_disable_save();exit;}
if(isset($_POST["hpot-delete"])){hpot_delete_save();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $html=$tpl->page_header("Honeypot","fa-regular fa-honey-pot",
        "{hpot_header_explain}","$page?table-start=yes",
        "decisionip-honeypot","progress-honeypot-restart",false);
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("DecisionIP/Honeypot",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ── Left column (unchanged) ──────────────────────────────────────────────

// honeypot-top keeps its periodic refresh; its only job now is to refresh the left status column.
function honeypot_top():bool{
    $page=CurrentPageName();
    echo "<script>LoadAjaxSilent('decision-ip-honeypot-status','$page?service-status=yes');</script>";
    return true;
}

function service_status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/status"),);

    if(!isset($json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,"{protocol_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,$json->Error,"{error}"));
        return true;
    }
    $restart=$tpl->framework_buildjs("/decisionipapps/restart",
        "decisionip.restart.progress",
        "decisionip.restart.progress.log",
        "progress-honeypot-restart");
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DECISIONIP",$restart);

    $CurDB=CurrentIPset();
    $html[]="<div style='border:1px solid #1ab394;border-radius:5px;padding:5px'>";
    $html[]="<table style='width:100%'>";
    foreach ($CurDB as $db=>$Number){
        $color="#1ab394";
        $html[]="<tr>";
        $tmp="&nbsp;";;
        $html[]="<td style='width:1%;padding-top:5px'>";
        if($Number==0){
            $color="#cccccc";
        }
        if($db=="activeattacks-tmp"){
            continue;
        }
        $Number=$tpl->FormatNumber($Number);
        $html[]=$tpl->Round(32,$color);
        $html[]="</td>";
        $html[]="<td style='width:100%;padding-left:8px;padding-top:5px'><strong>{didb_{$db}}$tmp {$Number} {items}</strong></td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="</div>";


    echo $tpl->_ENGINE_parse_body($html);


    return true;
}
function CurrentIPset():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/ipsets"),true);
    $databases=array();
    foreach ($json as $ipset){
        if(!$ipset["enabled"]){
            continue;
        }
        $databases[$ipset["database"]]=$ipset["entries"];
    }
    return $databases;
}

// ── Model ─────────────────────────────────────────────────────────────────

// Plain-language name of the services the default decoys imitate; other ports keep their configured name.
function hpot_service($port){
    $s=array(21=>"{hpot_svc_ftp}",23=>"{hpot_svc_telnet}",623=>"{hpot_svc_ipmi}",2222=>"{hpot_svc_directadmin}",
        3389=>"{hpot_svc_rdp}",5900=>"{hpot_svc_vnc}",5901=>"{hpot_svc_vnc}",5902=>"{hpot_svc_vnc}",
        5985=>"{hpot_svc_winrm}",5986=>"{hpot_svc_winrm}",8291=>"{hpot_svc_mikrotik}",8728=>"{hpot_svc_mikrotik}",
        8729=>"{hpot_svc_mikrotik}",10000=>"{hpot_svc_webmin}");
    $port=intval($port);
    return isset($s[$port])?$s[$port]:"";
}

function hpot_keys_valid($sections){
    if(!isset($sections["auth"]) || !is_array($sections["auth"])){return false;}
    $u=isset($sections["auth"]["user_key"])?(string)$sections["auth"]["user_key"]:"";
    $s=isset($sections["auth"]["server_key"])?(string)$sections["auth"]["server_key"]:"";
    return strlen($u)>=5 && strlen($s)>=5;
}

// Port -> list of program names listening on it, from /system/network/openports. null = unknown.
function hpot_listeners(){
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/openports"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["ports"]) || !is_array($json["ports"])){return null;}
    $out=array();
    foreach($json["ports"] as $p){
        $n=intval(isset($p["port"])?$p["port"]:0);
        if($n<1){continue;}
        $names=array();
        $procs=(isset($p["processes"]) && is_array($p["processes"]))?$p["processes"]:array();
        foreach($procs as $pr){ $nm=trim((string)(isset($pr["name"])?$pr["name"]:"")); if($nm!=""){$names[$nm]=true;} }
        if(count($names)==0){$names["?"]=true;}
        $out[$n]=array_keys($names);
    }
    return $out;
}

function hpot_model(){
    $m=array("state"=>"ok","error"=>"","enabled"=>false,"ports"=>array(),"window"=>600,"generated"=>"",
        "stats_ok"=>false,"stats_error"=>"","byport"=>array(),"caught"=>array(),"knocks"=>0,"listen"=>null);
    $conf=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);
    if(!is_array($conf) || !isset($conf["data"]["sections"]) || !is_array($conf["data"]["sections"])){
        $m["state"]="error";
        $m["error"]=(is_array($conf) && isset($conf["error"]))?(string)$conf["error"]:"{hpot_err_config}";
        return $m;
    }
    $S=$conf["data"]["sections"];
    if(!hpot_keys_valid($S)){ $m["state"]="unregistered"; return $m; }
    $m["enabled"]=(isset($S["honeypot"]["honeypot_enabled"]) && strtolower(trim((string)$S["honeypot"]["honeypot_enabled"]))=="true");
    // A single "port = …" line comes back as a string, several as an array.
    $raw=isset($S["ports"]["port"])?$S["ports"]["port"]:array();
    if(!is_array($raw)){$raw=array($raw);}
    foreach($raw as $line){
        if(!preg_match('#^\s*([0-9]+)\s*(.*)$#',(string)$line,$re)){continue;}
        $n=intval($re[1]);
        if($n<1 || $n>65535){continue;}
        $m["ports"][$n]=trim($re[2]);
    }
    ksort($m["ports"]);
    $m["listen"]=hpot_listeners();

    if($m["enabled"]){
        $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/stats"),true);
        if(!is_array($st)){
            $m["stats_error"]="{hpot_err_stats}";
        }elseif(isset($st["Status"]) && !$st["Status"]){
            $m["stats_error"]=isset($st["Error"])?(string)$st["Error"]:"{hpot_err_stats}";
        }else{
            $m["stats_ok"]=true;
            if(!empty($st["window_seconds"])){$m["window"]=max(60,intval($st["window_seconds"]));}
            $m["generated"]=isset($st["generated_at"])?(string)$st["generated_at"]:"";
            $sp=(isset($st["ports"]) && is_array($st["ports"]))?$st["ports"]:array();
            foreach($sp as $p){
                $n=intval(isset($p["port"])?$p["port"]:0);
                $ips=(isset($p["ips"]) && is_array($p["ips"]))?$p["ips"]:array();
                foreach($ips as $r){
                    $ip=trim((string)(isset($r["ip"])?$r["ip"]:""));
                    $a=intval(isset($r["attempts"])?$r["attempts"]:0);
                    if($ip=="" || $a<=0){continue;}
                    if(!isset($m["byport"][$n])){$m["byport"][$n]=array("knocks"=>0,"ips"=>0);}
                    $m["byport"][$n]["knocks"]+=$a;
                    $m["byport"][$n]["ips"]++;
                    if(!isset($m["caught"][$ip])){$m["caught"][$ip]=array("ip"=>$ip,"knocks"=>0,"ports"=>array());}
                    $m["caught"][$ip]["knocks"]+=$a;
                    $m["caught"][$ip]["ports"][$n]=true;
                    $m["knocks"]+=$a;
                }
            }
            usort($m["caught"],function($a,$b){ if($a["knocks"]==$b["knocks"]){return strcmp($a["ip"],$b["ip"]);} return ($a["knocks"]>$b["knocks"])?-1:1; });
        }
    }
    return $m;
}

// Listening state of one decoy port: on / off / conflict(<names>) / unknown.
function hpot_port_state($m,$port){
    if(!is_array($m["listen"])){return array("unknown","");}
    $names=isset($m["listen"][$port])?$m["listen"][$port]:array();
    $others=array();
    $trap=false;
    foreach($names as $nm){ if(stripos($nm,"decisionip")!==false){$trap=true;}else{$others[]=$nm;} }
    if(count($others)>0){return array("conflict",implode(", ",$others));}
    if($trap){return array("on","");}
    return array($m["enabled"]?"down":"off","");
}

function hpot_minutes($m){ return strval(max(1,intval(round($m["window"]/60)))); }

function hpot_snapshot_time($m){
    if($m["generated"]==""){return "";}
    $t=strtotime($m["generated"]);
    if($t===false || $t<=0){return htmlspecialchars($m["generated"]);}
    return date("H:i:s",$t);
}

// ── Page ──────────────────────────────────────────────────────────────────

function table_start():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:330px;vertical-align: top'><div id='decision-ip-honeypot-status' style='width:330px'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>
    <div id='honeypot-top' style='display:none'></div>
    <div id='hp-host' class='hpot' aria-live='polite'></div>
    </td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("honeypot-top",$page,"honeypot-top=yes");
    $html[]="Loadjs('$page?tiny=yes');";
    $html[]="LoadAjaxSilent('honeypot-top','$page?honeypot-top=yes');";
    $html[]="LoadAjax('hp-host','$page?hp-host=yes');";
    $html[]=$js;
    $html[]="</script>";
    echo hpot_style().$tpl->_ENGINE_parse_body($html).hpot_js($page);
    return true;
}

function tiny(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?port-js=yes')", ico_plus, "{hpot_add_port}");
    $topbuttons[]=array("HpotReload(true);", ico_refresh, "{refresh}");
    $TINY_ARRAY["TITLE"]="Honeypot";
    $TINY_ARRAY["ICO"]="fa-regular fa-honey-pot";
    $TINY_ARRAY["EXPL"]="{hpot_header_explain}";
    $TINY_ARRAY["URL"]="decisionip-honeypot";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";
}

function hpot_host(){
    $tpl=new template_admin();
    $m=hpot_model();
    $h=array();
    $h[]=hpot_banner($m);
    if($m["state"]=="error"){
        $h[]=hpot_alert_error($m["error"]);
    }elseif($m["state"]=="unregistered"){
        $h[]=hpot_unregistered();
    }else{
        if(!$m["enabled"]){
            $h[]=hpot_setup();
        }elseif($m["stats_error"]!=""){
            $h[]=hpot_alert_error($m["stats_error"]);
        }else{
            $h[]=hpot_activity($m);
            if(count($m["caught"])>0){$h[]=hpot_caught($m);}
        }
        $h[]=hpot_ports($m);
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$h))."<script>if(window.jQuery&&jQuery.fn.peity){jQuery('#hp-host .hpot-peity').peity('donut',{fill:['#005447','#e5e7eb'],radius:16,innerRadius:10});}</script>";
}

// ── Widgets ───────────────────────────────────────────────────────────────

function hpot_banner($m){
    $page=CurrentPageName();
    $right="";
    if($m["state"]=="ok"){
        if($m["enabled"]){
            $right="<div class='hpot-onoff'><span class='hpot-pill hpot-pill-on'><span class='hpot-dot'></span>{hpot_on}</span>".
                "<button type='button' class='hpot-btn-q' onclick=\"Loadjs('$page?disable-js=yes')\"><i class='fas fa-power-off' aria-hidden='true'></i>{hpot_turn_off}</button></div>";
        }else{
            $right="<div class='hpot-onoff'><span class='hpot-pill hpot-pill-off'><span class='hpot-dot'></span>{hpot_off}</span>".
                "<button type='button' class='hpot-btn' onclick=\"Loadjs('$page?enable-js=yes')\"><i class='fas fa-play' aria-hidden='true'></i>{hpot_activate}</button></div>";
        }
    }
    return "<section class='hpot-banner'>".
        "<div class='hpot-banner-ico' aria-hidden='true'><i class='fa-regular fa-honey-pot'></i></div>".
        "<div class='hpot-banner-txt'><h2 class='hpot-banner-t'>{hpot_banner_title}</h2><p class='hpot-banner-p'>{hpot_banner_text}</p>".
        "<div class='hpot-chips'>".
        "<span class='hpot-chip'><i class='fas fa-ghost' aria-hidden='true'></i>{hpot_chip_noservice}</span>".
        "<span class='hpot-chip'><i class='fas fa-ban' aria-hidden='true'></i>{hpot_chip_ban}</span>".
        "<span class='hpot-chip'><i class='fas fa-satellite-dish' aria-hidden='true'></i>{hpot_chip_report}</span>".
        "</div></div>$right</section>";
}

function hpot_setup(){
    $page=CurrentPageName();
    return "<section class='hpot-card hpot-setup'>".
        "<h3 class='hpot-card-t'>{hpot_setup_title}</h3>".
        "<ol class='hpot-steps'>".
        "<li><span class='hpot-step-n'>1</span><div><b>{hpot_step1_t}</b><span>{hpot_step1_d}</span></div></li>".
        "<li><span class='hpot-step-n'>2</span><div><b>{hpot_step2_t}</b><span>{hpot_step2_d}</span></div></li>".
        "<li><span class='hpot-step-n'>3</span><div><b>{hpot_step3_t}</b><span>{hpot_step3_d}</span></div></li>".
        "</ol>".
        "<button type='button' class='hpot-btn' onclick=\"Loadjs('$page?enable-js=yes')\"><i class='fas fa-play' aria-hidden='true'></i>{hpot_activate}</button>".
        "</section>";
}

function hpot_activity($m){
    $tpl=new template_admin();
    $min=hpot_minutes($m);
    $snap=hpot_snapshot_time($m);
    $upd=($snap!="")?"<span class='hpot-upd'><i class='fas fa-clock-rotate-left' aria-hidden='true'></i>".str_replace("%s",$snap,$tpl->_ENGINE_parse_body("{hpot_updated}"))."</span>":"";
    $h=array();
    $h[]="<div class='hpot-sec-head'><h3 class='hpot-sec-t'>{hpot_activity_title}</h3>$upd</div>";
    if($m["knocks"]==0){
        $h[]="<section class='hpot-card hpot-quiet'><div class='hpot-quiet-ico'><i class='fas fa-moon' aria-hidden='true'></i></div><div>".
            "<b class='hpot-card-t'>".str_replace("%s",$min,$tpl->_ENGINE_parse_body("{hpot_quiet_title}"))."</b>".
            "<p class='hpot-muted'>{hpot_quiet_text}</p></div></section>";
        return implode("\n",$h);
    }
    $h[]="<div class='hpot-kpis'>";
    $h[]=hpot_kpi_knocks($m);
    $h[]=hpot_kpi_caught($m);
    $h[]=hpot_kpi_ports($m);
    $h[]="</div>";
    return implode("\n",$h);
}

function hpot_kpi($icon,$label,$value,$sub,$extra=""){
    return "<div class='hpot-card hpot-kpi'><div class='hpot-kpi-l'><span>$label</span><i class='fas $icon' aria-hidden='true'></i></div>".
        "<div class='hpot-kpi-row'><div class='hpot-kpi-v'>$value</div>$extra</div><div class='hpot-kpi-s'>$sub</div></div>";
}

function hpot_kpi_knocks($m){
    $tpl=new template_admin();
    $snap=hpot_snapshot_time($m);
    $sub=($snap!="")?str_replace("%s",$snap,$tpl->_ENGINE_parse_body("{hpot_snapshot_at}")):"&nbsp;";
    return hpot_kpi("fa-bolt",str_replace("%s",hpot_minutes($m),$tpl->_ENGINE_parse_body("{hpot_kpi_knocks}")),number_format($m["knocks"]),$sub);
}

function hpot_kpi_caught($m){
    return hpot_kpi("fa-user-lock","{hpot_kpi_caught}",number_format(count($m["caught"])),"{hpot_kpi_caught_sub}");
}

function hpot_kpi_ports($m){
    $tpl=new template_admin();
    $hit=count($m["byport"]);$all=count($m["ports"]);
    $val=str_replace(array("%a","%b"),array(strval($hit),"<span class='hpot-of'>".strval($all)."</span>"),$tpl->_ENGINE_parse_body("{hpot_x_of_y}"));
    $sub=str_replace("%s",strval(max(0,$all-$hit)),$tpl->_ENGINE_parse_body("{hpot_kpi_ports_sub}"));
    $pie=($all>0)?"<span class='hpot-peity' aria-hidden='true'>".min($hit,$all)."/$all</span>":"";
    return hpot_kpi("fa-network-wired","{hpot_kpi_ports}",$val,$sub,$pie);
}

function hpot_port_chip($port,$m){
    $svc=hpot_service($port);
    if($svc=="" && isset($m["ports"][$port]) && $m["ports"][$port]!=""){$svc=htmlspecialchars($m["ports"][$port]);}
    return "<span class='hpot-pchip'><b>".intval($port)."</b>".($svc!=""?" $svc":"")."</span>";
}

function hpot_caught($m){
    $tpl=new template_admin();
    $n=count($m["caught"]);
    $max=max(1,intval($m["caught"][0]["knocks"]));
    $h=array();
    $h[]="<section class='hpot-card hpot-tcard'>";
    $h[]="<div class='hpot-thead'><div><h3 class='hpot-card-t'>{hpot_caught_title} <span class='hpot-count'>".str_replace("%s",number_format($n),$tpl->_ENGINE_parse_body("{hpot_n_addresses}"))."</span></h3>".
        "<p class='hpot-muted'>".str_replace("%s",hpot_minutes($m),$tpl->_ENGINE_parse_body("{hpot_caught_text}"))."</p></div></div>";
    $h[]="<table class='hpot-tbl'><thead><tr><th>{hpot_col_address}</th><th>{hpot_col_ports}</th><th class='hpot-r'>{hpot_col_knocks}</th></tr></thead><tbody id='hpot-caught'>";
    $i=0;
    foreach($m["caught"] as $r){
        $i++;
        $chips=array();
        foreach(array_keys($r["ports"]) as $p){ $chips[]=hpot_port_chip($p,$m); }
        $w=round($r["knocks"]*100/$max,1);
        $hide=($i>10)?" class='hpot-more' style='display:none'":"";
        $h[]="<tr$hide><td><span class='hpot-ip'>".htmlspecialchars($r["ip"])."</span></td><td>".implode(" ",$chips)."</td>".
            "<td class='hpot-r'><span class='hpot-kn'>".number_format($r["knocks"])."</span><span class='hpot-bar'><span style='width:$w%'></span></span></td></tr>";
    }
    $h[]="</tbody></table>";
    if($n>10){
        $h[]="<div class='hpot-tfoot'><button type='button' class='hpot-link' onclick=\"HpotShowAll(this)\">".str_replace("%s",number_format($n),$tpl->_ENGINE_parse_body("{hpot_show_all}"))."</button></div>";
    }
    $h[]="</section>";
    return implode("\n",$h);
}

function hpot_ports($m){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $min=hpot_minutes($m);
    $off=!$m["enabled"];
    $h=array();
    $h[]="<section class='hpot-card hpot-tcard".($off?" hpot-dim":"")."'>";
    $h[]="<div class='hpot-thead'><div><h3 class='hpot-card-t'>{hpot_ports_title} <span class='hpot-count'>".str_replace("%s",number_format(count($m["ports"])),$tpl->_ENGINE_parse_body("{hpot_n_configured}"))."</span></h3>".
        "<p class='hpot-muted'>{hpot_ports_text}</p></div>".
        "<button type='button' class='hpot-btn' onclick=\"Loadjs('$page?port-js=yes')\"><i class='fas fa-plus' aria-hidden='true'></i>{hpot_add_port}</button></div>";
    if(count($m["ports"])==0){
        $h[]="<div class='hpot-empty'><i class='fas fa-door-open' aria-hidden='true'></i><b>{hpot_noport_title}</b><p class='hpot-muted'>{hpot_noport_text}</p></div></section>";
        return implode("\n",$h);
    }
    $h[]="<table class='hpot-tbl'><thead><tr><th>{hpot_col_port}</th><th>{hpot_col_status}</th><th>".str_replace("%s",$min,$tpl->_ENGINE_parse_body("{hpot_col_last}"))."</th><th class='hpot-r'><span class='sr-only'>{hpot_col_action}</span></th></tr></thead><tbody>";
    foreach($m["ports"] as $port=>$name){
        $svc=hpot_service($port);
        $label=($svc!="")?$svc:htmlspecialchars($name);
        list($st,$who)=hpot_port_state($m,$port);
        $cls="";
        switch($st){
            case "on": $status="<span class='hpot-st hpot-st-on'><i class='fas fa-ear-listen' aria-hidden='true'></i>{hpot_st_on}</span>"; break;
            case "conflict":
                $status="<span class='hpot-st hpot-st-bad' title=\"{hpot_st_conflict_tip}\"><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>".str_replace("%s",htmlspecialchars($who),$tpl->_ENGINE_parse_body("{hpot_st_conflict}"))."</span>";
                $cls=" class='hpot-row-bad'";
                break;
            case "down": $status="<span class='hpot-st hpot-st-warn'><i class='fas fa-ear-deaf' aria-hidden='true'></i>{hpot_st_down}</span>"; break;
            case "off": $status="<span class='hpot-st hpot-st-off'><i class='fas fa-power-off' aria-hidden='true'></i>{hpot_st_off}</span>"; break;
            default: $status="<span class='hpot-muted'>—</span>";
        }
        if(isset($m["byport"][$port])){
            $b=$m["byport"][$port];
            $act=str_replace(array("%k","%a"),array("<b>".number_format($b["knocks"])."</b>",number_format($b["ips"])),$tpl->_ENGINE_parse_body("{hpot_knocks_addrs}"));
        }else{
            $act="<span class='hpot-muted'>—</span>";
        }
        $aria=str_replace("%s",strval($port),$tpl->_ENGINE_parse_body("{hpot_remove_aria}"));
        $h[]="<tr$cls><td><div class='hpot-port'><b>".intval($port)."</b><span>$label</span></div></td><td>$status</td><td>$act</td>".
            "<td class='hpot-r'><button type='button' class='hpot-del' aria-label=\"$aria\" title=\"$aria\" onclick=\"Loadjs('$page?delete-port=".intval($port)."')\"><i class='fas fa-trash-can' aria-hidden='true'></i></button></td></tr>";
    }
    $h[]="</tbody></table></section>";
    return implode("\n",$h);
}

function hpot_unregistered(){
    return "<section class='hpot-card hpot-state'><div class='hpot-state-ico'><i class='fas fa-key' aria-hidden='true'></i></div><div>".
        "<b class='hpot-card-t'>{hpot_unreg_title}</b><p class='hpot-muted'>{hpot_unreg_text}</p>".
        "<button type='button' class='hpot-btn' onclick=\"LoadAjax('MainContent','fw.nginx.DecisionIP.php')\"><i class='fas fa-sliders' aria-hidden='true'></i>{hpot_open_settings}</button></div></section>";
}

function hpot_alert_error($err){
    $e=(substr($err,0,1)=="{")?$err:htmlspecialchars($err);
    return "<section class='hpot-card hpot-state hpot-state-err' role='alert'><div class='hpot-state-ico'><i class='fas fa-circle-exclamation' aria-hidden='true'></i></div><div>".
        "<b class='hpot-card-t'>{hpot_err_title}</b><div class='hpot-err'>$e</div>".
        "<button type='button' class='hpot-btn hpot-btn-err' onclick='HpotReload(true);'><i class='fas fa-rotate-right' aria-hidden='true'></i>{hpot_try_again}</button></div></section>";
}

// ── Actions ───────────────────────────────────────────────────────────────

function hpot_after_js(){
    return "HpotReload(true);";
}

function hpot_enable_js(){
    $tpl=new template_admin();
    $tpl->js_dialog_confirm_action("{hpot_on_confirm}","hpot-enable","none",hpot_after_js());
}
function hpot_disable_js(){
    $tpl=new template_admin();
    $tpl->js_dialog_confirm_action("{hpot_off_confirm}","hpot-disable","none",hpot_after_js());
}
function hpot_delete_js(){
    $tpl=new template_admin();
    $port=intval($_GET["delete-port"]);
    if($port<1 || $port>65535){header("content-type: application/x-javascript");return;}
    $tpl->js_dialog_confirm_action("{hpot_del_confirm}","hpot-delete",strval($port),hpot_after_js());
}

// Daemon answer -> "" on success, else the error text (echoed: form_outside/confirm show it).
function hpot_rest_error($raw){
    $j=json_decode((string)$raw,true);
    if(!is_array($j)){return "{hpot_err_unreachable}";}
    if(isset($j["success"]) && !$j["success"]){ return isset($j["error"])?(string)$j["error"]:"{hpot_err_unreachable}"; }
    if(isset($j["Status"]) && !$j["Status"]){ return isset($j["Error"])?(string)$j["Error"]:"{hpot_err_unreachable}"; }
    return "";
}

function hpot_enable_save(){
    $tpl=new template_admin();
    $err=hpot_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/enable"));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return;}
    admin_tracks("Enable the Honeypot in DecisionIP feature");
}
function hpot_disable_save(){
    $tpl=new template_admin();
    $err=hpot_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/disable"));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return;}
    admin_tracks("Disable the Honeypot in DecisionIP feature");
}
function hpot_delete_save(){
    $tpl=new template_admin();
    $port=intval($_POST["hpot-delete"]);
    if($port<1 || $port>65535){echo $tpl->_ENGINE_parse_body("{hpot_err_port}");return;}
    $err=hpot_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/decisionip/honeypot/ports/$port"));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return;}
    admin_tracks("Remove the Honeypot port $port in DecisionIP feature");
}

function port_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{hpot_add_port}","$page?port-popup=yes",600);
}

function port_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Ports already used by a real program: the form warns before such a port is chosen.
    $used=array();
    $l=hpot_listeners();
    if(is_array($l)){
        foreach($l as $p=>$names){
            $others=array();
            foreach($names as $nm){ if(stripos($nm,"decisionip")===false){$others[]=$nm;} }
            if(count($others)>0){$used[strval($p)]=implode(", ",$others);}
        }
    }
    $form[]=$tpl->field_numeric("port","{hpot_f_port}","","{hpot_f_port_help}");
    $form[]=$tpl->field_text("desc","{hpot_f_name}","",true,"{hpot_f_name_help}");
    $picks=array(23=>"Telnet",3389=>"Remote Desktop",5900=>"VNC",10000=>"Webmin");
    $chips=array();
    foreach($picks as $p=>$n){ $chips[]="<button type='button' class='hpot-ex' onclick=\"HpotPick($p,'$n')\">$p $n</button>"; }
    $html=array();
    $html[]="<div class='hpot hpot-modal'>";
    $html[]="<p class='hpot-muted'>{hpot_add_explain}</p>";
    $html[]="<div class='hpot-picks'><span>{hpot_quick_picks}</span>".implode("",$chips)."</div>";
    $html[]="<div id='hpot-conflict' class='hpot-warn' role='status' style='display:none'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span></span></div>";
    $html[]=$tpl->form_outside("",$form,"","{hpot_add_btn}","dialogInstance2.close();HpotReload(true);","AsFirewallManager");
    $html[]="</div>";
    $tplC=$tpl->_ENGINE_parse_body("{hpot_conflict_warn}");
    $js="<script>
(function(){
 var used=".json_encode($used).", tpl=".json_encode(html_entity_decode($tplC,ENT_QUOTES,"UTF-8")).";
 function inp(n){ return document.querySelector('.hpot-modal [name='+n+']') || document.querySelector('.hpot-modal input[id^='+n+']'); }
 function check(){ var i=inp('port'), w=document.getElementById('hpot-conflict'); if(!i||!w){return;}
   var v=String(parseInt(i.value,10)); if(used[v]){ w.lastChild.textContent=tpl.replace('%p',used[v]).replace('%n',v); w.style.display=''; } else { w.style.display='none'; } }
 window.HpotPick=function(p,n){ var i=inp('port'), d=inp('desc'); if(i){i.value=p;} if(d){d.value=n;} check(); };
 var i=inp('port'); if(i){ i.addEventListener('input',check); i.addEventListener('change',check); }
})();
</script>";
    echo hpot_style().$tpl->_ENGINE_parse_body($html).$js;
    return true;
}

function port_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $port=intval($_POST['port']);
    $desc=trim((string)$_POST['desc']);
    if($port<1 || $port>65535){echo $tpl->_ENGINE_parse_body("{hpot_err_port}");return false;}
    if(strlen($desc)==0){echo $tpl->_ENGINE_parse_body("{hpot_err_name}");return false;}
    $err=hpot_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/decisionip/honeypot/ports",array("port"=>$port,"name"=>$desc)));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return false;}
    return admin_tracks("Add the Honeypot port $port ($desc) in DecisionIP feature");
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function hpot_js($page){
    return "<script src='js/jquery.peity.min.js'></script>
<script>
function HpotModalOpen(){
    if(document.querySelector('.modal.in, .modal.show')){ return true; }
    var sw=document.querySelector('.sweet-alert.visible, .sweet-alert.showSweetAlert');
    return !!(sw && sw.offsetParent!==null);
}
function HpotReload(force){
    if(!document.getElementById('hp-host')){ return; }
    if(!force && (document.hidden || HpotModalOpen())){ return; }
    LoadAjaxSilent('hp-host','$page?hp-host=yes');
}
function HpotShowAll(btn){
    var r=document.querySelectorAll('#hpot-caught .hpot-more');
    for(var i=0;i<r.length;i++){ r[i].style.display=''; }
    if(btn && btn.parentNode){ btn.parentNode.style.display='none'; }
}
(function(){
    if(window.HpotTimer){ clearInterval(window.HpotTimer); }
    window.HpotTimer=setInterval(function(){
        if(!document.getElementById('hp-host')){ clearInterval(window.HpotTimer); window.HpotTimer=null; return; }
        HpotReload(false);
    },10000);
})();
</script>";
}

function hpot_style(){
    if(isset($GLOBALS["HPOT_STYLE"])){return "";}
    $GLOBALS["HPOT_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.hpot{display:flex;flex-direction:column;gap:16px;color:#111827;font-size:14px}
.hpot *{box-sizing:border-box}
.hpot h2,.hpot h3{margin:0}
.hpot-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.hpot-muted{color:#6b7280;font-size:13px;margin:2px 0 0}
.hpot-banner{display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-left:4px solid $accent;border-radius:12px;padding:20px 24px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.hpot-banner-ico{flex:0 0 48px;height:48px;border-radius:12px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:22px}
.hpot-banner-txt{flex:1;min-width:0}
.hpot-banner-t{font-size:18px;font-weight:600;line-height:1.35}
.hpot-banner-p{font-size:14px;line-height:1.55;color:#374151;margin:6px 0 0;max-width:640px}
.hpot-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.hpot-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#374151;background:#f2f6f6;border:1px solid #e3ebea;border-radius:999px;padding:4px 12px}
.hpot-chip i,.hpot-chip svg{color:$accent}
.hpot-onoff{display:flex;flex-direction:column;align-items:flex-end;gap:10px;flex:0 0 auto}
.hpot-pill{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;border-radius:999px;padding:3px 12px}
.hpot-pill-on{background:#dcfce7;color:#166534}.hpot-pill-on .hpot-dot{background:#16a34a}
.hpot-pill-off{background:#f3f4f6;color:#4b5563}.hpot-pill-off .hpot-dot{background:#9ca3af}
.hpot-dot{display:inline-block;width:8px;height:8px;border-radius:50%}
.hpot-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.hpot-btn:hover{background:#003d33}
.hpot-btn-q{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:500;cursor:pointer}
.hpot-btn-q:hover{border-color:#dc2626;color:#b91c1c}
.hpot-btn-err{background:#dc2626}.hpot-btn-err:hover{background:#b91c1c}
.hpot-link{background:none;border:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;padding:4px 8px}
.hpot-link:hover{text-decoration:underline}
.hpot-btn:focus-visible,.hpot-btn-q:focus-visible,.hpot-link:focus-visible,.hpot-del:focus-visible,.hpot-ex:focus-visible{outline:2px solid $accent;outline-offset:2px}
.hpot-setup{padding:20px 24px}
.hpot-card-t{font-size:15px;font-weight:600;color:#111827}
.hpot-steps{list-style:none;margin:14px 0 16px;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.hpot-steps li{display:flex;gap:12px;align-items:flex-start}
.hpot-steps b{display:block;font-size:14px;color:#111827}
.hpot-steps span:not(.hpot-step-n){display:block;font-size:13px;color:#6b7280;margin-top:2px;line-height:1.45}
.hpot-step-n{flex:0 0 28px;height:28px;border-radius:50%;background:$soft;color:$accent;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:13px}
.hpot-sec-head{display:flex;align-items:center;gap:12px}
.hpot-sec-t{font-size:15px;font-weight:600}
.hpot-upd{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;background:#f3f4f6;border-radius:6px;padding:2px 8px}
.hpot-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.hpot-kpi{padding:16px 20px;min-height:104px}
.hpot-kpi-l{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#6b7280}
.hpot-kpi-l i,.hpot-kpi-l svg{color:$accent}
.hpot-kpi-row{display:flex;align-items:center;justify-content:space-between}
.hpot-kpi-v{font-size:28px;font-weight:600;line-height:34px;margin-top:4px;font-variant-numeric:tabular-nums}
.hpot-of{font-size:18px;color:#6b7280;font-weight:500}
.hpot-kpi-s{font-size:13px;color:#4b5563;margin-top:2px}
.hpot-quiet{display:flex;gap:14px;align-items:center;padding:18px 20px}
.hpot-quiet-ico{flex:0 0 40px;height:40px;border-radius:10px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:18px}
.hpot-tcard{overflow:hidden}
.hpot-thead{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:16px 20px 12px}
.hpot-count{display:inline-block;font-size:13px;font-weight:500;color:$accent;background:$soft;border-radius:999px;padding:1px 10px;margin-left:8px;vertical-align:1px}
.hpot-tbl{width:100%;border-collapse:collapse}
.hpot-tbl th{font-size:13px;font-weight:600;color:#374151;text-align:left;padding:10px 20px;background:#fafafa;border-top:1px solid #f1f2f4;border-bottom:1px solid #e5e7eb;white-space:nowrap}
.hpot-tbl td{font-size:13px;padding:10px 20px;border-bottom:1px solid #f1f2f4;vertical-align:middle;height:52px}
.hpot-tbl tr:last-child td{border-bottom:0}
.hpot-r{text-align:right!important}
.hpot-ip{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;background:#f3f4f6;border-radius:6px;padding:3px 8px}
.hpot-pchip{display:inline-block;font-size:13px;border:1px solid #e5e7eb;border-radius:6px;padding:2px 8px;margin:2px 4px 2px 0;color:#374151;white-space:nowrap}
.hpot-pchip b{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:$accent;margin-right:2px}
.hpot-kn{display:inline-block;min-width:56px;font-weight:600;font-variant-numeric:tabular-nums;margin-right:12px}
.hpot-bar{display:inline-block;vertical-align:middle;width:160px;height:6px;border-radius:999px;background:#eef0f2;overflow:hidden}
.hpot-bar span{display:block;height:100%;background:$accent;border-radius:999px}
.hpot-tfoot{text-align:center;padding:8px;border-top:1px solid #f1f2f4}
.hpot-port{display:flex;align-items:baseline;gap:10px}
.hpot-port b{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:15px;min-width:52px}
.hpot-port span{color:#374151}
.hpot-st{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;border-radius:999px;padding:3px 10px;white-space:nowrap}
.hpot-st-on{background:#dcfce7;color:#166534}
.hpot-st-off{background:#f3f4f6;color:#4b5563}
.hpot-st-warn{background:#fef3c7;color:#92400e}
.hpot-st-bad{background:#fee2e2;color:#991b1b}
.hpot-row-bad td{background:#fef2f2}
.hpot-dim .hpot-tbl td{color:#6b7280}
.hpot-del{background:none;border:0;color:#9ca3af;cursor:pointer;padding:6px;border-radius:6px;font-size:14px}
.hpot-del:hover{color:#dc2626;background:#fef2f2}
.hpot-empty{text-align:center;padding:32px 20px}
.hpot-empty i,.hpot-empty svg{font-size:26px;color:#9ca3af;display:block;margin:0 auto 8px}
.hpot-empty b{display:block;font-size:15px}
.hpot-card.hpot-state{display:flex;gap:16px;align-items:flex-start;padding:22px 24px}
.hpot-state-ico{flex:0 0 44px;height:44px;border-radius:12px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.hpot-card.hpot-state .hpot-muted{margin:4px 0 12px;font-size:14px;color:#374151;max-width:620px}
.hpot-state-err{background:#fef2f2;border-color:#fecaca}
.hpot-state-err .hpot-state-ico{background:#fee2e2;color:#dc2626}
.hpot-state-err .hpot-card-t{color:#991b1b}
.hpot-err{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#7f1d1d;margin:4px 0 12px;word-break:break-word}
.hpot-modal{gap:12px}
.hpot-picks{display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:13px;color:#6b7280}
.hpot-ex{font-size:13px;color:$accent;background:$soft;border:1px solid #bfd4d1;border-radius:6px;padding:3px 10px;cursor:pointer}
.hpot-ex:hover{background:#d6e5e3}
.hpot-warn{display:flex;gap:8px;align-items:flex-start;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:10px 12px;font-size:13px}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
@media(max-width:1200px){.hpot-banner{flex-wrap:wrap}.hpot-bar{width:90px}}
@media (prefers-reduced-motion:no-preference){.hpot-btn,.hpot-del{transition:background .15s,color .15s}}
</style>";
}
