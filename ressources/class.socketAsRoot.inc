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
		
		
// *************************************************************************************************************************

		
		
		
		
		
// *************************************************************************************************************************		
// *************************************************************************************************************************		
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
		if($uri=="services.php?GetMyHostId=yes"){
			$this->Runned=true;
			return $unix->GetMyHostId();
		}	
// *************************************************************************************************************************
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