<?php
// 4.31 -> 4.30
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.modsecurity.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}



if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--build"){build();}
if($argv[1]=="--default-white"){default_white();exit;}

function build_progress($text,$pourc){
    $cachefile=PROGRESS_DIR."/modsecurity.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "{$pourc}%: $text\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function install(){
    $unix=new unix();
    build_progress("{installing}",20);
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableModSecurityIngix",1);


    build_progress("{updating}",50);
    if(!is_dir("/etc/nginx/owasp-modsecurity-crs")){
        @mkdir("/etc/nginx/owasp-modsecurity-crs",0755,true);
    }

    if(!is_file("/etc/ld.so.conf.d/modsecurity.conf")){
        @file_put_contents("/etc/ld.so.conf.d/modsecurity.conf","/usr/local/modsecurity/lib\n");
        shell_exec("/usr/sbin/ldconfig");
    }
    if(!is_dir("/home/artica/modsecurity")){
        @mkdir("/home/artica/modsecurity",0755,true);
    }



    system("/usr/sbin/artica-phpfpm-service -waf-global -debug");

    chown("/home/artica/modsecurity","www-data");
    chgrp("/home/artica/modsecurity","www-data");

    build_progress("{installing}",60);




    build_progress("{done}",100);
}



function default_white(){
    system("/usr/sbin/artica-phpfpm-service -waf-global -debug");
    shell_exec("/etc/init.d/nginx reload");
}


function uninstall(){
    $unix=new unix();
    build_progress("{uninstalling}",20);
    $rm=$unix->find_program("rm");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableModSecurityIngix",0);
    $crons[]="ModSecurity";
    $crons[]="ModSecurity-Parse";
    $crons[]="ModSecurity-Clean";
    foreach ($crons as $cfile){
        $unix->Popuplate_cron_delete($cfile);
    }


    @unlink("/var/log/modsec_parse.log");

    if(is_dir("/etc/nginx/owasp-modsecurity-crs")){
        build_progress("{uninstalling}",50);
        shell_exec("$rm -rf /etc/nginx/owasp-modsecurity-crs");
    }

    if(is_dir("/home/artica/modsecurity")){
        build_progress("{uninstalling}",55);
        shell_exec("$rm -rf /home/artica/modsecurity");
    }

    if(is_file("/etc/rsyslog.d/modsecurity-update.conf")){
        @unlink("/etc/rsyslog.d/modsecurity-update.conf");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

    $prc=55;

    $tables[]="modsecurity_events";
    $tables[]="modsecurity_patterns";
    $tables[]="modsecurity_titles";
    $tables[]="modsecurity_vers";
    $tables[]="modsecurity_hosts";
    $tables[]="modsecurity_uris";
    $tables[]="modsecurity_explains";
    $tables[]="modsecurity_reports";
    $tables[]="modsecurity_tags";
    $tables[]="modsecurity_linked_tags";

    $q=new postgres_sql();
    foreach ($tables as $table){
        $prc++;
        build_progress("{removing} $table",$prc);
        $q->QUERY_SQL("DROP TABLE $table");
    }




    build_progress("{uninstalling} {done}",100);
}
function nginx_syslog($text):bool{
    echo $text."\n";
    $file=basename(__FILE__);
    if(!function_exists("openlog")){return false;}
    openlog("nginx", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "$text ($file)");
    closelog();
    return true;
}
function build(){
    $unix=new unix();
    build_progress("{configuring}",20);
    system("/usr/sbin/artica-phpfpm-service -waf-global -debug");
    build_progress("{reloading}",50);
    $nginx=$unix->find_program("nginx");
    nginx_syslog("Reloading reverse proxy service...");
    chown("/home/artica/modsecurity","www-data");
    chgrp("/home/artica/modsecurity","www-data");
    exec("$nginx -s reload 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#nginx:\s+\[emerg\]\s+#",$line)){
            nginx_syslog("Error $line");
            build_progress("{configuring} {failed}",110);
            return false;
        }

    }
    build_progress("{configuring} {done}",100);
}



