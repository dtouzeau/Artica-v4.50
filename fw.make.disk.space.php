<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once("/usr/share/artica-postfix/ressources/class.ActiveDirectory.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{make_disk_space}","$page?popup=yes",850);


}

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KIBANA_INSTALLED"))==1){
            $EnableKibana=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKibana");
            if($EnableKibana==0){

                $form[]=$tpl->field_checkbox("RemoveKibana","{remove} {APP_KIBANA}");

            }
    }


}
