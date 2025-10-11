<?php
$GLOBALS["VERBOSE"]=false;
if($_GET["verbose"]){$GLOBALS["VERBOSE"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$REQUEST_URI=$_SERVER["REQUEST_URI"];
$GLOBALS["HyperCacheStoragePath"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoragePath"));
if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}

if($GLOBALS["VERBOSE"]){echo "<li>$REQUEST_URI</li>";}

if(!preg_match("#\/(.+?)\.#", $REQUEST_URI,$re)){
	if($GLOBALS["VERBOSE"]){echo "<li>$REQUEST_URI no match <code>\/(.+?)\.</code></li>";}
	if(!$GLOBALS["VERBOSE"]){header("HTTP/1.0 404 Not Found");}
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if($GLOBALS["VERBOSE"]){echo "<li>MD5: {$re[1]}; storage in {$GLOBALS["HyperCacheStoragePath"]}</li>";}

	find_md5($re[1]);




function find_md5($filemd5){
	
	
	$array=HyperCacheMD5File_get($filemd5);
	
	
	if(!is_array($array)){
		if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." $filemd5  No such array</li>";}
		if(!$GLOBALS["VERBOSE"]){header("HTTP/1.0 404 Not Found");}
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	
	$filepath=$array["FILEPATH"];
	$basename=$array["FILENAME"];
	$filesize=$array["FILESIZE"];
	$content_type=$array["FILETYPE"];
	$FullPath="{$GLOBALS["HyperCacheStoragePath"]}/$filepath";
	
	
	if(!is_file($FullPath)){
		if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." $FullPath  No such file</li>";}
		header("HTTP/1.0 404 Not Found");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	
	
	
	$filepath=$array["FILEPATH"];
	$basename=$array["FILENAME"];
	$filesize=$array["FILESIZE"];
	$content_type=$array["FILETYPE"];
	$time=$array["time"];
	
	$content_type_NO["text/html"]=true;
	
	if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." FILEPATH:$filepath</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." FILENAME:$basename</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." FILESIZE:$filesize</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." FILETYPE:$content_type</li>";}
	
	
	if($GLOBALS["VERBOSE"]){die("DIE " .__FILE__." Line: ".__LINE__);}
	header("Content-type: $content_type");
	
	if(!isset($content_type_NO[$content_type])){
		header('Content-Transfer-Encoding: binary');
		header("Content-Disposition: attachment; filename=\"$basename\"");
	}
	header("Pragma: cache");
	header("Cache-Control: public");
	header("User-Cache-Control: public");
	header("Expires: Sat, 26 Jul 2100 05:00:00 GMT"); 
	header("Content-Length: ".$filesize);
	ob_clean();
	flush();
	readfile("{$GLOBALS["HyperCacheStoragePath"]}/$filepath");
	
	
	
}
function tool_create_berekley($dbfile){
	if(is_file($dbfile)){return true;}
	try {
		events("tool_create_berekley:: Creating $dbfile database");
		$db_desttmp = @dba_open($dbfile, "c","db4");
		if(!$db_desttmp){ events("tool_create_berekley::FATAL Error on $dbfile");}
	}
	catch (Exception $e) {$error=$e->getMessage(); events("tool_create_berekley::FATAL ERROR $error on $dbfile");}
	@dba_close($db_desttmp);
	if(is_file($dbfile)){return true;}
	return false;
}


function HyperCacheSizeLog($size){
	$dbfile="/usr/share/squid3/".date("Ymd")."_HyperCacheSizeLog.db";
	tool_create_berekley($dbfile);
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){
		events("UserAuthDB:: FATAL!!!::{$GLOBALS["UserAuthDB_path"]}, unable to open");
		return false;
	}
	
	$keymd5=date("Y-m-d H:00:00");
	
	
	if(!@dba_exists($keymd5,$db_con)){
		$array["HITS"]=1;
		$array["SIZE"]=$size;
		@dba_replace($keymd5,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	
	$CURRENT=intval(dba_fetch($keymd5,$db_con));
	$array=unserialize($CURRENT);
	$array["HITS"]=$array["HITS"]+1;
	$array["SIZE"]=$array["SIZE"]+$size;
	@dba_replace($keymd5,serialize($array),$db_con);
	@dba_close($db_con);
}


function headers_for_page_cache($cache_length=600){
	$cache_expire_date = gmdate("D, d M Y H:i:s", time() + $cache_length);
	header("Expires: $cache_expire_date");
	header("Pragma: cache");
	header("Cache-Control: max-age=$cache_length");
	header("User-Cache-Control: max-age=$cache_length");
}

function find_md5_loop($filemd5){
	$dbfile="{$GLOBALS["HyperCacheStoragePath"]}/cache.db";
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	$mainkey=dba_firstkey($db_con);
	
	while($mainkey !=false){
		$array=unserialize(dba_fetch($mainkey,$db_con));
		$keymd5=$array["md5file"];
		if($keymd5==$filemd5){
			$uri=$mainkey;
			@dba_close($db_con);
			return $uri;
		}
		$mainkey=dba_nextkey($db_con);
		
	}
	
	@dba_close($db_con);
	
}

function HyperCacheMD5File_get($md5){
	$dbfile="/usr/share/squid3/HyperCacheMD5.db";
	if(!is_file($dbfile)){
		if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." $dbfile no such file</li>";}
		return;}
	$db_con = dba_open($dbfile, "r","db4");
	if(!$db_con){return;}
	
	if(!@dba_exists($md5,$db_con)){
		if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." $md5 no such entry</li>";}
		@dba_close($db_con);
		return null;
	}
	
	$fetch_content=@dba_fetch($md5,$db_con);$array=@unserialize($fetch_content);
	$FILEPATH=$array["FILEPATH"];
	$FILENAME=$array["FILENAME"];
	
	if($GLOBALS["VERBOSE"]){echo "<li>".__LINE__." $md5  PATH = $FILEPATH / $FILENAME</li>";}
	@dba_close($db_con);
	
	return $array;
	
}

function events($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/HyperCacheWeb.log";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[".basename(__FILE__)."/{$GLOBALS["UFDBVERS"]} $pid $text\n");
	@fclose($f);
}