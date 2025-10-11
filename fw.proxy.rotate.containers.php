<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}





table();


function table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $TRCLASS    = null;
    $t          = time();
    $q          = new lib_sqlite("/home/artica/SQLITE/legallogs.db");

    //<i class="fad fa-file-search"></i>

  /*  $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0')\">";
    $html[]="<i class='fad fa-file-search'></i> {search} </label>";



    $html[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$reconfigure;\">";
    $html[]="<i class='fa fa-save'></i> {apply_configuration} </label>";	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0')\">";
    $html[]="<i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$reconfigure;\">";
    $html[]="<i class='fa fa-save'></i> {apply_configuration} </label>";
$html[]="</div>";
  */

$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
$html[]="<thead>";
$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{type}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{filename}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{size}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
    $html[]="</tr>";
$html[]="</thead>";
$html[]="<tbody>";

    $results=$q->QUERY_SQL("SELECT * FROM store_files ORDER BY ID DESC");

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md         = md5(serialize($ligne));
        $date       = $tpl->time_to_date($ligne["filedate"],true);
        $hostname   = $ligne["hostname"];
        $ztype      = $ligne["ztype"];
        $ID         = $ligne["ID"];
        $filename   = basename($ligne["filepath"]);
        $filesize   = FormatBytes($ligne["filesize"]/1024);
        $filename   = $tpl->td_href($filename,"{view}","Loadjs('fw.proxy.relatime.php?logs-container=$ID')");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$date</td>";
        $html[]="<td style='width:1%' nowrap>$hostname</td>";
        $html[]="<td style='width:1%' nowrap>$ztype</td>";
        $html[]="<td style='' nowrap>$filename</td>";
        $html[]="<td style='width:1%' nowrap>$filesize</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')","AsSquidAdministrator")."</center></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() {";
    $html[]="\t$('#table-$t').footable( {";
    $html[]="\t\t\"filtering\": { \"enabled\": true },";
    $html[]="\t\t\"sorting\": { \"enabled\": true },";
    $html[]="\t\t\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} }";
    $html[]="\t\t} );";
    $html[]="\t}";
    $html[]=");";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}