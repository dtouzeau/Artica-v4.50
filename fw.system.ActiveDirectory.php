<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["fullhosname_change"])){hostname_save();exit;}
if(isset($_GET["table"])){features();exit;}
if(isset($_POST["ADNETBIOSDOMAIN"])){Save();exit;}
if(isset($_GET["hostname-js"])){hostname_js();exit();}
if(isset($_GET["hostname-popup"])){hostname_popup();exit();}
if(isset($_GET["netad-status"])){netad_status();exit;}
if(isset($_GET["changetrustpw-js"])){changetrustpw_js();exit();}
if(isset($_GET["kerberos-disconnect-js"])){kerberos_disconnect_js();exit;}
if(isset($_POST["kdestroy"])){kerberos_disconnect_perform();exit;}
if(isset($_GET["ntpad-js"])){ntpad_js();exit;}
if(isset($_GET["netbiosdomain-js"])){netbiosdomain_js();exit;}
if(isset($_GET["netbiosdomain-popup"])){netbiosdomain_popup();exit;}
if(isset($_POST["ADNETBIOSDOMAIN_CHANGE"])){netbiosdomain_save();exit;}
if(isset($_GET["log-level-js"])){log_level_js();exit;}
if(isset($_GET["log-level-popup"])){log_level_popup();exit;}
if(isset($_POST["SMB_LOG_LEVEL"])){save_log_level();exit;}
if(isset($_GET["adbranch-js"])){adbranch_js();exit;}
if(isset($_GET["adbranch-popup"])){adbranch_popup();exit;}
if(isset($_POST["COMPUTER_BRANCH_CHANGE"])){adbranch_save();exit;}




page();

function save_log_level()
{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
}
function log_level_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{parameters}","$page?log-level-popup=yes");
    return true;
}

function log_level_popup()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $smb_log_level=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMB_LOG_LEVEL"));
    $log_leve["0"]="0";
    $log_leve["1"]="1";
    $log_leve["2"]="2";
    $log_leve["3"]="3";
    $form[]=$tpl->field_array_hash($log_leve,"SMB_LOG_LEVEL","{log_level}",$smb_log_level);
    //$jsafter[]="LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes";
    $jsafter[]="dialogInstance2.close()";
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.join.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.join.progress.txt";
    $ARRAY["CMD"]="activedirectory.php?restart-smb=yes";
    $ARRAY["TITLE"]="{reloading}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter[]="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ad-restart')";
    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsSquidAdministrator");
    return true;
}
function netad_status():bool{

    $NETADS_CHECK=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NETADS_CHECK"));

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/klist/dump"));
    $PrincipalName="";


    if($NETADS_CHECK==0){return false;}
    $tpl=new template_admin();
    $NETADS[1]="{retreive_informations}";
    $NETADS[2]="{status}";
    $NETADS[3]="Check Secret";
    $NETADS[4]="{check_account}";
    $NETADS[5]="Ping {domain_controler}";
    $NETADS[6]="Ping {winbindd}";
    $NETADS[7]="{license_error}";

    $time=$tpl->time_to_date($NETADS_CHECK,true);

    if(property_exists($data,"Info")) {
        if (property_exists($data->Info, "DEFAULT_PRINCPAL")) {
            $PrincipalName = $data->Info->DEFAULT_PRINCPAL;
            VERBOSE("PrincipalName=$PrincipalName", __LINE__);
        } else {
            VERBOSE("data->Info->DEFAULT_PRINCPAL NONE", __LINE__);
        }
    }

    if(strlen($PrincipalName)>3){
        $page=CurrentPageName();
        $btn[0]["name"]="{disconnect}";
        $btn[0]["icon"]="fas fa-unlink";
        $btn[0]["js"]="Loadjs('$page?kerberos-disconnect-js=yes');";
        $ppname=$tpl->widget_vert($PrincipalName,"Kerberos",$btn);
        VERBOSE("OK",__LINE__);
    }


    foreach ($NETADS as $index=>$explain){

        $Key="NETADS_CHECK{$index}";
        $Value=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Key));
        if($Value==1){
            echo $tpl->widget_jaune("{APP_CONNECTION} {failed}<br><small style='color:white'>{b_received} $time</small>",$NETADS[$index])."$ppname";
            return false;

        }

    }
    echo $tpl->widget_vert("{APP_CONNECTION}<br><small style='color:white'>{time} $time</small>","OK<br>")."$ppname";

     return true;

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $CONFIG_SAVED=intval($array["CONFIG_SAVED"]);
    if($array["LDAP_SUFFIX"]==null){$CONFIG_SAVED=0;}
    if($array["ADNETBIOSDOMAIN"]==null){$CONFIG_SAVED=0;}
    $addon=null;
    if($CONFIG_SAVED==1){
        $addon="&close=yes";
    }

    $html=$tpl->page_header("Active Directory &raquo;&raquo {join_domain}",
        "fab fa-windows",
    "{activedirectory_explain_section}","$page?table=yes{$addon}",
        "ad-connect",
        "progress-ad-restart",false,"table-loader-ntlm-cnx-status");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Active Directory",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function adbranch_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{parameters}","$page?adbranch-popup=yes");
    return true;
}
function changetrustpw_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ChangetrustpwDisable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChangetrustpwDisable"));
    if($ChangetrustpwDisable==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ChangetrustpwDisable",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ChangetrustpwDisable",1);
    }

    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $CONFIG_SAVED=intval($array["CONFIG_SAVED"]);
    if($array["LDAP_SUFFIX"]==null){$CONFIG_SAVED=0;}
    if($array["ADNETBIOSDOMAIN"]==null){$CONFIG_SAVED=0;}
    $addon=null;
    if($CONFIG_SAVED==1){
        $addon="&close=yes";
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes{$addon}');";
}

function kerberos_disconnect_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{kdestroy}","kdestroy","kdestroy","LoadAjax('netad-status','$page?netad-status=yes');");

}
function kerberos_disconnect_perform(){
    admin_tracks("Destroy Kerberos authorization tickets");
    $GLOBALS["CLASS_SOCKETS"]->go_exec("/usr/bin/kdestroy");
}

function ntpad_js():bool{
    $page=CurrentPageName();
    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    if($NtpdateAD==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NtpdateAD",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NtpdateAD",0);
    }


    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?ntpad-ntlm=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');";
    return true;

}


function features(){

    $ADVENCE_MODE=false;
    if(isset($_GET["advanced"])){$ADVENCE_MODE=true;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $domain=null;
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){
        echo $tpl->FATAL_ERROR_SHOW_128("{please_disconnect_your_server_from_kerberos}");
        return;
    }
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if(isset($_GET["close"])){close_form();exit;}


    $hostname_full=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    if($hostname_full==null) {
        $hostname_full = php_uname("n");
    }
    $hostname=$hostname_full;
    $NetbiosDomain=null;
    if(strpos($hostname, ".")>0){
        $tre=explode(".",$hostname);
        $hostname=$tre[0];
        $NetbiosDomain=$tre[1];
        unset($tre[0]);
        $domain=@implode(".", $tre);

    }

    if(strlen($hostname)>15){
        $hostname_exceed_15=$tpl->_ENGINE_parse_body("{hostname_exceed_15}");
        $hostname_exceed_15=str_replace("%s", "$hostname", $hostname_exceed_15);
        echo $tpl->FATAL_ERROR_SHOW_128("$hostname_exceed_15");
        return;
    }
    $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));

    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    $severtype["WIN_2003"]="Windows 2000/2003";
    $severtype["WIN_2008AES"]="Windows 2008/2012/2016";
    if(isset($_GET["switch-template"])){$_GET["switch-template"]=null;}
    if(!is_file("/etc/artica-postfix/settings/Daemons/KerbAuthSMBV2")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthSMBV2", 1);}
    $WindowsActiveDirectoryKerberos_explain=null;
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    if(!isset($array["ADNETBIOSDOMAIN"])){$array["ADNETBIOSDOMAIN"]=null;}
    if($array["ADNETBIOSDOMAIN"]==null){$array["ADNETBIOSDOMAIN"]=$NetbiosDomain;}

    if(strlen($array["WINDOWS_SERVER_NETBIOSNAME"])<3) {
        $sock = new sockets();
        $json = json_decode($sock->REST_API("/mktutils/kdcs"));
        if(property_exists($json,"Info")) {
            foreach ($json->Info as $key => $value) {
                $fullhosname = $value->Target;
                break;
            }
        }

    }
    if(is_null($fullhosname)){
        $fullhosname="";
    }

    if(strlen($fullhosname)<3) {
        $fullhosname = "{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
        if ($fullhosname == ".") {
            $fullhosname = null;
        }
        if ($fullhosname == null) {
            $fullhosname = getDcByNS($domain);
        }
    }
    if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
    if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2008AES";}
    $kerb[1]="{wizard_kerberos_button_on}";
    $kerb[0]="";


    if(trim($array["LDAP_SUFFIX"])==null){
        $tt=explode(".",$hostname_full);
        unset($tt[0]);
        $suff=array();
        foreach ($tt as $ligne){$ligne=trim($ligne);if($ligne==null){continue;}$suff[]="DC=$ligne";}
        $array["LDAP_SUFFIX"]=@implode(",", $suff);
    }




    $form[]=$tpl->field_hidden("CONFIG_SAVED",1);
    $form[]=$tpl->field_text("fullhosname", "{ad_full_hostname}", $fullhosname,true);
    if($ADVENCE_MODE) {
        $form[] = $tpl->field_text("ADNETBIOSDOMAIN", "{ADNETBIOSDOMAIN}", $array["ADNETBIOSDOMAIN"], true, "{howto_ADNETBIOSDOMAIN}");
    }
    if($ADVENCE_MODE) {
        $form[] = $tpl->field_text("LDAP_SUFFIX", "{suffix}", $array["LDAP_SUFFIX"], true);
    }

    $form[]=$tpl->field_checkbox("NtpdateAD","{synchronize_time_with_ad}",$NtpdateAD,false,"{synchronize_time_with_ad_explain}");
    $form[]=$tpl->field_checkbox("LDAP_SSL", "{enable_ssl} (port 636)","{$array["LDAP_SSL"]}");
    if($ADVENCE_MODE) {
        $form[] = $tpl->field_text("COMPUTER_BRANCH", "{ad_computers_branch}", $array["COMPUTER_BRANCH"], true);
    }
    $form[]=$tpl->field_array_hash($severtype, "WINDOWS_SERVER_TYPE", "{WINDOWS_SERVER_TYPE}", $array["WINDOWS_SERVER_TYPE"]);

    $form[]=$tpl->field_text("WINDOWS_SERVER_ADMIN", "{administrator}", $array["WINDOWS_SERVER_ADMIN"],true);
    $form[]=$tpl->field_password("WINDOWS_SERVER_PASS", "{password}", $array["WINDOWS_SERVER_PASS"],true);

    if(!$ADVENCE_MODE){
        $tpl->field_hidden("COMPUTER_BRANCH",$array["COMPUTER_BRANCH"]);
        $tpl->field_hidden("LDAP_SUFFIX",$array["LDAP_SUFFIX"]);
        $tpl->field_hidden("ADNETBIOSDOMAIN",$array["ADNETBIOSDOMAIN"]);

    }



    if($EnableKerbAuth==1){
        $form[]=$tpl->form_add_button("{close}", "LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');",null,"btn-danger");

        $form[]=$tpl->form_add_button("{analyze}", "Loadjs('fw.system.ActiveDirectory.analyze.php')","AsProxyMonitor","btn-primary");
    }

    $jsrestart="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";


    $TINY_ARRAY["TITLE"]="Active Directory &raquo;&raquo {join_domain}";
    $TINY_ARRAY["ICO"]="fab fa-windows";
    $TINY_ARRAY["EXPL"]="{activedirectory_explain_section}";
    $TINY_ARRAY["URL"]="ad-connect";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<div id='ntlm-check'></div>";
    $html[]=$tpl->form_outside("{join_activedirectory_domain} <strong>{wizard_kerberos_button_off}</strong>",
        @implode("\n", $form),"{wizard_kerberosntlm_explain}","{save}",
        $jsrestart,"AsSystemAdministrator",true)."<script>$jstiny</script>";


    $html[] = "<script>";
    $html[] = "LoadAjax('ntlm-check','fw.system.kerberos-single.php?CheckUPSettings=yes&ntlmcheck=yes');";
    $html[] = "</script>";
    echo $tpl->_ENGINE_parse_body($html);


}
function getDcByNS($domain):string{
    include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");

    $rs = new Net_DNS2_Resolver();
    $rs->timeout = 2;

    try {
        $result = $rs->query($domain, "NS");
    } catch(Net_DNS2_Exception $e) {
        writelogs($e->getMessage(),__FUNCTION__,__FILE__,__LINE__);
        return "";
    }

    if(!property_exists($result, "answer")){
        return "";
    }

    if(count($result->answer)==0){
        return "";
    }

    foreach ($result->answer as $rr){
        if(property_exists($rr,"nsdname")){
            return $rr->nsdname;
        }
    }

    return "";
}


function close_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    $NETADS_CHECK=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NETADS_CHECK"));
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $ChangetrustpwDisable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChangetrustpwDisable"));
    $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    $smb_log_level=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMB_LOG_LEVEL"));

    $text_button="{join_domain} (NTLM)";
    $script=null;

    if($NETADS_CHECK>0){
        $text_button="{activedirectroy_reconnection} (NTLM)";
        $script=$tpl->RefreshInterval_js("netad-status",$page,"netad-status=yes");

    }

    $severtype["WIN_2003"]="Windows 2000/2003";
    $severtype["WIN_2008AES"]="Windows 2008/2012/2016";
    if(isset($_GET["switch-template"])){$_GET["switch-template"]=null;}
    if(!is_file("/etc/artica-postfix/settings/Daemons/KerbAuthSMBV2")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthSMBV2", 1);}

    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!isset($array["ADNETBIOSDOMAIN"])){$array["ADNETBIOSDOMAIN"]=null;}

    $check="<i class=\"fas fa-check\"></i>";
    $fullhosname="{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
    $editjs="LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&advanced=yes');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";


    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$editjs\"><i class='fa fa-edit'></i> {edit} </label>";

    $ntlmProgress=$tpl->framework_buildjs(
                "/ntlm/connect",
                "ntlm.join.progress",
                "ntlm.join.log",
                "progress-ad-restart",
                "document.location.href='/ad-connect'"
            );


    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$ntlmProgress\"><i class='fas fa-plug'></i> $text_button </label>";

    $ntlmunjoin=$tpl->framework_buildjs(
        "/ntlm/disconnect",
        "ntlm.join.progress",
        "ntlm.join.log",
        "progress-ad-restart",
        "document.location.href='/ad-connect'"
    );

    if($EnableKerbAuth==1) {
        $btns[] = "<label class=\"btn btn btn-primary\"  OnClick=\"Loadjs('fw.system.ActiveDirectory.analyze.php')\"> <i class='btn-info'></i> {analyze} </label>";
        $btns[]="<label class=\"btn btn btn-danger\" OnClick=\"$ntlmunjoin\"><i class='fas fa-unlink'></i> {disconnect} </label>";
    }

    $btns[]="</div>";



    $yesno[0]="<span class='label label'>{no}</span>";
    $yesno[1]="<span class='label label-primary'>{yes}</span>";


    $html[]="<table style='width:70%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align: top'><div id='netad-status'></div></td>";
    $html[]="<td style='width:100%;vertical-align: top'>";
    $html[]="<div style='margin-left:50px'>";
    $html[]="<div id='kerb-status'></div>";

    $ADNETIPADDR=null;

    if(isset($array["ADNETIPADDR"])){
        $ADNETIPADDR=" <small>({$array["ADNETIPADDR"]})</small>";

    }

    $tpl->table_form_field_js("Loadjs('$page?hostname-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{ad_full_hostname}","{$array["fullhosname"]}</strong>$ADNETIPADDR <small>({$severtype[$array["WINDOWS_SERVER_TYPE"]]})</small>",ico_microsoft);

    $tpl->table_form_field_js("Loadjs('$page?netbiosdomain-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{ADNETBIOSDOMAIN}",$array["ADNETBIOSDOMAIN"],ico_earth);

    $tpl->table_form_field_js(null,"AsSystemAdministrator");
    $tpl->table_form_field_text("{suffix}",$array["LDAP_SUFFIX"],ico_earth);
    $tpl->table_form_field_bool("{enable_ssl} (port 636)",$array["LDAP_SSL"],ico_ssl);
    $tpl->table_form_field_js("Loadjs('$page?adbranch-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{ad_computers_branch}",$array["COMPUTER_BRANCH"],ico_computer);
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{administrator}",$array["WINDOWS_SERVER_ADMIN"],ico_administrator);

    $tpl->table_form_field_js("Loadjs('$page?ntpad-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{synchronize_time_with_ad}",$NtpdateAD,ico_clock);
    $tpl->table_form_field_js("Loadjs('$page?changetrustpw-js=yes')","AsSystemAdministrator");
    if($ChangetrustpwDisable==1){$Changetrustpw=0;}else{$Changetrustpw=1;}
    $tpl->table_form_field_bool("{changetrustpw}",$Changetrustpw,ico_users);

    $tpl->table_form_field_js(null,"AsSystemAdministrator");
    $tpl->table_form_field_bool("{web_portal_authentication}",$EnableSquidMicroHotSpot,ico_hotspot);
    $tpl->table_form_field_js("Loadjs('$page?log-level-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{log_level}",$smb_log_level,ico_file);


    $ntlmProgress=$tpl->framework_buildjs(
        "/ntlm/connect",
        "ntlm.join.progress",
        "ntlm.join.log",
        "progress-ad-restart",
        "document.location.href='/ad-connect'"
    );
    $tpl->table_form_button($text_button,$ntlmProgress,"AsSystemAdministrator",ico_plug);
    $html[]=$tpl->table_form_compile();

    $html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="Active Directory &raquo;&raquo {join_domain}";
    $TINY_ARRAY["ICO"]="fab fa-windows";
    $TINY_ARRAY["EXPL"]="{activedirectory_explain_section}";
    $TINY_ARRAY["URL"]="ad-connect";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $kerbStatus=$tpl->RefreshInterval_js("kerb-status","fw.system.kerberos-single.php","CheckUPSettings=yes&ntlmcheck=yes");

    $html[] = "<script>";
    $html[] = $kerbStatus;
    $html[] = $script;
    $html[] = $jstiny;
    $html[] = "</script>";


    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);


}
function netbiosdomain_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{ADNETBIOSDOMAIN}","$page?netbiosdomain-popup=yes");
}
function hostname_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{ADNETBIOSDOMAIN}","$page?hostname-popup=yes");

}
function hostname_popup():bool{
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $tpl=new template_admin();
    $page=CurrentPageName();

    $severtype["WIN_2003"]="Windows 2000/2003";
    $severtype["WIN_2008AES"]="Windows 2008/2012/2016";
    if(isset($_GET["switch-template"])){$_GET["switch-template"]=null;}
    if(!is_file("/etc/artica-postfix/settings/Daemons/KerbAuthSMBV2")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthSMBV2", 1);}

    $fullhosname="{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
    $ADNETIPADDR=null;
    if(isset($array["ADNETIPADDR"])){
        $ADNETIPADDR=$array["ADNETIPADDR"];
    }

    $form[]=$tpl->field_text("fullhosname_change", "{ad_full_hostname}", $fullhosname,true);
    $form[]=$tpl->field_ipv4("ADNETIPADDR", "{ADNETIPADDR}", $ADNETIPADDR,false,"{howto_ADNETIPADDR}");
    $form[]=$tpl->field_array_hash($severtype, "WINDOWS_SERVER_TYPE", "{WINDOWS_SERVER_TYPE}", $array["WINDOWS_SERVER_TYPE"]);

    $jsrestart="BootstrapDialog1.close();LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');";


    $html=$tpl->form_outside(null,
        $form,null,"{apply}",
        $jsrestart,"AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function hostname_save():bool{

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $old_ADNETIPADDR=$array["ADNETIPADDR"];
    $old_fullhosname=$array["fullhosname"];
    $old_WINDOWS_DNS_SUFFIX=$array["WINDOWS_DNS_SUFFIX"];
    $old_WINDOWS_SERVER_TYPE=$array["WINDOWS_SERVER_TYPE"];

    $ipaddr=$_POST["ipaddr"];
    $IPClass=new IP();
    if(!$IPClass->isValid($ipaddr)){
        $ipaddr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($_POST["fullhosname_change"]);
    }
    $tb=explode(".",$_POST["fullhosname_change"]);
    $array["WINDOWS_SERVER_NETBIOSNAME"]=$tb[0];
    unset($tb[0]);
    $array["WINDOWS_DNS_SUFFIX"]=@implode(".", $tb);
    $array["fullhosname"]=$_POST["fullhosname_change"];


    if(!$IPClass->isValid($ipaddr)){
        echo "jserror:".$tpl->javascript_parse_text("{unable_to_resolve} {$_POST["fullhosname_change"]}");
        return false;
    }

    $array["ADNETIPADDR"]=$ipaddr;
    $array["WINDOWS_SERVER_TYPE"]=$_POST["WINDOWS_SERVER_TYPE"];
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");


    if($old_fullhosname<>$_POST["fullhosname_change"]) {
        admin_tracks("Change Active Directory Hostname from $old_fullhosname  to {$_POST["fullhosname_change"]}");
    }
    if($old_ADNETIPADDR<>$ipaddr) {
        admin_tracks("Change Active Directory IP from $old_ADNETIPADDR  to $ipaddr");
    }
    if($old_WINDOWS_DNS_SUFFIX<>$array["WINDOWS_DNS_SUFFIX"]) {
        admin_tracks("Change Active Directory domain from $old_WINDOWS_DNS_SUFFIX  to {$array["WINDOWS_DNS_SUFFIX"]}");
    }
    if($old_WINDOWS_SERVER_TYPE<>$array["WINDOWS_SERVER_TYPE"]) {
        admin_tracks("Change Active Directory Type from $old_WINDOWS_SERVER_TYPE  to {$array["WINDOWS_SERVER_TYPE"]}");
    }
    return true;
}
function adbranch_popup():bool{
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsrestart="dialogInstance2.close();;LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');";
    $form[] = $tpl->field_text("COMPUTER_BRANCH_CHANGE", "{ad_computers_branch}", $array["COMPUTER_BRANCH"], true);

    $html=$tpl->form_outside(null,
        $form,"","{apply}",
        $jsrestart,"AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function netbiosdomain_popup():bool{
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsrestart="BootstrapDialog1.close();LoadAjax('table-loader-ntlm-cnx-status','$page?table=yes&close=yes');";
    $form[] = $tpl->field_text("ADNETBIOSDOMAIN_CHANGE", "{ADNETBIOSDOMAIN}", $array["ADNETBIOSDOMAIN"], true);

    $html=$tpl->form_outside(null,
           $form,"{howto_ADNETBIOSDOMAIN}","{apply}",
            $jsrestart,"AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function netbiosdomain_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $old=$array["ADNETBIOSDOMAIN"];
    $array["ADNETBIOSDOMAIN"]=$_POST["ADNETBIOSDOMAIN_CHANGE"];
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
    admin_tracks("Change Active Directory Netbios domain from $old  to {$_POST["ADNETBIOSDOMAIN_CHANGE"]}");
    return true;
}
function adbranch_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $old=$array["COMPUTER_BRANCH"];
    $array["COMPUTER_BRANCH"]=$_POST["COMPUTER_BRANCH_CHANGE"];
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
    admin_tracks("Change Active Directory Computer branche from $old  to {$_POST["COMPUTER_BRANCH_CHANGE"]}");
    return true;
}

function Save(){
    $ipClass=new IP();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $LDAP_SSL=intval($_POST["LDAP_SSL"]);

    $sock->SET_INFO("NtpdateAD", $_POST["NtpdateAD"]);

    if(strpos($_POST["fullhosname"], ".")==0){
        echo "jserror:".$tpl->javascript_parse_text("{reject_invalid_hostname} {$_POST["fullhosname"]}");
        return false;
    }

    $IPClass=new IP();
    $ipaddr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($_POST["fullhosname"]);
    if(!$IPClass->isValid($ipaddr)){
        echo "jserror:".$tpl->javascript_parse_text("{unable_to_resolve} {$_POST["fullhosname"]}");
        return false;
    }

    $LDAP_PORT=389;
    if($LDAP_SSL==1){$LDAP_PORT=636;}
    $array["ADNETIPADDR"]=$ipaddr;

    $zports=array($LDAP_PORT,88,135,139);

    foreach ($zports as $TEST_PORT) {
        $fp = @fsockopen($ipaddr, $TEST_PORT, $errno, $errstr, 2);
        if (!$fp) {
            echo "jserror:" . $tpl->javascript_parse_text("{unable_to_connect} $ipaddr:$TEST_PORT - $errstr");
            return;
            @fclose($fp);
            return false;
        }
    }


    $tb=explode(".",$_POST["fullhosname"]);
    $array["WINDOWS_SERVER_NETBIOSNAME"]=$tb[0];
    $array["fullhosname"]=$_POST["fullhosname"]; // Patch 10/06/2020



    if(strpos($_POST["WINDOWS_SERVER_ADMIN"], "@")>0){
        $trx=explode("@",$_POST["WINDOWS_SERVER_ADMIN"]);
        $_POST["WINDOWS_SERVER_ADMIN"]=$trx[0];
        $_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($trx[1]));
    }else{
        $tre=explode(".",$_POST["fullhosname"]);
        unset($tre[0]);
        $_POST["WINDOWS_DNS_SUFFIX"]=@implode(".", $tre);

    }

    if(!isset($_POST["WINDOWS_DNS_SUFFIX"])){
        $tb=explode(".",$_POST["fullhosname"]);
        unset($tb[0]);
        $_POST["WINDOWS_DNS_SUFFIX"]=@implode(".", $tb);
    }

    if(strpos($_POST["ADNETBIOSDOMAIN"], ".")>0){
        echo "The netbios domain \"{$_POST["ADNETBIOSDOMAIN"]}\" is invalid.\n";
        return false;
    }

    $array["LDAP_SERVER"]=$ipaddr;
    $array["LDAP_DN"]=$_POST["WINDOWS_SERVER_ADMIN"]."@".$_POST["WINDOWS_DNS_SUFFIX"];
    $array["LDAP_PASSWORD"]=$_POST["WINDOWS_SERVER_PASS"];
    $array["LDAP_PORT"]=389;
    $ldapuri = "ldap://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}";
    foreach ($_POST as $num=>$ligne){$array[$num]=$ligne;}

    if(intval($_POST["LDAP_SSL"])==1){
        $array["LDAP_PORT"]=636;
        $ldapuri = "ldaps://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}";
    }

    $ldap_connection=@ldap_connect($ldapuri);
    if(!$ldap_connection){
        $DIAG[]="{Connection_Failed_to_connect_to_DC} {$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}";
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo "jserror:".$tpl->javascript_parse_text(@implode("<br>", $DIAG));
        @ldap_close();
        return false;
    }

    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
    $bind=ldap_bind($ldap_connection, $array["LDAP_DN"],$array["LDAP_PASSWORD"]);
    if(!$bind){
        SyslogAd("{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]} {$array["LDAP_DN"]} bind failed");
        $DIAG[]="{login_Failed_to_connect_to_DC} {$array["LDAP_SERVER"]} - {$array["LDAP_DN"]}";
        $DIAG[]=ldap_err2str(ldap_errno($ldap_connection));
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo "jserror:".$tpl->javascript_parse_text(@implode("<br>", $DIAG));
        return false;
    }

    $ad_rootdse=new ad_rootdse(
        $array["LDAP_SERVER"],
        $array["LDAP_PORT"],
        $array["LDAP_DN"],
        $array["LDAP_PASSWORD"],$LDAP_SSL
    );

    if($_POST["LDAP_SUFFIX"]==null) {
        $RootDse = $ad_rootdse->RootDSE();
        if (!$RootDse) {
            echo "jserror:" . $tpl->javascript_parse_text("{rootdse_failed_advsuff}");
            return false;
        }
        $_POST["LDAP_SUFFIX"]=$RootDse;
        $array["LDAP_SUFFIX"]=$RootDse;
    }

    if($_POST["ADNETBIOSDOMAIN"]==null){
        $NetbiosDomain=$ad_rootdse->nETBIOSName( $_POST["LDAP_SUFFIX"]);
        if(!$NetbiosDomain){
            $error=$ad_rootdse->mysql_error;
            echo "jserror:" . $tpl->javascript_parse_text("netbios domain failed $error");
            return false;
        }

        $array["ADNETBIOSDOMAIN"]=$NetbiosDomain;
    }
    $array["CONFIG_SAVED"]=1;

    $sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
    $sock->SET_INFO("KerbAuthSMBV2", 1);
    $sock->SET_INFO("WindowsActiveDirectoryKerberos", 0);

}
function SyslogAd($text){
    if(!function_exists("openlog")){return true;}
    $f=basename(__FILE__);
    $text="[$f]: $text";
    openlog("activedirectory", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
