<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
CheckSettings();

function CheckSettings():bool{

    NetDATAEnabled();
	EnableMilterRegex();
	MilterGreyListEnabled();
    MimeDefangEnabled();
    NTPDClientEnabled();
    EnableUfdbGuard();
    EnableClamavDaemon();
    CicapEnabled();
    EnableFail2Ban();
    return  true;
}


function EnableClamavDaemon(){
        $sock = new sockets();
        $unix = new unix();
        $php = $unix->LOCATE_PHP5_BIN();
        $initd = "/etc/init.d/clamav-freshclam";
        $keyCode = intval($sock->GET_INFO("EnableClamavDaemon"));
        $keyCode2 = intval($sock->GET_INFO("CicapEnabled"));
        if($keyCode2==1){$keyCode=1;}

        if ($keyCode == 1) {
            if (is_file($initd)) {
                cluster_events(2, "Reconfiguring AV service", null, __LINE__);
                shell_exec("/usr/sbin/artica-phpfpm-service -reconfigure-clamd");
                return;
            }
            cluster_events(2, "{installing} AV service...", null, __LINE__);
            shell_exec("/usr/sbin/artica-phpfpm-service -install-clamd");
            return;
        }

        if (!is_file($initd)) {
            return;
        }
        cluster_events(0, "Uninstalling AV service...", null, __LINE__);
        shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-clamd");
    }
function CicapEnabled()
{
    $sock = new sockets();
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $initd = "/etc/init.d/c-icap";
    $keyCode = intval($sock->GET_INFO("CicapEnabled"));

    if ($keyCode == 1) {
        if (is_file($initd)) {
            cluster_events(2, "Reconfiguring ICAP AV service", null, __LINE__);
            $unix->CICAP_SERVICE_EVENTS("Reconfiguring ICAP service",__FILE__,__LINE__);
            shell_exec("$php /usr/share/artica-postfix/exec.c-icap.php --reconfigure");
            return;
        }
        cluster_events(2, "{installing} ICAP AV service...", null, __LINE__);
        shell_exec("/usr/sbin/artica-phpfpm-service -install-cicap");
        return;
    }

    if (!is_file($initd)) {
        return;
    }
    cluster_events(0, "Uninstalling ICAP AV...", null, __LINE__);
    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-cicap");
}
function EnableFail2Ban():bool{
    $sock = new sockets();
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $initd = "/etc/init.d/fail2ban";
    $keyCode = intval($sock->GET_INFO("EnableFail2Ban"));

    if ($keyCode == 1) {
        if (is_file($initd)) {
            cluster_events(2, "Reconfiguring Fail2ban service", null, __LINE__);
            shell_exec("$php /usr/share/artica-postfix/exec.fail2ban.php --restart");
            return true;
        }
        cluster_events(2, "{installing} Fail2ban service...", null, __LINE__);
        shell_exec("$php /usr/share/artica-postfix/exec.fail2ban.php --install");
        return true;
    }

    if (!is_file($initd)) {
        return true;
    }
    cluster_events(0, "Uninstalling Fail2ban service...", null, __LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.fail2ban.php --uninstall");
    return true;
}
function NetDATAEnabled()
{
    $sock = new sockets();
    $unix = new unix();
    $initd = "/etc/init.d/netdata";
    $keyCode = intval($sock->GET_INFO("NetDATAEnabled"));

    if ($keyCode == 1) {
        if (is_file($initd)) {
            cluster_events(2, "Reconfiguring NetData service", null, __LINE__);
            shell_exec("/usr/sbin/artica-phpfpm-service -restart-netdata");
            return;
        }
        cluster_events(2, "{installing} NetData service...", null, __LINE__);
        shell_exec("/usr/sbin/artica-phpfpm-service -install-netdata");
        return;
    }

    if (!is_file($initd)) {
        return;
    }
    cluster_events(0, "Uninstalling Nedata service...", null, __LINE__);
    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-netdata");
}




function EnableUfdbGuard(){
    $sock=new sockets();
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $initd="/etc/init.d/ufdb";
    $SQUIDEnable=intval($sock->GET_INFO("EnableUfdbGuard"));
    if($SQUIDEnable==1){
        if(is_file($initd)){
            cluster_events(2,"Reconfiguring Web-filtering service",null,__LINE__);
            shell_exec("$php /usr/share/artica-postfix/exec.ufdbguard.rules.php");
            return;
        }
        cluster_events(2,"Installing Web-Filtering service...",null,__LINE__);
        shell_exec("$php /usr/share/artica-postfix/exec.ufdb.enable.php");
        return;
    }

    if(!is_file($initd)){return;}
    cluster_events(0,"Uninstalling Web-Filtering service...",null,__LINE__);
    shell_exec("/usr/sbin/artica-phpfpm-service -install-ufdb");



}






function EnableMilterRegex(){
	$sock=new sockets();
	$initd="/etc/init.d/milter-regex";
	$EnableMilterRegex=intval($sock->GET_INFO("EnableMilterRegex"));
	if($EnableMilterRegex==1){
		if(is_file($initd)){return;} 
		$sock->getFrameWork("milter-regex.php?install=yes");
		return;
	}
	
	if(!is_file($initd)){return;}
	$sock->getFrameWork("milter-regex.php?uninstall=yes");
}
function MilterGreyListEnabled(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$initd="/etc/init.d/milter-greylist";
	
	$sock=new sockets();
	$Commutator=intval($sock->GET_INFO("MilterGreyListEnabled"));
	if($Commutator==1){
		if(is_file($initd)){return;}
		system("$php /usr/share/artica-postfix/exec.milter-greylist.install.php --install");
		return;
	}

	if(!is_file($initd)){return;}
	system("$php /usr/share/artica-postfix/exec.milter-greylist.install.php --uninstall");
}

function MimeDefangEnabled(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $initd="/etc/init.d/mimedefang";
    $sock=new sockets();
    $Commutator=intval($sock->GET_INFO("MimeDefangEnabled"));
    if($Commutator==1){
        if(is_file($initd)){return;}
        system("$php /usr/share/artica-postfix/exec.mimedefang.php --install");
        return;
    }

    if(!is_file($initd)){return;}
    squid_admin_mysql(0,"Uninstall {APP_MIMEDEFANG}",null,__FILE__,__LINE__);
    squid_admin_mysql(1,"[SMTP]: Uninstall MimeDefang system (after replication)",null,__FILE__,__LINE__);
    system("$php /usr/share/artica-postfix/exec.mimedefang.php --uninstall");
}

function NTPDClientEnabled(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $initd="/etc/cron.d/ntp-client";
    $sock=new sockets();
    $Commutator=intval($sock->GET_INFO("NTPDEnabled"));
    if($Commutator==1){
        if(is_file($initd)){return;}
        system("/usr/sbin/artica-phpfpm-service -install-ntp");
        return;
    }

    if(!is_file($initd)){return;}
    system("/usr/sbin/artica-phpfpm-service -uninstall-ntp");
}

function cluster_events($prio,$subject,$content,$line=0){
    $q=new lib_sqlite("/home/artica/SQLITE/clusters_events.db");
    $sql="CREATE TABLE IF NOT EXISTS `events` (
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
    `prio` INTEGER NOT NULL DEFAULT 2,
	`zdate` INTEGER,
	`sent` INTEGER NOT NULL DEFAULT 0,
	`subject` TEXT,
	`content` TEXT,
	`info` TEXT ) ";

    $q->QUERY_SQL($sql);
    $time=time();
    $info="Line ".$line ." file:".basename(__FILE__);
    $sql="INSERT INTO events (zdate,prio,sent,subject,content,info) VALUES('$time',$prio,0,'$subject','$content','$info');";
    $q->QUERY_SQL($sql);

}