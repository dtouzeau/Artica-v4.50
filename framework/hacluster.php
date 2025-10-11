<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["kerberos-ticket"])){kerberos_ticket();exit;}
if(isset($_GET["syslog-sysevnts"])){searchInSyslog_single_backends();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["syslog-backends"])){searchInSyslog_backends();exit;}
if(isset($_GET["cnxs"])){searchInSyslog_connections();exit;}
if(isset($_GET["ad-wizard"])){ad_wizard();exit;}
if(isset($_GET["status-instance"])){status_instance();exit;}
if(isset($_GET["statrt"])){status_instance_stats();exit;}
if(isset($_GET["start-instance"])){start_instance();exit;}
if(isset($_GET["stop-instance"])){stop_instance();exit;}
if(isset($_GET["restart-instance"])){restart_instance();exit;}
if(isset($_GET["restart-instance-silent"])){restart_instance_silent();exit;}
if(isset($_GET["build-instance"])){build_instance();exit;}
if(isset($_GET["reload-all-instances"])){reload_all_instances();exit;}
if(isset($_GET["main-status"])){status();exit;}
if(isset($_GET["global-status"])){global_status();exit;}
if(isset($_GET["global-stats"])){global_statistics();exit;}
if(isset($_GET["version"])){version();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["stop-socket"])){backend_stop();exit;}
if(isset($_GET["start-socket"])){backend_start();exit;}
if(isset($_GET["copy-conf"])){copy_conf();exit;}

if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["disconnect"])){client_disconnect();exit;}
if(isset($_GET["stop-transparent"])){stop_transparent();exit;}
if(isset($_GET["start-transparent"])){start_transparent();exit;}
if(isset($_GET["keytabexists"])){keytabexists();exit;}
if(isset($_GET["install-keytab"])){install_keytab();exit;}
if(isset($_GET["connect-nodes"])){connect_nodes();exit;}
if(isset($_GET["setup-client"])){setup_clients();exit;}



foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --hacluster >/usr/share/artica-postfix/ressources/logs/web/hacluster.status 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}
function kerberos_ticket(){
    $unix=new unix();
    $klist=$unix->find_program("klist");
    $filepath=PROGRESS_DIR."/hacluster.kerberos";
    shell_exec("$klist -kte /home/artica/PowerDNS/Cluster/storage/krb5.keytab >$filepath 2>&1");
}
function start_transparent(){
    $FOUND=false;
    $unix=new unix();
    $ID=$_GET["start-transparent"];
    writelogs_framework("start_transparent($ID)",__FUNCTION__,__FILE__,__LINE__);
    $f=explode("\n",@file_get_contents("/etc/hacluster/tproxy.conf"));
    foreach ($f as $index=>$line){

        if(preg_match("#cache_peer.*?name=$ID#",$line)){
            writelogs_framework("$line found, disable mark",__FUNCTION__,__FILE__,__LINE__);
            $f[$index]=str_replace("#","",$line);
            $FOUND=true;
            break;
        }
    }

    if(!$FOUND){
        writelogs_framework("$ID not found, reconfigure the whole service",__FUNCTION__,__FILE__,__LINE__);
        $php5=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php5 /usr/share/artica-postfix/exec.hacluster.php --reload-transparent");
        return true;
    }

    @file_put_contents("/etc/hacluster/tproxy.conf",@implode("\n",$f));
    $unix->go_exec("/usr/sbin/hacluster-transparent -f /etc/hacluster/tproxy.conf -k reconfigure");
    return true;
}

function stop_transparent(){
    $ID=$_GET["stop-transparent"];
    $unix=new unix();
    $f=explode("\n",@file_get_contents("/etc/hacluster/tproxy.conf"));
    foreach ($f as $index=>$line){

        if(!preg_match("#cache_peer.*?name=$ID#",$line)){
            writelogs_framework("BAD line 'name=$ID' $line index[$index]",__FUNCTION__,__FILE__,__LINE__);
            continue;
        }

        writelogs_framework("OK line $line index[$index]",__FUNCTION__,__FILE__,__LINE__);
        $f[$index]="#$line";

    }

    @file_put_contents("/etc/hacluster/tproxy.conf",@implode("\n",$f));
    $unix->go_exec("/usr/sbin/hacluster-transparent -f /etc/hacluster/tproxy.conf -k reconfigure");

}


function ad_wizard(){
    $unix=new unix();
    $unix->framework_execute("exec.hacluster.php --ad-wizard",
        "hacluster.wizard.progress",
        "hacluster.wizard.log");

}


function client_disconnect(){
    $unix=new unix();
    $unix->framework_execute("exec.hacluster.connect.php --disconnect ",
        "hacluster.disconnect.progress",
        "hacluster.disconnect.log");

}

function setup_clients(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php /usr/share/artica-postfix/exec.hacluster.connect.php --setup >/dev/null 2>&1 &";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);

}

function install_keytab(){
    $unix=new unix();
    $filename=$_GET["filename"];

    $unix->framework_execute("exec.hacluster.php --keytab \"$filename\"",
        "hacluster.ticket.progress",
        "hacluster.ticket.log");
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

function connect_nodes(){
    $unix=new unix();

    $unix->framework_exec("exec.hacluster.php --rebuildif");

    $filename=$_GET["filename"];
    $unix->framework_execute("exec.hacluster.connect.php --connect \"$filename\"",
        "hacluster.connect.progress",
        "hacluster.connect.txt");
}

function service_cmds(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$command=$_GET["service-cmds"];
	$cmd="$nohup /etc/init.d/hacluster $command >/usr/share/artica-postfix/ressources/logs/web/hacluster.cmds 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}









function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/hacluster.log.tmp";
	$query2=null;
	$sourceLog="/var/log/hacluster.log";
	
	if($_GET["FinderList"]<>null){
		$filename_compressed="/usr/share/artica-postfix/ressources/logs/web/logsfinder/{$_GET["FinderList"]}.gz";
		$filename_logs="/usr/share/artica-postfix/ressources/logs/web/logsfinder/{$_GET["FinderList"]}.log";
		if(is_file($filename_compressed)){
			if(!is_file($filename_logs)){
				$unix->uncompress($filename_compressed, $filename_logs);
				@chmod($filename_logs,0755);
				$sourceLog=$filename_logs;
			}else{
				$sourceLog=$filename_logs;
			}
		}
	}
	
	
	
	$rp=intval($_GET["rp"]);
	writelogs_framework("access_real -> $rp search {$_GET["query"]} SearchString = {$_GET["SearchString"]}" ,__FUNCTION__,__FILE__,__LINE__);
	
	$query=$_GET["query"];
	if($_GET["SearchString"]<>null){
		$query2=$query;
		$query=$_GET["SearchString"];
	}
	
	$grep=$unix->find_program("grep");
	$pattern2=array();
	
	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";
	
	if($query2<>null){
		$pattern2=str_replace(".", "\.", $query2);
		$pattern2=str_replace("*", ".*?", $pattern2);
		$pattern2=str_replace("/", "\/", $pattern2);
		$cmd2="$grep --binary-files=text -Ei \"$pattern2\"| ";
		$cmd3="$grep --binary-files=text -Ei \"$pattern2\"";
	}
	
	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){
		
		if(preg_match("#error(\s+|=)([0-9]+)#i", $pattern,$re)){
			$pattern2[]="[0-9\+]+\s+{$re[2]}\s+[0-9\+]+";
		}
		
		if(preg_match("#ip(\s+|=)([0-9\.]+)#", $query,$re)){
			$pattern2[]=":\s+".str_replace(".", "\.", $re[2]).":";
		}
		
		if(preg_match("#to(\s+|=)([0-9\-\_a-zA-Z]+)#", $query,$re)){
			$pattern2[]="\/{$re[2]}\s+[0-9]+";
		}
		
		if(preg_match("#site(\s+|=)(.+?)($|\s+)#", $query,$re)){
			$re[2]=str_replace(".", "\.", $re[2]);
			$pattern2[]="(CONNECT|GET|POST)\s+{$re[2]}";
		}		
		
		if(count($pattern2)>0){
			$pattern=$pattern2[0];
			unset($pattern2[0]);
		}
		if(count($pattern2)>0){
			foreach ($pattern2 as $pp){
				$FINALP[]="$grep --binary-files=text -Ei \"$pp\"";
			}
			
			$cmd2=@implode("| ", $FINALP)."|";
		}
		
		
		
		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$cmd2$tail -n $rp  >$targetfile 2>&1";
	}else{
		if($cmd3<>null){
			$cmd="$cmd3 $sourceLog|$cmd2 $tail -n $rp  >$targetfile 2>&1";
		}
		
	}
	
	
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/hacluster.log.cmd",$cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}

function backend_stop(){
	$unix=new unix();
	$host=$_GET["stop-socket"];
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");

	$Group="proxys";
	if(preg_match("#^Tproxy[0-9]+#",$host)){
	    $Group="TPROXY-BACKEND-HTTP";
    }


	$cmd="$echo \"disable server $Group/$host\"|$socat stdio /run/hacluster/admin.sock 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
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
function global_status(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");
	 $cmd="$echo \"show info\"|$socat stdio /run/hacluster/admin.sock 2>&1";
	 exec($cmd,$results);
	 writelogs_framework($cmd."=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function global_statistics(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");
	 $cmd="$echo \"show stat\"|$socat stdio unix-connect:/run/hacluster/admin.sock >/usr/share/artica-postfix/ressources/logs/web/hacluster.stattus.dmp 2>&1";
	 exec($cmd,$results);
	 writelogs_framework($cmd."=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}



function version():bool{
	$unix=new unix();
	$xr=$unix->find_program("hacluster");
    $version=$unix->HaProxyVersion($xr);
	echo "<articadatascgi>$version</articadatascgi>";
    return true;
}	

function restart_instance_silent(){
	$id=$_GET["ID"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.loadbalance.php --restart-instance $id >/dev/null 2>&1 &");
	shell_exec($cmd);		
}

function restart_instance(){
	$id=$_GET["ID"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --stop-instance $id 2>&1";
	exec($cmd,$results);
	
	$cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --build-instance $id 2>&1";
	exec($cmd,$results);	
	
	$cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --start-instance $id 2>&1";
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". @implode("\n", $results)."</articadatascgi>";	
}
function build_instance(){
	$id=$_GET["ID"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.loadbalance.php --build-instance $id >/dev/null 2>&1 &");
	shell_exec($cmd);

}
function start_instance(){
	$id=$_GET["ID"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --start-instance $id 2>&1";
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". @implode("\n", $results)."</articadatascgi>";	
}
function stop_instance(){
	$id=$_GET["ID"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --stop-instance $id 2>&1";
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". @implode("\n", $results)."</articadatascgi>";	
}

function reconfigure_all_instances(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php /usr/share/artica-postfix/exec.loadbalance.php --build >/dev/null 2>&1");
	shell_exec($cmd);		
}

function reload_all_instances(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.loadbalance.php --reload >/dev/null 2>&1 &");
	
}

function copy_conf(){
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/hacluster.cfg");
	@copy("/etc/hacluster/hacluster.cfg","/usr/share/artica-postfix/ressources/logs/web/hacluster.cfg");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/hacluster.cfg", 0755);
	
}

function searchInSyslog_connections(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["cnxs"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    $PID=$MAIN["PID"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$PID_P}{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/hacluster-connections.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/hacluster.cnxs.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster.cnxs.syslog.query", $search);
    shell_exec($cmd);

}

function searchInSyslog_single_backends(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog-sysevnts"]));
    $hostname=$_GET["hostname"];


    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);
    if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    $search="$date.*?$hostname.*?$TERM";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/hacluster-client.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/hacluster-$hostname.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster-clients.syslog.query", $search);
    shell_exec($cmd);

}

function searchInSyslog_backends(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog-backends"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    $PID=$MAIN["PID"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$PID_P}{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/hacluster-client.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/hacluster-clients.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster-clients.syslog.query", $search);
    shell_exec($cmd);

}

function searchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    $PID=$MAIN["PID"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}


    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$PID_P}{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/hacluster.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/hacluster.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster.syslog.query", $search);
    shell_exec($cmd);

}