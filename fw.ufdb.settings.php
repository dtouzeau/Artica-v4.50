<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

if(isset($_POST["WebFilteringCategoriesToLogRecipient"])){save();exit;}
if(isset($_POST["UfdbReloadBySchedule"])){save();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["notify-js"])){notify_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["notifs-start"])){notifications_start();exit;}
if(isset($_GET["notifs"])){notifications();exit;}
if(isset($_GET["notif-category-add-js"])){notifications_add_js();exit;}
if(isset($_GET["notif-category-settings-js"])){notifications_settings_js();exit;}
if(isset($_GET["notif-category-settings-popup"])){notifications_settings_popup();exit;}
if(isset($_GET["notif-category-add-popup"])){notifications_add_popup();exit;}
if(isset($_GET["notifications-add-save"])){notifications_add_save();exit;}
if(isset($_GET["notif-category-del"])){notifications_del();exit;}
page();

function tabs():bool{
    $page=CurrentPageName();


    echo "<div id='ufdbg-settings'></div>
    <script>LoadAjax('ufdbg-settings','$page?table=yes');</script>";
    return true;
}

function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{WEB_FILTERING}: {parameters}","$page?parameters-popup=yes");
}
function notify_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{notify_categories}: {parameters}","$page?notifs-start=yes",500);
}



function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{WEB_FILTERING}",
        "fab fa-soundcloud",
        "{ufdbgdb_explain}","$page?table=yes","webfiltering-status","progress-ppcategories-restart",false,"table-loader-ufdblight-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{webfiltering_databases}");
        return true;
    }


	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function notifications_settings_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog3("{parameters}","$page?notif-category-settings-popup=yes",650);
    return true;
}
function notifications_settings_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $WebFilteringCategoriesToLogRecipient=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringCategoriesToLogRecipient"));
    $WebFilteringCategoriesToLogTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringCategoriesToLogTimeOut"));

    if($WebFilteringCategoriesToLogTimeOut==0){$WebFilteringCategoriesToLogTimeOut=15;}

    $form[]=$tpl->field_email("WebFilteringCategoriesToLogRecipient","{recipient}",$WebFilteringCategoriesToLogRecipient);
    $form[]=$tpl->field_numeric("WebFilteringCategoriesToLogTimeOut","{scan_each} {minutes}",$WebFilteringCategoriesToLogTimeOut);


    $html=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "LoadAjaxSilent('go-shield-server-form-server','fw.go.shield.server.php?go-shield-server-form-server=yes');dialogInstance3.close();",
        "AsDansGuardianAdministrator");
    echo $html;
    return true;


}

function notifications_add_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{add_category}","$page?notif-category-add-popup=yes");
    return true;
}
function notifications_build():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_notifications` ( `category` integer PRIMARY KEY )";
    $q->QUERY_SQL($sql);

    $results=$q->QUERY_SQL("SELECT category FROM webfilter_notifications");
    $notifs=array();
    foreach ($results as $index=>$ligne){
        VERBOSE("$index => $ligne",__LINE__);
        $notifs[]=$ligne["category"];
    }
    return $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WebFilteringCategoriesToLog",@implode(",",$notifs));

}

function notifications_add_save():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = $_GET["notifications-add-save"];
    $md = $_GET["md"];

    $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql = "CREATE TABLE IF NOT EXISTS `webfilter_notifications` ( `category` integer PRIMARY KEY )";
    $q->QUERY_SQL($sql);

    $q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_notifications (category) VALUES ($id)");
    if (!$q->ok) {
        $tpl->js_display_results($q->mysql_error, true);
        return false;
    }

    echo "$('#$md').remove();\n";
    echo "LoadAjax('notifications-ufd-tablediv','$page?notifs=yes');";
    echo "LoadAjaxSilent('go-shield-server-form-server','fw.go.shield.server.php?go-shield-server-form-server=yes');";
    return notifications_build();
}
function notifications_del():bool{
    $id = $_GET["notif-category-del"];
    $md = $_GET["md"];
    $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("DELETE FROM webfilter_notifications WHERE category=$id");
    echo "$('#$md').remove();\n";
    echo "LoadAjaxSilent('go-shield-server-form-server','fw.go.shield.server.php?go-shield-server-form-server=yes');";
    return notifications_build();

}

function notifications_add_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<table id='table-category-add-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;

    $UfdbMasterCache=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    $q=new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){
        $categories=new categories();
        $categories->initialize();

    }
    $sql="SELECT *  FROM personal_categories ORDER BY categoryname";

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $sql="SELECT *  FROM personal_categories WHERE free_category=1 ORDER BY categoryname";
    }

    $results = $q->QUERY_SQL($sql);


    while ($ligne = pg_fetch_assoc($results)) {
        $categoryname=$ligne["categoryname"];
        $items=$ligne["items"];
        $category_id=$ligne["category_id"];
        $category_description=$ligne["category_description"];
        $category_icon=$ligne["category_icon"];
        $official_category=$ligne["official_category"];
        $free_category=$ligne["free_category"];
        $ISOFFICIAL=false;
        if($official_category==1){$ISOFFICIAL=true;}
        if($free_category==1){$ISOFFICIAL=true;}
        if($category_icon==null){$category_icon="20-categories-personnal.png";}
        if(isset($cats[$category_id])){continue;}
        $elements=null;



        if(!$ISOFFICIAL){
            $category_table_elements=$items;
        }else{
            $category_table_elements=$UfdbMasterCache[$category_id]["items"];
        }

        $license=null;
        $img=$category_icon;
        $md=md5($ligne["categorykey"]);
        $ligne['description']=$tpl->utf8_encode($category_description);


        $js="Loadjs('$page?notifications-add-save=$category_id&md=$md')";
        $button="<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            if($official_category==1){
                $license=" <i class='text-danger'>({license_error})</i>";
                $button="<button class='btn btn-default btn-xs' OnClick=\"javascript:blur()\">{select}</button>";

            }
        }
        if($category_table_elements>0){$elements="<br><strong><small>".FormatNumber($category_table_elements)." {items}</small></strong>";}

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$img' alt=''></td>";
        $html[]="<td width=1% nowrap>".$tpl->_ENGINE_parse_body($categoryname)."</td>";
        $html[]="<td>".$tpl->_ENGINE_parse_body("{$ligne['description']} $license$elements")."</td>";
        $html[]=$tpl->_ENGINE_parse_body("<td width=1% nowrap>$button</td>");
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-category-add-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function notifications_start():bool{
    $page=CurrentPageName();
    echo "<div id='notifications-ufd-tablediv'></div>
    <script>LoadAjax('notifications-ufd-tablediv','$page?notifs=yes');</script>";
    return true;
}

function notifications():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();


    $add="Loadjs('$page?notif-category-add-js=yes');";
    $params="Loadjs('$page?notif-category-settings-js=yes');";
    $html[]="<div class='alert alert-info' style='margin-top:10px'>{ufdb_notif_categories_explain}</div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {add_category} </label>";
    $html[]="<label class=\"btn btn btn-blue\" OnClick=\"$params\"><i class='".ico_params."'></i> {parameters} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" style='width:100%'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{categories}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_notifications` ( `category` integer PRIMARY KEY )";
    $q->QUERY_SQL($sql);

    $results=$q->QUERY_SQL("SELECT * FROM webfilter_notifications");
    $TRCLASS=null;

    $catz=new mysql_catz();

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=md5(serialize($ligne).$index);
        $category_id=$ligne["category"];
        $category_name=$catz->CategoryIntToStr($category_id);


        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td><i class=\"fas fa-envelope\"></i>&nbsp;<strong>$category_name</strong></td>";
        $html[]="<td style='width:1%;' nowrap class='center'>".$tpl->icon_delete("Loadjs('$page?notif-category-del=$category_id&md=$zmd5')","AsDansGuardianAdministrator") ."</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $UfdbReloadBySchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbReloadBySchedule"));
    $EnforceHttpsWithHostname=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceHttpsWithHostname"));
    $EnforceHttpsOfficialCertificate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceHttpsOfficialCertificate"));
    $HttpsProhibitInsecureSslv2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HttpsProhibitInsecureSslv2"));
    $UfdbDebugAll=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll"));
    $ufdbguardReloadTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardReloadTTL"));
    $UfdbGuardThreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardThreads"));
    $UfdbGuardInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardInterface"));
    $UfdbGuardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardPort"));if($UfdbGuardPort==0){$UfdbGuardPort=3977;}
    if($ufdbguardReloadTTL==0){$ufdbguardReloadTTL=10;}
    if($UfdbGuardThreads==0){$UfdbGuardThreads=65;}
    if($UfdbGuardThreads>1285){$UfdbGuardThreads=1285;}
    $CheckProxyTunnel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CheckProxyTunnel"));
    $NoMalwareUris=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoMalwareUris"));
    $StripDomainFromUsername=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StripDomainFromUsername"));
    $RefreshUserList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshUserList"));
    if($RefreshUserList==0){$RefreshUserList=15;}
    $RefreshDomainList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshDomainList"));
    $UfdbGuardMaxUrisize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardMaxUrisize"));

    $ssl=array();

    if($UfdbReloadBySchedule==1){
        $opts[]="{reload_byschedule}";
    }
    if($EnforceHttpsWithHostname==1){
        $ssl[]="{enforce-https-with-hostname}";
    }
    if($EnforceHttpsOfficialCertificate==1){
        $ssl[]="{enforce-https-official-certificate}";
    }
    if($HttpsProhibitInsecureSslv2==1){
        $ssl[]="{https-prohibit-insecure-sslv2}";
    }
    if($HttpsProhibitInsecureSslv2==1){
        $ssl[]="{allow-unknown-protocol-over-https}";
    }
    if($CheckProxyTunnel==1){
        $ssl[]="{check-proxy-tunnel}";
    }
    if(count($ssl)==0){$ssl[]="{default}";}

    $opts[]="{minimum_reload_interval} $ufdbguardReloadTTL {minutes}";

    if($UfdbGuardInterface==null){$UfdbGuardInterface="{all}";}
    if($RefreshUserList==0){$RefreshUserList=15;}
    if($RefreshDomainList==0){$RefreshDomainList=15;}

    $tpl->table_form_field_js("Loadjs('$page?parameters-js=yes')","AsDansGuardianAdministrator");
    $tpl->table_form_field_bool("{verbose_mode}",$UfdbDebugAll,ico_bug);
    $tpl->table_form_field_text("{listen}","$UfdbGuardInterface:$UfdbGuardPort - $UfdbGuardThreads Threads",ico_interface);
    $tpl->table_form_field_text("{options}","<small>".@implode(", ",$opts)."</small>",ico_ssl);
    $tpl->table_form_field_text("{refreshuserlist}","$RefreshUserList {minutes}",ico_timeout);
    $tpl->table_form_field_text("{refreshdomainlist}","$RefreshDomainList {minutes}",ico_timeout);
    $tpl->table_form_field_text("{ssl_options}","<small>".@implode(", ",$ssl)."</small>",ico_ssl);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/uris"));
    if(property_exists($json,"Uris")){
            $tt[]=$json->Uris->RedirectHttps;
            $tt[]=$json->Uris->RedirectFatalError;
            $tt[]=$json->Uris->RedirectLoadingDatabase;
        $tpl->table_form_field_text("{urls} ({default})","<small style='text-transform: none'>".@implode(", ",$tt)."</small>",ico_earth);
        }


    echo $tpl->table_form_compile();
    return true;
}
function parameters_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$UfdbReloadBySchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbReloadBySchedule"));

	$EnforceHttpsWithHostname=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceHttpsWithHostname"));
	$EnforceHttpsOfficialCertificate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceHttpsOfficialCertificate"));
	$HttpsProhibitInsecureSslv2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HttpsProhibitInsecureSslv2"));
	$AllowUnknownProtocolOverHttps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowUnknownProtocolOverHttps"));
	$UfdbDatabasesInMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDatabasesInMemory"));
	$DisableExpressionList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableExpressionList"));
	$UfdbDebugAll=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll"));
	$ufdbguardReloadTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardReloadTTL"));
	$UfdbGuardThreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardThreads"));
	$UfdbGuardInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardInterface"));
	$UfdbGuardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardPort"));if($UfdbGuardPort==0){$UfdbGuardPort=3977;}
	if($ufdbguardReloadTTL==0){$ufdbguardReloadTTL=10;}
	if($UfdbGuardThreads==0){$UfdbGuardThreads=65;}
	if($UfdbGuardThreads>1285){$UfdbGuardThreads=1285;}
	$CheckProxyTunnel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CheckProxyTunnel"));
	$NoMalwareUris=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoMalwareUris"));
	$StripDomainFromUsername=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StripDomainFromUsername"));
	$RefreshUserList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshUserList"));
	if($RefreshUserList==0){$RefreshUserList=15;}
	$RefreshDomainList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshDomainList"));
	$UfdbGuardMaxUrisize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardMaxUrisize"));
	

	
	$form[]=$tpl->field_checkbox("UfdbReloadBySchedule","{reload_byschedule}",$UfdbReloadBySchedule,false,"{ufdb_reload_byschedule_explain}");

	$form[]=$tpl->field_checkbox("EnforceHttpsWithHostname","{enforce-https-with-hostname}",$EnforceHttpsWithHostname,false,"{UFDBGUARD_SSL_OPTS}");
	$form[]=$tpl->field_checkbox("EnforceHttpsOfficialCertificate","{enforce-https-official-certificate}",$EnforceHttpsOfficialCertificate,false,"{UFDBGUARD_SSL_OPTS}");
	$form[]=$tpl->field_checkbox("HttpsProhibitInsecureSslv2","{https-prohibit-insecure-sslv2}",$HttpsProhibitInsecureSslv2,false,"{UFDBGUARD_SSL_OPTS}");
	$form[]=$tpl->field_checkbox("AllowUnknownProtocolOverHttps","{allow-unknown-protocol-over-https}",$AllowUnknownProtocolOverHttps,false,"{UFDBGUARD_SSL_OPTS}");
	$form[]=$tpl->field_checkbox("CheckProxyTunnel","{check-proxy-tunnel}",$CheckProxyTunnel,false,"");
	$form[]=$tpl->field_checkbox("NoMalwareUris","{NoMalwareUris}",$NoMalwareUris,false,"{NoMalwareUris_explain}");
    if($RefreshUserList==0){$RefreshUserList=15;}
    if($RefreshDomainList==0){$RefreshDomainList=15;}
	
	$form[]=$tpl->field_section("{UFDBGUARD_SERVICE_OPTS}");
	$form[]=$tpl->field_interfaces("UfdbGuardInterface", "{interface}", $UfdbGuardInterface);
	$form[]=$tpl->field_numeric("UfdbGuardPort","{listen_port}",$UfdbGuardPort);
	
	$form[]=$tpl->field_checkbox("UfdbDatabasesInMemory","{UfdbDatabasesInMemory}",$UfdbDatabasesInMemory,false,"{UfdbDatabasesInMemory_explain}");
	$form[]=$tpl->field_checkbox("DisableExpressionList","{DisableExpressionLists}",$DisableExpressionList,false,"{DisableExpressionLists_explain}");
	$form[]=$tpl->field_checkbox("UfdbDebugAll","{verbose_mode}",$UfdbDebugAll,false,"");
	$form[]=$tpl->field_numeric("ufdbguardReloadTTL","{minimum_reload_interval} {minutes}",$ufdbguardReloadTTL);
	$form[]=$tpl->field_numeric("UfdbGuardThreads","Threads",$UfdbGuardThreads);
	$form[]=$tpl->field_numeric("UfdbGuardMaxUrisize","{urls_max_size}",$UfdbGuardMaxUrisize);
	
	
	$form[]=$tpl->field_numeric("RefreshUserList","{refreshuserlist} {minutes}",$RefreshUserList);
	$form[]=$tpl->field_numeric("RefreshDomainList","{refreshdomainlist} {minutes}",$RefreshDomainList);
	$form[]=$tpl->field_checkbox("StripDomainFromUsername","{strip-domain-from-username}",$StripDomainFromUsername,false,"");



    $jsrestart=$tpl->framework_buildjs(
        "/ufdb/recompile",
        "ufdbguard.compile.progress",
        "ufdb.restart.log",
        "progress-ppcategories-restart",
        "LoadAjax('ufdbg-settings','$page?table=yes');");

	$html=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "LoadAjax('ufdbg-settings','$page?table=yes');dialogInstance2.close();$jsrestart",
        "AsDansGuardianAdministrator");
	echo $html;
    return true;
	
}

function save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    notifications_build();
    return true;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'):string{$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}