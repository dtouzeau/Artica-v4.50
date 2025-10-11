<?php
/*
 *
 * split -b 3MB artica-4.40.000000.tgz full-package.split
 */

$GLOBALS["VERBOSE"]=false;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(preg_match("#--verbose#",@implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($argv[1]=="--index"){GET_INDEX();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--recover"){recover();exit;}
if($argv[1]=="--verify"){verify($argv[2],$argv[3]);exit;}

function GET_INDEX()
{
    $tmpar="/home/artica/tmp/files.array";
    if(!is_dir("/home/artica/tmp")) {
        @mkdir("/home/artica/tmp", 0755, true);
    }
    $cmdline="/usr/bin/curl http://articatech.net/package.php > $tmpar";

    if (!is_file($tmpar)) {
        if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
        shell_exec($cmdline);
    }
    if (!is_file($tmpar)) {
        if($GLOBALS["VERBOSE"]){echo "Downloading failed...\n";}
        return false;

    }


    $data=@file_get_contents($tmpar);
    if($GLOBALS["VERBOSE"]){echo "Encoded data:\n$data\n";}
    $decoded=base64_decode($data);
    if($GLOBALS["VERBOSE"]){echo "Decoded:\n$decoded\n";}
    $array = unserialize($decoded);
    if($GLOBALS["VERBOSE"]){print_r($array);}

    if (!is_array($array)) {
        @unlink($tmpar);
        return false;
    }

    if(count($array)<5){
        @unlink($tmpar);
        return false;
    }
    return true;
}


function build(){
    @mkdir("/home/artica/tmp/package.split", 0755, true);
    if(is_file("/home/artica/tmp/package.splited")){@unlink("/home/artica/tmp/package.splited");}
    $f[]="#!/bin/bash";
    $f[]="INPUT=/tmp/menu.sh.$$";
    $f[]="OUTPUT=/tmp/output.sh.$$";
    $f[]="trap \"rm -f \$OUTPUT >/dev/null 2>&1; rm -f \$INPUT >/dev/null 2>&1; exit\" SIGHUP SIGINT SIGTERM";
    $f[]="DIALOG=\${DIALOG=dialog}";
    $array = unserialize(base64_decode(@file_get_contents("/home/artica/tmp/files.array")));
    $Count=count($array);
    if(is_file("/tmp/downloader.sh")){@unlink("/tmp/downloader.sh");}
    $c=0;
    foreach ($array as $md5=>$url){
        $c++;
        $prc=($c/$Count)*100;
        $prc=round($prc);
        $basename=basename($url);
        if(is_file("/home/artica/tmp/package.split/$basename")){
            $smd5=md5_file("/home/artica/tmp/package.split/$basename");
            if($smd5==$md5){continue;}
            @unlink("/home/artica/tmp/package.split/$basename");
        }
        $f[]="echo $prc| dialog --title \"DOWNLOADING MAIN PACKAGE\" --gauge \"Please wait $basename...\" 6 80";
        $f[]="curl -f $url -o /home/artica/tmp/package.split/$basename >/dev/null 2>&1";
        $f[]="/usr/bin/php ".__FILE__." --verify $md5 $basename >/dev/null 2>&1";
        $f[]="if [ ! -f /home/artica/tmp/package.split/$basename ]";
        $f[]="then";
        $f[]="\t/usr/bin/dialog --title \"\Zb\Z1ERROR! ERROR!\" --colors --infobox \"\Zb\Z1Installation failed\nUnable to download $basename file, please try again\"  0 0";
        $f[]="\texit 0";
        $f[]="fi";
    }
    $f[]="\ntouch /home/artica/tmp/package.splited";
    $f[]="exit 0\n";

    @file_put_contents("/tmp/downloader.sh",@implode("\n",$f));
    @chmod(0755,"/tmp/downloader.sh");

}

function recover(){
    chdir("/home/artica/tmp/package.split");
    system("cd /home/artica/tmp/package.split");
    system("/bin/cat full-package.* > /home/artica/tmp/package.tar.gz");
    if(!TARGZ_TEST_CONTAINER("/home/artica/tmp/package.tar.gz")){
        @unlink("/home/artica/tmp/package.tar.gz");
    }
    //system("rm -rf /home/artica/tmp/package.split");
    //system("rm -f /home/artica/tmp/package.splited");
    system("cd /root");
    chdir("/root");
}

function verify($md5,$basename){
    $smd5=md5_file("/home/artica/tmp/package.split/$basename");
    if($smd5==$md5){return true;}
    @unlink("/home/artica/tmp/package.split/$basename");

}
function TARGZ_TEST_CONTAINER($container){
    if(!is_file($container)){return false;}
    $size=@filesize($container);
    $resultsZ=array();
    if($size<100){return false;}
    $tar="/usr/bin/tar";
    $z="z";
    $cmdline="$tar {$z}tvf $container";

    exec("$cmdline 2>&1",$resultsZ);

    $c=0;
    foreach($resultsZ as $b){
       if($c>100){break;}

        if(preg_match("#Syntax error#",$b)){
            return false;
        }

        if(preg_match("#gzip: stdin: unexpected end of file#", $b)){
            return false;
        }

        if(preg_match("#gzip: stdin: not in gzip format#", $b)){
            return false;
        }

        if(preg_match("#does not look like a tar#", $b)){
            return false;
        }

        if(preg_match("#tar: Error#", $b)){
            return false;
        }
    }
    return true;

}