<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["postfix-smtpd-checktls"])){SMTPDCheckTLS();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["message_size_limit"])){section_mime_save();}
if(isset($_POST["instance_id"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-flat"])){table_flat();exit;}
if(isset($_GET["section-headers"])){section_header();exit;}
if(isset($_GET["section-headers-popup"])){section_headers_popup();exit;}

if(isset($_GET["section-interfaces"])){section_interfaces();exit;}
if(isset($_GET["section-interfaces-popup"])){section_interfaces_popup();exit;}

if(isset($_GET["section-safety"])){section_safety();exit;}
if(isset($_GET["section-safety-popup"])){section_safety_popup();exit;}

if(isset($_GET["section-outin"])){section_OutIn();exit;}
if(isset($_GET["section-outin-popup"])){section_OutIn_popup();exit;}

if(isset($_GET["section-auth"])){section_auth();exit;}
if(isset($_GET["section-auth-popup"])){section_auth_popup();exit;}

if(isset($_GET["section-mime"])){section_mime();exit;}
if(isset($_GET["section-mime-popup"])){section_mime_popup();exit;}

if(isset($_GET["section-others"])){section_others();exit;}
if(isset($_GET["section-others-popup"])){section_others_popup();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $MAIN_TITLE="{APP_POSTFIX}";
    $addon=null;
    if(isset($_GET["onlyMulti"])){
        $addon="&onlyMulti=yes";
        $MAIN_TITLE="{instances}";
    }
    if(isset($_GET["instance-id"])) {
        if(intval($_GET["instance-id"])>0){
            $addon="&instance-id={$_GET["instance-id"]}";
        }
    }

    $html=$tpl->page_header("$MAIN_TITLE v$POSTFIX_VERSION",
        "fas fa-mail-bulk",
        "{APP_POSTFIX_TEXT}",
        "$page?tabs=yes$addon",
        "postfix-settings",
        "progress-postfix-mainconf",false,
        "table-loader-postfix-service"
    );
	

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){

	$page=CurrentPageName();
	$tpl=new template_admin();
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    $addon=null;
    $array=array();
    if($EnablePostfixMultiInstance==1){
        if(!isset($_GET["instance-id"])) {
            $array["{multiple_instances}"] = "fw.postfix.multi.php?table=yes";
        }
    }
    if(isset($_GET["instance-id"])){
        if(intval($_GET["instance-id"])>0){
            $addon="&instance-id=".$_GET["instance-id"];
        }
    }

    if(!isset($_GET["onlyMulti"])) {
        $array["{parameters}"] = "$page?table=yes$addon";
        $array["{postmaster}/{templates}"] = "fw.postfix.postmaster.php?nothing=yes$addon";
        $array["TLS/SSL"] = "fw.postfix.tls.php?nothing=yes$addon";

    }
    if(!isset($_GET["onlyMulti"])) {
        if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $array["{cluster}"] = "fw.pdns.cluster.php";
        }
    }
	echo $tpl->tabs_default($array);
}
function tokens_migration(){
    $main=new maincf_multi(0);
    if(!$main->TokenExists("MasterCFUseDefaults")){
        $main->SET_VALUE("MasterCFUseDefaults",1);
    }
    if(!$main->TokenExists("TrustMyNetwork")){
        $main->SET_VALUE("TrustMyNetwork",1);
    }



    $f[]="TrustMyNetwork";
    $f[]="EnableGenericrDNSClients";
    $f[]="EnableBlockUsersTroughInternet";
    $f[]="broken_sasl_auth_clients";
    $f[]="smtpd_sasl_security_options";
    $f[]="smtpd_sasl_local_domain";
    $f[]="smtpd_sasl_authenticated_header";
    $f[]="smtpd_tls_security_level";
    $f[]="smtpd_tls_auth_only";
    $f[]="smtpd_tls_received_header";
    $f[]="EnablePostfixAntispamPack";
    $f[]="PostfixMiltersBehavior";
    $f[]="PostfixEnableMasterCfSSL";
    $f[]="PostfixBindInterfacePort";
    $f[]="smtp_sender_dependent_authentication";
    $f[]="PostFixEnableQueueInMemory";
    $f[]="MynetworksInISPMode";
    $f[]="PostFixQueueInMemory";
    $f[]="PostfixQueueEnabled";
    $f[]="PostfixQueueMaxMails";



    foreach ($f as $tokens){
        if(!$main->TokenExists($tokens)){
            $value=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO($tokens));
            if($value==null){continue;}
            $main->SET_VALUE($tokens,$GLOBALS["CLASS_SOCKETS"]->GET_INFO($tokens));
        }

    }

}
function table(){
    $page=CurrentPageName();
    echo "<div id='postfix-table-flat'></div>";
    echo "<script>LoadAjax('postfix-table-flat','$page?table-flat=yes');</script>";

}

function GetInstanceID():int{
    $instance_id=0;
    if(isset($_GET["instance-id"])){
        if(intval($_GET["instance-id"])>0) {
            return intval($_GET["instance-id"]);
        }
    }
    return $instance_id;
}
function section_header():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    return $tpl->js_dialog2("{smtp_headers}/{protocol}","$page?section-headers-popup=yes&instance-id=$instance_id");
}
function section_interfaces():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    return $tpl->js_dialog2("{listen_ports}/{nics}","$page?section-interfaces-popup=yes&instance-id=$instance_id");
}
function section_safety():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    return $tpl->js_dialog2("{safety_standards}","$page?section-safety-popup=yes&instance-id=$instance_id");
}
function section_interfaces_popup():bool{
    $tpl=new template_admin();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);
    $PostfixEnableSubmission=intval($main->GET_INFO("PostfixEnableSubmission"));
    $PostfixEnforceSubmission=intval($main->GET_INFO("PostfixEnforceSubmission"));
    $PostfixEnableProxyProtocol=intval($main->GET_INFO("PostfixEnableProxyProtocol"));

    if($instance_id==0) {
        $PostfixBinInterfaces = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBinInterfaces"));
        $form[] = $tpl->field_interfaces_choose("PostfixBinInterfaces", "{listen_interfaces}", $PostfixBinInterfaces);
    }
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_checkbox("PostfixEnableSubmission","{PostfixEnableSubmission}",$PostfixEnableSubmission,'PostfixEnforceSubmission',"{PostfixEnableSubmission_text}");
    $form[]=$tpl->field_checkbox("PostfixEnforceSubmission","{enforce_submission_port_encrypt}",$PostfixEnforceSubmission,false,"{PostfixEnableSubmission_text}");
    $form[]=$tpl->field_checkbox("PostfixEnableProxyProtocol","{enable_smtp_haproxy}",$PostfixEnableProxyProtocol,"PostfixProxyProtocolPort","{enable_smtp_haproxy_explain}");
    echo $tpl->form_outside(null, $form,null,"{apply}",reconfigure_js($instance_id),"AsPostfixAdministrator");
    return true;
}
function section_OutIn(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    $function="";
    if(isset($_GET["function"])){
        $function="&function={$_GET["function"]}";
    }
    return $tpl->js_dialog2("{incoming}/{outgoing}","$page?section-outin-popup=yes&instance-id=$instance_id$function");
}
function section_auth():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    return $tpl->js_dialog2("{authentication}/{method}","$page?section-auth-popup=yes&instance-id=$instance_id");
}
function section_mime():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    return $tpl->js_dialog2("{MIME_OPTIONS}","$page?section-mime-popup=yes&instance-id=$instance_id");
}
function section_others():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    return $tpl->js_dialog2("{other_settings}","$page?section-others-popup=yes&instance-id=$instance_id");

}
function section_others_popup(){
    $tpl=new template_admin();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);

    $enable_original_recipient=$main->GET("enable_original_recipient");
    $smtpd_discard_ehlo_keywords=$main->GET("smtpd_discard_ehlo_keywords");
    if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
    $undisclosed_recipients_header=$main->GET("undisclosed_recipients_header");
    if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_text("undisclosed_recipients_header", "{undisclosed_recipients_header}", $undisclosed_recipients_header,true,"{undisclosed_recipients_header_text}");
    $form[]=$tpl->field_text("smtpd_discard_ehlo_keywords", "{smtpd_discard_ehlo_keywords}", $smtpd_discard_ehlo_keywords,false,"{smtpd_discard_ehlo_keywords_explain}");
    $form[]=$tpl->field_checkbox("enable_original_recipient","{enable_original_recipient}",$enable_original_recipient,false,"{enable_original_recipient_text}");
    echo $tpl->form_outside(null, $form,null,"{apply}",reconfigure_js($instance_id),"AsPostfixAdministrator");
    return true;
}

function section_auth_popup():bool{
    $tpl=new template_admin();
    $instance_id=GetInstanceID();



    $smtp_sasl_security_options_class=new maincf_multi($instance_id);
    $smtp_sasl_security_options=unserialize($smtp_sasl_security_options_class->GET_BIGDATA("smtp_sasl_security_options"));
    if(!isset($smtp_sasl_security_options["noanonymous"])){$smtp_sasl_security_options["noanonymous"]=1;}

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_hidden("section_auth_popup",$instance_id);
    $form[]=$tpl->field_section("{smtp_authentication} <small>({smtp_sasl_security_options_text})</small>");
    $form[]=$tpl->field_checkbox("noanonymous","{smtp_sasl_security_options_noanonymous}",$smtp_sasl_security_options["noanonymous"],false);
    $form[]=$tpl->field_checkbox("noplaintext","{smtp_sasl_security_options_noplaintext}",$smtp_sasl_security_options["noplaintext"],false);
    $form[]=$tpl->field_checkbox("nodictionary","{smtp_sasl_security_options_nodictionary}",$smtp_sasl_security_options["nodictionary"],false);
    $form[]=$tpl->field_checkbox("mutual_auth","{smtp_sasl_security_options_mutual_auth}",$smtp_sasl_security_options["mutual_auth"],false);

    echo $tpl->form_outside(null, $form,null,"{apply}",reconfigure_js($instance_id),"AsPostfixAdministrator");
    return true;
}
function section_OutIn_popup():bool{
    $tpl=new template_admin();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);

    $RestrictToInternalDomains=intval($main->GET("RestrictToInternalDomains"));
    $RestrictToInternalDomainsLists=$main->GET('RestrictToInternalDomainsLists');
    $RestrictToOutgoingDomains=intval($main->GET("RestrictToOutgoingDomains"));
    $RestrictToOutgoingDomainsLists=$main->GET('RestrictToOutgoingDomainsLists');
    $RestrictToOutgoingDomainsErrorMsg=$main->GET('RestrictToOutgoingDomainsErrorMsg');

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_section("{incoming2}");
    $form[]=$tpl->field_checkbox("RestrictToInternalDomains","{RestrictToInternalDomains}",$RestrictToInternalDomains,"RestrictToInternalDomainsLists","{RestrictToInternalDomains_text}");
    $form[]=$tpl->field_text("RestrictToInternalDomainsLists","{allowed}: {domains}",$RestrictToInternalDomainsLists,false);
    $form[]=$tpl->field_section("{outgoing}");
    $form[]=$tpl->field_checkbox("RestrictToOutgoingDomains","{RestrictToOutgoingDomains}",$RestrictToOutgoingDomains,"RestrictToOutgoingDomainsLists,RestrictToOutgoingDomainsErrorMsg","{RestrictToOutgoingDomains_text}");
    $form[]=$tpl->field_text("RestrictToOutgoingDomainsLists","{allowed}: {domains}",$RestrictToOutgoingDomainsLists,false);
    $form[]=$tpl->field_text("RestrictToOutgoingDomainsErrorMsg","{error}: {message}",$RestrictToOutgoingDomainsErrorMsg,false);

    echo $tpl->form_outside(null, $form,null,"{apply}",reconfigure_js($instance_id),"AsPostfixAdministrator");
    return true;
}
function section_mime_save(){
    $tpl=new template_admin();
    $instance_id=intval($_POST["instance_id"]);
    $main=new maincf_multi($_POST["instance_id"]);
    $main->SET_INFO("detect_8bit_encoding_header",$_POST["detect_8bit_encoding_header"]);
    $main->SET_INFO("disable_mime_input_processing",$_POST["disable_mime_input_processing"]);
    $main->SET_INFO("disable_mime_output_conversion",$_POST["disable_mime_output_conversion"]);


    $_POST["message_size_limit"]=$_POST["message_size_limit"]*1024;
    $_POST["message_size_limit"]=$_POST["message_size_limit"]*1000;
    $main->SET_INFO("message_size_limit",$_POST["message_size_limit"]);
    $sock=new sockets();
    $data=json_decode($sock->REST_API("/postfix/smtpd/mimes/$instance_id"));
    if(!$data->Status){
        echo $tpl->post_error($data->Error);
    }
}

function section_mime_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);
    $detect_8bit_encoding_header=$main->GET("detect_8bit_encoding_header");
    $disable_mime_input_processing=$main->GET("disable_mime_input_processing");
    $disable_mime_output_conversion=$main->GET("disable_mime_output_conversion");
    $mime_nesting_limit=$main->GET("mime_nesting_limit");
    $message_size_limit=intval($main->GET("message_size_limit"));
    if($message_size_limit==0){
        $message_size_limit=102400000;
    }
    $message_size_limit=$message_size_limit/1000;
    $message_size_limit=$message_size_limit/1024;

    if(!is_numeric($detect_8bit_encoding_header)){$detect_8bit_encoding_header=1;}
    if(!is_numeric($disable_mime_input_processing)){$disable_mime_input_processing=0;}
    if(!is_numeric($disable_mime_output_conversion)){$disable_mime_output_conversion=0;}
    if(!is_numeric($mime_nesting_limit)){$mime_nesting_limit=100;}
       $smtpd_recipient_limit=intval($main->GET("smtpd_recipient_limit"));
    if($smtpd_recipient_limit==0){$smtpd_recipient_limit=1000;}
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_checkbox("detect_8bit_encoding_header","{detect_8bit_encoding_header}",$detect_8bit_encoding_header,false,"{detect_8bit_encoding_header_text}");
    $form[]=$tpl->field_checkbox("disable_mime_output_conversion","{disable_mime_output_conversion}",$disable_mime_output_conversion,false,"{disable_mime_output_conversion_text}");
    $form[]=$tpl->field_checkbox("disable_mime_input_processing","{disable_mime_input_processing}",$disable_mime_input_processing,false,"{disable_mime_input_processing_text}");
    $form[]=$tpl->field_numeric("mime_nesting_limit","{mime_nesting_limit}",$mime_nesting_limit,"{mime_nesting_limit_text}");

    $form[]=$tpl->field_numeric("message_size_limit","{message_size_limit} (MB)",round($message_size_limit),"{message_size_limit_text}");
    $form[]=$tpl->field_numeric("smtpd_recipient_limit","{smtpd_recipient_limit}", "$smtpd_recipient_limit");
    echo $tpl->form_outside(null, $form,null,"{apply}","dialogInstance2.close();LoadAjax('postfix-table-flat','$page?table-flat=yes');","AsPostfixAdministrator");
    return true;
}
function section_safety_popup():bool{
    $tpl=new template_admin();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);

    $ip_verification[0]="{check_ip_before_sender_email}";
    $ip_verification[1]="{check_sender_email_before_ip}";
    $PostfixIPVerification=intval($main->GET("PostfixIPVerification"));
    $disable_vrfy_command=intval($main->GET("disable_vrfy_command"));
    $reject_unknown_client_hostname=intval($main->GET("reject_unknown_client_hostname"));
    $reject_unknown_reverse_client_hostname=intval($main->GET("reject_unknown_reverse_client_hostname"));
    $reject_unknown_sender_domain=intval($main->GET("reject_unknown_sender_domain"));
    $reject_invalid_hostname=intval($main->GET("reject_invalid_hostname"));
    $reject_non_fqdn_sender=intval($main->GET("reject_non_fqdn_sender"));
    $enforce_helo_restrictions=intval($main->GET("enforce_helo_restrictions"));
    $reject_forged_mails=intval($main->GET("reject_forged_mails"));
    $EnableGenericrDNSClients=$main->GET_INFO("EnableGenericrDNSClients");
    $EnablePostfixInternalDomainsCheck=intval($main->GET("EnablePostfixInternalDomainsCheck"));


    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_array_hash($ip_verification,"PostfixIPVerification","{ip_verification}",$PostfixIPVerification);
    $form[]=$tpl->field_checkbox("disable_vrfy_command","{disable_vrfy_command}",$disable_vrfy_command,false,"{disable_vrfy_command_text}");
    $form[]=$tpl->field_checkbox("reject_unknown_client_hostname","{reject_unknown_client_hostname}",$reject_unknown_client_hostname,false,"{reject_unknown_client_hostname_text}");
    $form[]=$tpl->field_checkbox("reject_unknown_reverse_client_hostname","{reject_unknown_reverse_client_hostname}",$reject_unknown_reverse_client_hostname,false,"{reject_unknown_reverse_client_hostname_text}");
    $form[]=$tpl->field_checkbox("reject_unknown_sender_domain","{reject_unknown_sender_domain}",$reject_unknown_sender_domain,false,"{reject_unknown_sender_domain_text}");
    $form[]=$tpl->field_checkbox("reject_invalid_hostname","{reject_invalid_hostname}",$reject_invalid_hostname,false,"{reject_invalid_hostname_text}");
    $form[]=$tpl->field_checkbox("reject_non_fqdn_sender","{reject_non_fqdn_sender}",$reject_non_fqdn_sender,false,"{reject_non_fqdn_sender_text}");
    $form[]=$tpl->field_checkbox("enforce_helo_restrictions","{enforce_helo_restrictions}",$enforce_helo_restrictions,false,"{enforce_helo_restrictions_text}");
    $form[]=$tpl->field_checkbox("reject_forged_mails","{reject_forged_mails}",$reject_forged_mails,false,"{reject_forged_mails_text}");
    $form[]=$tpl->field_checkbox("EnableGenericrDNSClients","{EnableGenericrDNSClients}",$EnableGenericrDNSClients,false,"{EnableGenericrDNSClients_text}");
    $form[]=$tpl->field_checkbox("EnablePostfixInternalDomainsCheck","{EnablePostfixInternalDomainsCheck}",$EnablePostfixInternalDomainsCheck,false,"{EnablePostfixInternalDomainsCheck_text}");

    echo $tpl->form_outside(null, $form,"{smtpd_client_restrictions_text}","{apply}",reconfigure_js($instance_id),"AsPostfixAdministrator");
    return true;
}

function section_headers_popup():bool{
    $tpl=new template_admin();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);

    $MyHostname=trim($main->GET("myhostname"));
    if($MyHostname==null){
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $main->SET("myhostname",$MyHostname);
    }

    $myorigin=trim($main->GET("myorigin"));
    if($myorigin==null){
        $myorigin="\$myhostname";
    }
    $smtp_helo_name=$main->GET('smtp_helo_name');
    $smtpd_banner=$main->GET('smtpd_banner');
    if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name";}


    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_section("{smtp_headers}/{protocol}");
    $form[]=$tpl->field_text("myhostname", "{myhostname}", $MyHostname,true,"{myhostname_text}");
    $form[]=$tpl->field_text("smtp_helo_name", "{smtp_helo_name}", $smtp_helo_name,false);
    $form[]=$tpl->field_text("smtpd_banner", "{SMTP_BANNER}", $smtpd_banner,true,"{SMTP_BANNER_TEXT}");
    $form[]=$tpl->field_text("myorigin", "{myorigin}", $myorigin,true,"{myorigin_text}");
    echo $tpl->form_outside(null, $form,null,"{apply}",reconfigure_js($instance_id),"AsPostfixAdministrator");
    return true;
}
function reconfigure_js($instance_id):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function="";
    if(isset($_GET["function"])){
        $function="{$_GET["function"]}();";
    }

    return "dialogInstance2.close();LoadAjax('postfix-table-flat','$page?table-flat=yes');$function".$tpl->framework_buildjs(
        "postfix.php?reconfigure=yes&instance-id=$instance_id",
        "POSTFIX_COMPILES.$instance_id",
        "POSTFIX_COMPILES.$instance_id.txt",
        "progress-postfix-mainconf"
    );
}

function table_flat():bool{
	$tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=GetInstanceID();
    $main=new maincf_multi($instance_id);
    $instancename="";
    if($instance_id==0){
        tokens_migration();
    }
	
	$MyHostname=trim($main->GET("myhostname")); 
	if($MyHostname==null){
		$MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
		$main->SET("myhostname",$MyHostname);
	}

    $myorigin=trim($main->GET("myorigin"));
    if($myorigin==null){
        $myorigin="\$myhostname";
    }

    $EnableGenericrDNSClients=$main->GET_INFO("EnableGenericrDNSClients");
	$reject_forged_mails=intval($main->GET("reject_forged_mails"));
	
	
	$EnablePostfixInternalDomainsCheck=intval($main->GET("EnablePostfixInternalDomainsCheck"));
	$RestrictToInternalDomains=intval($main->GET("RestrictToInternalDomains"));
    $RestrictToInternalDomainsLists=$main->GET('RestrictToInternalDomainsLists');
    $PostfixIPVerification=intval($main->GET("PostfixIPVerification"));
	
	$reject_unknown_client_hostname=intval($main->GET("reject_unknown_client_hostname"));
	$reject_unknown_reverse_client_hostname=intval($main->GET("reject_unknown_reverse_client_hostname"));
	$reject_unknown_sender_domain=intval($main->GET("reject_unknown_sender_domain"));
	$reject_invalid_hostname=intval($main->GET("reject_invalid_hostname"));
	$reject_non_fqdn_sender=intval($main->GET("reject_non_fqdn_sender"));
	$disable_vrfy_command=intval($main->GET("disable_vrfy_command"));
	$enforce_helo_restrictions=intval($main->GET("enforce_helo_restrictions"));
	$smtp_helo_name=$main->GET('smtp_helo_name');

    $PostfixEnableSubmission=intval($main->GET_INFO("PostfixEnableSubmission"));
    $PostfixEnforceSubmission=intval($main->GET_INFO("PostfixEnforceSubmission"));
    $PostfixEnableProxyProtocol=intval($main->GET_INFO("PostfixEnableProxyProtocol"));
    $RestrictToOutgoingDomains=intval($main->GET("RestrictToOutgoingDomains"));
    $RestrictToOutgoingDomainsLists=$main->GET('RestrictToOutgoingDomainsLists');
    //$RestrictToOutgoingDomainsErrorMsg=$main->GET('RestrictToOutgoingDomainsErrorMsg');

	$smtpd_banner=$main->GET('smtpd_banner');
	if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name";}


	$ip_verification[0]="{check_ip_before_sender_email}";
    $ip_verification[1]="{check_sender_email_before_ip}";

    $tpl->table_form_section("{smtp_headers}/{protocol}");
    $tpl->table_form_field_js("Loadjs('$page?section-headers=yes&instance-id=$instance_id')","AsPostfixAdministrator");

    $tpl->table_form_field_text("{myhostname}","$MyHostname, {myorigin} $myorigin",ico_server);
    if(strlen($smtp_helo_name)>2) {
        $tpl->table_form_field_text("{smtp_helo_name}", $smtp_helo_name,ico_server);
    }
    if(strlen($smtpd_banner)>2) {
        $tpl->table_form_field_text("{SMTP_BANNER}", $smtpd_banner,ico_options);
    }
    $tpl->table_form_section("{listen_ports}/{nics}");
    $tpl->table_form_field_js("Loadjs('$page?section-interfaces=yes&instance-id=$instance_id')","AsPostfixAdministrator");


    if($instance_id==0) {

        $PostfixBinInterfaces = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBinInterfaces"));
        if(strlen($PostfixBinInterfaces)==0) {
            $tpl->table_form_field_text("{nics}", "{all}", ico_nic);
        }else{
            $tpl->table_form_field_text("{nics}", $PostfixBinInterfaces, ico_nic);
        }
    }
    if($PostfixEnableSubmission==1){
        if($PostfixEnforceSubmission==0) {
            $tpl->table_form_field_bool("{PostfixEnableSubmission}", 1, ico_certificate);
        }else{
            $tpl->table_form_field_bool("{enforce_submission_port_encrypt}", 1, ico_certificate);
        }
    }else{
        $tpl->table_form_field_bool("{PostfixEnableSubmission}",0, ico_certificate);
    }
    $tpl->table_form_field_bool("{enable_smtp_haproxy}",$PostfixEnableProxyProtocol, ico_networks);

    $tpl->table_form_section("{safety_standards}","");
    $tpl->table_form_field_js("Loadjs('$page?section-safety=yes&instance-id=$instance_id')","AsPostfixAdministrator");


    $tpl->table_form_field_text("{ip_verification}", $ip_verification[$PostfixIPVerification], ico_nic);
    if($disable_vrfy_command==1){
        $tpl->table_form_field_bool("{disable_vrfy_command}",1, ico_check);
    }
    if($reject_unknown_client_hostname==1){
        $tpl->table_form_field_bool("{reject_unknown_client_hostname}",1, ico_check);
    }
    if($reject_unknown_reverse_client_hostname==1){
        $tpl->table_form_field_bool("{reject_unknown_reverse_client_hostname}",1, ico_check);
    }
    if($reject_unknown_sender_domain==1){
        $tpl->table_form_field_bool("{reject_unknown_sender_domain}",1, ico_check);
    }
    if($reject_invalid_hostname==1){
        $tpl->table_form_field_bool("{reject_invalid_hostname}",1, ico_check);
    }
    if($reject_non_fqdn_sender==1){
        $tpl->table_form_field_bool("{reject_non_fqdn_sender}",1, ico_check);
    }
    if($enforce_helo_restrictions==1){
        $tpl->table_form_field_bool("{enforce_helo_restrictions}",1, ico_check);
    }
    if($reject_forged_mails==1){
        $tpl->table_form_field_bool("{reject_forged_mails}",1, ico_check);
    }
    if($EnableGenericrDNSClients==1){
        $tpl->table_form_field_bool("{EnableGenericrDNSClients}",1, ico_check);
    }
    if($EnablePostfixInternalDomainsCheck==1){
        $tpl->table_form_field_bool("{EnablePostfixInternalDomainsCheck}",1, ico_check);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-outin=yes&instance-id=$instance_id')","AsPostfixAdministrator");
    if($RestrictToInternalDomains==1){
        $tpl->table_form_field_text("{RestrictToInternalDomains}", $RestrictToInternalDomainsLists, ico_earth);
    }else{
        $tpl->table_form_field_bool("{RestrictToInternalDomains}",0, ico_earth);
    }
    if($RestrictToOutgoingDomains==1){
        $tpl->table_form_field_text("{RestrictToOutgoingDomains}", $RestrictToOutgoingDomainsLists, ico_earth);
    }else{
        $tpl->table_form_field_bool("{RestrictToOutgoingDomains}",0, ico_earth);
    }

    $tpl->table_form_section("{smtp_authentication}");
    $tpl->table_form_field_js("Loadjs('$page?section-auth=yes&instance-id=$instance_id')","AsPostfixAdministrator");



    $smtp_sasl_security_options_class=new maincf_multi($instance_id);
    $smtp_sasl_security_options=unserialize($smtp_sasl_security_options_class->GET_BIGDATA("smtp_sasl_security_options"));
    if(!$smtp_sasl_security_options){
        $smtp_sasl_security_options=array();
    }
    if(!isset($smtp_sasl_security_options["noanonymous"])){$smtp_sasl_security_options["noanonymous"]=1;}
    $opts=array();

    if($smtp_sasl_security_options["noanonymous"]==1){
        $opts[]="{smtp_sasl_security_options_noanonymous}";
    }
    if($smtp_sasl_security_options["noplaintext"]==1){
        $opts[]="{smtp_sasl_security_options_noplaintext}";
    }
    if($smtp_sasl_security_options["nodictionary"]==1){
        $opts[]="{smtp_sasl_security_options_nodictionary}";
    }
    if($smtp_sasl_security_options["mutual_auth"]==1){
        $opts[]="{smtp_sasl_security_options_mutual_auth}";
    }
    $tpl->table_form_field_text("{method}","<small>". @implode(", ",$opts)."</small>", ico_users);

	//--------------------------------------------------------------------------------------------------------
	$detect_8bit_encoding_header=$main->GET("detect_8bit_encoding_header");
	$disable_mime_input_processing=$main->GET("disable_mime_input_processing");
	$disable_mime_output_conversion=$main->GET("disable_mime_output_conversion");
	$mime_nesting_limit=$main->GET("mime_nesting_limit");
    $message_size_limit=intval($main->GET("message_size_limit"));
    if($message_size_limit==0){
        $message_size_limit=102400000;
    }
    $message_size_limit=$message_size_limit/1000;
    $message_size_limit=FormatBytes($message_size_limit);


    if(!is_numeric($detect_8bit_encoding_header)){$detect_8bit_encoding_header=1;}
    if(!is_numeric($disable_mime_input_processing)){$disable_mime_input_processing=0;}
    if(!is_numeric($disable_mime_output_conversion)){$disable_mime_output_conversion=0;}
    if(!is_numeric($mime_nesting_limit)){$mime_nesting_limit=100;}
  $smtpd_recipient_limit=intval($main->GET("smtpd_recipient_limit"));
    if($smtpd_recipient_limit==0){$smtpd_recipient_limit=1000;}
    $enable_original_recipient=$main->GET("enable_original_recipient");
	$smtpd_discard_ehlo_keywords=$main->GET("smtpd_discard_ehlo_keywords");
	if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
	$undisclosed_recipients_header=$main->GET("undisclosed_recipients_header");
	if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}
	//--------------------------------------------------------------------------------------------------------
    $tpl->table_form_section("{MIME_OPTIONS}");
    $tpl->table_form_field_js("Loadjs('$page?section-mime=yes&instance-id=$instance_id')","AsPostfixAdministrator");

    if($detect_8bit_encoding_header==1){
        $tpl->table_form_field_bool("{detect_8bit_encoding_header}",1,ico_proto);
    }
    if($disable_mime_output_conversion==1){
        $tpl->table_form_field_bool("{disable_mime_output_conversion_text}",1,ico_proto);
    }
    if($disable_mime_input_processing==1){
        $tpl->table_form_field_bool("{disable_mime_input_processing_text}",1,ico_proto);
    }
    $tpl->table_form_field_text("{mime_nesting_limit}", "$mime_nesting_limit {messages}", ico_file);
    $tpl->table_form_field_text("{message_size_limit}", "$message_size_limit", ico_weight);
       $tpl->table_form_field_text("{smtpd_recipient_limit}", "$smtpd_recipient_limit",ico_users);
    $tpl->table_form_section("{other_settings}");
	//------------------------------------------------------------------------------------

//	$form[]=$tpl->field_section("{other_settings}");
    $tpl->table_form_field_js("Loadjs('$page?section-others=yes&instance-id=$instance_id')","AsPostfixAdministrator");

    if(strlen($undisclosed_recipients_header)>1){
        $tpl->table_form_field_text("{undisclosed_recipients_header}", "$undisclosed_recipients_header", ico_user);
    }
    if(strlen($smtpd_discard_ehlo_keywords)>1){
        $tpl->table_form_field_text("{smtpd_discard_ehlo_keywords}", "$smtpd_discard_ehlo_keywords", ico_proto);
    }
    $tpl->table_form_field_bool("{enable_original_recipient}",$enable_original_recipient,ico_message);



    $final[]="<div id='postfix-smtpd-checktls'></div>";
    $final[]=$tpl->table_form_compile();
    echo @implode("\n",$final);

    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }
    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_POSTFIX} v$POSTFIX_VERSION <small>$instancename</small>";
    $TINY_ARRAY["ICO"]="fas fa-mail-bulk";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_TEXT}";
    $TINY_ARRAY["URL"]="instance-postffix-settings-$instance_id";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsrefresh=$tpl->RefreshInterval_js("postfix-smtpd-checktls",$page,"postfix-smtpd-checktls=yes");

    echo "<script>$jstiny;$jsrefresh</script>";
    return true;
}

function SMTPDCheckTLS():bool{
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postfix/smtpd/checktls"));

    $tls_widget=$tpl->widget_micro("gray-bg", ico_certificate, "TLS:25","{inactive2}");
    $ssl_widget=$tpl->widget_micro("gray-bg", ico_certificate, "SSL:465","{inactive2}");

    if(!property_exists($data,"Info")){
        echo "<table style='width:100%'><tr>
        <td style='width:50%'>$tls_widget</td>
        <td style='width:50%;padding-left:5px'>$ssl_widget</td>
        ";
        return false;
    }

    $IsTLS=$data->Info->IsTLS;
    $TlsChecks=$data->Info->TlsChecks;
    $IsSSL=$data->Info->IsSSL;
    $SSLCheck=$data->Info->SSLCheck;



    if($IsTLS) {
        if(!$TlsChecks) {
            $err=$data->Info->TLSError;
            $tls_widget = $tpl->widget_micro("red-bg", ico_certificate, "TLS:25",$err);
        }else{
            $tls_widget = $tpl->widget_micro("navy-bg", ico_certificate, "TLS:25","OK");
        }

    }
    if($IsSSL) {
        if(!$SSLCheck) {
            $err=$data->Info->SSLError;
            $ssl_widget = $tpl->widget_micro("red-bg", ico_certificate, "SSL:465",$err);
        }else{
            $ssl_widget = $tpl->widget_micro("navy-bg", ico_certificate, "SSL:465","OK");
        }
    }



    echo "<table style='width:100%'><tr>
        <td style='width:50%'>$tls_widget</td>
        <td style='width:50%;padding-left:5px'>$ssl_widget</td>
        ";
    return true;
}


function save(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance_id=$_POST["instance_id"];
    $main=new maincf_multi($_POST["instance_id"]);
	$MyHostname=trim($main->GET("myhostname"));
	$PostfixPostmaster=trim($main->GET("PostfixPostmaster"));
    $PostfixEnableSubmission=intval($main->GET("PostfixEnableSubmission"));
	$luser_relay=trim($main->GET("luser_relay"));




    if(isset($_POST["section_auth_popup"])) {
        unset($_POST["section_auth_popup"]);
        $pref = array("noanonymous", "noplaintext", "nodictionary", "mutual_auth");
        $GET_BIGDATA = array();
        foreach ($pref as $key) {
            $GET_BIGDATA[$key] = intval($_POST[$key]);
            unset($_POST[$key]);
        }
        $main->SET_BIGDATA("smtp_sasl_security_options", serialize($GET_BIGDATA));
    }
	$main->SAVE_POSTS();
	
	
	
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?postfix-others-values=yes&instance-id=$instance_id");
	if(isset($_POST["PostfixBinInterfaces"])) {
        $PostfixBinInterfaces = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBinInterfaces"));
        if ($_POST["PostfixBinInterfaces"] <> $PostfixBinInterfaces) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixBinInterfaces", $_POST["PostfixBinInterfaces"]);
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?postfix-interfaces=yes");
        }
    }

    if(isset($_POST["PostfixEnableSubmission"])) {
        if ($_POST["PostfixEnableSubmission"] <> $PostfixEnableSubmission) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?artica-filter-reload=yes&instance-id=$instance_id");
        }
    }
    if(isset($_POST["myhostname"])) {
        if ($MyHostname <> $_POST["myhostname"]) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("postfix.php?myhostname=yes&instance-id=$instance_id");
        }
    }

    if(isset($_POST["PostfixPostmaster"])) {
        if ($PostfixPostmaster <> $_POST["PostfixPostmaster"]) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?postfix-hash-aliases=yes&instance-id=$instance_id");
        }
    }
    if(isset($_POST["luser_relay"])) {
        if ($luser_relay <> $_POST["luser_relay"]) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?postfix-luser-relay=yes&instance-id=$instance_id");
        }
    }

    if(isset($_POST["PostfixQueueEnabled"])) {
        $main->SET_INFO("PostfixQueueEnabled", $_POST["PostfixQueueEnabled"]);
    }
    if(isset($_POST["PostfixQueueMaxMails"])) {
        $main->SET_INFO("PostfixQueueMaxMails", $_POST["PostfixQueueMaxMails"]);
    }
    $reboot=false;

	foreach ($_POST as $key=>$val){
		$oldvalue=trim($main->GET($key));
		if($oldvalue<>$val){$main->SET_INFO($key, $val);$reboot=true;}
	}
	if($reboot){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?postfix-smtpd-restrictions=yes&instance-id=$instance_id");
		
	}
	
}

