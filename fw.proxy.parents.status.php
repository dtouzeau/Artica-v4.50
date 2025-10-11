<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["dynamic-rows"])){dynamic_rows();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["proxies"])){proxies();exit;}
if(isset($_GET["proxies-list"])){proxies_list();exit;}
if(isset($_GET["fiche-proxy-js"])){proxy_fiche_js();exit;}
if(isset($_GET["fiche-proxy"])){proxy_fiche();exit;}
if(isset($_GET["delete-proxy-js"])){proxy_delete_js();exit;}
if(isset($_POST["proxy-aclid"])){proxy_fiche_save();exit;}
if(isset($_POST["delete-proxy"])){proxy_delete();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["parent-lb-status"])){parent_lb_status();exit;}

if(isset($_GET["configuration-file"])){configuration_file_js();exit;}
if(isset($_GET["configuration-popup"])){configuration_file();exit;}
if(isset($_POST["conf"])){configuration_file_save();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_POST["removefeature"])){uninstall_perform();exit;}
if(isset($_GET["DisableInstantParentProxy-js"])){DisableInstantParentProxy_js();exit;}
page();
function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{parent_proxies} &raquo;&raquo; {status}","fa fa-sitemap",
        "{parent_proxies_status_explain}","$page?tabs=yes","proxy-parents-status",
        "progress-squid-parent-restart",null,"table-loader-proxy-parents-status");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall("{parent_proxies} {status}");
		return false;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs():bool{

    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $array["{backends}"]="$page?table=yes";
    $array["{parameters}"]="fw.proxy.parents.parameters.php?flat=yes";
    echo $tpl->tabs_default($array);
    return true;
}


function uninstall_js():bool{
    $tpl=new template_admin();
    $jsUninstall=$tpl->framework_buildjs("/proxy/parents/uninstall",
        "squid.access.center.progress","squid.access.center.progress.txt",
        "parents-remove-feature","document.location.href='/proxy-status';");

    $tpl->js_confirm_delete("{parent_proxies}: {remove_this_section}","removefeature","yes",$jsUninstall);
    return true;
}
function uninstall_perform():bool{
    admin_tracks("Warning! Remove Parent Proxy feature");
    return true;
}
function configuration_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{view_config}","$page?configuration-popup=yes",900);
    return true;

}
function configuration_file():bool{
    $tpl=new template_admin();
    $form[]=$tpl->field_textareacode("conf",null,@file_get_contents("/etc/squid3/acls_peer.conf"));
    $html[] = $tpl->form_outside("{view_config}", @implode("\n", $form), "",
        "{apply}", "blur()", "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function configuration_file_save():bool{
    $tpl        = new template_admin();
    $sock       = new sockets();
    $basesrc    = "acls_peer.conf";
    $errfile    = PROGRESS_DIR."/$basesrc.error";
    $testfile   = UPLOAD_DIR."/$basesrc";

    $tpl->CLEAN_POST();
    @file_put_contents($testfile,$_POST["conf"]);

    $sock->getFrameWork("squid2.php?acls-peer-manual-conf=yes");

    if(is_file($errfile)){
        $data=@file_get_contents($errfile);
        if(preg_match("#^[0-9]+.*?\.conf(.+)#",$data,$re)){
            $data="Line $re[1]";
        }
        admin_tracks("Fatal error $data while modify directly proxy parents configuration file");
        echo "jserror:".$tpl->javascript_parse_text($data);
        return true;
    }
    admin_tracks("Success modify directly proxy parents configuration file");
    return true;
}

function dynamic_rows():bool{
    $tpl=new template_admin();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/peers/status");
    $jsonMain=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
       return false;
    }
    if(!property_exists($jsonMain,"peers")){
        return false;
    }
    $status_Up="<button type='button' class='btn btn-info btn-bitbucket btn-lg'><i class='fas fa-check'></i></button>";
    $status_Down="<button type='button' class='btn btn-danger btn-bitbucket'><i class='fas fa-exclamation-triangle'></i></button>";

    $f=array();
    if (isset($jsonMain->peers) && is_array($jsonMain->peers)) {
        foreach ($jsonMain->peers as $index => $json) {
            $error = "";

            $STATUS = strtolower($json->status);
            $Peer = $json->parent_name;
            $hostname = $json->parent_addr . ":" . $json->parent_port;
            $FETCHES = FormatNumber($json->fetches);
            $OPEN_CONNS = FormatNumber($json->open_conns);

            if ($STATUS == "up") {
                $STATUS_ICON = $status_Up;
            } else {
                $STATUS_ICON = $status_Down;
            }
            if ($hostname == "127.0.0.1:2320") {
                $hostname = "{APP_HAPROXY_SERVICE}";
            }
            if (strlen($json->telneterror)) {
                $STATUS_ICON = $status_Down;
                $error = "<div class='text-danger' id='$index' style='font-size:12px'>$json->telneterror</div>";
            }

            $f[] = "if(document.getElementById('$Peer-status')){";
            $STATUS_ICON = base64_encode($STATUS_ICON);
            $f[] = "document.getElementById('$Peer-status').innerHTML=base64_decode('$STATUS_ICON');";
            $f[] = "}";
            $f[] = "if(document.getElementById('$Peer-name')){";
            $Name = base64_encode($tpl->_ENGINE_parse_body("$hostname ($Peer)$error"));
            $f[] = "document.getElementById('$Peer-name').innerHTML=base64_decode('$Name');";
            $f[] = "}";
            $f[] = "if(document.getElementById('$Peer-fetches')){";
            $f[] = "document.getElementById('$Peer-fetches').innerHTML='$FETCHES';";
            $f[] = "}";
            $f[] = "if(document.getElementById('$Peer-conns')){";
            $f[] = "document.getElementById('$Peer-conns').innerHTML='$OPEN_CONNS';";
            $f[] = "}";
            $f[] = "";
        }
    }
    header("content-type: application/x-javascript");
    echo @implode("\n", $f);
    return true;
}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$parenttext=$tpl->_ENGINE_parse_body("{parents}");
	$STATUS=$tpl->_ENGINE_parse_body("{status}");
	$connexions=$tpl->_ENGINE_parse_body("{connections}");


    $jsrestart=$tpl->framework_buildjs("/proxy/parents/compile",
        "squid.access.center.progress","squid.access.center.progress.log",
        "progress-squid-parent-restart");


    $topbuttons[]=array("LoadAjax('table-loader-proxy-parents-status','$page?table=yes');",ico_refresh,"{refresh}");
    $topbuttons[]=array("Loadjs('$page?configuration-file=yes')",ico_file,"{view_config}");

    $topbuttons[]=array($jsrestart,ico_save,"{apply_parameters}");
    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/parents',1024,768,'Wiki')";
    $topbuttons[]=array($jshelp,ico_support,"Wiki");
    //

    $t=time();
    $btns=$tpl->table_buttons($topbuttons);
    $html[]="<table style='width:100%' id='$t'>";
    $html[]="<td style='vertical-align:top;width:240px'><div id='parent-lb-status'></div></td>";
    $html[]="<td style='vertical-align:top;width:99%;padding-left:10px'>";
	$html[]="<table id='table-firewall-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>$STATUS</th>";
	$html[]="<th data-sortable=false class='text-capitalize'>$parenttext</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%'>Fetches</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%'>$connexions</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/peers/status");
    $jsonMain=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}||".json_last_error_msg());
        return false;
    }


	$status_Up="<button type='button' class='btn btn-info btn-bitbucket btn-lg'><i class='fas fa-check'></i></button>";
	$status_Down="<button type='button' class='btn btn-danger btn-bitbucket'><i class='fas fa-exclamation-triangle'></i></button>";
    $TRCLASS=null;
    if(property_exists($jsonMain,"peers")){

            foreach ($jsonMain->peers as $index => $json) {

                if ($TRCLASS == "footable-odd") {
                    $TRCLASS = null;
                } else {
                    $TRCLASS = "footable-odd";
                }
                $error="";
                $zlineMD = md5(serialize($json));
                $STATUS = strtolower($json->status);
                $Peer = $json->parent_name;
                $hostname = $json->parent_addr . ":" . $json->parent_port;
                $FETCHES = FormatNumber($json->fetches);
                $OPEN_CONNS = FormatNumber($json->open_conns);
                if ($STATUS == "up") {
                    $STATUS_ICON = $status_Up;
                } else {
                    $STATUS_ICON = $status_Down;
                }
                if ($hostname == "127.0.0.1:2320") {
                    $hostname = "{APP_HAPROXY_SERVICE}";
                }
                if(strlen($json->telneterror)){
                    $STATUS_ICON = $status_Down;
                    $error="<div class='text-danger' id='$index' style='font-size:12px'>$json->telneterror</div>";
                }

                $html[] = "<tr class='$TRCLASS' id='row-parent-$zlineMD'>";
                $html[] = "<td class=\"center\"><span id='$Peer-status'>$STATUS_ICON</span></td>";
                $html[] = "<td style='font-size:26px'><span id='$Peer-name'>$hostname ($Peer)$error</span></td>";

                $html[] = "<td class=\"center\" style='font-size:26px'><span id='$Peer-fetches'>$FETCHES</span></td>";
                $html[] = "<td class=\"center\" style='font-size:26px'><span id='$Peer-conns'>$OPEN_CONNS</span></td>";
                $html[] = "</tr>";

            }

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
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="{parent_proxies} &raquo;&raquo; {status}";
    $TINY_ARRAY["ICO"]="fa fa-sitemap";
    $TINY_ARRAY["EXPL"]="{parent_proxies_status_explain}";
    $TINY_ARRAY["URL"]="proxy-parents-status";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $script=$tpl->RefreshInterval_Loadjs($t,$page,"dynamic-rows=yes");
    $script2=$tpl->RefreshInterval_js("parent-lb-status",$page,"parent-lb-status=yes",5);

	$html[]="
	<script>
	NoSpinner();
    $jstiny
    $script2
    $script
    </script>";

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}


function DisableInstantParentProxy_js():bool{
    $DisableInstantParentProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableInstantParentProxy"));
    if($DisableInstantParentProxy==0){
        $DisableInstantParentProxy=1;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableInstantParentProxy",1);
    }else{
        $DisableInstantParentProxy=0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableInstantParentProxy",0);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("Switch Disable link to Proxy parents to $DisableInstantParentProxy");
}
function parent_lb_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $html[]="<div id='parents-remove-feature' style='margin-bottom: 15px'></div>";
    $html[]=$tpl->button_autnonome("{remove_this_section}", "Loadjs('$page?uninstall-js=yes')", ico_trash,"AsSquidAdministrator",335,"btn-danger");

    $html[]="<div style='margin-top:5px'>&nbsp;</div>";
    $DisableInstantParentProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableInstantParentProxy"));
    if($DisableInstantParentProxy==0){
        $html[]=$tpl->button_autnonome("{temporarily_switch_to_direct_mode}", "Loadjs('$page?DisableInstantParentProxy-js=yes')", ico_link,"AsSquidAdministrator",335,"btn-default");
    }else{
        $html[]=$tpl->button_autnonome("{reconnect_to_parent_servers}", "Loadjs('$page?DisableInstantParentProxy-js=yes')", ico_unlink,"AsSquidAdministrator",335,"btn-warning");

    }

     $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parentlb/status"));
    if(!$data->Status){
        $err=$tpl->widget_rouge($data->Error,"{error}");
        echo $tpl->_ENGINE_parse_body($err);
        return false;
    }

    $bsini=new Bs_IniHandler();
    $bsini->loadString($data->Info);

    $jsRestart=$tpl->framework_buildjs("/proxy/parentlb/restart",
        "parentlb.progress","parentlb.log",
        "progress-squid-parent-restart",
        "LoadAjax('parent-lb-status','$page?parent-lb-status=yes');"
    );
    $Enabled=0;
    if(isset($bsini->_params["APP_PARENTLB"]["service_disabled"])) {
        $Enabled = intval($bsini->_params["APP_PARENTLB"]["service_disabled"]);
    }


    $html[]="<div style='margin-top: 15px'>";

    if($Enabled==0){
        $help_url="https://wiki.articatech.com/proxy-service/parents/parents-lb";
        $js_help="s_PopUpFull('$help_url','1024','900');";
        $btn[0]["js"] = $js_help;
        $btn[0]["name"] = "Wiki";
        $btn[0]["icon"] = ico_support;
        $html[]=$tpl->widget_grey("{load_balancer}","{disabled}",$btn);
    }else {
        $html[] = $tpl->SERVICE_STATUS($bsini, "APP_PARENTLB", $jsRestart);
    }
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}