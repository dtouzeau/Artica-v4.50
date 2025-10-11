<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["instance-id"])){save();exit;}
if(isset($_POST["SmtpTlsSecurityLevel"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["static"])){Static_form();exit;}
if(isset($_GET["submission-js"])){form_submission_js();exit;}
if(isset($_GET["submission-popup"])){form_submission_popup();exit;}

if(isset($_GET["allowanonymouslogin-js"])){form_allowanonymouslogin_js();exit;}
if(isset($_GET["allowanonymouslogin-popup"])){form_allowanonymouslogin_popup();exit;}

if(isset($_GET["tlsserv-js"])){form_tlsserv_js();exit;}
if(isset($_GET["tlsserv-popup"])){form_tlsserv_popup();exit;}

if(isset($_GET["tlsremoteserv-js"])){form_tlsremoteserv_js();exit;}
if(isset($_GET["tlsremoteserv-popup"])){form_tlsremoteserv_popup();exit;}

if(isset($_GET["tlsserv-ciphers-js"])){form_tlsserv_cipher_js();exit;}
if(isset($_GET["tlsserv-ciphers-popup"])){form_tlsserv_cipher_popup();exit;}



Static_start();

function Static_start():bool{
    $page=CurrentPageName();
    if(!isset($_GET["instance-id"])){
        $_GET["instance-id"]=0;
    }
    $instance_id=intval($_GET["instance-id"]);
    echo "<div id='postfix-tls-static'></div><script>LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id');</script>";
    return true;
}
function Static_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);
    $SmtpSaslAuthEnable=intval($main->GET("SmtpSaslAuthEnable"));
    $SmtpTlsSecurityLevel=$main->GET("SmtpTlsSecurityLevel");
    $SmtpTlsWrapperMode=intval($main->GET("SmtpTlsWrapperMode"));
    $q=new lib_sqlite();
    $PostfixEnableMasterCfSSL=intval($main->GET("PostfixEnableMasterCfSSL"));
    $SmtpTlsProtocols=trim($main->GET("SmtpTlsProtocols"));
    $SmtpTlsSessionCacheTimeout=intval($main->GET("SmtpTlsSessionCacheTimeout"));
    if($SmtpTlsSessionCacheTimeout==0){$SmtpTlsSessionCacheTimeout=3600;}
    if($SmtpTlsProtocols==null){$SmtpTlsProtocols="!SSLv2, !SSLv3";}
    $PostfixEnableSubmission=intval($main->GET("PostfixEnableSubmission"));
    $PostfixEnforceSubmission=intval($main->GET_INFO("PostfixEnforceSubmission"));
    $SmtpdSenderRestrictionsDisable= intval($main->GET("SmtpdSenderRestrictionsDisable"));
    $smtpd_tls_mandatory_ciphers=$main->GET_INFO("smtpd_tls_mandatory_ciphers");
    $smtpd_tls_exclude_ciphers=$main->GET_INFO("smtpd_tls_exclude_ciphers");
    $tls_high_cipherlist=$main->GET_INFO("tls_high_cipherlist");
    if(strlen($smtpd_tls_mandatory_ciphers)<2){
        $smtpd_tls_mandatory_ciphers="medium";
    }
    if($SmtpTlsSecurityLevel==""){
        $SmtpTlsSecurityLevel="may";
    }

    // Client
    $tls_preempt_cipherlist=intval($main->GET("tls_preempt_cipherlist"));
    $smtp_tls_mandatory_ciphers=$main->GET_INFO("smtp_tls_mandatory_ciphers");
    $smtp_tls_exclude_ciphers=$main->GET_INFO("smtp_tls_exclude_ciphers");
    $tls_high_cipherlist=$main->GET_INFO("tls_high_cipherlist");
    if(strlen($smtp_tls_mandatory_ciphers)<2){
        $smtp_tls_mandatory_ciphers="medium";
    }


    $smtp_cipherlist=$main->GET_INFO("smtp_cipherlist");
    $smtpd_tls_protocols=$main->GET("smtpd_tls_protocols");
    if($smtpd_tls_protocols==null){
        $smtpd_tls_protocols="!SSLv2, !SSLv3";
    }

    $PostFixMasterCertificate=trim($main->GET("PostFixMasterCertificate"));
    $smtpd_tls_session_cache_timeout=intval($main->GET("smtpd_tls_session_cache_timeout"));
    if($smtpd_tls_session_cache_timeout==0){$smtpd_tls_session_cache_timeout=3600;}
    $smtpd_tls_auth_only=intval($main->GET("smtpd_tls_auth_only"));

    $smtpd_tls_security_level=$main->GET("smtpd_tls_security_level");
    if($smtpd_tls_security_level==null){$smtpd_tls_security_level="may";}

    $array_field_relay_tls=$q->array_field_relay_tls;


    if(preg_match("#SUB:([0-9]+)#",$PostFixMasterCertificate,$re)){
        $db="/home/artica/SQLITE/certificates.db";
        if(isset($_SESSION["HARMPID"])){
            $gpid=intval($_SESSION["HARMPID"]);
            if($gpid>0){
                $db="/home/artica/SQLITE/certificates.$gpid.db";
            }
        }
        $q=new lib_sqlite($db);
        $ligne=$q->mysqli_fetch_array("SELECT commonName FROM subcertificates WHERE ID=$re[1]");
        $PostFixMasterCertificate=$ligne["commonName"]."-$re[1]";
    }

    $fleche="&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;";
    $tpl->table_form_field_js("Loadjs('$page?tlsremoteserv-js=yes&instance-id=$instance_id')");

    $tpl->table_form_section("{your_server} $fleche TLS $fleche {remote_servers}");

    if ($SmtpTlsSecurityLevel=="none"){
        $tpl->table_form_field_text("{enabled}", "{inactive2}", ico_certificate);


    }else{
        $tls="{tls_label} ".$array_field_relay_tls[$SmtpTlsSecurityLevel]. " $SmtpTlsProtocols {smtp_tls_session_cache_timeout} $smtpd_tls_session_cache_timeout seconds";
        $tpl->table_form_field_text("TLS {remote_servers}", $tls, ico_certificate);
        $exlude="";

        if(strlen($smtp_tls_exclude_ciphers)>2) {
            $exlude="&nbsp;{exclude} <span style='text-transform:initial'>$smtp_tls_exclude_ciphers</span>";
        }

        $tpl->table_form_field_text("{smtpd_tls_mandatory_ciphers}",
            "{".$smtp_tls_mandatory_ciphers."}$exlude", ico_certificate);
        if(strlen($smtp_cipherlist)>2){
            $tpl->table_form_field_text("Ciphers",
                "<span style='text-transform:initial;font-size:12px'>$smtp_cipherlist</span>", ico_certificate);
        }
    }


    $tpl->table_form_field_js("Loadjs('$page?tlsserv-js=yes&instance-id=$instance_id')");
    $tpl->table_form_section("{remote_servers} $fleche TLS $fleche {your_server}");

    if($PostfixEnableMasterCfSSL==0){
        $tpl->table_form_field_text("SMTP SSL (465)", "{inactive2}", ico_nic);

    }else {
        $opts = array();
        $tpl->table_form_field_bool("SMTP SSL (465)",1,ico_nic);
        if ($smtpd_tls_auth_only == 1) {
            $opts[] = "{smtpd_tls_auth_only}";
        }
        $opts[] = "{tls_label} $array_field_relay_tls[$smtpd_tls_security_level]";
        $opts[] = "{timeout} $smtpd_tls_session_cache_timeout {seconds}";

        $opts_text=@implode(", ",$opts);
        $tpl->table_form_field_text("{certificate}", " &laquo;$PostFixMasterCertificate&raquo;, $smtpd_tls_protocols, $opts_text", ico_certificate);
        $tpl->table_form_field_js("Loadjs('$page?tlsserv-ciphers-js=yes&instance-id=$instance_id')");

        if(strlen($smtpd_tls_exclude_ciphers)<2) {
            $smtpd_tls_exclude_ciphers = "EXP, MEDIUM, LOW, DES, 3DES, SSLv2";
        }
        $exlude="&nbsp;{exclude} <span style='text-transform:initial'>$smtpd_tls_exclude_ciphers</span>";

        $tpl->table_form_field_text("{smtpd_tls_mandatory_ciphers}",
            "{".$smtpd_tls_mandatory_ciphers."}$exlude", ico_certificate);
        if(strlen($tls_high_cipherlist)>2){
            $tpl->table_form_field_text("Ciphers",
            "<span style='text-transform:initial;font-size:12px'>$tls_high_cipherlist</span>", ico_certificate);
        }

        $tpl->table_form_field_bool("{tls_preempt_cipherlist}",$tls_preempt_cipherlist,ico_server);
    }




    $tpl->table_form_field_js("Loadjs('$page?submission-js=yes&instance-id=$instance_id')");
    $tpl->table_form_field_bool("{PostfixEnableSubmission} (587 port)",$PostfixEnableSubmission,ico_interface);

    $tpl->table_form_field_js("Loadjs('$page?allowanonymouslogin-js=yes&instance-id=$instance_id')");
    if($SmtpdSenderRestrictionsDisable==0) {
        $tpl->table_form_field_text("{allowanonymouslogin}", "{no}", ico_user_lock);
    }else{
        $tpl->table_form_field_text("{allowanonymouslogin}", "{yes}", ico_user);
    }
    $html[]=$tpl->table_form_compile();

    $instancename=null;
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

    $TINY_ARRAY["TITLE"]="TLS/SSL <small>$instancename</small>";
    $TINY_ARRAY["ICO"]="fa-brands fa-expeditedssl";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_TEXT}";
    $TINY_ARRAY["URL"]="instance-postffix-settings-$instance_id";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function form_submission_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    return $tpl->js_dialog2("{PostfixEnableSubmission}","$page?submission-popup=yes&instance-id=$instance_id");
}
function form_tlsserv_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    return $tpl->js_dialog2("TLS {your_server}","$page?tlsserv-popup=yes&instance-id=$instance_id");
}
function form_tlsserv_cipher_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    return $tpl->js_dialog2("TLS {your_server}","$page?tlsserv-ciphers-popup=yes&instance-id=$instance_id");
}
function form_allowanonymouslogin_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    return $tpl->js_dialog2("{allowanonymouslogin}","$page?allowanonymouslogin-popup=yes&instance-id=$instance_id");
}
function form_submission_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);
    $PostfixEnableSubmission=intval($main->GET("PostfixEnableSubmission"));
    $PostfixEnforceSubmission=intval($main->GET_INFO("PostfixEnforceSubmission"));
    $jsrestart="dialogInstance2.close();LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id')";

    $html[]="<div id='postfix-submission-restart-$instance_id'></div>";
    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_checkbox("PostfixEnableSubmission",
        "{PostfixEnableSubmission}",$PostfixEnableSubmission,false,'{PostfixEnableSubmission_text}');
    $form[]=$tpl->field_checkbox("PostfixEnforceSubmission","{enforce_submission_port_encrypt}",$PostfixEnforceSubmission,false,"{PostfixEnableSubmission_text}");

    $html[]= $tpl->form_outside(null, $form,"{PostfixEnableSubmission_text}","{apply}",$jsrestart,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function form_allowanonymouslogin_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);
    $SmtpdSenderRestrictionsDisable=intval($main->GET("SmtpdSenderRestrictionsDisable"));

    $jsrestart="dialogInstance2.close();LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id')";
    $html[]="<div id='postfix-allowanonymouslogin-restart-$instance_id'></div>";
    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_checkbox("SmtpdSenderRestrictionsDisable",
        "{allowanonymouslogin}",$SmtpdSenderRestrictionsDisable,false,'');

    $html[]= $tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function form_tlsremoteserv_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    return $tpl->js_dialog2("TLS {remote_servers}","$page?tlsremoteserv-popup=yes&instance-id=$instance_id");
}
function form_tlsremoteserv_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);
    $SmtpTlsWrapperMode=intval($main->GET("SmtpTlsWrapperMode"));
    $SmtpTlsProtocols=intval($main->GET("SmtpTlsProtocols"));
    $SmtpTlsSessionCacheTimeout=intval($main->GET("SmtpTlsSessionCacheTimeout"));
    $SmtpTlsSecurityLevel=$main->GET("SmtpTlsSecurityLevel");
    if($SmtpTlsSessionCacheTimeout==0){$SmtpTlsSessionCacheTimeout=3600;}
    if($SmtpTlsProtocols==null){$SmtpTlsProtocols="!SSLv2, !SSLv3";}

    $smtp_tls_mandatory_ciphers=$main->GET_INFO("smtp_tls_mandatory_ciphers");
    $smtp_tls_exclude_ciphers=$main->GET_INFO("smtp_tls_exclude_ciphers");
    $smtp_cipherlist=$main->GET_INFO("smtp_cipherlist");
    if(strlen($smtp_tls_mandatory_ciphers)<2){
        $smtp_tls_mandatory_ciphers="medium";
    }

    $q=new lib_sqlite();
    $array_field_relay_tls=$q->array_field_relay_tls;


    $html[]="<div id='postfix-tlsserv-restart-$instance_id'></div>";
    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_checkbox("SmtpTlsWrapperMode","{UseSMTPS}",$SmtpTlsWrapperMode,false,"{smtp_tls_wrappermode}");
    $form[]=$tpl->field_array_hash($array_field_relay_tls, "SmtpTlsSecurityLevel", "{tls_label} ({default})", $SmtpTlsSecurityLevel);
    $form[]=$tpl->field_text("SmtpTlsProtocols", "{smtp_tls_protocols}", $SmtpTlsProtocols,false,"");
    $form[]=$tpl->field_numeric("SmtpTlsSessionCacheTimeout","{smtp_tls_session_cache_timeout} ({seconds})",$SmtpTlsSessionCacheTimeout);

    $smtpd_tls_mandatory_ciphers_array["high"]="{high}";
    $smtpd_tls_mandatory_ciphers_array["medium"]="{medium}";
    $smtpd_tls_mandatory_ciphers_array["low"]="{low}";

    $form[]=$tpl->field_array_hash($smtpd_tls_mandatory_ciphers_array,
        "smtp_tls_mandatory_ciphers","{smtpd_tls_mandatory_ciphers}",$smtp_tls_mandatory_ciphers);

    $form[]=$tpl->field_text("smtp_tls_exclude_ciphers","{smtpd_tls_exclude_ciphers}",$smtp_tls_exclude_ciphers);

    $form[]=$tpl->field_text("smtp_cipherlist","Ciphers",$smtp_cipherlist);

    $jsrestart="dialogInstance2.close();LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id')";

    $html[]= $tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function form_tlsserv_cipher_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);
    $smtpd_tls_mandatory_ciphers=$main->GET_INFO("smtpd_tls_mandatory_ciphers");
    $smtpd_tls_exclude_ciphers=$main->GET_INFO("smtpd_tls_exclude_ciphers");
    $tls_high_cipherlist=$main->GET_INFO("tls_high_cipherlist");
    if(strlen($smtpd_tls_mandatory_ciphers)<2){
        $smtpd_tls_mandatory_ciphers="medium";
    }

    if(strlen($smtpd_tls_exclude_ciphers)<2) {
        $smtpd_tls_exclude_ciphers = "EXP, MEDIUM, LOW, DES, 3DES, SSLv2";
    }

    // Also modifies : smtpd_tls_ciphers, smtpd_tls_mandatory_ciphers
    // smtpd_tls_mandatory_exclude_ciphers,smtpd_tls_exclude_ciphers
    // tls_high_cipherlist

    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $smtpd_tls_mandatory_ciphers_array["high"]="{high}";
    $smtpd_tls_mandatory_ciphers_array["medium"]="{medium}";
    $smtpd_tls_mandatory_ciphers_array["low"]="{low}";

    $form[]=$tpl->field_array_hash($smtpd_tls_mandatory_ciphers_array,
        "smtpd_tls_mandatory_ciphers","{smtpd_tls_mandatory_ciphers}",$smtpd_tls_mandatory_ciphers);

    $form[]=$tpl->field_text("smtpd_tls_exclude_ciphers","{smtpd_tls_exclude_ciphers}",$smtpd_tls_exclude_ciphers);

    $jsrestart="dialogInstance2.close();LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id')";

    $form[]=$tpl->field_text("tls_high_cipherlist","Ciphers",$tls_high_cipherlist);
    $html[]= $tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function form_tlsserv_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);

    $PostfixEnableMasterCfSSL=intval($main->GET("PostfixEnableMasterCfSSL"));
    $PostFixMasterCertificate=trim($main->GET("PostFixMasterCertificate"));
    $smtpd_tls_session_cache_timeout=intval($main->GET("smtpd_tls_session_cache_timeout"));
    if($smtpd_tls_session_cache_timeout==0){$smtpd_tls_session_cache_timeout=3600;}
    $smtpd_tls_auth_only=intval($main->GET("smtpd_tls_auth_only"));
    $tls_preempt_cipherlist=intval($main->GET("tls_preempt_cipherlist"));
    $smtpd_tls_security_level=$main->GET("smtpd_tls_security_level");
    if($smtpd_tls_security_level==null){$smtpd_tls_security_level="may";}

    $smtpd_tls_protocols=$main->GET("smtpd_tls_protocols");
    if($smtpd_tls_protocols==null){
        $smtpd_tls_protocols="!SSLv2, !SSLv3";
    }

    $jsrestart="dialogInstance2.close();LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id')";


    $html[]="<div id='postfix-tlsserv-restart-$instance_id'></div>";
    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_checkbox("PostfixEnableMasterCfSSL","{ENABLE_SMTPS}",$PostfixEnableMasterCfSSL,true,"{SMTPS_TEXT}");
    $form[]=$tpl->field_certificate("PostFixMasterCertificate", "{use_certificate_from_certificate_center}",$PostFixMasterCertificate);
    $form[]=$tpl->field_checkbox("smtpd_tls_auth_only","{smtpd_tls_auth_only}",$smtpd_tls_auth_only,false,"{smtpd_tls_auth_only_text}");
    $q=new lib_sqlite();
    $array_field_relay_tls=$q->array_field_relay_tls;

    $form[]=$tpl->field_array_hash($array_field_relay_tls, "smtpd_tls_security_level", "{tls_label} ({default})", $smtpd_tls_security_level);

    $form[]=$tpl->field_text("smtpd_tls_protocols", "{smtp_tls_protocols}", $smtpd_tls_protocols,false,"");

    $form[]=$tpl->field_numeric("smtpd_tls_session_cache_timeout","{smtp_tls_session_cache_timeout} ({seconds})",$smtpd_tls_session_cache_timeout);

    $form[]=$tpl->field_checkbox("tls_preempt_cipherlist","{tls_preempt_cipherlist}",$tls_preempt_cipherlist);

    $html[]= $tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);

    $jsrestart="dialogInstance2.close();LoadAjax('postfix-tls-static','$page?static=yes&instance-id=$instance_id')";

    $SmtpSaslAuthEnable=intval($main->GET("SmtpSaslAuthEnable"));
    $SmtpTlsSecurityLevel=$main->GET("SmtpTlsSecurityLevel");
    $SmtpTlsWrapperMode=intval($main->GET("SmtpTlsWrapperMode"));


    $SmtpTlsProtocols=intval($main->GET("SmtpTlsProtocols"));
    $SmtpTlsSessionCacheTimeout=intval($main->GET("SmtpTlsSessionCacheTimeout"));
    if($SmtpTlsSessionCacheTimeout==0){$SmtpTlsSessionCacheTimeout=3600;}
    if($SmtpTlsProtocols==null){$SmtpTlsProtocols="!SSLv2, !SSLv3";}
    $PostfixEnableSubmission=intval($main->GET("PostfixEnableSubmission"));
    $SmtpdSenderRestrictionsDisable= intval($main->GET("SmtpdSenderRestrictionsDisable"));


    $PostfixEnableMasterCfSSL=intval($main->GET("PostfixEnableMasterCfSSL"));
    $PostFixMasterCertificate=trim($main->GET("PostFixMasterCertificate"));
    $smtpd_tls_session_cache_timeout=intval($main->GET("smtpd_tls_session_cache_timeout"));
    if($smtpd_tls_session_cache_timeout==0){$smtpd_tls_session_cache_timeout=3600;}
    $smtpd_tls_auth_only=intval($main->GET("smtpd_tls_auth_only"));

    $smtpd_tls_security_level=$main->GET("smtpd_tls_security_level");
    if($smtpd_tls_security_level==null){$smtpd_tls_security_level="may";}


    $q=new lib_sqlite();
    $array_field_relay_tls=$q->array_field_relay_tls;
    //smtp_use_tls_explain

    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_checkbox("SmtpTlsWrapperMode","{UseSMTPS}",$SmtpTlsWrapperMode,false,"{smtp_tls_wrappermode}");
    $form[]=$tpl->field_array_hash($array_field_relay_tls, "SmtpTlsSecurityLevel", "{tls_label} ({default})", $SmtpTlsSecurityLevel);
    $form[]=$tpl->field_text("SmtpTlsProtocols", "{smtp_tls_protocols}", $SmtpTlsProtocols,true,"");
    $form[]=$tpl->field_numeric("SmtpTlsSessionCacheTimeout","{smtp_tls_session_cache_timeout} ({seconds})",$SmtpTlsSessionCacheTimeout);




    $form[]=$tpl->field_section("TLS {your_server}");
    $form[]=$tpl->field_checkbox("PostfixEnableMasterCfSSL","{ENABLE_SMTPS}",$PostfixEnableMasterCfSSL,false,"{SMTPS_TEXT}");
    $form[]=$tpl->field_certificate("PostFixMasterCertificate", "{use_certificate_from_certificate_center}",$PostFixMasterCertificate);
    $form[]=$tpl->field_checkbox("smtpd_tls_auth_only","{smtpd_tls_auth_only}",$smtpd_tls_auth_only,false,"{smtpd_tls_auth_only_text}");


    $form[]=$tpl->field_array_hash($array_field_relay_tls, "smtpd_tls_security_level", "{tls_label} ({default})", $smtpd_tls_security_level);
    $form[]=$tpl->field_numeric("smtpd_tls_session_cache_timeout","{smtp_tls_session_cache_timeout} ({seconds})",$smtpd_tls_session_cache_timeout);

    echo $tpl->form_outside("TLS {remote_servers}", $form,null,"{apply}",$jsrestart,"AsPostfixAdministrator");

    $instancename=null;
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }



}

function save() {
    $tpl=new template_admin();
    $instance_id=intval($_POST["instance-id"]);
    $main=new maincf_multi($instance_id);
    $tpl->CLEAN_POST();
    $main->SAVE_POSTS();
    $sock=new sockets();
    $sock->REST_API("/postfix/tls/$instance_id");

}