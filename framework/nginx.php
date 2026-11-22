<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
/*$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
*/
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["check-reverse"])){check_reverse();exit;}

if(isset($_GET["clean-websites"])){clean_websites();exit;}
if(isset($_GET["modsecurity-events"])){mod_security_events();exit;}

if(isset($_GET["atomi-update"])){atomi_update();exit;}
if(isset($_GET["atomi-enable"])){atomi_enable();exit;}
if(isset($_GET["atomi-disable"])){atomi_disable();exit;}
if(isset($_GET["webcopy-sync"])){webcopy_sync();exit;}
if(isset($_GET["webcopy-delete"])){webcopy_delete();exit;}
if(isset($_GET["webcopy-events"])){webcopy_events();exit;}
if(isset($_GET["webcopy-syncall"])){webcopy_sync_all();exit;}
if(isset($_GET["erase-sync"])){webcopy_erase();exit;}

if(isset($_GET["waf-modsec"])){modesecurity_modsec();exit;}
if(isset($_GET["waf-modrep"])){modesecurity_modrep();exit;}
if(isset($_GET["prepare-modsec-backup"])){modesecurity_prepare_backup();exit;}
if(isset($_GET["delete-modsec-backup"])){modesecurity_delete_backup();exit;}
if(isset($_GET["nginx-debug"])){SearchInDebug();exit;}
if(isset($_GET["debug-prepare"])){debug_prepare();exit;}
if(isset($_GET["webcopy-sync"])){webcopy_sync();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("Unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");
function check_reverse():bool{
    $ID=intval($_GET["check-reverse"]);
    $file=PROGRESS_DIR."/check-reverse-$ID.txt";
    exec("/usr/share/artica-postfix/bin/reverse-tests -siteid $ID >$file 2>&1");
    @chmod($file,0755);
    return true;
}
function SearchInDebug():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");

    $MAIN=unserialize(base64_decode($_GET["nginx-debug"]));
    $siteid=intval($_GET["siteid"]);
    $targetfile="/var/log/nginx/$siteid.debug";
    $RFile=PROGRESS_DIR."/$siteid.syslog";
    $PFile=PROGRESS_DIR."/$siteid.pattern";

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("/", "\/", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
    }

    $max=intval($MAIN["MAX"]);if($max>500){$max=500;}
    if($max==0){$max=100;}
    $date=$MAIN["DATE"];
    $search=$MAIN["TERM"];
    $search="$date.*?$search";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' $targetfile |$tail -n $max >$RFile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($PFile, $search);
    shell_exec($cmd);
    return true;
}

function StringToRegex($pattern):string{
    $pattern=str_replace(".", "\.", $pattern);
    $pattern=str_replace("(", "\(", $pattern);
    $pattern=str_replace(")", "\)", $pattern);
    $pattern=str_replace("+", "\+", $pattern);
    $pattern=str_replace("?", "\?", $pattern);
    $pattern=str_replace("[", "\[", $pattern);
    $pattern=str_replace("]", "\]", $pattern);
    $pattern=str_replace("*", ".*", $pattern);
    return $pattern;
}
// SearchInAv() (?nginx-searchav) removed 2026-11-15: fw.clamav.waf.php reads the log through
// articarest's closed list (GET /system/logfile/search/waf-antivirus/...).
function modesecurity_prepare_backup(){
    $ID=intval($_GET["prepare-modsec-backup"]);
    $unix=new unix();
    $tgz_path=PROGRESS_DIR."/modesecurity-backup-$ID.tar.gz";
    $tdir="/home/artica/modsecurity_backup_$ID";
    $tar=$unix->find_program("tar");
    $cmd="$tar -czvf $tgz_path $tdir";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    chown($tgz_path,"www-data");
    chgrp($tgz_path,"www-data");

}

function modesecurity_delete_backup(){
    $ID=intval($_GET["delete-modsec-backup"]);
    $tdir="/home/artica/modsecurity_backup_$ID";
    if(!is_dir($tdir)){return true;}
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $rm=$unix->find_program("rm");
    shell_exec("$nohup $rm -rf $tdir >/dev/null 2>&1 &");

}


function modesecurity_modrep(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/modsec_audit.log.tmp";
    $cmdfile=$targetfile.".cmd";
    $sourceLog="/var/log/modsec-parse.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];

    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($cmdfile, $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);

}

function modesecurity_modsec(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/modsec_audit.log.tmp";
    $cmdfile=$targetfile.".cmd";
    $sourceLog="/var/log/modsec_audit.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];

    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($cmdfile, $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);
}





function atomi_update(){
    $unix=new unix();
    $unix->framework_execute("exec.ModSecurity.download.php --atomi --force",
        "atomi.progress","atomi.log"
    );

}
function atomi_enable(){
    $unix=new unix();
    $unix->framework_execute("exec.ModSecurity.download.php --atomi-enable --force",
        "atomi.progress","atomi.log"
    );

}
function atomi_disable(){
    $unix=new unix();
    $unix->framework_execute("exec.ModSecurity.download.php --atomi-disable --force",
        "atomi.progress","atomi.log"
    );

}






function mod_security_events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $search=null;
    $MAIN=unserialize(base64_decode($_GET["modsecurity-events"]));

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}


    if($MAIN["TERM"]<>null){
        $TERM=".*?{$MAIN["TERM"]}";
    }
    if($GLOBALS["VERBOSE"]){echo "TERM: $TERM<br>\n";}

    if($MAIN["SRC"]<>null){
        $addon_src="|$grep -i -E '{$MAIN["SRC"]}'";
    }

    $mainline="";
    if($TERM<>null){$mainline=$TERM;}

    if( $mainline<>null){
        $search="$mainline";
    }

    if($search==null){
        $cmd="$tail -n $max /var/log/nginx/modsecurity.log >/usr/share/artica-postfix/ressources/logs/web/modsecurity.log 2>&1";
        if($GLOBALS["VERBOSE"]){echo "$cmd<br>\n";}
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=str_replace(".*?.*?",".*?",$search);

    $cmd="$grep --binary-files=text -i -E '$search' /var/log/nginx/modsecurity.log $addon_src|$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/modsecurity.log 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd<bv>\n";}
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}


function webcopy_events(){
    $unix=new unix();
    $pattern=unserialize(trim(base64_decode($_GET["webcopy-events"])));
    $TERM=$pattern["TERM"];
    $MAX=intval($pattern["MAX"]);
    writelogs_framework($pattern ,__FUNCTION__,__FILE__,__LINE__);
    $syslogpath="/var/log/nginx/webcopy.log";
    $grepbin=$unix->find_program("grep");
    $tail = $unix->find_program("tail");
    if($tail==null){return;}
    writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
    if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
    if($MAX==0){$MAX=50;}
    $output=PROGRESS_DIR."/webcopy.syslog";

    if(strlen($pattern)>1){
        $grep="$grepbin --binary-file=text -i -E '$pattern' $syslogpath";
    }
    if($grep<>null){
        $cmd="$grep|$tail -n $MAX >$output 2>&1";
    }else{
        $cmd="$tail -n $MAX $syslogpath >$output 2>&1";
    }

    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($output, 0755);

}

