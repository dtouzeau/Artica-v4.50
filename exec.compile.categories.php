<?php
$GLOBALS["FAILED_UPLOADED"]=0;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["CLUSTER_SINGLE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.openssl.aes.inc");
include_once(dirname(__FILE__)."/ressources/class.ftp.client.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(isset($argv[1])){
    if($argv[1]=="--remove-all"){remove_all_categories();exit;}
    if($argv[1]=="--repair"){repair();exit;}
    if($argv[1]=="--single"){$GLOBALS["CLUSTER_SINGLE"]=true;compile_single_category($argv[2]);exit;}
    if($argv[1]=="--index"){create_index();exit;}
    if($argv[1]=="--cleand"){clean_doublons_danger();exit;}
    if($argv[1]=="--clean-ads"){clean_doublons_adv();exit;}
    if($argv[1]=="--clean-ads-revert"){clean_doublons_other_to_adv();exit;}
    if($argv[1]=="--clean-phishing"){clean_phishing();exit;}
    if($argv[1]=="--clean-porn"){clean_doublons_porn();exit;}
    if($argv[1]=="--clean-sect"){clean_sect();exit;}
    if($argv[1]=="--clean-porn-desc"){CleanPornOpposite();exit;}
    if($argv[1]=="--clean-trackers"){clean_doublons_trackers();exit;}
    if($argv[1]=="--clean-malwares"){clean_doublons_malwares();exit;}
    if($argv[1]=="--clean-suspicious"){clean_doublons_suspicious();exit;}
    if($argv[1]=="--clean-it"){clean_doublons_it();exit;}
    if($argv[1]=="--clean-proxy"){clean_suspicious("category_proxy");exit;}
    if($argv[1]=="--clean-phishing"){clean_phishing();exit;}
    if($argv[1]=="--suspect-it"){clean_suspicious("category_science_computing");exit;}
    if($argv[1]=="--suspect-porn"){clean_suspicious("category_porn");exit;}
    if($argv[1]=="--suspect-video"){clean_suspicious("category_audio_video");exit;}
    if($argv[1]=="--smooth"){compile_new_categories();exit;}
    if($argv[1]=="--clean"){clean_suspicious($argv[2]);exit;}
    if($argv[1]=="--clean-all"){clean_suspicious_all($argv[2]);exit;}
    if($argv[1]=="--ftp"){upload_category($argv[2]);exit;}
    if($argv[1]=="--rbl"){exit;}
    if($argv[1]=="--rbl-ftp"){compile_categories_rbl_ftp();exit;}
    if($argv[1]=="--catlist"){compile_catlist();exit;}
    if($argv[1]=="--clean-local"){clean_local_db();exit;}
    if($argv[1]=="--backup"){exit;}
    if($argv[1]=="--all"){compile_all();exit;}
    if($argv[1]=="--visits"){transfert_visits();exit;}
    if($argv[1]=="--syslog"){syslog_config();exit;}
    if($argv[1]=="--reaffected"){clean_reaffected();exit;}
    if($argv[1]=="--mkt"){clean_mkt();exit;}
    if($argv[1]=="--phish2"){clean_doublons_phishing();exit;}
    if($argv[1]=="--porn2"){clean_doublons_porn2();exit;}


}

echo "Compile all categories...\n";
compile_all();


function build_progress($text,$pourc){
    $cachefile=PROGRESS_DIR."/ufdbcat.compile.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},{$pourc}% $text...\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}
function build_progress_single($text,$pourc){
    $cachefile=PROGRESS_DIR."/ufdbguard.compile.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},{$pourc}% $text...\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}
function xsyslog($text):bool{
    if(!function_exists("syslog")){return false;}
    openlog("categories-update", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function get_crc_file($fname):string{
    if(!is_file($fname)){return "";}
    if(function_exists("crc32_file")) {
        return crc32_file($fname);
    }
    return md5_file($fname);

}
function syslog_config():bool{
    $old_fname="/etc/rsyslog.d/webfiltering-categories.conf";
    $fname="/etc/rsyslog.d/01_webfiltering-categories.conf";
    if(is_file($old_fname)){@unlink($old_fname);}

    $md5 = get_crc_file($fname);

    $add_rules = BuildRemoteSyslogs("categories","update");

    $f[]="if  (\$programname =='categories-update') then {";
    $f[]="\t-/var/log/webfiltering-categories.log";
    $f[]=$add_rules;
	$f[]="\t& stop";
    $f[]="}\n";
    @file_put_contents($fname,@implode("\n",$f));
    $md52 = get_crc_file($fname);

    if($md52<>$md5) {
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }
    return true;
}


function remove_all_categories(){
    $q=new postgres_sql();
    build_progress("{remove_all_categories}",5);

    $tables=$q->LIST_TABLES("proxydb");

    foreach ($tables as $tablename=>$true){
        if(!preg_match("#^category_#", $tablename)){continue;}
        build_progress("{remove} $tablename",15);
        $q->QUERY_SQL("DROP TABLE $tablename");
    }

    build_progress("{remove_all_categories}",30);
    $q->QUERY_SQL("DROP TABLE personal_categories");

    build_progress("{create_tables}",50);
    $catz=new categories();
    $catz->initialize();
    @unlink("/etc/artica-postfix/settings/Daemons/UTSCacheFile");
    build_progress("{remove_all_categories} {done}",100);
}

function repair(){
    $catz=new categories();
    $catz->initialize();

}
function transfert_visits():bool{
    $sql="SELECT zdate,familysite FROM not_categorized LIMIT 500";
    $q=new postgres_sql();
    $qb=new mysql_catz();
    $qlp=new mysql_squid_builder();
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        return false;
    }
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        squid_admin_mysql(0,"Redis error",$e->getMessage(),__FILE__,__LINE__);
        echo $e->getMessage() . "\n";
        return false;
    }



    $BAD["co.nf"]=true;


    while ($ligne = pg_fetch_assoc($results)) {
        $zdate=$ligne["zdate"];
        $domain=$ligne["familysite"];

        if(isset($BAD[$domain])){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }
        if(preg_match("#^\.#",$domain)){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }

        if(!preg_match("#^(.+?)\.(.+)#",$domain)){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }

        if(strpos(" $domain","#")>0){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }
        if(strpos(" $domain","/")>0){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }
        if(strpos(" $domain","|")>0){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }
        if(strpos(" $domain","*")>0){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }
        if(strpos(" $domain",":")>0){
            echo "$domain [CORRUPTED] ".__LINE__."\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }

        $CACHE=intval($redis->get("DomainToInt:$domain"));
        if($CACHE>0) {
            echo "$domain [HIT]\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }


        $category_id=$qb->GET_CATEGORIES($domain);

        if($category_id>0){
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            if(!$q->ok) {
                echo $q->mysql_error . "\n";
                return false;
            }
            $redis->set("DomainToInt:$domain",999);
            echo "$domain [$category_id]: SKIP\n";
            continue;

        }

        if(preg_match("#^[0-9\.]+$#",$domain)){
            echo "$domain [IPADDR]\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
            continue;
        }

        echo "$domain -> RESOLVE: ";

        if(!Resolve($domain)){
            echo "FAILED.1 ";
            if(preg_match("#^www\.#",$domain)){

                echo "DEFINITIVELY FAILED  [REAFFECTED]\n";
                $redis->set("DomainToInt:$domain",999);
                $qlp->categorize($domain,112);
                $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
                continue;
            }
            if(!Resolve("www.$domain")){
                if(not_resolved_porn($domain)){continue;}
                echo "FAILED +www [REAFFECTED]\n";
                $redis->set("DomainToInt:$domain",999);
                $qlp->categorize($domain,112);
                $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
                continue;
            }


        }
        echo "= OK\n";

        $q->QUERY_SQL("INSERT INTO cloud_categorize(domain,created) VALUES ('$domain','$zdate') ON CONFLICT DO NOTHING");
        $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$domain'");
    }

    return true;

}
function not_resolved_porn($domain):bool{
    if(!preg_match("#(sex|asian|amateur|bitch|gangbang|orgy|party|parties|massage|lesbian|orgasm|couple|porn|teen|virgin|young|tube|girl|hentai|hot|doll|zoophil|dick|slut|voyeur|cam|fuck|adult|japanese)#",$domain)){
        return false;
    }
    $qlp=new mysql_squid_builder();
    echo "[PORN]\n";
    $qlp->categorize($domain,109);
    return true;
}

function Resolve($domain):bool{
    $dnsA="8.8.8.8";
    $rs = new Net_DNS2_Resolver(array('nameservers' => array($dnsA)));
    $rs->timeout = 5;

    try {
        $result = $rs->query($domain, "A");
    } catch(Net_DNS2_Exception $e) {
        if(preg_match("#The domain name referenced#",$e->getMessage())){
            return false;
        }

        echo "ERROR DNS -->" . $e->getMessage() . "\n";
        return true;
    }

    if(count($result->answer)==0){
        return false;
    }

    foreach($result->answer as $record){
        if($record->type=="CNAME"){return true;}
        if(preg_match("#^[0-9\.]+$#",$record->address)){return true;}
        echo "Name: {$record->name}, type: {$record->type}, address: {$record->address} TTL: {$record->ttl}\n";


    }

    return true;

}

function clean_doublons_suspicious():bool{
    $dests_tables[]="category_society";
    $dests_tables[]="category_tracker";
    $dests_tables[]="category_shopping";
    $dests_tables[]="category_publicite";
    $dests_tables[]="category_health";

    $q=new postgres_sql();
    $TableToClean="category_suspicious";
    $c=0;
    foreach ($dests_tables as $to_table) {
        echo "checking source $to_table\n";
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( source $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from $TableToClean\n";
    return true;
}
function clean_doublons_phishing():bool{
    $dests_tables[]="category_tracker";
    $dests_tables[]="category_publicite";

    $q=new postgres_sql();
    $TableToClean="category_phishing";
    $c=0;
    foreach ($dests_tables as $to_table) {
        echo "checking source $to_table\n";
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( source $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from $TableToClean\n";
    return true;
}
function clean_doublons_porn2():bool{
    $dests_tables[]="category_tracker";
    $dests_tables[]="category_publicite";

    $q=new postgres_sql();
    $TableToClean="category_porn";
    $c=0;
    foreach ($dests_tables as $to_table) {
        echo "checking source $to_table\n";
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( source $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from $TableToClean\n";
    return true;
}
function clean_doublons_malwares():bool{

    $dests_tables[]="category_society";
    $dests_tables[]="category_tracker";
    $dests_tables[]="category_shopping";
    $dests_tables[]="category_publicite";
    $dests_tables[]="category_health";

    $q=new postgres_sql();
    $TableToClean="category_malware";
    $c=0;
    foreach ($dests_tables as $to_table) {
        echo "checking source $to_table\n";
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( source $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from $TableToClean\n";
    return true;
}

function clean_sect():bool{
    $dests_tables[]="category_religion";

    $q=new postgres_sql();
    $TableToClean="category_sect";
    $c=0;
    foreach ($dests_tables as $to_table) {
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( FROM $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from $TableToClean\n";
    return true;

}

function clean_doublons_trackers():bool{
    $dests_tables[]="category_science_computing";
    $dests_tables[]="category_industry";
    $dests_tables[]="category_society";
    $dests_tables[]="category_smartphones";
    $dests_tables[]="category_shopping";
    $dests_tables[]="category_publicite";
    $dests_tables[]="category_culture";
    $dests_tables[]="category_dangerous_material";
    $dests_tables[]="category_dating";
    $dests_tables[]="category_books";
    $dests_tables[]="category_clothing";
    $dests_tables[]="category_medical";
    $dests_tables[]="category_smartphones";
    $dests_tables[]="category_press";
    $dests_tables[]="category_recreation_schools";
    $dests_tables[]="category_recreation_nightout";
    $dests_tables[]="category_recreation_sports";
    $dests_tables[]="category_finance_realestate";
    $dests_tables[]="category_financial";

    $q=new postgres_sql();
    $TableToClean="category_tracker";
    $c=0;
    foreach ($dests_tables as $to_table) {
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( FROM $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from $TableToClean\n";
    return true;
}

function clean_doublons_porn(){
    $dests_tables[]="category_science_computing";
    $dests_tables[]="category_smallads";
    $dests_tables[]="category_microsoft";
    $dests_tables[]="category_facebook";
    $dests_tables[]="category_google";
    $dests_tables[]="category_society";
    $dests_tables[]="category_associations";
    $dests_tables[]="category_governments";
    $dests_tables[]="category_filehosting";
    $dests_tables[]="category_industry";
    $dests_tables[]="category_shopping";
    $dests_tables[]="category_webapps";
    $dests_tables[]="category_police";
    $dests_tables[]="category_luxury";
    $dests_tables[]="finance_moneylending";
    $dests_tables[]="category_finance_banking";
    $dests_tables[]="housing_doityourself";
    $dests_tables[]="category_housing_builders";
    $dests_tables[]="category_science_chemistry";
    $dests_tables[]="category_sex_lingerie";
    $dests_tables[]="category_sslsites";
    $dests_tables[]="category_tobacco";
    $dests_tables[]="category_weapons";
    $dests_tables[]="category_webphone";
    $dests_tables[]="category_webradio";
    $dests_tables[]="category_womanbrand";
    $dests_tables[]="category_meetings";
    $dests_tables[]="category_apple";
    $dests_tables[]="category_youtube";
    $dests_tables[]="category_redirector";
    $dests_tables[]="category_drugs";
    $dests_tables[]="category_forums";
    $dests_tables[]="category_jobtraining";
    $dests_tables[]="category_jobsearch";
    $dests_tables[]="category_paytosurf";
    $dests_tables[]="category_hobby_arts";
    $dests_tables[]="category_hacking";
    $dests_tables[]="category_green";
    $dests_tables[]="category_governments";
    $dests_tables[]="category_gamble";
    $dests_tables[]="category_filehosting";
    $dests_tables[]="category_industry";
    $dests_tables[]="category_mixed_adult";
    $dests_tables[]="category_automobile_boats";
    $dests_tables[]="category_automobile_planes";
    $dests_tables[]="category_bicycle";
    $dests_tables[]="category_blog";
    $dests_tables[]="category_celebrity";
    $dests_tables[]="category_chat";
    $dests_tables[]="category_children";
    $dests_tables[]="category_converters";
    $dests_tables[]="category_cosmetics";
    $dests_tables[]="category_culture";
    $dests_tables[]="category_dangerous_material";
    $dests_tables[]="category_dating";
    $dests_tables[]="category_books";
    $dests_tables[]="category_clothing";
    $dests_tables[]="category_medical";
    $dests_tables[]="category_smartphones";
    $dests_tables[]="category_press";
    $dests_tables[]="category_news";
    $dests_tables[]="category_recreation_nightout";
    $dests_tables[]="category_recreation_sports";
    $dests_tables[]="category_finance_realestate";
    $dests_tables[]="category_financial";
    $dests_tables[]="category_malware";

    $q=new postgres_sql();
    $TableToClean="category_porn";
    $c=0;
    foreach ($dests_tables as $to_table) {
        echo "Checking Source table $to_table\n";
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableToClean.sitename as site2 FROM $to_table,$TableToClean WHERE $TableToClean.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $TableToClean ( FROM $to_table)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $TableToClean WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from Porn\n";
}
function CleanPornOpposite(){
    $dests_tables[]="category_malware";
    $dests_tables[]="category_spyware";
    $dests_tables[]="category_suspicious";
    $dests_tables[]="category_phishing";
    $dests_tables[]="category_tracker";
    $dests_tables[]="category_publicite";
    $q=new postgres_sql();
    $TableSource="category_porn";
    $c=0;
    foreach ($dests_tables as $to_table) {

        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableSource.sitename as site2 FROM $to_table,$TableSource WHERE $TableSource.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $to_table ( FROM $TableSource)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $to_table WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from Porn\n";


}


function clean_doublons_it(){
    $q=new postgres_sql();
    $sql="SELECT sitename FROM category_science_computing";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=$ligne["sitename"];
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("DELETE FROM category_suspicious WHERE sitename='$sitename'");
        $q->QUERY_SQL("DELETE FROM category_malware WHERE sitename='$sitename'");
    }
}



function clean_doublons_other_to_adv(){

    dedoublon_othjer_adv_table("category_publicite");
}

function clean_mkt():bool{

    $q=new postgres_sql();
    $results = $q->QUERY_SQL("SELECT sitename FROM category_publicite WHERE sitename LIKE 'piwik.%'");
    if(!$q->ok){echo $q->mysql_error."\n";}
    while ($ligne = pg_fetch_assoc($results)) {

        $sitename=$ligne["sitename"];


        echo "Removing $sitename  ( FROM category_publicite)\n";
        $q->QUERY_SQL("DELETE FROM category_publicite WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_tracker (sitename) VALUES ('$sitename')");
        if(!$q->ok){
            echo $q->mysql_error;
            return false;
        }
    }
return true;
}

function clean_phishing(){
    $GLOBALS["DONOTDETECTS"]=unserialize(@file_get_contents("/home/artica/donot.detects"));
    dedoublon_malware_table("category_phishing");
    @file_put_contents("/home/artica/donot.detects",serialize($GLOBALS["DONOTDETECTS"]));
}

function clean_reaffected(){


    $dests_tables=AllTables();

    
    $q=new postgres_sql();
    $TableSource="category_reaffected";
    $c=0;
    foreach ($dests_tables as $to_table) {
        if($to_table==$TableSource){continue;}
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$TableSource.sitename as site2 FROM $to_table,$TableSource WHERE $TableSource.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = trim($ligne["site1"]);
            if($sitename==null){continue;}
            echo "Removing $sitename from $to_table ( FROM $TableSource)\n";
            $c++;
            $q->QUERY_SQL("DELETE FROM $to_table WHERE sitename='$sitename'");
        }

    }
    echo "$c removed sites from Porn\n";

}

function AllTables(){
    $sources_tables[]="category_recreation_humor";
    $sources_tables[]="category_stockexchange";
    $sources_tables[]="category_remote_control";
    $sources_tables[]="category_science_computing";
    $sources_tables[]="category_smallads";
    $sources_tables[]="category_microsoft";
    $sources_tables[]="category_facebook";
    $sources_tables[]="category_google";
    $sources_tables[]="category_society";
    $sources_tables[]="category_associations";
    $sources_tables[]="category_governments";
    $sources_tables[]="category_filehosting";
    $sources_tables[]="category_industry";
    $sources_tables[]="category_shopping";
    $sources_tables[]="category_webapps";
    $sources_tables[]="category_police";
    $sources_tables[]="category_politic";
    $sources_tables[]="category_luxury";
    $sources_tables[]="finance_moneylending";
    $sources_tables[]="category_finance_banking";
    $sources_tables[]="housing_doityourself";
    $sources_tables[]="category_housing_builders";
    $sources_tables[]="category_science_chemistry";
    $sources_tables[]="category_sex_lingerie";
    $sources_tables[]="category_sslsites";
    $sources_tables[]="category_tobacco";
    $sources_tables[]="category_weapons";
    $sources_tables[]="category_webphone";
    $sources_tables[]="category_webradio";
    $sources_tables[]="category_womanbrand";
    $sources_tables[]="category_meetings";
    $sources_tables[]="category_apple";
    $sources_tables[]="category_youtube";
    $sources_tables[]="category_redirector";
    $sources_tables[]="category_drugs";
    $sources_tables[]="category_forums";
    $sources_tables[]="category_jobtraining";
    $sources_tables[]="category_paytosurf";
    $sources_tables[]="category_hobby_arts";
    $sources_tables[]="category_hacking";
    $sources_tables[]="category_green";
    $sources_tables[]="category_governments";
    $sources_tables[]="category_gamble";
    $sources_tables[]="category_filehosting";
    $sources_tables[]="category_industry";
    $sources_tables[]="category_mixed_adult";
    $sources_tables[]="category_automobile_boats";
    $sources_tables[]="category_automobile_planes";
    $sources_tables[]="category_bicycle";
    $sources_tables[]="category_amazonaws";
    $sources_tables[]="category_google";
    $sources_tables[]="category_facebook";
    $sources_tables[]="category_blog";
    $sources_tables[]="category_celebrity";
    $sources_tables[]="category_chat";
    $sources_tables[]="category_children";
    $sources_tables[]="category_converters";
    $sources_tables[]="category_cosmetics";
    $sources_tables[]="category_culture";
    $sources_tables[]="category_dangerous_material";
    $sources_tables[]="category_dating";
    $sources_tables[]="category_books";
    $sources_tables[]="category_clothing";
    $sources_tables[]="category_medical";
    $sources_tables[]="category_smartphones";
    $sources_tables[]="category_press";
    $sources_tables[]="category_recreation_nightout";
    $sources_tables[]="category_recreation_sports";
    $sources_tables[]="category_finance_realestate";
    $sources_tables[]="category_financial";
    return $sources_tables;
}

function clean_doublons_adv(){

    $sources_tables[]="category_recreation_humor";
    $sources_tables[]="category_stockexchange";
    $sources_tables[]="category_remote_control";
    $sources_tables[]="category_science_computing";
    $sources_tables[]="category_smallads";
    $sources_tables[]="category_microsoft";
    $sources_tables[]="category_facebook";
    $sources_tables[]="category_google";
    $sources_tables[]="category_society";
    $sources_tables[]="category_associations";
    $sources_tables[]="category_governments";
    $sources_tables[]="category_filehosting";
    $sources_tables[]="category_industry";
    $sources_tables[]="category_shopping";
    $sources_tables[]="category_webapps";
    $sources_tables[]="category_police";
    $sources_tables[]="category_politic";
    $sources_tables[]="category_luxury";
    $sources_tables[]="finance_moneylending";
    $sources_tables[]="category_finance_banking";
    $sources_tables[]="housing_doityourself";
    $sources_tables[]="category_housing_builders";
    $sources_tables[]="category_science_chemistry";
    $sources_tables[]="category_sex_lingerie";
    $sources_tables[]="category_sslsites";
    $sources_tables[]="category_tobacco";
    $sources_tables[]="category_weapons";
    $sources_tables[]="category_webphone";
    $sources_tables[]="category_webradio";
    $sources_tables[]="category_womanbrand";
    $sources_tables[]="category_meetings";
    $sources_tables[]="category_apple";
    $sources_tables[]="category_youtube";
    $sources_tables[]="category_redirector";
    $sources_tables[]="category_drugs";
    $sources_tables[]="category_forums";
    $sources_tables[]="category_jobtraining";
    $sources_tables[]="category_paytosurf";
    $sources_tables[]="category_hobby_arts";
    $sources_tables[]="category_hacking";
    $sources_tables[]="category_green";
    $sources_tables[]="category_governments";
    $sources_tables[]="category_gamble";
    $sources_tables[]="category_filehosting";
    $sources_tables[]="category_industry";
    $sources_tables[]="category_mixed_adult";
    $sources_tables[]="category_automobile_boats";
    $sources_tables[]="category_automobile_planes";
    $sources_tables[]="category_bicycle";
    $sources_tables[]="category_reaffected";
    $sources_tables[]="category_blog";
    $sources_tables[]="category_celebrity";
    $sources_tables[]="category_chat";
    $sources_tables[]="category_children";
    $sources_tables[]="category_converters";
    $sources_tables[]="category_cosmetics";
    $sources_tables[]="category_culture";
    $sources_tables[]="category_dangerous_material";
    $sources_tables[]="category_dating";
    $sources_tables[]="category_books";
    $sources_tables[]="category_clothing";
    $sources_tables[]="category_medical";
    $sources_tables[]="category_smartphones";
    $sources_tables[]="category_press";
    $sources_tables[]="category_recreation_nightout";
    $sources_tables[]="category_recreation_sports";
    $sources_tables[]="category_finance_realestate";
    $sources_tables[]="category_financial";

    foreach ($sources_tables as $maintable){
        echo "Remove sites from $maintable\n";
        dedoublon_advert_table($maintable);
    }


}
function clean_doublons_danger(){
    $unix=new unix();
    $Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
    if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}

    $pid=$unix->get_pid_from_file($Pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        return false;
    }
    $GLOBALS["DONOTDETECTS"]=unserialize(@file_get_contents("/home/artica/donot.detects"));
    $sources_tables[]="category_porn";
    $sources_tables[]="category_abortion";
    $sources_tables[]="category_dynamic";
    $sources_tables[]="category_sexual_education";
    $sources_tables[]="category_violence";
    $sources_tables[]="category_dictionaries";
    $sources_tables[]="category_recreation_humor";
    $sources_tables[]="category_stockexchange";
    $sources_tables[]="category_remote_control";
    $sources_tables[]="category_smallads";
    $sources_tables[]="category_microsoft";
    $sources_tables[]="category_facebook";
    $sources_tables[]="category_google";
    $sources_tables[]="category_society";
    $sources_tables[]="category_associations";
    $sources_tables[]="category_webplugins";
    $sources_tables[]="category_webmail";
    $sources_tables[]="category_webtv";
    $sources_tables[]="category_wine";
    $sources_tables[]="category_horses";
    $sources_tables[]="category_tattooing";
    $sources_tables[]="category_browsersplugins";
    $sources_tables[]="category_isp";
    $sources_tables[]="category_police";
    $sources_tables[]="category_politic";
    $sources_tables[]="category_luxury";
    $sources_tables[]="finance_moneylending";
    $sources_tables[]="category_mailing";
    $sources_tables[]="category_finance_banking";
    $sources_tables[]="housing_doityourself";
    $sources_tables[]="category_housing_builders";
    $sources_tables[]="category_science_chemistry";
    $sources_tables[]="category_sex_lingerie";
    $sources_tables[]="category_alcohol";
    $sources_tables[]="category_sslsites";
    $sources_tables[]="category_tobacco";
    $sources_tables[]="category_weapons";
    $sources_tables[]="category_webphone";
    $sources_tables[]="category_webradio";
    $sources_tables[]="category_womanbrand";
    $sources_tables[]="category_meetings";
    $sources_tables[]="category_apple";
    $sources_tables[]="category_youtube";
    $sources_tables[]="category_redirector";
    $sources_tables[]="category_pictureslib";
    $sources_tables[]="category_drugs";
    $sources_tables[]="category_forums";
    $sources_tables[]="category_jobtraining";
    $sources_tables[]="category_jobsearch";
    $sources_tables[]="category_paytosurf";
    $sources_tables[]="category_hobby_arts";
    $sources_tables[]="category_hacking";
    $sources_tables[]="category_green";
    $sources_tables[]="category_governments";
    $sources_tables[]="category_gamble";
    $sources_tables[]="category_filehosting";
    $sources_tables[]="category_industry";
    $sources_tables[]="category_religion";
    $sources_tables[]="category_sect";
    $sources_tables[]="category_mixed_adult";
    $sources_tables[]="category_automobile_boats";
    $sources_tables[]="category_automobile_planes";
    $sources_tables[]="category_bicycle";
    $sources_tables[]="category_blog";
    $sources_tables[]="category_celebrity";
    $sources_tables[]="category_chat";
    $sources_tables[]="category_children";
    $sources_tables[]="category_converters";
    $sources_tables[]="category_cosmetics";
    $sources_tables[]="category_culture";
    $sources_tables[]="category_dangerous_material";
    $sources_tables[]="category_dating";
    $sources_tables[]="category_books";
    $sources_tables[]="category_clothing";
    $sources_tables[]="category_medical";
    $sources_tables[]="category_smartphones";
    $sources_tables[]="category_press";
    $sources_tables[]="category_recreation_nightout";
    $sources_tables[]="category_recreation_sports";
    $sources_tables[]="category_finance_realestate";
    $sources_tables[]="category_financial";
    $sources_tables[]="category_games";
    $sources_tables[]="category_health";
    $sources_tables[]="category_hobby_arts";
    $sources_tables[]="category_hobby_pets";
    $sources_tables[]="category_industry";
    $sources_tables[]="category_justice";
    $sources_tables[]="category_photo";
    $sources_tables[]="category_movies";
    $sources_tables[]="category_automobile_cars";
    $sources_tables[]="category_publicite";
    $sources_tables[]="category_audio_video";
    $sources_tables[]="category_proxy";
    $sources_tables[]="category_downloads";
    $sources_tables[]="category_socialnet";
    $sources_tables[]="category_cleaning";
    $sources_tables[]="category_webapps";
    $sources_tables[]="category_searchengines";
    $sources_tables[]="category_science_computing";
    $sources_tables[]="category_recreation_travel";
    $sources_tables[]="category_finance_insurance";



    foreach ($sources_tables as $maintable){
        echo "Remove sites from $maintable\n";
        dedoublon_suspicious_table($maintable);
    }

    @file_put_contents("/home/artica/donot.detects",serialize($GLOBALS["DONOTDETECTS"]));
}


function create_index(){
    $MAIN=array();
    $q=new postgres_sql();
    $sql="SELECT category_id,categoryname FROM personal_categories order by category_id";
    $results=$q->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $MAIN[$ligne["category_id"]]=$ligne["categoryname"];
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesIndex", serialize($MAIN));
}

function cleanTable($tablename){

    $q=new postgres_sql();

    $zDOMZ["eu.cm"]=true;
    $zDOMZ["eu.cr"]=true;
    $zDOMZ["eu.gg"]=true;
    $zDOMZ["eu.gl"]=true;
    $zDOMZ["eu.gp"]=true;
    $zDOMZ["eu.ki"]=true;
    $zDOMZ["eu.nu"]=true;
    $zDOMZ["co.de"]=true;
    $zDOMZ["co.gp"]=true;
    $zDOMZ["co.nu"]=true;
    $zDOMZ["co.at.hm"]=true;
    $zDOMZ["co.at.nr"]=true;
    $zDOMZ["co.cc"]=true;
    $zDOMZ["co.de"]=true;
    $zDOMZ["co.nf"]=true;
    $zDOMZ["co.technology"]=true;
    $zDOMZ["ae"]=true;
    $zDOMZ["africa"]=true;
    $zDOMZ["al"]=true;
    $zDOMZ["am"]=true;
    $zDOMZ["ar"]=true;
    $zDOMZ["at"]=true;
    $zDOMZ["ba"]=true;
    $zDOMZ["baby"]=true;
    $zDOMZ["be"]=true;
    $zDOMZ["bg"]=true;
    $zDOMZ["biz"]=true;
    $zDOMZ["biz.tr"]=true;
    $zDOMZ["blog"]=true;
    $zDOMZ["bo"]=true;
    $zDOMZ["by"]=true;
    $zDOMZ["ca"]=true;
    $zDOMZ["care"]=true;
    $zDOMZ["ch"]=true;
    $zDOMZ["cl"]=true;
    $zDOMZ["cm"]=true;
    $zDOMZ["co"]=true;
    $zDOMZ["co.id"]=true;
    $zDOMZ["co.il"]=true;
    $zDOMZ["co.jp"]=true;
    $zDOMZ["co.ke"]=true;
    $zDOMZ["co.kr"]=true;
    $zDOMZ["co.nz"]=true;
    $zDOMZ["co.th"]=true;
    $zDOMZ["co.uk"]=true;
    $zDOMZ["co.za"]=true;
    $zDOMZ["com"]=true;
    $zDOMZ["com.ar"]=true;
    $zDOMZ["com.au"]=true;
    $zDOMZ["com.bo"]=true;
    $zDOMZ["com.br"]=true;
    $zDOMZ["com.cn"]=true;
    $zDOMZ["com.co"]=true;
    $zDOMZ["com.cy"]=true;
    $zDOMZ["com.ec"]=true;
    $zDOMZ["com.gh"]=true;
    $zDOMZ["com.gt"]=true;
    $zDOMZ["com.hk"]=true;
    $zDOMZ["com.mx"]=true;
    $zDOMZ["com.my"]=true;
    $zDOMZ["com.ng"]=true;
    $zDOMZ["com.pa"]=true;
    $zDOMZ["com.pe"]=true;
    $zDOMZ["com.ph"]=true;
    $zDOMZ["com.pl"]=true;
    $zDOMZ["com.py"]=true;
    $zDOMZ["com.sv"]=true;
    $zDOMZ["com.tr"]=true;
    $zDOMZ["com.tw"]=true;
    $zDOMZ["com.uy"]=true;
    $zDOMZ["com.ve"]=true;
    $zDOMZ["com.vn"]=true;
    $zDOMZ["coupons"]=true;
    $zDOMZ["cz"]=true;
    $zDOMZ["de"]=true;
    $zDOMZ["dk"]=true;
    $zDOMZ["ee"]=true;
    $zDOMZ["es"]=true;
    $zDOMZ["family"]=true;
    $zDOMZ["fi"]=true;
    $zDOMZ["fr"]=true;
    $zDOMZ["ge"]=true;
    $zDOMZ["gr"]=true;
    $zDOMZ["gt"]=true;
    $zDOMZ["hamburg"]=true;
    $zDOMZ["hr"]=true;
    $zDOMZ["hu"]=true;
    $zDOMZ["ie"]=true;
    $zDOMZ["in"]=true;
    $zDOMZ["info"]=true;
    $zDOMZ["it"]=true;
    $zDOMZ["jp"]=true;
    $zDOMZ["go.jp"]=true;
    $zDOMZ["kz"]=true;
    $zDOMZ["lt"]=true;
    $zDOMZ["ltd"]=true;
    $zDOMZ["lu"]=true;
    $zDOMZ["lv"]=true;
    $zDOMZ["ma"]=true;
    $zDOMZ["mk"]=true;
    $zDOMZ["my"]=true;
    $zDOMZ["net"]=true;
    $zDOMZ["net.ph"]=true;
    $zDOMZ["ng"]=true;
    $zDOMZ["ngo.ph"]=true;
    $zDOMZ["nl"]=true;
    $zDOMZ["no"]=true;
    $zDOMZ["online"]=true;
    $zDOMZ["org"]=true;
    $zDOMZ["pa"]=true;
    $zDOMZ["ph"]=true;
    $zDOMZ["pl"]=true;
    $zDOMZ["press"]=true;
    $zDOMZ["presse.ml"]=true;
    $zDOMZ["pro"]=true;
    $zDOMZ["pt"]=true;
    $zDOMZ["re"]=true;
    $zDOMZ["reviews"]=true;
    $zDOMZ["ro"]=true;
    $zDOMZ["rs"]=true;
    $zDOMZ["ru"]=true;
    $zDOMZ["sale"]=true;
    $zDOMZ["se"]=true;
    $zDOMZ["shopping"]=true;
    $zDOMZ["si"]=true;
    $zDOMZ["sk"]=true;
    $zDOMZ["store"]=true;
    $zDOMZ["su"]=true;
    $zDOMZ["sv"]=true;
    $zDOMZ["sx"]=true;
    $zDOMZ["tj"]=true;
    $zDOMZ["tk"]=true;
    $zDOMZ["ua"]=true;
    $zDOMZ["us"]=true;
    $zDOMZ["uz"]=true;
    $zDOMZ["vip"]=true;
    $zDOMZ["world"]=true;
    $zDOMZ["xyz"]=true;
    $zDOMZ["za"]=true;
    $zDOMZ["one"]=true;
    $zDOMZ["chat"]=true;
    $zDOMZ["group"]=true;
    $zDOMZ["digital"]=true;
    $zDOMZ["cn"]=true;
    $zDOMZ["edu.au"]=true;

    foreach ($zDOMZ as $pattern=>$nothing){
        $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='$pattern'");
    }
     $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%?%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%/%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%;%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%..%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%)%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%{%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%}%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%(%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%!%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%@%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%§%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%#%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%^%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%¥%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%<%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%>%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%,%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%|%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%$%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%=%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%-moz-transition:%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='br'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.addr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.cdir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.ua'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.cn'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.my'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.pa'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.uy'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cn.com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.fr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='loan'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='guru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='..com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='*.com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.uk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.co.uk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='buzz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='info'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cdir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='addr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='xyz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pw'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.tt'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='www.co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='gs'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='paris'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='tools'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='life'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='online'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='reviews'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de.tc'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='da.ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.id'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.in'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.jp'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.org.ar'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.th'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ug'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ae'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.be'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.nz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.za'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='in.ua'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='in.net'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.bo'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='family'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='email'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='press'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='network'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='club'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='info'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='live'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='today'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='stream'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='video'");

    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='at'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='be'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='bid'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ch'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ca'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.id'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='dk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='da.ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='download'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ee'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='es'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='eu'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='eu.org'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fi'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='jp'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='name'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='no'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='nl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pisa.it'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ne.jp'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net.ar'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net.au'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net.ph'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net.vn'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='me'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='org'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='or.at'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='or.id'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='org.tw'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='or.th'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='link'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pro'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pro.br'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='re'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ro'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='it'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='is'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='us'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='usa.cc'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='uk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='sc'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='tech'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de.tc'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='kr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='xyz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='gl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.br'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.pa'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.hk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.kr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.za'");
    if($tablename=="category_suspicious") {
        $q->QUERY_SQL("DELETE FROM category_suspicious WHERE sitename ='asso.fr'");
    }
    if($tablename=="category_malware") {
        $q->QUERY_SQL("DELETE FROM category_malware WHERE sitename ='fromsmash.com'");
    }
    if($tablename=="category_filehosting") {
        $q->QUERY_SQL("DELETE FROM category_filehosting WHERE sitename ='fromsmash.com'");
    }






}
function clean_suspicious_all(){

    clean_suspicious("category_microsoft");
    clean_suspicious("category_facebook");
    clean_suspicious("category_apple");
    clean_suspicious("category_google");
    clean_suspicious("category_youtube");
    clean_suspicious("category_audio_video");
    clean_suspicious("category_publicite");
    clean_suspicious("category_industry");
    clean_suspicious("category_society");
    clean_suspicious("category_shopping");
    clean_suspicious("category_blog");
    clean_suspicious("category_governments");
    clean_suspicious("category_associations");
    clean_suspicious("category_automobile_cars");
    clean_suspicious("category_isp");
    clean_suspicious("category_science_computing");
    clean_suspicious("category_hobby_arts");
    clean_suspicious("category_religion");
    clean_suspicious("category_porn");
    clean_suspicious("category_amazonaws");

    if($GLOBALS["category_suspicious_D"]>0){
        squid_admin_mysql(1,"{$GLOBALS["category_suspicious_D"]} items removed from Suspicious category",null,__FILE__,__LINE__);

    }

    if($GLOBALS["category_malware_D"]>0){
        squid_admin_mysql(1,"{$GLOBALS["category_malware_D"]} items removed from Malware category",null,__FILE__,__LINE__);

    }


}
function dedoublon_othjer_adv_table($from_table){
    $q=new postgres_sql();

    $dests_tables[]="category_science_computing";
    $dests_tables[]="category_smallads";
    $dests_tables[]="category_microsoft";
    $dests_tables[]="category_facebook";
    $dests_tables[]="category_google";
    $dests_tables[]="category_society";
    $dests_tables[]="category_associations";
    $dests_tables[]="category_governments";
    $dests_tables[]="category_filehosting";
    $dests_tables[]="category_industry";
    $dests_tables[]="category_shopping";
    $dests_tables[]="category_webapps";
    $dests_tables[]="category_police";
    $dests_tables[]="category_luxury";
    $dests_tables[]="finance_moneylending";
    $dests_tables[]="category_finance_banking";
    $dests_tables[]="housing_doityourself";
    $dests_tables[]="category_housing_builders";
    $dests_tables[]="category_science_chemistry";
    $dests_tables[]="category_sex_lingerie";
    $dests_tables[]="category_sslsites";
    $dests_tables[]="category_tobacco";
    $dests_tables[]="category_weapons";
    $dests_tables[]="category_webphone";
    $dests_tables[]="category_webradio";
    $dests_tables[]="category_womanbrand";
    $dests_tables[]="category_meetings";
    $dests_tables[]="category_apple";
    $dests_tables[]="category_youtube";
    $dests_tables[]="category_redirector";
    $dests_tables[]="category_drugs";
    $dests_tables[]="category_forums";
    $dests_tables[]="category_jobtraining";
    $dests_tables[]="category_jobsearch";
    $dests_tables[]="category_paytosurf";
    $dests_tables[]="category_hobby_arts";
    $dests_tables[]="category_hacking";
    $dests_tables[]="category_green";
    $dests_tables[]="category_governments";
    $dests_tables[]="category_gamble";
    $dests_tables[]="category_filehosting";
    $dests_tables[]="category_industry";
    $dests_tables[]="category_mixed_adult";
    $dests_tables[]="category_automobile_boats";
    $dests_tables[]="category_automobile_planes";
    $dests_tables[]="category_bicycle";
    $dests_tables[]="category_blog";
    $dests_tables[]="category_celebrity";
    $dests_tables[]="category_chat";
    $dests_tables[]="category_children";
    $dests_tables[]="category_converters";
    $dests_tables[]="category_cosmetics";
    $dests_tables[]="category_culture";
    $dests_tables[]="category_dangerous_material";
    $dests_tables[]="category_dating";
    $dests_tables[]="category_books";
    $dests_tables[]="category_clothing";
    $dests_tables[]="category_medical";
    $dests_tables[]="category_smartphones";
    $dests_tables[]="category_press";
    $dests_tables[]="category_recreation_nightout";
    $dests_tables[]="category_recreation_sports";
    $dests_tables[]="category_finance_realestate";
    $dests_tables[]="category_financial";
    $dests_tables[]="category_malware";
    $dests_tables[]="category_spyware";
    $dests_tables[]="category_suspicious";
    $dests_tables[]="category_phishing";

    foreach ($dests_tables as $to_table) {
        $Count1=$q->COUNT_ROWS_LOW("$to_table");
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$from_table.sitename as site2 FROM $to_table,$from_table WHERE $from_table.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = $ligne["site1"];
            echo "Removing $sitename from $to_table\n";
            $q->QUERY_SQL("DELETE FROM $to_table WHERE sitename='$sitename'");
        }

        $Count2=$q->COUNT_ROWS_LOW("$to_table");
        $diff1=$Count1-$Count2;

        if($diff1>0){
            squid_admin_mysql(1,"$to_table $diff1 sites removed",null,__FILE__,__LINE__);
        }
    }

}

function dedoublon_advert_table($from_table){

    $q=new postgres_sql();

    $dests_tables[]="category_publicite";
    $dests_tables[]="category_tracker";


    foreach ($dests_tables as $to_table) {
        $Count1=$q->COUNT_ROWS_LOW("$to_table");
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$from_table.sitename as site2 FROM $to_table,$from_table WHERE $from_table.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = $ligne["site1"];
            echo "Removing $sitename from $to_table\n";
            $q->QUERY_SQL("DELETE FROM $to_table WHERE sitename='$sitename'");
        }

        $Count2=$q->COUNT_ROWS_LOW("$to_table");
        $diff1=$Count1-$Count2;

        if($diff1>0){
            squid_admin_mysql(1,"$to_table $diff1 sites removed",null,__FILE__,__LINE__);
        }
    }

}
function dedoublon_malware_table($from_table){
    $q=new postgres_sql();
    $dests_tables[]="category_malware";
    $dests_tables[]="category_isp";
    $dests_tables[]="category_science_computing";
    foreach ($dests_tables as $to_table) {
        $Count1=$q->COUNT_ROWS_LOW("$to_table");
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$from_table.sitename as site2 FROM $to_table,$from_table WHERE $from_table.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = $ligne["site1"];
            $GLOBALS["DONOTDETECTS"][$sitename]=true;
            echo "Removing $sitename from $to_table\n";
            $q->QUERY_SQL("DELETE FROM $to_table WHERE sitename='$sitename'");
        }

        $Count2=$q->COUNT_ROWS_LOW("$to_table");
        $diff1=$Count1-$Count2;

        if($diff1>0){
            squid_admin_mysql(1,"$to_table $diff1 sites removed",null,__FILE__,__LINE__);
        }
    }
}


function dedoublon_suspicious_table($from_table){
    $q=new postgres_sql();

    $dests_tables[]="category_malware";
    $dests_tables[]="category_suspicious";
    $dests_tables[]="category_phishing";
    $dests_tables[]="category_spyware";


    foreach ($dests_tables as $to_table) {
        $Count1=$q->COUNT_ROWS_LOW("$to_table");
        $results = $q->QUERY_SQL("SELECT $to_table.sitename as site1,$from_table.sitename as site2 FROM $to_table,$from_table WHERE $from_table.sitename = $to_table.sitename");

        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = $ligne["site1"];
            $GLOBALS["DONOTDETECTS"][$sitename]=true;
            echo "Removing $sitename from $to_table\n";
            $q->QUERY_SQL("DELETE FROM $to_table WHERE sitename='$sitename'");
        }

        $Count2=$q->COUNT_ROWS_LOW("$to_table");
        $diff1=$Count1-$Count2;

        if($diff1>0){
            squid_admin_mysql(1,"$to_table $diff1 sites removed",null,__FILE__,__LINE__);
        }
    }
}

function clean_suspicious($tablename){
    dedoublon_suspicious_table($tablename);

}

function temporay_work(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $tempdir="/home/artica/categories_works";
    if(is_dir($tempdir)){system("$rm -rf $tempdir");}
    @mkdir($tempdir,0777,true);
    @chown($tempdir,"ArticaStats");
    @chgrp($tempdir,"ArticaStats");
    return $tempdir;
}

function compile_single_category($category_id,$prc=0){
    $unix=new unix();
    $ufdbGenTable=$unix->find_program("ufdbGenTable");
    syslog_config();
    $FixCategories_tables[228]="category_228";
    $FixCategories_tables[229]="category_229";
    $FixCategories_tables[228]="category_228";
    $FixCategories_tables[230]="category_230";
    $FixCategories_tables[231]="category_231";
    $FixCategories_tables[232]="category_232";
    $FixCategories_tables[233]="category_233";
    $FixCategories_tables[234]="cloudflare";
    $FixCategories_tables[236]="hetzner_online";
    $FixCategories_tables[237]="doh_dns";




    if(!is_file($ufdbGenTable)){
        build_progress_single("{compile}: missing compilator...",110);
        if($prc>0){build_progress("{compressing} missing compilator...",$prc);}
        return false;
    }

    if(!isset($GLOBALS["CATEGORIES_CHECK"])){
        $catz=new categories();
        $catz->initialize();
        $GLOBALS["CATEGORIES_CHECK"]=true;
    }
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'"));
    $table=$ligne["categorytable"];
    $categoryname=$ligne["categoryname"];

    if($table==null){
        if(isset($FixCategories_tables[$category_id])){
            $table=$FixCategories_tables[$category_id];
        }
    }
    if($categoryname==null) {
        if(isset($FixCategories_tables[$category_id])){
            $categoryname=$FixCategories_tables[$category_id];
        }
    }


    if($table==null){
        xsyslog("[INFO]: Builder: $category_id, no table found in table personal_categories");
        if($prc>0){build_progress("{category} $category_id, no table found",$prc);}
        build_progress_single("{category} $category_id, no table found",110);
        return false;
    }

    if(!$q->TABLE_EXISTS($table)){
        xsyslog("[WARNING]: Builder: $category_id, no table found in database");
        if($prc>0){build_progress("{category} $category_id, $table, no such table",$prc);}
        build_progress_single("{category} $category_id, $table, no such table",110);
        return false;

    }

    $timestart=time();
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $CacheDatabase=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategoriesDatabase"));
    $MD5CatzDBS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MD5CatzDBS"));
    $AsCategoriesProvider=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AsCategoriesProvider"));
    $tempdir= temporay_work();

    $NICE=EXEC_NICE();
    if($ManageOfficialsCategories==0) {
        $nohup = $unix->find_program("nohup");
        $pid = $unix->get_pid_from_file("/var/run/compile-category.pid");
        if (!$unix->process_exists($pid)) {
            if ($AsCategoriesProvider == 0) {
                shell_exec("$nohup /usr/share/artica-postfix/bin/compile-category -compile-personal >/dev/null 2>&1 &");
            }
        }
    }

    $tmp="$tempdir/$category_id.txt";
    if(is_file($tmp)){@unlink($tmp);}

    if($prc>0){build_progress("{cleaning}: $categoryname 10%",$prc);}
    build_progress_single("{cleaning}: $categoryname",25);
    $tt=time();
    xsyslog("[INFO]: 25% Builder: Cleaning $table for $categoryname");
    cleanTable($table);
    xsyslog("[INFO]: Builder: Cleaning $table took:". $unix->distanceOfTimeInWords($tt,time()) );
    $items_sql=$q->COUNT_ROWS_LOW($table);
    if($items_sql==$CacheDatabase[$category_id]){
        xsyslog("[SKIP]: Builder: $items_sql items, SKIP $categoryname no Change");
        if($prc>0){build_progress("{nothing_to_do}: $categoryname (100%)",$prc);}
        echo "[$category_id]: $categoryname table $table items:$items_sql -> Skipping\n";
        build_progress_single("{nothing_to_do}: $categoryname",100);
        return false;
    }
    if($prc>0){build_progress("{exporting}: $categoryname (20%)",$prc);}
    build_progress_single("{exporting}: $categoryname",30);
    $tt=time();
    xsyslog("[INFO]: 35% Builder: exporting $table for $categoryname");
    $sql="COPY $table TO '$tmp'";
    $q->QUERY_SQL($sql);
    xsyslog("[INFO]: 35% Builder: exporting $table for $categoryname done took:".$unix->distanceOfTimeInWords($tt,time()));

    if(!$q->ok){
        echo $q->mysql_error."\n";
        xsyslog("[ERROR]: $table for $categoryname $q->mysql_error");
        if($prc>0){build_progress("{compile}: $categoryname {failed} L.".__LINE__,$prc);}
        build_progress_single("{compile}: $categoryname {failed} L.".__LINE__,110);
        @unlink($tmp);
        return false;
    }

    $crc32_1=null;
    if(isset($MD5CatzDBS[$category_id])){
        $crc32_1=$MD5CatzDBS[$category_id];
    }
    $crc32_2=get_crc_file($tmp);

    if($crc32_1 == $crc32_2){
        xsyslog("[SKIP]: 35% Builder: exporting $table for $categoryname SKIP, same CRC");
        return false;
    }

    cat_update_events("INFO: Building source file for $categoryname");
    build_progress_single("{compiling}: Transfert $categoryname",50);
    if($prc>0){build_progress("{compiling}: Transfert $categoryname (50%)",$prc);}

    $tt=time();
    xsyslog("[INFO]: prepare $tmp for $categoryname compilation");

    if(!transfert_file($category_id,$tmp,$categoryname)){
        cat_update_events("ERROR: failed to build source file $tmp");
        build_progress_single("{compile}: Transfert $categoryname {failed} L.".__LINE__,110);
        if($prc>0){build_progress("{compiling}: $categoryname Transfert $categoryname {failed} L.".__LINE__,$prc);}
        @unlink($tmp);
        return false;
    }
    xsyslog("[INFO]: 50% Builder: prepare $tmp for $categoryname compilation done took:".$unix->distanceOfTimeInWords($tt,time()));
    $destfile="/var/lib/squidguard/$category_id/domains";
    $date=date("Y-m-d");
    $backupdir="/home/artica/webfiltering-backup/$date";
    if(!is_dir($backupdir)){@mkdir($backupdir,0755,true);}

    $datesuffix=date("Y-m-d-H-i");
    $DestinationDatabaseBackup="$backupdir/$category_id-$categoryname-$datesuffix.txt";
    if(!is_file($DestinationDatabaseBackup)){
        xsyslog("[INFO]: $categoryname Backup database in $DestinationDatabaseBackup");
        cat_update_events("Backup database in $DestinationDatabaseBackup");
        @copy($destfile,$DestinationDatabaseBackup);
    }
    if(!is_file($DestinationDatabaseBackup)){
        xsyslog("[ERROR]: $categoryname Backup database in $DestinationDatabaseBackup (no such file)");
    }


    build_progress_single("{injecting}: ufdbGentable $categoryname",60);
    if($prc>0){build_progress("{injecting}: ufdbGentable $categoryname (60%)",$prc);}
    $squidguard_dir="/var/lib/squidguard/$category_id";

    $tt=time();
    xsyslog("[INFO]: 60% $categoryname - Executing compilation for Web-filtering");
    cat_update_events("INFO: Executing compilation of $categoryname");
    if(!is_file("$squidguard_dir/urls")){@touch("$squidguard_dir/urls");}
    $ctx=array();
    $ctx[]=$NICE;
    $ctx[]=$ufdbGenTable;
    $ctx[]="-n -q -Z -W -t $category_id";
    $ctx[]="-d /var/lib/squidguard/$category_id/domains";
    $ctx[]="-u /var/lib/squidguard/$category_id/urls";
    $ctx[]=">/dev/null 2>&1";
    system(@implode(" ", $ctx));

    xsyslog("[INFO]: 55% Builder: $categoryname - Executing compilation done took:".$unix->distanceOfTimeInWords($tt,time()));
    if($prc>0){build_progress("{injecting}: $categoryname {done}",$prc);}
    build_progress_single("{injecting}: $categoryname {done}",80);


    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");

    if(is_dir("/var/lib/ufdbartica/$category_id")){
        shell_exec("$rm -rf /var/lib/ufdbartica/$category_id");
    }
    @mkdir("/var/lib/ufdbartica/$category_id",0755,true);
    system("$cp -rfvd /var/lib/squidguard/$category_id/* /var/lib/ufdbartica/$category_id/");

    $q->QUERY_SQL("UPDATE personal_categories SET compiledate=".time()." WHERE category_id=$category_id");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ManageOfficialsCategoriesDatabase", serialize($CacheDatabase));

    $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    if($UfdbCatsUpload==1){
        build_progress_single("{create_index}: $categoryname",90);
        if($prc>0){build_progress("{create_index}: $categoryname...(90%)",$prc);}
        xsyslog("[INFO]: 60% Builder: $categoryname - Executing exec.upload.categories.php");
        create_index_for_upload($category_id,90,$prc);
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        $nohup=$unix->find_program("nohup");
        if($GLOBALS["FAILED_UPLOADED"]>0) {
            if ($prc == 0) {
                shell_exec("$nohup $php /usr/share/artica-postfix/exec.upload.categories.php >/dev/null 2>&1 &");
            }
        }
    }
    $took=distanceOfTimeInWords($timestart,time(),true);
    cat_update_events("INFO: Compilation of $categoryname took $took");
    squid_admin_mysql(2,"{success} {compiling} {category} $categoryname ($took)",null,__FILE__,__LINE__);
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/personal-categories/reload");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_exec($cURLConnection);
    curl_close($cURLConnection);

    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    $cmd="/usr/sbin/artica-phpfpm-service -reload-proxy";
    $unix->framework_exec($cmd);
    xsyslog("[INFO]: 100% Builder: $categoryname - Success took: $took" );
    build_progress_single("{compiling}: $categoryname {success}",100);
    if($prc>0){build_progress("{compiling}: $categoryname {success} (100%)",$prc);}

    if($GLOBALS["CLUSTER_SINGLE"]) {
        cluster_table();
    }

    compile_catlist();
    return true;

}
function cat_update_events($text){
    if(!function_exists("syslog")){return false;}
    openlog("categories-update", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "[COMPILE-CATEGORIES]: $text");
    closelog();
    return true;
}

function compile_catlist(){
    $q = new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){return false;}
    $unix=new unix();
    $ROOT_DIR           =  "/home/artica/dnscatz/dsbl";
    $ManageOfficialsCategories = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    if($UfdbCatsUpload==0){return false;}
    if(is_file("$ROOT_DIR/catlist.txt")){
        $SRC_CATLIST=unserialize(base64_decode(@file_get_contents("$ROOT_DIR/catlist.txt")));
    }

    $sql = "SELECT * FROM personal_categories 
            WHERE official_category=0 AND free_category=0 ORDER by category_id";
    if ($ManageOfficialsCategories == 1) {
        $sql = "SELECT * FROM personal_categories 
        WHERE (official_category=1 OR free_category=1) order by category_id";
    }

    if(!is_dir($ROOT_DIR)){@mkdir($ROOT_DIR,0755,true);}

    $results = $q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $category_id  = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        $categorytable = $ligne["categorytable"];
        $free_category = $ligne["free_category"];
        if($free_category==1){continue;}

        $category_description=$ligne["category_description"];
        $catlist[$category_id]["NAME"]=$categoryname;
        $catlist[$category_id]["DESC"]=$category_description;
        echo "Found Category $category_id ($categoryname)\n";
        $catlist[$category_id]["ITEMS"]=$q->COUNT_ROWS($categorytable);
        $ufdbfile="/var/lib/ufdbartica/$category_id/domains.ufdb";
        $ufdbzip="/var/lib/ufdbartica/$category_id/$category_id.gz";


        if(is_file($ufdbfile)){
            if(!isset($SRC_CATLIST[$category_id]["UFDB"])){$SRC_CATLIST[$category_id]["UFDB_TXT"]="";}
            $ufdbfilemd5=null;
            if(is_file($ufdbzip)) {
                $ufdbfilemd5 = md5_file($ufdbzip);
            }
            if($SRC_CATLIST[$category_id]["UFDB_TXT"]==$ufdbfilemd5){
                $catlist[$category_id]["UFDB_TXT"]=$ufdbfilemd5;
                $catlist[$category_id]["UFDB"] = $SRC_CATLIST[$category_id]["UFDB"];
                if(is_file($ufdbzip)){@unlink($ufdbzip);}
                continue;
            }
            $catlist[$category_id]["UFDB"]=$ufdbfilemd5;
            echo "Compressing $ufdbfile to $ufdbzip\n";
            if($unix->compress($ufdbfile,$ufdbzip)){
                echo "Uploading $ufdbzip to ufdb/\n";
                if(upload_category($ufdbzip,"ufdb")) {
                    $catlist[$category_id]["UFDB"] = md5_file($ufdbzip);
                }
            }
            if(is_file($ufdbzip)){@unlink($ufdbzip);}
        }

    }

    $DnsCatzPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzPort"));
    $DnscatzDomain=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnscatzDomain"));
    $DnsCatzCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzCrypt"));
    $DnsCatzPassPharse=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsCatzPassPharse"));
    $catlist["99999999"]["TIME"]=time();
    $catlist["99999999"]["DOMAIN"]=$DnscatzDomain;
    $catlist["99999999"]["PORT"]=$DnsCatzPort;
    $catlist["99999999"]["CRYPT"]=$DnsCatzCrypt;
    $catlist["99999999"]["PASS"]=$DnsCatzPassPharse;

    @file_put_contents("$ROOT_DIR/catlist.txt",base64_encode(serialize($catlist)));
    echo "$ROOT_DIR/catlist.txt done\n";
    upload_category("$ROOT_DIR/catlist.txt","catlist");
    return true;
}
function is_in_dnscatz($category_id){
    $dnscatz_pid=dnscatz_pid();
    if(!is_file("/proc/$dnscatz_pid/cmdline")){return false;}
    $cmdline=trim(@file_get_contents("/proc/$dnscatz_pid/cmdline"));
    if(!preg_match("#:dnset:([0-9,]+)#",$cmdline,$re)){return false;}
    $tb=explode(",",$re[1]);
    foreach ($tb as $ident){
        if($ident==$category_id){return true;}
    }
    return false;
}

function dnscatz_pid():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/dnscatz.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/sbin/dnscatz");
}

function create_index_for_upload($category_id,$percent,$prc=0){

    $unix=new unix();
    $gzip=$unix->find_program("gzip");
    build_progress_single("Prepare $category_id for repository (10%)",$percent);
    if($prc>0){build_progress("Prepare $category_id for repository (10%)",$prc);}
    $TEMPDIR="/home/artica/webfiltering/temp-upload";
    if(!is_dir($TEMPDIR)){@mkdir($TEMPDIR,0755,true);}
    $SourceDatabase="/var/lib/squidguard/$category_id/domains.ufdb";
    $DestinationDatabase="$TEMPDIR/$category_id.gz";
    $DestinationIndexFile="$TEMPDIR/$category_id.txt";
    if(!is_file($SourceDatabase)){
        $unix->ToSyslog("[ERROR]: Builder: $SourceDatabase no such file",false,"categories-update");
        return false;
    }
    $unix=new unix();
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'"));
    $table=$ligne["categorytable"];
    $categoryname=$ligne["categoryname"];
    $items_sql=$q->COUNT_ROWS_LOW($table);
    if(is_file($DestinationDatabase)){@unlink($DestinationDatabase);}
    if(is_file($DestinationIndexFile)){@unlink($DestinationIndexFile);}
    $INDEX_ARRAY["TIME"]=time();
    $INDEX_ARRAY["category_id"]=$category_id;
    $INDEX_ARRAY["ELEMENTS"]=$items_sql;
    $INDEX_ARRAY["MD5"]=md5_file($SourceDatabase);
    $INDEX_ARRAY["official_category"]=$ligne["official_category"];
    $INDEX_ARRAY["free_category"]=$ligne["free_category"];

    build_progress_single("{compressing} (20%)",$percent);
    build_progress("{compressing} (20%)",$percent);
    if($prc>0){build_progress("{compressing} $category_id (20%)",$prc);}
    $unix->ToSyslog("[INFO]: Builder: Compressing $SourceDatabase to $DestinationDatabase",false,"categories-update");
    shell_exec("$gzip -c $SourceDatabase  > $DestinationDatabase");

    $INDEX_ARRAY["MD5GZ"]=md5_file($DestinationDatabase);
    $unix->ToSyslog("[INFO]: Builder: Saving index file $DestinationIndexFile",false,"categories-update");
    @file_put_contents($DestinationIndexFile, base64_encode(serialize($INDEX_ARRAY)));
    build_progress_single("{compressing} {done} (100%)",$percent);
    if($prc>0){build_progress("{compressing} $category_id {done} (100%)",$prc);}
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php ".__FILE__." --ftp \"$DestinationIndexFile\"");

    if(is_file("$DestinationIndexFile.err")){
        @unlink("$DestinationIndexFile.err");
        $unix->ToSyslog("[ERROR]: Builder: Failed to upload index file $category_id $categoryname",false,"categories-update");
        squid_admin_mysql(0,"Failed to upload $category_id $categoryname");
        return false;
    }

    system("$php ".__FILE__." --ftp \"$DestinationDatabase\"");
    if(is_file("$DestinationDatabase.err")){
        @unlink("$DestinationDatabase.err");
        $unix->ToSyslog("[ERROR]: Builder: Failed to upload database file $category_id $categoryname",false,"categories-update");
        squid_admin_mysql(0,"Failed to upload $category_id $categoryname");
        return false;
    }



    @unlink($DestinationIndexFile);
    @unlink($DestinationDatabase);
    compile_catlist();
    return true;
}

function compile_new_categories(){
    $q=new postgres_sql();
    $unix=new unix();
    $CATNAMECOMPILE=array();
    $ufdbGenTable=$unix->find_program("ufdbGenTable");
    build_progress("{compile_all_categories}",5);
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));

    if(!$q->FIELD_EXISTS("personal_categories", "compilerows")){
        $q->QUERY_SQL("alter table personal_categories add column if not exists compilerows bigint;");
        if(!$q->ok){echo $q->mysql_error;}
    }
    if(!$q->FIELD_EXISTS("personal_categories", "compiledate")){
        $q->QUERY_SQL("alter table personal_categories add column if not exists compiledate bigint;");
        if(!$q->ok){echo $q->mysql_error;}
    }


    @mkdir("/etc/artica-postfix/pids",0755,true);
    @unlink("/etc/artica-postfix/pids/CompiledAllCategories");
    @file_put_contents("/etc/artica-postfix/pids/CompiledAllCategories", time());


    if(!is_file($ufdbGenTable)){
        build_progress("{compile}: missing compilator...",110);
        return false;
    }

    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
    if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories order by category_id";}
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n$sql\n";}

    $total=pg_num_rows($results);
    $c=0;$SKIPPED=array();
    $timestart=time();
    $SUC=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $c++;
        $prc=$c/$total;
        $prc=round($prc*100,0);
        if($prc<5){$prc=5;}
        if($prc>90){$prc=90;}
        $category_id=$ligne["category_id"];
        $categoryname=$ligne["categoryname"];
        if(preg_match("#^reserved[0-9]+#", $categoryname)){continue;}
        $categorykey=$ligne["categorykey"];
        $table=$ligne["categorytable"];
        if(!$q->TABLE_EXISTS($table)){continue;}

        $items_sql=$q->COUNT_ROWS_LOW($table);
        $q->QUERY_SQL("UPDATE personal_categories SET items='$items_sql' WHERE category_id='$category_id'");


        $old_items_count=intval($ligne["compilerows"]);
        echo "[$category_id]: compilerows=$old_items_count items:$items_sql -> NO CHANGES\n";

        if($items_sql==$old_items_count){
            echo "[$category_id]: $categoryname/$categorykey table $table items:$items_sql -> NO CHANGES\n";
            $SKIPPED[]=$category_id;
            continue;
        }

        if(compile_single_category($category_id,$prc)){
            $SUC++;
            $CATNAMECOMPILE[]=$categoryname;
            $t=time();
            $q->QUERY_SQL("UPDATE personal_categories SET compiledate='$t', compilerows='$items_sql' WHERE category_id='$category_id'");
        }

    }


    if($SUC>0){
        $took=distanceOfTimeInWords($timestart,time(),true);
        squid_admin_mysql(2,"{success} {compiling} $SUC Web-filtering categories skipped:".count($SKIPPED)." ($took)",@implode("\n", $CATNAMECOMPILE)."\n".@implode("\n",$SKIPPED),__FILE__,__LINE__);


        if(is_file("/etc/init.d/ufdbcat")){
            build_progress("{reloading_services}",97);
            system("/etc/init.d/ufdbcat reload");
        }
        if(is_file("/etc/init.d/ufdb")){
            build_progress("{reloading_services}",98);
            system("/etc/init.d/ufdb reload");
        }


        $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
        if($UfdbCatsUpload==1){
            $unix=new unix();
            $php=$unix->LOCATE_PHP5_BIN();
            $nohup=$unix->find_program("nohup");
            if($GLOBALS["FAILED_UPLOADED"]>0) {
                shell_exec("$nohup $php /usr/share/artica-postfix/exec.upload.categories.php >/dev/null 2>&1 &");
            }
        }
    }

    build_progress("{compile_all_categories}: {done}",100);



}
function compile_categories_rbl_ftp(){

    system("/etc/init.d/firehol stop");
    $Config=explode("\n",@file_get_contents("/root/export-rbls-catz.txt"));
    $UfdbCatsUploadFTPusr   = $Config[1];
    $UfdbCatsUploadFTPserv  = $Config[0];
    $UfdbCatsUploadFTPpass  = $Config[2];
    $DestDir                = "/home/artica/categories_rbl";

    if ( ($UfdbCatsUploadFTPserv==null) OR ($UfdbCatsUploadFTPusr==null)){
        return false;
    }

    $conn_id = ftp_connect($UfdbCatsUploadFTPserv);        // set up basic connection
    $login_result = ftp_login($conn_id, $UfdbCatsUploadFTPusr, $UfdbCatsUploadFTPpass);

    if ((!$conn_id) || (!$login_result)) {
        events("Failed $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv",__FUNCTION__,__FILE__,__LINE__);
        squid_admin_mysql(0,"Failed to upload RBLs to $UfdbCatsUploadFTPserv",null,__FILE__,__LINE__);
        return false;
    }

    $unix=new unix();
    $files=$unix->DirFiles($DestDir);

    foreach ($files as $filename){
        $SourceFile="$DestDir/$filename";
        $fp = fopen($SourceFile, 'r');
        if (!ftp_fput($conn_id, "$filename", $fp, FTP_BINARY)) {
            events("Failed to upload $filename",__FUNCTION__,__FILE__,__LINE__);
            echo "Failed to upload $filename\n";
            ftp_close($conn_id);
            fclose($fp);
            return false;
        }
        echo "$DestDir/$filename success...\n";
        events("$DestDir/$filename Success",__FUNCTION__,__FILE__,__LINE__);
        fclose($fp);

    }
    ftp_close($conn_id);
    system("/etc/init.d/firehol start");
}

function patch_categories_table(){

    $q=new postgres_sql();

    if(!$q->FIELD_EXISTS("personal_categories", "compiledate")){
        $q->QUERY_SQL("alter table personal_categories add column if not exists compiledate bigint;");
        if(!$q->ok){echo $q->mysql_error;}
    }
    if(!$q->FIELD_EXISTS("personal_categories", "compilerows")){
        $q->QUERY_SQL("alter table personal_categories add column if not exists compilerows bigint;");
        if(!$q->ok){echo $q->mysql_error;}
    }

}

function compile_all():bool{
    $unix=new unix();
    $SKIPPED=array();
    $Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
    if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}

    $pid=$unix->get_pid_from_file($Pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        build_progress("Already task exists pid $pid, aborting",110);
        xsyslog("[WARNING]: Builder: Already task exists pid $pid, aborting");
        return false;
    }
    @file_put_contents($Pidfile, getmypid());
    $PersonalCategoriesLimitBefore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesLimitBefore"));
    if($PersonalCategoriesLimitBefore==0){$PersonalCategoriesLimitBefore=60;}

    if(!$GLOBALS["VERBOSE"]){
        if(!$GLOBALS["FORCE"]){
            if($PersonalCategoriesLimitBefore>1) {
                $time = $unix->file_time_min($PidTime);
                echo "$PidTime = {$time}mn\n";
                if ($time < $PersonalCategoriesLimitBefore) {
                    $unix->ToSyslog("[ERROR]: Builder: Only each 60mn, aborting", false, "categories-update");
                    build_progress("Only each {$PersonalCategoriesLimitBefore}mn, aborting", 110);
                    echo "Only each {$PersonalCategoriesLimitBefore}mn\n";
                    return false;
                }
            }
            @unlink($PidTime);
            @file_put_contents($PidTime, time());
        }
    }


    syslog_config();

    $q=new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){
        xsyslog("[ERROR]: Builder: Missing personal_categories table");
        build_progress("{compile_all_categories} {failed} no table",110);
        return false;
    }
    build_progress("{compile_all_categories}",5);
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $nohup=$unix->find_program("nohup");
    xsyslog("[ERROR]: Builder: ManageOfficialsCategories = $ManageOfficialsCategories");
    if($ManageOfficialsCategories==0) {
        $thesheild_db = "/home/artica/SQLITE/theshield.categories.db";
        $thesheild_src="";
        if(is_file($thesheild_db)) {
            $thesheild_src = md5_file($thesheild_db);
        }
        $pid = $unix->get_pid_from_file("/var/run/compile-category.pid");
        if (!$unix->process_exists($pid)) {
            build_progress("{compile_all_categories} (personal with go-shield)",5);
            xsyslog("[INFO]: Builder: Launch compilation of personal categories");
            shell_exec("$nohup /usr/share/artica-postfix/bin/compile-category -compile-personal >/dev/null 2>&1 &");
        }else{
            build_progress("{compile_all_categories} (personal with go-shield) already running",5);
            xsyslog("[ERROR]: Builder: (personal with go-shield) already running");
        }
        $thesheild_dst="";
        if(is_file($thesheild_db)) {
            $thesheild_dst = md5_file($thesheild_db);
        }
        if($thesheild_dst<>$thesheild_src){
            if(is_file("/etc/init.d/theshields")) {
                shell_exec("/etc/init.d/theshields restart");
            }
        }
    }

    $ufdbGenTable=$unix->find_program("ufdbGenTable");
    $q->QUERY_SQL("DELETE FROM category_porn WHERE sitename='allocine.fr'");

    @mkdir("/etc/artica-postfix/pids",0755,true);
    @unlink("/etc/artica-postfix/pids/CompiledAllCategories");
    @file_put_contents("/etc/artica-postfix/pids/CompiledAllCategories", time());
    $CacheDatabase=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategoriesDatabase"));






    if(!is_file($ufdbGenTable)){
        build_progress("{compile}: missing compilator...",110);
        $unix->ToSyslog("[ERROR]: Builder: Missing compilator",false,"categories-update");
        return false;
    }

    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
    if($ManageOfficialsCategories==1){
        xsyslog("[INFO]: Builder: Compiling as Official Master categories");
        $sql="SELECT * FROM personal_categories WHERE official_category=1 order by category_id";
    }
    $results=$q->QUERY_SQL($sql);
    $total=pg_num_rows($results);
    $c=0;
    $timestart=time();
    $SUC=0;
    $CATNAMECOMPILE=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $c++;
        $prc=$c/$total;
        $prc=round($prc*100,0);
        if($prc<5){$prc=5;}
        if($prc>90){$prc=90;}
        $category_id=$ligne["category_id"];
        $categoryname=$ligne["categoryname"];
        $free_category=$ligne["free_category"];
        if($free_category==1){continue;}
        if(preg_match("#^reserved[0-9]+#", $categoryname)){continue;}
        $categorykey=$ligne["categorykey"];
        $table=$ligne["categorytable"];
        $items=$ligne["items"];
        if(!$q->TABLE_EXISTS($table)){continue;}
        $items_sql=$q->COUNT_ROWS_LOW($table);
        $q->QUERY_SQL("UPDATE personal_categories SET items='$items_sql' WHERE category_id='$category_id'");

        if($items_sql==$CacheDatabase[$category_id]){
            $SKIPPED[]="SKIPPED: $categoryname";
            xsyslog("[INFO]: Builder: SKIPING $categoryname (no change)");
            echo "[$category_id]: $categoryname/$categorykey table $table items:$items -> Skipping\n";
            continue;
        }

        if(compile_single_category($category_id,$prc)){
            $SUC++;
            $CATNAMECOMPILE[]=$categoryname;
            $t=time();
            $q->QUERY_SQL("UPDATE personal_categories SET compiledate='$t', compilerows='$items' WHERE category_id='$category_id'");
            xsyslog("[SUCCESS]: Builder: $categoryname compiled with $items items");
        }


    }

    if($SUC>0){
        $took=distanceOfTimeInWords($timestart,time(),true);
        squid_admin_mysql(2,"{success} {compiling} $SUC Web-filtering categories skipped:".count($SKIPPED)." ($took)",@implode("\n", $CATNAMECOMPILE)."\n".@implode("\n",$SKIPPED),__FILE__,__LINE__);


        if(is_file("/etc/init.d/ufdbcat")){
            build_progress("{reloading_services}",95);
            system("/etc/init.d/ufdbcat reload");
        }
        if(is_file("/etc/init.d/ufdb")){
            build_progress("{reloading_services}",96);
            system("/etc/init.d/ufdb reload");
        }
        $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
        if($UfdbCatsUpload==1){
            $unix=new unix();
            $php=$unix->LOCATE_PHP5_BIN();
            $nohup=$unix->find_program("nohup");
            if($GLOBALS["FAILED_UPLOADED"]>0) {
                shell_exec("$nohup $php /usr/share/artica-postfix/exec.upload.categories.php >/dev/null 2>&1 &");
            }
        }
    }

    $DD=date("Y-m-d");
    $BACKDIR="/home/artica/webfiltering-backup/$DD";
    if(is_dir($BACKDIR)){
        system("cd $BACKDIR");
        system("tar -czf /home/artica/webfiltering-backup/$DD.tar.gz *");
        system("rm -rf $BACKDIR");

    }
    build_progress("{compile_all_categories}: {done}",97);

    if(is_file("/etc/init.d/dnscatz")){
        build_progress("{compile_all_categories}: {reloading}",98);
        system("/etc/init.d/dnscatz reload");
    }

    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/personal-categories/reload");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_exec($cURLConnection);
    curl_close($cURLConnection);
    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    $cmd="/usr/sbin/artica-phpfpm-service -reload-proxy";
    $unix->framework_exec($cmd);

    build_progress("{compile_all_categories}: {done}",100);
    compile_catlist();
    cluster_table();
    return true;

}


function upload_category($filetext=null,$subdir=null){
    $unix=new unix();
    $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    if($UfdbCatsUpload==0){return false;}
    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));
    $UfdbCatsUploadFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPTLS"));
    $servlog="$UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv";
    if($UfdbCatsUploadFTPDir==null){$UfdbCatsUploadFTPDir="/";}

    if ( ($UfdbCatsUploadFTPserv==null) OR ($UfdbCatsUploadFTPusr==null)){
        return false;
    }

    $curl=$unix->find_program("curl");
    $proto="ftp";
    if($UfdbCatsUploadFTPTLS==1){$proto="ftps";}
    $cmd[]="$curl";

    if(!is_file($filetext)){
        $unix->ToSyslog("[INFO]: Builder: Unable to find $filetext",false,"categories-update");
        echo "!!! unable to find $filetext\n";
        die();
    }

    $file = basename($filetext);
    $slog[]="PUT: $filetext transformed to $file";
    $tmpfile=$unix->FILE_TEMP();

    if($subdir<>null){
        $slog[]="MOD: $UfdbCatsUploadFTPDir add subdirectory $subdir";
        $UfdbCatsUploadFTPDir="$UfdbCatsUploadFTPDir/$subdir";
    }

    $cmd[]="-T $filetext";
    if($UfdbCatsUploadFTPusr<>null){
        $UfdbCatsUploadFTPpass=$unix->shellEscapeChars($UfdbCatsUploadFTPpass);
        $cmd[]="--user $UfdbCatsUploadFTPusr:$UfdbCatsUploadFTPpass";
    }
    $cmd[]="--ftp-create-dirs";
    $cmd[]="$proto://$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$file";
    $cmd[]=">$tmpfile 2>&1";
    $unix->ToSyslog("[INFO]: Builder: uploading $file to $proto://$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$file",false,"categories-update");
    $cmdline=@implode(" ",$cmd);
    shell_exec($cmdline);
    $data=@file_get_contents($tmpfile);
    $infos=@explode("\n",$data);
    @unlink($tmpfile);
    $error_curl=compile_categories_upload_curl($infos);

    $slog[]="PUT: $filetext to $UfdbCatsUploadFTPDir/$file";
    if($error_curl<>null){
        $unix->ToSyslog("[ERROR]: Builder: Failed to upload $file by FTP with $error_curl",false,"categories-update");
        echo "Failed to upload $filetext to $UfdbCatsUploadFTPserv $error_curl\n";
        $slog[]=$error_curl;
        $slog[]="***********************\n";
        squid_admin_mysql(0, "{failed} to upload $file to $servlog", @implode("\n",$slog),
            __FILE__,__LINE__);
        @file_put_contents("$filetext.err",time());
        return false;
    }
    $unix->ToSyslog("[SUCCESS]: Builder: upload $file by FTP",false,"categories-update");
    return true;


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


function transfert_file($category_id,$tmpfile,$categoryname):bool{
    $destfile="/var/lib/squidguard/$category_id/domains";
    @mkdir(dirname($destfile),0755,true);
    if(is_file($destfile)){@unlink($destfile);}

    $out = fopen($destfile, 'wb');
    if(!$out){
        echo "Unable to fopen $destfile\n";
        xsyslog("[ERROR]: Builder: Unable to fopen $destfile");
        return false;
    }

    $handle = @fopen($tmpfile, "r");

    if (!$handle) {
        xsyslog("[ERROR]: Builder: Unable to fopen $tmpfile");
        echo "Unable to fopen $tmpfile\n";
        @unlink($tmpfile);
        return false;

    }

    while (!feof($handle)){
        $line=@fgets($handle);
        $line=trim($line);
        if($line==null){continue;}
        $line=str_replace("..", ".", $line);
        $line=str_replace('"', "", $line);
        $line=str_replace("'", "", $line);
        if(substr($line,0,2)=="*."){$line=substr($line,2,strlen($line));}
        if(substr($line, 0,1)=="."){$line=substr($line, 1,strlen($line));}

        if(substr($line, strlen($line)-1,1)=="."){
            echo "ALERT3 $line for $categoryname\n";
            continue;
        }

        if(preg_match("#\.rar$#", $line)){
            echo "ALERT5 $line for $categoryname\n";
            continue;
        }

        if(preg_match("#^(.+?):[0-9]+$#", $line,$re)){$line=trim($re[1]);}
        if(strpos($line, "%")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "@")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, ")")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, ":")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, ";")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "/")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "(")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "}")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "<")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, ">")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "¦")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "÷")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "\\")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "*")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "#")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, " ")>0){ echo "skip $line for $categoryname\n";continue;}
        if(strpos($line, "..")>0){ echo "skip $line for $categoryname\n";continue;}
        if(preg_match("#^www\.(.+?)#", $line,$re)){$line=$re[1];}
        if(preg_match("#^www[0-9]+\.(.+?)#", $line,$re)){$line=$re[1];}
        if(preg_match("#^ww[0-9]+\.(.+?)#", $line,$re)){$line=$re[1];}
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($line),$re)){$line=ip2long(trim($line)).".addr";}


        $newline="$line\n";
        fwrite($out, $newline);
    }

    @fclose($handle);
    @fclose($out);
    xsyslog("[INFO]: Builder: Prepare $destfile for compilation done");
    return true;

}
function events($text=null,$function=null,$file=null,$line=null){
    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("categories-backup", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, "$text ($function/$line)");}
    if(function_exists("closelog")){closelog();}

}

function clean_local_db(){

    $trackers=array();
    $advertising=array();
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/databases/trackers.txt"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#(actonservice|gigya-api|mobileapptracking|hyros|cedexis|stackpathdns|wpengine\.netdna-cdn)\.(com|net)$#",$line)){
            continue;
        }
        if(preg_match("#^(ads|ad|ad[0-9]+)\.#",$line)){
            $advertising[$line]=true;
            continue;
        }
        $trackers[$line]=true;
    }
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/databases/advertising.txt"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(isset($trackers[$line])){continue;}
        if(preg_match("#(mobileapptracking|hyros|cedexis|stackpathdns)\.(com|net)$#",$line)){
            continue;
        }

        if(preg_match("#(tracking|track\.|trck\.|tracker|analytics|analys|online-metrix\.net)#",$line)){
            $trackers[$line]=true;
            continue;
        }
        if(preg_match("#(actonservice|gigya-api|mobileapptracking|hyros|cedexis|stackpathdns|wpengine\.netdna-cdn)\.(com|net)$#",$line)){
            $trackers[$line]=true;
            continue;
        }
        if(preg_match("#^track#",$line)){
            $trackers[$line]=true;
            continue;
        }




        $advertising[$line]=true;
    }


    ksort($advertising);
    ksort($trackers);
    $tracks=array();
    $advs=array();
    foreach ($advertising as $line=>$none){
        $advs[]=$line;
    }
    foreach ($trackers as $line=>$none){
        $tracks[]=$line;
    }

    @file_put_contents("/usr/share/artica-postfix/ressources/databases/advertising.txt",@implode("\n",$advs));
    @file_put_contents("/usr/share/artica-postfix/ressources/databases/trackers.txt",@implode("\n",$tracks));
    echo "Done...\n";
}
