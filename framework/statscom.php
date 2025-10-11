<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}

if(isset($_GET["export-db"])){export_database();exit;}
if(isset($_GET["import-backup"])){import_database();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["template"])){template();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["update"])){update();exit;}
if(isset($_GET["test-smtp"])){test_smtp();exit;}
if(isset($_GET["schedules"])){schedules();exit;}
if(isset($_GET["schedule"])){schedule();exit;}
if(isset($_GET["install-client"])){install_client();exit;}
if(isset($_GET["recategorize-single"])){recategorize_single();exit;}
if(isset($_GET["procedure-status"])){procedure_status();exit;}
if(isset($_GET["searchdebugs"])){searchdebugs();exit;}
if(isset($_GET["check-ports"])){check_ports();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function recategorize_single(){
    $category_id=intval($_GET["recategorize-single"]);
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recategorize-$category_id.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recategorize-$category_id.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.re-categorize.php --recategorize-category $category_id >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --uninstall","stats-com.progress","stats-com.log");
}
function export_database(){
    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --export-db","exportdb.progress","exportdb.progress.txt");
   }
function import_database(){
    $filename=base64_encode($_GET["import-backup"]);
    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --import-db $filename","exportdb.progress","exportdb.progress.txt");

}
function check_ports(){
    $unix=new unix();
    $nc=$unix->find_program("nc");
    $config=unserialize(base64_decode($_GET["check-ports"]));
    $proto=$config["PROTO"];
    $host=$config["HOST"];
    $port=$config["PORT"];
    $fname=PROGRESS_DIR."/check_ports.tmp";
    $ARRAY=array();

    if($proto=="udp"){
        exec("$nc -vnzu $host $port 2>&1",$results);
    }else{
        exec("$nc -vnz $host $port 2>&1",$results);
    }

    foreach ($results as $line){
        if(preg_match("#\s+open$#",$line)){
            $ARRAY["RESULTS"]=true;
            @file_put_contents($fname,serialize($ARRAY));
            return true;
        }

        if(preg_match("#Connection refused#",$line)){
            $ARRAY["RESULTS"]=false;
            $ARRAY["LOG"]="Connection refused";
            @file_put_contents($fname,serialize($ARRAY));
            return true;
        }
        if(preg_match("#succeeded#",$line)){
            $ARRAY["RESULTS"]=true;
            $ARRAY["LOG"]="Success";
            @file_put_contents($fname,serialize($ARRAY));
            return true;
        }

        if(preg_match("#\s+:\s+(.+?)$#",$line,$re)){
            $ARRAY["RESULTS"]=false;
            $ARRAY["LOG"]=$re[1];
            @file_put_contents($fname,serialize($ARRAY));
            return true;
        }

    }
return false;
}


function restart(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/statscom.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/statscom.txt";

    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --restart","logs/statscom.progress","logs/statscom.txt");

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	

}

function install_client(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/statscom.client.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/statscom.client.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom.client.php >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function install(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/stats-com.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/stats-com.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function schedule(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.schedule.progres";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.report.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdf.proxy.daily.php --schedule {$_GET["schedule"]} >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function schedules(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.report.progres";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.report.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom-stats.php --set-schedules >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function test_smtp(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.smtp.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.smtp.progress.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom-stats.php --test-smtp >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function status(){
	
	writelogs_framework("Starting" ,__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --statscom >/usr/share/artica-postfix/ressources/logs/web/statscom.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function update(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.privoxy.php --update --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function events(){
	$search=trim(base64_decode($_GET["ss5events"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}
	
		if($search==null){
	
			$cmd="$grep --binary-files=text -i -E 'Crunch:' /var/log/privoxy/privoxy.log|$tail -n $rp 2>&1";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			exec($cmd,$results);
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/privoxy-events", serialize($results));
	
			return;
		}
	
		$search=$unix->StringToGrep($search);
	
	
		$cmd="$grep --binary-files=text -i -E 'Crunch:' /var/log/privoxy/privoxy.log|$grep --binary-files=text -i -E '$search'|$tail -n $rp 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$cmd",$results);
	
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/privoxy-events", serialize($results));
	
	
}
function searchdebugs()
{
    $search = $_GET["searchdebugs"];
    $target_file = PROGRESS_DIR . "/statsdebug.log";
    $source_file = "/var/log/statscom-stats.log";

    $unix = new unix();
    $tail = $unix->find_program("tail");
    $grep = $unix->find_program("grep");
    $rp = 500;
    if (is_numeric($_GET["rp"])) {
        $rp = intval($_GET["rp"]);
    }
    if($rp>1500){$rp=1500;}
    if ($search == null) {

        $cmd = "$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
        shell_exec($cmd);
        return;
    }
}

function procedure_status(){
    $target_file="/usr/share/artica-postfix/ressources/logs/web/statscom-procedure.log";
    $data1=@file_get_contents("/etc/squid3/logging.conf");
    $data2=@file_get_contents("/etc/rsyslog.d/artica-statscom.conf");
    @file_put_contents($target_file,"$data1\n$data2");
}

function searchlogs(){
    $search=$_GET["searchlogs"];
    $target_file="/usr/share/artica-postfix/ressources/logs/web/statsredis.log";
    $source_file="/var/log/statsredis.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){

        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");



}
