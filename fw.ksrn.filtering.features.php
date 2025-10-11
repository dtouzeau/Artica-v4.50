<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["MacToUidPHP"])){main_install_kcloud_confirmed();exit;}
if(isset($_GET["global-status-ufdbguard"])){global_echo_ufdbguard();exit;}
if(isset($_GET["table"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["install-kcloud-confirm"])){main_install_kcloud_confirm();exit;}
if(isset($_GET["global-status"])){global_status();exit;}
if(isset($_GET["jstiny"])){jstiny();exit;}
page();



function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $libmem=new lib_memcached();
    $KRSN_VERSION=trim($libmem->getKey("KSRN_VERSION"));
    VERBOSE("KSRN_VERSION = [$KRSN_VERSION]",__LINE__);
    if($KRSN_VERSION<>null){$KRSN_VERSION=" v$KRSN_VERSION";}

    $html=$tpl->page_header("{features}",
        ico_cd,
        "{filtering_service_explain}","$page?table=yes","filtering-features",
        "progress-ksrnfeatures-restart",false,"table-loader-ksrn-features-pages"
    );


    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{features}"]="$page?status=yes";
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    if($Go_Shield_Server_Enable==1) {
        $array["{KSRN_SERVER2}"] = "fw.go.shield.server.php?tiny-page=yes";
    }
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    if($EnableUfdbGuard==1){
        $array["{APP_UFDBGUARD}"]="fw.ufdb.status.php?tiny-page=yes";

    }
    echo $tpl->tabs_default($array);
}

function main_install_ksrn():string{
    $page=CurrentPageName();
    $tpl=new template_admin();


    return $tpl->framework_buildjs("/theshield/install","ksrn.progress",
        "ksrn.log",
        "progress-ksrnfeatures-restart","LoadAjax('table-loader-ksrn-features-pages','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");
}
function main_install_kcloud_confirm():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js=$tpl->framework_buildjs("ksrn.php?install-kcloud=yes","ksrn.progress",
        "ksrn.log",
        "progress-ksrnfeatures-restart","LoadAjax('table-loader-ksrn-features-pages','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

    $tpl->js_confirm_execute("{specific_plugins_ask}","MacToUidPHP","yes",$js);
    return true;

}
function main_install_kcloud_confirmed():bool{
    admin_tracks("Artica Categories specific plugin installation confirmed");
    return true;
}
function  main_install_kcloud():string{
    $page=CurrentPageName();
    return "Loadjs('$page?install-kcloud-confirm=yes')";


}
function  main_uninstall_kcloud():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    return $tpl->framework_buildjs("ksrn.php?uninstall-kcloud=yes","ksrn.progress",
        "ksrn.log",
        "progress-ksrnfeatures-restart","LoadAjax('table-loader-ksrn-features-pages','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

}
function main_uninstall_ksrn():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    return $tpl->framework_buildjs("/theshield/uninstall","ksrn.progress",
        "ksrn.log",
        "progress-ksrnfeatures-restart","LoadAjax('table-loader-ksrn-features-pages','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");
}
function main_uninstall_itcharter():string{


    $tpl=new template_admin();
    return $tpl->framework_buildjs("/itcharter/uninstall",
        "ichart.progress",
        "ichart.install.log",
        "progress-ksrnfeatures-restart",restart_after());

}


function main_install_hotspot():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-ksrnfeatures-restart",restart_after());

}
function main_uninstall_hotspot():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/proxy/hotspot/uninstall",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-ksrnfeatures-restart",restart_after());

}


function restart_after():string{
    $page=CurrentPageName();
    $f[]="LoadAjax('table-loader-ksrn-features-pages','$page?table=yes');";
    $f[]="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";
    return @implode(";",$f);
}
function main_install_itcharter():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/itcharter/install",
            "ichart.progress",
            "ichart.install.log",
           "progress-ksrnfeatures-restart",
            restart_after()
    );

}
function main_install_categories_cache():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("ksrn.php?install-categories-cache=yes",
        "categories-cache.progress",
        "/categories-cache.log",
        "progress-ksrnfeatures-restart",
        restart_after()
    );

}
function main_uninstall_categories_cache():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("ksrn.php?uninstall-categories-cache=yes",
        "categories-cache.progress",
        "categories-cache.log",
        "progress-ksrnfeatures-restart",
        restart_after()
    );

}
function main_install_ufdbcat():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/dnscatz/install",
        "dnscatz.install.progress",
        "dnscatz.install.progress.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function main_uninstall_ufdbcat():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/dnscatz/uninstall",
        "dnscatz.install.progress",
        "dnscatz.install.progress.log",
        "progress-ksrnfeatures-restart",restart_after());
}

function main_install_ufdb():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/ufdb/install",
        "ufdb.enable.progress",
        "ufdb.enable.progress.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function main_uninstall_ufdb():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/ufdb/uninstall",
        "ufdb.enable.progress",
        "ufdb.enable.progress.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function main_install_go_shield_server():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/goshield/install",
        "go.shield.server.progress",
        "go.shield.server.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function main_uninstall_go_shield_server():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("ksrn.php?disable-go-shield-server=yes",
        "go.shield.server.progress",
        "go.shield.server.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function main_install_go_shield_connector():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/goshield/connector/enable",
        "go.shield.connector.progress",
        "go.shield.connector.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function main_uninstall_go_shield_connector():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/goshield/connector/disable",
        "go.shield.connector.progress",
        "go.shield.connector.log",
        "progress-ksrnfeatures-restart",restart_after());
}
function row_status($array):string{

    $title=$array["TITLE"];
    $TRCLASS=$array["TRCLASS"];
    $explain=$array["EXPLAIN"];
    $STATUS_R=$array["STATUS"];

    $status[0]="<span class='label'>{disabled}</span>";
    $status[1]="<span class='label label-primary'>{active2}</span>";
    $status[2]="<span class='label'>{not_available}</span>";
    $status[3]="<span class='label'>{license_error}</span>";
    $status[5]="<span class='label label-primary'>{active2}</span>";

    $text_class[0]="text-muted";
    $text_class[1]="font-bold";
    $text_class[2]="text-muted";
    $text_class[3]="text-muted";
    $text_class[5]="font-bold";

    $icon[0]="<i class='fas fa-thumbs-down'>";
    $icon[1]="<i class='text-navy fas fa-thumbs-up'>";
    $icon[2]="<i class='far fa-times-circle'>";
    $icon[3]="<i class='far fa-times-circle'>";
    $icon[5]="<i class='text-navy fas fa-thumbs-up'>";

    if($array["CMD_ON"]<>null) {
        $install[0] = "<a href=\"javascript:blur()\" OnClick=\"{$array["CMD_ON"]}\" class='btn btn-primary btn-sm'>
			<i class='fas fa-check'></i> {install_the_feature} </a>";
    }

    if($array["CMD_OFF"]<>null) {
        $install[1] = "<a href=\"javascript:blur()\" OnClick=\"{$array["CMD_OFF"]}\" class='btn btn-warning btn-sm'>
			<i class='fas fa-check'></i> {uninstall} </a>";
    }


    $install[2]= "<a href=\"javascript:blur()\" OnClick=\"blur()\" class='btn btn-default btn-sm'>
			<i class='fas fa-check'></i> {not_available} </a>";

    $install[3]= "<a href=\"javascript:blur()\" OnClick=\"blur()\" class='btn btn-default btn-sm'>
			<i class='fas fa-check'></i> {license_error} </a>";

    $install[5]= "<a href=\"javascript:blur()\" OnClick=\"blur()\" class='btn btn-default btn-sm'>
			<i class='fas fa-check'></i> {locked} </a>";


    $cxlass=$text_class[$STATUS_R];
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td nowrap style='width:1%'>$icon[$STATUS_R]</td>";
    $html[]="<td nowrap style='width:20%'><strong class='$cxlass'>$title</strong></td>";
    $html[]="<td style='width:80%'><span class='$cxlass'>$explain</span></td>";
    $html[]="<td style='vertical-align:middle;width=1%' nowrap><span class='$cxlass'>$status[$STATUS_R]</span></td>";
    $html[]="<td style='vertical-align:middle;width=1%' nowrap>$install[$STATUS_R]</span></td>";
    $html[]="</tr>";
    return @implode("\n",$html);
}

function StatusGoShield(){
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $EnableGoShieldServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $CORP_LICENSE=$sock->CORP_LICENSE();

    VERBOSE("CORP_LICENSE Returns [$CORP_LICENSE]",__LINE__);
    if($CORP_LICENSE){
        VERBOSE("CORP_LICENSE = TRUE",__LINE__);
    }else{
        VERBOSE("CORP_LICENSE = FALSE",__LINE__);
    }
    $TRCLASS=null;
    $t=time();
    $html[]="<div id='global-status'></div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" style='margin-top:20px' data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{features}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    $EnableCategoriesCache = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesCache"));
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $MacToUidPHP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidPHP"));
    $EnableITChart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableITChart"));

    $EnableGoShieldConnector=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Enable"));





    if(!$CORP_LICENSE){$MacToUidPHP=3;}
    if($MacToUidPHP==1){
        $EnableSquidMicroHotSpot=2;
        $KSRNEnable=2;
        $EnableUfdbGuard=2;
        $EnableITChart=2;
        $EnableGoShieldServer=2;
        $EnableGoShieldConnector=2;
    }
    if($EnableGoShieldConnector==1){
        //$EnableSquidMicroHotSpot=2;
        //$KSRNEnable=2;
        //$EnableUfdbGuard=2;
        //$EnableITChart=2;
        $MacToUidPHP=2;
    }
    $array=array();

    $CloudCat=5;
    if(!$CORP_LICENSE){
        $CloudCat=3;
    }
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $array["TRCLASS"]=$TRCLASS;
    $array["TITLE"]="{query_artica_cloud_database}";
    $array["EXPLAIN"]="{query_artica_cloud_database_explain}";
    $array["CMD_ON"] = null;
    $array["STATUS"]=$CloudCat;
    $array["CMD_OFF"]= null;
    $html[]=row_status($array);

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $array["TRCLASS"]=$TRCLASS;
    $array["TITLE"]="{blackandwhite_list}";
    $array["STATUS"]=5;
    $array["EXPLAIN"]="{blackandwhite_list_members}";
    $html[]=row_status($array);



    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $array["TRCLASS"]=$TRCLASS;
    $array["TITLE"]="{reputation_services}";
    $array["EXPLAIN"]="{KSRN_EXPLAIN}";
    $array["CMD_ON"] = main_install_ksrn();
    $array["STATUS"]=$KSRNEnable;
    $array["CMD_OFF"]= main_uninstall_ksrn();
    $html[]=row_status($array);


    $currentMem = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TOTAL_MEMORY_MB"));


        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $array["TRCLASS"]=$TRCLASS;
        $array["TITLE"]="{APP_UFDBGUARDD}";
        $array["EXPLAIN"]="{ufdbgdb_explain}";
        $array["CMD_ON"] = main_install_ufdbcat();
        $array["STATUS"]=$EnableUfdbGuard;
        $array["CMD_OFF"]= main_uninstall_ufdbcat();
        $html[]=row_status($array);

    if($currentMem<2500){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $array["TRCLASS"]=$TRCLASS;
        $array["TITLE"]="{APP_UFDBGUARDD}";
        $array["EXPLAIN"]="{ufdbgdb_explain}<br><strong class='text-danger'>{NO_ENOUGH_MEMORY_FOR_THIS_SECTION}</strong>";
        $array["CMD_ON"] = "";
        $array["STATUS"]=$EnableUfdbGuard;
        $array["CMD_OFF"]= "";
        $html[]=row_status($array);
    }



    $SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));



    if($SQUIDEnable==1) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $array["TRCLASS"] = $TRCLASS;
        $array["TITLE"] = "{APP_CATEGORIES_CACHE}";
        $array["EXPLAIN"] = "{APP_CATEGORIES_CACHE_TEXT}";
        $array["CMD_ON"] = main_install_categories_cache();
        $array["STATUS"] = $EnableCategoriesCache;
        $array["CMD_OFF"] = main_uninstall_categories_cache();
        $html[] = row_status($array);


        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }

        $array["TRCLASS"] = $TRCLASS;
        $array["TITLE"] = "{web_portal_authentication}";
        $array["STATUS"] = $EnableSquidMicroHotSpot;
        $array["EXPLAIN"] = "{HotSpot_text}";
        $array["CMD_ON"] = main_install_hotspot();
        $array["CMD_OFF"] = main_uninstall_hotspot();
        $html[] = row_status($array);

        if (!$CORP_LICENSE) {
            $EnableITChart = 3;
        }

        $array = array();
        $array["TRCLASS"] = $TRCLASS;
        $array["TITLE"] = "{APP_ITCHARTER}";
        $array["STATUS"] = $EnableITChart;
        $array["EXPLAIN"] = "{IT_charter_explain}";
        $array["CMD_ON"] = main_install_itcharter();
        $array["CMD_OFF"] = main_uninstall_itcharter();
        $html[] = row_status($array);

        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $array["TRCLASS"] = $TRCLASS;
        $array["TITLE"] = "{UseCloudArticaCategories}";
        $array["EXPLAIN"] = "{UseCloudArticaCategories_text}";
        $array["CMD_ON"] = main_install_kcloud();
        $array["STATUS"] = $MacToUidPHP;
        $array["CMD_OFF"] = main_uninstall_kcloud();
        $html[] = row_status($array);
    }

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="</tbody>";
    $html[]="</table>";

    $refersh=$tpl->RefreshInterval_js("global-status",$page,"global-status=yes");

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$refersh;
    Loadjs('fw.ksrn.filtering.features.php?jstiny=yes');
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function jstiny():bool{
    $tpl=new template_admin();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    $remove_feature=$tpl->framework_buildjs("ksrn.php?uninstall-feature=yes",
        "go.shield.server.progress",
        "go.shield.server.log",
        "progress-ksrnfeatures-restart","document.location.href='/index';");


    $dblen=$GLOBALS["CLASS_SOCKETS"]->GET_GOSHIELD_CACHES_ENTRIES();
    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-danger\" OnClick=\"$remove_feature\"><i class='fa fa-trash'></i> {remove_this_section} </label>";
    if($dblen>0) {
        $after=base64_encode("Loadjs('fw.ksrn.filtering.features.php?jstiny=yes');");
        $jscache = "Loadjs('fw.go.shield.server.php?clean-cache=yes&after=$after')";
        $dblen = $tpl->FormatNumber($dblen);
        $btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"$jscache;\">
	    <i class='fa fa-trash'></i> {empty_cache} - $dblen {records}</label>";

    }

    if($Go_Shield_Server_Enable==1){
        $HotSpotWizard="Loadjs('fw.proxy.hotspot.status.php?wizard-js=yes');";
        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        if($SQUIDEnable==1) {
            if ($EnableSquidMicroHotSpot == 0) {
                $btns[] = "<label class=\"btn btn btn-primary\" OnClick=\"$HotSpotWizard;\">
	    <i class='" . ico_wizard . "'></i> {hotspotwizard}</label>";

            }
        }
    }
    $btns[] = "</div>";
    $TINY_ARRAY["TITLE"]="{filtering_service}:{features}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{filtering_service_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
    return true;
}
function GoShield_Widget():string{
    $tpl=new template_admin();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $EnableRemoteCategoriesServices = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
    $GoShieldConnectorDisableACL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorDisableACL"));

    $btn["name"]="{install}";
    $btn["icon"]=ico_cd;
    $btn["js"] = main_install_go_shield_server();

    if($Go_Shield_Server_Enable==0){
        return $tpl->widget_h("gray", ico_disabled, "{disabled}", "{KSRN_SERVER2}",$btn);
    }



    $GET_GOSHIELD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_GOSHIELD_VERSION();
    $btn=array();
    $btn["name"]="{uninstall}";
    $btn["icon"]="fas fa-trash-alt";
    $btn["js"] = main_uninstall_go_shield_server();
    $SquidEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("SQUIDEnable"));
    if($SquidEnable==1) {
        $MacToUidUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidUrgency"));
        $Go_Shield_Connector_Enable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Enable"));
        if($MacToUidUrgency==1){
            if($Go_Shield_Connector_Enable==1){
                return $tpl->widget_h("red", ico_engine_warning, "{connector} {emergency}", "{KSRN_SERVER2}", $btn);
            }
        }
        $SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
        if ($SQUIDACLsEnabled==0) {
            $GoShieldConnectorDisableACL=1;
        }
        if ($GoShieldConnectorDisableACL == 0) {
            if ($Go_Shield_Connector_Enable == 0) {
                $btn2["name"]="{connector}";
                $btn2["icon"]=ico_plug;
                $btn2["js"] = "Loadjs('fw.go.shield.connector.php?link-connector=yes');";
                return $tpl->widget_h("yellow", ico_engine_warning, "{connector} {disabled}", "{KSRN_SERVER2}", $btn,$btn2);
            }
        }
        $Go_Shield_Connector_Addr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
        $Go_Shield_Connector_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));
        if ($Go_Shield_Connector_Addr == null) {
            $Go_Shield_Connector_Addr = "127.0.0.1";
        }
        if ($Go_Shield_Connector_Port == 0) {
            $Go_Shield_Connector_Port = 3333;
        }
        $connection = fsockopen($Go_Shield_Connector_Addr, $Go_Shield_Connector_Port, $errno, $errstr, 1);
        if (!is_resource($connection)) {

            return $tpl->widget_h("red", "fad fa-compress-arrows-alt",
                "{failed_to_connect}",
                $errstr . "  ($Go_Shield_Connector_Addr:$Go_Shield_Connector_Port)",$btn);

        }
        fclose($connection);

        $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/proxy/connector/status"));
        $bsini = new Bs_IniHandler();
        $bsini->loadString($data->Info);

        if(isset($bsini->_params["GO_SHIELD_CONNECTOR_PROXY"]["running"])){
            $running=intval($bsini->_params["GO_SHIELD_CONNECTOR_PROXY"]["running"]);
            if($running==0){
                $btn=array();
                $btn["name"]="{restart}";
                $btn["icon"]="fas fa-trash-alt";
                $btn["js"] =  $tpl->framework_buildjs("/goshield/connector/restart","go.shield.connector.progress","go.shield.connector.log","progress-ksrnfeatures-restart",restart_after());
                return $tpl->widget_h("red", "fad fa-compress-arrows-alt",
                    "{stopped}", "{GO_SHIELD_CONNECTOR_PROXY}",$btn);

            }
        }
    }

    if($EnableRemoteCategoriesServices==1){
        $UFDBCAT_DNS_ERROR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_DNS_ERROR"));
        if(strlen($UFDBCAT_DNS_ERROR)>3){
            return  $tpl->widget_h("red", "fad fa-compress-arrows-alt",
                $UFDBCAT_DNS_ERROR,"{APP_UFDBCAT}",$btn);
        }
    }
   return  $tpl->widget_h("green",ico_goshield,
        "{enabled}",
        "{KSRN_SERVER2} v$GET_GOSHIELD_VERSION",$btn);
}
function global_echo_ufdbguard():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(global_status_ufdbguard());
    return true;
}
function global_status_ufdbguard():string{
    $tpl=new template_admin();
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    if($EnableUfdbGuard==0) {
        $currentMem = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TOTAL_MEMORY_MB"));
        if ($currentMem < 2500) {
            $btn = array();
            $btn["name"] = "{memory} < 2.5GB";
            $btn["icon"] = ico_cd;
            $btn["js"] = "blur();";
            return $tpl->widget_h("gray", "fab fa-soundcloud", "{memory_issue}", "{APP_UFDBGUARDD}", $btn);
        }
        $btn = array();
        $btn["name"] = "{install}";
        $btn["icon"] = ico_cd;
        $btn["js"] = main_install_ufdb();
        return $tpl->widget_h("gray", "fab fa-soundcloud", "{disabled}", "{APP_UFDBGUARDD}", $btn);

    }
    $btn=array();
    $btn["name"]="{uninstall}";
    $btn["icon"]="fas fa-trash-alt";
    $btn["js"] = main_uninstall_ufdb();

    $btn2=array();
    $btn2["name"]="{filtering_rules}";
    $btn2["icon"]="fa-align-justify";
    $btn2["js"] = "LoadAjaxSilent('MainContent','fw.ufdb.rules.php')";



    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/redirector/stats"));
    if(!$json->Status){
        return $tpl->widget_h("red","fab fa-soundcloud", "{error}", "{APP_UFDBGUARDD} $json->Error",$btn,$btn2);
    }
    if(!property_exists($json,"Stats")){
        return $tpl->widget_h("red","fab fa-soundcloud", "{error}", "{APP_UFDBGUARDD} Stats!",$btn,$btn2);
    }
    if(!property_exists($json->Stats,"UfdbguardRunning")){
        return $tpl->widget_h("red","fab fa-soundcloud", "{error}", "{APP_UFDBGUARDD} Running?",$btn,$btn2);
    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/used");
    $NumberActive=$json->Stats->NumberActive;
    $RequestsSent=$tpl->FormatNumber($json->Stats->RequestsSent);

    $UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)){$UfdbUsedDatabases=array();}

    if(!isset($UfdbUsedDatabases["INSTALLED"])){
        $UfdbUsedDatabases["INSTALLED"]=array();
    }
    $INSTALLEDDB=count($UfdbUsedDatabases["INSTALLED"]);


    if(!$json->Stats->UfdbguardRunning){
        return $tpl->widget_h("red","fab fa-soundcloud", "{stopped}", "{APP_UFDBGUARDD}",$btn,$btn2);
    }

    if($INSTALLEDDB==0){
        return $tpl->widget_h("yellow","fab fa-soundcloud", "0 {category}", "{APP_UFDBGUARDD}",$btn,$btn2);
    }

    return $tpl->widget_h("green","fab fa-soundcloud", "$RequestsSent/$NumberActive", "{APP_UFDBGUARDD} ({requests}/{processes})",$btn,$btn2);
}
function global_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    $GoShieldServ=GoShield_Widget();

    if($KSRNEnable==0) {
        $btn=array();
        $btn["name"]="{install}";
        $btn["icon"]=ico_cd;
        $btn["js"] = main_install_ksrn();
        $KSRN = $tpl->widget_h("gray", "fa fa-shield", "{disabled}", "{reputation_services}",$btn);
    }

    if($KSRNEnable==1){
        $btn=array();
        $btn["name"]="{uninstall}";
        $btn["icon"]="fas fa-trash-alt";
        $btn["js"] = main_uninstall_ksrn();

        $KSRN=$tpl->widget_h("green","fa fa-shield", "{enabled}", "{reputation_services}",$btn);
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td class='celltop' style='width:30%'>";
    $html[]=$GoShieldServ;
    $html[]="</td>";
    $html[]="<td class='celltop' style='width:36%'>";
    $html[]="<div id='global_echo_ufdbguard'>".global_status_ufdbguard()."</div>";
    $html[]="</td>";
    $html[]="<td class='celltop' style='width:33%'>";
    $html[]=$KSRN;
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $js=$tpl->RefreshInterval_js("global_echo_ufdbguard",$page,"global-status-ufdbguard=yes");
    $html[]="<script>$js</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
