<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__) . "/ressources/class.ccurl.inc");
include_once(dirname(__FILE__) . "/ressources/class.tcpip.inc");
include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');


if($argv[1]=="--push"){PushToRepo_alls();exit;}
xstart();
//c-icap-mods-sguardDB

function xstart(){
	$unix=new unix();
	$MAIN_CACHE=unserialize(@file_get_contents("/root/C_ICAP_COMPILE_DATABASES"));
	$q=new mysql_squid_builder();
	
	
	$ufdbGenTable=$unix->find_program("c-icap-mods-sguardDB");
	$trans["category_malware"]="malware";
	$trans["category_suspicious"]="suspicious";
	$trans["category_phishing"]="phishing";
	$trans["category_spyware"]="spyware";
	$UPDATE_FTP=false;
	
	$WORKDIR="/home/artica/cicapv10";
	$OUTPUTDIR="/home/artica/cicapv10Export";
	@mkdir($OUTPUTDIR,0755,true);
	$UPDATE_ARRAY=unserialize(base64_decode(@file_get_contents("$OUTPUTDIR/cicapindex.txt")));
	
	
	$tarbin=$unix->find_program("tar");
	while (list ($category_table, $category) = each ($trans) ){
		echo "Extracting $category_table\n";
		
		$CountCategoryTableRows=$q->COUNT_ROWS("$category_table");
		echo "$category_table: $CountCategoryTableRows rows\n";
		if(isset($MAIN_CACHE[$category_table])){
			echo "Old number was {$MAIN_CACHE[$category_table]} < > $CountCategoryTableRows\n";
			if($MAIN_CACHE[$category_table]==$CountCategoryTableRows){continue;}
		}
		
		if($CountCategoryTableRows==0){
			sendEmail("ALERT! $category_table NO ROW!");
			continue;
		}
		
		$workingtempdir="$WORKDIR/$category_table";
		$workingtempFile="$workingtempdir/domains";
		@mkdir($workingtempdir,0777,true);
		$unix->chmod_func(0777, $workingtempdir);
		if(is_file($workingtempFile)){@unlink($workingtempFile);}
		
		
		
		$sql="SELECT pattern FROM $category_table WHERE pattern not like '%.addr' ORDER BY pattern INTO OUTFILE '$workingtempFile' LINES TERMINATED BY '\n';";
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			sendEmail("ALERT! $category_table MySQL error",$q->mysql_error);
			exit();
		}
		
		$TGZ_file="cicap_$category_table.gz";
		$TGZ_path="$OUTPUTDIR/$TGZ_file";
		if(is_file($TGZ_path)){@unlink($TGZ_path);}
		if(!$unix->compress($workingtempFile, $TGZ_path)){
			@unlink($workingtempFile);
			@unlink($TGZ_path);
			sendEmail("ALERT! $workingtempFile compress error","$workingtempFile -> $TGZ_path");
			continue;
		}
		@unlink($workingtempFile);
		$UPDATE_FTP=true;
		$UPDATE_ARRAY[$category_table]["TIME"]=time();
		$UPDATE_ARRAY[$category_table]["COUNT"]=$CountCategoryTableRows;
		$UPDATE_ARRAY[$category_table]["FILENAME"]="$TGZ_file";

		$UPDATE_ARRAY[$category_table]["FILENAME_MD5"]=md5_file($TGZ_path);
		$UPDATE_ARRAY[$category_table]["FILENAME_SIZE"]=@filesize($TGZ_path);
		$MAIN_CACHE[$category_table]=$CountCategoryTableRows;
	}
	
	
	$indexdata=base64_encode(serialize($UPDATE_ARRAY));
	@file_put_contents("$OUTPUTDIR/cicapindex.txt", $indexdata);
	@file_put_contents("/root/C_ICAP_COMPILE_DATABASES", serialize($MAIN_CACHE));
	if($UPDATE_FTP){PushToRepo_alls();}
}
function PushToRepo_alls(){
	$OUTPUTDIR="/home/artica/cicapv10Export";
	$unix=new unix();
	$FILES=$unix->DirFiles($OUTPUTDIR);

	while (list ($filename, $category) = each ($FILES) ){
		$srcfile="$OUTPUTDIR/$filename";
		PushToRepo($srcfile);
	}


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
