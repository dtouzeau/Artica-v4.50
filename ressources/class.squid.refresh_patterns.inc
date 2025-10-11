<?php

class squid_refresh_pattern{
	private $SquidUrgency=0;
	private $SquidCacheLevel=0;
	private $DisableAnyCache=0;
	private $reload_into_ims=1;
	private $SquidCachesProxyEnabled=0;
	private $SquidUrgencyCaches=0;
	private $CORP_LICENSE=false;
	
	public function __construct(){
		$this->SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
		$this->DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
		$this->SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
		$this->reload_into_ims=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReloadIntoIMS"));
		$this->SquidUrgencyCaches=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgencyCaches"));
		$MainarticakEy=base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0FydGljYUNvcnBMaWNlbnNl=");
		if(is_file($MainarticakEy)){if(intval(@file_get_contents($MainarticakEy))==1){$this->CORP_LICENSE=true;}}
		if($this->SquidCachesProxyEnabled==0){$this->DisableAnyCache=1;}
	}
	
	
	public function build(){
		$this->refresh_pattern_domains();
		$this->refresh_pattern_list();
		@chown("/etc/squid3/refresh_pattern_domains.conf","squid");
		@chown("/etc/squid3/refresh_pattern_artica.conf","squid");
	}
	
	private function refresh_pattern_domains(){
		if($this->SquidCacheLevel==0){$this->DisableAnyCache=1;}
		$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
		if($SquidDisableCaching==1){
			$conf[]="# SquidDisableCaching =1";
			@file_put_contents("/etc/squid3/refresh_pattern_domains.conf", @implode("\n", $conf));
			return;
				
		}
		if($this->SquidUrgency==1){
			$conf[]="# SquidUrgency =1";
			@file_put_contents("/etc/squid3/refresh_pattern_domains.conf", @implode("\n", $conf));
			return;
			
		}
		if($this->SquidUrgencyCaches==1){
			$conf[]="# SquidUrgencyCaches =1";
			@file_put_contents("/etc/squid3/refresh_pattern_domains.conf", @implode("\n", $conf));
			return;
		}
		
		if($this->DisableAnyCache==1){
			$conf[]="# DisableAnyCache =1";
			@file_put_contents("/etc/squid3/refresh_pattern_domains.conf", @implode("\n", $conf));
			return;
		}
		
		$q=new mysql();
		$sql="SELECT * FROM `squid_speed` WHERE `domain` IS NOT NULL";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
	
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$pattern=trim($ligne["domain"]);
			if($pattern=='.'){continue;}
			if(strpos("  $pattern  ", " -i ")==0){$pattern="-i $pattern";}
			if(isset($already[".$pattern"])){continue;}
	
			if(!$this->is_regex($pattern)){
				$pattern=str_replace(".","\.",$pattern);
				$pattern=str_replace("*","?",$pattern);
			}
			$conf[]="refresh_pattern $pattern  {$ligne["refresh_pattern_min"]}    {$ligne["refresh_pattern_perc"]}%     {$ligne["refresh_pattern_max"]}  {$ligne["refresh_pattern_options"]}";
	
		}
	
		@file_put_contents("/etc/squid3/refresh_pattern_domains.conf", @implode("\n", $conf)."\n");
	
	
	}
	
	private function is_regex($pattern){
		$f[]="{";
		$f[]="[";
		$f[]="+";
		$f[]="\\";
		$f[]="?";
		$f[]="$";
		$f[]=".*";

		foreach ($f as $key=>$val){
			if(strpos(" $pattern", $val)>0){return true;}
		}
	}
	
	private function compilation_params(){
		if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}
		exec("$squidbin -v 2>&1",$results);
		$text=@implode("\n", $results);
		if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
		if(preg_match_all("#'(.+?)'#is", $text, $re)){
			foreach ($re[1] as $index=>$line){
				if(preg_match("#(.+?)=(.*)#", $line,$ri)){
					$key=$ri[1];
					$value=$ri[2];
					$key=str_replace("--", "", $key);
					if(trim($value)==null){$value=true;}
					$GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
					continue;
				}
				$key=$line;
				$value=1;
				$key=str_replace("--", "", $key);
				if($GLOBALS["VERBOSE"]){echo "squid -v [$key] = `$value`\n";}
				$GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
			}
				
		}
		return $GLOBALS["COMPILE_SQUID_TOKENS"];
	}
	
	private function refresh_pattern_tokens(){
		if(isset($GLOBALS["refresh_pattern_tokens_values"])){return $GLOBALS["refresh_pattern_tokens_values"];}
		unset($GLOBALS["refresh_pattern_tokens"]);
		if(!is_numeric($this->SquidCacheLevel)){$this->SquidCacheLevel=3;}
		$fa["override-expire"]=true;
		$fa["override-lastmod"]=true;
		$fa["reload-into-ims"]=true;
		$fa["ignore-reload"]=true;
		$fa["ignore-no-store"]=true;
		$fa["refresh-ims"]=true;
		$fa["store-stale"]=true;
		
		$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
		$refresh_pattern_def_opts=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_opts")));
		$refresh_pattern_def_opts2=array();
		
		if(!is_file('/etc/artica-postfix/settings/Daemons/refresh_pattern_def_opts')){
		    foreach ($fa as $key=>$val){

				$refresh_pattern_def_opts[$key]=true;
			}
				
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("refresh_pattern_def_opts", base64_encode(serialize($refresh_pattern_def_opts)));
		}
		
		
		$GLOBALS["refresh_pattern_tokens"][]="# refresh_pattern_def_opts=".count($refresh_pattern_def_opts);
		if(count($refresh_pattern_def_opts)>0){
			if($this->reload_into_ims==0){unset($refresh_pattern_def_opts["reload-into-ims"]);}
			$DEFCMD=array();
			if(is_array($refresh_pattern_def_opts)){
			    foreach ($refresh_pattern_def_opts as $key=>$val){
					if($key==null){continue;}
					$refresh_pattern_def_opts2[$key]=$val;
		
				}
			}
		}
		
		
		
			if($this->reload_into_ims==1){ $fa["reload-into-ims"]=true; }
			$refresh_pattern_def_opts2=array();
			$DEFCMD=array();
			$fa=array();
			if($SquidCacheLevel==4){
				$fa["override-expire"]=true;
				$fa["override-lastmod"]=true;
				$fa["ignore-reload"]=true;
				$fa["ignore-no-store"]=true;
				$fa["refresh-ims"]=true;
				$fa["store-stale"]=true;
			}
			if($SquidCacheLevel==3){
				$fa["override-expire"]=true;
				$fa["override-lastmod"]=true;
				$fa["ignore-reload"]=true;
				$fa["refresh-ims"]=true;
			}
			if($SquidCacheLevel==2){
				$fa["override-expire"]=true;
				$fa["override-lastmod"]=true;
			}
            foreach ($fa as $key=>$val){$DEFCMD[]=$key;}
		
		
		
		$GLOBALS["refresh_pattern_tokens"][]="# refresh_pattern_def_opts2=".count($refresh_pattern_def_opts2);
		
		if(count($refresh_pattern_def_opts2)>0){
			if($this->reload_into_ims==0){unset($refresh_pattern_def_opts["reload-into-ims"]);}
			$DEFCMD=array();
			reset($refresh_pattern_def_opts);
			foreach ($refresh_pattern_def_opts as $key=>$val){
				$DEFCMD[]=$key;
					
			}
		}
		$GLOBALS["refresh_pattern_tokens_values"]=@implode(" ", $DEFCMD);
		return $GLOBALS["refresh_pattern_tokens_values"];
		
	}
	
	public function refresh_pattern_list(){
		$conf[]="minimum_expiry_time 0";
		$conf[]="collapsed_forwarding off";
		if($this->SquidUrgency==1){
			$conf[]="# SquidUrgency =1";
			$conf[]="refresh_pattern -i . 0 100% 0";
			@file_put_contents("/etc/squid3/refresh_pattern_artica.conf", @implode("\n", $conf)."\n");
			return;
				
		}
		if($this->SquidUrgencyCaches==1){
			$conf[]="# SquidUrgencyCaches =1";
			@file_put_contents("/etc/squid3/refresh_pattern_artica.conf", @implode("\n", $conf));
			return;
		}

		$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
		if($SquidDisableCaching==1){
			$conf[]="# SquidDisableCaching =1";
			@file_put_contents("/etc/squid3/refresh_pattern_artica.conf", @implode("\n", $conf));
			return;
		
		}
	
		$sock=new sockets();
		$users=new usersMenus();
		if(!is_numeric($this->reload_into_ims)){$this->reload_into_ims=1;}
		if(!is_numeric($this->SquidCacheLevel)){$this->SquidCacheLevel=3;}
		
	
		if($this->SquidCacheLevel==0){
			$this->DisableAnyCache=1;
			$conf[]="reload_into_ims off";
			$conf[]="refresh_pattern -i . 0 100% 0";
			@file_put_contents("/etc/squid3/refresh_pattern_artica.conf", @implode("\n", $conf)."\n");
			return;
		}
	
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if($this->DisableAnyCache==1){return;}
	
	
	
		$refresh_pattern_def_min=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_min"));
		$refresh_pattern_def_max=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_max"));
		$refresh_pattern_def_perc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_perc"));
		if($refresh_pattern_def_min==0){$refresh_pattern_def_min=1;}
		if($refresh_pattern_def_max==0){$refresh_pattern_def_max=259200;}
		if($refresh_pattern_def_perc==0){$refresh_pattern_def_perc=100;}
	
		if($this->SquidCacheLevel>2){
			$compilation_params=$this->compilation_params();
			if(isset($compilation_params["enable-http-violations"])){
				if($this->reload_into_ims==1){
					$conf[]="reload_into_ims on";
				}else{
					$conf[]="reload_into_ims off";
				}
			}
		}
	
		$conf[]=$this->refresh_pattern_defaults(true);
		$refresh_pattern_tokens=$this->refresh_pattern_tokens();
		$conf[]="refresh_pattern . $refresh_pattern_def_min {$refresh_pattern_def_perc}% $refresh_pattern_def_max $refresh_pattern_tokens";
		@file_put_contents("/etc/squid3/refresh_pattern_artica.conf", @implode("\n", $conf)."\n");
	
	}

	private function refresh_pattern_defaults($OnlyDefaults=false){
		
		$refresh_pattern_def_min=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_min"));
		$refresh_pattern_def_max=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_max"));
		$refresh_pattern_def_perc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_perc"));
		if($refresh_pattern_def_min==0){$refresh_pattern_def_min=129600;}
		if($refresh_pattern_def_max==0){$refresh_pattern_def_max=259200;}
		if($refresh_pattern_def_perc==0){$refresh_pattern_def_perc=100;}
		
		
		$refresh_pattern_tokens=$this->refresh_pattern_tokens();
		
		
		
		// offline_mode off
		// max-stale
		$f[]=@implode("\n", $GLOBALS["refresh_pattern_tokens"]);
		$f[]="# Tuning....";
		$f[]="range_offset_limit 1024 KB";
		
		if($this->SquidCacheLevel>2){
			$f[]="vary_ignore_expire on";
		}
		
		if($this->SquidCacheLevel==4){
			$f[]="offline_mode on";
			$f[]="max_stale $refresh_pattern_def_max minutes";
		}
		
		$final="refresh_pattern -i . $refresh_pattern_def_min {$refresh_pattern_def_perc}% $refresh_pattern_def_max $refresh_pattern_tokens";
		
	
		if($this->SquidCacheLevel>2){
			$f[]="# --------- Make Windows Updates cache";
			if(!is_file("/etc/artica-postfix/settings/Daemons/AllowWindowsUpdates")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowWindowsUpdates", 1);}
			$refresh_pattern_def_min=64800;
			$refresh_pattern_def_max=129600;
			$refresh_pattern_def_perc=100;
			
			
			$f[]="refresh_pattern -i ^ftp: 0 90% 200000";
			$f[]="refresh_pattern -i ^gopher: 1440 0% 1440";
		}
	
		if($OnlyDefaults){return @implode("\n", $f);}
	
		if($this->SquidCacheLevel<3){
			$f[]="refresh_pattern -i ^ftp: 1440 20% 10080";
			$f[]="refresh_pattern -i ^gopher: 1440 0% 1440";
			$f[]="refresh_pattern -i (/cgi-bin/|\?) 0 0% 0";
		}
	
		$f[]=$final;
		return @implode("\n", $f."\n");
	}
}