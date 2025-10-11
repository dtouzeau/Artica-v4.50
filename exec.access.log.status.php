<?php
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


$file=$argv[1];
$user_req=trim(strtolower($argv[2]));


$handle = @fopen($file, "r");
if (!$handle) {echo "Failed to open file\n";return;}

$rex="^[0-9\.]+\s+[0-9]+\s+[0-9\.]+\s+[A-Z0-9\/_]+\s+[0-9]+\s+[A-Z]+\s+(.*?)\s+.*?\s+[A-Z_0-9]+";
$fam=new squid_familysite();
$catz=new mysql_catz();
while (!feof($handle)) {
    $c++;
    $pattern = trim(fgets($handle));
    if ($pattern == null) {
        continue;
    }
    if (!preg_match("#$rex#", $pattern,$re)) {
        echo $pattern . "\n";
        continue;
    }
    $sitename=$re[1];
    //echo $sitename." -> ";
    if(preg_match("#http:\/\/#",$sitename)){
        $URLAR=parse_url($sitename);
        if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
      //  echo " parse_url($sitename) -> ";
    }

    if(preg_match("#^www\.(.*?)#", $sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^(.*?):[0-9]+#", $sitename,$re)){$sitename=$re[1];}
    //echo " preg_match($sitename) -> ";
    $sitename=$fam->GetFamilySites($sitename);
    //echo $sitename."\n";
    if(!isset($MAIN[$sitename])){
        $MAIN[$sitename]["COUNT"]=0;
    }
    $MAIN[$sitename]["COUNT"]++;
    if(!isset($MAIN[$sitename]["CAT"])) {
        $cat=$catz->limited_categorize($sitename);
        $cat=$cat.",".$catz->CategoryIntToStr($cat);
        $MAIN[$sitename]["CAT"] =$cat;
    }
}

foreach ($MAIN as $sitename=>$requests){
    echo "$sitename,{$requests["COUNT"]},{$requests["CAT"]}\n";
}
