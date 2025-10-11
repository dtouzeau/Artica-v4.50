<?php
$GLOBALS["PEITYCONF"]="{ width:150,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nodes.inc");
if(isset($_GET["flat-popup"])){flat_popup();exit;}
if(isset($_GET["form-js"])){form_js();exit();}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_POST["hostid"])){form_save();exit;}



flat_start();

function flat_start(){
    $page=CurrentPageName();
    $ID=$_GET["ID"];
    echo "<div id='micronode$ID'></div><script type='text/javascript'>LoadAjax('micronode$ID','$page?flat-popup=$ID');</script>";
}

function flat_popup(){
    $ID=$_GET["flat-popup"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");

    $sock=new micronodes($ID);
    $realname=$sock->GET_INFO("realname");
    if($ligne["realname"]<>$realname){
        $ligne["realname"]="{$ligne["realname"]}/$realname";
    }

    $tpl->table_form_field_js("Loadjs('$page?form-js=$ID')","AsSystemAdministrator");
    $tpl->table_form_field_text("{hostname}",$ligne["realname"],ico_server);
    echo $tpl->table_form_compile();
}
function form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["form-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
    $title="{$ligne["backendname"]}";
    return $tpl->js_dialog3($title, "$page?form-popup=$ID");
}
function form_popup():bool{
    $ID=$_GET["form-popup"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new micronodes($ID);
    $realname=$sock->GET_INFO("realname");
    if($realname==null) {
        $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
        if($Enablehacluster==1) {
            $q = new lib_sqlite("/home/artica/SQLITE/haproxy.db");
            $ligne = $q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
            $realname = $ligne["realname"];
        }
    }
    $form[]=$tpl->field_hidden("hostid",$ID);
    $form[]=$tpl->field_text("realname","{hostname}",$realname);
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance3.close();LoadAjax('micronode$ID','$page?flat-popup=$ID');","AsSystemAdministrator");
    return true;
}

function form_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["hostid"];
    $sock=new micronodes($ID);
    foreach ($_POST as $key => $value) {
        $sock->SET_INFO($key,$value);
    }
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){
        $sock=new sockets();
        $json=json_decode($sock->REST_API("/hacluster/server/pushconfig/node/$ID"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    return true;
}