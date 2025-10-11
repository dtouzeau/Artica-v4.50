<?php

class mysql_status{
	private $mysqladmin;
	private $kill;
	private $mysqlcheck;
	private $mysqlhotcopy;
	private $mysqlcheck_running=false;
	private $mysqlhotcopy_running=false;
	private $nohup;
	private $MaxExecution=2880;
	
	function __construct(){
		$this->CheckMems();
		$this->mysqladmin	=	$GLOBALS["CLASS_UNIX"]->find_program("mysqladmin");
		$this->mysqlcheck	=	$GLOBALS["CLASS_UNIX"]->find_program("mysqlcheck");
		$this->mysqlhotcopy	=	$GLOBALS["CLASS_UNIX"]->find_program("mysqlhotcopy");
		$this->kill			=	$GLOBALS["CLASS_UNIX"]->find_program("kill");
		$this->nohup		=	$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		$this->CheckProcesses();
	}
	
	private function CheckMems(){
		if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		
	}
	
	
	function MainInstance(){
		if($this->mysqlcheck_running){if($GLOBALS["VERBOSE"]){echo "*** mysqlcheck is running - ABORT -\n";}return true;}
		if($this->mysqlhotcopy_running){if($GLOBALS["VERBOSE"]){echo "*** mysqlhotcopy is running - ABORT -\n";}return true;}
		
		if(!is_file("/etc/artica-postfix/settings/Mysql/mysql_server")){@touch("/etc/artica-postfix/settings/Mysql/mysql_server");}
		if(!is_file("/etc/artica-postfix/settings/Mysql/database_password")){@touch("/etc/artica-postfix/settings/Mysql/database_password");}
		if(!is_file("/etc/artica-postfix/settings/Mysql/database_admin")){@touch("/etc/artica-postfix/settings/Mysql/database_admin");}
		
		
		$database_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		if(is_file("/etc/artica-postfix/settings/Mysql/database_admin")) {
            $database_admin = trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
        }
		$mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		if($mysql_server==null){$mysql_server="127.0.0.1";}
		if($mysql_server=="localhost"){$mysql_server="127.0.0.1";}
		if($database_admin==null){$database_admin="root";}
		if($mysql_server<>"127.0.0.1"){if($GLOBALS["VERBOSE"]){echo "*** $mysql_server is not 127.0.0.1 - ABORT -\n";}return true;}
		if($database_password<>null){
			$database_password=$GLOBALS["CLASS_UNIX"]->shellEscapeChars($database_password);
			$database_password=" -p$database_password ";
		}
		$cmdline="$this->mysqladmin -S /var/run/mysqld/mysqld.sock -u $database_admin$database_password processlist 2>&1";
		if($GLOBALS["VERBOSE"]){echo "*** Run -> $cmdline\n";}
		exec($cmdline,$results);
		$ParseErrors=$this->ParseErrors($results);
		if($ParseErrors==0){return;}
		if($ParseErrors==1){
			squid_admin_mysql(0,"Fatal: Too Many connections, restart mysql service.", null,__FILE__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->send_email_events("Fatal: Too Many connections, restart mysql service.",__CLASS__."/".__FUNCTION__." in file ".basename(__FILE__), "system");
			shell_exec("$this->nohup /etc/init.d/mysql restart --force --framework=".__FILE__." >/dev/null 2>&1 &");
		}
	}
	
	
	private function CheckProcesses(){
		if(!isset($GLOBALS["mysql_CheckProcesses"])){$GLOBALS["mysql_CheckProcesses"]=0;}
		$this->mysqlhotcopy_running=false;
		$this->mysqlcheck_running=false;
		if($GLOBALS["mysql_CheckProcesses"]>5){return;}
		
		$restart=false;
		$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($this->mysqlcheck);
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
			$timexec=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			if($timexec>$this->MaxExecution){
				$cmdlineTXT=@file_get_contents("/proc/$pid/cmdline");
				shell_exec("$this->kill -9 $pid");
				$GLOBALS["CLASS_UNIX"]->send_email_events("Fatal: Killed a mysqlcheck instance freezed and run since {$timexec}Mn", "Max execution time is:{$this->MaxExecution}Mn\nRunned $cmdlineTXT", "system");
				$restart=true;
		
			}
			if($GLOBALS["VERBOSE"]){echo "mysqlcheck PID: $pid running since $timexec minutes\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
				$this->mysqlcheck_running=true;
			}
		}
		
		$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($this->mysqlhotcopy);
		if($GLOBALS["CLASS_UNIX"]->process_exists($GLOBALS["CLASS_UNIX"]->PIDOF($this->mysqlhotcopy))){
			$timexec=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
				
			if($timexec>$this->MaxExecution){
				$cmdlineTXT=@file_get_contents("/proc/$pid/cmdline");
				shell_exec("$this->kill -9 $pid");
				$GLOBALS["CLASS_UNIX"]->send_email_events("Fatal: Killed a mysqlhotcopy instance freezed and run since {$timexec}Mn", "Max execution time is:{$this->MaxExecution}Mn\nRunned $cmdlineTXT", "system");
				$restart=true;
			}
				
			if($GLOBALS["VERBOSE"]){echo "mysqlhotcopy PID: $pid running since $timexec minutes\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){$this->mysqlhotcopy_running=true;}
		}
		
		$GLOBALS["mysql_CheckProcesses"]=$GLOBALS["mysql_CheckProcesses"]+1;
		if($restart){$this->CheckProcesses();}
	}
	
	
	private function ParseErrors($array){
		reset($array);
		foreach ($array as $line){
			if($GLOBALS["VERBOSE"]){echo "*** Analyze: \"$line\"\n";}
			
			if(preg_match("#error.*?Too many connections#", $line)){
				squid_admin_mysql(0,"Fatal: Too Many connections, restart mysql service.", $line,__FILE__,__LINE__);
				return 1;
			}
			
		}
		
		return 0;
	}
	
	
}