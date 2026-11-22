<?php
// stamp 01/11/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["first-install"])){first_install();exit;}
if(isset($_GET["remove"])){remove_pdns();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["restart-dsc"])){dsc_restart();exit;}
if(isset($_GET["add-record"])){power_dns_add_record();exit;}
if(isset($_GET["dsc-status"])){dsc_status();exit;}

if(isset($_GET["activate-dsc"])){dsc_install();exit;}
if(isset($_GET["disable-dsc"])){dsc_uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["recursor-install"])){recursor_install();exit;}
if(isset($_GET["recursor-uninstall"])){recursor_uninstall();exit;}
if(isset($_GET["restart-recusor"])){recursor_restart();exit;}
if(isset($_GET["reload-recusor"])){recursor_reload();exit;}
if(isset($_GET["restart-all"])){restart_all();exit;}
if(isset($_GET["recursor-infos"])){recursor_infos();exit;}
if(isset($_GET["pdns-infos"])){pdns_infos();exit;}
if(isset($_GET["dnssec"])){dnssec();exit;}
if(isset($_GET["dnscheck"])){dnscheck();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["rectify-zone"])){rectify_zone();exit;}
if(isset($_GET["create-zone"])){create_zone();exit;}
if(isset($_GET["rest-install"])){rest_install();exit;}
if(isset($_GET["check-domain"])){check_domain();exit;}

if(isset($_GET["export-backup"])){export_backup();exit;}
if(isset($_GET["import-backup"])){import_backup();exit;}
if(isset($_GET["pdns-util-load-zone"])){pdns_util_load_zone();exit;}
if(isset($_GET["pdns-util-save-zone"])){pdns_util_save_zone();exit;}
if(isset($_GET["repair-database"])){pdns_repair_database();exit;}

if(isset($_GET["syslog"])){searchInSyslog();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	

function pdns_util_load_zone(){
    $unix=new unix();
    $zone=$_GET["pdns-util-load-zone"];
    $pdnsutil=$unix->find_program("pdnsutil");
    $zone_safe=escapeshellarg($zone);
    $zone_base=preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $zone);
    $DESTF=PROGRESS_DIR."/$zone_base.dump";
    shell_exec("$pdnsutil list-zone $zone_safe >$DESTF 2>&1");
    @chown($DESTF,"www-data");
    @chgrp($DESTF,"www-data");
    @chmod($DESTF,0755);
}
function pdns_util_save_zone(){
    $unix=new unix();
    $zone=$_GET["pdns-util-save-zone"];
    $pdnsutil=$unix->find_program("pdnsutil");
    $zone_safe=escapeshellarg($zone);
    $zone_base=preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $zone);
    $DESTF=PROGRESS_DIR."/$zone_base.log";
    $SESTF=PROGRESS_DIR."/$zone_base.save";
    shell_exec("$pdnsutil load-zone $zone_safe $SESTF >$DESTF 2>&1");
    @chown($DESTF,"www-data");
    @chgrp($DESTF,"www-data");
    @chmod($DESTF,0755);
}

function purge(){
	$unix=new unix();
	$pdns_control=$unix->find_program("pdns_control");
	shell_exec("$pdns_control purge >/usr/share/artica-postfix/ressources/logs/web/pdns.purge");
}
function pdns_repair_database(){
    $unix=new unix();
    $unix->framework_execute("exec.pdns.php --repair-db", "pdns.repair.progress",
        "pdns.repair.log");
}

function power_dns_add_record(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.pdns.php --add-record";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --pdns >/usr/share/artica-postfix/ressources/logs/web/pdns.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function dsc_status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --dsc >/usr/share/artica-postfix/ressources/logs/web/dsc.status";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function restart(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function restart_all(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.php --restart-all >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function recursor_restart(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_recursor.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function recursor_reload(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_recursor.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}



function recursor_install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.recursor.install";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.recursor.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --install-recursor >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function recursor_uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.recursor.install";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.recursor.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --uninstall-recursor >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
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


	$xdate=date("Y-m-d");
	$search="$date.*?$mainline";
	$search=str_replace(".*?.*?",".*?",$search);
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/$xdate.pdns.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/pdns.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/pdns.syslog.pattern", $search);
	shell_exec($cmd);

}

function first_install(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}


function rest_install(){
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install";
    $GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --rest-install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}
function dsc_install(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.dsc.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.dsc.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dsc.php --enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}

function dsc_uninstall(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.dsc.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.dsc.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dsc.php --disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function dsc_restart(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.dsc.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.dsc.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dsc.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function remove_pdns(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --remove >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function recursor_infos(){
	$unix=new unix();
	$rec_control=$unix->find_program("rec_control");
	system("$rec_control --socket-dir=/var/run/pdns get-all >/usr/share/artica-postfix/ressources/logs/web/recursor.infos 2>&1");
	
}
function pdns_infos(){
	$unix=new unix();
	$rec_control=$unix->find_program("pdns_control");
	system("$rec_control --socket-dir=/var/run/pdns show \* >/usr/share/artica-postfix/ressources/logs/web/pdns.infos 2>&1");
	system("$rec_control --socket-dir=/var/run/pdns ccounts >/usr/share/artica-postfix/ressources/logs/web/pdns.ccounts 2>&1");

}
function export_backup(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/pdns.import.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.import.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --export >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}

function dnssec(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/pdns.dnssec.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/pdns.dnssec.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --dnssec >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function dnscheck(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --verify-zones >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function import_backup(){
    $unix=new unix();
	$filename=base64_encode($_GET["filename"]);
	$unix->framework_execute("exec.pdns.php --import \"$filename\"","pdns.import.progress","pdns.import.progress.log");

}
function rectify_zone(){
	$domain_id=$_GET["rectify-zone"];
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/dns.rectify-zone.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/dns.rectify-zone.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --rectify-zone $domain_id >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function create_zone(){
    $unix=new unix();
    $domain=base64_decode($_GET["create-zone"]);
    $pdnsutil=$unix->find_program("pdnsutil");
    $nsserver=base64_decode($_GET["ns"]);
    $ipaddr=base64_decode($_GET["ip"]);
    $nsserver_exp=explode(".",$nsserver);
    $logfile=PROGRESS_DIR."/pdns.add.domain.txt";
    $domain_safe=escapeshellarg($domain);
    $nsprefix_safe=escapeshellarg($nsserver_exp[0].".".$domain);
    $nsname_safe=escapeshellarg($nsserver_exp[0]);
    $ipaddr_safe=escapeshellarg($ipaddr);
    shell_exec("$pdnsutil create-zone $domain_safe $nsprefix_safe >$logfile 2>&1");
    shell_exec("$pdnsutil add-record $domain_safe $nsname_safe A $ipaddr_safe >>$logfile 2>&1");
    shell_exec("$pdnsutil set-kind $domain_safe master >>$logfile 2>&1");
    $unix->framework_exec("exec.pdns.php --cleandb");
}
function check_domain(){
    $unix       = new unix();
    $nohup      = $unix->find_program("nohup");
    $php5       = $unix->LOCATE_PHP5_BIN();
    $domain_id  = intval($_GET["check-domain"]);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --zone-info $domain_id >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}


