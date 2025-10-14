<?php
$GLOBALS["DYNAMIC_RATE_FEATURE"]=false;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();



if(isset($_GET["JsBC-js"])){www_parameters_JsBC_js();exit;}
if(isset($_GET["JsBC-popup"])){www_parameters_JsBC_popup();exit;}
if(isset($_POST["JsBC"])){www_parameters_JsBC_save();exit;}

if(isset($_GET["BotChecker-js"])){www_parameters2_BotChecker_save();exit;}
if(isset($_GET["monitored-frontend"])){www_parameters2_auditFrontend_save();exit;}
if(isset($_GET["jping"])){jsping();exit;}
if(isset($_GET["js-tiny-ping"])){js_tiny_ping();exit;}
if(isset($_GET["download-db"])){download_database();exit;}
if(isset($_GET["MaintenanceSite"])){MaintenanceSite();exit;}
if(isset($_GET["TableNavigate"])){table_pagination();exit;}
if(isset($_GET["MaxItems"])){table_MaxItems();exit;}
if(isset($_GET["js-tiny"])){js_tiny();exit;}
if(isset($_GET["destinations-prepare"])){destinations_prepare();exit;}
if(isset($_GET["success-js"])){success_js();exit;}
if(isset($_GET["backend-error-js"])){backend_error_js();exit;}
if(isset($_GET["backend-error-popup"])){backend_error_popup();exit;}
if(isset($_GET["backend-analyze-js"])){backend_js();exit;}
if(isset($_GET["backend-analyze2-js"])){backend2_js();exit;}
if(isset($_GET["create-self-signed"])){create_self_signed();exit;}
if(isset($_GET["ProxySslServerName-js"])){proxy_ssl_server_name_js();exit;}
if(isset($_GET["ProxySslServerName-popup"])){proxy_ssl_server_name_popup();exit;}
if(isset($_POST["vitrification"])){www_parameters_vitrification_save();exit;}
if(isset($_POST["ProxySslServerName"])){proxy_ssl_server_name_save();exit;}

if(isset($_GET["check-reverse"])){check_reverse_js();exit;}
if(isset($_GET["check-reverse-popup"])){check_reverse_popup();exit;}
if(isset($_GET["check-reverse-perform"])){check_reverse_perform();exit;}
if(isset($_GET["rows-ping"])){rows_ping();exit;}
if(isset($_GET["td-row"])){td_row_status($_GET["td-row"]);exit;}
if(isset($_GET["td-destinations"])){td_destinations();exit;}
if(isset($_GET["locked-config-js"])){locked_config_js();exit;}
if(isset($_GET["locked-config-popup"])){locked_config_popup();exit;}
if(isset($_GET["locked-config-disable"])){locked_config_disable();exit;}

if(isset($_GET["table-form"])){table_form();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["disable-all-js"])){disable_all_js();exit;}
if(isset($_GET["disable-fw-js"])){disable_fw_js();exit;}
if(isset($_GET["enable-fw-js"])){enable_fw_js();exit;}

if(isset($_GET["new-www-after"])){new_www_after();exit;}
if(isset($_GET["new-www-js"])){new_www_js();exit;}
if(isset($_GET["new-www"])){new_www();exit;}
if(isset($_GET["www-js"])){www_js();exit;}
if(isset($_GET["www-tabs"])){www_tabs();exit;}
if(isset($_GET["www-parameters"])){www_parameters();exit;}
if(isset($_GET["www-parameters2"])){www_parameters2();exit;}
if(isset($_GET["www-parameters-general-js"])){www_parameters_section_js("general");exit;}
if(isset($_GET["www-parameters-vitrification-js"])){www_parameters_vitrification_js();exit;}
if(isset($_GET["www-parameters-vitrification-switch"])){www_parameters_vitrification_switch();exit;}

if(isset($_GET["www-parameters-security-js"])){www_parameters_section_js("security");exit;}
if(isset($_GET["www-parameters-ssl-js"])){www_parameters_section_js("ssl");exit;}
if(isset($_GET["www-parameters-general-popup"])){www_parameters_general_popup();exit;}
if(isset($_GET["www-parameters-security-popup"])){www_parameters_security_popup();exit;}
if(isset($_GET["www-parameters-ssl-popup"])){www_parameters_ssl_popup();exit;}
if(isset($_GET["www-parameters-vitrification-popup"])){www_parameters_vitrification_popup();exit;}
if(isset($_GET["www-parameters-vitrification-status"])){
    www_parameters_vitrification_status();exit;
}

if(isset($_GET["restart-needed"])){restart_needed_js();exit;}
if(isset($_POST["restart-needed"])){restart_needed_perform();exit;}

if(isset($_GET["doh-params"])){doh_parameters();exit;}
if(isset($_GET["www-hosts"])){www_hosts();exit;}
if(isset($_GET["www-hosts2"])){www_hosts2();exit;}
if(isset($_GET["www-host-edit"])){www_hosts_edit_js();exit;}
if(isset($_GET["www-host-edit-popup"])){www_hosts_edit_popup();exit;}
if(isset($_GET["www-host-delete"])){www_hosts_delete();exit;}
if(isset($_POST["hosts-delete"])){www_hosts_delete_perform();exit;}
if(isset($_GET["duplicate-js"])){duplicate_js();exit;}
if(isset($_POST["duplicate-from"])){duplicate_perform();exit;}

if(isset($_POST["ID"])){www_save();exit;}
if(isset($_POST["doh-params"])){doh_parameters_save();exit;}
if(isset($_POST["hosts-id"])){www_hosts_save();exit;}
if(isset($_POST["none"])){exit;}
if(isset($_GET["compile"])){compile_js();exit;}
if(isset($_POST["compile-confirm"])){compile_confirm();exit;}
if(isset($_GET["compile-firewall"])){compile_firewall();exit;}
if(isset($_GET["badconf"])){badconf_js();exit;}
if(isset($_GET["badconf-popup"])){badconf_popup();exit;}
if(isset($_GET["goodconf"])){goodconf_js();exit;}
if(isset($_GET["goodconf-popup"])){goodconf_popup();exit;}
if(isset($_POST["goodconf_id"])){goodconf_save();exit;}

if(isset($_POST["disable-all"])){disable_all_perform();exit;}
if(isset($_POST["disable-fw"])){disable_fw_perform();exit;}
if(isset($_POST["enable-fw"])){enable_fw_perform();exit;}
if(isset($_GET["enable-waf"])){enable_fw_single();exit;}

if(isset($_GET["cache-settings-js"])){cache_settings_js();exit;}
if(isset($_GET["cache-settings-popup"])){cache_settings_popup();exit;}
if(isset($_POST["cacheid"])){cache_settings_save();exit;}

if(isset($_GET["reconfigure-all-js"])){reconfigure_all_sites();exit;}
if(isset($_GET["extract-domains-js"])){extract_domains_js();exit;}
if(isset($_GET["extract-domains-popup"])){extract_domains_popup();exit;}
if(isset($_GET["action-js"])){action_js();exit;}
if(isset($_GET["action-popup"])){action_popup();exit;}
if(isset($_POST["reconfigure_all_sites"])){reconfigure_all_sites_perform();exit;}



page();
function cache_settings_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["cache-settings-js"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    return $tpl->js_dialog1("{cache} $servicename","$page?cache-settings-popup=$ID");
}
function locked_config_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog1("{locked_configuration}","$page?locked-config-popup=yes");
}
function locked_config_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $function=$_GET["function"];
    $html[]=$tpl->div_warning("{locked_configuration}||{locked_configuration_explain}");
    $html[]="<div style='margin:30px' class=center>";
    $html[]=$tpl->button_autnonome("{unlock}",
        "Loadjs('$page?locked-config-disable=yes&function=$function')",ico_lock,"AsWebMaster",335,"btn-warning");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function locked_config_disable():bool{
    $function=$_GET["function"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableBuildNginxConfig",0);
    header("content-type: application/x-javascript");
    echo "dialogInstance1.close();\n";
    echo "$function();\n";
    return admin_tracks("Reverse-proxy has been unlocked");
}


function restart_needed_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $function=$_GET["function"];
    if(strlen($function)>2){
        $function="$function()";
    }

    $service_restart=$tpl->framework_buildjs("nginx:/reverse-proxy/restarthup",
        "nginx.restart.progress","nginx.restart.progress.txt",
        "progress-websites-restart","$function;document.getElementById('progress-websites-restart').innerHTML='';");

    return $tpl->js_confirm_execute("{restart_needed_explain}","restart-needed","yes",$service_restart);
}
function restart_needed_perform():bool{
    return admin_tracks("Restart the reverse-proxy in order to apply new sites");
}
function rows_ping(){
    $tb=explode(",",$_GET["rows-ping"]);
    $f=array();
    $page=CurrentPageName();
    foreach ($tb as $sID){
        $ID=intval($sID);
        if($ID==0){continue;}
        $ServerStats=base64_encode(td_row_serverstats($ID));
        $f[]="if( document.getElementById('rcolorStats-$ID') ){";
        $f[]="\ttempdata=base64_decode('$ServerStats');";
        $f[]="\tdocument.getElementById('rcolorStats-$ID').innerHTML=tempdata;";
        $f[]="}";
        $f[]="Loadjs('$page?td-row=$ID&no-destinations=yes');";
    }
    echo @implode("\n",$f);
}


function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function success_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    return $tpl->js_config_applied();
}


function cache_settings_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["cache-settings-popup"]);
    $socknginx=new socksngix($ID);
    $servicename=get_servicename($ID);
    $proxy_cache_valid=intval($socknginx->GET_INFO("proxy_cache_valid"));
    if($proxy_cache_valid==0){$proxy_cache_valid=4320;}

    $tpl->field_hidden("cache",$ID);
    $tpl->field_hidden("cacheid",0);

    $cgicache=intval($socknginx->GET_INFO("cgicache"));

    $form[]=$tpl->field_checkbox("cgicache","{caching_using_redis}", $cgicache,false,"{proxy_cache_revalidate_explain}");
    $form[]=$tpl->field_numeric("proxy_cache_valid","{proxy_cache_valid} ({minutes})",
        $proxy_cache_valid,"{proxy_cache_valid_text}");

    $form[] = $tpl->field_checkbox("cache_images", "{cache_images} ({browser})",
        intval($socknginx->GET_INFO("cache_images")), false, "");

    $form[] = $tpl->field_checkbox("cache_htmlext", "{cache_htmlext} ({browser})",
        intval($socknginx->GET_INFO("cache_htmlext")), false, "");

    $form[] = $tpl->field_checkbox("cache_binaries", "{cache_binaries} ({browser})",
        intval($socknginx->GET_INFO("cache_binaries")), false, "");


    $service_reconfigure="Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');Loadjs('$page?td-row=$ID');dialogInstance1.close();";

    $html[]="<div id='cache-setting-$ID'></div>";
    $html[]=$tpl->form_outside("$servicename: {cache} ($ID)",$form,null,"{apply}",
        "Loadjs('$page?td-row=$ID');$service_reconfigure",
        "AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function cache_settings_save():bool{
    $tpl = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $ID=$_POST["cache"];
    unset($_POST["cache"]);
    $socknginx=new socksngix($ID);
    $servicename=get_servicename($ID);
    foreach ($_POST as $key=>$val){
        $socknginx->SET_INFO($key,$val);
    }
    admin_tracks_post("Reverse-site site cache settings $servicename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return true;
}
function disable_all_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    return $tpl->js_confirm_execute("{disable_all}","disable-all","yes","NgixSitesReload()");
}
function disable_fw_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    return $tpl->js_confirm_execute("{disable_all_web_firewall}","disable-fw","yes","NgixSitesReload()");
}
function enable_fw_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    return $tpl->js_confirm_execute("{enable_all_web_firewall}","enable-fw","yes","NgixSitesReload()");
}
function disable_all_perform():bool{
    admin_tracks("Disable all web services");
    $q=new lib_sqlite(NginxGetDB());
    $sql="UPDATE nginx_services SET enabled=0";
    $q->QUERY_SQL($sql);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?disable-all=yes");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    return true;
}
function disable_fw_perform():bool{
    admin_tracks("Disable Web application firewall on all web services");
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT ID FROM nginx_services");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $sockngix=new socksngix($ID);
        $sockngix->SET_INFO("EnableModSecurity",0);
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?modsecurity-compile-all=yes");
    return true;
}
function enable_fw_single():bool{
    header("content-type: application/x-javascript");
    $ID=intval($_GET["enable-waf"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];

    $sockngix=new socksngix($ID);
    $sockngix->SET_INFO("EnableModSecurity",1);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return  admin_tracks("Activate Web application firewall for $servicename");

}

function enable_fw_perform():bool{
    admin_tracks("Enable Web application firewall on all web services");
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT ID FROM nginx_services");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $sockngix=new socksngix($ID);
        $sockngix->SET_INFO("EnableModSecurity",1);
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?modsecurity-compile-all=yes");
    return true;
}
function duplicate_js():bool{
    $ID                         = intval($_GET["duplicate-js"]);
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $q                          = new lib_sqlite(NginxGetDB());
    $t2=time();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];

    $tmpname="$servicename - copied";
    header("content-type: application/x-javascript");
    $jafter="NgixSitesReload();";
    $ask=$tpl->javascript_parse_text("{duplicate_the_ruleid_give_name}");
    $html="
		var x_Duplicaterule$t2= function (obj) {
			var res=obj.responseText;
			if (res.length>0){alert(res);}
			$jafter
		}
	
	
		function Duplicaterule$t2(){
			var rulename=prompt('$ask $servicename','$tmpname');
			if(!rulename){return;}
			 var XHR = new XHRConnection();
		     XHR.appendData('duplicate-from', '$ID');
		     var pp=encodeURIComponent(rulename);
		     XHR.appendData('duplicate-name', pp);
		     XHR.sendAndLoad('$page', 'POST',x_Duplicaterule$t2); 
		
		}
		
	
	Duplicaterule$t2();";
    echo $html;
    return true;

}

function duplicate_perform():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $fromid=$_POST["duplicate-from"];
    $new_servicename=$_POST["duplicate-name"];
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$fromid");
    $tempvalue=md5(time().$new_servicename);
    $fkeys=array();
    $fvalues=array();
    $exclude["goodconftime"]=true;
    $exclude["badconf"]=true;
    $exclude["badconf_error"]=true;
    $exclude["ID"]=true;
    $exclude["BackendErrDetail"]=true;
    $exclude["BackendErr"]=true;
    $exclude["rebooted"]=true;
    $exclude["BackendAnalyzed"]=true;
    $exclude["BackendAnalyzedTime"]=true;
    $exclude["MaintenanceSite"]=true;
    $exclude["BackendStatus"]=true;
    $exclude["FrontendErr"]=true;
    $exclude["CompileTime"]=true;
    $exclude["FrontendErrDetail"]=true;
    $exclude["BackendAnalyzeLock"]=true;
    $exclude["phpengine"]=true;
    $exclude["slalatencyok"]=true;
    $exclude["ResolvErrDetail"]=true;
    $exclude["latestlatency"]=true;
    $exclude["latencyscore"]=true;

    foreach ($ligne as $key=>$value){
        if(is_numeric($key)){continue;}
        if($key=="servicename"){$value=$tempvalue;}
        if(isset($exclude[$key])){continue;}
        $fkeys[]=$key;
        $fvalues[]="'".$q->sqlite_escape_string2($value)."'";

    }
    $sql="INSERT INTO nginx_services (".@implode(",",$fkeys).") 
    VALUES (".@implode(",",$fvalues).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n$sql";return false;}
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE servicename='$tempvalue'");
    $new_serviceid=intval($ligne["ID"]);
    if($new_serviceid==0){echo "Unable to find $tempvalue";return false;}
    $new_servicename=$q->sqlite_escape_string2($new_servicename);
    $q->QUERY_SQL("UPDATE nginx_services SET servicename='$new_servicename' WHERE ID=$new_serviceid");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($new_serviceid);

    $results=$q->QUERY_SQL("SELECT * FROM `service_parameters` WHERE serviceid=$fromid");
    foreach ($results as $index=>$ligne){
        $zkeyz=$ligne["zkey"];
        $value=$q->sqlite_escape_string2($ligne["zvalue"]);
        $q->QUERY_SQL("INSERT INTO `service_parameters` (serviceid,zkey,zvalue) VALUES('$new_serviceid','$zkeyz','$value')");
        if(!$q->ok){echo "service_parameters:$q->mysql_error";return false;}
    }
    $results=$q->QUERY_SQL("SELECT * FROM `stream_ports` WHERE serviceid=$fromid");
    foreach ($results as $index=>$ligne){
        $interface=$ligne["interface"];
        $port=$ligne["port"];
        $options=$q->sqlite_escape_string2($ligne["options"]);
        $md5=md5($ligne["interface"].$ligne["port"].$new_serviceid);

        $sql="INSERT INTO stream_ports(serviceid,interface,port,zmd5,options) 
    VALUES ($new_serviceid,'$interface',$port,'$md5','$options');";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "stream_ports:$q->mysql_error\n$sql";return false;}
    }


    $results=$q->QUERY_SQL("SELECT * FROM `ngx_stream_access_module` WHERE serviceid=$fromid");
    foreach ($results as $index=>$ligne) {

        $item=$ligne["item"];
        $allow=intval($ligne["allow"]);

        $q->QUERY_SQL("INSERT OR IGNORE INTO ngx_stream_access_module(serviceid,item,allow) 
				VALUES ($new_serviceid,'$item',$allow)");
        if(!$q->ok){echo "ngx_stream_access_module:$q->mysql_error";return false;}
    }

    $results=$q->QUERY_SQL("SELECT * FROM `backends` WHERE serviceid=$fromid");
    foreach ($results as $index=>$ligne) {
        $hostname=$ligne["hostname"];
        $port=intval($ligne["port"]);
        $options=$q->sqlite_escape_string2($ligne["options"]);
        $q->QUERY_SQL("INSERT OR IGNORE INTO backends(serviceid,hostname,port,options) 
				VALUES ($new_serviceid,'$hostname',$port,'$options')");
        if(!$q->ok){echo "backends:$q->mysql_error\n$sql";}
    }
    return true;
}

function reconfigure_all_sites():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    $service_reconfigure=$tpl->framework_buildjs(
        "/reverse-proxy/hupreconfigure",
        "nginx.reconfigure.allsites.progress",
        "nginx.reconfigure.all.progress.txt",
        "progress-websites-restart",
        "NgixSitesReload()");

    $tpl->js_confirm_execute("{reconfigure_all_sites_warn}","reconfigure_all_sites","reconfigure_all_sites",$service_reconfigure);
    return true;
}
function reconfigure_all_sites_perform(){
    admin_tracks("Reconfigured all reverse-proxy sites");
}

function extract_domains_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->js_dialog1("{extract_domains}", "$page?extract-domains-popup=yes");
    return true;

}

function enable():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    $ID=$_GET["enable"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename,enabled FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    if($ligne["enabled"]==0){
        $text="Enable Web service $servicename";
        $q->QUERY_SQL("UPDATE nginx_services SET `enabled`='1' WHERE ID=$ID");
    }else{
        $text="Disable Web service $servicename";
        $q->QUERY_SQL("UPDATE nginx_services SET `enabled`='0' WHERE ID=$ID");
    }
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    admin_tracks($text);
    echo td_row_clean($ID)."\n";
    echo "
        function RealoadSite$ID(){
            Loadjs('fw.nginx.sites.php?td-row=$ID');
        }
        setTimeout(\"RealoadSite$ID()\",1000);
    ";
    return true;
}
function delete_js(){
    $ID=intval($_GET["delete"]);
    $q=new lib_sqlite(NginxGetDB());
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $md=$_GET["md"];
    $jsafter="$('#$md').remove();";
    $tpl->js_confirm_delete($ligne["servicename"], "delete", $ID,$jsafter);
}


function badconf_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["badconf"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $tpl->js_dialog1("{bad_configuration} $servicename", "$page?badconf-popup=$ID");
}
function badconf_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["badconf-popup"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT badconf FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];

    $form[]=$tpl->field_textareacode("null", null, base64_decode($ligne["badconf"]));
    echo $tpl->form_outside($servicename, $form,null,null,"NgixSitesReload();","AsSystemWebMaster");
}
function goodconf_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["goodconf"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    return $tpl->js_dialog1("{configuration} $servicename", "$page?goodconf-popup=$ID");
}


function check_redirect_queries($RedirectQueries):array{
    $ico_fleche=ico_arrow_right;
    $RedirectQueries=str_replace(" ","",$RedirectQueries);
    if(preg_match("#^([0-9,]+)=(http|https):([0-9]+)$#", $RedirectQueries,$re)){
        $RedirectQueries="$re[1]=$re[2]://{your_server}:$re[3]";
    }
    if(preg_match("#^([0-9,]+)=([0-9]+)$#", $RedirectQueries,$re)){
        $RedirectQueries="$re[1]=(http {or} https)://{your_server}:$re[2]";
    }
    if(!preg_match("#^([0-9,]+)=(.+)#", $RedirectQueries,$re)){
        return array(true,"<small></small><i class='$ico_fleche'></i>&nbsp; $RedirectQueries</small>");
    }

    if(strpos("  ".$re[1],",")>0){
        $ports = explode(",",$re[1]);
    }else{
        $ports[]=$re[1];
    }
    $textes=array();
    foreach($ports as $port){
        $textes[]="{if} {port} $port";
    }

    $final=implode(" {or} ",$textes)." <i class='$ico_fleche'></i>&nbsp; $re[2]";
    return array(false,"<small>$final</small>");
}

function check_reverse_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["check-reverse"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    return $tpl->js_dialog1("{check} $servicename", "$page?check-reverse-popup=$ID");
}
function check_reverse_popup():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["check-reverse-popup"]);
    echo "<div id='check-reverse-$ID' style='margin-top:10px'></div>\n";
    echo "<script>LoadAjax('check-reverse-$ID','$page?check-reverse-perform=$ID');</script>";
    return true;
}
function check_reverse_perform():bool{
    $html=array();
    $ID=intval($_GET["check-reverse-perform"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?check-reverse=$ID");
    $file=PROGRESS_DIR."/check-reverse-$ID.txt";
    $ERROR=false;
    $f=explode("\n",@file_get_contents($file));
    foreach ($f as $line){
        $html[]= "<div>$line</div>\n";
        if(preg_match("#^ERROR#",$line)){
            $ERROR=true;
        }

    }
    if($ERROR){
        echo "<H1 class='text-danger'>ERROR</H1>";

    }else{
        echo "<H1 style='color:#1ab394'>SUCCESS</H1>";
    }
    echo @implode("\n",$html);
    return true;
}

function extract_domains_popup():bool{
    $f=array();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT * FROM nginx_services ORDER BY zorder";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
       $hosts=explode("||",$ligne["hosts"]);
       foreach ($hosts as $servername){
           $zfirst[strtolower(trim($servername))]=true;
       }

    }
    ksort($zfirst);
    foreach ($zfirst as $host=>$none){
        $f[]=$host;
    }
    $form[]=$tpl->field_textareacode("goodconf", null, @implode("\n",$f));
    echo $tpl->form_outside("{websites}", $form,null,null,"dialogInstance1.close();NgixSitesReload()","AsSystemWebMaster");
    return true;
}

function goodconf_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["goodconf-popup"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename,goodconf FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $form[]=$tpl->field_hidden("goodconf_id", $ID);
    $form[]=$tpl->field_textareacode("goodconf", null, base64_decode($ligne["goodconf"]));
    echo $tpl->form_outside($servicename, $form,null,"{apply}","dialogInstance1.close();NgixSitesReload()","AsSystemWebMaster");
}
function goodconf_save(){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_POST["goodconf_id"];
    $tpl->CLEAN_POST();
    $goodconf=base64_encode($_POST["goodconf"]);
    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL("UPDATE nginx_services SET goodconftime=0,goodconf='$goodconf',badconf='' WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
}

function delete():bool{

    $ID = $_POST["delete"];
    $q  = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];

    $tables[] = "stream_ports";
    $tables[] = "backends";
    $tables[] = "service_parameters";
    $tables[] = "ngx_stream_access_module";
    $tables[] = "ngx_directories";
    $tables[] = "ngx_subdir_items";
    $tables[] = "httrack_sites";
    $tables[] = "modsecurity_whitelist";
    $tables[] = "directories_backends";

    foreach ($tables as $tablename){
        if(!$q->TABLE_EXISTS($tablename)) {continue;}
         $q->QUERY_SQL("DELETE FROM $tablename WHERE serviceid='$ID'");
    }

    $q->QUERY_SQL("DELETE FROM nginx_services WHERE ID='$ID'");

    if($q->TABLE_EXISTS("mod_security_rules")) {
        $results = $q->QUERY_SQL("SELECT ID FROM mod_security_rules WHERE siteid='$ID'");
        foreach ($results as $index => $ligne) {
            $ruleid = $ligne["ruleid"];
            if ($q->TABLE_EXISTS("mod_security_patterns")) {
                $q->QUERY_SQL("DELETE FROM mod_security_patterns WHERE ruleid=$ruleid");
            }
            if ($q->TABLE_EXISTS("mod_security_rules")) {
                $q->QUERY_SQL("DELETE FROM mod_security_rules WHERE ID=$ruleid");
            }
        }
    }


    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/delete/$ID");
    admin_tracks("Removed reverse-proxy service $servicename");
    return true;
}

function action_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $service_reconfigure_all="Loadjs('$page?reconfigure-all-js=yes');";
    $function=$_GET["function"];
    $DisableBuildNginxConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBuildNginxConfig"));
    $html[]="<table style='width:100%'>";
    $TRCLASS=null;



    if($DisableBuildNginxConfig==0) {
        if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:99%;padding:10px'><H2>{rebuild_all_websites}</H2><p>{rebuild_all_websites_explain}</p></td>";
        $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"$service_reconfigure_all\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
        $html[] = "</tr>";

        $service_reconfigure_restart=$tpl->framework_buildjs("nginx:/reconfigure/restart",
            "reverse-proxy.reconfigure-all.progress","reverse-proxy.reconfigure-all.log",
            "rebuild-all-websites-and-restart");
        if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:99%;padding:10px'>";
        $html[] = "<div id='rebuild-all-websites-and-restart'></div>";
        $html[] = "<H2>{rebuild_all_websites_and_restart}</H2><p>{rebuild_all_websites_and_restart_explain}</p>";
        $html[] = "</td>";
        $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"$service_reconfigure_restart\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
        $html[] = "</tr>";


    }


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{extract_domains}</H2><p>{extract_domains_nginx_explain}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?extract-domains-js=yes&function=$function');\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{import_a_rule}</H2><p>{import_a_rule_nginx_explain}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.nginx.export.php?ID=0&function=$function');\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{export_rules}</H2><p>{export_rules_nginx_explain}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"document.location.href='$page?download-db=yes');\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{import_rules}</H2><p>{import_rules_nginx_explain}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.nginx.importdb.php?function=$function');\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{disable_all}</H2><p>{disable_all_nginx}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?disable-all-js=yes&function=$function')\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{disable_all_web_firewall}</H2><p>{disable_all_web_firewall_explain}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?disable-fw-js=yes&function=$function')\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td style='width:99%;padding:10px'><H2>{enable_all_web_firewall}</H2><p>{enable_all_web_firewall_explain}</p></td>";
    $html[]="<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?enable-fw-js=yes&function=$function')\"><i class='fas fa-arrow-alt-right'></i> {run_task} </label></td>";
    $html[]="</tr>";

    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function download_database():bool{
    $users=new usersMenus();
    if(!$users->AsWebMaster){die();}
    $db=NginxGetDB();
    $size=filesize($db);
    $baseName="database.db";
    header('Content-type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$baseName\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$size);
    ob_clean();
    flush();
    readfile($db);
    return true;
}

function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}

function js_tiny_ping(){
    $uniqid = $_GET["js-tiny-ping"];
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->CLUSTER_CLI = true;
    header("content-type: application/x-javascript");
    echo $tpl->RefreshInterval_Loadjs($uniqid, $page, "jping=$uniqid", 5);
}


function jsping():bool{
    $uniqid=$_GET["jping"];
    $sock=new sockets();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $data=$sock->REST_API_NGINX("/reverse-proxy/service/tinystatus");
    $json=json_decode($data);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    if (json_last_error()> JSON_ERROR_NONE) {
       return false;
    }

    if($json->Status){
        $json->ActiveConnections=$tpl->FormatNumber($json->ActiveConnections);
        $json->ActiveRequests=$tpl->FormatNumber($json->ActiveRequests);
        $f[]="if (document.getElementById('$uniqid') ){";
        $title=base64_encode($tpl->_ENGINE_parse_body("{web_services} <small style='font-size: 16px'>({running_since} $json->Uptime)<br> $json->ActiveConnections {connections} $json->ActiveRequests {requests}</small>"));
        $f[]="\t\tdocument.getElementById('$uniqid').innerHTML=base64_decode('$title');";
        $f[]="\t$('#tiny-ico').removeClass('text-danger');";
        $f[]="}";
        echo @implode("\n",$f);
    }else{
        $f[]="if (document.getElementById('$uniqid') ){";
        $f[] = "$('#tiny-ico').addClass('text-danger');";
        $title=base64_encode($tpl->_ENGINE_parse_body("<span class='text-danger'>{APP_NGINX} {stopped}</span>"));
        $f[]="\tdocument.getElementById('$uniqid').innerHTML=base64_decode('$title');";
        $f[]="}";
        echo @implode("\n",$f);

    }

    echo "
    function jping(){
      const ids = [];
      document.querySelectorAll('span[id^=\"status-\"]').forEach(span => {
            const id = span.id;
            const match = id.match(/^status-(\d+)$/);
            if (match) {
                ids.push(match[1]);
            }
        });
      const commaList = ids.join(',');
      Loadjs('$page?rows-ping=' + commaList);
    }
    
    jping();";

    return true;
}

function js_tiny():bool{
    if($GLOBALS["VERBOSE"]){echo __LINE__."\n";}
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $function=$_GET["function"];
    $PermsButton="AsWebMaster";
    $DisableBuildNginxConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBuildNginxConfig"));
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    if(isset($_GET["harmpid"])){
        if(intval($_GET["harmpid"])>0){
            $_SESSION["HARMPID"]=$_GET["harmpid"];
        }
    }


    $LICJSON=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/count/license"));

    if (json_last_error()== JSON_ERROR_NONE) {
        if(!$LICJSON->Status){
            $PermsButton="FalseLicense";
        }
    }

    $addplus="";


    if($DisableBuildNginxConfig==0) {
        if($PowerDNSEnableClusterSlave==0) {
            $topbuttons[] = array("Loadjs('$page?new-www-js=yes');", ico_plus, "{new_service}", $PermsButton);
        }
    }else{
        $topbuttons[] = array("Loadjs('$page?locked-config-js=yes&function=$function');", ico_lock, "{locked_configuration}", $PermsButton);

    }

    $service_restart=$tpl->framework_buildjs("nginx:/reverse-proxy/restarthup",
        "nginx.restart.progress","nginx.restart.progress.txt",
        "progress-websites-restart","$function();document.getElementById('progress-websites-restart').innerHTML='';",null,null,$PermsButton);

    $topbuttons[] = array($service_restart, ico_refresh, "{restart_service}",$PermsButton);
    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('fw.nginx.templates.php?function=$function')", ico_clone, "{templates}", $PermsButton);
    }
    $topbuttons[] = array("Loadjs('fw.nginx.backups.php?function=$function')", ico_file_zip, "{backups}",$PermsButton);
    $topbuttons[] = array("Loadjs('fw.nginx.license.php')", ico_certificate, "{license}");
    $topbuttons[] = array("Loadjs('$page?action-js=yes&function=$function');", "fas fas fa-cogs", "{actions}");
    $topbuttons[] = array("s_PopUp('https://wiki.articatech.com/en/reverse-proxy','1024','800');", ico_support, "WIKI");


    if(!isHarmpID()) {
        $sock=new sockets();
        $data=$sock->REST_API("/reverse-proxy/status");
        $json=json_decode($data);
        $addplus="";

        if(!$json->Status){
            $addplus = "text-danger ";
            $titleadd = "&nbsp;&nbsp;-&nbsp;&nbsp;<span class='text-danger'>{service_stopped}!</span>";
        }//else{
            //$titleadd="&nbsp;<small style='font-size: 16px'>({running_since} $json->Uptime)</small>";
        //}

    }
    if(isHarmpID()) {
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $ligne = $q->mysqli_fetch_array("SELECT * FROM groups WHERE ID='{$_SESSION["HARMPID"]}'");
        $groupname       = $ligne["groupname"];
        $titleadd = "&nbsp;&raquo;&nbsp;$groupname";
    }

    if($GLOBALS["VERBOSE"]){echo __LINE__."\n";}

    $about2="";
    if(!$LICJSON->Status){
        $about2="<br><strong class='text-danger'>{license_error} $LICJSON->Error</strong>";
    }

//$bts[]=
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $jsping="jsping".time();
    header("content-type: application/x-javascript");
    $TINY_ARRAY["TITLE"]="{web_services}";
    $TINY_ARRAY["ICO"]="{$addplus}fa fa-globe-africa";
    $TINY_ARRAY["EXPL"]="{about_nginx_services}: $about2";
    $TINY_ARRAY["BUTTONS"]="<span id='$jsping'></span>".$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["JSAFTER"]="Loadjs('$page?js-tiny-ping=%s')";



    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    return true;

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(isset($_GET["harmpid"])){
        if(intval($_GET["harmpid"])>0){
            $_SESSION["HARMPID"]=$_GET["harmpid"];
        }
    }
     $html=$tpl->page_header("{web_services}","fa fa-globe-africa","{about_nginx_services}","$page?table-form=yes",
        "websites","progress-websites-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{websites}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function new_www_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    return $tpl->js_dialog1("{new_service}", "$page?new-www=yes");
}
function compile_js():bool{
    $tpl    =  new template_admin();$tpl->CLUSTER_CLI=true;
    $ID     = $_GET["compile"];
    $q=new lib_sqlite(NginxGetDB());
    $function=$_GET["function"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $js=compile_js_progress($ID,"progress-websites-restart");
    if(isset($_GET["forcediv"])){
        $js=compile_js_progress($ID,$_GET["forcediv"]);
    }

    $type=$ligne["type"];
    if($type==2){
        $js="$function()";
    }
    if($type==5){
        $js="$function()";
    }

    return $tpl->js_confirm_execute("{apply_parameters_to_the_system}:.....$servicename", "compile-confirm", $ID,$js);
}
function compile_firewall():bool{
    $page   = CurrentPageName();
    $tpl    =  new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["compile-firewall"];
    $q=new lib_sqlite(NginxGetDB());

    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.nginx.apply.php?serviceid=$ID');\n";
    return admin_tracks("Web Application firewall rules compiled for $servicename");
}

function compile_confirm():bool{
    $ID=$_POST["compile-confirm"];
    $sock=new sockets();
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $type=$ligne["type"];
    if( ($type==2) OR ($type==5) ) {
        $data=$sock->REST_API_NGINX("/reverse-proxy/single/$ID");
        $json=json_decode($data);
        if (json_last_error()> JSON_ERROR_NONE) {
            echo "Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg();
            return false;
        }
        if(!$json->Status){
            echo $json->Error;
            return false;
        }
    }

    admin_tracks("Compiling web service $servicename Type:$type");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    return true;
}

function compile_js_progress($ID,$sdiv=null):string{
    $addjs="";
    if(isset($_GET["addjs"])){
        $addjs=base64_decode($_GET["addjs"]);
    }
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=$addjs');";
}


function action_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $function=$_GET["function"];
    $tpl->js_dialog1("{actions}", "$page?action-popup=yes&function=$function",850);
}



function www_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog1("#$ID - $servicename", "$page?www-tabs=$ID",1200);
}
function backend_error_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["backend-error-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog2("#$ID - $servicename {error}", "$page?backend-error-popup=$ID",850);
}
function www_parameters_section_js($section):bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-parameters-$section-js"]);
    $servicename=get_servicename($ID);
    $CertCenter="";
    if(isset($_GET["CertCenter"])){$CertCenter=$_GET["CertCenter"];}
    $addon="";
    if ($section=="ssl"){
        $addon="- {ssl_protocols}";
    }
    return $tpl->js_dialog2("#$ID - $servicename$addon", "$page?www-parameters-$section-popup=$ID&CertCenter=$CertCenter");
}
function www_parameters_vitrification_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-parameters-vitrification-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog2("#$ID - $servicename", "$page?www-parameters-vitrification-popup=$ID");
}
function www_parameters_JsBC_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["JsBC-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog2("#$ID - $servicename", "$page?JsBC-popup=$ID");
}

function www_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["www-tabs"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT `type` FROM nginx_services WHERE ID=$ID");
    $type=intval($ligne["type"]);
    $Limited[14]=true;
    $icoParam=ico_params;
    $ico_hearth=ico_earth;
    $ico_net=ico_nic;
    $ico_fw=ico_firewall;
    $ico_folder=ico_folder;
    $ico_ar=ico_arrow_right;
    $ico_htm=ico_html;
    $head_backends="<i class='$ico_ar'></i> {backends}";

    $array["<i class='$icoParam'></i> {general_settings}"]="$page?www-parameters=$ID";
    if(!isset($Limited[$type])) {
        $array["<i class='$ico_hearth'></i> {servernames}"] = "$page?www-hosts=$ID";
        $array["<i class='$ico_net'></i> {ports}"] = "fw.nginx.ports.php?service=$ID";
        $array["<i class='$ico_fw'></i> {access_rules}"] = "fw.nginx.ngx_stream_access_module.php?service=$ID";
    }

    if($type==2){
        $array["<i class='$ico_folder'></i> {paths}"]="fw.nginx.directories.php?service=$ID";
        $array[$head_backends]="fw.nginx.backends.php?service=$ID";
        $array["<i class='$icoParam'></i> {options}"]="fw.nginx.reverse-options.php?service=$ID";

    }

    if($type==13){
        $array[$head_backends]="fw.nginx.backends.php?service=$ID";
    }
    if($type==15){
        $array[$head_backends]="fw.nginx.backends.php?service=$ID";
    }

    if($type==5){
        unset($array["<i class='$ico_hearth'></i> {servernames}"]);
        unset($array["<i class='$ico_net'></i> {ports}"]);
        $array["<i class='$ico_net'></i> {listen_address_and_port}"]="fw.nginx.stream.ports.php?service=$ID";
        $array[$head_backends]="fw.nginx.backends.php?service=$ID";
    }

    if($type==7){
        $array["{APP_DOH_SERVER}"]="$page?doh-params=$ID";

    }

    if($type==9){
        $array["<i class='$ico_htm'></i> {website_content}"]="fw.nginx.site.content.php?ID=$ID";
    }

    echo $tpl->tabs_default($array);
}

function doh_parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["doh-params"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $sockngix=new socksngix($ID);

    $doh_subfolder=$sockngix->GET_INFO("doh_subfolder");
    if($doh_subfolder==null){$doh_subfolder="dns-query";}

    $tpl->field_hidden("doh-params",$ID);
    $form[]=$tpl->field_text("doh_subfolder","{doh_subfolder}",$doh_subfolder);

    echo $tpl->form_outside("$servicename <small>({DOH_WEB_SERVICE})</small>", $form,"{CREATE_DOH_WEB_SERVICE_SERVICE} ({$ligne["type"]})","{apply}","NgixSitesReload();","AsSystemWebMaster");

}
function doh_parameters_save(){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $ID=$_POST["doh-params"];
    $sockngix=new socksngix($ID);
    $sockngix->SET_INFO("doh_subfolder",$_POST["doh_subfolder"]);
}
function www_parameters():bool{
    $page                       = CurrentPageName();
    $ID                         = $_GET["www-parameters"];

    echo "<div id='www-parameters-$ID'></div>
    <script>LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');</script>";
    return true;
}




function get_ServiceType($ID):int{
    $ID=intval($ID);
    if($ID==0){return 0;}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT type FROM nginx_services WHERE ID=$ID");
    return intval($ligne["type"]);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}




function www_parameters_isProxy($ID):bool{
    $type=get_ServiceType($ID);
    if($type==2 OR $type==5  OR $type==15){
        return true;
    }
    return false;
}
function www_parameters_general_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["www-parameters-general-popup"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $sockngix                   = new socksngix($ID);


    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=get_servicename($ID);
    $DenyAccess=$sockngix->GET_INFO("DenyAccess");
    $RedirectQueries=trim($sockngix->GET_INFO("RedirectQueries"));
    $debug=intval($sockngix->GET_INFO("Debug"));
    $ASPROXY=www_parameters_isProxy($ID);
    $WebSocketsSupport  = trim($sockngix->GET_INFO("WebSocketsSupport"));

    $DENYALL=false;
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("ztype", $ligne["type"]);
    $form[]=$tpl->field_text("servicename", "{service_name2}", "$servicename",true);

    if($ligne["type"]==14){
        $ligne["isDefault"]=1;
        $DENYALL=true;
    }

    $form[]=$tpl->field_checkbox("isDefault","{default_server}",$ligne["isDefault"],false,"{nginx_default_server_explain}",$DENYALL);
    $form[]=$tpl->field_checkbox("WebSocketsSupport","{websockets_support}",$WebSocketsSupport,false,null,$DENYALL);
    $form[]=$tpl->field_checkbox("DenyAccess","{deny_access}",$DenyAccess,false,null,$DENYALL);
    $form[]=$tpl->field_checkbox("Debug","{debug}",$debug,false,null,$DENYALL);

    if($ASPROXY) {
        if (!isHarmpID()) {
            $form[] = $tpl->field_interfaces("proxy_bind", "{outgoing_interface}", $sockngix->GET_INFO("proxy_bind"));
        }
    }
    if($ASPROXY) {
        $form[] = $tpl->field_text("RedirectQueries", "{RedirectQueries}", $RedirectQueries, false, "url:https://wiki.articatech.com/en/reverse-proxy/port-redirects;{RedirectQueries_explain}", $DENYALL);
    }

    $form[]=$tpl->field_section("{limits}");
    $form[]=$tpl->field_numeric("limit_conn","{limit_connections_ip}",intval($sockngix->GET_INFO("limit_conn")),null,null,$DENYALL);

    if($ASPROXY) {
        $proxy_download_rate=intval($sockngix->GET_INFO("proxy_download_rate"));
        $form[]=$tpl->field_numeric("proxy_download_rate","{max_hard_download_rate} (kb/s)",$proxy_download_rate,"{ngx_bandwidth_explain}",$DENYALL);
            $form[]=$tpl->field_numeric("proxy_upload_rate","{max_hard_upload_rate} (kb/s)",$proxy_download_rate,"{ngx_bandwidth_explain}",null,true);
    }
    $NgxSysguardDeny=false;
    $NgxSysguardModule = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NgxSysguardModule");
    if($NgxSysguardModule==0){$NgxSysguardDeny=true;}
    $sysguard=intval($sockngix->GET_INFO("sysguard"));
    $sysguardLoad=intval($sockngix->GET_INFO("sysguardLoad"));
    $sysguardSwap=intval($sockngix->GET_INFO("sysguardSwap"));
    $sysguardMem=intval($sockngix->GET_INFO("sysguardMem"));
    if($sysguardLoad==0){$sysguardLoad=5;}
    if($sysguardSwap==0){$sysguardSwap=50;}
    if($sysguardMem==0){$sysguardMem=500;}
    $form[]=$tpl->field_checkbox("sysguard","{exceed_os_capacities}",$sysguard,"sysguardLoad,sysguardSwap,sysguardMem",null,$NgxSysguardDeny);
    $form[]=$tpl->field_numeric("sysguardLoad","{MaxLoadAvg}",$sysguardLoad,null,null,$NgxSysguardDeny);
    $form[]=$tpl->field_numeric("sysguardSwap","{swap} (%)",$sysguardSwap,null,null,$NgxSysguardDeny);
    $form[]=$tpl->field_numeric("sysguardMem","{free_mem_less} (MB)",$sysguardMem,null,null,$NgxSysguardDeny);




    echo $tpl->form_outside(null, $form,null,"{apply}",www_parameters_reload($ID),"AsSystemWebMaster");
    return true;

}
function www_parameters_security_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["www-parameters-security-popup"]);

    $q                          = new lib_sqlite(NginxGetDB());
    $users                      = new usersMenus();
    $sockngix                   = new socksngix($ID);
    $ASPROXY                    = www_parameters_isProxy($ID);
    $hts_enabled                = intval($sockngix->GET_INFO("hts_enabled"));
    $EnableCrowdSec             = intval($sockngix->GET_INFO("EnableCrowdSec"));
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");

    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("ztype", $ligne["type"]);


    if($ASPROXY){
        $RestrictIFrames=intval($sockngix->GET_INFO("RestrictIFrames"));
        $XSSBrowser=intval($sockngix->GET_INFO("XSSBrowser"));
        $ReferrerPolicy=intval($sockngix->GET_INFO("ReferrerPolicy"));



        $form[]=$tpl->field_checkbox("RestrictIFrames", "{RestrictIFrames}", $RestrictIFrames,false,"{RestrictIFrames_text}");
        $form[]=$tpl->field_checkbox("XSSBrowser", "{XSS_FILTERB}", $XSSBrowser,false,"{XSS_FILTERB_TEXT}");
        $form[]=$tpl->field_checkbox("ReferrerPolicy", "{ReferrerPolicy}", $ReferrerPolicy,false,"{ReferrerPolicy_explain}");
    }

    $form[]=$tpl->field_checkbox("hts_enabled","HTTP Strict Transport Security (HSTS)",$hts_enabled,false,"{AllowSquidHSTS_explain}");

    $servicename=get_servicename($ID);
    echo $tpl->form_outside("$servicename", $form,null,"{apply}",www_parameters_reload($ID),"AsSystemWebMaster");
    return true;

}
function www_parameters_reload($serviceid):string{
    $page=CurrentPageName();
    $CertCenter="";
    if(isset($_GET["CertCenter"])){$CertCenter=$_GET["CertCenter"];}
    $js[]="LoadAjax('www-parameters-$serviceid','$page?www-parameters2=$serviceid')";
    if(strlen($CertCenter)>2){
        $js[]="$CertCenter();";
    }
    $js[]="Loadjs('$page?td-row=$serviceid');";
    $js[]="dialogInstance2.close()";
    return @implode(";",$js);
}
function www_parameters_JsBC_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->CLUSTER_CLI = true;
    $ID                         = intval($_GET["JsBC-popup"]);
    $sockngix                   = new socksngix($ID);
    $JsBC=intval($sockngix->GET_INFO("JsBC"));

    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("JsBC","{enable_feature}",$JsBC);
    $html[]= $tpl->form_outside("", $form,"{signed_js_browser_challenge}|{signed_js_browser_challenge_explain}|fab fa-js","{apply}",www_parameters_reload($ID),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function www_parameters_JsBC_save():bool{
    $ID=$_POST["ID"];
    $JsBC=$_POST["JsBC"];
    $sockngix                   = new socksngix($ID);
    $sockngix->SET_INFO("JsBC",$JsBC);
    $servicename=get_servicename($ID);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Set Signed JavaScript Browser Challenge feature to $JsBC for $servicename");
}

function www_parameters_vitrification_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["www-parameters-vitrification-popup"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT vitrification FROM nginx_services WHERE ID=$ID");
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("vitrification","{enable_feature}",$ligne["vitrification"]);

    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'>";
    $html[]="<div id='vitrification-status-$ID'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;vertical-align: top'>";
    $html[]= $tpl->form_outside("", $form,"{vitrification}|{vitrification_explain}|fas fa-wine-glass","{apply}",www_parameters_reload($ID),"AsSystemWebMaster");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('vitrification-status-$ID','$page?www-parameters-vitrification-status=$ID');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function www_parameters_vitrification_switch():bool{
    $ID                         = intval($_GET["www-parameters-vitrification-switch"]);
    $sockngix                   = new socksngix($ID);
    $page=CurrentPageName();
    $vitrification=intval($sockngix->GET_INFO("EnableVitrification"));
    VERBOSE("$ID: EnableVitrification=$vitrification",__LINE__);

    if($vitrification==0){
        $vitrification_text="On";
        $vitrification=1;
    }else{
        $vitrification_text="Off";
        $vitrification=0;
    }
    VERBOSE("$ID: EnableVitrification=$vitrification",__LINE__);
    $sname=get_servicename($ID);
    $sockngix->SET_INFO("EnableVitrification",$vitrification);
    header("content-type: application/x-javascript");
    echo "LoadAjax('vitrification-status-$ID','$page?www-parameters-vitrification-status=$ID');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Turn vitrification to $vitrification_text for service $ID $sname");

}
function www_parameters_vitrification_status():bool{
    $page                       = CurrentPageName();
    $ID=intval($_GET["www-parameters-vitrification-status"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT vitrification FROM nginx_services WHERE ID=$ID");
    if($ligne["vitrification"]==0) {
        echo $tpl->widget_grey("{vitrification}", "{disabled}", null, "fas fas fa-times-circle");
        return true;
    }


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/status/$ID"));
    if(!$json->Status){
        echo $tpl->widget_rouge("{vitrification}",$json->Error,null,"fas fa-hourglass-start",true);
        return true;
    }


    $hosts=array();
    foreach ($json->Info->Hosts as $domain=>$class){
        $size=FormatBytes($class->packageSize);
        $hosts[] = "$domain ($size)";

    }
    if(count($hosts)==0){
        echo $tpl->widget_grey("{vitrification}","{wait_package_cloud}",null,"fas fa-hourglass-start");
        return true;
    }
    $nginx=new socksngix($ID);
    if(intval($nginx->GET_INFO("EnableVitrification")==0)){
        $btn[]=array("name"=>"{activate}","js"=>"Loadjs('$page?www-parameters-vitrification-switch=$ID')","icon"=>"fad fa-badge-check","color"=>null);

        $js=$tpl->framework_buildjs("nginx:/vitrification/run/$ID",
            "vitrification.$ID.run",
            "vitrification.$ID.run.log",
            "vitrification-$ID-run"
        );

        $btn[]=array("name"=>"{run_vitrification}","js"=>$js,"icon"=>ico_run,"color"=>null);

        echo $tpl->widget_grey("{vitrification}","{disabled}",$btn,
            "fas far fa-wine-glass");
        echo "<div id='vitrification-$ID-run' style='margin-top:10px'></div>";
        return true;
    }
// <i class="fas fa-times-circle"></i>
//<i class="fas fa-wine-glass"></i>




    $btn[]=array("name"=>"{disable}","js"=>"Loadjs('$page?www-parameters-vitrification-switch=$ID')","icon"=>"fas fa-times-circle","color"=>null);



    echo $tpl->widget_vert("{vitrification}","{active2}",$btn,"fas fa-wine-glass");
    return true;

}
function www_parameters_vitrification_save():bool{
    $page                       = CurrentPageName();
    $q                          = new lib_sqlite(NginxGetDB());
    $ID=$_POST["ID"];
    $vitrification=$_POST["vitrification"];
    $q->QUERY_SQL("UPDATE nginx_services SET vitrification=$vitrification WHERE ID=$ID");
    $servicename=get_servicename($ID);
    $sock=new sockets();
    $sock->REST_API_NGINX("/push/cloud");
    return admin_tracks("Set vitrification feature to $vitrification for $servicename");

}
function www_parameters_ssl_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["www-parameters-ssl-popup"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $sockngix                   = new socksngix($ID);


    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");

    $ssl_protocols=$sockngix->GET_INFO("ssl_protocols");
    $ssl_ciphers=$sockngix->GET_INFO("ssl_ciphers");
    $SslStapling=intval($sockngix->GET_INFO("SslStapling"));
    $SslStaplingVerifies=intval($sockngix->GET_INFO("SslStaplingVerifies"));

    $ssl_prefer_server_ciphers=intval($sockngix->GET_INFO("ssl_prefer_server_ciphers"));
    $ssl_buffer_size=intval($sockngix->GET_INFO("ssl_buffer_size"));
    if($ssl_buffer_size==0){$ssl_buffer_size=16;}
    $ssl_certificate=$sockngix->GET_INFO("ssl_certificate");


    if($ssl_protocols==null){$ssl_protocols="TLSv1.2 TLSv1.3";}
    if($ssl_ciphers==null){$ssl_ciphers="ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256";}


    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("ztype", $ligne["type"]);
    $form[]=$tpl->field_checkbox("Redirect80To443","{Redirect80To443}",intval($sockngix->GET_INFO("Redirect80To443")),false);

    $form[]=$tpl->field_certificate("ssl_certificate", "{certificate}",$ssl_certificate);
    $form[]=$tpl->field_numeric("ssl_buffer_size","{ssl_buffer_size} (k)",$ssl_buffer_size,"{ssl_buffer_size_text}");

    $ssl_protocols_a=explode(" ",$ssl_protocols);
    foreach ($ssl_protocols_a as $pproto){
        $MyProtos[strtolower($pproto)]=true;
    }

    $protos=explode(" ","TLSv1 TLSv1.1 TLSv1.2 TLSv1.3");
    foreach ($protos as $pproto){
        $pproto_low=strtolower($pproto);
        $c=0;
        if(isset($MyProtos[$pproto_low])){
            $c++;
            $form[]=$tpl->field_checkbox("pproto_$pproto",$pproto,1);
        }else{
            $form[]=$tpl->field_checkbox("pproto_$pproto",$pproto,0);
        }

    }
    $ssl_ciphers_array=explode(":",$ssl_ciphers);

    $form[]=$tpl->field_info("ssl_ciphers_button", " {ssl_ciphers}",

        array("VALUE"=>null,
            "BUTTON"=>true,
            "BUTTON_CAPTION"=>count($ssl_ciphers_array)." {ssl_ciphers}",
            "BUTTON_JS"=>"Loadjs('fw.nginx.ciphers.php?service-js=$ID')"

        ),null);


    $form[]=$tpl->field_checkbox("ssl_prefer_server_ciphers","{ssl_prefer_server_ciphers}",$ssl_prefer_server_ciphers,false,"{ssl_prefer_server_ciphers_explain}");

    $form[]=$tpl->field_checkbox("SslStapling","OCSP Stapling",$SslStapling,false,"{SslStapling_explain}");

    $form[]=$tpl->field_checkbox("SslStaplingVerifies","{ssl_stapling_verify}",$SslStaplingVerifies,false,"{ssl_stapling_verify_explain}");



    echo $tpl->form_outside("", $form,null,"{apply}",www_parameters_reload($ID),"AsSystemWebMaster");
    return true;

}
function nginx_pagespeed_enabled():int{
    if(isHarmpID()) {return 1;}
    $nginx_pagespeed_installed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
    if($nginx_pagespeed_installed==0){return 0;}
    return  intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));
}
function www_parameters2_BotChecker_save():bool{
    $ID=intval($_GET["BotChecker-js"]);
    $page=CurrentPageName();
    $socknginx=new socksngix($ID);
    $Enabled=intval($socknginx->GET_INFO("BotChecker"));
    if($Enabled==0){
        $Enabled=1;
    }else{
        $Enabled=0;
    }
    $socknginx->SET_INFO("BotChecker",$Enabled);
    $servicename=get_servicename($ID);
    header("content-type: application/x-javascript");
    echo "LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Set reverse-proxy Botnets checking to $Enabled for $servicename");
}
function www_parameters2_auditFrontend_save():bool{
    $q=new lib_sqlite(NginxGetDB());
    $ID=intval($_GET["monitored-frontend"]);
    $ligne=$q->mysqli_fetch_array("SELECT servicename,monitored FROM nginx_services WHERE ID=$ID");
    $monitored=intval($ligne["monitored"]);
    $servicename=$ligne["servicename"];
    if($monitored==0){
        $monitored=1;
    }else{
        $monitored=0;
    }

    $q->QUERY_SQL("UPDATE nginx_services SET monitored=$monitored WHERE ID=$ID");
    if(!$q->ok){
        $tpl=new template_admin();
        return $tpl->js_error($q->mysql_error);
    }
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');";
    if($monitored==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sla/frontend/remove/$ID");
    }
    return admin_tracks("Set reverse-proxy Cloud monitoring to $monitored for $servicename");
}
function www_parameters2_SignedJSBC($tpl,$ID){
    $sockngix=new socksngix($ID);
    $page=CurrentPageName();
    $JsBC=intval($sockngix->GET_INFO("JsBC"));
    $js="Loadjs('$page?JsBC-js=$ID');";
    $tpl->table_form_field_js($js,"AsWebMaster");
    $tpl->table_form_field_bool("{signed_js_browser_challenge}",$JsBC, "fab fa-js");
    return $tpl;
}
function www_parameters2_BotNetsEngine($tpl,$ID){
    $sockngix=new socksngix($ID);
    $page=CurrentPageName();
    $BotChecker=intval($sockngix->GET_INFO("BotChecker"));
    $js="Loadjs('$page?BotChecker-js=$ID');";
    $tpl->table_form_field_js($js,"AsWebMaster");
    $tpl->table_form_field_bool("{BotChecker}",$BotChecker, "fa-regular fa-user-robot");
    return $tpl;
}


function www_parameters2_auditFrontend($tpl,$ID){
    $q=new lib_sqlite(NginxGetDB());
    $page=CurrentPageName();
    $NginxDisableFrontEndSLA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxDisableFrontEndSLA"));
    if($NginxDisableFrontEndSLA==1){
        $tpl->table_form_field_js("","AsWebMaster");
        $tpl->table_form_field_bool("{audited_artica_cloud}",0, ico_list);
        return $tpl;
    }

    $ligne=$q->mysqli_fetch_array("SELECT monitored FROM nginx_services WHERE ID=$ID");
    $monitored=intval($ligne["monitored"]);
    $js="Loadjs('$page?monitored-frontend=$ID');";
    $tpl->table_form_field_js($js,"AsWebMaster");
    $tpl->table_form_field_bool("{audited_artica_cloud}",$monitored, ico_list);
    return $tpl;
}
function www_parameters2_isSSL($tpl,$ID){

    $q=new lib_sqlite(NginxGetDB());
    $isSSL=false;
    // On check les ports.
    $results=$q->QUERY_SQL("SELECT options,port FROM stream_ports WHERE serviceid=$ID");
    if(!$q->ok){
        VERBOSE($q->mysql_error,__LINE__);
    }
    foreach ($results as $index=>$ligne){
        $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
        $port=intval($ligne["port"]);
        if($port==443){
            $options["ssl"]=1;
        }
        if(!isset($options["ssl"])){$options["ssl"]=0;}
        if($options["ssl"]==1){
            $isSSL=true;
            break;
        }
    }
    if(!$isSSL){
        VERBOSE("[$ID]: isSSL=[FALSE] OK",__LINE__);
        return $tpl;
    }
    $sockngix = new socksngix($ID);
    $ssl_certificate = $sockngix->GET_INFO("ssl_certificate");
    if(strlen($ssl_certificate)>2){
        VERBOSE("[$ID}: ssl_certificate=[$ssl_certificate] OK",__LINE__);
        return $tpl;
    }
    $page=CurrentPageName();
    $js="Loadjs('$page?create-self-signed=$ID');";
    $tpl->table_form_field_js($js,"AsWebMaster");
    $tpl->table_form_field_button("<span class='text-danger'>{missing} {certificate}</span>","{create_a_sef_signed_certificate}",ico_certificate);
    return $tpl;
}
function create_self_signed():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["create-self-signed"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/selfsigned/$serviceid"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('www-parameters-$serviceid','$page?www-parameters2=$serviceid');";
    return true;
}
function www_parameters2_vitrification($tpl,$ID){
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT vitrification FROM nginx_services WHERE ID=$ID");
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?www-parameters-vitrification-js=$ID')");
    if(intval($ligne["vitrification"])==0){
        $tpl->table_form_field_bool("{vitrification}",0,"fas fa-wine-glass");
        return $tpl;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/status/$ID"));
    if(!$json->Status){
        $tpl->table_form_field_text("{vitrification}",$json->Error,"fas fa-hourglass-start",true);
        return $tpl;
    }


    $hosts=array();
    foreach ($json->Info->Hosts as $domain=>$class){
        $size=FormatBytes($class->packageSize);
        $hosts[]="$domain ($size)";
    }



    if(count($hosts)==0){
        $tpl->table_form_field_text("{vitrification}","{wait_package_cloud}","fas fa-hourglass-start");
        return $tpl;
    }
    $sockngix = new socksngix($ID);
    $EnableVitrification=intval($sockngix->GET_INFO("EnableVitrification"));
    if($EnableVitrification==0){
        $hosts[]="<span class='label label-default'>{inactive2}</span>";
    }else{
        $hosts[]="<span class='label label-primary'>{active2}</span>";
    }

    $tpl->table_form_field_text("{vitrification}","<small style='text-transform: none'>".@implode(", ",$hosts)." </small>","fas fa-wine-glass");
    return $tpl;
}

function www_parameters2_waf($tpl,$ID){

    $socknginx=new socksngix($ID);
    $NginxHTTPModSecurity=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    VERBOSE("NginxHTTPModSecurity=$NginxHTTPModSecurity",__LINE__);
    if ($NginxHTTPModSecurity == 0) {
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{WAF_LONG}", "{not_compiled}", ico_shield_disabled);
        return $tpl;
    }
    $EnableModSecurityIngix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    VERBOSE("EnableModSecurityIngix = $EnableModSecurityIngix",__LINE__);
    if ($EnableModSecurityIngix == 0) {
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{WAF_LONG}", "{globally_disabled}", ico_shield_disabled);
        return $tpl;
    }

    $tpl->table_form_field_js("Loadjs('fw.nginx.sites.modsecurity.php?serviceid=$ID')");
    $EnableModSecurity = intval($socknginx->GET_INFO("EnableModSecurity"));
    VERBOSE("EnableModSecurity($ID)=$EnableModSecurity",__LINE__);
    if ($EnableModSecurity == 0) {
        $tpl->table_form_field_text("{WAF_LONG}", "{inactive2}", ico_shield_disabled);
        return $tpl;
    }
    $ModSecurityAction=GetModSecurityAction($ID);
    $tpl->table_form_field_text("{WAF_LONG}", "{active2}/".sModSecurityAction[$ModSecurityAction], ico_shield);
    return $tpl;

}
function www_parameters2():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["www-parameters2"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $sockngix                   = new socksngix($ID);
    $ASPROXY                    = www_parameters_isProxy($ID);
    $GenericHarden              = intval($sockngix->GET_INFO("GenericHarden"));
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }

    if(!$q->FIELD_EXISTS("nginx_services", "isDefault")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD isDefault INTEGER DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nginx_services", "HamrpSaved")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD HamrpSaved INTEGER NOT NULL DEFAULT 0");
    }

    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=get_servicename($ID);
    $type=$ligne["type"];


    $exTypes[1]="{php_website_explain}";
    $exTypes[2]="{artica_reverse_sites_explain}";
    $exTypes[3]="{hotspot_website_explain}";
    $exTypes[4]="{artica_website_explain}";
    $exTypes[5]="{nginx_stream_explain}";
    $exTypes[6]="{CREATE_WEBFILTERING_ERROR_SERVICE}";
    $exTypes[7]="{CREATE_DOH_WEB_SERVICE_SERVICE}";
    $exTypes[8]="{CREATE_PROXY_PAC_SERVICE}";
    $exTypes[9]="{CREATE_WEB_HTML_SERVICE}";
    $exTypes[10]="{CREATE_ITCHARTER_SERVICE}";
    $exTypes[11]="{CREATE_APTMIRROR_SERVICE}";
    $exTypes[12]="{CREATE_WEBCOPY_SERVICE}";
    $exTypes[13]="{CREATE_ADFS_SERVICE}";
    $exTypes[15]="{gateway} DoH";


    $Types[1]="{website}";
    $Types[2]="{reverse_proxy}";
    $Types[3]="{HOTSPOT_WWW}";
    $Types[4]="{ARTICA_ADM}";
    $Types[5]="{TCP_FORWARD}";
    $Types[6]="{WEBFILTERING_ERROR_SERVICE}";
    $Types[7]="{DOH_WEB_SERVICE}";
    $Types[8]="{PROXY_PAC_SERVICE}";
    $Types[9]="{WEB_HTML_SERVICE}";
    $Types[10]="{IT_charter}";
    $Types[11]="{APP_APT_MIRROR_WEB}";
    $Types[12]="{CREATE_WEBCOPY_SERVICE}";
    $Types[13]="ADFS 3.0";
    $Types[14]="{default} {deny}";
    $Types[15]="{DOH_WEB_SERVICE}";
    $Types[16]="{artica_meta_server}";

    $TypesNoSLSA[15]=true;
    $TypesNoSLSA[14]=true;
    $TypesNoSLSA[12]=true;
    $TypesNoSLSA[11]=true;
    $TypesNoSLSA[9]=true;
    $TypesNoSLSA[8]=true;
    $TypesNoSLSA[7]=true;
    $TypesNoSLSA[4]=true;
    $TypesNoSLSA[3]=true;
    $TypesNoSLSA[1]=true;
    $TypesNoSLSA[5]=true;

    $Sla=true;

    if(isset($TypesNoSLSA[$type])){
        $Sla=false;
    }
    $TCP_STREAM=false;
    $nginx_pagespeed_enabled=nginx_pagespeed_enabled();
    $RedirectQueries=trim($sockngix->GET_INFO("RedirectQueries"));
    $debug=intval($sockngix->GET_INFO("Debug"));
    $pagespeed=intval($sockngix->GET_INFO("pagespeed"));
    $WebCopyID=intval($sockngix->GET_INFO("WebCopyID"));
    $ico_fleche=ico_arrow_right;
    $gzip               = intval($sockngix->GET_INFO("gzip"));
    $cgicache           = intval($sockngix->GET_INFO("cgicache"));
    $EnableCSP=intval($sockngix->GET_INFO("EnableCSP"));
    $EnableCSPText="{active2}";
    $ico_shield=ico_shield;
    $CONTINUE_DETAILS=true;

    if(strlen($RedirectQueries)>3){
        VERBOSE("RedirectQueries=[$RedirectQueries]",__LINE__);
        list($block,$query)=check_redirect_queries($RedirectQueries);
        VERBOSE("RedirectQueries=$block ($query)",__LINE__);
        if($block) {
            $RedirectQueries = $query;
            $CONTINUE_DETAILS = false;
        }else{
            $RedirectQueries=$query;
        }
    }


    $WebSocketsSupport  = trim($sockngix->GET_INFO("WebSocketsSupport"));
    $Noptimize=array();
    $Noptimize[13]=true;
    $Noptimize[14]=true;
    $Noptimize[5]=true;

    $NoLimits[13]=true;
    $NoLimits[14]=true;
    $NoDeny[14]=true;
    $NoSecu[14]=true;
    $noSSL[14]=true;
    $noSSL[5]=true;
    $NoWaf[5]=true;

    $NoLatency[5]=true;

    $error=false;
    $debug_icon="";
    if($debug==1){
        $ico_bug=ico_bug;
        $debug_icon="&nbsp;<span class='text-danger'></span><li class='$ico_bug' style='color:red'></li> {debug}</span>";
        $error=true;
    }


    $names[]=$servicename;
    if($ligne["isDefault"]==1){
        $names[]="<small>{default_server}</small>";
    }
    $FINAL_ERROR=array();
    $names[]="<span style='text-transform: none'>(".$Types[$type].")</span>";
    if(strlen($RedirectQueries)>3) {
        $names[] = $RedirectQueries;
        if(!$CONTINUE_DETAILS){
            $FINAL_ERROR[] = "{redirect_no_opts}";
        }
    }

    if($WebSocketsSupport==1){
        $names[]="{websockets_support}";
    }
    if(strlen($debug_icon)>1) {
        $names[] = $debug_icon;
    }


    $goodconftime=intval($ligne["goodconftime"]);
    $goodconftimeStr=$tpl->time_to_date($goodconftime,true);
    $LowerCase="<span style='text-transform: lowercase'>";
    $goodconftime_text ="<br><small><i>{saved_on} $goodconftimeStr</i></small>";
    $tpl->table_form_field_js("Loadjs('$page?www-parameters-general-js=$ID')");
    $tpl->table_form_field_text("{service_name2}", $LowerCase.implode(", ",$names)."</span>$goodconftime_text",ico_earth,$error);


    $tpl=www_parameters2_vitrification($tpl,$ID);
    $tpl=www_parameters2_auditFrontend($tpl,$ID);

    if(!isset($noSSL[$type])) {
        $tpl = www_parameters2_isSSL($tpl, $ID);
    }

    if($type==13){
        $HostHeader=$sockngix->GET_INFO("HostHeader");
        $XMSProxyHeader=$sockngix->GET_INFO("XMSProxyHeader");
        if(strlen($HostHeader)<2){
            $HostHeader="{default}";
        }
        if(strlen($XMSProxyHeader)<2){
            $XMSProxyHeader="{default}";
        }
        $ensure_redirects="";
        $AdfsForceRedirect=intval($sockngix->GET_INFO("AdfsForceRedirect"));
        if($AdfsForceRedirect==1){
            $ensure_redirects="&nbsp;({ensure_redirects})";
        }
        $tpl->table_form_field_js("Loadjs('fw.nginx.adfs.php?service=$ID')");
        $tpl->table_form_field_text("ADFS {parameters}","<span style='text-transform: lowercase'>$XMSProxyHeader&nbsp;&nbsp;&nbsp;&nbsp;<i class='$ico_fleche'></i>&nbsp;&nbsp;&nbsp;&nbsp;$HostHeader</span>$ensure_redirects",ico_params);


    }
    if(!isset($NoLimits[$type])) {
        $limit_con = intval($sockngix->GET_INFO("limit_conn"));
        $opts = array();
        if ($limit_con > 0) {
            $opts[] = "{limit_connections_ip} $limit_con";
        }
        $proxy_download_rate=intval($sockngix->GET_INFO("proxy_download_rate"));
        if($proxy_download_rate>0){
            $opts[] = "{max_hard_download_rate} $proxy_download_rate KB/s";

        }

        $NgxSysguardModule = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NgxSysguardModule");
        if($NgxSysguardModule==1){
            $sysguard=intval($sockngix->GET_INFO("sysguard"));
            if($sysguard==1){
                $sysguardLoad=intval($sockngix->GET_INFO("sysguardLoad"));
                $sysguardSwap=intval($sockngix->GET_INFO("sysguardSwap"));
                $sysguardMem=intval($sockngix->GET_INFO("sysguardMem"));
                if($sysguardLoad==0){$sysguardLoad=5;}
                if($sysguardSwap==0){$sysguardSwap=50;}
                if($sysguardMem==0){$sysguardMem=500;}
                $sysguard_explain=$tpl->_ENGINE_parse_body("{sysguard_explain}");
                $sysguard_explain=str_replace("%s",$sysguardLoad,$sysguard_explain);
                $sysguard_explain=str_replace("%s%","$sysguardSwap%",$sysguard_explain);
                $sysguard_explain=str_replace("%sM","$sysguardMem%",$sysguard_explain);
                $opts[] ="<small>$sysguard_explain</small>";
            }
        }

        if (count($opts) == 0) {
            $opts[] = "{none}";
        }

        $tpl->table_form_field_text("{limits}", @implode(", ", $opts), ico_timeout);
        if($GLOBALS["DYNAMIC_RATE_FEATURE"]) {
            $tpl->table_form_field_js("Loadjs('fw.nginx.dynamlic-rate.php?service-js=$ID')");
            $tpl->table_form_field_bool("{dynamic_rate_limiting}", 0, ico_timeout);
        }
        
    }
    if($ligne["type"]==12){
        $WebCopyList=array();
        $results=$q->QUERY_SQL("SELECT ID,enforceuri FROM httrack_sites ORDER BY enforceuri");
        foreach ($results as $index=>$WebCopyLine){
            $WebCopyList[$WebCopyLine["ID"]]=$WebCopyLine["enforceuri"];

        }
        $tpl->table_form_field_text("{mirror}",$WebCopyList[$WebCopyID],ico_copy);
    }

    // Not show others settings because there is no sense to continue ( see RedirectQueries for example)
    if(!$CONTINUE_DETAILS){
        $html[]=$tpl->table_form_compile();
        if(count($FINAL_ERROR)>0){
            $html[]=$tpl->div_warning(@implode("<br>",$FINAL_ERROR));
        }
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if($Sla) {
        $tpl->table_form_field_js("Loadjs('fw.nginx.sites.latency.php?serviceid=$ID')");
        $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/latency/urls/$ID"));
        $LatencyUris = $data->Urls;
        $CountOfLatencyUris=1;
        if(is_array($LatencyUris)){
            $CountOfLatencyUris = count($LatencyUris);
        }
        $tpl->table_form_field_text("{latency}", "$CountOfLatencyUris Urls", ico_speed);

    }
    if($nginx_pagespeed_enabled==0){
        $pagespeed=0;
    }

    $OptimizeLevel=0;
    $OptimizeLevelprc=0;
    $proxy_buffering=intval($sockngix->GET_INFO("proxy_buffering"));
    if($proxy_buffering==1){$OptimizeLevel=$OptimizeLevel+1;}
    if($pagespeed==1){$OptimizeLevel=$OptimizeLevel+3;}
    if($gzip==1){$OptimizeLevel++;}
    $OptimizeForLargeFiles=intval($sockngix->GET_INFO("OptimizeForLargeFiles"));


    if($OptimizeForLargeFiles==1){
        $OptimizeLevel=$OptimizeLevel+1;
    }

    if($cgicache==1){
        $nginxsock=new socksngix(0);
        $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
        if($nginxCachesDir==1){
            $OptimizeLevel=$OptimizeLevel+1;
        }

        $OptimizeLevel=$OptimizeLevel+2;}

    if ($OptimizeLevel>0){
        $OptimizeLevelprc=round(($OptimizeLevel/7)*100);
    }

    $optzerror=true;
    if($OptimizeLevelprc>1){
        $optzerror=false;
    }

    $DisableOptimize=false;
    if($ligne["type"]==4){
        $DisableOptimize=true;
    }
    if($ligne["type"]==14){
        $DisableOptimize=true;
    }

    if(!$DisableOptimize) {
        $tpl->table_form_field_js("Loadjs('fw.nginx.sites.optimize.php?serviceid=$ID')");
        if (!isset($Noptimize[$type])) {
            $tpl->table_form_field_text("{optimization}", "{$OptimizeLevelprc}%", ico_speed, $optzerror);
        }
    }

    if(!isset($NoSecu[$type])) {
        $tpl->table_form_section("{security}");

        $tpl=www_parameters2_BotNetsEngine($tpl,$ID);
        $tpl=www_parameters2_SignedJSBC($tpl,$ID);

        if(!isset($NoWaf[$type])) {
            $tpl = www_DenyAccess($ligne, $tpl);
        }
        $tpl = www_fingerprint($ID,$tpl);
        $tpl = www_Reputation($ID, $tpl);


        if(!isset($NoWaf[$type])) {
            $AllowedProtos = trim($sockngix->GET_INFO("ModSecurityProtocols"));
            if (strlen($AllowedProtos) < 3) {
                $AllowedProtos = "POST,GET,HEAD,OPTIONS,PUT";
            }
            $tpl = www_extensions($ligne, $tpl);
            $tpl->table_form_field_js("Loadjs('fw.nginx.sites.AllowedProtos.php?serviceid=$ID')");
            $tpl->table_form_field_text("{protocols}", "{allow} $AllowedProtos", ico_proto);
            $tpl->table_form_field_js("Loadjs('fw.nginx.sites.genericharden.php?serviceid=$ID')");
            $tpl->table_form_field_bool("{generic_hardening}", $GenericHarden, ico_shield_disabled);
        }

        $tpl = www_crowdsec($ID, $tpl);
        if(!isset($NoWaf[$type])) {
            $tpl = www_parameters2_waf($tpl, $ID);
            $tpl = www_hideheaders($ID, $tpl);
        }

        $tpl = www_parameters_countries($ID, $tpl);
        if(!isset($NoWaf[$type])) {
            $tpl = www_parameters_userAgents($ID, $tpl);
            $tpl = www_parameters_uris($ID, $tpl);
            $tpl = www_permissions_policy($ID, $tpl);

            $tpl->table_form_field_js("Loadjs('fw.nginx.rules.csp.php?service-js=$ID')");
            if ($EnableCSP == 0) {
                $ico_shield = ico_shield_disabled;
                $EnableCSPText = "{disabled}";
            }
            $CSP = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("csp_rules"));
            if (!is_array($CSP)) {
                $CSP = array();
            }
            $CountOfRules = count($CSP);
            $array["BUTTON"]["VALUE"] = $EnableCSPText;
            $array["BUTTON"]["LABEL"] = $CountOfRules . " {rules}";
            $array["BUTTON"]["JS"] = "Loadjs('fw.nginx.rules.csp.php?service-js=$ID')";
            $tpl->table_form_field_text("Content Security Policy", $array, $ico_shield);

            $opts = array();
            $tpl->table_form_field_js("Loadjs('$page?www-parameters-security-js=$ID')");
            if ($ASPROXY) {
                $RestrictIFrames = intval($sockngix->GET_INFO("RestrictIFrames"));
                $XSSBrowser = intval($sockngix->GET_INFO("XSSBrowser"));
                $ReferrerPolicy = intval($sockngix->GET_INFO("ReferrerPolicy"));
                $ico_shield = ico_shield;
                if ($RestrictIFrames == 1) {
                    $opts[] = "{RestrictIFrames}";
                }
                if ($XSSBrowser == 1) {
                    $opts[] = "{XSS_FILTERB}";
                }
                if ($ReferrerPolicy == 1) {
                    $opts[] = "{ReferrerPolicy}";
                }

            }

            if (count($opts) == 0) {
                $opts[] = "{none}";
                $ico_shield = ico_shield_disabled;
            }
            $tpl->table_form_field_js("Loadjs('$page?www-parameters-security-js=$ID')");
            $tpl->table_form_field_text("{headers}", "<small>" . @implode(", ", $opts) . "</small>", $ico_shield);
        }
    }

    if(!isset($noSSL[$type])) {
        $tpl->table_form_field_js("Loadjs('$page?www-parameters-ssl-js=$ID')");
        $ssl_certificate = $sockngix->GET_INFO("ssl_certificate");
        if (strlen($ssl_certificate) < 4) {
            $tpl->table_form_field_text("{certificate}", "{not_used}", ico_ssl);
        } else {
            $tpl->table_form_section("{ssl_parameters}");
            $ssl_protocols = $sockngix->GET_INFO("ssl_protocols");
            $ssl_ciphers = $sockngix->GET_INFO("ssl_ciphers");
            $ssl_prefer_server_ciphers = intval($sockngix->GET_INFO("ssl_prefer_server_ciphers"));
            $ssl_certificate = $sockngix->GET_INFO("ssl_certificate");
            $proxy_ssl_server_name = intval($sockngix->GET_INFO("proxy_ssl_server_name"));
            $proxy_ssl_name= $sockngix->GET_INFO("proxy_ssl_name");

            if ($ssl_protocols == null) { $ssl_protocols = "TLSv1.2 TLSv1.3"; }
            if ($ssl_ciphers == null) {
                $ssl_ciphers = "ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256";
            }

            $ssl_protocols_a = explode(" ", $ssl_protocols);
            foreach ($ssl_protocols_a as $pproto) {
                $MyProtos[strtolower($pproto)] = true;
            }

            $protos = explode(" ", "TLSv1 TLSv1.1 TLSv1.2 TLSv1.3");
            $LocalProto = array();
            $c = 0;
            foreach ($protos as $pproto) {
                $pproto_low = strtolower($pproto);
                if (!isset($MyProtos[$pproto_low])) {
                    continue;
                }
                $c++;
                $LocalProto[] = $pproto;
            }
            if ($c == 0) {
                $LocalProto = array("TLSv1.2", "TLSv1.3");
            }
            if(preg_match("#^SUB:([0-9]+)#",$ssl_certificate,$re)){
                $qSSL=new lib_sqlite("/home/artica/SQLITE/certificates.db");
                $ligneSSL=$qSSL->mysqli_fetch_array("SELECT commonName FROM subcertificates WHERE ID=$re[1]");
                $ssl_certificate=$ligneSSL["commonName"];
            }
            $ssl_ciphers_array = explode(":", $ssl_ciphers);
            $tpl->table_form_field_bool("{Redirect80To443}", intval($sockngix->GET_INFO("Redirect80To443")), ico_arrow_right);
            $tpl->table_form_field_text("{certificate}", $ssl_certificate . " " . @implode(",", $LocalProto), ico_certificate);
            $cpher_text = "";
            if ($ssl_prefer_server_ciphers == 1) {
                $cpher_text = "{ssl_prefer_server_ciphers}";
            }

            $array["BUTTON"]["VALUE"] = $cpher_text;
            $array["BUTTON"]["LABEL"] = count($ssl_ciphers_array) . " {rules}";
            $array["BUTTON"]["JS"] = "Loadjs('fw.nginx.ciphers.php?service-js=$ID')";

            $tpl->table_form_field_text("{ssl_ciphers}", $array, ico_ssl);
            $EnableClientCertificate = intval($sockngix->GET_INFO("EnableClientCertificate"));
            $OptionalClientCertificate = intval($sockngix->GET_INFO("OptionalClientCertificate"));

            $client_side_text = "{for_the_entire_website}";
            if ($OptionalClientCertificate == 1) {
                $client_side_text = "{for_part_website}";
            }

            $tpl->table_form_field_js("Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-js=$ID&function=$function')","AsWebMaster");
            if ($EnableClientCertificate == 0) {
                $tpl->table_form_field_text("{client_side_certificate}", "{inactive2}", ico_users);
            } else {
                $tpl->table_form_field_text("{client_side_certificate}", $client_side_text, ico_users);
            }
            $tpl->table_form_field_js("Loadjs('$page?ProxySslServerName-js=$ID&function=$function')","AsWebMaster");
            if($proxy_ssl_server_name==0){
                $tpl->table_form_field_bool("{snih2}",0,ico_ssl);
            }else{
                $tpl->table_form_field_text("{snih2}", "<span style='text-transform: none'>$proxy_ssl_name</span>", ico_ssl);
            }


        }
    }
    $html[]=$tpl->table_form_compile();
    if(count($FINAL_ERROR)>0){
        $html[]=$tpl->div_warning(@implode("<br>",$FINAL_ERROR));
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function www_parameters_userAgents($ID,$tpl){

    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.useragents.php?service-js=$ID')");
    $FilterUserAgents=intval($sockngix->GET_INFO("FilterUserAgents"));

    if($FilterUserAgents==0){
        $tpl->table_form_field_bool("{http_user_agent} ({deny})",0,ico_html);
        return $tpl;
    }

    $FUserAgents       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("FUserAgents"));
    if(!is_array($FUserAgents)){$FUserAgents=array();}
    $c=0;
    foreach ($FUserAgents as $D=>$enabled){
        if($enabled==0){continue;}
        $c++;
    }



    $tpl->table_form_field_text("{http_user_agent} ({deny})","$c {rules}",ico_html);
    return $tpl;
}
function www_permissions_policy($ID,$tpl){
    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.permissions-policies.php?service-js=$ID')");
    $FilterPermPolicy=intval($sockngix->GET_INFO("FilterPermPolicy"));
    if($FilterPermPolicy==0){
        $tpl->table_form_field_bool("Permissions Policy",0,ico_html);
        return $tpl;
    }
    $FPermPolicy       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("FPermPolicy"));
    if(!is_array($FPermPolicy)){$FPermPolicy=array();}
    $c=0;
    foreach ($FPermPolicy as $D=>$enabled){
        if($enabled==0){continue;}
        $c++;
    }
    $tpl->table_form_field_text("Permissions Policy","$c {rules}",ico_html);
    return $tpl;
}
function www_DenyAccess($ligne,$tpl){
    $page=CurrentPageName();
    $NoDeny[14]=true;
    $type=$ligne["type"];
    $ID=$ligne["ID"];
    if(isset($NoDeny[$type])) {return $tpl;}
    $sockngix=new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('$page?www-parameters-general-js=$ID')");
    $DenyAccess=intval($sockngix->GET_INFO("DenyAccess"));
    $tpl->table_form_field_bool("{website_blocked}", $DenyAccess, ico_ban);
    return $tpl;

}
function www_extensions_count($ID):int{
    $sockngix=new socksngix($ID);
    $AllowedExtensions= $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("AllowedExtensions"));
    if(count($AllowedExtensions)==0){
        $AllowedExtensions=array("html","htm","css","js","jpg","jpeg","png","gif","svg","ico","webp","txt","pdf","php","asp","aspx","jsp","py","rb","pl","xml","json","ejs","pug","twig","hbs","mustache","mp3","mp4","webm","ogg","wav","avi","mov","zip","tar","gz","rar","7z","dbf","exe","woff","woff2","ttf","otf");
        return count($AllowedExtensions);
    }
    $c=0;
    foreach ($AllowedExtensions as $D=>$enabled){
        if($enabled==0){continue;}
        $c++;
    }
return $c;
}
function www_extensions($ligne,$tpl){
    $ID=$ligne["ID"];
    $sockngix=new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.extensions.php?service-js=$ID')");
    $LimitExtensions=intval($sockngix->GET_INFO("LimitExtensions"));
    if($LimitExtensions==0) {
        $tpl->table_form_field_bool("{allowed_extensions}",0, ico_file);
        return $tpl;
    }

    $items=www_extensions_count($ID);
    $tpl->table_form_field_text("{allowed_extensions}", "{allow} $items {elements}", ico_file);
    return $tpl;
}

function www_Reputation($ID,$tpl){
    $f=array();
    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.sites.reputation.php?serviceid=$ID')");
    $ReputationServiceBlack = intval($sockngix->GET_INFO("ReputationServiceBlack"));
    $ReputationServiceWhite = intval($sockngix->GET_INFO("ReputationServiceWhite"));
    $ReputationServiceRedir = intval($sockngix->GET_INFO("ReputationServiceRedir"));
    $ReputationServiceURL = trim($sockngix->GET_INFO("ReputationServiceURL"));
    $ReputationServiceErrCode = intval($sockngix->GET_INFO("ReputationServiceErrCode"));

    if($ReputationServiceBlack==0){
        $tpl->table_form_field_bool("{reputation_service}",0,ico_shield_disabled);
        return $tpl;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if($ReputationServiceBlack>0) {
        $ligne = $q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID=$ReputationServiceBlack");
        $rulename=$ligne["rulename"];
        $f[]="<small>{deny_access}: $rulename";
    }


    if($ReputationServiceWhite>0){
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID=$ReputationServiceWhite");
        $rulename=$ligne["rulename"];
        $f[]="{allow_access} $rulename";
    }
    if($ReputationServiceRedir==1){
        $f[]="{redirect}: $ReputationServiceURL";
    }else{
        $f[]="{error_code}: $ReputationServiceErrCode";
    }
    $f[]="</small>";
    $tpl->table_form_field_text("{reputation_service}",@implode(", ",$f),ico_shield);

    return $tpl;
}
function www_fingerprint($ID,$tpl){
    $f=array();
    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.sites.fingerprinting.php?serviceid=$ID')");
    $FingerPrinting = intval($sockngix->GET_INFO("FingerPrinting"));

    if($FingerPrinting==0){
        $tpl->table_form_field_bool("{fingerprinting}",0,"fas fa-fingerprint");
        return $tpl;
    }
    $tpl->table_form_field_text("{fingerprinting}","{active2}","fas fa-fingerprint");

    return $tpl;
}
function www_crowdsec($ID,$tpl){

    VERBOSE("www_crowdsec($ID,..)",__LINE__);
    $sockngix                   = new socksngix($ID);
    $EnableCrowdSecGen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));

    if($EnableCrowdSecGen==0){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("CrowdSec {checking}","{not_installed}",ico_shield_disabled);
        return $tpl;
    }

    $tpl->table_form_field_js("Loadjs('fw.nginx.sites.modsecurity.php?serviceid=$ID')");

    $EnableCrowdSec=intval($sockngix->GET_INFO("EnableCrowdSec"));
    VERBOSE("EnableCrowdSec($ID)=$EnableCrowdSec",__LINE__);

    if($EnableCrowdSec==0){
        $tpl->table_form_field_bool("CrowdSec {checking}",0,ico_shield_disabled);
        return $tpl;
    }

    $tpl->table_form_field_text("CrowdSec {checking}","{active2}",ico_shield);
    return $tpl;
}
function www_hideheaders($ID,$tpl){
    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.rules.hide_headers.php?service-js=$ID')");

    $DisableHideHeadersDefault=intval($sockngix->GET_INFO("DisableHideHeadersDefault"));

    $data=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("proxy_hide_headers"));
    $c=0;
    foreach ($data as $num=>$ligne) {
        $enable = intval($ligne["enable"]);
        if ($enable == 0) {
           continue;
        }
        $c++;
    }

    if($DisableHideHeadersDefault==1){
        if($c==0){
            $tpl->table_form_field_bool("{header_remove_rules}",0,ico_html);
            return $tpl;
        }
    }


    $hide_headers_text=array();

    if($c>0){
        $c=$c+10;
        $hide_headers_text[]="$c {rules}";
    }else{
        $hide_headers_text[]="{default}: 10 {rules}";
    }

    $hide_headers=@implode(", ",$hide_headers_text);
    $tpl->table_form_field_text("{header_remove_rules}",$hide_headers,ico_html);
    return $tpl;

}
function www_parameters_countries($ID,$tpl){


    $sockngix                   = new socksngix($ID);
    if(!is_file("/etc/nginx/maps.d/00_GeoIP.map")) {
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{countries} ({inactive2})",0,ico_location);
        return $tpl;
    }
    $tpl->table_form_field_js("Loadjs('fw.nginx.countries.php?service-js=$ID')");
    $FilterCountries=intval($sockngix->GET_INFO("FilterCountries"));
    if($FilterCountries==0){
        $tpl->table_form_field_bool("{countries} ({deny})",0,ico_location);
        return $tpl;
    }



    $FCountries       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("FCountries"));
    if(!is_array($FCountries)){$FCountries=array();}
    $c=0;
    foreach ($FCountries as $D=>$enabled){
        if($enabled==0){continue;}
        $c++;
    }

    $tpl->table_form_field_text("{countries} ({deny})","$c {countries}",ico_location);

    return $tpl;

}
function www_parameters_uris($ID,$tpl){
    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.urisblock.php?service-js=$ID')");
    $FilterCountries=intval($sockngix->GET_INFO("FilterUris"));
    if($FilterCountries==0){
        $tpl->table_form_field_bool("{urls} ({deny})",0,ico_link);
        return $tpl;
    }



    $FCountries       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sockngix->GET_INFO("FUris"));
    if(!is_array($FCountries)){$FCountries=array();}
    $c=0;
    foreach ($FCountries as $D=>$enabled){
        if($enabled==0){continue;}
        $c++;
    }

    $tpl->table_form_field_text("{urls} ({deny})","$c {urls}",ico_link);

    return $tpl;

}
function www_hosts():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["www-hosts"];

    $html[]="<div id='nginx-hosts-$ID'></div>";
    $html[]="<script>LoadAjaxSilent('nginx-hosts-$ID','$page?www-hosts2=$ID')</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function www_hosts_edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $servicename=get_servicename($ID);
    $domain=$_GET["www-host-edit"];
    if($domain==null){
        $title="$servicename {new_domain}";
    }else{
        $title="$servicename ".base64_decode($domain);
    }
    $domain=urlencode($domain);
    return $tpl->js_dialog2($title,"$page?www-host-edit-popup=$domain&service-id=$ID");
}

function proxy_ssl_server_name_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["ProxySslServerName-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog2("$servicename: {snih2}","$page?ProxySslServerName-popup=$ID",650);
}
if(isset($_GET["ProxySslServerName-popup"])){proxy_ssl_server_name_popup();exit;}
function proxy_ssl_server_name_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["ProxySslServerName-popup"]);
    $sock=new socksngix($ID);
    $proxy_ssl_server_name=intval($sock->GET_INFO("proxy_ssl_server_name"));
    $proxy_ssl_name =trim($sock->GET_INFO("proxy_ssl_name"));
    $form[]=$tpl->field_hidden("ProxySslServerName", $ID);
    $form[]=$tpl->field_checkbox("proxy_ssl_server_name","{enable_feature}",$proxy_ssl_server_name);
    $form[]=$tpl->field_text("proxy_ssl_name","{domain}",$proxy_ssl_name);
    $js[]="LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');";
    $js[]="dialogInstance2.close();";
    $jsAll=@implode(";",$js);
    echo $tpl->form_outside("", $form,"{proxy_ssl_server_name_explain}","{apply}",$jsAll,"AsSystemWebMaster");
    return true;
}
function proxy_ssl_server_name_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ProxySslServerName"]);
    $proxy_ssl_server_name=intval($_POST["proxy_ssl_server_name"]);
    $proxy_ssl_name=trim($_POST["proxy_ssl_name"]);
    $sock=new socksngix($ID);
    $sock->SET_INFO("proxy_ssl_server_name",$proxy_ssl_server_name);
    $sock->SET_INFO("proxy_ssl_name",$proxy_ssl_name);
    $servicename=get_servicename($ID);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Save Reverse-Proxy SNI enforce domain for $servicename to enabled=$proxy_ssl_server_name, domain=$proxy_ssl_name");
}
function www_hosts_edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $servicename=get_servicename($ID);
    $Type=get_ServiceType($ID);
    $domain=base64_decode($_GET["www-host-edit-popup"]);
    $redirect=null;

    $Noredirect=false;
    if($Type==13){
        $Noredirect=true;
    }
    if(preg_match("#(.+)>(.+)#",$domain,$re)){
        $domain=$re[1];
        $redirect=$re[2];
    }
    $form[]=$tpl->field_hidden("hosts-id", $ID);
    $form[]=$tpl->field_text("hosts","{domain}", $domain,true);
    if(!$Noredirect) {
        $form[] = $tpl->field_text("redirect", "{redirect_uri} ({domain} - {optional})", $redirect);
    }
    echo $tpl->form_outside("$servicename: {servernames}", $form,"{servernames_explain}","{apply}",
        "dialogInstance2.close();LoadAjaxSilent('nginx-hosts-$ID','$page?www-hosts2=$ID');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');","AsSystemWebMaster");
    return true;
}
function www_hosts2():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $q          = new lib_sqlite(NginxGetDB());
    $ID         = intval($_GET["www-hosts2"]);
    $socksngix  = new socksngix($ID);

    if(!$q->FIELD_EXISTS("nginx_services","ResolvErrDetail")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD ResolvErrDetail TEXT NOT NULL DEFAULT ''");
    }
    $ligne      = $q->mysqli_fetch_array("SELECT servicename,hosts,ResolvErrDetail FROM nginx_services WHERE ID=$ID");

    $html[]="<div style='margin-top:20px;width=80%'>";
    $ExpireCert="";
    $Zhosts=explode("||",$ligne["hosts"]);
    $ResolvErrDetail=unserialize($ligne["ResolvErrDetail"]);
    $ssl_certificate = $socksngix->GET_INFO("ssl_certificate");
    $ssl_certificates = array();
    if(strlen($ssl_certificate)>3){
        $sock=new sockets();
        $json=json_decode($sock->REST_API_NGINX("/reverse-proxy/certinfo/$ID"));
        if(!$json->Status){
            $html[]=$tpl->div_error($json->Error);
        }

        if(is_array($json->data->DNSNames)) {
            foreach ($json->data->DNSNames as $index => $domain) {
                $ssl_certificates[$domain] = true;
            }
        }
        $ExpireCert=distanceOfTimeInWords($json->data->ExpireDate,time());
    }

    $topbuttons[]=array("Loadjs('$page?www-host-edit=&service-id=$ID');", ico_plus,"{new_domain}");
    $topbuttons[]=array("Loadjs('fw.nginx.sites.dynamics.php?service-id=$ID')",ico_routes,"{APP_OSPF}");

    $topbuttons[]=array("Loadjs('fw.nginx.sites.letsencrypt.php?service-id=$ID')",ico_certificate,"{certificate} Let's Encrypt");

    $StyleRow="font-size:18px";


    $btns=$tpl->th_buttons($topbuttons);

    $html[]=$btns;
    $html[]="<table class=\"table table-stripped\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th colspan='2'>{domains}</th>";
    $html[]="<th>{certificate}</th>";
    $html[]="<th>{RESOLVED}</th>";
    $html[]="<th>{available}</th>";


    $html[]="<th></th>";
    $html[]="</tr>";
    $TRCLASS=null;

    $ForwardServersDynamics =   intval($socksngix->GET_INFO("ForwardServersDynamics"));
    if($ForwardServersDynamics==1) {
        $arrow="&nbsp;&nbsp;<i class='fa-solid fa-arrow-right-to-line'></i>&nbsp;&nbsp;";
        $FSDynamicsExt = intval($socksngix->GET_INFO("FSDynamicsExt"));
        $FSDynamicsSrc = trim($socksngix->GET_INFO("FSDynamicsSrc"));
        $FSDynamicsDst = trim($socksngix->GET_INFO("FSDynamicsDst"));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        if($FSDynamicsExt==1){
            if(preg_match("#\.(.*?)$#",$FSDynamicsSrc,$re)){
                $FSDynamicsSrc=str_replace(".".$re[1],".*",$FSDynamicsSrc);
            }else{
                $FSDynamicsSrc=$FSDynamicsSrc.".*";
            }
            if(preg_match("#\.(.*?)$#",$FSDynamicsDst,$re)){
                $FSDynamicsDst=str_replace(".".$re[1],".*",$FSDynamicsDst);
            }else{
                $FSDynamicsDst=$FSDynamicsDst.".*";
            }
        }


        $FSDynamicsSrc=$tpl->td_href($FSDynamicsSrc,null,"Loadjs('fw.nginx.sites.dynamics.php?service-id=$ID');");
        $html[]="<tr class='$TRCLASS' id='a00'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap><i class='".ico_routes." fa-2x'></i></td>";
        $html[]="<td style='width:100%;'><strong style='$StyleRow'>*.$FSDynamicsSrc$arrow*.$FSDynamicsDst</strong></td>";
        $html[]="<td style='width:1%;' class='center'>&nbsp;</td>";
        $html[]="</tr>";
    }
    $icon_certif=ico_certificate;

    foreach ($Zhosts as $domains){
        if (trim($domains)==null){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($domains);
        $arrow=null;$redir=null;
        $domainsenc=urlencode(base64_encode($domains));
        $ResolvErr="<span class='text-danger'><i class='text-danger fa-2x fas fa-times'></i></span>";
        $SSLText="";
        $resolvedIp="";
        $LetsEncrypt=false;
        $infs="";
        $Subtext=array();
        if(isset($ResolvErrDetail[$domains])){
            VERBOSE("ResolvErrDetail:$ResolvErrDetail[$domains]",__LINE__);
            if(preg_match("#^ERROR:(.+)#",$ResolvErrDetail[$domains],$re)){
                $ResolvErr="<span class='text-danger'><i class='text-danger fa-2x fas fa-times'></i></span>";
                $Subtext[]="<small class='text-danger'>$re[1]</small>";
            }else{

                $ResolvedInfo=$ResolvErrDetail[$domains];
                VERBOSE("ResolvedInfo:$ResolvedInfo",__LINE__);
                if(strpos($ResolvedInfo,"||")){
                    $ResolvedInfoSplitted=explode("||",$ResolvedInfo);
                    if(preg_match("#^RESOLVED:([a-z:0-9\.]+)#",$ResolvedInfoSplitted[0],$re)){
                        $resolvedIp=$re[1];
                        $Subtext[]="<small>$resolvedIp</small>";
                        $ResolvErr="<i style='color:#18a689' class='fa-2x fas fa-check-circle'></i>";
                    }
                    if(preg_match("#TRUE:(.+?):#",$ResolvedInfoSplitted[1],$re)){
                        $LetsEncrypt=true;
                    }
                    if(preg_match("#^ERROR:(.+?):(.+)#",$ResolvedInfoSplitted[1],$re)){
                        $Subtext[]="<small>$resolvedIp</small>";
                        $ResolvErr="<i style='color:#18a689' class='fa-2x fas fa-check-circle'></i>";
                        $Subtext[]="<small class='text-danger'>$re[2]</small>";
                    }
                }



            }
        }


        $delete=$tpl->icon_delete("Loadjs('$page?www-host-delete=$domainsenc&md=$md&service-id=$ID')","AsWebMaster");

        if(preg_match("#^(.+?)>(.+)#",$domains,$re)){
            $domains=$re[1];
            $arrow="&nbsp;&nbsp;<i class='fa-solid fa-arrow-right-to-line fa-1x'></i>&nbsp;&nbsp;";
            $redir=$re[2];
        }
        if(count($ssl_certificates)>0){
            if(isset($ssl_certificates[$domains])){
                $SSLText="<i class='fa-2x $icon_certif' style='color:#18a689'></i><span style='$StyleRow'>&nbsp;{expire}: $ExpireCert</span>";
            }else{
                $SSLText="<i class='fa-2x $icon_certif' style='color:#ed5565'></i><span style='$StyleRow'>&nbsp;{unsigned}</span>";
            }
        }


        $domains=$tpl->td_href($domains,null,"Loadjs('$page?www-host-edit=$domainsenc&service-id=$ID');");

        $LetsEncrypt_ico="<i class='text-danger fa-2x fas fa-times'></i>";
        if($LetsEncrypt){
            $LetsEncrypt_ico="<i style='color:#18a689' class='fa-2x fas fa-check-circle'></i>";
        }

        if(count($Subtext)>0){
            $infs=@implode(", ",$Subtext);
        }
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap><i class='".ico_earth." fa-2x'></i></td>";
        $html[]="<td style='width:100%;'><strong style='$StyleRow'><div>$domains$arrow$redir</strong></div>$infs</td>";
        $html[]="<td width='1%' nowrap>$SSLText</td>";
        $html[]="<td width='1%' nowrap class='center'>$ResolvErr</td>";
        $html[]="<td width='1%' nowrap class='center'>$LetsEncrypt_ico</td>";
        $html[]="<td style='width:1%;' class='center'>$delete</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function www_hosts_delete():bool{
    $md=$_GET["md"];
    $ID=$_GET["service-id"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $value=base64_decode($_GET["www-host-delete"]);
    $servicename=get_servicename($ID);
    $array["service-id"]=$ID;
    $array["value"]=$value;
    $finale=urlencode(base64_encode(serialize($array)));
    return $tpl->js_confirm_delete("$servicename>$value","hosts-delete",$finale,"$('#$md').remove();Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');");
}
function www_hosts_delete_perform():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();

    $svalue=base64_decode($_POST["hosts-delete"]);
    $array=unserialize($svalue);
    $ID=$array["service-id"];
    $value=$array["value"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT `servicename`,`hosts` FROM nginx_services WHERE ID=$ID");
    $Zhosts=explode("||",$ligne["hosts"]);
    $MAIN=array();


    foreach ($Zhosts as $domain){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        $MAIN[$domain]=true;

    }
    unset($MAIN[$value]);
    $F=array();
    foreach($MAIN as $domain=>$none){
        $domain=trim(strtolower($domain));
        if(strpos($domain, ";")>0){continue;}
        if($domain==null){continue;}
        $F[]=$domain;
    }
    $newval=trim(@implode("||",$F));
    $q->QUERY_SQL("UPDATE nginx_services SET `hosts`='$newval',`goodconftime`=0 WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    $servicename=get_servicename($ID);
    admin_tracks("Delete $value from reverse-proxy service $servicename");
    return true;
}
function www_hosts_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_POST["hosts-id"];
    $F=array();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite(NginxGetDB());
    if(!isset($_POST["redirect"])){$_POST["redirect"]="";}
    $ligne=$q->mysqli_fetch_array("SELECT `servicename`,`hosts` FROM nginx_services WHERE ID=$ID");
    $Zhosts=explode("||",$ligne["hosts"]);

    foreach ($Zhosts as $domain){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        if(preg_match("#^(.+?)>(.+)#",$domain,$re)){
            $MAIN[$re[1]]=$re[2];
            continue;
        }
        $MAIN[$domain]="";

    }
    $domain=trim(strtolower($_POST["hosts"]));
    $redirect=trim(strtolower($_POST["redirect"]));
    $MAIN[$domain]=$redirect;


    foreach($MAIN as $domain=>$redirect){
        $domain=trim(strtolower($domain));
        if(strpos($domain, ";")>0){continue;}
        if($domain==null){continue;}
        if($redirect<>null){
            $domain="$domain>$redirect";
        }
        $F[]=$domain;
    }

    $newval=trim(@implode("||",$F));
    $q->QUERY_SQL("UPDATE nginx_services SET `hosts`='$newval',`goodconftime`=0 WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    $servicename=get_servicename($ID);
    admin_tracks_post("Add/Edit domain for reverse-proxy service  $servicename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return true;
}
function isAlready14():bool{
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE type=14");
    if(isset($ligne["ID"])){return true;}
    return false;
}
function new_www():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $DOHServerEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOHServerEnabled"));
    $EnableAptMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror"));
    $PHPReverseEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHPReverseEnabled"));
    $NgxStreamJS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NgxStreamJS"));
    $ArticaMetaEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));

    if($PHPReverseEnabled==1) {
        $Types[1] = "{php_website_explain}";
    }
    $Types[2] = "{artica_reverse_sites_explain}";
    $Types[13] = "{CREATE_ADFS_SERVICE}";
    $Types[9] = "{CREATE_WEB_HTML_SERVICE}";

    if($NgxStreamJS==1){
        $Types[15] = "{CREATE_DOH_SERVICE}";
    }
    if($ArticaMetaEnabled==1){
        $Types[16] = "{CREATE_METAR_SERVICE}";
    }

    if(!isHarmpID()) {
        $Types[4] = "{artica_website_explain}";
        $Types[5] = "{nginx_stream_explain}";
        /*


        //$Types[3]="{hotspot_website_explain}";


        //$Types[6]="{CREATE_WEBFILTERING_ERROR_SERVICE}";
        if ($DOHServerEnabled == 1) {
            $Types[7] = "{CREATE_DOH_WEB_SERVICE_SERVICE}";
        }
        //$Types[8]="{CREATE_PROXY_PAC_SERVICE}";

        //$Types[10]="{CREATE_ITCHARTER_SERVICE}";
        if ($EnableAptMirror == 1) {
            $Types[11] = "{CREATE_APTMIRROR_SERVICE}";
        }
        $Types[12] = "{CREATE_WEBCOPY_SERVICE}";
        $Types[13] = "{CREATE_ADFS_SERVICE}";
        */
    }
    if(!isAlready14()) {
        $Types[14] = "{DEFAULT_SERVER_BLOCK}";
    }
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT *  FROM nginx_templates");

    foreach ($results as $index=>$ligne) {
        $ID = intval($ligne["ID"]);
        $tpname = $ligne["tpname"];
        $tpdate = $ligne["tpdate"];
        $tpdesc = $ligne["tpdesc"];

        $Types["tpl:$ID"]="<strong>$tpname</strong>&nbsp;({template})<br>$tpdesc<br><small>{created_at}: ".$tpl->time_to_date($tpdate)."</small>";

    }

    $form[]=$tpl->field_hidden("ID", 0);
    $form[]=$tpl->field_text("servicename", "{service_name2}", "New service",true);
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ztype", 2);
    echo $tpl->form_outside("{new_service}", $form,"{nginx_service_explain}","{add}","dialogInstance1.close();Loadjs('$page?new-www-after=yes');","AsSystemWebMaster");
    return true;
}
function is_valid_domain_name($domain_name):bool{
    if(!preg_match("#^(.+?)\.([a-z\.]+)$#",$domain_name)){
        return false;
    }
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name)
        && preg_match("/^.{1,253}$/", $domain_name)
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   );
}
function new_www_after():bool{
    header("content-type: application/x-javascript");
    if(!isset($_SESSION["NEWNGINXAFTER"]["TPLID"])){
        echo "NgixSitesReload();";
        return true;
    }
    if(!is_numeric( $_SESSION["NEWNGINXAFTER"]["TPLID"])){
        echo "NgixSitesReload();";
        return true;
    }

    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tmplid=intval($_SESSION["NEWNGINXAFTER"]["TPLID"]);
    $serviceid=intval($_SESSION["NEWNGINXAFTER"]["SITEID"]);
   echo  $tpl->framework_buildjs("nginx.php?apply-template=$tmplid&serviceid=$serviceid",
        "nginx.replic.$serviceid.progress",
        "nginx.replic.$serviceid.log",
       "progress-websites-restart","NgixSitesReload();"
    );
    return true;
}
function new_www_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $q=new lib_sqlite(NginxGetDB());
    $servicename=$_POST["servicename"];
    $_SESSION["NEWNGINXAFTER"]=array();

    if(preg_match("#tpl:([0-9]+)#",$_POST["ztype"],$re)){
        $NginxTemplates=new NginxTemplates($re[1]);
        if(!$NginxTemplates->CreateSite($servicename)){
            echo $tpl->post_error("Template:$NginxTemplates->mysql_error");
            return false;
        }
        $_SESSION["NEWNGINXAFTER"]["TPLID"]=$re[1];
        $_SESSION["NEWNGINXAFTER"]["SITEID"]=$NginxTemplates->serviceid;
        return admin_tracks("Add new reverse-proxy site $servicename from template $NginxTemplates->TemplateName");
    }

    $ztype=intval($_POST["ztype"]);
    $port=443;
    if($ztype==0){
        echo $tpl->post_error("Please specify the web service type");
        return false;
    }


    if($ztype==14){
        $results=$q->QUERY_SQL("SELECT ID FROM nginx_services WHERE isDefault=1");
        foreach ($results as $index=>$xline){
            $q->QUERY_SQL("UPDATE nginx_services SET isDefault=0 WHERE ID={$xline["ID"]}");
            $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/{$xline["ID"]}");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
        }
    }
    if($ztype==16){
        // Special META SERVER
    }

    if(preg_match("#^(http|https):\/\/(.+)#",$servicename)){
        $zuri=parse_url($servicename);
        $servicename=$zuri["host"];
    }

    $servicename=sqlite_escape_string2($servicename);
    $TEMPKEY=md5(time().rand(0,time()));
    $q->QUERY_SQL("INSERT INTO nginx_services (`type`,`servicename`,`enabled`,`rebooted`) VALUES ('$ztype','$TEMPKEY',1,0)");
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE servicename='$TEMPKEY'");
    $ID=intval($ligne["ID"]);

    new_www_defaults_ports($servicename,$ztype,$ID);
    new_www_defaults_ports2($servicename,$ztype,$ID);
    new_www_dnsports($servicename,$ztype,$ID);

    // If service name is a domain ?
    if(is_valid_domain_name($servicename)){
        $q->QUERY_SQL("UPDATE nginx_services SET `hosts`='$servicename',`goodconftime`=0 WHERE ID=$ID");
    }
    $q->QUERY_SQL("UPDATE nginx_services SET `servicename`='$servicename' WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    return admin_tracks("Add new reverse-proxy site $servicename");
}
function new_www_defaults_ports($servicename,$servicetype,$ID):bool{
    $port=443;
    if($servicetype==14){return true;}
    if($servicetype==5){return true;}
    if($servicetype==16){$port=443;}
    if(isHarmpID()) { return true;}
    $options["ssl"]=1;
    $options["http2"]=1;

    if(preg_match("#^(http|https):\/\/(.+)#",$servicename)){
        $zuri=parse_url($servicename);
        $servicename=$zuri["host"];
        if(isset($zuri["port"])){
            $port=$zuri["port"];
        }
    }
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $q=new lib_sqlite(NginxGetDB());
    // Try to found the best interface for default port
    $nics = $tpl->list_interfaces(true);
    foreach ($nics as $int => $none) {
        $zn[] = $int;
    }
    $options=array();
    if($port==443){
        $options["ssl"]=1;
        $options["http2"]=1;
    }
    $options=base64_encode(serialize($_POST));
    $interface = $zn[0];
    if (preg_match("#eth([0-9]+)#", $interface, $re)) {
        $interface = "eth{$re[1]}";
        $md5 = md5($interface . $port . $ID);
        $q->QUERY_SQL("INSERT INTO stream_ports(serviceid,interface,port,zmd5,options) 
                    VALUES ($ID,'$interface',$port,'$md5','$options')");
    }

    return true;
}
function new_www_dnsports($servicename,$servicetype,$ID):bool{
    if($servicetype<>14){return true;}
    $port=443;
    $q=new lib_sqlite(NginxGetDB());
    if(preg_match("#^(.+?):([0-9]+)$#",$servicename,$re)){
        $port=$re[2];
    }
    $md5 = md5($port . $ID);
    if($port==443){
        $options["ssl"]=1;
        $options["http2"]=1;
    }
    $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_POST);
    $q->QUERY_SQL("INSERT INTO stream_ports(serviceid,interface,port,zmd5,options) VALUES ($ID,'',$port,'$md5','$options')");

    return true;
}
function new_www_defaults_ports2($servicename,$servicetype,$ID):bool{
    if($servicetype==14){return true;}
    if($servicetype==5){return true;}
    if(!isHarmpID()) { return true;}
    $port=443;

    if(preg_match("#^(.+?):([0-9]+)$#",$servicename,$re)){
        $port=$re[2];
    }
    $q=new lib_sqlite(NginxGetDB());

    $md5 = md5($port . $ID);
    if($port==443){
        $options["ssl"]=1;
        $options["http2"]=1;
    }
    $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_POST);
    $q->QUERY_SQL("INSERT INTO stream_ports(serviceid,interface,port,zmd5,options) VALUES ($ID,'',$port,'$md5','$options')");

    return true;
}
function www_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST_XSS();
    $q=new lib_sqlite(NginxGetDB());
    $ID=intval($_POST["ID"]);
    if($ID==0){return new_www_save();}
    $isDefault=intval($_POST["isDefault"]);

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
        $isDefaultOld=intval($ligne["isDefault"]);
        if($isDefaultOld<>$isDefault){
            $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/RemoveDefaults");
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    if(!$q->ok){echo $q->mysql_error;}
    $sockngix=new socksngix($ID);

    $pprotoFound=false;
    $pproto=array();
    foreach ($_POST as $key=>$value){
        if(!preg_match("#pproto_(.+)#",$key,$re)){continue;}
        if($value==0){continue;}
        $re[1]=str_replace("_",".",$re[1]);
        $pprotoFound=true;
        $pproto[]=$re[1];
        unset($_POST[$key]);

    }

    if($pprotoFound) {
        $_POST["ssl_protocols"] = @implode(" ", $pproto);
    }
    foreach ($_POST as $key=>$value){
        $sockngix->SET_INFO($key, $value);

    }

    if(isset($_POST["servicename"])) {
        $servicename = sqlite_escape_string2($_POST["servicename"]);
        $q->QUERY_SQL("UPDATE nginx_services SET `servicename`='$servicename', isDefault=$isDefault WHERE ID=$ID");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    }
    if($ID>0) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    }
    if($servicename<>null) {
        return admin_tracks_post("Save $servicename reverse-proxy parameters");
    }
    return true;
}
function td_btnPagespeed($enabled,$ID):array{

    $ID=intval($ID);
    if($ID==0){return array("","");}
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }


    $sock=new socksngix($ID);
    $Type=$sock->GetType();
    if( $Type==4 OR $Type==14 ){
        return array("","");
    }
    if($enabled==0){
        return array("","");
    }

    $Icon="fas fa-gauge-circle-bolt";

    if(!isHarmpID()){
        $nginx_pagespeed_installed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
        if($nginx_pagespeed_installed==0){
            return array("","");

        }
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"))==0){
            return array("PageSpeed ({inactive})","$Icon:color:grey||blur()");
        }
    }


    $js="Loadjs('fw.nginx.sites.optimize.php?serviceid=$ID&function=$function')";



    $pagespeed=intval($sock->GET_INFO("pagespeed"));
    VERBOSE("pagespeed == $pagespeed",__LINE__);
    if($pagespeed==1){
        return array("PageSpeed ({active2})","$Icon:color:green||$js");


    }
    return array("PageSpeed ({disabled})","$Icon:color:grey||$js");


}
function td_row_waf($ID):string{
    $ID=intval($ID);
    if($ID==0){return "";}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();


    $NginxHTTPModSecurity       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $EnableModSecurityIngix     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));

    if($NginxHTTPModSecurity==0){return $tpl->icon_shield();}
    if($EnableModSecurityIngix==0){
        return $tpl->icon_shield();
    }

    $sockngix                   = new socksngix($ID);
    $Type=$sockngix->GetType();
    if($Type==14){
        return "&nbsp;";
    }

    $EnableModSecurity=intval($sockngix->GET_INFO("EnableModSecurity"));
    if($EnableModSecurity==0){
        return $tpl->icon_shield_grey("Loadjs('fw.nginx.sites.modsecurity.php?serviceid=$ID')","AsSystemWebMaster",true);
    }
    $ModSecurityAction=GetModSecurityAction($ID);

    if($ModSecurityAction=="auditlog,pass"){
        VERBOSE("icon_shield_yellow($ID) !",__LINE__);
        return $tpl->icon_shield_yellow("Loadjs('fw.nginx.sites.modsecurity.php?serviceid=$ID')");
    }

    return $tpl->icon_shield("Loadjs('fw.nginx.sites.modsecurity.php?serviceid=$ID')");

}
function MaintenanceSite():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["MaintenanceSite"];
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT MaintenanceSite FROM nginx_services WHERE ID=$ID");
    $MaintenanceSite=intval($ligne["MaintenanceSite"]);
    if($MaintenanceSite==0){
        $Text="enabled";
        $q->QUERY_SQL("UPDATE nginx_services SET MaintenanceSite=1 WHERE ID=$ID");
        if(!$q->ok){
            $tpl->js_error($q->mysql_error);
            return false;
        }

    }else{
        $Text="disabled";
        $q->QUERY_SQL("UPDATE nginx_services SET MaintenanceSite=0 WHERE ID=$ID");
        if(!$q->ok){
            $tpl->js_error($q->mysql_error);
            return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    header("content-type: application/x-javascript");
    $Sitename=get_servicename($ID);
    echo "Loadjs('$page?td-row=$ID');\n";
    return admin_tracks("Set maintenance reverse-proxy website of $Sitename to $Text");
}
function td_destinations():bool{
    $ID=$_GET["td-destinations"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();

    $q                          = new lib_sqlite(NginxGetDB());
    $sockngix                   = new socksngix($ID);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $idDiv="rcolor9-$ID";
    if(!isset($ligne["type"])){$ligne["type"]=0;}

    if(($ligne["type"]==8) OR ($ligne["type"]==6) or ($ligne["type"]==1) or ($ligne["type"]==10)){
        $destination=base64_encode($tpl->_ENGINE_parse_body("{local}"));
        VERBOSE("This is a local website",__LINE__);
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        echo @implode("\n",$f);
        return true;
    }
    if($ligne["type"]==14 or $ligne["type"]==9 or $ligne["type"]==1){
        VERBOSE("$ID: Type 14 OR 9",__LINE__);
        $destination=base64_encode($tpl->_ENGINE_parse_body("{local}"));
        if($ligne["type"]==9){
            $WebDavEnabled=$sockngix->GET_INFO("WebDavEnabled");
            if($WebDavEnabled==1){
                $destination=base64_encode($tpl->td_href("{local}&nbsp;<span class='label label-info'>WebDav</span>",
                    null,"Loadjs('fw.nginx.site.content.php?webdav-explain=$ID')"));
            }

        }


        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        echo @implode("\n",$f);
        return true;

    }
    if($ligne["type"]==7){
        $doh_subfolder=$sockngix->GET_INFO("doh_subfolder");
        if($doh_subfolder==null){$doh_subfolder="dns-query";}
        $destination=base64_encode($tpl->_ENGINE_parse_body("{local_dns_service}"));
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        echo @implode("\n",$f);
        return true;
    }
    if($ligne["type"]==5){
        $destination=backendsOf($ID);
        $destination=base64_encode($tpl->_ENGINE_parse_body($destination));
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        echo @implode("\n",$f);
        return true;
    }

    $mouses="onMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";



    if($ligne["type"]==2 OR $ligne["type"]==13 OR $ligne["type"]==15){
        $destination="{unknown}";
        $backends=extract_backends($ID);
        $tootips="";
        $fname="/usr/share/artica-postfix/ressources/databases/ReverseProxy/$ID.json";
        $json=json_decode(file_get_contents($fname));

        $BackendAnalyzed=$json->BackendAnalyzed;
        $BackendErr=$json->BackendErr;

        if($ligne["enabled"]==1) {
            if($BackendAnalyzed==1) {
                if ($BackendErr == 1) {
                    $js = "Loadjs('$page?backend-error-js=$ID')";
                    $tootips = "<span class='label label-danger' $mouses OnClick=\"$js\">{error}</span>&nbsp;";
                }
            }
        }

        $latencyscore_text="";
        $latencyscore=$json->LatencyScore;
        if($latencyscore>0){
            $latencyscore=MillisToText($latencyscore);
            if(file_exists("img/squid/nginxbackendslatency$ID-hourly.png")) {
                $latencyscore_text = "<br><small>".$tpl->td_href("{latency}: $latencyscore", "{statistics}",
                    "Loadjs('fw.rrd.php?img=nginxbackendslatency$ID')")."</small>";
                }
        }

        if($backends<>null){
            $destination="<small>$tootips$backends</small>$latencyscore_text";
        }

        $destination=base64_encode($tpl->_ENGINE_parse_body($destination));
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        echo @implode("\n",$f);
        return true;
    }

    $destination="<div class='center'>".$tpl->icon_nothing()."</div>";
    $destination=base64_encode($tpl->_ENGINE_parse_body($destination));
    $f[]="if( document.getElementById('$idDiv') ){";
    $f[]="\ttempdata=base64_decode('$destination');";
    $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
    $f[]="}";
    echo @implode("\n",$f);
    return true;
}
function MillisToText($mill):string{
    if($mill<1000){
        return round($mill,2)."ms";
	}

    $seconds =$mill / 1000;

	if($seconds < 60) {
        return round($seconds,2)."s";
	}
	$minutes=($mill/ 60000);
    return round($minutes,2)."mn";
}
function backend_error_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["backend-error-popup"];
    $page=CurrentPageName();
    $Gpid=intval($_SESSION["HARMPID"]);
    $restart=$tpl->framework_buildjs("nginx:/reverse-proxy/progress/checkbackend/$ID/$Gpid",
        "nginx.CheckReverseTests.$ID.progress",
        "nginx.CheckReverseTests.$ID.log","renalyze-backend-$ID",
        "dialogInstance2.close();Loadjs('$page?td-row=$ID');","Loadjs('$page?backend-error-js=$ID');");


    $btn=$tpl->button_autnonome("{analyze}",$restart,ico_refresh,null,335,"btn-danger");


    $fname="/usr/share/artica-postfix/ressources/databases/ReverseProxy/$ID.json";
    $json=json_decode(file_get_contents($fname));
    $BackendAnalyzedTime=$json->BackendAnalyzedTime;
    $BackendErrDetail=base64_decode($json->BackendErrDetail);
    $html[]="<div id='renalyze-backend-$ID'>";
    $html[]="<H2>{scan_date}: ".$tpl->time_to_date($BackendAnalyzedTime,true)." <small>( {since} ".distanceOfTimeInWords($BackendAnalyzedTime,time()).")</small></H2>";
    $btn="<div style='text-align:right;margin-top: 10px'>$btn</div>";

    $html[]=$tpl->div_error($BackendErrDetail.$btn)."</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function backend_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["backend-analyze-js"];
    $page=CurrentPageName();
    $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px'><H1>{please_wait}</H1></div>"));
    $js[]="document.getElementById('renalyze-backend-$ID').innerHTML=base64_decode('$html');";
    $js[]="Loadjs('$page?backend-analyze2-js=$ID')";
    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
    return true;
}
function backend2_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=$_GET["backend-analyze2-js"];
    $sock=new sockets();
    $Gpid=intval($_SESSION["HARMPID"]);
    $data=$sock->REST_API_NGINX("/reverse-proxy/checkbackend/$ID/$Gpid");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
       $text=json_last_error_msg();
        $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;color;red'><H1>$text</H1></div>"));
        $js[]="document.getElementById('renalyze-backend-$ID').innerHTML=base64_decode('$html');";
        $js[]="Loadjs('$page?backend-error-js=$ID');";
        header("content-type: application/x-javascript");
        echo @implode("\n",$js);
        return false;
    }
    if(!$json->Status){
        $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;color;red'><H1>$json->Error</H1></div>"));
        $js[]="document.getElementById('renalyze-backend-$ID').innerHTML=base64_decode('$html');";
        $js[]="Loadjs('$page?backend-error-js=$ID');";
        header("content-type: application/x-javascript");
        echo @implode("\n",$js);
        return false;
    }

    $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;color;green'><H1>{success}</H1></div>"));
    $js[]="document.getElementById('renalyze-backend-$ID').innerHTML=base64_decode('$html');";
    $js[]="Loadjs('$page?td-row=$ID');";
    $js[]="dialogInstance2.close();";

    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
    return true;
}
function td_row_clean($id):string{
    $b64=base64_encode(@file_get_contents("img/wait.gif"));
    $content=base64_encode("<img src='data:image/gif;base64,$b64' alt=''>");
    $f[]="tempdata=base64_decode('$content');";
    $f[]="if( document.getElementById('status-$id') ){";
    $f[]="\tdocument.getElementById('status-$id').innerHTML=tempdata;";
    $f[]="}";
    $f[]="if( document.getElementById('rcolor4-$id') ){";
    $f[]="\tdocument.getElementById('rcolor4-$id').innerHTML=tempdata;";
    $f[]="}";
    $f[]="if( document.getElementById('rcolor7-$id') ){";
    $f[]="\tdocument.getElementById('rcolor7-$id').innerHTML=tempdata;";
    $f[]="}";
    $f[]="if( document.getElementById('rcolor6-$id') ){";
    $f[]="\tdocument.getElementById('rcolor6-$id').innerHTML=tempdata;";
    $f[]="}";
    $f[]="if( document.getElementById('rcolor9-$id') ){";
    $f[]="\tdocument.getElementById('rcolor9-$id').innerHTML=tempdata;";
    $f[]="}";
    return @implode("\n",$f);
}
function td_row_serverstats($QueryID):string{

    if(isset($GLOBALS["TD_ROWS_STATS"])){
        $STATS=$GLOBALS["TD_ROWS_STATS"];
    }else {

        $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/stats");
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return json_last_error_msg();
        }
        if (!$json->Status) {
            return "L.".__LINE__."";
        }
        if (!property_exists($json, "Stats")) {
            return "L.".__LINE__;
        }
        $Class = $json->Stats;
        $Rules = $Class->rules;
        foreach ($Rules as $domain => $id) {
            $DOMS[$domain] = $id;
        }
        $STATS=array();
        foreach ($Class->serverZones as $Domain => $jclass) {
            if (isset($DOMS[$Domain])) {
                $STATS[$DOMS[$Domain]] = $jclass;
            }
        }
        $GLOBALS["TD_ROWS_STATS"]=$STATS;
    }
    if(!isset($STATS[$QueryID])){
        return "";
    }
    $MainJson=$STATS[$QueryID];
    if(!property_exists($MainJson,"requestCounter")){
        return "requestCounter!";
    }
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $requestCounter=$tpl->FormatNumber($MainJson->requestCounter,0,"&nbsp;",".");
    $inBytes=FormatBytes($MainJson->inBytes/1024);
    $outBytes=FormatBytes($MainJson->outBytes/1024);

    $requestCounterInt=$MainJson->requestCounter;
    $requestMsecCounter = $MainJson->requestMsecCounter;


    $averageSeconds=0;
    if ($requestCounterInt > 0) {
        $averageSeconds = $requestMsecCounter / $requestCounterInt / 1000;
    }

    $icoclouds="fas fa-cloud-showers";
    $icoUp=ico_upload;
    $icoDown=ico_download;
    $icocl=ico_timeout;
    $z[]="<div><small>";
    $z[]="<i class='$icoclouds'></i>&nbsp;$requestCounter";
    $z[]="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<i class='$icoDown'></i>&nbsp;$inBytes";
    $z[]="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<i class='$icoUp'></i>&nbsp;$outBytes";
    if($averageSeconds>0){
        $z[]="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<i class='$icocl'></i>&nbsp;".number_format($averageSeconds, 2)."/s";
    }
    $z[]="</small></div>";
    return @implode("",$z);

}
function td_row_status($id=0):bool{

    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    if($id==0) {
        $id = intval($_GET["td-status"]);
    }
    $q                          = new lib_sqlite(NginxGetDB());
    $sockngix                   = new socksngix($id);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$id");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?reverse-fs=yes");

    $WAF=base64_encode(td_row_waf($id));
    $status=base64_encode($tpl->_ENGINE_parse_body(td_status($ligne,$sockngix)));
    $td_saved=base64_encode($tpl->_ENGINE_parse_body(td_saved($ligne,$sockngix)));
    $BtnAction=base64_encode($tpl->_ENGINE_parse_body(td_btnAction($id)));
    $servicename=base64_encode(td_row_servicename($id));
    $servernames=base64_encode(td_row_serversnames($id));
    $ServerStats=base64_encode(td_row_serverstats($id));



    $f[]="if( document.getElementById('rcolor0-$id') ){";
    $f[]="\ttempdata=base64_decode('$status');";
    $f[]="\tdocument.getElementById('rcolor0-$id').innerHTML=tempdata;";
    $f[]="}";


    $f[]="if( document.getElementById('rcolor1-$id') ){";
    $f[]="\ttempdata=base64_decode('$td_saved');";
    $f[]="\tdocument.getElementById('rcolor1-$id').innerHTML=tempdata;";
    $f[]="}";

    $f[]="if( document.getElementById('rcolor2-$id') ){";
    $f[]="\ttempdata=base64_decode('$servicename');";
    $f[]="\tdocument.getElementById('rcolor2-$id').innerHTML=tempdata;";
    $f[]="}";

    $f[]="if( document.getElementById('rcolor4-$id') ){";
    $f[]="\ttempdata=base64_decode('$WAF');";
    $f[]="\tdocument.getElementById('rcolor4-$id').innerHTML=tempdata;";
    $f[]="}";

    $f[]="if( document.getElementById('rcolor5-$id') ){";
    $f[]="\ttempdata=base64_decode('$servernames');";
    $f[]="\tdocument.getElementById('rcolor5-$id').innerHTML=tempdata;";
    $f[]="}";

    $f[]="if( document.getElementById('rcolorStats-$id') ){";
    $f[]="\ttempdata=base64_decode('$ServerStats');";
    $f[]="\tdocument.getElementById('rcolorStats-$id').innerHTML=tempdata;";
    $f[]="}";


    $f[]="if( document.getElementById('rcolor7-$id') ){";
    $f[]="\ttempdata=base64_decode('$BtnAction');";
    $f[]="\tdocument.getElementById('rcolor7-$id').innerHTML=tempdata;";
    $f[]="}";




    if($ligne["enabled"]==1) {
        $color="";
    }else{
        $color="rgb(191, 194, 196)";
    }
    $f[]="if(document.getElementById('saved-text-$id')){";
    $f[]="\tdocument.getElementById('saved-text-$id').style.color = '$color';";
    $f[]="}";

    for ($i=1;$i<20;$i++){
        $f[]="if(document.getElementById('rcolor$i-$id')){";
        $f[]="\tdocument.getElementById('rcolor$i-$id').style.color = '$color';";
        $f[]="}";

    }
    if(!isset($_GET["no-destinations"])){
        $f[]="Loadjs('$page?td-destinations=$id&function=');";
    }

    echo @implode("\n",$f);
    return true;
}
function table_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    $_SESSION["NginxTableCurpage"]=1;
    $_SESSION["NginxTableOffset"]=0;

    if(!isset($_SESSION["NginxTableMaxRecs"])){
        $_SESSION["NginxTableMaxRecs"]=10;
    }
    $websites=$tpl->_ENGINE_parse_body("{websites}");
    $max=$tpl->_ENGINE_parse_body("{maximum}");
    $t=time();
    $options["DROPDOWN"]["TITLE"]=sprintf("<span id='selector-$t'>$max %s $websites</span>",$_SESSION["NginxTableMaxRecs"]);
    $options["DROPDOWN"]["CONTENT"]["5 $websites"]="Loadjs('$page?MaxItems=5&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["10 $websites"]="Loadjs('$page?MaxItems=10&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["15 $websites"]="Loadjs('$page?MaxItems=15&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["25 $websites"]="Loadjs('$page?MaxItems=25&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["50 $websites"]="Loadjs('$page?MaxItems=50&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["100 $websites"]="Loadjs('$page?MaxItems=100&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["150 $websites"]="Loadjs('$page?MaxItems=150&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["200 $websites"]="Loadjs('$page?MaxItems=200&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["250 $websites"]="Loadjs('$page?MaxItems=250&id=selector-$t&function=%s')";

    if(isset($_GET["MiniAdm"])){
        echo "<div style='margin-top:10px'>";
        echo $tpl->search_block($page,null,null,null,null,$options);
        echo "</div>";
        return true;
    }

    echo $tpl->search_block($page,null,null,null,null,$options);
    return true;
}
function table_MaxItems(){
    $MaxItems=$_GET["MaxItems"];
    $function=$_GET["function"];
    $_SESSION["NginxTableMaxRecs"]=$MaxItems;
    $_SESSION["NginxTableCurpage"]=1;
    $_SESSION["NginxTableOffset"]=0;
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $id=$_GET["id"];
    $websites=$tpl->_ENGINE_parse_body("{websites}");
    $max=$tpl->_ENGINE_parse_body("{maximum}");
    $t=time();
    $title=sprintf("$max %s $websites",$MaxItems);
    echo "$function();\n";
    echo "document.getElementById('$id').innerHTML='$title';";
}
function table_pagination():bool{
    $function=$_GET["function"];
// $js="Loadjs('$me?TableNavigate=$page&offset=$offset&max=$recordsPerPage&function=$function');";
    $_SESSION["NginxTableCurpage"]=$_GET["TableNavigate"];
    $_SESSION["NginxTableOffset"]=$_GET["offset"];
    $_SESSION["NginxTableMax"]=$_GET["offset"];
    echo "$function();\n";
    return true;
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $users=new usersMenus();


    $search=trim($_GET["search"]);
    $function=$_GET["function"];

    if(!isset($_SESSION["NginxTableMaxRecs"])){
        $_SESSION["NginxTableMaxRecs"]=25;
    }
    if(!isset($_SESSION["NginxTableOffset"])){
        $_SESSION["NginxTableOffset"]=0;
    }

    $nginx_reconfigure="Loadjs('fw.nginx.apply.php?serviceid=0&function=$function')";

    VERBOSE("isHarmpID:: ".isHarmpID()." ",__LINE__);

    if(isHarmpID()){
        VERBOSE("nginx_reconfigure:: hamrp.php?reconfigure-nginx",__LINE__);
        $nginx_reconfigure=$tpl->framework_buildjs(
            "hamrp.php?reconfigure-nginx={$_SESSION["HARMPID"]}",
            "harmp.nginx.{$_SESSION["HARMPID"]}.progress","harmp.nginx.{$_SESSION["HARMPID"]}.log",
            "progress-websites-restart",
            "$function();");

    }

    $ModSecurity=true;
    $NginxHTTPModSecurity       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $EnableModSecurityIngix     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    $DisableBuildNginxConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBuildNginxConfig"));
    if($NginxHTTPModSecurity==0){$ModSecurity=false;}
    if($EnableModSecurityIngix==0){$ModSecurity=false;}


    $IS_LICENSE=IS_LICENSE();
    $html[]="<table id='table-websites-main' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{saved_on}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>WAF</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{servernames}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{default}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{destination}</th>";
    if(!isHarmpID()) {
        $html[] = "<th data-sortable=false></th>";
    }

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite(NginxGetDB());
    $Types[1]="{PHP_WSITE}";
    $Types[2]="{reverse_proxy}";
    $Types[3]="{HOTSPOT_WWW}";
    $Types[4]="Web Console";
    $Types[5]="{TCP_FORWARD}";
    $Types[6]="{WEBFILTERING_ERROR_SERVICE}";
    $Types[7]="{DOH_WEB_SERVICE}";
    $Types[8]="{PROXY_PAC_SERVICE}";
    $Types[9]="{WEB_HTML_SERVICE}";
    $Types[10]="{IT_charter}";
    $Types[11]="{APP_APT_MIRROR_WEB}";
    $Types[12]="WebCopy";
    $Types[13]="ADFS 3.0";
    $Types[14]="{default_website}";
    $Types[15]="DNS Over HTTPS";
    $Types[16]="{artica_meta_server}";
    $ANDPRIVS="";
    if(!$q->FIELD_EXISTS("backends","weight")) {
        $q->QUERY_SQL("ALTER TABLE backends ADD `weight` INT NOT NULL DEFAULT 0");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }

    if(!$q->FIELD_EXISTS("backends","backup")) {
        $q->QUERY_SQL("ALTER TABLE backends ADD `backup` INT NOT NULL DEFAULT 0");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }
    if(!$q->FIELD_EXISTS("backends","down")) {
        $q->QUERY_SQL("ALTER TABLE backends ADD `down` INT NOT NULL DEFAULT 0");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }
    if(!$q->FIELD_EXISTS("backends","fail_timeout")) {
        $q->QUERY_SQL("ALTER TABLE backends ADD `fail_timeout` INT NOT NULL DEFAULT 0");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }
    if(!$q->FIELD_EXISTS("backends","max_fails")) {
        $q->QUERY_SQL("ALTER TABLE backends ADD `max_fails` INT NOT NULL DEFAULT 0");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }
    $sql="SELECT * FROM nginx_services ORDER BY zorder";
    if(!$users->AsWebMaster){
        if(count($users->NGINX_SERVICES)==0){
            echo $tpl->div_error("{error}||{ERROR_NO_PRIVS2}");
            $html[]="</tbody>";
            $html[]="";
            $html[]="</table>";
            $html[]="";
            $html[]="<script>";
            $html[]="Loadjs('$page?js-tiny=yes&function=$function')";
            $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
            $html[]="</script>";
            echo $tpl->_ENGINE_parse_body($html);
            return true;
        }
        $tql=array();
        foreach ($users->NGINX_SERVICES as $ServiceID=>$none){
            if(!is_numeric($ServiceID)){continue;}
            $tql[]="(ID=$ServiceID)";
        }

        if(count($tql)==0){
            echo $tpl->div_error("{error}||{ERROR_NO_PRIVS2}");
            $html[]="</tbody>";
            $html[]="";
            $html[]="</table>";
            $html[]="";
            $html[]="<script>";
            $html[]="Loadjs('$page?js-tiny=yes&function=$function')";
            $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
            $html[]="</script>";
            echo $tpl->_ENGINE_parse_body($html);
            return true;
        }

        $ANDPRIVS=sprintf(" AND (%s)",@implode(" OR ",$tql));
        $sql=sprintf("SELECT * FROM nginx_services WHERE (%s) ORDER BY zorder",@implode(" OR ",$tql));
    }


    $SEARCH_SSL=false;


    if(strlen($search)>0) {
        if (!$tpl->is_regex($search)) {

            if (preg_match("#(\s+)(SSL|https)#i", $search, $re)) {
                $SEARCH_SSL = true;
                $search = str_replace("{$re[1]}{$re[2]}", "", $search);
            }

            if (!is_numeric($search)) {
                $search = "*$search*";
                $search = str_replace("**", "*", $search);
                $search = str_replace(".", "\.", $search);
                $search = str_replace("*", ".*?", $search);
            } else {
                $sql = "SELECT * FROM nginx_services WHERE ID=$search$ANDPRIVS";
                $search = "";
            }
        }
    }

    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    $GLOBALS["PEITYCONF"]="{ width:200,height:25,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";

    $peity_js=array();
    $c=0;
    $cOffset=0;
    $TRCLASS=null;
    $MAX_WEB_SITES=count($results);
    $SessionTableOffset=$_SESSION["NginxTableOffset"];
    $NginxTableCurpage=$_SESSION["NginxTableCurpage"];
    $StartItems=0;
    $StopItems=99999;

    if ($SessionTableOffset>0){
        $StartItems=($NginxTableCurpage-1)*$SessionTableOffset;
        $StopItems = $NginxTableCurpage*$SessionTableOffset;
    }

    VERBOSE("SessionTableOffset=$SessionTableOffset ( Number of items per page) NginxTableCurpage=$NginxTableCurpage (page requested) Start at $StartItems Stop at $StopItems",__LINE__);

    if(strlen($search)>1) {
        VERBOSE("SEARCH = [$search]",__LINE__);
        $StartItems=0;
        $SessionTableOffset=0;
    }
    $spans=array();
$ssTyle1="style='width:1%'";
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $sockngix=new socksngix($ID);
        $md="MdNginxRule$ID";
        $badconf=null;
        $LOCK_ACTION=false;
        if(!$IS_LICENSE){$LOCK_ACTION=true;}
        if($DisableBuildNginxConfig==1){
            $LOCK_ACTION=true;
        }

        $icon_type="";
        $color=null;
        $debug_ico=null;
        $debug=intval($sockngix->GET_INFO("Debug"));
        $DestinationsPrepare[$ID]=$md;
        $WebSiteType=intval($ligne["type"]);
        $enabled=intval($ligne["enabled"]);

        if($debug==1){
            $debug_ico="&nbsp;&nbsp;<i class='fad fa-bug' style='color:#ec4758'></i>&nbsp;".
                $tpl->td_href("{debug}",null,"Loadjs('fw.nginx.sites.debug.php?siteid=$ID')");
        }

        list($serversnames,$ServerNameFields)=extract_hosts($ligne["hosts"],$ID);
        VERBOSE("$ID) Server type: {$ligne["type"]} $ServerNameFields",__LINE__);

        if($SEARCH_SSL){
            if (!preg_match("#https:\/\/#is", strip_tags($serversnames))) {continue;}
        }

        if(is_string($serversnames)) {
            $searchStrings = sprintf("%s %s", str_replace(array("\n", "<br>"), " ", strip_tags($serversnames)), $ligne["servicename"]);
        }
        if(strlen($search)>1) {
            $FIND=false;
            if (preg_match("#$search#i", $searchStrings)) {$FIND=true;}
            if(!$FIND){
                VERBOSE("#$search#i NOt found in [$searchStrings] SEARCH_SSL=$SEARCH_SSL sql=$sql",__LINE__);
                continue;
            }
        }
        VERBOSE("SessionTableOffset = $SessionTableOffset StartItems=$StartItems",__LINE__);
        $cOffset++;
        if ($StartItems>0) {
            if ($cOffset<$StartItems){
                continue;
            }
        }
        if ($cOffset>=$StopItems){
            break;
        }


        if($WebSiteType==14){
            $ligne['isDefault']=1;
        }

        $isDefault=$ligne['isDefault'];
        $is_default_icon=null;
        if($isDefault==1){$is_default_icon="<i class='fas fa-check'></i>";}

        $jsCompile="Loadjs('fw.nginx.apply.php?serviceid=$ID&function=$function&addjs=');";
        $icon_run=$tpl->icon_run($jsCompile,"AsSystemWebMaster");
        if($LOCK_ACTION){
            $icon_run=$tpl->icon_run();
        }


        if($ligne["enabled"]==0){
            $color="color:rgb(191, 194, 196);";

        }
        if($ligne["enabled"]==1){
            if($debug_ico<>null){
                $debug_ico="&nbsp;&nbsp;<i class='fad fa-bug' style='color:#cccccc'></i>";
            }
        }



        list($peity_div,$peityjs)=table_peity($ID);
        if(strlen($peityjs)>3){
            $peity_js[]=$peityjs;
        }
        $jssite=$tpl->td_href($ligne["servicename"],null,"Loadjs('$page?www-js=$ID')");
        if(!$IS_LICENSE){
            $jssite=$ligne["servicename"];
        }
        if($ligne["type"]==1){
            $icon_type="<li class='fa-brands fa-php'></li>&nbsp;";
        }


        $pleasewait="<i class=\"fas fa-sync fa-spin\" style='width:35%' ></i>&nbsp;{analyze}...</span>";
        if($enabled==0){
            $pleasewait="";
        }
        $RCOlor2="<span style='$color' id='rcolor2-$ID'>$icon_type$jssite$debug_ico</span>$peity_div";

        $TypeText=$Types[$ligne["type"]];
        $spans[]="<span id='status-$ID'></span>";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $ssTyle1><span id='rcolor0-$ID'><span></td>";
        $html[]="<td $ssTyle1 nowrap><span style='$color' id='rcolor1-$ID'></span></td>";
        $html[]="<td nowrap>$RCOlor2<span id='rcolorStats-$ID'></span></td>";
        $html[]="<td><span style='$color' id='rcolor3-$ID'></span></td>";
        $html[]="<td><span style='$color' id='rcolor4-$ID'></span></td>";
        $html[]="<td><span style='$color' id='rcolor7-$ID'></span></td>";
        $html[]="<td><span style='$color;width:35%' id='rcolor5-$ID'>$ServerNameFields</span></td>";
        $html[]="<td $ssTyle1 class='center' nowrap>$is_default_icon</td>";
        $html[]="<td $ssTyle1 nowrap><span style='$color' id='rcolor7-$ID'>$TypeText</span></td>";
        $html[]="<td nowrap><span style='$color' id='rcolor9-$ID'>$pleasewait</td>";
        if(!isHarmpID()) {
            $html[] = "<td $ssTyle1>$icon_run</td>";
        }

        $html[]="</tr>";
    }


    $html[]="</tbody>";
    $html[]=table_footer($MAX_WEB_SITES);
    $html[]="</table>";
    $html[]=@implode("",$spans);
    $html[]="";
    $html[]="<script>";
    $html[]="Loadjs('$page?js-tiny=yes&function=$function')";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="function NgixSitesReload(){ $function(); }";
    $html[]="function NgixSitesReconfigure(){ $nginx_reconfigure;}";

    if(count($peity_js)>0){
        $html[]=@implode("\n",$peity_js);
    }
    $html[]=sprintf("Loadjs('$page?destinations-prepare=%s&function=$function')",base64_encode(serialize($DestinationsPrepare)));
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function td_btnAction($ID){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $md="MdNginxRule$ID";
    $q= new lib_sqlite(NginxGetDB());

    $ligne=$q->mysqli_fetch_array("SELECT MaintenanceSite,enabled FROM nginx_services WHERE ID=$ID");
    $MaintenanceSite=$ligne["MaintenanceSite"];
    $enabled=$ligne["enabled"];



    if($enabled==1){
        $ico=ico_check;
        $label_class="btn-primary";
        $filters["{active2}"]="$ico:color:green||Loadjs('$page?enable=$ID');";
    }else{
       $label_class="btn-default";
        $ico=ico_disabled;
        $filters["{inactive}"]="$ico:color:grey||Loadjs('$page?enable=$ID');";
    }
    if($MaintenanceSite==0) {
        list($title, $cont) = td_btnCache($enabled, $ID);
        if (strlen($title) > 0) {
            $filters[$title] = $cont;
        }
        list($title, $cont) = td_btnPagespeed($enabled, $ID);
        if (strlen($title) > 0) {
            $filters[$title] = $cont;
        }
    }


    $filters["BUTTON"]=array("type"=>"xs","NoCleanJs"=>"yes","GLOBAL_CLASS"=>$label_class);
    $filters["{export}"]="fas fa-file-archive||Loadjs('fw.nginx.export.php?ID=$ID');";
    $filters["{duplicate}"]="fa fa-copy||Loadjs('$page?duplicate-js=$ID');";
    $filters["SPACER"]=true;
    list($title,$cont)=td_btnMaintenance($enabled,$MaintenanceSite,$ID);
    if(!is_null($title)) {
        if (strlen($title) > 0) {
            $filters[$title] = $cont;
        }
    }

    $filters["{delete_this_rule}"]=ico_trash.":color:red||Loadjs('$page?delete=$ID&md=$md');";

    return $tpl->button_dropdown_table("{actions}",$filters,"AsCertifsManager");
}
function IS_LICENSE():bool{
    VERBOSE("IS_LICENSE",__LINE__);
    $IS_LICENSE=true;
    $LICJSON=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/count/license"));
    if (json_last_error()== JSON_ERROR_NONE) {
        if(!property_exists($LICJSON,"ActiveRules")){
            return true;
        }

        if (intval($LICJSON->ActiveRules)<2){
            return true;
        }
       return $LICJSON->Status;
    }
    return $IS_LICENSE;
}
function td_row_sslcertificate($id):string{
    $sockngix = new socksngix($id);
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ssl_certificate = $sockngix->GET_INFO("ssl_certificate");
    $labeldanger="label-danger";
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM nginx_services WHERE ID=$id");
    $enabled=intval($ligne["enabled"]);
    if($enabled==0){
        $labeldanger="label-default";
    }

    if ($ssl_certificate == null) {
        return "";
    }
    $squid_reverse = new squid_reverse();
    if (!isset($GLOBALS["SSLCERTIFICATES"])) {
        $GLOBALS["SSLCERTIFICATES"] = $squid_reverse->ssl_certificates_list();
    }
    $sslcertificates = $GLOBALS["SSLCERTIFICATES"];
    $sslcertificates["__DEFAULT__"] = true;

    if (!isset($sslcertificates[$ssl_certificate])) {
        $CertName=$tpl->td_href($ssl_certificate,null,"Loadjs('fw.nginx.sites.php?www-parameters-ssl-js=$id')");
        return $tpl->_ENGINE_parse_body( "<br><span class='label $labeldanger'>{missing} {certificate}: <strong>$CertName</strong></span>");
    }

    VERBOSE("ssl_certificate=$ssl_certificate",__LINE__);
    if(preg_match("#^SUB:[0-9]+#", $ssl_certificate)) {
        $ssl_certificate=$sslcertificates[$ssl_certificate];
    }
    VERBOSE("ssl_certificate=$ssl_certificate",__LINE__);
    $zProtos[strtolower("TLSv1")] = "<span class='text-warning'>TLSv1</span>&nbsp;";
    $zProtos[strtolower("TLSv1.1")] = "<span class='text-warning'>TLSv1.1</span>&nbsp;";
    $zProtos[strtolower("TLSv1.2")] = "<span class='text-warning'>TLSv1.2</span>&nbsp;";
    $zProtos[strtolower("TLSv1.3")] = "<span class='text-success'>TLSv1.3</span>&nbsp;";

    $ssl_certificates = array();
    $CertName=$tpl->td_href($ssl_certificate,null,"Loadjs('fw.nginx.sites.php?www-parameters-ssl-js=$id')");
    $ssl_certificates[] =$tpl->_ENGINE_parse_body( "<br><small>{certificate}:$CertName&nbsp;");
    $AllowOldSSLProtocols = intval($sockngix->GET_INFO("AllowOldSSLProtocols"));
    if ($AllowOldSSLProtocols == 0) {
        $ssl_certificates[] = "&nbsp;<span class='text-success'>TLSv1.3</span>";
    } else {
        $ssl_protocols = $sockngix->GET_INFO("ssl_protocols");
        if ($ssl_protocols == null) {
            $ssl_protocols = "TLSv1.2 TLSv1.3";
        }
        $zssl_protocols = explode(" ", $ssl_protocols);
        foreach ($zssl_protocols as $sproto) {
            $sproto = trim($sproto);
            if ($sproto == null) {
                continue;
            }
            $ssl_certificates[] = $zProtos[strtolower($sproto)];
        }

    }

 return @implode("",$ssl_certificates)."</small>";


}
function td_row_serversnames($id):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if($id==0) {
        $id = intval($_GET["td-status"]);
    }
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT type,hosts FROM nginx_services WHERE ID=$id");
    $ServerType=$ligne["type"];
    if($ServerType==5){
        return td_row_serversnames_stream();
    }


    list($serversnames,$ServerNameFields)=extract_hosts($ligne["hosts"],$id);
    return $tpl->_ENGINE_parse_body($ServerNameFields);
}
function td_row_serversnames_stream($id):string{
    $f=array();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$id'");
    if(!$q->ok){return "";}
    foreach ($results as $index=>$ligne) {
        $options = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
        $interface = $ligne["interface"];
        if ($interface == null) {
            $NICNAME = "{all}";
        } else {
            $eth = new system_nic($interface);
            $NICNAME = $eth->NICNAME;
        }
        $proto = "tcp";

        $port = $ligne["port"];
        if ($options["udp"] == 1) {
            $proto = "udp";
        }
        $f[]="<div>$proto://$NICNAME:$port</div>";

    }
    return @implode("",$f);
}
function td_row_servicename($id=0):string{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    if($id==0) {
        $id = intval($_GET["td-status"]);
    }
    if($id==0){
        return "";
    }

    $q                          = new lib_sqlite(NginxGetDB());
    $sockngix                   = new socksngix($id);
    $ligne=$q->mysqli_fetch_array("SELECT enabled,badconf,servicename FROM nginx_services WHERE ID=$id");
    $enabled=$ligne["enabled"];
    $badconflength=strlen($ligne["badconf"]);
    $debug=intval($sockngix->GET_INFO("Debug"));
    $debug_ico="&nbsp;";
    $badconf="";
    $textdanger="text-danger";
    $labelfanger="label-danger";
    if(!isset($ligne["servicename"])){
        $ligne["servicename"]="Unknown";
    }
    $jssite=$tpl->td_href($ligne["servicename"],null,"Loadjs('$page?www-js=$id')");
    if(!IS_LICENSE()){
        $jssite=$ligne["servicename"];
    }


    if($enabled==1) {
        if ($debug == 1) {
            $debug_ico = $tpl->_ENGINE_parse_body("&nbsp;&nbsp;<i class='fad fa-bug' style='color:#ec4758'></i>&nbsp;" .
                $tpl->td_href("{debug}", null, "Loadjs('fw.nginx.sites.debug.php?siteid=$id')"));
        }
    }

    if($enabled==0){
        $textdanger="text-default";
        $labelfanger="label-default";
    }

    $DenyAccess=intval($sockngix->GET_INFO("DenyAccess"));

    if($badconflength>10){
        $badconf="<br><small class=$textdanger>".$tpl->td_href("{bad_configuration}",null,"Loadjs('$page?badconf=$id');")."</small>";
    }

    if($DenyAccess==1){
        $badconf=$badconf."&nbsp;<span class='label $labelfanger'>{deny_access}</span>";
    }

    $ssl_certificate=td_row_sslcertificate($id);
    return $tpl->_ENGINE_parse_body("$jssite$badconf$ssl_certificate$debug_ico");

}
function table_footer($totalRecords):string{
    $currentPage=1;
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $PageText=$tpl->_ENGINE_parse_body("{page}");
    $me=CurrentPageName();
    if(isset($_SESSION["NginxTableCurpage"])){
        $currentPage=$_SESSION["NginxTableCurpage"];
    }
    $function=$_GET["function"];
    $recordsPerPage=$_SESSION["NginxTableMaxRecs"];
    $totalPages = ceil($totalRecords / $recordsPerPage);

    $f[]="<tfoot>";
    $f[]="<tr>";
    $f[]="<td colspan='17'>";


    $f[]="<div class=\"dataTables_paginate paging_simple_numbers\" id=\"DataTables_Table_0_paginate\" style='text-align:right'>";
    $f[]="	<ul class=\"pagination\">";
    for ($page = 1; $page <= $totalPages; $page++) {

    $class="";
    if($page==$currentPage){
        $class="active";
    }
        $offset = ($page - 1) * $recordsPerPage;
    if($offset>$totalRecords){
        continue;
    }
    $js="Loadjs('$me?TableNavigate=$page&offset=$recordsPerPage&max=$recordsPerPage&function=$function');";

    $f[]="		<li class=\"paginate_button $class\" style='text-transform:capitalize'>";
    $f[]="			<a href=\"#\" OnClick=\"$js\">&laquo;&laquo;&nbsp;$PageText $page&nbsp;&raquo;&raquo;</a>";
    $f[]="		</li>";


    }
    $f[]="		</ul>";
    $f[]="</div>";
    $f[]="</td>";
    $f[]="</tr>";
    $f[]="</tfoot>";
    return @implode("\n",$f);

}
function table_peity($ID):array{
    $date=date("Y-m-d H:i:s",strtotime('-12 hours'));
    $q=new postgres_sql();
    $field_time="date_trunc('hour', zdate) + INTERVAL '10 minute' * floor(EXTRACT(MINUTE FROM zdate) / 10) as hour_formatted";
    $sql="SELECT SUM(requestcounter) as tsum,$field_time FROM nginx_stats WHERE serviceid=$ID AND requestcounter>0 AND zdate>'$date' GROUP BY hour_formatted";
    VERBOSE("$ID:$sql",__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        VERBOSE("$ID:$q->mysql_error",__LINE__);
    }

    $xdata=array();
    VERBOSE("$ID: ".pg_num_rows($results)." records",__LINE__);

    if($results) {
        while ($ligne = @pg_fetch_assoc($results)) {
            $xdata[] = $ligne["tsum"];
            VERBOSE("$ID:{$ligne["tsum"]}", __LINE__);
        }
    }
    if(count($xdata)==0) {
        return array("", "");
    }
    $peity_conf=$GLOBALS["PEITYCONF"];
    $peity_div = "<div style='margin-top:5px' 
		onMouseOver=\"this.style.cursor='pointer'\" 
		OnMouseOut=\"this.style.cursor='default'\"
		onclick=\"Loadjs('fw.nginx.metrics.php?serviceid=$ID')\"><span id=\"nginx-sites-rqs-$ID\">" . @implode(",",$xdata) . "</div></span>";
        $peity_js = "\t$(\"#nginx-sites-rqs-$ID\").peity(\"line\",$peity_conf);";
    return array($peity_div,$peity_js);
}
function td_saved($ligne,$sockngix):string{
    $ID = $ligne["ID"];
    $tpl=new template_admin();
    $MAIN_REVERSED=MAIN_REVERSED();
    if ($ligne["enabled"] == 0) {
        return $tpl->icon_nothing();
    }
    if (!isset($MAIN_REVERSED[$ID])) {
        return $tpl->icon_nothing();
    }
    return distanceOfTimeInWords($MAIN_REVERSED[$ID]["TIME"],time());
}
function MAIN_REVERSED():array{

    if(isset($GLOBALS["MAIN_REVERSED"])){
        return $GLOBALS["MAIN_REVERSED"];

    }
    $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/harmp/nginx/reversed/confs"));
    $GLOBALS["MAIN_REVERSED"]=array();
    if(!$data->Status){
        return array();
    }
    if(!property_exists($data,"SitesID")){
        return array();
    }
    if(!is_null($data->SitesID)) {
        foreach ($data->SitesID as $site) {
            if (!property_exists($site, "siteid")) {
                continue;
            }
            $GLOBALS["MAIN_REVERSED"][$site->siteid]["TIME"] = $site->filetime;
        }
    }
    return $GLOBALS["MAIN_REVERSED"];
}
function td_status($ligne,$sockngix):string{
    VERBOSE("-------------------------- START STATUS --------------------------", __LINE__);
    $page = CurrentPageName();
    $ssl_certificate = $sockngix->GET_INFO("ssl_certificate");
    $tpl = new template_admin();
    $ID = $ligne["ID"];

    if (!isset($GLOBALS["SSLCERTIFICATES"])) {
        $squid_reverse = new squid_reverse();
        $GLOBALS["SSLCERTIFICATES"] = $squid_reverse->ssl_certificates_list();
    }
    $MaintenanceSite=$ligne["MaintenanceSite"];
    $goodconf_js = "Loadjs('$page?goodconf=$ID')";
    $sslcertificates = $GLOBALS["SSLCERTIFICATES"];
    $sslcertificates["__DEFAULT__"] = true;
    $fname="/usr/share/artica-postfix/ressources/databases/ReverseProxy/$ID.json";
    if ($ligne["enabled"] == 0) {
        return "<span class='label label-default'>#$ID {disabled}</span>";
    }

    if($MaintenanceSite==1){
        return $tpl->td_href("<span class='label label-warning'>#$ID {maintenance}</span>", null, $goodconf_js);
    }

    $MAIN_REVERSED=MAIN_REVERSED();

    if ($ligne["type"] == 13) {
        if ($ssl_certificate == null) {
            $js = "Loadjs('fw.nginx.sites.php?www-parameters-ssl-js=$ID')";
            return $tpl->td_href("<span class='label label-danger'>#$ID {no_certificate}</span>", null, $js);
        }
        $q = new lib_sqlite(NginxGetDB());
        $sligne = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM backends WHERE serviceid='$ID'");
        $tcount = intval($sligne["tcount"]);
        if ($tcount == 0) {
            return $tpl->td_href("<span class='label label-danger'>#$ID no backend</span>", null, $goodconf_js);
        }

        $HostHeader = trim($sockngix->GET_INFO("HostHeader"));
        if ($HostHeader == null) {
            return $tpl->td_href("<span class='label label-danger'>{HostHeader}</span>", null, $goodconf_js);
        }
        if (!isset($MAIN_REVERSED[$ID])) {
            VERBOSE("MAIN_REVERSED[$ID] === NONE not_saved !!!", __LINE__);
            return $tpl->td_href("<span class='label label-danger'>#$ID {not_saved}</span>", null, $goodconf_js);
        }
        return $tpl->td_href("<span class='label label-primary'>#$ID OK</span>", null, $goodconf_js);

    }

    if ($ssl_certificate <> null) {
        if (!isset($sslcertificates[$ssl_certificate])) {
            return "<span class='label label-danger'>#$ID {error}</span>";
        }
    }

    if (!isset($MAIN_REVERSED[$ID])) {
        VERBOSE("MAIN_REVERSED[$ID] === NONE not_saved !!!", __LINE__);
        return $tpl->td_href("<span class='label label-danger'>#$ID {not_saved}</span>", null, $goodconf_js);
    }
    if(is_file($fname)){
        $json=json_decode(file_get_contents($fname));
        if(property_exists($json,"FrontendErrDetail")) {
            $FrontendErrDetail = base64_decode($json->FrontendErrDetail);
            $FrontendErr = $json->FrontendErr;
            if ($FrontendErr == 1) {
                return $tpl->td_href("<span class='label label-danger'>#$ID $FrontendErrDetail</span>",
                    null, "Loadjs('fw.nginx.frontend.error.php?serviceid=$ID')");
            }
        }
    }
    return $tpl->td_href("<span class='label label-primary'>#$ID OK</span>", null, $goodconf_js);
}
function extract_backends($serviceid):string{
    include_once(dirname(__FILE__)."/ressources/class.nginx.reverse.http.inc");
    $sock=new socksngix($serviceid);
    $UseSSL=intval( $sock->GET_INFO("UseSSL"));

    $ForwardServersDynamics =   intval($sock->GET_INFO("ForwardServersDynamics"));
    if($ForwardServersDynamics==1){
        $FSDynamicsExt = intval($sock->GET_INFO("FSDynamicsExt"));
        $FSDynamicsDst = trim($sock->GET_INFO("FSDynamicsDst"));
        $proto="http";
        if($UseSSL==1){$proto="https";}
        if($UseSSL==0){$proto="http";}
        if($FSDynamicsExt==1){
            if(preg_match("#\.(.*?)$#",$FSDynamicsDst,$re)){
                $FSDynamicsDst=str_replace($re[1],"*",$FSDynamicsDst);
            }else{
                $FSDynamicsDst=$FSDynamicsDst.".*";
            }
        }
        return "$proto://*.$FSDynamicsDst";

    }


    $UseSSL=intval( $sock->GET_INFO("UseSSL"));
    $HostHeader=trim($sock->GET_INFO("HostHeader"));
    $HostHeader=tool_nginx_clean_uri($HostHeader);
    if($HostHeader<>null){$HostHeader=" ($HostHeader)";}

    $RemotePath=$sock->GET_INFO("RemotePath");
    if(strlen($RemotePath)<2){$RemotePath=null;}

    if($RemotePath<>null) {
        if (!preg_match("#^/#", $RemotePath)) {
            $RemotePath = "/$RemotePath";
        }
        if (!preg_match("#/$#", $RemotePath)) {
            $RemotePath = "$RemotePath/";
        }
    }

    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM backends WHERE serviceid='$serviceid'");
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ServiceType=get_ServiceType($serviceid);

    $T=array();
    $error_proto=null;
    foreach ($results as $md5=>$ligne) {
        $ID = intval($ligne["ID"]);
        $port = $ligne["port"];
        $hostname = $ligne["hostname"];
        $ssl=intval($ligne["ssl"]);
        $proto="http";



        if($port==443){
            $ssl=1;
        }
        if($ssl==1){
            $proto="https";
        }
        if(preg_match("#^http.*?:#i",$hostname)){
            $parse_url=parse_url($hostname);
            $hostname=$parse_url["host"];
            $proto="http";
        }
        if(preg_match("#^https.*?:#i",$hostname)){
            $parse_url=parse_url($hostname);
            $hostname=$parse_url["host"];
            $proto="https";
        }
        if ($ServiceType==15){
            $proto="dns";
        }
        VERBOSE("Table backends service=$serviceid ID=$ID,port=$port,hostname=$hostname,proto=$proto,ssl=$ssl",__LINE__);
        $js="Loadjs('fw.nginx.backends.php?id-js=$ID')";
        $T[]=$tpl->td_href("$proto://{$hostname}:{$port}{$RemotePath}",null,$js);

    }
    if(count($T)==0){
        $no_backend_server_defined=$tpl->td_href("{no_backend_server_defined}",null,"Loadjs('fw.nginx.backends.php?id-js=0&serviceid=$serviceid&md5=')");
        $T[]="<small class='text-danger' style='font-size:11px'>
            <i class=\"fas fa-exclamation-triangle\"></i> $no_backend_server_defined</small>";
    }
    if($error_proto<>null){
        $T[]= $error_proto;
    }

    return @implode("<br>",$T);
}
function extract_hosts($hosts,$serviceid):array{
    $f=array();
    $nginxsock=new socksngix(0);
    $Zhosts=array();
    $NginxProxyProtocol=$nginxsock->GET_INFO("NginxProxyProtocol");
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    if(!is_null($hosts)) {
        $Zhosts = explode("||", $hosts);
    }
    $sockngix=new socksngix($serviceid);
    $Type=$sockngix->GetType();

    if($Type==5){
        $z=td_row_serversnames_stream($serviceid);
        return array($z,$z);
    }

    VERBOSE("extract_hosts($serviceid): Server Type: $Type",__LINE__);
    if($Type==14){
        return array("http(s)://*/","http(s)://*/");
    }


    $ssl_certificate=$sockngix->GET_INFO("ssl_certificate");
    $Redirect80To443=intval($sockngix->GET_INFO("Redirect80To443"));
    $MAIN_PORTS=array();

    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$serviceid'");
    foreach ($results as $md5=>$ligne){
        $port=intval($ligne["port"]);
        if($port==0){continue;}
        $interface=$ligne["interface"];
        $MAIN_PORTS["$interface:$port"]=array(
            "port"=>$port,
            "options"=>$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"])
        );
    }

    if(count($MAIN_PORTS)==0){
        $f[]="<div><small class='text-danger'><i class=\"fas fa-exclamation-triangle\"></i> {no_backend_port_defined}</small></div>";
    }

    $missing_cert="<span class='label label-warning'>{missing_certificate}</span>";


    $ForwardServersDynamics =   intval($sockngix->GET_INFO("ForwardServersDynamics"));
    $FSDynamicsExt          =   intval($sockngix->GET_INFO("FSDynamicsExt"));
    $FSDynamicsSrc          =   trim($sockngix->GET_INFO("FSDynamicsSrc"));

    if($ForwardServersDynamics==1){
        if($FSDynamicsExt==1){
            if(preg_match("#\.(.*?)$#",$FSDynamicsSrc,$re)){
                $FSDynamicsSrc=str_replace(".".$re[1],".*",$FSDynamicsSrc);
            }else{
                $FSDynamicsSrc=$FSDynamicsSrc.".*";
            }
        }
        $Zhosts[]="*.$FSDynamicsSrc";
    }

    $ServerNameFields=array();

    VERBOSE("ZHOSTS === ".count($Zhosts)." ITEMS",__LINE__);
    foreach ($Zhosts as $servername){
        $catch_all=null;
        $servername=trim($servername);
        VERBOSE("Host === [$servername]",__LINE__);
        if($servername==null){continue;}
        if($servername=="*"){
            $servername=".*";
            $catch_all="&nbsp;&nbsp;<span class='label label-warning'>{catch_all}</span>";
        }

        foreach ($MAIN_PORTS as $index=>$array){
            $port=$array["port"];

            if($Redirect80To443==1){
                if($port==80){
                    $port=$port."&nbsp;<i class=\"fas fa-arrow-to-right\"></i>&nbsp;443";
                }
            }
            if(!isset($array["options"])){ $array["options"]=array(); }
            if(!isset($array["options"]["ssl"])){ $array["options"]["ssl"]=0; }
            $options=$array["options"];
            $proto="http";

            if($port==443){
                $proto="https";
                $options["ssl"]=1;
            }

            if($options["ssl"]==1){
                $proto="https";
                if($ssl_certificate==null){$proto="$missing_cert&nbsp;$proto";}
            }
            if($NginxProxyProtocol==0){$options["proxy_protocol"]=0;}

            if($NginxProxyProtocol==1) {
                if(!isset($options["proxy_protocol"])) {$options["proxy_protocol"]=1;}
            }

            if (isset($options["proxy_protocol"])) {
                if (intval($options["proxy_protocol"]) == 1) {
                    $proto = "<strong>TCP PROXY&nbsp;|&nbsp;</strong>$proto";
                }
            }

            if(count($ServerNameFields)<3){
                $ServerNameFields[]="<div style='margin-top:5px'><small>$proto://$servername:$port</small>$catch_all</div>";
            }

            $f[]="<div style='margin-top:5px'><small>$proto://$servername:$port</small>$catch_all</div>";
        }

    }

    if(count($f)==0){
        VERBOSE("NO DOMAIN SPECIFIED !!",__LINE__);
        $error_no_domain_specified=$tpl->td_href("{error_no_domain_specified}",null,"Loadjs('fw.nginx.sites.php?www-host-edit=&service-id=$serviceid')");
        $ServerNameFields[]="<div>
            <strong class='text-danger'><i class=\"fas fa-exclamation-triangle\" ></i> $error_no_domain_specified</strong>
          </div>";
        return array(array(),@implode("",$ServerNameFields));
    }
    if(count($f)==0){return array("","");}
    return array(@implode("", $f),@implode("",$ServerNameFields));
}
function stream_ports($serviceid):string{
    $f=array();
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$serviceid'");
    foreach ($results as $md5=>$ligne){
        $interface=$ligne["interface"];
        if($interface==null){$interface="{all}";}
        $port=$ligne["port"];
        $f[]="$interface:$port";

    }
    if(count($f)==0){return "";}
    return @implode("<br>", $f);
}
function backendsOf($serviceid):string{
    $f=array();
    VERBOSE("Backend of $serviceid ----------------------------------------",__LINE__);
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM backends WHERE serviceid='$serviceid'");
    foreach ($results as $md5=>$ligne){
        $hostname=$ligne["hostname"];
        VERBOSE("Backend of $serviceid = $hostname",__LINE__);
        if(preg_match("#^http.*?:#i",$hostname)){
            $parse_url=parse_url($hostname);
            $hostname=$parse_url["host"];
        }

        $port=$ligne["port"];
        $f[]="$hostname:$port";

    }
    return @implode("<br>", $f);

}
function td_btnCacheDisk($ID):array{
    $sock=new socksngix($ID);
    $cgicache=intval($sock->GET_INFO("cgicache"));
    $prefix="fw.nginx.sites.optimize.php";

    $NginxSitesCacheSize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxSitesCacheSize");
    $NginxSitesCacheSizeArray=unserialize($NginxSitesCacheSize);
    if(!isset($NginxSitesCacheSizeArray[$ID])){
        $NginxSitesCacheSizeArray[$ID]=0;
    }
    $bytes=$NginxSitesCacheSizeArray[$ID];
    $Icon=ico_hd;
    if($bytes>1024){
        $size=FormatBytes($bytes/1024);

        return array("{cache} ($size)","$Icon:color:green||Loadjs('fw.nginx.cachesobject.php?serviceid=$ID');");
    }
    if($cgicache==0) {

        return array("{cache} ({inactive2})","$Icon:color:grey||Loadjs('$prefix?cgicache=1&serviceid=$ID');");

    }
    return array("{cache} ({active2})","$Icon:color:green||Loadjs('$prefix?cgicache=0&serviceid=$ID');");


}
function td_btnMaintenance($enabled,$MaintenanceSite,$ID):array{
    $page=CurrentPageName();
    $ID=intval($ID);
    if($ID==0){
        return array("","");
    }

    $Icon="fas fa-construction";

    if($enabled==0) {
        return array("{maintenance} ({disabled})","$Icon:color:black||Loadjs('$page?MaintenanceSite=$ID')");
    }
    if($MaintenanceSite==1){
        return array("{maintenance} ({active2})","$Icon:color:yellow||Loadjs('$page?MaintenanceSite=$ID')");

    }
    return array("{maintenance} ({inactive})","$Icon:color:grey||Loadjs('$page?MaintenanceSite=$ID')");

}
function td_btnCache($enabled,$ID):array{
    $page=CurrentPageName();
    $ID=intval($ID);
    if($ID==0){
        return array("","");
    }

    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $sock=new socksngix($ID);
    $Type=$sock->GetType();
    if($Type==4){
        return array("","");
    }
    if($Type==14){
        return array("","");
    }
    if($enabled==0){
        return array("","");
    }

    if(!isset($GLOBALS["nginxCachesDir"])){
        $nginxsock=new socksngix(0);
        $GLOBALS["nginxCachesDir"]=intval($nginxsock->GET_INFO("nginxCachesDir"));
    }
    if($GLOBALS["nginxCachesDir"]==1){
        return td_btnCacheDisk($ID);
    }
    $Icon=ico_hd;
    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    $stattuscgicache= $tpl->label_click("grey","{disabled}","");
    if($NginxCacheRedis==0){
        return array("{cache} ({not_available})","$Icon:color:grey||blur()");
    }



    $cgicache=intval($sock->GET_INFO("cgicache"));
    if($cgicache==0) {
        return array("{cache} ({inactive})","$Icon:color:grey||Loadjs('$page?cache-settings-js=$ID');");


    }
    return array("{cache} ({active2})","$Icon:color:green||Loadjs('$page?cache-settings-js=$ID');");


}
function destinations_artica():string{
    $LighttpdArticaListenInterface  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $ipaddr="127.0.0.1";
    if($LighttpdArticaListenInterface<>null){
        $ipaddr=$LighttpdArticaListenInterface;
        if($LighttpdArticaListenInterface=="lo"){$ipaddr="127.0.0.1";}
    }
    if($ipaddr==null){$ipaddr="127.0.0.1";}

    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
    $method="http";
    if($ArticaHttpUseSSL==1){$method="https";}
    $finalURI="$method://$ipaddr:$ArticaHttpsPort";
    return $finalURI;
}
function destinations_prepare():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    header("content-type: application/x-javascript");
    $data=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["destinations-prepare"]);
    $t=time();
    $Timeout=1000;
    $f=array();
    foreach ($data as $ID=>$md){
        $Timeout=$Timeout+50;
        $idDiv="rcolor9-$ID";

        $fname="/usr/share/artica-postfix/ressources/databases/ReverseProxy/$ID.json";
        if(!is_file($fname)){
            continue;
        }
        $json=json_decode(file_get_contents($fname));
        if(!is_object($json)){
            continue;
        }
        if(!property_exists($json,"Type")){
            continue;
        }
        $f[]="// $ID Type = $json->Type";
        if($json->Type==4){
            $text=base64_encode(destinations_artica());
            $f[]="tempdata=base64_decode('$text');";
            $f[]="\tif( document.getElementById('$idDiv') ){";
            $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
            $f[]="}\n";
            continue;
        }


        $pl="<i class=\"fas fa-sync fa-spin\" style='color: #1ab394'></i>&nbsp;{please_wait}...";
        $text=base64_encode($tpl->_ENGINE_parse_body($pl));
        $f[]="function DestinationsPrepare$t$ID(){";
        $f[]="\tif( document.getElementById('$idDiv') ){";
        $f[]="\t\ttempdata=base64_decode('$text');";
        $f[]="\t\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="\t\tLoadjs('$page?td-destinations=$ID&function=$function&md=$md');";
        $f[]="\t}";
        $f[]="}\n";
        $f[]="setTimeout(\"DestinationsPrepare$t$ID()\",$Timeout);\n\n";
        $f[]="";
    }


    echo @implode("\n",$f);
    return true;
}
