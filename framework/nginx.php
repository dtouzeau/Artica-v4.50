<?php
/*$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
*/
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["nginx-searchav"])){SearchInAv();exit;}


if(isset($_GET["apply-template"])){apply_template();exit;}
if(isset($_GET["check-reverse"])){check_reverse();exit;}
if(isset($_GET["create-server-cert"])){create_server_cert();exit;}
if(isset($_GET["create-client-cert"])){create_client_cert();exit;}

if(isset($_GET["upgrade1"])){upgrade1();exit;}
if(isset($_GET["remove-site"])){remove_website();exit;}
if(isset($_GET["access-errors"])){events_errors();exit;}
if(isset($_GET["disable-all"])){disable_all();exit;}
if(isset($_GET["execute-wizard"])){execute_wizard();exit;}
if(isset($_GET["import"])){import();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["status-infos"])){status_info();exit;}
if(isset($_GET["delete-cache"])){delete_cache();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["www-events"])){www_events();exit;}
if(isset($_GET["mysqldb-restart"])){mysqldb_restart();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["conf-view"])){conf_view();exit;}
if(isset($_GET["replic-conf"])){conf_save();exit;}
if(isset($_GET["uncompress-nginx"])){uncompress_nginx();exit;}
if(isset($_GET["reconfigure-single"])){reconfigure_single();exit;}
if(isset($_GET["purge-cache"])){purge_cache();exit;}
if(isset($_GET["import-bulk"])){import_bulk();exit;}
if(isset($_GET["reconfigure-progress"])){reconfigure_progress();exit;}
if(isset($_GET["access-query"])){events_all();exit;}
if(isset($_GET["compile-single"])){compile_single();exit;}
if(isset($_GET["compile-destination"])){compile_destination();exit;}
if(isset($_GET["refresh-caches"])){refresh_caches();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["clean-websites"])){clean_websites();exit;}
if(isset($_GET["backup"])){backup();exit;}
if(isset($_GET["restore"])){restore();exit;}
if(isset($_GET["build-main"])){build_main();exit;}
if(isset($_GET["status"])){status_info2();exit;}
if(isset($_GET["clean"])){clean();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["reconfigre-php-fpm"])){reconfigure_php_fpm();exit;}

if(isset($_GET["reconfigure-modsecurity"])){mod_security_reconfigure();exit;}
if(isset($_GET["modsecurity-install"])){mod_security_install();exit;}
if(isset($_GET["modsecurity-uninstall"])){mod_security_uninstall();exit;}
if(isset($_GET["modsecurity-events"])){mod_security_events();exit;}
if(isset($_GET["modsecurity-default-white"])){mod_security_default_white();exit;}
if(isset($_GET["modsecurity-compile"])){modsecurity_compile();exit;}
if(isset($_GET["modsecurity-compile-all"])){modsecurity_compile_all();exit;}


if(isset($_GET["atomi-update"])){atomi_update();exit;}
if(isset($_GET["atomi-enable"])){atomi_enable();exit;}
if(isset($_GET["atomi-disable"])){atomi_disable();exit;}



if(isset($_GET["upload-package"])){upload_package();exit;}
if(isset($_GET["webcopy-sync"])){webcopy_sync();exit;}
if(isset($_GET["webcopy-delete"])){webcopy_delete();exit;}
if(isset($_GET["webcopy-events"])){webcopy_events();exit;}
if(isset($_GET["webcopy-syncall"])){webcopy_sync_all();exit;}
if(isset($_GET["erase-sync"])){webcopy_erase();exit;}

if(isset($_GET["waf-modsec"])){modesecurity_modsec();exit;}
if(isset($_GET["waf-modrep"])){modesecurity_modrep();exit;}
if(isset($_GET["prepare-modsec-backup"])){modesecurity_prepare_backup();exit;}
if(isset($_GET["delete-modsec-backup"])){modesecurity_delete_backup();exit;}
if(isset($_GET["waf-rules"])){modsecurity_rules();exit;}
if(isset($_GET["nginx-requests"])){SearchInSyslog();exit;}
if(isset($_GET["list-local-reverses"])){list_local_reverses();exit;}
if(isset($_GET["nginx-debug"])){SearchInDebug();exit;}
if(isset($_GET["debug-prepare"])){debug_prepare();exit;}
if(isset($_GET["webcopy-sync"])){webcopy_sync();exit;}

if(isset($_GET["cache-disk-scan"])){cache_disk_scan();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("Unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);
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
function cache_disk_scan():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.nginx.php --cache-disk-scan",
        "nginx.scan.progress","nginx.scan.log");
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
function SearchInAv():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["nginx-searchav"]));
    $index=$_GET["index"];


    if(preg_match("#-([0-9]+)$#",$index,$re)){
        $index=$re[1];
    }
    $targetfile="/var/log/nginx/antivirus.log";

    if(isset($_GET["uuid"])){
        if(strlen($_GET["uuid"])>5){
            $targetfile="/home/artica/harmp/{$_GET["uuid"]}/access.$index.log";
        }
    }

    $RFile=PROGRESS_DIR."/nginx-searchav.$index.syslog";
    $PFile=PROGRESS_DIR."/nginx-search.$index.pattern";
    if(!isset($MAIN["TERM"])){$MAIN["TERM"]=null;}
    $opts=unserialize(base64_decode($_GET["opts"]));

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=StringToRegex($MAIN[$val]);
    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($max==0){$max=100;}
    $search=$MAIN["TERM"];
    $SearchOpts=array();

    foreach ($opts as $key=>$val){
        $val=trim($val);
        $index=0;
        if(strlen($val)==0){continue;}

        if($key=="proxy_upstream_name"){
            $key="Serverid";
            $index=10;
            if(preg_match("#.*?-([0-9]+)#",$val)){
                $val=$re[1];
            }
        }
        $val=str_replace("*",".*?",$val);

        if($key=="remote_addr"){
            $index=1;
            $key="src_ip";
        }

        if($key=="request"){
            $index=2;
        }

        if($key=="status"){
            $index=3;
        }
        if($key=="user_agent"){
            $index=4;
        }

        $val=StringToRegex($val);
        $SearchOpts[$index]="$key:$val";

    }
    $MainSearch="";
    if (count($SearchOpts)>0){
        ksort($SearchOpts);
        $MainSearch=@implode(".*?",$SearchOpts);

    }
    $MainSearch=str_replace(".*?.*?",".*?",$MainSearch);
    $FirstCmd="$tail -n $max $targetfile";

    if(strlen($search)>2 AND strlen($MainSearch)>2){
        $FirstCmd="$grep --binary-files=text -i -E '$MainSearch' $targetfile|$grep -i -E '$search'|$tail -n $max";
    }
    if(strlen($search)==0 AND strlen($MainSearch)>2){
        $FirstCmd="$grep --binary-files=text -i -E '$MainSearch' $targetfile|$tail -n $max";
    }
    if(strlen($search)>2 AND strlen($MainSearch)==0){
        $FirstCmd="$grep --binary-files=text -i -E '$search' $targetfile|$tail -n $max";
    }

    $cmd="$FirstCmd >$RFile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($PFile, $search);
    shell_exec($cmd);
    @chmod($RFile,0755);
    @chown($RFile,"www-data");
    return true;
}
function SearchInSyslog():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["nginx-requests"]));
    $index=$_GET["index"];


    if(preg_match("#-([0-9]+)$#",$index,$re)){
        $index=$re[1];
    }


    $targetfile="/var/log/nginx/access.$index.log";

    if(isset($_GET["uuid"])){
        if(strlen($_GET["uuid"])>5){
            $targetfile="/home/artica/harmp/{$_GET["uuid"]}/access.$index.log";
        }
    }

    $RFile=PROGRESS_DIR."/nginx-search.$index.syslog";
    $PFile=PROGRESS_DIR."/nginx-search.$index.pattern";
    if(!isset($MAIN["TERM"])){$MAIN["TERM"]=null;}
    $opts=unserialize(base64_decode($_GET["opts"]));

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=StringToRegex($MAIN[$val]);
    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($max==0){$max=100;}
    $search=$MAIN["TERM"];
    $SearchOpts=array();

    foreach ($opts as $key=>$val){
        $val=trim($val);
        $index=0;
        if(strlen($val)==0){continue;}

        if($key=="proxy_upstream_name"){
            $index=10;
        }
        $val=str_replace("*",".*?",$val);

        if($key=="remote_addr"){
            $index=1;
            $key="src_ip";
        }

        if($key=="request"){
            $index=2;
        }

        if($key=="status"){
            $index=3;
        }
        if($key=="user_agent"){
            $index=4;
        }

        $val=StringToRegex($val);
        $SearchOpts[$index]="\"$key\":\"$val\"";
        $SearchOpts[$index]=str_replace("\..*\?",".*?",$SearchOpts[$index]);
        $SearchOpts[$index]=str_replace("\[0-9\]","[0-9]+",$SearchOpts[$index]);




    }
    $MainSearch="";
    if (count($SearchOpts)>0){
        ksort($SearchOpts);
        $MainSearch=@implode(".*?",$SearchOpts);

    }
    $MainSearch=str_replace(".*?.*?",".*?",$MainSearch);
    $FirstCmd="$tail -n $max $targetfile";

    if(strlen($search)>2 AND strlen($MainSearch)>2){
          $FirstCmd="$grep --binary-files=text -i -E '$MainSearch' $targetfile|$grep -i -E '$search'|$tail -n $max";
    }
    if(strlen($search)==0 AND strlen($MainSearch)>2){
        $FirstCmd="$grep --binary-files=text -i -E '$MainSearch' $targetfile|$tail -n $max";
    }
    if(strlen($search)>2 AND strlen($MainSearch)==0){
        $FirstCmd="$grep --binary-files=text -i -E '$search' $targetfile|$tail -n $max";
    }

    $cmd="$FirstCmd >$RFile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($PFile, $search);
    shell_exec($cmd);
    @chmod($RFile,0755);
    @chown($RFile,"www-data");
    return true;
}
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
function apply_template():bool{
    $unix=new unix();
    $tmplid=intval($_GET["apply-template"]);
    $serviceid=intval($_GET["serviceid"]);
    return $unix->framework_execute("exec.nginx.single.php --restore-template $tmplid $serviceid",
        "nginx.replic.$serviceid.progress",
        "nginx.replic.$serviceid.log");
}

function list_local_reverses(){
    $IDS=array();
    $unix=new unix();
    $dstf=PROGRESS_DIR."/nginx-reverses-ids.db";
    $DirFiles=$unix->DirFiles("/etc/nginx/reverse.d","[0-9]+-[0-9]+\.conf");
    foreach ($DirFiles as $fname){
        if(!preg_match("#^[0-9]+-([0-9]+)\.conf$#",$fname,$re)){continue;}
        $IDS[$re[1]]=true;
    }
    @file_put_contents($dstf,serialize($IDS));
    @chown($dstf,"www-data");

}
function modsecurity_rules(){
    $unix=new unix();
    $unix->framework_execute("exec.nginx.single.php --modsec-rules",
        "modsecurity-compile.progress",
        "modsecurity-compile.log");

}
function debug_prepare(){
    $unix=new unix();
    $siteid=intval($_GET["debug-prepare"]);
    $unix->framework_execute("exec.nginx.single.php --debug-prepare $siteid",
        "nginx.debug.$siteid.progress",
        "nginx.debug.$siteid.log"
);


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
function create_server_cert(){
    $ID=intval($_GET["create-server-cert"]);
    $unix=new unix();
    $unix->framework_execute("exec.nginx.single.php --server-cert $ID", "nginx.servercert.progress",
        "nginx.servercert.log");

}
function create_client_cert(){
    $ID=intval($_GET["create-client-cert"]);
    $unix=new unix();
    $unix->framework_execute("exec.nginx.single.php --client-cert $ID",
        "nginx.clientcert.progress",
        "nginx.clientcert.log"
    );
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

function mod_security_install(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/modsecurity.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/modsecurity.progress.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ModSecurity.install.php --install >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function mod_security_default_white(){
    $unix=new unix();
    $unix->framework_exec("exec.ModSecurity.install.php --default-white");
}
function webcopy_sync(){
    $unix=new unix();
    $serviceid=intval($_GET["webcopy-sync"]);
    $unix->framework_execute("exec.httptrack.php --single $serviceid","webcopy-$serviceid.progress","webcopy-$serviceid.log");
}
function webcopy_erase(){
    $unix=new unix();
    $serviceid=intval($_GET["erase-sync"]);
    $unix->framework_execute("exec.httptrack.php --erase $serviceid","webcopy-$serviceid.progress","webcopy-$serviceid.log");
}

function webcopy_delete(){
    $unix=new unix();
    $ID=intval($_GET["webcopy-delete"]);
    $unix->framework_exec("exec.httptrack.php --delete $ID");
}
function webcopy_sync_all(){
    $unix=new unix();
    $unix->framework_execute("exec.httptrack.php --sync-all",
        "webcopy.synchronize.progress",
        "webcopy.synchronize.log"
    );
}
function modsecurity_compile(){
    $unix=new unix();
    $serviceid=intval($_GET["modsecurity-compile"]);
    $unix->framework_execute("exec.nginx.single.php --modsecurity $serviceid",
        "modsecurity-compile.progress",
        "modsecurity-compile.log"
    );
}
function modsecurity_compile_all(){
    $unix=new unix();
    $unix->framework_execute("exec.nginx.single.php --modsecurity",
        "modsecurity-compile.progress",
        "modsecurity-compile.log"
    );
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

function mod_security_reconfigure(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/modsecurity.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/modsecurity.progress.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ModSecurity.install.php --build >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function mod_security_uninstall(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/modsecurity.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/modsecurity.progress.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ModSecurity.install.php --uninstall >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function upload_package(){
    $unix=new unix();

    $pname=$unix->shellEscapeChars($_GET["upload-package"]);
    $unix->framework_execute("exec.nginx.php --upgrade-from $pname","nginx.upgrade.progress", "nginx.upgrade.log");

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

function upgrade1(){
    $unix=new unix();
    $unix->framework_execute("exec.nginx.php --upgrade1","nginx.reconfigure.progress","nginx.reconfigure.progress.txt");


}

function status_info(){
    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    if(!is_file($nginx)){return false;}
    return $unix->framework_exec("exec.status.php --nginx --nowachdog");

}
function events(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/nginx.log.tmp";
	$sourceLog="/var/log/nginx/access.log";
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

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/nginx.log.cmd", $cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}
function status_info2():bool{
	$unix=new unix();
    return $unix->framework_exec("exec.status.php --nginx --nowachdog");
}
function remove_website(){
	
	$website=$_GET["remove-site"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php --remove \"$website\" --output=yes >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php --clean-reboot >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function reconfigure_php_fpm(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/fpm.reload.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/fpm.reload.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.lighttpd.php --fpm-reload --sleep=3 >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

    if(is_file("/etc/init.d/firehol")){
        $cmd="$nohup /etc/init.d/firehol restart >/dev/null 2>&1 &";
        writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
    }

}

function build_main(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --main >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function clean(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
    $ID=$_GET["clean"];
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php --clean-reboot >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php --clean-single $ID >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function execute_wizard(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-wizard.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/rnginx-wizard.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.wizard.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function compile_single(){
	$unix=new unix();
    $unix->framework_execute("exec.nginx.single.php {$_GET["compile-single"]}","nginx-single.progress","nginx-single.log");
	$GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($_GET["compile-single"]);

}




function disable_all(){
    $unix=new unix();
    $BaseWorkDirs[]="/etc/nginx/reverse.d";
    $BaseWorkDirs[]="/etc/nginx/stream.d";
    $BaseWorkDirs[]="/etc/nginx/sites-enabled";
    $BaseWorkDirs[]="/etc/nginx/local-sites";
    $BaseWorkDirs[]="/etc/nginx/local-sslsites";
    foreach ($BaseWorkDirs as $BaseWorkDir){
        $handle = opendir($BaseWorkDir);
        if($handle) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename == ".") {continue;}
                if ($filename == "..") {continue;}
                $targetFile = "$BaseWorkDir/$filename";
                if (is_dir($targetFile)) {continue;}
                if (!preg_match("#^[0-9]+-([0-9]+)\.conf$#", $filename, $re)) {continue;}
                @unlink($targetFile);
            }
        }
    }
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
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

function events_errors(){
	$pattern=trim(base64_decode($_GET["access-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath=$_GET["syslog-path"];
	$maxrows=0;
	$syslogpath="/var/log/nginx/error.log";
	$size=@filesize($syslogpath);
	if($size==0){@unlink($syslogpath);}
	$output="/usr/share/artica-postfix/ressources/logs/web/nginx.query.errors";
	$unix=new unix();
	
	if(!is_file($syslogpath)){
		if(is_file("/var/log/apache2/access-common.log")){
			$syslogpath="/var/log/apache2/access-common.log";
		}
	}
	
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}
	
	
	if(strlen($pattern)>1){
		$grep="$grepbin -i -E '$pattern' $syslogpath";
	}
		
	if($grep<>null){
		$cmd="$grep|$tail -n $maxrows >$output 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath >$output 2>&1";
	}
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod($output, 0755);
	
	
}

function events_all(){
	$pattern=trim(base64_decode($_GET["access-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath=$_GET["syslog-path"];
	$maxrows=0;
	$syslogpath="/var/log/nginx/access.log";
	$size=@filesize($syslogpath);
	if($size==0){@unlink($syslogpath);}
	$output="/usr/share/artica-postfix/ressources/logs/web/nginx.query";
	$unix=new unix();
	
	if(!is_file($syslogpath)){
		if(is_file("/var/log/apache2/access-common.log")){
			$syslogpath="/var/log/apache2/access-common.log";
		}
	}
	
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}


	if(strlen($pattern)>1){
		$grep="$grepbin -i -E '$pattern' $syslogpath";
	}
			
	if($grep<>null){
		$cmd="$grep|$tail -n $maxrows >$output 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath >$output 2>&1";
	}

	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod($output, 0755);
}

function conf_save(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	$servername=$_GET["replic-conf"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php \"$servername\" --replic-conf >/dev/null 2>&1 &");
	
	writelogs_framework("$nginx -c /etc/nginx/nginx.conf -t 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$nginx -c /etc/nginx/nginx.conf -t 2>&1",$results);
	foreach ($results as $line){
		writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#test is successful#", $line)){$OK=true;}
	}
	
	if(!$OK){
		writelogs_framework("FAILED",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
		return;
	}
	
	writelogs_framework("SUCCESS",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode("SUCCESS\n******************\n".@implode("\n", $results))."</articadatascgi>";

	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --force-restart >/dev/null 2>&1 &");
	
}

function conf_view(){
	$sitename=$_GET["conf-view"];
	writelogs_framework("conf_view $sitename",__FUNCTION__,__FILE__,__LINE__);
	foreach (glob("/etc/nginx/sites-enabled/freewebs-$sitename*") as $filename) {
		writelogs_framework("Copy $filename",__FUNCTION__,__FILE__,__LINE__);
		@copy($filename, "/usr/share/artica-postfix/ressources/logs/".basename($filename));
		$array["FILENAME"]=basename($filename);
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return;
	}
	
}

function sync_freewebs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --sync-squid");
	
}

function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	if(isset($_GET["enabled"])){
		if($_GET["enabled"]==0){
			$cmd="$nohup /etc/init.d/apache2 restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &";
			shell_exec($cmd);		
			$cmd="$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/monit restart >/dev/null 2>&1 &";
			shell_exec($cmd);
		}
	}
	
}

function delete_cache(){
	
	$directory=base64_decode($_GET["delete-cache"]);
	if(trim($directory)==null){return;}
	if(!is_dir($directory)){return;}
	$unix=new unix();
	if($unix->IsProtectedDirectory($directory,true)){return;}
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $rm -rf \"$directory\" >/dev/null 2>&1 &");
}
function www_events(){
	$servername=$_GET["servername"];
	$servername=str_replace(" ", "", $servername);
	$port=$_GET["port"];	
	$type=$_GET["type"];
	$filename="/var/log/apache2/$servername/nginx.access.log";
	if($type==2){
		$filename="/var/log/apache2/$servername/nginx.error.log";
	}
	$search=$_GET["search"];
	$unix=new unix();
	$search=$unix->StringToGrep($search);
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$refixcmd="$tail -n 2500 $filename";
	if($search<>null){
		$refixcmd=$refixcmd."|$grep --binary-files=text -i -E '$search'|$tail -n 500";
	}else{
		$refixcmd="$tail -n 500 $filename";
	}
	
	
	exec($refixcmd." 2>&1",$results);
	writelogs_framework($refixcmd." (".count($results).")",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
function mysqldb_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx-db.php --init");
	shell_exec("$nohup /etc/init.d/nginx-db restart >/dev/null 2>&1");
}
function uncompress_nginx(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress-nginx"];
	$nohup=$unix->find_program("nohup");
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
	}
	
	$cmd="$tar -xhf $FilePath -C /";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=nginx_version();
	shell_exec("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
}

function reconfigure_single(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$servername=$_GET["servername"];
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/nginx-$servername.log";
	@unlink($cachefile);
	@file_put_contents($cachefile, "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") **** RECONFIGURING $servername ****\n");
	@chmod($cachefile, 0777);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --reconfigure \"$servername\" >>$cachefile 2>&1 &");
}

function clean_websites(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.wizard.php --check-http >/dev/null 2>&1 &");
}


function nginx_version(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$nginx -V 2>&1",$results);

	foreach ($results as $key=>$value){
		if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
	}
}


function reconfigure_progress(){
    $unix=new unix();
    $unix->framework_execute("exec.nginx.php --reconfigure-all-reboot","nginx.reconfigure.progress","nginx.reconfigure.progress.txt");
	
}

function compile_destination(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-destination.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-destination.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.destinations.php {$_GET["cacheid"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($_GET["cacheid"]);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
}

function backup(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.dump.php --dump --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function restore(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.dump.php --restore \"{$_GET["filename"]}\" --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}



function refresh_caches(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-caches.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-caches.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --caches-status --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$servername=$_GET["servername"];
	$targetfile="/usr/share/artica-postfix/ressources/logs/access.log.$servername.tmp";
	$sourceLog="/var/log/apache2/$servername/nginx.access.log";
	
	$rp=intval($_GET["rp"]);
	writelogs_framework("access_real -> $rp" ,__FUNCTION__,__FILE__,__LINE__);


	$query=$_GET["query"];
	$grep=$unix->find_program("grep");


	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}


function purge_cache(){
	$unix=new unix();
	$ID=$_GET["purge-cache"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --purge-cache $ID >/dev/null 2>&1 &");
}

function export(){
    $unix=new unix();
    $ID=intval($_GET["export"]);
    $unix->framework_execute("exec.nginx.single.php --export $ID","nginx.export.$ID.progress","nginx.export.$ID.log");
}

function import(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --import-file >/usr/share/artica-postfix/ressources/logs/web/nginx.import.results 2>&1");	
}

function import_bulk(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --import-bulk >/usr/share/artica-postfix/ressources/logs/web/nginx.import-bulk.results 2>&1");
}

