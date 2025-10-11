<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["remove"])){remove();exit;}
js();

function js():bool{
    $tpl                        = new template_admin();
    $page                       = CurrentPageName();
    $pool                       = urlencode($_GET["pool"]);

    return $tpl->js_dialog4("{cache}: $pool","$page?table-start=$pool");
}

function remove(){
    $domain=urlencode($_GET["remove"]);
    $md=$_GET["md"];
    $function=$_GET["func"];
    $pool                       = urlencode($_GET["pool"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/dnsfw/cache/remove/$pool/$domain");
    echo "$('#$md').remove();\n";

    
}

function table_start(){
    $pool                       = urlencode($_GET["table-start"]);
    $page=CurrentPageName();
    $tpl                        = new template_admin();
    $searchBlock=$tpl->search_block($page,null,null,null,"&table=yes&pool=$pool");
    echo $tpl->_ENGINE_parse_body($searchBlock);
}

function table(){
    $pool                       = urlencode($_GET["pool"]);
    $search                     = urlencode($_GET["search"]);
    if(strlen($search)==""){
        $search="NONE";
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/cache/dump/$pool/$search");
    $resultfile=PROGRESS_DIR."/dnsdist.$pool.results";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $t=time();
    $data=explode("\n",@file_get_contents( $resultfile ));

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{domain}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{query}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{saved}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $text_class=null;
    foreach ($data as $line){

        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([A-Z]+).*?added\s+([0-9]+)#",$line,$re)){
            continue;
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $domain=$re[1];
        $domainenc=urlencode($domain);
        $qtype=$re[3];
        $time_saved=$re[4];
        $time_saved=$tpl->time_to_date($time_saved,true);
        $id=md5($line);
        $remove=$tpl->icon_delete("Loadjs('$page?remove=$domainenc&func=$function&md=$id&pool=$pool')");

        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td class=\"$text_class\" style='width:99%' nowrap><i class=\"fa-solid fa-floppy-disk\"></i>&nbsp;&nbsp;<strong>$domain</strong></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$qtype</td>";
        $html[]="<td class='$text_class' style='width:1%' nowrap>$time_saved</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$remove</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( {\"filtering\": {\"enabled\": false },\"sorting\": {\"enabled\": true } } ); });

</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}