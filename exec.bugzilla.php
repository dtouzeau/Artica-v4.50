<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SCHEDULE"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--schedule#",implode(" ",$argv))){$GLOBALS["SCHEDULE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.sqlite.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');


if($argv[1]=="--get-user"){get_user();exit;}
if($argv[1]=="--create-bug"){create_bug();exit;}
if($argv[1]=="--get-bugs"){get_bugs();exit;}
if($argv[1]=="--reply"){reply();exit;}
if($argv[1]=="--delete-bug"){delete_bug($argv[2]);exit;}
if($argv[1]=="--support-tool"){support_tool($argv[2]);exit;}


function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/bugzilla.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function get_user(){
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$time=$unix->file_time_min($pidTime);
		if($time<30){
			build_progress(10, "{check_account} failed $time<30");
			exit();}
	}
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
	
	build_progress(10, "{check_account}");
	
	$LicenseInfos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
    if(!isset($LicenseInfos["EMAIL"])){$LicenseInfos["EMAIL"]="";}
	if(!isset($WizardSavedSettings["mail"])){$WizardSavedSettings["mail"]="";}
	if($LicenseInfos["EMAIL"]==""){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}

	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	if($BugzillaAccount==null){$BugzillaAccount=$LicenseInfos["EMAIL"];}
	if($BugzillaAccount==null){
        build_progress(110, "{check_account} {failed} Unable to get account information");
        if($GLOBALS["VERBOSE"]){echo "Failed BugzillaAccount is Null\n";}
        return false;
    }

	build_progress(20, "{check_account} $BugzillaAccount");

	$MAIN=GetUserInfos($BugzillaAccount);
	
			
	if(!$MAIN){
		if($GLOBALS["ERROR_API"]>0){
			build_progress(110, "{check_account} {failed} {error} {$GLOBALS["ERROR_API"]}");
			if($GLOBALS["VERBOSE"]){echo "Failed\n";}
			return false;
		}
	}
		
	if(!isset($MAIN["userid"])){
		build_progress(110, "{check_account} {failed} {error} line ".__LINE__);
		return false;
	}
	
	$real_name=$MAIN["username"];
	$ID=intval($MAIN["userid"]);
	
	if($ID==0){
		build_progress(110, "{check_account} {failed} {error} line ".__LINE__);
		return false;
	}
	
	build_progress(100, "{check_account} {ID}: $ID");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BugzillaID", $ID);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BugzillaName", $real_name);
	build_progress(100, "{check_account} {success}");
	return true;
}

function GetUserInfos($email){
    if(!function_exists("curl_init")){return false;}
	$url="user?names={$email}";
	$data=build_curl($url,null,"GET");
	if(!$data){return false;}
	$json=json_decode($data);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BugzillaChecked", 1);
	if(!property_exists( $json, 'users' )){
		if(!property_exists( $json, 'error' )){
			if($json->error){
				if(!property_exists( $json, 'code' )){$GLOBALS["ERROR_API"]=$json->code;}
				if(!property_exists( $json, 'message' )){echo $json->message;}
			}
			
		}
		
		return false;}
	if($GLOBALS["VERBOSE"]){var_dump($json);}
	$real_name=$json->users[0]->real_name;
	$ID=$json->users[0]->id;
	if($ID==0){return false;}
	$MAIN["username"]=$real_name;
	$MAIN["userid"]=$ID;
	return  $MAIN;
	
	
}

function reply(){
    if(!function_exists("curl_init")){return false;}
	if(!is_file("/etc/artica-postfix/settings/Daemons/BugzillaReply")){return;}
	$MAIN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaReply"));
	@unlink("/etc/artica-postfix/settings/Daemons/BugzillaReply");
	$bugid=$MAIN["bug"];
	$content=$MAIN["CONTENT"];
	//POST /rest/bug/(id)/comment
	
	$array["comment"]=$content;
	$array["is_private"]=false;
	$response=build_curl("bug/$bugid/comment",$array,"POST");
	$json=json_decode($response);
	
	if($GLOBALS["CURLINFO_HTTP_CODE"]<>200){
		echo "CURLINFO_HTTP_CODE !!!! ---> {$GLOBALS["CURLINFO_HTTP_CODE"]}\n";
		$error=null;
		if(property_exists( $json, 'code' )){$GLOBALS["ERROR_API"]=$json->code;}
		if(property_exists( $json, 'message' )){$error=$json->message;}
		squid_admin_mysql(0, "[TICKET]: Unable to post comment to Ticket $bugid Err.{$GLOBALS["ERROR_API"]}", $error,__FILE__,__LINE__);
		get_bugs();
		return;
	}
	echo "ID ---->'" .$json->id."'\n";
	
	$ID=intval($json->id);
	squid_admin_mysql(2, "[TICKET]: Success posting new comment to Ticket $bugid","New Comment #$ID",__FILE__,__LINE__);
	get_comment($bugid);
}


function build_curl($url,$json=null,$PROTO=null){
	$ch = curl_init();
	$BugzillaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaPassword"));
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	$BugzillaApikey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaApikey"));
	
	$GLOBALS["ERROR_API"]=0;
	$CURLOPT_HTTPHEADER[]="Accept: application/json";
	$CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
	$CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
	$CURLOPT_HTTPHEADER[]="Expect:";
	
	if(is_array($json)){
		
		$CURLOPT_HTTPHEADER[]="Content-Type: application/json";
	}

    if($BugzillaApikey==null AND $BugzillaAccount==null){
        echo "Nor API Key defined or Not Account defined, exit program.\n";
        die();
    }
	
	if($BugzillaApikey==null){
	if(strpos(" $url", "?")>0){
		$url="$url&login=$BugzillaAccount&password=$BugzillaPassword";
	}else{
		$url="$url?login=$BugzillaAccount&password=$BugzillaPassword";
	}}else{
		if(strpos(" $url", "?")>0){
			$url="$url&api_key=$BugzillaApikey";
		}else{
			$url="$url?api_key=$BugzillaApikey";
		}
	}
	
	$MAIN_URI="https://bugs.articatech.com/rest.cgi/$url";
	if($GLOBALS["VERBOSE"]){echo "MAIN_URI=$MAIN_URI\n";}
	
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	curl_setopt($ch, CURLOPT_URL, "$MAIN_URI");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

	if($PROTO=="GET"){curl_setopt($ch, CURLOPT_POST, 0);}
	if($PROTO=="POST"){curl_setopt($ch, CURLOPT_POST, 1);}
	if($PROTO=="PUT"){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER,$CURLOPT_HTTPHEADER);
	
	if(is_array($json)){
		$payload = json_encode( $json);
		if($GLOBALS["VERBOSE"]){echo $payload."\n";}
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE);
	
	
	
	$BugzillaProxyName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaProxyName"));
	$BugzillaProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaProxyPort"));
	$BugzillaUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaUseProxy"));
	
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
			if($BugzillaProxyName==null){$BugzillaProxyName="127.0.0.1";}
			if($BugzillaProxyName=="127.0.0.1"){$BugzillaProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
		}
	}
	
	if($BugzillaUseProxy==1){
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($ch, CURLOPT_PROXY,"$BugzillaProxyName:$BugzillaProxyPort");
	}

	if($GLOBALS["VERBOSE"]){echo "$PROTO -> $url\n";}
	$data=curl_exec($ch);
	if($GLOBALS["VERBOSE"]){echo "$data\n";}
	$errno=curl_errno($ch);
    $error_text=curl_error ($ch);
	$CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));

	if($ch){@curl_close($ch);}
	$GLOBALS["CURLINFO_HTTP_CODE"]=$CURLINFO_HTTP_CODE;

	if($errno>0){
		echo "Error Number $errno ( $CURLINFO_HTTP_CODE ) - $error_text\n";
		squid_admin_mysql(1, "[TRACKER]: Error Number $errno ( $CURLINFO_HTTP_CODE ) - $error_text","$errno ( $CURLINFO_HTTP_CODE ) - $error_text",__FILE__,__LINE__);
		return false;
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "CURLINFO_HTTP_CODE=$CURLINFO_HTTP_CODE\n";}
	if($CURLINFO_HTTP_CODE==200){return $data;}
	if($CURLINFO_HTTP_CODE==201){return $GLOBALS["CURLINFO_HTTP_CODE"]=200;return $data;}
	if($CURLINFO_HTTP_CODE==404){return $data;}
	if($CURLINFO_HTTP_CODE==400){return $data;}
	

}

function create_bug(){
	
	
	$DEF=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaCreateBug")));

	if(!isset($DEF["summary"])){
        build_progress(110, "Issue while posting... Summary missing");
        exit;
    }

	build_progress(10, "Initialize {$DEF["component"]} {$DEF["summary"]}");
	$SupportTool=$DEF["SupportTool"];
	
	$array["product"]="Artica";
	$array["component"]=$DEF["component"];
	$array["version"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$array["summary"]=$DEF["summary"];
	$array["priority"]="Normal";
	$array["op_sys"]="Linux";
	$array["rep_platform"]="All";
	$array["platform"]="All";
	
	echo "Initialize {$DEF["component"]} {$DEF["summary"]}\n";
	
	build_progress(50, "Posting ticket");
	$response=build_curl("bug",$array,"POST");
	$json=json_decode($response);
	if($GLOBALS["CURLINFO_HTTP_CODE"]<>200){
		echo "CURLINFO_HTTP_CODE !!!! ---> {$GLOBALS["CURLINFO_HTTP_CODE"]}\n";
		$error=null;
		if(property_exists( $json, 'code' )){$GLOBALS["ERROR_API"]=$json->code;}
		if(property_exists( $json, 'message' )){$error=$json->message;}
		build_progress(110, "{failed} Err.{$GLOBALS["ERROR_API"]} $error");
		return;
	}
	
	echo "ID ---->'" .$json->id."'\n";
	
	$ID=intval($json->id);
	build_progress(50, "Ticket: $ID");
	if($ID==0){
		build_progress(110, "{failed} ID == 0 !");
		return;
	}
	
	$array["comment"]=$DEF["comment"];
	$array["is_private"]=False;
	$array["is_markdown"]=False;
	build_progress(55, "Ticket: $ID, Posting content");
	$response=build_curl("bug/$ID/comment",$array,"POST");
	$json=json_decode($response);
	
	if($GLOBALS["CURLINFO_HTTP_CODE"]<>200){
		$error=null;
		if(property_exists( $json, 'code' )){$GLOBALS["ERROR_API"]=$json->code;}
		if(property_exists( $json, 'message' )){$error=$json->message;}
		build_progress(110, "{failed} Err.{$GLOBALS["ERROR_API"]} $error");
		return;
	}
	
	@unlink("/etc/artica-postfix/settings/Daemons/BugzillaCreateBug");
	get_bugs();
	
	if($SupportTool==1){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup $php ".__FILE__." --support-tool $ID >/dev/null 2>&1 &");
	}
	build_progress(100, "{success}");
	
}

function get_bugs(){
	
	if($GLOBALS["SCHEDULE"]){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/exec.bugzilla.php.get_bugs.pid";
		$pidTime="/etc/artica-postfix/pids/exec.bugzilla.php.get_bugs.time";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){exit();}
		$pidTimeEx=$unix->file_time_min($pidTime);
		if($pidTimeEx<60){exit();}
	}
	
	
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	build_progress(60, "Fill bugs list for $BugzillaAccount..");
	$response=build_curl("bug?creator=$BugzillaAccount&product=Artica",null,"GET");
	$json=json_decode($response);
	
	@mkdir("/home/artica/SQLITE",0755,true);
	if(is_file("/home/artica/SQLITE/bugzilla.db")){@unlink("/home/artica/SQLITE/bugzilla.db");}
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	@chmod("/home/artica/SQLITE/bugzilla.db", 0644);
	@chown("/home/artica/SQLITE/bugzilla.db", "www-data");
	@chown("/home/artica/SQLITE", "www-data");
	@chmod("/home/artica/SQLITE", 0755);
	
	$sql="CREATE TABLE `bugs` (
				`id` INTEGER PRIMARY KEY,
				`status` text,
				`summary` text,
				`priority` text,
				`severity` text,
				`component` text,
				`creation_time` int,
				`last_change_time` int,
				`version` text)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	$sql="CREATE TABLE `discuss` (
				`bugid` INTEGER,
				`id` INTEGER PRIMARY KEY,
				`creator` text,
				`time` int,
				`content` text,
				`attachment_id` int)";
	$q->QUERY_SQL($sql);
	
	
	foreach ($json->bugs as $index=>$class){
		
		$status=$class->status;
		$id=$class->id;
		$priority=$class->priority;
		$severity=$class->severity;
		$summary=str_replace("'", "''", $class->summary);
		$component=$class->component;
		$creation_time=$class->creation_time;
		$last_change_time=$class->last_change_time;
		$last_change_time_int=strtotime($last_change_time);
		$creation_time_int=strtotime($creation_time);
		$version=$class->version;
		$f[]="('$id','$status','$summary','$priority','$severity','$component','$creation_time_int','$last_change_time_int','$version')";
		build_progress(65, "$id: $summary");
		get_comment($id);
		
		
		
		
	}
	
	build_progress(70, count($f)." {tickets}");
	
	if(count($f)==0){
		if(is_file("/home/artica/SQLITE/bugzilla.db")){@unlink("/home/artica/SQLITE/bugzilla.db");}
	}
	
	if(count($f)>0){
		$sql="INSERT OR IGNORE INTO bugs (`id`,`status`,`summary`,`priority`,`severity`,`component`,`creation_time`,`last_change_time`,`version`) VALUES ". @implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			build_progress(110, "MySQL Error");
			echo $q->mysql_error."\n";
			exit();
		}
	}
	build_progress(100, "{success}");
	
}

function delete_bug($id){

    if(!function_exists("curl_init")){
        build_progress(110, "{failed} $id");
        return false;
    }
	build_progress(40, "{remove} $id");
	$array["id_or_alias"]=$id;
	$array["product"]="Trash";
	$array["component"]="Trash";
	$array["version"]="unspecified";
	
	$response=json_decode(build_curl("bug/$id",$array,"PUT"));
	if($GLOBALS["CURLINFO_HTTP_CODE"]<>200){
		build_progress(40, "{failed} $id");
		return;
	}
	
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$q->QUERY_SQL("DELETE FROM bugs WHERE id=$id");
	$q->QUERY_SQL("DELETE FROM discuss WHERE bugid=$id");
	build_progress(100, "{success} $id");
	
}


function get_comment($bugid){
    if(!function_exists("curl_init")){return false;}
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$q->QUERY_SQL("DELETE FROM discuss WHERE bugid='$bugid'");
	$response=build_curl("bug/$bugid/comment",null,"GET");
	$json=json_decode($response);
	
	build_progress(65, "{history} $bugid");
	
	if($GLOBALS["CURLINFO_HTTP_CODE"]<>200){return;}
	
	foreach ($json->bugs->$bugid->comments as $index=>$class){
		$creator=$class->creator;
		$time=strtotime($class->time);
		$text=str_replace("'", "''", $class->text);
		$id=$class->id;
		$attachment_id=intval($class->attachment_id);
		echo "$id) Attach by $creator $time TEXT size: ".strlen($text)." bytes\n";
		$sql="INSERT OR IGNORE INTO discuss (`bugid`,`id`,`creator`,`time`,`content`,`attachment_id`) VALUES ('$bugid','$id','$creator','$time','$text','$attachment_id')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "FATAL !!!! --> $q->mysql_error\n$sql\n";}

	}



//var_dump($class);
}


function support_tool($bugid){
    if(!function_exists("curl_init")){return false;}
	$i=intval(@file_get_contents("/etc/artica-postfix/support-tool-prc"));
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	progress_support_tool("Ticket ID $bugid",5);
    system("/usr/sbin/artica-phpfpm-service -support-tool");
	
	if(!is_file("/usr/share/artica-postfix/ressources/support/support.tar.gz")){
		progress("{failed}",110);
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
		return;
	}
	
	$array["data"]=base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/support/support.tar.gz"));
	$array["file_name"]=$unix->hostname_g().".".time().".tar.gz";
	$array["summary"]="Generated support Tool";
	$array["comment"]="auto-generated support package for {$unix->hostname_g()}";
	$array["content_type"]="application/x-tar";
	$array["is_patch"]=False;
	
	$size=InternalFormatBytes(strlen($array["data"])/1024);
	progress_support_tool("{uploading} $size...",90);
	$response=build_curl("bug/$bugid/attachment",$array,"POST");
	$json=json_decode($response);
	progress_support_tool("{uploading} {done}...",91);
	
	if($GLOBALS["CURLINFO_HTTP_CODE"]<>200){
		if(property_exists( $json, 'code' )){$GLOBALS["ERROR_API"]=$json->code;}
		if(property_exists( $json, 'message' )){echo $json->message."\n";}
		progress_support_tool(110, "{failed} Error {$GLOBALS["ERROR_API"]}");
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
		return;
	}
	progress_support_tool("{synchronize}...",92);
	get_comment($bugid);
	progress_support_tool("{success}...",100);

}

function progress_support_tool($title,$perc){
	echo "$title,$perc\n";
	$echotext=$title;
	echo "Starting......: ".date("H:i:s")." {$perc}% $title\n";
	$cachefile=PROGRESS_DIR."/squid.debug.support-tool.progress";
	$array["POURC"]=$perc;
	$array["TEXT"]=$title;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function InternalFormatBytes($kbytes,$nohtml=false){

    $spacer="&nbsp;";
    if($nohtml){$spacer="";}

    if($kbytes>1048576){
        $value=round($kbytes/1048576, 2);
        if($value>1000){
            $value=round($value/1000, 2);
            return "$value{$spacer}TB";
        }
        return "$value{$spacer}GB";
    }
    elseif ($kbytes>=1024){
        $value=round($kbytes/1024, 2);
        return "$value{$spacer}MB";
    }
    else{
        $value=round($kbytes, 2);
        return "$value{$spacer}KB";
    }
}




?>