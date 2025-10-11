<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ftp.client.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--backup"){perform_ldap_backup();exit();}
if($argv[1]=="--import"){perform_ldap_restore($argv[2]);}
//if($argv[1]=="--restore"){perform_db_restore($argv[2],$argv[3],$argv[4]);exit();}


function build_progress($text,$pourc){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/openldap.backup.progress";
    echo "{$pourc}% $text\n";
    $cachefile=$GLOBALS["CACHEFILE"];
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function perform_ldap_restore($filepath): bool{
    $srcfile            =   $filepath;
    $target_directory   = "/home/artica/ldap_backup";
    $NODELETE           = True;

    if(!is_file($filepath)){
        $filepath=dirname(__FILE__)."/ressources/conf/upload/$filepath";
        $NODELETE = False;
    }
    if(!is_file($filepath)){
        $filepath="$target_directory/$srcfile";
    }

    if(!is_file($filepath)){
        $filepath="$target_directory/$srcfile";
        build_progress("$filepath no such file",110);
        return false;
    }


    if(!preg_match("#\.gz$#",$filepath)){
        echo "$filepath not a gz file...\n";
        if(!$NODELETE){@unlink($filepath);}
        build_progress("$filepath {corrupted}",110);
        return false;
    }


    $unix               = new unix();
    $tmp_dn             = null;
    $base               = basename($filepath);
    $tmpfile            = $unix->FILE_TEMP();
    $target_directory   = "/home/artica/ldap_backup";
    $suffix             = trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
    $php5               = $unix->LOCATE_PHP5_BIN();
    $rm                 = $unix->find_program("rm");
    $slapadd            = $unix->find_program("slapadd");
    $NewF               = array();

    build_progress("{extracting} $base",10);
    if(!$unix->uncompress($filepath,$tmpfile)){
        echo @implode("\n",$GLOBALS["UNCOMPRESS"]);
        build_progress("{extracting} $base {failed}",110);
        if(!$NODELETE){@unlink($filepath);}
        return false;
    }

    if(!$NODELETE){@unlink($filepath);}
    echo "Current suffix: $suffix\n";
    $f=explode("\n",@file_get_contents($tmpfile));
    build_progress("{analyze_database} $base",15);

    foreach ($f as $line){
        if(preg_match("#^dn:(.+)#",$line,$re)){
            $tmp_dn=trim($re[1]);
            break;
        }
    }

    if($tmp_dn==null){
        build_progress("{analyze_database} $base {failed}",110);
        if(!$NODELETE){@unlink($filepath);}
        return false;
    }

    if(strtolower($suffix)<>strtolower($tmp_dn)){
        build_progress("{prepare_snapshot} $base",20);
        foreach ($f as $line){
            $NewF[]=str_replace($tmp_dn,$suffix,$line);
        }
        build_progress("{prepare_snapshot} $base {success}",25);
        @file_put_contents($tmpfile,@implode("\n",$NewF));
    }

    build_progress("{stopping} {APP_OPENLDAP}",30);
    system("/usr/sbin/artica-phpfpm-service -stop-ldap");
    build_progress("{stopping} {APP_OPENLDAP}",35);
    shell_exec("/etc/init.d/monit stop");
    build_progress("{stopping} {APP_OPENLDAP}",40);
    shell_exec("/etc/init.d/artica-status stop");
    build_progress("{stopping} {APP_OPENLDAP}",45);
    echo "Stopping Removing OpenLDAP database file\n";
    shell_exec("$rm -f /var/lib/ldap/*");
    build_progress("{importing}",50);
    $cmd="$slapadd -v -s -c -l $tmpfile -f /etc/ldap/slapd.conf";
    echo $cmd."\n";
    shell_exec($cmd);
    build_progress("{starting} {APP_OPENLDAP}",60);
    system("/usr/sbin/artica-phpfpm-service -start-ldap");
    build_progress("{starting} {APP_OPENLDAP}",70);
    shell_exec("/etc/init.d/monit start");
    build_progress("{starting} {APP_OPENLDAP}",80);
    shell_exec("/etc/init.d/artica-status start");

    if(!$NODELETE) {
        $target_file = "$target_directory/" . time() . ".gz";
        build_progress("{compressing}", 90);
        $unix->compress($tmpfile, $target_file);
        build_progress("{cleaning}", 95);
        $FTP_LIST = backup_cleaning();
        build_progress("{ftp_backup}", 96);
        if (!backup_ftp($FTP_LIST)) {
            build_progress("{ftp_backup} {failed}", 110);
            return false;
        }
    }
    build_progress("{done}",100);

    return true;

}

function perform_ldap_backup(){
    $unix=new unix();
    $pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
    $pid=$unix->get_pid_from_file($pidpath);

    if($unix->process_exists($pid)){
        build_progress("Already running PID $pid...",110);
        echo "Already running PID $pid...\n";
        die();
    }

    $target_directory="/home/artica/ldap_backup";
    if(!is_dir($target_directory)){@mkdir($target_directory,0755,true);}
    $slapcat=$unix->find_program("slapcat");
    $gzip=$unix->find_program("gzip");
    $nice=$unix->EXEC_NICE();
    $container_path="$target_directory/".time().".gz";
    $suffix=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
    echo "Current suffix: $suffix\n";

    build_progress("{backup} $container_path",50);
    $cmd=trim("$nice $slapcat -b \"$suffix\" -c -c -f /etc/ldap/slapd.conf|$gzip >$container_path 2>&1");
    exec($cmd,$results);


    build_progress("{cleaning}",90);
    $FTP_LIST=backup_cleaning();
    build_progress("{ftp_backup}",95);
    if(!backup_ftp($FTP_LIST)){
        build_progress("{ftp_backup} {failed}",110);
    }
    build_progress("{done}",100);
}

function backup_cleaning(): array{
    $list=get_containers_list();
    $target_directory="/home/artica/ldap_backup";
    $OpenDLAPBackupMaxContainers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenDLAPBackupMaxContainers"));
    if($OpenDLAPBackupMaxContainers==0){$OpenDLAPBackupMaxContainers=30;}

    $c=0;
    $FTP_LIST=array();
    foreach ($list as $filename=>$none){
        $container_path=$target_directory."/$filename";
        if(!is_file($container_path)){continue;}
        $c++;
        if($c<$OpenDLAPBackupMaxContainers){
            $FTP_LIST[]=$container_path;
            continue;}
        @unlink($container_path);
    }

    return $FTP_LIST;

}

function backup_ftp($FTP_LIST): bool{
    $OpenLDAPBackUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUpload"));
    if($OpenLDAPBackUpload==0){return true;}

    $unix=new unix();
    $hostname=$unix->hostname_g();


    $OpenLDAPBackUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPserv"));
    $OpenLDAPBackUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPusr"));
    $OpenLDAPBackUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPpass"));
    $OpenLDAPBackUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPDir"));
    $OpenLDAPBackUploadFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPPassive"));
    $OpenLDAPBackUploadFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPTLS"));
    $servlog="$OpenLDAPBackUploadFTPusr@$OpenLDAPBackUploadFTPserv";

    $REMOTE_DIR="$OpenLDAPBackUploadFTPDir/$hostname/ldap_backup";


    $ftp=new ftp_client($OpenLDAPBackUploadFTPserv,$OpenLDAPBackUploadFTPusr,$OpenLDAPBackUploadFTPpass);
    if($OpenLDAPBackUploadFTPPassive==1){$ftp->setPassive();}
    if($OpenLDAPBackUploadFTPTLS==1){$ftp->SetTLS();}

    if(!$ftp->connect()) {
        squid_admin_mysql(0, "LDAP Backup: {failed} {connecting} $servlog", $ftp->error,__FILE__,__LINE__);
        return false;
    }

    $ftp->mkdir("$OpenLDAPBackUploadFTPDir/$hostname");
    if(!$ftp->mkdir($REMOTE_DIR)){
        squid_admin_mysql(0, "LDAP Backup:  {$REMOTE_DIR} {permission_denied} $servlog", $ftp->error,__FILE__,__LINE__);
        return false;
    }

    $list=$ftp->ls($REMOTE_DIR);

    foreach ($list as $filename){
        $sname=basename($filename);
        $SRCLIST[$sname]=true;

    }
    foreach ($FTP_LIST as $srcfile){
        $tname=basename($srcfile);
        if(isset($SRCLIST[$tname])){continue;}
        echo "Backuping $srcfile\n";
        if(!$ftp->put($srcfile,"$REMOTE_DIR/$tname")){
            squid_admin_mysql(0, "LDAP Backup:  {$REMOTE_DIR}/{$tname} {permission_denied} $servlog", $ftp->error,__FILE__,__LINE__);
            return false;
        }

    }

    return true;
}


function get_containers_list(): array
{
        $unix=new unix();
        $target_directory   = "/home/artica/ldap_backup";
        $dir_handle         = @opendir($target_directory);
        $array              = array();

        if(!$dir_handle){return array();}

        while ($file = readdir($dir_handle)) {
            if($file=='.'){continue;}
            if($file=='..'){continue;}
            if(!is_file("$target_directory/$file")){continue;}

            if(preg_match("#^ldap\.ldif\.([0-9]+)$#",$file,$re)){
                $unix->compress("$target_directory/$file","$target_directory/{$re[1]}.gz");
                @unlink("$target_directory/$file");
                $array["{$re[1]}.gz"]="{$re[1]}.gz";
            }

            if(!preg_match("#^[0-9]+\.gz$#",$file)){continue;}
            $array[$file]=$file;
            continue;


        }
        @closedir($dir_handle);
        krsort($array);
        return $array;

}