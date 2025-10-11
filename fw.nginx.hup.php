<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");


if(isset($_GET["hup"])){hup_prepare();exit;}
if(isset($_GET["hub-launch"])){hup_launch();exit;}

function hup_launch():bool{
    $id=$_GET["hub-launch"];
    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/singlehup/$id");
    echo "Loadjs('fw.nginx.sites.php?td-row=$id');\n";
    return true;
}

function hup_prepare():bool{
    $id=$_GET["serviceid"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $building=$tpl->_ENGINE_parse_body("{building}");
    $status=base64_encode("<span class='label label-warning'>$building...</span>");
    header("content-type: application/x-javascript");
    $f[]="function HupLaunch$id(){";
    $f[]="\tLoadjs('$page?hub-launch=$id');";
    $f[]="}";

    $f[]="function HupPrepare$id(){";
    $f[]="\tif( document.getElementById('status-$id') ){";
    $f[]="\t\ttempdata=base64_decode('$status');";
    $f[]="\t\tdocument.getElementById('status-$id').innerHTML=tempdata;";
    $f[]="\t}";
    $f[]="setTimeout('HupLaunch$id()',1000);";
    $f[]="}";
    $f[]="HupPrepare$id();";
    echo @implode("\n",$f);
    return true;
}




