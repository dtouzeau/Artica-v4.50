<?php

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if($argv[1]=="--package"){create_package();exit;}

xparse();

function git_pull(){
    $unix=new unix();
    $rm =$unix->find_program("rm");
    $maindir="/etc/firehol/ipsets";
    if(is_dir($maindir)){
        shell_exec("$rm -rf $maindir");
    }
    $git=$unix->find_program("git");
    shell_exec("$git clone https://github.com/firehol/blocklist-ipsets.git /etc/firehol/ipsets");
}

function xparse(){
    $q=new postgres_sql();
    git_pull();
    $q->QUERY_SQL("TRUNCATE TABLE ipset_auto");
    echo "Parsing /etc/firehol/ipsets\n";
    $DirPath = "/etc/firehol/ipsets";
    if (!$handle = opendir($DirPath)) {
        return;
    }
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {
            continue;
        }
        if ($file == "..") {
            continue;
        }

        if(preg_match("#voipbl#",$file)){
            continue;
        }

        if(preg_match_all("#iblocklist_level1#",$file)){
            continue;
        }
        if(preg_match_all("#microsoft#",$file)){
            continue;
        }

        if(preg_match_all("#iblocklist_org_#",$file)){
            continue;
        }
        if(preg_match_all("#iblocklist_isp_#",$file)){
            continue;
        }

        if(preg_match_all("#electronic#",$file)){
            continue;
        }

        if(preg_match_all("#iblocklist_level3#",$file)){
            continue;
        }

        if(preg_match("#iblocklist_edu#",$file)){
            continue;
        }

        if (preg_match("#iblocklist_exclusions#", $file)) {
            continue;
        }
        if (preg_match("#fornonlancomputers#", $file)) {
            continue;
        }

        if (preg_match("#datacenters#i", $file)) {
            continue;
        }

        if (!preg_match("#(.+?)\.(netset|ipset)$#", $file, $re)) {
            continue;
        }
        $path = "$DirPath/$file";
        $category = $re[1];
        $type=$re[2];
        xinject($category,$type, $path);


    }
    GreenSnow();
    create_package();


}


function GreenSnow(){
    $unix=new unix();
    $tmpfile=$unix->FILE_TEMP();
    $curl=new ccurl("https://blocklist.greensnow.co/greensnow.txt");
    if(!$curl->GetFile($tmpfile)){return;}
    xinject("greensnow","ipset",$tmpfile);

}

function xinject($category,$type,$filepath){
    echo "Parsing $filepath $category $type\n";
    $f=array();
    $q=new postgres_sql();
    $q->CREATE_TABLES();
    $handle=fopen($filepath,"r");
    $d=0;
    while (!feof($handle)) {
        $d++;
        $buffer = trim(fgets($handle));
        if ($buffer == null) {
            continue;
        }
        if (!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $buffer)) {
            continue;
        }
        if(preg_match("#Entries.+?0\s+unique\s+IPs#",$buffer)){
            fclose($handle);
            return true;
        }

        $ipaddr=$buffer;
        $f[]="('$ipaddr','$category','$type')";

        if(count($f)>30000){
            echo "Parsing $filepath $category $type 30000\n";
            $sql="INSERT INTO ipset_auto (ipaddr,category,ztype) VALUES ".@implode(",",$f). " ON CONFLICT DO NOTHING";
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n";die();}
            $f=array();
        }


    }

    fclose($handle);

    if(count($f)>0){
        $sql="INSERT INTO ipset_auto (ipaddr,category,ztype) VALUES ".@implode(",",$f). " ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error."\n";die();}
        $f=array();
    }




}

function create_package(){
    $unix=new unix();
    mkdir("/home/artica/ipset/export",0755,true);
    @chmod("/home/artica/ipset/export",0755);
    @chown("/home/artica/ipset/export","ArticaStats");
    @chgrp("/home/artica/ipset/export","ArticaStats");
    if(is_file("/home/artica/ipset/export/ipsetauto.pgsql")){@unlink("/home/artica/ipset/export/ipsetauto.pgsql");}
    $cmd="/usr/local/ArticaStats/bin/pg_dump --file=/home/artica/ipset/export/ipsetauto.pgsql --data-only --format=custom --table=ipset_auto -h /var/run/ArticaStats -U ArticaStats proxydb";

    shell_exec($cmd);
    if(is_file("/home/artica/ipset/export/ipsetauto.gz")){@unlink("/home/artica/ipset/export/ipsetauto.gz");}
    if(!$unix->compress("/home/artica/ipset/export/ipsetauto.pgsql","/home/artica/ipset/export/ipsetauto.gz")){
        @unlink("/home/artica/ipset/export/ipsetauto.pgsql");
        @unlink("/home/artica/ipset/export/ipsetauto.gz");
        echo "Failed...\n";

    }

    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM ipset_auto");
    $entries=$ligne["tcount"];
    $md5file=md5_file("/home/artica/ipset/export/ipsetauto.gz");
    $date=time();

    $GLOBALS2["items"]=$entries;
    $GLOBALS2["md5"]=$md5file;
    $GLOBALS2["time"]=$date;
    @file_put_contents("/home/artica/ipset/export/ipsetauto.index",base64_encode(serialize($GLOBALS2)));
    UploadFTP("/home/artica/ipset/export/ipsetauto.index");
    UploadFTP("/home/artica/ipset/export/ipsetauto.gz");

    @unlink("/home/artica/ipset/export/ipsetauto.pgsql");
    @unlink("/home/artica/ipset/export/ipsetauto.gz");

}


function UploadFTP($localfile){

    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));

    if($UfdbCatsUploadFTPusr<>null){
        $auth=rawurlencode($UfdbCatsUploadFTPusr).":".rawurlencode($UfdbCatsUploadFTPpass);
        $auth=$auth."@";

    }

    $ch = curl_init();
    $fp = fopen($localfile, 'r');
    $basename=basename($localfile);
    $size=filesize($localfile);
    $uri="ftp://{$auth}$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$basename";
    echo "ftp://$UfdbCatsUploadFTPusr:***@$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$basename";
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_UPLOAD, 1);
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, $size);
    curl_exec ($ch);

    $Infos= curl_getinfo($ch);
    $http_code=$Infos["http_code"];
    echo "HTTP CODE: $http_code\n";

    if(curl_errno($ch)){
        $curl_error=curl_error($ch);
        squid_admin_mysql(0, "Unable to upload file $localfile to repository With Error $curl_error ( task aborted)", null,__FILE__,__LINE__);
        echo "Error:Curl error: $curl_error\n";
        return;
    }
    curl_close($ch);



}



