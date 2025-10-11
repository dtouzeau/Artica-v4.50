<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.mount.inc');
include_once(dirname(__FILE__).'/class.mysql.squid.builder.php');

class mysql_storelogs{
	private $ClassSQL;
	private $LogRotatePath=null;
	public $MySQLSyslogType=1;
	private $mysql_errornum=0;
	public $mysql_admin;
	public $mysql_password;
	public $mysql_server;
	public $MYSQL_CMDLINES;
	public $database="syslogs";
	private $mysqli_connection=false;
	public $SocketName="";
	public $ok=false;
	public $mysql_error;
	public $last_id=0;
	public $dbidsn="";
	public $mysql_port=3306;
	private $NOCHDB=array("mysql"=>true);
	public $start_error=null;
	private $MySQLLogsTypeTextAR=array("{not_set}","{server}","{client}");
	public $MYSQLTypeText;
	public $EnableSyslogDB=0;
	private $filename=null;
	private $EnableSargGenerator=0;
	private $mySar=0;
	private $mySarBin=null;
	private $category=null;
	private $EnableMySQLSyslogWizard;
	private $BackupSquidLogsUseNas=0;
	private $LogRotateMysql=0;
	private $EnableArticaMetaClient=0;
	
	function __construct(){
		$this->OutputDebugVerbose("Loading...");
		$this->PrepareMySQLClass();
		$sock=new sockets();
		if($GLOBALS["AS_ROOT"]){
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
			$GLOBALS["CLASS_SOCKETS"]=$sock;
		}
		
		if(!function_exists("rotate_events")){include_once("/usr/share/artica-postfix/framework/class.unix.inc"); }
		$this->EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
		if(!is_numeric($this->EnableSargGenerator)){$this->EnableSargGenerator=0;}
		if(isset($GLOBALS["CLASS_UNIX"])){
			$sarg=$GLOBALS["CLASS_UNIX"]->find_program("sarg");
			if(strlen($sarg)<3){$this->EnableSargGenerator=0;}
			$this->mySarBin=$GLOBALS["CLASS_UNIX"]->find_program("mysar");
			if(strlen($this->mySarBin)>3){$this->mySar=1;}
			
		}
		
		$this->EnableMySQLSyslogWizard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQLSyslogWizard"));
		$this->EnableSyslogDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogDB"));
		$this->BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
		$this->LogRotateMysql=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateMysql"));
		$this->EnableArticaMetaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaClient"));
		
		if($this->BackupSquidLogsUseNas==1){$this->MySQLSyslogType=3; $this->LogRotateMysql=0;}
		if($this->EnableArticaMetaClient==1){
			$this->LogRotateMysql=0;
			$this->MySQLSyslogType=4;
			$this->EnableSyslogDB=0;
		}
		
		
		if($this->EnableSyslogDB==0){return;}
		$this->OutputDebugVerbose("DATABASE_EXISTS($this->database) ?");
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->OutputDebugVerbose("CREATE_DATABASE($this->database) ?");
			$this->CREATE_DATABASE($this->database);
			$this->OutputDebugVerbose("CheckTables() ?");
			$this->CheckTables();
		}		
		
	}
	
	private function PrepareMySQLClass(){
		$sock=new sockets();
		unset($GLOBALS["MYSQL_PARAMETERS"]);
		if(isset($_SESSION["MYSQL_PARAMETERS"])){unset($_SESSION["MYSQL_PARAMETERS"]);}
		$this->MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
		$this->EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
		if(!is_numeric($this->EnableSyslogDB)){$this->EnableSyslogDB=0;}
		if(!is_numeric($this->MySQLSyslogType)){$this->MySQLSyslogType=1;}
		if($this->MySQLSyslogType==0){$this->MySQLSyslogType=4;}
		$this->LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($this->LogRotatePath==null){$this->LogRotatePath="/home/logrotate";}
		$TuningParameters=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogParams")));
		if(isset($this->MySQLLogsTypeTextAR[$this->MySQLSyslogType])){
			$this->MYSQLTypeText=$this->MySQLLogsTypeTextAR[$this->MySQLSyslogType];
		}
		
		$this->OutputDebugVerbose("MySQLSyslogType=$this->MySQLSyslogType");
		if($this->MySQLSyslogType==1){
			if($this->EnableSyslogDB==0){
				$this->MySQLSyslogType=4;
			}
		}
	
		if($this->MySQLSyslogType==1){

		}
		
		
		if($this->MySQLSyslogType==2){
			$this->EnableSyslogDB=1;
			
		}
		
		if($this->MySQLSyslogType>=3){ return; }
		
		return $this->ClassSQL;
	}
	
	private function DATABASE_EXISTS($database){

	}
	private FUNCTION CREATE_DATABASE($database){

	}
	

	FUNCTION TABLE_SIZE($table){

	
	}
	
	
	public function GRANT_PRIVS($hostname,$username,$password){

	}
	
	
	private function THIS_CREATE_DATABASE($database){
		
	}
	
	
	private function THIS_DATABASE_EXISTS($database,$nocache=false){
		if($this->MySQLSyslogType>=3){ return; }
		$key=__CLASS__.__FUNCTION__;
		if(!$nocache){
			if(isset($_SESSION[$key][$database])){
				if($_SESSION[$key][$database]==true){return true;}
			}
		}
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysqli_query($this->mysqli_connection,$sql);
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION[$key][$database]=true;
				return true;
			}
		}
	
		return false;
	
	}

	public function THIS_BD_CONNECT($noretry=false,$called=null){
		if($this->MySQLSyslogType>=3){ return; }
		if($GLOBALS["VERBOSE"]){echo "THIS_BD_CONNECT()<br>\n";}
		
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection()<br>\n";}
		if($this->THIS_TestingConnection(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection() -> TRUE<br>\n";}
			return true;
		}
		return false;
	}
	
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}	
	
	private function THIS_TestingConnection($noretry=false,$called=null){
		if($this->MySQLSyslogType>=3){ return; }
		if($this->mysqli_connection){return true;}
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		if($this->MySQLSyslogType==1){
			if($GLOBALS["VERBOSE"]){echo "mysqli_connect -> /var/run/syslogdb.sock<br>\n";}
			
			if(!$this->is_socket("/var/run/syslogdb.sock")){
				$this->mysql_error="/var/run/syslogdb.sock no such socket";
				$this->OutputDebugVerbose("/var/run/syslogdb.sock !!! false");
				$GLOBALS["THIS_TestingConnection"]=false;
				return false;
			}
			
			$bd=@mysqli_connect(":/var/run/syslogdb.sock","root",null);
			
			if($bd){
				$this->mysqli_connection=$bd;
				$GLOBALS["THIS_TestingConnection"]=true;
				return true;
			}
			if($GLOBALS["VERBOSE"]){echo "mysqli_connect /var/run/syslogdb.sock -> error<br>\n";}
			$des=@mysqli_error(); $errnum=@mysqli_errno();
			$this->writelogs("mysqli_connect(\":/var/run/syslogdb.sock\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			$this->mysql_error="/var/run/syslogdb.sock Error:$errnum \"$des\"";
			return false;
		}
		
		if($this->MySQLSyslogType==2){
			
			$bd=mysqli_connect(null,$this->mysql_admin,$this->mysql_password,null,0,$this->SocketPath);
			if($bd){$this->mysqli_connection=$bd;return true;}
			$des=@mysqli_error(); $errnum=@mysqli_errno();
			$this->writelogs("mysqli_connect(\":/var/run/syslogdb.sock\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			return false;
		}
			
	}
	
	public function BD_CONNECT(){
		$this->ok=false;
		return false;
	
	}
	
	private function OutputDebugVerbose($text){
	if(!$GLOBALS["VERBOSE"]){return;}
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$filename=basename($trace[1]["file"]);
	$function=$trace[1]["function"];
	$line=$trace[1]["line"];
	echo "$filename:: $function $text in line $line<br>\n";
}
	
	private function THIS_COUNT_ROWS($table,$database){
		return 0;
		}

	public function COUNT_ROWS($table,$database=null){
		if($this->MySQLSyslogType>=3){ return true; }
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
	
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if(!method_exists($this->ClassSQL, "COUNT_ROWS")) {
			return $this->THIS_COUNT_ROWS($table,$database);
		}
		
		
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			$this->ok=false;
			$this->mysql_error=$this->ClassSQL->mysql_error;
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			$this->writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
		
	
	public function QUERY_SQL($sql,$database=null){
		$this->ok=false;
	}
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
	
		$this->ok=false;
	}	
	
	public function FIELD_EXISTS($table,$field,$database=null){
		$this->ok=false;
	}	
	
	public function SHOW_STATUS(){
		if($this->MySQLSyslogType>=3){ return true; }
		$sql="SHOW STATUS;";
		$this->BD_CONNECT();
		$results=@mysqli_query($this->mysqli_connection,$sql);
		$errnum=@mysqli_error($this->mysqli_connection);
		$des=@mysqli_error($this->mysqli_connection);
		$this->mysql_error=$des;
		
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
		}
		return $array;
	}		
	
	public function COUNT_ALL_TABLES(){
		if($this->MySQLSyslogType>=3){ return true; }
		if(!$this->BD_CONNECT()){return false;}
		$sql="SELECT COUNT(*) as tcount, (SUM(`INDEX_LENGTH`)+ SUM(`DATA_LENGTH`)) as x FROM information_schema.tables WHERE table_schema = '$this->database'";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
		return array($ligne["tcount"],$ligne["x"]);
	}	

	public function checkTables(){
		if($this->MySQLSyslogType>=3){ return true; }
		if(!$this->BD_CONNECT()){return false;}
		
		}		
	
	public function TABLE_EXISTS($table,$database=null){
		return false;
	}

	public function DelteItem($ID){
	
	}
	
	public function DelteAccessItem($ID){

	}	
	
	public function ExtractFile($filepath,$storeid){
	
	}
	
	public function ExtractAccessFile($filepath,$storeid){
		if($this->MySQLSyslogType>=3){ return true; }
	
	}	
	
	private function ConvertGZToBzip($filesource){
		$t=time();
		$fromTime=time();
		$fileDest=str_replace(".gz", ".bz2", $filesource);
		$unix=new unix();
		$gunzip=$unix->find_program("gunzip");
		$bzip2=$unix->find_program("bzip2");
		$cmd="$gunzip --to-stdout \"$filesource\" | $bzip2 > \"$fileDest\"";
		shell_exec($cmd);
		if(!is_file($fileDest)){return null;}
		return $fileDest;
	}

	
	
	
	public function ROTATE_ACCESS_TOMYSQL($filename,$sourceDate=null){
		$sock=new sockets();
		$unix=new unix();
		$this->category="all";
		$this->filename=$filename;
		if(strpos($filename, "access.")>0){$this->category="proxy";}
		$taskid=0;
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($this->filename));}
		$size=@filesize($filename);
		$this->events("InjectFile_accesslog($filename) - $size Bytes");
		if(!$this->InjectFile_accesslog($filename, $sourceDate)){
			$this->events("InjectFile_accesslog($this->filename) RETURN FALSE, do not delete the file...");
			return false;
		}
		@unlink($this->filename);
		return true;
	}
	
	private function TestCompressed($filename=null,$destile=null){
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		$COMPRESSEDS["bz"]=true;
		
		if($filename<>null){
			if(!is_file($filename)){
				$this->events("$filename No such file");
				return false;
			}
			$this->filename=$filename;
		}else{
			if($this->filename<>null){
				$filename=$this->filename;
			}
		}
		
		if(!is_file($filename)){
			$this->events("$filename No such file");
			return false;
		}
		
		$size=@filesize($filename);
		
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		
		if(isset($COMPRESSEDS[$extension])){
			$this->events("$filename ( $size bytes ) Already compressed return $filename");
			return $filename;
		}
		
		$size=@filesize($filename);
		if(!isset($COMPRESSEDS[$extension])){
			$unix=new unix();
			if($destile==null){$destile=$filename.".gz";}
			$this->events("Compress $filename ( $size Bytes ) to $destile");
			if(!$unix->compress($filename, $destile)){
				$this->events("Compress $filename ( $size Bytes ) FAILED");
				return false;
			}
			$this->events("Removing $filename");
			$size=@filesize($filename);
			$this->events("New compressed file $destile ( $size bytes )");
			@unlink($filename);
			return $destile;
		}
	
		return $filename;
		
	}
	

	
	private function MySarExec($filename){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$q=new mysql_squid_builder();
		$NICE=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
		
		$q->PRIVILEGES("mysar",$GLOBALS["CLASS_UNIX"]->GetUniqueID());
		$f[]="username=mysar";
		$f[]="password=".$GLOBALS["CLASS_UNIX"]->GetUniqueID();
		$f[]="database=squidlogs";
		
		if($q->mysql_port<>3306){$q->EnableSquidRemoteMySQL=0;}
		if($q->EnableSquidRemoteMySQL==1){	
			echo "Build config Use remote MySQL server = $q->mysql_admin@$q->mysql_server::$q->mysql_port / $q->mysql_password\n";
			$f[]="server={$q->mysql_server}:$q->mysql_port";
		}else{
			echo "Build config Use Local MySQL server = $q->SocketName\n";
			$f[]="server=127.0.0.1";
			
		}
		
		$f[]="pidfile=/var/run/mysar.pid";
		@file_put_contents("/etc/mysar.conf", @implode("\n", $f));
		@mkdir("/usr/etc");
		@file_put_contents("/usr/etc/mysar.conf", @implode("\n", $f));
		
		
		
		$cmdline="$NICE $this->mySarBin --logfile $filename --stats --offline 2>&1";
		$results=array();
		$t=time();
		
		exec($cmdline,$results);
		$took=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($t,time());
		rotate_events("MySar executed on $filename took:$took",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		if($GLOBALS["VERBOSE"]){echo @implode("\n", $results);}
		
	}
	
	
	
	public function agregate_access_logs($filepath){
		if($filepath=="."){return false;}
		if($filepath==".."){return false;}
		if(is_dir($filepath)){return false;}
		$sock=new sockets();
		$this->events("Aggregate $filepath",__FUNCTION__,__LINE__);
		
		if(!is_file($filepath)){
			$this->events("$filepath no such file !",__FUNCTION__,__LINE__);
			$GLOBALS["agregate_access_logs"][$filepath]=false;
			return false;
		}
		if(isset($GLOBALS["agregate_access_logs"][$filepath])){
			return false;
		}
		$basename=basename($filepath);
		if(preg_match("#^access\.merged\.#",$basename)){return;}
		$LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
		if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}
		$LogRotatePathWork="$LogRotatePath/merged";
		$BigMerged="$LogRotatePathWork/access.merged.log";

	
		
		$MERGED_FILES=unserialize(@file_get_contents("/etc/artica-postfix/accesslogs_merged.db"));
		

		
		@mkdir($LogRotatePathWork,0755,true);
		$md5file=md5_file($filepath);
		if(isset($MERGED_FILES[$md5file])){return;}
		$MERGED_FILES[$md5file]=true;
		$sep=">>";
		if(!is_file($BigMerged)){$sep=">";}
		if(is_file($BigMerged)){
			$size=@filesize($BigMerged);
			$size=$size/1024; // KB
			$size=$size/1024; //MB
			if($size>$SquidRotateMergeMaxFile){
				$this->events("$BigMerged will be rotated ( $size MB)",__FUNCTION__,__LINE__);
				@copy($BigMerged, "$LogRotatePath/work/access.merged.".time().".log");
				@unlink($BigMerged);
				$sep=">";
			}
		}
		
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		$COMPRESSEDS["bz"]=true;
		$size=@filesize($filepath);
		$extension = pathinfo($filepath, PATHINFO_EXTENSION);
		if(isset($COMPRESSEDS[$extension])){
			$this->events("$filepath already compressed, just save it",__FUNCTION__,__LINE__);
			$destfile="$LogRotatePathWork/".time().".$extension";
			@copy($filepath, $destfile);
			@file_put_contents("/etc/artica-postfix/accesslogs_merged.db", serialize($MERGED_FILES));
			return;
		}
		
		
		
		$unix=new unix();
		$cat=$unix->find_program("cat");
		
		shell_exec("$cat $filepath $sep $BigMerged");
		$size=@filesize($BigMerged);
		$size=$size/1024; // KB
		$size=$size/1024; //MB
		$this->events("$filepath has been merged to $BigMerged ( $size MB)",__FUNCTION__,__LINE__);
		@file_put_contents("/etc/artica-postfix/accesslogs_merged.db", serialize($MERGED_FILES));
		return;
		
	}
	
	public function FILE_SOURCE_TO_WORK($filepath){
		if($filepath=="."){return false;}
		if($filepath==".."){return false;}
		if(is_dir($filepath)){return false;}
		if(!is_file($filepath)){
			$this->events("$filepath no such file !");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			return false;
		}
		if(isset($GLOBALS["FILE_SOURCE_TO_WORK"][$filepath])){
			$this->events("$filepath aloready done, return  {$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]}");
			return $GLOBALS["FILE_SOURCE_TO_WORK"][$filepath];}
		
		$this->events("->TestCompressed($filepath) ");
		$filepath=$this->TestCompressed($filepath);
		if(!$filepath){
			$this->events("TestCompressed Return false");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			return false;
		}
		$sock=new sockets();
		$LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
		if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}

        $LogRotatePathWork="$LogRotatePath/work";

        if(!is_dir($LogRotatePathWork)) {
            @mkdir($LogRotatePathWork, 0755, true);
        }
		
		
		if(!is_dir($LogRotatePathWork)){
			$this->events("$LogRotatePathWork no such directory or permission denied !");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			return false;
		}
		$DirectorySource=dirname($filepath);
		
		$this->events("Source directory = $DirectorySource destination directory is `$LogRotatePathWork`");
		
		if($DirectorySource==$LogRotatePathWork){
			$this->events("Source directory is the same of destination `$LogRotatePathWork`");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=$filepath;
			return $filepath;
		}
		
		if(is_link($DirectorySource)){
			$DirectorySource=@readlink($DirectorySource);
			$this->events("Source directory symbolic link = $DirectorySource");
			$this->events("Source directory = $DirectorySource destination directory is `$LogRotatePathWork`");
		}
		
		
		if($DirectorySource==$LogRotatePathWork){
			$this->events("Source directory is the same of destination `$LogRotatePathWork`");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=$filepath;
			return $filepath;
		}		

		
		$size=@filesize($filepath);
		$Basename=basename($filepath);
		
		$this->events("$filepath filename is `$Basename` size is ( $size bytes )");
		$DestFile="$LogRotatePathWork/$Basename";
		$this->events("Destination = `$DestFile`");
		
		
		if(is_file($DestFile)){
			$md5Dest=md5_file($DestFile);
			$md5Srource=md5_file($filepath);
			if($md5Dest==$md5Srource){
				$this->events("Destination Already exists, and is the same");
				@unlink($filepath);
				return $DestFile;
			}
			$this->events("Destination Already exists, create a new file");
			$ext = pathinfo($Basename, PATHINFO_EXTENSION);
			$basenameFF=str_replace(".$ext", "", $Basename);
			$basenameFF=$basenameFF.".".time().".$ext";
			$DestFile="$LogRotatePathWork/$basenameFF";
			$this->events("Desintation = `$DestFile`");
		}
		
		
		if(!@copy($filepath, $DestFile)){
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			$this->events("Copy $filepath -> $DestFile FAILED !!!");
			return false;
		}
		$sizeDest=@filesize($DestFile);
		$this->events("Copy $filepath -> $DestFile ( $sizeDest bytes ) SUCCESS");
		$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=$DestFile;
		$this->events("Removing source $filepath");
		@unlink($filepath);
		return $DestFile;
		
		
	}
	
	
	
	public function ROTATE_TOMYSQL($filename,$sourceDate=null){
		$sock=new sockets();
		$unix=new unix();
		$taskid=0;
		$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
		$this->filename=$filename;
		
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($this->filename));}
		
		
		$filename=$this->FILE_SOURCE_TO_WORK($filename);
		if(!$filename){return false;}
		$this->filename=$filename;
		
		
		if($GLOBALS["VERBOSE"]){echo "EnableSyslogDB: $this->EnableSyslogDB - BackupSquidLogsUseNas: $BackupSquidLogsUseNas\n";}
		
		if($this->MySQLSyslogType==4){
			if(!$this->InjectFileToDir($filename)){
				return false;
			}
			return true;
		}
		
		if($BackupSquidLogsUseNas==1){
			if(!$this->InjectFileToNas($filename, $sourceDate)){return false;}
			return true;
		}
		
		
		if($this->EnableSyslogDB==1){
			if($this->MySQLSyslogType>=3){return;}
			if(!$this->InjectFile($filename, $sourceDate)){return false;}
			@unlink($filename);
			return true;
		}


		return false;
	}

	
	private function TransformName($filepath){
		$unix=new unix();
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));
		
		$LogRotatePathWork="$this->LogRotatePath/work";	
		@mkdir("$this->LogRotatePath/work",0755);
		if(!is_dir($LogRotatePathWork)){@mkdir($LogRotatePathWork,0777);}
		@chmod($LogRotatePathWork, 0777);
		$basenameFF=null;
		$DestinationFile="$LogRotatePathWork/$basename";
		if(preg_match("#-TASK-([0-9]+)#",$basename,$re)){$taskid=$re[1];}
		if(is_file($DestinationFile)){
			$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
			$basenameFF=basename($DestinationFile);
			$basenameFF=str_replace(".$ext", "", $basenameFF);
			$basenameFF=$basenameFF.".".time().".$ext";
			$DestinationFile=str_replace(basename($DestinationFile), $basenameFF, $DestinationFile);
		}
		
		return $DestinationFile;
	}
	
	private function InjectFileToDir($filepath){
		$sock=new sockets();
		$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
		if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
		$dirname=dirname($filepath);
		if($dirname==$BackupMaxDaysDir){return true;}
		$basename=basename($filepath);
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);
		$destfile="$BackupMaxDaysDir/$basename";
		if(is_file($destfile)){
			$basename=str_replace(".$ext", time().".$ext", $basename);
			$destfile="$BackupMaxDaysDir/$basename";
		}
		if(!@copy($filepath, $destfile)){
			rotate_events("Failed to backup $filepath to $destfile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@unlink($destfile);
			return false;
		}
		rotate_events("Success backup $destfile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		@unlink($filepath);
		return true;
		
		
	}
	
	private function InjectFileToNas($filepath){
		if(is_dir($filepath)){
			$this->events("$filepath is a directory, skip it",__FUNCTION__,__LINE__);
			return;}
		
		$BaseName=basename($filepath);
		if($BaseName=="."){return;}
		if($BaseName==".."){return;}
		$size=@filesize($filepath);
		
		
		
		if($size==0){
			$this->events("$filepath 0 bytes, skip and return true",__FUNCTION__,__LINE__);
			rotate_events("$filepath 0 bytes, skip and return true",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return true;
		}
		
		$sock=new sockets();
		$users=new usersMenus();
		$unix=new unix();
		$myHostname=$unix->hostname_g();
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		
		if($BackupSquidLogsNASIpaddr==null){
			$this->events("Backup via NAS is disabled, skip",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return false;
		}
		
		$mountPoint="/mnt/BackupSquidLogsUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			rotate_events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			
			if($BackupSquidLogsNASRetry==0){return;}
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				rotate_events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				return;
			}
				
		}
		
		$DirSuffix=null;
		
		if(preg_match("#ziproxy#", $BaseName)){
			$DirSuffix="/proxy_access";
		}
		if(preg_match("#access\.log#", $BaseName)){
			$DirSuffix="/proxy_access";
		}
		if(preg_match("#cache\.log#", $BaseName)){
			$DirSuffix="/proxy_service";
		}
		
		if(preg_match("#(syslog|daemon|user|)\.log#", $BaseName)){
			$DirSuffix="/system";
		}		
		$this->events("Hostname=$myHostname Suffix = $DirSuffix $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder",__FUNCTION__,__LINE__);
		$BackupMaxDaysDir="$mountPoint/artica-backup-syslog/{$myHostname}$DirSuffix";
		@mkdir("$BackupMaxDaysDir",0755,true);
		
		if(!is_dir($BackupMaxDaysDir)){
			$this->events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			rotate_events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			squid_admin_mysql(0,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$mount->umount($mountPoint);
			return false;
		}
		
		
		$t=time();
		@file_put_contents("$BackupMaxDaysDir/$t", time());
		if(!is_file("$BackupMaxDaysDir/$t")){
			$this->events("Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed",__FUNCTION__,__LINE__);
			rotate_events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			squid_admin_mysql(0,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$mount->umount($mountPoint);
			return false;
		}
		
		
		@unlink("$BackupMaxDaysDir/$t");
		
		$fileSizeSource=@filesize($filepath);
		
		$targetFile="$BackupMaxDaysDir/".basename($filepath);
		if(!copy($filepath, $targetFile)){
			$this->events("Fatal Failed to copy $filepath -> $targetFile");
			rotate_events("Failed to copy $filepath {$fileSizeSource}Bytes -> $targetFile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@unlink($targetFile);
			$mount->umount($mountPoint);
			echo "Failed to copy $filepath -> $targetFile\n";
			return;
		}
		
		$filesizeDest=@filesize($targetFile);
		
		
		if($fileSizeSource<>$filesizeDest){
			$this->events("Success to backup $targetFile to $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/artica-backup-syslog/$users->hostname",__FUNCTION__,__LINE__);
			$this->events("Size differ, source = {$fileSizeSource}Bytes destination = {$filesizeDest}Bytes");
			@unlink($targetFile);
			$mount->umount($mountPoint);
			return false;
		}
		$filesizeDest=$filesizeDest/1024;
		
		$this->events("Success to backup $targetFile ({$filesizeDest}KB)to $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/artica-backup-syslog/$users->hostname",__FUNCTION__,__LINE__);
		rotate_events("Success to backup $targetFile ({$filesizeDest}KB)to $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/artica-backup-syslog/$users->hostname",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		$this->events("Removing $filepath",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$mount->umount($mountPoint);
		return true;
		
		
	}
	
	
	
	public function InjectFile($filepath,$sourceDate=null){
		$unix=new unix();
		$sock=new sockets();
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}else{
				if(isset($trace[1])){
					$file=basename($trace[0]["file"]);
					$function=$trace[0]["function"];
					$line=$trace[0]["line"];
				}
			}
				
			$called="called by $file $function() in line $line";
				
		}
		
		if(is_dir($filepath)){return;}
		$BaseName=basename($filepath);
		if($BaseName=="."){return;}
		if($BaseName==".."){return;}
		if(is_link($filepath)){$filepath=@readlink($filepath);}
		

		
		$filepath=$this->FILE_SOURCE_TO_WORK($filepath);
		if(!$filepath){return false;}
		
		$FILE_SOURCE_TO_DELETE=$filepath;
		$SourceFile=$filepath;
		$DestinationFile=$filepath;
		$basenameFF=basename($filepath);
		$taskid=0;
		
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));}
		$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
		
	
		if($this->MySQLSyslogType>=3){
			if(!$this->InjectFileToNas($filepath)){
				$this->events("MySQLSyslogType:3, Failed to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
				return false;
			}
			$this->events("Success to copy $filepath => N.A.S");
			return true;
		}
		
		if($BackupSquidLogsUseNas==1){
			if(!$this->InjectFileToNas($filepath)){
				$this->events("BackupSquidLogsUseNas=1, Failed to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
				return false;
			}	
			$this->events("Success to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
			return true;
		}
		
		
		if(preg_match("#^access\.log#", $basename)){
			if(!$this->InjectFile_accesslog($filepath, $sourceDate)){return false;}
			$this->events("Return true for $filepath",__FUNCTION__,__LINE__);
			return true;
		}
	
		
		$this->events("Info: Next destination \"$DestinationFile\"",__FUNCTION__,__LINE__);
	
		$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
		$filesize=$unix->file_size($DestinationFile);		
		
		$fcontent=null;
		
		
		if($GLOBALS["VERBOSE"]){echo "Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)\n";}
		$this->events("Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)");
		
		$fcontent=mysql_escape_string2(@file_get_contents($DestinationFile));
		$sql = "INSERT INTO `files_store` (`filecontent`) VALUES( '$fcontent' )";
		$this->events("Info:INSERTING INTO files_store $this->mysql_server:$this->mysql_port ($this->SocketName)");
		
		$this->QUERY_SQL($sql);
		$fcontent=null;
		
		if(!$this->ok){
			@unlink($DestinationFile);
			$this->events("$q->mysql_error, $sql");
			return false;
		}	

		$ID=$this->last_id;
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){
			$this->events("$basenameFF: BAD ID = $ID");
			return false;
		}
		$this->events("$basenameFF: New ID = $ID");
		@unlink($DestinationFile);
		$zmd5=md5("$hostname$basenameFF$taskid$filesize$sourceDate");
		$sql="INSERT INTO files_info (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){
			if($GLOBALS["VERBOSE"]){echo "$q->mysql_error, $sql\n";}
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM files_store WHERE ID=$ID");
			return false;
		}
		$this->events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__LINE__,"logrotate");
		$this->events("removing \"$FILE_SOURCE_TO_DELETE\"");
		@unlink($FILE_SOURCE_TO_DELETE);
		return true;
		
	}
	
	public function InjectFile_accesslog($filepath,$sourceDate=null){
		$basename=basename($filepath);
		if($basename=="."){return;}
		if($basename==".."){return;}
		
		
		$taskid=0;
		$unix=new unix();

		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}else{
				if(isset($trace[1])){
					$file=basename($trace[0]["file"]);
					$function=$trace[0]["function"];
					$line=$trace[0]["line"];
				}
			}
			$called="called by $file $function() in line $line";
		}
		
		$this->events("Info: $called");
		if(!is_file($filepath)){
			$this->events("$filepath no such file");
			return false;
		}
		
		$this->agregate_access_logs($filepath);
			
	
		
		
		$size=@filesize($filepath);

		
		
		
		$this->events("FILE_SOURCE_TO_WORK($filepath) ( $size bytes )");
		$filepath=$this->FILE_SOURCE_TO_WORK($filepath);
		if(!$filepath){return false;}
		$FILE_SOURCE_TO_DELETE=$filepath;
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$basenameFF=$basename;
		
		
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));}
		$this->events("Info: want to backup $filepath ($sourceDate)",__FUNCTION__,__LINE__);
		
		if($this->MySQLSyslogType==3){
			if(!$this->InjectFileToNas($filepath)){
				$this->events("MySQLSyslogType:3 Failed to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
				return false;
			}
			$this->events("Success to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
			return true;
		}
		
		if($this->MySQLSyslogType==4){
			if(!$this->InjectFileToDir($filepath)){
				$this->events("MySQLSyslogType:4, Failed to copy $filepath => LOCAL ( InjectFileToDir return false )",__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			$this->events("Success to copy $filepath => LOCAL",__FUNCTION__,__LINE__);
			return true;
		}
	
		if(!$this->TABLE_EXISTS("access_store")){$this->checkTables();}
	
	
		$filesize=@filesize($filepath);
		$this->events("InjectFile_accesslog:: Injecting $basename Task:$taskid  ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)",__FUNCTION__,__FILE__,__LINE__,$this->category,$GLOBALS["SCHEDULE_ID"]);
		
	
		if($this->MySQLSyslogType==1){
			$unix=new unix();
			if(!$unix->is_socket("/var/run/syslogdb.sock")){
				$this->events("InjectFile_accesslog: /var/run/syslogdb.sock no such socket...");
				return false;
			}
			
		}
		
		
		$fcontent=mysql_escape_string2(@file_get_contents($filepath));
		$sql = "INSERT INTO `access_store` (`filecontent`) VALUES( '$fcontent' )";
		$this->QUERY_SQL($sql);
		$fcontent=null;
	
		if(!$this->ok){
			@unlink("$filepath");
			$this->events("$q->mysql_error, $sql");
			@unlink($filepath);
			return false;
		}
	
		$ID=$this->last_id;
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){
			$this->events("$basenameFF: BAD ID = $ID");
			return false;
		}
		$this->events("$basenameFF: New ID = $ID");
		@unlink($filepath);
		$zmd5=md5("$hostname$basenameFF$taskid$filesize$sourceDate");
		$sql="INSERT INTO accesslogs (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
	
		$this->QUERY_SQL($sql);
		if(!$this->ok){
				
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM access_store WHERE ID=$ID");
			return false;
		}
		$this->events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__LINE__,"logrotate");
		$this->events("removing \"$FILE_SOURCE_TO_DELETE\"");
		@unlink($FILE_SOURCE_TO_DELETE);
		return true;
	
	}	
	
	public function events($text,$function=null,$line=0){
		$pid=@getmypid();
		if($GLOBALS["PROGRESS"]){echo "$text [$function/$line]\n";}

		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				if($function==null){$function=$trace[1]["function"];}
				if($line==0){$line=$trace[1]["line"];}
			}
			
			if(isset($trace[0])){
				$file=basename($trace[0]["file"]);
				if($function==null){$function=$trace[0]["function"];}
				if($line==0){$line=$trace[0]["line"];}
			}
		}
		
		if(!isset($GLOBALS["CLASS_UNIX"])){
			if(class_exists("unix")){
				$GLOBALS["CLASS_UNIX"]=new unix();
			}
		}

		if(isset($GLOBALS["CLASS_UNIX"])){
			if(class_exists("unix")){
				$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/artica-postfix/logrotate.debug",false,__CLASS__."/$function",$line,$file);
			}
		}
		
	}
		
	function writelogs($text=null,$function=null,$line=0){
			$class=__CLASS__;
			$file_source=PROGRESS_DIR."/mysql.debug";
			@mkdir('/usr/share/artica-postfix/ressources/logs/web');
			if(!is_numeric($line)){$line=0;}
			if(function_exists("writelogs")){writelogs("$text (L.$line)","$class/$function",__FILE__,$line);}
			if(!$GLOBALS["VERBOSE"]){return;}
			$logFile=$file_source;
			if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
			if (is_file($logFile)) {
				$size=filesize($logFile);
				if($size>1000000){unlink($logFile);}
			}
		
			$f = @fopen($logFile, 'a');
			$date=date("Y-m-d H:i:s");
			@fwrite($f, "$date:[$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
			@fclose($f);
				
		}

		
	private function THIS_QUERY_SQL($sql,$database=null,$called=null){
		$database=trim($database);
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}

		if(!$this->BD_CONNECT(false,$called)){
				if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
				if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}squid_admin_mysql(2, "MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
				$this->ok=false;
				$this->mysql_error=$this->mysql_error ." Error, unable to connect to MySQL server";
				return false;
			}
		
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				squid_admin_mysql(2, "MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
		
		
		if($database<>null){
			$ok=@mysqli_select_db($this->mysqli_connection,$database);
				if (!$ok){
					$errnum=@mysqli_errno($this->mysqli_connection);
					$des=@mysqli_error($this->mysqli_connection);
					if($GLOBALS["VERBOSE"]){echo "mysql_select_db [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
					$this->mysql_errornum=$errnum;
					$this->mysql_error=$des;
					$time=date('h:i:s');
				    $this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
				    $this->mysql_error="QUERY_SQL:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
				    $this->ok=false;if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
				    return null;
				}
			}
			
			ini_set("mysql.connect_timeout",20);
			if($GLOBALS["VERBOSE"]){echo "mysqli_query(.... Timeout:". ini_get("mysql.connect_timeout")."\n";}
			$results=mysqli_query($this->mysqli_connection,$sql);
			if(!$results){
				$errnum=@mysqli_errno($this->mysqli_connection);
				$des=@mysqli_error($this->mysqli_connection);
				
				if(preg_match('#Duplicate entry#',$des)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
				
					$this->ok=true;
					if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
					return true;
				}				
				
				
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				@mysqli_free_result($this->mysqli_connection);
				
			}
		
		$this->ok=true;
		$this->last_id=@mysqli_insert_id($this->mysqli_connection);	
		$result_return=$results;
		@mysqli_free_result($this->mysqli_connection);
		if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
		$this->mysqli_connection=false;
		return $result_return;
		

		}
		
		
}
