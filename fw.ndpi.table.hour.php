<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{statistics} {today} {this_hour}","$page?popup=yes",1024);



}

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   echo $tpl->search_block($page,null,null,null);



}



function search(){
    $t=time();
    $q=new postgres_sql();
    $tpl=new template_admin();
    $hour1=date("Y-m-d H:00:00");
    $hour2=date("Y-m-d H:59:59");
    $ipClass=new IP();

    $search=$_GET["search"];

    if($search==null) {
        $sql = "SELECT src,dst,category,sum(download) as download,sum(upload) as upload FROM ndpi_main WHERE zdate>='$hour1' and zdate<='$hour2' GROUP BY src,dst,category ORDER BY download DESC LIMIT 500 ";
    }
    if($ipClass->isValid($search)){

        $sql = "SELECT src,dst,category,sum(download) as download,sum(upload) as upload FROM ndpi_main 
        WHERE ( (src='$search') OR (dst='$search') ) AND (zdate>='$hour1' and zdate<='$hour2') GROUP BY src,dst,category ORDER BY download DESC LIMIT 500 ";

    }



    $results=$q->QUERY_SQL($sql);



    if(!$q->ok){echo $q->mysql_error."\n";}


	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{src}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{dst}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{download}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{upload}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        $src=$ligne["src"];
        $dst=$ligne["dst"];
        $category=$ligne["category"];
        $js=null;
        $download=FormatBytes($ligne["download"]/1024);
        $upload=FormatBytes($ligne["upload"]/1024);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $html[]="<tr class='$TRCLASS'>";
        
        $html[]="<td><strong>". texttooltip($src,null,$js)."</strong></td>";
        $html[]="<td><strong>". texttooltip($dst,null,$js)."</strong></td>";
        $html[]="<td  width='1% nowrap'>". texttooltip($category,null,$js)."</td>";
        $html[]="<td  width='1% nowrap'>$download</td>";
        $html[]="<td  width='1% nowrap'>$upload</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";




$html[]="</div><div class='center' style='margin-top:10px;'><small>$sql</small></div>
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
echo $tpl->_ENGINE_parse_body($html);

}