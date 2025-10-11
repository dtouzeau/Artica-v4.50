<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
$GLOBALS["FORCE"]=false;
if(preg_match("#--force#",@implode("",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--urlhaus"){urlhaus();exit;}
if($argv[1]=="--rescure"){rescure_me();exit;}
if($argv[1]=="--rpz"){compile_rpz();exit;}
if($argv[1]=="--upload"){upload_files();exit;}
if($argv[1]=="--tls1"){tls1_global();exit;}
if($argv[1]=="--rss"){rssaa419_org();exit;}

general();

function create_table(){
    $q=new postgres_sql();
    $sql="CREATE TABLE IF NOT EXISTS rpz_central ( www varchar(512) PRIMARY KEY)";
    $q->QUERY_SQL($sql);
}

function general(){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
    $pid=@file_get_contents($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
         exit();
    }

    @file_put_contents($pidfile, getmypid());

    $pidTimeEx=$unix->file_time_min($pidTime);
    if(!$GLOBALS["FORCE"]){
        if($pidTimeEx<60){
            $unix->ToSyslog("Waiting 60 minimal - current ({$pidTimeEx}Mn)");
            return;
        }
    }

    $AdguardFilters="https://raw.githubusercontent.com/AdguardTeam/AdguardFilters";
    $github="raw.githubusercontent.com";

    $pos=new postgres_sql();
    $rows=$pos->COUNT_ROWS_LOW("rpz_central");
    unveiltech_blockdoms();
    unveiltech_globaldoms();
    urlhaus();

    $array=array();
    $array["URL"]="https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts";
    general_file($array);

    $array=array();
    $array["URL"]="https://rescure.me/rescure_domain_blacklist.txt";
    $array["category"]="category_malware";
    general_file($array);

    $array=array();
    $array["URL"]="https://s3.amazonaws.com/lists.disconnect.me/simple_ad.txt";
    $array["category"]="category_publicite";
    general_file($array);


    $array=array();
    $array["URL"]="https://block.energized.pro/extensions/xtreme/formats/domains.txt";
    $array["category"]=null;
    general_file($array);

    $array=array();
    $array["URL"]="https://raw.githubusercontent.com/Yhonay/antipopads/master/hosts";
    $array["category"]="category_spyware";
    general_file($array);

    $array=array();
    $array["URL"]="$AdguardFilters/master/BaseFilter/sections/adservers.txt";
    $array["category"]="category_publicite";
    $array["ADBLOCK"]=true;
    general_file($array);

    $array=array();
    $array["URL"]="$AdguardFilters/master/SpywareFilter/sections/tracking_servers.txt";
    $array["category"]="category_trackers";
    $array["ADBLOCK"]=true;
    general_file($array);

    $array=array();
    $array["URL"]="https://$github/FadeMind/hosts.extras/master/add.Risk/hosts";
    $array["category"]="category_suspicious";
    general_file($array);

    $array=array();
    $array["URL"]="https://$github/blocklistproject/Lists/master/phishing.txt";
    $array["category"]="category_phishing";
    general_file($array);

    $array=array();
    $array["URL"]="https://phishing.army/download/phishing_army_blocklist.txt";
    $array["category"]="category_phishing";
    general_file($array);

    $array=array();
    $array["URL"]="https://$github/notracking/hosts-blocklists/master/domains.txt";
    $array["category"]="category_phishing";
    $array["DNSMASQ"]=true;
    general_file($array);

    $array=array();
    $array["URL"]="https://rpz.oisd.nl";
    $array["category"]=null;
    $array["RPZ"]=true;
    general_file($array);



    $rows2=$pos->COUNT_ROWS_LOW("rpz_central");
    $results=$rows2-$rows;
    echo "$results added rows\n";
    if($rows2>0){
        squid_admin_mysql(2,"RPZ:Compiling $rows2 new record");
        compile_rpz();
    }


}
function rescure_me(){
    $AdguardFilters="https://raw.githubusercontent.com/AdguardTeam/AdguardFilters";
    $github="raw.githubusercontent.com";

    $array=array();
    $array["URL"]="https://www.malwareworld.com/textlists/suspiciousDomains.txt";
    $array["category"]="category_suspicious";
    general_file($array);

}

function upload_files(){
    $maindir="/home/artica/rpz";
    $outgz="$maindir/rpz.gz";
    $indexfile="$maindir/rpz.txt";

    if(is_file($indexfile)) {
        ftp_upload($indexfile);
    }
    if(is_file($outgz)) {
        ftp_upload($outgz);
    }
}

function compile_rpz(){
    $unix=new unix();
    $maindir="/home/artica/rpz";
    $mainfile="$maindir/rpz-src.txt";
    $outfile="$maindir/rpz-dest.txt";
    $outgz="$maindir/rpz.gz";
    $indexfile="$maindir/rpz.txt";
    $curindex=unserialize(base64_decode(@file_get_contents($indexfile)));
    echo __LINE__."]  ------------ [COMPILE TABLE] ------------\n";

    $md5_sql=$curindex["md5_sql"];
    $q=new postgres_sql();
    if(!is_dir($maindir)){@mkdir($maindir,0755,true);}
    if(is_file($mainfile)){@unlink($mainfile);}
    @chown($maindir,"ArticaStats");
    @chmod($maindir,0755);

    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='gstatic.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.gvt2.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.gvt1.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.gvt3.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.msftncsi.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.googleapis.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='graph.facebook.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='googleapis.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='akamaihd.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='amazon.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='alibaba.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='aliexpress.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='youtube.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.youtube.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='wp.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='cloudfront.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='youporn.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='xp.apple.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE  www LIKE'ssl-images-amazon.%'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.microsoft.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.groupondata.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.ubuntu.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.doubleclick.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.php'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='amazonaws.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='trivago.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='shop.mango.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='skynet.be'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='banquepopulaire.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='msedge.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='promovacances.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='akamaized.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='mywot.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www= LIKE '%.mywot.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='blockeddomain.hosts'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='127.0.0.1 local'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.mozilla.org'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.itunes.apple.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='s3.amazonaws.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='mon-ip.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='societegenerale.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='adsafeprotected.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='tagcommander.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='cdn.tagcommander.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='static.adsafeprotected.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='privacy.trustcommander.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='facebook.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='connect.facebook.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='cdn77.org'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='officeapps-live.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='media.rtl.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='static.rtl.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='doubleclick.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='virustotal.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='virustotal.com.br'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='netdna-ssl.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.virustotal.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='swift.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='github.io'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='leboncoin.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.googleadservices.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='ads.google.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='analytics.google.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='thefunpost.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='leparisien.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='sfr.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='feedspot.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='consensu.org'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='cdscdn.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='apis.google.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='reverso.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.binance.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='fbcdn.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='booking.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='ccmbg.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='bfmtv.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='brightcove.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='brightcove.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.brightcove.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='edge.api.brightcove.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='hubvisor.io'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='players.brightcove.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='logrocket.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='lefigaro.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='nouvelobs.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='cookiefirst.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='ipinfo.io'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='hq3x.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='www.hq3x.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='videohdzog.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='lnkd.in'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='dmcdn.net'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='jwplayer.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='ladmedia.fr'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='blogspot.com'");
    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='oppo.com'");
  //  foreach ($badext as $ext){
    //    $q->QUERY_SQL("DELETE FROM rpz_central WHERE www LIKE '%.$ext'");
    //}


    $sql="COPY (SELECT www FROM rpz_central ORDER by www) TO '$mainfile'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        return false;
    }
    $md5_builded=md5_file($mainfile);
    if(!$GLOBALS["FORCE"]) {
        if ($md5_sql == $md5_builded) {
            @unlink($mainfile);
            echo "Same file... SKIP\n";
            return true;
        }
    }
    $curindex["md5_sql"]=$md5_builded;
    $in = @fopen($mainfile, "r");
    if (!$in) {echo "Unable to fopen $mainfile\n";@unlink($mainfile);return false;}

    if(is_file($outfile)){@unlink($outfile);}
    $out = fopen($outfile, 'wb');
    if(!$out){@unlink($mainfile);echo "Unable to fopen $outfile\n";return false;}

    $c=0;
    $DUPLICATE=array();
    while (!feof($in)){
        $line=@fgets($in);
        $line=trim($line);
        $linesrc=$line;
        if($line==null){continue;}
        if(substr($line,0,1)=="-"){
            echo "Remove $line\n";
            $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='$linesrc'");
            continue;
        }
        if(preg_match("#^(.+?)\.$#",$line,$re)){$line=$re[1];}
        $nextdomain=strtolower("$line");

        if(preg_match("#^[0-9\.]+\s+(.+)#",$nextdomain,$re)){
            $nextdomain=$re[1];
            echo "$linesrc > $nextdomain, UPDATE IT\n";
            $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='$linesrc'");
            $q->QUERY_SQL("INSERT INTO rpz_central (www) VALUES ('$nextdomain') ON CONFLICT DO NOTHING");
        }
        if(preg_match("#address=\/(.+?)\/0\.0\.0\.0#",$nextdomain,$re)){
            $nextdomain=$re[1];
            echo "$linesrc > $nextdomain, UPDATE IT\n";
            $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='$linesrc'");
            $q->QUERY_SQL("INSERT INTO rpz_central (www) VALUES ('$nextdomain') ON CONFLICT DO NOTHING");
       }
        if(preg_match("#\|\|(.+?)\^#",$nextdomain,$re)){
            $nextdomain=$re[1];
            echo "$linesrc > $nextdomain, UPDATE IT\n";
            $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='$linesrc'");
            $q->QUERY_SQL("INSERT INTO rpz_central (www) VALUES ('$nextdomain') ON CONFLICT DO NOTHING");
        }
        if(preg_match("#^(.+?)\s+cname#i",$nextdomain,$re)){
            $nextdomain=$re[1];
            echo "$linesrc > $nextdomain, UPDATE IT\n";
            $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='$linesrc'");
            $q->QUERY_SQL("INSERT INTO rpz_central (www) VALUES ('$nextdomain') ON CONFLICT DO NOTHING");
        }

        if(isset($DUPLICATE[$nextdomain])){continue;}
        if(count($DUPLICATE)>65000){$DUPLICATE=array();}
        $DUPLICATE[$nextdomain]=true;

        if(strlen($nextdomain)>254){
            echo "$nextdomain > 254, REMOVE IT\n";

            continue;
        }
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$nextdomain)){
            $q->QUERY_SQL("DELETE FROM rpz_central WHERE www='$linesrc'");
            continue;
        }


        $c++;

        fwrite($out, "local-zone: \"$nextdomain\" refuse\n");
    }
    fclose($in);
    fclose($out);
    echo "$outfile $c items done\n";
    if(is_file($outgz)){@unlink($outgz);}
    $unix->compress($outfile,$outgz);

    $curindex["md5_sql"]=$md5_builded;
    $curindex["md5_gz"]=md5_file($outgz);
    $curindex["compile_time"]=time();
    $curindex["items"]=$c;
    $curindex["rpz_size"]=filesize($outgz);

    @unlink($outfile);
    @unlink($mainfile);

    @file_put_contents($indexfile,base64_encode(serialize($curindex)));
    upload_files();
    return true;


}

function badext($domain){
    $badext=array("arc","arm","arm4","arm4l","arm4t","arm4tl","arm4tll","armv4","armv4l","arm5","arm5l","arm5n","armv5l","arm6","arm6l","arm64","armv6","armv6l","armv61","arm7","arm7l","armv7l","arm8","dbg","exploit","i4","i6","i486","i586","i686","kill","m68","m68k","mips","mips64","mpsl","mipsel","pcc","ppc","ppc2","ppc440","ppc440fp","powerpc","powerppc","powerpc-440fp","root","root32","sh","sh4","ssh4","spc","sparc","x32","x64","x86","op","hakka","Arbiter","cnc","MG","hehe","fuck","Execution","DankyDanky","Voltage","seize","rozxw","etherial","tru","samoura","doink","Sinix","leet","cow","uogpmegagay","Nakuma","orbitclient","0nezz","eagle","puss","k1337","SNOOPY","psysec","Ahrix0","TacoBellGodYo","switchware","zuapleq","kamiko","gang","snype","onion","leeteds","servicecheck","systemservice","temp","working","yeeto","servicechecker","flash","genisys","Heartless","Depression","vbrxmr","xtc");

    foreach ($badext as $ext){
        if(preg_match("#\.$ext$#",$domain)){return true;}
    }

    $badext=array("arc","arm","arm4","arm4l","arm4t","arm4tl","arm4tll","armv4","armv4l","arm5","arm5l","arm5n","armv5","armv5l","arm6","arm6l","arm64","armv6","armv6l","armv61","arm7","arm7l","armv7","armv7l","arm8","armv8","dbg","exploit","i4","i6","i486","i586","i686","kill","m68","m68k","mips","mips64","mpsl","mipsel","pcc","ppc","ppc2","ppc440","ppc440fp","powerpc","powerppc","powerpc-440fp","root","root32","sh","sh4","ssh4","spc","sparc","x32","x64","x86","wiz","apk","php");

    foreach ($badext as $ext){
        if(preg_match("#\.$ext$#",$domain)){return true;}
    }
    return false;
}

function general_file($array){
    $adblock=false;$dnsmasq=false;$rpz=false;$category_table=null;
    if(isset($array["ADBLOCK"])){$adblock=$array["ADBLOCK"];}
    if(isset($array["DNSMASQ"])){$dnsmasq=$array["DNSMASQ"];}
    if(isset($array["RPZ"])){$rpz=$array["RPZ"];}
    if(isset($array["category"])) {$category_table = $array["category"];}
    $url= $array["URL"];
    $md5=md5($url);
    $time_local="/root/$md5.time";
    $tmp_local="/root/$md5.tmp";
    echo __LINE__."]  ------------ [$url] (HEAD) ------------\n";
    $curl=new ccurl($url);
    $Infos=$curl->getHeaders();

    $filetime=$Infos["filetime"];
    if($filetime=="-1"){
        $filetime=$Infos["Content-Length"];
    }

    echo __LINE__."]  ------------ [$url] ($filetime) ------------\n";
    if(strlen($category_table)<5){$category_table=null;}
    $curtime=intval(@file_get_contents($time_local));


    if($filetime<>null) {
        if ($filetime == $curtime) {
            echo __LINE__ . "] $url SKIP time: $filetime same\n";
            return true;
        }
    }
    if(!$curl->GetFile($tmp_local)){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($url),$curl->error,__FILE__,__LINE__);
        return false;
    }
    $fp = @fopen($tmp_local, "r");

    if(!$fp){
        echo __LINE__."] $tmp_local Failed to open\n";
        squid_admin_mysql(0,"Unable to open $tmp_local",null,__FILE__,__LINE__);
        return false;
    }

    $f=array();$f1=array();
                    $catz=new mysql_catz();
    $pos=new postgres_sql();
    echo __LINE__."] Parsing...\n";
    $c=0;
    while(!feof($fp)) {
        $line = trim(fgets($fp));
        if($line==null){continue;}
        if(strpos(" $line","#")>0){continue;}
        if(strpos(" $line","/::")>0){continue;}
        if(strpos(" $line","*.")>0){continue;}
        if(strpos(" $line","localhost")>0){continue;}
        if(strpos(" $line","loopback")>0){continue;}
        if(strpos(" $line","127.0.0.1 local")>0){continue;}
        if(strpos(" $line","-mcastprefix")>0){continue;}
        if(strpos(" $line","-allnodes")>0){continue;}
        if(strpos(" $line","-localnet")>0){continue;}
        if(strpos(" $line","-allrouters")>0){continue;}
        if(strpos(" $line","-allhosts")>0){continue;}
        if(strpos(" $line","ip6-")>0){continue;}
        if(strpos(" $line","0.0.0.0 0.0.0.0")>0){continue;}
        if(strpos(" $line","broadcasthost")>0){continue;}
        if(strpos(" $line","blockeddomain")>0){continue;}

        $domain=trim(strtolower($line));
        $domain=str_replace("0.0.0.0 ","",$domain);


        if($adblock){
            if(!preg_match("#^\|\|(.+?)\^$#",$domain,$re)){
                echo "SKIP $domain / adblock ($url)\n";
                continue;}
            $domain=$re[1];
        }
        if($dnsmasq){
            if(!preg_match("#^address=\/(.+?)\/0#",$domain,$re)){echo "SKIP $domain / dnsmasq ($url)\n";continue;}
            $domain=$re[1];
        }
        if(preg_match("#address=\/(.+?)\/0\.0\.0\.0#",$domain,$re)){
            $domain=$re[1];
        }

        if($rpz){
            if(!preg_match("#^(.+?)\s+CNAME#i",$line,$re)){continue;}
            $domain=trim(strtolower($re[1]));
        }
        if(badext($domain)){echo "SKIP 'BADEXT' form $domain $url\n";continue;}
        if(substr($domain,0,1)=="-"){echo "SKIP '-' form $domain $url\n";continue;}
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$domain)){echo "SKIP 'IP' form $domain $url\n";continue;}
        if(preg_match("#^[0-9\.]+$#",$domain)){echo "SKIP IPADDR FOR $domain ($url)\n";continue;}
        $domain=clean_domain($domain);
        $domain2=$domain;
        $c++;
        $f[]="('$domain')";



        if(count($f)>2000){
            echo __LINE__."] Injecting $c...\n";
            $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");
            if(!$pos->ok){echo "$pos->mysql_error\n";}
            $f=array();
        }

    }
    if(count($f)>0){
        $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");
    }


    fclose($fp);
    @unlink($tmp_local);
    @file_put_contents($time_local,$filetime);
    return true;


}

function clean_domain($nextdomain){
    $nextdomain=strtolower("$nextdomain");
    if(preg_match("#^(.+?)\.$#",$nextdomain,$re)){$nextdomain=$re[1];}
    if(preg_match("#^[0-9\.]+\s+(.+)#",$nextdomain,$re)){$nextdomain=$re[1];}
    if(preg_match("#address=\/(.+?)\/0\.0\.0\.0#i",$nextdomain,$re)){$nextdomain=$re[1];}
    if(preg_match("#^(.+?)\s+CNAME#i",$nextdomain,$re)){$nextdomain=$re[1];}
    if(preg_match("#\|\|(.+?)\^#",$nextdomain,$re)){$nextdomain=$re[1];}
    return trim($nextdomain);
}

function urlhaus(){
    $url="https://urlhaus.abuse.ch/downloads/rpz/";
    $time_local="/root/urlhaus.abuse.ch.time";
    $tmp_local="/root/urlhaus.abuse.ch.tmp";
    $curl=new ccurl($url);
    $Infos=$curl->getHeaders();
    $filetime=$Infos["filetime"];
    $curtime=intval(@file_get_contents($time_local));
    if($filetime==$curtime){
        echo __LINE__."] $url SKIP\n";
        return true;
    }

    if(!$curl->GetFile($tmp_local)){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($url),$curl->error,__FILE__,__LINE__);
        return false;
    }
   shell_exec("cp $tmp_local /home/artica/download.lists/urlhaus.abuse.ch");
    $fp = @fopen($tmp_local, "r");
    if(!$fp){
        echo __LINE__."] $tmp_local Failed to open\n";
        squid_admin_mysql(0,"Unable to open $tmp_local",null,__FILE__,__LINE__);
        return false;
    }

    $f=array();$f1=array();
    $catz=new mysql_catz();


    $pos=new postgres_sql();
    while(!feof($fp)) {
        $line = trim(fgets($fp));
        if(!preg_match("#^(.+?)\s+CNAME#",$line,$re)){continue;}
        $domain=trim(strtolower($re[1]));
        if($domain=="testentry.rpz.urlhaus.abuse.ch"){continue;}
        $domain2=$domain;
        $f[]="('$domain')";

        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$domain2)){echo "SKIP 'IP' form $domain2 $url\n";continue;}
        if(preg_match("#^[0-9\.]+$#",$domain2)){echo "SKIP IPADDR FOR $domain2 ($url)\n";continue;}
        $domain2=clean_domain($domain2);


        if(preg_match("#^www\.(.+?)$#",$domain2,$ri)){
            $domain2=$ri[1];
        }

        if(count($f)>2000){
            $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");
            if(!$pos->ok){echo "$pos->mysql_error\n";}
            $f=array();
        }


    }
    if(count($f)>0){
        $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");
    }


    fclose($fp);
    @unlink($tmp_local);
    @file_put_contents($time_local,$filetime);
    return true;

}




function unveiltech_blockdoms(){
    create_table();
    $pos=new postgres_sql();
    $md5="http://cdn.unveiltech.ovh/cats/dnsbox-enginesblockdoms.md5.txt";
    $http_file="http://cdn.unveiltech.ovh/cats/dnsbox-enginesblockdoms.txt";
    $http_temp="/tmp/dnsbox-enginesblockdoms.txt";
    $md5_local="/root/dnsbox-enginesblockdoms.md5.txt";
    $md51=trim(@file_get_contents($md5_local));
    $curl=new ccurl($md5);
    if(!$curl->get()){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($md5_local),$curl->error,__FILE__,__LINE__);
        return false;
    }
    $data=trim($curl->data);
    if($md51==$data){
        echo "[" .__FUNCTION__."] SKIP SAME\n";
        return true;}
    echo "<$md51> <$data>\n";
    $curl=new ccurl($http_file);
    echo __LINE__."] Downloading $http_file\n";
    if(!$curl->GetFile($http_temp)){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($http_file),$curl->error,__FILE__,__LINE__);
        return false;
    }



    if(!is_dir("/home/artica/download.lists")){@mkdir("/home/artica/download.lists",0755);}
    shell_exec("cp $http_temp /home/artica/download.lists/unveiltech_blockdoms");

    $fp = @fopen($http_temp, "r");
    if(!$fp){
        echo __LINE__."] $http_temp Failed to open\n";
        squid_admin_mysql(0,"Unable to open $http_temp",null,__FILE__,__LINE__);
        return false;
    }
    $catz=new mysql_catz();
    $f=array();
    $f1=array();
    while(!feof($fp)) {
        $line = trim(fgets($fp));
        if(!preg_match("#local-zone:\s+\"(.+?)\" refuse#",$line,$re)){continue;}
        $domain=trim(strtolower($re[1]));
        if(substr($domain,0,1)=="-"){echo "SKIP '-' form $domain unveiltech_globaldoms\n";continue;}
        if(isset($GLOBALS["DONOTDETECTS"][$domain])){continue;}
        $f[]="('$domain')";

        $categry=$catz->GET_CATEGORIES($domain);
        if($categry==0){
            $f1[]="('$domain')";
        }

        if(count($f)>2000){
            $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");
            if(!$pos->ok){
                echo "$pos->mysql_error\n";
            }
            $pos->QUERY_SQL("INSERT INTO category_malware (sitename) VALUES " .@implode(",", $f1)." ON CONFLICT DO NOTHING");
            $f=array();

        }
    }
    if(count($f)>0){
        $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");

    }
    if(count($f1)>0) {
        $pos->QUERY_SQL("INSERT INTO category_malware (sitename) VALUES " . @implode(",", $f1) . " ON CONFLICT DO NOTHING");
    }

    fclose($fp);
    @file_put_contents($md5_local,$data);
    return true;

}

function unveiltech_globaldoms(){
    create_table();
    $pos=new postgres_sql();
    $md5="http://cdn.unveiltech.ovh/cats/dnsbox-globalblockdoms.md5.txt";
    $http_file="http://cdn.unveiltech.ovh/cats/dnsbox-globalblockdoms.txt";
    $http_temp="/tmp/dnsbox-globalblockdoms.txt";
    $md5_local="/root/dnsbox-globalblockdoms.md5.txt";
    $md51=trim(@file_get_contents($md5_local));
    $curl=new ccurl($md5);
    if(!$curl->get()){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($md5_local),$curl->error,__FILE__,__LINE__);
        return false;
    }
    $data=trim($curl->data);
    if($md51==$data){
        echo "[" .__FUNCTION__."] SKIP SAME\n";
        return true;}
    echo "<$md51> <$data>\n";
    $curl=new ccurl($http_file);
    echo __LINE__."] Downloading $http_file\n";
    if(!$curl->GetFile($http_temp)){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($http_file),$curl->error,__FILE__,__LINE__);
        return false;
    }

    if(!is_dir("/home/artica/download.lists")){@mkdir("/home/artica/download.lists",0755);}
    shell_exec("cp $http_temp /home/artica/download.lists/unveiltech_globaldoms");

    $fp = @fopen($http_temp, "r");
    if(!$fp){
        echo __LINE__."] $http_temp Failed to open\n";
        squid_admin_mysql(0,"Unable to open $http_temp",null,__FILE__,__LINE__);
        return false;
    }
    $GLOBALS["DONOTDETECTS"]=unserialize(@file_get_contents("/home/artica/donot.detects"));
    $catz=new mysql_catz();
    $f=array();
    $f1=array();
    while(!feof($fp)) {
        $line = trim(fgets($fp));
        if(!preg_match("#local-zone:\s+\"(.+?)\" refuse#",$line,$re)){continue;}
        $domain=trim(strtolower($re[1]));
        if(isset($GLOBALS["DONOTDETECTS"][$domain])){continue;}
        if(substr($domain,0,1)=="-"){echo "SKIP '-' form $domain unveiltech_globaldoms\n";continue;}
        $f[]="('$domain')";


        $category=$catz->GET_CATEGORIES($domain);
        if($category==0){
            $f1[]="('$domain')";
        }

		if(count($f)>2000){
            $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");
            if(!$pos->ok){
                echo "$pos->mysql_error\n";
            }
            if(count($f1)>0) {
                $pos->QUERY_SQL("INSERT INTO category_malware (sitename) VALUES " . @implode(",", $f1) . " ON CONFLICT DO NOTHING");
                $f = array();
                $f1 = array();
            }

        }
    }
    if(count($f)>0){
        $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $f)." ON CONFLICT DO NOTHING");

    }
    if(count($f1)>0){
        $pos->QUERY_SQL("INSERT INTO category_malware (sitename) VALUES " .@implode(",", $f1)." ON CONFLICT DO NOTHING");
    }
    fclose($fp);
    @file_put_contents($md5_local,$data);
    return true;

}

function ftp_upload($localfile){
    $localfile_src=$localfile;
    $basename=basename($localfile);
    $unix=new unix();
    $curl=$unix->find_program("curl");

    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));
    $UfdbCatsUploadFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPPassive"));
    $UfdbCatsUploadFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPTLS"));
    $servlog="$UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv";
    if($UfdbCatsUploadFTPDir==null){$UfdbCatsUploadFTPDir="/";}
    $tmpfile=$unix->FILE_TEMP();

    $proto="ftp";
    $file = basename($localfile);
    if($UfdbCatsUploadFTPTLS==1){$proto="ftps";}

    $cmd[]="$curl";
    $cmd[]="-T $localfile";
    if($UfdbCatsUploadFTPusr<>null){
        $UfdbCatsUploadFTPpass=$unix->shellEscapeChars($UfdbCatsUploadFTPpass);
        $cmd[]="--user $UfdbCatsUploadFTPusr:$UfdbCatsUploadFTPpass";
    }
    $cmd[]="--ftp-create-dirs";
    $cmd[]="$proto://$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$file";
    $cmd[]=">$tmpfile 2>&1";

    $cmdline=@implode(" ",$cmd);
    shell_exec($cmdline);


    shell_exec($cmdline);
    $data=@file_get_contents($tmpfile);
    $infos=@explode("\n",$data);
    @unlink($tmpfile);
    $error_curl=compile_categories_upload_curl($infos);

    $slog[]="PUT: $localfile to $UfdbCatsUploadFTPDir/$file";
    if($error_curl<>null){
        echo "Failed to upload $localfile to $UfdbCatsUploadFTPserv $error_curl\n";
        $slog[]=$error_curl;
        $slog[]="***********************\n";
        squid_admin_mysql(0, "{failed} to upload $file to $servlog", @implode("\n",$slog),
            __FILE__,__LINE__);
        return false;
    }
    return true;
}

function tls1_global(){

    $md5_local="/root/tsl1.md5.txt";
    $OLD_MAIN=unserialize(@file_get_contents($md5_local));

    $index="http://dsi.ut-capitole.fr/blacklists/download/MD5SUM.LST";
    $curl=new ccurl($index);
    if(!$curl->get()){
        echo __LINE__."] $curl->error\n";
        squid_admin_mysql(0,"Unable to download " . basename($md5_local),$curl->error,__FILE__,__LINE__);
        return false;
    }

    $data=explode("\n",$curl->data);
    foreach ($data as $line){
        $line=trim($line);
        if(!preg_match("#^(.+?)\s+(.+?)\.tar\.gz$#",$line,$re)){continue;}
        $fname=$re[2].".tar.gz";
        $smd5=$re[1];
        $NEW_MAIN[$fname]=$smd5;
    }

    $translates["ads.tar.gz"]="category_publicite";
    $translates["adult.tar.gz"]="category_porn";
    $translates["child.tar.gz"]="category_children";
    $translates["celebrity.tar.gz"]="category_celebrity";
    $translates["cooking.tar.gz"]="category_hobby_cooking";
    $translates["gambling.tar.gz"]="category_gamble";
    $translates["games.tar.gz"]="category_games";
    $translates["lingerie.tar.gz"]="category_sex_lingerie";
    $translates["malware.tar.gz"]="category_malware";
    $translates["manga.tar.gz"]="category_manga";
    $translates["mixed_adult.tar.gz"]="category_mixed_adult";
    $translates["dating.tar.gz"]="category_dating";
    $translates["bank.tar.gz"]="category_finance_banking";
    $translates["astrology.tar.gz"]="category_astrology";
    $translates["associations_religieuses.tar.gz"]="category_religion";
    $translates["sports.tar.gz"]="category_recreation_sports";
    $translates["vpn.tar.gz"]="category_proxy";
    $translates["radio.tar.gz"]="category_webradio";
    $translates["remote-control.tar.gz"]="category_remote_control";
    $translates["financial.tar.gz"]="category_financial";
    $translates["phishing.tar.gz"]="category_phishing";
    $translates["jobsearch.tar.gz"]="category_jobsearch";
    $translates["press.tar.gz"]="category_news";
    $translates["proxy.tar.gz"]="category_proxy";
    $rpz["ads.tar.gz"]=true;
    $rpz["malware.tar.gz"]=true;
    $rpz["phishing.tar.gz"]=true;


    foreach ($translates as $srcfile=>$tablename){
        if(!isset($OLD_MAIN[$srcfile])){$OLD_MAIN[$srcfile]=null;}
        if($OLD_MAIN[$srcfile]==$NEW_MAIN[$srcfile]){echo "UTS: SKIP $srcfile\n";continue;}
        echo "UTLS: Analyze $srcfile\n";
        $add_rpz=false;
        if(isset($rpz[$srcfile])){$add_rpz=true;}
        if(!tls1_download($srcfile,$tablename,$add_rpz)){continue;}
        $OLD_MAIN[$srcfile]=$NEW_MAIN[$srcfile];
        @file_put_contents($md5_local,serialize($OLD_MAIN));
    }

    @file_put_contents($md5_local,serialize($OLD_MAIN));
    return true;


}

function tls1_download($srcfile,$tablename,$rpz=false){
    $srcdir=str_replace(".tar.gz","",$srcfile);
    $url="http://dsi.ut-capitole.fr/blacklists/download/$srcfile";
    $curl=new ccurl($url);
    $dirpath="/root/$srcfile";
    $rmline="/bin/rm -rf $dirpath";

    if(!is_dir($dirpath)){@mkdir($dirpath,0755,true);}
    if(!$curl->GetFile("$dirpath/$srcfile")){
        echo "UTLS: $srcfile ERROR: $url $curl->error\n";
        shell_exec($rmline);
        return false;
    }
    echo "UTLS: $srcfile Extracting $dirpath/$srcfile\n";
    shell_exec("/bin/tar xf $dirpath/$srcfile -C $dirpath/");

    $dbsrc="$dirpath/$srcdir/domains";
    if(!is_file($dbsrc)){
        echo "UTLS: $srcfile $dbsrc missing !";
        shell_exec($rmline);
        return false;
    }
    echo "UTLS: $srcfile $dbsrc Open\n";
    $in = @fopen($dbsrc, "r");
    if (!$in) {echo "UTLS: $srcfile Unable to fopen $dbsrc\n";
        shell_exec($rmline);
        return false;
    }
    $sql_rpz=array();
    $catz=new mysql_catz();
    $pos=new postgres_sql();
    $ADD=0;$e=0;
    while (!feof($in)){
        $line=@fgets($in);
        $line=trim($line);
        $e++;
        if($line==null){continue;}
        if($rpz){
            $sql_rpz[]="('$line')";
            if(count($sql_rpz)>2000){
                $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " .@implode(",", $sql_rpz)." ON CONFLICT DO NOTHING");
                if(!$pos->ok){
                    echo "$pos->mysql_error\n";
                    shell_exec($rmline);
                    fclose($in);
                    return false;
                }
                $sql_rpz=array();
            }
        }

        $category = $catz->GET_CATEGORIES($line);
        if ($category > 0) {continue;}
        $date=date("Y-m-d H:i:s");
        echo "UTLS: $date $srcfile ADD $line ($e)\n";
        if(preg_match("#^[0-9\.]+$#",$line)){$line=ip2long($line).".addr";}
        $f1[] = "('$line')";
        $ADD++;
        if(count($f1)>500){
            $pos->QUERY_SQL("INSERT INTO $tablename (sitename) VALUES " .@implode(",", $f1)." ON CONFLICT DO NOTHING");
            $f1=array();
        }

        $c++;
    }

    fclose($in);
    shell_exec($rmline);
    if(count($f1)>0){
        $pos->QUERY_SQL("INSERT INTO $tablename (sitename) VALUES " .@implode(",", $f1)." ON CONFLICT DO NOTHING");
    }

    if($rpz) {
        if (count($sql_rpz) > 0) {
            $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES " . @implode(",", $sql_rpz) . " ON CONFLICT DO NOTHING");
        }
    }

    $date=date("Y-m-d H:i:s");
    echo "UTLS: $date $srcfile FINISH ADD $ADD  ($e rows)\n";

   if($ADD>0){
       squid_admin_mysql(2,"Success $ADD new domain to $tablename",null,__FILE__,__LINE__);
   }
   return true;


}

function rssaa419_org(){

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
    $pid=@file_get_contents($pidfile);
    if($unix->process_exists($pid)){return false;}

    @mkdir("/home/artica/download.lists",0755);
    $pos=new postgres_sql();
    $url="https://www.aa419.org/rss.php";
    $curl=new ccurl($url);
    $curl->get();
    $f=explode("\n",$curl->data);
    @file_put_contents("/home/artica/download.lists/rssaa419_org",$curl->data);
    $catz=new mysql_catz();
    foreach ($f as $line){
        if(!preg_match("#description.*?URL:\s+(.+?)($|\s+)#",$line,$re)){continue;}
        $URI=$re[1];
        $arrayURI=parse_url($URI);
        $hostname=$arrayURI["host"];
        if(strpos($hostname,":")>0){
            $tr=explode(":",$hostname);
            $hostname=$tr[0];
        }
        if(preg_match("^www\.(.+)#",$hostname,$re)){$hostname=$re[1];}
        if($catz->GET_CATEGORIES($hostname)==0) {
            $pos->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES ('$hostname') ON CONFLICT DO NOTHING");
        }
        $pos->QUERY_SQL("INSERT INTO rpz_central (www) VALUES ('$hostname') ON CONFLICT DO NOTHING");
    }


}

function compile_categories_upload_curl($array):string{

    foreach ($array as $line){
        if(preg_match("#curl:.*?([0-9]+)\)\s+(.+)#",$line,$re)){
            return "Error {$re[1]} {$re[2]}";
        }

        if(preg_match("#rsync error:(.+)#",$line,$re)){
            return "Error {$re[1]}";
        }

        echo "$line\n";
    }
    return "";
}






