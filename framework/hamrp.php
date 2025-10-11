<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["uninstall"])){UNINSTALL();exit;}
if(isset($_GET["install"])){INSTALL();exit;}
if(isset($_GET["status"])){STATUS();exit;}
if(isset($_GET["restart"])){RESTART();exit;}
if(isset($_GET["apply"])){APPLY();exit;}
if(isset($_GET["events"])){SearchServiceEventsInSyslog();exit;}
if(isset($_GET["new-node"])){NEW_NODE();exit;}
if(isset($_GET["new-ssh"])){NEW_SSH();exit;}
if(isset($_GET["update-agent"])){UPDATE_AGENT();exit;}
if(isset($_GET["sync-singlenode"])){SYNC_SINGLE_NODE();exit;}
if(isset($_GET["adrest-events"])){ADREST_EVENTS();exit;}
if(isset($_GET["refresh-group"])){REFRESH_GROUP();exit;}
if(isset($_GET["reconfigure-nginx"])){RECONFIGURE_NGINX();exit;}
if(isset($_GET["installv2"])){installv2();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function STATUS(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.status.php --nagios-client --nowachdog";
    shell_exec($cmd);
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
}
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

function installv2():bool{
    $product=$_GET["product"];
    $key=$_GET["key"];
    $uuid=$_GET["uuid"];

    $unix=new unix();

    return $unix->framework_execute("exec.hamrp.php --installv2 $product $key $uuid",
        "system.installsoft.$uuid.progress","system.installsoft.progress.$uuid.txt");
}

function ADREST_EVENTS():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");


    $MAIN=unserialize(base64_decode($_GET["adrest-events"]));
    $uuid=$MAIN["uuid"];
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
    if(isset($MAIN["TERM"])) {
        if ($MAIN["TERM"] <> null) {
            $TERM = ".*?{$MAIN["TERM"]}";
        }
    }

    if($TERM<>null) {
        $search = "$TERM";
        $search = str_replace(".*?.*?", ".*?", $search);
        $cmd = "$grep --binary-files=text -i -E '$search' $src |$tail -n $max >$target 2>&1";
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
    return $unix->framework_execute("exec.hamrp.php --sync-single $uuid","harmp.connect.progress","harmp.connect.log");
}

function INSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.hamrp.php --install","hamrp.progress","hamrp.log");
}

function UNINSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.hamrp.php --uninstall","hamrp.progress","hamrp.log");
	
}

function RESTART(){
    $unix=new unix();
    $unix->framework_execute("exec.nagios-client.php --restart","nagios.client.progress","nagios.client.progress.log");
}


function SearchServiceEventsInSyslog(){
    $unix=new unix();
    $unix->framework_search_syslog($_GET["events"],
        "/var/log/nagios-client.log",
        "nagios.events.syslog","
        ");
}
?>