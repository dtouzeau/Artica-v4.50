<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
check_certificates();


function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [INIT]: Let's Encrypt renew $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("letsencrypt", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function check_certificates(){

    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $results=$q->QUERY_SQL("SELECT * FROM sslcertificates WHERE UseLetsEncrypt=1");

    if(count($results)==0){
        _out("No let's Encrypt certificates....");
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");
        return false;
    }
    $php=$unix->LOCATE_PHP5_BIN();
    $LighttpdArticaCertificateName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName"));
    $NgnixIds=array();
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $DateTo=$ligne["DateTo"];
        $CommonName=$ligne["CommonName"];
        $time=strtotime($DateTo);
        $Today=time();
        $resultTime=$time-$Today;
        $resultTime=round($resultTime/60,0);
        $resultTime=round($resultTime/60,0);
        echo "[$ID]: $CommonName expire in $DateTo {$resultTime}h Left\n";
        if($resultTime<24){
            _out("[$ID]: $CommonName must be renewed");
            echo "$php /usr/share/artica-postfix/exec.certbot.php --create-certificate $ID\n";
            system("$php /usr/share/artica-postfix/exec.certbot.php --create-certificate $ID");
            $ligne2=$q->mysqli_fetch_array("SELECT DateTo FROM sslcertificates WHERE ID=$ID");
            $NewDateTo=$ligne2["DateTo"];
            if($NewDateTo==$DateTo){
                _out("Certificate Center, unable to renew $CommonName lets Encrypt certificate");
                squid_admin_mysql(0,"Certificate Center, unable to renew $CommonName lets Encrypt certificate",null,__FILE__,__LINE__);
                continue;
            }else{
                $WebsiteIDs=GetWebSiteFromCertificate($CommonName);
                if(count($WebsiteIDs)>0){foreach ($WebsiteIDs as $b=>$c){$NgnixIds[$b]=$b;}}
            }
            _out("Certificate Center, $CommonName lets Encrypt certificate renewed to $NewDateTo");
            squid_admin_mysql(1,"Certificate Center, $CommonName lets Encrypt certificate renewed to $NewDateTo",null,__FILE__,__LINE__);
            if($LighttpdArticaCertificateName==$CommonName){
                echo "[$ID]: $CommonName reconfigure Artica Web console\n";
                squid_admin_mysql(1,"Certificate Center, reconfigure Artica Web console for the new certificate",null,__FILE__,__LINE__);
                system("/usr/sbin/artica-phpfpm-service -reload-webconsole -debug");

            }

        }
    }
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");

    if(count($NgnixIds)>0){
        foreach ($NgnixIds as $ID=>$none){
            shell_exec("$php /usr/share/artica-postfix/exec.nginx.single.php $ID");
        }
    }


}

function GetWebSiteFromCertificate($CommonName)
{
    if (!is_file("/etc/init.d/nginx")) {return array();}
    $Resturn=array();
    $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $results = $q->QUERY_SQL("SELECT serviceid FROM service_parameters 
    WHERE zkey='ssl_certificate' AND zvalue='$CommonName'");

    foreach ($results as $index=>$ligne){
        $serviceid=$ligne["serviceid"];
        $ligne2=$q->mysqli_fetch_array("SELECT enabled FROM nginx_services WHERE ID=$serviceid");
        if(intval($ligne2["enabled"])==0){continue;}
        $Resturn[$serviceid]=$serviceid;
    }
    return $Resturn;
}
