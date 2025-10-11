<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string'," Fatal..:");
    ini_set('error_append_string',"\n");
}


GetSizeof();



function GetSizeOf(){
    $unix=new unix();
    $main_sitepath="/home/wordpress_sites";

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $wpcli=$unix->find_program("wp-cli.phar");

    $results=$q->QUERY_SQL("SELECT * FROM wp_sites ORDER BY hostname");

    if(!$q->FIELD_EXISTS("wp_sites","site_size")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD site_size INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("wp_sites","wp_version")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD wp_version text NOT NULL DEFAULT '0.0'");
    }

    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $directory="$main_sitepath/site{$ID}";
        $size=$unix->DIRSIZE_BYTES_NOCACHE($directory);
        $version=trim(exec("$wpcli --allow-root --path=$directory core version 2>&1"));
        $q->QUERY_SQL("UPDATE wp_sites SET site_size=$size,wp_version='$version' WHERE ID=$ID");
    }



}



//$q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");