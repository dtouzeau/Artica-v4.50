<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.samba.privileges.inc");
if(isset($_GET["netadsinfo"])){netadsinfo();exit;}
if(isset($_GET["netrpctestjoin"])){netrpctestjoin();exit;}
if(isset($_GET["netrpcinfo"])){netrpcinfo();exit;}
if(isset($_GET["wbinfoalldom"])){wbinfo_alldomains();exit;}
if(isset($_GET["wbinfomoinst"])){wbinfo_checksecret();exit;}
if(isset($_GET["wbinfomoinsa"])){wbinfo_authenticate();exit;}
if(isset($_GET["build-homes"])){build_homes();exit;}
if(isset($_GET["GetNetAdsInfos"])){GetNetAdsInfos();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");


function netadsinfo(){
	$unix=new unix();

	$net=$unix->find_program("net");
	if(is_file($net)){
		$cmd="$net ads info 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: net no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function netrpctestjoin(){
	$unix=new unix();

	$net=$unix->find_program("net");

	
	
	$results[]="<strong><i>Active Directory Mode:</i></strong>";
	if(is_file($net)){
		$cmd="$net ads testjoin 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: net no such binary !";
	}	
	
	$KerbAuthSMBV2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthSMBV2"));
	if($KerbAuthSMBV2==0){
		$results[]="<br><strong><i>NT4 Mode (optional):</i></strong>";
		if(is_file($net)){
			$cmd="$net rpc testjoin 2>&1";
			exec($cmd,$results);
		}else{
			$results[]="Failed: net no such binary !";
		}
	}
	
	
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function netrpcinfo(){
	$unix=new unix();

	$net=$unix->find_program("net");
	$array=unserialize(base64_decode($_GET["auth"]),array("allowed_classes"=>false));
	if($array["USER"]==null){
		$results[]="netrpcinfo();failed No such user !!!";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	// Pentest 2026-11-04 : identifiants passes en un seul argument quote (injection shell root).
	if(is_file($net)){
		$cmd="$net rpc info -U ".fw_shell_quote("{$array["USER"]}%{$array["PASSWD"]}")." 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: net no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
	
}



function wbinfo_alldomains(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]),array("allowed_classes"=>false));
	if(is_file($wbinfo)){
		$cmd="$wbinfo --online-status 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function wbinfo_checksecret(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]),array("allowed_classes"=>false));
	if(is_file($wbinfo)){	
		$cmd="$wbinfo --check-secret 2>&1";
	exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function wbinfo_authenticate(){
	$unix=new unix();
	$domain=null;
	$wbinfo=$unix->find_program("wbinfo");
	$array=unserialize(base64_decode($_GET["auth"]),array("allowed_classes"=>false));
	if($array["USER"]==null){
		$results[]="<strong style='color:#d32d2d'>netrpcinfo();failed No such user !!!</strong>";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}	
	// Pentest 2026-11-04 : identifiants et domaine quotes (injection shell root).
	if(isset($array["WORKGROUP"])){
		if($array["WORKGROUP"]<>null){
			$domain=" --domain=".fw_shell_quote($array["WORKGROUP"]);
		}
	}
	
	if(is_file($wbinfo)){	
		$cmd="$wbinfo --authenticate=".fw_shell_quote("{$array["USER"]}%{$array["PASSWD"]}")."$domain 2>&1";
		exec($cmd,$results);
	}else{
		$results[]="Failed: wbinfos no such binary !";
	}
	writelogs_framework("$cmd = " . count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}

function build_homes(){
	$uid=$_GET["build-homes"];
	// Pentest 2026-11-04 : uid = nom simple (traversee de repertoire et injection shell).
	if(!preg_match('#^[A-Za-z0-9_.@$-]+$#',$uid) || strpos($uid,"..")!==false){return;}
	$SambaProfilePath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaProfilePath"));
	if(trim($SambaProfilePath)==null){$SambaProfilePath="/home/export/profile";}
	@mkdir("$SambaProfilePath/$uid.V2",0770,true);
	@chown("$SambaProfilePath/$uid.V2", $uid);
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $chown -R ".fw_shell_quote($uid)." ".fw_shell_quote("$SambaProfilePath/$uid.V2"));
	
}
function GetNetAdsInfos(){
	$unix=new unix();
	@mkdir("/etc/squid3",0755,true);
	if(is_file("/etc/squid3/NET_ADS_INFOS")){
		$array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
		if(count($array)>5){
			echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
			return;
		}
	}
	
	$net=$unix->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	
	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	@file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}
function searchInSyslog(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]),array("allowed_classes"=>false));
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

	if($PID<>null){$PID_P=".*?pid=$PID.*?";}
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



	$search="$date.*?$mainline";
	$search=str_replace(".*?.*?",".*?",$search);
	// Pentest 2026-11-04 : motif quote (injection shell root).
	$cmd="$grep --binary-files=text -i -E ".fw_shell_quote($search)." /var/log/samba/log.smbd |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/smbd.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/smbd.syslog.pattern", $search);
	shell_exec($cmd);

}

