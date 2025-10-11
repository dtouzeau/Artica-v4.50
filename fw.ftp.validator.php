<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["FTP_SERVER"])){Save();exit;}
if(isset($_GET["ftp-validator-confirmed"])){SaveConfirmed();exit;}

js();


function js(){
    $id=$_GET["id"];
    $data=urlencode($_GET["data"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog8("{squid_ftp_user}","$page?popup=yes&id=$id&data=$data");
}

function popup(){
    $id=$_GET["id"];
    $array=unserialize(base64_decode($_GET["data"]));
    $data=urlencode($_GET["data"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ftp.validator.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ftp.validator.logs";
    $ARRAY["CMD"]="ftpproxy.php?validator=yes";
    $ARRAY["TITLE"]="{squid_ftp_user}";
    $ARRAY["AFTER"]="Loadjs('$page?ftp-validator-confirmed=yes&id=$id&data=$data')";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=ftp-validator')";

    $html[]="<div id='ftp-validator'></div>";
    $form[]=$tpl->field_text("FTP_SERVER", "{ftp_server}", $array["FTP_SERVER"]["VALUE"]);

    if(isset($array["FTP_PASSIVE"])){
        $form[]=$tpl->field_checkbox("FTP_PASSIVE","{enable_passive_mode}",intval($array["FTP_PASSIVE"]),false,"{enable_passive_mode_explain}");
    }

    if(isset($array["TLS"])){
        $form[]=$tpl->field_checkbox("TLS","{useTLS}",intval($array["TLS"]["VALUE"]));
    }

    $form[]=$tpl->field_text("TARGET_DIR", "{target_directory}", $array["TARGET_DIR"]["VALUE"]);
    $form[]=$tpl->field_text("USERNAME", "{ftp_username}", $array["USERNAME"]["VALUE"]);
    $form[]=$tpl->field_password("PASSWORD", "{ftp_password}", $array["PASSWORD"]["VALUE"]);
    $html[]=$tpl->form_outside("{squid_ftp_user}", @implode("\n", $form),null,"{apply}",$jsafter,"AsAnAdministratorGeneric");
    echo $tpl->_ENGINE_parse_body($html);
}

function SaveConfirmed(){
    $id=$_GET["id"];
    $array=unserialize(base64_decode($_GET["data"]));
    $FTPValidator=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPValidator")));

    foreach ($array as $Key=>$main){
        $KeyToSave=$main["KEY"];
        $valueToSave=$FTPValidator[$Key];
        writelogs("SAVING $KeyToSave -> $valueToSave",__FILE__,__FUNCTION__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($KeyToSave,$valueToSave);

    }
    $hostname=$FTPValidator["FTP_SERVER"];
    header("content-type: application/x-javascript");
    echo "dialogInstance8.close();\n";
    echo "document.getElementById('$id').value='$hostname';\n";

}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FTPValidator",base64_encode(serialize($_POST)));

}

