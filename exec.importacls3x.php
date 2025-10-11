<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["TITLENAME"]="Rsync Daemon";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.categories.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");



import3x($argv[1]);
function build_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents(PROGRESS_DIR."/import3x.progress", serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0755);
    if($GLOBALS["WAIT"]){usleep(800);}
}
function import3x($filename){

    $unix=new unix();
    echo "Filename.....: $filename\n";
    build_progress($filename,10);

    if(is_file($filename)){
        $fullpath=$filename;
    }else{
        $fullpath=dirname(__FILE__)."/ressources/conf/upload/$filename";
    }


    echo "Fullpath.....: $fullpath\n";
    if(!is_file($fullpath)){
        build_progress($filename." no such file",110);
        return;
    }

    $rm=$unix->find_program("rm");
    if(is_dir("/home/artica/import3x")){system("$rm -rf /home/artica/import3x");}
    @mkdir("/home/artica/import3x",0755,true);

    $tar=$unix->find_program("tar");
    echo "Uncompressing...\n";

    build_progress("{importing}",20);
    shell_exec("$tar -xf $fullpath -C /home/artica/import3x/");
    @unlink($fullpath);

    if(!is_file("/home/artica/import3x/squidlogs/webfilters_sqgroups.gz")){
        system("$rm -rf /home/artica/import3x");
        echo "/home/artica/import3x/squidlogs/webfilters_sqgroups.gz no such file...\n";
        build_progress("webfilters_sqgroups.gz missing",110);
        return;
    }
    if(!is_file("/home/artica/import3x/squidlogs/webfilters_sqitems.gz")){
        system("$rm -rf /home/artica/import3x");
        echo "/home/artica/import3x/squidlogs/webfilters_sqitems.gz no such file...\n";
        build_progress("webfilters_sqitems.gz missing",110);
        return;
    }

    build_progress("{importing}",30);
    if(!$unix->uncompress("/home/artica/import3x/squidlogs/webfilters_sqgroups.gz","/home/artica/import3x/squidlogs/webfilters_sqgroups.txt")){
        build_progress("webfilters_sqgroups.gz {uncompress_failed}",110);
        system("$rm -rf /home/artica/import3x");
        return;
    }


    build_progress("{importing}",40);
    if(!$unix->uncompress("/home/artica/import3x/squidlogs/webfilters_sqitems.gz","/home/artica/import3x/squidlogs/webfilters_sqitems.txt")){
        build_progress("webfilters_sqitems.gz {uncompress_failed}",110);
        system("$rm -rf /home/artica/import3x");
        return;
    }

    $data=@file_get_contents("/home/artica/import3x/squidlogs/webfilters_sqgroups.txt");

    if(!preg_match("#INSERT\s+IGNORE INTO `webfilters_sqgroups` VALUES.*?\((.+?)\);#is",$data,$re)){
        build_progress("data {failed}",110);
        system("$rm -rf /home/artica/import3x");
        return;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("webfilters_sqgroups", "idtemp")){$q->QUERY_SQL("ALTER TABLE webfilters_sqgroups ADD `idtemp` INTEGER NOT NULL DEFAULT 0");}

    $tb=explode("),(",$re[1]);
    $prefix="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,tplreset,idtemp) VALUES ";
    foreach ($tb as $line){

        $new_tb=explode(",",$line);
        foreach ($new_tb as $index=>$sline){$new_tb[$index]=str_replace("'","",$sline);}
        $ID=$new_tb[0];
        $GroupName=$new_tb[1];
        $GroupType=$new_tb[2];
        $enabled=$new_tb[5];
        build_progress("{importing} $GroupName",50);
        $l="('$GroupName','$GroupType','$enabled','0','$ID')";
        $q->QUERY_SQL($prefix.$l);
        if(!$q->ok){echo $q->mysql_error;build_progress("SQL {error}",110);return;}

    }


    $data=@file_get_contents("/home/artica/import3x/squidlogs/webfilters_sqitems.txt");
    if(!preg_match("#INSERT\s+IGNORE INTO.*?VALUES.*?\((.+?)\);#is",$data,$re)){
        build_progress("data items {failed}",110);
        system("$rm -rf /home/artica/import3x");
        return;
    }

    $GPIDZ=array();
    $tb=explode("),(",$re[1]);
    $prefix = "INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES ";
    $zdate=date("Y-m-d H:i:s");
    foreach ($tb as $line) {

        $new_tb = explode(",", $line);
        foreach ($new_tb as $index => $sline) {
            $new_tb[$index] = str_replace("'", "", $sline);
        }
        $pattern=$new_tb[1];
        build_progress("{importing} $pattern",80);
        $gpid=$new_tb[3];
        if(!is_numeric($gpid)){echo "$pattern: Incompatible data";continue;}
        if(intval($gpid)==0){echo "Incompatible data";continue;}

        if(!isset($GPIDZ[$gpid])) {
            $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE idtemp=$gpid");
            $GPIDZ[$gpid] = $ligne["ID"];
        }

        $newgpid=intval($GPIDZ[$gpid]);
        if($newgpid==0){
            echo "$pattern: group translation failed...\n";
            continue;
        }

        $l="('$newgpid','$pattern','$zdate','root',1)";
        $q->QUERY_SQL($prefix.$l);
        if(!$q->ok){echo $q->mysql_error;build_progress("SQL {error}",110);return;}

    }



    build_progress("{importing} $pattern",90);
    system("$rm -rf /home/artica/import3x");
    build_progress("{importing} {success}", 100);
}