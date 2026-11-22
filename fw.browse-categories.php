<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js(){
    $page=CurrentPageName();
    $title=$_GET["title"];
    $field_id=$_GET["field-id"];
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("$title");
    $tpl->js_dialog9($title, "$page?popup=yes&field-id=$field_id",750);
}

function popup(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $field_id = $_GET["field-id"];
    echo $tpl->search_block($page, null, null, null, "&field-id=$field_id");
}

function search(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $search = $_GET["search"];


    $TRCLASS = null;
    $field_id = $_GET["field-id"];
    $t = time();
    $fid = $_GET["field-id"];
    $id_string = "$fid-string";

    // Daemon API: list nginx_services rows ordered by zorder.
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/list/all");
    $data = json_decode($resp, true);
    print_r($data);

}