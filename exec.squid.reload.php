<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");


$unix=new unix();
if($unix->ServerRunSince()<3){exit();}
$squid=$unix->LOCATE_SQUID_BIN();
exec("/usr/sbin/artica-phpfpm-service -reload-proxy 2>&1",$results);
squid_admin_mysql(2, "[schedule]: Proxy service was reloaded...", @implode("\n", $results),__FILE__,__LINE__);