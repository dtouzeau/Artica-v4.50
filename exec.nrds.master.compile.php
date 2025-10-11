<?php

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
xparse();

function xparse(){
    $unix=new unix();
    $fname="/home/artica/nrds/nrd-7days-free.txt";
    $dname=dirname($fname)."/nrds.gz";
    $iname=dirname($fname)."/nrds.txt";
    $md51=md5_file($fname);
    shell_exec("/home/artica/nrds/nrd.sh >/home/artica/nrds/nrd.log 2>&1");
    $md52=md5_file($fname);
    $lines=$unix->COUNT_LINES_OF_FILE($fname);
    if($md51==$md52){return true;}

    if($lines<200){
        $logs=@file_get_contents("/home/artica/nrds/nrd.log");
        squid_admin_mysql(0, "NRDS Corrupted", $logs,__FILE__,__LINE__);
        return false;
    }

    if(!$unix->compress($fname,$dname)){
        squid_admin_mysql(0, "NRDS Compressions failed", $GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
        return false;
    }

    $MAIN["MD5"]=$md52;
    $MAIN["TIME"]=time();
    $MAIN["ROWS"]=$lines;
    @file_put_contents($iname,base64_encode(serialize($MAIN)));



    UploadFTP($dname);
    UploadFTP($iname);
    $logs=@file_get_contents("/home/artica/nrds/nrd.log");
    squid_admin_mysql(1, "NRDS Update success $lines domains", $logs,__FILE__,__LINE__);
    return true;
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



