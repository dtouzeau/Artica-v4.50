<?php
// Patch SSL Client Certificate.
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

if(isset($_GET["server-certificate"])){server_certificate();exit;}
if(isset($_GET["certificate-manager"])){certificate_manager();exit;}
if(isset($_GET["client-certificate"])){certificate_client();exit;}
if(isset($_GET["reload-webconsole"])){reload_webconsole();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function server_certificate(){
    $unix=new unix();
    $unix->framework_execute("exec.webconsole.certificates.php","manager-certificate.progress","manager-certificate.log");
}
function certificate_manager(){
    $unix=new unix();
    $unix->framework_execute("exec.webconsole.certificates.php --client -100 \"\"","manager-certificate.progress","manager-certificate.log");
}
function certificate_client(){
    $unix=new unix();
    $unix->framework_execute("exec.webconsole.certificates.php --client -generic \"\"","manager-certificate.progress","manager-certificate.log");
}
function reload_webconsole(){
    $unix=new unix();
    $unix->framework_exec("exec.lighttpd.php --reload");

}