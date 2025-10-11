<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.compile_squid.inc');

$unix=new unix();
$GLOBALS["ROOT-DIR"]="/root/go-shield-server";
$version = $argv[1];
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$strip=$unix->find_program("strip");
$mkdir=$unix->find_program("mkdir");
$scp=$unix->find_program("scp");
$curl=$unix->find_program("curl");
$WORKDIR=$GLOBALS["ROOT-DIR"];
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
