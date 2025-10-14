<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.smtp.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["sync"])){sync();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-options"])){rule_options();exit;}
if(isset($_POST["rule-options"])){rule_options_save();exit;}
if(isset($_GET["smtp-js"])){smtp_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["parameters"])){rule_parameters();exit;}
if(isset($_POST["parameters-save"])){rule_parameters_save();exit;}
if(isset($_POST["smtp-messages"])){smtp_messages_save();exit;}

if(isset($_GET["smtp-popup"])){smtp_parameters();exit;}
if(isset($_GET["rule-proxies-table"])){rule_proxies_table();exit;}
if(isset($_POST["smtp_server_name"])){smtp_parameters_save();exit;}
if(isset($_GET["rule-proxies-newpopup"])){rule_proxies_new_popup();exit;}
if(isset($_GET["rules-proxies-move"])){rule_proxies_move();exit;}
if(isset($_GET["skin"])){rule_skin();exit;}
if(isset($_GET["rules-move"])){rules_move();exit;}
if(isset($_POST["UfdbGuardHTTPDisableHostname"])){rule_skin_save();exit;}
if(isset($_GET["test-smtp-js"])){smtp_parameters_test();exit;}
if(isset($_GET["smtp-sendto"])){smtp_parameters_lauch();exit;}
if(isset($_GET["smtp_sendto"])){tests_smtp();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["smtp-messages"])){smtp_messages();exit;}
page();

function sync(){
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules"));
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return;
    }
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
	$tpl->js_dialog2($email, "$page?smtp_sendto=$email");
	
}
function rule_delete_js(){
	$tpl=new template_admin();
	$aclid=$_GET["delete-rule-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_page_rules WHERE zmd5='$aclid'");
	$title="{rule}: {$ligne["rulename"]}";
	$md=$aclid;
	$jsafet="$('#$md').remove();";
	$tpl->js_confirm_delete($title, "delete-rule", $aclid,$jsafet);

	
}
function rule_delete():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ID=$_POST["delete-rule"];
	$q->QUERY_SQL("DELETE FROM `ufdb_page_rules` WHERE zmd5='$ID'");
	$mem=new lib_memcached();
	$mem->saveKey("ufdb_page_rules_l",false);
	$mem->Delkey("ufdb_page_rules_$ID");

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return admin_tracks("Removed Proxy Web error page rule #$ID");
}
function smtp_messages():bool{
    $md5=$_GET["smtp-messages"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM ufdb_page_rules WHERE zmd5='$md5'");
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
    $tpl->CLEAN_POST();
    $md5=$_POST["smtp-messages"];
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $smtp_ticket1_subj=$q->sqlite_escape_string2($_POST["smtp_ticket1_subj"]);
    $smtp_ticket1_body=$q->sqlite_escape_string2($_POST["smtp_ticket1_body"]);
    $q->QUERY_SQL("UPDATE `ufdb_page_rules` SET smtp_ticket1_subj='$smtp_ticket1_subj',smtp_ticket1_body='$smtp_ticket1_body' WHERE zmd5='$md5'");
    if(!$q->ok){
        echo $tpl->popup_error($q->mysql_error);
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
			$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
			$ligne=$q->mysqli_fetch_array("SELECT * FROM ufdb_page_rules WHERE zmd5='$md5'");
			$mem->saveKey("ufdb_page_rules_$md5", serialize($ligne));
		}
			
		$title="{rule}: {$ligne["rulename"]}";
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
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne = $q->mysqli_fetch_array("SELECT aclname,zTemplate FROM webfilters_sqacls WHERE ID=$ID");
    return $ligne["aclname"];

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

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results = $q->QUERY_SQL("SELECT ID,aclname,zTemplate FROM webfilters_sqacls ORDER BY xORDER");
    $ACLS_RULES=array();
    foreach($results as $index=>$ligne) {
        $zTemplate=unserializeb64($ligne["zTemplate"]);
        if(!isset($zTemplate["ENABLE_ERROR_PAGE"])){continue;}
        if(intval($zTemplate["ENABLE_ERROR_PAGE"])==0){continue;}
        $ACLS_RULES[$ligne["ID"]]=$ligne["aclname"];
    }

	
	
	$BootstrapDialog="BootstrapDialog1.close();LoadAjax('table-loader-webhttp-rules','$page?table=yes');";
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

	
	if($md5<>null){
        $sql="SELECT * FROM ufdb_page_rules WHERE zmd5='$md5'";
        VERBOSE($sql,__LINE__);
		$ligne=$q->mysqli_fetch_array($sql);
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
	$form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"],false);
	$form[]=$tpl->field_templates("templateid","{template}",$ligne["templateid"]);
	$form[]=$tpl->field_section("{source}");
	$form[]=$tpl->field_array_hash($LANGS, "lang", "{language}", $ligne["lang"]);
	
	if($EnableUfdbGuard==1){
		$form[]=$tpl->field_webfiltering_rules("webruleid","{webfiltering_rule}",$ligne["webruleid"]);
	}else{
        $tpl->field_hidden("webruleid", 0);
	}
    $form[]=$tpl->field_array_hash($ACLS_RULES,"aclid","{ACLS} <small>({APP_PROXY})</small>",$ligne["aclid"]);
	$form[]=$tpl->field_categories_list("category:ALL","{category}",intval($ligne["category"]));
	
	$form[]=$tpl->field_cdir("network", "{network2}", $ligne["network"]);
    $form[]=$tpl->field_text("username", "{username}", $ligne["username"]);
	$form[]=$tpl->field_choose_adgroupDN("adgroup","{active_directory_group}",$ligne["adgroup"]);

    $form[]=$tpl->field_section("API");
    $form[]=$tpl->field_checkbox("postapi","POST API",intval($ligne["postapi"]));
    $form[]=$tpl->field_text("postapiurl","POST URL",trim($ligne["postapiurl"]));

    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/web-error/postapi',1024,768,'WIKI')";
    $form[]=$tpl->field_button("help","{see_wiki}",$jshelp);
    $form[]=$tpl->field_section("{action}");
	$form[]=$tpl->field_checkbox("notify","{write_events}",$ligne["notify"]);
	$form[]=$tpl->field_checkbox("allow","{allow_unlock}",$ligne["allow"]);
	$form[]=$tpl->field_choose_periods("maxtime","{unlock_during}",intval($ligne["maxtime"]));
    $form[]=$tpl->field_checkbox("ticket","{allow_create_ticket}",$ligne["ticket"]);
    $form[]=$tpl->field_checkbox("ticket2","{allow_create_ticket2}",$ligne["ticket2"]);
    $form[]=$tpl->field_text("recipients", "{recipients}", $ligne["recipients"]);

    if($EnableUfdbGuard==1) {
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePersonalCategories") == 1)) {
            $form[] = $tpl->field_personal_categories_list("addTocat", "{automatically_add_to}", $ligne["addTocat"]);
        } else {
            $tpl->field_hidden("addTocat", 0);
        }
    }

	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,"$BootstrapDialog","AsDansGuardianAdministrator");
}




function rule_options_save(){
	$tpl=new template_admin();

    if(intval($_POST["allow"])==1){$_POST["deny"]=0;}
    if(intval($_POST["allow"])==0){$_POST["deny"]=1;}
    $NullFields=array("addTocat","username","adgroup");
    foreach ($NullFields as $key){
        if(!isset($_POST[$key])){
            $_POST[$key]="";
        }
    }

	$fields=$tpl->CLEAN_POST_XSS("rule-options");
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	if(!$users->AsDansGuardianAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

	
	$md5=trim($_POST["rule-options"]);
	if(strlen($md5)<10){$md5=null;}

	if($md5==null){
		$fields["FIELDS_ADD"][]="`zmd5`";
		$fields["VALUES_ADD"][]="'".md5(serialize($_POST))."'";


		$sql="INSERT INTO ufdb_page_rules (" .@implode( ",",$fields["FIELDS_ADD"]).") VALUES (" .@implode( ",",$fields["VALUES_ADD"]).")";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);}
	}else{
		$sql="UPDATE ufdb_page_rules SET ".@implode(",",$fields["EDIT"])." WHERE zmd5='$md5'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);}
	}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();

}

function rule_skin():bool{

	$tpl=new template_admin();
    $zmd5=$_GET["skin"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='{$_GET["skin"]}'";
	$ligne=$q->mysqli_fetch_array($sql);

    foreach ($ligne as $key=>$val){
        if(!is_numeric($key)){
            $ligne[$key]=$val;
        }
    }
	
	if(!$q->ok){echo $q->mysql_error_html();}
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
    if (PHP_MAJOR_VERSION<8) {
        $encode = true;
    }else{
        $encode=false;

    }
    $fields = $tpl->CLEAN_POST("rule-skin", $encode);
    $zmd5=$_POST["rule-skin"];
	$sql="UPDATE ufdb_page_rules SET ".@implode(",",$fields["EDIT"])." WHERE zmd5='$zmd5'";
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}
    admin_tracks("Modify the skin of the Web error page ID $zmd5");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return true;
}

function rule_tab():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=trim($_GET["rule-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	if($id<>null){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM ufdb_page_rules WHERE zmd5='$id'");
		$array["{$ligne["rulename"]}"]="$page?rule-options=$id";
		$refresh_js="LoadAjaxSilent('proxy-pac-rule-sources','fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_sources_link');";
		$refresh_enc=base64_encode("LoadAjax('table-loader-webhttp-rules','$page?table=yes');");
		$array["{skin}"]="$page?skin=$id";
        $array["{messages} (SMTP)"]="$page?smtp-messages=$id";
		
		
	}else{
		$array["{new_rule}"]="$page?rule-options=";
	}
	
	
	echo $tpl->tabs_default($array);
    return true;
	
	
}

function table(){
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=True;
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$users=new usersMenus();
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$automatically_add_to=$tpl->javascript_parse_text("{automatically_add_to}");
	$everyone=$tpl->javascript_parse_text("{everyone}");

	$qTMPL=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$results = $qTMPL->QUERY_SQL("SELECT TemplateName,ID FROM templates_manager");
	foreach ($results as $index=>$ligne){
		$TemplateName=$tpl->utf8_encode($ligne["TemplateName"]);
		$ID=$ligne["ID"];
		$XTPLS[$ID]=$TemplateName;
	}	
	$TRCLASS=null;
	$add="Loadjs('$page?ruleid-js=0',true);";
	if(!$users->AsDansGuardianAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("$add",ico_plus,"{new_rule}");
    }
    $topbuttons[] = array("Loadjs('$page?sync=yes')",ico_refresh,"{synchronize}");

	$html[]="<table id='table-proxypac-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>{description}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if($q->COUNT_ROWS("ufdb_page_rules")==0){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$explain_network=" {and} {for_all_networks}";
		$explain_category=" {and_for_all_categories}";
		$adgroup=" {and} <strong>$everyone</strong>";
		$explain[]="{display_error_page_using_template} <strong>{$XTPLS[1]}</strong>";
		$explain[]="{for_all_webfiltering_rules}$explain_category$explain_network$adgroup";
		$final_text=$tpl->utf8_encode($tpl->_ENGINE_parse_body(@implode("<br>", $explain)));
		
		$html[]="<tr class='$TRCLASS' id='null'>";
		$html[]="<td><H3>{default}</H3>$final_text</td>";
		$html[]="<td style='width:1%;' class='center' nowrap>". $tpl->icon_nothing() ."</center></td>";
		$html[]="</tr>";

		
	}else{

		$results=$q->QUERY_SQL("SELECT * FROM ufdb_page_rules ORDER BY zorder");
		$TRCLASS=null;
		
		
		
		foreach ($results as $index=>$ligne){
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$zmd5=$ligne["zmd5"];
			$category=intval($ligne["category"]);
			$webruleid=intval($ligne["webruleid"]);
			$deny=intval($ligne["deny"]);
			$adgroup=$ligne["adgroup"];
			$username=$ligne["username"];
			$maxtime=$ligne["maxtime"];
			$notify=intval($ligne["notify"]);
			$allow=intval($ligne["allow"]);
			$automatically_add_to_text=null;
			$network=$ligne["network"];
			$explain=array();
            $aclid=intval($ligne["aclid"]);
			$intro="{display_error_page_using_template}";
			$for_all_webfiltering_rules="{for_all_webfiltering_rules}";
			if($network=="0.0.0.0/0"){$network=null;}
			if($adgroup=="*"){$adgroup=null;}
			if($adgroup<>null){$adgroup="{group2} $adgroup";}
            $addTocat=intval($ligne["addTocat"]);
            $ligne["templateid"]=intval($ligne["templateid"]);
			
			if($ligne["templateid"]==0){$ligne["templateid"]=1;}
			$explain[]="$intro {$XTPLS[$ligne["templateid"]]}</a>";
			
			if($username<>null){$adgroup=$username;}
			if($addTocat>0){
				$addTocat_name=$tpl->CategoryidToName($addTocat);
				$automatically_add_to_text=" {and} $automatically_add_to <strong>$addTocat_name</strong>";
            }

			$unlocktime=" {for_unlimited_time} (10 {years})";

            if($maxtime>0){
                $unlocktime=" <i style='font-weight:bold'>( {unlock_during} $maxtime {minutes} )</i>";
            }
			
			if($adgroup<>null){
				$adgroup=" {and_memberss} <strong>$adgroup</strong>";
			}else{
				$adgroup=" {and} <strong>$everyone</strong>";
			}
			
			if($network==null){
				$explain_network=" {and} {for_all_networks}";
			}else{
				$explain_network=" {and} {for_network}: <strong>$network</strong>";
			}
			
			
			if($category==0){
				$explain_category=" {and_for_all_categories}";
			}else{
				$explain_category=" {and_category} <strong>".$tpl->CategoryidToName($category)."</strong>";
			}
			
			if($category=="itchart"){$explain_category=null;}
			
			
			if($aclid>0){
                $webruleid=0;
                $for_all_webfiltering_rules="{acl} <strong>".acl_rule_name($aclid)."</strong>";
            }
			
			if($webruleid==0){
				$explain[]="$for_all_webfiltering_rules$explain_category$explain_network$adgroup";
			}else{
				$ligne2=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_rules WHERE ID=$webruleid");
				$explain[]="{only_for_webfrule} ($webruleid) <strong>".$tpl->utf8_encode($ligne2["groupname"])."</strong>$explain_category";
			}
			
			
			
			if($ligne["ticket"]==1){
				$allow=0;
				$deny=0;
                $unlocktime=null;
				if($ligne["ticket2"]==0){
					$explain[]="{allow_users_to_notify_the_ITTeam}$unlocktime$automatically_add_to_text";
				}else{
					$explain[]="{allow_users_to_notify_the_ITTeam2}$unlocktime$automatically_add_to_text";
				}
			}
			
			if($allow==1){
				$deny=0;
				$explain[]="{then_allow_user_to_whitelist_theblocked}$unlocktime$automatically_add_to_text";
			}
			
			if($deny==1){
				$allow=0;
				$explain[]="{then_just_display_the_error_page}";
			}
			

			
			if(preg_match("#EXTLDAP:(.+)#", $adgroup,$re)){
                $CountOfUsers=0;
				$ldap=new ldap_extern();
				$hash=$ldap->DNInfos($re[1]);
				$DNENC=urlencode($re[1]);
			if(isset($hash[0]["cn"])){
				$adgroup=$hash[0]["cn"][0];
				if(isset($hash[0][$ldap->ldap_filter_group_attribute]["count"])){
				$CountOfUsers=" (<a href=\"javascript:blur();\" OnClick=\"Loadjs('browse-extldap-users.php?DN=$DNENC');\" style='text-decoration:underline'>".intval($hash[0][$ldap->ldap_filter_group_attribute]["count"])." {members}</a>)";
						}
				if(isset($hash[0]["description"])){
					$description="<br><i>{$hash[0]["description"][0]}</i>";
				}

				$adgroup=$tpl->_ENGINE_parse_body("{ldap_group}: $adgroup $CountOfUsers$description");

            }
		
		}
			
		if($notify==1){
				$explain[]="{finally_write_event_to_notify_administrator}";
		}

		$js="Loadjs('$page?ruleid-js=$zmd5')";
		$final_text=$tpl->utf8_encode($tpl->_ENGINE_parse_body(@implode("<br>", $explain)));
		if($ligne["rulename"]==null){$ligne["rulename"]="{rule}:{unknown}";}
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><H3>". $tpl->td_href($ligne["rulename"],null,$js)."</H3>$final_text</td>";
		$html[]="<td style='width:1%;' class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$zmd5')","AsDansGuardianAdministrator") ."</center></td>";
		$html[]="</tr>";
	
		}

	}
	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{block_pages_behavior}";
    $TINY_ARRAY["ICO"]="fas fa-list-ul";
    $TINY_ARRAY["EXPL"]="{block_pages_behavior_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-proxypac-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	$jstiny
</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

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
		echo $msmtp->logs;
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("Test Message\nTo $recipient: {success}")."');</script>";
		return;
	}


	echo $msmtp->logs."\n";
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
