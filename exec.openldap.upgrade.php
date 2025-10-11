<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if(isset($argv[1])){
    if($argv[1]=="--cve"){getcve();exit;}
    if($argv[1]=="--upgrade"){upgradeOpenLDAP();exit;}

}

function _GetdpkgVer(){
    $unix=new unix();
    $dpkg=$unix->find_program("dpkg");
    exec("$dpkg -s slapd 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#Version:\s+(.+)#",$line,$re)){
            if($GLOBALS["VERBOSE"]){echo "Found Version: {$re[1]}\n";}
            return $re[1];
        }
    }
    if($GLOBALS["VERBOSE"]){echo "Not Found any version!\n";}
    return "";

}
function getcve(){
    $version=_GetdpkgVer();
    $tb=explode("+",$version);
    $CurrentVersion=trim($tb[0]);
    if($GLOBALS["VERBOSE"]){echo "Current software version=$CurrentVersion\n";}
    if($CurrentVersion=="2.4.47") {
        if(preg_match("#deb10u([0-9]+)#", $tb[2], $re)) {
            $PatchVersion = $re[1];
            if($GLOBALS["VERBOSE"]){echo "Current software version=$CurrentVersion patchverssion=$PatchVersion\n";}
            if($PatchVersion<7){
               $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CVE-2022-29155",1);
               return true;
            }
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CVE-2022-29155",0);
    return false;
}
function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"exec.openldap.upgrade.progress");
}
function upgradeOpenLDAP(){
    $unix=new unix();
    $aptmark        = $unix->find_program("apt-mark");
    $aptget         = $unix->find_program("apt-get");

    build_progress(20,"{upgrading}");
    shell_exec("$aptmark unhold slapd");
    build_progress(30,"{upgrading}");
    shell_exec("$aptget update -y --allow-releaseinfo-change");
    build_progress(50,"{upgrading}");
    shell_exec("DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -fuy  --allow-change-held-packages --only-upgrade install slapd");
    build_progress(60,"{upgrading}");
    shell_exec("$aptmark hold slapd");
    if(getcve()){
        build_progress(110,"{upgrading} {failed}");
        return false;
    }
    build_progress(100,"{upgrading} {success}");
    return true;

}
