<?php
$GLOBALS["FORCE"]=false;
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');


if(isset($argv[1])){
    if($argv[1]=="--compile"){compile();exit;}
}

xtsart();

function build_progress($pourc,$text){
	$echotext=$text;

	$cachefile=PROGRESS_DIR."/ipset.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function xtsart(){
	$unix=new unix();
	
	
	$cacheTemp="/etc/artica-postfix/pids/exec.postfix.ipsets.php.time";
	$unix=new unix();
	$ztime=$unix->file_time_min($cacheTemp);
	if(!$GLOBALS["FORCE"]){
		if($ztime<15){
			echo "Please, restart later (15mn) - current = {$ztime}mn\n";
			return ;
		}
	}
	@unlink($cacheTemp);
	@file_put_contents($cacheTemp, time());
	
	
	$q=new postgres_sql();
	$ipClass=new IP();
	$q->SMTP_TABLES();
	if(!$unix->CORP_LICENSE()){
		build_progress(5, "{removing}....");
		$q->QUERY_SQL("DELETE FROM smtp_ipset WHERE automatic=1");
		build_progress(100, "{success}....");
		return;
	}
	
	$PostFixAutopIpsets=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsets");
	$PostFixAutopIpsetsDB=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsetsDB"));
	if($PostFixAutopIpsets==0){
		build_progress(5, "{removing}....");
		$q->QUERY_SQL("DELETE FROM smtp_ipset WHERE automatic=1");
		build_progress(100, "{success}....");
		return;
	}
	
	$progress=20;
	$PATTERN[1]="https://www.spamhaus.org/drop/drop.txt";
	$PATTERN[2]="https://www.spamhaus.org/drop/edrop.txt";
	$PATTERN[3]="http://lists.blocklist.de/lists/mail.txt";
	$PATTERN[4]="http://lists.blocklist.de/lists/imap.txt";
	$PATTERN[5]="https://reputation.alienvault.com/reputation.generic";
	$prefix="INSERT INTO smtp_ipset (pattern,zdate,patype,automatic,enabled) VALUES ";
	build_progress(30, "{downloading} ". count($PATTERN)." databases....");
	$CHANGES=false;
	
	foreach ($PATTERN as $index=>$url){
		if(!isset($PostFixAutopIpsetsDB[$index]["MD5"])){$PostFixAutopIpsetsDB[$index]["MD5"]=null;}
		$OLD_MD5=$PostFixAutopIpsetsDB[$index]["MD5"];
		
		$TMPFILE=$unix->FILE_TEMP();
		$curl=new ccurl($url);
		$t=array();
		build_progress($progress++, "{downloading} database ($index)....");
		if($curl->GetFile($TMPFILE)){
			$md5File=md5_file($TMPFILE);
			if($md5File==$OLD_MD5){echo "database ($index) no changes, skip\n";continue;}
			$q->QUERY_SQL("DELETE FROM smtp_ipset WHERE patype=$index and enabled=1");
			$f=explode("\n",@file_get_contents($TMPFILE));
			$PostFixAutopIpsetsDB[$index]["MD5"]=$md5File;
			$CHANGES=true;
			while (list ($a,$line) = each ($f) ){
				$line=trim(strtolower($line));
				if($line==null){continue;}
				$date=date("Y-m-d H:i:s");
				if(preg_match("#([0-9\.\/]+)#", $line,$re)){
					if(!$ipClass->IsACDIROrIsValid($re[1])){echo "Exclude : {$re[1]}\n";continue;}
					$t[]="('{$re[1]}','$date',$index,1,1)";}
			
				if(count($t)>1000){
					$q->QUERY_SQL($prefix .@implode(",", $t). " ON CONFLICT DO NOTHING");
					if(!$q->ok){echo $q->mysql_error;die();}
					$t=array();
				}
				
				
			}
			
			if(count($t)>0){
				$q->QUERY_SQL($prefix .@implode(",", $t)." ON CONFLICT DO NOTHING");
				if(!$q->ok){echo $q->mysql_error;die();}
				$t=array();
			}
				
		}
		
		build_progress($progress++, "{injecting} database ($index) done....");
		@unlink($TMPFILE);
	
	}
	
	

	if($CHANGES){
		build_progress($progress++, "{analyze} {table}...");
		echo "Analyze table...\n";
		$q->QUERY_SQL("ANALYZE TABLE smtp_ipset");
		echo "VACUUM table...\n";
		build_progress($progress++, "VACUUM {table}...");
		$q->QUERY_SQL("VACUUM smtp_ipset");
		$PostFixAutopIpsetsDB["TIME"]=time();
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostFixAutopIpsetsDB",serialize($PostFixAutopIpsetsDB));
		echo "Database as changed, build it....\n";
		if(!BuildIPSet($progress++)){build_progress(110, "{failed}");return;}
		system("/etc/init.d/firehol reconfigure");
		build_progress($progress++, "{done}");
		
		
	}
	
	
	build_progress(100, "{done}");
	
	
}

function compile(){
	build_progress(10, "{analyze} {table}...");
	if(!BuildIPSet(50)){build_progress(110, "{failed}");return;}
	system("/etc/init.d/firehol reconfigure");
	build_progress(90, "{done}");
	build_progress(100, "{done}");
	
}

function BuildIPSet($progress){
	$pg=new postgres_sql();
	$PostFixAutopIpsetsDB=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsetsDB"));
	$ipClass=new IP();
	@unlink("/home/artica/firewall/SMTPDeny.txt");
	@unlink("/home/artica/firewall/SMTPDenyCDIR.txt");
	
	if(!is_dir("/home/artica/firewall")){@mkdir("/home/artica/firewall",0755,true);}
	$fileip = @fopen("/home/artica/firewall/SMTPDeny.txt", "w");
	$filecdir=@fopen("/home/artica/firewall/SMTPDenyCDIR.txt", "w");
	
	$sql="SELECT pattern from smtp_ipset WHERE enabled=1";
	$results=$pg->QUERY_SQL($sql);
	if(!$pg->ok){echo "$pg->mysql_error\n";return false;}
	$max=pg_num_rows($results);
	
	$d=0;$c=0;$d=0;
	while ($ligne = pg_fetch_assoc($results)) {
		$ipaddr=trim($ligne["pattern"]);
		if($ipaddr==null){continue;}
		
		$d++;
		
		if($d>1000){
			$d=0;
			$prc=($c/$max)*100;
			echo round($prc,2)."% $c / $max\n";
			build_progress($progress,"{compile_database}".round($prc,2)."%");
		}
		
		if($ipClass->IsACDIR($ipaddr)){
			$c++;
			fwrite($filecdir, "$ipaddr\n");
			continue;
		}
		$c++;
		fwrite($fileip, "$ipaddr\n");

	}
	@fclose($fileip);
	@fclose($filecdir);
	$PostFixAutopIpsetsDB["ITEMS"]=$c;
	echo "$c items compiled\n";
	build_progress($progress++,"{compile_database} {success}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostFixAutopIpsetsDB",serialize($PostFixAutopIpsetsDB));
	return true;
}

