<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["sigtool"])){sigtool();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["access-events"])){access_events();exit;}
if(isset($_GET["disable-progress"])){disable_progress();exit;}
if(isset($_GET["apply-progress"])){apply_progress();exit;}
if(isset($_GET["reinstall-progress"])){reinstall_progress();exit;}
if(isset($_GET["service-events"])){service_events();exit;}
if(isset($_GET["requests-events"])){requests_events();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["import"])){import();exit;}
if(isset($_GET["rules-conf"])){rules_conf();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function apply_progress():bool{
    $unix=new unix();
    $unix->framework_execute("exec.proxy.pac.builder.php --build --force","autoconfiguration.apply.progress","autoconfiguration.apply.log");
    return true;
}
function rules_conf():bool{
    $tfile=PROGRESS_DIR."/proxy.pac.rules";
    if(is_file($tfile)){@unlink($tfile);}
    @copy("/home/squid/proxy_pac_rules/rules.conf",$tfile);
    @chmod($tfile,0755);
    return true;
}

function export():bool{
    $ID=intval($_GET["export"]);
    $unix=new unix();
    $unix->framework_execute("exec.proxy.pac.builder.php --export $ID", "pac.rule.export.progress", "pac.rule.export.log");
    return true;
}
function import():bool{
    $filename=$_GET["import"];
    $unix=new unix();
    $unix->framework_execute("exec.proxy.pac.builder.php --import \"$filename\"", "pac.rule.import.progress", "pac.rule.import.log");
    return true;
}


function reinstall_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid-autoconf.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/autoconfiguration.apply.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php /usr/share/artica-postfix/exec.squid.autoconfig.php --re-install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function pattern(){

}
function requests_events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["requests-events"]));
    $TERM="";

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
     $IN=$MAIN["IN"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}


    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $search=trim($search);
    if($search<>null) {
        $cmd = "$grep --binary-files=text -i -E '$search' /var/log/proxy-pac/access.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/proxypacr.syslog 2>&1";
    }else{
        $cmd = "$tail -n $max /var/log/proxy-pac/access.log >/usr/share/artica-postfix/ressources/logs/web/proxypacr.syslog 2>&1";
    }
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/proxypacr.syslog.query", $search);
    shell_exec($cmd);

}
function service_events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["service-events"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $IN=$MAIN["IN"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}


    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $search=trim($search);

    if($search<>null) {
        $cmd = "$grep --binary-files=text -i -E '$search' /var/log/proxy-pac/server.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/proxypac.syslog 2>&1";
    }else{
        $cmd = "$tail -n $max /var/log/proxy-pac/server.log >/usr/share/artica-postfix/ressources/logs/web/proxypac.syslog 2>&1";
    }
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/proxypac.syslog.query", $search);
    shell_exec($cmd);

}
