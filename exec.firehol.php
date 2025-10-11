#!/usr/bin/php
<?php
$GLOBALS["SERVICE_NAME"]="Local firewall";
$GLOBALS["PERIOD"]=null;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["NOPROGRESS"]=false;
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--period=([0-9]+)([a-z])#", implode(" ",$argv),$re)){$GLOBALS["PERIOD"]=$re[1].$re[2];}
if(preg_match("#--noprogress#", implode(" ",$argv),$re)){$GLOBALS["NOPROGRESS"]=true;}

include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
include_once(dirname(__FILE__) . '/ressources/class.firehol.inc');
include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');


if($argv[1]=="--configure-lb"){$GLOBALS["PROGRESS"]=true;reconfigure_link_balancer();exit;}

if($argv[1]=="--link-balancer-failed"){link_balancer_test_failed($argv[2],$argv[3]);}
if($argv[1]=="--updated-routes"){link_balancer_updated_routes($argv[2],$argv[3]);}
if($argv[1]=="--updated-rules"){link_balancer_updated_rules();}
if($argv[1]=="--uninstall-lb"){$GLOBALS["PROGRESS"]=true;uninstall_link_balancer();exit;}
if($argv[1]=="--install-lb"){$GLOBALS["PROGRESS"]=true;install_link_balancer();exit;}
if($argv[1]=="--reconfigure-progress"){$GLOBALS["PROGRESS"]=true;reconfigure_progress();exit;}
if($argv[1]=="--disable-progress"){$GLOBALS["PROGRESS"]=true;disable_progress();exit;}
if($argv[1]=="--check-ndpi"){$GLOBALS["PROGRESS"]=true;ndpi_check();exit;}
if($argv[1]=="--groups"){$GLOBALS["PROGRESS"]=true;$fire=new firehol();$fire->GROUPS_LISTS();exit;}
if($argv[1]=="--reconfigure"){$GLOBALS["PROGRESS"]=true;reconfigure();exit;}
if($argv[1]=="--build"){$GLOBALS["PROGRESS"]=true;reconfigure();exit;}
if($argv[1]=="--scan"){$GLOBALS["PROGRESS"]=true;scanservices();exit;}
if($argv[1]=="--stop"){$GLOBALS["PROGRESS"]=true;stop_service();exit;}
if($argv[1]  =="stop"){$GLOBALS["PROGRESS"]=true;stop_service();exit;}
if($argv[1]=="--start"){$GLOBALS["PROGRESS"]=true;start_service();exit;}
if($argv[1]  =="start"){$GLOBALS["PROGRESS"]=true;start_service();exit;}
if($argv[1]=="--status"){$GLOBALS["PROGRESS"]=true;build_status();exit;}
if($argv[1]=="--mark"){$GLOBALS["PROGRESS"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);BUILD_MARK();exit;}

if($argv[1]=="--ndpi-export"){ndpi_export();exit;}
if($argv[1]=="--ndpi-start"){ndpi_start();exit;}
if($argv[1]=="--ndpi-stop"){ndpi_stop();exit;}
if($argv[1]=="--ndpi-restart"){ndpi_restart();exit;}

if($argv[1]=="--dump-service"){dump_service($argv[2]);exit;}
if($argv[1]=="--dump-rule"){dump_rule($argv[2]);exit;}

if($argv[1]=="--dump-itself"){$GLOBALS["PROGRESS"]=true;dump_itself();exit;}
if($argv[1]=="--cybercrime-install"){$GLOBALS["PROGRESS"]=true;cybercrime_install();exit;}
if($argv[1]=="--cybercrime-uninstall"){$GLOBALS["PROGRESS"]=true;cybercrime_uninstall();exit;}

function BUILD_MARK(){
	$ff=new firehol();
	echo $ff->BUILD_MARK();
}


function build_status():bool{
    $unix       = new unix();
    $iptables   = $unix->find_program("iptables");
    exec("$iptables -nvL -x 2>&1",$results);
    exec("$iptables -t nat -nvL -x 2>&1",$results);
    exec("$iptables -t mangle -nvL -x 2>&1",$results);
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1 AND NoFirewall=0";
    $results2 = $q->QUERY_SQL($sql);

    foreach ($results2 as $index=>$ligne2) {
        $ID=$ligne2["ID"];
        $nic_from = $ligne2["nic_from"];
        $nic_to = $ligne2["nic_to"];
        $RouterName = "{$nic_from}2{$nic_to}";
        $xRouter[$ID]=$RouterName;
    }


    $MATCH=array();
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if($GLOBALS["VERBOSE"]){echo "<SCAN>$line</SCAN>\n";}


        if(preg_match("#LOG.*?\/.*?LOG\.([0-9]+)#",$line,$re)){
            if(isset($MATCH["LOG"][$re[1]])){continue;}
            $MATCH["LOG"][$re[1]]=true;
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+).*?\/.*?ROUTER\.([0-9]+)\s+#",$line,$re)){
            if($GLOBALS["VERBOSE"]){
                echo "* * MATCHES: ROUTER $line\n";
            }
            $ROUTER_ID=$re[3];

            if(!isset($xRouter[$ROUTER_ID])){$xRouter[$ROUTER_ID]="Unknown{$ROUTER_ID}";}

            $ethName=$xRouter[$ROUTER_ID];
            if(!isset($MATCH["ROUTER"][$ROUTER_ID]["pkts"])){
                $MATCH["ROUTER"][$ROUTER_ID]["pkts"]=intval($re[1]);
            }else{
                $MATCH["ROUTER"][$ROUTER_ID]["pkts"]=$MATCH["ROUTER"][$ROUTER_ID]["pkts"]+intval($re[1]);
            }
            $MATCH[$ethName]["pkts"]=$MATCH["ROUTER"][$ROUTER_ID]["pkts"];

            if(!isset($MATCH["ROUTER"][$ROUTER_ID]["bytes"])){
                $MATCH["ROUTER"][$ROUTER_ID]["bytes"]=intval($re[2]);
            }else{
                $MATCH["ROUTER"][$ROUTER_ID]["bytes"]=$MATCH["ROUTER"][$ROUTER_ID]["pkts"]+intval($re[2]);
            }
            $MATCH[$ethName]["bytes"]=$MATCH["ROUTER"][$ROUTER_ID]["bytes"];
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+).*?\/.*?RULE\.CROWDSEC\s+#",$line,$re)){
            if($GLOBALS["VERBOSE"]){
                echo "* * MATCHES: CROWDSEC $line\n";
            }
            $MATCH["CROWDSEC"]=array("pkts"=>$re[1],"bytes"=>$re[2]);
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+).*?\/.*?RULE\.REVERSEPROXY\s+#",$line,$re)){
            if($GLOBALS["VERBOSE"]){
                echo "* * MATCHES: REVERSEPROXY $line\n";
            }
            $MATCH["REVERSEPROXY"]=array("pkts"=>$re[1],"bytes"=>$re[2]);
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+).*?\/.*?RULE\.([0-9]+)\s+#",$line,$re)){
            if(isset($MATCH[$re[3]])){continue;}
            if($GLOBALS["VERBOSE"]){
                echo "* * MATCHES: RULE Number {$re[3]} ($line)\n";
            }
            if(isset($MATCH[$re[3]])){
                $pkts=$MATCH[$re[3]]["pkts"];
                $bytes=$MATCH[$re[3]]["bytes"];
                $pkts=$pkts+intval($re[1]);
                $bytes=$bytes+intval($re[2]);
                $MATCH[$re[3]]=array("pkts"=>$pkts,"bytes"=>$bytes);
                continue;
            }

            $MATCH[$re[3]]=array("pkts"=>$re[1],"bytes"=>$re[2]);
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+).*?\/.*?FINAL\.(.+?)\s+#",$line,$re)){
            if(isset($MATCH[$re[3]])){
                $pkts=$MATCH[$re[3]]["pkts"];
                $bytes=$MATCH[$re[3]]["bytes"];
                $pkts=$pkts+intval($re[1]);
                $bytes=$bytes+intval($re[2]);
                $MATCH[$re[3]]=array("pkts"=>$pkts,"bytes"=>$bytes);
                continue;
            }

            $MATCH[$re[3]]=array("pkts"=>$re[1],"bytes"=>$re[2]);
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+).*?\/.*?NAT\.(.+?)\s+#",$line,$re)){
            if(isset($MATCH["NAT"][$re[3]])){continue;}
            if(isset($MATCH["NAT"][$re[3]])){
                $pkts=$MATCH["NAT"][$re[3]]["pkts"];
                $bytes=$MATCH["NAT"][$re[3]]["bytes"];
                $pkts=$pkts+intval($re[1]);
                $bytes=$bytes+intval($re[2]);
                $MATCH["NAT"][$re[3]]=array("pkts"=>$pkts,"bytes"=>$bytes);
                continue;
            }
            $MATCH["NAT"][$re[3]]=array("pkts"=>$re[1],"bytes"=>$re[2]);
            continue;
        }

    }
    if($GLOBALS["VERBOSE"]){
        print_r($MATCH);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPTABLES_RRULES_STATUS",serialize($MATCH));
    return true;

}



function ndpi_restart(){
    system("/usr/sbin/artica-phpfpm-service -stop-ndpi");
    system("/usr/sbin/artica-phpfpm-service -start-ndpi");
}
function ndpi_stop(){

    system("/usr/sbin/artica-phpfpm-service -stop-ndpi");
}

function ndpi_start(){
    system("/usr/sbin/artica-phpfpm-service -start-ndpi");
}

function ndpi_check(){
    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    $ulimit=$unix->find_program("ulimit");
    system("$ulimit -HSd unlimited");
    system("$ulimit -u unlimited");
    exec("$iptables_save 2>&1",$results);
    $zError=error_get_last();
    if(isset($zError["message"])) {
        if (preg_match("#Unable to fork.*?iptables-save#i", $zError["message"])) {
            echo "[FireHOL]: Execution failed\n";
            foreach ($zError as $key => $val) {
                $messages[] = "$key: $val";
            }
            squid_admin_mysql(0, "Unable to Fork iptables !", @implode("\n", $messages), __FILE__, __LINE__);
            die();
        }
    }



    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#PREROUTING.*?-m\s+ndpi.*?--flow-info#",$line)){
            if($GLOBALS["VERBOSE"]){echo "[FireHOL]: Success $line\n";}
            return true;
        }
    }

    echo "[FireHOL]: nDPI is not added in iptables -> start service\n";
    squid_admin_mysql(0,"Starting service nDPI (not added in firewall)",null,__FILE__,__LINE__);
    system("/etc/init.d/nDPI start");

}

function ndpi_export(){
    $GLOBALS["YESCGROUP"]=true;
    xcgroups();
    ndpi_check();
    $unix=new unix();
    if(!is_dir("/home/artica/ndpi-temp")){
        @mkdir("/home/artica/ndpi-temp",0755,true);
    }
    $cat=$unix->find_program("cat");
    $time=time();
    $nice=$unix->EXEC_NICE();
    system("{$nice}$cat /proc/net/xt_ndpi/flows >/home/artica/ndpi-temp/$time.ndpi");

}




function build_progress($text,$pourc):bool{
    if($GLOBALS["NOPROGRESS"]){
        echo "[FireHOL]: {$pourc}%: $text\n";
        return false;
    }
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[FireHOL]: {$pourc}%: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
    return true;
}
function lb_progress($text,$pourc){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/LinkBalancer.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[FireHOL]: {$pourc}%: $text\n";
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["PROGRESS"]){sleep(1);}

}



function reconfigure(){
	$unix=new unix();
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==0){return;}
	$ServerRunSince=$unix->ServerRunSince();
	if($ServerRunSince>3){
		squid_admin_mysql(1, "Reconfigure Firewall service (server {running} {since} {$ServerRunSince}mn)", null,__FILE__,__LINE__);
		$rm=$unix->find_program("rm");
		$dirs=$unix->dirdir("/var/run/firehol");
        foreach ($dirs as $num=>$directory){
            shell_exec("$rm -rf $directory");
        }



		$fire=new firehol();
		$fire->checkTables();
		$fire->build();
        reconfigure_link_balancer();
        build_status();
	}
}

function install_link_balancer(){
    $unix=new unix();
    lb_progress("{install} {APP_LINK_BALANCER}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableLinkBalancer",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetworkAdvancedRouting",0);
    $linkbalancerbin=$unix->find_program("link-balancer");
    $LinkBalancerSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinkBalancerSchedule"));
    if($LinkBalancerSchedule<1){$LinkBalancerSchedule=10;}
    $unix->Popuplate_cron_make("link-balancer","*/{$LinkBalancerSchedule} * * * *",$linkbalancerbin);
    system("/etc/init.d/cron reload");
    lb_progress("{install} {APP_LINK_BALANCER}",50);
    reconfigure_link_balancer();
    lb_progress("{install} {APP_LINK_BALANCER} {success}",100);

}
function uninstall_link_balancer(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    lb_progress("{uninstall} {APP_LINK_BALANCER}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableLinkBalancer",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetworkAdvancedRouting",0);
    $linkbalancerbin=$unix->find_program("link-balancer");
    $LinkBalancerSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinkBalancerSchedule"));
    if($LinkBalancerSchedule<1){$LinkBalancerSchedule=10;}
    if(is_file("/etc/cron.d/link-balancer")){@unlink("/etc/cron.d/link-balancer");system("/etc/init.d/cron reload");}
    $unix->Popuplate_cron_make("link-balancer","*/{$LinkBalancerSchedule} * * * *",$linkbalancerbin);
    lb_progress("{uninstall} {APP_LINK_BALANCER}",50);
    @unlink("/etc/firehol/link-balancer.conf");
    system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
    lb_progress("{uninstall} {APP_LINK_BALANCER} {success}",100);


}

function reconfigure_link_balancer(){

    $EnableLinkBalancer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
    if ($EnableLinkBalancer == 0) {
        return false;
    }
    $unix = new unix();
    $q = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results = $q->QUERY_SQL("SELECT * FROM link_balance WHERE enabled=1 ORDER BY weight");
    $WAN_PROVIDERS=array();
    $MAIN_TABLES=array();
    $MAIN_POLICY=array();
    $RULES_SRC_IPS=array();
    $table_id=180;


    system("/etc/init.d/cron reload");
    lb_progress("{reconfigure} {APP_LINK_BALANCER}",55);
    if(count($results)==0){
        if(is_file("/etc/firehol/link-balancer.conf")){@unlink("/etc/firehol/link-balancer.conf");}
        if(is_file("/etc/cron.d/link-balancer")){
            @unlink("/etc/cron.d/link-balancer");
            system("/etc/init.d/cron reload");
        }
        echo "[FireHOL]: Need at least 2 valid interfaces\n";
        lb_progress("{reconfigure} {APP_LINK_BALANCER}",100);
        return false;
    }

    $linkbalancerbin=$unix->find_program("link-balancer");
    $LinkBalancerSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinkBalancerSchedule"));
    if($LinkBalancerSchedule<1){$LinkBalancerSchedule=10;}
    $unix->Popuplate_cron_make("link-balancer","*/{$LinkBalancerSchedule} * * * *",$linkbalancerbin);
    system("/etc/init.d/cron reload");

    $CountOfInterfaces=0;
    lb_progress("{reconfigure} {APP_LINK_BALANCER}",60);
    foreach ($results as $index => $ligne) {
        $check=null;
        $Interface = $ligne["Interface"];
        if (!$unix->is_interface_available($Interface)) {
            $CONF[] = "# $Interface is not available";
            continue;
        }

        $nic = new system_nic($Interface);
        if ($nic->enabled == 0) {
            $CONF[] = "# $Interface is disabled";
            continue;
        }
        $CountOfInterfaces++;
        $table_id++;
        $WANNAME="Provider{$ligne["ID"]}";
        $WANTABLE=$table_id;
        $checkytype=$ligne["checkytype"];
        $checkaddr=$ligne["checkaddr"];
        if($checkaddr=="0.0.0.0"){$checkytype="alwayson";}
        $checkytype_text=null;
        if($checkytype=="alwayson"){
            $checkytype_text=" check alwayson";
        }else{
            $checkytype_text=" check $checkytype $checkaddr";
        }






        $WAN_PROVIDERS[]="gateway $WANNAME dev $Interface gw $nic->GATEWAY{$checkytype_text}";
        $WAN_TABLES[]="table $WANTABLE\n\tdefault via $WANNAME";
        $MAIN_TABLES[]="\tdefault via $WANNAME weight {$ligne["weight"]}";
        $MAIN_POLICY[]="\tconnmark 0x{$ligne["mark"]} table $WANTABLE";
        $RULES_SRC_IPS[]="\trules src gw-src-ips $WANNAME table $WANTABLE";


    }

    $CONF[]="# -----------------------------------------\n\n";
    $CONF[]="# wan providers";
    $CONF[]=@implode("\n",$WAN_PROVIDERS);
    $CONF[]="\n\n# -----------------------------------------\n\n";
    $CONF[]="# one table per WAN gateway";
    $CONF[]=@implode("\n",$WAN_TABLES);
    $CONF[]="\n\n";
    $CONF[]="# this is the main system routing table";
    $CONF[]="table main";
    $CONF[]=@implode("\n",$MAIN_TABLES);
    $CONF[]="\n\n# -----------------------------------------\n\n";
    $CONF[]="policy";
    $CONF[]=@implode("\n",$MAIN_POLICY);
    $CONF[]="";
    $CONF[]="# handle local daemons";
    $CONF[]=@implode("\n",$RULES_SRC_IPS);
    echo "[FireHOL]: /etc/firehol/link-balancer.conf done.\n";

    $php=$unix->LOCATE_PHP5_BIN();
    $curl=$unix->find_program("curl");
    $echo=$unix->find_program("echo");
    $CONF[]="updated_rules() {";
    $CONF[]="\t$php ".__FILE__." --updated-rules || true";
    $CONF[]="\treturn 0";
    $CONF[]="}";
    $CONF[]="";
    $CONF[]="updated_routes() {";
    $CONF[]="\tlocal table=\"\${1}\"";
    $CONF[]="\tlocal def=\"\${2}\" # if this is 1, the default route has been updated";
    $CONF[]="\t$php ".__FILE__." --updated-routes \$table \$def || true";
	$CONF[]="\treturn 0";
	$CONF[]="}";
    $CONF[]="";
    $CONF[]="";
    $CONF[]="";
		# do your IPv4 check here

    if($CountOfInterfaces<2){
        if(is_file("/etc/firehol/link-balancer.conf")){@unlink("/etc/firehol/link-balancer.conf");}
        if(is_file("/etc/cron.d/link-balancer")){
            @unlink("/etc/cron.d/link-balancer");
            system("/etc/init.d/cron reload");
        }
        echo "[FireHOL]: Need at least 2 valid interfaces\n";
        lb_progress("{reconfigure} {APP_LINK_BALANCER}",110);
        return false;

    }

    @file_put_contents("/etc/firehol/link-balancer.conf",@implode("\n",$CONF)."\n");

    $CONF=array();
    $CONF[]="#!/bin/sh";

    $CONF[]="dev=\"$1\"";
    $CONF[]="src=\"$2\"";
    $CONF[]="dst=\"$3\"";
    $CONF[]="echo \"Checking IPv4 (\$dev/\$src/\$dst)...\"";
    $CONF[]="$curl --connect-timeout 3 --interface \$dev --silent --output /dev/null --head --write-out \"%{http_code}\" http://www.msftncsi.com/ncsi.txt >/tmp/link_balancer_\$dev 2>&1 || true";

    $CONF[]="RESULT=`cat /tmp/link_balancer_\$dev`";
    $CONF[]="echo \"URL: \$RESULT\"";

    $CONF[]="if test \$RESULT -eq 200";
    $CONF[]="\tthen";
    $CONF[]="\t\techo \"\$RESULT Success 200\"";
    $CONF[]="\t\texit 0";
    $CONF[]="fi";
    $CONF[]="# return 0 for success, or 1 for failure";
    $CONF[]="$php ".__FILE__." --link-balancer-failed \$RESULT \$dev|| true";
    $CONF[]="exit 1";
    $CONF[]="";
    @file_put_contents("/usr/sbin/check_mycheck",@implode("\n",$CONF)."\n");
    lb_progress("{reconfigure} {APP_LINK_BALANCER}",100);
    @chmod("/usr/sbin/check_mycheck",0755);
    return true;

}

function link_balancer_updated_rules(){
    link_balancer_events(2,"Rules are updated",null,__LINE__);
    squid_admin_mysql(1,"[NET]: Routing rules are updated",null,__FILE__,__LINE__);
}
function link_balancer_updated_routes($table,$def){
    link_balancer_events(1,"Routes are updated on $table=$def",null,__LINE__);
    squid_admin_mysql(1,"[NET]: Routing rules are updated",null,__FILE__,__LINE__);
}
function link_balancer_test_failed($result,$dev){
    link_balancer_events(1,"Internet failed on $dev error:$result",null,__LINE__);
    squid_admin_mysql(0,"[NET]: Internet failed on $dev error:$result",null,__FILE__,__LINE__);
}

function link_balancer_events($prio,$subject,$content,$line){

    $file="link-balancer";
    if(function_exists('syslog')){
        openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);
        syslog(LOG_INFO, "$subject $content");
        closelog();
    }


    $q=new lib_sqlite("/home/artica/SQLITE/link_balancer.db");

    $time=time();
    $info="Line ".$line ." file:".basename(__FILE__);

    $sql="INSERT INTO events (zdate,prio,sent,subject,content,info) VALUES('$time',$prio,'$subject','$content','$info');";
    $q->QUERY_SQL($sql);

}


function install(){}

function disable_progress():bool{
	$unix           = new unix();
	$php            = $unix->LOCATE_PHP5_BIN();
	$sock           = new sockets();
	$sock->SET_INFO("FireHolEnable", 0);

    build_progress("{stopping_firewall}",30);
	shell_exec("$php ".__FILE__." stop");
	build_progress("{building_init_script}",30);
	build_progress("{reconfiguring}",50);
	remove_service("/etc/init.d/firehol");
	build_progress("{reconfiguring}",90);
    iptables_flush();
    @unlink("/usr/sbin/firewall-builder.sh");
	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){
        build_progress("{reconfiguring} {APP_SQUID}",91);
	    system("$php /usr/share/artica-postfix/exec.squid.global.access.php --chk-port --no-firehol");
        build_progress("{reconfiguring} {APP_SQUID}",92);
        system("/usr/sbin/artica-phpfpm-service -iptables-routers");

	}
	
	return build_progress("{done}",100);
}
function iptables_flush() {

    $unix               = new unix();
    $iptables           = $unix->find_program("iptables");
    $iptables_restore   = $unix->find_program("iptables-restore");
    $ipset              = $unix->find_program("ipset");

    shell_exec("$iptables -F INPUT");
    shell_exec("$iptables -P INPUT ACCEPT");
    shell_exec("$iptables -F OUTPUT");
    shell_exec("$iptables -P OUTPUT ACCEPT");
    shell_exec("$iptables -F FORWARD");
    shell_exec("$iptables -P FORWARD ACCEPT");
    shell_exec("$iptables -t nat -F PREROUTING");
    shell_exec("$iptables -t nat -F");
    shell_exec("$iptables -t mangle -F");
    shell_exec("$iptables -F");
    shell_exec("$iptables -X");

    $f[]="# Empty the entire filter table";
    $f[]="*nat";
    $f[]=":PREROUTING ACCEPT [0:0]";
    $f[]=":INPUT ACCEPT [0:0]";
    $f[]=":POSTROUTING ACCEPT [0:0]";
    $f[]=":OUTPUT ACCEPT [0:0]";
    $f[]="COMMIT";
    $f[]="*filter";
    $f[]=":INPUT ACCEPT [0:0]";
    $f[]=":FORWARD ACCEPT [0:0]";
    $f[]=":OUTPUT ACCEPT [0:0]";
    $f[]="COMMIT";
    $f[]="";
    $TMPFILE=$unix->FILE_TEMP();
    @file_put_contents( $TMPFILE,@implode("\n",$f));
    system("$iptables_restore < $TMPFILE");
    @unlink($TMPFILE);
    exec("$ipset list -n 2>&1",$results);

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        echo "[FireHOL]: Removing group $line\n";
        shell_exec("$ipset destroy $line");
    }

}
function enable_progress(){
	$unix       = new unix();
    $squid      = $unix->LOCATE_SQUID_BIN();
	$php        = $unix->LOCATE_PHP5_BIN();
	build_progress("{enable_firewall}",20);


    shell_exec("/usr/sbin/artica-phpfpm-service -iptables-routers");


	build_progress("{building_init_script}",30);
	build_progress("{reconfiguring}",50);

    $fire=new firehol();
    $fire->build();


	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --chk-port --no-firehol");}
	
	build_progress("{starting_firewall}",70);
	shell_exec("/etc/init.d/firehol start");

	build_progress("{done}",100);
	
}

function cybercrime_progress($text,$prc){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"cybercrime.progress");
}

function cybercrime_install():bool{
    $unix=new unix();
    $php    = $unix->LOCATE_PHP5_BIN();

    cybercrime_progress("{installing}",20);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFireholIPSets",1);
    $unix->Popuplate_cron_delete("firehol-ipset");
    cybercrime_progress("{installing}",50);


    $unix->framework_exec("exec.articapcap.php --restart");

    cybercrime_progress("{success}",100);
    return true;
}
function cybercrime_uninstall():bool{
    $unix=new unix();
    $php    = $unix->LOCATE_PHP5_BIN();
    cybercrime_progress("{uninstalling}",20);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFireholIPSets",0);
    $unix->framework_exec("exec.articapcap.php --reload");

    cybercrime_progress("{uninstalling}",50);
    $q=new postgres_sql();
    $q->QUERY_SQL("TRUNCATE TABLE ipset_auto");

    cybercrime_progress("{success}",100);
    return true;
}

function reconfigure_progress():bool{
	$sock   = new sockets();
	$unix   = new unix();
	$php    = $unix->LOCATE_PHP5_BIN();
    $EnableFireholIPSets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFireholIPSets"));

    if($EnableFireholIPSets==0){
        echo "CyberCrime IPSet not enabled.F\n";

    }


        if(is_file("/etc/cron.d/firehol-ipset")){
            @unlink("/etc/cron.d/firehol-ipset");
            system("/etc/init.d/cron reload");
        }

	if(!$sock->isFirehol(true)){
	    echo "[FireHOL]: Firewall is not enabled\n";
		build_progress("{building_init_script}",80);
		build_progress("{building_rules}",81);
		system("$php /usr/share/artica-postfix/exec.secure.gateway.php");
		build_progress("{building_rules}",82);
		if(is_file("/bin/artica-secure-gateway.sh")){system("/bin/artica-secure-gateway.sh");}
		build_progress("FireWall service:{disabled}",100);
		return false;
	}
	
	build_progress("{building_rules}",10);
	$iptables=$unix->find_program("iptables");
	exec("$iptables -V 2>&1",$iptablesResults);
    foreach ($iptablesResults as $line){
        echo "$line\n";
        if(preg_match("#Failed to initialize nft: Protocol not supported#i",$line)){
            echo "* * * Unable to load Firewall modules * * *\nYour kernel disallow loading new modules for security reason, please see the faq https://wiki.articatech.com/maintenance/troubleshooting/kernel\n";
            squid_admin_mysql(0,"Unable to load Firewall modules","Your kernel disallow loading new modules for security reason, please see the faq https://wiki.articatech.com/maintenance/troubleshooting/kernel");
            build_progress("{building_rules} {failed}",110);
            return false;
        }
    }




	$fire=new firehol();
	$fire->build();

	if(!is_file("/usr/sbin/firewall-builder.sh")){
	    echo "[FireHOL]: Warning... /usr/sbin/firewall-builder.sh no such file!\n";
        build_progress("FireWall service:{failed}",110);
	    return false;
    }

	system("/usr/sbin/artica-phpfpm-service -reconfigure-firewall");
	build_progress("{restarting_firewall}",50);
	shell_exec("/usr/sbin/firewall-builder.sh");
	build_progress("{building_init_script}",80);
	build_progress("{done}",100);
    return true;
}

function stop_service(){
	build_progress("{stop_firewall}",10);
    iptables_flush();
	build_progress("{stop_firewall} {success}",100);
    build_status();
}

function dump_itself(){
    $unix=new unix();
    $filetemp=$unix->FILE_TEMP();
    build_progress("{building}",10);
    $firehol=new firehol();

    build_progress("{building} {done}",50);
    $f[]="#!/bin/sh";
    $f[]=$results."\n";
    @file_put_contents($filetemp,@implode("\n",$f));
    @chmod($filetemp,0755);
    build_progress("{importing}...",80);
    echo "[FireHOL]: $filetemp\n";
    system($filetemp);
    @unlink($filetemp);
    build_progress("Set PublicServers: {done}...",100);

}

function start_service(){
	$unix=new unix();
	build_progress("{start_firewall}",10);
	$KERNEL_VERSION         = $unix->KERNEL_VERSION();
	$modprobe               = $unix->find_program("modprobe");
	$nohup                  = $unix->find_program("nohup");
	$iptables               = $unix->find_program("iptables");
	if(is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){shell_exec("$nohup $modprobe xt_ndpi ndpi_enable_flow=1 >/dev/null 2>&1 &");}

	exec("$iptables -S 2>&1",$results);

	foreach ($results as $line){
	    $line=trim($line);
	    if(!preg_match("#-N ARTICAFW_([0-9]+)#",$line,$re)){continue;}
        build_progress("{start_firewall} {success}",100);
	    $time=$re[1];
	    $rest=$unix->time_min($time);
	    echo "[FireHOL]: Already started since {$rest}mn...\n";
	    return true;
    }



	if(is_file("/usr/sbin/firewall-builder.sh")){
	    @chmod("/usr/sbin/firewall-builder.sh",0755);
	    $unix->ToSyslog("[FireHOL]: Starting Artica Firewall",false,"Firewall");
        exec("/usr/sbin/firewall-builder.sh 2>&1",$results);
    }
    build_status();
	build_progress("{start_firewall} {success}",100);
    return true;
}

function isCF(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	exec("$iptables_save 2>&1",$results);
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireHoleDumpRules",@implode("\n", $results));
	
	
	foreach ($results as $index=>$line){
		if(preg_match("#FIREHOL:#", $line)){return true;}
	}
	return false;
}





function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}






function scanservices(){
	$unix=new unix();
	$firehol=$unix->find_program("firehol");
	$f=explode("\n", @file_get_contents($firehol));

	foreach ($f as $line){
			if(preg_match('#server_(.+?)_ports="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["server"]["ports"]=$re[2];
			continue;
		}
		if(preg_match('#client_(.+?)_ports="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["client"]["ports"]=$re[2];
			continue;
		}
		if(preg_match('#helper_(.+)="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["helper"]=$re[2];
			continue;
		}		
		
		
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/firehol.services.db", base64_encode(serialize($array)));
	
}


function dump_service($servicename=null){
    if($servicename==null){
        echo "--dump-service [name]\n";
        return;
    }
    $GLOBALS["VERBOSE"]=true;
    $firehol=new firehol();

    $array=$firehol->extract_ports($servicename);

    foreach ($array as $line){
        echo $line."\n";

    }

    echo "\n";

}

function dump_rule($ruleid){
    $firehol=new firehol();
   $OUT=$firehol->build_rule($ruleid);
echo $OUT."\n";
   foreach ($OUT as $line){
       echo "$line\n";
   }

}



