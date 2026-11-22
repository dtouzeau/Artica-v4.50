<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Refonte stitch 2026-11-18 : les anciens dynamic-rows / parent-lb-status (rafraichissement
// cellule par cellule et colonne de boutons) sont remplaces par l'hote #pps-main.
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){pps_build();exit;}
if(isset($_GET["main-host"])){pps_main();exit;}
if(isset($_GET["configuration-file"])){configuration_file_js();exit;}
if(isset($_GET["configuration-popup"])){configuration_file();exit;}
if(isset($_POST["conf"])){configuration_file_save();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_POST["removefeature"])){uninstall_perform();exit;}
if(isset($_GET["direct-js"])){pps_direct_js();exit;}
if(isset($_POST["pps-direct"])){pps_direct_save();exit;}
page();
function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{parent_proxies} &raquo;&raquo; {status}","fa fa-sitemap",
        "{parent_proxies_status_explain}","$page?tabs=yes","proxy-parents-status",
        "progress-squid-parent-restart",null,"table-loader-proxy-parents-status");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall("{parent_proxies} {status}");
		return false;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs():bool{

    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $array["{pps_tab_parents}"]="$page?table=yes";
    $array["{parameters}"]="fw.proxy.parents.parameters.php?flat=yes";
    echo $tpl->tabs_default($array);
    return true;
}


function uninstall_js():bool{
    $tpl=new template_admin();
    if(!isRights()){return $tpl->js_no_privileges();}
    // Le droit est signe dans la tache et reverifie par fw.progress.php avant la route.
    $jsUninstall=$tpl->framework_buildjs("/proxy/parents/uninstall",
        "squid.access.center.progress","squid.access.center.progress.txt",
        "parents-remove-feature","document.location.href='/proxy-status';",null,null,"AsSquidAdministrator");

    $tpl->js_confirm_delete("{parent_proxies}: {remove_this_section}","removefeature","yes",$jsUninstall);
    return true;
}
function uninstall_perform():bool{
    if(!isRights()){echo (new template_admin())->post_error("{ERROR_NO_PRIVS2}");return false;}
    admin_tracks("Warning! Remove Parent Proxy feature");
    return true;
}
function configuration_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isRights()){return $tpl->js_no_privileges();}
    $tpl->js_dialog1("{view_config}","$page?configuration-popup=yes",900);
    return true;

}
function configuration_file():bool{
    $tpl=new template_admin();
    // acls_peer.conf peut contenir les identifiants des proxys parents (cache_peer login=).
    if(!isRights()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $form[]=$tpl->field_textareacode("conf",null,@file_get_contents("/etc/squid3/acls_peer.conf"));
    $html[] = $tpl->form_outside("{view_config}", @implode("\n", $form), "",
        "{apply}", "blur()", "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function configuration_file_save():bool{
    $tpl        = new template_admin();
    $sock       = new sockets();
    $basesrc    = "acls_peer.conf";
    $errfile    = PROGRESS_DIR."/$basesrc.error";
    $testfile   = UPLOAD_DIR."/$basesrc";
    if(!isRights()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}

    $tpl->CLEAN_POST();
    @file_put_contents($testfile,$_POST["conf"]);

    // articarest /proxy/config/manual/acls_peer : installe, verifie (squid -k check),
    // restaure si la configuration est refusee ; l'erreur revient dans la reponse.
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/manual/acls_peer"));
    $data="";
    if(!is_object($json)){$data="articarest: ".json_last_error_msg();}
    elseif(!$json->Status){$data=isset($json->Error)?(string)$json->Error:"articarest: unknown error";}

    if($data<>""){
        if(preg_match("#^[0-9]+.*?\.conf(.+)#",$data,$re)){
            $data="Line $re[1]";
        }
        admin_tracks("Fatal error $data while modify directly proxy parents configuration file");
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text($data));
        return true;
    }
    admin_tracks("Success modify directly proxy parents configuration file");
    return true;
}

// Pentest 2026-11-04 : la page est ouverte aux comptes AsProxyMonitor (consultation) ;
// les actions qui modifient le proxy ou exposent sa configuration exigent le meme droit
// que leurs boutons, AsSquidAdministrator.
function isRights():bool{
	$users=new usersMenus();
	return (bool)$users->AsSquidAdministrator;
}

// ---------------------------------------------------------------------------
// Statut des proxys parents (maquette stitch_parent_proxy_status_dashboard).
// Un modele unique (pps_model) alimente tous les widgets ; l'hote #pps-main est
// recharge toutes les 10 s par un timer global unique (window.PpsTimer).
// Donnees reelles seulement : /proxy/peers/status (status, fetches, open_conns,
// test TCP d'1 s), /proxy/parents/acls (regles), /proxy/parentlb/status (INI du
// service de repartition), DisableInstantParentProxy (mode direct).
// ---------------------------------------------------------------------------

function pps_build():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=pps_model();

    $jsrestart=pps_apply_js();
    $topbuttons=array();
    $topbuttons[]=array("PpsReload(true);",ico_refresh,"{refresh}");
    if(isRights()){
        $topbuttons[]=array("Loadjs('$page?configuration-file=yes')",ico_file,"{view_config}");
        $topbuttons[]=array($jsrestart,ico_save,"{apply_parameters}");
    }
    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/parents',1024,768,'Wiki')";
    $topbuttons[]=array($jshelp,ico_support,"Wiki");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{parent_proxies} &raquo;&raquo; {status}";
    $TINY_ARRAY["ICO"]="fa fa-sitemap";
    $TINY_ARRAY["EXPL"]="{parent_proxies_status_explain}";
    $TINY_ARRAY["URL"]="proxy-parents-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html="<div class='pps'><div id='pps-main'>".pps_main_html($m)."</div></div>";
    echo pps_style().$tpl->_ENGINE_parse_body($html)."<script>NoSpinner();\n".pps_js($page)."\n$jstiny\nPpsApply();</script>";
    return true;
}

// Rechargement periodique : seulement le contenu, jamais le TINY.
function pps_main():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(pps_main_html(pps_model()))."<script>if(typeof PpsApply==='function'){PpsApply();}</script>";
    return true;
}

function pps_apply_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->framework_buildjs("/proxy/parents/compile",
        "squid.access.center.progress","squid.access.center.progress.log",
        "progress-squid-parent-restart","PpsReload(true);");
}

function pps_model():array{
    $m=array("peers"=>array(),"peers_ok"=>false,"total"=>0,"up"=>0,"down"=>0,
        "fetches"=>0,"conns"=>0,"rules_active"=>0,"rules_disabled"=>0,"rules_lb"=>0,
        "direct"=>false,"lb"=>array("ok"=>false,"error"=>"","enabled"=>false,"running"=>false,"memory"=>0,"uptime"=>""));

    $m["direct"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableInstantParentProxy"))==1;

    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/peers/status"),true);
    if(is_array($json)){
        $m["peers_ok"]=true;
        $list=(isset($json["peers"]) && is_array($json["peers"]))?$json["peers"]:array();
        foreach($list as $j){
            if(!is_array($j)){continue;}
            $p=pps_peer($j);
            $m["peers"][]=$p;
            $m["total"]++;
            if($p["up"]){$m["up"]++;}else{$m["down"]++;}
            $m["fetches"]+=$p["fetches"];
            $m["conns"]+=$p["conns"];
        }
        // Les pannes d'abord, puis l'ordre alphabetique : stable d'un rafraichissement a l'autre.
        usort($m["peers"],function($a,$b){
            if($a["up"]!=$b["up"]){return $a["up"]?1:-1;}
            return strcasecmp($a["name"],$b["name"]);
        });
    }

    $rules=acls_api_get("/proxy/parents/acls");
    if(is_array($rules)){
        foreach($rules as $r){
            if(!is_array($r)){continue;}
            if(intval($r["enabled"] ?? 1)==0){$m["rules_disabled"]++;continue;}
            $m["rules_active"]++;
            if(intval($r["useLb"] ?? 0)==1){$m["rules_lb"]++;}
        }
    }

    $lb=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parentlb/status"));
    if(!is_object($lb)){
        $m["lb"]["error"]=json_last_error_msg();
    }elseif(empty($lb->Status)){
        $m["lb"]["error"]=isset($lb->Error)?(string)$lb->Error:"";
    }else{
        $m["lb"]["ok"]=true;
        $ini=new Bs_IniHandler();
        $ini->loadString(isset($lb->Info)?(string)$lb->Info:"");
        $a=(isset($ini->_params["APP_PARENTLB"]) && is_array($ini->_params["APP_PARENTLB"]))?$ini->_params["APP_PARENTLB"]:array();
        // Convention Artica : service_disabled=1 signifie « active ».
        $m["lb"]["enabled"]=intval($a["service_disabled"] ?? 0)==1;
        $m["lb"]["running"]=intval($a["running"] ?? 0)==1;
        $m["lb"]["memory"]=intval($a["master_memory"] ?? 0);
        $m["lb"]["uptime"]=trim((string)($a["uptime"] ?? ""));
    }
    return $m;
}

function pps_peer(array $j):array{
    $addr=(string)($j["parent_addr"] ?? "");
    $port=intval($j["parent_port"] ?? 0);
    $telnet=trim((string)($j["telneterror"] ?? ""));
    $squidUp=strtolower(trim((string)($j["status"] ?? "")))=="up";
    return array(
        "name"=>(string)($j["parent_name"] ?? ""),
        "addr"=>$addr,"port"=>$port,
        "hostport"=>$addr.($port>0?":$port":""),
        "lb"=>($addr=="127.0.0.1" && $port==2320),
        "squid_up"=>$squidUp,"telnet"=>$telnet,
        "up"=>($squidUp && $telnet==""),
        "fetches"=>intval($j["fetches"] ?? 0),
        "conns"=>intval($j["open_conns"] ?? 0));
}

function pps_main_html(array $m):string{
    $h=array();
    $h[]=pps_hero($m);
    $h[]=pps_alerts($m);
    $h[]=pps_strip($m);
    $h[]="<div class='pps-cols'>";
    $h[]="<div class='pps-left'>".pps_peers($m)."</div>";
    $h[]="<div class='pps-side'>".pps_lb_card($m).pps_direct_card($m).pps_danger_card()."</div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

function pps_n(int $v):string{
    return number_format($v,0,"."," ");
}

function pps_hero(array $m):string{
    $lbOn=$m["lb"]["enabled"];
    $chips=array();
    $chips[]=pps_chip("fa-solid fa-plug","{pps_chip_tested}");
    $chips[]=pps_chip("fa-solid fa-rotate","{pps_chip_refresh}");
    $chips[]=pps_chip("fa-solid fa-scale-balanced",$lbOn?"{pps_chip_lb_on}":"{pps_chip_lb_off}",$lbOn?"pps-chip-on":"");
    if($m["rules_active"]+$m["rules_disabled"]==0){
        $chips[]=pps_chip("fa-solid fa-list-check","{pps_chip_norule}");
    }else{
        $txt="{pps_rules}: ".pps_n($m["rules_active"])." {pps_active}";
        if($m["rules_disabled"]>0){$txt.=" &middot; ".pps_n($m["rules_disabled"])." {pps_disabled}";}
        $chips[]=pps_chip("fa-solid fa-list-check",$txt);
    }
    $h="<section class='pps-hero'>";
    $h.="<div class='pps-hero-ico'><i class='fa-solid fa-sitemap'></i></div>";
    $h.="<div class='pps-hero-body'>";
    $h.="<h2>{pps_hero_title}</h2>";
    $h.="<p>{pps_hero_text} ".($lbOn?"{pps_hero_text_lb_on}":"{pps_hero_text_lb_off}")."</p>";
    $h.="<div class='pps-chips'>".@implode("",$chips)."</div>";
    $h.="</div></section>";
    return $h;
}

function pps_chip(string $ico,string $text,string $cls=""):string{
    return "<span class='pps-chip $cls'><i class='$ico'></i><span>$text</span></span>";
}

function pps_alerts(array $m):string{
    $a=array();
    // Sans aucun parent affiche, l'etat vide porte deja ce message.
    if(!$m["peers_ok"] && $m["total"]>0){$a[]=pps_alert_daemon();}
    if($m["direct"]){$a[]=pps_alert_direct();}
    if(!$m["direct"] && $m["peers_ok"] && $m["rules_active"]>0 && $m["total"]==0){$a[]=pps_alert_unapplied();}
    if($m["down"]>0){$a[]=pps_alert_down($m);}
    if(count($a)==0){return "";}
    return "<div class='pps-alerts'>".@implode("",$a)."</div>";
}

function pps_alert(string $kind,string $ico,string $title,string $text,string $btn):string{
    $h="<div class='pps-alert pps-alert-$kind' role='alert'>";
    $h.="<span class='pps-alert-ico'><i class='$ico'></i></span>";
    $h.="<div class='pps-alert-body'><strong>$title</strong><span>$text</span></div>";
    if($btn<>""){$h.="<div class='pps-alert-act'>$btn</div>";}
    $h.="</div>";
    return $h;
}

function pps_btn(string $js,string $ico,string $label,string $cls,bool $allowed=true):string{
    if(!$allowed){
        $why=htmlspecialchars(pps_plain("{pps_admin_only}"),ENT_QUOTES);
        return "<button type='button' class='pps-btn $cls' disabled title=\"$why\"><i class='$ico'></i> $label</button>";
    }
    return "<button type='button' class='pps-btn $cls' OnClick=\"$js\"><i class='$ico'></i> $label</button>";
}

function pps_alert_daemon():string{
    return pps_alert("err","fa-solid fa-plug-circle-xmark","{pps_al_daemon_t}","{pps_al_daemon}",
        pps_btn("PpsReload(true);","fa-solid fa-rotate","{refresh}","pps-btn-ghost"));
}

function pps_alert_direct():string{
    $page=CurrentPageName();
    return pps_alert("warn","fa-solid fa-triangle-exclamation","{pps_al_direct_t}","{pps_al_direct}",
        pps_btn("Loadjs('$page?direct-js=yes');","fa-solid fa-link","{reconnect_to_parent_servers}","pps-btn-warn",isRights()));
}

function pps_alert_unapplied():string{
    return pps_alert("warn","fa-solid fa-hourglass-half","{pps_al_unapplied_t}","{pps_al_unapplied}",
        pps_btn(pps_apply_js(),"fa-solid fa-floppy-disk","{apply_parameters}","pps-btn-warn",isRights()));
}

function pps_alert_down(array $m):string{
    $title=pps_n($m["down"])." / ".pps_n($m["total"])." {pps_al_down_t}";
    $btn="";
    if($m["total"]>1){$btn=pps_btn("PpsFilter('down');","fa-solid fa-filter","{pps_show_down}","pps-btn-err");}
    return pps_alert("err","fa-solid fa-circle-xmark",$title,"{pps_al_down}",$btn);
}

function pps_strip(array $m):string{
    $h="<div class='pps-strip'>";
    $h.=pps_tile_answering($m);
    $h.=pps_tile_fetches($m);
    $h.=pps_tile_conns($m);
    $h.=pps_tile_mode($m);
    $h.="</div>";
    return $h;
}

function pps_tile(string $label,string $value,string $sub,string $extra=""):string{
    return "<div class='pps-tile'><div class='pps-tile-txt'><span class='pps-tile-lbl'>$label</span>"
        ."<span class='pps-tile-val'>$value</span><span class='pps-tile-sub'>$sub</span></div>$extra</div>";
}

function pps_tile_answering(array $m):string{
    if(!$m["peers_ok"] || $m["total"]==0){
        return pps_tile("{pps_t_answering}","&mdash;","{pps_t_answering_sub}");
    }
    $pct=intval(round($m["up"]*100/$m["total"]));
    $col=$m["down"]>0?"#dc2626":"var(--pp-accent)";
    $c=87.96; // 2*pi*14
    $off=round($c*(100-$pct)/100,2);
    $svg="<div class='pps-donut' role='img' aria-label='$pct%'><svg viewBox='0 0 36 36' width='56' height='56'>"
        ."<circle cx='18' cy='18' r='14' fill='none' stroke='#e2e8f0' stroke-width='3.5'></circle>"
        ."<circle cx='18' cy='18' r='14' fill='none' stroke='$col' stroke-width='3.5' stroke-dasharray='$c' stroke-dashoffset='$off' stroke-linecap='round' transform='rotate(-90 18 18)'></circle>"
        ."</svg><span>$pct%</span></div>";
    return pps_tile("{pps_t_answering}",pps_n($m["up"])." / ".pps_n($m["total"]),"{pps_t_answering_sub}",$svg);
}

function pps_tile_fetches(array $m):string{
    $v=($m["peers_ok"] && $m["total"]>0)?pps_n($m["fetches"]):"&mdash;";
    return pps_tile("{pps_t_fetches}",$v,"{pps_t_fetches_sub}");
}

function pps_tile_conns(array $m):string{
    $v=($m["peers_ok"] && $m["total"]>0)?pps_n($m["conns"]):"&mdash;";
    return pps_tile("{pps_t_conns}",$v,"{pps_t_conns_sub}");
}

function pps_tile_mode(array $m):string{
    if(!$m["peers_ok"] && !$m["direct"]){
        $v="&mdash;";
    }elseif($m["direct"]){
        $v="<span class='pps-dot pps-dot-warn'></span>{pps_mode_direct}";
    }elseif($m["total"]>0){
        $v="<span class='pps-dot pps-dot-ok'></span>{pps_mode_parents}";
    }else{
        $v="<span class='pps-dot'></span>{pps_mode_none}";
    }
    return pps_tile("{pps_t_mode}","<span class='pps-mode'>$v</span>","{pps_t_mode_sub}");
}

function pps_peers(array $m):string{
    if($m["total"]==0){
        return pps_empty($m);
    }
    $h="<div class='pps-count'><span class='pps-count-txt'><b>".pps_n($m["total"])."</b> {pps_parents_count}";
    $h.=" <i class='pps-sep'>&bull;</i> <span class='pps-ok'>".pps_n($m["up"])." {pps_answering}</span>";
    if($m["down"]>0){
        $h.=" <i class='pps-sep'>&bull;</i> <span class='pps-ko'>".pps_n($m["down"])." {pps_not_answering}</span>";
    }
    $h.="</span>";
    if($m["down"]>0 && $m["total"]>1){
        $h.="<span class='pps-seg' role='group'>";
        $h.="<button type='button' data-f='all' OnClick=\"PpsFilter('all');\">{pps_filter_all} (".pps_n($m["total"]).")</button>";
        $h.="<button type='button' data-f='down' OnClick=\"PpsFilter('down');\">{pps_filter_down} (".pps_n($m["down"]).")</button>";
        $h.="</span>";
    }
    $h.="</div>";
    $h.="<div class='pps-grid'>";
    foreach($m["peers"] as $p){$h.=pps_peer_card($p);}
    $h.="</div>";
    return $h;
}

function pps_peer_card(array $p):string{
    $up=$p["up"];
    $h="<div class='pps-card".($up?"":" pps-card-ko")."' data-up='".($up?"1":"0")."'>";
    $h.="<div class='pps-card-head'>";
    if($up){
        $h.="<span class='pps-pill pps-pill-ok'><i class='fa-solid fa-circle-check'></i> {pps_st_up}</span>";
    }else{
        $h.="<span class='pps-pill pps-pill-ko'><i class='fa-solid fa-circle-xmark'></i> {pps_st_down}</span>";
    }
    $h.="<span class='pps-addr' title=\"".htmlspecialchars($p["hostport"],ENT_QUOTES)."\">".htmlspecialchars($p["hostport"])."</span>";
    $h.="</div>";
    if($p["lb"]){
        $h.="<div class='pps-name'><i class='fa-solid fa-scale-balanced'></i><span>{pps_lb_peer}</span></div>";
        $h.="<div class='pps-name-sub'>{pps_lb_peer_sub}</div>";
    }else{
        $h.="<div class='pps-name'><i class='fa-solid fa-server'></i><span title=\"".htmlspecialchars($p["name"],ENT_QUOTES)."\">".htmlspecialchars($p["name"])."</span></div>";
        $h.="<div class='pps-name-sub'>{pps_conf_name}</div>";
    }
    $h.="<div class='pps-metrics'>";
    $h.=pps_metric("{pps_m_fetches}","fetches",pps_n($p["fetches"]),"{pps_m_fetches_help}");
    $h.=pps_metric("{pps_m_conns}","open conns",pps_n($p["conns"]),"{pps_m_conns_help}");
    $h.="</div>";
    if(!$up){$h.=pps_peer_why($p);}
    $h.="</div>";
    return $h;
}

function pps_metric(string $label,string $tech,string $value,string $help):string{
    return "<div class='pps-metric'><span class='pps-metric-lbl'>$label <em>$tech</em></span>"
        ."<span class='pps-metric-val'>$value</span><span class='pps-metric-help'>$help</span></div>";
}

function pps_peer_why(array $p):string{
    $h="<div class='pps-why'><strong><i class='fa-solid fa-triangle-exclamation'></i> {pps_why}</strong>";
    if($p["telnet"]<>""){
        $h.="<span>{pps_why_tcp}</span>";
        $h.="<span class='pps-raw'>".htmlspecialchars($p["telnet"])."</span>";
    }else{
        $h.="<span>{pps_why_squid}</span>";
    }
    $h.="</div>";
    return $h;
}

function pps_empty(array $m):string{
    $page=CurrentPageName();
    $adm=isRights();
    $wiki=pps_btn("s_PopUpFull('https://wiki.articatech.com/en/proxy-service/parents',1024,768,'Wiki');","fa-solid fa-book","Wiki","pps-btn-ghost");
    if(!$m["peers_ok"]){
        $ico="fa-solid fa-plug-circle-xmark";$t="{pps_al_daemon_t}";$x="{pps_al_daemon}";
        $btn=pps_btn("PpsReload(true);","fa-solid fa-rotate","{refresh}","pps-btn-main");
    }elseif($m["direct"]){
        $ico="fa-solid fa-link-slash";$t="{pps_empty_direct_t}";$x="{pps_empty_direct}";
        $btn=pps_btn("Loadjs('$page?direct-js=yes');","fa-solid fa-link","{reconnect_to_parent_servers}","pps-btn-main",$adm);
    }elseif($m["rules_active"]>0){
        $ico="fa-solid fa-hourglass-half";$t="{pps_empty_unapplied_t}";$x="{pps_empty_unapplied}";
        $btn=pps_btn(pps_apply_js(),"fa-solid fa-floppy-disk","{apply_parameters}","pps-btn-main",$adm);
    }else{
        $ico="fa-solid fa-sitemap";$t="{pps_empty_t}";$x="{pps_empty}";
        $btn=pps_btn("document.location.href='/proxy-parents-rules';","fa-solid fa-plus","{pps_create_rule}","pps-btn-main",$adm);
    }
    return "<div class='pps-empty'><i class='$ico'></i><h3>$t</h3><p>$x</p><div class='pps-empty-act'>$btn $wiki</div></div>";
}

function pps_side_head(string $ico,string $title,string $pill=""):string{
    return "<div class='pps-side-head'><span><i class='$ico'></i> $title</span>$pill</div>";
}

function pps_lb_card(array $m):string{
    $lb=$m["lb"];
    $h="<div class='pps-side-card'>";
    if(!$lb["ok"]){
        $h.=pps_side_head("fa-solid fa-scale-balanced","{pps_lb_title}","<span class='pps-pill pps-pill-ko'><i class='fa-solid fa-circle-question'></i> {pps_unknown}</span>");
        $h.="<p>{pps_lb_err}</p>";
        if($lb["error"]<>""){$h.="<span class='pps-raw'>".htmlspecialchars($lb["error"])."</span>";}
        return $h."</div>";
    }
    if(!$lb["enabled"]){
        $h.=pps_side_head("fa-solid fa-scale-balanced","{pps_lb_title}","<span class='pps-pill pps-pill-off'><i class='fa-solid fa-circle-minus'></i> {pps_lb_off}</span>");
        $h.="<p>{pps_lb_text}</p><p class='pps-muted'>{pps_lb_off_text}</p>";
        $h.="<a class='pps-link' href='#' OnClick=\"s_PopUpFull('https://wiki.articatech.com/proxy-service/parents/parents-lb','1024','900');return false;\"><i class='fa-solid fa-book'></i> Wiki</a>";
        return $h."</div>";
    }
    if($lb["running"]){
        $pill="<span class='pps-pill pps-pill-ok'><i class='fa-solid fa-circle-check'></i> {pps_lb_running}</span>";
    }else{
        $pill="<span class='pps-pill pps-pill-ko'><i class='fa-solid fa-circle-stop'></i> {pps_lb_stopped}</span>";
    }
    $h.=pps_side_head("fa-solid fa-scale-balanced","{pps_lb_title}",$pill);
    $h.="<p>{pps_lb_text}</p>";
    if($lb["running"]){
        $facts=array();
        if($lb["memory"]>0){$facts[]="{pps_lb_memory} ".FormatBytes($lb["memory"],true);}
        $up=pps_uptime($lb["uptime"]);
        if($up<>""){$facts[]="{pps_lb_since} $up";}
        $facts[]="{pps_lb_used_by}: ".pps_n($m["rules_lb"]);
        $h.="<div class='pps-facts'><i class='fa-solid fa-microchip'></i> ".@implode(" &middot; ",$facts)."</div>";
    }else{
        $h.="<p class='pps-ko-txt'>{pps_lb_stopped_text}</p>";
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsRestart=$tpl->framework_buildjs("/proxy/parentlb/restart","parentlb.progress","parentlb.log",
        "progress-squid-parent-restart","PpsReload(true);");
    $h.=pps_btn($jsRestart,"fa-solid fa-rotate","{pps_lb_restart}","pps-btn-ghost pps-btn-block",isRights());
    return $h."</div>";
}

function pps_uptime(string $u):string{
    if($u==""){return "";}
    $units=array("year"=>"y","month"=>"mo","week"=>"w","day"=>"d","hour"=>"h","min"=>"min","sec"=>"s");
    $out=array();
    if(preg_match_all('#([0-9]+)\s*([a-zA-Z]+)#',$u,$re,PREG_SET_ORDER)){
        foreach($re as $x){
            $unit=strtolower($x[2]);
            foreach($units as $needle=>$abbr){
                if(strpos($unit,$needle)===0){$out[]=$x[1]." ".$abbr;break;}
            }
            if(count($out)>=2){break;}
        }
    }
    if(count($out)==0){return htmlspecialchars($u);}
    return @implode(" ",$out);
}

function pps_direct_card(array $m):string{
    $page=CurrentPageName();
    $adm=isRights();
    $h="<div class='pps-side-card".($m["direct"]?" pps-side-warn":"")."'>";
    if($m["direct"]){
        $h.=pps_side_head("fa-solid fa-link-slash","{pps_direct_title}","<span class='pps-pill pps-pill-warn'><i class='fa-solid fa-triangle-exclamation'></i> {pps_on}</span>");
        $h.="<p>{pps_direct_on_text}</p>";
        $h.=pps_btn("Loadjs('$page?direct-js=yes');","fa-solid fa-link","{reconnect_to_parent_servers}","pps-btn-warn pps-btn-block",$adm);
        return $h."</div>";
    }
    $h.=pps_side_head("fa-solid fa-link-slash","{pps_direct_title}");
    $h.="<p>{pps_direct_text}</p>";
    $h.=pps_btn("Loadjs('$page?direct-js=yes');","fa-solid fa-link-slash","{temporarily_switch_to_direct_mode}","pps-btn-dark pps-btn-block",$adm);
    $h.="<div class='pps-note'><i class='fa-solid fa-triangle-exclamation'></i><span><b>{pps_direct_warn_t}</b> {pps_direct_warn}</span></div>";
    return $h."</div>";
}

function pps_danger_card():string{
    $page=CurrentPageName();
    $h="<details class='pps-danger' id='pps-danger' ontoggle=\"window.PpsDangerOpen=this.open;\">";
    $h.="<summary><span><i class='fa-solid fa-skull-crossbones'></i> {pps_danger}</span><span class='pps-show'>{pps_show} <i class='fa-solid fa-chevron-down'></i></span></summary>";
    $h.="<div class='pps-danger-body'><p>{pps_danger_text}</p>";
    $h.=pps_btn("Loadjs('$page?uninstall-js=yes');","fa-solid fa-trash","{remove_this_section}","pps-btn-danger pps-btn-block",isRights());
    $h.="</div></details>";
    return $h;
}

// Texte brut d'un token (attributs title=) : [br] et entites hors attribut.
function pps_plain(string $s):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body($s);
    $t=preg_replace('#<br\s*/?>#i'," ",$t);
    return trim(html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8"));
}

// Bascule du mode direct : confirmation avant de court-circuiter les parents,
// retour direct sans confirmation (sans risque).
function pps_direct_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isRights()){return $tpl->js_no_privileges();}
    $direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableInstantParentProxy"))==1;
    if(!$direct){
        $tpl->js_dialog_confirm_action("{pps_direct_confirm}","pps-direct","1","if(typeof PpsReload==='function'){PpsReload(true);}");
        return true;
    }
    header("content-type: application/x-javascript");
    echo "$.post('$page',{'pps-direct':'0'},function(r){r=String(r||'').trim();if(r.length>0){alert(r);}if(typeof PpsReload==='function'){PpsReload(true);}});";
    return true;
}

function pps_direct_save():bool{
    $tpl=new template_admin();
    if(!isRights()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $val=intval($_POST["pps-direct"])==1?1:0;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableInstantParentProxy",$val);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile"));
    admin_tracks("Proxy parents: ".($val==1?"switch to direct mode (parents bypassed)":"reconnect to parent servers"));
    if(is_object($json) && isset($json->Status) && !$json->Status){
        echo isset($json->Error)?(string)$json->Error:"/proxy/parents/compile failed";
    }
    return true;
}

function pps_js(string $page):string{
    return "
window.PpsFilterV=window.PpsFilterV||'all';
function PpsFilter(v){
\twindow.PpsFilterV=v;
\tvar c=document.querySelectorAll('#pps-main .pps-card');
\tvar hasDown=document.querySelectorAll('#pps-main .pps-card[data-up=\"0\"]').length>0;
\tif(!hasDown){window.PpsFilterV='all';}
\tfor(var i=0;i<c.length;i++){ c[i].style.display=(window.PpsFilterV==='down' && c[i].getAttribute('data-up')==='1')?'none':''; }
\tvar b=document.querySelectorAll('#pps-main .pps-seg button');
\tfor(var j=0;j<b.length;j++){ var on=b[j].getAttribute('data-f')===window.PpsFilterV; b[j].classList.toggle('on',on); b[j].setAttribute('aria-pressed',on?'true':'false'); }
}
function PpsApply(){
\tPpsFilter(window.PpsFilterV||'all');
\tvar d=document.getElementById('pps-danger'); if(d && window.PpsDangerOpen){ d.open=true; }
}
function PpsBusy(){
\tif(typeof ModalIsOpenOrOpening==='function' && ModalIsOpenOrOpening()){return true;}
\tvar s=document.querySelector('.sweet-alert.visible'); return !!s;
}
function PpsReload(force){
\tif(!document.getElementById('pps-main')){ if(window.PpsTimer){clearInterval(window.PpsTimer);window.PpsTimer=null;} return; }
\tif(!force && PpsBusy()){return;}
\tLoadAjaxSilent('pps-main','$page?main-host=yes');
}
if(window.PpsTimer){clearInterval(window.PpsTimer);}
window.PpsTimer=setInterval(function(){PpsReload(false);},10000);";
}

function pps_style():string{
    $accent="#10b981";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    $mono="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
    $c=array();
    $c[]=".pps{--pp-accent:$accent;--pp-soft:$soft;margin-top:10px;color:#0f172a;font-size:14px}";
    $c[]=".pps #pps-main{display:flex;flex-direction:column;gap:16px}";
    $c[]=".pps *{box-sizing:border-box}";
    // Bandeau
    $c[]=".pps-hero{display:flex;gap:18px;background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--pp-accent);border-radius:12px;padding:20px 24px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".pps-hero-ico{flex:0 0 52px;height:52px;border-radius:12px;background:var(--pp-soft);color:var(--pp-accent);display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".pps-hero-body{min-width:0;flex:1}";
    $c[]=".pps-hero h2{margin:0 0 6px;font-size:20px;font-weight:600;color:#0f172a}";
    $c[]=".pps-hero p{margin:0;color:#475569;font-size:14px;line-height:1.55;max-width:980px}";
    $c[]=".pps-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".pps-chip{display:inline-flex;align-items:center;gap:7px;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;border-radius:999px;padding:5px 12px;font-size:13px}";
    $c[]=".pps-chip i{color:#64748b}";
    $c[]=".pps-chip-on{background:var(--pp-soft);border-color:transparent;color:#065f46}.pps-chip-on i{color:var(--pp-accent)}";
    // Alertes
    $c[]=".pps-alerts{display:flex;flex-direction:column;gap:8px}";
    $c[]=".pps-alert{display:flex;align-items:center;gap:14px;border-radius:12px;padding:14px 18px;border:1px solid}";
    $c[]=".pps-alert-ico{flex:0 0 34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;background:#fff}";
    $c[]=".pps-alert-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;font-size:13.5px;line-height:1.5}";
    $c[]=".pps-alert-body strong{font-size:14px}";
    $c[]=".pps-alert-act{flex:0 0 auto}";
    $c[]=".pps-alert-err{background:#fef2f2;border-color:#fca5a5;color:#991b1b}.pps-alert-err .pps-alert-ico{color:#dc2626}";
    $c[]=".pps-alert-warn{background:#fffbeb;border-color:#fcd34d;color:#92400e}.pps-alert-warn .pps-alert-ico{color:#d97706}";
    // Boutons
    $c[]=".pps-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:8px;padding:8px 14px;font-size:13.5px;font-weight:600;border:1px solid transparent;cursor:pointer;line-height:1.2;white-space:nowrap}";
    $c[]=".pps-btn:focus-visible{outline:2px solid var(--pp-accent);outline-offset:2px}";
    $c[]=".pps-btn[disabled]{opacity:.55;cursor:not-allowed}";
    $c[]=".pps-btn-block{width:100%;margin-top:12px}";
    $c[]=".pps-btn-main{background:var(--pp-accent);color:#fff}";
    $c[]=".pps-btn-ghost{background:#f8fafc;border-color:#e2e8f0;color:#334155}";
    $c[]=".pps-btn-dark{background:#1e293b;color:#fff}";
    $c[]=".pps-btn-warn{background:#fff;border-color:#f59e0b;color:#92400e}";
    $c[]=".pps-btn-err{background:#fff;border-color:#f87171;color:#b91c1c}";
    $c[]=".pps-btn-danger{background:#fff;border-color:#ef4444;color:#b91c1c}";
    // Tuiles
    $c[]=".pps-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}";
    $c[]=".pps-tile{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;min-height:104px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".pps-tile-txt{display:flex;flex-direction:column;gap:4px;min-width:0}";
    $c[]=".pps-tile-lbl{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}";
    $c[]=".pps-tile-val{font-size:24px;font-weight:700;font-variant-numeric:tabular-nums;color:#0f172a}";
    $c[]=".pps-tile-sub{font-size:13px;color:#64748b}";
    $c[]=".pps-donut{position:relative;flex:0 0 56px;width:56px;height:56px}";
    $c[]=".pps-donut span{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#334155}";
    $c[]=".pps-mode{display:inline-flex;align-items:center;gap:8px;font-size:20px}";
    $c[]=".pps-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;display:inline-block}.pps-dot-ok{background:var(--pp-accent)}.pps-dot-warn{background:#f59e0b}";
    // Colonnes
    $c[]=".pps-cols{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:16px;align-items:start}";
    $c[]=".pps-left{min-width:0}";
    $c[]=".pps-side{display:flex;flex-direction:column;gap:16px}";
    $c[]=".pps-count{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #e2e8f0;font-size:14px;color:#334155}";
    $c[]=".pps-sep{font-style:normal;color:#cbd5e1;margin:0 4px}.pps-ok{color:#047857;font-weight:600}.pps-ko{color:#b91c1c;font-weight:600}";
    $c[]=".pps-seg{display:inline-flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fff}";
    $c[]=".pps-seg button{border:0;background:#fff;padding:6px 12px;font-size:13px;color:#475569;cursor:pointer}";
    $c[]=".pps-seg button+button{border-left:1px solid #e2e8f0}";
    $c[]=".pps-seg button.on{background:#0f172a;color:#fff}";
    $c[]=".pps-seg button:focus-visible{outline:2px solid var(--pp-accent);outline-offset:-2px}";
    // Cartes parent
    $c[]=".pps-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}";
    $c[]=".pps-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:6px;min-width:0;box-shadow:0 1px 2px rgba(15,23,42,.06);animation:ppsIn .15s ease-out}";
    $c[]=".pps-card-ko{border-color:#fca5a5;box-shadow:0 0 0 1px #fee2e2}";
    $c[]=".pps-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:6px}";
    $c[]=".pps-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600;white-space:nowrap}";
    $c[]=".pps-pill-ok{background:#d1fae5;color:#047857}.pps-pill-ko{background:#fee2e2;color:#b91c1c}.pps-pill-off{background:#f1f5f9;color:#475569}.pps-pill-warn{background:#fef3c7;color:#92400e}";
    $c[]=".pps-addr{font-family:$mono;font-size:14px;font-weight:600;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:3px 9px;color:#0f172a;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".pps-card-ko .pps-addr{background:#fff;border-color:#fca5a5}";
    $c[]=".pps-name{display:flex;align-items:center;gap:8px;font-size:16px;font-weight:600;min-width:0}";
    $c[]=".pps-name span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".pps-name i{color:#94a3b8}.pps-card-ko .pps-name i{color:#ef4444}";
    $c[]=".pps-name-sub{font-size:13px;color:#64748b}";
    $c[]=".pps-metrics{display:grid;grid-template-columns:1fr 1fr;gap:12px;background:#f8fafc;border-radius:10px;padding:12px 14px;margin-top:10px}";
    $c[]=".pps-metric{display:flex;flex-direction:column;gap:2px;min-width:0}";
    $c[]=".pps-metric-lbl{font-size:13px;color:#475569;font-weight:600}.pps-metric-lbl em{display:block;font-style:normal;font-weight:400;color:#94a3b8;font-size:12px}";
    $c[]=".pps-metric-val{font-size:20px;font-weight:700;font-variant-numeric:tabular-nums}";
    $c[]=".pps-card-ko .pps-metric-val{color:#94a3b8}";
    $c[]=".pps-metric-help{font-size:13px;color:#64748b}";
    $c[]=".pps-why{margin-top:10px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:12px 14px;display:flex;flex-direction:column;gap:6px;color:#991b1b;font-size:13.5px;line-height:1.5}";
    $c[]=".pps-why strong{font-size:13px;text-transform:uppercase;letter-spacing:.04em}";
    $c[]=".pps-raw{display:block;font-family:$mono;font-size:12px;color:#475569;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:6px 8px;word-break:break-all;white-space:normal}";
    // Etat vide
    $c[]=".pps-empty{background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:36px 24px;text-align:center;color:#475569}";
    $c[]=".pps-empty>i{font-size:38px;color:var(--pp-accent);margin-bottom:10px}";
    $c[]=".pps-empty h3{margin:6px 0 8px;font-size:18px;font-weight:600;color:#0f172a}";
    $c[]=".pps-empty p{margin:0 auto 16px;max-width:560px;line-height:1.55}";
    $c[]=".pps-empty-act{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}";
    // Colonne laterale
    $c[]=".pps-side-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".pps-side-warn{border-color:#fcd34d}";
    $c[]=".pps-side-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid #f1f5f9;font-size:16px;font-weight:600}";
    $c[]=".pps-side-head>span:first-child i{color:var(--pp-accent);margin-right:6px}";
    $c[]=".pps-side-card p{margin:0 0 8px;color:#475569;font-size:13.5px;line-height:1.55}";
    $c[]=".pps-muted{color:#64748b!important}.pps-ko-txt{color:#b91c1c!important}";
    $c[]=".pps-facts{background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:9px 12px;font-size:13.5px;color:#334155}.pps-facts i{color:#94a3b8;margin-right:4px}";
    $c[]=".pps-link{font-size:13.5px;font-weight:600;color:var(--pp-accent)}";
    $c[]=".pps-note{display:flex;gap:9px;margin-top:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;font-size:13px;color:#92400e;line-height:1.5}.pps-note i{color:#d97706;margin-top:3px}";
    $c[]=".pps-danger{background:#fff;border:1px solid #fecaca;border-radius:12px;padding:0}";
    $c[]=".pps-danger summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:14px 20px;font-weight:600;color:#b91c1c}";
    $c[]=".pps-danger summary::-webkit-details-marker{display:none}";
    $c[]=".pps-danger summary:focus-visible{outline:2px solid #ef4444;outline-offset:-2px;border-radius:12px}";
    $c[]=".pps-show{font-weight:500;color:#64748b;font-size:13px}";
    $c[]=".pps-danger[open] .pps-show i{transform:rotate(180deg)}";
    $c[]=".pps-danger-body{padding:0 20px 18px}.pps-danger-body p{margin:0;color:#475569;font-size:13.5px;line-height:1.55}";
    $c[]="@keyframes ppsIn{from{opacity:0}to{opacity:1}}";
    $c[]="@media (prefers-reduced-motion:reduce){.pps-card{animation:none}}";
    $c[]="@media (max-width:1200px){.pps-cols{grid-template-columns:minmax(0,1fr)}.pps-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.pps-grid{grid-template-columns:minmax(0,1fr)}}";
    return "<style>".@implode("\n",$c)."</style>";
}
