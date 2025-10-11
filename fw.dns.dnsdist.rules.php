<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

//

$GLOBALS["DNS_RULES"][1]="{load_balance_dns_action}";
$GLOBALS["DNS_RULES"][2]="{refuse_to_resolve}"; //RCodeAction(dnsdist.REFUSED)
$GLOBALS["DNS_RULES"][3]="{SpoofCNAMEAction}";
$GLOBALS["DNS_RULES"][4]="{SpoofAction}";
$GLOBALS["DNS_RULES"][6]="{us_google_dns_gdmains}";

if(isset($_GET["counts-js"])){count_js();exit;}
if(isset($_GET["rule-safesearch"])){rule_safesearch();exit;}
if(isset($_GET["replace-rule"])){replace_rule();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["new-rule-js2"])){new_rule_js2();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_GET["newrule-popup2"])){new_rule_popup2();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}
if(isset($_POST["newrule2"])){new_rule_save2();exit;}
if(isset($_POST["cache_settings"])){rule_cache_save();exit;}
if(isset($_POST["EnableGoogleSafeSearch"])){rule_safesearch_save();exit;}

if(isset($_GET["ch-method-js"])){change_method_js();exit;}
if(isset($_GET["ch-method-popup"])){change_method_popup();exit;}
if(isset($_POST["ch-rule"])){change_method_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["default-js"])){default_js();exit;}
if(isset($_GET["default-popup"])){default_popup();exit;}
if(isset($_POST["ProxyDefaultUncryptSSL"])){ProxyDefaultUncryptSSL_save();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["rule-cache"])){rule_cache();exit;}
if(isset($_GET["view-rules"])){view_rules_js();exit;}
if(isset($_GET["view-rules-popup"])){view_rules_popup();exit;}
page();


function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{APP_DNSDIST}&nbsp;&raquo;&nbsp;{DNS_ACLS}",
        "fas fa-list","&nbsp;","$page?table-start=yes","dnsdist-acls",
        "progress-dnsdist-restart",false,"table-acls-dnsdist-rules");

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
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ID'");
    $crypt=$ligne["crypt"];
    $enabled=intval($ligne["enabled"]);
    if($crypt==1 OR ($ligne["trust"]==1)){$ACCESSEnabled=1;}
    $squid_acls_groups=new squid_acls_groups();
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"dnsdist_sqacllinks");
    $and_text=$tpl->javascript_parse_text(" {and} ");

    $TTEXT=array();
    $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");

    if(count($objects)>0) {

        $explain=$squid_acls_groups->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ACCESSEnabled,0,"dnsdist_sqacllinks")." {then} ".@implode($and_text, $TTEXT);

    }else{
        $explain="<div class=text-danger'>$please_specify_an_object</div>";
    }
    $img=$tpl->_ENGINE_parse_body(icon_status($crypt,$enabled,$objects));
    $explain=$tpl->_ENGINE_parse_body($explain);
    header("content-type: application/x-javascript");
    echo "document.getElementById('ssl-rule-icon-$ID').innerHTML=\"$img\";\n";
    echo "document.getElementById('ssl-rule-text-$ID').innerHTML=\"$explain\";\n";
}

function rule_delete_js(){
    header("content-type: application/x-javascript");
    $ID=$_GET["rule-delete-js"];
    $md="acl-$ID";
    if(!rule_delete($ID)){return;}
    echo "$('#$md').remove();";


}
function rule_enable():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM dnsdist_rules WHERE ID='$ID'");
    $enabled_src=intval($ligne["enabled"]);
    if($enabled_src==0){
        $js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
        $enabled=1;
    }else{
        $js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
        $enabled=0;
    }

    $q->QUERY_SQL("UPDATE dnsdist_rules SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");


    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}

    header("content-type: application/x-javascript");
    echo "// ID = $ID, src=$enabled_src, enabled =$enabled\n";
    echo $js."\n";
    echo "Loadjs('$page?filltable=$ID');\n";
    return true;
}
function change_method_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ch-method-js"]);
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `rulename` FROM dnsdist_rules WHERE ID='$ID'");
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
    $ligne=$q->mysqli_fetch_array("SELECT `rulename`,ruletype FROM dnsdist_rules WHERE ID='$ID'");
    $ruletype=intval($ligne["ruletype"]);

    fillRulesList_explain();
    $RULESSWITH=fillRules_canchange();
    unset($GLOBALS["DNS_RULES"][$ruletype]);
    foreach ($GLOBALS["DNS_RULES"] as $rtype=>$none){
        if(!isset($RULESSWITH[$rtype])){
            unset($GLOBALS["DNS_RULES"][$rtype]);
        }
    }

    $form[]=$tpl->field_hidden("ch-rule", $ID);
    $form[]=$tpl->field_array_checkboxes2Columns($GLOBALS["DNS_RULES"],"ruletype",1);

    $jsafter[]="BootstrapDialog1.close()";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="RefreshDNSDistRules()";
    if(strlen($function)>3){
        $jsafter[]="$function()";
    }
    $jsafter[]="Loadjs('$page?rule-id-js=$ID&function=$functionenc')";
    $jsafters=@implode(";",$jsafter);

    $html=$tpl->form_outside("", $form,null,"{apply}",$jsafters,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function change_method_save():bool{
    $tpl=new template_admin();
    $ID=intval($_POST["ch-rule"]);
    $ruletype2=intval($_POST["ruletype"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `rulename`,ruletype FROM dnsdist_rules WHERE ID='$ID'");
    $ruletype=intval($ligne["ruletype"]);
    $rulename=$ligne["rulename"];

    $q->QUERY_SQL("UPDATE dnsdist_rules SET ruletype=$ruletype2 WHERE ID=$ID");
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
    $ligne=$q->mysqli_fetch_array("SELECT rulename,ruletype FROM dnsdist_rules WHERE ID='$ID'");
    $ruletype=$ligne["ruletype"];
    $tpl->js_dialog("{rule}: $ID {$ligne["rulename"]}/$ruletype","$page?rule-tabs=$ID&function=$function");
    return true;
}
function default_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{default}","$page?default-popup=yes");
    return true;
}
function rule_cache_save():bool{
    $ID=intval($_POST["cache_settings"]);
    $tpl=new template_admin();
    $cache_settings=base64_encode(serialize($_POST));
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("UPDATE dnsdist_rules SET `dns_caches`='$cache_settings' WHERE ID=$ID");
    if(!$q->ok){$tpl->post_error($q->mysql_error);}
    return true;
}

function rule_safesearch():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-safesearch"]);
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `dns_caches` FROM dnsdist_rules WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $safe_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);
    $EnableGoogleSafeSearch=intval($safe_settings["EnableGoogleSafeSearch"]);
    $EnableBraveSafeSearch=intval($safe_settings["EnableBraveSafeSearch"]);
    $EnableDuckduckgoSafeSearch=intval($safe_settings["EnableDuckduckgoSafeSearch"]);
    $EnableYandexSafeSearch=intval($safe_settings["EnableYandexSafeSearch"]);
    $EnablePixabaySafeSearch=intval($safe_settings["EnablePixabaySafeSearch"]);
    $EnableQwantSafeSearch=intval($safe_settings["EnableQwantSafeSearch"]);
    $EnableBingSafeSearch=intval($safe_settings["EnableBingSafeSearch"]);
    $EnableYoutubeSafeSearch=intval($safe_settings["EnableYoutubeSafeSearch"]);
    $EnbaleYoutubeModerate=intval($safe_settings["EnbaleYoutubeModerate"]);

    $form[]=$tpl->field_hidden("gbdbid",$ID);
    $form[]=$tpl->field_checkbox("EnableGoogleSafeSearch","Google SafeSearch",$EnableGoogleSafeSearch,false,"{safesearch_explain}");
    $form[]=$tpl->field_checkbox("EnableQwantSafeSearch","Qwant SafeSearch",$EnableQwantSafeSearch,false,"{qwant_safesearch_explain}");
    $form[]=$tpl->field_checkbox("EnableBraveSafeSearch","Brave SafeSearch",$EnableBraveSafeSearch,false,"{qwant_safesearch_explain}");

    $form[]=$tpl->field_checkbox("EnableBingSafeSearch","Bing SafeSearch",$EnableBingSafeSearch,false,"");
    $form[]=$tpl->field_checkbox("EnableYoutubeSafeSearch","Youtube (strict)",$EnableYoutubeSafeSearch,false,"");
    $form[]=$tpl->field_checkbox("EnbaleYoutubeModerate","Youtube (Moderate)",$EnbaleYoutubeModerate,false,"");
    $form[]=$tpl->field_checkbox("EnableDuckduckgoSafeSearch","Duckduckgo",$EnableDuckduckgoSafeSearch,"");
    $form[]=$tpl->field_checkbox("EnableYandexSafeSearch","Yandex",$EnableYandexSafeSearch,"");
    $form[]=$tpl->field_checkbox("EnablePixabaySafeSearch","Pixabay",$EnablePixabaySafeSearch,"");
    $js=null;
    if($function<>null){
        $js="$function();";
    }
    echo $tpl->form_outside(null, $form,null,"{apply}",$js,"AsDnsAdministrator",true);
    return true;
}
function rule_safesearch_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["gbdbid"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `dns_caches` FROM dnsdist_rules WHERE ID='$ID'");
    $safe_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);
    foreach ($_POST as $key=>$val){
        $safe_settings[$key]=$val;
    }
    $cache_settings=base64_encode(serialize($safe_settings));
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("UPDATE dnsdist_rules SET `dns_caches`='$cache_settings' WHERE ID=$ID");
    if(!$q->ok){$tpl->post_error($q->mysql_error);}
}
function rule_cache(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-cache"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->FIELD_EXISTS("dnsdist_rules","dns_caches")) {
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `dns_caches` TEXT NULL");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }


    $ligne=$q->mysqli_fetch_array("SELECT `dns_caches` FROM dnsdist_rules WHERE ID='$ID'");

    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $cache_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);


    if(!isset( $cache_settings["cache_enable"])){ $cache_settings["cache_enable"]=0;}
    if(!isset( $cache_settings["MaxRecords"])){ $cache_settings["MaxRecords"]=10000;}
    if(!isset( $cache_settings["maxTTL"])){ $cache_settings["maxTTL"]=86400;}
    if(!isset( $cache_settings["minTTL"])){ $cache_settings["minTTL"]=0;}
    if(!isset( $cache_settings["staleTTL"])){ $cache_settings["staleTTL"]=60;}


    $TIMES[0]="{none}";
    $TIMES[10]="10 {seconds}";
    $TIMES[20]="20 {seconds}";
    $TIMES[30]="30 {seconds}";
    $TIMES[60]="1 {minute}";
    $TIMES[300]="5 {minutes}";
    $TIMES[900]="15 {minutes}";
    $TIMES[1800]="30 {minutes}";
    $TIMES[3600]="1 {hour}";
    $TIMES[7200]="2 {hours}";
    $TIMES[10800]="3 {hours}";
    $TIMES[14400]="4 {hours}";
    $TIMES[28800]="8 {hours}";
    $TIMES[57600]="16 {hours}";
    $TIMES[86400]="1 {day}";
    $TIMES[172800]="2 {days}";
    $TIMES[604800]="7 {days}";

    $tpl->field_hidden("cache_settings",$ID);
    $form[] = $tpl->field_checkbox("cache_enable", "{DHCPDEnableCacheDNS}", $cache_settings["cache_enable"], "MaxRecords,minTTL,maxTTL,staleTTL");
    $form[]=$tpl->field_numeric("MaxRecords","{max_records_in_memory}",$cache_settings["MaxRecords"]);
    $form[]=$tpl->field_array_hash($TIMES, "minTTL", "{cache-ttl} (Min)", $cache_settings["minTTL"]);
    $form[]=$tpl->field_array_hash($TIMES, "maxTTL", "{cache-ttl} (Max)", $cache_settings["maxTTL"]);
    $form[]=$tpl->field_array_hash($TIMES, "staleTTL", "{negquery-cache-ttl}", $cache_settings["staleTTL"]);

    echo $tpl->form_outside("{dns_cache}", @implode("\n", $form),null,"{apply}",
        "RefreshDNSDistRules()","AsDnsAdministrator");

}

function rule_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $function=$_GET["function"];
    $ID=intval($_GET["rule-tabs"]);
    $RefreshFunction=base64_encode("RefreshDNSDistRules()");
    $array["{rule}"]="$page?rule-settings=$ID&function=$function";
    $RefreshTable=base64_encode("LoadAjax('dnsdist-acl-table','$page?table=yes');");
    $array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=dnsdist_sqacllinks&RefreshTable=$RefreshTable&RefreshFunction=$RefreshFunction&function=$function";
    $array["{cache}"]="$page?rule-cache=$ID&function=$function";

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT `ruletype` FROM dnsdist_rules WHERE ID='$ID'");
    $ruletype=$ligne["ruletype"];
    if($ruletype==2 OR $ruletype==3 OR $ruletype==4 OR $ruletype==9){unset($array["{cache}"]);}
    if($ruletype==9){
        $array["SafeSearch(s)"]="$page?rule-safesearch=$ID&function=$function";
    }
    echo $tpl->tabs_default($array);

}
function default_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ProxyDefaultUncryptSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDefaultUncryptSSL"));
    $jsafter="RefreshDNSDistRules();BootstrapDialog1.close();";
    $form[]=$tpl->field_hidden("default", "yes");
    $form[]=$tpl->field_checkbox("ProxyDefaultUncryptSSL","{uncrypt_ssl}",$ProxyDefaultUncryptSSL,false,"{uncrypt_ssl_explain}");
    $html=$tpl->form_outside("{default}", @implode("\n", $form),null,"{apply}",$jsafter,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}
function ProxyDefaultUncryptSSL_save(){
    $sock=new sockets();
    $sock->SET_INFO("ProxyDefaultUncryptSSL",$_POST["ProxyDefaultUncryptSSL"]);

}

function FireWallObjects():array{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q2=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $sql="SELECT firewallfilter_sqacllinks.gpid,
                                  firewallfilter_sqacllinks.negation,
                                  firewallfilter_sqacllinks.aclid,
                                  firewallfilter_sqacllinks.zOrder,
                                  firewallfilter_sqacllinks.zmd5 
                                  as mkey, webfilters_sqgroups.* FROM firewallfilter_sqacllinks,
                                  webfilters_sqgroups WHERE 
                                  firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID            
                                  AND firewallfilter_sqacllinks.direction=0
                                  AND webfilters_sqgroups.GroupType='src'
                                  AND webfilters_sqgroups.enabled=1
                                  ORDER BY firewallfilter_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);
    $MAIN=array();
    foreach ($results as $index=>$ligne){
        $GroupName=$ligne["GroupName"];
        $aclid=$ligne["aclid"];
        $gpid=$ligne["ID"];
        $ligne2=$q2->mysqli_fetch_array("SELECT rulename FROM iptables_main WHERE ID=$aclid");
        $rulename=$ligne2["rulename"];
        $IpsetName="src-$aclid-$gpid";
        $MAIN[$IpsetName]="$rulename: $GroupName";
    }
    return $MAIN;

}

function rule_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=$_GET["rule-settings"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

    if(!$q->FIELD_EXISTS("dnsdist_rules","useClientSubnet")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `useClientSubnet` INTEGER NOT NULL DEFAULT 0");
        if(!$q->ok){echo $tpl->div_error("{sql_error}||$q->mysql_error");}
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","simpledomains")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `simpledomains` NULL");
        if(!$q->ok){echo $tpl->div_error("{sql_error}||$q->mysql_error");}
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkTimeout")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD checkTimeout INT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","rulevalue")){
            $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD a TEXT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","fwobject")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD fwobject TEXT NOT NULL DEFAULT ''");
    }

    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ID'");

    $ruletype=$ligne["ruletype"];
    $form[]=$tpl->field_hidden("ID", $ID);



    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
    $form[]=$tpl->field_checkbox("useClientSubnet","{useClientSubnet}",$ligne["useClientSubnet"],false,"{useClientSubnet_explain}");

    if($FireHolEnable==1){
        if($ruletype==2){
            $FWGroups=FireWallObjects();
            $form[]=$tpl->field_array_hash($FWGroups,"fwobject","{firewall_group}",$ligne["fwobject"]);
        }
    }






    if($ruletype==1){
        $DNSDistCheckName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckName"));
        $DNSDistCheckInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
        $DNSDistMaxCheckFailures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
        $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));
        if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
        if($DNSDistCheckInterval==0){$DNSDistCheckInterval=2;}
        if($DNSDistMaxCheckFailures==0){$DNSDistMaxCheckFailures=3;}
        if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}
        if($DNSDistCheckInterval<2){$DNSDistCheckInterval=2;}
        if(is_null($ligne["checkName"])){$ligne["checkName"]=$DNSDistCheckName;}
        if(trim($ligne["checkName"])==null){$ligne["checkName"]=$DNSDistCheckName;}
        if(intval($ligne["checkInterval"])==0){$ligne["checkInterval"]=$DNSDistCheckInterval;}
        if(intval($ligne["maxCheckFailures"])==0){$ligne["maxCheckFailures"]=$DNSDistMaxCheckFailures;}
        if(intval($ligne["checkTimeout"])==0){$ligne["checkTimeout"]=$DNSDistCheckTimeout;}

        $form[]=$tpl->field_section("{APP_HAPROXY_SERVICE}");
        $form[]=$tpl->field_text("checkName", "{check_addr}", $ligne["checkName"],true);
        $form[]=$tpl->field_numeric("checkTimeout", "{timeout} ({seconds})", $ligne["checkTimeout"]);
        $form[]=$tpl->field_numeric("checkInterval", "{check_interval} ({seconds})", $ligne["checkInterval"]);
        $form[]=$tpl->field_numeric("maxCheckFailures", "{failed_number} ({attempts})", $ligne["maxCheckFailures"]);


    }

    $rulevalue_explain=null;

    if($ruletype==4){
        $rulevalue_explain="{dnsfw_forge_explain}";
        $form[]=$tpl->field_text("simpledomains", "{destination_domains}", $ligne["simpledomains"],false,"{dnsfw_forge_domain_explain}");
    }

    if( ($ruletype==3) OR ($ruletype==4) ){
        $form[]=$tpl->field_text("rulevalue", "{value_to_send}", $ligne["rulevalue"],false,$rulevalue_explain);
    }

    $RULESSWITH=fillRules_canchange();

    if(isset($RULESSWITH[$ruletype])){
        $tpl->form_add_button("{change_method}","Loadjs('$page?ch-method-js=$ID&function=$function');");

    }
    $jsafter=null;
    if($function<>null) {
        $jsafter = "$function();";
    }

    $html=$tpl->form_outside("{rule} {$ligne["rulename"]} <small>{$GLOBALS["DNS_RULES"][$ruletype]} </small>", $form,null,"{apply}",$jsafter,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function patch_table():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("dnsdist_rules","caches")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD caches TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","uuid")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD uuid TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","simpledomains")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `simpledomains` TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkInterval")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD COLUMN checkInterval INTEGER NOT NULL DEFAULT 5");
        if(!$q->ok){
            echo $q->mysql_error;
        }
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","rulevalue")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD rulevalue TEXT");
    }


    if(!$q->FIELD_EXISTS("dnsdist_rules","maxCheckFailures")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD COLUMN maxCheckFailures INTEGER NOT NULL DEFAULT 3");
    }

    if(!$q->FIELD_EXISTS("dnsdist_rules","checkTimeout")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD checkTimeout INT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkName")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD COLUMN checkName TEXT NOT NULL DEFAULT 'a.root-servers.net'");
        if(!$q->ok){
            echo $q->mysql_error;
        }
    }

    if(!$q->FIELD_EXISTS("dnsdist_rules","checkInterval")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD COLUMN checkInterval INTEGER NOT NULL DEFAULT 5");
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD COLUMN maxCheckFailures INTEGER NOT NULL DEFAULT 3");
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD COLUMN checkName TEXT NOT NULL DEFAULT 'a.root-servers.net'");


    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","dns_caches")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD dns_caches TEXT NULL");
    }
    return true;

}

function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    $ID=$_POST["ID"];
    unset($_POST["ID"]);
    $edit_fields=array();

if(isset($_POST["checkInterval"])){
    if(intval($_POST["checkInterval"])<2){
        $languageF=$tpl->_ENGINE_parse_body("{check_interval} ({seconds})");
        echo "jserror:$languageF < 2";
        return;
    }
    }
    if(isset($_POST["checkInterval"])){
        if(intval($_POST["checkTimeout"])<3){
            $languageF=$tpl->_ENGINE_parse_body("{timeout} ({seconds})");
            echo "jserror:$languageF < 3";
            return;
        }

    }


    foreach ($_POST as $key=>$val){
        $edit_fields[]="`$key`='$val'";
    }



    $sql="UPDATE dnsdist_rules SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");



    patch_table();

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
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
    $newTB=array();
    $tb=explode("\n",$json->Info);
    foreach ($tb as $index=>$ligne){
        if(strpos($ligne,"#")>0){continue;}
        if(strpos($ligne," plain-text password")){
            continue;
        }
        $newTB[]=$ligne;
    }



    $text=@implode("\n",$newTB);
    echo "<textarea style='width:100%;height:100%;min-height: 450px' readonly>$text</textarea>";

}

function new_rule_js2():bool{
    $page=CurrentPageName();
    $LAST_DNSFW_POSTED_RULE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LAST_DNSFW_POSTED_RULE");
    if($LAST_DNSFW_POSTED_RULE==null){return false;}
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE uuid='$LAST_DNSFW_POSTED_RULE'");
    $rulename=$ligne["rulename"];
    $ruletype=intval($ligne["ruletype"]);
    $ID=intval($ligne["ID"]);
    if($ruletype==10){
        return $tpl->js_dialog("$rulename","$page?newrule-popup2=$ID");
    }
    return false;
}

function new_rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{new_rule}";
    return $tpl->js_dialog($title,"$page?newrule-popup=yes");

}
function new_rule_popup2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["newrule-popup2"];
    $form[]=$tpl->field_hidden("newrule2", $ID);

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ID'");
    $rulename=$ligne["rulename"];
    $ruletype=intval($ligne["ruletype"]);
    fillRulesList_explain();
    $explain=$GLOBALS["DNS_RULES"][$ruletype];
    $domain=null;
    if(preg_match("#^[a-z\-_0-9]+\.[a-z]+$#",$rulename,$re)){
        $domain=$re[1];
    }

    if($ruletype==10){
        $form[]=$tpl->field_text("addomain", "{activedirectory_domain}", $domain,true);
        $form[]=$tpl->field_ipv4("adaddr","{activedirectory_addr}",null,true);
        $jsafter="RefreshDNSDistRules();BootstrapDialog1.close();";
        $html=$tpl->form_outside($rulename, $form,$explain,"{add}",$jsafter,"AsDnsAdministrator");
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    return false;
}
function new_rule_save2():bool{
    //We use "dns_caches" entry for storing values, be cleaned in with background wizard task.
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["newrule2"]);
    $data=base64_encode(serialize($_POST));
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("UPDATE dnsdist_rules SET dns_caches='$data' WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->getGoFramework("exec.dnsdist.php --template10 $ID");
    return true;
}


function new_rule_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    fillRulesList_explain();

    $form[]=$tpl->field_hidden("newrule", "yes");
    $form[]=$tpl->field_hidden("enabled","1");
    $form[]=$tpl->field_array_checkboxes2Columns($GLOBALS["DNS_RULES"],"ruletype",1,false,null);
//

    $form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
    $jsafter="RefreshDNSDistRules();BootstrapDialog1.close();Loadjs('$page?new-rule-js2=yes');";
    $html=$tpl->form_outside("{new_rule}", @implode("\n", $form),null,"{add}",$jsafter,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function fillRulesList_explain():bool{
    $GLOBALS["DNS_RULES"][1]="<strong>{load_balance_dns_action}</strong><br>{load_balance_dns_action_explain}";
    $GLOBALS["DNS_RULES"][2]="<strong>{refuse_to_resolve}</strong><br>{refuse_to_resolve_explain}";
    $GLOBALS["DNS_RULES"][3]="<strong>{SpoofCNAMEAction}</strong><br>{SpoofCNAMEAction_explain}";
    $GLOBALS["DNS_RULES"][4]="<strong>{SpoofAction}</strong><br>{SpoofAction_explain}";
    $GLOBALS["DNS_RULES"][6]="<strong>{us_google_dns_gdmains}</strong><br>{us_google_dns_gdmains_explain}";
    $GLOBALS["DNS_RULES"][7]="<strong>{web_browsing_protection}</strong><br>{web_browsing_protection_explain}";
    $GLOBALS["DNS_RULES"][8]="<strong>{web_browsing_cleaning}</strong><br>{web_browsing_cleaning_explain}";
    $GLOBALS["DNS_RULES"][9]="<strong>SafeSearch(s)</strong><br>{app_safesearch_explains}";
    $GLOBALS["DNS_RULES"][10]="<strong>Active Directory Offloading</strong><br>{activedirectory_offloadingexp}";
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
    $sql="SELECT zOrder FROM dnsdist_rules WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
    $xORDER_ORG=intval($ligne["zOrder"]);
    $xORDER=$xORDER_ORG;


    if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
    if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
    if($xORDER<0){$xORDER=0;}
    $sql="UPDATE dnsdist_rules SET zOrder=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    if($GLOBALS["VERBOSE"]){echo "$sql\n";}

    if($_GET["acl-rule-dir"]==1){
        $xORDER2=$xORDER+1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE dnsdist_rules SET zOrder=$xORDER2 WHERE `ID`<>'$ID' AND zOrder=$xORDER";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}

        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }
    if($_GET["acl-rule-dir"]==0){
        $xORDER2=$xORDER-1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE dnsdist_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}' AND zOrder=$xORDER";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
    }

    $c=0;
    $sql="SELECT ID FROM dnsdist_rules ORDER BY zOrder";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE dnsdist_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "UPDATE dnsdist_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }


}

function table_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null,null,"&table=yes");

}

function replace_rule(){
    $tpl=new template_admin();
    $ID=$_GET["replace-rule"];
    $page=CurrentPageName();
    $squid_acls_groups=new squid_acls_groups();
    $GLOBALS["ACL_OBJECTS_JS_AFTER"]=base64_encode("LoadAjax('dnsdist-rule-text-$ID','$page?replace-rule=$ID');");
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"dnsdist_sqacllinks");

    if(count($objects)==0) {
        echo $tpl->_ENGINE_parse_body("<strong class=\"text-danger\">{please_specify_an_object}</strong>");
        return;
    }

    $explain="&nbsp;{for_objects} ". @implode(" {and} ", $objects);
    $explain=$explain.EXPLAIN_THIS_RULE($ID);
    $tpl->_ENGINE_parse_body($explain);
}


function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];

    $html[]="<table id='table-dns-fw-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
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

    $jsAfter="LoadAjax('table-firewall-rules','$page?table=yes');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $squid_acls_groups=new squid_acls_groups();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->FIELD_EXISTS("dnsdist_rules","caches")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `dns_caches` TEXT NULL");
        if(!$q->ok){echo $tpl->div_error("{sql_error}||$q->mysql_error");}
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","useClientSubnet")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `useClientSubnet` INTEGER NOT NULL DEFAULT 1");
        if(!$q->ok){echo $tpl->div_error("{sql_error}||$q->mysql_error");}
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","fwobject")) {
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `fwobject` TEXT INTEGER NOT NULL DEFAULT ''");
        if (!$q->ok) {
            echo $tpl->div_error("{sql_error}||$q->mysql_error");
        }
    }

    $sql="SELECT * FROM dnsdist_rules ORDER BY zOrder";
    if(isset($_GET["search"])){
        $search=$_GET["search"];
        if($search<>null){
            $search="*$search*";
            $search=str_replace("**","*",$search);
            $search=str_replace("*","%",$search);
            $sql="SELECT * FROM dnsdist_rules WHERE rulename LIKE '$search' ORDER BY zOrder";

        }
    }

    $results=$q->QUERY_SQL($sql);
    $TRCLASS=null;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/listrules"));
    $RULES_STATUS=array();
    if(property_exists($json,"Info")){
        foreach ($json->Info as $RID=>$hits){
            $RULES_STATUS[$RID]=$hits;
        }
    }

    $valign="vertical-align:middle;";
    $textright="class=\"text-right\"";
    foreach($results as $index=>$ligne) {
        if($TRCLASS=="footable-odd "){$TRCLASS=null;}else{$TRCLASS="footable-odd ";}
        $explain=null;
        $ACCESSEnabled=1;
        $ruletype=$ligne["ruletype"];
        $ID=$ligne["ID"];
        $rulename=$tpl->utf8_encode($ligne["rulename"]);
        $uuid=$ligne["uuid"];
        $uuids=$ligne["uuids"];
        VERBOSE(" ------------------ Rule.$ID $rulename uuid=[$uuid] uuids=[$uuids]",__LINE__);
        $status=$status_inactive;
        $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");
        $useClientSubnet=intval($ligne["useClientSubnet"]);
        $useClientSubnet_ico=null;


        $TTEXT=array();
        $TTEXT[]=$GLOBALS["DNS_RULES"][$ruletype];

        $GLOBALS["ACL_OBJECTS_JS_AFTER"]=base64_encode("LoadAjax('dnsdist-rule-text-$ID','$page?replace-rule=$ID');");
        $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"dnsdist_sqacllinks");

        if($ruletype==6){
            if(count($objects)==0){$objects[]="&laquo;{everyone}&raquo;";}
            $useClientSubnet=0;
        }
        if($ruletype==9){
            if(count($objects)==0){$objects[]="&laquo;{everyone}&raquo;";}
            $useClientSubnet=0;
        }

        if(count($objects)>0){
            $explain="&nbsp;{for_objects} ". @implode(" {and} ", $objects);
        }else{
            if($ruletype==1) {
                $ACCESSEnabled = 0;
                $explain = "<strong class=\"text-danger\">$please_specify_an_object</strong>";
            }
            if($ruletype==2) {
                $ACCESSEnabled = 0;
                $explain = "<strong class=\"text-danger\">$please_specify_an_object</strong>";
            }
            if($ruletype==3) {
                $ACCESSEnabled = 0;
                $explain = "<strong class=\"text-danger\">$please_specify_an_object</strong>";
            }
            if($ruletype==5) {
                $ACCESSEnabled = 0;
                $explain = "<strong class=\"text-danger\">$please_specify_an_object</strong>";
            }

        }


        $class="text-muted";


        if($useClientSubnet==1){$useClientSubnet_ico="&nbsp;<span class='label label-info'>EDNS</span>";}
        if($ACCESSEnabled==1){$explain=$explain.EXPLAIN_THIS_RULE($ID);}

        $delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
        $js="Loadjs('$page?rule-id-js=$ID&function=$function')";

        $up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
        $rulename=$tpl->utf8_decode($rulename);
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')");
        $rulerow=$tpl->td_href($rulename,"{click_to_edit}",$js);
        $logs=$tpl->icon_loupe();
        $requests=0;

        if(isset($RULES_STATUS[$ID])){
            $status = "<span class='label label-primary'>{active2}</span>";
            if($RULES_STATUS[$ID]>0) {
                $requests = $tpl->FormatNumber($RULES_STATUS[$ID]);
                $class = "text-primary";
                $logs=$tpl->icon_loupe(true,"Loadjs('fw.dnsdist.logs.php?rule-js=$ID')");
            }

            if($RULES_STATUS[$ID]==0) {
                $requests = 0;
                $status = "<span class='label label-default'>{active2}</span>";
                $class = "text-primary";
            }

        }
        if($ruletype==2 OR $ruletype==3 OR $ruletype==4 OR $ruletype==6){$useClientSubnet_ico=null;}

        $html[]="<tr style='$valign' class='$TRCLASS' id='acl-$ID'>";
        $html[]="<td style='$valign;width:1%' class=\"$class\" nowrap><span class='$class' id='RA-$ID'> $status</span></td>";
        $html[]="<td style='$valign;width:1%;' $textright nowrap><span class='$class' id='RR-$ID'>$requests</span></td>";
        $html[]="<td style='$valign;width:1%' nowrap><span class='$class'>$rulerow</span></td>";
        $html[]="<td style='$valign;width:99%' class=\"$class\" >$explain</span>$useClientSubnet_ico</td>";
        $html[]="<td style='$valign;width:1%' class='center'>$logs</td>";
        $html[]="<td style='$valign;width:1%' class='center'>$check</td>";
        $html[]="<td style='$valign;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</td>";
        $html[]="<td style='$valign;width:1%' class='center'>$delete</td>";
        $html[]="</tr>";
    }


    $default_rule=default_rule();
    $requests=0;
    $status=$status_inactive;
    //$DNSDIST_DEFAULT_UUID=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDIST_DEFAULT_UUID");
    if(isset($RULES_STATUS[0])){
        $requests=$tpl->FormatNumber($RULES_STATUS[0]);
        $status="<span class='label label-primary'>{active2}</span>";
    }

    if($default_rule<>null) {
        $logs=$tpl->icon_loupe(true,"Loadjs('fw.dnsdist.logs.php?rule-js=0')");
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd ";}
        $html[] = "<tr style='$valign' class='$TRCLASS' id='acl-0'>";
        $html[] = "<td style='$valign;width:1%' nowrap><span id='RA-0'>$status</span></td>";
        $html[] = "<td style='$valign' $textright nowrap><span id='RR-0'>$requests</span></td>";
        $html[] = "<td style='$valign;width:1%'  nowrap>{default}</td>";
        $html[] = "<td style='$valign'>$default_rule</td>";
        $html[] = "<td class='center'>$logs</td>";
        $html[] = "<td class='center'>" . $tpl->icon_nothing() . "</td>";
        $html[] = "<td class='center' style='$valign'>" . $tpl->icon_nothing() . "</td>";
        $html[] = "<td class='center' style='$valign'>" . $tpl->icon_nothing() . "</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $add="Loadjs('$page?newrule-js=yes&function=$function',true);";

    $jsrestart=$tpl->framework_buildjs("/dnsfw/service/php/restart",
        "dnsdist.restart",
        "dnsdist.restart.log","progress-dnsdist-restart","RefreshDNSDistRules();");

    $jsView="Loadjs('$page?view-rules=yes');";


    $btns="	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
        <label class=\"btn btn btn-blue\" OnClick=\"$jsView\"><i class='fad fa-search'></i> {view_rules} </label>
        <label class=\"btn btn btn-primary\" OnClick=\"RefreshDNSDistRules();\"><i class='far fa-sync-alt'></i> {reload} </label>
         <label class=\"btn btn btn-info\" OnClick=\"LoadAjaxSilent('MainContent','fw.dns.unbound.php');\"><i class='fab fa-gripfire'></i> {status} </label>
     </div>	";

    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}&nbsp;&raquo;&nbsp;{DNS_ACLS}";
    $TINY_ARRAY["ICO"]="fas fa-list";
    $TINY_ARRAY["EXPL"]="{dnsdist_rules_explain}";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsRefr=$tpl->RefreshInterval_Loadjs("table-dns-fw-rules",$page,"counts-js=yes");
    
    $html[]="
<script> 
    function RefreshDNSDistRules(){ {$_GET["function"]}(); }
    $jstiny
    $jsRefr
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function ChecksIntervalDefaults($ligne):array{
    $DNSDistCheckName=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistCheckInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistMaxCheckFailures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
    $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));

    if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
    if($DNSDistCheckInterval==0){$DNSDistCheckInterval=1;}
    if($DNSDistMaxCheckFailures==0){$DNSDistMaxCheckFailures=3;}
    if($DNSDistCheckTimeout==0){$DNSDistCheckTimeout=1;}

    if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}
    if($DNSDistCheckInterval<2){$DNSDistCheckInterval=2;}
    if(is_null($ligne["checkName"])){$ligne["checkName"]=$DNSDistCheckName;}
    if(trim($ligne["checkName"])==null){$ligne["checkName"]=$DNSDistCheckName;}
    if(intval($ligne["checkInterval"])==0){$ligne["checkInterval"]=$DNSDistCheckInterval;}
    if(intval($ligne["maxCheckFailures"])==0){$ligne["maxCheckFailures"]=$DNSDistMaxCheckFailures;}
    if(intval($ligne["checkTimeout"])==0){$ligne["checkTimeout"]=$DNSDistCheckTimeout;}

    if(!isset($ligne["dns_caches"])){$ligne["dns_caches"]=base64_encode(serialize(array("cache_enable"=>0)));}
    $cache_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);

    if(!isset( $cache_settings["cache_enable"])){ $cache_settings["cache_enable"]=0;}
    if(!isset( $cache_settings["MaxRecords"])){ $cache_settings["MaxRecords"]=10000;}
    if(!isset( $cache_settings["maxTTL"])){ $cache_settings["maxTTL"]=86400;}
    if(!isset( $cache_settings["minTTL"])){ $cache_settings["minTTL"]=0;}
    if(!isset( $cache_settings["staleTTL"])){ $cache_settings["staleTTL"]=60;}
    $ligne["dns_caches"]=base64_encode(serialize($cache_settings));
    return $ligne;
}


function EXPLAIN_THIS_RULE($ID){
  /*  $GLOBALS["DNS_RULES"][1]="{load_balance}";
    $GLOBALS["DNS_RULES"][2]="{refuse_to_resolve}"; //RCodeAction(dnsdist.REFUSED)
    $GLOBALS["DNS_RULES"][3]="{SpoofCNAMEAction}";
    $GLOBALS["DNS_RULES"][4]="{SpoofAction}";
  */
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

  $page=CurrentPageName();
  $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ID'");
    if(!$q->ok){return $q->mysql_error;}
    $ruletype=$ligne["ruletype"];
    $cacheexpl=null;
    $ligne=ChecksIntervalDefaults($ligne);
    $checkName=$ligne["checkName"];
    $checkInterval=$ligne["checkInterval"];
    $maxCheckFailures=$ligne["maxCheckFailures"];
    $checkTimeout=$ligne["checkTimeout"];
    $cache_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);


    $REMOVEGRPS=array();
    if($cache_settings["cache_enable"]==1){
        $cache_settings["MaxRecords"]=$tpl->FormatNumber($cache_settings["MaxRecords"]);
        $cacheexpl="&nbsp;{and} <strong>{use_cache}</strong> {with} {$cache_settings["MaxRecords"]} {max_records_in_memory}";
        $UnBoundCacheMinTTL=$cache_settings["minTTL"];
        $UnBoundCacheMAXTTL=$cache_settings["maxTTL"];
        $UnBoundCacheNEGTTL=$cache_settings["staleTTL"];
        $UnBoundCacheMinTTLText=distanceOfTimeInWords(time(),time()+$UnBoundCacheMinTTL,true);
        $UnBoundCacheMAXTTLText=distanceOfTimeInWords(time(),time()+$UnBoundCacheMAXTTL,true);
        $UnBoundCacheNEGTTLText=distanceOfTimeInWords(time(),time()+$UnBoundCacheNEGTTL,true);
        $cacheexpl="$cacheexpl {minimum} {cache-ttl} $UnBoundCacheMinTTLText {and} <strong>$UnBoundCacheMAXTTLText {maximum}</strong> {and} {negquery-cache-ttl} $UnBoundCacheNEGTTLText";


    }
    $jsafter=base64_encode("RefreshDNSDistRules()");
    $name="&nbsp;{then} <strong>{$GLOBALS["DNS_RULES"][$ruletype]}</strong>";

    $checks="<br>{check_addr} <strong>$checkName</strong> {timeout} $checkTimeout {seconds} {each} $checkInterval {seconds}  $maxCheckFailures {attempts}";


    VERBOSE("RULE TYPE = $ruletype",__LINE__);

    if($ruletype==1){
        $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID 
		AND (webfilters_sqgroups.GroupType='dst' OR webfilters_sqgroups.GroupType='doh' OR webfilters_sqgroups.GroupType='opendns'  OR webfilters_sqgroups.GroupType='opendnsf')
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
        $gps=array();
        $results=$q->QUERY_SQL($sql);

        foreach ($results as $index=>$ligne){

            $GroupType=$ligne["GroupType"];
            if($GroupType=="opendns"){
                $REMOVEGRPS[$ligne['ID']]=true;
            }
            if($GroupType=="opendnsf"){
                $REMOVEGRPS[$ligne['ID']]=true;
            }
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $href="<a href=\"javascript:blur();\" 
			OnClick=\"Loadjs('fw.rules.items.php?groupid={$ligne['ID']}&js-after=$jsafter',true);\" 
			style=\"text-decoration:underline;\">";
            $gps[]="<strong>$href$GroupName</strong></a>";

        }

        if(count($gps)==0){
            $name=$name."&nbsp;<strong class='text-error'>{no_destination_as_been_defined}</strong>";
            return $name;
        }

        $name=$name." {dns_servers} ".@implode(" {or} ",$gps);
        return $name.$cacheexpl.$checks;
    }
    if($ruletype==2){
        if($FireHolEnable==1) {
            if (strlen($ligne["fwobject"]) > 2) {
                $tb=explode("-",$ligne["fwobject"]);
                $ruleid=$tb[1];
                $groupid=$tb[2];
                $q1 = new lib_sqlite("/home/artica/SQLITE/acls.db");
                $ligne1 = $q1->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'");
                $GroupName=$ligne1["GroupName"];
                $q2 = new lib_sqlite("/home/artica/SQLITE/firewall.db");
                $ligne1 = $q2->mysqli_fetch_array("SELECT rulename FROM iptables_main WHERE ID='$ruleid'");
                $rulename=$ligne1["rulename"];
                $text=$tpl->_ENGINE_parse_body("{dnsdist_fwxplain}");
                $text=str_replace("%groupid","<strong>$GroupName</strong>",$text);
                $text=str_replace("%ruleid","<strong>$rulename</strong>",$text);
                return $name."<br>".$text;
            }
        }
        return $name;
    }

    if($ruletype==3){
        if($ligne["rulevalue"]==null){
            return " <strong class=\"text-danger\">{value_to_send}: {no_entry}</strong>";
        }else{

            return " {then} {SpoofCNAMEAction} <strong>{$ligne["rulevalue"]}</strong>";
        }
    }

    if($ruletype==6){
        return " {then} <strong>{us_google_dns_gdmains}</strong>$cacheexpl$checks";

    }
    if($ruletype==9){
        $safe=array();
        if(intval($cache_settings["EnableGoogleSafeSearch"])){$safe[]="Google";}
        if(intval($cache_settings["EnableBraveSafeSearch"])){$safe[]="Brave";}
        if(intval($cache_settings["EnableDuckduckgoSafeSearch"])){$safe[]="Duckduckgo";}
        if(intval($cache_settings["EnableYandexSafeSearch"])){$safe[]="Yandex";}
        if(intval($cache_settings["EnablePixabaySafeSearch"])){$safe[]="Pixabay";}
        if(intval($cache_settings["EnableQwantSafeSearch"])){$safe[]="Qwant";}
        if(intval($cache_settings["EnableBingSafeSearch"])){$safe[]="Bing";}
        if(intval($cache_settings["EnableYoutubeSafeSearch"])){$safe[]="Youtube Strict";}
        if(intval($cache_settings["EnbaleYoutubeModerate"])){$safe[]="Youtube Moderate";}
        if(count($safe)==0){
            return " {then} <strong>{nothing}</strong>";
        }
        return " {then} <strong>SafeSearch:&nbsp;".@implode(", ",$safe)."</strong>";

    }


    if($ruletype==4){
        $spoofdomains=null;
        $simpledomains=$ligne["simpledomains"];
        if($simpledomains<>null){
            $spoofdomains="{domains} :$simpledomains&nbsp;";
        }

        $PP=array();
        if(strpos($ligne["rulevalue"],",")>0){
            $values=explode(",",$ligne["rulevalue"]);
            foreach ($values as $pattern){$PP[]=$pattern;}
        }else{
            if($ligne["rulevalue"]<>null){$PP[]=$ligne["rulevalue"];}
        }


        $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='dst'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
        $gps=array();
        $results=$q->QUERY_SQL($sql);

        $IPClass=new IP();
        foreach ($results as $index=>$ligne1){
            $gpid=$ligne1["gpid"];
            $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid ORDER BY pattern";
            $results2 = $q->QUERY_SQL($sql);
            foreach ($results2 as $index2=>$ligne3){
                $pattern=$ligne3["pattern"];
                if(!$IPClass->isIPAddressOrRange($pattern)){continue;}
                if(strpos($pattern,"/")>0){
                    $tt=explode("/",$pattern);
                    if($tt[1]<32){continue;}
                    $pattern=$tt[0];
                }
                $PP[]=$pattern;
            }
        }

        if(count($PP)>0){$ligne["rulevalue"]=@implode(", ",$PP);}

        if($ligne["rulevalue"]==null){
            return " <strong class=\"text-danger\">{value_to_send}: {no_entry}</strong>";
        }else{

            return " $spoofdomains{then} {SpoofAction} {with} <strong>{$ligne["rulevalue"]}</strong>";
        }
    }

    if($ruletype==5){

        return " {then} {do_not_cache} {DNS_QUERIES}";
    }
    
    return "Unknown";
}

function new_rule_save():bool{
//a.root-servers.net
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    patch_table();
    $wizard_protection=null;
    $wizard_cleaning=null;
    $wizard_safesearch=null;
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST["useClientSubnet"]=0;

    unset($_POST["newrule"]);
    if($_POST["ruletype"]==6){
        $_POST["checkName"]="redirector.googlevideo.com";

    }

    if($_POST["ruletype"]==7){
        $tpl=new template_admin();
        $_POST["checkName"]="redirector.googlevideo.com";
        $_POST["ruletype"]=2;
        if($_POST["rulename"]==null) {
            $_POST["rulename"] = $tpl->javascript_parse_text("{web_browsing_protection}");
        }
        $_POST["uuid"]=time();
        $wizard_protection=$_POST["uuid"];
    }
    if($_POST["ruletype"]==8){
        $tpl=new template_admin();
        $_POST["checkName"]="redirector.googlevideo.com";
        $_POST["ruletype"]=2;
        if($_POST["rulename"]==null) {
            $_POST["rulename"] = $tpl->javascript_parse_text("{web_browsing_cleaning}");
        }
        $_POST["uuid"]=time();
        $wizard_cleaning=$_POST["uuid"];
    }

    if($_POST["ruletype"]==9){
        $_POST["checkName"]="redirector.googlevideo.com";
        $_POST["ruletype"]=9;
        if($_POST["rulename"]==null) {
            $_POST["rulename"] = "SafeSearch(s)";
        }

        $safe_settings["EnableGoogleSafeSearch"]=1;
        $safe_settings["EnableBraveSafeSearch"]=1;
        $safe_settings["EnableDuckduckgoSafeSearch"]=1;
        $safe_settings["EnableYandexSafeSearch"]=0;
        $safe_settings["EnablePixabaySafeSearch"]=0;
        $safe_settings["EnableQwantSafeSearch"]=1;
        $safe_settings["EnableBingSafeSearch"]=1;
        $safe_settings["EnableYoutubeSafeSearch"]=0;
        $safe_settings["EnbaleYoutubeModerate"]=0;
        $cache_settings=base64_encode(serialize($safe_settings));
        $_POST["dns_caches"]=$cache_settings;

        $_POST["uuid"]=time();
        $wizard_safesearch=$_POST["uuid"];
    }

    if($_POST["ruletype"]==10){
        $_POST["checkName"]="redirector.googlevideo.com";
        $_POST["ruletype"]=10;
        if($_POST["rulename"]==null) {
            $_POST["rulename"] = "Active Directory Offloading";
        }
        $_POST["uuid"]=time();

    }

    foreach ($_POST as $key=>$val){
        $add_fields[]="`$key`";
        $add_values[]="'$val'";
    }
    $sql="INSERT INTO dnsdist_rules (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."<hr>$sql";return false;}

    if($wizard_protection<>null){
        $GLOBALS["CLASS_SOCKETS"]->getGoFramework("exec.dnsdist.php --template7 $wizard_protection");
    }
    if($wizard_cleaning<>null){
        $GLOBALS["CLASS_SOCKETS"]->getGoFramework("exec.dnsdist.php --template8 $wizard_cleaning");
    }
    if($wizard_safesearch<>null){
        $GLOBALS["CLASS_SOCKETS"]->getGoFramework("exec.dnsdist.php --template9 $wizard_cleaning");
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LAST_DNSFW_POSTED_RULE",$_POST["uuid"]);
    admin_tracks_post("Create a new DNS Firewall rule");
    return true;

}

function rule_delete($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM dnsdist_rules WHERE ID='$ID'");
    return true;
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
    if(!isset($resolv->MainArray["DNS3"])){$resolv->MainArray["DNS3"]="";}

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

function count_js():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/listrules"));
    if(!property_exists($json,"Info")){return false;}
    $f=array();
    header("content-type: application/x-javascript");
        foreach ($json->Info as $RID=>$hits){
            $status = $tpl->_ENGINE_parse_body("<span class='label label-primary'>{active2}</span>");
            if($hits==0){
                $status = $tpl->_ENGINE_parse_body("<span class='label label-default'>{active2}</span>");
            }
            $statusEnc=base64_encode($status);
            $hitsText=$tpl->FormatNumber($hits);
            $f[]="if(document.getElementById('RR-$RID')){";
            $f[]="document.getElementById('RR-$RID').innerHTML='$hitsText';";
            $f[]="}";
            $f[]="if(document.getElementById('RA-$RID')){";
            $f[]="document.getElementById('RA-$RID').innerHTML=base64_decode('$statusEnc');";
            $f[]="}";

        }

    echo @implode("\n",$f);
        return true;
}

