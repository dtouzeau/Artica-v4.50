<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["UnboundEDNS"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}

js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("EDNS","$page?popup=yes");
    return true;
}

function popup(){

    $tpl=new template_admin();
    $UnboundEDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNS"));
    $UnboundEDNSNetworks=explode("||",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNSNetworks"));
    $ClientSubnetAlwaysForward=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClientSubnetAlwaysForward"));
    $form[]=$tpl->field_checkbox("UnboundEDNS","{enable}",$UnboundEDNS,true);
    $form[]=$tpl->field_tags("UnboundEDNSNetworks","{frontends}",@implode(",",$UnboundEDNSNetworks));
    $form[]=$tpl->field_checkbox("ClientSubnetAlwaysForward","{ClientSubnetAlwaysForward}",$ClientSubnetAlwaysForward);

    $jsafter[]="LoadAjax('unbound-table-start','fw.dns.unbound.php?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafterText=@implode(";",$jsafter);
    $html=$tpl->form_outside("",$form,"","{apply}",$jsafterText,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $UnboundEDNSNetworks=str_replace(" ","||",$_POST["UnboundEDNSNetworks"]);
    $UnboundEDNSNetworks=str_replace(",","||",$UnboundEDNSNetworks);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEDNS",$_POST["UnboundEDNS"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEDNSNetworks",$UnboundEDNSNetworks);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClientSubnetAlwaysForward",$_POST["ClientSubnetAlwaysForward"]);



    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
    return admin_tracks_post("Saving EDNS feature in DNS Cache service");
}