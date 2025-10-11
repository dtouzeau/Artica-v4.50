<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="ImapBox Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build_accounts();exit();}
if($argv[1]=="--run"){$GLOBALS["OUTPUT"]=true;run();exit();}
if($argv[1]=="--scan"){$GLOBALS["OUTPUT"]=true;ScanMailBoxMember($argv[2]);exit();}
if($argv[1]=="--remove-mbx"){remove_mbx($argv[2]);exit;}
if($argv[1]=="--reconfigure"){reconfigure();}





function build_progress($pourc,$text){
    if(!is_dir("/usr/share/artica-postfix/ressources/logs/web")){@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);}
    $cachefile=PROGRESS_DIR."/imapbox.progress";

    if(is_numeric($text)){
        $array["POURC"]=$text;
        $array["TEXT"]=$pourc;
        echo "{$pourc}% $text\n";
        @file_put_contents($cachefile, serialize($array));
        @chmod($cachefile,0755);
        return;

    }
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "{$pourc}% $text\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function build_progress_remove($mbxid,$pourc,$text){
    if(!is_dir("/usr/share/artica-postfix/ressources/logs/web")){@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);}
    $cachefile=PROGRESS_DIR."/imapbox.$mbxid.progress";

    if(is_numeric($text)){
        $array["POURC"]=$text;
        $array["TEXT"]=$pourc;
        echo "{$pourc}% $text\n";
        @file_put_contents($cachefile, serialize($array));
        @chmod($cachefile,0755);
        return;

    }
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "{$pourc}% $text\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function remove_mbx($mailbox_id){
    $q          =   new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $ligne      =   $q->mysqli_fetch_array("SELECT * FROM mailboxes WHERE id='$mailbox_id'");
    $account_id =   intval($ligne["account_id"]);
    $unix=new unix();

    if($account_id==0){
        build_progress_remove($mailbox_id,110,"{failed} No account");
        return false;
    }

    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}
    $WORKDIR="$ImapBoxDirectory/$account_id/$mailbox_id";
    $rm=$unix->find_program("rm");
    if(is_dir($WORKDIR)){
        build_progress_remove($mailbox_id,50,"{removing} {messages}");
        shell_exec("$rm -rf $WORKDIR");
    }

    build_progress_remove($mailbox_id,90,"{removing} {settings}");
    $q->QUERY_SQL("DELETE FROM mailboxes WHERE id=$mailbox_id");
    if(!$q->ok){
        echo $q->mysql_error;
        build_progress_remove($mailbox_id,110,"{failed} $q->mysql_error");
        return false;
    }
    build_progress_remove($mailbox_id,100,"{success}");
    return true;
}

function uninstall(){
    $unix=new unix();
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}
    $rm=$unix->find_program("rm");
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress(10,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableImapBox",0);
    build_progress(50,"{uninstalling}");

    if(is_dir($ImapBoxDirectory)) {
        shell_exec("$rm -rf $ImapBoxDirectory");
    }

    build_progress(70,"{uninstalling}");
    shell_exec("/usr/share/artica-postfix/bin/articarest -phpini -debug");
    build_progress(80,"{uninstalling}");

    if(is_file("/etc/cron.d/imapbox")){
        @unlink("/etc/cron.d/imapbox-scan");
        @unlink("/etc/cron.d/imapbox");
        UNIX_RESTART_CRON();
    }

    build_progress(100,"{uninstalling} {done}");





}

function install(){
    $unix=new unix();
    build_progress(10,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableImapBox",1);
    build_progress(50,"{installing}");
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("/usr/share/artica-postfix/bin/articarest -phpini -debug");
    reconfigure();
    build_progress(100,"{installing} {done}");

}

function run(){
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}
    build_accounts();
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $sql="SELECT * FROM accounts WHERE enabled=1 order by userid";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne){
        $userid=$ligne["userid"];
        $WORKDIR="$ImapBoxDirectory/$userid";
        echo "Userid: {$ligne["userid"]} --> $WORKDIR\n";
        if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}
        run_user($ligne["id"]);
    }
}

function ScanMailBoxUsers(){
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $sql="SELECT * FROM accounts WHERE enabled=1 order by userid";
    $results = $q->QUERY_SQL($sql);
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}


    foreach ($results as $index=>$ligne){
        $userid=$ligne["userid"];
        $WORKDIR="$ImapBoxDirectory/$userid";
        echo "Userid: {$ligne["userid"]} --> $WORKDIR\n";
        if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}
        ScanMailBoxMember($ligne["id"]);

    }
}

function ScanMailBoxMember($userid){
    $unix=new unix();
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    $ImapBoxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDays"));
    if($ImapBoxDays==0){$ImapBoxDays=365;}
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}
    $WORKDIR="$ImapBoxDirectory/$userid";
    if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}

    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");

    if(!$q->FIELD_EXISTS("mailboxes","messages")){
        $q->QUERY_SQL("ALTER TABLE mailboxes ADD `messages` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("mailboxes","scanned")){
        $q->QUERY_SQL("ALTER TABLE mailboxes ADD `scanned` INTEGER NOT NULL DEFAULT 0");
    }

    $sql="SELECT * FROM mailboxes WHERE account_id='$userid' AND enabled=1 order by hostname ";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne) {
        $mailboxid = $ligne["id"];
        $mailbox_workdir = "$WORKDIR/$mailboxid/messages/new";
        $mailbox_datadir = "$WORKDIR/$mailboxid/scanned";
        if (!is_dir($mailbox_workdir)) {
            echo "$mailbox_workdir no such directory\n";
            continue;
        }
        $t1 = time();
        echo "Scanning backup storage $mailbox_workdir\n";
        $workdir_status=ScanSize($mailbox_workdir,$mailbox_datadir);
        $t2 = time();
        $t3 = $unix->distanceOfTimeInWords($t1,$t2,true);
        echo "Scan took $t3\n";
        print_r($workdir_status);
        $FileNum=$workdir_status["MESSAGES_NUMBER"];
        $Completed=$workdir_status["SCANNED"];
        $size=$workdir_status["SIZE"];
        $q->QUERY_SQL("UPDATE mailboxes SET messages=$FileNum,scanned=$Completed,database_size=$size WHERE id=$mailboxid");

    }


}


function reconfigure(){
    $unix=new unix();
    $ImapBoxSchedules=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxSchedules"));
    if($ImapBoxSchedules==0){$ImapBoxSchedules=120;}

    $Timez[5] ="*/5 * * * *";
    $Timez[10]="*/10 * * * *";
    $Timez[15]="*/15 * * * *";
    $Timez[30]="*/30 * * * *";
    $Timez[60]="0 * * * * *";
    $Timez[120]="0 */2 * * *";
    $Timez[180]="0 */3 * * *";
    $Timez[360]="0 */6 * * *";
    $Timez[720]="0 */12 * * * ";
    $Timez[1440]="0  1 * * *";
    $Timez[2880]="0  1 */2 * *";

    //
    $schedule=$Timez[$ImapBoxSchedules];
    echo "Schedule $schedule\n";

    $unix->Popuplate_cron_make("imapbox",$schedule,basename(__FILE__)." --run");
    $unix->Popuplate_cron_make("imapbox-scan","*/5 * * * *",basename(__FILE__)." --scan");


    echo "Schedule Restarting cron..\n";
    UNIX_RESTART_CRON();


}

function build_accounts(){
    $unix=new unix();
    $wkhtmltopdf=$unix->find_program("wkhtmltopdf");
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    $ImapBoxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDays"));
    if($ImapBoxDays==0){$ImapBoxDays=365;}
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}


    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $sql="SELECT * FROM mailboxes WHERE enabled=1 order by ID DESC";
    $results = $q->QUERY_SQL($sql);
    if(!is_dir("/etc/imapbox")){@mkdir("/etc/imapbox");}

    $SUBs=array("cur","new","tmp");

    foreach ($results as $index=>$ligne){
        $account_id=$ligne["account_id"];
        $id=$ligne["id"];


        $WORKDIR="$ImapBoxDirectory/$account_id/$id/messages";
        foreach ($SUBs as $subdir) {
            if (!is_dir("$WORKDIR/$subdir")) {
                @mkdir("$WORKDIR/$subdir", 0755, true);

            }
            chown("$WORKDIR/$subdir","www-data");
            chgrp("$WORKDIR/$subdir","www-data");
        }

        $username=$ligne["username"];
        $hostname=$ligne["hostname"];
        $remote_folder=$ligne["remote_folder"];
        $remote_port=$ligne["remote_port"];
        $password=$ligne["password"];
        echo "Building $username@$hostname:$remote_port/$remote_folder\n";
        $f=array();
        $f[]="[retriever]";
        if($remote_port==143){
        $f[]="type = SimpleIMAPRetriever";
        }
        if($remote_port==993){
            $f[]="type = SimpleIMAPSSLRetriever";
        }
        $f[]="server    = $hostname";
        $f[]="username  = $username";
        $f[]="password  = $password";
        $f[]="port      = $remote_port";

        $f[]="use_peek  = true";
        if($remote_folder=="__ALL__"){
            $f[]="mailboxes = ALL";
        }



        $f[]="";
        $f[]="[destination]";
        $f[]="type = Maildir";
        $f[]="path = $WORKDIR/";
        $f[]="user = www-data";
        $f[]="";
        $f[]="[options]";
        $f[]="message_log_syslog = True";
        echo "/etc/imapbox/config{$account_id}.cfg done...\n";
        @file_put_contents("/etc/imapbox/config{$account_id}.cfg",@implode("\n",$f));

    }





}

function installScripts(){
    $SourceDir="/usr/share/artica-postfix/bin/install";
    $DestDir="/opt/imapbox";
    if(!is_dir($DestDir)){@mkdir($DestDir);}

    $files[]="mailboxresource.py";
    $files[]="message.py";
    $files[]="imapbox.py";

    foreach ($files as $filename){
        $md5b=null;
        $SourceFile="$SourceDir/$filename";
        $DestFile="$DestDir/$filename";
        if(!is_file($SourceFile)){continue;}
        $md5a=md5_file($SourceFile);
        if(is_file($DestFile)){$md5b=md5_file($DestFile);}
        if($md5a==$md5b){continue;}
        if(is_file($DestFile)){@unlink($DestFile);}
        @copy($SourceFile,$DestFile);

    }



}

function mailboxpid($mailboxid){
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    echo "Find Process with mailbox id:$mailboxid\n";
    exec("$pgrep -l -f \"getmail.*?config{$mailboxid}\.cfg\" 2>&1",$results);
    foreach ($results as $line){
        echo "Detects....$line\n";
        $line=trim($line);
        if(!preg_match("#^([0-9]+)\s+.*?(getmail|python)#",$line,$re)){continue;}
        echo "Find Process $line success\n";
        return $re[1];
    }
}

function run_user($userid){
    $unix=new unix();
    installScripts();
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    $ImapBoxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDays"));
    if($ImapBoxDays==0){$ImapBoxDays=365;}
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}
    $WORKDIR="$ImapBoxDirectory/$userid/messages";
    if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}

    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $sql="SELECT * FROM mailboxes WHERE account_id='$userid' AND enabled=1 order by hostname ";
    $results = $q->QUERY_SQL($sql);

    $getmail=$unix->find_program("getmail");
    $nohup=$unix->find_program("nohup");
    $python=$unix->find_program("python");
    $imapbox="$python $getmail";

    foreach ($results as $index=>$ligne){
        $mailboxid=$ligne["id"];
        $mailboxdir="$WORKDIR/";
        if(!is_dir($mailboxdir)){@mkdir($mailboxdir,0755,true);}
        $pid=mailboxpid($mailboxid);
        if($unix->process_exists($pid)){continue;}
        echo "Run in Directory $mailboxdir\n";
        $cmdline=array();
        $cmdline[]="$nohup $imapbox";
        $cmdline[]="--getmaildir=/etc/imapbox --rcfile /etc/imapbox/config{$mailboxid}.cfg --dont-delete";
        $cmdline[]=">/dev/null 2>&1 &";
        $cmdlineF=@implode(" ",$cmdline);
        echo $cmdlineF."\n";
        $unix->ToSyslog("Executing mailbox id: [$mailboxid]",false,"imapbox");
        system($cmdlineF);

    }
}

function ScanSize($directory,$mailbox_datadir){
    $size=0;
    $FileNum=0;
    $Completed=0;$c=0;$t1=0;$t2=0;$All=0;
    $handle = opendir($directory);if(!$handle){return false;}
    $t1=time();
    while (false !== ($filename = readdir($handle))) {
        $c++;
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $All++;
        $targetFile="$directory/$filename";
        if(is_dir($targetFile)){continue;}
        if($GLOBALS["VERBOSE"]) {
            $sizeKB=$size/1024;
            $SizeMB=$sizeKB/1024;
            if ($c > 1500) {
                $t2=time();
                $Secs=$t2-$t1;
                $msgs=$All/$Secs;
                echo "Scanned: $All message(s) {$SizeMB}MB ($msgs/s)\n";
                $c=0;
            }
        }
        if(!preg_match("#^(.+?).mailbackup#",$filename)){continue;}
        $size=$size+@filesize($targetFile);
        if(is_dir("$mailbox_datadir/$filename")){$Completed++;continue;}
        $FileNum++;
    }

    $array["MESSAGES_NUMBER"]=$FileNum;
    $array["SCANNED"]=$Completed;
    $array["SIZE"]=$size;

}

function ScanQueue(){
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}
    $ImapBoxDirectory_regex=str_replace("/","\/",$ImapBoxDirectory);
    $c=0;
    $directory="/var/log/artica-postfix/imapbox-queue";
    if(!is_dir($directory)){@mkdir($directory,0755,true);}
    if (!$handle = opendir($directory)) {return;}
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        $tfile="$directory/$file";
        if (is_dir($tfile)) {continue;}
        $mailbox_path=@file_get_contents($tfile);
        echo "$tfile: $mailbox_path\n";
        if(!preg_match("#$ImapBoxDirectory_regex.*?\/([0-9]+)\/messages\/([0-9]+)/([0-9]+)/(.+)#",$mailbox_path,$re)){
            echo "Could not understand $mailbox_path\n";
            continue;
        }
        $userid=$re[1];
        $mailbox_id=$re[2];
        $year=$re[3];
        $message_md5=$re[4];
        $MailBoxPath="$ImapBoxDirectory/$userid/messages/$mailbox_id";
        $RefreshScan[$mailbox_id]=$MailBoxPath;

        $c++;
        echo "Account id: $userid, mailbox id=$mailbox_id, year: $year, message $message_md5\n";
        omindex($ImapBoxDirectory,$userid,$mailbox_path);
        @unlink($tfile);
    }
    $unix=new unix();
    if($c>0) {
        $unix->ToSyslog("Archived $c new message(s)", false, "imapbox");
        ScanSize($RefreshScan);
    }

}

function omindex($ImapBoxDirectory,$userid,$DirToScan){
    $unix=new unix();
    $nice=$unix->EXEC_NICE();
    $omindex=$unix->find_program("omindex");
    $databasePath="$ImapBoxDirectory/$userid.db";
    $filelist2omega="$ImapBoxDirectory/filelist2omega.script";
    if(!is_file($filelist2omega)){filelist2omega($ImapBoxDirectory);}
    $databasePath="$ImapBoxDirectory/$userid.db";
    $cmd="$nice{$omindex} -l 2 --follow -D $databasePath -U \"$DirToScan\" \"$DirToScan\"";
    echo "$cmd\n";
    exec($cmd,$results);
    foreach ($results as $line){
        echo $line."\n";
    }


}

function filelist2omega($ImapBoxDirectory){
    $f[]="nid: index field=nid";
    $f[]="url : index field=id field=url";
    $f[]="name : weight=3 indexnopos hash field=name";
    $f[]="path : indexnopos field=path";
    $f[]="format : index field=format";
    $f[]="size : index field=size";
    $f[]="modtime : index field=modtime\n";
    @file_put_contents("$ImapBoxDirectory/filelist2omega.script", @implode("\n", $f));
}



?>