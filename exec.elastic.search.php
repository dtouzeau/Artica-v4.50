<?php

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["OUTPUT"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--join"){join_cluster();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--monit"){build_monit();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--reload"){reload();exit;}
if($argv[1]=="--maps"){put_mapping();exit;}
if($argv[1]=="--pipeline"){create_pipeline();exit;}
if($argv[1]=="--dashboard"){import_dashboard();exit;}
if($argv[1]=="--remove-proxy"){remove_proxydb();exit;}
if($argv[1]=="--get-version"){get_version();exit;}
if($argv[1]=="--artica-install"){artica_install();exit;}
if($argv[1]=="--artica-uninstall"){artica_uninstall();exit;}





function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/ElasticSearch.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_restart($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_join($text,$pourc){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/ElasticSearch.nodes.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function build_progress_artica_install($text,$pourc){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/AsElasticClient.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function artica_install(){
    $unix=new unix();
    build_progress_artica_install("{installing}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsElasticClient", 1);
    build_progress("{done}",100);
}
function artica_uninstall(){
    $unix=new unix();
    build_progress_artica_install("{uninstalling}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsElasticClient", 0);
    build_progress("{done}",100);
}

function install(){
	$unix=new unix();
	build_progress(10, "{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableElasticSearch", 1);
	build_progress(20, "{creating_service}");
    $adduser=$unix->find_program("adduser");
    $addgroup=$unix->find_program("addgroup");
    system("$addgroup --quiet --system elasticsearch");
    system("$adduser --quiet --system --no-create-home --home /nonexistent --ingroup elasticsearch --disabled-password     --shell /bin/false elasticsearch");
    system("$adduser --quiet --system --no-create-home --home /nonexistent --ingroup elasticsearch --disabled-password     --shell /bin/false elasticsearch");

    chown("/etc/default/elasticsearch","elasticsearch");
    chgrp("/etc/default/elasticsearch","elasticsearch");

	create_service();
	build_monit();
	build_config();
	security_limit();
	build_progress(50, "{restarting_service}");
	system("/etc/init.d/elasticsearch restart");
	build_progress(100, "{done}");
}

function build(){
	create_service();
	build_config();
	build_monit();
	security_limit();
	put_mapping();
}

function GET_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/elasticsearch/elasticsearch.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("/java.*?/elasticsearch");
	
}

function join_cluster(){
    $unix=new unix();
    build_progress_join("Contacting master sever...",10);
    $ELASTIC_CLUSTER_WIZARD=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTIC_CLUSTER_WIZARD"));
    $ARTICA_MASTER=$ELASTIC_CLUSTER_WIZARD["ARTICA_MASTER"];
    $ElasticsearchTransportInterface=$ELASTIC_CLUSTER_WIZARD["ElasticsearchTransportInterface"];
    $ElasticsearchTransportPort=$ELASTIC_CLUSTER_WIZARD["ElasticsearchTransportPort"];
    if($ElasticsearchTransportPort==0){$ElasticsearchTransportPort=9300;}

    echo "Artica Master: $ARTICA_MASTER\n";

    if($ElasticsearchTransportInterface==null){
        build_progress_join("Transport interface not defined",110);
        return;
    }
    $ElasticsearchTransportIP = $unix->InterfaceToIPv4($ElasticsearchTransportInterface);
    if($ElasticsearchTransportIP==null){
        build_progress_join("Transport interface [$ElasticsearchTransportInterface] corrupted",110);
        return;
    }


    $mynode=urlencode("$ElasticsearchTransportIP:$ElasticsearchTransportPort");

    $ch = curl_init();
    $method = "GET";
    $url = "$ARTICA_MASTER/nodes.listener.php?elkMaster=yes&node=$mynode";

    $zurl = parse_url($url);
    $masterNode=$zurl['host'];
    if(preg_match("#^(.+?):[0-9]+#",$masterNode,$re)){$masterNode=$re[1];}

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);

    $Infos= curl_getinfo($ch);
    $http_code=$Infos["http_code"];
    echo "HTTP Code: $http_code\n";

    if(curl_errno($ch)){
        $curl_error=curl_error($ch);
        build_progress_join("Communication failed Err.$curl_error",110);
        echo "Error:Curl error: $curl_error\n";
        return;
    }

    curl_close($ch);



     if(!preg_match("#<transportport>([0-9]+)<\/transportport>#is",$result,$re)){
         build_progress_join("Communication failed unable to get master transport port",110);
         return;
     }

     $masterNodePort=intval($re[1]);
     if($masterNode==0){
         build_progress_join("Communication failed Master transport port is set to 0",110);
         return;
     }
    $masterNode="$masterNode:$masterNodePort";

    if(!preg_match("#<hostname>(.+?)<\/hostname>#is",$result,$re)){
        build_progress_join("Communication failed unable to get master hostname",110);
        return;
    }

    $hostname=$re[1];
    $df=explode(".",$hostname);
    $netbiosname=$df[0];



    if(!preg_match("#<NODESNUM>([0-9]+)<\/NODESNUM>#is",$result,$re)){
        build_progress_join("Communication failed unable to get number of nodes.",110);
        return;
    }

    $CountOfNodes=intval($re[1]);
    echo "Master Name.......: $netbiosname\n";
    echo "Master Address....: $masterNode\n";
    echo "Nodes count.......: $CountOfNodes\n";

    if(!preg_match("#<OKNODES>(.*?)<\/OKNODES>#is",$result,$re)){
        preg_match("#<ERROR>(.*?)<\/ERROR>#is",$result,$re);
        echo $result;
        build_progress_join("Communication failed {$re[1]}",110);
        return;
    }

    $ARRAY=unserialize($re[1]);

    if(count($ARRAY)<>$CountOfNodes){
        build_progress_join("Communication failed Number of nodes differ",110);
        return;
    }

    $ARRAY[$masterNode]=$masterNode;
    echo "Adding master node $masterNode\n";
    foreach ($ARRAY as $comp=>$none){
        echo "Node..............: $comp\n";
        $znew[]=$comp;
    }

    if(!preg_match("#<GROUP>(.*?)<\/GROUP>#is",$result,$re)) {
        echo $result;
        build_progress_join("Communication failed missing cluster group", 110);
        return;
    }
$ClusterGroupName=$re[1];


    build_progress_join("{reconfiguring}",20);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticSearchInitialMasterName",$netbiosname);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticSearchClusterLists",@implode(",",$znew));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticsearchTransportPort",$ElasticsearchTransportPort);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticsearchTransportInterface",$ElasticsearchTransportInterface);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticSearchClusterClient",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterGroupName",$ClusterGroupName);



    build_config();
    security_limit();
    build_progress_join( "{restarting_service}",50);
    $rm=$unix->find_program("rm");
    shell_exec("$rm -rf /home/elasticsearch/*");
    system("/etc/init.d/elasticsearch restart");
    build_progress_join("{restarting_service} {done}",100);
}



function restart(){
	$unix=new unix();
	build_progress_restart("{stopping_service}",10);
	system("/etc/init.d/elasticsearch stop");
	$pid=GET_PID();
	$php=$unix->LOCATE_PHP5_BIN();
	if($unix->process_exists($pid)){
		echo "Always running PID $pid\n";
		build_progress_restart("{stopping_service} {failed}",110);
		return;
	}
	build_progress_restart("{reconfiguring}",40);
	build_config();
	build_progress_restart("{reconfiguring}",45);
	create_service();
	build_progress_restart("{reconfiguring}",50);
	security_limit();
	build_progress_restart("{reconfiguring}",55);
	build_progress_restart("{starting_service}",65);
	$tmp=$unix->FILE_TEMP();
	system("/etc/init.d/elasticsearch start >$tmp 2>&1");
	for($i=0;$i<4;$i++){
		if($unix->process_exists(GET_PID())){break;}
		build_progress_restart("{starting_service} {waiting}",65+$i);
		sleep(1);
	}
	
	if($unix->process_exists(GET_PID())){
		build_progress_restart("{starting_service} {success}",100);
        system("$php /usr/share/artica-postfix/exec.elastic.status.php");
		return;
	}
	build_progress_restart("{starting_service} {failed}",110);
	
}
function reload(){
    $unix=new unix();
    build_progress_restart("{reconfiguring}",40);
    build_config();
    build_progress_restart("{reconfiguring}",45);
    create_service();
    build_progress_restart("{reconfiguring}",50);
    security_limit();
    build_progress_restart("{reloading_service}",65);

    $pid=GET_PID();
    $php=$unix->LOCATE_PHP5_BIN();
    if($unix->process_exists($pid)){
        system("/etc/init.d/elasticsearch force-reload");
    }else{
        system("/etc/init.d/elasticsearch start");
    }

    for($i=0;$i<4;$i++){
        if($unix->process_exists(GET_PID())){break;}
        build_progress_restart("{starting_service} {waiting}",65+$i);
        sleep(1);
    }

    if($unix->process_exists(GET_PID())){
        build_progress_restart("{starting_service} {success}",100);
        system("$php /usr/share/artica-postfix/exec.elastic.status.php");
        return;
    }
    build_progress_restart("{starting_service} {failed}",110);
}


function security_limit(){
    $unix=new unix();
    $unix->SystemSecurityLimitsConf();
}




function uninstall(){
	$unix=new unix();
    build_progress(10, "{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableElasticSearch", 0);
	if(is_file("/etc/monit/conf.d/APP_ELASTICSEARCH.monitrc")){
	    @unlink("/etc/monit/conf.d/APP_ELASTICSEARCH.monitrc");
        $unix->reload_monit();
	}
	$delgroup=$unix->find_program("delgroup");
    $deluser=$unix->find_program("deluser");

    system("$delgroup elasticsearch");
    system("$deluser elasticsearch");
    build_progress(15, "{uninstalling}");

    if(is_file("/etc/cron.d/elastic-status")){
        @unlink("/etc/cron.d/elastic-status");
    }

    if(is_file("/etc/init.d/kibana")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKibana", 0);
        remove_service("/etc/init.d/kibana");
        build_progress(20, "{uninstalling} {APP_KIBANA}");
        if(is_file("/etc/monit/conf.d/APP_KIBANA.monitrc")){
            @unlink("/etc/monit/conf.d/APP_KIBANA.monitrc");
            $unix->reload_monit();
        }

    }
    if(is_file("/etc/init.d/filebeat")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFileBeat",0);
        remove_service("/etc/init.d/filebeat");

        if (is_file("/etc/cron.d/filebeat-clean")) {
            @unlink("/etc/cron.d/filebeat-clean");
        }
        if (is_file("/etc/cron.d/filebeat-stats")) {
            @unlink("/etc/cron.d/filebeat-stats");
        }

        build_progress(25, "{uninstall} {APP_FILEBEAT}");
        if (is_file("/etc/monit/conf.d/APP_FILEBEAT.monitrc")) {
            @unlink("/etc/monit/conf.d/APP_FILEBEAT.monitrc");
            $unix->reload_monit();
        }

    }

	
	build_progress(50, "{uninstalling}");
    system("/etc/init.d/cron reload");
	remove_service("/etc/init.d/elasticsearch");
	
	if(is_dir("/home/elasticsearch")){
		$rm=$unix->find_program("rm");
		system("$rm -rf /home/elasticsearch/*");
	}

   build_progress(100, "{uninstalling} {done}");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}



function create_service(){
    $ElasticSearch_FS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearch_FS"));
    if($ElasticSearch_FS==0){
        $ElasticSearch_FS=65536;
    }

	$INITD_PATH="/etc/init.d/elasticsearch";
	$f[]="#!/bin/bash";
    $f[]="#";
    $f[]="# /etc/init.d/elasticsearch -- startup script for Elasticsearch";
    $f[]="#";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          elasticsearch";
    $f[]="# Required-Start:    \$network \$remote_fs \$named";
    $f[]="# Required-Stop:     \$network \$remote_fs \$named";
    $f[]="# Default-Start:     2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Starts elasticsearch";
    $f[]="# Description:       Starts elasticsearch using start-stop-daemon";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="PATH=/bin:/usr/bin:/sbin:/usr/sbin";
    $f[]="NAME=elasticsearch";
    $f[]="DESC=\"Elasticsearch Server\"";
    $f[]="DEFAULT=/etc/default/\$NAME";
    $f[]="ulimit -n $ElasticSearch_FS";
    $f[]="";
    $f[]="if [ `id -u` -ne 0 ]; then";
    $f[]="	echo \"You need root privileges to run this script\"";
    $f[]="	exit 1";
    $f[]="fi";
    $f[]="";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="if [ -r /etc/default/rcS ]; then";
    $f[]="	. /etc/default/rcS";
    $f[]="fi";
    $f[]="";
    $f[]="";
    $f[]="# The following variables can be overwritten in \$DEFAULT";
    $f[]="";
    $f[]="# Directory where the Elasticsearch binary distribution resides";
    $f[]="ES_HOME=/usr/share/\$NAME";
    $f[]="";
    $f[]="# Additional Java OPTS";
    $f[]="#ES_JAVA_OPTS=";
    $f[]="";
    $f[]="# Maximum number of open files";
    $f[]="MAX_OPEN_FILES=65535";
    $f[]="";
    $f[]="# Maximum amount of locked memory";
    $f[]="#MAX_LOCKED_MEMORY=";
    $f[]="";
    $f[]="# Elasticsearch configuration directory";
    $f[]="ES_PATH_CONF=/etc/\$NAME";
    $f[]="";
    $f[]="# Maximum number of VMA (Virtual Memory Areas) a process can own";
    $f[]="MAX_MAP_COUNT=262144";
    $f[]="";
    $f[]="# Elasticsearch PID file directory";
    $f[]="PID_DIR=\"/var/run/elasticsearch\"";
    $f[]="";
    $f[]="# End of variables that can be overwritten in \$DEFAULT";
    $f[]="";
    $f[]="# overwrite settings from default file";
    $f[]="if [ -f \"\$DEFAULT\" ]; then";
    $f[]="	. \"\$DEFAULT\"";
    $f[]="fi";
    $f[]="";
    $f[]="# ES_USER and ES_GROUP settings were removed";
    $f[]="if [ ! -z \"\$ES_USER\" ] || [ ! -z \"\$ES_GROUP\" ]; then";
    $f[]="    echo \"ES_USER and ES_GROUP settings are no longer supported. To run as a custom user/group use the archive distribution of Elasticsearch.\"";
    $f[]="    exit 1";
    $f[]="fi";
    $f[]="";
    $f[]="# Define other required variables";
    $f[]="PID_FILE=\"\$PID_DIR/\$NAME.pid\"";
    $f[]="DAEMON=\$ES_HOME/bin/elasticsearch";
    $f[]="DAEMON_OPTS=\"-d -p \$PID_FILE\"";
    $f[]="";
    $f[]="export ES_JAVA_OPTS";
    $f[]="export ES_JAVA_HOME";
    $f[]="export ES_PATH_CONF";
    $f[]="";
    $f[]="if [ ! -x \"\$DAEMON\" ]; then";
    $f[]="	echo \"The elasticsearch startup script does not exists or it is not executable, tried: \$DAEMON\"";
    $f[]="	exit 1";
    $f[]="fi";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";
    $f[]="";
    $f[]="	log_daemon_msg \"Starting \$DESC\"";
    $f[]="";
    $f[]="	pid=`pidofproc -p \$PID_FILE elasticsearch`";
    $f[]="	if [ -n \"\$pid\" ] ; then";
    $f[]="		log_begin_msg \"Already running.\"";
    $f[]="		log_end_msg 0";
    $f[]="		exit 0";
    $f[]="	fi";
    $f[]="";
    $f[]="	# Ensure that the PID_DIR exists (it is cleaned at OS startup time)";
    $f[]="	if [ -n \"\$PID_DIR\" ] && [ ! -e \"\$PID_DIR\" ]; then";
    $f[]="		mkdir -p \"\$PID_DIR\" && chown elasticsearch:elasticsearch \"\$PID_DIR\"";
    $f[]="	fi";
    $f[]="	if [ -n \"\$PID_FILE\" ] && [ ! -e \"\$PID_FILE\" ]; then";
    $f[]="		touch \"\$PID_FILE\" && chown elasticsearch:elasticsearch \"\$PID_FILE\"";
    $f[]="	fi";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="	if [ -n \"\$MAX_MAP_COUNT\" -a -f /proc/sys/vm/max_map_count ] && [ \"\$MAX_MAP_COUNT\" -gt \$(cat /proc/sys/vm/max_map_count) ]; then";
    $f[]="		sysctl -q -w vm.max_map_count=\$MAX_MAP_COUNT";
    $f[]="	fi";
    $f[]="";
    $f[]="	# Start Daemon";
    $f[]="	start-stop-daemon -d \$ES_HOME --start --user elasticsearch -c elasticsearch --pidfile \"\$PID_FILE\" --exec \$DAEMON -- \$DAEMON_OPTS";
    $f[]="	return=\$?";
    $f[]="	if [ \$return -eq 0 ]; then";
    $f[]="		sleep 5";
    $f[]="		exit 0";
    $f[]="	fi";
    $f[]="	log_end_msg 0";
    $f[]="	exit 0";
    $f[]="	;;";
    $f[]="  stop)";
    $f[]="	log_daemon_msg \"Stopping \$DESC\"";
    $f[]="";
    $f[]="	if [ -f \"\$PID_FILE\" ]; then";
    $f[]="		start-stop-daemon --stop --pidfile \"\$PID_FILE\" --user elasticsearch --quiet --retry forever/TERM/20 > /dev/null";
    $f[]="		if [ \$? -eq 1 ]; then";
    $f[]="			log_progress_msg \"\$DESC is not running but pid file exists, cleaning up\"";
    $f[]="		elif [ \$? -eq 3 ]; then";
    $f[]="			PID=\"`cat \$PID_FILE`\"";
    $f[]="			log_failure_msg \"Failed to stop \$DESC (pid \$PID)\"";
    $f[]="			exit 1";
    $f[]="		fi";
    $f[]="		rm -f \"\$PID_FILE\"";
    $f[]="	else";
    $f[]="		log_progress_msg \"(not running)\"";
    $f[]="	fi";
    $f[]="	log_end_msg 0";
    $f[]="	;;";
    $f[]="  status)";
    $f[]="	status_of_proc -p \$PID_FILE elasticsearch elasticsearch && exit 0 || exit \$?";
    $f[]="	;;";
    $f[]="  restart|force-reload)";
    $f[]="	if [ -f \"\$PID_FILE\" ]; then";
    $f[]="		\$0 stop";
    $f[]="	fi";
    $f[]="	\$0 start";
    $f[]="	;;";
    $f[]="  *)";
    $f[]="	log_success_msg \"Usage: \$0 {start|stop|restart|force-reload|status}\"";
    $f[]="	exit 1";
    $f[]="	;;";
    $f[]="esac";
    $f[]="";
    $f[]="exit 0\n";

    @file_put_contents("$INITD_PATH",@implode("\n",$f));


    @chmod($INITD_PATH,0755);
     if(is_file('/usr/sbin/update-rc.d')){
      shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }
    ApplySecurities();

}

function ApplySecurities(){
    $unix=new unix();
    if(!is_dir("/home/elasticsearch/OutOfMemoryError")){mkdir("/home/elasticsearch/OutOfMemoryError",0755,true);}
    $chown=$unix->find_program("chown");
    system("$chown -R elasticsearch:elasticsearch /etc/elasticsearch");
    system("$chown -R elasticsearch:elasticsearch /var/log/elasticsearch");
    system("$chown -R elasticsearch:elasticsearch /usr/share/elasticsearch");
    system("$chown -R elasticsearch:elasticsearch /var/lib/elasticsearch");
    system("$chown elasticsearch:elasticsearch /etc/default/elasticsearch");
    system("$chown elasticsearch:elasticsearch /home/elasticsearch");
    system("$chown elasticsearch:elasticsearch /home/elasticsearch/OutOfMemoryError");

}

function build_monit(){
	$ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
	if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}

	$f[]="check process APP_ELASTICSEARCH with pidfile /var/run/elasticsearch/elasticsearch.pid";
	$f[]="\tstart program = \"/etc/init.d/elasticsearch start\"";
	$f[]="\tstop program = \"/etc/init.d/elasticsearch stop\"";

	$f[]="\tif failed host 127.0.0.1 port $ElasticsearchBindPort protocol http then restart";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_ELASTICSEARCH.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_ELASTICSEARCH.monitrc")){
		echo "/etc/monit/conf.d/APP_ELASTICSEARCH.monitrc failed !!!\n";
	}
	echo "ElasticSearch: [INFO] Writing /etc/monit/conf.d/APP_ELASTICSEARCH.monitrc\n";
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
}

function build_config(){

	$unix=new unix();
	$uuid=$unix->GetUniqueID();
    ApplySecurities();

    if(!is_file("/etc/cron.d/elastic-status")) {
        $unix->Popuplate_cron_make("elastic-status", "*/3 * * * *", "exec.elastic.status.php");
        system("/etc/init.d/cron reload");
    }

    $php                            = $unix->LOCATE_PHP5_BIN();
	$hostname                       = $unix->hostname_g();

	$ElasticsearchBinIP=null;
	$ElasticsearchBindInterface     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindInterface"));
    $ElasticsearchTransportPort     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchTransportPort"));
    $ElasticsearchTransportInterface= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchTransportInterface"));
    $ElasticsearchBehindReverse     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBehindReverse"));
	$ElasticsearchBindPort          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    $ElasticSearchClusterClient     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterClient"));
    $ElasticSearchClusterClientInjest=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterClientInjest"));
    $ClusterGroupName               = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterGroupName"));
    $df                             = explode(".",$hostname);
	$netbiosname                    = $df[0];

    if($ClusterGroupName==null) {
        $LicenseInfos = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
        if ($LicenseInfos["COMPANY"] == null) {
            $LicenseInfos["COMPANY"] = $WizardSavedSettings["company_name"];
        }
        $ClusterGroupName=$LicenseInfos["COMPANY"];
        $ClusterGroupName=str_replace(".","_",$ClusterGroupName);
        $ClusterGroupName=str_replace(" ","_",$ClusterGroupName);
    }


	if($ElasticsearchBindInterface<>null) {
	    if($ElasticsearchBindInterface<>"lo") {
            $ElasticsearchBinIP = $unix->InterfaceToIPv4($ElasticsearchBindInterface);
        }
    }
    if($ElasticsearchTransportInterface<>null) {
        if($ElasticsearchTransportInterface<>"lo") {
            $ElasticsearchTransportIP = $unix->InterfaceToIPv4($ElasticsearchTransportInterface);
        }
    }

    if($ElasticsearchBehindReverse==1){

        if(!is_file("/etc/init.d/nginx")){
            shell_exec("/usr/sbin/artica-phpfpm-service -nginx-install");
        }

        $ElasticsearchBindPort=9200;
        $ElasticsearchBinIP=null;
    }


	if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}
	if($ElasticsearchTransportPort==0){$ElasticsearchTransportPort=9300;}
	
	if($ElasticsearchBinIP==null){$ElasticsearchBinIP_token="127.0.0.1";}else{
        $ElasticsearchBinIP_token="[\"$ElasticsearchBinIP\",\"127.0.0.1\"]";
    }

    if($ElasticsearchTransportIP==null){
        $ElasticsearchTransportIP="127.0.0.1";
    }

    if($ElasticsearchTransportIP=="127.0.0.1"){
        if($ElasticsearchBinIP<>null) {
            if ($ElasticsearchBinIP <> "127.0.0.1") {
                $ElasticsearchTransportIP = $ElasticsearchBinIP;
            }
        }
    }



    if(trim($ElasticsearchTransportIP)==null){$ElasticsearchTransportIP="127.0.0.1";}
    if(trim($ElasticsearchBinIP)==null){$ElasticsearchBinIP="127.0.0.1";}
    echo "Starting......: ".date("H:i:s")." Listen Injections on $ElasticsearchBinIP:$ElasticsearchBindPort\n";
    echo "Starting......: ".date("H:i:s")." Listen Transports on $ElasticsearchTransportIP:$ElasticsearchTransportPort\n";


    $conf[]="cluster.name: $ClusterGroupName";
    $conf[]="node.name: $netbiosname";
    $conf[]="node.attr.rack: Artica";
    $conf[]="node.attr.rack_id: $uuid";
    //https://docs.fortinet.com/document/fortisiem/5.2.5/elasticsearch-storage-guide/887430/setting-up-elasticsearch-for-fortisiem-event-storage
    $conf[]="path.data: /home/elasticsearch";
    $conf[]="path.logs: /var/log/elasticsearch";
    #$conf[]="bootstrap.memory_lock: true";
    $conf[]="network.host: $ElasticsearchBinIP_token";
    $conf[]="http.port: $ElasticsearchBindPort";
    $conf[]="transport.port: $ElasticsearchTransportPort";
    $conf[]="transport.host: $ElasticsearchTransportIP";




    $ElasticSearchClusterLists=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterLists"));
    $exploded=explode(",",$ElasticSearchClusterLists);
    $MAINCLSTR=array();
    foreach ($exploded as $name){
        $MAINCLSTR[$name]=$name;
    }

    $str=array();
    $MAINCLSTR[$ElasticsearchTransportIP]=$ElasticsearchTransportIP;
    foreach ($MAINCLSTR as $name=>$none){
        if(trim($name)==null){continue;}
        $str[]="\"$name\"";
    }

    if(count($str)>0) {
        $conf[] = "discovery.seed_hosts: [" . @implode(",", $str) . "]";
    }

    $ElasticSearchClusterClientInjest_text="false";
    if($ElasticSearchClusterClientInjest==1){$ElasticSearchClusterClientInjest_text="true";}

    if($ElasticSearchClusterClient==1){
        $ElasticSearchInitialMasterName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchInitialMasterName"));
        $conf[]="node.master: false";
        $conf[]="node.data: true";
        $conf[]="node.ingest: $ElasticSearchClusterClientInjest_text";
        $conf[]="cluster.initial_master_nodes: $ElasticSearchInitialMasterName";
    }else{
        $conf[]="node.master: true";
        $conf[]="node.data: true";
        $conf[]="node.ingest: true";
        $conf[]="cluster.initial_master_nodes: $netbiosname";
       // $conf[]="cluster.remote.connect: false";
    }


    $conf[]="";
	@file_put_contents("/etc/elasticsearch/elasticsearch.yml", @implode("\n", $conf));



	$f=array();
    $f[]="status = error";
    $f[]="";
    $f[]="# log action execution errors for easier debugging";
    $f[]="logger.action.name = org.elasticsearch.action";
    $f[]="logger.action.level = debug";
    $f[]="";
    $f[]="appender.console.type = Console";
    $f[]="appender.console.name = console";
    $f[]="appender.console.layout.type = PatternLayout";
    $f[]="appender.console.layout.pattern = [%d{ISO8601}][%-5p][%-25c{1.}] [%node_name]%marker %m%n";
    $f[]="";
    $f[]="######## Server JSON ############################";
    $f[]="appender.rolling.type = RollingFile";
    $f[]="appender.rolling.name = rolling";
    $f[]="appender.rolling.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}_server.json";
    $f[]="appender.rolling.layout.type = ESJsonLayout";
    $f[]="appender.rolling.layout.type_name = server";
    $f[]="";
    $f[]="appender.rolling.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}-%d{yyyy-MM-dd}-%i.json.gz";
    $f[]="appender.rolling.policies.type = Policies";
    $f[]="appender.rolling.policies.time.type = TimeBasedTriggeringPolicy";
    $f[]="appender.rolling.policies.time.interval = 1";
    $f[]="appender.rolling.policies.time.modulate = true";
    $f[]="appender.rolling.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.rolling.policies.size.size = 128MB";
    $f[]="appender.rolling.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.rolling.strategy.fileIndex = nomax";
    $f[]="appender.rolling.strategy.action.type = Delete";
    $f[]="appender.rolling.strategy.action.basepath = \${sys:es.logs.base_path}";
    $f[]="appender.rolling.strategy.action.condition.type = IfFileName";
    $f[]="appender.rolling.strategy.action.condition.glob = \${sys:es.logs.cluster_name}-*";
    $f[]="appender.rolling.strategy.action.condition.nested_condition.type = IfAccumulatedFileSize";
    $f[]="appender.rolling.strategy.action.condition.nested_condition.exceeds = 2GB";
    $f[]="################################################";
    $f[]="######## Server -  old style pattern ###########";
    $f[]="appender.rolling_old.type = RollingFile";
    $f[]="appender.rolling_old.name = rolling_old";
    $f[]="appender.rolling_old.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}.log";
    $f[]="appender.rolling_old.layout.type = PatternLayout";
    $f[]="appender.rolling_old.layout.pattern = [%d{ISO8601}][%-5p][%-25c{1.}] [%node_name]%marker %m%n";
    $f[]="";
    $f[]="appender.rolling_old.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}-%d{yyyy-MM-dd}-%i.log.gz";
    $f[]="appender.rolling_old.policies.type = Policies";
    $f[]="appender.rolling_old.policies.time.type = TimeBasedTriggeringPolicy";
    $f[]="appender.rolling_old.policies.time.interval = 1";
    $f[]="appender.rolling_old.policies.time.modulate = true";
    $f[]="appender.rolling_old.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.rolling_old.policies.size.size = 128MB";
    $f[]="appender.rolling_old.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.rolling_old.strategy.fileIndex = nomax";
    $f[]="appender.rolling_old.strategy.action.type = Delete";
    $f[]="appender.rolling_old.strategy.action.basepath = \${sys:es.logs.base_path}";
    $f[]="appender.rolling_old.strategy.action.condition.type = IfFileName";
    $f[]="appender.rolling_old.strategy.action.condition.glob = \${sys:es.logs.cluster_name}-*";
    $f[]="appender.rolling_old.strategy.action.condition.nested_condition.type = IfAccumulatedFileSize";
    $f[]="appender.rolling_old.strategy.action.condition.nested_condition.exceeds = 2GB";
    $f[]="################################################";
    $f[]="";
    $f[]="rootLogger.level = info";
    $f[]="rootLogger.appenderRef.console.ref = console";
    $f[]="rootLogger.appenderRef.rolling.ref = rolling";
    $f[]="rootLogger.appenderRef.rolling_old.ref = rolling_old";
    $f[]="";
    $f[]="######## Deprecation JSON #######################";
    $f[]="appender.deprecation_rolling.type = RollingFile";
    $f[]="appender.deprecation_rolling.name = deprecation_rolling";
    $f[]="appender.deprecation_rolling.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}_deprecation.json";
    $f[]="appender.deprecation_rolling.layout.type = ESJsonLayout";
    $f[]="appender.deprecation_rolling.layout.type_name = deprecation";
    $f[]="";
    $f[]="appender.deprecation_rolling.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}_deprecation-%i.json.gz";
    $f[]="appender.deprecation_rolling.policies.type = Policies";
    $f[]="appender.deprecation_rolling.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.deprecation_rolling.policies.size.size = 1GB";
    $f[]="appender.deprecation_rolling.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.deprecation_rolling.strategy.max = 4";
    $f[]="#################################################";
    $f[]="######## Deprecation -  old style pattern #######";
    $f[]="appender.deprecation_rolling_old.type = RollingFile";
    $f[]="appender.deprecation_rolling_old.name = deprecation_rolling_old";
    $f[]="appender.deprecation_rolling_old.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}_deprecation.log";
    $f[]="appender.deprecation_rolling_old.layout.type = PatternLayout";
    $f[]="appender.deprecation_rolling_old.layout.pattern = [%d{ISO8601}][%-5p][%-25c{1.}] [%node_name]%marker %m%n";
    $f[]="";
    $f[]="appender.deprecation_rolling_old.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _deprecation-%i.log.gz";
    $f[]="appender.deprecation_rolling_old.policies.type = Policies";
    $f[]="appender.deprecation_rolling_old.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.deprecation_rolling_old.policies.size.size = 1GB";
    $f[]="appender.deprecation_rolling_old.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.deprecation_rolling_old.strategy.max = 4";
    $f[]="#################################################";
    $f[]="logger.deprecation.name = org.elasticsearch.deprecation";
    $f[]="logger.deprecation.level = warn";
    $f[]="logger.deprecation.appenderRef.deprecation_rolling.ref = deprecation_rolling";
    $f[]="logger.deprecation.appenderRef.deprecation_rolling_old.ref = deprecation_rolling_old";
    $f[]="logger.deprecation.additivity = false";
    $f[]="";
    $f[]="######## Search slowlog JSON ####################";
    $f[]="appender.index_search_slowlog_rolling.type = RollingFile";
    $f[]="appender.index_search_slowlog_rolling.name = index_search_slowlog_rolling";
    $f[]="appender.index_search_slowlog_rolling.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs\ ";
    $f[]="  .cluster_name}_index_search_slowlog.json";
    $f[]="appender.index_search_slowlog_rolling.layout.type = ESJsonLayout";
    $f[]="appender.index_search_slowlog_rolling.layout.type_name = index_search_slowlog";
    $f[]="";
    $f[]="appender.index_search_slowlog_rolling.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs\ ";
    $f[]="  .cluster_name}_index_search_slowlog-%i.json.gz";
    $f[]="appender.index_search_slowlog_rolling.policies.type = Policies";
    $f[]="appender.index_search_slowlog_rolling.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.index_search_slowlog_rolling.policies.size.size = 1GB";
    $f[]="appender.index_search_slowlog_rolling.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.index_search_slowlog_rolling.strategy.max = 4";
    $f[]="#################################################";
    $f[]="######## Search slowlog -  old style pattern ####";
    $f[]="appender.index_search_slowlog_rolling_old.type = RollingFile";
    $f[]="appender.index_search_slowlog_rolling_old.name = index_search_slowlog_rolling_old";
    $f[]="appender.index_search_slowlog_rolling_old.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _index_search_slowlog.log";
    $f[]="appender.index_search_slowlog_rolling_old.layout.type = PatternLayout";
    $f[]="appender.index_search_slowlog_rolling_old.layout.pattern = [%d{ISO8601}][%-5p][%-25c{1.}] [%node_name]%marker %m%n";
    $f[]="";
    $f[]="appender.index_search_slowlog_rolling_old.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _index_search_slowlog-%i.log.gz";
    $f[]="appender.index_search_slowlog_rolling_old.policies.type = Policies";
    $f[]="appender.index_search_slowlog_rolling_old.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.index_search_slowlog_rolling_old.policies.size.size = 1GB";
    $f[]="appender.index_search_slowlog_rolling_old.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.index_search_slowlog_rolling_old.strategy.max = 4";
    $f[]="#################################################";
    $f[]="logger.index_search_slowlog_rolling.name = index.search.slowlog";
    $f[]="logger.index_search_slowlog_rolling.level = trace";
    $f[]="logger.index_search_slowlog_rolling.appenderRef.index_search_slowlog_rolling.ref = index_search_slowlog_rolling";
    $f[]="logger.index_search_slowlog_rolling.appenderRef.index_search_slowlog_rolling_old.ref = index_search_slowlog_rolling_old";
    $f[]="logger.index_search_slowlog_rolling.additivity = false";
    $f[]="";
    $f[]="######## Indexing slowlog JSON ##################";
    $f[]="appender.index_indexing_slowlog_rolling.type = RollingFile";
    $f[]="appender.index_indexing_slowlog_rolling.name = index_indexing_slowlog_rolling";
    $f[]="appender.index_indexing_slowlog_rolling.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _index_indexing_slowlog.json";
    $f[]="appender.index_indexing_slowlog_rolling.layout.type = ESJsonLayout";
    $f[]="appender.index_indexing_slowlog_rolling.layout.type_name = index_indexing_slowlog";
    $f[]="";
    $f[]="appender.index_indexing_slowlog_rolling.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _index_indexing_slowlog-%i.json.gz";
    $f[]="appender.index_indexing_slowlog_rolling.policies.type = Policies";
    $f[]="appender.index_indexing_slowlog_rolling.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.index_indexing_slowlog_rolling.policies.size.size = 1GB";
    $f[]="appender.index_indexing_slowlog_rolling.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.index_indexing_slowlog_rolling.strategy.max = 4";
    $f[]="#################################################";
    $f[]="######## Indexing slowlog -  old style pattern ##";
    $f[]="appender.index_indexing_slowlog_rolling_old.type = RollingFile";
    $f[]="appender.index_indexing_slowlog_rolling_old.name = index_indexing_slowlog_rolling_old";
    $f[]="appender.index_indexing_slowlog_rolling_old.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _index_indexing_slowlog.log";
    $f[]="appender.index_indexing_slowlog_rolling_old.layout.type = PatternLayout";
    $f[]="appender.index_indexing_slowlog_rolling_old.layout.pattern = [%d{ISO8601}][%-5p][%-25c{1.}] [%node_name]%marker %m%n";
    $f[]="";
    $f[]="appender.index_indexing_slowlog_rolling_old.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}\ ";
    $f[]="  _index_indexing_slowlog-%i.log.gz";
    $f[]="appender.index_indexing_slowlog_rolling_old.policies.type = Policies";
    $f[]="appender.index_indexing_slowlog_rolling_old.policies.size.type = SizeBasedTriggeringPolicy";
    $f[]="appender.index_indexing_slowlog_rolling_old.policies.size.size = 1GB";
    $f[]="appender.index_indexing_slowlog_rolling_old.strategy.type = DefaultRolloverStrategy";
    $f[]="appender.index_indexing_slowlog_rolling_old.strategy.max = 4";
    $f[]="#################################################";
    $f[]="";
    $f[]="logger.index_indexing_slowlog.name = index.indexing.slowlog.index";
    $f[]="logger.index_indexing_slowlog.level = trace";
    $f[]="logger.index_indexing_slowlog.appenderRef.index_indexing_slowlog_rolling.ref = index_indexing_slowlog_rolling";
    $f[]="logger.index_indexing_slowlog.appenderRef.index_indexing_slowlog_rolling_old.ref = index_indexing_slowlog_rolling_old";
    $f[]="logger.index_indexing_slowlog.additivity = false";
    $f[]="";
    $f[]="";
    $f[]="appender.audit_rolling.type = RollingFile";
    $f[]="appender.audit_rolling.name = audit_rolling";
    $f[]="appender.audit_rolling.fileName = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}_audit.json";
    $f[]="appender.audit_rolling.layout.type = PatternLayout";
    $f[]="appender.audit_rolling.layout.pattern = {\ ";
    $f[]="                \"@timestamp\":\"%d{ISO8601}\"\ ";
    $f[]="                %varsNotEmpty{, \"node.name\":\"%enc{%map{node.name}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"node.id\":\"%enc{%map{node.id}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"host.name\":\"%enc{%map{host.name}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"host.ip\":\"%enc{%map{host.ip}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"event.type\":\"%enc{%map{event.type}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"event.action\":\"%enc{%map{event.action}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.name\":\"%enc{%map{user.name}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.run_by.name\":\"%enc{%map{user.run_by.name}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.run_as.name\":\"%enc{%map{user.run_as.name}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.realm\":\"%enc{%map{user.realm}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.run_by.realm\":\"%enc{%map{user.run_by.realm}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.run_as.realm\":\"%enc{%map{user.run_as.realm}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"user.roles\":%map{user.roles}}\ ";
    $f[]="                %varsNotEmpty{, \"origin.type\":\"%enc{%map{origin.type}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"origin.address\":\"%enc{%map{origin.address}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"realm\":\"%enc{%map{realm}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"url.path\":\"%enc{%map{url.path}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"url.query\":\"%enc{%map{url.query}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"request.method\":\"%enc{%map{request.method}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"request.body\":\"%enc{%map{request.body}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"request.id\":\"%enc{%map{request.id}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"action\":\"%enc{%map{action}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"request.name\":\"%enc{%map{request.name}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"indices\":%map{indices}}\ ";
    $f[]="                %varsNotEmpty{, \"opaque_id\":\"%enc{%map{opaque_id}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"x_forwarded_for\":\"%enc{%map{x_forwarded_for}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"transport.profile\":\"%enc{%map{transport.profile}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"rule\":\"%enc{%map{rule}}{JSON}\"}\ ";
    $f[]="                %varsNotEmpty{, \"event.category\":\"%enc{%map{event.category}}{JSON}\"}\ ";
    $f[]="                }%n";
    $f[]="# \"node.name\" node name from the `elasticsearch.yml` settings";
    $f[]="# \"node.id\" node id which should not change between cluster restarts";
    $f[]="# \"host.name\" unresolved hostname of the local node";
    $f[]="# \"host.ip\" the local bound ip (i.e. the ip listening for connections)";
    $f[]="# \"event.type\" a received REST request is translated into one or more transport requests. This indicates which processing layer generated the event \"rest\" or \"transport\" (internal)";
    $f[]="# \"event.action\" the name of the audited event, eg. \"authentication_failed\", \"access_granted\", \"run_as_granted\", etc.";
    $f[]="# \"user.name\" the subject name as authenticated by a realm";
    $f[]="# \"user.run_by.name\" the original authenticated subject name that is impersonating another one.";
    $f[]="# \"user.run_as.name\" if this \"event.action\" is of a run_as type, this is the subject name to be impersonated as.";
    $f[]="# \"user.realm\" the name of the realm that authenticated \"user.name\"";
    $f[]="# \"user.run_by.realm\" the realm name of the impersonating subject (\"user.run_by.name\")";
    $f[]="# \"user.run_as.realm\" if this \"event.action\" is of a run_as type, this is the realm name the impersonated user is looked up from";
    $f[]="# \"user.roles\" the roles array of the user; these are the roles that are granting privileges";
    $f[]="# \"origin.type\" it is \"rest\" if the event is originating (is in relation to) a REST request; possible other values are \"transport\" and \"ip_filter\"";
    $f[]="# \"origin.address\" the remote address and port of the first network hop, i.e. a REST proxy or another cluster node";
    $f[]="# \"realm\" name of a realm that has generated an \"authentication_failed\" or an \"authentication_successful\"; the subject is not yet authenticated";
    $f[]="# \"url.path\" the URI component between the port and the query string; it is percent (URL) encoded";
    $f[]="# \"url.query\" the URI component after the path and before the fragment; it is percent (URL) encoded";
    $f[]="# \"request.method\" the method of the HTTP request, i.e. one of GET, POST, PUT, DELETE, OPTIONS, HEAD, PATCH, TRACE, CONNECT";
    $f[]="# \"request.body\" the content of the request body entity, JSON escaped";
    $f[]="# \"request.id\" a synthentic identifier for the incoming request, this is unique per incoming request, and consistent across all audit events generated by that request";
    $f[]="# \"action\" an action is the most granular operation that is authorized and this identifies it in a namespaced way (internal)";
    $f[]="# \"request.name\" if the event is in connection to a transport message this is the name of the request class, similar to how rest requests are identified by the url path (internal)";
    $f[]="# \"indices\" the array of indices that the \"action\" is acting upon";
    $f[]="# \"opaque_id\" opaque value conveyed by the \"X-Opaque-Id\" request header";
    $f[]="# \"x_forwarded_for\" the addresses from the \"X-Forwarded-For\" request header, as a verbatim string value (not an array)";
    $f[]="# \"transport.profile\" name of the transport profile in case this is a \"connection_granted\" or \"connection_denied\" event";
    $f[]="# \"rule\" name of the applied rulee if the \"origin.type\" is \"ip_filter\"";
    $f[]="# \"event.category\" fixed value \"elasticsearch-audit\"";
    $f[]="";
    $f[]="appender.audit_rolling.filePattern = \${sys:es.logs.base_path}\${sys:file.separator}\${sys:es.logs.cluster_name}_audit-%d{yyyy-MM-dd}.json";
    $f[]="appender.audit_rolling.policies.type = Policies";
    $f[]="appender.audit_rolling.policies.time.type = TimeBasedTriggeringPolicy";
    $f[]="appender.audit_rolling.policies.time.interval = 1";
    $f[]="appender.audit_rolling.policies.time.modulate = true";
    $f[]="";
    $f[]="logger.xpack_security_audit_logfile.name = org.elasticsearch.xpack.security.audit.logfile.LoggingAuditTrail";
    $f[]="logger.xpack_security_audit_logfile.level = info";
    $f[]="logger.xpack_security_audit_logfile.appenderRef.audit_rolling.ref = audit_rolling";
    $f[]="logger.xpack_security_audit_logfile.additivity = false";
    $f[]="";
    $f[]="logger.xmlsig.name = org.apache.xml.security.signature.XMLSignature";
    $f[]="logger.xmlsig.level = error";
    $f[]="logger.samlxml_decrypt.name = org.opensaml.xmlsec.encryption.support.Decrypter";
    $f[]="logger.samlxml_decrypt.level = fatal";
    $f[]="logger.saml2_decrypt.name = org.opensaml.saml.saml2.encryption.Decrypter";
    $f[]="logger.saml2_decrypt.level = fatal\n";
        
	
	@file_put_contents("/etc/elasticsearch/log4j2.properties", @implode("\n", $f));
	$f=array();
    $ElasticsearchMaxMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchMaxMemory"));
    if($ElasticsearchMaxMemory==0){$ElasticsearchMaxMemory=512;}

    $f[]="## JVM configuration";
    $f[]="";
    $f[]="################################################################";
    $f[]="## IMPORTANT: JVM heap size";
    $f[]="################################################################";
    $f[]="##";
    $f[]="## You should always set the min and max JVM heap";
    $f[]="## size to the same value. For example, to set";
    $f[]="## the heap to 4 GB, set:";
    $f[]="##";
    $f[]="## -Xms{$ElasticsearchMaxMemory}m";
    $f[]="## -Xmx{$ElasticsearchMaxMemory}m";
    $f[]="##";
    $f[]="## See https://www.elastic.co/guide/en/elasticsearch/reference/current/heap-size.html";
    $f[]="## for more information";
    $f[]="##";
    $f[]="################################################################";
    $f[]="";
    $f[]="# Xms represents the initial size of total heap space";
    $f[]="# Xmx represents the maximum size of total heap space";
    $f[]="";
    $f[]="-Xms{$ElasticsearchMaxMemory}m";
    $f[]="-Xmx{$ElasticsearchMaxMemory}m";
    $f[]="";
    $f[]="################################################################";
    $f[]="## Expert settings";
    $f[]="################################################################";
    $f[]="##";
    $f[]="## All settings below this section are considered";
    $f[]="## expert settings. Don't tamper with them unless";
    $f[]="## you understand what you are doing";
    $f[]="##";
    $f[]="################################################################";
    $f[]="";
    $f[]="## GC configuration";
    $f[]="#-XX:+UseConcMarkSweepGC";
    $f[]="#-XX:CMSInitiatingOccupancyFraction=75";
    $f[]="#-XX:+UseCMSInitiatingOccupancyOnly";
    $f[]="";
    $f[]="## G1GC Configuration";
    $f[]="# NOTE: G1GC is only supported on JDK version 10 or later.";
    $f[]="# To use G1GC uncomment the lines below.";
    $f[]="# 10-:-XX:-UseConcMarkSweepGC";
    $f[]="# 10-:-XX:-UseCMSInitiatingOccupancyOnly";
    $f[]="# 10-:-XX:+UseG1GC";
    $f[]="# 10-:-XX:InitiatingHeapOccupancyPercent=75";
    $f[]="";
    $f[]="## DNS cache policy";
    $f[]="# cache ttl in seconds for positive DNS lookups noting that this overrides the";
    $f[]="# JDK security property networkaddress.cache.ttl; set to -1 to cache forever";
    $f[]="-Des.networkaddress.cache.ttl=60";
    $f[]="# cache ttl in seconds for negative DNS lookups noting that this overrides the";
    $f[]="# JDK security property networkaddress.cache.negative ttl; set to -1 to cache";
    $f[]="# forever";
    $f[]="-Des.networkaddress.cache.negative.ttl=10";
    $f[]="";
    $f[]="## optimizations";
    $f[]="";
    $f[]="# pre-touch memory pages used by the JVM during initialization";
    $f[]="-XX:+AlwaysPreTouch";
    $f[]="";
    $f[]="## basic";
    $f[]="";
    $f[]="# explicitly set the stack size";
    $f[]="-Xss1m";
    $f[]="";
    $f[]="# set to headless, just in case";
    $f[]="-Djava.awt.headless=true";
    $f[]="";
    $f[]="# ensure UTF-8 encoding by default (e.g. filenames)";
    $f[]="-Dfile.encoding=UTF-8";
    $f[]="";
    $f[]="# use our provided JNA always versus the system one";
    $f[]="-Djna.nosys=true";
    $f[]="";
    $f[]="# turn off a JDK optimization that throws away stack traces for common";
    $f[]="# exceptions because stack traces are important for debugging";
    $f[]="-XX:-OmitStackTraceInFastThrow";
    $f[]="";
    $f[]="# flags to configure Netty";
    $f[]="-Dio.netty.noUnsafe=true";
    $f[]="-Dio.netty.noKeySetOptimization=true";
    $f[]="-Dio.netty.recycler.maxCapacityPerThread=0";
    $f[]="";
    $f[]="# log4j 2";
    $f[]="-Dlog4j.shutdownHookEnabled=false";
    $f[]="-Dlog4j2.disable.jmx=true";
    $f[]="";
    $f[]="-Djava.io.tmpdir=\${ES_TMPDIR}";
    $f[]="";
    $f[]="## heap dumps";
    $f[]="";
    $f[]="# generate a heap dump when an allocation from the Java heap fails";
    $f[]="# heap dumps are created in the working directory of the JVM";
    $f[]="-XX:+HeapDumpOnOutOfMemoryError";
    $f[]="";
    $f[]="# specify an alternative path for heap dumps; ensure the directory exists and";
    $f[]="# has sufficient space";
    $f[]="-XX:HeapDumpPath=/var/lib/elasticsearch";
    $f[]="";
    $f[]="# specify an alternative path for JVM fatal error logs";
    $f[]="-XX:ErrorFile=/var/log/elasticsearch/hs_err_pid%p.log";
    $f[]="";
    $f[]="## JDK 8 GC logging";
    $f[]="";
    $f[]="8:-XX:+PrintGCDetails";
    $f[]="8:-XX:+PrintGCDateStamps";
    $f[]="8:-XX:+PrintTenuringDistribution";
    $f[]="8:-XX:+PrintGCApplicationStoppedTime";
    $f[]="8:-Xloggc:/var/log/elasticsearch/gc.log";
    $f[]="8:-XX:+UseGCLogFileRotation";
    $f[]="8:-XX:NumberOfGCLogFiles=32";
    $f[]="8:-XX:GCLogFileSize=64m";
    $f[]="";
    $f[]="# JDK 9+ GC logging";
    $f[]="9-:-Xlog:gc*,gc+age=trace,safepoint:file=/var/log/elasticsearch/gc.log:utctime,pid,tags:filecount=32,filesize=64m";
    $f[]="# due to internationalization enhancements in JDK 9 Elasticsearch need to set the provider to COMPAT otherwise";
    $f[]="# time/date parsing will break in an incompatible way for some date patterns and locals";
    $f[]="9-:-Djava.locale.providers=COMPAT\n";
	@file_put_contents("/etc/elasticsearch/jvm.options", @implode("\n", $f));


    $f=array();
    $f[]="################################";
    $f[]="# Elasticsearch";
    $f[]="################################";
    $f[]="";
    $f[]="# Elasticsearch home directory";
    $f[]="ES_HOME=/usr/share/elasticsearch";
    $f[]="";
    $f[]="# Elasticsearch Java path";
    if(is_file("/usr/share/elasticsearch/jdk/bin/java")) {
        $f[] = "ES_JAVA_HOME=/usr/share/elasticsearch/jdk";
    }
    $f[]="";
    $f[]="# Elasticsearch configuration directory";
    $f[]="ES_PATH_CONF=/etc/elasticsearch";
    $f[]="";
    $f[]="# Elasticsearch PID directory";
    $f[]="PID_DIR=/var/run/elasticsearch";
    $f[]="";
    $f[]="# Additional Java OPTS";
    $f[]="#ES_JAVA_OPTS=";
    $f[]="";
    $f[]="# Configure restart on package upgrade (true, every other setting will lead to not restarting)";
    $f[]="RESTART_ON_UPGRADE=true";
    $f[]="";
    $f[]="################################";
    $f[]="# Elasticsearch service";
    $f[]="################################";
    $f[]="";
    $f[]="# SysV init.d";
    $f[]="#";
    $f[]="# The number of seconds to wait before checking if Elasticsearch started successfully as a daemon process";
    $f[]="ES_STARTUP_SLEEP_TIME=5";
    $f[]="";
    $f[]="################################";
    $f[]="# System properties";
    $f[]="################################";
    $f[]="";
    $f[]="# Specifies the maximum file descriptor number that can be opened by this process";
    $f[]="# When using Systemd, this setting is ignored and the LimitNOFILE defined in";
    $f[]="# /usr/lib/systemd/system/elasticsearch.service takes precedence";
    $f[]="#MAX_OPEN_FILES=65535";
    $f[]="";
    $f[]="# The maximum number of bytes of memory that may be locked into RAM";
    $f[]="# Set to \"unlimited\" if you use the 'bootstrap.memory_lock: true' option";
    $f[]="# in elasticsearch.yml.";
    $f[]="# When using systemd, LimitMEMLOCK must be set in a unit file such as";
    $f[]="# /etc/systemd/system/elasticsearch.service.d/override.conf.";
    $f[]="MAX_LOCKED_MEMORY=unlimited";
    $f[]="";
    $f[]="# Maximum number of VMA (Virtual Memory Areas) a process can own";
    $f[]="# When using Systemd, this setting is ignored and the 'vm.max_map_count'";
    $f[]="# property is set at boot time in /usr/lib/sysctl.d/elasticsearch.conf";
    $f[]="#MAX_MAP_COUNT=262144";

    @file_put_contents("/etc/default/elasticsearch",@implode("\n",$f));
   ApplySecurities();
	
}

function remove_proxydb(){
	$el=new elasticsearch();
	$el->remove_database("proxy");
}

function put_mapping(){
	$el=new elasticsearch();
	$el->mapping_proxy();	
}

function create_pipeline(){
	$el=new elasticsearch();
	$el->create_pipeline();	
}

function import_dashboard(){
	$el=new elasticsearch();
	$el->import_dashboard();	
}

function get_version(){
	$el=new elasticsearch();
	$ver = $el->GetVersion();
}