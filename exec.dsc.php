<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="DNS Statistics Daemon";
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--enable"){$GLOBALS["OUTPUT"]=true;enable();exit();}
if($argv[1]=="--disable"){$GLOBALS["OUTPUT"]=true;disable();exit();}
if($argv[1]=="--parse"){$GLOBALS["OUTPUT"]=true;parse();exit();}
if($argv[1]=="--monit"){$GLOBALS["OUTPUT"]=true;monit_install();exit();}
if($argv[1]=="--days"){$GLOBALS["OUTPUT"]=true;CompressDay();exit();}
if($argv[1]=="--stats"){$GLOBALS["OUTPUT"]=true;GlobalStats();exit();}
if($argv[1]=="--excludes"){RemoveExclude();exit;}




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
	build_progress(20, "{stopping_service}");
	stop(true);
	sleep(1);
	build_progress(50, "{reconfiguring}");
	build();
	build_progress(90, "{starting_service}");
	start(true);
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress(110, "{starting_service} {failed}");
		return;
	}
	build_progress(100, "{starting_service} {success}");
	
}

function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/pdns.dsc.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}

function RemoveExclude():bool{
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=@file_get_contents($pidfile);
    if($unix->process_exists($pid)){return false;}
    @file_put_contents($pidfile,getmypid());


    $DSCBlacklistDoms       = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));

    if(!is_array($DSCBlacklistDoms) or empty($DSCBlacklistDoms)){
        $DSCBlacklistDoms=Default_excludes();
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSCBlacklistDoms",serialize($DSCBlacklistDoms));
    }
    $q=new postgres_sql();
    foreach ($DSCBlacklistDoms as $domain=>$none){
        $q->QUERY_SQL("DELETE FROM dns_access_days WHERE sitename LIKE '%$domain'");
    }
    GlobalStats();
    return true;
}

function Default_excludes(){
    $DSCBlacklistDoms[".ntp.org"]=true;
    $DSCBlacklistDoms[".tld"]=true;
    $DSCBlacklistDoms[".lab"]=true;
    $DSCBlacklistDoms[".local"]=true;
    $DSCBlacklistDoms[".int"]=true;
    $DSCBlacklistDoms[".infra"]=true;
    $DSCBlacklistDoms["touzeau.biz"]=true;
    $DSCBlacklistDoms[".touzeau"]=true;
    $DSCBlacklistDoms[".filter.artica.center"]=true;
    return $DSCBlacklistDoms;
}

function enable(){
	build_progress(20, "{creating_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSStatsEnabled", 1);
	install_service();
	monit_install();
	build_progress(60, "{stopping_service}");
	stop(true);
	sleep(1);
	build();
	$unix=new unix();
	$unix->Popuplate_cron_make("DSC-schedule","*/10 * * * *",basename(__FILE__)." --parse");
	UNIX_RESTART_CRON();
	
	build_progress(90, "{starting_service}");
	start(true);
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
	build_progress(100, "{done}");
}
function disable(){
    $unix=new unix();
	build_progress(20, "{remove_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSStatsEnabled", 0);
	remove_service("/etc/init.d/dsc");
	if(is_file("/etc/monit/conf.d/APP_DSC.monitrc")){
		@unlink("/etc/monit/conf.d/APP_DSC.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}

    build_progress(50, "{cleaning_data}");
	$q=new postgres_sql();
	$q->DELETE_TABLE("dns_access_days");
    $q->QUERY_SQL("vacuum full");

	@unlink("/etc/cron.d/DSC-schedule");
	UNIX_RESTART_CRON();
	$rm=$unix->find_program("rm");
    if(is_dir("/home/artica/SQLITE_DSC")){shell_exec("$rm -rf /home/artica/SQLITE_DSC");}
	
	system("/etc/init.d/artica-status restart --force");
	build_progress(100, "{done}");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          dsc";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time \$pdns";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: DSC Webinterface";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable DNS statidtics daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";

	$f[]="   $php /usr/share/artica-postfix/exec.dsc.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.dsc.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php /usr/share/artica-postfix/exec.dsc.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.dsc.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/dsc";
	echo "PDNS: [INFO] Writing $INITD_PATH with new config\n";
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

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("dsc");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, dsc not installed\n";}
		return;
	}

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
	
	$pid=PID_NUM();
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory\n";}
		if($unix->process_exists($pid)){stop();}
		return;
	}

	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/dsc.pid", $pid);
		return;
	}

	$cmd="$Masterbin /usr/local/etc/dsc/dsc.conf";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	system($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function build(){
	$unix=new unix();
	$ipClass=new IP();
	
	$Interfaces=$unix->NETWORK_ALL_INTERFACES();
	$nic=new system_nic();
    foreach ($Interfaces as $Interface=>$ligne){
		if($Interface=="lo"){continue;}
		if($ligne["IPADDR"]=="0.0.0.0"){continue;}
		if(!$ipClass->isValid($ligne["IPADDR"])){continue;}
		$Interface=$nic->NicToOther($Interface);
		$f[]="interface $Interface;";
		$f[]="local_address {$ligne["IPADDR"]};";
	}
	
	$f[]="";

	$f[]="run_dir \"/home/artica/dsc\";";
	$f[]="minfree_bytes 5000000;";
	$f[]="pid_file \"/var/run/dsc.pid\";";
	$f[]="#bpf_program \"udp port 53\";";
	$f[]="#bpf_program \"udp dst port 53 and udp[10:2] & 0x8000 = 0\";";
	$f[]="bpf_program \"udp port 53 or tcp port 53\";";
	
	//$f[]="dataset qtype dns All:null Qtype:qtype queries-only;";
	$f[]="dataset qnamequery dns label:client query:qname queries-only;";
	/*$f[]="dataset rcode dns All:null Rcode:rcode replies-only;";
	$f[]="dataset opcode dns All:null Opcode:opcode queries-only;";
	$f[]="dataset rcode_vs_replylen dns Rcode:rcode ReplyLen:msglen replies-only;";
	$f[]="dataset client_subnet dns All:null ClientSubnet:client_subnet queries-only max-cells=200;";
	$f[]="dataset qtype_vs_qnamelen dns Qtype:qtype QnameLen:qnamelen queries-only;";
	$f[]="dataset qtype_vs_tld dns Qtype:qtype TLD:tld queries-only,popular-qtypes max-cells=200;";
	$f[]="dataset certain_qnames_vs_qtype dns CertainQnames:certain_qnames Qtype:qtype queries-only;";
	$f[]="dataset client_subnet2 dns Class:query_classification ClientSubnet:client_subnet queries-only max-cells=200;";
	$f[]="dataset client_addr_vs_rcode dns Rcode:rcode ClientAddr:client replies-only max-cells=50;";
	$f[]="dataset chaos_types_and_names dns Qtype:qtype Qname:qname chaos-class,queries-only;";
	$f[]="dataset idn_qname dns All:null IDNQname:idn_qname queries-only;";
	$f[]="dataset edns_version dns All:null EDNSVersion:edns_version queries-only;";
	$f[]="dataset edns_bufsiz dns All:null EDNSBufSiz:edns_bufsiz queries-only;";
	$f[]="dataset do_bit dns All:null D0:do_bit queries-only;";
	$f[]="dataset rd_bit dns All:null RD:rd_bit queries-only;";
	$f[]="dataset idn_vs_tld dns All:null TLD:tld queries-only,idn-only;";
	$f[]="dataset ipv6_rsn_abusers dns All:null ClientAddr:client queries-only,aaaa-or-a6-only,root-servers-net-only max-cells=50;";
	$f[]="dataset transport_vs_qtype dns Transport:transport Qtype:qtype queries-only;";
	$f[]="dataset client_port_range dns All:null PortRange:dns_sport_range queries-only;";
	$f[]="#dataset second_ld_vs_rcode dns Rcode:rcode SecondLD:second_ld replies-only max-cells=50;";
	$f[]="#dataset third_ld_vs_rcode dns Rcode:rcode ThirdLD:third_ld replies-only max-cells=50;";
	$f[]="dataset direction_vs_ipproto ip Direction:ip_direction IPProto:ip_proto any;";
	$f[]="#dataset dns_ip_version_vs_qtype dns IPVersion:dns_ip_version Qtype:qtype queries-only;";
	$f[]="";
	$f[]="#	datasets for collecting data on priming queries at root nameservers";
	$f[]="#dataset priming_queries dns Transport:transport EDNSBufSiz:edns_bufsiz priming-query,queries-only;";
	$f[]="#dataset priming_responses dns All:null ReplyLen:msglen priming-query,replies-only;";
	$f[]="";
	$f[]="#   dataset for monitoring an authoritative nameserver for DNS reflection attack";
	$f[]="#dataset qr_aa_bits dns Direction:ip_direction QRAABits:qr_aa_bits any;";
	$f[]="";
	$f[]="# bpf_vlan_tag_byte_order";
	$f[]="#";
	$f[]="#	Set this to 'host' on FreeBSD-4 where the VLAN id that we";
	$f[]="#	get from BPF appears to already be in host byte order.";
	$f[]="#bpf_vlan_tag_byte_order host;";
	*/
	$f[]="";
	$f[]="# match_vlan";
	$f[]="#";
	$f[]="#	A whitespace-separated list of VLAN IDs.  If set, only the";
	$f[]="#	packets with these VLAN IDs will be analyzed by DSC.";
	$f[]="#";
	$f[]="#match_vlan 100 200;";
	$f[]="statistics_interval 300;";
	$f[]="output_format JSON;";
	$f[]="dump_reports_on_exit;";
	$f[]="";
	
	@mkdir("/usr/local/etc/dsc",0755,true);
	@mkdir("/home/artica/dsc",0755,true);
	@file_put_contents("/usr/local/etc/dsc/dsc.conf", @implode("\n", $f));

}

function monit_install(){
	
	$f=array();
	$f[]="check process APP_DSC";
	$f[]="with pidfile /var/run/dsc.pid";
	$f[]="start program = \"/etc/init.d/dsc start --monit\"";
	$f[]="stop program =  \"/etc/init.d/dsc stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_DSC.monitrc", @implode("\n", $f));
	$f=array();
	//********************************************************************************************************************
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}


function PID_NUM(){
	$unix=new unix();
    $Masterbin=$unix->find_program("dsc");
	$pid=$unix->get_pid_from_file("/var/run/dsc.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($Masterbin);
	
}


function parse(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.dsc.php.parse.pid";
	$pidtime="/etc/artica-postfix/pids/exec.dsc.php.parse.time";
	$pid=$unix->get_pid_from_file($pidfile);
	
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
			return;
		}
		
		$Time=$unix->file_time_min($pidtime);
		if($Time<5){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} require 5mn, current {$Time}mn\n";
			exit();
		}
		
		@file_put_contents($pidfile, getmypid());
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
	}
	
	$path="/home/artica/dsc";
	if(!is_dir($path)){return;}
	if (!$handle = opendir($path)) {return;}
	
	
	include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	
	$q=new postgres_sql();
	$q->DNS_TABLES();

	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$full_path="$path/$fileZ";
		if(is_dir($full_path)){continue;}
		if(!preg_match("#dscdata\.json$#", $fileZ)){continue;}
		if(parse_file($full_path)){@unlink($full_path);continue;}
		$time=$unix->file_time_min($full_path);
		if($time>240){@unlink($full_path);}
	}
	
	CompressDay();
	GlobalStats();
}

function GlobalStats(){

    $q                      = new postgres_sql();
    $qlite                  = new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $tables_size_day        = 0;

    $tables[]="dns_access_days";
    $tables[]="dns_rqcounts";
    $tables[]="dns_access";

    $count_rows=0;
    foreach ($tables as $table) {
        $ligne = $qlite->mysqli_fetch_array("SELECT zrows,zbytes FROM ztables WHERE tablename='$table'");
        if($GLOBALS["VERBOSE"]){echo "$table: {$ligne["zrows"]}; {$ligne["zbytes"]}\n";}
        $count_rows = $count_rows + intval($ligne["zrows"]);
        $zbytes = $zbytes + intval($ligne["zbytes"]);
    }

    $tables_size=FormatBytes($zbytes/1024);

    $ligne=$q->mysqli_fetch_array("SELECT zdate FROM dns_access_days ORDER BY zdate LIMIT 1");
    $FIRST_DATE=$ligne["zdate"];
    $MAIN["TOTAL"]["ALL"]["FIRST_DATE"]=$FIRST_DATE;
    $MAIN["TOTAL"]["ALL"]["ROWS"]=$count_rows;
    $FIRST_DATE_BIN=strtotime($FIRST_DATE);
    $LAST_DATE_BIN =time();
    $RtMDays=0;
    $distanceInSeconds = round(abs(time() - $FIRST_DATE_BIN));
    $distanceInMinutes = round($distanceInSeconds / 60);
    if($distanceInMinutes>1440){
        $RtMDays=round(floatval($distanceInMinutes) / 1440);
    }

    if($RtMDays>0){
        $tables_size_day=round($zbytes/$RtMDays);
        $tables_size_day=FormatBytes($tables_size_day/1024);
    }else{
        $tables_size_day=$tables_size;
    }

    $DISTANCE_DATE=distanceOfTimeInWords($FIRST_DATE_BIN,$LAST_DATE_BIN);
    $MAIN["TOTAL"]["ALL"]["SIZE"]=$tables_size;
    $MAIN["TOTAL"]["ALL"]["SIZE_DAY"]=$tables_size_day;
    $MAIN["TOTAL"]["ALL"]["DISTANCE"]=$DISTANCE_DATE;
    $MAIN["TOTAL"]["ALL"]["DAYS"]=$RtMDays;

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSC_DASHBOARD",serialize($MAIN));


}
	
function parse_file($full_path){
	$array=json_decode(@file_get_contents($full_path));
	$family=new squid_familysite();
	$qu=new mysql_catz();
	if($GLOBALS["VERBOSE"]){echo "Scanning $full_path\n";}
	$stop_time=$array[0]->stop_time;
	$date=date("Y-m-d H:i:s",$stop_time);
	$DatabaseFile=date("Y-m-d",$stop_time).".db";
	$c=0;
	$ipCount=0;
	
	if(!is_dir("/home/artica/SQLITE_DSC")){@mkdir("/home/artica/SQLITE_DSC",0755,true);}
	$q=new lib_sqlite("/home/artica/SQLITE_DSC/$DatabaseFile");
	$sql="CREATE TABLE IF NOT EXISTS dns_access (zdate INTEGER,ipaddr TEXT,sitename TEXT,familysite TEXT,category INTEGER,rqs INTEGER )";
	$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", $q->mysql_error,__FILE__,__LINE);return false;}
	
	$sql="CREATE TABLE IF NOT EXISTS dns_rqcounts(zdate INTEGER,rqs INTEGER,clients INTEGER)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", $q->mysql_error,__FILE__,__LINE);return false;}
	
	$sql="CREATE TABLE IF NOT EXISTS dummy_table(`ID` INTEGER PRIMARY KEY AUTOINCREMENT,toto text)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", $q->mysql_error,__FILE__,__LINE);return false;}


    $DSCReverseDnsLookup = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCReverseDnsLookup"));
    $DSCBlacklistDoms       = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));

    if(!is_array($DSCBlacklistDoms) or empty($DSCBlacklistDoms)){
        $DSCBlacklistDoms=Default_excludes();
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSCBlacklistDoms",serialize($DSCBlacklistDoms));
    }


	
	
	foreach ($array[1]->data as $data){
		$ipaddr=$data->label;
		$ipCount++;
		foreach ($data->query as $queries){
			$domain = strtolower($queries->val);
            $BLKS   = false;

			foreach ($DSCBlacklistDoms as $blk=>$none){
                $blk=strtolower($blk);
                if(strpos(" $domain",$blk)>0){$BLKS=true;break;}
            }
            if ($BLKS){continue;}

			if($DSCReverseDnsLookup==0){
			    if(strpos(" $domain","in-addr.arpa")>0){continue;}
            }

			if(strlen($domain)>256){
			    $out=strlen($domain)-259;
			    $domain=substr($domain,$out,256)."...";
            }

			$familysite=$family->GetFamilySites($domain);
			$category=$qu->GET_CATEGORIES($domain);
			$rqs=$queries->count;
			$f[]="('$stop_time','$ipaddr','$domain','$familysite','$category','$rqs')";
			if($GLOBALS["VERBOSE"]){echo "('$date','$ipaddr','$domain','$familysite','$category','$rqs')\n";}
			$c++;
			if(count($f)>0){
				$q->QUERY_SQL("INSERT INTO dns_access (zdate,ipaddr,sitename,familysite,category,rqs) VALUES ".@implode(",", $f));
				if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", $q->mysql_error,__FILE__,__LINE);return false;}
				$f=array();
			}
			
			
		}
	}
	
	
	$sql="SELECT SUM(rqs) as rqs , zdate, AVG(clients) as clients FROM dns_rqcounts GROUP BY zdate ORDER BY zdate;";
	$results=$q->QUERY_SQL($sql);
	$datacache=base64_encode(serialize($results));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURRENT_DSC_USERS",$datacache);
	
	
	$sql="SELECT SUM(rqs) as rqs, familysite FROM dns_access GROUP BY familysite ORDER BY rqs DESC LIMIT 20";
	$results=$q->QUERY_SQL($sql);
	$datacache=base64_encode(serialize($results));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURRENT_DSC_TOPDOMAINS",$datacache);
	
	$sql="SELECT SUM(rqs) as rqs, ipaddr FROM dns_access GROUP BY ipaddr ORDER BY rqs DESC LIMIT 50;";
	$results=$q->QUERY_SQL($sql);
	$datacache=base64_encode(serialize($results));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURRENT_DSC_TOPIPS",$datacache);

	$sql="INSERT INTO dns_rqcounts (zdate,rqs,clients) VALUES ('$stop_time','$c','$ipCount')";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return false;}
	return true;
	
}

function CompressDay_inject($database,$time){
	
	$q=new lib_sqlite("/home/artica/SQLITE_DSC/$database");
	$qPostgres=new postgres_sql();
	
	$results=$q->QUERY_SQL("SELECT ipaddr,sitename,familysite,category,SUM(rqs) as rqs FROM dns_access GROUP BY ipaddr,sitename,familysite,category");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		return false;
	}
	
	$date=date("Y-m-d")." 00:00:00";
	$sql_prefix="INSERT INTO \"dns_access_days\" (zdate,ipaddr,sitename,familysite,category,rqs) VALUES ";
    $DSCReverseDnsLookup = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCReverseDnsLookup"));
	foreach ($results as $index=>$ligne){
		$ipaddr=$ligne["ipaddr"];
		$sitename=strtolower($ligne["sitename"]);

        if($DSCReverseDnsLookup==0){
            if(strpos(" $sitename","in-addr.arpa")>0){continue;}
        }


		$familysite=$ligne["familysite"];
		$category=$ligne["category"];
		$rqs=$ligne["rqs"];
		$f[]="('$date','$ipaddr','$sitename','$familysite','$category','$rqs')";
		
		if(count($f)>500){
			echo "$database Injecting 500 rows\n";
			$qPostgres->QUERY_SQL($sql_prefix.@implode(",", $f));
			$f=array();
			if(!$qPostgres->ok){
				echo $qPostgres->mysql_error."\n";
				return false;
			}
		}
		
	}
	
	if(count($f)>0){
		echo "$database Injecting ".count($f)." rows\n";
		$qPostgres->QUERY_SQL($sql_prefix.@implode(",", $f));
		if(!$qPostgres->ok){
			echo $qPostgres->mysql_error."\n";
			return false;
		}
	}
	

	
	
	return true;
	
	
}

function CompressDay(){
	$unix=new unix();
	$DirectorySource="/home/artica/SQLITE_DSC";
	$CurrentDatabase=date("Y-m-d").".db";
	
	$Files=$unix->DirFiles($DirectorySource,"\.db$");
	
	foreach ($Files as $filename=>$none){
		echo "Scanning $filename ( Cur: $CurrentDatabase)\n";
		if($filename==$CurrentDatabase){
			echo "Skipping $filename\n";
			continue;
		}
		
		$strdate=str_replace(".db", "", $filename)." 00:00:00";
		$xtime=strtotime($strdate);
		echo "$filename (".date("Y l F ",$xtime).")\n";
		if(!CompressDay_inject($filename,$xtime)){continue;}
		@unlink("$DirectorySource/$filename");
		
	}
	
}

?>