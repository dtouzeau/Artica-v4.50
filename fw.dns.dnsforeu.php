<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["UseDNSForEUBackends"])){UseDNSForEUBackends();exit;}

js();

function js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog4("{UseDNSForEUBackends}","$page?popup=yes");
}

function popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $UseDNSForEUBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseDNSForEUBackends"));
    $Types[0] = "<strong>{inactive2}</strong><br>{feature_disabled}";
    $Types[1] = "<strong>{protective_resolution}</strong><br>{protective_resolution_explain}";
    $Types[2] = "<strong>{child_protection}</strong><br>{child_protection_explain}";
    $Types[3] = "<strong>{ads_protection}</strong><br>{dns4eu_ads_protection}";
    $Types[4] = "<strong>{child_protection} & {ads_protection}</strong><br>{dns4eu_adschild_protection}";
    $Types[5] = "<strong>{unfiltered_resolution}</strong><br>{unfiltered_resolution_explain}";
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "UseDNSForEUBackends", $UseDNSForEUBackends);
    echo $tpl->form_outside("", $form,"{UseDNSForEUBackends_explain}","{apply}","dialogInstance4.close();LoadAjax('dns-servers-main-table','fw.dns.servers.php?table2=yes');","AsDnsAdministrator");
    return true;
}
function UseDNSForEUBackends():bool{

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseDNSForEUBackends",$_POST["UseDNSForEUBackends"]);

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/php/restart");
    }
    if($UnboundEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
    }

    return admin_tracks("Set DNSForEU feature to {$_POST["UseDNSForEUBackends"]}");
}