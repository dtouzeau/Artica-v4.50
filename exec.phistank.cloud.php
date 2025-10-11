<?php
ini_set('memory_limit','1000M');
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');

if($argv[1]=="--parse"){parse();exit;}
if($argv[1]=="--compile"){Compileufdb();exit;}
startx();


function startx(){
	$sock=new sockets();
	$uri="http://data.phishtank.com/data/a524064556a0567429302302773dfe0e48fb2f59a0cc5b8d85f3dd46c5fab9dc/online-valid.php_serialized.gz";
	$tmpfile="/root/online-valid.php_serialized.gz";

	$curl=new ccurl($uri);
	
	$heads=$curl->getHeaders();
	if(!isset($heads["http_code"])){
		echo "phishtank: Unable to get header information\n";
		return;
	}
	
	if(!isset($heads["Last-Modified"])){$heads["Last-Modified"]=$heads["filetime"];}
	
	$Time=$heads["Last-Modified"];
	$MyDate=$sock->GET_INFO("PhishTankLastDate");
	
	echo "Last date: $Time\n";
	if(!is_file($tmpfile)){$MyDate=null;}
	if($Time==$MyDate){
		echo "No new update...\n";
		return;
	}
	
	
	echo "Downloading PhishTank...$tmpfile\n";
	if(!$curl->GetFile($tmpfile)){
		echo "Downloading PhishTank...$tmpfile Failed\n";
		@unlink($tmpfile);
		return;
	}
	
	echo "Downloading PhishTank...$tmpfile Success\n";
	parse();
	
}

function Compileufdb(){
	$unix=new unix();
	$LastNumber=intval(@file_get_contents("/root/PhistankLastNumber"));
	$q=new mysql_squid_builder();
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	
	$Count1=$q->COUNT_ROWS("category_phishtank");
	$Count2=$q->COUNT_ROWS("categoryuris_phishtank");
	$NewNumber=$Count1+$Count2;
	
	if($NewNumber==$LastNumber){return;}
	
	$WORKDIR="/home/artica/phishtank";
	@mkdir($WORKDIR,0777,true);
	$workingtempdir="$WORKDIR/category_phishtank";
	$workingtempFile="$workingtempdir/domains";
	$workingurlTempfile="$workingtempdir/urls";
	@mkdir($workingtempdir,0777,true);
	$unix->chmod_func(0777, $workingtempdir);
	if(is_file($workingtempFile)){@unlink($workingtempFile);}
	if(is_file($workingurlTempfile)){@unlink($workingurlTempfile);}
	
	$sql="SELECT pattern FROM category_phishtank ORDER BY pattern INTO OUTFILE '$workingtempFile' LINES TERMINATED BY '\n';";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		sendEmail("Phishtank ALERT! category_phishtank MySQL error",$q->mysql_error);
		exit();
	}
	
	$sql="SELECT pattern FROM categoryuris_phishtank ORDER BY pattern INTO OUTFILE '$workingurlTempfile' LINES TERMINATED BY '\n';";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		sendEmail("Phishtank ALERT! category_phishtank MySQL error",$q->mysql_error);
		exit();
	}
	
	@unlink("$workingtempdir/domains.ufdb");
	$categoryKey="phishtank";
	$u=" -u $workingtempdir/urls";
	$d=" -d $workingtempdir/domains";
	$cmd="$ufdbGenTable -n -q -W -t $categoryKey$d$u >/dev/null 2>&1";
	echo "[phishtank]::$cmd\n";	
	
	shell_exec($cmd);
	
	if(!is_file("$workingtempdir/domains.ufdb")){
		sendEmail("ALERT! phishtank domains.ufdb no such file!");
		return;
	}
	
	if(is_file($workingtempFile)){@unlink($workingtempFile);}
	$sql="SELECT pattern FROM category_phishtank WHERE pattern not like '%.addr' ORDER BY pattern INTO OUTFILE '$workingtempFile' LINES TERMINATED BY '\n';";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		sendEmail("ALERT! category_phishtank(C-ICAP) MySQL error",$q->mysql_error);
		exit();
	}
	
	
	
	$unix->compress("$workingtempdir/domains.ufdb", "$workingtempdir/phishtank_ufdb_domains.gz");
	$unix->compress("$workingtempFile", "$workingtempdir/phishtank_cicap_domains.gz");
	$unix->compress("$workingtempdir/urls", "$workingtempdir/phishtank_url.gz");
	
	$MAINARRAY["phishtank_ufdb_domains.gz"]=md5_file("$workingtempdir/phishtank_ufdb_domains.gz");
	$MAINARRAY["phishtank_cicap_domains.gz"]=md5_file("$workingtempdir/phishtank_cicap_domains.gz");
	$MAINARRAY["phishtank_url.gz"]=md5_file("$workingtempdir/phishtank_url.gz");
	$MAINARRAY["DOMAINS_COUNT"]=$Count1;
	$MAINARRAY["URLS_COUNT"]=$Count2;
	$MAINARRAY["TIME"]=time();
	
	@file_put_contents("/root/PhistankLastNumber", $NewNumber);
	
	
	@file_put_contents("$workingtempdir/phishtank.txt", base64_encode(serialize($MAINARRAY)));
	PushToRepo("$workingtempdir/phishtank.txt");
	PushToRepo("$workingtempdir/phishtank_ufdb_domains.gz");
	PushToRepo("$workingtempdir/phishtank_cicap_domains.gz");
	PushToRepo("$workingtempdir/phishtank_url.gz");
	sendEmail("Success uploading Phishtank $Count1 domains and $Count2 urls");
	
}

function PushToRepo($filepath){
	$curl="/usr/bin/curl";
	$unix=new unix();
	$ftpass5=trim(@file_get_contents("/root/ftp-password5"));
	$uri="ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS";
	$size=round(filesize($filepath)/1024);
	$ftpass5=$unix->shellEscapeChars($ftpass5);
	echo "Push $filepath ( $size KB ) to $uri\n$curl -T $filepath $uri/ --user $ftpass5\n";
	shell_exec("$curl -T $filepath $uri/ --user $ftpass5");
}


function parse(){
	$tmpfile="/root/online-valid.php_serialized.gz";
	$tmpfileDest="/root/online-valid.php_serialized";
	$unix=new unix();
	$tcp=new IP();
	$uuid=$unix->GetUniqueID();
	if(!is_file($tmpfileDest)){
		echo "Uncompress PhishTank...$tmpfile -> $tmpfileDest\n";
		$unix->uncompress($tmpfile, $tmpfileDest);
		
	}
	
	$q=new mysql_squid_builder();
	
	$Count1=$q->COUNT_ROWS("category_phishtank");
	$Count2=$q->COUNT_ROWS("categoryuris_phishtank");
	
	
	$prefixdom="INSERT IGNORE INTO category_phishtank (zmd5,zDate,category,pattern,uuid) VALUES ";
	$prefixdurl="INSERT IGNORE INTO categoryuris_phishtank (zmd5,zDate,pattern,enabled) VALUES ";

	
	$c=0;
	$d=0;
	$u=0;
	$q->CreateCategoryUrisTable("phishtank");
	$sqlDOM=array();
	$sqluris=array();
	$MAIN=unserialize(@file_get_contents($tmpfileDest));
	while (list ($index, $array) = each ($MAIN)){
		$path=null;
		$query=null;
		$url=$array["url"];
		
	
		$c++;
		$parsed=parse_url($url);
		$host=$parsed["host"];
		if(preg_match("#^www\.(.+)#", $host,$re)){$host=$re[1];}
		$domain=$host;
		if(isset($parsed["path"])){$path=$parsed["path"];}
		if(isset($parsed["query"])){$query=$parsed["query"];}
		
		if($tcp->isValid($domain)){
			$domain=ip2long($domain).".addr";
		}
		
		$md5=md5("phishtank".$domain);
		$q=new mysql_squid_builder();
		
		if(count($sqlDOM)>1500){
			echo "Inserting ".count($sqlDOM)." $d domains/$c lines\n";
			$q->QUERY_SQL($prefixdom.@implode(",", $sqlDOM));
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$sqlDOM=array();
		}		
		
		if(count($sqluris)>1500){
			echo "Inserting: ".count($sqluris)." $u urls/$c lines\n";
			$q->QUERY_SQL($prefixdurl.@implode(",", $sqluris));
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$sqluris=array();
		}
		
		
		
		
		if($path=="/"){
			if($query==null){
				$d++;
				$sqlDOM[]="('$md5',NOW(),'phishtank','$domain','$uuid')";
				continue;
			}
		}
		if($path==null){
			if($query==null){
				$d++;
				$sqlDOM[]="('$md5',NOW(),'phishtank','$domain','$uuid')";
				continue;
			}
		}
		
		$u++;
		$pattern=mysql_escape_string2("$host{$path}$query");
		$sqluris[]="('$md5',NOW(),'$pattern','1')";
		
	}
	
	if(count($sqlDOM)>0){
		echo "Inserting ".count($sqlDOM)." $d domains/$c lines\n";
		$q->QUERY_SQL($prefixdom.@implode(",", $sqlDOM));
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$sqlDOM=array();
	}
	
	if(count($sqluris)>0){
		echo "Inserting: ".count($sqluris)." $u urls/$c lines\n";
		$q->QUERY_SQL($prefixdurl.@implode(",", $sqluris));
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$sqluris=array();
	}
	
	$CountNew1=$q->COUNT_ROWS("category_phishtank");
	$CountNew2=$q->COUNT_ROWS("categoryuris_phishtank");
	
	if($CountNew1<>$Count1){Compileufdb();return;}
	if($CountNew2<>$Count2){Compileufdb();return;}
	
	
}

function sendEmail($subject,$content=null){
	$unix=new unix();

	$hostname="ks220503.kimsufi.com";
	$mailfrom="root@$hostname";
	$recipient="david@articatech.com";
	$TargetHostname="37.187.142.164";
	$params["helo"]=$hostname;
	$params["host"]=$TargetHostname;
	$params["do_debug"]=true;
	$params["debug"]=true;


	$smtp=new smtp($params);

	if(!$smtp->connect($params)){
		smtp::events("Error $smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	$random_hash = md5(date('r', time()));

	$content=str_replace("\r\n", "\n", $content);
	$content=str_replace("\n", "\r\n", $content);
	$body[]="Return-Path: <$mailfrom>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $mailfrom (robot)";
	$body[]="Subject: $subject";
	$body[]="To: $recipient";
	$body[]="";
	$body[]="";
	$body[]=$content;
	$body[]="";
	$finalbody=@implode("\r\n", $body);

	if(!$smtp->send(array(
			"from"=>"$mailfrom",
			"recipients"=>$recipient,
			"body"=>$finalbody,"headers"=>null)
	)
	){
		smtp::events("Error $smtp->error_number: Could not send to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		$smtp->quit();
		return;
	}

	smtp::events("Success sending message trough [{$TargetHostname}:25]",__FUNCTION__,__FILE__,__LINE__);
	$smtp->quit();


}