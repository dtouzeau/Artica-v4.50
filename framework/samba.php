<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.samba.privileges.inc");
if(isset($_GET["smbd-logs"])){smbdlogs();exit;}
if(isset($_GET["testparm"])){testparm();exit;}
if(isset($_GET["idof"])){idof();exit;}
if(isset($_GET["wins-list"])){winsdat();exit;}
if(isset($_GET["test-ads-join"])){testadsjoin();exit;}
if(isset($_GET["adsinfos"])){adsinfos();exit;}
if(isset($_GET["getent"])){getent();exit;}
if(isset($_GET["getent-group"])){getent_group();exit;}
if(isset($_GET["apply-chmod"])){apply_chmod();exit;}
if(isset($_GET["trash-restore"])){trash_restore();exit;}
if(isset($_GET["trash-scan"])){trash_scan();exit;}
if(isset($_GET["trash-delete"])){trash_delete();exit;}
if(isset($_GET["SmblientBrowse"])){SmblientBrowse();exit;}
if(isset($_GET["SAMBA-HAVE-POSIX-ACLS"])){SAMBA_HAVE_POSIX_ACLS();exit;}
if(isset($_GET["netadsinfo"])){netadsinfo();exit;}
if(isset($_GET["netrpctestjoin"])){netrpctestjoin();exit;}
if(isset($_GET["netrpcinfo"])){netrpcinfo();exit;}
if(isset($_GET["wbinfoalldom"])){wbinfo_alldomains();exit;}
if(isset($_GET["wbinfomoinst"])){wbinfo_checksecret();exit;}
if(isset($_GET["wbinfomoinsa"])){wbinfo_authenticate();exit;}
if(isset($_GET["wbinfomoinsd"])){wbinfo_domain_info();exit;}
if(isset($_GET["fullversion"])){SAMBA_VERSION();exit;}
if(isset($_GET["build-homes"])){build_homes();exit;}
if(isset($_GET["wbinfo-m-verb"])){wbinfo_alldomains_verb();exit;}
if(isset($_GET["dsgetdcname"])){dsgetdcname();exit;}
if(isset($_GET["dcinfo"])){dcinfo();exit;}
if(isset($_GET["smb-logon-scripts-user"])){login_script_user();exit;}
if(isset($_GET["watchdog-config"])){watchdog_monit();exit;}
if(isset($_GET["winbindd-logs"])){winbind_logs();exit;}
if(isset($_GET["joint"])){join_ad();exit;}
if(isset($_GET["GetNetAdsInfos"])){GetNetAdsInfos();exit;}
if(isset($_GET["current-version"])){current_version();exit;}
if(isset($_GET["downgrade"])){downgrade();exit;}
if(isset($_GET["net-ads-status"])){ads_status();exit;}
if(isset($_GET["global-status"])){GLOBAL_STATUS();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["promote"])){promote();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function winbind_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	if(!isset($_GET["rp"])){$rp=50;}else{$rp=intval($_GET["rp"]);}
	exec("$tail -n $rp /var/log/samba/log.winbindd 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function install(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/samba.install.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/samba.install.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.samba-service.php --install >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function promote(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/samba.install.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/samba.install.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.samba-service.php --promote >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function uninstall(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/samba.install.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/samba.install.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.samba-service.php --uninstall >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function reconfigure(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/samba.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/samba.install.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.samba-service.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function trash_restore(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.samba.php --trash-restore >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function trash_scan(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.samba.php --recycles >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function trash_delete(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.samba.php --trash-delete >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}





function idof(){
	$uid=$_GET["idof"];
$unix=new unix();
	$id=$unix->find_program("id");
	if(!is_file($id)){
		echo "<articadatascgi>". base64_encode("id, no such binary")."</articadatascgi>";
		return;
	}	
	
	$cmd="$id $uid 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(implode(" ",$results))."</articadatascgi>";	
	
}

function apply_chmod(){
	$chmodbin=$_GET["apply-chmod"];
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$cmd="$chmod $chmodbin \"$path\"";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	
}

function testparm(){
	$hostname=$_GET["testparm"];
	if($hostname=="yes"){$hostname=null;}
	if($hostname<>null){$L=" -L $hostname ";}else{
		$L="/etc/samba/smb.conf";
	}
	$unix=new unix();
	$testparm=$unix->find_program("testparm");
	if(!is_file($testparm)){
		echo "<articadatascgi>". base64_encode(serialize(array("testparm, no such binary")))."</articadatascgi>";
		return;
	}
	
	$cmd="$testparm -s -v $L 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}


function smbdlogs(){
	$unix=new unix();
	$search=base64_decode($_GET["search"]);
	$search=str_replace("***", "*", $search);
	$search=str_replace("**", "*", $search);
	$rows=$_GET["rows"];
	if($search=="*"){$search=null;}
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	if($search==null){
		$cmd="$tail -n $rows /var/log/samba/log.smbd 2>&1";
	}else{
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		$search=str_replace("/", "\/", $search);
		$cmd="$grep --binary-files=text -i -E \"$search\" /var/log/samba/log.smbd|$tail -n $rows 2>&1";
	}
	exec($cmd,$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
	
}

function testadsjoin(){
	$unix=new unix();
	$net=$unix->LOCATE_NET_BIN_PATH();
	exec("$net ads testjoin 2>&1",$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	foreach ($results as $num=>$line){
		if(preg_match("#Join to domain is not valid:(.+)#", $line,$re)){
			echo "<articadatascgi>FALSE:{$re[1]}</articadatascgi>";
			return;
		}
		
		if(preg_match("#Join is OK#", $line,$re)){
			echo "<articadatascgi>TRUE</articadatascgi>";
			return;
		}
		
	}
	
}
function adsinfos(){
	$unix=new unix();
	$net=$unix->LOCATE_NET_BIN_PATH();
	exec("$net ads info 2>&1",$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	foreach ($results as $num=>$line){
		if(preg_match("#(.+?):(.+)#", $line,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
		
		
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}


function ads_status(){
	$unix=new unix();
	$net=$unix->LOCATE_NET_BIN_PATH();
	$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	shell_exec("net ads status -U $adminname%$kinitpassword >/usr/share/artica-postfix/ressources/logs/web/NET_ADS_STATUS 2>&1");
	
	
	
}



function winsdat(){
	$unix=new unix();
	$dat="/var/lib/samba/wins.dat";
	if(!is_file($dat)){
		echo "<articadatascgi>". base64_encode(serialize(array("\"Failed\" unable to stat $dat")))."</articadatascgi>";
		return;
		
	}
	$search=$_GET["search"];
	$search=str_replace("***", "*", $search);
	$search=str_replace("**", "*", $search);
	$rows=$_GET["rows"];
	if($search=="*"){$search=null;}
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	if($search==null){
		$cmd="$tail -n 500 $dat 2>&1";
	}else{
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		$search=str_replace("/", "\/", $search);
		$cmd="$grep --binary-files=text -i -E \"$search\" $dat|$tail -n 500 2>&1";
	}
	exec($cmd,$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
		
	
}

function getent(){
	$pattern=trim($_GET["getent"]);
	$pattern=str_replace(".","\.", $pattern);
	$pattern=str_replace("*",".*?", $pattern);
	$unix=new unix();
	$getent=$unix->find_program("getent");
	if($pattern<>null){
		$grep=$unix->find_program("grep");
		$pipe="|grep -i -E \"$pattern\"";
	}
	
	$cmd="$getent passwd$pipe 2>&1";
	exec($cmd,$results);
	
	foreach ($results as $num=>$line){
		if(preg_match("#^(.+?):.*?:#", $line,$re)){
			$return[$re[1]]=$re[1];
		}else{
			$false++;
		}
	}
	writelogs_framework("$cmd = " . count($results)." rows $false bad lines return ". count($return)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($return))."</articadatascgi>";	
	
	
}


function SmblientBrowse(){
	$datas=unserialize(base64_decode($_GET["SmblientBrowse"]));
	$username=$datas[0];
	$password=$datas[1];
	$unix=new unix();
	$password=$unix->shellEscapeChars($password);
;	
	$smbclient=$unix->find_program("smbclient");
	$cmd="$smbclient -g -L //localhost -U {$username}%{$password} 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $num=>$line){
		if(strpos($line, "|")==0){
			writelogs_framework("$line = skipped",__FUNCTION__,__FILE__,__LINE__);
			continue;}
		writelogs_framework("$line = OK",__FUNCTION__,__FILE__,__LINE__);	
		$tr=explode("|", $line);
		$return[]=$tr;
		
	}
	echo "<articadatascgi>". base64_encode(serialize($return))."</articadatascgi>";	
}

function getent_group(){
	$pattern=trim($_GET["getent-group"]);
	$pattern=str_replace(".","\.", $pattern);
	$pattern=str_replace("*",".*?", $pattern);
	$unix=new unix();
	$getent=$unix->find_program("getent");
	if($pattern<>null){
		$grep=$unix->find_program("grep");
		$pipe="|grep -i -E \"$pattern\"";
	}
	
	$cmd="$getent group$pipe 2>&1";
	exec($cmd,$results);
	
	foreach ($results as $num=>$line){
		if(preg_match("#^(.+?):.*?#", $line,$re)){
			$return[$re[1]]=$re[1];
		}else{
			$false++;
		}
	}
	
	writelogs_framework("$cmd = " . count($results)." rows $false bad lines return ". count($return)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($return))."</articadatascgi>";	
}

function netadsinfo(){
	$unix=new unix();

	$net=$unix->find_program("net");
	if(is_file($net)){
		$cmd="$net ads info 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: net no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function netrpctestjoin(){
	$unix=new unix();

	$net=$unix->find_program("net");

	
	
	$results[]="<strong><i>Active Directory Mode:</i></strong>";
	if(is_file($net)){
		$cmd="$net ads testjoin 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: net no such binary !";
	}	
	
	$KerbAuthSMBV2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthSMBV2"));
	if($KerbAuthSMBV2==0){
		$results[]="<br><strong><i>NT4 Mode (optional):</i></strong>";
		if(is_file($net)){
			$cmd="$net rpc testjoin 2>&1";
			exec($cmd,$results);
		}else{
			$results[]="Failed: net no such binary !";
		}
	}
	
	
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function netrpcinfo(){
	$unix=new unix();

	$net=$unix->find_program("net");
	$array=unserialize(base64_decode($_GET["auth"]));
	if($array["USER"]==null){
		$results[]="netrpcinfo();failed No such user !!!";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	$array["PASSWD"]=$unix->shellEscapeChars($array["PASSWD"]);

	if(is_file($net)){
		$cmd="$net rpc info -U {$array["USER"]}%{$array["PASSWD"]} 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: net no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
	
}



function wbinfo_alldomains(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]));
	if(is_file($wbinfo)){
		$cmd="$wbinfo --online-status 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function wbinfo_alldomains_verb(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]));
	
	if(is_file($wbinfo)){
		$cmd="$wbinfo -m --verbose 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
}

function dsgetdcname(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$dsgetdcname=base64_decode($_GET["dsgetdcname"]);
	if(is_file($wbinfo)){
		$cmd="$wbinfo --dsgetdcname=$dsgetdcname 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
}
function dcinfo(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$dsgetdcname=base64_decode($_GET["dcinfo"]);
	if(is_file($wbinfo)){
		$cmd="$wbinfo --dc-info=$dsgetdcname 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode($results[0])."</articadatascgi>";	
	
}



function wbinfo_checksecret(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]));
	if(is_file($wbinfo)){	
		$cmd="$wbinfo --check-secret 2>&1";
	exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function wbinfo_domain_info(){
	$unix=new unix();
	$domain=base64_decode($_GET["wbinfomoinsd"]);
	$wbinfo=$unix->find_program("wbinfo");	
	if(is_file($wbinfo)){
		$cmd="$wbinfo --domain-info=$domain 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}



function wbinfo_authenticate(){
	$unix=new unix();
	$domain=null;
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]));
	if($array["USER"]==null){
		$results[]="<strong style='color:#d32d2d'>netrpcinfo();failed No such user !!!</strong>";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}	
	$array["PASSWD"]=$unix->shellEscapeChars($array["PASSWD"]);
	
	if(isset($array["WORKGROUP"])){
		if($array["WORKGROUP"]<>null){
			$domain=" --domain={$array["WORKGROUP"]}";
		}
	}
	
	if(is_file($wbinfo)){	
		$cmd="$wbinfo --authenticate={$array["USER"]}%{$array["PASSWD"]}$domain 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}

function SAMBA_HAVE_POSIX_ACLS(){
	$unix=new unix();
	$HAVE_POSIX_ACLS="FALSE";
	$smbd=$unix->find_program("smbd");
	$grep=$unix->find_program("grep");
	exec("$smbd -b|$grep -i acl 2>&1",$results);
	foreach ($results as $index=>$line){
		
		if(preg_match("#HAVE_POSIX_ACLS#",$line)){
			writelogs_framework("$line match !",__FUNCTION__,__FILE__,__LINE__);
			$HAVE_POSIX_ACLS="TRUE";
			break;
		}else{
			writelogs_framework("$line no match....",__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	echo "<articadatascgi>". base64_encode($HAVE_POSIX_ACLS)."</articadatascgi>";	
	}

function SAMBA_VERSION(){
	$unix=new unix();
	echo "<articadatascgi>". $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION")."</articadatascgi>";	
}
function current_version(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->samba_version())."</articadatascgi>";
}
function join_ad(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.samba.php --join >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function build_homes(){
	$uid=$_GET["build-homes"];
	$SambaProfilePath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaProfilePath"));
	if(trim($SambaProfilePath)==null){$SambaProfilePath="/home/export/profile";}
	@mkdir("$SambaProfilePath/$uid.V2",0770,true);
	@chown("$SambaProfilePath/$uid.V2", $uid);
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $chown -R $uid \"$SambaProfilePath/$uid.V2\"");
	
}
function login_script_user(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.samba.php --login-script-user \"{$_GET["smb-logon-scripts-user"]}\" >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function watchdog_monit(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.samba.php --monit >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function GetNetAdsInfos(){
	$unix=new unix();
	@mkdir("/etc/squid3",0755,true);
	if(is_file("/etc/squid3/NET_ADS_INFOS")){
		$array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
		if(count($array)>5){
			echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
			return;
		}
	}
	
	$net=$unix->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	
	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	@file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}
function downgrade(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.samba.downgrade.php \"{$_GET["downgrade"]}\" >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function GLOBAL_STATUS(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --samba --nowachdog >/usr/share/artica-postfix/ressources/logs/web/samba.status 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	
}
function searchInSyslog(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]));
	$PROTO_P=null;

	foreach ($MAIN as $val=>$key){
		$MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
		$MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

	}

	$max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
	$date=$MAIN["DATE"];
	$PROTO=$MAIN["PROTO"];
	$SRC=$MAIN["SRC"];
	$DST=$MAIN["DST"];
	$SRCPORT=$MAIN["SRCPORT"];
	$DSTPORT=$MAIN["DSTPORT"];
	$IN=$MAIN["IN"];
	$OUT=$MAIN["OUT"];
	$MAC=$MAIN["MAC"];
	$PID=$MAIN["PID"];
	if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

	if($PID<>null){$PID_P=".*?pid=$PID.*?";}
	if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
	if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
	if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
	if($MAIN["C"]==0){$TERM_P=$TERM;}


	$mainline="{$PID_P}{$TERM_P}{$IN_P}";
	if($TERM<>null){
		if($MAIN["C"]>0){
			$mainline="($mainline|$TERM)";
		}
	}



	$search="$date.*?$mainline";
	$search=str_replace(".*?.*?",".*?",$search);
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/samba/log.smbd |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/smbd.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/smbd.syslog.pattern", $search);
	shell_exec($cmd);

}

