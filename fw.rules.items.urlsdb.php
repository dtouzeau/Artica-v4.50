<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}

if(isset($_GET["page"])){page();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $tpl->js_dialog10("{items}","$page?page=$gpid");

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["page"]);
    echo $tpl->search_block($page,null,null,null,"&gpid=$gpid");
}

function search(){
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $search=$_GET["search"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?urlsdb-search=$search&gpid=$gpid");
    $sfile="/usr/share/artica-postfix/ressources/logs/urlsdb.$gpid.log";
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{urls}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=explode("\n",@file_get_contents($sfile));

    $TRCLASS=null;
    foreach ($results as $url){
        $url=trim($url);
        if($url==null){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]= "<tr class='$TRCLASS'>";
        $html[]= "<td><strong>$url</strong></td>";
        $html[]= "</tr>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='1'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}