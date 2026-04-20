<?php
$GLOBALS["PEITYCONF"]="{ width:280,height:25,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
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


if(isset($_GET["www-parameters-security-js"])){www_parameters_section_js("security");exit;}
if(isset($_GET["www-parameters-ssl-js"])){www_parameters_section_js("ssl");exit;}
if(isset($_GET["www-parameters-general-popup"])){www_parameters_general_popup();exit;}
if(isset($_GET["www-parameters-security-popup"])){www_parameters_security_popup();exit;}
if(isset($_GET["www-parameters-ssl-popup"])){www_parameters_ssl_popup();exit;}

if(isset($_GET["restart-needed"])){restart_needed_js();exit;}
if(isset($_POST["restart-needed"])){restart_needed_perform();exit;}

if(isset($_GET["doh-params"])){doh_parameters();exit;}

if(isset($_GET["duplicate-js"])){duplicate_js();exit;}
if(isset($_POST["duplicate-from"])){duplicate_perform();exit;}

if(isset($_POST["ID"])){www_save();exit;}
if(isset($_POST["doh-params"])){doh_parameters_save();exit;}

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
    $MAIN_RT_USERS=array();
    if(!isset($GLOBALS["MAIN_RT_USERS"])){
        $ThisInf=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/clients/counts"),true);
        if(isset($ThisInf["services"])){
            foreach ($ThisInf["services"] as $num=>$ligneUsers){
                VERBOSE("MAIN_RT_USERS: {$ligneUsers["service_id"]} = {$ligneUsers["clients_count"]}",__LINE__);
                $MAIN_RT_USERS[$ligneUsers["service_id"]]=$ligneUsers["clients_count"];
            }
            $GLOBALS["MAIN_RT_USERS"]=$MAIN_RT_USERS;
        }else{
            $GLOBALS["MAIN_RT_USERS"]=array();
            VERBOSE("MAIN_RT_USERS: NO!!",__LINE__);
        }
    }


    foreach ($tb as $sID){
        $ID=intval($sID);
        if($ID==0){continue;}
        $ServerStats=base64_encode(td_row_serverstats($ID,$GLOBALS["MAIN_RT_USERS"]));
        $f[]="if( document.getElementById('rcolorStats-$ID') ){";
        $f[]="\ttempdata=base64_decode('$ServerStats');";
        $f[]="\tdocument.getElementById('rcolorStats-$ID').innerHTML=tempdata;";
        $f[]="}";

        $f[]="if(document.getElementById('destination-$ID-builded')){";
        $f[]="\tLoadjs('$page?td-destinations=$ID&function=');";
        $f[]="}else{";
        $f[]="if(document.getElementById('backend-analyze-$ID')){";
        $f[]="\tLoadjs('$page?td-destinations=$ID&function=');";
        $f[]="}";
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
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/sites/disable-all", []);
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
    $fromid=intval($_POST["duplicate-from"]);
    $new_servicename=$_POST["duplicate-name"];

    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$fromid/duplicate", [
        "servicename" => $new_servicename
    ]),true);

    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        echo $err;
        return false;
    }
    $new_serviceid=intval($result["ID"]);
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($new_serviceid);
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

    $ID=intval($_GET["enable"]);
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/enable", []),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        $tpl->js_mysql_alert($err);
        return false;
    }
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    $text=($result["enabled"]==1) ? "Enable Web service $servicename" : "Disable Web service $servicename";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
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
    $ID=intval($_POST["goodconf_id"]);
    $tpl->CLEAN_POST();
    $goodconf=base64_encode($_POST["goodconf"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/update-service", [
        "goodconf" => $goodconf,
        "goodconftime" => 0,
        "badconf" => ""
    ]);
}

function delete():bool{
    $ID = intval($_POST["delete"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();

    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/delete", []),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        echo $err;
        return false;
    }
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



function www_tabs():bool{
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
        $array["<i class='$ico_hearth'></i> {servernames}"] = "fw.nginx.servicenames.php?start=$ID";
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
    return true;
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
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
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
    $ID=intval($_GET["monitored-frontend"]);
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    $monitored=intval($ligne["monitored"]);
    $servicename=$sock->GetServiceName();
    $monitored=($monitored==0) ? 1 : 0;

    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/update-service", [
        "monitored" => $monitored
    ]),true);
    if(!is_array($result) || !$result["Status"]){
        $tpl=new template_admin();
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        return $tpl->js_error($err);
    }
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');";
    if($monitored==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sla/frontend/remove/$ID");
    }
    return admin_tracks("Set reverse-proxy Cloud monitoring to $monitored for $servicename");
}
function www_parameters2_vitrification($tpl,$ID){

    $Vitrification=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Vitrification"));
    if($Vitrification==0) {
        return $tpl;
    }
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    $tpl->table_form_field_js("Loadjs('fw.nginx.sites.vitrification.php?js=$ID')");
    if($ligne["vitrification_enabled"]==0) {
        $tpl->table_form_field_bool("{vitrification}",0,"fas fa-wine-glass");
        return $tpl;
    }
    $tpl->table_form_field_bool("{vitrification}",1,"fas fa-wine-glass");
    return $tpl;

}
function www_parameters2_SignedJSBC($tpl,$ID){
    $sockngix=new socksngix($ID);
    $page=CurrentPageName();
    $JsBC=intval($sockngix->GET_INFO("JsBC"));
    $js="Loadjs('fw.nginx.sites.JsBC.php?serviceid=$ID');";
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

function www_parameters2_trap_files($tpl,$ID){
    $q=new lib_sqlite(NginxGetDB());
    $page=CurrentPageName();
    $sockngix=new socksngix($ID);
    $EnableTrapFiles=intval($sockngix->GET_INFO("EnableTrapFiles"));
    $tpl->table_form_field_js("Loadjs('fw.nginx.trapfiles.php?service-js=$ID')","AsWebMaster");
    if($EnableTrapFiles==0){
        $tpl->table_form_field_bool("{trap_files}",0, ico_file);
        return $tpl;
    }
    $data       = json_decode($sockngix->GET_INFO("trap_files"),true);
    if(!is_array($data)){$data=array();}
    $tpl->table_form_field_text("{trap_files}",count($data)." {files}",ico_file);

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
    $Types[16]="{APP_DEBIAN_NETWORK_AGENT}";

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
    $tpl->table_form_field_text("{service_name2}", $LowerCase.implode(", ",$names)."</span>$goodconftime_text {type}:$type",ico_earth,$error);


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
        $tpl=www_parameters2_trap_files($tpl,$ID);
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






function isAlready14():bool{
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE type=14");
    if(isset($ligne["ID"])){return true;}
    return false;
}
function new_www():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $PHPReverseEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHPReverseEnabled"));
    $NgxStreamJS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NgxStreamJS"));
    $EnableDebianAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDebianAgent"));

    if($PHPReverseEnabled==1) {
        $Types[1] = "{php_website_explain}";
    }
    $Types[2] = "{artica_reverse_sites_explain}";
    $Types[13] = "{CREATE_ADFS_SERVICE}";
    $Types[9] = "{CREATE_WEB_HTML_SERVICE}";

    if($NgxStreamJS==1){
        $Types[15] = "{CREATE_DOH_SERVICE}";
    }
    if($EnableDebianAgent==1){
        $Types[16] = "{CREATE_DEBIAN_AGENT_SERVICE}";
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
        $APP_MATTERMOST_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MATTERMOST_INSTALLED"));
        $EnableMattermost=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMattermost"));
        if($APP_MATTERMOST_INSTALLED==0){
            $EnableMattermost=0;
        }
        if($EnableMattermost==1) {
            $Types[19] = "{CREATE_MATTERMOST_SERVICE}";
        }

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
    if($ztype==0){
        echo $tpl->post_error("Please specify the web service type");
        return false;
    }

    $result = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site", [
        "servicename" => $servicename,
        "type" => $ztype
    ]), true);

    if (!is_array($result) || !$result["Status"]) {
        $err = is_array($result) ? $result["Error"] : "reverse-proxy daemon unavailable";
        echo $tpl->post_error($err);
        return false;
    }

    $_SESSION["NEWNGINXAFTER"]["SITEID"] = intval($result["ID"]);
    return admin_tracks("Add new reverse-proxy site $servicename");
}
function www_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST_XSS();
    $ID=intval($_POST["ID"]);
    if($ID==0){return new_www_save();}
    $isDefault=intval($_POST["isDefault"]);

    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    $isDefaultOld=intval($ligne["isDefault"]);
    if($isDefaultOld<>$isDefault){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/RemoveDefaults");
    }

    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
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

    $servicename=null;
    if(isset($_POST["servicename"])) {
        $servicename = $_POST["servicename"];
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/update-service", [
            "servicename" => $servicename,
            "isDefault" => $isDefault
        ]);
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

function td_row_vitrification($id,$sockngix):string{
    $Vitrification=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Vitrification"));
    if($Vitrification==0) {
        return "";
    }
    $js="Loadjs('fw.nginx.sites.vitrification.php?js=$id')";
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ligne=$sockngix->GetCache();
    if($ligne["vitrification_enabled"]==0){
        return "";
    }
    $ay=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/fetch/status/$id"),true);

    if(!isset($ay["Status"]) OR !isset($ay["Data"])){
        return $tpl->icon_vitrification($js,false,true);
    }
    $array=$ay["Data"];
    if($array["Running"]) {
        return $tpl->icon_refresh_animate($js);
    }
    $ay=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/storage/status/$id"),true);
    $array=$ay["Data"];

    $TotalSizeBytes=$array["TotalSizeBytes"];

    if($TotalSizeBytes>0) {
        if (!$array["IsVitrified"]) {
            return $tpl->icon_vitrification($js);

        }
        return $tpl->icon_vitrification($js,true);

    }

    return __LINE__."-".$TotalSizeBytes.$tpl->icon_vitrification($js);

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
    $Data=$sockngix->GetCache();
    if(intval($Data["enabled"])==0){
        return $tpl->icon_shield();
    }
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
    $ID=intval($_GET["MaintenanceSite"]);
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    $MaintenanceSite=intval($ligne["MaintenanceSite"]);
    $newVal=($MaintenanceSite==0) ? 1 : 0;
    $Text=($newVal==1) ? "enabled" : "disabled";

    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/update-service", [
        "MaintenanceSite" => $newVal
    ]),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        $tpl->js_error($err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    header("content-type: application/x-javascript");
    $Sitename=get_servicename($ID);
    echo "Loadjs('$page?td-row=$ID');\n";
    return admin_tracks("Set maintenance reverse-proxy website of $Sitename to $Text");
}
function td_destinations($return=false):string{
    $ID=$_GET["td-destinations"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $latency="<div id='peity-start-$ID'></div>";

    VERBOSE("-------> socksngix",__LINE__);
    $sockngix                   = new socksngix($ID);
    VERBOSE("-------> GetCache",__LINE__);
    $ligne=$sockngix->GetCache();
    VERBOSE("-------> GetCache -> OK",__LINE__);
    $idDiv="rcolor9-$ID";
    if(!isset($ligne["type"])){$ligne["type"]=0;}

    if(($ligne["type"]==8) OR ($ligne["type"]==19) OR ($ligne["type"]==6) OR ($ligne["type"]==1) or ($ligne["type"]==10)){
        $destination=base64_encode($tpl->_ENGINE_parse_body("<div id='destination-$ID-builded'>{local}</div>"));
        VERBOSE("This is a local website",__LINE__);
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        if(!$return) {
            echo @implode("\n", $f);
        }
        return @implode("\n", $f);
    }
    if($ligne["type"]==14 or $ligne["type"]==9 or $ligne["type"]==1){
        VERBOSE("$ID: Type 14 OR 9",__LINE__);
        $destination=base64_encode($tpl->_ENGINE_parse_body("<div id='destination-$ID-builded'>{local}</div>"));
        if($ligne["type"]==9){
            $WebDavEnabled=$ligne["WebDavEnabled"];
            if($WebDavEnabled==1){
                $destination=base64_encode($tpl->td_href("{local}&nbsp;<span class='label label-info'>WebDav</span>",
                    null,"Loadjs('fw.nginx.site.content.php?webdav-explain=$ID')"));
            }

        }


        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        if(!$return) {
            echo @implode("\n", $f);
        }
        return @implode("\n", $f);

    }
    if($ligne["type"]==7){
        $doh_subfolder=$ligne["doh_subfolder"];
        if($doh_subfolder==null){$doh_subfolder="dns-query";}
        $destination=base64_encode($tpl->_ENGINE_parse_body("<div id='destination-$ID-builded'>{local_dns_service}</div>"));
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        if(!$return) {
            echo @implode("\n", $f);
        }
        return @implode("\n", $f);
    }
    if($ligne["type"]==5){
        $destination=@implode("<br>",$ligne["backendsOf"]);
        $destination=base64_encode("<div id='destination-$ID-builded'>".$tpl->_ENGINE_parse_body($destination).$latency."</div>");
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        if(!$return) {
            echo @implode("\n", $f);
        }
        return @implode("\n", $f);
    }

    $mouses="onMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";

    if($ligne["type"]==2 OR $ligne["type"]==13 OR $ligne["type"]==15){
        $destination="{unknown}";
        $backends=extract_backends($ID);
        $tootips="";
        $json=@json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/site/$ID"));
        if(is_object($json) && isset($json->config)){$json=$json->config;}else{$json=null;}

        $ActiveHealthCheck=intval($sockngix->GET_INFO("ActiveHealthCheckEnabled"));
        if($ActiveHealthCheck==0){

            $BackendAnalyzed=is_object($json) ? intval($json->backend_analyzed) : 0;
            $BackendErr=is_object($json) ? intval($json->backend_err) : 0;

            if($ligne["enabled"]==1) {
                if($BackendAnalyzed==1) {
                    if ($BackendErr == 1) {
                        $js = "Loadjs('$page?backend-error-js=$ID')";
                        $tootips = "<span class='label label-danger' $mouses OnClick=\"$js\">{error}</span><br>";
                    }
                }
            }
        }


        $latencyscore_text="";
        $latencyscore=is_object($json) ? floatval($json->LatencyScore) : 0;
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

        $destination=base64_encode($tpl->_ENGINE_parse_body($destination.$latency));
        $f[]="if( document.getElementById('$idDiv') ){";
        $f[]="\ttempdata=base64_decode('$destination');";
        $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
        $f[]="}";
        if(!$return) {
            echo @implode("\n", $f);
        }
        return @implode("\n", $f);
    }

    $destination="<div class='center' id='destination-$ID-builded'>".$tpl->icon_nothing()."</div>";
    $destination=base64_encode($tpl->_ENGINE_parse_body($destination));
    $f[]="if( document.getElementById('$idDiv') ){";
    $f[]="\ttempdata=base64_decode('$destination');";
    $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
    $f[]="}";

    if($ligne["enabled"]==1) {
        $f[] = "function LatenciesPeity(){";
        $f[] = "  $(\"[id^='peity-latencies-']\").each(function () {";
        $f[] = "      let fullId = this.id;";
        $f[] = "      let match = fullId.match(/^peity-latencies-(\d+)$/);";
        $f[] = "      if (match && match[1]) {";
        $f[] = "          let id = match[1];";
        $f[] = "          ";
        $f[] = "      }";
        $f[] = "  });";
        $f[] = "";
        $f[] = "LatenciesPeity();";
    }
    if(!$return) {
        echo @implode("\n", $f);
    }
    return @implode("\n", $f);
}
function td_row_latencies($ID):array{

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/backends-scanner/metrics/$ID"),true);
    if(!isset($json["Data"])){
        return array("","");
    }

    $max_ms=array();
    foreach ($json["Data"] as $atence){
        $max_ms[]=$atence["max_ms"];

    }
    if(count($max_ms)==0){
        return array("","");
    }

    $peity_div = "<div style='margin-top:5px' 
		onMouseOver=\"this.style.cursor='pointer'\" 
		OnMouseOut=\"this.style.cursor='default'\"
		onclick=\"Loadjs('fw.nginx.metrics.latencies.php?js=$ID')\">
		<span id=\"nginx-sites-latencies-$ID\"></div>";

    return array($peity_div,@implode(",",$max_ms));
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

    $restart=$tpl->framework_buildjs("nginx:/reverse-proxy/progress/checkbackend/$ID/0",
        "nginx.CheckReverseTests.$ID.progress",
        "nginx.CheckReverseTests.$ID.log","renalyze-backend-$ID",
        "dialogInstance2.close();Loadjs('$page?td-row=$ID');","Loadjs('$page?backend-error-js=$ID');");


    $btn=$tpl->button_autnonome("{analyze}",$restart,ico_refresh,null,335,"btn-danger");
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    $BackendAnalyzedTime=$ligne["backend_analyzed_time"];
    $BackendErrDetail=base64_decode($ligne["backend_err_detail"]);
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


    $content=base64_encode("<i class='".ico_refresh_animate."'></i>");
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
function td_row_serverstats($QueryID,$MAIN_RT_USERS):string{

    $UserLabel="";
    if(isset($MAIN_RT_USERS[$QueryID])){
        $tpl=new template_admin();
        $UserNum=intval($MAIN_RT_USERS[$QueryID]);
        if($UserNum>0){
            $icoUsr=ico_member;
            $class="text-muted";
            if($UserNum>1){
                $class="text-primary";
            }
            if($UserNum>5000){
                $class="text-warning";
            }
            if($UserNum>50000){
                $class="text-danger";
            }
            $UserNumText="<span class='$class'>".$tpl->FormatNumber($UserNum)."</span>";
            $UserNumText=$tpl->td_href($UserNumText,"","Loadjs('fw.nginx.active_requests.php?serviceid=$QueryID');");
            $UserLabel="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<i class='$icoUsr'></i>&nbsp;$UserNumText";
        }
    }


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
        $DOMS=array();
        if (property_exists($Class, "rules") && is_iterable($Class->rules)) {
            $Rules = $Class->rules;
            foreach ($Rules as $domain => $id) {
                $DOMS[$domain] = $id;
            }
        }
        $STATS=array();
        if (property_exists($Class, "serverZones") && is_iterable($Class->serverZones)) {
            foreach ($Class->serverZones as $Domain => $jclass) {
                if (isset($DOMS[$Domain])) {
                    $STATS[$DOMS[$Domain]] = $jclass;
                }
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
    if(strlen($UserLabel)>1){
        $z[]=$UserLabel;
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

    $sockngix                   = new socksngix($id);
    $ligne=$sockngix->GetCache();


    $MAIN_REVERSED = MAIN_REVERSED();
    if(!isset($GLOBALS["MAIN_RT_USERS"])){
        $ThisInf=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/clients/counts"),true);
        if(isset($ThisInf["services"])){
            $MAIN_RT_USERS=array();
            foreach ($ThisInf["services"] as $num=>$ligneUsers){
                VERBOSE("MAIN_RT_USERS: {$ligneUsers["service_id"]} = {$ligneUsers["clients_count"]}",__LINE__);
                $MAIN_RT_USERS[$ligneUsers["service_id"]]=$ligneUsers["clients_count"];
            }
            $GLOBALS["MAIN_RT_USERS"]=$MAIN_RT_USERS;
        }else{
            VERBOSE("MAIN_RT_USERS: NO!!",__LINE__);
        }
    }


    $WAF=base64_encode(td_row_waf($id));
    $status=base64_encode($tpl->_ENGINE_parse_body(td_status($id,$MAIN_REVERSED)));
    $td_saved=base64_encode($tpl->_ENGINE_parse_body(td_saved($ligne,$sockngix)));
    $BtnAction=base64_encode($tpl->_ENGINE_parse_body(td_btnAction($id)));
    $servicename=base64_encode(td_row_servicename($id,$MAIN_REVERSED));
    $servernames=base64_encode(td_row_serversnames($id));
    $ServerStats=base64_encode(td_row_serverstats($id,$GLOBALS["MAIN_RT_USERS"]));
    $Vitrification=base64_encode(td_row_vitrification($id,$sockngix));
    $checkTenant=base64_encode(getTenantID($id));



    $f[]="if( document.getElementById('rcolor0-$id') ){";
    $f[]="\ttempdata=base64_decode('$status');";
    $f[]="\tdocument.getElementById('rcolor0-$id').innerHTML=tempdata;";
    $f[]="}";


    $f[]="if( document.getElementById('rcolor1-$id') ){";
    $f[]="\ttempdata=base64_decode('$td_saved');";
    $f[]="\tdocument.getElementById('rcolor1-$id').innerHTML=tempdata;";
    $f[]="}";

    // rcolor3 --> Vitrification
    $f[]="if( document.getElementById('rcolor3-$id') ){";
    $f[]="\ttempdata=base64_decode('$Vitrification');";
    $f[]="\tdocument.getElementById('rcolor3-$id').innerHTML=tempdata;";
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

    $f[]="if( document.getElementById('rcolor10-$id') ){";
    $f[]="\ttempdata=base64_decode('$checkTenant');";
    $f[]="\tdocument.getElementById('rcolor10-$id').innerHTML=tempdata;";
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
    $_GET["td-destinations"]=$id;
    VERBOSE("-------> td_destinations",__LINE__);
    $f[]=td_destinations(true);


    $peity_conf="{ width:150,height:25,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
    //return array($peity_div,@implode(",",$max_ms));
    list($div,$data)=td_row_latencies($id);
    if(strlen($data)>1) {
        $divEnc=base64_encode($div);
        $f[] = "if(document.getElementById('peity-start-$id')){";
        $f[]="\ttempdata=base64_decode('$divEnc');";
        $f[]="\tdocument.getElementById('peity-start-$id').innerHTML=tempdata;";
        $f[] = "\t$(\"#nginx-sites-latencies-$id\").peity(\"line\",$peity_conf);";
        $f[] = "}";
        $f[] = "";
    }






    echo @implode("\n",$f);
    return true;
}

function getTenantID($id){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tenantID=0;
    //Check Service Tenant
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/site/$id"),true);
    $ligne=$json["config"];
    $hosts=$ligne["hosts"];

    $jsonTenant=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/service/tenant/$hosts"),true);
    if(isset($jsonTenant["Tenant"])) {
        $tenantID = intval($jsonTenant["Tenant"]);
    }
    $style="";
    $TenantMessage="";
    if($tenantID==0) {
        $style = "style='color:#a3a1a1;'";
        $TenantMessage="<button class=\"btn btn-default\" type=\"button\" title=\"{not_protected}\"><i class=\"fa-solid fa-binary-slash\"></i></button>";

    }

    if($tenantID>0) {
        $TenantMessage="<button class=\"btn btn-primary\" type=\"button\" title=\"{protected_by_tenant} $tenantID\"><i class=\"fa-solid fa-binary-circle-check\"></i> <span class=\"badge\">$tenantID</span></button>";
    }
    return "<span $style>".$tpl->_ENGINE_parse_body($TenantMessage)."</span>";

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
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{edgeguard}</th>";
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
    $Types[16]="{APP_DEBIAN_NETWORK_AGENT}";
    $Types[19]="{APP_MATTERMOST}";
    $SEARCH_SSL=false;


    if(strlen($search)>0) {
        if (!$tpl->is_regex($search)) {
            if (preg_match("#(\s+)(SSL|https)#i", $search, $re)) {
                $SEARCH_SSL = true;
                $search = str_replace("$re[1]$re[2]", "", $search);
            }
            if (!is_numeric($search)) {
                $search = "*$search*";
                $search = str_replace("**", "*", $search);
                $search = str_replace(".", "\.", $search);
                $search = str_replace("*", ".*?", $search);
            }
        }
    }


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/list"),true);

    if(!$json["Status"]){
        $html[]=$tpl->div_error("{error} API||".$json["Error"]);
        $html[]="<script>";
        $html[]="Loadjs('$page?js-tiny=yes&function=$function')";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    if(!isset($json["sites"])){
        $json["sites"]=array();
    }

    $results=$json["sites"];



    $peity_js=array();
    $c=0;
    $cOffset=0;
    $TRCLASS=null;
    $MAX_WEB_SITES=count($results);
    $NginxTableCurpage=1;
    $SessionTableOffset=$_SESSION["NginxTableOffset"];
    VERBOSE("[START] SessionTableOffset=$SessionTableOffset",__LINE__);
    if(isset($_SESSION["NginxTableCurpage"])) {
        $NginxTableCurpage = $_SESSION["NginxTableCurpage"];
    }
    $StartItems=0;
    $StopItems=99999;

    if ($SessionTableOffset>0){
        $StartItems=($NginxTableCurpage-1)*$SessionTableOffset;
        $StopItems = $NginxTableCurpage*$SessionTableOffset;
    }
    VERBOSE("[START] SessionTableOffset=$SessionTableOffset ( Number of items per page) NginxTableCurpage=$NginxTableCurpage (page requested) Start at $StartItems Stop at $StopItems",__LINE__);

    if(strlen($search)>1) {
        VERBOSE("SEARCH = [$search]",__LINE__);
        $StartItems=0;
        $SessionTableOffset=0;
    }
    $spans=array();
    $ssTyle1="style='width:1%'";

    foreach ($results as $ligne){

        if(strlen($search)>1) {
            if(!preg_match("#$search#i",serialize($ligne))){
                continue;
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];

        $md="MdNginxRule$ID";

        $LOCK_ACTION=false;
        if(!$IS_LICENSE){$LOCK_ACTION=true;}
        if($DisableBuildNginxConfig==1){
            $LOCK_ACTION=true;
        }

        $icon_type="";
        $color=null;
        $debug_ico=null;
        $debug=intval($ligne["debug"]);
        $DestinationsPrepare[$ID]=$md;
        $WebSiteType=intval($ligne["type"]);
        $enabled=intval($ligne["enabled"]);

        if($debug==1){
            $debug_ico="&nbsp;&nbsp;<i class='fad fa-bug' style='color:#ec4758'></i>&nbsp;".
                $tpl->td_href("{debug}",null,"Loadjs('fw.nginx.sites.debug.php?siteid=$ID')");
        }

        $cOffset++;
        VERBOSE("cOffset=$cOffset StopItems=$StopItems SessionTableOffset = $SessionTableOffset StartItems=$StartItems ",__LINE__);
        if ($StartItems>0) {
            if ($cOffset<$StartItems){
                continue;
            }
        }
        if ($cOffset>=$StopItems){
            break;
        }
        if($StopItems==99999){
             if($cOffset>=$_SESSION["NginxTableMaxRecs"]){
                 break;
             }
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
        $jssite=$tpl->td_href($ligne["service_name"],null,"Loadjs('$page?www-js=$ID')");
        if(!$IS_LICENSE){
            $jssite=$ligne["service_name"];
        }
        if(intval($ligne["type"])==1){
            $icon_type="<li class='fa-brands fa-php'></li>&nbsp;";
        }


        $pleasewait="<i class=\"fas fa-sync fa-spin\" style='width:35%' id='backend-analyze-$ID'></i>&nbsp;{analyze}...</span>";
        if($enabled==0 OR intval($ligne["type"])==14){
            $pleasewait="";
        }
        $RCOlor2="<span style='$color' id='rcolor2-$ID'>$icon_type$jssite$debug_ico</span>$peity_div";

        $TypeText=$Types[$ligne["type"]];
        $spans[]="<span id='status-$ID'></span>";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $ssTyle1><span id='rcolor0-$ID'><span></td>";
        $html[]="<td $ssTyle1 nowrap><span style='$color' id='rcolor1-$ID'></span></td>";
        $html[]="<td nowrap>$RCOlor2<span id='rcolorStats-$ID'></span></td>";
        $html[]="<td><span style='$color' id='rcolor3-$ID'></span></td>"; // Vitrification
        $html[]="<td><span style='$color' id='rcolor4-$ID'></span></td>"; // WAF
        $html[]="<td><span style='$color' id='rcolor10-$ID'></span></td>"; // EDGEGUARD
        $html[]="<td><span style='$color' id='rcolor7-$ID'></span></td>";
        $html[]="<td><span style='$color;width:35%' id='rcolor5-$ID'></span></td>";
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
    $ligne=$sockngix->GetCache();
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
function td_row_serversnames($id,$MAIN_REVERSED=array()):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $style="";
    if($id==0) {
        $id = intval($_GET["td-status"]);
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/site/$id"),true);
    if(!$json["Status"]){
        return "<span class='text-danger'>".$json["Error"]."</span>";
    }
    $ligne=$json["config"];
    $ServerType=$ligne["type"];
    $enabled=intval($ligne["enabled"]);
    if(count($MAIN_REVERSED)==0){
        $MAIN_REVERSED=MAIN_REVERSED();
    }
    if (!isset($MAIN_REVERSED[$id])) {
        $enabled=0;
    }


    if($ServerType==5){
        return td_row_serversnames_stream($id);
    }
    if($enabled==0) {
        $style = "style='color:#a3a1a1;'";
    }

    list($serversnames,$ServerNameFields)=extract_hosts($ligne["hosts"],$id);
    return "<span $style>".$tpl->_ENGINE_parse_body($ServerNameFields)."</span>";
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
function td_row_servicename($id=0,$MAIN_REVERSED=array()):string{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $style="";
    $page=CurrentPageName();
    if($id==0) {
        $id = intval($_GET["td-status"]);
    }
    if($id==0){
        return "";
    }
    $sockngix                   = new socksngix($id);
    $ligne=$sockngix->GetCache();
    $enabled=$ligne["enabled"];
    $badconflength=0;
    if(!is_null($ligne["badconf"])) {
        $badconflength = strlen($ligne["badconf"]);
    }
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
    if(count($MAIN_REVERSED)==0){
        $MAIN_REVERSED=MAIN_REVERSED();
    }
    if (!isset($MAIN_REVERSED[$id])) {
        $enabled=0;
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
        $style="style='color:#a3a1a1;'";
    }

    $DenyAccess=intval($sockngix->GET_INFO("DenyAccess"));

    if($badconflength>10){
        $badconf="<br><small class=$textdanger>".$tpl->td_href("{bad_configuration}",null,"Loadjs('$page?badconf=$id');")."</small>";
    }

    if($DenyAccess==1){
        $badconf=$badconf."&nbsp;<span class='label $labelfanger'>{deny_access}</span>";
    }

    $ssl_certificate="";
    if($enabled==1) {
        $ssl_certificate = td_row_sslcertificate($id);
    }
    return $tpl->_ENGINE_parse_body("<span $style>$jssite$badconf$ssl_certificate$debug_ico</span>");

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


    if($results) {
        VERBOSE("$ID: ".pg_num_rows($results)." records",__LINE__);
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



function td_status($ID,$MAIN_REVERSED=array()):string{
    VERBOSE("-------------------------- START STATUS $ID--------------------------", __LINE__);
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    if (intval($ligne["enabled"]) == 0) {
        VERBOSE("------- $ID DISABLED",__LINE__);
        return "<span class='label label-default'>#$ID {disabled}</span>";
    }
    VERBOSE("------- $ID ACTIVE",__LINE__);
    $page = CurrentPageName();
    $ssl_certificate="";
    if(isset($ligne["ssl_certificate"])) {
        $ssl_certificate = $ligne["ssl_certificate"];
    }
    $tpl = new template_admin();


    if (!isset($GLOBALS["SSLCERTIFICATES"])) {
        $squid_reverse = new squid_reverse();
        $GLOBALS["SSLCERTIFICATES"] = $squid_reverse->ssl_certificates_list();
    }
    $MaintenanceSite=$ligne["MaintenanceSite"];
    $goodconf_js = "Loadjs('$page?goodconf=$ID')";
    $sslcertificates = $GLOBALS["SSLCERTIFICATES"];
    $sslcertificates["__DEFAULT__"] = true;


    if(count($MAIN_REVERSED)==0) {
        $MAIN_REVERSED = MAIN_REVERSED();
    }
    if($MaintenanceSite==1){
        if (!isset($MAIN_REVERSED[$ID])) {
            return $tpl->td_href("<span class='label label-danger'>#$ID {not_saved}</span>", null, $goodconf_js);
        }
        return $tpl->td_href("<span class='label label-warning'>#$ID {maintenance}</span>", null, $goodconf_js);
    }
    if ($ligne["type"] == 13) {
        if ($ssl_certificate == null) {
            $js = "Loadjs('fw.nginx.sites.php?www-parameters-ssl-js=$ID')";
            return $tpl->td_href("<span class='label label-danger'>#$ID {no_certificate}</span>", null, $js);
        }

        $tcount = count($ligne["backends"]);
        if ($tcount == 0) {
            return $tpl->td_href("<span class='label label-danger'>#$ID no backend</span>", null, $goodconf_js);
        }

        $HostHeader = trim($ligne["HostHeader"]);
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

    $FrontendErrDetail=base64_decode($ligne["frontend_err_detail"]);
    $FrontendErr=$ligne["frontend_err"];
    if ($FrontendErr == 1) {
        return $tpl->td_href("<span class='label label-danger'>#$ID $FrontendErrDetail</span>",
        null, "Loadjs('fw.nginx.frontend.error.php?serviceid=$ID')");
    }


    return $tpl->td_href("<span class='label label-primary'>#$ID OK</span>", null, $goodconf_js);
}
function extract_backends($serviceid):string{
    include_once(dirname(__FILE__)."/ressources/class.nginx.reverse.http.inc");
    $sock=new socksngix($serviceid);
    $ligne=$sock->GetCache();
    $UseSSL=intval( $ligne["UseSSL"]);

    $ForwardServersDynamics =   intval($ligne["ForwardServersDynamics"]);
    if($ForwardServersDynamics==1){
        $FSDynamicsExt = intval($ligne["FSDynamicsExt"]);
        $FSDynamicsDst = trim($ligne["FSDynamicsDst"]);
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

    $HostHeader=trim($ligne["HostHeader"]);
    $HostHeader=tool_nginx_clean_uri($HostHeader);
    if($HostHeader<>null){$HostHeader=" ($HostHeader)";}

    $RemotePath=$ligne["RemotePath"];
    if(strlen($RemotePath)<2){$RemotePath=null;}

    if($RemotePath<>null) {
        if (!preg_match("#^/#", $RemotePath)) {
            $RemotePath = "/$RemotePath";
        }
        if (!preg_match("#/$#", $RemotePath)) {
            $RemotePath = "$RemotePath/";
        }
    }


    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ServiceType=intval($ligne["type"]);

    $T=array();
    $error_proto=null;

    foreach ($ligne["backends"] as $ligne) {
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
        if(!is_null($hostname)) {
            if (preg_match("#^http.*?:#i", $hostname)) {
                $parse_url = parse_url($hostname);
                $hostname = $parse_url["host"];
                $proto = "http";
            }
        }
        if(!is_null($hostname)) {
            if (preg_match("#^https.*?:#i", $hostname)) {
                $parse_url = parse_url($hostname);
                $hostname = $parse_url["host"];
                $proto = "https";
            }
        }
        if ($ServiceType==15){
            $proto="dns";
        }
        VERBOSE("Table backends service=$serviceid ID=$ID,port=$port,hostname=$hostname,proto=$proto,ssl=$ssl",__LINE__);
        $js="Loadjs('fw.nginx.backends.php?id-js=$ID')";
        $ActiveHealthCheck=intval($sock->GET_INFO("ActiveHealthCheckEnabled"));
        if($ActiveHealthCheck==1){

            $curl = new ccurl("http://localhost:9090/api/history?server={$hostname}:{$port}&limit=1", true);
            $curl->NoLocalProxy();
            if (!$curl->get()) {
            }

            $json=json_decode($curl->data,true);
            if (json_last_error() > JSON_ERROR_NONE) {
                //ADD ERROR HANDLER
            }
            if (!property_exists($json,"Status")){
                //ADD ERROR HANDLER
            }

            $last = $json['metrics'][0];

            $status = $last['healthy'];
            $class="btn-success";
            $rt     = round($last['duration']/ 1000000,2);
            $iconFast="<i class=\"fa-solid fa-jet-fighter\"></i>";
            $iconHealth="<i class=\"fa-solid fa-heart-circle-check\"></i>";
            $timeText="Response Time $rt ms";
            $healthyText="Healthy";
            if($last['duration']>1300000000){
                $iconFast="<i class=\"fa-solid fa-turtle\"></i>";
                $class="btn-warning";
                $iconHealth="<i class=\"fa-solid fa-brake-warning\"></i>";
                $healthyText="Slow";

            }
            if($last['duration']>3000000000 || $status==0){
                $iconFast="<i class=\"fa-solid fa-explosion\"></i>";
                $class="btn-danger";
                $iconHealth="<i class=\"fa-regular fa-octagon-exclamation\"></i>";
                $healthyText="Unhealthy";
            }

            $text=$tpl->td_href("$proto://{$hostname}:{$port}{$RemotePath}",null,$js);
            $btn='<button type="button" class="btn btn-labeled '.$class.'" style="padding-top: 0;padding-bottom: 0;margin-bottom: 10px;">
                <span data-toggle="tooltip" data-placement="top" title="'.$healthyText.'" class="btn-label" style="position: relative;left: -12px;display: inline-block;padding: 6px 12px;background: rgba(0, 0, 0, 0.15);border-radius: 3px 0 0 3px;">'.$iconHealth.'</span>'.$text.'<span class="btn-label" style="position: relative;right: -12px;display: inline-block;padding: 6px 12px;background: rgba(0, 0, 0, 0.15);border-radius: 3px 0 0 3px;" alt="'.$timeText.'" title="'.$timeText.'">'.$iconFast.'</span></button>';
            $T[]=$btn;



        }
        else{
            $T[]=$tpl->td_href("$proto://{$hostname}:{$port}{$RemotePath}",null,$js);
        }

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

    $data=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["destinations-prepare"]);
    $t=time();
    $Timeout=1000;
    $f=array();



    foreach ($data as $ID=>$md){
        $Timeout=$Timeout+50;
        $idDiv="rcolor9-$ID";
        $sock=new socksngix($ID);
        $ligne=$sock->GetCache();
        $Type=$ligne["type"];

        $f[]="// $ID Type = $Type";
        if($Type==4){
            $text=base64_encode(destinations_artica());
            $f[]="tempdata=base64_decode('$text');";
            $f[]="\tif( document.getElementById('$idDiv') ){";
            $f[]="\tdocument.getElementById('$idDiv').innerHTML=tempdata;";
            $f[]="}\n";
            continue;
        }
        if( $Type==19 OR $Type==14 ){
            $text=base64_encode($tpl->_ENGINE_parse_body("{local}"));
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

    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}
