<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["limit-countries-js"])){limit_countries_js();exit;}
if(isset($_GET["limit-countries-popup"])){limit_countries_popup();exit;}
if(isset($_GET["limit-countries-table"])){limit_countries_table();exit;}
if(isset($_GET["limit-countries-deny-all"])){limit_countries_deny_all();exit;}
if(isset($_GET["limit-countries-allow-all"])){limit_countries_allow_all();exit;}
if(isset($_GET["limit-access-country"])){limit_countries_single();exit;}

limit_countries_js();
function limit_countries_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $PHP_GEOIP_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));

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
    $ArticaWebDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries")));

    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){

        $ArticaWebDenyCountries[$CountryCode]=true;
    }
    $scount=count($ArticaWebDenyCountries);
    $ArticaWebDenyCountries_enc=base64_encode(serialize($ArticaWebDenyCountries));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebDenyCountries",$ArticaWebDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo js_after_forms();

}

function js_after_forms():string{
    $page=CurrentPageName();
    $jsafter[]="dialogInstance1.close();";
    $jsafter[]="LoadAjaxSilent('artica-web','fw.articaweb.php?table-static=yes')";
    return @implode(";",$jsafter);
}

function limit_countries_single():bool{

    $CU=$_GET["limit-access-country"];
    $ArticaWebDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries")));

    if(isset($ArticaWebDenyCountries[$CU])){unset($ArticaWebDenyCountries[$CU]);}else{
        $ArticaWebDenyCountries[$CU]=true;
    }


    $ArticaWebDenyCountries_enc=base64_encode(serialize($ArticaWebDenyCountries));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebDenyCountries",$ArticaWebDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('artica-web','fw.articaweb.php?table-static=yes');";
    return admin_tracks("Addin Web console limitation by country:$CU");
}
function limit_countries_allow_all(){
    $page=CurrentPageName();
    $ArticaWebDenyCountries_enc=base64_encode(serialize(array()));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebDenyCountries",$ArticaWebDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo js_after_forms();
}
function limit_countries_table(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $page               =   CurrentPageName();
    $GEOIPCOUNTRIES     = GEO_IP_COUNTRIES_LIST();
    $deny_all           = "Loadjs('$page?limit-countries-deny-all=yes');";
    $allow_all          = "Loadjs('$page?limit-countries-allow-all=yes');";
    $t                  = time();
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ArticaWebDenyCountries  = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries")));

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
    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){
        $class=null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $aclid=md5($CountryCode);
        $Enabled=0;
        if(isset($ArticaWebDenyCountries[$CountryCode])){
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