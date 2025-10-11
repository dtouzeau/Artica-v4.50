<?php
$GLOBALS["ARTPATH"]="/usr/share/artica-postfix";
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
    ini_set('display_errors', 1);
    ini_set('html_errors',0);
    ini_set('display_errors', 1);

    $GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

if(isset($_GET["install"])){install();}
if(isset($_GET["uninstall"])){uninstall();}
if(isset($_GET["status"])){status();}
if(isset($_GET["restart"])){restart();}
if(isset($_GET["events"])){events();}

function status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/webapi.status";
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --ad-rest >{$GLOBALS["LOGSFILES"]} 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function restart():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.active-directory-rest.php --restart","active-directory-rest.restart","active-directory-rest.restart.log");

}

function install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.active-directory-rest.php --install","active-directory-rest.progress","active-directory-rest.log");

}
function uninstall():bool{

    $unix=new unix();
    return $unix->framework_execute("exec.active-directory-rest.php --uninstall","active-directory-rest.progress","active-directory-rest.log");

}

function events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $target=PROGRESS_DIR."/adrest.search";
    $src="/var/log/articarest.log";
    $MAIN=unserialize(base64_decode($_GET["events"]));


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