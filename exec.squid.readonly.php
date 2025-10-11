<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["TITLENAME"]="DNS Load-balancing Daemon";
$GLOBALS["OUTPUT"]=true;
include_once("/usr/share/artica-postfix/ressources/class.resolv.conf.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.inc");
include_once("/usr/share/artica-postfix/ressources/class.sqlite.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


if($argv[1]=="--on"){readonly_on();exit();}
if($argv[1]=="--off"){readonly_off();exit();}

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"squid.readonly.progress");
}

function readonly_on(){
    $unix=new unix();
    $chattr=$unix->find_program("chattr");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidConfReadOnly",1);
    build_progress(50,"{readonly} {configuration}");
    $f=listfiles();
    foreach ($f as $filename){
        $path="/etc/squid3/$filename";
        if(!is_file($path)){continue;}
        shell_exec("$chattr +i $path");
    }
    build_progress(100,"{readonly} {configuration} {success}");
}
function readonly_off(){
    $unix=new unix();
    $chattr=$unix->find_program("chattr");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidConfReadOnly",0);
    build_progress(50,"{unlock} {configuration}");
    $f=listfiles();
    foreach ($f as $filename){
        $path="/etc/squid3/$filename";
        if(!is_file($path)){continue;}
        shell_exec("$chattr -i $path");
    }
    build_progress(100,"{unlock} {configuration} {success}");
}
function listfiles(){
    $f[]="ChildsProxy.conf";
    $f[]="DomainsBlackLists.acl";
    $f[]="FTP.conf";
    $f[]="FromBlackLists.acl";
    $f[]="GlobalAccessManager_auth.conf";
    $f[]="GlobalAccessManager_deny.conf";
    $f[]="GlobalAccessManager_deny_cache.conf";
    $f[]="GlobalAccessManager_url_rewrite.conf";
    $f[]="MgrInfosMaxFailedCount";
    $f[]="NetworksBlackLists.acl";
    $f[]="NetworksBlackLists.db";
    $f[]="StoreID.conf";
    $f[]="acl_NoFilterService.conf";
    $f[]="acls_adgroup.conf";
    $f[]="acls_bandwidth.conf";
    $f[]="acls_browsers.conf";
    $f[]="acls_center.conf";
    $f[]="acls_center_meta.conf";
    $f[]="acls_ecap.conf";
    $f[]="acls_headers.conf";
    $f[]="acls_peer.conf";
    $f[]="acls_whitelist.arp.conf";
    $f[]="acls_whitelist.browser.conf";
    $f[]="acls_whitelist.conf";
    $f[]="acls_whitelist.dst.conf";
    $f[]="acls_whitelist.dstdom_regex.conf";
    $f[]="acls_whitelist.dstdomain.conf";
    $f[]="acls_whitelist.src.conf";
    $f[]="anonymous.conf";
    $f[]="artica-meta";
    $f[]="authenticate.authenticated.conf";
    $f[]="authenticate.conf";
    $f[]="cachemgr.conf";
    $f[]="caches.conf";
    $f[]="common.conf";
    $f[]="dangerous_extensions.conf";
    $f[]="dns.conf";
    $f[]="errorpage.css";
    $f[]="external_acl_krsn.py";
    $f[]="external_acls.conf";
    $f[]="external_categorize.conf";
    $f[]="external_krsn.conf";
    $f[]="http_access.conf";
    $f[]="http_access_final.conf";
    $f[]="http_reply_access.conf";
    $f[]="icap.conf";
    $f[]="listen_ports.conf";
    $f[]="logging.conf";
    $f[]="mime.conf";
    $f[]="non_ntlm.access";
    $f[]="non_ntlm.conf";
    $f[]="parentlb.cfg";
    $f[]="refresh_pattern_artica.conf";
    $f[]="refresh_pattern_domains.conf";
    $f[]="snmpd.conf";
    $f[]="squid-block.acl";
    $f[]="squid.conf";
    $f[]="ssl.conf";
    $f[]="tcp_outgoing_address.conf";
    $f[]="tcp_outgoing_mark.conf";
    $f[]="timeouts.conf";
    $f[]="ufdbgclient.conf";
    $f[]="url_regex_nocache.conf";
    $f[]="url_rewrite_access.conf";
    $f[]="wccp.conf";
    $f[]="xcc.conf";
    return $f;
}