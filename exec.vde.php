<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Virtual Switch";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');




$GLOBALS["ARGVS"]=implode(" ",$argv);

if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_switch($argv[2]);exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop_all();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start_all();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;reconfigure();exit();}
if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;vde_status();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;reconfigure();exit();}
if($argv[1]=="--vlan"){$GLOBALS["OUTPUT"]=true;vde_plug2tap_vlan($argv[2]);exit();}
if($argv[1]=="--vde-routes"){$GLOBALS["OUTPUT"]=true;vde_check_routes($argv[2]);exit();}
if($argv[1]=="--start-switch"){$GLOBALS["OUTPUT"]=true;start_switch($argv[2]);exit();}
if($argv[1]=="--restart-switch"){$GLOBALS["OUTPUT"]=true;restart_switch($argv[2]);exit();}
if($argv[1]=="--reconfigure-switch"){$GLOBALS["OUTPUT"]=true;reconfigure_switch($argv[2]);exit();}
if($argv[1]=="--stop-switch"){$GLOBALS["OUTPUT"]=true;stop_switch($argv[2]);exit();}
if($argv[1]=="--vde-all"){$GLOBALS["OUTPUT"]=true;vde_all();exit();}

if($argv[1]=="--pcapplug-start"){$GLOBALS["OUTPUT"]=true;vde_pcapplug($argv[2]);exit();}
if($argv[1]=="--pcapplug-stop"){$GLOBALS["OUTPUT"]=true;vde_pcapplug_down($argv[2]);exit();}

if($argv[1]=="--remove"){$GLOBALS["OUTPUT"]=true;remove_switch($argv[2]);exit();}
if($argv[1]=="--build-port"){$GLOBALS["OUTPUT"]=true;port_create($argv[2]);exit;}
if($argv[1]=="--start-port"){$GLOBALS["OUTPUT"]=true;port_start($argv[2]);exit;}
if($argv[1]=="--stop-port"){$GLOBALS["OUTPUT"]=true;port_stop($argv[2]);exit;}
if($argv[1]=="--remove-port"){$GLOBALS["OUTPUT"]=true;port_remove($argv[2]);exit;}







function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
		
	stop_all(true);
	reconfigure(true);
	sleep(1);
	start_all(true);
	
	
	
}
function progress_remove($title,$perc){
	echo "*** $title {$perc}%\n";
	
	$array["POURC"]=$perc;
	$array["TEXT"]=$title;
	echo "{$perc}% $title\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/virtualswitch.uninstall.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/support/support.progress", 0755);
}


function reconfigure_switch($switch,$aspid=false){
	$GLOBALS["TITLENAME"]="Virtual Switch for $switch";
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	
	
	$INITD_PATH="/etc/init.d/virtualnet-$switch";
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
	}
	
	@unlink($INITD_PATH);
	
	$VirtualSwitchEnabled=$sock->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Virtual Switch is disabled\n";}
		return;
	}
	
	$sql="SELECT * FROM nics_switch WHERE nic='$switch'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$toto=@mysqli_num_rows($results);
	if($toto==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} no interface set\n";}
		return;
	}
	

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $toto interfaces\n";}
	$killbin=$unix->find_program("kill");
	$php=$unix->LOCATE_PHP5_BIN();
	$ifconfig=$unix->find_program("ifconfig");
	$ipbin=$unix->find_program("ip");
	$vde_tunctl=$unix->find_program("vde_tunctl");
	$vde_plug2tap=$unix->find_program("vde_plug2tap");
	$rmbin=$unix->find_program("rm");
	$arp=$unix->find_program("arp");
	$cat=$unix->find_program("cat");
	$routebin=$unix->find_program("route");
	$LOGFILE=" >>/var/log/vde-ports.log 2>&1";
	$pgrepbin=$unix->find_program("pgrep");
	
	$START=array();
	$STOP=array();
	
	$START[]="if [ ! -f /etc/init.d/virtualswitch-$switch ]";
	$START[]="then";
	$START[]="	echo \"Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not installed\"";
	$START[]="	exit 0";
	$START[]="fi";
	$START[]="";
	$START[]="$php ".__FILE__." --start-switch $switch";
	
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ID=$ligne["ID"];
		$START[]="/etc/init.d/virtualport-$ID start";
		$STOP[]="/etc/init.d/virtualport-$ID stop";
	}
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          virtualnet-$switch";
	$f[]="# Required-Start:    \$local_fs \$syslog \$virtualswitch-$switch";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Virtual Switch $switch network";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Virtual Switch $switch network";
	$f[]="### END INIT INFO";
	$f[]="IFCONFIG=$ifconfig";
	$f[]="IPBIN=$ipbin";
	$f[]="VDE_TUNCTL=$vde_tunctl";
	$f[]="ARP_BIN=$arp";
	$f[]="vde_plug2tap=$vde_plug2tap";
	$f[]="RMBIN=$rmbin";
	$f[]="";
	
	
	
	
	$f[]="do_start () {";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} START():".count($START)."\n";}
	$f[]=@implode("\n", $START);
	$f[]="}";
	$f[]="";
	$f[]="do_stop () {";
	$f[]=@implode("\n", $STOP);
	$f[]="}";
	$f[]="";	
	
	$f[]="case \"\$1\" in";
	
	$f[]=" start)";	
	$f[]="\techo \"Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} network\"";
	$f[]="\tdo_start";
	$f[]=" exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="\techo \"Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} network\"";
	$f[]="\tdo_stop";
	$f[]=" exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="\tdo_stop";
	$f[]="\tdo_start";
	$f[]=" exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="\tdo_stop";
	$f[]="\tdo_start";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";	
	
	
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $INITD_PATH done\n";}
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}




function restart_switch($eth,$aspid=false){
	$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$eth.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	vde_switch_down($eth);
	sleep(1);
	start_switch($eth,true);
	
}

function stop_switch($eth,$aspid=false){
	$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$eth.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	vde_switch_down($eth);
}

function start_switch($eth,$aspid=false){
	$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$eth.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	shell_exec_logs("$sysctl -w net.ipv4.ip_forward=1 >/dev/null 2>&1");
	vde_switch($eth);	
	
	
}

function port_create($ID){
	port_remove($ID);
	$sql="SELECT * FROM nics_switch WHERE ID='$ID'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	
	
	while (list ($num, $line) = each ($ligne) ){
		if(is_numeric($num)){continue;}
		$MAIN_ARRAY[$num]=$line;
	}
	
	@mkdir("/etc/vde/Interfaces",0755,true);
	@file_put_contents("/etc/vde/Interfaces/$ID", serialize($MAIN_ARRAY));
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$SCRIPTFILENAME=__FILE__;
	$INIT_D="/etc/init.d/virtualport-$ID";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         virtualport-$ID";
	$f[]="# Required-Start:    \$local_fs \$network";
	$f[]="# Required-Stop:     \$local_fs \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: virtualport-$ID";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: virtualport-$ID";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php $SCRIPTFILENAME --start-port $ID --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php $SCRIPTFILENAME --stop-port $ID --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	
	$f[]="    $php $SCRIPTFILENAME --stop-port $ID --byinitd --force \$2 \$3";
	$f[]="    $php $SCRIPTFILENAME --start-port $ID --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	@file_put_contents($INIT_D, @implode("\n", $f));
	@chmod($INIT_D,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f ".basename($INIT_D)." defaults >/dev/null 2>&1");
	
	}
	
	if(is_file("/sbin/chkconfig")){
		shell_exec("/sbin/chkconfig --add ".basename($INIT_D)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 ".basename($INIT_D)." on >/dev/null 2>&1");
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: ".basename($INIT_D)." success...\n";}	
	shell_exec($INIT_D." start");
	
	
}


function switchcommand($eth,$command){
	$unix=new unix();
	if(!is_file("/etc/vde2/vdecmd")){
		$f[]="TIMEOUT 1000";
		$f[]="1 IN '\$ ' 100";
		$f[]="3 SEND '\$*\\n'";
		$f[]="5 THROW";
		$f[]="6 IN '\n' 100";
		$f[]="7 IF '0000 DATA END WITH \'.\'' 10";
		$f[]="8 IF '10' 20";
		$f[]="9 GOTO 100";
		$f[]="10 THROW ";
		$f[]="11 IN '\\n' 100";
		$f[]="12 IF '.\\n' 5";
		$f[]="13 COPY";
		$f[]="14 GOTO 10";
		$f[]="20 SKIP 2";
		$f[]="21 SEND 'logout\\n'";
		$f[]="22 EXITATOI";
		$f[]="100 EXIT -1";
		@file_put_contents("/etc/vde2/vdecmd", @implode("\n", $f));
	}
	
	$moins=" -s";
	$moinsf=" -f /etc/vde2/vdecmd";
	$vdecmd=$unix->find_program("vdecmd");
	if(!is_file($vdecmd)){$vdecmd=$unix->find_program("unixcmd");}
	if(!is_file($vdecmd)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: vdecmd no such binary\n";}return;}	
	$sock="/var/run/switchM$eth";
	if($GLOBALS["VERBOSE"]){echo "$vdecmd -s $sock $command\n";}
	exec("$vdecmd$moins $sock$moinsf $command 2>&1",$results);
	return $results;
}


function port_plug_vlan($ID){
	$unix=new unix();
	$GLOBALS["TITLENAME"]="Port $ID";
	$configfile="/etc/vde/Interfaces/$ID";
	if(!is_file("$configfile")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $configfile no such file\n";}
		return;
	}	
	
	$ligne=unserialize(@file_get_contents($configfile));
	$vlan=$ligne["vlan"];
	if(!is_numeric($vlan)){$vlan=0;}
	if($vlan==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No VLAN\n";}
		return;
	}
	$eth=$ligne["nic"];
	$port=$ligne["port"];
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Create VLAN $vlan into Switch $eth\n";}
	switchcommand($eth,"vlan/create $vlan");

	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Linking VLAN $vlan to port $port Switch $eth\n";}
	switchcommand($eth,"vlan/addport $port $vlan");
	
	
}
function port_stop($ID){
	$unix=new unix();
	$GLOBALS["TITLENAME"]="Port $ID";
	$configfile="/etc/vde/Interfaces/$ID";
	if(!is_file("$configfile")){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $configfile no such file\n";}
		return;
	}
	
	if(!vde_plug2tap_down($ID)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Port $ID cannot be unplugged\n";}
		return;
	}
	
	$ligne=unserialize(@file_get_contents($configfile));
	$vde_tunctl=$unix->find_program("vde_tunctl");
	$routebin=$unix->find_program("route");
	$ifconfig=$unix->find_program("ifconfig");
	$virtname="virt{$ID}";
	$ipaddr=$ligne["ipaddr"];
	$gateway=$ligne["gateway"];
	$cdir=$ligne["cdir"];
	$metric=$ligne["metric"];
	$netmask=$ligne["netmask"];
	$port=$ligne["port"];
	if(!is_numeric($metric)){$metric="10{$ID}";}
	if(!is_numeric($port)){$port=1;}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Port $ID $virtname $ipaddr/$netmask $gateway\n";}
	$ip=new IP();
	if(!$ip->isIPAddress($ip)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Port $ID bad ip address\n";}
		return;
	}
	if($cdir<>null){
		shell_exec("$routebin del -net $cdir gw $gateway dev $virtname >/dev/null 2>&1");
	}
	
	shell_exec("$routebin del -net 0.0.0.0 gw $gateway dev $virtname >/dev/null 2>&1");
	shell_exec("$ifconfig $virtname down >/dev/null 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Port $ID success\n";}
	
	$ip=$unix->find_program("ip");
	system("$ip rule show 2>&1",$results);
	
	$iprule_added=false;
	foreach ($results as $line){
		if(preg_match("#from all iif $virtname lookup $virtname#", $line)){$iprule_added=true;break;}
	}
	
	if($iprule_added){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Removing IP rule success\n";}
		system("$ip rule del from all iif $virtname lookup $virtname 2>&1",$results);
	}
	
	
}

function port_start($ID){
	$unix=new unix();
	$GLOBALS["TITLENAME"]="Port $ID";
	$configfile="/etc/vde/Interfaces/$ID";
	if(!is_file("$configfile")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $configfile no such file\n";}
		return;
	}
	
	if(!vde_plug2tap($ID)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Port $ID not plugged\n";}
		return;
	}
	
	$ligne=unserialize(@file_get_contents($configfile));
	$vde_tunctl=$unix->find_program("vde_tunctl");
	$routebin=$unix->find_program("route");
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	$virtname="virt{$ID}";
	$ipaddr=$ligne["ipaddr"];
	$gateway=$ligne["gateway"];
	$cdir=$ligne["cdir"];
	$metric=$ligne["metric"];
	$netmask=$ligne["netmask"];
	$port=$ligne["port"];
	if(!is_numeric($metric)){$metric="10{$ID}";}
	if(!is_numeric($port)){$port=1;}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Port $ID $virtname $ipaddr/$netmask $gateway\n";}
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Port $ID $vde_tunctl -t $virtname\n";}
	shell_exec("$vde_tunctl -t $virtname >/dev/null 2>&1");
	shell_exec("$ifconfig $virtname up");
	shell_exec("$ifconfig $virtname $ipaddr netmask $netmask up");
	
	
	
	
	if($gateway<>null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Port $ID Checking Routes\n";}
		vde_check_routes($ID);
	}
	
	
	if($ligne["vlan"]>0){port_plug_vlan($ID);}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Port $ID $virtname success\n";}
	
	
	
	
}

function port_remove($ID){
	
	progress_remove("{removing}: $ID",15);
	
	$INIT_D="/etc/init.d/virtualport-$ID";
	if($GLOBALS["VERBOSE"]){$verb=" --verbose";}
	progress_remove("{removing}: $ID",20);
	system("$INIT_D stop");
	@unlink("/etc/vde/Interfaces/$ID $verb");
	if(is_file('/usr/sbin/update-rc.d')){
		progress_remove("{removing}: $ID",30);
		shell_exec("/usr/sbin/update-rc.d -f virtualport-$ID remove >/dev/null 2>&1");
		@unlink("/etc/init.d/virtualport-$ID");
	}
	if(is_file('/sbin/chkconfig')){
		progress_remove("{removing}: $ID",30);
		shell_exec("/sbin/chkconfig --del virtualport-$ID >/dev/null 2>&1");
		@unlink("/etc/init.d/virtualport-$ID");
	}	
	
	progress_remove("{removing}: $ID",50);
	
	

	
}

function port_remove_virt_interface($virtname,$nic){
	$unix=new unix();
	$virtname=$_GET["virtual-delete"];
	$nic=$_GET["nic"];
	$pidfile="/var/run/$virtname.pid";
	$ipbin=$unix->find_program("ip");
	$ifconfig=$unix->find_program("ifconfig");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();


	$pid=$unix->get_pid_from_file($pidfile);
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){unix_system_kill($pid);sleep(1);}
	if($unix->process_exists($pid)){unix_system_kill_force($pid);sleep(1);}

	$cmd="$ipbin route flush table $virtname";
	echo "$cmd\n";
	system("$cmd");



	$cmd="/usr/share/artica-postfix/bin/rt_tables.pl --remove-name $virtname >/dev/null 2>&1";
	echo "$cmd\n";
	system("$cmd");


	$cmd="$ifconfig $virtname down";
	echo "$cmd\n";
	system("$cmd");
}


function start_all($aspid=false){
	
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["TITLENAME"]=$GLOBALS["TITLENAME"]." ($ID)";
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	$dirs=$unix->dirdir("/etc/vde_switch_config");
	shell_exec_logs("$sysctl -w net.ipv4.ip_forward=1 >/dev/null 2>&1");
	
	while (list ($num, $ligne) = each ($dirs) ){
		$eth=basename($num);
		$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
		vde_switch($eth);
		
	}
}


function stop_all($aspid=false){
	
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$dirs=$unix->dirdir("/etc/vde_switch_config");
	while (list ($num, $ligne) = each ($dirs) ){
		$eth=basename($num);
		$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}\n";}
		vde_switch_down($eth);
	
	}
}

function vde_status($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$ips=$unix->NETWORK_ALL_INTERFACES();
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	while (list ($eth, $ligne) = each ($ips) ){
		if(!preg_match("#^virt([0-9]+)#", $eth,$re)){
			if($GLOBALS["VERBOSE"]){echo "$eth SKIP...\n";}
			continue;}
		$ID=$re[1];
		$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
		$eth=$ligne["nic"];
		$virtname="virt$ID";
		
		$pid=vde_switch_pid($eth);
		if($unix->process_exists($pid)){
			$ARRAY[$virtname]["VDE"]=$pid;
			$ARRAY[$virtname]["VDE_RUN"]=$unix->PROCCESS_TIME_MIN($pid);
		}
		

		$pid=vde_plug2tap_pid($virtname);
		if($unix->process_exists($pid)){
			$ARRAY[$virtname]["PCAP"]=$pid;
			$ARRAY[$virtname]["PCAP_RUN"]=$unix->PROCCESS_TIME_MIN($pid);
		}
		
	}
	
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0777,true);
	@file_put_contents(PROGRESS_DIR."/vde_status", serialize($ARRAY));
	@chmod(PROGRESS_DIR."/vde_status",0755);

	
	
}



function vde_route_set_tables($virtname){
	$LastNumber=0;
	$TABLES=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	while (list ($id, $ligne) = each ($TABLES) ){
		if(!preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){continue;}
		if($re[1]==0){continue;}
		if(trim($re[2])==$virtname){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $virtname << FOUND >> table {$re[1]}\n";}
			return $virtname;}
		if($re[1]>$LastNumber){$LastNumber=$re[1];}
		
	}
	$LastNumber=$LastNumber+1;
	$TABLES[]="$LastNumber\t$virtname";
	@file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $TABLES));
	
}
function vde_port_saveconf($ID){
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM nics_switch WHERE ID='$ID'","artica_backup"));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ID creating /etc/vde_switch_config/{$ID}.conf\n";}
	@mkdir("/etc/vde_switch_config",0755,true);
	@file_put_contents("/etc/vde_switch_config/{$ID}.conf", serialize($ligne));
	
}

function vde_route_set_rule($tablename,$ipaddr){
	
	
	
}

function vde_check_routes($ID){
	$virtname="virt{$ID}";
	$config_file="/etc/vde_switch_config/{$ID}.conf";
	if(!is_file($config_file)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $virtname creating configuration file\n";}
		vde_port_saveconf($ID);
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $virtname Checking $config_file\n";}
	$unix=new unix();
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	
	$ip=$unix->find_program("ip");
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ip route 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#(.+?)\s+dev#",$line,$re)){
			$ROUTES[$re[1]]=true;
		}
		
	}
	$ip=$unix->find_program("ip");

	
	$ipaddr=$ligne["ipaddr"];

	$tbCDIR=explode(".",$ipaddr);
	$SourceCDIR="{$tbCDIR[0]}.{$tbCDIR[1]}.{$tbCDIR[2]}.0/24";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $virtname Network should be:$SourceCDIR\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $virtname Network should be:$cdir\n";}
	
	$gateway=$ligne["gateway"];
	$cdir=$ligne["cdir"];	
	$metric=$ligne["metric"];
	$netmask=$ligne["netmask"];
	if(!is_numeric($metric)){$metric="10{$ID}";}
	if($gateway==null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $virtname No gateway, aborting\n";}
		return;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Adding $gateway to $virtname CDIR:$cdir\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Checking IP Rules...\n";}
	exec("$ip rule show 2>&1",$results);
	
	$iprule_added=false;
	foreach ($results as $line){
		if(preg_match("#from all iif $virtname lookup $virtname#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: [OK]: $line\n";}
			$iprule_added=true;break;}
	}
	
	
	if($cdir<>null){
		vde_route_set_tables($virtname);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing route $cdir dev $virtname\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ip route del $cdir dev $virtname\n";}
		shell_exec("$ip route del $cdir dev $virtname");
		if($SourceCDIR<>$cdir){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing route $SourceCDIR dev $virtname\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ip route del $SourceCDIR dev $virtname\n";}
			shell_exec("$ip route del $SourceCDIR dev $virtname");
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Adding $virtname table $virtname for gateway $gateway\n";}
		shell_exec_logs("$ip route add $gateway dev $virtname table $virtname >/dev/null 2>&1");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Adding $cdir to $virtname trough $gateway table $virtname\n";}
		shell_exec_logs("$ifconfig $virtname up >/dev/null 2>&1");
		$cmd="$ip route add default via $gateway dev $virtname metric $metric table $virtname";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
		shell_exec_logs($cmd);
		
		if(!$iprule_added){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ip rule add iif $virtname table $virtname\n";}
			shell_exec_logs("$ip rule add iif $virtname table $virtname");
		}
		
		
		return;
	}
	
	$t=explode(".",$ipaddr);
	$t[3]=0;
	$net=@implode(".", $t);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Adding $cdir to $virtname trough $gateway\n";}
	shell_exec_logs("$ifconfig $virtname up >/dev/null 2>&1");
	shell_exec_logs("$ip route add $gateway dev $virtname table $virtname >/dev/null 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Adding default to $virtname trough $gateway table $virtname\n";}
	shell_exec_logs("$ip route add default via $gateway dev $virtname metric $metric table $virtname");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ip rule add iif $virtname table $virtname\n";}
	if(!$iprule_added){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ip rule add iif $virtname table $virtname\n";}
		shell_exec_logs("$ip rule add iif $virtname table $virtname");
	}
	
	
	
}


function vde_config($ID){
}

function vde_plug2tap_down($ID){
	$unix=new unix();
	$sock=new sockets();
	
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	$eth=$ligne["nic"];
	$virtname="virt$ID";
	$GLOBALS["TITLENAME"]="Interface Plug $virtname";
	
	
	$pid=vde_plug2tap_pid($virtname);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped\n";}
		@unlink("/var/run/$virtname.pid");
		return true;
	}
	
	
	
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	shell_exec_logs("$ifconfig $virtname down >/dev/null 2>&1");	
	
	$kill=$unix->find_program("kill");
	$pid=vde_plug2tap_pid($virtname);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec_logs("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_plug2tap_pid($virtname);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=vde_plug2tap_pid($virtname);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		@unlink("/var/run/$virtname.pid");
		return true;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=vde_plug2tap_pid($virtname);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=vde_plug2tap_pid($virtname);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}	
	@unlink("/var/run/$virtname.pid");
	return true;
}


function vde_plug2tap($ID){
	$unix=new unix();
	$sock=new sockets();
	$configfile="/etc/vde/Interfaces/$ID";
	$Masterbin=$unix->find_program("vde_plug2tap");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, vde_plug2tap not installed\n";}
		return;
	}
	
	
	
	$ligne=unserialize(@file_get_contents($configfile));
	$eth=$ligne["nic"];
	$port=$ligne["port"];
	$virtname="virt$ID";
	$vlan=$ligne["vlan"];
	$GLOBALS["TITLENAME"]="Interface Plug $virtname";
	$nohup=$unix->find_program("nohup");
	
	switchcommand($eth,"port/create $port");
	$pid=vde_plug2tap_pid($virtname);
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	@unlink("/var/run/$virtname.pid");
	$port=$ligne["port"];
	if(!is_numeric($port)){$port=1;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}\n";}
	$cmd="$Masterbin -s /var/run/switch$eth --port=$port --daemon -P /var/run/$virtname.pid $virtname >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Switch $eth Interface $virtname port $port\n";}
	shell_exec_logs($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=vde_plug2tap_pid($virtname);
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=vde_plug2tap_pid($virtname);
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}	
	
}




function vde_plug2tap_pid($virtname){
	if($GLOBALS["VERBOSE"]){echo "PID: /var/run/$virtname.pid\n";}
	$pid=trim(@file_get_contents("/var/run/$virtname.pid"));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vde_plug2tap");
	return $unix->PIDOF_PATTERN("$Masterbin.*?$virtname");
}






function vde_switch($eth){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("vde_switch");
	$vde_tunctl=$unix->find_program("vde_tunctl");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, vde switch not installed\n";}
		return;
	}
	
	$VirtualSwitchEnabled=$sock->GET_INFO("VirtualSwitchEnabled{$eth}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, vde switch not enabled ( see VirtualSwitchEnabled{$eth} )\n";}
		return;
	}

	$pid=vde_switch_pid($eth);

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		vde_pcapplug($eth,true);
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	$cmd="$nohup $Masterbin -s /var/run/switch$eth -M /var/run/switchM$eth -daemon -p /var/run/switch-$eth.pid >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec_logs($cmd);

	for($i=1;$i<4;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/3\n";}
		sleep(1);
		$pid=vde_switch_pid($eth);
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=vde_switch_pid($eth);
	if(!$unix->process_exists($pid)){	
		shell_exec_logs($cmd);
		sleep(1);
	}

	$pid=vde_switch_pid($eth);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		vde_pcapplug($eth,true);
		
		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}
}

function vde_nics_shutdown($eth){


	$c=0;
	foreach (glob("/etc/vde/Interfaces/*") as $filename) {
		$c++;
		$file=basename($filename);
		if(is_numeric($filename)){
			port_stop($filename,true);
		}
	}

	$GLOBALS["TITLENAME"]="Virtual Interfaces ($eth)";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $c Interface(s)\n";}


}

function vde_pcapplug_init_install($eth){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          pcapplug-$eth";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Capture Plug ($eth)";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Capture Plug ($eth)";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".__FILE__." --pcapplug-start $eth \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".__FILE__." --pcapplug-stop $eth \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".__FILE__." --pcapplug-stop $eth \$2 \$3";
	$f[]="   $php ".__FILE__." --pcapplug-start $eth \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".__FILE__." --pcapplug-stop $eth \$2 \$3";
	$f[]="   $php ".__FILE__." --pcapplug-start $eth \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/virtualhook-$eth";
	echo "vde_pcapplug: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}


function vde_pcapplug($eth,$aspid=false){
	$unix=new unix();
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$eth.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	
	$sock=new sockets();
	$Masterbin=$unix->find_program("vde_pcapplug");
	
	$VirtualSwitchEnabled=$sock->GET_INFO("VirtualSwitchEnabled{$eth}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	
	
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, vde switch not enabled - VirtualSwitchEnabled{$eth}:$VirtualSwitchEnabled -\n";}
		return;
	}
	
	
	$GLOBALS["TITLENAME"]="Capture Plug ($eth)";
	
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, vde switch not installed\n";}
		return;
	}
	
	
	if(!$unix->is_interface_available($eth)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $eth not available\n";}
		return;
	}
	
	$pid=vde_pcapplug_pid($eth);
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		if(is_file("/etc/init.d/virtualnet-$eth")){
			shell_exec("/etc/init.d/virtualnet-$eth start");
		}
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$Masterbin --sock=/var/run/switch$eth --daemon --port=1 --pidfile=/var/run/switch{$eth}p.pid $eth >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service on real interface $eth\n";}
	shell_exec_logs($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=vde_pcapplug_pid($eth);
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=vde_pcapplug_pid($eth);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}

	
	
}

function vde_switch_down($eth){

	$pid=vde_switch_pid($eth);
	$GLOBALS["TITLENAME"]="Virtual Switch ($eth)";
	$unix=new unix();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		vde_pcapplug_down($eth);
		vde_nics_shutdown($eth);
		return;
	}
	
	vde_pcapplug_down($eth,true);
	if(is_file("/etc/init.d/virtualnet-$eth")){
		shell_exec("/etc/init.d/virtualnet-$eth stop");
	}
	
	$pid=vde_switch_pid($eth);
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$rm=$unix->find_program("rm");
	$GLOBALS["TITLENAME"]="Virtual Switch ($eth)";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec_logs("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_switch_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=vde_switch_pid($eth);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} cleaning sockets\n";}
		shell_exec_logs("rm -rf /var/run/switch$eth");
		@unlink("/var/run/switch{$eth}p.pid");
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=vde_switch_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} cleaning sockets\n";}
	shell_exec_logs("rm -rf /var/run/switch$eth");
	@unlink("/var/run/switch{$eth}p.pid");
	

}
function vde_pcapplug_down($eth,$aspid=false){
	$GLOBALS["TITLENAME"]="Capture Plug ($eth)";
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$eth.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=vde_pcapplug_pid($eth);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=vde_pcapplug_pid($eth);
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec_logs("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_pcapplug_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=vde_pcapplug_pid($eth);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=vde_pcapplug_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function shell_exec_logs($cmdline){
	$trace=debug_backtrace();
	if(isset($trace[1])){
		$function="{$trace[1]["function"]}()";
		$line="{$trace[1]["line"]}";			
		}
	
		$f = @fopen("/var/log/net-start.log", 'a');
		@fwrite($f, "exec.vde.php:: $cmdline function $function in line $line\n");
		@fclose($f);
		shell_exec($cmdline);
	
}

function vde_switch_pid($eth){
	$pid=trim(@file_get_contents("/var/run/switch-$eth.pid"));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vde_switch");
	return $unix->PIDOF_PATTERN("$Masterbin.*?$eth");
	
}
function vde_pcapplug_pid($eth){
	if($GLOBALS["VERBOSE"]){echo "PID: /var/run/switch{$eth}p.pid\n";}
	$pid=trim(@file_get_contents("/var/run/switch{$eth}p.pid"));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vde_pcapplug");
	return $unix->PIDOF_PATTERN("$Masterbin.*?switchp{$eth}.pid");	
}

function remove_switch($switch){
	
	progress_remove("{removing}: {stopping_service}",15);
	shell_exec("/etc/init.d/virtualnet-$switch stop");
	
	progress_remove("{removing}: {stopping_service}",20);
	vde_switch_down($switch,true);
	
	progress_remove("{removing}: {stopping_service}",25);
	vde_pcapplug_down($switch,true);
	
	if(is_file('/usr/sbin/update-rc.d')){
		progress_remove("{removing}: virtualnet-$switch,virtualswitch-$switch,virtualhook-$switch",30);
		shell_exec("/usr/sbin/update-rc.d -f virtualnet-$switch remove >/dev/null 2>&1");
		shell_exec("/usr/sbin/update-rc.d -f virtualswitch-$switch remove >/dev/null 2>&1");
		shell_exec("/usr/sbin/update-rc.d -f virtualhook-$switch remove >/dev/null 2>&1");
		@unlink("/etc/init.d/virtualnet-$switch");
		@unlink("/etc/init.d/virtualswitch-$switch");
		@unlink("/etc/init.d/virtualhook-$switch");
	}
	if(is_file('/sbin/chkconfig')){
		progress_remove("{removing}: virtualnet-$switch,virtualswitch-$switch,virtualhook-$switch",30);
		shell_exec("/sbin/chkconfig --del virtualnet-$switch >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --del virtualswitch-$switch >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --del virtualhook-$switch >/dev/null 2>&1");
		@unlink("/etc/init.d/virtualnet-$switch");
		@unlink("/etc/init.d/virtualswitch-$switch");
		@unlink("/etc/init.d/virtualhook-$switch");
	}
	
	
	progress_remove("{removing}: {interfaces}",50);
	$sql="SELECT ID FROM nics_switch WHERE nic='$switch'";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(mysqli_num_rows($results)==0){
		progress_remove("{removing}: $switch {success}",100);
		return;
	}
	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$virtname="virt{$ligne["ID"]}";
		progress_remove("{removing}: $switch/$virtname",80);
		port_remove_virt_interface($virtname,$switch);
		$q->QUERY_SQL("DELETE FROM nics_switch WHERE ID={$ligne["ID"]}","artica_backup");
		$q->QUERY_SQL("DELETE FROM routing_rules WHERE nic='$virtname'","artica_backup");
		$sh=new mysql_shorewall();
		$sh->INTERFACE_DELETE($virtname);
	
	}
	progress_remove("{removing}: $switch {success}",100);
	
}


function install_switch($switch){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          virtualswitch-$switch";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Virtual Switch $switch";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Virtual Switch $switch";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".__FILE__." --start-switch $switch \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".__FILE__." --stop-switch $switch \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".__FILE__." --restart-switch $switch \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".__FILE__." --reload-switch $switch \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/virtualswitch-$switch";
	echo "Virtual Switch: [INFO] Writing \"$INITD_PATH\" with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	vde_pcapplug_init_install($switch);
	
}

function vde_all(){
	$unix=new unix();
	
	$files=$unix->DirFiles("/etc/init.d","virtualswitch");
	foreach ($files as $num=>$ligne){
		if(preg_match("#virtualswitch-(.+)#", $ligne,$re)){
			echo "switch {$re[1]}\n";
		}
		
	}
}

?>