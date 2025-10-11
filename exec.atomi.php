<?php
// 4.31 -> 4.30
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


atomi_corp();

function atomi_corp(){
    $unix=new unix();
    $TMPDIR=$unix->TEMP_DIR()."/Atomic";

    $AtomicUsername="dtouzeau";
    $AtomicPassword="02V59I8Dj";
    $rm=$unix->find_program("rm");
    $delete_cmd="$rm -rf $TMPDIR";
    $Index=unserialize(base64_decode(@file_get_contents("/root/atomic-modsecurity.index")));

    $ATOMI_MODSEC_VERSION=intval($Index["VERSION"]);

    $curl=new ccurl("https://updates.atomicorp.com/channels/rules/nginx-latest/VERSION");
    $curl->authname=$AtomicUsername;
    $curl->authpass=$AtomicPassword;
    if(!is_dir($TMPDIR)){@mkdir($TMPDIR,0755,true);}
    $VERSION_PATH="$TMPDIR/VERSION";
    if(!$curl->GetFile($VERSION_PATH)){
        squid_admin_mysql(0,"Downloading Atomic CORP Index database failed L.".__LINE__,$curl->error,__FILE__,__LINE__);
        sysevnt("Downloading Atomic CORP Index database failed","modsecurity-update");
        sysevnt($curl->error);
        shell_exec($delete_cmd);
        return false;
    }
    $MODSEC_VERSION=0;
    $f=explode("\n",@file_get_contents($VERSION_PATH));
    foreach ($f as $line){
    $line=trim($line);
    if(preg_match("#^MODSEC_VERSION=([0-9]+)#",$line,$re)){
        $MODSEC_VERSION=intval($re[1]);
    }

    }
    if($MODSEC_VERSION==0){
        sysevnt("Atomic CORP Index database failed (Corrupted)","modsecurity-update");
        shell_exec($delete_cmd);
        return false;
    }
    if(!$GLOBALS["FORCE"]) {
        if ($MODSEC_VERSION == $ATOMI_MODSEC_VERSION) {
            sysevnt("Atomic CORP ($ATOMI_MODSEC_VERSION/$MODSEC_VERSION) success, no update ", "modsecurity-update");
            shell_exec($delete_cmd);
            return true;
        }

        if ($ATOMI_MODSEC_VERSION > $MODSEC_VERSION) {
            sysevnt("Atomic CORP ($ATOMI_MODSEC_VERSION/$MODSEC_VERSION) success, no update ", "modsecurity-update");
            shell_exec($delete_cmd);
            return true;
        }
    }
    $tfile="nginx-waf-$MODSEC_VERSION.tar.gz";
    $PACKAGE_PATH="$TMPDIR/$tfile";

    $curl=new ccurl("https://updates.atomicorp.com/channels/rules/artica/$tfile");
    $curl->authname=$AtomicUsername;
    $curl->authpass=$AtomicPassword;

    if(!$curl->GetFile($PACKAGE_PATH)){
        squid_admin_mysql(0,"Downloading Atomic CORP database $MODSEC_VERSION failed",$curl->error,__FILE__,__LINE__);
        sysevnt("Downloading Atomic CORP database $MODSEC_VERSION failed","modsecurity-update");
        sysevnt($curl->error);
        shell_exec($delete_cmd);
        return false;
    }


    $tar=$unix->find_program("tar");
    shell_exec("$tar xf $PACKAGE_PATH -C $TMPDIR");

    $RulesSrcPath="$TMPDIR/nginx-waf";
    system("find $TMPDIR");

    if(!is_dir($RulesSrcPath)){
        $dir_handle = @opendir($TMPDIR);
        while ($file = readdir($dir_handle)) {
            if($file=='.'){continue;}
            if($file=='..'){continue;}
            if(!is_dir( $TMPDIR/$file)){continue;}
            sysevnt("Error: Found directory $TMPDIR/$file");
        }

        squid_admin_mysql(0,"{installing} Atomic CORP database $MODSEC_VERSION failed",$curl->error,__FILE__,__LINE__);
        sysevnt("Installing Atomic CORP database $MODSEC_VERSION failed ($RulesSrcPath not found)");
       // shell_exec($delete_cmd);
        return false;
    }

    $cd=$unix->find_program("cd");
    chdir($RulesSrcPath);
    shell_exec("$cd $RulesSrcPath");
    $tar=$unix->find_program("tar");
    if(is_file("/root/atomic-modsecurity.tar.gz")){@unlink("/root/atomic-modsecurity.tar.gz");}
    echo "Compressing $RulesSrcPath\n";
    system("$tar cvf /root/atomic-modsecurity.tar.gz *");
    $md5=md5_file("/root/atomic-modsecurity.tar.gz");
    $array["MD5"]=$md5;
    $array["VERSION"]=$MODSEC_VERSION;
    @file_put_contents("/root/atomic-modsecurity.index",base64_encode(serialize($array)));
    upload_category("/root/atomic-modsecurity.index");
    upload_category("/root/atomic-modsecurity.tar.gz");

    return true;


}

function upload_category($filetext=null){

    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));


    if ( ($UfdbCatsUploadFTPserv==null) OR ($UfdbCatsUploadFTPusr==null)){
        return false;
    }
    echo "Connect to $UfdbCatsUploadFTPserv...\n";
    $conn_id = ftp_connect($UfdbCatsUploadFTPserv);        // set up basic connection

    if(!$conn_id){
        echo "Connection Failed....\n";
        return false;
    }
    echo "Login as $UfdbCatsUploadFTPusr...\n";
    $login_result = ftp_login($conn_id, $UfdbCatsUploadFTPusr, $UfdbCatsUploadFTPpass);

    if ((!$conn_id) || (!$login_result)) {
        echo "Failed $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv\n";
        squid_admin_mysql(0, "{failed} {connecting} $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv", null,__FILE__,__LINE__);
        @file_put_contents("$filetext.err",time());
        return false;
    }

    ftp_pasv($conn_id, true);
    if(!is_file($filetext)){echo "!!! unable to find $filetext\n";die();}
    $file = basename($filetext);
    $fp = fopen($filetext, 'r');

    if (ftp_fput($conn_id, "$UfdbCatsUploadFTPDir/$file", $fp, FTP_BINARY)) {
        echo "Uploading $filetext success ($UfdbCatsUploadFTPDir) $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv\n";

    } else {
        print_r( error_get_last() );
        echo "Failed to upload $filetext in $UfdbCatsUploadFTPDir\n";
        ftp_close($conn_id);
        fclose($fp);
        @file_put_contents("$filetext.err",time());
        return false;
    }
    fclose($fp);
    return true;



}
function sysevnt($text,$none=null){
    echo "$text\n";
    if(!function_exists("openlog")){return false;}
    openlog("modsecurity-update", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}