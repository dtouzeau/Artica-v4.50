<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["history-search"])){history_search();exit;}
if(isset($_GET["history-delete"])){history_delete();exit;}
if(isset($_GET["postfix-ipset"])){postfix_ipset();exit;}

if(isset($_GET["smtp-transactions"])){smtp_transactions();exit;}
if(isset($_GET["sasl"])){postfix_sasl();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);

function history_delete(){
	@unlink("/home/artica/postfix/history/".basename($_GET["history-delete"]).".log");
}

// Rewired 2026-08: the SMTP ipset compile/download is owned by the artica-postfix daemon (module
// postfix/smtpfw_*). These framework entry points no longer shell-exec the old PHP SMTP ipset script (which
// ended in `/etc/init.d/firehol reconfigure`, a rule afirewall then wiped); they kick the daemon's
// fire-and-track job over /run/artica-postfix.sock. Kept for any residual caller — the WebConsole
// pages now call the daemon routes directly.
function postfix_ipset(){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/ipset/download");
}

function postfix_sasl(){
    $unix=new unix();
    $unix->framework_execute("exec.postfix.maincf.php --smtp-sasl","SMTP_SASL_PROGRESS","SMTP_SASL_LOG");
}

function smtp_transactions(){

    $query=$_GET["smtp-transactions"];
    $tfile=$_GET["tfile"];
    // Pentest 2026-11-04 : tfile est un md5 (traversee de repertoire et injection shell root).
    if(!preg_match('#^[a-f0-9]{32}$#',$tfile)){return;}


    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$tfile.sql",base64_decode($query));
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp.transactions.$tfile.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp.transactions.$tfile.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    $array["POURC"]=2;
    $array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.transaction.query.php $tfile >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}






function history_search(){


    $patternenc=$_GET["history-search"];
    $md5=$_GET["md5"];
    // Pentest 2026-11-04 : md5 attendu (traversee de repertoire), motif quote (injection shell root).
    if(!preg_match('#^([a-f0-9]{32})?$#',$md5)){return;}

    $finalfile="/usr/share/artica-postfix/ressources/logs/postfix.events.$md5.results";


	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/postfix.events.$md5.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR."/postfix.events.$md5.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);

	if(is_file("$finalfile")){
        $array["POURC"]=100;
        $array["TEXT"]="{done}";
        file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
        file_put_contents($GLOBALS["LOGSFILES"], "Done...");
        @chmod($GLOBALS["LOGSFILES"],0777);
        @chmod($GLOBALS["PROGRESS_FILE"],0777);
        return;
    }


    $array["POURC"]=2;
    $array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.logsearch.php ".fw_shell_quote($patternenc)." ".fw_shell_quote($md5)." >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
