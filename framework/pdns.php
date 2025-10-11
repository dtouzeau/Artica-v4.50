<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["reload-tenir"])){reload_tenir();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["rebuild-database"])){rebuild_database();exit;}
if(isset($_GET["replic"])){replic_artica_servers();exit;}
if(isset($_GET["digg"])){digg();exit;}
if(isset($_GET["repair-tables"])){repair_tables();exit;}
if(isset($_GET["build-smooth-tenir"])){reload_tenir();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
if(isset($_GET["import-file"])){import_fromfile();exit;}
if(isset($_GET["first-install"])){first_install();exit;}
if(isset($_GET["remove"])){remove_pdns();exit;}
if(isset($_GET["activate"])){activate_pdns();exit;}
if(isset($_GET["activate-ufdb"])){activate_ufdb();exit;}
if(isset($_GET["disable-ufdb"])){disable_ufdb();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["restart-dsc"])){dsc_restart();exit;}
if(isset($_GET["add-record"])){power_dns_add_record();exit;}
if(isset($_GET["dsc-status"])){dsc_status();exit;}
if(isset($_GET["rpz"])){rpz();exit;}


if(isset($_GET["poweradmin-install"])){poweradmin_install();exit;}
if(isset($_GET["poweradmin-disable"])){poweradmin_disable();exit;}
if(isset($_GET["poweradmin-enable"])){poweradmin_enable();exit;}
if(isset($_GET["reload-poweradmin"])){poweradmin_reload();exit;}
if(isset($_GET["activate-dsc"])){dsc_install();exit;}
if(isset($_GET["disable-dsc"])){dsc_uninstall();exit;}
if(isset($_GET["reconfigure-all"])){reconfigure_all();exit;}
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
if(isset($_GET["rest-uninstall"])){rest_uninstall();exit;}
if(isset($_GET["check-domain"])){check_domain();exit;}



if(isset($_GET["export-backup"])){export_backup();exit;}
if(isset($_GET["import-backup"])){import_backup();exit;}
if(isset($_GET["pdns-util-load-zone"])){pdns_util_load_zone();exit;}
if(isset($_GET["pdns-util-save-zone"])){pdns_util_save_zone();exit;}
if(isset($_GET["repair-database"])){pdns_repair_database();exit;}

if(isset($_GET["syslog"])){searchInSyslog();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	

function rpz():bool{
    $unix=new unix();
    $tfile="/etc/powerdns/PowerDNS.lua";
    if(!is_file($tfile)){
        $unix->framework_exec("exec.pdns_recursor.php --restart");
        $unix->framework_exec("exec.pdns.rpz.php");
        return true;
    }
    $unix->framework_exec("exec.pdns.rpz.php");
    $unix->framework_exec("exec.pdns_recursor.php --rpz");
    return true;
}

function service_cmds(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("$php /usr/share/artica-postfix/exec.pdns_server.php --$cmds  2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function pdns_util_load_zone(){
    $unix=new unix();
    $zone=$_GET["pdns-util-load-zone"];
    $pdnsutil=$unix->find_program("pdnsutil");
    $DESTF=PROGRESS_DIR."/$zone.dump";
    shell_exec("$pdnsutil list-zone $zone >$DESTF 2>&1");
    @chown($DESTF,"www-data");
    @chgrp($DESTF,"www-data");
    @chmod($DESTF,0755);
}
function pdns_util_save_zone(){
    $unix=new unix();
    $zone=$_GET["pdns-util-save-zone"];
    $pdnsutil=$unix->find_program("pdnsutil");
    $DESTF=PROGRESS_DIR."/$zone.log";
    $SESTF=PROGRESS_DIR."/$zone.save";
    shell_exec("$pdnsutil load-zone $zone $SESTF >$DESTF 2>&1");
    @chown($DESTF,"www-data");
    @chgrp($DESTF,"www-data");
    @chmod($DESTF,0755);
}

function rebuild_database(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --rebuild-database 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
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
function dsc_exclude(){

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.status.php --excludes >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function reload_tenir(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.pdns_server.php --reload 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}
function reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.php --reload >/dev/null 2>&1 &");
}
function reconfigure(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.php --restart >/dev/null 2>&1 &");
}
function poweradmin_reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.poweradmin.php --restart >/dev/null 2>&1 &");	
}

function repair_tables(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.repair.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.repair.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --mysql --verbose > {$GLOBALS["LOGSFILES"]} 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	
}

function reconfigure_all(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/pdns.reconfigure.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.reconfigure.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --reconfigure-all --verbose > {$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function disable_ufdb(){
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
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --uninstall-ufdb >{$GLOBALS["LOGSFILES"]} 2>&1 &";
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

function activate_ufdb(){
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
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --install-ufdb >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
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
function rest_uninstall(){
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
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --rest-uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function poweradmin_install(){
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
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --install-poweradmin >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}

function poweradmin_disable(){
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
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --disable-poweradmin >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function poweradmin_enable(){
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
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --enable-poweradmin >{$GLOBALS["LOGSFILES"]} 2>&1 &";
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

function activate_pdns(){
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
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
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

function replic_artica_servers(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --replic-artica 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}
function digg(){
	$unix=new unix();
	$digg=$unix->find_program("dig");
	$hostname=$_GET["hostname"];
	$interface=$_GET["interface"];
	$timeout=intval($_GET["timeout"]);
	$dns_server=$_GET["dns_server"];
	$doh_uri=$_GET["doh_uri"];
	$PTR=intval($_GET['PTR']);
    if($timeout==0){$timeout=2;}
    $cmd=null;
    $ptr_text=null;
	if($doh_uri<>null){
        if(!is_file("/usr/sbin/doh")){
            @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/pdns.query","Error: /usr/sbin/doh no such file...");
            return;
        }

        $cmd="/usr/sbin/doh \"$hostname\" \"$doh_uri\" > /usr/share/artica-postfix/ressources/logs/web/pdns.query 2>&1";
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;

    }
    if($interface==null){$interface="eth0";}
	if($hostname==null){$hostname="www.google.com";}
	$interface2=$unix->InterfaceToIPv4($interface);
	
	if($PTR==1){
        $cmd="$digg -x $hostname +time={$timeout} +tries=1 @$dns_server -b $interface2 -4 >/usr/share/artica-postfix/ressources/logs/web/pdns.query 2>&1";
    }
    if($cmd==null) {
        $cmd = "$digg +time={$timeout} +tries=1 @$dns_server -b $interface2 -q $hostname -4 >/usr/share/artica-postfix/ressources/logs/web/pdns.query 2>&1";
    }
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
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
    shell_exec("$pdnsutil create-zone $domain {$nsserver_exp[0]}.$domain >$logfile 2>&1");
    shell_exec("$pdnsutil add-record $domain {$nsserver_exp[0]} A $ipaddr >>$logfile 2>&1");
    shell_exec("$pdnsutil set-kind $domain master >>$logfile 2>&1");
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


function import_fromfile(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$file=$_GET["import-file"];
	$domain=$_GET["domain"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.pdns.import.php --import $file $domain 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";	
	
}