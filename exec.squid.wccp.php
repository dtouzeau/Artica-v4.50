#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["EBTABLES"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="--install"){wccp_install();exit;}
if($argv[1]=="--uninstall"){wccp_uninstall();exit;}
if($argv[1]=="--reconfigure"){wccp();exit;}
if($argv[1]=="--configure"){wccp();exit;}
if($argv[1]=="--build"){wccp();exit;}
if($argv[1]=="--reload-fw"){wccp_reload_iptables();exit;}
if($argv[1]=="--build"){wccp();exit;}


echo basename(__FILE__)." Could not understand your Command-line\n";
die();

function build_progress($text,$pourc){
	outputSCR("{$pourc}%) $text");
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.wccp.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function wccp_install(){
    $unix       = new unix();
    $chattr     = $unix->find_program("chattr");
    build_progress("WCCP {installing}",10);
    sleep(2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWCCPEnabled",1);
    shell_exec("$chattr -i /etc/squid3/wccp.conf");
    build_progress("WCCP {installing}",50);
    sleep(2);
    build_progress("WCCP {installing} {success}",100);
}
function wccp_uninstall(){
    $unix       = new unix();
    $ipbin      = $unix->find_program("ip");
    $php        = $unix->LOCATE_PHP5_BIN();
    $chattr     = $unix->find_program("chattr");
    build_progress("WCCP {uninstalling}",10);
    sleep(2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWCCPEnabled",0);
    build_progress("WCCP {uninstalling}",50);
    shell_exec("$ipbin tunnel del wccp0 >/dev/null 2>&1");
    @file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n\n");
    shell_exec("$chattr +i /etc/squid3/wccp.conf");



    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM transparent_ports WHERE hiddenID=10");
    $q->QUERY_SQL("DELETE FROM transparent_ports WHERE hiddenID=20");
    $q->QUERY_SQL("UPDATE transparent_ports SET enabled='1' WHERE hiddenID=0");
    build_progress("WCCP {reconfiguring} {APP_SQUID}",80);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports --firehol --force");
    build_progress("WCCP {reconfiguring} {APP_IPTABLES}",80);
    disable_iptables();
    build_progress("WCCP {reconfiguring} {uninstall}",90);
    shell_exec("$ipbin link set wccp0 down");
    shell_exec("$ipbin tunnel del wccp0");
    build_progress("WCCP {uninstalling} {success}",100);

}

function wccp(){
	$SquidWCCPEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPEnabled"));
	if($SquidWCCPEnabled==0){
        @file_put_contents("/etc/squid3/wccp.conf","# Uninstalled");
	    die("SquidWCCPEnabled = 0");
	}

	$unix       = new unix();
	$ipbin      = $unix->find_program("ip");
	$modprobe   = $unix->find_program("modprobe");
	$php        = $unix->LOCATE_PHP5_BIN();
	$RELOAD_PRXY= False;

    $WCCP_HTTPS_SERVICE_ID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_SERVICE_ID"));
    $WCCP_HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTP_PORT"));
    $WCCP_HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_PORT"));
    $WCCP_LOCAL_INTERFACE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_LOCAL_INTERFACE"));
    $WCCP_ASA_ADDR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ADDR"));
    $WCCP_ASA_ROUTER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ROUTER"));
    $WCCP_ASA_USE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_USE"));
    if($WCCP_HTTPS_SERVICE_ID==0){$WCCP_HTTPS_SERVICE_ID=70;}
    $WCCP_CERTIFICATE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_CERTIFICATE"));
    $WCCP_PASSWORD=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_PASSWORD"));

    if($WCCP_HTTP_PORT==0){$WCCP_HTTP_PORT=3126;}
    if($WCCP_HTTPS_PORT==0){$WCCP_HTTPS_PORT=3125;}
    if($WCCP_LOCAL_INTERFACE==null){$WCCP_LOCAL_INTERFACE="eth0";}


    if($WCCP_ASA_ADDR==null){
		@file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# No router set\n");
		build_progress("{failed}, WCCP Interface Address not set...",110);
		return;
	}

    if($WCCP_ASA_USE==1) {
        if ($WCCP_ASA_ROUTER == null) {
            @file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# No router set\n");
            build_progress("{failed}, WCCP Router Address not set...", 110);
            return;
        }
    }



    if($WCCP_CERTIFICATE==null){
        @file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# No Certificate set\n");
        build_progress("{failed}, WCCP Router Certificate not set...",110);
        return;
    }

    echo "Local Interface: $WCCP_LOCAL_INTERFACE\n";
    if(!$unix->is_interface_available($WCCP_LOCAL_INTERFACE)){
        @file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# $WCCP_LOCAL_INTERFACE unavailable\n");
        build_progress("{failed}, $WCCP_LOCAL_INTERFACE unavailable...",110);
        return;

    }

    $f=explode("\n",@file_get_contents("/etc/squid3/wccp.conf"));
    $SRC_WCCP_ASA_ADDR=null;
    $SRC_WCCP_PASSWORD=null;
    foreach ($f as $line){
        if(preg_match("#wccp2_router\s+(.+)#",$line,$re)){
            $SRC_WCCP_ASA_ADDR=trim($re[1]);
            continue;
        }
        if(preg_match("#wccp2_service.*?password=(.+)#",$line,$re)){
            $SRC_WCCP_PASSWORD=trim($re[1]);

        }

    }

    if($SRC_WCCP_ASA_ADDR<>$SRC_WCCP_ASA_ADDR){$RELOAD_PRXY=true;}
    if($SRC_WCCP_PASSWORD<>$SRC_WCCP_PASSWORD){$RELOAD_PRXY=true;}
    $WCCP_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_VERSION"));
    if($WCCP_VERSION==0){$WCCP_VERSION=4;}
    if($WCCP_PASSWORD<>null){
        $password=" password=$WCCP_PASSWORD";
    }
    build_progress("{reconfiguring}...",30);
    $conf[]="wccp2_router $WCCP_ASA_ADDR";
    $conf[]="wccp_version $WCCP_VERSION";
    $conf[]="wccp2_rebuild_wait on";
    $conf[]="wccp2_forwarding_method gre";
    $conf[]="wccp2_return_method gre";
    $conf[]="wccp2_assignment_method hash";
    //$conf[]="wccp2_service standard 0$password";
    $conf[]="wccp2_service dynamic {$WCCP_HTTPS_SERVICE_ID}$password";
    $conf[]="wccp2_service_info $WCCP_HTTPS_SERVICE_ID protocol=tcp flags=src_ip_hash,src_port_alt_hash priority=240 ports=80,443";
    $conf[]="wccp2_weight 10000";
    @file_put_contents("/etc/squid3/wccp.conf",@implode("\n",$conf));


    build_progress("{reconfiguring}...",50);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    if(!$q->FIELD_EXISTS("transparent_ports","hiddenID")){ $q->QUERY_SQL("ALTER TABLE transparent_ports ADD `hiddenID` INTEGER DEFAULT 0"); }

    $RECONFIGURE_PROXY=false;
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM transparent_ports WHERE hiddenID=10");
    $HTTP_ID=intval($ligne["ID"]);
    if($HTTP_ID==0){
        $PortName="HTTP Cisco";
        $outgoing_nic=null;
        $nic=null;
        $sslcertificate=null;
        $enabled=1;
        $NoCache=0;
        $NoFilter=0;$TProxy=0;
        $localport=$WCCP_HTTP_PORT;
        $port=80;
        $others_ports=null;
        $sql = "INSERT INTO transparent_ports (PortName,nic,outgoing_nic,sslcertificate,enabled,NoCache,NoFilter,TProxy,localport,port,others_ports,hiddenID) 
                VALUES ('$PortName','$nic','$outgoing_nic','$sslcertificate','$enabled',$NoCache,$NoFilter,$TProxy,$localport,$port,'$others_ports',10)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error."\n";
            build_progress("{failed}, SQL Error...",110);
            return;
        }



        $ligne=$q->mysqli_fetch_array("SELECT ID FROM transparent_ports WHERE hiddenID=10");
        $HTTP_ID=intval($ligne["ID"]);
        if($HTTP_ID==0){
            build_progress("{failed}, HTTP Port...",110);
            return;
        }
        $RECONFIGURE_PROXY=true;
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM transparent_ports WHERE hiddenID=20");
    $SSL_ID=intval($ligne["ID"]);
    if($SSL_ID==0){
        $PortName="SSL Cisco";
        $outgoing_nic=null;
        $nic=null;
        $sslcertificate=$WCCP_CERTIFICATE;
        $enabled=1;
        $NoCache=0;
        $NoFilter=0;$TProxy=0;
        $localport=$WCCP_HTTPS_PORT;
        $port=443;
        $others_ports=null;
        $sql = "INSERT INTO transparent_ports (PortName,nic,outgoing_nic,sslcertificate,enabled,NoCache,NoFilter,TProxy,localport,port,others_ports,hiddenID) 
                VALUES ('$PortName','$nic','$outgoing_nic','$sslcertificate','$enabled',$NoCache,$NoFilter,$TProxy,$localport,$port,'$others_ports',20)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error."\n";
            build_progress("{failed}, SQL Error...",110);
            return;
        }
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM transparent_ports WHERE hiddenID=20");
        $SSL_ID=intval($ligne["ID"]);
        if($SSL_ID==0){
            build_progress("{failed}, HTTPs Port...",110);
            return;
        }
        $RECONFIGURE_PROXY=true;
    }


    $q->QUERY_SQL("UPDATE transparent_ports SET enabled='0' WHERE hiddenID=0");
    if(!$q->ok){echo $q->mysql_error." LINE ".__LINE__."\n";}
    $q->QUERY_SQL("UPDATE transparent_ports SET sslcertificate='$WCCP_CERTIFICATE',port=443,localport='$WCCP_HTTPS_PORT' WHERE hiddenID=20");
    if(!$q->ok){echo $q->mysql_error." LINE ".__LINE__."\n";}
    $q->QUERY_SQL("UPDATE transparent_ports SET localport='$WCCP_HTTP_PORT',port=80 WHERE hiddenID=10");
    if(!$q->ok){echo $q->mysql_error." LINE ".__LINE__."\n";}

    $certificate_filename=md5($WCCP_CERTIFICATE);

    $listen_ports=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
    $HTTP_OK=false;
    $HTTPS_OK=false;

    foreach ($listen_ports as $line){
        if($HTTP_OK and $HTTPS_OK){break;}
        if(preg_match("#^http_port\s+.*?:$WCCP_HTTP_PORT.*?intercept\s+name=#",$line)){
            $HTTP_OK=true;
            continue;
        }
        if(preg_match("#^https_port\s+.*?:$WCCP_HTTPS_PORT.*?intercept.*?$certificate_filename#",$line)){
            $HTTPS_OK=true;
            continue;
        }
    }

    if(!$HTTP_OK){
        outputSCR("Need to reconfigure HTTP port($WCCP_HTTP_PORT)");
        $RECONFIGURE_PROXY=true;
    }
    if(!$HTTPS_OK){
        outputSCR("Need to reconfigure HTTPs port($WCCP_HTTPS_PORT)");
        $RECONFIGURE_PROXY=true;
    }

    build_progress("{reconfiguring}...",60);
    if($RECONFIGURE_PROXY){
        squid_admin_mysql(1,"WCCP: Reconfiguring proxy service",null,__FILE__,__LINE__);
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports --nofw --force");
    }
    if($RELOAD_PRXY){
        squid_admin_mysql(1,"WCCP: {reloading_proxy_service}",null,__FILE__,__LINE__);
        outputSCR("{reloading_proxy_service}");
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }

    exec("$ipbin tunnel show 2>&1",$tunnels);

    $SOURCE_REMOTE=null;
    $SOURCE_INTERFACE=null;
    $SOURCE_LOCAL=null;
    $RECONFIGURE_GRE=false;

    foreach ($tunnels as $line){
        if(preg_match("#wccp0:\s+.*?remote\s+([0-9\.]+)\s+local.*?([0-9\.]+).*?\s+dev\s+(.*?)\s+#",$line,$re)){
            $SOURCE_INTERFACE=$re[3];
            $SOURCE_LOCAL=$re[2];
            $SOURCE_REMOTE=$re[1];
            break;
        }
    }


    $WCCP_ASA_ADDR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ADDR"));
    if($WCCP_ASA_USE==0){$WCCP_ASA_ROUTER=$WCCP_ASA_ADDR; }
    $WCCP_LOCAL_IP=$unix->InterfaceToIPv4($WCCP_LOCAL_INTERFACE);
    if($WCCP_LOCAL_INTERFACE<>$SOURCE_INTERFACE){$RECONFIGURE_GRE=true;}
    if($SOURCE_REMOTE<>$WCCP_ASA_ROUTER){$RECONFIGURE_GRE=true;}
    if($WCCP_LOCAL_IP<>$SOURCE_LOCAL){$RECONFIGURE_GRE=true;}

    echo "Checking tunne GRE to [$WCCP_ASA_ROUTER] using $WCCP_LOCAL_IP IP address (interface $WCCP_LOCAL_INTERFACE)\n";

    if($RECONFIGURE_GRE){
        outputSCR("Reconfiguring GRE Interface");
        if($SOURCE_REMOTE<>null){
            shell_exec("$ipbin tunnel del wccp0 >/dev/null 2>&1");
        }
        $cmdline="$ipbin tunnel add wccp0 mode gre remote $WCCP_ASA_ROUTER local $WCCP_LOCAL_IP dev $WCCP_LOCAL_INTERFACE";
        $SH[]="$modprobe ip_gre";
        $SH[]=$cmdline;
        shell_exec("$modprobe ip_gre");
        shell_exec($cmdline);
        $cmdline="$ipbin addr add $WCCP_LOCAL_IP/32 dev wccp0";
        shell_exec($cmdline);
        shell_exec("$ipbin link set wccp0 up");
        shell_exec("/usr/sbin/artica-phpfpm-service -firewall-tune");
    }

    build_progress("{reconfiguring}...",70);
    enable_iptables();



    build_progress("{reconfiguring} {done}...",100);
}

function disable_iptables(){
	$d=0;
	$pattern2="#.+?ArticaWCCP3#";
	$iptables_save=find_program("iptables-save");
	$conf=array();
	exec("$iptables_save 2>&1",$datas);
	foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){
			$d++;
			continue;
		}
		
		$conf[]=$ligne;
	}
	if($d==0){return;}
	$iptables_restore=find_program("iptables-restore");
	file_put_contents("/etc/artica-postfix/iptables.new.conf",@implode("\n",$conf));
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
}
function wccp_reload_iptables(){
    outputSCR("Disable iptables rules");
    disable_iptables();
    outputSCR("Enable iptables rules");
    enable_iptables();
}

function enable_iptables(){

    $unix       = new unix();
    $iptables   = $unix->find_program("iptables");
    $iptcomment = "-m comment --comment \"ArticaWCCP3\"";
    $tnat       = "-t nat -A PREROUTING -i wccp0";
    $iptables_save=$unix->find_program("iptables-save");

    $WCCP_HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTP_PORT"));
    $WCCP_HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_PORT"));
    $WCCP_LOCAL_INTERFACE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_LOCAL_INTERFACE"));

    if($WCCP_HTTP_PORT==0){$WCCP_HTTP_PORT=3126;}
    if($WCCP_HTTPS_PORT==0){$WCCP_HTTPS_PORT=3125;}
    if($WCCP_LOCAL_INTERFACE==null){$WCCP_LOCAL_INTERFACE="eth0";}




    $IPTABLES_WCCP_HTTP_PORT=true;
    $IPTABLES_WCCP_HTTPS_PORT=true;
    $IPTABLES_CHANGES=true;
    $FOUND=false;

    exec("$iptables_save 2>&1",$iptout);
    foreach ($iptout as $line){

        if(preg_match("#wccp0.*?80.*?--to-(port|destination).*?([0-9]+)$#",$line,$re)){
            outputSCR("iptables: destination 80 found {$re[2]}");
            $FOUND=true;
            if(intval($re[2])==$WCCP_HTTP_PORT){$IPTABLES_WCCP_HTTP_PORT=false;}
            continue;
        }
        if(preg_match("#wccp0.*?443.*?--to-(port|destination).*?([0-9]+)$#",$line,$re)){
            outputSCR("iptables: destination 443 found {$re[2]}");
            $FOUND=true;
            if(intval($re[2])==$WCCP_HTTPS_PORT){$IPTABLES_WCCP_HTTPS_PORT=false;}
            continue;
        }
    }

    if(!$FOUND){
        $IPTABLES_WCCP_HTTP_PORT=true;
        $IPTABLES_WCCP_HTTPS_PORT=true;
    }

    if($IPTABLES_WCCP_HTTP_PORT){$IPTABLES_CHANGES=false;}
    if($IPTABLES_WCCP_HTTPS_PORT){$IPTABLES_CHANGES=false;}


    if(!$IPTABLES_CHANGES){
        $WCCP_LOCAL_ADDR=$unix->InterfaceToIPv4($WCCP_LOCAL_INTERFACE);
        outputSCR("iptables: Creating transparent rules (to $WCCP_LOCAL_ADDR)");
        disable_iptables();
        shell_exec("$iptables $tnat -p tcp --dport 80 -j DNAT --to-destination $WCCP_LOCAL_ADDR:$WCCP_HTTP_PORT $iptcomment");
        shell_exec("$iptables $tnat -p tcp --dport 443 -j DNAT --to-destination $WCCP_LOCAL_ADDR:$WCCP_HTTPS_PORT $iptcomment");
    }

    shell_exec("/usr/sbin/artica-phpfpm-service -firewall-tune");

}

function outputSCR($text){
    echo "Starting......: ".date("H:i:s")." WCCP mode: $text\n";

}


function disablenics(){
	$unix=new unix();
	$sock=new sockets();
	$ipbin=$unix->find_program("ip");	
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ipbin tunnel show 2>&1",$results);
	
	
	foreach ($results as $index=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^wccp([0-9]+):\s+gre\/#", $line,$re)){continue;}
		$ID=$re[1];
		outputSCR("Starting......: ".date("H:i:s")." Squid Listen removing wccp{$ID}");
		shell_exec("$ipbin tunnel del wccp{$ID}");
		shell_exec("$ifconfig wccp{$ID} down");
	}
	
}










