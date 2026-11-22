<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
$GLOBALS["PEITYCONF"]="{ width:280,height:25,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
$GLOBALS["DYNAMIC_RATE_FEATURE"]=false;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
// awc_table_style()/awc_pager_server(): kit des tableaux d'entites. Include au
// PREMIER NIVEAU obligatoire -- glisse dans un bloc conditionnel il ne
// s'executerait jamais et la page mourrait sur « Call to undefined function ».
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

/**
 * Collecteur des lignes « parametres du site ».
 *
 * Les ~30 helpers www_parameters2_*() / www_*() ecrivent leurs lignes avec
 * table_form_field_*() et portent toute la logique metier (etat, compteur,
 * ouvreur de modale, privilege). Plutot que de les reecrire pour les tuiles,
 * www_parameters2() leur passe cet objet : il capte chaque ligne comme une
 * DONNEE (libelle, valeur, icone, erreur, JS d'edition) au lieu d'un HTML,
 * puis ngxp_render() en fait les tuiles. Un helper modifie demain change donc
 * la tuile sans toucher au rendu.
 *
 * Le JS d'edition reste « collant » comme dans template_admin (switchjs n'est
 * jamais desarme) : plusieurs lignes SSL heritent volontairement de l'ouvreur
 * pose avant elles.
 */
class NgxpCollector extends template_admin{
    public $NGXP_ROWS=array();
    public $NGXP_JS="";
    public $NGXP_RO=false;

    public function table_form_field_js($js,$privilege=null,$buttons=array()):bool{
        $this->NGXP_RO=false;
        if($privilege<>null && !$this->IsSecurity($privilege)){
            $this->NGXP_JS="";
            $this->NGXP_RO=true;
            return false;
        }
        $this->NGXP_JS=(string)$js;
        return true;
    }
    private function ngxp_add($kind,$field,$value,$ico,$error):bool{
        $this->NGXP_ROWS[]=array(
            "kind"=>$kind,"field"=>(string)$field,"value"=>$value,"ico"=>(string)$ico,
            "error"=>$error,"js"=>$this->NGXP_JS,"ro"=>$this->NGXP_RO);
        return true;
    }
    public function table_form_field_text($field,$value,$ico=null,$error=false):bool{
        // Un $error commencant par http est un lien d'aide, pas une erreur.
        $isErr=($error===true || $error===1 || $error==="1");
        return $this->ngxp_add("text",$field,$value,$ico,$isErr);
    }
    public function table_form_field_info($field,$value,$ico=null,$buttons=array()):bool{
        return $this->ngxp_add("text",$field,$value,$ico,false);
    }
    public function table_form_field_bool($field,$value,$ico=null,$helpuri=null):bool{
        if(is_array($value) && isset($value["BUTTON"]["VALUE"])){$value=$value["BUTTON"]["VALUE"];}
        return $this->ngxp_add("bool",$field,$this->BoolToInteger($value),$ico,false);
    }
    public function table_form_field_button($field,$value,$ico=null):bool{
        return $this->ngxp_add("button",$field,$value,$ico,true);
    }
    public function table_form_field_warn($text):bool{
        return $this->ngxp_add("warn",$text,"",null,false);
    }
    public function table_form_field_warning($text):bool{
        return $this->ngxp_add("warn",$text,"",null,false);
    }
    // Same signature as template_admin (2 optional arguments added 2026-11-11):
    // PHP 8 refuses an incompatible override and the whole page died with a 500.
    public function table_form_section($title=null,$explain=null,$ico=null,$button=null):bool{
        return true;
    }
    public function table_form_compile($Columns=1):string{
        return "";
    }
}



if(isset($_POST["JsBC"])){www_parameters_JsBC_save();exit;}

if(isset($_GET["BotChecker-js"])){www_parameters2_BotChecker_save();exit;}

if(isset($_GET["monitored-frontend-js"])){www_parameters2_auditFrontend_js();exit;}
if(isset($_GET["monitored-frontend-popup"])){www_parameters2_auditFrontend_popup();exit;}
if(isset($_POST["monitored-save"])){www_parameters2_auditFrontend_save();exit;}

if(isset($_GET["download-db"])){download_database();exit;}
if(isset($_GET["MaintenanceSite"])){MaintenanceSite();exit;}
if(isset($_GET["TableNavigate"])){table_pagination();exit;}
if(isset($_GET["MaxItems"])){table_MaxItems();exit;}

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


if(isset($_GET["www-js"])){www_js();exit;}
if(isset($_GET["www-tabs"])){www_tabs();exit;}
if(isset($_GET["www-parameters"])){www_parameters();exit;}
if(isset($_GET["www-parameters2"])){www_parameters2();exit;}
if(isset($_GET["ngxp-maintenance-js"])){ngxp_maintenance_js();exit;}
if(isset($_POST["ngxp-maintenance-on"])){ngxp_maintenance_on();exit;}
if(isset($_GET["ngxp-deny-js"])){ngxp_deny_js();exit;}
if(isset($_POST["ngxp-deny-on"])){ngxp_deny_on();exit;}
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
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
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




// fetch_stream_ports_for_service returns all stream_ports rows for a service.

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
    foreach (fetch_nginx_services_all() as $ligne){
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
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";

    $sockngix=new socksngix($ID);
    $sockngix->SET_INFO("EnableModSecurity",1);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return  admin_tracks("Activate Web application firewall for $servicename");

}

function enable_fw_perform():bool{
    admin_tracks("Enable Web application firewall on all web services");
    foreach (fetch_nginx_services_all() as $ligne){
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
    $t2=time();
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";

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
        "nginx:/reverse-proxy/hupreconfigure",
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
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ligne=fetch_nginx_service_row($ID);
    $md=$_GET["md"];
    $jsafter="$('#$md').remove();";
    $tpl->js_confirm_delete(isset($ligne["servicename"])?$ligne["servicename"]:"", "delete", $ID,$jsafter);
}


function badconf_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["badconf"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    $tpl->js_dialog1("{bad_configuration} $servicename", "$page?badconf-popup=$ID");
}
function badconf_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["badconf-popup"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";

    $form[]=$tpl->field_textareacode("null", null, base64_decode(isset($ligne["badconf"])?$ligne["badconf"]:""));
    echo $tpl->form_outside($servicename, $form,null,null,"NgixSitesReload();","AsSystemWebMaster");
}
function goodconf_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["goodconf"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
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
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
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
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services?order=zorder");
    $data=json_decode($resp,true);
    $results=(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"])) ? $data["rows"] : [];
    $zfirst=array();
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
    $ID=intval($_GET["goodconf-popup"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    $form[]=$tpl->field_hidden("goodconf_id", $ID);
    $form[]=$tpl->field_textareacode("goodconf", null, base64_decode(isset($ligne["goodconf"])?$ligne["goodconf"]:""));
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

function nginx_actions_css():string{
    $f=array();
    $f[]="<style>";
    $f[]=".nact-wrap{padding:10px;background:#fbf9f8;color:#1b1c1c;font-family:Inter,'Helvetica Neue',Helvetica,Arial,sans-serif;}";
    $f[]=".nact-sec{margin-bottom:26px;}";
    $f[]=".nact-sec:last-child{margin-bottom:0;}";
    $f[]=".nact-sec-title{font-size:20px;line-height:28px;font-weight:400;letter-spacing:-0.01em;color:#1b1c1c;margin:0 0 12px 0;padding:0;}";
    $f[]=".nact-grid{display:grid;grid-template-columns:1fr 1fr;gap:2px;background:#eeeeee;border:1px solid #eeeeee;border-radius:4px;overflow:hidden;}";
    $f[]=".nact-card{box-sizing:border-box;background:#ffffff;padding:20px;position:relative;min-height:96px;}";
    $f[]=".nact-card *{box-sizing:border-box;}";
    $f[]=".nact-card:nth-child(even){background:#f9f9f9;}";
    $f[]=".nact-card-full{grid-column:1 / -1;}";
    $f[]=".nact-card-danger{background:#ffffff;border-left:4px solid #ba1a1a;}";
    $f[]=".nact-body{padding-right:150px;}";
    $f[]=".nact-head{display:flex;align-items:center;gap:8px;margin-bottom:8px;}";
    $f[]=".nact-ico{font-size:18px;color:#5d5f5f;flex:0 0 auto;}";
    $f[]=".nact-ico-danger{font-size:18px;color:#ba1a1a;flex:0 0 auto;}";
    $f[]=".nact-title{font-size:17px;line-height:24px;font-weight:500;color:#333333;margin:0;padding:0;}";
    $f[]=".nact-title-danger{font-size:17px;line-height:24px;font-weight:500;color:#ba1a1a;margin:0;padding:0;}";
    $f[]=".nact-text{font-size:14px;line-height:20px;color:#3f4a3d;margin:0;padding:0;}";
    $f[]=".nact-warn{display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;color:#92400e;font-size:13px;line-height:18px;font-weight:500;}";
    $f[]=".nact-wico{font-size:16px;color:#d97706;flex:0 0 auto;}";
    $f[]=".nact-btn{position:absolute;top:20px;right:20px;display:inline-flex;align-items:center;gap:8px;margin:0;background:#016e1c;color:#ffffff;border:0;border-radius:4px;padding:8px 16px;font-size:13px;line-height:16px;font-weight:600;text-transform:uppercase;white-space:nowrap;cursor:pointer;transition:background .15s ease;}";
    $f[]=".nact-btn:hover{background:#015a17;color:#ffffff;}";
    $f[]=".nact-btn-danger{background:#ba1a1a;}";
    $f[]=".nact-btn-danger:hover{background:#a01616;}";
    $f[]=".nact-bico{font-size:14px;}";
    $f[]="@media (max-width:720px){";
    $f[]=".nact-grid{grid-template-columns:1fr;}";
    $f[]=".nact-body{padding-right:0;}";
    $f[]=".nact-btn{position:static;margin-top:12px;width:100%;justify-content:center;}";
    $f[]="}";
    $f[]="</style>";
    return @implode("\n",$f);
}

function nginx_action_tile(array $c):string{
    $h=array();
    $danger=!empty($c["DANGER"]);
    $classes="nact-card";
    if(!empty($c["FULL"])){$classes.=" nact-card-full";}
    if($danger){$classes.=" nact-card-danger";}
    $icoClass=$danger?"nact-ico-danger":"nact-ico";
    $titleClass=$danger?"nact-title-danger":"nact-title";
    $btnClass=$danger?"nact-btn nact-btn-danger":"nact-btn";
    $ico=isset($c["ICO"])?$c["ICO"]:"fas fa-circle-info";
    $btnIco=isset($c["BTNICO"])?$c["BTNICO"]:"fas fa-play";
    $btnText=isset($c["BTNTEXT"])?$c["BTNTEXT"]:"{run_task}";
    $title=isset($c["TITLE"])?$c["TITLE"]:"";
    $text=isset($c["TEXT"])?$c["TEXT"]:"";
    $js=str_replace('"',"&quot;",isset($c["JS"])?$c["JS"]:"");

    $h[]="<div class=\"$classes\">";
    $h[]="<div class=\"nact-body\">";
    if(!empty($c["PROGRESS"])){$h[]="<div id=\"{$c["PROGRESS"]}\"></div>";}
    $h[]="<div class=\"nact-head\"><i class=\"$ico $icoClass\"></i><h3 class=\"$titleClass\">$title</h3></div>";
    $h[]="<p class=\"nact-text\">$text</p>";
    if(!empty($c["WARN"])){
        $h[]="<div class=\"nact-warn\"><i class=\"fas fa-triangle-exclamation nact-wico\"></i><span>{$c["WARN"]}</span></div>";
    }
    $h[]="</div>";
    $h[]="<label class=\"$btnClass\" OnClick=\"$js\">$btnText <i class=\"$btnIco nact-bico\"></i></label>";
    $h[]="</div>";
    return @implode("\n",$h);
}

function nginx_action_section(string $title,array $cards):string{
    if(count($cards)==0){return "";}
    $h=array();
    $h[]="<div class=\"nact-sec\">";
    $h[]="<h2 class=\"nact-sec-title\">$title</h2>";
    $h[]="<div class=\"nact-grid\">".@implode("\n",$cards)."</div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

function action_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $function=isset($_GET["function"])?$_GET["function"]:"";
    $DisableBuildNginxConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBuildNginxConfig"));
    $css=nginx_actions_css();
    $sections=array();

    if($DisableBuildNginxConfig==0){
        $maintenance=array();
        $maintenance[]=nginx_action_tile(array(
            "ICO"=>"fas fa-arrows-rotate",
            "TITLE"=>"{rebuild_all_websites}",
            "TEXT"=>"{rebuild_all_websites_explain}",
            "WARN"=>"{resource_intensive_operation_warning}",
            "JS"=>"Loadjs('$page?reconfigure-all-js=yes');"));
        $maintenance[]=nginx_action_tile(array(
            "ICO"=>"fas fa-rotate-left",
            "TITLE"=>"{rebuild_all_websites_and_restart}",
            "TEXT"=>"{rebuild_all_websites_and_restart_explain}",
            "PROGRESS"=>"rebuild-all-websites-and-restart",
            "JS"=>$tpl->framework_buildjs("nginx:/reconfigure/restart",
                "reverse-proxy.reconfigure-all.progress","reverse-proxy.reconfigure-all.log",
                "rebuild-all-websites-and-restart")));
        $sections[]=nginx_action_section("{maintenance_and_rebuild}",$maintenance);
    }

    $data=array();
    $data[]=nginx_action_tile(array(
        "ICO"=>"fas fa-file-export",
        "TITLE"=>"{extract_domains}",
        "TEXT"=>"{extract_domains_nginx_explain}",
        "JS"=>"Loadjs('$page?extract-domains-js=yes&function=$function');"));
    $data[]=nginx_action_tile(array(
        "ICO"=>"fas fa-file-import",
        "TITLE"=>"{import_a_rule}",
        "TEXT"=>"{import_a_rule_nginx_explain}",
        "BTNTEXT"=>"{import}",
        "BTNICO"=>"fas fa-upload",
        "JS"=>"Loadjs('fw.nginx.export.php?ID=0&function=$function');"));
    $data[]=nginx_action_tile(array(
        "ICO"=>"fas fa-download",
        "TITLE"=>"{export_rules}",
        "TEXT"=>"{export_rules_nginx_explain}",
        "BTNTEXT"=>"{export}",
        "BTNICO"=>"fas fa-download",
        "JS"=>"document.location.href='$page?download-db=yes';"));
    $sections[]=nginx_action_section("{data_management}",$data);

    $controls=array();
    $controls[]=nginx_action_tile(array(
        "ICO"=>"fas fa-triangle-exclamation",
        "TITLE"=>"{disable_all}",
        "TEXT"=>"{disable_all_nginx}",
        "DANGER"=>true,
        "FULL"=>true,
        "BTNICO"=>"fas fa-circle-xmark",
        "JS"=>"Loadjs('$page?disable-all-js=yes&function=$function');"));
    $controls[]=nginx_action_tile(array(
        "ICO"=>"fas fa-shield-xmark",
        "TITLE"=>"{disable_all_web_firewall}",
        "TEXT"=>"{disable_all_web_firewall_explain}",
        "JS"=>"Loadjs('$page?disable-fw-js=yes&function=$function');"));
    $controls[]=nginx_action_tile(array(
        "ICO"=>"fas fa-shield-halved",
        "TITLE"=>"{enable_all_web_firewall}",
        "TEXT"=>"{enable_all_web_firewall_explain}",
        "JS"=>"Loadjs('$page?enable-fw-js=yes&function=$function');"));
    $sections[]=nginx_action_section("{global_controls_and_security}",$controls);

    $html=array();
    $html[]="<div class=\"nact-wrap\">";
    $html[]=@implode("\n",$sections);
    $html[]="</div>";
    echo $css.$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function download_database():bool{
    $users=new usersMenus();
    if(!$users->AsWebMaster){die();}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/download");
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"]) || empty($data["data"]) || !is_file($data["data"])){
        header("HTTP/1.1 503 Service Unavailable");
        echo is_array($data) && isset($data["Error"]) ? $data["Error"] : "snapshot unavailable";
        return false;
    }
    $db=$data["data"];
    $size=filesize($db);
    $baseName="database.db";
    header('Content-type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$baseName\"");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".$size);
    ob_clean();
    flush();
    readfile($db);
    @unlink($db);
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


function compile_js():bool{
    $tpl    =  new template_admin();$tpl->CLUSTER_CLI=true;
    $ID     = intval($_GET["compile"]);
    $function=$_GET["function"];
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    $js=compile_js_progress($ID,"progress-websites-restart");
    if(isset($_GET["forcediv"])){
        $js=compile_js_progress($ID,$_GET["forcediv"]);
    }

    $type=isset($ligne["type"])?$ligne["type"]:0;
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
    $ID=intval($_GET["compile-firewall"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.nginx.apply.php?serviceid=$ID');\n";
    return admin_tracks("Web Application firewall rules compiled for $servicename");
}

function compile_confirm():bool{
    $ID=intval($_POST["compile-confirm"]);
    $sock=new sockets();
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    $type=isset($ligne["type"])?$ligne["type"]:0;
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
        // Pentest 2026-11-04 : addjs etait decode puis recopie brut dans l'URL (XSS) ;
        // fw.nginx.apply.php attend le base64 et le decode lui-meme.
        $addjs=req_url(jsafter_token($_GET["addjs"]));
    }
    $ID=intval($ID);
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=$addjs');";
}


function action_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $function=$_GET["function"];
    $tpl->js_dialog1("{actions}", "$page?action-popup=yes&function=$function",980);
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
    return $tpl->js_dialog2("#$ID - $servicename {error}", "$page?backend-error-popup=$ID",980);
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
    $ID=intval($_GET["www-tabs"]);
    $ligne=fetch_nginx_service_row($ID);
    $type=intval(isset($ligne["type"])?$ligne["type"]:0);
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
    $ID=intval($_GET["doh-params"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    $sockngix=new socksngix($ID);

    $doh_subfolder=$sockngix->GET_INFO("doh_subfolder");
    if($doh_subfolder==null){$doh_subfolder="dns-query";}

    $tpl->field_hidden("doh-params",$ID);
    $form[]=$tpl->field_text("doh_subfolder","{doh_subfolder}",$doh_subfolder);

    $type=isset($ligne["type"])?$ligne["type"]:0;
    echo $tpl->form_outside("$servicename <small>({DOH_WEB_SERVICE})</small>", $form,"{CREATE_DOH_WEB_SERVICE_SERVICE} ($type)","{apply}","NgixSitesReload();","AsSystemWebMaster");

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
    $ligne=fetch_nginx_service_row($ID);
    return intval(isset($ligne["type"])?$ligne["type"]:0);
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
    $sockngix                   = new socksngix($ID);


    $ligne=fetch_nginx_service_row($ID);
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

    $users                      = new usersMenus();
    $sockngix                   = new socksngix($ID);
    $ASPROXY                    = www_parameters_isProxy($ID);
    $hts_enabled                = intval($sockngix->GET_INFO("hts_enabled"));
    $EnableCrowdSec             = intval($sockngix->GET_INFO("EnableCrowdSec"));
    $ligne=fetch_nginx_service_row($ID);

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
    $sockngix                   = new socksngix($ID);


    $ligne=fetch_nginx_service_row($ID);

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
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsWebMaster")){
        return $tpl->js_error("{ERROR_NO_PRIVS2}");
    }
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
function www_parameters2_auditFrontend_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["monitored-frontend-js"]);
    $ligne=fetch_nginx_service_row($ID);
    $servicename=isset($ligne["servicename"])?$ligne["servicename"]:"";
    return $tpl->js_dialog8("$servicename", "$page?monitored-frontend-popup=$ID");
}
function www_parameters2_auditFrontend_popup(){
    $tpl=new template_admin();
    $serviceid=intval($_GET["monitored-frontend-popup"]);
    $ligne=fetch_nginx_service_row($serviceid);
    $monitored=intval(isset($ligne["monitored"])?$ligne["monitored"]:0);

   echo $tpl->BigCircleCheckbox("monitored-save:$serviceid|monitored-frontend","{audited_artica_cloud}",
        "{artica_cloud_reverse}",$monitored,
        "dialogInstance8.close();LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');NgixSitesReload()");

}
function www_parameters2_auditFrontend_save():bool{
    $ID=intval($_POST["monitored-save"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    $monitored=intval($_POST["monitored-frontend"]);

    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/update-service", [
        "monitored" => $monitored
    ]),true);
    if(!is_array($result) || !$result["Status"]){
        $tpl=new template_admin();
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        echo $tpl->post_error($err);
        return false;
    }
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
// Maintenance mode: the site answers with the maintenance page and the backends
// are no longer contacted. Toggled by MaintenanceSite(), which refreshes this card.
function www_parameters2_maintenance($tpl,$ID){
    $page=CurrentPageName();
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    $tpl->table_form_field_js("Loadjs('$page?MaintenanceSite=$ID&refresh=params')","AsSystemWebMaster");
    $tpl->table_form_field_bool("{maintenance_mode}||{maintenance_mode_explain}",intval($ligne["MaintenanceSite"] ?? 0),"fas fa-person-digging");
    return $tpl;
}
function www_parameters2_phpreverse($tpl,$ID){
    // Lire nginx_services.phpengine via nginx-db (pas GetCache / php_engine JSON).
    $engId=0;
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID"),true);
    if(is_array($resp) && !empty($resp["Status"]) && isset($resp["rows"][0]) && is_array($resp["rows"][0])){
        $row=$resp["rows"][0];
        if(isset($row["phpengine"])){$engId=intval($row["phpengine"]);}
        elseif(isset($row["php_engine"])){$engId=intval($row["php_engine"]);}
    }

    $tpl->table_form_field_js("Loadjs('fw.nginx.sites.phpengine.php?serviceid=$ID')","AsWebMaster");
    if($engId<1){
        $tpl->table_form_field_bool("{php_engine}||<span class=text-danger>{nginx_website_no_php}</span>",0,"fa-brands fa-php");
        return $tpl;
    }

    $label="#$engId";
    $ico="fa-brands fa-php";
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/engines");
    $json=json_decode($raw,true);
    if(is_array($json) && !empty($json["Status"]) && isset($json["Data"]) && is_array($json["Data"])){
        foreach($json["Data"] as $rec){
            if(!is_array($rec) || intval(isset($rec["id"])?$rec["id"]:0)!==$engId){continue;}
            $name=(string)(isset($rec["instancename"])?$rec["instancename"]:"");
            $app=(string)(isset($rec["app_type"])?$rec["app_type"]:"php");
            $rt=(string)(isset($rec["runtime_type"])?$rec["runtime_type"]:"");
            $img=(string)(isset($rec["docker_image"])?$rec["docker_image"]:"");
            if($app==="wordpress"){$ico="fa-brands fa-wordpress";}
            $bits=array("#$engId");
            if($name!==""){$bits[]=$name;}
            if($app!==""){$bits[]=$app;}
            if($rt!==""){$bits[]=$rt;}
            if($img!==""){$bits[]=$img;}
            $label=htmlspecialchars(implode(" · ",$bits));
            break;
        }
    }
    $tpl->table_form_field_text("{php_engine}",$label,$ico);
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
    $page=CurrentPageName();

    $NginxDisableFrontEndSLA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxDisableFrontEndSLA"));
    if($NginxDisableFrontEndSLA==1){
        $tpl->table_form_field_js("","AsWebMaster");
        $tpl->table_form_field_bool("{audited_artica_cloud}||{feature_disabled_globalAdm}",0, ico_list);
        return $tpl;
    }
    $js="Loadjs('$page?monitored-frontend-js=$ID');";
    $ligne=fetch_nginx_service_row($ID);
    $monitored=intval(isset($ligne["monitored"])?$ligne["monitored"]:0);
    $tpl->table_form_field_js($js,"AsWebMaster");
    $tpl->table_form_field_bool("{audited_artica_cloud}",$monitored, ico_list);
    return $tpl;
}

function www_parameters2_mirror($tpl,$ID){
    $tpl->table_form_field_js("Loadjs('fw.nginx.mirror.php?js=yes&serviceid=$ID')","AsWebMaster");
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/mirror/servers/$ID");
    $json=json_decode($raw,true);
    if(isset($json["Status"]) && !$json["Status"]){
        $tpl->table_form_field_text("{mirroring}",$json["Error"] ?? "{error}", "fa-solid fa-clone",true);
        return $tpl;
    }
    $records=array();
    if(isset($json["servers"]) && is_array($json["servers"])){
        $records=$json["servers"];
    }
    if(count($records)==0){
        $tpl->table_form_field_bool("{mirroring}",0, "fa-solid fa-clone");
        return $tpl;
    }
    $tt=array();
    foreach($records as $rec) {
        $host = htmlspecialchars($rec["hostname"] ?? "");
        $port = htmlspecialchars($rec["port"] ?? "");
        $tt[]="$host:$port";
    }
    $tpl->table_form_field_text("{mirroring}",implode(", ", $tt), "fa-solid fa-clone");
    return $tpl;

}

/**
 * Ligne d'entree du module Well-known dans les parametres du site.
 * Calquee sur www_parameters2_mirror ci-dessus.
 *
 * Appelee depuis www_parameters2(), la fonction qui rend REELLEMENT le
 * contenu de la route ?www-parameters= — www_parameters() n'est qu'une
 * coquille AJAX et n'affiche rien.
 */
function www_parameters2_wellknown($tpl,$ID){
    $tpl->table_form_field_js("Loadjs('fw.nginx.well-known.php?js=yes&serviceid=$ID')","AsWebMaster");
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/well-known/$ID");
    $json=json_decode($raw,true);
    if(!is_array($json) || (isset($json["Status"]) && !$json["Status"])){
        $err=isset($json["Error"])?$json["Error"]:"{error}";
        $tpl->table_form_field_text("{well_known_files}",$err,"fas fa-file-lines",true);
        return $tpl;
    }
    $count=0;
    if(isset($json["resources"]) && is_array($json["resources"])){
        foreach($json["resources"] as $r){
            if(isset($r["enabled"]) && $r["enabled"]){$count++;}
        }
    }
    if($count==0){
        $tpl->table_form_field_bool("<span style='text-transform:none'>{well_known_files}</span>||{well_known_files_explain}",0,"fas fa-file-lines");
        return $tpl;
    }
    $tpl->table_form_field_text("<span style='text-transform:none'>{well_known_files}</span>||{well_known_files_explain}",
        "$count {enabled}","fas fa-file-lines");
    return $tpl;
}

/**
 * Ligne d'entree du module SiteMapXML dans les parametres du site.
 * Calquee sur www_parameters2_wellknown ci-dessus.
 *
 * Appelee depuis www_parameters2(), la fonction qui rend REELLEMENT le
 * contenu de la route ?www-parameters= — www_parameters() n'est qu'une
 * coquille AJAX et n'affiche rien.
 */
function www_parameters2_sitemap($tpl,$ID){
    $tpl->table_form_field_js("Loadjs('fw.nginx.sitemap.php?js=yes&serviceid=$ID')","AsWebMaster");
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sitemap-xml/$ID/status");
    $json=json_decode($raw,true);
    if(!is_array($json) || (isset($json["Status"]) && !$json["Status"])){
        $err=isset($json["Error"])?$json["Error"]:"{error}";
        $tpl->table_form_field_text("{xml_sitemap}",$err,"fas fa-sitemap",true);
        return $tpl;
    }
    $enabled=(isset($json["enabled"]) && $json["enabled"])?1:0;
    if($enabled==0){
        $tpl->table_form_field_bool("<span style='text-transform:none'>{xml_sitemap}</span>||{xml_sitemap_explain}",0,"fas fa-sitemap");
        return $tpl;
    }
    // Le chiffre affiche doit etre celui que l Overview appelle « Indexable
    // URLs » : le bilan du dernier run reussi. published.urls compte les
    // entrees de tous les fichiers (une page illustree y figure deux fois,
    // dans le sitemap des pages ET dans celui des images) — l afficher sous
    // le libelle « indexable » contredisait l Overview (8 contre 6).
    if(isset($json["last_success"]["urls_indexable"])){
        $urls=intval($json["last_success"]["urls_indexable"]);
        $tpl->table_form_field_text("{xml_sitemap}","$urls {indexable_urls}","fas fa-sitemap");
        return $tpl;
    }
    $urls=0;
    if(isset($json["published"]["urls"])){$urls=intval($json["published"]["urls"]);}
    $tpl->table_form_field_text("{xml_sitemap}","$urls {sitemap_published_urls}","fas fa-sitemap");
    return $tpl;
}

function www_parameters2_trap_files($tpl,$ID){
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

    $isSSL=false;
    // On check les ports via the daemon API.
    $results=fetch_stream_ports_for_service($ID);
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
    // Collecteur : les helpers ci-dessous ecrivent des lignes, ngxp_render() en fait des tuiles.
    $tpl                        = new NgxpCollector();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["www-parameters2"]);
    $sockngix                   = new socksngix($ID);
    $ASPROXY                    = www_parameters_isProxy($ID);
    $GenericHarden              = intval($sockngix->GET_INFO("GenericHarden"));
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }

    // Schema migrations are owned by the daemon (nginxsites/DBConf.go FixTables);
    // the previous PHP-side ALTER TABLE statements are now redundant.

    $ligne=fetch_nginx_service_row($ID);
    $servicename=get_servicename($ID);
    $type=isset($ligne["type"])?$ligne["type"]:0;


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
    // En-tete de la page de tuiles : les memes faits que la ligne ci-dessus, separes.
    $hdr=array(
        "name"=>$servicename,
        "type"=>isset($Types[$type])?$Types[$type]:"",
        "default"=>(intval(isset($ligne["isDefault"])?$ligne["isDefault"]:0)==1),
        "websockets"=>($WebSocketsSupport==1),
        "debug"=>($debug==1),
        "redirect"=>(strlen($RedirectQueries)>3)?$RedirectQueries:"",
        "saved"=>($goodconftime>0)?$goodconftimeStr:"",
    );

    if($type==1) {
        $tpl = www_parameters2_phpreverse($tpl, $ID);
    }
    $tpl=www_parameters2_vitrification($tpl,$ID);
    if($type!=5 && $type!=14) {
        $tpl = www_parameters2_maintenance($tpl, $ID);
    }
    $tpl=www_parameters2_auditFrontend($tpl,$ID);

    if(!isset($noSSL[$type])) {
        $tpl = www_parameters2_isSSL($tpl, $ID);
    }
    $tpl = www_parameters2_mirror($tpl, $ID);
    $tpl = www_parameters2_wellknown($tpl, $ID);
    $tpl = www_parameters2_sitemap($tpl, $ID);

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
        // Les limites s'editent dans la fenetre « general » : sans cet ouvreur la
        // ligne heritait de celui du sitemap pose juste avant.
        $tpl->table_form_field_js("Loadjs('$page?www-parameters-general-js=$ID')");
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
        foreach (fetch_httrack_sites_ordered() as $WebCopyLine){
            $WebCopyList[$WebCopyLine["ID"]]=$WebCopyLine["enforceuri"];
        }
        $tpl->table_form_field_text("{mirror}",isset($WebCopyList[$WebCopyID])?$WebCopyList[$WebCopyID]:"",ico_copy);
    }

    // Not show others settings because there is no sense to continue ( see RedirectQueries for example)
    if(!$CONTINUE_DETAILS){
        return ngxp_render($tpl,$ID,$hdr,$FINAL_ERROR);
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
            // Enabled features, as in the Optimization window. A feature enabled but
            // unable to work (module missing or disabled globally) is a configuration
            // defect: amber, never red. A site with nothing enabled is a choice.
            $nsum=nopt_summary($ID);
            $nval=@implode(", ",$nsum["on"]);
            if(count($nsum["broken"])>0){
                $nval=trim($nval.($nval==""?"":", ")."{nopt_unavailable}: ".@implode(", ",$nsum["broken"]));
            }
            if($nval==""){$nval="{nopt_sum_none}";}
            $tpl->table_form_field_text("{optimization}", $nval, ico_speed, count($nsum["broken"])>0?"warn":false);
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

            // OIDC SSO — only for reverse-proxy service types (2, 4, 19)
            if($type == 2 || $type == 4 || $type == 19) {
                $oidcResp    = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/oidc/rule/$ID"), true);
                $oidcEnabled  = (is_array($oidcResp) && isset($oidcResp["rule"]["enabled"])) ? intval($oidcResp["rule"]["enabled"]) : 0;
                $oidcProvider = (is_array($oidcResp) && isset($oidcResp["rule"]["provider_name"])) ? htmlspecialchars($oidcResp["rule"]["provider_name"]) : "";
                $tpl->table_form_field_js("Loadjs('fw.nginx.oidc.php?js=yes&serviceid=$ID')");
                if ($oidcEnabled && strlen($oidcProvider) > 0) {
                    $tpl->table_form_field_text("OIDC SSO", "{active2} &#8212; $oidcProvider", ico_shield);
                } elseif ($oidcEnabled) {
                    $tpl->table_form_field_bool("OIDC SSO", 1, ico_shield);
                } else {
                    $tpl->table_form_field_bool("OIDC SSO", 0, ico_shield_disabled);
                }
            }
        }

        $tpl = www_crowdsec($ID, $tpl);
        if(!isset($NoWaf[$type])) {
            $tpl = www_parameters2_waf($tpl, $ID);
            $tpl = www_dangerous_paths($ID, $tpl);
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
                include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
                $cdbSSL=new CertificatesDB();
                $subSSL=$cdbSSL->subGetById(intval($re[1]));
                $ssl_certificate=is_object($subSSL)?(string)($subSSL->commonName ?? ""):"";
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

            // Own link: without it the row kept the SSL parameters link set above.
            $tpl->table_form_field_js("Loadjs('fw.nginx.ciphers.php?service-js=$ID')");
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
    return ngxp_render($tpl,$ID,$hdr,$FINAL_ERROR);
}

// ---------------------------------------------------------------------------
// Parametres du site en tuiles (maquette stitch_reverse_proxy_dialog).
// Menu de sections a gauche, recherche en haut, une tuile par fonctionnalite.
// Toutes les valeurs viennent des lignes captees par NgxpCollector : aucune
// donnee n'est calculee ici, seulement classee et mise en forme.
// ---------------------------------------------------------------------------

/** Sections, dans l'ordre d'affichage : cle => array(titre, icone). */
function ngxp_sections():array{
    return array(
        "service"     => array("{ngxp_sec_service}","fa-solid fa-server"),
        "publishing"  => array("{ngxp_sec_publishing}","fa-solid fa-upload"),
        "performance" => array("{ngxp_sec_performance}","fa-solid fa-gauge-high"),
        "access"      => array("{ngxp_sec_access}","fa-solid fa-user-lock"),
        "threat"      => array("{ngxp_sec_threat}","fa-solid fa-shield-halved"),
        "headers"     => array("{ngxp_sec_headers}","fa-solid fa-heading"),
        "ssl"         => array("{ssl_parameters}","fa-solid fa-lock"),
    );
}

/**
 * Catalogue des tuiles, dans l'ordre d'affichage de chaque section :
 * cle => array(section, titre, description, icone, valeur seule).
 * « valeur seule » : la tuile montre une valeur, pas un etat actif/inactif.
 */
function ngxp_catalog():array{
    return array(
        "php"          => array("service","{php_engine}","{ngxp_d_php}","fa-brands fa-php",false),
        "maintenance"  => array("service","{maintenance_mode}","{ngxp_d_maintenance}","fa-solid fa-person-digging",false),
        "adfs"         => array("service","ADFS {parameters}","{ngxp_d_adfs}","fa-solid fa-id-badge",true),
        "cloudaudit"   => array("service","{audited_artica_cloud}","{ngxp_d_cloudaudit}","fa-solid fa-cloud",false),
        "vitrification"=> array("publishing","{vitrification}","{ngxp_d_vitrification}","fa-solid fa-wine-glass",false),
        "mirroring"    => array("publishing","{mirroring}","{ngxp_d_mirroring}","fa-solid fa-clone",false),
        "wellknown"    => array("publishing","{well_known_files}","{ngxp_d_wellknown}","fa-solid fa-file-lines",false),
        "sitemap"      => array("publishing","{xml_sitemap}","{xml_sitemap_explain}","fa-solid fa-sitemap",false),
        "webcopy"      => array("publishing","{mirror}","{ngxp_d_webcopy}","fa-solid fa-copy",true),
        "limits"       => array("performance","{ngxp_limits}","{ngxp_d_limits}","fa-solid fa-gauge",true),
        "dynrate"      => array("performance","{dynamic_rate_limiting}","{ngxp_d_dynrate}","fa-solid fa-sliders",false),
        "latency"      => array("performance","{latency}","{ngxp_d_latency}","fa-solid fa-stopwatch",true),
        "optimization" => array("performance","{optimization}","{ngxp_d_optimization}","fa-solid fa-bolt",true),
        "blocked"      => array("access","{website_blocked}","{ngxp_d_blocked}","fa-solid fa-ban",false),
        "countries"    => array("access","{countries}","{ngxp_d_countries}","fa-solid fa-earth-europe",false),
        "useragents"   => array("access","{http_user_agent}","{ngxp_d_useragents}","fa-solid fa-user-secret",false),
        "uris"         => array("access","{ngxp_urls}","{ngxp_d_uris}","fa-solid fa-link",false),
        "extensions"   => array("access","{allowed_extensions}","{ngxp_d_extensions}","fa-solid fa-file-circle-check",false),
        "protocols"    => array("access","{protocols}","{ngxp_d_protocols}","fa-solid fa-right-left",true),
        "oidc"         => array("access","OIDC SSO","{ngxp_d_oidc}","fa-solid fa-key",false),
        "waf"          => array("threat","{WAF_LONG}","{ngxp_d_waf}","fa-solid fa-shield-halved",false),
        "crowdsec"     => array("threat","{ngxp_crowdsec}","{ngxp_d_crowdsec}","fa-solid fa-users-viewfinder",false),
        "reputation"   => array("threat","{reputation_service}","{ngxp_d_reputation}","fa-solid fa-star-half-stroke",false),
        "botnets"      => array("threat","{BotChecker}","{ngxp_d_botnets}","fa-solid fa-robot",false),
        "jsbc"         => array("threat","{signed_js_browser_challenge}","{ngxp_d_jsbc}","fa-brands fa-js",false),
        "fingerprint"  => array("threat","{fingerprinting}","{ngxp_d_fingerprint}","fa-solid fa-fingerprint",false),
        "trapfiles"    => array("threat","{trap_files}","{ngxp_d_trapfiles}","fa-solid fa-mask",false),
        "dangerous"    => array("threat","{dangerous_paths}","{ngxp_d_dangerous}","fa-solid fa-road-barrier",false),
        "hardening"    => array("threat","{generic_hardening}","{ngxp_d_hardening}","fa-solid fa-helmet-safety",false),
        "csp"          => array("headers","Content Security Policy","{ngxp_d_csp}","fa-solid fa-file-shield",false),
        "permpolicy"   => array("headers","Permissions Policy","{ngxp_d_permpolicy}","fa-solid fa-list-check",false),
        "hideheaders"  => array("headers","{header_remove_rules}","{ngxp_d_hideheaders}","fa-solid fa-eye-slash",false),
        "headers"      => array("headers","{headers}","{ngxp_d_headers}","fa-solid fa-heading",false),
        "certificate"  => array("ssl","{certificate}","{ngxp_d_certificate}","fa-solid fa-certificate",true),
        "redirect443"  => array("ssl","{Redirect80To443}","{ngxp_d_redirect443}","fa-solid fa-lock",false),
        "ciphers"      => array("ssl","{ngxp_ciphers}","{ngxp_d_ciphers}","fa-solid fa-shuffle",true),
        "clientcert"   => array("ssl","{client_side_certificate}","{ngxp_d_clientcert}","fa-solid fa-id-card",false),
        "sni"          => array("ssl","{snih2}","{ngxp_d_sni}","fa-solid fa-signs-post",false),
    );
}

/** Libelle d'une ligne captee => cle du catalogue ("" si inconnue). */
function ngxp_row_key($field):string{
    $l=trim(strip_tags(explode("||",(string)$field,2)[0]));
    $lit=array("CrowdSec"=>"crowdsec","OIDC"=>"oidc","Permissions Policy"=>"permpolicy",
        "Content Security Policy"=>"csp","ADFS"=>"adfs");
    foreach($lit as $prefix=>$key){
        if(stripos($l,$prefix)===0){return $key;}
    }
    if(!preg_match('/\{([A-Za-z0-9_]+)\}/',$l,$re)){return "";}
    $tok=array("service_name2"=>"service","php_engine"=>"php","maintenance_mode"=>"maintenance",
        "vitrification"=>"vitrification","audited_artica_cloud"=>"cloudaudit","mirroring"=>"mirroring",
        "well_known_files"=>"wellknown","xml_sitemap"=>"sitemap","mirror"=>"webcopy","limits"=>"limits",
        "dynamic_rate_limiting"=>"dynrate","latency"=>"latency","optimization"=>"optimization",
        "trap_files"=>"trapfiles","BotChecker"=>"botnets","signed_js_browser_challenge"=>"jsbc",
        "website_blocked"=>"blocked","fingerprinting"=>"fingerprint","reputation_service"=>"reputation",
        "allowed_extensions"=>"extensions","protocols"=>"protocols","generic_hardening"=>"hardening",
        "WAF_LONG"=>"waf","dangerous_paths"=>"dangerous","header_remove_rules"=>"hideheaders",
        "countries"=>"countries","http_user_agent"=>"useragents","urls"=>"uris","headers"=>"headers",
        "missing"=>"certificate","certificate"=>"certificate","Redirect80To443"=>"redirect443",
        "ssl_ciphers"=>"ciphers","client_side_certificate"=>"clientcert","snih2"=>"sni");
    return isset($tok[$re[1]])?$tok[$re[1]]:"";
}

/** Texte affichable : sans balise, entites resolues puis echappees, {tokens} gardes. */
function ngxp_clean($v):string{
    $v=html_entity_decode(strip_tags(str_replace(array("<br>","<br/>","&nbsp;"),array(" "," "," "),(string)$v)),ENT_QUOTES,"UTF-8");
    $v=trim(preg_replace('/\s+/u',' ',$v));
    return htmlspecialchars($v,ENT_QUOTES,"UTF-8");
}

/**
 * Le modele : une entree par tuile, rangee par section.
 * Etats : on | off | err | na (indisponible : non installe, desactive globalement,
 * aucun ouvreur) | none (tuile de valeur, sans badge).
 */
function ngxp_model($rows,$ID):array{
    $page=CurrentPageName();
    $catalog=ngxp_catalog();
    $order=array_flip(array_keys($catalog));
    $offMarks=array("{inactive2}","{inactive}","{disabled}","{not_used}");
    $naMarks=array("{not_compiled}","{globally_disabled}","{not_installed}");
    $tiles=array();
    $warns=array();
    $seen=array();
    foreach($rows as $r){
        if($r["kind"]=="warn"){$warns[]=$r["field"];continue;}
        $key=ngxp_row_key($r["field"]);
        if($key=="service"){continue;}
        $parts=explode("||",$r["field"],2);
        $explain=isset($parts[1])?$parts[1]:"";
        if($key=="" || !isset($catalog[$key])){
            // Ligne qu'aucune entree du catalogue ne connait : on l'affiche quand meme.
            $key="x".count($tiles);
            $catalog[$key]=array("service",ngxp_clean($parts[0]),"","fa-solid fa-sliders",true);
            $order[$key]=1000+count($tiles);
        }
        $base=$key;
        if(isset($seen[$base])){$key=$base."_".count($tiles);}
        $seen[$base]=true;
        $c=$catalog[$base];
        $t=array("key"=>$key,"section"=>$c[0],"title"=>$c[1],"desc"=>$c[2],"icon"=>$c[3],
            "state"=>"none","value"=>"","chips"=>array(),"donut"=>-1,
            "js"=>$r["js"],"ro"=>$r["ro"],"btn"=>"{ngxp_edit}","primary"=>false,"order"=>$order[$base]+count($tiles)/1000);
        if($base=="php" && strpos($r["ico"],"wordpress")!==false){$t["icon"]=$r["ico"];}

        $value=$r["value"];
        $btnLabel="";
        if(is_array($value) && isset($value["BUTTON"])){
            // Valeur « bouton » (CSP, chiffrements) : VALUE = etat, LABEL = compteur.
            $bv=isset($value["BUTTON"]["VALUE"])?(string)$value["BUTTON"]["VALUE"]:"";
            $btnLabel=isset($value["BUTTON"]["LABEL"])?(string)$value["BUTTON"]["LABEL"]:"";
            $value=$bv;
        }
        $value=(string)$value;

        if($r["kind"]=="bool"){
            $t["state"]=(intval($value)==1)?"on":"off";
            if(strpos($explain,"text-danger")!==false){
                $t["state"]="err";
                $t["value"]=ngxp_clean($explain);
            }
        }elseif($r["kind"]=="button"){
            $t["state"]="err";
            $t["value"]=ngxp_clean($parts[0]);
            $t["btn"]=$value;
            $t["primary"]=true;
        }else{
            $isOff=false;$isNa=false;
            foreach($offMarks as $m){if(strpos($value,$m)!==false){$isOff=true;}}
            foreach($naMarks as $m){if(strpos($value,$m)!==false){$isNa=true;}}
            if($base=="headers" && strpos($value,"{none}")!==false){$isOff=true;}
            if($r["error"]){
                $t["state"]="err";
            }elseif($isNa){
                $t["state"]="na";
            }elseif($isOff){
                $t["state"]="off";
            }elseif(!$c[4]){
                $t["state"]="on";
            }
            $line=$value;
            // « Active » est deja dit par le badge : on ne garde que le detail.
            if(strpos($line,"{active2}")===0){$line=preg_replace('/^\s*(\/|-|&#8212;|\x{2014})?\s*/u','',substr($line,strlen("{active2}")));}
            $line=str_replace("{none}","{ngxp_none}",$line);
            if($t["state"]=="off"){$line="";}
            if($base=="latency"){$line=str_replace(" Urls"," {ngxp_urls}",$line);}
            if($base=="protocols"){
                $raw=trim(str_replace("{allow}","",strip_tags($line)));
                foreach(explode(",",$raw) as $p){$p=trim($p);if($p!==""){$t["chips"][]=htmlspecialchars($p,ENT_QUOTES,"UTF-8");}}
                $line="";
            }elseif($base=="headers" && $t["state"]=="on"){
                foreach(explode(",",strip_tags($line)) as $p){$p=trim($p);if($p!==""){$t["chips"][]=ngxp_clean($p);}}
                $line="";
            }elseif($base=="optimization"){
                if(preg_match('/([0-9]+)\s*%/',$line,$re)){$t["donut"]=intval($re[1]);}
                if($r["error"]){$t["state"]="err";}
            }
            if($btnLabel!==""){
                // CSP : « 8 rules » ; chiffrements : « 10 rules · Prefer server ciphers ».
                $line=$btnLabel;
                if($base=="ciphers" && trim($value)!==""){$line.=" \u{00B7} ".$value;}
            }
            if($line!==""){$t["value"]=ngxp_clean($line);}
            if(in_array($base,array("countries","useragents","uris")) && $t["state"]=="on" && $t["value"]!==""){
                $t["value"]="{deny} \u{00B7} ".$t["value"];
            }
        }

        // Pas d'ouvreur : droit insuffisant (lecture seule) ou fonction indisponible.
        if(strlen(trim($t["js"]))<3){
            if(!$t["ro"] && $t["state"]!="err"){$t["state"]="na";}
            if($t["state"]=="na" && $t["value"]==="" && $explain!==""){$t["value"]=ngxp_clean($explain);}
        }
        // Bascules directes : le bouton dit ce qu'il fait.
        if($base=="maintenance" || $base=="botnets" || $base=="blocked"){
            $t["btn"]=($t["state"]=="on")?"{ngxp_disable}":"{ngxp_enable}";
            if($base=="maintenance" && $t["state"]!="on" && strlen($t["js"])>3){
                // Activer la maintenance coupe le site : confirmation d'abord.
                $t["js"]="Loadjs('$page?ngxp-maintenance-js=$ID')";
            }
        }
        $tiles[]=$t;
    }
    usort($tiles,function($a,$b){ return ($a["order"]<$b["order"])?-1:(($a["order"]>$b["order"])?1:0); });
    $bySection=array();
    foreach(ngxp_sections() as $sk=>$sv){$bySection[$sk]=array();}
    foreach($tiles as $t){$bySection[$t["section"]][]=$t;}
    return array("sections"=>$bySection,"warns"=>$warns,"count"=>count($tiles));
}

function ngxp_badge($state):string{
    switch($state){
        case "on":  return "<span class='ak-pill ak-ok'><i class='fa-solid fa-circle-check'></i> {ngxp_active}</span>";
        case "off": return "<span class='ak-pill ak-off'><i class='fa-solid fa-circle-minus'></i> {ngxp_inactive}</span>";
        case "err": return "<span class='ak-pill ak-err'><i class='fa-solid fa-triangle-exclamation'></i> {ngxp_error}</span>";
        case "na":  return "<span class='ak-pill ak-warn'><i class='fa-solid fa-ban'></i> {ngxp_unavailable}</span>";
    }
    return "";
}

function ngxp_tile($t):string{
    $cls="ak-card ngxp-tile ngxp-st-".$t["state"];
    $h=array();
    $h[]="<div class='$cls' data-key='{$t["key"]}'>";
    $h[]="<div class='ngxp-tile-top'>";
    $h[]="<span class='ak-ico ak-ico-sm ak-ico-sq ".(array("on"=>"","err"=>"ak-err","na"=>"ak-warn")[$t["state"]] ?? "ak-off")."'><i class='{$t["icon"]}'></i></span>";
    $h[]="<span class='ngxp-tile-title'>{$t["title"]}</span>";
    $h[]=ngxp_badge($t["state"]);
    $h[]="</div>";
    if($t["desc"]!==""){$h[]="<div class='ngxp-desc'>{$t["desc"]}</div>";}
    $h[]="<div class='ngxp-tile-foot'>";
    $h[]="<div class='ngxp-val'>";
    if($t["donut"]>=0){
        $h[]="<span class='ngxp-donut' aria-hidden='true'>{$t["donut"]}/100</span><span>{$t["donut"]} %</span>";
    }elseif(count($t["chips"])>0){
        foreach($t["chips"] as $c){$h[]="<span class='ak-tag ak-off'>$c</span>";}
    }else{
        $h[]="<span class='ngxp-val-t'>{$t["value"]}</span>";
    }
    $h[]="</div>";
    $js=str_replace('"',"&quot;",$t["js"]);
    $btnCls="ak-btn ngxp-btn".($t["primary"]?" ak-btn-primary":"");
    $ico=$t["primary"]?"fa-solid fa-plus":"fa-solid fa-pen";
    if($t["key"]=="maintenance" || $t["key"]=="botnets" || $t["key"]=="blocked"){$ico="fa-solid fa-power-off";}
    if(strlen(trim($t["js"]))<3){
        $why=$t["ro"]?"{ngxp_read_only}":"{ngxp_unavailable}";
        $h[]="<button type='button' class='$btnCls' disabled title=\"$why\"><i class='$ico'></i> {$t["btn"]}</button>";
    }else{
        $h[]="<button type='button' class='$btnCls' data-ngxp-title='1' OnClick=\"$js\"><i class='$ico'></i> {$t["btn"]}</button>";
    }
    $h[]="</div></div>";
    return @implode("",$h);
}

function ngxp_header($hdr,$ID):string{
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='ak-card ngxp-head'>";
    $h[]="<div class='ngxp-head-l'>";
    $h[]="<span class='ngxp-head-ico'><i class='fa-solid fa-globe'></i></span>";
    $h[]="<span class='ngxp-head-name'>".htmlspecialchars($hdr["name"],ENT_QUOTES,"UTF-8")."</span>";
    if($hdr["type"]!==""){$h[]="<span class='ak-pill ak-off'>{$hdr["type"]}</span>";}
    if($hdr["default"]){$h[]="<span class='ak-pill ak-accent'>{default_server}</span>";}
    if($hdr["websockets"]){$h[]="<span class='ak-pill ak-off'>{websockets_support}</span>";}
    if($hdr["redirect"]!==""){$h[]="<span class='ak-pill ak-off'><i class='fa-solid fa-share'></i> ".ngxp_clean($hdr["redirect"])."</span>";}
    if($hdr["debug"]){$h[]="<span class='ak-pill ak-err'><i class='fa-solid fa-bug'></i> {ngxp_debug}</span>";}
    $h[]="</div>";
    $h[]="<div class='ngxp-head-r'>";
    if($hdr["saved"]!==""){$h[]="<span class='ngxp-saved'>{saved_on} ".htmlspecialchars($hdr["saved"],ENT_QUOTES,"UTF-8")."</span>";}
    $h[]="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?www-parameters-general-js=$ID')\"><i class='fa-solid fa-pen'></i> {ngxp_edit}</button>";
    $h[]="</div></div>";
    return @implode("",$h);
}

function ngxp_search($ID,$count):string{
    $h=array();
    $h[]="<div class='ngxp-search'>";
    $h[]="<div class='ngxp-search-box'>";
    $h[]="<span class='ngxp-search-ico'><i class='fa-solid fa-magnifying-glass'></i></span>";
    $h[]="<input type='text' id='ngxp-q-$ID' class='ngxp-q' autocomplete='off' placeholder=\"{ngxp_search_placeholder}\" aria-label=\"{ngxp_search_placeholder}\">";
    $h[]="<button type='button' class='ngxp-clear' id='ngxp-clear-$ID' aria-label=\"{ngxp_clear_search}\" title=\"{ngxp_clear_search}\" style='display:none'><i class='fa-solid fa-xmark'></i></button>";
    $h[]="<kbd class='ngxp-kbd'>/</kbd>";
    $h[]="</div>";
    $h[]="<div class='ngxp-count' id='ngxp-count-$ID' aria-live='polite'>$count {ngxp_features}</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function ngxp_nav($model,$ID):string{
    $h=array();
    $h[]="<nav class='ngxp-nav' aria-label=\"{ngxp_sections}\">";
    $first=true;
    foreach(ngxp_sections() as $sk=>$sv){
        $tiles=$model["sections"][$sk];
        if(count($tiles)==0){continue;}
        $err="";
        foreach($tiles as $t){if($t["state"]=="err"){$err="<span class='ak-dot ak-err' title=\"{ngxp_section_error}\" aria-label=\"{ngxp_section_error}\"></span>";break;}}
        $cls="ngxp-nav-i".($first?" ngxp-nav-on":"");
        $cur=$first?" aria-current='true'":"";
        $h[]="<a href=\"javascript:blur();\" class='$cls' data-sec='$sk'$cur>";
        $h[]="<span class='ngxp-nav-ico'><i class='{$sv[1]}'></i></span><span class='ngxp-nav-t'>{$sv[0]}</span>$err";
        $h[]="<span class='ak-count ngxp-nav-n'>".count($tiles)."</span></a>";
        $first=false;
    }
    $h[]="</nav>";
    return @implode("",$h);
}

function ngxp_sections_html($model,$ID):string{
    $h=array();
    foreach(ngxp_sections() as $sk=>$sv){
        $tiles=$model["sections"][$sk];
        if(count($tiles)==0){continue;}
        $h[]="<section class='ngxp-sec' id='ngxp-sec-$ID-$sk' data-sec='$sk'>";
        $h[]="<div class='ngxp-sec-h'><span class='ngxp-sec-ico'><i class='{$sv[1]}'></i></span><span class='ngxp-sec-t'>{$sv[0]}</span><span class='ak-count ngxp-sec-n'>".count($tiles)."</span></div>";
        $h[]="<div class='ngxp-grid'>";
        foreach($tiles as $t){$h[]=ngxp_tile($t);}
        $h[]="</div></section>";
    }
    $h[]="<div class='ngxp-empty' style='display:none'><i class='fa-solid fa-magnifying-glass-minus'></i> <span>{ngxp_no_match}</span> <a href=\"javascript:blur();\" class='ngxp-empty-clear'>{ngxp_clear_search}</a></div>";
    return @implode("",$h);
}

function ngxp_render($tpl,$ID,$hdr,$FINAL_ERROR):bool{
    $model=ngxp_model($tpl->NGXP_ROWS,$ID);
    $h=array();
    $h[]="<div class='ngxp ak' id='ngxp-$ID' data-id='$ID'>";
    $h[]=ngxp_header($hdr,$ID);
    $h[]=ngxp_search($ID,$model["count"]);
    $alerts=array_merge($FINAL_ERROR,$model["warns"]);
    foreach($alerts as $a){
        $h[]="<div class='ak-banner ak-warn ngxp-alert' role='alert'><i class='fa-solid fa-triangle-exclamation'></i> <span>$a</span></div>";
    }
    $h[]="<div class='ngxp-body'>";
    $h[]=ngxp_nav($model,$ID);
    $h[]="<div class='ngxp-main'>".ngxp_sections_html($model,$ID)."</div>";
    $h[]="</div></div>";
    // CSS et JS concatenes HORS de _ENGINE_parse_body : leurs {…} seraient lus comme des tokens.
    echo ngxp_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).ngxp_js($ID);
    return true;
}

function ngxp_style():string{
    if(isset($GLOBALS["NGXP_STYLE"])){return "";}
    $GLOBALS["NGXP_STYLE"]=true;
    $acc="#10b981";$accDark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_dark"])){$accDark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.ngxp{color:#111d23;padding:6px 2px 10px}
.ngxp-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 18px;margin-bottom:12px}
.ngxp-head-l{display:flex;align-items:center;gap:10px;flex-wrap:wrap;min-width:0}
.ngxp-head-ico{font-size:18px;color:$accDark}
.ngxp-head-name{font-size:18px;font-weight:600;line-height:24px;color:#111d23;word-break:break-all}
.ngxp-head-r{display:flex;align-items:center;gap:14px}
.ngxp-saved{font-size:13px;color:#6d7a77}
.ngxp-search{display:flex;gap:12px;align-items:stretch;margin-bottom:12px}
.ngxp-search-box{position:relative;flex:1 1 auto}
.ngxp-search-ico{position:absolute;left:14px;top:0;height:44px;display:flex;align-items:center;font-size:14px;color:#6d7a77;pointer-events:none}
.ngxp-q{width:100%;height:44px;padding:0 80px 0 40px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;font-size:14px;color:#111d23;outline:none}
.ngxp-q:focus{border-color:$acc;box-shadow:0 0 0 2px $soft}
.ngxp-clear{position:absolute;right:40px;top:8px;width:28px;height:28px;border:0;border-radius:6px;background:transparent;color:#6d7a77;font-size:13px;cursor:pointer}
.ngxp-clear:hover{background:#e3f0f8;color:#111d23}
.ngxp-kbd{position:absolute;right:12px;top:11px;font-size:12px;line-height:20px;padding:0 7px;border:1px solid #e2e8f0;border-radius:5px;background:#f8fafc;color:#6d7a77}
.ngxp-count{display:flex;align-items:center;padding:0 16px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;font-size:13px;font-weight:600;color:#3d4947;white-space:nowrap}
.ngxp-alert{align-items:flex-start;padding:12px 16px;margin-bottom:12px;border-left:4px solid #865300;border-radius:0 10px 10px 0;font-size:13px}
.ngxp-alert svg{margin-top:2px}
.ngxp-body{display:flex;gap:20px;align-items:flex-start}
.ngxp-nav{flex:0 0 210px;width:210px;position:sticky;top:10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:6px}
.ngxp-nav-i{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;border-left:3px solid transparent;font-size:13px;font-weight:600;color:#3d4947;text-decoration:none!important}
.ngxp-nav-i:hover{background:#f8fafc;color:#111d23}
.ngxp-nav-i:focus-visible,.ngxp-clear:focus-visible,.ngxp-empty-clear:focus-visible{outline:2px solid $acc;outline-offset:2px}
.ngxp-nav-on{background:$soft;border-left-color:$acc;color:$accDark}
.ngxp-nav-on:hover{background:$soft;color:$accDark}
.ngxp-nav-ico{width:16px;text-align:center;font-size:13px}
.ngxp-nav-t{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ngxp-nav-zero{opacity:.45}
.ngxp-main{flex:1 1 auto;min-width:0}
.ngxp-sec{margin-bottom:22px;scroll-margin-top:12px}
.ngxp-sec-h{display:flex;align-items:center;gap:8px;padding-bottom:8px;margin-bottom:12px;border-bottom:1px solid #e2e8f0}
.ngxp-sec-ico{font-size:14px;color:#3d4947}
.ngxp-sec-t{font-size:16px;font-weight:600;color:#111d23}
.ngxp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.ngxp-tile{display:flex;flex-direction:column;min-height:150px;padding:14px;transition:border-color .15s,box-shadow .15s}
.ngxp-tile:hover{border-color:#cbd5e1;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.ngxp-st-err{border-color:#f5b5ae}
.ngxp-tile-top{display:flex;align-items:flex-start;gap:10px}
.ngxp-tile-title{flex:1 1 auto;min-width:0;font-size:14px;font-weight:600;line-height:19px;color:#111d23;padding-top:6px}
.ngxp-tile-top .ak-pill{flex:0 0 auto;margin-top:2px}
.ngxp-desc{margin-top:8px;font-size:13px;line-height:18px;color:#4b5563;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ngxp-tile-foot{margin-top:auto;padding-top:12px;display:flex;align-items:center;justify-content:space-between;gap:10px}
.ngxp-val{flex:1 1 auto;min-width:0;display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:13px;font-weight:600;color:#111d23}
.ngxp-val-t{max-width:100%;word-break:break-word;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ngxp-st-err .ngxp-val-t{color:#ba1a1a;-webkit-line-clamp:4}
.ngxp-st-na .ngxp-val-t{color:#865300;font-weight:500;-webkit-line-clamp:4}
.ngxp-btn{flex:0 0 auto}
.ngxp .ak-btn[disabled]{opacity:.5;cursor:not-allowed}
.ngxp-empty{padding:40px 20px;text-align:center;font-size:14px;color:#6d7a77;background:#fff;border:1px dashed #cbd5e1;border-radius:12px}
.ngxp-empty-clear{margin-left:6px;color:$accDark;font-weight:600}
.ngxp-hide{display:none!important}
@media (prefers-reduced-motion:reduce){.ngxp-tile{transition:none}}
</style>";
}

function ngxp_js($ID):string{
    $ID=intval($ID);
    $js='<script>
(function(){
 var id=__ID__;
 var root=document.getElementById("ngxp-"+id); if(!root){return;}
 var q=document.getElementById("ngxp-q-"+id), clr=document.getElementById("ngxp-clear-"+id), cnt=document.getElementById("ngxp-count-"+id);
 var tiles=root.querySelectorAll(".ngxp-tile"), secs=root.querySelectorAll(".ngxp-sec"), navs=root.querySelectorAll(".ngxp-nav-i");
 var empty=root.querySelector(".ngxp-empty"), total=tiles.length;
 var wordFeatures=(cnt.textContent||"").replace(/^[0-9]+\s*/,"");
 var reduced=window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
 for(var i=0;i<tiles.length;i++){
   var sec=tiles[i].closest(".ngxp-sec"), st=sec?sec.querySelector(".ngxp-sec-t"):null;
   tiles[i].setAttribute("data-s",((tiles[i].textContent||"")+" "+(st?st.textContent:"")).toLowerCase().replace(/\s+/g," "));
   var t=tiles[i].querySelector(".ngxp-tile-title"), b=tiles[i].querySelector(".ngxp-btn");
   if(t && b){ b.setAttribute("aria-label",(b.textContent||"").trim()+" "+(t.textContent||"").trim()); }
 }
 function filter(){
   var v=(q.value||"").toLowerCase().trim(), words=v.split(/\s+/), shown=0;
   window.NgxpQuery=window.NgxpQuery||{}; window.NgxpQuery[id]=q.value;
   clr.style.display=v===""?"none":"";
   for(var i=0;i<tiles.length;i++){
     var ok=true, s=tiles[i].getAttribute("data-s");
     if(v!==""){ for(var w=0;w<words.length;w++){ if(s.indexOf(words[w])<0){ok=false;break;} } }
     tiles[i].classList.toggle("ngxp-hide",!ok); if(ok){shown++;}
   }
   for(var k=0;k<secs.length;k++){
     var n=secs[k].querySelectorAll(".ngxp-tile:not(.ngxp-hide)").length, key=secs[k].getAttribute("data-sec");
     secs[k].classList.toggle("ngxp-hide",n===0);
     var nc=secs[k].querySelector(".ngxp-sec-n"); if(nc){nc.textContent=n;}
     for(var j=0;j<navs.length;j++){ if(navs[j].getAttribute("data-sec")===key){
       navs[j].classList.toggle("ngxp-nav-zero",n===0);
       var nn=navs[j].querySelector(".ngxp-nav-n"); if(nn){nn.textContent=n;}
     } }
   }
   empty.style.display=(shown===0)?"":"none";
   var e=empty.querySelector("span"); if(e && !e.getAttribute("data-t")){e.setAttribute("data-t",e.textContent);}
   if(e){e.textContent=e.getAttribute("data-t")+" “"+q.value.trim()+"”";}
   cnt.textContent=(v===""?total:shown+" / "+total)+" "+wordFeatures;
 }
 function clear(){ q.value=""; filter(); q.focus(); }
 q.addEventListener("input",filter);
 // Echap vide la recherche ; sans stopPropagation la modale Bootstrap du site se fermait aussi.
 // Recherche deja vide : Echap ferme la modale, comme avant.
 // BootstrapDialog ferme sur keyup : le keyup de la meme touche est bloque aussi.
 var escUsed=false;
 q.addEventListener("keydown",function(e){ if(e.key==="Escape" && q.value!==""){ e.preventDefault(); e.stopPropagation(); escUsed=true; clear(); } });
 q.addEventListener("keyup",function(e){ if(e.key==="Escape" && escUsed){ e.preventDefault(); e.stopPropagation(); escUsed=false; } });
 clr.addEventListener("click",clear);
 root.querySelector(".ngxp-empty-clear").addEventListener("click",function(e){e.preventDefault();clear();});
 function setActive(key){
   for(var j=0;j<navs.length;j++){ var on=navs[j].getAttribute("data-sec")===key;
     navs[j].classList.toggle("ngxp-nav-on",on);
     if(on){navs[j].setAttribute("aria-current","true");}else{navs[j].removeAttribute("aria-current");} }
 }
 for(var j=0;j<navs.length;j++){
   navs[j].addEventListener("click",function(e){
     e.preventDefault(); var key=this.getAttribute("data-sec");
     var s=document.getElementById("ngxp-sec-"+id+"-"+key); if(!s){return;}
     clickLock=Date.now(); setActive(key); s.scrollIntoView({behavior:reduced?"auto":"smooth",block:"start"});
   });
 }
 // Apres un clic, la section choisie reste active pendant le defilement : la derniere
 // section ne peut pas remonter en haut et l observateur designerait la precedente.
 var clickLock=0;
 window.NgxpObs=window.NgxpObs||{};
 if(window.NgxpObs[id]){ try{window.NgxpObs[id].disconnect();}catch(x){} }
 if("IntersectionObserver" in window){
   var vis={};
   var obs=new IntersectionObserver(function(en){
     for(var i=0;i<en.length;i++){ vis[en[i].target.getAttribute("data-sec")]=en[i].isIntersecting?en[i].boundingClientRect.top:null; }
     var best=null,bt=null;
     for(var k in vis){ if(vis[k]!==null && (bt===null || vis[k]<bt)){best=k;bt=vis[k];} }
     if(best && Date.now()-clickLock>1500){setActive(best);}
   },{threshold:[0,0.25]});
   for(var k=0;k<secs.length;k++){obs.observe(secs[k]);}
   window.NgxpObs[id]=obs;
 }
 if(!window.NgxpSlash){
   window.NgxpSlash=true;
   document.addEventListener("keydown",function(e){
     if(e.key!=="/"){return;}
     var a=document.activeElement, tag=a?a.tagName:"";
     if(tag==="INPUT"||tag==="TEXTAREA"||tag==="SELECT"||(a&&a.isContentEditable)){return;}
     var box=document.querySelector(".ngxp .ngxp-q"); if(!box){return;}
     e.preventDefault(); box.focus();
   });
 }
 if(window.NgxpQuery && window.NgxpQuery[id]){ q.value=window.NgxpQuery[id]; filter(); }
 function donuts(){ $(root).find(".ngxp-donut").peity("donut",{width:26,height:26,innerRadius:8,fill:["__ACC__","#e2e8f0"]}); }
 if(window.jQuery){ if($.fn.peity){donuts();}else{$.getScript("js/jquery.peity.min.js",donuts);} }
})();
</script>';
    $acc="#10b981";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
    }
    return str_replace(array("__ID__","__ACC__"),array($ID,$acc),$js);
}

/** Confirmation avant d'activer la maintenance : le site cesse de repondre. */
function ngxp_maintenance_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["ngxp-maintenance-js"]);
    return $tpl->js_dialog_confirm_action("{ngxp_maintenance_confirm}","ngxp-maintenance-on",$ID,
        "LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');");
}
function ngxp_maintenance_on():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(!$tpl->IsSecurity("AsSystemWebMaster")){
        echo $tpl->post_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $ID=intval($_POST["ngxp-maintenance-on"]);
    if($ID<1){
        echo $tpl->post_error("{error} ID=0");
        return false;
    }
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$ID/update-service", [
        "MaintenanceSite" => 1
    ]),true);
    if(!is_array($result) || empty($result["Status"])){
        $err=(is_array($result) && isset($result["Error"]))?$result["Error"]:"daemon unavailable";
        echo $tpl->post_error($err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    $Sitename=get_servicename($ID);
    return admin_tracks("Set maintenance reverse-proxy website of $Sitename to enabled");
}

/** Tuile « Site Web interdit d'acces » : OFF tout de suite, ON apres confirmation (coupe le site). */
function ngxp_deny_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["ngxp-deny-js"]);
    $refresh="LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');";
    $sockngix=new socksngix($ID);
    if(intval($sockngix->GET_INFO("DenyAccess"))==0){
        return $tpl->js_dialog_confirm_action("{ngxp_deny_confirm}","ngxp-deny-on",$ID,$refresh);
    }
    header("content-type: application/x-javascript");
    $err=ngxp_deny_set($ID,0);
    if($err!==""){
        $tpl->js_error($err);
        return false;
    }
    echo $refresh."\n";
    return true;
}
function ngxp_deny_on():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $err=ngxp_deny_set(intval($_POST["ngxp-deny-on"]),1);
    if($err!==""){
        echo $tpl->post_error($err);
        return false;
    }
    return true;
}
/** Ecrit DenyAccess et recharge le site ; rend "" ou le message d'erreur. */
function ngxp_deny_set($ID,$value):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(!$tpl->IsSecurity("AsSystemWebMaster")){return "{ERROR_NO_PRIVS2}";}
    $ID=intval($ID);
    if($ID<1){return "{error} ID=0";}
    $sockngix=new socksngix($ID);
    if(intval($sockngix->GetType())==14){return "{error} type=14";}
    if(!$sockngix->SET_INFO("DenyAccess",$value)){return "{error} DenyAccess";}
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    $Sitename=get_servicename($ID);
    admin_tracks("Set deny access of reverse-proxy website $Sitename to ".($value==1?"enabled":"disabled"));
    return "";
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
    // Bascule directe de DenyAccess (la fenetre « general » melangeait 5 options).
    $tpl->table_form_field_js("Loadjs('$page?ngxp-deny-js=$ID')","AsSystemWebMaster");
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
function www_dangerous_paths($ID,$tpl){
    $sockngix                   = new socksngix($ID);
    $tpl->table_form_field_js("Loadjs('fw.nginx.dangerous-paths.php?js=yes&serviceid=$ID')");

    $EnableDangerousPaths=intval($sockngix->GET_INFO("EnableDangerousPaths"));
    VERBOSE("EnableDangerousPaths($ID)=$EnableDangerousPaths",__LINE__);

    if($EnableDangerousPaths==0){
        $tpl->table_form_field_bool("{dangerous_paths}",0,ico_shield_disabled);
        return $tpl;
    }

    $tpl->table_form_field_text("{dangerous_paths}","{active2}",ico_shield);
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
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/type/14");
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return false;}
    return count($data["rows"])>0;
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
    // The toggle is offered from three places; each one refreshes its own view.
    $refresh=preg_replace('/[^a-z0-9]/','',(string)($_GET["refresh"] ?? ""));
    if($refresh=="params"){
        echo "LoadAjax('www-parameters-$ID','$page?www-parameters2=$ID');\n";
    }elseif($refresh=="sites2"){
        echo "Loadjs('fw.nginx.sites2.php?row=$ID');\n";
    }else{
        echo "Loadjs('$page?td-row=$ID');\n";
    }
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
// ════════════════════════════════════════════════════════════════
// Backend error dialog (?backend-error-popup=ID) — Stitch port 2026-11-17
// One model (nbe_model) read once, one function per widget.
// Data: /site/ID config (socksngix::GetCache): backend_err, backend_analyzed_time,
// backend_err_detail, backends, type, LatencyScore. backend_err_detail is base64 of
// "<Go error><br><report>" written by CheckReverseTests(), OR a PHP-serialized
// domain => result array written by the public-name check (Frontends.go).
// ════════════════════════════════════════════════════════════════
function backend_error_popup():bool{
    if(!fw_progress_page_priv("AsWebMaster|AsSystemWebMaster")){return false;}   // task link signed for this session: built only for whom may run it
    $ID=intval($_GET["backend-error-popup"]);
    $m=nbe_model($ID);
    $inner=nbe_render($m);
    if(isset($_GET["inner"])){echo $inner;return true;}
    echo nbe_style()."<div id='nbe-$ID'>$inner</div>";
    return true;
}

// Escapes operator/daemon text: HTML + braces (the engine would read {x} as a token).
function nbe_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

// host:port key shared by the configured list and the report lines.
function nbe_key(string $host,string $port):string{
    return strtolower(trim($host,"[] ")).":".trim($port);
}
function nbe_url_key(string $url):string{
    $url=trim(preg_replace('#\s*\(unix:.*$#',"",$url));
    $p=@parse_url($url);
    if(is_array($p) && isset($p["host"])){
        $port=isset($p["port"])?strval($p["port"]):"";
        if($port===""){
            $sch=strtolower($p["scheme"] ?? "");
            $port=$sch==="https"?"443":($sch==="dns"?"53":"80");
        }
        return nbe_key($p["host"],$port);
    }
    return strtolower($url);
}

function nbe_model(int $ID):array{
    $sock=new socksngix($ID);
    $c=$sock->GetCache();
    $m=array("id"=>$ID,"err"=>intval($c["backend_err"] ?? 0),"time"=>intval($c["backend_analyzed_time"] ?? 0),
        "type"=>intval($c["type"] ?? 0),"latency"=>floatval($c["LatencyScore"] ?? 0),
        "watchdog"=>intval($sock->GET_INFO("ActiveHealthCheckEnabled"))==0,
        "format"=>"none","goerr"=>"","raw"=>"","failed"=>"","failed_label"=>"","port"=>"",
        "ok"=>array(),"headers"=>array(),"servers"=>array(),"dns"=>array(),"kind"=>"other");
    $raw=(string)($c["backend_err_detail"] ?? "");
    nbe_parse_detail($raw,$m);
    $m["servers"]=nbe_servers($c,$m);
    // The TCP probe names no URL: borrow the configured label of the failing server.
    if($m["failed_label"]==="" && $m["failed"]!==""){
        foreach($m["servers"] as $s){if($s["key"]===$m["failed"]){$m["failed_label"]=$s["label"];break;}}
    }
    if($m["format"]!=="dns"){$m["kind"]=nbe_kind($m);}
    if($m["port"]==="" && $m["failed"]!==""){
        $pos=strrpos($m["failed"],":");
        if($pos!==false){$m["port"]=substr($m["failed"],$pos+1);}
    }
    return $m;
}

function nbe_parse_detail(string $raw,array &$m):void{
    if($raw===""){return;}
    if(preg_match('#^a:\d+:\{#',$raw)){
        $arr=@unserialize($raw);
        if(is_array($arr)){
            $m["format"]="dns";
            foreach($arr as $dom=>$res){$m["dns"][]=array((string)$dom,(string)$res);}
            return;
        }
    }
    $dec=base64_decode($raw,true);
    if($dec===false){$dec=$raw;}
    $m["format"]="report";
    $pos=strpos($dec,"<br>");
    $goerr=$pos===false?$dec:substr($dec,0,$pos);
    $report=$pos===false?"":substr($dec,$pos+4);
    $m["goerr"]=trim(html_entity_decode(strip_tags($goerr),ENT_QUOTES,"UTF-8"));
    $cur="";
    foreach(explode("\n",str_replace("\r","",$report)) as $line){
        if(preg_match('#SUCCESS: Backend (.+?) OK#',$line,$r)){
            $cur=nbe_url_key($r[1]);
            $m["ok"][$cur]=trim(strip_tags($r[1]));
            $m["headers"][$cur]=array();
            continue;
        }
        if(preg_match('#<li><strong>(.*?):</strong>\s*(.*?)</li>#',$line,$r)){
            if($cur!==""){
                $m["headers"][$cur][]=array(html_entity_decode(strip_tags($r[1]),ENT_QUOTES,"UTF-8"),html_entity_decode(strip_tags($r[2]),ENT_QUOTES,"UTF-8"));
            }
            continue;
        }
        if(preg_match('#Connect to Backend (.+?) Failed#',$line,$r)){
            $m["failed_label"]=trim(strip_tags($r[1]));
            $m["failed"]=nbe_url_key($r[1]);
            continue;
        }
        if(preg_match('#Backend (.+?): relay not registered#',$line,$r)){
            $m["failed_label"]=trim(strip_tags($r[1]));
            $m["failed"]=strtolower($m["failed_label"]);
        }
    }
    // The TCP probe fails before writing a line: the address is in the Go error.
    if($m["failed"]==="" && preg_match('#dial tcp (\[[^\]]+\]|[^\s:]+):(\d+)#',$m["goerr"],$r)){
        $m["failed"]=nbe_key($r[1],$r[2]);
        $m["port"]=$r[2];
    }
    $txt=preg_replace('#<br\s*/?>|</li>|</div>#i',"\n",$report);
    $txt=html_entity_decode(strip_tags($txt),ENT_QUOTES,"UTF-8");
    $txt=preg_replace("#\n{2,}#","\n",str_replace("{time}","Time",$txt));
    $m["raw"]=trim($m["goerr"]."\n".trim($txt));
}

// Plain-language kind of the failure, read from the Go error text.
function nbe_kind(array $m):string{
    $e=strtolower($m["goerr"]);
    if($m["format"]==="none"){return "none_yet";}
    if(strpos($e,"no backend configured")!==false){return "nobackend";}
    if(strpos($e,"unable to determine servertype")!==false){return "type";}
    if(strpos($e,"relay not registered")!==false){return "relay";}
    if(strpos($m["failed_label"],"dns://")===0 || $m["type"]==15){return "dnsbackend";}
    if(strpos($e,"connection refused")!==false){return "refused";}
    if(strpos($e,"no such host")!==false || strpos($e,"lookup ")!==false){return "dnsname";}
    if(strpos($e,"x509")!==false || strpos($e,"tls:")!==false || strpos($e,"certificate")!==false){return "tls";}
    if(strpos($e,"no route to host")!==false || strpos($e,"network is unreachable")!==false || strpos($e,"host is down")!==false){return "unreachable";}
    if(strpos($e,"client.timeout")!==false || strpos($e,"awaiting headers")!==false || strpos($e,"context deadline")!==false){return "httptimeout";}
    if(strpos($e,"i/o timeout")!==false || strpos($e,"timeout")!==false){return "timeout";}
    if(strpos($e,"connection reset")!==false || strpos($e,"eof")!==false){return "reset";}
    return "other";
}

// Configured servers (cache order) + servers only named by the report (HAProxy sidecar, Docker relay).
function nbe_servers(array $c,array $m):array{
    $out=array(); $seen=array();
    $type=intval($c["type"] ?? 0);
    $list=(isset($c["backends"]) && is_array($c["backends"]))?$c["backends"]:array();
    foreach($list as $b){
        $host=trim((string)($b["hostname"] ?? ""));
        if($host===""){continue;}
        $port=trim((string)($b["port"] ?? ""));
        $proto=intval($b["ssl"] ?? 0)==1 || $port==="443"?"https":"http";
        if(preg_match('#^(https?)://#i',$host,$r)){
            $p=@parse_url($host); $proto=strtolower($r[1]);
            if(is_array($p) && isset($p["host"])){$host=$p["host"];}
        }
        if($type==15){$proto="dns";}
        $key=nbe_key($host,$port);
        if(isset($seen[$key])){continue;}
        $seen[$key]=true;
        $out[]=array("key"=>$key,"label"=>"$proto://$host".($port!==""?":$port":""),"proto"=>$proto,"host"=>$host);
    }
    foreach($m["ok"] as $key=>$label){
        if(isset($seen[$key])){continue;}
        $seen[$key]=true; $out[]=array("key"=>$key,"label"=>$label,"proto"=>"","host"=>"");
    }
    if($m["failed"]!=="" && !isset($seen[$m["failed"]])){
        $out[]=array("key"=>$m["failed"],"label"=>$m["failed_label"]!==""?$m["failed_label"]:$m["failed"],"proto"=>"","host"=>"");
    }
    foreach($out as $i=>$s){
        if(isset($m["ok"][$s["key"]])){$st="ok";}
        elseif($m["failed"]!=="" && $s["key"]===$m["failed"]){$st="fail";}
        elseif($s["proto"]==="http" && $s["host"]==="127.0.0.1"){$st="local";}
        else{$st="nt";}
        $out[$i]["state"]=$st;
    }
    return $out;
}

// "12 min ago": the number is injected after translation.
function nbe_ago(int $t):string{
    if($t<=0){return "—";}
    $tpl=new template_admin();
    $d=max(0,time()-$t);
    if($d<60){return $tpl->_ENGINE_parse_body("{nbe_ago_now}");}
    if($d<3600){return str_replace("%s",strval(intval($d/60)),$tpl->_ENGINE_parse_body("{nbe_ago_min}"));}
    if($d<172800){return str_replace("%s",strval(intval($d/3600)),$tpl->_ENGINE_parse_body("{nbe_ago_hour}"));}
    return str_replace("%s",strval(intval($d/86400)),$tpl->_ENGINE_parse_body("{nbe_ago_day}"));
}

// Translated token with "%s" replaced AFTER the parse.
function nbe_tr(string $token,string $val=""):string{
    $tpl=new template_admin();
    return str_replace("%s",$val,$tpl->_ENGINE_parse_body("{".$token."}"));
}

function nbe_render(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$m["id"];
    $restart=$tpl->framework_buildjs("nginx:/reverse-proxy/progress/checkbackend/$ID/0",
        "nginx.CheckReverseTests.$ID.progress",
        "nginx.CheckReverseTests.$ID.log","nbe-prog-$ID",
        "dialogInstance2.close();Loadjs('$page?td-row=$ID');",
        "LoadAjaxSilent('nbe-$ID','$page?backend-error-popup=$ID&inner=yes');Loadjs('$page?td-row=$ID');");
    $h=array();
    $h[]="<div class='nbe'>";
    $h[]=nbe_banner($m);
    if($m["err"]==1){
        if($m["format"]==="dns"){
            // Details overwritten by the public-name check: no per-server result to show.
            $h[]=nbe_dns_card($m);
        }else{
            $h[]=nbe_failed_card($m);
            $h[]=nbe_servers_card($m);
            $h[]=nbe_steps_card($m);
        }
    }
    $h[]="<div id='nbe-prog-$ID' class='nbe-prog'></div>";
    $h[]=nbe_footer($m);
    $h[]="</div>";
    return $tpl->_ENGINE_parse_body(implode("\n",$h)).nbe_js($ID,$restart);
}

function nbe_banner(array $m):string{
    $multi=count($m["servers"])>1;
    if($m["err"]==1){
        $tone="nbe-b-err"; $ico="fas fa-plug-circle-xmark";
        $title=$multi?"{nbe_title_err_multi}":"{nbe_title_err}";
        $text=$multi?"{nbe_text_err_multi}":"{nbe_text_err}";
    }elseif($m["time"]>0){
        $tone="nbe-b-ok"; $ico="fas fa-circle-check"; $title="{nbe_title_ok}"; $text="{nbe_text_ok}";
    }else{
        $tone="nbe-b-off"; $ico="fas fa-circle-question"; $title="{nbe_title_none}"; $text="{nbe_text_none}";
    }
    $chips=array();
    if($m["err"]==1 && $m["watchdog"]){
        $chips[]="<span class='nbe-chip'><i class='fas fa-rotate' aria-hidden='true'></i><span>{nbe_chip_auto}</span></span>";
        $chips[]="<span class='nbe-chip'><i class='fas fa-wand-magic-sparkles' aria-hidden='true'></i><span>{nbe_chip_clear}</span></span>";
    }
    $chips[]="<span class='nbe-chip'><i class='fas fa-shield-halved' aria-hidden='true'></i><span>{nbe_chip_safe}</span></span>";
    return "<section class='nbe-banner $tone' role='status'>"
        ."<span class='nbe-bico'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div class='nbe-bmain'><h3 class='nbe-btitle'>$title</h3><p class='nbe-btext'>$text</p>"
        ."<div class='nbe-chips'>".implode("",$chips)."</div></div></section>";
}

function nbe_problem_text(array $m):string{
    $port=$m["port"]!==""?$m["port"]:"?";
    switch($m["kind"]){
        case "refused": return nbe_tr("nbe_pb_refused",nbe_h($port));
        case "timeout": return nbe_tr("nbe_pb_timeout");
        case "httptimeout": return nbe_tr("nbe_pb_httptimeout");
        case "dnsname": return nbe_tr("nbe_pb_dnsname");
        case "tls": return nbe_tr("nbe_pb_tls");
        case "unreachable": return nbe_tr("nbe_pb_unreachable");
        case "reset": return nbe_tr("nbe_pb_reset");
        case "dnsbackend": return nbe_tr("nbe_pb_dnsbackend");
        case "relay": return nbe_tr("nbe_pb_relay");
        case "nobackend": return nbe_tr("nbe_pb_nobackend");
        case "type": return nbe_tr("nbe_pb_type");
    }
    return nbe_tr("nbe_pb_other");
}

function nbe_failed_card(array $m):string{
    $ID=$m["id"];
    $label=$m["failed_label"]!==""?$m["failed_label"]:$m["failed"];
    if($label!==""){
        $srv="<span class='nbe-addr' id='nbe-addr-$ID'>".nbe_h($label)."</span>"
            ."<button type='button' class='nbe-icobtn' aria-label=\"{nbe_copy}\" title=\"{nbe_copy}\" data-ok=\"{nbe_copied}\" OnClick=\"NbeCopy('nbe-addr-$ID',this)\"><i class='far fa-copy' aria-hidden='true'></i></button>";
    }else{
        $srv="<span class='nbe-muted'>—</span>";
    }
    $tech="";
    if($m["raw"]!==""){
        $tech="<details class='nbe-tech'><summary><i class='fas fa-chevron-right nbe-chev' aria-hidden='true'></i>{nbe_tech}</summary>"
            ."<div class='nbe-raw'>".nl2br(nbe_h($m["raw"]))."</div></details>";
    }
    $when=nbe_ago($m["time"]);
    $exact=$m["time"]>0?nbe_h(date("Y-m-d H:i:s",$m["time"])):"";
    $h=array();
    $h[]="<section class='nbe-card'>";
    $h[]="<div class='nbe-ch'><h4 class='nbe-ct'><i class='fas fa-circle-info' aria-hidden='true'></i>{nbe_what_failed}</h4></div>";
    $h[]="<dl class='nbe-dl'>";
    $h[]="<dt>{nbe_internal_server}<span class='nbe-sub'>{nbe_backend_sub}</span></dt><dd>$srv</dd>";
    $h[]="<dt>{nbe_problem}</dt><dd><p class='nbe-pb'>".nbe_problem_text($m)."</p>$tech</dd>";
    $h[]="<dt>{nbe_detected}</dt><dd><span class='nbe-when' title='$exact'><i class='far fa-clock' aria-hidden='true'></i>".nbe_h($when)."</span>"
        ."<span class='nbe-note'>{nbe_detected_note}</span></dd>";
    $h[]="</dl></section>";
    return implode("\n",$h);
}

function nbe_dns_card(array $m):string{
    $rows=array();
    foreach($m["dns"] as $r){
        $bad=stripos($r[1],"ERROR")===0;
        $res=$bad?"<span class='nbe-st nbe-st-fail'><i class='fas fa-circle-xmark' aria-hidden='true'></i>{nbe_dns_bad}</span><span class='nbe-sub'>".nbe_h(trim(preg_replace('#^ERROR:\s*#i',"",$r[1])))."</span>"
            :"<span class='nbe-val'>".nbe_h($r[1])."</span>";
        $rows[]="<tr><td class='nbe-mono'>".nbe_h($r[0])."</td><td>$res</td></tr>";
    }
    if(count($rows)==0){$rows[]="<tr><td colspan='2' class='nbe-muted'>—</td></tr>";}
    return "<section class='nbe-card'><div class='nbe-ch'><h4 class='nbe-ct'><i class='fas fa-globe' aria-hidden='true'></i>{nbe_dns_title}</h4></div>"
        ."<p class='nbe-note nbe-note-b'>{nbe_dns_note}</p>"
        ."<table class='nbe-tbl'><thead><tr><th>{nbe_domain}</th><th>{nbe_result}</th></tr></thead><tbody>".implode("",$rows)."</tbody></table></section>";
}

function nbe_server_row(array $s,int $i,array $m):string{
    $n=sprintf("%02d",$i);
    $tag=""; $side="";
    switch($s["state"]){
        case "ok":
            $side="<span class='nbe-st nbe-st-ok'><i class='fas fa-circle-check' aria-hidden='true'></i>{nbe_st_ok}</span>";
            $hd=$m["headers"][$s["key"]] ?? array();
            if(count($hd)>0){
                $li=array();
                foreach($hd as $kv){$li[]="<div class='nbe-hd'><span class='nbe-hk'>".nbe_h($kv[0])."</span><span class='nbe-mono'>".nbe_h($kv[1])."</span></div>";}
                $side.="<details class='nbe-ans'><summary>{nbe_answer}<i class='fas fa-chevron-down nbe-chev' aria-hidden='true'></i></summary><div class='nbe-hds'>".implode("",$li)."</div></details>";
            }
            break;
        case "fail":
            $tag="<span class='nbe-tag'>{nbe_failing}</span>";
            $side="<span class='nbe-st nbe-st-fail'><i class='fas fa-circle-xmark' aria-hidden='true'></i>{nbe_st_fail}</span>";
            break;
        case "local":
            $side="<span class='nbe-st nbe-st-nt'><i class='fas fa-circle-minus' aria-hidden='true'></i>{nbe_st_local}</span><span class='nbe-why'>{nbe_local_help}</span>";
            break;
        default:
            $side="<span class='nbe-st nbe-st-nt'><i class='fas fa-circle-question' aria-hidden='true'></i>{nbe_st_nt}</span>";
            if($m["failed"]!==""){$side.="<span class='nbe-why'>{nbe_nt_help}</span>";}
    }
    $cls=$s["state"]==="fail"?" nbe-row-fail":"";
    return "<li class='nbe-row$cls'><span class='nbe-n'>$n</span><span class='nbe-srv'><span class='nbe-mono nbe-strong'>".nbe_h($s["label"])."</span>$tag</span><span class='nbe-side'>$side</span></li>";
}

function nbe_servers_card(array $m):string{
    $n=count($m["servers"]);
    $lat="";
    if($m["latency"]>0){
        $lat="<span class='nbe-lat'><i class='fas fa-gauge' aria-hidden='true'></i>{nbe_latency}: <strong>".nbe_h(MillisToText($m["latency"]))."</strong></span>";
    }
    $rows=array(); $i=0;
    foreach($m["servers"] as $s){$i++; $rows[]=nbe_server_row($s,$i,$m);}
    if($n==0){
        $body="<div class='nbe-empty'><i class='fas fa-server' aria-hidden='true'></i><span>{nbe_pb_nobackend}</span></div>";
    }else{
        $body="<ul class='nbe-list'>".implode("",$rows)."</ul>";
    }
    return "<section class='nbe-card'><div class='nbe-ch'><h4 class='nbe-ct'><i class='fas fa-server' aria-hidden='true'></i>{nbe_servers} <span class='nbe-count'>"
        .nbe_tr("nbe_servers_total",strval($n))."</span></h4>$lat</div>$body</section>";
}

function nbe_steps(array $m):array{
    $port=$m["port"];
    $fw=$port!==""?nbe_tr("nbe_s_fw",nbe_h($port)):nbe_tr("nbe_s_fw_noport");
    $power=nbe_tr("nbe_s_power");
    $go=nbe_tr("nbe_s_analyze");
    switch($m["kind"]){
        case "nobackend": return array(nbe_tr("nbe_s_add"),$go);
        case "type": return array(nbe_tr("nbe_s_type"),$go);
        case "relay": return array(nbe_tr("nbe_s_relay"),$go);
        case "dnsname": return array(nbe_tr("nbe_s_dns"),$power,$go);
        case "tls": return array(nbe_tr("nbe_s_tls"),$go);
        case "dnsbackend": return array(nbe_tr("nbe_s_dnssrv"),$fw,$go);
        case "httptimeout": return array(nbe_tr("nbe_s_slow"),$fw,$go);
    }
    return array($power,$fw,$go);
}

function nbe_steps_card(array $m):string{
    if($m["format"]==="dns"){return "";}
    $li=array(); $i=0;
    foreach(nbe_steps($m) as $s){$i++; $li[]="<li><span class='nbe-num'>$i</span><span>$s</span></li>";}
    return "<section class='nbe-card nbe-card-soft'><div class='nbe-ch'><h4 class='nbe-ct'><i class='fas fa-screwdriver-wrench' aria-hidden='true'></i>{nbe_todo}</h4></div>"
        ."<ol class='nbe-steps'>".implode("",$li)."</ol></section>";
}

function nbe_footer(array $m):string{
    $ID=$m["id"];
    return "<div class='nbe-foot'><span class='nbe-fhelp'><i class='far fa-clock' aria-hidden='true'></i>{nbe_foot_help}</span>"
        ."<span class='nbe-fbtns'><button type='button' class='nbe-btn nbe-btn-ghost' OnClick=\"dialogInstance2.close();\">{nbe_close}</button>"
        ."<button type='button' class='nbe-btn nbe-btn-primary' id='nbe-go-$ID' data-busy=\"{nbe_testing}\" OnClick=\"NbeAnalyze$ID()\"><i class='fas fa-rotate' aria-hidden='true'></i><span class='nbe-go-t'>{nbe_analyze}</span></button></span></div>";
}

function nbe_js(int $ID,string $restart):string{
    return "<script>
function NbeAnalyze$ID(){
    var b=document.getElementById('nbe-go-$ID');
    if(b){ if(b.disabled){return;} b.disabled=true; b.setAttribute('aria-busy','true');
        var t=b.querySelector('.nbe-go-t'); if(t){t.textContent=b.getAttribute('data-busy');} }
    $restart
}
if(typeof NbeCopy!=='function'){
window.NbeCopy=function(id,btn){
    var el=document.getElementById(id); if(!el){return;}
    var txt=(el.textContent||'').trim();
    var done=function(){ if(btn){ btn.setAttribute('title',btn.getAttribute('data-ok')); btn.classList.add('nbe-done'); setTimeout(function(){btn.classList.remove('nbe-done');},1500);} };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,function(){}); return; }
    var ta=document.createElement('textarea'); ta.value=txt; ta.setAttribute('readonly',''); ta.style.position='absolute'; ta.style.left='-9999px';
    document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); done(); }catch(e){} document.body.removeChild(ta);
};
}
</script>";
}

function nbe_style():string{
    $soft="rgba(16,185,129,.12)"; $accent="#006a62"; $fill="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$accent=$a["accent_dark"];}
    }
    if(function_exists("abt_fill")){$f=abt_fill($accent); if(is_string($f) && $f!==""){$fill=$f;}}
    return "<style>
.nbe{--nbe-a:$accent;--nbe-as:$soft;--nbe-fill:$fill;color:#111d23;font-size:14px;display:flex;flex-direction:column;gap:16px;padding:4px 2px}
.nbe *{box-sizing:border-box}
.nbe h3,.nbe h4,.nbe p{margin:0}
.nbe-banner{display:flex;gap:16px;align-items:flex-start;padding:18px 20px;border-radius:12px;border:1px solid #e2e8f0;border-left-width:4px}
.nbe-b-err{background:rgba(186,26,26,.05);border-color:#f3c7c2;border-left-color:#ba1a1a}
.nbe-b-ok{background:var(--nbe-as);border-color:#cfe6df;border-left-color:var(--nbe-a)}
.nbe-b-off{background:#f8fafc;border-left-color:#94a3b8}
.nbe-bico{flex:0 0 40px;width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.nbe-b-err .nbe-bico{background:#ffdad6;color:#ba1a1a}.nbe-b-ok .nbe-bico{background:#fff;color:var(--nbe-a)}.nbe-b-off .nbe-bico{background:#e3f0f8;color:#3d4947}
.nbe-bmain{min-width:0;flex:1}
.nbe-btitle{font-size:18px;font-weight:600;line-height:24px;color:#111d23}
.nbe-btext{margin-top:6px!important;font-size:14px;line-height:21px;color:#3d4947}
.nbe-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.nbe-chip{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;font-size:13px;color:#111d23}
.nbe-b-err .nbe-chip{border-color:#f3c7c2}
.nbe-chip svg,.nbe-chip i{color:var(--nbe-a);font-size:12px}
.nbe-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px}
.nbe-card-soft{background:#f8fafc}
.nbe-ch{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid #f1f5f9}
.nbe-ct{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#3d4947}
.nbe-ct svg{color:#64748b}
.nbe-count{font-weight:600;letter-spacing:0;text-transform:none;color:#6d7a77}
.nbe-dl{display:grid;grid-template-columns:200px 1fr;gap:14px 20px;margin:0}
.nbe-dl dt{font-size:14px;font-weight:500;color:#111d23}
.nbe-dl dd{margin:0;min-width:0}
.nbe-sub{display:block;font-size:13px;font-weight:400;color:#6d7a77}
.nbe-addr{display:inline-block;font-family:monospace;font-size:14px;padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;word-break:break-all}
.nbe-icobtn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;margin-left:6px;border:0;border-radius:8px;background:transparent;color:#64748b;cursor:pointer;vertical-align:middle}
.nbe-icobtn:hover{background:#e3f0f8;color:#111d23}
.nbe-icobtn.nbe-done{color:var(--nbe-a)}
.nbe-icobtn:focus-visible,.nbe-btn:focus-visible,.nbe summary:focus-visible{outline:2px solid var(--nbe-a);outline-offset:2px}
.nbe-pb{font-size:15px;line-height:22px;color:#111d23}
.nbe details>summary{list-style:none;cursor:pointer}
.nbe details>summary::-webkit-details-marker{display:none}
.nbe-tech{margin-top:8px}
.nbe-tech>summary{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#3d4947}
.nbe-tech>summary:hover{color:var(--nbe-a)}
.nbe-chev{font-size:11px;transition:transform .15s}
.nbe-tech[open] .nbe-chev{transform:rotate(90deg)}
.nbe-ans[open] .nbe-chev{transform:rotate(180deg)}
.nbe-raw{margin-top:8px;padding:10px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;font-family:monospace;font-size:13px;line-height:19px;color:#3d4947;max-height:220px;overflow:auto;word-break:break-word}
.nbe-when{display:inline-flex;align-items:center;gap:6px;font-weight:600;border-bottom:1px dotted #94a3b8;cursor:help}
.nbe-note{display:block;margin-top:4px;font-size:13px;line-height:19px;color:#6d7a77}
.nbe-note-b{margin:0 0 12px}
.nbe-lat{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#3d4947}
.nbe-list{list-style:none;margin:0;padding:0;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.nbe-row{display:flex;align-items:flex-start;gap:14px;padding:12px 16px;border-top:1px solid #f1f5f9}
.nbe-row:first-child{border-top:0}
.nbe-row-fail{background:rgba(186,26,26,.04)}
.nbe-n{flex:0 0 22px;font-family:monospace;font-size:13px;color:#94a3b8;line-height:22px}
.nbe-row-fail .nbe-n{color:#ba1a1a}
.nbe-srv{flex:1;min-width:0;display:flex;align-items:center;flex-wrap:wrap;gap:8px;line-height:22px}
.nbe-mono{font-family:monospace;font-size:13px;word-break:break-all}
.nbe-strong{font-size:14px;color:#111d23}
.nbe-tag{display:inline-block;font-size:13px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;padding:2px 8px;border-radius:4px;background:#ffdad6;color:#ba1a1a}
.nbe-side{flex:0 1 auto;display:flex;flex-direction:column;align-items:flex-end;gap:4px;text-align:right;max-width:55%}
.nbe-st{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:500;white-space:nowrap}
.nbe-st-ok{color:var(--nbe-a)}.nbe-st-fail{color:#ba1a1a}.nbe-st-nt{color:#3d4947}
.nbe-st-nt svg{color:#94a3b8}
.nbe-why{font-size:13px;font-style:italic;color:#6d7a77}
.nbe-ans>summary{display:inline-flex;align-items:center;gap:6px;font-size:13px;padding:3px 10px;border-radius:6px;background:#f1f5f9;color:#3d4947}
.nbe-ans>summary:hover{background:#e3f0f8}
.nbe-hds{margin-top:6px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;text-align:left;max-width:460px}
.nbe-hd{display:flex;gap:8px;font-size:13px;line-height:19px}
.nbe-hk{font-weight:600;white-space:nowrap}
.nbe-empty{display:flex;align-items:center;gap:10px;padding:14px;color:#6d7a77;font-size:14px}
.nbe-steps{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.nbe-steps li{display:flex;gap:12px;align-items:flex-start;font-size:14px;line-height:22px}
.nbe-num{flex:0 0 24px;height:24px;border-radius:50%;border:1px solid #bcc9c6;background:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#3d4947}
.nbe-tbl{width:100%;border-collapse:collapse}
.nbe-tbl th{text-align:left;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#3d4947;padding:8px 10px;border-bottom:1px solid #e2e8f0}
.nbe-tbl td{padding:10px;border-top:1px solid #f1f5f9;vertical-align:top;font-size:14px}
.nbe-muted{color:#6d7a77}
.nbe-prog:empty{display:none}
.nbe-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-top:16px;border-top:1px solid #e2e8f0}
.nbe-fhelp{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#6d7a77}
.nbe-fbtns{display:flex;gap:12px}
.nbe-btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 20px;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;border:1px solid transparent}
.nbe-btn-ghost{background:#fff;border-color:#e2e8f0;color:#111d23}
.nbe-btn-ghost:hover{background:#f8fafc}
.nbe-btn-primary{background:var(--nbe-fill);color:#fff}
.nbe-btn-primary:hover{filter:brightness(.92)}
.nbe-btn[disabled]{opacity:.7;cursor:wait}
.nbe-btn[aria-busy=true] svg{animation:nbespin 1s linear infinite}
@keyframes nbespin{to{transform:rotate(360deg)}}
@media (prefers-reduced-motion:reduce){.nbe-chev{transition:none}.nbe-btn[aria-busy=true] svg{animation:none}}
</style>";
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
        $TenantMessage="<button class=\"btn btn-default\" type=\"button\" title=\"{not_protected}\">
<i class=\"fa-solid fa-binary-slash\"></i></button>";

    }

    if($tenantID>0) {
        $TenantMessage="<button class=\"btn btn-primary\" type=\"button\" title=\"{protected_by_tenant} $tenantID\">
<i class=\"fa-solid fa-binary-circle-check\"></i> <span class=\"badge\">$tenantID</span></button>";
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
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table class='awc-tbl awc-tbl-compact'>";
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
    $html[]="<tbody id='ngxs-rows'>";


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

        // .awc-tbl separe les lignes par une bordure et gere le survol.
        $TRCLASS=null;
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
    $html[]="</table></div>";
    $html[]=table_footer($MAX_WEB_SITES);
    $html[]="</div></div>";
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
    // Le cablage du pager se concatene APRES le parse: la regex #{(.+?)}# du
    // moteur de traduction lirait chaque bloc {...} de son JS comme un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .table_footer_js($function);
    return true;
}
function td_btnAction($ID){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $md="MdNginxRule$ID";
    $ligne=fetch_nginx_service_row($ID);
    $MaintenanceSite=isset($ligne["MaintenanceSite"])?$ligne["MaintenanceSite"]:0;
    $enabled=isset($ligne["enabled"])?$ligne["enabled"]:0;



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

        if (intval($LICJSON->ActiveRules)<3){ // 2 websites without license, same as ReverseLicense.FreeWebsites
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
    $results=fetch_stream_ports_for_service($id);
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
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $currentPage=1;
    if(isset($_SESSION["NginxTableCurpage"])){
        $currentPage=intval($_SESSION["NginxTableCurpage"]);
    }
    $recordsPerPage=intval($_SESSION["NginxTableMaxRecs"]);
    if($recordsPerPage<1){$recordsPerPage=25;}
    $totalPages=ceil($totalRecords/$recordsPerPage);

    // awc_pager_server() -- et non awc_pager_js() -- parce que le fragment ne
    // contient qu'UNE page de lignes: la boucle de table() s'arrete a
    // NginxTableMaxRecs, il n'y a donc rien a masquer cote navigateur.
    // Il rend une fenetre de cinq pages + precedent/suivant, la ou l'ancien
    // <tfoot> alignait un <li> par page -- 100 liens pour 500 sites a 5 par page.
    $f[]="<div class='awc-foot'>";
    $f[]="<span>".number_format($totalRecords)." {websites}</span>";
    $f[]="<span class='awc-pg' id='ngxs-pager'>".awc_pager_server($currentPage,$totalPages)."</span>";
    $f[]="</div>";
    return @implode("\n",$f);
}
/**
 * Cablage des liens du pager. awc_pager_server() ne rend que le balisage
 * (chaque lien porte data-p="<numero>"): lui seul ne peut pas connaitre l'URL a
 * recharger. On rebranche donc ici le meme Loadjs() que l'ancien <tfoot>.
 *
 * A concatener APRES _ENGINE_parse_body(): la regex #{(.+?)}# du moteur lirait
 * chaque bloc {...} de ce JS comme un {token}.
 */
function table_footer_js($function):string{
    $me=CurrentPageName();
    $recordsPerPage=intval($_SESSION["NginxTableMaxRecs"]);
    if($recordsPerPage<1){$recordsPerPage=25;}
    // $function vient de $_GET et part dans un litteral JS: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
    $js ="<script>(function(){";
    $js.="var b=document.getElementById('ngxs-pager'); if(!b){return;}";
    $js.="var a=b.getElementsByTagName('a');";
    $js.="for(var i=0;i<a.length;i++){ a[i].onclick=function(){";
    $js.="var p=parseInt(this.getAttribute('data-p'),10); if(isNaN(p)){return false;}";
    $js.="Loadjs('$me?TableNavigate='+p+'&offset=$recordsPerPage";
    $js.="&max=$recordsPerPage&function=$function'); return false; }; }";
    $js.="})();</script>";
    return $js;
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

    $results=fetch_stream_ports_for_service($serviceid);
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
    $results=fetch_stream_ports_for_service($serviceid);
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
