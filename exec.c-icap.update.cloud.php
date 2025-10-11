<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


xstart();

function xstart(){
	$users=new usersMenus();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.c-icap.update.cloud.php.xstart.pid";
	$pidTime="/etc/artica-postfix/pids/exec.c-icap.update.cloud.php.xstart.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already executed...\n";
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	if($unix->ServerRunSince()<10){return;}
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	
	if(!$GLOBALS["FORCE"]){
		if($pidTimeINT<240){
			echo "To short time to execute the process $pidTime = {$pidTimeINT}Mn < 240\n";
			return;
		}
	}
	
	

	$compilator=$unix->find_program("c-icap-mods-sguardDB");
	if(!is_file($compilator)){
		echo "c-icap-mods-sguardDB not such binary\n";
		return;
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	if(!is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	
	
	
	
	$CloudIndex="/etc/artica-postfix/settings/Daemons/CicapArticatechDatabases";
	$CurrentIndex="/etc/artica-postfix/settings/Daemons/CicapLocalDatabases";
	$curl=new ccurl("http://articatech.net/webfilters-databases/cicapindex.txt");
	if(!$curl->GetFile($CloudIndex)){
		squid_admin_mysql(1, "Unable to download index file cicapindex", $curl->error,__FILE__,__LINE__);
		return false;
	}
	$LICENSE=$users->CORP_LICENSE;
	if(!$LICENSE){remove();exit;}
	
	$trans["category_malware"]="malware";
	$trans["category_suspicious"]="suspicious";
	$trans["category_phishing"]="phishing";
	$trans["category_spyware"]="spyware";
	
	$rm=$unix->find_program("rm");
	$CLOUD_ARRAY=unserialize(base64_decode(@file_get_contents($CloudIndex)));
	$LOCAL_ARRAY=unserialize(base64_decode(@file_get_contents($CurrentIndex)));
	$DIRECTORY_TEMP=$unix->TEMP_DIR();
	$RELOAD=false;
	
	while (list ($category_table, $HASH) = each ($CLOUD_ARRAY) ){
		$t=time();
		$TIME=$HASH["TIME"];
		$COUNT=$HASH["COUNT"];
		$FILENAME=$HASH["FILENAME"];
		$FILENAME_MD5=$HASH["FILENAME_MD5"];
		$FILENAME_SIZE=$HASH["FILENAME_SIZE"];
		$category_folder="cicap_{$category_table}";
		$categoryname=$trans[$category_table];
		$category_path="/var/lib/squidguard/$category_folder";
		
		if(!is_file("$category_path/domains.db")){
			echo "Checking: $category_path/domains.db no such file\n";
			unset($LOCAL_ARRAY[$category_table]);
		}
		
		if($FILENAME_MD5==$LOCAL_ARRAY[$category_table]["FILENAME_MD5"]){
			echo "Checking: $FILENAME no new update...\n";
			continue;
		}
		echo "Checking: $FILENAME $FILENAME_MD5 ! === {$LOCAL_ARRAY[$category_table]["FILENAME_MD5"]}\n";
		
		$TEMP_FILE="$DIRECTORY_TEMP/$FILENAME";
		$FILENAME_SIZE_T=FormatBytes($FILENAME_SIZE/1024,true);
		$URL="http://articatech.net/webfilters-databases/$FILENAME";
		echo "Downloading $URL to $TEMP_FILE ( $FILENAME_SIZE_T )\n";
		$curl=new ccurl("http://articatech.net/webfilters-databases/$FILENAME");
		$curl->Timeout=2400;
		$curl->WriteProgress=true;
		$curl->ProgressFunction="download_progress";
		$GLOBALS["previousProgress"]=0;
		$GLOBALS["WriteProgress_filename"]=$FILENAME;

		if(!$curl->GetFile($TEMP_FILE)){
			@unlink($TEMP_FILE);
			echo "Downloading $FILENAME failed\n";
			echo "Error $curl->error\n";
			continue;
		}
		
		$md5=md5_file($TEMP_FILE);
		if($md5<>$FILENAME_MD5){
			echo "Downloading $FILENAME failed (corrupted)\n";
			@unlink($TEMP_FILE);
			continue;
		}
		
		$domainsSource="$category_path/domains";
		if(!is_dir($category_path)){@mkdir($category_path,0755,true);}
		
		if(is_file($domainsSource)){@unlink($domainsSource);}
		
		
		if(is_dir($category_path)){
			echo "Removing $category_path\n";
			system("$rm -rfv $category_path/*");
		}
		
		echo "Extracting $TEMP_FILE\n";
		if(!$unix->uncompress($TEMP_FILE, "$domainsSource")){
			unset($LOCAL_ARRAY[$category_table]);
			echo "Failed to uncompress $TEMP_FILE\n";
			@unlink($TEMP_FILE);
			continue;
			
		}
		@unlink($TEMP_FILE);
		
		
		if(!is_file($domainsSource)){
			unset($LOCAL_ARRAY[$category_table]);
			echo "Extracting $TEMP_FILE failed\n";
			continue;
		}
		$size=@filesize($domainsSource);
		
		echo "Extracting $TEMP_FILE ( ". FormatBytes($size/1024,true)." ) success\n";
		echo "Compiling database $category_table\n";
		if(!is_file("$category_path/urls")){@touch("$category_path/urls");}
		system("$compilator -C -db $category_path");
		if(!is_file("$category_path/domains.db")){
			echo "Compiling $category_path failed\n";
			unset($LOCAL_ARRAY[$category_table]);
			@unlink($domainsSource);
			continue;
		}
		@unlink($domainsSource);
		$took=distanceOfTimeInWords($t,time());
		squid_admin_mysql(2, "Success updating Enterprise $categoryname database in $took", null,__FILE__,__LINE__);
		
		$RELOAD=true;
		$LOCAL_ARRAY[$category_table]["FILENAME_MD5"]=$FILENAME_MD5;
		$LOCAL_ARRAY[$category_table]["FILENAME_SIZE"]=$FILENAME_MD5;
		$LOCAL_ARRAY[$category_table]["FILENAME_SIZE"]=$FILENAME_SIZE;
		$LOCAL_ARRAY[$category_table]["COUNT"]=$COUNT;
		$LOCAL_ARRAY[$category_table]["TIME"]=$TIME;
		
	}
	
	echo "Saving Index file\n";
	@file_put_contents($CurrentIndex,base64_encode(serialize($LOCAL_ARRAY)));
	
	if($RELOAD){
		echo "Reloading Service\n";
		squid_admin_mysql(2, "Reloading ICAP service after updating databases", null,__FILE__,__LINE__);
        $unix->CICAP_SERVICE_EVENTS("Reconfigure ICAP service",__FILE__,__LINE__);
		shell_exec("/etc/init.d/c-icap reconfigure");
		
	}
	
	
	
	
}
	
function remove(){
	$unix=new unix();
	$trans["cicap_malware"]="malware";
	$trans["cicap_suspicious"]="suspicious";
	$trans["cicap_phishing"]="phishing";
	$trans["cicap_spyware"]="spyware";
	$rm=$unix->find_program("rm");
	$RELOAD=false;
	while (list ($category_table, $category) = each ($trans) ){
		if(is_file("/var/lib/squidguard/$category_table/domains.db")){
			shell_exec("$rm -rf /var/lib/squidguard/$category_table/*");
			$RELOAD=true;
		}
		
	}
	
	if($RELOAD){
		squid_admin_mysql(1, "Reloading Web-filtering service after purging database ( License error )", null,__FILE__,__LINE__);
        $unix->CICAP_SERVICE_EVENTS("Reconfigure ICAP service",__FILE__,__LINE__);
		system("/etc/init.d/c-icap reconfigure");
	}
	
	
}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}
	 
	if ( $progress > $GLOBALS["previousProgress"]){
		echo "Downloading {$GLOBALS["WriteProgress_filename"]}: ". $progress."%, please wait...\n";
		$GLOBALS["previousProgress"]=$progress;
	}
}	
?>