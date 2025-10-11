<?php




function wifi_mode_dormant(){
    $EnableIwConfig     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
    $Files              = $GLOBALS["CLASS_UNIX"]->DirFiles("/etc/init.d","wifi-wlan[0-9]+");
    $ip                 = $GLOBALS["CLASS_UNIX"]->find_program("ip");
    $ifconfig           = $GLOBALS["CLASS_UNIX"]->find_program("ifconfig");
    $iw                 = $GLOBALS["CLASS_UNIX"]->find_program("iw");

    foreach ($Files as $net=>$nothing) {
        echo "Scannning $net\n";
        if (!preg_match("#wifi-(.+)#", $net, $re)) {
            echo "Scannning $net - SKIP\n";
            continue;
        }
        $Interface = $re[1];


    }




}



