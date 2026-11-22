<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["kerberos-ticket"])){kerberos_ticket();exit;}
if(isset($_GET["start-socket"])){backend_start();exit;}

if(isset($_GET["keytabexists"])){keytabexists();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function kerberos_ticket(){
    $unix=new unix();
    $klist=$unix->find_program("klist");
    $filepath=PROGRESS_DIR."/hacluster.kerberos";
    shell_exec("$klist -kte /home/artica/PowerDNS/Cluster/storage/krb5.keytab >$filepath 2>&1");
}


function keytabexists(){
    $tfile=PROGRESS_DIR."/keytabexists.key";
    $destinationfile="/home/artica/PowerDNS/Cluster/storage/krb5.keytab";
    if(is_file($destinationfile)){
        writelogs_framework("$tfile --> 1",__FUNCTION__,__FILE__,__LINE__);
        @file_put_contents($tfile,1);
        @chown($tfile,"www-data");
        return true;
    }
    $destinationfile="/home/artica/PowerDNS/Cluster/storage/krb5.crypt";
    if(is_file($destinationfile)){
        writelogs_framework("$tfile --> 1",__FUNCTION__,__FILE__,__LINE__);
        @file_put_contents($tfile,1);
        @chown($tfile,"www-data");
        return true;

    }
    writelogs_framework("$tfile --> 0",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($tfile,0);
    @chown($tfile,"www-data");
    return false;
}



function backend_start(){
	$unix=new unix();
    $host=$_GET["start-socket"];
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");


    $Group="proxys";
    if(preg_match("#^Tproxy[0-9]+#",$host)){
        $Group="TPROXY-BACKEND-HTTP";
    }

	$cmd="$echo \"enable server $Group/$host\"|$socat stdio /run/hacluster/admin.sock 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
