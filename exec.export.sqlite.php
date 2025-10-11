<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');



xtart($argv[1],$argv[2]);

function build_progress($text,$pourc){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/{$GLOBALS["filename"]}.{$GLOBALS["tablename"]}.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);

}


function xtart($filename,$tablename){

    $unix=new unix();
    $GLOBALS["tablename"]=$tablename;
    $GLOBALS["filename"]=$filename;
    $database_path="/home/artica/SQLITE/$filename.db";
    $destination=PROGRESS_DIR."/$filename.$tablename.gz";
    $ORDER=null;
    if(is_file($destination)){
        @unlink($destination);
    }

    if(!is_file($database_path)){
        build_progress("$filename.db no such file",110);
        return;
    }

    if(!is_file("/usr/bin/sqlite3")){
        build_progress("{please_wait} {installing} sqlite3",30);
        $unix->DEBIAN_INSTALL_PACKAGE("sqlite3");

    }
    if(!is_file("/usr/bin/sqlite3")){
        build_progress("sqlite3 no such binary",110);
        return;
    }

    build_progress("{please_wait} {exporting} $filename/$tablename",50);

    $q=new lib_sqlite($database_path);
    if($q->FIELD_EXISTS($tablename,"zDate")){
        $ORDER=" ORDER BY zDate DESC";
    }
    if($q->FIELD_EXISTS($tablename,"ID")){
        $ORDER=" ORDER BY ID DESC";
    }




    $tmpfile=$unix->FILE_TEMP().".csv";
    $cmd="/usr/bin/sqlite3 -header -csv $database_path \"select * from $tablename{$ORDER};\" > $tmpfile";
    shell_exec($cmd);
    build_progress("{please_wait} {compressing} $filename/$tablename",80);
    if(!$unix->compress($tmpfile,$destination)){
        build_progress("{failed} {compressing} $filename/$tablename",110);
        @unlink($tmpfile);
        @unlink($destination);
        return;
    }
    @unlink($tmpfile);
    build_progress("{success} $filename/$tablename",100);

}



