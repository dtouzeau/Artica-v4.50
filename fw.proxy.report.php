<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.features.inc");


if(isset($_GET["query"])){page();exit;}


function page(){

    $basedir=null;
    $tpl=new template_admin();




    if($_GET["baseroot"]==null){

        if(strpos($_GET["query"],"/")>0){

            $_GET["baseroot"]=dirname($_GET["query"]);
            $_GET["query"]=basename($_GET["query"]);
        }

    }
    $basename=$_GET["query"];
    $basepath="/home/artica/squidanalyzer/proxyreport/";
    $fullpath="$basepath$basename";


    if(isset($_GET["baseroot"])){
        echo "<H1>{$_GET["baseroot"]}</H1>";
        $baseroot=$_GET["baseroot"];
        if($baseroot==".."){$baseroot=null;}
        $fullpath=$basepath."/$baseroot/$basename";
    }


    $fullpath=str_replace("//","/",$fullpath);
    echo "<H1>$fullpath</H1>";
    $f=file_get_contents($fullpath);



    if(preg_match("#\.js$#",$basename)){echo $f;return;}

    if(preg_match("#<body.*?>(.+?)</body>#is",$f,$re)){

        $body=replaces($re[1],$baseroot);
        $body=$body."<script>Loadjs('/proxyreport/sorttable.js');Loadjs('/proxyreport/flotr2.js');</script>";


    }

    echo $tpl->_ENGINE_parse_body($body);



}

function replaces($body,$baseRoot=null){


    if(preg_match_all("#<a href=\"(.+?)\"#",$body,$re)){

        foreach ($re[1] as $uris){
            $urisenc=urlencode($uris);
            $body=str_replace($uris,"javascript:LoadAjax('MainContent','fw.proxy.report.php?query={$urisenc}&baseroot=$baseRoot')",$body);

        }


    }

    $body=str_replace('<table class="sortable stata','<table class="table table-striped sortable stata',$body);
    $body=str_replace('<table class="graphs"','<table class="table table-striped graphs"',$body);
    $body=str_replace('<table>','<table class="table table-striped">',$body);
    $body=str_replace('blockquote class="notification"',"blockquote class='small'",$body);
    $body=str_replace('table class="stata"','table class="table table-striped"',$body);
    $body=str_replace("squidanalyzer.darold.net","articatech.net",$body);
    $body=str_replace("SquidAnalyzer","{APP_SQUIDANALYZER}",$body);
    return $body;

}