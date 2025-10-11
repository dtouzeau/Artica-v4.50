<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["ipset-itself"])){ipset_itself();}
if(isset($_GET["is-installed"])){is_installed();exit;}
if(isset($_GET["reconfigure-progress"])){reconfigure_progress();exit;}
if(isset($_GET["reconfigure-qos-progress"])){reconfigure_qos_progress();exit;}
if(isset($_GET["firehol-version"])){firehol_version();exit;}
if(isset($_GET["accesses"])){access_real();exit;}
if(isset($_GET["qos-install"])){qos_install();exit;}
if(isset($_GET["qos-apply"])){reconfigure_qos_progress();exit;}
if(isset($_GET["qos-remove"])){qos_remove();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["rules-status"])){rules_status();exit;}

if(isset($_GET["install-link-balancer"])){install_link_balancer();exit;}
if(isset($_GET["uninstall-link-balancer"])){uninstall_link_balancer();exit;}
if(isset($_GET["configure-link-balancer"])){configure_link_balancer();exit;}
if(isset($_GET["transparent-status"])){transparent_status();exit;}
if(isset($_GET["modules-infos"])){iptables_modules_infos();exit;}

if(isset($_GET["traffic-shap-remove"])){traffic_shap_remove();exit;}
if(isset($_GET["traffic-shap-add"])){traffic_shap_add();exit;}
if(isset($_GET["block-this"])){blockthis();exit;}

if(isset($_GET["cybercrime-install"])){cybercrime_install();exit;}
if(isset($_GET["cybercrime-uninstall"])){cybercrime_uninstall();exit;}
if(isset($_GET["action-trusted"])){action_trusted();exit;}
if(isset($_GET["articapcap-install"])){articapcap_install();exit;}
if(isset($_GET["articapcap-uninstall"])){articapcap_uninstall();exit;}
if(isset($_GET["articapcap-status"])){articapcap_status();exit;}
if(isset($_GET["articapcap-restart"])){articapcap_restart();exit;}
if(isset($_GET["articapcap-events"])){articapcap_events();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function articapcap_install():bool{
    $unix=new unix();
    $unix->framework_execute("exec.articapcap.php --install","articapcap.progress", "articapcap.log");
    return true;
}
function articapcap_status():bool{
    $unix=new unix();
    $unix->framework_exec("exec.status.php --articapcap");
    return true;
}
function articapcap_restart():bool{
    $unix=new unix();
    $unix->framework_execute("exec.articapcap.php --restart","articapcap.restart", "articapcap.restart.log");
    return true;
}
function articapcap_uninstall():bool{
    $unix=new unix();
    $unix->framework_execute("exec.articapcap.php --uninstall","articapcap.progress", "articapcap.log");
    return true;
}
function action_trusted():bool{
    $unix=new unix();
    $unix->framework_exec("exec.firehol.php --action-trusted");
    return true;
}
function cybercrime_install(){
    $unix=new unix();
    $unix->framework_execute("exec.firehol.php --cybercrime-install",
        "cybercrime.progress","cybercrime.log");
}
function cybercrime_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.firehol.php --cybercrime-uninstall",
        "cybercrime.progress","cybercrime.log");
}


function blockthis(){
    $ip=$_GET["block-this"];
    $MARKLOG="-m comment --comment \"WebConsoleBan\"";
    $cmd="/usr/sbin/iptables -I INPUT -p tcp -s $ip $MARKLOG -j REJECT --reject-with icmp-port-unreachable";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}

function traffic_shap_remove(){
    $unix=new unix();
    $pattern=$_GET["traffic-shap-remove"];
    $ruleid=$_GET["ruleid"];
    $echo=$unix->find_program("echo");
    $cmd="$echo \"-{$pattern}\" > /proc/net/ipt_ratelimit/rule{$ruleid}";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);

}
function traffic_shap_add(){
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $pattern=$_GET["traffic-shap-add"];
    $ruleid=$_GET["ruleid"];
    $value=$_GET["value"];

    $cmd="$echo \"@+{$pattern} $value\" > /proc/net/ipt_ratelimit/rule{$ruleid}";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function firehol_version(){
	$unix=new unix();
	$firehol=$unix->find_program("firehol");
	exec("$firehol 2>&1",$f);
	foreach ($f as $num=>$filename){
		if(preg_match("#FireHOL\s+([0-9\.]+)#", $filename,$re)){
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
			return;
		}
	}
}



function install_link_balancer(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/LinkBalancer.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/LinkBalancer.progress.log";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --install-lb >{$GLOBALS["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function transparent_status(){
    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    exec("$iptables_save 2>&1",$results);

    foreach ($results as $line){

        if(preg_match("#\s+(ArticaSquidTransparent|ArticaWCCP3)#",$line)){
            @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ArticaSquidTransparent",1);
            return;
        }

        if(preg_match("#PREROUTING.*?-p tcp -j REDIRECT --to-ports#",$line)){
            @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ArticaSquidTransparent",1);
            return;
        }

        writelogs_framework("No Match $line",__FUNCTION__,__FILE__,__LINE__);
    }
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ArticaSquidTransparent",0);


}
function uninstall_link_balancer(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/LinkBalancer.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/LinkBalancer.progress.log";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --uninstall-lb >{$GLOBALS["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function configure_link_balancer(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/LinkBalancer.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/LinkBalancer.progress.log";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --configure-lb >{$GLOBALS["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}








function rules_status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.firehol.php --status >/dev/null 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/firehol.log.tmp";
	$query2=null;
	$sourceLog="/var/log/syslog";
	$rp=intval($_GET["rp"]);
	writelogs_framework("access_real -> $rp search {$_GET["query"]}" ,__FUNCTION__,__FILE__,__LINE__);

	$query=$_GET["query"];
	$grep=$unix->find_program("grep");


	$cmd="$grep --binary-files=text -Ei \"FIREHOL:.*?IN=\" $sourceLog|$tail -n $rp >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){
		$cmd="$grep --binary-files=text -Ei \"FIREHOL:.*?IN=.*?$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
	}



	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}


function is_installed(){
	$unix=new unix();
	$firehol=$unix->find_program("firehol");
	if(is_file($firehol)){
		
		echo "<articadatascgi>1</articadatascgi>";
	}else{
		
		echo "<articadatascgi>0</articadatascgi>";
	}
}

function ipset_itself(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
    $GLOBALS["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --dump-itself >{$GLOBALS["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function reconfigure_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$comand="--reconfigure-progress";
	
	if($_GET["comand"]<>null){
		if($_GET["comand"]=="stop"){
			$comand="--stop";
		}
		if($_GET["comand"]=="start"){
			$comand="--start";
		}		
	}

    $cmd=trim("$nohup /usr/sbin/artica-phpfpm-service -reconfigure-syslog 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php {$comand} >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function articapcap_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $target=PROGRESS_DIR."/articapcap.search";
    $src="/var/log/articapsniffer-service.log";
    $MAIN=unserialize(base64_decode($_GET["articapcap-events"]));


    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }
    $TERM=null;
    $max=intval($MAIN["MAX"]);
    if($max>1500){$max=1500;}
     if(isset($MAIN["TERM"])) {
        if ($MAIN["TERM"] <> null) {
            $TERM = ".*?{$MAIN["TERM"]}";
        }
    }

    if($TERM<>null) {
        $search = "$TERM";
        $search = str_replace(".*?.*?", ".*?", $search);
        $cmd = "$grep --binary-files=text -i -E '$search' $src |$tail -n $max >$target 2>&1";
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return true;
    }
    $cmd = "$tail $src -n $max >$target 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;

}
function searchInSyslog(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]));
	$PROTO_P=null;
	
	foreach ($MAIN as $val=>$key){
		$MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
		$MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
		
	}
    $SRC=null;
    $DST=null;$SRCPORT=null;$DSTPORT=null;$IN=null;$OUT=null;$MAC=null;$PROTO=null;$TERM=null;
	$max=intval($MAIN["MAX"]);
    if($max>1500){$max=1500;}
    if($GLOBALS["VERBOSE"]) {
        print_r($MAIN);
    }
	$date=$MAIN["DATE"];
    if(isset($MAIN["PROTO"])) {
        $PROTO = $MAIN["PROTO"];
    }

    if(isset($MAIN["SRC"])) {
        $SRC = $MAIN["SRC"];
    }
    if(isset($MAIN["DST"])) {
        $DST = $MAIN["DST"];
    }

    if(isset($MAIN["SRCPORT"])) {
        $SRCPORT = $MAIN["SRCPORT"];
    }
    if(isset($MAIN["DSTPORT"])) {
        $DSTPORT = $MAIN["DSTPORT"];
    }
    if(isset($MAIN["IN"])) {
        $IN = $MAIN["IN"];
    }
    if(isset($MAIN["OUT"])) {
        $OUT = $MAIN["OUT"];
    }

    if(isset($MAIN["MAC"])) {
        $MAC = $MAIN["MAC"];
    }
    if(isset($MAIN["TERM"])) {
        if ($MAIN["TERM"] <> null) {
            $TERM = ".*?{$MAIN["TERM"]}";
        }
    }
	//FIREHOL:RULE-2:IN=eth0 OUT= MAC=00:0c:29:26:46:3d:00:26:b9:78:8f:0a:08:00 SRC=192.168.1.173 DST=192.168.1.180 LEN=91 TOS=0x00 PREC=0x00 TTL=128 ID=23066 DF PROTO=TCP SPT=445 DPT=29089 WINDOW=510 RES=0x00 ACK PSH URGP=0

    if($PROTO=="ALL"){$PROTO=null;}
    if($PROTO=="all"){$PROTO=null;}
    if($PROTO=="*"){$PROTO=null;}
    $DST_P=null;
    $DSTPORT_P=null;$IN_P=null;$OUT_P=null;$MAC_P=null;$SRC_P=null;$DST_P=null;$PROTO_P=null;$SRCPORT_P=null;
    $TERM_P=null;
	if($IN<>null){$IN_P=".*?IN=$IN.*?";}
	if($OUT<>null){$OUT_P=".*?OUT=$OUT.*?";}
	if($MAC<>null){$MAC_P=".*?MAC=.*?$MAC.*?";}
	if($SRC<>null){$SRC_P=".*?SRC=$SRC.*?";}
	if($DST<>null){$DST_P=".*?DST=$DST.*?";}
	if($SRCPORT<>null){$SRCPORT_P=".*?SPT=$SRCPORT.*?";}
	if($DSTPORT<>null){$DSTPORT_P=".*?DPT=$DSTPORT.*?";}
	if($PROTO<>null){$PROTO_P=".*?PROTO=$PROTO.*?";}

	
	
	$mainline="$TERM_P$IN_P$OUT_P$MAC_P$SRC_P$DST_P$PROTO_P$SRCPORT_P$DSTPORT_P";

    if($TERM<>null) {
        if(preg_match("#protocol.+#",$TERM)){
            $TERM=null;
        }

    }
	if($TERM<>null){
        if($MAIN["C"]==0){$TERM_P=$TERM;}
        $mainline="$TERM_P$IN_P$OUT_P$MAC_P$SRC_P$DST_P$PROTO_P$SRCPORT_P$DSTPORT_P";
		if($MAIN["C"]>0){
			$mainline="($mainline|$TERM)";
		}
	}
	
	
	
	$search="$date.*?$mainline";
	$search=str_replace(".*?.*?",".*?",$search);
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/firewall.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/firehol.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function iptables_modules_infos(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.iptables.modules.info.php";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function reconfigure_qos_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/fireqos.reconfigure.progress.txt";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);

	$comand="--reconfigure-progress";

	if($_GET["comand"]<>null){
		if($_GET["comand"]=="stop"){
			$comand="--stop";
		}
		if($_GET["comand"]=="start"){
			$comand="--start";
		}
	}

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.fireqos.php {$comand} >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function qos_install(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/fireqos.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.fireqos.php --install-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function qos_remove(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/fireqos.reconfigure.progress.txt";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.fireqos.php --uninstall >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function save_client_config(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --comps >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function save_client_server(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --backup-server >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

