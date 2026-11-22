<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
// Politique Artica Meta "ACL proxy" : regles metaid et SquidAclFinishDeny en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.proxyacls.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["AclsUsePages"])){$tpl=new template_admin();$tpl->SAVE_POSTs();exit;}
if(isset($_GET["search"])){table_builder();exit;}
if(isset($_GET["toggle-items-search"])){toggle_items_search();exit;}
// Pentest 2026-11-04 : enabled/aclgroup etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"],intval($_GET["enabled"] ?? 0),intval($_GET["aclgroup"] ?? 0));exit;}
if(isset($_GET["opts"])){opts_js();exit;}
if(isset($_GET["opts-popup"])){opts_popup();exit;}
if(isset($_GET["tiny-js"])){TINY_PAGE();exit;}
if(isset($_GET["file-uploaded"])){import_uploaded_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["externalALCLDAPRecursive"])){externalALCLDAPRecursive();exit;}
if(isset($_GET["externalALCLDAPRecursive-popup"])){externalALCLDAPRecursive_popup();exit;}
if(isset($_GET["externalALCLDAPRecursive-switch"])){externalALCLDAPRecursive_switch();exit;}
if(isset($_GET["bungled-js"])){bungled_js();exit;}
if(isset($_GET["bungled-popup"])){bungled_popup();exit;}
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
if(isset($_GET["export-rule-exported"])){export_rule_exported();exit;}
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

page();

function SquidAclFinishDeny_js():bool{
    if(proxyacls_meta_finish_held()){proxyacls_meta_refuse_js();return false;}
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
function opts_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{options}","$page?opts-popup=yes&function={$_GET["function"]}");
}
function bungled_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{squid_compile_failed}","$page?bungled-popup=yes&function={$_GET["function"]}");
}
function bungled_popup():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/bungled"),true);
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($json["Status"])) {
        echo $tpl->_ENGINE_parse_body("{protocol_error}");
        return true;
    }

    if(!isset($json["isbungled"])) {
        echo $tpl->_ENGINE_parse_body("{protocol_error}");
        return true;
    }
    $squid_compile_failed=$tpl->_ENGINE_parse_body("{squid_compile_failed}");
    $squid_compile_failed_explain=$tpl->_ENGINE_parse_body("{squid_compile_failed_explain}");
    $squid_compile_failed_explain=str_replace("%d",$tpl->time_to_date($json["bungled_time"],true),$squid_compile_failed_explain);
    $html[]=$tpl->div_explain("$squid_compile_failed||$squid_compile_failed_explain");
    $jj=explode("\n",$json["BungledContent"]);
    krsort($jj);
    foreach ($jj as $line) {
        if(preg_match("#(fatal|error|bungled)#i",$line)){
            $html[]="<div style='font-size:12px;' class='text-danger'>$line</div>";
            continue;
        }
        $html[]="<div style='font-size:12px;' class='text-muted'>$line</div>";
    }

    echo $tpl->_ENGINE_parse_body($html);
return true;

}
function opts_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $AclsUsePages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUsePages"));
    $AclsUseRows=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseRows"));
    $SquidBuilderAclsv1=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBuilderAclsv1"));
    $AclsUseLimitSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseLimitSec"));
    $AclsResultsPerPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsResultsPerPage"));
    if($AclsResultsPerPage==0){$AclsResultsPerPage=$GLOBALS["FOOTABLE_PSIZE"];}
    $ACL_SEARCH_ITEMS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ACL_SEARCH_ITEMS"));
    if($AclsUseLimitSec==0){$AclsUseLimitSec=10;}
    if($AclsUseRows==0){$AclsUseRows=30;}
    $form[]=$tpl->field_checkbox("AclsUsePages","{page_interval}",$AclsUsePages,"AclsResultsPerPage");
    $form[]=$tpl->field_numeric("AclsResultsPerPage","{results} {per} {page}",$AclsResultsPerPage);
    $form[]=$tpl->field_numeric("AclsUseRows","{maximum} {results}",$AclsUseRows);
    $form[]=$tpl->field_checkbox("SquidBuilderAclsv1","ACLs builder v1",$SquidBuilderAclsv1,);

    $form[]=$tpl->field_numeric("AclsUseLimitSec","{max_time} ({seconds})",$AclsUseLimitSec);
    $form[]=$tpl->field_checkbox("ACL_SEARCH_ITEMS","{search_inside_item_descriptions}",$ACL_SEARCH_ITEMS);

    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function()");
    return true;


}


function export_rule_exported():bool{
    $filename = basename($_GET["export-rule-exported"]);
    $path = "/usr/share/artica-postfix/ressources/conf/upload/".$filename;
    if (!preg_match('/^acls-rule-\d+\.json\.gz$/', $filename) || !is_file($path)) {
        http_response_code(404); echo "Not found"; return false;
    }
    header("Content-Type: application/gzip");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: ".filesize($path));
    ob_clean();
    flush();
    readfile($path);
    @unlink($path);                          // optional: one-shot download
    return true;
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
        $html[] = $tpl->button_autnonome("{disable_feature}", "Loadjs('$page?externalALCLDAPRecursive-switch=yes&gprule=$gprule&function=$function')", ico_check, "AsDansGuardianAdministrator", "335");
    }else{
        $html[] = $tpl->button_autnonome("{enable_feature}", "Loadjs('$page?externalALCLDAPRecursive-switch=yes&gprule=$gprule&function=$function')", ico_check, "AsDansGuardianAdministrator", "335");
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
function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{import}","$page?import-popup=yes&function=$function",550);
}
function import_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $html[]="<div id='acls-import-progress'></div>";
    $html[]=$tpl->div_explain("{import}||{import_acl_explain2}");
    $html[]="<div class='center' style='margin: 30px'>".
        $tpl->button_upload("{upload_a_file}",$page,"","&function=$function")."</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function import_uploaded_js():bool{
    $tpl=new template_admin();
    $filename   = $_GET["file-uploaded"];
    $fileencode = urlencode($filename);
    $function=$_GET["function"];
    admin_tracks("$filename was uploaded to Proxy ACLs rules");
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proxy/acls-import/$fileencode", array());

    $js=$tpl->framework_buildjs("/ping",
        "acls.parse.progress",
        "acls.logs",
        "progress-acls-restart",
        "$function()");

   echo "$js\n";
    return true;
}




function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{PROXY_ACLS}","fad fa-shield-alt","{PROXY_ACLS_EXPLAIN}<div id='proxy-acls-bugs'></div>","$page?table=yes","proxy-acls-access","progress-acls-restart",false,"table-acls-rules");

    // A direct browser load or an F5 refresh (URL bar = fw.proxy.acls.php) is NOT
    // an AJAX request: the framework JS from /index (default.js -> LoadAjax) is
    // absent, so the page_header fragment's inline `LoadAjax('table-acls-rules',
    // '?table=yes')` bootstrap throws "LoadAjax is not defined" and the table
    // stays empty. Render the full standalone page (build_firewall pulls default.js
    // in) whenever this isn't a framework XHR. The left-menu keeps the lightweight
    // fragment: MenuRoot()->LoadAjaxSilent() goes through jQuery $.ajax, which
    // always sends X-Requested-With: XMLHttpRequest.
    $isAjax = (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest");
    if(isset($_GET["main-page"]) || !$isAjax){
        $tpl=new template_admin("{PROXY_ACLS}",$html);
        echo $tpl->build_firewall();
        return;
    }
	echo $tpl->_ENGINE_parse_body($html);

}

function change_order_js():bool{
    $page    = CurrentPageName();
    $tpl     = new template_admin();
    $ID      = intval($_GET["change-order"]);
    $rule    = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(proxyacls_meta_rule($rule)){proxyacls_meta_refuse_js();return false;}
    $current = $rule ? intval($rule["xOrder"]) : 0;
    $jsafter = "LoadAjax('table-acls-rules','$page?table=yes');";
    return $tpl->js_prompt("{order}","{give_new_position}","fas fa-sort-numeric-up-alt",$page,"NewOrder",$jsafter,$current,"$ID");
}

function change_order_save(){
    $xORDER  = intval($_POST["NewOrder"]);
    $RuleID  = intval($_POST["KeyID"]);
    if(proxyacls_meta_rule_id($RuleID)){proxyacls_meta_refuse_text();return;}
    list($ok,$data) = acls_api_send("PUT","/proxy/acls/sqlite/rule/$RuleID/order",["xOrder"=>$xORDER]);
    if(!$ok){ echo $data; }
}

function rule_delete_js(){
    $ID = intval($_GET["rule-delete-js"]);
    $md = "acl-$ID";
    $rule = acls_api_get("/proxy/acls/sqlite/rule/".intval($ID));
    if(!$rule){
        header("content-type: application/x-javascript");
        echo "alert('rule $ID not found');";
        return;
    }
    if(proxyacls_meta_rule($rule)){proxyacls_meta_refuse_js();return;}
    if(intval($rule["aclgroup"])==1){
        $tpl = new template_admin();
        $aclname = $rule["aclname"];
        $tpl->js_confirm_delete("{group_of_rules}<br><strong>$aclname</strong><br>{group_of_rules_delete_ask}","rule-delete-confirm",$ID,"$('#$md').remove();");
        return;
    }
    header("content-type: application/x-javascript");
    if(!rule_delete($ID)){return;}
    echo "$('#$md').remove();";
}
function rule_delete_confirm():bool{
    $ID = intval($_POST["rule-delete-confirm"]);
    if(proxyacls_meta_rule_id($ID)){return proxyacls_meta_refuse_text();}
    return rule_delete($ID);
}

function proxy_acls_bugs():bool{
    $SQUID_ACLS_BUGS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ACLS_BUGS"));
    if($SQUID_ACLS_BUGS==0){return true;}
    $tpl=new template_admin();
    $error=$tpl->_ENGINE_parse_body("{acls_check_errors}");
    $error=str_replace("%s",$SQUID_ACLS_BUGS,$error);
    $bt=$tpl->button_autnonome("{view}", "Loadjs('fw.proxy.acls.bugs.php')", "fas fa-bug","AsDansGuardianAdministrator",100,"btn-danger");
    $html[]="<div style='float:right;margin-right:50px;margin-bottom:20px'>$bt</div>";
    $html[]=$error;
    echo $tpl->_ENGINE_parse_body($tpl->div_error(@implode("\n",$html)));

    return true;

}


function duplicate_js(){
    $ID_SRC = intval($_GET["duplicate-js"]);
    $rule   = acls_api_get("/proxy/acls/sqlite/rule/$ID_SRC");
    if(!$rule){
        header("content-type: application/x-javascript");
        echo "alert('rule $ID_SRC not found');";
        return;
    }
    list($ok,$data) = acls_api_send("POST","/proxy/acls/sqlite/rule/$ID_SRC/duplicate",[]);
    if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".addslashes($data)."');";
        return;
    }
    $newID = intval($data["newID"] ?? 0);
    admin_tracks("Duplicate {$rule["aclname"]} proxy rule");

    $page = CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-acls-rules','$page?table=yes');";
}

function rule_enable(){
    $page    = CurrentPageName();
    $ID      = intval($_GET["enable-js"]);
    $rule    = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    $tpl=new template_admin();
    if(!$rule){
        header("content-type: application/x-javascript");
        return $tpl->js_error("rule $ID not found");
    }
    if(proxyacls_meta_rule($rule)){proxyacls_meta_refuse_js();return false;}
    $aclname = $rule["aclname"];
    $current = intval($rule["enabled"]);
    $new     = $current==0 ? 1 : 0;
    $js      = $new==1
        ? "$( \"#acl-$ID\" ).removeClass( \"text-muted\" );"
        : "$( \"#acl-$ID\" ).addClass( \"text-muted\" );";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/rule/enable/$ID/$new"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    header("content-type: application/x-javascript");
    echo $js;
    echo "\nLoadjs('$page?fill=$ID');\n";
    return admin_tracks("Change $aclname proxy rule activation to $new");
}

function rule_js(){
    $page = CurrentPageName();
    $tpl  = new template_admin();
    $ID   = intval($_GET["rule-id-js"]);
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){
        header("content-type: application/x-javascript");
        echo "alert('rule $ID not found');";
        return;
    }
    $size     = null;
    $aclgroup = intval($rule["aclgroup"]);
    $aclgpid  = intval($rule["aclgpid"]);
    $aclname  = $rule["aclname"];
    if(strlen($aclname)>50){ $aclname = substr($aclname,0,47)."..."; }
    if($aclgroup==1){
        $size = 1024;
        $aclname = "{group_of_rules}: $aclname";
    }
    if($aclgpid>0){
        $size = 990;
        $tpl->js_dialog2("{rule}: $ID {$aclname}","$page?rule-tabs=$ID",$size);
        return;
    }
    $tpl->js_dialog1("{rule}: $ID {$aclname}","$page?rule-tabs=$ID",$size);
}
function new_rule_group_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $tpl->js_prompt("{new_group_of_rules}","{rulename}","fa fa-plus",$page,"new-group-rule",
        "LoadAjax('table-acls-rules','$page?table=yes');");

}



function rule_tabs(){
    $ID         = intval($_GET["rule-tabs"]);
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $rule       = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    $aclgroup   = is_array($rule) ? intval($rule["aclgroup"]) : 0;
    $access_type = is_array($rule) ? ($rule["accessType"] ?? "") : "";

    $RefreshFunction=base64_encode("Loadjs('$page?fill=$ID')");
    $array["{rule}"]="$page?rule-settings=$ID";
    if($aclgroup==0) {
        $array["{proxy_objects}"] = "fw.proxy.acls.objects.php?rule-id=$ID&RefreshFunction=$RefreshFunction";
        if($access_type=="access_deny"){
            $array["{NotifyMessage}"] = "$page?notify-message=$ID";
            $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));
            if($UfdbUseInternalService==1){
                $array["{WEB_ERROR_PAGE}"]="$page?web-error-page=$ID";
            }
        }
    }else{
        $array["{rules}"] = "$page?rules-table-start=$ID&no-tiny=yes";
    }
    echo $tpl->tabs_default($array);
}


function rule_settings(){
    $page = CurrentPageName();
    $tpl  = new template_admin();
    $ID   = intval($_GET["rule-settings"]);
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){ echo $tpl->_ENGINE_parse_body($tpl->div_error("rule $ID not found")); return; }

    $MainGroupRules = [];
    $groups = acls_api_get("/proxy/acls/sqlite/groups");
    if(is_array($groups)){
        foreach($groups as $g){
            $MainGroupRules[intval($g["id"])] = $tpl->utf8_encode($g["aclname"]);
        }
    }
    $MainGroupRules[0] = "{none}";
    $array_access = [
        "access_allow" => "{allow_access}",
        "access_deny"  => "{deny_access}",
    ];

    $aclgroup    = intval($rule["aclgroup"]);
    $aclname     = $tpl->utf8_encode($rule["aclname"]);
    $access_type = $rule["accessType"] ?? "";

    $metaBanner = "";
    if(proxyacls_meta_rule($rule)){
        proxyacls_meta_lock_form($tpl);
        $metaBanner = proxyacls_meta_rule_banner($tpl);
    }
    $form = [];
    $form[]="<div id='rule-export-progress-$ID'></div>";
    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("aclname", "{rule_name}", $aclname,true);
    $form[]=$tpl->field_checkbox("enabled", "{enabled}", $rule["enabled"],true);
    if($aclgroup==0){
        $form[]=$tpl->field_array_hash($MainGroupRules, "aclgpid", "{group_of_rules}", $rule["aclgpid"]);
        $form[]=$tpl->field_array_hash($array_access,   "access",  "{type}", $access_type);
        $tpl->field_hidden("description","");
    }

    $aclgpid = intval($rule["aclgpid"]);
    if($aclgpid==0){
        $form[] = $tpl->field_proxy_ports("PortDirection", "{method}", $rule["aclport"]);
    }else{
        $tpl->field_hidden("PortDirection",0);
    }
    $jsafter = "LoadAjax('table-acls-rules','$page?table=yes');Loadjs('$page?fill=$ID');";

    if($aclgroup==1){
        $tpl->field_hidden("aclgpid",0);
        $form[]=$tpl->field_text("description","{description}", base64_decode($rule["description"] ?? ""));
    }
    $filename="acls-rule-$ID.json.gz";
    $exportjs=$tpl->framework_buildjs(
        "/proxy/acls-export/".$ID,
        "acls.parse", "acls.logs", "rule-export-progress-$ID",
        "document.location.href='".CurrentPageName()."?export-rule-exported=".urlencode($filename)."'");

    $tpl->form_add_button("{export}",$exportjs);
    $html = $metaBanner."<div id='export-progress-$ID' style='margin-top:5px'></div>".
        $tpl->form_outside($aclname, @implode("\n", $form),null,"{apply}",$jsafter,"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

function rule_save(){
    $tpl = new template_admin();
    $ID  = intval($_POST["rule-save"]);
    if(proxyacls_meta_rule_id($ID)){echo proxyacls_meta_refuse_post();return;}
    $tpl->CLEAN_POST_XSS();

    $payload = [
        "aclname"  => $tpl->utf8_decode($_POST["aclname"] ?? ""),
        "enabled"  => intval($_POST["enabled"] ?? 0),
        "aclport"  => intval($_POST["PortDirection"] ?? 0),
        "aclgpid"  => intval($_POST["aclgpid"] ?? 0),
    ];
    if(isset($_POST["description"])){
        $payload["description"] = base64_encode($_POST["description"]);
    }
    if(isset($_POST["access"])){
        $payload["access"] = $_POST["access"];
    }

    list($ok,$data) = acls_api_send("PUT","/proxy/acls/sqlite/rule/$ID", $payload);
    if(!$ok){
        echo "js:error:" . $tpl->javascript_parse_text($data);
        return;
    }
    admin_tracks("Modify settings of {$payload["aclname"]} proxy rule");
}

function new_rule_js(){
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $rulename   = null;
	$gprule     = intval($_GET["gprule"]);
    $function   = $_GET["function"];

	if($gprule>0){
        $rule = acls_api_get("/proxy/acls/sqlite/rule/$gprule");
        $rulename = $rule ? ":".$rule["aclname"] : "";
    }

	$title="{new_rule}$rulename";
	$tpl->js_dialog($title,"$page?newrule-popup=yes&gprule=$gprule&function=$function");
}
function new_rule_popup(){
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $gprule     = intval($_GET["gprule"]);
    $jsafter    = array();
	$array_access["access_allow"]="{allow_access}";
	$array_access["access_deny"]="{deny_access}";
	$explain=$tpl->_ENGINE_parse_body("{new_acls_rule_explain}");
    $function=$_GET["function"];
    $jsafter[]="BootstrapDialog1.close();";

    if($function<>null){
        $jsafter[]="$function()";
    }
	
	$PortDirection=0;
    $MainGroupRules=array();
	if($gprule>0){
	    $tpl->field_hidden("aclgpid","$gprule");
        $jsafter[]="LoadAjax('GroupOfRules{$gprule}','$page?table=yes&gprule=$gprule');";
        $jsafter[]="Loadjs('$page?fill=$gprule');";

    }else{
        $MainGroupRules = [];
        $groups = acls_api_get("/proxy/acls/sqlite/groups");
        if(is_array($groups)){
            foreach($groups as $g){
                $MainGroupRules[intval($g["id"])] = $tpl->utf8_encode($g["aclname"]);
            }
        }
        if(count($MainGroupRules)>0){
            $form[]=$tpl->field_array_hash($MainGroupRules, "aclgpid", "{group_of_rules}", null);
        }else{
            $tpl->field_hidden("aclgpid","0");
        }
    }



	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("aclname", "{rule_name}", null,true);
	$form[]=$tpl->field_array_hash($array_access, "access", "{type}", "access_allow");
	if($gprule==0){$form[]=$tpl->field_proxy_ports( "PortDirection", "{method}", 0);}

	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",
        @implode(";",$jsafter),"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}



function rule_move(){
    $tpl  = new template_admin();
    $ID   = intval($_GET["acl-rule-move"]);
    $dir  = intval($_GET["acl-rule-dir"]);
    if(proxyacls_meta_rule_id($ID)){proxyacls_meta_refuse_js();return;}
    list($ok,$data) = acls_api_send("PUT","/proxy/acls/sqlite/rule/$ID/move",["dir"=>$dir]);
    if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".addslashes($data)."');";
        return;
    }
    $aclname  = $data["aclname"] ?? "";
    $oldOrder = intval($data["oldXOrder"] ?? 0);
    $newOrder = intval($data["newXOrder"] ?? 0);
    admin_tracks("Move Proxy acl order of $aclname from $oldOrder to $newOrder");
    $page = CurrentPageName();
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

    $checkboxHtml = "

<script>
(function(){
    var resDiv = document.querySelector('[id^=\"results-\"]');
    if (resDiv) { window.__aclResultsDivId = resDiv.id; }
})();

</script>";
    echo $tpl->_ENGINE_parse_body($checkboxHtml);
    return true;
}

function toggle_items_search():void{
    $v = intval($_POST["v"] ?? 0);
    $_SESSION["ACL_SEARCH_ITEMS"] = $v == 1 ? 1 : 0;
    header("content-type: application/json;charset=UTF-8");
    echo json_encode(["ok"=>true,"items"=>$_SESSION["ACL_SEARCH_ITEMS"]]);
}
function TINY_PAGE($return=false):string{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $externalALCLDAPRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("externalALCLDAPRecursive"));
    $add="Loadjs('$page?newrule-js=yes&gprule=$gprule&function=$function',true);";
    $addgroup="Loadjs('$page?new-group-rule-js=yes',true);";
    $jsimport="Loadjs('$page?import-js=yes&function=$function');";

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/php/compile",
        "/squid.access.artica.progress","squid.access.center.progress.log","progress-acls-restart","$function()");

    $users=new usersMenus();
    $topbuttons=array();

    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        if ($gprule == 0) {
            $topbuttons[] = array($addgroup, ico_plus, "{new_group_of_rules}");
            if ($function <> null) {
                $topbuttons[] = array("$function()", ico_refresh, "{reload}");
            }

            $topbuttons[] = array("Loadjs('$page?import-js=yes')",ico_download, "{import}");
            $topbuttons[] = array("Loadjs('fw.proxy.acls.backups.php?function=$function')",ico_file_zip, "{backup}");

            if($EnableActiveDirectoryFeature==1){
                $addon="OFF";
                if($externalALCLDAPRecursive==1){
                    $addon="ON";
                }
                $topbuttons[] = array("Loadjs('$page?externalALCLDAPRecursive=yes&gprule=$gprule&function=$function')", ico_group, "{recursive_search} ($addon)");

            }
            $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
        }
    }

    $acls=new squid_acls();
    if($acls->IsAuthenticationEnabled()){
        $topbuttons[] = array("Loadjs('fw.goSquidAuth.php')", ico_group, "{check_groups}");
    }

    if($return){
        return $tpl->table_buttons($topbuttons);
    }

    $TINY_ARRAY["TITLE"]="{PROXY_ACLS}";
    $TINY_ARRAY["ICO"]="fad fa-shield-alt";
    $TINY_ARRAY["EXPL"]="{PROXY_ACLS_EXPLAIN}";
    $TINY_ARRAY["URL"]="proxy-acls-access";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    header("content-type: application/x-javascript");
    echo $jstiny;
    return "";
}
function global_whitelist_labels():array{
    // Mirrors fw.proxy.global.rules.php::table() — the same Valkey keys, the same
    // SquidDisableWhiteLists kill-switch and the same AvVendors default-on rule.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"))==1){return array();}
    $keys=array(
        "AllowWindowsUpdates"=>"{ProxyDedicateMicrosoftRules}",
        "SquidAllowSmartPhones"=>"{AllowSmartphonesRuleText}",
        "AllowSquidDropBox"=>"{AllowSquidDropBox}",
        "AllowSquidWhatsApp"=>"{AllowSquidWhatsApp}",
        "AllowSquidSkype"=>"{AllowSquidSkype}",
        "AllowSquidMicrosoft"=>"{AllowSquidMicrosoft}",
        "AllowSquidOffice365"=>"{AllowSquidOffice365}",
        "AllowSquidTeamViewer"=>"{AllowSquidTeamViewer}",
        "AllowSquidGoogle"=>"{AllowSquidGoogle}",
        "AllowSquidAvVendors"=>"{AllowSquidAvVendors}",
        "AllowSquidGoogleHearth"=>"{AllowSquidGoogleHearth}",
        "AllowSquidCompatibility"=>"{AllowSquidCompatibility}",
        "AllowSquidOtherProtocols"=>"{AllowSquidOtherProtocols}",
        "AllowSquidHSTS"=>"{AllowSquidHSTS}",
        "AllowSquidWhitelistsSSL"=>"{AllowSquidWhitelistsSSL}",
    );
    $GLOBALS["CLASS_SOCKETS"]->PREFETCH_INFO(array_keys($keys));
    $labels=array();
    foreach($keys as $key=>$token){
        $raw=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key);
        $val=($key=="AllowSquidAvVendors" && $raw==="")?1:intval($raw);
        if($val==1){$labels[]=$token;}
    }
    return $labels;
}
function noauth_ports_rows():array{
    // Mirrors fw.proxy.ports.php: an enabled listening port carrying NoAuth=1
    // ("{disable_authentication}") lets everything through, before the rules.
    $rows=array();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    if(!$q->IF_TABLE_EXISTS("proxy_ports")){return $rows;}
    $results=$q->QUERY_SQL("SELECT port,PortName FROM proxy_ports WHERE enabled=1 AND NoAuth=1 ORDER BY port");
    if(!is_array($results) && !is_object($results)){return $rows;}
    foreach($results as $ligne){
        $rows[]=array("port"=>intval($ligne["port"]),"PortName"=>(string)$ligne["PortName"]);
    }
    return $rows;
}
function table_builder():bool{

    if(!isset($_GET["gprule"])){$_GET["gprule"]=0;}
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $order          = $tpl->_ENGINE_parse_body("{order}");
    $rulename       = $tpl->_ENGINE_parse_body("{rulename}");
    $enabledLabel   = $tpl->_ENGINE_parse_body("{enabled}");
    $gprule         = intval($_GET["gprule"]);
    $search         = $_GET["search"] ?? "";
    $function       = $_GET["function"] ?? "";

    $t=md5(time()."{$_GET["gprule"]}".microtime(true));
    $data1="class='text-capitalize' style='width:1%;text-align:center'";

    $html = [];
    if($gprule>0){
        $html[]=TINY_PAGE(true);
    }
    if($gprule==0){
        // Politique Artica Meta "ACL proxy" appliquee : bandeau (deja traduit, hors _ENGINE_parse_body).
        $metaBanner=proxyacls_meta_banner($tpl);
        if($metaBanner!==""){echo $metaBanner;}
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/bungled"),true);

        if(isset($json["Status"])){

            if(isset($json["isbungled"])){
                if($json["isbungled"]){
                    $squid_compile_failed=$tpl->_ENGINE_parse_body("{squid_compile_failed}");
                    $squid_compile_failed_explain=$tpl->_ENGINE_parse_body("{squid_compile_failed_explain}");
                    $squid_compile_failed_explain=str_replace("%d",$tpl->time_to_date($json["bungled_time"],true),$squid_compile_failed_explain);

                    $topbuttons[]=array("Loadjs('$page?bungled-js=yes')",ico_bug,"{view_details}");
                    $btns=$tpl->table_buttons($topbuttons);

                    echo $tpl->div_error("$squid_compile_failed||$squid_compile_failed_explain<div style='margin-top:15px;text-align:right;margin-right:15px;'>$btns</div>");

                }
            }
        }
    }
    $AclsUsePages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUsePages"));
    $limit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseRows"));
    if($limit==0){$limit=120;}

    // Honor inline rows=/max=/limit= keyword — historic behavior.
    if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
        $limit=intval($re[2]);
        $search=trim(str_replace("$re[1]=$re[2]","",$search));
    }
    $_SESSION["ACL_SEARCH"]=$search;
    $ACL_SEARCH_ITEMS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ACL_SEARCH_ITEMS"));
    $includeItems = intval($ACL_SEARCH_ITEMS ?? 0);

    // Single call into the Go backend replaces the 4-way SQL join, per-row
    // REST explain, and http_access.conf parsing that used to run here.
    $endpoint = "/proxy/webconsole/acls/table"
        ."?gprule=$gprule"
        ."&search=".urlencode($search)
        ."&items=$includeItems"
        ."&limit=$limit";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT = 60;
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
    $resp = json_decode($raw, true);
    if(isset($resp["Status"]) && isset($resp["Error"])){
        if(!$resp["Status"]){
            if (strlen($resp["Error"])>3){
                echo $tpl->_ENGINE_parse_body($tpl->div_error($resp["Error"]));
                return false;
            }
        }
    }

    if(!is_array($resp) || empty($resp["success"])){
        $msg = is_array($resp) ? ($resp["error"] ?? "unknown error") : "unreachable";
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{acls_backend_error}: ".htmlspecialchars($msg)));
        return false;
    }

    $data      = $resp["data"] ?? [];
    $rules     = is_array($data["rules"] ?? null) ? $data["rules"] : [];
    $sGroups   = is_array($data["searchGroups"] ?? null) ? $data["searchGroups"] : [];
    $activeArr = is_array($data["activeRuleIDs"] ?? null) ? $data["activeRuleIDs"] : [];
    $activeSet = array_flip($activeArr);
    $truncated = !empty($data["truncated"]);

    $elapsedMs = intval($data["elapsedMs"] ?? 0);
    $BudgetMs = intval($data["BudgetMs"] ?? 0);
    if(!empty($data["budgetExceeded"])){
        $pending   = intval($data["explainPending"]   ?? 0);
        $completed = intval($data["explainCompleted"] ?? 0);
        echo $tpl->_ENGINE_parse_body($tpl->div_warning(
            "{timeout} ({$elapsedMs}ms > {$BudgetMs}ms) — $pending/" .($pending+$completed)." {explain_still_warming}"
        ));
    }

    $AclsResultsPerPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsResultsPerPage"));
    if($AclsResultsPerPage==0){$AclsResultsPerPage=$GLOBALS["FOOTABLE_PSIZE"];}
    // L'en-tete declarait DIX colonnes (dont quatre vides) pour des lignes qui
    // en emettaient NEUF: tout etait decale d'un cran a partir du bouton de
    // rafraichissement. Le tableau en compte desormais CINQ, la description
    // ayant rejoint le nom de la regle dont elle est le sous-titre.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th class='awc-nowrap'>$order</th>";
    $html[]="<th>$rulename</th>";
    $html[]="<th class='awc-nowrap'>{status}</th>";
    $html[]="<th class='awc-nowrap'>$enabledLabel</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='acls-rows'>";

    if($truncated){
        $html[]="<tr><td colspan='5' style='padding:6px 10px;'>"
            .$tpl->div_warning("{search_truncated_explain}")."</td></tr>";
    }

    // Sans filtre actif, on expose les autorisations permanentes activees dans
    // fw.proxy.global.rules.php: elles sont compilees AVANT les regles ci-dessous,
    // donc une destination bloquee peut malgre tout passer par l'une d'elles.
    if($gprule==0 && trim($search)==""){
        $wlLabels=global_whitelist_labels();
        if(count($wlLabels)>0){
            $gotoGlobal="LoadAjaxSilent('MainContent','fw.proxy.global.rules.php');";
            $ww=array();
            foreach($wlLabels as $token){
                $ww[]="<span class='awc-chip'><i class='fa-solid fa-check'></i>&nbsp;$token</span>";
            }
            $html[]=acls_info_row("acl-global-whitelists","fas fa-shield-halved",
                $tpl->td_href("{permanent_authorizations}","{acls_whitelist_row_explain}",$gotoGlobal),
                "{acls_whitelist_row_explain}",
                "<div class='awc-chips'>".@implode("",$ww)."</div>",
                "<span class='awc-badge awc-badge-on'>{active2}</span>");
        }

        // Ports d'ecoute portant l'option "{disable_authentication}" (NoAuth):
        // tout ce qui entre par la est autorise avant les regles ci-dessous.
        foreach(noauth_ports_rows() as $pinfo){
            $pnum=intval($pinfo["port"]);
            $pname=htmlspecialchars($pinfo["PortName"]);
            $html[]=acls_info_row("acl-noauth-port-$pnum","fas fa-door-open",
                "{everything_allowed_from_the_port} $pnum",
                "{everything_allowed_from_the_port_explain}",
                "<div class='awc-chips'><span class='awc-chip'><i class='fa-solid fa-check'></i>&nbsp;$pname</span></div>",
                "<span class='awc-badge awc-badge-on'>{active2}</span>");
        }
    }

    if(count($sGroups)>0){
        $ff = [];
        foreach ($sGroups as $g){
            $gpid = intval($g["gpid"]);
            $gname = htmlspecialchars($g["groupName"] ?? "");
            $ff[]="<span class='awc-chip'><i class='fas fa-cubes'></i>&nbsp;"
                 .$tpl->td_href($gname,"","Loadjs('fw.rules.items.php?groupid=$gpid$function');")."</span>";
        }
        $html[]=acls_info_row("acl--1","fas fa-cubes","{follow_group_found}","",
            "<div class='awc-chips'>".@implode("",$ff)."</div>","");
    }

    $anyActive = count($activeArr) > 0;
    $lastID    = 0;

    foreach($rules as $rule) {
        $lastID = intval($rule["id"]);
        $html[] = acls_rule_row($rule,$activeSet,$anyActive);
    }

    $SquidAclFinishDeny=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAclFinishDeny"));
    $AclFinishDeny = $SquidAclFinishDeny==1 ? 0 : 1;
    $html[]=acls_final_row($lastID,$AclFinishDeny);

    $html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='acls-pager'></span></div>";
	$html[]="</div></div>";

    $toTiny="Loadjs('$page?tiny-js=yes&gprule=$gprule&function=$function')";
    if($gprule>0){$toTiny=null;}

    $html[]="<script>";

    $html[]="$toTiny
    console.log('ACL table elapsed=".intval($elapsedMs)."ms rules=".count($rules)."');
    NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
    LoadAjax('proxy-acls-bugs','$page?proxy-acls-bugs=yes');
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($AclsUsePages==1?$AclsResultsPerPage:100000,"acls-rows","acls-pager");
    return true;
}

/**
 * Une regle d'ACL.
 * La description -- le "pourquoi" de la regle, calcule par le daemon -- occupait
 * une colonne a part; elle est devenue le sous-titre du nom, et le bouton qui
 * la recalcule a rejoint la colonne d'actions.
 */
function acls_rule_row($rule,$activeSet,$anyActive):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID          = intval($rule["id"]);
    $aclname     = (string)($rule["aclname"] ?? "");
    $ruleEnabled = intval($rule["enabled"] ?? 0);
    $aclgroup    = intval($rule["aclgroup"] ?? 0);
    $xOrder      = intval($rule["xOrder"] ?? 0);
    $explain     = base64_decode((string)($rule["explainHTML"] ?? ""));

    // Regle d'une politique Artica Meta : lecture seule (ni suppression, ni deplacement, ni activation).
    $isMeta = proxyacls_meta_rule($rule);
    $wsec   = proxyacls_meta_sec($isMeta);
    $delete = $tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')",$wsec);
    $js     = "Loadjs('$page?rule-id-js=$ID')";
    $up     = $tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');",$wsec);
    $down   = $tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');",$wsec);
    $reload = $tpl->icon_refresh("LoadAjaxTiny('explain-this-rule-$ID','$page?explain-this-rule=$ID&enabled=$ruleEnabled&aclgroup=$aclgroup')","{refresh_description_of_thisrule}");
    $copy   = $tpl->icon_copy("Loadjs('$page?duplicate-js=$ID')","AsDansGuardianAdministrator");

    $status=acls_status_badge($ID,$ruleEnabled,$aclgroup,$activeSet,$anyActive);
    if(strlen($aclname)>50){ $aclname = substr($aclname,0,47)."..."; }

    $row_order=$isMeta
        ? "<span class='awc-badge awc-badge-off' id='acl-order-$ID'>$xOrder</span>"
        : $tpl->td_href("<span class='awc-badge awc-badge-off' id='acl-order-$ID'>$xOrder</span>",
        null,"Loadjs('$page?change-order=$ID');");

    $off = ($ruleEnabled==0);
    $h ="<tr id='acl-$ID'>";
    $h.="<td class='awc-nowrap'>$row_order</td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas fa-filter'></i></span>";
    $h.="<div><div class='awc-name".($off?" awc-name-off":"")."'>"
       .$tpl->td_href(htmlspecialchars($aclname),$isMeta?"{view}":"{click_to_edit}",$js).proxyacls_meta_badge($rule)."</div>";
    $h.="<div class='awc-meta'><span id='explain-this-rule-$ID' "
       ."data='$page?explain-this-rule=$ID&enabled=$ruleEnabled&aclgroup=$aclgroup'>$explain</span></div>";
    $h.="</div></div></td>";
    $h.="<td class='awc-nowrap'>$status</td>";
    $h.="<td class='awc-nowrap'>".$tpl->icon_check($ruleEnabled,"Loadjs('$page?enable-js=$ID')",null,$wsec)."</td>";
    $h.="<td><div class='awc-acts'>$reload$copy$up$down$delete</div></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Pastille d'etat d'une regle.
 * Trois sources distinctes: une regle de groupe suit son propre drapeau, une
 * regle racine suit la liste des regles effectivement compilees, et cette liste
 * est vide tant que squid n'a rien compile -- d'ou le troisieme etat "inconnu",
 * qui ne doit surtout pas etre affiche comme "inactif".
 */
function acls_status_badge($ID,$ruleEnabled,$aclgroup,$activeSet,$anyActive):string{
    if($aclgroup>0){
        return $ruleEnabled==0
            ? "<span class='awc-badge awc-badge-off'>{inactive}</span>"
            : "<span class='awc-badge awc-badge-on'>{active2}</span>";
    }
    if(!$anyActive){ return "<span class='awc-badge awc-badge-off'>{unknown}</span>"; }
    if(isset($activeSet[$ID])){ return "<span class='awc-badge awc-badge-on'>{active2}</span>"; }
    return "<span class='awc-badge awc-badge-off'>{inactive}</span>";
}

/**
 * Une ligne d'information non modifiable: autorisations permanentes, port sans
 * authentification, groupes trouves par la recherche. Les trois etaient ecrites
 * en toutes lettres, chacune avec ses cinq icon_nothing() de remplissage.
 */
function acls_info_row($rowid,$ico,$title,$explain,$body,$status):string{
    $h ="<tr id='$rowid'>";
    $h.="<td class='awc-nowrap'>&nbsp;</td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='$ico'></i></span>";
    $h.="<div><div class='awc-name'>$title</div>";
    if(trim((string)$explain)!==""){$h.="<div class='awc-meta'><i>$explain</i></div>";}
    if(trim((string)$body)!==""){$h.="<div class='awc-meta'>$body</div>";}
    $h.="</div></div></td>";
    $h.="<td class='awc-nowrap'>$status</td>";
    $h.="<td class='awc-nowrap'><span class='awc-none'>&mdash;</span></td>";
    $h.="<td>&nbsp;</td>";
    $h.="</tr>";
    return $h;
}

/** Regle implicite de fin de chaine: tout autoriser, ou tout refuser. */
function acls_final_row($lastID,$AclFinishDeny):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $off=($AclFinishDeny==0);
    $h ="<tr id='acl-$lastID-final'>";
    $h.="<td class='awc-nowrap'>&nbsp;</td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas fa-flag-checkered'></i></span>";
    $h.="<div><div class='awc-name".($off?" awc-name-off":"")."'>{finally}</div>";
    $h.="<div class='awc-meta'>{finally_allow_all}</div>";
    $h.="</div></div></td>";
    $h.="<td class='awc-nowrap'><span class='awc-none'>&mdash;</span></td>";
    $h.="<td class='awc-nowrap'>".$tpl->icon_check($AclFinishDeny,"Loadjs('$page?SquidAclFinishDeny=yes')",null,proxyacls_meta_sec(proxyacls_meta_finish_held()))."</td>";
    $h.="<td>&nbsp;</td>";
    $h.="</tr>";
    return $h;
}

function fillthisRule(){
    $tpl  = new template_admin();
    $ID   = intval($_GET["fill"]);
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){ return; }
    $acls       = new squid_acls_groups();
    $enabled    = intval($rule["enabled"]);
    $aclgroup   = intval($rule["aclgroup"]);
    $aclgpid    = intval($rule["aclgpid"]);
    $page       = CurrentPageName();

    $FINAL = $tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ID,$enabled,$aclgroup));
    echo "if(document.getElementById('explain-this-rule-$ID')){\n";
    $FINAL = str_replace("'","\'",$FINAL);
    $FINAL = str_replace("\n","\\n",$FINAL);
    echo "document.getElementById('explain-this-rule-$ID').innerHTML='$FINAL';\n";
    echo "}\n";

    $orders = acls_api_get("/proxy/acls/sqlite/rules/orders");
    if(is_array($orders)){
        foreach($orders as $o){
            $oid = intval($o["id"]);
            $ox  = intval($o["xOrder"]);
            echo "if(document.getElementById('acl-order-$oid')){document.getElementById('acl-order-$oid').innerHTML='$ox'};\n";
        }
    }
    if($aclgpid>0){ echo "Loadjs('$page?fill=$aclgpid');\n"; }
}

function EXPLAIN_THIS_RULE($ID,$enabled,$aclgroup):string{
    $ID=intval($ID);
    if($ID==0){
        return "<span style='color:red'>??? wrong ID??</span>";
    }
    $acls=new squid_acls_groups();
    $tpl=new template_admin();
    $page=CurrentPageName();
    // force=true → Go side regenerates HTML and persists zExplain + advances
    // zExplainCount, so a subsequent /proxy/webconsole/acls/table call hits
    // the cache. The PHP-side UPDATE that used to live here wrote a wrongly
    // base64-wrapped value — removed.
    $FINAL=$acls->ACL_MULTIPLE_EXPLAIN($ID,$enabled,$aclgroup,null,true);

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID&enabled=$enabled&aclgroup=$aclgroup'>$FINAL</span>");
}

function new_rule_group_save(){
    $tpl     = new template_admin();
    $tpl->CLEAN_POST_XSS();
    $aclname = utf8_decode($_POST["new-group-rule"] ?? "");

    list($ok,$data) = acls_api_send("POST","/proxy/acls/sqlite/rule",[
        "aclname"=>$aclname, "aclport"=>0, "aclgpid"=>0, "aclgroup"=>1,
    ]);
    if(!$ok){
        echo $tpl->javascript_parse_text($data);
        return;
    }
    admin_tracks("Create new ACL rule $aclname");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
}
function groups_rules_table_start(){
    $ID     = intval($_GET["rules-table-start"]);
    $page   = CurrentPageName();
    echo "<div id='GroupOfRules{$ID}' style='margin-top:15px'></div><script>LoadAjax('GroupOfRules{$ID}','$page?table=yes&gprule=$ID&no-tiny=yes');</script>";
}

function new_rule_save(){
    $tpl     = new template_admin();
    $tpl->CLEAN_POST_XSS();
    $aclport = intval($_POST["PortDirection"]);
    $aclname = $_POST["aclname"] ?? "";
    $aclgpid = intval($_POST["aclgpid"] ?? 0);
    $access  = $_POST["access"] ?? "access_allow";

    list($ok,$data) = acls_api_send("POST","/proxy/acls/sqlite/rule",[
        "aclname"=>$aclname, "aclport"=>$aclport, "aclgpid"=>$aclgpid,
        "aclgroup"=>0, "access"=>$access,
    ]);
    if(!$ok){
        echo $tpl->javascript_parse_text($data);
        return false;
    }
    return true;
}

function rule_delete($ID){
    $ID = intval($ID);
    list($ok,$data) = acls_api_send("DELETE","/proxy/acls/sqlite/rule/$ID");
    if(!$ok){
        echo "alert('".addslashes($data)."');";
        return false;
    }
    $aclname = $data["aclname"] ?? "rule#$ID";
    admin_tracks("Remove $aclname proxy ACL rule");

    // Cross-DB cleanup: ufdb_page_rules in webfilter.db (best-effort).
    list($ok2,$d2) = acls_api_send("DELETE","/proxy/acls/sqlite/ufdb-page-rules/$ID");
    if(!$ok2){
        error_log("rule_delete: ufdb-page-rules cleanup failed for ID=$ID: $d2");
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}



function notify_error_page():bool{
    $ID  = intval($_GET["web-error-page"]);
    $tpl = new template_admin();
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){ echo $tpl->_ENGINE_parse_body($tpl->div_error("rule $ID not found")); return false; }

    $UfdbUseInternalService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));
    $ENABLE_ERROR_PAGE = $UfdbUseInternalService==0;

    $zTemplate = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($rule["zTemplate"]);
    if(!is_array($zTemplate)){ $zTemplate = []; }
    if(proxyacls_meta_rule($rule)){ proxyacls_meta_lock_form($tpl); echo proxyacls_meta_rule_banner($tpl); }
    $form[]=$tpl->field_hidden("notify-error-page",$ID);
    $form[]=$tpl->field_checkbox("ENABLE_ERROR_PAGE","{redirect_error_page}",$zTemplate["ENABLE_ERROR_PAGE"] ?? 0,false,$ENABLE_ERROR_PAGE);
    $form[]=$tpl->field_checkbox("FREE_REDIRECT","{redirect_to_email}",$zTemplate["FREE_REDIRECT"] ?? 0,"FREE_REDIRECT_URL,FREE_REDIRECT_JAVASCRIPT");
    $form[]=$tpl->field_text("FREE_REDIRECT_URL","{destination}",$zTemplate["FREE_REDIRECT_URL"] ?? "");
    $page = CurrentPageName();
    $form[]=$tpl->field_checkbox("FREE_REDIRECT_JAVASCRIPT","{use_javascript}",$zTemplate["FREE_REDIRECT_JAVASCRIPT"] ?? 0);

    $jsafter = "LoadAjax('table-acls-rules','$page?table=yes');Loadjs('$page?fill=$ID');";
    $html[]=$tpl->form_outside("($ID): {enable_NAT_proxy}", $form,"{ufdbguard_redirect_ssl_explain}","{apply}",$jsafter,"AsDansGuardianAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function notify_message(){
    $page = CurrentPageName();
    $ID   = intval($_GET["notify-message"]);
    $tpl  = new template_admin();
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){ echo $tpl->_ENGINE_parse_body($tpl->div_error("rule $ID not found")); return; }

    $zTemplate = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($rule["zTemplate"]);
    if(!is_array($zTemplate)){ $zTemplate = []; }
    if(!isset($zTemplate["ENABLE"])){$zTemplate["ENABLE"]=0;}
    if(!isset($zTemplate["BODY"])){$zTemplate["BODY"]=null;}
    if(!isset($zTemplate["TITLE"])){$zTemplate["TITLE"]=null;}
    if(!isset($zTemplate["TEMPLATE_ID"])){$zTemplate["TEMPLATE_ID"]=0;}
    if($zTemplate["BODY"]==null){
        $zTemplate["BODY"] = "<div id=\"titles\">\n<h1>ERROR</h1>\n<h2>Cache Access Denied.</h2>\n</div>\n<hr>\n\n<div id=\"content\">\n<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>\n\n<blockquote id=\"error\">\n<p><b>Cache Access Denied.</b></p>\n</blockquote>\n\n<p>Sorry, you are not currently allowed to request %U from this cache until you have authenticated yourself.</p>\n\n<p>Please contact the <a href=\"mailto:%w%W\">cache administrator</a> if you have difficulties authenticating yourself.</p>\n\n<br>\n</div>\n\n<hr> \n<div id=\"footer\">\n<p>Generated %T by %h (%s)</p>\n<!-- %c -->\n</div>";
    }
    if($zTemplate["TITLE"]==null){$zTemplate["TITLE"]="ERROR: Internet Access Denied";}
    if(intval($zTemplate["TEMPLATE_ID"])==0){$zTemplate["TEMPLATE_ID"]=1;}

    if(proxyacls_meta_rule($rule)){ proxyacls_meta_lock_form($tpl); echo proxyacls_meta_rule_banner($tpl); }
    $form[]=$tpl->field_hidden("notify-message",$ID);
    $form[]=$tpl->field_checkbox("ENABLE","{enable}",$zTemplate["ENABLE"],true);
    $form[]=$tpl->field_templates("TEMPLATE_ID","{template}",$zTemplate["TEMPLATE_ID"]);
    $form[]=$tpl->field_text("TITLE", "{subject}", $zTemplate["TITLE"]);
    $form[]=$tpl->field_textareacode("BODY","{content}",$zTemplate["BODY"]);
    $tpl->form_add_button("{help}", "Loadjs('fw.proxy.templates.error.squid.php?help-js')");
    $jsafter = "LoadAjax('table-acls-rules','$page?table=yes');Loadjs('$page?fill=$ID');";
    echo $tpl->form_outside("($ID): {NotifyMessage}", $form,null,"{apply}",$jsafter,"AsDansGuardianAdministrator",true);
}

function notify_message_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["ENABLE"]==1){ $_POST["ENABLE_ERROR_PAGE"]=0; }

    $ID = intval($_POST["notify-message"]);
    if(proxyacls_meta_rule_id($ID)){echo proxyacls_meta_refuse_post();return false;}
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){ echo $tpl->_ENGINE_parse_body("rule $ID not found"); return false; }
    $aclname = $rule["aclname"];
    $zTemplate = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($rule["zTemplate"]);
    if(!is_array($zTemplate)){ $zTemplate = []; }
    foreach($_POST as $key=>$value){ $zTemplate[$key] = $value; }
    $zTemplateB64 = base64_encode(serialize($zTemplate));

    list($ok,$data) = acls_api_send("PUT","/proxy/acls/sqlite/rule/$ID/template",["zTemplateB64"=>$zTemplateB64]);
    if(!$ok){ echo $tpl->_ENGINE_parse_body($data); return false; }


    return admin_tracks("Modify notification page of $aclname rule");
}
function notify_error_page_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["ENABLE_ERROR_PAGE"]==1){ $_POST["ENABLE"]=0; }

    $ID = intval($_POST["notify-error-page"]);
    if(proxyacls_meta_rule_id($ID)){echo proxyacls_meta_refuse_post();return;}
    $rule = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    if(!$rule){ echo $tpl->_ENGINE_parse_body("rule $ID not found"); return; }
    $aclname = $rule["aclname"];
    $zTemplate = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($rule);
    if(!is_array($zTemplate)){ $zTemplate = []; }
    foreach($_POST as $key=>$value){ $zTemplate[$key] = $value; }
    $zTemplateB64 = base64_encode(serialize($zTemplate));

    list($ok,$data) = acls_api_send("PUT","/proxy/acls/sqlite/rule/$ID/template",["zTemplateB64"=>$zTemplateB64]);
    if(!$ok){ echo $tpl->_ENGINE_parse_body($data); return; }
    admin_tracks("Modify redirect to url page of $aclname rule enable:{$_POST["ENABLE_ERROR_PAGE"]}");
}

