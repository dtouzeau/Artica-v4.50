<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.openssl.aes.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}


if(!isset($argv[1])){
    $unix=new unix();
    $unix->ToSyslog(__FILE__." Was exectued without any parameter");
    die();
}

if($argv[1]=="--ufdb"){ufdb_databases();exit;}
if($argv[1]=="--validate"){validate();exit;}
if($argv[1]=="--sync"){synchronize(array(),true);exit;}
if($argv[1]=="--remove"){remove_service();exit;}



function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/cguard.validator.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function synchronize($CategoriesList=array(),$pprocess=false){
    $unix=new unix();
    if($pprocess){
        $pidtime="/etc/artica-postfix/pids/cguard.synchronize.time";
        $timepid=$unix->file_time_min($pidtime);
        if(!$GLOBALS["VERBOSE"]){
            if($timepid<5){return false;}
        }

        @unlink($pidtime);
        @file_put_contents($pidtime, getmypid());
    }


    if(count($CategoriesList)==0) {
        $CategoriesList = validate();
        $pidfile="/etc/artica-postfix/pids/cguard.synchronize.pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid)){return false;}
        @file_put_contents($pidfile, getmypid());
    }



    if(!is_array($CategoriesList)){
        echo "CategoriesList is not an array!\n";
        return false;}

    $categories=new categories();
    $categories->output=true;
    $categories->cached=false;

    $ID=999991;
    foreach ($CategoriesList as $category_id=>$array){
        if(!isset($array["ITEMS"])){continue;}
        $CategoryName=$array["NAME"];
        $DESC=$array["DESC"];
        $items=$array["ITEMS"];
          if($CategoryName==null){
            echo "Creating category: $category_id No Category name, aborting\n";
            continue;
        }

        echo "Creating category: $category_id ($CategoryName) $items item(s)\n";
        if(!$categories->create_category($CategoryName,$DESC,$ID,$category_id)){
            echo $categories->mysql_error."\n";
            return false;
        }
        echo "Updating category: $category_id ($CategoryName) $items item(s)\n";
        $categories->update_category_items($category_id,$items);



    }

}

function remove_service(){
    $q=new postgres_sql();
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $q->QUERY_SQL("DELETE FROM personal_categories WHERE serviceid=999991");
    $CGuardHistoryDB=$unix->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGuardHistoryDB"));
    foreach ($CGuardHistoryDB as $category_id=>$none){
        if(!is_numeric($category_id)){continue;}
        $ufdbdir="/var/lib/ufdbartica/$category_id";
        if(!is_dir($ufdbdir)){
            continue;
        }
        shell_exec("$rm -rf $ufdbdir");
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGuardHistoryDB",base64_encode(serialize(array())));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("useCGuardCategories",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CguardCatzData",base64_encode(serialize(array())));
    squid_admin_mysql(2,"Removing CGuard category service");

}


function ufdb_databases(){
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $useCGuardCategories=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories");
    $CGuardLicense=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGuardLicense"));
    if($EnableUfdbGuard==0){die();}
    if($useCGuardCategories==0){die();}
    if($CGuardLicense==null){die();}
    $unix=new unix();
    $uuid=$unix->GetUniqueID();
    
    $GLOBALS["CguardCatzData"]=validate();
    $CGuardHistoryDB=$unix->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGuardHistoryDB"));
    $UPDATE=0;

    foreach ($GLOBALS["CguardCatzData"] as $category_id=>$MAIN){
        if(!isset($MAIN["UFDB"])){continue;}
        if(!isset($CGuardHistoryDB[$category_id])){$CGuardHistoryDB[$category_id]=null;}
        $ufdbdir="/var/lib/ufdbartica/$category_id";
        $ufdbfile="$ufdbdir/domains.ufdb";
        $ufdbback="/var/lib/ufdbartica/$category_id/domains.back";
        $ufdburl="https://cguard.artica.center/ufdb/$category_id.gz";
        $MD5_SRC=$MAIN["UFDB"];
        $MD5_DST=$CGuardHistoryDB[$category_id];
        if(!is_file($ufdbfile)){$MD5_DST=null;}
        if($MD5_SRC==$MD5_DST){
            echo "Skip category: $category_id (no changes)\n";
            continue;
        }
        $curl=new ccurl($ufdburl);
        $tmpfile=$unix->FILE_TEMP().".gz";
        echo "Downloading $ufdburl to $tmpfile\n";
        $curl->authname=$uuid;
        $curl->authpass=$CGuardLicense;
        $curl->NoHTTP_POST=true;
        if(!$curl->GetFile($tmpfile)){
            echo "Failed to download Err.$curl->error\n";
            continue;
        }
        $MD5_TMP=md5_file($tmpfile);
        if($MD5_TMP<>$MD5_SRC){
            @unlink($tmpfile);
            echo "Failed to download Corrupted\n";
            continue;
        }

        if(is_file($ufdbback)){@unlink($ufdbback);}
        echo "Uncompressing $tmpfile to $ufdbfile\n";
        if(is_file($ufdbfile)) {
            @copy($ufdbfile, $ufdbback);
            @unlink($ufdbfile);
        }
        if(!is_dir($ufdbdir)){@mkdir($ufdbdir,0755,true);}
        if(!$unix->uncompress($tmpfile,$ufdbfile)){
            echo "Uncompressing $tmpfile to $ufdbfile - FAILED -\n";
            @unlink($ufdbfile);
            @unlink($tmpfile);
            if(is_file($ufdbback)){
                echo "Back $ufdbback to $ufdbfile - -\n";
                @copy($ufdbback, $ufdbfile);
                @unlink($ufdbback);
                continue;
            }

        }
        $CGuardHistoryDB[$category_id]=$MD5_SRC;
        @unlink($tmpfile);
        echo "$ufdbfile SUCCESS!\n";
        $UPDATE++;

    }

    if($UPDATE>0) {
        if (is_file("/etc/init.d/ufdb")) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGuardHistoryDB",base64_encode(serialize($CGuardHistoryDB)));
            echo "Reloading UFDB\n";
            squid_admin_mysql(1, "Reload Web-Filtering after updating $UPDATE database(s)",__FILE__,__LINE__);
            shell_exec("/etc/init.d/ufdb reload");
        }
    }
    synchronize();

}


function validate(){
    $unix=new unix();
    $CGuardLicense=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGuardLicense"));
    $uuid=$unix->GetUniqueID();
    
    echo "Using uuid: $uuid\n";
    build_progress(10,"{checking}....");

    if($CGuardLicense==null){
        echo "Null value!\n";
        build_progress(110,"{failed} no license....");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("useCGuardCategories",0);
        return false;
    }

    build_progress(20,"{downloading}....");
    $curl=new ccurl("https://cguard.artica.center/catlist/index.php");
    $curl->authname=$uuid;
    $curl->authpass=$CGuardLicense;
    if($GLOBALS["VERBOSE"]){
        echo "Username: $uuid\nPassword: $CGuardLicense\n";
    }
    $curl->NoHTTP_POST=true;
    if(!$curl->get()){
        if($curl->CURLINFO_HTTP_CODE==401){
            remove_service();
        }
        echo "Failed Error number. $curl->error_num\n";
        build_progress(110,"{failed} " .$curl->error_num." ".$curl->error);
        return array();
    }

    echo "Done Code.$curl->CURLINFO_HTTP_CODE\n";
    build_progress(60,"{downloading} {success}....");
    $Array=$unix->unserializeb64($curl->data);
    if(!is_array($Array)){
        echo "Failed Corrupted data\n";
        build_progress(110,"{failed} {corrupted}");
        return array();
    }

    build_progress(100,"{success}....");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("useCGuardCategories",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CguardCatzTime",time());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CguardCatzData",$curl->data);
    return $Array;

}