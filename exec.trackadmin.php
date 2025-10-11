<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--syslog"){syslog_conf();exit;}

function build_progress($text,$pourc){
    $filename=basename(__FILE__);
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/TrackAdmins.progress";
    echo "[{$pourc}%] $filename: $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["OUTPUT"]){usleep(5000);}
}

function install(){
    syslog_conf();
    build_progress("{Success}",100);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TrackAdmins",1);
}
function uninstall(){
    build_progress("{Success}",100);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TrackAdmins",0);
}

function syslog_conf(){
    $unix=new unix();
    $tfile="/etc/rsyslog.d/trackadmins.conf";
    $md51=null;
    if(is_file($tfile)){$md51=md5_file($tfile);}


    $rules = BuildRemoteSyslogs("admintrack","admins");
    $f[]="if (\$programname =='ArticaTrackAdmins') then {";
    $f[]="action(type=\"omfile\" dirCreateMode=\"0700\" FileCreateMode=\"0755\" File=\"/var/log/admintracks.log\" ioBufferSize=\"128k\" flushOnTXEnd=\"off\" asyncWriting=\"on\")";
    $f[]=$rules;
    $f[]="& stop";
    $f[]="}";
    $f[]="";
    @file_put_contents($tfile,@implode("\n",$f));
    $md52=md5_file($tfile);
    if($md51<>$md52){
        $unix=new unix();$unix->RESTART_SYSLOG(true);
        $unix->ToSyslog("Rsyslog for adminstrack successfully installed",false,"ArticaTrackAdmins");

    }

}