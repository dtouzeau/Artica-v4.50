<?php
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__) .'/ressources/class.autofs.inc');
$GLOBALS["INDEXED"]=0;
$GLOBALS["ONLYF"]=false;
$GLOBALS["SKIPPED"]=0;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--onlyfiles#",implode(" ",$argv),$re)){$GLOBALS["ONLYF"]=$re[1];}
$cmdlines=@implode(" ", $argv);
writelogs("Executed `$cmdlines`","MAIN",__FILE__,__LINE__);
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if($argv[1]=="--mysql-dirs"){Scan_mysql_dirs();exit();}
if($argv[1]=="--shared"){shared();exit();}
if($argv[1]=="--homes"){homes();exit();}


if(systemMaxOverloaded()){
	build_progress(110,"{overloaded}");
	build_progress_single(110,"{overloaded}");
	writelogs("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
	exit();
}
if($argv[1]=="--single"){single($argv[2]);exit();}
ScanQueue();
exit();

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/omindex.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_single($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/omindex.single.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function filelist2omega(){
	
	$f[]="nid: index field=nid";
	$f[]="url : index field=id field=url";
	$f[]="name : weight=3 indexnopos hash field=name";
	$f[]="path : indexnopos field=path";
	$f[]="format : index field=format";
	$f[]="size : index field=size";
	$f[]="modtime : index field=modtime\n";
	
	@file_put_contents("/tmp/filelist2omega.script", @implode("\n", $f));
}

function single($ID){
	$unix=new unix();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".$ID.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	if($unix->process_exists($pid)){
		build_progress_single(110,"Already instance executed pid:$pid");
		writelogs("Already instance executed pid:$pid",__FUNCTION__,__FILE__,__LINE__);
		exit();
	}
	
	if(intval($ID)==0){
		build_progress_single(110,"Invalid ID...");
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	$q=new mysql();
	$sql="SELECT * FROM xapian_folders WHERE ID=$ID";
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM xapian_folders WHERE ID=$ID","artica_backup"));
	if(!$q->ok){build_progress_single(110,"MySQL Error...");echo $q->mysql_error."\n";exit();}
	$mountpoint="/home/xapian/mounts";
	$mount=new mount();
	if(!is_dir($mountpoint)){@mkdir($mountpoint,0755,true);}
	if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
	if($mount->ismounted($mountpoint)){echo "Failed to unmount $mountpoint\n";build_progress_single(110,"$mountpoint {failed}");return;}
	$omindex=$unix->find_program("omindex");
	$omindexlist=$unix->find_program("omindex-list");
	$chmod=$unix->find_program("chmod");
	$nohup=$unix->find_program("nohup");
	$ps=$unix->find_program("ps");
	$find=$unix->find_program("find");
	$awk=$unix->find_program("awk");
	$scriptindex=$unix->find_program("scriptindex");
	
	if(!is_file($omindex)){echo "omindex no such binary\n";build_progress_single(110,"omindex no such binary");return;}
	if(!is_file($omindexlist)){echo "omindex-list no such binary\n";build_progress_single(110,"omindex no such binary");return;}
	if(!is_file($scriptindex)){echo "scriptindex no such binary\n";build_progress_single(110,"scriptindex no such binary");return;}
	$c=15;
	$ztype=$ligne["ztype"];
	$ID=$ligne["ID"];
	
	echo "Type................: $ztype\n";
	echo "ID..................: $ID\n";
	echo "sfolder.............: {$ligne["sfolder"]}\n";
	echo "tfolder.............: {$ligne["tfolder"]}\n";
	if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
	build_progress_single($c++,"$ztype (ID $ID)");
	
	if($ztype=="smb"){
		$resource="\\\\{$ligne["hostname"]}\\{$ligne["sfolder"]}\\{$ligne["tfolder"]}";
		echo "Resource............: $resource\n";
		if($ligne["workgroup"]<>null){$ligne["hostname"]="{$ligne["hostname"]}@{$ligne["workgroup"]}";}
		build_progress_single($c++,"$ID {$ligne["ressourcename"]} {mounting}");

		if(!$mount->smb_mount($mountpoint, $ligne["hostname"], $ligne["username"], $ligne["password"], $ligne["sfolder"])){
			SetError($ID,"Mount error (".__LINE__.")\n".@implode("\n", $GLOBALS["MOUNT_EVENTS"]));
			build_progress_single(110,"{failed} {$ligne["ressourcename"]}");
			return;
		}
		
	}
		if(!$mount->ismounted($mountpoint)){SetError($ID,"$mountpoint not mounted why ???");return;;}
	
		$ScannerPath="$mountpoint/{$ligne["tfolder"]}";
		$ScannerPath=str_replace("\\", "/", $ScannerPath);
		echo "Base Path...........: $ScannerPath\n";
				
		if(!is_dir($ScannerPath)){
			SetError($ID,"$ScannerPath no such directory");
			if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
			build_progress_single(110,"{failed} {$ligne["ressourcename"]}");
			return;
		}
		
		
	$cmdline=buicmdline($ligne,$ScannerPath,$omindex);
	echo $cmdline."\n";
	build_progress_single($c++,"{selected_resource} {$ligne["ressourcename"]} {please_wait}");
	$SartOn=time();
	if(!$GLOBALS["ONLYF"]){shell_exec("$nohup $cmdline &");}
	sleep(1);
	$PID=$unix->PIDOF($omindex);
	while ($unix->process_exists($PID)) {
		$psa=exec("$ps auxhq $PID 2>&1");
		writelogs("$PID -> $psa",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#(.+?)\s+[0-9]+\s+([0-9\.]+)\s+([0-9\.]+)#", $psa,$re)){
			if($c>90){$c=90;}
			build_progress_single($c++,"{$ligne["ressourcename"]}: CPU {$re[2]}%, Memory {$re[3]}%");
		}
		sleep(10);
		$PID=$unix->PIDOF($omindex);
		if(!$unix->process_exists($PID)){break;}
	}
	
	if($c>90){$c=90;}build_progress($c++,"Index all files...");
	filelist2omega();
	$filescmd="$find \"$ScannerPath\" -type f -printf 'nid={$ligne["ID"]}\\nurl=%p\\npath=%h\\nname=%f\\nsize=%s\\nmodtime=%A@\\n\\n' | $awk '{if ($1 ~ /^path=/) gsub(/\//, \"\\n=\"); if ($1 ~ /^name=/) sub(/\./,\"\\nformat=\"); print}'| $scriptindex /home/omindex-databases/{$ligne["ID"]} /tmp/filelist2omega.script";
	echo "$filescmd\n";
	system($filescmd);
	
	$distance=distanceOfTimeInWords($SartOn,time());
	build_progress_single($c++,"$distance (ID $ID)");
	shell_exec("$omindexlist /home/omindex-databases/{$ID} >/home/omindex-databases/{$ligne["ID"]}.count 2>&1");
	$NumberOfFiles=$unix->COUNT_LINES_OF_FILE("/home/omindex-databases/{$ligne["ID"]}.count");
	build_progress_single($c++,"Files $NumberOfFiles (ID $ID)");
	shell_exec("$chmod -R 0755 /home/omindex-databases/{$ligne["ID"]}");
	$SizeOfIndexDB=$unix->DIRSIZE_BYTES_NOCACHE("/home/omindex-databases/{$ligne["ID"]}");
	$SizeOfIndexDB=round($SizeOfIndexDB/1024);
	$ResultsArray=AnalyzeLogFile("/home/omindex-databases/{$ligne["ID"]}.log");
	$Indexed=$ResultsArray[0];
	$SkippedFiles=$ResultsArray[1];
	UpdateFinal($ID,$NumberOfFiles,$SizeOfIndexDB,$distance,$SkippedFiles);
	build_progress_single($c++,"{umount} {$ligne["ressourcename"]}");
	if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
	build_progress_single(100,"{success} {$ligne["ressourcename"]}");
	return true;
}

function ScanQueue(){
	
	$unix=new unix();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		build_progress(110,"Already instance executed pid:$pid");
		writelogs("Already instance executed pid:$pid",__FUNCTION__,__FILE__,__LINE__);
		exit();
	}
	


	@file_put_contents($pidfile, getmypid());	
	$q=new mysql();
	$sql="SELECT * FROM xapian_folders WHERE enabled=1 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){build_progress(110,"MySQL Error...");echo $q->mysql_error."\n";exit();}
	
	$mountpoint="/home/xapian/mounts";
	$mount=new mount();
	if(!is_dir($mountpoint)){@mkdir($mountpoint,0755,true);}
	if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
	if($mount->ismounted($mountpoint)){echo "Failed to unmount $mountpoint\n";build_progress(110,"$mountpoint {failed}");return;}
	$omindex=$unix->find_program("omindex");
	$omindexlist=$unix->find_program("omindex-list");
	$chmod=$unix->find_program("chmod");
	$nohup=$unix->find_program("nohup");
	$ps=$unix->find_program("ps");
	if(!is_file($omindex)){echo "omindex no such binary\n";build_progress(110,"omindex no such binary");return;}
	if(!is_file($omindexlist)){echo "omindex-list no such binary\n";build_progress(110,"omindex no such binary");return;}	
	$find=$unix->find_program("find");
	$awk=$unix->find_program("awk");
	$scriptindex=$unix->find_program("scriptindex");
	$CountOfResources=mysqli_num_rows($results);
	$c=15;
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ztype=$ligne["ztype"];
		$ID=$ligne["ID"];
		if($c>90){$c=90;}
		echo "Type................: $ztype\n";
		echo "ID..................: $ID\n";
		if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
		build_progress($c++,"$ztype (ID $ID)");
		
		if($ztype=="smb"){
			$resource="\\\\{$ligne["hostname"]}\\{$ligne["sfolder"]}\\{$ligne["tfolder"]}";
			echo "Resource............: $resource\n";
			if($ligne["workgroup"]<>null){$ligne["hostname"]="{$ligne["hostname"]}@{$ligne["workgroup"]}";}
			build_progress($c++,"$ID {mounting} {$ligne["ressourcename"]}");
			if(!$mount->smb_mount($mountpoint, $ligne["hostname"], $ligne["username"], $ligne["password"], $ligne["sfolder"])){
				SetError($ID,"Line ".__LINE__."\n".@implode("\n", $GLOBALS["MOUNT_EVENTS"]));
				continue;
			}
		}
		
		if(!$mount->ismounted($mountpoint)){
			SetError($ID,"$mountpoint not mounted why ???");
			continue;
		}
		
		$ScannerPath="$mountpoint/{$ligne["tfolder"]}";
		$ScannerPath=str_replace("\\", "/", $ScannerPath);
		echo "Base Path...........: $ScannerPath\n";
			
		if(!is_dir($ScannerPath)){
			SetError($ID, "$ScannerPath no such directory");
			if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
			continue;
		}
		
		$cmdline=buicmdline($ligne,$ScannerPath,$omindex);
		echo $cmdline."\n";
		build_progress($c++,"{selected_resource} {$ligne["ressourcename"]} {please_wait}");
		$SartOn=time();
		shell_exec("$nohup $cmdline &");
		sleep(1);
		$PID=$unix->PIDOF($omindex);
		while ($unix->process_exists($PID)) {
			
		    $psa=exec("$ps auxhq $PID 2>&1");
			writelogs("$PID -> $psa",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#(.+?)\s+[0-9]+\s+([0-9\.]+)\s+([0-9\.]+)#", $psa,$re)){
				if($c>90){$c=90;}
				build_progress($c++,"{$ligne["ressourcename"]}: CPU {$re[2]}%, Memory {$re[3]}%");
			}
			sleep(10);
			$PID=$unix->PIDOF($omindex);
			if(!$unix->process_exists($PID)){break;}
		}
		
		
		if($c>90){$c=90;}build_progress($c++,"Index all files...");
		filelist2omega();
		$filescmd="$find \"$ScannerPath\" -type f -printf 'nid={$ligne["ID"]}\\nurl=%p\\npath=%h\\nname=%f\\nsize=%s\\nmodtime=%A@\\n\\n' | $awk '{if ($1 ~ /^path=/) gsub(/\//, \"\\n=\"); if ($1 ~ /^name=/) sub(/\./,\"\\nformat=\"); print}'| $scriptindex /home/omindex-databases/{$ligne["ID"]} /tmp/filelist2omega.script";
		echo "$filescmd\n";
		system($filescmd);
	
		$distance=distanceOfTimeInWords($SartOn,time());
		if($c>90){$c=90;}build_progress($c++,"$distance (ID $ID)");
		shell_exec("$omindexlist /home/omindex-databases/{$ligne["ID"]} >/home/omindex-databases/{$ligne["ID"]}.count 2>&1");
		$NumberOfFiles=$unix->COUNT_LINES_OF_FILE("/home/omindex-databases/{$ligne["ID"]}.count");
		if($c>90){$c=90;}build_progress($c++,"Files $NumberOfFiles (ID $ID)");
		shell_exec("$chmod -R 0755 /home/omindex-databases/{$ligne["ID"]}");
		$SizeOfIndexDB=$unix->DIRSIZE_BYTES_NOCACHE("/home/omindex-databases/{$ligne["ID"]}");
		$SizeOfIndexDB=round($SizeOfIndexDB/1024);
		$ResultsArray=AnalyzeLogFile("/home/omindex-databases/{$ligne["ID"]}.log");
		$Indexed=$ResultsArray[0];
		$SkippedFiles=$ResultsArray[1];
		UpdateFinal($ID,$NumberOfFiles,$SizeOfIndexDB,$distance,$SkippedFiles);
		if($c>90){$c=90;}build_progress($c++,"{umount} {$ligne["ressourcename"]}");
		if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
	}
	
	if($mount->ismounted($mountpoint)){$mount->umount($mountpoint);}
	
	if($mount->ismounted($mountpoint)){
		echo "Failed to unmount $mountpoint\n";
		build_progress(110,"$mountpoint {failed}");
		return;
	}
	@rmdir($mountpoint);
	build_progress(100,"{scanning} {success}");
}

function buicmdline($ligne,$ScannerPath,$omindex){
	
	$f[]=$omindex;
	if(!is_dir("/home/omindex-databases/{$ligne["ID"]}")){
		@mkdir("/home/omindex-databases/{$ligne["ID"]}",0755,true);
	}
	
	if($ligne["depth"]>0){$f[]="--depth-limit={$ligne["depth"]}";}
	$f[]="--stemmer={$ligne["stemming"]}";
	$f[]="--follow";
	$f[]="--retry-failed";
	$f[]="--opendir-sleep=2";
	$f[]="--track-ctime";
	if($ligne["reindex"]==1){$f[]="--overwrite";}
	$f[]="--verbose";
	$f[]="--db=/home/omindex-databases/{$ligne["ID"]}";
	$f[]="--url=/ID-{$ligne["ID"]}";
	$f[]="\"$ScannerPath\"";
	$f[]=">/home/omindex-databases/{$ligne["ID"]}.log 2>&1";
	return @implode(" ", $f);
}

function SetError($ID,$text){
	
	echo "FATAL!! Resource ID:[$ID]:\n$text\n";
	$q=new mysql();
	$myTime=date("Y-m-d H:i:s");
	$q->QUERY_SQL("UPDATE xapian_folders SET ScannedTime='$myTime' WHERE ID=$ID","artica_backup");
	

	
	$text=mysql_escape_string2($text);
	$q->QUERY_SQL("UPDATE xapian_folders SET GetError=1, GetErrorText='$text',ScannedTime='$myTime' WHERE ID=$ID","artica_backup");
	

	
	
}

function UpdateFinal($ID,$filesnumber,$DatabaseSize,$DistanceTime,$SkippedFiles){
	$myTime=date("Y-m-d H:i:s");
	$q=new mysql();

	if(!$q->FIELD_EXISTS("xapian_folders", "SkippedFiles", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `SkippedFiles` INT UNSIGNED NOT NULL DEFAULT 0","artica_backup");
	}
	
	$DistanceTime=mysql_escape_string2($DistanceTime);
	$q->QUERY_SQL("UPDATE xapian_folders SET filesnumber='$filesnumber',
			DatabaseSize='$DatabaseSize',indexed=1,GetError=0,ScannedTime='$myTime',DistanceTime='$DistanceTime',SkippedFiles='$SkippedFiles'
			WHERE ID=$ID","artica_backup");
	
	if(!$q->ok){
		SetError($ID,$q->mysql_error);
		echo $q->mysql_error."\n";
	}
}

function ScanFile($toScan){
	if(!$GLOBALS["SAMBA_INSTALLED"]){return true;}
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	$file=@file_get_contents($toScan);
	$ext=Get_extension($file);
	$nice=EXEC_NICE();	
	$database="$localdatabase/samba.db";
	if(!is_file($GLOBALS["omindex"])){return true;}
	$directory=dirname($file);
	if($GLOBALS["DIRS"]["$directory"]){return true;}
	$basename=basename($file);
	
	$cmd="$nice{$GLOBALS["omindex"]} -l 1 --follow -D $database -U \"$directory\" \"$directory\"";
	$GLOBALS["DIRS"]["$directory"]=true;
	exec($cmd,$results);
	ParseLogs($results);
	return true;
	}

//xls2csv,antiword

function AnalyzeLogFile($filename){
	$indexed=0;
	$skipped=0;

	$fp = @fopen($filename, "r");
	if(!$fp){
		if($GLOBALS["DEBUG_GREP"]){echo "$filename BAD FD\n";}
		return array();
	}
	while(!feof($fp)){
		$ligne = trim(fgets($fp, 4096));
		if(trim($ligne)==null){continue;}
		if(preg_match('#Entering directory#i',$ligne)){continue;}
		if(preg_match('#File is encrypted#',$ligne)){continue;}
		if(preg_match('#Error: Missing or invalid#',$ligne)){continue;}
		if(preg_match('#^Skipping\s+#',$ligne)){$skipped++;continue;}
		if(preg_match('#\s+Skipping\s+#',$ligne)){$skipped++;continue;}
		if(preg_match('#^Indexing.+?\s+(updated|added)#i',trim($ligne))){$indexed++;continue;}
		if(preg_match('#^Indexing.+?\s+indexing metadata#i',trim($ligne))){$indexed++;continue;}
		if(preg_match('#^Indexing.+?Syntax (Error|Warning)#i',trim($ligne))){$skipped++;continue;}
		if(preg_match('#^Indexing.*?already indexed#i',trim($ligne))){$indexed++;continue;}
		if(preg_match('#^note:.*?not an archive#i',trim($ligne))){$skipped++;continue;}
		writelogs("Unable to understand: \"$ligne\"",__FUNCTION__,__FILE__,__LINE__);
		
		
	}
	fclose($fp);
	@unlink($filename);
	return array($indexed,$skipped);
	
}

function shared(){
	$FOLDERS=array();
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){squid_admin_mysql(2, "Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"xapian");exit();}
	@file_put_contents($pidfile, getmypid());			
	$EnableSambaXapian=$sock->GET_INFO("EnableSambaXapian");
	if(!is_numeric($EnableSambaXapian)){$EnableSambaXapian=0;}
	if($EnableSambaXapian==0){squid_admin_mysql(2, "Parsing shared folder is disabled in this configuration, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}	
	$SambaXapianAuth=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaXapianAuth")));	
	if(!is_file($GLOBALS["omindex"])){squid_admin_mysql(2, "omindex no such binary, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$smbclient=$unix->find_program("smbclient");
	$umount=$unix->find_program("umount");
	
	
	if(!is_file($smbclient)){squid_admin_mysql(2, "smbclient, no such binary, aborting...",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$username=$SambaXapianAuth["username"];
	$password=$SambaXapianAuth["password"];
	$domain=$SambaXapianAuth["domain"];
	$comp=$SambaXapianAuth["ip"];
	if(!isset($SambaXapianAuth["lang"])){$SambaXapianAuth["lang"]=="none";}
	$lang=$SambaXapianAuth["lang"];
	if($lang==null){$lang="none";}
	
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	$database="$localdatabase/samba.default.db";
	$nice=EXEC_NICE();	
	
	
	if($comp==null){squid_admin_mysql(2, "smbclient, no computer set, aborting...",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	if($username<>null){
		$creds="-U $username";
		if($password<>null){
			$creds=$creds."%$password";
		}
	}
	$t1=time();
	$cmd="$smbclient -N $creds -L //$comp -g 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	
	if(is_array($results)){
		foreach ($results as $num=>$ligne){
			if(preg_match("#Disk\|(.+?)\|#",$ligne,$re)){
				$folder=$re[1];
				if($folder=="$username"){continue;}
				$FOLDERS[$folder]=true;
			}
		}
	}	
	if(count($FOLDERS)==0){squid_admin_mysql(2, "No shared folder can be browsed with $username@$comp",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$tmpM="/artica-mount-xapian";
	$count=0;
	@mkdir($tmp,0755,true);
	while (list ($directory, $none) = each ($FOLDERS) ){
		
		$mount=new mount();
		if(!$mount->smb_mount($tmpM, $comp, $username, $password, $directory)){squid_admin_mysql(2, "Folder:$directory, permission denied\n".@implode("\n", $GLOBALS["MOUNT_EVENTS"]),__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
		$BaseUrl="file://///$comp/$directory";
		
		
		$cmd="$nice{$GLOBALS["omindex"]} -l 0 -s $lang -E 512 -m 60M --follow -D \"$database\" -U \"$BaseUrl\" \"$tmpM\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		
		$results_scan=array();
		exec($cmd,$results_scan);
		shell_exec("$umount -l $tmpM");
		
		$dirRes=ParseLogs($results_scan);		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$count++;
		squid_admin_mysql(2, "scanned smb://$comp/$directory {took} $took indexed:{$dirRes[0]} skipped:{$dirRes[1]}",__FUNCTION__,__FILE__,__LINE__,"xapian");
			
		
	}
	@rmdir($tmpM);
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	squid_admin_mysql(2, "scanned $count directorie(s) {took} $took",__FUNCTION__,__FILE__,__LINE__,"xapian");	
	
}

function Scan_mysql_dirs(){
	$GLOBALS["INDEXED"]=0;
	$GLOBALS["SKIPPED"]=0;	
	$GLOBALS["DIRS"]=array();
	$unix=new unix();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){squid_admin_mysql(2, "Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"xapian");exit();}
	@file_put_contents($pidfile, getmypid());		
	$q=new mysql();
	$q->check_storage_table(true);
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	
	$nice=EXEC_NICE();
	$sql="SELECT * FROM xapian_folders";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){squid_admin_mysql(2, "$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$t1=time();
	if(!is_file($GLOBALS["omindex"])){squid_admin_mysql(2, "omindex no such binary, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$autofs=new autofs();
	$autofs->automounts_Browse();
	$count=0;
	while ($ligne = mysqli_fetch_assoc($results)) {	
		$directory=$ligne["directory"];
		$database="$localdatabase/samba.".md5($directory).".db";
		$depth=$ligne["depth"];
		$maxsize=$ligne["maxsize"];
		$samplsize=$ligne["sample-size"];
		$lang=$ligne["lang"];
		$WebCopyID=$ligne["WebCopyID"];
		$autmountdn=$ligne["autmountdn"];
		if($lang==null){$lang="english";}
		$indexed=$ligne["indexed"];
		if(!is_numeric($samplsize)){$samplsize=512;}
		if(!is_numeric($maxsize)){$maxsize=60;}
		if(!is_numeric($depth)){$depth=0;}	
		$BaseUrl=$directory;
		
		if($WebCopyID>0){
			$directory=WebCopyIDDirectory($WebCopyID);
			$BaseUrl=WebCopyIDAddresses($WebCopyID)."/";
			
		}
		
		if($autmountdn<>null){
			if(!isset($autofs->hash_by_dn[$autmountdn])){
				squid_admin_mysql(2, "Fatal.. $autmountdn no such connection",__FUNCTION__,__FILE__,__LINE__,"xapian");
				continue;
			}
			$autmountdn_array=$autofs->hash_by_dn[$autmountdn];
			$directory="/automounts/{$autmountdn_array["FOLDER"]}";
			$autmountdn_infos=$autmountdn_array["INFOS"];
			if(!isset($autmountdn_infos["BROWSER_URI"])){
				squid_admin_mysql(2, "Fatal.. $autmountdn external protocol error",__FUNCTION__,__FILE__,__LINE__,"xapian");
				continue;
			}
			$BaseUrl=$autmountdn_infos["BROWSER_URI"];
		}
		
		if(!is_dir($database)){@mkdir($database,0755,true);}
		if(!is_dir($directory)){squid_admin_mysql(2, "$directory, no such directory",__FUNCTION__,__FILE__,__LINE__,"xapian");continue;}
		$t=time();
		$cmd="$nice{$GLOBALS["omindex"]} -l $depth -s $lang -E $samplsize -m {$maxsize}M --follow -D \"$database\" -U \"$BaseUrl\" \"$directory\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$GLOBALS["DIRS"]["$directory"]=true;
		$results_scan=array();
		exec($cmd,$results_scan);
		$dirRes=ParseLogs($results_scan);		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$DatabaseSize=$unix->DIRSIZE_BYTES($database);
		$count++;
		$indexed=$indexed+$dirRes[0];
		squid_admin_mysql(2, "scanned $directory {took} $took indexed:$indexed skipped:{$dirRes[1]}",__FUNCTION__,__FILE__,__LINE__,"xapian");
		$q->QUERY_SQL("UPDATE xapian_folders SET ScannedTime=NOW(),indexed=$indexed,DatabasePath='$database',DatabaseSize='$DatabaseSize'
	
		WHERE ID={$ligne["ID"]}","artica_backup");
		if(!$q->ok){squid_admin_mysql(2, "$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"xapian");}
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	squid_admin_mysql(2, "scanned $count directorie(s) {took} $took",__FUNCTION__,__FILE__,__LINE__,"xapian");
	
}

function WebCopyIDAddresses($ID){
	$q=new mysql();
	$sql="SELECT useSSL,servername FROM freeweb WHERE WebCopyID=$ID";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["servername"]<>null){
		$method="http";
		if($ligne["useSSL"]==1){$method="https";}
		return "$method://{$ligne["servername"]}";
	}
	
	$sql="SELECT sitename FROM httrack_sites WHERE ID=$ID";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	return $ligne["sitename"];
	
}
function WebCopyIDDirectory($ID){
	$q=new mysql();
	$sql="SELECT workingdir,sitename FROM httrack_sites WHERE ID=$ID";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$parsed_url=parse_url($ligne["sitename"]);
	$ligne["sitename"]="{$parsed_url["host"]}";	
	return $ligne["workingdir"]."/{$ligne["sitename"]}";
	
}


function TransFormToHtml($file){
	if(!is_file($file)){return false;}
	$original_file=trim(@file_get_contents("$file"));
	
 $attachmentdir=dirname($file);
 $fullmessagesdir=dirname($file);
 $attachmenturl='images.listener.php?mailattach=';   
   $cmd='/usr/bin/mhonarc ';
   $cmd=$cmd."-attachmentdir $attachmentdir ";
   $cmd=$cmd."-attachmenturl $attachmenturl ";
   $cmd=$cmd.'-nodoc ';
   $cmd=$cmd.'-nofolrefs ';
   $cmd=$cmd.'-nomsgpgs ';
   $cmd=$cmd.'-nospammode ';
   $cmd=$cmd.'-nosubjectthreads ';
   $cmd=$cmd.'-idxfname storage ';
   $cmd=$cmd.'-nosubjecttxt "no subject" ';
   $cmd=$cmd.'-single ';
   $cmd=$cmd.$original_file . ' ';
   $cmd=$cmd. ">$attachmentdir/message.html 2>&1";
   system($cmd);
   $size=filesize("$attachmentdir/message.html");
	write_syslog("Creating html  $attachmentdir/message.html ($size bytes)",__FILE__);
	
}

function homes(){
	$GLOBALS["INDEXED"]=0;
	$GLOBALS["SKIPPED"]=0;	
	$GLOBALS["DIRS"]=array();
	$FOLDERS=array();
	$RFOLDERS=array();
	$unix=new unix();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){squid_admin_mysql(2, "Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"xapian");exit();}
	@file_put_contents($pidfile, getmypid());		
	$nice=EXEC_NICE();
	$t1=time();
	if(!is_file($GLOBALS["omindex"])){squid_admin_mysql(2, "omindex no such binary, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	
	$ldap=new clladp();
	$attr=array("homeDirectory","uid","dn");
	$pattern="(&(objectclass=sambaSamAccount)(uid=*))";
	$sock=new sockets();
	$sock=new sockets();
	$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,".$ldap->suffix,$pattern,$attr);
	$hash=ldap_get_entries($ldap->ldap_connection,$sr);
	$sock=new sockets();
	for($i=0;$i<$hash["count"];$i++){
		$uid=$hash[$i]["uid"][0];
		$homeDirectory=$hash[$i][strtolower("homeDirectory")][0];
		if($uid==null){writelogs("uid is null, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($uid=="nobody"){writelogs("uid is nobody, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($uid=="root"){writelogs("uid is root, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if(substr($uid,strlen($uid)-1,1)=='$'){writelogs("$uid:This is a computer, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($homeDirectory==null){$homeDirectory="/home/$uid";}	
		if(!is_dir($homeDirectory)){continue;}	
		$FOLDERS[$uid]=$homeDirectory;
		$RFOLDERS[$homeDirectory]=true;
	}
	
	$SambaXapianAuth=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaXapianAuth")));	
	$username=$SambaXapianAuth["username"];
	$password=$SambaXapianAuth["password"];
	$domain=$SambaXapianAuth["domain"];
	$comp=$SambaXapianAuth["ip"];
	if(!isset($SambaXapianAuth["lang"])){$SambaXapianAuth["lang"]=="none";}
	$lang=$SambaXapianAuth["lang"];
	if($lang==null){$lang="none";}	
	$t1=time();
	$dirs=$unix->dirdir("/home");
	$samba=new samba();
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	
	while (list ($dir, $ligne) = each ($dirs) ){
		if($dir=="/home/export"){continue;}
		if($dir=="/home/netlogon"){continue;}
		if($dir=="/home/artica"){continue;}
		if($dir=="/home/logs-backup"){continue;}
		if(isset($RFOLDERS[$dir])){continue;}
		if(isset($samba->main_shared_folders[$dir])){continue;}
		$FOLDERS[basename($dir)]=$dir;
	}
	$count=0;
	while (list ($uid, $directory) = each ($FOLDERS) ){	
		
		$BaseUrl=$directory;
		$database="$localdatabase/xapian-$uid";
		@mkdir($database,0755,true);
		if(!is_dir($directory)){squid_admin_mysql(2, "$directory, no such directory",__FUNCTION__,__FILE__,__LINE__,"xapian");continue;}
		$t=time();
		$cmd="$nice{$GLOBALS["omindex"]} -l 0 -s $lang -E 512 -m 60M --follow -D \"$database\" -U \"$BaseUrl\" \"$directory\" -v 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$results_scan=array();
		exec($cmd,$results_scan);
		$dirRes=ParseLogs($results_scan);		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$count++;
		squid_admin_mysql(2, "scanned $directory {took} $took indexed:{$dirRes[0]} skipped:{$dirRes[1]}",__FUNCTION__,__FILE__,__LINE__,"xapian");
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	squid_admin_mysql(2, "scanned $count directorie(s) {took} $took",__FUNCTION__,__FILE__,__LINE__,"xapian");	
}



?>