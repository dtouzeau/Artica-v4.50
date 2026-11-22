<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
include_once(dirname(__FILE__)."/ressources/class.dashboard2.ui.inc");
include_once("/usr/share/artica-postfix/ressources/class.postgres.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["DEBUG_PRIVS"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){page_no_privs();die();}
clean_xss_deep();
if(isset($_GET["trial-explain"])){trial_explain_js();exit;}
if(isset($_GET["trial-popup"])){trial_explain_popup();exit;}
if(isset($_GET["widget-hostname"])){widget_hostname();exit;}
if(isset($_GET["widget-info"])){widget_info();exit;}
xgen();
function xgen():bool{
    $users              = new usersMenus();
    $page               = CurrentPageName();
    $IsWizardExecuted   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsWizardExecuted"));
    $EnableOpenVPNServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer"));

    // Le drapeau de service prime sur la presence du paquet : un OpenVPN
    // installe mais desactive ne doit pas compter comme disponible.
    if($EnableOpenVPNServer==0){$users->OPENVPN_INSTALLED=false;}

    // Opérateur de tenant SIEM (retour Maxime REGENT 2026-09-11) : sans droit système,
    // l'état de l'appliance (fw.system.status.php) ne lui rend rien et sa page d'arrivée
    // est vide. Son tableau de bord statistiques prend la place — c'est la seule chose
    // qu'il vient voir, et son périmètre y est résolu côté serveur.
    $status_url="fw.system.status.php?tabs=yes";
    if(!$users->AsSystemAdministrator && !$users->AsAnAdministratorGeneric){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"))==1){
            include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
            $stats=new statistics_appliance();
            if(!$stats->denied && !$stats->global){ $status_url="fw.statistics.dashboard.php"; }
        }
    }
    // Compte AsMetaAdmin seul : le bandeau de l'appliance n'est meme pas calcule (voir plus bas).
    $MetaAdminOnly=$users->isMetaAdminOnly();
    $html="
    <!-- IsWizardExecuted ===  $IsWizardExecuted -->
    ".dashboard2_css()."
    <div id='dash2'>
    <div class=\"dashboard-header\" style='padding-top:3px'>
    ".($MetaAdminOnly?"":dashboard2_hero_grid())."
        <div id='index-warning'></div>
        <div id='applications-status'></div>
        <div id='main-dashboard-status' class='white-bg' style='padding-top:10px'></div>
    </div>
    </div>

    <script>
        $.address.state('/');
        $.address.value('index');
        if(document.getElementById('main-dashboard-status') ){ LoadAjaxSilent('main-dashboard-status','$status_url');}


    </script>
    ";
    // Compte qui n'a que AsMetaAdmin (administrateur de tenants Artica Meta, spec 2026-11-13
    // 5.10.6) : ni bandeau de l'appliance ni etat de ses services -- ils nomment les noeuds et
    // services de tous les clients. Son accueil est le tableau de bord Artica Meta, filtre par
    // le daemon sur son tenant.
    if($MetaAdminOnly){
        $html="
    <div id='meta-admin-home' class='white-bg' style='padding-top:10px'></div>
    <script>
        $.address.state('/');
        $.address.value('index');
        if(document.getElementById('meta-admin-home') ){ LoadAjaxSilent('meta-admin-home','fw.netagents.php?home=yes');}
    </script>
    ";
    }
    VERBOSE("template_admin() OK", __LINE__);
    $tpl=new template_admin(null,$html);
    if(isset($_GET["content"])) {
        $tpl=new templates();
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }


    echo $tpl->build_firewall();
    return true;
}


function trial_explain_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog_modal("{trial_period}","$page?trial-popup=yes");

}
function trial_explain_popup():bool{
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{trial_period}||{trial_period_explain}");
    $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_certificate)."</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function widget_hostname():bool{
    $tpl=new  template_admin();
    $data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FW_INDEX_PHP_HOSTNAME");
    echo $tpl->_ENGINE_parse_body($data);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/widget/hostname");
    return true;
}
function widget_unbound_active():int{
    $EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    $UnboundInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($EnablePDNS==0) {
        if ($UnboundInstalled == 1) {
            if ($UnboundEnabled == 1) {
                return 1;
            }
        }
    }
    return 0;

}
function widget_category_service():bool{

    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==0){
        return widget_Suricata();
    }
    $DnscatzDomain=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("fw.haclDnscatzDomain");
    if(strlen($DnscatzDomain)<3){
        $DnscatzDomain="categories.tld";
    }
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon="fa fa-engine";
    $sock=new sockets();
    $data=$sock->REST_API("/dnscatz/status");
    $json=json_decode($data);

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $widget_title="{APP_UFDBCAT}";
    $running=$ini->get("APP_UFDBCAT","running",0);

    if($running==0){
        $explain_error ="{stopped}";

    }
    $CountOfDatabase=array();
    $DBUSED="{none}";
    if(is_file("/etc/dnscatz.databases.conf")){
        $CurrentLoaded=explode(",",trim(@file_get_contents("/etc/dnscatz.databases.conf")));
        $CountOfDatabase=count($CurrentLoaded);
        if($CountOfDatabase>0) {
            $CountOfDatabase = count($CurrentLoaded) - 1;
        }
    }

    if($CountOfDatabase>0){
        $DBUSED=$CountOfDatabase;
    }

    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{
        $uptime=$ini->get("APP_UFDBCAT","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }
    $DISPLAY[]="<li><span class=\"".ico_database." m-r-xs\"></span>&nbsp;{used_databases}:</label> $DBUSED</li>";
    $DISPLAY[]="<li><span class=\"".ico_earth." m-r-xs\"></span>&nbsp;{domain}:</label> $DnscatzDomain</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";
    echo $tpl->_ENGINE_parse_body($widget1);
    return true;


}

// widget_siem_tenant — la carte vue par un opérateur de tenant.
//
// Elle ne peut pas montrer les compteurs d'exploitation (taille de base, nœuds, événements/s) :
// ceux-là décrivent l'APPLIANCE, pas un périmètre, et /api/v1/admin/overview les calcule sans
// aucun filtre de tenant. Elle montre donc ce que l'opérateur a le droit de voir — son propre
// trafic sur 24 h — et NOMME les tenants couverts dès qu'il y en a plusieurs, sans quoi un total
// additionnerait plusieurs organisations sans le dire.
function widget_siem_tenant($stats,$tpl):bool{
    $to=gmdate("Y-m-d\\TH:i:s\\Z");
    $from=gmdate("Y-m-d\\TH:i:s\\Z",time()-86400);
    $o=$stats->get("/api/v1/siem/overview?from=".urlencode($from)."&to=".urlencode($to));
    $DISPLAY=array();
    $bg="white-bg";
    if(!is_array($o) || !$stats->ok()){
        $bg="red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-triangle-exclamation m-r-xs\"></span>{api_unavailable}</li>";
        echo $tpl->_ENGINE_parse_body(widget_siem_card($bg,"",$DISPLAY));
        return true;
    }
    $k=isset($o["kpis"])&&is_array($o["kpis"])?$o["kpis"]:array();
    $ev=intval((isset($k["proxy_requests"])?$k["proxy_requests"]:0))
       +intval((isset($k["firewall_sessions"])?$k["firewall_sessions"]:0))
       +intval((isset($k["mail_messages"])?$k["mail_messages"]:0));
    $DISPLAY[]="<li><span class=\"fas fa-database m-r-xs\"></span><label>{last_24_hours}:</label> <strong>".siem_number($ev)."</strong> {events}</li>";

    $den=intval((isset($k["proxy_denied"])?$k["proxy_denied"]:0))
        +intval((isset($k["firewall_denied"])?$k["firewall_denied"]:0));
    $DISPLAY[]="<li><span class=\"fas fa-ban m-r-xs\"></span><label>{blocked_traffic}:</label> ".siem_number($den)."</li>";

    // Les tenants couverts, seulement s'il y en a plusieurs : sur un périmètre unique la carte
    // reste exactement aussi sobre qu'avant.
    $tenants=siem_scope_tenants($o);
    if(count($tenants)>0){
        $DISPLAY[]="<li><span class=\"fas fa-building m-r-xs\"></span><label>{siem_scope_covers} (".count($tenants)."):</label> ".siem_h(@implode(", ",$tenants))."</li>";
    }
    echo $tpl->_ENGINE_parse_body(widget_siem_card($bg,"",$DISPLAY));
    return true;
}
function widget_pdns():bool{
    $EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    if($EnablePDNS==0){
        VERBOSE("--- widget_postfix",__LINE__);
        return widget_postfix();}
    $tpl=new template_admin();
    $bg="white-bg";
    $queries=0;
    $title_icon=ico_database;
    $APP_PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
    $sock=new sockets();
    $data=$sock->REST_API("/pdns/status");
    $json=json_decode($data);

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    //var_dump($json->Recursor);

    $explain_error=null;
    $widget_title="{APP_PDNS} v$APP_PDNS_VERSION";
    $running=$ini->get("APP_PDNS","running",0);
    include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
    if($running==0){
        $explain_error ="{stopped}";

    }


    $HIT = $json->Recursor->{"cache-hits"};
    $MISS= $json->Recursor->{"cache-misses"};
    VERBOSE("HIT: $HIT",__LINE__);
    VERBOSE("MISS: $MISS / ".$json->Recursor->{"cache-misses"},__LINE__);

    $TOTAL=$MISS+$HIT;
    $QUERIES_TEXT=$tpl->FormatNumber($TOTAL);

    $q=new mysql_pdns();
    $records=$q->COUNT_ROWS("records");
    $domains=$q->COUNT_ROWS("domains");
    $records=$tpl->FormatNumber($records);
    $domains=$tpl->FormatNumber($domains);


    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{
        $uptime=$ini->get("APP_PDNS","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }

    $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{records}:</label> $records</li>";
    $DISPLAY[]="<li><span class=\"fas fa-microchip m-r-xs\"></span>&nbsp;{queries}:</label> $QUERIES_TEXT</li>";
    $DISPLAY[]="<li><span class=\"fas fa-globe-africa m-r-xs\">
        </span>&nbsp;{domains}:</label> $domains</li>";

    $logobg="img/pdnslogo.png";
    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row' style='background-image: url($logobg);background-repeat: no-repeat;
background-position-x: right;
background-position-y: bottom;'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_postfix_iniget():string{
    $mem=new lib_memcached();
    $Key="fw.index.php".__FUNCTION__;
    if(!function_exists("json_decode")){
        return "[POSTFIX]\nrunning=0";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/master/status"));
    if (json_last_error()> JSON_ERROR_NONE) {
        $data=$mem->getKey($Key);
        if(strlen($data)>0){
            return $data;
        }
        return "[POSTFIX]\nrunning=0";
    }
    if(!property_exists($json,"Info")){
        $data=$mem->getKey($Key);
        if(strlen($data)>0){
            return $data;
        }
        return "[POSTFIX]\nrunning=0";
    }
    $mem->saveKey($Key,strval($json->Info));
    return strval($json->Info);
}
function widget_postfix():bool{
    $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
    if($POSTFIX_INSTALLED==0){$EnablePostfix=0;}
    if($EnablePostfix==0){
        VERBOSE("--- widget_nginx",__LINE__);
        return widget_nginx();}
    $tpl=new template_admin();
    $bg="white-bg";

    $title_icon=ico_message;

    $explain_error=null;
    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $widget_title="{APP_POSTFIX} v$POSTFIX_VERSION";
    $ini=new Bs_IniHandler();
    $ini->loadString(widget_postfix_iniget());
    $running=$ini->get("APP_POSTFIX","running",0);

    if($running==0){
        $explain_error ="{stopped}";
    }

    $SMTP_REFUSED_INT=$tpl->FormatNumber($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOfSMTPThreats"));
    $postqueuep=$tpl->FormatNumber(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM")));
    $EnablePostfixMultiInstance = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");

    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{
        $uptime=$ini->get("APP_POSTFIX","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }

    if($EnablePostfixMultiInstance==1){
        $EnablePostfixMultiInstance_text="{active2}";
    }else{
        $EnablePostfixMultiInstance_text="{inactive}";
    }

    $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{refused}:</label> $SMTP_REFUSED_INT</li>";
    $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{queue}:</label> $postqueuep</li>";
    $DISPLAY[]="<li><span class=\"fas ".ico_history." m-r-xs\">
        </span>&nbsp;{multiple_instances}:</label> $EnablePostfixMultiInstance_text</li>";


    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_nginx():bool{
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){
        VERBOSE("--- widget_cicap",__LINE__);
        return widget_cicap();}
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon="fas fa-globe-africa";
    $explain_error=null;
    $APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    $widget_title="{APP_NGINX} v$APP_NGINX_VERSION";
    $running=0;
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/service/status");
    $json=json_decode($data);
    $ini=new Bs_IniHandler();

    if (json_last_error() == JSON_ERROR_NONE) {
        if ($json->Status) {
            $ini->loadString($json->Info);
            $running = $ini->get("APP_NGINX", "running", 0);
        }
    }
    if($running==0){
        $explain_error ="{stopped}";
    }

    $GLOBAL_NGINX_REQUESTS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLOBAL_NGINX_REQUESTS"));
    $GLOBAL_NGINX_ACNX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLOBAL_NGINX_ACNX"));
    $NGINX_ALL_HOSTS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_ALL_HOSTS"));
    $QUERIES_TEXT=$tpl->FormatNumber($GLOBAL_NGINX_REQUESTS);
    $CNX_TEXT=$tpl->FormatNumber($GLOBAL_NGINX_ACNX);




    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{

        $uptime=$ini->get("APP_NGINX","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }
    if(!is_array($NGINX_ALL_HOSTS)){$NGINX_ALL_HOSTS=array();}
    $QUERIES_TEXT=$tpl->td_href($QUERIES_TEXT,null,"");
    $CNX_TEXT=$tpl->td_href($CNX_TEXT,null,"");
    $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{queries}:</label> $QUERIES_TEXT</li>";
    $DISPLAY[]="<li><span class=\"fas fa-microchip m-r-xs\"></span>&nbsp;{connexions}:</label> $CNX_TEXT</li>";
    $DISPLAY[]="<li><span class=\"fas fa-globe-africa m-r-xs\">
        </span>&nbsp;{hosts}:</label> ".count($NGINX_ALL_HOSTS)."</li>";


    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_dnsdist():bool{
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==0){
        VERBOSE("-- widget_openvpn",__LINE__);
        return widget_openvpn();}
    $tpl=new template_admin();
    $bg="white-bg";
    $queries=0;
    $title_icon="fab fa-gripfire";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
    $explain_error=null;
    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $widget_title="{APP_DNSDIST} v$APP_DNSDIST_VERSION";
    include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
    $ini=new Bs_IniHandler(PROGRESS_DIR."/dnsdist.status");

    $running=$ini->get("APP_DNSDIST","running",0);

    if($running==0){
        $explain_error ="{stopped}";

    }

    $dnsdis=new dnsdist_status("127.0.0.1");
    if(!$dnsdis->generic_stats()) {
        $running=0;
       $explain_error = $dnsdis->error;

    }else{
        $queries=$dnsdis->mainStats["queries"];
    }
    $QUERIES_TEXT=$tpl->FormatNumber($queries);
    $prc=$dnsdis->mainStats["cache_rate"];

    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{
        $uptime=$ini->get("APP_DNSDIST","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }
    $EnableDNSFirewall_text="{inactive}";
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }


    $new_host_resolve=$tpl->td_href("{new_host_resolve}",null,"Loadjs('fw.dns.unbound.php?addhost-js=yes&function=$function');");

    $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{queries}:</label> $QUERIES_TEXT</li>";
    $DISPLAY[]="<li><span class=\"fas fa-microchip m-r-xs\"></span>&nbsp;{cache_rate}:</label> $prc%</li>";
    $DISPLAY[]="<li><span class=\"fa-solid fa-block-brick-fire\"></span>&nbsp;{reputation}:</label> $EnableDNSFirewall_text</li>";
    $DISPLAY[]="<li><span class=\"fa-solid fa-display-medical\"></span>&nbsp;$new_host_resolve</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_rbldnsd():bool{
    $sock=new sockets();
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon=ico_database;
    $json=json_decode($sock->REST_API("/rbldnsd/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);



    $explain_error=null;

    $widget_title="{APP_RBLDNSD}";

    $running=$ini->get("APP_RBLDNSD","running",0);
    if($running==0){
        $explain_error ="{stopped}";
    }

$RBLDNSD_BLCK_COUNT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_BLCK_COUNT");
	$RBLDNSD_WHITE_COUNT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_WHITE_COUNT");
	$RBLDNSD_COMPILE_TIME=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_COMPILE_TIME");

	$xtime=$tpl->time_to_date($RBLDNSD_COMPILE_TIME,true);

    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{
        $uptime=$ini->get("APP_RBLDNSD","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }
    $icodb=ico_database;
    $icoclock=ico_clock;
    $RBLDNSD_BLCK_COUNT=$tpl->FormatNumber($RBLDNSD_BLCK_COUNT);
    $RBLDNSD_WHITE_COUNT=$tpl->FormatNumber($RBLDNSD_WHITE_COUNT);
    $DISPLAY[]="<li><span class=\"$icodb\"></span>&nbsp;{blacklists}:</label> $RBLDNSD_BLCK_COUNT</li>";
    $DISPLAY[]="<li><span class=\"$icodb\"></span>&nbsp;{whitelists}:</label> $RBLDNSD_WHITE_COUNT</li>";
    $DISPLAY[]="<li><span class=\"$icoclock\"></span>&nbsp;{version_compile_machine}:</label> $xtime</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;


}
function widget_hacluster():bool{
    $tpl=new template_admin();


    $ha=json_decode( $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/info"));
    //var_dump($ha);
    $bg="white-bg";
    $title_icon="fas fa-code-branch";

    $json=json_decode( $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/status"));
    $explain_error=null;
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    if(preg_match("#^(.+?)-#",$version,$re)){
        $version=$re[1];
    }
    $widget_title="HaCluster v$version";
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $running=$ini->get("APP_HAPROXY_CLUSTER","running",0);
    $QUERIES_TEXT=0;
    $rate=0;
    $BytesKB="O";
    if($running==0){
        $explain_error ="{stopped}";
    }


    if(!$ha->status) {
        if($ha->BackendsCount>0) {
            $running = 0;
            $explain_error = "{connection_error}";
        }


    }else{
        $queries=$ha->CumConns;
        $QUERIES_TEXT=$tpl->FormatNumber($queries);
        $rate=$ha->MaxConnRate;
        $TotalBytesOut=$ha->TotalBytesOut;
        $BytesKB=FormatBytes($TotalBytesOut/1024);
    }


    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> $explain_error</li>";

    }else{
        $uptime=$ini->get("APP_HAPROXY_CLUSTER","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }
    if($ha->BackendsCount==0){
        $bug=ico_bug;
        $DISPLAY[]="<li class='text-danger'><span class=\"$bug m-r-xs\"></span>&nbsp;{no_backend_server}</label></li>";
    }else{
        $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{queries}:</label> $QUERIES_TEXT</li>";
    }


    $DISPLAY[]="<li><span class=\"fas fa-tachometer-alt\"></span>&nbsp;{rate}:</label> $rate/s</li>";
    $DISPLAY[]="<li><span class=\"fas fa-chart-area\"></span>&nbsp;{bandwidth}:</label> $BytesKB</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$widget_title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;


}
// Bastion RDS Proxy : prime sur le resolveur DNS (et sur tout ce que widget_unbound()
// teste ensuite). Metriques lues en un appel /rdpproxy/state (sessions connectees,
// strategies actives et enregistrees, port, services), plus la duree de fonctionnement
// (/rdpproxy/status) et le nombre d'enregistrements video. RDPPROXY_CNX n'est pas
// repris : aucun code ne l'ecrit, il vaudrait toujours 0.
function widget_rdpproxy():bool{
    include_once(dirname(__FILE__)."/ressources/class.rdpproxy.inc");
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon="fa-solid fa-display";
    $DISPLAY=array();

    $error="";
    $st=rdpproxy_state($error);
    $version=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION"));
    if($error!=""){
        $bg="red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{error}: ".htmlspecialchars($error,ENT_QUOTES)."</li>";
    }else{
        if(!empty($st["proxy"]["version"])){$version=(string)$st["proxy"]["version"];}
        if(empty($st["proxy"]["running"])){
            $bg="red-bg";
            $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{APP_RDPPROXY}: {stopped}</li>";
        }else{
            $uptime="";
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/status"));
            if(is_object($json) && !empty($json->Status) && isset($json->Info)){
                $ini=new Bs_IniHandler();
                $ini->loadString($json->Info);
                $uptime=trim((string)$ini->get("APP_RDPPROXY","uptime"));
            }
            $since=$uptime==""?"":" {since} ".htmlspecialchars($uptime,ENT_QUOTES);
            $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:$since</li>";
        }
        // Sans authentificateur, plus aucune connexion n'aboutit : a voir des l'accueil.
        if(empty($st["hook"]["running"])){
            $bg="red-bg";
            $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{APP_RDPPROXY_AUTHHOOK}: {stopped}</li>";
        }
        $sessions=$tpl->FormatNumber(intval($st["sessions"] ?? 0));
        $DISPLAY[]="<li><span class=\"fas fa-users m-r-xs\"></span>&nbsp;{active_sessions}: <a href='/rdpproxy-sessions' style='color:inherit;text-decoration:underline'>$sessions</a></li>";

        $policies=intval($st["policies_enabled"] ?? 0);
        $recording=intval($st["policies_recording"] ?? 0);
        $rec=$recording>0?" &middot; {recorded}: ".$tpl->FormatNumber($recording):"";
        $DISPLAY[]="<li><span class=\"fas fa-list-ol m-r-xs\"></span>&nbsp;{policies}: ".$tpl->FormatNumber($policies)."$rec</li>";

        $verr="";$vpath="";$vsupported=false;$vtotal=0;
        rdpproxy_videos($verr,$vpath,$vsupported,1,$vtotal);
        $port=intval($st["listen_port"] ?? 3389);
        $videos=$verr==""?" &middot; {videos}: ".$tpl->FormatNumber($vtotal):"";
        $DISPLAY[]="<li><span class=\"fas fa-video m-r-xs\"></span>&nbsp;{listen_port}: $port$videos</li>";
    }
    $version=htmlspecialchars($version,ENT_QUOTES);
    $vtxt=$version==""?"":" v.$version";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_RDPPROXY}$vtxt</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";
    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function widget_unbound():bool{
    $tpl=new template_admin();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"))==1){
        VERBOSE("-- widget_rdpproxy",__LINE__);
        return widget_rdpproxy();
    }

    if(LogsSinkEnabled()){
        VERBOSE("-- widget_log_sink",__LINE__);
        return widget_log_sink();
    }

    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){
        return widget_hacluster();
    }
    $APP_RBLDNSD_ENABLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_ENABLED"));
    if($APP_RBLDNSD_ENABLED==1){
        VERBOSE("-- widget_rbldnsd",__LINE__);
        return widget_rbldnsd();
    }
    $EnableArticaSiem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"));
    // Une appliance SIEM n'est pas un résolveur : son widget d'accueil doit
    // parler d'événements et de stockage, pas de requêtes DNS. Le rôle proxy,
    // lui, garde la priorité historique (testé au-dessus).
    VERBOSE("--- widget_siem: $EnableArticaSiem",__LINE__);
    if($EnableArticaSiem==1){
        return widget_siem();
    }
    if(sambaEnabled()==1){
        VERBOSE("-- widget_samba",__LINE__);
        return widget_samba();
    }
    $users=new usersMenus();
    $EnableOpenVPNServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer"));
    if(!$users->OPENVPN_INSTALLED){$EnableOpenVPNServer=0;}
    if($EnableOpenVPNServer==1) {
        return widget_openvpn();
    }


    $widget_unbound_active=widget_unbound_active();
    if($widget_unbound_active==0){
        VERBOSE("-- widget_dnsdist",__LINE__);
        return widget_dnsdist();
    }
    $EnableDNSFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
    $bg="white-bg";
    $title_icon=ico_database;
    $statsf=PROGRESS_DIR."/unbound.control.stats";
    // The daemon writes the file (stats_noreset); exec.status.php no longer does.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/stats");

    $unbound_control=array();
    $f=explode("\n",@file_get_contents($statsf));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        $tbd=explode("=",$line);
        $unbound_control[$tbd[0]]=$tbd[1];

    }

   //* print_r($unbound_control);
    $QUERIES=$unbound_control["total.num.queries"];
    $QUERIES_TEXT=$tpl->FormatNumber($QUERIES);
    $cache=intval($unbound_control["mem.cache.rrset"])+intval($unbound_control["mem.cache.message"]);
    $cache=FormatBytes($cache/1024);


    // GET /unbound/status rend l'etat du service (INI [APP_UNBOUND]) ; remplace
    // framework/unbound.php?status, qui lancait exec.status.php --unbound en root
    // pour ecrire un fichier relu ici.
    $ini=new Bs_IniHandler();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/status"));
    if(is_object($json) && isset($json->Info)){$ini->loadString((string)$json->Info);}

    $running=$ini->get("APP_UNBOUND","running",0);

    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>&nbsp;{service}:</label> {stopped}</li>";

    }else{
        $uptime=$ini->get("APP_UNBOUND","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>&nbsp;{running}:</label> {since} $uptime</li>";
    }
    $EnableDNSFirewall_text="{inactive}";
if($EnableDNSFirewall==1){
    $EnableDNSFirewall_text=$tpl->td_href("{active2}","{info}","Loadjs('fw.dns.unbound.php?fw-status=yes');");}

    $DISPLAY[]="<li><span class=\"fas fa-bolt m-r-xs\"></span>&nbsp;{queries}:</label> $QUERIES_TEXT</li>";
    $DISPLAY[]="<li><span class=\"fas fa-microchip m-r-xs\"></span>&nbsp;{memory_cache}:</label> $cache</li>";
    $DISPLAY[]="<li><span class=\"fa-solid fa-block-brick-fire\"></span>&nbsp;{APP_DNS_FIREWALL}:</label> $EnableDNSFirewall_text</li>";





    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_UNBOUND} v.$UnboundVersion</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";
echo $tpl->_ENGINE_parse_body($widget1);
return true;


}
function LogsSinkEnabled():bool{
    $ActAsASyslogServer     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
    if($ActAsASyslogServer==0){return false;}
    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    if($EnableSyslogLogSink==0){return false;}
    return true;
}
function ServiceStatus(){
    $sock=new sockets();
    $data=$sock->REST_API("/syslog/status");
    $json=json_decode($data);
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $ini;

}

function widget_log_sink():bool{
    $tpl=new template_admin();
    $bg="white-bg";
    VERBOSE("-- widget_log_sink",__LINE__);
    $ini=ServiceStatus();
    $running=$ini->get("APP_RSYSLOG","running",0);
    $title_icon=ico_logsink;
    $Title="{logs_sink}";

    if($running==0){
        $bg = "red-bg";
        $title_icon="fa-solid fa-shield-slash";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{service}:</label> {stopped}</li>";

    }else{
        $uptime=$ini->get("APP_RSYSLOG","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>{running}:</label> {since} $uptime</li>";
    }

    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    $Clients=0;
    $size=0;

    if(is_array($SyslogSinkStatus)) {
        foreach ($SyslogSinkStatus as $fname => $array) {
            VERBOSE("..................$fname",__LINE__);
            if(!isset($array["SIZE"])){continue;}
            $size = $size + intval($array["SIZE"]);
            $Clients++;
        }
    }
    $size=FormatBytes($size/1024);
    $SYSLOG_MSG_RECEIVED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSLOG_MSG_RECEIVED"));
    $SYSLOG_MSG_RECEIVED=$tpl->FormatNumber($SYSLOG_MSG_RECEIVED);

    $DISPLAY[]="<li><span class=\"".ico_server." m-r-xs\"></span>{clients}:</label> $Clients/$size</li>";
    $DISPLAY[]="<li><span class=\"".ico_rain." m-r-xs\"></span>{received_messages}:</label>$SYSLOG_MSG_RECEIVED</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$Title</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function widget_cicap():bool{
    $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
    if($CicapEnabled==0){
        VERBOSE("-- widget_dhcp",__LINE__);
        return widget_kea();
    }
    $tpl=new template_admin();
    $bg="white-bg";
    $CiCapTEXT=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CiCapTEXT"));
    $USED_SERVERS=intval($CiCapTEXT["USED_SERVERS"]);
    $FREE_SERVERS=intval($CiCapTEXT["FREE_SERVERS"]);
    $BYTES_IN=intval($CiCapTEXT["BYTES_IN"]);
    $REQUESTS=$CiCapTEXT["REQUESTS"];
    $REQUESTS=$tpl->FormatNumber($REQUESTS);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?status=yes");
    $ini=new Bs_IniHandler(PROGRESS_DIR."/cicap.status");
    $running=$ini->get("C-ICAP","running",0);
    $title_icon="fa fa-shield";
    $CicapVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");
    $Title="{SERVICE_WEBAVEX}";
    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));

    $qpos=new postgres_sql();
    $threats=$qpos->COUNT_ROWS_LOW("webfilter");



    if($running==0){
        $bg = "red-bg";
        $title_icon="fa-solid fa-shield-slash";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{service}:</label> {stopped}</li>";

    }else{
        $uptime=$ini->get("C-ICAP","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>{running}:</label> {since} $uptime</li>";
    }

    if($EnableClamavInCiCap==0){
        $DISPLAY[]="<li><span class=\"fas fa-virus m-r-xs\"></span>{antivirus}:</label> OFF</li>";
    }else{
        if($CiCapTEXT["srv_clamav"]) {
            $DISPLAY[] = "<li><span class=\"fas fa-virus m-r-xs\"></span>{antivirus}:</label> ON</li>";
        }else{
            $bg = "red-bg";
            $title_icon="fa-solid fa-shield-slash";
            $DISPLAY[] = "<li><span class=\"text-dang fas fa-virus m-r-xs\"></span>{antivirus}:</label> <strong>{error}</strong>&nbsp;<i class='fa-solid fa-exclamation'></i></li>";
        }
    }



    $DISPLAY[]="<li><span class=\"fas fa-microchip m-r-xs\"></span>{processes}:</label> $USED_SERVERS/$FREE_SERVERS</li>";


    $DISPLAY[]="<li><span class=\"fa-solid fa-virus-slash m-r-xs\"></span>{threats}:</label> $threats</li>";
    $DISPLAY[]="<li><span class=\"fas fa-tachometer-alt-average m-r-xs\"></span>{requests}:</label> $REQUESTS</li>";
    $DISPLAY[]="<li><span class=\"fas fa-chart-line m-r-xs\"></span>{bandwidth}:</label> {$BYTES_IN}Kbs</li>";


    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>$Title $CicapVersion</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}

function widget_kea(){
    $tpl=new template_admin();
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $running=0;
    if($EnableKEA==0){
        VERBOSE("-- widget_unbound",__LINE__);
        return widget_dhcp();
    }
    $ini = new Bs_IniHandler();
    $DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KEA_VERSION");
    $bg="white-bg";
    $title_icon="fas fa-chart-network";
    $dhcpd_leases=$tpl->FormatNumber(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KeaLeasesNumber")));

    $DHCPD_COUNT_OF_QUERIES=$tpl->FormatNumber($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOFDHCPLast"));
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/status");
    $json = json_decode($data);
    if(property_exists($json,"Status")) {
        if ($json->Status) {

            $ini->loadString($json->Info);
            $running = $ini->get("APP_KEA_DHCPD4", "running", 0);
        }
    }
    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{service}:</label> {stopped}</li>";

    }else{
        $uptime=$ini->get("APP_KEA_DHCPD4","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>{running}:</label> {since} $uptime</li>";
    }

    $DISPLAY[]="<li><span class=\"fa fa-desktop m-r-xs\"></span>{computers_number}:</label> $DHCPD_COUNT_OF_QUERIES</li>";


    $dhcpd_leases=$tpl->td_href($dhcpd_leases,"","Loadjs('fw.kea.leases.php')");
    $DISPLAY[]="<li><span class=\"fas fa-laptop m-r-xs\"></span>{leases}:</label> $dhcpd_leases</li>";



    $EnableNetBox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetBox"));

    if($EnableNetBox==1){
        $active=$tpl->td_href("{active2}","","s_PopUp('/netbox',1024,648);");
        $DISPLAY[]="<li><span class=\"fas fa-tasks m-r-xs\"></span>Netbox:</label> $active</li>";
    }else{
        $DISPLAY[]="<li><span class=\"fas fa-tasks m-r-xs\"></span>Netbox:</label> {inactive2}</li>";
    }

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_DHCP} v.$DHCPDVersion</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function widget_dhcp(){
    $tpl=new template_admin();
    $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    if($EnableDHCPServer==0){
        VERBOSE("-- widget_unbound",__LINE__);
        return widget_unbound();}
    $DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");
    $dhcpd_free=0;
    $bg="white-bg";
    $title_icon="fas fa-chart-network";
    $ini = new Bs_IniHandler();
    $DHCPD_POOLS_JSON=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_POOLS_JSON");
    if(!function_exists("json_decode")){$DHCPD_POOLS_JSON="";}
    if(strlen($DHCPD_POOLS_JSON)>10){
        $json=json_decode($DHCPD_POOLS_JSON);
        $DHCPD_COUNT_OF_QUERIES=$tpl->FormatNumber($json->summary->defined);
        $dhcpd_leases=$tpl->FormatNumber($json->summary->used);
        $dhcpd_free=$tpl->FormatNumber($json->summary->free);
    }else{
        $q=new postgres_sql();
        $dhcpd_leases=$q->COUNT_ROWS_LOW("dhcpd_leases");
        $DHCPD_COUNT_OF_QUERIES=$tpl->FormatNumber($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_COUNT_OF_QUERIES"));

    }

    $running=0;
    if(!method_exists($GLOBALS["CLASS_SOCKETS"],"REST_API")){
        return false;
    }
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcpd/service/status");
    $json = json_decode($data);
    if(property_exists($json,"Status")) {
        if ($json->Status) {

            $ini->loadString($json->Info);
            $running = $ini->get("APP_DHCP", "running", 0);
        }
    }

    if($running==0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{service}:</label> {stopped}</li>";

    }else{
        $uptime=$ini->get("APP_DHCP","uptime");
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>{running}:</label> {since} $uptime</li>";
    }

    $DISPLAY[]="<li><span class=\"fa fa-desktop m-r-xs\"></span>{computers_number}:</label> $DHCPD_COUNT_OF_QUERIES</li>";

    $DISPLAY[]="<li><span class=\"fas fa-laptop m-r-xs\"></span>{leases}:</label> $dhcpd_leases</li>";
    if($dhcpd_free<>null){
        $DISPLAY[]="<li><span class=\"fas fa-laptop m-r-xs\"></span>{free} IP:</label> $dhcpd_free</li>";
    }

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_DHCP} v.$DHCPDVersion</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_docker():bool{
    include_once(dirname(__FILE__).'/ressources/class.docker.inc');
    $tpl=new template_admin();
    $bg="white-bg";
    $Main=unserialize(@file_get_contents("Docker/info.json"));
    $dock=new dockerd();
    $PermimeterID=$Main["perimeter"];
    $groupname=$Main["groupname"];
    $MaxInstances=$Main["MaxInstances"];
    $groupid=$Main["groupid"];
    $tag="com.articatech.artica.scope.$PermimeterID.backend.$groupid";
    $array=$dock->ContainersListByTag($tag);
    $CountOfInstances=count($array);
    $DISPLAY[]="<li><span class=\"".ico_networks."\"></span>&nbsp;{perimeter}:</label> $groupname</li>";
    $DISPLAY[]="<li><span class=\"".ico_server."\"></span>&nbsp;{maxchild}:</label>$CountOfInstances/$MaxInstances</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='".ico_docker." fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_DOCKER}</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
// widget_siem — la carte d'accueil d'une appliance Artica SIEM, sur le patron des
// autres widget_* : les chiffres essentiels du rôle (événements stockés, taille de
// la base ClickHouse, débit d'ingestion, nœuds du déploiement), lus sur
// /api/v1/admin/overview via l'include commun (jamais ClickHouse en direct).
// Un champ que l'API n'a pas renvoyé s'affiche « - », jamais 0 : « nodes » et
// « version » n'existent qu'à partir du binaire 1.0.22 (absent ≠ zéro).
function widget_siem():bool{
    include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
    $tpl=new template_admin();
    $bg="white-bg";
    $DISPLAY=array();
    $version="";

    $stats=new statistics_appliance();

    // Aucun tenant attribué : ce n'est PAS une panne, et la carte ne doit pas le dire. Avant la
    // feature 021 ce cas tombait dans la branche « API indisponible » en rouge, ce qui envoyait
    // l'exploitant chercher un service arrêté au lieu d'un droit manquant.
    if($stats->denied){
        $DISPLAY[]="<li><span class=\"fas fa-user-lock m-r-xs\"></span>{siem_no_tenant_granted}</li>";
        echo $tpl->_ENGINE_parse_body(widget_siem_card($bg,$version,$DISPLAY));
        return true;
    }

    // /api/v1/admin/overview compte les tables ENTIÈRES, sans filtre de tenant, et exige le rôle
    // global. Un opérateur de tenant n'a donc pas à le voir : il lit son propre périmètre par
    // /api/v1/siem/overview, qui l'applique côté serveur.
    if(!$stats->global){
        widget_siem_tenant($stats,$tpl);
        return true;
    }

    $o=$stats->get("/api/v1/admin/overview");
    if(!is_array($o) || !$stats->ok()){
        // API injoignable : la carte le DIT (fond rouge, motif) au lieu d'aligner
        // des zéros qui ressembleraient à une appliance saine et vide.
        $bg="red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-triangle-exclamation m-r-xs\"></span>{api_unavailable}</li>";
    }else{
        if(isset($o["version"]) && trim($o["version"])!=""){ $version=" v.".htmlspecialchars($o["version"]); }

        $total=isset($o["total_events"])?$tpl->FormatNumber(intval($o["total_events"])):"-";
        $DISPLAY[]="<li><span class=\"fas fa-database m-r-xs\"></span><label>{total_events}:</label> <strong>$total</strong></li>";

        $db=isset($o["db_bytes"])?siem_bytes(floatval($o["db_bytes"])):"-";
        $DISPLAY[]="<li><span class=\"fas fa-hard-drive m-r-xs\"></span><label>{database_size}:</label> $db</li>";

        if(isset($o["events_per_sec"]) && floatval($o["events_per_sec"])>0){
            $DISPLAY[]="<li><span class=\"fas fa-gauge-high m-r-xs\"></span><label>{siem_events_per_sec}:</label> ".number_format(floatval($o["events_per_sec"]),2)."/s</li>";
        }

        $nodes=isset($o["nodes"])?strval(intval($o["nodes"])):"-";
        $DISPLAY[]="<li><span class=\"fas fa-circle-nodes m-r-xs\"></span><label>{siem_nodes}:</label> $nodes</li>";
    }

    echo $tpl->_ENGINE_parse_body(widget_siem_card($bg,$version,$DISPLAY));
    return true;
}

// widget_siem_card — le gabarit de la carte SIEM, partagé par ses trois états (global, tenant,
// aucun tenant). Extrait pour que les variantes ne soient pas trois copies du même HTML, qui
// divergeraient à la première retouche.
function widget_siem_card($bg,$version,$DISPLAY):string{
    return "<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='fas fa-chart-area fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>Artica SIEM$version</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";
}

function widget_info():bool{
    VERBOSE("--- widget_info",__LINE__);
    $windows=null;
    $tpl=new  template_admin();
    $bg="white-bg";
    $error_ico=null;

    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        VERBOSE("--- widget_docker",__LINE__);
        return widget_docker();
    }else{
        VERBOSE("/etc/artica-postfix/AS_DOCKER_SERVICE",__LINE__);
    }

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        VERBOSE("--- widget_pdns",__LINE__);
        return widget_pdns();}

    $icotop=new icontop();
    $icotop->from_cache=true;
    $THESHIELDS_USERS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("THESHIELDS_USERS");
    $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/metrics/info"));

    if(property_exists($json,"version")){
        $realsquidversion=$json->version;
    }
    if(property_exists($json,"ClientsAccessingCache")){
        $THESHIELDS_USERS=$json->ClientsAccessingCache;
    }
    $base="/home/artica/rrd";
    $reqtot=@file_get_contents("$base/allqueries.cache");
    $CurentMem=intval(@file_get_contents("$base/squid_mem.cache"));


    $DISPLAY_ERRORS=array();
    $ERR=$icotop->NOTIFS_SQUID(array(),true);
    $js1="Loadjs('fw.rrd.php?img=allqueries')";
    $js2="Loadjs('fw.rrd.php?img=squidmem')";
    $js3="Loadjs('fw.rrd.php?img=proxy_users')";
    $js4="Loadjs('fw.rrd.php?img=squiddnsq')";
    $js5="Loadjs('fw.rrd.php?img=squidcpu')";


    if(!is_file("img/squid/allqueries-day.png")){
        $js1="Blur()";
    }
    if(!is_file("img/squid/squidmem-day.png")){
        $js2="Blur()";
    }
    if(!is_file("img/squid/proxy_users-day.png")){
        $js3="Blur()";
    }
    if(!is_file("img/squid/squiddnsq-day.png")){
        $js4="Blur()";
    }
    if(!is_file("img/squid/squidcpu-day.png")){
        $js5="Blur()";
    }
    //SquidClientIPCache

    $RRD_SQUID_IDNS_QUERIES=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_IDNS_QUERIES"));
    $RRD_SQUID_CPUS=floatval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_CPUS"));
    // "A propos" : les fonctionnalites du proxy, leur etat et leur raccourci.
    $sf=$tpl->td_href("{about_your_proxy}","{about2}","Loadjs('fw.proxy.about.php')");
    $sf1=$tpl->td_href("{requests}","{statistics}",$js1);
    $sf2=$tpl->td_href("{proxy_memory}","{statistics}",$js2);
    $sf3=$tpl->td_href("{members}","{statistics}",$js3);
    $sf4=$tpl->td_href("{DNS_QUERIES}","{statistics}",$js4);

    $DISPLAY[]="<li><span class=\"far fa-circle-info m-r-xs\"></span>$sf</li>";

    if($reqtot>0){
        $reqtot=$tpl->FormatNumber($reqtot);
        $DISPLAY[]="<li><span class=\"fas fa-cloud-showers-heavy m-r-xs\"></span>$sf1:$reqtot</li>";
    }
    if($CurentMem>0){
        $cpu_text="";
        if($RRD_SQUID_CPUS>0){
            $RRD_SQUID_CPUS=round($RRD_SQUID_CPUS,2);
            $cpu_text=  $tpl->td_href("CPU: $RRD_SQUID_CPUS%","{statistics}",$js5);
        }
        $DISPLAY[]="<li><span class=\"fas fa-memory m-r-xs\"></span>$sf2:{$CurentMem}MiB $cpu_text</li>";
    }
    if($THESHIELDS_USERS>0){
        $THESHIELDS_USERS=$tpl->FormatNumber($THESHIELDS_USERS);
        $DISPLAY[]="<li><span class=\"far fa-users m-r-xs\"></span>$sf3:$THESHIELDS_USERS</li>";
    }
    if($RRD_SQUID_IDNS_QUERIES>0){
        $RRD_SQUID_IDNS_QUERIES=$tpl->FormatNumber($RRD_SQUID_IDNS_QUERIES);
        $DISPLAY[]="<li><span class=\"fas fa-cloud-showers-heavy m-r-xs\"></span>$sf4:</label>$RRD_SQUID_IDNS_QUERIES</li>";
    }


    $CATEGORIZED_WEBSITES=$GLOBALS["CLASS_SOCKETS"]->CATEGORIZED_WEBSITES();
    if($CATEGORIZED_WEBSITES>0){
        $categorized_websites=$tpl->FormatNumber($CATEGORIZED_WEBSITES);
        $sf2=$tpl->td_href("{categorized_websites}","{about2}","Loadjs('fw.official.categories.php')");
        $DISPLAY[]="<li><span class=\"fa-solid fa-cloud-arrow-up m-r-xs\"></span>$sf2:</label>&nbsp;<strong>$categorized_websites</strong></li>";
    }




    if(count($ERR)>0) {
        $ERR_ARRAY=$icotop->ERROR_TO_ARRAY($ERR);
        foreach ($ERR_ARRAY as $index=>$ligne){
            if(!isset($ligne["JS"])){$ligne["JS"]=null;}
            $icon=$ligne["ICO"];
            $LEVEL=$ligne["LEVEL"];
            if($LEVEL=="WARN"){continue;}
            $JS=$ligne["JS"];
            $error=$ligne["ERROR"];
            $btn="btn-warning";
            $button=null;
            if($JS<>null){
                $button = "<button style=\"text-transform: capitalize;\"   
                           class=\"btn $btn btn-xs\" type=\"button\" 
                           OnClick=\"$JS\" id='$index'>" . $tpl->_ENGINE_parse_body("{fix_it}") ;
            }

            $DISPLAY_ERRORS[]="<li>
                           <span class=\"$icon m-r-xs\"></span>
                           <label>{error}:</label>$error&nbsp;$button
                            </li>";
        }

    }

    if(count($DISPLAY_ERRORS)>0) {
        $bg = "red-bg";
        $error_ico=@implode("\n",$DISPLAY_ERRORS);

    }

    if($icotop->IsActiveDirectory()){
        $windows=widget_get_activedirectortyname();
    }

    if(!is_null($realsquidversion)) {
        if (preg_match("#^([0-9\.]+)#", $realsquidversion, $re)) {
            $realsquidversion = $re[1];
        }
        if (strlen($realsquidversion) > 1) {
            $realsquidversion = " v." . $realsquidversion;
        }
    }

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='far fa-spider-web fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{your_proxy}$realsquidversion</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            $error_ico
                            $windows
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>

</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function widget_get_activedirectortyname():string{
    $data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos");
    if(strlen($data)==0){
        $ico=ico_microsoft;
    return "<li><span class=\"$ico m-r-xs\"></span><label>Active Directory:</label> {unknown}</li>";
    }
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($data);


    if(isset($array["WINDOWS_SERVER_NETBIOSNAME"])) {
        if(is_bool($array["WINDOWS_SERVER_NETBIOSNAME"])){
            $array["WINDOWS_SERVER_NETBIOSNAME"]="";
        }
    }
    if (isset($array["LDAP_SERVER"])) {
        if (is_bool($array["LDAP_SERVER"])) {
            $array["LDAP_SERVER"] = "";
        }
    }
    if (!isset($array["LDAP_SERVER"])) {
        $array["LDAP_SERVER"]="";
    }

    if(!isset($array["WINDOWS_SERVER_NETBIOSNAME"])) {
        if (isset($array["fullhosname"])) {
            $array["WINDOWS_SERVER_NETBIOSNAME"] = $array["fullhosname"];
        }
        if(!isset($array["WINDOWS_SERVER_NETBIOSNAME"])){
            $array["WINDOWS_SERVER_NETBIOSNAME"]=null;
        }
    }
    if( $array["WINDOWS_SERVER_NETBIOSNAME"]==null) {
        $array["WINDOWS_SERVER_NETBIOSNAME"] = $array["LDAP_SERVER"];
    }

    if( $array["WINDOWS_SERVER_NETBIOSNAME"]==null) {
        $array["WINDOWS_SERVER_NETBIOSNAME"]="unknown";
    }
    $ico=ico_microsoft;
    $hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"]));
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $ico=ico_emergency;
        $hostname="{license_error}";
    }

    return "<li><span class=\"$ico m-r-xs\"></span><label>Active Directory:</label> $hostname</li>";
}

/**
 * Serveur OpenVPN -- maillon de la chaine de repli des widgets.
 *
 * Place AVANT widget_vpn_client() : celui-ci couvre le cote CLIENT (table
 * vpn_client, tunnels sortants), celui-ci le cote SERVEUR. Sur une appliance
 * sans serveur OpenVPN, on rend widget_vpn_client() -- donc le comportement
 * est EXACTEMENT celui d'avant l'ajout.
 *
 * N'utilise PAS l'ancien chemin getFrameWork("cmd.php?openvpn-status=yes") :
 * son handler existe toujours (framework/cmd.php:266), mais il fait ecrire un
 * fichier par exec.status.php pour le relire aussitot -- un aller-retour disque
 * par affichage, que deux chargements simultanes font se marcher dessus.
 * /openvpn/service/status rend la meme chose en une requete.
 */
function widget_openvpn():bool{
    $users=new usersMenus();
    // Le drapeau de service prime sur la presence du paquet : un OpenVPN
    // installe mais desactive n'a pas d'etat a montrer.
    $EnableOpenVPNServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer"));
    if(!$users->OPENVPN_INSTALLED OR $EnableOpenVPNServer==0){
        VERBOSE("-- widget_vpn_client",__LINE__);
        return widget_vpn_client();
    }

    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon=ico_shield;
    $OpenVPNVersion=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion"));
    $VERSION=($OpenVPNVersion!=="")?" v$OpenVPNVersion":"";

    // --- etat du service -----------------------------------------------------
    $running=false;
    // « lecture ratee » et « service arrete » sont deux etats DIFFERENTS, et le
    // widget ne doit pas affirmer le second quand il n'a mesure que le premier.
    $reached=false;
    // « uptime » de cet INI est une CHAINE LISIBLE ("3 Days, 16 hours"), pas un
    // nombre de secondes : SERVICE_STATUS l'affiche tel quel apres {since}
    // (class.template-admin.inc:13717). Un intval() y lirait 3, et time()-3
    // afficherait « 0 minute » sans que rien ne le signale.
    $uptime="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/service/status"));
    if(is_object($json) && isset($json->Status) && $json->Status && property_exists($json,"Info")){
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $reached=true;
        $running=(intval($ini->_params["APP_OPENVPN"]["running"] ?? 0)==1);
        $uptime=trim((string)($ini->_params["APP_OPENVPN"]["uptime"] ?? ""));
    }

    if(!$running){
        // Rouge dans les deux cas -- une route de statut muette merite autant
        // l'attention qu'un service arrete -- mais le LIBELLE dit ce qu'on
        // sait, jamais ce qu'on suppose.
        $bg="red-bg";
        $etat=$reached?"{stopped}":"{unavailable}";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{APP_OPENVPN}: $etat</li>";
        return widget_openvpn_render($tpl,$bg,$title_icon,$VERSION,$DISPLAY);
    }

    $ico_time=ico_clock;
    if($uptime!==""){
        // Affichee TELLE QUELLE, comme le fait SERVICE_STATUS. Ne pas tenter
        // svctile_uptime() pour la compacter : elle est PRIVATE, et
        // method_exists() rend true sur une methode privee -- le garde aurait
        // ete inoperant et l'appel un fatal, c'est-a-dire un spinner sans fin.
        $DISPLAY[]="<li><span class=\"$ico_time m-r-xs\"></span>{running}:</label> {since} $uptime</li>";
    }else{
        $DISPLAY[]="<li><span class=\"$ico_time m-r-xs\"></span>{running}</li>";
    }

    // --- sessions ------------------------------------------------------------
    // Une lecture RATEE n'est pas une absence de session : afficher « 0 »
    // parce que la route n'a pas repondu serait indiscernable d'un serveur
    // reellement inoccupe. Dans ce cas la ligne n'est pas emise du tout.
    $scan=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/session/scan"));
    if(is_object($scan) && isset($scan->Status) && $scan->Status && property_exists($scan,"List")){
        $list=is_array($scan->List)?$scan->List:array();
        $rx=0;$tx=0;
        foreach($list as $o){
            $rx+=floatval($o->BytesReceived ?? 0);
            $tx+=floatval($o->BytesSent ?? 0);
        }
        $n=count($list);
        $icodown=ico_download;
        $icoupload=ico_upload;
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>{sessions}:</label> $n</li>";
        // FormatBytes attend des KILO-octets, pas des octets.
        $DISPLAY[]="<li><span class=\"$icodown m-r-xs\"></span>{reception}:</label> ".FormatBytes($rx/1024)."</li>";
        $DISPLAY[]="<li><span class=\"$icoupload m-r-xs\"></span>{transmission}:</label> ".FormatBytes($tx/1024)."</li>";
    }

    return widget_openvpn_render($tpl,$bg,$title_icon,$VERSION,$DISPLAY);
}

/** Le gabarit, separe pour que les deux sorties de widget_openvpn() le partagent. */
function widget_openvpn_render($tpl,$bg,$title_icon,$VERSION,$DISPLAY):bool{
    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_OPENVPN}$VERSION</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";
    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function widget_vpn_client():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM vpn_client WHERE enable=1");
    $CountOfConnections=intval($ligne["tcount"]);
    if($CountOfConnections==0) {return widget_category_service();}

    $bg="white-bg";
    $title_icon="fa fa-compress";

    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM vpn_client WHERE enable=1 AND status >100");
    $CountOfFailedCnx=intval($ligne["tcount"]);
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/openvpn/clients/txtrx"));


    if($CountOfFailedCnx>0){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{stopped}: $CountOfFailedCnx {connections}</label> {stopped}</li>";

    }else{
        $DISPLAY[]="<li><span class=\"fas fa-plug m-r-xs\"></span>{running}:</label> $CountOfConnections {connections}</li>";
    }
    $icodown=ico_download;
    $icoupload=ico_upload;

    $reception=FormatBytes($json->Rx/1024);
    $transmission=FormatBytes($json->Tx/1024);

    $DISPLAY[]="<li><span class=\"$icodown m-r-xs\"></span>{reception}:</label> $reception</li>";

    $DISPLAY[]="<li><span class=\"$icoupload m-r-xs\"></span>{transmission}:</label> $transmission</li>";
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");


    $EnableArticaAsGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
    $gwText="{no}";
    if ($EnableArticaAsGateway==1){
        $gwText="{yes}";
    }
    $icor="fas fa-router";
    $DISPLAY[]="<li><span class=\"$icor m-r-xs\"></span>{ARTICA_AS_GATEWAY}:</label> $gwText</li>";


    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{vpn_client} v.$OpenVPNVersion</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_Meta():bool{
    $EnableArticaMeta=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMeta"));
    if($EnableArticaMeta==0){
        return widget_samba();
    }
    // Compteurs du parc Meta et taille du depot : comptes Meta seulement (sinon, sans en-tete de
    // perimetre, le daemon repondrait pour tout le parc de l'infogerant).
    if(!function_exists("meta_node_admin") || !meta_node_admin()){
        return widget_samba();
    }
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon="fa-regular fa-arrows-to-circle";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/stats"));
    if (json_last_error() > JSON_ERROR_NONE) {
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{error}</label> </li>";
    }
    $total = isset($json->total) ? intval($json->total) : 0;
    $offline = isset($json->offline) ? intval($json->offline) : 0;
    $ico=ico_computer;
    $DISPLAY[]="<li><span class=\"$ico m-r-xs\"></span>{agents}:</label> $total</li>";
    if($offline>0) {
        $DISPLAY[] = "<li class='text-danger'><span class=\"$ico m-r-xs\"></span>{offline}:</label> $offline</li>";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/sync/status"));
    $repoSizeHuman = isset($json->repo_size_human) ? $json->repo_size_human : '{none}';
    $ico=ico_database;
    $DISPLAY[] = "<li><span class=\"$ico m-r-xs\"></span>{repository}:</label> $repoSizeHuman</li>";
    $ico=ico_version;
    $DebianAgentStorageVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    $DISPLAY[] = "<li><span class=\"$ico m-r-xs\"></span>{version}:</label> $DebianAgentStorageVersion</li>";

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>Artica Meta</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>".@implode(" ",$DISPLAY)."</ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function widget_Suricata():bool{
    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
    if($EnableSuricata==0){
        return widget_Meta();
    }
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon=ico_sensor;
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    $VERSION=$data->Version;
    if(!$data->Status OR !$data->Running){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{IDS}: {stopped}</label> </li>";
    }else{

        $Stats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/stats"));
        $stats2=json_decode($Stats->Stats);
        $kernel_packets=$tpl->FormatNumber($stats2->message->capture->kernel_packets);
        $ico_time=ico_clock;
        $ico=ico_engine_warning;
        $Since=time()-$data->Uptime;
        $since=distanceOfTimeInWords($Since,time());
        $DISPLAY[]="<li><span class=\"$ico_time m-r-xs\"></span>{running}:</label> {since} $since</li>";
        $CountOfRules=intval($data->Alerts);
        $icor=ico_list_opt;
        $icoz=ico_list;
        $Activerule=$tpl->FormatNumber($data->Info->ActiveRules);
        $DISPLAY[]="<li><span class=\"$ico m-r-xs\"></span>{detected_threats}:</label> $CountOfRules</li>";
        $DISPLAY[]="<li><span class=\"$icoz m-r-xs\"></span>{packets}:</label> $kernel_packets</li>";
        $DISPLAY[]="<li><span class=\"$icor m-r-xs\"></span>{rules}:</label> $Activerule</li>";
    }
    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{IDS} v.$VERSION</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;
}

function sambaEnabled():int{
    $EnableSamba=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSamba"));
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"))==0){$EnableSamba=0;}
    return $EnableSamba;
}

function widget_samba():bool{

    include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");
    if(sambaEnabled()==0){
        return  widget_Firewall();
    }

    $tpl=new template_admin();
    $samba=new ArticaSamba();
    try{
        $health=$samba->health();
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return false;
    }
    $bg="white-bg";
    $status=$health["status"] ?? "unknown";
    $version=$health["version"] ?? "";
    $instances=$health["instances"] ?? 0;
    $samba_version=$health["samba_version"]  ?? "???";
    $title_icon=ico_server;
    if($status<>"ok"){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{service}: {stopped}</label> </li>";
    }else{
        $ico=ico_clock;
        $DISPLAY[]="<li><span class=\"$ico m-r-xs\"></span>{service}:</label> {running} v$samba_version</li>";
        $ico=ico_list_opt;
        $DISPLAY[]="<li><span class=\"$ico m-r-xs\"></span>{instances}:</label> $instances {servers}</li>";
    }
    if($instances==0){
        $bg="red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{status}: <strong class='text-danger'>{no_smb_instance}</strong></label> </li>";
    }

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{APP_SAMBA} v.$version</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}



function widget_Firewall():bool{
    $tpl=new template_admin();


    $FireHolEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable");
    if($FireHolEnable==0){
        return widget_nothing();
    }
    $bg="white-bg";
    $title_icon=ico_firewall;
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/isactive"));


    if(!$data->Status){
        $bg = "red-bg";
        $DISPLAY[]="<li><span class=\"fas fa-exclamation-circle m-r-xs\"></span>{firewall_status}: {stopped}</label> </li>";
    }else{
        $ico=ico_clock;
        $since=distanceOfTimeInWords($data->Since,time());
        $DISPLAY[]="<li><span class=\"$ico m-r-xs\"></span>{running}:</label> {since} $since</li>";
        $CountOfRules=intval($data->Count);
        $ico=ico_list_opt;
        $DISPLAY[]="<li><span class=\"$ico m-r-xs\"></span>{rules}:</label> $CountOfRules {rules}</li>";
    }
    $EnableArticaAsGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
    $gwText="{no}";
    if ($EnableArticaAsGateway==1){
        $gwText="{yes}";
    }
    $icor="fas fa-router";
    $DISPLAY[]="<li><span class=\"$icor m-r-xs\"></span>{ARTICA_AS_GATEWAY}:</label> $gwText</li>";
    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{your_firewall} v.$IPTABLES_VERSION</h2>
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";

    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}
function widget_nothing(){
    $tpl=new template_admin();
    $bg="white-bg";
    $title_icon=ico_computer;
    $page=CurrentPageName();
    $DISPLAY=array();
    $installDHCPD="";
    $installFw=$tpl->framework_buildjs(
        "/firewall/install",
    "firehol.reconfigure.progress",
    "firehol.reconfigure.log",
    "mainsoft-progress",
    "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');");

    $DHCPD_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_INSTALLED"));

    if($DHCPD_INSTALLED==1) {
        // /dhcpd/service/install: /dhcpd/install never existed in the daemon
        // (404 "did you use the Correct version of the Web API service?").
        $installDHCPD = $tpl->framework_buildjs(
            "/dhcpd/service/install",
            "dhcpd.progress",
            "dhcpd.progress.log",
            "mainsoft-progress",
            "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');");
    }





    $topbuttons[] = array($installFw, ico_cd, "{firewall}");
    $topbuttons[] = array($installDHCPD, ico_cd, "{APP_DHCP}");

    $widget1="<div class=\"widget $bg p-xl\" style='padding-top:5px;padding-bottom:2px;min-height: 197px'>
<div class='row'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align:top;width:1%' nowrap><i class='$title_icon fa-7x' style='margin-top:10px'></i></td>
    <td style='vertical-align:top;'>
        <div class='col-xs-8 text-left'>
        <h2>{manage_your_server}</h2>
        <div id='mainsoft-progress'></div>
        <p>{no_mainsoft}</p>
        ".$tpl->table_buttons($topbuttons)."
        <ul class=\"list-unstyled m-t-md\" style='margin-top:5px'>
                            ".@implode(" ",$DISPLAY)."
                        </ul>
        </div>
     </td>
     </tr>
     </table>
</div>
</div>";
    echo $tpl->_ENGINE_parse_body($widget1);
    return true;

}





function page_no_privs():bool{
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    VERBOSE("BUILDING ERROR 500",__LINE__);
    $data=@file_get_contents("generic.html");
    $data=str_replace("_CODE_",500,$data);
    $data=str_replace("_TITLE_","{ERROR_NO_PRIVS}",$data);

    $tpl=new template_admin();
    $content=$tpl->_ENGINE_parse_body("<strong>{$_SESSION["uid"]}</strong>, {not allowed}<p>{session_expired_text}</p>");
    $content=str_replace("href=logon.php","{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes",$content);
    $data=str_replace("_DESC_",$content,$data);
    echo $tpl->_ENGINE_parse_body($data);
    return true;
}

