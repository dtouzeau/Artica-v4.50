<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["YESCGROUP"]=true;
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
xstart();
function build_progress($prc,$text):bool{

    $unix=new unix();
    return $unix->framework_progress($prc,$text,"lts.progress");
}

function xstart():bool{
    $unix=new unix();
    build_progress(10,"Upgrade to Artica LTS...");
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    $key_lts=update_find_lts($ArticaUpdateRepos);
    if($key_lts==0){
        return build_progress(100,"Upgrade to Artica LTS {nothing_to_do}...");
    }
    $LTS=$ArticaUpdateRepos["LTS"];
    $Lastest=$LTS[$key_lts]["VERSION"];
    $MAIN_URI=$LTS[$key_lts]["URL"];
    $MD5Src=$LTS[$key_lts]["MD5"];
    $GLOBALS["UPDATE_VERSION"]=$Lastest;
    build_progress(15,"{donwloading} v$Lastest");
    $curl=new ccurl($MAIN_URI);
    $curl->ProgressFunction="lts_download_progress";
    $curl->Timeout=2400;
    $curl->WriteProgress=true;
    $curl->NoHTTP_POST=true;

    $tpmfile=$unix->FILE_TEMP();
    if(!$curl->GetFile($tpmfile)){
        echo $curl->error;
        return build_progress(110,"{donwloading} v$Lastest {failed}");
    }

    build_progress(20,"{checking} v$Lastest");
    $md5=md5_file($tpmfile);
    if($md5<>$MD5Src){
        echo "$md5 !== $MD5Src\n";
        @unlink($tpmfile);
        return build_progress(110,"{checking} v$Lastest {failed}");
    }
    $tar=$unix->find_program("tar");
    build_progress(50,"{extracting} v$Lastest");
    shell_exec("$tar xf $tpmfile -C /usr/share/");
    build_progress(70,"{cleaning}...");
    @unlink($tpmfile);
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $rm=$unix->find_program("rm");
    shell_exec("$nohup $php /usr/share/artica-postfix/exec.nightly.php --restart-services >/dev/null 2>&1 &");
    if(is_dir("/usr/share/artica-postfix/SP")){
        build_progress(75,"{cleaning} old Service Packs");
        shell_exec("$rm -rf /usr/share/artica-postfix/SP");
    }
    if(is_dir("/home/artica/patchsBackup")){
        build_progress(80,"{cleaning} old Service Packs");
        shell_exec("$rm -rf /home/artica/patchsBackup");
    }

    return build_progress(100,"{done}");
}

function update_find_lts($array):int{
    if(!is_array($array["LTS"])){return 0;}
    $MAIN=$array["LTS"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}
        if($key>$keyMain){$keyMain=$key;}}
    return $keyMain;
}
function lts_download_progress( $download_size, $downloaded_size ):bool{
    if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

    if ( $download_size == 0 ){
        $progress = 0;
    }else{
        $progress = round( $downloaded_size * 100 / $download_size );
    }

    if ( $progress > $GLOBALS["previousProgress"]){
            build_progress("{downloading} {$GLOBALS["UPDATE_VERSION"]} $progress%", 20);
            $GLOBALS["previousProgress"]=$progress;

    }
    return true;
}