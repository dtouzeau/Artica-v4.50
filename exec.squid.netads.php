<?php

$GLOBALS["DEBUG_INCLUDES"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');


$unix=new unix();
$sock=new sockets();

$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
$net=$unix->find_program("net");

$f[]="#!/usr/bin/perl -w";
$f[]="#";
$f[]="# external_acl helper to Squid to verify NT Domain group";
$f[]="# membership using \"net ads user info\"";
$f[]="#";
$f[]="# This program is put in the public domain by Jerry Murdock ";
$f[]="# <jmurdock@itraktech.com>. It is distributed in the hope that it will";
$f[]="# be useful, but WITHOUT ANY WARRANTY; without even the implied warranty";
$f[]="# of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.";
$f[]="#";
$f[]="# Author:";
$f[]="#   Jerry Murdock <jmurdock@itraktech.com>";
$f[]="#";
$f[]="# Version history:";
$f[]="#   2002-07-05 Jerry Murdock <jmurdock@itraktech.com>";
$f[]="#		Initial release";
$f[]="#";
$f[]="#   2005-07-05 Joe Cooper <joe@swelltech.com>";
$f[]="#		converted to net ads use from wbinfo, which broke for";
$f[]="#		some reason";
$f[]="";
$f[]="# external_acl uses shell style lines in it's protocol";
$f[]="require 'shellwords.pl';";
$f[]="";
$f[]="# Disable output buffering";
$f[]="\$|=1;           ";
$f[]="";
$f[]="# User and password for net ads commands";
$f[]="\$adsuser=\"{$array["WINDOWS_SERVER_ADMIN"]}\";";
$f[]="\$adspass=\"{$array["WINDOWS_SERVER_PASS"]}\";";
$f[]="";
$f[]="sub debug {";
$f[]="	# Uncomment this to enable debugging";
$f[]="	#print STDERR \"@_\\n\";";
$f[]="}";
$f[]="";
$f[]="#";
$f[]="# Check if a user belongs to a group";
$f[]="#";
$f[]="sub check {";
$f[]="        local(\$user, \$group) = @_;";
$f[]="        &debug (\"Got user: \$user and group: \$group\");";
$f[]="		  if(index(\$user, '\\\\')>0){";
$f[]="				(\$domain, \$user) = split(/".'\\\\'."/, \$user);";
$f[]="				&debug (\"Now user: \$user and domain: \$domain\");";
$f[]="			}";
$f[]="";
$f[]="		  if(index(\$user, '/')>0){";
$f[]="				(\$domain, \$user) = split('/', \$user);";
$f[]="				&debug (\"Now user: \$user and domain: \$domain\");";
$f[]="			}";
$f[]="";
$f[]="		  my \$regex='^'.	\$group.'$';";	  
$f[]="        return 'OK' if(`$net ads user info \$user -U \$adsuser%\$adspass`  =~ /\$regex"."/"."m);";
$f[]="        return 'ERR';";
$f[]="}";

$f[]="";
$f[]="#";
$f[]="# Main loop";
$f[]="#";
$f[]="while (<STDIN>) {";
$f[]="        chop;";
$f[]="	&debug (\"Got \$_ from squid\");";
$f[]="        (\$user, \$group) = &shellwords;";
$f[]="	\$ans = &check(\$user, \$group);";
$f[]="	&debug (\"Sending \$ans to squid\");";
$f[]="	print \"\$ans\\n\";";
$f[]="}";
$f[]="";
@file_put_contents("/etc/squid3/net_ads_group.pl", @implode("\n", $f));
@mkdir("/etc/squid3",0755,true);
@chmod("/etc/squid3/net_ads_group.pl",0755);
@chown("/etc/squid3/net_ads_group.pl","squid");
@chgrp("/etc/squid3/net_ads_group.pl","squid");
echo "Starting......: ".date("H:i:s")." Dynamic ADS group done...\n";
