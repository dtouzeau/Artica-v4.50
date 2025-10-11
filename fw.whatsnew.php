<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog7("What`s New","$page?popup=yes");
}

function popup(){
    preg_match("#START_WHATSNEW(.*?)STOP_WHATSNEW#s",@file_get_contents("WHATSNEW"),$re);
    $t      = time();
    $table  = explode("\n",$re[1]);
    $VERSION= trim(@file_get_contents("VERSION"));
    $tpl    = new template_admin();
    $TRCLASS= null;

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    rsort($table);
    foreach ($table as $line){

            $line   = trim($line);
            $xver   = $VERSION;
            $md     = md5($line);

        if($line==null){continue;}
        if(preg_match("#^([0-9\.]+):(.+)#",$line,$re)){
            $xver=$re[1];
            $line=$re[2];
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fas fa-check'></i>&nbsp;<strong>$xver</strong></td>";
        $html[]="<td class=\"\">$line</td>";

        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}