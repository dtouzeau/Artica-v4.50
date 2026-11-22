<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Verrou Artica Meta (politique dnsfirewall) : regles Meta en lecture seule
include_once(dirname(__FILE__)."/ressources/class.dnsfirewall.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : enable-js/filltable/rule-delete-js/replace-rule etaient recopies sans encodage dans la page (XSS). Ce sont des ID de regle.
foreach(array("enable-js","filltable","rule-delete-js","replace-rule") as $k){if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}}

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
if(isset($_POST["dacl-delete"])){dacl_delete_save();exit;}

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
    // To be filled
    return true;
    $ACCESSEnabled=0;
    $tpl=new template_admin();
    $uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_websites}");
    $trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
    $ID=$_GET["filltable"];
    $ruleData=acls_api_get("/dns/dnsdist/rules/".intval($ID));
    $ligne=array();
    if(is_array($ruleData)){
        $ligne["ID"]=$ruleData["id"];
        $ligne["enabled"]=$ruleData["enabled"];
        $ligne["ruletype"]=$ruleData["ruleType"];
        $ligne["rulename"]=$ruleData["ruleName"];
        $ligne["rulevalue"]=$ruleData["ruleValue"];
        $ligne["checkName"]=$ruleData["checkName"];
        $ligne["checkInterval"]=$ruleData["checkInterval"];
        $ligne["maxCheckFailures"]=$ruleData["maxCheckFailures"];
        $ligne["checkTimeout"]=$ruleData["checkTimeout"];
        $ligne["dns_caches"]=$ruleData["dnsCaches"];
        $ligne["caches"]=$ruleData["caches"];
        $ligne["uuid"]=$ruleData["uuid"];
        $ligne["simpledomains"]=$ruleData["simpleDomains"];
        $ligne["uuids"]=$ruleData["uuids"];
        $ligne["useClientSubnet"]=intval($ruleData["useClientSubnet"] ?? 0);
        $ligne["fwobject"]=$ruleData["fwobject"] ?? "";
    }
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
    $ID=intval($_GET["rule-delete-js"]);
    $tpl=new template_admin();
    if(!dacl_can_write()){echo "alert(".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")).");";return;}
    // Regle Meta : refus avant tout appel, la ligne reste affichee.
    if(dnsfw_rule_is_meta($ID)){dnsfw_meta_refuse_js();return;}
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    $name=is_array($ruleData)?(string)($ruleData["ruleName"] ?? ""):"";
    if($name===""){$name="#$ID";}
    // Confirmation (plus de suppression au premier clic) ; la suppression part en POST.
    $tpl->js_confirm_delete(htmlspecialchars($name,ENT_QUOTES),"dacl-delete",$ID,"$('#acl-$ID').remove();RefreshDNSDistRules();");
}
function dacl_delete_save():bool{
    $tpl=new template_admin();
    if(!dacl_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["dacl-delete"]);
    if($ID<=0){echo $tpl->post_error("rule not found");return false;}
    if(dnsfw_rule_is_meta($ID)){return dnsfw_meta_refuse_post();}
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    $name=is_array($ruleData)?(string)($ruleData["ruleName"] ?? ""):"";
    list($ok,$err)=rule_delete($ID);
    // Le refus du demon (423 pour une regle Meta, erreur d'ecriture) ne retire pas la ligne.
    if(!$ok){echo $tpl->post_error((string)$err);return false;}
    admin_tracks("Delete DNS Firewall rule $ID $name");
    return true;
}
function rule_enable():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    header("content-type: application/x-javascript");
    if(!dacl_can_write()){echo "alert(".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")).");";return false;}
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(!is_array($ruleData)){echo "alert('rule not found');";return false;}
    if(dnsfw_meta_rule($ruleData)){return dnsfw_meta_refuse_js();}
    $enabled=intval($ruleData["enabled"])==0?1:0;
    list($ok,$err)=acls_api_send("PUT","/dns/dnsdist/rules/$ID",["enabled"=>$enabled]);
    if(!$ok){echo "alert(".json_encode($tpl->javascript_parse_text((string)$err)).");";return false;}
    admin_tracks("Set DNS Firewall rule $ID enabled=$enabled");
    $on=$enabled==1?"true":"false";
    $off=$enabled==1?"false":"true";
    echo "(function(){var r=document.getElementById('acl-$ID');if(r){r.classList.toggle('dacl-off',$off);}";
    echo "var s=document.getElementById('dacl-sw-$ID');if(s){s.setAttribute('aria-checked','$on');}})();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/flush");
    return true;
}
function change_method_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ch-method-js"]);
    // Ce nom finit dans des URL et dans du JavaScript execute: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    $rulename=is_array($ruleData) ? $ruleData["ruleName"] : "";
    $tpl->js_dialog2("{rule}: {change_method} $rulename","$page?ch-method-popup=$ID&function=$function");
    return true;
}
function change_method_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ch-method-popup"]);
    // Ce nom finit dans des URL et dans du JavaScript execute: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $functionenc=urlencode($function);
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    $ruletype=is_array($ruleData) ? intval($ruleData["ruleType"]) : 0;
    if(dnsfw_meta_rule($ruleData)){
        dnsfw_meta_lock_form($tpl);
        echo $tpl->_ENGINE_parse_body(dnsfw_meta_banner($tpl,true,"{dnsfw_meta_row_locked}"));
        return false;
    }

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
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(dnsfw_meta_rule($ruleData)){return dnsfw_meta_refuse_post();}
    $ruletype=is_array($ruleData) ? intval($ruleData["ruleType"]) : 0;
    $rulename=is_array($ruleData) ? $ruleData["ruleName"] : "";

    list($ok,$err)=acls_api_send("PUT","/dns/dnsdist/rules/$ID",["ruleType"=>$ruletype2]);
    if(!$ok){
        $tpl->post_error($err);
        return false;
    }
    admin_tracks("Change DNS Firewall $rulename rule method from type $ruletype to $ruletype2");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/flush");
    return true;
}

function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    // Ce nom finit dans des URL et dans du JavaScript execute: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $ID=intval($_GET["rule-id-js"]);
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    $rulename=is_array($ruleData) ? $ruleData["ruleName"] : "";
    $ruletype=is_array($ruleData) ? $ruleData["ruleType"] : 0;
    $tpl->js_dialog("{rule}: $ID $rulename/$ruletype","$page?rule-tabs=$ID&function=$function");
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
    if(dnsfw_rule_is_meta($ID)){return dnsfw_meta_refuse_post();}
    $cache_settings=base64_encode(serialize($_POST));
    list($ok,$err)=acls_api_send("PUT","/dns/dnsdist/rules/$ID",["dnsCaches"=>$cache_settings]);
    if(!$ok){$tpl->post_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/flush");
    return true;
}

function rule_safesearch():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-safesearch"]);
    // Ce nom finit dans des URL et dans du JavaScript execute: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(!is_array($ruleData)){echo $tpl->div_error("rule not found");return false;}
    $safe_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ruleData["dnsCaches"]);
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
    $ID=intval($_POST["gbdbid"]);
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(!is_array($ruleData)){$tpl->post_error("rule not found");return false;}
    if(dnsfw_meta_rule($ruleData)){return dnsfw_meta_refuse_post();}
    $safe_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ruleData["dnsCaches"]);
    foreach ($_POST as $key=>$val){
        $safe_settings[$key]=$val;
    }
    $cache_settings=base64_encode(serialize($safe_settings));
    list($ok,$err)=acls_api_send("PUT","/dns/dnsdist/rules/$ID",["dnsCaches"=>$cache_settings]);
    if(!$ok){$tpl->post_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/flush");
    return true;
}
function rule_cache(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-cache"]);
    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(!is_array($ruleData)){echo $tpl->div_error("rule not found");return;}
    $cache_settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ruleData["dnsCaches"]);


    if(!isset( $cache_settings["cache_enable"])){ $cache_settings["cache_enable"]=0;}
    if(!isset( $cache_settings["MaxRecords"])){ $cache_settings["MaxRecords"]=10000;}
    if(!isset( $cache_settings["maxTTL"])){ $cache_settings["maxTTL"]=86400;}
    if(!isset( $cache_settings["minTTL"])){ $cache_settings["minTTL"]=0;}
    if(!isset( $cache_settings["staleTTL"])){ $cache_settings["staleTTL"]=60;}
    if(!isset( $cache_settings["temporaryFailureTTL"])){ $cache_settings["temporaryFailureTTL"]=10;}


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
    $form[]=$tpl->field_numeric("temporaryFailureTTL", "{temporaryFailureTTL} ({seconds})",  $cache_settings["temporaryFailureTTL"]);

    echo $tpl->form_outside("{dns_cache}", @implode("\n", $form),null,"{apply}",
        "RefreshDNSDistRules()","AsDnsAdministrator");

}

function rule_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    // Ce nom finit dans des URL et dans du JavaScript execute: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $ID=intval($_GET["rule-tabs"]);
    $RefreshFunction=base64_encode("RefreshDNSDistRules()");
    $array["{rule}"]="$page?rule-settings=$ID&function=$function";
    $RefreshTable=base64_encode("LoadAjax('dnsdist-acl-table','$page?table=yes');");
    $array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=dnsdist_sqacllinks&RefreshTable=$RefreshTable&RefreshFunction=$RefreshFunction&function=$function";
    $array["{cache}"]="$page?rule-cache=$ID&function=$function";

    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    $ruletype=is_array($ruleData) ? intval($ruleData["ruleType"]) : 0;
    if(dnsfw_meta_rule($ruleData)){
        // Regle Meta : lecture seule, seul l'onglet de la regle (verrouille) est montre.
        echo $tpl->tabs_default(array("{rule}"=>$array["{rule}"]));
        return;
    }
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
    // Build iptables_main lookup map: id => ruleName
    $iptablesRaw=acls_api_get("/firewall/iptables-rules");
    $iptablesMap=array();
    if(is_array($iptablesRaw)){
        foreach($iptablesRaw as $ir){
            $iptablesMap[intval($ir["id"])]=$ir["ruleName"];
        }
    }

    $MAIN=array();
    // Enumerate all iptables rules; for each, fetch firewallfilter_sqacllinks (direction=0, src groups)
    foreach($iptablesMap as $aclid=>$rulename){
        $links=acls_api_get("/proxy/acls/sqlite/links?table=firewallfilter_sqacllinks&aclid=$aclid");
        if(!is_array($links)){ continue; }
        foreach($links as $lnk){
            if($lnk["groupType"]!="src"){ continue; }
            $gpid=intval($lnk["gpid"]);
            if(!intval($lnk["enabled"])){ continue; }
            $GroupName=$lnk["groupName"];
            $IpsetName="src-$aclid-$gpid";
            $MAIN[$IpsetName]="$rulename: $GroupName";
        }
    }
    return $MAIN;

}

function rule_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Ce nom finit dans des URL et dans du JavaScript execute: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $ID=intval($_GET["rule-settings"]);
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(!is_array($ruleData)){echo $tpl->div_error("rule not found");return false;}
    $isMetaRule=dnsfw_meta_rule($ruleData);
    if($isMetaRule){dnsfw_meta_lock_form($tpl);}
    $ligne=array(
        "ID"=>$ruleData["id"],
        "enabled"=>$ruleData["enabled"],
        "ruletype"=>$ruleData["ruleType"],
        "rulename"=>$ruleData["ruleName"],
        "rulevalue"=>$ruleData["ruleValue"],
        "checkName"=>$ruleData["checkName"],
        "checkInterval"=>$ruleData["checkInterval"],
        "maxCheckFailures"=>$ruleData["maxCheckFailures"],
        "checkTimeout"=>$ruleData["checkTimeout"],
        "dns_caches"=>$ruleData["dnsCaches"],
        "caches"=>$ruleData["caches"],
        "uuid"=>$ruleData["uuid"],
        "simpledomains"=>$ruleData["simpleDomains"],
        "uuids"=>$ruleData["uuids"],
        "useClientSubnet"=>intval($ruleData["useClientSubnet"] ?? 0),
        "fwobject"=>$ruleData["fwobject"] ?? "",
    );

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

    if($isMetaRule){
        // Pas de bouton "changer de methode" sur une regle Meta, et bandeau en tete.
        array_unshift($form,dnsfw_meta_banner($tpl,true,"{dnsfw_meta_row_locked}"));
        $tpl->FORM_LOCKED=true;
    }
    $html=$tpl->form_outside("{rule} {$ligne["rulename"]} <small>{$GLOBALS["DNS_RULES"][$ruletype]} </small>", $form,null,"{apply}",$jsafter,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function patch_table():bool{
    // Schema migrations handled by Go daemon at startup.
    return true;
}

function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $ID=intval($_POST["ID"]);
    unset($_POST["ID"]);
    if(dnsfw_rule_is_meta($ID)){return dnsfw_meta_refuse_post();}

    if(isset($_POST["checkInterval"])){
        if(intval($_POST["checkInterval"])<2){
            $languageF=$tpl->_ENGINE_parse_body("{check_interval} ({seconds})");
            echo (new template_admin())->post_error("$languageF < 2");
            return false;
        }
    }
    if(isset($_POST["checkInterval"])){
        if(intval($_POST["checkTimeout"])<3){
            $languageF=$tpl->_ENGINE_parse_body("{timeout} ({seconds})");
            echo (new template_admin())->post_error("$languageF < 3");
            return false;
        }
    }
    $intFields = array(
        "enabled",
        "ruleType",
        "checkInterval",
        "maxCheckFailures",
        "checkTimeout",
        "useClientSubnet"
    );
    // Map POST field names to API camelCase keys
    $fieldMap=array(
        "rulename"=>"ruleName","enabled"=>"enabled","ruletype"=>"ruleType",
        "rulevalue"=>"ruleValue","checkName"=>"checkName","checkInterval"=>"checkInterval",
        "maxCheckFailures"=>"maxCheckFailures","checkTimeout"=>"checkTimeout",
        "simpledomains"=>"simpleDomains","dns_caches"=>"dnsCaches","caches"=>"caches",
        "uuid"=>"uuid","uuids"=>"uuids","fwobject"=>"fwobject","useClientSubnet"=>"useClientSubnet",
    );
    $body=array();
    foreach ($_POST as $key=>$val){
        $apiKey = isset($fieldMap[$key]) ? $fieldMap[$key] : $key;

        if (in_array($apiKey, $intFields, true)) {
            $body[$apiKey] = intval($val);
        } else {
            $body[$apiKey] = $val;
        }
    }

    list($ok,$err)=acls_api_send("PUT","/dns/dnsdist/rules/$ID",$body);
    if(!$ok){echo $err;return false;}
    return admin_tracks_post("Edit DNS Firewall rule $ID");
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
    $rules=acls_api_get("/dns/dnsdist/rules");
    $rulename="";$ruletype=0;$ID=0;
    if(is_array($rules)){
        foreach($rules as $r){
            if(($r["uuid"]??"")== $LAST_DNSFW_POSTED_RULE){
                $rulename=$r["ruleName"];
                $ruletype=intval($r["ruleType"]);
                $ID=intval($r["id"]);
                break;
            }
        }
    }
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
    $ID=intval($_GET["newrule-popup2"]);
    $form[]=$tpl->field_hidden("newrule2", $ID);

    $ruleData=acls_api_get("/dns/dnsdist/rules/$ID");
    if(!is_array($ruleData)){return false;}
    $rulename=$ruleData["ruleName"];
    $ruletype=intval($ruleData["ruleType"]);
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
    acls_api_send("PUT","/dns/dnsdist/rules/$ID",["dnsCaches"=>$data]);
    $ruleData=acls_api_get("/dns/dnsdist/rules/".intval($ID));
    $uuid=is_array($ruleData)?($ruleData["uuid"]??null):null;
    if($uuid<>null){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/template10/".urlencode($uuid));
    }
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
    $ID=intval($_GET["acl-rule-move"]);
    $dir=intval($_GET["acl-rule-dir"]);
    if(!dacl_can_write()){echo "alert(".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")).");";return;}
    if(dnsfw_rule_is_meta($ID)){dnsfw_meta_refuse_js();return;}
    list($ok,$err)=acls_api_send("PUT","/dns/dnsdist/rules/$ID/move",["dir"=>$dir]);
    if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/flush");
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


// --- Liste des regles (portage Stitch, prefixe dacl_) ---------------------------
// Modele unique calcule une fois : regles de /dns/dnsdist/rules (Meta d'abord, comme le demon les
// evalue), liens d'objets (dnsdist_sqacllinks), compteurs cumules /dnsfw/service/listrules (une
// regle absente de la liste n'est pas chargee dans dnsdist), uptime /dnsfw/struct, regle par defaut.

function dacl_can_write():bool{
    $users=new usersMenus();
    return isWritter($users);
}

function dacl_h($v):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars((string)$v,ENT_QUOTES));
}

function dacl_t(string $token,$value):string{
    $tpl=new template_admin();
    return str_replace("%s",(string)$value,$tpl->_ENGINE_parse_body($token));
}

function dacl_dst_type(string $t):bool{
    return in_array($t,array("dst","doh","opendns","opendnsf"));
}

// Objets lies a une regle : sources (ce qui correspond) et destinations (serveurs DNS).
function dacl_links(int $ID):array{
    $out=array("src"=>array(),"dst"=>array());
    $rows=acls_api_get("/proxy/acls/sqlite/links?table=dnsdist_sqacllinks&aclid=$ID");
    if(!is_array($rows)){ return $out; }
    foreach($rows as $l){
        $t=(string)($l["groupType"] ?? "");
        $o=array("name"=>(string)($l["groupName"] ?? ""),"gid"=>intval($l["groupID"] ?? 0),
            "neg"=>intval($l["negation"] ?? 0)==1,"type"=>$t);
        if(dacl_dst_type($t)){ $out["dst"][]=$o; }else{ $out["src"][]=$o; }
    }
    return $out;
}

function dacl_duration(int $sec):string{
    if($sec<=0){ return "0 s"; }
    if($sec<120){ return "$sec s"; }
    if($sec<7200){ return round($sec/60)." min"; }
    if($sec<172800){ return round($sec/3600)." h"; }
    return round($sec/86400)." d";
}

function dacl_cache_line(array $ligne):string{
    $c=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);
    if(!is_array($c) || intval($c["cache_enable"] ?? 0)!=1){ return ""; }
    $n=number_format(intval($c["MaxRecords"] ?? 10000),0,".",",");
    return dacl_t("{dacl_opt_cache}",$n)." ".dacl_duration(intval($c["minTTL"] ?? 0))." &ndash; ".dacl_duration(intval($c["maxTTL"] ?? 86400));
}

function dacl_check_line(string $name,int $interval,int $timeout,int $failures):string{
    $s=dacl_t("{dacl_opt_check}","<span class='dacl-mono'>".dacl_h($name)."</span>");
    $s=str_replace(array("%i","%t","%f"),array($interval,$timeout,$failures),$s);
    return $s;
}

function dacl_rule(array $r,array $hits,bool $hitsKnown):array{
    $ID=intval($r["id"]);
    $ligne=array(
        "ID"=>$ID,"enabled"=>$r["enabled"],"ruletype"=>$r["ruleType"],"rulename"=>$r["ruleName"],
        "rulevalue"=>$r["ruleValue"],"checkName"=>$r["checkName"],"checkInterval"=>$r["checkInterval"],
        "maxCheckFailures"=>$r["maxCheckFailures"],"checkTimeout"=>$r["checkTimeout"],
        "dns_caches"=>$r["dnsCaches"],"simpledomains"=>$r["simpleDomains"],"fwobject"=>$r["fwobject"] ?? "",
        "metaid"=>(string)($r["metaid"] ?? ""),
    );
    $m=array("id"=>$ID,"name"=>(string)$r["ruleName"],"enabled"=>intval($r["enabled"])==1,"type"=>intval($r["ruleType"]),
        "meta"=>dnsfw_meta_rule($ligne),"edns"=>intval($r["useClientSubnet"] ?? 0)==1,"attention"=>"",
        "target"=>"","options"=>array());
    $type=$m["type"];
    $links=dacl_links($ID);
    $m["src"]=$links["src"];
    $m["dst"]=$links["dst"];
    if($type==2 || $type==3 || $type==4 || $type==6 || $type==9){ $m["edns"]=false; }

    if(count($m["src"])==0){
        if(in_array($type,array(1,2,3,5))){ $m["attention"]="{dacl_att_noobj}"; }
    }

    if($type==1){
        if(count($m["dst"])==0){ if($m["attention"]===""){ $m["attention"]="{dacl_att_nodest}"; } }
        else{ $m["target"]=dacl_chips($m["dst"],"dacl-chip-dst"); }
    }elseif($type==2){
        $m["target"]="{dacl_t_refuse}";
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"))==1 && strlen((string)$ligne["fwobject"])>2){
            $m["target"].="<div class='dacl-opt'>{dacl_t_refuse_fw}</div>";
        }
    }elseif($type==3){
        if(trim((string)$ligne["rulevalue"])===""){ if($m["attention"]===""){ $m["attention"]="{dacl_att_novalue}"; } }
        else{ $m["target"]="CNAME <span class='dacl-mono'>".dacl_h($ligne["rulevalue"])."</span>"; }
    }elseif($type==4){
        $vals=array();
        foreach(explode(",",(string)$ligne["rulevalue"]) as $v){ $v=trim($v); if($v!==""){ $vals[]=$v; } }
        $parts=array();
        if(count($vals)){ $parts[]="A <span class='dacl-mono'>".dacl_h(implode(", ",$vals))."</span>"; }
        if(count($m["dst"])){ $parts[]=dacl_chips($m["dst"],"dacl-chip-dst"); }
        if(count($parts)==0){ if($m["attention"]===""){ $m["attention"]="{dacl_att_novalue}"; } }
        $m["target"]=implode(" ",$parts);
        if(trim((string)$ligne["simpledomains"])!==""){
            $m["options"][]="{dacl_opt_domains} <span class='dacl-mono'>".dacl_h($ligne["simpledomains"])."</span>";
        }
    }elseif($type==5){
        $m["target"]="{dacl_t_nocache}";
    }elseif($type==6){
        $m["target"]="{dacl_t_google}";
    }elseif($type==9){
        $c=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["dns_caches"]);
        if(!is_array($c)){ $c=array(); }
        $map=array("EnableGoogleSafeSearch"=>"Google","EnableBraveSafeSearch"=>"Brave","EnableDuckduckgoSafeSearch"=>"Duckduckgo",
            "EnableYandexSafeSearch"=>"Yandex","EnablePixabaySafeSearch"=>"Pixabay","EnableQwantSafeSearch"=>"Qwant",
            "EnableBingSafeSearch"=>"Bing","EnableYoutubeSafeSearch"=>"Youtube Strict","EnbaleYoutubeModerate"=>"Youtube Moderate");
        $safe=array();
        foreach($map as $k=>$label){ if(intval($c[$k] ?? 0)==1){ $safe[]=$label; } }
        if(count($safe)==0){ if($m["attention"]===""){ $m["attention"]="{dacl_att_nosafe}"; } }
        else{ $m["target"]=implode(", ",$safe); }
    }else{
        $m["target"]="<span class='dacl-muted'>{dacl_t_builtin}</span>";
    }

    if($type==1 || $type==6){
        $ligne=ChecksIntervalDefaults($ligne);
        $cl=dacl_cache_line($ligne);
        if($cl!==""){ $m["options"][]=$cl; }
        $m["options"][]=dacl_check_line((string)$ligne["checkName"],intval($ligne["checkInterval"]),intval($ligne["checkTimeout"]),intval($ligne["maxCheckFailures"]));
    }

    $m["loaded"]=$hitsKnown && array_key_exists($ID,$hits);
    $m["hits"]=$m["loaded"]?floatval($hits[$ID]):null;
    return $m;
}

function dacl_default_model(array $hits,bool $hitsKnown):array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $d=array();
    $nets=array();
    $restricts=acls_api_get("/dns/pdns-restricts");
    if(is_array($restricts)){
        foreach($restricts as $r){ $a=trim((string)($r["address"] ?? "")); if($a!==""){ $nets[]=$a; } }
    }
    if(count($nets)==0){ $nets=array("192.168.0.0/16","10.0.0.0/8","172.16.0.0/12"); }
    $d["nets"]=$nets;
    $dns=array();
    $resolv=new resolv_conf();
    foreach(array("DNS1","DNS2","DNS3") as $k){
        $v=trim((string)($resolv->MainArray[$k] ?? ""));
        if($v!=="" && $v!=="127.0.0.1"){ $dns[]=$v; }
    }
    $d["dns"]=$dns;
    // Meme calcul qu'articarest (Acls.go) : Mo x 1024 x 1024 / 512, defaut 300 Mo.
    $size=intval($s->GET_INFO("UnBoundCacheSize"));
    if($size<=0){ $size=300; }
    $d["cache"]=$size*2048;
    $min=intval($s->GET_INFO("UnBoundCacheMinTTL")); if($min<0){ $min=0; }
    $max=intval($s->GET_INFO("UnBoundCacheMAXTTL")); if($max==0){ $max=172800; } if($max<0){ $max=0; }
    $d["min"]=$min; $d["max"]=$max;
    $cn=trim((string)$s->GET_INFO("DNSDistCheckName")); if($cn===""){ $cn="a.root-servers.net"; }
    $ci=intval($s->GET_INFO("DNSDistCheckInterval")); if($ci<2){ $ci=2; }
    $ct=intval($s->GET_INFO("DNSDistCheckTimeout")); if($ct<3){ $ct=3; }
    $cf=intval($s->GET_INFO("DNSDistMaxCheckFailures")); if($cf<=0){ $cf=3; }
    $d["check"]=dacl_check_line($cn,$ci,$ct,$cf);
    $d["loaded"]=$hitsKnown && array_key_exists(0,$hits);
    $d["hits"]=$d["loaded"]?floatval($hits[0]):null;
    return $d;
}

function dacl_model():array{
    if(isset($GLOBALS["DACL_MODEL"])){ return $GLOBALS["DACL_MODEL"]; }
    $hits=array();
    $hitsKnown=false;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/listrules"));
    if(is_object($json) && !empty($json->Status) && property_exists($json,"Info") && (is_object($json->Info) || is_array($json->Info))){
        $hitsKnown=true;
        foreach($json->Info as $RID=>$n){ $hits[intval($RID)]=floatval($n); }
    }
    $all=acls_api_get("/dns/dnsdist/rules");
    if(!is_array($all)){ $all=array(); }
    $meta=array(); $local=array();
    foreach($all as $r){
        if(!is_array($r) || !isset($r["id"])){ continue; }
        $x=dacl_rule($r,$hits,$hitsKnown);
        if($x["meta"]){ $meta[]=$x; }else{ $local[]=$x; }
    }
    $rules=array_merge($meta,$local);
    $pos=0;
    foreach($rules as $i=>$x){ $pos++; $rules[$i]["pos"]=$pos; }

    $m=array("rules"=>$rules,"hits_known"=>$hitsKnown,"has_meta"=>count($meta)>0);
    $m["default"]=dacl_default_model($hits,$hitsKnown);
    $total=0;
    foreach($hits as $n){ $total+=$n; }
    $m["total"]=$hitsKnown?$total:null;
    $m["uptime"]=null;
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/struct"));
    if(is_object($st) && !empty($st->Status) && isset($st->Info->statistics->uptime) && is_numeric($st->Info->statistics->uptime)){
        $m["uptime"]=intval($st->Info->statistics->uptime);
    }
    $c=array("enabled"=>0,"disabled"=>0,"loaded"=>0,"pending"=>0,"attention"=>0);
    foreach($rules as $x){
        if($x["enabled"]){ $c["enabled"]++; }else{ $c["disabled"]++; }
        if($x["enabled"] && $x["loaded"]){ $c["loaded"]++; }
        if($x["enabled"] && !$x["loaded"]){ $c["pending"]++; }
        if($x["enabled"] && $x["attention"]!==""){ $c["attention"]++; }
    }
    $m["c"]=$c;
    $GLOBALS["DACL_MODEL"]=$m;
    return $m;
}

// --- Petits rendus ---------------------------------------------------------------

function dacl_fmt($v):string{
    if($v===null){ return "&mdash;"; }
    return number_format($v,0,".",",");
}

function dacl_chips(array $objs,string $cls="dacl-chip-src"):string{
    $jsafter=base64_encode("RefreshDNSDistRules()");
    $h=array();
    foreach($objs as $o){
        $neg=$o["neg"]?"<span class='dacl-not'>{dacl_not}</span> ":"";
        $name=dacl_h($o["name"]);
        if($o["gid"]>0){
            $h[]="<button type='button' class='dacl-chip $cls' title='{dacl_open_object}' OnClick=\"Loadjs('fw.rules.items.php?groupid={$o["gid"]}&js-after=$jsafter',true);\">$neg$name</button>";
        }else{
            $h[]="<span class='dacl-chip $cls'>$neg$name</span>";
        }
    }
    return "<div class='dacl-chips'>".implode("",$h)."</div>";
}

function dacl_action_pill(int $type):string{
    $map=array(
        1=>array("fa-solid fa-scale-balanced","dacl-a-teal","{load_balance_dns_action}"),
        2=>array("fa-solid fa-ban","dacl-a-red","{refuse_to_resolve}"),
        3=>array("fa-solid fa-reply","dacl-a-purple","{SpoofCNAMEAction}"),
        4=>array("fa-solid fa-reply","dacl-a-purple","{SpoofAction}"),
        5=>array("fa-solid fa-database","dacl-a-grey","{do_not_cache}"),
        6=>array("fa-brands fa-google","dacl-a-blue","{us_google_dns_gdmains}"),
        7=>array("fa-solid fa-shield-halved","dacl-a-green","{web_browsing_protection}"),
        8=>array("fa-solid fa-broom","dacl-a-green","{web_browsing_cleaning}"),
        9=>array("fa-solid fa-child-reaching","dacl-a-green","SafeSearch(s)"),
        10=>array("fa-solid fa-sitemap","dacl-a-blue","Active Directory Offloading"),
    );
    if(!isset($map[$type])){ return "<span class='dacl-action dacl-a-grey'>?</span>"; }
    list($ico,$cls,$label)=$map[$type];
    return "<span class='dacl-action $cls'><i class='$ico'></i><span>$label</span></span>";
}

function dacl_requests_html($hits,$total):string{
    $pct=0;
    if($total!==null && $total>0 && $hits!==null){ $pct=round($hits*100/$total,1); }
    $w=max(0,min(100,$pct));
    $share=($total!==null && $total>0)?dacl_t("{dacl_share}",$pct):"";
    return "<div class='dacl-req'>".dacl_fmt($hits)."</div><div class='dacl-bar' role='img' aria-label='$pct%'><span style='width:$w%'></span></div><div class='dacl-share'>$share</div>";
}

// --- Widgets ---------------------------------------------------------------------

function dacl_kpi(string $ico,string $tone,string $label,string $value,string $foot):string{
    return "<div class='dacl-kpi $tone'><span class='dacl-kico'><i class='$ico'></i></span>"
        ."<div class='dacl-kt'><div class='dacl-klabel'>$label</div><div class='dacl-kval'>$value</div><div class='dacl-kfoot'>$foot</div></div></div>";
}

function dacl_kpi_rules(array $m):string{
    $c=$m["c"];
    $total=$c["enabled"]+$c["disabled"];
    return dacl_kpi("fa-solid fa-list-check","","{dacl_k_rules}",dacl_t("{dacl_k_rules_v}",$c["enabled"]),
        dacl_t("{dacl_k_rules_f}",$total).($c["disabled"]>0?" &middot; ".dacl_t("{dacl_k_disabled}",$c["disabled"]):""));
}

function dacl_kpi_loaded(array $m):string{
    $c=$m["c"];
    if(!$m["hits_known"]){
        return dacl_kpi("fa-solid fa-server","dacl-k-warn","{dacl_k_loaded}","&mdash;","{dacl_k_unreachable}");
    }
    $tone=$c["pending"]>0?"dacl-k-warn":"";
    $foot=$c["pending"]>0?dacl_t("{dacl_k_pending}",$c["pending"]):"{dacl_k_uptodate}";
    return dacl_kpi("fa-solid fa-server",$tone,"{dacl_k_loaded}",dacl_t("{dacl_k_loaded_v}",$c["loaded"]),$foot);
}

function dacl_kpi_requests(array $m):string{
    $foot=$m["uptime"]!==null?dacl_t("{dacl_k_uptime}",dacl_duration($m["uptime"])):"";
    return dacl_kpi("fa-solid fa-chart-simple","","{dacl_k_requests}",dacl_fmt($m["total"]),$foot);
}

function dacl_kpi_attention(array $m):string{
    $n=$m["c"]["attention"];
    if($n==0){ return dacl_kpi("fa-solid fa-circle-check","","{dacl_k_attention}","{dacl_none}","{dacl_k_att_none}"); }
    return dacl_kpi("fa-solid fa-triangle-exclamation","dacl-k-warn","{dacl_k_attention}",dacl_t("{dacl_k_att_v}",$n),"{dacl_k_att_f}");
}

function dacl_switch(array $x,bool $write):string{
    $page=CurrentPageName();
    $on=$x["enabled"]?"true":"false";
    $lbl=dacl_h($x["name"]);
    if($x["meta"] || !$write){
        $why=$x["meta"]?"{dacl_meta_locked}":"{dacl_no_privs}";
        return "<button type='button' class='dacl-sw' role='switch' aria-checked='$on' aria-label='$lbl' title='$why' disabled><span></span></button>";
    }
    return "<button type='button' class='dacl-sw' id='dacl-sw-{$x["id"]}' role='switch' aria-checked='$on' aria-label='$lbl' OnClick=\"Loadjs('$page?enable-js={$x["id"]}');\"><span></span></button>";
}

function dacl_actions(array $x,bool $write,string $function):string{
    $page=CurrentPageName();
    $ID=$x["id"];
    $h=array();
    if($x["hits"]!==null && $x["hits"]>0){
        $h[]="<button type='button' class='ak-act' aria-label='{dacl_events}' title='{dacl_events}' OnClick=\"Loadjs('fw.dnsdist.logs.php?rule-js=$ID');\"><i class='fa-solid fa-magnifying-glass'></i></button>";
    }else{
        $h[]="<button type='button' class='ak-act' aria-label='{dacl_events}' title='{dacl_no_events}' disabled><i class='fa-solid fa-magnifying-glass'></i></button>";
    }
    if($x["meta"] || !$write){
        $h[]="<span class='dacl-lock' title='".($x["meta"]?"{dacl_meta_locked}":"{dacl_no_privs}")."'><i class='fa-solid fa-lock'></i></span>";
        return "<div class='ak-acts'>".implode("",$h)."</div>";
    }
    $h[]="<button type='button' class='ak-act' aria-label='{dacl_move_up}' title='{dacl_move_up}' OnClick=\"Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');\"><i class='fa-solid fa-arrow-up'></i></button>";
    $h[]="<button type='button' class='ak-act' aria-label='{dacl_move_down}' title='{dacl_move_down}' OnClick=\"Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');\"><i class='fa-solid fa-arrow-down'></i></button>";
    $h[]="<button type='button' class='ak-act ak-danger' aria-label='{dacl_delete}' title='{dacl_delete}' OnClick=\"Loadjs('$page?rule-delete-js=$ID&function=$function');\"><i class='fa-solid fa-trash'></i></button>";
    return "<div class='ak-acts'>".implode("",$h)."</div>";
}

function dacl_row(array $x,array $m,bool $write,string $function):string{
    $page=CurrentPageName();
    $ID=$x["id"];
    $cls="dacl-row";
    if(!$x["enabled"]){ $cls.=" dacl-off"; }
    if($x["enabled"] && $x["attention"]!==""){ $cls.=" dacl-att"; }
    if($x["meta"]){ $cls.=" dacl-meta"; }
    $name=dacl_h($x["name"]);
    $js="Loadjs('$page?rule-id-js=$ID&function=$function')";
    $title=$x["meta"]?"{view}":"{click_to_edit}";
    $badges=array();
    if($x["meta"]){ $badges[]="<span class='dacl-badge dacl-b-meta'><i class='fa-solid fa-lock'></i> Artica Meta</span>"; }
    if($x["edns"]){ $badges[]="<span class='dacl-badge'>EDNS</span>"; }
    if(!$x["enabled"]){ $badges[]="<span class='dacl-badge dacl-b-off'>{dacl_disabled}</span>"; }
    if($x["enabled"] && $x["attention"]!==""){ $badges[]="<span class='dacl-badge dacl-b-att'><i class='fa-solid fa-triangle-exclamation'></i> {dacl_needs_attention}</span>"; }

    if(count($x["src"])){ $matches=dacl_chips($x["src"]); }
    elseif(in_array($x["type"],array(6,9))){ $matches="<div class='dacl-chips'><span class='dacl-chip dacl-chip-all'><i class='fa-solid fa-globe'></i> {dacl_everyone}</span></div>"; }
    else{ $matches="<span class='dacl-muted'>{dacl_no_object}</span>"; }

    $target=$x["target"];
    if($x["attention"]!==""){ $target.="<div class='dacl-attline'><i class='fa-solid fa-circle-exclamation'></i> ".$x["attention"]."</div>"; }
    foreach($x["options"] as $o){ $target.="<div class='dacl-opt'>$o</div>"; }

    if($x["loaded"]){ $req=dacl_requests_html($x["hits"],$m["total"]); }
    elseif(!$x["enabled"]){ $req="<span class='dacl-muted'>&mdash;</span>"; }
    elseif(!$m["hits_known"]){ $req="<span class='dacl-muted' title='{dacl_k_unreachable}'>&mdash;</span>"; }
    else{ $req="<span class='dacl-pill-nl' title='{dacl_not_loaded_help}'><i class='fa-solid fa-circle-pause'></i> {dacl_not_loaded}</span>"; }

    return "<tr class='$cls' id='acl-$ID'>"
        ."<td class='dacl-c-pos'><span class='dacl-pos'>{$x["pos"]}</span></td>"
        ."<td class='dacl-c-name'><a href='javascript:void(0)' class='dacl-name' title='$title' OnClick=\"$js\">$name</a><div class='dacl-badges'>".implode("",$badges)."</div></td>"
        ."<td class='dacl-c-match'>$matches</td>"
        ."<td class='dacl-c-action'>".dacl_action_pill($x["type"])."</td>"
        ."<td class='dacl-c-target'>$target</td>"
        ."<td class='dacl-c-req' id='RR-$ID'>$req</td>"
        ."<td class='dacl-c-sw'>".dacl_switch($x,$write)."</td>"
        ."<td class='dacl-c-act'>".dacl_actions($x,$write,$function)."</td>"
        ."</tr>";
}

function dacl_default_row(array $m):string{
    $d=$m["default"];
    $nets=array();
    foreach($d["nets"] as $n){ $nets[]="<span class='dacl-chip dacl-chip-net'>".dacl_h($n)."</span>"; }
    if(count($d["dns"])){
        $dns=array();
        foreach($d["dns"] as $n){ $dns[]="<span class='dacl-chip dacl-chip-dst'>".dacl_h($n)."</span>"; }
        $target="<div class='dacl-chips'>".implode("",$dns)."</div>";
    }else{
        $target="<div class='dacl-attline'><i class='fa-solid fa-circle-exclamation'></i> {dacl_default_nodns}</div>";
    }
    $target.="<div class='dacl-opt'>".dacl_t("{dacl_opt_cache}",number_format($d["cache"],0,".",","))." ".dacl_duration($d["min"])." &ndash; ".dacl_duration($d["max"])."</div>";
    $target.="<div class='dacl-opt'>".$d["check"]."</div>";
    if($d["loaded"]){ $req=dacl_requests_html($d["hits"],$m["total"]); }
    else{ $req="<span class='dacl-muted'>&mdash;</span>"; }
    $ev=($d["hits"]!==null && $d["hits"]>0)
        ?"<button type='button' class='ak-act' aria-label='{dacl_events}' title='{dacl_events}' OnClick=\"Loadjs('fw.dnsdist.logs.php?rule-js=0');\"><i class='fa-solid fa-magnifying-glass'></i></button>"
        :"<button type='button' class='ak-act' aria-label='{dacl_events}' title='{dacl_no_events}' disabled><i class='fa-solid fa-magnifying-glass'></i></button>";
    return "<tbody class='dacl-default'><tr id='acl-0'>"
        ."<td class='dacl-c-pos'><span class='dacl-pos dacl-pos-def' title='{dacl_default_last}'><i class='fa-solid fa-anchor'></i></span></td>"
        ."<td class='dacl-c-name'><span class='dacl-name-def'>{dacl_default}</span><div class='dacl-sub'>{dacl_default_last}</div></td>"
        ."<td class='dacl-c-match'><div class='dacl-sub'>{dacl_allowed_networks}</div><div class='dacl-chips'>".implode("",$nets)."</div></td>"
        ."<td class='dacl-c-action'>".dacl_action_pill(1)."</td>"
        ."<td class='dacl-c-target'>$target</td>"
        ."<td class='dacl-c-req' id='RR-0'>$req</td>"
        ."<td class='dacl-c-sw'><span class='dacl-always' title='{dacl_default_locked}'>{dacl_always_on}</span></td>"
        ."<td class='dacl-c-act'><div class='ak-acts'>$ev<span class='dacl-lock' title='{dacl_default_locked}'><i class='fa-solid fa-thumbtack'></i></span></div></td>"
        ."</tr></tbody>";
}

function dacl_empty_row(bool $write,string $function):string{
    $page=CurrentPageName();
    $btn=$write?"<button type='button' class='ak-btn ak-btn-sm' OnClick=\"Loadjs('$page?newrule-js=yes&function=$function',true);\"><i class='fa-solid fa-plus'></i> {new_rule}</button>":"";
    return "<tr><td colspan='8'><div class='dacl-empty'><i class='fa-solid fa-list'></i><span>{dacl_empty}</span>$btn</div></td></tr>";
}

function dacl_style():string{
    if(isset($GLOBALS["DACL_STYLE"])){ return ""; }
    $GLOBALS["DACL_STYLE"]=true;
    return "<style>
#dacl-root{display:flex;flex-direction:column;gap:16px;margin-top:10px}
#dacl-root #dnsfw-meta-banner{margin:0!important}
#dacl-root .dacl-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
#dacl-root .dacl-kpi{display:flex;align-items:center;gap:12px;min-height:72px;padding:12px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-sizing:border-box;min-width:0}
#dacl-root .dacl-kico{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;flex:0 0 40px;border-radius:10px;background:#f1f5f9;color:#334155;font-size:17px}
#dacl-root .dacl-kt{min-width:0}
#dacl-root .dacl-klabel{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
#dacl-root .dacl-kval{font-size:17px;font-weight:600;color:#0f172a;font-variant-numeric:tabular-nums}
#dacl-root .dacl-kfoot{font-size:12px;color:#64748b}
#dacl-root .dacl-k-warn{background:#fffbeb;border-color:#fde68a}
#dacl-root .dacl-k-warn .dacl-kico{background:#fef3c7;color:#b45309}
#dacl-root .dacl-k-warn .dacl-kfoot{color:#92400e}
#dacl-root .dacl-guide{display:flex;align-items:center;gap:8px;padding:12px 16px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb}
#dacl-root table.dacl-tbl{width:100%;border-collapse:collapse}
#dacl-root .dacl-tbl th{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;text-align:left;padding:10px 12px;border-bottom:1px solid #e5e7eb;white-space:nowrap}
#dacl-root .dacl-tbl td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:13px;color:#0f172a}
#dacl-root .dacl-row.dacl-off td{opacity:.55}
#dacl-root .dacl-row.dacl-off td.dacl-c-sw,#dacl-root .dacl-row.dacl-off td.dacl-c-act{opacity:1}
#dacl-root .dacl-row.dacl-att td:first-child{box-shadow:inset 4px 0 0 #d97706}
#dacl-root .dacl-row.dacl-att{background:#fffbeb}
#dacl-root .dacl-c-pos{width:44px;text-align:center}
#dacl-root .dacl-pos{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600}
#dacl-root .dacl-pos-def{background:#e2e8f0}
#dacl-root .dacl-c-name{min-width:150px;max-width:220px}
#dacl-root .dacl-name{font-size:14px;font-weight:600;color:#0f172a;text-decoration:none;overflow-wrap:anywhere}
#dacl-root .dacl-name:hover,#dacl-root .dacl-name:focus{color:var(--ak-fill,#0f766e);text-decoration:underline}
#dacl-root .dacl-name-def{font-size:14px;font-weight:600}
#dacl-root .dacl-sub{font-size:12px;color:#64748b;margin-top:2px}
#dacl-root .dacl-badges{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
#dacl-root .dacl-badge{display:inline-flex;align-items:center;gap:4px;padding:1px 7px;border-radius:6px;font-size:12px;font-weight:600;background:#f1f5f9;color:#475569}
#dacl-root .dacl-b-meta{background:#eff6ff;color:#1d4ed8}
#dacl-root .dacl-b-off{background:#f1f5f9;color:#64748b}
#dacl-root .dacl-b-att{background:#fef3c7;color:#92400e}
#dacl-root .dacl-chips{display:flex;flex-wrap:wrap;gap:4px}
#dacl-root .dacl-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;color:#334155;font-size:12px;font-weight:600;cursor:default;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#dacl-root button.dacl-chip{cursor:pointer}
#dacl-root button.dacl-chip:hover,#dacl-root button.dacl-chip:focus-visible{border-color:#94a3b8;background:#fff}
#dacl-root .dacl-chip-dst{background:#f0fdfa;border-color:#99f6e4;color:#115e59}
#dacl-root .dacl-chip-net{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-weight:500;background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a}
#dacl-root .dacl-chip-all{background:#f8fafc}
#dacl-root .dacl-not{color:#b91c1c;font-weight:700}
#dacl-root .dacl-action{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:8px;border:1px solid;font-size:12px;font-weight:600;max-width:220px;line-height:1.3}
#dacl-root .dacl-a-teal{background:#f0fdfa;border-color:#99f6e4;color:#0f766e}
#dacl-root .dacl-a-red{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
#dacl-root .dacl-a-purple{background:#f2f6f6;border-color:#bfd4d1;color:#005447}
#dacl-root .dacl-a-grey{background:#f8fafc;border-color:#e2e8f0;color:#475569}
#dacl-root .dacl-a-blue{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
#dacl-root .dacl-a-green{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
#dacl-root .dacl-c-target{min-width:220px}
#dacl-root .dacl-opt{font-size:12px;color:#64748b;margin-top:3px;line-height:1.45}
#dacl-root .dacl-attline{font-size:12px;font-weight:600;color:#b45309;margin-top:3px}
#dacl-root .dacl-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12px;overflow-wrap:anywhere}
#dacl-root .dacl-muted{color:#94a3b8}
#dacl-root .dacl-c-req{width:120px}
#dacl-root .dacl-req{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;font-variant-numeric:tabular-nums}
#dacl-root .dacl-bar{height:4px;width:96px;border-radius:4px;background:#e5e7eb;margin-top:4px;overflow:hidden}
#dacl-root .dacl-bar span{display:block;height:100%;background:var(--ak-fill,#0f766e);border-radius:4px}
#dacl-root .dacl-share{font-size:12px;color:#64748b;margin-top:2px}
#dacl-root .dacl-pill-nl{display:inline-flex;align-items:center;gap:5px;padding:2px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:600;white-space:nowrap}
#dacl-root .dacl-c-sw{width:70px;text-align:center}
#dacl-root .dacl-sw{position:relative;width:38px;height:22px;border:0;border-radius:999px;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
#dacl-root .dacl-sw span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
#dacl-root .dacl-sw[aria-checked=true]{background:var(--ak-fill,#0f766e)}
#dacl-root .dacl-sw[aria-checked=true] span{left:19px}
#dacl-root .dacl-sw[disabled]{opacity:.5;cursor:not-allowed}
#dacl-root .dacl-sw:focus-visible{outline:2px solid var(--ak-fill,#0f766e);outline-offset:2px}
#dacl-root .dacl-c-act{width:150px}
#dacl-root .dacl-lock{display:inline-flex;align-items:center;justify-content:center;width:28px;height:30px;color:#94a3b8}
#dacl-root .dacl-always{display:inline-block;padding:2px 8px;border-radius:6px;background:#f0fdfa;border:1px solid #99f6e4;color:#0f766e;font-size:12px;font-weight:600;white-space:nowrap}
#dacl-root .dacl-default td{background:#f8fafc;border-top:2px dashed #cbd5e1}
#dacl-root .dacl-empty{display:flex;align-items:center;gap:12px;padding:18px;color:#475569}
#dacl-root .dacl-empty>span{flex:1}
#dacl-root .dacl-legend{display:flex;flex-wrap:wrap;gap:14px;font-size:12px;color:#64748b}
#dacl-root .dacl-legend i{margin-right:4px}
@media (max-width:1200px){#dacl-root .dacl-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (prefers-reduced-motion:reduce){#dacl-root .dacl-sw,#dacl-root .dacl-sw span{transition:none}}
</style>";
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // $_GET["function"] part dans des OnClick et dans RefreshDNSDistRules() : liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $m=dacl_model();
    $write=dacl_can_write();

    $rules=$m["rules"];
    if(isset($_GET["search"]) && trim((string)$_GET["search"])!==""){
        $pat=strtolower(trim((string)$_GET["search"]));
        $rules=array_values(array_filter($rules,function($x) use($pat){
            if(strpos(strtolower($x["name"]),$pat)!==false){ return true; }
            foreach(array_merge($x["src"],$x["dst"]) as $o){ if(strpos(strtolower($o["name"]),$pat)!==false){ return true; } }
            return false;
        }));
    }

    $h=array();
    $h[]="<div id='dacl-root' class='ak'>";
    $h[]="<div class='dacl-kpis'>".dacl_kpi_rules($m).dacl_kpi_loaded($m).dacl_kpi_requests($m).dacl_kpi_attention($m)."</div>";
    $h[]="<div class='awc-wrap'><div class='awc-card'>";
    $h[]="<div class='dacl-guide'><i class='fa-solid fa-arrow-down-1-9'></i><span>{dacl_order_help}</span></div>";
    $h[]="<div class='awc-scroll'><table id='table-dns-fw-rules' class='dacl-tbl'>";
    $h[]="<thead><tr><th class='dacl-c-pos' title='{dacl_th_pos_help}'>#</th><th>{rulename}</th><th title='{dacl_th_match_help}'>{dacl_th_match}</th>"
        ."<th>{dacl_th_action}</th><th title='{dacl_th_target_help}'>{dacl_th_target}</th><th title='{dacl_th_req_help}'>{requests}</th>"
        ."<th class='dacl-c-sw'>{enabled}</th><th class='dacl-c-act' style='text-align:right'>{dacl_actions}</th></tr></thead>";
    $h[]="<tbody id='dnsfw-rows'>";
    foreach($rules as $x){ $h[]=dacl_row($x,$m,$write,$function); }
    if(count($rules)==0){ $h[]=dacl_empty_row($write,$function); }
    $h[]="</tbody>";
    $h[]=dacl_default_row($m);
    $h[]="</table></div>";
    $h[]="<div class='awc-foot'><span>".dacl_t("{dacl_showing}",count($rules))."</span>"
        ."<span class='dacl-legend'><span><i class='fa-solid fa-circle-check' style='color:#0f766e'></i>{dacl_lg_loaded}</span>"
        ."<span><i class='fa-solid fa-circle-pause' style='color:#b45309'></i>{dacl_not_loaded}</span>"
        ."<span><i class='fa-solid fa-lock' style='color:#1d4ed8'></i>Artica Meta</span></span>"
        ."<span class='awc-pg' id='dnsfw-pager'></span></div>";
    $h[]="</div></div></div>";

    $add="Loadjs('$page?newrule-js=yes&function=$function',true);";
    $jsrestart=$tpl->framework_buildjs("/dnsfw/service/php/restart","dnsdist.restart","dnsdist.restart.log",
        "progress-dnsdist-restart","RefreshDNSDistRules();");
    $topbuttons=array();
    if($write){
        $topbuttons[]=array($add,ico_plus,"{new_rule}");
        $topbuttons[]=array($jsrestart,ico_save,"{apply_rules}");
    }
    $topbuttons[]=array("Loadjs('$page?view-rules=yes');","fa-solid fa-magnifying-glass","{view_rules}");
    $topbuttons[]=array("RefreshDNSDistRules();","fa-solid fa-rotate","{dacl_reload}");
    $topbuttons[]=array("LoadAjaxSilent('MainContent','fw.dns.unbound.php');","fab fa-gripfire","{status}");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}&nbsp;&raquo;&nbsp;{DNS_ACLS}";
    $TINY_ARRAY["ICO"]="fas fa-list";
    $TINY_ARRAY["EXPL"]="{dacl_page_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    // table-dns-fw-rules est la SENTINELLE de ce rafraichisseur : il doit rester sur le <table>.
    $jsRefr=$tpl->RefreshInterval_Loadjs("table-dns-fw-rules",$page,"counts-js=yes");
    $refresh="LoadAjax('table-firewall-rules','$page?table=yes');";
    if($function!==""){ $refresh="if(typeof $function==='function'){ $function(); }"; }
    $js=array();
    $js[]="function RefreshDNSDistRules(){ $refresh }";
    $js[]=$jstiny;
    $js[]=$jsRefr;
    $js[]="NoSpinner();";
    // Script concatene APRES le parse (la regex des {jetons} lirait le corps des fonctions).
    $script="<script>".implode("\n",$js)."</script>";

    $body=$tpl->_ENGINE_parse_body(implode("\n",$h));
    // Bandeau Meta deja traduit et echappe par dnsfw_meta_banner() : insere apres le parse.
    $meta=dnsfw_meta_banner($tpl,$m["has_meta"],"{dnsfw_meta_managed_explain}");
    if($meta!==""){ $body=str_replace("<div id='dacl-root' class='ak'>","<div id='dacl-root' class='ak'>$meta",$body); }
    echo awc_table_style().dacl_style().$body.$script.awc_pager_js(50,"dnsfw-rows","dnsfw-pager");
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
    $tpl=new template_admin();
    $ruleData=acls_api_get("/dns/dnsdist/rules/".intval($ID));
    if(!is_array($ruleData)){return "rule not found";}
    $ligne=array(
        "ID"=>$ruleData["id"],"enabled"=>$ruleData["enabled"],"ruletype"=>$ruleData["ruleType"],
        "rulename"=>$ruleData["ruleName"],"rulevalue"=>$ruleData["ruleValue"],
        "checkName"=>$ruleData["checkName"],"checkInterval"=>$ruleData["checkInterval"],
        "maxCheckFailures"=>$ruleData["maxCheckFailures"],"checkTimeout"=>$ruleData["checkTimeout"],
        "dns_caches"=>$ruleData["dnsCaches"],"caches"=>$ruleData["caches"],"uuid"=>$ruleData["uuid"],
        "simpledomains"=>$ruleData["simpleDomains"],"uuids"=>$ruleData["uuids"],
        "fwobject"=>$ruleData["fwobject"] ?? "",
    );
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
        $gps=array();
        $links=acls_api_get("/proxy/acls/sqlite/links?table=dnsdist_sqacllinks&aclid=$ID");
        if(is_array($links)){
            foreach ($links as $lnk){
                $GroupType=$lnk["groupType"];
                $gid=intval($lnk["groupID"]);
                if($GroupType=="opendns"){
                    $REMOVEGRPS[$gid]=true;
                }
                if($GroupType=="opendnsf"){
                    $REMOVEGRPS[$gid]=true;
                }
                if($GroupType!="dst" && $GroupType!="doh" && $GroupType!="opendns" && $GroupType!="opendnsf"){
                    continue;
                }
                $GroupName=$tpl->utf8_encode($lnk["groupName"]);
                $href="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('fw.rules.items.php?groupid=$gid&js-after=$jsafter',true);\"
			style=\"text-decoration:underline;\">";
                $gps[]="<strong>$href$GroupName</strong></a>";
            }
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
                $ruleid=intval($tb[1]);
                $groupid=intval($tb[2]);
                $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
                $GroupName=is_array($grp) ? ($grp["groupName"] ?? "") : "";
                $iptablesRules=acls_api_get("/firewall/iptables-rules");
                $rulename="";
                if(is_array($iptablesRules)){
                    foreach($iptablesRules as $ir){
                        if(intval($ir["id"])===$ruleid){ $rulename=$ir["ruleName"]; break; }
                    }
                }
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

        $links4=acls_api_get("/proxy/acls/sqlite/links?table=dnsdist_sqacllinks&aclid=$ID");
        $IPClass=new IP();
        if(is_array($links4)){
            foreach ($links4 as $lnk4){
                if($lnk4["groupType"]!="dst"){ continue; }
                $gpid=intval($lnk4["gpid"]);
                $items=acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
                if(!is_array($items)){ continue; }
                foreach ($items as $itm){
                    $pattern=$itm["pattern"];
                    if(!$IPClass->isIPAddressOrRange($pattern)){continue;}
                    if(strpos($pattern,"/")>0){
                        $tt=explode("/",$pattern);
                        if($tt[1]<32){continue;}
                        $pattern=$tt[0];
                    }
                    $PP[]=$pattern;
                }
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
        $_POST["uuid"]=strval(time());
        $wizard_protection=$_POST["uuid"];
    }
    if($_POST["ruletype"]==8){
        $tpl=new template_admin();
        $_POST["checkName"]="redirector.googlevideo.com";
        $_POST["ruletype"]=2;
        if($_POST["rulename"]==null) {
            $_POST["rulename"] = $tpl->javascript_parse_text("{web_browsing_cleaning}");
        }
        $_POST["uuid"]=strval(time());
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
        $_POST["uuid"]=strval(time());
        $wizard_safesearch=$_POST["uuid"];
    }

    if($_POST["ruletype"]==10){
        $_POST["checkName"]="redirector.googlevideo.com";
        $_POST["ruletype"]=10;
        if($_POST["rulename"]==null) {
            $_POST["rulename"] = "Active Directory Offloading";
        }
        $_POST["uuid"]=strval(time());

    }

    // Map POST field names to API camelCase keys for POST body
    $fieldMap=array(
        "rulename"=>"ruleName","enabled"=>"enabled","ruletype"=>"ruleType",
        "rulevalue"=>"ruleValue","checkName"=>"checkName","checkInterval"=>"checkInterval",
        "maxCheckFailures"=>"maxCheckFailures","checkTimeout"=>"checkTimeout",
        "simpledomains"=>"simpleDomains","dns_caches"=>"dnsCaches","caches"=>"caches",
        "uuid"=>"uuid","uuids"=>"uuids","fwobject"=>"fwobject","useClientSubnet"=>"useClientSubnet",
    );

    $intFields = array(
        "enabled",
        "ruleType",
        "checkInterval",
        "maxCheckFailures",
        "checkTimeout",
        "useClientSubnet"
    );

    $body=array();
    foreach ($_POST as $key=>$val){
        $apiKey=isset($fieldMap[$key]) ? $fieldMap[$key] : $key;
        if (in_array($apiKey, $intFields, true)) {
            $body[$apiKey] = intval($val);
        } else {
            $body[$apiKey] = $val;
        }
    }
    list($ok,$newData)=acls_api_send("POST","/dns/dnsdist/rules",$body);
    if(!$ok){echo $newData;return false;}

    if($wizard_protection<>null){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/template7/".urlencode($wizard_protection));
    }
    if($wizard_cleaning<>null){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/template8/".urlencode($wizard_cleaning));
    }
    if($wizard_safesearch<>null){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/template9/".urlencode($wizard_safesearch));
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LAST_DNSFW_POSTED_RULE",$_POST["uuid"]);
    admin_tracks_post("Create a new DNS Firewall rule");
    return true;

}

function rule_delete($ID){
    list($ok,$err)=acls_api_send("DELETE","/dns/dnsdist/rules/".intval($ID));
    if($ok){$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/flush");}
    return array($ok,$err);
}

// La regle $ID a-t-elle ete ecrite par Meta (metaid non vide). Lecture en echec = false : le demon
// refuse de toute facon l'ecriture (423).
function dnsfw_rule_is_meta($ID):bool{
    $ID=intval($ID);
    if($ID<=0){return false;}
    return dnsfw_meta_rule(acls_api_get("/dns/dnsdist/rules/$ID"));
}


function count_js():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/listrules"));
    // property_exists() sur null est une TypeError sous PHP 8, et ce repondeur tourne toutes les N secondes.
    if(!is_object($json) || !property_exists($json,"Info")){return false;}
    header("content-type: application/x-javascript");
    $hits=array();
    $total=0;
    foreach($json->Info as $RID=>$n){$hits[intval($RID)]=floatval($n);$total+=floatval($n);}
    $f=array();
    foreach($hits as $RID=>$n){
        $enc=base64_encode(dacl_requests_html($n,$total));
        $f[]="if(document.getElementById('RR-$RID') && !document.querySelector('#acl-$RID.dacl-off')){document.getElementById('RR-$RID').innerHTML=base64_decode('$enc');}";
    }
    echo implode("\n",$f);
    return true;
}
function isWritter($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }
    if($users->AsDnsAdministrator){
        return true;
    }
    return false;
}