<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
include_once(dirname(__FILE__)."/ressources/class.spf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : id et mynetworks-edit-js etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["id"])){$_GET["id"]=preg_replace('#[^A-Za-z0-9_-]#','',(string)$_GET["id"]);}
if(isset($_GET["mynetworks-edit-js"])){$_GET["mynetworks-edit-js"]=intval($_GET["mynetworks-edit-js"]);}
if(isset($_GET["mynetworks"])){mynetworks();exit;}
if(isset($_GET["mynetwork-table"])){mynetworks_table();exit;}
if(isset($_GET["mynetwork-js"])){mynetworks_js();exit;}
if(isset($_GET["mynetwork-popup"])){mynetworks_popup();exit;}
if(isset($_POST["mynetworks-ipv4"])){mynetworks_save();exit;}
if(isset($_POST["mynetworks-cdir"])){mynetworks_save();exit;}
if(isset($_GET["mynetworks-delete"])){mynetworks_delete();exit;}
if(isset($_GET["mynetworks-edit-js"])){mynetworks_edit_js();exit;}
if(isset($_POST["mynetworks-delete"])){mynetworks_delete_perform();exit;}
if(isset($_POST["mynetworks-description"])){mynetworks_description_save();exit;}
if(isset($_GET["mynetwork-import-js"])){mynetworks_import_js();exit;}
if(isset($_GET["mynetwork-import-popup"])){mynetworks_import_popup();exit;}
if(isset($_POST["mynetwork-import"])){mynetworks_import_save();exit;}

if(isset($_GET["mynetwork-export-js"])){mynetworks_export_js();exit;}
if(isset($_GET["mynetwork-export-popup"])){mynetworks_export_popup();exit;}



if(isset($_GET["routing"])){routing_popup();exit;}
if(isset($_GET["routing-table"])){routing_table();exit;}
if(isset($_GET["routing-js"])){routing_js();exit;}
if(isset($_GET["routing-entry"])){routing_entry();exit;}
if(isset($_GET["routing-enable"])){routing_enable();exit;}
if(isset($_GET["routing-delete"])){routing_delete();exit;}
if(isset($_POST["routing-delete"])){routing_delete_perform();exit;}
if(isset($_GET["routing-domains"])){routing_domains();exit;}
if(isset($_POST["OtherDomains"])){routing_domains_save();exit;}
if(isset($_GET["routing-params"])){routing_params();exit;}
if(isset($_POST["nexthope"])){routing_save();exit;}
if(isset($_GET["routing-spf"])){routing_spf();exit;}


if(isset($_GET["routing-bcc"])){routing_bcc();exit;}
if(isset($_GET["routing-bcc-popup"])){routing_bcc_popup();exit;}


if(isset($_GET["tabs"])){tabs();exit;}

page();

// TLS relay modes (formerly lib_sqlite::$array_field_relay_tls) — kept local so this
// page no longer instantiates lib_sqlite just to read a static label map.
function routing_tls_modes():array{
    return array(
        "none"=>"{none}",
        "may"=>"{Opportunistic_TLS}",
        "dane"=>"{DANE_SMTP}",
        "dane-only"=>"{DANE_ONLY}",
        "encrypt"=>"{Mandatory_TLS_encryption}",
        "verify"=>"{Mandatory_TLS_verification}",
        "secure"=>"{Secure_channel_TLS}");
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);
    $html=$tpl->page_header("{routing_tables} v$POSTFIX_VERSION",
        "fas fa-bus",
        "{postfix_routing_tables_explain}",
        "$page?tabs=yes&instance-id=$instance_id",
        "postfix-routing",
        "progress-postfix-routing",false,
        "table-loader-postfix-routing"
    );

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function mynetworks_edit_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["mynetworks-edit-js"];
	$sock=new sockets();
	$json=json_decode($sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/mynetworks/get/0",array("id"=>$ID)),true);
	$ligne=isset($json["row"])?$json["row"]:array();
	$ipaddr=$ligne["addr"];
    $instance_id=intval($ligne["instanceid"]);
    $instancename="SMTP Master";
    if($instance_id>0){
        $j2=json_decode($sock->REST_API_POSTFIX("/postfix/routing/instance-name/$instance_id"),true);
        $instancename=$j2["name"];
    }

	$tpl->js_prompt("[$ID] $instancename:<br><strong>$ipaddr</strong>", "{description}", "fas fa-info-circle",$page,
			 "mynetworks-description","LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');",
			 $ligne["description"],$ID);
	
	
}

function mynetworks_import_js(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{mynetworks_title}:: {import}", "$page?mynetwork-import-popup=yes&instance-id=$instance_id");

}

function mynetworks_export_js(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{mynetworks_title}:: {export}", "$page?mynetwork-export-popup=yes&instance-id=$instance_id");
}

function mynetworks_import_popup(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();


    $instancename="SMTP Master";
    if($instance_id>0){
        $sock=new sockets();
        $j=json_decode($sock->REST_API_POSTFIX("/postfix/routing/instance-name/$instance_id"),true);
        $instancename=$j["name"];
    }

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_textareacode("mynetwork-import","{networks}",null,null);
    echo $tpl->form_outside("$instancename:{import}",$form,null,"{import}","dialogInstance1.close();LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
}

function mynetworks_export_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $sock=new sockets();
    $json=json_decode($sock->REST_API_POSTFIX("/postfix/routing/mynetworks/list/$instance_id"),true);
    $results=isset($json["rows"])?$json["rows"]:array();

    foreach ($results as $index=>$ligne){
        $description=$ligne["description"];
        $val[]=trim($ligne["addr"])."\t$description";

    }
    $tpl->field_hidden("instance_id",$instance_id);
    $tpl->field_hidden("FROMEX",1);
    $form[]=$tpl->field_textareacode("mynetwork-import","{networks}",@implode("\n",$val),null);
    echo $tpl->form_outside("{data}",$form,null,"{apply}","dialogInstance1.close();LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
}

function mynetworks_description_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["KeyID"];
	$sock=new sockets();
	$sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/mynetworks/description/0",
		array("id"=>$ID,"description"=>$_POST["mynetworks-description"]));

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	if(!extension_loaded("pdo_sqlite")){
		echo $tpl->FATAL_ERROR_SHOW_128("{Warninphp70sqlite3}");
		return false;
	}
	
	$array["{routing}"]="$page?routing=yes&instance-id=$instance_id";
	$array["{mynetworks_title}"]="$page?mynetworks=yes&instance-id=$instance_id";
    $array["{office365_relay}"]="fw.postfix.office365.php?build-page=yes&instance-id=$instance_id";
	
	echo $tpl->tabs_default($array);
}

function mynetworks_delete(){
	$tpl=new template_admin();
	$addr=$_GET["mynetworks-delete"];
    $instance_id=intval($_GET["instance-id"]);
	// Pentest 2026-11-04 : mynetworks-delete etait recopie sans encodage dans la page (XSS).
	$tpl->js_confirm_delete(req_html($addr), "mynetworks-delete", "$addr||$instance_id","$('#{$_GET["id"]}').remove()");
}
function routing_delete(){
	$tpl=new template_admin();
	$addr=$_GET["routing-delete"];
    $instance_id=intval($_GET["instance-id"]);
	// Pentest 2026-11-04 : routing-delete etait recopie sans encodage dans la page (XSS).
	$tpl->js_confirm_delete(req_html($addr), "routing-delete", "$addr||$instance_id","$('#{$_GET["id"]}').remove()");
}
function routing_delete_perform(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    $value=explode("||",$_POST["routing-delete"]);
    $addr=$value[0];
    $instance_id=intval($value[1]);
	$sock=new sockets();
	$sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/delete/$instance_id",array("addr"=>$addr));
}

function routing_bcc(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$value=$_GET["routing-bcc"];
	// Pentest 2026-11-04 : routing-bcc etait recopie sans encodage dans la page (XSS).
	if($value==null){$title="{new_bcc}";}else{$title=req_html(req_html($value));}
	$valueenc=urlencode($value);
    $instance_id=intval($_GET["instance-id"]);
	if(preg_match("#BCC:(.+)#", $value,$re)){$title="{BCC} ".req_html(req_html($re[1]));}
	$tpl->js_dialog1("{routing_table}:: $title", "$page?routing-bcc-popup=$valueenc&instance-id=$instance_id");
	
}

function routing_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$value=$_GET["routing-js"];
	// Pentest 2026-11-04 : routing-js etait recopie sans encodage dans la page (XSS).
	if($value==null){$title="{new_entry}";}else{$title=req_html(req_html($value));}
	$valueenc=urlencode($value);
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{routing_table}:: $title", "$page?routing-entry=$valueenc&instance-id=$instance_id");
    return true;
	
}

function mynetworks_delete_perform():bool{
    $value=explode("||",$_POST["mynetworks-delete"]);
    $addr=$value[0];
    $instance_id=intval($value[1]);
    $sock=new sockets();
    $sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/mynetworks/delete/$instance_id",array("addr"=>$addr));
    $sock->REST_API_POSTFIX("/postfix/mynetwork/$instance_id");
    return true;
}

function mynetworks_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{mynetworks_title}:: {new_address}", "$page?mynetwork-popup=&instance-id=$instance_id");
    return true;
}
function mynetworks_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $instancename=null;
    if($instance_id>0){
        $sock=new sockets();
        $j=json_decode($sock->REST_API_POSTFIX("/postfix/routing/instance-name/$instance_id"),true);
        $instancename=" <small>({$j["name"]})</small>";
    }


    $instance_id=intval($_GET["instance-id"]);
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_ipv4("mynetworks-ipv4", "{new_address}", null);
	$form[]=$tpl->field_cdir("mynetworks-cdir", "{new_range}", null);
	$form[]=$tpl->field_text("description", "{description}", null);
	echo $tpl->form_outside("{mynetworks_title}$instancename", $form,"{mynetworks_text}","{add}",
			"dialogInstance1.close();LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
}
function routing_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$addr=$_POST["addr"];
	$direction=$_POST["direction"];
	$service=$_POST["service"];
	$nexthope=$_POST["nexthope"];
	$nextport=$_POST["nextport"];
	$enabled=$_POST["enabled"];
	$tls_enabled=$_POST["tls_enabled"];
	$tls_mode=$_POST["tls_mode"];
    if(!isset($_POST["instance_id"])){
        echo $tpl->post_error("Need instance id");
        return false;
    }
    $instance_id=intval($_POST["instance_id"]);
	
	$auth=$_POST["auth"];
	$username=$_POST["username"];
	$password=$_POST["password"];
    $client_certificate=$_POST["client_certificate"];
    $certificate=$_POST["certificate"];

	
	if($service=="BCC"){
		if(!preg_match("#BCC:(.+)#", $addr)){$addr="BCC:$addr";}
		
	}
	
	$payload=array(
		"addr"=>$addr,
		"direction"=>$direction,
		"service"=>$service,
		"enabled"=>$enabled,
		"nexthope"=>$nexthope,
		"nextport"=>$nextport,
		"tls_enabled"=>$tls_enabled,
		"tls_mode"=>$tls_mode,
		"auth"=>$auth,
		"username"=>$username,
		"password"=>$password,
		"client_certificate"=>$client_certificate,
		"certificate"=>$certificate
	);
	if(isset($_POST["relay_domains_restricted"])){
		$payload["relay_domains_restricted"]=intval($_POST["relay_domains_restricted"]);
	}
	if(isset($_POST["recipient_ad_check"])){
		$payload["recipient_ad_check"]=intval($_POST["recipient_ad_check"]);
		$payload["recipient_ad_cnx"]=intval($_POST["recipient_ad_cnx"] ?? 0);
	}
	$sock=new sockets();
	$sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/save/$instance_id",$payload);

	
	return admin_tracks("SMTP: Saving new domain $service->$addr $nexthope:$nextport");
}

function routing_enable(){
	header("content-type: application/x-javascript");
	$addr=$_GET["routing-enable"];
    $instance_id=intval($_GET["instance-id"]);
	$sock=new sockets();
	$sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/enable/$instance_id",array("addr"=>$addr));
}


function routing_bcc_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$value=$_GET["routing-bcc-popup"];
	$button="{add}";
	$title="{new_bcc}";
	$array_direction[0]="{inbound}";
	$array_direction[1]="{outbound}";
	$js="dialogInstance1.close();";
	$sock=new sockets();
	$ligne=array();
	$form[]=$tpl->field_hidden("service", "BCC");
	if($value==null){
		$form[]=$tpl->field_text("addr", "{email}", null,true,"{transport_email_explain}");
	}else{
		$json=json_decode($sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/get/$instance_id",array("addr"=>$value)),true);
		$ligne=isset($json["row"])?$json["row"]:array();
		// Pentest 2026-11-04 : routing-bcc-popup etait recopie sans encodage dans la page (XSS).
		$form[]=$tpl->field_hidden("addr", $value);
		if(preg_match("#BCC:(.+)#", $value,$re)){$title="{BCC} ".req_html($re[1]);}

		$button="{apply}";

	}
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$form[]=$tpl->field_array_hash($array_direction, "direction", "{direction}", intval($ligne["direction"]));
	$form[]=$tpl->field_text("nexthope", "{bcc_to}", $ligne["nexthope"],false);
	
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
	echo $tpl->form_outside("{routing_rule}: $title", $form,"",$button,"$js;LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	
	
}

function routing_entry(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $value = $_GET["routing-entry"];
    $instance_id=intval($_GET["instance-id"]);
    $valueencoded=urlencode($value);
    $array["{parameters}"]="$page?routing-params=$valueencoded&instance-id=$instance_id";
    if($value<>null) {
        $array["{identical_domains}"] = "$page?routing-domains=$valueencoded&instance-id=$instance_id";
        $array["SPF"] = "$page?routing-spf=$valueencoded&instance-id=$instance_id";
    }
    echo $tpl->tabs_default($array);

}

function routing_domains(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $value = $_GET["routing-domains"];
    $instance_id=intval($_GET["instance-id"]);
    $sock=new sockets();
    $json=json_decode($sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/get/$instance_id",array("addr"=>$value)),true);
    $ligne=isset($json["row"])?$json["row"]:array();
    $HASH=unserialize(base64_decode($ligne["OtherDomains"]));
    if(!is_array($HASH)){$HASH=array();}

    foreach ($HASH as $domain=>$none){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        $f[]=$domain;

    }

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_hidden("domainkey",$value);
    $form[]=$tpl->field_textareacode("OtherDomains",null,@implode("\n",$f));
    echo $tpl->form_outside("{domains}", $form,"{identical_domains_explain}","{apply}","LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");

}

function routing_domains_save(){

    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $domainkey=$_POST["domainkey"];
    $instance_id=intval($_POST["instance_id"]);
    $domains=array();
    foreach (explode("\n",$_POST["OtherDomains"]) as $domain){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        $domains[]=$domain;
    }
    $sock=new sockets();
    $sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/domains/$instance_id",
        array("addr"=>$domainkey,"domains"=>$domains));

    return admin_tracks("SMTP: Saving domain $domainkey/$domains");
}

function routing_params(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $postfixBin=$GLOBALS["CLASS_SOCKETS"]->Postfix_VersionBin();


    $instance_id=intval($_GET["instance-id"]);
    $value = $_GET["routing-params"];
	$button="{add}";
	$title="{new_entry}";
	$array_direction[0]="{inbound}";
	$array_direction[1]="{outbound}";
	$js="dialogInstance1.close();";
	
	$arra_service["smtp"]="SMTP";
	$arra_service["error"]="{error}";
	
	
	
	$sock=new sockets();
	$ligne=array();
    $form[]=$tpl->field_hidden("instance_id", $instance_id);
	if($value==null){
		$form[]=$tpl->field_text("addr", "{destination_domain_or_recipient}", null,true,"{transport_email_explain}");
	}else{
		$json=json_decode($sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/get/$instance_id",array("addr"=>$value)),true);
		$ligne=isset($json["row"])?$json["row"]:array();
		// Pentest 2026-11-04 : routing-params etait recopie sans encodage dans la page (XSS).
		$form[]=$tpl->field_hidden("addr", $value);
		$title=req_html($value);
		$button="{apply}";

	}

	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!isset($ligne["service"])){$ligne["service"]="smtp";}
	if(intval($ligne["nextport"])==0){$ligne["nextport"]=25;}
	if($ligne["service"]==null){$ligne["service"]="smtp";}
	$array_field_relay_tls=routing_tls_modes();

	if(strpos(" $value", "@")==0){
		$relay_domains_restricted=intval($ligne["relay_domains_restricted"] ?? 0);
		$form[]=$tpl->field_checkbox("relay_domains_restricted","{reject_unverified_recipient}",
                $relay_domains_restricted,false,"{reject_unverified_recipient_text}");
		// AD recipient validation (relay_recipient_maps): only offered when an AD connection exists.
		if(routing_has_ad_connections()){
			$form[]=$tpl->field_checkbox("recipient_ad_check","{validate_recipients_in_ad}",
				intval($ligne["recipient_ad_check"] ?? 0),"recipient_ad_cnx","{validate_recipients_in_ad_text}");
			$form[]=$tpl->field_activedirectory_cnxs("recipient_ad_cnx","{active_directory_connection}",
				intval($ligne["recipient_ad_cnx"] ?? 0));
		}
	}
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_array_hash($array_direction, "direction", "{direction}", intval($ligne["direction"]));
	$form[]=$tpl->field_array_hash($arra_service, "service", "{service2}", $ligne["service"],true);
	$form[]=$tpl->field_text("nexthope", "{relay_address}", $ligne["nexthope"],false);
	$form[]=$tpl->field_numeric("nextport","{port}", $ligne["nextport"]);
	$form[]=$tpl->field_section("{tls_title}");
	$form[]=$tpl->field_checkbox("tls_enabled","{smtp_use_tls}",$ligne["tls_enabled"],false);
	$form[]=$tpl->field_array_hash($array_field_relay_tls, "tls_mode", "{tls_label}", $ligne["tls_mode"]);
    if($postfixBin>356) {
            $form[] = $tpl->field_checkbox("client_certificate","{client_certificate}",$ligne["client_certificate"]);
            $form[] = $tpl->field_certificate("certificate","{use_certificate_from_certificate_center}",
                $ligne["certificate"]);

    }


	$form[]=$tpl->field_section("{authenticate}");
	$form[]=$tpl->field_checkbox("auth","{enabled}",$ligne["auth"],false);
	$form[]=$tpl->field_text("username", "{username}", $ligne["username"],false);
	$form[]=$tpl->field_password2("password", "{password}", $ligne["password"]);

    if($postfixBin<357){
        $html[]="<p>";
        $html[]=$tpl->div_error("{need_to_update_postfix357}");
        $html[]="</p>";
    }

	$html[]=$tpl->form_outside("", $form,"",$button,"$js;LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}


function mynetworks(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	echo "<div id='mynetworks-div' style='margin-top:20px'></div><script>LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');</script>";
}
function routing_popup(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	echo "<div id='routing-div' style='margin-top:20px'></div><script>LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');</script>";
}
function mynetworks_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$instance_id=intval($_POST["instance_id"]);
	$sock=new sockets();
	if($_POST["mynetworks-ipv4"]<>null){
		$sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/mynetworks/add/$instance_id",
			array("addr"=>$_POST["mynetworks-ipv4"],"description"=>$_POST["description"]));
	}
	if($_POST["mynetworks-cdir"]<>null){
		$sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/mynetworks/add/$instance_id",
			array("addr"=>$_POST["mynetworks-cdir"],"description"=>$_POST["description"]));
	}
	$sock->REST_API_POSTFIX("/postfix/mynetwork/$instance_id");
}

function mynetworks_import_save(){
    include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance_id"]);
    $sock=new sockets();

    $instancename="SMTP Master";
    if($instance_id>0){
        $j=json_decode($sock->REST_API_POSTFIX("/postfix/routing/instance-name/$instance_id"),true);
        $instancename=$j["name"];
    }

    $IP=new IP();
    $networks=array();
    $tb=explode("\n",$_POST["mynetwork-import"]);
    foreach ($tb as $line){
        $line=trim($line);
        $description="Instance: $instancename - Imported on ".$tpl->time_to_date(time());
        if(preg_match("#^(.+?)\s+(.+)#",$line,$re)){
            $ipaddr=$re[1];
            $description=$re[2];
        }else{
            $ipaddr=$line;
        }
        if(!$IP->IsACDIROrIsValid($ipaddr)){continue;}
        $networks[]=array("addr"=>$ipaddr,"description"=>$description);
    }
    if(count($networks)==0){return false;}
    $payload=array("networks"=>$networks);
    if(isset($_POST["FROMEX"])){$payload["replace"]=1;}
    $sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/mynetworks/import/$instance_id",$payload);
    if(function_exists("admin_tracks")) {
        admin_tracks("Imported " . count($networks) . " authorized networks for the SMTP relay $instancename");
    }
    $sock->REST_API_POSTFIX("/postfix/mynetwork/$instance_id");
    return true;

}


function mynetworks_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mynetwork-js=&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_address} </label>";

    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?mynetwork-import-js=yes&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {import} </label>";

    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mynetwork-export-js=yes&instance-id=$instance_id');\">";
    $btn[]="<i class='fas fa-file-csv'></i> {export} </label>";
	$btn[]="</div>";

    // Enhanced "Systematic Mail Admin" card layout (Stitch design) — inline CSS, FontAwesome icons.
    // Data + every action opener are unchanged; only the presentation is a card list.
    $html[]="<style>
    .cyx-wrap{--pri:#00685e;--pri2:#008377;--ora:#8e4e14;--ora2:#ffab69;--err:#ba1a1a;--ink:#191c1e;--mut:#3d4947;--out:#6d7a77;--bd:#bcc9c6;--bg2:#e6e8ea;--card:#fff;color:var(--ink)}
    .cyx-refresh{display:inline-block;background:#e6f6f3;color:var(--pri);border:1px solid #bfe6df;padding:4px 12px;border-radius:9999px;font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;margin-bottom:10px}
    .cyx-head{display:grid;grid-template-columns:1fr auto;gap:18px;padding:4px 20px 2px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--out)}
    .cyx-head .c{text-align:center}.cyx-head .r{text-align:right}
    .cyx-list{display:flex;flex-direction:column;gap:12px;margin-top:6px}
    .cyx-card{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center;background:var(--card);border:1px solid var(--bd);border-radius:16px;padding:15px 20px;transition:border-color .15s,box-shadow .15s}
    .cyx-card:hover{border-color:var(--pri);box-shadow:0 8px 18px -8px rgba(0,0,0,.18)}
    .cyx-user{display:flex;align-items:center;gap:14px;min-width:0}
    .cyx-av{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex:0 0 auto}
    .cyx-av-ok{background:rgba(0,104,94,.10);color:var(--pri)}
    .cyx-av-off{background:rgba(186,26,26,.10);color:var(--err)}
    .cyx-name{font-weight:600;font-size:15px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cyx-email{font-size:12.5px;color:var(--mut);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cyx-c{text-align:center}
    .cyx-chip{display:inline-block;padding:3px 11px;border-radius:9999px;font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
    .cyx-chip-ok{background:rgba(0,104,94,.10);color:var(--pri)}
    .cyx-chip-off{background:rgba(186,26,26,.10);color:var(--err)}
    .cyx-chip-none{background:#eceef0;color:var(--out)}
    .cyx-msg{font-weight:600;font-size:16px}
    .cyx-quota .p{font-size:12px;font-weight:700;font-family:'JetBrains Mono',monospace;display:block;margin-bottom:5px;text-align:left}
    .cyx-bar{width:100%;height:8px;background:var(--bg2);border-radius:9999px;overflow:hidden}
    .cyx-bar i{display:block;height:100%;border-radius:9999px}
    .cyx-q-ok{color:var(--pri)}.cyx-q-ok i{background:var(--pri2)}
    .cyx-q-warn{color:var(--ora)}.cyx-q-warn i{background:var(--ora2)}
    .cyx-q-crit{color:var(--err)}.cyx-q-crit i{background:var(--err)}
    .cyx-active{display:flex;gap:12px;justify-content:center;font-size:17px}
    .cyx-active a,.cyx-active span{color:var(--out);text-decoration:none}
    .cyx-on{color:var(--pri) !important}
    .cyx-actions{display:flex;gap:3px;justify-content:flex-end;flex-wrap:wrap}
    .cyx-actions a{width:32px;height:32px;display:flex;align-items:center;justify-content:center;color:var(--mut);border-radius:8px;text-decoration:none;transition:color .12s,background .12s;font-size:14px}
    .cyx-actions a:hover{color:var(--pri);background:rgba(0,104,94,.08)}
    .cyx-actions a.del{color:var(--err)}
    .cyx-actions a.del:hover{background:rgba(186,26,26,.10)}
    .cyx-empty{padding:40px;text-align:center;color:var(--out);border:1px dashed var(--bd);border-radius:16px}
    @media(max-width:900px){.cyx-head{display:none}.cyx-card{grid-template-columns:1fr;gap:10px}.cyx-c,.cyx-active,.cyx-actions{justify-content:flex-start}}
    </style>";

    $html[]="<div class='cyx-wrap'>";
    // Lightweight client-side filter — replaces footable's built-in search box (footable was dropped
    // with the card conversion); shows/hides cards by text match, no server round-trip.
    $html[]="<div style='margin:2px 0 6px'><input type='text' oninput=\"cyxFilter(this,'cyxlist-mynet')\" placeholder='{search}...' style='width:100%;max-width:380px;padding:9px 16px;border:1px solid #bcc9c6;border-radius:9999px;font-size:13px;outline:none'></div>";
    $html[]="<div class='cyx-head'>";
    $html[]="<div>{networks}</div><div class='r'>{delete}</div>";
    $html[]="</div><div class='cyx-list' id='cyxlist-mynet'>";

	$sock=new sockets();
	$json=json_decode($sock->REST_API_POSTFIX("/postfix/routing/mynetworks/list/$instance_id"),true);
	$results=isset($json["rows"])?$json["rows"]:array();

    $shown=0;
	foreach ($results as $num=>$ligne){
        $ID=$ligne["ID"];
		$val=trim($ligne["addr"]);
		if($val==null){continue;}
		$id=md5($val);
        $desc=trim((string)$ligne["description"]);
        $shown++;

        $html[]="<div class='cyx-card' id='$id'>";
        $html[]="<div class='cyx-user'><div class='cyx-av cyx-av-ok'><i class='fas fa-network-wired'></i></div><div style='min-width:0'><div class='cyx-name'>".htmlspecialchars($val)."</div>".($desc!==""?"<div class='cyx-email'>".htmlspecialchars($desc)."</div>":"")."</div></div>";
        $a=array();
        $a[]="<a href=\"javascript:Loadjs('$page?mynetworks-edit-js=$ID&instance-id=$instance_id')\" title='{edit}'><i class='fas fa-edit'></i></a>";
        // Pentest 2026-11-04 : addr (donnee de la base) dans une URL JS sans encodage (XSS stockee).
        $a[]=$tpl->icon_delete("Loadjs('$page?mynetworks-delete=".req_url($val)."&id=$id&instance-id=$instance_id')","AsPostfixAdministrator");
        $html[]="<div class='cyx-actions'>".@implode("",$a)."</div>";
        $html[]="</div>"; // card
	}
    if($shown===0){ $html[]="<div class='cyx-empty'>{postfix_none_network}</div>"; }
    $html[]="</div></div>"; // list, wrap

    $instancename=null;
    if($instance_id>0){
        $sock=new sockets();
        $j=json_decode($sock->REST_API_POSTFIX("/postfix/routing/instance-name/$instance_id"),true);
        $instancename=$j["name"];
    }

    // Pentest 2026-11-04 : instancename (donnee de la base) affiche sans encodage (XSS stockee).
    $TINY_ARRAY["TITLE"]="{mynetworks_title} <small>".req_html($instancename)."</small>";
    $TINY_ARRAY["ICO"]="fa fa-network-wired";
    $TINY_ARRAY["EXPL"]="{mynetworks_text}";
    $TINY_ARRAY["URL"]="postfix-routing";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	function cyxFilter(inp,listId){var q=inp.value.toLowerCase();var L=document.getElementById(listId);if(!L)return;var c=L.getElementsByClassName('cyx-card');for(var i=0;i<c.length;i++){c[i].style.display=(c[i].textContent.toLowerCase().indexOf(q)>=0)?'':'none';}}
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function routing_table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function="";
	$instance_id=intval($_GET["instance-id"]);
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }

    $jsrestart=$tpl->framework_buildjs(
         "postfix:/postfix/transport/$instance_id",
      "postfix.transport.$instance_id.progress",
        "postfix.transport.$instance_id.log",
         "progress-postfix-routing");


	//

	
	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?routing-js=&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_rule} </label>";

    $btn[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?routing-bcc=&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_bcc} </label>";

    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";

    $btn[]="</div>";

    // Enhanced "Systematic Mail Admin" card layout (Stitch design) — inline CSS, FontAwesome icons.
    // Data + every action opener are unchanged; only the presentation is a card list.
    $html[]="<style>
    .cyx-wrap{--pri:#00685e;--pri2:#008377;--ora:#8e4e14;--ora2:#ffab69;--err:#ba1a1a;--ink:#191c1e;--mut:#3d4947;--out:#6d7a77;--bd:#bcc9c6;--bg2:#e6e8ea;--card:#fff;color:var(--ink)}
    .cyx-refresh{display:inline-block;background:#e6f6f3;color:var(--pri);border:1px solid #bfe6df;padding:4px 12px;border-radius:9999px;font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;margin-bottom:10px}
    .cyx-head{display:grid;grid-template-columns:2.2fr .8fr 1.6fr .7fr auto;gap:18px;padding:4px 20px 2px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--out)}
    .cyx-head .c{text-align:center}.cyx-head .r{text-align:right}
    .cyx-list{display:flex;flex-direction:column;gap:12px;margin-top:6px}
    .cyx-card{display:grid;grid-template-columns:2.2fr .8fr 1.6fr .7fr auto;gap:18px;align-items:center;background:var(--card);border:1px solid var(--bd);border-radius:16px;padding:15px 20px;transition:border-color .15s,box-shadow .15s}
    .cyx-card:hover{border-color:var(--pri);box-shadow:0 8px 18px -8px rgba(0,0,0,.18)}
    .cyx-user{display:flex;align-items:center;gap:14px;min-width:0}
    .cyx-av{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex:0 0 auto}
    .cyx-av-ok{background:rgba(0,104,94,.10);color:var(--pri)}
    .cyx-av-off{background:rgba(186,26,26,.10);color:var(--err)}
    .cyx-name{font-weight:600;font-size:15px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cyx-email{font-size:12.5px;color:var(--mut);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cyx-c{text-align:center}
    .cyx-chip{display:inline-block;padding:3px 11px;border-radius:9999px;font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
    .cyx-chip-ok{background:rgba(0,104,94,.10);color:var(--pri)}
    .cyx-chip-off{background:rgba(186,26,26,.10);color:var(--err)}
    .cyx-chip-none{background:#eceef0;color:var(--out)}
    .cyx-msg{font-weight:600;font-size:16px}
    .cyx-quota .p{font-size:12px;font-weight:700;font-family:'JetBrains Mono',monospace;display:block;margin-bottom:5px;text-align:left}
    .cyx-bar{width:100%;height:8px;background:var(--bg2);border-radius:9999px;overflow:hidden}
    .cyx-bar i{display:block;height:100%;border-radius:9999px}
    .cyx-q-ok{color:var(--pri)}.cyx-q-ok i{background:var(--pri2)}
    .cyx-q-warn{color:var(--ora)}.cyx-q-warn i{background:var(--ora2)}
    .cyx-q-crit{color:var(--err)}.cyx-q-crit i{background:var(--err)}
    .cyx-active{display:flex;gap:12px;justify-content:center;font-size:17px}
    .cyx-active a,.cyx-active span{color:var(--out);text-decoration:none}
    .cyx-on{color:var(--pri) !important}
    .cyx-actions{display:flex;gap:3px;justify-content:flex-end;flex-wrap:wrap}
    .cyx-actions a{width:32px;height:32px;display:flex;align-items:center;justify-content:center;color:var(--mut);border-radius:8px;text-decoration:none;transition:color .12s,background .12s;font-size:14px}
    .cyx-actions a:hover{color:var(--pri);background:rgba(0,104,94,.08)}
    .cyx-actions a.del{color:var(--err)}
    .cyx-actions a.del:hover{background:rgba(186,26,26,.10)}
    .cyx-empty{padding:40px;text-align:center;color:var(--out);border:1px dashed var(--bd);border-radius:16px}
    @media(max-width:900px){.cyx-head{display:none}.cyx-card{grid-template-columns:1fr;gap:10px}.cyx-c,.cyx-active,.cyx-actions{justify-content:flex-start}}
    </style>";

	$array_direction[0]="{inbound}";
	$array_direction[1]="{outbound}";
    $array_direction["local"]="{mailbox}";

    $html[]="<div class='cyx-wrap'>";
    // Lightweight client-side filter — replaces footable's built-in search box (dropped with the card
    // conversion); shows/hides cards by text match, no server round-trip.
    $html[]="<div style='margin:2px 0 6px'><input type='text' oninput=\"cyxFilter(this,'cyxlist-routing')\" placeholder='{search}...' style='width:100%;max-width:380px;padding:9px 16px;border:1px solid #bcc9c6;border-radius:9999px;font-size:13px;outline:none'></div>";
    $html[]="<div class='cyx-head'>";
    $html[]="<div>{item}</div><div class='c'>{direction}</div><div>{forward_to}</div><div class='c'>{enabled}</div><div class='r'>&nbsp;</div>";
    $html[]="</div><div class='cyx-list' id='cyxlist-routing'>";

    $shown=0;
	$sock=new sockets();
	$json=json_decode($sock->REST_API_POSTFIX("/postfix/routing/transport/list/$instance_id"),true);
	$results=isset($json["rows"])?$json["rows"]:array();
	$array_field_relay_tls=routing_tls_modes();
	
	foreach ($results as $num=>$ligne){
		$relay_domains_restricted_text=null;
		$item=trim($ligne["addr"]);
		$service=$ligne["service"];
		$nexthope=$ligne["nexthope"];
		$nextport=intval($ligne["nextport"]);
        $certificate=null;
		$OtherDomains=unserializeb64($ligne["OtherDomains"]);
		$tls_text=null;
		$auth_text=null;
		// Pentest 2026-11-04 : addr/service/nexthope/certificate/username/OtherDomains (donnees de la base) affiches sans encodage (XSS stockee).
		$next="(".req_html($service).")&nbsp;".req_html($nexthope).":$nextport";
        $item_text=req_html($item);
		if($item=="*"){$item_text="{all_domains}";}

		if(intval($ligne["client_certificate"])==1){
		    $certificate="&nbsp;<i class='fas fa-file-certificate'></i> ".req_html($ligne["certificate"]);
        }



		
		if($item==null){continue;}
		$itemencode=urlencode($item);
		$linkjs="Loadjs('$page?routing-js=$itemencode&instance-id=$instance_id')";
		
		if(preg_match("#BCC:(.+)#", $item,$re)){
			$next="{bcc_to} ".req_html($nexthope);
			$item="{$re[1]}";
			$linkjs="Loadjs('$page?routing-bcc=$itemencode&instance-id=$instance_id')";
		}
		
		$direction=$ligne["direction"];
		$id=md5($item);
		$shown++;

		$dir=$array_direction[$direction];
		$relay_domains_restricted=intval($ligne["relay_domains_restricted"] ?? 0);

		if($relay_domains_restricted==1){$relay_domains_restricted_text="<br><small>{verifiy_recipients_on_target_server}</small>";}
		if(intval($ligne["recipient_ad_check"] ?? 0)==1){$relay_domains_restricted_text.="<br><small>{recipients_validated_in_ad}</small>";}
		if($ligne["tls_enabled"]==1){
			if($ligne["tls_mode"]<>null){
				$tls_text="<br><small>{smtp_use_tls}: {$array_field_relay_tls[$ligne["tls_mode"]]}</small>";
			}
		}
		if($ligne["auth"]==1){
			$auth_text="<br><small>{authentication}: ".req_html($ligne["username"])."</small>";
		}

		if(count($OtherDomains)>10){
            $item_text=$item_text. " {and} ".count($OtherDomains)." {identical_domains}";
        }else{
            $dmz=array();
		    foreach ($OtherDomains as $dm=>$none){ if(strpos("  $dm","#")>0){continue;} $dmz[]=req_html($dm); }
		    if(count($dmz)>0) {
                $item_text = $item_text . "," . @implode(", ", $dmz);
            }
        }

        $a=array();
        if($direction<>"local"){
            $a[]="<a href=\"javascript:$linkjs\" title='{edit}'><i class='fas fa-edit'></i></a>";
        }
        $itemCheck=($direction=="local")?"&nbsp;":$tpl->icon_check($ligne["enabled"],"Loadjs('$page?routing-enable=$itemencode&instance-id=$instance_id')",null,"AsPostfixAdministrator");
        if($direction<>"local"){
            $a[]=$tpl->icon_delete("Loadjs('$page?routing-delete=$itemencode&id=$id&instance-id=$instance_id')","AsPostfixAdministrator");
        }
        $details=$relay_domains_restricted_text.$tls_text.$auth_text.$certificate;
        if($details==""){$details="&nbsp;";}

        $html[]="<div class='cyx-card' id='$id'>";
        $html[]="<div class='cyx-user'><div class='cyx-av cyx-av-ok'><i class='fas fa-route'></i></div><div style='min-width:0'><div class='cyx-name'>$item_text</div><div class='cyx-email'>$next</div></div></div>";
        $html[]="<div class='cyx-c'><span class='cyx-chip cyx-chip-none'>$dir</span></div>";
        $html[]="<div>$details</div>";
        $html[]="<div class='cyx-c'>$itemCheck</div>";
        $html[]="<div class='cyx-actions'>".@implode("",$a)."</div>";
        $html[]="</div>"; // card


	}

    $main=new maincf_multi($instance_id);
    $RestrictToInternalDomains=intval($main->GET("RestrictToInternalDomains"));
    $RestrictToInternalDomainsLists=$main->GET('RestrictToInternalDomainsLists');
    $RestrictToOutgoingDomains=intval($main->GET("RestrictToOutgoingDomains"));
    $RestrictToOutgoingDomainsLists=$main->GET('RestrictToOutgoingDomainsLists');
    $RestrictToOutgoingDomainsErrorMsg=$main->GET('RestrictToOutgoingDomainsErrorMsg');

    if($RestrictToInternalDomains==1){
        $js="Loadjs('fw.postfix.settings.php?section-outin=yes&$instance_id-id=0&function=$function')";
        $text=$tpl->td_href("{RestrictToInternalDomains}: $RestrictToInternalDomainsLists",null,$js);
        $shown++;
        $html[]="<div class='cyx-card' id=''>";
        $html[]="<div class='cyx-user'><div class='cyx-av cyx-av-ok'><i class='fas fa-route'></i></div><div style='min-width:0'><div class='cyx-name'>$text</div></div></div>";
        $html[]="<div class='cyx-c'><span class='cyx-chip cyx-chip-none'>{$array_direction[0]}</span></div>";
        $html[]="<div>&nbsp;</div>";
        $html[]="<div class='cyx-c'>&nbsp;</div>";
        $html[]="<div class='cyx-actions'></div>";
        $html[]="</div>"; // card
    }
    if($RestrictToOutgoingDomains==1){
        $js="Loadjs('fw.postfix.settings.php?section-outin=yes&$instance_id-id=0&function=$function')";
        $text=$tpl->td_href("{RestrictToOutgoingDomains}: $RestrictToOutgoingDomainsLists",null,$js);
        $shown++;
        $html[]="<div class='cyx-card' id=''>";
        $html[]="<div class='cyx-user'><div class='cyx-av cyx-av-ok'><i class='fas fa-route'></i></div><div style='min-width:0'><div class='cyx-name'>$text</div></div></div>";
        $html[]="<div class='cyx-c'><span class='cyx-chip cyx-chip-none'>{$array_direction[1]}</span></div>";
        $html[]="<div>&nbsp;</div>";
        $html[]="<div class='cyx-c'>&nbsp;</div>";
        $html[]="<div class='cyx-actions'></div>";
        $html[]="</div>"; // card
    }

    if($shown===0){ $html[]="<div class='cyx-empty'>&mdash;</div>"; }
    $html[]="</div></div>"; // list, wrap

    $instancename=null;
    if($instance_id>0){
        $sock=new sockets();
        $j=json_decode($sock->REST_API_POSTFIX("/postfix/routing/instance-name/$instance_id"),true);
        $instancename=$j["name"];
    }

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    // Pentest 2026-11-04 : instancename (donnee de la base) affiche sans encodage (XSS stockee).
    $TINY_ARRAY["TITLE"]="{routing_tables} <small>".req_html($instancename)."</small>";
    $TINY_ARRAY["ICO"]="fas fa-bus";
    $TINY_ARRAY["EXPL"]="{postfix_routing_tables_explain}";
    $TINY_ARRAY["URL"]="postfix-routing";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="

	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	function cyxFilter(inp,listId){var q=inp.value.toLowerCase();var L=document.getElementById(listId);if(!L)return;var c=L.getElementsByClassName('cyx-card');for(var i=0;i<c.length;i++){c[i].style.display=(c[i].textContent.toLowerCase().indexOf(q)>=0)?'':'none';}}
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
	//
	
}

function routing_spf(){
    $tpl=new template_admin();
    $value=$_GET["routing-spf"];
    $instance_id=intval($_GET["instance-id"]);
    $sock=new sockets();
    $json=json_decode($sock->REST_API_POSTFIX_POST_JSON("/postfix/routing/transport/get/$instance_id",array("addr"=>$value)),true);
    $ligne=isset($json["row"])?$json["row"]:array();
    $item = trim($ligne["addr"]);
    $OtherDomains = unserializeb64($ligne["OtherDomains"]);

    $myhostname=php_uname("n");
    $tb=explode(".",$myhostname);
    $netbiosname=$tb[0];unset($tb[0]);
    $domainname=@implode(".",$tb);

    $record_generated[]= "{$item}.  IN TXT or SPF   \"v=spf1 mx ip4:1.2.3.4 ip4:1.2.3.4.5 a:$netbiosname.$domainname a:{$netbiosname}2.$domainname";

    $zdoms=array();
    foreach ($OtherDomains as $domain=>$none){
        $zdoms[]="include:$domain";
    }

    if(count($zdoms)>0){
        $record_generated[]=@implode(" ",$zdoms);
    }

    $DNS_ENTRY=@implode(" ",$record_generated)." -all\"";
    // Pentest 2026-11-04 : addr/OtherDomains (donnees de la base) affiches sans encodage (XSS stockee).
    $DNS_ENTRY_HTML=req_html($DNS_ENTRY);



    // Mise en page en flex. La version en <table> ouvrait un <div> dans la premiere
    // cellule et le fermait dans la seconde: le balisage etait invalide, et selon le
    // navigateur le bouton se retrouvait aligne a gauche au lieu d'etre a droite.
    $html="<div style='display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;margin-top:20px'>"
        ."<div style='flex:1 1 320px;min-width:0'><p>{SPF_DNS_HOWTO}</p></div>"
        ."<div style='flex:0 0 auto'>"
        .$tpl->button_autnonome("{verify_spf_entries}",
            "s_PopUpFull('https://mxtoolbox.com/SuperTool.aspx?action=spf:".req_url($item)."','1024','900');","fas fa-shield-check")
        ."</div></div>"
        ."<textarea style='width:99%;height:250px;margin-top:20px'>$DNS_ENTRY_HTML</textarea>";


    echo $tpl->_ENGINE_parse_body($html);



}

function routing_has_ad_connections():bool{
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADLicenseRemoved"))==1){return false;}
	$cnx=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
	if(!is_array($cnx)){return false;}
	foreach($cnx as $index=>$ligne){
		if(is_numeric($index) && isset($ligne["LDAP_SERVER"])){return true;}
	}
	return false;
}
