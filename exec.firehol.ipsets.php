<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if(!isset($argv[1])){die();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--catz"){build_categories();exit;}
if($argv[1]=="--build"){build_ipsets();exit;}
if($argv[1]=="--tests"){tests();exit;}

function tests(){
    $array=get_list_ip("192.168.1.0/24");
    print_r($array);
}
function build_ipsets(){
    shell_exec("/usr/share/artica-postfix/bin/articapsniffer --cybercrime-export");
}

function ipset_exists($fname){

    $ARRAY=ipset_list();
    $fname=strtolower($fname);
    if(isset($ARRAY[$fname])){return true;}
    return false;
}

function ipset_list(){
    if(isset($GLOBALS["ipsetlist"])){return $GLOBALS["ipsetlist"];}
    $unix=new unix();
    $ipset=$unix->find_program("ipset");
    exec("$ipset list -n 2>&1",$results);
    foreach ($results as $db){
        $GLOBALS["ipsetlist"][strtolower($db)]=true;
    }


}

function white_list_ip($ip_addr_cidr,$ipsetname,$link){
    $ip_arr = explode("/", $ip_addr_cidr);
    $cdir=intval($ip_arr[1]);

    if($cdir<16){
        fwrite($link,"add $ipsetname $ip_addr_cidr nomatch\n");
        return;
    }
    if($cdir==32){
        fwrite($link,"add $ipsetname $ip_addr_cidr nomatch\n");
        return;
    }

    $bin = "";

    for($i=1;$i<=32;$i++) {
        $bin .= $ip_arr[1] >= $i ? '1' : '0';
    }

    $ip_arr[1] = bindec($bin);

    $ip = ip2long($ip_arr[0]);
    $nm = $ip_arr[1];
    $nw = ($ip & $nm);
    $bc = $nw | ~$nm;
    $bc_long = ip2long(long2ip($bc));

    for($zm=1;($nw + $zm)<=($bc_long - 1);$zm++) {
        fwrite($link,"add $ipsetname ".long2ip($nw + $zm)." nomatch\n");

    }

}


//


