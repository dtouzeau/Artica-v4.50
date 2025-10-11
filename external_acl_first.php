#!/usr/bin/php
<?php
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["SQUID_AS_PLUGIN"]=true;
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.external_acls_user.inc");
include_once(dirname(__FILE__).'/ressources/class.memcached.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.cloud.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if (!isset($GLOBALS["ARTICALOGDIR"])) {
    $GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");
    if ($GLOBALS["ARTICALOGDIR"]==null) {
        $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix";
    }
}
ini_set('display_errors', 1);	ini_set('html_errors', 0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
  $GLOBALS["UfdbgclientSockTimeOut"]    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientSockTimeOut"));
  $GLOBALS["UseCloudArticaCategories"]  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseCloudArticaCategories"));
  $GLOBALS["CloudArticaCategoriesOutgoing"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CloudArticaCategoriesOutgoing"));
  $GLOBALS["EnableNoTracks"]            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNoTracks"));
  $GLOBALS["CategoryItemsInMemory"]     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHotCacheSize"));
  $GLOBALS["EnableURLhaus"]             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableURLhaus"));
  $GLOBALS["QUERY_CATEGORIES"]          = 0;
  $GLOBALS["EnableLocalUfdbCatService"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
  $GLOBALS["UseCloudArticaCategories"]  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseCloudArticaCategories"));
  $GLOBALS["UseLocalArticaCategories"]  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseLocalArticaCategories"));
  $GLOBALS["EnableProxyGeoIP"]          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyGeoIP"));
  $GLOBALS["PID"]                       = getmypid();
  $GLOBALS["SPLASH_DEBUG"]              =   false;
  $catzz                                =   new mysql_catz();
  $GLOBALS["QUERY_CATEGORIES"]          = $GLOBALS["EnableLocalUfdbCatService"]+$GLOBALS["UseCloudArticaCategories"]+$GLOBALS["UseLocalArticaCategories"]+$GLOBALS["RemoteUfdbCat"];
  $GLOBALS["EnableStrongswanServer"]        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer"));
  $GLOBALS["ExternalAclFirstDebug"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ExternalAclFirstDebug"));

  $GLOBALS["SPLASH"]=false;
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["DEBUG_LEVEL"]=0;
  $GLOBALS["ROTATETIME"]=time();
  $GLOBALS["CheckNoTracksTime"]=time();
  $GLOBALS["CATEGORIES"]=array();
  $GLOBALS["MEMORY_WEBSITES"]=array();
  $GLOBALS["EnableUfdbGuard"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
  if ($GLOBALS["UfdbgclientSockTimeOut"]==0) {
      $GLOBALS["UfdbgclientSockTimeOut"]=2;
  }
  if ($GLOBALS["CategoryItemsInMemory"]==0) {
      $GLOBALS["CategoryItemsInMemory"]=5000;
  }

  if (!class_exists("memcached")) {
      WLOG("Starting: memcached...............: No such class");
  }

  $lib_track_user=new lib_track_user();
  $max_execution_time=ini_get('max_execution_time');
  WLOG("Starting: Log level...............:{$GLOBALS["DEBUG_LEVEL"]};");
  WLOG("Starting: UseCloudArticaCategories:{$GLOBALS["UseCloudArticaCategories"]}", true);
  WLOG("Starting: CategoryItemsInMemory...:{$GLOBALS["CategoryItemsInMemory"]}", true);
  WLOG("Starting: Query categories ?......:{$GLOBALS["QUERY_CATEGORIES"]}", true);
  $localcatz=array();

  $EnableCategoriesCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesCache"));
  $CategoriesCacheRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheRemote"));
  if($CategoriesCacheRemote==1){$EnableCategoriesCache=1;}

  if(is_file("/etc/squid3/compiled-categories.db")) {$localcatz = unserialize(@file_get_contents("/etc/squid3/compiled-categories.db"));}
  $redis_socket="/var/run/categories-cache/categories-cache.sock";
  $fam=new familysite();
  $catzz=new mysql_catz();
  $ccloud=new artica_cloud();
  $redis_ready=false;
  if($EnableCategoriesCache==1) {

      $CategoriesCacheRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheRemoteAddr"));
      $CategoriesCacheRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheRemotePort"));
      if($CategoriesCacheRemotePort==0){$CategoriesCacheRemotePort=2214;}


      try {
            $redis = new Redis();
            if($CategoriesCacheRemote==1) {
                $redis->connect($CategoriesCacheRemoteAddr,$CategoriesCacheRemotePort);
            }else{
                $redis->connect($redis_socket);
            }
            $redis_ready = true;
      } catch (Exception $e) {
            ToSyslog("Categories Cache error " . $e->getMessage());

      }
    }

  if ($argv[1]=="--catz") {
      $GLOBALS["VERBOSE"]=true;
      echo $catzz->CategoryIntToStr($ccloud->GET_CATEGORIES($argv[2]))."\n";
      exit;
  }
    if($GLOBALS["ExternalAclFirstDebug"]==1){$GLOBALS["DEBUG_LEVEL"]=10;}
    if(!isset($GLOBALS["MEMCACHE"])){$GLOBALS["MEMCACHE"]=new lib_memcached();}
    $TEMPCACHE=array();



while (!feof(STDIN)) {
 	$url = trim(fgets(STDIN));
 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("GET ------------- <$url> -------------");}
 	$CHOOSE=null;
 	if($url==null){
        continue;
     }
 	$clt_conn_tag=null;
 	$TOKENS=array();
 	$USER_CERT=null;
 	$CATEGORY=null;
 	$BLOCK=false;
 	$WHITE=false;
 	$WHITE_CACHE=false;$category_name=null;
    $FULL_URL=null;
 	$CNX_ID=0;
 	$tt=null;
 	$re=preg_split("/[\s]+/", $url);
 	if(is_numeric($re[0])){$CNX_ID=$re[0];}
 	
 	if(count($re)<5){
 		WLOG("BAD ARRAY Return <{$CNX_ID} OK>");
		fwrite(STDOUT, "{$CNX_ID} OK\n");
        continue;
 	}
    $FAMILYSITE=null;
    $TOKENS_CATEGORY_ADDED=false;
    $USERNAME=trim($re[1]);
    $IPADDR1=trim($re[2]);
    $MACADDR=strtolower(trim($re[3]));
    $IPADDR2=trim($re[4]);
    $CATEGORY=0;
    
    if ($USERNAME=="-") {$USERNAME=null;}
    if ($IPADDR2=="-") {$IPADDR2=null;}
    if ($MACADDR=="00:00:00:00:00:00") {$MACADDR=null;}
    if ($MACADDR=="-") {$MACADDR=null;}
    if ($IPADDR2<>null) {if ($IPADDR2<>$IPADDR1) {$IPADDR1=$IPADDR2;}}
    
    $WEBSITE=trim(strtolower($re[5]));
    $WEBSITE_SNI=trim($re[6]);
    if (isset($re[7])) {$USER_CERT=$re[7];}
    if ($USER_CERT=="-") {$USER_CERT=null;}
    if ($WEBSITE_SNI=="-") {$WEBSITE_SNI=null;}
    if ($WEBSITE_SNI<>null) {$WEBSITE=$WEBSITE_SNI;}
    if ($USERNAME<>null) {$CHOOSE=$USERNAME;}
    if ($CHOOSE==null) {if ($MACADDR<>null) {$CHOOSE=$MACADDR;}}
    if ($CHOOSE==null) {$CHOOSE=$IPADDR1;}
    $FAMILYSITE=null;
    $mem=new lib_memcached();
    if (substr($WEBSITE,0,4)=='www.'){$WEBSITE=substr($WEBSITE,4,strlen($WEBSITE));}
    $key_not_categorized="notcategorized.".strtolower($WEBSITE);

    $redis_ready_saved=false;
    $CountLocal=count($localcatz);
    if(count($TEMPCACHE)>10000){$TEMPCACHE=array();}

    if(count($localcatz)>0){
        if(isset($localcatz[$WEBSITE])){
            $CATEGORY=$localcatz[$WEBSITE]["category_id"];
            $category_name=$localcatz[$WEBSITE]["category_name"];
        }
        if($CATEGORY==0) {
            if($FAMILYSITE==null){$FAMILYSITE=$fam->GetFamilySites($WEBSITE);}
            if(isset($localcatz[$FAMILYSITE])){
                $CATEGORY=$localcatz[$FAMILYSITE]["category_id"];
                $category_name=$localcatz[$FAMILYSITE]["category_name"];
            }
        }
    }

    if($CATEGORY==0) {
        if (isset($TEMPCACHE[$key_not_categorized])) {
            $GetUser = GetUser($USERNAME, $USER_CERT, $MACADDR, $IPADDR1, $lib_track_user, $CHOOSE);
            $USERNAME = $GetUser["USER"];
            $TOK = $GetUser["TOKEN"];
            $TOKENS[] = "category=0 clog=cinfo:0-unknown;";
            if ($TOK <> null) {
                $TOKENS[] = $TOK;
            }
            $tt = @implode(" ", $TOKENS);
            WLOG("!!! NOT CATEGORIZED !!! Return <{$CNX_ID} OK $tt>", true);
            fwrite(STDOUT, "{$CNX_ID} OK $tt\n");
            continue;
        }

        if (isset($TEMPCACHE[$WEBSITE])) {
            $CATEGORY = $TEMPCACHE[$WEBSITE][0];
            $category_name = $TEMPCACHE[$WEBSITE][1];
            $category_name = str_replace(" ", "_", $category_name);
            $category_name = str_replace("/", "_", $category_name);
            $GetUser = GetUser($USERNAME, $USER_CERT, $MACADDR, $IPADDR1, $lib_track_user, $CHOOSE);
            $USERNAME = $GetUser["USER"];
            $TOK = $GetUser["TOKEN"];
            $TOKENS[] = "category=$CATEGORY clog=cinfo:$CATEGORY-$category_name;";
            if ($TOK <> null) {
                $TOKENS[] = $TOK;
            }
            $tt = @implode(" ", $TOKENS);
            WLOG("HIT Return <{$CNX_ID} OK $tt>", true);
            fwrite(STDOUT, "{$CNX_ID} OK $tt\n");
            continue;
        }

        if (intval($mem->getKey($key_not_categorized)) == 1) {
            $GetUser = GetUser($USERNAME, $USER_CERT, $MACADDR, $IPADDR1, $lib_track_user, $CHOOSE);
            $USERNAME = $GetUser["USER"];
            $TOK = $GetUser["TOKEN"];
            $TOKENS[] = "category=0 clog=cinfo:0-unknown;";
            if ($TOK <> null) {
                $TOKENS[] = $TOK;
            }
            $tt = @implode(" ", $TOKENS);
            WLOG("NOT CATEGORIZED Return <{$CNX_ID} OK $tt>", true);
            fwrite(STDOUT, "{$CNX_ID} OK $tt\n");
            continue;
        }
    }

    if($redis_ready) {
        try {
            $WEBSITE_MD = md5($WEBSITE);
            $CATEGORY = intval($redis->get($WEBSITE_MD));
            if($CATEGORY>0){
                WLOG("[$CNX_ID][$WEBSITE] * * * HIT * * * $WEBSITE_MD CATEGORY: <$CATEGORY>", true);
            }
            if ($CATEGORY == 0) {
                $FAMILYSITE = $fam->GetFamilySites($WEBSITE);
                $FAMMD_MD = md5($FAMILYSITE);
                $CATEGORY = intval($redis->get($FAMMD_MD));
                WLOG("[$CNX_ID][$WEBSITE] - $FAMILYSITE - $FAMMD_MD CATEGORY: <$CATEGORY>", true);
                if ($CATEGORY > 0) {
                    $redis->set($WEBSITE_MD, $CATEGORY,18800);
                    $redis_ready_saved=true;
                }
            }
        } catch (Exception $e) {
            ToSyslog("Categories Cache error L.".__LINE__." " . $e->getMessage());

        }
    }

    if($CATEGORY==0) { $CATEGORY = intval($ccloud->GET_CATEGORIES($WEBSITE));}
    if($CATEGORY==0) {
        $TEMPCACHE[$key_not_categorized]=1;
        $mem->saveKey($key_not_categorized, 1, 3600);
    }
    $GetUser=GetUser($USERNAME,$USER_CERT,$MACADDR,$IPADDR1,$lib_track_user,$CHOOSE);
    $USERNAME=$GetUser["USER"];
    $TOK=$GetUser["TOKEN"];
    if($TOK<>null){$TOKENS[]=$TOK;}

    WLOG("[$CNX_ID][$WEBSITE] ($CHOOSE) CATEGORY: <$CATEGORY> WEBSITE SNI <$WEBSITE/$WEBSITE_SNI> USERNAME: <$USERNAME> MACADDR: <$MACADDR> IPADDR1: <$IPADDR1>", true);

    if ($CATEGORY>0) {
        if ($redis_ready) {
            if (!$redis_ready_saved) {
                try {
                    $redis->set($WEBSITE_MD, $CATEGORY, 18800);
                } catch (Exception $e) {
                    ToSyslog("Categories Set Cache error L." . __LINE__ . " " . $e->getMessage());
                }
            }
        }

        if ($category_name == null) {
            $category_name = $catzz->CategoryIntToStr($CATEGORY);
        }
        $category_name = str_replace(" ", "_", $category_name);
        $category_name = str_replace("/", "_", $category_name);
        $TEMPCACHE[$WEBSITE]=array($CATEGORY,$category_name);
        $TOKENS_CATEGORY_ADDED = true;
        $TOKENS[] = "category=$CATEGORY clog=cinfo:$CATEGORY-$category_name;";
    }


    if ($BLOCK) {
        $TOKENS[]="shieldsblock=yes";
        $tt=@implode(" ", $TOKENS);
        WLOG("Return <{$CNX_ID} ERR $tt>", true);
        fwrite(STDOUT, "{$CNX_ID} ERR $tt\n");
        continue;
    }

    if ($WHITE) {
        $TOKENS[]="rblpass=yes";
    }
    if ($WHITE_CACHE) {
        $TOKENS[]="rblcache=yes";
    }

    if (!$TOKENS_CATEGORY_ADDED) {
        $TOKENS[]="category=0 clog=cinfo:0-unknown;";
    }
    
    $tt=@implode(" ", $TOKENS);
    WLOG("Return <{$CNX_ID} OK $tt>", true);
    fwrite(STDOUT, "{$CNX_ID} OK $tt\n");
}
//----------------------------------------------------------------------------------------------------------------
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
//----------------------------------------------------------------------------------------------------------------


function GetUser($USERNAME,$USER_CERT,$MACADDR,$IPADDR1,$lib_track_user,$CHOOSE){
    $TOKENS=null;
    if ($USERNAME==null) {
        if ($USER_CERT<>null) {
            $USERNAME=$USER_CERT;
            $TOKENS="user=".urlencode($USERNAME);
        }
    }
    if ($USERNAME==null) {
        $USERNAME=$lib_track_user->UserAliases($MACADDR, $IPADDR1);
        if ($USERNAME<>null) {
            $TOKENS="user=".urlencode($USERNAME);
        }
    }
    if ($USERNAME==null) {
        if ($GLOBALS["EnableStrongswanServer"]==1) {
            $USERNAME=null;
            $Users=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanClientsArray"));
            foreach ($Users as $key=>$val) {
                foreach ($val as $k=>$v) {
                    foreach ($v as $j=>$y) {
                        if (array_key_exists('remote-eap-id', $v)) {
                            if ($j=='remote-eap-id') {
                                $USERNAME=$y;
                            }
                        } else {
                            if ($j=='remote-id') {
                                $USERNAME=$y;
                            }
                        }
                        if (is_array($y)) {
                            foreach ($y as $m=>$n) {
                                if ($m=="remote-vips") {
                                    if ($n==$IPADDR1) {
                                        $TOKENS="user=".urlencode($USERNAME);
                                        //return $USERNAME;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if ($USERNAME<>null) {
        $lib_track_user->SetHit($USERNAME);

        if($MACADDR<>null){
            $GLOBALS["MEMCACHE"]->saveKey("STATSCACHE.$MACADDR", trim($USERNAME), 30);
        }else{
            if($IPADDR1<>"127.0.0.1") {
                $GLOBALS["MEMCACHE"]->saveKey("STATSCACHE.$IPADDR1", trim($USERNAME), 30);
            }
        }
    }

    if ($USERNAME==null) {
        $lib_track_user->SetHit($CHOOSE);
    }

    return array("USER"=>$USERNAME,"TOKEN"=>$TOKENS);
}


function ToSyslog($text){
    if (!function_exists("openlog")){return false;}
    $LOG_SEV=LOG_INFO;
    openlog("ksrn", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, "[PROXY]: $text");
    closelog();
    return true;
}


function WLOG($text=null, $debug=false):bool{
    if(trim($text)==null){return false;}
    if ($debug) {if (intval($GLOBALS["DEBUG_LEVEL"])<1) {return false;}}
    ToSyslog("DEBUG $text");
    return true;
}

?>