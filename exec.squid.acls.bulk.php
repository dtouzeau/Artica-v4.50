<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


xstart();

function xstart(){
	
	$unix=new unix();

	if($unix->ServerRunSince()<3){
		echo "Server running less than 3mn, please try later\n";
		exit();
	}

    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        echo "Already Artica task running PID $pid since {$time}mn\n";
        return false;
    }
    @file_put_contents($pidfile, getmypid());

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,GroupName,bulkimport,bulkmd5,length(bulkimport) as bulk FROM webfilters_sqgroups WHERE enabled=1 AND bulk>6");

    if(count($results)>0){
        if(is_file("/etc/cron.d/squid-acls-bulk")){
            @unlink("/etc/cron.d/squid-acls-bulk");
            UNIX_RESTART_CRON();
            die();
        }
    }
    $RELOAD=false;
    foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $TMPFILE=$unix->FILE_TEMP();
            $GroupName=$ligne["GroupName"];
            $target_filename="/etc/squid3/acls/container_$ID.txt";
            $target_filename_temp="/etc/squid3/acls/container_$ID.txt.tmp";
            $url=$ligne["bulkimport"];
            $bulkmd5=$ligne["bulkmd5"];
            echo "$target_filename $url $bulkmd5\n";
            $curl=new ccurl($url);
            if(!$curl->GetFile($TMPFILE)){
                echo "Unable to download $url $curl->error\n";
                squid_admin_mysql(0,"Unable to download source form object Acl $GroupName",
                    $curl->error."\n$url",__FILE__,__LINE__);
                continue;
            }
            $md5=md5_file($TMPFILE);
            echo "$TMPFILE = $md5\n";
            if($md5==$bulkmd5){
                @unlink($TMPFILE);
                continue;
            }
            if(is_file($target_filename_temp)){@unlink($target_filename_temp);}
            @copy($target_filename,$target_filename_temp);

            if(!dstdomain_parse($TMPFILE,$target_filename)){
                @unlink($TMPFILE);
                squid_admin_mysql(0,"Unable to parse source form object Acl $GroupName",
                   $url,__FILE__,__LINE__);
                continue;
            }
            @unlink($TMPFILE);
            if(!Test_config()){
                echo @implode("\n",$GLOBALS["SQUIDERR"]);
                $GLOBALS["SQUIDERR"][]="URL:$url";
                squid_admin_mysql(0,"Unable to compile object Acl $GroupName (bungled)",
                    @implode("\n",$GLOBALS["SQUIDERR"]),__FILE__,__LINE__);
                @unlink($TMPFILE);
                @unlink($target_filename);
                @copy($target_filename_temp,$target_filename);
                continue;
            }
            $RELOAD=true;
            squid_admin_mysql(2,"{success} {compiling} ACL object $GroupName with {$GLOBALS["COUNTACL"]} items");
            $q->QUERY_SQL("UPDATE webfilters_sqgroups SET bulkmd5='$md5' WHERE ID=$ID");

    }
    $unix->Popuplate_cron_make("squid-acls-bulk","35 */3 * * *",basename(__FILE__));
    if($RELOAD) {
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
    return true;
}

function dstdomain_parse($srcfile,$dstfile){
    $GLOBALS["COUNTACL"]=0;
    $handle_src = @fopen($srcfile, "r");
    if (!$handle_src) {echo "Failed to open $srcfile\n";return false;}

    $handle_dst = @fopen($dstfile, "w");
    if (!$handle_src) {
        echo "Failed to open $dstfile\n";
        fclose($handle_src);
        return false;
    }


    while (!feof($handle_src)){
        $c++;
        $www =strtolower(trim(fgets($handle_src, 4096)));
        if($www==null){$CBADNULL++;continue;}

        if(substr($www,0,1)=="#"){
          continue;
        }
        if(preg_match("#domain$#",$www)){continue;}
        if(preg_match("#^[0-9\.]+\s+(.+)#",$www,$re)){
            $www=$re[1];
        }
        if(preg_match("#^[0-9\.]+$#",$www,$re)){continue;}
        if(stripos($www,".")==0){continue;}
        $GLOBALS["COUNTACL"]++;
        @fwrite($handle_dst,"$www\n");
    }
    return true;

}

function Test_config(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
    $GLOBALS["SQUIDERR"]=array();

	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
            $GLOBALS["SQUIDERR"][]=$ligne;
			echo "`$ligne`, aborting configuration\n";
			return false;
		}
	
		if(preg_match("#Segmentation fault#", $ligne)){
            $GLOBALS["SQUIDERR"][]="Segmentation fault";
			echo "`$ligne`, aborting configuration\n";
			return false;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$GLOBALS["SQUIDERR"][]="[line:$lineNumber]: {$tt[$i]}\n";
				}
			}

			return false;
		}
	
	}

	return true;
	
}