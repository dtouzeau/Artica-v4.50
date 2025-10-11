<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;$GLOBALS["ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.dump.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--visited"){days_visited();exit;}
if($argv[1]=="--week"){week_visited();exit;}
if($argv[1]=="--month"){month_visited();exit;}



start();


function start(){
	if($GLOBALS["VERBOSE"]){echo "Starting....\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		squid_admin_mysql(1, "already $pid pid exists in memory, aborting", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	LoadParams();
	if($GLOBALS["EnableBackup"]==0){
		squid_admin_mysql(1, "Backup database statistics is currently disabled, remove this task in this case...", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}
	
	$DaysbackupOlder=$GLOBALS["DaysbackupOlder"];
	$workdir=$GLOBALS["WORKDIR"];
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,zDate,DATE_FORMAT(zDate,'%Y%m%d') AS suffix FROM  tables_day WHERE backuped=0 AND zDate<DATE_SUB(NOW(),INTERVAL $DaysbackupOlder DAY) ORDER BY zDate";
	echo $sql."\n";
	
	$workdir=$SquidBackupStats["workdir"];
	@mkdir($workdir,0755,true);
	if(!is_dir($workdir)){
		squid_admin_mysql(1, "$workdir, permission denied...", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		squid_admin_mysql(1, "Fatal, $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}
	
	$GLOBALS["TABLECOUNT"]=0;
	$GLOBALS["BACKUPED_SIZE"]=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "To backup {$ligne["tablename"]}\n";}
		$filename="$workdir/{$ligne["tablename"]}.gz";
		if(is_file($filename)){@unlink($filename);}
		if(!$q->TABLE_EXISTS($ligne["tablename"])){continue;}
		if(!backupTable($ligne["tablename"],$filename)){continue;}
		
		$filesize=$unix->file_size($filename);
		$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;
		$GLOBALS["TABLECOUNT"]++;
		if($GLOBALS["VERBOSE"]){echo "$filename ($filesize)\n";}
		$q->QUERY_SQL("UPDATE tables_day SET backuped=1 WHERE tablename='{$ligne["tablename"]}'");
		if(!$q->ok){squid_admin_mysql(1, "Fatal, MySQL error $q->mysql_error on tables_day", __FUNCTION__, __FILE__, __LINE__, "backup");return;}
		
		if(system_is_overloaded(basename(__FILE__))){sleep(15);if(system_is_overloaded(__FILE__)){sleep(10);if(system_is_overloaded(__FILE__)){sleep(5);}}}			
		
		if(system_is_overloaded(basename(__FILE__))){			
			squid_admin_mysql(1, "Fatal, {OVERLOADED_SYSTEM}, aborting task and restart in newt cycle...", __FUNCTION__, __FILE__, __LINE__, "backup");
			if($GLOBALS["TABLECOUNT"]>0){
				$GLOBALS["BACKUPED_SIZET"]=FormatBytes($GLOBALS["BACKUPED_SIZE"]/1024);
				squid_admin_mysql(1, "Success backuped {$GLOBALS["TABLECOUNT"]} tables {$GLOBALS["BACKUPED_SIZET"]} added in backuped directory", __FUNCTION__, __FILE__, __LINE__, "backup");return;
			}
		 return;
		}
		
	}
	
	days_visited();
	week_visited();
	month_visited();
	
	$took=$unix->distanceOfTimeInWords($t,time());
	if($GLOBALS["TABLECOUNT"]>0){
		$GLOBALS["BACKUPED_SIZET"]=FormatBytes($GLOBALS["BACKUPED_SIZE"]/1024);
		squid_admin_mysql(1, "Success backuped {$GLOBALS["TABLECOUNT"]} tables {$GLOBALS["BACKUPED_SIZET"]} added in backuped directory took:$took", __FUNCTION__, __FILE__, __LINE__, "backup");return;
	}
	
	
}

function backupTable($tablename,$filename){
	if(isset($GLOBALS["ALREADYDONETABLE"][$tablename])){return true;}
	$GLOBALS["ALREADYDONETABLE"][$tablename]=true;
	$unix=new unix();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($tablename)){return true;}
	
	$q->BD_CONNECT();
	$dump=new phpMyDumper("squidlogs",$q->mysqli_connection,$filename,true,$tablename);
	if(!$dump->doDump()){
		squid_admin_mysql(1, "Fatal, unable to dump database {$ligne["tablename"]}", __FUNCTION__, __FILE__, __LINE__, "backup");
		return false;
	}

	$filesize=$unix->file_size($filename);
	if($filesize<200){
		squid_admin_mysql(1, "Fatal, $filesize bytes it seems there is an issue on $tablename table", __FUNCTION__, __FILE__, __LINE__, "backup");
		return false;
	}

	$q->QUERY_SQL("INSERT INTO webstats_backup (`tablename`,`filepath`,`filesize`) 
	VALUES('$tablename','$filename','$filesize')");
	if(!$q->ok){squid_admin_mysql(1, "Fatal, MySQL error $q->mysql_error on webstats_backup", __FUNCTION__, __FILE__, __LINE__, "backup");return false;}
	$q->DELETE_TABLE($tablename);
	return true;
	
}

function LoadParams(){
	
	$sock=new sockets();
	$SquidBackupStats=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBackupStats")));
	$t=time();
	$DaysbackupOlder=$SquidBackupStats["DaysbackupOlder"];
	$MonthsbackupOlder=$SquidBackupStats["MonthsbackupOlder"];
	$WeekBackupOlder=$SquidBackupStats["WeekBackupOlder"];
	$EnableBackup=$SquidBackupStats["EnableBackup"];
	if(!is_numeric($EnableBackup)){$EnableBackup=0;}
	if(!is_numeric($DaysbackupOlder)){$DaysbackupOlder="180";}
	if(!is_numeric($MonthsbackupOlder)){$MonthsbackupOlder="6";}
	if(!is_numeric($WeekBackupOlder)){$WeekBackupOlder="24";}
	if($SquidBackupStats["workdir"]==null){$SquidBackupStats["workdir"]="/home/artica/backup-squid-stats";}	
	if($DaysbackupOlder<7){$DaysbackupOlder=7;}
	if($MonthsbackupOlder<3){$MonthsbackupOlder=3;}
	if($WeekBackupOlder<3){$WeekBackupOlder=3;}
	$GLOBALS["EnableBackup"]=$EnableBackup;
	$GLOBALS["DaysbackupOlder"]=$DaysbackupOlder;
	$GLOBALS["WORKDIR"]=$SquidBackupStats["workdir"];
	$users=new usersMenus();
	if(!is_numeric($GLOBALS["DaysbackupOlder"])){$GLOBALS["DaysbackupOlder"]=90;}
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$GLOBALS["EnableBackup"]=0;}
}

function week_visited(){
	$unix=new unix();
	LoadParams();
	$DaysbackupOlder=$GLOBALS["DaysbackupOlder"];
	$workdir=$GLOBALS["WORKDIR"];
	$q=new mysql_squid_builder();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT WEEK( NOW( ) ) AS tweek, YEAR( NOW( ) ) AS tyear","artica_events"));
	
	$current_table0="{$ligne["tyear"]}{$ligne["tweek"]}_week";
	
	if(strlen($ligne["tweek"])==1){$ligne["tweek"]="0".$ligne["tweek"];}
	$current_table1="{$ligne["tyear"]}{$ligne["tweek"]}_week";
	$current_table2="{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";
	
	$sql="SELECT WEEK( zDate ) AS tweek, YEAR( zDate ) AS tyear FROM tables_day WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 200 DAY ) GROUP BY tweek, tyear";
	$GLOBALS["BACKUPED_SIZE"]=0;
	$GLOBALS["TABLECOUNT"]=0;
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		squid_admin_mysql(1, "Fatal, $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$alt_table0=null;
		$alt_table1=null;
		if(strlen($ligne["tweek"])==1){
			$alt_table0="{$ligne["tyear"]}{$ligne["tweek"]}_week";
			$alt_table1="{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";
			$ligne["tweek"]="0".$ligne["tweek"];
		}
		$tablename="{$ligne["tyear"]}{$ligne["tweek"]}_week";
		if($tablename==$current_table1){continue;}
		if($alt_table0==$current_table0){continue;}
		
		
		if($alt_table0<>null){
			if($GLOBALS["VERBOSE"]){echo "$alt_table0\n";}
			$filename="$workdir/$alt_table0.gz";
			if(is_file($filename)){@unlink($filename);}
			if(backupTable($alt_table0,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
				}
			}			
		}
		
		if($alt_table1<>null){
			if($GLOBALS["VERBOSE"]){echo "$alt_table1\n";}
			$filename="$workdir/$alt_table1.gz";
			if(is_file($filename)){@unlink($filename);}
			if(backupTable($alt_table1,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
				}
			}			
		}		
		
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
		if(is_file($filename)){
			$filesize=$unix->file_size($filename);
			$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
			$GLOBALS["TABLECOUNT"]++;
			}
		}			
		
			$tablename="{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";	
			if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
			$filename="$workdir/$tablename.gz";
			if(is_file($filename)){@unlink($filename);}
			if(backupTable($tablename,$filename)){
				if(is_file($filename)){
					$filesize=$unix->file_size($filename);
					$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
					$GLOBALS["TABLECOUNT"]++;
				}
			}

		if(system_is_overloaded(basename(__FILE__))){sleep(15);if(system_is_overloaded(__FILE__)){sleep(10);if(system_is_overloaded(__FILE__)){sleep(5);}}}			
		if(system_is_overloaded(basename(__FILE__))){			
			squid_admin_mysql(1, "Fatal, {OVERLOADED_SYSTEM}, aborting task and restart in newt cycle...", __FUNCTION__, __FILE__, __LINE__, "backup");		
			return;
		}				
		
	}	
	
	
}

function days_visited(){
	$unix=new unix();
	LoadParams();
	$DaysbackupOlder=$GLOBALS["DaysbackupOlder"];
	$workdir=$GLOBALS["WORKDIR"];
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS suffix FROM  tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL $DaysbackupOlder DAY)";
	echo $sql."\n";	
	
	$GLOBALS["BACKUPED_SIZE"]=0;
	$GLOBALS["TABLECOUNT"]=0;
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		squid_admin_mysql(1, "Fatal, $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		
		
		$tablename="{$ligne["suffix"]}_visited";
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}
		
		$tablename="{$ligne["suffix"]}_members";
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}	

		$tablename="{$ligne["suffix"]}_hour";
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}
		
		$tablename="{$ligne["suffix"]}_visited";
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}		

		
		$tablename="{$ligne["suffix"]}_blocked";
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}		

		if(system_is_overloaded(basename(__FILE__))){sleep(15);if(system_is_overloaded(__FILE__)){sleep(10);if(system_is_overloaded(__FILE__)){sleep(5);}}}			
		if(system_is_overloaded(basename(__FILE__))){			
			squid_admin_mysql(1, "Fatal, {OVERLOADED_SYSTEM}, aborting task and restart in newt cycle...", __FUNCTION__, __FILE__, __LINE__, "backup");		
			return;
		}

	}
	
}


function month_visited(){
	$unix=new unix();
	LoadParams();
	$DaysbackupOlder=$GLOBALS["DaysbackupOlder"];
	$workdir=$GLOBALS["WORKDIR"];
	$q=new mysql_squid_builder();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT MONTH( NOW( ) ) AS tmonth, YEAR( NOW( ) ) AS tyear","artica_events"));
	
	if(strlen($ligne["tmonth"])==1){$ligne["tmonth"]="0".$ligne["tmonth"];}
	$current_table="{$ligne["tyear"]}{$ligne["tmonth"]}_day";
	
	
	
	$sql="SELECT MONTH( zDate ) AS tmonth, YEAR( zDate ) AS tyear FROM tables_day 
	WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 200 DAY ) GROUP BY tmonth, tyear";
	$GLOBALS["BACKUPED_SIZE"]=0;
	$GLOBALS["TABLECOUNT"]=0;
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		squid_admin_mysql(1, "Fatal, $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");
		return;
	}	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$alt_table0=null;
		$alt_table1=null;
		if(strlen($ligne["tmonth"])==1){$ligne["tmonth"]="0".$ligne["tmonth"];}
		$tablename="{$ligne["tyear"]}{$ligne["tmonth"]}_day";
		if($tablename==$current_table){continue;}
		
	
			
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
		if(is_file($filename)){
			$filesize=$unix->file_size($filename);
			$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
			$GLOBALS["TABLECOUNT"]++;
			}
		}
		
		$tablename="{$ligne["tyear"]}{$ligne["tmonth"]}_members";	
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}
		
		$tablename="{$ligne["tyear"]}{$ligne["tmonth"]}_blocked_days";	
		if($GLOBALS["VERBOSE"]){echo "$tablename\n";}
		$filename="$workdir/$tablename.gz";
		if(is_file($filename)){@unlink($filename);}
		if(backupTable($tablename,$filename)){
			if(is_file($filename)){
				$filesize=$unix->file_size($filename);
				$GLOBALS["BACKUPED_SIZE"]=$GLOBALS["BACKUPED_SIZE"]+$filesize;		
				$GLOBALS["TABLECOUNT"]++;
			}
		}		

		if(system_is_overloaded(basename(__FILE__))){sleep(15);if(system_is_overloaded(__FILE__)){sleep(10);if(system_is_overloaded(__FILE__)){sleep(5);}}}			
		if(system_is_overloaded(basename(__FILE__))){			
			squid_admin_mysql(1, "Fatal, {OVERLOADED_SYSTEM}, aborting task and restart in newt cycle...", __FUNCTION__, __FILE__, __LINE__, "backup");		
			return;
		}				
		
	}	
	
	
}
