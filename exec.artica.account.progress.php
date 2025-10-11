<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["CONSOLE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--console"){$GLOBALS["CONSOLE"]=true;}

start();

function build_progress($text,$pourc){
    $unix=new unix();
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
    $DIALOG=$unix->find_program("dialog");

    if($GLOBALS["CONSOLE"]){
	    system("echo \"$pourc\" |$DIALOG --gauge \"$text\" 10 70 0");
        return true;
    }

	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
    return true;
}


function start(){
	$unix=new unix();
	$suffixcmd=null;
	if($GLOBALS["CONSOLE"]){$suffixcmd=" >/dev/null 2>&1";}
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/account.progress";
	$DATA=base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/conf/upload/ChangeLDPSSET"));
	$POSTED=unserialize($DATA);
    $change_ldap_server_settings="no";
    $CHANGE_ADMIN=true;
	$username=trim($POSTED["change_admin"]);
	$password=trim($POSTED["change_password"]);
    $ldap_server=$POSTED["ldap_server"];
    $ldap_port=$POSTED["ldap_port"];


	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));

	if(!$GLOBALS["CONSOLE"]) {
        $FWACCOUNTTEMP = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FWACCOUNTTEMP"));
        $ldap_server=$FWACCOUNTTEMP["ldap_server"];
        $ldap_port=$FWACCOUNTTEMP["ldap_port"];
        $suffix=$FWACCOUNTTEMP["suffix"];
        $change_ldap_server_settings=$FWACCOUNTTEMP["change_ldap_server_settings"];
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FWACCOUNTTEMP", serialize(array()));
    }

	

	if($ldap_server==null){$ldap_server="127.0.0.1";}
	if($ldap_port==null){$ldap_port="389";}
	if($suffix==null){$suffix="dc=nodomain";}

	if(!$GLOBALS["CONSOLE"]) {
        echo "Posted...........: " . strlen($DATA) . " bytes\n";
        echo "Username.........: $username\n";
        echo "Password.........: $password\n";
        echo "LDAP Server......: $ldap_server\n";
        echo "LDAP Port........: $ldap_port\n";
        echo "Suffix...........: $suffix\n";
    }
	if(!is_array($POSTED)){build_progress("Nothing as been posted",110);return;}
	if($username==null){$CHANGE_ADMIN=false;}
	if($password==null){$CHANGE_ADMIN=false;}

	build_progress("{checking}",20);
	$php=$unix->LOCATE_PHP5_BIN();
    if($CHANGE_ADMIN){
        if($GLOBALS["CONSOLE"]) {
            squid_admin_mysql(0, "SuperAdmin account was changed to $username using UNIX console", null, __FILE__, __LINE__);
        }else{
            squid_admin_mysql(0, "SuperAdmin account was changed to $username using Web console", null, __FILE__, __LINE__);
        }
        build_progress("Modify Super-Admin credentials",21);
    }else{
        build_progress("Not change Super-Admin credentials",21);
    }
	
	$md5=md5(strtolower($username).trim($password));
	$ldap=new clladp();
	$md52=md5(trim(strtolower($ldap->ldap_admin)).trim($ldap->ldap_password));
	build_progress("Change credentials",30);

	$BASCONF="/etc/artica-postfix/ldap_settings";
    if($md5==$md52){$CHANGE_ADMIN=false;}

    if($CHANGE_ADMIN) {
        @file_put_contents("$BASCONF/admin", $username);
        @file_put_contents("$BASCONF/password", $password);
    }

    if($change_ldap_server_settings=="yes") {
        file_put_contents("$BASCONF/port", $ldap_port);
        file_put_contents("$BASCONF/server", $ldap_server);
        file_put_contents("$BASCONF/suffix", $suffix);
    }
	
	@unlink("/etc/artica-postfix/no-ldap-change");
	@chmod("$php /usr/share/artica-postfix/exec.status.php --process1{$suffixcmd}", 0755);

	build_progress("Refresh global settings",40);
	system('/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --checkout --force --verbose '. time().$suffixcmd);
	
	if($EnableOpenLDAP==1){
		build_progress("{Restarting_LDAP_server}",45);
        system("$php /usr/share/artica-postfix/exec.slapd.conf.php{$suffixcmd}");
		shell_exec("/etc/init.d/slapd restart --framework=". basename(__FILE__).$suffixcmd);
	}
	build_progress("{Update_others_services}",50);
	system("$php /usr/share/artica-postfix/exec.change.password.php{$suffixcmd}");
	
	build_progress("{checking}",60);
	sleep(3);
	build_progress("{success}",100);
	
	
}
