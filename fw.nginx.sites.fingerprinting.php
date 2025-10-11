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

js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["serviceid"];
    $servicename=get_servicename($ID);
    return $tpl->js_dialog4("#$ID - $servicename - {fingerprinting}", "$page?popup=$ID",800);
}
function popup():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["popup"];
    $servicename=get_servicename($serviceid);
    $page=CurrentPageName();
    $sockngix                   = new socksngix($serviceid);
    $FingerPrinting = intval($sockngix->GET_INFO("FingerPrinting"));
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("FingerPrinting","{enable_feature}",$FingerPrinting);

    echo $tpl->form_outside("",$form,"{fingerprinting_explain}","{apply}",
        "dialogInstance4.close();LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');","AsSystemAdministrator");
    return true;
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

function Save(){
    $serviceid=intval($_POST["serviceid"]);
    $sockngix                   = new socksngix($serviceid);
    unset($_POST["serviceid"]);
    foreach ($_POST as $key=>$value){
        $sockngix->SET_INFO($key,$value);
    }

    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
}