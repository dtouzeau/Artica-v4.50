<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.munin.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}

if($argv[1]=="--server"){build_server();exit;}
if($argv[1]=="--node"){build_node();exit;}
if($argv[1]=="--reconfigure"){build_node();build_server();exit;}
if($argv[1]=="--install"){install_node();exit;}
if($argv[1]=="--uninstall"){uninstall_node();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--cron"){munin_cron();exit;}




function build_server(){
	
	
	
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"))==0){
		echo "build_server():: Munin is not enabled...\n";
		return;
	}
	$unix=new unix();
	$unix->SystemCreateUser("nobody","nobody");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	@mkdir("/var/lib/munin/cgi-tmp",0755,true);
	@mkdir("/etc/munin/plugin-conf.d",0755,true);
	@mkdir("/var/lib/munin-node/plugin-state",0775,true);
	
	
	system("$chmod 0755 /var/lib/munin/cgi-tmp");
	system("$chmod 0755 /etc/munin/plugin-conf.d");
	system("$chmod 0775 /var/lib/munin-node/plugin-state");
	system("$chown munin:munin /var/lib/munin/cgi-tmp");
	system("$chown nobody:nobody /var/lib/munin-node/plugin-state");
	

	$users=new usersMenus();
	
	@mkdir("/usr/share/artica-postfix/munin",0755,true);
	shell_exec("/bin/chown munin:munin /usr/share/artica-postfix/munin >/dev/null 2>&1");
	
	$f[]="dbdir	/var/lib/munin";
	$f[]="htmldir /var/cache/munin/www";
	$f[]="logdir /var/log/munin";
	$f[]="rundir  /var/run/munin";
	$f[]="#tmpldir	/etc/munin/templates";
	$f[]="#staticdir /etc/munin/static";
	$f[]="# cgitmpdir /var/lib/munin/cgi-tmp";
	$f[]="includedir /etc/munin/munin-conf.d";
	$f[]="";
	$f[]="#graph_period second";
	$f[]="graph_strategy cron";
	$f[]="#munin_cgi_graph_jobs 6";
	$f[]="#cgiurl_graph /munin-cgi/munin-cgi-graph";
	$f[]="max_size_x 4000";
	$f[]="max_size_y 4000";
	$f[]="graph_width 720";
	$f[]="html_strategy cron";
	$f[]="max_processes 24";
	$f[]="#rrdcached_socket /var/run/rrdcached.sock";
	$f[]="";
	$f[]="[localhost.localdomain]";
	$f[]="    address 127.0.0.1";
	$f[]="    use_node_name yes";
	$f[]="";
	$f[]="#";
	$f[]="";

	@file_put_contents("/etc/munin/munin.conf",@implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." munin server /etc/munin/munin.conf done\n";	

	
	$SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
	if($SquidMgrListenPort==0){$SquidMgrListenPort=rand(50000,64000);}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMgrListenPort", $SquidMgrListenPort);
	$ln=$unix->find_program("ln");
	
	
	$f=array();
	$f[]="";
	$f[]="[df*]";
	$f[]="env.warning 92";
	$f[]="env.critical 98";
	$f[]="env.exclude_re ^/run/user";
	$f[]="";
	$f[]="";
	$f[]="[fw_conntrack]";
	$f[]="user root";
	$f[]="";
	$f[]="[fw_forwarded_local]";
	$f[]="user root";
	$f[]="";
	$f[]="[hddtemp_smartctl]";
	$f[]="user root";
	$f[]="";
	$f[]="[hddtemp2]";
	$f[]="user root";
	$f[]="";
	$f[]="[if_*]";
	$f[]="user root";
	$f[]="";
	$f[]="[if_err_*]";
	$f[]="user nobody";
	$f[]="";
	$f[]="[ip_*]";
	$f[]="user root";
	$f[]="";
	$f[]="[ipmi_*]";
	$f[]="user root";
	$f[]="";
	$f[]="[mysql*]";
	$f[]="user root";
	$f[]="env.mysqlopts --defaults-file=/etc/mysql/debian.cnf";
	$f[]="env.mysqluser debian-sys-maint";
	$f[]="env.mysqlconnection DBI:mysql:mysql;mysql_read_default_file=/etc/mysql/debian.cnf";
	$f[]="";
	$postconf=$unix->find_program('postconf');
	if(is_file($postconf)){
		echo "Starting......: ".date("H:i:s")." munin server: Postfix OK\n";
		$f[]="[postfix_mailqueue]";
		$f[]="env.spooldir ".get_queue_directory();
		$f[]="user postfix";
		$f[]="";
		$f[]="[postfix_mailstats]";
		$f[]="env.logdir  /var/log";
		$f[]="env.logfile mail.log";
		$f[]="group adm";
		$f[]="";
		$f[]="[postfix_mailvolume]";
		$f[]="group adm";
		$f[]="env.logdir  /var/log";
		$f[]="env.logfile mail.log";
	}
	$f[]="";
	$f[]="[sendmail_*]";
	$f[]="user smmta";
	$f[]="";
	$f[]="[smart_*]";
	$f[]="user root";
	$f[]="";
	$f[]="[vlan*]";
	$f[]="user root";
	$f[]="";
	$f[]="[ejabberd*]";
	$f[]="user ejabberd";
	$f[]="env.statuses available away chat xa";
	$f[]="env.days 1 7 30";
	$f[]="";
	$f[]="[dhcpd3]";
	$f[]="user root";
	$f[]="env.leasefile /var/lib/dhcp3/dhcpd.leases";
	$f[]="env.configfile /etc/dhcp3/dhcpd.conf";
	$f[]="";
	$f[]="[jmx_*]";
	$f[]="env.ip 127.0.0.1";
	$f[]="env.port 5400";
	$f[]="";
	$f[]="[samba]";
	$f[]="user root";
	$f[]="";
	$f[]="";
	$f[]="[postgres_*]";
	$f[]="user postgres";
	$f[]="env.PGUSER postgres";
	$f[]="env.PGPORT 5432";
	$f[]="";
	$f[]="[fail2ban]";
	$f[]="user root";
	$f[]="";
	
	
	$EnableMySQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL");
	$MYSQLP[]="bin_relay_log";
	$MYSQLP[]="commands";
	$MYSQLP[]="connections";
	$MYSQLP[]="files_tables";
	$MYSQLP[]="innodb_bpool";
	$MYSQLP[]="innodb_bpool_act";
	$MYSQLP[]="innodb_insert_buf";
	$MYSQLP[]="innodb_io";
	$MYSQLP[]="innodb_io_pend";
	$MYSQLP[]="innodb_log";
	$MYSQLP[]="innodb_rows";
	$MYSQLP[]="innodb_semaphores";
	$MYSQLP[]="innodb_tnx";
	$MYSQLP[]="myisam_indexes";
	$MYSQLP[]="network_traffic";
	$MYSQLP[]="qcache";
	$MYSQLP[]="qcache_mem";
	$MYSQLP[]="replication";
	$MYSQLP[]="select_types";
	$MYSQLP[]="slow";
	$MYSQLP[]="sorts";
	$MYSQLP[]="table_locks";
	$MYSQLP[]="tmp_tables";
	if($EnableMySQL==1){
		$f[]="[mysql_*]";
		$f[]="env.mysqladmin /usr/bin/mysqladmin";
		$f[]="env.mysqluser root";
		$f[]="env.mysqlconnection DBI:mysql:mysql;host=localhost;mysql_socket=/var/run/mysqld/mysqld.sock";
		
		foreach ($MYSQLP as $plugin){
			system("$ln -sf /usr/share/munin/plugins/mysql_ /etc/munin/plugins/mysql_$plugin");
		}
		echo "Starting......: ".date("H:i:s")." munin server: MySQL OK\n";
		
	}else{
		foreach ($MYSQLP as $plugin){
			@unlink("/etc/munin/plugins/mysql_$plugin");
		}
		echo "Starting......: ".date("H:i:s")." munin server: MySQL NONE/DISABLED\n";
	}
	
	
	$unbound_control=$unix->find_program("unbound-control");
	echo "Starting......: ".date("H:i:s")." munin server: unbound-control: $unbound_control OK\n";
	@unlink("/etc/munin/plugins/unbound_munin_");
	@unlink("/usr/share/munin/plugins/unbound_munin_");
	@copy("/usr/share/artica-postfix/bin/install/munin/unbound_munin_", "/usr/share/munin/plugins/unbound_munin_");
	@chmod("/usr/share/munin/plugins/unbound_munin_",0755);
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_by_class");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_by_flags");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_by_opcode");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_by_rcode");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_by_type");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_histogram");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_hits");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_memory");
	system("$ln -sf /usr/share/munin/plugins/unbound_munin_ /etc/munin/plugins/unbound_munin_queue");
	$f[]="[unbound*]";
	$f[]="user root";
	$f[]="env.statefile /var/lib/munin-node/plugin-state/unbound-state";
	$f[]="env.unbound_conf /etc/unbound/unbound.conf";
	$f[]="env.unbound_control $unbound_control";
	$f[]="env.spoof_warn 1000";
	$f[]="env.spoof_crit 100000";
	$f[]="";
	$f[]="";
	$f[]="[squid_*]";
	$f[]="user root";
	$f[]="env.squidhost 127.0.0.1";
	$f[]="env.squidport $SquidMgrListenPort";
	$f[]="";
	$EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
	if($EnableNginx==1){
		echo "Starting......: ".date("H:i:s")." munin server: nginx OK\n";
		$f[]="[nginx*]";
		$f[]="env.url http://127.0.0.1:1842/nginx_status/";
		$f[]="";
		system("$ln -sf /usr/share/munin/plugins/nginx_request /etc/munin/plugins/nginx_request");
		system("$ln -sf /usr/share/munin/plugins/nginx_status /etc/munin/plugins/nginx_status");
	}else{
        echo "Starting......: ".date("H:i:s")." munin server: nginx No\n";
		@unlink("/etc/munin/plugins/nginx_request");
		@unlink("/etc/munin/plugins/nginx_status");
	}

	

		@unlink("/etc/munin/plugins/memcached_multi_");
		@unlink("/etc/munin/plugins/memcached_multi_bytes");
		@unlink("/etc/munin/plugins/memcached_multi_commands");
		@unlink("/etc/munin/plugins/memcached_multi_conns");
		@unlink("/etc/munin/plugins/memcached_multi_evictions");
		@unlink("/etc/munin/plugins/memcached_multi_items");
		@unlink("/etc/munin/plugins/memcached_multi_memory");
		@unlink("/etc/munin/plugins/memcached_multi_unfetched");
		

	$EnableMonit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMonit"));
	if($EnableMonit==1){
		if(is_file("/usr/share/artica-postfix/bin/install/munin/monit_parser")){
			@unlink("/usr/share/munin/plugins/monit_parser");
			@copy("/usr/share/artica-postfix/bin/install/munin/monit_parser","/usr/share/munin/plugins/monit_parser");
			@chmod("/usr/share/munin/plugins/monit_parser",0755);
			system("$ln -sf /usr/share/munin/plugins/monit_parser /etc/munin/plugins/monit_parser");
			$f[]="[monit_parser]";
			$f[]="env.port 2874";
			$f[]="env.host 127.0.0.1";
			$f[]="";
		}
		
	}else{
		@unlink("/etc/munin/plugins/monit_parser");
	}
    @file_put_contents("/etc/munin/plugin-conf.d/munin-node",@implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." munin server /etc/munin/plugin-conf.d/munin-node done\n";
	
}

function queue_directory(){
	if(!is_file("/etc/artica-postfix/settings/Daemons/postfix_queue_directory")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("postfix_queue_directory", get_queue_directory());
	}
	return trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("postfix_queue_directory"));
}

function get_queue_directory(){
	$unix=new unix();
	$postconf=$unix->find_program('postconf');
	if($postconf==null){return null;}
	exec("$postconf -h queue_directory 2>&1",$results);
	return trim($results[0]);
}

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/munin.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/munin/munin-node.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("munin-node");
	return $unix->PIDOF_PATTERN($Masterbin);
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("munin-node");
	$GLOBALS["TITLENAME"]="Munin Client";
	$GLOBALS["OUTPUT"]=true;

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, munin-node not installed\n";}
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
		@file_put_contents("/var/run/munin/munin-node.pid", $pid);
		return;
	}


	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	$cmd="$nohup $Masterbin >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);




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

function install_node(){
	$unix=new unix();
    $chown=$unix->find_program("chown");
    $dirs[]="/var/cache/munin";
    $dirs[]="/var/lib/munin";
    $dirs[]="/var/log/munin";
    $dirs[]="/var/cache/munin/www/localdomain";
    foreach ($dirs as $directory){
        if(!is_dir($directory)){
            @mkdir($directory,0755,true);
        }
        @chmod($directory,0755);
        system("$chown munin:munin $directory");
    }

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMunin", 1);
	build_progress(15, "{installing} {service}");
	munin_node_service();
	build_progress(25, "{installing} {APP_MONIT}");
	munin_node_monit();
	build_progress(50, "{reconfiguring}");
	build_node();
	build_server();
	build_progress(55, "{installing} cron");
	munin_cron();
	build_progress(60, "{installing} cron");
	system("/etc/init.d/cron reload");
	$su=$unix->find_program("su");
	
	$ln=$unix->find_program("ln");

	$squid[]="fail2ban";
	foreach ($squid as $filename){
		if(!is_file("/usr/share/munin/plugins/$filename")){continue;}
		if(is_file("/etc/munin/plugins/$filename")){continue;}
		system("$ln -s /usr/share/munin/plugins/$filename /etc/munin/plugins/$filename");
		
	}
	build_progress(65, "{installing} {APP_MUNIN}");
	$remo[]="exim_mailqueue";
	$remo[]="if_ens192";
	$remo[]="if_err_ens192";
	$remo[]="munin_stats";
	$remo[]="forks";
	foreach ($remo as $filename){
		if(is_file("/etc/munin/plugins/$filename")){@unlink("/etc/munin/plugins/$filename");}
	}
	build_progress(70, "{installing} {APP_MUNIN}");
	$munin=new munin_plugins();
	$munin->build();
	
	system("/etc/init.d/munin-node restart");
	$su=$unix->find_program("su");
	build_progress(80, "{initializing} {APP_MUNIN}");
	system("$su - munin --shell=/bin/bash -c \"/usr/share/munin/munin-update --nofork\"");
	build_progress(85, "{initializing} {APP_MUNIN}");
	system("$su - munin --shell=/bin/bash -c \"/usr/share/munin/munin-html\"");
	build_progress(90, "{initializing} {APP_MUNIN}");
	system("$su - munin --shell=/bin/bash -c \"/usr/share/munin/munin-limits\"");
	build_progress(95, "{initializing} {APP_MUNIN}");
	system("$su - munin --shell=/bin/bash -c \"/usr/share/munin/munin-graph --cron\"");
	
	build_progress(95, "{restarting} {APP_MUNIN}");
	system('/etc/init.d/munin-node restart');
	build_progress(100, "{success} {APP_MUNIN}");
	

}

function uninstall_node():bool{
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMunin", 0);
	build_progress(15, "{uninstalling} {APP_MUNIN}");
	@unlink("/etc/cron.d/munin-node");
	@unlink("/etc/cron.d/munin");
	build_progress(20, "{uninstalling} {APP_MUNIN}");

    $c=20;
    $dirs[]="/var/cache/munin";
    $dirs[]="/var/lib/munin";
    $dirs[]="/var/log/munin";
    foreach ($dirs as $directory){
        $c++;
        if(!is_dir($directory)){
            continue;
        }
        build_progress($c, "{uninstalling} $directory");
        shell_exec("$rm -rf $directory");
    }

	build_progress(50, "{uninstalling} {APP_MUNIN}");
	remove_service("/etc/init.d/munin-node");
	build_progress(100, "{success} {APP_MUNIN}");
	
	$squid[]="squid_cache";
	$squid[]="squid_objectsize";
	$squid[]="squid_requests";
	$squid[]="squid_traffic";
	$squid[]="dhcpd3";
	$squid[]="haproxy_";
	
	foreach ($squid as $filename){
		if(is_file("/etc/munin/plugins/$filename")){@unlink("/etc/munin/plugins/$filename");}
	}
	return true;
	
}

function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function munin_node_monit(){
	$f[]="check process APP_MUNIN with pidfile /var/run/munin/munin-node.pid";
	$f[]="\tstart program = \"/etc/init.d/munin-node start\"";
	$f[]="\tstop program = \"/etc/init.d/munin-node stop\"";
	
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_MUNIN.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_MUNIN.monitrc")){
		echo "/etc/monit/conf.d/APP_MUNIN.monitrc failed !!!\n";
	}
	echo "Munin-node: [INFO] Writing /etc/monit/conf.d/APP_MUNIN.monitrc\n";

    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function build_node(){
	
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"))==0){
		echo "build_node():: Munin is not enabled...\n";
		return;
	}
	
	$users=new usersMenus();	
	$conf[]="log_level 4";
	$conf[]="log_file /var/log/munin/munin-node.log";
	$conf[]="pid_file /var/run/munin/munin-node.pid";
	$conf[]="";
	$conf[]="background 1";
	$conf[]="setsid 1";
	$conf[]="";
	$conf[]="user root";
	$conf[]="group root";
	$conf[]="";
	$conf[]="# Regexps for files to ignore";
	$conf[]="";
	$conf[]="ignore_file ~$";
	$conf[]="#ignore_file [#~]$  # FIX doesn't work. '#' starts a comment";
	$conf[]="ignore_file DEADJOE$ ";
	$conf[]="ignore_file \.bak$";
	$conf[]="ignore_file %$";
	$conf[]="ignore_file \.dpkg-(tmp|new|old|dist)$";
	$conf[]="ignore_file \.rpm(save|new)$";
	$conf[]="ignore_file \.pod$";
	$conf[]="";
	$conf[]="# Set this if the client doesn't report the correct hostname when";
	$conf[]="# telnetting to localhost, port 4949";
	$conf[]="#";
	$conf[]="host_name localhost.localdomain";
	$conf[]="";
	$conf[]="# A list of addresses that are allowed to connect.  This must be a";
	$conf[]="# regular expression, since Net::Server does not understand CIDR-style";
	$conf[]="# network notation unless the perl module Net::CIDR is installed.  You";
	$conf[]="# may repeat the allow line as many times as you'd like";
	$conf[]="";
	$conf[]="allow ^127\.0\.0\.1$";
	$conf[]="";
	$conf[]="# If you have installed the Net::CIDR perl module, you can use";
	$conf[]="# multiple cidr_allow and cidr_deny address/mask patterns.  A";
	$conf[]="# connecting client must match any cidr_allow, and not match any";
	$conf[]="# cidr_deny.  Example:";
	$conf[]="";
	$conf[]="# cidr_allow 127.0.0.1/32";
	$conf[]="# cidr_allow 192.0.2.0/24";
	$conf[]="# cidr_deny  192.0.2.42/32";
	$conf[]="";
	$conf[]="# Which address to bind to;";
	$conf[]="#host *";
	$conf[]="host 127.0.0.1";
	$conf[]="";
	$conf[]="# And which port";
	$conf[]="port 4949";
	$conf[]="";	
	
	@file_put_contents("/etc/munin/munin-node.conf",@implode("\n",$conf));
	echo "Starting......: ".date("H:i:s")." munin-node /etc/munin/munin-node.conf done\n";	
	
	$munin=new munin_plugins();
	$munin->build();
	
	
}

function munin_node_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#! /bin/bash";
	$f[]="";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          munin-node";
	$f[]="# Required-Start:    \$network \$named \$local_fs \$remote_fs";
	$f[]="# Required-Stop:     \$network \$named \$local_fs \$remote_fs";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Start/stop Munin-Node";
	$f[]="# Description:       Start/stop Munin-Node";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="DAEMON=/usr/sbin/munin-node";
	$f[]="PIDFILE=/var/run/munin/munin-node.pid";
	$f[]="CONFFILE=/etc/munin/munin-node.conf";
	$f[]="DAEMON_ARGS=";
	$f[]="";
	$f[]=". /lib/lsb/init-functions";
	$f[]="[ -r /etc/default/munin-node ] && . /etc/default/munin-node";
	$f[]="";
	$f[]="if [ ! -x \$DAEMON ]; then";
	$f[]="	log_failure_msg \"Munin-Node appears to be uninstalled.\"";
	$f[]="	exit 5";
	$f[]="elif [ ! -e \$CONFFILE ]; then";
	$f[]="	log_failure_msg \"Munin-Node appears to be unconfigured.\"";
	$f[]="	exit 6";
	$f[]="fi";
	$f[]="";
	$f[]="# Figure out if the pid file is in a non-standard location";
	$f[]="while read line; do";
	$f[]="	line=\${line%%\#*} # get rid of comments";
	$f[]="	set -f";
	$f[]="	line=\$(echo \$line) # get rid of extraneous blanks";
	$f[]="	set +f";
	$f[]="	if [ \"\$line\" != \"\${line#pid_file }\" ]; then";
	$f[]="		PIDFILE=\${line#pid_file }";
	$f[]="	fi";
	$f[]="done < \$CONFFILE";
	$f[]="";
	$f[]="verify_superuser() {";
	$f[]="	action=\$1";
	$f[]="	[ \$EUID -eq 0 ] && return";
	$f[]="	log_failure_msg \"Superuser privileges required for the\" \"\"\$action\" action.\"";
	$f[]="	exit 4";
	$f[]="}";
	$f[]="";
	$f[]="start() {";
	$f[]="	mkdir -p /var/run/munin /var/log/munin";
	$f[]="	chown munin:root /var/run/munin";
	$f[]="	chown munin:www-data /var/log/munin";
	$f[]="	chmod 0755 /var/run/munin";
	$f[]="	chmod 0755 /var/log/munin";
	$f[]="	$php ".__FILE__." --start";
	$f[]="	return 0";
	$f[]="}";
	$f[]="";
	$f[]="stop() {";
	$f[]="	log_daemon_msg \"Stopping Munin-Node\"";
	$f[]="	# killproc() doesn't try hard enough if the pid file is missing,";
	$f[]="	# so create it is gone and the daemon is still running";
	$f[]="	if [ ! -r \$PIDFILE ]; then";
	$f[]="		pid=\$(pidofproc -p \$PIDFILE \$DAEMON)";
	$f[]="		if [ -z \"\$pid\" ]; then";
	$f[]="			log_progress_msg \"stopped beforehand\"";
	$f[]="			log_end_msg 0";
	$f[]="			return 0";
	$f[]="		fi";
	$f[]="		echo \$pid 2>/dev/null > \$PIDFILE";
	$f[]="		if [ \$? -ne 0 ]; then";
	$f[]="			log_end_msg 1";
	$f[]="			return 1";
	$f[]="		fi";
	$f[]="	fi";
	$f[]="	killproc -p \$PIDFILE /usr/bin/munin-node";
	$f[]="	ret=\$?";
	$f[]="	# killproc() isn't thorough enough, ensure the daemon has been";
	$f[]="	# stopped manually";
	$f[]="	attempts=0";
	$f[]="	until ! pidofproc -p \$PIDFILE \$DAEMON >/dev/null; do";
	$f[]="		attempts=\$(( \$attempts + 1 ))";
	$f[]="		sleep 0.05";
	$f[]="		[ \$attempts -lt 20 ] && continue";
	$f[]="		log_end_msg 1";
	$f[]="		return 1";
	$f[]="	done";
	$f[]="	[ \$ret -eq 0 ] && log_progress_msg \"done\"";
	$f[]="	log_end_msg \$ret";
	$f[]="	return \$ret";
	$f[]="}";
	$f[]="";
	$f[]="if [ \"\$#\" -ne 1 ]; then";
	$f[]="	log_failure_msg \"Usage: /etc/init.d/munin-node\" \"{start|stop|restart|force-reload|try-restart}\"";
	$f[]="	exit 2";
	$f[]="fi";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="  	verify_superuser \$1";
	$f[]="  	start";
	$f[]="	exit \$?";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="  	verify_superuser \$1";
	$f[]="  	stop";
	$f[]="	exit \$?";
	$f[]="	;;";
	$f[]="  restart|force-reload)";
	$f[]="  	verify_superuser \$1";
	$f[]="  	stop || exit \$?";
	$f[]="	start";
	$f[]="	exit \$?";
	$f[]="	;;";
	$f[]="  try-restart)";
	$f[]="  	verify_superuser \$1";
	$f[]="	pidofproc -p \$PIDFILE \$DAEMON >/dev/null";
	$f[]="	if [ \$? -eq 0 ]; then";
	$f[]="		stop || exit \$?";
	$f[]="		start";
	$f[]="		exit \$?";
	$f[]="	fi";
	$f[]="	log_success_msg \"Munin-Node was stopped beforehand and thus not\" \"restarted.\"";
	$f[]="	exit 0";
	$f[]="	;;";
	$f[]="  reload)";
	$f[]="  	log_failure_msg \"The \"reload\" action is not implemented.\"";
	$f[]="	exit 3";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="  	pid=\$(pidofproc -p \$PIDFILE \$DAEMON)";
	$f[]="	ret=\$?";
	$f[]="	pid=\${pid% } # pidofproc() supplies a trailing space, strip it";
	$f[]="	if [ \$ret -eq 0 ]; then";
	$f[]="		log_success_msg \"Munin-Node is running (PID: \$pid)\"";
	$f[]="		exit 0";
	$f[]="	# the LSB specifies that I in this case (daemon dead + pid file exists)";
	$f[]="	# should return 1, however lsb-base returned 2 in this case up to and";
	$f[]="	# including version 3.1-10 (cf. #381684).  Since that bug is present";
	$f[]="	# in Sarge, Ubuntu Dapper, and (at the time of writing) Ubuntu Etch,";
	$f[]="	# and taking into account that later versions of pidofproc() do not";
	$f[]="	# under any circumstance return 2, I'll keep understanding invalid";
	$f[]="	# return code for the time being, even though the LSB specifies it is";
	$f[]="	# to be used for the situation where the \"program is dead and /var/lock";
	$f[]="	# lock file exists\".  ";
	$f[]="	elif [ \$ret -eq 1 ] || [ \$ret -eq 2 ]; then";
	$f[]="		log_failure_msg \"Munin-Node is dead, although \$PIDFILE exists.\"";
	$f[]="		exit 1";
	$f[]="	elif [ \$ret -eq 3 ]; then";
	$f[]="		log_warning_msg \"Munin-Node is not running.\"";
	$f[]="		exit 3";
	$f[]="	fi";
	$f[]="	log_warning_msg \"Munin-Node status unknown.\"";
	$f[]="	exit 4";
	$f[]="        ;;";
	$f[]="  *)";
	$f[]="	log_failure_msg \"Usage: /etc/init.d/munin-node\" \"{start|stop|restart|force-reload|try-restart}\"";
	$f[]="	exit 2";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]="log_failure_msg \"Unexpected failure, please file a bug.\"";
	$f[]="exit 1";
	$f[]="";
	
	$INITD_PATH="/etc/init.d/munin-node";
	echo "[INFO] Writing $INITD_PATH with new config\n";
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

function munin_cron(){
    $cgroupsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsEnabled"));
	$f[]="#";
	$f[]="# cron-jobs for munin";
	$f[]="#";
	$f[]="MAILTO=\"\"";
	$f[]="*/5 * * * * munin if [ -x /usr/bin/munin-cron ]; then /usr/bin/munin-cron; fi";
	$f[]="14 10 * * *     munin if [ -x /usr/share/munin/munin-limits ]; then /usr/share/munin/munin-limits --force --contact nagios --contact old-nagios; fi";
	$f[]="\n";
	
	@file_put_contents("/etc/cron.d/munin", @implode("\n", $f));
	$f=array();
    $nice="nice ";
	if($cgroupsEnabled==1) {
        $nice = "/usr/bin/cgexec -g cpu,cpuset,blkio:php ";
    }

    $f[]="#!/bin/sh";
    $f[]="";
    $f[]="# This used to test if the executables were installed.  But that is";
    $f[]="# perfectly redundant and supresses errors that the admin should see.";
    $f[]="";
    $f[]="{$nice}/usr/share/munin/munin-update \$@ || exit 1";
    $f[]="";
    $f[]="# The result of munin-limits is needed by munin-html but not by";
    $f[]="# munin-graph.  So run it in the background now, it will be done";
    $f[]="# before munin-graph.";
    $f[]="";
    $f[]="{$nice}/usr/share/munin/munin-limits \$@ ";
    $f[]="";
    $f[]="# We always launch munin-html.";
    $f[]="# It is a noop if html_strategy is \"cgi\"";
    $f[]="{$nice}/usr/share/munin/munin-html \$@ || exit 1";
    $f[]="";
    $f[]="# The result of munin-html is needed for munin-graph.";
    $f[]="# It is a noop if graph_strategy is \"cgi\"";
    $f[]="{$nice}/usr/share/munin/munin-graph --cron \$@ || exit 1";
    $f[]="";
    @file_put_contents("/usr/bin/munin-cron",@implode("\n",$f));
    @chmod("/usr/bin/munin-cron",0755);




    $f=array();
    $f[]="#";
	$f[]="# cron-jobs for munin-node";
	$f[]="#";
	$f[]="MAILTO=\"\"";
	$f[]="# If the APT plugin is enabled, update packages databases approx. once";
	$f[]="# an hour (12 invokations an hour, 1 in 12 chance that the update will";
	$f[]="# happen), but ensure that there will never be more than two hour (7200";
	$f[]="# seconds) interval between updates..";
	$f[]="*/5 * * * * root if [ -x /etc/munin/plugins/apt_all ]; then /etc/munin/plugins/apt_all update 7200 12 >/dev/null; elif [ -x /etc/munin/plugins/apt ]; then /etc/munin/plugins/apt update 7200 12 >/dev/null; fi";
	$f[]="#\n";
	@file_put_contents("/etc/cron.d/munin-node", @implode("\n", $f));
	$f=array();	
	UNIX_RESTART_CRON();
	
}
