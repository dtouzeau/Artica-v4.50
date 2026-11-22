<?php
ini_set('error_reporting', E_ALL);
	
	/*$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
	ini_set('error_append_string',"");	
    */

	if(  isset($_REQUEST["VERBOSE"])  OR (isset($_REQUEST["verbose"]))   ){
		echo "STATISTICS APPLIANCE -> VERBOSE MODE\n";
		$GLOBALS["VERBOSE"]=true;
		SetDebug();
	}
	
	if(isset($_GET["test-connection"])){echo "\n\nCONNECTIONOK\n\n";die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.mysql.squid.builder.php');		
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.mysql.syslogs.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
    include_once('ressources/class.artica-logon.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

    foreach ($_REQUEST as $num=>$val){
		if(strlen($val)>500){$val=strlen($val)." bytes";}
		writelogs("From: {$_SERVER["REMOTE_ADDR"]} $num = $val",__FUNCTION__,__FILE__,__LINE__);
	}
    if(isset($_POST["HaClusterRegister"])){hacluster_receive_register();exit;}
	if( isset($_POST["SQUID_BEREKLEY"])){stats_berekley_upload();exit;}
    if( isset($_POST['legalslogs-upload']) ){LEGALSLOGS_UPLOADED();exit();}

	// ---------------------------------------------------------------------

    if(isset($_POST["hacluster-connect"])){
        $ConnectedIP=$_SERVER["REMOTE_ADDR"];
        hacluster_syslog("[ERROR]: $ConnectedIP Depreciated protocol, please Upgrade to latest version..");
        exit;
    }


if (isset($_GET["ldapcluster"])){
    LDAP_CLUSTER_INFOS();exit;
}
if (isset($_GET["legallogs-register"])) {
    LEGAL_LOGS_REGISTER();
    exit;
}
if (isset($_GET["legallogs-unregister"])) {
    LEGAL_LOGS_UNREGISTER();
    exit;
}
if (isset($_GET["elkMaster"])) {
    SetDebug();
    elkMaster();
    exit;
}
if (isset($_GET["ucarp"])) {
    SetDebug();
    ucarp_step1();
    exit;
}
if (isset($_GET["ucarp2"])) {
    SetDebug();
    ucarp_step2();
    exit;
}
if (isset($_GET["ucarp3"])) {
    SetDebug();
    ucarp_step3();
    exit;
}
if (isset($_GET["ucarp2-remove"])) {
    SetDebug();
    UCARP_REMOVE();
    exit;
}
if (isset($_POST["UCARP_DOWN"])) {
    UCARP_DOWN();
    exit;
}
if (isset($_GET["stats-appliance-compatibility"])) {
    stats_appliance_comptability();
    exit;
}
if (isset($_GET["stats-appliance-ports"])) {
    stats_appliance_ports();
    exit;
}
if (isset($_GET["stats-perform-connection"])) {
    stats_appliance_privs();
    exit;
}
if (isset($_GET["ufdbguardport"])) {
    stats_appliance_remote_port();
    exit;
}
if (isset($_POST["OPENSYSLOG"])) {
    OPENSYSLOG();
    exit;
}
if (isset($_GET["squid-table"])) {
    export_squid_table();
    exit;
}
if (isset($_POST["DNS_LINKER"])) {
    DNS_LINKER();
    exit;
}
if (isset($_POST["PING-ORDERS"])) {
    PARSE_ORDERS();
    exit;
}

$error=array();
if(isset($_FILES['DNS_LINKER'])) {
    foreach ($_FILES['DNS_LINKER'] as $num => $val) {
        $error[] = "\$_FILES['DNS_LINKER'][$num]:$val";
    }
}
foreach ($_REQUEST as $num=>$val){
    hacluster_syslog("ERROR Unexpected query $num FROM" .$_SERVER["REMOTE_ADDR"]);
    $error[] = "\$_REQUEST[$num]:$val";
}


writelogs("Unable to understand " . @implode(",", $error), __FILE__, __FUNCTION__, __LINE__);
http_response_code(404);
function SetDebug()
{
    ini_set('html_errors', 0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', $_SERVER["SERVER_ADDR"] . ":");
    ini_set('error_append_string', "");
}

function LEGAL_LOGS_UNREGISTER(){
    $ipaddr=$_SERVER["REMOTE_ADDR"];
    $LegalLogsProxyServers=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogsProxyServers"));
    unset($LegalLogsProxyServers[$ipaddr]);
    $Final=base64_encode(serialize($LegalLogsProxyServers));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogsProxyServers",$Final);
    echo "<SUCCESS>OK</SUCCESS>";
}
function LDAP_CLUSTER_INFOS(){

    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ldapcluster"]);
    if(!isset($MAIN["username"])){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="{wrong_privileges} ERR.554";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }
    if(!isset($MAIN["password"])){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="{wrong_privileges} ERR.560";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }

    if(!isAuthenticated($MAIN["username"],$MAIN["password"])){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="{wrong_privileges} ERR.569";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }

    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    if($EnableLDAPSyncProv==0){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="NOT A MASTER";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }

    $SyncProvUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN");
    if($SyncProvUser==null){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="NO SYNC USER DEFINED!";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }
    $LdapListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
    if($LdapListenInterface==null){$LdapListenInterface="lo";}
    if($LdapListenInterface=="lo"){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="NO LISTEN INTERFACE DEFINED";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();

    }


    $ldap=new clladp();
    $HASH=$ldap->GetDNValues($SyncProvUser);
    $RESULTS["ERROR"]=false;
    $RESULTS["LDAPSyncProvClientBindPassword"]=$HASH[0]["userpassword"][0];
    $RESULTS["LDAPSyncProvClientBindDN"]=$SyncProvUser;
    $RESULTS["LDAPSyncProvClientSearchBase"]=$ldap->suffix;
    $arrstr=base64_encode(serialize($RESULTS));
    echo "<RESULTS>$arrstr</RESULTS>\n";
    die();
}
function LEGAL_LOGS_REGISTER(){

    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["legallogs-register"]);

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        echo "<error>{license_error}</error>";
        die();
    }

    $user=trim(strtolower($MAIN["CREDS"]["USER"]));
    $password=$MAIN["CREDS"]["PASS"];
    $HOST=$MAIN["CREDS"]["HOST"];

    if(!isAuthenticated($user,$password)){"<error>$user {wrong_privileges}</error>";die();}

    $ipaddr=$_SERVER["REMOTE_ADDR"];
    $LegalLogsProxyServers=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogsProxyServers"));
    $LegallogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServerPort"));
    if($LegallogServerPort==0){$LegallogServerPort=10514;}

    zWriteToSyslog("Request open Proxy Logs by $ipaddr ($HOST) local server port: $LegallogServerPort","LEGAL_LOGS");
    $LegalLogsProxyServers[$ipaddr]=$HOST;
    $Final=base64_encode(serialize($LegalLogsProxyServers));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogsProxyServers",$Final);
    echo "<SUCCESS>$LegallogServerPort</SUCCESS>";

}
function LEGALSLOGS_UPLOADED(){
    $sf=" from {$_SERVER["REMOTE_ADDR"]}";
    $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );

    $uploaded_filename=$_FILES["file"]["name"];
    $uploaded_tmpname=$_FILES["file"]["tmp_name"];
    $uploaded_error=intval($_FILES["file"]["error"]);
    $uploaded_size=intval($_FILES["file"]["size"]);
    $target=$_POST["target"];
    $src_md5=$_POST["md5"];

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        zWriteToSyslog("[Legal Logs]: Unable to receive $uploaded_filename with error License Error $sf");
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        die();
    }

    if($uploaded_error>0){
        $uploaded_error_text=$phpFileUploadErrors[$uploaded_error];
        zWriteToSyslog("[Legal Logs]: Unable to receive $uploaded_filename with error $uploaded_error ($uploaded_error_text) $sf");
        http_response_code(500);
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        exit;
    }
    // Pentest 2026-11-04 : nom de fichier uploade sans chemin (traversee de repertoire).
    $uploaded_filename=nodes_safe_name(basename((string)$uploaded_filename));
    if($uploaded_filename==""){
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        http_response_code(500);
        exit;
    }
    $tarballs_dir="/usr/share/artica-postfix/ressources/conf/upload/LEGALS";
    if(!is_dir($tarballs_dir)){@mkdir($tarballs_dir,0755,true);}
    $tarballs_file="$tarballs_dir/$uploaded_filename";
    $tarballs_explain="$tarballs_dir/$uploaded_filename.txt";

    if (!move_uploaded_file($uploaded_tmpname, $tarballs_file)){
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        zWriteToSyslog("[Legal Logs]: Unable to move $uploaded_tmpname to $tarballs_file $sf");
        http_response_code(500);
        exit;
    }


    $md5=md5_file($tarballs_file);
    if($md5<>$src_md5){
        zWriteToSyslog("[Legal Logs]: Corrupted upload $uploaded_filename, aborting ($md5<>$src_md5) $sf");
        http_response_code(500);
        exit;
    }

    @file_put_contents($tarballs_explain,$target);
    $uploaded_size_kb=round($uploaded_size/1024,2);
    zWriteToSyslog("[Legal Logs]: Success received $uploaded_filename ({$uploaded_size_kb}Kb $sf");
    http_response_code(200);


}
// Pentest 2026-11-04 : nom simple (fichier, hote, uuid) sans chemin ; "" si autre chose.

function nodes_safe_name($value):string{
    $value=trim((string)$value);
    if($value=="" || $value=="." || $value==".."){return "";}
    if(!preg_match('#^[A-Za-z0-9_.@+-]+$#',$value)){return "";}
    if(strpos($value,"..")!==false){return "";}
    return $value;
}
function zWriteToSyslog($text,$bin="stats-appliance"){
		if(!function_exists("syslog")){return;}
		$LOG_SEV=LOG_INFO;
		openlog($bin, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();
	
	}
function hacluster_syslog($text){
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster-client", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();

}


function hacluster_receive_register(){
    include_once(dirname(__FILE__)."/ressources/class.manager.inc");
    $UserName=$_POST["HaClusterRegister"];
    $Password=$_POST["HaClusterRegisterPassword"];
    $ComPort=$_POST["ComPort"];
    $ID=intval($_POST["ID"]);
    if($ID==0){
        echo "<ERROR>No such ID</ERROR>";
        die();
    }
    $manager=new class_manager();
    $Admin=md5(strtolower($manager->admin));
    $PasswordSrc=md5($manager->password);
    if( $UserName<>$Admin){
        echo "<ERROR>Credentials failed</ERROR>";
        die();
    }
    if($Password<>$PasswordSrc){
        echo "<ERROR>Credentials failed</ERROR>";
        die();
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterID",$ID);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterClientListenPort",$ComPort);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/install");
    echo "\nRETURNED_TRUE\n";
    die();
}









function export_squid_table(){
	$workdir=dirname(__FILE__)."/ressources/squid-export";
	$table=$_GET["squid-table"];
	// Pentest 2026-11-04 : nom de table uniquement (traversee de repertoire).
	if(!preg_match('#^[A-Za-z0-9_]+$#',$table)){http_response_code(400);return;}
	$q=new mysql_squid_builder();
	$q->BD_CONNECT();
	if(is_file("$workdir/$table.gz")){@unlink("$workdir/$table.gz");}
	$dump=new phpMyDumper("squidlogs",$q->mysqli_connection,"$workdir/$table.gz",true,$table);
	$dump->doDump();
	$sock=new sockets();
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode("$workdir/$table.gz")));
	$fsize = filesize("$workdir/$table.gz");
	
	
	
	if($GLOBALS["VERBOSE"]){
		echo "Content-type: $content_type<br>\nfilesize:$fsize<br>\n";
		
		return;}
	
	header('Content-type: '.$content_type);
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$table.gz\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile("$workdir/$table.gz");	
	
	
	
}
	


function stats_berekley_upload(){
	$hostname=$_POST["HOSTNAME"];
	$FILENAME=$_POST["FILENAME"];
	$SIZE=$_POST["SIZE"];
	$UUID=trim($_POST["UUID"]);
	// Pentest 2026-11-04 : UUID et noms de fichiers sans chemin (traversee de repertoire).
	if($UUID<>null){$UUID=nodes_safe_name($UUID);if($UUID==""){return false;}}
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/BEREKLEY";
	$moved_file=$content_dir . "/$UUID-$FILENAME";
	@mkdir($content_dir,0755,true);
	if($UUID==null){$UUID=time();}
	$UploadedDir=$content_dir;
	@mkdir($UploadedDir,0755,true);
	if(!is_dir($UploadedDir)){return false;}
	
	foreach ($_FILES as $key=>$arrayF){
		$type_file = $arrayF['type'];
		$name_file = nodes_safe_name(basename((string)$arrayF['name']));
		$tmp_file  = $arrayF['tmp_name'];
		if($name_file==""){continue;}
				
		$target_file="$UploadedDir/$UUID-$name_file";
		if(file_exists( $target_file)){@unlink( $target_file);}
		if( !move_uploaded_file($tmp_file, $target_file) ){
			echo "$UUID: Error Unable to Move File : $target_file\n";
			writelogs("$UUID: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
				
		}
		$sock=new sockets();
		$sock->getFrameWork("artica.php?stats-appliance-berekley=yes");
	
	
}

function isAuthenticated($username,$password){
    $username=strtolower(trim($username));

    $logon=new artica_logon($username,$password);
    $logon->Simulate=true;
    if($logon->isManager()){
        $logon->webconsole_syslog("Success connected as Manager using internal API");
        return true;
    }
    if(!$logon->CheckCreds()){
        $logon->webconsole_syslog("Failed connected as $username using internal API");
        $logon->logon_events("FAILED");
        return false;
    }
    if(!$logon->IsAdminPrivileges()){
        $logon->webconsole_syslog("Failed connected as $username (wrong privileges) using internal API");
        $logon->logon_events("FAILED");
        return false;
    }
    $logon->webconsole_syslog("Success connected as $username using internal API");
    return true;
}

function PARSE_ORDERS(){
	$sock=new sockets();
	writelogs("Request PING-ORDER FROM " .$_SERVER["REMOTE_ADDR"],__FUNCTION__,__FILE__,__LINE__);
	writelogs("-> services.php?netagent-ping=yes",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("services.php?netagent-ping=yes");
	echo "<SUCCESS>SUCCESS</SUCCESS>";
	
}









function OPENSYSLOG(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer", "1");
	$sock->SET_INFO("DisableArticaProxyStatistics", "0");
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");
	$sock->REST_API("/proxy/nohup/reconfigure");
	$sock->REST_API("/proxy/schedule/apply");
	echo "\n<RESULTS>OK</RESULTS>\n";
}

function stats_appliance_comptability(){
	$f=array();
	$users=new usersMenus();
	$sock=new sockets();
	$APP_SQUID_DB=true;
	$APP_SYSLOG_DB=true;
	$sock=new sockets();
	
	// framework/squid.php?IS_APP_SQUIDDB_INSTALLED repondait toujours vide.
	$APP_SQUIDDB_INSTALLED="";
	if($GLOBALS["VERBOSE"]){echo "<H1>APP_SQUIDDB_INSTALLED = `$APP_SQUIDDB_INSTALLED`</H1>\n";}
	
	if($APP_SQUIDDB_INSTALLED<>"TRUE"){
		$f[]="Token APP_SQUIDDB_INSTALLED return false";
		$APP_SQUID_DB=false;
	}
	
	
	$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
	if(!is_numeric($ProxyUseArticaDB)){$ProxyUseArticaDB=0;}
	if($ProxyUseArticaDB==0){
		$sock->SET_INFO("ProxyUseArticaDB",1);
		$APP_SQUID_DB=true;
	
	}
	$sock->SET_INFO("DisableArticaProxyStatistics", 0);
	$array["APP_SQUID_DB"]=$APP_SQUID_DB;
	
	
	if($_GET["AS_DISCONNECTED"]==1){
		$credentials=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["creds"]);
		$ldap=new clladp();
		$array["DETAILS"][]="Manager: {$credentials["MANAGER"]}";
		if($ldap->ldap_admin<>$credentials["MANAGER"]){
			$array["APP_CREDS"]=false;
			$array["DETAILS"][]="Account mismatch..";
			echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
			return;
		}
		if($ldap->ldap_password<>$credentials["PASSWORD"]){
			$array["APP_CREDS"]=false;
			$array["DETAILS"][]="Password mismatch..";
			echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
			return;
		}	

		$array["APP_CREDS"]=true;
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
		
	}
	
	
	
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableMySQLSyslogWizard=$sock->GET_INFO("EnableMySQLSyslogWizard");
	if(!is_numeric($EnableMySQLSyslogWizard)){$EnableMySQLSyslogWizard=0;}
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if($EnableSyslogDB==0){
		$MySQLSyslogWorkDir=$sock->GET_INFO("MySQLSyslogWorkDir");
		if($MySQLSyslogWorkDir==null){$MySQLSyslogWorkDir="/home/syslogsdb";}
		$TuningParameters=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogParams"));
		
		if(!is_numeric($TuningParameters["ListenPort"])){$TuningParameters["ListenPort"]=0;}
		if($TuningParameters["ListenPort"]==0){$TuningParameters["ListenPort"]=rand(12500, 36500);}
		$sock->SET_INFO("EnableMySQLSyslogWizard", 1);
		$sock->SET_INFO("EnableSyslogDB", 1);
		$sock->SET_INFO("MySQLSyslogType",1);
		$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
		$sock->getFrameWork("cmd.php?restart-artica-status=yes");
		$APP_SYSLOG_DB=true;
	}
	
	
	$datas=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
	$datas["tcpsockets"]=1;
	$datas["listen_port"]=3977;
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");

	
	
	$array["APP_SYSLOG_DB"]=$APP_SYSLOG_DB;
	$array["DETAILS"]=$f;
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
	
}

function stats_appliance_ports(){
	$sock=new sockets();
	$TuningParameters=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogParams"));
	$ListenPort=$TuningParameters["ListenPort"];
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	if($ListenPort==0){
		$ListenPort=rand(21500, 63000);
		$TuningParameters["ListenPort"]=$ListenPort;
		$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
	}
	$f["SyslogListenPort"]=$ListenPort;
	
	
	$SquidDBTuningParameters=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDBTuningParameters"));
	$ListenPort=$SquidDBTuningParameters["ListenPort"];
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	if($ListenPort==0){
		$ListenPort=rand(21500, 63000);
		$SquidDBTuningParameters["ListenPort"]=$ListenPort;
		$sock->SaveConfigFile(base64_encode(serialize($SquidDBTuningParameters)), "SquidDBTuningParameters");
	}
	
	$f["SquidDBListenPort"]=$ListenPort;
	echo "\n\n<RESULTS>".base64_encode(serialize($f))."</RESULTS>\n\n";
}

function stats_appliance_remote_port(){
	$sock=new sockets();
	$UFDB=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
	$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
	$UFDB["tcpsockets"]=1;
	$UFDB["listen_port"]=$_GET["ufdbguardport"];
	$UFDB["listen_addr"]="all";
	$UFDB["UseRemoteUfdbguardService"]="0";
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->SET_INFO("EnableUfdbGuard2",1);
	$sock->SET_INFO("UseRemoteUfdbguardService",0);
	$sock->SaveConfigFile(base64_encode(serialize($UFDB)),"ufdbguardConfig");	
	$sock->getFrameWork("cmd.php?restart-ufdb=yes");
	
}

function stats_appliance_privs(){
	if($GLOBALS["VERBOSE"]){echo "stats_appliance_privs():: {$_SERVER["REMOTE_ADDR"]}<br> \n";}
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$OrginalPassword=$q->mysql_password;
	$server=$_SERVER["REMOTE_ADDR"];
	$username=str_replace(".", "", $server);
	$password=md5($server);
	if($GLOBALS["VERBOSE"]){echo "USER:$username@$server and password: $password Line:".__LINE__."<br> \n";}
	writelogs("USER:$username@$server and password: $password",__FUNCTION__,__FILE__,__LINE__);
	
	
	// Enable Ufdbguard...
	$UFDB=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
	$UFDB["tcpsockets"]=1;
	$UFDB["listen_port"]=3977;
	$UFDB["listen_addr"]="all";
	$UFDB["UseRemoteUfdbguardService"]="0";
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->SET_INFO("EnableUfdbGuard2",1);
	$sock->SET_INFO("UseRemoteUfdbguardService",0);
	
	
	
	$sock->SaveConfigFile(base64_encode(serialize($UFDB)),"ufdbguardConfig");
	//
	
	
	if(!$q->GRANT_PRIVS($server,$username,$password)){
		$array["ERROR"]=$q->mysql_error;
		if($GLOBALS["VERBOSE"]){echo "stats_appliance_privs():: MySQL Error line: ".__LINE__." $q->mysql_error<br> \n";}
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}

	
		
$q=new mysql_storelogs();
	if(!$q->GRANT_PRIVS($server,$username,$password)){
			$array["ERROR"]=$q->mysql_error;
			echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
			return;
		}

$array["mysql"]["username"]=$username;
$array["mysql"]["password"]=$password;
if($GLOBALS["VERBOSE"]){print_r($array);}
$sock->getFrameWork("cmd.php?restart-ufdb=yes");
echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";

}

function ucarp_step1(){
	$MyEth=null;
    $SEND_SETTING=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ucarp"]);
	if(!is_array($SEND_SETTING)){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."<br>Not an array()</RESULTS>\n\n";
		return;
	}
	
	$second_ipaddr=$SEND_SETTING["SLAVE"];
	$ip=new IP();
	if(!$ip->isValid($second_ipaddr)){
		$array["ERROR"]=true;
        foreach ($SEND_SETTING as $a=>$b){
			$f[]="<strong>$a = $b</strong><br>";
		}

		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr<br>".@implode("\n", $f);
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	$ip=new networking();

    foreach ($ip->array_TCP as $eth=>$cip){
		if($cip==null){continue;}
		if($cip==$second_ipaddr){
			$MyEth=$eth;
		}
	}
	if($MyEth==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr {cannot_found_interface}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
		
	}
	$array["ERROR"]=false;
	$array["ERROR_SHOW"]="{interface}:$MyEth";
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
}
function ucarp_step2(){
	$MyEth=null;
	$SEND_SETTING=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ucarp2"]);
	if(!is_array($SEND_SETTING)){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."<br>Not an array()</RESULTS>\n\n";
		return;
	}

	$second_ipaddr=$SEND_SETTING["SLAVE"];
	$ip=new IP();
	if(!$ip->isValid($second_ipaddr)){
		$array["ERROR"]=true;
        foreach ($SEND_SETTING  as $a=>$b){
			$f[]="<strong>$a = $b</strong><br>";
		}

		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr<br>".@implode("\n", $f);
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}

	$ip=new networking();
    foreach ($ip->array_TCP as $eth=>$cip){
		if($cip==null){continue;}
		if($cip==$second_ipaddr){
			$MyEth=$eth;
		}
	}
	if($MyEth==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr {cannot_found_interface}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;

	}

	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{license_error}: {this_feature_is_disabled_corp_license}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;		
	}
	
	$nic=new system_nic($MyEth);
	if($nic->IPADDR==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{unconfigured_network}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;		
	}
	
	if(isset($SEND_SETTING["first_ipaddr"])){
		$SEND_SETTING["BALANCE_IP"]=$SEND_SETTING["first_ipaddr"];
	}
	
	$nic->ucarp_enabled=1;
	$nic->ucarp_vip=$SEND_SETTING["BALANCE_IP"];
	$nic->ucarp_vid=$SEND_SETTING["ucarp_vid"];
	$nic->ucarp_master=0;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="Save Network in Local Database failed [".__LINE__."]<br>$nic->mysql_error<br>MySQL server will be restarted, please try again";
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?restart-mysql=yes");
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;		
	}
	$array["ERROR"]=false;
	$array["ERROR_SHOW"]="{success}";
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";	
}
function ucarp_step3(){
	$sock=new sockets();
	$sock->REST_API("/system/network/reconfigure-restart");
	$array["ERROR"]=false;
	$array["ERROR_SHOW"]="DONE";
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
	
}


function UCARP_REMOVE(){
	$sock=new sockets();
	$SEND_SETTING=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ucarp2-remove"]);
	
	if(!is_array($SEND_SETTING)){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}";
		echo "\n\n<RESULTS>".base64_decode(serialize($array))."<br>Not an array()</RESULTS>\n\n";
		return;
	}
	
	$second_ipaddr=$SEND_SETTING["SLAVE"];
	$ip=new IP();
	if(!$ip->isValid($second_ipaddr)){
		$array["ERROR"]=true;
        foreach ($SEND_SETTING as $a=>$b){
			$f[]="<strong>$a = $b</strong><br>";
		}
	
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr<br>".@implode("\n", $f);
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	$ip=new networking();
    foreach ($ip->array_TCP as $eth=>$cip){
		if($cip==null){continue;}
		if($cip==$second_ipaddr){
			$MyEth=$eth;
		}
	}
	if($MyEth==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr {cannot_found_interface}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	
	}
	$nic=new system_nic($MyEth);
	if($nic->IPADDR==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{unconfigured_network}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	$nic->ucarp_enabled=0;
	$nic->ucarp_vip=null;
	$nic->ucarp_vid=0;
	$nic->ucarp_master=0;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="Save in Database failed";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	echo "\n<RESULTS>SUCCESS</RESULTS>";
	$data=base64_decode($sock->getFrameWork("network.php?ucarp-down=$MyEth&master={$_SERVER["REMOTE_ADDR"]}"));
	$sock->REST_API("/system/network/reconfigure-restart");
	
	
}

function UCARP_DOWN(){
	$sock=new sockets();
	$data=base64_decode($sock->getFrameWork("network.php?ucarp-down={$_POST["UCARP_DOWN"]}&master={$_SERVER["REMOTE_ADDR"]}"));
	echo "\n<RESULTS>$data</RESULTS>";
	
}



function elkMaster(){

    $hostname=php_uname("n");
    $node=$_GET["node"];

    if($node==null){
        echo "<ERROR>Node is null</ERROR>";return;
    }

    $ElasticsearchTransportPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchTransportPort"));
    $ElasticSearchClusterLists=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterLists");
    $ClusterGroupName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterGroupName"));
    $zElasticSearchClusterLists=explode(",",$ElasticSearchClusterLists);
    foreach ($zElasticSearchClusterLists as $cluster){
        if(trim($cluster)==null){continue;}
        $ARRAY[$cluster]=$cluster;
    }

    echo "
    <transportport>$ElasticsearchTransportPort</transportport>\n
    <hostname>$hostname</hostname>\n
    <OKNODES>".serialize($ARRAY)."</OKNODES>\n<NODESNUM>".count($ARRAY)."</NODESNUM>\n<GROUP>$ClusterGroupName</GROUP>";

    $ARRAY[$node]=$node;

    foreach ($ARRAY as $comp=>$none){
        $znew[]=$comp;

    }
    $sock=new sockets();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticSearchClusterLists",@implode(",",$znew));
    $sock->getFrameWork("elasticsearch.php?reload=yes");

}

