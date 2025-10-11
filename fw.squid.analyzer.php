<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_POST["squidanalyzer_path"])){save();exit;}
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
    $.address.value('/proxy-statistics-generator');
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

function save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ARRAY=array();


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squidanalyzer.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squidanalyzer.log";
    $ARRAY["CMD"]="squid2.php?run-squid-analyzer=yes";
    $ARRAY["TITLE"]="{build_statistics}";
    $ARRAY["AFTER"]="LoadAjax('table-squid-analyzer','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squid-analyzer-restart');";

    $APP_SQUIDANALYZER_SCHEDULE_EXPLAIN=$tpl->_ENGINE_parse_body("{APP_SQUIDANALYZER_SCHEDULE_EXPLAIN}");

    $html[]="<table style='width:100%;margin-top:5px'>";
    $html[]="<tr>";
    $html[]="<td style='width:500px' valign='top'>";
    $html[]="<div id='dir-db-size' style='width:500px'></div>";
    $html[]="<script>Loadjs('$page?graph1=yes');</script>";
    $html[]="</td>";
    $html[]="<td style='width:90%;padding-left:25px' valign='top'>";
    $html[]="<p>$APP_SQUIDANALYZER_SCHEDULE_EXPLAIN</p>";
    $html[]="<div class='center;' style='margin-top:20px;text-align:center'>".$tpl->button_autnonome("{build_statistics}",$jsrestart,"fas fa-sync-alt","AsProxyMonitor")."</div>";

    $path="/home/artica/squidanalyzer";

    if(is_link("/home/artica/squidanalyzer")){
        $path=readlink("/home/artica/squidanalyzer");
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squidanalyzer.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squidanalyzer.log";
    $ARRAY["CMD"]="squid2.php?chdir-analyzer=yes";
    $ARRAY["TITLE"]="{change_directory}";
    $ARRAY["AFTER"]="LoadAjax('table-squid-analyzer','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squid-analyzer-restart');";

    $form[]=$tpl->field_browse_directory("squidanalyzer_path","{storage_directory}",$path,null);
    $html[]=$tpl->form_outside("{settings}",$form,null,"{apply}",$jsrestart,"AsProxyMonitor");

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}
function graph1(){


    $tpl=new templates();
    $ARRAY=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAnalyzerUseDir"));
    $USED=$ARRAY["USED"];
    $TOTAL=$ARRAY["TOTAL"]-$USED;
    $TOTAL=$TOTAL/1024;
    $USED=$USED/1024;
    $ARRAY["PART"]=$ARRAY["PART"]/1024;

    $PART=intval($ARRAY["PART"])-intval($ARRAY["SIZEKB"]);

    $MAIN["Used " .FormatBytes($USED)]=$USED;
    $MAIN["Partition " .FormatBytes($TOTAL)]=$TOTAL;
    $MAIN["DIR ".FormatBytes($ARRAY["SIZEKB"])]=$ARRAY["SIZEKB"];

    $PieData=$MAIN;
    $highcharts=new highcharts();
    $highcharts->container="dir-db-size";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{directory_size}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{directory_size} ".FormatBytes($ARRAY["SIZEKB"]));
    echo $highcharts->BuildChart();
}