<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["NOCHECK"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["CLUSTER"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--reload#",implode(" ",$argv),$re)){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--nochek#",implode(" ",$argv))){$GLOBALS["NOCHECK"]=true;}
if(preg_match("#--cluster#",implode(" ",$argv))){$GLOBALS["CLUSTER"]=true;}


include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.checks.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.access.manager.inc');
include_once(dirname(__FILE__).'/ressources/class.products-ip-ranges.inc');
include_once(dirname(__FILE__)."/ressources/class.certificate_parser.inc");
start();

function build_progress($text,$pourc){
    $echotext=$text;
    $echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/squid.ssl.whitelists.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);


}



function start(){
    $unix=new unix();
    if(!$GLOBALS["FORCE"]) {
        $ServerRunSince = $unix->ServerRunSince();
        if ($ServerRunSince < 3) {
            squid_admin_mysql(1, "Aborting apply white-lists server running less than 3mn", null, __FILE__, __LINE__);
            exit();
        }
    }

    $GLOBALS["OUTPUT"]=true;
    $squid=new squid_acls();
    $GLOBALS["GroupType"]["src"]="{src_addr}";
    $GLOBALS["GroupType"]["dst"]="{dst_addr}";
    $GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
    $squidbin=$unix->LOCATE_SQUID_BIN();
    $isSquid5=isSquid5();

    $ssl_whitelist="/etc/squid3/ssl_whitelist.conf";
    $ssl_splice_whitelist_dst="/etc/squid3/ssl_splice_whitelist.dst.conf";
    $ssl_splice_whitelist_src="/etc/squid3/ssl_splice_whitelist.src.conf.conf";
    $ssl_splice_whitelist_dstdomain="/etc/squid3/ssl_splice_whitelist.dstdomain.conf";
    $ssl_splice_whitelist_fingerprint="/etc/squid3/ssl_splice_whitelist.fingerprint.conf";
    $dstdomain_file = "/etc/squid3/acls_whitelist.dstdomain.conf";
    $dstdom_regex   = "/etc/squid3/acls_whitelist.dstdom_regex.conf";
    $dst_file       ="/etc/squid3/acls_whitelist.dst.conf";

    $crc32files[]="/etc/squid3/ssl.conf";
    $crc32files[]="intermediate_ca.pem";
    $crc32files[]=$dstdomain_file;
    $crc32files[]=$dstdom_regex;
    $crc32files[]=$dst_file;
    $crc32files[]=$ssl_whitelist;
    $crc32files[]=$ssl_splice_whitelist_dst;
    $crc32files[]=$ssl_splice_whitelist_src;
    $crc32files[]=$ssl_splice_whitelist_dstdomain;
    $crc32files[]=$ssl_splice_whitelist_fingerprint;

    foreach ($crc32files as $fname){
        $CRC1[$fname]=crc32_file($fname);
    }


    if(is_file($ssl_whitelist)) {
        @unlink($ssl_whitelist);
    }
    build_progress("{global_whitelists}: {apply}",15);
    build_progress("{global_whitelists}: {apply}",30);
    @touch($ssl_whitelist);
    squidprivs($ssl_whitelist);

    $final=array();
    $annotate_transaction=null;
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="CREATE TABLE IF NOT EXISTS `ssl_whitelist` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT ,
        `pattern` VARCHAR( 128 ) UNIQUE,
        `description` VARCHAR( 128 ),
        `zDate` text,
        `ztype` VARCHAR( 40 ) NOT NULL DEFAULT 'src',
        `enabled` INTEGER NULL,
         `auto` INTEGER NOT NULL DEFAULT 1,
         frommeta INTEGER
     )";
    $q->QUERY_SQL($sql);

    $sql="SELECT * FROM ssl_whitelist WHERE enabled=1";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        squid_admin_mysql(1, "Aborting reconfiguring white-lists server MySQL Error", $q->mysql_error,__FILE__,__LINE__);
        exit();
    }

    $final_access=array();
    $main=array();
    $main["src"]=array();
    $main["dstdomain"]=array();
    $main["dst"]=array();
    $main["server_cert_fingerprint"]=array();
    $numrows=count($results);
    $products_ip_ranges=new products_ip_ranges();

    $cert=new certgetter();
    $IPClass=new IP();
    $CLUSTERS=array();
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        $description=$ligne["description"];
        $pattern=trim($ligne["pattern"]);
        if($pattern==null){continue;}

        if($ligne["ztype"]=="server_cert_fingerprint"){
            $pattern=strtolower($ligne["pattern"]);

            if(preg_match("#^([a-z0-9:]+)#",$pattern,$re)){
                $main["server_cert_fingerprint"][]=$re[1];
                continue;
            }
            $sha1=$cert->Get_sha1_fingherprint($pattern);
            if($sha1==null){
                $q->QUERY_SQL("UPDATE ssl_whitelist SET enabled=0,description='$description $cert->error' WHERE ID=$ID");
                continue;
                
            }
            $description=$description." $pattern (".date("Y-m-d H:i:s").")";
            $q->QUERY_SQL("UPDATE ssl_whitelist SET pattern='$sha1',description='$description' WHERE ID=$ID");
            $main["server_cert_fingerprint"][]=$sha1;
            continue;
        }


        if( ($IPClass->isValid($pattern)) OR ($IPClass->IsARange($pattern)) OR ($IPClass->IsACDIR($pattern)) )  {
            if($ligne["ztype"]=="dstdomain"){$ligne["ztype"]="dst";}
        }
        if($ligne["ztype"]=="dstdom_regex"){$pattern=string_to_regex_3($pattern);}
        $main[$ligne["ztype"]][]=$pattern;
        echo "Starting......: ".date("H:i:s")." {$ligne["ztype"]} {$pattern}\n";
    }

    $memcache=new lib_memcached();

    foreach ($main["dstdomain"] as $dstdomain){
        $line=str_replace("^","",$dstdomain);
        if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}
        $memcache->saveKey("isWhite:$line",true,259200);
    }


    if($isSquid5){
        $final[]="acl AnnotateSSLGBW annotate_transaction whitelistssl=yes";
        $annotate_transaction = " AnnotateSSLGBW";
    }


    echo "Starting......: ".date("H:i:s")." Saving ". count($main["dstdomain"])." dstdomain items\n";
    $main["dstdomain"]=$squid->clean_dstdomains($main["dstdomain"]);
    $final[]="acl SSLWhitelistDomains dstdomain \"$ssl_splice_whitelist_dstdomain\"";
    $final_access[]="ssl_bump splice SSLWhitelistDomains$annotate_transaction";
    @file_put_contents($ssl_splice_whitelist_dstdomain, @implode("\n", $main["dstdomain"]));
    squidprivs($ssl_splice_whitelist_dstdomain);

    if(count($main["src"])>0){
        echo "Starting......: ".date("H:i:s")." Saving ". count($main["src"])." src items\n";
        $final[]="acl SSLWhitelistSRCNet src \"$ssl_splice_whitelist_src\"";
        $final_access[]="ssl_bump splice SSLWhitelistSRCNet$annotate_transaction";
        @file_put_contents("$ssl_splice_whitelist_src", @implode("\n", $main["src"]));
        squidprivs($ssl_splice_whitelist_src);
    }

    @unlink($ssl_splice_whitelist_fingerprint);
    @touch($ssl_splice_whitelist_fingerprint);
    squidprivs($ssl_splice_whitelist_fingerprint);
    if(count($main["server_cert_fingerprint"])>0) {
        $final[]="acl SSLWhitelistFingerprint server_cert_fingerprint \"$ssl_splice_whitelist_fingerprint\"";
        $final_access[]="ssl_bump splice SSLWhitelistFingerprint$annotate_transaction";
        @file_put_contents("$ssl_splice_whitelist_fingerprint",
            @implode("\n", $main["server_cert_fingerprint"]));

    }



    foreach ($main["dst"] as $ipdd){$TEMPARRAY[$ipdd]=true;}
    foreach ($TEMPARRAY as $num=>$val){$TEMPARRAY2[]=$num;}
    echo "Starting......: ".date("H:i:s")." Saving ". count($main["dst"])." dst items\n";
    $final[]="acl SSLWhitelistDSTNet dst \"$ssl_splice_whitelist_dst\"";
    $final_access[]="ssl_bump splice SSLWhitelistDSTNet$annotate_transaction";
    @file_put_contents($ssl_splice_whitelist_dst, @implode("\n", $TEMPARRAY2));
    squidprivs($ssl_splice_whitelist_dst);

    build_progress("{reconfiguring}",50);
    echo "Starting......: ".date("H:i:s")." Saving $ssl_whitelist items\n";

    $finalZ[]=@implode("\n", $final);
    $finalZ[]=@implode("\n", $final_access);
    @file_put_contents($ssl_whitelist, @implode("\n", $finalZ));
    squidprivs($ssl_whitelist);
    $squid_ssl=new squid_ssl();
    $squid_ssl->build();
    build_progress("{reconfiguring}",90);

    $RECOMPILE=false;
    foreach ($CRC1 as $fname=>$crc1){
        $crc=crc32_file($fname);
        if($crc<>$crc1){
            $RECOMPILE=true;
            break;
        }
    }

    if(!$RECOMPILE){
        echo "No changes...\n";
        build_progress("{success}",100);
        return true;
    }



    if($GLOBALS["NOCHECK"]){
        build_progress("{success}",91);

        if($GLOBALS["RELOAD"]){
            if( is_file($squidbin)){
                build_progress("{reloading}",95);
                squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
                system("/usr/sbin/artica-phpfpm-service -reload-proxy");
            }
        }
        build_progress("{done}",100);
        return true;
    }

    $tempsquid=$unix->TEMP_DIR()."/squid.conf";
    @unlink($tempsquid);
    @copy("/etc/squid3/squid.conf",$tempsquid);
    $squid_checks=new squid_checks($tempsquid);
    if(!$squid_checks->squid_parse()){
        build_progress("{checking}: {failed}",110);
        return false;
    }

    cluster_table();


    if($GLOBALS["RELOAD"]){
        if( is_file($squidbin)){
            squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
        }
    }

    build_progress("{success}",100);
    return true;
}


function squidprivs($path){
    @chmod($path, 0755);
    @chown($path,"squid");
    @chgrp($path, "squid");

}

function isSquid5(){
    if(preg_match("#^(5|6|7)\.#",GET_SQUID_VERSION())){return true;}
    return false;
}

function GET_SQUID_VERSION():string{
    if(isset($GLOBALS["GET_SQUID_VERSION"])){return $GLOBALS["GET_SQUID_VERSION"];}
    exec("/usr/sbin/squid -v 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#Squid Cache: Version\s+([0-9\.]+)#",$line,$re)){
            $GLOBALS["GET_SQUID_VERSION"]=strval($re[1]);
            return $GLOBALS["GET_SQUID_VERSION"];
        }
    }
    $GLOBALS["GET_SQUID_VERSION"]=strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion"));
    return $GLOBALS["GET_SQUID_VERSION"];
}



function string_to_regex_3($pattern){
    if(preg_match("#^regex:(.+)#", trim($pattern),$re)){return $re[1];}
    $pattern=str_replace(".", "\.", $pattern);
    $pattern=str_replace("(", "\(", $pattern);
    $pattern=str_replace(")", "\)", $pattern);
    $pattern=str_replace("+", "\+", $pattern);
    $pattern=str_replace("|", "\|", $pattern);
    $pattern=str_replace("{", "\{", $pattern);
    $pattern=str_replace("}", "\}", $pattern);
    $pattern=str_replace("?", "\?", $pattern);
    $pattern=str_replace("*", "?", $pattern);
    $pattern=str_replace("http://", "^http://", $pattern);
    $pattern=str_replace("https://", "^https://", $pattern);
    $pattern=str_replace("ftp://", "^ftp://", $pattern);
    $pattern=str_replace("ftps://", "^ftps://", $pattern);
    return $pattern;
}