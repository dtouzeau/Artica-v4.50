<?php

include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.postgres.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if ($argv[1] == "--start") {
    start();
    exit;
}
if ($argv[1] == "--stop") {
    stop();
    exit;
}
if ($argv[1] == "--restart") {
    restart();
    exit;
}



function STRONGSWAN_VICI_PID(): int
{
    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/strongswan-stats.pid");
    if ($unix->process_exists($pid)) {
        return intval($pid);
    }
    return intval($unix->PIDOF_PATTERN(ARTICA_ROOT."/strongswan-vici.py"));
}

function STRONGSWAN_VICI_STATS_PID(): int
{
    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/strongswan-vici-stats.pid");
    if ($unix->process_exists($pid)) {
        return intval($pid);
    }
    return intval($unix->PIDOF_PATTERN(ARTICA_ROOT."/bin/strongswan-vici-stats.py"));
}

function restart()
{
    build_progress_str(25, "{stopping_service}");
    stop();
    build_progress_str(50, "{starting_service}");
    sleep(3);
    start();
    build_progress_str(100, "{starting_service} {success}");

}

function start(): bool
{
    $unix = new unix();
    $nohup=$unix->find_program("nohup");
    $EnableStrongswanServer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer"));
    $python = $unix->find_program("python");
    $binary_vici         = ARTICA_ROOT."/strongswan-vici.py";
    $binary_vici_stats         = ARTICA_ROOT."/bin/strongswan-vici-stats.py";


    if (!is_file($binary_vici)) {
        _out("$binary_vici no such binary file");
        return false;
    }

    if (!is_file($binary_vici_stats)) {
        _out("$binary_vici_stats no such binary file");
        return false;
    }

    if ($EnableStrongswanServer == 0) {
        _out("IPSEC Service not enabled");
        return false;
    }

    //CHECK strongswan-vici.py
    $pid = STRONGSWAN_VICI_PID();
    if ($unix->process_exists($pid)) {
        $ptime = $unix->PROCCESS_TIME_MIN($pid);
        _out("strongswan-vici.py service already running PID $pid since {$ptime}mn");
        return true;
    }
    if (!is_file("/etc/init.d/ipsec-stats")) {
        _out("strongSwan Stats service, corrupted installation");
        return false;
    }
    _out("Starting service strongswan-vici.py...");
    @chmod($binary_vici,0755);
    $TEMPFILE=$unix->FILE_TEMP();
    system("$nohup $python $binary_vici >$TEMPFILE 2>&1 &");
    for ($i = 1; $i < 6; $i++) {
        $pid = STRONGSWAN_VICI_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
        _out("Waiting strongswan-vici.py service to start $i/5");
        usleep(500);

    }

    $pid = STRONGSWAN_VICI_PID();
    if (!$unix->process_exists($pid)) {
        _out("Starting strongswan-vici.py service failed...");
        return false;
    }

    //CHECK strongswan-vici-stats.py
    $pid = STRONGSWAN_VICI_STATS_PID();
    if ($unix->process_exists($pid)) {
        $ptime = $unix->PROCCESS_TIME_MIN($pid);
        _out("strongswan-vici-stats.py service already running PID $pid since {$ptime}mn");
        return true;
    }
    if (!is_file("/etc/init.d/ipsec-stats")) {
        _out("strongSwan Stats service, corrupted installation");
        return false;
    }
    _out("Starting service strongswan-vici-stats.py...");
    $TEMPFILE=$unix->FILE_TEMP();
    @chmod($binary_vici_stats,0755);
    system("$nohup $python $binary_vici_stats >$TEMPFILE 2>&1 &");
    for ($i = 1; $i < 6; $i++) {
        $pid = STRONGSWAN_VICI_STATS_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
        _out("Waiting strongswan-vici-stats.py service to start $i/5");
        usleep(500);

    }

    $pid = STRONGSWAN_VICI_STATS_PID();
    if (!$unix->process_exists($pid)) {
        _out("Starting strongswan-vici-stats.py service failed...");
        return false;
    }

    _out("Starting service success...");
    return true;

}

function _out($text): bool
{
    $unix = new unix();
    $unix->ToSyslog("[START] $text", false, "nginx");
    $date = date("H:i:s");
    echo "Starting......: $date [INIT]: strongswan-stats service: $text\n";
    return true;
}

function stop($aspid = false)
{
    $unix = new unix();
    //CHECK strongswan-vici.py
    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";

        $pid = STRONGSWAN_VICI_PID();
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            if ($GLOBALS["OUTPUT"]) {
                echo "Starting......: " . date("H:i:s") . " [INIT]: strongswan-vici (from FILE )service Already Artica task running PID $pid since {$time}mn\n";
            }
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid = STRONGSWAN_VICI_PID();


    if (!$unix->process_exists($pid)) {
        _out("strongswan-vici.py service already stopped");
        //return true;
    }


    $pid = STRONGSWAN_VICI_PID();


    _out("strongswan-vici.py service Shutdown pid $pid");
    $unix->KILL_PROCESS($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = STRONGSWAN_VICI_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("strongswan-vici.py service waiting pid:$pid $i/5");
        sleep(1);
    }

    $pid = STRONGSWAN_VICI_PID();
    if (!$unix->process_exists($pid)) {
        _out("strongswan-vici.py service success");
        //return true;
    }

    _out("strongswan-vici.py service shutdown - force - pid $pid");
    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = STRONGSWAN_VICI_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("strongswan-vici.py service waiting pid:$pid $i/5");
        sleep(1);
        unix_system_kill_force($pid);
    }

    if (!$unix->process_exists($pid)) {
        _out("strongswan-vici.py service success");
        //return true;
    }

    //CHECK strongswan-vici-stats.py
    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";

        $pid = STRONGSWAN_VICI_STATS_PID();
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            if ($GLOBALS["OUTPUT"]) {
                echo "Starting......: " . date("H:i:s") . " [INIT]: strongswan-vici-stats.py (from FILE )service Already Artica task running PID $pid since {$time}mn\n";
            }
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid = STRONGSWAN_VICI_STATS_PID();


    if (!$unix->process_exists($pid)) {
        _out("strongswan-vici-stats.py service already stopped");
        return true;
    }


    $pid = STRONGSWAN_VICI_STATS_PID();


    _out("strongswan-vici-stats.py service Shutdown pid $pid");
    $unix->KILL_PROCESS($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = STRONGSWAN_VICI_STATS_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("strongswan-vici-stats.py service waiting pid:$pid $i/5");
        sleep(1);
    }

    $pid = STRONGSWAN_VICI_STATS_PID();
    if (!$unix->process_exists($pid)) {
        _out("strongswan-vici-stats.py service success");
        return true;
    }

    _out("strongswan-vici-stats.py service shutdown - force - pid $pid");
    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = STRONGSWAN_VICI_STATS_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("strongswan-vici-stats.py service waiting pid:$pid $i/5");
        sleep(1);
        unix_system_kill_force($pid);
    }

    if (!$unix->process_exists($pid)) {
        _out("strongswan-vici-stats.py service success");
        return true;
    }

    _out("strongSwan Stats service failed");
    return false;

}
function build_progress_str($pourc, $text)
{
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.install.php";
    if ($argv[1]=="--reconfigure") {
        $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.build.progress";
    }
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"], 0755);
    if ($GLOBALS["OUTPUT"]) {
        sleep(1);
    }
}














