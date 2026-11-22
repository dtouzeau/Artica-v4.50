<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_POST["EnableProxyGeoIP"])){Save();exit;}
if(isset($_GET["limit-countries-page-js"])){limit_countries_page_js();exit;}
if(isset($_GET["limit-countries-page-popup"])){limit_countries_page_popup();exit;}
if(isset($_GET["limit-countries-js"])){limit_countries_js();exit;}
if(isset($_GET["limit-countries-popup"])){limit_countries_popup();exit;}
if(isset($_GET["limit-countries-table"])){limit_countries_table();exit;}
if(isset($_GET["limit-countries-deny-all"])){limit_countries_deny_all();exit;}
if(isset($_GET["limit-countries-allow-all"])){limit_countries_allow_all();exit;}
if(isset($_GET["limit-access-country"])){limit_countries_single();exit;}
if(isset($_GET["flat"])){flat();exit;}
start();

function Save(){
    $tpl                    = new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
}

function limit_countries_js(){
    $page                   = CurrentPageName();
    $tpl                    = new template_admin();
    $PHP_GEOIP_INSTALLED    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    $EnableGeoipUpdate      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));

    if($PHP_GEOIP_INSTALLED==0) {
        if (!extension_loaded("maxminddb")) {
            $tpl->js_error("{APP_PHP_GEOIP2_MISSING}");
            return;
        }
    }
    if($EnableGeoipUpdate==0){$tpl->js_error("{GeoIPUpdate_not_installed}");return;}

    $tpl->js_dialog5("{limit_countries}", "$page?limit-countries-popup=yes");

}
function limit_countries_popup(){
    $page=CurrentPageName();
    echo "<div id='limit-countries-table'></div><script>LoadAjax('limit-countries-table','$page?limit-countries-table=yes');</script>";

}
function limit_countries_deny_all(){
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $GEOIPCOUNTRIES=GEO_IP_COUNTRIES_LIST();
    $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));;

    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){

        $ProxyDenyCountries[$CountryCode]=true;
    }
    $scount=count($ProxyDenyCountries);
    $ProxyDenyCountries_enc=base64_encode(serialize($ProxyDenyCountries));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ProxyDenyCountries",$ProxyDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo "document.getElementById('dashboard-ProxyDeny-countries').innerHTML='$scount';";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
}
function limit_countries_single(){

    $CU=$_GET["limit-access-country"];
    $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));;

    if(isset($ProxyDenyCountries[$CU])){unset($ProxyDenyCountries[$CU]);}else{
        $ProxyDenyCountries[$CU]=true;
    }

    $scount=count($ProxyDenyCountries);
    $ProxyDenyCountries_enc=base64_encode(serialize($ProxyDenyCountries));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ProxyDenyCountries",$ProxyDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "document.getElementById('dashboard-ProxyDeny-countries').innerHTML='$scount';";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
}
function limit_countries_allow_all(){
    $page=CurrentPageName();
    $ProxyDenyCountries_enc=base64_encode(serialize(array()));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ProxyDenyCountries",$ProxyDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo "document.getElementById('dashboard-ProxyDeny-countries').innerHTML='0';";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
    
    
}
function limit_countries_table(){
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $GEOIPCOUNTRIES     = GEO_IP_COUNTRIES_LIST();
    $deny_all           = "Loadjs('$page?limit-countries-deny-all=yes');";
    $allow_all          = "Loadjs('$page?limit-countries-allow-all=yes');";
    $t                  = time();
    $tpl                = new template_admin();
    $page               = CurrentPageName();

    $html[]="<div class='ibox-content'>
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-danger\" OnClick=\"$deny_all\"><i class='far fa-check-double'></i> {deny_all} </label>
        <label class=\"btn btn btn-primary\" OnClick=\"$allow_all\"><i class='far fa-check-double'></i> {allow_all} </label>
     </div>";

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th></th>";
    $html[]="<th>{countries}</th>";
    $html[]="<th>{deny}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='generalcloud-rows'>";


    $TRCLASS=null;

    $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));;


    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){
        $class=null;
        $TRCLASS=null;
        $aclid=md5($CountryCode);
        $Enabled=0;
        if(isset($ProxyDenyCountries[$CountryCode])){
            $Enabled=1;
        }
        $html[]="<tr class='$TRCLASS' id='$aclid'>";
        $html[]="<td class=\"center\"><i class=\"far fa-globe-europe\"></i></td>";
        $html[]="<td><strong>{$Country}</strong></td>";
        $html[]="<td>". $tpl->icon_check($Enabled,"Loadjs('$page?limit-access-country=$CountryCode&md=$aclid')")."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='generalcloud-pager'></span></div>";
	$html[]="</div></div>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"generalcloud-rows","generalcloud-pager");
}

function limit_countries_page_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{deny_countries}","$page?limit-countries-page-popup=yes",650);
}
function limit_countries_page_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;

    $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));
    if(!is_array($ProxyDenyCountries)){$ProxyDenyCountries=array();}
    $labelcountries="<span id='dashboard-ProxyDeny-countries'>".count($ProxyDenyCountries)."</span> {items}";
    $EnableProxyGeoIP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyGeoIP"));
    $form[]=$tpl->field_checkbox("EnableProxyGeoIP","{enable_geoip_localisation}",$EnableProxyGeoIP);
    $form[]=$tpl->td_button("{deny_countries}", "{manage}", "Loadjs('$page?limit-countries-js=yes');",$labelcountries);
    $html=$tpl->form_outside("",$form,"","{apply}","LoadAjaxSilent('proxy-cloud-start','$page?flat=yes');","AsSquidAdministrator",true);
    echo $html;
    return true;
}
function flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableProxyGeoIP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyGeoIP"));

    $tpl->table_form_section("{cloud_mode}","{squid_cloud_mode_explain}");
    $tpl->table_form_field_js("Loadjs('$page?limit-countries-page-js=yes')");
    if($EnableProxyGeoIP==0){
        $tpl->table_form_field_bool("{enable_geoip_localisation}",0,ico_earth);
    }else{
        $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));
        if(!is_array($ProxyDenyCountries)){$ProxyDenyCountries=array();}
        $ct=count($ProxyDenyCountries);
        $tpl->table_form_field_text("{enable_geoip_localisation}","{deny_countries}:$ct",ico_earth);
    }
    $EnableSquidFireWallScheduler=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidFireWallScheduler"));
    $tpl->table_form_field_js("Loadjs('fw.proxy.firewall.schedule.php')");
    if($EnableSquidFireWallScheduler==0){
        $tpl->table_form_field_bool("{proxy_firewall_schedule}",0,ico_clock_desk);
    }else{
        $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/proxy/schedule/status"));
        $cfg=(is_object($status) && isset($status->config) && is_object($status->config)) ? $status->config : null;
        $daysArr  =($cfg && isset($cfg->days) && is_array($cfg->days)) ? array_map("intval",$cfg->days) : array();
        $from     =($cfg && isset($cfg->from)) ? $cfg->from : "01:00";
        $to       =($cfg && isset($cfg->to))   ? $cfg->to   : "06:00";
        $excList  =($cfg && isset($cfg->exceptions) && is_array($cfg->exceptions)) ? $cfg->exceptions : array();
        $tpl->table_form_field_text("{proxy_firewall_schedule}","$cfg->from {to} $cfg->to",ico_clock_desk);
    }

    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;

}

function start(){
    $page=CurrentPageName();
    echo "<div id='proxy-cloud-start'></div>";
    echo "<script>LoadAjaxSilent('proxy-cloud-start','$page?flat=yes');</script>";
}
