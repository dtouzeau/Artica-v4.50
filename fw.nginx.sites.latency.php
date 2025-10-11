<?php
///$_GET["verbose"]="yes";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["serviceid"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["serviceid"])){Save();exit;}

js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["serviceid"];
    $servicename=get_servicename($ID);
    return $tpl->js_dialog4("#$ID - $servicename - {latency}", "$page?popup=$ID",800);
}
function popup():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["popup"];


    $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/latency/urls/$serviceid"));

    $Content=trim($data->Content);
    if(strlen($Content)<2){
        $Content=null;
    }

    if(is_null($Content) ){

        $Content="# Use / as only index page\n# Set Paths that will be tested by the reverse-proxy\n# eg:\n# /shop/index\n# /home/script/test\n/\n";
        VERBOSE($Content,__LINE__);
    }
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_textarea("urls","URLS",$Content);

    echo $tpl->form_outside("",$form,"","{apply}",
        "dialogInstance4.close();LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');","AsSystemAdministrator");
    return true;
}
function Save(){
    $sock=new sockets();
    $sock->REST_API_POST("/reverse-proxy/latency/upload/uris",$_POST);
}


function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}

