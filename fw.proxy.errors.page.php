<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["form-js"])){parameters_js();exit;}
if(isset($_GET["form-popup"])){parameters_popup();exit;}
if(isset($_POST["SquidGuardDenyConnect"])){SquidGuardDenyConnect_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["web-error-page-middle-section"])){web_error_page_middle_section();exit;}
if(isset($_POST["WebErrorPageServiceInDebug"])){web_error_page_save();exit;}
if(isset($_POST["UfdbUseInternalServiceEnableSSL"])){web_error_page_save();exit;}


if(isset($_POST["SquidTemplateSimple"])){SquidTemplateSimple();exit;}
if(isset($_GET["service-status-start"])){service_status_start();exit;}
if(isset($_GET["service-status-events"])){service_status_events();exit;}
if(isset($_GET["service-status-events-search"])){service_status_events_search();exit;}
if(isset($_GET["SquidGuardDenyConnect-js"])){SquidGuardDenyConnect_js();exit;}
if(isset($_GET["SquidGuardDenyConnect-popup"])){SquidGuardDenyConnect_popup();exit;}
if(isset($_GET["form-squid-templates-js"])){form_squid_templates_params_js();exit;}
if(isset($_GET["form-squid-templates-popup"])){form_squid_templates_params_popup();exit;}
if(isset($_GET["form-ssl-js"])){form_ssl_js();exit;}
if(isset($_GET["form-ssl-popup"])){form_ssl_popup();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$TITLE="{errors_pages}";
	$sock=new sockets();
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==0){$TITLE="{templates}";}

    $html=$tpl->page_header($TITLE,"fas fa-file-alt",
        "{epg_page_explain}","$page?tabs=yes",
        "proxy-errors","progress-pxtempl-restart",false,"table-loader-errors-pages");


	if(isset($_GET["main-page"])){$tpl=new template_admin($TITLE,$html);
        echo $tpl->build_firewall();return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));

    $array["{service_status}"]="$page?service-status-start=yes";
	$array["{template_manager}"]="fw.proxy.templates.manager.php";
	if($SQUIDEnable==1){
		$array["{errors_pages}"]="fw.proxy.templates.error.squid.php";
	}
    $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));
    if($UfdbUseInternalService==1){
        $array["{service_events}"]="$page?service-status-events=yes";

    }
	
	echo $tpl->tabs_default($array);
	return true;
	
}
function parameters_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{proxy_error_pages}","$page?form-popup=yes",750);
}
function form_ssl_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{UseSSL}","$page?form-ssl-popup=yes",750);
}

function form_squid_templates_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{parameters}","$page?form-squid-templates-popup=yes",750);

}
function SquidGuardDenyConnect_check():int{

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE UseSSL=1 AND enabled=1");
    $c=0;
    foreach ($results as $index=>$ligne){
        $c++;
    }
    if($c>0){
        return $c;
    }
    $results = $q->QUERY_SQL("SELECT * FROM transparent_ports WHERE enabled=1");

    foreach ($results as $ligne) {
        if(strlen($ligne["sslcertificate"])>1) {
            $c++;
        }
    }

    return $c;
}

function SquidGuardDenyConnect_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{ssl_decrypt_compatibility}","$page?SquidGuardDenyConnect-popup=yes",750);
    return true;
}
function SquidGuardDenyConnect_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidGuardDenyConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardDenyConnect"));
    $ssl_decrypt_compatibility_explain=$tpl->_ENGINE_parse_body("{ssl_decrypt_compatibility_explain}");
    $link="<a href=\"/webfiltering-policies\">{web_filter_policies}</a>";
    $ssl_decrypt_compatibility_explain=str_replace("%s",$link,$ssl_decrypt_compatibility_explain);

   echo $tpl->BigCircleCheckbox("SquidGuardDenyConnect","{ssl_decrypt_compatibility}",$ssl_decrypt_compatibility_explain,$SquidGuardDenyConnect,"dialogInstance1.close();LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');","AsDansGuardianAdministrator");

    return true;
}
function SquidGuardDenyConnect_save():bool{
    $tpl=new template_admin();
    if(!epg_can()){ echo $tpl->post_error("{epg_no_privs}"); return false; }
    $tpl->CLEAN_POST();
      $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardDenyConnect",$_POST["SquidGuardDenyConnect"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/url/rewrite/access/nobuild");
    return   admin_tracks("Save Proxy MAN-IN-THE-MIDDLE compatibility to {$_POST["SquidGuardDenyConnect"]}");
}
function form_ssl_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UfdbUseInternalServiceEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    $UfdbUseInternalServiceHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
    $UfdbUseInternalServiceCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceCertificate"));
    if($UfdbUseInternalServiceHTTPSPort==0){$UfdbUseInternalServiceHTTPSPort=9026;}

    $form[] = $tpl->field_section("{UseSSL}");
    $form[] = $tpl->field_checkbox("UfdbUseInternalServiceEnableSSL","{UseSSL}",$UfdbUseInternalServiceEnableSSL,"UfdbUseInternalServiceHTTPSPort,UfdbUseInternalServiceCertificate");
    $form[] = $tpl->field_numeric("UfdbUseInternalServiceHTTPSPort","{ssl_port}",$UfdbUseInternalServiceHTTPSPort);
    $form[] = $tpl->field_certificate("UfdbUseInternalServiceCertificate","{ssl_certificate}",$UfdbUseInternalServiceCertificate);



    $jsrestart="dialogInstance1.close();".
        "LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');".
        $tpl->framework_buildjs("/weberror/restart",
            "weberror.progress","weberror.log","ufdbweberror-restart");


    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsDansGuardianAdministrator");
    return true;
}

function parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
    $WebErrorPageServiceInDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPageServiceInDebug"));
    $UfdbUseInternalServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
    if($UfdbUseInternalServiceHostname==null){$UfdbUseInternalServiceHostname=php_uname("n");}
    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $WebErrorPageListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPageListenInterface"));

    $HaClusterUseCentralErrorPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseCentralErrorPage"));
    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==0){
        $HaClusterUseCentralErrorPage=0;
    }
    if($HaClusterUseCentralErrorPage==1){
        $EnableNginx=0;
    }

    if($EnableNginx==1) {
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_nginx_explain}");
    }else{
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_explain}");
    }

    if($SquidTemplateid==0){$SquidTemplateid=1;}
    $form[] = $tpl->field_checkbox("WebErrorPageServiceInDebug","{debug}",$WebErrorPageServiceInDebug);
    $form[] = $tpl->field_templates("SquidTemplateid", "{default_template}", $SquidTemplateid);

    if($EnableNginx==1) {
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_nginx_explain}");

    }else{
        $form[] = $tpl->field_section("&nbsp;");
        $form[] =$tpl->field_interfaces("WebErrorPageListenInterface","{listen_interface}",$WebErrorPageListenInterface);
        if($HaClusterUseCentralErrorPage==0) {
            $form[] = $tpl->field_numeric("UfdbUseInternalServiceHTTPPort", "{http_port}", $UfdbUseInternalServiceHTTPPort);
            $form[] = $tpl->field_text("UfdbUseInternalServiceHostname","{hostname}",$UfdbUseInternalServiceHostname);
        }

    }
    $jsrestart="dialogInstance1.close();".
        "LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');".
        $tpl->framework_buildjs("/weberror/restart",
        "weberror.progress","weberror.log","ufdbweberror-restart");


    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsDansGuardianAdministrator");
    return true;
}
function service_status_start():bool{
    $page=CurrentPageName();
    echo "<div id='web-error-page-middle-section'></div>
    <script>LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes')</script>
    ";
    return true;
}
// ---------------------------------------------------------------------------
// Onglet « Service status » (maquette stitch_proxy_error_pages_redesign).
// Deux familles de pages, que l'ecran doit distinguer :
//   - pages de blocage : service artica-error-page (/weberror/*), vers lequel le
//     filtrage web, Go-Shield, dnsdist ou HaCluster redirigent le navigateur ;
//   - pages d'erreur du proxy (Squid) : modele, langue, e-mail affiche, options.
// Tout le contenu est rendu dans #web-error-page-middle-section (id conserve : les
// modales et fw.ufdbweb.php le rechargent), recharge toutes les 30 s.

/** Droit d'ecriture : filtrage web OU proxy, comme les modales (AsDansGuardianAdministrator / AsSquidAdministrator). */
function epg_can():bool{
    $users=new usersMenus();
    return $users->AsDansGuardianAdministrator || $users->AsSquidAdministrator;
}

function web_error_page_middle_section():bool{
    $tpl=new template_admin();
    $m=epg_model();
    $h=array();
    $h[]="<div id='ufdbweberror-restart'></div><div id='ufdbweberror-restart-pr'></div>";
    $h[]=epg_banner($m);
    $h[]=epg_kinds($m);
    $h[]="<div class='epg-pair'>".epg_service_card($m).epg_https_card($m)."</div>";
    $h[]=epg_proxy_card($m);
    echo epg_style().epg_js($m)."<div class='epg'>".$tpl->_ENGINE_parse_body(@implode("\n",$h))."</div>"
        ."<script>NoSpinner();\n".epg_tiny($tpl,$m)."</script>";
    return true;
}

/** Le modele unique de l'onglet. */
function epg_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["enabled"]=intval($sock->GET_INFO("UfdbUseInternalService"))==1;
    $m["squid"]=intval($sock->GET_INFO("SQUIDEnable"))==1;
    $m["nginx"]=intval($sock->GET_INFO("EnableNginx"))==1;
    $m["ha_client"]=intval($sock->GET_INFO("HaClusterClient"))==1;
    $m["ha_force"]=$m["ha_client"] && intval($sock->GET_INFO("EnableHaClusterForceURI"))==1;
    $m["ha_force_uri"]=trim((string)$sock->GET_INFO("HaClusterForceURI"));
    $m["ha_central"]=$m["ha_client"] && intval($sock->GET_INFO("HaClusterUseCentralErrorPage"))==1;
    $m["debug"]=intval($sock->GET_INFO("WebErrorPageServiceInDebug"))==1;
    $m["iface"]=trim((string)$sock->GET_INFO("WebErrorPageListenInterface"));
    $m["port"]=intval($sock->GET_INFO("UfdbUseInternalServiceHTTPPort")); if($m["port"]==0){ $m["port"]=9025; }
    $m["ssl"]=intval($sock->GET_INFO("UfdbUseInternalServiceEnableSSL"))==1;
    $m["ssl_port"]=intval($sock->GET_INFO("UfdbUseInternalServiceHTTPSPort")); if($m["ssl_port"]==0){ $m["ssl_port"]=9026; }
    $m["cert"]=trim((string)$sock->GET_INFO("UfdbUseInternalServiceCertificate"));
    $m["host"]=trim((string)$sock->GET_INFO("UfdbUseInternalServiceHostname")); if($m["host"]===""){ $m["host"]=php_uname("n"); }
    if($m["ha_force"]){ $m["ssl"]=false; $m["port"]=9025; }
    $m["version"]=trim((string)$sock->GET_INFO("WEBERRORPAGE_VERSION"));

    // Les services qui maintiennent le service de blocage en vie (weberrorpage.isMustEnabled).
    $m["users"]=array();
    if($m["squid"]){ $m["users"][]="{epg_u_proxy}"; }
    if(intval($sock->GET_INFO("EnableUfdbGuard"))==1){ $m["users"][]="{epg_u_webfilter}"; }
    if(intval($sock->GET_INFO("Go_Shield_Server_Enable"))==1){ $m["users"][]="Go-Shield"; }
    if(intval($sock->GET_INFO("EnableDNSDist"))==1){ $m["users"][]="{epg_u_dns}"; }
    if($m["ha_client"] || intval($sock->GET_INFO("Enablehacluster"))==1){ $m["users"][]="HaCluster"; }

    // Etat du service (INI) et sonde /ping.
    $m["state"]="off"; $m["uptime"]=""; $m["mem"]=0; $m["counter"]=null; $m["probe"]=""; $m["probe_err"]="";
    if($m["enabled"]){
        $m["state"]="unknown";
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/status"));
        if(is_object($json) && !empty($json->Status)){
            $ini=new Bs_IniHandler();
            $ini->loadString((string)($json->Info ?? ""));
            foreach((is_array($ini->_params) ? $ini->_params : array()) as $sec){
                if(!is_array($sec) || !isset($sec["running"])){ continue; }
                $m["state"]=intval($sec["running"])==1 ? "running" : "stopped";
                $m["uptime"]=trim((string)($sec["uptime"] ?? ""));
                $m["mem"]=intval($sec["master_memory"] ?? 0);
                break;
            }
            if(isset($json->Metric) && is_object($json->Metric)){
                $m["probe"]=trim((string)($json->Metric->mainaddr ?? ""));
                if(!empty($json->Metric->Status)){ $m["counter"]=intval($json->Metric->counter ?? 0); }
                else{ $m["probe_err"]=trim((string)($json->Metric->error ?? "")); }
            }
        }
    }

    // Adresses de redirection compilees (uniques).
    $m["urls"]=array();
    $compiled=@unserialize((string)$sock->GET_INFO("WebErrorPagesCompiled"));
    if(is_array($compiled)){
        foreach($compiled as $l){
            $u=is_array($l) ? trim((string)($l["url"] ?? "")) : "";
            if(strlen($u)>=4 && !in_array($u,$m["urls"],true)){ $m["urls"][]=$u; }
        }
    }

    // Compatibilite SSL : reglage + ports qui dechiffrent reellement.
    $m["deny_connect"]=intval($sock->GET_INFO("SquidGuardDenyConnect"))==1;
    $m["ssl_ports"]=array();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    foreach((is_array($r=$q->QUERY_SQL("SELECT port FROM proxy_ports WHERE UseSSL=1 AND enabled=1")) ? $r : array()) as $l){ $m["ssl_ports"][]=intval($l["port"] ?? 0); }
    foreach((is_array($r=$q->QUERY_SQL("SELECT port,sslcertificate FROM transparent_ports WHERE enabled=1")) ? $r : array()) as $l){
        if(strlen((string)($l["sslcertificate"] ?? ""))>1){ $m["ssl_ports"][]=intval($l["port"] ?? 0); }
    }
    $m["ssl_ports"]=array_values(array_unique(array_filter($m["ssl_ports"])));

    // Pages d'erreur du proxy.
    $tid=intval($sock->GET_INFO("SquidTemplateid")); if($tid==0){ $tid=1; }
    $fid=intval($sock->GET_INFO("FTPTemplateid")); if($fid==0){ $fid=2; }
    $m["tpl_name"]=epg_template_name($q,$tid);
    $m["ftp_name"]=epg_template_name($q,$fid);
    $m["simple"]=intval($sock->GET_INFO("SquidTemplateSimple"))==1;
    $m["lang"]=trim((string)$sock->GET_INFO("SquidHTTPTemplateLanguage")); if($m["lang"]===""){ $m["lang"]="en-us"; }
    $m["hide_artica"]=trim(strval($sock->GET_INFO("SquidHTTPTemplateNoVersion")))!=="0";
    $m["hide_proxy"]=trim(strval($sock->GET_INFO("SquidHTTPTemplateNoProxyVersion")))!=="0";
    $m["mail"]=trim((string)$sock->GET_INFO("cache_mgr_user"));
    $m["smtp_queue"]=intval($sock->GET_INFO("SquidDifferedSMTPMessages"))==1;
    $m["smtp_timeout"]=intval($sock->GET_INFO("SquidSMTPTimeout")); if($m["smtp_timeout"]==0){ $m["smtp_timeout"]=10; }
    $m["can"]=epg_can();
    return $m;
}

function epg_template_name($q,int $id):string{
    $l=$q->mysqli_fetch_array("SELECT TemplateName FROM templates_manager WHERE ID=$id");
    $n=is_array($l) ? trim((string)($l["TemplateName"] ?? "")) : "";
    return $n!=="" ? $n : "{default}";
}

function epg_chip(string $cls,string $ico,string $text,string $title=""):string{
    $t=$title!=="" ? " title=\"$title\"" : "";
    return "<span class='epg-chip epg-chip-$cls'$t><i class='$ico' aria-hidden='true'></i>$text</span>";
}

function epg_fmt(string $token,$v):string{
    $tpl=new template_admin();
    return str_replace("%s",(string)$v,$tpl->_ENGINE_parse_body($token));
}

function epg_jstext(string $token):string{
    $tpl=new template_admin();
    $t=str_ireplace(array("<br>","<br/>","<br />")," ",$tpl->_ENGINE_parse_body($token));
    $t=trim(preg_replace('/\s+/'," ",html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8")));
    return str_replace(array("\\","'","</"),array("\\\\","\\'","<\\/"),$t);
}

function epg_onoff(bool $on):string{
    return $on ? epg_chip("ok","fas fa-circle-check","{epg_on}") : epg_chip("off","fas fa-circle-minus","{epg_off}");
}

// --- 1. Bandeau

function epg_banner(array $m):string{
    $c=array();
    if(!$m["enabled"]){ $c[]=epg_chip("off","fas fa-ban","{epg_chip_bp_off}"); }
    elseif($m["state"]==="running" && $m["probe_err"]===""){ $c[]=epg_chip("ok","fas fa-ban","{epg_chip_bp_on}"); }
    else{ $c[]=epg_chip("err","fas fa-ban","{epg_chip_bp_down}"); }
    if($m["squid"]){
        $c[]=epg_chip("n","fas fa-language",epg_fmt("{epg_chip_lang}",htmlspecialchars($m["lang"])));
        if($m["mail"]!==""){ $c[]=epg_chip("n","fas fa-envelope",epg_fmt("{epg_chip_mail}",htmlspecialchars($m["mail"]))); }
        if($m["hide_artica"] && $m["hide_proxy"]){ $c[]=epg_chip("n","fas fa-eye-slash","{epg_chip_hidden}"); }
    }
    return "<section class='epg-card epg-hero'><div class='epg-hero-ico'><i class='fas fa-file-shield' aria-hidden='true'></i></div>"
        ."<div class='epg-hero-body'><h2>{epg_hero_title}</h2><p>{epg_hero_text}</p><div class='epg-chips'>".@implode("",$c)."</div></div></section>";
}

// --- 2. Les deux familles de pages

function epg_kinds(array $m):string{
    $used=count($m["users"]) ? epg_fmt("{epg_used_by}",@implode(", ",$m["users"])) : "{epg_used_none}";
    $a="<div class='epg-card epg-kind'><h3><span class='epg-k-ico'><i class='fas fa-hand' aria-hidden='true'></i></span>{epg_k1_title}</h3>"
        ."<p>{epg_k1_text}</p><div class='epg-ex'><b>{epg_example}</b> {epg_k1_example}</div>"
        ."<div class='epg-k-foot'><i class='fas fa-check' aria-hidden='true'></i>$used</div></div>";
    if(!$m["squid"]){ return "<div class='epg-pair epg-pair-1'>$a</div>"; }
    $b="<div class='epg-card epg-kind'><h3><span class='epg-k-ico epg-k-ico-w'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span>{epg_k2_title}</h3>"
        ."<p>{epg_k2_text}</p><div class='epg-ex'><b>{epg_example}</b> {epg_k2_example}</div>"
        ."<div class='epg-k-foot'>".epg_fmt("{epg_k2_foot}",htmlspecialchars($m["tpl_name"]))." &middot; ".epg_fmt("{epg_k2_foot_ftp}",htmlspecialchars($m["ftp_name"]))." &middot; ".epg_fmt("{epg_k2_foot_lang}",htmlspecialchars($m["lang"]))."</div></div>";
    return "<div class='epg-pair epg-pair-kinds'>$a$b</div>";
}

// --- 3. Service des pages de blocage

function epg_fact(string $ico,string $label,string $value,string $help=""):string{
    return "<div class='epg-fact'><i class='$ico' aria-hidden='true'></i><div class='epg-fact-b'><div class='epg-fact-h'><span>$label</span><span class='epg-fact-v'>$value</span></div>"
        .($help!=="" ? "<div class='epg-help'>$help</div>" : "")."</div></div>";
}

function epg_service_card(array $m):string{
    $page=CurrentPageName();
    $ver=$m["version"]!=="" ? "<span class='epg-ver'>v".htmlspecialchars($m["version"])."</span>" : "";
    if(!$m["enabled"]){
        $st=epg_chip("off","fas fa-circle-minus","{epg_st_off}");
    }elseif($m["state"]==="running"){
        $bits=array("{epg_st_running}");
        if($m["uptime"]!==""){ $bits[]=epg_fmt("{epg_st_up}",htmlspecialchars($m["uptime"])); }
        if($m["mem"]>0){ $bits[]=FormatBytes($m["mem"],true); }
        $st=epg_chip("ok","fas fa-circle-check",@implode(" &middot; ",$bits));
    }elseif($m["state"]==="stopped"){
        $st=epg_chip("err","fas fa-circle-xmark","{epg_st_stopped}");
    }else{
        $st=epg_chip("warn","fas fa-circle-question","{epg_st_unknown}");
    }
    $h=array();
    $h[]="<section class='epg-card epg-svc'><div class='epg-svc-head'><div><h3>{epg_svc_title} $ver</h3><div class='epg-sub'>{epg_svc_sub}</div></div>$st</div>";

    if($m["nginx"] && !$m["ha_central"]){
        $h[]="<div class='epg-note epg-note-info'><i class='fas fa-circle-info' aria-hidden='true'></i>{epg_nginx_note}</div>";
    }
    if($m["ha_force"] && $m["ha_force_uri"]!==""){
        $h[]="<div class='epg-note epg-note-blue'><i class='fas fa-network-wired' aria-hidden='true'></i>".epg_fmt("{epg_ha_note}","<span class='epg-mono'>".htmlspecialchars($m["ha_force_uri"])."</span>")."</div>";
    }
    if(!$m["enabled"]){
        $h[]="<div class='epg-invite'><i class='fas fa-ban' aria-hidden='true'></i><div><b>{epg_off_title}</b><p>{epg_off_text}</p></div>"
            .($m["can"] ? "<button type='button' class='epg-btn epg-btn-pri' onclick='EpgActivate()'><i class='fas fa-power-off' aria-hidden='true'></i>{epg_activate}</button>" : "")."</div>";
    }else{
        if($m["probe_err"]!==""){
            $h[]="<div class='epg-note epg-note-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>".epg_fmt("{epg_probe_err}",htmlspecialchars($m["probe"]))." <span class='epg-mono'>".htmlspecialchars($m["probe_err"])."</span></div>";
        }
        $urls=array();
        foreach($m["urls"] as $u){ $urls[]="<span class='epg-mono epg-url' title=\"".htmlspecialchars($u,ENT_QUOTES)."\">".htmlspecialchars($u)."</span>"; }
        $h[]="<div class='epg-facts'>";
        $h[]=epg_fact("fas fa-link","{epg_f_urls}",count($urls) ? @implode("",$urls) : "&mdash;","{epg_f_urls_help}");
        if(!($m["nginx"] && !$m["ha_central"])){
            $iface=($m["iface"]==="" || $m["iface"]==="*") ? "{epg_all_ifaces}" : htmlspecialchars($m["iface"]);
            $h[]=epg_fact("fas fa-plug","{epg_f_listen}",epg_fmt("{epg_f_listen_v}",$iface." &middot; ".$m["port"])." <span class='epg-sub'>(".htmlspecialchars($m["host"]).")</span>","{epg_f_listen_help}");
            $sslv=$m["ssl"] ? epg_fmt("{epg_f_ssl_v}",$m["ssl_port"]).($m["cert"]!=="" ? " &middot; <span class='epg-mono'>".htmlspecialchars($m["cert"])."</span>" : " &middot; {epg_no_cert}") : epg_onoff(false);
            $h[]=epg_fact("fas fa-lock","{epg_f_ssl}",$sslv,"{epg_f_ssl_help}");
        }
        $h[]=epg_fact("fas fa-file-lines","{epg_f_tpl}",htmlspecialchars($m["tpl_name"]));
        $h[]=epg_fact("fas fa-bug","{epg_f_debug}",epg_onoff($m["debug"]),"{epg_f_debug_help}");
        if($m["counter"]!==null){
            $h[]=epg_fact("fas fa-gauge","{epg_f_counter}",number_format($m["counter"],0,"."," "),epg_fmt("{epg_f_counter_help}","<span class='epg-mono'>".htmlspecialchars($m["probe"])."</span>"));
        }
        $h[]="</div>";
    }
    $h[]="<div class='epg-auto'><i class='fas fa-wand-magic-sparkles' aria-hidden='true'></i>{epg_auto_note}</div>";
    if($m["can"] && $m["enabled"]){
        $h[]="<div class='epg-actions'>";
        $h[]="<button type='button' class='epg-btn epg-btn-pri' onclick=\"Loadjs('$page?form-js=yes')\"><i class='fas fa-pen' aria-hidden='true'></i>{epg_edit_service}</button>";
        if(!($m["nginx"] && !$m["ha_central"]) && !$m["ha_force"]){
            $h[]="<button type='button' class='epg-btn' onclick=\"Loadjs('$page?form-ssl-js=yes')\"><i class='fas fa-lock' aria-hidden='true'></i>{epg_f_ssl}</button>";
        }
        $h[]="<button type='button' class='epg-btn' onclick='EpgRestart()'><i class='fas fa-rotate' aria-hidden='true'></i>{epg_restart}</button>";
        if(!$m["ha_central"]){
            $h[]="<button type='button' class='epg-link-danger' onclick='EpgUninstall()'><i class='fas fa-trash' aria-hidden='true'></i>{epg_uninstall}</button>";
        }
        $h[]="</div>";
    }
    $h[]="</section>";
    return @implode("\n",$h);
}

// --- 4. Sites HTTPS bloques (SquidGuardDenyConnect)

function epg_https_card(array $m):string{
    $page=CurrentPageName();
    $n=count($m["ssl_ports"]);
    $ports=$n ? "<span class='epg-mono epg-ports'>".epg_fmt("{epg_ports}",@implode(", ",$m["ssl_ports"]))."</span>" : "";
    $dec=$n ? "<div class='epg-dec epg-dec-on'><i class='fas fa-unlock' aria-hidden='true'></i><span>".($n==1 ? "{epg_dec_on1}" : epg_fmt("{epg_dec_on}",$n))."</span>$ports</div>"
        : "<div class='epg-dec epg-dec-off'><i class='fas fa-lock' aria-hidden='true'></i><span>{epg_dec_off}</span></div>";
    $state=$m["deny_connect"] ? "{epg_on}" : "{epg_off}";
    $sw="<div class='epg-compat'><div class='epg-compat-h'><b>{ssl_decrypt_compatibility}</b>"
        ."<span class='epg-sw".($m["deny_connect"] ? " epg-sw-on" : "")."' role='switch' aria-checked='".($m["deny_connect"] ? "true" : "false")."' aria-readonly='true'><span class='epg-sw-t'><span></span></span><em>$state</em></span></div>"
        ."<div class='epg-help'><b>{epg_on}:</b> {epg_compat_on}<b>{epg_off}:</b> {epg_compat_off}</div>"
        .($m["can"] ? "<button type='button' class='epg-btn' onclick=\"Loadjs('$page?SquidGuardDenyConnect-js=yes')\"><i class='fas fa-sliders' aria-hidden='true'></i>{epg_change}</button>" : "")."</div>";
    $warn="";
    if($m["deny_connect"] && $n==0){
        $warn="<div class='epg-note epg-note-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{epg_compat_warn} <a href='/webfiltering-policies'>{epg_open_policies}</a></span></div>";
    }
    return "<section class='epg-card epg-https'><h3><i class='fas fa-shield-halved' aria-hidden='true'></i>{epg_https_title}</h3><div class='epg-sub'>{ssl_decrypt_compatibility}</div>"
        ."<p class='epg-p'>{epg_https_text}</p>$dec$sw$warn</section>";
}

// --- 5. Pages d'erreur du proxy

function epg_row(string $label,string $value,string $help,string $tech=""):string{
    return "<div class='epg-row'><div class='epg-row-l'><b>$label</b>".($tech!=="" ? "<span class='epg-tech'>$tech</span>" : "")."<div class='epg-help'>$help</div></div><div class='epg-row-v'>$value</div></div>";
}

function epg_proxy_card(array $m):string{
    if(!$m["squid"]){ return ""; }
    $page=CurrentPageName();
    $btns=$m["can"] ? "<button type='button' class='epg-btn epg-btn-pri' onclick=\"Loadjs('$page?form-squid-templates-js=yes')\"><i class='fas fa-pen' aria-hidden='true'></i>{epg_edit_proxy}</button>" : "";
    $btns.="<button type='button' class='epg-btn' onclick=\"EpgTemplates()\"><i class='fas fa-palette' aria-hidden='true'></i>{template_manager}</button>";
    $r=array();
    $r[]=epg_row("{epg_p_simple}",epg_onoff($m["simple"]),"<b>{epg_on}:</b> {epg_p_simple_on}<b>{epg_off}:</b> {epg_p_simple_off}","simple template mode");
    $r[]=epg_row("{epg_p_tpl}","<span class='epg-val'>".htmlspecialchars($m["tpl_name"])."</span>","{epg_p_tpl_help}");
    $r[]=epg_row("{epg_p_ftp}","<span class='epg-val'>".htmlspecialchars($m["ftp_name"])."</span>","{epg_p_ftp_help}");
    $r[]=epg_row("{epg_p_lang}","<span class='epg-val epg-mono'>".htmlspecialchars($m["lang"])."</span>","{epg_p_lang_help}");
    $r[]=epg_row("{epg_p_mail}",$m["mail"]!=="" ? "<span class='epg-val epg-mono'>".htmlspecialchars($m["mail"])."</span>" : "&mdash;","{epg_p_mail_help}");
    $r[]=epg_row("{epg_p_hide}","<span class='epg-hide'>Artica ".($m["hide_artica"] ? epg_chip("ok","fas fa-eye-slash","{epg_hidden}") : epg_chip("warn","fas fa-eye","{epg_shown}"))
        ." {epg_proxy_word} ".($m["hide_proxy"] ? epg_chip("ok","fas fa-eye-slash","{epg_hidden}") : epg_chip("warn","fas fa-eye","{epg_shown}"))."</span>","{epg_p_hide_help}");
    $smtp="<div class='epg-smtp'><div class='epg-smtp-t'><i class='fas fa-envelope' aria-hidden='true'></i>{epg_smtp}</div><div class='epg-smtp-g'>"
        .epg_row("{epg_p_queue}",epg_onoff($m["smtp_queue"]),"{epg_p_queue_help}")
        .epg_row("{epg_p_timeout}","<span class='epg-val'>".epg_fmt("{epg_seconds}",$m["smtp_timeout"])."</span>","{epg_p_timeout_help}")."</div></div>";
    return "<section class='epg-card epg-proxy'><div class='epg-proxy-head'><div><h3>{epg_k2_title}</h3><div class='epg-sub'>{epg_proxy_sub}</div></div><div class='epg-actions'>$btns</div></div>"
        ."<div class='epg-rows'>".@implode("",$r)."</div>$smtp</section>";
}

// --- TINY, JS, style

function epg_tiny($tpl,array $m):string{
    $btns=array();
    $btns[]=array("EpgTemplates()","fas fa-palette","{template_manager}");
    if($m["can"] && $m["enabled"]){ $btns[]=array("EpgRestart()",ico_refresh,"{epg_restart}"); }
    $TINY=array();
    $TINY["TITLE"]=$m["squid"] ? "{errors_pages}" : "{templates}";
    $TINY["ICO"]="fas fa-file-alt";
    $TINY["EXPL"]="{epg_page_explain}";
    $TINY["URL"]="proxy-errors";
    $TINY["BUTTONS"]=$tpl->table_buttons($btns);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

function epg_js(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $reload="LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');";
    $restart=$tpl->framework_buildjs("/weberror/restart","weberror.progress","weberror.log","ufdbweberror-restart",$reload);
    $install=$tpl->framework_buildjs("/weberror/install","weberror.progress","weberror.log","ufdbweberror-restart",$reload);
    $uninstall=$tpl->framework_buildjs("/weberror/uninstall","weberror.progress","weberror.log","ufdbweberror-restart",$reload);
    $T=array();
    foreach(array("ask_uninstall","ask_uninstall_txt","uninstall","cancel","ask_restart","ask_restart_txt","restart","activate") as $k){ $T[$k]=epg_jstext("{epg_$k}"); }
    return "<script>
function EpgReload(){ $reload }
function EpgTemplates(){ s_PopUp('/proxy-templates-manager','1200','800'); }
function EpgActivate(){ $install }
function EpgRestart(){
  swal({title:'{$T["ask_restart"]}',text:'{$T["ask_restart_txt"]}',type:'warning',showCancelButton:true,confirmButtonColor:'#0f766e',confirmButtonText:'{$T["restart"]}',cancelButtonText:'{$T["cancel"]}'},
    function(y){ if(!y){return;} $restart });
}
function EpgUninstall(){
  swal({title:'{$T["ask_uninstall"]}',text:'{$T["ask_uninstall_txt"]}',type:'warning',showCancelButton:true,confirmButtonColor:'#b91c1c',confirmButtonText:'{$T["uninstall"]}',cancelButtonText:'{$T["cancel"]}'},
    function(y){ if(!y){return;} $uninstall });
}
if(window.EpgTimer){ clearInterval(window.EpgTimer); }
window.EpgTimer=setInterval(function(){
  if(!document.getElementById('web-error-page-middle-section')){ clearInterval(window.EpgTimer); window.EpgTimer=null; return; }
  if(document.hidden){ return; }
  if(typeof ModalIsOpenOrOpening==='function' && ModalIsOpenOrOpening()){ return; }
  if(jQuery('.modal.in,.modal.show,.sweet-alert.visible,.swal2-container').length){ return; }
  var p=document.getElementById('ufdbweberror-restart'); if(p && p.innerHTML.trim()!==''){ return; }
  EpgReload();
},30000);
</script>";
}

function epg_accent():string{
    $ac="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){ $ac=$a["accent_dark"]; }
    }
    return $ac;
}

function epg_style():string{
    $ac=epg_accent();
    $soft=function_exists("brand_rgba") ? brand_rgba($ac,".14") : "";
    if($soft==""){ $soft="rgba(13,148,136,.14)"; }
    return "<style>
.epg{--epg-ac:$ac;--epg-soft:$soft;display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#1e293b;font-size:14px}
.epg *{box-sizing:border-box}
.epg-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.epg-mono{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px}
.epg-sub{font-size:13px;color:#64748b}
.epg-help{font-size:13px;color:#64748b;line-height:1.45;margin-top:3px}
.epg-p{font-size:14px;color:#475569;line-height:1.6;margin:10px 0}
.epg-hero{display:flex;gap:20px;padding:22px 24px;border-left:4px solid var(--epg-ac)}
.epg-hero-ico{flex:0 0 52px;height:52px;border-radius:12px;background:var(--epg-soft);color:var(--epg-ac);display:flex;align-items:center;justify-content:center;font-size:24px}
.epg-hero-body{flex:1;min-width:0}
.epg-hero h2{font-size:18px;font-weight:600;margin:0 0 6px;color:#0f172a}
.epg-hero p{font-size:14px;line-height:1.6;color:#475569;margin:0 0 12px;max-width:980px}
.epg-chips{display:flex;flex-wrap:wrap;gap:8px}
.epg-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;line-height:1.3;padding:3px 10px;border-radius:999px;white-space:nowrap}
.epg-chip i{font-size:12px}
.epg-chip-n{background:#f1f5f9;color:#334155}
.epg-chip-ok{background:#d1fae5;color:#047857}
.epg-chip-warn{background:#fef3c7;color:#92400e}
.epg-chip-err{background:#fee2e2;color:#b91c1c}
.epg-chip-off{background:#f1f5f9;color:#64748b}
.epg-pair{display:grid;grid-template-columns:3fr 2fr;gap:16px;align-items:start}
.epg-pair-kinds{grid-template-columns:1fr 1fr}
.epg-pair-1{grid-template-columns:1fr!important}
@media (max-width:1200px){.epg-pair{grid-template-columns:1fr!important}}
.epg-kind{padding:18px 20px;display:flex;flex-direction:column;height:100%}
.epg-kind h3,.epg-https h3,.epg-svc h3,.epg-proxy h3{font-size:16px;font-weight:600;margin:0;color:#0f172a;display:flex;align-items:center;gap:10px}
.epg-k-ico{width:30px;height:30px;border-radius:8px;background:var(--epg-soft);color:var(--epg-ac);display:inline-flex;align-items:center;justify-content:center;font-size:14px}
.epg-k-ico-w{background:#fef3c7;color:#b45309}
.epg-kind p{font-size:14px;color:#475569;line-height:1.55;margin:10px 0}
.epg-ex{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:13px;color:#334155;line-height:1.5}
.epg-k-foot{margin-top:auto;padding-top:12px;font-size:13px;color:#047857;display:flex;gap:6px;align-items:baseline}
.epg-kind:last-child .epg-k-foot{color:#475569}
.epg-svc,.epg-https,.epg-proxy{padding:20px 22px}
.epg-svc-head,.epg-proxy-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;padding-bottom:14px;border-bottom:1px solid #f1f5f9;margin-bottom:12px}
.epg-ver{font-size:12px;font-weight:600;color:#475569;background:#f1f5f9;border-radius:6px;padding:2px 7px;font-family:'JetBrains Mono',ui-monospace,monospace}
.epg-facts{display:flex;flex-direction:column}
.epg-fact{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.epg-fact:last-child{border-bottom:0}
.epg-fact>i{width:18px;color:#94a3b8;margin-top:3px;text-align:center}
.epg-fact-b{flex:1;min-width:0}
.epg-fact-h{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:14px;color:#0f172a}
.epg-fact-v{font-weight:600;display:flex;flex-direction:column;align-items:flex-end;gap:2px;max-width:60%;text-align:right;overflow-wrap:anywhere}
.epg-url{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:6px;padding:1px 8px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.epg-note{display:flex;gap:10px;align-items:flex-start;border-radius:8px;padding:10px 12px;font-size:13px;line-height:1.5;margin:6px 0 10px;border:1px solid}
.epg-note i{margin-top:2px}
.epg-note-info{background:#f8fafc;border-color:#e2e8f0;color:#475569}
.epg-note-blue{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.epg-note-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.epg-note-warn a{color:#92400e;font-weight:600;text-decoration:underline}
.epg-note-err{background:#fef2f2;border-color:#fecaca;color:#991b1b}
.epg-invite{display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:16px;border:1px dashed #cbd5e1;border-radius:10px;margin:6px 0}
.epg-invite>i{font-size:22px;color:#94a3b8}
.epg-invite>div{flex:1 1 260px}
.epg-invite p{margin:4px 0 0;font-size:13px;color:#475569}
.epg-auto{display:flex;gap:10px;font-size:13px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;margin-top:12px;line-height:1.5}
.epg-auto i{color:#64748b;margin-top:2px}
.epg-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:14px}
.epg-proxy-head .epg-actions{margin-top:0}
.epg-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#334155;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.epg-btn:hover{background:#f8fafc;border-color:#94a3b8}
.epg-btn-pri{background:var(--epg-ac);border-color:var(--epg-ac);color:#fff}
.epg-btn-pri:hover{background:var(--epg-ac);color:#fff;filter:brightness(.92)}
.epg-link-danger{border:0;background:transparent;color:#b91c1c;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;gap:6px;align-items:center;padding:6px 8px}
.epg-link-danger:hover{text-decoration:underline}
.epg-btn:focus-visible,.epg-link-danger:focus-visible{outline:2px solid var(--epg-ac);outline-offset:2px}
.epg-https h3 i{color:#005447}
.epg-dec{display:flex;align-items:center;gap:10px;flex-wrap:wrap;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;font-size:14px;margin-bottom:12px}
.epg-dec-on i{color:#047857}
.epg-dec-off{background:#fffbeb;border-color:#fde68a;color:#92400e}
.epg-ports{margin-left:auto;background:#ecfdf5;color:#065f46;border-radius:6px;padding:2px 8px}
.epg-compat{border:1px solid #e2e8f0;border-radius:10px;padding:14px}
.epg-compat-h{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px}
.epg-compat .epg-btn{margin-top:10px}
.epg-sw{display:inline-flex;align-items:center;gap:8px}
.epg-sw-t{position:relative;width:38px;height:22px;border-radius:11px;background:#cbd5e1}
.epg-sw-t span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2)}
.epg-sw-on .epg-sw-t{background:var(--epg-ac)}
.epg-sw-on .epg-sw-t span{left:19px}
.epg-sw em{font-style:normal;font-size:12px;font-weight:700;color:#64748b}
.epg-sw-on em{color:var(--epg-ac)}
.epg-rows{display:flex;flex-direction:column}
.epg-row{display:flex;justify-content:space-between;gap:20px;padding:12px 0;border-bottom:1px solid #f1f5f9;align-items:flex-start}
.epg-row-l{flex:1 1 auto;min-width:0;max-width:640px}
.epg-row-l b{font-weight:600;color:#0f172a}
.epg-tech{display:block;font-family:'JetBrains Mono',ui-monospace,monospace;font-size:12px;color:#94a3b8}
.epg-row-v{flex:0 0 auto;text-align:right}
.epg-val{display:inline-block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:3px 10px;font-weight:600;font-size:13px}
.epg-hide{display:inline-flex;gap:8px;align-items:center;font-size:13px;color:#475569;flex-wrap:wrap;justify-content:flex-end}
.epg-smtp{margin-top:14px}
.epg-smtp-t{font-size:14px;font-weight:600;color:#0f172a;display:flex;gap:8px;align-items:center;margin-bottom:8px}
.epg-smtp-t i{color:#64748b}
.epg-smtp-g{display:grid;grid-template-columns:1fr 1fr;gap:0 28px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:4px 16px}
.epg-smtp-g .epg-row{border-bottom:0}
@media (max-width:1200px){.epg-smtp-g{grid-template-columns:1fr}}
</style>";
}

function template_langs_array($SquidHTTPTemplateLanguage):array{
    $languages=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
    foreach ($languages as $lang=>$line){if($lang=="templates"){continue;}$flang[$lang]="$lang";}

    $xtpl=new template_simple();
    reset($xtpl->arrayxLangs);
    foreach ($xtpl->arrayxLangs as $lang=>$xarr){
        foreach ($xarr as $index=>$z){unset($flang[$z]);}
    }
    $flang[null]="{not_defined}";
    $flang[$SquidHTTPTemplateLanguage]=$SquidHTTPTemplateLanguage;
    unset($flang["templates"]);
    ksort($flang);
    return $flang;
}

function form_squid_templates_params_popup():bool{
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
	$FTPTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPTemplateid"));
	$SquidTemplateSimple=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateSimple"));
	$SquidHTTPTemplateLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage"));
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	
	$SquidHTTPTemplateNoProxyVersion=(trim(strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoProxyVersion")))==="0"?0:1);
	$SquidHTTPTemplateNoVersion=(trim(strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoVersion")))==="0"?0:1);
	
	$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}

	
	$languages=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
    foreach ($languages as $lang=>$line){if($lang=="templates"){continue;}$flang[$lang]="$lang";}
	
	$xtpl=new template_simple();
	reset($xtpl->arrayxLangs);
    foreach ($xtpl->arrayxLangs as $lang=>$xarr){
        foreach ($xarr as $index=>$z){unset($flang[$z]);}
    }
	$flang[null]="{not_defined}";
	$flang[$SquidHTTPTemplateLanguage]=$SquidHTTPTemplateLanguage;
	unset($flang["templates"]);
	ksort($flang);
	
	$cache_mgr_user=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("cache_mgr_user");
	if($cache_mgr_user==null){

		$sock=new sockets();$sock->SET_INFO("cache_mgr_user", $LicenseInfos["EMAIL"]);
		$cache_mgr_user=$LicenseInfos["EMAIL"];
	}
	
	
	if($SquidTemplateid==0){$SquidTemplateid=1;}
	if($FTPTemplateid==0){$FTPTemplateid=2;}
    $SquidDifferedSMTPMessages=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDifferedSMTPMessages");

    $SquidSMTPTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMTPTimeout"));
    if($SquidSMTPTimeout==0){
        $SquidSMTPTimeout=10;
    }

	
	$form[]=$tpl->field_checkbox("SquidTemplateSimple","{use_simple_template_mode}",$SquidTemplateSimple,false,"{use_simple_template_mode_squid_explain}");
	$form[]=$tpl->field_templates("SquidTemplateid", "{proxy_error_pages}", $SquidTemplateid);
	$form[]=$tpl->field_templates("FTPTemplateid", "{ftp_template}", $FTPTemplateid);
	$form[]=$tpl->field_array_hash($flang, "SquidHTTPTemplateLanguage", "{language} ({default})", $SquidHTTPTemplateLanguage);
	$form[]=$tpl->field_email("cache_mgr_user", "{cache_mgr_user}", $cache_mgr_user,false,"{cache_mgr_user_text}");

    // Patch 08/10/2025 - Differed SMTP messages.
    $form[]=$tpl->field_checkbox("SquidDifferedSMTPMessages","{use_smtp_queue}",$SquidDifferedSMTPMessages,false,"{use_smtp_queue_notifs_explain}");
    $form[]=$tpl->field_numeric("SquidSMTPTimeout","{service_smtp} {timeout} ({seconds})",$SquidSMTPTimeout);

	$form[]=$tpl->field_checkbox("SquidHTTPTemplateNoVersion","{remove_artica_version}",$SquidHTTPTemplateNoVersion);
	$form[]=$tpl->field_checkbox("SquidHTTPTemplateNoProxyVersion","{remove_proxy_version}",$SquidHTTPTemplateNoProxyVersion);

    $jsrestart="LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');".
        $tpl->framework_buildjs("/proxy/templates",
            "squid.templates.single.progress","squid.templates.single.log","ufdbweberror-restart-pr","dialogInstance1.close();LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');");

	
    echo "<div id='ufdbweberror-restart-pr'></div>";
	echo $tpl->form_outside("", $form,"{proxy_error_pages_explain}","{apply}",$jsrestart,"AsSquidAdministrator",true);
    return true;
}
function web_error_page_save():bool{
    $tpl=new template_admin();
    if(!epg_can()){ echo $tpl->post_error("{epg_no_privs}"); return false; }
    $tpl->SAVE_POSTs();


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return admin_tracks_post("Saving Web error page service");
}

function SquidTemplateSimple(){
	$tpl=new template_admin();
	if(!epg_can()){ echo $tpl->post_error("{epg_no_privs}"); return; }
	$tpl->SAVE_POSTs();
	
}
// ---- "Service events" tab (maquette stitch_block_page_service_events_console, 2026-11-19) -----
// Source: /var/log/web-error-page.log through $tpl->logfile_search("weberror") (articarest
// /system/logfile/search/{log}/{max}/{pattern}): 250 lines by default, 1500 at most, newest first.
// artica-error-page logs everything at LOG_INFO: severity and topic only exist as tags in the
// text ([ERROR], [WARNING], [DEBUG], [TICKET-CREATE]…), so they are read from the text and every
// count is "in the events shown". The service is named web-ERROR-page: never match the bare word.
// Built once: the filter bar (the search keeps its focus). Reloaded: banner, tiles and timeline.
// Left out of the mock-up because invented: the sample messages; the "Page 1 of 5" label kept as
// numbered pages.
function service_status_events(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$segs=array("all"=>"{bpev_seg_all}","error"=>"{bpev_seg_errors}","warn"=>"{bpev_seg_warnings}",
		"ticket"=>"{bpev_seg_tickets}","mail"=>"{bpev_seg_mails}","service"=>"{bpev_seg_service}");
	$b=array();
	foreach($segs as $k=>$label){
		$b[]="<button type='button' class='bpev-seg-b".($k==="all"?" on":"")."' data-k='$k' aria-pressed='".($k==="all"?"true":"false")."' OnClick=\"BpevSeg('$k')\">$label <span class='bpev-n' id='bpev-c-$k'>0</span></button>";
	}
	$h=array();
	$h[]="<div class='bpev'>";
	$h[]="<div id='bpev-top'></div>";
	$h[]="<div class='ak-card bpev-filters'><div class='bpev-frow'>";
	$h[]="<div class='bpev-seg' role='group' aria-label=\"{bpev_filter_aria}\">".@implode("",$b)."</div>";
	$h[]="<div class='bpev-sw'><button type='button' role='switch' class='ak-sw' id='bpev-dbg' aria-checked='true' aria-labelledby='bpev-dbg-l' OnClick=\"this.setAttribute('aria-checked',this.getAttribute('aria-checked')==='true'?'false':'true');BpevApply();\"></button>"
		."<div><b id='bpev-dbg-l'>{bpev_hide_debug}</b><span>{bpev_hide_debug_help}</span></div></div>";
	$h[]="<button type='button' class='ak-btn ak-btn-ghost' OnClick='BpevLoad()'><i class='fas fa-rotate'></i>{bpev_refresh}</button>";
	$h[]="</div><div class='bpev-srow'><label class='ak-search bpev-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='bpev-q' placeholder=\"{bpev_search}\" aria-label=\"{bpev_search}\" oninput='BpevQueue()'></label>";
	$h[]="<div class='bpev-ex'>{bpev_examples} ".bpev_example("today")." · ".bpev_example("this hour")." · ".bpev_example("yesterday")." · ".bpev_example("max 500")." · {bpev_examples_free}</div></div></div>";
	$h[]="<div id='bpev-list'></div>";
	$h[]="</div>";
	echo bpev_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>".bpev_js($page)."\nBpevLoad();</script>";
}

function bpev_example($v):string{
	return "<button type='button' class='bpev-chip' OnClick=\"var q=document.getElementById('bpev-q');q.value='$v';BpevLoad();\">$v</button>";
}

// One syslog line -> array(ts, pid, sev, topic, tags, text) or null.
function bpev_parse($raw){
	$raw=trim((string)$raw);
	$ts=0; $pid=""; $msg="";
	if(preg_match("#^([A-Z][a-z]{2})\s+([0-9]{1,2})\s+([0-9:]{8})\s+\S+\s+[^\[:]+\[([0-9]+)\]:\s+(.+)$#",$raw,$re)){
		$ts=bpev_syslog_ts($re[1],$re[2],$re[3]); $pid=$re[4]; $msg=$re[5];
	}elseif(preg_match("#^([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9:]{8}\S*)\s+\S+\s+[^\[:]+\[([0-9]+)\]:\s+(.+)$#",$raw,$re)){
		$ts=intval(strtotime($re[1])); $pid=$re[2]; $msg=$re[3];
	}elseif(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)$#",$raw,$re)){
		$ts=bpev_syslog_ts($re[1],$re[2],$re[3]); $pid=$re[4]; $msg=$re[5];   // the former, looser reading
	}else{
		return null;
	}
	$sev="info";
	if(preg_match("#\[(ERROR)\]|\]:\s*Error:|^Error:#i",$msg)){$sev="error";}
	elseif(preg_match("#\[(WARNING|WARN)\]#i",$msg)){$sev="warn";}
	elseif(preg_match("#\[DEBUG\]#i",$msg)){$sev="debug";}
	$topic="other";
	if(preg_match("#\[TICKET-CREATE\]#",$msg)){$topic="ticket_create";}
	elseif(preg_match("#\[TICKET-CONFIRM\]#",$msg)){$topic="ticket_confirm";}
	elseif(preg_match("#\[(TICKET-UNLOCK|UNLOCK-REDIRECT)\]#",$msg)){$topic="ticket_unlock";}
	elseif(preg_match("#\[NOTIFY\]|notify_admin#i",$msg)){$topic="mail";}
	elseif(preg_match("#\[POST-API\]#",$msg)){$topic="api";}
	elseif(preg_match("#\[RULES\]#",$msg)){$topic="rules";}
	elseif(preg_match("#Daemon started version|Starting listen|Service Listening|Service stopped|Received signal#i",$msg)){$topic="service";}
	$tags=array();
	$text=$msg;
	while(preg_match("#^\s*(\[[A-Za-z0-9 _\-]+\]:?)\s*#",$text,$m)){ $tags[]=rtrim($m[1],":"); $text=substr($text,strlen($m[0])); }
	if(preg_match("#^\s*Error:\s*#i",$text,$m)){ $tags[]="Error:"; $text=substr($text,strlen($m[0])); }
	$text=trim(ltrim($text,": "));
	if($text===""){ $text=$msg; }
	return array("ts"=>$ts,"pid"=>$pid,"sev"=>$sev,"topic"=>$topic,"tags"=>@implode(" ",$tags),"text"=>$text,"raw"=>$msg);
}

// Syslog dates carry no year: this year, or last year if that would be in the future.
function bpev_syslog_ts($mon,$day,$time):int{
	$y=intval(date("Y"));
	$t=strtotime("$day $mon $y $time");
	if($t===false){return 0;}
	if($t>time()+86400){ $t=strtotime("$day $mon ".($y-1)." $time"); }
	return intval($t);
}

function bpev_model():array{
	$tpl=new template_admin();
	$q=trim((string)($_GET["search"] ?? ""));
	$MAIN=$tpl->format_search_protocol($q,false,false,false,true);
	$err="";
	$data=$tpl->logfile_search("weberror",$MAIN,true,250,$err);
	$M=array("error"=>$err,"search"=>$q,"rows"=>array(),"n"=>0,"errors"=>0,"warns"=>0,"tickets"=>0,"start"=>0,"version"=>"");
	foreach((array)$data as $raw){
		$r=bpev_parse($raw);
		if($r===null){continue;}
		$M["rows"][]=$r;
		$M["n"]++;
		if($r["sev"]==="error"){$M["errors"]++;}
		if($r["sev"]==="warn"){$M["warns"]++;}
		if($r["topic"]==="ticket_create"){$M["tickets"]++;}
		if($M["start"]==0 && preg_match("#Daemon started version\s+(\S+)#i",$r["raw"],$re)){ $M["start"]=$r["ts"]; $M["version"]=$re[1]; }
	}
	return $M;
}

function bpev_tr($token):string{
	$tpl=new template_admin();
	$t=$tpl->_ENGINE_parse_body("{".$token."}");
	$args=func_get_args(); array_shift($args);
	foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
	return $t;
}

function bpev_esc($v):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

function bpev_age(int $t):string{
	if($t<=0){return "—";}
	$d=max(0,time()-$t);
	if($d<3600){ return bpev_tr("bpev_ago_min",max(1,intval($d/60))); }
	if($d<86400){ return bpev_tr("bpev_ago_h",intval($d/3600)); }
	$n=intval($d/86400);
	return $n==1?bpev_tr("bpev_ago_1d"):bpev_tr("bpev_ago_d",$n);
}

// ?service-status-events-search= : banner + tiles (moved above the filter bar) and the timeline.
function service_status_events_search(){
	$tpl=new template_admin();
	$M=bpev_model();
	// An unreadable log is not "no error": no tiles then, only the error below.
	$top=bpev_banner($M).($M["error"]===""?bpev_tiles($M):"");
	$h=array();
	$h[]="<div id='bpev-top-src' hidden>$top</div>";
	if($M["error"]!==""){
		$h[]=$tpl->div_error(bpev_esc($M["error"]));
	}elseif($M["n"]==0){
		$h[]=$M["search"]===""?bpev_empty_none():bpev_empty_search();
	}else{
		$h[]=bpev_timeline($M);
	}
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{WEB_ERROR_PAGE}: {service_events}";
	$TINY_ARRAY["ICO"]=ico_eye;
	$TINY_ARRAY["EXPL"]="{bpev_tiny_explain}";
	$TINY_ARRAY["URL"]="proxy-errors";
	$TINY_ARRAY["BUTTONS"]=null;
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$h))
		."<script>NoSpinner();(function(){var s=document.getElementById('bpev-top-src'),t=document.getElementById('bpev-top');if(s&&t){t.innerHTML=s.innerHTML;s.parentNode.removeChild(s);}})();BpevApply();\n$jstiny</script>";
}

function bpev_banner(array $M):string{
	return "<section class='bpev-banner'><span class='bpev-banner-ico'><i class='fas fa-list-check'></i></span><div>"
		."<h3>{bpev_banner_title}</h3><p>{bpev_banner_text}</p><div class='ak-chips'>"
		.($M["n"]>0?"<span class='ak-tag ak-info'><i class='fas fa-clock-rotate-left'></i>".bpev_tr("bpev_chip_loaded",$M["n"])."</span>":"")
		."<span class='ak-tag ak-info'><i class='fas fa-triangle-exclamation'></i>{bpev_chip_errors}</span>"
		."<span class='ak-tag ak-info'><i class='fas fa-eye'></i>{bpev_chip_readonly}</span></div></div></section>";
}

function bpev_tiles(array $M):string{
	return "<div class='bpev-tiles'>".bpev_tile_errors($M).bpev_tile_warns($M).bpev_tile_tickets($M).bpev_tile_start($M)."</div>";
}

function bpev_tile($tone,$ico,$label,$value,$sub,$js=""):string{
	$tag=$js!==""?"button type='button' OnClick=\"$js\"":"div";
	$end=$js!==""?"button":"div";
	return "<$tag class='bpev-tile ak-card'><div class='bpev-tile-h'><span>$label</span><span class='ak-ico ak-ico-xs $tone'><i class='$ico'></i></span></div>"
		."<div class='bpev-tile-v bpev-$tone'>$value</div><div class='bpev-tile-s'>$sub</div></$end>";
}

function bpev_tile_errors(array $M):string{
	$v=$M["errors"]>0?$M["errors"]:"{bpev_none}";
	return bpev_tile($M["errors"]>0?"ak-err":"ak-off","fas fa-circle-xmark","{bpev_t_errors}",$v,"{bpev_in_shown}",$M["errors"]>0?"BpevSeg('error')":"");
}
function bpev_tile_warns(array $M):string{
	$v=$M["warns"]>0?$M["warns"]:"{bpev_none}";
	return bpev_tile($M["warns"]>0?"ak-warn":"ak-off","fas fa-triangle-exclamation","{bpev_t_warnings}",$v,"{bpev_in_shown}",$M["warns"]>0?"BpevSeg('warn')":"");
}
function bpev_tile_tickets(array $M):string{
	return bpev_tile("ak-accent","fas fa-hand","{bpev_t_tickets}",$M["tickets"],"{bpev_in_shown}",$M["tickets"]>0?"BpevSeg('ticket')":"");
}
function bpev_tile_start(array $M):string{
	if($M["start"]<=0){ return bpev_tile("ak-off","fas fa-power-off","{bpev_t_start}","<span class='bpev-small'>{bpev_start_none}</span>","{bpev_in_shown}"); }
	$v="<span title=\"".bpev_esc(date("Y-m-d H:i:s",$M["start"]))."\">".bpev_esc(bpev_age($M["start"]))."</span>";
	$sub=$M["version"]!==""?"<span class='ak-chip'>v".bpev_esc($M["version"])."</span>":"{bpev_in_shown}";
	return bpev_tile("ak-off","fas fa-power-off","{bpev_t_start}",$v,$sub);
}

// Day groups (newest first) of event rows; filtering/paging is done by BpevApply().
function bpev_timeline(array $M):string{
	$h=array("<div class='ak-card bpev-tl' id='bpev-tl'>");
	$day="";
	foreach($M["rows"] as $r){
		$d=$r["ts"]>0?date("Y-m-d",$r["ts"]):"";
		if($d!==$day){
			if($day!==""){ $h[]="</ul></section>"; }
			$day=$d;
			$h[]="<section class='bpev-day'><div class='bpev-day-h'><i class='far fa-calendar'></i><b>".bpev_day_label($r["ts"])."</b><span class='bpev-day-n'></span></div><ul class='bpev-rows'>";
		}
		$h[]=bpev_row($r);
	}
	if($day!=="" || count($M["rows"])>0){ $h[]="</ul></section>"; }
	$h[]="<div class='bpev-nomatch' id='bpev-nomatch' hidden><b>{bpev_nomatch_title}</b><span>{bpev_nomatch_text}</span><button type='button' class='ak-btn ak-btn-ghost' OnClick='BpevClear()'>{bpev_clear}</button></div>";
	$h[]="<div class='bpev-foot'><div><span id='bpev-count'></span><div class='bpev-older'>{bpev_older}</div></div><span class='bpev-pg' id='bpev-pager'></span></div>";
	$h[]="</div>";
	return @implode("",$h);
}

function bpev_day_label(int $t):string{
	if($t<=0){ return "—"; }
	$d=date("Y-m-d",$t);
	if($d===date("Y-m-d")){ return "{bpev_today} <span class='bpev-day-d'>".date("M j",$t)."</span>"; }
	if($d===date("Y-m-d",time()-86400)){ return "{bpev_yesterday} <span class='bpev-day-d'>".date("M j",$t)."</span>"; }
	return date("M j",$t);
}

function bpev_row(array $r):string{
	$sev=array("error"=>array("ak-err","fas fa-circle-xmark","{bpev_sev_error}"),"warn"=>array("ak-warn","fas fa-triangle-exclamation","{bpev_sev_warning}"),
		"debug"=>array("ak-off","fas fa-code","{bpev_sev_debug}"),"info"=>array("ak-info","fas fa-circle-info","{bpev_sev_info}"));
	list($tone,$ico,$label)=$sev[$r["sev"]];
	$seg=in_array($r["topic"],array("ticket_create","ticket_confirm","ticket_unlock"),true)?"ticket":($r["topic"]==="mail"?"mail":($r["topic"]==="service"?"service":"other"));
	$time=$r["ts"]>0?date("H:i:s",$r["ts"]):"—";
	$age=$r["ts"]>0?bpev_esc(date("Y-m-d H:i:s",$r["ts"])." · ".bpev_age($r["ts"])):"";
	$tags=$r["tags"]!==""?"<div class='bpev-tags'>".bpev_esc($r["tags"])."</div>":"";
	return "<li class='bpev-row bpev-s-".$r["sev"]."' data-sev='".$r["sev"]."' data-seg='$seg'>"
		."<span class='ak-pill $tone bpev-sev'><i class='$ico'></i>$label</span>"
		."<span class='bpev-time' title=\"$age\">$time</span>"
		."<div class='bpev-msg'><b>{bpev_topic_".$r["topic"]."}</b><div class='bpev-text'>".bpev_esc($r["text"])."</div>$tags</div>"
		."<span class='bpev-pid'>".bpev_tr("bpev_pid",bpev_esc($r["pid"]))."</span></li>";
}

function bpev_empty_none():string{
	return "<div class='ak-card ak-empty-c bpev-empty'><i class='fas fa-file-circle-question'></i><b>{bpev_empty_title}</b><span>{bpev_empty_text}</span>"
		."<button type='button' class='ak-btn' OnClick=\"var t=document.querySelector('a[data-toggle=tab][data-url*=\\'service-status-start\\']');if(t){t.click();}\"><i class='fas fa-arrow-right'></i>{bpev_open_status}</button></div>";
}

function bpev_empty_search():string{
	return "<div class='ak-card ak-empty-c bpev-empty'><i class='fas fa-magnifying-glass'></i><b>{bpev_nomatch_title}</b><span>{bpev_nomatch_text}</span>"
		."<button type='button' class='ak-btn ak-btn-ghost' OnClick='BpevClear()'>{bpev_clear}</button></div>";
}

function bpev_js($page):string{
	$tpl=new template_admin();
	$shown=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{bpev_showing}"),ENT_QUOTES,"UTF-8"));
	$nev=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{bpev_day_events}"),ENT_QUOTES,"UTF-8"));
	$nev1=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{bpev_day_event_1}"),ENT_QUOTES,"UTF-8"));
	return "window.BpevShown=$shown;window.BpevNev=$nev;window.BpevNev1=$nev1;window.BpevK=window.BpevK||'all';window.BpevPage=1;
window.BpevLoad=function(){var q=(document.getElementById('bpev-q')||{value:''}).value;LoadAjaxSilent('bpev-list','$page?service-status-events-search=yes&search='+encodeURIComponent(q));};
window.BpevQueue=function(){clearTimeout(window.BpevQT);window.BpevQT=setTimeout(BpevLoad,700);};
window.BpevSeg=function(k){window.BpevK=k;var bs=document.querySelectorAll('.bpev-seg-b');for(var i=0;i<bs.length;i++){var on=bs[i].getAttribute('data-k')===k;bs[i].className='bpev-seg-b'+(on?' on':'');bs[i].setAttribute('aria-pressed',on?'true':'false');}BpevApply();};
window.BpevClear=function(){var q=document.getElementById('bpev-q');var had=q&&q.value!=='';if(q){q.value='';}BpevSeg('all');if(had){BpevLoad();}};
window.BpevApply=function(p){
 window.BpevPage=(typeof p==='number')?p:1;
 var hide=(document.getElementById('bpev-dbg')||{getAttribute:function(){return 'true';}}).getAttribute('aria-checked')==='true';
 var rows=document.querySelectorAll('#bpev-tl .bpev-row'),c={all:0,error:0,warn:0,ticket:0,mail:0,service:0},m=[],i,k=window.BpevK;
 for(i=0;i<rows.length;i++){var r=rows[i],sv=r.getAttribute('data-sev'),sg=r.getAttribute('data-seg');r.style.display='none';
  if(hide&&sv==='debug'){continue;}
  c.all++;if(sv==='error'){c.error++;}if(sv==='warn'){c.warn++;}if(c[sg]!==undefined&&sg!=='all'){c[sg]++;}
  if(k==='all'||(k==='error'&&sv==='error')||(k==='warn'&&sv==='warn')||k===sg){m.push(r);}}
 for(var key in c){var e=document.getElementById('bpev-c-'+key);if(e){e.textContent=c[key];}}
 var size=50,pages=Math.max(1,Math.ceil(m.length/size));if(window.BpevPage>pages){window.BpevPage=pages;}
 var a=(window.BpevPage-1)*size,b=Math.min(m.length,a+size);for(i=a;i<b;i++){m[i].style.display='';}
 var ds=document.querySelectorAll('#bpev-tl .bpev-day');for(i=0;i<ds.length;i++){var n=0,lr=ds[i].querySelectorAll('.bpev-row');for(var j=0;j<lr.length;j++){if(lr[j].style.display!=='none'){n++;}}ds[i].style.display=n?'':'none';var dn=ds[i].querySelector('.bpev-day-n');if(dn){dn.textContent=n===1?window.BpevNev1:window.BpevNev.replace('%s',n);}}
 var cnt=document.getElementById('bpev-count');if(cnt){cnt.textContent=m.length?window.BpevShown.replace('%s',a+1).replace('%s',b).replace('%s',m.length):'';}
 var nm=document.getElementById('bpev-nomatch');if(nm){nm.hidden=m.length>0;}
 var pg=document.getElementById('bpev-pager');if(!pg){return;}
 if(pages<2){pg.innerHTML='';return;}
 var h='';for(i=1;i<=pages;i++){h+='<button type=\"button\" class=\"'+(i===window.BpevPage?'cur':'')+'\" onclick=\"BpevApply('+i+')\">'+i+'</button>';}pg.innerHTML=h;
};";
}

function bpev_style():string{
	if(isset($GLOBALS["BPEV_STYLE"])){return "";}
	$GLOBALS["BPEV_STYLE"]=true;
	$A="#0d9488";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){ $a=brand_ui_accents(brand_palette()); if(!empty($a["accent"])){$A=$a["accent"];} }
	return "<style>
.bpev{color:#0f172a;font-size:14px;margin-top:10px}
.bpev *{box-sizing:border-box}
.bpev p{margin:0}
.bpev-banner{display:flex;gap:16px;align-items:flex-start;background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;padding:18px 20px;margin-bottom:16px}
.bpev-banner-ico{flex:0 0 44px;height:44px;border-radius:10px;background:#dbeafe;color:#1d4ed8;display:inline-flex;align-items:center;justify-content:center;font-size:19px}
.bpev-banner h3{font-size:17px;font-weight:600;margin:0 0 6px;color:#0f172a}
.bpev-banner p{font-size:14px;color:#334155;line-height:1.55;margin-bottom:12px}
.bpev-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}
.bpev-tile{display:block;width:100%;text-align:left;padding:14px 16px;font:inherit;color:inherit;background:#fff}
button.bpev-tile{cursor:pointer}
button.bpev-tile:hover{border-color:#94a3b8}
.bpev-tile-h{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:13px;font-weight:600;color:#475569}
.bpev-tile-v{font-size:22px;font-weight:700;margin:6px 0 2px;color:#0f172a}
.bpev-tile-v.bpev-ak-err{color:#b91c1c}
.bpev-tile-v.bpev-ak-warn{color:#b45309}
.bpev-tile-v.bpev-ak-off{color:#64748b}
.bpev-small{font-size:14px;font-weight:600}
.bpev-tile-s{font-size:13px;color:#64748b}
.bpev-filters{padding:12px 14px;margin-bottom:16px}
.bpev-frow{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.bpev-seg{display:inline-flex;flex-wrap:wrap;gap:2px;background:#f1f5f9;border-radius:10px;padding:3px}
.bpev-seg-b{border:0;background:transparent;color:#334155;font-size:13px;font-weight:600;padding:7px 12px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.bpev-seg-b.on{background:$A;color:#fff}
.bpev-n{font-size:13px;font-weight:600;background:rgba(15,23,42,.08);border-radius:999px;padding:0 7px;min-width:22px;text-align:center}
.bpev-seg-b.on .bpev-n{background:rgba(255,255,255,.25)}
.bpev-sw{display:flex;align-items:center;gap:10px;margin-left:auto}
.bpev-sw b{display:block;font-size:13px;font-weight:600}
.bpev-sw span{display:block;font-size:13px;color:#64748b}
.bpev-srow{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:12px}
.bpev-search{flex:1;min-width:320px;max-width:640px}
.bpev-ex{font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.bpev-chip{border:1px solid #d1fae5;background:#ecfdf5;color:#065f46;border-radius:6px;padding:1px 7px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;cursor:pointer}
.bpev-tl{overflow:hidden}
.bpev-day-h{display:flex;align-items:center;gap:8px;padding:10px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:13px;color:#475569;text-transform:uppercase;letter-spacing:.04em;position:sticky;top:0;z-index:1}
.bpev-day-d{text-transform:none;font-weight:400;color:#64748b}
.bpev-day-n{margin-left:auto;text-transform:none;letter-spacing:0;color:#64748b}
.bpev-rows{list-style:none;margin:0;padding:0}
.bpev-row{display:grid;grid-template-columns:150px 80px minmax(0,1fr) auto;gap:14px;align-items:center;padding:10px 18px;border-bottom:1px solid #f1f5f9;min-height:48px}
.bpev-row.bpev-s-error{box-shadow:inset 3px 0 0 #b91c1c;background:#fffbfb}
.bpev-sev{justify-self:start}
.bpev-time{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#334155}
.bpev-msg{min-width:0}
.bpev-msg b{display:block;font-size:14px;font-weight:600}
.bpev-s-error .bpev-msg b{color:#b91c1c}
.bpev-text{font-size:13px;color:#334155;overflow-wrap:anywhere;margin-top:1px}
.bpev-tags{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#94a3b8;margin-top:2px}
.bpev-pid{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#64748b;white-space:nowrap}
.bpev-nomatch{padding:28px;display:flex;flex-direction:column;align-items:center;gap:6px;color:#64748b}
.bpev-nomatch[hidden]{display:none}
.bpev-nomatch b{color:#0f172a}
.bpev-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;font-size:13px;color:#475569}
.bpev-older{color:#64748b;margin-top:2px}
.bpev-pg{display:flex;gap:6px;flex-wrap:wrap}
.bpev-pg button{min-width:34px;height:34px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#0f172a;font-size:13px;font-weight:600;cursor:pointer}
.bpev-pg button.cur{background:$A;border-color:$A;color:#fff}
.bpev-empty{padding:44px 20px}
.bpev-empty span{color:#64748b;max-width:520px}
.bpev .ak-btn:focus-visible,.bpev-seg-b:focus-visible,.bpev-chip:focus-visible,.bpev-pg button:focus-visible,button.bpev-tile:focus-visible,.bpev .ak-sw:focus-visible{outline:2px solid $A;outline-offset:2px}
@media(max-width:1200px){.bpev-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.bpev-sw{margin-left:0}}
</style>";
}
