<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');

CATEGORIES_COUNT();

function CATEGORIES_COUNT(){
    $unix=new unix();
    $q=new postgres_sql();

    if(!$q->TABLE_EXISTS("personal_categories")){
        $q->create_personal_categories();
    }

    if(!$q->TABLE_EXISTS("personal_categories")){return false;}

    if(!$q->FIELD_EXISTS("personal_categories","remotecatz")) {
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD remotecatz smallint NOT NULL DEFAULT 0");
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD serviceid bigint NOT NULL DEFAULT 0");
        $q->create_index("personal_categories","idx_serviceid",array("serviceid"));
        $q->create_index("personal_categories", "idx_rcatz", array("remotecatz"));
    }

    if(!$q->FIELD_EXISTS("personal_categories","created")) {
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD created bigint NOT NULL DEFAULT 0");
    }


    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php ".dirname(__FILE__)."/exec.categories.synchronize.php";
    echo "$cmd\n";
    shell_exec($cmd);


    $sql=LIST_CATEGORIES_SQL();


    $results=$q->QUERY_SQL($sql);
    $TOTAL=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $categorytable=$ligne["categorytable"];
        $categoryname=$ligne["categoryname"];
        if(preg_match("#reserved#",$categoryname)){continue;}
        $category_id=$ligne["category_id"];
        $ligneScount=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM $categorytable");
        $rows=intval($ligneScount["tcount"]);
        echo "$categoryname: $rows\n";
        $MAIN[$category_id]["NAME"]=$categoryname;
        $MAIN[$category_id]["ROWS"]=$rows;
        $TOTAL=$TOTAL+$rows;


    }
    $MAIN[0]["ROWS"]=$TOTAL;
    echo "TOTAL: $TOTAL\n------------------------------\n";
    $sock=new sockets();
    $sock->SET_INFO("PERSONAL_CATEGORIES_COUNT",base64_encode(serialize($MAIN)));


    $ligne=$q->mysqli_fetch_array("SELECT count(familysite) as tcount FROM not_categorized");
    $not_categorized_int=intval($ligne["tcount"]);
    $sock->SET_INFO("PERSONAL_NOT_CATEGORIZED_COUNT",$not_categorized_int);
    $sock->SET_INFO("CATEGORIES_MAINTENANCE_TIME",time());



}

function LIST_CATEGORIES_SQL(){

    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    if($ManageOfficialsCategories==1){
        return "SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";
    }

    return "SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 order by categoryname";

}