<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["empty-database"])){database_empty();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["version"])){getversion();exit;}
if(isset($_GET["dump-database"])){database_list();exit;}
if(isset($_GET["web-events"])){web_events();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["scandb"])){scandb();exit;}



function database_list(){
	$db="/var/milter-greylist/greylist.db";
	$inc_file="/usr/share/artica-postfix/ressources/logs/mgrelist-db.inc";
	if(isset($_GET["db_path"])){
		$db=base64_decode(trim($_GET["db_path"]));
		$inc_file="/usr/share/artica-postfix/ressources/logs/mgrelist-{$_GET["hostname"]}.inc";
		}
	
	$datas=file_get_contents($db);
	
	$tbl=explode("\n",$datas);
	if(!is_array($tbl)){return null;}

    foreach ($tbl as $line){
		if(trim($line)==null){continue;}
		if(preg_match("#greylisted tuples#",$line)){$KEY="GREY";continue;}
		if(preg_match("#stored tuples#",$line)){$KEY="GREY";continue;}
		if(preg_match("#Auto-whitelisted tuples#",$line)){$KEY="WHITE";continue;}
		
		if(preg_match("#([0-9\.]+)\s+<(.+?)>\s+<(.+?)>#",$line,$re)){
			$conf[]="\$MGREYLIST_DB[\"$KEY\"][]=array('{$re[1]}','{$re[2]}','{$re[3]}');";
			continue;
		}
		
		writelogs_framework("unable to preg_match $line",__FUNCTION__,__FILE__,__LINE__);
	}
	writelogs_framework("DB FILE=\"$db\"",__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("INC FILE=$inc_file",__FUNCTION__,__FILE__,__LINE__);
	
	$file="<?php\n";
	if(is_array($conf)){
	$file=$file.implode("\n",$conf);
	}
	$file=$file."\n";
	$file=$file."?>";
	
	@file_put_contents($inc_file,$file);
	@chmod($inc_file,0755);
	
	
}

function web_events(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$search=trim(base64_decode($_GET["web-events"]));
	$cmdsearch=null;
	$rp=intval($_GET["rp"]);
	if(!is_numeric($rp)){$rp=50;}
	if($search<>null){
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		$cmdsearch="|$grep --binary-files=text -Eii '$search'";
	}
	$cmdline="$grep TAILTHIS /var/log/greylist-web.log$cmdsearch|$tail -n $rp >/usr/share/artica-postfix/ressources/logs/web/greyweb.query";
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdline);
	
	
}


function getversion(){
	return $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_VERSION");
}
function install_tgz(){
	$migration=null;

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.upgrade.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.upgrade.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.upgrade.php --install {$_GET["key"]} {$_GET["OS"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.install.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.install.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function restart(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php --restart-single >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function scandb(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.scan.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.scan.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php --database >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function database_empty(){
	$hostname=$_GET["hostname"];
	if($hostname==null){$hostname="master";}
	if($hostname=="master"){
		$d[]="/var/milter-greylist/greylist.db";
		$d[]="/usr/share/artica-postfix/ressources/logs/mgrelist-db.inc";
	}
	if($hostname<>"master"){
		$d[]="/var/milter-greylist/$hostname/greylist.db";
		$d[]="/usr/share/artica-postfix/ressources/logs/mgrelist-{$_GET["hostname"]}.inc";
	}
	$d[]="/usr/share/artica-postfix/ressources/logs/greylist-count-$hostname.tot";
	$d[]="/usr/share/artica-postfix/ressources/logs/mgrelist-$hostname.inc";
	while (list ($num, $line) = each ($d) ){
		if(is_file($line)){@unlink($line);}
	}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/milter-greylist restart >/dev/null 2>&1 &");
}

?>