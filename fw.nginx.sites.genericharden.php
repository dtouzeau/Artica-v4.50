<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_POST["GenericHarden"])){Save();exit;}

js();


function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["serviceid"]);
    return $tpl->js_dialog5("{generic_hardening}","$page?table-start=$ID");
}

function table_start():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["table-start"]);
	echo "<div id='genericharden-$ID'></div>
	<script>LoadAjax('genericharden-$ID','$page?table=$ID')</script>";
    return true;
}

function Save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock               = new socksngix($serviceid);
    $sock->SET_INFO("GenericHarden",$_POST["GenericHarden"]);
    return admin_tracks_post("Save Generic Harden for service ID #$serviceid");

}




function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["table"]);
    $sock               = new socksngix($serviceid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $GenericHarden              = intval($sock->GET_INFO("GenericHarden"));



    $form[]=$tpl->field_checkbox("GenericHarden","{enable_feature}",$GenericHarden);


    echo $tpl->form_outside(null,$form,null,"{apply}",
        "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');Loadjs('fw.nginx.sites.php?td-row=$serviceid')","AsWebAdministrator");
    return true;
}
