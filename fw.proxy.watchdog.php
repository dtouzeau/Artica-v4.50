<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : scriptname, method, eth, filename et line etaient recopies sans encodage dans la page (XSS).
// scriptname est une liste de noms de scripts separes par des virgules.
if(isset($_GET["scriptname"])){$_GET["scriptname"]=preg_replace('/[^A-Za-z0-9_.,-]/','',strval($_GET["scriptname"]));}
if(isset($_GET["method"])){$_GET["method"]=preg_replace('/[^A-Za-z0-9_]/','',strval($_GET["method"]));}
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',strval($_GET["eth"]));}
if(isset($_GET["filename"])){$_GET["filename"]=preg_replace('#[^A-Za-z0-9_.:@/ -]#','',strval($_GET["filename"]));}
if(isset($_GET["line"])){$_GET["line"]=intval($_GET["line"]);}
if(isset($_GET["myevents"])){myevents();exit;}
if(isset($_GET["search-myevents"])){myevents_search();exit;}
if(isset($_POST["SquidWatchDownSize"])){proxy_watchdog_file_size_save();exit;}
if(isset($_POST["SquidWatchUseDirect"])){proxy_watchdog_direct_save();exit;}
if(isset($_POST["SquidWatchPing"])){proxy_watchdog_ping_save();exit;}
if(isset($_POST["SquidWatchUrls"])){proxy_watchdog_sites_save();exit;}
if(isset($_POST["SquidWatchIface"])){proxy_watchdog_iface_save();exit;}


if(isset($_GET["proxy-watchdog-sites-js"])){proxy_watchdog_sites_js();exit;}
if(isset($_GET["proxy-watchdog-sites-popup"])){proxy_watchdog_sites_popup();exit;}

if(isset($_GET["proxy-watchdog-direct-js"])){proxy_watchdog_direct_js();exit;}
if(isset($_GET["proxy-watchdog-direct-popup"])){proxy_watchdog_direct_popup();exit;}

if(isset($_GET["proxy-watchdog-ping-js"])){proxy_watchdog_ping_js();exit;}
if(isset($_GET["proxy-watchdog-ping-popup"])){proxy_watchdog_ping_popup();exit;}

if(isset($_GET["proxy-watchdog-iface-js"])){proxy_watchdog_iface_js();exit;}
if(isset($_GET["proxy-watchdog-iface-popup"])){proxy_watchdog_iface_popup();exit;}

if(isset($_GET["proxy-watchdog-fsize-js"])){proxy_watchdog_file_size_js();exit;}
if(isset($_GET["proxy-watchdog-fsize-popup"])){proxy_watchdog_file_size_popup();exit;}

if(isset($_GET["table-incidents"])){table_incidents();exit;}
if(isset($_GET["search-incidents"])){table_incidents_search();exit;}

if(isset($_GET["proxy-watchdog-top"])){top_status();exit;}
if(isset($_GET["proxy-watchdog-flat"])){flat_config();exit;}
if(isset($_GET["proxy-watchdog-status"])){service_status();exit;}
if(isset($_GET["failedloginattempts-js"])){failed_logging_js();exit;}
if(isset($_GET["licenseserver-js"])){failed_license_server_js();exit;}
if(isset($_GET["licenseserver-popup"])){failed_license_server_popup();exit;}


if(isset($_GET["failed-logging"])){failed_logging_start();exit;}
if(isset($_GET["failed-login-search"])){failed_logging_search();exit;}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["trackadmin"])){search_admin();exit;}
if(isset($_GET["search-admin"])){search_admins();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["remove-events"])){remove_events_js();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}
if(isset($_POST["remove-events"])){remove_events_perform();exit;}
if(isset($_GET["sys-events"])){search_system();exit;}
if(isset($_GET["sys-events-block"])){search_system_block();exit;}
if(isset($_GET["button-fw-system-watchdog"])){watchdog_buttons_output();exit;}
page();

function ShowID_js():bool{
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
		return false;
	}
	// Pentest 2026-11-04 : ShowID-js etait concatene dans la requete.
	$id=intval($id);$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT subject FROM squid_admin_mysql WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
    if(!isset($_GET["function"])){$_GET["function"]="";}
	return $tpl->js_dialog($subject, "$page?ShowID=$id&function={$_GET["function"]}");
}
function failed_logging_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("{failedloginattempts}", "$page?failed-login-search=yes",1024);
}
function proxy_watchdog_file_size_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("{watchdog_file_size}", "$page?proxy-watchdog-fsize-popup=yes",750);
}
function proxy_watchdog_direct_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("{direct_confirmation}", "$page?proxy-watchdog-direct-popup=yes",750);
}
function proxy_watchdog_ping_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("Ping", "$page?proxy-watchdog-ping-popup=yes",750);
}
function proxy_watchdog_iface_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("{outgoing_interface}", "$page?proxy-watchdog-iface-popup=yes",750);
}
function proxy_watchdog_sites_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("{websites}", "$page?proxy-watchdog-sites-popup=yes",750);
}


function proxy_watchdog_direct_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchUseDirect",intval($_POST["SquidWatchUseDirect"]));
    return admin_tracks("Set {$_POST["SquidWatchUseDirect"]} watchdog testing direct mode");
}
function proxy_watchdog_ping_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchPing",intval($_POST["SquidWatchPing"]));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchPingAddr",trim($_POST["SquidWatchPingAddr"]));
    return admin_tracks("Save Proxy Watchdog Ping={$_POST["SquidWatchPingAddr"]} enable={$_POST["SquidWatchPing"]}");
}
function proxy_watchdog_iface_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();

    $SquidWatchIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchIface"));

    $form[]=$tpl->field_interfaces("SquidWatchIface","{outgoing_interface}",$SquidWatchIface,true);

    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside(null, $form,"","{apply}",
        "dialogInstance1.close();LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');",
        $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function proxy_watchdog_iface_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchIface",$_POST["SquidWatchIface"]);
    return admin_tracks("Save outgoing interface for proxy watchdog to {$_POST["SquidWatchIface"]}");
}
function proxy_watchdog_ping_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();

    $SquidWatchPing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchPing"));
    $SquidWatchPingAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchPingAddr");
    if(strlen($SquidWatchPingAddr)<3){
        $SquidWatchPing=0;
    }


    $form[]=$tpl->field_checkbox("SquidWatchPing","{enable}",$SquidWatchPing,true);
    $form[] = $tpl->field_text("SquidWatchPingAddr", "{ipaddr}", $SquidWatchPingAddr);


    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside(null, $form,"{ipv4_comma}","{apply}",
        "dialogInstance1.close();LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');",
        $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function proxy_watchdog_direct_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $SquidWatchUseDirect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchUseDirect"));


    $form[]=$tpl->field_checkbox("SquidWatchUseDirect","{direct_confirmation}",$SquidWatchUseDirect);
    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside(null, $form,"{direct_confirmation_explain}","{apply}",
        "dialogInstance1.close();LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');",
        $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function proxy_watchdog_file_size_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchDownSize",intval($_POST["SquidWatchDownSize"]));
    return admin_tracks("Set {$_POST["SquidWatchDownSize"]} for the watchdog bandwidth calculation");
}
function proxy_watchdog_file_size_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $SquidWatchDownSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchDownSize"));
    if($SquidWatchDownSize==0){
        $SquidWatchDownSize=1024;
    }

    $form[]=$tpl->field_numeric("SquidWatchDownSize","{watchdog_file_size} (KB)",$SquidWatchDownSize);
    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside(null, $form,"{watchdog_file_size_explain}","{apply}",
        "dialogInstance1.close();LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');",
        $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function proxy_watchdog_sites_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $sites=array();
    $c=0;
    $SquidWatchUrls=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchUrls")));
    foreach ($SquidWatchUrls as $dWatchUrl) {
        $dWatchUrl=trim($dWatchUrl);
        if(strlen($dWatchUrl)<4){
            continue;
        }
        $sites[]=$dWatchUrl;
        $c++;
    }
    if($c==0){
        $sites=defurls();
    }
    $form[]=$tpl->field_textareacode("SquidWatchUrls","",@implode("\n",$sites));
    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside(null, $form,"","{apply}",
        "dialogInstance1.close();LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');",
        $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function proxy_watchdog_sites_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchUrls",$_POST["SquidWatchUrls"]);
    return admin_tracks("Saving Proxy watchdog set of testing sites");
}
function failed_license_server_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("License server", "$page?licenseserver-popup=yes",800);

}
function failed_license_server_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LicensingServerError=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicensingServerError"));
    echo $tpl->div_error($LicensingServerError);
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{incidents}"]="$page?table-incidents=yes";
    $array["{reports}"]="fw.system.monit.php?incidents=yes";
    $array["{events}"]="$page?myevents=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function empty_js(){
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	$title="{system_events}";
	$tpl->js_confirm_empty($title,"empty","yes","{$_GET["function"]}();");
	
}

function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$q->QUERY_SQL("DROP TABLE squid_admin_mysql");
    admin_tracks("Empty table of system events");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
	
}

// Nom d'une fonction JavaScript de rappel : identifiant seulement.
function watchdog_callback_name($v):string{
    $v=trim(strval($v));
    if(!preg_match('#^[A-Za-z0-9_]{1,128}$#',$v)){return "";}
    return $v;
}
function watchdog_buttons_output():bool{
    if($_GET["button-fw-system-watchdog"]==""){return true;}
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=watchdog_callback_name($_GET["function"] ?? "");
    $topbuttons=array();
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1) {
        $js="LoadAjaxSilent('search-block','$page?sys-events-block=yes&method=squid');";
        $topbuttons[] = array($js, ico_filter, "{APP_SQUID}");
    }
    $topbuttons[]=array("Loadjs('fw.export.sqlite.php?table=squid_admin_mysql&file=system_events');",ico_csv,"{export}");
    $topbuttons[]=array("Loadjs('$page?empty-js=yes&function=$function')",ico_trash,"{empty}");
    echo $tpl->table_buttons($topbuttons);
    return true;
}
function ShowID(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 E-01 : ShowID etait concatene sans guillemets (UNION SELECT).
	$ShowID=intval($_GET["ShowID"]);
	$sql="SELECT * FROM squid_admin_mysql WHERE ID=$ShowID";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	// Pentest 2026-11-04 : contenu de l evenement (donnee externe) affiche sans encodage (XSS stockee).
	$content=html_safe_fragment($tpl->_ENGINE_parse_body($ligne["content"]));
	$content=nl2br($content);
	echo "<p>$content</p>";

	$filename=$ligne["filename"];
	$line=$ligne["line"];
    $html[]="<div style='margin-top:10px' id='progress-remove-event'></div>";
	$html[]="<div style='text-align:right;margin-top:10px'>";

    $users=new usersMenus();

    if($users->AsSystemAdministrator) {
        $html[] = $tpl->button_autnonome("{remove_all_same}", "Loadjs('$page?remove-events=yes&filename=$filename&line=$line&function={$_GET["function"]}');",
            "fas fa-trash-alt", "AsSystemAdministrator", 0, "btn-danger");
    }
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body($html);

}
function remove_events_js(){
    $filename=$_GET["filename"];
    $line=$_GET["line"];
    $tpl=new template_admin();
    $funcadd=null;

    if($_GET["function"]<>null){
        $funcadd="{$_GET["function"]}();";
    }

    $tpl->js_confirm_empty("{remove_all_same} $filename ($line)","remove-events","$filename;$line",
        "BootstrapDialog1.close();{$funcadd}");
}
function remove_events_perform(){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $tt=explode(";",$_POST["remove-events"]);
    $filename=$tt[0];
    $line=intval($tt[1]);

    // Pentest 2026-11-04 : remove-events (filename) etait concatene dans la requete.
    $q->QUERY_SQL_PARAMS("DELETE FROM squid_admin_mysql WHERE filename=? AND line=?",array((string)$filename,(string)$line));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
    admin_tracks("Removed all system events with $filename filename and line $line");

}
function search_system():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $scriptname=$_GET["scriptname"];
    $html[]="<div style='margin-top:15px' id='search-block'></div>";
    $html[]="<script>LoadAjaxSilent('search-block','$page?sys-events-block=yes&scriptname=$scriptname');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function search_system_block():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $method=null;
    if(isset($_GET["method"])) {
        $method = $_GET["method"];
    }

    $html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/system_events.db","squid_admin_mysql","table-loader","&table=yes&scriptname={$_GET["scriptname"]}&method=$method");
    $html[]="<div id='table-loader'></div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function search_admin(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $method=null;
    if(isset($_GET["method"])) {
        $method = "&method=".$_GET["method"];
    }
    $scriptanme=null;
    if(isset($_GET["scriptname"])){
        $scriptanme="&scriptname={$_GET["scriptname"]}";
    }

    $html[]="<div style='margin-top:15px'>";
    $html[]=$tpl->search_block($page,null,null,"table-sysadmin","&search-admin=yes$scriptanme$method");
    $html[]="<div id='table-sysadmin'></div>";
    $html[]="</div>";
    $html[]="<script>LoadAjaxSilent('button-fw-system-watchdog','$page?button-fw-system-watchdog=');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_PROXY_WATCHDOG}",ico_watchdog,"{APP_PROXY_WATCHDOG_EXPLAIN}",
        "$page?tabs=yes","events","progress-squid-watchdog-restart",false,"table-start");

if(isset($_GET["main-page"])){
	$tpl=new template_admin(null, $html);
	echo $tpl->build_firewall();
	return true;
}
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function failed_logging_search(){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $tpl=new template_admin();
    $t=time();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{member}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{date}</th>";
    $html[]="</tr></thead><tbody id='pxwd-btmp-rows'>";

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(intval($search["MAX"])>1500){$search["MAX"]=1500;}
    $ZZZ="";
    // Pentest 2026-11-04 : search etait concatene dans la requete.
    $binds=array();
    if(strlen($search["Q"])>1){
        $s=$search["Q"];
        $ZZZ="WHERE (user LIKE ? OR terminal LIKE ? OR timestamp LIKE ? OR host LIKE ?)";
        $binds=array($s,$s,$s,$s);
    }
    $smax=intval($search["MAX"]);

    $sql="SELECT *  FROM btmp_records $ZZZ ORDER BY timestamp DESC LIMIT $smax";
    $results=$q->QUERY_SQL_PARAMS($sql,$binds);

    $n=0;
    foreach ($results as $index=>$ligne){
        $html[]=pxwd_btmp_row(md5(serialize($ligne)),
            (string)$ligne["user"],(string)$ligne["host"],(string)$ligne["terminal"],
            $tpl->time_to_date(strtotime($ligne["timestamp"]),true));
        $n++;
    }
    if($n==0){$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n</span>";
    $html[]="<span class='awc-pg' id='pxwd-btmp-pager'></span></div></div></div>";
    $headsjs="";
    if(isset($_GET["tiny"])){
        $TINY_ARRAY["TITLE"]="{failedloginattempts}";
        $TINY_ARRAY["ICO"]=ico_eye;
        $TINY_ARRAY["EXPL"]="{BTMMPExplains}";
        $TINY_ARRAY["BUTTONS"]="";
        $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    }

    $html[]="
	<script>
	$headsjs
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(50,"pxwd-btmp-rows","pxwd-btmp-pager");
}

/**
 * Extracts the legacy "NNN events" / "limit NNN" / "max NNN" row-count
 * directives from a search string (same regexes as query_pattern()) and
 * returns array(cleaned search text, max). Filtering itself now runs in
 * articarest GET /localadmins/admintracks.
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

function search_admins(){
    $tpl=new template_admin();
    $t=$_GET["t"];
    if(!is_numeric($t)){$t=time();}
    $date=$tpl->_ENGINE_parse_body("{date}");

    $t=time();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{events}</th>";
    $html[]="<th>$date</th>";
    $html[]="</tr></thead><tbody id='pxwd-adm-rows'>";


    list($searchText,$max)=admintracks_search_params(strval($_GET["search"] ?? ""));
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/localadmins/admintracks?search=".urlencode($searchText)."&max=$max")),true);
    $results=array();
    $apierror=null;
    if(!is_array($resp) || empty($resp["success"])){
        $apierror=is_array($resp)?strval($resp["error"] ?? "unknown error"):"daemon unreachable";
    }else{
        $results=is_array($resp["data"] ?? null)?$resp["data"]:array();
    }
    $sql="search: $searchText (max $max)";


   if($apierror!==null){

        echo "<div class='alert alert-danger'>".htmlspecialchars($apierror)."<br><strong><span style='font-family:monospace'>".htmlspecialchars(strval($_GET["search"]))."</span></strong></div>";
    }


    $n=0;
    foreach ($results as $index=>$ligne){
        // Pentest 2026-11-04 : operation (donnee externe) affiche sans encodage (XSS stockee).
        $html[]=pxwd_admin_row(html_safe_fragment($tpl->_ENGINE_parse_body($ligne["operation"])),
            (string)$ligne["username"],(string)$ligne["ipaddr"],
            $tpl->time_to_date($ligne["time"],true));
        $n++;
    }
    if($n==0){$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n</span>";
    $html[]="<span class='awc-pg' id='pxwd-adm-pager'></span></div></div></div>";
    $html[]="
	<script>
	
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."

</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function failed_logging_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&failed-login-search=yes&tiny=yes");
    echo "</div>";
}

function widget_bandwidth():string{
    $tpl=new template_admin();
    $SquidWatchAverageBps=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchAverageBps");
    $SquidWatchDownSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchDownSize"));

    if($SquidWatchDownSize==0){
        $SquidWatchDownSize=1024;
    }
    $btn["ico"]=ico_chart_line;
    $btn["name"]="{statistics}";
    $btn["js"]="blur();";

    if($SquidWatchDownSize<512){
        return $tpl->widget_h("grey", ico_download, "{disabled}", "{bandwidth}", $btn);
    }

    if(intval($SquidWatchAverageBps)==0) {
        return $tpl->widget_h("grey", ico_download, "{none}", "{bandwidth}", $btn);
    }

    $btn["ico"]=ico_chart_line;
    $btn["name"]="{statistics}";
    $btn["js"]="Loadjs('fw.rrd.php?img=squidwatch-bandwidth');";


    $mb_per_sec = $SquidWatchAverageBps / 8;
    $band=sprintf("%.2f MB/s", $mb_per_sec);
    return  $tpl->widget_h("green", ico_download, $band, "{bandwidth} $SquidWatchAverageBps Mbps",$btn);

}

function top_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Mise en page en flex: trois tuiles figees a 33% se compressaient sur un
    // ecran etroit au lieu de se replier.
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:5px;margin-top:-5px;align-items:flex-start'>";
    $html[]="<div style='flex:1 1 280px;min-width:0'>";
    $html[]=widget_bandwidth();
    $html[] ="</div>";
    $html[]="<div style='flex:1 1 280px;min-width:0'>";

    $btn["ico"]=ico_chart_line;
    $btn["name"]="{statistics}";
    $btn["js"]="blur();";
    $SquidWatchCurLantency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchCurLantency");
    if(intval($SquidWatchCurLantency)==0) {
        $html[] = $tpl->widget_h("grey", ico_clock, "{none}", "{latency}",$btn);
    }else{
        $btn=array();
        if(is_file("img/squid/squidwatch-latency-hourly.flat.png")){
            $btn["ico"]=ico_chart_line;
            $btn["name"]="{statistics}";
            $btn["js"]="Loadjs('fw.rrd.php?img=squidwatch-latency');";
        }
        $color="green";
        $addon="";
        if ($SquidWatchCurLantency>1000) {
            $seconds = floor($SquidWatchCurLantency / 1000);
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            $color="yellow";
            $addon="&nbsp;$minutes {minutes} $remainingSeconds {seconds}";
        }

        $html[] = $tpl->widget_h($color, ico_clock, "{$SquidWatchCurLantency}ms", "{latency}$addon",$btn);
    }
    $html[] ="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function table_incidents():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>&nbsp;</div>";
    echo $tpl->search_block($page,"","","","&search-incidents=yes");
    return true;
}
function table_incidents_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{events}</th>";
    $html[]="<th>{date}</th>";
    $html[]="</tr></thead><tbody id='pxwd-perf-rows'>";

    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(intval($search["MAX"])>1500){$search["MAX"]=1500;}
    $sql="SELECT *  FROM perfs_events {$search["Q"]} ORDER BY zDate DESC LIMIT {$search["MAX"]}";
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        // $_GET partait BRUT dans la page, et <code> est proscrit par le theme.
        echo "<div class='alert alert-danger'>".htmlspecialchars((string)$q->mysql_error)
            ."<br><strong><span style='font-family:monospace'>"
            .htmlspecialchars((string)($_GET["search"] ?? ""))."</span></strong></div>";
        return false;
    }

    $n=0;
    foreach ($results as $index=>$ligne){
        // Pentest 2026-11-04 : sujet (donnee externe) affiche sans encodage (XSS stockee).
        $html[]=pxwd_perf_row(html_safe_fragment($tpl->_ENGINE_parse_body($ligne["subject"])),
            (string)$ligne["sfunction"],intval($ligne["ztype"]),
            $tpl->time_to_date($ligne["zDate"],true));
        $n++;
    }
    if($n==0){$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";}
    // Le </table> manquait: le tableau restait ouvert dans le DOM.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n</span>";
    $html[]="<span class='awc-pg' id='pxwd-perf-pager'></span></div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(50,"pxwd-perf-rows","pxwd-perf-pager");
    return true;

}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Mise en page en flex. La colonne de gauche etait figee a 240px alors que la
    // tuile de service qu'elle recoit en mesure 335, et le </td> de droite manquait.
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:15px;align-items:flex-start;margin-top:10px'>";
    $html[]="<div style='flex:0 0 auto'><div id='proxy-watchdog-status'></div></div>";
    $html[]="<div style='flex:1 1 420px;min-width:0'>";
    $html[]="<div id='proxy-watchdog-top'></div>";
    $html[]="<div id='proxy-watchdog-flat'></div>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="<script>";
    $Ping=$tpl->RefreshInterval_js("proxy-watchdog-status",$page,"proxy-watchdog-status=yes");
    $html[]="LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');";
    $html[]=$Ping;
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}
function service_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableProxyWatchdog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyWatchdog"));
    if($EnableProxyWatchdog==0){
        echo $tpl->widget_grey("{APP_PROXY_WATCHDOG}","{not_installed}");
        return true;
    }

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy-watchdog/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));


    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));
             return true;
        }
    }
    if($json->Status) {

        $jsRestart=$tpl->framework_buildjs("/proxy-watchdog/restart","proxy.watchdog.progress","proxy.watchdog.progress.log","progress-squid-watchdog-restart","LoadAjax('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');");

        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo  $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_PROXY_WATCHDOG", $jsRestart));
    }

    echo "<script>
        LoadAjaxSilent('proxy-watchdog-top','$page?proxy-watchdog-top=yes');
        LoadAjaxSilent('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');  
      </script>";

    return true;
}

function flat_config():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $EnableProxyWatchdog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyWatchdog"));
    if($EnableProxyWatchdog==1){
        $js=$tpl->framework_buildjs("/proxy-watchdog/uninstall","proxy.watchdog.progress","proxy.watchdog.progress.log","progress-squid-watchdog-restart","LoadAjax('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');");
        $topbuttons[] = array($js, ico_trash, "{disable_watchdog_eth}");
    }else{
        $js=$tpl->framework_buildjs("/proxy-watchdog/install","proxy.watchdog.progress","proxy.watchdog.progress.log","progress-squid-watchdog-restart","LoadAjax('proxy-watchdog-flat','$page?proxy-watchdog-flat=yes');");

        $topbuttons[] = array($js, ico_cd, "{enable_watchdog_eth}");
    }

    $SquidWatchIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchIface"));
    $tpl->table_form_field_js("Loadjs('$page?proxy-watchdog-iface-js=yes');");
    if(strlen($SquidWatchIface)<3){
        $tpl->table_form_field_bool("{outgoing_interface}",0,ico_nic);
    }else{
        $tpl->table_form_field_text("{outgoing_interface}",$SquidWatchIface,ico_nic);
    }

    $SquidWatchDownSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchDownSize"));
    $SquidWatchUseDirect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchUseDirect"));
    $SquidWatchDownSizeText="{$SquidWatchDownSize}KB";
    if($SquidWatchDownSize==0){
        $SquidWatchDownSizeText=1024;
    }
    if($SquidWatchDownSize>1023) {
        $SquidWatchDownSizeText = FormatBytes($SquidWatchDownSize);
    }

    $dWatchUrls=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchUrls"));
    $c=0;
    foreach ($dWatchUrls as $dWatchUrl) {
        $dWatchUrl=trim($dWatchUrl);
        if(strlen($dWatchUrl)<4){
            continue;
        }
        $c++;
    }
    if($c==0){
        $c=count(defurls());
    }
    $tpl->table_form_field_js("Loadjs('$page?proxy-watchdog-sites-js=yes');");
    $tpl->table_form_field_text("{websites}","$c {records}",ico_earth);

    $tpl->table_form_field_js("Loadjs('$page?proxy-watchdog-fsize-js=yes');");
    if($SquidWatchDownSize>512) {
        $tpl->table_form_field_text("{watchdog_file_size}", $SquidWatchDownSizeText, ico_weight);
    }else{
        $tpl->table_form_field_bool("{watchdog_file_size}", 0, ico_weight);
    }
    $tpl->table_form_field_js("Loadjs('$page?proxy-watchdog-direct-js=yes');");
    $tpl->table_form_field_bool("{direct_confirmation}",$SquidWatchUseDirect,ico_clouds);

    $SquidWatchPing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchPing"));
    $SquidWatchPingAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchPingAddr");
    $SquidWatchPingResults=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchPingResults"));
    if(strlen($SquidWatchPingAddr)<3){
        $SquidWatchPing=0;
    }

    $tpl->table_form_field_js("Loadjs('$page?proxy-watchdog-ping-js=yes');");
    if($SquidWatchPing==0){
        $tpl->table_form_field_bool("Ping",0,ico_monitor);
    }else{
        $tpl->table_form_field_bool("Ping",1,ico_monitor);
        if($SquidWatchPing==1) {
            if(is_array($SquidWatchPingResults)){
                foreach ($SquidWatchPingResults as $ipaddr=>$latency) {
                    if(is_file("img/squid/icmp-$ipaddr-day.flat.png")){
                        $tpl->table_form_field_js("Loadjs('fw.rrd.php?img=icmp-$ipaddr');");
                        $tpl->table_form_field_text("Ping","$ipaddr ({$latency}ms)",ico_monitor);
                    }else{
                        $tpl->table_form_field_js("");
                        $tpl->table_form_field_bool("$ipaddr",0,ico_monitor);
                    }
                }
            }else{
                $tpl->table_form_field_text("Ping",$SquidWatchPingAddr,ico_monitor);
            }

        }
    }

    $SquidWatchDogDNS=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchDogDNS"));
    if (json_last_error() !== JSON_ERROR_NONE) {
        $SquidWatchDogDNS=null;
    }

    if(!is_null($SquidWatchDogDNS)){

        foreach ($SquidWatchDogDNS as $dns=>$json) {
            $timeValue=$json->time;
            $nowTimestamp = time();
            $differenceInSeconds = $nowTimestamp - $timeValue;
            $differenceInMinutes = floor($differenceInSeconds / 60);
            if($differenceInMinutes>5){
                continue;
            }
            $latency_text="0ms";
            $latency=$json->latency;
            if ($latency > 0) {
                $latency_text = "{$latency}ms";
            }
            if($json->aserror){
                $tpl->table_form_field_text("DNS $dns","$json->error $latency_text",ico_error,true);
                continue;
            }
            $tpl->table_form_field_text("DNS $dns","$latency_text",ico_monitor);
        }

    }



    $html[]=$tpl->table_form_compile();
    $PROXYWATCH_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYWATCH_VERSION");
    if(strlen($PROXYWATCH_VERSION)>2){
        $PROXYWATCH_VERSION="v$PROXYWATCH_VERSION";
    }
    $TINY_ARRAY["TITLE"]="{APP_PROXY_WATCHDOG} $PROXYWATCH_VERSION";
    $TINY_ARRAY["ICO"]=ico_watchdog;
    $TINY_ARRAY["EXPL"]="{APP_PROXY_WATCHDOG_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function defurls():array{
    return array("https://www.google.com", "https://maps.google.com", "https://news.google.com", "https://scholar.google.com", "https://translate.google.com", "https://accounts.google.com", "https://mail.google.com", "https://photos.google.com", "https://docs.google.com", "https://drive.google.com", "https://play.google.com", "https://video.google.com", "https://speed.cloudflare.com/cdn-cgi/trace", "https://1.1.1.1", "https://8.8.8.8", "https://example.com", "https://www.cloudflare.com", "https://www.facebook.com", "https://api.github.com", "https://www.oracle.com/downloads", "https://www.google.com");

}
function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$eth_sql=null;
	$token=null;
	$class=null;

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$date=$tpl->_ENGINE_parse_body("{date}");
	$title=$tpl->_ENGINE_parse_body("{IDS} {events}");
	$events=$tpl->javascript_parse_text("{events}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
    $method=$_GET["method"];
	
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$nic=new networking();
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	if($_GET["eth"]==null){$class=" active";}

	$t=time();
	$add="Loadjs('$page?ruleid-js=0$token',true);";


    //LoadAjaxSilent('search-block','$page?sys-events-block=yes&scriptname=$scriptname');

    // Pentest 2026-11-04 M-01 : les boutons faisaient l'aller-retour par le
    // client en base64 et la page renvoyait ce HTML tel quel. Le client ne
    // transmet plus que le nom de la fonction de rappel, les boutons sont
    // reconstruits par watchdog_buttons_output().
    $buttons_encoded="yes&function=".urlencode(watchdog_callback_name($_GET["function"] ?? ""));
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $buttons_encoded=null;
    }



	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";

	$aliases["src_ip"]="ipaddr";
	$aliases["ipaddr"]="ipaddr";
	$aliases["address"]="ipaddr";
	$aliases["mac"]="mac";
	$aliases["text"]="subject,function,filename";
	if(preg_match("#(file|script|fichier|process|processus)([\s|=]+)([0-9a-z\.]+)#i", $_GET["search"],$re)){
		$_GET["search"]=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $_GET["search"]);
		$_GET["scriptname"]=$re[3];
	}
	
	$critic_aliases="critic|urgence|urgency|emergency|important";
	if(preg_match("#($critic_aliases)#", $_GET["search"])){
		$ADDON="severity=0";
		$tt=explode("|",$critic_aliases);
		foreach ($tt as $word){
			$_GET["search"]=str_replace($word, "", $_GET["search"]);
		}
	}
	
	$warning_aliases="warn|warning|attention";
	if(preg_match("#($warning_aliases)#", $_GET["search"])){
		$ADDON="severity=1";
		$tt=explode("|",$warning_aliases);
		foreach ($tt as $word){
			$_GET["search"]=str_replace($word, "", $_GET["search"]);
		}
	}

	$info_aliases="info|information";
	if(preg_match("#($info_aliases)#", $_GET["search"])){
		$ADDON="severity=2";
		$tt=explode("|",$info_aliases);
		foreach ($tt as $word){
			$_GET["search"]=str_replace($word, "", $_GET["search"]);
		}
	}
	$_GET["search"]=trim($_GET["search"]);
	$_SESSION["SYSEVS_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),$aliases,$ADDON);
	

	
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	@chmod("/home/artica/SQLITE/system_events.db", 0644);
	@chown("/home/artica/SQLITE/system_events.db", "www-data");

    if(intval($search["MAX"])>1500){$search["MAX"]=1500;}
	$sql="SELECT ID,zDate,subject,severity,function,line,filename, LENGTH(content) as content  FROM squid_admin_mysql {$search["Q"]} ORDER BY zDate DESC LIMIT {$search["MAX"]}";

    if($method=="squid"){
        $_GET["scriptname"]="exec.squid.disable.php,class.status.squid.inc,exec.squid.watchdog.php,squid-service,web-filtering,squid,class.squid.automatic-tasks.inc,exec.squid.php";

    }
	
	if($_GET["scriptname"]<>null){
		// $_GET partait BRUT dans le WHERE. Un nom de fichier ne contient que ces
		// caracteres; tout le reste est retire avant la construction de la requete.
		$sn=preg_replace('/[^A-Za-z0-9_.,\-]/','',(string)$_GET["scriptname"]);
		$WHERE="WHERE filename='$sn'";
		if(strpos($sn, ",")>0){
			$scripts=explode(",",$sn);
			$OR=array();
			foreach ($scripts as $ffile){$OR[]="(filename='{$ffile}')";}
			$WHERE="WHERE (".@implode(" OR ", $OR).")";
		}
		
		$sql="SELECT * FROM (SELECT ID,zDate,subject,severity,function,line,filename, 
		LENGTH(content) as content  FROM squid_admin_mysql $WHERE ORDER BY zDate DESC LIMIT {$search["MAX"]}) as t {$search["Q"]}";
	}
	
	
	$results=$q->QUERY_SQL($sql);
	

	
	$html[]="<th>$events</th>";
	$html[]="<th>$daemon</th>";
	$html[]="<th>$date</th>";
	$html[]="</tr></thead><tbody id='pxwd-sq-rows'>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		
		// $_GET partait BRUT dans la page, et <code> est proscrit par le theme.
		echo "<div class='alert alert-danger'>".htmlspecialchars((string)$q->mysql_error)
			."<br><strong><span style='font-family:monospace'>"
			.htmlspecialchars((string)($_GET["search"] ?? ""))."</span></strong></div>";
	}
	
	$severityCL[0]="label-danger";
	$severityCL[1]="label-warning";
	$severityCL[2]="label-primary";
	
	$severityTX[0]="text-danger";
	$severityTX[1]="text-warning";
	$severityTX[2]="text-primary";
	$curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";
	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$TRCLASS=null;
		$text_class=null;
		$id=md5(serialize($ligne));
		$zdate=$tpl->time_to_date($ligne["zDate"],true);
		$severity_class=$severityCL[$ligne["severity"]];
		$js="Loadjs('$page?ShowID-js={$ligne["ID"]}&function={$_GET["function"]}')";
		$link="<span><i class='fa fa-search ' id='$id'></i>&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"$js\" class='{$severityTX[$ligne["severity"]]}' style='font-weight:bold'>";
	    if(!isset($ligne["hostname"])){$ligne["hostname"]=null;}
		if($ligne["content"]==0){$link="<span style='font-weight:bold'>";$js="blur()";}
		
		if(preg_match("#\/var\/lib\/ufdbartica\/([0-9]+)#", $ligne["subject"],$re)){
			$zcat=new mysql_catz();
			$_CATEGORIES_NAME=$zcat->CategoryIntToStr($re[1]);
			$ligne["subject"]=str_replace("/var/lib/ufdbartica/{$re[1]}", $_CATEGORIES_NAME, $ligne["subject"]);
		}
		
		
		
		
		// Pentest 2026-11-04 : sujet, hote, fonction (donnee externe) affiche sans encodage (XSS stockee).
		$text=$link.html_safe_fragment($tpl->_ENGINE_parse_body($ligne["subject"]))."</a></span>
		<div style='font-size:10px'>".$tpl->_ENGINE_parse_body("{host}").":".req_html($ligne["hostname"])." ".$tpl->_ENGINE_parse_body("{function}").":".req_html($ligne["function"]).", ".$tpl->_ENGINE_parse_body("{line}").":".req_html($ligne["line"])."</div>";
		if(strpos(" ".$ligne["filename"], "/")>0){$ligne["filename"]=basename($ligne["filename"]);}
		
		$html[]=pxwd_squid_row($text,(string)$ligne["filename"],intval($ligne["severity"]),$zdate,$js);
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span class='awc-pg' id='pxwd-sq-pager'></span></div></div></div>";
	// La requete SQL etait affichee sous le tableau: reste de mise au point.
	$html[]="
	<script>
	LoadAjaxSilent('button-fw-system-watchdog','$page?button-fw-system-watchdog=$buttons_encoded');
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

			echo awc_table_style().@implode("\n", $html)
				.awc_pager_js(50,"pxwd-sq-rows","pxwd-sq-pager");

}
function myevents():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>&nbsp;</div>";
    echo $tpl->search_block($page,"","","","&search-myevents=yes");
    return true;
}
function myevents_search(){
    $tpl=new template_admin();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{events}</th>";
    $html[]="<th class='center'>{level}</th>";
    $html[]="<th>{date}</th>";
    $html[]="</tr></thead><tbody id='pxwd-ev-rows'>";

    $MAIN=$tpl->format_search_protocol($_GET["search"]);

    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    VERBOSE("/proxy/watchdog/events/$rp/$search",__LINE__);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/watchdog/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }
    // Le fond porte seul le sens: awc-badge impose deja color:#fff.
    $LEVELS["info"] ="<span class='awc-badge awc-badge-off'>INFO</span>";
    $LEVELS["warn"] ="<span class='awc-badge' style='background:#865300'>WARN</span>";
    $LEVELS["error"]="<span class='awc-badge' style='background:#ba1a1a'>ERROR</span>";
    $LEVELS["fatal"]="<span class='awc-badge' style='background:#ba1a1a'>FATAL</span>";
    $LEVELS["debug"]="<span class='awc-badge awc-badge-off'>DEBUG</span>";
    $LEVELS["trace"]="<span class='awc-badge awc-badge-off'>TRACE</span>";

    $st1="style='width:1%' nowrap";

    if(!property_exists($json,"Events")){
        echo $tpl->div_error("{error}<br>Events is not a property of the json object!<hr>");
        return false;
    }

    foreach ($json->Events as $line){
        $line=trim($line);
        if(is_null($line)){continue;}
        $jsonline=json_decode($line);
        if (json_last_error() > JSON_ERROR_NONE) {
            VERBOSE(json_last_error_msg(),__LINE__);
            continue;
        }



        $zico=$LEVELS[$jsonline->level];
        $FTime=$tpl->time_to_date($jsonline->time,true);
        if(!property_exists($jsonline,"message")){
            continue;
        }
        // Pentest 2026-11-04 : message du journal (donnee externe) affiche sans encodage (XSS stockee).
        $line=$jsonline->message;
        if(is_null($line)){
            continue;
        }
        $line=req_html($line);
        $class=null;

        if(preg_match("#(Failed password|Invalid user|user unknown|fatal|error)#i", $line)){
            $class="text-danger";
            $line="<span class='text-danger'>$line</span>";
        }
        if(preg_match("#(Success)#i", $line)){
            $line="<span class='text-success'>$line</span>";
            $class="text-success";
        }


        if(preg_match("#(warning notify|restarting|authentication failures|authentication failure|Did not receive identification string)#i", $line)){
            $line="<span class='text-warning'>$line</span>";
        }
        if(strpos("   $line","Latencies.go[Latencies.CheckBandwidth")>0){
            $line=str_replace("Latencies.go[Latencies.CheckBandwidth","[",$line);
            $line="<span class='label label-success'>Bandwidth</span>&nbsp;$line";
        }
        if(strpos("   $line","Latencies.go[Latencies.PingTarget:")>0){
            $line=str_replace("Latencies.go[Latencies.PingTarget:","[",$line);
            $line="<span class='label label-success'>PING</span>&nbsp;$line";
        }
        if(strpos("   $line","Latencies.go[Latencies.ExpingWeb:")>0){
            $line=str_replace("Latencies.go[Latencies.ExpingWeb:","[",$line);
            $line="<span class='label label-success'>{latency}</span>&nbsp;$line";
        }

        if(strpos("   $line","main.go[main.main:")>0){
            $line=str_replace("main.go[main.main:","[",$line);
            $line="<span class='label label-default'>Daemon</span>&nbsp;$line";
        }

        if(strpos("   $line","main.go[main.runDaemon")>0){
            $line=str_replace("main.go[main.runDaemon","[",$line);
            $line="<span class='label label-default'>Daemon</span>&nbsp;$line";
        }
        if(strpos("   $line","main.go[main.BecomeDaemon")>0){
            $line=str_replace("main.go[main.BecomeDaemon","[",$line);
            $line="<span class='label label-default'>Daemon</span>&nbsp;$line";
        }

        if(strpos("   $line","main.go[main.gracefulShutdown")>0){
            $line=str_replace("main.go[main.gracefulShutdown","[",$line);
            $line="<span class='label label-default'>Daemon</span>&nbsp;$line";
        }

        $html[]=pxwd_event_row($line,$zico,$FTime,$class);
    }
    $jstiny="";
    $html[]="</tbody></table></div></div></div>";
/*
    $TINY_ARRAY["TITLE"]="{legal_logs}: {events} &laquo;$search&raquo;";
    $TINY_ARRAY["ICO"]="fas fa-eye";
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="logs-rotate";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

*/

    $html[]="<script>NoSpinner();$jstiny</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(100,"pxwd-ev-rows","pxwd-ev-pager");
}

/**
 * Une tentative de connexion refusee (btmp): identifiant en identite, terminal et
 * adresse en sous-titre. Ces trois champs viennent du journal systeme -- donc
 * d'un tiers -- et etaient rendus BRUTS.
 */
// Pentest 2026-11-04 : utilisateur, terminal, adresse, fonction, fichier (donnee externe) affiche sans encodage (XSS stockee).
function pxwd_btmp_row($id,$username,$ipaddr,$terminal,$zdate):string{
    $h ="<tr id='$id'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico awc-ico-off'><i class='fas fa-user-lock'></i></span>";
    $h.="<span><span class='awc-name'>".req_html($username)."</span>";
    if(trim($terminal)!==""){$h.="<div class='awc-meta'>".req_html($terminal)."</div>";}
    $h.="</span></div></td>";
    $h.="<td class='awc-mono'>".req_html($ipaddr)."</td>";
    $h.="<td class='awc-mono'>".req_html($zdate)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Une operation d'administration: l'operation est l'identite (c'est ce qu'on
 * lit), l'auteur et son adresse passent en sous-titre. Le libelle est deja
 * traduit par l'appelant, d'ou l'absence d'echappement.
 */
function pxwd_admin_row($text,$username,$ipaddr,$zdate):string{
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-user-shield'></i></span>";
    $h.="<span><span class='awc-name' style='font-weight:400'>$text</span>";
    $h.="<div class='awc-meta'>".req_html(trim($username." - ".$ipaddr," -"))."</div>";
    $h.="</span></div></td>";
    $h.="<td class='awc-mono'>".req_html($zdate)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Un evenement de surveillance: le sujet est l'identite, la fonction d'origine
 * passe en sous-titre. $ztype vaut 1 pour un controle passe, 0 pour un incident.
 */
function pxwd_perf_row($text,$sfunction,$ztype,$zdate):string{
    $ok=($ztype==1);
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($ok?"awc-ico":"awc-ico awc-ico-off")."'>";
    $h.="<i class='fas ".($ok?"fa-circle-check":"fa-triangle-exclamation")."'></i></span>";
    $h.="<span><span class='awc-name' style='font-weight:400'>";
    $h.=($ok?"<span class='awc-badge awc-badge-on'>OK</span>":"<span class='awc-badge' style='background:#ba1a1a'>{issue}</span>");
    $h.=" $text</span>";
    if(trim($sfunction)!==""){$h.="<div class='awc-meta'>".req_html($sfunction)."</div>";}
    $h.="</span></div></td>";
    $h.="<td class='awc-mono'>".req_html($zdate)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Un evenement du journal du proxy: le sujet est l'identite, le fichier source
 * et la date suivent. La date etait rendue dans une pastille de severite occupant
 * toute la colonne -- le code couleur porte maintenant sur la puce d'identite.
 * $text est deja mis en forme (lien de zoom, contexte) par l'appelant.
 */
function pxwd_squid_row($text,$filename,$severity,$zdate,$js):string{
    $bg=($severity==0)?"#ba1a1a":(($severity==1)?"#865300":"#006a62");
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico' style='background:$bg;color:#fff'><i class='fas fa-triangle-exclamation'></i></span>";
    $h.="<span><span class='awc-name' style='font-weight:400'>$text</span></span>";
    $h.="</div></td>";
    $h.="<td class='awc-mono'>".req_html($filename)."</td>";
    $h.="<td class='awc-mono'><a href=\"javascript:blur();\" style='color:inherit' OnClick=\"$js\">"
        .req_html($zdate)."</a></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Une ligne du journal du service: le message est l'identite (c'est ce qu'on lit),
 * le niveau et l'horodatage suivent. $line est deja mis en forme par l'appelant
 * (mise en evidence des erreurs, etiquettes de sous-systeme).
 */
function pxwd_event_row($line,$level,$ftime,$class):string{
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-file-lines'></i></span>";
    $h.="<span><span class='awc-name $class' style='font-weight:400'>$line</span></span>";
    $h.="</div></td>";
    $h.="<td class='center'>$level</td>";
    $h.="<td class='awc-mono'>".req_html((string)$ftime)."</td>";
    $h.="</tr>";
    return $h;
}

