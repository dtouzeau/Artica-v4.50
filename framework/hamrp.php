<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["uninstall"])){UNINSTALL();exit;}
if(isset($_GET["install"])){INSTALL();exit;}
if(isset($_GET["new-node"])){NEW_NODE();exit;}
if(isset($_GET["new-ssh"])){NEW_SSH();exit;}
if(isset($_GET["update-agent"])){UPDATE_AGENT();exit;}
if(isset($_GET["sync-singlenode"])){SYNC_SINGLE_NODE();exit;}
if(isset($_GET["adrest-events"])){ADREST_EVENTS();exit;}
if(isset($_GET["refresh-group"])){REFRESH_GROUP();exit;}
if(isset($_GET["reconfigure-nginx"])){RECONFIGURE_NGINX();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function NEW_NODE():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.hamrp.php --newnode","harmp.connect.progress","harmp.connect.log");
}
function UPDATE_AGENT():bool{
    $unix=new unix();
    $groupid=intval($_GET["update-agent"]);
    return $unix->framework_execute("exec.hamrp.php --update-agent $groupid","harmp.connect.progress","harmp.connect.log");
}
function REFRESH_GROUP():bool{
    $unix=new unix();
    $groupid=intval($_GET["refresh-group"]);
    return $unix->framework_execute("exec.hamrp.php --sync-group $groupid","harmp.refresh.$groupid.progress","harmp.refresh.$groupid.log");
}
function RECONFIGURE_NGINX():bool{
    $unix=new unix();
    $groupid=intval($_GET["reconfigure-nginx"]);
    return $unix->framework_execute("exec.hamrp.php --push-nginx $groupid","harmp.nginx.$groupid.progress","harmp.nginx.$groupid.log");
}

function ADREST_EVENTS():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");


    // JSON from fw.hamrp.nodes.php (never unserialize() a request value in a root process).
    $MAIN=json_decode((string)base64_decode((string)$_GET["adrest-events"],true),true);
    if(!is_array($MAIN)){return false;}
    $uuid=isset($MAIN["uuid"])?(string)$MAIN["uuid"]:"";
    if(!preg_match('/^[A-Za-z0-9._:-]{1,128}$/',$uuid)){return false;}
    $MAIN=array("MAX"=>isset($MAIN["MAX"])?intval($MAIN["MAX"]):200,"TERM"=>isset($MAIN["TERM"])?(string)$MAIN["TERM"]:"");
    $src="/home/artica/harmp/$uuid/articarest.log";
    $target     = PROGRESS_DIR."/$uuid.articarest.search";

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }
    $TERM=null;
    if(!isset($MAIN["MAX"])){
        $MAIN["MAX"]=200;
    }

    $max=intval($MAIN["MAX"]);
    if($max>1500){$max=1500;}
    if($max<1){$max=200;}
    if(isset($MAIN["TERM"])) {
        if ($MAIN["TERM"] <> null) {
            $TERM = ".*?{$MAIN["TERM"]}";
        }
    }

    if($TERM<>null) {
        $search = "$TERM";
        $search = str_replace(".*?.*?", ".*?", $search);
        $cmd = "$grep --binary-files=text -i -E ".fw_shell_quote($search)." $src |$tail -n $max >$target 2>&1";
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return true;
    }
    $cmd = "$tail $src -n $max >$target 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function NEW_SSH():bool{
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $logfile=PROGRESS_DIR."/SSHDeployAgent.progress.log";
    $cmd="$nohup /usr/sbin/artica-phpfpm-service -ssh-deploy >$logfile 2>&1 &";
    writelogs_framework($cmd,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}
function SYNC_SINGLE_NODE(){
    $uuid=$_GET["sync-singlenode"];
    $unix=new unix();
    return $unix->framework_execute("exec.hamrp.php --sync-single ".fw_shell_quote($uuid),"harmp.connect.progress","harmp.connect.log");
}

function INSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.hamrp.php --install","hamrp.progress","hamrp.log");
}

function UNINSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.hamrp.php --uninstall","hamrp.progress","hamrp.log");
	
}

?>