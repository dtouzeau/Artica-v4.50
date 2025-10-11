<?php

$GLOBALS["VERBOSE"]=false;
if(isset($argv[1])) {
    if(preg_match("#--verbose#",@implode("",$argv))){
        ini_set('html_errors', 0);
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
        $GLOBALS["VERBOSE"]=true;
    }
}
if($GLOBALS["VERBOSE"]){echo "START...\n";}
$GLOBALS["BYPASS"]=true;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.mysql.catz.inc");
include_once("/usr/share/artica-postfix/ressources/class.ccurl.inc");
if($GLOBALS["VERBOSE"]){echo "new sockets(\n";}
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if($GLOBALS["VERBOSE"]){echo "heads_exec_root(\n";}
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if($GLOBALS["VERBOSE"]){echo "Parse ARGV\n";}
if(isset($argv[1])) {
    if ($argv[1]=="--retreive"){retreive_files();exit;}
    if($argv[1]=="--scan"){scandomains();exit;}
    if($argv[1]=="--syslog"){build_syslog();exit;}
    if($argv[1]=="--omapi"){omapi();exit;}

}
echo "Do you mean --retreive --checks --install --push --scan --syslog --omapi ?\n";
function retreive_files():bool{
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__.": START\n";}
    $q=new postgres_sql();
    $unix=new unix();
    $url="http://articatech.net/categorize.submit.php?list=yes";
    $curl=new ccurl($url);
    $curl->NoHTTP_POST=true;
    $USEREDIS=false;
    if(!$curl->get()){
        ev("HTTP error $url". $curl->error);
        return false;
    }

    if(is_file("/etc/init.d/redis-server")){
        $USEREDIS=true;
    }

    if($USEREDIS) {
        $redis = new Redis();

        try {
            $redis->connect('/var/run/redis/redis.sock');
        } catch (Exception $e) {
            ev("Redis error". $e->getMessage());
            squid_admin_mysql(0, "Redis error", $e->getMessage(), __FILE__, __LINE__);
            echo $e->getMessage() . "\n";
            return false;
        }

    }

    if(!preg_match("#<FILES>(.*?)</FILES>#",$curl->data,$re)){
        ev("$url -> FAILED -> <FILES>(.*?)</FILES> no matches");
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS cloud_categorize (
            domain varchar(255) PRIMARY KEY,
            created TIMESTAMP,
            updated TIMESTAMP)";
    $q->QUERY_SQL($sql);
    $ql=new mysql_catz();
    $files=unserializeb64($re[1]);
    $count=count($files);
    ev("Files to scan: $count");
    foreach ($files as $fname){
        ev("Scanning [$fname] ...");
        if($fname=="."){continue;}
        if($fname==".."){continue;}
        if($fname==null){continue;}
        $url="http://articatech.net/categorize-queries/$fname";
        ev("$fname: Url to scan: $url");
        $curl=new ccurl($url);
        $tmpfile=$unix->FILE_TEMP();
        if(!$curl->GetFile($tmpfile)){
            ev("$url -> FAILED -> ".$curl->error);
            echo "[FAILED]: $url\n";
        }
        $domains=unserializeb64(@file_get_contents($tmpfile));
        $c=0;
        ev("Domains to scan: ".count($domains));


        foreach ($domains as $domain){
            $domain=trim(strtolower($domain));
            if(strpos("   $domain","*")>0){continue;}

            if($USEREDIS) {
                $CACHE=intval($redis->get("DomainToInt:$domain"));
                if($CACHE>0){
                    ev("HIT REDIS: $domain");
                    continue;
                }
            }

            $date=date("Y-m-d H:i:s");
            $category=$ql->GET_CATEGORIES($domain);
            if($category>0){
                ev("HIT CATEGORY:$category: $domain");
                if($USEREDIS) {

                    $redis->set("DomainToInt:$domain",999);
                }
                continue;
            }
            $domainInsert=$domain;
            if(preg_match("#^(www|ww|mx|admin|magento|hostmaster|ns|ns1|webmail|mail|bbs|cpanel|webdisk|autodiscover|smtp|cpcalendars|cpcontacts)\.(.+)#",$domainInsert,$re)){
                $domainInsert=$re[2];
            }

            ev("ADD: $domainInsert");
            $q->QUERY_SQL("INSERT INTO cloud_categorize(domain,created) VALUES ('$domainInsert','$date') ON CONFLICT DO NOTHING");

            if(!$q->ok){
                ev("ERR: FATAL $q->mysql_error");
                _out("[INJECT]: FATAL $q->mysql_error");
                squid_admin_mysql("MySQL ERROR $q->mysql_error",$q->mysql_error,__FILE__,__LINE__);
                return false;
            }

            if($USEREDIS) {
                $redis->set("DomainToInt:$domain",999);
            }

            $c++;

        }
        ev("Remove: $fname");
        @unlink($tmpfile);
        _out("[articatech.net]: Ask to remove $fname");
        $url="http://articatech.net/categorize.submit.php?delete=$fname";
        $curl=new ccurl($url);
        $curl->NoHTTP_POST=true;
        $curl->get();
        if($c>0){
            ev("$c domains added in queue");
            _out("[INJECT]: $c domains added in queue.");
        }
    }


    return true;


}
function ev($text):bool{
    $lineToSave=date("Y/m/d H:i:s")." [UNCAT-SERVER]: $text";
    $f = @fopen("/var/log/dd-categorize.log", 'a');
    @fwrite($f, "$lineToSave\n");
    @fclose($f);
    return true;
}
function set_DomainToIntredis($strdomain,$id){
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        return null;
    }
    $redis->set("DomainToInt:$strdomain",$id,2880);
    $redis->close();
}

function omapi():bool{
    //unveiltech
    shell_exec("/etc/init.d/firehol stop");
    echo "OmaPI Start\n";
    $ql=new mysql_catz();
    $srcuri=trim(@file_get_contents("/etc/artica-postfix/omeapi.txt"));
    $c=0;$d=0;
    $q=new postgres_sql();
    $memcached=new lib_memcached();

    $GDCats=GDCats();
    $BadIcat=BadICat();
    $ForwardCat=ForwardCat();

    $sql="SELECT * FROM cloud_categorize ORDER BY updated ASC LIMIT 5000";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
    echo "Start LOOP\n";
    $i=0;
    while($ligne=@pg_fetch_assoc($results)){
        $i++;
        $updated=$ligne["updated"];
        if($updated<>null){continue;}
        $domain=$ligne["domain"];
        $stringDOM=$domain;
        $domainTEST=cleanDomain($domain);
        if($domain<>$domainTEST){
            $stringDOM="$domain --> $domainTEST";
        }

        if (ifSkip($domainTEST)){
            $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domainTEST'");
            continue;
        }

        if(isset($GLOBALS["ALREADYQUERY"][$domainTEST])){
            stampDomain($domain);
            continue;
        }
        $uri=str_replace("%s",$domainTEST,$srcuri);

        $curl=new ccurl($uri);
        $curl->NoHTTP_POST=true;
        if(!$curl->get()){
            _out("HTTP error on omapi ($domain)");
            squid_admin_mysql(0,"HTTP error on omapi ($domain)",null,__FILE__,__LINE__);
        }
        $GLOBALS["ALREADYQUERY"][$domainTEST]=true;
        $json=json_decode($curl->data);
        if(!property_exists($json,"icat")){
            echo "$i) $domain [ERROR] no icat\n";
            continue;
        }
        $catnum=$json->icat;
        $scat=$json->scat;
        if(isset($BadIcat[$catnum])){
            $d++;
            echo "$i) $stringDOM [SKIP] Bad icat $catnum ($scat)\n";
            stampDomain($domain);
            $memcached->saveKey("icatdone:$domain","bad_icat:$catnum",3600000);
            continue;
        }

        if(!isset($ForwardCat[$catnum])){
            $d++;
            echo "$i) $stringDOM, [UNKNOWN] icat $catnum ($scat)\n";
            stampDomain($domain);
            continue;
        }
        $table=$ForwardCat[$catnum];
        _out("ADD $stringDOM, icat $catnum ($scat) into \"$table\"");
        $c++;


        $memcached->saveKey("icatdone:$domain",$table,3600000);
        $q->QUERY_SQL("INSERT INTO $table (sitename) VALUES('$domainTEST') ON CONFLICT DO NOTHING");
        if(!$q->ok){_out($q->mysql_error);continue;}
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domain'");


    }

    if($d>0){
        _out("[CATEGORIZE]: $d sites are not categorized and keep it in queue");

    }
    if($c>0){
        _out("[CATEGORIZE]: $c sites categorized and removed from queue");
    }
    shell_exec("/etc/init.d/firehol start");
    return true;
}

function BadICat():array{
    $BadIcat[9]=true;
    $BadIcat[44]=true;
    $BadIcat[999]=true;
    $BadIcat[102]=true;
    $BadIcat[124]=true;
    $BadIcat[504]=true;
    $BadIcat[503]=true;
    $BadIcat[997]=true;
    return $BadIcat;
}

function stampDomain($domain):bool{
    $q=new postgres_sql();
    $date=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE cloud_categorize set updated='$date' WHERE domain='$domain'");
    return true;
}
function ifSkip($domain):bool{

    if(preg_match("#^[0-9\.]+$#",$domain)){
        return true;
    }

    $ql=new mysql_catz();
    $q=new postgres_sql();
    $category=$ql->GET_CATEGORIES($domain);
    if($category>0){
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domain'");
        return true;
    }

    if(preg_match("#^(track|xtrk)\.(.+)#",$domain)){
        $q->QUERY_SQL("INSERT INTO category_tracker (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        return true;
    }

    if(preg_match("#^mairie-#",$domain)){
        $q->QUERY_SQL("INSERT INTO category_governments (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        return true;

    }
    if(preg_match("#restaurant#",$domain)){
        $q->QUERY_SQL("INSERT INTO category_recreation_nightout (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        return true;

    }
    if(preg_match("#\.(xzblogs|blog2learn|getblogs|renewables-apps)\.(com|net)$#",$domain)){
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domain'");
        return true;
    }
    if(preg_match("#\.yaomen\.com\.cn$#",$domain)){
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domain'");
        return true;
    }
    if(preg_match("#\.(scgirls|vc115|a2b1p0|adultg)\.cn$#",$domain)){
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domain'");
        return true;
    }

    return false;
}

function scandomains():bool{

    $pidfile="/etc/artica-postfix/scandomains.pid";
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){
        return false;
    }

    $time=$unix->file_time_min($pidfile);
    if($time<10){return false;}

    @unlink($pidfile);
    @file_put_contents($pidfile,getmypid());

    shell_exec("/etc/init.d/firehol stop");
    $ql=new mysql_catz();
    $srcuri=trim(@file_get_contents("/etc/artica-postfix/omeapi.txt"));
    $c=0;$d=0;
    $q=new postgres_sql();
    $memcached=new lib_memcached();

    $GDCats=GDCats();
    $BadIcat=BadICat();
    $ForwardCat=ForwardCat();


    $sql="SELECT * FROM cloud_categorize";
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $domain=$ligne["domain"];
        $domainTEST=cleanDomain($domain);


        if(ifSkip($domainTEST)){
            $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domainTEST'");
            continue;
        }

        $value=$memcached->getKey("icatdone:$domain");
        if(strlen($value)>0){
            echo "$domain Memcached: $value SKIP\n";
            continue;
        }

        if(isset($GLOBALS["ALREADYQUERY"][$domainTEST])){continue;}
        $uri=str_replace("%s",$domainTEST,$srcuri);

        $curl=new ccurl($uri);
        $curl->NoHTTP_POST=true;
        if(!$curl->get()){
            _out("HTTP error on omapi ($domain)");
            squid_admin_mysql(0,"HTTP error on omapi ($domain)",null,__FILE__,__LINE__);
            continue;
        }
        $GLOBALS["ALREADYQUERY"][$domainTEST]=true;
        $json=json_decode($curl->data);
        if(!property_exists($json,"icat")){
            echo "$domain, no icat\n";
            continue;
        }
        $catnum=$json->icat;
        $scat=$json->scat;
        if(isset($BadIcat[$catnum])){
            $d++;
            echo "$domain, Bad icat $catnum ($scat)\n";
            $memcached->saveKey("icatdone:$domain","bad_icat:$catnum",3600000);
            continue;
        }

        if(!isset($ForwardCat[$catnum])){
            $d++;
            echo "$domain, unknown icat $catnum ($scat)\n";
            continue;
        }
        $table=$ForwardCat[$catnum];
        _out("ADD $domain, icat $catnum ($scat) into \"$table\"");
        $c++;


        $memcached->saveKey("icatdone:$domain",$table,3600000);
        $q->QUERY_SQL("INSERT INTO $table (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        if(!$q->ok){_out($q->mysql_error);continue;}
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain='$domain'");


    }

    if($d>0){
        _out("[CATEGORIZE]: $d sites are not categorized and keep it in queue");

    }
    if($c>0){
        _out("[CATEGORIZE]: $c sites categorized and removed from queue");
    }
    shell_exec("/etc/init.d/firehol start");
    return true;

}
function build_syslog():bool{
    $conf="/etc/rsyslog.d/00_uncategorized.conf";
    $md5_start=null;
    if(is_file($conf)){$md5_start=md5_file($conf);}

    $add_rules  = BuildRemoteSyslogs("uncategorized",'uncategorized');
    $h[]="if  (\$programname =='uncategorized') then {";
    $h[]=buildlocalsyslogfile("/var/log/uncategorized.log");
    if(strlen($add_rules)>3) {
        $h[] = $add_rules;
    }
    $h[]="& stop";
    $h[]="}";
    $h[]="";

    @file_put_contents($conf,@implode("\n", $h));
    $md5_end=md5_file($conf);
    if($md5_end<>$md5_start) {
        _out("Starting: Updating Syslog configuration...");
        shell_exec("/etc/init.d/rsyslog restart");
    }
    return true;

}
function _out($text):bool{
    echo "$text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("uncategorized", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function cleanDomain($domain):string{
    if(preg_match("#^(www|forms|cdn|fr|app|st|asset)\.(.+)#",$domain,$re)){
        $domain=$re[2];
    }
    if(preg_match("#^\.(.+)#",$domain,$re)){
        $domain=$re[1];
    }
    return $domain;
}

function GDCats():array{
    $GDCats = array(
        1 => 'Adult / Mature Content',
        3 => 'Pornography',
        4 => 'Sex Education',
        5 => 'Intimate Apparel / Swimsuit',
        6 => 'Nudity',
        7 => 'Extreme',
        9 => 'Illegal / Questionable / Scam',
        11 => 'Gambling',
        14 => 'Violence / Hate / Racism',
        15 => 'Weapons',
        16 => 'Abortion',
        17 => 'Hacking',
        18 => 'Phishing',
        20 => 'Arts / Entertainment',
        21 => 'Business / Economy',
        22 => 'Alternative Spirituality / Occult',
        23 => 'Alcohol',
        24 => 'Tobacco',
        25 => 'Drugs',
        26 => 'Child Pornography',
        27 => 'Education',
        29 => 'Cultural / Charitable Organizations',
        30 => 'Art / Culture',
        31 => 'Financial Services',
        32 => 'Brokerage / Trading',
        33 => 'Games',
        34 => 'Government / Legal',
        35 => 'Military',
        36 => 'Political / Activist Groups',
        37 => 'Health',
        38 => 'Computers / Internet',
        40 => 'Search Engines / Portals',
        43 => 'Virus / Malware / Spyware',
        44 => 'Spyware Effects',
        45 => 'Job Search / Careers',
        46 => 'News / Media',
        47 => 'Personals / Dating',
        49 => 'Reference',
        50 => 'Open Image / Media Search',
        51 => 'Chat / Instant Messaging',
        52 => 'Email',
        53 => 'Newsgroups / Forums',
        54 => 'Religion',
        55 => 'Social Networking',
        56 => 'Online Storage',
        57 => 'Remote Access Tools',
        58 => 'Shopping',
        59 => 'Auctions',
        60 => 'Real Estate',
        61 => 'Society / Daily Living',
        63 => 'Personal Pages / Blogs',
        64 => 'Restaurants / Dining / Food',
        65 => 'Sports / Recreation',
        66 => 'Travel',
        67 => 'Vehicles',
        68 => 'Humor / Jokes',
        71 => 'Software Downloads',
        72 => 'Pay to Surf',
        83 => 'Peer-to-Peer',
        84 => 'Audio / Video Clips',
        85 => 'Web Applications',
        86 => 'Proxy Avoidance',
        87 => 'For Kids',
        88 => 'Web Advertisements',
        89 => 'Web Hosting',
        90 => 'Unrated',
        92 => 'Suspicious',
        93 => 'Alternative Sexuality / Lifestyles',
        94 => 'LGBT',
        95 => 'Translation',
        96 => 'Non-viewable',
        97 => 'Content Servers',
        98 => 'Parked Domains',
        101 => 'Spam', // 501 -> 101
        102 => 'Potentially Unwanted Software',
        103 => 'Dynamic DNS Host',
        104 => 'URL Shorteners',
        105 => 'Email Marketing',
        106 => 'Greeting Cards',
        107 => 'Informational',
        108 => 'Information Security', // new BlueCoat
        109 => 'Internet Connected Devices', // new BlueCoat
        110 => 'Internet Telephony', // 502 -> 110
        111 => 'Online Meetings',
        112 => 'Media Sharing',
        113 => 'Radio / Audio Streams',
        114 => 'TV / Video Streams',
        116 => 'Cloud Infrastructure', // new BlueCoat
        117 => 'Cryptocurrency', // new BlueCoat
        118 => 'Piracy / Copyright', // new BlueCoat
        121 => 'Illegal Drugs', // new BlueCoat
        124 => 'Compromised Sites', // new BlueCoat

        503 => 'Warez',
        504 => 'Tracker',
        505 => 'Private IP Addresses',
        506 => 'Beauty / Fashion',
        507 => 'Marketing Services',
        508 => 'Ecology / Nature',
        509 => 'Animals / Pets',
        510 => 'Adult Social Networking',
        602 => 'Photo Searches',
        901 => 'Hardcore',                  // Unveiltech
        902 => 'Heuristic',                 // Unveiltech
        903 => 'Local Black List',    // Unveiltech
        904 => 'Local White List',    // Unveiltech
        994 => 'Filter Rules',              // Unveiltech
        995 => 'DDOS Attack',               // Unveiltech
        996 => 'SSL Security',              // Unveiltech
        997 => 'Dead Site (No DNS)',        // Unveiltech
        998 => 'Dead Site',
        999 => 'Unknown',
    );

    return $GDCats;

}

function ForwardCat(){
    $ForwardCat[1]="category_porn";
    $ForwardCat[3]="category_porn";
    $ForwardCat[4]="category_sexual_education";
    $ForwardCat[5]="category_sex_lingerie";
    $ForwardCat[6]="category_mixed_adult";
    $ForwardCat[11]="category_religion";
    $ForwardCat[14]="category_violence";
    $ForwardCat[15]="category_weapons";
    $ForwardCat[17]="category_hacking";
    $ForwardCat[18]="category_phishing";
    //$ForwardCat[20]="category_hobby_arts";
    $ForwardCat[21]="category_industry";
    $ForwardCat[22]="category_sect";
    $ForwardCat[23]="category_alcohol";
    $ForwardCat[24]="category_tobacco";
    $ForwardCat[27]="category_recreation_schools";
    $ForwardCat[29]="category_associations";
    //$ForwardCat[30]="category_hobby_arts";
    $ForwardCat[31]="category_financial";
    $ForwardCat[32]="category_stockexchange";
    $ForwardCat[33]="category_games";
    $ForwardCat[34]="category_governments";
    $ForwardCat[35]="category_weapons";
    $ForwardCat[36]="category_politic";

    $ForwardCat[37]="category_health";
    $ForwardCat[38]="category_science_computing";
    $ForwardCat[40]="category_searchengines";
    $ForwardCat[43]="category_malware";
    $ForwardCat[45]="category_jobsearch";
    $ForwardCat[46]="category_news";
    $ForwardCat[47]="category_dating";
    $ForwardCat[49]="category_dictionaries";
    $ForwardCat[50]="category_pictureslib";
    $ForwardCat[51]="category_chat";
    $ForwardCat[52]="category_webmail";
    $ForwardCat[53]="category_forums";
    $ForwardCat[54]="category_religion";
    $ForwardCat[55]="category_socialnet";
    $ForwardCat[56]="category_filehosting";
    $ForwardCat[57]="category_remote_control";
    $ForwardCat[58]="category_shopping";
    $ForwardCat[60]="category_finance_realestate";
    $ForwardCat[61]="category_society";
    $ForwardCat[63]="category_blog";
    $ForwardCat[64]="category_recreation_nightout";
    $ForwardCat[65]="category_recreation_sports";
    $ForwardCat[66]="category_recreation_travel";
    $ForwardCat[67]="category_automobile_cars";
    $ForwardCat[68]="category_recreation_humor";
    $ForwardCat[71]="category_downloads";
    $ForwardCat[83]="category_downloads";
    $ForwardCat[84]="category_audio_video";
    $ForwardCat[85]="category_webapps";
    $ForwardCat[86]="category_proxy";
    $ForwardCat[88]="category_publicite";
    $ForwardCat[89]="category_isp";
    $ForwardCat[97]="category_isp";
    $ForwardCat[98]="category_reaffected";
    $ForwardCat[92]="category_suspicious";

    $ForwardCat[101]="category_mailing";
    $ForwardCat[103]="category_dynamic";
    $ForwardCat[110]="category_webphone";
    $ForwardCat[111]="category_meetings";
    $ForwardCat[112]="category_webapps";
    $ForwardCat[113]="category_webradio";
    $ForwardCat[114]="category_movies";
    $ForwardCat[506]="category_recreation_wellness";
    $ForwardCat[901]="category_porn";
    return $ForwardCat;
}