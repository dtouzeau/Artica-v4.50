<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["import"])){import_popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog6("{import_rules}", "$page?import=yes&function=$function",700);
}

function import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $bt_upload=$tpl->button_upload("{import_rules}",$page,null,"&function=$function")."&nbsp;&nbsp;";
    $html="<div id='db-import'><div class='center'>$bt_upload</div></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function file_uploaded():bool{
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $function=$_GET["function"];
    if(!preg_match("#\.db$#",$file)){
        return $tpl->js_error("Error: $file not a *.db file");
    }

   if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }
    $array["HarmpID"]=$gpid;
    $sock=new sockets();
    $array["path"]=$file;
    $data=$sock->REST_API_POST("/reverse-proxy/database/import",$array);
    $json=json_decode($data);
    if(!$json->Status){
        return $tpl->js_mysql_alert($json->Error);
    }
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    if(strlen($function)>2) {
        echo "$function();\n";
    }
    return admin_tracks("Imported reverse-proxy full set of rules");

}