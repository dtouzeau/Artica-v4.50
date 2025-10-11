<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
$GLOBALS["NOBLOG"]=false;
$GLOBALS["NOMALW"]=false;
$GLOBALS["NODYN"]=false;
$GLOBALS["NOAUDIO"]=false;
$GLOBALS["NOISP"]=false;
$GLOBALS["NOTRACK"]=false;
$GLOBALS["NOWEBTV"]=false;
$GLOBALS["LIMIT"]=0;
$GLOBALS["ANDCLEAN"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if($argv[1]="--check"){knton($argv[2]);exit;}




function knton($www){
        $GLOBALS["OUTPUT"]=true;
	    $GLOBALS["DEBUG_EXTERN"]=true;
        $f=new external_categorize(null);
        $articacats=$f->K9_TESTS($www);
        echo "K9_TESTS: $articacats\n";

}

