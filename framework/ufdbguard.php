<?php
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string','');
ini_set('error_append_string','');
ini_set("log_errors", 1);
ini_set("error_log", "/var/log/php.log");
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["reindexes-catz"])){reindexes_catz();exit;}
if(isset($_GET["restore-id"])){backup_restore_id();exit;}
if(isset($_GET["backup-delete"])){backup_delete();exit;}
if(isset($_GET["backup-categories"])){backup_categories();exit;}
if(isset($_GET["wizardxbfpage"])){wizard_webfiltering_page();exit;}
if(isset($_GET["ufdbcat-service-events"])){ufdbcat_service_events();exit;}
if(isset($_GET["restore-categories"])){restore_categories();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}



if(isset($_GET["ufdbweb-simple-install"])){install_webservice_light();exit;}
if(isset($_GET["ufdbweb-simple-uninstall"])){uninstall_webservice_light();exit;}
if(isset($_GET["compile-all-categories"])){compile_all_categories();exit;}
if(isset($_GET["ufdbweb-simple-restart"])){restart_webservice_light();exit;}
if(isset($_GET["getversion"])){getversion();exit;}
if(isset($_GET["db-size"])){db_size();exit;}
if(isset($_GET["recompile"])){recompile();exit;}
if(isset($_GET["recompile-all"])){recompile_all();exit;}
if(isset($_GET["db-status"])){db_status();exit;}
if(isset($_GET["recompile-dbs"])){recompile_all();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["ad-dump"])){ad_dump();exit;}
if(isset($_GET["saveconf"])){ufdbguard_save_content();exit;}
if(isset($_GET["debug-groups"])){debug_groups();exit;}
if(isset($_GET["databases-percent"])){databases_percent();exit;}
if(isset($_GET["articawebfilter-database-version"])){artica_webfilter_database_version();exit;}
if(isset($_GET["remove-sessions-caches"])){remove_sessions_caches();exit;}
if(isset($_GET["ufdbtail-restart"])){ufdbtail_restart();exit;}
if(isset($_GET["services-status"])){ufdbguard_status();exit;}
if(isset($_GET["conf"])){ufdbguardconf();exit;}
if(isset($_GET["phishtank"])){phishtank();exit;}
if(isset($_GET["install-webpage"])){install_webservice();exit;}
if(isset($_GET["uninstall-webpage"])){uninstall_webservice();exit;}
if(isset($_GET["restart-webpage"])){restart_webservice();exit;}
if(isset($_GET["remove-all-categories"])){remove_all_categories();exit;}
if(isset($_GET["ufdbweb-events"])){ufdbweb_events();exit;}
if(isset($_GET["catgorize-manu"])){catgorize_manu();exit;}
if(isset($_GET["status-webpage"])){status_webservice();exit;}
if(isset($_GET["unlock-rules"])){unlock_rules();exit;}
if(isset($_GET["ppcategories-enable"])){personal_categories_enable();exit;}
if(isset($_GET["ppcategories-disable"])){personal_categories_disable();exit;}
if(isset($_GET["restart-service"])){restart_service();exit;}
if(isset($_GET["reload-service"])){reload_service();exit;}

if(isset($_GET["dump-members"])){dump_members();exit;}
if(isset($_GET["restart-client"])){restart_client();exit;}
foreach ($_GET as $num=>$line){$f[]="`$num=$line`";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function remove_sessions_caches(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup $rm -rf /home/squid/error_page_sessions/* >/dev/null 2>&1 &");
    shell_exec("$nohup $rm -rf /home/squid/error_page_cache/* >/dev/null 2>&1 &");

}

function ufdbtail_restart(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup /etc/init.d/ufdb-tail restart 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function restart_service(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    writelogs_framework("/etc/init.d/ufdb restart",__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$nohup /etc/init.d/ufdb restart >/dev/null 2>&1 &");
}
function reload_service(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    writelogs_framework("/etc/init.d/ufdb reload",__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$nohup /etc/init.d/ufdb reload >/dev/null 2>&1 &");
}

function ufdbguard_status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --ufdbguardd >/usr/share/artica-postfix/ressources/databases/ALL_UFDB_STATUS 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}
function UfdbCatsUpload(){
    $users=new usersMenus();
    $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    $UfdbCatsUploadFTPSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPSchedule"));
    $durations[60]="7 * * * *";
    $durations[120]="7 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
    $durations[240]="7 0,4,8,12,16,22 * * *";
    $durations[480]="7 0,8,16 * * *";
    $durations[720]="7 0,12 * * *";
    $durations[960]="7 0,16 * * *";
    $durations[1440]="7 0 * * *";
    $durations[2880]="7 0 */2 * *";
    $durations[5760]="7 0 */4 * *";
    $durations[10080]="7 0 * * 0";
    $durations[43200]="7 0 1 * *";


    if($UfdbCatsUpload==1){
        if(!is_file("/etc/cron.d/artica-ufdbcat-upload")){
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-ufdbcat-upload",$durations[$UfdbCatsUploadFTPSchedule],"exec.upload.categories.php");
            shell_exec("/etc/init.d/cron reload");
        }
    }else{
        if(is_file("/etc/cron.d/artica-ufdbcat-upload")){
            @unlink("/etc/cron.d/artica-ufdbcat-upload");
            shell_exec("/etc/init.d/cron reload");
        }

    }

}


function ufdbguardconf(){
    if(!is_file("/etc/squid3/ufdbGuard.conf")){
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdbGuard.conf", "/etc/squid3/ufdbGuard.conf no such file!");
        return;
    }
    @unlink("/usr/share/artica-postfix/ressources/logs/web/ufdbGuard.conf");
    @copy("/etc/squid3/ufdbGuard.conf","/usr/share/artica-postfix/ressources/logs/web/ufdbGuard.conf");
    @chmod("/usr/share/artica-postfix/ressources/logs/web/ufdbGuard.conf", 0755);
}

function dump_members(){
    $unix=new unix();
    $id=$_GET["dump-members"];
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/external_acl_squid_ldap.php --db $id >/usr/share/artica-postfix/ressources/logs/external_acl_squid_ldap.dump.$id 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function restart_client(){
    $unix=new unix();
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
}

function getversion(){
    $unix=new unix();
    $ufdbguardd=$unix->find_program("ufdbguardd");
    exec("$ufdbguardd -v 2>&1",$results);
    foreach ($results as $num=>$line){
        if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){echo "<articadatascgi>{$re[1]}</articadatascgi>";return;}
    }
}

function db_size(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-status");
}

function recompile(){
    @mkdir("/etc/artica-postfix/ufdbguard.recompile-queue",644,true);
    $db=$_GET["recompile"];
    @file_put_contents("/etc/artica-postfix/ufdbguard.recompile-queue/".md5($db)."db",$db);

}
function reindexes_catz(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.backup-categories4x.php --indexes >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function catgorize_manu(){
    $category_id=intval($_GET["catgorize-manu"]);
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$category_id.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/categorize.manu.$category_id.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.categorize.php $category_id >{$ARRAY["LOG_FILE"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function compile_all_categories(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.compile.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.compile.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.compile.categories.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function wizard_webfiltering_page(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdberror.compile.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdberror.compile.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.wizard.webfiltering.page.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    writelogs_framework("DONE !",__FUNCTION__,__FILE__,__LINE__);

}

function remove_all_categories(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.compile.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.compile.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.compile.categories.php --remove-all >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function backup_delete(){
    $ID=$_GET["backup-delete"];
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.backup-categories4x.php --delete $ID >/dev/null 2>&1");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function backup_restore_id(){
    $ID=$_GET["restore-id"];
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.backup-categories4x.php --restore-id $ID >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function backup_categories(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.backup-categories4x.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function restore_categories(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $filepath=$_GET["restore-categories"];
    if(isset($_GET["uploaded"])){$add=" --uploaded";}
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.backup-categories4x.php --restore  \"$filepath\" $add >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}





function unlock_rules(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-http-build.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function install_webservice(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-http.php --install-web >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function personal_categories_enable(){

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb.enable.php --ppcategories-enable >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function personal_categories_disable(){

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb.enable.php --ppcategories-disable >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}


function install_webservice_light(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbweb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbweb.enable.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-lighthttp.php --install-web >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function uninstall_webservice_light(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbweb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbweb.enable.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-lighthttp.php --uninstall-web >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function restart_webservice_light(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbweb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbweb.enable.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-lighthttp.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function status_webservice(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --ufdbguard-http >/usr/share/artica-postfix/ressources/logs/web/APP_UFDB_HTTP.status 2>&1");
    shell_exec($cmd);
    echo "/usr/share/artica-postfix/ressources/logs/web/APP_UFDB_HTTP.status == ".filesize("/usr/share/artica-postfix/ressources/logs/web/APP_UFDB_HTTP.status")." bytes..\n";
    @chmod("/usr/share/artica-postfix/ressources/logs/web/APP_UFDB_HTTP.status",0755);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}



function restart_webservice(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.web.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.web.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-http.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function uninstall_webservice(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdb-http.php --uninstall-web >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}


function ufdbcat_disable_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.remove.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.remove.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdbcat.php --remove-progress --ouptut >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function install_tgz(){
    $migration=null;


    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ufdb.install.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ufdb.install.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ufdb.install.php --install {$_GET["key"]} {$_GET["OS"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function phishtank(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/phishtank.build.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/phishtank.build.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --phistank --force --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}



function ufdbcat_connect_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.connect.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.connect.log";


    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdbcat.php --connect --ouptut --force >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}



function ufdbcat_install_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/dnscatz.install.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/dnscatz.install.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdbcat.php --install-progress --ouptut --force >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function recompile_all(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-recompile-dbs >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function db_status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.squidguard.php --databases-status >/dev/null 2>&1");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function service_cmds(){
    $action=$_GET["service-cmds"];
    $unix=new unix();
    if($action=="reconfigure"){
        $php5=$unix->LOCATE_PHP5_BIN();
        exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build --verbose 2>&1",$results);
        echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
        return;

    }

    $results[]="/etc/init.d/ufdb $action 2>&1";
    exec("/etc/init.d/ufdb $action 2>&1",$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function ad_dump(){
    $ruleid=$_GET["ad-dump"];
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --dump-adrules $ruleid >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}




function ufdbguard_save_content(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $ufdbguardd=$unix->find_program("ufdbguardd");
    $datas=base64_decode($_GET["saveconf"]);
    writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
    if($datas==null){
        echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
        return;
    }
    @file_put_contents("/etc/squid3/ufdbGuard-temp.conf", $datas);
    @chown("/etc/squid3/ufdbGuard-temp.conf", "squid");

    $cmd="$ufdbguardd -c /etc/squid3/ufdbGuard-temp.conf -C verify 2>&1";

    exec($cmd,$results);
    $ERR=array();
    writelogs_framework($cmd ." ->".count($results),__FUNCTION__,__FILE__,__LINE__);
    $error=false;
    foreach ($results as $num=>$ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);
        if(!preg_match("#(ERROR:|FATAL ERROR)#", $ligne)){continue;}
        writelogs_framework("ERROR ***** > $ligne",__FUNCTION__,__FILE__,__LINE__);
        $ERR[]=$ligne;
        $error=true;
    }


    if($error){echo "<articadatascgi>". base64_encode(@implode("\n",$ERR))."</articadatascgi>";return;}
    writelogs_framework("/etc/squid3/ufdbGuard-temp.conf -> /etc/squid3/ufdbGuard.conf",__FUNCTION__,__FILE__,__LINE__);
    @copy("/etc/squid3/ufdbGuard-temp.conf", "/etc/squid3/ufdbGuard.conf");
    @chown("/etc/squid3/ufdbGuard.conf", "squid");
    squid_admin_mysql(1, "Reloading Webfiltering service", null,__FILE__,__LINE__);
    shell_exec("$nohup /etc/init.d/ufdb reload >/dev/null 2>&1 &");
}
function debug_groups(){
    $f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
    foreach ($f as $num=>$ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        if(!preg_match("#execuserlist\s+\"(.+?)\"#", $ligne,$re)){continue;}
        $path=$re[1];
        $cmds[$path]=true;
    }

    foreach ($cmds as $ligne){
          exec("$num --verbose 2>&1",$results);

    }
    echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";


}

function artica_webfilter_database_version(){
    $file="/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD";
    $STATUS=unserialize(@file_get_contents($file));
    $myVersion=intval($STATUS["LAST_DOWNLOAD"]["TIME"]);
    echo "<articadatascgi>$myVersion</articadatascgi>";

}

function databases_percent(){
    $unix=new unix();

    if(is_file("/etc/artica-postfix/UFDB_DB_STATS")){
        if($unix->file_time_min("/etc/artica-postfix/UFDB_DB_STATS")<3){
            echo "<articadatascgi>". base64_encode(@file_get_contents("/etc/artica-postfix/UFDB_DB_STATS"))."</articadatascgi>";
            return;
        }
    }


    $MAX=47;
    $files=$unix->dirdir("/var/lib/ftpunivtlse1fr");

    $c=0;
    while (list ($dir, $line) = each ($files)){
        if(is_link($dir)){continue;}
        $database_path="$dir/domains.ufdb";
        if(!is_file($database_path)){continue;}
        $cat=basename($dir);
        $size=@filesize("$dir/domains.ufdb");
        if($size<290){continue;}
        $time=filemtime("$dir/domains.ufdb");
        $UFDB[$time]=true;
        $c++;

    }

    krsort($UFDB);
    while (list ($time, $line) = each ($UFDB)){
        $xtime=$time;
        break;
    }
    $ARRAY["TLSE"]["LAST_TIME"]=$xtime;
    $ARRAY["TLSE"]["MAX"]=$MAX;
    $ARRAY["TLSE"]["COUNT"]=$c;

    $files=$unix->dirdir("/var/lib/ufdbartica");

    $MAX=144;
    $c=0;
    $UFDB=array();

    while (list ($dir, $line) = each ($files)){
        if(is_link($dir)){continue;}
        $database_path="$dir/domains.ufdb";
        if(!is_file($database_path)){continue;}
        $cat=basename($dir);
        $size=@filesize("$dir/domains.ufdb");
        if($size<290){continue;}
        $time=filemtime("$dir/domains.ufdb");
        $UFDB[$time]=true;
        $c++;

    }


    krsort($UFDB);
    while (list ($time, $line) = each ($UFDB)){
        $xtime=$time;
        break;
    }

    $ARRAY["ARTICA"]["LAST_TIME"]=$xtime;
    $ARRAY["ARTICA"]["MAX"]=$MAX;
    $ARRAY["ARTICA"]["COUNT"]=$c;


    $MAX=150;
    $c=0;
    $UFDB=array();
    $files=$unix->DirFiles("/home/artica/categories_databases");

    $c=0;
    while (list ($filename, $line) = each ($files)){
        $filepath="/home/artica/categories_databases/$filename";
        if($filename=="CATZ_ARRAY"){continue;}
        if(is_link("$filepath")){continue;}

        $cat=basename($filepath);
        $size=@filesize($filepath);
        if($size<290){continue;}
        $UFDB[$time]=true;
        $c++;

    }
    krsort($UFDB);
    while (list ($time, $line) = each ($UFDB)){
        $xtime=$time;
        break;
    }
    $ARRAY["CATZ"]["LAST_TIME"]=$xtime;
    $ARRAY["CATZ"]["MAX"]=$MAX;
    $ARRAY["CATZ"]["COUNT"]=$c;

    @file_put_contents("/etc/artica-postfix/UFDB_DB_STATS", serialize($ARRAY));

    echo "<articadatascgi>". base64_encode(serialize($ARRAY))."</articadatascgi>";

}
function ufdbcat_service_events(){
    $search=trim(base64_decode($_GET["search"]));
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    $target_file="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.log";
    if(is_numeric($_GET["rp"])){$rp=$_GET["rp"]+50;}

    if($search==null){

        $cmd="$grep --binary-files=text -i -E '[0-9\s\-\:]+\s+\[[0-9]+\]\s+' /var/log/ufdbcat/ufdbguardd.log | $tail -n $rp >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }



    $search=$unix->StringToGrep($search);


    $cmd="$grep --binary-files=text -i -E '[0-9\s\-\:]+\s+\[[0-9]+\]\s+' /var/log/ufdbcat/ufdbguardd.log|$grep --binary-files=text -i -E '$search' |$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");



}

function ufdbweb_events(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/ufdbweb.log.tmp";
    $sourceLog="/var/log/ufdb-http.log";
    $grep=$unix->find_program("grep");
    $LinesZ=".*";
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];
    $cmd="$grep --binary-files=text -Ei \"$LinesZ\" $sourceLog|$tail -n $rp >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$LinesZ\" $sourceLog|$grep --binary-files=text -Ei \"$pattern\" | $tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbweb.log.cmd", $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);


}