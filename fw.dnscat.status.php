<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["section-rpz-js"])){section_rpz_js();exit();}
if(isset($_GET["section-rpz-popup"])){section_rpz_popup();exit;}
if(isset($_POST["CategoryServiceRPZEnabled"])){section_rpz_save();exit;}

if(isset($_GET["dnscatz-table-status-left"])){status_left();exit;}
if(isset($_GET["dnscatz-table-status-top"])){status_top();exit;}
if(isset($_POST["none"])){die();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["DnscatzInterface"])){Save();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["remove-database-ask"])){remove_database_ask();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["rpzserver-reload-js"])){rpzserver_reload_js();exit;}
if(isset($_GET["ufdb-update-js"])){ufdb_update_js();exit;}
status();

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?table-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{global_parameters}","$page?form-popup=yes");
    return true;
}
function rpzserver_reload_js():bool{
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/server/rpz/reload");
    echo $tpl->js_ok("{success}");
    return admin_tracks("Reload the RPZ server access");
}
function ufdb_update_js():bool{
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/server/ufdb/update");
    echo $tpl->js_ok("{success}");
    return admin_tracks("Launch Web-filtering databases update");
}

function status():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_UFDBCAT} &raquo;&raquo; {service_status}",
        "fa-solid fa-engine","{APP_UFDBCAT_EXPLAIN}","$page?tabs=yes","categories-service",
    "progress-ufdbcat-restart",false,"table-ufdbcatstatus");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function section_rpz_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/server/rpz/reload");
    return admin_tracks_post("Saving RPZ features in Categories service");
}
function section_rpz_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));
    $CategoryServiceRPZInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZInterface");
    $CategoryServiceRPZPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZPort"));
    $CategoryServiceRPZTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZTTL"));
    $CategoryServiceRPZAction=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZAction"));
    $CategoryServiceRPZCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZCertificate");
    $CategoryServiceRPZSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZSSL"));
    $CategoryServiceWebFilteringEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringEnabled"));

    if($CategoryServiceRPZPort==0){
        $CategoryServiceRPZPort=9905;
    }
    if($CategoryServiceRPZTTL==0){
        $CategoryServiceRPZTTL=10;
    }
    $RPZActions[1]="{drop}";
    $RPZActions[2]="NXDOMAIN";
    $RPZActions[3]="{whitelist}";


    $form[]=$tpl->field_checkbox("CategoryServiceRPZEnabled","{enable_feature}",$CategoryServiceRPZEnabled,true,"");
    $form[]=$tpl->field_interfaces("CategoryServiceRPZInterface","{listen_interface}",$CategoryServiceRPZInterface);
    $form[]=$tpl->field_numeric("CategoryServiceRPZPort","{listen_port} (HTTP)",$CategoryServiceRPZPort);
    $form[]=$tpl->field_checkbox("CategoryServiceRPZSSL","{use_ssl}",$CategoryServiceRPZSSL);
    $form[]=$tpl->field_certificate("CategoryServiceRPZCertificate","{certificate}",$CategoryServiceRPZCertificate);
    $form[]=$tpl->field_section("{dns_servers} (RPZ)");
    $form[]=$tpl->field_numeric("CategoryServiceRPZTTL","{ttl} ({minutes})",$CategoryServiceRPZTTL);
    $form[]=$tpl->field_array_hash($RPZActions,"CategoryServiceRPZAction","{action} ({default})",$CategoryServiceRPZAction);
    $form[]=$tpl->field_section("{proxy_clients} ({webfiltering})");
    $CategoryServiceWebFilteringEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringEnabled"));

    $CategoryServiceWebFilteringOfficials=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringOfficials"));

    $form[]=$tpl->field_checkbox("CategoryServiceWebFilteringEnabled","{web_filtering_proxy_repository}",$CategoryServiceWebFilteringEnabled,"CategoryServiceWebFilteringOfficials");
    $form[]=$tpl->field_checkbox("CategoryServiceWebFilteringOfficials","{official_categories_support}",$CategoryServiceWebFilteringOfficials,false);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{CategoryDatabaseDistribution_explain}","{apply}",section_js_form(),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_js_form():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('dnscatz-main-status','$page?table=yes');";
}
function section_rpz_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{CategoryDatabaseDistribution}","$page?section-rpz-popup=yes");
}
function form_popup():bool{
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $DnscatzProfessional=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzProfessional");
    $DnscatzInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzInterface");
    $DnsCatzPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzPort"));
    $DnscatzDomain=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzDomain"));
    $DnsCatzTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzTTL"));
    $DnsCatzCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzCrypt"));
    $DnsCatzPassPharse=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzPassPharse"));
    if($DnsCatzPassPharse==null){$DnsCatzPassPharse=generateRandomString();}

    if($UnboundEnabled==0) {
        $form[] = $tpl->field_interfaces("DnscatzInterface", "nodef:{listen_interface}", $DnscatzInterface);
        $form[] = $tpl->field_numeric("DnsCatzPort", "{listen_port}", $DnsCatzPort);
    }else{
        $tpl->field_hidden("DnscatzInterface",$DnscatzInterface);
        $tpl->field_hidden("DnsCatzPort",$DnsCatzPort);
    }
    $form[]=$tpl->field_numeric("DnsCatzTTL","{ttl} ({minutes})",$DnsCatzTTL);
    $form[]=$tpl->field_text("DnscatzDomain","{domain}",$DnscatzDomain);
    if($DnscatzProfessional==1) {
        $form[] = $tpl->field_checkbox("DnsCatzCrypt", "{encrypt_data}", $DnsCatzCrypt, "DnsCatzPassPharse");
        $form[] = $tpl->field_text("DnsCatzPassPharse", "{passphrase}", $DnsCatzPassPharse, true);
    }

    $jsrestart="dialogInstance1.close();".restart_js();
    $html[]="<div id='progress-ufdbcat-restart-$t'></div>";
    $html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}","LoadAjax('table-ufdbcatstatus','$page?tabs=yes');$jsrestart","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restart_js():string{
    $page=CurrentPageName();
    $tpl            = new template_admin();
    return $tpl->js_restart_api("/dnscatz/restart","LoadAjax('table-ufdbcatstatus','$page?tabs=yes');");
}
function status_top():bool{
    $tpl            = new template_admin();
    $status_rpz_server=status_rpz_server();
    $status_ufdb_repos=status_ufdb_repos();
    $html[]="<table style='width:100%;margin-top:-5px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:50%'>$status_rpz_server</td>";
    $html[]="<td style='vertical-align:top;width:50%;padding-left: 5px'>$status_ufdb_repos</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function UfdbDatabasesEnabled(){

    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));

    if($CategoryServiceRPZEnabled==0){
        return false;
    }

    $CategoryServiceWebFilteringEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringEnabled"));

    if($CategoryServiceWebFilteringEnabled==0){
        return false;
    }

    $CategoryServiceWebFilteringOfficials=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringOfficials"));

    if($CategoryServiceWebFilteringOfficials==0){
        return false;
    }
    return true;

}
function status_ufdb_repos(){
    $page=currentPageName();
    $tpl            = new template_admin();
    if(!UfdbDatabasesEnabled()){
        $btn["ico"]=ico_params;
        $btn["name"]="{enable_feature}";
        $btn["js"]="Loadjs('$page?section-rpz-js=yes')";
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey",ico_database,"{inactive2}","{official_categories_support}",$btn));
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BlackListsInfoStatus"));

    if (is_null($json)){
        $btn["ico"]=ico_download;
        $btn["name"]="{launch_updates}";
        $btn["js"]="Loadjs('$page?ufdb-update-js=yes')";

        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey",ico_database,"{missing_databases}","{official_categories_support}",$btn));
    }

    if(!property_exists($json,"check_time")){
        $btn["ico"]=ico_download;
        $btn["name"]="{launch_updates}";
        $btn["js"]="Loadjs('$page?ufdb-update-js=yes')";

        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey",ico_database,"{missing_databases}","{official_categories_support}",$btn));
    }
    $check_time=$json->check_time;
    $btn["ico"]=ico_download;
    $btn["name"]="{launch_updates}";
    $btn["js"]="Loadjs('$page?ufdb-update-js=yes')";
    $lastUpdate=distanceOfTimeInWords($check_time,time(),true);
    return $tpl->_ENGINE_parse_body($tpl->widget_h("green",ico_database,"<small style='color:white'>$lastUpdate</small>","{official_categories_support}",$btn));
}

function status_rpz_server():string{
    $tpl            = new template_admin();
    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));
    $page=currentPageName();

    if($CategoryServiceRPZEnabled==0){
        $btn["ico"]=ico_params;
        $btn["name"]="{enable_feature}";
        $btn["js"]="Loadjs('$page?section-rpz-js=yes')";
        return $tpl->widget_h("grey",ico_database,"{inactive2}","RPZ Server",$btn);
    }

    $CategoryServiceRPZInterfaceTov4=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZInterface");
    $CategoryServiceRPZPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServicePort"));
    $CategoryServiceRPZSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZSSL"));
    $proto="http";
    if($CategoryServiceRPZSSL==1){
        $proto="https";
    }
    if($CategoryServiceRPZPort==0){
        $CategoryServiceRPZPort=9905;
    }
    if($CategoryServiceRPZInterfaceTov4==""){
        $CategoryServiceRPZInterfaceTov4="127.0.0.1";
    }

    $uri="$proto://$CategoryServiceRPZInterfaceTov4:$CategoryServiceRPZPort/status";
    $curl=new ccurl($uri);
    $curl->NoLocalProxy();

    $btn["ico"]=ico_refresh;
    $btn["name"]="{reload}";
    $btn["js"]="Loadjs('$page?rpzserver-reload-js')";
    ///categories/server/rpz/reload

    if(!$curl->get()){
        return $tpl->widget_h("red",ico_bug,"{error}","RPZ Server $curl->error",$btn);
    }


    $json = json_decode($curl->data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->widget_h("red",ico_bug,"{error}",json_last_error());


    }else {
        if (!$json->Status) {
            return $tpl->widget_h("red",ico_bug,"{error}",$json->Error);
        }
    }
    $Requests=$tpl->FormatNumber($json->Requests);
    return $tpl->widget_h("green",ico_database,"{active2} $Requests {requests}","RPZ Server",$btn);
}
function status_left():bool{
    $tpl            = new template_admin();
    $html           = array();
    $CountOfDatabase = 0;
    $page           = CurrentPageName();
    $DBUSED=$tpl->widget_grey("{used_databases}","{none}");
    if(is_file("/etc/dnscatz.databases.conf")){
        $CurrentLoaded=explode(",",trim(@file_get_contents("/etc/dnscatz.databases.conf")));
        $CountOfDatabase=count($CurrentLoaded);
        if($CountOfDatabase>0) {
            $CountOfDatabase = count($CurrentLoaded) - 1;
        }
    }

    if($CountOfDatabase>0){
        $DBUSED=$tpl->widget_vert("{used_databases}",$CountOfDatabase);
    }

    $html[]=status_service();
    $html[]=$DBUSED;
    $html[]="<script>LoadAjaxSilent('dnscatz-table-status-top','$page?dnscatz-table-status-top=yes');</script>";
    echo $tpl->_engine_parse_body($html);
    return true;
}
function status_service():string{
    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $sock           = new sockets();


    $json = json_decode($sock->REST_API("/dnscatz/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));

    }else {
        if (!$json->Status) {
            return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error", "{error}"));

        }
    }

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    return  $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_UFDBCAT", $jsRestart));


}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='dnscatz-main-status'></div>
    <script>LoadAjaxSilent('dnscatz-main-status','$page?table=yes');</script>";
    return true;
}
function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$sock->REST_API("/dnscatz/status");
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/dnscat.status");
    $txt=array();
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $DnscatzProfessional=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzProfessional");
    $DnscatzInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzInterface");
    $DnsCatzPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzPort"));
    $DnscatzDomain=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzDomain"));
    $DnsCatzTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzTTL"));
    $DnsCatzCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzCrypt"));
    $DnsCatzPassPharse=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzPassPharse"));
    if($DnsCatzPassPharse==null){$DnsCatzPassPharse=generateRandomString();}

    if($DnscatzInterface==null){$DnscatzInterface="lo";}
    if($DnscatzDomain==null){$DnscatzDomain="categories.tld";}
    if($DnsCatzTTL==0){$DnsCatzTTL=35;}
    if($DnsCatzPort==0){$DnsCatzPort=3477;}


	$html[]="<table style='width:100%;margin-top: 15px'>";
	$html[]="<tr>";
	$html[]="<td style=\"width:260px;vertical-align:top\" >";
    $html[]="<div id='dnscatz-table-status-left'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='dnscatz-table-status-top'></div>";




    if($UnboundEnabled==0) {
        $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
        $tpl->table_form_field_text("{listen_interface}","$DnscatzInterface:$DnsCatzPort",ico_nic);

    }else{
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{listen_interface}","127.0.0.1:3477 ({APP_UNBOUND})",ico_nic);
    }
    $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
    $tpl->table_form_field_text("{ttl}","$DnsCatzTTL {minutes}",ico_timeout);
    $tpl->table_form_field_text("{domain}","$DnscatzDomain",ico_earth);
    $tpl->table_form_field_bool("{SQUID_ISP_MODE}","$DnscatzProfessional",ico_city);
    $tpl->table_form_field_bool("{encrypt_data}","$DnsCatzCrypt",ico_crypt);

    $CategoryServiceWebFilteringEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringEnabled"));
    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));
    if($CategoryServiceRPZEnabled==0){
        $CategoryServiceWebFilteringEnabled=0;
        $tpl->table_form_field_js("Loadjs('$page?section-rpz-js=yes')");
        $tpl->table_form_field_bool("{CategoryDatabaseDistribution}",0,ico_database);
    }else{
        $CategoryServiceRPZInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZInterface");
        $CategoryServiceRPZPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZPort"));
        $CategoryServiceRPZTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZTTL"));
        $CategoryServiceRPZAction=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZAction"));
        $CategoryServiceRPZSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZSSL"));
        $proto="http";

        $RPZActions[1]="{drop}";
        $RPZActions[2]="NXDOMAIN";
        $RPZActions[3]="{whitelist}";
        if($CategoryServiceRPZPort==0){
            $CategoryServiceRPZPort="9905";
        }
        if($CategoryServiceRPZTTL==0){
            $CategoryServiceRPZTTL=10;
        }
        if($CategoryServiceRPZInterface==""){
            $CategoryServiceRPZInterface="*";
        }
        if($CategoryServiceRPZSSL==1){
            $proto="https";
        }
        if($CategoryServiceRPZAction==0){$CategoryServiceRPZAction=2;}
        $txt[]="$proto://$CategoryServiceRPZInterface:$CategoryServiceRPZPort {default} $RPZActions[$CategoryServiceRPZAction] {ttl} $CategoryServiceRPZTTL {minutes}";
        $text=@implode( " ", $txt);

        $tpl->table_form_field_js("Loadjs('$page?section-rpz-js=yes')");
        $tpl->table_form_field_text("{CategoryDatabaseDistribution}","<small>$text</small>",ico_database);
    }
    $tpl->table_form_field_js("Loadjs('$page?section-rpz-js=yes')");
    $tpl->table_form_field_bool("{web_filtering_proxy_repository}",$CategoryServiceWebFilteringEnabled,ico_file_zip);



    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $jsCompile=$tpl->framework_buildjs(
        "/dnscatz/compile/all",
        "dnscat.compile.progress",
        "dnscat.compile.log",
        "progress-ufdbcat-restart",
        "LoadAjax('table-ufdbcatstatus','$page?tabs=yes');"
    );
    $users=new usersMenus();
    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    if($users->AsDansGuardianAdministrator){
        $btns[]="<label class=\"btn btn-info\" OnClick=\"$jsCompile\"><i class='fa fa-save'></i> {compile_all_categories} </label>";
    }
    $btns[]="</div>";


    $TINY_ARRAY["TITLE"]="{APP_UFDBCAT}";
    $TINY_ARRAY["ICO"]="fa-solid fa-engine";
    $TINY_ARRAY["EXPL"]="{APP_UFDBCAT_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $jsref=$tpl->RefreshInterval_js("dnscatz-table-status-left",$page,"dnscatz-table-status-left=yes");

    $html[]="<script>$jstiny;$jsref</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}

function Save():bool{
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	if($_POST["DnsCatzCrypt"]==1){
        if (mb_strlen($_POST["DnsCatzPassPharse"], '8bit') !== 32) {
            echo "jserror: Needs a 256-bit key! (32 characters) for pass phrase.";
            return false;
        }
    }

	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	return true;
}

function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}