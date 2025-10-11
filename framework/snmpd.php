<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["events"])){snmpd_events();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["installed"])){installed();exit;}
if(isset($_GET["uncompress"])){uncompress();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["walk-local"])){walk_local();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);






function walk_local(){
    $unix=new unix();
    $port=intval($_GET["port"]);
    $SNMPDCommunity=trim($_GET["com"]);
    $snmpwalk=$unix->find_program("snmpwalk");
    $Disablev2=intval($_GET["Disablev2"]);
    $ipaddr="localhost";
    $auth=unserialize(base64_decode($_GET["auth"]));
    $eth=$_GET["eth"];
    if($eth<>null){
        $ipaddr = $unix->InterfaceToIPv4($eth);
    }

    $middle="-v 2c -c $SNMPDCommunity";

    if($Disablev2==1){
        $middle="-v 3 -u {$auth["username"]} -a SHA -x AES -A \"{$auth["password"]}\" -X \"{$auth["passphrase"]}\" -l authPriv";
    }

    $cmd="$snmpwalk $middle $ipaddr:$port iso.3.6.1.2.1.1.1.0 >/usr/share/artica-postfix/ressources/logs/web/snmpd.walk 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --snmpd --nowachdog >/usr/share/artica-postfix/ressources/logs/web/snmpd.status 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function installed(){
	$unix=new unix();
	$snmpd=$unix->find_program("snmpd");
	if(!is_file($snmpd)){echo "<articadatascgi>FALSE</articadatascgi>";return;}
	echo "<articadatascgi>TRUE</articadatascgi>";
}


function uncompress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress"];
	$nohup=$unix->find_program("nohup");
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
		return;
	}

	
	$cmd="$tar -xhf $FilePath -C /";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_VERSION"));
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";

}

function snmpd_events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["events"]));
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

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
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



    $search="$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/snmpd.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/snmpd.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/snmpd.syslog.pattern", $search);
    shell_exec($cmd);

}