<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["fortigate-params-popup"])){fortigate_params_popup();exit;}
if(isset($_GET["fortigate-params-js"])){fortigate_params_js();exit;}
if(isset($_POST["fortigate-uninstall"])){fortigate_uninstall_confirm();exit;}
if(isset($_GET["fortigate-uninstall-js"])){fortigate_uninstall_js();exit;}
if(isset($_GET["fortigate-rules-popup"])){fortigate_rules_popup();exit;}
if(isset($_GET["fortigate-rules-js"])){fortigate_rules_js();exit;}
if(isset($_GET["tooltip-js"])){tooltip_js();exit;}
if(isset($_GET["tooltip-start"])){tooltip_start();exit;}
if(isset($_GET["tooltip-step1"])){tooltip_step1();exit;}
if(isset($_GET["tooltip-step2"])){tooltip_step2();exit;}
if(isset($_GET["tooltip-step3"])){tooltip_step3();exit;}
if(isset($_GET["tooltip-step4"])){tooltip_step4();exit;}
if(isset($_POST["FortinetTransparentFromIface"])){tooltip_save();exit;}
if(isset($_POST["FortinetTransparentToIface"])){tooltip_save();exit;}
if(isset($_GET["fortigate-stats"])){fortigate_stats();exit;}
if(isset($_GET["start"])){start();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="Fortigate &raquo; {APP_SQUID}";
    $addon="";
    if(isset($_GET["fortiget"])){
        $addon="&fortiget=yes";
    }
    $html=$tpl->page_header($title,ico_dashboard,"{fortigate_use_tproxy}","$page?start=yes$addon",
        "fortiports",
        "progress-fortiports-restart",false,
        "table-fortiports"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Fortigate > {APP_SQUID}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function fortigate_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{parameters}","$page?fortigate-params-popup=yes",650);
}
function fortigate_params_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentFromIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentFromIface");
    $FortinetTransparentToIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentToIface");
    $f[]=$tpl->field_hidden("InstallIT","yes");
    $f[]=$tpl->field_interfaces("FortinetTransparentFromIface","{listen_interface}",$FortinetTransparentFromIface);
    $f[]=$tpl->field_interfaces("FortinetTransparentToIface","{outgoing_interface}",$FortinetTransparentToIface);
    echo $tpl->form_outside("",$f,"","{apply}","dialogInstance7.close();");
    return true;
}
function fortigate_rules_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{fortigate_rules}","$page?fortigate-rules-popup=yes",650);
}
function fortigate_uninstall_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{uninstall}","fortigate-uninstall","yes","document.location.href='/proxy-status'");
}
function fortigate_uninstall_confirm():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/tproxyforti/uninstall",array());
    return admin_tracks("Uninstall Fortigate integration...");
}
function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Hote unique du tableau de bord : bandeau, cartes et graphiques sont recharges ensemble
    // pour la periode choisie (?fortigate-stats=yes&period=…), car les KPI en dependent.
    $html=array();
    $html[]="<div id='fortigate-stats'></div>";


    // En mode WCCP, les regles FortiOS et les interfaces du mode transparent ne s'appliquent
    // pas : l'en-tete reprend celui du mode WCCP (fw.proxy.transparent.fortigate.php).
    $wccp=fgts_mode()=="wccp";
    $topbuttons=array();
    $topbuttons[] = array("TpRefresh()", ico_refresh, "{refresh}");
    if($wccp){
        $topbuttons[] = array("Loadjs('$page?fortigate-uninstall-js')", ico_trash, "{uninstall}");
        $title="{fgw_title_wccp}";
        $expl="{fgw_wccp_explain}";
    }else{
        $topbuttons[] = array("Loadjs('$page?fortigate-rules-js')", ico_support, "{fortigate_rules}");
        $topbuttons[] = array("Loadjs('$page?fortigate-params-js')", ico_params, "{parameters}");
        $topbuttons[] = array("Loadjs('$page?fortigate-uninstall-js')", ico_trash, "{uninstall}");
        $title="Fortigate &raquo; {APP_SQUID}";
        $expl="{fortigate_use_tproxy}";
    }
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_firewall;
    $TINY_ARRAY["EXPL"]=$expl;
    // La periode gouverne toute la page : elle va dans l'en-tete (siem_tiny_filters), pas dans
    // le corps. Les libelles disent la vraie fenetre de chaque seau du collecteur (Stats.go) :
    // echantillons 5 min gardes 48 h, totaux horaires 7 j, journaliers 30 j, hebdomadaires.
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"fgts-period","LABEL"=>"","VALUE"=>"current",
        "OPTIONS"=>fgts_periods(),"JS"=>"TpRange");
    $fields[]=array("TYPE"=>"note","LABEL"=>"{fgts_updated_5min}");
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    // Declare AVANT la TINY : le segment est cliquable des que l'en-tete arrive, avant le fragment.
    // Le fragment ne recharge que le corps : l'en-tete n'est pas regenere, on deplace donc
    // la marque "on" du segment ici.
    $html[]="window.FgtsPeriod='current';";
    $html[]="function TpRange(p){";
    $html[]="  window.FgtsPeriod=p;";
    $html[]="  \$('#fgts-period .siem-tinyf-s').each(function(){ \$(this).toggleClass('on',(\$(this).attr('onclick')||'').indexOf(\"'\"+p+\"'\")>-1); });";
    $html[]="  TpRefresh();";
    $html[]="}";
    // Bouton {refresh}, segment de periode et minuterie de 5 min passent tous par ici.
    $html[]="function TpRefresh(){ if(!document.getElementById('fortigate-stats')){ clearTimeout(window.FgtsTimer); return; } LoadAjaxSilent('fortigate-stats','$page?fortigate-stats=yes&period='+encodeURIComponent(window.FgtsPeriod||'current')); }";
    $html[]=$jstiny;
    if(isset($_GET["fortiget"])){
    $html[]="Loadjs('$page?fortigate-rules-js');";
    }
    $html[]="TpRefresh();";
    $html[]="</script>";

    echo siem_tiny_filters_style().$tpl->_ENGINE_parse_body($html);
    return true;
}
/** Seaux du collecteur (TProxyForti/Stats.go) : cle de l'API => libelle de la vraie fenetre. */
function fgts_periods():array{
    return array("current"=>"48 h","hourly"=>"7 d","daily"=>"30 d","weekly"=>"{fgts_weeks}");
}

function tooltip_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("Fortigate > {APP_SQUID}","$page?tooltip-start=yes",650);
}
function tooltip_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $f[]="<div id='fortidiv'></div>";
    $f[]="<script>LoadAjax('fortidiv','$page?tooltip-step1=yes')</script>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}
function tooltip_step1():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nextButton=$tpl->button_autnonome("{next}","LoadAjax('fortidiv','$page?tooltip-step2=yes')",ico_wizard,
        "AsSquidAdministrator",400,"btn-primary");

    $f[]="<table style='width:100%;margin-top:-35px'>";
    $f[]="<tr>";
    $f[]="<td style='width:64px' nowrap><img src='img/Fortinet-logomark-rgb-red.png' style='margin:5px;width:100%'></td>";
    $f[]="<td style='width:70%;text-align:left'>";
    $f[]="<p class='font-size:18px'>{fortigate_use_tproxy2}</p>";
    $f[]="</td>";
    $f[]="</tr>";
    $f[]="</table>";

    $f[]="<table style='width:100%;margin-top:10px'>";
    $f[]="<tr>";
    $f[]="<td style='width:50%'></td>";
    $f[]="<td style='width:50%;text-align:right'>$nextButton</td>";
    $f[]="</tr>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function tooltip_step2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentFromIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentFromIface");
    $f[]=$tpl->field_interfaces("FortinetTransparentFromIface","{listen_interface}",$FortinetTransparentFromIface);
    $f[]=$tpl->form_add_button("{previous}","LoadAjax('fortidiv','$page?tooltip-step2=yes')");
    $html= $tpl->form_outside("",$f,"{fortigate_use_tproxy3}","{next}","LoadAjax('fortidiv','$page?tooltip-step3=yes')");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tooltip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $InstallIT=false;
    if(isset($_POST["InstallIT"])){
        $InstallIT=true;
        unset($_POST["InstallIT"]);
    }
    $tpl->SAVE_POSTs();
    if($InstallIT){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/install");
        return admin_tracks_post("Fortigate params...");
    }
    return admin_tracks_post("Fortigate wizard...");
}
function tooltip_step3():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentToIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentToIface");
    $f[]=$tpl->field_interfaces("FortinetTransparentToIface","{outgoing_interface}",$FortinetTransparentToIface);
    $f[]=$tpl->form_add_button("{previous}","LoadAjax('fortidiv','$page?tooltip-step3=yes')");
    $html= $tpl->form_outside("",$f,"{fortigate_use_tproxy4}","{next}","LoadAjax('fortidiv','$page?tooltip-step4=yes')");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tooltip_step4():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/install");
    $f[]="<script>";
    $f[]="document.location.href='/fortiports'";
    $f[]="</script>";
    echo implode("\n",$f);
    return true;
}

function fortigate_rules_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentFromIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentFromIface");
    $ipclass=new system_nic($FortinetTransparentFromIface);
    $Ipaddr=$ipclass->IPADDR;
    $html[]="<div class='chatCode'>";
    $html[]="<div class='chatCode-header'>";
    $html[]="<span class='chatCode-lang'>fortigate console</span>";
    $html[]="<button class='chatCode-copy' onclick='...'>&nbsp;</button>";
    $html[]="</div>";
    $html[]="<pre># {fortigate_subtitle0}</pre>";
    $html[]="<pre>config system settings</pre>";
    $html[]="<pre>    set asymroute enable</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle1}</pre>";
    $html[]="<pre>config firewall address</pre>";
    $html[]="<pre>\tedit \"client1\"</pre>";
    $html[]="<pre>\t\tset subnet 192.168.1.190 255.255.255.255</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tedit \"client2\"</pre>";
    $html[]="<pre>\t\tset subnet 192.168.1.191 255.255.255.255</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tedit \"ArticaProxy\"</pre>";
    $html[]="<pre>\t\tset subnet $Ipaddr 255.255.255.255</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tedit \"LAN-192\"</pre>";
    $html[]="<pre>\t\tset subnet 192.168.0.0 255.255.0.0</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle2}</pre>";
    $html[]="<pre>config firewall addrgrp</pre>";
    $html[]="<pre>\tedit \"proxy-clients\"</pre>";
    $html[]="<pre>\t\tset member \"client1\" \"client2\"</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle3}</pre>";
    $html[]="<pre>config router policy</pre>";
    $html[]="<pre># {fortigate_subtitle5}</pre>";
    $html[]="<pre>    edit 1</pre>";
    $html[]="<pre>        set input-device \"internal\"</pre>";
    $html[]="<pre>        set dstaddr \"LAN-192\"</pre>";
    $html[]="<pre>        set action stop-policy-routing (or deny for old versions)</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    edit 2</pre>";
    $html[]="<pre>        set input-device \"internal\"</pre>";
    $html[]="<pre>        set srcaddr \"ArticaProxy\"</pre>";
    $html[]="<pre>        set gateway 10.10.1.254</pre>";
    $html[]="<pre>        set output-device \"wan1\"</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    edit 3</pre>";
    $html[]="<pre>        set input-device internal</pre>";
    $html[]="<pre>        set srcaddr \"proxy-clients\"</pre>";
    $html[]="<pre>        set protocol 6</pre>";
    $html[]="<pre>        set start-port 80</pre>";
    $html[]="<pre>        set end-port 80</pre>";
    $html[]="<pre>        set gateway $Ipaddr</pre>";
    $html[]="<pre>        set output-device internal</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    edit 4</pre>";
    $html[]="<pre>        set input-device internal</pre>";
    $html[]="<pre>        set srcaddr \"proxy-clients\"</pre>";
    $html[]="<pre>        set protocol 6</pre>";
    $html[]="<pre>        set start-port 443</pre>";
    $html[]="<pre>        set end-port 443</pre>";
    $html[]="<pre>        set gateway $Ipaddr</pre>";
    $html[]="<pre>        set output-device internal</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle4}</pre>";
    $html[]="<pre>config firewall policy</pre>";
    $html[]="<pre>    edit 15</pre>";
    $html[]="<pre>        set name \"Transparent_proxy_hairpin\"</pre>";
    $html[]="<pre>        set srcintf internal</pre>";
    $html[]="<pre>        set dstintf internal</pre>";
    $html[]="<pre>        set srcaddr \"proxy-clients\"</pre>";
    $html[]="<pre>        set dstaddr all</pre>";
    $html[]="<pre>        set action accept</pre>";
    $html[]="<pre>        set schedule always</pre>";
    $html[]="<pre>        set service HTTP HTTPS</pre>";
    $html[]="<pre>        set comments \"Transparent-proxy-hairpin\"</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    move 15 before 1";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

/** Mode actif de l'integration (transparent|wccp) ; route illisible = transparent (en-tete historique). */
function fgts_mode():string{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/status"));
    if(is_object($json) && !empty($json->success) && isset($json->data) && is_object($json->data)){
        if(strval($json->data->mode ?? "")=="wccp"){ return "wccp"; }
    }
    return "transparent";
}

// ---- FGTS : tableau de bord des statistiques (modele + widgets) ----

/** Periode demandee, en liste blanche (cles de l'API /tproxyforti/stats). */
function fgts_period_get():string{
    $p=isset($_GET["period"])?(string)$_GET["period"]:"current";
    return in_array($p,array("current","hourly","daily","weekly"),true)?$p:"current";
}

/** Pas d'un echantillon de chaque seau, en secondes (TProxyForti/Stats.go). */
function fgts_step(string $period):int{
    $m=array("current"=>300,"hourly"=>3600,"daily"=>86400,"weekly"=>604800);
    return isset($m[$period])?$m[$period]:300;
}

/** Lit une serie. $ok=false si la route est illisible (≠ serie vide). Echantillons tries par date. */
function fgts_fetch_samples(string $period,&$ok):array{
    $ok=false;
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/stats?period=".urlencode($period)),true);
    if(!is_array($json) || empty($json["success"])){ return array(); }
    $ok=true;
    $raw=array();
    if(isset($json["data"]) && is_array($json["data"]) && isset($json["data"]["samples"]) && is_array($json["data"]["samples"])){
        $raw=$json["data"]["samples"];
    }
    $out=array();
    foreach($raw as $s){
        if(!is_array($s)){ continue; }
        $ts=isset($s["timestamp"])?intval($s["timestamp"]):0;
        if($ts<=0){ continue; }
        $out[]=array("ts"=>$ts,
            "http_b"=>floatval(isset($s["http_bytes"])?$s["http_bytes"]:0),
            "https_b"=>floatval(isset($s["https_bytes"])?$s["https_bytes"]:0),
            "http_p"=>floatval(isset($s["http_pkts"])?$s["http_pkts"]:0),
            "https_p"=>floatval(isset($s["https_pkts"])?$s["https_pkts"]:0));
    }
    usort($out,function($a,$b){ return $a["ts"]-$b["ts"]; });
    return $out;
}

/** Modele unique : etat de l'integration, serie de la periode, fraicheur (toujours sur "current"). */
function fgts_model(string $period):array{
    $m=array("period"=>$period,"step"=>fgts_step($period),"status_ok"=>false,"enabled"=>false,
        "mode"=>"transparent","iface"=>"","source_ip"=>"","router"=>"","collector"=>false,
        "stats_ok"=>false,"samples"=>array(),"fresh_ok"=>false,"last_ts"=>0,
        "https_b"=>0.0,"http_b"=>0.0,"https_p"=>0.0,"http_p"=>0.0,"peak_b"=>0.0,"peak_ts"=>0,"covered"=>0);

    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/status"),true);
    if(is_array($st) && !empty($st["success"]) && isset($st["data"]) && is_array($st["data"])){
        $d=$st["data"];
        $m["status_ok"]=true;
        $m["enabled"]=!empty($d["enabled"]);
        $m["mode"]=(isset($d["mode"]) && $d["mode"]=="wccp")?"wccp":"transparent";
        $m["iface"]=isset($d["interface"])?trim((string)$d["interface"]):"";
        $m["source_ip"]=isset($d["source_ip"])?trim((string)$d["source_ip"]):"";
        $m["router"]=isset($d["router"])?trim((string)$d["router"]):"";
        $m["collector"]=!empty($d["collector_running"]);
    }
    if(!$m["status_ok"] || !$m["enabled"]){ return $m; }

    $ok=false;
    $m["samples"]=fgts_fetch_samples($period,$ok);
    $m["stats_ok"]=$ok;

    // Fraicheur : dernier echantillon de 5 min, quelle que soit la periode affichee
    // (sinon une vue 30 j dirait "il y a 1 jour").
    $cur=$m["samples"]; $cok=$ok;
    if($period!="current"){ $cur=fgts_fetch_samples("current",$cok); }
    $m["fresh_ok"]=$cok;
    foreach($cur as $s){ if($s["ts"]>$m["last_ts"]){ $m["last_ts"]=$s["ts"]; } }

    foreach($m["samples"] as $s){
        $m["https_b"]+=$s["https_b"]; $m["http_b"]+=$s["http_b"];
        $m["https_p"]+=$s["https_p"]; $m["http_p"]+=$s["http_p"];
        $t=$s["https_b"]+$s["http_b"];
        if($m["peak_ts"]==0 || $t>$m["peak_b"]){ $m["peak_b"]=$t; $m["peak_ts"]=$s["ts"]; }
    }
    // Duree reellement mesuree : un echantillon couvre un pas, un trou ne compte pas.
    $m["covered"]=count($m["samples"])*$m["step"];
    return $m;
}

/** Octets en unites humaines, base 1024 (meme regle que fgtsB() cote JS). */
function fgts_bytes($n):string{
    $n=floatval($n);
    $u=array("B","KB","MB","GB","TB");
    $i=0;
    while($n>=1024 && $i<count($u)-1){ $n=$n/1024; $i++; }
    if($i==0){ return number_format($n,0)." B"; }
    return number_format($n,$n>=100?0:($n>=10?1:2))." ".$u[$i];
}
/** Paquets en k / M (meme regle que fgtsP() cote JS). */
function fgts_pkts($n):string{
    $n=floatval($n);
    if($n>=1000000){ return number_format($n/1000000,1)." M"; }
    if($n>=1000){ return number_format($n/1000,1)." k"; }
    return number_format($n,0);
}
/** Age lisible : min sous l'heure, h sous deux jours, j au-dela. */
function fgts_age(int $sec):string{
    $tpl=new template_admin();
    if($sec<3600){ return str_replace("%s",strval(max(1,intval(floor($sec/60)))),$tpl->_ENGINE_parse_body("{fgts_age_min}")); }
    if($sec<172800){ return str_replace("%s",strval(intval(floor($sec/3600))),$tpl->_ENGINE_parse_body("{fgts_age_h}")); }
    return str_replace("%s",strval(intval(floor($sec/86400))),$tpl->_ENGINE_parse_body("{fgts_age_d}"));
}
/** Jeton traduit puis valeur substituee (traduire d'abord, sinon le jeton ne correspond plus). */
function fgts_t(string $token,string $value):string{
    $tpl=new template_admin();
    return str_replace("%s",$value,$tpl->_ENGINE_parse_body("{".$token."}"));
}
function fgts_res_token(string $period):string{
    $m=array("current"=>"{fgts_res_current}","hourly"=>"{fgts_res_hourly}","daily"=>"{fgts_res_daily}","weekly"=>"{fgts_res_weekly}");
    return $m[$period];
}

/** Fragment ?fortigate-stats=yes&period=… : tout le contenu de l'onglet pour une periode. */
function fortigate_stats(){
    $tpl=new template_admin();
    $m=fgts_model(fgts_period_get());
    $h=array();
    $h[]="<div class='ak fgts'>";
    if(!$m["status_ok"]){
        $h[]=fgts_unavailable();
    }elseif(!$m["enabled"]){
        $h[]=fgts_empty();
    }else{
        $h[]=fgts_context($m);
        if(!$m["stats_ok"]){
            $h[]=fgts_unavailable();
        }else{
            $h[]=fgts_kpis($m);
            $h[]=fgts_traffic_chart($m);
            $h[]="<div class='fgts-row'>".fgts_packets_chart($m).fgts_split($m)."</div>";
            $h[]=fgts_footer($m);
        }
    }
    $h[]="</div>";
    $js="<script>NoSpinner();</script>";
    if($m["status_ok"] && $m["enabled"] && $m["stats_ok"]){ $js=fgts_js($m); }
    else{ $js.=fgts_timer_js(); }
    echo fgts_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).$js;
}

function fgts_unavailable():string{
    return "<div class='ak-banner ak-off fgts-unav' role='status'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>{fgts_unavailable}</span></div>";
}
function fgts_empty():string{
    return "<div class='ak-card'><div class='ak-empty-c fgts-empty'><i class='fas fa-plug-circle-xmark'></i><span>{fgts_not_installed}</span></div></div>";
}

/** Bandeau de contexte (lecture seule) + fraicheur du collecteur. */
function fgts_context(array $m):string{
    $h=array();
    $h[]="<div class='ak-card fgts-ctx'>";
    $h[]="<div class='fgts-ctx-l'>";
    if($m["mode"]=="wccp"){
        $pill="<span class='ak-pill ak-off fgts-mode'><i class='fas fa-tower-broadcast'></i> WCCP</span>";
    }else{
        $pill="<span class='ak-pill ak-off fgts-mode'><i class='fas fa-route'></i> {fgts_transparent}</span>";
    }
    $h[]="<span class='fgts-kv'><span class='fgts-k'>{fgts_mode}</span>$pill</span>";
    $iface=$m["iface"]==""?"—":htmlspecialchars($m["iface"]);
    $h[]="<span class='fgts-kv'><span class='fgts-k'>{fgts_interface}</span><span class='fgts-v ak-mono'>$iface</span></span>";
    $src=$m["source_ip"]==""?"—":htmlspecialchars($m["source_ip"]);
    $h[]="<span class='fgts-kv'><span class='fgts-k'>{fgts_proxy_address}</span><span class='fgts-v ak-mono'>$src</span></span>";
    if($m["mode"]=="wccp"){
        $rt=$m["router"]==""?"—":htmlspecialchars($m["router"]);
        $h[]="<span class='fgts-kv fgts-kv-router'><span class='fgts-k'>FortiGate</span><span class='fgts-v ak-mono'>$rt</span></span>";
    }
    $h[]="</div>";
    $h[]=fgts_freshness($m);
    $h[]="</div>";
    return implode("",$h);
}

/** Fraicheur : collecteur arrete (rouge) > aucun echantillon (gris) > age du dernier (ok / ambre). */
function fgts_freshness(array $m):string{
    if(!$m["collector"]){
        return "<span class='ak-pill ak-err fgts-fresh' data-state='stopped'><i class='fas fa-circle-xmark'></i> {fgts_collector_stopped}</span>";
    }
    if(!$m["fresh_ok"]){
        return "<span class='ak-pill ak-off fgts-fresh' data-state='unknown'><i class='fas fa-circle-question'></i> {fgts_fresh_unknown}</span>";
    }
    if($m["last_ts"]==0){
        return "<span class='ak-pill ak-off fgts-fresh' data-state='none'><i class='fas fa-hourglass-half'></i> {fgts_no_sample_yet}</span>";
    }
    $age=max(0,time()-$m["last_ts"]);
    if($age<600){
        return "<span class='ak-pill ak-ok fgts-fresh' data-state='ok'><i class='fas fa-clock'></i> ".fgts_t("fgts_last_sample",fgts_age($age))."</span>";
    }
    return "<span class='ak-pill ak-warn fgts-fresh' data-state='idle'><i class='fas fa-triangle-exclamation'></i> ".fgts_t("fgts_collector_idle",fgts_age($age))."</span>";
}

/** Quatre cartes calculees sur la periode choisie ; aucune donnee => "—", jamais 0. */
function fgts_kpis(array $m):string{
    $has=count($m["samples"])>0;
    $total=$m["https_b"]+$m["http_b"];
    $dash="—";
    $h=array();
    $h[]="<div class='ak-grid-4 fgts-kpis'>";

    $v=$has?fgts_bytes($m["https_b"]):$dash;
    $s=$has?fgts_t("fgts_n_packets",fgts_pkts($m["https_p"])):$dash;
    $h[]=fgts_fig("https","{fgts_https_traffic}",$v,$s,"fas fa-lock","fgts-ico-https");

    $v=$has?fgts_bytes($m["http_b"]):$dash;
    $s=$has?fgts_t("fgts_n_packets",fgts_pkts($m["http_p"])):$dash;
    $h[]=fgts_fig("http","{fgts_http_traffic}",$v,$s,"fas fa-globe","ak-info");

    if($has && $total>0){
        $pct=number_format($m["https_b"]*100/$total,1)." %";
        $pie="<span class='fgts-pie' data-peity='".htmlspecialchars(json_encode(array("fill"=>array("#0F766E","#93C5FD"))))."'>".sprintf("%.0f/%.0f",$m["https_b"],$total)."</span>";
        $s="<span class='fgts-share-s'>$pie<span>{fgts_of_total_bytes}</span></span>";
    }else{
        $pct=$dash; $s="{fgts_of_total_bytes}";
    }
    $h[]=fgts_fig("share","{fgts_https_share}",$pct,$s,"fas fa-chart-pie","fgts-ico-https");

    if($has){
        $fmt=($m["period"]=="current"||$m["period"]=="hourly")?"Y-m-d H:i":"Y-m-d";
        $v=fgts_bytes($m["peak_b"])."<span class='fgts-peak-d'>".date($fmt,$m["peak_ts"])."</span>";
        if($m["period"]=="current" && $m["covered"]>0){
            $kbps=$total*8/$m["covered"]/1000;
            $s=fgts_t("fgts_kbits_range",number_format($kbps,1));
        }else{
            $s=fgts_tt_token($m["period"]);
        }
    }else{
        $v=$dash; $s=$dash;
    }
    $h[]=fgts_fig("peak","{fgts_peak_interval}",$v,$s,"fas fa-arrow-trend-up","fgts-ico-https");
    $h[]="</div>";
    return implode("",$h);
}
function fgts_fig(string $id,string $label,string $value,string $sub,string $ico,string $tone):string{
    return "<div class='ak-fig fgts-fig' data-kpi='$id'><div class='ak-fig-txt'><span class='ak-fig-l'>$label</span>"
        ."<span class='ak-fig-v'>$value</span><span class='ak-fig-s'>$sub</span></div>"
        ."<span class='ak-ico ak-ico-sq $tone'><i class='$ico'></i></span></div>";
}
function fgts_tt_token(string $period):string{
    $m=array("current"=>"{fgts_tt_current}","hourly"=>"{fgts_tt_hourly}","daily"=>"{fgts_tt_daily}","weekly"=>"{fgts_tt_weekly}");
    return $m[$period];
}
/** Etat "pas encore d'echantillon" partage par les graphiques. */
function fgts_waiting():string{
    return "<div class='ak-empty-c fgts-wait'><i class='fas fa-hourglass-half'></i><span>{fgts_waiting}</span></div>";
}

function fgts_traffic_chart(array $m):string{
    $has=count($m["samples"])>0;
    $h=array();
    $h[]="<div class='ak-card ak-card-pad fgts-card'>";
    $h[]="<div class='fgts-h'><div><h3 class='fgts-t'><i class='fas fa-chart-area'></i> {fgts_traffic_title}</h3>";
    $h[]="<div class='fgts-s'>{fgts_traffic_sub} · ".fgts_res_token($m["period"])."</div></div>";
    if($has){
        $h[]="<div class='fgts-lg'>";
        $h[]="<span class='fgts-lg-i'><span class='fgts-sq' style='background:#0F766E'></span>HTTPS<span class='fgts-lg-n'>".fgts_bytes($m["https_b"])."</span></span>";
        $h[]="<span class='fgts-lg-i'><span class='fgts-sq' style='background:#3B82F6'></span>HTTP<span class='fgts-lg-n'>".fgts_bytes($m["http_b"])."</span></span>";
        $h[]="</div>";
    }
    $h[]="</div>";
    $h[]=$has?"<div class='fgts-cv' style='height:280px'><canvas id='fgts-c-traffic' aria-label='{fgts_traffic_title}' role='img'></canvas></div>":fgts_waiting();
    $h[]="</div>";
    return implode("",$h);
}

function fgts_packets_chart(array $m):string{
    $has=count($m["samples"])>0;
    $h=array();
    $h[]="<div class='ak-card ak-card-pad fgts-card'>";
    $h[]="<div class='fgts-h'><div><h3 class='fgts-t'><i class='fas fa-chart-column'></i> {fgts_packets_title}</h3>";
    $h[]="<div class='fgts-s'>{fgts_packets_sub} · ".fgts_res_token($m["period"])."</div></div>";
    if($has){
        $h[]="<div class='fgts-lg'>";
        $h[]="<span class='fgts-lg-i'><span class='fgts-dot' style='background:#0F766E'></span>HTTPS</span>";
        $h[]="<span class='fgts-lg-i'><span class='fgts-dot' style='background:#3B82F6'></span>HTTP</span>";
        $h[]="</div>";
    }
    $h[]="</div>";
    $h[]=$has?"<div class='fgts-cv' style='height:268px'><canvas id='fgts-c-packets' aria-label='{fgts_packets_title}' role='img'></canvas></div>":fgts_waiting();
    $h[]="</div>";
    return implode("",$h);
}

function fgts_split(array $m):string{
    $has=count($m["samples"])>0;
    $total=$m["https_b"]+$m["http_b"];
    $over=array("current"=>"{fgts_over_48h}","hourly"=>"{fgts_over_7d}","daily"=>"{fgts_over_30d}","weekly"=>"{fgts_over_weeks}");
    $h=array();
    $h[]="<div class='ak-card ak-card-pad fgts-card'>";
    $h[]="<div class='fgts-h'><div><h3 class='fgts-t'><i class='fas fa-chart-pie'></i> {fgts_split_title}</h3>";
    $h[]="<div class='fgts-s'>{fgts_split_sub}</div></div></div>";
    if(!$has){
        $h[]=fgts_waiting();
        $h[]="</div>";
        return implode("",$h);
    }
    $h[]="<div class='fgts-donut'><canvas id='fgts-c-split' aria-label='{fgts_split_title}' role='img'></canvas>";
    $h[]="<div class='fgts-donut-c'><span class='fgts-donut-v'>".fgts_bytes($total)."</span><span class='fgts-donut-s'>".$over[$m["period"]]."</span></div></div>";
    $ps=$total>0?number_format($m["https_b"]*100/$total,1)." %":"—";
    $pp=$total>0?number_format($m["http_b"]*100/$total,1)." %":"—";
    $h[]="<div class='fgts-sl'>";
    $h[]="<div class='fgts-sl-r'><span class='fgts-dot' style='background:#0F766E'></span><span>HTTPS</span><span class='fgts-num fgts-pct'>$ps</span><span class='fgts-num ak-muted'>".fgts_bytes($m["https_b"])."</span></div>";
    $h[]="<div class='fgts-sl-r'><span class='fgts-dot' style='background:#3B82F6'></span><span>HTTP</span><span class='fgts-num fgts-pct'>$pp</span><span class='fgts-num ak-muted'>".fgts_bytes($m["http_b"])."</span></div>";
    $h[]="</div></div>";
    return implode("",$h);
}

function fgts_footer(array $m):string{
    $iface=$m["iface"]==""?"—":"<span class='ak-mono'>".htmlspecialchars($m["iface"])."</span>";
    return "<p class='fgts-foot'><i class='fas fa-circle-info'></i><span>".fgts_t("fgts_footer",$iface)."</span></p>";
}

/** CSS propre a la page : seulement ce que le kit ak- n'a pas. Hors _ENGINE_parse_body. */
function fgts_style():string{
    if(isset($GLOBALS["FGTS_STYLE"])){ return ""; }
    $GLOBALS["FGTS_STYLE"]=true;
    $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){ $soft=$a["accent_soft"]; }
    }
    return "<style>
.fgts{display:flex;flex-direction:column;gap:16px;margin-top:10px}
.fgts-ctx{display:flex;align-items:center;justify-content:space-between;gap:12px 20px;flex-wrap:wrap;padding:14px 20px}
.fgts-ctx-l{display:flex;flex-wrap:wrap;align-items:center;gap:10px 28px;min-width:0}
.fgts-kv{display:inline-flex;align-items:center;gap:8px;min-width:0}
.fgts-k{font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.fgts-v{font-size:14px;font-weight:600;color:#111d23;overflow-wrap:anywhere}
.fgts-mode{cursor:default}
.fgts-fresh{cursor:default}
.fgts-fig .ak-ico.fgts-ico-https{background:$soft;color:#0F766E}
.fgts-fig .ak-fig-v{font-size:24px;white-space:nowrap}
.fgts-peak-d{display:block;font-size:13px;font-weight:600;color:#3d4947;margin-top:2px;white-space:nowrap}
.fgts-share-s{display:inline-flex;align-items:center;gap:8px}
.fgts-card{min-width:0}
.fgts-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px 16px;flex-wrap:wrap;margin-bottom:14px}
.fgts-t{font-size:16px;font-weight:600;color:#111d23;display:flex;align-items:center;gap:8px;margin:0}
.fgts-t svg,.fgts-t i{color:#0F766E}
.fgts-s{font-size:13px;color:#64748b;margin-top:4px}
.fgts-lg{display:flex;gap:10px;flex-wrap:wrap}
.fgts-lg-i{display:inline-flex;align-items:center;gap:8px;border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;font-size:13px;color:#111d23}
.fgts-sq{width:10px;height:10px;border-radius:3px;display:inline-block;flex:0 0 auto}
.fgts-dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex:0 0 auto}
.fgts-lg-n{font-weight:600;font-variant-numeric:tabular-nums;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:1px 6px}
.fgts-cv{position:relative;width:100%}
.fgts-row{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:16px}
@media (max-width:1200px){.fgts-row{grid-template-columns:minmax(0,1fr)}}
.fgts-donut{position:relative;height:200px;max-width:220px;margin:4px auto 0}
.fgts-donut-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;text-align:center}
.fgts-donut-v{font-size:22px;font-weight:700;color:#111d23;font-variant-numeric:tabular-nums}
.fgts-donut-s{font-size:13px;color:#64748b}
.fgts-sl{border-top:1px solid #f1f5f9;margin-top:14px;padding-top:10px}
.fgts-sl-r{display:grid;grid-template-columns:auto minmax(0,1fr) auto auto;gap:10px;align-items:center;font-size:13px;padding:4px 0;color:#111d23}
.fgts-num{font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap}
.fgts-pct{font-weight:600}
.fgts-foot{display:flex;gap:8px;align-items:flex-start;font-size:13px;color:#64748b;margin:0}
.fgts-foot>svg,.fgts-foot>i{margin-top:3px;flex:0 0 auto}
</style>";
}

/** Minuterie unique : recharge l'onglet toutes les 5 min (cadence du collecteur), pas pendant une modale. */
function fgts_timer_js():string{
    return "<script>
clearTimeout(window.FgtsTimer);
window.FgtsTimer=setTimeout(function fgtsTick(){
 if(!document.getElementById('fortigate-stats')){return;}
 if(document.querySelector('.modal.in,.modal.show,.bootstrap-dialog.in,.sweet-alert.visible,.swal2-container')){window.FgtsTimer=setTimeout(fgtsTick,30000);return;}
 if(typeof TpRefresh==='function'){TpRefresh();}
},300000);
</script>";
}

/** Donnees des graphiques (trous = null, jamais un zero invente) + Chart.js / peity charges a la demande. */
function fgts_js(array $m):string{
    $tpl=new template_admin();
    $step=$m["step"];
    $long=($m["period"]=="current"||$m["period"]=="hourly");
    $axisFmt=$long?"m-d H:i":"m-d";
    $ttFmt=$long?"Y-m-d H:i":"Y-m-d";
    $labels=array(); $titles=array(); $hs=array(); $hh=array(); $ps=array(); $ph=array();
    $prev=0;
    foreach($m["samples"] as $s){
        if($prev>0 && ($s["ts"]-$prev)>2*$step){
            // Trou de collecte : des points vides coupent la courbe (spanGaps:false) et occupent
            // la largeur du temps manquant, jamais un zero invente.
            $miss=min(1000,max(1,intval(floor(($s["ts"]-$prev)/$step))-1));
            for($k=1;$k<=$miss;$k++){
                $labels[]=date($axisFmt,$prev+$k*$step); $titles[]="";
                $hs[]=null; $hh[]=null; $ps[]=null; $ph[]=null;
            }
        }
        $labels[]=date($axisFmt,$s["ts"]);
        $titles[]=date($ttFmt,$s["ts"]);
        $hs[]=$s["https_b"]; $hh[]=$s["http_b"]; $ps[]=$s["https_p"]; $ph[]=$s["http_p"];
        $prev=$s["ts"];
    }
    $total=$m["https_b"]+$m["http_b"];
    $D=array("labels"=>$labels,"titles"=>$titles,"https"=>$hs,"http"=>$hh,"https_p"=>$ps,"http_p"=>$ph,
        "tot_https"=>$m["https_b"],"tot_http"=>$m["http_b"],"total"=>$total,
        "radius"=>count($labels)>60?0:2,
        "t_total"=>$tpl->javascript_parse_text("{fgts_total}"),
        "t_pk"=>$tpl->javascript_parse_text("{fgts_n_packets}"),
        "t_int"=>$tpl->javascript_parse_text(fgts_tt_token($m["period"])));
    $json=json_encode($D);
    return "<script>
NoSpinner();
(function(){
var D=$json;
window.FgtsCharts=window.FgtsCharts||[];
while(window.FgtsCharts.length){try{window.FgtsCharts.pop().destroy();}catch(e){}}
function fB(v){if(v===null||v===undefined){return '—';}var u=['B','KB','MB','GB','TB'],i=0;v=Number(v);while(v>=1024&&i<u.length-1){v=v/1024;i++;}if(i===0){return Math.round(v)+' B';}return v.toFixed(v>=100?0:(v>=10?1:2))+' '+u[i];}
function fP(v){v=Number(v);if(v>=1000000){return (v/1000000).toFixed(1)+' M';}if(v>=1000){return (v/1000).toFixed(1)+' k';}return String(Math.round(v));}
function trim(s){return s.replace(/\.(\d*?)0+(?= )/,function(m,d){return d?'.'+d:'';});}
function nice(bin){return {afterDataLimits:function(sc){var mx=sc.max;if(!(mx>0)){return;}var b=bin?1024:1000,u=1;while(mx/u>=b&&u<Math.pow(b,4)){u*=b;}var n=[1,2,5,10,20,50,100,200,500,1000],st=u;for(var i=0;i<n.length;i++){st=n[i]*u;if(mx/st<=6){break;}}sc.max=Math.ceil(mx/st)*st;sc.fgtsStep=st;},
 afterBuildTicks:function(sc){if(!sc.fgtsStep){return;}var t=[];for(var v=0;v<=sc.max+sc.fgtsStep/1000;v+=sc.fgtsStep){t.push({value:v});}sc.ticks=t;}};}
function base(fmt,cb,axis,bin,ticks){var y=nice(bin);y.bounds='data';y.beginAtZero=true;y.grid={color:'#f1f5f9'};y.ticks={color:'#64748b',font:{size:13},callback:function(v){return trim(axis(v));}};
 return {responsive:true,maintainAspectRatio:false,animation:false,interaction:{mode:'index',intersect:false},
 plugins:{legend:{display:false},tooltip:{filter:function(i){return i.parsed.y!==null;},callbacks:{
  title:function(it){return it.length?D.titles[it[0].dataIndex]:'';},
  label:function(c){return ' '+c.dataset.label+': '+fmt(c.parsed.y);},
  footer:cb}}},
 scales:{x:{grid:{display:false},ticks:{autoSkip:true,autoSkipPadding:16,maxTicksLimit:ticks,maxRotation:0,color:'#64748b',font:{size:13}}},
  y:y}};}
function ds(label,data,color,fill){return {label:label,data:data,borderColor:color,backgroundColor:fill||'transparent',fill:!!fill,spanGaps:false,tension:0.25,borderWidth:2,pointRadius:D.radius,pointHoverRadius:4};}
function draw(){
 var a=document.getElementById('fgts-c-traffic');
 if(a){window.FgtsCharts.push(new Chart(a,{type:'line',data:{labels:D.labels,datasets:[
   ds('HTTPS',D.https,'#0F766E','rgba(15,118,110,.15)'),ds('HTTP',D.http,'#3B82F6','rgba(59,130,246,.12)')]},
   options:base(fB,function(it){if(!it.length){return '';}var i=it[0].dataIndex;if(D.https[i]===null){return '';}return D.t_total+': '+fB(Number(D.https[i])+Number(D.http[i]))+'\\n'+D.t_int;},fB,true,12)}));}
 var b=document.getElementById('fgts-c-packets');
 if(b){window.FgtsCharts.push(new Chart(b,{type:'line',data:{labels:D.labels,datasets:[
   ds('HTTPS',D.https_p,'#0F766E'),ds('HTTP',D.http_p,'#3B82F6')]},
   options:base(function(v){return D.t_pk.replace('%s',fP(v));},function(it){return it.length&&D.https_p[it[0].dataIndex]!==null?D.t_int:'';},fP,false,7)}));}
 var c=document.getElementById('fgts-c-split');
 if(c){var on=D.total>0;window.FgtsCharts.push(new Chart(c,{type:'doughnut',data:{labels:['HTTPS','HTTP'],datasets:[{
   data:on?[D.tot_https,D.tot_http]:[1],backgroundColor:on?['#0F766E','#3B82F6']:['#e2e8f0'],borderWidth:0}]},
   options:{responsive:true,maintainAspectRatio:false,cutout:'74%',animation:false,plugins:{legend:{display:false},
   tooltip:{enabled:on,callbacks:{label:function(x){return ' '+x.label+': '+fB(x.raw)+' ('+(x.raw*100/D.total).toFixed(1)+' %)';}}}}}}));}
}
function ensure(cb){if(typeof Chart!=='undefined'){cb();return;}var s=document.createElement('script');s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';s.onload=cb;document.head.appendChild(s);}
function pies(){jQuery('#fortigate-stats .fgts-pie').peity('donut',{radius:14,innerRadius:9});}
if(D.labels.length){ensure(draw);}
if(typeof jQuery!=='undefined' && jQuery('#fortigate-stats .fgts-pie').length){
 if(jQuery.fn.peity){pies();}else{var p=document.createElement('script');p.src='js/jquery.peity.min.js';p.onload=pies;document.head.appendChild(p);}
}
})();
</script>".fgts_timer_js();
}
// ---- FGTS fin ----
