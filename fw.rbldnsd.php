<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.rbldnsd.tools.inc");
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["geoip-js"])){geoip_js();exit;}
if(isset($_GET["geoip-popup"])){geoip_popup();exit;}
if(isset($_POST["RbldnsdAPIGeo"])){geoip_save();exit;}
if(isset($_GET["params"])){settings_js();exit;}
if(isset($_GET["postprocessing-js"])){postprocessing_js();exit;}
if(isset($_GET["postprocessing-popup"])){postprocessing_popup();exit;}

if(isset($_GET["params-popup"])){settings_popup();exit;}
if(isset($_GET["dohproxy"])){dohproxy_js();exit;}
if(isset($_GET["dohproxy-popup"])){dohproxy_popup();exit;}
if(isset($_POST["IPQualityScoreAPI"])){postprocessing_save();exit;}
if(isset($_POST["DohProxyInterface"])){Save();exit;}
if(isset($_GET["dohproxy-disable-ask"])){dohproxy_uninstall_ask();exit;}
if(isset($_POST["dohproxy-disable"])){dohproxy_uninstall_confirm();exit;}
if(isset($_GET["lookup-js"])){lookup_js();exit;}
if(isset($_GET["lookup-popup"])){lookup_popup();exit;}
if(isset($_POST["lookup"])){$_SESSION["lookup"]=$_POST["lookup"];exit;}
if(isset($_GET["lookup-progress"])){lookup_progress();exit;}
if(isset($_GET["lookup-results"])){lookup_results();exit;}
if(isset($_POST["RbldnsdAPI"])){Save();exit;}
if(isset($_GET["api-js"])){api_js();exit;}
if(isset($_GET["api-popup"])){api_popup();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status_start();exit;}
if(isset($_GET["status-build"])){status_build();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_POST["RbldnsdInterface"])){Save();exit;}
page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{APP_RBLDNSD}",ico_database,"{APP_RBLDNSD_EXPLAIN}",
        "$page?tabs=yes","rbl-service",
        "progress-rbldnsd-restart");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_RBLDNSD}",$html);
	echo $tpl->build_firewall();return true;}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dohproxy_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{APP_DOH_PROXY}","$page?dohproxy-popup=yes");
}
function lookup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{lookup_address}","$page?lookup-popup=yes");
}
function api_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{APP_ACTIVE_DIRECTORY_REST}","$page?api-popup=yes");
}
function geoip_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RbldnsdAPIGeo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdAPIGeo"));
    $RbldnsdPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPort"));
    $proto="http";
    if ($RbldnsdPort == 0) {
        $RbldnsdPort = 2653;
    }
    $RbldnsdListenCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListenCertificate"));
    if(strlen($RbldnsdListenCertificate)>1){
        $proto="https";
    }

    $url="$proto://{$_SERVER["SERVER_ADDR"]}:$RbldnsdPort";

    $GEOIP_API_EXPLAIN=$tpl->_ENGINE_parse_body("{GEOIP_API_EXPLAIN}");
    $GEOIP_API_EXPLAIN=str_replace("%s",$url,$GEOIP_API_EXPLAIN);
    $form[]=$tpl->field_checkbox("RbldnsdAPIGeo","{enable_feature}",$RbldnsdAPIGeo);
    echo $tpl->form_outside("", $form,
        "{GEOIP_API}|$GEOIP_API_EXPLAIN|fas fa-map-marker","{apply}",
        "dialogInstance2.close();LoadAjax('status-build','$page?status-build=yes');"
    );

    return true;

}
function geoip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks("Save Geoip API feature in {$_POST["RbldnsdAPIGeo"]} in reputation server");
}
function api_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RbldnsdAPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdAPI"));
    $RbldnsdListen=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListen"));
    $RbldnsdListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListenPort"));
    if($RbldnsdListenPort==0){
        $RbldnsdListenPort=9507;
    }
    $RbldnsdListenCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListenCertificate"));

    $form[]=$tpl->field_checkbox("RbldnsdAPI","{enable_feature}",$RbldnsdAPI);
    $form[]=$tpl->field_interfaces("RbldnsdListen", "{listen_interface}", $RbldnsdListen);
    $form[]=$tpl->field_numeric("RbldnsdListenPort", "{listen_port}", $RbldnsdListenPort);
    $form[]=$tpl->field_certificate("RbldnsdListenCertificate", "{ssl_certificate}", $RbldnsdListenCertificate,true);

    $jsRestart=$tpl->framework_buildjs("/rbldnsd/reputation-injector/restart","reputation-injecter.progress",
        "rbldnsd.progress.log","progress-rbldnsd-restart");

    echo $tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjax('status-build','$page?status-build=yes');$jsRestart");
    return true;
}


function lookup_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_text_big("lookup","{ipaddr}","176.65.149.231",true);
    $html[]="<div id='lookup-progress'></div>";
    $html[]="<div id='lookup-results'></div>";
    $html[]=$tpl->form_outside("",$form,"","{find}","Loadjs('$page?lookup-progress=yes');");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function lookup_progress():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ipddr=urlencode($_SESSION["lookup"]);
   echo  $tpl->framework_buildjs("/rbldnsd/lookup/$ipddr","rbldnsd.lookup.progress","rbldnsd.lookup.log","lookup-progress","LoadAjax('lookup-results','$page?lookup-results=$ipddr');");
    return admin_tracks("Lookup an ip reputation of {$_SESSION["lookup"]}");
}
function lookup_results():bool{
    $tpl=new template_admin();
    $ipddr=$_GET["lookup-results"];
    $Fname="/usr/share/artica-postfix/ressources/logs/web/$ipddr.json";
    if(!file_exists($Fname)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error} $ipddr.json {no_such_file}"));
        return false;
    }
    $json=json_decode(file_get_contents($Fname));
    if($json->found==0){
        $lookup_address_db_failed=$tpl->_ENGINE_parse_body("{lookup_address_db_failed}");
        $lookup_address_db_failed=str_replace("%s","<strong>$ipddr</strong>",$lookup_address_db_failed);
        echo $tpl->div_warning("$ipddr||$lookup_address_db_failed");
        return false;
    }

    $q=new postgres_sql();
    $f=array();
    foreach ($json->lookups as $index=>$lookup){
        $description=$lookup->description;
        $date=$lookup->date;
        $src=$lookup->src;
        $ligne=$q->mysqli_fetch_array("SELECT description FROM rbl_sources WHERE id=$src");
        $database=$ligne["description"];
        $f[]=$tpl->div_explain("DB: $src||<div style='font-size:18px'>{found} {database} <strong>$database</strong> (<i style='font-size: 12px'>$description</i>) {created} $date</div>");

    }

    $html[]=@implode("\n",$f);
    $html[]="<H2>{DNSBL} <strong>$json->localRBLAdddr</strong></H2>";

    if(strlen($json->localRBLErr)>2){
        $html[]=$tpl->div_error($json->localRBLErr);
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    $html[]="<div class='center'>";
    if(strlen($json->localRBL)>2) {
        $html[] =$tpl->widget_rouge("{answer}",$json->localRBL,null,null,"100%");
    }else{
        $html[] =$tpl->widget_grey("{answer}","{unkown}",null,null,"100%");

    }
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return false;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    if(!isset($_SESSION["RBLDNSD"]["DB-CHOOSE"])){
        $_SESSION["RBLDNSD"]["DB-CHOOSE"]=1270002;
    }
	$array["{status}"]="$page?status=yes";
    $array["{databases}"]="fw.rbldnsd.black.php?database={$_SESSION["RBLDNSD"]["DB-CHOOSE"]}";
    $array["{sources}"]="fw.rbldnsd.sources.php";
    $array["{rules} {senders}"]="fw.rbldnsd.email.php";
    //$array["{reputation}"]="fw.rbldnsd.reputation.php";
	echo $tpl->tabs_default($array);
    return true;
}
function geoip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==0){
        return $tpl->js_error("{GEOIP_API_NO_FEATURE}");
    }
    return $tpl->js_dialog2("{GEOIP_API}","$page?geoip-popup=yes");
}
function settings_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?params-popup=yes");
}
function postprocessing_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{postprocessing}","$page?postprocessing-popup=yes");
}
function postprocessing_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RbldnsdPostProcessing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPostProcessing"));
    $RbldnsdPostProcessingSpamHaus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPostProcessingSpamHaus"));
    $IPQualityScoreAPI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPQualityScoreAPI");
    $RbldnsdPostProcessingDB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPostProcessingDB");
    $form[]=$tpl->field_checkbox("RbldnsdPostProcessing","{enable}",$RbldnsdPostProcessing);
    $DBS=TranslateTables();
    $databases=array();
    foreach ($DBS as $dbkey=>$ligne){
        $databases[$dbkey]=$ligne["title"];
    }
    $form[]=$tpl->field_checkbox("RbldnsdPostProcessingSpamHaus","SpamHaus {enable}",$RbldnsdPostProcessingSpamHaus);
    $form[]=$tpl->field_array_hash($databases,"RbldnsdPostProcessingDB","{database}",$RbldnsdPostProcessingDB);
    $form[]=$tpl->field_text("IPQualityScoreAPI","IPQualityScore  {API_KEY}",$IPQualityScoreAPI);
    echo $tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjax('status-build','$page?status-build=yes');");
    return true;
}
function postprocessing_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/myself/restart");
    return true;
}
function settings_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RbldnsdInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdInterface");
    $RbldnsdPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPort"));
    $RbldnsdDomainName=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdDomainName");



    if ($RbldnsdPort == 0) {
        $RbldnsdPort = 2653;
    }

    if($RbldnsdInterface==null){$RbldnsdInterface="eth0";}
    if($RbldnsdDomainName==null){$RbldnsdDomainName="rbl.mydomain.com";}
    $form[]=$tpl->field_interfaces("RbldnsdInterface", "{listen_interface}", $RbldnsdInterface);
    $form[]=$tpl->field_numeric("RbldnsdPort", "{listen_port}", $RbldnsdPort);
    $form[]=$tpl->field_text("RbldnsdDomainName", "{domain}", $RbldnsdDomainName,true);
    //$form[]=$tpl->field_text("AbuseIPApiKey", "AbuseIP API Key", $AbuseIPApiKey,false);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/rbldnsd.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/rbldnsd.progress.log";
    $ARRAY["CMD"]="/rbldnsd/restart";
    $ARRAY["TITLE"]="{APP_RBLDNSD} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('rbldnsd-status','$page?service-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rbldnsd-restart')";
    echo $tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjax('status-build','$page?status-build=yes');$jsRestart");
    return true;
}
function status_start():bool{
    $page=CurrentPageName();
    echo "<div id='status-build'></div><script>LoadAjax('status-build','$page?status-build=yes');</script>";
    return true;
}
function status_postprocessing($tpl){
    $RbldnsdPostProcessing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPostProcessing"));
    $RbldnsdPostProcessingSpamHaus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPostProcessingSpamHaus"));
    $IPQualityScoreAPI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPQualityScoreAPI");
    $RbldnsdPostProcessingDB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPostProcessingDB");
    $DBS=TranslateTables();
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?postprocessing-js=yes')","AsDnsAdministrator");
    if($RbldnsdPostProcessing==0){
        $tpl->table_form_field_bool("{postprocessing}", 0, ico_disabled);
        return  $tpl;
    }

    if (strlen($RbldnsdPostProcessingDB)<3){
        $tpl->table_form_field_bool("{postprocessing}", 0, ico_disabled);
        return  $tpl;
    }

    $Post=false;
    if($RbldnsdPostProcessingSpamHaus==1){
        $Post=true;
        $text[]="SpamHaus";
    }
    if(strlen($IPQualityScoreAPI)>5){
        $Post=true;
        $text[]="IPQualityScore";
    }

    if(!$Post){
        $tpl->table_form_field_bool("{postprocessing}", 0, ico_disabled);
        return  $tpl;
    }

    $text[]="{database}: {$DBS[$RbldnsdPostProcessingDB]["title"]}";
    $tpl->table_form_field_text("{postprocessing}", @implode(", ",$text), ico_database);
    return $tpl;

}

function status_build():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$RbldnsdInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdInterface");
	$RbldnsdPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdPort"));
    $RbldnsdDomainName=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdDomainName");



	if ($RbldnsdPort == 0) {
        $RbldnsdPort = 2653;
	}
	
	if($RbldnsdInterface==null){$RbldnsdInterface="eth0";}
	if($RbldnsdDomainName==null){$RbldnsdDomainName="rbl.mydomain.com";}


    $APP_RBLDNSD_HOWTO=$tpl->_ENGINE_parse_body("{APP_RBLDNSD_HOWTO}");
    $APP_RBLDNSD_HOWTO=str_replace("rbl.mydomain.com",$RbldnsdDomainName,$APP_RBLDNSD_HOWTO);

	
	$tpl->table_form_section(null,"<div style='font-size: 13px'>$APP_RBLDNSD_HOWTO<div>");
    $tpl->table_form_field_js("Loadjs('$page?params=yes');","AsDnsAdministrator");
    $tpl->table_form_field_text("{listen}","$RbldnsdInterface:$RbldnsdPort",ico_nic);
    $tpl->table_form_field_text("{domain}",$RbldnsdDomainName,ico_earth);

    $RbldnsdAPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdAPI"));
    $tpl->table_form_field_js("Loadjs('$page?api-js=yes');","AsDnsAdministrator");
    if($RbldnsdAPI==0){
        $tpl->table_form_field_bool("{APP_ACTIVE_DIRECTORY_REST}", 0, ico_disabled);
    }else{
        $RbldnsdListen=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListen"));
        if(strlen($RbldnsdListen)<3){
            $RbldnsdListen="0.0.0.0";
        }
        $RbldnsdAPIGeo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdAPIGeo"));
        $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
        if($EnableGeoipUpdate==0){
            $RbldnsdAPIGeo=0;
        }

        $RbldnsdListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListenPort"));
        $RbldnsdListenCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdListenCertificate"));
        $tpl->table_form_field_text("{APP_ACTIVE_DIRECTORY_REST}","$RbldnsdListen:$RbldnsdListenPort ($RbldnsdListenCertificate)",ico_servcloud);
        $tpl->table_form_field_js("Loadjs('$page?geoip-js=yes');","AsDnsAdministrator");
        $tpl->table_form_field_bool("{GEOIP_API}", $RbldnsdAPIGeo, "fas fa-map-marker");
    }


    $tpl=status_postprocessing($tpl);


    $APP_DOH_PROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOH_PROXY_VERSION");
    $DOH_PROXY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOH_PROXY_INSTALLED"));

    if($DOH_PROXY_INSTALLED==0){
        $tpl->table_form_section("{DOH_WEB_SERVICE}");
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{APP_DOH_PROXY}","{not_installed}",ico_cd);
    }else{
        $tpl->table_form_section("DoH {APP_DOH_PROXY} v$APP_DOH_PROXY_VERSION");
        $EnableDohProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDohProxy"));

        if($EnableDohProxy==0) {
            $tpl->table_form_field_bool("{APP_DOH_PROXY}", 0, ico_disabled);
        }else{
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dohproxy/link"));
            $DohProxySSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxySSL"));
            $DohProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyPort"));
            if ($DohProxyPort==0) {
                $DohProxyPort=443;
                if($DohProxySSL==0){
                    $DohProxyPort=80;
                }
            }

            $Link=$json->Info;
            if(!is_null($Link)) {
                $Link = substr($Link, 0, 50) . "...";
            }
            $tpl->table_form_field_js("Loadjs('$page?dohproxy-disable-ask=yes')","AsDnsAdministrator");
            $tpl->table_form_field_bool("{APP_DOH_PROXY}", 1, ico_check);
            $tpl->table_form_field_js("Loadjs('$page?dohproxy=yes')","AsDnsAdministrator");
            if(is_null($Link)){
                $tpl->table_form_field_bool("DNS Stamp",0, ico_link);
            }else {
                $tpl->table_form_field_text("DNS Stamp", "<small>$Link</small>", ico_link);
            }
            $DohProxyInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyInterface");
            if(strlen($DohProxyInterface)<3){
                $DohProxyInterface="0.0.0.0";
            }
            $proxy_protocol="";
            $DohProxyProtocol=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyProtocol");
            $DohProxyHaProxy=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyHaProxy");
            if($DohProxyProtocol==1){
                $proxy_protocol=" ({proxy_protocol})";
            }
            if($DohProxyHaProxy==1){
                $proxy_protocol=" ({haproxypp_support})";
            }

            $tpl->table_form_field_text("{listen}","$DohProxyInterface:$DohProxyPort$proxy_protocol",ico_nic);
            if($DohProxySSL==1) {
                if($DohProxyPort==80){
                    $tpl->table_form_field_warn("{ssl_on_80_port}");
                }
                $DohProxyCertificate = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyCertificate");
                $tpl->table_form_field_text("{certificate}","$DohProxyCertificate",ico_certificate);
            }else{
                $tpl->table_form_field_bool("{useSSL}", 0, ico_disabled);
                $tpl->table_form_field_bool("{certificate}", 0, ico_disabled);
                $tpl->table_form_field_warn("{no_http_even_lb}");
            }
        }
    }



    $final=$tpl->table_form_compile();
    $Compile=$tpl->framework_buildjs("/rbldnsd/compile",
        "rbldnsd.compile.progress",
        "rbldnsd.compile.progress.log",
        "progress-rbldnsd-restart");

    $Sync=$tpl->framework_buildjs("/rbldnsd/syncdbs",
        "rbldnsd.sync.progress",
        "rbldnsd.compile.progress.log",
        "progress-rbldnsd-restart");

    $topbuttons[] = array($Sync, ico_refresh, "{synchronize_databases}");
    $topbuttons[] = array("Loadjs('$page?lookup-js=yes')", ico_loupe, "{lookup_address}");
    $topbuttons[] = array($Compile, ico_run, "{compile_rules}");

    $TINY_ARRAY["TITLE"]="{APP_RBLDNSD}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{APP_RBLDNSD_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="";
	$html[]="<table style='width:100%;margin-top:10px'>
	<tr>
		<td style='vertical-align: top' style='width:450px'><div id='rbldnsd-status'></div></td>
		<td style='vertical-align: top;width:100%'><div id='top-status'></div>
		<div style='margin-top: -23px'>$final</div></td>
	</tr>		
	</table>		
	";
    $jsrefresh=$tpl->RefreshInterval_js("rbldnsd-status",$page,"service-status",5);
	$html[]="<script>";
    $html[]=$jsrefresh;
    $html[]="LoadAjax('top-status','$page?top-status=yes');";
    $html[]="$jstiny;";
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}
function top_status():bool{
    $tpl=new template_admin();

    $RBLDNSD_BLCK_COUNT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_BLCK_COUNT");
    $RBLDNSD_WHITE_COUNT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_WHITE_COUNT");
    $RBLDNSD_COMPILE_TIME=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_COMPILE_TIME");

    $xtime=$tpl->time_to_date($RBLDNSD_COMPILE_TIME,true);
    $RBLDNSD_BLCK_COUNT=$tpl->widget_h("green","fas fa-database",$tpl->FormatNumber($RBLDNSD_BLCK_COUNT)."<div style='font-size:10px'>$xtime</div>","{blacklist} {items}");
    $RBLDNSD_WHITE_COUNT=$tpl->widget_h("green","fas fa-database",$tpl->FormatNumber($RBLDNSD_WHITE_COUNT)."<div style='font-size:10px'>$xtime</div>","{whitelist} {items}");


    $html[]="<table style='width:100%;margin-top:-13px'>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;padding:5px'>$RBLDNSD_BLCK_COUNT</td>";
    $html[]="<td style='width:50%;padding:5px'>$RBLDNSD_WHITE_COUNT</td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function dohproxy_uninstall_ask():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UninstallDoh=$tpl->framework_buildjs("/dohproxy/uninstall","dohproxy.progress","dohproxy.log","progress-rbldnsd-restart","LoadAjax('status-build','$page?status-build=yes');");
    return $tpl->js_confirm_delete("{APP_DOH_PROXY}","dohproxy-disable","yes",$UninstallDoh);
}
function dohproxy_uninstall_confirm():bool{
    return admin_tracks("Uninstall DoH DNSBL service");
}

function InjectorStatus():string{
    $tpl=new template_admin();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/rbldnsd/reputation-injector/status");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    if(!property_exists($json,"Info")){
        echo $tpl->widget_rouge("{protocol_error}","{APP_RBLDNSD_INJECTER}");
        return true;
    }

    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $jsRestart=$tpl->framework_buildjs("/rbldnsd/reputation-injector/restart","reputation-injecter.progress",
        "rbldnsd.progress.log","progress-rbldnsd-restart");
    return $tpl->SERVICE_STATUS($bsini, "APP_RBLDNSD_INJECTER",$jsRestart);
}
function service_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/rbldnsd/status");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    if(!property_exists($json,"Info")){
        echo $tpl->widget_rouge("{protocol_error}","{APP_RBLDNSD}");
        return true;
    }

    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $jsRestart=$tpl->framework_buildjs("/rbldnsd/restart","rbldnsd.progress",
        "rbldnsd.progress.log","progress-rbldnsd-restart","LoadAjax('rbldnsd-status','$page?service-status=yes')");

	

    $Status=$tpl->SERVICE_STATUS($bsini, "APP_RBLDNSD",$jsRestart);
    $EnableDohProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDohProxy"));

    if($EnableDohProxy==1){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dohproxy/status"));
        $bsini=new Bs_IniHandler();
        $bsini->loadString($json->Info);
        $jsRestartDoh=$tpl->framework_buildjs("/dohproxy/restart","dohproxy.progress","dohproxy.log","progress-rbldnsd-restart");
        $DohProxy=$tpl->SERVICE_STATUS($bsini, "APP_DOH_PROXY",$jsRestartDoh);
    }else {
        $InstallDoh=$tpl->framework_buildjs("/dohproxy/install","dohproxy.progress","dohproxy.log","progress-rbldnsd-restart","LoadAjax('status-build','$page?status-build=yes');");
        $btn[0]["js"] =$InstallDoh;
        $btn[0]["name"] = "{install}";
        $btn[0]["icon"] = ico_cd;
        $DohProxy=$tpl->widget_grey("{not_installed}","{APP_DOH_PROXY}",$btn);
    }

	echo $Status.InjectorStatus().$DohProxy;
    return true;
}
function dohproxy_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableDohProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDohProxy"));
    $DohProxyCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyCertificate");
    $DohProxyInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyInterface");
    $DohProxyProtocol=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyProtocol");
    $DohProxyHaProxy=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyHaProxy");
    $DohProxySSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxySSL"));
    $DohProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DohProxyPort"));
    if ($DohProxyPort==0) {
        $DohProxyPort=443;
        if($DohProxySSL==0){
            $DohProxyPort=80;
        }
    }

    if($EnableDohProxy==1) {
        $sock = new sockets();
        $json = json_decode($sock->REST_API("/dohproxy/link"));
        $Link = $json->Info;
        $form[] = "<textarea style='width: 770px;
            height: 63px;
            margin-bottom: 20px; 
            margin-top: 10px;border: 1px solid transparent'
            >$Link</textarea>";
    }


    $form[]=$tpl->field_interfaces("DohProxyInterface", "{listen_interface}", $DohProxyInterface);
    $form[]=$tpl->field_checkbox("DohProxyProtocol", "{proxy_protocol}", $DohProxyProtocol);
    $form[]=$tpl->field_checkbox("DohProxyHaProxy", "{haproxypp_support}", $DohProxyHaProxy);

    $form[]=$tpl->field_numeric("DohProxyPort", "{listen_port}", $DohProxyPort);
    $form[]=$tpl->field_checkbox("DohProxySSL", "{RsyncClientSSL}", $DohProxySSL);
    $form[]=$tpl->field_certificate("DohProxyCertificate","{certificate}",$DohProxyCertificate);

    $jsRestart=$tpl->framework_buildjs("/dohproxy/restart","dohproxy.progress","dohproxy.log","progress-rbldnsd-restart","LoadAjax('rbldnsd-status','$page?service-status=yes');");
    echo $tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjax('status-build','$page?status-build=yes');$jsRestart");
    return true;

}
function Save():bool{
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
    return admin_tracks_post("Saving DNS RBL reputation service parameters");
}
