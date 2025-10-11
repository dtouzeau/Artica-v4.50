<?php
$GLOBALS["BYPASS"]=true;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');


if(is_array($argv)){
	if($argv[1]=="--recategorize-category"){recategorize_single($argv[2]);exit;}
}

function build_progress_single($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $category_id=$GLOBALS["category_id"];
    $cachefile=PROGRESS_DIR."/recategorize-$category_id.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function recategorize_single($category_id){
    $pid=0;
    $GLOBALS["category_id"]=$category_id;
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(is_file($pidfile)){$pid=@file_get_contents($pidfile);}


    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." Already executed PID: $pid since {$time}Mn\n";
        if($time<120){if(!$GLOBALS["FORCE"]){exit();}}
        unix_system_kill_force($pid);
    }

    if(is_file($pidfile)){@unlink($pidfile);}
    @file_put_contents($pidfile, getmypid());

    $q=new postgres_sql();

    if(!$q->FIELD_EXISTS("statscom_websites","lastseen")) {
        $q->QUERY_SQL("ALTER TABLE statscom_websites ADD lastseen bigint default 0");
    }

    $results    = $q->QUERY_SQL("SELECT * FROM statscom_websites WHERE category = $category_id");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress_single(110,"SQL {failed}");
    }
    $catz       = new mysql_catz();
    $libmem     = new lib_memcached();
    $TimeStart  = time();
    $mem_ttl    = 172800;
    $GLOBALS["NOCACHE"]=true;
    $TotalRows=pg_num_rows($results);
    echo "Analyze $TotalRows records\n";

    $c=0;
    $final_rate=null;
    $percent=0;
    $ContentLine=array();
    $sec_start=time();
    $sec_sites=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $c++;$sec_sites++;
        if($c >12000){build_progress_single(100,"MAx = 12000, skip!");break;}

        $precent_tmp=$c/$TotalRows;
        $precent_tmp=round($precent_tmp*100);
        if($precent_tmp>99){$precent_tmp=99;}
        if($precent_tmp>$percent){
            $percent=$precent_tmp;
            $Numberofsec=time()-$sec_start;
            if($Numberofsec>1) {
                $sitesSec = round($sec_sites / $Numberofsec);
                $Calculation = "$c/$TotalRows {$sitesSec} sites per second, $c sites in $Numberofsec seconds";
                $text="$Calculation - $sec_sites websites in {$Numberofsec} seconds";
            }else{
                $text="$sec_sites websites per second";
            }
            build_progress_single($precent_tmp,$text );


        }
        $siteid         = $ligne["siteid"];
        $category_src   = $ligne["category"];
        $sitename       = $ligne["sitename"];
        $new_category = $catz->GET_CATEGORIES($sitename);

        $q->QUERY_SQL("UPDATE statscom_websites SET lastseen=$sec_start WHERE siteid=$siteid");

        if(!$q->ok){echo $q->mysql_error."\n";break;}
        if($new_category==0){continue;}
        if($category_src==$new_category){continue;}


        $category_src_text=$catz->CategoryIntToStr($category_src);
        $new_category_text=$catz->CategoryIntToStr($new_category);
        $zcontent= "$sitename $category_src_text [$category_src], refresh to $new_category_text [$new_category]\n";
        echo "$zcontent\n";
        $ContentLine[]=$zcontent;

        if( strtolower($category_src_text)==strtolower($new_category_text) ){continue;}
        $zcontent = "$sitename was moved from [$category_src_text] to [$new_category_text]";
        echo "$zcontent\n";
        $q->QUERY_SQL("UPDATE statscom_websites SET category='$new_category' WHERE siteid='$siteid'");
        if(!$q->ok){
            echo $q->mysql_error."\n";
            build_progress_single(110,"SQL {failed}");
            return false;
        }

        $ContentLine[]=$zcontent;

    }

    $TimeOff=time();
    $countResults=count($ContentLine);
    $CountOfTime=$unix->distanceOfTimeInWords_text($TimeStart,$TimeOff,true);

    if(is_numeric($sitesSec)) {
        $final_rate="For a rate of $sitesSec websites per second";
    }
    build_progress_single(100,"$countResults {recategorized} $final_rate ");
    squid_admin_mysql(2,"$TotalRows as been rescanned with $countResults moved site(s) $final_rate","Took $CountOfTime\n".@implode("\n",$ContentLine),__FILE__,__LINE__);

    return true;
}

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/recategorize.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


	if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

	build_progress(10, "{starting}");
	
	if(!is_dir("/etc/artica-postfix/pids")){@mkdir("/etc/artica-postfix/pids",666,true);}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($pid)){
		build_progress(110,"Already process exists $pid aborting");
		events("Already process exists $pid aborting");
		exit();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$memcache=new lib_memcached();
	build_progress(10, "{analyze}");
	
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT * FROM not_categorized ORDER BY rqs DESC");
	
	
	$MAX=pg_num_rows($results);
	build_progress(11, "{analyze} $MAX {elements}");
	
	$ct=new mysql_catz();
	$c=0;
	while ($ligne = pg_fetch_assoc($results)) {
		$c++;
        $domain=trim($ligne["familysite"]);

		if(strpos($domain,".")==0){
		    echo "Domain $domain doesn't have dot...\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='{$ligne["familysite"]}'");
            continue;
        }
        if(strpos($domain,",")>0) {
            echo "Domain $domain have comma...\n";
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='{$ligne["familysite"]}'");
            continue;
        }

		if($domain==null){
			echo "DOMAIN is null...\n";
			$q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='{$ligne["familysite"]}'");
			continue;
		}
		$category_id=intval($ct->GET_CATEGORIES($domain));
		if($category_id==0){
			$prc=$c/$MAX;
			$prc=$prc*100;
			$prc=round($prc);
			
			if($prc<11){
				build_progress(10, "$domain UNKNOWN");
				continue;
			}
			if($prc>99){continue;}
			echo "$c: $domain FAILED...{$prc}%\n";
			
			continue;
		}

        $key_not_categorized="notcategorized.".strtolower($ligne["familysite"]);
        $memcache->Delkey($key_not_categorized);
		if($category_id>0){$q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='{$ligne["familysite"]}'");}
		$prc=$c/$MAX;
		$prc=$prc*100;
		echo "$c: $domain $category_id...{$prc}%\n";
		$prc=round($prc);
		if($prc<11){continue;}
		if($prc>99){continue;}
		build_progress($prc, "$domain OK");
	}

    $ligne=$q->mysqli_fetch_array("SELECT count(familysite) as tcount FROM not_categorized");
    $not_categorized_int=intval($ligne["tcount"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PERSONAL_NOT_CATEGORIZED_COUNT",$not_categorized_int);
	
	build_progress(100, "{success}");
	
	
function cloudlogs($text=null){
		$logFile="/var/log/cleancloud.log";
		$time=date("Y-m-d H:i:s");
		$PID=getmypid();
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {
			$size=filesize($logFile);
			if($size>1000000){unlink($logFile);}
		}
		$logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$time [$PID]: $text\n");
		@fclose($f);
	}	

function events($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		$common="/var/log/artica-postfix/squid.stats.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
		$h = @fopen($common, 'a');
		$sline="[$pid] $text";
		$line="$date [$pid] $text\n";
		@fwrite($h,$line);
		@fclose($h);
}