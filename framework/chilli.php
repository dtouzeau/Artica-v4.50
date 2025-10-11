<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["build"])){build();exit;}

if(isset($_GET["query"])){chilli_query();exit;}
if(isset($_GET["sessiondel"])){sessiondel();exit;}
if(isset($_GET["sessioncon"])){sessioncon();exit;}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["authorize"])){authorize();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function restart(){
	$unix=new unix();
	$nohup=null;
	$end=null;
	$chilli=$unix->find_program("chilli");
	if(!is_file($chilli)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableChilli=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli"));
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if(isset($_GET["nohup"])){
		$nohup=$unix->find_program("nohup")." ";
		$end=" >/dev/null 2>&1 &";
	}	
	
	
	if($EnableChilli==0){
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.chilli.php --stop --byconsole$end");
		
		return;}
	
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.chilli.php --init");
	

	
	shell_exec("$nohup/etc/init.d/chilli restart$end");
	shell_exec("$nohup/etc/init.d/nginx restart$end");
	
}

function authorize(){
	$unix=new unix();
	$chilli_query=$unix->find_program("chilli_query");
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	$HS_LANIF=$ChilliConf["HS_LANIF"];
	$user=$_GET["user"];
	$ClientIP=$_GET["ClientIP"];
	$prefix="$chilli_query -s /var/run/chilli.$HS_LANIF.sock";
	
	$table=chilli_table();
	while (list ($ID, $array) = each ($table) ){
		
		$MAC=$array["MAC"];
		$IP=$array["IP"];
		$STATUS=$array["STATUS"];
		$UID=$array["UID"];
		$URI=$array["URI"];
		if($ClientIP==$IP){
			$cmd="$prefix update username \"$user\" sessionid $ID";
			writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			$cmd="$prefix authorize sessionid $ID";
			writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);			
			shell_exec($cmd);
			echo "OK";
			return;
		}
	}
}

function sessioncon(){
	$unix=new unix();
	$chilli_query=$unix->find_program("chilli_query");
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	$HS_LANIF=$ChilliConf["HS_LANIF"];
	$ID=$_GET["sessioncon"];
	$prefix="$chilli_query -s /var/run/chilli.$HS_LANIF.sock";	
	$cmd="$prefix update username \"guest\" sessionid $ID";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$cmd="$prefix authorize sessionid $ID";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	echo "OK";	
}

/*
 * 
 * 
 * 00-0C-29-E1-1B-21 192.168.5.64 pass 51b367a20000000a 1 admin 445/0 21/0 13100/0 6330/0 0 0 0%/0 0%/0 http://93.88.245.88/
 * 
 * 

    [0]=>00-0C-29-E1-1B-21 192.168.5.64 pass 51b367a20000000a 1 admin 445/0 21/0 13100/0 6330/0 0 0 0%/0 0%/0 http://93.88.245.88/
    [1]=>00-0C-29-E1-1B-21
    [2]=>192.168.5.64
    [3]=>pass
    [4]=>51b367a20000000a
    [5]=>1
    [6]=>admin
    [7]=>445/0
    [8]=>21/0
    [9]=>13100/0
    [10]=>6330/0
    [11]=>0
    [12]=>0
    [13]=>0
    [14]=>0
    [15]=>0
    [16]=>0
    [17]=>http://93.88.245.88/

 */

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --chilli --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
	
	
}

function chilli_query(){
$sortie=base64_encode(serialize(chilli_table()));
echo "<articadatascgi>$sortie</articadatascgi>";
	
}
function chilli_table(){
	$unix=new unix();
	$chilli_query=$unix->find_program("chilli_query");
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	$HS_LANIF=$ChilliConf["HS_LANIF"];
	if($HS_LANIF==null){
		return array();
	}

	$cmd="$chilli_query -s /var/run/chilli.$HS_LANIF.sock list 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$chilli_query -s /var/run/chilli.$HS_LANIF.sock list 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#([0-9A-Z\-]+)\s+([0-9\.]+)\s+([a-z]+)\s+(.*?)\s+([0-9]+)\s+(.*?)\s+([0-9\/]+)\s+([0-9\/]+)\s+([0-9\/]+)\s+([0-9\/]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\/([0-9]+)\s+([0-9]+)%\/([0-9]+)\s+(.*)#", $ligne,$re)){
			$MAC=$re[1];
			$MACSOURCE=$re[1];
			$MAC=strtolower($MAC);
			$MAC=str_replace("-", ":", $MAC);
			$IP=$re[2];
			$ID=$re[4];
			$status=$re[3];
			$username=$re[6];
			$URi=$re[17];
			$ARRAY[$ID]["MAC"]=$MAC;
			$ARRAY[$ID]["IP"]=$IP;
			$ARRAY[$ID]["STATUS"]=$status;
			$ARRAY[$ID]["UID"]=$username;
			$ARRAY[$ID]["URI"]=$URi;
			continue;
		}

		if(preg_match("#([0-9A-Z\-]+)\s+([0-9\.]+)\s+([a-z]+)\s+(.*?)\s+([0-9]+)\s+(.*?)\s+#", $ligne,$re)){
			$MAC=$re[1];
			$MACSOURCE=$re[1];
			$MAC=strtolower($MAC);
			$MAC=str_replace("-", ":", $MAC);
			$IP=$re[2];
			$ID=$re[4];
			$status=$re[3];
			$username=$re[6];
			$ARRAY[$ID]["MAC"]=$MAC;
			$ARRAY[$ID]["IP"]=$IP;
			$ARRAY[$ID]["STATUS"]=$status;
			$ARRAY[$ID]["UID"]=$username;
			$ARRAY[$ID]["URI"]=$URi;
			continue;
		}

		writelogs_framework("Not match $ligne",__FUNCTION__,__FILE__,__LINE__);

	}

	return $ARRAY;

}



function build(){
	$unix=new unix();
	$nohup=null;
	$end=null;
	$chilli=$unix->find_program("chilli");
	if(!is_file($chilli)){return;}
	$EnableChilli=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli"));
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==0){return;}
	if(isset($_GET["nohup"])){
		$nohup=$unix->find_program("nohup")." ";
		$end=" >/dev/null 2>&1 &";
	}
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup$php /usr/share/artica-postfix/exec.chilli.php --build$end");

}

function sessiondel(){
	$unix=new unix();
	$ID=$_GET["sessiondel"];
	$chilli_query=$unix->find_program("chilli_query");
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	$HS_LANIF=$ChilliConf["HS_LANIF"];	
	$cmd="$chilli_query -s /var/run/chilli.$HS_LANIF.sock logout sessionid $ID 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
}