<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
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
$GLOBALS["POSTFIX_RULES"][10]="<strong>DKIM<br>{dkim_about}";

$GLOBALS["POSTFIX_RULES_STATUS"][1]="label-primary";
$GLOBALS["POSTFIX_RULES_STATUS"][2]="label-info";
$GLOBALS["POSTFIX_RULES_STATUS"][3]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][4]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][5]="label-pink";
$GLOBALS["POSTFIX_RULES_STATUS"][6]="label-warning";
$GLOBALS["POSTFIX_RULES_STATUS"][7]="label-info";
$GLOBALS["POSTFIX_RULES_STATUS"][8]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][10]="label-info";
$GLOBALS["POSTFIX_RULES_TITLE"][1]="{ACCEPT}";
$GLOBALS["POSTFIX_RULES_TITLE"][2]="{INFO}";
$GLOBALS["POSTFIX_RULES_TITLE"][3]="{DISCARD}";
$GLOBALS["POSTFIX_RULES_TITLE"][4]="{REJECT}";
$GLOBALS["POSTFIX_RULES_TITLE"][5]="{BCC}";
$GLOBALS["POSTFIX_RULES_TITLE"][6]="{PREPEND}";
$GLOBALS["POSTFIX_RULES_TITLE"][7]="{backup_messages}";
$GLOBALS["POSTFIX_RULES_TITLE"][8]="{REJECT}/{firewall}";
$GLOBALS["POSTFIX_RULES_TITLE"][10]="DKIM";



if(isset($_GET["rule-safesearch"])){rule_safesearch();exit;}
if(isset($_GET["rule-dkim_options"])){rule_dkim();exit;}
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
if(isset($_POST["ProxyDefaultUncryptSSL"])){ProxyDefaultUncryptSSL_save();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["rule-cache"])){rule_cache();exit;}
if(isset($_GET["view-rules"])){view_rules_js();exit;}
if(isset($_GET["view-rules-popup"])){view_rules_popup();exit;}
if(isset($_POST["dkimOptions"])){dkimOptions_save();exit;}
if(isset($_GET["reload"])){Reload();exit;}
page();

function rule_dkim()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-dkim_options"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `actionvalue` FROM postfix_rules WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $actionvalue=unserialize(base64_decode($ligne["actionvalue"]));
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
    $ID=$_GET["filltable"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM postfix_rules WHERE ID='$ID'");
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
    echo "document.getElementById('ssl-rule-icon-$ID').innerHTML=\"$img\";\n";
    echo "document.getElementById('ssl-rule-text-$ID').innerHTML=\"$explain\";\n";
}
function rule_delete_js():bool{

    $ID=intval($_GET["rule-delete-js"]);
    $md="acl-$ID";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM postfix_rules WHERE ID='$ID'");
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("{rule} {$ligne["rulename"]}","rule-delete",$ID,"$('#$md').remove();");
}
function rule_delete_perform():bool{
    header("content-type: application/x-javascript");
    $ID=intval($_POST["rule-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM postfix_rules WHERE ID='$ID'");
    $rulename=$ligne["rulename"];
    $q->QUERY_SQL("DELETE FROM postfix_rules WHERE ID='$ID'");
    $q->QUERY_SQL("DELETE FROM postfix_sqacllinks WHERE aclid='$ID'");
    return admin_tracks("Delete SMTP Artica Milter rule $rulename");
    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
}
function rule_enable():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM postfix_rules WHERE ID='$ID'");
    $enabled_src=intval($ligne["enabled"]);
    if($enabled_src==0){
        $js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
        $enabled=1;
    }else{
        $js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
        $enabled=0;
    }

    $q->QUERY_SQL("UPDATE postfix_rules SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");


    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}

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
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `rulename` FROM postfix_rules WHERE ID='$ID'");
    $tpl->js_dialog2("{rule}: {change_method} {$ligne["rulename"]}","$page?ch-method-popup=$ID&function=$function");
    return true;
}
function change_method_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ch-method-popup"]);
    $function=$_GET["function"];
    $functionenc=urlencode($function);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `rulename`,ruletype FROM postfix_rules WHERE ID='$ID'");
    $ruletype=intval($ligne["ruletype"]);

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
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `rulename`,ruletype FROM postfix_rules WHERE ID='$ID'");
    $ruletype=intval($ligne["ruletype"]);
    $rulename=$ligne["rulename"];

    $q->QUERY_SQL("UPDATE postfix_rules SET ruletype=$ruletype2 WHERE ID=$ID");
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
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
    $ID=$_GET["rule-id-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `rulename` FROM postfix_rules WHERE ID='$ID'");
    $tpl->js_dialog("{rule}: $ID {$ligne["rulename"]}","$page?rule-tabs=$ID&function=$function");
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


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `ruletype` FROM postfix_rules WHERE ID='$ID'");
    $ruletype=$ligne["ruletype"];
    if($ruletype==2 OR $ruletype==3 OR $ruletype==4 OR $ruletype==9){unset($array["{cache}"]);}
    if($ruletype==9){
        $array["SafeSearch(s)"]="$page?rule-safesearch=$ID&function=$function";
    }
    if($ruletype==10){
        $array["{options}"]="$page?rule-dkim_options=$ID&function=$function";
    }
    echo $tpl->tabs_default($array);

}


function rule_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=$_GET["rule-settings"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM postfix_rules WHERE ID='$ID'");
    $form[]=$tpl->field_hidden("ID", $ID);
    $ruletype=intval($ligne["ruletype"]);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);

    $form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
    if($ruletype==2){
        $form[]=$tpl->field_text("actionvalue", "{text_to_add_in_the_event}", $ligne["actionvalue"],true);
    }

    if($ruletype==6){
        $form[]=$tpl->field_text("actionvalue", "{PREPEND}", $ligne["actionvalue"],true);
    }
    if($ruletype==5){
        $form[]=$tpl->field_text("actionvalue", "{recipients}", $ligne["actionvalue"],true);
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

    $html=$tpl->form_outside("{rule} {$ligne["rulename"]}", $form,$GLOBALS["POSTFIX_RULES"][$ruletype],"{apply}",$jsafter,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function patch_table():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="CREATE TABLE IF NOT EXISTS `postfix_rules` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`rulename` TEXT,enabled INTEGER DEFAULT 1,actionvalue TEXT NOT NULL DEFAULT '',instanceid INTEGER NOT NULL DEFAULT 0 ,ruletype INTEGER DEFAULT 1,zOrder INTEGER DEFAULT 1)";
    $q->QUERY_SQL($sql);


    if(!$q->FIELD_EXISTS("postfix_rules","instanceid")){
        $q->QUERY_SQL("ALTER TABLE postfix_rules ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("postfix_rules","actionvalue")){
        $q->QUERY_SQL("ALTER TABLE postfix_rules ADD actionvalue TEXT NOT NULL DEFAULT ''");
    }


    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `postfix_sqacllinks` (
			`zmd5` TEXT PRIMARY KEY ,
			`aclid` INTEGER,
			`negation` INTEGER NOT NULL DEFAULT 0,
			`direction` INTEGER,
			`gpid` INTEGER,
			`zOrder` INT NOT NULL DEFAULT 1)");



    return true;

}

function dkimOptions_save()
{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["dkimOptions"];
    unset($_POST["dkimOptions"]);
    unset($_POST["dkimPrivateKey"]);
    $edit_fields=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $data=base64_encode(serialize($_POST));

    $sql="UPDATE postfix_rules SET actionvalue='$data' WHERE ID='$ID'";
    patch_table();
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
    //$GLOBALS["CLASS_SOCKETS"]->DKIM_GEN_KEY($_POST["dkimDomain"],$_POST["dkimSelector"]);
    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/createPK/{$_POST["dkimDomain"]}/{$_POST["dkimSelector"]}");

    $GLOBALS["CLASS_SOCKETS"]->MILTER_API("/reload");
    return true;
}

function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["ID"];
    unset($_POST["ID"]);
    $edit_fields=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    foreach ($_POST as $key=>$val){
        $add_fields[]="`$key`";
        $add_values[]="'$val'";
        $edit_fields[]="`$key`='$val'";
    }
    $sql="UPDATE postfix_rules SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
    patch_table();
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
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

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/rules"));

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
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";
    echo $tpl->_ENGINE_parse_body($html);
}



function new_rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{new_rule}";
    return $tpl->js_dialog($title,"$page?newrule-popup=yes");

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
    $ID=$_GET["acl-rule-move"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT zOrder FROM postfix_rules WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
    $xORDER_ORG=intval($ligne["zOrder"]);
    $xORDER=$xORDER_ORG;


    if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
    if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
    if($xORDER<0){$xORDER=0;}
    $sql="UPDATE postfix_rules SET zOrder=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    if($GLOBALS["VERBOSE"]){echo "$sql\n";}

    if($_GET["acl-rule-dir"]==1){
        $xORDER2=$xORDER+1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE postfix_rules SET zOrder=$xORDER2 WHERE `ID`<>'$ID' AND zOrder=$xORDER";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}

        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }
    if($_GET["acl-rule-dir"]==0){
        $xORDER2=$xORDER-1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE postfix_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}' AND zOrder=$xORDER";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
    }

    $c=0;
    $sql="SELECT ID FROM postfix_rules ORDER BY zOrder";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE postfix_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "UPDATE postfix_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }

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
    $instanceid=intval($_GET["instanceid"]);
    $function=$_GET["function"];
    $html[]="<input type='hidden' id='proxy-acls-bugs-function' value='$function'>";
    $html[]="<table id='table-ssl-proxy-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{requests}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{events}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $array_help["TITLE"]="{inactive2}";
    $array_help["content"]="{inactive_acl_why}";
    $array_help["ico"]="fa fa-question";
    $scontent=base64_encode(serialize($array_help));

    $status_inactive=$tpl->td_href("<span class='label'>{inactive2}</span>","{explain}","LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')");

    $jsAfter="LoadAjax('table-firewall-rules','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $squid_acls_groups=new squid_acls_groups();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    $sql="SELECT * FROM postfix_rules WHERE instanceid=$instanceid ORDER BY zOrder";
    if(isset($_GET["search"])){
        $search=$_GET["search"];
        if($search<>null){
            $search="*$search*";
            $search=str_replace("**","*",$search);
            $search=str_replace("*","%",$search);
            $sql="SELECT * FROM postfix_rules WHERE WHERE instanceid=$instanceid and rulename LIKE '$search' ORDER BY zOrder";

        }
    }

    $results=$q->QUERY_SQL($sql);
    $TRCLASS=null;

    $tdmid="style='vertical-align:middle;width:1%'";
    $qPost=new postgres_sql();
    foreach($results as $index=>$ligne) {
        if($TRCLASS=="footable-odd "){$TRCLASS=null;}else{$TRCLASS="footable-odd ";}
        $ACCESSEnabled=1;
        $ruletype=$ligne["ruletype"];
        $enabled=intval($ligne["enabled"]);
        $ID=$ligne["ID"];
        $rulename=$tpl->utf8_encode($ligne["rulename"]);
        $class="text-primary";
        $CountRequest=true;

        if ($ruletype == 2 OR $ruletype == 5 OR $ruletype == 6 ){
            $CountRequest=false;
        }


        $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");
        $label_status=$GLOBALS["POSTFIX_RULES_STATUS"][$ruletype];
        $label_title=$GLOBALS["POSTFIX_RULES_TITLE"][$ruletype];
        $status="<span class='label $label_status'>$label_title</span>";

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
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')");
        $rulerow=$tpl->td_href($rulename,"{click_to_edit}",$js);
        $logs=$tpl->icon_nothing();
        $requests=$tpl->icon_nothing();
        VERBOSE("Rule $ID CountRequest = $CountRequest",__LINE__);
        if($CountRequest){
            $qligne=$qPost->mysqli_fetch_array("SELECT COUNT(*) AS tcount FROM articamilter WHERE ruleid=$ID");
            if(!$qPost->ok){
                VERBOSE("ERROR: $qPost->mysql_error",__LINE__);
            }
            $requests=$tpl->FormatNumber($qligne["tcount"]);
            $logs=$tpl->icon_loupe(1,"Loadjs('fw.postfix.acl.accesses.php?popjs=yes&instance-id=$instanceid&ruleid=$ID')");
        }
        if($enabled==0){
            $class="text-muted";
        }
        $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='acl-$ID'>";
        $html[]="<td $tdmid class=\"$class\" nowrap>$status</td>";
        $html[]="<td $tdmid class=\"text-right\" width='1%' nowrap><span class='$class'>$requests</span></td>";
        $html[]="<td $tdmid nowrap><span class='$class'>$rulerow</span></td>";
        $html[]="<td style='vertical-align:middle;width:99%' class=\"$class\">$explain</span></td>";
        $html[]="<td $tdmid class='center'>$logs</td>";
        $html[]="<td $tdmid class='center'>$check</td>";
        $html[]="<td $tdmid class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
        $html[]="<td $tdmid class='center'>$delete</td>";
        $html[]="</tr>";
    }

    $requests=$tpl->icon_nothing();
    $logs=$tpl->icon_nothing();
    $status="<span class='label label-primary'>{FORWARD}</span>";
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
    if($TRCLASS=="footable-odd "){$TRCLASS=null;}else{$TRCLASS="footable-odd ";}
    $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='acl-0'>";
    $html[]="<td $tdmid class=\"$class\" nowrap>$status</td>";
    $html[]="<td $tdmid class=\"text-right\" width='1%' nowrap><span class='$class'>$requests</span></td>";
    $html[]="<td $tdmid nowrap><span class='$class'>$rulerow</span></td>";
    $html[]="<td style='vertical-align:middle;width:99%' class=\"$class\">{finally_allow_all} {and} {forward_messages_to} {APP_POSTFIX}</span></td>";
    $html[]="<td $tdmid class='center'>$logs</td>";
    $html[]="<td $tdmid class='center'>&nbsp;</td>";
    $html[]="<td $tdmid class='center' nowrap>&nbsp;</center></td>";
    $html[]="<td $tdmid class='center'>&nbsp;</td>";
    $html[]="</tr>";


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $add="Loadjs('$page?newrule-js=yes&function=$function&instance-id=$instanceid',true);";
    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?reload=yes');", ico_refresh, "{reload_service}");

    $TINY_ARRAY["TITLE"]="{APP_POSTFIX}&nbsp;&raquo;&nbsp;{ACLS}";
    $TINY_ARRAY["ICO"]="fas fa-list";
    $TINY_ARRAY["EXPL"]="{artica_milter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
<script> 
    function RefreshPostfixRules(){ {$_GET["function"]}(); }
    $jstiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}




function EXPLAIN_THIS_RULE($ID){

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM postfix_rules WHERE ID='$ID'");

    if(!$q->ok){return $q->mysql_error;}
    $ruletype=intval($ligne["ruletype"]);
    $actionvalue=$ligne["actionvalue"];
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


    $name="&nbsp;{then} <strong>{$GLOBALS["POSTFIX_RULES_TITLE"][$ruletype]} $actionvalue</strong>";
   return $name;
}

function new_rule_save():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    patch_table();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    unset($_POST["newrule"]);

    foreach ($_POST as $key=>$val){
        $add_fields[]="`$key`";
        $add_values[]="'$val'";
    }
    $sql="INSERT INTO postfix_rules (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."<hr>$sql";return false;}
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

    if($UnBoundCacheMinTTL==0){$UnBoundCacheMinTTL=3600;}
    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}
    if($UnBoundCacheNEGTTL==0){$UnBoundCacheNEGTTL=3600;}

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

