<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="Glances";
if($argv[1]=="--prepare"){prepare();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--schedule"){schedule();exit;}


function restart(){
	$unix=new unix();
	restart_progress("{stopping}",20);
	prepare();
	restart_progress("{stopping}",50);
	stop(true);
	restart_progress("{stopping}",60);
	sleep(1);
	restart_progress("{starting}",80);
    remove_systemd();
	start(true);
	restart_progress("{starting}",90);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		restart_progress("{starting} {success}",100);
		return;
	}
	restart_progress("{starting} {failed}",110);
}
function remove_systemd(){
    $f[]="/usr/lib/systemd/system/glances.service";
    $f[]="/var/lib/systemd/deb-systemd-helper-enabled/glances.service.dsh-also";
    $f[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/glances.service";
    foreach ($f as $file){
        if(is_file($file)){
            @unlink($file);
        }
    }
}

function schedule(){
	
	$unix=new unix();
	$GlancesRestartServiceTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GlancesRestartServiceTime"));
	if($GlancesRestartServiceTime==0){$GlancesRestartServiceTime=4;}
	$MAIN_SCHEDULE[1]="0 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *";
	$MAIN_SCHEDULE[2]="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
	$MAIN_SCHEDULE[3]="0 0,3,6,9,12,15,18,21 * * *";
	$MAIN_SCHEDULE[4]="0 0,4,8,16,20 * * *";
	$MAIN_SCHEDULE[5]="0 0,5,10,15,20 * * *";
	$MAIN_SCHEDULE[6]="0 0,6,12,18 * * *";
	$MAIN_SCHEDULE[12]="0 0,12 * * *";
	$MAIN_SCHEDULE[24]="0 1 * * * ";
	
	$shced=$MAIN_SCHEDULE[$GlancesRestartServiceTime];
	$unix->Popuplate_cron_make("glances-restart",$shced,"exec.glances.php --restart");
	system("/etc/init.d/cron reload");
}

function prepare(){
		
	$EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
	
	@mkdir("/var/run/glances",0755,true);
	$f[]="# Default is to launch glances with '-s' option.";
	$f[]="#-s == Server mode";
	$f[]="#DAEMON_ARGS=\"-w --bind 127.0.0.1 --theme-white\"";
	$f[]="";
	$f[]="# Change to 'true' to have glances running at startup";
	if($EnableGlances==1){
		$f[]="RUN=\"true\"";
	}else{
		$f[]="RUN=\"false\"";
	}
	$f[]="";
	@file_put_contents("/etc/default/glances", @implode("\n", $f));
	$f=array();
	##############################################################################";
	$f[]="# Globals Glances parameters";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="[global]";
	$f[]="# Does Glances should check if a newer version is available on Pypi ?";
	$f[]="check_update=true";
	$f[]="# History size (maximum number of values)";
	$f[]="# Default is 28800: 1 day with 1 point every 3 seconds (default refresh time)";
	$f[]="history_size=28800";
	$f[]="";
	$f[]="##############################################################################";
	$f[]="# User interface";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="[outputs]";
	$f[]="# Theme name for the Curses interface: black or white";
	$f[]="curse_theme=white";
	$f[]="";
	$f[]="##############################################################################";
	$f[]="# plugins";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="[quicklook]";
	$f[]="# Define CPU, MEM and SWAP thresholds in %";
	$f[]="cpu_careful=50";
	$f[]="cpu_warning=70";
	$f[]="cpu_critical=90";
	$f[]="mem_careful=50";
	$f[]="mem_warning=70";
	$f[]="mem_critical=90";
	$f[]="swap_careful=50";
	$f[]="swap_warning=70";
	$f[]="swap_critical=90";
	$f[]="";
	$f[]="[cpu]";
	$f[]="# Default values if not defined: 50/70/90 (except for iowait)";
	$f[]="user_careful=50";
	$f[]="user_warning=70";
	$f[]="user_critical=90";
	$f[]="#user_log=False";
	$f[]="#user_critical_action=echo {{user}} {{value}} {{max}} > /tmp/cpu.alert";
	$f[]="system_careful=50";
	$f[]="system_warning=70";
	$f[]="system_critical=90";
	$f[]="steal_careful=50";
	$f[]="steal_warning=70";
	$f[]="steal_critical=90";
	$f[]="#steal_log=True";
	$f[]="# I/O wait percentage should be lower than 1/# (of CPU cores)";
	$f[]="# Let commented for default config (1/#-20% / 1/#-10% / 1/#)";
	$f[]="#iowait_careful=30";
	$f[]="#iowait_warning=40";
	$f[]="#iowait_critical=50";
	$f[]="# Context switch limit (core / second)";
	$f[]="# Let commented for default config (critical is 56000/# (of CPU core))";
	$f[]="#ctx_switches_careful=10000";
	$f[]="#ctx_switches_warning=12000";
	$f[]="#ctx_switches_critical=14000";
	$f[]="";
	$f[]="[percpu]";
	$f[]="# Define CPU thresholds in %";
	$f[]="# Default values if not defined: 50/70/90";
	$f[]="user_careful=50";
	$f[]="user_warning=70";
	$f[]="user_critical=90";
	$f[]="iowait_careful=50";
	$f[]="iowait_warning=70";
	$f[]="iowait_critical=90";
	$f[]="system_careful=50";
	$f[]="system_warning=70";
	$f[]="system_critical=90";
	$f[]="";
	$f[]="[load]";
	$f[]="# Define LOAD thresholds";
	$f[]="# Value * number of cores";
	$f[]="# Default values if not defined: 0.7/1.0/5.0 per number of cores";
	$f[]="# Source: http://blog.scoutapp.com/articles/2009/07/31/understanding-load-averages";
	$f[]="#         http://www.linuxjournal.com/article/9001";
	$f[]="careful=0.7";
	$f[]="warning=1.0";
	$f[]="critical=5.0";
	$f[]="#log=False";
	$f[]="";
	$f[]="[mem]";
	$f[]="# Define RAM thresholds in %";
	$f[]="# Default values if not defined: 50/70/90";
	$f[]="careful=50";
	$f[]="warning=70";
	$f[]="critical=90";
	$f[]="";
	$f[]="[memswap]";
	$f[]="# Define SWAP thresholds in %";
	$f[]="# Default values if not defined: 50/70/90";
	$f[]="careful=50";
	$f[]="warning=70";
	$f[]="critical=90";
	$f[]="";
	$f[]="#[network]";
	$f[]="# Define the list of hidden network interfaces (comma-separated regexp)";
	$f[]="#hide=docker.*,lo";
	$f[]="# WLAN 0 alias";
	$f[]="#wlan0_alias=Wireless IF";
	$f[]="# WLAN 0 Default limits (in bits per second aka bps) for interface bitrate";
	$f[]="#wlan0_rx_careful=4000000";
	$f[]="#wlan0_rx_warning=5000000";
	$f[]="#wlan0_rx_critical=6000000";
	$f[]="#wlan0_rx_log=True";
	$f[]="#wlan0_tx_careful=700000";
	$f[]="#wlan0_tx_warning=900000";
	$f[]="#wlan0_tx_critical=1000000";
	$f[]="#wlan0_tx_log=True";
	$f[]="";
	$f[]="#[diskio]";
	$f[]="# Define the list of hidden disks (comma-separated regexp)";
	$f[]="#hide=sda2,sda5,loop.*";
	$f[]="# Alias for sda1";
	$f[]="#sda1_alias=IntDisk";
	$f[]="";
	$f[]="[fs]";
	$f[]="# Define the list of hidden file system (comma-separated regexp)";
	$f[]="#hide=/boot.*";
	$f[]="# Define filesystem space thresholds in %";
	$f[]="# Default values if not defined: 50/70/90";
	$f[]="# It is also possible to define per mount point value";
	$f[]="# Example: /_careful=40";
	$f[]="careful=50";
	$f[]="warning=70";
	$f[]="critical=90";
	$f[]="# Allow additional file system types (comma-separated FS type)";
	$f[]="#allow=zfs";
	$f[]="";
	$f[]="[folders]";
	$f[]="# Define a folder list to monitor";
	$f[]="# The list is composed of items (list_#nb <= 10)";
	$f[]="# An item is defined by:";
	$f[]="# * path: absolute path";
	$f[]="# * careful: optional careful threshold (in MB)";
	$f[]="# * warning: optional warning threshold (in MB)";
	$f[]="# * critical: optional critical threshold (in MB)";
	$f[]="#folder_1_path=/tmp";
	$f[]="#folder_1_careful=2500";
	$f[]="#folder_1_warning=3000";
	$f[]="#folder_1_critical=3500";
	$f[]="#folder_2_path=/home/nicolargo/Videos";
	$f[]="#folder_2_warning=17000";
	$f[]="#folder_2_critical=20000";
	$f[]="#folder_3_path=/nonexisting";
	$f[]="#folder_4_path=/root";
	$f[]="";
	$f[]="[sensors]";
	$f[]="# Sensors core thresholds (in Celsius...)";
	$f[]="# Default values if not defined: 60/70/80";
	$f[]="temperature_core_careful=60";
	$f[]="temperature_core_warning=70";
	$f[]="temperature_core_critical=80";
	$f[]="# Temperatures threshold in °C for hddtemp";
	$f[]="# Default values if not defined: 45/52/60";
	$f[]="temperature_hdd_careful=45";
	$f[]="temperature_hdd_warning=52";
	$f[]="temperature_hdd_critical=60";
	$f[]="# Battery threshold in %";
	$f[]="battery_careful=80";
	$f[]="battery_warning=90";
	$f[]="battery_critical=95";
	$f[]="# Sensors alias";
	$f[]="#temp1_alias=Motherboard 0";
	$f[]="#temp2_alias=Motherboard 1";
	$f[]="#core 0_alias=CPU Core 0";
	$f[]="#core 1_alias=CPU Core 1";
	$f[]="";
	$f[]="[processlist]";
	$f[]="# Define CPU/MEM (per process) thresholds in %";
	$f[]="# Default values if not defined: 50/70/90";
	$f[]="cpu_careful=50";
	$f[]="cpu_warning=70";
	$f[]="cpu_critical=90";
	$f[]="mem_careful=50";
	$f[]="mem_warning=70";
	$f[]="mem_critical=90";
	$f[]="";
	$f[]="[ports]";
	$f[]="# Ports scanner plugin configuration";
	$f[]="# Interval in second between two scans";
	$f[]="refresh=30";
	$f[]="# Set the default timeout (in second) for a scan (can be overwrite in the scan list)";
	$f[]="timeout=3";
	$f[]="# If port_default_gateway is True, add the default gateway on top of the scan list";
	$f[]="port_default_gateway=True";
	$f[]="# Define the scan list (1 < x < 255)";
	$f[]="# port_x_host (name or IP) is mandatory";
	$f[]="# port_x_port (TCP port number) is optional (if not set, use ICMP)";
	$f[]="# port_x_description is optional (if not set, define to host:port)";
	$f[]="# port_x_timeout is optional and overwrite the default timeout value";
	$f[]="# port_x_rtt_warning is optional and defines the warning threshold in ms";
	$f[]="#port_1_host=192.168.0.1";
	$f[]="#port_1_port=80";
	$f[]="#port_1_description=Home Box";
	$f[]="#port_1_timeout=1";
	$f[]="#port_2_host=www.free.fr";
	$f[]="#port_2_description=My ISP";
	$f[]="#port_3_host=www.google.com";
	$f[]="#port_3_description=Internet ICMP";
	$f[]="#port_3_rtt_warning=1000";
	$f[]="#port_4_host=www.google.com";
	$f[]="#port_4_description=Internet Web";
	$f[]="#port_4_port=80";
	$f[]="#port_4_rtt_warning=1000";
	$f[]="";
	$f[]="##############################################################################";
	$f[]="# Client/server";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="[serverlist]";
	$f[]="# Define the static servers list";
	$f[]="#server_1_name=localhost";
	$f[]="#server_1_alias=My local PC";
	$f[]="#server_1_port=61209";
	$f[]="#server_2_name=localhost";
	$f[]="#server_2_port=61235";
	$f[]="#server_3_name=192.168.0.17";
	$f[]="#server_3_alias=Another PC on my network";
	$f[]="#server_3_port=61209";
	$f[]="#server_4_name=pasbon";
	$f[]="#server_4_port=61237";
	$f[]="";
	$f[]="[passwords]";
	$f[]="# Define the passwords list";
	$f[]="# Syntax: host=password";
	$f[]="# Where: host is the hostname";
	$f[]="#        password is the clear password";
	$f[]="# Additionally (and optionally) a default password could be defined";
	$f[]="#localhost=abc";
	$f[]="#default=defaultpassword";
	$f[]="";
	$f[]="##############################################################################";
	$f[]="# Exports";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="[influxdb]";
	$f[]="# Configuration for the --export-influxdb option";
	$f[]="# https://influxdb.com/";
	$f[]="host=localhost";
	$f[]="port=8086";
	$f[]="user=root";
	$f[]="password=root";
	$f[]="db=glances";
	$f[]="prefix=localhost";
	$f[]="#tags=foo:bar,spam:eggs";
	$f[]="";
	$f[]="[cassandra]";
	$f[]="# Configuration for the --export-cassandra option";
	$f[]="# Also works for the ScyllaDB";
	$f[]="# https://influxdb.com/ or http://www.scylladb.com/";
	$f[]="host=localhost";
	$f[]="port=9042";
	$f[]="protocol_version=3";
	$f[]="keyspace=glances";
	$f[]="replication_factor=2";
	$f[]="# If not define, table name is set to host key";
	$f[]="table=localhost";
	$f[]="";
	$f[]="[opentsdb]";
	$f[]="# Configuration for the --export-opentsdb option";
	$f[]="# http://opentsdb.net/";
	$f[]="host=localhost";
	$f[]="port=4242";
	$f[]="#prefix=glances";
	$f[]="#tags=foo:bar,spam:eggs";
	$f[]="";
	$f[]="[statsd]";
	$f[]="# Configuration for the --export-statsd option";
	$f[]="# https://github.com/etsy/statsd";
	$f[]="host=localhost";
	$f[]="port=8125";
	$f[]="#prefix=glances";
	$f[]="";
	$f[]="[elasticsearch]";
	$f[]="# Configuration for the --export-elasticsearch option";
	$f[]="# Data are available via the ES Restful API. ex: URL/<index>/cpu/system";
	$f[]="# https://www.elastic.co";
	$f[]="host=localhost";
	$f[]="port=9200";
	$f[]="index=glances";
	$f[]="";
	$f[]="[riemann]";
	$f[]="# Configuration for the --export-riemann option";
	$f[]="# http://riemann.io";
	$f[]="host=localhost";
	$f[]="port=5555";
	$f[]="";
	$f[]="[rabbitmq]";
	$f[]="host=localhost";
	$f[]="port=5672";
	$f[]="user=guest";
	$f[]="password=guest";
	$f[]="queue=glances_queue";
	$f[]="";
	$f[]="##############################################################################";
	$f[]="# AMPS";
	$f[]="# * enable: Enable (true) or disable (false) the AMP";
	$f[]="# * regex: Regular expression to filter the process(es)";
	$f[]="# * refresh: The AMP is executed every refresh seconds";
	$f[]="# * one_line: (optional) Force (if true) the AMP to be displayed in one line";
	$f[]="* * command: (optional) command to execute when the process is detected (thk to the regex)";
	$f[]="# * countmin: (optional) minimal number of processes";
	$f[]="#             A warning will be displayed if number of process < count";
	$f[]="# * countmax: (optional) maximum number of processes";
	$f[]="#             A warning will be displayed if number of process > count";
	$f[]="# * <foo>: Others variables can be defined and used in the AMP script";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="[amp_dropbox]";
	$f[]="# Use the default AMP (no dedicated AMP Python script)";
	$f[]="# Check if the Dropbox daemon is running";
	$f[]="# Every 3 seconds, display the 'dropbox status' command line";
	$f[]="enable=false";
	$f[]="regex=.*dropbox.*";
	$f[]="refresh=3";
	$f[]="one_line=false";
	$f[]="command=dropbox status";
	$f[]="countmin=1";
	$f[]="";
	$f[]="[amp_python]";
	$f[]="# Use the default AMP (no dedicated AMP Python script)";
	$f[]="# Monitor all the Python scripts";
	$f[]="# Alert if more than 20 Python scripts are running";
	$f[]="enable=false";
	$f[]="regex=.*python.*";
	$f[]="refresh=3";
	$f[]="countmax=20";
	$f[]="";
	$f[]="[amp_nginx]";
	$f[]="# Use the NGinx AMP";
	$f[]="# Nginx status page should be enable (https://easyengine.io/tutorials/nginx/status-page/)";
	$f[]="enable=false";
	$f[]="regex=\/usr\/sbin\/nginx";
	$f[]="refresh=60";
	$f[]="one_line=false";
	$f[]="status_url=http://localhost/nginx_status";
	$f[]="";
	$f[]="[amp_systemd]";
	$f[]="# Use the Systemd AMP";
	$f[]="enable=false";
	$f[]="regex=\/usr\/lib\/systemd\/systemd";
	$f[]="refresh=30";
	$f[]="one_line=true";
	$f[]="systemctl_cmd=/usr/bin/systemctl --plain";
	$f[]="";
	$f[]="[amp_systemv]";
	$f[]="# Use the Systemv AMP";
	$f[]="enable=false";
	$f[]="regex=\/sbin\/init";
	$f[]="refresh=30";
	$f[]="one_line=true";
	$f[]="service_cmd=/usr/bin/service --status-all";
	$f[]="";
	@file_put_contents("/etc/glances/glances.conf", @implode("\n", $f));
	
}
function build_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"glances.progress");
}
function restart_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"restart-glances.progress");
}


function install(){
	$unix=new unix();
	build_progress("{installing}",20);
	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){ $unix->DEBIAN_INSTALL_PACKAGE("python3-bottle");}
	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){
		echo "/usr/lib/python3/dist-packages/bottle.py no such file ( see apt-get install python3-bottle)\n";
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 0);
		build_progress("{failed}",110);
		return;
	}
	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 1);
	prepare();
	build_progress("{installing}",50);
	glances_service();
	build_progress("{installing}",60);
	monit();
	schedule();
	build_progress("{starting}",70);
	system("/etc/init.d/glances start");
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 0);
		prepare();
		build_progress("{failed}",110);
		return;
	}
	build_progress("{success}",100);
	
}


function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/glances/glances.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("glances");
	return $unix->PIDOF_PATTERN($Masterbin);

}
function uninstall(){
	build_progress("{uninstalling}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 0);
	prepare();
	if(is_file("/etc/monit/conf.d/APP_GLANCES.monitrc")){
		@unlink("/etc/monit/conf.d/APP_GLANCES.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	@unlink("/etc/cron.d/glances-restart");
	system("/etc/init.d/cron reload");
	build_progress("{stopping_service}",70);
    remove_service("/etc/init.d/glances");
    build_progress("{success}",100);
}


function monit(){
	$f[]="check process APP_GLANCES with pidfile /var/run/glances/glances.pid";
	$f[]="start program = \"/etc/init.d/glances start\"";
	$f[]="stop program = \"/etc/init.d/glances stop\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_GLANCES.monitrc", @implode("\n", $f));
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function glances_service(){
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$glances_parth=$unix->find_program("glances");
	
	$f[]="#! /bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          glances";
	$f[]="# Required-Start:    \$remote_fs \$local_fs \$network";
	$f[]="# Required-Stop:     \$remote_fs \$local_fs \$network";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Starts and daemonize Glances server";
	$f[]="# Description:       Starts and daemonize Glances server";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="# Author: Geoffroy Youri Berret <efrim@azylum.org>";
	$f[]="";
	$f[]="# PATH should only include /usr/* if it runs after the mountnfs.sh script";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="DESC=\"Glances server\"";
	$f[]="NAME=glances";
	$f[]="USER=\$NAME";
	$f[]="DAEMON=\"$glances_parth\"";
	$f[]="PIDFILE=\"/var/run/glances/glances.pid\"";
	$f[]="CONF=\"/etc/glances/glances.conf\"";
	$f[]="DAEMON_ARGS=\"-C \$CONF -s\"";
	$f[]="";
	$f[]="# Exit if the package is not installed";
	$f[]="[ -x \"\$DAEMON\" ] || exit 0";
	$f[]="";
	$f[]="# Read configuration variable file if it is present";
	$f[]="[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME";
	$f[]="";
	$f[]="# Define LSB log_* functions.";
	$f[]="# Depend on lsb-base (>= 3.2-14) to ensure that this file is present";
	$f[]="# and status_of_proc is working.";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="# Ensure /run/glances is there, cf. Debian policy 9.4.1";
	$f[]="# http://www.debian.org/doc/debian-policy/ch-opersys.html#s-fhs-run";
	$f[]="if [ ! -d \"$(dirname \$PIDFILE)\" ]; then";
	$f[]="    mkdir \"$(dirname \$PIDFILE)\"";
	$f[]="    chown \$USER:\$USER \"$(dirname \$PIDFILE)\"";
	$f[]="    chmod 755 \"$(dirname \$PIDFILE)\"";
	$f[]="fi";
	$f[]="";
	$f[]="#";
	$f[]="# Function that starts the daemon/service";
	$f[]="#";
	$f[]="do_start()";
	$f[]="{";
	$f[]="    log_daemon_msg \"Starting \$DESC\" \"\$NAME \"";
	$f[]="    $php ".__FILE__." --prepare";
	$f[]="";
	$f[]="    if [ \"\$RUN\" != \"true\" ]; then";
	$f[]="        log_action_msg \"Not starting glances: disabled by /etc/default/\$NAME\".";
	$f[]="        exit 0";
	$f[]="    fi";
	$f[]="";
	$f[]="    $php ".__FILE__." --start";
	$f[]="    return 0";
	$f[]="}";
	$f[]="";
	$f[]="#";
	$f[]="# Function that stops the daemon/service";
	$f[]="#";
	$f[]="do_stop()";
	$f[]="{";
	$f[]="    log_daemon_msg \"Stopping \$DESC\" \"\$NAME \"";
	$f[]="    $php ".__FILE__." --stop";
	$f[]="    return 0";
	$f[]="}";
	$f[]="";
	$f[]="case \"$1\" in";
	$f[]="  start)";
	$f[]="    do_start";
	$f[]="    case \"$?\" in";
	$f[]="        0|1) log_end_msg 0 ;;";
	$f[]="        2) log_end_msg 1 ;;";
	$f[]="    esac";
	$f[]="    ;;";
	$f[]="  stop)";
	$f[]="    do_stop";
	$f[]="    case \"$?\" in";
	$f[]="        0) log_end_msg 0 ;;";
	$f[]="        1) log_end_msg 1 ;;";
	$f[]="    esac";
	$f[]="    ;;";
	$f[]="  status)";
	$f[]="    status_of_proc -p \"\$PIDFILE\" \"\$DAEMON\" \"\$NAME\"";
	$f[]="    ;;";
	$f[]="  restart|force-reload)";
	$f[]="    do_stop";
	$f[]="    case \"$?\" in";
	$f[]="      0)";
	$f[]="        log_end_msg 0";
	$f[]="        do_start";
	$f[]="        case \"$?\" in";
	$f[]="            0) log_end_msg 0 ;;";
	$f[]="            *) log_end_msg 1 ;; # Failed to start";
	$f[]="        esac";
	$f[]="        ;;";
	$f[]="      *)";
	$f[]="        # Failed to stop";
	$f[]="        if [ \"\$RUN\" != \"true\" ]; then";
	$f[]="            log_action_msg \"disabled by /etc/default/\$NAME\"";
	$f[]="            log_end_msg 0";
	$f[]="        else";
	$f[]="            log_end_msg 1";
	$f[]="        fi";
	$f[]="        ;;";
	$f[]="    esac";
	$f[]="    ;;";
	$f[]="  *)";
	$f[]="    echo \"Usage: invoke-rc.d \$NAME {start|stop|status|restart|force-reload}\" >&2";
	$f[]="    exit 3";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="";
	
	$INITD_PATH="/etc/init.d/glances";
	echo "Glances: [INFO] Writing $INITD_PATH with new config\n";
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
	$Masterbin=$unix->find_program("glances");

	if(is_file("/usr/local/bin/glances")){
        $Masterbin="/usr/local/bin/glances";
    }

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, glances not installed\n";}
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


	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/glances/glances.pid", $pid);
		return;
	}
	$EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
	
	if($EnableGlances==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
		return;
	}
	
	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){ $unix->DEBIAN_INSTALL_PACKAGE("python3-bottle");}

    build_templates();
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} python3-bottle no such installed package\n";}
		return;
	}

	$shfile=$unix->sh_command("$Masterbin -w --bind 127.0.0.1 --theme-white");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	$unix->go_exec($shfile);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){
			@file_put_contents("/var/run/glances/glances.pid", $pid);
			break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		@file_put_contents("/var/run/glances/glances.pid", $pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
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
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




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
function build_templates():bool{


    $f[]="<!DOCTYPE html>";
    $f[]="<html ng-app=\"glancesApp\">";
$f[]="";
$f[]="<head>";
$f[]="    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
$f[]="    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />";
$f[]="    <title ng-bind=\"title\">Glances For Artica</title>";
$f[]="";
$f[]="    <link rel=\"icon\" type=\"image/x-icon\" href=\"favicon.ico\" />";
$f[]="    <script type=\"text/javascript\" src=\"/glances/glances.js\"></script>";
$f[]="    <script>";
$f[]="            angular.module('glances.config', []).constant('REFRESH_TIME', {{ refresh_time }});";
$f[]="    </script>";
$f[]="</head>";
$f[]="";
$f[]="<body>";
$f[]="  <glances></glances>";
$f[]="</body>";
$f[]="</html>";
$f[]="";

$files[]="/usr/lib/python3/dist-packages/glances/outputs/static/templates/index.html.tpl";
    foreach ($files as $pth){
        if(!is_file($pth)){continue;}
        echo "Patching $pth\n";
        @file_put_contents($pth,@implode("\n",$f));
    }

    return true;

}