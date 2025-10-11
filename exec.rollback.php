<?php
if (function_exists("posix_getuid")) {if (posix_getuid() != 0) {die("Cannot be used in web server mode\n\n");}}
include_once "/usr/share/artica-postfix/ressources/class.sockets.inc";
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once dirname(__FILE__) . '/ressources/class.system.network.inc';
include_once dirname(__FILE__) . '/framework/class.unix.inc';
include_once dirname(__FILE__) . "/framework/frame.class.inc";
$GLOBALS["PROGRESS_FILE"] = PROGRESS_DIR."/system.installsoft.progress";
if (is_numeric($argv[1])){rollback_to($argv[1]);exit;}


function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/roolback.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function rollback_to($sp_required){
    $BASE           = ARTICA_ROOT;
    $VERSION        = trim(@file_get_contents("$BASE/VERSION"));
    $BaseWorkDir    = "/home/artica/patchsBackup/$VERSION";
    $MAIN           = array();
    $unix           = new unix();
    $tar            = $unix->find_program("tar");

    if(!is_file("$BaseWorkDir/$sp_required/package.tgz")){
        echo "'$BaseWorkDir/$sp_required/package.tgz' not found\n";
        build_progress(110,"$sp_required source not found");
        return false;
    }

    if (!$handle = opendir($BaseWorkDir)) {
        echo "$BaseWorkDir handle issue\n";
        build_progress(110,"$BaseWorkDir handle issue");
        return false;
    }



    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(!is_numeric($filename)){continue;}
        $backupdir="$BaseWorkDir/$filename";
        if(!is_file("$backupdir/package.tgz")){continue;}
        $fsize=filesize("$backupdir/package.tgz");
        $MAIN[$filename]=$fsize;
    }
    krsort($MAIN);

    build_progress(10,"Restoring...");
    $max=count($MAIN);
    $c=1;
    foreach ($MAIN as $sp=>$size){
        echo "$sp\n";
        $prcb=$c/$max;
        $prc=round($prcb*100);
        if($prc<10){$prc=10;}
        if($prc>95){$prc=95;}
        if($sp<$sp_required){continue;}
        $srcfile="$BaseWorkDir/$sp_required/package.tgz";
        build_progress($prc,"Restoring Service Pack $sp...");
        shell_exec("$tar xf $srcfile -C /");
        @file_put_contents("$BASE/SP/$VERSION",$sp);
    }


    shell_exec("/usr/sbin/artica-phpfpm-service -permission-watch");
    build_progress(100,"$sp_required {done}");


}