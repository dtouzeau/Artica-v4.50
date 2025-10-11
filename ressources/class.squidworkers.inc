<?php


class squidworkers{
	public $ASROOT=false;
	private $SquidBoosterOnly=0;
	private $CPU_NUMBER=0;
    public $SQUID_VERSION="";
	private $IS_32=false;
	private $store_dir_minsize=null;
	private $uuid=null;
	private $EnableRemoteStatisticsAppliance=0;
	private $SquidBoosterMemK;
	private $DisableAnyCache=0;
	private $SquidCachesProxyEnabled=0;
	private $SquidBoosterMem=0;
	private $SquidForceCacheTypes=null;
	
	private $SquidCachesMount=array();
	public $CPU_FINAL=0;
	
	function __construct(){
        if(function_exists("posix_getuid")) { if (posix_getuid() == 0) { $this->ASROOT = true; } }else{ $this->ASROOT = true; }
		$users=new usersMenus();
		$sock=new sockets();
		
		$this->CPU_NUMBER=$users->CPU_NUMBER;
		if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
		if(preg_match("#^3\.2\.#", $this->SQUID_VERSION)){$this->IS_32=true;}
		if(preg_match("#^3\.3\.#", $this->SQUID_VERSION)){$this->IS_32=true;}
		$this->uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
		$this->SquidCachesMount=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesMount")));
		$this->SquidForceCacheTypes=$sock->GET_INFO("SquidForceCacheTypes");
		if($this->SquidForceCacheTypes==null){$this->SquidForceCacheTypes="aufs";}
		$this->FillSettings();
		$this->store_dir_minsize_calc();
	}
	private function FillSettings(){
		$sock=new sockets();
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		$this->SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$this->SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		$this->DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
		$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
		$this->SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
		if($this->SquidCachesProxyEnabled==0){$this->DisableAnyCache=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			include_once(dirname(__FILE__)."/class.blackboxes.inc");
			$sock=new blackboxes($this->uuid);
			$this->SquidBoosterMem=$sock->GET_SQUID_INFO("SquidBoosterMem");
			$this->SquidBoosterMemK=$sock->GET_SQUID_INFO("SquidBoosterMemK");
			$this->DisableAnyCache=$sock->GET_SQUID_INFO("DisableAnyCache");	

		}	
		
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if(!is_numeric($this->SquidBoosterMemK)){$this->SquidBoosterMemK=50;}
		if($SquidCacheLevel==0){$this->DisableAnyCache=1;}
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
	}
	
	
	private function store_dir_minsize_calc(){
		if($this->DisableAnyCache==1){$this->SquidBoosterMem=0;}
		
		if($this->SquidBoosterMem>0){
			$this->SquidBoosterMemK=$this->SquidBoosterMemK*1024;
			$this->store_dir_minsize="min-size=$this->SquidBoosterMemK";
		}else{
			$this->store_dir_minsize="min-size=0";
		}		
	}
	
	private function WorkersDefault(){}	

	public function zcheck_squidbooster_value(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		
	
		if($GLOBALS["VERBOSE"]){
			echo "Starting......: ".date("H:i:s")." [SMP] Use only \$SquidBoosterOnly:$this->SquidBoosterOnly\n";
		}
		if(!is_numeric($this->SquidBoosterOnly)){$this->SquidBoosterOnly=0;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if($this->SquidBoosterMem==0){$this->SquidBoosterOnly=0;}
		$GLOBALS[__FUNCTION__]=$this->SquidBoosterOnly;
		return $this->SquidBoosterOnly;
	}

	private function root_squid_version():string{
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}
		if(!is_file($squidbin)){return "";}
		exec("$squidbin -v 2>&1",$results);
		foreach ($results as $num=>$val){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
	
		return "";
		
		
	}
	
	private function CountDeCaches($cpuindex,$uuid){
		$count=0;
		
		$CacheMount=$this->BuildSquidCachesMount($cpuindex);
		echo "Starting......: ".date("H:i:s")." [SMP] CPU[$cpuindex] : Memory Cache = `$CacheMount`\n";
		if($CacheMount<>null){$count++;}
		
		if($this->DisableAnyCache==0){
			$q=new mysql();
			if($this->SquidBoosterOnly==0){
				$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 
					AND uuid='$uuid' AND cpunum=$cpuindex AND ToDelete=0 AND Building=2","artica_backup");
				if(mysqli_num_rows($results)>0){$count++;}
			}
			
		}
		
		return $count;	
	}

	
	private function caches_mysql(){
		$unix=new unix();
		$uuid=$unix->GetUniqueID();
		
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 AND uuid='$uuid' AND ToDelete=0 AND Building=2","artica_backup");
		$CPU_NUMBER=$this->CPU_NUMBER();
		echo "Starting......: ".date("H:i:s")." [SMP] CPU(s) = $CPU_NUMBER\n";
		
		$f[]="workers $CPU_NUMBER";
		$boost=new squidbooster();
		$WORKCOUNT=0;
		
		for($i=1;$i<$CPU_NUMBER+1;$i++){
			$CountDeCaches=$this->CountDeCaches($i,$uuid);
			echo "Starting......: ".date("H:i:s")." [SMP] CPU[$i] = CPU[$i] Caches = `$CountDeCaches`\n";
			if($CountDeCaches==0){continue;}
			$f[]="if \${process_number} = $i";
			$this->CPU_FINAL++;
			
			$CacheMount=$this->BuildSquidCachesMount($i);
			echo "Starting......: ".date("H:i:s")." [SMP] CPU[$i] = Cache mounted `$CacheMount`\n";
			if($CacheMount<>null){
				$WORKCOUNT++;
				$f[]=$CacheMount;
			}
			
			if($this->DisableAnyCache==0){
				
				$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 AND uuid='$uuid' AND cpunum=$i AND ToDelete=0 AND Building=2","artica_backup");
				if(!$q->ok){echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i `$q->mysql_error`\n";continue;}
			
				if(mysqli_num_rows($results)==0){
					echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i No cache saved, this CPU run wihout caching\n";
				}else{
					echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i `". mysqli_num_rows($results)." caches`\n";
				}
				if(mysqli_num_rows($results)>0){
					while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
						$cache_maxsizeT=null;
						$cache_directory=$ligne["cache_directory"];
						$cache_directory_calc=$ligne["cache_directory"];
						if(is_link($cache_directory_calc)){$cache_directory_calc=readlink($cache_directory_calc);}
						if(preg_match("#^\/var\/#", $cache_directory)){$cache_directory=str_replace("/var/", "/home/squid/", $cache_directory);}
						if(preg_match("#^\/var\/#", $cache_directory_calc)){$cache_directory_calc=str_replace("/var/", "/home/squid/", $cache_directory_calc);}
						
						if(isset($sizeAvailble[$cache_directory_calc])){
							$sizeAvailble[$cache_directory_calc]=$unix->DIRECTORY_FREEM($cache_directory_calc);
						}
						
						
						echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i cache dir:`$cache_directory`\n";
						if(!$this->ifValidCache($cache_directory)){echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i invalid cache path `$cache_directory`\n";continue;}
						$cache_directory=$ligne["cache_directory"];
						$cache_size=$ligne["size"]*1000;
						
						if($cache_size>$sizeAvailble[$cache_directory_calc]){
							echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i cache dir:`$cache_directory` no space left {$sizeAvailble[$cache_directory_calc]}\n";
							continue;
						}
						$sizeAvailble[$cache_directory_calc]=$sizeAvailble[$cache_directory_calc]-$cache_size;
						
						$cache_dir_level1=$ligne["cache_dir_level1"];
						$cache_dir_level2=$ligne["cache_dir_level2"];
						$cache_maxsize=$ligne["cache_maxsize"];
						$cache_type=$ligne["cache_type"];	

					
					
						if($cache_maxsize>0){
							$cache_maxsize=$cache_maxsize*1024;
							$cache_maxsizeT=" max-size=$cache_maxsize";
						}
					
						if(!is_numeric($cache_dir_level1)){$cache_dir_level1=16;}
						if(!is_numeric($cache_dir_level2)){$cache_dir_level2=256;}
						
						if($cache_type=="ufs"){$cache_type="aufs";}
						$f[]="cache_dir $cache_type $cache_directory $cache_size $cache_dir_level1 $cache_dir_level2 $cache_maxsizeT";
						$WORKCOUNT++;
					}
				}
			}
			
			
			
			$f[]="endif";
		
		}
		$f[]="#------------------";	
		if($WORKCOUNT>0){	
			return @implode("\n", $f);
		}
		
		
	}
	
	public function ifValidCache($directory){
		$unix=new unix();
		$directory=trim($directory);
		if($directory==null){return false;}
		if(preg_match("#^\/dev\/#", $directory)){return false;}
		$subdir=basename($directory);
		$MainDir=dirname($directory);
		$mountPoint=$unix->isDirInFsTab($MainDir);
		if($mountPoint==null){return true;}
		if(!$unix->alt_stat2($mountPoint)){return false;}
		return true;
		
	}
	
	private function BuildSquidCachesMount($Index){
		if(isset($GLOBALS["BuildSquidCachesMount"][$Index])){return $GLOBALS["BuildSquidCachesMount"][$Index];}
		
		
		if(!isset($this->SquidCachesMount[$Index])){
			return null;
		}
		$mountedram=intval($this->SquidCachesMount[$Index]);

		if(!is_numeric($mountedram)){return null;}
		if($mountedram<0){$mountedram=0;}
		
		if($mountedram==0){
			if($this->BuildSquidCachesMountIsMounted($Index)>0){

				$this->BuildSquidCachesMountUmount($Index);
			}
			return null;
		}
		
		$Dir="/home/squid/cache/MemBooster$Index";

		
		$AlreadyMounted=$this->BuildSquidCachesMountIsMounted($Index);
		if($AlreadyMounted==0){$this->BuildSquidCachesMountInRam($mountedram,$Index);}
		$Diff=intval($mountedram-$AlreadyMounted);
		if(preg_match("#^-([0-9]+)#", $Diff,$re)){$Diff=intval($re[1]);}
		if($Diff>5){
			$this->BuildSquidCachesMountUmount($Index);
			$this->BuildSquidCachesMountInRam($mountedram,$Index);
		}
		
		
		$MemDIFF=$mountedram-10;

		$GLOBALS["BuildSquidCachesMount"][$Index]="cache_dir $this->SquidForceCacheTypes $Dir $MemDIFF 16 256";
		return "cache_dir aufs $Dir $MemDIFF 16 256";		
		
	}
	
	private function BuildSquidCachesMountIsMounted($Index){
		$f=file("/proc/mounts");
        foreach ($f as $val){
				if(preg_match("#^tmpfs.+?\/home\/squid\/cache\/MemBooster$Index\s+.+?size=([0-9]+)([a-zA-Z]+)#", $val,$re)){
					$size=$re[1];
					$unit=strtolower($re[2]);
					if($unit=="k"){return round($size/1024);}
					if($unit=="m"){return $size;}
		
				}
			}
			return 0;
		}
		
		private function BuildSquidCachesMountInRam($RAM,$Index){
			$unix=new unix();
			$mount=$unix->find_program("mount");
			$Dir="/home/squid/cache/MemBooster$Index";
			
			@mkdir($Dir,0755,true);
			@chown($Dir, "squid");
			@chgrp($Dir, "squid");
			shell_exec("$mount -t tmpfs -o size={$RAM}M tmpfs $Dir >/dev/null 2>&1");
		
		}		
	
		private function  BuildSquidCachesMountUmount($Index){
			$unix=new unix();
			$umount=$unix->find_program("umount");
			$rm=$unix->find_program("rm");
			$Dir="/home/squid/cache/MemBooster$Index";
			shell_exec("$umount -l $Dir");
			shell_exec("$rm -rf  -l $Dir");
			
		}
		
	
	private function CPU_NUMBER(){
		$unix=new unix();
		$cat=$unix->find_program("cat");
		$grep=$unix->find_program("grep");
		$cut=$unix->find_program("cut");
		$wc=$unix->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$CPUNUM=exec($cmd);
		return $CPUNUM;
	}	
	
	
}