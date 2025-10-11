#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.openssh.inc');
$GLOBALS["TITLENAME"]="OpenSSH daemon";
$GLOBALS["MONIT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DIALOG"]=false;
if(preg_match("#--monit#", @implode(" ", $argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--dialog#", @implode(" ", $argv))){$GLOBALS["DIALOG"]=true;}
if(isset($argv[1])){
    if($argv[1]=="--genkeys"){$GLOBALS["OUTPUT"]=true;genkeys($argv[2]);exit;}
    if($argv[1]=="--notify"){$GLOBALS["OUTPUT"]=true;notify($argv[2]);exit;}
    if($argv[1]=="--countries"){$GLOBALS["OUTPUT"]=true;country_hook();exit;}
    if($argv[1]=="--countriesoff"){$GLOBALS["OUTPUT"]=true;country_hook_remove();exit;}
    if($argv[1]=="--2fa"){$GLOBALS["OUTPUT"]=true;pam_google_authenticator_qrcode();exit;}
    if($argv[1]=="--authorizedkeys"){$GLOBALS["OUTPUT"]=true;sshd_authorizedkeys();exit;}

}
_out("Unable to understand the commanline ".@implode(" ",$argv ));
function build_progress($text,$pourc):bool{
	$filename=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[0])){$file=basename($trace[0]["file"]);$function=$trace[0]["function"];$line=$trace[0]["line"];}
		if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];}
	}

	if($GLOBALS["DIALOG"]) {
        system("echo $pourc| dialog --title \"{$GLOBALS["TITLE"]}\" --gauge \"$text\" 6 80");
        return true;
	}


	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/sshd.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}
    return true;
}
function sshd_authorizedkeys():bool{
    _out("Building authorized keys..");
    $sshd=new openssh();
    $count=$sshd->sshd_authorizedkeys();
    _out("Building $count authorized keys done.");
    return true;
}
function _out($text):bool{
    $date=date("H:i:s");
    $STAT="INIT";

    echo "$STAT......: $date OpenSSH service: $text\n";
    if(!function_exists("openlog")){return true;}
    openlog("sshd", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function country_hook_remove(){
    echo "Starting......: ".date("H:i:s")." [INIT]: Remove Country hooks...\n";
    $f=explode("\n",@file_get_contents("/etc/hosts.allow"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^sshd:#",$line)){continue;}
        $hostallow[]=$line;
    }
    @file_put_contents("/etc/hosts.allow",@implode("\n",$hostallow)."\n");
    $hostallow=array();
    $f=explode("\n",@file_get_contents("/etc/hosts.deny"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^sshd:#",$line)){continue;}
        $hostallow[]=$line;
    }
    @file_put_contents("/etc/hosts.deny",@implode("\n",$hostallow)."\n");
}

function country_hook(){
    $HOSTALLOW=false;
    $HOSTDENY=false;

    $SSHDDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDDenyCountries")));
    if(!is_array($SSHDDenyCountries)){$SSHDDenyCountries=array();}

    if(count($SSHDDenyCountries)==0){
        country_hook_remove();
        return;
    }

    echo "Starting......: ".date("H:i:s")." [INIT]: Create Country hooks...\n";
    $f=explode("\n",@file_get_contents("/etc/hosts.allow"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        $hostallow[]=$line;
        if(preg_match("#sshd: ALL: aclexec#",$line)){
            $HOSTALLOW=true;
            break;

        }
    }

    if(!$HOSTALLOW){
        $hostallow[]="sshd: ALL: aclexec /usr/sbin/artica-phpfpm-service -sshd-geo %a\n";
        @file_put_contents("/etc/hosts.allow",@implode("\n",$hostallow));
    }
    $f=explode("\n",@file_get_contents("/etc/hosts.deny"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        $hostdeny[]=$line;
        if(preg_match("#sshd:\s+ALL#",$line)){
            $HOSTDENY=true;
            break;

        }
    }
    if(!$HOSTDENY){
        $hostdeny[]="sshd: ALL\n";
        @file_put_contents("/etc/hosts.deny",@implode("\n",$hostdeny));
    }

}

function genkeys($uid=null){
	if($uid==null){
		build_progress("Generate keys {failed} no user set",110);
		return;
	}
	build_progress("Generate keys for $uid",10);
	$unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$mypass=null;
	
	$path="/home/$uid/.ssh";
	echo "$path\n";
	$echo=$unix->find_program("echo");
	$sshkeygen=$unix->find_program("ssh-keygen");
	$su=$unix->find_program("su");
	if(!is_dir("/home/$uid/.ssh")){
		@mkdir("/home/$uid/.ssh",0700,true);
	
	}
	
	if(is_file("$path/id_rsa")){@unlink("$path/id_rsa");}
	if(is_file("$path/id_rsa.pub")){@unlink("$path/id_rsa.pub");}
	@chown("/home/$uid/.ssh",$uid);
	@chgrp("/home/$uid/.ssh", $uid);
	@chmod("/home/$uid",0755);
	@chmod("/home/$uid/.ssh",0700);
	@mkdir("$path",0700,true);
	@chown($path,$uid);
	$ligne=$q->mysqli_fetch_array("SELECT passphrase FROM sshd_privkeys WHERE `username`='$uid'");
	$passphrase=$ligne["passphrase"];
	if(!$q->ok){echo $q->mysql_error."\n";build_progress("{failed}",110);return;}
	build_progress("Generate keys for $uid",20);
	echo "Pass Phrase: ".strlen($passphrase)."\n";
	
	if($passphrase<>null){
		$passphrase=$unix->shellEscapeChars($_GET["passphrase"]);
		$mypass2=" -P '$passphrase'";
	}
	
	$maincmd="$echo y|$sshkeygen -t rsa -b 2048 -N '$passphrase' -q -f $path/id_rsa";
	
	if($uid<>"root"){$maincmd="$su $uid -c \"$maincmd\"";}
	echo "$echo y|$sshkeygen -t rsa -b 2048 -N '*****' -q -f $path/id_rsa\n";
	build_progress("Generate keys for $uid",30);
	system($maincmd);
	
	$publickey=@file_get_contents("$path/id_rsa");
	$publickey_lenght=strlen($publickey);
	echo "id_rsa: $publickey_lenght Bytes\n";
	if($publickey_lenght==0){
		build_progress("Generate keys for $uid {failed} id_rsa",110);
		return;
	}
	
	if(!is_file("$path/id_rsa.pub")){
		build_progress("Generate keys for $uid {failed} id_rsa.pub no such file",110);
		return;
	}
	
	$privatekey=@file_get_contents("$path/id_rsa.pub");
	$privatekey_lenght=strlen($privatekey);
	if($privatekey_lenght==0){
		build_progress("Generate keys for $uid {failed} id_rsa.pub",110);
		return;
	}
	
	$privatekey=mysql_escape_string2($privatekey);
	$publickey=mysql_escape_string2($publickey);
	$q->QUERY_SQL("UPDATE sshd_privkeys SET `publickey`='$publickey',`privatekey` ='$privatekey',`slength`='$publickey_lenght' WHERE `username`='$uid'");
	if(!$q->ok){echo $q->mysql_error."\n";build_progress("MySQL {failed}",110);return;}
	build_progress("Generate keys for $uid {success}",100);
}

function notify($cmd){
	$tt=explode(";",$cmd);
	squid_admin_mysql(1, "SSH: $tt[1] is connected with SSH from {$tt[0]}", null,__FILE__,__LINE__);
	
}








function test_file($path){
    $unix=new unix();
    $sshd=$unix->find_program("sshd");
    echo "Testing Configuration file $path...\n";
    exec("$sshd -T -f $path 2>&1",$results);

    foreach ($results as $line) {

        if(preg_match("#Bad configuration.*?SSHDCiphers#i",$line,$re)){
            echo "$line\n";
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDCiphersEnable",0);
            return false;
        }
        if(preg_match("#(Bad configuration|fatal|error)#i",$line,$re)){
            echo "$line\n";
            return false;
        }


    }

    return true;

}

function test_config($data){

    $DestFile="/etc/ssh/sshd_config";
    $backupFile="/etc/ssh/sshd_config.BAK";
    $backupTEST="/etc/ssh/sshd_config.TEST";

    if(is_file($backupFile)){
        if(!test_file($backupFile)){
            echo "Backup file: $backupFile corrupted\n";
            @unlink($backupFile);
        }
    }

    if(!test_file($DestFile)){
        echo "Test File: $DestFile failed\n";
        @unlink($DestFile);

    }


    @file_put_contents($backupTEST,$data);
    if(!test_file($backupTEST)){
        echo "Test File: $backupTEST failed\n";

        if(!is_file($DestFile)){
            if(is_file($backupFile)){
                @copy($backupFile,$DestFile);
            }
        }

        return false;
    }

    @file_put_contents($DestFile, $data);
    @file_put_contents($backupFile, $data);


}


function pam_google_authenticator_qrcode(){
    $unix=new unix();
    $unix->framework_progress(20,"{starting}","ssh2fa.restart");
    $ssh=new openssh();
    if(!$ssh->pam_google_authenticator_qrcode()){
        $unix->framework_progress(110,"{failed}","ssh2fa.restart");
        return false;
    }
    $unix->framework_progress(100,"{done}","ssh2fa.restart");
}
