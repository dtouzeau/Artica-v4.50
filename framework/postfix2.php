<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["queue-params"])){queue_parameters();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["postfix-notifs-progress"])){postfix_notifs();exit;}
if(isset($_GET["transport"])){transport();exit;}
if(isset($_GET["history-search"])){history_search();exit;}
if(isset($_GET["history-delete"])){history_delete();exit;}
if(isset($_GET["aliases"])){postfix_aliases_build();exit;}
if(isset($_GET["all-status"])){all_status();exit;}
if(isset($_GET["all-status-wait"])){all_status_wait();exit;}
if(isset($_GET["milter-greylist-update"])){milter_greylist_update();exit;}
if(isset($_GET["smtpd-milter-maps"])){smtpd_milter_maps();exit;}
if(isset($_GET["tls"])){tls();exit;}
if(isset($_GET["postfix-ipset-compile"])){postfix_ipset_compile();exit;}
if(isset($_GET["postfix-ipset"])){postfix_ipset();exit;}
if(isset($_GET["smtp-member-stats"])){smtp_sender_stats();exit;}

if(isset($_GET["smtp-transactions"])){smtp_transactions();exit;}
if(isset($_GET["sasl"])){postfix_sasl();exit;}
if(isset($_GET["multi-install"])){multiple_install();exit;}
if(isset($_GET["multi-uninstall"])){multiple_uninstall();exit;}
if(isset($_GET["multi-status"])){multiple_status();exit;}
if(isset($_GET["multi-reconfigure-single"])){multiple_instance_reconfigure();exit;}
if(isset($_GET["multi-reconfigure"])){multiple_reconfigure();exit;}
if(isset($_GET["multi-stop"])){multiple_stop();exit;}
if(isset($_GET["multi-start"])){multiple_start();exit;}
if(isset($_GET["multi-restart"])){multiple_restart();exit;}
if(isset($_GET["multi-install-instance"])){multiple_install_instance();exit;}
if(isset($_GET["multi-uninstall-instance"])){multiple_uninstall_instance();exit;}
if(isset($_GET["instance-status"])){multiple_instance_status();exit;}
if(isset($_GET["smtp-tool"])){smtp_tool();exit;}
if(isset($_GET["instance-interface"])){multiple_instance_interface();exit;}
if(isset($_GET["reinstall-instance"])){multiple_instance_reinstall();exit;}
if(isset($_GET["postfix-single-status"])){postfix_single_status();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);

function postfix_single_status():bool{
    $unix = new unix();
    $unix->framework_exec("exec.status.php --postfix");
    return true;
}

function multiple_install():bool{
    $unix=new unix();
    $unix->framework_execute("exec.postfix-multi.php --install","postfix-multi.progress","postfix-multi.progress.log");
    return true;
}
function multiple_reconfigure():bool{
    $unix=new unix();
    $unix->framework_execute("exec.postfix-multi.php --reconfigure-all","postfix-multi.progress","postfix-multi.progress.log");
    return true;
}
function multiple_uninstall():bool{
    $unix=new unix();
    $unix->framework_execute("exec.postfix-multi.php --uninstall","postfix-multi.progress","postfix-multi.progress.log");
    return true;
}
function multiple_instance_reconfigure():bool{
    $unix=new unix();
    $instance_id=intval($_GET["multi-reconfigure-single"]);
    $unix->framework_execute("exec.postfix-multi.php --reconfigure-instance $instance_id",
        "postfix-multi.$instance_id.reconfigure.progress",
        "postfix-multi.$instance_id.reconfigure.log");

    return true;
}

function multiple_stop():bool{
    $unix=new unix();
    $id=$_GET["multi-stop"];
    $unix->framework_execute("exec.postfix-multi.php --instance-stop $id",
    "postfix-multi.$id.progress",
        "postfix-multi.$id.progress.log");
    return true;
}
function multiple_instance_reinstall():bool{
    $unix=new unix();
    $instance_id=$_GET["reinstall-instance"];
    $unix->framework_execute("exec.postfix-multi.php --instance-reinstall $instance_id",
        "postfix-multi.$instance_id.reinstall.progress",
        "postfix-multi.$instance_id.reinstall.log");
    return true;

}

function multiple_start(){
    $unix=new unix();
    $id=$_GET["multi-start"];
    $unix->framework_execute("exec.postfix-multi.php --instance-start $id",
        "postfix-multi.$id.progress",
        "postfix-multi.$id.progress.log");
}
function multiple_install_instance():bool{
    $unix=new unix();
    $id=$_GET["multi-install-instance"];
    $unix->framework_execute("exec.postfix-multi.php --instance-install $id",
        "postfix-multi.$id.install.progress",
        "postfix-multi.$id.install.progress.log");
    return true;
}
function multiple_instance_interface():bool{
    $unix=new unix();
    $id=$_GET["instance-interface"];
    $interface=$_GET["interface"];
    $unix->framework_execute("exec.postfix-multi.php --interface-change $id $interface",
        "postfix-multi.$id.interface.progress",
        "postfix-multi.$id.interface.log");
    return true;
}
function multiple_uninstall_instance():bool{
    $unix=new unix();
    $id=$_GET["multi-uninstall-instance"];
    $unix->framework_execute("exec.postfix-multi.php --instance-uninstall $id",
        "postfix-multi.$id.install.progress",
        "postfix-multi.$id.install.progress.log");

    return true;
}



function multiple_restart(){
    $unix=new unix();
    $id=$_GET["multi-restart"];
    $unix->framework_execute("exec.postfix-multi.php --instance-restart $id",
        "postfix-multi.$id.restart.progress",
        "postfix-multi.$id.restart.progress.log");
}

function smtp_tool(){
    $fname=PROGRESS_DIR."/smtp.tool.infos";
    $tool="/usr/share/artica-postfix/bin/smtp-client";
    @chmod($tool,0755);
    shell_exec("$tool > $fname 2>&1");
    @chmod($fname,0755);
}

function multiple_status():bool{
    $ID=$_GET["multi-status"];
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    writelogs_framework("$php /usr/share/artica-postfix/exec.postfix-multi.php --status $ID",__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.postfix-multi.php --status $ID");
    return true;
}
function multiple_instance_status():bool{
    $ID=intval($_GET["instance-status"]);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    writelogs_framework("php /usr/share/artica-postfix/exec.postfix-multi.php --instance-status $ID" ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.postfix-multi.php --instance-status $ID --nostart");
    return true;

}

function all_status(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.status.php --all-postfix --nowachdog 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function all_status_wait(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --all-postfix --nowachdog";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function history_delete(){
	@unlink("/home/artica/postfix/history/{$_GET["history-delete"]}.log");
}

function postfix_ipset_compile(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.ipsets.php --compile >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function postfix_ipset(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.ipsets.php --force >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function postfix_sasl(){
    $unix=new unix();
    $unix->framework_execute("exec.postfix.maincf.php --smtp-sasl","SMTP_SASL_PROGRESS","SMTP_SASL_LOG");
}

function smtp_transactions(){

    $query=$_GET["smtp-transactions"];
    $tfile=$_GET["tfile"];


    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$tfile.sql",base64_decode($query));
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp.transactions.$tfile.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp.transactions.$tfile.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    $array["POURC"]=2;
    $array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.transaction.query.php $tfile >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}





function transport(){
    $instance_id=intval($_GET["instance-id"]);
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.transport.php --instance-id=$instance_id >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function tls(){
	$instance_id=intval($_GET["instance-id"]);
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_tls_policy_maps.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_tls_policy_maps.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.tls.php --instance-id=$instance_id >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function history_search(){


    $patternenc=$_GET["history-search"];
    $md5=$_GET["md5"];

    $finalfile="/usr/share/artica-postfix/ressources/logs/postfix.events.$md5.results";


	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/postfix.events.$md5.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR."/postfix.events.$md5.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);

	if(is_file("$finalfile")){
        $array["POURC"]=100;
        $array["TEXT"]="{done}";
        file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
        file_put_contents($GLOBALS["LOGSFILES"], "Done...");
        @chmod($GLOBALS["LOGSFILES"],0777);
        @chmod($GLOBALS["PROGRESS_FILE"],0777);
        return;
    }


    $array["POURC"]=2;
    $array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.logsearch.php \"$patternenc\" \"$md5\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function milter_greylist_update(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.update.php >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function postfix_aliases_build(){
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.aliases.progress";
$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.aliases.progress.txt";
@unlink($GLOBALS["PROGRESS_FILE"]);
@unlink($GLOBALS["LOGSFILES"]);
@touch($GLOBALS["PROGRESS_FILE"]);
@touch($GLOBALS["LOGSFILES"]);
@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
@chmod($GLOBALS["LOGSFILES"],0777);
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --aliases >{$GLOBALS["LOG_FILE"]} 2>&1 &";
writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
shell_exec($cmd);

}
function restart(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.progress.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.php --restart >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function smtp_sender_stats(){
    $email=$_GET["smtp-member-stats"];
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp.stats.progress";
    $GLOBALS["LOG_FILE"]=    "/usr/share/artica-postfix/ressources/logs/web/smtp.stats.progress.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.statistics.MEMBERS.build.php --email \"$email\" >/usr/share/artica-postfix/ressources/logs/web/smtp.stats.progress.log 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function postfix_notifs(){
    $instance_id=intval($_GET["instance-id"]);
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_postfix_templates";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_postfix_templates.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --notifs-templates --instance-id=$instance_id >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function queue_parameters(){
    $unix=new unix();
    $instance_id=intval($_GET["instance-id"]);
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --queue-params --instance-id=$instance_id >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}