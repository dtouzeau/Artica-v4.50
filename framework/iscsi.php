<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);



if(isset($_GET["build-server-config"])){build_config_server();exit;}
if(isset($_GET["volumes"])){volumes();exit;}
if(isset($_GET["iscsi-search"])){iscsi_search();exit;}
if(isset($_GET["iscsi-sessions"])){iscsi_client_sessions();exit;}
if(isset($_GET["install"])){iscsi_install();exit;}
if(isset($_GET["uninstall"])){iscsi_uninstall();exit;}
if(isset($_GET["delete-client"])){iscsi_delete();exit;}
if(isset($_GET["remove-server-config"])){iscsi_remove_lun();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function volumes(){
@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/proc.net.iet.volume",@file_get_contents("/proc/net/iet/volume"));
@chmod("/usr/share/artica-postfix/ressources/logs/web/proc.net.iet.volume",0755);
}


function build_config_server(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/system_disks_iscsi_progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --build --force --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}

function iscsi_remove_lun(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$ID=$_GET["remove-server-config"];
	
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/system_disks_iscsi_progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --remove-lun $ID --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	
}

function iscsi_install(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/iscsi.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iscsi.install.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
}
function iscsi_uninstall(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/iscsi.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iscsi.install.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}

function iscsi_delete(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/iscsi.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iscsi.install.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --delete-client {$_GET["delete-client"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	
}


function iscsi_search(){
	

	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system_disks_iscsi_progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system_disks_iscsi_progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --search \"{$_GET["iscsi-search"]}\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	
	

}
function iscsi_client_sessions(){
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	$cmd="$iscsiadm -m session 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$array=array();
	foreach ($results as $index=>$line){
		
		if(!preg_match("#\[([0-9]+)\]\s+([0-9\.]+):([0-9]+),([0-9]+)\s+(.+?):(.+)#",$line,$re)){continue;}
		$STATE=null;
		$cmd="$iscsiadm -m session -r {$re[1]} -P 3 2>&1";
		exec($cmd,$SESSIONSR);
		foreach ($SESSIONSR as $b){
			if(preg_match("#iSCSI Connection State:\s+(.+)#i", $b,$ri)){
				$STATE=trim($ri[1]);
				continue;
			}
			if(preg_match("#Attached scsi disk\s+(.+?)\s+State:\s+(.+)#i",$b,$ri)){
				$DEVNAME=trim($ri[1]);
				$DEVSTATE=trim($ri[2]);
			}
		}
		
		
		
		$array[$re[2]][]=array("PORT"=>$re[3],"ID"=>$re[4],"ISCSI"=>$re[5],"FOLDER"=>$re[6],"IP"=>$re[2],"STATE"=>$STATE,
				"DEVNAME"=>$DEVNAME,"DEVSTATE"=>$DEVSTATE
					
				
		);
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/iscsi-sessions.array", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/iscsi-sessions.array", 0755);
	

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

	if($PID<>null){$PID_P=".*?\[$PID\].*?";}
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
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/iscsid.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/iscsi.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/iscsi.syslog.pattern", $search);
	shell_exec($cmd);

}