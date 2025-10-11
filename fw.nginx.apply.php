<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["launch-order"])){launch_order();exit;}
if(isset($_GET["request-failed"])){request_failed();exit;}
js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $addjs="";
    if(isset($_GET["addjs"])){
        $addjs="&addjs=".$_GET["addjs"];
    }


    return $tpl->js_dialog12("{apply_configuration}","$page?popup=yes&serviceid=$serviceid&function=$function$addjs");
}
function request_failed():bool{
    $tpl=new template_admin();
    return $tpl->js_error(base64_decode($_GET["request-failed"]));
}

function launch_order():bool{
    $sock = new sockets();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"]);
    if($serviceid==0){
        $data = $sock->REST_API_NGINX("/reverse-proxy/all");
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            echo $tpl->div_error(json_last_error_msg());
            return false;
        }

        if(!$json->Status){
            header('HTTP/1.1 500 Internal Server Error');
            echo $json->Error;
            return false;
        }
        return true;

    }


    $data = $sock->REST_API_NGINX("/reverse-proxy/single/$serviceid");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
        return false;
    }

    if(!$json->Status){
        header('HTTP/1.1 500 Internal Server Error');
        echo $json->Error;
        return false;
    }

    sleep(1);

    return true;
}

function popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"]);

    $function=$_GET["function"];
    $t=time();

    $addjs="";
    if(isset($_GET["addjs"])){
        $addjs="&addjs=".$_GET["addjs"];
    }

$f[]="<H1>{apply_configuration} {please_wait}...</H1>";
$f[]="<div id=\"counter\" class='center' style='font-size: 80px;margin:50px'></div>";
$f[]="";
$f[]="<script>";
    $f[]="var myObjExec$t = {";
    $f[]="\tmyFunction$t: function() {";
    $f[]="\t\tdialogInstance12.close();";
    $f[]="\t\tLoadjs('fw.nginx.sites.php?success-js=yes');";
    if($serviceid>0) {
        $f[] = "\t\tLoadjs('fw.nginx.sites.php?td-row=$serviceid&function=$function');";
    }

    if(isset($_GET["addjs"])){
        $addjs=base64_decode($_GET["addjs"]);
        $f[]="\t\t$addjs\n";
    }


    $f[]="\t}";
    $f[]="}";
    $f[]="";
    $f[]="\t$.get(\"$page?launch-order=yes&serviceid=$serviceid&function=$function\", function(data) {";
    $f[]="\t\tvar function$t = 'myFunction$t';";
    $f[]="\t\tmyObjExec$t"."[function$t]();";
    $f[]="\t\t}).fail(function(data) {";
    $f[]="\t\t\tdialogInstance12.close();";
    $f[]="\t\t\tLoadjs('$page?request-failed='+base64_encode(data.responseText));";
    $f[]="\t\t});";
    $f[]="";
    $f[]="    var count = 15;";
    $f[]="    document.getElementById('counter').textContent = count;";
    $f[]="    var interval = setInterval(function() {";
    $f[]="        count--;";
    $f[]="        if(!document.getElementById('counter')){";
    $f[]="               clearInterval(interval);";
    $f[]="               return;";
    $f[]="        }";
    $f[]="        document.getElementById('counter').textContent = count;";
    $f[]="        if (count <= 0) {";
    $f[]="            clearInterval(interval);";
    $f[]="        }";
    $f[]="    }, 1000); // every second";
    $f[]="</script>";
$f[]="";
echo $tpl->_ENGINE_parse_body($f);

return true;
}