<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if(isset($argv[1])){
    if($argv[1]=="--cve"){getcve();exit;}
    if($argv[1]=="--upgrade"){upgradeSamba();exit;}

}

function _GetdpkgVer(){
    $unix=new unix();
    $dpkg=$unix->find_program("dpkg");
    exec("$dpkg -s samba-common-bin 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#Version:\s+[0-9]+:(.+)#",$line,$re)){
            return $re[1];
        }
    }

    return "";

}
function getcve(){
    $version=_GetdpkgVer();
    $tb=explode("+",$version);
    print_r($tb);
    $CurrentVersion=trim($tb[0]);
    if($CurrentVersion=="4.9.5") {
        if(preg_match("#deb10u([0-9]+)#", $tb[2], $re)) {
            $PatchVersion = $re[1];
            if($PatchVersion<3){
               $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CVE-2021-44142",1);
               return true;
            }
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CVE-2021-44142",0);
    return false;
}
function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"exec.samba.upgrade.progress");
}
function upgradeSamba(){
    $unix=new unix();
    $aptmark        = $unix->find_program("apt-mark");
    $aptget         = $unix->find_program("apt-get");
    $chmods[]="/var/run/samba";
    $chmods[]="/var/lib/samba";
    $chmods[]="/var/cache/samba";
    foreach ($chmods as $directory){
        if(is_dir($directory)){
            @chmod($directory,0755);
        }
    }
    build_progress(20,"{upgrading}");
    shell_exec("$aptmark unhold samba-common winbind");
    build_progress(30,"{upgrading}");
    shell_exec("$aptget update -y --allow-releaseinfo-change");
    build_progress(50,"{upgrading}");
    shell_exec("DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -fuy  --allow-change-held-packages --only-upgrade install samba samba-libs samba-common-bin samba-common");
    build_progress(60,"{upgrading}");
    shell_exec("$aptmark hold samba-common winbind");
    if(getcve()){
        build_progress(110,"{upgrading} {failed}");
        return false;
    }
    build_progress(100,"{upgrading} {success}");
    return true;

}
