<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.html2text.inc');


startx();

function startx(){

        $localmd5='/root/spam_blacklist_forum-sih.md5';


        $unix=new unix();
		$curl=new ccurl("https://www.forum-sih.fr/spam/spam_blacklist_forum-sih.txt");
		$targetpath=$unix->FILE_TEMP();
		if(!$curl->GetFile($targetpath)){
            return false;
        }

        $lastmd5=md5_file($localmd5);
        $Currentmd5=md5_file($targetpath);
        if($lastmd5==$Currentmd5){return;}

    $fp = @fopen($targetpath, "r");
    if(!$fp){
        if($GLOBALS["DEBUG_GREP"]){echo "$targetpath BAD FD\n";}
        return array();
    }
    $pos=new postgres_sql();
    $c=0;
    $t=array();
    $ttdoms=array();
    $family=new familysite();
    while(!feof($fp)){
        $line = trim(strtolower(fgets($fp)));
        $line=str_replace("\r\n", "", $line);
        $line=str_replace("\n", "", $line);
        $line=str_replace("\r", "", $line);

        if ($line == null) {
            continue;
        }
        if(preg_match("#(notification@facebook)\.#",$line)){continue;}
        if(preg_match("#(linkedin|groupemoniteur|groups\.yahoo|sogelink|\.gouv|showroomprive|cdiscount|\.booking|facebookmail)\.#",$line)){continue;}

        if (preg_match("#(.+?);#", $line, $re)) {$line = $re[1];}
        if(preg_match("#^\*\.(.+)#",$line,$re)){$line=$re[1];}

        if(strpos("$line","*")>0) {
            continue;
        }

        if(strpos("$line","@")==0) {
            $FAM = $family->GetFamilySites($line);
            if ($FAM == $line) {
                $ttdoms[] = $line;
            }
        }
        if(isset($ALR[$line])){continue;}
        $sdate=date("Y-m-d H:i:s");
        echo "$line\n";

        $pos->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES('$line','Imported from www.forum-sih.fr','$sdate') ON CONFLICT DO NOTHING");
        $ALR[$line]=true;
    }

    @file_put_contents($localmd5,$Currentmd5);
    @file_put_contents("/root/domains-to-add.txt",@implode("\n",$ttdoms));

}



