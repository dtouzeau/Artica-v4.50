<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$users=new usersMenus();
if(!$users->AsSystemAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    return false;
}
if(isset($_POST["LighttpdServerCertificate"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["remove"])){remove();exit;}
js();

function remove() {
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdArticaClientAuth",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerClientAuth","");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerCertDown",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdServerCertificate","");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerEnforce",0);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("webconsole.php?reload-webconsole=yes");
    echo "LoadAjaxSilent('client-certificate-status','fw.articaweb.status.php?status=yes');\n";
}

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $cert=null;
    if(isset($_GET["check"])){$cert="&check=yes";}
    $tpl->js_dialog7("{server_certificate}","$page?popup=yes$cert");
}

function popup(){
    $page=CurrentPageName();
    $security="AsSystemAdministrator";
    $jsRefresh="LoadAjaxSilent('client-certificate-status','fw.articaweb.status.php?status=yes')";
    $tpl=new template_admin();
    $title=null;
    $explain=null;
    $check=false;
    if(isset($_GET["check"])){
        $check=true;
        $form[]=$tpl->field_checkbox("FORCE","{activate_ssl_restriction}");

    }
    $jsClose="dialogInstance7.close();";

    $LighttpdServerCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdServerCertificate");
    VERBOSE("LighttpdServerCertificate: $LighttpdServerCertificate",__LINE__);
    $array=unserialize(base64_decode($LighttpdServerCertificate));
    if(!is_array($array)){$array=array();}
    if(!isset($array["crt_data"])){$LighttpdServerCertificate=null;}
    $form[]=$tpl->field_textarea("LighttpdServerCertificate",null,$LighttpdServerCertificate);

    $crt_data=base64_decode($array["crt_data"]);
    $array=openssl_x509_parse($crt_data);
    if(isset($array["subject"]["O"])){
        $title="{server_certificate} {from} &laquo{$array["subject"]["O"]}&raquo;";
    }else{
        $title="{server_certificate}";
    }

    if($check){
        if(!isset($array["subject"]["O"])){
            $explain="{certificate_server_paste}";
            $html[]="<div id='server-certificate-progress'></div>";
            $build=$tpl->framework_buildjs("webconsole.php?server-certificate=yes",
                "manager-certificate.progress",
                "manager-certificate.log",
                "server-certificate-progress",
                "$jsClose;$jsRefresh");

            $tpl->form_add_button("{create_certificate}",$build);
        }
    }

    $html[]=$tpl->form_outside($title, @implode("\n", $form),
        $explain,"{apply}","$jsRefresh;$jsClose",$security);
    echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["LighttpdServerCertificate"]==null){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdArticaClientAuth",0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerClientAuth","");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerCertDown",0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdServerCertificate","");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerEnforce",0);
        admin_tracks("Removing Web Console Certificate Client checking ");
        return false;
    }




    $MAIN=unserialize(base64_decode($_POST["LighttpdServerCertificate"]));
    $crt_data=base64_decode($MAIN["crt_data"]);
    $array=openssl_x509_parse($crt_data);
    if(!isset($array["subject"])){echo "jserror:Corrupted data";return false;}
    foreach ($array["subject"] as $key=>$val){
        $ff[]="$key=$val";
    }

   $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdServerCertificate",$_POST["LighttpdServerCertificate"]);

    if(isset($_POST["FORCE"])){
        if($_POST["FORCE"]==1){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdArticaClientAuth",1);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerEnforce",1);
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("webconsole.php?reload-webconsole=yes");
        }
    }

    admin_tracks("Uploading a new Web Console Certificate ".@implode(",",$ff));
    return true;
}