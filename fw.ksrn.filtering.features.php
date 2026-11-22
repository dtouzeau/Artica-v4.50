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
    return $tpl->framework_buildjs("/goshield/uninstall",
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
/**
 * Feuille de style de la page (design "Enhanced Setup Center").
 * Concatenee autour du parsing, jamais dedans: _ENGINE_parse_body prendrait chaque
 * corps de regle CSS pour un {token}. Emise une fois par requete.
 */
function ksf_style():string{
    if(isset($GLOBALS["KSF_CSS_DONE"])){return "";}
    $GLOBALS["KSF_CSS_DONE"]=true;
    return "<style>
.ksf-wrap{background:#f7fafc;padding:16px;border-radius:8px;margin:10px 0}
.ksf-wrap *{-webkit-box-sizing:border-box;box-sizing:border-box}
.ksf-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
.ksf-scroll{overflow-x:auto}
.ksf-tbl{width:100%;border-collapse:collapse;text-align:left}
.ksf-tbl thead tr{background:#f1f4f6;border-bottom:1px solid #e2e8f0}
.ksf-tbl thead th{padding:16px 20px;font-size:12px;font-weight:600;line-height:16px;
letter-spacing:.05em;text-transform:uppercase;color:#3e4944;white-space:nowrap}
.ksf-tbl tbody tr{border-top:1px solid #e2e8f0}
.ksf-tbl tbody tr:first-child{border-top:0}
.ksf-tbl tbody td{padding:18px 20px;vertical-align:middle}
.ksf-feat{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;
-ms-flex-align:center;align-items:center}
.ksf-ico{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;width:44px;margin-right:16px;
font-size:30px;line-height:1;text-align:center}
.ksf-ico-on{color:#0f765e}
.ksf-ico-off{color:#8a959b}
.ksf-ico-err{color:#ba1a1a}
.ksf-name{font-size:15px;font-weight:700;line-height:20px;color:#181c1e}
.ksf-name-off{color:#6e7a74}
.ksf-desc{font-size:14px;line-height:20px;color:#3e4944;max-width:600px}
.ksf-desc-off{color:#6e7a74}
.ksf-badge{display:inline-block;font-size:11px;font-weight:700;line-height:12px;letter-spacing:.02em;
text-transform:uppercase;padding:5px 8px;border-radius:2px;color:#fff;white-space:nowrap}
.ksf-badge-on{background:#0f765e}
.ksf-badge-off{background:#545f72}
.ksf-badge-err{background:#ba1a1a}
.ksf-act{text-align:right;white-space:nowrap}
.ksf-btn{display:inline-block;border-radius:4px;padding:10px 18px;font-size:13px;font-weight:700;
line-height:16px;text-decoration:none !important;border:1px solid transparent}
.ksf-btn-primary{background:#0f765e;color:#fff !important}
.ksf-btn-primary:hover{background:#005b48;color:#fff !important}
.ksf-btn-ghost{background:#fff;border-color:#bec9c3;color:#3e4944 !important}
.ksf-btn-ghost:hover{background:#f1f4f6;color:#181c1e !important}
.ksf-btn-off{background:#fff;border-color:#e8edf0;color:#9aa5ab !important;cursor:default}
</style>";
}

/**
 * Etat numerique -> apparence.
 * 0 desactive, 1 actif, 2 indisponible, 3 licence, 5 actif verrouille.
 * Retourne array(icone, variante, libelle_du_badge, variante_du_badge).
 */
function ksf_state($status){
    $status=intval($status);
    if($status==1){return array("fas fa-thumbs-up","on","{active2}","on");}
    if($status==5){return array("fas fa-thumbs-up","on","{active2}","on");}
    if($status==2){return array("far fa-times-circle","off","{not_available}","off");}
    if($status==3){return array("far fa-times-circle","err","{license_error}","err");}
    return array("fas fa-thumbs-down","off","{disabled}","off");
}

/** Colonne "fonctionnalite": grande icone d'etat + intitule. */
function ksf_feature($status,$title):string{
    list($ico,$variant,$none1,$none2)=ksf_state($status);
    $nameoff=($variant=="on")?"":" ksf-name-off";
    return "<div class='ksf-feat'><span class='ksf-ico ksf-ico-$variant'><i class='$ico'></i></span>"
        ."<span class='ksf-name$nameoff'>$title</span></div>";
}

/** Pastille d'etat rectangulaire. */
function ksf_badge($status):string{
    list($none,$variant,$label,$badge)=ksf_state($status);
    return "<span class='ksf-badge ksf-badge-$badge'>$label</span>";
}

/**
 * Bouton d'action.
 * Un etat 0 sans CMD_ON (ou 1 sans CMD_OFF) n'a aucune commande a proposer: on rend
 * un bouton inerte plutot qu'une cellule vide. L'ancien code laissait dans ce cas
 * $install[$STATUS_R] indefini.
 */
function ksf_action($status,$cmd_on,$cmd_off):string{
    $status=intval($status);
    if($status==0){
        if(strlen(trim(strval($cmd_on)))>0){
            return "<a href=\"javascript:blur()\" OnClick=\"$cmd_on\" class='ksf-btn ksf-btn-primary'>"
                ."<i class='fas fa-circle-plus'></i>&nbsp;{install_the_feature}</a>";
        }
        return "<span class='ksf-btn ksf-btn-off'><i class='fas fa-ban'></i>&nbsp;{not_available}</span>";
    }
    if($status==1){
        if(strlen(trim(strval($cmd_off)))>0){
            return "<a href=\"javascript:blur()\" OnClick=\"$cmd_off\" class='ksf-btn ksf-btn-ghost'>"
                ."<i class='fas fa-gear'></i>&nbsp;{uninstall}</a>";
        }
        return "<span class='ksf-btn ksf-btn-off'><i class='fas fa-lock'></i>&nbsp;{locked}</span>";
    }
    if($status==2){return "<span class='ksf-btn ksf-btn-off'><i class='fas fa-ban'></i>&nbsp;{not_available}</span>";}
    if($status==3){return "<span class='ksf-btn ksf-btn-off'><i class='fas fa-triangle-exclamation'></i>&nbsp;{license_error}</span>";}
    return "<span class='ksf-btn ksf-btn-off'><i class='fas fa-lock'></i>&nbsp;{locked}</span>";
}

function row_status($array):string{

    $title=$array["TITLE"];
    $explain=$array["EXPLAIN"];
    $STATUS_R=intval($array["STATUS"]);
    $cmd_on=isset($array["CMD_ON"])?$array["CMD_ON"]:null;
    $cmd_off=isset($array["CMD_OFF"])?$array["CMD_OFF"]:null;

    list($none1,$variant,$none2,$none3)=ksf_state($STATUS_R);
    $descoff=($variant=="on")?"":" ksf-desc-off";

    $html=array();
    $html[]="<tr>";
    $html[]="<td style='width:1%' nowrap>".ksf_feature($STATUS_R,$title)."</td>";
    $html[]="<td><div class='ksf-desc$descoff'>$explain</div></td>";
    $html[]="<td style='width:1%' nowrap>".ksf_badge($STATUS_R)."</td>";
    $html[]="<td class='ksf-act' style='width:1%' nowrap>".ksf_action($STATUS_R,$cmd_on,$cmd_off)."</td>";
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
    $html[]="<div id='global-status'></div>";
    $html[]="<div class='ksf-wrap'><div class='ksf-card'><div class='ksf-scroll'>";
    $html[]="<table class='ksf-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{features}</th>";
    $html[]="<th>{description}</th>";
    $html[]="<th>{status}</th>";
    $html[]="<th style='text-align:right'>{action}</th>";
    $html[]="</tr></thead>";
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
    $array["TITLE"]="{query_artica_cloud_database}";
    $array["EXPLAIN"]="{query_artica_cloud_database_explain}";
    $array["CMD_ON"] = null;
    $array["STATUS"]=$CloudCat;
    $array["CMD_OFF"]= null;
    $html[]=row_status($array);

    $array["TITLE"]="{blackandwhite_list}";
    $array["STATUS"]=5;
    $array["EXPLAIN"]="{blackandwhite_list_members}";
    $html[]=row_status($array);



    $array["TITLE"]="{reputation_services}";
    $array["EXPLAIN"]="{KSRN_EXPLAIN}";
    $array["CMD_ON"] = main_install_ksrn();
    $array["STATUS"]=$KSRNEnable;
    $array["CMD_OFF"]= main_uninstall_ksrn();
    $html[]=row_status($array);


    $currentMem = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TOTAL_MEMORY_MB"));


    // Web filtering (ufdbGuard): its own routes (/ufdb/install, /ufdb/uninstall), the ones of the
    // status widget below. This row used the categories service commands (/dnscatz/*) and, under
    // 2.5 GB of memory, was rendered twice. Not enough memory only refuses the installation: an
    // installed service can still be removed.
    $array["TITLE"]="{APP_UFDBGUARDD}";
    $array["EXPLAIN"]="{ufdbgdb_explain}";
    $array["STATUS"]=$EnableUfdbGuard;
    $array["CMD_ON"] = main_install_ufdb();
    $array["CMD_OFF"]= main_uninstall_ufdb();
    if($currentMem<2500){
        $array["EXPLAIN"]="{ufdbgdb_explain}<br><strong class='text-danger'>{NO_ENOUGH_MEMORY_FOR_THIS_SECTION}</strong>";
        $array["CMD_ON"] = "";
    }
    $html[]=row_status($array);



    $SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));



    if($SQUIDEnable==1) {
        $array["TITLE"] = "{APP_CATEGORIES_CACHE}";
        $array["EXPLAIN"] = "{APP_CATEGORIES_CACHE_TEXT}";
        $array["CMD_ON"] = main_install_categories_cache();
        $array["STATUS"] = $EnableCategoriesCache;
        $array["CMD_OFF"] = main_uninstall_categories_cache();
        $html[] = row_status($array);



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
        $array["TITLE"] = "{APP_ITCHARTER}";
        $array["STATUS"] = $EnableITChart;
        $array["EXPLAIN"] = "{IT_charter_explain}";
        $array["CMD_ON"] = main_install_itcharter();
        $array["CMD_OFF"] = main_uninstall_itcharter();
        $html[] = row_status($array);

        $array["TITLE"] = "{UseCloudArticaCategories}";
        $array["EXPLAIN"] = "{UseCloudArticaCategories_text}";
        $array["CMD_ON"] = main_install_kcloud();
        $array["STATUS"] = $MacToUidPHP;
        $array["CMD_OFF"] = main_uninstall_kcloud();
        $html[] = row_status($array);
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="</div></div></div>";

    $refersh=$tpl->RefreshInterval_js("global-status",$page,"global-status=yes");

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$refersh;
    Loadjs('fw.ksrn.filtering.features.php?jstiny=yes');
	</script>";

    echo ksf_style().$tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function jstiny():bool{
    $tpl=new template_admin();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    $remove_feature=$tpl->framework_buildjs("/goshield/uninstall",
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
