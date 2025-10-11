<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once("/usr/share/artica-postfix/ressources/class.nginx.params.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["hub-launch"])){hup_launch();exit;}


www_js();
function www_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["serviceid"];
    $servicename=get_servicename($ID);
    if($ID==0){$servicename="{all}";}
    return $tpl->js_dialog4("#$ID - $servicename - {error}", "$page?popup=$ID",650);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
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
function hup_launch():bool{
    $id=$_GET["hub-launch"];
    $sock=new sockets();
    header("content-type: application/x-javascript");
    $sock->REST_API_NGINX("/reverse-proxy/checksiteid/$id");
    sleep(3);
    echo "dialogInstance4.close();\n";
    echo "Loadjs('fw.nginx.sites.php?td-row=$id');\n";
    return true;
}
function restart_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["restart-js"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $building=$tpl->_ENGINE_parse_body("{analyze}");
    $status=base64_encode("<span class='label label-warning'>$building...</span>");
    $statustitle=base64_encode($tpl->_ENGINE_parse_body("<H1>{please_wait}...</H1>"));

    header("content-type: application/x-javascript");
    $f[]="function HupLaunch$id(){";
    $f[]="\tLoadjs('$page?hub-launch=$id');";
    $f[]="}";

    $f[]="function HupPrepare$id(){";
    $f[]="\tif( document.getElementById('status-$id') ){";
    $f[]="\t\ttempdata=base64_decode('$status');";
    $f[]="\t\tdocument.getElementById('status-$id').innerHTML=tempdata;";
    $f[]="\t}";
    $f[]="\tif( document.getElementById('frontend-failed-$id') ){";
    $f[]="\t\ttempdata=base64_decode('$statustitle');";
    $f[]="\t\tdocument.getElementById('frontend-failed-$id').innerHTML=tempdata;";
    $f[]="\t}";
    $f[]="setTimeout('HupLaunch$id()',1000);";
    $f[]="}";
    $f[]="HupPrepare$id();";
    echo @implode("\n",$f);
    return true;
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["popup"];


    $fname="/usr/share/artica-postfix/ressources/databases/ReverseProxy/$ID.json";
    $json=json_decode(file_get_contents($fname));
    $FrontendErrDetail=base64_decode($json->FrontendErrDetail);
    $servicename=$json->Servicename;

    $nginx_frontend_failed=$tpl->_ENGINE_parse_body("{nginx_frontend_failed}");
    $nginx_frontend_failed=str_replace("%s","<strong>$FrontendErrDetail</strong>",$nginx_frontend_failed);
    $html[]="<div id='frontend-failed-$ID'>";
    $html[]=$tpl->div_error("{error} $servicename||$nginx_frontend_failed");
    $html[]="<div style='margin:10px;text-align: right'>";
    $html[]=$tpl->button_autnonome("{analyze}","Loadjs('$page?restart-js=$ID')",ico_refresh,"AsWebMaster",334,"btn-danger");
    $html[]="</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}