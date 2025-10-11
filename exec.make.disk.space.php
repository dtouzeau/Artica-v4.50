<?php
if (is_file("/usr/bin/cgclassify")) {if (is_dir("/cgroups/blkio/php")) {shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php " . getmypid());}}
include_once dirname(__FILE__) . '/framework/class.unix.inc';if (!isset($GLOBALS["CLASS_SOCKETS"])) {if (!class_exists("sockets")) {include_once "/usr/share/artica-postfix/ressources/class.sockets.inc";}
    $GLOBALS["CLASS_SOCKETS"] = new sockets();}


function ScanDisk(){

    $directories["ntopng"]="/usr/share/ntopng";
    $directories["kibana"]="/usr/share/kibana";
    $directories["elasticsearch"]="/usr/share/elasticsearch";
    $directories["java"]="/usr/lib/jvm";
    $directories["artica-patchs"]="/home/artica/patchsBackup";
    $directories["apt-cache"]="/var/cache/apt/archives";
    $directories["ufdbartica"]="/var/lib/ufdbartica";
    $directories["clamav"]="/var/lib/clamav";
}

