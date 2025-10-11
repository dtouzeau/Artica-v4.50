<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.postfix.inc');
include_once(dirname(__FILE__).  "/framework/frame.class.inc");
include_once(dirname(__FILE__).  '/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;}

$users=new usersMenus();
$sock=new sockets();
$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
if($DisableMessaging==1){exit();}


$unix=new unix();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);

$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==0){exit();}
if($unix->process_exists($pid)){if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::Already executed PID: $pid.. aborting the process\n";}exit();}
if(system_is_overloaded()){exit();}
$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
watchdog();
corrupt_queue_master();
postqueue_master("MASTER");
if($EnablePostfixMultiInstance==1){multiples_instances();exit;}


function watchdog(){
    $unix=new unix();
    $PostfixQueueEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueEnabled"));
    $PostfixQueueMaxMails=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueMaxMails"));
    if($PostfixQueueMaxMails==0){$PostfixQueueMaxMails=20;}

    if($PostfixQueueMaxMails==null){$PostfixQueueMaxMails=20;}
    if($PostfixQueueEnabled==0){
	if($GLOBALS["VERBOSE"]){echo "PostfixQueueEnabled is disabled\n";}return;}

    $postfix_system=new postfix_system();
    $array=$postfix_system->getQueuesNumber();

    if($array["active"]>$PostfixQueueMaxMails){
        $postqueue=$unix->find_program("postqueue");
        shell_exec("$postqueue -f");
    }

foreach ($array as $num=>$val){
	$logs[]="$num=$val message(s)";
	if($GLOBALS["VERBOSE"]){echo "$num=$val message(s)\n";}
	if(intval($val)>$PostfixQueueMaxMails){
		if(is_file("/etc/artica-postfix/croned.1/postfix.$num.exceed")){if(file_time_min("/etc/artica-postfix/croned.1/postfix.$num.exceed")<30){continue;}}
		@file_put_contents("/etc/artica-postfix/croned.1/postfix.$num.exceed","#");
		$subject="Postfix queue $num exceed limit";
		$text="The $num storage queue contains $val messages\nIt exceed the maximum $PostfixQueueMaxMails messages number...";
		send_email_events($subject,$text,'system');
	}
}

$logs[]="$num=$val message(s)";



RTMevents(implode(" ",$logs));
}

function corrupt_queue_master(){
	foreach (glob("/var/spool/postfix/corrupt/*") as $filename) {
	$basename=basename($filename);	
	if(is_file($filename)){
			if(!is_file("/var/spool/postfix/maildrop/$basename")){
				shell_exec("/bin/mv $filename /var/spool/postfix/maildrop/$basename");
			}
		}
	}
}


function postqueue_master($instance="MASTER"){
	$unix=new unix();
	$NICE=EXEC_NICE();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $instance:: analyze $instance\n";}
	$instance_name=$instance;
	if($instance=="MASTER"){$instance_text="master";$instance=null;}else{$instance="-$instance";$instance_text=$instance;}
	$postqueue=$unix->find_program("postqueue");
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $instance:: $postqueue -c /etc/postfix$instance -p\n";}
	exec("$NICE$postqueue -c /etc/postfix$instance -p",$results);
	$count=count($results);
	
	$array["COUNT"]=postqueue_master_count($instance,$results);
	
	if(substr($instance, 0,1)=="-"){$instance=substr($instance, 1,strlen($instance));}
	if(substr($instance_text, 0,1)=="-"){$instance_text=substr($instance_text, 1,strlen($instance_text));}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $instance:: {$array["COUNT"]} message(s)\n";}
	
	$MSGID=null;
	for($i=0;$i<=$count;$i++){
		$line=$results[$i];
		$active=false;
		if(preg_match("#([A-Z0-9\*]+)\s+([0-9]+)\s+(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)$#",$line,$re)){
			$MSGID=$re[1];
			if(strpos($MSGID,"*")>0){$active=true;}
			$MSGID=str_replace("*","",$MSGID);
			$size=$re[2];
			$day=$re[3];
			$dayNum=$re[4];
			$time=$re[5];
			$from=$re[6];
			$timestamp=strtotime("$day $dayNum $time");
			
			$date=date('Y-m-d H:i:s',$timestamp);
			$array["LIST"][$instance_text][$MSGID]["DATE"]="$day $dayNum $time";
			$array["LIST"][$instance_text][$MSGID]["timestamp"]=$date;
			$array["LIST"][$instance_text][$MSGID]["FROM"]="$from";
			$array["LIST"][$instance_text][$MSGID]["msgsize"]=$size;
			if($active){
				$array["LIST"][$instance_text][$MSGID]["ACTIVE"]="YES";
			}
			if(preg_match("#(.+?)@(.+)#",$array["LIST"][$instance_text][$MSGID]["FROM"],$re)){$array["LIST"][$MSGID]["FROM_DOMAIN"]=$re[2];}
			continue;
		}
		if(preg_match("#^\((.+?)\)$#",trim($line),$re)){
			$array["LIST"][$instance_text][$MSGID]["STATUS"]=$re[1];
			continue;
		}
		
		if(preg_match("#^\s+\s+\s+(.+?)$#",$line,$re)){
			$array["LIST"][$instance_text][$MSGID]["TO"][]=trim($re[1]);
			continue;
		}	
			
		
		
		
		
	}
	
	$content=serialize($array);
	$filename=md5($content);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/postqueue")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/postqueue",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/postqueue/$filename.array",$content);
}

function postqueue_master_count($instance="MASTER",$results){
	$unix=new unix();
	$instance_name=$instance;
	if($instance=="MASTER"){$instance=null;}else{$instance="-$instance";}
	reset($results);
	foreach ($results as $num=>$line){
		if(preg_match("#Mail queue is empty#",$line)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $line ($num)\n";} 
			$count=0;
			break;
		}
		
		if(preg_match("#-- [0-9]+\s+([a-zA-Z]+)\s+in\s+([0-9]+)\s+Requests#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $line ($num)\n";}
			$count=$re[2];
			break;
		}
		
	}

	return $count;
}

function multiples_instances(){
	$sql="SELECT `value` FROM postfix_multi WHERE `key`='myhostname'";
	$q= new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$instance=$ligne["value"];
		postqueue_master($instance);
	}

}



function RTMevents($text){
		$f=new debuglogs();
		$f->events(basename(__FILE__)." $text","{$GLOBALS["ARTICALOGDIR"]}/artica-status.debug");
		}
?>