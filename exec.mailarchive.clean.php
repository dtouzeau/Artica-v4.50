<?php

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.archive.builder.inc');





$q=new mysql_squid_builder();

$q=new mysql_mailarchive_builder();


$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'mailarchive'";
$results=$q->QUERY_SQL($sql,"mailarchive");
while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
	
	$tablename=$ligne["c"];
	if($tablename=="indextables"){continue;}
	$Cyear=substr($tablename, 0,4);
	$CMonth=substr($tablename,4,2);
	$CDay=substr($tablename,6,2);
	$CDay=str_replace("_", "", $CDay);
	$xtime=strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	if(date("Y",$xtime)==date("Y")){continue;}
	echo "Delete Table $tablename\n";
	$q->QUERY_SQL("DROP TABLE `$tablename`");	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	
	
	

}


