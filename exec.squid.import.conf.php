<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
$GLOBALS["MYCOMMANDS"]=implode(" ",$argv);

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--nocaches#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;}
if(preg_match("#--noapply#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;$GLOBALS["NOAPPLY"]=true;$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}





if($argv[1]=="--import"){import($argv[2]);exit();}
if($argv[1]=="--zip"){import_zip();exit;}



function build_progress($text,$pourc){
	$PROGRESS_FILE="/usr/share/artica-postfix/ressources/logs/squid.import.progress";
	$LOG_FILE=PROGRESS_DIR."/squid.import.progress.txt";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($PROGRESS_FILE, serialize($array));
	@chmod($PROGRESS_FILE,0755);

}

function import_zip(){
	$zipfile="/usr/share/artica-postfix/ressources/conf/upload/squid-zip-import.zip";
	if(!is_file($zipfile)){
		echo "squid-zip-import.zip no such file\n";
		build_progress("squid-zip-import.zip no such file",110);
		exit();
	}
	$unix=new unix();
	$unzip=$unix->find_program("unzip");
	$rm=$unix->find_program("rm");
	if(!is_file($unzip)){
		echo "unzip no such binary\n";
		build_progress("unzip no such binary",110);
		@unlink($zipfile);
		exit();
	}
	
	echo "Uncompress\n";
	build_progress("{uncompress}",10);
	$TMP_DIR=$unix->TEMP_DIR()."/".time();
	@mkdir($TMP_DIR,0755,true);
	system("$unzip -o $zipfile -d $TMP_DIR");
	@unlink($zipfile);
	if(!is_file("$TMP_DIR/squid.conf")){
		build_progress("squid.conf no such file",110);
		shell_exec("$rm -rf $TMP_DIR");
		
	}
	
	build_progress("Importing...",20);
	import("$TMP_DIR/squid.conf");
	shell_exec("$rm -rf $TMP_DIR");
	
}


function import($filepath){
	$squid=new squidbee();
	$sock=new sockets();
	if(!is_file($filepath)){echo "$filepath no such file\n";}
	$GLOBALS["FILEPATH"]=$filepath;
	clean($filepath);
	echo "$filepath cleaned...\n";
	$f=explode("\n",@file_get_contents($filepath));
	foreach ($f as $index=>$line){
		build_progress("$line",40);
		if(!preg_match("#^acl\s+#", $line)){continue;}
		import_acl($line,$filepath);
	}
	
	reset($f);
	$c=0;
	$FIRST_PORT=false;
	foreach ($f as $index=>$line){
		$line=trim($line);
		if(trim($line)=="http_access deny all"){continue;}
		if(!preg_match("#^(http_access|http_reply_access)\s+#", $line)){continue;}
		$c++;
		build_progress("$line",50);
		import_http_access($line,$c);
	}	
	
	$squid->SaveToLdap(true);
	
}

function clean($filepath){
	
	$f=explode("\n",@file_get_contents($filepath));
	foreach ($f as $index=>$line){
		if(trim($line)==null){continue;}
		if(preg_match("#^\##", $line)){continue;}
		$t[]=$line;	
		
		
	}
	@file_put_contents($filepath, @implode("\n", $t));
	
}

function external_acl_find($aclname){
	$filepath=$GLOBALS["FILEPATH"];
	$f=explode("\n",@file_get_contents($filepath));
	foreach ($f as $index=>$line){
		if(!preg_match("#^acl\s+$aclname\s+external\s+.*?\s+(.+)#", $line,$re)){continue;}
		return trim($re[1]);
	}
	
	
	
}

function import_acl_file($path){
	$path=str_replace('"', '', $path);
	$t=array();
	$size=@filesize($path);
	echo "Analyze line: `$path` = $size bytes [".__LINE__."]\n";
	
	$f=explode("\n",@file_get_contents($path));
	foreach ($f as $index=>$line){
		$line=trim($line);
		if(trim($line)==null){continue;}
		if(preg_match("#^\##", $line)){continue;}
		if(substr($line, 0,1)=="."){$line=substr($line, 1,strlen($line));}
		$t[$line]=$line;
	
	
	}
	return $t;
	
}

function import_acl($line,$filepath=null){
	if(!preg_match("#^acl\s+(.+?)\s+(.+?)\s(.+)#", $line,$re)){echo "FAILED: `$line`\n";return;}
	$items=array();
	$objectname=trim($re[1]);
	$objectType=trim(strtolower($re[2]));
	$objectvalues=trim($re[3]);
	if(preg_match("#proxy_auth REQUIRED#is", $line)){
		echo "Skipping line $line\n";
		return;
	}
	
	echo "Analyze line: Object name: $objectname\n";
	echo "Analyze line: Object Type: $objectType\n";
	echo "Analyze line: Object values: $objectvalues\n";
	
	if($filepath<>null){
		if(!preg_match("#[0-9\.]+\/[0-9\.]+#", $objectvalues)){
			if(strpos(" $objectvalues", "/")>0){
				$DIR=dirname($filepath);
				$basename=basename($objectvalues);
				echo "Analyze line: read content in $DIR/$basename [".__LINE__."]\n";
				$items=import_acl_file("$DIR/$basename");
				if(count($items)>0){
					echo "Analyze line: ".count($items)." in $DIR/$basename [".__LINE__."]\n";
					$objectvalues=null;
				}else{
					echo "Analyze line: -ERR- $DIR/$basename no such data [".__LINE__."]\n";
				}
			}
		}
	}
	
	if(strtolower($objectname)=="all"){return;}
	
	if(preg_match("#acl\s+(.+?)\s+external\s+(.+?)\s+(.+)#", $line,$re)){
		$objectname=$re[1];
		$objectType="external";
		$items[0]=$re[2];
		$items[1]=$re[3];
	}
	
	
	$external_index_ad["nt_group"]=true;
	$external_index_ad["proxy_auth_ads"]=true;
	
	if(count($items)==0){
		$arrayVals=explode(" ",$objectvalues);
		while (list ($index, $val) = each ($arrayVals)){
			$val=trim($val);
			if($val==null){continue;}
			if(trim($val)=="-i"){continue;}
			if(preg_match('#"(.+?)\/#', $val)){continue;}
			if(substr($val, 0,1)=="."){$val=substr($val, 1,strlen($val));}
			$items[$val]=$val;
			
		}
	}
	
	
	$LogSupp=null;
	$asAD=false;
	if($objectType=="external"){
		if(isset($external_index_ad[$items[0]])){
			$objectType="proxy_auth_ads";
			$LogSupp=" - {$items[1]}";
			$objectname=$items[1];
			unset($items);
			
		}
		
	}
	if(!isset($items)){$items=array();}
	echo "Checking Proxy object name \"$objectname\" with \"$objectType\" type and ". count($items) ." item(s)$LogSupp ";
	import_acl_sql($objectname,$objectType,$items,$asAD);
	
}


function import_acl_sql($GroupName,$GroupType,$items,$AsAd=false){
	
	if($GroupType=="proxy_auth_ads"){
		$items=array();
	}
	
	$q=new mysql_squid_builder();
	
	if($GroupName==null) {
		echo "-ERR- unable to understand '$GroupType' with a null GroupName\n";
		return;
		
	}
	
	if(!isset($q->acl_GroupType[$GroupType])){
		echo "-ERR- unable to understand `$GroupName` Type:`$GroupType` /{$items[0]}/{$items[1]}/ please contact Artica support\n";
		return;
	}
	
	$sql="SELECT ID FROM webfilters_sqgroups WHERE GroupName='$GroupName' AND GroupType='$GroupType'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($ligne["ID"]>0){
		echo " Already Imported as ID {$ligne["ID"]}\n";
		import_acl_sql_items($ligne["ID"],$items);
		return $ligne["ID"];
	}
	
	
	$sql="SELECT ID,GroupName,GroupType,params FROM webfilters_sqgroups WHERE enabled=1";
	$sql="INSERT OR IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled) VALUES ('$GroupName','$GroupType','1')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo " -ERR- !!\n$q->mysql_error\n";return;}
	echo "Imported as ID $q->last_id\n";
	import_acl_sql_items($q->last_id,$items);
	
}

function import_acl_sql_items($gpid,$items){
	if(count($items)==0){return;}
	$q=new mysql_squid_builder();
	$c=0;
	while (list ($index, $val) = each ($items)){
		$pattern=mysql_escape_string2($val);
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND pattern='$pattern'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(trim($ligne["pattern"])<>null){continue;}
		$sql="INSERT OR IGNORE INTO webfilters_sqitems (pattern,gpid,enabled) VALUES ('$pattern','$gpid','1')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo " $pattern -> -ERR- $gpid !!\n$q->mysql_error\n";continue;}
		$c++;
		
	}
	if($c>0){echo "Group ID $gpid, $c imported items\n";}
}

function import_http_access($line,$xORDER){
	if(!preg_match("#(http_access|http_reply_access)\s+(allow|deny)\s+(.+)#", $line,$re)){
		echo "`$line` -ERR- unable to understand this rule\n";
		return;
	}
	
	$PortDirectionS["proxy_auth_ads"]=1;
	
	$q=new mysql_squid_builder();
	$PortDirection=0;
	$re[2]=trim($re[2]);
	$re[3]=trim($re[3]);
	$GroupsX=explode(" ",$re[3]);
	$GPS=array();
	while (list ($index, $gptmp) = each ($GroupsX)){
		$gptmp=trim($gptmp);
		$gpName=null;
		$negation=false;
		$Alternate=null;
		if($gptmp==null){continue;}
		if(substr($gptmp,0,1)=="!"){$gptmp=substr($gptmp, 1,strlen($gptmp));$negation=true;}
		if($gptmp=="all"){continue;}
		
		$sql="SELECT ID,GroupType FROM webfilters_sqgroups WHERE GroupName='$gptmp'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if($ligne["ID"]==0){
			$Alternate=external_acl_find($gptmp);
			if($Alternate<>null){
				$gptmp=$Alternate;
				$sql="SELECT ID,GroupType FROM webfilters_sqgroups WHERE GroupName='$Alternate'";
				$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			}
		}
		if($ligne["ID"]==0){
			echo " -ERR- Unable to find group id from `$gptmp`\n";
			continue;
		}
		if(isset($PortDirectionS[$ligne["GroupType"]])){
			$PortDirection=$PortDirectionS[$ligne["GroupType"]];
		}
		
		$GroupLogs[]=" $gptmp id:{$ligne["ID"]}";
		$Groups[$ligne["ID"]]=$negation;
		if($negation){$gpName="not ";}
		$gpName=$gpName.$gptmp;
		$GPS[]=$gpName;
	}
	
	
	if(count($GPS)==0){
		echo "`$line` -ERR- no associated groups\n";
		return;
	}
	$DenyAllow=$re[2];
	$aclType=trim($re[1]);
	$aclname2=trim(@implode(" ", $GPS));
	$aclname="$DenyAllow $aclname2";
	
	$TRANS["http_access"]["deny"]="access_deny";
	$TRANS["http_access"]["allow"]="access_allow";
	$TRANS["http_reply_access"]["deny"]="http_reply_access_deny";
	$TRANS["http_reply_access"]["allow"]="http_reply_access_allow";
	
	$acl_type=$TRANS[$aclType][$DenyAllow];
	if($acl_type==null){
		echo " $aclname -> -ERR- Unable to understand $aclType/$DenyAllow\n";
		return;
	}
	
	
	echo "Acl Name `$aclname`";
	$sql="SELECT ID FROM webfilters_sqacls WHERE aclname='$aclname'";
	//if(isset($_POST["PortDirection"])){$PortDirection=",`PortDirection`='{$_POST["PortDirection"]}'";}
	
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($ligne["ID"]>0){
			$aclid=$ligne["ID"];
			$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER='$xORDER',PortDirection=$PortDirection WHERE ID='$aclid'");
			echo " $aclid (edited) [".@implode(" ", $GroupLogs)."]";
	}else{
		$sql="INSERT INTO webfilters_sqacls (aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid,PortDirection) VALUES ('$aclname',1,'','$xORDER','0','0','0','$PortDirection')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo " $aclname -> -ERR- !!\n$q->mysql_error\n";return;}
		$aclid=$q->last_id;
		echo " ID:$aclid (added) [".@implode(" ", $GroupLogs)."]";
	}
	$acl=new squid_acls_groups();
	if(!$acl->aclrule_edittype($aclid,$acl_type,1)){
		echo " $aclname -> aclrule_edittype -> -ERR- !!\n$q->mysql_error\n";
		return;
	}
	
	
	$c=0;
	while (list ($gpid, $negation) = each ($Groups)){
		$xnegation=0;
		$md5=md5($aclid.$gpid);
		if($negation){$xnegation=1;}
		$sql="INSERT OR IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid,negation) VALUES('$md5','$aclid','$gpid','$xnegation')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo " -ERR- Group:$gpid on rule $aclid Line:".__LINE__." $q->mysql_error\n";continue;}
		$c++;
	}
	
	
	echo " Linked to $c Group(s) Done..\n";
	
}
