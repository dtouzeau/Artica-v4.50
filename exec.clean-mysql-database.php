<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

$unix=new unix();
$q=new mysql_squid_builder();
if(is_dir("/opt/squidsql/data")){

	$Dirs=$unix->dirdir("/opt/squidsql/data");
	while (list ($directory, $none) = each ($Dirs) ){

		$Files=$unix->DirFiles($directory,"[0-9]+_.*?\.MYI$");
		foreach ($Files as $filename=>$none){
			$tablename=$filename;
			$tablename=str_replace(".MYI", "", $tablename);
			$fullpath="$directory/$filename";
			echo "HAVE TO DELETE $tablename\n";
			$q->QUERY_SQL("DROP TABLE $tablename");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
		
}
}



$f=LIST_TABLES_gen("dansguardian_events_%");
$hours2=LIST_TABLES_gen("squidhour_%");
$hours3=LIST_TABLES_gen("sizehour_%");
$hours3=LIST_TABLES_gen("quotaday_%");
$hours3=LIST_TABLES_gen("RTTD_%");

while (list ($num, $table) = each ($f) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($hours2) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($hours2) ){$squidlogs[$num]=true;}



$q=new mysql_squid_builder();
while (list ($tablename, $none) = each ($squidlogs)){
	echo "Cleaning $tablename\n";
	if(!$q->TABLE_EXISTS($tablename)){continue;}
	if($q->COUNT_ROWS($table)==0){$q->QUERY_SQL("DROP TABLE `$tablename`");continue;}
	$q->QUERY_SQL("DROP TABLE `$tablename`");
}

function LIST_TABLES_gen($pattern){
	$q=new mysql_squid_builder();

	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '$pattern'";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(preg_match("#^webfilter_#i", $ligne["c"])){continue;}
		$GLOBALS["LIST_TABLES_CACHE_DAY"][$ligne["c"]]=$ligne["c"];
		$array[$ligne["c"]]=$ligne["c"];

	}
	return $array;
}