<?php

class freeradius2{
	private $FreeRadiusListenPort=1812;
	private $ListenIP="*";
	private $checkrad;
	
	function __construct(){
		$unix=new unix();
		$sock=new sockets();
		$this->ListenIP=$sock->GET_INFO("FreeRadiusListenIP");
		$this->FreeRadiusListenPort=$sock->GET_INFO("FreeRadiusListenPort");
		if($this->ListenIP==null){$this->ListenIP="*";}
		if(!is_numeric($this->FreeRadiusListenPort)){$this->FreeRadiusListenPort=1812;}
		$this->checkrad=$unix->find_program("checkrad");
		
		
		
	}
	
	
	function mainconf(){
		$f[]="prefix 		= /usr";
		$f[]="exec_prefix 	= /usr";
		$f[]="sysconfdir 	= /etc";
		$f[]="localstatedir = /var";
		$f[]="sbindir 		= \${exec_prefix}/sbin";
		$f[]="logdir 		= /var/log/freeradius";
		$f[]="raddbdir 		= /etc/freeradius";
		$f[]="radacctdir 	= \${logdir}/radacct";
		$f[]="name 			= freeradius";
		$f[]="confdir 		= \${raddbdir}";
		$f[]="run_dir 		= \${localstatedir}/run/\${name}";
		$f[]="db_dir 		= \${raddbdir}";
		$f[]="libdir 		= /usr/lib/freeradius";
		$f[]="pidfile 		= \${run_dir}/\${name}.pid";
		$f[]="user 			= 	root";
		$f[]="group 		= 	root";
		$f[]="max_request_time = 30";
		$f[]="cleanup_delay = 5";
		$f[]="max_requests = 1024";
		$f[]="listen {";
		$f[]="	type = auth";
		$f[]="	ipaddr = *";
		$f[]="	port = 0";
		$f[]="#	interface = eth0";
		$f[]="#	clients = per_socket_clients";
		$f[]="}";
		$f[]="";
		$f[]="listen {";
		$f[]="	ipaddr = $this->ListenIP";
		$f[]="	port = $this->FreeRadiusListenPort";
		$f[]="	type = acct";
		$f[]="#	interface = eth0";
		$f[]="#	clients = per_socket_clients";
		$f[]="}";
		$f[]="";
		$f[]="hostname_lookups = no";
		$f[]="allow_core_dumps = no";
		$f[]="regular_expressions	= yes";
		$f[]="extended_expressions	= yes";
		$f[]="";
		$f[]="log {";
		$f[]="	destination = syslog";
		$f[]="	file = \${logdir}/radius.log";
		$f[]="	syslog_facility = daemon";
		$f[]="	stripped_names = no";
		$f[]="	auth = yes";
		$f[]="	auth_badpass = no";
		$f[]="	auth_goodpass = no";
		$f[]="}";
		$f[]="security {";
		$f[]="	max_attributes = 200";
		$f[]="	reject_delay = 1";
		$f[]="	status_server = yes";
		$f[]="}";
		$f[]="";
		$f[]="proxy_requests  = yes";
		$f[]="\$INCLUDE proxy.conf";
		$f[]="\$INCLUDE clients.conf";
		$f[]="";
		$f[]="thread pool {";
		$f[]="	start_servers = 5";
		$f[]="	max_servers = 32";
		$f[]="	min_spare_servers = 3";
		$f[]="	max_spare_servers = 10";
		$f[]="#	max_queue_size = 65536";
		$f[]="	max_requests_per_server = 0";
		$f[]="}";
		$f[]="";
		$f[]="modules {";
		$f[]="	\$INCLUDE \${confdir}/modules/";
		$f[]="	\$INCLUDE eap.conf";
		$f[]="#	\$INCLUDE sql.conf";
		$f[]="#	\$INCLUDE sql/mysql/counter.conf";
		$f[]="#	\$INCLUDE sqlippool.conf";
		$f[]="}";
		$f[]="";
		$f[]="instantiate {";
		$f[]="	exec";
		$f[]="	expr";
		$f[]="#	daily";
		$f[]="	expiration";
		$f[]="	logintime";
		$f[]="}";
		$f[]="";
		$f[]="\$INCLUDE policy.conf";
		$f[]="\$INCLUDE sites-enabled/";
		$f[]="";
		@file_put_contents("/etc/freeradus/radiusd.conf", @implode("\n", $f));
		
	}
	
}
