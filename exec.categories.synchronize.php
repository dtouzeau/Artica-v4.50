<?php
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["FAILED_UPLOADED"]=0;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["CLUSTER_SINGLE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.openssl.aes.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if(isset($argv[1])){
    if($argv[1]=="--single"){single($argv[2],$argv[3]);exit;}
    if($argv[1]=="--server"){single_server($argv[2]);exit;}
}

xsync();
function build_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/categories.services.progress", serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function xsync(){
    $unix=new unix();

    $pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
    $ExecTime=$unix->file_time_min($pidtime);
    if($ExecTime<3){
        build_progress("{$ExecTime}mn, need minimal each 4Mn, retry later",110);
        return false;
    }

    if(system_is_overloaded(__FILE__)){
        build_progress("Overloaded system",110);
        die();
    }

    if(is_file("/etc/cron.d/categories-services")) {
        $unix->Popuplate_cron_delete("categories-services");
    }

}

function single_server($ID){
    $catz=new mysql_catz();
    $catz->synchronizeCatz("articatech-non-existent-domain.zva",$ID);
    if(!$catz->ok){
        echo "$catz->mysql_error\n";
        return;
    }



    $ItemsNumber=intval($catz->CategoryNumbers);
    $lastUpdate=intval($catz->CategoryTime);
    $CategoriesCount=count($catz->CategoriesList);
    $CategoriesList=$catz->CategoriesList;
    echo "Number of categories = $CategoriesCount, items=$ItemsNumber, time=$lastUpdate\n";
    foreach ($CategoriesList as $category_id){
        echo "$category_id\n";
    }

}

function single($ID,$category_id){
    echo "Scanning remote $ID ($category_id)\n";
    $catz=new mysql_catz();
    $catz->ufdbcat_remote("articatech-non-existent-$category_id.zva",$ID);
    $CategoryName=$catz->CategoryName;
    $items=$catz->CategoriesCount;
    $CategoriesNumber=count($catz->CategoriesList);
    echo "$category_id) $CategoryName Number Of Categories: $CategoriesNumber items:$items\n";

}