#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["NO_GLOBAL_RELOAD"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["SLEEP"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}


$unix=new unix();
$sysct=$unix->find_program("sysctl");
$sync=$unix->find_program("sync");
$memory1=$unix->TOTAL_MEMORY_MB_USED();
shell_exec("$sync");
shell_exec("$sysct vm.drop_caches=3 >/dev/null 2>&1");
shell_exec("$sync");
sleep(2);
$memory2=$unix->TOTAL_MEMORY_MB_USED();
$free=$memory1-$memory2;
squid_admin_mysql(2, "Drop clean caches, dentries and inodes from memory {$free}M memory cleaned",
 "BASE", __FILE__, __LINE__, "system");