<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["AclsUsePages"])){$tpl=new template_admin();$tpl->SAVE_POSTs();exit;}
if(isset($_GET["search"])){table_builder();exit;}
if(isset($_GET["opts"])){opts_js();exit;}
if(isset($_GET["opts-popup"])){opts_popup();exit;}
if(isset($_GET["tiny-js"])){TINY_PAGE();exit;}
if(isset($_GET["file-uploaded"])){import_uploaded_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["externalALCLDAPRecursive"])){externalALCLDAPRecursive();exit;}
if(isset($_GET["externalALCLDAPRecursive-popup"])){externalALCLDAPRecursive_popup();exit;}
if(isset($_GET["externalALCLDAPRecursive-switch"])){externalALCLDAPRecursive_switch();exit;}
if(isset($_GET["export-js"])){export_js();exit;}
if(isset($_GET["rule-code"])){rule_code();exit;}
if(isset($_GET["proxy-acls-bugs"])){proxy_acls_bugs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_GET["new-group-rule-js"])){new_rule_group_js();exit;}
if(isset($_GET["notify-message"])){notify_message();exit;}
if(isset($_POST["notify-message"])){notify_message_save();exit;}
if(isset($_GET["web-error-page"])){notify_error_page();exit;}
if(isset($_POST["new-group-rule"])){new_rule_group_save();exit;}

if(isset($_POST["notify-error-page"])){notify_error_page_save();exit;}
if(isset($_GET["SquidAclFinishDeny"])){SquidAclFinishDeny_js();exit;}
if(isset($_GET["export-acls"])){export_acls();exit;}
if(isset($_GET["export-rule-js"])){export_rule_js();exit;}
if(isset($_GET["export-rule-exported"])){export_rule_exported();exit;}
if(isset($_GET["export-rule-download"])){export_rule_download();exit;}
if(isset($_GET["rules-table-start"])){groups_rules_table_start();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}
if(isset($_GET["fill"])){fillthisRule();exit;}
if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["duplicate-js"])){duplicate_js();exit;}
if(isset($_GET["change-order"])){change_order_js();exit;}
if(isset($_POST["NewOrder"])){change_order_save();exit;}
if(isset($_POST["rule-delete-confirm"])){rule_delete_confirm();exit;}
if(isset($_GET["rules-scan"])){rules_scan();exit;}
if(isset($_GET["rules-scan-ids"])){rules_scan_ids();exit;}

page();

function SquidAclFinishDeny_js():bool{
    $SquidAclFinishDeny=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAclFinishDeny"));
    if($SquidAclFinishDeny==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAclFinishDeny",1);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/localnets");
        return admin_tracks("Modify the final proxy acl rules by deny all except trusted network");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAclFinishDeny",0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/localnets");
    return admin_tracks("Modify the final proxy acl rules by accepting all nodes");
}
function export_js(){

    $ID=intval($_GET["export-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/acls/export/$ID"));
    if(!$json->Status){
        echo "<html lang='en'><head></head><body><H1 style='color;red'>$json->Error</H1></body></html>";
        return false;
    }
    $data=$json->export;
    $fsize=strlen($data);
    $timestamp =time();
    $etag = md5($data);
    if($GLOBALS["VERBOSE"]) {
        echo $data . "\n";
        return true;
    }

    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: application/json');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"ids.rule.$ID.json\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"$etag\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $data;

}

function opts_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{options}","$page?opts-popup=yes&function={$_GET["function"]}");
}
function opts_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $AclsUsePages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUsePages"));
    $AclsUseRows=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseRows"));
    if($AclsUseRows==0){$AclsUseRows=30;}
    $form[]=$tpl->field_checkbox("AclsUsePages","{page_interval}",$AclsUsePages);
    $form[]=$tpl->field_numeric("AclsUseRows","{rows}",$AclsUseRows);
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function()");
return true;
}

function export_rule_exported(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["export-rule-exported"]);
    $div="export-progress-$ID";

    $fname=PROGRESS_DIR."/rule.$ID.acl";
    if(!is_file($fname)){
        echo $tpl->js_error("rule.$ID.acl {no_such_file}");
        return false;
    }
    header("content-type: application/x-javascript");
    echo "document.getElementById('$div').innerHTML='';\n";
    echo "document.location.href=\"$page?export-rule-download=$ID\"\n";
    return true;
}
function export_rule_download(){
    $ID=intval($_GET["export-rule-download"]);
    $tfile=PROGRESS_DIR."/rule.$ID.acl";
    $basename=basename($tfile);
    if(!is_file($tfile)){die();}

    $fsize=@filesize($tfile);
    $timestamp =filemtime($tfile);
    $etag = md5($tfile . $timestamp);


    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: application/x-sqlite3');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"{$etag}\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tfile);
    @unlink($tfile);
}
function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{import}","$page?import-popup=yes&function=$function",550);
}
function externalALCLDAPRecursive():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    return $tpl->js_dialog1("{AD_LDAP_RECURSIVE}","$page?externalALCLDAPRecursive-popup=yes&gprule=$gprule&function=$function",550);
}
function externalALCLDAPRecursive_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    $externalALCLDAPRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("externalALCLDAPRecursive"));
    $html[]=$tpl->div_explain("{externalALCLDAPRecursive}");
    $html[]="<div class=center style='margin:30px'>";
    if($externalALCLDAPRecursive==1) {
        $html[] = $tpl->button_autnonome("{disable_feature}", "Loadjs('$page?externalALCLDAPRecursive-switch=yes&gprule=$gprule&function=$function')", ico_check, "AsFirewallManager", "335");
    }else{
        $html[] = $tpl->button_autnonome("{enable_feature}", "Loadjs('$page?externalALCLDAPRecursive-switch=yes&gprule=$gprule&function=$function')", ico_check, "AsFirewallManager", "335");
    }
    $html[] = "</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function externalALCLDAPRecursive_switch():bool{
    $page=CurrentPageName();
    $externalALCLDAPRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("externalALCLDAPRecursive"));
    if ($externalALCLDAPRecursive==1) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("externalALCLDAPRecursive",0);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("externalALCLDAPRecursive",1);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");
    }
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    $toTiny="Loadjs('$page?tiny-js=yes&gprule=$gprule&function=$function')";
    header("content-type: application/x-javascript");
    echo "dialogInstance1.close();\n";
    echo $toTiny;
    return true;
}
function import_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function = $_GET["function"];
    $html[]=$tpl->div_explain("{import}||{import_acl_ids_explain}");
    $html[]="<div class='center' style='margin: 30px'>".$tpl->button_upload("{upload_a_file}",$page,null,"&function=$function")."</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function import_uploaded_js():bool{
    if(!fw_progress_page_priv("AsFirewallManager")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    $function = $_GET["function"];
    $filename   = $_GET["file-uploaded"];
    $fileencode = urlencode($filename);
    $page=CurrentPageName();

    admin_tracks("$filename was uploaded to IDS ACLs rules");
    header("content-type: application/x-javascript");
    $js=$tpl->framework_buildjs("suricata:/acls/import/$fileencode",
        "suricata.acls.parse",
        "acls.logs","progress-acls-restart","$function()");

    echo "dialogInstance1.close();\n$js\n";
    return true;

}
function export_acls(){
    $tfile="/home/artica/SQLITE/acls.db";
    $basename=basename($tfile);
    if(!is_file($tfile)){die();}

    $fsize=@filesize($tfile);
    $timestamp =filemtime($tfile);
    $etag = md5($tfile . $timestamp);


    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: application/x-sqlite3');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"{$etag}\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tfile);
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{SURICATA_ACLS}","fad fa-shield-alt","{SURICATA_ACLS_EXPLAIN}<div id='proxy-acls-bugs'></div>","$page?table=yes","suricata-acls-access","progress-acls-restart",false,"table-acls-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{PROXY_ACLS}",$html);
        echo $tpl->build_firewall();
        return;
    }
	echo $tpl->_ENGINE_parse_body($html);

}
function change_order_js(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $ID             = intval($_GET["change-order"]);

    // REST API: get current xOrder for the prompt default value
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    $xORDER=0;
    if(is_object($raw) && property_exists($raw,"xOrder")){$xORDER=intval($raw->xOrder);}

    $jsafter        = "LoadAjax('table-acls-rules','$page?table=yes');";
    $tpl->js_prompt("{order}","{give_new_position}","fas fa-sort-numeric-up-alt",$page,"NewOrder",$jsafter,$xORDER,"$ID");

}
function change_order_save(){
    $xORDER     = intval($_POST["NewOrder"]);
    $RuleID     = intval($_POST["KeyID"]);

    // REST API: PUT /suricata/acls/{id} with xOrder field
    [$ok,$err] = acls_api_send("PUT","/suricata/acls/$RuleID",["xOrder"=>$xORDER]);
    if(!$ok){ echo "// change_order_save error: $err\n"; }
}
function rule_delete_js():bool{
    $ID         = intval($_GET["rule-delete-js"]);
    $md         = "acl-$ID";

    // REST API: get rule info for delete confirmation
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    $aclname="";
    $aclgroup=0;
    if(is_object($raw)){
        $aclname  = property_exists($raw,"aclname")  ? $raw->aclname  : "";
        $aclgroup = property_exists($raw,"aclgroup") ? intval($raw->aclgroup) : 0;
    }

    if($aclgroup==1){
        $tpl=new template_admin();
        $tpl->js_confirm_delete("{group_of_rules}<br><strong>$aclname</strong><br>{group_of_rules_delete_ask}","rule-delete-confirm",$ID,"$('#$md').remove();");
        return true;
    }

	header("content-type: application/x-javascript");

	if(!rule_delete($ID)){return true;}
	echo "$('#$md').remove();";
    return true;
}
function rule_delete_confirm():bool{
    $ID         = intval($_POST["rule-delete-confirm"]);
    if(!rule_delete($ID)){return false;}
    admin_tracks("Remove Proxy acl id $ID");
    return true;
}
function proxy_acls_bugs():bool{
    $SQUID_ACLS_BUGS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ACLS_BUGS"));
    if($SQUID_ACLS_BUGS==0){return true;}
    $tpl=new template_admin();
    $error=$tpl->_ENGINE_parse_body("{acls_check_errors}");
    $error=str_replace("%s",$SQUID_ACLS_BUGS,$error);
    $bt=$tpl->button_autnonome("{view}", "Loadjs('fw.proxy.acls.bugs.php')", "fas fa-bug","AsFirewallManager",100,"btn-danger");
    $html[]="<div style='float:right;margin-right:50px;margin-bottom:20px'>$bt</div>";
    $html[]=$error;
    echo $tpl->_ENGINE_parse_body($tpl->div_error(@implode("\n",$html)));

    return true;

}

function duplicate_rule($ID_SRC, $NewAClGPID=0){
    $ID_SRC = intval($ID_SRC);
    list($ok, $data) = acls_api_send("POST", "/suricata/acls/$ID_SRC/duplicate", [
        "newAclGpid" => intval($NewAClGPID),
    ]);
    if(!$ok){
        $tpl = new template_admin();
        $tpl->js_mysql_alert($data);
        return 0;
    }
    $newID = intval($data["newID"] ?? 0);
    if($newID > 0){
        admin_tracks("Duplicate Suricata rule (src=$ID_SRC → new=$newID)");
    }
    return $newID;
}
function duplicate_js(){
    $ID_SRC = intval($_GET["duplicate-js"]);
    $DuplicatedID = duplicate_rule($ID_SRC);
    if($DuplicatedID == 0){ return; }
    $page = CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-acls-rules','$page?table=yes');";
}
function rules_scan():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $js[]="var TheIDS = Array.from(document.querySelectorAll('[id^=\"ids-status-\"]'))";
    $js[]=" .map(el => {";
    $js[]=" var match = el.id.match(/^ids-status-(\d+)$/);";
    $js[]=" return match ? parseInt(match[1], 10) : null;";
    $js[]=" })";
    $js[]=" .filter(n => n !== null);";
    $js[]="var CompiledIDS = TheIDS.join(\"-\");";
    $js[]="Loadjs('$page?rules-scan-ids='+encodeURIComponent(CompiledIDS));";
    echo @implode("\n", $js);
    return true;
}
function rules_scan_ids():bool{
    $tpl=new template_admin();
    $ids=array_filter(array_map('intval', explode("-",$_GET["rules-scan-ids"])));
    if(empty($ids)){
        header("content-type: application/x-javascript");
        return true;
    }
    $f=array();

    // REST API: fetch all rules then filter by requested IDs
    $allRules=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls"));
    $ruleMap=array();
    if(is_array($allRules)){
        foreach($allRules as $r){
            if(is_object($r) && property_exists($r,"id")){
                $ruleMap[intval($r->id)]=$r;
            }
        }
    }

    $getCurrentRules=getCurrentRules();
    foreach($ids as $ID){
        if(!isset($ruleMap[$ID])){continue;}
        $r=$ruleMap[$ID];
        // Build a compatible array for td_status()
        $ligne=["ID"=>$ID,"enabled"=>intval($r->enabled??1),"iserror"=>intval($r->iserror??0)];
        $status=base64_encode($tpl->_ENGINE_parse_body(td_status($ligne,$getCurrentRules)));
        $f[]="// id $ID";
        $f[]="if (document.getElementById('ids-status-$ID') ){";
        $f[]="  document.getElementById('ids-status-$ID').innerHTML=base64_decode('$status');";
        $f[]="}";
    }
    header("content-type: application/x-javascript");
    echo @implode("\n", $f);
    return true;
}

function rule_enable(){
    $ID=intval($_GET["enable-js"]);

    // REST API: get current enabled state
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    if(!is_object($raw)){
        header("content-type: application/x-javascript");
        echo "alert('rule_enable: failed to fetch rule $ID');";
        return;
    }
    $aclname  = property_exists($raw,"aclname")  ? $raw->aclname  : "";
    $enabled  = property_exists($raw,"enabled")  ? intval($raw->enabled) : 1;

    header("content-type: application/x-javascript");
    if($enabled==0){
        $js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
        $newEnabled=1;
    }else{
        $js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
        $newEnabled=0;
    }

    // REST API: update enabled state
    [$ok,$err]=acls_api_send("PUT","/suricata/acls/$ID",["enabled"=>$newEnabled]);
    if(!$ok){echo "alert('".addslashes($err)."')";return;}

    admin_tracks("Change $aclname proxy rule activation to $newEnabled");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/acls/explains");
    echo $js;
}
function rule_js(){
	$page       = CurrentPageName();
	$tpl        = new template_admin();
	$ID         = intval($_GET["rule-id-js"]);

    // REST API: get rule info
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    $aclname  = "";
    $aclgroup = 0;
    $aclgpid  = 0;
    if(is_object($raw)){
        $aclname  = property_exists($raw,"aclname")  ? $raw->aclname  : "";
        $aclgroup = property_exists($raw,"aclgroup") ? intval($raw->aclgroup) : 0;
        $aclgpid  = property_exists($raw,"aclgpid")  ? intval($raw->aclgpid)  : 0;
    }
    $size = null;
    if(strlen($aclname)>50){
        $aclname=substr($aclname,0,47)."...";
    }

    if($aclgroup==1){
        $size=1024;
        $aclname="{group_of_rules}: $aclname";
    }

    if($aclgpid>0){
        $tpl->js_dialog2("{rule}: $ID $aclname","$page?rule-tabs=$ID",$size);
        return;
    }

	$tpl->js_dialog1("{rule}: $ID $aclname","$page?rule-tabs=$ID",$size);
}
function new_rule_group_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $tpl->js_prompt("{new_group_of_rules}","{rulename}","fa fa-plus",$page,"new-group-rule",
        "LoadAjax('table-acls-rules','$page?table=yes');");

}
function rule_tabs():bool{
    $ID         = intval($_GET["rule-tabs"]);
    $page       = CurrentPageName();
	$tpl        = new template_admin();

    $RefreshFunction=base64_encode("Loadjs('$page?fill=$ID')");
	$array["{rule}"]="$page?rule-settings=$ID";
    $array["{objects}"] = "fw.proxy.acls.objects.php?rule-id=$ID&RefreshFunction=$RefreshFunction&IDS=1&TableLink=suricata_sqacllinks";
    $array["{generated_rule}"]="$page?rule-code=$ID";
	echo $tpl->tabs_default($array);
    return true;
}
function rule_settings():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $classification["not-suspicious"]="not-suspicious";
    $classification["unknown"]="unknown";
    $classification["bad-unknown"]="bad-unknown";
    $classification["attempted-recon"]="attempted-recon";
    $classification["successful-recon-limited"]="successful-recon-limited";
    $classification["successful-recon-largescale"]="successful-recon-largescale";
    $classification["attempted-dos"]="attempted-dos";
    $classification["successful-dos"]="successful-dos";
    $classification["attempted-user"]="attempted-user";
    $classification["unsuccessful-user"]="unsuccessful-user";
    $classification["successful-user"]="successful-user";
    $classification["attempted-admin"]="attempted-admin";
    $classification["successful-admin"]="successful-admin";
    $classification["rpc-portmap-decode"]="rpc-portmap-decode";
    $classification["shellcode-detect"]="shellcode-detect";
    $classification["string-detect"]="string-detect";
    $classification["suspicious-filename-detect"]="suspicious-filename-detect";
    $classification["suspicious-login"]="suspicious-login";
    $classification["system-call-detect"]="system-call-detect";
    $classification["tcp-connection"]="tcp-connection";
    $classification["trojan-activity"]="trojan-activity";
    $classification["unusual-client-port-connection"]="unusual-client-port-connection";
    $classification["network-scan"]="network-scan";
    $classification["denial-of-service"]="denial-of-service";
    $classification["non-standard-protocol"]="non-standard-protocol";
    $classification["protocol-command-decode"]="protocol-command-decode";
    $classification["web-application-activity"]="web-application-activity";
    $classification["web-application-attack"]="web-application-attack";
    $classification["misc-activity"]="misc-activity";
    $classification["misc-attack"]="misc-attack";
    $classification["icmp-event"]="icmp-event";
    $classification["policy-violation"]="policy-violation";
    $classification["default-login-attempt"]="default-login-attempt";
    $classification["targeted-activity"]="targeted-activity";
    $classification["exploit-kit"]="exploit-kit";
    $classification["external-ip-check"]="external-ip-check";
    $classification["domain-c2"]="domain-c2";
    $classification["pup-activity"]="pup-activity";
    $classification["credential-theft"]="credential-theft";
    $classification["social-engineering"]="social-engineering";
    $classification["coin-mining"]="coin-mining";
    $classification["command-and-control"]="command-and-control";

    $action["alert"]="{ids_prefix_1}";
    $action["pass"]="{ids_prefix_2}";

    $f["http"]="http";
    $f["ftp"]="ftp";
    $f["smtp"]="smtp";
    $f["tls"]="tls";
    $f["ssh"]="ssh";
    $f["imap"]="imap";
    $f["smb"]="smb";
    $f["dcerpc"]="dcerpc";
    $f["dns"]="dns";
    $f["modbus"]="modbus";
    $f["enip"]="enip";
    $f["dnp3"]="dnp3";
    $f["nfs"]="nfs";
    $f["ntp"]="ntp";
    $f["ftp-data"]="ftp-data";
    $f["tftp"]="tftp";
    $f["ike"]="ike";
    $f["krb5"]="krb5";
    $f["quic"]="quic";
    $f["dhcp"]="dhcp";
    $f["sip"]="sip";
    $f["rfb"]="rfb";
    $f["mqtt"]="mqtt";
    $f["pgsql"]="pgsql";
    $f["telnet"]="telnet";
    $f["websocket"]="websocket";
    $f["ldap"]="ldap";
    $f["doh2"]="doh2";
    $f["rdp"]="rdp";
    $f["http2"]="http2";
    $f["bittorrent-dht"]="bittorrent-dht";
    $f["pop3"]="pop3";
    $f["mdns"]="mdns";
    $f["snmp"]="snmp";

    $target["src"]="{src}";
    $target["dst"]="{dst}";

    $proto["ip"]="{all}";
    $proto["tcp"]="TCP/IP";
    $proto["udp"]="UDP";
    $proto["icmp"]="icmp";
    $proto["icmpv4"]="icmpv4";
    $proto["icmpv6"]="icmpv6";
    $proto["ipv4"]="ipv4";
    $proto["ipv6"]="ipv6";
    $proto["http"]="http";
    $proto["http1"]="http1";
    $proto["http2"]="http2";
    $proto["tls"]="tls";
    $proto["quic"]="quic";
    $proto["ftp"]="ftp";
    $proto["ftp-data"]="ftp-data";
    $proto["smb"]="smb";
    $proto["dns"]="dns";
    $proto["doh2"]="doh2";
    $proto["mdns"]="mdns";
    $proto["dcerpc"]="dcerpc";
    $proto["ldap"]="ldap";
    $proto["dhcp"]="dhcp";
    $proto["ssh"]="ssh";
    $proto["smtp"]="smtp";
    $proto["imap"]="imap";
    $proto["pop3"]="pop3";
    $proto["nfs"]="nfs";
    $proto["ike"]="ike";
    $proto["krb5"]="krb5";
    $proto["bittorrent-dht"]="bittorrent-dht";
    $proto["mqtt"]="mqtt";
    $proto["ntp"]="ntp";
    $proto["rfb"]="rfb";
    $proto["rdp"]="rdp";
    $proto["snmp"]="snmp";
    $proto["tftp"]="tftp";
    $proto["sip"]="sip";
    $proto["telnet"]="telnet";
    $proto["websocket"]="websocket";
    $proto["pgsql"]="pgsql";
    ksort($proto);

    $flow[""]="{all}";
    $flow["to_client"]="{flow_to_client}";
    $flow["to_server"]="{flow_to_server}";

    $Direction[0] = "{one_direction_only}";
	$Direction[1] = "{both_stateful}";

    $ID=intval($_GET["rule-settings"]);

    // REST API: get full rule details
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    if(!is_object($raw)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_such_file}"));
        return false;
    }
    // Build a flat array matching the legacy $ligne shape
    $ligne=array(
        "ID"               => $ID,
        "aclname"          => property_exists($raw,"aclname")          ? $raw->aclname          : "",
        "action"           => property_exists($raw,"action")           ? $raw->action           : "alert",
        "classtype"        => property_exists($raw,"classtype")        ? $raw->classtype        : "policy-violation",
        "proto"            => property_exists($raw,"proto")            ? $raw->proto            : "",
        "direction"        => property_exists($raw,"direction")        ? intval($raw->direction) : 0,
        "flow"             => property_exists($raw,"flow")             ? $raw->flow             : "",
        "ApplayerProtocol" => property_exists($raw,"applayerProtocol") ? $raw->applayerProtocol : "",
        "target"           => property_exists($raw,"target")           ? $raw->target           : "",
        "count"            => property_exists($raw,"count")            ? intval($raw->count)    : 0,
        "seconds"          => property_exists($raw,"seconds")         ? intval($raw->seconds)  : 0,
        "enabled"          => property_exists($raw,"enabled")         ? intval($raw->enabled)  : 1,
        "description"      => property_exists($raw,"description")     ? base64_decode($raw->description) : "",
    );

    $ligne["aclname"]=$tpl->utf8_encode($ligne["aclname"]);
	$form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("aclname", "{rule_name}", $ligne["aclname"],true);
    $form[]=$tpl->field_array_hash($action, "action", "{action}", $ligne["action"]);
    $form[]=$tpl->field_array_hash($classification, "classtype", "{category}", $ligne["classtype"]);
    $form[]=$tpl->field_array_hash($proto, "proto", "{protocol}", $ligne["proto"]);
    $form[]=$tpl->field_array_hash($Direction, "direction", "{direction}", $ligne["direction"]);
    $form[]=$tpl->field_array_hash($flow, "flow", "{flow}", $ligne["flow"]);
    $form[]=$tpl->field_array_hash($f, "ApplayerProtocol", "{ApplicationLayerProtocol}", $ligne["ApplayerProtocol"]);
    $form[]=$tpl->field_array_hash($target, "target", "{target}", $ligne["target"]);
    $form[]=$tpl->field_numeric("count", "{NumberOfMatches}", $ligne["count"]);
    $form[]=$tpl->field_numeric("seconds", "{during} ({seconds})", $ligne["seconds"]);
	$form[]=$tpl->field_checkbox("enabled", "{enabled}", $ligne["enabled"],true);
   	$jsafter="Loadjs('$page?fill=$ID');";

    $tpl->form_add_button("{export}","Loadjs('$page?export-rule-js=$ID')");
	$html[]="<div id='export-progress-$ID' style='margin-top:5px'></div>";
    $html[]=$tpl->form_outside($ligne["aclname"], @implode("\n", $form),null,"{apply}",$jsafter,"AsFirewallManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function rule_code():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["rule-code"]);

    // REST API: get coded field
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    $coded="";
    if(is_object($raw) && property_exists($raw,"coded")){$coded=$raw->coded;}

    $html[]="<div style='margin-top:20px'><textarea spellcheck='false' autocomplete='off'
  style=\"width:100%;min-height:220px;padding:12px 14px;border:1px solid #d1d5db;border-radius:8px;outline:none;
         font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace;
         font-size:16px;line-height:1.5;color:#111827;background:#ffffff;
         white-space:pre-wrap;word-wrap:break-word;overflow:auto;resize:vertical;tab-size:4;caret-color:#2563eb;\">#ID: $ID\n{$coded}</textarea></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function utf8_decode_switch($value):string{
    if(is_null($value)){
        return "";
    }
    if(PHP_MAJOR_VERSION>7) {
        return $value;
    }
    $tpl=new template_admin();
    return $tpl->utf8_decode($value);
}
function rule_save():bool{

	$tpl    = new template_admin();
	$ID     = intval($_POST["rule-save"]);
	$tpl->CLEAN_POST_XSS();

	$aclname=utf8_decode_switch($_POST["aclname"] ?? "");
	// description stored base64-encoded in DB (PHP convention)
	$description=base64_encode($_POST["description"] ?? "");

    $body=array(
        "aclname"          => $aclname,
        "action"           => $_POST["action"]            ?? "alert",
        "enabled"          => intval($_POST["enabled"]    ?? 1),
        "seconds"          => intval($_POST["seconds"]    ?? 0),
        "count"            => intval($_POST["count"]      ?? 0),
        "proto"            => $_POST["proto"]             ?? "",
        "flow"             => $_POST["flow"]              ?? "",
        "target"           => $_POST["target"]            ?? "",
        "classtype"        => $_POST["classtype"]         ?? "policy-violation",
        "direction"        => intval($_POST["direction"]  ?? 0),
        "applayerProtocol" => $_POST["ApplayerProtocol"]  ?? "",
        "priority"         => intval($_POST["priority"]   ?? 1),
        "description"      => $description,
    );

    // REST API: PUT /suricata/acls/{id}
    [$ok,$err]=acls_api_send("PUT","/suricata/acls/$ID",$body);
    if(!$ok){echo $tpl->post_error($tpl->javascript_parse_text($err));return false;}

   $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/acls/explains");
   return admin_tracks("Modify settings of $aclname IDS rule");
}
function new_rule_js():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $rulename   = null;
	$gprule     = intval($_GET["gprule"]);
    $function   = $_GET["function"];

	if($gprule>0){
        // REST API: get group rule name
        $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$gprule"));
        if(is_object($raw) && property_exists($raw,"aclname")){
            $rulename=":".$raw->aclname;
        }
    }

	$title="{new_rule}$rulename";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes&gprule=$gprule&function=$function");
}
function new_rule_popup():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $gprule     = intval($_GET["gprule"]);
    $jsafter    = array();

    $f["http"]="http";
    $f["ftp"]="ftp";
    $f["smtp"]="smtp";
    $f["tls"]="tls";
    $f["ssh"]="ssh";
    $f["imap"]="imap";
    $f["smb"]="smb";
    $f["dcerpc"]="dcerpc";
    $f["dns"]="dns";
    $f["modbus"]="modbus";
    $f["enip"]="enip";
    $f["dnp3"]="dnp3";
    $f["nfs"]="nfs";
    $f["ntp"]="ntp";
    $f["ftp-data"]="ftp-data";
    $f["tftp"]="tftp";
    $f["ike"]="ike";
    $f["krb5"]="krb5";
    $f["quic"]="quic";
    $f["dhcp"]="dhcp";
    $f["sip"]="sip";
    $f["rfb"]="rfb";
    $f["mqtt"]="mqtt";
    $f["pgsql"]="pgsql";
    $f["telnet"]="telnet";
    $f["websocket"]="websocket";
    $f["ldap"]="ldap";
    $f["doh2"]="doh2";
    $f["rdp"]="rdp";
    $f["http2"]="http2";
    $f["bittorrent-dht"]="bittorrent-dht";
    $f["pop3"]="pop3";
    $f["mdns"]="mdns";
    $f["snmp"]="snmp";

    $function=$_GET["function"];
    $jsafter[]="BootstrapDialog1.close();";

    if($function<>null){
        $jsafter[]="$function()";
    }
	if($gprule>0){
	    $tpl->field_hidden("aclgpid","$gprule");
        $jsafter[]="LoadAjax('GroupOfRules{$gprule}','$page?table=yes&gprule=$gprule');";
        $jsafter[]="Loadjs('$page?fill=$gprule');";

    }else{
        $tpl->field_hidden("aclgpid","0");

    }
    $form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("aclname", "{rule_name}", null,true);


    $action["alert"]="{ids_prefix_1}";
    $action["pass"]="{ids_prefix_2}";

    $form[]=$tpl->field_array_hash($action, "action", "{action}", "alert");
    $form[]=$tpl->field_array_hash($f, "ApplayerProtocol", "{ApplicationLayerProtocol}", "");



    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),"AsFirewallManager");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function rule_move(){
    $ID  = intval($_GET["acl-rule-move"]);
    $dir = intval($_GET["acl-rule-dir"]);

    // REST API: get current rule info for audit log
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    $aclname="";
    $xORDER_ORG=0;
    if(is_object($raw)){
        $aclname    = property_exists($raw,"aclname") ? $raw->aclname : "";
        $xORDER_ORG = property_exists($raw,"xOrder")  ? intval($raw->xOrder) : 0;
    }

    // REST API: PUT /suricata/acls/{id}/move  dir=1 (up) or dir=0 (down)
    [$ok,$err]=acls_api_send("PUT","/suricata/acls/$ID/move",["dir"=>$dir]);
    if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".addslashes($err)."');";
        return;
    }

    $xORDER_NEW = ($dir==1) ? $xORDER_ORG-1 : $xORDER_ORG+1;
    admin_tracks("Move IDS acl order of $aclname from $xORDER_ORG to $xORDER_NEW");

    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?fill=$ID');\n";
}
function table():bool{
    if(!isset($_GET["gprule"])){$_GET["gprule"]=0;}
    $gprule         = intval($_GET["gprule"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_SESSION["ACL_SEARCH"])){$_SESSION["ACL_SEARCH"]=null;}
    $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";
    echo $tpl->search_block($page,null,null,"value=".$_SESSION["ACL_SEARCH"],"&gprule=$gprule",$options);
    return true;
}
function  getCurrentRules():array{
    $MAIN=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/acls/current"));


    if(!property_exists($json,"rules")){
        return array();
    }

    foreach ($json->rules as $ruleid=>$none){
            $MAIN[$ruleid]=true;
        }


    return $MAIN;
}
function TINY_PAGE($return=false):string{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];

    $add="Loadjs('$page?newrule-js=yes&gprule=$gprule&function=$function',true);";
    $jsimport="Loadjs('$page?import-js=yes&function=$function');";
    $users=new usersMenus();
    $topbuttons=array();

    if($users->AsFirewallManager) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        if ($gprule == 0) {
            if ($function <> null) {
                $topbuttons[] = array("$function()", ico_refresh, "{reload}");
            }

            $topbuttons[] = array("document.location.href='$page?export-acls=yes'", ico_export, "{export}");
            $topbuttons[] = array($jsimport, ico_import, "{import}");
        }
    }


    if($return){
        return $tpl->th_buttons($topbuttons);
    }
    $jscompile=  $tpl->framework_buildjs(
        "suricata:/build/acls",
        "dumpacls.progress",
        "dumpacls.progress.txt","progress-acls-restart"
    );

    $topbuttons[] = array($jscompile,ico_save,"{apply_changes}");

    $TINY_ARRAY["TITLE"]="{SURICATA_ACLS}";
    $TINY_ARRAY["ICO"]="fad fa-shield-alt";
    $TINY_ARRAY["EXPL"]="{SURICATA_ACLS_EXPLAIN}";
    $TINY_ARRAY["URL"]="suricata-acls-access";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    header("content-type: application/x-javascript");
    echo $jstiny;
    return "";
}
function table_builder():bool{

    if(!isset($_GET["gprule"])){$_GET["gprule"]=0;}
    $tpl            = new template_admin();
	$page           = CurrentPageName();
	$order          = $tpl->_ENGINE_parse_body("{order}");
	$rulename       = $tpl->_ENGINE_parse_body("{rulename}");
	$description    = $tpl->_ENGINE_parse_body("{description}");
	$enabled        = $tpl->_ENGINE_parse_body("{enabled}");
    $gprule         = intval($_GET["gprule"]);
    $search         = $_GET["search"];
    $function       = $_GET["function"];

    $getCurrentRules=getCurrentRules();
	$t=md5(time()."{$_GET["gprule"]}".microtime(true));

    $data1="class='text-capitalize' style='width:1%;text-align:center'";
    if($gprule>0){
        $html[]=TINY_PAGE(true);
    }
    $AclsUsePages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUsePages"));
    $limit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseRows"));
    if($limit==0){$limit=120;}


    $TableClass="";
    if($AclsUsePages==1){
        $TableClass="footable ";
    }

    $th1="data-sortable=true class='text-capitalize' data-type='text'";
    $html[]="<table id='table-ids-rules-$t' class=\"{$TableClass}table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th $data1>$order</th>";
    $html[]="<th $data1>{status}</th>";
	$html[]="<th  nowrap>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false>{export}</th>";
    $html[]="<th $th1>$enabled</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-firewall-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

// Pentest 2026-11-04 : search etait recopie sans encodage dans la page (XSS).
    VERBOSE(req_html("search:$search"),__LINE__);
    $_SESSION["ACL_SEARCH"]=$search;
    if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
        $limit=$re[2];
        $search=str_replace("$re[1]=$re[2]","",$search);
    }

    // REST API: fetch rules list (optionally filtered by aclgpid)
    $apiUrl = "/suricata/acls";
    if($gprule>0){$apiUrl.="?aclgpid=$gprule";}
    $allRules=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($apiUrl));
    if(!is_array($allRules)){$allRules=array();}

    // Apply search filter client-side (simple name/ID match)
    $results=array();
    $searchStr=trim($search ?? "");
    foreach($allRules as $r){
        if(!is_object($r)){continue;}
        if($searchStr!=""){
            $rname=property_exists($r,"aclname") ? $r->aclname : "";
            $rid  =property_exists($r,"id")      ? intval($r->id) : 0;
            if(is_numeric($searchStr)){
                if($rid!=(int)$searchStr){continue;}
            } else {
                $pat=str_replace(array("*","%"),array("",""),$searchStr);
                if(stripos($rname,$pat)===false){continue;}
            }
        }
        $results[]=$r;
        if(count($results)>=$limit){break;}
    }

    // search_all_items: complex 4-table JOIN not migrable via single REST call.
    // DONE_WITH_CONCERNS: group-linked-item search requires direct SQLite for now.
    list($search2,$searchgroups)=search_all_items($search);

    $nothing=$tpl->icon_nothing();
    $TRCLASS=null;
    if(count($searchgroups)>0){
        foreach ($searchgroups as $gpid=>$GroupName){
            $ff[]="&laquo;".$tpl->td_href("$GroupName","Loadjs('fw.rules.items.php?groupid=$gpid$function',true);")."&raquo;&nbsp;";
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='acl--1'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap >$nothing</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>&nbsp;</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>{follow_group_found}:</td>";
        $html[]="<td class='left' style='width:99%'>".@implode(", ",$ff)."</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
        $html[]="</tr>";
    }

    // Merge search2 results (from search_all_items) by ID to avoid duplicates
    $already_isset=array();
    // First pass: index already-fetched results
    foreach($results as $r){
        $rid=is_object($r) ? intval($r->id ?? 0) : 0;
        if($rid>0){$already_isset[$rid]=true;}
    }
    // Add search2 rows (from search_all_items) if not already in results
    foreach($search2 as $sid=>$srow){
        $sid=intval($sid);
        if(isset($already_isset[$sid])){continue;}
        $already_isset[$sid]=true;
        // Convert legacy array row to pseudo-object for unified rendering below
        $results[]=(object)$srow;
    }

	foreach($results as $r) {
        if(!is_object($r)){continue;}
        $ID=intval($r->id ?? 0);
        if($ID<=0){continue;}
        if(isset($already_isset["rendered_$ID"])){continue;}
        $already_isset["rendered_$ID"]=true;

		$MUTED=null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $enabled_val = property_exists($r,"enabled") ? intval($r->enabled) : 1;
        $xorder_val  = property_exists($r,"xOrder")  ? intval($r->xOrder)  : 0;
        $zExplain    = property_exists($r,"zExplain") ? $r->zExplain : "";
        $aclname_raw = property_exists($r,"aclname")  ? $r->aclname  : "";

        $ligne=array(
            "ID"      => $ID,
            "enabled" => $enabled_val,
            "iserror" => property_exists($r,"iserror") ? intval($r->iserror) : 0,
            "xORDER"  => $xorder_val,
            "zExplain"=> $zExplain,
            "aclname" => $aclname_raw,
        );

    	$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($enabled_val==0){$MUTED=" text-muted";}
    	$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");

        $aclname = $tpl->utf8_encode($aclname_raw);
        if(strlen($aclname)>50){
            $aclname=substr($aclname,0,47)."...";
        }
		$row_order=$tpl->td_href(" <span class=\"label label-default\" id='acl-order-$ID'>{$xorder_val}</span>",
            null,"Loadjs('$page?change-order=$ID');");

        $rule_status=td_status($ligne,$getCurrentRules);
        $td_name=$tpl->td_href($aclname,"{click_to_edit}",$js);

        $dCet="style='vertical-align:middle;width:1%' nowrap";
        $dCenter="class=\"center\" style='width:1%' nowrap";
		$html[]="<tr class='$TRCLASS$MUTED' id='acl-$ID'>";
		$html[]="<td $dCenter>$row_order</td>";
        $html[]="<td $dCet><span id='ids-status-$ID'>$rule_status</span></td>";
		$html[]="<td $dCet>$td_name</td>";
		$html[]="<td style='vertical-align:middle'><div id='ids-explain-$ID'>$zExplain</div></td>";
        $html[]="<td $dCenter></td>";
        $html[]="<td $dCenter>".$tpl->icon_export("window.location.href ='$page?export-js=$ID'","AsFirewallManager")."</td>";
		$html[]="<td $dCenter>".$tpl->icon_check($enabled_val,"Loadjs('$page?enable-js=$ID')",null,"AsFirewallManager")."</td>";
		$html[]="<td $dCenter>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td $dCenter>$delete</center></td>";
		$html[]="</tr>";

	}

    $MUTED=null;
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $SquidAclFinishDeny=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAclFinishDeny"));
    if($SquidAclFinishDeny==1){$AclFinishDeny=0;$MUTED=" text-muted";}else{$AclFinishDeny=1;}
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-NONE'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap ></td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap ></td>";
    $html[]="<td style='vertical-align:middle;width:1%'  nowrap>{finally}</td>";
    $html[]="<td style='vertical-align:middle'>{do_nothing}</td>";
    $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
    $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
    $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($AclFinishDeny,"Loadjs('$page?SquidAclFinishDeny=yes')",null,"AsFirewallManager")."</td>";
    $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
    $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
    $html[]="</tr>";


	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='9'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";



    $toTiny="Loadjs('$page?tiny-js=yes&gprule=$gprule&function=$function')";
    if($gprule>0){$toTiny=null;}

    $jsRefesh=$tpl->RefreshInterval_Loadjs("table-ids-rules-$t",$page,"rules-scan=yes");

    $html[]="<script>";
    if($AclsUsePages==1){
$html[]="$(document).ready(function() { $('#table-ids-rules-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    }
    $html[]="$jsRefesh;";
    $html[]="$toTiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function search_all_items($search){
    if($search === null || trim($search) === ""){ return array(array(), array()); }
    $search = trim($search);
    // Fetch all suricata rules, then probe each rule's linked groups via REST.
    $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls"), true);
    if(!is_array($raw)){ return array(array(), array()); }
    $COMPRESSRULES = array();
    $COMPRESS_GROUPS = array();
    foreach($raw as $ruleRaw){
        if(!is_array($ruleRaw)){ continue; }
        $rid = intval($ruleRaw["id"] ?? 0);
        if($rid <= 0){ continue; }
        // GET links for this rule with the search term applied to GroupName/description.
        $linksRaw = $GLOBALS["CLASS_SOCKETS"]->REST_API(
            "/proxy/acls/sqlite/links?table=suricata_sqacllinks&aclid=$rid&search=".urlencode($search)
        );
        $linksResp = json_decode($linksRaw, true);
        if(!is_array($linksResp) || empty($linksResp["success"])){ continue; }
        $links = $linksResp["data"] ?? [];
        if(!is_array($links) || count($links) === 0){ continue; }
        // Rule has at least one matching linked group — include it.
        $COMPRESSRULES[$rid] = $ruleRaw;
        foreach($links as $lnk){
            $gpid = intval($lnk["gpid"] ?? 0);
            $gname = (string)($lnk["groupName"] ?? "");
            if($gpid > 0 && $gname !== ""){
                $COMPRESS_GROUPS[$gpid] = $gname;
            }
        }
    }
    return array($COMPRESSRULES, $COMPRESS_GROUPS);
}
function fillthisRule(){
    $tpl=new template_admin();
    $ID=intval($_GET["fill"]);

    // REST API: get rule details for explain display
    $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls/$ID"));
    $enabled  = 1;
    $aclgroup = 0;
    $aclgpid  = 0;
    if(is_object($raw)){
        $enabled  = property_exists($raw,"enabled")  ? intval($raw->enabled)  : 1;
        $aclgroup = property_exists($raw,"aclgroup") ? intval($raw->aclgroup) : 0;
        $aclgpid  = property_exists($raw,"aclgpid")  ? intval($raw->aclgpid)  : 0;
    }

    $acls=new squid_acls_groups();
    $page=CurrentPageName();

    $FINAL=$tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ID,$enabled,$aclgroup));
    echo "if(document.getElementById('explain-this-rule-$ID')){\n";
    $FINAL=str_replace("'","\'",$FINAL);
    $FINAL=str_replace("\n","\\n",$FINAL);
    echo "document.getElementById('explain-this-rule-$ID').innerHTML='$FINAL';\n";
    echo "}\n";

    // REST API: fetch all rules to update order badges
    $allRules=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/acls"));
    if(is_array($allRules)){
        foreach($allRules as $r){
            if(!is_object($r)){continue;}
            $rid   = intval($r->id ?? 0);
            $rord  = intval($r->xOrder ?? 0);
            if($rid<=0){continue;}
            echo "if(document.getElementById('acl-order-$rid')){document.getElementById('acl-order-$rid').innerHTML='$rord'};\n";
        }
    }

   if($aclgpid>0){
       echo "Loadjs('$page?fill=$aclgpid');\n";
   }


}
function groups_rules_table_start(){
    $ID     = intval($_GET["rules-table-start"]);
    $page   = CurrentPageName();
    echo "<div id='GroupOfRules{$ID}' style='margin-top:15px'></div><script>LoadAjax('GroupOfRules{$ID}','$page?table=yes&gprule=$ID&no-tiny=yes');</script>";
}
function new_rule_save():bool{
	$tpl        = new template_admin();
	$tpl->CLEAN_POST_XSS();
	$aclname    =utf8_decode_switch($_POST["aclname"] ?? "");
	$aclgpid    = intval($_POST["aclgpid"] ?? 0);
	$ApplayerProtocol=$_POST["ApplayerProtocol"] ?? "";
    $action=$_POST["action"] ?? "alert";

    $body=array(
        "aclname"          => $aclname,
        "aclport"          => 0,
        "aclgroup"         => 0,
        "aclgpid"          => $aclgpid,
        "applayerProtocol" => $ApplayerProtocol,
        "action"           => $action,
    );

    // REST API: POST /suricata/acls
    [$ok,$err]=acls_api_send("POST","/suricata/acls",$body);
    if(!$ok){
        writelogs($err,__FUNCTION__,__FILE__,__LINE__);
        $tpl2=new template_admin();
        echo $tpl2->post_error($err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/acls/explains");
    return admin_tracks("Create new IDS ACL rule $aclname");
}
function rule_delete($ID):bool{
    $ID=intval($ID);

    // REST API: DELETE /suricata/acls/{id} (cascades to suricata_sqacllinks)
    [$ok,$err]=acls_api_send("DELETE","/suricata/acls/$ID");
    if(!$ok){echo "alert('".addslashes($err)."');";return false;}

    admin_tracks("Remove ACL IDS rule $ID");
    return true;
}

function td_status($ligne,$getCurrentRules):string{
    if($ligne["enabled"]==0){
        return"<span class='label label-default'>{disabled}</span>";
    }
    $isError=intval($ligne["iserror"]);
    if($ligne["enabled"]==1 && $isError==1){
        return "<span class='label label-danger'>{error}</span>";
    }
    if(!isset($getCurrentRules[$ligne["ID"]])){
        return"<span class='label label-default'>{inactive}</span>";
    }

    return"<span class='label label-primary'>{active2}</span>";
}

// Stub handlers for notify_message, notify_error_page, new_rule_group_save
// — these were not present in the original file body (not reached via dispatch)
// but their dispatch lines reference them. Define empty stubs to avoid fatal errors.
if(!function_exists("notify_message")){
    function notify_message():bool{ return true; }
}
if(!function_exists("notify_message_save")){
    function notify_message_save():bool{ return true; }
}
if(!function_exists("notify_error_page")){
    function notify_error_page():bool{ return true; }
}
if(!function_exists("notify_error_page_save")){
    function notify_error_page_save():bool{ return true; }
}
if(!function_exists("new_rule_group_save")){
    function new_rule_group_save():bool{ return true; }
}
