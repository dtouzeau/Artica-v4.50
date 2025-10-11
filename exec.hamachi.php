<?php
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');

$_GET["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/web/interface-postfix.log";


if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if($argv[1]=="route"){FixRoute();exit();}
if($argv[1]=="--gateway"){HasGateway();exit();}
if($argv[1]=="--hosts"){hamachi_etc_hosts();exit();}
if($argv[1]=="--schedule"){SetSchedule();exit();}
if($argv[1]=="--initd"){buildinit();exit();}


	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." hamachi already executed PID: $pid since {$time}Mn\n";
		writelogs("hamachi already executed PID: $pid","MAIN",__FUNCTION__,__FILE__,__LINE__);
		if(!$GLOBALS["FORCE"]){exit();}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	@file_put_contents($pidfile, getmypid());
	main();
	
	
	
function main(){	
	$sock=new sockets();
	$unix=new unix();
	$users=new usersMenus();
	if(!$users->HAMACHI_INSTALLED){echo "Starting......: ".date("H:i:s")." hamachi not installed\n";exit();}
	if(!isset($GLOBALS["hamachi_bin"])){$GLOBALS["hamachi_bin"]=$unix->find_program("hamachi");}
	if(!is_file($GLOBALS["hamachi_bin"])){echo "Starting......: ".date("H:i:s")." hamachi no such binary\n";exit();}
	
	$EnableHamachi=$sock->GET_INFO("EnableHamachi");
	if(!is_numeric($EnableHamachi)){$EnableHamachi=1;}	
	if($EnableHamachi==0){echo "Starting......: ".date("H:i:s")." hamachi disabled\n";HasGateway_iptables_delete_rules();hamachi_etc_hosts_remove();@unlink("/etc/cron.d/HamachiHosts");exit();}
	AdditionalSettings();
	GetNets();
	shell_exec("/etc/init.d/artica-postfix start hamachi");
	$sql="SELECT * FROM hamachi ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	AdditionalSettings();
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$array=unserialize(base64_decode($ligne["pattern"]));
		connect($array);
	}
	
	DO_SET_NICK();
	FixRoute();
	HasGateway();
	buildinit();
	SetSchedule();

}
function SetSchedule(){
	$sock=new sockets();
	$unix=new unix();	
	$HamachiExtDomain=trim($sock->GET_INFO("HamachiExtDomain"));
	$targetfile="/etc/cron.d/HamachiHosts";
	@unlink("/etc/cron.d/HamachiHosts");
	if($HamachiExtDomain<>null){
		$php5=$unix->LOCATE_PHP5_BIN();
 		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$f[]="MAILTO=\"\"";
		$f[]="0,5,10,15,20,25,30,35,40,45,50,55 * * * *  root $php5 ".__FILE__." --hosts >/dev/null 2>&1";
		$f[]="";	
		if($GLOBALS["VERBOSE"]){echo " -> $targetfile\n";}
		@file_put_contents($targetfile,implode("\n",$f));
		if(!is_file($targetfile)){if($GLOBALS["VERBOSE"]){echo " -> $targetfile No such file\n";}}
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 640 $targetfile");
		echo "Starting......: ".date("H:i:s")." hamachi $targetfile done\n";
		unset($f);			
	}else{
		hamachi_etc_hosts_remove();
	}
}	
	


	
function GetNets(){
	$unix=new unix();
	exec($unix->find_program("hamachi")." list",$l);
	foreach ($l as $num=>$ligne){
		if(preg_match("#\[(.+?)\]#",$ligne,$re)){
			echo "Starting......: ".date("H:i:s")." hamachi {$re[1]} OK...\n";
			$GLOBALS["NETS"][$re[1]]=true;
		}
		
	}
	
}

function DO_SET_NICK(){
	$users=new usersMenus();
	if(!isset($GLOBALS["COUNT".__FUNCTION__])){$GLOBALS["COUNT".__FUNCTION__]=0;}
	$cmd=$GLOBALS["hamachi_bin"]." set-nick $users->hostname 2>&1";	
	exec($cmd,$l3);
	while (list ($num1, $ligne2) = each ($l3) ){
		echo "Starting......: ".date("H:i:s")." hamachi set-nick: $users->hostname $ligne2 - {$GLOBALS["COUNT".__FUNCTION__]}\n";
		if(preg_match("#failed, busy#", $ligne2)){
			echo "Starting......: ".date("H:i:s")." hamachi set-nick: $users->hostname waiting 2 seconds\n";
			sleep(2);
			if($GLOBALS["COUNT".__FUNCTION__]<5){
				$GLOBALS["COUNT".__FUNCTION__]=$GLOBALS["COUNT".__FUNCTION__]+1;
				DO_SET_NICK();
			}
		}
	}
}

	
function connect($array){
	if(isset($GLOBALS["NETS"][$array["NETWORK"]])){
		echo "Starting......: ".date("H:i:s")." hamachi {$array["NETWORK"]} already connected...\n";
		return true;
	}
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi perform {$array["TYPE"]} operation...\n";}
	
	switch ($array["TYPE"]) {
		case "JOIN_NET":JOIN_NET($array);break;
		case "CREATE_NET":CREATE_NET($array);break;
		default:
			;
		break;
	}
	
	
	
}

function DO_JOIN($network,$password){
	if(!isset($GLOBALS["COUNT".__FUNCTION__])){$GLOBALS["COUNT".__FUNCTION__]=0;}
	$cmd=$GLOBALS["hamachi_bin"]." do-join $network $password 2>&1";	
	exec($cmd,$l3);
	while (list ($num1, $ligne2) = each ($l3) ){
		echo "Starting......: ".date("H:i:s")." hamachi [do-join]: $ligne2 - {$GLOBALS["COUNT".__FUNCTION__]}\n";
		if(preg_match("#failed, busy#", $ligne2)){
			echo "Starting......: ".date("H:i:s")." hamachi [do-join]: waiting 2 seconds\n";
			sleep(2);
			if($GLOBALS["COUNT".__FUNCTION__]<5){
				$GLOBALS["COUNT".__FUNCTION__]=$GLOBALS["COUNT".__FUNCTION__]+1;
				DO_JOIN($network,$password);
			}
		}
	}
}
function DO_GO_ONLINE($network){
	if(!isset($GLOBALS["COUNT".__FUNCTION__])){$GLOBALS["COUNT".__FUNCTION__]=0;}
	$cmd=$GLOBALS["hamachi_bin"]." go-online $network 2>&1";	
	exec($cmd,$l3);
	while (list ($num1, $ligne2) = each ($l3) ){
		echo "Starting......: ".date("H:i:s")." hamachi [go-online]: $ligne2 - {$GLOBALS["COUNT".__FUNCTION__]}\n";
		if(preg_match("#failed, busy#", $ligne2)){
			echo "Starting......: ".date("H:i:s")." hamachi [go-online]: waiting 2 seconds\n";
			sleep(2);
			if($GLOBALS["COUNT".__FUNCTION__]<5){
				$GLOBALS["COUNT".__FUNCTION__]=$GLOBALS["COUNT".__FUNCTION__]+1;
				DO_GO_ONLINE($network);
			}
		}
	}
}

function JOIN_NET($array){
	if(!isset($GLOBALS["COUNT".__FUNCTION__])){$GLOBALS["COUNT".__FUNCTION__]=0;}
	$unix=new unix();
	if(!isset($GLOBALS["hamachi_bin"])){$GLOBALS["hamachi_bin"]=$unix->find_program("hamachi");}
	echo "Starting......: ".date("H:i:s")." hamachi [logout]: ...- {$GLOBALS["COUNT".__FUNCTION__]}\n";
	exec($GLOBALS["hamachi_bin"]." logout 2>&1",$l);
	foreach ($l as $num=>$ligne){
		echo "Starting......: ".date("H:i:s")." hamachi [logout]: $ligne\n";
	}
	$l=array();
	echo "Starting......: ".date("H:i:s")." hamachi [login]: ...- {$GLOBALS["COUNT".__FUNCTION__]}\n";
	
	
	exec($GLOBALS["hamachi_bin"]." login 2>&1",$l);
	foreach ($l as $num=>$ligne){
		echo "Starting......: ".date("H:i:s")." hamachi [login]: $ligne\n";
		if(preg_match("#failed, busy#", $ligne)){
			echo "Starting......: ".date("H:i:s")." hamachi [login]: waiting 2 seconds\n";
			sleep(2);
			if($GLOBALS["COUNT".__FUNCTION__]<5){
				$GLOBALS["COUNT".__FUNCTION__]=$GLOBALS["COUNT".__FUNCTION__]+1;
				JOIN_NET($array);
			}
		}

		if(preg_match("#failed, already online#", $ligne)){
			echo "Starting......: ".date("H:i:s")." hamachi [login]: OK already online\n";
		}
		
	}
	
	
	echo "Starting......: ".date("H:i:s")." hamachi [join]: {$array["NETWORK"]}...\n";
	$cmd=$GLOBALS["hamachi_bin"]." join {$array["NETWORK"]} {$array["PASSWORD"]} 2>&1";
	exec($cmd,$l1);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	while (list ($num, $ligne) = each ($l1) ){
		echo "Starting......: ".date("H:i:s")." hamachi [join]: $ligne\n";
		if(preg_match("#failed, manual approval required#", $ligne)){
			echo "Starting......: ".date("H:i:s")." hamachi [join]: approval requested\n";	
			DO_JOIN($array["NETWORK"],$array["PASSWORD"]);	
		}
		
	}
	
	
		
	DO_GO_ONLINE($array["NETWORK"]);
	FixRoute();
	
}

function CREATE_NET($array){
	exec($unix->find_program("hamachi")." create {$array["NETWORK"]} {$array["PASSWORD"]}",$l);
}


function hamachi_currentIP(){
	if(is_file("/etc/hamachi/state")){
		$datas=explode("\n",@file_get_contents("/etc/hamachi/state"));
		foreach ($datas as $num=>$ligne){
			if(preg_match("#Identity\s+([0-9\.]+)#",$ligne,$re)){
				return $re[1];
				break;
			}
		}
	}
	if(is_file("/var/lib/logmein-hamachi/h2-engine.cfg")){
		$datas=explode("\n",@file_get_contents("/var/lib/logmein-hamachi/h2-engine.cfg"));
		foreach ($datas as $num=>$ligne){
			if(preg_match("#VIP4Addr\s+([0-9\.]+)#",$ligne,$re)){
				return trim($re[1]);
				break;
			}
		}		
	}	
	
}

function AdditionalSettings(){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$CurrentPageName=CurrentPageName();
		$datas=$sock->GET_INFO("ArticaProxySettings");
		$HamachiFwInterface=$sock->GET_INFO("HamachiFwInterface");
		if(!is_dir("/var/lib/logmein-hamachi")){@mkdir("/var/lib/logmein-hamachi",0755,true);}
		$f=array();
		$f[]="Login.OnLaunch\t1";
		$f[]="Core.AutoLogin\t1";
		

		if(trim($datas)<>null){
			$ini->loadString($datas);
			$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
			$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			$ArticaCompiledProxyUri=$ini->_params["PROXY"]["ArticaCompiledProxyUri"];
		}	
	
	if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}
	if($ArticaProxyServerEnabled=="yes"){
		echo "Starting......: ".date("H:i:s")." hamachi [Conf]: Proxy tunnel is enabled\n";
		$f[]="Conn.PxyAddr\t$ArticaProxyServerName";
		$f[]="Conn.PxyPort\t$ArticaProxyServerPort";
		$f[]="Conn.PxySave\t1";
		if($ArticaProxyServerUsername<>null){
			if($ArticaProxyServerUserPassword<>null){$f[]="Conn.PxyPass\t$ArticaProxyServerName";}
			$f[]="Conn.PxyUser\t$ArticaProxyServerName";
		}
		
	}
	if($HamachiFwInterface<>null){
		$f[]="Vpn.BridgeTo\t$HamachiFwInterface";
		
	}
	@file_put_contents("/var/lib/logmein-hamachi/h2-engine-override.cfg", @implode("\n", $f));
	echo "Starting......: ".date("H:i:s")." hamachi [Conf]: h2-engine-override.cfg done ". count($f)." parameters\n";
}


function FixRoute(){
	$ip=hamachi_currentIP();
	if($ip==null){return;}
	
	$unix=new unix();
	exec($unix->find_program("route"),$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#([0-9\.]+)\s+([0-9\.\*]+).+?\s+([0-9\.]+)\s+[A-Z]+.+ham0#",$ligne,$re)){
			if(trim($re[2])<>$ip){
				echo "Starting......: ".date("H:i:s")." hamachi [Net]: ham0: $ip, building routes\n";
				shell_exec("route del -net 5.0.0.0 gw 0.0.0.0 netmask 255.0.0.0 dev ham0");
				shell_exec("route add -net 5.0.0.0 gw $ip netmask 255.0.0.0 dev ham0");
			}
			
		}
	}
}

function HasGateway_iptables_delete_rules(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");	
	shell_exec("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaHamachi#";	
	$count=0;
	foreach ($datas as $num=>$ligne){
			if($ligne==null){continue;}
			if(preg_match($pattern,$ligne)){$count++;continue;}
			$conf=$conf . $ligne."\n";
			}
	if($count>0){
		file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
		shell_exec("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
		echo "Starting......: ".date("H:i:s")." hamachi [Net]: Cleaning iptables $count rules\n";
	}

}

function HasGateway(){
	$sock=new sockets();
	$EnableArticaAsGateway=$sock->GET_INFO("EnableArticaAsGateway");
	$HamachiFwInterface=$sock->GET_INFO("HamachiFwInterface");
	$HamachiExtDomain=$sock->GET_INFO("HamachiExtDomain");
	$unix=new unix();
	if(!is_numeric($EnableArticaAsGateway)){$EnableArticaAsGateway=0;}
	
	
	if($EnableArticaAsGateway==1){
		$sysctl=$unix->find_program("sysctl");
		echo "Starting......: ".date("H:i:s")." hamachi [Net]: Enable gateway mode\n";	
		shell_exec("$sysctl -w net.ipv4.ip_forward=1");
	}
	HasGateway_iptables_delete_rules();
	if($HamachiFwInterface<>null){
		echo "Starting......: ".date("H:i:s")." hamachi [Net]: Transfert $HamachiFwInterface requests to ham0\n";
		HasGateway_iptables($HamachiFwInterface);
	}
	
}

function HasGateway_iptables($IPTABLES_ETH){
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	if(!is_file($iptables)){echo "Starting......: ".date("H:i:s")." hamachi `iptables`, no such binary\n";return false;}
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: hook the $IPTABLES_ETH nic\n";}
	shell_exec2("$iptables -A INPUT -i ham0 -j ACCEPT -m comment --comment \"ArticaHamachi\"");
	shell_exec2("$iptables -A FORWARD -i ham0 -j ACCEPT -m comment --comment \"ArticaHamachi\"");
	shell_exec2("$iptables -A OUTPUT -o ham0 -j ACCEPT -m comment --comment \"ArticaHamachi\"");
	shell_exec2("$iptables -t nat -A POSTROUTING -o $IPTABLES_ETH -j MASQUERADE -m comment --comment \"ArticaHamachi\"");

	shell_exec2("$iptables -A INPUT -i $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaHamachi\"");
	shell_exec2("$iptables -A FORWARD -i $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaHamachi\"");
	shell_exec2("$iptables -A OUTPUT -o $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaHamachi\"");
	shell_exec2("$iptables -t nat -A POSTROUTING -o ham0 -j MASQUERADE -m comment --comment \"ArticaHamachi\"");
	echo "Starting......: ".date("H:i:s")." hamachi prerouting success from ham0 -> $IPTABLES_ETH...\n";
	
}
function shell_exec2($cmd){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: executing \"$cmd\"\n";}shell_exec($cmd);}
function buildinit(){
		if(!is_file("/etc/init.d/logmein-hamachi")){echo "Starting......: ".date("H:i:s")." hamachi: [init]: logmein-hamachi no such file\n";return; }
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();	
		$nohup=$unix->find_program("nohup");
		$f[]="#! /bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          logmein-hamachi";
		$f[]="# Required-Start:    \$local_fs \$network";
		$f[]="# Required-Stop:     \$local_fs \$network";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: Start/stop logmein-hamachi engine";
		$f[]="### END INIT INFO";
		$f[]="#";
		$f[]="# Author: LogMeIn, Inc. <hamachilinux-feedback@logmein.com>";
		$f[]="#";
		$f[]="";
		$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
		$f[]="DESC=\"LogMeIn Hamachi VPN tunneling engine\"";
		$f[]="NAME=logmein-hamachi";
		$f[]="DAEMON=/opt/logmein-hamachi/bin/hamachid";
		$f[]="PIDFILE=/var/run/logmein-hamachi/hamachid.pid";
		$f[]="SCRIPTNAME=/etc/init.d/\$NAME";
		$f[]="";
		$f[]="# Exit if the package is not installed";
		$f[]="[ -x \"\$DAEMON\" ] || exit 5";
		$f[]="";
		$f[]="# Read configuration variable file if it is present";
		$f[]="[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME";
		$f[]="";
		$f[]="# Define LSB log_* functions. Depend on lsb-base (>= 3.0-6)";
		$f[]=". /lib/lsb/init-functions";
		$f[]="";
		$f[]="# start the daemon/service";
		$f[]="";
		$f[]="do_start()";
		$f[]="{";
		$f[]="    # Return";
		$f[]="    #   0 if daemon has been started";
		$f[]="    #   1 if daemon was already running";
		$f[]="    #   2 if daemon could not be started";
		$f[]="";
		$f[]="    start_daemon -p \$PIDFILE \$DAEMON";
		$f[]="";
		$f[]="    return \"\$?\"";
		$f[]="}";
		$f[]="";
		$f[]="# stop the daemon/service";
		$f[]="";
		$f[]="do_stop()";
		$f[]="{";
		$f[]="    # Return";
		$f[]="    #   0 if daemon has been stopped";
		$f[]="    #   1 if daemon was already stopped";
		$f[]="    #   2 if daemon could not be stopped";
		$f[]="    #   other if a failure occurred";
		$f[]="";
		$f[]="    killproc -p \$PIDFILE \$DAEMON";
		$f[]="";
		$f[]="    RETVAL=\"\$?\"";
		$f[]="    [ \"\$RETVAL\" = 2 ] && return 2";
		$f[]="";
		$f[]="    # remove pidfile if daemon could not delete on exit.";
		$f[]="    rm -f \$PIDFILE";
		$f[]="";
		$f[]="    return \"\$RETVAL\"";
		$f[]="}";
		$f[]="";
		$f[]="case \"\$1\" in";
		$f[]="  start)";
		$f[]="    echo -n \"Starting \$DESC \$NAME\"";
		$f[]="";
		$f[]="    do_start";
		$f[]="";
		$f[]="    case \"\$?\" in";
		$f[]="        0|1) ";
		$f[]="		 	log_success_msg";
		$f[]="		 	$nohup $php ". __FILE__." >/dev/null 2>&1 &";
		$f[]="			;;";
		$f[]="        *)   log_failure_msg ;;";
		$f[]="    esac";
		$f[]="    ;;";
		$f[]="  stop)";
		$f[]="    echo -n \"Stopping \$DESC \$NAME\"";
		$f[]="";
		$f[]="    do_stop";
		$f[]="";
		$f[]="    case \"\$?\" in";
		$f[]="        0|1) log_success_msg ;;";
		$f[]="        2)   log_failure_msg ;;";
		$f[]="    esac";
		$f[]="    ;;";
		$f[]="  restart|force-reload)";
		$f[]="    echo -n \"Restarting \$DESC \$NAME\"";
		$f[]="";
		$f[]="";
		$f[]="    do_stop";
		$f[]="    case \"\$?\" in";
		$f[]="      0|1)";
		$f[]="        sleep 1";
		$f[]="        do_start";
		$f[]="";
		$f[]="        case \"\$?\" in";
		$f[]="            0)";
		$f[]="		 	log_success_msg";
		$f[]="		 	$nohup $php ". __FILE__." --force >/dev/null 2>&1 &";
		$f[]="			/bin/rm /var/lib/logmein-hamachi/h2-engine.log >/dev/null 2>&1";
		$f[]="			;;";
		$f[]="            1) log_failure_msg ;; # Old process is still running";
		$f[]="            *) log_failure_msg ;; # Failed to start";
		$f[]="        esac";
		$f[]="        ;;";
		$f[]="      *)";
		$f[]="          # Failed to stop";
		$f[]="        log_failure_msg";
		$f[]="        ;;";
		$f[]="    esac";
		$f[]="    ;;";
		$f[]="  *)";
		$f[]="";
		$f[]="    log_warning_msg \"Usage: \$SCRIPTNAME {start|stop|restart|force-reload}\" >&2";
		$f[]="    exit 3";
		$f[]="    ;;";
		$f[]="esac";
		$f[]="";
		$f[]=":";	
		@file_put_contents("/etc/init.d/logmein-hamachi", @implode("\n", $f));	
		echo "Starting......: ".date("H:i:s")." hamachi: [init]: logmein-hamachi done\n";
}

function hamachi_etc_hosts(){
		$sock=new sockets();
		$DisableEtcHosts=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableEtcHosts"));
		if($DisableEtcHosts==1){echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: DisableEtcHosts is enabled, aborting\n";return;}
		$fixedDomain="hamachi.local";
		$HamachiExtDomain=trim($sock->GET_INFO("HamachiExtDomain"));
		if($HamachiExtDomain==null){hamachi_etc_hosts_remove();return;}
		
		$cache=unserialize(@file_get_contents("/etc/artica-postfix/hamachi.cache"));
		
		$unix=new unix();
		$hamachi=$unix->find_program("hamachi");
		$php5=$unix->LOCATE_PHP5_BIN();
		$edit=false;
		if(!isset($cache["HOSTSMD"])){$edit=true;}
		if(isset($cache["HOSTSMD"])){
			if($cache["HOSTSMD"]<>md5_file("/etc/hosts")){$cache=array();}
		}
		
		exec("$hamachi list 2>&1",$f);
		foreach ($f as $num=>$ligne){
			if(preg_match("#[0-9\-]+\s+(.+?)\s+([0-9\.]+)\s+([0-9a-z\:]+)#", $ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: {$re[1]} `{$re[2]}` `{$re[3]}`\n";}
				$re[1]=trim($re[1]);
				$re[2]=trim($re[2]);
				$re[3]=trim($re[3]);
				$key=md5("{$re[1]}{$re[2]}{$re[3]}");
				if(!isset($cache[$key])){
					$cache[$key]=true;
					$edit=true;
					$hostSplit=explode(".", $re[1]);
					$hostname=strtolower($hostSplit[0].".$HamachiExtDomain");
					$hostname2=strtolower($hostSplit[0].".hamachi.local");
					$hostFill[$key]=$re[2]."\t$hostname\t$hostname2";
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: {$hostFill[$key]}\n";}
				}
				
			}else{
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: $ligne NO MATCH\n";}
			}
			
		}
		
	
	if($edit){
		hamachi_etc_hosts_remove();
		$f=explode("\n", @file_get_contents("/etc/hosts"));
		if(count($hostFill)>0){
		    foreach ($hostFill as $ligne){$f[]=$ligne;}
		}
		
		
		
		$cache["HOSTSMD"]=md5_file("/etc/hosts");
		@file_put_contents("/etc/artica-postfix/hamachi.cache", serialize($cache));
		
		
		echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: /etc/hosts ". count($f)." items done...\n";
		$DNSMASQInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSMASQInstalled"));
		if($DNSMASQInstalled==1){
			echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: reloading DNSMASQ...\n";
			$cmd="/etc/init.d/dnsmasq reload";
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: $cmd\n";}
			system($cmd);
		}
		
		if($users->POWER_DNS_INSTALLED){
			echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: reloading PDNS...\n";
			$cmd="$php5 /usr/share/artica-postfix/exec.pdns.php --reload";
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: $cmd\n";}
			system($cmd);
		}

	}else{
		if($GLOBALS["VERBOSE"]){
			echo "Starting......: ".date("H:i:s")." hamachi: [hosts]: no changes\n";
		}
	}	
		
		
		
		
}

function hamachi_etc_hosts_remove(){
	
}


?>