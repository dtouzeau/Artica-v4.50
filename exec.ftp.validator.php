<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ftp.client.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(is_array($argv)) {
    if (preg_match("#--verbose#", implode(" ", $argv))) {
        $GLOBALS["VERBOSE"] = true;
        $GLOBALS["DEBUG_MEM"] = true;
    }
    if(isset($argv[1])) {
        if ($argv[1] == "--validate") {
            validate();
            exit;
        }
    }

}

    if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


validate_ftp();


function build_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/ftp.validator.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/ftp.validator.progress",0755);

}

function validate_ftp(){
    $unix=new unix();
    $FTPValidator=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPValidator")));
    $FTP_SERVER=$FTPValidator["FTP_SERVER"];
    $TARGET_DIR=$FTPValidator["TARGET_DIR"];
    $USERNAME=$FTPValidator["USERNAME"];
    $PASSWORD=$FTPValidator["PASSWORD"];
    $FTP_PASSIVE=0;$FTP_PASSIVE_TEXT=null;
    $TLS=0;$TLS_TEXT=null;
    if(isset($FTPValidator["FTP_PASSIVE"])){
        $FTP_PASSIVE=intval($FTPValidator["FTP_PASSIVE"]);
    }
    if(isset($FTPValidator["TLS"])){
        $TLS=intval($FTPValidator["TLS"]);
    }

    if($FTP_PASSIVE==1){
        $FTP_PASSIVE_TEXT=" with PASSIVE";
    }
    if($TLS==1){
        $TLS_TEXT=" with TLS";
    }

    if(strlen($PASSWORD)==0){
        echo "Warning, no password as been defined...\n";
    }

    echo "Connecting{$FTP_PASSIVE_TEXT}{$TLS_TEXT} to $USERNAME@ftp://$FTP_SERVER/$TARGET_DIR...\n";
    build_progress("{connecting}: $FTP_SERVER",20);

    if($GLOBALS["VERBOSE"]){
        echo "Username: $USERNAME\n";
        echo "Password: $PASSWORD\n";
        echo "Target Directory: $TARGET_DIR \n";
    }


    $ftp=new ftp_client($FTP_SERVER,$USERNAME,$PASSWORD,21,5);
    if($FTP_PASSIVE==1){
        echo "PASSIVE MODE Enabled...\n";
        $ftp->setPassive();
    }
    if($TLS==1){
        $ftp->SetTLS();
    }
    build_progress("{connecting}: TCP $FTP_SERVER:21",25);
    $fp=@fsockopen($FTP_SERVER, 21, $errno, $errstr, 4);
    if(!$fp){
        echo "Unable to TCP connect $FTP_SERVER:21 with error $errno: $errstr\n";
        build_progress("TCP {failed}",110);
        die();
    }


    build_progress("{connecting}: FTP $FTP_SERVER:21",30);
    if(!$ftp->connect()) {
        echo "Unable to connect with error: $ftp->error\n";
        build_progress("FTP {failed}",110);
        die();
    }

    build_progress("{connected}: $FTP_SERVER, {checking} $TARGET_DIR",40);

    if(!$ftp->cd($TARGET_DIR)){
        echo "[CD] $TARGET_DIR $ftp->error\n";
        build_progress("[CD] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();
    }

    echo "Current directory: ".$ftp->pwd()."\n";


    build_progress("{connected}: $FTP_SERVER, {checking} $TARGET_DIR",45);
    $tmpfile=$unix->FILE_TEMP();
    $data=null;
    echo "Build temporary file: $tmpfile\n";
    for($i=0;$i<10000;$i++){
        $data=$data."AAAAAAAAAAAAAAAAAA";
    }

    $datal=strlen($data);
    $datal=$datal/1024;
    $datal=$datal/1024;
    echo "Temporary file size: $datal Mb\n";
    @file_put_contents($tmpfile,$data);
    build_progress("{connected}: $FTP_SERVER, {checking} $TARGET_DIR",50);
    echo "Uploading $tmpfile to ".$ftp->pwd()."\n";
    if(!$ftp->put($tmpfile,basename($tmpfile))){
        echo "[PUT] $TARGET_DIR $ftp->error\n";
        build_progress("[PUT] $TARGET_DIR {permission_denied}  {or} {timeout}",110);
        $ftp->close();
        @unlink($tmpfile);
        die();
    }

    echo "Temporary $tmpfile uploaded - success, remove uploaded file now...\n";
    @unlink($tmpfile);
    build_progress("{connected}: $FTP_SERVER, {checking} DEL",60);
    if(!$ftp->delete(basename($tmpfile))){
        echo "[DEL] $TARGET_DIR $ftp->error\n";
        build_progress("[DEL] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();
    }

    echo "Creating temporary folder $TARGET_DIR/".basename($tmpfile);
    build_progress("{connected}: $FTP_SERVER, {checking} MKDIR",70);
    if(!$ftp->mkdir(basename($tmpfile))){
        echo "[MKDIR] $TARGET_DIR $ftp->error\n";
        build_progress("[MKDIR] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();

    }
    echo "Removing temporary folder $TARGET_DIR/".basename($tmpfile);
    build_progress("{connected}: $FTP_SERVER, {checking} RMDIR",80);
    if(!$ftp->rmdir(basename($tmpfile))){
        echo "[RMDIR] $TARGET_DIR $ftp->error\n";
        build_progress("[RMDIR] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();
    }

   print_r($ftp->ls());

    $ftp->close();
    build_progress("{success}",100);

}




