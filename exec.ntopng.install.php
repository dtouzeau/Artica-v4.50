#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="IDS Daemon";
$GLOBALS["PROGRESS"]=true;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if($argv[1]=="--redis_server_install"){redis_server_install();exit();}


xinstall();


function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ntopng.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function xinstall(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_idb("{installing} {APP_NTOPNG}",10);
	system("$php /usr/share/artica-postfix/exec.darkstat.php --uninstall");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enablentopng", 1);
	build_progress_idb("{installing} {APP_NTOPNG}",20);
	if(!is_file("/etc/init.d/redis-server")){
	    system("/usr/sbin/artica-phpfpm-service -install-redis");
    }

	install_ntopng();
	install_ntopng_monit();
	build_progress_idb("{installing} {APP_NTOPNG}",30);
	install_pf_ring();
	build_progress_idb("{starting} {APP_NTOPNG}",40);
    system("/usr/sbin/artica-phpfpm-service -start-redis");
	build_progress_idb("{starting} {APP_NTOPNG}",50);
	system("/etc/init.d/pf_ring start");
	build_progress_idb("{starting} {APP_NTOPNG}",60);
	
	
	
	
	@mkdir("/var/tmp/ntopng",0755,true);
	@mkdir("/home/ntopng",0755,true);
	
	system("/etc/init.d/ntopng start");
	
	
	build_progress_idb("{installing} {APP_NTOPNG} {success}",100);
}





function install_ntopng(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/ntopng";
	$php5script="exec.ntopng.php";
	$daemonbinLog="Network traffic probe";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
	$f[]="# Required-Start:    \$local_fs \$syslog \$ntopng";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$ntopng";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";

	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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

function install_pf_ring(){

	$f[]="#!/bin/bash";
	$f[]="#";
	$f[]="# pf_ring          Load the pfring driver";
	$f[]="#";
	$f[]="# chkconfig: 2345 30 60";
	$f[]="#";
	$f[]="# (C) 2003-13 - ntop.org";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          pf_ring";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$network \$syslog";
	$f[]="# Required-Stop:     \$n2disk \$nprobe";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Start/stop pf_ring";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="#Needed to skip systemctl redirect in ubuntu 16";
	$f[]="_SYSTEMCTL_SKIP_REDIRECT=yes";
	$f[]="";
	$f[]="DISTRO=\"unknown\"";
	$f[]="MGMT_INTERFACE=\"\$(/sbin/route | grep default | head -n 1 | tr -s ' ' | cut -d ' ' -f 8)\"";
	$f[]="";
	$f[]="if [ -f /lib/lsb/init-functions ]; then";
	$f[]="  DISTRO=\"debian\"";
	$f[]="  . /lib/lsb/init-functions";
	$f[]="fi";
	$f[]="if [ -f /etc/init.d/functions ]; then";
	$f[]="  DISTRO=\"centos\"";
	$f[]="  . /etc/init.d/functions";
	$f[]="fi";
	$f[]="";
	$f[]="if [ \"\${MGMT_INTERFACE}\" == \"\" ]; then";
	$f[]="  MGMT_INTERFACE=\"eth0\"";
	$f[]="fi";
	$f[]="";
	$f[]="MGMT_INTERFACE_DRIVER=\"\$(/sbin/ethtool -i \$MGMT_INTERFACE | grep driver | cut -d ' ' -f 2)\"";
	$f[]="PFRING=\"pf_ring\"";
	$f[]="DRIVERS_CONFIG_DIR=\"/etc/pf_ring\"";
	$f[]="DRIVERS_FLAVOR=(\"zc\")";
	$f[]="DKMS_DRIVERS=('e1000e' 'igb' 'ixgbe' 'i40e' 'fm10k')";
	$f[]="ERROR=0";
	$f[]="DRIVER_INSTALLED=0";
	$f[]="OLD_HUGEPAGES_CONFIG=\"/etc/pf_ring/hugepages\"";
	$f[]="HUGEPAGES_CONFIG=\"/etc/pf_ring/hugepages.conf\"";
	$f[]="HUGEPAGES_SIZE=`grep Hugepagesize /proc/meminfo | cut -d ':' -f 2|sed 's/kB//g'|sed 's/ //g'` # KB";
	$f[]="HUGEPAGES_MOUNTPOINT=\"/dev/hugepages\"";
	$f[]="MTU_CONFIG=\"/etc/pf_ring/mtu.conf\"";
	$f[]="FORCESTART=0";
	$f[]="FORCESTART_FILE=\"/etc/pf_ring/forcestart\"";
	$f[]="DO_NOT_LOAD_HUGEPAGES=0 # set to 1 to disable hugepages preallocation";
	$f[]="LOAD_HUGEPAGES=0";
	$f[]="PFRING_CONFIG=\"/etc/pf_ring/pf_ring.conf\"";
	$f[]="";
	$f[]="function dkms_installed {";
	$f[]="  if [ \${DISTRO} == \"debian\" ]; then";
	$f[]="    if [ `dpkg -l |grep \$1-zc-dkms |wc -l` -gt 0 ]; then";
	$f[]="      return 1";
	$f[]="    fi";
	$f[]="  elif [ \${DISTRO} == \"centos\" ]; then";
	$f[]="    if [ `rpm -qa | grep \$1-zc | wc -l` -gt 0 ]; then";
	$f[]="      return 1";
	$f[]="    fi";
	$f[]="  fi";
	$f[]="  return 0";
	$f[]="}";
	$f[]="";
	$f[]="check_pf_ring() {";
	$f[]="   # check the module status";
	$f[]="   RETVAL=0";
	$f[]="   if [ \$1 == \"start\" ] && [ `lsmod | grep ^\${PFRING} | wc -l ` -gt 0 ]; then";
	$f[]="       MSG=\"PF_RING already loaded. Exiting\"";
	$f[]="       ERROR=1";
	$f[]="       RETVAL=1";
	$f[]="   elif [ \$1 == \"stop\" ] && [ `lsmod | grep ^\${PFRING} | wc -l ` -le 0 ]; then";
	$f[]="       MSG=\"PF_RING already unloaded Exiting\"";
	$f[]="       ERROR=0 ";
	$f[]="       RETVAL=0";
	$f[]="       [ \${DISTRO} == \"debian\" ] && log_end_msg \$ERROR";
	$f[]="       [ \${DISTRO} == \"centos\" ] && echo_success && echo";
	$f[]="       exit 0";
	$f[]="   fi";
	$f[]="";
	$f[]="   if [ \${ERROR} -gt 0 ]; then";
	$f[]="      if [ \${DISTRO} == \"debian\" ]; then";
	$f[]="         log_failure_msg \"\${MSG}\"";
	$f[]="         log_end_msg \$ERROR";
	$f[]="         exit 99";
	$f[]="      elif [ \${DISTRO} == \"centos\" ]; then";
	$f[]="         echo -n \${MSG} ";
	$f[]="         echo_failure; echo";
	$f[]="         exit 99";
	$f[]="      fi";
	$f[]="   fi";
	$f[]="}";
	$f[]="";
	$f[]="rebuild_dkms() {";
	$f[]="	# Get module version";
	$f[]="	MOD_VERSION=`ls -1d /usr/src/\${1}* | tail -1 | cut -d '/' -f 4 | sed -e \"s/\${1}-//\"`";
	$f[]="	echo \"Uninstalling old \${1} version\"";
	$f[]="	/usr/sbin/dkms uninstall -m \${1} -v \${MOD_VERSION} > /dev/null";
	$f[]="	/usr/sbin/dkms remove    -m \${1} -v \${MOD_VERSION} -k `uname -r` > /dev/null";
	$f[]="	echo \"Compiling new \${1} driver\"";
	$f[]="	/usr/sbin/dkms build     -m \${1} -v \${MOD_VERSION} > /dev/null";
	$f[]="	echo \"Installing new \${1} driver\"";
	$f[]="	/usr/sbin/dkms install   -m \${1} -v \${MOD_VERSION} > /dev/null";
	$f[]="}";
	$f[]="";
	$f[]="check_hugepages() {";
	$f[]="	if [ `cat /sys/kernel/mm/hugepages/hugepages-\${HUGEPAGES_SIZE}kB/nr_hugepages` -gt 0 ] && [ `cat /proc/mounts | grep \${HUGEPAGES_MOUNTPOINT} | wc -l` -gt 0 ]; then # hugepages already loaded";
	$f[]="		LOAD_HUGEPAGES=0";
	$f[]="		return";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="unload_hugepages() {";
	$f[]="	umount \${HUGEPAGES_MOUNTPOINT}";
	$f[]="	echo 0 > /sys/kernel/mm/hugepages/hugepages-\${HUGEPAGES_SIZE}kB/nr_hugepages";
	$f[]="}";
	$f[]="";
	$f[]="load_hugepages() {";
	$f[]="	check_hugepages";
	$f[]="	HUGEPAGES_NUMBER=0";
	$f[]="	if [ \${LOAD_HUGEPAGES} -eq 0 ]; then";
	$f[]="		#hugepages already loaded";
	$f[]="		return";
	$f[]="	fi";
	$f[]="";
	$f[]="	if [ -f \${HUGEPAGES_CONFIG} ]; then";
	$f[]="		cat \${HUGEPAGES_CONFIG} | while read node ; do";
	$f[]="			HUGEPAGES_NODE=`echo \${node} | cut -d ' ' -f 1 | cut -d '=' -f 2` # entry should contain node=yy hugepagenumber=XXX";
	$f[]="			HUGEPAGES_NUMBER=`echo \${node} | cut -d ' ' -f 2 | cut -d '=' -f 2` # entry should contain node=yy hugepagenumber=XXX";
	$f[]="			if [ \${HUGEPAGES_NUMBER} -gt 0 ] && [ -f /sys/devices/system/node/node\${HUGEPAGES_NODE}/hugepages/hugepages-\${HUGEPAGES_SIZE}kB/nr_hugepages ]; then";
	$f[]="				echo \${HUGEPAGES_NUMBER} >  /sys/devices/system/node/node\${HUGEPAGES_NODE}/hugepages/hugepages-\${HUGEPAGES_SIZE}kB/nr_hugepages";
	$f[]="			fi";
	$f[]="			HUGEPAGES_NODE=\"\"";
	$f[]="			HUGEPAGES_NUMBER=\"\"";
	$f[]="		done";
	$f[]="	else # set it to default";
	$f[]="		# Computing max available";
	$f[]="		#MEM_AVAIL=`grep MemFree /proc/meminfo | cut -d ':' -f 2|sed 's/kB//g'|sed 's/ //g'`;";
	$f[]="		#MEM_AVAIL=\$(( MEM_AVAIL - 524288 ))";
	$f[]="		MEM_AVAIL=1048576 # 1GB Hugepages (this avoids consuming all memory at boot time, causing kernel panic)";
	$f[]="		HUGEPAGES_NUMBER=\$(( MEM_AVAIL / HUGEPAGES_SIZE ))";
	$f[]="		if [ \${HUGEPAGES_NUMBER} -gt 0 ]; then";
	$f[]="			echo \${HUGEPAGES_NUMBER} > /sys/kernel/mm/hugepages/hugepages-\${HUGEPAGES_SIZE}kB/nr_hugepages";
	$f[]="		fi";
	$f[]="	fi";
	$f[]="	if [ ! -d \${HUGEPAGES_MOUNTPOINT} ]; then";
	$f[]="		mkdir \${HUGEPAGES_MOUNTPOINT}";
	$f[]="	fi";
	$f[]="	mount -t hugetlbfs none \${HUGEPAGES_MOUNTPOINT}";
	$f[]="}";
	$f[]="";
	$f[]="set_interface_mtu() {";
	$f[]="	MTU=\"\"";
	$f[]="	if [ -f \${MTU_CONFIG} ]; then ";
	$f[]="		HWADDR=`ifconfig \${1} |grep -w HWaddr |awk '{print \$5}'`";
	$f[]="		MTU=`grep -w \"\${HWADDR}\" \${MTU_CONFIG} | cut -d ' ' -f 2`";
	$f[]="		if [ ! -z \${MTU} ] && [ \${MTU} != \"\" ]; then";
	$f[]="			/sbin/ifconfig \${1} mtu \${MTU} > /dev/null 2>&1";
	$f[]="		fi";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="start_interfaces() {";
	$f[]="	SETUP_FOR_PACKET_CAPTURE=\$1";
	$f[]="";
	$f[]="	INTERFACES=\"\$(cat /proc/net/dev | grep ':' | cut -d ':' -f 1|grep -v 'lo' | tr '\n' ' '| sed 's/  / /g')\"";
	$f[]="	for D in \$INTERFACES ; do";
	$f[]="		if [ \$SETUP_FOR_PACKET_CAPTURE -eq 1 ] && [ \"\$D\" != \"\$MGMT_INTERFACE\" ]; then";
	$f[]="			# Disabling offloads";
	$f[]="			/sbin/ethtool -K \$D sg off tso off gso off gro off > /dev/null 2>&1";
	$f[]="";
	$f[]="			# Disabling VLAN stripping";
	$f[]="			/sbin/ethtool -K \$D rxvlan off > /dev/null 2>&1";
	$f[]="";
	$f[]="			# Disabling Flow Control";
	$f[]="			/sbin/ethtool -A \$D rx off > /dev/null 2>&1";
	$f[]="			/sbin/ethtool -A \$D tx off > /dev/null 2>&1";
	$f[]="";
	$f[]="			# Setting max number of RX/TX slots";
	$f[]="			MAX_RX_SLOTS=\$(/sbin/ethtool -g \$D 2>/dev/null | grep \"RX\" | head -n 1 | cut -d ':' -f 2 | tr -d '\t')";
	$f[]="			MAX_TX_SLOTS=\$(/sbin/ethtool -g \$D 2>/dev/null | grep \"TX\" | head -n 1 | cut -d ':' -f 2 | tr -d '\t')";
	$f[]="			if [ ! -z \$MAX_RX_SLOTS ]; then";
	$f[]="				/sbin/ethtool -G \$D rx \$MAX_RX_SLOTS";
	$f[]="				/sbin/ethtool -G \$D tx \$MAX_TX_SLOTS";
	$f[]="			fi";
	$f[]="";
	$f[]="			set_interface_mtu \${D}";
	$f[]="		fi";
	$f[]="		/sbin/ifconfig \$D up";
	$f[]="	done";
	$f[]="}";
	$f[]="";
	$f[]="load_driver() {";
	$f[]="	DRV_PARAM=\"\"";
	$f[]="";
	$f[]="	if [ `/sbin/modinfo \${1}-\${2} | wc -l` -gt 1 ]; then";
	$f[]="		# driver is available";
	$f[]="		DRV_PARAM=`cat \${DRIVERS_CONFIG_DIR}/\${2}/\${1}/\${1}.conf`";
	$f[]="		if [ \"\${MGMT_INTERFACE_DRIVER}\" == \"\${1}\" ] && [ \${FORCESTART} -eq 0 ] && [ ! -f \${FORCESTART_FILE} ]; then";
	$f[]="			echo \"Skipping driver \${1}: driver matches the management interface \${MGMT_INTERFACE}\"";
	$f[]="			return";
	$f[]="		fi";
	$f[]="		# unload old module if needed";
	$f[]="		if [ `lsmod |grep -w ^\${1} | wc -l` -eq 1 ]; then";
	$f[]="			/sbin/modprobe -r \${1}";
	$f[]="			if [ `echo \$?` -gt 0 ]; then";
	$f[]="				echo \"Error unloading driver \${1}\"";
	$f[]="				return";
	$f[]="			fi";
	$f[]="		fi";
	$f[]="			";
	$f[]="		# check if already loaded";
	$f[]="		if [ `lsmod |grep -w ^\${1}_\${2} | wc -l` -eq 1 ]; then";
	$f[]="			echo \"Driver \${1}-\${2} already installed\"";
	$f[]="			DRIVER_INSTALLED=1";
	$f[]="			return				";
	$f[]="		fi";
	$f[]="			";
	$f[]="		/sbin/modprobe \${1}_\${2} \${DRV_PARAM}";
	$f[]="		if [ `echo \$?` -gt 0 ]; then";
	$f[]="			echo \"Error loading driver \${1}-\${2}\"";
	$f[]="			# last resort: try rebuilding dkms driver";
	$f[]="			rebuild_dkms \${1}-\${2}";
	$f[]="			# attempt to load the driver now that it has been rebuilt";
	$f[]="			/sbin/modprobe \${1}_\${2} \${DRV_PARAM}";
	$f[]="			if [ `echo \$?` -eq 0 ]; then";
	$f[]="				DRIVER_INSTALLED=1";
	$f[]="			fi";
	$f[]="		else ";
	$f[]="			DRIVER_INSTALLED=1";
	$f[]="		fi";
	$f[]="	else";
	$f[]="		echo \"Driver \${1}-\${2} not available\"";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="load_zc() {";
	$f[]="	# load dependencies";
	$f[]="	/sbin/modprobe uio > /dev/null 2>&1";
	$f[]="	/sbin/modprobe ptp > /dev/null 2>&1";
	$f[]="	/sbin/modprobe vxlan > /dev/null 2>&1";
	$f[]="	/sbin/modprobe dca > /dev/null 2>&1";
	$f[]="	/sbin/modprobe configfs > /dev/null 2>&1";
	$f[]="";
	$f[]="	# load dkms drivers";
	$f[]="	# search for file under /etc/pf_ring/zc/{e1000e,igb,ixgbe,i40e,fm10k}/{e1000e,igb,ixgbe,i40e,fm10k}.conf";
	$f[]="	for F in \${DRIVERS_FLAVOR[@]} ; do";
	$f[]="		for D in \${DKMS_DRIVERS[@]} ; do";
	$f[]="			if [ -f \${DRIVERS_CONFIG_DIR}/\${F}/\${D}/\${D}.conf ] && [ -f \${DRIVERS_CONFIG_DIR}/\${F}/\${D}/\${D}.start ]; then";
	$f[]="				load_driver \${D} \${F}";
	$f[]="				if [ \${F} == \"zc\" ] && [ \${DO_NOT_LOAD_HUGEPAGES} -eq 0 ]; then";
	$f[]="					LOAD_HUGEPAGES=1";
	$f[]="				fi";
	$f[]="			fi";
	$f[]="		done";
	$f[]="	done";
	$f[]="	";
	$f[]="        CLUSTER_HUGEPAGES=0";
	$f[]="        if [ `grep '\-u\=' /etc/cluster/cluster-*conf 2>/dev/null |wc -l` -ge 1 ] && [ `ls /etc/cluster/cluster-*.start 2>/dev/null |wc -l` -ge 1 ]; then";
	$f[]="                LOAD_HUGEPAGES=1";
	$f[]="        fi";
	$f[]="";
	$f[]="	if [ -f \${HUGEPAGES_CONFIG} ] ; then";
	$f[]="		LOAD_HUGEPAGES=1";
	$f[]="	fi";
	$f[]="";
	$f[]="	if [ \${LOAD_HUGEPAGES} -eq 1 ] ; then";
	$f[]="		load_hugepages";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="unload_driver() {";
	$f[]="	/sbin/modprobe -r \${1}_\${2}";
	$f[]="	if [ `echo \$?` -gt 0 ]; then";
	$f[]="		echo \"Error unloading driver \${1}\"";
	$f[]="		return";
	$f[]="	fi";
	$f[]="	# Restore vanilla driver";
	$f[]="	/sbin/modprobe \${1}";
	$f[]="}";
	$f[]="";
	$f[]="unload_zc() {";
	$f[]="	UNLOAD_HUGEPAGES=0";
	$f[]="	for F in \${DRIVERS_FLAVOR[@]} ; do";
	$f[]="		for D in \${DKMS_DRIVERS[@]} ; do";
	$f[]="			if [ `/sbin/lsmod | grep -w ^\${D}_\${F} |wc -l ` -eq 1 ]; then";
	$f[]="				unload_driver \${D} \${F}";
	$f[]="				if [ \${F} == \"zc\" ] && [ \${DO_NOT_LOAD_HUGEPAGES} -eq 0 ]; then";
	$f[]="					UNLOAD_HUGEPAGES=1";
	$f[]="				fi";
	$f[]="			fi";
	$f[]="		done";
	$f[]="	done";
	$f[]="	if [ \${UNLOAD_HUGEPAGES} -eq 1 ]; then";
	$f[]="		unload_hugepages";
	$f[]="	fi";
	$f[]="	start_interfaces 0";
	$f[]="}";
	$f[]="";
	$f[]="start_pf_ring() {";
	$f[]="";
	$f[]="    # Update driver directory structure";
	$f[]="";
	$f[]="    init_config";
	$f[]="";
	$f[]="    [ \${DISTRO} == \"debian\" ] && log_daemon_msg \"Starting PF_RING module\"";
	$f[]="    [ \${DISTRO} == \"centos\" ] && echo -n \"Starting PF_RING module: \"";
	$f[]="";
	$f[]="    # Set CPU freq to performance useful in particular";
	$f[]="    # on CPUs with aggressive scaling such as Intel E5";
	$f[]="    ";
	$f[]="    find /sys/devices/system/cpu/ -name scaling_governor -exec sh -c 'echo performance > {}' \;";
	$f[]="";
	$f[]="    KERNEL_VERSION=\$(uname -r)";
	$f[]="";
	$f[]="    if [ ! -f \$PFRING_CONFIG ]; then";
	$f[]="        touch \$PFRING_CONFIG";
	$f[]="    fi";
	$f[]="";
	$f[]="    PARAM=\"\$(cat \$PFRING_CONFIG)\"";
	$f[]="";
	$f[]="    PF_RING_MOD=\"/lib/modules/\$KERNEL_VERSION/kernel/net/pf_ring/pf_ring.ko\"";
	$f[]="    PF_RING_MOD_LOCAL=\"/usr/local/pfring/kernel/pf_ring.ko\"";
	$f[]="";
	$f[]="    check_pf_ring start";
	$f[]="    ";
	$f[]="    if [ ! -f \"\${DRIVERS_CONFIG_DIR}/pf_ring.start\" ] && [ ! -f \"\${DRIVERS_CONFIG_DIR}/pfring.start\" ] && [ \${FORCESTART} -eq 0 ]; then";
	$f[]="    	# remove pf_ring in any case";
	$f[]="    	/sbin/modprobe -r pf_ring";
	$f[]="    	#";
	$f[]="        echo \"PF_RING not enabled: please touch /etc/pf_ring/pf_ring.start\"";
	$f[]="        [ \${DISTRO} == \"debian\" ] && log_end_msg \$ERROR";
	$f[]="	    [ \${DISTRO} == \"centos\" ] && echo_success && echo";
	$f[]="	    return";
	$f[]="    fi";
	$f[]="";
	$f[]="	# Try loading pfring";
	$f[]="	/sbin/modprobe pf_ring \$PARAM";
	$f[]="";
	$f[]="	if [ `echo \$?` -gt 0 ]; then";
	$f[]="		# try building dkms";
	$f[]="		rebuild_dkms pfring";
	$f[]="		/sbin/modprobe pf_ring \$PARAM";
	$f[]="		";
	$f[]="		# try with local copies in case of failure";
	$f[]="		if [ `echo \$?` -gt 0 ]; then";
	$f[]="		    if [ -f \$PF_RING_MOD ]; then";
	$f[]="    		    /sbin/insmod \$PF_RING_MOD \$PARAM";
	$f[]="		    elif [ -f \$PF_RING_MOD_LOCAL ]; then";
	$f[]="    	    	/sbin/insmod \$PF_RING_MOD_LOCAL \$PARAM";
	$f[]="			fi";
	$f[]="		fi";
	$f[]="	fi";
	$f[]="   ";
	$f[]="    if [ `lsmod | grep ^\${PFRING} | wc -l ` -le 0 ] ; then";
	$f[]="	   # PFRING not loaded. Exiting";
	$f[]="       MSG=\"Unable to load PF_RING. Exiting\"";
	$f[]="       ERROR=1";
	$f[]="       RETVAL=1";
	$f[]="       if [ \${DISTRO} == \"debian\" ]; then";
	$f[]="           log_failure_msg \"\${MSG}\"";
	$f[]="           log_end_msg \$ERROR";
	$f[]="           exit 99";
	$f[]="       elif [ \${DISTRO} == \"centos\" ]; then";
	$f[]="           echo -n \${MSG} ";
	$f[]="           echo_failure; echo";
	$f[]="           exit 99";
	$f[]="      fi";
	$f[]="    fi";
	$f[]="";
	$f[]="    ## Load NTOP drivers ##";
	$f[]="    load_zc";
	$f[]="    ## Load NTOP drivers ##";
	$f[]="    sleep 1";
	$f[]="    ## Load dummy interfaces associated to timelines or nic cards ##";
	$f[]="    local N2IF_SCRIPT=\"/usr/local/bin/n2if\"";
	$f[]="    if [ -f \"\${N2IF_SCRIPT}\" ]; then";
	$f[]="	\${N2IF_SCRIPT} up-all";
	$f[]="	sleep 1";
	$f[]="    fi";
	$f[]="    ";
	$f[]="    start_interfaces 1";
	$f[]="";
	$f[]="    [ \${DISTRO} == \"debian\" ] && log_end_msg \$ERROR";
	$f[]="    [ \${DISTRO} == \"centos\" ] && echo_success && echo";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="stop_pf_ring() {";
	$f[]="";
	$f[]="    RETVAL=0";
	$f[]="    [ \${DISTRO} == \"debian\" ] && log_daemon_msg \"Stopping PF_RING module\"";
	$f[]="    [ \${DISTRO} == \"centos\" ] && echo -n \"Stopping PF_RING module: \"";
	$f[]="";
	$f[]="    check_pf_ring stop";
	$f[]="";
	$f[]="    if [ -f /etc/init.d/nprobe ]; then";
	$f[]="		/etc/init.d/nprobe stop";
	$f[]="		sleep 1";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -f /etc/init.d/n2disk ]; then";
	$f[]="		/etc/init.d/n2disk stop";
	$f[]="		sleep 1";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -f /etc/init.d/ntop ]; then";
	$f[]="		/etc/init.d/ntop stop";
	$f[]="		sleep 1";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -f /etc/init.d/ntopng ]; then";
	$f[]="		/etc/init.d/ntopng stop";
	$f[]="		sleep 1";
	$f[]="    fi";
	$f[]="";
	$f[]="    ## Unload NTOP drivers ##";
	$f[]="    unload_zc";
	$f[]="    ## Unload NTOP drivers ##";
	$f[]="	";
	$f[]="";
	$f[]="    NUM=\"\$(grep pf_ring /proc/modules|wc -l)\"";
	$f[]="    if [ \$NUM -gt 0 ]; then";
	$f[]="	/sbin/modprobe -r pf_ring";
	$f[]="        NUM=\"\$(grep pf_ring /proc/modules|wc -l)\"";
	$f[]="	if [ \${NUM} -gt 0 ]; then";
	$f[]="	   MSG=\"unable to unload PF_RING module\"";
	$f[]="           [ \${DISTRO} == \"debian\" ] && log_failure_msg \"\$MSG\"";
	$f[]="           [ \${DISTRO} == \"centos\" ] && echo_failure";
	$f[]="           ERROR=1";
	$f[]="	fi";
	$f[]="    fi";
	$f[]="    [ \${DISTRO} == \"debian\" ] && log_end_msg \$ERROR";
	$f[]="    [ \${DISTRO} == \"centos\" ] && echo_success && echo";
	$f[]="}";
	$f[]="";
	$f[]="check_driver_status() {";
	$f[]="	local NUM_CONFIGED_DRIVERS=0";
	$f[]="	local NUM_LOADED_DRIVERS=0";
	$f[]="	local UNLOADED_DRIVERS=()";
	$f[]="	for F in \${DRIVERS_FLAVOR[@]} ; do";
	$f[]="		for D in \${DKMS_DRIVERS[@]} ; do";
	$f[]="			if [ -f \${DRIVERS_CONFIG_DIR}/\${F}/\${D}/\${D}.conf ] && [ -f \${DRIVERS_CONFIG_DIR}/\${F}/\${D}/\${D}.start ]; then";
	$f[]="				NUM_CONFIGED_DRIVERS=\$((NUM_CONFIGED_DRIVERS+1))";
	$f[]="				if [ `lsmod |grep -w ^\${D}_\${F} | wc -l` -eq 1 ]; then";
	$f[]="					NUM_LOADED_DRIVERS=\$((NUM_LOADED_DRIVERS+1))";
	$f[]="				else";
	$f[]="					UNLOADED_DRIVERS+=(\${D}_\${F})";
	$f[]="				fi";
	$f[]="			fi";
	$f[]="		done";
	$f[]="	done";
	$f[]="	if [ \$NUM_CONFIGED_DRIVERS -eq \$NUM_LOADED_DRIVERS ]; then";
	$f[]="		echo \"UP\"";
	$f[]="	else";
	$f[]="		local MSG=\"The following drivers has not been loaded: \"";
	$f[]="		for driver in \${UNLOADED_DRIVERS[@]}; do";
	$f[]="			 MSG+=\"\$driver \"";
	$f[]="		done";
	$f[]="		echo \"\$MSG\"";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="check_pf_ring_status() {";
	$f[]="	# check the module status";
	$f[]="	if [ -f \${DRIVERS_CONFIG_DIR}/\${PFRING}.conf ] && [ -f \${DRIVERS_CONFIG_DIR}/\${PFRING}.start ]; then";
	$f[]="		if [ `lsmod | grep ^\${PFRING} | wc -l` -gt 0 ]; then";
	$f[]="			echo \"UP\"";
	$f[]="		elif [ `lsmod | grep ^\${PFRING} | wc -l` -le 0 ]; then";
	$f[]="			local  MSG=\"pf_ring module not running\"";
	$f[]="			echo \"\$MSG\"";
	$f[]="		fi";
	$f[]="	fi";
	$f[]="";
	$f[]="}";
	$f[]="";
	$f[]="get_status() {";
	$f[]="	EXIT_CODE=0";
	$f[]="	driver_result=\$(check_driver_status);";
	$f[]="	pf_ring_result=\$(check_pf_ring_status);";
	$f[]="";
	$f[]="	if [[ \$driver_result == UP* ]]; then";
	$f[]="		echo \"Drivers Loaded\"";
	$f[]="	else";
	$f[]="		echo \"\$driver_result\"";
	$f[]="		EXIT_CODE=3";
	$f[]="	fi";
	$f[]="";
	$f[]="	if [[ \$pf_ring_result == UP* ]]; then";
	$f[]="		echo \"pf_ring Loaded\"";
	$f[]="	else";
	$f[]="		echo \"\$pf_ring_result\"";
	$f[]="		EXIT_CODE=3";
	$f[]="	fi";
	$f[]="";
	$f[]="	exit \"\$EXIT_CODE\"";
	$f[]="}";
	$f[]="";
	$f[]="init_config() {";
	$f[]="	for F in \${DRIVERS_FLAVOR[@]} ; do";
	$f[]="		for D in \${DKMS_DRIVERS[@]} ; do";
	$f[]="			if [ ! -d \${DRIVERS_CONFIG_DIR}/\${F}/\${D} ]; then";
	$f[]="				mkdir -p \${DRIVERS_CONFIG_DIR}/\${F}/\${D}";
	$f[]="			fi";
	$f[]="		done";
	$f[]="	done";
	$f[]="}";
	$f[]="########";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	start_pf_ring;";
	$f[]="	;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="	stop_pf_ring;";
	$f[]="	;;";
	$f[]="";
	$f[]="  restart)";
	$f[]="	stop_pf_ring;";
	$f[]="	start_pf_ring;";
	$f[]="	;;";
	$f[]="";
	$f[]="  force-start | forcestart)";
	$f[]="	FORCESTART=1";
	$f[]="	start_pf_ring;";
	$f[]="	;;";
	$f[]="";
	$f[]="  force-restart | forcerestart)";
	$f[]="	FORCESTART=1";
	$f[]="	stop_pf_ring;";
	$f[]="	start_pf_ring;";
	$f[]="	;;";
	$f[]="";
	$f[]="  status)";
	$f[]="	get_status;";
	$f[]="	;;";
	$f[]="";
	$f[]="  *)";
	$f[]="	echo \"Usage: /etc/init.d/pf_ring {start|stop|restart|force-start|force-restart|status}\"";
	$f[]="	exit 1";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0";
	$INITD_PATH="/etc/init.d/pf_ring";
	echo "ntopng: [INFO] Writing $INITD_PATH with new config\n";
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


function install_ntopng_monit(){
	$f[]="check process APP_NTOPNG with pidfile /var/run/ntopng/ntopng.pid";
	$f[]="\tstart program = \"/etc/init.d/ntop start\"";
	$f[]="\tstop program = \"/etc/init.d/ntop stop\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_NTOPNG.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_NTOPNG.monitrc")){
		echo "/etc/monit/conf.d/APP_NTOPNG.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

}


