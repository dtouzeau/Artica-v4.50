<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["config-file"])){config_file();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["postfix-milter"])){postfix_milter();exit;}
if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}
if(isset($_GET["export-computers"])){export_computers();exit;}
if(isset($_GET["import-computers"])){import_computers();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	





function export_computers(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/export-computers.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/export-computers.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.export-import-computers.php --export >{$GLOBALS["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function import_computers(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $filename=$_GET["filename"];
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/export-computers.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/export-computers.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.export-import-computers.php --import $filename >{$GLOBALS["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}










function service_cmds(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/artica-postfix $cmds dhcp 2>&1",$results);
	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}





function postfix_milter(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}


function getramtmpfs(){
	$dir="/var/spool/MIMEDefang";
	if($dir==null){return;}
	$unix=new unix();
	$df=$unix->find_program("df");
	$cmd="$df -h \"$dir\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$df -h \"$dir\" 2>&1",$results);
	foreach ($results as $key=>$value){
		
		if(!preg_match("#tmpfs\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.]+)%\s+.*?MIMEDefang#", $value,$re)){
			writelogs_framework("$value no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		writelogs_framework("{$re[2]}:{$re[4]}%",__FUNCTION__,__FILE__,__LINE__);
			$array["SIZE"]=$re[1];
			$array["PURC"]=$re[4];
			echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
			return;
		
	}
		
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
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/dhcpd.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/dhcpd.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.syslog.pattern", $search);
	shell_exec($cmd);

}

function config_file(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config");
	@copy("/etc/dhcp3/dhcpd.conf", "/usr/share/artica-postfix/ressources/logs/web/dhcpd.config");
	
}