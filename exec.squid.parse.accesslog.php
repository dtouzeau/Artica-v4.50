<?php
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


ExplodeFile($argv[1]);

function ExplodeFile($filepath){
	$unix=new unix();
	$LastScannLine=0;
	$GLOBALS["MYSQL_CATZ"]=new mysql_catz();
	$GLOBALS["SQUID_FAMILY_CLASS"]=new squid_familysite();
	if(!isset($GLOBALS["MYHOSTNAME"])){$unix=new unix();$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();}
	$GLOBALS["SEQUENCE"]=md5_file($filepath);

	$handle = @fopen($filepath, "r");
	if (!$handle) {
		echo "Fopen failed on $filepath\n";
		return false;
	}

	$countlines=0;
	
	
	$c=0;
	$d=0;
	$e=0;
	$prc=0;
	$prc_text=0;
	$mysql_first_time=0;

	

	while (!feof($handle)){
		$c++;
		$d++;
		$e++;



		if($countlines>0){
			$prc=$c/$countlines;
			$prc=round($prc*100);
				
				
			if(!isset($GLOBALS["LAST_PRC"])){
				if($GLOBALS["PROGRESS"]){echo "{$prc}%\n";}
				$GLOBALS["LAST_PRC"]=$prc;
			}else{
				if($GLOBALS["LAST_PRC"]<>$prc){
					if($GLOBALS["PROGRESS"]){echo "{$prc}%\n";}
					$GLOBALS["LAST_PRC"]=$prc;
				}
			}
				
				
			if($prc>10){
				if($prc<99){
					if($prc>$prc_text){
						$prc_text=$prc;
					}
				}
			}
		}

		if($d>50){
			$iSeek = ftell($handle);
			@file_put_contents("$filepath.last", $iSeek);
			if($GLOBALS["VERBOSE"]){
				$prc_design=FormatNumber($c)."/".FormatNumber($countlines);
				echo "{$prc}% $prc_design\n";
			}
			$d=0;
		}

		if($e>500){
			$e=0;
		}

		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$array=parseline($buffer);
		if(count($array)==0){continue;}

		if($mysql_first_time==0){
			if(date("Y",$array["TIME"])>2001){
				$mysql_first_time=$array["TIME"];
				
			}
		}
		
		if($array["CATEGORY"]==null){
			
			//$MAIN[$array["SITENAME"]]["COUNT"]++;
			
			//echo date("Y-m-d H:i:s",$array["TIME"]);
			//echo "{$array["SITENAME"]} {$array["SIZE"]} {$array["UID"]} {$array["IPADDR"]} {$array["CATEGORY"]} {$array["FAMILYSITE"]}\n";
		}
		
	}
	
	
	krsort($MAIN);
	while (list ($sitename, $SUBARRAY) = each ($MAIN) ){
		echo "$sitename {$SUBARRAY["COUNT"]}\n";
		
	}
	


}

function parseline($buffer){
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

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}