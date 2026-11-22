<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

//
$GLOBALS["POSTFIX_RULES"][1]="<strong>{ACCEPT}</strong><br>{POSTFIX_REGEX_ACCEPT}";
$GLOBALS["POSTFIX_RULES"][2]="<strong>{INFO}</strong><br>{POSTFIX_REGEX_INFO}";
$GLOBALS["POSTFIX_RULES"][3]="<strong>{DISCARD}</strong><br>{POSTFIX_REGEX_DISCARD}";
$GLOBALS["POSTFIX_RULES"][4]="<strong>{REJECT}</strong><br>{POSTFIX_REGEX_REJECT}";
$GLOBALS["POSTFIX_RULES"][5]="<strong>{BCC}</strong><br>{BCC_help}";
$GLOBALS["POSTFIX_RULES"][6]="<strong>{PREPEND}</strong><br>{POSTFIX_REGEX_PREPEND}";
$GLOBALS["POSTFIX_RULES"][7]="<strong>{backup_messages}</strong><br>{backup_messages_explain}";
$GLOBALS["POSTFIX_RULES"][8]="<strong>{REJECT} {and} {firewall_rules}<br>{reject_firewall_temp}";
$GLOBALS["POSTFIX_RULES"][10]="<strong>DKIM</strong><br>{dkim_about}";
$GLOBALS["POSTFIX_RULES"][11]="<strong>ARC</strong><br>{arc_about}";
$GLOBALS["POSTFIX_RULES"][12]="<strong>{greylisting}</strong><br>{greylisting_about}";
$GLOBALS["POSTFIX_RULES"][13]="<strong>DLP</strong><br>{dlp_about}";

// Couleur de fond du verdict. awc-badge impose color:#fff, seul le fond porte le
// sens: vert = laisse passer, sarcelle = annote/observe, ambre = retarde,
// rouge = bloque, prune = duplique (BCC/DLP).
$GLOBALS["POSTFIX_RULES_STATUS"][1]="#26a69a";
$GLOBALS["POSTFIX_RULES_STATUS"][2]="#006a62";
$GLOBALS["POSTFIX_RULES_STATUS"][3]="#ba1a1a";
$GLOBALS["POSTFIX_RULES_STATUS"][4]="#ba1a1a";
$GLOBALS["POSTFIX_RULES_STATUS"][5]="#7b3f8c";
$GLOBALS["POSTFIX_RULES_STATUS"][6]="#865300";
$GLOBALS["POSTFIX_RULES_STATUS"][7]="#006a62";
$GLOBALS["POSTFIX_RULES_STATUS"][8]="#ba1a1a";
$GLOBALS["POSTFIX_RULES_STATUS"][10]="#006a62";
$GLOBALS["POSTFIX_RULES_STATUS"][11]="#006a62";
$GLOBALS["POSTFIX_RULES_STATUS"][12]="#865300";
$GLOBALS["POSTFIX_RULES_STATUS"][13]="#7b3f8c";
$GLOBALS["POSTFIX_RULES_TITLE"][1]="{ACCEPT}";
$GLOBALS["POSTFIX_RULES_TITLE"][2]="{INFO}";
$GLOBALS["POSTFIX_RULES_TITLE"][3]="{DISCARD}";
$GLOBALS["POSTFIX_RULES_TITLE"][4]="{REJECT}";
$GLOBALS["POSTFIX_RULES_TITLE"][5]="{BCC}";
$GLOBALS["POSTFIX_RULES_TITLE"][6]="{PREPEND}";
$GLOBALS["POSTFIX_RULES_TITLE"][7]="{backup_messages}";
$GLOBALS["POSTFIX_RULES_TITLE"][8]="{REJECT}/{firewall}";
$GLOBALS["POSTFIX_RULES_TITLE"][10]="DKIM";
$GLOBALS["POSTFIX_RULES_TITLE"][11]="ARC";
$GLOBALS["POSTFIX_RULES_TITLE"][12]="{greylisting}";
$GLOBALS["POSTFIX_RULES_TITLE"][13]="DLP";



if(isset($_GET["rule-safesearch"])){rule_safesearch();exit;}
if(isset($_GET["rule-dkim_options"])){rule_dkim();exit;}
if(isset($_GET["rule-greylist_options"])){rule_greylist();exit;}
if(isset($_GET["replace-rule"])){replace_rule();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}

if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}


if(isset($_GET["ch-method-js"])){change_method_js();exit;}
if(isset($_GET["ch-method-popup"])){change_method_popup();exit;}
if(isset($_POST["ch-rule"])){change_method_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_POST["rule-delete"])){rule_delete_perform();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["default-js"])){default_js();exit;}
if(isset($_GET["default-popup"])){default_popup();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["view-rules"])){view_rules_js();exit;}
if(isset($_GET["view-rules-popup"])){view_rules_popup();exit;}
if(isset($_POST["dkimOptions"])){dkimOptions_save();exit;}
if(isset($_POST["greylistOptions"])){greylistOptions_save();exit;}
if(isset($_GET["reload"])){Reload();exit;}
page();

// articarest wraps every response in {"success":bool,"data":...}. Unwrap to the
// payload (object for a single rule, array for a list, {id} for create) so the
// flat accesses below keep working. Falls back to the raw decode for any legacy
// flat endpoint, so this is safe to apply uniformly.
function acl_json($resp){
    $j=json_decode((string)$resp);
    if(is_object($j)&&isset($j->success)&&isset($j->data)){return $j->data;}
    return $j;
}

function rule_dkim()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-dkim_options"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($json)||!isset($json->id)){echo $tpl->div_error("Failed to load rule $ID");return false;}
    $actionvalue=unserialize(base64_decode($json->actionValue));
    $dkimDomain=trim($actionvalue["dkimDomain"]);
    $dkimSelector=trim($actionvalue["dkimSelector"]);
    //$dkimPrivateKey=trim($actionvalue["dkimPrivateKey"]);
    $dkimCanonicalization=trim($actionvalue["dkimCanonicalization"]);

    $canonicalizationOptions["relaxed/relaxed"]="relaxed/relaxed";
    $canonicalizationOptions["simple/simple"]="simple/simple";
    $canonicalizationOptions["simple/relaxed"]="simple/relaxed";
    $canonicalizationOptions["relaxed/simple"]="relaxed/simple";


    if(strlen($dkimCanonicalization)==0){
        $dkimCanonicalization="relaxed/relaxed";
    }
    $dkminPKMessage="No private key detected, please apply the settings to create a new one automatically.";

    if(file_exists("/etc/dkimPKs/$dkimDomain/$dkimSelector.pub")){
        $dkminPKMessage=file_get_contents("/etc/dkimPKs/$dkimDomain/$dkimSelector.pub");
    }

    $form[]=$tpl->field_hidden("dkimOptions",$ID);
    $form[]=$tpl->field_text("dkimDomain","{domain}",$dkimDomain,true);
    $form[]=$tpl->field_text("dkimSelector","{selector}",$dkimSelector,true);

    $form[]=$tpl->field_array_hash_simple($canonicalizationOptions,"dkimCanonicalization","{canonicalization}",$dkimCanonicalization,true);
    $form[]=$tpl->field_textareaP("dkimPrivateKey","DNS {configuration}",$dkminPKMessage);
    $jsafter[]="BootstrapDialog1.close()";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="RefreshPostfixRules()";
    $jsafters=@implode(";",$jsafter);
    echo $tpl->form_outside(null, $form,null,"{apply}",$jsafters,"AsDnsAdministrator",true);
    return true;

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-id"]);

    $html=$tpl->page_header("{APP_POSTFIX}&nbsp;&raquo;&nbsp;{DNS_ACLS}",
        "fas fa-list","{artica_milter_explain}","$page?table-start=yes&instance-id=$instanceid","postfix-acls-$instanceid",
        "progress-postfixrules-restart",false,"table-acls-postfix-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function filltable(){
    $ACCESSEnabled=0;
    $tpl=new template_admin();
    $uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_websites}");
    $trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
    $ID=intval($_GET["filltable"]);
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($json)||!isset($json->id)){return;}
    $ligne=["crypt"=>0,"trust"=>0,"enabled"=>intval($json->enabled),"ID"=>$json->id];
    $crypt=$ligne["crypt"];
    $enabled=intval($ligne["enabled"]);
    if($crypt==1 OR ($ligne["trust"]==1)){$ACCESSEnabled=1;}
    $squid_acls_groups=new squid_acls_groups();
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"postfix_sqacllinks");
    $and_text=$tpl->javascript_parse_text(" {and} ");

    $TTEXT=array();
    $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");

    if(count($objects)>0) {

        $explain=$squid_acls_groups->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ACCESSEnabled,0,"postfix_sqacllinks")." {then} ".@implode($and_text, $TTEXT);

    }else{
        $explain="<div class=text-danger'>$please_specify_an_object</div>";
    }
    $img=$tpl->_ENGINE_parse_body(icon_status($crypt,$enabled,$objects));
    $explain=$tpl->_ENGINE_parse_body($explain);
    header("content-type: application/x-javascript");
    // Pentest 2026-11-04 : explication de la regle (noms d'objets) inseree brute dans une chaine JS (XSS stockee).
    // req_js() garde le fragment HTML intact pour innerHTML mais rien ne peut fermer la chaine.
    echo "document.getElementById('ssl-rule-icon-$ID').innerHTML=\"".req_js($img)."\";\n";
    echo "document.getElementById('ssl-rule-text-$ID').innerHTML=\"".req_js($explain)."\";\n";
}
function rule_delete_js():bool{

    $ID=intval($_GET["rule-delete-js"]);
    $md="acl-$ID";
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    $rulename=(is_object($json)&&isset($json->ruleName))?$json->ruleName:"#$ID";
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("{rule} $rulename","rule-delete",$ID,"$('#$md').remove();");
}
function rule_delete_perform():bool{
    header("content-type: application/x-javascript");
    $ID=intval($_POST["rule-delete"]);
    // Fetch name before deleting (for audit trail); DELETE endpoint cascades postfix_sqacllinks
    $jsonGet=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    $rulename=(is_object($jsonGet)&&isset($jsonGet->ruleName))?$jsonGet->ruleName:"#$ID";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_DELETE("/postfix/acls/$ID");
    return admin_tracks("Delete SMTP Artica Milter rule $rulename");
}
function rule_enable():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    $jsonGet=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($jsonGet)||!isset($jsonGet->id)){
        header("content-type: application/x-javascript");
        echo "alert('Failed to load rule $ID');";
        return false;
    }
    $enabled_src=intval($jsonGet->enabled);
    if($enabled_src==0){
        $js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
        $enabled=1;
    }else{
        $js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
        $enabled=0;
    }

    $putData=["enabled"=>$enabled];
    $jsonPut=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/acls/$ID",$putData));
    if(!is_object($jsonPut)){
        header("content-type: application/x-javascript");
        echo "alert('Failed to update rule $ID');";
        return false;
    }

    header("content-type: application/x-javascript");
    echo "// ID = $ID, src=$enabled_src, enabled =$enabled\n";
    echo $js."\n";
    echo "Loadjs('$page?filltable=$ID');\n";
    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
    return true;
}
function change_method_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ch-method-js"]);
    $function=$_GET["function"];
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    $rulename=(is_object($json)&&isset($json->ruleName))?$json->ruleName:"#$ID";
    $tpl->js_dialog2("{rule}: {change_method} $rulename","$page?ch-method-popup=$ID&function=$function");
    return true;
}
function change_method_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ch-method-popup"]);
    $function=$_GET["function"];
    $functionenc=urlencode($function);
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($json)||!isset($json->id)){echo $tpl->div_error("Failed to load rule $ID");return false;}
    $ruletype=intval($json->ruleType);

    fillRulesList_explain();
    $RULESSWITH=fillRules_canchange();
    unset($GLOBALS["POSTFIX_RULES"][$ruletype]);
    foreach ($GLOBALS["POSTFIX_RULES"] as $rtype=>$none){
        if(!isset($RULESSWITH[$rtype])){
            unset($GLOBALS["POSTFIX_RULES"][$rtype]);
        }
    }

    $form[]=$tpl->field_hidden("ch-rule", $ID);
    $form[]=$tpl->field_array_checkboxes2Columns($GLOBALS["POSTFIX_RULES"],"ruletype",1);

    $jsafter[]="BootstrapDialog1.close()";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="RefreshPostfixRules()";
    if(strlen($function)>3){
        $jsafter[]="$function()";
    }
    $jsafter[]="Loadjs('$page?rule-id-js=$ID&function=$functionenc')";
    $jsafters=@implode(";",$jsafter);

    $html=$tpl->form_outside("", $form,null,"{apply}",$jsafters,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function change_method_save():bool{
    $tpl=new template_admin();
    $ID=intval($_POST["ch-rule"]);
    $ruletype2=intval($_POST["ruletype"]);
    // Fetch current name/type for audit trail
    $jsonGet=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    $ruletype=(is_object($jsonGet)&&isset($jsonGet->ruleType))?intval($jsonGet->ruleType):0;
    $rulename=(is_object($jsonGet)&&isset($jsonGet->ruleName))?$jsonGet->ruleName:"#$ID";

    $putData=["ruleType"=>$ruletype2];
    $jsonPut=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/acls/$ID",$putData));
    if(!is_object($jsonPut)){
        $tpl->post_error("Failed to update rule $ID");
        return false;
    }
    admin_tracks("Change DNS Firewall $rulename rule method from type $ruletype to $ruletype2");
    return true;
}

function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $function=$_GET["function"];
    $ID=intval($_GET["rule-id-js"]);
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    $rulename=(is_object($json)&&isset($json->ruleName))?$json->ruleName:"#$ID";
    $tpl->js_dialog("{rule}: $ID $rulename","$page?rule-tabs=$ID&function=$function");
    return true;
}
function default_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{default}","$page?default-popup=yes");
    return true;
}
function rule_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $function=$_GET["function"];
    $ID=intval($_GET["rule-tabs"]);
    $RefreshFunction=base64_encode("RefreshPostfixRules()");
    $array["{rule}"]="$page?rule-settings=$ID&function=$function";
    $RefreshTable=base64_encode("LoadAjax('dnsdist-acl-table','$page?table=yes');");
    $array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=postfix_sqacllinks&RefreshTable=$RefreshTable&RefreshFunction=$RefreshFunction&function=$function";


    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    $ruletype=(is_object($json)&&isset($json->ruleType))?intval($json->ruleType):1;
    if($ruletype==2 OR $ruletype==3 OR $ruletype==4 OR $ruletype==9){unset($array["{cache}"]);}
    if($ruletype==9){
        $array["SafeSearch(s)"]="$page?rule-safesearch=$ID&function=$function";
    }
    if($ruletype==10 OR $ruletype==11){
        $array["{options}"]="$page?rule-dkim_options=$ID&function=$function";
    }
    if($ruletype==12){
        $array["{options}"]="$page?rule-greylist_options=$ID&function=$function";
    }
    if($ruletype==13){
        $array["{options}"]="fw.postfix.acl.rules.dlp.php?rule-dlp_options=$ID&function=$function";
    }
    echo $tpl->tabs_default($array);

}


function rule_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["rule-settings"]);
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($json)||!isset($json->id)){echo $tpl->div_error("Failed to load rule $ID");return false;}
    $ruletype=intval($json->ruleType);
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$json->enabled,true);

    $form[]=$tpl->field_text("rulename", "{rule_name}", $json->ruleName,true);
    if($ruletype==2){
        $form[]=$tpl->field_text("actionvalue", "{text_to_add_in_the_event}", $json->actionValue,true);
    }

    if($ruletype==6){
        $form[]=$tpl->field_text("actionvalue", "{PREPEND}", $json->actionValue,true);
    }
    if($ruletype==5){
        $form[]=$tpl->field_text("actionvalue", "{recipients}", $json->actionValue,true);
    }

    $rulevalue_explain=null;
    $RULESSWITH=fillRules_canchange();

    if(isset($RULESSWITH[$ruletype])){
        $tpl->form_add_button("{change_method}","Loadjs('$page?ch-method-js=$ID&function=$function');");
    }
    $jsafter=null;
    if($function<>null) {
        $jsafter = "$function();";
    }

    $html=$tpl->form_outside("{rule} ".$json->ruleName, $form,$GLOBALS["POSTFIX_RULES"][$ruletype],"{apply}",$jsafter,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function patch_table():bool{
    // Schema is now managed by the articarest Go daemon; this function is intentionally a no-op.
    return true;
}

function dkimOptions_save()
{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["dkimOptions"]);
    unset($_POST["dkimOptions"]);
    $dkimDomain=$_POST["dkimDomain"];
    $dkimSelector=$_POST["dkimSelector"];
    unset($_POST["dkimPrivateKey"]);

    $data=base64_encode(serialize($_POST));

    $putData=["actionValue"=>$data];
    $jsonPut=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/acls/$ID",$putData));
    if(!is_object($jsonPut)){echo "Failed to save DKIM options for rule $ID";return false;}

    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/createPK/$dkimDomain/$dkimSelector");
    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
    return true;
}

function rule_greylist()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-greylist_options"]);
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($json)||!isset($json->id)){echo $tpl->div_error("Failed to load rule $ID");return false;}

    $av=array();
    if(isset($json->actionValue)&&strlen($json->actionValue)>3){
        $tmp=@unserialize(base64_decode($json->actionValue));
        if(is_array($tmp)){$av=$tmp;}
    }
    // Defaults mirror greylisting.DefaultConfig() in the artica-milter daemon.
    $delay=isset($av["delay_seconds"])?intval($av["delay_seconds"]):300;
    $accepted=isset($av["accepted_ttl_seconds"])?intval($av["accepted_ttl_seconds"]):2592000;
    $pending=isset($av["pending_ttl_seconds"])?intval($av["pending_ttl_seconds"]):86400;
    $cachettl=isset($av["cache_ttl_seconds"])?intval($av["cache_ttl_seconds"]):900;
    $cleanup=isset($av["cleanup_interval_seconds"])?intval($av["cleanup_interval_seconds"]):3600;
    $authmode=isset($av["auth_mode"])?$av["auth_mode"]:"spf_or_dkim_with_dmarc";
    $rejectcode=isset($av["reject_code"])?$av["reject_code"]:"451";
    $rejecttext=isset($av["reject_text"])?$av["reject_text"]:"4.7.1 Greylisted, please retry later";
    $failmode=isset($av["fail_mode"])?$av["fail_mode"]:"cache_only";
    $store=isset($av["store_authenticated_allow"])?intval($av["store_authenticated_allow"]):1;
    $cidr=(isset($av["allowlist_cidr"])&&is_array($av["allowlist_cidr"]))?@implode("\n",$av["allowlist_cidr"]):"";
    $hosts=(isset($av["allowlist_hostnames"])&&is_array($av["allowlist_hostnames"]))?@implode("\n",$av["allowlist_hostnames"]):"";

    // auth_mode: only the behaviours that differ in v1 (SPF/DMARC, no inbound DKIM verify).
    $authOptions=array(
        "spf_or_dkim_with_dmarc"=>"{greylist_auth_dmarc}",
        "strict_all"=>"{greylist_auth_strict}",
        "none"=>"{greylist_auth_none}");
    $failOptions=array("cache_only"=>"cache_only","fail_open"=>"fail_open","fail_closed"=>"fail_closed");

    $form[]=$tpl->field_hidden("greylistOptions",$ID);
    $form[]=$tpl->field_text("delay_seconds","{greylist_delay}",$delay,true);
    $form[]=$tpl->field_array_hash_simple($authOptions,"auth_mode","{greylist_auth_mode}",$authmode,true);
    $form[]=$tpl->field_checkbox("store_authenticated_allow","{greylist_store_auth}",$store,true);
    $form[]=$tpl->field_array_hash_simple($failOptions,"fail_mode","{greylist_fail_mode}",$failmode,true);
    $form[]=$tpl->field_text("reject_code","{greylist_reject_code}",$rejectcode,true);
    $form[]=$tpl->field_text("reject_text","{greylist_reject_text}",$rejecttext,true);
    $form[]=$tpl->field_text("accepted_ttl_seconds","{greylist_accepted_ttl}",$accepted,true);
    $form[]=$tpl->field_text("pending_ttl_seconds","{greylist_pending_ttl}",$pending,true);
    $form[]=$tpl->field_text("cache_ttl_seconds","{greylist_cache_ttl}",$cachettl,true);
    $form[]=$tpl->field_text("cleanup_interval_seconds","{greylist_cleanup_interval}",$cleanup,true);
    $form[]=$tpl->field_textarea("allowlist_cidr","{greylist_allowlist_cidr}",$cidr,"100%","100px");
    $form[]=$tpl->field_textarea("allowlist_hostnames","{greylist_allowlist_hostnames}",$hosts,"100%","100px");

    $jsafter[]="BootstrapDialog1.close()";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="RefreshPostfixRules()";
    $jsafters=@implode(";",$jsafter);
    echo $tpl->form_outside("{greylisting}",$form,"{greylist_options_explain}","{apply}",$jsafters,"AsPostfixAdministrator",true);
    return true;
}

function greylistOptions_save()
{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["greylistOptions"]);
    unset($_POST["greylistOptions"]);

    // Checkbox: ensure an explicit 0/1 is always serialized.
    $_POST["store_authenticated_allow"]=(isset($_POST["store_authenticated_allow"])&&$_POST["store_authenticated_allow"])?"1":"0";

    // Allowlists are entered one entry per line; serialize them as PHP arrays so the
    // Go side (greylisting.ParseConfig -> toStringSlice) reads them as string slices.
    foreach(array("allowlist_cidr","allowlist_hostnames") as $k){
        $lines=preg_split('/\r\n|\r|\n/',(string)(isset($_POST[$k])?$_POST[$k]:""));
        $arr=array();
        foreach($lines as $l){$l=trim($l);if($l!=""){$arr[]=$l;}}
        $_POST[$k]=$arr;
    }

    // Field names are the actionValue map keys the daemon's getGreylistOptions reads.
    $data=base64_encode(serialize($_POST));
    $putData=array("actionValue"=>$data);
    $jsonPut=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/acls/$ID",$putData));
    if(!is_object($jsonPut)){echo "Failed to save greylisting options for rule $ID";return false;}

    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
    return true;
}

function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    unset($_POST["ID"]);

    // Map snake_case POST keys to camelCase API fields
    $fieldMap=["rulename"=>"ruleName","actionvalue"=>"actionValue","instanceid"=>"instanceID","ruletype"=>"ruleType","enabled"=>"enabled"];
    $putData=[];
    foreach($_POST as $key=>$val){
        $apiKey=isset($fieldMap[$key])?$fieldMap[$key]:$key;
        $putData[$apiKey]=$val;
    }
    // articarest expects integers for these fields (json: cannot unmarshal string into int)
    foreach(["enabled","instanceID","ruleType"] as $ik){ if(isset($putData[$ik])){ $putData[$ik]=intval($putData[$ik]); } }

    $jsonPut=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/acls/$ID",$putData));
    if(!is_object($jsonPut)){echo "Failed to save rule $ID";return false;}
    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
    return true;
}
function view_rules_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{view_rules}";
    $tpl->js_dialog($title,"$page?view-rules-popup=yes");
}
function view_rules_popup(){
    $tpl=new template_admin();

    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/rules"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $data=explode("\n",$json->Info);
    $sf[]="ID\tName\tUUID\tCr.\tOrder\tMatches\tRule\tAction";
    foreach ($data as $line){

        if(!preg_match("#^([0-9]+)#",$line,$re)){continue;}
        $sf[]=$line;
    }

    $html[]="<textarea style='width: 847px; height: 400px;'>";
    $html[]=@implode("\n",$sf);
    $html[]="</textarea>";
    
    $html[]="
<script> 
    NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
</script>";
    echo $tpl->_ENGINE_parse_body($html);
}



function new_rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{new_rule}";
    $instanceid=intval($_GET["instance-id"]);
    return $tpl->js_dialog($title,"$page?newrule-popup=yes&instance-id=$instanceid");

}




function new_rule_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-id"]);
    fillRulesList_explain();

    $form[]=$tpl->field_hidden("newrule", "yes");
    $form[]=$tpl->field_hidden("enabled","1");
    $form[]=$tpl->field_hidden("instanceid",$instanceid);
    $form[]=$tpl->field_array_checkboxes2Columns($GLOBALS["POSTFIX_RULES"],"ruletype",1,false,null);

    $form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
    $jsafter="RefreshPostfixRules();BootstrapDialog1.close();";
    $html=$tpl->form_outside("{new_rule}", @implode("\n", $form),null,"{add}",$jsafter,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function fillRulesList_explain():bool{
    $GLOBALS["POSTFIX_RULES"][1]="<strong>{ACCEPT}</strong><br>{POSTFIX_REGEX_ACCEPT}";
    $GLOBALS["POSTFIX_RULES"][2]="<strong>{INFO}</strong><br>{POSTFIX_REGEX_INFO}";
    $GLOBALS["POSTFIX_RULES"][3]="<strong>{DISCARD}</strong><br>{POSTFIX_REGEX_DISCARD}";
    $GLOBALS["POSTFIX_RULES"][4]="<strong>{REJECT}</strong><br>{POSTFIX_REGEX_REJECT}";
    $GLOBALS["POSTFIX_RULES"][5]="<strong>{BCC}</strong><br>{BCC_help}";
    $GLOBALS["POSTFIX_RULES"][6]="<strong>{PREPEND}</strong><br>{POSTFIX_REGEX_PREPEND}";
    $GLOBALS["POSTFIX_RULES"][12]="<strong>{greylisting}</strong><br>{greylisting_about}";
    $GLOBALS["POSTFIX_RULES"][13]="<strong>DLP</strong><br>{dlp_about}";
    return true;
}
function fillRules_canchange():array{
    $RULESSWITH[2]=true;
    $RULESSWITH[3]=true;
    $RULESSWITH[4]=true;
    return $RULESSWITH;
}



function rule_move(){
    $tpl=new template_admin();
    $ID=intval($_GET["acl-rule-move"]);
    $dir=intval($_GET["acl-rule-dir"]);  // 1=up (lower order), 0=down (higher order)
    if($GLOBALS["VERBOSE"]){echo "$ID, dir=$dir;\n";}

    $putData=["dir"=>$dir];
    $jsonPut=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/acls/$ID/move",$putData));
    if(!is_object($jsonPut)){
        echo "alert('".$tpl->javascript_parse_text("Failed to move rule $ID")."');";
        return;
    }
    if($GLOBALS["VERBOSE"]){echo "PUT /postfix/acls/$ID/move dir=$dir\n";}

    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
}

function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-id"]);
    patch_table();
    echo $tpl->search_block($page,null,null,null,"&table=yes&instance-id=$instanceid");
    return true;
}

function replace_rule(){
    $tpl=new template_admin();
    $ID=$_GET["replace-rule"];
    $page=CurrentPageName();
    $squid_acls_groups=new squid_acls_groups();
    $GLOBALS["ACL_OBJECTS_JS_AFTER"]=base64_encode("LoadAjax('dnsdist-rule-text-$ID','$page?replace-rule=$ID');");
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"postfix_sqacllinks");

    if(count($objects)==0) {
        echo $tpl->_ENGINE_parse_body("<strong class=\"text-danger\">{please_specify_an_object}</strong>");
        return;
    }

    $explain="&nbsp;{for_objects} ". @implode(" {and} ", $objects);
    $explain=$explain.EXPLAIN_THIS_RULE($ID);
    $tpl->_ENGINE_parse_body($explain);
}

function reload():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->MILTER_API("/reload");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->js_error("{error}");
        return true;
    }


    $tpl->js_executed_background("{reloading_service}");
    return true;
}
function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-id"]);
    // Sanitize to a JS identifier: $function is interpolated into an inline <script>
    // (RefreshPostfixRules) and a hidden input — raw $_GET would be a reflected XSS.
    $function=preg_replace('/[^a-zA-Z0-9_]/','',(string)($_GET["function"]??''));
    $html[]="<input type='hidden' id='proxy-acls-bugs-function' value='$function'>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{rulename}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th class='center'>{requests}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pfxacl-rows'>";

    $array_help["TITLE"]="{inactive2}";
    $array_help["content"]="{inactive_acl_why}";
    $array_help["ico"]="fa fa-question";
    $scontent=base64_encode(serialize($array_help));

    $status_inactive=$tpl->td_href("<span class='label'>{inactive2}</span>","{explain}","LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')");

    $jsAfter="LoadAjax('table-firewall-rules','$page?table=yes&instance-id=$instanceid');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $squid_acls_groups=new squid_acls_groups();
    $apiUrl="/postfix/acls?instanceID=$instanceid";
    if(isset($_GET["search"])&&trim($_GET["search"])<>null){
        $apiUrl.="&search=".urlencode(trim($_GET["search"]));
    }
    $apiJson=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API($apiUrl));
    $results=(is_array($apiJson))?$apiJson:[];
    $TRCLASS=null;

    $tdmid="style='vertical-align:middle;width:1%'";
    $qPost=new postgres_sql();
    foreach($results as $index=>$ligne) {
        $TRCLASS=null;
        $ACCESSEnabled=1;
        $ruletype=intval($ligne->ruleType);
        $enabled=intval($ligne->enabled);
        $ID=intval($ligne->id);
        $rulename=$tpl->utf8_encode($ligne->ruleName);
        $class="text-primary";
        $CountRequest=true;

        // matchedrules makes per-rule counts accurate for continue-action rules
        // (INFO/BCC/PREPEND/BACKUP), so they are now counted like every other type.
        // ruletype 10 rows are DKIM signing config, not ACL actions — no events.
        // ruletype 11 rows are ARC sealing config, same — no events.
        // ruletype 12 rows are greylisting config, same — no events.
        if ($ruletype == 10 OR $ruletype == 11 OR $ruletype == 12){
            $CountRequest=false;
        }


        $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");
        $label_status=isset($GLOBALS["POSTFIX_RULES_STATUS"][$ruletype])?$GLOBALS["POSTFIX_RULES_STATUS"][$ruletype]:"#263238";
        $label_title=isset($GLOBALS["POSTFIX_RULES_TITLE"][$ruletype])?$GLOBALS["POSTFIX_RULES_TITLE"][$ruletype]:"#$ruletype";
        $status="<span class='awc-badge' style='background:$label_status'>$label_title</span>";

        $TTEXT=array();
        $TTEXT[]=$GLOBALS["POSTFIX_RULES"][$ruletype];

        $GLOBALS["ACL_OBJECTS_JS_AFTER"]=base64_encode("LoadAjax('dnsdist-rule-text-$ID','$page?replace-rule=$ID');");
        $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"postfix_sqacllinks");

        if(count($objects)>0){
            $explain="&nbsp;{for_objects} ". @implode(" {and} ", $objects);
        }else{
            $class = "text-danger";
            $ACCESSEnabled = 0;
            $explain = "<strong class=\"text-danger\">$please_specify_an_object</strong>";
        }
        if($ACCESSEnabled==1){$explain=$explain.EXPLAIN_THIS_RULE($ID);}

        $delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
        $js="Loadjs('$page?rule-id-js=$ID&function=$function')";

        $up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
        $rulename=$tpl->utf8_decode($rulename);
        $check=$tpl->icon_check($enabled,"Loadjs('$page?enable-js=$ID')");
        $rulerow=$tpl->td_href($rulename,"{click_to_edit}",$js);
        $logs=$tpl->icon_nothing();
        $requests=$tpl->icon_nothing();
        VERBOSE("Rule $ID CountRequest = $CountRequest",__LINE__);
        if($CountRequest){
            $qligne=$qPost->mysqli_fetch_array("SELECT COUNT(*) AS tcount FROM articamilter WHERE ruleid=$ID OR '$ID' = ANY(string_to_array(matchedrules, ','))");
            if(!$qPost->ok){
                VERBOSE("ERROR: $qPost->mysql_error",__LINE__);
            }
            $requests=$tpl->FormatNumber($qligne["tcount"]);
            $logs=$tpl->icon_loupe(1,"Loadjs('fw.postfix.acl.accesses.php?popjs=yes&instance-id=$instanceid&ruleid=$ID')");
        }
        if($enabled==0){
            $class="text-muted";
        }
        $html[]=pfxacl_row($ID,$rulerow,$explain,$status,$requests,$enabled,$logs.$check.$up.$down.$delete);
    }

    $requests=$tpl->icon_nothing();
    $logs=$tpl->icon_nothing();
    $status="<span class='awc-badge awc-badge-on'>{FORWARD}</span>";
    $qligne=$qPost->mysqli_fetch_array("SELECT COUNT(*) AS tcount FROM articamilter WHERE ruleid=0");
    if(!$qPost->ok){
        VERBOSE("ERROR: $qPost->mysql_error",__LINE__);
    }

    if($qligne["tcount"]>0) {
        $requests = $tpl->FormatNumber($qligne["tcount"]);
        $logs = $tpl->icon_loupe(1, "Loadjs('fw.postfix.acl.accesses.php?popjs=yes&instance-id=$instanceid&ruleid=0')");
    }
    $rulerow="{default}";
    $class="";
    $TRCLASS=null;
    // La regle implicite de fin de chaine: elle n'est pas dans la base, elle est
    // cablee dans la configuration generee, d'ou l'absence d'action.
    $html[]=pfxacl_row(0,"{default}",
        "{finally_allow_all} {and} {forward_messages_to} {APP_POSTFIX}",
        $status,$requests,1,$logs);

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span class='awc-pg' id='pfxacl-pager'></span>";
    $html[]="</div></div></div>";


    $add="Loadjs('$page?newrule-js=yes&function=$function&instance-id=$instanceid',true);";
    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('fw.postfix.acl.rules.dlp.php?dlp-templates-js=yes&function=$function&instance-id=$instanceid')", ico_configure, "{dlp_templates}");
    $topbuttons[] = array("Loadjs('$page?reload=yes');", ico_refresh, "{reload_service}");

    $TINY_ARRAY["TITLE"]="{APP_POSTFIX}&nbsp;&raquo;&nbsp;{ACLS}";
    $TINY_ARRAY["ICO"]="fas fa-list";
    $TINY_ARRAY["EXPL"]="{artica_milter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
<script> 
    function RefreshPostfixRules(){ {$function}(); }
    $jstiny
    NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(50,"pfxacl-rows","pfxacl-pager");
    return true;
}

/**
 * Une regle ACL: nom en identite, portee et explication en sous-titre (la colonne
 * "description" occupait 99% de la largeur et ecrasait tout le reste), type de
 * verdict, nombre de messages vus, actions.
 * L'ordre des colonnes suit celui des autres tableaux d'entites: l'identite
 * d'abord, les mesures ensuite, les actions a droite.
 */
function pfxacl_row($ID,$rulename,$explain,$status,$requests,$enabled,$actions):string{
    $ID=intval($ID);
    $h ="<tr id='acl-$ID'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($enabled?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-list'></i></span>";
    $h.="<span><span class='".($enabled?"awc-name":"awc-name awc-name-off")."'>$rulename</span>";
    if(trim((string)$explain)!==""){$h.="<div class='awc-meta'>$explain</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center'>$status</td>";
    $h.="<td class='center awc-mono'>$requests</td>";
    $h.="<td><div class='awc-acts'>$actions</div></td>";
    $h.="</tr>";
    return $h;
}


function EXPLAIN_THIS_RULE($ID){

    $ID=intval($ID);
    $json=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/acls/$ID"));
    if(!is_object($json)||!isset($json->id)){return "Failed to load rule $ID";}
    $ruletype=intval($json->ruleType);
    $actionvalue=$json->actionValue??"";
    $jsafter=base64_encode("RefreshPostfixRules()");

    if($ruletype==6){
        $ico=ico_arrow_right;
        $actionvalue=" <i class='$ico'></i>&nbsp;&laquo;$actionvalue&raquo;";
    }
    if($ruletype==5){
        $ico=ico_message;
        $actionvalue=" <i class='$ico'></i>&nbsp;&laquo;$actionvalue&raquo;";
    }
    if($ruletype==2){
        if(strlen($actionvalue)>3) {
            $ico=ico_eye;
            $actionvalue = " <i class='$ico'></i>&nbsp;{with_the_value} &laquo;$actionvalue&raquo;";
        }
    }
    if($ruletype==10){
        if(strlen($actionvalue)>3) {
            $ico=ico_certificate;
            $actionvalue = unserialize(base64_decode($actionvalue));
            //print_r($actionvalue);
            $txt="Domain={$actionvalue["dkimDomain"]} Selector={$actionvalue["dkimSelector"]} Canonicalization={$actionvalue["dkimCanonicalization"]}";
            $actionvalue = " <i class='$ico'></i>&nbsp;sign (dkim) outbound emails&nbsp;{with_the_value} &laquo;$txt&raquo;";
        }
    }
    if($ruletype==11){
        if(strlen($actionvalue)>3) {
            $ico=ico_certificate;
            $actionvalue = unserialize(base64_decode($actionvalue));
            $txt="Domain={$actionvalue["dkimDomain"]} Selector={$actionvalue["dkimSelector"]}";
            $actionvalue = " <i class='$ico'></i>&nbsp;seal (arc) emails&nbsp;{with_the_value} &laquo;$txt&raquo;";
        }
    }
    if($ruletype==12){
        if(strlen($actionvalue)>3) {
            $ico=ico_clock;
            $av = @unserialize(base64_decode($actionvalue));
            if(is_array($av)){
                $gdelay=isset($av["delay_seconds"])?$av["delay_seconds"]:"300";
                $gauth=isset($av["auth_mode"])?$av["auth_mode"]:"spf_or_dkim_with_dmarc";
                $txt="delay={$gdelay}s auth_bypass=$gauth";
                $actionvalue = " <i class='$ico'></i>&nbsp;greylist unknown senders&nbsp;{with_the_value} &laquo;$txt&raquo;";
            }
        }
    }


    $name="&nbsp;{then} <strong>{$GLOBALS["POSTFIX_RULES_TITLE"][$ruletype]} $actionvalue</strong>";
   return $name;
}

function new_rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    unset($_POST["newrule"]);

    // Map snake_case POST keys to camelCase API fields
    $fieldMap=["rulename"=>"ruleName","actionvalue"=>"actionValue","instanceid"=>"instanceID","ruletype"=>"ruleType","enabled"=>"enabled"];
    $postData=[];
    foreach($_POST as $key=>$val){
        $apiKey=isset($fieldMap[$key])?$fieldMap[$key]:$key;
        $postData[$apiKey]=$val;
    }
    // articarest expects integers for these fields (json: cannot unmarshal string into int)
    foreach(["instanceID","ruleType","enabled"] as $ik){ if(isset($postData[$ik])){ $postData[$ik]=intval($postData[$ik]); } }

    $jsonPost=acl_json($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/acls",$postData));
    if(!is_object($jsonPost)||!isset($jsonPost->id)){
        echo "Failed to create rule".(is_object($jsonPost)&&isset($jsonPost->error)?": ".$jsonPost->error:"");
        return false;
    }
    return admin_tracks_post("Create a new SMTP Firewall rule");
}



function default_rule(){
    $tpl=new template_admin();
    $DNSDistCheckName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckName"));
    $DNSDistCheckInterval=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistMaxCheckFailures=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
    $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));
    if(intval($DNSDistCheckTimeout)==0){$DNSDistCheckTimeout=1;}
    if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
    if(intval($DNSDistCheckInterval)==0){$DNSDistCheckInterval=1;}
    if(intval($DNSDistMaxCheckFailures)==0){$DNSDistMaxCheckFailures=3;}

    if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}
    if($DNSDistCheckInterval<2){$DNSDistCheckInterval=2;}
    $UnBoundCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheSize"));
    $UnBoundCacheMinTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
    $UnBoundCacheNEGTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheNEGTTL"));
    $EnableUnboundLogQueries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundLogQueries"));


    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}
    if($UnBoundCacheNEGTTL==0){$UnBoundCacheNEGTTL=30;}

    if($UnBoundCacheMinTTL==-1){$UnBoundCacheMinTTL=0;}
    if($UnBoundCacheMAXTTL==-1){$UnBoundCacheMAXTTL=0;}
    if($UnBoundCacheNEGTTL==-1){$UnBoundCacheNEGTTL=0;}

    if($UnBoundCacheSize==0){$UnBoundCacheSize=100;}

    $DnsDistCacheItem=$UnBoundCacheSize*1024;
    $DnsDistCacheItem=$DnsDistCacheItem*1024;
    $DnsDistCacheItem=round($DnsDistCacheItem/512);

    $DnsDistCacheItem=$tpl->FormatNumber($DnsDistCacheItem);
    $cacheexpl="<br>{and} <strong>{use_cache}</strong> {with} {$DnsDistCacheItem} {max_records_in_memory}";
    $UnBoundCacheMinTTLText=distanceOfTimeInWords(time(),time()+$UnBoundCacheMinTTL,true);
    $UnBoundCacheMAXTTLText=distanceOfTimeInWords(time(),time()+$UnBoundCacheMAXTTL,true);
    $UnBoundCacheNEGTTLText=distanceOfTimeInWords(time(),time()+$UnBoundCacheNEGTTL,true);
    $cacheexpl="$cacheexpl {cache-ttl} {minimum} $UnBoundCacheMinTTLText {and} <strong>$UnBoundCacheMAXTTLText {maximum}</strong> {and} {negquery-cache-ttl} $UnBoundCacheNEGTTLText";


    $DNS=array();
    $default_rule_src=default_rule_src();
    $resolv=new resolv_conf();
    if($resolv->MainArray["DNS1"]<>null){
        if($resolv->MainArray["DNS1"]<>"127.0.0.1") {
            $DNS[] = $resolv->MainArray["DNS1"];
        }
    }
    if($resolv->MainArray["DNS2"]<>null){
        if($resolv->MainArray["DNS1"]<>"127.0.0.1") {
            $DNS[] = $resolv->MainArray["DNS2"];
        }
    }
    if($resolv->MainArray["DNS3"]<>null){
        if($resolv->MainArray["DNS1"]<>"127.0.0.1") {
            $DNS[] = $resolv->MainArray["DNS3"];
        }
    }

    if(count($DNS)==0){return null;}

    return "{for} {ipaddresses} <strong>$default_rule_src</strong> {and} <strong>{all_domains}</strong> {then} <strong>{load_balance_dns_action}</strong> {to_addresses} <strong>" .@implode(" {or} ",$DNS)."</strong>".
        "$cacheexpl<br>{check_addr} <strong>$DNSDistCheckName</strong> {timeout} $DNSDistCheckTimeout {seconds} {each} $DNSDistCheckInterval {seconds}  $DNSDistMaxCheckFailures {attempts}";


}


function default_rule_src(){


    $q = new lib_sqlite("/home/artica/SQLITE/dns.db");
    $Rules=$q->COUNT_ROWS("pdns_restricts");

    if($Rules==0){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";

    }else {
        $sql = "SELECT *  FROM pdns_restricts";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index => $ligne) {
            $address=trim($ligne["address"]);
            if($address==null){continue;}
            $ACLREST[] = $address;
        }
    }

    return @implode(" {or} ",$ACLREST);


}

