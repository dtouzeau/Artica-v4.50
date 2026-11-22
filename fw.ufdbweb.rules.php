<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.smtp.inc");
// awc_table_style() / awc_pager_js() : kit des tableaux d'entites. Inclusion au premier niveau,
// jamais sous condition (sinon « Call to undefined function awc_table_style() »).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

// Pentest 2026-11-04 : ruleid-js/rule-popup/rule-options/skin/smtp-messages/smtp-sendto/smtp_sendto etaient recopies sans encodage dans la page (XSS).
// Les regles sont identifiees par un md5 (meme alphabet que delete-rule-js) ; les destinataires de test sont des adresses e-mail.
foreach(array("ruleid-js","rule-popup","rule-options","skin","smtp-messages") as $k){if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET[$k]);}}
foreach(array("smtp-sendto","smtp_sendto") as $k){if(isset($_GET[$k])){$_GET[$k]=trim((string)$_GET[$k]);if(!filter_var($_GET[$k],FILTER_VALIDATE_EMAIL)){$_GET[$k]="";}}}
if(isset($_GET["switch-enabled"])){switch_enabled();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["sync"])){sync();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["move-rule"])){rule_move();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-options"])){rule_options();exit;}
if(isset($_POST["rule-options"])){rule_options_save();exit;}
if(isset($_GET["smtp-js"])){smtp_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_POST["smtp-messages"])){smtp_messages_save();exit;}

if(isset($_GET["smtp-popup"])){smtp_parameters();exit;}
if(isset($_POST["smtp_server_name"])){smtp_parameters_save();exit;}
if(isset($_GET["skin"])){rule_skin();exit;}
if(isset($_POST["UfdbGuardHTTPDisableHostname"])){rule_skin_save();exit;}
if(isset($_GET["test-smtp-js"])){smtp_parameters_test();exit;}
if(isset($_GET["smtp-sendto"])){smtp_parameters_lauch();exit;}
if(isset($_GET["smtp_sendto"])){tests_smtp();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["smtp-messages"])){smtp_messages();exit;}
page();

function sync(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!ufdbweb_is_admin()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return;}
    $err=bpr_compile();
    if($err!==""){echo $tpl->js_error($err);return;}
    admin_tracks("Apply the Web error page rules");
    $tpl->js_ok("{success}");
}
function page(){
	$page=CurrentPageName();
	$error=null;

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{banned_page_webservice} &raquo; &raquo; {rules}</h1>
	<p>{APP_UFDB_HTTP_EXPLAIN}</p>$error
	</div>
	
	</div>



	<div class='row'><div id='progress-weberrorules-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-webhttp-rules'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-webhttp-rules','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function table2(){
	$page=CurrentPageName();
	echo "<div id='table-loader-webhttp-rules' style='margin-top:10px'></div><script>
	LoadAjax('table-loader-webhttp-rules','$page?table=yes');

	</script>";
	
}
function smtp_parameters_test(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{give_your_email_address}");
	header("content-type: application/x-javascript");
	
	
	
	$t=time();
	echo "
	var xStart$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	}
	
	
	function Start$t(){
	var email=prompt('$title');
	if(!email){return;}
	Loadjs('$page?smtp-sendto='+email);
	}
	Start$t();";	
	
	
}
function smtp_parameters_lauch(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$email=$_GET["smtp-sendto"];
	$tpl->js_dialog2(req_html(req_html($email)), "$page?smtp_sendto=".req_url($email));
	
}
function rule_delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$aclid=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET["delete-rule-js"]);
	$ruleData=acls_api_get("/webfilter/page-rules/".urlencode($aclid));
	$ruleName=is_array($ruleData)?trim((string)($ruleData["ruleName"] ?? "")):"";
	if($ruleName===""){$ruleName=$aclid;}
	$text="<strong style='font-size:16px'>".bpr_h(bpr_t("bpr_delete_title",$ruleName))."</strong><br><br>".bpr_t("bpr_delete_text");
	$tpl->js_confirm_delete($text, "delete-rule", $aclid,"LoadAjaxSilent('table-loader-webhttp-rules','$page?table=yes');",true);
}
// Masquer le bouton n'est pas un controle : les gestionnaires qui ecrivent le verifient eux-memes.
function ufdbweb_is_admin():bool{
	$users=new usersMenus();
	return (bool)$users->AsDansGuardianAdministrator;
}
function rule_delete():bool{
	if(!ufdbweb_is_admin()){$tpl=new template_admin();echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return false;}
	$ID=preg_replace('/[^A-Za-z0-9]/','',(string)$_POST["delete-rule"]);
	list($ok,$errData)=acls_api_send("DELETE","/webfilter/page-rules/".urlencode($ID));
	if(!$ok){
        $tpl=new template_admin();
        $tpl->js_mysql_alert(is_string($errData)?$errData:"API error");
        return false;
    }
	bpr_forget($ID);
    $err=bpr_compile();
    if($err!==""){echo bpr_h($err);return false;}
    return admin_tracks("Removed Proxy Web error page rule #$ID");
}
function smtp_messages():bool{
    $md5=$_GET["smtp-messages"];
    $tpl=new template_admin();
    $ligne=acls_api_get("/webfilter/page-rules/".urlencode($md5));
    if(!is_array($ligne)){$ligne=[];}
    $form[]=$tpl->field_hidden("smtp-messages", $md5);

    if(strlen($ligne["smtp_ticket1_subj"])<3){
        $ligne["smtp_ticket1_subj"]="New ticket: Claim of a blocked user on %domain";
    }
    if(strlen($ligne["smtp_ticket1_body"])<3){
        $def[]="Hi admin,";
        $def[]="The user has clicked on the error page button to inform you that he has been blocked in his navigation for the following reasons:";
        $def[]="Hostname: %host";
        $def[]="Artica Version: %ver";
        $def[]="Member: %user";
        $def[]="Policy: %policy";
        $def[]="URL: %uri";
        $def[]="";
        $def[]="";
        $def[]="To accept and release the blocked website, please click on the link bellow:";
        $def[]="%release";
        $def[]="";
        $ligne["smtp_ticket1_body"]=@implode("\n",$def);
    }

    $form[]=$tpl->field_text("smtp_ticket1_subj", "{ticket}:{subject}", $ligne["smtp_ticket1_subj"]);
    $form[]=$tpl->field_textarea("smtp_ticket1_body", "{ticket}:{body}", $ligne["smtp_ticket1_body"]);
    echo $tpl->form_outside("", $form,null,"{apply}","blur()","AsDansGuardianAdministrator");
    return true;
}
function smtp_messages_save():bool{
    $tpl=new template_admin();
    if(!ufdbweb_is_admin()){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $md5=$_POST["smtp-messages"];
    $smtp_ticket1_subj=$_POST["smtp_ticket1_subj"] ?? "";
    $smtp_ticket1_body=$_POST["smtp_ticket1_body"] ?? "";
    list($ok,$errData)=acls_api_send("PUT","/webfilter/page-rules/".urlencode($md5),[
        "smtp_ticket1_subj"=>$smtp_ticket1_subj,
        "smtp_ticket1_body"=>$smtp_ticket1_body,
    ]);
    if(!$ok){
        echo $tpl->popup_error(is_string($errData)?htmlspecialchars($errData):"API error");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return admin_tracks("Updated Proxy Web error page rule #$md5 for SMTP message $smtp_ticket1_subj");

}



function rule_id_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["ruleid-js"];
	$title="{new_rule}";
	if($md5=="0"){$md5=null;}
	
	if($md5<>null){
		$mem=new lib_memcached();
		$value=$mem->getKey("ufdb_page_rules_$md5");
		if($mem->MemCachedFound){
			VERBOSE("rule_id_js `$md5` HIT", __LINE__);
			$ligne=unserialize($value);
		}else{
			$ligne=acls_api_get("/webfilter/page-rules/".urlencode($md5));
			if(!is_array($ligne)){$ligne=[];}
			$mem->saveKey("ufdb_page_rules_$md5", serialize($ligne));
		}

		$ruleName=$ligne["ruleName"] ?? $ligne["rulename"] ?? "";
		$title="{rule}: $ruleName";
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$md5");
}
function smtp_js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{smtp_parameters}");
	$tpl->js_dialog($title,"$page?smtp-popup=yes");
}
function smtp_parameters(){
	$page=CurrentPageName();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	for($i=1;$i<25;$i++){
		$MaxError[$i]="$i {times}";
	}
	
	$SquidGuardWebSMTP=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebSMTP"));
	if(!is_numeric($SquidGuardWebSMTP["MaxError"])){$SquidGuardWebSMTP["MaxError"]=5;}
	if(intval($SquidGuardWebSMTP["smtp_server_port"])==0){$SquidGuardWebSMTP["smtp_server_port"]=25;}
	$title="{smtp_parameters}";
	$form[]=$tpl->field_text("smtp_server_name", "{smtp_server_name}", $SquidGuardWebSMTP["smtp_server_name"]);
	$form[]=$tpl->field_numeric("smtp_server_port", "{smtp_server_port}", $SquidGuardWebSMTP["smtp_server_port"]);
	$form[]=$tpl->field_email("smtp_sender", "{smtp_sender}", $SquidGuardWebSMTP["smtp_sender"]);
	$form[]=$tpl->field_email("smtp_recipient", "{smtp_recipient}", $SquidGuardWebSMTP["smtp_recipient"]);
	$form[]=$tpl->field_text("smtp_auth_user", "{smtp_auth_user}", $SquidGuardWebSMTP["smtp_auth_user"]);
	$form[]=$tpl->field_password("smtp_auth_passwd", "{smtp_auth_passwd}", $SquidGuardWebSMTP["smtp_auth_passwd"]);
	$form[]=$tpl->field_checkbox("tls_enabled","{tls_enabled}",$SquidGuardWebSMTP["tls_enabled"]);
	$form[]=$tpl->field_array_hash($MaxError, "MaxError", "{retry}", $SquidGuardWebSMTP["MaxError"]);
	
	$tpl->form_add_button("SMTP:{test}", "Loadjs('$page?test-smtp-js=yes')");
	
	echo $tpl->form_outside($title,@implode("\n", $form),null,"{apply}","blur()","AsDansGuardianAdministrator");

}

function smtp_parameters_save(){
	$tpl=new template_admin();
    if(!ufdbweb_is_admin()){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}
	$tpl->CLEAN_POST();
	$SquidGuardWebSMTP=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebSMTP"));
	
	foreach ($_POST as $num=>$ligne){
		$SquidGuardWebSMTP[$num]=$tpl->utf8_encode($ligne);
	
	}
	$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($SquidGuardWebSMTP)), "SquidGuardWebSMTP");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
	
}
function acl_rule_name($ID){
    $ruleData=acls_api_get("/proxy/acls/sqlite/rule/".intval($ID));
    return is_array($ruleData)?($ruleData["aclname"] ?? ""):"";
}

function rule_options(){
	$md5=$_GET["rule-options"];
	if(strlen($md5)<10){$md5=null;}
	$page=CurrentPageName();
	$tpl=new template_admin();
    VERBOSE("MD5:$md5",__LINE__);
	$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableUfdbGuard'));
	$ligne=array();
	$ligne["enabled"]=1;
	$ligne["zorder"]=1;

	$LANGS=bpr_langs();

    // Build ACLS_RULES dropdown: fetch all ACL rules and filter by ENABLE_ERROR_PAGE in zTemplate
    $allAclOrders=acls_api_get("/proxy/acls/sqlite/rules/orders");
    $ACLS_RULES=array();
    if(is_array($allAclOrders)){
        foreach($allAclOrders as $orderRow){
            $ruleID=intval($orderRow["id"] ?? 0);
            if($ruleID===0){continue;}
            $ruleData=acls_api_get("/proxy/acls/sqlite/rule/$ruleID");
            if(!is_array($ruleData)){continue;}
            $zTemplate=unserializeb64($ruleData["zTemplate"] ?? "");
            if(!isset($zTemplate["ENABLE_ERROR_PAGE"])){continue;}
            if(intval($zTemplate["ENABLE_ERROR_PAGE"])==0){continue;}
            $ACLS_RULES[$ruleID]=$ruleData["aclname"] ?? "";
        }
    }



	$BootstrapDialog="BootstrapDialog1.close();LoadAjax('table-loader-webhttp-rules','$page?table=yes');";

	if($md5<>null){
        VERBOSE("rule_options md5=$md5",__LINE__);
		$apiRow=acls_api_get("/webfilter/page-rules/".urlencode($md5));
		if(!is_array($apiRow)){$apiRow=[];}
		// Normalize camelCase API response to lowercase column names used by the form
		$ligne=array(
			"zmd5"=>$apiRow["zmd5"] ?? $md5,
			"rulename"=>$apiRow["ruleName"] ?? $apiRow["rulename"] ?? "",
			"enabled"=>$apiRow["enabled"] ?? 1,
			"zorder"=>$apiRow["xOrder"] ?? $apiRow["zorder"] ?? 1,
			"ticket"=>$apiRow["ticket"] ?? 0,
			"ticket2"=>$apiRow["ticket2"] ?? 0,
			"category"=>$apiRow["category"] ?? 0,
			"deny"=>$apiRow["deny"] ?? 0,
			"allow"=>$apiRow["allow"] ?? 0,
			"adgroup"=>$apiRow["adgroup"] ?? "",
			"noauth"=>$apiRow["noauth"] ?? 0,
			"notify"=>$apiRow["notify"] ?? 0,
			"maxtime"=>$apiRow["maxtime"] ?? 0,
			"infinite"=>$apiRow["infinite"] ?? 0,
			"addTocat"=>$apiRow["addTocat"] ?? "",
			"templateid"=>$apiRow["templateID"] ?? $apiRow["templateid"] ?? 0,
			"network"=>$apiRow["network"] ?? "0.0.0.0/0",
			"webruleid"=>$apiRow["webruleID"] ?? $apiRow["webruleid"] ?? 0,
			"lang"=>$apiRow["lang"] ?? "",
			"aclid"=>$apiRow["aclid"] ?? 0,
			"recipients"=>$apiRow["recipients"] ?? "",
			"UNBLOCK_PARAGRAPH"=>$apiRow["UNBLOCK_PARAGRAPH"] ?? "",
			"postapi"=>$apiRow["postapi"] ?? 0,
			"postapiurl"=>$apiRow["postapiurl"] ?? "",
			"smtp_ticket1_subj"=>$apiRow["smtp_ticket1_subj"] ?? "",
			"smtp_ticket1_body"=>$apiRow["smtp_ticket1_body"] ?? "",
			"username"=>$apiRow["username"] ?? "",
			"UfdbGuardHTTPNoVersion"=>$apiRow["UfdbGuardHTTPNoVersion"] ?? 0,
			"UfdbGuardHTTPEnablePostmaster"=>$apiRow["UfdbGuardHTTPEnablePostmaster"] ?? 1,
		);
		$btname="{apply}";
		$title="{rule}: {$ligne["rulename"]}";
		$BootstrapDialog="LoadAjax('table-loader-webhttp-rules','$page?table=yes');";
	}else{
		$title="{new_rule}";
		$btname="{add}";
		$ligne["deny"]=1;
		$ligne["network"]='0.0.0.0/0';
	}

	if(!isset($ligne["zorder"])){$ligne["zorder"]=1;}
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	$tpl->field_hidden("rule-options", $md5);
	$tpl->field_hidden("noauth", 0);
	$tpl->field_hidden("infinite", 0);
	$tpl->field_hidden("UfdbGuardHTTPNoVersion",intval($ligne["UfdbGuardHTTPNoVersion"]));
	$tpl->field_hidden("UfdbGuardHTTPEnablePostmaster",intval($ligne["UfdbGuardHTTPEnablePostmaster"]));
	$form[]=$tpl->field_section("{bpr_s_name}");
	$form[]=$tpl->field_text("rulename","{bpr_f_name}",$ligne["rulename"],true,"{bpr_f_name_help}");
	$form[]=$tpl->field_checkbox("enabled","{bpr_f_enabled}",$ligne["enabled"],false,"{bpr_f_enabled_help}");
	$form[]=$tpl->field_numeric("zorder","{bpr_f_order}",$ligne["zorder"],"{bpr_f_order_help}");
	$form[]=$tpl->field_templates("templateid","{bpr_f_design}",$ligne["templateid"],"{bpr_f_design_help}");

	$form[]=$tpl->field_section("{bpr_s_who}","{bpr_s_who_help}");
	if($EnableUfdbGuard==1){
		$form[]=$tpl->field_webfiltering_rules("webruleid","{bpr_f_wfrule}",$ligne["webruleid"],"{bpr_f_wfrule_help}");
	}else{
		$tpl->field_hidden("webruleid", 0);
	}
	$form[]=$tpl->field_array_hash($ACLS_RULES,"aclid","{bpr_f_acl}",$ligne["aclid"],false,"{bpr_f_acl_help}");
	$form[]=$tpl->field_categories_list("category:ALL","{bpr_f_category}",intval($ligne["category"]),"{bpr_f_category_help}");
	$form[]=$tpl->field_cdir("network", "{bpr_f_network}", $ligne["network"],false,"{bpr_f_network_help}");
	$form[]=$tpl->field_text("username", "{bpr_f_users}", $ligne["username"],false,"{bpr_f_users_help}");
	$form[]=$tpl->field_array_hash($LANGS, "lang", "{bpr_f_lang}", $ligne["lang"],false,"{bpr_f_lang_help}");
	$form[]=$tpl->field_choose_adgroupDN("adgroup","{bpr_f_adgroup}",$ligne["adgroup"],"{bpr_f_adgroup_help}");

	$form[]=$tpl->field_section("{bpr_s_what}");
	$outcomes=array(0=>"{bpr_oc_explain}",1=>"{bpr_oc_unlock}",2=>"{bpr_oc_ask}",3=>"{bpr_oc_ask2}");
	$form[]=$tpl->field_array_hash($outcomes,"bpr_outcome","{bpr_f_outcome}",bpr_outcome_of($ligne),true,"{bpr_f_outcome_help}",null,false);
	$form[]=$tpl->field_choose_periods("maxtime","{bpr_f_maxtime}",intval($ligne["maxtime"]),"{bpr_f_maxtime_help}");
	$form[]=$tpl->field_text("recipients", "{bpr_f_recipients}", $ligne["recipients"],false,"{bpr_f_recipients_help}");
	$form[]=$tpl->field_checkbox("notify","{bpr_f_notify}",$ligne["notify"],false,"{bpr_f_notify_help}");
	if($EnableUfdbGuard==1) {
		if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePersonalCategories")) == 1) {
			$form[] = $tpl->field_personal_categories_list("addTocat", "{bpr_f_addcat}", $ligne["addTocat"],"{bpr_f_addcat_help}");
		} else {
			$tpl->field_hidden("addTocat", 0);
		}
	}

	$form[]=$tpl->field_section("{bpr_s_api}");
	$form[]=$tpl->field_checkbox("postapi","{bpr_f_postapi}",intval($ligne["postapi"]),false,"{bpr_f_postapi_help}");
	$form[]=$tpl->field_text("postapiurl","{bpr_f_posturl}",trim($ligne["postapiurl"]),false,"{bpr_f_posturl_help}");
	$jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/web-error/postapi',1024,768,'WIKI')";
	$form[]=$tpl->field_button("help","{bpr_how_it_works}",$jshelp);

	echo $tpl->form_outside($title,@implode("\n", $form),"{bpr_dialog_explain}",$btname,"$BootstrapDialog","AsDansGuardianAdministrator");
}




function rule_options_save(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["bpr_outcome"])){
        // Un seul comportement : le service lit allow avant ticket, deux cases cochees se contredisaient.
        foreach(bpr_outcome_fields(intval($_POST["bpr_outcome"])) as $k=>$v){$_POST[$k]=$v;}
        unset($_POST["bpr_outcome"]);
    }else{
        if(intval($_POST["allow"] ?? 0)==1){$_POST["deny"]=0;}else{$_POST["deny"]=1;}
    }
    if(intval($_POST["postapi"] ?? 0)==1 && !preg_match('#^https?://#i',trim((string)($_POST["postapiurl"] ?? "")))){
        echo $tpl->_ENGINE_parse_body("{bpr_err_posturl}");return;
    }
    $NullFields=array("addTocat","username","adgroup");
    foreach ($NullFields as $key){
        if(!isset($_POST[$key])){
            $_POST[$key]="";
        }
    }

	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

	$md5=trim($_POST["rule-options"] ?? "");
	if(strlen($md5)<10){$md5=null;}

    // Build the body from POST (exclude the sentinel key)
    $excludeKeys=["rule-options","noauth","infinite"];
    $body=[];
    foreach($_POST as $k=>$v){
        if(in_array($k,$excludeKeys)){continue;}
        $body[$k]=$v;
    }

	if($md5==null){
        list($ok,$data)=acls_api_send("POST","/webfilter/page-rules",$body);
        if(!$ok){echo htmlspecialchars(is_string($data)?$data:"API error");return;}
	}else{
        list($ok,$data)=acls_api_send("PUT","/webfilter/page-rules/".urlencode($md5),$body);
        if(!$ok){echo htmlspecialchars(is_string($data)?$data:"API error");return;}
	}
    // Invalidate memcache for this rule
    if($md5!==null){
        $mem=new lib_memcached();
        $mem->saveKey("ufdb_page_rules_l",false);
        $mem->Delkey("ufdb_page_rules_$md5");
    }
    $err=bpr_compile();
    if($err!==""){echo bpr_h($err);return;}
    admin_tracks(($md5==null?"Create":"Update")." Web error page rule ".($_POST["rulename"] ?? ""));
}

function rule_skin():bool{

	$tpl=new template_admin();
    $zmd5=$_GET["skin"];
	$apiRow=acls_api_get("/webfilter/page-rules/".urlencode($zmd5));
	if(!is_array($apiRow)){$apiRow=[];}
	// Normalize camelCase API keys to legacy lowercase column names used below
	$ligne=array(
		"UfdbGuardHTTPNoVersion"=>$apiRow["UfdbGuardHTTPNoVersion"] ?? 0,
		"UfdbGuardHTTPEnablePostmaster"=>$apiRow["UfdbGuardHTTPEnablePostmaster"] ?? 1,
		"UfdbGuardHTTPDisableHostname"=>$apiRow["UfdbGuardHTTPDisableHostname"] ?? 0,
		"REDIRECT_TEXT"=>$apiRow["REDIRECT_TEXT"] ?? null,
		"UFDBGUARD_TITLE_1"=>$apiRow["UFDBGUARD_TITLE_1"] ?? null,
		"UFDBGUARD_PARA1"=>$apiRow["UFDBGUARD_PARA1"] ?? null,
		"UFDBGUARD_TITLE_2"=>$apiRow["UFDBGUARD_TITLE_2"] ?? null,
		"UFDBGUARD_PARA2"=>$apiRow["UFDBGUARD_PARA2"] ?? null,
		"UFDBGUARD_SERVICENAME"=>$apiRow["UFDBGUARD_SERVICENAME"] ?? null,
		"UNBLOCK_PARAGRAPH"=>$apiRow["UNBLOCK_PARAGRAPH"] ?? null,
		"UFDBGUARD_PROXYNAME"=>$apiRow["UFDBGUARD_PROXYNAME"] ?? null,
		"UFDBGUARD_ADMIN"=>$apiRow["UFDBGUARD_ADMIN"] ?? null,
		"UFDBGUARD_LABELVER"=>$apiRow["UFDBGUARD_LABELVER"] ?? null,
		"UFDBGUARD_LABELPOL"=>$apiRow["UFDBGUARD_LABELPOL"] ?? null,
		"UFDBGUARD_LABELRQS"=>$apiRow["UFDBGUARD_LABELRQS"] ?? null,
		"UFDBGUARD_LABELMEMBER"=>$apiRow["UFDBGUARD_LABELMEMBER"] ?? null,
		"UFDBGUARD_UNLOCK_LINK"=>$apiRow["UFDBGUARD_UNLOCK_LINK"] ?? null,
		"UFDBGUARD_TICKET_LINK"=>$apiRow["UFDBGUARD_TICKET_LINK"] ?? null,
		"TICKET_TEXT"=>$apiRow["TICKET_TEXT"] ?? null,
		"TICKET_TEXT_SUCCESS"=>$apiRow["TICKET_TEXT_SUCCESS"] ?? null,
		"CONFIRM_TICKET_TEXT"=>$apiRow["CONFIRM_TICKET_TEXT"] ?? null,
		"CONFIRM_TICKET_BT"=>$apiRow["CONFIRM_TICKET_BT"] ?? null,
	);
	$UfdbGuardHTTPNoVersion=$ligne["UfdbGuardHTTPNoVersion"];
	$REDIRECT_TEXT=$ligne["REDIRECT_TEXT"];

	$UFDBGUARD_TITLE_1=$ligne["UFDBGUARD_TITLE_1"];
	$UFDBGUARD_PARA1=$ligne["UFDBGUARD_PARA1"];
	$UFDBGUARD_TITLE_2=$ligne["UFDBGUARD_TITLE_2"];
	$UFDBGUARD_PARA2=$ligne["UFDBGUARD_PARA2"];
	$UFDBGUARD_SERVICENAME=$ligne["UFDBGUARD_SERVICENAME"];
    $UNBLOCK_PARAGRAPH=$ligne["UNBLOCK_PARAGRAPH"];

	if($ligne["UFDBGUARD_PROXYNAME"]==null){$ligne["UFDBGUARD_PROXYNAME"]="Proxy hostname";}
	if($ligne["UFDBGUARD_ADMIN"]==null){$ligne["UFDBGUARD_ADMIN"]="Your administrator";}
	if($ligne["UFDBGUARD_LABELVER"]==null){$ligne["UFDBGUARD_LABELVER"]="Application Version";}
	if($ligne["UFDBGUARD_LABELPOL"]==null){$ligne["UFDBGUARD_LABELPOL"]="Policy";}
	if($ligne["UFDBGUARD_LABELRQS"]==null){$ligne["UFDBGUARD_LABELRQS"]="Request";}
	if($ligne["UFDBGUARD_LABELMEMBER"]==null){$ligne["UFDBGUARD_LABELMEMBER"]="Member";}

	$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
	$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	$UFDBGUARD_UNLOCK_LINK=$ligne["UFDBGUARD_UNLOCK_LINK"];
	$UFDBGUARD_TICKET_LINK=$ligne["UFDBGUARD_TICKET_LINK"];
	$TICKET_TEXT=$ligne["TICKET_TEXT"];
	$TICKET_TEXT_SUCCESS=$ligne["TICKET_TEXT_SUCCESS"];
	$CONFIRM_TICKET_TEXT=$ligne["CONFIRM_TICKET_TEXT"];
	$CONFIRM_TICKET_BT=$ligne["CONFIRM_TICKET_BT"];

	if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
	if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
	if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
	if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
	if($UFDBGUARD_SERVICENAME==null){$UFDBGUARD_SERVICENAME="{webfiltering}";}
	if($UFDBGUARD_UNLOCK_LINK==null){$UFDBGUARD_UNLOCK_LINK="{unlock}";}
	if($UFDBGUARD_TICKET_LINK==null){$UFDBGUARD_TICKET_LINK="{submit_a_ticket}";}
    if($UNBLOCK_PARAGRAPH==null){$UNBLOCK_PARAGRAPH="{UNBLOCK_PARAGRAPH}";}
	if(!is_numeric($UfdbGuardHTTPEnablePostmaster)){$UfdbGuardHTTPEnablePostmaster=1;}

	if($TICKET_TEXT==null){$TICKET_TEXT="{ufdb_ticket_text}";}
	if($TICKET_TEXT_SUCCESS==null){$TICKET_TEXT_SUCCESS="{ufdb_ticket_text_success}";}
	if($REDIRECT_TEXT==null){$REDIRECT_TEXT="{WAIT_REDIRECT_TEXT}";}
	if($CONFIRM_TICKET_TEXT==null){$CONFIRM_TICKET_TEXT="{CONFIRM_TICKET_PG_TEXT}";}
	if($CONFIRM_TICKET_BT==null){$CONFIRM_TICKET_BT="{CONFIRM_TICKET_BT_TEXT}";}



	
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$html[]="<div class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</div>";
	}
	
	$title="{skin}";
	$tpl->field_hidden("rule-skin", $zmd5);
	
	
	$form[]=$tpl->field_checkbox("UfdbGuardHTTPDisableHostname","{remove_proxy_hostname}",$UfdbGuardHTTPDisableHostname,false);
	$form[]=$tpl->field_checkbox("UfdbGuardHTTPNoVersion","{remove_artica_version}",$UfdbGuardHTTPNoVersion);
	$form[]=$tpl->field_checkbox("UfdbGuardHTTPEnablePostmaster","{add_webmaster}",$UfdbGuardHTTPEnablePostmaster);
	
	
	$form[]=$tpl->field_text("UFDBGUARD_SERVICENAME","{service_name}",utf8_decode_switch($UFDBGUARD_SERVICENAME));
	$form[]=$tpl->field_text("UFDBGUARD_PROXYNAME","{label} {proxyname}",utf8_decode_switch($ligne["UFDBGUARD_PROXYNAME"]));
	
	$form[]=$tpl->field_text("UFDBGUARD_LABELMEMBER","{label} {member}",utf8_decode_switch($ligne["UFDBGUARD_LABELMEMBER"]));
	$form[]=$tpl->field_text("UFDBGUARD_ADMIN","{label} {administrator}",utf8_decode_switch($ligne["UFDBGUARD_ADMIN"]));
	$form[]=$tpl->field_text("UFDBGUARD_LABELVER","{label} {version}",utf8_decode_switch($ligne["UFDBGUARD_LABELVER"]));
	$form[]=$tpl->field_text("UFDBGUARD_LABELPOL","{label} {policy}",utf8_decode_switch($ligne["UFDBGUARD_LABELPOL"]));
	$form[]=$tpl->field_text("UFDBGUARD_LABELRQS","{label} {request}",utf8_decode_switch($ligne["UFDBGUARD_LABELRQS"]));
	
	$form[]=$tpl->field_text("UFDBGUARD_TITLE_1","{titletext} 1",utf8_decode_switch($UFDBGUARD_TITLE_1));
	$form[]=$tpl->field_textareaP("UFDBGUARD_PARA1","{parapgraph} 1",utf8_decode_switch($UFDBGUARD_PARA1));
	$form[]=$tpl->field_text("UFDBGUARD_TITLE_2","{titletext} 2",utf8_decode_switch($UFDBGUARD_TITLE_2));
	$form[]=$tpl->field_textareaP("UFDBGUARD_PARA2","{parapgraph} 2",utf8_decode_switch($UFDBGUARD_PARA2));


    $form[]=$tpl->field_section("{ticket}");
	$form[]=$tpl->field_textareaP("TICKET_TEXT","{submit_ticket_text}",utf8_decode_switch($TICKET_TEXT));
	$form[]=$tpl->field_textareaP("CONFIRM_TICKET_TEXT","{confirm_ticket_text}",utf8_decode_switch($CONFIRM_TICKET_TEXT));
	$form[]=$tpl->field_text("CONFIRM_TICKET_BT","{CONFIRM_TICKET_BT}",utf8_decode_switch($CONFIRM_TICKET_BT));
	$form[]=$tpl->field_textareaP("TICKET_TEXT_SUCCESS","{submit_ticket_text} ({success})",utf8_decode_switch($TICKET_TEXT_SUCCESS));
    $form[]=$tpl->field_text("UFDBGUARD_TICKET_LINK","{UFDBGUARD_TICKET_LINK}",utf8_decode_switch($UFDBGUARD_TICKET_LINK));
    $form[]=$tpl->field_section("{unlock}");
	$form[]=$tpl->field_text("UFDBGUARD_UNLOCK_LINK","{UFDBGUARD_UNLOCK_LINK}",utf8_decode_switch($UFDBGUARD_UNLOCK_LINK));
    $form[]=$tpl->field_textareaP("UNBLOCK_PARAGRAPH","{parapgraph} 1",utf8_decode_switch($UNBLOCK_PARAGRAPH));
    $form[]=$tpl->field_text("REDIRECT_TEXT","{redirect_text}",utf8_decode_switch($REDIRECT_TEXT));

	$html[]=$tpl->form_outside($title,@implode("\n", $form),null,"{apply}","blur()","AsDansGuardianAdministrator",true);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function utf8_decode_switch($value):string{
    if(is_null($value)){return "";}
    if(PHP_MAJOR_VERSION>7) {
        return $value;
    }
    $tpl=new template_admin();
    return $tpl->utf8_decode($value);
}

function rule_skin_save():bool{
	$tpl=new template_admin();
    if(!ufdbweb_is_admin()){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return false;}
    $zmd5=$_POST["rule-skin"];
    $tpl->CLEAN_POST();
    // Build body from POST excluding the sentinel key
    $excludeKeys=["rule-skin"];
    $body=[];
    $integers=array(
        "UfdbGuardHTTPDisableHostname"=>true,
        "UfdbGuardHTTPNoVersion"=>true,
        "UfdbGuardHTTPEnablePostmaster"=>true
    );

    foreach($_POST as $k=>$v){
        if(in_array($k,$excludeKeys)){continue;}
        if(isset($integers[$k])){
            $body[$k]=intval($v);
            continue;
        }
        $body[$k]=$v;
    }


    list($ok,$errData)=acls_api_send("PUT","/webfilter/page-rules/".urlencode($zmd5),$body);
    if(!$ok){echo htmlspecialchars(is_string($errData)?$errData:"API error");return false;}
    admin_tracks("Modify the skin of the Web error page ID $zmd5");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return true;
}

function rule_tab():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=trim($_GET["rule-popup"]);
	if($id<>null){
		$ruleRow=acls_api_get("/webfilter/page-rules/".urlencode($id));
		$ruleName=is_array($ruleRow)?($ruleRow["ruleName"] ?? $ruleRow["rulename"] ?? $id):$id;
		$array["{$ruleName}"]="$page?rule-options=$id";
		$refresh_enc=base64_encode("LoadAjax('table-loader-webhttp-rules','$page?table=yes');");
		$array["{skin}"]="$page?skin=$id";
        $array["{messages} (SMTP)"]="$page?smtp-messages=$id";


	}else{
		$array["{new_rule}"]="$page?rule-options=";
	}
	
	
	echo $tpl->tabs_default($array);
    return true;
	
	
}
function switch_enabled():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!ufdbweb_is_admin()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return false;}
    $zmd5=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET["switch-enabled"]);
    $apiRow=acls_api_get("/webfilter/page-rules/".urlencode($zmd5));
    if(!is_array($apiRow)){echo $tpl->js_error("page rule not found");return false;}
    $enabled=intval($apiRow["enabled"] ?? 0)==1?0:1;
    list($ok,$data)=acls_api_send("PUT","/webfilter/page-rules/".urlencode($zmd5),array("enabled"=>$enabled));
    if(!$ok){echo $tpl->js_error(is_string($data)?$data:"API error");return false;}
    bpr_forget($zmd5);
    // Avant : la base changeait mais les regles n'etaient pas reexportees, la bascule restait sans effet.
    $err=bpr_compile();
    if($err!==""){echo $tpl->js_error($err);return false;}
    echo "LoadAjaxSilent('table-loader-webhttp-rules','$page?table=yes');";
    return admin_tracks("Set Web error page ".($apiRow["ruleName"] ?? $zmd5)." to enable=$enabled");
}
function table(){
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=True;
	$page=CurrentPageName();
	$m=bpr_model();

	$h=array();
	$h[]="<div class='bpr'>";
	$h[]=bpr_hero();
	$h[]=bpr_rules_card($m);
	$h[]="</div>";

	$topbuttons=array();
	if($m["can_create"]){
		$topbuttons[]=array("Loadjs('$page?ruleid-js=0',true);",ico_plus,"{bpr_new_rule}");
	}
	$topbuttons[]=array("LoadAjax('table-loader-webhttp-rules','$page?table=yes');",ico_refresh,"{refresh}");
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{block_pages_behavior}";
	$TINY_ARRAY["ICO"]="fas fa-hand-holding-hand";
	$TINY_ARRAY["EXPL"]="{block_pages_behavior_explain}";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	echo bpr_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();$jstiny</script>";
}

// ── Modele : lu une seule fois, alimente tous les widgets ───────────────────

function bpr_can_write():bool{
	return ufdbweb_is_admin();
}

function bpr_t($token,$a="",$b=""):string{
	if(!isset($GLOBALS["BPR_T"])){$GLOBALS["BPR_T"]=array();}
	if(!isset($GLOBALS["BPR_T"][$token])){
		$tpl=new template_admin();
		$GLOBALS["BPR_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
	}
	return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["BPR_T"][$token]);
}

function bpr_h($s):string{
	return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

// Le service applique allow AVANT ticket (artica-error-page main.go : Allow==1 => NoTicket).
// 0 explication seule, 1 deblocage, 2 demande a l'equipe informatique, 3 demande avec confirmation.
function bpr_outcome_of(array $r):int{
	if(intval($r["allow"] ?? 0)==1){return 1;}
	if(intval($r["ticket"] ?? 0)==1){return intval($r["ticket2"] ?? 0)==1?3:2;}
	return 0;
}

// Champs ecrits pour un choix : un seul comportement a la fois, comme le lit le service.
function bpr_outcome_fields(int $o):array{
	if($o<0 || $o>3){$o=0;}
	return array(
		"allow"=>$o==1?1:0,
		"deny"=>$o==0?1:0,
		"ticket"=>$o>=2?1:0,
		"ticket2"=>$o==3?1:0,
	);
}

function bpr_unlock_label(int $maxtime):string{
	if($maxtime<=0){return bpr_t("bpr_o_unlock_forever");}
	if($maxtime<60){return bpr_t("bpr_o_unlock_min",(string)$maxtime);}
	if($maxtime<1440 && $maxtime%60==0){return bpr_t("bpr_o_unlock_h",(string)($maxtime/60));}
	if($maxtime<1440){return bpr_t("bpr_o_unlock_min",(string)$maxtime);}
	$d=round($maxtime/1440,1);
	if($d==1){return bpr_t("bpr_o_unlock_1d");}
	return bpr_t("bpr_o_unlock_d",(string)$d);
}

// Meme jugement que artica-error-page isrule() : la regle porte-t-elle au moins une condition ?
function bpr_has_condition(array $r):bool{
	$network=trim((string)($r["network"] ?? ""));
	if($network=="0.0.0.0/0"){$network="";}
	if(strlen($network)>4){return true;}
	if(strlen(trim((string)($r["lang"] ?? "")))>1){return true;}
	if(intval($r["aclid"] ?? 0)>0){return true;}
	if(intval($r["webruleid"] ?? 0)>0){return true;}
	if(intval($r["category"] ?? 0)>0){return true;}
	if(strlen(trim((string)($r["username"] ?? "")))>2){return true;}
	return false;
}

function bpr_category_name($id):string{
	$id=intval($id);
	if($id<=0){return "";}
	$tpl=new template_admin();
	$n=$tpl->CategoryidToName($id);
	return is_string($n)?strip_tags($n):"";
}

function bpr_model():array{
	$tpl=new template_admin();
	$m=array("rules"=>array(),"on"=>0,"templates"=>array(),"default_template"=>"");
	$m["can_write"]=bpr_can_write();
	$m["can_create"]=$m["can_write"] && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==0;

	$tmplRows=acls_api_get("/proxy/templates-manager");
	if(is_array($tmplRows)){
		foreach($tmplRows as $tmpl){
			$ID=intval($tmpl["id"] ?? 0);
			if($ID===0){continue;}
			$m["templates"][$ID]=$tpl->utf8_encode((string)($tmpl["templateName"] ?? ""));
		}
	}
	$m["default_template"]=$m["templates"][1] ?? "";
	$WFRULES=array();
	$wf=acls_api_get("/webfilter/rules");
	if(is_array($wf)){
		foreach($wf as $w){
			$wid=intval($w["id"] ?? 0);
			if($wid>0){$WFRULES[$wid]=(string)($w["groupName"] ?? $w["groupname"] ?? "");}
		}
	}
	$LANGS=bpr_langs();
	$list=acls_api_get("/webfilter/page-rules");
	if(!is_array($list)){$list=array();}
	foreach($list as $a){
		if(!is_array($a)){continue;}
		$r=array(
			"id"=>preg_replace('/[^A-Za-z0-9]/','',(string)($a["zmd5"] ?? "")),
			"name"=>trim($tpl->utf8_encode((string)($a["ruleName"] ?? $a["rulename"] ?? ""))),
			"enabled"=>intval($a["enabled"] ?? 0)==1,
			"allow"=>intval($a["allow"] ?? 0),
			"ticket"=>intval($a["ticket"] ?? 0),
			"ticket2"=>intval($a["ticket2"] ?? 0),
			"maxtime"=>intval($a["maxtime"] ?? 0),
			"notify"=>intval($a["notify"] ?? 0),
			"postapi"=>intval($a["postapi"] ?? 0),
			"addTocat"=>intval($a["addTocat"] ?? 0),
			"recipients"=>trim((string)($a["recipients"] ?? "")),
			"network"=>trim((string)($a["network"] ?? "")),
			"lang"=>trim((string)($a["lang"] ?? "")),
			"aclid"=>intval($a["aclid"] ?? 0),
			"webruleid"=>intval($a["webruleID"] ?? $a["webruleid"] ?? 0),
			"category"=>intval($a["category"] ?? 0),
			"username"=>trim((string)($a["username"] ?? "")),
			"adgroup"=>trim((string)($a["adgroup"] ?? "")),
			"templateid"=>intval($a["templateID"] ?? $a["templateid"] ?? 0),
		);
		if($r["id"]===""){continue;}
		if($r["templateid"]==0){$r["templateid"]=1;}
		if($r["adgroup"]=="*"){$r["adgroup"]="";}
		$r["template"]=$m["templates"][$r["templateid"]] ?? "";
		$r["outcome"]=bpr_outcome_of($r);
		$r["fallback"]=!bpr_has_condition($r);
		$r["acl_name"]=$r["aclid"]>0?(string)acl_rule_name($r["aclid"]):"";
		$r["policy"]=$WFRULES[$r["webruleid"]] ?? "";
		$r["category_name"]=bpr_category_name($r["category"]);
		$r["addcat_name"]=bpr_category_name($r["addTocat"]);
		$r["lang_name"]=$r["lang"]!==""?(string)($LANGS[$r["lang"]] ?? $r["lang"]):"";
		if($r["enabled"]){$m["on"]++;}
		$m["rules"][]=$r;
	}
	return $m;
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function bpr_hero():string{
	return "<div class='bpr-hero'><div class='bpr-hero-ico'><i class='fas fa-hand-holding-hand'></i></div><div class='bpr-hero-body'>"
		."<h3>{bpr_hero_title} <span class='bpr-hero-sub'>{bpr_hero_sub}</span></h3><p>{bpr_hero_text}</p><div class='bpr-chips'>"
		."<span class='bpr-chip'><i class='fas fa-arrow-down-1-9'></i> {bpr_chip_order}</span>"
		."<span class='bpr-chip'><i class='fas fa-headset'></i> {bpr_chip_support}</span>"
		."<span class='bpr-chip'><i class='fas fa-hourglass-half'></i> {bpr_chip_time}</span>"
		."<span class='bpr-chip'><i class='fas fa-palette'></i> {bpr_chip_texts}</span>"
		."</div></div></div>";
}

function bpr_tag($ico,$text,$cls="",$title=""):string{
	$t=$title!==""?" title=\"".bpr_h($title)."\"":"";
	return "<span class='bpr-tag $cls'$t><i class='$ico'></i><span class='bpr-tag-t'>$text</span></span>";
}

// Ligne « Qui » : une puce par condition reelle, « Any … » quand le champ est vide.
function bpr_who_line(array $r):string{
	$c=array();
	if($r["aclid"]>0){
		$n=$r["acl_name"]!==""?bpr_h($r["acl_name"]):bpr_t("bpr_unknown",(string)$r["aclid"]);
		$c[]=bpr_tag("fas fa-shield-halved",bpr_t("bpr_acl",$n));
	}elseif($r["webruleid"]>0){
		$n=$r["policy"]!==""?bpr_h($r["policy"]):bpr_t("bpr_unknown",(string)$r["webruleid"]);
		$c[]=bpr_tag("fas fa-shield-halved",bpr_t("bpr_filter",$n));
	}else{
		$c[]=bpr_tag("fas fa-shield-halved","{bpr_everyone_filtered}","bpr-tag-muted");
	}
	if($r["category"]>0){
		$c[]=bpr_tag("fas fa-tags",$r["category_name"]!==""?bpr_h($r["category_name"]):bpr_t("bpr_unknown",(string)$r["category"]));
	}else{
		$c[]=bpr_tag("fas fa-tags","{bpr_any_category}","bpr-tag-muted");
	}
	$net=$r["network"]=="0.0.0.0/0"?"":$r["network"];
	if(strlen($net)>4){
		$c[]=bpr_tag("fas fa-network-wired","<span class='bpr-mono'>".bpr_h($net)."</span>");
	}else{
		$c[]=bpr_tag("fas fa-network-wired","{bpr_any_network}","bpr-tag-muted");
	}
	if(strlen($r["username"])>2){
		$c[]=bpr_tag("fas fa-user",bpr_h($r["username"]),"",$r["username"]);
	}
	if($r["lang"]!==""){
		$c[]=bpr_tag("fas fa-language",bpr_h($r["lang_name"]));
	}
	if($r["adgroup"]!==""){
		$g=preg_replace('/^CN=([^,]+),.*$/i','$1',$r["adgroup"]);
		$c[]=bpr_tag("fas fa-triangle-exclamation",bpr_t("bpr_adgroup_ignored",bpr_h($g)),"bpr-tag-warn",bpr_t("bpr_adgroup_ignored_help"));
	}
	if($r["fallback"]){
		$c[]=bpr_tag("fas fa-life-ring","{bpr_fallback_badge}","bpr-tag-muted",strip_tags(bpr_t("bpr_fallback_help")));
	}
	return "<div class='bpr-line'><span class='bpr-line-l'>{bpr_who}</span><span class='bpr-chipset'>".implode("",$c)."</span></div>";
}

// Ligne « Ce qu'ils peuvent faire » : UN resultat + options reelles.
function bpr_what_line(array $r):string{
	$o=$r["outcome"];
	if($o==1){
		$pill=bpr_tag("fas fa-unlock",bpr_unlock_label($r["maxtime"]),$r["maxtime"]>0?"bpr-pill bpr-pill-ok":"bpr-pill bpr-pill-warn");
	}elseif($o==2){
		$pill=bpr_tag("fas fa-paper-plane","{bpr_o_ask}","bpr-pill bpr-pill-info");
	}elseif($o==3){
		$pill=bpr_tag("fas fa-paper-plane","{bpr_o_ask2}","bpr-pill bpr-pill-info");
	}else{
		$pill=bpr_tag("fas fa-circle-info","{bpr_o_explain}","bpr-pill");
	}
	$x=array($pill);
	if($r["notify"]==1){$x[]=bpr_tag("fas fa-file-lines","{bpr_x_record}","bpr-tag-muted");}
	if($o>0 && $r["addTocat"]>0){
		$n=$r["addcat_name"]!==""?bpr_h($r["addcat_name"]):"#".$r["addTocat"];
		$x[]=bpr_tag("fas fa-folder-plus",bpr_t("bpr_x_addcat",$n),"bpr-tag-muted");
	}
	if($r["postapi"]==1){$x[]=bpr_tag("fas fa-code","{bpr_x_api}","bpr-tag-muted");}
	if($o>=2 && $r["recipients"]!==""){
		$x[]="<span class='bpr-recip' title=\"".bpr_h($r["recipients"])."\">".bpr_t("bpr_x_recipients",bpr_h($r["recipients"]))."</span>";
	}
	return "<div class='bpr-line'><span class='bpr-line-l'>{bpr_what}</span><span class='bpr-chipset'>".implode("",$x)."</span></div>";
}

function bpr_rule_row(array $r,int $i,int $n,array $m):string{
	$page=CurrentPageName();
	$id=$r["id"];
	$can=$m["can_write"];
	$edit="Loadjs('$page?ruleid-js=$id');";
	$name=$r["name"]!==""?bpr_h($r["name"]):bpr_t("bpr_unknown",$id);
	$up=($i>0 && $can)?"<button type='button' class='bpr-ibtn' aria-label=\"".bpr_h(bpr_t("bpr_move_up"))."\" title=\"".bpr_h(bpr_t("bpr_move_up"))."\" OnClick=\"Loadjs('$page?move-rule=$id&dir=1');\"><i class='fas fa-chevron-up'></i></button>"
		:"<button type='button' class='bpr-ibtn' disabled aria-hidden='true' tabindex='-1'><i class='fas fa-chevron-up'></i></button>";
	$down=($i<$n-1 && $can)?"<button type='button' class='bpr-ibtn' aria-label=\"".bpr_h(bpr_t("bpr_move_down"))."\" title=\"".bpr_h(bpr_t("bpr_move_down"))."\" OnClick=\"Loadjs('$page?move-rule=$id&dir=0');\"><i class='fas fa-chevron-down'></i></button>"
		:"<button type='button' class='bpr-ibtn' disabled aria-hidden='true' tabindex='-1'><i class='fas fa-chevron-down'></i></button>";
	$design=$r["template"]!==""?bpr_h($r["template"]):"#".$r["templateid"];
	$sw=$r["enabled"]?"true":"false";
	$swl=$r["enabled"]?"{bpr_on}":"{bpr_off}";
	$toggle=$can?"<span class='bpr-swl'>$swl</span><button type='button' class='bpr-switch' role='switch' aria-checked='$sw' aria-label=\"".bpr_h(bpr_t("bpr_toggle_x",$r["name"]))."\" OnClick=\"Loadjs('$page?switch-enabled=$id');\"><span class='bpr-knob'></span></button>"
		:"<span class='bpr-swl'>$swl</span>";
	$acts="<button type='button' class='bpr-ibtn' aria-label=\"".bpr_h(bpr_t("bpr_edit"))."\" title=\"".bpr_h(bpr_t("bpr_edit"))."\" OnClick=\"$edit\"><i class='fas fa-pen'></i></button>";
	if($can){
		$acts.="<button type='button' class='bpr-ibtn bpr-ibtn-del' aria-label=\"".bpr_h(bpr_t("bpr_delete"))."\" title=\"".bpr_h(bpr_t("bpr_delete"))."\" OnClick=\"Loadjs('$page?delete-rule-js=$id');\"><i class='fas fa-trash'></i></button>";
	}
	$off=$r["enabled"]?"":" bpr-row-off";
	$badge=$r["enabled"]?"":"<span class='bpr-badge-off' title=\"".bpr_h(bpr_t("bpr_skipped"))."\">{bpr_off}</span>";
	return "<div class='bpr-row$off' id='$id'>"
		."<div class='bpr-pos'><span class='bpr-num' aria-label=\"".bpr_h(bpr_t("bpr_pos_aria",(string)($i+1),(string)$n))."\">".($i+1)."</span><div class='bpr-moves'>$up$down</div></div>"
		."<div class='bpr-main'><div class='bpr-head'><a class='bpr-name' href='javascript:void(0)' OnClick=\"$edit\">$name</a>$badge"
		.bpr_tag("fas fa-palette",bpr_t("bpr_design",$design),"bpr-tag-muted bpr-design")."</div>"
		.bpr_who_line($r).bpr_what_line($r)."</div>"
		."<div class='bpr-side'><div class='bpr-toggle'>$toggle</div><div class='bpr-acts'>$acts</div></div>"
		."</div>";
}

function bpr_fallback_row(array $m):string{
	$design=$m["default_template"]!==""?bpr_tag("fas fa-palette",bpr_t("bpr_design",bpr_h($m["default_template"])),"bpr-tag-muted"):"";
	return "<div class='bpr-fallback'><span class='bpr-fb-ico'><i class='fas fa-shield-halved'></i></span><div class='bpr-main'>"
		."<div class='bpr-head'><span class='bpr-name'>{bpr_fb_title}</span><span class='bpr-badge-off'>{bpr_fb_badge}</span>$design</div>"
		."<div class='bpr-fb-text'>{bpr_fb_text}</div><div class='bpr-sub'>{bpr_fb_help}</div></div>"
		."<div class='bpr-side'>".bpr_tag("fas fa-circle-info","{bpr_o_explain}","bpr-pill")."</div></div>";
}

function bpr_empty(array $m):string{
	$page=CurrentPageName();
	$btn=$m["can_create"]?"<button type='button' class='bpr-btn bpr-btn-primary' OnClick=\"Loadjs('$page?ruleid-js=0',true);\"><i class='fas fa-plus'></i> {bpr_new_rule}</button>":"";
	return "<div class='bpr-empty'><div class='bpr-empty-ico'><i class='fas fa-hand-holding-hand'></i></div><h4>{bpr_empty_title}</h4><p>{bpr_empty_text}</p>$btn</div>";
}

function bpr_rules_header(array $m):string{
	$page=CurrentPageName();
	$n=count($m["rules"]);
	$count="";
	if($n>0){
		$count=$n==1?bpr_t("bpr_count1",number_format($m["on"])):bpr_t("bpr_count",number_format($n),number_format($m["on"]));
		$count="<span class='bpr-count'>$count</span><span class='bpr-hnote'>{bpr_order_note}</span>";
	}
	$btns="";
	if($m["can_write"]){
		$btns.="<button type='button' class='bpr-btn' title=\"".bpr_h(strip_tags(bpr_t("bpr_apply_help")))."\" OnClick=\"Loadjs('$page?sync=yes');\"><i class='fas fa-arrows-rotate'></i> {bpr_apply}</button>";
	}
	if($m["can_create"]){
		$btns.="<button type='button' class='bpr-btn bpr-btn-primary' OnClick=\"Loadjs('$page?ruleid-js=0',true);\"><i class='fas fa-plus'></i> {bpr_new_rule}</button>";
	}
	return "<div class='bpr-card-h'><div class='bpr-card-t'><h3>{bpr_your_rules}</h3>$count</div><div class='bpr-card-b'>$btns</div></div>";
}

function bpr_rules_card(array $m):string{
	$n=count($m["rules"]);
	$rows=array();
	foreach($m["rules"] as $i=>$r){$rows[]=bpr_rule_row($r,$i,$n,$m);}
	$body=$n==0?bpr_empty($m):"<div class='bpr-list'>".implode("",$rows)."</div>";
	return "<div class='bpr-card'>".bpr_rules_header($m).$body.bpr_fallback_row($m)."</div>";
}

// ── Ecritures ───────────────────────────────────────────────────────────────

// Reexporte les regles vers le service des pages bloquees ; "" = succes.
function bpr_compile():string{
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules"));
	if(!is_object($json)){return "articarest: no answer";}
	if(property_exists($json,"Status") && !$json->Status){return property_exists($json,"Error")?(string)$json->Error:"error";}
	$GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
	return "";
}

// rule_id_js() met la regle en cache : l'oublier apres chaque ecriture.
function bpr_forget($zmd5){
	$mem=new lib_memcached();
	$mem->saveKey("ufdb_page_rules_l",false);
	$mem->Delkey("ufdb_page_rules_$zmd5");
}

// Nouvel ordre apres deplacement (pure). $ids dans l'ordre courant.
function bpr_reorder(array $ids,$ID,$up):array{
	$pos=array_search($ID,$ids,true);
	if($pos===false){return $ids;}
	$to=$up?$pos-1:$pos+1;
	if($to<0 || $to>=count($ids)){return $ids;}
	$tmp=$ids[$to];$ids[$to]=$ids[$pos];$ids[$pos]=$tmp;
	return $ids;
}

function rule_move():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	if(!bpr_can_write()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return false;}
	$ID=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET["move-rule"]);
	$up=intval($_GET["dir"] ?? 0)==1;
	$list=acls_api_get("/webfilter/page-rules");
	if(!is_array($list)){echo $tpl->js_error("articarest: no answer");return false;}
	$ids=array();$cur=array();
	foreach($list as $a){
		$z=preg_replace('/[^A-Za-z0-9]/','',(string)($a["zmd5"] ?? ""));
		if($z===""){continue;}
		$ids[]=$z;
		$cur[$z]=intval($a["xOrder"] ?? $a["zorder"] ?? 0);
	}
	$new=bpr_reorder($ids,$ID,$up);
	foreach($new as $i=>$z){
		if($cur[$z]==$i+1){continue;}
		list($ok,$err)=acls_api_send("PUT","/webfilter/page-rules/".urlencode($z),array("zorder"=>$i+1));
		if(!$ok){echo $tpl->js_error(is_string($err)?$err:"API error");return false;}
		bpr_forget($z);
	}
	$err=bpr_compile();
	if($err!==""){echo $tpl->js_error($err);return false;}
	echo "LoadAjaxSilent('table-loader-webhttp-rules','$page?table=yes');";
	return admin_tracks("Move Web error page rule $ID ".($up?"up":"down"));
}

function bpr_style():string{
	if(isset($GLOBALS["BPR_STYLE"])){return "";}
	$GLOBALS["BPR_STYLE"]=true;
	$accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$accent=$a["accent"];}
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
		if(!empty($a["accent_line"])){$line=$a["accent_line"];}
	}
	return "<style>
.bpr{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
.bpr-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px 20px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.bpr-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.bpr-hero-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.bpr-hero-body{flex:1;min-width:0}
.bpr-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600}
.bpr-hero-sub{font-size:13px;font-weight:400;color:#64748b;margin-left:6px}
.bpr-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569;max-width:960px}
.bpr-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.bpr-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155;white-space:nowrap}
.bpr-chip i,.bpr-chip svg{color:$accent}
.bpr-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.bpr-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 22px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
.bpr-card-t{display:flex;align-items:center;gap:12px;flex-wrap:wrap;min-width:0}
.bpr-card-t h3{margin:0;font-size:17px;font-weight:600}
.bpr-card-b{display:flex;gap:10px;flex:0 0 auto}
.bpr-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.bpr-hnote{font-size:13px;color:#64748b;white-space:nowrap}
.bpr-list{display:flex;flex-direction:column;gap:12px;padding:18px 22px 0 22px}
.bpr-row{display:flex;align-items:center;gap:18px;padding:16px 18px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}
.bpr-row:hover{border-color:#cbd5e1}
.bpr-row-off .bpr-main,.bpr-row-off .bpr-pos{opacity:.6}
.bpr-pos{display:flex;flex-direction:column;align-items:center;gap:4px;flex:0 0 auto}
.bpr-num{width:32px;height:32px;border-radius:50%;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;font-weight:600;color:#475569}
.bpr-moves{display:flex;flex-direction:column}
.bpr-main{flex:1;min-width:0}
.bpr-head{display:flex;align-items:center;flex-wrap:wrap;gap:8px 10px;margin-bottom:8px}
.bpr-name{font-size:16px;font-weight:600;color:#0f172a;text-decoration:none;overflow-wrap:anywhere}
a.bpr-name:hover{color:$dark}
.bpr-badge-off{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:600;color:#64748b;white-space:nowrap}
.bpr-line{display:flex;align-items:flex-start;gap:8px;margin-top:6px}
.bpr-line-l{flex:0 0 118px;font-size:13px;color:#64748b;padding-top:3px}
.bpr-chipset{flex:1;min-width:0;display:flex;flex-wrap:wrap;align-items:center;gap:6px 8px}
.bpr-tag{display:inline-flex;align-items:center;gap:6px;max-width:340px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;border-radius:6px;padding:2px 9px;font-size:13px;font-weight:500;white-space:nowrap}
.bpr-tag-t{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.bpr-tag i,.bpr-tag svg{flex:0 0 auto}
.bpr-tag-muted{background:#f1f5f9;border-color:#e2e8f0;color:#334155}
.bpr-tag-muted i,.bpr-tag-muted svg{color:#64748b}
.bpr-tag-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}
.bpr-design{font-weight:400}
.bpr-mono{font-family:monospace;font-size:13px}
.bpr-tag.bpr-pill{border-radius:999px;padding:3px 12px;font-weight:600;background:#f1f5f9;border-color:#e2e8f0;color:#475569}
.bpr-tag.bpr-pill-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
.bpr-tag.bpr-pill-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}
.bpr-tag.bpr-pill-info{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.bpr-recip{font-size:13px;color:#64748b;max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bpr-side{display:flex;align-items:center;gap:18px;flex:0 0 auto}
.bpr-toggle{display:flex;align-items:center;justify-content:flex-end;gap:8px;min-width:84px}
.bpr-switch{position:relative;width:46px;height:26px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.bpr-switch[aria-checked=true]{background:$accent}
.bpr-knob{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.bpr-switch[aria-checked=true] .bpr-knob{left:23px}
.bpr-switch:focus-visible,.bpr-ibtn:focus-visible,.bpr-btn:focus-visible,a.bpr-name:focus-visible{outline:2px solid $accent;outline-offset:2px}
.bpr-swl{font-size:13px;font-weight:600;color:#475569}
.bpr-acts{display:flex;gap:4px;border-left:1px solid #e2e8f0;padding-left:14px}
.bpr-ibtn{border:0;background:transparent;color:#64748b;width:32px;height:28px;border-radius:8px;cursor:pointer}
.bpr-ibtn:hover:not([disabled]){background:#f1f5f9;color:#0f172a}
.bpr-ibtn[disabled]{color:#cbd5e1;cursor:default}
.bpr-ibtn-del:hover:not([disabled]){background:#fef2f2;color:#b91c1c}
.bpr-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap}
.bpr-btn:hover{background:#f8fafc}
.bpr-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.bpr-btn-primary:hover{background:$dark;border-color:$dark}
.bpr-fallback{display:flex;align-items:center;gap:16px;margin:16px 22px 22px 22px;padding:16px 18px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc}
.bpr-fb-ico{flex:0 0 40px;height:40px;border-radius:50%;background:#e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center}
.bpr-fb-text{font-size:14px;color:#475569}
.bpr-sub{margin-top:4px;font-size:12px;color:#64748b;font-style:italic}
.bpr-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:36px 24px 10px 24px;gap:10px}
.bpr-empty-ico{width:68px;height:68px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px}
.bpr-empty h4{margin:4px 0 0 0;font-size:17px;font-weight:600}
.bpr-empty p{margin:0;color:#475569;max-width:580px;line-height:1.6}
@media (max-width:1200px){.bpr-row{flex-wrap:wrap}.bpr-side{margin-left:auto}.bpr-line-l{flex-basis:auto}}
@media (prefers-reduced-motion:reduce){.bpr-switch,.bpr-knob{transition:none}}
</style>";
}

function bpr_langs():array{
	$LANGS=array();
	$LANGS[null]="{all_languages}";
	$LANGS["af"]="Afrikaans";
	$LANGS["sq"]="Albanian";
	$LANGS["ar-dz"]= "Arabic (Algeria)";
	$LANGS["ar-bh"]= "Arabic (Bahrain)";
	$LANGS["ar-eg"]= "Arabic (Egypt)";
	$LANGS["ar-iq"]= "Arabic (Iraq)";
	$LANGS["ar-jo"]= "Arabic (Jordan)";
	$LANGS["ar-kw"]= "Arabic (Kuwait)";
	$LANGS["ar-lb"]= "Arabic (Lebanon)";
	$LANGS["ar-ly"]= "Arabic (Libya)";
	$LANGS["ar-ma"]= "Arabic (Morocco)";
	$LANGS["ar-om"]= "Arabic (Oman)";
	$LANGS["ar-qa"]= "Arabic (Qatar)";
	$LANGS["ar-sa"]= "Arabic (Saudi Arabia)";
	$LANGS["ar-sy"]= "Arabic (Syria)";
	$LANGS["ar-tn"]= "Arabic (Tunisia)";
	$LANGS["ar-ae"]= "Arabic (U.A.E.)";
	$LANGS["ar-ye"]= "Arabic (Yemen)";
	$LANGS["ar"]="Aragonese";
	$LANGS["hy"]="Armenian";
	$LANGS["as"]="Assamese";
	$LANGS["ast"]="Asturian";
	$LANGS["az"]="Azerbaijani";
	$LANGS["eu"]="Basque";
	$LANGS["be"]="Belarusian";
	$LANGS["bn"]="Bengali";
	$LANGS["bs"]="Bosnian";
	$LANGS["br"]="Breton";
	$LANGS["bg"]="Bulgarian";
	$LANGS["my"]="Burmese";
	$LANGS["ca"]="Catalan";
	$LANGS["ch"]="Chamorro";
	$LANGS["ce"]="Chechen";
	$LANGS["zh"]="Chinese";
	$LANGS["zh-hk"]= "Chinese (Hong Kong)";
	$LANGS["zh-cn"]= "Chinese (PRC)";
	$LANGS["zh-sg"]= "Chinese (Singapore)";
	$LANGS["zh-tw"]= "Chinese (Taiwan)";
	$LANGS["cv"]="Chuvash";
	$LANGS["co"]="Corsican";
	$LANGS["cr"]="Cree";
	$LANGS["hr"]="Croatian";
	$LANGS["cs"]="Czech";
	$LANGS["da"]="Danish";
	$LANGS["nl"]="Dutch (Standard)";
	$LANGS["nl-be"]= "Dutch (Belgian)";
	$LANGS["en"]="English";
	$LANGS["en-au"]= "English (Australia)";
	$LANGS["en-bz"]= "English (Belize)";
	$LANGS["en-ca"]= "English (Canada)";
	$LANGS["en-ie"]= "English (Ireland)";
	$LANGS["en-jm"]= "English (Jamaica)";
	$LANGS["en-nz"]= "English (New Zealand)";
	$LANGS["en-ph"]= "English (Philippines)";
	$LANGS["en-za"]= "English (South Africa)";
	$LANGS["en-tt"]= "English (Trinidad & Tobago)";
	$LANGS["en-gb"]= "English (United Kingdom)";
	$LANGS["en-us"]= "English (United States)";
	$LANGS["en-zw"]= "English (Zimbabwe)";
	$LANGS["eo"]="Esperanto";
	$LANGS["et"]="Estonian";
	$LANGS["fo"]="Faeroese";
	$LANGS["fj"]="Fijian";
	$LANGS["fi"]="Finnish";
	$LANGS["fr"]="French (Standard)";
	$LANGS["fr-be"]= "French (Belgium)";
	$LANGS["fr-ca"]= "French (Canada)";
	$LANGS["fr-fr"]= "French (France)";
	$LANGS["fr-lu"]= "French (Luxembourg)";
	$LANGS["fr-mc"]= "French (Monaco)";
	$LANGS["fr-ch"]= "French (Switzerland)";
	$LANGS["fy"]="Frisian";
	$LANGS["fur"]= "Friulian";
	$LANGS["gd-ie"]= "Gaelic (Irish)";
	$LANGS["gl"]="Galacian";
	$LANGS["ka"]="Georgian";
	$LANGS["de"]="German (Standard)";
	$LANGS["de-at"]= "German (Austria)";
	$LANGS["de-de"]= "German (Germany)";
	$LANGS["de-li"]= "German (Liechtenstein)";
	$LANGS["de-lu"]= "German (Luxembourg)";
	$LANGS["de-ch"]= "German (Switzerland)";
	$LANGS["el"]="Greek";
	$LANGS["gu"]="Gujurati";
	$LANGS["ht"]="Haitian";
	$LANGS["he"]="Hebrew";
	$LANGS["hi"]="Hindi";
	$LANGS["hu"]="Hungarian";
	$LANGS["is"]="Icelandic";
	$LANGS["id"]="Indonesian";
	$LANGS["iu"]="Inuktitut";
	$LANGS["ga"]="Irish";
	$LANGS["it"]="Italian (Standard)";
	$LANGS["it-ch"]= "Italian (Switzerland)";
	$LANGS["ja"]="Japanese";
	$LANGS["kn"]="Kannada";
	$LANGS["ks"]="Kashmiri";
	$LANGS["kk"]="Kazakh";
	$LANGS["km"]="Khmer";
	$LANGS["ky"]="Kirghiz";
	$LANGS["tlh"]= "Klingon";
	$LANGS["ko"]="Korean";
	$LANGS["ko-kp"]= "Korean (North Korea)";
	$LANGS["ko-kr"]= "Korean (South Korea)";
	$LANGS["la"]="Latin";
	$LANGS["lv"]="Latvian";
	$LANGS["lt"]="Lithuanian";
	$LANGS["lb"]="Luxembourgish";
	$LANGS["mk"]="FYRO Macedonian";
	$LANGS["ms"]="Malay";
	$LANGS["ml"]="Malayalam";
	$LANGS["mt"]="Maltese";
	$LANGS["mi"]="Maori";
	$LANGS["mr"]="Marathi";
	$LANGS["mo"]="Moldavian";
	$LANGS["nv"]="Navajo";
	$LANGS["ng"]="Ndonga";
	$LANGS["ne"]="Nepali";
	$LANGS["no"]="Norwegian";
	$LANGS["nb"]="Norwegian (Bokmal)";
	$LANGS["nn"]="Norwegian (Nynorsk)";
	$LANGS["oc"]="Occitan";
	$LANGS["or"]="Oriya";
	$LANGS["om"]="Oromo";
	$LANGS["fa"]="Persian";
	$LANGS["fa-ir"]= "Persian/Iran";
	$LANGS["pl"]="Polish";
	$LANGS["pt"]="Portuguese";
	$LANGS["pt-br"]= "Portuguese (Brazil)";
	$LANGS["pa"]="Punjabi";
	$LANGS["pa-in"]= "Punjabi (India)";
	$LANGS["pa-pk"]= "Punjabi (Pakistan)";
	$LANGS["qu"]="Quechua";
	$LANGS["rm"]="Rhaeto-Romanic";
	$LANGS["ro"]="Romanian";
	$LANGS["ro-mo"]= "Romanian (Moldavia)";
	$LANGS["ru"]="Russian";
	$LANGS["ru-mo"]= "Russian (Moldavia)";
	$LANGS["sz"]="Sami (Lappish)";
	$LANGS["sg"]="Sango";
	$LANGS["sa"]="Sanskrit";
	$LANGS["sc"]="Sardinian";
	$LANGS["gd"]="Scots Gaelic";
	$LANGS["sd"]="Sindhi";
	$LANGS["si"]="Singhalese";
	$LANGS["sr"]="Serbian";
	$LANGS["sk"]="Slovak";
	$LANGS["sl"]="Slovenian";
	$LANGS["so"]="Somani";
	$LANGS["sb"]="Sorbian";
	$LANGS["es"]="Spanish";
	$LANGS["es-ar"]= "Spanish (Argentina)";
	$LANGS["es-bo"]= "Spanish (Bolivia)";
	$LANGS["es-cl"]= "Spanish (Chile)";
	$LANGS["es-co"]= "Spanish (Colombia)";
	$LANGS["es-cr"]= "Spanish (Costa Rica)";
	$LANGS["es-do"]= "Spanish (Dominican Republic)";
	$LANGS["es-ec"]= "Spanish (Ecuador)";
	$LANGS["es-sv"]= "Spanish (El Salvador)";
	$LANGS["es-gt"]= "Spanish (Guatemala)";
	$LANGS["es-hn"]= "Spanish (Honduras)";
	$LANGS["es-mx"]= "Spanish (Mexico)";
	$LANGS["es-ni"]= "Spanish (Nicaragua)";
	$LANGS["es-pa"]= "Spanish (Panama)";
	$LANGS["es-py"]= "Spanish (Paraguay)";
	$LANGS["es-pe"]= "Spanish (Peru)";
	$LANGS["es-pr"]= "Spanish (Puerto Rico)";
	$LANGS["es-es"]= "Spanish (Spain)";
	$LANGS["es-uy"]= "Spanish (Uruguay)";
	$LANGS["es-ve"]= "Spanish (Venezuela)";
	$LANGS["sx"]="Sutu";
	$LANGS["sw"]="Swahili";
	$LANGS["sv"]="Swedish";
	$LANGS["sv-fi"]= "Swedish (Finland)";
	$LANGS["sv-sv"]= "Swedish (Sweden)";
	$LANGS["ta"]="Tamil";
	$LANGS["tt"]="Tatar";
	$LANGS["te"]="Teluga";
	$LANGS["th"]="Thai";
	$LANGS["tig"]= "Tigre";
	$LANGS["ts"]="Tsonga";
	$LANGS["tn"]="Tswana";
	$LANGS["tr"]="Turkish";
	$LANGS["tk"]="Turkmen";
	$LANGS["uk"]="Ukrainian";
	$LANGS["hsb"]= "Upper Sorbian";
	$LANGS["ur"]="Urdu";
	$LANGS["ve"]="Venda";
	$LANGS["vi"]="Vietnamese";
	$LANGS["vo"]="Volapuk";
	$LANGS["wa"]="Walloon";
	$LANGS["cy"]="Welsh";
	$LANGS["xh"]="Xhosa";
	$LANGS["ji"]="Yiddish";
	$LANGS["zu"]= "Zulu";
	return $LANGS;
}

function tests_smtp(){
	include_once('ressources/class.squidguard-msmtp.inc');
	include_once(dirname(__FILE__).'/ressources/smtp/class.smtp.loader.inc');
	echo "<textarea style='width:100%;height:275px;font-size:14px !important;border:4px solid #CCCCCC;
	font-family:\"Courier New\",
	Courier,monospace;color:black' id='subtitle'>";
	
	$tpl=new templates();
	$SquidGuardWebSMTP=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebSMTP"));
	


	$smtp_sender=$_GET["smtp_sendto"];
	$recipient=$SquidGuardWebSMTP["smtp_recipient"];
	$smtp_senderTR=explode("@",$_GET["smtp_sendto"]);
    $body[]="Return-Path: <$smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $smtp_sender";
	$body[]="To: $recipient";
	$body[]="Subject: Test notification from Web interface";

	$body[]="";
	$body[]="";
	$body[]="Here, the message from the robot...";
	$body[]="";
	$body[]="";

	$finalbody=@implode("\r\n", $body);


	$msmtp=new squidguard_msmtp($smtp_sender,$finalbody);
	if($msmtp->Send()){
		echo req_html($msmtp->logs); // Pentest 2026-11-04 : smtp_sendto etait recopie sans encodage dans la page (XSS).
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("Test Message\nTo $recipient: {success}")."');</script>";
		return;
	}


	echo req_html($msmtp->logs)."\n";
	$smtp=new smtp();
	$smtp->debug=true;
	if($SquidGuardWebSMTP["smtp_auth_user"]<>null){
		$params["auth"]=true;
		$params["user"]=$SquidGuardWebSMTP["smtp_auth_user"];
		$params["pass"]=$SquidGuardWebSMTP["smtp_auth_passwd"];
	}
	$params["host"]=$SquidGuardWebSMTP["smtp_server_name"];
	$params["port"]=$SquidGuardWebSMTP["smtp_server_port"];
	if(!$smtp->connect($params)){
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text")."');</script>";

		return;
	}


	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$recipient,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error}\\n $smtp->error_number $smtp->error_text")."');</script>";
		return;
	}

	echo "</textarea><script>";
	echo "alert('".$tpl->javascript_parse_text("Test Message\nTo $recipient: {success}")."');</script>";
	$smtp->quit();

}
