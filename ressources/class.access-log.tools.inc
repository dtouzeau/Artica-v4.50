<?php
include_once(dirname(__FILE__)."/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/class.squid.familysites.inc");

function parseAccessLine($buffer){
	
	if(!isset($GLOBALS["MYSQL_CATZ"])){$GLOBALS["MYSQL_CATZ"]=new mysql_catz();}
	if(!isset($GLOBALS["SQUID_FAMILY_CLASS"])){$GLOBALS["SQUID_FAMILY_CLASS"]=new squid_familysite();}
	
	$logfile_daemon=new logfile_daemon();
	$return=array();
	$ipaddr=null;

	if(strpos($buffer," TAG_NONE/")>0){return;}
	if(strpos($buffer," TAG_NONE_ABORTED/000")>0){return;}
	if(strpos($buffer," TCP_REDIRECT_TIMEDOUT/")>0){return;}
	if(strpos($buffer," TCP_TUNNEL/200 0 CONNECT")>0){return;}
	if(strpos($buffer," TCP_MISS_ABORTED/000")>0){return;}
	if(strpos($buffer," TAG_NONE_TIMEDOUT/200 0")>0){return;}
	if(preg_match("#^([0-9\.]+)\s+([0-9\-]+)\s+(.*?)\s+([A-Z_]+)\/([0-9]+)\s+([0-9]+)\s+([A-Z_]+)\s+(.*?)\s+(.*?)\s+([A-Z_]+)\/(.*?)\s+#is", $buffer,$re)){
			
		$cached=0;
			
		$time=round($re[1]);
		$hostname=$re[3];
		$SquidCode=$re[4];
		$code_error=$re[5];
		$size=$re[6];
		$proto=$re[7];
		$uri=$re[8];
		$uid=$re[9];
		$basenameECT=$re[10];
		$remote_ip=$re[11];
		if($hostname=="127.0.0.1"){return array();}


		if(intval($size)==0){
			if($GLOBALS["VERBOSE"]){
				echo "$buffer SIZE=0;\n";
				print_r($re);
			}
				
		}

		if(trim($uid)=="-"){$uid=null;}
		if(preg_match("#^[0-9\.]+$#", $hostname)){$ipaddr=$hostname;$hostname=null;}

		if(preg_match("#^(.+?)\\\\(.+)#", $uid,$ri)){$uid=$ri[2];}
		$cached=$logfile_daemon->CACHEDORNOT($SquidCode);

		$arrayURI=parse_url($uri);
		$sitename=$arrayURI["host"];
		if(strpos($sitename, ":")){
			$xtr=explode(":",$sitename);
			$sitename=$xtr[0];
			if(preg_match("#^www\.(.+)#", $sitename,$rz)){$sitename=$rz[1];}
		}

		$category=$GLOBALS["MYSQL_CATZ"]->GET_CATEGORIES($sitename);
		$familysite=$GLOBALS["SQUID_FAMILY_CLASS"]->GetFamilySites($sitename);


		if(is_numeric($familysite)){
			if($GLOBALS["VERBOSE"]){echo "familysite = $familysite ??? numeric ??? ".__LINE__."\n";}
		}



		$return=array(
				"TIME"=>$time,
				"IPADDR"=>$ipaddr,
				"CACHED"=>$cached,
				"UID"=>$uid,
				"HOSTNAME"=>$hostname,
				"SITENAME"=>$sitename,
				"FAMILYSITE"=>$familysite,
				"CATEGORY"=>$category,
				"ERRCODE"=>$code_error,
				"SIZE"=>$size,
				"PROTO"=>$proto,
				"URI"=>$uri,
				"REMOTE"=>$remote_ip,


		);
		return $return;

	}

	if($GLOBALS["VERBOSE"]){echo "NO MATCH\n$buffer\n";}

}