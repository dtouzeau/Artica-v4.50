<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["ecap-clamav-uninstall-js"])){ecap_clamav_uninstall_ask();exit;}
if(isset($_POST["ecap-clamav-uninstall"])){ecap_clamav_install_confirm();exit;}
if(isset($_GET["section-main-js"])){section_main_js();exit;}
if(isset($_GET["section-template-js"])){section_template_js();exit;}
if(isset($_GET["section-template-popup"])){section_template_popup();exit;}
if(isset($_GET["section-main-popup"])){section_main_popup();exit;}
if(isset($_POST["eCAPClamavMaxSize"])){save();exit;}
if(isset($_POST["template"])){section_template_save();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){events_search();exit;}



page();

function tabs():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array["{parameters}"]="$page?table=yes";
    $array["{av_exclusions}"]='fw.proxy.ecap.whitelist.php';
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{integrated_antivirus}",
        ico_antivirus,"{integrated_antivirus_explain}","$page?tabs=yes","ecapav","progress-squid-ecap-restart",
        false,"table-loader-proxy-ecapav");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{file_descriptors}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function events():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   echo "<div style='margin-top:10px'>".$tpl->search_block($page)."</div>";
    return true;
}
function events_search():bool{
    $tpl=new template_admin();

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $data=$sock->REST_API("/proxy/ecap/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th style='width:1%' nowrap>{virus}</th>
        	<th style='width:1%' nowrap>{date}</th>
        	<th style='width:1%' nowrap>PID</th>
        	<th style='width:99%' nowrap>URL</th>
        	<th style='width:1%' nowrap>{member}</th>
        </tr>
  	</thead>
	<tbody>
";



    foreach ($json->Logs as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.+?)\s+\[(.+?)\]\s+(.+)#", $line,$re)){continue;}
        $FTime=$re[1]." $re[2] $re[3]";
        $PID=$re[4];
        $URL=$re[5];
        $VIRUS=$re[6];
        $IP=$re[7];
        $html[]="<tr>
                <td style='width:1%' nowrap><span class='label label-danger'>$VIRUS</span></td>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap>$PID</td>
				<td style='width:99%'>$URL</td>
				<td style='width:1%' nowrap>$IP</td>
				</tr>";

    }

    $html[]="</tbody></table>";

    $TINY_ARRAY["TITLE"]="{integrated_antivirus}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{ecapav_log_explain}";
    $TINY_ARRAY["URL"]="ecapav";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
return true;

}

function table(){
    $page=CurrentPageName();
    echo "<div id='ecap-clamav-table'></div>
        <script>LoadAjaxSilent('ecap-clamav-table','$page?table1=yes');</script>";
}
function section_main_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{parameters}","$page?section-main-popup=yes");
}
function section_template_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{template}","$page?section-template-popup=yes");
}
function section_template_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $tpl->CLUSTER_CLI=True;

    $template=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavTemplate"));
    if(strlen($template)<50){
        $template=@file_get_contents("/usr/share/artica-postfix/ressources/databases/ecap.template");
    }
    $form[]=$tpl->field_textareacode("template",null,$template);

    $jsafter=section_js_form();
    $html[]=$tpl->form_outside("", $form,null,"{apply}",$jsafter,$security,true)."</td>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_template_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("eCAPClamavTemplate",base64_encode(trim($_POST["template"])));
    admin_tracks("Saving template for eCAP Clamav antivirus");
    return true;
}

function section_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart");



    return "BootstrapDialog1.close();LoadAjaxSilent('ecap-clamav-table','$page?table1=yes');$jsinstall";
}

function table1():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eCAPClamavMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavMaxSize"));
    $eCAPClamavSkipScanning=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavSkipScanning"));
    if($eCAPClamavSkipScanning==null){
        $eCAPClamavSkipScanning="video/.*,image/.*,text/html";
    }
    $eCAPClamavEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavEmergency"));
    if($eCAPClamavEmergency==1){
        echo $tpl->div_error("<H3>{eCAPClamav_emergency_mode}</H3><p>{eCAPClamav_emergency_mode_explain}</p>");

    }

    $template=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavTemplate"));
    if(strlen($template)<50){
        $template=@file_get_contents("/usr/share/artica-postfix/ressources/databases/ecap.template");
    }

    if($eCAPClamavMaxSize==0){$eCAPClamavMaxSize=4;}
    $tpl->table_form_field_js("Loadjs('$page?section-main-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{srv_clamav.MaxScanSize}","$eCAPClamavMaxSize MB",ico_archive);
    $tpl->table_form_field_text("{exclude} {ExcludeMimeType}",$eCAPClamavSkipScanning,ico_exclude_file);

    $tpl->table_form_field_js("Loadjs('$page?section-template-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{template}",strlen($template)." Bytes",ico_html);



    $html[]=$tpl->table_form_compile();

    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart");


    $jsreconfigure=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart");

    $topbuttons[] = array("Loadjs('$page?ecap-clamav-uninstall-js=yes');",ico_trash,"{uninstall}");
    $topbuttons[] = array($jsinstall,ico_refresh,"{reload}");
    $topbuttons[] = array($jsreconfigure,ico_refresh,"{reconfigure}");
    $topbuttons[] = array("Loadjs('fw.proxy.emergency.remove.php');",ico_bug,"{disable_emergency_mode}");

    $TINY_ARRAY["TITLE"]="{integrated_antivirus}";
    $TINY_ARRAY["ICO"]=ico_antivirus;
    $TINY_ARRAY["EXPL"]="{integrated_antivirus_explain}";
    $TINY_ARRAY["URL"]="ecapav";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ecap_clamav_uninstall_ask():bool{
    $tpl=new template_admin();
    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/uninstall",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart",
        "document.location.href='/index';");

    echo $tpl->js_confirm_execute("{disable_feature} {antivirus_proxy} eCAP",
        "ecap-clamav-uninstall","{disable_feature} {antivirus_proxy}",$jsinstall);
    return true;
}
function ecap_clamav_install_confirm():bool{
    admin_tracks("Uninstall Antivirus for Proxy (eCAP) mode");
    return true;
}
function save():bool{
    $tpl=new template_admin();
    if(isset($_POST["eCAPClamavSkipScanning"])){
        $_POST["eCAPClamavSkipScanning"]=str_replace("\n",",",$_POST["eCAPClamavSkipScanning"]);
    }
    $tpl->SAVE_POSTs();
    return true;

}

function section_main_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $security="AsSquidAdministrator";
    $tpl->CLUSTER_CLI=True;

    $eCAPClamavMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavMaxSize"));
    if($eCAPClamavMaxSize==0){$eCAPClamavMaxSize=4;}
    $eCAPClamavSkipScanning=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavSkipScanning"));
    if($eCAPClamavSkipScanning==null){
        $eCAPClamavSkipScanning="video/.*,image/.*,text/html";
    }
    $eCAPClamavSkipScanning=str_replace(",","\n",$eCAPClamavSkipScanning);
    $form[]=$tpl->field_numeric("eCAPClamavMaxSize","{srv_clamav.MaxScanSize} (MB)",$eCAPClamavMaxSize,"");
    $form[]=$tpl->field_textarea("eCAPClamavSkipScanning","{exclude} {ExcludeMimeType}",$eCAPClamavSkipScanning);




    $jsafter=section_js_form();
    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsafter,$security,true)."</td>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}




