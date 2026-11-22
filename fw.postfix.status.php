<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["restart-postfix-3"])){restart_postfix_3();exit;}
if(isset($_GET["restart-postfix-2"])){restart_postfix_2();exit;}
if(isset($_GET["restart-postfix-popup"])){restart_postfix_1();exit;}
if(isset($_GET["restart-postfix-js"])){restart_postfix_js();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats-mailqueue"])){stats_mailqueue();exit;}
if(isset($_GET["stats-mailstats"])){stats_mailstats();exit;}
if(isset($_GET["stats-mailvolume"])){stats_mailvolume();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_POST["uninstall"])){uninstall_confirm();exit;}

if(isset($_GET["right-status"])){right_status();exit;}
if(isset($_GET["socks-connection"])){sock_connection();exit;}
if(isset($_GET["instance-reinstall-js"])){reinstall_instance();exit;}
if(isset($_POST["instance-reinstall"])){reinstall_instance_confirm();exit;}
if(isset($_GET["multi-feature-disable"])){multi_feature_uninstall();exit;}
if(isset($_POST["multi-feature-disable"])){multi_feature_uninstall_confirm();exit;}

if(isset($_GET["multi-feature-enable"])){multi_feature_install();exit;}
if(isset($_POST["multi-feature-enable"])){multi_feature_install_confirm();exit;}

if(isset($_GET["multi-rest-restart"])){multi_rest_restart();exit;}
if(isset($_GET["multi-rest-reconfigure-single"])){multi_rest_reconfigure_single();exit;}
if(isset($_GET["multi-rest-install"])){multi_rest_install();exit;}
if(isset($_GET["multi-rest-uninstall"])){multi_rest_uninstall();exit;}
if(isset($_GET["left-postfix-status"])){left_postfix_status();exit;}
if(isset($_GET["right-top-status"])){top_status();exit;}
if(isset($_GET["reputation-js"])){reputation_js();exit;}
if(isset($_GET["reputation-popup"])){reputation_popup();exit;}
if(isset($_POST["ArticaReputationWhite"])){reputation_save();exit;}
page();

function reinstall_instance():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-reinstall-js"]);
    $instancename=get_instance_name($instance_id);

    $log="Reinstall the SMTP instance $instancename ($instance_id)";
    $restart=$tpl->framework_buildjs(
        "postfix2.php?reinstall-instance=$instance_id",
        "postfix-multi.$instance_id.reinstall.progress",
        "postfix-multi.$instance_id.reinstall.log","progress-postfix-restart"
    );


    $tpl->js_dialog_confirm_action("$instancename:{reinstall_instance}","instance-reinstall",$log,$restart);
    return true;
}
function uninstall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uninstall=$tpl->framework_buildjs(
        "/smtp-manager/uninstall",
        "postfix.install",
        "postfix.install",
        "progress-postfix-restart",
        "document.location.href='/index'");

    return $tpl->js_confirm_execute("{uninstall_feature} > > {APP_POSTFIX}","uninstall",1,$uninstall);

}
function uninstall_confirm():bool{
    return admin_tracks("Important: Confirm Uninstall the whole SMTP feature");
}

function restart_postfix_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance=intval($_GET["restart-postfix-js"]);
    return $tpl->js_dialog_modal("{restarting_service}","$page?restart-postfix-popup=$instance");

}
function restart_postfix_1():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance=intval($_GET["restart-postfix-popup"]);
    $html[]="<H1 id='h1_restart_postfix_1'>{stopping_service}</H1>";
    $html[]="<div id='restart_postfix_1' style='margin:30px'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('restart_postfix_1','$page?restart-postfix-2=$instance');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restart_postfix_2():bool{
    $page=CurrentPageName();
    $instance=intval($_GET["restart-postfix-2"]);
    $sock=new sockets();
    $data=$sock->REST_API_POSTFIX("/postfix/master/stop/$instance");
    $tpl=new template_admin();
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $html[]=$tpl->widget_rouge("{error}",json_last_error_msg());
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(!$json->Status){
        $html[]=$tpl->widget_rouge("{error}","{failed}");
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $title=base64_encode($tpl->_ENGINE_parse_body("{starting_service}"));
    $html[]="<script>";
    $html[]="var tempvalue='$title';";
    $html[]="document.getElementById('h1_restart_postfix_1').innerHTML=base64_decode(tempvalue);";
    $html[]="LoadAjax('restart_postfix_1','$page?restart-postfix-3=$instance');";
    $html[]="</script>";
    echo @implode("\n",$html);
return true;
}
function restart_postfix_3():bool{
    $page=CurrentPageName();
    $instance=intval($_GET["restart-postfix-2"]);
    $sock=new sockets();
    $data=$sock->REST_API_POSTFIX("/postfix/master/start/$instance");
    $tpl=new template_admin();
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $html[]=$tpl->widget_rouge("{error}",json_last_error_msg());
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(!$json->Status){
        $html[]=$tpl->widget_rouge("{error}","{failed}");
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $html[]="<script>";
    $html[]="LoadAjaxSilent('postfix-status-div','$page?table2=yes&instance-id=$instance');";
    $html[]="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";
    $html[]="DialogModal.close();";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}

function reinstall_instance_confirm():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks($_POST["instance-reinstall"]);
    return true;
}

// REST triggers: fire the new /postfix/multi/* Go endpoints. The Go handlers
// return immediately and write the SAME progress files the dialogs poll.
function multi_rest_restart():bool{
    $id=intval($_GET["multi-rest-restart"]);
    $instancename=get_instance_name($id);
    admin_tracks("Postfix multi: restart instance $id - $instancename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/instance/$id/restart",array());
    return true;
}
function multi_rest_reconfigure_single():bool{
    $id=intval($_GET["multi-rest-reconfigure-single"]);
    $instancename=get_instance_name($id);
    admin_tracks("Postfix multi: reconfigure instance $id - $instancename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/instance/$id/reconfigure",array());
    return true;
}
function multi_rest_install():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/enable",array());
    return admin_tracks("Postfix multi: enable multiple instances");
}
function multi_rest_uninstall():bool{
    admin_tracks("Postfix multi: disable multiple instances");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/disable",array());
    return true;
}
function get_instance_name($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT instancename FROM postfix_instances WHERE id='$ID'");
    return trim($ligne["instancename"]);

}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    if(!isset($_GET["instance-id"])){
        $_GET["instance-id"]=0;
    }
    $instance_id=intval($_GET["instance-id"]);
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
	$title=$tpl->_ENGINE_parse_body("{APP_POSTFIX} &raquo;&raquo; {service_status}");

    if($EnablePostfixMultiInstance==1){
        $title=$tpl->_ENGINE_parse_body("{InternalRouter} &raquo;&raquo; {service_status}");
    }

	$js="$page?table=yes&instance-id=$instance_id";
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    if($instance_id>0){$EnableMunin=0;}
	if($MUNIN_CLIENT_INSTALLED==1){
		if($EnableMunin==1){
            $js="$page?tabs=yes";
		}
	}

    $html=$tpl->page_header(
        "$title v$POSTFIX_VERSION",
        "fas fa-tachometer-alt",
        "{APP_POSTFIX_TEXT}",
        $js,
        "postfix-status-$instance_id",
        "progress-postfix-restart",false,
        "table-postfix"

    );

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$array["{status}"]="$page?table=yes&instance-id=$instance_id";
	$array["{statistics} {queue}"]="$page?stats-mailqueue=yess&instance-id=$instance_id";
	$array["{statistics} {messages}"]="$page?stats-mailstats=yess&instance-id=$instance_id";
	$array["{statistics} {volumes}"]="$page?stats-mailvolume=yess&instance-id=$instance_id";
	echo $tpl->tabs_default($array);
    return true;
}
function table():bool{
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    echo "<div id='postfix-status-div'></div>
    <script>LoadAjaxSilent('postfix-status-div','$page?table2=yess&instance-id=$instance_id');</script>
    ";
    return true;
}

function tiny_instance($instance_id=0):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $postfix_restart=$tpl->framework_buildjs(
        "$page?multi-rest-restart=$instance_id",
        "postfix-multi.$instance_id.reinstall.progress",
        "postfix-multi.$instance_id.reinstall.progress.log",
        "progress-postfix-restart"

    );

    $postfix_reconfigure=$tpl->framework_buildjs(
        "$page?multi-rest-reconfigure-single=$instance_id",
        "postfix-multi.$instance_id.reconfigure.progress",
        "postfix-multi.$instance_id.reconfigure.log",
        "progress-postfix-restart"
    );

    $postfix_reinstall="Loadjs('$page?instance-reinstall-js=$instance_id')";
    $topbuttons[] =array($postfix_restart, ico_refresh, "{restart}");
    $topbuttons[] =array($postfix_reinstall, ico_cd, "{reinstall}");
    $topbuttons[] =array($postfix_reconfigure, ico_configure, "{reconfigure}");
    $topbuttons[] = array("Loadjs('fw.support-tool.php?support-tool=yes&smtp=yes')", ico_support, "SupportTool");
    if($instance_id==0 && $users->AsSystemAdministrator){
        $topbuttons[] = array("Loadjs('$page?uninstall-js=yes')", ico_trash, "{uninstall_feature}");
    }

    $TINYLOGS[]="<!--- ".__FUNCTION__ ."/".__LINE__."-->";
    $TINYLOGS[]="<!--- AsSystemAdministrator=$users->AsSystemAdministrator -->";
    $TINYLOGS[]="<!--- instance_id=$instance_id -->";


    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $sql="SELECT instancename FROM  `postfix_instances` WHERE ID=$instance_id";
    $ligne=$q->mysqli_fetch_array($sql);
    $instancename=$ligne["instancename"];
    $title=$tpl->_ENGINE_parse_body("$instancename v$POSTFIX_VERSION &raquo;&raquo; {service_status}");


    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=" fa-solid fa-gauge-circle-bolt";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_TEXT}".@implode("\n",$TINYLOGS);
    $TINY_ARRAY["URL"] = "postfix-status-$instance_id";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}

function table2():bool{
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
    $users=new UsersMenus();
    $mulitple=null;
    $headsjs=null;
    VERBOSE("Instance ID = $instance_id");

    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    $title=$tpl->_ENGINE_parse_body("{APP_POSTFIX} &raquo;&raquo; {service_status}");

    if($EnablePostfixMultiInstance==1){
        $title=$tpl->_ENGINE_parse_body("{InternalRouter} &raquo;&raquo; {service_status}");
    }
    $TINYLOGS[]="<!--- ".__FUNCTION__ ."/".__LINE__."-->";
    $TINYLOGS[]="<!--- AsSystemAdministrator=$users->AsSystemAdministrator -->";
    $TINYLOGS[]="<!--- instance_id=$instance_id -->";

    if($instance_id==0 && $users->AsSystemAdministrator){
        $topbuttons[] = array("Loadjs('$page?uninstall-js=yes')", ico_trash, "{uninstall_feature}");
    }


    $topbuttons[] = array("Loadjs('fw.support-tool.php?support-tool=yes&smtp=yes')", ico_support, "SupportTool");

    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_TEXT}".@implode("\n",$TINYLOGS);
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Grille 3/9 (colonne unique sous 1200px). Les ids des conteneurs sont ceux de la chaine
	// de rafraichissement (left-postfix-status -> right-top-status / socks-connection, right-status):
	// ne pas les renommer.
	$html[]="<div class='pfx-st-root ak'>";
	$html[]="<div class='pfx-st-grid'>";
	$html[]="<div class='pfx-st-left'>";
	$html[]="<div id='left-postfix-status-$instance_id'></div>";
	$html[]="<div id='socks-connection-$instance_id'></div>";
	$html[]="</div>";
	$html[]="<div class='pfx-st-right'>";
	$html[]="<div id='right-top-status'></div>";
	$html[]="<div id='right-status'></div>";
	$html[]="</div>";
	$html[]="</div>";
	$html[]="</div>";
	$html[]="<script>";
    $html[]=$headsjs;
    $html[]=$jstiny;
    $AutoJs=$tpl->RefreshInterval_js("left-postfix-status-$instance_id",$page,"left-postfix-status=$instance_id");

    $html[]="LoadAjaxSilent('right-status','$page?right-status=yes&instance-id=$instance_id');";
    $html[]=$AutoJs;
	$html[]="</script>";

	// La feuille de style ne traverse jamais _ENGINE_parse_body (chaque regle CSS y serait lue
	// comme un {jeton}).
	echo pfx_st_css().$tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}
// ---------------------------------------------------------------------------
// Design "pfx-st" (maquette Stitch 031) — propre a CETTE page.
// La page rend son propre HTML au lieu de widget_h / widget_style1 / SERVICE_STATUS,
// a partir des MEMES donnees et avec les MEMES actions JS. Aucune feuille partagee n'est
// modifiee: tout est prefixe .pfx-st-.
// ---------------------------------------------------------------------------

/** Feuille de style de la page, une fois par requete, concatenee HORS _ENGINE_parse_body. */
function pfx_st_css():string{
    if(isset($GLOBALS["PFX_ST_CSS"])){return "";}
    $GLOBALS["PFX_ST_CSS"]=true;
    return "<style>
/* Carte, pastilles, boutons, points, grille : kit ak- (angular.css.php). */
.pfx-st-root{background:#eef2f6;border-radius:6px;padding:16px;margin-top:10px;color:#334155}
.pfx-st-grid{display:grid;grid-template-columns:minmax(260px,3fr) 9fr;gap:20px;align-items:start}
.pfx-st-left,.pfx-st-right{min-width:0}
.pfx-st-left>div+div,.pfx-st-right>div+div{margin-top:16px}
@media(max-width:1200px){.pfx-st-grid{grid-template-columns:1fr}}
.pfx-st-card{position:relative;padding:20px}
.pfx-st-card+.pfx-st-card{margin-top:16px}
.pfx-st-tiles>.pfx-st-card{margin-top:0}
.pfx-st-wm{position:absolute;right:12px;bottom:-10px;font-size:80px;line-height:1;opacity:.10;pointer-events:none;color:#64748b}
.pfx-st-tone-ok .pfx-st-wm{color:#10b981}
.pfx-st-tone-teal .pfx-st-wm{color:#0d9488}
.pfx-st-tone-err .pfx-st-wm{color:#e11d48}
.pfx-st-dot{width:10px;height:10px;margin-left:8px;vertical-align:middle}
.pfx-st-dot-pulse{animation:pfxStPulse 2s ease-in-out infinite}
@keyframes pfxStPulse{0%,100%{opacity:1}50%{opacity:.35}}
.pfx-st-root .ak-btn{text-transform:capitalize}
.pfx-st-btn-solid-danger,.pfx-st-btn-solid-danger:hover{background:#e11d48;border-color:#e11d48;color:#fff}
.pfx-st-btn-block{width:100%}
.pfx-st-btn-ico{width:34px;padding:0}
/* Tuile de service principale */
.pfx-st-svc{text-align:center}
.pfx-st-svc-ico{display:inline-flex;align-items:center;justify-content:center;width:56px;height:48px;border-radius:4px;font-size:24px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669}
.pfx-st-tone-err .pfx-st-svc-ico,.pfx-st-tone-err .pfx-st-svc-c-ico{background:#fff1f2;border-color:#fecdd3;color:#e11d48}
.pfx-st-tone-off .pfx-st-svc-ico,.pfx-st-tone-off .pfx-st-svc-c-ico{background:#f1f5f9;border-color:#e2e8f0;color:#94a3b8}
.pfx-st-svc-name{margin-top:12px}
.pfx-st-svc-state{font-size:26px;font-weight:700;color:#1e293b;margin-top:2px}
.pfx-st-tone-err .pfx-st-svc-state{color:#be123c}
.pfx-st-tone-off .pfx-st-svc-state{color:#64748b}
.pfx-st-kpi{display:grid;grid-template-columns:1fr 1fr;margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:10px 4px}
.pfx-st-kpi>div{min-width:0;padding:0 8px}
.pfx-st-kpi>div+div{border-left:1px solid #e2e8f0}
.pfx-st-kpi-l{font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.pfx-st-kpi-v{font-size:13px;font-weight:700;color:#1e293b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pfx-st-meta{display:flex;justify-content:space-between;gap:8px;font-size:11px;color:#475569;margin-top:12px;padding-top:8px;border-top:1px solid #f1f5f9;text-align:left}
.pfx-st-meta strong{color:#1e293b}
.pfx-st-meta-r{font-style:italic;font-weight:600;color:#059669;white-space:nowrap}
.pfx-st-spark{margin-top:12px;text-align:left}
.pfx-st-spark-bars{display:flex;align-items:flex-end;gap:3px;height:38px;margin-top:6px;padding:4px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px}
.pfx-st-spark-bars span{flex:1 1 0;min-width:2px;background:#10b981;border-radius:1px 1px 0 0}
.pfx-st-spark-bars span:last-child{background:#6ee7b7}
.pfx-st-svc-btns{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px}
.pfx-st-svc-btns .ak-btn{padding:0 4px}
.pfx-st-svc-restart{margin-top:8px}
/* Tuile de service compacte */
.pfx-st-svc-c{display:flex;align-items:center;gap:12px;padding:14px 16px}
.pfx-st-svc-c-ico{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:4px;font-size:18px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669}
.pfx-st-svc-c-txt{flex:1 1 auto;min-width:0}
.pfx-st-svc-c-state{font-size:14px;font-weight:700;color:#1e293b}
.pfx-st-tone-err .pfx-st-svc-c-state{color:#be123c}
.pfx-st-tone-off .pfx-st-svc-c-state{color:#64748b}
.pfx-st-err-text{font-size:13px;color:#be123c;word-break:break-word}
/* Rangee de quatre tuiles */
.pfx-st-tile{display:flex;flex-direction:column;justify-content:space-between;min-height:150px;padding:16px}
.pfx-st-tile.pfx-st-tone-err{background:#fff1f2;border-color:#fecdd3}
.pfx-st-tile.pfx-st-tone-err .ak-fig-l{color:#be123c}
.pfx-st-tile-title-teal{color:#0f766e;font-weight:700}
.pfx-st-tile-value{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:6px;font-size:24px;font-weight:700;color:#1e293b;line-height:1.2;word-break:break-word}
.pfx-st-tone-off .pfx-st-tile-value{color:#475569}
.pfx-st-tone-err .pfx-st-tile-value{color:#be123c;font-size:18px}
.pfx-st-tile-foot{position:relative;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:16px}
.pfx-st-rep-rows{margin-top:8px}
.pfx-st-rep-row{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12px;font-weight:500;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:4px 10px;position:relative}
.pfx-st-rep-row+.pfx-st-rep-row{margin-top:6px}
.pfx-st-rep-row-off{color:#64748b}
.pfx-st-rep-ico-ok{color:#10b981}
.pfx-st-rep-ico-off{color:#94a3b8}
.pfx-st-rep-count{font-size:11px;color:#475569;font-weight:600;text-align:right}
.pfx-st-tile-foot-sep{border-top:1px solid #f1f5f9;padding-top:10px;margin-top:10px}
/* Bandeaux file d'attente / refus */
.pfx-st-banner{padding:22px 24px;margin-top:16px}
.pfx-st-banner-ok{background:#ecfdf5;border-color:#a7f3d0}
.pfx-st-banner-warn{background:#fffbeb;border-color:#fde68a}
.pfx-st-banner-err{background:#fff1f2;border-color:#fecdd3}
.pfx-st-banner-off{background:#f8fafc;border-color:#e2e8f0}
.pfx-st-big-wrap{position:relative;flex:0 0 auto}
.pfx-st-banner .pfx-st-wm{right:calc(100% + 24px);top:50%;bottom:auto;transform:translateY(-50%);font-size:72px}
.pfx-st-banner-ok .pfx-st-wm{color:#059669}
.pfx-st-banner-warn .pfx-st-wm{color:#d97706}
.pfx-st-banner-err .pfx-st-wm{color:#e11d48}
.pfx-st-banner-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.pfx-st-banner-title{font-size:13px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.pfx-st-banner-ok .pfx-st-banner-title{color:#065f46}
.pfx-st-banner-warn .pfx-st-banner-title{color:#92400e}
.pfx-st-banner-err .pfx-st-banner-title{color:#9f1239}
.pfx-st-banner-off .pfx-st-banner-title{color:#475569}
.pfx-st-time{font-size:11px;font-weight:500;letter-spacing:0;padding:1px 8px;border-radius:4px;border:1px solid #6ee7b7;background:#d1fae5;color:#065f46}
.pfx-st-banner-warn .pfx-st-time{border-color:#fcd34d;background:#fef3c7;color:#92400e}
.pfx-st-banner-err .pfx-st-time{border-color:#fda4af;background:#ffe4e6;color:#9f1239}
.pfx-st-banner-off .pfx-st-time{border-color:#cbd5e1;background:#f1f5f9;color:#475569}
.pfx-st-banner-text{font-size:13px;color:#475569;margin-top:6px;max-width:640px;line-height:1.5}
.pfx-st-big{font-size:48px;font-weight:700;line-height:1;text-align:right;white-space:nowrap}
.pfx-st-banner-ok .pfx-st-big{color:#065f46}
.pfx-st-banner-warn .pfx-st-big{color:#92400e}
.pfx-st-banner-err .pfx-st-big{color:#9f1239}
.pfx-st-banner-off .pfx-st-big{color:#64748b;font-size:32px}
.pfx-st-big-cap{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;text-align:right;margin-top:6px;color:#64748b}
.pfx-st-banner-foot{position:relative;display:flex;align-items:center;flex-wrap:wrap;gap:8px 20px;margin-top:16px;padding-top:12px;border-top:1px solid rgba(15,23,42,.08);font-size:13px;color:#334155}
.pfx-st-banner-ok .pfx-st-banner-foot{border-top-color:#a7f3d0}
.pfx-st-banner-err .pfx-st-banner-foot{border-top-color:#fecdd3}
.pfx-st-banner-warn .pfx-st-banner-foot{border-top-color:#fde68a}
.pfx-st-ind{display:inline-flex;align-items:center;gap:6px;font-weight:500}
.pfx-st-ind-ok{color:#059669}
.pfx-st-ind-warn{color:#d97706}
.pfx-st-ind-err{color:#e11d48}
.pfx-st-ind-muted{color:#94a3b8}
.pfx-st-link{margin-left:auto;font-weight:600;text-decoration:underline!important;cursor:pointer;white-space:nowrap}
.pfx-st-banner-ok .pfx-st-link{color:#047857!important}
.pfx-st-banner-warn .pfx-st-link{color:#b45309!important}
.pfx-st-banner-err .pfx-st-link,.pfx-st-banner-off .pfx-st-link{color:#be123c!important}
.pfx-st-freeze{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:none;color:#fff;background:#e11d48;border-radius:4px;padding:2px 8px}
@media(max-width:700px){.pfx-st-banner-head{flex-direction:column}.pfx-st-big,.pfx-st-big-cap{text-align:left}.pfx-st-banner .pfx-st-wm{display:none}}
</style>";
}

/** Icone filigrane. Taille portee par le conteneur: Font Awesome remplace le <i> par un <svg>. */
function pfx_st_wm(string $icon):string{
    return "<span class='pfx-st-wm'><i class='$icon'></i></span>";
}
function pfx_st_pill(string $text,string $tone=""):string{
    $c=$tone==""?"ak-off":"ak-$tone";
    return "<span class='ak-pill $c'>$text</span>";
}
/** Bouton fin. $js est le JS existant (framework_buildjs / Loadjs), toujours entre apostrophes. */
function pfx_st_button(string $label,string $js,string $icon,string $class=""):string{
    if($js==""){return "";}
    $c=$class==""?"":" $class";
    $ic=$icon==""?"":"<i class='$icon'></i>";
    $txt=$label==""?"":"<span>$label</span>";
    return "<button type='button' class='ak-btn$c' OnClick=\"$js\">$ic$txt</button>";
}

/** Meme abreviation que SERVICE_STATUS (svctile_uptime, prive): « 1 month, 22 days » -> 1mo,22d,23h. */
function pfx_st_uptime($uptime):string{
    $uptime=trim((string)$uptime);
    if($uptime===""){return "";}
    $units=array("year"=>"y","month"=>"mo","week"=>"w","day"=>"d","hour"=>"h","min"=>"mn","sec"=>"s");
    $out=array();
    $re=array();
    if(preg_match_all('#([0-9]+)\s*([a-zA-Z]+)#',$uptime,$re,PREG_SET_ORDER)){
        foreach($re as $m){
            $unit=strtolower($m[2]);
            foreach($units as $needle=>$abbr){
                if(strpos($unit,$needle)===0){$out[]=$m[1].$abbr;break;}
            }
            if(count($out)>=3){break;}
        }
    }
    if(count($out)==0){return $uptime;}
    return implode(",",$out);
}

/** Courbe memoire: uniquement les vrais points de ressources/pmemory/<service>, jamais decoratifs. */
function pfx_st_spark(string $service_name):string{
    if($service_name=="" || !preg_match('#^[A-Za-z0-9_.-]+$#',$service_name)){return "";}
    $path="/usr/share/artica-postfix/ressources/pmemory/$service_name";
    if(!is_file($path)){return "";}
    $vals=array();
    foreach(explode(";",(string)@file_get_contents($path)) as $v){
        $v=trim($v);
        if($v!=="" && is_numeric($v)){$vals[]=floatval($v);}
    }
    if(count($vals)<2){return "";}
    if(count($vals)>30){$vals=array_slice($vals,-30);}
    $max=max($vals);
    $min=min($vals);
    if($max<=0){return "";}
    // Une memoire stable (ecart < 5 % du maximum) se lit comme une rangee a mi-hauteur;
    // sinon l'ecart min..max occupe 20..95 % pour que la variation reelle reste visible.
    $flat=(($max-$min)/$max)<0.05;
    $bars=array();
    foreach($vals as $v){
        $h=$flat?50:intval(round(20+(($v-$min)/($max-$min))*75));
        $bars[]="<span style='height:$h%'></span>";
    }
    return "<div class='pfx-st-spark'><div class='ak-fig-l'>{graph}: {memory}</div>"
        ."<div class='pfx-st-spark-bars'>".implode("",$bars)."</div></div>";
}

/**
 * Tuile de service, grande ($big) ou compacte, lue dans l'ini Bs_IniHandler comme SERVICE_STATUS:
 * service_disabled (0 = desactive), running, uptime, master_memory, maxfd/curfd, service_name,
 * Nograph, et ChangetitleTo. Memes actions: graphes fw.rrd.micro.php et JS de redemarrage,
 * ce dernier reserve a AsSystemAdministrator. Cle absente de l'ini -> rien (comme SERVICE_STATUS).
 */
function pfx_st_service_card($ini,string $key,$restartjs,bool $big):string{
    if(!is_object($ini) || !isset($ini->_params[$key]) || !is_array($ini->_params[$key])){return "";}
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$restartjs=null;}
    $restartjs=is_null($restartjs)?"":(string)$restartjs;
    $a=$ini->_params[$key];

    $sname=isset($a["service_name"])?(string)$a["service_name"]:"";
    $title="{".$key."}";
    if(preg_match("#^APP_#",$sname)){$title="{".$sname."}";}
    if(property_exists($ini,"ChangetitleTo") && !is_null($ini->ChangetitleTo) && strlen((string)$ini->ChangetitleTo)>1){
        $title=htmlspecialchars((string)$ini->ChangetitleTo,ENT_QUOTES);
    }

    // Memes comparaisons lâches que SERVICE_STATUS: une cle absente vaut 0.
    $tone="ok"; $label="{running}"; $ico="fas fa-thumbs-up"; $dot="ak-dot ak-ok pfx-st-dot pfx-st-dot-pulse";
    if(intval($a["service_disabled"] ?? 0)==0){
        $tone="off"; $label="{disabled}"; $ico="far fa-times-circle"; $dot="ak-dot ak-off pfx-st-dot"; $restartjs="";
    }elseif(intval($a["running"] ?? 0)==0){
        $tone="err"; $label="{stopped}"; $ico="fas fa-exclamation-triangle"; $dot="ak-dot ak-err pfx-st-dot";
    }
    $restartClass=$tone=="err"?"pfx-st-btn-solid-danger":"ak-btn-primary";

    if(!$big){
        $h=array();
        $h[]="<div class='ak-card pfx-st-card pfx-st-svc-c pfx-st-tone-$tone'>";
        $h[]="<div class='pfx-st-svc-c-ico'><i class='$ico'></i></div>";
        $h[]="<div class='pfx-st-svc-c-txt'><div class='ak-fig-l'>$title</div>";
        $h[]="<div class='pfx-st-svc-c-state'>$label<span class='$dot'></span></div></div>";
        if($restartjs!=""){
            $h[]="<span title='{restart}'>".pfx_st_button("",$restartjs,"fas fa-sync-alt","pfx-st-btn-ico".($tone=="err"?" pfx-st-btn-solid-danger":""))."</span>";
        }
        $h[]="</div>";
        return implode("\n",$h);
    }

    $h=array();
    $h[]="<div class='ak-card pfx-st-card pfx-st-svc pfx-st-tone-$tone'>";
    $h[]="<div class='pfx-st-svc-ico'><i class='$ico'></i></div>";
    $h[]="<div class='ak-fig-l pfx-st-svc-name'>$title</div>";
    $h[]="<div class='pfx-st-svc-state'>$label<span class='$dot'></span></div>";
    if($tone=="ok"){
        $uptime=htmlspecialchars(pfx_st_uptime($a["uptime"] ?? ""));
        // FormatBytes() renvoie deja du HTML (chiffre + &nbsp; + unite): ne pas l'echapper une 2e fois.
        $mem=(string)FormatBytes(floatval($a["master_memory"] ?? 0));
        $h[]="<div class='pfx-st-kpi'>";
        $h[]="<div><div class='pfx-st-kpi-l'>{uptime}</div><div class='pfx-st-kpi-v' title='$uptime'>$uptime</div></div>";
        $h[]="<div><div class='pfx-st-kpi-l'>{memory}</div><div class='pfx-st-kpi-v'>$mem</div></div>";
        $h[]="</div>";
        if(isset($a["maxfd"])){
            $maxfd=intval($a["maxfd"]);
            $curfd=intval($a["curfd"] ?? 0);
            $health="";
            if($maxfd>0){
                $percent=100-(($curfd/$maxfd)*100);
                if($percent<0){$percent=0;}
                $health="<span class='pfx-st-meta-r'>".sprintf("%.1f",$percent)."% {health}</span>";
            }
            $h[]="<div class='pfx-st-meta'><span>{files}: <strong>$curfd/$maxfd</strong></span>$health</div>";
        }
        $h[]=pfx_st_spark($sname);
        if(!isset($a["Nograph"])){
            $h[]="<div class='pfx-st-svc-btns'>";
            $h[]=pfx_st_button("{graph} {memory}","Loadjs('fw.rrd.micro.php?key=$key');",ico_mem);
            $h[]=pfx_st_button("{graph} {cpu}","Loadjs('fw.rrd.micro.php?key=$key');",ico_cpu);
            $h[]="</div>";
        }
    }
    if($restartjs!=""){
        $h[]="<div class='pfx-st-svc-restart'>".pfx_st_button("{restart}",$restartjs,"fas fa-sync-alt","pfx-st-btn-block $restartClass")."</div>";
    }
    $h[]="</div>";
    return implode("\n",$h);
}

/** Carte d'erreur compacte (daemon injoignable, reponse illisible). */
function pfx_st_error_card(string $title,string $text):string{
    return "<div class='ak-card pfx-st-card pfx-st-svc-c pfx-st-tone-err'>"
        ."<div class='pfx-st-svc-c-ico'><i class='".ico_bug."'></i></div>"
        ."<div class='pfx-st-svc-c-txt'><div class='ak-fig-l'>$title</div>"
        ."<div class='pfx-st-err-text'>$text</div></div></div>";
}

/** Tuile de la rangee du haut: titre en petites capitales, valeur, pastille, corps, pied. */
function pfx_st_tile(string $tone,string $icon,string $title,string $value,string $pill,string $body,string $foot):string{
    $h=array();
    $h[]="<div class='ak-card pfx-st-card pfx-st-tile pfx-st-tone-$tone'>";
    $h[]=pfx_st_wm($icon);
    $h[]="<div>";
    $h[]="<div class='ak-fig-l'>$title</div>";
    if($value!=="" || $pill!==""){
        $h[]="<div class='pfx-st-tile-value'><span>$value</span>$pill</div>";
    }
    $h[]=$body;
    $h[]="</div>";
    if($foot!==""){$h[]="<div class='pfx-st-tile-foot'>$foot</div>";}
    $h[]="</div>";
    return implode("\n",$h);
}

/**
 * Indicateurs de la file pour l'instance affichee, lus dans /postfix/queue/watchdog/status.
 * Retourne array("items"=>[[ton,icone,texte]], "alerting"=>bool). Route en echec, instance non
 * mesuree ou mesure perimee: aucun indicateur (on n'affiche pas un chiffre faux).
 */
function pfx_st_queue_watchdog(int $instance_id):array{
    $out=array("items"=>array(),"alerting"=>false);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/watchdog/status"),true);
    if(!is_array($json) || empty($json["Status"])){return $out;}
    if(empty($json["enabled"])){
        // Desactive: la derniere mesure peut dater de plusieurs semaines, on ne la montre pas.
        $out["items"][]=array("muted","fas fa-power-off","{watchdog}: {disabled}");
        return $out;
    }
    $last=intval($json["last_pass_at"] ?? 0);
    if($last<=0 || (time()-$last)>86400){return $out;}
    $instances=(isset($json["instances"]) && is_array($json["instances"]))?$json["instances"]:array();
    foreach($instances as $inst){
        if(!is_array($inst) || intval($inst["instanceid"] ?? -1)!=$instance_id){continue;}
        if(empty($inst["measured"])){return $out;}
        $queues=(isset($inst["queues"]) && is_array($inst["queues"]))?$inst["queues"]:array();
        $alert="";
        foreach($queues as $q){
            if(!is_array($q)){continue;}
            $qname=(string)($q["queue"] ?? "");
            if($qname=="deferred"){
                $line="{deferred_queue}: ".intval($q["count"] ?? 0);
                $age=intval($q["oldest_age_secs"] ?? 0);
                if($age>0){$line.=" &middot; {pfx_st_oldest} ".pfx_st_age($age);}
                $out["items"][]=array("warn","fas fa-hourglass-half",$line);
            }
            if(!empty($q["alerting"]) && $alert==""){
                $cause=(string)($q["cause"] ?? "");
                $alert=htmlspecialchars($qname);
                if(preg_match('#^[a-z+]+$#',$cause)){$alert.=": {queuewatchdog.cause.$cause}";}
            }
        }
        if($alert!=""){
            $out["alerting"]=true;
            $out["items"][]=array("err","fas fa-triangle-exclamation","{pfx_st_watchdog_alerting} ($alert)");
        }else{
            $out["items"][]=array("ok","fas fa-circle-check","{pfx_st_watchdog_no_alert}");
        }
        return $out;
    }
    return $out;
}
/** Meme format que la page de la file (fw.postfix.queue.php::watchdog_age). */
function pfx_st_age(int $secs):string{
    if($secs<=0){return "-";}
    $d=intval($secs/86400); $h=intval(($secs%86400)/3600); $m=intval(($secs%3600)/60);
    if($d>0){return "{$d}d {$h}h";}
    if($h>0){return "{$h}h {$m}m";}
    if($m>0){return "{$m}m";}
    return "{$secs}s";
}

/**
 * Deux premieres categories de refus, en pourcentage du camembert PIE_REFUSED_SMTP (top 10 ecrit
 * par la maintenance maillog toutes les 15 min). Jamais de GROUP BY sur smtplog ici.
 */
function pfx_st_refused_top():array{
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PIE_REFUSED_SMTP");
    if($raw===""){return array();}
    $p=@unserialize(base64_decode($raw),array("allowed_classes"=>false));
    if(!is_array($p) || count($p)==0){return array();}
    $total=0;
    foreach($p as $k=>$v){$total+=intval($v);}
    if($total<=0){return array();}
    arsort($p);
    // Liste fermee des categories du daemon (postfix/maillog/RefusalCategory.go), comme
    // fw.postfix.refused.php::pfxref_category_slugs().
    $slugs=array("no_ptr","auth_policy","rbl","policy","spam","virus","greylisting",
        "user_unknown","mailbox_full","rate_limit","relay_denied","auth_required","tls_policy",
        "size_exceeded","sender_rejected","helo_rejected","syntax_error","host_unreachable",
        "mail_loop","service_unavailable","blacklist_acl","milter_discard","rejected","other");
    $out=array();
    foreach($p as $k=>$v){
        $k=(string)$k;
        $m=array();
        if(preg_match('/^(?:(\d{3})\s+)?([a-z][a-z0-9_]*)$/',$k,$m) && in_array($m[2],$slugs,true)){
            $label=trim($m[1]." {smtp_refusal_".$m[2]."}");
        }else{
            $label=htmlspecialchars(mb_strlen($k)>40?mb_substr($k,0,40)."…":$k);
        }
        $pct=(intval($v)/$total)*100;
        $out[]=array($label,pfx_st_pct($pct));
        if(count($out)>=2){break;}
    }
    return $out;
}

/** Pourcentage lisible: entier entre 10 et 90, une decimale aux extremes, jamais un faux 100 ou 0. */
function pfx_st_pct(float $pct):string{
    if($pct>0 && $pct<0.1){return "&lt;0.1";}
    if($pct<100 && $pct>99.9){return "&gt;99.9";}
    if($pct>=10 && $pct<=90){return sprintf("%d",round($pct));}
    $r=rtrim(rtrim(sprintf("%.1f",$pct),"0"),".");
    return $r===""?"0":$r;
}

function right_status():bool{
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"] ?? 0);
    $postqueuepInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM"));
    $postqueuepTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM_TIME"));
    $refusedInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOfSMTPThreats"));

    $main=new maincf_multi($instance_id);
    $freeze_delivery_queue=intval($main->GET("freeze_delivery_queue"));
    $wd=pfx_st_queue_watchdog($instance_id);

    $tone="ok";
    if($wd["alerting"]){$tone="warn";}
    if($freeze_delivery_queue==1){$tone="err";}

    // ---- File d'attente
    // 0 et non {none} : ce jeton partage vaut « Don't use feature » (options de listes).
    $big=$postqueuepInt==0?"0":FormatNumber($postqueuepInt);
    $html=array();
    $html[]="<div class='ak-card pfx-st-card pfx-st-banner pfx-st-banner-$tone'>";
    $html[]="<div class='pfx-st-banner-head'><div>";
    $title="<span>{queue}</span>";
    if($postqueuepTime>0){$title.="<span class='pfx-st-time'>(".date("H:i:s",$postqueuepTime).")</span>";}
    if($freeze_delivery_queue==1){$title.="<span class='pfx-st-freeze'><i class='fas fa-stop-circle'></i> {WARN_QUEUE_FREEZE}</span>";}
    $html[]="<div class='pfx-st-banner-title'>$title</div>";
    $html[]="<div class='pfx-st-banner-text'>{pfx_st_queue_explain}</div>";
    $html[]="</div><div class='pfx-st-big-wrap'>".pfx_st_wm("fas fa-inbox")."<div class='pfx-st-big'>$big</div><div class='pfx-st-big-cap'>{messages}</div></div></div>";
    $html[]="<div class='pfx-st-banner-foot'>";
    foreach($wd["items"] as $it){
        $html[]="<span class='pfx-st-ind'><span class='pfx-st-ind-{$it[0]}'><i class='{$it[1]}'></i></span>{$it[2]}</span>";
    }
    $html[]="<a class='pfx-st-link' href=\"javascript:blur();\" OnClick=\"LoadAjax('MainContent','fw.postfix.queue.php?instance-id=$instance_id');\">{pfx_st_inspect_queue} &rarr;</a>";
    $html[]="</div></div>";

    // ---- Refus
    $rtone=$refusedInt==0?"off":"err";
    $rbig=$refusedInt==0?"{none}":FormatNumber($refusedInt);
    $html[]="<div class='ak-card pfx-st-card pfx-st-banner pfx-st-banner-$rtone'>";
    $html[]="<div class='pfx-st-banner-head'><div>";
    $html[]="<div class='pfx-st-banner-title'><span>{refused}</span></div>";
    $html[]="<div class='pfx-st-banner-text'>{pfx_st_refused_explain}</div>";
    $html[]="</div><div class='pfx-st-big-wrap'>".pfx_st_wm("fas fa-shield-xmark")."<div class='pfx-st-big'>$rbig</div><div class='pfx-st-big-cap'>{messages}</div></div></div>";
    $html[]="<div class='pfx-st-banner-foot'>";
    $icons=array("fas fa-shield-halved","fas fa-filter");
    foreach(pfx_st_refused_top() as $i=>$r){
        $html[]="<span class='pfx-st-ind'><span class='pfx-st-ind-err'><i class='{$icons[$i]}'></i></span>{$r[0]}: {$r[1]}%</span>";
    }
    $html[]="<a class='pfx-st-link' href=\"javascript:blur();\" OnClick=\"LoadAjax('MainContent','fw.postfix.refused.php');\">{pfx_st_analyze_refusals} &rarr;</a>";
    $html[]="</div></div>";

    echo pfx_st_css().$tpl->_ENGINE_parse_body($html);
    return true;
}

function left_postfix_status_master():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();

    // MTA + Artica Mail.Log: meme ini (/postfix/master/status) que lisait SERVICE_STATUS.
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/master/status");
    $json=json_decode((string)$data);
    if(json_last_error()>JSON_ERROR_NONE || !is_object($json) || !property_exists($json,"Info")){
        $err=json_last_error()>JSON_ERROR_NONE?json_last_error_msg():"{protocol_error}";
        $html[]=pfx_st_error_card("{APP_POSTFIX}",htmlspecialchars($err));
    }else{
        $bsini=new Bs_IniHandler();
        $bsini->loadString($json->Info);
        $html[]=pfx_st_service_card($bsini,"APP_POSTFIX","Loadjs('$page?restart-postfix-js=0')",true);
        $restart_js=$tpl->framework_buildjs("postfix:/postfix/artica/maillog/restart","articamail.progress",
            "articamail.progress.log",
            "progress-postfix-restart"
        );
        $maillog=pfx_st_service_card($bsini,"APP_ARTICA_MAILLOG",$restart_js,false);
    }

    // SMTP Manager: ini de /smtp-manager/status (articarest).
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/smtp-manager/status"),true);
    if(!is_array($json) || !isset($json["Status"])){
        $html[]=pfx_st_error_card("{SMTP_MANAGER}","{protocol_error} ".htmlspecialchars(json_last_error_msg()));
    }elseif(!$json["Status"]){
        $html[]=pfx_st_error_card("{SMTP_MANAGER}",htmlspecialchars((string)($json["Error"] ?? "")));
    }else{
        $bsini=new Bs_IniHandler();
        $bsini->loadString((string)($json["Info"] ?? ""));
        $smtp_manager_restart=$tpl->framework_buildjs(
            "/smtp-manager/restart",
            "smtp-manager.progress",
            "smtp-manager.progress.log",
            "progress-postfix-restart"
        );
        $html[]=pfx_st_service_card($bsini,"SMTP_MANAGER",$smtp_manager_restart,false);
    }
    if(isset($maillog)){$html[]=$maillog;}

    // Chaine de rafraichissement: emise sur TOUS les chemins, erreurs comprises.
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]="LoadAjaxSilent('socks-connection-0','$page?socks-connection=yes&instance-id=0');";
    $html[]="LoadAjaxSilent('right-top-status','$page?right-top-status=yes&instance-id=0');";
    $html[]="</script>";
    echo pfx_st_css().$tpl->_ENGINE_parse_body($html);
    return true;
}



function left_postfix_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["left-postfix-status"]);

    if($instance_id==0) {
        left_postfix_status_master();
        return true;
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("postfix2.php?instance-status=$instance_id");
    $ini=new Bs_IniHandler(PROGRESS_DIR . "/postfix.$instance_id.status");
    $html=array();
    $html[]=pfx_st_service_card($ini,"APP_POSTFIX",null,true);
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]="LoadAjaxSilent('right-top-status','$page?right-top-status=yes&instance-id=$instance_id');";
    $html[]="LoadAjaxSilent('socks-connection-$instance_id','$page?socks-connection=yes&instance-id=$instance_id');";
    $html[]="</script>";
    echo pfx_st_css().$tpl->_ENGINE_parse_body($html);
    return true;
}

function widget_articapostfix():string{
    $tpl=new template_admin();
    $ico="fas fa-paper-plane";

    $smtp_manager_restart=$tpl->framework_buildjs(
        "/smtp-manager/restart",
        "smtp-manager.progress",
        "smtp-manager.progress.log",
        "progress-postfix-restart"
    );
    $foot=pfx_st_button("{restart}",$smtp_manager_restart,"fas fa-sync-alt");

    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/status"),true);
    if(!is_array($json) || !isset($json["Status"])){
        return pfx_st_tile("err",ico_bug,"{SMTP_MANAGER}","{protocol_error}","","",$foot);
    }
    if(!$json["Status"]){
        return pfx_st_tile("err",ico_bug,"{SMTP_MANAGER}","{error}","",
            "<div class='pfx-st-err-text'>".htmlspecialchars((string)($json["Error"] ?? ""))."</div>",$foot);
    }
    $version=htmlspecialchars((string)($json["Version"] ?? ""));
    return pfx_st_tile("ok",$ico,"{SMTP_MANAGER}","v$version","","",$foot);
}

function top_status():bool{
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"] ?? 0);
    $page=CurrentPageName();
    $users=new usersMenus();
    $html=array();
    $html[]="<div class='ak-grid-4 pfx-st-tiles'>";
    $html[]=widget_multiple_instances();
    $html[]=widget_artica_milter();
    $html[]=widget_articapostfix();
    $html[]=widget_artica_reputation($instance_id);
    $html[]="</div>";


    $TINYLOGS[]="<!--- ".__FUNCTION__ ."/".__LINE__."-->";
    $TINYLOGS[]="<!--- AsSystemAdministrator=$users->AsSystemAdministrator -->";
    $TINYLOGS[]="<!--- instance_id=$instance_id -->";

    $instancename="SMTP Master";
    if($instance_id>0){
        $jstiny=tiny_instance($instance_id);
    }else {
        $POSTFIX_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
        $TINY_ARRAY["TITLE"] = "{APP_POSTFIX} &raquo;&raquo; {service_status} <small>($instancename) v.$POSTFIX_VERSION</small>";

        $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));

        if($EnablePostfixMultiInstance==1){
            $TINY_ARRAY["TITLE"] = "{InternalRouter} &raquo;&raquo; {service_status} <small>($instancename) v.$POSTFIX_VERSION</small>";

        }
        $TINY_ARRAY["ICO"] = "fas fa-tachometer-alt";
        $TINY_ARRAY["EXPL"] = "{APP_POSTFIX_TEXT}".@implode("\n",$TINYLOGS);
        $TINY_ARRAY["URL"] = "postfix-status-$instance_id";
        $topbuttons[] = array("Loadjs('fw.support-tool.php?support-tool=yes&smtp=yes')", ico_support, "SupportTool");
        if($instance_id==0 && $users->AsSystemAdministrator){
            $topbuttons[] = array("Loadjs('$page?uninstall-js=yes')", ico_trash, "{uninstall_feature}");
        }



        // Remise à zéro de TOUTES les statistiques SMTP : maillog (traçabilité par message),
        // compteurs pflogsumm, postscreen, rspamd, EgressGuard, et les bases BoltDB de métrologie
        // (Postfix, KSMG, Cyrus). Irréversible et volontairement global (toutes instances), d'où
        // sa place sur le TINY « SMTP Master » plutôt que sur celui d'une instance donnée.
        // La confirmation est portée par le 7e argument de framework_buildjs ; le daemon exige en
        // plus confirm=yes pour qu'un simple GET ne puisse pas détruire l'historique.
        $purge_statistics=$tpl->framework_buildjs(
            "postfix:/postfix/statistics/purge?confirm=yes",
            "postfix.statistics.purge.progress",
            "postfix.statistics.purge.progress.log",
            "progress-postfix-restart",
            null,
            null,
            "{reset_statistics_confirm}"
        );
        $topbuttons[] = array($purge_statistics, "fas fa-trash-alt", "{reset_statistics}");

        $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    }



    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="</script>";
    echo pfx_st_css().$tpl->_ENGINE_parse_body($html);
    return true;
}

function widget_milter_greylist():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ico="fa-solid fa-hourglass-clock";
    $button["help"]="https://wiki.articatech.com/en/smtp-service";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_INSTALLED"))==0){
        return $tpl->widget_h("grey",$ico,"{not_installed}","{MILTER_GREYLIST}",$button);
    }
    $MilterGreyListEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));
    if($MilterGreyListEnabled==0){
        return $tpl->widget_h("grey",$ico,"{not_installed}","{MILTER_GREYLIST}",$button);
    }
    return $tpl->widget_h("grey",$ico,"{not_installed}","{MILTER_GREYLIST}",$button);




}
function widget_artica_milter():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableArticaMilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMilter"));
    if($EnableArticaMilter==1){

        $jsremove=$tpl->framework_buildjs(
            "/articamilter/uninstall",
            "articamilter.progress",
            "articamilter.log","progress-postfix-restart",
            "LoadAjaxSilent('right-status','$page?right-status=yes');"
        );
        return pfx_st_tile("ok","fa-solid fa-filter","Artica Milter","{active2}",pfx_st_pill("{enabled}","ok"),"",
            pfx_st_button("{uninstall}",$jsremove,"fas fa-trash-alt","ak-btn-danger"));
    }

    $install=$tpl->framework_buildjs(
        "/articamilter/install",
        "articamilter.progress",
        "articamilter.log","progress-postfix-restart",
        "LoadAjaxSilent('right-status','$page?right-status=yes');"
    );
    return pfx_st_tile("off","fa-solid fa-filter-circle-xmark","Artica Milter","{disabled}",pfx_st_pill("{inactive2}"),"",
        pfx_st_button("{install}",$install,ico_cd));

}
// Feature 031 - Artica IP reputation (rblquery.artica.center). A Corporate license is required:
// without it the tile is grey with no rows and no button, and the save is refused.
function widget_artica_reputation(int $instance_id):string{
    $page=CurrentPageName();
    $ico="fas fa-shield-virus";
    $title="<span class='pfx-st-tile-title-teal'>{artica_reputation}</span>";
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        return pfx_st_tile("off",$ico,"{artica_reputation}","{no_license}","","","");
    }
    $white=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReputationWhite"))==1;
    $black=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReputationBlack"))==1;

    $rows=array();
    $rows[]="<div class='pfx-st-rep-rows'>";
    foreach(array(array("{whitelist}",$white,"fas fa-circle-check"),array("{blacklist}",$black,"fas fa-ban")) as $r){
        $on=$r[1];
        $rows[]="<div class='pfx-st-rep-row".($on?"":" pfx-st-rep-row-off")."'>"
            ."<span><span class='".($on?"pfx-st-rep-ico-ok":"pfx-st-rep-ico-off")."'><i class='{$r[2]}'></i></span> {$r[0]}</span>"
            .($on?pfx_st_pill("{pfx_st_on}","ok"):pfx_st_pill("{off}"))."</div>";
    }
    $rows[]="</div>";

    // Compteurs 24 h: si le daemon ne repond pas, rien (pas de zero invente).
    $count="";
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/reputation/status/$instance_id"),true);
    if(is_array($json) && !empty($json["counters_available"]) && isset($json["last24h"]) && is_array($json["last24h"])){
        $w=intval($json["last24h"]["white"] ?? 0);
        $b=intval($json["last24h"]["black_smtpd"] ?? 0)+intval($json["last24h"]["black_postscreen"] ?? 0);
        $count="<span class='pfx-st-rep-count'>$w {reputation_allowed} &middot; $b {reputation_blocked}</span>";
    }
    $foot=pfx_st_button("{configure}","Loadjs('$page?reputation-js=yes&instance-id=$instance_id')","fas fa-sliders","ak-btn-primary").$count;
    return pfx_st_tile(($white||$black)?"teal":"off",$ico,$title,"","",implode("\n",$rows),$foot);
}

function reputation_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $tpl->js_dialog2("{artica_reputation}","$page?reputation-popup=yes&instance-id=$instance_id",550);
    return true;
}

function reputation_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        echo $tpl->div_error("{no_license}");
        return true;
    }
    $white=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReputationWhite"));
    $black=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReputationBlack"));
    $apply=$tpl->framework_buildjs(
        "postfix:/postfix/reputation/apply",
        "postfix.reputation.progress",
        "postfix.reputation.progress.log",
        "reputation-progress",
        "dialogInstance2.close();LoadAjaxSilent('right-top-status','$page?right-top-status=yes&instance-id=$instance_id');"
    );
    $html=array();
    $form=array();
    $html[]="<div id='reputation-progress'></div>";
    $form[]=$tpl->field_checkbox("ArticaReputationWhite","{reputation_allow}",$white);
    $form[]=$tpl->field_checkbox("ArticaReputationBlack","{reputation_block}",$black);
    $html[]=$tpl->form_outside(null,$form,"{artica_reputation_explain}","{apply}",$apply,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// Nothing is echoed on success (form_outside convention). Booleans arrive as integers.
function reputation_save():bool{
    $tpl=new template_admin();
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        echo $tpl->post_error("{no_license}");
        return false;
    }
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaReputationWhite",intval($_POST["ArticaReputationWhite"] ?? 0)==1?1:0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaReputationBlack",intval($_POST["ArticaReputationBlack"] ?? 0)==1?1:0);
    return true;
}

function widget_multiple_instances():string{
    $page=CurrentPageName();
    $ico="fa-solid fa-layer-group";
    $EnablePostfixMultiInstance = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));

    if ($EnablePostfixMultiInstance == 0) {
        return pfx_st_tile("off",$ico,"{multiple_instances}","{disabled}",pfx_st_pill("{inactive2}"),"",
            pfx_st_button("{install}","Loadjs('$page?multi-feature-enable=yes')",ico_cd));
    }
    $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $postfix_instances = intval($q->COUNT_ROWS("postfix_instances"));
    return pfx_st_tile("ok",$ico,"{multiple_instances}","$postfix_instances {instances}",pfx_st_pill("{enabled}","ok"),"",
        pfx_st_button("{uninstall}","Loadjs('$page?multi-feature-disable=yes')",ico_trash,"ak-btn-danger"));
}

function multi_feature_uninstall_confirm():bool{
    return admin_tracks("SMTP: Confirm removing multiple-instances feature and back to single instance");
}
function multi_feature_install_confirm():bool{
    return admin_tracks("SMTP: Confirm installing multiple-instances feature");
}
function multi_feature_install():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $postfix_install_instances = $tpl->framework_buildjs("postfix:/postfix/multi/feature/enable",
        "postfix-multi.progress",
        "postfix-multi.progress.log",
        "progress-postfix-restart",
        "LoadAjax('table-postfix','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

    return $tpl->js_confirm_execute("{multi_postfix_install}","multi-feature-enable","yes",$postfix_install_instances);
}
function multi_feature_uninstall():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $postfix_remove_instances = $tpl->framework_buildjs("postfix:/postfix/multi/feature/disable",
        "postfix-multi.progress", "postfix-multi.progress.log", "progress-postfix-restart",
        "LoadAjax('table-postfix','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

    return $tpl->js_confirm_execute("{multi_postfix_remove}","multi-feature-disable","yes",$postfix_remove_instances);

}


function sock_connection():bool{
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    if($instance_id==0){return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");


    $sql="SELECT * FROM `postfix_instances` WHERE ID=$instance_id";
/*        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `instancename` TEXT NOT NULL,
         interface TEXT NOT NULL,
        `enabled` INTEGER NOT NULL DEFAULT 1);";
*/
    $ligne=$q->mysqli_fetch_array($sql);
    $interface=$ligne["interface"];
    $enabled=intval($ligne["enabled"]);

    if($enabled==0){
        echo $tpl->widget_grey("$interface:25","{inactive}",null,ico_interface);
        return true;
    }

    $net=new networking();
    $Ipaddr=$net->InterfaceToIPv4($interface);

    $fp=@fsockopen($Ipaddr, 25, $errno, $errstr, 2);
    if(!$fp){
        echo $tpl->widget_rouge("$Ipaddr:25 - $errstr","{error} $errno",null,ico_interface);
        return true;
    }
    echo $tpl->widget_vert("$interface:25","{connected}",null,ico_interface);
    @fclose($fp);
    return true;
}

function stats_mailqueue():bool{
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailqueue-day.png";
	$f[]="postfix_mailqueue-week.png";
	$f[]="postfix_mailqueue-month.png";
	$f[]="postfix_mailqueue-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	return true;
}

function stats_mailstats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailstats-day.png";
	$f[]="postfix_mailstats-week.png";
	$f[]="postfix_mailstats-month.png";
	$f[]="postfix_mailstats-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function stats_mailvolume(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailvolume-day.png";
	$f[]="postfix_mailvolume-week.png";
	$f[]="postfix_mailvolume-month.png";
	$f[]="postfix_mailvolume-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}






function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}