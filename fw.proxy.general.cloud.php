<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_POST["EnableProxyGeoIP"])){Save();exit;}
if(isset($_GET["limit-countries-js"])){limit_countries_js();exit;}
if(isset($_GET["limit-countries-popup"])){limit_countries_popup();exit;}
if(isset($_GET["limit-countries-table"])){limit_countries_table();exit;}
if(isset($_GET["limit-countries-deny-all"])){limit_countries_deny_all();exit;}
if(isset($_GET["limit-countries-allow-all"])){limit_countries_allow_all();exit;}
if(isset($_GET["limit-access-country"])){limit_countries_single();exit;}

page();

function Save(){
    $tpl                    = new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
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

    $tpl->js_dialog1("{limit_countries}", "$page?limit-countries-popup=yes");

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
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
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
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
}
function limit_countries_allow_all(){
    $page=CurrentPageName();
    $ProxyDenyCountries_enc=base64_encode(serialize(array()));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ProxyDenyCountries",$ProxyDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo "document.getElementById('dashboard-ProxyDeny-countries').innerHTML='0';";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
    
    
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

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{countries}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{deny}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $TRCLASS=null;

    $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));;


    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){
        $class=null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
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

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;

    $ProxyDenyCountries=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));
    if(!is_array($ProxyDenyCountries)){$ProxyDenyCountries=array();}
    $labelcountries="<span id='dashboard-ProxyDeny-countries'>".count($ProxyDenyCountries)."</span> {items}";


    $EnableProxyGeoIP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyGeoIP"));

    $form[]=$tpl->field_checkbox("EnableProxyGeoIP","{enable_geoip_localisation}",$EnableProxyGeoIP);
    $form[]=$tpl->td_button("{deny_countries}", "{manage}", "Loadjs('$page?limit-countries-js=yes');",$labelcountries);
    $html=$tpl->form_outside("{cloud_mode}",$form,"{squid_cloud_mode_explain}","{apply}",null,"AsSquidAdministrator",true);
    echo $html;

}
