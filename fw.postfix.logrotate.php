<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["myhostname"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{events_storage}</h1>
	<p>{APP_POSTFIX_TEXT}</p>
	</div>

	</div>
	<div class='row'><div id='progress-postfix-mainconf'></div>
		<div class='ibox-content'>
			<div id='table-loader-postfix-service'></div>
		</div>
	</div>
	<script>
	
	$.address.state('/');
	$.address.value('/postfix-settings');
	
	LoadAjax('table-loader-postfix-service','$page?tabs=yes');
	</script>";
	
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
	$sock=new sockets();
	$users=new usersMenus();
	$array["{parameters}"]="$page?table=yes";
	$array["TLS/SSL"]="fw.postfix.tls.php";
	//smtp_tls_security_level
	
	///$array["{listen_ports}"]="fw.postfix.ports.php";
	echo $tpl->tabs_default($array);
}

function table(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$main=new maincf_multi("master","master");
	
	$MyHostname=trim($main->GET("myhostname")); 
	if($MyHostname==null){
		$MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
		$main->SET("myhostname",$MyHostname);
	}
	
	$PostfixPostmaster=trim($main->GET("PostfixPostmaster")); 
	$luser_relay=trim($main->GET("luser_relay")); 
	
	$EnablePostfixAntispamPack_value=intval($main->GET("EnablePostfixAntispamPack"));
	$EnableGenericrDNSClients=$sock->GET_INFO("EnableGenericrDNSClients");
	$reject_forged_mails=intval($main->GET("reject_forged_mails"));
	
	
	$EnablePostfixInternalDomainsCheck=intval($main->GET("EnablePostfixInternalDomainsCheck"));
	$RestrictToInternalDomains=intval($main->GET("RestrictToInternalDomains"));
	
	$reject_unknown_client_hostname=intval($main->GET("reject_unknown_client_hostname"));
	$reject_unknown_reverse_client_hostname=intval($main->GET("reject_unknown_reverse_client_hostname"));
	$reject_unknown_sender_domain=intval($main->GET("reject_unknown_sender_domain"));
	$reject_invalid_hostname=intval($main->GET("reject_invalid_hostname"));
	$reject_non_fqdn_sender=intval($main->GET("reject_non_fqdn_sender"));
	$disable_vrfy_command=intval($main->GET("disable_vrfy_command"));
	$enforce_helo_restrictions=intval($main->GET("enforce_helo_restrictions"));
	
	$smtpd_banner=$main->GET('smtpd_banner');
	if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name";}
	

	
	
	
	
	$form[]=$tpl->field_text("myhostname", "{myhostname}", $MyHostname,true,"{myhostname_text}");
	$form[]=$tpl->field_text("smtpd_banner", "{SMTP_BANNER}", $smtpd_banner,true,"{SMTP_BANNER_TEXT}");
	
	$form[]=$tpl->field_section("{safety_standards}","{smtpd_client_restrictions_text}");
	
	$form[]=$tpl->field_checkbox("disable_vrfy_command","{disable_vrfy_command}",$disable_vrfy_command,false,"{disable_vrfy_command_text}");
	$form[]=$tpl->field_checkbox("reject_unknown_client_hostname","{reject_unknown_client_hostname}",$reject_unknown_client_hostname,false,"{reject_unknown_client_hostname_text}");
	$form[]=$tpl->field_checkbox("reject_unknown_reverse_client_hostname","{reject_unknown_reverse_client_hostname}",$reject_unknown_reverse_client_hostname,false,"{reject_unknown_reverse_client_hostname_text}");
	$form[]=$tpl->field_checkbox("reject_unknown_sender_domain","{reject_unknown_sender_domain}",$reject_unknown_sender_domain,false,"{reject_unknown_sender_domain_text}");
	$form[]=$tpl->field_checkbox("reject_invalid_hostname","{reject_invalid_hostname}",$reject_invalid_hostname,false,"{reject_invalid_hostname_text}");
	$form[]=$tpl->field_checkbox("reject_non_fqdn_sender","{reject_non_fqdn_sender}",$reject_non_fqdn_sender,false,"{reject_non_fqdn_sender_text}");
	$form[]=$tpl->field_checkbox("enforce_helo_restrictions","{reject_non_fqdn_sender}",$enforce_helo_restrictions,false,"{enforce_helo_restrictions_text}");
	$form[]=$tpl->field_checkbox("reject_forged_mails","{reject_forged_mails}",$enforce_helo_restrictions,false,"{reject_forged_mails_text}");
	$form[]=$tpl->field_checkbox("EnablePostfixAntispamPack","{EnablePostfixAntispamPack}",$EnablePostfixAntispamPack_value,false,"{EnablePostfixAntispamPack_text}");
	$form[]=$tpl->field_checkbox("EnableGenericrDNSClients","{EnableGenericrDNSClients}",$EnableGenericrDNSClients,false,"{EnableGenericrDNSClients_text}");
	$form[]=$tpl->field_checkbox("EnablePostfixInternalDomainsCheck","{EnablePostfixInternalDomainsCheck}",$EnablePostfixInternalDomainsCheck,false,"{EnablePostfixInternalDomainsCheck_text}");
	$form[]=$tpl->field_checkbox("RestrictToInternalDomains","{RestrictToInternalDomains}",$RestrictToInternalDomains,false,"{RestrictToInternalDomains_text}");
	
	
	$form[]=$tpl->field_section("Postmaster");
	$form[]=$tpl->field_email("PostfixPostmaster", "{postmaster}", $PostfixPostmaster,false,"{postmaster_text}");
	$form[]=$tpl->field_email("luser_relay","{unknown_users}",$luser_relay,false,"{postfix_unknown_users_tinytext}");
	
			
			
	
	//--------------------------------------------------------------------------------------------------------------
	
	
	$detect_8bit_encoding_header=$main->GET("detect_8bit_encoding_header");
	$disable_mime_input_processing=$main->GET("disable_mime_input_processing");
	$disable_mime_output_conversion=$main->GET("disable_mime_output_conversion");
	$mime_nesting_limit=$main->GET("mime_nesting_limit");
	$enable_original_recipient=$main->GET("enable_original_recipient");
	$smtpd_discard_ehlo_keywords=$main->GET("smtpd_discard_ehlo_keywords");
	if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
	$undisclosed_recipients_header=$main->GET("undisclosed_recipients_header");
	if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}
	
	

	//--------------------------------------------------------------------------------------------------------------
	$form[]=$tpl->field_section("{MIME_OPTIONS}");
	if(!is_numeric($detect_8bit_encoding_header)){$detect_8bit_encoding_header=1;}
	if(!is_numeric($disable_mime_input_processing)){$disable_mime_input_processing=0;}
	if(!is_numeric($disable_mime_output_conversion)){$disable_mime_output_conversion=0;}
	if(!is_numeric($mime_nesting_limit)){$mime_nesting_limit=100;}
	
	$form[]=$tpl->field_checkbox("detect_8bit_encoding_header","{detect_8bit_encoding_header}",$detect_8bit_encoding_header,false,"{detect_8bit_encoding_header_text}");
	$form[]=$tpl->field_checkbox("disable_mime_output_conversion","{disable_mime_output_conversion}",$disable_mime_output_conversion,false,"{disable_mime_output_conversion_text}");
	$form[]=$tpl->field_checkbox("disable_mime_input_processing","{disable_mime_input_processing}",$disable_mime_input_processing,false,"{disable_mime_input_processing_text}");
	$form[]=$tpl->field_numeric("mime_nesting_limit","{mime_nesting_limit}",$mime_nesting_limit,"{mime_nesting_limit_text}");
	//--------------------------------------------------------------------------------------------------------------

	$form[]=$tpl->field_section("{other_settings}");
	$form[]=$tpl->field_text("undisclosed_recipients_header", "{undisclosed_recipients_header}", $undisclosed_recipients_header,true,"{undisclosed_recipients_header_text}");
	$form[]=$tpl->field_text("smtpd_discard_ehlo_keywords", "{smtpd_discard_ehlo_keywords}", $smtpd_discard_ehlo_keywords,false,"{smtpd_discard_ehlo_keywords_explain}");
	$form[]=$tpl->field_checkbox("enable_original_recipient","{enable_original_recipient}",$enable_original_recipient,false,"{enable_original_recipient_text}");
	
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/POSTFIX_COMPILES";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/POSTFIX_COMPILES.txt";
	$ARRAY["CMD"]="postfix.php?reconfigure=yes";
	$ARRAY["TITLE"]="{reconfigure}";
	$ARRAY["AFTER"]="";
	$prgress=base64_encode(serialize($ARRAY));
	$reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-mainconf')";
	
	
	
	echo $tpl->form_outside("{general_settings}", $form,null,"{apply}",$reconfigure,"AsPostfixAdministrator");
	
		
	//
	
	
			
	
	
	
}


function save(){
	$main=new maincf_multi();
	$tpl=new template_admin();
	$MyHostname=trim($main->GET("myhostname"));
	$PostfixPostmaster=trim($main->GET("PostfixPostmaster"));
	$luser_relay=trim($main->GET("luser_relay"));
	
	$tpl->CLEAN_POST();
	$main->SAVE_POSTS();
	
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-others-values=yes&hostname=master");
	
	
	
	if($MyHostname<>$_POST["myhostname"]){
		$sock->getFrameWork("postfix.php?myhostname=yes");
		
	}
	if($PostfixPostmaster<>$_POST["PostfixPostmaster"]){
		$sock->getFrameWork("cmd.php?postfix-hash-aliases=yes");
	}
	if($luser_relay<>$_POST["luser_relay"]){
		$sock->getFrameWork("cmd.php?postfix-luser-relay=yes");
	}	
	
	
	
	
	

	foreach ($_POST as $key=>$val){
		$oldvalue=trim($main->GET("$key"));
		if($oldvalue<>$val){$sock->SET_INFO($key, $val);$reboot=true;}
	}
	if($reboot){
		$sock->getFrameWork("cmd.php?postfix-smtpd-restrictions=yes");
		
	}
	
}

