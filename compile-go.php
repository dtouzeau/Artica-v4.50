<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.compile_squid.inc');


if (empty($argv[2])){
    echo "No version set, exit";
    die();
}
if (empty($argv[1])){
    echo "No pakcage set, exit";
    die();
}
$GLOBALS["ver"]=$argv[2];
echo $GLOBALS["ver"];
//Go Shield Server
if($argv[1]=="--go-shield-server"){compile_go_shield_server();exit;}
//Go Shield Connector
if($argv[1]=="--go-shield-connector"){compile_go_shield_connector();exit;}
//Go Exec
if($argv[1]=="--go-exec"){compile_go_exec();exit;}
//Go FS
if($argv[1]=="--go-fs"){compile_go_fs();exit;}
//Go AD Groups
if($argv[1]=="--go-ad-groups"){compile_go_ad_groups();exit;}
//GO AD Agent Connector
if($argv[1]=="--go-ad-agent-connector"){compile_go_ad_ad_agent_connector();exit;}
//Go Hotspot
if($argv[1]=="--go-hotspot"){compile_go_hotspot();exit;}
//Go Pac
if($argv[1]=="--go-pac"){compile_go_pac();exit;}
//Go Webfilter Error Pages
if($argv[1]=="--go-webfilter-error-pages"){compile_go_webfilter_error_page();exit;}
//Go Failover Checker
if($argv[1]=="--go-failover-checker"){go_failover_checker();exit;}
function compile_go_shield_server(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-shield-server";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/server/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/server/bin/go-shield-server $WORKDIR/usr/share/artica-postfix/bin/go-shield/server/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-goshield");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}


function compile_go_shield_connector(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-shield-connector";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/client/external_acl_first/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/client/external_acl_first/bin/go-shield-connector $WORKDIR/usr/share/artica-postfix/bin/go-shield/client/external_acl_first/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-goshieldconnector");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}

function compile_go_exec(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-exec";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/exec/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/exec/go-exec $WORKDIR/usr/share/artica-postfix/bin/go-shield/exec/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/exec/go-forker $WORKDIR/usr/share/artica-postfix/bin/go-shield/exec/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-goexec");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}


function compile_go_fs(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-fs";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/fs-watcher/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/fs-watcher/bin/go-shield-server-fs-watcher $WORKDIR/usr/share/artica-postfix/bin/go-shield/fs-watcher/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-goshieldfs");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}

function compile_go_ad_ad_agent_connector(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-ad-agent-connector";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/client/external_acls_gc/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/client/external_acls_gc/bin/external_acls_ad_agent $WORKDIR/usr/share/artica-postfix/bin/go-shield/client/external_acls_gc/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-goadagentconnector");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
}
function compile_go_ad_groups(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-ad-groups";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/client/external_acls_ldap/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/client/external_acls_ldap/bin/go-squid-auth $WORKDIR/usr/share/artica-postfix/bin/go-shield/client/external_acls_ldap/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-goadgroups");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}

function compile_go_hotspot(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-hotspot";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/hotspot-web $WORKDIR/usr/share/artica-postfix/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-gohotspot");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}


function compile_go_pac(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-pac";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/proxy-pac $WORKDIR/usr/share/artica-postfix/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-gopac");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}

function go_failover_checker(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-failover-checker";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/go-shield/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/go-shield/go-failover-checker $WORKDIR/usr/share/artica-postfix/bin/go-shield/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-gofailoverchecker");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
}

function compile_go_webfilter_error_page(){
    $version = $GLOBALS["ver"];
    $unix=new unix();
    $wget=$unix->find_program("wget");
    $tar=$unix->find_program("tar");
    $rm=$unix->find_program("rm");
    $cp=$unix->find_program("cp");
    $strip=$unix->find_program("strip");
    $mkdir=$unix->find_program("mkdir");
    $scp=$unix->find_program("scp");
    $curl=$unix->find_program("curl");
    $WORKDIR="/root/go-webfilter-error-page";
    $targtefile="$version.tar.gz";
    echo "version $version\n";
    $f[]="rm -rf $WORKDIR";
    $f[]="rm -rf /root/$targtefile";
    $f[]="$mkdir -p $WORKDIR";
    $f[]="$mkdir -p $WORKDIR/usr/share/artica-postfix/bin/";
    $f[]="$cp -fvd /usr/share/artica-postfix/bin/artica-error-page $WORKDIR/usr/share/artica-postfix/bin/";
    foreach ($f as $line){
        echo "$line\n";
        system($line);
    }
    echo "Creating package done....\n";
    echo "Going to $WORKDIR\n";
    @chdir("$WORKDIR");
    echo "Compressing $targtefile\n";
    if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
    shell_exec("tar -czf /root/$targtefile *");
    echo "Compressing /root/$targtefile Done...\n";
    echo "Uploading to remote server\n";
    shell_exec("$scp /root/$targtefile root@37.187.156.120:/home/www.artica.fr/download/Debian10-gowebfiltererrorpage");
    echo "Refreshing index\n";
    shell_exec("$curl http://www.articatech.net/v4softs-debian10.php?verbose=yes");

    $f[]="rm -rf /root/$targtefile";
//scp file.txt remote_username@10.10.0.2:/remote/directory
}