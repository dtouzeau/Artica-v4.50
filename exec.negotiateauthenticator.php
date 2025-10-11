<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["VERBOSE"]=false;
$GLOBALS["makeQueryForce"]=false;
$GLOBALS["FORCE"]=false;

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");


if(is_array($argv)){
    if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
    if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["makeQueryForce"]=true;}
}

if($GLOBALS["VERBOSE"]){echo "START.....\n";}
xstart();

function xstart():bool{
    $unix=new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    if(!$GLOBALS["FORCE"]) {
        $execTime = $unix->file_time_min($pidfile);
        if ($execTime < 3) {
            return false;
        }
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            return false;
        }
    }
    @unlink($pidfile);
    @file_put_contents($pidfile,getmypid());


    $EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));
    if($EnableAdLDAPAuth==1){
        echo "EnableAdLDAPAuth = 1: Means Only Active Directory Authentication using LDAP...Aborting\n";
        return false;
    }




    $data=$unix->squidclient("negotiateauthenticator");
    if($data==null){return false;}
    $f=explode("\n",$data);
    $ARRAY=array();
    foreach ($f as $line){
        if(preg_match("#number active:\s+([0-9]+)\s+of\s+([0-9]+)#",$line,$re)){
            if(isset($ARRAY["ACTIVE"])){
                $ARRAY["ACTIVE"]=$ARRAY["ACTIVE"]+$re[1];
                $ARRAY["MAX"]=$ARRAY["MAX"]+$re[1];
                continue;
            }
            $ARRAY["ACTIVE"]=$re[1];
            $ARRAY["MAX"]=$re[2];
            continue;
         }

        if(preg_match("#requests sent:\s+([0-9]+)#",$line,$re)){
            if(isset($ARRAY["SENT"])){
                $ARRAY["SENT"]=$ARRAY["SENT"]+$re[1];
                continue;
            }
            $ARRAY["SENT"]=$re[1];
            continue;
         }

        if(preg_match("#replies received:\s+([0-9]+)#",$line,$re)){
            if(isset($ARRAY["RECIEVED"])){
                $ARRAY["RECIEVED"]=$ARRAY["RECIEVED"]+$re[1];
                continue;
            }
            $ARRAY["RECIEVED"]=$re[1];
            continue;
        }

        if(preg_match("#requests timedout:\s+([0-9]+)#",$line,$re)){
            if(isset($ARRAY["TIMEDOUT"])){
                $ARRAY["TIMEDOUT"]=$ARRAY["TIMEDOUT"]+$re[1];
                continue;
            }
            $ARRAY["TIMEDOUT"]=$re[1];
            continue;
        }

        if(preg_match("#avg service time:\s+([0-9]+)\s+(.+)#",$line,$re)){
            $ARRAY["AVG"]=$re[1]." ".trim($re[2]);
            continue;
        }

        if(preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9\.]+)#",$line,$re)){

            $ARRAY["ROWS"][]=array(
                "PID"=>$re[3],
                "REQ"=>$re[4],
                "REP"=>$re[5],
                "TOU"=>$re[6],
                "TIME"=>$re[7]
            );

        }
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUID_NEGOTIATE_AUTHENTICATOR",serialize($ARRAY));
    return true;


}

/**
 * Created by PhpStorm.
 * User: dtouzeau
 * Date: 21/04/19
 * Time: 10:39
 */