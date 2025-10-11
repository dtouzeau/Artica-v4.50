<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["backends-events"])){backend_events();exit;}



writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);

function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.unbound.php --uninstall-firewall","dnsfw.install.progress","dnsfw.install.log");

}

function install(){
	$unix=new unix();
	$unix->framework_execute("exec.unbound.php --install-firewall","dnsfw.install.progress","dnsfw.install.log");

}

function backend_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $TERM=null;
    $MAIN=unserialize(base64_decode($_GET["backends-events"]));
    $tfile=PROGRESS_DIR."/dnsfw.backends";
    if($GLOBALS["VERBOSE"]){
        print_r($MAIN);
    }
    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
        $MAIN[$val]=str_replace("'", "\'", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);
    if($max==0){$max=200;}
    if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    $search="$date.*?$TERM";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/dnsfw.backends.log |$tail -n $max >$tfile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

