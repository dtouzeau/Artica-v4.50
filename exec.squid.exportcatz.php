<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');



if($argv[1]=="--category"){export_category($argv[2]);}
if($argv[1]=="--categories"){export_allcategories($argv[2]);}


function export_allcategories(){
		$q=new mysql_squid_builder();
		$tables=$q->LIST_TABLES_CATEGORIES();
		while (list ($table, $none) = each ($tables) ){	
			export_category($table);
		}
	
	
}





function export_category($category){
	$q=new mysql_squid_builder();
	
	echo "Exporting $category\n";
	if(!preg_match("#^category_.+?#", $category)){
		$table="category_".$q->category_transform_name($category);
	}else{
		$table=$category;
	}
	$t=time();
	$dirtmp="/tmp/categories_$t";
	$Finaldir="/root/categories";
	@mkdir($dirtmp);
	@mkdir($Finaldir);
	shell_exec("/bin/chmod 777 -R $dirtmp");
	$sql="SELECT zmd5,zDate,category,pattern,uuid FROM $table WHERE enabled=1 ORDER BY pattern
	INTO OUTFILE '$dirtmp/$table.csv'
	FIELDS TERMINATED BY ','
	ENCLOSED BY '\"'
	LINES TERMINATED BY '\\n'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	if(!is_file("$dirtmp/$table.csv")){echo "$dirtmp/$table.csv no such file\n";return;}
	echo "$dirtmp/$table.csv success\n";
	compress("$dirtmp/$table.csv","$dirtmp/$table.csv.gz");
	if(!is_file("$dirtmp/$table.csv.gz")){echo "Failed $dirtmp/$table.csv.gz no such file\n";return;}
	@unlink("$dirtmp/$table.csv");
	echo "Success $dirtmp/$table.csv.gz\n";
	copy("$dirtmp/$table.csv.gz", "$Finaldir/$table.csv.gz");
	@unlink("$dirtmp/$table.csv.gz");
	
	
}

function compress($source,$dest){
    
    $mode='wb9';
    $error=false;
    $fp_out=gzopen($dest,$mode);
    if(!$fp_out){return;}
    $fp_in=fopen($source,'rb');
    if(!$fp_in){return;}
    
    while(!feof($fp_in)){
    	gzwrite($fp_out,fread($fp_in,1024*512));
    }
    fclose($fp_in);
    gzclose($fp_out);
	return true;
}

function oldcompress($srcName, $dstName){
if(is_file($dstName)){@unlink($dstName);}	
$fp = fopen($srcName, "r");
$data = fread ($fp, filesize($srcName));
fclose($fp);

$zp = gzopen($dstName, "w9");
gzwrite($zp, $data);
gzclose($zp);
echo "Compress: $dstName (". filesize($dstName)." bytes) done\n";

}