<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

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
if(isset($_GET["apply-conf"])){apply_conf();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["stop"])){stop();exit;}
if(isset($_GET["start"])){start();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --haproxy >/usr/share/artica-postfix/ressources/logs/web/haproxy.status 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);

}

function service_cmds(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$command=$_GET["service-cmds"];
	$cmd="$nohup /etc/init.d/haproxy $command >/usr/share/artica-postfix/ressources/logs/web/haproxy.cmds 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/haproxy.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haproxy.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/haproxy.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haproxy.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function stop(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haproxy.php --stop >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function start(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haproxy.php --start >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function uninstall(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{disable_feature}<hr>{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.iwlwifi.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/haproxy.log.tmp";
	$query2=null;
	$sourceLog="/var/log/haproxy.log";
	
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
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/haproxy.log.cmd",$cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}

function install(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.iwlwifi.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function backend_stop(){
	$unix=new unix();
	$array=unserialize(base64_decode($_GET["stop-socket"]));
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");
	if(!is_file($socat)){shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");}
	$cmd="$echo \"disable server {$array[0]}/{$array[1]}\"|$socat stdio /var/run/haproxy.stat 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function backend_start(){
	$unix=new unix();
	$array=unserialize(base64_decode($_GET["start-socket"]));
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");
	if(!is_file($socat)){shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");}
	$cmd="$echo \"enable server {$array[0]}/{$array[1]}\"|$socat stdio /var/run/haproxy.stat 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function global_status(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");
	if(!is_file($socat)){
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");
	}
	
	 $cmd="$echo \"show info\"|$socat stdio /var/run/haproxy.stat 2>&1";
	 exec($cmd,$results);
	 writelogs_framework($cmd."=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function global_statistics(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$socat=$unix->find_program("socat");
	if(!is_file($socat)){
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");
	}
	
	 $cmd="$echo \"show stat\"|$socat stdio unix-connect:/var/run/haproxy.stat >/usr/share/artica-postfix/ressources/logs/web/haproxy.stattus.dmp 2>&1";
	 exec($cmd,$results);
	 writelogs_framework($cmd."=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}



function version(){
	$unix=new unix();
	
	$xr=$unix->find_program("haproxy");
	if(!is_file($xr)){return;}
	exec("$xr -v 2>&1",$results);
	foreach ($results as $line){
        $line=trim($line);
		if(preg_match("#^(HA-Proxy|HAProxy)\s+version\s+([0-9\.]+)#", $line,$re)){$version=$re[2];break;}
	}
	echo "<articadatascgi>$version</articadatascgi>";
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

function apply_conf(){
	$unix=new unix();
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/haproxy2.cfg")){return;}
	@unlink("/etc/haproxy/haproxy.cfg");
	@copy("/usr/share/artica-postfix/ressources/logs/web/haproxy2.cfg","/etc/haproxy/haproxy.cfg");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/haproxy2.cfg");	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.haproxy.php --reload --noconf >/dev/null 2>&1 &");
}


function copy_conf(){
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/haproxy.cfg");
	@copy("/etc/haproxy/haproxy.cfg","/usr/share/artica-postfix/ressources/logs/web/haproxy.cfg");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/haproxy.cfg", 0755);
	
}

function status_instance_stats(){
	$ID=$_GET["ID"];
	$unix=new unix();
	$pidfile="/var/run/crossroads/cross_$ID.pid";
	@file_put_contents("/var/log/crossroads/cross_$ID.log", "\n");
	$pid=trim(@file_get_contents($pidfile));
	$kill=$unix->find_program("kill");
	shell_exec("$kill -1 $pid");
	$datas=explode("\n",@file_get_contents("/var/log/crossroads/cross_$ID.log"));
	foreach ($datas as $index=>$line){
		if(preg_match("#REPORT Back end\s+([0-9]+):\s+(.+?),\s+weight\s+([0-9]+)#", $line,$re)){
			$back=$re[1];
			$array[$back]["NAME"]=trim($re[2]);
			$array[$back]["WEIGHT"]=trim($re[3]);
			continue;	
		}
		
		if(preg_match("#Status:\s+(.+)#",$line,$re)){
			$array[$back]["STATUS"]=$re[1];
			continue;	
		}
		
		if(preg_match("#Connections:\s+([0-9]+)#",$line,$re)){
			$array[$back]["CNX"]=$re[1];
			continue;	
		}
		
		if(preg_match("#Served:(.+?),\s+([0-9]+)\s+clients#",$line,$re)){
			$array[$back]["FLOW"]=$re[1];
			$array[$back]["CLIENTS"]=$re[2];
			continue;	
		}
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
	
	
}