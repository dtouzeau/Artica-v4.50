<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["NO_BUILD_MAIN"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
$GLOBALS["debug"]=true;

if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv),$re)){$GLOBALS["NO_RELOAD"]=true;}
if(preg_match("#--no-buildmain#",implode(" ",$argv),$re)){$GLOBALS["NO_BUILD_MAIN"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="--exec"){execute_autconfig();exit;}
execute_autconfig();

function build_progress($text,$pourc):bool{
	$filename=basename(__FILE__);
    _out($text);
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid-autoconf.progress";
	echo "[{$pourc}%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}
    return true;

}
function _out($text):bool{
    $LOG_SEV = LOG_INFO;
    echo $text."\n";
    if (!function_exists("openlog")) {return false;}
    openlog("proxy-pac", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, "[AUTOCONF]: $text");
    closelog();
    return true;
}
function execute_autconfig($not100=false):bool{
	$unix=new unix();
	build_progress("Execute....",5);
	build_progress("Loading settings....",5);

	$hostname=$unix->hostname_g();
	$dd=explode(".",$hostname);
	unset($dd[0]);
	$DOMAIN=@implode(".", $dd);
    $fnets[]="192.168.0.0/16";
    $fnets[]="10.0.0.0/8";
    $fnets[]="127.0.0.0/8";
    $fnets[]="172.16.0.0/12";
	$LOCALNET=@implode(",",$fnets);
    $NETS="";

	$port=0;
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT nic,port  FROM `proxy_ports` WHERE enabled=1 AND transparent=0 AND TProxy=0 AND is_nat=0 AND WCCP=0";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$nic=$ligne["nic"];
		$port=intval($ligne["port"]);
		if($nic<>null){$nics=new system_nic($nic);$NETS=$nics->IPADDR;}
		
		
	}
	
	if($port==0){$port=3128;}
	$PROXY=$NETS;
	$PORT=$port;
	$php=$unix->LOCATE_PHP5_BIN();
	if($PROXY==null){$PROXY=$unix->hostname_g();}
	if($hostname<>null){$PROXY=$hostname;}
	
	echo "DOMAIN.........: $DOMAIN\n";
	echo "LOCALNET.......: $LOCALNET\n";
	echo "PROXY..........: $PROXY:$PORT\n";
	
	if($DOMAIN==null){
		build_progress("Missing domain....",110);
		return false;
	}
	if($LOCALNET==null){
		build_progress("Missing LOCALNET....",110);
		return false;
	}	
	if($PROXY==null){
		build_progress("Missing PROXY....",110);
		return false;
	}	
	if(!is_numeric($PORT)){build_progress("Missing PROXY PORT....",110);
		return false;
	}


	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyPac", 1);
    build_progress("{install_service}",10);
	install_service();


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $CountOfrules=0;
    $results=$q->QUERY_SQL("SELECT * FROM wpad_rules");
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        if($ID>0){$CountOfrules++;}

    }
    _out("Count of rule: $CountOfrules");
    if($CountOfrules==0) {
        _out("[$index]: Creating default rule...");
        $rulnename="Wizard - all to $PROXY:$PORT";
        echo "Check if rule $rulnename Already exists...\n";
        $ligne = $q->mysqli_fetch_array("SELECT ID FROM wpad_rules WHERE rulename='$rulnename'");
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        $MAIN_RULE_ID = intval($ligne["ID"]);

        if ($MAIN_RULE_ID == 0) {
            $sql = "INSERT INTO `wpad_rules` (`rulename`,`enabled`,`zorder`,`dntlhstname`) VALUES ('$rulnename',1,0,1)";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "\n";
                build_progress("Building first rule...MySQL error", 110);
                return false;
            }

            $MAIN_RULE_ID = intval($q->last_id);
        }
        if ($MAIN_RULE_ID == 0) {
            build_progress("Building first rule...MAIN_RULE_ID = 0!", 110);
            return false;
        }

        $zmd5 = md5("$MAIN_RULE_ID$PROXY$PORT");
        build_progress("Add destination $PROXY:$PORT", 20);

        $ligne = $q->mysqli_fetch_array("SELECT zmd5 FROM wpad_destination WHERE proxyserver='$PROXY' AND proxyport='$PORT'");

        if(!$q->ok){$ligne["zmd5"]="";}
        if(!isset($ligne["zmd5"])){$ligne["zmd5"]="";}
        if(is_null($ligne["zmd5"])){$ligne["zmd5"]="";}

        if (strlen(trim($ligne["zmd5"])) == 0) {
            $q->QUERY_SQL("INSERT OR IGNORE INTO wpad_destination (zmd5,aclid,proxyserver,proxyport,zorder)
			VALUES ('$zmd5','$MAIN_RULE_ID','$PROXY','$PORT',0)");
            if (!$q->ok) {
                echo $q->mysql_error . "\n";
                build_progress("Add destination $PROXY:$PORT MySQL error", 110);
                return false;
            }
        }

        build_progress("Creating Proxy object `Everyone`", 25);


        $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE `GroupType`='all'");
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        $SourceGroupID = intval($ligne["ID"]);
        if ($SourceGroupID == 0) {
            $sql = "INSERT OR IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`tplreset`) VALUES ('Everyone','all','1','','',0);";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "\n";
                build_progress("Creating Proxy object `Everyone` MySQL error", 110);
                return false;
            }
            $SourceGroupID = intval($q->last_id);
        }

        if ($SourceGroupID == 0) {
            build_progress("Creating Proxy object `Everyone` SourceGroupID = 0!", 110);
            return false;
        }

        build_progress("Creating Proxy object `WPAD - Local networks`", 25);
        $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE `GroupName`='WPAD - Local networks'");
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        $NetWorkGroupID = intval($ligne["ID"]);

        if ($NetWorkGroupID == 0) {
            $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE `params`='WizardWPADLocalNet'");
            if(!isset($ligne["ID"])){$ligne["ID"]=0;}
            $NetWorkGroupID = intval($ligne["ID"]);
        }

        if ($NetWorkGroupID == 0) {
            echo "Creating WPAD - Local networks\n";
            $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
            $sql = "INSERT OR IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`tplreset`) 
				VALUES ('WPAD - Local networks','dst','1','','WizardWPADLocalNet',0);";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "\n";
                build_progress("Creating Proxy object `WPAD - Local networks` MySQL error", 110);
                return false;
            }
            $NetWorkGroupID = intval($q->last_id);
            echo "Creating WPAD - Local networks new ID is = $NetWorkGroupID\n";

            if (($NetWorkGroupID == $SourceGroupID) or ($NetWorkGroupID == 0)) {
                echo "Creating WPAD - Local networks wrong ID $NetWorkGroupID try to find it\n";
                $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
                $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE `params`='WizardWPADLocalNet'");
                $NetWorkGroupID = intval($ligne["ID"]);
                if(!isset($ligne["ID"])){$ligne["ID"]=0;}
                echo "Creating WPAD - Local networks WizardWPADLocalNet -> ID $NetWorkGroupID\n";

            }


        }

        if ($NetWorkGroupID == 0) {
            build_progress("Creating Proxy object `WPAD - Local networks` NetWorkGroupID = 0!", 110);
            return false;
        }

        $IP = new IP();
        $LOCALNET_ARRAY = array();
        if (strpos($LOCALNET, ",") > 0) {
            $LOCALNET_ARRAY_TEMP = explode(",", $LOCALNET);
            foreach ($LOCALNET_ARRAY_TEMP as $line) {
                $line = trim($line);
                if (!$IP->isIPAddressOrRange($line)) {
                    continue;
                }
                $LOCALNET_ARRAY[] = "('$line','$NetWorkGroupID','1','')";
            }
        } else {
            if ($IP->isIPAddressOrRange(trim($LOCALNET))) {
                $LOCALNET_ARRAY[] = "('$LOCALNET','$NetWorkGroupID','1','')";
            }
        }

        build_progress("Filling Proxy object `WPAD - Local networks`", 30);
        echo "WPAD - Local networks: $NetWorkGroupID, removing items...\n";


        $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$NetWorkGroupID");
        if (!$q->ok) {
            echo "DELETE FROM webfilters_sqitems WHERE gpid=$NetWorkGroupID\n";
            echo $q->mysql_error . "\n";
            build_progress("Filling Proxy object `WPAD - Local networks` MySQL error", 110);
            return false;
        }

        if (!$q->FIELD_EXISTS("webfilters_sqitems", "other")) {
            $q->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `other` TEXT");
        }


        echo "WPAD - Local networks: $NetWorkGroupID, insert items...\n";
        $sql = "INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES " . @implode(",", $LOCALNET_ARRAY);
        if ($GLOBALS["VERBOSE"]) {
            echo $sql . "\n";
        }
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
            build_progress("Filling Proxy object `WPAD - Local networks` MySQL error", 110);
            return false;
        }


        build_progress("Linking Everyone - $SourceGroupID - to rule $MAIN_RULE_ID", 30);
        $zmd5 = md5("$MAIN_RULE_ID$SourceGroupID");
        $q->QUERY_SQL("INSERT INTO wpad_sources_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$MAIN_RULE_ID','0','$SourceGroupID',1)");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
            build_progress("MySQL error", 110);
            return false;
        }

        $zmd5 = md5("$MAIN_RULE_ID$NetWorkGroupID");
        build_progress("Linking WPAD - Local networks - $NetWorkGroupID - to rule $MAIN_RULE_ID", 50);
        $q->QUERY_SQL("INSERT INTO wpad_white_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$MAIN_RULE_ID','0','$NetWorkGroupID',1)");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
            build_progress("MySQL error", 110);
            return false;
        }
    }
	system("$php /usr/share/artica-postfix/exec.proxy.pac.builder.php");
	if(!$not100){
	build_progress("{success}",100);}
    return true;
		
}


?>