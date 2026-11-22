<?php
// fw.nginx.DecisionIP.php — DecisionIP home: registration, protection counters and the lists
// the firewall applies.
//
// Stitch redesign 2026-11-21 (stitch_decisionip_firewall_console_redesign). REAL DATA ONLY:
//   GET /decisionip/config     data.sections.auth.user_key / server_key (present or not)
//   GET /decisionip/stats      Info = JSON {total_entries,total_packets,total_bytes,ipsets[{name,entries,packets,bytes,active}]}
//                              (iptables DROP counters: they restart from zero with the firewall)
//   GET /decisionip/health     relay of the decisionip binary: Version, license, buffer, downerror,
//                              last_download_error_at, last_down_error
//   GET|PUT /decisionip/databases   the 7 lists and whether each one is enabled
// The left column (decision-ip-agent-status, service_status(), CurrentIPset()) is shared and
// refreshed by its own timer: kept exactly as it was.
// One model (dipm_model) feeds every widget; one widget = one function.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["dipm-host"])){dipm_host();exit;}
if(isset($_GET["services-status"])){service_status();exit;}
if(isset($_GET["tiny"])){tiny();exit;}
if(isset($_GET["server-key"])){serverkey_request();exit;}
if(isset($_POST["userkey"])){userkey_save();exit;}
if(isset($_POST["postdb"])){flat_databases_save();exit;}
if(isset($_GET["firewall-restart"])){firewall_restart_js();exit;}
if(isset($_POST["dipm-fwrestart"])){firewall_restart_save();exit;}
if(isset($_GET["already-register-js"])){already_register_js();exit;}
if(isset($_GET["already-register-popup"])){already_register_popup();exit;}
if(isset($_POST["aukey"])){already_register_save();exit;}
if(isset($_GET["reload"])){reload();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


    $html=$tpl->page_header("DecisionIP","fa-decisionip",
        "{decisionIPAbout}","$page?table-start=yes",
        "decisionip","progress-decisionip-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("DecisionIP",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function already_register_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{already_server_key}";
    return $tpl->js_dialog1($title,"$page?already-register-popup=yes",650);
}
function already_register_popup():bool{
    $tpl=new template_admin();
    $Array=getConfig();
    $inf=array("user_key"=>"","server_key"=>"");
    if(isset($Array["data"]["sections"]["auth"]) && is_array($Array["data"]["sections"]["auth"])){
        $inf=array_merge($inf,$Array["data"]["sections"]["auth"]);
    }
    $form[]=$tpl->field_text("aukey","{userkey}",$inf["user_key"],true,"{dipm_aukey_help}");
    $form[]=$tpl->field_text("sukey","{serverkey}",$inf["server_key"],true,"{dipm_sukey_help}");
    echo $tpl->form_outside("",$form,"{dipm_already_explain}","{apply}","dialogInstance1.close();DipmReload(true);","AsFirewallManager");
    return true;
}
function already_register_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $aukey=urlencode($_POST["aukey"]);
    $sukey=urlencode($_POST["sukey"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/register/$aukey/$sukey"),true);
    if(!isset($json["Status"])){
        echo $tpl->post_error($tpl->js_error("{protocol_error}"));
        return false;
    }
    if(!$json["Status"]){
        echo $tpl->post_error($tpl->js_error($json["Error"]));
        return false;
    }
    return admin_tracks("Register to DecisionIP: user:$aukey server:$sukey");
}
function firewall_restart_js(){
    $tpl=new template_admin();
    $tpl->js_dialog_confirm_action("{dipm_fw_confirm}","dipm-fwrestart","none","DipmReload(true);");
}
function firewall_restart_save(){
    $tpl=new template_admin();
    $err=dipm_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/firewall/restart"));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return;}
    admin_tracks("Restart the DecisionIP firewall rules");
}
function reload():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionipapps/reload"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    admin_tracks("Reload the DecisionIP service");
    return $tpl->js_ok("{success}");

}

// Step 1 of the setup: echoes nothing on success, the error text otherwise (DipmRegister shows it).
function userkey_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $raw=trim((string)$_POST["userkey"]);
    if(strlen($raw)<5){echo $tpl->_ENGINE_parse_body("{dipm_err_userkey}");return false;}
    $usrkey=urlencode($raw);
    $err=dipm_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/userkey/$usrkey"));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return false;}
    return admin_tracks("Success saving DecisionIP user key $usrkey");
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
        "progress-decisionip-restart");
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

function getConfig():array{
    if(isset($GLOBALS["DECIONIP"]) && is_array($GLOBALS["DECIONIP"])){
        return $GLOBALS["DECIONIP"];
    }
    $j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);
    $GLOBALS["DECIONIP"]=is_array($j)?$j:array();
    return $GLOBALS["DECIONIP"];
}
function serverkey_request():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/serverkey"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    header("content-type: application/x-javascript");
    echo "DipmReload(true);";
    return admin_tracks("Success request DecisionIP server key");
}

function table_start():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:330px;vertical-align: top'><div id='decision-ip-agent-status' style='width:330px'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>
    <div id='dipm-host' class='dipm' aria-live='polite'></div>
    </td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("decision-ip-agent-status",$page,"services-status=yes");

    $html[]="Loadjs('$page?tiny=yes');";
    $html[]="LoadAjax('dipm-host','$page?dipm-host=yes');";
    $html[]=$js;
    $html[]="</script>";
    echo dipm_style().$tpl->_ENGINE_parse_body($html).dipm_js($page);
    return true;
}
function tiny(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?firewall-restart=yes')", ico_refresh, "{your_firewall}: {restart}");
    $topbuttons[] = array("Loadjs('$page?reload=yes')", ico_refresh, "{reload_service}");
    $topbuttons[] = array("Loadjs('$page?already-register-js=yes')",ico_server,"{already_server_key}");


    $TINY_ARRAY["TITLE"]="DecisionIP";
    $TINY_ARRAY["ICO"]="fa-decisionip";
    $TINY_ARRAY["EXPL"]="{dipm_header_explain}";
    $TINY_ARRAY["URL"]="decisionip";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";
}

function CurrentIPset():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/ipsets"),true);
    $databases=array();
    if(!is_array($json)){return $databases;}
    foreach ($json as $ipset){
        if(!$ipset["enabled"]){
            continue;
        }
        $databases[$ipset["database"]]=$ipset["entries"];
    }
    return $databases;
}

// name => enabled, in the daemon's order; null when the daemon cannot be read.
function GetDatabases(){
    $dbIn=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/databases"),true);
    if(!is_array($dbIn) || empty($dbIn["success"]) || !isset($dbIn["data"]["databases"])){
        return null;
    }
    $dbnames=array();
    foreach ($dbIn["data"]["databases"] as $ligne){
        $dbnames[$ligne["name"]]=!empty($ligne["enabled"]);
    }
    return $dbnames;
}

// One list switched on or off. Echoes nothing on success, the error otherwise.
// "Anonymous relays" and its "without VPNs" variant exclude each other: turning one on turns the
// other off, so the switches always show what the firewall applies.
function flat_databases_save():bool{
    $tpl=new template_admin();
    $RequiredDb=preg_replace('/[^a-z0-9_-]/','',strtolower((string)$_POST["postdb"]));
    $Renabled=intval($_POST["enabled"]);
    $dbs=GetDatabases();
    if(!is_array($dbs)){echo $tpl->_ENGINE_parse_body("{dipm_err_unreachable}");return false;}
    if(!isset($dbs[$RequiredDb])){echo $tpl->_ENGINE_parse_body("{dipm_err_unknown}");return false;}
    $exclusive=array("anonymousrelays"=>"anonymousrelays-novpn","anonymousrelays-novpn"=>"anonymousrelays");
    $data=array("databases"=>array());
    foreach ($dbs as $db=>$enabled){
        if($db==$RequiredDb){continue;}
        if($Renabled==1 && isset($exclusive[$RequiredDb]) && $exclusive[$RequiredDb]==$db){continue;}
        if($enabled){$data["databases"][]=$db;}
    }
    if($Renabled==1){$data["databases"][]=$RequiredDb;}

    $err=dipm_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/decisionip/databases", $data));
    if($err!=""){echo $tpl->_ENGINE_parse_body($err);return false;}
    return admin_tracks("DecisionIP set $RequiredDb to $Renabled");
}

// Daemon answer -> "" on success, else the error text.
function dipm_rest_error($raw){
    $j=json_decode((string)$raw,true);
    if(!is_array($j)){return "{dipm_err_unreachable}";}
    if(isset($j["success"]) && !$j["success"]){ return isset($j["error"])?(string)$j["error"]:"{dipm_err_unreachable}"; }
    if(isset($j["Status"]) && !$j["Status"]){ return isset($j["Error"])?(string)$j["Error"]:"{dipm_err_unreachable}"; }
    return "";
}

// ── Model ─────────────────────────────────────────────────────────────────

// database name (/decisionip/databases) => ipset name (/decisionip/stats), cf. DecisionIP/Fetch.go lKnown()
function dipm_ipset_of($db){
    $map=array("activeattacks"=>"dip_activeattacks","anonymousrelays"=>"dip_anonyrelays",
        "anonymousrelays-novpn"=>"dip_anonyrelays_novpn","unethicalbots"=>"dip_unethicalbots",
        "whitelist"=>"dip_whitelist_remote","honeypot"=>"dip_honey",
        "shadow_fortress"=>"dip_shadow_fortress","datashield"=>"dip_datashield");
    return isset($map[$db])?$map[$db]:"";
}

function dipm_model(){
    $m=array("userkey"=>false,"serverkey"=>false,"stats_ok"=>false,"entries"=>0,"packets"=>0,"bytes"=>0,
        "per"=>array(),"health_ok"=>false,"version"=>"","license"=>"","buffer"=>null,
        "down_error"=>"","down_error_at"=>0,"dbs"=>null,"privs"=>true);

    $cfg=getConfig();
    if(isset($cfg["data"]["sections"]["auth"])){
        $a=$cfg["data"]["sections"]["auth"];
        $m["userkey"]=isset($a["user_key"]) && strlen((string)$a["user_key"])>=5;
        $m["serverkey"]=isset($a["server_key"]) && strlen((string)$a["server_key"])>=5;
    }

    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/stats"),true);
    if(is_array($st) && !empty($st["Status"]) && isset($st["Info"])){
        $s=json_decode((string)$st["Info"],true);
        if(is_array($s)){
            $m["stats_ok"]=true;
            $m["entries"]=isset($s["total_entries"])?floatval($s["total_entries"]):0;
            $m["packets"]=isset($s["total_packets"])?floatval($s["total_packets"]):0;
            $m["bytes"]=isset($s["total_bytes"])?floatval($s["total_bytes"]):0;
            if(isset($s["ipsets"]) && is_array($s["ipsets"])){
                foreach($s["ipsets"] as $i){
                    if(!isset($i["name"])){continue;}
                    $m["per"][$i["name"]]=array("entries"=>floatval($i["entries"] ?? 0),
                        "packets"=>floatval($i["packets"] ?? 0),"bytes"=>floatval($i["bytes"] ?? 0),
                        "active"=>!empty($i["active"]));
                }
            }
        }
    }

    $h=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/health"),true);
    if(is_array($h) && isset($h["Version"])){
        $m["health_ok"]=true;
        $m["version"]=(string)$h["Version"];
        $m["license"]=isset($h["license"])?(string)$h["license"]:"";
        $m["buffer"]=isset($h["buffer"])?floatval($h["buffer"]):null;
        if(!empty($h["downerror"])){
            $m["down_error"]=isset($h["last_down_error"])?(string)$h["last_down_error"]:"";
            $m["down_error_at"]=isset($h["last_download_error_at"])?intval($h["last_download_error_at"]):0;
            if($m["down_error"]==""){$m["down_error"]="?";}
        }
    }

    $m["dbs"]=GetDatabases();
    return $m;
}

function dipm_n($v){
    $tpl=new template_admin();
    return $tpl->FormatNumber($v);
}
function dipm_bytes($b){
    if($b<=0){return "0 KB";}
    return preg_replace('/(\d)\s*([KMGTP]?B)$/','$1 $2',trim(FormatBytes($b/1024,true)));
}

// Display order: lone blockers, then the two exclusive relay lists side by side, then the
// community lists, the allow list last. Lists unknown to this console are appended.
function dipm_order($dbs){
    $want=array("activeattacks","unethicalbots","anonymousrelays","anonymousrelays-novpn","datashield","shadow_fortress","whitelist");
    $out=array();
    foreach($want as $w){ if(array_key_exists($w,$dbs)){$out[]=$w;} }
    foreach($dbs as $k=>$v){ if(!in_array($k,$out)){$out[]=$k;} }
    return $out;
}

// ── Host ──────────────────────────────────────────────────────────────────

function dipm_host(){
    $tpl=new template_admin();
    $m=dipm_model();
    $h=array();
    $h[]=dipm_banner();
    if(!$m["userkey"] || !$m["serverkey"]){ $h[]=dipm_setup($m); }
    if($m["down_error"]!=""){ $h[]=dipm_alert_download($m); }
    $h[]=dipm_kpis($m);
    $h[]=dipm_lists($m);
    $h[]=dipm_about($m);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Widgets ───────────────────────────────────────────────────────────────

function dipm_banner(){
    $chips=array(array("fas fa-bolt","{dipm_chip_firewall}"),array("fas fa-rotate","{dipm_chip_refresh}"),
        array("fas fa-toggle-on","{dipm_chip_choose}"));
    $c=array();
    foreach($chips as $ch){ $c[]="<span class='dipm-chip'><i class='{$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>"; }
    return "<section class='dipm-banner'><div class='dipm-banner-ico' aria-hidden='true'><i class='fas fa-shield-halved'></i></div>
<div class='dipm-banner-txt'><h2 class='dipm-banner-t'>{dipm_banner_title}</h2><p class='dipm-banner-p'>{dipm_banner_text}</p>
<div class='dipm-chips'>".implode("",$c)."</div><p class='dipm-powered'>{dipm_powered}</p></div></section>";
}

function dipm_setup($m){
    $s1=$m["userkey"]?"done":"current";
    $s2=$m["serverkey"]?"done":($m["userkey"]?"current":"wait");
    $h=array();
    $h[]="<section class='dipm-card dipm-setup'><div class='dipm-sec-head'><h3 class='dipm-card-t'>{dipm_setup_title}</h3><span class='dipm-muted'>{dipm_setup_sub}</span></div>";
    $h[]="<div class='dipm-steps'>";
    $h[]=dipm_step_account($s1);
    $h[]=dipm_step_server($s2);
    $h[]="</div>";
    $h[]="<div class='dipm-setup-foot'><button type='button' class='dipm-link' onclick=\"Loadjs('".CurrentPageName()."?already-register-js=yes')\"><i class='fas fa-key' aria-hidden='true'></i>{already_server_key}</button></div>";
    $h[]="</section>";
    return implode("",$h);
}

function dipm_step_badge($state){
    if($state=="done"){return "<span class='dipm-st dipm-st-on'><i class='fas fa-circle-check' aria-hidden='true'></i>{dipm_step_done}</span>";}
    if($state=="current"){return "<span class='dipm-st dipm-st-cur'>{dipm_step_current}</span>";}
    return "<span class='dipm-st dipm-st-off'>{dipm_step_wait}</span>";
}

function dipm_step_account($state){
    $h=array();
    $h[]="<div class='dipm-step dipm-step-$state'><div class='dipm-step-head'><span class='dipm-step-n'>1</span><b>{dipm_step1_title}</b>".dipm_step_badge($state)."</div>";
    if($state=="done"){
        $h[]="<p class='dipm-muted'>{dipm_step1_done}</p>";
    }else{
        $h[]="<p class='dipm-muted'>{dipm_step1_text}</p>";
        $h[]="<label class='dipm-lbl' for='dipm-userkey'>{userkey}</label>";
        $h[]="<div class='dipm-inrow'><input id='dipm-userkey' class='dipm-in' type='text' autocomplete='off' spellcheck='false' onkeydown=\"if(event.keyCode==13){DipmRegister();}\">";
        $h[]="<button type='button' class='dipm-btn' onclick='DipmRegister()'>{register}</button></div>";
        $h[]="<p class='dipm-help'>{dipm_userkey_help}</p>";
        $h[]="<button type='button' class='dipm-link' onclick=\"s_PopUp('https://wiki.articatech.com/en/reverse-proxy/security/decisionip/userkey','1024','800')\"><i class='fas fa-circle-question' aria-hidden='true'></i>{dipm_online_help}</button>";
    }
    $h[]="</div>";
    return implode("",$h);
}

function dipm_step_server($state){
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='dipm-step dipm-step-$state'><div class='dipm-step-head'><span class='dipm-step-n'>2</span><b>{dipm_step2_title}</b>".dipm_step_badge($state)."</div>";
    if($state=="done"){
        $h[]="<p class='dipm-muted'>{dipm_step2_done}</p>";
    }else{
        $h[]="<p class='dipm-muted'>{dipm_step2_text}</p>";
        if($state=="current"){
            $h[]="<button type='button' class='dipm-btn' onclick=\"Loadjs('$page?server-key=yes')\"><i class='fas fa-rotate' aria-hidden='true'></i>{register}: {your_server}</button>";
        }else{
            $h[]="<button type='button' class='dipm-btn' disabled aria-disabled='true'><i class='fas fa-rotate' aria-hidden='true'></i>{register}: {your_server}</button>";
            $h[]="<p class='dipm-help'>{dipm_step2_wait}</p>";
        }
    }
    $h[]="</div>";
    return implode("",$h);
}

function dipm_alert_download($m){
    $page=CurrentPageName();
    $when="";
    if($m["down_error_at"]>0){
        $tpl=new template_admin();
        $when="<span class='dipm-when'>".$tpl->time_to_date($m["down_error_at"],true)."</span>";
    }
    $err=htmlspecialchars($m["down_error"],ENT_QUOTES,"UTF-8");
    return "<section class='dipm-card dipm-alert' role='alert'><div class='dipm-alert-ico' aria-hidden='true'><i class='fas fa-triangle-exclamation'></i></div>
<div class='dipm-alert-txt'><div class='dipm-alert-head'><h3 class='dipm-card-t'>{dipm_down_title}</h3>$when</div><p class='dipm-muted'>{dipm_down_text}</p>
<div class='dipm-err'>$err</div><button type='button' class='dipm-btn dipm-btn-err' onclick=\"Loadjs('$page?reload=yes')\"><i class='fas fa-rotate' aria-hidden='true'></i>{reload_service}</button></div></section>";
}

function dipm_kpis($m){
    $h=array();
    $h[]="<section class='dipm-sec'><div class='dipm-sec-head'><h3 class='dipm-sec-t'>{dipm_glance_title}</h3></div>";
    $h[]="<div class='dipm-kpis'>".dipm_kpi_refused($m).dipm_kpi_watch($m).dipm_kpi_traffic($m)."</div>";
    if(!$m["stats_ok"]){
        $h[]="<p class='dipm-cap dipm-cap-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>{dipm_kpi_unavailable}</p>";
    }elseif($m["packets"]==0){
        $h[]="<p class='dipm-cap'>{dipm_kpi_zero}</p>";
    }else{
        $h[]="<p class='dipm-cap'>{dipm_kpi_since}</p>";
    }
    $h[]="</section>";
    return implode("",$h);
}

function dipm_kpi($icon,$tone,$value,$label,$sub,$zero){
    $cls=$zero?" dipm-kpi-zero":"";
    return "<div class='dipm-card dipm-kpi$cls'><div class='dipm-kpi-ico dipm-tone-$tone' aria-hidden='true'><i class='$icon'></i></div>
<div class='dipm-kpi-v'>$value</div><div class='dipm-kpi-l'>$label</div><div class='dipm-kpi-s'>$sub</div></div>";
}
function dipm_kpi_refused($m){
    $v=$m["stats_ok"]?dipm_n($m["packets"]):"—";
    return dipm_kpi("fas fa-hand","red",$v,"{dipm_kpi_refused}","{dipm_kpi_refused_sub}",!$m["stats_ok"]||$m["packets"]==0);
}
function dipm_kpi_watch($m){
    $v=$m["stats_ok"]?dipm_n($m["entries"]):"—";
    return dipm_kpi("fas fa-database","accent",$v,"{dipm_kpi_watch}","{dipm_kpi_watch_sub}",!$m["stats_ok"]||$m["entries"]==0);
}
function dipm_kpi_traffic($m){
    $v=$m["stats_ok"]?dipm_bytes($m["bytes"]):"—";
    return dipm_kpi("fas fa-gauge","amber",$v,"{dipm_kpi_traffic}","{dipm_kpi_traffic_sub}",!$m["stats_ok"]||$m["bytes"]==0);
}

function dipm_lists($m){
    $h=array();
    $h[]="<section class='dipm-sec'><div class='dipm-sec-head dipm-sec-col'><h3 class='dipm-sec-t'>{dipm_lists_title}</h3><p class='dipm-muted'>{dipm_lists_text}</p></div>";
    if(!is_array($m["dbs"])){
        $h[]="<div class='dipm-card dipm-alert' role='alert'><div class='dipm-alert-ico' aria-hidden='true'><i class='fas fa-plug-circle-xmark'></i></div><div class='dipm-alert-txt'><h3 class='dipm-card-t'>{dipm_lists_unreadable}</h3><p class='dipm-muted'>{dipm_lists_unreadable_text}</p></div></div></section>";
        return implode("",$h);
    }
    $blocking=0;
    foreach($m["dbs"] as $db=>$en){ if($en && $db!="whitelist"){$blocking++;} }
    if($blocking==0 && $m["userkey"] && $m["serverkey"]){ $h[]=dipm_lists_empty(); }
    $h[]="<div class='dipm-grid'>";
    foreach(dipm_order($m["dbs"]) as $db){
        if($db=="anonymousrelays"){ $h[]=dipm_novpn_note(); }
        $h[]=dipm_list_card($db,$m);
    }
    $h[]="</div></section>";
    return implode("",$h);
}

function dipm_lists_empty(){
    return "<div class='dipm-card dipm-empty'><div class='dipm-empty-ico' aria-hidden='true'><i class='fas fa-shield'></i></div><div><b>{dipm_empty_title}</b><p class='dipm-muted'>{dipm_empty_text}</p>
<button type='button' class='dipm-btn' onclick=\"DipmToggle('activeattacks',1)\"><i class='fas fa-toggle-on' aria-hidden='true'></i>{dipm_empty_btn}</button></div></div>";
}

function dipm_novpn_note(){
    return "<div class='dipm-note'><i class='fas fa-circle-info' aria-hidden='true'></i><span><b>{dipm_pick_one}</b> {dipm_novpn_note}</span></div>";
}

// Static facts about each list (labels, icon, colour, badges). Texts are tokens.
function dipm_list_meta($db){
    $meta=array(
        "activeattacks"=>array("fas fa-crosshairs","#dc2626",array()),
        "anonymousrelays"=>array("fas fa-user-secret","#d97706",array()),
        "anonymousrelays-novpn"=>array("fas fa-user-shield","#a16207",array()),
        "unethicalbots"=>array("fas fa-robot","#64748b",array("beta")),
        "whitelist"=>array("fas fa-handshake","#15803d",array("allow","enterprise")),
        "datashield"=>array("fas fa-shield","#15803d",array("minne")),
        "shadow_fortress"=>array("fas fa-chess-rook","#005447",array("grujowmi","max100k")),
    );
    return isset($meta[$db])?$meta[$db]:array("fas fa-list","#64748b",array());
}

function dipm_badge($b){
    $map=array(
        "beta"=>"<span class='dipm-badge dipm-badge-amber'>{dipm_badge_beta}</span>",
        "allow"=>"<span class='dipm-badge dipm-badge-green'><i class='fas fa-circle-check' aria-hidden='true'></i>{dipm_badge_allow}</span>",
        "enterprise"=>"<span class='dipm-badge'><i class='fas fa-certificate' aria-hidden='true'></i>{dipm_badge_enterprise}</span>",
        "minne"=>"<span class='dipm-badge'>{dipm_badge_minne}</span>",
        "grujowmi"=>"<span class='dipm-badge'>{dipm_badge_grujowmi}</span>",
        "max100k"=>"<span class='dipm-badge'>{dipm_badge_max100k}</span>",
    );
    return isset($map[$b])?$map[$b]:"";
}

function dipm_list_card($db,$m){
    $en=!empty($m["dbs"][$db]);
    $meta=dipm_list_meta($db);
    $tok=str_replace("-","_",$db);
    $name=($db=="datashield")?"Data-Shield IPv4 Blocklist":"{didb_$db}";
    $badges="";
    foreach($meta[2] as $b){ $badges.=dipm_badge($b); }
    $ck=$en?"true":"false";
    $state=$en?"{dipm_on}":"{dipm_off}";
    $dis=$m["privs"]?"":" disabled aria-disabled='true'";
    $next=$en?0:1;
    $sw="<button type='button' class='dipm-sw' role='switch' aria-checked='$ck' aria-label=\"{dipm_turn_on} $name\" onclick=\"DipmToggle('$db',$next)\"$dis><span class='dipm-sw-k'></span></button>";

    $foot="";
    $ipset=dipm_ipset_of($db);
    $p=($ipset!="" && isset($m["per"][$ipset]))?$m["per"][$ipset]:null;
    if(!$en){
        $foot="<span class='dipm-foot-off'>{dipm_foot_off}</span>";
    }elseif(!$m["stats_ok"] || $p===null){
        $foot="<span class='dipm-foot-off'>{dipm_foot_nostats}</span>";
    }elseif($db=="whitelist"){
        $foot="<span>".dipm_n($p["entries"])." {dipm_addr_allowed}</span>";
    }else{
        if($p["entries"]==0 && $p["packets"]==0){
            $foot="<span class='dipm-st dipm-st-warn'><i class='fas fa-hourglass-half' aria-hidden='true'></i>{dipm_foot_loading}</span>";
        }else{
            $foot="<span>".dipm_n($p["entries"])." {dipm_addresses} · ".dipm_n($p["packets"])." {dipm_refused} · ".dipm_bytes($p["bytes"])."</span>";
            $foot.="<span class='dipm-st dipm-st-on'><span class='dipm-dot' aria-hidden='true'></span>{dipm_foot_active}</span>";
        }
    }
    $cls=$en?"":" dipm-list-off";
    return "<article class='dipm-card dipm-list$cls'><div class='dipm-list-head'>
<div class='dipm-list-ico' style='color:{$meta[1]};background:{$meta[1]}14' aria-hidden='true'><i class='{$meta[0]}'></i></div>
<div class='dipm-list-name'><b>$name</b><div class='dipm-badges'>$badges</div></div>
<div class='dipm-sw-wrap'><span class='dipm-sw-t'>$state</span>$sw</div></div>
<p class='dipm-list-p'>{dipm_l_{$tok}_text}</p>
<p class='dipm-ex'><b>{dipm_example}</b> {dipm_l_{$tok}_ex}</p>
<div class='dipm-list-foot'>$foot</div></article>";
}

function dipm_about($m){
    $v=$m["health_ok"]&&$m["version"]!=""?htmlspecialchars($m["version"],ENT_QUOTES,"UTF-8"):"—";
    $l=$m["health_ok"]&&$m["license"]!=""?htmlspecialchars($m["license"],ENT_QUOTES,"UTF-8"):"—";
    $b=($m["health_ok"]&&$m["buffer"]!==null)?dipm_n($m["buffer"]):"—";
    $h=array();
    $h[]="<section class='dipm-card dipm-about'><h3 class='dipm-card-t'>{dipm_about_title}</h3>";
    if(!$m["health_ok"]){ $h[]="<p class='dipm-muted'>{dipm_about_unreachable}</p>"; }
    $h[]="<div class='dipm-about-grid'>";
    $h[]="<div><span class='dipm-about-l'><i class='fas fa-circle-info' aria-hidden='true'></i>{dipm_version}</span><span class='dipm-about-v'>$v</span></div>";
    $h[]="<div><span class='dipm-about-l'><i class='fas fa-certificate' aria-hidden='true'></i>{dipm_license}</span><span class='dipm-about-v'>$l</span></div>";
    $h[]="<div><span class='dipm-about-l'><i class='fas fa-layer-group' aria-hidden='true'></i>{dipm_buffer}</span><span class='dipm-about-v'>$b</span><span class='dipm-help'>{dipm_buffer_help}</span></div>";
    $h[]="</div></section>";
    return implode("",$h);
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function dipm_js($page){
    $tpl=new template_admin();
    $empty=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{dipm_err_userkey}"),ENT_QUOTES,"UTF-8"));
    return "<script>
function DipmModalOpen(){
    if(document.querySelector('.modal.in, .modal.show')){ return true; }
    var sw=document.querySelector('.sweet-alert.visible, .sweet-alert.showSweetAlert');
    return !!(sw && sw.offsetParent!==null);
}
function DipmReload(force){
    if(!document.getElementById('dipm-host')){ return; }
    if(!force){
        if(document.hidden || DipmModalOpen()){ return; }
        var a=document.activeElement; if(a && a.id==='dipm-userkey' && a.value!==''){ return; }
    }
    LoadAjaxSilent('dipm-host','$page?dipm-host=yes');
}
function DipmError(t){
    if(window.swal){ swal({title:'Oops...',text:t,type:'warning',html:true}); } else { alert(t); }
}
function DipmToggle(db,on){
    $.post('$page',{postdb:db,enabled:on}).done(function(r){
        r=String(r||'').trim(); if(r.length>0){ DipmError(r); }
        DipmReload(true);
    }).fail(function(x){ DipmError(x.statusText||'Error'); DipmReload(true); });
}
function DipmRegister(){
    var i=document.getElementById('dipm-userkey'); if(!i){ return; }
    var v=String(i.value||'').trim(); if(v.length<5){ DipmError($empty); i.focus(); return; }
    $.post('$page',{userkey:v}).done(function(r){
        r=String(r||'').trim(); if(r.length>0){ DipmError(r); return; }
        DipmReload(true);
    }).fail(function(x){ DipmError(x.statusText||'Error'); });
}
(function(){
    if(window.DipmTimer){ clearInterval(window.DipmTimer); }
    window.DipmTimer=setInterval(function(){
        if(!document.getElementById('dipm-host')){ clearInterval(window.DipmTimer); window.DipmTimer=null; return; }
        DipmReload(false);
    },30000);
})();
</script>";
}

function dipm_style(){
    if(isset($GLOBALS["DIPM_STYLE"])){return "";}
    $GLOBALS["DIPM_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.dipm{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px}
.dipm *{box-sizing:border-box}
.dipm h2,.dipm h3,.dipm p{margin:0}
.dipm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.dipm-muted{color:#475569;font-size:13px;line-height:1.5;margin-top:4px!important}
.dipm-help{color:#64748b;font-size:13px;line-height:1.45;margin-top:4px!important;display:block}
.dipm-banner{display:flex;gap:18px;align-items:flex-start;background:$soft;border:1px solid #bfd4d1;border-radius:16px;padding:24px}
.dipm-banner-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.dipm-banner-txt{flex:1;min-width:0}
.dipm-banner-t{font-size:20px;font-weight:600;line-height:1.3}
.dipm-banner-p{font-size:14px;line-height:1.55;color:#334155;margin-top:6px!important;max-width:720px}
.dipm-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.dipm-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#334155;background:#fff;border:1px solid #bfd4d1;border-radius:999px;padding:4px 12px}
.dipm-chip i,.dipm-chip svg{color:$accent}
.dipm-powered{font-size:13px;color:#64748b;margin-top:12px!important}
.dipm-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.dipm-btn:hover{background:#003d33}
.dipm-btn[disabled]{background:#cbd5e1;color:#475569;cursor:not-allowed}
.dipm-btn-err{background:#dc2626}.dipm-btn-err:hover{background:#b91c1c}
.dipm-link{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;padding:4px 0;margin-top:8px}
.dipm-link:hover{text-decoration:underline}
.dipm-btn:focus-visible,.dipm-link:focus-visible,.dipm-sw:focus-visible,.dipm-in:focus{outline:2px solid $accent;outline-offset:2px}
.dipm-sec{display:flex;flex-direction:column;gap:12px}
.dipm-sec-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap}
.dipm-sec-col{flex-direction:column;align-items:flex-start;gap:0}
.dipm-sec-t{font-size:17px;font-weight:600}
.dipm-card-t{font-size:15px;font-weight:600}
.dipm-setup{padding:20px 24px}
.dipm-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-top:14px}
.dipm-step{border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;display:flex;flex-direction:column;align-items:flex-start}
.dipm-step-done{background:#f0fdf4;border-color:#bbf7d0}
.dipm-step-current{border:2px solid $accent}
.dipm-step-wait{background:#f8fafc}
.dipm-step-head{display:flex;align-items:center;gap:10px;width:100%}
.dipm-step-head b{flex:1;font-size:14px}
.dipm-step-n{flex:0 0 26px;height:26px;border-radius:50%;background:$soft;color:$accent;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:13px}
.dipm-step-done .dipm-step-n{background:#dcfce7;color:#15803d}
.dipm-step .dipm-btn{margin-top:12px}
.dipm-lbl{font-size:13px;font-weight:600;margin:12px 0 4px;display:block}
.dipm-inrow{display:flex;gap:8px;width:100%}
.dipm-inrow .dipm-btn{margin-top:0}
.dipm-in{flex:1;min-width:0;height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:13px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.dipm-setup-foot{margin-top:12px;border-top:1px solid #f1f5f9;padding-top:4px}
.dipm-st{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;border-radius:999px;padding:2px 10px;white-space:nowrap}
.dipm-st-on{background:#dcfce7;color:#166534}
.dipm-st-cur{background:$soft;color:$accent}
.dipm-st-off{background:#f1f5f9;color:#475569}
.dipm-st-warn{background:#fef3c7;color:#92400e}
.dipm-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a}
.dipm-alert{display:flex;gap:16px;align-items:flex-start;padding:20px 24px;background:#fef2f2;border-color:#fecaca}
.dipm-alert-ico{flex:0 0 40px;height:40px;border-radius:10px;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:18px}
.dipm-alert-txt{flex:1;min-width:0}
.dipm-alert-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
.dipm-alert .dipm-card-t{color:#991b1b}
.dipm-when{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#7f1d1d}
.dipm-err{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#7f1d1d;background:#fff;border:1px solid #fecaca;border-radius:8px;padding:8px 10px;margin:10px 0 12px;word-break:break-word}
.dipm-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.dipm-kpi{padding:18px 20px}
.dipm-kpi-ico{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:10px}
.dipm-tone-red{background:#fef2f2;color:#dc2626}
.dipm-tone-accent{background:$soft;color:$accent}
.dipm-tone-amber{background:#fffbeb;color:#d97706}
.dipm-kpi-v{font-size:28px;font-weight:600;line-height:34px;font-variant-numeric:tabular-nums}
.dipm-kpi-zero .dipm-kpi-v{color:#94a3b8}
.dipm-kpi-l{font-size:14px;font-weight:600;margin-top:2px}
.dipm-kpi-s{font-size:13px;color:#64748b;margin-top:2px}
.dipm-cap{font-size:13px;color:#64748b;margin:0!important}
.dipm-cap-err{color:#b91c1c;display:flex;gap:6px;align-items:center}
.dipm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.dipm-list{padding:18px 20px;display:flex;flex-direction:column}
.dipm-list-off{background:#fcfcfd}
.dipm-list-head{display:flex;gap:12px;align-items:flex-start}
.dipm-list-ico{flex:0 0 36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
.dipm-list-name{flex:1;min-width:0}
.dipm-list-name b{font-size:15px;display:block;line-height:1.3}
.dipm-badges{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
.dipm-badge{display:inline-flex;align-items:center;gap:4px;font-size:13px;color:#334155;background:#f1f5f9;border-radius:999px;padding:1px 9px}
.dipm-badge-amber{background:#fef3c7;color:#92400e;font-weight:600}
.dipm-badge-green{background:#dcfce7;color:#166534}
.dipm-sw-wrap{display:flex;align-items:center;gap:8px;flex:0 0 auto}
.dipm-sw-t{font-size:13px;font-weight:600;color:#334155;min-width:22px;text-align:right}
.dipm-list-off .dipm-sw-t{color:#64748b}
.dipm-sw{position:relative;width:44px;height:24px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;flex:0 0 44px}
.dipm-sw[aria-checked=true]{background:$accent}
.dipm-sw[disabled]{opacity:.5;cursor:not-allowed}
.dipm-sw-k{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2)}
.dipm-sw[aria-checked=true] .dipm-sw-k{left:23px}
.dipm-list-p{font-size:13px;color:#334155;line-height:1.5;margin-top:12px!important}
.dipm-ex{font-size:13px;color:#475569;line-height:1.5;background:#f8fafc;border-radius:8px;padding:8px 10px;margin-top:10px!important}
.dipm-ex b{color:#0f172a}
.dipm-list-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#0f172a;font-variant-numeric:tabular-nums}
.dipm-list-p+.dipm-ex+.dipm-list-foot{margin-top:12px}
.dipm-foot-off{color:#64748b}
.dipm-note{grid-column:1/-1;display:flex;gap:8px;align-items:center;font-size:13px;color:#334155;background:$soft;border:1px solid #bfd4d1;border-radius:10px;padding:8px 14px}
.dipm-note i,.dipm-note svg{color:$accent}
.dipm-empty{display:flex;gap:16px;align-items:flex-start;padding:20px 24px;border-style:dashed}
.dipm-empty-ico{flex:0 0 44px;height:44px;border-radius:12px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.dipm-empty b{font-size:15px}
.dipm-empty .dipm-btn{margin-top:12px}
.dipm-about{padding:18px 24px}
.dipm-about-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:12px}
.dipm-about-l{display:flex;align-items:center;gap:6px;font-size:13px;color:#64748b}
.dipm-about-v{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:14px;font-weight:600;margin-top:2px;word-break:break-word}
@media(max-width:1200px){.dipm-grid{grid-template-columns:minmax(0,1fr)}.dipm-banner{flex-wrap:wrap}}
@media (prefers-reduced-motion:no-preference){.dipm-sw,.dipm-sw-k,.dipm-btn{transition:all .15s}.dipm-kpi-v{animation:dipmIn .2s ease-out}@keyframes dipmIn{from{opacity:0}to{opacity:1}}}
</style>";
}
