<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}




$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){exit();}


@file_put_contents($pidfile, getmypid());

$mv=$unix->find_program("mv");
$ln=$unix->find_program("ln");
$cp=$unix->find_program("cp");
$rm=$unix->find_program("rm");
$dir=base64_decode($argv[1]);
if($dir=="/var/spool"){return;}
if(!is_dir($dir)){@mkdir($dir,0755,true);}


if(!is_link("/var/spool")){
	writelogs("move /var/spool to /var/spool.moved","MAIN",__FILE__,__LINE__);
	shell_exec("mv /var/spool /var/spool.moved");
	writelogs("link $dir to /var/spool","MAIN",__FILE__,__LINE__);
	shell_exec("$ln -s $dir /var/spool");
	writelogs("copy /var/spool.moved/* to $dir/","MAIN",__FILE__,__LINE__);
	shell_exec("$cp -rfp /var/spool.moved/* $dir/");
	writelogs("remove /var/spool.moved","MAIN",__FILE__,__LINE__);
	shell_exec("$rm -rf /var/spool.moved");
	exit();
}
$SourceDir=readlink("/var/spool");
if(is_dir("/var/spool.moved")){
	shell_exec("$cp -rfp /var/spool.moved/* $SourceDir/");
	shell_exec("$rm -rf /var/spool.moved");
}


writelogs("Source Dir $SourceDir","MAIN",__FILE__,__LINE__);
writelogs("unlink /var/spool","MAIN",__FILE__,__LINE__);
@unlink("/var/spool");
writelogs("link $dir to /var/spool","MAIN",__FILE__,__LINE__);
shell_exec("$ln -s $dir /var/spool");
writelogs("copy $SourceDir/* to $dir/","MAIN",__FILE__,__LINE__);
shell_exec("$cp -rfp $SourceDir/* $dir/");
writelogs("remove $SourceDir","MAIN",__FILE__,__LINE__);
shell_exec("$rm -rf $SourceDir");
?>