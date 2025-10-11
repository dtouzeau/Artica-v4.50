<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(isset($_POST["export"])){export_confirm();exit;}
if(isset($_GET["export-js"])){export_js();exit;}
if(isset($_GET["export-popup"])){export_popup();exit;}
if(isset($_GET["export-layer"])){export_layer();exit;}
if(isset($_GET["export-layer-1"])){export_layer1();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}


js();

function js():bool{
    $ID=intval($_GET["ID"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $servicename="";
    $function=$_GET["function"];
    if($ID>0){
        $q = new lib_sqlite(NginxGetDB());
        $ligne = $q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
        $servicename = $ligne["servicename"];
    }
    return $tpl->js_dialog4("{export}/{import} $servicename","$page?export-layer=$ID&function=$function",750);
}
function file_uploaded():bool{
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $function=$_GET["function"];
    if(!preg_match("#\.gz$#",$file)){
        @unlink($fullpath);
        return $tpl->js_error("Error: $file not a gz file");

    }
    $fileEnc=urlencode($file);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/import/$fileEnc"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    header("content-type: application/x-javascript");
    echo " dialogInstance4.close();\n";
    if(strlen($function)>2){
        echo "$function();";
    }
    return admin_tracks("Success importing a new reverse-proxy rule from $file");

}
function export_layer1():bool{
    $ID=intval($_GET["export-layer-1"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $export=$tpl->button_autnonome("{export_this_website}","Loadjs('$page?export-js=$ID&function=$function');",ico_export,"AsWebMaster",450,"btn-primary");

    $import=$tpl->button_upload("{import}",$page,"style=width:450px;class=btn-primary","&function=$function");
    if($ID>0) {
        $html[] = "<div class='center'>$export</div>";
    }else {
        $html[] = "<div class='center'>$import</div>";
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function export_layer(){
    $ID=intval($_GET["export-layer"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    echo "<div id='export-layer-$ID'></div>";
    echo "<script>LoadAjaxSilent('export-layer-$ID','$page?export-layer-1=$ID&function=$function');</script>";
}

function js_export(){
    $ID=intval($_GET["ID"]);
    $tpl=new template_admin();
    $page=CurrentPageName();

    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $tpl->js_confirm_execute("{export_this_website}: $servicename","export",$ID,"Loadjs('$page?export-js=$ID')");

}
function export_confirm(){
    $ID=intval($_POST["export"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    admin_tracks("Exporting source date of website $servicename");

}

function export_js(){
    $page=CurrentPageName();
    $ID=intval($_GET["export-js"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $tpl=new template_admin();
    $tpl->js_dialog4("{export} $servicename","$page?export-popup=$ID",550);
}
function export_popup():bool{
    $ID=intval($_GET["export-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js[]="dialogInstance4.close();";
    $js[]="document.location.href='ressources/logs/web/www-$ID.gz'";
    $bugjs=$tpl->framework_buildjs("/reverse-proxy/export/$ID",
        "nginx.export.$ID.progress","nginx.export.$ID.log","export-popup-$ID",@implode(";",$js));
    $html="<div id='export-popup-$ID'></div><script>$bugjs</script>";
    echo $html;
return true;

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
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
