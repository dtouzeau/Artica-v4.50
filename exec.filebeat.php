<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=="--setup"){setup_template();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--clean"){cleandirs();exit;}
if($argv[1]=="--stats"){filebeat_stats();exit;}
if($argv[1]=="--restart"){filebeat_restart();exit;}
if($argv[1]=="--cloud-install"){Cloud_install();exit;}
if($argv[1]=="--cloud-uninstall"){Cloud_uninstall();exit;}

function GET_PID(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/filebeat.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $binpath="/usr/share/filebeat/bin/filebeat";
    return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}


function filebeat_restart(){
    $unix=new unix();
    build_progress_restart("{reconfiguring}...",10);
    build();
    $pid=GET_PID();
    if($unix->process_exists($pid)){
        build_progress_restart("{reloading}...",15);
        system("/etc/init.d/filebeat force-reload");
    }else{
        build_progress_restart("{starting}...",15);
        system("/etc/init.d/filebeat start");
    }

    $c=15;
    for($i=0;$i<6;$i++){
        $c++;
        build_progress_restart("{waiting}...",$c);
        sleep(1);
        $pid=GET_PID();
        if($unix->process_exists($pid)){break;}
    }
    $pid=GET_PID();
    if($unix->process_exists($pid)){
        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
        $filebeat = $unix->find_program("filebeat");
        if ($SQUIDEnable == 1) {
            shell_exec("$filebeat setup --pipelines --modules squid -M \"squid.proxy.enabled=true\"");
        }
        if ($EnableUfdbGuard ==1) {
            shell_exec("$filebeat setup --pipelines --modules squid -M \"squid.ufdb.enabled=true\"");
        }
        build_progress_restart("{success}...",100);
    }else{
        build_progress_restart("{failed}...",110);
    }

}

function Cloud_install(){
    $unix=new unix();
    build_progress(10,"{installing}");

    if(is_file("/etc/init.d/squid")){
        build_progress(50,"{installing}");
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
    build_progress(100,"{installing} {success}");
}

function Cloud_uninstall(){
    $unix=new unix();
    build_progress(10,"{uninstalling}");

    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==1){
        build_progress(110,"{uninstalling} {failed} {APP_UFDBCAT} installed");
        return false;
    }


    if(is_file("/etc/init.d/squid")){
        build_progress(50,"{uninstalling}");
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
    build_progress(100,"{uninstalling} {success}");
}


function install(){

    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFileBeat",1);
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress(10,"{creating_service}");
    filebeat_service();

    $unix->Popuplate_cron_make("filebeat-clean","*/30 * * * *",basename(__FILE__)." --clean");
    $unix->Popuplate_cron_make("filebeat-stats","*/3 * * * *",basename(__FILE__)." --stats");
    system("/etc/init.d/cron reload");

    build_progress(20,"{creating_service}");
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    echo "Proxy Enabled.....: $SQUIDEnable\n";
    if($SQUIDEnable==1){
        echo "Update Proxy configuration...\n";
        build_progress(25,"{creating_service}");
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
    }
    build();
    build_monit();
    system("/etc/init.d/filebeat start");
    build_progress(100,"{installing} {APP_FILEBEAT} {success}");

}


function build_monit(){

    $f[]="check process APP_FILEBEAT with pidfile /var/run/filebeat.pid";
    $f[]="\tstart program = \"/etc/init.d/filebeat start\"";
    $f[]="\tstop program = \"/etc/init.d/filebeat stop\"";

    $f[]="\tif failed host 127.0.0.1 port 5066 then restart";
    $f[]="";

    @file_put_contents("/etc/monit/conf.d/APP_FILEBEAT.monitrc", @implode("\n", $f));
    if(!is_file("/etc/monit/conf.d/APP_FILEBEAT.monitrc")){
        echo "/etc/monit/conf.d/APP_FILEBEAT.monitrc failed !!!\n";
    }
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

}

function uninstall(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFileBeat",0);
    build_progress(10,"{uninstall}");
    remove_service("/etc/init.d/filebeat");

    if(is_file("/etc/cron.d/filebeat-clean")){
        @unlink("/etc/cron.d/filebeat-clean");
    }
    if(is_file("/etc/cron.d/filebeat-stats")){
        @unlink("/etc/cron.d/filebeat-stats");
    }

    build_progress(50,"{uninstall}");
    if(is_file("/etc/monit/conf.d/APP_FILEBEAT.monitrc")){
        @unlink("/etc/monit/conf.d/APP_FILEBEAT.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    }

    build_progress(100,"{uninstall} {done}");

}


function cleandirs(){
    $unix=new unix();

    $Files=$unix->DirFiles("/var/log/filebeat","\.[0-9]+$");

    foreach ($Files as $filename=>$none){
        @unlink("/var/log/filebeat/$filename");

    }


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

function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/web/filebeat.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function build_progress_restart($pourc,$text){

    if(is_numeric($text)){$text2=$pourc;$pourc=$text;$text=$text2;}

    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/web/filebeat.restart.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function setup_template(){
    $unix=new unix();
    build();
    $filebeat=$unix->find_program("filebeat");
    shell_exec("$filebeat setup --template");

}


function build(){


    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchRemotePort"));

    $EnableElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));
    if($ElasticSearchAddress==null){$ElasticSearchAddress="127.0.0.1";}
    if($EnableElasticSearch==1){
        $ElasticSearchAddress="127.0.0.1";
        $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    }

    if($ElasticSearchAddress==null){
        @unlink("/etc/artica-postfix/elasticsearch_remote_configured");
    }

    @touch("/etc/artica-postfix/elasticsearch_remote_configured");

    if($ElasticsearchRemotePort==0){$ElasticsearchRemotePort=9200;}
    $ElasticSearchProtocol=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchProtocol"));
    if(empty($ElasticSearchProtocol)){$ElasticSearchProtocol='http';}
    $ElasticsearchEnableAuthFilebeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchEnableAuthFilebeat"));
    $ElasticSearchUsernameFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchUsernameFilebeat"));
    $ElasticSearchPasswordFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchPasswordFilebeat"));
    echo "ElasticSearch database.....: $ElasticSearchAddress:$ElasticsearchRemotePort\n";

    $unix=new unix();
    $chown=$unix->find_program("chown");
    shell_exec("$chown -R root:root /etc/filebeat");
    shell_exec("find /etc/filebeat -type d -exec chmod 755 {} +");
    shell_exec("find /etc/filebeat -type f -exec chmod 600 {} +");
    system("$chown -R root:root /usr/share/filebeat/module");
    $conf[]="filebeat.inputs:";
    $conf[]="- type: log";
    $conf[]="  enabled: false";
    $conf[]="  paths:";
    $conf[]="    - /var/log/*.log";
    $conf[]="  #exclude_lines: ['^DBG']";
    $conf[]="  #include_lines: ['^ERR', '^WARN']";
    $conf[]="  #exclude_files: ['.gz\$']";
    $conf[]="  #multiline.pattern: ^\[";
    $conf[]="  #multiline.negate: false";
    $conf[]="  #multiline.match: after";
    $conf[]="";
    $conf[]="";
    $conf[]="filebeat.config.inputs:";
    $conf[]="  enabled: true";
    $conf[]="  path: \${path.config}/configs.d/*.yml";
    $conf[]="";
    $conf[]="";
    $conf[]="filebeat.config.modules:";
    $conf[]="  path: \${path.config}/modules.d/*.yml";
    $conf[]="  reload.enabled: false";
    $conf[]="  #reload.period: 10s";
    $conf[]="";
    $conf[]="";
    $conf[]="setup.template.settings:";
    $conf[]="  index.number_of_shards: 1";
    $conf[]="  index.number_of_replicas: 0";
    $conf[]="  #index.codec: best_compression";
    $conf[]="  #_source.enabled: false";
    $conf[]="";
    $conf[]="setup.kibana:";
    $conf[]="  #host: \"127.0.0.1:5601\"";
    $conf[]="  #space.id:";
    $conf[]="  #enabled: false";
    $conf[]="";
    $conf[]="";
    $conf[]="setup.ilm.enabled: false";
    $conf[]="output.elasticsearch:";
    $conf[]="  hosts: [\"{$ElasticSearchAddress}:{$ElasticsearchRemotePort}\"]";
    $conf[]="  protocol: \"{$ElasticSearchProtocol}\"";
    if($ElasticsearchEnableAuthFilebeat==0) {
        $conf[] = "  #username: \"elastic\"";
        $conf[] = "  #password: \"changeme\"";
    }
    if($ElasticsearchEnableAuthFilebeat==1) {
        $conf[] = "  username: \"{$ElasticSearchUsernameFilebeat}\"";
        $conf[] = "  password: \"{$ElasticSearchPasswordFilebeat}\"";
    }
    $conf[]="  worker: 1";
    $conf[]="  index: artica-%{[fileset.name]}-%{+yyyy}";
    $conf[]="  allow_older_versions: true";
    $conf[]="";
    $conf[]="processors:";
    $conf[]="  - add_host_metadata: ~";
    $conf[]="  - add_cloud_metadata: ~";
    $conf[]="";
    $conf[]="";
    //$conf[]="setup.template.enabled: false";
    $conf[]="setup.template.name: \"artica\"";
    $conf[]="setup.template.pattern: \"artica-*\"";
    // $conf[]="setup.dashboards.enabled: true";
    // $conf[]="setup.dashboards.directory: \"/usr/share/filebeat/module/squid/_meta/kibana/\"";
    // $conf[]="setup.dashboards.always_kibana: true";
    // $conf[]="setup.dashboards.retry.enabled: true";
    // $conf[]="setup.dashboards.index: artica-*";
    // $conf[]="setup.dashboards.beat: artica";
    #$conf[]="setup.template.overwrite: false";
    #$conf[]="setup.template.fields: \"/usr/share/filebeat/module/squid/proxy/_meta/fields.yml\"";
    $conf[]="migration.6_to_7.enabled: true\n";
    $conf[]="";


    $conf[]="";
    $conf[]="http.enabled: true";
    $conf[]="http.host: 127.0.0.1";
    $conf[]="http.port: 5066";
    $conf[]="";
    $conf[]="";
    @file_put_contents("/etc/filebeat/filebeat.yml",@implode("\n",$conf));

    echo "/etc/filebeat/filebeat.yml done\n";
    shell_exec("$chown root:root /etc/filebeat/filebeat.yml");
    @chmod("/etc/filebeat/filebeat.yml",0644);
    filebeat_postfix();
    filebeat_squid();
    filebeat_haproxy();
    filebeat_nginx();

}

function filebeat_service(){
    $unix=new unix();
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $php=$unix->LOCATE_PHP5_BIN();
    $ElasticsearchEnableAuthFilebeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchEnableAuthFilebeat"));
    $ElasticSearchUsernameFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchUsernameFilebeat"));
    $ElasticSearchPasswordFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchPasswordFilebeat"));
    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    if($ElasticSearchAddress==null){$ElasticSearchAddress="127.0.0.1";}

    $INIT_FILE="/etc/init.d/filebeat";

    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          filebeat";
    $f[]="# Required-Start:    \$local_fs \$network \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$network \$syslog";
    $f[]="# Default-Start:     2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Filebeat sends log files to Logstash or directly to Elasticsearch.";
    $f[]="# Description:       Filebeat is a shipper part of the Elastic Beats";
    $f[]="#                    family. Please see: https://www.elastic.co/products/beats";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="# Do NOT \"set -e\"";
    $f[]="";
    $f[]="# PATH should only include /usr/* if it runs after the mountnfs.sh script";
    $f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
    $f[]="DESC=\"Filebeat sends log files to Logstash or directly to Elasticsearch.\"";
    $f[]="NAME=\"filebeat\"";
    $f[]="DAEMON=/usr/share/\${NAME}/bin/\${NAME}";
    $f[]="DAEMON_ARGS=\"-c /etc/\${NAME}/\${NAME}.yml -path.home /usr/share/\${NAME} -path.config /etc/\${NAME} -path.data /var/lib/\${NAME} -path.logs /var/log/\${NAME}\"";
    $f[]="TEST_ARGS=\"-e test config\"";
    $f[]="PIDFILE=/var/run/filebeat.pid";
    $f[]="WRAPPER=\"/usr/share/\${NAME}/bin/\${NAME}-god\"";
    $f[]="BEAT_USER=\"root\"";
    $f[]="WRAPPER_ARGS=\"-r / -n -p \$PIDFILE\"";
    $f[]="SCRIPTNAME=/etc/init.d/filebeat";
    $f[]="";
    $f[]="# Exit if the package is not installed";
    $f[]="[ -x \"\$DAEMON\" ] || exit 0";


    $f[]="";
    $f[]="# Read configuration variable file if it is present";
    $f[]="[ -r /etc/default/filebeat ] && . /etc/default/filebeat";
    $f[]="";
    $f[]="[ \"\$BEAT_USER\" != \"root\" ] && WRAPPER_ARGS=\"\$WRAPPER_ARGS -u \$BEAT_USER\"";
    $f[]="USER_WRAPPER=\"su\"";
    $f[]="USER_WRAPPER_ARGS=\"\$BEAT_USER -c\"";
    $f[]="";
    $f[]="if command -v runuser >/dev/null 2>&1; then";
    $f[]="    USER_WRAPPER=\"runuser\"";
    $f[]="fi";
    $f[]="";
    $f[]="# Load the VERBOSE setting and other rcS variables";
    $f[]=". /lib/init/vars.sh";
    $f[]="";
    $f[]="# Define LSB log_* functions.";
    $f[]="# Depend on lsb-base (>= 3.2-14) to ensure that this file is present";
    $f[]="# and status_of_proc is working.";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";

    $f[]="if [ ! -f /etc/artica-postfix/elasticsearch_remote_configured ]; then";
    $f[]="\tlog_daemon_msg \"Could not start \$DESC\" \"\$NAME\"";
    $f[]="\tlog_daemon_msg \"Not configured \$DESC\" \"\$NAME\"";
    $f[]="\tlog_end_msg 0";
    $f[]="\texit 0";
    $f[]="fi";
    $f[]="#";
    $f[]="# Function that calls runs the service in foreground";
    $f[]="# to test its configuration.";
    $f[]="#";
    $f[]="do_test()";
    $f[]="{";
    $f[]="        \$USER_WRAPPER \$USER_WRAPPER_ARGS \"\$DAEMON \$DAEMON_ARGS \$TEST_ARGS\"";
    $f[]="}";
    $f[]="";
    $f[]="#";
    $f[]="# Function that starts the daemon/service";
    $f[]="#";
    $f[]="do_start()";
    $f[]="{";
    $f[]="	# Return";
    $f[]="	#   0 if daemon has been started";
    $f[]="	#   1 if daemon was already running";
    $f[]="	#   2 if daemon could not be started";
    $f[]="	start-stop-daemon --start --pidfile \$PIDFILE --exec \$WRAPPER -- \$WRAPPER_ARGS -- \$DAEMON \$DAEMON_ARGS || return 2";
    if ($SQUIDEnable == 1) {
        if($ElasticsearchEnableAuthFilebeat==0) {
            $f[] = "curl -X POST -H 'Content-Type: application/json' -H 'kbn-xsrf: true' -d @/usr/share/artica-postfix/ressources/proxy.json http://$ElasticSearchAddress:5601/api/kibana/dashboards/import?force=true";
        }
        if($ElasticsearchEnableAuthFilebeat==1) {
            $f[] = "curl -u $ElasticSearchUsernameFilebeat:$ElasticSearchPasswordFilebeat -X POST -H 'Content-Type: application/json' -H 'kbn-xsrf: true' -d @/usr/share/artica-postfix/ressources/proxy.json http://$ElasticSearchAddress:5601/api/kibana/dashboards/import?force=true";
        }
    }
    if ($EnableUfdbGuard == 1) {
        if($ElasticsearchEnableAuthFilebeat==0) {
            $f[]="curl -X POST -H 'Content-Type: application/json' -H 'kbn-xsrf: true' -d @/usr/share/artica-postfix/ressources/ufdb.json http://$ElasticSearchAddress:5601/api/kibana/dashboards/import?force=true";
        }
        if($ElasticsearchEnableAuthFilebeat==1) {
            $f[] = "curl -u $ElasticSearchUsernameFilebeat:$ElasticSearchPasswordFilebeat -X POST -H 'Content-Type: application/json' -H 'kbn-xsrf: true' -d @/usr/share/artica-postfix/ressources/ufdb.json http://$ElasticSearchAddress:5601/api/kibana/dashboards/import?force=true";
        }




    }
    $f[]="}";
    $f[]="";
    $f[]="#";
    $f[]="# Function that stops the daemon/service";
    $f[]="#";
    $f[]="do_stop()";
    $f[]="{";
    $f[]="	# Return";
    $f[]="	#   0 if daemon has been stopped";
    $f[]="	#   1 if daemon was already stopped";
    $f[]="	#   2 if daemon could not be stopped";
    $f[]="	#   other if a failure occurred";
    $f[]="	start-stop-daemon --stop --quiet --retry=TERM/5/KILL/5 --pidfile \$PIDFILE --exec \$WRAPPER";
    $f[]="	RETVAL=\"\$?\"";
    $f[]="	[ \"\$RETVAL\" = 2 ] && return 2";
    $f[]="	# Wait for children to finish too if this is a daemon that forks";
    $f[]="	# and if the daemon is only ever run from this initscript.";
    $f[]="	# If the above conditions are not satisfied then add some other code";
    $f[]="	# that waits for the process to drop all resources that could be";
    $f[]="	# needed by services started subsequently.  A last resort is to";
    $f[]="	# sleep for some time.";
    $f[]="	start-stop-daemon --stop --quiet --oknodo --retry=0/30/KILL/5 --exec \$DAEMON";
    $f[]="	[ \"\$?\" = 2 ] && return 2";
    $f[]="	# Many daemons don't delete their pidfiles when they exit.";
    $f[]="	rm -f \$PIDFILE";
    $f[]="	return \"\$RETVAL\"";
    $f[]="}";
    $f[]="";
    $f[]="#";
    $f[]="# Function that sends a SIGHUP to the daemon/service";
    $f[]="#";
    $f[]="do_reload() {";
    $f[]="	#";
    $f[]="	# If the daemon can reload its configuration without";
    $f[]="	# restarting (for example, when it is sent a SIGHUP),";
    $f[]="	# then implement that here.";
    $f[]="	#";
    $f[]="  $php ".__FILE__." --build";
    $f[]="	start-stop-daemon --stop --signal 1 --quiet --pidfile \$PIDFILE --exec \$DAEMON";
    $f[]="	return 0";
    $f[]="}";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";

    $f[]="	[ \"\$VERBOSE\" != no ] && log_daemon_msg \"Starting \$DESC\" \"\$NAME\"";
    $f[]="        do_test";
    $f[]="        case \"\$?\" in";
    $f[]="                0) ;;";
    $f[]="                *)";
    $f[]="                        log_end_msg 1";
    $f[]="                        exit 1";
    $f[]="                        ;;";
    $f[]="        esac";
    $f[]="	do_start";
    $f[]="	case \"\$?\" in";
    $f[]="		0|1) [ \"\$VERBOSE\" != no ] && log_end_msg 0 ;;";
    $f[]="		2) [ \"\$VERBOSE\" != no ] && log_end_msg 1 ;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="  stop)";
    $f[]="	[ \"\$VERBOSE\" != no ] && log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
    $f[]="	do_stop";
    $f[]="	case \"\$?\" in";
    $f[]="		0|1) [ \"\$VERBOSE\" != no ] && log_end_msg 0 ;;";
    $f[]="		2) [ \"\$VERBOSE\" != no ] && log_end_msg 1 ;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="  status)";
    $f[]="       status_of_proc \"\$WRAPPER\" \"\$NAME\" && exit 0 || exit \$?";
    $f[]="       ;;";
    $f[]="  #reload|force-reload)";
    $f[]="	#";
    $f[]="	# If do_reload() is not implemented then leave this commented out";
    $f[]="	# and leave 'force-reload' as an alias for 'restart'.";
    $f[]="	#";
    $f[]="	#log_daemon_msg \"Reloading \$DESC\" \"\$NAME\"";
    $f[]="	#do_reload";
    $f[]="	#log_end_msg \$?";
    $f[]="	#;;";
    $f[]="  restart|force-reload)";
    $f[]="	#";
    $f[]="	# If the \"reload\" option is implemented then remove the";
    $f[]="	# 'force-reload' alias";
    $f[]="	#";
    $f[]="  $php ".__FILE__." --build";
    $f[]="	log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
    $f[]="        do_test";
    $f[]="        case \"\$?\" in";
    $f[]="                0) ;;";
    $f[]="                *)";
    $f[]="                        log_end_msg 1  # Old process is still running";
    $f[]="                        exit 1";
    $f[]="                        ;;";
    $f[]="        esac";
    $f[]="";
    $f[]="	do_stop";
    $f[]="	case \"\$?\" in";
    $f[]="	  0|1)";
    $f[]="		do_start";
    $f[]="		case \"\$?\" in";
    $f[]="			0) log_end_msg 0 ;;";
    $f[]="			1) log_end_msg 1 ;; # Old process is still running";
    $f[]="			*) log_end_msg 1 ;; # Failed to start";
    $f[]="		esac";
    $f[]="		;;";
    $f[]="	  *)";
    $f[]="	  	# Failed to stop";
    $f[]="		log_end_msg 1";
    $f[]="		;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="  *)";
    $f[]="	#echo \"Usage: \$SCRIPTNAME {start|stop|restart|reload|force-reload}\" >&2";
    $f[]="	echo \"Usage: \$SCRIPTNAME {start|stop|status|restart|force-reload}\" >&2";
    $f[]="	exit 3";
    $f[]="	;;";
    $f[]="esac";
    $f[]="";
    $f[]=":";

    $chmod=$unix->find_program("chmod");
    @file_put_contents($INIT_FILE,@implode("\n",$f));
    $debianbin=$unix->find_program("update-rc.d");
    $redhatbin=$unix->find_program("chkconfig");

    shell_exec("$chmod +x $INIT_FILE >/dev/null 2>&1");
    if(is_file($debianbin)){
        shell_exec("$debianbin -f ".basename($INIT_FILE)." defaults >/dev/null 2>&1");
        return;
    }
    if(is_file($redhatbin)){
        shell_exec("$redhatbin --add ".basename($INIT_FILE)." >/dev/null 2>&1");
        shell_exec("$redhatbin --level 2345 ".basename($INIT_FILE)." on >/dev/null 2>&1");
    }
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: ".basename($INIT_FILE)." success...\n";}

}

function filebeat_squid(){
    $unix=new unix();
    $filebeat=$unix->find_program("filebeat");
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    if($SQUIDEnable==0){
        if(is_file("/etc/filebeat/modules.d/squid.yml")){
            shell_exec("$filebeat modules disable squid");
        }
        echo "Squid, disabled\n";
        return;
    }


    if(!is_file("/etc/filebeat/modules.d/squid.yml")){
        shell_exec("$filebeat modules enable squid");
        echo "Squid, Enabled\n";
    }


    $f=array();
    $f[]="# Module: squid";
    $f[]="- module: squid";
    $f[]="  proxy:";
    $f[]="      enabled: true";
    if($EnableUfdbGuard==1){
        $f[]="  ufdb:";
        $f[]="      enabled: true";
    }else{
        $f[]="  ufdb:";
        $f[]="      enabled: false";
    }
    $f[]="";


    echo "/etc/filebeat/modules.d/squid.yml OK\n";
    @file_put_contents("/etc/filebeat/modules.d/squid.yml",@implode("\n",$f));

    $f=array();

    $f[]="module_version: 1.0";
    $f[]="";
    $f[]="var:";
    $f[]="  - name: paths";
    $f[]="    default:";
    $f[]="      - /var/log/squid/ufdbguardd.log*";
    $f[]="ingest_pipeline: ingest/pipeline.json";
    $f[]="input: config/ufdb.yml\n";

    echo "/usr/share/filebeat/module/squid/ufdb/manifest.yml OK";
    @file_put_contents("/usr/share/filebeat/module/squid/ufdb/manifest.yml",@implode("\n",$f));


}

function filebeat_haproxy(){

    $unix=new unix();
    $filebeat=$unix->find_program("filebeat");
    $EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));
    $chmod=$unix->find_program("chmod");
    shell_exec("$chmod go-w /usr/share/filebeat/module/haproxy/log/manifest.yml");

    if($EnableHaProxy==0){
        if(is_file("/etc/filebeat/modules.d/haproxy.yml")){
            shell_exec("$filebeat modules disable haproxy");
        }
        echo "haproxy, disabled\n";
        return;
    }


    if(!is_file("/etc/filebeat/modules.d/haproxy.yml")){
        shell_exec("$filebeat modules enable haproxy");
    }

    $f=array();
    $f[]="# Module: haproxy";
    $f[]="- module: haproxy";
    $f[]="  log:";
    $f[]="      enabled: true";
    $f[]="      var.input: \"file\"";
    $f[]="      var.paths: [\"/var/log/haproxy.log\"]";
    $f[]="";


    echo "/etc/filebeat/modules.d/haproxy.yml OK\n";
    @file_put_contents("/etc/filebeat/modules.d/haproxy.yml",@implode("\n",$f));


}

function filebeat_nginx(){

    $unix=new unix();
    $filebeat=$unix->find_program("filebeat");
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $chmod=$unix->find_program("chmod");
    shell_exec("$chmod go-w /usr/share/filebeat/module/nginx/error/manifest.yml");
    shell_exec("$chmod go-w /usr/share/filebeat/module/nginx/access/manifest.yml");
    if($EnableNginx==0){
        if(is_file("/etc/filebeat/modules.d/nginx.yml")){
            shell_exec("$filebeat modules disable nginx");
        }
        echo "nginx, disabled\n";
        return;
    }


    if(!is_file("/etc/filebeat/modules.d/nginx.yml")){
        shell_exec("$filebeat modules enable nginx");
    }

    $f=array();
    $f[]="# Module: nginx";
    $f[]="- module: nginx";
    $f[]="  access:";
    $f[]="      var.paths: [\"/var/log/nginx/realtime/filebeat.log\"]";
    $f[]="";
    $f[]="  error:";
    $f[]="      var.paths: [\"/var/log/nginx/error.log\"]";
    $f[]="";


    echo "/etc/filebeat/modules.d/nginx.yml OK\n";
    @file_put_contents("/etc/filebeat/modules.d/nginx.yml",@implode("\n",$f));


}

function filebeat_postfix(){

    $unix=new unix();
    $filebeat=$unix->find_program("filebeat");
    $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    if($EnablePostfix==0){
        if(is_file("/etc/filebeat/modules.d/postfix.yml")){
            shell_exec("$filebeat modules disable postfix");
        }
        echo "Postfix, disabled\n";
        return;
    }


    if(!is_file("/etc/filebeat/modules.d/postfix.yml")){
        shell_exec("$filebeat modules enable postfix");
    }

    $f=array();
    $f[]="# Module: postfix";
    $f[]="- module: postfix";
    $f[]="  mail:";
    $f[]="      enabled: true";
    $f[]="";


    echo "/etc/filebeat/modules.d/postfix.yml OK\n";
    @file_put_contents("/etc/filebeat/modules.d/postfix.yml",@implode("\n",$f));


}

function filebeat_stats(){

    $ch = curl_init();
    $method = "GET";
    $url = "http://127.0.0.1:5066/stats?pretty";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);

    if ($result === false) {
        $Error=curl_error($ch);
        squid_admin_mysql(1,"Filebeat stats return network error $Error",null,__FILE__,__LINE__);
        curl_close($ch);
        return;
    }

    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($responseCode >= 400) {
        squid_admin_mysql(1,"Filebeat stats return HTTP error $responseCode",null,__FILE__,__LINE__);
        curl_close($ch);
        return;
    }

    curl_close($ch);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FILEBEAT_STATS",$result);





}

// suppression des files  d'attentes
// rm /var/lib/filebeat/registry/filebeat/data.json