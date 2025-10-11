<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}

xstart();


function xstart(){

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: Already Artica task running PID $pid since {$time}mn\n";
        return false;
    }
    @file_put_contents($pidfile, getmypid());



    if($unix->ServerRunSince()<2){return false;}

    $UrlCheckingEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingEnable"));
    if($UrlCheckingEnable==0){return false;}


    $UrlCheckingAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAddress"));
    $UrlCheckingAction=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAction"));
    $UrlCheckingProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingProxyPort"));
    if($UrlCheckingProxyPort==0){echo "UrlCheckingProxyPort == 0, aborting...\n";return false;}
    $UrlCheckingInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingInterval"));

    if($UrlCheckingInterval>0){
        $xtime=$unix->file_time_min($pidtime);
        if($xtime<$UrlCheckingInterval){return false;}
    }

    if(is_file($pidtime)){@unlink($pidtime);}
    @file_put_contents($pidtime,time());


    if($UrlCheckingAction==0){$UrlCheckingAction=3;}
    if($UrlCheckingAddress==null){$UrlCheckingAddress="https://www.google.com";}
    $zer["400"]="Bad Request";
    $zer["401"]="Unauthorized";
    $zer["402"]="Payment Required";
    $zer["403"]="Forbidden";
    $zer["404"]="Not Found";
    $zer["405"]="Method Not Allowed";
    $zer["406"]="Not Acceptable";
    $zer["407"]="Proxy Authentication Required";
    $zer["408"]="Request Timeout";
    $zer["409"]="Conflict";
    $zer["410"]="Gone";
    $zer["411"]="Length Required";
    $zer["412"]="Precondition Failed";
    $zer["413"]="Request Entity To Large";
    $zer["414"]="Request-URI Too Long";
    $zer["415"]="Unsupported Media Type";
    $zer["416"]="Requested range not satisfiable";
    $zer["417"]="Expectation Failed";
    $zer["420"]="Bad Protocol Extension Request";
    $zer["421"]="Protocol Extension Unknown";
    $zer["422"]="Protocol Extension Refused";
    $zer["423"]="Bad Protocol Extension Parameters";
    $zer["500"]="Internal Server Error";
    $zer["501"]="Not Implemented";
    $zer["502"]="Bad Gateway";
    $zer["503"]="Service Unavailable";
    $zer["504"]="Gateway Timeout";
    $zer["505"]="HTTP Version Not Supported";
    $zer["510"]="Not Extended";
    $zer["520"]="Protocol Extension Error";
    $zer["521"]="Protocol Extension Not Implemented";
    $zer["522"]="Protocol Extension Parameters Not Acceptable";
    $zer["600"]="Proxy header parsing error";





    $sql="SELECT * FROM proxy_ports WHERE ID=$UrlCheckingProxyPort";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array($sql);
    $eth=trim($ligne["nic"]);
    $port=$ligne["port"];
    if($eth<>null) {
        $Interface = $unix->InterfaceToIPv4($eth);
    }else{
        $NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
        foreach ($NETWORK_ALL_INTERFACES as $ifname=>$array) {
            if($ifname=="lo"){continue;}
            $IPADDR=$array["IPADDR"];
            if($IPADDR==null){continue;}
            if($IPADDR=="0.0.0.0"){continue;}
            $eth=$ifname;
            $Interface=$IPADDR;
            break;
        }
    }

    if($Interface=="0.0.0.0"){$Interface=null;}


    $unix->ToSyslog("URLCHECKER: Using proxy interface [$eth]=[$Interface]:$port",false,"squid");
    if($Interface==null){
        $unix->ToSyslog("URLCHECKER: Wrong Interface IP for $eth",false,"squid");
        return false;
    }

    $ch = curl_init();
    $headers=array();
    curl_setopt($ch, CURLOPT_INTERFACE, $Interface);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate",
        "Cache-Control: no-cache,must revalidate", 'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, $UrlCheckingAddress);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    curl_setopt($ch, CURLOPT_PROXY,"$Interface:$port");
    curl_setopt($ch, CURLOPT_HEADERFUNCTION,
        function($curl, $header) use (&$headers)
        {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
                return $len;

            $headers[strtolower(trim($header[0]))]= trim($header[1]);

            return $len;
        }
    );

    curl_exec($ch);
    $CURLINFO_HTTP_CODE = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_strerr=curl_strerror($curl_errno);

    $UrlCheckingAddress=trim($UrlCheckingAddress);
    if($GLOBALS["VERBOSE"]){echo "$Interface -> $Interface:$port -> $UrlCheckingAddress -> $curl_errno -> Layer7 $CURLINFO_HTTP_CODE\n";}
    $zUrlCheckingAction[0]="{do_nothing}";
    $zUrlCheckingAction[1]="{APP_SQUID}: {reconfigure}";
    $zUrlCheckingAction[2]="{APP_SQUID}: {restart}";
    $zUrlCheckingAction[3]="{APP_SQUID}: {reload}";

    if($curl_errno==0 AND $CURLINFO_HTTP_CODE < 399){return true;}

    $php=$unix->LOCATE_PHP5_BIN();

    $LOGS[]="Interface..: $Interface";
    $LOGS[]="Proxy Port.: $port";
    $LOGS[]="URL........: [$UrlCheckingAddress]";
    $LOGS[]="Transport..: Code $curl_errno ($curl_strerr)";
    if(is_array($headers)) {
        foreach ($headers as $key => $val) {
            $LOGS[] = "Header: $key: $val";
        }
    }

    if($curl_errno>0){
        curl_close($ch);
        $subjects[]="Url Checker: Transport Error $curl_errno $curl_strerr";
        $unix->ToSyslog("URLCHECKER: $Interface:$port/$UrlCheckingAddress: {$zUrlCheckingAction[$UrlCheckingAction]}: Error $curl_errno $curl_strerr",false,"squid");

        if($CURLINFO_HTTP_CODE>0){
            $subjects[]="HTTP Code $CURLINFO_HTTP_CODE";
        }

        if(isset($headers["x-squid-error"])){
            $subjects[]="Proxy Error {$headers["x-squid-error"]}";
        }

        $subjects[]="action=[".$zUrlCheckingAction[$UrlCheckingAction]."]";
        if($UrlCheckingAction==1){
            shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php");
        }
        if($UrlCheckingAction==2){
            shell_exec("/etc/init.d/squid restart");
        }
        if($UrlCheckingAction==3){
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
        }
        if($GLOBALS["VERBOSE"]){
            echo @implode("\n",$LOGS);
        }
        squid_admin_mysql(0,@implode(" ",$subjects),@implode("\n",$LOGS),__FILE__,__LINE__);
        return false;

    }



    if($CURLINFO_HTTP_CODE>399){

        if(isset($headers["x-squid-error"])){
            $subjects[]="Url Checker: Error $CURLINFO_HTTP_CODE Proxy Error {$headers["x-squid-error"]}";
        }

        $subjects[]="action=[".$zUrlCheckingAction[$UrlCheckingAction]."]";
        if($UrlCheckingAction==1){
            shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php");
        }
        if($UrlCheckingAction==2){
            shell_exec("/etc/init.d/squid restart");
        }
        if($UrlCheckingAction==3){
            $squidbin=$unix->LOCATE_SQUID_BIN();
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
        }

        $zertyext=$zer[$CURLINFO_HTTP_CODE];
        $LOGS[]="HTTP Error: $CURLINFO_HTTP_CODE - $zertyext";
        squid_admin_mysql(0,@implode(" ",$subjects),@implode("\n",$LOGS),__FILE__,__LINE__);
        return false;
    }


    return true;
}