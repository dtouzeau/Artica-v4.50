<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["FORCE"]=true;$GLOBALS["VERBOSE"]=true;}

Run();


function Run(){
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){return true;}
	$cacheTemp="/etc/artica-postfix/pids/exec.spamassassin.update.artica.time";
	$unix=new unix();
	$ztime=$unix->file_time_min($cacheTemp);
	if(!$GLOBALS["FORCE"]){
		if($ztime<15){
			echo "Please, restart later (15mn) - current = {$ztime}mn\n";
			return ;
		}
	}

	@unlink($cacheTemp);
	@file_put_contents($cacheTemp, time());
	KAM_CF();

	if(!$unix->CORP_LICENSE()){
		$files[]="spamassassin-rules1.cf";
		$files[]="spamassassin-rules2.cf";
		$files[]="spamassassin-rules3.cf";
		foreach ($files as $filename){
			if(is_file("/etc/spamassassin/$filename")){@unlink("/etc/spamassassin/$filename");}
		}
		$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize(array()), "CurrentSPAMASSDBArtica");
		echo "No License...\n";
		die();
	}

	$mirror="http://mirror.articatech.net/webfilters-databases";
	echo "Downloading $mirror/blacklist-database.txt\n";
	$curl=new ccurl("$mirror/blacklist-database.txt");
	$curl->NoHTTP_POST=true;
	$TEMPFILE=$unix->FILE_TEMP();
	if(!$curl->GetFile($TEMPFILE)){
		squid_admin_mysql(0, "Unable to get Artica AntiSpam index file (download error)", $curl->error);
		@unlink($TEMPFILE);
		return;
	}
			
	$data=@file_get_contents($TEMPFILE);
	$MAIN=unserialize($data);
	@unlink($TEMPFILE);
	
	$CurrentSPAMASSDBArtica=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurrentSPAMASSDBArtica"));
	
	if(!isset($MAIN["SPAMASS_1"])){
		squid_admin_mysql(0, "Unable to get Artica AntiSpam index file (corrupted)", $curl->error);
		return;
	}
	
	
	$SUCCESS=false;
	for($i=0;$i<4;$i++){
		if(!isset($MAIN["SPAMASS_$i"])){
			echo "SPAMASS_$i not indexed!\n";
			continue;
		}
		echo "Checking SPAMASS_$i\n";
		if(!isset($CurrentSPAMASSDBArtica["SPAMASS_$i"]["MD5"])){$CurrentSPAMASSDBArtica["SPAMASS_$i"]["MD5"]=null;}
		if($MAIN["SPAMASS_$i"]["MD5"]==$CurrentSPAMASSDBArtica["SPAMASS_$i"]["MD5"]){
			echo "SPAMASS_$i No updates...\n";
			continue;
		}
		$CountRules=DownloadSpamassFile($mirror,"spamassassin-rules$i.gz");
		if($CountRules==0){continue;}
		$SUCCESS=true;
		squid_admin_mysql(2, "Success get Artica AntiSpam database $i ($CountRules rules)", null,__FILE__,__LINE__);
		$CurrentSPAMASSDBArtica["SPAMASS_$i"]["MD5"]=$MAIN["SPAMASS_$i"]["MD5"];
		$CurrentSPAMASSDBArtica["SPAMASS_$i"]["TIME"]=$MAIN["SPAMASS_$i"]["TIME"];
		$CurrentSPAMASSDBArtica["SPAMASS_$i"]["RULES"]=$CountRules;
		
	
	}
	
	if($SUCCESS){
		$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($CurrentSPAMASSDBArtica), "CurrentSPAMASSDBArtica");
		system("/etc/init.d/mimedefang reload");
	}

	
				
}


function KAM_CF(){
	
	$curl=new ccurl("https://www.pccc.com/downloads/SpamAssassin/contrib/KAM.cf");
	$curl->GetFile("/etc/spamassassin/KAM.cf");
	
	$curl=new ccurl("https://www.pccc.com/downloads/SpamAssassin/contrib/nonKAMrules.cf");
	$curl->GetFile("/etc/spamassassin/nonKAMrules.cf");
	
	
}

function DownloadSpamassFile($mirror,$filename){
	$unix=new unix();
	$cat=$unix->find_program("cat");
	$grep=$unix->find_program("grep");
	$wc=$unix->find_program("wc");
	$curl=new ccurl("$mirror/$filename");
	$TargetFilename="/etc/spamassassin/".str_replace(".gz", ".cf", $filename);
	$TargetFilUnCompress="/etc/spamassassin/".str_replace(".gz", ".tmp", $filename);
	$TEMPFILE=$unix->FILE_TEMP();
	echo "$mirror/$filename ->";
	if(!$curl->GetFile($TEMPFILE)){
		echo " Download error ".$curl->error."\n";
		squid_admin_mysql(0, "Unable to get Artica AntiSpam database1 (download error)", $curl->error,__FILE__,__LINE__);
		@unlink($TEMPFILE);
		return 0;
	}
	if(!$unix->uncompress($TEMPFILE, $TargetFilUnCompress)){
		echo "uncompress error\n";
		@unlink($TEMPFILE);
		@unlink($TargetFilUnCompress);
		squid_admin_mysql(0, "Unable to get Artica AntiSpam database1 (uncompress error)", null,__FILE__,__LINE__);
		return 0;
	}
	
	@unlink($TargetFilename);
	@copy($TargetFilUnCompress, $TargetFilename);
	@unlink($TargetFilUnCompress);
	
	$score=exec("$cat $TargetFilename|$grep score|$wc -l");
	echo "Score: $score\n";
	return $score;
	
}
