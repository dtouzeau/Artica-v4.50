<?php
$GLOBALS["WIZARD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

if($argv[1]=="--install"){install_vlan();exit();}
if($argv[1]=="--uninstall"){uninstall_vlan();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--remove"){RemoveVLans();exit;}
if($argv[1]=="--removem"){removeMasquerades();exit;}


function install_module_8021q(){
    $f=explode("\n",@file_get_contents("/etc/modules"));
    $unix=new unix();
    $modprobe=$unix->find_program("modprobe");
    shell_exec("$modprobe 8021q >/dev/null 2>&1");
    foreach ( $f as $line ){
        if(preg_match("#^8021q#", $line)){return;}
    }
    $f[]="8021q";
    @file_put_contents("/etc/modules", @implode("\n", $f));

}

function build(){
    echo "Build VLANs\n";
    BuildVlans();
    echo "Running configuration...\n";
    shell_exec("/usr/local/sbin/vlan-start.sh");
}

function uninstall_module_8021q(){
    $f=explode("\n",@file_get_contents("/etc/modules"));
    $unix=new unix();
    $modprobe=$unix->find_program("modprobe");
    shell_exec("$modprobe 8021q >/dev/null 2>&1");
    $NewF=array();
    foreach ( $f as $line ){
        $line=trim($line);
        if(preg_match("#^8021q#", $line)){continue;}
        $NewF[]=$line;
    }

    @file_put_contents("/etc/modules", @implode("\n", $NewF));

}

function checkMainScript(){
    $f=explode("\n",@file_get_contents("/etc/init.d/artica-ifup"));

    foreach ( $f as $line ){
        if(preg_match("#\/vlan-start\.sh#",$line)){
            return true;
        }
    }

    return false;
}

function build_progress($text,$pourc){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/vlans.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function uninstall_vlan(){
    build_progress("{uninstalling}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableVLANs",0);
    $unix=new unix();
    build_progress("{uninstalling}",20);
    uninstall_module_8021q();
    sleep(1);
    build_progress("{uninstalling}",30);
    RemoveVLans();
    removeMasquerades();
    $rmmod=$unix->find_program("rmmod");
    shell_exec("$rmmod 8021q");
    build_progress("{uninstalling}",50);
    sleep(1);
    build_progress("{uninstalling} {done}",100);
}

function install_vlan(){
    build_progress("{installing}",10);
    $unix=new unix();
    $moprobe=$unix->find_program("moprobe");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableVLANs",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArticaAsGateway",1);

    install_module_8021q();
    if(!checkMainScript()){
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
    }
    sleep(1);
    build_progress("{installing}",50);
    shell_exec("$moprobe 8021q >/dev/null 2>&1");
    sleep(1);
    build_progress("{installing}",60);
    BuildVlans();
    sleep(1);
    build_progress("{installing}",100);

}

function RemoveVLans(){
    $unix=new unix();
    $ip=$unix->find_program("ip");

    exec("$ip route show table all 2>&1",$results);

    foreach ($results as $line){
            $line=trim($line);
            if(preg_match("#dev vlan[0-9]+#",$line)){
                echo "Removing table $line";
                shell_exec("$ip route del $line\n");
            }
    }

    echo "Removing table rules...\n";
    $results=array();
    exec("$ip rule 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(!preg_match("#^([0-9]+):(.+)#",$line,$re)){
            echo "NP $line\n";
            continue;}
        $line=$re[2];
        if(!preg_match("#iif vlan[0-9]+#",$line,$re)){continue;}
        echo "Remove routing rule $line\n";
        shell_exec("$ip rule del $line\n");

    }

    $results=explode("\n",@file_get_contents("/proc/net/dev"));
    foreach ($results as $line) {
        $line = trim($line);
        if (!preg_match("#^vlan([0-9]+):\s+#", $line, $re)) {
            continue;}
        $ifname = "vlan{$re[1]}";
        echo "removing $ifname\n";
        shell_exec("$ip link set dev $ifname down");
        shell_exec("$ip link delete $ifname");
    }

    removeMasquerades();




}

function removeMasquerades(){

    exec("/sbin/iptables-save 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#-A POSTROUTING\s+-o\s+(.+?)\s+-j MASQUERADE#i",$line,$re)){
            continue;
        }
        $interface=$re[1];
        echo "Removing MASQUERADE for $interface\n";
        shell_exec("/sbin/iptables -t nat -D POSTROUTING -o $interface -j MASQUERADE");
    }

}

function BuildVlans(){
    $sock=new sockets();
    $NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
    $unix=new unix();
    $ip=$unix->find_program("ip");
    $sysctl=$unix->find_program("sysctl");
    $iptables=$unix->find_program("iptables");
    $php=$unix->LOCATE_PHP5_BIN();

    $sql="SELECT * FROM nics_vlan ORDER BY ID DESC";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
    }


    $results=$q->QUERY_SQL($sql);
    $count=count($results);
    $GLOBALS["SCRIPTS"][]="#!/bin/sh";
    $php=$unix->LOCATE_PHP5_BIN();

    if($count>0){
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for VLANs ****";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
        $GLOBALS["SCRIPTS"][]="/usr/sbin/artica-phpfpm-service -firewall-tune >/dev/null 2>&1";
        $GLOBALS["SCRIPTS"][]="$php ".__FILE__." --remove >/dev/null 2>&1";
    }else{
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** NONE for VLANs ****";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
        $GLOBALS["SCRIPTS"][]="# [".__LINE__."]";

    }


    $MASQ[]="$php ".__FILE__." --removem || true";
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $ipaddr=$ligne["ipaddr"];
        $vlanid=$ligne["vlanid"];
        $eth_text="vlan{$ligne["ID"]}";
        $eth="{$ligne["nic"]}";
        $enabled=$ligne["enabled"];
        $netmask=$ligne["netmask"];
        $masquerade=intval($ligne["masquerade"]);
        $metric=$ligne["metric"];
        $ForceGateway=intval($ligne["ForceGateway"]);
        $cdir=$ligne["cdir"];

        $GLOBALS["SCRIPTS"][]="# [".__LINE__."] $ipaddr/$netmask vlan $vlanid on $eth";

        if($vlanid==0){
            $GLOBALS["SCRIPTS"][]="# [".__LINE__."] $eth_text is disabled, Aborting";
            continue;
        }

        if($enabled==0){
            $GLOBALS["SCRIPTS"][]="# [".__LINE__."] $eth_text is disabled, Aborting";
            continue;
        }

        $nic=new system_nic($eth);

        if($nic->enabled==0){
            $GLOBALS["SCRIPTS"][]="# [".__LINE__."] $eth is disabled, Aborting";
            continue;
        }
        $IP=new IP();
        $cdir=$IP->mask2cdr($netmask);
        $metric=null;
        $GLOBALS["SCRIPTS"][]="$ip link add link $eth name $eth_text type vlan id $vlanid || true";
        $GLOBALS["SCRIPTS"][]="$ip link set up $eth_text || true";
        $GLOBALS["SCRIPTS"][]="$ip addr add $ipaddr/$cdir dev $eth_text{$metric} || true";
        if($ForceGateway==1){
            $TableID=300+$ID;
            $GLOBALS["SCRIPTS"][]="$ip route add default via {$ligne["gateway"]} table $TableID dev $eth_text";
            $GLOBALS["SCRIPTS"][]="$ip rule add from $cdir lookup $TableID dev $eth_text";
            $GLOBALS["SCRIPTS"][]="$ip rule add to $cdir lookup $TableID dev $eth_text";
        }

        if($masquerade==1){
            $MASQ[]="$iptables -t nat -I POSTROUTING -o $eth_text -j MASQUERADE -m comment --comment \"MasqueradeVlan\" || true";

        }

        $f[]="";

    }
    $GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
    $GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
    $GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** MASQUERADE ****";
    $GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
    $GLOBALS["SCRIPTS"][]="# [".__LINE__."]";

    foreach ($MASQ as $line){
        $GLOBALS["SCRIPTS"][]=$line;
    }

    @file_put_contents("/usr/local/sbin/vlan-start.sh",@implode("\n",$GLOBALS["SCRIPTS"]));
    @chmod("/usr/local/sbin/vlan-start.sh",0755);
}



