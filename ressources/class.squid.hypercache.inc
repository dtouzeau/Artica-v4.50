<?php

class HyperCacheSquid{
	private $root_squid_version_nocache;
	private $SquidUrgency=0;
	private $MGR_LISTEN_PORT=0;
	private $HyperCacheMaxProcesses=0;
	private $FATAL_ERROR=false;
	
	function __construct(){
		$this->root_squid_version_nocache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
		$this->MGR_LISTEN_PORT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort");
		$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
		$this->HyperCacheMaxProcesses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheMaxProcesses"));
		if($this->HyperCacheMaxProcesses==0){$this->HyperCacheMaxProcesses=20;}
	}
	
	
	public function build():bool{
		$content=$this->StoreID();
		if($this->FATAL_ERROR){
			@chown("/etc/squid3/StoreID.conf","squid");
			return false;
		}
		@file_put_contents("/etc/squid3/StoreID.conf",$content);
		echo $content;
		@chown("/etc/squid3/StoreID.conf","squid");
		return true;
	}
    
    private function StoreIDUpdates():bool{
        $md52=null;
        $srcfile="/usr/share/artica-postfix/bin/storeid_file_rewrite";
        $destfile="/lib/squid3/storeid_file_rewrite";
        if(!is_file($srcfile)){return false;}
        $md51=md5_file($srcfile);
        if(is_file($destfile)){$md52=md5_file($destfile);}
        if($md51==$md52){return true;}
        $unix=new unix();
        $squidbin=$unix->LOCATE_SQUID_BIN();
        if(function_exists("_out")){ _out("Updating StoreID engine binary..."); }

        squid_admin_mysql(2,"Updating StoreID engine...",null,__FILE__,__LINE__);
        for($i=1;$i<5;$i++){
            if(function_exists("_out")){ _out("Reloading proxy service for HyperCache update"); }
            shell_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
            if(is_file($destfile)){@unlink($destfile);}
            if(!@copy($srcfile,$destfile)){sleep(1);continue;}
            $md52=md5_file($destfile);
            if($md51<>$md52){continue;}
            @chmod($destfile,0755);
            squid_admin_mysql(2,"Updating StoreID engine success...",null,__FILE__,__LINE__);
            return true;
        }

        return false;

    }

	private function StoreIDFree():string{
		$f[]="# # # # # # STORE ID ". __FUNCTION__." L.".__LINE__." # # # # # #";
		$f[]="# # # # # # STORE ID Proxy v$this->root_squid_version_nocache # # # # # #";

        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		if(!is_file("/etc/artica-postfix/settings/Daemons/AllowWindowsUpdates")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowWindowsUpdates", 1);}
		$AllowWindowsUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowWindowsUpdates"));

        $f[]="# # # # # # STORE AllowWindowsUpdates:$AllowWindowsUpdates # # # # # #";

		include_once(dirname(__FILE__)."/class.storeid.defaults.inc");
		if(function_exists("FillStoreIDDefaults")){
			$sql=FillStoreIDDefaults();
			if($GLOBALS["VERBOSE"]){echo $sql."\n";}
			$q->QUERY_SQL($sql);
		}else{
			if($GLOBALS["VERBOSE"]){echo "FillStoreIDDefaults !!! no such function\n";}
		}

		if(function_exists("FillStoreIDUpdates")){
			$sql=FillStoreIDUpdates();
			if($GLOBALS["VERBOSE"]){echo $sql."\n";}
			$q->QUERY_SQL($sql);
		}else{
			if($GLOBALS["VERBOSE"]){echo "FillStoreIDUpdates !!! no such function\n";}
		}

        $EXPLAINZ=array();
		$COUNT_ROWS=$q->COUNT_ROWS("StoreID");
		
		if(!$q->ok){
			if(function_exists("debug_backtrace")){
				$trace=debug_backtrace();
				foreach ($trace as $line){$file=basename($line["file"]);$function=$line["function"];$line=$line["line"];$EXPLAINZ[]="From $file $function() Line $line";}
			}
			squid_admin_mysql(1, "Configuration failed on HyperCache [action=abort]", $q->mysql_error."\n",@implode("\n", $EXPLAINZ));
			$this->FATAL_ERROR=true;
			return "# SQL Error";
		}

		if($COUNT_ROWS==0){
			@file_put_contents("/etc/squid3/storeid_rewrite", @file_get_contents("/usr/share/artica-postfix/bin/install/squid/storeid_file_rewrite"));
		}else{
			$TR=array();
			$results=$q->QUERY_SQL("SELECT * FROM StoreID WHERE enabled=1 order by zOrder");
			foreach ($results as $index=>$ligne){
				$pattern=trim($ligne["pattern"]);
				$dedup=trim($ligne["dedup"]);
                if($GLOBALS["VERBOSE"]){echo "$index: $pattern -------> $dedup\n";}
				if($pattern==null){continue;}
				if($dedup==null){continue;}
				$TR[]="$pattern\t$dedup";
				
			}
			@file_put_contents("/etc/squid3/storeid_rewrite",@implode("\n", $TR)."\n");
			
		}

		@mkdir("/home/squid/youtubeStoreID",0755,true);
		@chown("/home/squid/youtubeStoreID","squid");
		@chgrp("/home/squid/youtubeStoreID", "squid");

        if(!$this->StoreIDUpdates()){
            return "# Update binary failed\n";
        }

        $store_id_program="/lib/squid3/storeid_file_rewrite";
        @chown($store_id_program,"squid");
        @chgrp($store_id_program, "squid");
		

		
		if(is_file("/var/log/squid/storeid.log")){@unlink("/var/log/squid/storeid.log");}
		@touch("/var/log/squid/storeid.log");
		@chmod("/var/log/squid/storeid.log",0755);
		@chown("/var/log/squid/storeid.log","squid");
		@chgrp("/var/log/squid/storeid.log", "squid");
		

	
		$f[]="refresh_pattern -i \.SQUIDINTERNAL\/.* 446760 100% 525600 ignore-reload ignore-no-store reload-into-ims ignore-private ignore-auth ignore-must-revalidate override-expire override-lastmod";
		$f[]="acl DenyStoreID dstdomain .xiti.com";
		$f[]="acl DenyStoreID dstdomain .estat.com";
		$f[]="acl DenyStoreID dstdomain .googlecode.com";
		$f[]="acl DenyStoreID dstdomain .articatech.net";
		$f[]="acl DenyStoreID dstdomain .php.net";
		$f[]="acl DenyStoreID dstdomain .msftncsi.com";
		
		$f[]="store_id_bypass on";
		$f[]="store_id_program $store_id_program";
		$f[]="store_id_children $this->HyperCacheMaxProcesses startup=5 idle=2 concurrency=1";
		$f[]="store_id_access deny manager";
		$f[]="store_id_access deny DenyStoreID";
		
		$f[]="store_id_access deny internal_icons";
		$f[]="store_id_access deny !GET";
		$f[]="store_id_access deny MgRDest";
		$f[]="store_id_access allow all";

		return @implode("\n",$f)."\n";

	}
	
	
	private function StoreID():string{
		
	
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidCacheLevel")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCacheLevel", 2);
		}
		$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
		$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
		$SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
		$StoreIDUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StoreIDUrgency"));
        $EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
        $HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));

        $f[]="# # # # # # HyperCacheStoreID $HyperCacheStoreID # # # # # #";
        if($HyperCacheStoreID==0){
            $f[]="# # # # # # DISABLED # # # # # #";
            return @implode("\n", $f);
        }

		if($EnableIntelCeleron==1){
			$f[]="# StoreID disabled with Enable Intel Celeron";
			return @implode("\n",$f)."\n";
		}
	
		if($SquidCacheLevel==0){
			$f[]="# SquidCacheLevel == 0 Aborting";
			return @implode("\n",$f)."\n";
		}
		if($SquidDisableCaching==1){
			$f[]="# SquidDisableCaching == 0 Aborting";
			return @implode("\n",$f)."\n";
		}	
		
	
		if($this->SquidUrgency==1){$f[]="# Emergency mode!";
            if(function_exists("_out")){ _out("Cannot set HyperCache - Proxy is in emergency"); }
			return @implode("\n",$f)."\n";
			
		}
		
		if($SquidDisableHyperCacheDedup==1){
			$f[]="# Temporary disabled";
			return @implode("\n", $f);
		}
		
		if($StoreIDUrgency==1){
            if(function_exists("_out")){ _out("Cannot set HyperCache - HyperCache is in emergency"); }
			$f[]="# !!!! StoreID Emergency mode !!!!";
			return @implode("\n",$f)."\n";
		
		}

		$Compatible=false;
		if(preg_match("#^3\.5#", $this->root_squid_version_nocache)){$Compatible=true;}
		if(preg_match("#^4\.#", $this->root_squid_version_nocache)){$Compatible=true;}
        if(preg_match("#^5\.#", $this->root_squid_version_nocache)){$Compatible=true;}
        if(preg_match("#^6\.#", $this->root_squid_version_nocache)){$Compatible=true;}
        if(preg_match("#^7\.#", $this->root_squid_version_nocache)){$Compatible=true;}
        if(preg_match("#^8\.#", $this->root_squid_version_nocache)){$Compatible=true;}
		if(!$Compatible){
			$f[]="# Not a 3.5/4.x/5.x/6.x/7.x/8.x version";
			return @implode("\n",$f)."\n";
				
		}
	
		$f[]=$this->StoreIDFree();
		return @implode("\n",$f)."\n";
	
	
	}
	
	
	
}
