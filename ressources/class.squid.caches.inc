<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.squid.inc');

class SquidCacheCenter{

    private $table="squid_caches_center";
    private $database="artica_backup";
    private $CORP_LICENSE=false;
    private $SquidBoosterEnable=0;
    private $DisableAnyCache=0;
    private $SquidCacheLevel=3;
    private $SquidSimpleConfig;
    public $CURRENT_PROGRESS=0;
    public $MAX_PROGRESS=0;
    public $ASROOT=false;
    public $CACHES_ARRAY=array();
    private $global_conf_array=array();
    public $IsCacheRock=false;
    private $SquidUrgencyCaches=0;
    private $SquidUrgency=0;
    private $SquidCachesProxyEnabled=0;

    function __construct(){


        $users=new usersMenus();
        $this->CORP_LICENSE=$users->CORP_LICENSE;
        $this->SquidBoosterEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBoosterEnable"));
        $this->DisableAnyCache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache");
        $this->SquidCacheLevel=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel");
        $this->SquidSimpleConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSimpleConfig"));
        $this->SquidUrgencyCaches=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgencyCaches"));
        if(!is_numeric($this->SquidCacheLevel)){$this->SquidCacheLevel=3;}
        if($this->SquidCacheLevel==0){$this->DisableAnyCache=1;$this->SquidBoosterEnable=0;}
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->CACHES_ARRAY=unserialize(@file_get_contents(PROGRESS_DIR."/squid_caches_center.db"));
        $this->global_conf_array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGlobalConfArray")));
        if($this->SquidUrgency==1){$this->SquidUrgencyCaches=1;}
        $this->SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
        if($this->SquidCachesProxyEnabled==0){$this->DisableAnyCache=1;}

    }
    private function root_squid_version_nocache(){
        if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        $unix=new unix();

        $squidbin=$unix->LOCATE_SQUID_BIN();
        if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
        if(!is_file($squidbin)){return;}
        exec("$squidbin -v 2>&1",$results);


        foreach ($results as $num=>$val){
            if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
                if($re[1]=="2.7."){$re[1]="2.7.0";}
                if($re[1]=="3.0."){$re[1]="3.0.0";}
                if($re[1]=="3.1."){$re[1]="3.1.0";}
                if($re[1]=="3.2."){$re[1]="3.2.0";}
                if($re[1]=="3.3."){$re[1]="3.3.0";}
                if($re[1]=="3.4."){$re[1]="3.4.0";}
                if($re[1]=="3.5."){$re[1]="3.5.0";}

                $GLOBALS[__FUNCTION__]=$re[1];
                return $re[1];
            }
        }

    }



    private function cache_tweaks(){
        $SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
        $SquidCacheSwapHigh=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheSwapHigh"));
        $SquidCacheSwapLow=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheSwapLow"));
        $SquidMiniMumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMiniMumObjectSize"));
        $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
        $maximum_object_size_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maximum_object_size_in_memory"));
        if($maximum_object_size_in_memory==0){$maximum_object_size_in_memory=512;}

        if($SquidCacheSwapLow==0){$SquidCacheSwapLow=95;}
        if($SquidCacheSwapHigh==0){$SquidCacheSwapHigh=97;}
        if($max_filedesc==0){$max_filedesc=16384;}

        $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));

        $CacheReplacementPolicyZ["lru"]="lru";
        $CacheReplacementPolicyZ["heap_GDSF"]="heap GDSF";
        $CacheReplacementPolicyZ["heap_LFUDA"]="heap LFUDA";
        $CacheReplacementPolicyZ["heap_LRU"]="heap LRU";

        $CacheReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheReplacementPolicy"));
        $SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));
        if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
        if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="heap_LFUDA";}

        if(is_numeric($SquidMemoryReplacementPolicy)){$SquidMemoryReplacementPolicy="heap_LFUDA";}
        if(is_numeric($CacheReplacementPolicy)){$CacheReplacementPolicy="heap_LFUDA";}

        $CacheReplacementPolicyT=$CacheReplacementPolicyZ[$CacheReplacementPolicy];
        $SquidMemoryReplacementPolicy=$CacheReplacementPolicyZ[$SquidMemoryReplacementPolicy];


        if($CacheReplacementPolicyT==null){$CacheReplacementPolicyT="heap LFUDA";}
        if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="heap LFUDA";}

        $SquidReadAheadGap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReadAheadGap"));
        if($SquidReadAheadGap==0){$SquidReadAheadGap=1024;}
        $SquidMaximumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMaximumObjectSize"));
        if($SquidMaximumObjectSize==0){$SquidMaximumObjectSize="32768";}
        if($SquidMiniMumObjectSize==0){$SquidMiniMumObjectSize=1;}
        if($SquidMemoryCacheMode==null){$SquidMemoryCacheMode="always";}
        if($max_filedesc>1024 ){$conf[]="max_filedescriptors $max_filedesc";}


        $conf[]="memory_replacement_policy $SquidMemoryReplacementPolicy";
        if($SquidDisableMemoryCache==0) {
            $conf[] = "maximum_object_size_in_memory $maximum_object_size_in_memory KB";
            $conf[] = "memory_cache_mode $SquidMemoryCacheMode";
        }else{
            $conf[] = "maximum_object_size_in_memory 1 bytes";
            $conf[] = "memory_cache_mode disk";
        }

        if($this->DisableAnyCache==1) {
            $conf[] = "# # # CACHES ARE DISABLED # # # [" . __LINE__ . "]";
            $conf[] = "# SquidCachesProxyEnabled = $this->SquidCachesProxyEnabled [" . __LINE__ . "]";
            return @implode("\n", $conf);
        }

        $this->global_conf_cache_array_defaults();
        if($this->SquidCacheLevel==0){return "# # # LOW CACHE LEVEL # # # [".__LINE__."]\n";}


        $conf[]="cache_swap_high {$SquidCacheSwapHigh}";
        $conf[]="cache_swap_low {$SquidCacheSwapLow}";
        $conf[]="read_ahead_gap {$SquidReadAheadGap} KB";
        $conf[]="store_avg_object_size 64 KB";
        $conf[]="store_objects_per_bucket 50";
        $conf[]="cache_replacement_policy $CacheReplacementPolicyT";

        $conf[]="maximum_object_size $SquidMaximumObjectSize MB";
        $conf[]="minimum_object_size $SquidMiniMumObjectSize bytes";
        return @implode("\n", $conf)."\n";

    }


    private function global_conf_cache_array_defaults(){

        if(!isset($this->global_conf_array["request_header_max_size"])){$this->global_conf_array["request_header_max_size"]="64 KB";}
        if(!isset($this->global_conf_array["reply_header_max_size"])){$this->global_conf_array["reply_header_max_size"]="64 KB";}


        if(!isset($this->global_conf_array["reply_body_max_size"])){$this->global_conf_array["reply_body_max_size"]=0;}
        if($this->global_conf_array["minimum_object_size"]==null){$this->global_conf_array["minimum_object_size"]="0 KB";}
        if(!isset($this->global_conf_array["memory_cache_mode"])){$this->global_conf_array["memory_cache_mode"]="always";}
        if($this->global_conf_array["memory_cache_mode"]==null){$this->global_conf_array["memory_cache_mode"]="always";}
        if(!isset($this->global_conf_array["memory_replacement_policy"])){$this->global_conf_array["memory_replacement_policy"]="lru";}
        if($this->global_conf_array["memory_replacement_policy"]==null){$this->global_conf_array["memory_replacement_policy"]="lru";}



    }



    public function WORKERS_NUMBER(){
        if(isset($GLOBALS["SQUID_WORKERS_NUMBER"])){return $GLOBALS["SQUID_WORKERS_NUMBER"];}
        $GLOBALS["SQUID_WORKERS_NUMBER"]=$this->WORKERS();
        return  $GLOBALS["SQUID_WORKERS_NUMBER"];
    }

    private function WORKERS(){
        $unix=new unix();
        $CPU_NUMBER=$unix->CPU_NUMBER();
        if($CPU_NUMBER<1){$CPU_NUMBER=1;}
        $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
        $c=count($SquidSMPConfig);
        if($c==0){$c=1;}
        if($c>$CPU_NUMBER){$c=$CPU_NUMBER;}
        return $c;
    }


    private function build_progress($text){

        if(!isset($GLOBALS["PRC"])){$GLOBALS["PRC"]=$this->CURRENT_PROGRESS;}


        ///--wccp-build

        $GLOBALS["PRC"]++;
        echo "Starting......: ".date("H:i:s")." $text {$GLOBALS["PRC"]}%\n";
        if(!function_exists("build_progress")){return;}
        if($this->CURRENT_PROGRESS==0){return;}
        if($GLOBALS["PRC"]>$this->MAX_PROGRESS){$GLOBALS["PRC"]=$this->MAX_PROGRESS;}
        build_progress("{reconfigure} $text",$GLOBALS["PRC"]);
    }


    public function build(){
        $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $SquidUrgencyCaches=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgencyCaches"));
        $SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
        $SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
        $squid_cache_mem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_cache_mem"));
        if($squid_cache_mem==0){$squid_cache_mem=256;}
        $conf[]="#  --- ".date("Y d F l H:i:s")." ---";

        $conf[]="#";
        $conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
        $conf[]="# Disable Digest generation.";
        $conf[]="#digest_generation off";
        $conf[]="#";




        $conf[]="#";

        if( $SquidCacheLevel ==0){
            $conf[]="cache_mem 512 KB";
            $conf[]="#";
            $conf[]="#";
            $conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
            $conf[]="# ! ! ! ! ! ! ! DISABLED (SquidCacheLevel)! ! ! ! ! ! ! !";
            $WORKERS=$this->WORKERS();
            $conf[]="workers $WORKERS";
            $conf[]="#";
            $conf[]="#";
            @file_put_contents("/etc/squid3/caches.conf", @implode("\n", $conf));
            return;
        }


        if( $SquidDisableCaching ==1){

            $conf[]="cache deny all";
            $conf[]="send_hit deny all";
            $conf[]="store_miss deny all";
            $conf[]="cache_mem 512 KB";
            $conf[]="#";
            $conf[]="#";
            $conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
            $conf[]="# ! ! ! ! ! ! ! TEMPORARY DISABLED (SquidDisableCaching)! ! ! ! ! ! ! !";
            $WORKERS=$this->WORKERS();
            $conf[]="workers $WORKERS";
            $conf[]="#";
            $conf[]="#";
            @file_put_contents("/etc/squid3/caches.conf", @implode("\n", $conf));
            return;
        }

        if( $SquidUrgency ==1){
            $conf[]="cache_mem 512 KB";
            $conf[]="#";
            $conf[]="#";
            $conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
            $conf[]="# ! ! ! ! ! ! ! EMERGENCY ! ! ! ! ! ! ! !";
            $conf[]="workers 1";
            $conf[]="#";
            $conf[]="#";
            @file_put_contents("/etc/squid3/caches.conf", @implode("\n", $conf));
            return;
        }
        if( $SquidUrgencyCaches ==1){
            $conf[]="cache_mem 512 KB";
            $WORKERS=$this->WORKERS();
            $conf[]="#";
            $conf[]="#";
            $conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
            $conf[]="# ! ! ! ! ! ! ! EMERGENCY ! ! ! ! ! ! ! !";
            $conf[]="#";
            $conf[]="#";
            $conf[]="workers $WORKERS";
            $conf[]="#";
            $conf[]="#";
            @file_put_contents("/etc/squid3/caches.conf", @implode("\n", $conf));
            return;
        }

        $squid_memory_cache_mode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
        if($squid_memory_cache_mode==null){$squid_memory_cache_mode="always";}
        $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
        $this->build_progress("Checking Workers");
        $WORKERS=$this->WORKERS();
        if($SquidCacheLevel>0) {
            if ($SquidDisableMemoryCache == 0) {
                $conf[] = "cache_mem {$squid_cache_mem} MB\n";
            }else{
                $squid_memory_cache_mode="disk";
                $conf[] = "cache_mem 51200 bytes\n";
            }
        }
        $this->build_progress("Checking Default cache");

        $conf[]="#";
        $conf[]="#";
        $conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
        $conf[]="#";
        $conf[]="# --------- MULTIPLE CPUs --------- ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
        $conf[]="# --------- Number of CPU.....: $WORKERS ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
        $conf[]="# --------- SquidBoosterEnable: $this->SquidBoosterEnable ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
        $conf[]="# --------- DisableAnyCache...: $this->DisableAnyCache ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
        $conf[]="# --------- SquidCacheLevel...: $this->SquidCacheLevel ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
        $conf[]="# --------- SquidDisableCaching: $SquidDisableCaching ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";



        $conf[]=$this->cache_tweaks();
        $conf[]="";
        $this->build_progress("Checking Rock cache");

        $conf[]="#";
        $conf[]="# # # # # # WORKERS # # # # # #";
        $conf[]="#";
        $conf[]="workers $WORKERS";
        $conf[]="#";
        $conf[]="#";
        $rockLines = null;

        if($WORKERS>1){
            $shared_memory_locking_disable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("shared_memory_locking_disable"));
            if($shared_memory_locking_disable==1){
                $f[]="shared_memory_locking off";
            }else{
                $f[]="shared_memory_locking on";
            }
            $rockLines=$this->build_rock();
        }

        $conf[]=$rockLines;
        $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));


        for($i=1;$i<$WORKERS+1;$i++){
            $conf[]="# Building CPU $i";
            $conf[]=$this->build_CPU($i);
        }


        for($i=1;$i<$WORKERS+1;$i++){
            $conf[]="# --------- CPU #{$i} ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
            $conf[]="if \${process_number} = $i";
            $conf[]="memory_cache_mode $squid_memory_cache_mode";
            $this->build_progress("Checking #CPU $i");
            if(isset($SquidSMPConfig[$i])){
                if($SquidSMPConfig[$i]>0){
                    $conf[]="cpu_affinity_map process_numbers=\${process_number} cores={$SquidSMPConfig[$i]}";
                }
            }
            $conf[]="endif";

        }
        $conf[]="#";
        $conf[]="#";
        $this->build_progress("Checking SMP done.");
        @file_put_contents("/etc/squid3/caches.conf", @implode("\n", $conf));
    }




    public function build_rock(){
        if($this->DisableAnyCache==1){return "";}
        $q=new mysql();
        $unix=new unix();
        $conf=array();
        $sock=new sockets();
        $EnableRockCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRockCache"));
        $conf[]="# Rock cache EnableRockCache = $EnableRockCache [".__LINE__."]";
        if($EnableRockCache==0){return @implode("\n", $conf);}

        $SquidRockPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
        $SquidRockSwapTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSwapTimeOut"));
        $SquidRockMaxSwap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSwap"));
        $SquidRockSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSize"));
        $SquidRockMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSize"));
        if($SquidRockMaxSize==0){$SquidRockMaxSize=128;}
        $SquidRockMaxSize=$SquidRockMaxSize*1024;

        $cachename=basename($SquidRockPath);
        $cache_dir=$SquidRockPath."/rock";
        $cache_dir=str_replace("//", "/", $cache_dir);
        $cache_size=$SquidRockSize;
        if($cache_dir==null){
            $conf[]="# Rock cache EnableRockCache = $EnableRockCache [".__LINE__."]";
            $conf[]="# Rock cache is null, aborting [".__LINE__."]";
            $this->IsCacheRock=false;
            return @implode("\n", $conf);


        }
        $this->CreateDir($cache_dir);

        $conf[]="# --------- ROCK $cache_dir Size:{$cache_size}M max objects size:{$SquidRockMaxSize}Bytes";


        $tt[]="min-size=0";
        if($SquidRockMaxSize>0){$tt[]="max-size=".$SquidRockMaxSize;}
        if($SquidRockMaxSwap>0){$tt[]="max-swap-rate=".$SquidRockMaxSwap;}
        if($SquidRockSwapTimeOut>0){$tt[]="swap-timeout=".$SquidRockSwapTimeOut;}
        $tt[]="slot-size=32000";
        $conf[]="cache_dir rock $cache_dir $cache_size ".@implode(" ", $tt);
        $this->IsCacheRock=true;
        return @implode("\n", $conf);
    }

    private function IsSNMP(){
        $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
        if(count($SquidSMPConfig)==0){$SquidSMPConfig[1]=1;}
        foreach ($SquidSMPConfig as $num=>$val){
            if($val==null){continue;}
            $val=intval($val);
            if($val==0){continue;}
            $CPUZ[$num]=true;
        }
        if(count($CPUZ)>1){return true;}
        return false;
    }

    private function build_CPU($cpu){
        $q=new lib_sqlite("/home/artica/SQLITE/caches.db");
        $this->build_progress("Loading MySQL settings for CPU #$cpu");
        $sql="SELECT * FROM $this->table WHERE `enabled`=1 AND `cpu`=$cpu ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){$conf[]="# SQLite Error";}
        $conf[]="# CPU #$cpu table $this->table ".count($results). " defined caches.";
        $Toend=null;
        $ALREADYSET=array();
        $IsSNMP=$this->IsSNMP();
        foreach($results as $index=>$ligne) {
            $ID=$ligne["ID"];
            $remove=intval($ligne["remove"]);
            if($remove==1){continue;}
            $max_size_text=null;
            $cachename=$ligne["cachename"];
            $cache_dir=$ligne["cache_dir"];
            if(isset($ALREADYSET[$cache_dir])){continue;}
            $ALREADYSET[$cache_dir]=true;
            $cache_type=$ligne["cache_type"];
            $cache_size=abs(intval($ligne["cache_size"]));
            $cache_dir_level1=intval($ligne["cache_dir_level1"]);
            $cache_dir_level2=intval($ligne["cache_dir_level2"]);
            $RemoveSize=intval($ligne["RemoveSize"]);
            $cache_dir=str_replace("//", "/", $cache_dir);
            if(trim($cache_dir)==null){continue;}

            if($cache_dir_level1==0){continue;}
            if($cache_dir_level2==0){continue;}
            if($cache_size==0){continue;}

            $min_size=intval($ligne["min_size"]);
            $max_size=$ligne["max_size"];
            $max_size_src=$max_size;

            if($this->SquidBoosterEnable==1){
                if($min_size==0){$min_size=256;}
            }





            $this->build_progress("Cache $cachename Cache size:{$cache_size}M");
            $conf[]="#";
            $conf[]="# [CPU #{$cpu}]: Cache {$cache_dir} name:$cachename ID = $ID type:$cache_type Cache size:{$cache_size}M is Rock=$this->IsCacheRock SMP=$IsSNMP [".__LINE__."]";
            if($this->DisableAnyCache==1){$cache_type="Cachenull";}

            if($IsSNMP) {
                if ($cache_type <> "rock") {
                    $conf[]="# [CPU #{$cpu}]: $cache_type Not supported cache format in SMP configuration";
                    continue;
                }
            }

            if($cache_type=="tmpfs"){
                $conf[]=$this->build_MemBooster($ID,$cache_size,$min_size,$max_size,$RemoveSize);
                continue;
            }

            if($cache_type=="Cachenull"){continue;}


            if(@is_link($cache_dir)){$cache_dir=@readlink($cache_dir);}
            $this->CreateDir($cache_dir);


            $min_size_text=" min-size=0";

            if($max_size>0){
                $max_size=$max_size*1024;
                $max_size_text=" max-size=$max_size";
            }

            if($min_size>0){
                $min_size=$min_size*1024;
                $min_size_text=" min-size=$min_size";
            }

            if($RemoveSize==1){$min_size_text=null;$max_size_text=null;}

            if($cache_type=="rock"){
                $lineZ="cache_dir\trock\t$cache_dir\t$cache_size\t$min_size_text$max_size_text";
                $conf[]=$lineZ;
                continue;
            }

            if($this->IsCacheRock){
                $SquidRockMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSize"));
                if($SquidRockMaxSize==0){$SquidRockMaxSize=128;}
                $min_size=$SquidRockMaxSize;
                if($min_size>$max_size_src){
                    $max_size_src=$min_size*2;
                    $max_size_src=$max_size_src*1024;
                    $max_size_text=" max-size=$max_size_src";
                }

                $min_size=$min_size*1024;
                $min_size_text=" min-size=$min_size";
                $conf[]="# --------- [CPU #{$cpu}]: Is ROCK = True, min size = '$min_size_text' [".__LINE__."]";
            }


            $lineZ="cache_dir\t$cache_type\t$cache_dir\t$cache_size\t$cache_dir_level1\t$cache_dir_level2$min_size_text$max_size_text";
            if($cachename=="cache-default"){$Toend=$lineZ;continue;}

            $conf[]=$lineZ;

        }

        if($Toend<>null){
            $conf[]=$Toend;
        }

        $conf[]="# --------- [CPU #{$cpu}]: ".count($conf)." items [".__LINE__."]";
        $this->build_progress("Checking #CPU $cpu - Done -");
        return @implode("\n", $conf);

    }

    private function build_MemBooster($ID,$cache_size,$minsize=0,$maxsize=0,$RemoveSize=0){
        $GLOBALS["BuildSquidCachesMountInRam"][]="# Checking Memory Booster $ID {$cache_size}MB [".__LINE__."]";
        $this->build_progress("Checking Memory Booster $ID {$cache_size}MB");
        $directory="/home/squid/cache/MemBooster$ID";
        $options=array();

        $cache_size_conf=$cache_size-10;
        if($minsize>0){$minsize=$minsize*1024;}
        if($maxsize>0){$maxsize=$maxsize*1024;}
        if($minsize==0){$minsize=2048;}

        if($RemoveSize==0){
            $options[]="min-size=$minsize";
            if($maxsize>0){$options[]="max-size=$maxsize";}
        }

        $conf="cache_dir	aufs	$directory $cache_size_conf 16 256 ".@implode(" ", $options);

        $this->CreateDir($directory);
        $MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
        if($MountedRAM==0){
            $this->BuildSquidCachesMountInRam($cache_size,$ID);
            $MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
            if($MountedRAM>0){return $conf;}
            return "# --------- $ID {$cache_size}MB unable to mount $directory\n".@implode("\n", $GLOBALS["BuildSquidCachesMountInRam"]);

        }


        $Diff=intval($cache_size-$MountedRAM);
        if(preg_match("#^-([0-9]+)#", $Diff,$re)){$Diff=intval($re[1]);}
        if($Diff>5){
            $this->BuildSquidCachesMountUmount($ID);
            $this->BuildSquidCachesMountInRam($cache_size,$ID);
            $MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
            if($MountedRAM>0){return $conf;}
            return "# --------- $ID {$cache_size}MB unable to mount $directory";
        }
        return $conf;

    }

    private function  BuildSquidCachesMountUmount($Index){
        $unix=new unix();
        $umount=$unix->find_program("umount");
        $rm=$unix->find_program("rm");
        $Dir="/home/squid/cache/MemBooster$Index";
        shell_exec("$umount -l $Dir");
        shell_exec("$rm -rf  -l $Dir");

    }

    private function CreateDir($directory){
        @mkdir($directory,0755,true);
        @chown($directory, "squid");
        @chgrp($directory, "squid");

    }

    private function BuildSquidCachesMountInRam($RAM,$Index){
        $unix=new unix();
        $GLOBALS["BuildSquidCachesMountInRam"]=array();
        $mount=$unix->find_program("mount");
        $Dir="/home/squid/cache/MemBooster$Index";
        $this->CreateDir($Dir);
        $this->build_progress("Checking Memory Booster $Index {$RAM}MB mounting...");
        $GLOBALS["BuildSquidCachesMountInRam"][]="# Checking Memory Booster $Index {$RAM}MB mounting...";
        exec("$mount -t tmpfs -o size={$RAM}M tmpfs $Dir 2>&1",$results);
        foreach ($results as $num=>$val){
            $GLOBALS["BuildSquidCachesMountInRam"][]="# $val";
        }

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

}