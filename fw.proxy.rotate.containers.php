<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
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

$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
$html[]="<thead>";
$html[]="<tr>";
    $html[]="<th>{date}</th>";
    $html[]="<th>{hostname}</th>";
    $html[]="<th class='center'>{type}</center></th>";
    $html[]="<th class='center'>{filename}</center></th>";
    $html[]="<th class='center'>{size}</center></th>";
    $html[]="<th class='center'>{delete}</center></th>";
    $html[]="</tr>";
$html[]="</thead>";
$html[]="<tbody id='rotatecontai-rows'>";

    $results=$q->QUERY_SQL("SELECT * FROM store_files ORDER BY ID DESC");

    foreach ($results as $index=>$ligne){
        $TRCLASS=null;
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
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rotatecontai-pager'></span></div></div></div>";
    $html[]="";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rotatecontai-rows","rotatecontai-pager");

}