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
if(isset($_GET["events-query"])){events_query();exit;}
if(isset($_GET["activate-ufdb"])){activate_ufdb();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["restart-recusor"])){recursor_restart();exit;}
writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);


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
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/itchart.status";
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --itchart >{$GLOBALS["LOGSFILES"]} 2>&1";
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
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/ITCharter.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/itchart.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/itchart.syslog.pattern", $search);
    shell_exec($cmd);

}