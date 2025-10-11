<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["syslog"])){build_syslog();exit;}
if(isset($_GET["fw-requests"])){firewall_events();exit;}
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
if(isset($_GET["events-query"])){events_query();exit;}
if(isset($_GET["activate-ufdb"])){activate_ufdb();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["poweradmin-install"])){poweradmin_install();exit;}
if(isset($_GET["poweradmin-disable"])){poweradmin_disable();exit;}
if(isset($_GET["poweradmin-enable"])){poweradmin_enable();exit;}
if(isset($_GET["reload-poweradmin"])){poweradmin_reload();exit;}
if(isset($_GET["activate-dsc"])){dsc_install();exit;}
if(isset($_GET["disable-dsc"])){dsc_uninstall();exit;}
if(isset($_GET["blacklists-enable"])){blacklists_enable();exit;}
if(isset($_GET["blacklists-disable"])){blacklists_disable();exit;}
if(isset($_GET["compile-categories"])){unbound_compile_categories();exit;}
if(isset($_GET["install-dns"])){install_unbound_dns();exit;}
if(isset($_GET["uninstall-dns"])){uninstall_unbound_dns();exit;}
if(isset($_GET["cache-clear"])){cache_clear();exit;}
if(isset($_GET["requests"])){requests();exit;}
if(isset($_GET["install-redis"])){install_redis();exit;}
if(isset($_GET["uninstall-redis"])){uninstall_redis();exit;}
if(isset($_GET["local-data"])){local_data();exit;}
if(isset($_GET["local-data-remove"])){local_data_remove();exit;}
if(isset($_GET["restart-recusor"])){recursor_restart();exit;}
if(isset($_GET["dnsfw-checker"])){firewall_dns_checker();exit;}

$tt=array();
foreach ($_GET as $key=>$val){
    $tt[]="$key=>$val";
}
writelogs_framework("Unable to understand the query ".@implode(" ",$tt),__FUNCTION__,__FILE__,__LINE__);


function local_data_remove(){
    $unix=new unix();
    $fqdn_name=$_GET["local-data-remove"];

    $unbound_control=$unix->find_program("unbound-control");
    $final="$unbound_control local_data_remove $fqdn_name 2>&1";
    writelogs_framework("$final",__FUNCTION__,__FILE__,__LINE__);
    exec($final,$results);
    foreach ($results as $line){
        if(preg_match("#ok#",$line)){
            echo "<articadatascgi>1</articadatascgi>";
            return true;
        }
    }
    echo "<articadatascgi>".@implode("<br>",$results)."</articadatascgi>";
    return false;
}


function build_syslog(){
    $unix=new unix();
    $unix->framework_execute("exec.unbound.php --syslog","dns.syslog.progress","dns.syslog.log");
}

function local_data(){
    $MAIN=unserialize(base64_decode($_GET["local-data"]));
    $unix=new unix();
    $fqdn_name=trim(strtolower($MAIN["name"]));
    $type=$MAIN["type"];
    $content=$MAIN["content"];
    $ttl=$MAIN["ttl"];
    $prio=intval($MAIN["prio"]);

    if($fqdn_name==null){
        writelogs_framework("name not found",__FUNCTION__,__FILE__,__LINE__);
        echo "<articadatascgi>Name not found</articadatascgi>";
        return;
    }

    $cmd[]=$fqdn_name;
    $cmd[]=intval($ttl);
    $cmd[]="IN";
    $cmd[]=$type;
    $cmd[]=$content;

    $unbound_control=$unix->find_program("unbound-control");
    $final="$unbound_control local_data ".@implode(" ",$cmd)." 2>&1";
    writelogs_framework("$final",__FUNCTION__,__FILE__,__LINE__);
    exec($final,$results);
    foreach ($results as $line){
        if(preg_match("#ok#",$line)){
            echo "<articadatascgi>1</articadatascgi>";

        }
    }
    echo "<articadatascgi>".@implode("<br>",$results)."</articadatascgi>";
    @file_put_contents(PROGRESS_DIR."/unbound_local_data",@implode("\n",$results));
    @chmod(PROGRESS_DIR."/unbound_local_data",0755);

}

function service_cmds(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("$php /usr/share/artica-postfix/exec.pdns_server.php --$cmds  2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function rebuild_database(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --rebuild-database 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unbound.status";
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --unbound >{$GLOBALS["LOGSFILES"]} 2>&1";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unbound.php --reload >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}





function blacklists_enable(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unbound.php --blacklists-enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function blacklists_disable(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unbound.php --blacklists-disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
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
function install_redis(){
    $unix=new unix();
    $unix->framework_execute("exec.unbound.php --install-redis","unbound.install.php","unbound.install.php.log");

}



function uninstall_redis(){
    $unix=new unix();
    $unix->framework_execute("exec.unbound.php --uninstall-redis","unbound.install.php","unbound.install.php.log");
}

function install_unbound_dns(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unbound.php --install-dns >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function uninstall_unbound_dns(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unbound.install.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unbound.php --uninstall-dns >{$GLOBALS["LOGSFILES"]} 2>&1 &";
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
	if(!is_file($digg)){
		echo "<articadatascgi>".base64_encode(serialize(array("dig, nos such binary")))."</articadatascgi>";
		return;
	}
	
	$hostname=$_GET["hostname"];
	$interface=$_GET["interface"];
	if($interface==null){$interface="127.0.0.1";}
	if($hostname==null){$hostname="www.google.com";}
	$cmd="$digg @$interface $hostname 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}

function unbound_compile_categories(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.unbound.php --reload 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function cache_clear(){
	$host=$_GET["cache-clear"];
	$unix=new unix();
	$unbound_control=$unix->find_program("unbound-control");
	$cmd="$unbound_control flush_zone $host";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function firewall_dns_checker(){
    $unix=new unix();
    $python=$unix->find_program("python");
    $binary="/usr/share/artica-postfix/bin/dnsfw-debug.py";
    $ARRAY=unserialize(base64_decode($_GET["dnsfw-checker"]));
    if(!isset($ARRAY["RULE_CHECKER_DOMAIN"])){$ARRAY["RULE_CHECKER_DOMAIN"]="www.ibm.com";}
    if(!isset($ARRAY["RULE_CHECKER_SRC"])){$ARRAY["RULE_CHECKER_SRC"]="192.168.1.100";}
    if(!isset($ARRAY["RULE_CHECKER_QTYPE"])){$ARRAY["RULE_CHECKER_QTYPE"]="A";}
    $t=$ARRAY["t"];
    $tfile=PROGRESS_DIR."/$t.html";
    $SourceIPAddr=$ARRAY["RULE_CHECKER_SRC"];
    $domainname=$ARRAY["RULE_CHECKER_DOMAIN"];
    $QueryType=$ARRAY["RULE_CHECKER_QTYPE"];
    $cmd="$python $binary \"$SourceIPAddr\" \"$domainname\" \"$QueryType\" >$tfile 2>&1";
    writelogs_framework("\"$cmd\"" ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function firewall_events(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/unbound.log.tmp";
    $sourceLog="/var/log/dns-firewall.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];

    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/unbound.log.cmd", $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);
}

function requests(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/unbound.log.tmp";
    $sourceLog="/var/log/unbound.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];

    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/unbound.log.cmd", $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);
}
