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


menu();



function menu(){
	system("clear");
	echo "Restart MySQL service.....................: [R]\n";
	echo "Change the Local MySQL root password......: [P]\n";
	echo "Use a remote MySQL service................: [R]\n";
	echo "MySQL status..............................: [S]\n";
	echo "MySQL Events..............................: [E]\n";
	echo "Return to main menu.......................: [Q]\n";
	
	$answer=trim(strtolower(fgets(STDIN)));
	
	if($answer=="q"){die(0);}
	
	if($answer=="r"){
		RestartMySQL();
		menu();
		return;
	}
	if($answer=="p"){
		ChangeRootPassword();
		menu();
		return;
	}	
	if($answer=="s"){
		mysql_status();
		menu();
		return;
	}	
	
	if($answer=="r"){
		mysql_remote();
		menu();
		return;
	}

	if($answer=="e"){
		mysql_events();
		menu();
		return;
	}	

}

function RestartMySQL(){
	squid_admin_mysql(1,"Restarting MySQL service...", null,__FILE__,__LINE__);
	system("/etc/init.d/mysql restart --force --framework=".__FILE__);
	echo "Type Enter key to exit.\n";
	$answer=trim(strtolower(fgets(STDIN)));
	menu();
}

function ChangeRootPassword(){
	
	$unix=new unix();
	echo "Give the root password:\n";
	$answer=trim(fgets(STDIN));
	if($answer==null){
		echo "No password set\n";
		echo "Type Enter key to exit.\n";
		$answer=trim(strtolower(fgets(STDIN)));
		menu();
		return;
	}
	
	$password=$unix->shellEscapeChars($answer);
	
	$cmd="/usr/share/artica-postfix/bin/artica-install --change-mysqlroot --inline \"root\" \"$password\"";
	echo "Running $cmd\n";
	system($cmd);
	echo "Refresh settings...\n";
	system("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force");
	echo "\n\nType Enter key to exit.\n";
	$answer=trim(strtolower(fgets(STDIN)));
	
	
}

function mysql_remote(){
	$unix=new unix();
	echo "Give the MySQL Username:\n";
	$MySQLUsername=trim(fgets(STDIN));
	echo "Give the MySQL password:\n";
	$MySQLPassword=trim(fgets(STDIN));	
	echo "Give the MySQL Servername address:\n";
	$MySQLServer=trim(strtolower(fgets(STDIN)));		
	echo "Give the MySQL Remote Port: [3306]\n";
	$MySQLPort=trim(strtolower(fgets(STDIN)));	
	
	echo "MySQL Paramaters:\n*******************************\n";
	echo "MySQL username..: $MySQLUsername\n";
	echo "MySQL Password..: $MySQLPassword\n";
	echo "MySQL server....: $MySQLServer\n";
	echo "MySQL Port......: $MySQLPort\n";

	echo "\n\nType Y key to continue or any other to abort.\n";
	$answer=trim(strtolower(fgets(STDIN)));	
	if($answer<>"y"){return;}
	@file_put_contents("/etc/artica-postfix/settings/Mysql/database_admin", $MySQLUsername);
	@file_put_contents("/etc/artica-postfix/settings/Mysql/database_password", $MySQLPassword);
	@file_put_contents("/etc/artica-postfix/settings/Mysql/database_password2", $MySQLPassword);
	@file_put_contents("/etc/artica-postfix/settings/Mysql/mysql_server",$MySQLServer);
	@file_put_contents("/etc/artica-postfix/settings/Mysql/port",$MySQLPort);
	echo "Refresh settings...\n";
	system("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force");
	echo "\n\nType Enter key to exit.\n";
	$answer=trim(strtolower(fgets(STDIN)));	
	
	
}

function mysql_events(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	system("$tail -n 50 /var/lib/mysql/mysqld.err");
	echo "\n\nType Enter key to exit.\n";
	$answer=trim(strtolower(fgets(STDIN)));
	
	
}

function mysql_status(){
	$q=new mysql();
	if(!$q->test_mysqli_connection()){
		echo " ********* FAILED **************\n";
		echo $q->mysql_error."\n";
		echo "\n\nType Enter key to exit.\n";
		$answer=trim(strtolower(fgets(STDIN)));
		menu();
	}
	
	echo "Connecting to MySQL server success\n";
	
	echo "MySQL Paramaters:\n*******************************\n";
	echo "MySQL username..: $q->mysql_admin\n";
	echo "MySQL Password..: $q->mysql_password\n";
	echo "MySQL server....: $q->mysql_server\n";
	echo "MySQL Port......: $q->mysql_port\n";
	echo "MySQL SocketName: $q->SocketName\n";
	echo "\n\nType Enter key to continue.\n";
	$answer=trim(strtolower(fgets(STDIN)));
	
	
	$GLOBALS["VERBOSE"]=true;
	$q->BuildTables();
	$GLOBALS["VERBOSE"]=false;
	echo "\n\nType Enter key to exit.\n";
	$answer=trim(strtolower(fgets(STDIN)));
	menu();	
}



