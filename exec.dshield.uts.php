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
include_once(dirname(__FILE__).'/ressources/class.categories.inc');
xtsart();
function xtsart(){

$unix=new unix();
$GLOBALS["TLS"]=array("uts_publicite"=>"publicite.tar.gz",
"uts_adult"=>"porn.tar.gz",
"uts_agressif"=>"agressif.tar.gz",
"uts_arjel"=>"arjel.tar.gz",
"uts_170"=>"associations_religieuses.tar.gz",
"uts_astrology"=>"astrology.tar.gz",
"uts_audio-video"=>"audio-video.tar.gz",
"uts_bank"=>"bank.tar.gz",
"uts_bitcoin"=>"bitcoin.tar.gz",
"uts_blog"=>"blog.tar.gz",
"uts_celebrity"=>"celebrity.tar.gz",
"uts_chat"=>"chat.tar.gz",
"uts_child"=>"child.tar.gz",
"uts_cleaning"=>"cleaning.tar.gz",
"uts_cooking"=>"cooking.tar.gz",
"uts_cryptojacking"=>"cryptojacking.tar.gz",
"uts_182"=>"dangerous_material.tar.gz",
"uts_dating"=>"dating.tar.gz",
"uts_ddos"=>"ddos.tar.gz",
"uts_dialer"=>"dialer.tar.gz",
"uts_drogue"=>"drugs.tar.gz",
"uts_download"=>"download.tar.gz",
"uts_188"=>"educational_games.tar.gz",
"uts_filehosting"=>"filehosting.tar.gz",
"uts_financial"=>"financial.tar.gz",
"uts_forums"=>"forums.tar.gz",
"uts_gambling"=>"gambling.tar.gz",
"uts_games"=>"games.tar.gz",
"uts_hacking"=>"hacking.tar.gz",
"uts_jobsearch"=>"jobsearch.tar.gz",
"uts_lingerie"=>"lingerie.tar.gz",
"uts_liste_blanche"=>"liste_blanche.tar.gz",
"uts_liste_bu"=>"liste_bu.tar.gz",
"uts_malware"=>"malware.tar.gz",
"uts_manga"=>"manga.tar.gz",
"uts_marketingware"=>"marketingware.tar.gz",
"uts_mixed_adult"=>"mixed_adult.tar.gz",
"uts_mobile-phone"=>"mobile-phone.tar.gz",
"uts_phishing"=>"phishing.tar.gz",
"uts_press"=>"press.tar.gz",
"uts_redirector"=>"redirector.tar.gz",
"uts_radio"=>"radio.tar.gz",
"uts_reaffected"=>"reaffected.tar.gz",
"uts_remote-control"=>"remote-control.tar.gz",
"uts_sect"=>"sect.tar.gz",
"uts_sexual_education"=>"sexual_education.tar.gz",
"uts_shopping"=>"shopping.tar.gz",
"uts_shortener"=>"shortener.tar.gz",
"uts_social_networks"=>"social_networks.tar.gz",
"uts_special"=>"special.tar.gz",
"uts_sports"=>"sports.tar.gz",
"uts_221"=>"strict_redirector.tar.gz",
"uts_222"=>"strong_redirector.tar.gz",
"uts_translation"=>"translation.tar.gz",
"uts_tricheur"=>"tricheur.tar.gz",
"uts_update"=>"update.tar.gz",
"uts_warez"=>"warez.tar.gz",
"uts_webmail"=>"webmail.tar.gz");

$tmp=$unix->FILE_TEMP();
$md5fileuei="http://dsi.ut-capitole.fr/blacklists/download/MD5SUM.LST";

$curl=new ccurl($md5fileuei);
if(!$curl->GetFile($tmp)){return false;}

$tt=explode("\n",@file_get_contents($tmp));
@unlink($tmp);
foreach ($tt as $line){

    if(preg_match("#^(.+?)\s+(.+?)\.tar\.gz#",$line,$re)){
        $SOURCES_MD5[trim("{$re[2]}.tar.gz")]=trim($re[1]);
    }
}

$rm=$unix->find_program("rm");
$tar=$unix->find_program("tar");
$TEMPDIR=$unix->TEMP_DIR();
$q=new postgres_sql();

foreach ($GLOBALS["TLS"] as $tablename=>$filename){
    if(!isset($SOURCES_MD5[$filename])){continue;}
    $oldmd5=@file_get_contents("/root/tls.$filename.md5");
    $uri="http://dsi.ut-capitole.fr/blacklists/download/$filename";
    $tmpfile=$unix->FILE_TEMP();
    if($oldmd5==$SOURCES_MD5[$filename]){continue;}
    $uri="http://dsi.ut-capitole.fr/blacklists/download/$filename";

    echo $uri."\n";

    $curl=new ccurl($uri);
    echo "DOwnloading in $TEMPDIR/$tablename\n";
    shell_exec("$rm -rf $TEMPDIR/$tablename");
    @mkdir("$TEMPDIR/$tablename",0755,true);
    if(!$curl->GetFile($tmpfile)){echo "DOwnloading $filename failed...\n";continue;}
    shell_exec("$tar -xf $tmpfile -C $TEMPDIR/$tablename/");
    $domainfile=FindSourcefile("$TEMPDIR/$tablename");
    if(!is_file($domainfile)){
        echo "Unbale to find domain in $TEMPDIR/$tablename\n";
        shell_exec("$rm -rf $TEMPDIR/$tablename");
        continue;
    }

    if(!$q->TABLE_EXISTS($tablename)){
        $q->CREATE_CATEGORY_TABLE($tablename);
    }
    $q->QUERY_SQL("DELETE FROM $tablename");
    $f=explode("\n",@file_get_contents($domainfile));
    $c=0;
    $ll=array();
    foreach ($f as $index=>$domain){
        $domain=trim($domain);
        if($domain==null){continue;}

        $ll[]="('$domain')";

        $c++;
        if(count($ll)>5000){
            $q->QUERY_SQL("INSERT INTO \"$tablename\" (sitename) VALUES ".@implode(",",$ll)." ON CONFLICT DO NOTHING");
            if(!$q->ok){echo $q->mysql_error;return;}
            $ll=array();
            echo "$filename $tablename $c items\n";
        }
    }

    if(count($ll)>0){
        $q->QUERY_SQL("INSERT INTO \"$tablename\" (sitename) VALUES ".@implode(",",$ll)." ON CONFLICT DO NOTHING");
        if(!$q->ok){echo $q->mysql_error;return;}
        $ll=array();
    }

    echo "$filename $tablename $c items\n";
    @file_put_contents("/root/tls.$filename.md5",$SOURCES_MD5[$filename]);

}



}

function FindSourcefile($directory){
    $unix=new unix();
    $dirs=$unix->dirdir($directory);

    foreach ($dirs as $direct=>$nothi){
        echo "Checking $direct/domains\n";
        if(is_file("$direct/domains")){return "$direct/domains";}
    }


}