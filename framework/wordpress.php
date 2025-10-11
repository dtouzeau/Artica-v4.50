<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["ch-admin"])){ch_admin();exit;}
if(isset($_GET["is_installed"])){is_installed();exit;}
if(isset($_GET["version"])){version();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["import"])){import();exit;}
if(isset($_GET["backup-now"])){backup_now();exit;}
if(isset($_GET["backup-delete"])){backup_delete();exit;}


if(isset($_GET["build-new-sites"])){build_new_sites();exit;}
if(isset($_GET["build-sites"])){build_sites();exit;}
if(isset($_GET["remove-site"])){remove_site();exit;}
if(isset($_GET["enable-checks"])){enable_checks();exit;}
if(isset($_GET["build-single"])){build_single();exit;}
if(isset($_GET["ssl"])){build_ssl();exit;}
if(isset($_GET["core-status"])){core_status();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["readonly-off"])){readonly_off();exit;}
if(isset($_GET["readonly-on"])){readonly_on();exit;}
if(isset($_GET["nginx-site"])){compile_nginx_site();exit;}
if(isset($_GET["letsencrypt"])){letsencrypt_build();exit;}
if(isset($_GET["plugins-list"])){plugins_list();exit;}
if(isset($_GET["plugins-enable"])){plugins_enable();exit;}
if(isset($_GET["plugins-disable"])){plugins_disable();exit;}
if(isset($_GET["plugins-install"])){plugins_install();exit;}
if(isset($_GET["plugins-uninstall"])){plugins_uninstall();exit;}

if(isset($_GET["templates-list"])){templates_list();exit;}
if(isset($_GET["templates-enable"])){templates_enable();exit;}
if(isset($_GET["templates-install"])){templates_install();exit;}
if(isset($_GET["templates-uninstall"])){templates_uninstall();exit;}

if(isset($_GET["updates-list"])){updates_list();exit;}
if(isset($_GET["updates-install"])){updates_install();exit;}
if(isset($_GET["wp-duplicator-back"])){wordpress_duplicator_container();}
if(isset($_GET["wp-duplicator-insta"])){wordpress_duplicator_installer();exit;}
if(isset($_GET["wp-restore"])){wordpress_duplicator_restore();exit;}
if(isset($_GET["wordpress-fs"])){wordpress_fs();exit;}
if(isset($_GET["cache"])){caches_dir();exit;}
if(isset($_GET["import-backup"])){backup_import();exit;}
if(isset($_GET["restore-backup"])){backup_restore();exit;}
if(isset($_GET["site-replace"])){replace_insite();exit;}
if(isset($_GET["repair-db"])){repair_db();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function is_installed(){
	if(is_dir("/usr/share/wordpress-src/wp-includes")){ echo "<articadatascgi>TRUE</articadatascgi>"; return; }
	echo "<articadatascgi>FALSE</articadatascgi>";
	
}
function replace_insite(){
    $siteid=intval($_GET["site-replace"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --site-replace $siteid","wordpress.$siteid.replace.progress",
        "wordpress.$siteid.replace.log");
}
function repair_db():bool{
    $siteid=intval($_GET["repair-db"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --repair-db $siteid","wordpress.mysql.$siteid.progress","wordpress.mysql.$siteid.log");
    return true;
}

function build_single(){
    $unix=new unix();
    $ID=intval($_GET["build-single"]);
    $unix->framework_execute("exec.wordpress.install.php --nginx $ID","wordpress.$ID.progress",
        "wordpress.$ID.progress.txt");
}
function caches_dir(){
    $ID=intval($_GET["cache"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --caches-dir $ID",
        "nginx.cache.$ID.progress",
        "nginx.cache.$ID.log");
}
function core_status(){
    $ID=intval($_GET["core-status"]);
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $target=PROGRESS_DIR."/wp.core-status.$ID";
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root core version --extra --path=$path >$target 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chown($target,"www-data");
}

function readonly_off(){
    $ID=$_GET["readonly-off"];
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --readonly-off $ID >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function readonly_on(){
    $ID=$_GET["readonly-on"];
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --readonly-on $ID >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function compile_nginx_site(){
    $unix=new unix();
    $ID=intval($_GET["nginx-site"]);
    $unix->framework_execute("exec.wordpress.install.php --nginx $ID","wordpress.single.$ID","wordpress.single.$ID.log");
}

function ch_admin(){
    $ID=intval($_GET["ch-admin"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --ch-admin $ID","wordpress.single.$ID","wordpress.single.$ID.log");
}
function plugins_install(){
    $ID=intval($_GET["plugins-install"]);
    $unix=new unix();
    $filename=$_GET["filename"];
    if($ID==0){
        writelogs_framework("plugins-install == 0 !!!",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $unix->framework_execute("exec.wordpress.install.php --install-plugin $ID \"$filename\"","wordpress.single-install.$ID","wordpress.single-install.$ID.log");
}
function templates_install(){
    $ID=intval($_GET["templates-install"]);
    $unix=new unix();
    $filename=$_GET["filename"];
    if($ID==0){
        writelogs_framework("templates-install == 0 !!!",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $unix->framework_execute("exec.wordpress.install.php --install-theme $ID \"$filename\"","wordpress.single-install.$ID","wordpress.single-install.$ID.log");
    return true;
}


function plugins_uninstall(){
    $ID=intval($_GET["plugins-uninstall"]);
    $unix=new unix();
    $plugin=$_GET["plugin"];
    if($ID==0){
        writelogs_framework("plugins-uninstall == 0 !!!",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $unix->framework_execute("exec.wordpress.install.php --uninstall-plugin $ID \"$plugin\"","wordpress.single-install.$ID","wordpress.single-install.$ID.log");
}
function templates_uninstall(){
    $ID=intval($_GET["templates-uninstall"]);
    $unix=new unix();
    $template=$_GET["template"];
    if($ID==0){
        writelogs_framework("templates-uninstall == 0 !!!",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $unix->framework_execute("exec.wordpress.install.php --uninstall-theme $ID \"$template\"","wordpress.single-install.$ID","wordpress.single-install.$ID.log");
    return true;
}

function plugins_enable(){
    $ID=intval($_GET["plugins-enable"]);
    $plugin=$_GET["plugin"];
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root plugin activate $plugin --path=$path >/dev/null 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function plugins_disable(){
    $ID=intval($_GET["plugins-disable"]);
    $plugin=$_GET["plugin"];
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root plugin deactivate $plugin --path=$path >/dev/null 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function templates_enable(){
    $ID=intval($_GET["templates-enable"]);
    $template=$_GET["template"];
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root theme activate $template --path=$path >/dev/null 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function plugins_list(){
    $ID=intval($_GET["plugins-list"]);
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $target=PROGRESS_DIR."/wp.plugins-list.$ID";
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root plugin list --format=json --path=$path >$target 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chown($target,"www-data");
}
function templates_list(){
    $ID=intval($_GET["templates-list"]);
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $target=PROGRESS_DIR."/wp.templates-list.$ID";
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root theme list --format=json --path=$path >$target 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chown($target,"www-data");
}
function updates_install(){
    $ID=intval($_GET["updates-install"]);
    $version=$_GET["VersionToUpgrade"];
    writelogs_framework("Upgrade $ID to $version",__FUNCTION__,__FILE__,__LINE__);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --upgrade-core $ID \"$version\"",
        "wordpress.single-upgrade.$ID", "wordpress.single-upgrade.$ID.log");
}

function updates_list(){
    $ID=intval($_GET["updates-list"]);
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $target=PROGRESS_DIR."/wp.updates-list.$ID";
    $path="/home/wordpress_sites/site$ID";
    $cmd="$wp_cli_phar --allow-root core check-update --format=json --path=$path >$target 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chown($target,"www-data");
}
function letsencrypt_build(){
    $unix=new unix();
    $ID=intval($_GET["letsencrypt"]);
    $unix->framework_execute("exec.wordpress.letsencrypt.php $ID",
        "wordpress.letsencrypt.progress.$ID",
        "wordpress.letsencrypt.log.$ID");

}


function searchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
    //FIREHOL:RULE-2:IN=eth0 OUT= MAC=00:0c:29:26:46:3d:00:26:b9:78:8f:0a:08:00 SRC=192.168.1.173 DST=192.168.1.180 LEN=91 TOS=0x00 PREC=0x00 TTL=128 ID=23066 DF PROTO=TCP SPT=445 DPT=29089 WINDOW=510 RES=0x00 ACK PSH URGP=0


    if($IN<>null){$IN_P=".*?IN=$IN.*?";}
    if($OUT<>null){$OUT_P=".*?OUT=$OUT.*?";}
    if($MAC<>null){$MAC_P=".*?MAC=.*?$MAC.*?";}
    if($SRC<>null){$SRC_P=".*?SRC=$SRC.*?";}
    if($DST<>null){$DST_P=".*?DST=$DST.*?";}
    if($SRCPORT<>null){$SRCPORT_P=".*?SPT=$SRCPORT.*?";}
    if($DSTPORT<>null){$DSTPORT_P=".*?DPT=$DSTPORT.*?";}
    if($PROTO<>null){$PROTO_P=".*?PROTO=$PROTO.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$TERM_P}{$IN_P}{$OUT_P}{$MAC_P}{$SRC_P}{$DST_P}{$PROTO_P}{$SRCPORT_P}{$DSTPORT_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/fw-nginx.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/firehol-wordpress.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function build_ssl(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $ID=intval($_GET["ssl"]);
    $GLOBALS["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/wordpress.$ID.progress";
    $GLOBALS["LOG_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/wordpress.$ID.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOG_FILE"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --ssl $ID >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function install(){

	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.install.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wordpress.install.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --install >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function build_sites(){
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --build",
        "wordpress.build.progress","wordpress.build.progress.txt");

}
function build_new_sites(){
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --build-new",
        "wordpress.build.progress","wordpress.build.progress.txt");
}



function enable_checks(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --enable-checks >/var/log/wordpress.enable.log 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function remove_site(){
    $unix=new unix();
    $ID=intval($_GET["remove-site"]);
    $unix->framework_execute("exec.wordpress.install.php --remove $ID",
        "wordpress.remove.progress",
        "wordpress.remove.progress.txt"
    );

}


function uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.install.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wordpress.install.progress.txt";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOG_FILE"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --uninstall >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function export(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$servername=$_GET["servername"];
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.export.$servername.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wordpress.export.$servername.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress-backup.php --export \"$servername\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function backup_import(){

    $fname=$_GET["import-backup"];
    $siteid=intval($_GET["siteid"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --backup-uploaded \"$fname\" $siteid",
        "wordpres.$siteid.restore.backup.progress",
        "wordpres.$siteid.restore.backup.log"
    );
}
function backup_restore(){
    //restore-backup=$ID&siteid=$siteid
    $backupid=$_GET["restore-backup"];
    $siteid=intval($_GET["siteid"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --backup-restore $backupid $siteid",
        "wordpres.$siteid.restore.backup.progress",
        "wordpres.$siteid.restore.backup.log"
    );
}
function backup_delete():bool{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $ID=intval($_GET["backup-delete"]);
    $cmd="$php5 /usr/share/artica-postfix/exec.wordpress.install.php --backup-delete $ID";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function backup_now():bool{
    $ID=$_GET["backup-now"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.backup.$ID.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.backup.$ID.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress.install.php --backup $ID >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	return true;
}

function import(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$filename=$_GET["filename"];
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.import.$filename.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wordpress.import.$filename.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wordpress-backup.php --import \"$filename\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function version(){
	@chmod("/usr/share/artica-postfix/bin/wp-cli.phar",0755);
	$cmd="/usr/share/artica-postfix/bin/wp-cli.phar --allow-root core version --path=/usr/share/wordpress-src 2>&1";
	
	$version=exec($cmd);
	if(preg_match("#wp core download#", $version)){
		return null;
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".exec($cmd)."</articadatascgi>";
	
}
function wordpress_duplicator_container(){
    $fname=$_GET["wp-duplicator-back"];
    $ARROOTR    = ARTICA_ROOT;
    $siteid=$_GET["siteid"];
    $filepath   = "$ARROOTR/ressources/conf/upload/$fname";
    if(!is_file($filepath)){return false;}
    $target_path = "/home/wordpress/wp-duplicator/$siteid/$fname";
    if(!is_dir("/home/wordpress/wp-duplicator/$siteid")){
        @mkdir("/home/wordpress/wp-duplicator/$siteid",755,true);
    }
    if(is_file($target_path)){@unlink($target_path);}
    @copy($filepath,$target_path);
    @unlink($filepath);
    return true;
}
function wordpress_duplicator_installer(){
    $fname=$_GET["wp-duplicator-insta"];
    $ARROOTR    = ARTICA_ROOT;
    $siteid=$_GET["siteid"];
    $filepath   = "$ARROOTR/ressources/conf/upload/$fname";
    if(!is_file($filepath)){return false;}
    $target_path = "/home/wordpress/wp-duplicator/$siteid/installer.php";
    if(!is_dir("/home/wordpress/wp-duplicator/$siteid")){
        @mkdir("/home/wordpress/wp-duplicator/$siteid",755,true);
    }
    if(is_file($target_path)){@unlink($target_path);}
    @copy($filepath,$target_path);
    @unlink($filepath);
    return true;
}
function wordpress_duplicator_restore(){
    $ID=intval($_GET["wp-restore"]);
    $unix=new unix();
    $unix->framework_execute("exec.wordpress.install.php --wp-restore $ID",
        "wordpress.build.progress",
        "wordpress.build.progress.txt"
    );
}

function wordpress_fs(){

    $MAIN=array();
    $BaseWorkDir="/etc/nginx/wordpress";
    $handle = opendir($BaseWorkDir);

    if(!$handle){
        return false;}
    while (false !== ($filename = readdir($handle))) {
        if ($filename == ".") {
            continue;
        }
        if ($filename == "..") {
            continue;
        }
        $targetFile = "$BaseWorkDir/$filename";
        if (is_dir($targetFile)) {
            continue;
        }
        if(!preg_match("#^([0-9]+)\.conf$#",$filename,$re)){
            continue;}
        $MAIN[$re[1]]=true;
    }

    $tfile=PROGRESS_DIR."/wordpress-fs.dump";
    @file_put_contents($tfile,serialize($MAIN));
    @chmod($tfile,0755);
    return true;
}

