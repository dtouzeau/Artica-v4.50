<?php
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



MAIN_MENU();

function MAIN_MENU(){
$unix=new unix();
$clear=$unix->find_program("clear");
if(is_file($clear)){system("$clear");}

echo "Credentials Menu\n";
echo "---------------------------------------------\n";
echo "Display SuperAdmin credentials...: [1]\n";
echo "Modify SuperAdmin credentials....: [2]\n";
echo "Exit menu........................: [q]\n";
echo "\n";

$answer=trim(strtolower(fgets(STDIN)));

switch ($answer) {
	case "1":ShowPassword();break;
	case "2":ChangePassword();break;
	case "q":exit();break;
	default:
		;
	break;
	
	MAIN_MENU();
	return;
	
}


}


function ShowPassword(){
	system("clear");
	$ldap=new clladp();
	echo "\nUse the Account $ldap->ldap_admin\nAnd the password $ldap->ldap_password\nIn order to connect trough the Web administration console\as SuperAdmin\n\nPress Enter Key to exit...\n";
	$answer=trim(strtolower(fgets(STDIN)));
	system("clear");
}



function ChangePassword(){
	system("clear");
	echo "This operation will change the SuperAdmin account and\n";
	echo "password.\n";
	echo "Press Q to exit or Enter to change credentials\n";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$answer=trim(strtolower(fgets(STDIN)));
	
	if($answer=="q"){die(0);}
	system("clear");
	
	$ldap=new clladp();
	
	
	echo "Please define the username: [default: $ldap->ldap_admin]:\n";
	$ldap_admin=trim(strtolower(fgets(STDIN)));
	if($ldap_admin==null){$ldap_admin=$ldap->ldap_admin;}
	$ldap_password=askpassword($ldap_admin);
	
	echo "Saving new credentials $ldap_admin:*****\n";
	@mkdir("/etc/artica-postfix/ldap_settings",0755,true);
	@file_put_contents("/etc/artica-postfix/ldap_settings/admin",$ldap_admin);
	@file_put_contents("/etc/artica-postfix/ldap_settings/password", $ldap_password);
	
	system("$php /usr/share/artica-postfix/exec.change.password.php");
	
	echo "Rebuilding OpenLDAP service...\n";
	@unlink("/etc/artica-postfix/no-ldap-change");
	@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
	system("/usr/share/artica-postfix/bin/artica-install --slapdconf");
	echo "Restarting OpenLDAP service...\n";
	system("/etc/init.d/slapd restart --force");
	echo "Synchronize Artica settings\n";
	system("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force");
	echo "Restarting Web console\n";
	system("/etc/init.d/artica-webconsole restart");
	echo "Press Q to exit or Enter\n";
	$answer=trim(strtolower(fgets(STDIN)));
	exit();
}

function askpassword($ldap_admin){
	echo "Type the password of $ldap_admin\n";
	$password1=trim(fgets(STDIN));
	echo "Re-type the password of $ldap_admin\n";
	$password2=trim(fgets(STDIN));
	if($password1<>$password2){
		echo "Password did not match..\n";
		echo "Press Enter to retry or Q to abort\n";
		$answer=trim(strtolower(fgets(STDIN)));
		if($answer=="q"){die(0);}
		return askpassword($ldap_admin);
	}
	return $password1;
}
?>