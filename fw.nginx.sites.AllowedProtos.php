<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_POST["serviceid"])){Save();exit;}

js();


function js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["serviceid"]);
    return $tpl->js_dialog5("{protocols}","$page?table-start=$ID");
}

function table_start():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["table-start"]);
	echo "<div id='AllowedProto-$ID'></div>
	<script>LoadAjax('AllowedProto-$ID','$page?table=$ID')</script>";
    return true;
}

function Save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock               = new socksngix($serviceid);

    $final=array();
    foreach ($_POST as $key => $value) {
        if(!preg_match("#^PROTO_(.+)#",$key,$m)){
            continue;
        }
        if(intval($value)==0){
            continue;
        }
        $final[]=trim(strtoupper($m[1]));

    }
    if(count($final)==0){
        $ModSecurityProtocols="POST,GET,HEAD,OPTIONS,PUT";
    }else {
        $ModSecurityProtocols = @implode(",", $final);
    }

    $sock->SET_INFO("ModSecurityProtocols",$ModSecurityProtocols);
    return admin_tracks_post("Save Allowed protocols service ID #$serviceid");

}




function table():bool{
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["table"]);
    $sock               = new socksngix($serviceid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);

    $allprotos="GET, HEAD, POST, OPTIONS, PUT, PATCH, DELETE, CHECKOUT, COPY, DELETE, LOCK, MERGE, MKACTIVITY, MKCOL, MOVE, PROPFIND, PROPPATCH, PUT, UNLOCK, TRACE, CONNECT";

    $ModSecurityProtocols              = $sock->GET_INFO("ModSecurityProtocols");
    VERBOSE("ModSecurityProtocols: $ModSecurityProtocols",__LINE__);

    if(strlen($ModSecurityProtocols)<3){
        VERBOSE("USE FAULT",__LINE__);
        $ModSecurityProtocols="POST,GET,HEAD,OPTIONS,PUT";
    }
    $tb=explode(",",$ModSecurityProtocols);
    $MAINPROTOS=array();
    foreach ($tb as $proto){
        $proto=trim(strtoupper($proto));
        VERBOSE("SAVED PROTO: $proto",__LINE__);
        $MAINPROTOS[$proto]=true;

    }

    $ts=explode(",",$allprotos);
    $ALREADY=array();
    foreach ($ts as $proto){
        $proto=trim(strtoupper($proto));
        if(isset($ALREADY[$proto])){
            continue;
        }
        $value=0;
        if(isset($MAINPROTOS[$proto])){
            $value=1;
        }
        $form[]= $tpl->field_checkbox("PROTO_$proto",$proto,$value);
        $ALREADY[$proto]=true;
    }

    echo $tpl->form_outside(null,$form,null,"{apply}",
        "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');Loadjs('fw.nginx.sites.php?td-row=$serviceid')","AsWebAdministrator");
    return true;
}
