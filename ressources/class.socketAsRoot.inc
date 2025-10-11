<?php

class SocketsAsRoot{
	public $Runned=false;
	private $uri=null;
    private $EnableIntelCeleron=0;
	private $SquidPerformance=0;
	
	
	public function __construct($uri){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$this->uri=$uri;
		$this->SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
		$this->EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
		
		
	}
	
	public function SocketsAsRoot_perform():string{
		$uri=$this->uri;
		$this->Runned=false;
        $array=array();
		$unix=new unix();
// *************************************************************************************************************************		
		
		if($uri=="cmd.php?ldap-restart=yes"){
			$this->Runned=true;
			if($this->SquidPerformance>2){return "";}
			if($this->EnableIntelCeleron==1){return "";}
			
			$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
			if($EnableOpenLDAP==1){
				shell_exec("/etc/init.d/slapd restart --framework=". basename(__FILE__)." &");
			}
			$this->Runned=true;return true;
		}
		
// *************************************************************************************************************************
		if($uri=="system.php?debian_version=yes"){
			if(!is_file("/etc/debian_version")){return false;}
			$ver=trim(@file_get_contents("/etc/debian_version"));
			preg_match("#^([0-9]+)\.#",$ver,$re);
			if(preg_match("#squeeze\/sid#",$ver)){return 6;}
			$Major=$re[1];
			if(!is_numeric($Major)){return false;}
			$this->Runned=true;
			return $Major;
			
		}
		
		
		
		if($uri=="squid.php?IsIcapClient=yes"){
			if($unix->SQUID_ICAP_ENABLED()){ return "TRUE"; }
			$this->Runned=true;
			return true;
		}
		
		
		if($uri=="postfix.php?islocked=yes"){
			$this->Runned=true;
			if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){ return base64_encode("TRUE"); }
			return true;
		}
// *************************************************************************************************************************		
		if($uri=="cmd.php?status-forced=yes"){
			return true;
		}
// *************************************************************************************************************************		
		if($uri=="squid.php?isInjectrunning=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			exec("$pgrep -l -f \"exec.squid.blacklists.php --v2\" 2>&1",$results);
			foreach ($results as $num=>$ligne){	
				if(preg_match("#pgrep#", $ligne)){continue;}
				if(preg_match("#^([0-9]+).*?blacklists\.php#", $ligne,$re)){
					return strval($unix->PROCCESS_TIME_MIN($re[1]));
				}
			}
			
			return true;
		}
// *************************************************************************************************************************		
		if($uri=="services.php?ARTICA-MAKE=yes"){

			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			exec("$pgrep -l -f \"bin/artica-make\"",$results);
			foreach ($results as $line){
				if(preg_match("#pgrep#", $line)){continue;}
				if(preg_match("#^([0-9]+)\s+sh\s+#", $line,$re)){continue;}
				if(preg_match("#^([0-9]+)\s+.+?artica-make\s+([A-Z\_0-9]+)#", $line,$re)){
					$pid=$re[1];
					$time=$unix->PROCESS_TTL_TEXT($pid);
					$SOFT=$re[2];
					$array[$SOFT]=$time;
				}
			}
			return base64_encode(serialize($array));
		}
//*************************************************************************************************************************		
		if($uri=="squid.php?schedule-import-exec=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			exec("$pgrep -l -f \"exec.squid.blacklists.php --inject\" 2>&1",$results);
			foreach ($results as $num=>$ligne){
				if(!preg_match("#^([0-9]+).+?blacklists\.php#", $ligne,$re)){continue;}
				if(preg_match("#pgrep -l#", $ligne)){continue;}
				$time=$unix->PROCCESS_TIME_MIN($re[1]);
				$array["RUNNING"]=true;
				$array["TIME"]=$time;
				return base64_encode(serialize($array));
			}			
			
		}
// *************************************************************************************************************************		
		if($uri=="status.php?cpu-check-nx=yes"){
			$this->Runned=true;
			$check=$unix->find_program("check-bios-nx");
			if(strlen($check)<5){return "";}
			exec("$check --verbose 2>&1",$results);
			return base64_encode(@implode("\n",$results));
		}
// *************************************************************************************************************************		
		if($uri=="services.php?is-dpkg-running=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			$master_pid=0;
			$cmdline="$pgrep -l -f \"/dpkg\" 2>&1";
			exec("$cmdline",$results);
			foreach ($results as $num=>$line){
				if(preg_match("#pgrep#", $line)){continue;}
				if(preg_match("#^([0-9]+)\s+#", $line,$re)){
					$array[$re[1]]=true;
				}
			}
			return base64_encode(serialize($array));
		}
				
// *************************************************************************************************************************		
		if($uri=="cmd.php?MalwarePatrolDatabasesCount=yes"){
			$datas=explode("\n",@file_get_contents("/etc/squid3/malwares.acl"));
			$count=0;
			foreach ( $datas as $num=>$line ){ if(trim($line)==null){continue;} if(substr($line,0,1)=="#"){continue;} $count=$count+1; }
			$this->Runned=true;
			return $count;
		}
// *************************************************************************************************************************
		if($uri=="cmd.php?postfixQueues=yes"){
			include_once("/usr/share/artica-postfix/framework/class.postfix.inc");
			$p=new postfix_system();$datas=serialize($p->getQueuesNumber());
			$this->Runned=true;
			return serialize($p->getQueuesNumber());
		}
// *************************************************************************************************************************
		if($uri=="services.php?SessionPathInMemoryInfos=yes"){
			$array=$unix->SessionPathInMemoryInfos();
			$this->Runned=true;
			return base64_encode(serialize($array));
		}
// *************************************************************************************************************************
		if($uri=="services.php?CPU-NUMBER=yes"){
			$CPUNUM=$unix->CPU_NUMBER();
			$this->Runned=true;
			return $CPUNUM;
		}
// *************************************************************************************************************************		
		if($uri=="cmd.php?system-debian-kernel=yes"){
			exec("/usr/bin/php /usr/share/artica-postfix/exec.apt-cache.kernel.php --detect");
			$this->Runned=true;
			return "";
		}	
// *************************************************************************************************************************
		if($uri=="cmd.php?squid-ini-status=yes"){
			exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --all-squid --nowachdog 2>&1",$results);
			$this->Runned=true;
			return base64_encode(@implode("\n", $results));
		}		
// *************************************************************************************************************************				
		if($uri=="services.php?GetMyHostId=yes"){
			$this->Runned=true;
			return $unix->GetMyHostId();
		}	
// *************************************************************************************************************************
		if($uri=="squid.php?full-version=yes"){
			$squidbin=$unix->find_program("squid");
			if($squidbin==null){$squidbin=$unix->find_program("squid3");}
			exec("$squidbin -v 2>&1",$results);
			foreach ($results as $num=>$val){
				if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
					$this->Runned=true;
					return trim($re[1]);}
			}
			$this->Runned=true;
			return "";
				
		}

		if($uri=="system.php?restart-ldap=yes"){
			shell_exec("/etc/init.d/artica-postfix restart ldap >/dev/null 2>&1");
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		
		if($uri=="cmd.php?ifconfig-all-ips=yes"){
			if(!isset($GLOBALS["ifconfig_all_ips"])){
			$GLOBALS["ifconfig_all_ips"]=base64_encode(serialize($unix->ifconfig_all_ips()));
			}
			$this->Runned=true;
			return $GLOBALS["ifconfig_all_ips"];
		}
		
		return "";
	}
	
}