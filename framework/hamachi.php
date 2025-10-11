<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["hamachi-ip"])){hamachi_currentIP();}
if(isset($_GET["hamachi-id"])){hamachi_currentID();}
if(isset($_GET["hamachi-status"])){hamachi_currentStatus();}
if(isset($_GET["hamachi-sessions"])){hamachi_sessions();}
if(isset($_GET["hamachi-gateway"])){hamachi_gateway();}
if(isset($_GET["peer-infos"])){hamachi_peer_infos();}
if(isset($_GET["net-infos"])){hamachi_net_infos();}
if(isset($_GET["hamachi-init"])){hamachi_init();}
if(isset($_GET["hamachi-restart"])){hamachi_restart();}




function hamachi_gateway(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.hamachi.php --gateway >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));		
	
}
function hamachi_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup /etc/init.d/logmein-hamachi >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));		
	
}
function hamachi_peer_infos(){
	$unix=new unix();
	$hamachi=$unix->find_program("hamachi");
	exec("$hamachi peer {$_GET["peer-infos"]} 2>&1",$datas);
	foreach ($datas as $num=>$ligne){
			if(preg_match("#(.+?):(.+)#",$ligne,$re)){
				$array[trim($re[1])]=trim($re[2]);
				
			}
		}	
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}
function hamachi_net_infos(){
	$unix=new unix();
	$hamachi=$unix->find_program("hamachi");
	exec("$hamachi network {$_GET["net-infos"]} 2>&1",$datas);
	foreach ($datas as $num=>$ligne){
			if(preg_match("#(.+?):(.+)#",$ligne,$re)){
				$array[trim($re[1])]=trim($re[2]);
				
			}
		}	
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function hamachi_init(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.hamachi.php --initd >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));		
}



function hamachi_currentIP(){
	$unix=new unix();
	$cmd=$unix->find_program("hamachi")." 2>&1";
	exec($cmd,$datas);
	
	foreach ($datas as $num=>$ligne){
		if(preg_match("#address.+?([0-9\.]+)\s+#",$ligne,$re)){
			echo "<articadatascgi>". $re[1]."</articadatascgi>";
			break;
		}
	}
	
}
function hamachi_currentID(){
	$unix=new unix();
	$cmd=$unix->find_program("hamachi")." 2>&1";
	exec($cmd,$datas);
	writelogs_framework("$cmd = ". count($datas)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($datas as $num=>$ligne){
		if(preg_match("#client id.+?([0-9\-]+)#",$ligne,$re)){
			echo "<articadatascgi>". trim($re[1])."</articadatascgi>";
			break;
		}
	}
	
}
function hamachi_currentStatus(){
	$unix=new unix();
	$cmd=$unix->find_program("hamachi")." 2>&1";
	exec($cmd,$datas);
	writelogs_framework("$cmd = ". count($datas)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($datas as $num=>$ligne){
		if(preg_match("#status\s+:(.+)#",$ligne,$re)){
			echo "<articadatascgi>". trim($re[1])."</articadatascgi>";
			break;
		}
	}
	
}
function hamachi_sessions(){
	$unix=new unix();
	
	$session=array();
	exec($unix->find_program("hamachi")." list",$l);
	foreach ($l as $num=>$ligne){
		if(preg_match("#You have no networks#", $ligne)){break;}
		if(preg_match("#\[(.+?)\]#",$ligne,$re)){$net=$re[1];continue;}
		if(preg_match("#\*\s+([0-9\-]+)\s+(.+?)\s+([0-9\.]+)\s+.+?\s+(.+?)\s+([A-Z]+)\s+([0-9\.]+):#",$ligne,$re)){
			$session[$net][]=array("NETID"=>$re[1],"HOST"=>$re[2],"IPPUB"=>$re[3],"TYPE"=>$re[4],"PROTO"=>$re[5],"LOCALIP"=>$re[6]);
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($session))."</articadatascgi>";
}


?>