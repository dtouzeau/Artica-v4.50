<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_POST["HostHeader"])){Save();exit;}

js();


function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["service"]);
    return $tpl->js_dialog5("ADFS {parameters}","$page?table-start=$ID");
}

function table_start():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["table-start"]);
	echo "<div id='adfs-$ID'></div>
	<script>LoadAjax('adfs-$ID','$page?table=$ID')</script>";
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $HostHeader=$_POST["HostHeader"];
    $AdfsForceRedirect=$_POST["AdfsForceRedirect"];
    $sock               = new socksngix($serviceid);
    $sock->SET_INFO("HostHeader",$HostHeader);
    $sock->SET_INFO("XMSProxyHeader",$_POST["XMSProxyHeader"]);
    $sock->SET_INFO("AdfsForceRedirect",$_POST["AdfsForceRedirect"]);
    return admin_tracks("Save Host header $HostHeader, redirects=$AdfsForceRedirect for ADFS 3.0 service ID #$serviceid");

}




function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $serviceid=intval($_GET["table"]);
    $sock               = new socksngix($serviceid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $AdfsForceRedirect=$sock->GET_INFO("AdfsForceRedirect");
    $form[]=$tpl->field_text("HostHeader","{servicename} (ADFS {hostname})",$sock->GET_INFO("HostHeader"));
    $form[]=$tpl->field_text("XMSProxyHeader","Reverse-Proxy {hostname} (X-MS-Proxy) header",$sock->GET_INFO("XMSProxyHeader"));
    $form[]=$tpl->field_checkbox("AdfsForceRedirect","{ensure_redirects}",$AdfsForceRedirect);
    echo $tpl->form_outside(null,$form,null,"{apply}",

        "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');Loadjs('fw.nginx.sites.php?td-row=$serviceid');dialogInstance5.close();","AsWebAdministrator");
    return true;
}
