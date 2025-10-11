<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["content-id"])){content();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_POST["WebDirectory"])){Save();exit;}
if(isset($_GET["webdav-explain"])){webdav_explain_js();exit;}
if(isset($_GET["webdav-explain-popup"])){webdav_explain_popup();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["ID"]);
    echo "<div id='webcontent-site-$ID' style='margin-top: 10px'></div>
        <script>LoadAjaxSilent('webcontent-site-$ID','$page?content-id=$ID');</script>";
    return true;
}
function webdav_explain_js():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
     $page=CurrentPageName();
    $ID=intval($_GET["webdav-explain"]);
    return $tpl->js_dialog4("{ACTIVATE_THIS_USER_WEBDAV}","$page?webdav-explain-popup=$ID");
}

function webdav_explain_popup():bool{
    $ID=intval($_GET["webdav-explain-popup"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->table_form_section("WebDav","{webdavinfoexplain}");
    $q=new lib_sqlite(NginxGetDB());
    $nginxSock=new socksngix($ID);
    $ligne      = $q->mysqli_fetch_array("SELECT `servicename`,`hosts` FROM nginx_services WHERE ID=$ID");
    $Zhosts=explode("||",$ligne["hosts"]);
    $User=$nginxSock->GET_INFO("WebDavUser");
    $css="<span style='text-transform:initial'>";
    $css2="</span>";
    foreach ($Zhosts as $hostname){
        $tpl->table_form_field_text("{hostname}",$css.$hostname.$css2,ico_server);
        $tpl->table_form_field_text("{username}",$css.$User.$css2,ico_user);
        $tpl->table_form_field_text("{directory}",$css."/webdav$ID$css2",ico_directory);
        $tpl->table_form_section("&nbsp;","&nbsp;");
    }

    echo $tpl->table_form_compile();
    return true;
}

function content():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["content-id"]);
    $_SESSION["NGINX-WEBCONTENT-ID"]=$ID;
    $nginxSock=new socksngix($ID);

    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $tpl->field_hidden("ID","$ID");

    if($ligne["WebDirectory"]==null) {$ligne["WebDirectory"]="/home/www/$ID";}
    $WebContentSize=intval($ligne["WebContentSize"]);
    $html=array();
    if($WebContentSize>10){

        $Download=$tpl->button_autnonome("{download}","document.location.href='$page?download=$ID'",ico_download,"AsWebMaster",335,"btn-default");

        $WebContentSize=FormatBytes($WebContentSize/1024);
        $html[]=$tpl->div_explain("{website_content}||{size}: $WebContentSize<hr>
        <div style='text-align:right;margin-top:10px'>$Download</div>");
    }

    $NgxDavExtModule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NgxDavExtModule"));
    if($NgxDavExtModule==0){
        $html[]=$tpl->div_warning("{need_update_current_version}||{NgxDavExtModule}");
    }

    if(!isset($ligne["WebDirectoryChmod"])){
        $ligne["WebDirectoryChmod"]=775;
    }
    if(intval($ligne["WebDirectoryChmod"])==0){
        $ligne["WebDirectoryChmod"]=775;
    }
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_browse_directory("WebDirectory","{storage_directory}",$ligne["WebDirectory"]);
    $form[]=$tpl->field_numeric("WebDirectoryChmod","{privileges}",$ligne["WebDirectoryChmod"]);



    $form[]=$tpl->form_button_upload("{zip_file}",$page,"{web_content_upload_zip}","AsWebMaster");
    $form[]=$tpl->field_section("WebDav");
    $form[]=$tpl->field_checkbox("WebDavEnabled","{ACTIVATE_THIS_USER_WEBDAV}",$nginxSock->GET_INFO("WebDavEnabled"),"WebDavUser,WebDavPassword","{ACTIVATE_THIS_USER_WEBDAV_TEXT}");
    $form[]=$tpl->field_text("WebDavUser","{username}",$nginxSock->GET_INFO("WebDavUser"));
    $form[]=$tpl->field_password("WebDavPassword","{password}",$nginxSock->GET_INFO("WebDavPassword"));

    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount from webdav_access WHERE serviceid=$ID");
    $Count=intval($ligne["tcount"]);
    $form[]=$tpl->field_button("{access_rules}","$Count {rules}","Loadjs('fw.nginx.webdav.access.php?serviceid=$ID')");



    $html[]=$tpl->form_outside(null, $form,null,"{apply}","LoadAjax('webcontent-site-$ID','$page?content-id=$ID');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');","AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function Save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $q=new lib_sqlite(NginxGetDB());
    $ID=intval($_POST["ID"]);
    $sql=sprintf("UPDATE nginx_services SET WebDirectory='%s',WebDirectoryChmod='%s' WHERE ID=%s",$_POST["WebDirectory"],$_POST["WebDirectoryChmod"],$ID);
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $nginxSock=new socksngix($ID);
    $nginxSock->SET_INFO("WebDavEnabled",$_POST["WebDavEnabled"]);
    $nginxSock->SET_INFO("WebDavUser",$_POST["WebDavUser"]);
    $nginxSock->SET_INFO("WebDavPassword",$_POST["WebDavPassword"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    return admin_tracks("Save HTML Site #$ID with WebDavEnabled={$_POST["WebDavEnabled"]} And User:{$_POST["WebDavUser"]}");
}

function download(){
    $ID=intval($_GET["download"]);
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT servicename,WebContent  FROM nginx_services WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $data=base64_decode($ligne["WebContent"]);
    $size=strlen($data);
    $attchname=$ligne["servicename"].".zip";


    header('Content-type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$attchname\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$size);
    ob_clean();
    flush();
    echo $data;

}
function file_uploaded(){

    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $file="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["file-uploaded"]}";
    $ID=intval($_SESSION["NGINX-WEBCONTENT-ID"]);

    if($ID==0){
        $tpl->js_error("Wrong ID...");
        @unlink($file);
        return false;
    }


    if(!is_file($file)){
        $tpl->js_error("$file not found...");
        return false;
    }

    if(!preg_match("#\.zip$#i",basename($file))){
        $tpl->js_error("$file unexpected file..");
        @unlink($file);
        return false;
    }

    $data=@file_get_contents($file);
    @unlink($file);
    $filelenght=strlen($data);
    $data=base64_encode($data);
    $function=$_GET["function"];
    $refreshjs="";

    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL("UPDATE nginx_services SET WebContent='$data', WebContentSize='$filelenght' WHERE ID=$ID");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    $jsCompile="Loadjs('fw.nginx.apply.php?serviceid=$ID&function=$function&addjs=$refreshjs');";
    echo "$jsCompile\n";
    echo "LoadAjax('webcontent-site-$ID','$page?content-id=$ID');\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
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