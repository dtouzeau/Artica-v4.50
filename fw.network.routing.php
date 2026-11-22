<?php
// Routage : coquille a onglets du moteur de routage d'articarest (spec 032 lot 5).
// Moteur actif (etat actif ET migration "active") : un onglet par page du moteur.
// Moteur inactif (ou basculement inacheve) : la migration et l'ancienne page (fw.network.routing.legacy.php).
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.routing.ui.inc");
include_once(dirname(__FILE__)."/ressources/class.routing.watch.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["watch-banner"])){watch_banner();exit;}
if(isset($_GET["multipath-js"])){multipath_js();exit;}
if(isset($_GET["multipath-popup"])){multipath_popup();exit;}
page();

function page(){
    $tpl=new template_admin();
    $html=$tpl->page_header("{routing_rules}","fa fa-road","{routing_engine_explain}",
        "fw.network.routing.php?build-page=yes","routing","progress-iproute-restart",false,"routing-shell");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{routing_rules}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function rtshell_tiny($tpl,bool $networking,bool $active):string{
    // Appliquer, Multipath (onglet Multipath de l'interface de la route par defaut), Actualiser : rtui_shell_buttons
    $topbuttons=rtui_shell_buttons($networking,$active);

    $TINY_ARRAY=rtui_tiny_array($tpl->table_buttons($topbuttons,"AsSystemAdministrator"),$active);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function build_page(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
        return;
    }
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    if($DisableNetworking==1){
        echo $tpl->FATAL_ERROR_SHOW_128("{DisableNetworking_explain}");
        echo "<script>".rtshell_tiny($tpl,false,false)."</script>";
        return;
    }

    $engine=rtui_load_engine();
    $mode=rtui_engine_mode($engine["state"],$engine["migration"]);
    $active=($mode=="active");
    $notices=rtui_shell_notices($mode,$engine["state"],$engine["migration"]);

    $html=array();
    foreach($notices["error"] as $t){ $html[]=$tpl->div_error($t); }
    foreach($notices["warning"] as $t){ $html[]=$tpl->div_warning($t); }
    foreach($notices["info"] as $t){ $html[]=$tpl->div_explain($t); }

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    // fenetre de surveillance : hote du bandeau au-dessus des onglets (moteur actif seulement), recharge toutes les 15 s
    // par le fragment watch-banner, pour suivre les ecritures des onglets qui ne rechargent que leur onglet
    if($active){
        echo rtwat_banner_host_js();
    }
    // tabs_default rend un HTML deja traduit ; la TINY vient apres les onglets (recette, section 3b).
    echo $tpl->tabs_default(rtui_shell_tabs($active));
    echo "<script>".rtshell_tiny($tpl,true,$active)."</script>";
}

/**
 * Fragment du bandeau de la fenetre de surveillance (hote rtwat-banner-host de la coquille, recharge toutes les 15 s).
 * Sortie vide sans droits, sous DisableNetworking, moteur non actif, ou sans fenetre ouverte (503 compris).
 */
function watch_banner(){
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){return;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"))==1){return;}
    $engine=rtui_load_engine();
    if(rtui_engine_mode($engine["state"],$engine["migration"])!=="active"){return;}
    $tpl=new template_admin();
    $banner=rtwat_banner_html(rtapi_get("/system/routing/api/watch"),time(),$tpl);
    if($banner===""){return;}
    echo rtwat_banner_css().$banner.rtwat_countdown_js();
}

/** Bouton Multipath : l'onglet Multipath de l'interface de la route par defaut, lue dans le noyau (lecture seule). */
function multipath_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_error("{ERROR_NO_PRIVS}");return;}
    $k=rtapi_get("/system/routing/api/kernel");
    $ifaces=rtui_multipath_ifaces(($k["ok"] && is_array($k["data"]))?$k["data"]:array());
    $url=rtui_multipath_url($ifaces);
    if($url===""){echo "LoadAjax('MainContent','fw.network.interfaces.php');";return;}
    $title=count($ifaces)==1?"{routing_multipath}: ".rtapi_h($ifaces[0]):"{routing_multipath}";
    $tpl->js_dialog1($title,$url,1024);
}

/** Plusieurs interfaces portent la route par defaut : un bouton par interface, l'onglet Multipath s'ouvre dessous. */
function multipath_popup(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");return;}
    $k=rtapi_get("/system/routing/api/kernel");
    $ifaces=rtui_multipath_ifaces(($k["ok"] && is_array($k["data"]))?$k["data"]:array());
    $b=array();
    foreach($ifaces as $i){
        $u=rawurlencode($i);
        $b[]="<button type='button' class='btn btn-default btn-sm' OnClick=\"LoadAjax('rtmp-host','fw.network.interfaces.php?multipath-section=$u');\">".rtapi_h($i)."</button>";
    }
    if(count($b)==0){$b[]="<div class='awc-empty'>{no_data}</div>";}
    echo $tpl->_ENGINE_parse_body("<div style='margin:10px 0;display:flex;gap:8px;flex-wrap:wrap'>".implode("",$b)."</div><div id='rtmp-host'></div>");
}
