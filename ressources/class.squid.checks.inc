<?php


class squid_checks{
	private $sourcefile=null;
	private $squidbin=null;
	public $results=array();
	
	function __construct($sourceconf=null){
		if($sourceconf==null){$sourceconf="/etc/squid3/squid.conf";}
		$this->sourcefile=$sourceconf;
		$this->squidbin=$this->find_program("squid3");
        if(is_null($this->squidbin)){$this->squidbin=$this->find_program("squid");}
        if(!is_file($this->squidbin)){$this->squidbin=$this->find_program("squid");}
		
	}
	
	function ignoreline($ligne){
		$ligne=trim($ligne);
		if($ligne==null){return true;}
		if(strpos($ligne,"| WARNING:")>0){return true;}
		if(strpos($ligne,"url_regex -i")>0){return true;}
		if(preg_match("#Processing:\s+#", $ligne)){return true;}
		if(preg_match("#Warning: empty ACL#", $ligne)){return true;}
		if(preg_match("#searching predictable#", $ligne)){return true;}
		if(preg_match("#is a subnetwork of#", $ligne)){return true;}
		if(preg_match("#You should probably#", $ligne)){return true;}
		if(preg_match("#Startup:\s+#", $ligne)){return true;}
		
	}
	
	function isfatal($ligne){
		if(preg_match("#Segmentation fault#", $ligne)){
			echo "Starting......: ".date("H:i:s")." [SYS]: !!!! $ligne !!!!!\n";
			if(function_exists("build_progress")){build_progress("{operation_failed} Segmentation fault !!!",110);}
			return true;
		}
		
	}
	
	private function OutputLines($ligne){
		
		if(preg_match("#\/etc\/squid3\/(.+?):([0-9]+)#", $ligne,$ri)){
			$Buggedline=$ri[2];
			echo "Starting......: ".date("H:i:s")." [SYS]: Line number: $Buggedline on file {$ri[1]}\n";
			$tt=explode("\n",@file_get_contents("/etc/squid3/{$ri[1]}"));
			
			echo "Starting......: ".date("H:i:s")." [SYS]: *****************************\n";
			for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
				$lineNumber=$i+1;
				if(trim($tt[$i])==null){continue;}
					echo "Starting......: ".date("H:i:s")." [SYS]: [line:$lineNumber]: \"{$tt[$i]}\"\n";
				}
			echo "Starting......: ".date("H:i:s")." [SYS]: *****************************\n";
			return;
		}
		
		if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents($this->sourcefile));
				echo "Starting......: ".date("H:i:s")." [SYS]: *****************************\n";
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
                    if(!isset($tt[$i])){continue;}
					if(trim($tt[$i])==null){continue;}
					echo "Starting......: ".date("H:i:s")." [SYS]: [line:$lineNumber]: {$tt[$i]}\n";
				}
			
			
			return;
		}
		
		
	}
		
	
	
	function isBungled($ligne){
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$this->OutputLines($ligne);
			if(function_exists("build_progress")){build_progress("{operation_failed} Bungled !!!",95);}
			return true;
		
		}
		
	}
	
	function isFileMissing($ligne){
		if(!preg_match("#strtokFile:\s+(.+?)\s+not found#", $ligne,$re)){return false;}
		$filename=trim($re[1]);
		echo "Starting......: ".date("H:i:s")." [SYS]: Squid missing $filename, create an empty one\n";
		@mkdir(dirname($filename),0755,true);
		@file_put_contents($filename ,"");
		@chown($filename, "squid");
		@chgrp($filename, "squid");
		
		
	}
	
	
	private function revert():bool{
		if(!is_file("/etc/squid3/squid.conf")){return false;}
		if(!is_file("/etc/squid3/listen_ports.conf")){return false;}
		
		if(!is_file("/root/squid-good.tgz")){
			echo "Starting......: ".date("H:i:s")." [SYS]: !!! Missing backup configuration, could not revert\n";
			return false;
		}
		
		
	
		echo "Starting......: ".date("H:i:s")." [SYS]: !!! aborting configuration, keep the old one...\n";
		echo "Starting......: ".date("H:i:s")." [SYS]: Revert to old config...\n";
		shell_exec("/bin/tar -xhf /root/squid-good.tgz -C /etc/squid3/");
        return true;
		
	}
	
	private function backup(){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GlobalSquidConf",@file_get_contents("/etc/squid3/squid.conf"));
        chdir("/etc/squid3");
		shell_exec("cd /etc/squid3");
		echo "Starting......: ".date("H:i:s")." [SYS]: Compressing and backup configuration...\n";
		shell_exec("/bin/tar -czf /root/squid-good.tgz *");
		chdir("/root");
		shell_exec("cd /root");
	}



	private function WhoisMe($what=null):bool{
	    $date=date("Y-m-d H:i:s");
        if(!function_exists("debug_backtrace")){return false;}
            $f=array();
            $f[]="* * * * * * *\n$what\n* * * * * * *\n";
            $trace=@debug_backtrace();
            foreach ($trace as $traceback){
                $method="";
                if(is_array($traceback)){
                    foreach ($traceback as $a=>$b){
                        try {
                            if(is_object($a) OR is_object($b)){continue;}
                            if(is_array($b)){
                                foreach ($b as $d=>$e){
                                    $method = $method . " $a = > $d => $e";
                                }
                                continue;
                            }

                            $method = $method . " $a = > $b";
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }
                $filename=$traceback["file"];
                $function=$traceback["function"];
                $line=$traceback["line"];
                $f[]="$date) Called by $filename $function line  $line - $method";
            }
        @file_put_contents("/etc/squid3/debug_backtrace.log",@implode("\n",$f));
        return true;
	}


	
	
	function squid_parse($nobackup=false){
	    if(isset($GLOBALS["NO_VERIF_ACLS"])){if($GLOBALS["NO_VERIF_ACLS"]){return true;}}
		if($GLOBALS["VERBOSE"]){echo "$this->squidbin -f $this->sourcefile -k parse 2>&1\n";}
		exec("$this->squidbin -f $this->sourcefile -k parse 2>&1",$this->results);
        shell_exec("nohup /usr/sbin/artica-phpfpm-service -proxy-clean-bad-acls >/dev/null 2>/dev/null &");
		$results=$this->results;
		foreach ($results as $index=>$ligne){
			if($this->ignoreline($ligne)){continue;}
			if($this->isfatal($ligne)){return false;}
			if($this->isFileMissing($ligne)){return true;}
		
			if(preg_match("#ERROR: Failed#", $ligne)){
			    $this->WhoisMe($ligne);
				echo "********************************************\n";
				$newindex=$index-10;for($i=$newindex;$i<$index;$i++){echo "{$results[$i]}\n";$tt[]=$results[$i];}
				echo "---> $ligne <----\n";
				echo "********************************************\n";
				if(function_exists("build_progress")){build_progress("{operation_failed} Bungled {building_support_package}",96);}
				$this->MakeSupportPackage($this->results);
				if(function_exists("squid_admin_mysql")){squid_admin_mysql(0, "Configuration failed, reverts to backuped config", @implode("\n", $tt)."\n$ligne",__FILE__,__LINE__);}
				$this->revert();
				return false;
			}
		
			if($this->isBungled($ligne)){
			    $this->WhoisMe($ligne);
				echo "********************************************\n";
				$newindex=$index-10;for($i=$newindex;$i<$index;$i++){
					if(isset($results[$i])){
						echo "{$results[$i]}\n";
						$tt[]=$results[$i];
					}
				}
				echo "---> $ligne <----\n";
				echo "********************************************\n";
				if(function_exists("build_progress")){build_progress("{operation_failed} Bungled {building_support_package}",96);}
				$this->MakeSupportPackage($this->results);
				if(function_exists("squid_admin_mysql")){squid_admin_mysql(0, "Configuration failed, reverts to backuped config", @implode("\n", $tt)."\n$ligne",__FILE__,__LINE__);}
                if(!$nobackup){	$this->revert();}
				return false;
				
			}
										
		}
		if(!$nobackup){	$this->backup();}
		return true;
	
	}
	
	private function MakeSupportPackage(){
		$results=$this->results;
		$unix=new unix();
		$rm=$unix->find_program("rm");
		$cp=$unix->find_program("cp");
		$tar=$unix->find_program("tar");
		if(is_file(PROGRESS_DIR."/squid-config-failed.tar.gz")){@unlink(PROGRESS_DIR."/squid-config-failed.tar.gz");}
		if(is_dir("/root/squid-config-failed")){shell_exec("$rm -rf /root/squid-config-failed");}
		@mkdir("/root/squid-config-failed",0755,true);
		if(function_exists("build_progress")){build_progress("{operation_failed} Bungled {building_support_package}",97);}
		@file_put_contents("/root/squid-config-failed/reconfigure.log", @implode("\n", $results));
		shell_exec("$cp -rfv /etc/squid3/* /root/squid-config-failed/");
		@copy($this->sourcefile, "/root/squid-config-failed/squid.conf");
		chdir("/root/squid-config-failed");
		system("cd /root/squid-config-failed");
		if(function_exists("build_progress")){build_progress("{operation_failed} Bungled {building_support_package}",98);}
		system("$tar czf /usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz *");
		chdir("/root");
		system("cd /root");
		if(is_dir("/root/squid-config-failed")){shell_exec("$rm -rf /root/squid-config-failed");}
		
		
	}
	
	
	
	private function find_program($strProgram){
		if($strProgram=="rm"){
			if(!is_file("/bin/rm")){
				$Arch=$this->LINUX_ARCHITECTURE();
				if($Arch==64){
					@copy("/usr/share/artica-postfix/bin/rm","/bin/rm");
					$chattr=$this->find_program("chattr");
					shell_exec("$chattr -i /bin/rm");
				}
			}
		}
	
		global $addpaths;
		$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
				'/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
		if (function_exists("is_executable")) {
			foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
		} else {
			return strpos($strProgram, '.exe');
		}
	}	
	
	
}