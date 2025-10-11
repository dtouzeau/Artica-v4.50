<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["graph1"])){graph1();exit;}

page();

function page(){
    $page=CurrentPageName();

    $APP_SQUIDANALYZER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SQUIDANALYZER_VERSION");
    $html="
<div class=\"row border-bottom white-bg dashboard-header\">
<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_SQUIDANALYZER} v.$APP_SQUIDANALYZER_VERSION</h1><p>{APP_SQUIDANALYZER_EXPLAIN}</p></p></div>
</div>
<div class='row'>
    <div id='progress-squid-analyzer-restart'></div>
    <div class='ibox-content'>
        <div id='table-squid-analyzer'></div>
    </div>
</div>



<script>
    $.address.state('/');
    $.address.value('/proxy-statistics-index');
    LoadAjax('table-squid-analyzer','$page?table=yes');

</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SQUIDANALYZER}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}

function table(){

    $tpl=new template_admin();
    $t=time();
    $html=array();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' >{reports}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{weekly}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{monthly}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $results=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ANALYZER_DATES"));
    krsort($results);

    foreach ($results as $stime=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$stime'>";
        $syear=date("Y",$stime);
        $smonth=date("m",$stime);
        $sday=date("d",$stime);
        $date=$tpl->time_to_date($stime);
        $Week=date("W",$stime);
        $hrefMonth=null;
        $hrefweek=null;
        if(is_file("/home/artica/squidanalyzer/proxyreport/$syear/$smonth/index.html")){
            $hrefMonth=$tpl->td_href(date("{F}",$stime),null,"s_PopUpFull('proxystats/$syear/$smonth/index.html',2048,1024);");

        }
        if(is_file("/home/artica/squidanalyzer/proxyreport/$syear/week{$Week}/index.html")){
            $hrefweek=$tpl->td_href("{week} $Week",null,"s_PopUpFull('proxystats/$syear/week{$Week}/index.html',2048,1024);");

        }

        $href=$tpl->td_href($date,null,"s_PopUpFull('proxystats/$syear/$smonth/$sday/index.html',2048,1024);");


        $html[]="<td class=center width=1%><i class=\"fas fa-chart-bar\"></i></td>";
        $html[]="<td class=\"left\"><strong>$href</strong></td>";
        $html[]="<td class=\"left\"><strong>$hrefweek</strong></td>";
        $html[]="<td class=\"left\"><strong>$hrefMonth</strong></td>";
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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
