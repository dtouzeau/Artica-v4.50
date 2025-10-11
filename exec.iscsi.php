<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

$GLOBALS["deflog_start"]="Starting......: ".date("H:i:s")." [INIT]: iSCSI target";
$GLOBALS["deflog_sstop"]="Stopping......: ".date("H:i:s")." [INIT]: iSCSI target";

if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	
	if($GLOBALS["VERBOSE"]){ini_set_verbosed();}
}
if($argv[1]=="--install"){install_iscsi();exit();}
if($argv[1]=="--uninstall"){uninstall_iscsi();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--clients"){clients();exit();}
if($argv[1]=="--stat"){statfile($argv[2]);exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--search"){$GLOBALS["OUTPUT"]=true;search_target($argv[2]);exit();}
if($argv[1]=="--checknodes"){$GLOBALS["OUTPUT"]=true;checknodes();exit();}
if($argv[1]=="--delete-client"){$GLOBALS["OUTPUT"]=true;delete_target($argv[2]);exit();}
if($argv[1]=="--dump-config"){$GLOBALS["OUTPUT"]=true;dump_config();exit();}
if($argv[1]=="--remove-lun"){$GLOBALS["OUTPUT"]=true;remove_lun($argv[2]);exit();}


function GetSessionNodes(){
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	$cmd="$iscsiadm -m session 2>&1";
	exec($cmd,$results);
	$SESSIONS=array();
	foreach ($results as $line){
		if(!preg_match("#\[([0-9]+)\]\s+([0-9\.]+):([0-9]+),([0-9]+)\s+(.+?):(.+)#",$line,$re)){continue;}
		if(preg_match("#(.+?)\s+\(#", $re[6],$ri)){$re[6]=$ri[1];}
		$mkey="{$re[5]}:{$re[6]}";
		echo "[".__LINE__."] Session open on $mkey\n";
		$SESSIONS[$mkey]=$re[1];
	}
	
	return $SESSIONS;
}

function GetSessionDisk($session_id){
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	$cmd="$iscsiadm -m session -r $session_id -P 3 2>&1";
	exec($cmd,$results);
	foreach ($results as $line){
		if(preg_match("#Attached scsi disk\s+(.+?)\s+State#i", $line,$re)){
			return $re[1];
		}
	}
	
}

function GetMountedSessions(){
	$SESSIONS=array();
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	foreach ($f as $line){
		if(preg_match("#(.+?)\s+\/iSCSI\/(.+?)\/partition.*?\s+(.+?)\s+#", $line,$re)){
			$SESSIONS[$re[2]]=array("dev"=>$re[1],"type"=>$re[3]);
		}
	}
	return $SESSIONS;
}
function GetMountedNodesSessions($xnode){
	$SESSIONS=array();
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	foreach ($f as $line){
		if(preg_match("#(.+?)\s+\/iSCSI\/$xnode\/(.+?)\s+(.+?)\s+#", $line,$re)){
			$SESSIONS[$re[1]]="/iSCSI/$xnode/{$re[2]}";
		}
	}
	return $SESSIONS;	
	
}

function delete_target($ID){
	$unix=new unix();
	if(intval($ID)==0){
		echo "[".__LINE__."] ID === 0 !!!\n";
		build_progress_install("{delete_iscsi_connection} {failed}",110);
		return;
	}
	
	$sql="SELECT * FROM iscsi_client WHERE ID='{$ID}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$subarray2=unserialize(base64_decode($ligne["Params"]));
	
	$title="{$subarray2["ISCSI"]}:{$subarray2["FOLDER"]}";
	$xnode=$title;
	$portal="{$subarray2["IP"]}:{$subarray2["PORT"]}";
	$iscsiadm=$unix->find_program("iscsiadm");
	$rm=$unix->find_program("rm");
	echo "[".__LINE__."] Remove connection $title\n";
	build_progress_install("$title",15);
	
	
	echo "[".__LINE__."] Get sessions nodes...\n";
	$GetSessionNodes=GetSessionNodes();
	
	
	build_progress_install("Get Mounted sessions",25);
	echo "[".__LINE__."] Get Mounted sessions...\n";
	$MOUNTED=GetMountedNodesSessions($xnode);
	$umount=$unix->find_program("umount");
	
	build_progress_install("{umount} {disks}",26);
	
	while (list ($dev, $path) = each ($MOUNTED)){
		build_progress_install("{umount} $path",26);
		echo "[".__LINE__."] Mounted on $path from $dev\n";
		system("$umount -l $path");
		@rmdir($path);
	}

	if(isset($GetSessionNodes[$title])){
		echo "[".__LINE__."] Logout connection $title\n";
		build_progress_install("{logout}",30);
		$cmd="$iscsiadm --m node -T $title --portal $portal -u";
		echo "[".__LINE__."] $cmd\n";
		system($cmd);
		$GetSessionNodes=GetSessionNodes();
		if(isset($GetSessionNodes[$title])){
			build_progress_install("{logout} {failed}",110);
			return;
		}
	}
	
	build_progress_install("{remove} $title",40);
	$cmd="$iscsiadm -m node -o delete -T $title --portal $portal";
	echo "[".__LINE__."] $cmd\n";
	system($cmd);
	if(is_dir("/etc/iscsi/nodes/$title")){system("$rm -rf /etc/iscsi/nodes/$title");}
	build_progress_install("{remove} $ID",50);
	$q->QUERY_SQL("DELETE FROM iscsi_client WHERE ID='$ID'",'artica_backup');
	
	build_progress_install("{checking} $ID",90);
	build_progress_install("{delete_iscsi_connection} {done}",100);
	
	
}

function checknodes(){
	$unix=new unix();
	$nodes=$unix->dirdir("/etc/iscsi/nodes");
	if(count($nodes)==0){echo "[".__LINE__."] No node...\n";return;}
	
	$pidfile="/etc/artica-postfix/pids/exec.iscsi.php.checknodes.pid";
	$pidtime="/etc/artica-postfix/pids/exec.iscsi.php.checknodes.time";
	
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid)){
		echo "[".__LINE__."] Already process exists $pid\n";
		exit();
	}
	
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){
		if($time<3){
			echo "[".__LINE__."] {$time}mn, need 3 minutes, please restart later.\n";
			exit();
		}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile,getmypid());
	
	
	$iscsiadm=$unix->find_program("iscsiadm");
	$php=$unix->LOCATE_PHP5_BIN();
	$SESSIONS=GetSessionNodes();
	
	
	
	$c=0;
	while (list ($path, $subarray) = each ($nodes)){
		$xnode=basename($path);
		echo "[".__LINE__."] Checking node $xnode:";
		if(isset($SESSIONS[$xnode])){
			echo "[".__LINE__."]  OK ";
			ChecksMount($xnode);			
			continue;
		}
		echo "[".__LINE__."]  Connecting...";
		$results=array();
		exec("$iscsiadm --mode node --targetname $xnode --login 2>&1",$results);
		$SESSIONS=GetSessionNodes();
		if(isset($SESSIONS[$xnode])){
			squid_admin_mysql(2, "iSCSI node $xnode Success to connect", @implode("\n", $results),__FILE__,__LINE__);
			echo "[".__LINE__."]  OK ";
			if(ChecksMount($xnode)>0){$c++;}
			continue;
		}
		squid_admin_mysql(0, "iSCSI node $xnode failed to connect", @implode("\n", $results),__FILE__,__LINE__);
	}
	
	if($c>0){

	}

}

function ChecksMount($xnode){
	$MOUNTCOUNT=0;
	$MOUNTED=GetMountedSessions();
	if(isset($MOUNTED[$xnode])){
		echo "[".__LINE__."]  mounted as {$MOUNTED[$xnode]["dev"]} type {$MOUNTED[$xnode]["type"]}\n";
		return;
	}
	$unix=new unix();
	$SESSIONS=GetSessionNodes();
	$mount=$unix->find_program("mount");
	$session_id=$SESSIONS[$xnode];
	$dev=GetSessionDisk($session_id);
	if($dev==null){
		echo "[".__LINE__."] Unable to determine dev system on $xnode\n";
		return;
	}
	$dev="/dev/$dev";
	$PartitionNumber=$unix->DISK_GET_PARTNUMBER($dev);
	echo "[".__LINE__."] Partition Number: $PartitionNumber\n";
	echo "[".__LINE__."] Node............: $xnode\n";
	echo "[".__LINE__."] Session id......: $session_id\n";
	echo "[".__LINE__."] Dev name........: $dev\n";
	
	for($i=1;$i<$PartitionNumber+1;$i++){
		$filesystem=trim($unix->DISK_GET_TYPE_BYFILE("$dev$i"));
		echo "[".__LINE__."] --------------------------------------\n";
		$mountpath="/iSCSI/$xnode/partition$i";
		echo "[".__LINE__."] Dev name........: $dev$i\n";
		echo "[".__LINE__."] Filesystem......: $filesystem\n";
		echo "[".__LINE__."] Mount path......: $mountpath\n";
		if($filesystem==null){echo "[".__LINE__."] Unable to determine file system on \"$dev$i\"\n";continue;}
		
		if(is_dir($mountpath)){rmdir($mountpath);}
		@mkdir($mountpath,0755,true);
		echo "[".__LINE__."] Mounting....\n";
		$cmd="$mount -t $filesystem $dev{$i} $mountpath";
		echo $cmd."\n";
		system($cmd);
		$MOUNTCOUNT++;
	}
	
	
	
	return $MOUNTCOUNT;

	
	
}

function iscsi_client_sessions(){
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	$cmd="$iscsiadm -m session 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$array=array();
	foreach ($results as $index=>$line){

		if(!preg_match("#([0-9\.]+):([0-9]+),([0-9]+)\s+(.+?):(.+)#",$line,$re)){continue;}
		$STATE=null;
		$cmd="$iscsiadm -m session -r {$re[1]} -P 3 2>&1";
		exec($cmd,$SESSIONSR);
		foreach ($SESSIONSR as $b){
			if(preg_match("#iSCSI Connection State:\s+(.+)#i", $b,$ri)){
				$STATE=trim($ri[1]);
				continue;
			}
			if(preg_match("#Attached scsi disk\s+(.+?)\s+State:\s+(.+?)#i",$b,$ri)){
				$DEVNAME=trim($ri[1]);
				$DEVSTATE=trim($ri[2]);
			}
		}



		$array[$re[1]][]=array("PORT"=>$re[2],"ID"=>$re[3],"ISCSI"=>$re[4],"FOLDER"=>$re[5],"IP"=>$re[1],"STATE"=>$STATE,
				"DEVNAME"=>$DEVNAME,"DEVSTATE"=>$DEVSTATE
					

		);
	}
	@file_put_contents(PROGRESS_DIR."/iscsi-sessions.array", serialize($array));
	@chmod(PROGRESS_DIR."/iscsi-sessions.array", 0755);


}



function statfile($path){
	echo "[".__LINE__."] $path\n---------------------------------\n";
	$array=stat($path);
	print_r($array);
	echo filetype($path)."\n";
	if(!is_file($path)){echo "[".__LINE__."] is_file:false\n";}
	print_r( pathinfo($path));
}

function build_progress($text,$pourc){
	
	if(is_numeric($text)){
		$newtext=$pourc;
		$pourc=$text;
		$text=$newtext;
		
	}
	
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system_disks_iscsi_progress";
	echo "[".__LINE__."] {$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}





function search_target($search){
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	@unlink(PROGRESS_DIR."/iscsi-search.array");
	echo "[".__LINE__."] iscsiadm = $iscsiadm\n";
	
	build_progress(20, "{search} $search");
	$cmd="$iscsiadm --mode discovery --type sendtargets --portal $search 2>&1";
	exec($cmd,$results);
	echo "[".__LINE__."] $cmd = ". count($results)." rows\n";
	build_progress(30, "{search} $search");

	$array=array();
	$ERROR="{failed}";
	foreach ($results as $index=>$line){

	
		if(!preg_match("#([0-9\.]+):([0-9]+),([0-9]+)\s+(.+?):(.+)#",$line,$re)){
			echo "[".__LINE__."] $line\n";
			if(preg_match("#Connection refused#i", $line,$re)){$ERROR="{connection_refused}";}
			continue;
		}
		build_progress(40, "{found} {$re[4]}");
		
		
		$array[$re[1]][]=array("PORT"=>$re[2],"ID"=>$re[3],"ISCSI"=>$re[4],"FOLDER"=>$re[5],"IP"=>$re[1]);
	}
	
	if(count($array)==0){
		build_progress(110, "{search} $search $ERROR");
		return;
	}
	
	@file_put_contents(PROGRESS_DIR."/iscsi-search.array", serialize($array));
	@chmod(PROGRESS_DIR."/iscsi-search.array", 0755);
	build_progress(100, "{search} $search {success}");
	
}

function remove_lun($ID){
	
	
	$q=new mysql();
	$sql="SELECT * FROM iscsi_params WHERE ID='$ID'";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$Params=unserialize(base64_decode($ligne["Params"]));
	$iqn=$Params["WWN"];
	$MAINCONFIG=dump_config();
	
	$unix=new unix();
	$targetcli=$unix->find_program("targetcli");
	
	$shared_folder=$ligne["shared_folder"];
	$artica_type=$ligne["type"];
	
	if($artica_type=="file"){$type="fileio";}else{$type="block";}
	
	echo "[".__LINE__."] Resource: $shared_folder\n";
	echo "[".__LINE__."] Type....: $type\n";
	

	
	
	if(isset($MAINCONFIG[$iqn])){
		if($MAINCONFIG[$iqn]<>null){
			build_progress("{delete} {$MAINCONFIG[$iqn]}",20);
			echo "[".__LINE__."] Removing LUN $ID $iqn\n";
			echo "[".__LINE__."] Removing {$MAINCONFIG[$iqn]}\n";
			$cmd="$targetcli /iscsi/$iqn/tpg1/luns delete {$MAINCONFIG[$iqn]}";
			echo $cmd."\n";
			system($cmd);
		}
		build_progress("{delete} {$iqn}",30);
		$cmd="$targetcli /iscsi delete $iqn";
		system($cmd);
		$MAINCONFIG=dump_config();
		if(isset($ARRAY["$iqn"])){
			build_progress("{delete} $iqn {failed}",110 );
			return false;
		}
		
	}
	
	echo "[".__LINE__."] Checks /backstores/$type/$shared_folder\n";
	if(isset($MAINCONFIG["backstores"]["/backstores/$type/$shared_folder"])){
		build_progress("{delete} /backstores/$type/$shared_folder",50);
		$path="/backstores/$type";
		$cmd="$targetcli /backstores/$type delete $shared_folder";
		echo $cmd."\n";
		system($cmd);
		$MAINCONFIG=dump_config();
		echo "[".__LINE__."] Checking backstores /backstores/$type/$shared_folder\n";
		print_r($MAINCONFIG["backstores"]);
		if(isset($MAINCONFIG["backstores"]["/backstores/$type/$shared_folder"])){
			build_progress("{delete} /backstores/$type/$shared_folder {failed}",110 );
			return false;
		}
	}
	
	
	build_progress("{remove_entry_from_database}",80);
	$sql="DELETE FROM iscsi_params WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("{remove_entry_from_database} {failed}",110);
		return;
	}
	build_progress("{remove_entry_from_database} {success}",100);
	
	
}


function build(){
	
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__."pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		build_progress("{$GLOBALS["deflog_start"]} Already process exists $pid",110);
		echo "[".__LINE__."] {$GLOBALS["deflog_start"]} Already process exists $pid\n";
		return;
	}
	
	@file_put_contents($pidfile,getmypid());	
	$year=date('Y');
	$month=date('m');
	$EnableISCSI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableISCSI"));
	$dd=$unix->find_program("dd");
	if($EnableISCSI==0){
		build_progress("{$GLOBALS["deflog_start"]} {service_disabled}",110);
		return;
	}
	
	$sql="SELECT * FROM iscsi_params ORDER BY ID DESC";
	$q=new mysql();
	$c=0;
	$dd=$unix->find_program("dd");
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	if(!$q->ok){
		build_progress("{$GLOBALS["deflog_start"]} MySQL error",110);
		echo "[".__LINE__."] {$GLOBALS["deflog_start"]} $q->mysql_error\n";return;		
	}
	
	$targetcli=$unix->find_program("targetcli");
	$MAINCONFIG=dump_config();
	
	build_progress("{$GLOBALS["deflog_start"]} {checking_containers}...",10);		
	$max=mysqli_num_rows($results);
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		$hostname=$ligne["hostname"];
		$artica_type=$ligne["type"];
		$tbl=explode(".",$hostname);
		
		echo "[".__LINE__."] {$GLOBALS["deflog_start"]} [{$ligne["ID"]}] ressource type:$artica_type {$ligne["dev"]}\n";
		build_progress("{$GLOBALS["deflog_start"]} {building} $c/$max $artica_type {$ligne["dev"]}",20);
		
		echo "[".__LINE__."] Type..............: $artica_type\n";
		echo "[".__LINE__."] Path..............: {$ligne["dev"]}\n";
		
		if($artica_type=="file"){
			
			if(!isset($MAINCONFIG["fileio"][$ligne["dev"]])){
				if(!create_fileio($ligne["dev"],$ligne["file_size"],$ligne["ID"],$ligne["shared_folder"])){continue;}
			}
			
			
		}
		if($artica_type=="disk"){
			if(!isset($MAINCONFIG["block"][$ligne["dev"]])){
				echo "[".__LINE__."] Create results....: [block][{$ligne["dev"]}] no such entry\n";
				if(!create_block($ligne["dev"],$ligne["file_size"],$ligne["ID"],$ligne["shared_folder"])){
					echo "[".__LINE__."] Create results....: FALSE\n";
					continue;
				}else{
					echo "[".__LINE__."] Create results....: TRUE\n";
				}
			}else{
				echo "[".__LINE__."] Create results....: ALREADY\n";
			}
		}

		
		
		
		
		krsort($tbl);
		$newhostname=@implode(".",$tbl);
		$Params=unserialize(base64_decode($ligne["Params"]));
		
		if(!isset($Params["WWN"])){
			$iqn="iqn.$year-$month.$newhostname:{$ligne["shared_folder"]}";
			$Params["WWN"]=$iqn;
			$newParams=base64_encode(serialize($Params));
			$q->QUERY_SQL("UPDATE iscsi_params SET `Params`='$newParams' WHERE ID='{$ligne["ID"]}'","artica_backup");
			
		}else{
			$iqn=$Params["WWN"];

		}
		
		
		
		
		
		echo "[".__LINE__."] wwn...............: $iqn\n";
		
		if(!isset($MAINCONFIG[$iqn])){
			if(!create_wwn($iqn)){
				echo "[".__LINE__."] Create results....: FALSE\n";
			}else{
				echo "[".__LINE__."] Create results....: TRUE\n";
			}
		}else{
			echo "[".__LINE__."] Create results....: ALREADY\n";
		}
		
		if(!attach_lun($iqn,$ligne["shared_folder"])){
			echo "[".__LINE__."] Attach LUN........: {$ligne["shared_folder"]} FALSE\n";
			
		}else{
			echo "[".__LINE__."] Attach LUN........: {$ligne["shared_folder"]} TRUE\n";
		}
		
		
		echo "[".__LINE__."] Authentication....: {$ligne["EnableAuth"]}\n";
		if(!isset($Params["ImmediateData"])){$Params["ImmediateData"]=1;}
		if(!isset($Params["MaxConnections"])){$Params["MaxConnections"]=1;}
		if(!isset($Params["Wthreads"])){$Params["Wthreads"]=8;}
		if(!isset($Params["IoType"])){$Params["IoType"]="fileio";}
		if(!isset($Params["mode"])){$Params["mode"]="wb";}

		if(!is_numeric($Params["MaxConnections"])){$Params["MaxConnections"]=1;}
		if(!is_numeric($Params["ImmediateData"])){$Params["ImmediateData"]=1;}
		if(!is_numeric($Params["Wthreads"])){$Params["Wthreads"]=8;}
		$Password=$Params["password"];
		if($Params["IoType"]==null){$Params["IoType"]="fileio";}
		if($Params["mode"]==null){$Params["mode"]="wb";}
		$EnableAuth=$ligne["EnableAuth"];	
		$uid=trim($ligne["uid"]);
		
		$cmdz=array();
		$cmdz[]="$targetcli iscsi/$iqn/tpg1/acls create $iqn";
		
		
		$cmdz[]="$targetcli iscsi/$iqn/tpg1 set parameter MaxConnections={$Params["MaxConnections"]}";
		$cmdz[]="$targetcli iscsi/$iqn/tpg1 set parameter ImmediateData={$Params["ImmediateData"]}";
		
		if($EnableAuth==1){
			$cmdz[]="$targetcli iscsi/$iqn/tpg1 set attribute authentication=1";
			$cmdz[]="$targetcli iscsi/$iqn/tpg1/acls/$iqn set auth userid=\"$uid\" password='$Password'";
			
			
		}else{
			$cmdz[]="$targetcli iscsi/$iqn/tpg1 set attribute authentication=0";
		}

		foreach ($cmdz as $cmd){
			echo $cmd."\n";
			system($cmd);
			
		}
		
		
		$c++;
	}
	
	
	if($GLOBALS["PROGRESS"]){
		build_progress("{restarting}",80);
		system("/etc/init.d/iscsitarget restart");
	}
	
	build_progress("{done}",100);
	
}

function etc_default_iscsitarget(){
	if(!is_file("/etc/default/iscsitarget")){return;}
	$f[]="ISCSITARGET_ENABLE=true";
	@file_put_contents("/etc/default/iscsitarget", @implode("\n", true));
}

function clients(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__."pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "[".__LINE__."] Already process exists $pid\n";
		return;
	}
	
	$iscsiadm=$unix->find_program("iscsiadm");
	if(!is_file($iscsiadm)){
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] {$GLOBALS["deflog_start"]} iscsiadm no such file\n";}
		return;
	}	
	
	@file_put_contents($pidfile,getmypid());
	$php=$unix->LOCATE_PHP5_BIN();
	
	
$f[]="node.startup = automatic";
$f[]="#node.session.auth.authmethod = CHAP";
$f[]="#node.session.auth.username = username";
$f[]="#node.session.auth.password = password";
$f[]="#node.session.auth.username_in = username_in";
$f[]="#node.session.auth.password_in = password_in";
$f[]="#discovery.sendtargets.auth.authmethod = CHAP";
$f[]="#discovery.sendtargets.auth.username = username";
$f[]="#discovery.sendtargets.auth.password = password";
$f[]="#discovery.sendtargets.auth.username_in = username_in";
$f[]="#discovery.sendtargets.auth.password_in = password_in";
$f[]="node.session.timeo.replacement_timeout = 120";
$f[]="node.conn[0].timeo.login_timeout = 15";
$f[]="node.conn[0].timeo.logout_timeout = 15";
$f[]="node.conn[0].timeo.noop_out_interval = 10";
$f[]="node.conn[0].timeo.noop_out_timeout = 15";
$f[]="node.session.initial_login_retry_max = 4";
$f[]="#node.session.iscsi.InitialR2T = Yes";
$f[]="node.session.iscsi.InitialR2T = No";
$f[]="#node.session.iscsi.ImmediateData = No";
$f[]="node.session.iscsi.ImmediateData = Yes";
$f[]="node.session.iscsi.FirstBurstLength = 262144";
$f[]="node.session.iscsi.MaxBurstLength = 16776192";
$f[]="node.conn[0].iscsi.MaxRecvDataSegmentLength = 131072";
$f[]="discovery.sendtargets.iscsi.MaxRecvDataSegmentLength = 32768";
$f[]="#node.conn[0].iscsi.HeaderDigest = CRC32C,None";
$f[]="#node.conn[0].iscsi.DataDigest = CRC32C,None";
$f[]="#node.conn[0].iscsi.HeaderDigest = None,CRC32C";
$f[]="#node.conn[0].iscsi.DataDigest = None,CRC32C";
$f[]="#node.conn[0].iscsi.HeaderDigest = CRC32C";
$f[]="#node.conn[0].iscsi.DataDigest = CRC32C";
$f[]="#node.conn[0].iscsi.HeaderDigest = None";
$f[]="#node.conn[0].iscsi.DataDigest = None";
$f[]="";	

@file_put_contents("/etc/iscsi/iscsid.conf",@implode("\n",$f));
	
	
	
	
	
	$sql="SELECT * FROM iscsi_client";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] {$GLOBALS["deflog_start"]} iscsiadm $sql\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	if(!$q->ok){
		echo "[".__LINE__."] {$GLOBALS["deflog_start"]} iscsiadm $q->mysql_error\n";
		return;		
	}
	
	if(mysqli_num_rows($results)==0){
		echo "[".__LINE__."] {$GLOBALS["deflog_start"]} iscsiadm no iSCSI disk connection scheduled\n";
		return;
	}	
	
	
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] {$GLOBALS["deflog_start"]} $q->mysql_error\n";}return;}
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){		
		$subarray2=unserialize(base64_decode($ligne["Params"]));	
		$iqn="{$subarray2["ISCSI"]}:{$subarray2["FOLDER"]}";
		$port=$subarray2["PORT"];
		$ip=$subarray2["IP"];
		echo "[".__LINE__."] {$GLOBALS["deflog_start"]} $iqn -> $ip:$port Auth:{$ligne["EnableAuth"]} Persistane:{$ligne["Persistante"]}\n";
		if($ligne["EnableAuth"]==1){
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.session.auth.username -v \"{$ligne["username"]}\" 2>&1";
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.session.auth.password -v \"{$ligne["password"]}\" 2>&1";
		}else{
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port --login 2>&1";
		}
		
		if($ligne["Persistante"]==1){
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.startup -v automatic 2>&1";
		}else{
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.startup -v manual 2>&1";
		}
		
		$cmds[]="$iscsiadm -m node --logoutall all 2>&1";
		
	}
		

	if(is_array($cmds)){
		while (list ($num, $line) = each ($cmds)){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] --------------------------\n$line\n";}
			$results=array();
			exec($line,$results);
			if($GLOBALS["VERBOSE"]){@implode("\n",$results);}
		}
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] --------------------------\n$iscsiadm -m node --loginall all\n";}
	shell_exec("$iscsiadm -m node --loginall all");

	
		
		
}

function stat_system($path){
	exec("stat -f $path -c %b 2>&1",$results);
	$line=trim(@implode("",$results));
	if(preg_match("#^[0-9]+#",$line,$results)){return true;}
	return false;
}
function PID_NUM(){

	$unix=new unix();
	
	$pid=$unix->get_pid_from_file("/var/run/iscsid.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("iscsid");
	return $unix->PIDOF($Masterbin);

}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("iscsid");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress("{starting_service}",90);
		return;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$iscsiiname=$unix->find_program("iscsi-iname");
	
	$iSCSInitiatorName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("iSCSInitiatorName"));
	if($iSCSInitiatorName==null){
		system("$iscsiiname >/etc/artica-postfix/settings/Daemons/iSCSInitiatorName");
		$iSCSInitiatorName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("iSCSInitiatorName"));
	}
	
	@file_put_contents("/etc/iscsi/initiatorname.iscsi","InitiatorName=$iSCSInitiatorName\n");
	$cmd="$Masterbin -c /etc/iscsi/iscsid.conf --uid=root --gid=root --initiatorname=/etc/iscsi/initiatorname.iscsi --pid=/var/run/iscsid.pid";
	
	if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: initiator name...........: $iSCSInitiatorName\n";}
	build_progress("{starting_service}",60);
	if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);

	for($i=1;$i<11;$i++){
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}
	build_progress("{starting_service}",70);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		squid_admin_mysql(2,"iSCSI service success to be started PID $pid", null, __FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		$nodes=$unix->dirdir("/etc/iscsi/nodes");
		if(count($nodes)>0){$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --checknodes");}
		
		
	}else{
		squid_admin_mysql(0,"iSCSI service failed to be started", null, __FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}
	build_progress("{starting_service}",90);

}



function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service shutdown - force - pid $pid...\n";}
	system("$kill -9 $pid");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] {$GLOBALS["deflog_sstop"]} service failed...\n";}
		return;
	}

}
function build_progress_install($text,$pourc){
	
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/iscsi.install.prg";
	echo "[".__LINE__."] {$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function uninstall_iscsi(){
	build_progress_install("{uninstalling} {APP_IETD}",25);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableISCSI",0);
	remove_service("/etc/init.d/iscsitarget");
	
	$sql="SELECT ID FROM iscsi_client";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$aclid=$ligne["ID"];
		build_progress_install("{uninstalling} {APP_IETD} Client ID $aclid",80);
		delete_target($aclid);
	}
	
	
	$sql="SELECT ID FROM iscsi_params";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$aclid=$ligne["ID"];
		build_progress_install("{uninstalling} {APP_IETD} Server ID $aclid",90);
		remove_lun($aclid);
	}
	build_progress_install("{restarting_artica_status}",95);
	system("/etc/init.d/artica-status restart --force");
	
	
	build_progress_install("{uninstalling} {APP_IETD} {success}",100);
}

function install_iscsi(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableISCSI",1);
	build_progress_install("{installing} {APP_IETD}",25);
	iscsitarget_debian();
	build_progress_install("{installing} {APP_IETD}",50);
	clients();
	build_progress_install("{installing} {APP_IETD}",60);
	build();
	build_progress_install("{installing} {APP_IETD}",80);
	start(true);
	build_progress_install("{installing} {APP_IETD}",90);
	checknodes();
	build_progress_install("{restarting_artica_status}",95);
	system("/etc/init.d/artica-status restart --force");
	
	build_progress_install("{installing} {APP_IETD} {success}",100);
}

function iscsitarget_debian(){
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] iscsitarget_debian()\n";}
	if(!is_file('/usr/sbin/update-rc.d')){echo "[".__LINE__."] iscsitarget: [INFO] /usr/sbin/update-rc.d no such binary\n";return;}
	$unix=new unix();
	$sock=new sockets();
	$ietd=$unix->find_program("ietd");
	$php=$unix->LOCATE_PHP5_BIN();
	$deflog_start="Starting......: ".date("H:i:s")." [INIT]: iSCSI target";
	$deflog_sstop="Stopping......: ".date("H:i:s")." [INIT]: iSCSI target";
	$php5=$unix->LOCATE_PHP5_BIN();

			$f[]="#!/bin/sh";
			$f[]="#";
			$f[]="### BEGIN INIT INFO";
			$f[]="# Provides:          iscsitarget";
			$f[]="# Required-Start:    \$network \$time";
			$f[]="# Required-Stop:     \$network \$time";
			$f[]="# Default-Start:     3 4 5";
			$f[]="# Default-Stop:      0 1 6";
			$f[]="# Short-Description: Starts and stops the iSCSI target";
			$f[]="### END INIT INFO";
			$f[]="";
			$f[]="PID_FILE=/var/run/iscsi_trgt.pid";
			$f[]="CONFIG_FILE=/etc/ietd.conf";
			$f[]="DAEMON=$ietd";
			$f[]="";
			$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
			$f[]="";
			$f[]="# Don't touch this \"memsize thingy\" unless you are blessed";
			$f[]="# with knowledge about it.";
			$f[]="MEM_SIZE=1048576";
			$f[]="";
			$f[]=". /lib/lsb/init-functions # log_{warn,failure}_msg";
			$f[]="";
			$f[]="configure_memsize()";
			$f[]="{";
			$f[]="    if [ -e /proc/sys/net/core/wmem_max ]; then";
			$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/wmem_max";
			$f[]="    fi";
			$f[]="";
			$f[]="    if [ -e /proc/sys/net/core/rmem_max ]; then";
			$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/rmem_max";
			$f[]="    fi";
			$f[]="";
			$f[]="    if [ -e /proc/sys/net/core/wmem_default ]; then";
			$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/wmem_default";
			$f[]="    fi";
			$f[]="";
			$f[]="    if [ -e /proc/sys/net/core/rmem_default ]; then";
			$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/rmem_default";
			$f[]="    fi";
			$f[]="";
			$f[]="    if [ -e /proc/sys/net/ipv4/tcp_mem ]; then";
			$f[]="        echo \"\${MEM_SIZE} \${MEM_SIZE} \${MEM_SIZE}\" > /proc/sys/net/ipv4/tcp_mem";
			$f[]="    fi";
			$f[]="";
			$f[]="    if [ -e  /proc/sys/net/ipv4/tcp_rmem ]; then";
			$f[]="        echo \"\${MEM_SIZE} \${MEM_SIZE} \${MEM_SIZE}\" > /proc/sys/net/ipv4/tcp_rmem";
			$f[]="    fi";
			$f[]="";
			$f[]="    if [ -e /proc/sys/net/ipv4/tcp_wmem ]; then";
			$f[]="        echo \"\${MEM_SIZE} \${MEM_SIZE} \${MEM_SIZE}\" > /proc/sys/net/ipv4/tcp_wmem";
			$f[]="    fi";
			$f[]="}";
			$f[]="";
			$f[]="RETVAL=0";
			$f[]="";
			$f[]="ietd_start(){";
			$f[]="	log_daemon_msg \"$deflog_start service\"";
			$f[]="	configure_memsize";
			$f[]="	modprobe -q crc32c && modprobe -q scsi_mod";
			$f[]="	RETVAL=\$?";
			$f[]="	if [ \$RETVAL != \"0\" ] ;  then ";
			$f[]="		log_end_msg 1";
			$f[]="		exit \$RETVAL";
			$f[]="	fi";
			
			$f[]="}";
			$f[]="	";
			
			$f[]="";
			$f[]="case \"\$1\" in";
			$f[]="  start)";
			$f[]="        ietd_start";
			$f[]="  	  $php /usr/share/artica-postfix/exec.iscsi.php --start";
			$f[]="        ;;";
			$f[]="  stop)";
			$f[]="        $php /usr/share/artica-postfix/exec.iscsi.php --stop";
			$f[]="        ;;";
			$f[]="  restart|force-reload)";
			$f[]="        $php /usr/share/artica-postfix/exec.iscsi.php --stop";
			$f[]="		  sleep 1";
			$f[]="        $php /usr/share/artica-postfix/exec.iscsi.php --build";
			$f[]="  	  $php /usr/share/artica-postfix/exec.iscsi.php --start";
			$f[]="        ;;";
			$f[]="  status)";
			$f[]="	status_of_proc -p \$PID_FILE \$DAEMON \"iSCSI enterprise target\" && exit 0 || exit \$?";
			$f[]="	;;";
			$f[]="  *)";
			$f[]="        log_action_msg \"Usage: \$0 {start|stop|restart|status}\"";
			$f[]="        exit 1";
			$f[]="esac";
			$f[]="";
			$f[]="exit 0";
			$f[]="";

			$INITD_PATH="/etc/init.d/iscsitarget";
			echo "[".__LINE__."] iscsitarget: [INFO] Writing /etc/init.d/iscsitarget with new config\n";
			@unlink($INITD_PATH);
			@file_put_contents($INITD_PATH, @implode("\n", $f));
			@chmod($INITD_PATH,0755);

			if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");}


}

function dump_config(){
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$targetcli=$unix->find_program("targetcli");
	
	system("$targetcli saveconfig $tmpfile >/dev/null 2>&1");
	$class=json_decode(@file_get_contents($tmpfile));
	
	
	@unlink($tmpfile);
	
	var_dump($class);
	
	foreach($class->storage_objects as $storage){
		
		$ARRAY[$storage->plugin][$storage->dev]["NAME"]= $storage->name;
		if(property_exists($storage,"size")){
			$ARRAY[$storage->plugin][$storage->dev]["SIZE"]= $storage->size;
		}
		$ARRAY[$storage->plugin][$storage->dev]["write_back"]= $storage->write_back;
		$ARRAY[$storage->plugin][$storage->dev]["wwn"]= $storage->wwn;
		$ARRAY[$storage->name]["path"]="/backstores/$storage->plugin/$storage->name";
		$ARRAY["backstores"]["/backstores/$storage->plugin/$storage->name"]=true;

	}
	foreach($class->targets as $targets){
		echo "[".__LINE__."] $targets->wwn --> ".$targets->tpgs[0]->luns[0]->storage_object."\n";
		$ARRAY[$targets->wwn][$targets->tpgs[0]->luns[0]->storage_object]=true;
		
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("iSCSIDumpArray", serialize($ARRAY));
	return $ARRAY;

}


function create_wwn($iqn){
	echo "[".__LINE__."] Create wwn $iqn\n";
	$unix=new unix();
	$targetcli=$unix->find_program("targetcli");
	$cmd="$targetcli /iscsi create $iqn";
	system($cmd);
	$ARRAY=dump_config();
	if(!isset($ARRAY["$iqn"])){return false;}
	return true;
}

function attach_lun($iqn,$folder_name){
	echo "[".__LINE__."] Attach $iqn with $folder_name\n";
	$ARRAY=dump_config();
	if(!isset($ARRAY[$folder_name])){
		echo "[".__LINE__."] $folder_name was not created\n";
	}
	$path=$ARRAY[$folder_name]["path"];
	echo "[".__LINE__."] Attach $folder_name with path $path\n";
	if(isset($ARRAY[$iqn][$path])){
		echo "[".__LINE__."] $folder_name ($path) Already attached\n";
		return true;
	}
	
	$unix=new unix();
	$targetcli=$unix->find_program("targetcli");
	$cmd="$targetcli /iscsi/$iqn/tpg1/luns create $path";
	echo $cmd."\n";
	system($cmd);
	$ARRAY=dump_config();
	if(!isset($ARRAY[$iqn][$path])){
		echo "[".__LINE__."] $folder_name ($path) failed to attach\n";
		return false;
	}
	return true;
}


function create_fileio($dev,$size,$ID,$shared_folder){
	echo "[".__LINE__."] Create fileio $dev for {$size}Gib\n";
	$unix=new unix();
	$targetcli=$unix->find_program("targetcli");
	$cmd="$targetcli /backstores/fileio create $shared_folder $dev {$size}G";
	system($cmd);
	$ARRAY=dump_config();
	if(!isset($ARRAY["fileio"][$dev])){return false;}
	if(!is_file($dev)){return false;}
	return true;		
}

function create_block($dev,$size,$ID,$shared_folder){
	echo "[".__LINE__."] Create block $dev\n";
	$unix=new unix();
	$targetcli=$unix->find_program("targetcli");
	$cmd="$targetcli /backstores/block create $shared_folder $dev";
	system($cmd);
	$ARRAY=dump_config();
	if(!isset($ARRAY["block"][$dev])){return false;}
	if(!is_file($dev)){return false;}
	return true;
}


?>