<?php
// SP 127
$GLOBALS["FORCE"] = false;
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
if (function_exists("posix_getuid")) {
    if (posix_getuid() <> 0) {
        die("Cannot be used in web server mode\n\n");
    }
}
$GLOBALS["AS_ROOT"] = true;
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
//include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');
if (preg_match("#--verbose#", implode(" ", $argv))) {
    $GLOBALS["VERBOSE"] = true;
    $GLOBALS["OUTPUT"] = true;
    $GLOBALS["debug"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (preg_match("#--force#", implode(" ", $argv))) {
    $GLOBALS["FORCE"] = true;
}
xrun();
function xrun()
{
    $unix = new unix();
    $sock = new sockets();
    $LasTime = $unix->file_time_min("/etc/artica-postfix/pids/exec.strongswan.php.status.mmtime");
    if (!$GLOBALS["FORCE"]) {
        if ($LasTime == 0) {
            echo "Only each 1mn, current 0, Aborting...\n";
            die();
        }
    }

    @unlink("/etc/artica-postfix/pids/exec.strongswan.php.status.mmtime");
    @file_put_contents("/etc/artica-postfix/pids/exec.strongswan.php.status.mmtime", getmypid());

    if (!is_file("/etc/cron.d/strongswan-status-mn")) {
        $unix->Popuplate_cron_make("artica-strongswan-sess2mn", "0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *", "exec.strongswan.sessions.php");
        $unix->Popuplate_cron_make("strongswan-status-mn", "* * * * *", "exec.strongswan.sessions.php");

        shell_exec("/etc/init.d/cron reload");
    }
//    $z=new postgres_sql();
    $q = new lib_sqlite("/home/artica/SQLITE/strongswan.db");

//    if (!$z->TABLE_EXISTS('strongswan_stats')) {
//        echo "table is= ".$z->TABLE_EXISTS('strongswan_stats') . "\n";
//        $sql = "CREATE TABLE  IF NOT EXISTS strongswan_stats(
//				ID SERIAL,
//				zdate TIMESTAMP,
//				spi_in VARCHAR(255),
//                spi_out VARCHAR(255),
//				conn_name VARCHAR(255),
//                username VARCHAR(255),
//				remote_host inet,
//                local_vip inet,
//                time bigint default 0 ,
//				bytes_in bigint default 0,
//				bytes_out bigint default 0,
//    			packets_in bigint default 0,
//				packets_out bigint default 0
//		) ";
//        $z->QUERY_SQL($sql);
//    }


    $pid = STRONGSWAN_PID();
    if (!$unix->process_exists($pid)) {
        if ($GLOBALS["VERBOSE"]) {
            echo "{$pid} strongSwan not in memory...Aborting\n";
        }
        return;
    }

    // $kill=$unix->find_program("kill");
    // shell_exec("$kill -USR2 $pid");
    // if ($GLOBALS["VERBOSE"]) {
    //     echo "USR2 Process number $pid\n";
    // }

    $bin_path = $unix->find_program("swanctl");
    $array = shell_exec("$bin_path -l  --pretty");
    $array = trim(preg_replace('/[\n\r]/', '|', $array));
    $array = str_replace(' ', '', $array);
    $array = str_replace('CN=', 'CN-', $array);
    $array = str_replace('=', '":"', $array);
    $array = str_replace('{', '":{', $array);
    //$array = substr_replace($array, '"', 0, 0);

    $k = explode("|", $array);

    $j = array();
    foreach ($k as $y => $value) {
        $j[] = $value;
    }

    $c = array();
    foreach ($j as $k => $val) {
        $c[] = '"' . $val . '",';
    }

    $h = array();
    foreach ($c as $key => $str) {
        $h[] = str_replace('":{",', '":{', $str);
        //$h[] = str_replace('":[",', '":[', $str);
    }
    $p = array();
    foreach ($h as $key => $str) {
        $p[] = str_replace(':"["', ':[', $str);
    }

    $v = array();
    foreach ($p as $key => $str) {
        $v[] = str_replace('"]"', ']', $str);
    }

    $d = array();
    foreach ($v as $key => $str) {
        $d[] = str_replace('[,', '[', $str);
    }

    $b = array();
    foreach ($d as $key => $str) {

        $b[] = str_replace('"}",', '}', $str);
    }

    $m = array();
    foreach ($b as $key => $str) {
        $m[] = str_replace('list-saevent', $key, $str);
    }
    $tim = array_map('rtrim', $m);

    $json = json_encode($tim, JSON_PRETTY_PRINT);
    $json = str_replace('"\\', '', $json);
    $json = str_replace('\\', '', $json);
    $json = str_replace('{",', '{', $json);
    $json = str_replace('",",', '",', $json);
    $json = str_replace('"}",', '}', $json);
    $json = str_replace('"],",', ']', $json);
    $json = str_replace('[",', '[', $json);
    $json = str_replace('","', '', $json);


    $json = str_replace_first('[', '{', $json);
    $json = str_lreplace(']', '}', $json);


    $l = json_decode(json_encode($json));

    $l = trim(preg_replace('/\s+/', ' ', $l));
    $l = str_replace('} } } }', '} } } },', $l);
    $l = str_replace('", ]', '" ],', $l);
    $l = str_replace('" ], }', ' "] }', $l);
    $l = str_replace('"}"', ' }', $l);
    $l = str_replace('] } "', ']},"', $l);
    $l = str_replace('} } "', '}},"', $l);
    $l = str_replace(' ", ', '', $l);
    $l = str_replace(', " ]', ']', $l);
    $l = str_replace('{}}}', '{}}},', $l);
    $l = str_replace('],}', ']}', $l);
    $l = str_replace('}}}}', '}}}},', $l);
    $l = str_replace('",}', '}', $l);

    //echo $l;
    $sessions = json_decode($l, true);

    unset($sessions['list-sasreply']);

    $strongSwanCNXNUmber = count($sessions);

    $sql = "SELECT * FROM strongswan_conns ORDER BY `order` ASC";
    $results = $q->QUERY_SQL($sql);
//    //fallback values
//    $conn_name = '';
//    $username = '';
//    $remote_host = '';
//    $vips = '';
//    $spi_in = 0;
//    $spi_out = 0;
//    $bytes_in = 0;
//    $bytes_out = 0;
//    $packets_in = 0;
//    $packets_out = 0;
//    $time = 0;
//    echo "NUMBER OF SESSIONS IS=>" . count($sessions) . "\n";
//    //INSERT STATS
//        foreach ($sessions as $j => $v) {
//            $GLOBALS['bp']=0;
//            if (is_array($v)) {
//                foreach ($v as $k => $w) {
//                        if (is_array($w)) {
//                        foreach ($w as $t => $l) {
//                            if ($t == 'remote-host') {
//                                $remote_host = $l;
//                            }
//
//
//                            if (array_key_exists('remote-eap-id', $w)) {
//                                if ($t == 'remote-eap-id') {
//                                    $username = $l;
//                                }
//                            } else {
//                                if ($t == 'remote-id') {
//                                    $username = $l;
//                                }
//                            }
//                            if (is_array($l)) {
//                                foreach ($l as $p => $o) {
//                                    if ($p == 'remote-vips') {
//                                        $vips = $o;
//                                        $GLOBALS['bp']=1;
//                                    }
//                                    else{
//                                        if($GLOBALS['bp']==0) {
//                                            if (is_array($o)) {
//                                                foreach ($o as $e => $r) {
//                                                    if ($e == 'remote-ts') {
//                                                        $vips = str_replace(' ', '', $r[0]);
//                                                    }
//                                                }
//                                            }
//                                        }
//                                    }
//                                    if (is_array($o)) {
//                                        foreach ($o as $e => $r) {
//                                            if ($e == "spi-in") {
//                                                $spi_in = $r;
//                                            }
//                                            if ($e == "spi-out") {
//                                                $spi_out = $r;
//                                            }
//                                            if ($e == "name") {
//                                                $conn_name = $r;
//                                            }
//                                            if ($e == "bytes-in") {
//                                                $bytes_in = $r;
//                                            }
//                                            if ($e == "bytes-out") {
//                                                $bytes_out = $r;
//                                            }
//                                            if ($e == "packets-in") {
//                                                $packets_in = $r;
//                                            }
//                                            if ($e == "packets-out") {
//                                                $packets_out = $r;
//                                            }
//
//                                            if ($e == 'install-time') {
//                                                $time = $r;
//                                            }
//                                        }
//                                    }
//                                }
//                            }
//                        }
//                    }
//
//                }
//            }
//            $zdate = date("Y-m-d H:i:s",time());
//            //$ligne=$z->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM strongswan_stats where spi_in='$spi_in' AND spi_out='$spi_out' AND  zdate between date_trunc('hour', TIMESTAMP '$zdate') and date_trunc('hour', TIMESTAMP '$zdate' + interval '1 hour')");
//            $ligne=$z->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM strongswan_stats where spi_in='$spi_in' AND spi_out='$spi_out' ");
//
//            if (intval($ligne["tcount"]) == 0) {
//                echo "INSERT  $conn_name\n";
//                //INSERT DATA
//                $sql = "INSERT INTO strongswan_stats (zdate,spi_in,spi_out,conn_name,username,remote_host,local_vip,time,bytes_in,bytes_out,packets_in,packets_out) VALUES ('$zdate','$spi_in','$spi_out','$conn_name','$username','$remote_host','$vips','$time','$bytes_in','$bytes_out','$packets_in','$packets_out')";
//                $z->QUERY_SQL($sql);
//            } else {
//                echo "UPDATE $conn_name\n";
//                //UPDATE DATA
//                $sql = "UPDATE strongswan_stats set conn_name='$conn_name',username='$username',remote_host='$remote_host',local_vip='$vips',time='$time',bytes_in='$bytes_in',bytes_out='$bytes_out',packets_in='$packets_in',packets_out='$packets_out' WHERE spi_in='$spi_in' AND spi_out='$spi_out'";
//                $z->QUERY_SQL($sql);
//            }
//        }


    //UPDATE TOKENS
    foreach ($results as $index => $ligne) {
        $counter = 0;
        foreach ($sessions as $j => $v) {

            if (is_array($v)) {
                foreach ($v as $k => $w) {
                    // if (isset($w['remote-vips'])) {
                    //     if (is_array($w)) {
                    //         file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_$k", count($w['remote-vips']));
                    //     }
                    // } else {
                    //     if (is_array($w)) {
                    //         foreach ($w as $t => $l) {
                    //             if (is_array($l)) {
                    //                 foreach ($l as $p => $o) {
                    //                     file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_$k", count($o['remote-ts']));
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }
                    if ($k == $ligne["conn_name"]) {
                        $time = secondsToTime($w['established']);
                        file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_time_{$ligne["conn_name"]}", $time);
                        if ($w['state'] == 'ESTABLISHED') {
                            $counter = $counter + 1;
                        }

                    }
//                    if (is_array($w)) {
//                        foreach ($w as $t => $l) {
//                            if (is_array($l)) {
//                                foreach ($l as $p => $o) {
//                                    if (is_array($o)) {
//                                        foreach ($o as $e => $r) {
//                                                                                        if ($e == 'install-time') {
//                                                $time = secondsToTime($r);
//                                                file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_time_$k", $time);
//                                            }
//                                        }
//                                    }
//                                }
//                            }
//                        }
//                    }

                }
            }
        }


        file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_{$ligne["conn_name"]}", $counter);
    }
    @unlink("/etc/artica-postfix/settings/Daemons/strongSwanClientsArray");
    $sock->SET_INFO("strongSwanCNXNUmber", $strongSwanCNXNUmber);
    $sock->SET_INFO("strongSwanClientsArray", serialize($sessions));
}

function secondsToTime($seconds)
{
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%h hours, %i minutes and %s seconds');
}

function str_replace_first($from, $to, $content)
{
    $from = '/' . preg_quote($from, '/') . '/';
    return preg_replace($from, $to, $content, 1);
}

function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

function STRONGSWAN_PID()
{
    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/charon.pid");
    if ($unix->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = $unix->find_program("charon");
    return $unix->PIDOF($Masterbin);
}
