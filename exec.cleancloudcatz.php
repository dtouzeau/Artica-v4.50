<?php
	exit();
	include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
	include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
	include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
	include_once(dirname(__FILE__) . '/framework/frame.class.inc');	
	include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
	
	$GLOBALS["SENDMAIL"]=false;
	$GLOBALS["NOPR"]=false;
	$GLOBALS["FORCE"]=false;
	//exec.cleancloudcatz.php --all --sendmail
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--sendmail#",implode(" ",$argv),$re)){$GLOBALS["SENDMAIL"]=true;}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--no-prepare#",implode(" ",$argv))){$GLOBALS["NOPR"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	
	if($argv[1]=="--nocatz"){nocatz();exit();}
	if($argv[1]=="--catz"){catz();exit();}
	if($argv[1]=="--all"){catzall();exit();}
	if($argv[1]=="--router"){nocatz($argv[2]);exit();}
	if($argv[1]=="--tests"){testcatz($argv[2]);exit();}
	if($argv[1]=="--parseblogs"){parseblogs();exit();}
	if($argv[1]=="--dtable"){Download_table($argv[2]);exit();}
	if($argv[1]=="--export-nocatz"){export_notcaz();exit();}
	if($argv[1]=="--userapi"){userapi();exit();}
	if($argv[1]=="--cleanlocal"){cleanlocal();exit();}
	if($argv[1]=="--chk"){checks_tables();exit();}
	if($argv[1]=="--bluealpha"){bluealpha();exit();}
	if($argv[1]=="--corrupted"){corrupted();exit();}
	if($argv[1]=="--webtests"){webtests();exit();}
	if($argv[1]=="--testsbright"){testsbright();exit();}
	
	
	catz();
	
	function repair_tables_sources(){
		$cats=LIST_TABLES_CATEGORIES();
		$q=new mysql_squid_builder();
	
		while (list ($index, $category_table) = each ($cats) ){
			if(strpos($category_table, ",")>0){continue;}
			$q->QUERY_SQL("DELETE FROM $category_table WHERE `enabled`=0");
			if(!$q->ok){echo $q->mysql_error;exit();}
			echo "Repair table $category_table\n";
			$q->QUERY_SQL("REPAIR TABLE $category_table");
			if(!$q->ok){echo $q->mysql_error;exit();}
			echo "optimize table $category_table\n";
			$q->QUERY_SQL("OPTIMIZE TABLE $category_table");
			if(!$q->ok){echo $q->mysql_error;exit();}
	
		}
	
	}
	
function bluealpha(){
	
	$f=explode("\n", @file_get_contents("/var/log/bluealpha.log"));
	foreach ( $f as $index=>$line ){
		if(preg_match("#^([0-9A-Z]+)\s+(.*)#", $line,$re)){
			$re[2]=str_replace('"', "", $re[2]);
			echo "{$re[1]} {$re[2]}\n";
			$array[$re[1]][]=$re[2];
			continue;
		}
		
		echo "No match $line\n";
	}
	
	foreach ($array as $index=>$line){
		echo "\n*******\n$index ".count($line)." items\n";
		echo @implode(", ", $line);
		
	}
	
	
}


	
function checks_tables(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$mysqlcheck=$unix->find_program("mysqlcheck");
	if($q->mysql_password<>null){
		$password=" -p$q->mysql_password";
		
	}
	
	
	$cmd="$mysqlcheck -C -u $q->mysql_admin $password -S $q->SocketName --databases squidlogs 2>&1";
	exec($cmd,$results);
	
	foreach ($results as $index=>$line){
		if(!preg_match("#squidlogs\.(.+?)\s+(.+)#", $line,$re)){continue;}
		$table=$re[1];
		$res=strtolower(trim($re[2]));
		echo "$table: $res\n";
	}

	
	
}	

function corrupted($nopid=false){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		sendEmail("corrupted(), function already executed $pid","\r\n");
		return;
	}
	
	
	

	$myisamchk=$unix->find_program("myisamchk");
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$myisamchk\"",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+\s+#", $line)){
			sendEmail("$line already executed",@implode("\r\n", $results));return;
		}
	}
	
	
	@file_put_contents($pidfile, getmypid());
	
	$q=new mysql_squid_builder();
	$array=$q->TABLES_STATUS_CORRUPTED();
	while (list ($tablename, $expl) = each ($array) ){
		if(preg_match("#is marked as crashed#", $expl)){
			$results=array();
			$t=time();
			if(is_file("/var/lib/mysql/squidlogs/$tablename.TMD")){
				@copy("/var/lib/mysql/squidlogs/$tablename.TMD", "/var/lib/mysql/squidlogs/$tablename.TMD-".time());
				@unlink("/var/lib/mysql/squidlogs/$tablename.TMD");
			}
			
			
			$cmd="$myisamchk -r /var/lib/mysql/squidlogs/$tablename.MYI";
			echo "$cmd\n";
			exec($cmd,$results);
			$took=$unix->distanceOfTimeInWords($t,time());
			sendEmail("$tablename repaired took: $took",@implode("\r\n", $results));
			continue;
		}
		
		sendEmail("$tablename ??? ",$expl);
		
	}
			
	
	
}


function LIST_TABLES_CATEGORIES(){
		$remove["category_radio"]=true;
		$remove["category_aa_list_blanche"]=true;
		$remove["category_radiotv"]=true;
		$remove["category_gambling"]=true;
		$remove["category_drogue"]=true;
		$remove["category_english_malware"]=true;
		$remove["category_forum"]=true;
		$remove["category_hobby_games"]=true;
		$remove["category_spywmare"]=true;
		$remove["category_phishtank"]=true;
		$remove["category_housing_reale_state_"]=true;
		$remove["category_wfa_sites"]=true;
		$remove["category_a_noir_acc_ouve"]=true;
		$remove["category_siti_approvati"]=true;
		$remove["category_reseau_social"]=true;
	
		if(isset($GLOBALS["LIST_TABLES_CATEGORIES"])){if($GLOBALS["VERBOSE"]){echo "return array\n";}return $GLOBALS["LIST_TABLES_CATEGORIES"];}
		$array=array();
		$q=new mysql_squid_builder();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%' ORDER BY table_name";
	
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	
	
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(isset($remove[$ligne["c"]])){continue;}
			$array[$ligne["c"]]=$ligne["c"];
		}
	
		$GLOBALS["LIST_TABLES_CATEGORIES"]=$array;
		return $array;
	
	}	
	
function catzall(){
	if(!$GLOBALS["NOPR"]){repair_tables_sources();}else{$cats=LIST_TABLES_CATEGORIES();}
	if(testsK9()<>"searchengines"){
		sendEmail("Cloud categorized failed, Bug detected...","testk9");
		return;
		
	}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timefile=$unix->file_time_min($pidfile);
		cloudlogs("Already executed pid $pid since $timefile minutes");
		squid_admin_mysql(1, "Already executed pid $pid since $timefile minutes.. aborting the process",__FUNCTION__,__FILE__,__LINE__,"categorize");
		return;
	}
	
	$t=time();
	$q=new mysql_squid_builder();
	$count1=$q->COUNT_CATEGORIES();
	$GLOBALS["CATEGORIZELOGS-COUNT"]=0;
	$GLOBALS["CATEGORIZELOGS-COUNTED"]=0;
	nocatz();
	catz();
	$count2=$q->COUNT_CATEGORIES();
	$AddedWebsites=$count2-$count1;
	$took=$unix->distanceOfTimeInWords($t,time());
	squid_admin_mysql(1, "Cloud categorized {took} $took {$GLOBALS["CATEGORIZELOGS-COUNTED"]} items scanned, $AddedWebsites new items categorized",__FUNCTION__,__FILE__,__LINE__,"categorize");
	if($GLOBALS["SENDMAIL"]){
		$mem=round(((memory_get_usage()/1024)/1000),2);
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		$text="\r\nK9:{$GLOBALS["K9COUNT"]} items\r\nArticaDB:{$GLOBALS["ARTICADB"]} items\r\nHeuristic:{$GLOBALS["HEURISTICS"]} items\r\nMemory used for this script:{$mem}M; System Load: $internal_load\r\n";			
		sendEmail("Cloud categorized took $took $AddedWebsites added {$GLOBALS["CATEGORIZELOGS-COUNTED"]} items scanned, {$GLOBALS["CATEGORIZELOGS-COUNT"]} items deleted",$text);
		
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	//shell_exec("$php5 /usr/share/artica-postfix/exec.squid.cloud.compile.php --backup-catz");
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.cloud.compile.php --others");
	
	
}


function testsK9(){

	$sock=new sockets();
	$GLOBALS["BlueCoatKey"]=$sock->GET_INFO("BlueCoatKey");
	$external_categorize=new external_categorize(null);
	$external_categorize->sitename="www.google.com";
	return $external_categorize->K9();

	
}

//*.userapi.com

function userapi(){
	
	$q=new mysql_squid_builder();
	$sql="SELECT *  FROM `category_science_computing` WHERE `pattern` LIKE '%.userapi.com'";
	$results=$q->QUERY_SQL($sql);
	$count=mysqli_num_rows($results);
	$c=0;
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$sitename=$ligne["pattern"];
		$uuid=$ligne["uuid"];
		$zmd5=$ligne["zmd5"];
		$zDate=$ligne["zDate"];
		if($zmd5==null){echo "Failed\n";}
		$newmd5=md5("webplugins$sitename");
		$q->QUERY_SQL("INSERT IGNORE INTO category_webplugins (zmd5,zDate,category,pattern,uuid) 
				VALUES('$newmd5','$zDate','webplugins','$sitename','$uuid')");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM category_science_computing WHERE zmd5='$zmd5'");
		$c++;
		echo "$c/$count\n";
	}
	
	
}

function cleanlocal(){
	echo "Open category_malware database\n";
	$q=new mysql_squid_builder();
	$sql="SELECT zmd5,pattern FROM category_malware";
	$results=$q->QUERY_SQL($sql);
	$count=mysqli_num_rows($results);	
	echo "$count items.\n";
	$c=0;
	$t=0;
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$sitename=$ligne["pattern"];	
		$zmd5=$ligne["zmd5"];
		$c++;
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT zmd5,pattern FROM category_publicite WHERE 'pattern'='$sitename'"));
				
		if($ligne2["pattern"]<>null){
			echo "$sitename -> category_publicite\n";
		}
		
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT zmd5,pattern FROM category_spyware WHERE 'pattern'='$sitename'"));
		
		if($ligne2["pattern"]<>null){
			echo "$sitename -> category_publicite\n";
		}		
		
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT zmd5,pattern FROM category_tracker WHERE 'pattern'='$sitename'"));
		
		if($ligne2["pattern"]<>null){
			echo "$sitename -> category_tracker\n";
		}

		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT zmd5,pattern FROM category_science_computing WHERE 'pattern'='$sitename'"));
		
		if($ligne2["pattern"]<>null){
			echo "$sitename -> category_science_computing\n";
		}		
		
		if($c>1000){
			$t=$t+$c;
			$c=0;
			echo "$t/$count (".($count-$t)."\n";
		}
	
	}
}





function testcatz($sitename){
	$www=$sitename;
	$q=new mysql_squid_builder();
	$GLOBALS["BIGDEBUG"]=true;
    $md5=md5($sitename);
	$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){deleteWebsite($md5,$www,__LINE__);return;}
		if(strpos($www, ",")>0){deleteWebsite($md5,$www,__LINE__);return;}
		if(strpos($www, " ")>0){deleteWebsite($md5,$www,__LINE__);return;}
		if(strpos($www, ":")>0){deleteWebsite($md5,$www,__LINE__);return;}
		if(strpos($www, "%")>0){deleteWebsite($md5,$www,__LINE__);return;}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){deleteWebsite($md5,$www,__LINE__);return;}
		$articacats=null;
		$articacats=trim($q->GET_CATEGORIES($www,true,false));
		echo "$www -> \"$articacats\"\n";	
	
}

function parseblogs(){
	include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
	if(function_exists("debug_mem")){debug_mem();}
	$f=file("/var/log/bluealpha.log");
	$bb=new external_categorize();
	$BC=$bb->UboxBlueaCoatAlter();
	foreach ( $f as $index=>$line ){
		if(!preg_match("#([A-Z0-9]+)\s+\"(.+?)\"#", $line,$re)){continue;}
		if(isset($BC[$re[1]])){continue;}
		$array[$re[1]][]=$re[2];
	}
	
	print_r($array);
}

function export_notcaz(){
	$q=new mysql_squid_builder();
	$sql="SELECT sitename FROM webtests ORDER BY sitename";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f[]=$ligne["sitename"];
	}
	@file_put_contents("/root/no-catz-export.txt", @implode("\n", $f));
	echo "/root/no-catz-export.txt\ndone\n";
}
	
function catz(){	
	
	$pidfile="/etc/artica-postfix/pids/CleanCloudNoCatz.pid";
	$timefile="/etc/artica-postfix/pids/CleanCloudNoCatz.time";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		cloudlogs("Already executed pid $pid");
		return;}
	$timeexec=$unix->file_time_min($timefile);
	if($timeexec<10){
		cloudlogs("$timefile Need 10Mn, {$timeexec}Mn");
		echo "$timefile Need 10Mn, {$timeexec}Mn";
		return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	
	if(testsK9()=="hacking"){
		sendEmail("Cloud categorized failed, hacking detected...","testk9");
		return;
	
	}	
	
	$q=new mysql_squid_builder();
	
	
	$array=$q->TABLES_STATUS_CORRUPTED();
	if(!is_array($array)){
		
		while (list ($tablename, $www) = each ($array) ){$f[]="$tablename $www";}
		sendEmail("Cloud categorized failed, Seems tables corrupted","Not an array");
		shell_exec("$php5 ".__FILE__." --corrupted >/dev/null 2>&1 &");
		return;
	}
	
	if(count($array)>0){
		while (list ($tablename, $www) = each ($array) ){$f[]="$tablename $www";}
		sendEmail("Cloud categorized failed, Seems tables corrupted",@implode("\r\n", $f));
		shell_exec("$php5 ".__FILE__." --corrupted >/dev/null 2>&1 &");
		return;
	}
	
	
	$tablescat=$q->LIST_TABLES_CATEGORIES();
	if(count($tablescat)<145){
		sendEmail("Error LIST_TABLES_CATEGORIES is under 145 !!", "aborting");
		return;
	}	
	
	$GLOBALS["OUTPUT_EXTCATZ"]=true;
	echo "Downloading pattern...\n";
	$curl=new ccurl("http://www.articatech.net/categories.manage.php?ExportCLCats=yes");
	$curl->NoHTTP_POST=true;
	$curl->ArticaProxyServerEnabled="no";
	$curl->interface="188.165.242.213";
	if(!$curl->get()){echo "http://www.articatech.net/categories.manage.php -> error: \n".$curl->error."\n";return;}
	if(!preg_match("#<CATZ>(.*)</CATZ>#is", $curl->data,$rz)){echo "No preg_match\n$curl->data\n";return;}
	$dd=$rz[1];
	echo (strlen($dd)/1024)." Ko lenth\n";
	$datas=unserialize(base64_decode($dd));
	if(!is_array($datas)){echo "Not an array, die\n";echo $dd."\n";return;}

	$q=new mysql_squid_builder();
	$GLOBALS["CATEGORIZELOGS-COUNTED"]=$GLOBALS["CATEGORIZELOGS-COUNTED"]+count($datas);
	$max=count($datas);
	$c=0;
	echo "Starting Loop... ". count($datas)." items...\n";
	cloudlogs("Starting Loop... ". count($datas)." items...");
	while (list ($md5, $www) = each ($datas) ){
		$c++;
		if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos("$www ", ",")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, " ")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos(" $www", ":")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos(" $www", "%")>0){deleteWebsite($md5,$www,__LINE__);continue;}	
		if(strpos(" $www", "#")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){deleteWebsite($md5,$www,__LINE__);continue;}	
		$articacats=null;
		if($GLOBALS["CATZWHY"]=="K9"){
			
			$sleep=rand(1, 5);
			cloudlogs("Sleep {$sleep}mn");
			sleep($sleep);
		}
		$articacats=trim($q->GET_CATEGORIES($www,true,false));

		if($articacats<>null){
			
			echo "$c/$max [$www] \"$articacats\" {$GLOBALS["CATZWHY"]}\n";
			cloudlogs("$c/$max [$www] \"$articacats\" {$GLOBALS["CATZWHY"]}");
			$MEM[$www]=$articacats;
			deleteWebsite($md5,$www,__LINE__,$articacats);
			continue;
		}
		cloudlogs("$c/$max [$www] \"$articacats\" `SKIP` by line [".__LINE__."]");
		not_categorized_add($www,gethostbyaddr($www));
		deleteWebsite($md5,$www,__LINE__,$articacats);

		echo "$c/$max [$www] \"$articacats\" `SKIP` by line [".__LINE__."]\n";
		
	}
	
}

function sendEmail($subject,$content){
$unix=new unix();	
$from="robot@".$unix->hostname_g();
$header .= "From: ARTICA <$from>\r\n";
$header .= 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8';
$header .= "Reply-To: $from\r\n";
$header .= 'X-Mailer: PHP/' . phpversion()."\r\n";
$mailto=@file_get_contents("/root/artica-notifs.txt");
if($mailto==null){return;}
@mail("$mailto",$subject,$content,$header);
}

// 1636b7346f2e261c5b21abfcaef45a69
	
	
function nocatz($router=null){
	
	
	$pidfile="/etc/artica-postfix/pids/CleanCloudCatz.pid";
	$timefile="/etc/artica-postfix/pids/CleanCloudCatz.time";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
	$timeexec=$unix->file_time_min($timefile);
	if(!$GLOBALS["FORCE"]){
		if($timeexec<10){
			cloudlogs("{$timeexec} - require 10Mn");
			return;}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	import_translated();
	
	if(testsK9()=="hacking"){
		sendEmail("Cloud categorized failed, hacking detected...","testk9");
		return;
	
	}	
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.checkscatz.php --moves-schedules >/dev/null 2>&1 &");
	
	
	$q=new mysql_squid_builder();
	$array=$q->TABLES_STATUS_CORRUPTED();
	if(!is_array($array)){
		sendEmail("Cloud categorized failed, Seems tables corrupted",__FUNCTION__);
		shell_exec("$php5 ".__FILE__." --corrupted >/dev/null 2>&1 &");
		return;
	}
	
	if(count($array)>0){
		sendEmail("Cloud categorized failed, Seems tables corrupted",__FUNCTION__);
		shell_exec("$php5 ".__FILE__." --corrupted >/dev/null 2>&1 &");
		return;		
	}
	
	
	
	
	$tablescat=$q->LIST_TABLES_CATEGORIES();
	if(count($tablescat)<145){
		sendEmail("Error LIST_TABLES_CATEGORIES is under 145 !!", "aborting");
		return;
	}
	
	
	$q->CheckTables();
	if($router<>null){
		$curl=new ccurl("http://www.articatech.net/categories.manage.php?ExportCLNoCats=$router");
	}else{
		$curl=new ccurl("http://www.articatech.net/categories.manage.php?ExportCLNoCats=yes");
	}
	$curl->ArticaProxyServerEnabled="no";
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "http://www.articatech.net/categories.manage.php -> error: \n".$curl->error."\n";return;}
	if(!preg_match("#<CATZ>(.*)</CATZ>#is", $curl->data,$rz)){echo "No preg_match\n$curl->data\n";return;}
	$dd=$rz[1];
	echo (strlen($dd)/1024)." Ko lenth\n";
	$datas=unserialize(base64_decode($dd));
	if(!is_array($datas)){echo "Not an array, die\n";echo $dd."\n";return;}
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$q=new mysql_squid_builder();
	$GLOBALS["CATEGORIZELOGS-COUNTED"]=$GLOBALS["CATEGORIZELOGS-COUNTED"]+count($datas);
	$max=count($datas);
	$c=0;	
	$GoodCatz=0;
	while (list ($md5, $www) = each ($datas) ){	
		$sleep=0;
		if($GLOBALS["CATZWHY"]=="K9"){
			$sleep=rand(1, 5);
			sleep($sleep);
		}
		$c++;
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){
			$hostname=gethostbyaddr($www);
			
			if($hostname<>$www){
				$articacats=trim($q->GET_CATEGORIES($hostname,true,false));
				if($articacats<>null){
					cloudlogs("$c/$max [$www] \"$articacats\" {$GLOBALS["CATZWHY"]}");
					echo "[$mypid] $c/$max $www -> $hostname {$GLOBALS["CATZWHY"]}:[$articacats] by line [".__LINE__."]\n";
					deleteWebsiteNocatz($md5,$www,__LINE__,$articacats,"[$mypid] $c/$max ");
					continue;
				}
			}
		}		
			
		if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, ",")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, ">")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, "<")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, " ")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, ":")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, "%")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, "#")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}	
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}	
		$articacats=null;
		
		$articacats=trim($q->GET_CATEGORIES($www,true,false));
		//if(count($GLOBALS["CATEGORIZELOGS"])>0){
			//while (list ($a, $b) = each ($GLOBALS["CATEGORIZELOGS"]) ){echo "$md5 -> `LOG` \"$b\" by line $line [".__LINE__."]\n";}
		//}
		if($articacats<>null){
			$MEM[$www]=$articacats;
			cloudlogs("$c/$max [$www] \"$articacats\" {$GLOBALS["CATZWHY"]}");
			deleteWebsiteNocatz($md5,$www,__LINE__,$articacats,"[$mypid] $c/$max ");
			continue;
		}
		
		$ipaddr=gethostbyname($www);
		if($ipaddr==$www){
			cloudlogs("`categorize_reaffected` \"$www\"");
			
			$GLOBALS["CATEGORIZELOGS-COUNT"]++;
			deleteWebsiteNocatz($md5,$www,__LINE__,"reaffected","[$mypid]  $c/$max ");
			continue;			
		}
		
		
		echo "[$mypid]  $c/$max $md5 -> `SKIP` \"$www\" ($ipaddr) by line [".__LINE__."] sleep $sleep\n";
		not_categorized_add($www,$ipaddr);
		deleteWebsiteNocatz($md5,$www,__LINE__,null,"$c/$max sleep $sleep ");
		
	}	
	
	if($max<500){
		catz();
	}
	echo "Success {$GLOBALS["CATEGORIZELOGS-COUNT"]} categorized websites\n";
	
}
function import_translated(){

	$q=new mysql_squid_builder();

	$MAINZ=unserialize(@file_get_contents("/root/translated"));
	$translated_done=unserialize(@file_get_contents("/root/translated_done"));
	$max=count($MAINZ);
	$gg=new generic_categorize();
	$i=1;
	while (list ($www, $category) = each ($MAINZ)){
		$prefix="$i/$max $www ";
		$i++;
		if(isset($translated_done[$www])){echo "\n";continue;}
		$category_artica=$gg->GetCategories($www);
		if($category_artica<>null){
			cloudlogs( "$prefix -> ARTICA $category_artica");
			$q->categorize($www, $category_artica);
			$translated_done[$www]=true;
			@file_put_contents("/root/translated_done", serialize($translated_done));
			continue;
		}

		$category_artica=$q->GET_CATEGORIES($www,true,true,true,true);
		if($category_artica<>null){
			cloudlogs( "$prefix -> ARTICA $category<>$category_artica");
			$translated_done[$www]=true;
			@file_put_contents("/root/translated_done", serialize($translated_done));
			continue;
		}

		cloudlogs( "$prefix -> $category NEW");
		$q->categorize($www, $category);
		$translated_done[$www]=true;
		@file_put_contents("/root/translated_done", serialize($translated_done));

	}


}


function not_categorized_add($www,$ipaddr){
	$country=null;
	if(function_exists("geoip_record_by_name")){
		$record = geoip_record_by_name($ipaddr);
		if ($record) {$country=$record["country_name"];$city=$record["city"];}
	}
	
	$q=new mysql_squid_builder();
	$family=$q->GetFamilySites($www);
	$country=mysql_escape_string2($country);
	$date=date('Y-m-d H:i:s');
	cloudlogs("`not_categorized_add` \"$www\" $ipaddr $country");
	$sql="INSERT IGNORE INTO webtests (`sitename`,`family`,`Country`,`zDate`,`ipaddr`) VALUES ('$www','$family','$country','$date','$ipaddr')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(preg_match("#Country#", $q->mysql_error)){
			$q->QUERY_SQL("ALTER TABLE `webtests` ADD `Country`  VARCHAR( 50 ) NOT NULL ,ADD INDEX ( `Country` )");
			$q->QUERY_SQL($sql);
			
		}
	}
	if(!$q->ok){
	echo getmypid()." Error $q->mysql_error\n";}
	
}



function Download_table($tablename){
	// category_science_computing
	//category_dynamic
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql_squid_builder();	
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	$indexuri="http://www.articatech.net/webfilters-instant.php?ChecksTable=yes&table=$tablename";
	$data_temp_file=$unix->FILE_TEMP();
	
	
	$catz=$q->tablename_tocat($tablename);
	
	if($catz==null){
		echo "$tablename, no associated catz\n";
		return;
	}	
	$curl=new ccurl($indexuri);
	if(!$curl->GetFile($data_temp_file)){echo "Fatal error downloading $data_temp_file\n";return;}
	$array=unserialize(base64_decode(@file_get_contents($data_temp_file)));
    if(!is_array($array)){$array=array();}
	
	if(!is_array($array)){
		echo $curl->data;
		return;
	}
	
	echo "Checking ".count($array)." rows with uuid=$uuid\n";
	$prefix="INSERT IGNORE INTO $tablename (zmd5,zDate,category,pattern,uuid) VALUES";
	
	$maxcount=count($array);
	$c=0;
	$f=array();
	while (list ($md5, $content) = each ($array) ){	
		$uuidT=$content["uuid"];
		$c++;
		$sitename=$content["pattern"];
		if($uuidT==$uuid){
			echo "$c/$maxcount) Deleting $tablename/$md5\n";
			deleteWebsiteTable($md5,$tablename);
			continue;
		}
		if($uuidT==null){$uuidT=$uuid;}
		echo count($f)." $c/$maxcount) Adding \"$sitename\"\n";
		$zDate=$content["zDate"];
		$f[]="('$md5','$zDate','$catz','$sitename','$uuidT',1)";
		if(count($f)>500){
			$sql="INSERT IGNORE INTO `$tablename` (zmd5,zDate,category,pattern,uuid,enabled) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";
				$errorfile="/var/log/".time().".mysqlerr.sql";
				echo "Save $errorfile\n";
				@file_put_contents($errorfile, $sql);
				return;
			}
			
			$f=array();
		}
		deleteWebsiteTable($md5,$tablename);
		
		
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO `$tablename` (zmd5,zDate,category,pattern,uuid,enabled) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";
			$errorfile="/var/log/".time().".mysqlerr.sql";
			echo "Save $errorfile\n";
			@file_put_contents($errorfile, $sql);
			return;
		}
			
			$f=array();
	}
	
}
	
function deleteWebsiteTable($md5,$table){	
	cloudlogs("Delete $md5 from $table");
	$indexuri="http://www.articatech.net/webfilters-instant.php?DeleTable=yes&table=$table&md5=$md5";
	$curl=new ccurl($indexuri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "Fatal error downloading $curl->error\n";return;}
	if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
		cloudlogs("Fatal error downloading {$re[1]}");
		echo "Fatal error downloading {$re[1]}\n";
	}
	
}

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
	

function deleteWebsiteNocatz($md5,$www,$line=0,$cat=null,$output=false){
	
	
	if($cat<>null){
		echo "$output$md5 -> `DELETE` \"$www\" by line $line {$GLOBALS["CATZWHY"]}:[$cat]\n";
		cloudlogs("`DELETE` \"$www\" by line $line {$GLOBALS["CATZWHY"]}:[$cat]");
	}
	$curl=new ccurl("http://www.articatech.net/categories.manage.php?killNoCatz=$md5");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo $curl->error."\n";return;}
	if(preg_match("#<ERROR>(.*?)</ERROR>#", $curl->data,$re)){
		cloudlogs("`ERROR` {{$re[1]}}");
		echo "{$re[1]}\n";
	}
}	
	
function deleteWebsite($md5,$www,$line=0,$cat=null){
	$www=strtolower($www);
	echo "$md5 -> `DELETE` \"$www\" (by {{$GLOBALS["CATZWHY"]}) line $line [$cat]\n";
	$curl=new ccurl("http://www.articatech.net/categories.manage.php?kill=$md5");
	$curl->NoHTTP_POST=true;
	writelogs("deleteWebsite: $md5",__FUNCTION__,__FILE__,__LINE__);
	if(!$curl->get()){echo $curl->error."\n";return;}
}

function testsbright(){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_EXTERN"]=true;
	$tt=new external_categorize("www.google.com");
	$tt->bright();
	
}

function webtests(){
	$curl=new ccurl("https://188.165.242.213:9000/squid.stats.listener.php?webtests=yes");
	$curl->NoHTTP_POST=true;
	$curl->get();
	$f=explode("\n", $curl->data);
	while (list ($index, $sitename) = each ($f) ){
		if($GLOBALS["VERBOSE"]){echo "$sitename";}
		if(trim($sitename)==null){continue;}
		$tt=new external_categorize($sitename);
		$category=$tt->bright();
		cloudlogs("$sitename -> $category");
		if($GLOBALS["VERBOSE"]){echo " -> $category\n";}
		if($category<>null){webtests_save($sitename,$category);}
	}
	
	
}

function webtests_save($domain,$category){
	$logFile="/var/log/webtests.log";
	$time=date("Y-m-d H:i:s");
	$PID=getmypid();
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}

	$f = @fopen($logFile, 'a');
	@fwrite($f, "$domain;$category\n");
	@fclose($f);
}
