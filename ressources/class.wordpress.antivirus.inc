<?php



function ScanBadPhpFiles(){
    $WORKPATH="/home/wordpress_sites";
    if(!is_dir($WORKPATH)){return true;}

    $sfiles=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/databases/wordpress.php.name.db"));
    foreach ($sfiles as $line){
        $sourcesfiles[trim($line)]=true;
    }
    $unix=new unix();
    $logs=array();
    $find=$unix->find_program("find");
    $rm=array();
    if (!$handle = opendir($WORKPATH)) {return;}
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        $subdir="$WORKPATH/$file";
        if (!is_dir($subdir)) {continue;}
        if(!preg_match("#site([0-9]+)#",$file)){continue;}
        $results=array();
        if($GLOBALS["VERBOSE"]){echo "Scanning $subdir\n";}
        exec("$find $subdir -name '*.php' 2>&1",$results);

        foreach ($results as $filepath){
            $filepath=trim($filepath);
            if(preg_match("#uploads\/cache\/wpml\/twig\/#",$filepath)){continue;}
            $phpname=basename($filepath);
            if(!isset($sourcesfiles[$phpname])){
                if($GLOBALS["VERBOSE"]){echo "Suspicious filename $filepath\n";}
                $created=filemtime($filepath);
                $logs[]="Suspicious filename (created on ".date("Y-m-d H:i:s",$created). ") $filepath";
                $rm[]="rm $filepath";
            }
        }

    }

    if(count($logs)==0){return true;}
    $NumberOfFiles=count($logs);

    squid_admin_mysql(0,"$NumberOfFiles suspicious files",
        @implode("\n",$logs)."\n".@implode("\n",$rm),
        __FILE__,__LINE__);

    return true;
}

function CleanViruses($aspid=false):bool{

    $unix=new unix();
    $pidfile    = "/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$aspid){
    $pid        = @file_get_contents($pidfile);
    if($unix->process_exists($pid)){
        wordpress_av_syslog("CleanViruses: Instance PID $pid already running, Aborting");
        return false;
        }

    }
    @file_put_contents($pidfile,getmypid());

    if(!is_file("/etc/cron.d/wordpress-antivirus")){
        $unix->Popuplate_cron_make("wordpress-antivirus","0 */2 * * *","exec.wordpress.install.php --antivirus");
        UNIX_RESTART_CRON();
    }

    $find=$unix->find_program("find");
    $grep=$unix->find_program("grep");
    $WORKPATH="/home/wordpress_sites";
    if(!is_dir($WORKPATH)){return true;}

    wordpress_av_syslog("CleanViruses: find ico files in $WORKPATH..");
    exec("$find $WORKPATH/|$grep -E \"\/..*?\.ico\" 2>&1",$results);

    foreach ($results as $path){
        $basename=basename($path);
        if(!is_file($path)){continue;}
        $created=filemtime($path);
        $date=date("Y-m-d H:i:s",$created);
        echo "{Suspicious} file $basename discovered ($path)\n";
        squid_admin_mysql(0,"{Suspicious} file $basename created on $date discovered ","Found a bad file here:$path created on $date");
        @unlink($path);


    }

    $NOTS=array();
    $pids=$unix->PIDOF_PATTERN_ALL("cron.php -e0");
    if(count($pids)>0) {
        foreach ($pids as $pid => $ligne) {
            $time = $unix->PROCESS_TTL($pid);
            $cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
            $NOTS[]="Pid $pid [$cmdline] ($time minutes)";
           
        }
        squid_admin_mysql(0,"INFECTED: ".count($NOTS)." processes killed",@implode("\n",$NOTS),
            __FILE__,__LINE__);
    }
    $handle = opendir($WORKPATH);
    while (false !== ($fileZ = readdir($handle))) {
        if ($fileZ == ".") {continue;}
        if ($fileZ == "..") {continue;}
        $path = "$WORKPATH/$fileZ";
        if (!is_dir($path)) {continue;}
        if($fileZ=="DisabledSites"){continue;}
        ScanWordpressRoot($path);
        ScanWpContent($path);
        nophpindir("$WORKPATH/wp-content/themes/dt-the7/images");
    }

    $BAD_PROCESSES[]="vnfyx";
    $BAD_PROCESSES[]="ontphyb";
    foreach ($BAD_PROCESSES as $pprc)
    $pid=$unix->PIDOF($pprc);
    if($unix->find_program($pid)) {
        $time = $unix->PROCESS_TTL($pid);
        $unix->KILL_PROCESS($pid);
        squid_admin_mysql("{Suspicious} {process} killed: $pprc ({running} {since} $time {minutes})",null,
            __FILE__,__LINE__);
    }
    $ps=$unix->find_program("ps");
    $TT=array();
    exec("$ps aux 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(!preg_match("#^www-data\s+([0-9]+)\s+[0-9\.]+\s+[0-9\.]+.*?\s+\s+.*?\s+.*?\s+.*?\s+(.+)#",$line,$re)){
            continue;
        }
        $pid=$re[1];
        $time = $unix->PROCESS_TTL($pid);
        if(!is_file("/proc/$pid/cmdline")){continue;}
        $cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
        echo "[$pid]: <$cmdline>\n";
        if(preg_match("#^(nginx|php-fpm|artica-webconsole|proftpd:)#",$cmdline)){continue;}
        $TT[]="Pid: $pid ($time minutes) [$cmdline]";

    }

    if(count($TT)>0){
        $CountOfProcesses=count($TT);
        squid_admin_mysql(0,"$CountOfProcesses {Suspicious} {processes} found in memory",
            @implode("\n",$TT),
            __FILE__,__LINE__);
    }


    ScanBadPhpFiles();
    return true;
}

function wordpress_av_syslog($text):bool{
    if(!function_exists("openlog")){return true;}
    openlog("wordpress", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function ScanWpContent($WORKPATH){
    $ALLOWED_PATHS["backups-dup-lite"]=true;
    $ALLOWED_PATHS["languages"]=true;
    $ALLOWED_PATHS["mu-plugins"]=true;
    $ALLOWED_PATHS["plugins"]=true;
    $ALLOWED_PATHS["themes"]=true;
    $ALLOWED_PATHS["upgrade"]=true;
    $ALLOWED_PATHS["uploads"]=true;
    $ALLOWED_PATHS["gallery"]=true;
    $ALLOWED_PATHS["cache"]=true;
    $ALLOWED_PATHS["wflogs"]=true;
    $ALLOWED_PATHS["mu-plugins-old"]=true;
    $ALLOWED_PATHS["et-cache"]=true;
    $ALLOWED_PATHS["wp-rocket-config"]=true;
    $ALLOWED_PATHS["updraft"]=true;
    $ALLOWED_PATHS["ai1wm-backups"]=true;
    $ALLOWED_PATHS["ngg"]=true;
    $ALLOWED_PATHS["NewFolder"]=true;
    $ALLOWED_PATHS["log"]=true;
    $ALLOWED_PATHS["logs"]=true;
    $ALLOWED_PATHS["upgrade-temp-backup"]=true;
    $ALLOWED_PATHS["aiowps_backups"]=true;
    $WorkDir="$WORKPATH/wp-content";
    if(!is_dir($WorkDir)){return true;}
    $handle = opendir($WorkDir);
    wordpress_av_syslog("ScanWpContent: Scan Wordpress content $WORKPATH");
    while (false !== ($fileZ = readdir($handle))) {
        $fileZ=trim($fileZ);
        if ($fileZ == ".") {continue;}
        if ($fileZ == "..") {continue;}
        if ($fileZ == null) {continue;}
        $path = "$WorkDir/$fileZ";
        if (!is_dir($path)) {continue;}
        if(!$ALLOWED_PATHS[$fileZ]){
            if($GLOBALS["VERBOSE"]){echo "Suspicious file [$WorkDir]/[$fileZ]\n";}
            wordpress_av_syslog("ScanWpContent: Suspicious directory [$WorkDir]/[$fileZ]");
            squid_admin_mysql(0,"{suspicious} {directory} $path found",null,__FILE__,__LINE__);
        }
    }


}



function nophpindir($WORKPATH){

    if(!is_dir($WORKPATH)){return true;}
    echo "Scanning $WORKPATH\n";
    $handle = opendir($WORKPATH);
    while (false !== ($fileZ = readdir($handle))) {
        if ($fileZ == ".") {
            continue;
        }
        if ($fileZ == "..") {
            continue;
        }
        $path = "$WORKPATH/$fileZ";
        if (is_dir($path)) {
            continue;
        }
        if (!preg_match("#\.php$#", $fileZ)) {
            continue;
        }
        squid_admin_mysql(0, "{Suspicious} $fileZ in $WORKPATH", null, __FILE__, __LINE__);
    }

    return true;
}


function ScanWordpressRoot($WORKPATH){
    echo "Scanning $WORKPATH\n";
    $unix=new unix();
    $rm=$unix->find_program("rm");
    if(is_file("$WORKPATH/installer.php")){
        if(!is_file("$WORKPATH/index.php")){
            return true;
        }
    }

    $handle = opendir($WORKPATH);
    $ALLOWED_PATHS["wp-admin"]=true;
    $ALLOWED_PATHS["wp-content"]=true;
    $ALLOWED_PATHS["wp-includes"]=true;
    $ALLOWED_FILES["index.php"]=true;
    $ALLOWED_FILES["wp-blog-header.php"]=true;
    $ALLOWED_FILES["wp-config-sample.php"]=true;
    $ALLOWED_FILES["wp-links-opml.php"]=true;
    $ALLOWED_FILES["wp-login.php"]=true;
    $ALLOWED_FILES["wp-settings.php"]=true;
    $ALLOWED_FILES["wp-trackback.php"]=true;
    $ALLOWED_FILES["wp-activate.php"]=true;
    $ALLOWED_FILES["wp-comments-post.php"]=true;
    $ALLOWED_FILES["wp-cron.php"]=true;
    $ALLOWED_FILES["wp-load.php"]=true;
    $ALLOWED_FILES["wp-mail.php"]=true;
    $ALLOWED_FILES["wp-signup.php"]=true;
    $ALLOWED_FILES["xmlrpc.php"]=true;

    $ALLOWED_FILES["wp-activate.php"]=true;
    $ALLOWED_FILES["wp-app.php"]=true;
    $ALLOWED_FILES["wp-blog-header.php"]=true;
    $ALLOWED_FILES["wp-comments-post.php"]=true;
    $ALLOWED_FILES["wp-config-sample.php"]=true;
    $ALLOWED_FILES["wp-cron.php"]=true;
    $ALLOWED_FILES["wp-links-opml.php"]=true;
    $ALLOWED_FILES["wp-load.php"]=true;
    $ALLOWED_FILES["wp-login.php"]=true;
    $ALLOWED_FILES["wp-mail.php"]=true;
    $ALLOWED_FILES["wp-pass.php"]=true;
    $ALLOWED_FILES["wp-register.php"]=true;
    $ALLOWED_FILES["wp-settings.php"]=true;
    $ALLOWED_FILES["wp-signup.php"]=true;
    $ALLOWED_FILES["wp-trackback.php"]=true;
    $ALLOWED_FILES["xmlrpc.php"]=true;
    $ALLOWED_FILES["wp-config.php"]=true;
    $ALLOWED_FILES["wp-artica.php"]=true;
    $ALLOWED_FILES[".htaccess"]=true;
    $ALLOWED_FILES["robots.txt"]=true;
    $ALLOWED_FILES["nginx.conf"]=true;
    $ALLOWED_FILES["readme.html"]=true;
    $ALLOWED_FILES["license.txt"]=true;

    while (false !== ($fileZ = readdir($handle))) {
        if ($fileZ == ".") {continue;}
        if ($fileZ == "..") {continue;}
        $path = "$WORKPATH/$fileZ";
        if (!is_dir($path)) {continue;}

        $suspicious1="$path/wp-content/plugins/contact-widgeismhm/vnfyx";
        if(is_dir($suspicious1)){
            $created=filemtime("$suspicious1/.");
            $sdate=date("Y-m-d H:i:s",$created);
            $pid=$unix->PIDOF("vnfyx");
            if($unix->find_program($pid)) { $unix->KILL_PROCESS($pid); }
            squid_admin_mysql(0,"Malware found in directory wp-content/plugins/contact-widgeismhm created on $sdate discovered ",
                "Found a bad directory here:$path it was removed.");
            shell_exec("$rm -f $suspicious1");
        }


        if(!isset($ALLOWED_PATHS[$fileZ])) {
            $created=filemtime($path);
            $sdate=date("Y-m-d H:i:s",$created);
            echo "Suspicous directory $path created on $sdate\n";
            squid_admin_mysql(0,"{Suspicious} directory $fileZ created on $sdate discovered ",
                "Found a bad directory here:$path it was removed.");
            shell_exec("$rm -rf $path");
        }
    }

    if(!is_dir("/home/wordpress_suspicious")){
        @mkdir("/home/wordpress_suspicious",0755,true);
    }
    $backupedDir="/home/wordpress_suspicious/".basename($WORKPATH);
    $handle = opendir($WORKPATH);
    while (false !== ($fileZ = readdir($handle))) {
        if ($fileZ == ".") {
            continue;
        }
        if ($fileZ == "..") {
            continue;
        }
        $path = "$WORKPATH/$fileZ";
        if (!is_file($path)) {
            continue;
        }



        $chattr=$unix->find_program("chattr");
        if (!isset($ALLOWED_FILES[$fileZ])) {
            $created = filemtime($path);
            $sdate = date("Y-m-d H:i:s", $created);
            $finalBackupDir="$backupedDir/".date("Y-m-d-H");
            if(!is_dir($finalBackupDir)){@mkdir($finalBackupDir,0755,true);}
            @copy($path,"$finalBackupDir/$fileZ");
            $unix->ToSyslog("{Suspicious} file $fileZ in wordress root $WORKPATH directory created on $sdate","wordpress");
            squid_admin_mysql(0,"{Suspicious} file $fileZ in wordress root $WORKPATH directory created on $sdate",
                "Found a bad file here:$path (it was removed.)");
            shell_exec("$chattr -i $path");
            @unlink($path);
        }
    }

}
