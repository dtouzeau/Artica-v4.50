<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$pid=getmypid();
$pidefile="/etc/artica-postfix/croned.1/".basename(__FILE__).".pid";
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(file_exists($pidefile)){
	$currentpid=trim(@file_get_contents($pidefile));
	echo date('Y-m-d H:i:s')." NewPID PID: $pid\n";
	echo date('Y-m-d H:i:s')." Current PID: $currentpid\n";
	if($currentpid<>$pid){
		if(is_dir('/proc/'.$currentpid)){
			write_syslog("Already instance executed aborting...",__FILE__);
			exit();
			
	}else{
		echo date('Y-m-d H:i:s')." $currentpid is not executed continue...\n";
	}
		
	}
}

$users=new usersMenus();
$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==0){exit();}

if(BuildDayTable()){CleanSmtp_events_database();}
exit();

function BuildDayTable(){
	
	$q=new mysql();
	$q->BuildTables();
	if(!$q->TABLE_EXISTS('smtp_logs_day','artica_events')){
		send_email_events("Mysql error on smtp_logs_day table","Artica was unable to create or detect smtp_logs_day table...","system");
		return false;
	}
	$today=date('Y-m-d');
	
$sql="SELECT COUNT(id) as tcount,delivery_domain,DATE_FORMAT(time_stamp,'%Y-%m-%d') as tdate,bounce_error FROM 
	smtp_logs 
	GROUP BY delivery_domain,tdate,bounce_error HAVING tdate<'$today' ORDER BY tdate DESC"; 	
	
$q=new mysql();
$results=$q->QUERY_SQL($sql,"artica_events");

if(!$q->ok){
		echo "Wrong sql query $q->mysql_error\n";
		write_syslog("Wrong sql query $q->mysql_error",__FILE__);
		return false;
	}		
	
	
while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
	
		$count=$count+1;
		$emails=$ligne["tcount"];
		$delivery_domain=$ligne["delivery_domain"];
		$date=$ligne["tdate"];
		$bounce_error=$ligne["bounce_error"];
		$md5=md5($delivery_domain.$date.$bounce_error.$emails);
		
		$sql="INSERT IGNORE INTO smtp_logs_day (`key`,`day`,`delivery_domain`,`bounce_error`,`emails`)
		VALUES('$md5','$date','$delivery_domain','$bounce_error','$emails')";
		$q->QUERY_SQL($sql,"artica_events");
		

		if(!$q->ok){
				echo "Wrong sql query $q->mysql_error\n";
				write_syslog("Wrong sql query \"$sql\" $q->mysql_error",__FILE__);
				return false;
			}		
		
		}	
	
	return true;
	
}


function CleanSmtp_events_database(){}


?>