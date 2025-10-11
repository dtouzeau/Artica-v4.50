<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{file_configuration}","$page?popup=yes",780);

}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();


   $form= $tpl->field_textareacode("non",null,@file_get_contents("/etc/squid3/authenticate.conf"));
    echo $tpl->form_outside("{file_configuration}",$form,null,null);

}
