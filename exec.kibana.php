<?php
if (is_file("/usr/bin/cgclassify")) {if (is_dir("/cgroups/blkio/php")) {shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php " . getmypid());}}
include_once dirname(__FILE__) . '/framework/class.unix.inc';if (!isset($GLOBALS["CLASS_SOCKETS"])) {if (!class_exists("sockets")) {include_once "/usr/share/artica-postfix/ressources/class.sockets.inc";}
    $GLOBALS["CLASS_SOCKETS"] = new sockets();}
include_once dirname(__FILE__) . '/framework/frame.class.inc';
include_once dirname(__FILE__) . '/ressources/class.elasticssearch.inc';
if (preg_match("#schedule-id=([0-9]+)#", implode(" ", $argv), $re)) {$GLOBALS["SCHEDULE_ID"] = $re[1];}
if (preg_match("#--verbose#", implode(" ", $argv))) {$GLOBALS["VERBOSE"] = true;}
if (preg_match("#--force#", implode(" ", $argv))) {$GLOBALS["FORCE"] = true;}
$GLOBALS["OUTPUT"] = true;
if (!isset($GLOBALS["CLASS_SOCKETS"])) {if (!class_exists("sockets")) {include_once "/usr/share/artica-postfix/ressources/class.sockets.inc";}
    $GLOBALS["CLASS_SOCKETS"] = new sockets();}if (function_exists("posix_getuid")) {if (posix_getuid() != 0) {die("Cannot be used in web server mode\n\n");}}
if (preg_match("#--verbose#", implode(" ", $argv))) {$GLOBALS["debug"] = true;
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('html_errors', 0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}
if (preg_match("#--verbose#", implode(" ", $argv))) {
    $GLOBALS["DEBUG"] = true;
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if ($argv[1] == "--install") {install();exit;}
if ($argv[1] == "--uninstall") {uninstall();exit;}
if ($argv[1] == "--start") {start();exit;}
if ($argv[1] == "--stop") {stop();exit;}
if ($argv[1] == "--monit") {build_monit();exit;}
if ($argv[1] == "--build") {build();exit;}
if ($argv[1] == "--create_service") {create_service();exit;}
if ($argv[1] == "--permissions") {permissions();exit;}


function build_progress($pourc, $text)
{
    $echotext = $text;
    echo "Starting......: " . date("H:i:s") . " {$pourc}% $echotext\n";
    $cachefile = PROGRESS_DIR."/kibana.progress";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}

function permissions(){
    $unix       = new unix();
    $chown      = $unix->find_program("chown");
    $addgroup   = $unix->find_program("addgroup");
    $adduser    = $unix->find_program("adduser");

    if(!$unix->SystemGroupExists("kibana")){
        echo "Starting......: " . date("H:i:s") . " Kibana group doesn't exists ( create it)\n";
        shell_exec("$addgroup --quiet --system \"kibana\"");
    }

    if(!$unix->SystemUserExists("kibana")){
        echo "Starting......: " . date("H:i:s") . " Kibana user doesn't exists ( create it)\n";
        shell_exec("$adduser --quiet --system --no-create-home --disabled-password --ingroup \"kibana\" --shell /bin/false \"kibana\"");
    }

    $dirs[]="/var/log/kibana";
    $dirs[]="/var/run/kibana";
    $dirs[]="/usr/share/kibana";
    $dirs[]="/var/lib/kibana";
    $dirs[]="/etc/kibana";



    foreach ($dirs as $directory){
        if(!is_dir($directory)){@mkdir($directory,0755,true);}
        @chmod($directory,0755);
        shell_exec("$chown -R kibana:kibana $directory");
    }

}

function install(){

    build_progress(10, "{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKibana", 1);
    build_progress(20, "{creating_service}");
    create_service();
    build_progress(30, "{configuring}");
    build();
    build_progress(40, "{configuring}");
    build_monit();
    build_progress(50, "{apply_permissions}");
    permissions();
    build_progress(70, "{starting_service}");
    system("/etc/init.d/kibana start");
    build_progress(100, "{installing} {done}");
}

function uninstall(){
    build_progress(10, "{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKibana",0);
    remove_service("/etc/init.d/kibana");
    build_progress(50, "{uninstalling}");
    if(is_file("/etc/monit/conf.d/APP_KIBANA.monitrc")){
        @unlink("/etc/monit/conf.d/APP_KIBANA.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    build_progress(100, "{done}");

}
function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function GET_PID()
{
    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/kibana/kibana.pid");
    if ($unix->process_exists($pid)) {return $pid;}
    return $unix->PIDOF_PATTERN("/usr/share/kibana/bin/.*?kibana.yml");

}

function start()
{
    $unix = new unix();
    $GLOBALS["TITLENAME"] = "Kibana";
    $pid = GET_PID();
    if ($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service already running pid $pid...\n";}
        return true;
    }
    if (!is_dir("/var/log/kibana")) {@mkdir("/var/log/kibana", 0755, true);}
    if (!is_dir("/var/run/kibana")) {@mkdir("/var/run/kibana", 0755, true);}
    $chmod = $unix->find_program("chmod");
    shell_exec("$chmod 0755 /var/log/kibana");
    shell_exec("$chmod 0755 /var/run/kibana");

    build();
    $nohup = $unix->find_program("nohup");
    if ($GLOBALS["OUTPUT"]) {echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} starting service\n";}
    @chdir("/usr/share/kibana");
    $cmd="/usr/share/kibana/bin/kibana serve -c /etc/kibana/kibana.yml >/var/log/kibana/kibana.log";
    system("$nohup $cmd 2>&1 &");
    @chdir("/root");
    $pid = GET_PID();
    for ($i = 0; $i < 5; $i++) {
        $pid = GET_PID();
        if ($unix->process_exists($pid)) {break;}
        if ($GLOBALS["OUTPUT"]) {echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service waiting $i/5...\n";}
        sleep(1);

    }

    $pid = GET_PID();
    if ($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service success\n";}
        return true;
    }
    if ($GLOBALS["OUTPUT"]) {echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} starting service FAILED\n\"$cmd\"\n";}
    return false;
}

function build_monit(){

    $f[] = "check process APP_KIBANA with pidfile /var/run/kibana/kibana.pid";
    $f[] = "\tstart program = \"/etc/init.d/kibana start\"";
    $f[] = "\tstop program = \"/etc/init.d/kibana stop\"";
    $f[] = "\tif 5 restarts within 5 cycles then timeout";
    $f[] = "\tif failed host 127.0.0.1 port 5601 protocol http then restart";
    $f[] = "";
    @file_put_contents("/etc/monit/conf.d/APP_KIBANA.monitrc", @implode("\n", $f));
    if (!is_file("/etc/monit/conf.d/APP_KIBANA.monitrc")) {
        echo "/etc/monit/conf.d/APP_KIBANA.monitrc failed !!!\n";
    }
    echo "Kibana: [INFO] Writing /etc/monit/conf.d/APP_KIBANA.monitrc\n";
    system('/etc/init.d/monit restart');
}

function stop()
{
    $unix = new unix();

    $pid = GET_PID();
    $GLOBALS["TITLENAME"] = "Kibana";

    if (!$unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
        return true;
    }
    $pid = GET_PID();

    if ($GLOBALS["OUTPUT"]) {echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
    $unix->KILL_PROCESS($pid, 15);
    for ($i = 0; $i < 5; $i++) {
        $pid = GET_PID();
        if (!$unix->process_exists($pid)) {break;}
        if ($GLOBALS["OUTPUT"]) {echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
        unix_system_kill($pid);
    }

    $pid = GET_PID();
    if (!$unix->process_exists($pid)) {stop_port();return true;}
    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = squid_logger_pid();
        if (!$unix->process_exists($pid)) {break;}
        if ($GLOBALS["OUTPUT"]) {echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
        unix_system_kill($pid);
    }

    $pid = squid_logger_pid();
    if (!$unix->process_exists($pid)) {stop_port();return true;}

    if ($GLOBALS["OUTPUT"]) {echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} failed...\n";}
}
function stop_port()
{

    $unix = new unix();
    $pid = $unix->PIDOF_BY_PORT(5601);
    if (!$unix->process_exists($pid)) {return;}
    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = $unix->PIDOF_BY_PORT(5601);
        if (!$unix->process_exists($pid)) {break;}
        if ($GLOBALS["OUTPUT"]) {echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service waiting stopped port:5601 :$pid $i/5...\n";}
        sleep(1);
        unix_system_kill($pid);
    }

}

function build()
{
    $unix                   = new unix();
    $hostname               = $unix->hostname_g();
    $ElasticsearchBindPort  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    $KibanaVersion          = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("KibanaVersion");

    if ($ElasticsearchBindPort == 0) {$ElasticsearchBindPort = 9200;}


    $f[] = "# Kibana is served by a back end server. This setting specifies the port to use.";
    $f[] = "server.port: 5601";
    $f[] = "server.host: \"0.0.0.0\"";
    $f[] = "server.basePath: \"/kibana\"";
    $f[] = "server.rewriteBasePath: false";
    $f[] = "pid.file: /var/run/kibana/kibana.pid";
    $f[] = "#server.maxPayloadBytes: 1048576";
    $f[] = "server.name: \"$hostname\"";
    if (substr($KibanaVersion, 0, strlen('6')) === '6') {
        $f[] = "elasticsearch.url: \"http://127.0.0.1:$ElasticsearchBindPort\"";
    }
    if (substr($KibanaVersion, 0, strlen('7')) === '7') {
        $f[] = "elasticsearch.hosts: \"http://127.0.0.1:$ElasticsearchBindPort\"";
    }
    $f[] = "#elasticsearch.preserveHost: true";
    $f[] = "#kibana.index: \".kibana\"";
    $f[] = "#kibana.defaultAppId: \"discover\"";
    $f[] = "#elasticsearch.username: \"user\"";
    $f[] = "#elasticsearch.password: \"pass\"";
    $f[] = "#server.ssl.enabled: false";
    $f[] = "#server.ssl.certificate: /path/to/your/server.crt";
    $f[] = "#server.ssl.key: /path/to/your/server.key";
    $f[] = "#elasticsearch.ssl.certificate: /path/to/your/client.crt";
    $f[] = "#elasticsearch.ssl.key: /path/to/your/client.key";
    $f[] = "#elasticsearch.ssl.certificateAuthorities: [ \"/path/to/your/CA.pem\" ]";
    $f[] = "#elasticsearch.ssl.verificationMode: full";
    $f[] = "#elasticsearch.pingTimeout: 1500";
    $f[] = "#elasticsearch.requestTimeout: 30000";
    $f[] = "#elasticsearch.requestHeadersWhitelist: [ authorization ]";
    $f[] = "#elasticsearch.customHeaders: {}";
    $f[] = "#elasticsearch.shardTimeout: 0";
    $f[] = "#elasticsearch.startupTimeout: 5000";
    $f[] = "#logging.dest: stdout";
    $f[] = "#logging.silent: false";
    $f[] = "#logging.quiet: false";
    $f[] = "logging.verbose: true";
    $f[] = "#ops.interval: 5000";
    $f[] = "#i18n.defaultLocale: \"en\"\n";
    @file_put_contents("/etc/kibana/kibana.yml", @implode("\n", $f));
    @chgrp("/etc/kibana/kibana.yml","kibana");
    @chown("/etc/kibana/kibana.yml","kibana");
    @chmod("/etc/kibana/kibana.yml",0755);
    echo "Starting......: " . date("H:i:s") . " /etc/kibana/kibana.yml done.\n";
}

function create_service()
{
    $unix = new unix();
    $php  = $unix->LOCATE_PHP5_BIN();

    $f[]="#!/bin/sh";
    $f[]="# Init script for kibana";
    $f[]="# Maintained by";
    $f[]="# Generated by pleaserun.";
    $f[]="# Implemented based on LSB Core 3.1:";
    $f[]="#   * Sections: 20.2, 20.3";
    $f[]="#";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          kibana";
    $f[]="# Required-Start:    \$remote_fs \$syslog";
    $f[]="# Required-Stop:     \$remote_fs \$syslog";
    $f[]="# Default-Start:     2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description:";
    $f[]="# Description:       Kibana";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="name=kibana";
    $f[]="program=/usr/share/kibana/bin/kibana";
    $f[]="args=-c\\\\ /etc/kibana/kibana.yml";
    $f[]="pidfile=\"/var/run/\$name.pid\"";
    $f[]="";
    $f[]="[ -r /etc/default/\$name ] && . /etc/default/\$name";
    $f[]="[ -r /etc/sysconfig/\$name ] && . /etc/sysconfig/\$name";
    $f[]="";
    $f[]="export NODE_OPTIONS";
    $f[]="";
    $f[]="[ -z \"\$nice\" ] && nice=0";
    $f[]="";
    $f[]="trace() {";
    $f[]="  logger -t \"/etc/init.d/kibana\" \"\$@\"";
    $f[]="}";
    $f[]="";
    $f[]="emit() {";
    $f[]="  trace \"\$@\"";
    $f[]="  echo \"\$@\"";
    $f[]="}";
    $f[]="";
    $f[]="start() {";
    $f[]="  $php ".__FILE__." --permissions >/dev/null 2>&1 || true";
    $f[]="  # Run the program!";
    $f[]="";
    $f[]="  chroot --userspec \"\$user\":\"\$group\" \"\$chroot\" sh -c \"";
    $f[]="";
    $f[]="    cd \\\"\$chdir\\\"";
    $f[]="    exec \\\"\$program\\\" \$args\n\" >> /var/log/kibana/kibana.stdout 2>> /var/log/kibana/kibana.stderr &";
    $f[]="";
    $f[]="  # Generate the pidfile from here. If we instead made the forked process";
    $f[]="  # generate it there will be a race condition between the pidfile writing";
    $f[]="  # and a process possibly asking for status.";
    $f[]="  echo \$! > \$pidfile";
    $f[]="";
    $f[]="  emit \"\$name started\"";
    $f[]="  return 0";
    $f[]="}";
    $f[]="";
    $f[]="stop() {";
    $f[]="  # Try a few times to kill TERM the program";
    $f[]="  if status ; then";
    $f[]="    pid=\$(cat \"\$pidfile\")";
    $f[]="    trace \"Killing \$name (pid \$pid) with SIGTERM\"";
    $f[]="    kill -TERM \$pid";
    $f[]="    # Wait for it to exit.";
    $f[]="    for i in 1 2 3 4 5 ; do";
    $f[]="      trace \"Waiting \$name (pid \$pid) to die...\"";
    $f[]="      status || break";
    $f[]="      sleep 1";
    $f[]="    done";
    $f[]="    if status ; then";
    $f[]="      if [ \"\$KILL_ON_STOP_TIMEOUT\" -eq 1 ] ; then";
    $f[]="        trace \"Timeout reached. Killing \$name (pid \$pid) with SIGKILL.  This may result in data loss.\"";
    $f[]="        kill -KILL \$pid";
    $f[]="        emit \"\$name killed with SIGKILL.\"";
    $f[]="      else";
    $f[]="        emit \"\$name stop failed; still running.\"";
    $f[]="      fi";
    $f[]="    else";
    $f[]="      emit \"\$name stopped.\"";
    $f[]="    fi";
    $f[]="  fi";
    $f[]="}";
    $f[]="";
    $f[]="status() {";
    $f[]="  if [ -f \"\$pidfile\" ] ; then";
    $f[]="    pid=\$(cat \"\$pidfile\")";
    $f[]="    if ps -p \$pid > /dev/null 2> /dev/null ; then";
    $f[]="      # process by this pid is running.";
    $f[]="      # It may not be our pid, but that's what you get with just pidfiles.";
    $f[]="      # TODO(sissel): Check if this process seems to be the same as the one we";
    $f[]="      # expect. It'd be nice to use flock here, but flock uses fork, not exec,";
    $f[]="      # so it makes it quite awkward to use in this case.";
    $f[]="      return 0";
    $f[]="    else";
    $f[]="      return 2 # program is dead but pid file exists";
    $f[]="    fi";
    $f[]="  else";
    $f[]="    return 3 # program is not running";
    $f[]="  fi";
    $f[]="}";
    $f[]="";
    $f[]="force_stop() {";
    $f[]="  if status ; then";
    $f[]="    stop";
    $f[]="    status && kill -KILL \$(cat \"\$pidfile\")";
    $f[]="  fi";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  force-start|start|stop|force-stop|restart)";
    $f[]="    trace \"Attempting '\$1' on kibana\"";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  force-start)";
    $f[]="    PRESTART=no";
    $f[]="    exec \"\$0\" start";
    $f[]="    ;;";
    $f[]="  start)";
    $f[]="    status";
    $f[]="    code=\$?";
    $f[]="    if [ \$code -eq 0 ]; then";
    $f[]="      emit \"\$name is already running\"";
    $f[]="      exit \$code";
    $f[]="    else";
    $f[]="      start";
    $f[]="      exit \$?";
    $f[]="    fi";
    $f[]="    ;;";
    $f[]="  stop) stop ;;";
    $f[]="  force-stop) force_stop ;;";
    $f[]="  status)";
    $f[]="    status";
    $f[]="    code=\$?";
    $f[]="    if [ \$code -eq 0 ] ; then";
    $f[]="      emit \"\$name is running\"";
    $f[]="    else";
    $f[]="      emit \"\$name is not running\"";
    $f[]="    fi";
    $f[]="    exit \$code";
    $f[]="    ;;";
    $f[]="  restart)";
    $f[]="";
    $f[]="    stop && start";
    $f[]="    ;;";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$SCRIPTNAME {start|force-start|stop|force-start|force-stop|status|restart}\" >&2";
    $f[]="    exit 3";
    $f[]="  ;;";
    $f[]="esac";
    $f[]="";
    $f[]="exit \$?";
    $INITD_PATH = "/etc/init.d/kibana";

    echo "Kibana: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));

    $f=array();
    $f[]="user=\"kibana\"";
    $f[]="group=\"kibana\"";
    $f[]="chroot=\"/\"";
    $f[]="chdir=\"/\"";
    $f[]="nice=\"\"";
    $f[]="";
    $f[]="";
    $f[]="# If this is set to 1, then when `stop` is called, if the process has";
    $f[]="# not exited within a reasonable time, SIGKILL will be sent next.";
    $f[]="# The default behavior is to simply log a message \"program stop failed; still running\"";
    $f[]="KILL_ON_STOP_TIMEOUT=0";
    $f[]="";
    @file_put_contents("/etc/default/kibana", @implode("\n", $f));

    @chmod($INITD_PATH, 0755);

    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " defaults >/dev/null 2>&1");
    }

}
