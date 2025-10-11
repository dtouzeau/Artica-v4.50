<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["step0"])){step0();exit;}
if(isset($_GET["step1"])){step1();exit;}

js();

function js(){
    $tpl       = new template_admin();
    $page      = CurrentPageName();

    $tpl->js_dialog1("{new_report}","$page?step0=yes");
}

function step0(){
    $page      = CurrentPageName();
    echo "<div id='statistics-query'></div><script>LoadAjax('statistics-query','$page?step1=yes');</script>";
}

function step1(){
    $tpl       = new template_admin();
    $page      = CurrentPageName();

    $HASH[1]="{daily}";

    $form[]=$tpl->field_array_hash($HASH,"report_type","{report_type}");
    $html=$tpl->form_outside("{new_report}",$form,null,"{apply}","LoadAjax('statistics-query','$page?step1=yes');");
    echo $tpl->_ENGINE_parse_body($html);

}