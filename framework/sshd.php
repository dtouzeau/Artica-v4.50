<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["ssh-kegen-key"])){ssh_keygen_key();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["proxy-disable"])){proxy_disable();exit;}
if(isset($_GET["scandisk"])){scandisk();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["restart-reconf"])){restart_reconf();exit;}
if(isset($_GET["hook-countries"])){hook_countries();exit;}
if(isset($_GET["ssh2fa"])){ssh2fa();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_GET["wizard-enable"])){wizard_progress();exit;}
if(isset($_GET["ssh-keygen"])){SSHD_KEY_GEN();exit;}
if(isset($_GET["public-key"])){public_key();exit;}
if(isset($_GET["gen-keys"])){GENKEYS();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["config-file"])){config_file();exit;}
if(isset($_GET["pam_google_authenticator"])){pam_google_authenticator();}
if(isset($_GET["authorizedkeys"])){authorizedkeys();exit;}
if(isset($_GET["build-key-pair"])){build_key_pair();exit;}

if(isset($_GET["install-portal"])){sshportal_install();exit;}
if(isset($_GET["uninstall-portal"])){sshportal_uninstall();exit;}
if(isset($_GET["sshportal-status"])){sshportal_status();exit;}
if(isset($_GET["sshportal-restart"])){sshportal_restart();exit;}
if(isset($_GET["sshportal-chown"])){chown_sshportal();exit;}
if(isset($_GET["sshportal-countries"])){countries_sshportal();exit;}
if(isset($_GET["sshportal-syslog"])){syslog_sshportal();exit;}
if(isset($_GET["compile-reload"])){compile_reload();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);



function authorizedkeys():bool{
    $unix=new unix();
    $unix->framework_exec("exec.sshd.php --authorizedkeys");
    return true;
}
function ssh_keygen_key():bool{
    $unix=new unix();
    $md=$_GET["ssh-kegen-key"];
    $tfile=PROGRESS_DIR."/$md.key";
    $tdest=PROGRESS_DIR."/$md.keygen";
    chmod($tfile,0600);
    $ssh_keygen=$unix->find_program("ssh-keygen");
    shell_exec("$ssh_keygen -y -f $tfile >$tdest");
    @unlink($tfile);
    @chown($tdest,"www-data");
    writelogs_framework("ssh_keygen_key - ".@file_get_contents($tdest) ,__FUNCTION__,__FILE__,__LINE__);
    return true;
}

function build_key_pair():bool{
    $unix=new unix();
    $lfile=$_GET["build-key-pair"];
    $tfile=PROGRESS_DIR."/$lfile";
    $ssh_keygen=$unix->find_program("ssh-keygen");
    $tool="/usr/share/artica-postfix/bin/ssh-keypair-gen";
    chmod($tool,0755);
    $TMPFILE=$unix->FILE_TEMP();
    exec("$tool -private-key $TMPFILE.pem -public-key $TMPFILE.pub 2>&1",$results);
    if(!is_file("$TMPFILE.pem")){
        $ARRAY["ERROR"]=@implode("\n",$results);
        @file_put_contents($tfile,serialize($ARRAY));
        @chown($tfile,"www-data");
        return false;
    }
    if(!is_file("$TMPFILE.pub")){
        $ARRAY["ERROR"]=@implode("\n",$results);
        @file_put_contents($tfile,serialize($ARRAY));
        return false;
    }
    chmod("$TMPFILE.pem",0600);
    shell_exec("$ssh_keygen -y -f $TMPFILE.pem >$TMPFILE.keygen");

    if(!is_file("$TMPFILE.keygen")){
        $ARRAY["ERROR"]=@implode("\n",$results);
        @file_put_contents($tfile,serialize($ARRAY));
        @chown($tfile,"www-data");
        return false;
    }
    $ARRAY["PRIV"]=@file_get_contents("$TMPFILE.pem");
    $ARRAY["PUB"]=@file_get_contents("$TMPFILE.pub");
    $ARRAY["KEYGEN"]=@file_get_contents("$TMPFILE.keygen");

    @unlink("$TMPFILE.pem");
    @unlink("$TMPFILE.pub");
    @unlink("$TMPFILE.keygen");
    @file_put_contents($tfile,serialize($ARRAY));
    @chown($tfile,"www-data");
    return true;
}

function compile_reload():bool{
    $unix=new unix();
    $unix->framework_exec("exec.sshd.php --compile");
    return true;
}

function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/hypercache-access.log.tmp";
	$query2=null;
	$sourceLog="/var/log/hypercache-service/access.log";

	if($_GET["FinderList"]<>null){
		$filename_compressed=PROGRESS_DIR."/logsfinder/{$_GET["FinderList"]}.gz";
		$filename_logs=PROGRESS_DIR."/logsfinder/{$_GET["FinderList"]}.log";
		if(is_file($filename_compressed)){
			if(!is_file($filename_logs)){
				$unix->uncompress($filename_compressed, $filename_logs);
				@chmod($filename_logs,0755);
				$sourceLog=$filename_logs;
			}else{
				$sourceLog=$filename_logs;
			}
		}
	}



	$rp=intval($_GET["rp"]);
	writelogs_framework("access_real -> $rp search {$_GET["query"]} SearchString = {$_GET["SearchString"]}" ,__FUNCTION__,__FILE__,__LINE__);

	$query=$_GET["query"];
	if($_GET["SearchString"]<>null){
		$query2=$query;
		$query=$_GET["SearchString"];
	}

	$grep=$unix->find_program("grep");


	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query2<>null){
		$pattern2=str_replace(".", "\.", $query2);
		$pattern2=str_replace("*", ".*?", $pattern2);
		$pattern2=str_replace("/", "\/", $pattern2);
		$cmd2="$grep --binary-files=text -Ei \"$pattern2\"| ";
		$cmd3="$grep --binary-files=text -Ei \"$pattern2\"";
	}

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$cmd2$tail -n $rp  >$targetfile 2>&1";
	}else{
		if($cmd3<>null){
			$cmd="$cmd3 $sourceLog|$cmd2 $tail -n $rp  >$targetfile 2>&1";
		}

	}



	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}

function public_key(){
	
	@unlink("/etc/artica-postfix/settings/Daemons/SSHDKeyPub");
	$_GET["uid"]="root";
	$_GET["passphrase"]=$_GET["pass"];
	SSHD_KEY_GEN();
		
}

function chown_sshportal(){

    @chown("/home/artica/SQLITE/sshdportal.db","www-data");
    @chgrp("/home/artica/SQLITE/sshdportal.db","www-data");
}

function GENKEYS(){
	$uid=$_GET["gen-keys"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/sshd.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/sshd.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshd.php --genkeys \"$uid\" >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function hook_countries(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshd.php --countries >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function SSHD_KEY_GEN(){
	$uid=$_GET["uid"];
	$unix=new unix();
	$mypass=null;

	$path="/home/$uid/.ssh";
	$echo=$unix->find_program("echo");
	$sshkeygen=$unix->find_program("ssh-keygen");
	$su=$unix->find_program("su");
	if(!is_dir($path)){
		@mkdir($path,0700,true);

	}

	if(is_file("$path/id_rsa")){@unlink("$path/id_rsa");}
	if(is_file("$path/id_rsa.pub")){@unlink("$path/id_rsa.pub");}
	@chown($path,$uid);
	@chgrp($path, $uid);
	@chmod($path,0700);
	@chown($path,$uid);
	
	if($_GET["passphrase"]<>null){
		$_GET["passphrase"]=$unix->shellEscapeChars($_GET["passphrase"]);
		$mypass=$_GET["passphrase"];
		$mypass2=" -P '$mypass'";
	}
	
	$maincmd="$echo y|$sshkeygen -t rsa -b 2048 -N '$mypass' -q -f $path/id_rsa";

	if($uid<>"root"){$maincmd="$su $uid -c \"$maincmd\"";}
	writelogs_framework("$maincmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($maincmd,$results);
	$maincmd="/usr/bin/ssh-keygen -l{$mypass2} -f $path/id_rsa >$path/id_rsa.pub";
	if($uid<>"root"){$maincmd="$su $uid -c \"$maincmd\"";}
	
	shell_exec($maincmd);
	writelogs_framework("$maincmd" ,__FUNCTION__,__FILE__,__LINE__);
	
	$id_rsa_pub=@file_get_contents("$path/id_rsa.pub");
	writelogs_framework("$path/id_rsa.pub == $id_rsa_pub" ,__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDKeyUsr",$id_rsa_pub);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDKeyPub", @file_get_contents("$path/id_rsa"));
	echo "<articadatascgi>" .base64_encode(@implode("\n",$results))."</articadatascgi>";
}



function sshportal_status(){
    shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --sshportal --nowachdog >/usr/share/artica-postfix/ressources/logs/web/sshportal.status 2>&1");
}

//
function sshportal_restart(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/sshportal.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function sshportal_uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function sshportal_install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function countries_sshportal(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --countries >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}







function proxy_remove(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/hypercache.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/hypercache.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.hypercache-service.install.php --remove >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function config_file(){

	@unlink("/usr/share/artica-postfix/ressources/logs/web/sshd.config");
	@copy("/etc/ssh/sshd_config", "/usr/share/artica-postfix/ressources/logs/web/sshd.config");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/sshd.config", 0777);
}

function syslog_sshportal(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["sshportal-syslog"]));
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

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
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
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/sshportal.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/sshportal.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/sshportal.syslog.pattern", $search);
    shell_exec($cmd);

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
	
	if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
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
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/sshd.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/sshd.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/sshd.syslog.pattern", $search);
	shell_exec($cmd);

}
function ssh2fa(){
    $unix=new unix();
    if(is_file(ARTICA_ROOT."/img/pam_google_authenticator_qrcode.png")){
        @unlink(ARTICA_ROOT."/img/pam_google_authenticator_qrcode.png");
    }
    $unix->framework_execute("exec.sshd.php --2fa","ssh2fa.restart","ssh2fa.log");
}

function pam_google_authenticator(){
    $dstfile=PROGRESS_DIR."/pam_google_authenticator.auth";
    @copy("/etc/ssh/pam_google_authenticator.auth",$dstfile);
    @chmod($dstfile,0755);
    @chown($dstfile,"www-data");

}

function status(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --hypercache-proxy --nowachdog >/usr/share/artica-postfix/ressources/logs/web/hypercache.proxy.status 2>&1");
		
}


