<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.wordpress.antivirus.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.nginx.certificate.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
$GLOBALS["ONLYCONFIG"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--onlyconfig#",implode(" ",$argv))){$GLOBALS["ONLYCONFIG"]=true;}


if($argv[1]=="--ch-admin"){ch_admin($argv[2]);exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--install-plugin"){install_plugin($argv[2],$argv[3]);exit;}
if($argv[1]=="--install-theme"){install_theme($argv[2],$argv[3]);exit;}
if($argv[1]=="--uninstall-theme"){uninstall_theme($argv[2],$argv[3]);exit;}
if($argv[1]=="--uninstall-plugin"){uninstall_plugin($argv[2],$argv[3]);exit;}
if($argv[1]=="--upgrade-core"){upgrade_core($argv[2],$argv[3]);exit;}

if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--build-new"){build_new_sites();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--remove"){remove_website($argv[2]);exit;}
if($argv[1]=="--backup"){backup_website($argv[2]);exit;}
if($argv[1]=="--backup-delete"){backup_delete($argv[2]);exit;}



if($argv[1]=="--backup-uploaded"){backup_restore_website($argv[2],$argv[3]);exit;}
if($argv[1]=="--nginx"){build_ngnix($argv[2]);exit;}
if($argv[1]=="--enable-checks"){enable_checks();exit;}

if($argv[1]=="--backup-all"){backup_all();exit;}
if($argv[1]=="--clean-backup"){Clean_backups($argv[2]);exit;}
if($argv[1]=="--readonly-on"){wordpress_lock($argv[2]);exit;}
if($argv[1]=="--readonly-off"){wordpress_unlock($argv[2]);exit;}
if($argv[1]=="--ssl"){wordpress_ssl($argv[2]);exit;}
if($argv[1]=="--cron-event"){wordpress_cron();exit;}
if($argv[1]=="--antivirus"){CleanViruses();exit;}
if($argv[1]=="--create"){create_site($argv[2]);exit;}
if($argv[1]=="--listen"){wordpress_listen();exit;}
if($argv[1]=="--disabled"){build_disabled_websites();exit;}
if($argv[1]=="--wp-restore"){wp_restore($argv[2]);exit;}
if($argv[1]=="--fix-missdb"){fix_miss_config_db();exit;}
if($argv[1]=="--php-viruses"){ScanBadPhpFiles();exit;}
if($argv[1]=="--caches-dir"){nginx_cache_dir_build($argv[2]);exit;}
if($argv[1]=="--backup-restore"){backup_restore($argv[2],$argv[3]);exit;}
if($argv[1]=="--site-replace"){replace_content($argv[2]);exit;}
if($argv[1]=="--repair-db"){repair_database($argv[2]);exit;}

download();

function wordpress_listen(){
    $unix=new unix();
    $WordPressListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressListenInterface"));
    if($WordPressListenInterface==null){$WordPressListenInterface="eth0";}

    $WordPressListenIP=$unix->InterfaceToIPv4($WordPressListenInterface);
    if($WordPressListenIP==null){$WordPressListenIP="0.0.0.0";}
    echo "Listen $WordPressListenInterface: $WordPressListenIP\n";
}

function build_progress_backup($text,$pourc){
    if(!isset($GLOBALS["INSTANCE_ID"])){return;}
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/wordpress.backup.{$GLOBALS["INSTANCE_ID"]}.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/wordpress.backup.{$GLOBALS["INSTANCE_ID"]}.progress",0755);

}
function build_progress_install($text,$pourc){
    if(!isset($GLOBALS["INSTANCE_ID"])){return;}
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"wordpress.single-install.{$GLOBALS["INSTANCE_ID"]}");
}
function build_progress_upgrade($text,$pourc){
    if(!isset($GLOBALS["INSTANCE_ID"])){return;}
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"wordpress.single-upgrade.{$GLOBALS["INSTANCE_ID"]}",true);
}
function build_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/wordpress.install.progress", serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0755);

}
function build_progress_build($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"wordpress.build.progress");
    exec_compile_nginx_config_progress($pourc,$text);
    build_progress_single($text,$pourc);
}

function build_progress_single($text,$pourc){
    if(!isset($GLOBALS["INSTANCE_ID"])){return;}
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"wordpress.{$GLOBALS["INSTANCE_ID"]}.progress");
}


function build_progress_remove($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"wordpress.remove.progress");
}
function repair_database_progress($ID,$pourc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"wordpress.mysql.$ID.progress");
    return true;
}
function repair_database($ID):bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    repair_database_progress($ID,10,"{mysql_repair}");

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $database_name=$ligne["database_name"];

    if($GLOBALS["VERBOSE"]){echo "Site $ID == $database_name\n";}
    $q=new mysql();
    $TABLES_LIST=$q->TABLES_LIST($database_name);
    $ct=count($TABLES_LIST);
    $c=0;
    foreach ($TABLES_LIST as $tbname=>$none){
        $c++;
        $ppr=round( ($c/$ct)*100 );
        echo "$ppr% $tbname\n";
        if($ppr>95){$ppr=95;}
        repair_database_progress($ID,$ppr,"{mysql_repair} $tbname");
        shell_exec("$php /usr/share/artica-postfix/exec.myisamchk.php $database_name $tbname");
        $q->QUERY_SQL("REPAIR TABLE $tbname",$database_name);

    }
    repair_database_progress($ID,100,"{mysql_repair} {success}");
    return true;

}

function wp_restore($siteid){
    $srcdir   = "/home/wordpress/wp-duplicator/$siteid";
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $exrm="$rm -rf $srcdir";

    build_progress_build("{restore} site.$siteid",15);
    $install_file=$srcdir."/installer.php";
    if(!is_file($install_file)){
        echo "Unable to stat $install_file\n";
        build_progress_build("{restore} site.$siteid {failed}",110);
        shell_exec($exrm);
        return false;
    }
    $ARCHIVE_FILENAME=null;
    $f=explode("\n",@file_get_contents($install_file));
    foreach ($f as $line){
        if(preg_match("#const ARCHIVE_FILENAME.*?=.*?(.+?);#",$line,$re)){
            $ARCHIVE_FILENAME=trim($re[1]);
            $ARCHIVE_FILENAME=str_replace("'","",$ARCHIVE_FILENAME);
            $ARCHIVE_FILENAME=str_replace('"','',$ARCHIVE_FILENAME);

        }
    }
    if($ARCHIVE_FILENAME==null){
        echo "Corrupted installer file $install_file\n";
        build_progress_build("{restore} site.$siteid {failed}",110);
        shell_exec($exrm);
        return false;
    }
    echo "Using the package $ARCHIVE_FILENAME\n";
    $ARCHIVE_FILENAME_PATH="$srcdir/$ARCHIVE_FILENAME";
    if(!is_file($ARCHIVE_FILENAME_PATH)){
        echo "Unable to stat $ARCHIVE_FILENAME_PATH\n";
        build_progress_build("{restore} site.$siteid {failed}",110);
        shell_exec($exrm);
        return false;
    }
    $sitepath=get_sitepath($siteid);
    echo "Site location = $sitepath\n";
    build_progress_build("{unlock} site.$siteid",30);
    wordpress_unlock($siteid);
    echo "Empty $sitepath";
    build_progress_build("{remove} site.$siteid",40);
    if(is_dir($sitepath)){@mkdir($sitepath,0755,true);}
    shell_exec("$rm -rf $sitepath/*");
    build_progress_build("{installing} {container} site.$siteid",50);
    if(!@copy($install_file,"$sitepath/installer.php")){
        echo "Unable to copy $install_file to $sitepath/installer.php\n";
        build_progress_build("{restore} site.$siteid {failed}",110);
        shell_exec($exrm);
    }
    @copy($ARCHIVE_FILENAME_PATH,"$sitepath/$ARCHIVE_FILENAME");
    @chmod("$sitepath",0755);
    @chown("$sitepath","www-data");
    @chmod("$sitepath/installer.php",0755);
    @chown("$sitepath/installer.php","www-data");

    @chmod("$sitepath/$ARCHIVE_FILENAME",0755);
    @chown("$sitepath/$ARCHIVE_FILENAME","www-data");
    shell_exec($exrm);
    build_progress_build("{restore} site.$siteid {success}",100);
    return true;

}

function uninstall():bool{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $rm=$unix->find_program("rm");
    build_progress("{uninstalling}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWordpressManagement",0);
    firewall_delete_rules();
    system("$rm -rf /usr/share/wordpress-src");
    system("$rm -f /etc/nginx/wordpress/*");

    shell_exec("$php5 /usr/share/artica-postfix/exec.wordpress-phpfpm.php --uninstall");

    build_progress("{uninstalling}",50);


    $crons[]="wordpress-backup";
    $crons[]="wordpress-cron";
    $crons[]="wordpress-antivirus";
    $crons[]="wordpress-letsenc";
    foreach ($crons as $cronfile){
        $unix->Popuplate_cron_delete($cronfile);
    }


    build_progress("{uninstalling}",90);
    if(is_file("/etc/rsyslog.d/fw-wordpress.conf")) {
        @unlink("/etc/rsyslog.d/fw-wordpress.conf");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

    build_progress("{uninstalling} {success}",100);
    return true;

}

function install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    build_progress("{installing}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWordpressManagement",1);
    $nginx=$unix->find_program("nginx");

    if(!is_file($nginx)){
        build_progress("{APP_NGINX} {not_installed}",110);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWordpressManagement",0);
        return false;
    }


    if(!is_file("/etc/init.d/nginx")){
        build_progress("{installing} {APP_NGINX}",15);
        system("/usr/sbin/artica-phpfpm-service -nginx-install");
        build_progress("{installing} {APP_NGINX} {done}",20);
        if(!is_file("/etc/init.d/nginx")){
            build_progress("{installing} {APP_NGINX} {failed}",110);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWordpressManagement",0);
            return false;
        }

    }

    if(!is_file("/etc/init.d/mysql")){
        build_progress("{installing} {APP_MYSQL}",20);
        system("$php5 /usr/share/artica-postfix/exec.mysql.start.php --install");
        build_progress("{installing} {APP_MYSQL} {done}",25);
        if(!is_file("/etc/init.d/mysql")){
            build_progress("{installing} {APP_MYSQL} {failed}",110);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWordpressManagement",0);
            return false;
        }
    }


    @mkdir("/home/artica/SQLITE",0755,true);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    @chmod("/home/artica/SQLITE/wordpress.db", 0644);
    @chown("/home/artica/SQLITE/wordpress.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);

    if(!$q->FIELD_EXISTS("wp_sites","site_size")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD site_size INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("wp_sites","version")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD version text NOT NULL DEFAULT '0.0'");
    }

    $sql="CREATE TABLE IF NOT EXISTS `wp_sites` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`WP_LANG` TEXT,
		`date_created` TEXT,
		`hostname` TEXT UNIQUE,
		`admin_user` TEXT,
		`admin_password` TEXT,
		`admin_email` TEXT,
		`database_name` TEXT,
		`database_user` TEXT,
		`database_password` TEXT,
		`database_error` TEXT,
		`aliases` TEXT,
		`wp_version` TEXT,
		`ssl` INTEGER NOT NULL DEFAULT 0,
		`letsencrypt` INTEGER NOT NULL DEFAULT 0,
		`ssl_certificate` TEXT,
		`enabled` INTEGER,
		`status` INTEGER,
		`cgicache` INTEGER,
		`readonly` INTEGER NOT NULL DEFAULT 0,
		`site_size` INTEGER NOT NULL DEFAULT 0,
		`wp_config` TEXT,
        `zmd5` TEXT
		)
		";



    //wp core config --dbname=wordpress --dbuser=user --dbpass=password --dbhost=localhost --dbprefix=w
    //--admin_user=supervisor --admin_password=strongpassword --admin_email= [--skip-email

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "Fatal: $q->mysql_error (".__LINE__.")\n";}


    if(!is_file("/usr/share/wordpress-src/wp-admin/index.php")){
        if(!download()) {
            build_progress("{installing} {failed}", 110);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWordpressManagement", 0);
            return false;
        }
    }


    if(!is_file("/etc/rsyslog.d/fw-wordpress.conf")){
        $f=array();
        $f[]="if  (\$msg contains 'fw-out-wordpress:') then {";
        $f[]="\t-/var/log/firewall-wordpress.log";
        $f[]="& stop";
        $f[]="}\n";
        @file_get_contents("/etc/rsyslog.d/fw-wordpress.conf",@implode("\n",$f));
        $unix=new unix();$unix->RESTART_SYSLOG(true);

    }
    $wp_cli=$unix->find_program("wp-cli.phar");
    $versions=array();
    exec("$wp_cli core  version --allow-root --path=/usr/share/wordpress-src 2>&1",$versions);
    $version=trim(@implode("",$versions));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_SRC_VERSION",$version);


    build_progress("{installing}...",40);
    $unix=new unix();
    $unix->Popuplate_cron_make("wordpress-backup","30 2 * * *","exec.wordpress.install.php --backup-all");
    $unix->Popuplate_cron_make("wordpress-cron","*/15 * * * *","exec.wordpress.install.php --cron-event");
    $unix->Popuplate_cron_make("wordpress-letsenc","15 2 */2 * *","exec.wordpress.letsencrypt.php --renews");

    system("/etc/init.d/cron reload");
    system("$php5 /usr/share/artica-postfix/exec.wordpress-phpfpm.php --install");
    build_progress("{installing}...",50);
    system("/usr/local/sbin/reverse-proxy -nginx-reconfigure -debug");
    build_progress("{installing}...",60);
    system("$php5 /usr/share/artica-postfix/exec.lighttpd.php --nginx-build");
    build_progress("{installing} {success}",100);
    return true;

}

function backup_all(){

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        squid_admin_mysql(1,"Peform Backup failed (license error)",null,"backup",__LINE__);
        return;
    }

    fix_miss_config_db();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->mysqli_fetch_array("SELECT ID FROM wp_sites WHERE enabled=1");
    $c=0;
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        if($ID==0){continue;}
        Clean_backups($ID);
        if(backup_website($ID)){$c++;}

    }

    if($c>0){
        squid_admin_mysql(2,"[wordpress] $c backuped websites...",null,__FILE__,__LINE__);
    }

}

function wordpress_cron(){

    CleanViruses();
    $Dirs["wp-admin"]=true;
    $Dirs["wp-content"]=true;
    $Dirs["wp-includes"]=true;



    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->QUERY_SQL("SELECT hostname,ID FROM wp_sites WHERE enabled=1");
    if(!$q->ok){echo "$q->mysql_error.\n";return false;}
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");

    foreach ($results as $index=>$ligne){

        $ID=intval($ligne["ID"]);
        $hostname=$ligne["hostname"];
        if($ID==0){continue;}
        $sitepath="/home/wordpress_sites/site{$ID}";
        $INSTALLED=true;
        foreach ($Dirs as $subdir=>$none){
            $PathToTest="$sitepath/$subdir";
            if(!is_dir($PathToTest)){
                $INSTALLED=false;
                squid_admin_mysql(0,"Fatal Wordpress site $hostname is not installed [install it]",
                    "Missing $PathToTest",__FILE__,__LINE__);
                $q->QUERY_SQL("UPDATE wp_sites SET status=0 WHERE ID=$ID");

                $ligne2=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
                if(!create_site($ligne2,0)){
                    squid_admin_mysql(0,"Fatal Wordpress site $hostname Unable to install",null,__FILE__,__LINE__);
                    break;
                }
                CheckUser($ligne2);
                if(!create_nginx_config($ligne2)){
                    squid_admin_mysql(0,"Fatal Wordpress site $hostname Unable to configure",null,__FILE__,__LINE__);
                    break;
                }
                ChmodSite($ligne2);
                break;
            }
        }

        if(!$INSTALLED){continue;}

        shell_exec("$wp_cli_phar cron event run --all --due-now --allow-root --path=$sitepath");
    }


}

function fix_miss_config_db(){

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->QUERY_SQL("SELECT * FROM wp_sites ORDER BY hostname");
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $database_name=trim($ligne["database_name"]);
        $database_user=trim($ligne["database_user"]);
        $database_password=trim($ligne["database_password"]);
        $sitepath=get_sitepath($ID);
        $MAIN=GetConfigFromWPConfig("$sitepath/wp-config.php");
        $DB_NAME=$MAIN["DB_NAME"];
        $DB_USER=$MAIN["DB_USER"];
        $DB_PASSWORD=$MAIN["DB_PASSWORD"];
        if($database_name==null){
            if($DB_NAME<>null){
                echo "$ID. Missing DB name\n";
                $q->QUERY_SQL("UPDATE wp_sites SET database_name='$DB_NAME' WHERE ID=$ID");
            }
        }
        if($database_user==null){
            if($DB_USER<>null){
                echo "$ID. Missing database_user name\n";
                $q->QUERY_SQL("UPDATE wp_sites SET database_user='$DB_USER' WHERE ID=$ID");

            }
        }
        if($database_password==null){
            if($DB_PASSWORD<>null){
                echo "$ID. Missing database_password name\n";
                $q->QUERY_SQL("UPDATE wp_sites SET database_password='$DB_PASSWORD' WHERE ID=$ID");

            }
        }
    }





}

function wordpress_get_config($ID){
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $sitepath="/home/wordpress_sites/site{$ID}";
    exec("$wp_cli_phar --allow-root --path=$sitepath");
}

function exec_compile_nginx_config_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"wordpress.single.{$GLOBALS["INSTANCE_ID"]}");
}

function wordpress_ssl($ID){
    $ID=intval($ID);
    $unix=new unix();
    if($ID==0){
        echo "Failed, wrong ID\n";
        build_progress_backup("{failed}",110);
        return;
    }

    $GLOBALS["INSTANCE_ID"]=$ID;
    build_progress_single("{starting} ID $ID",5);

}
function wordpress_unlock($ID){
    $unix=new unix();
    $chattr=$unix->find_program("chattr");
    $sitepath="/home/wordpress_sites/site{$ID}";
    shell_exec("$chattr -R -i $sitepath");
}

function wordpress_lock($ID,$prcstart=0){
    $unix=new unix();
    $find=$unix->find_program("find");
    $chattr=$unix->find_program("chattr");

    $NOREGEX[]="\/wp-content\/cache";

    echo "Scanning directory\n";

    $sitepath="/home/wordpress_sites/site{$ID}";
    $cmd[]="$find $sitepath -type f -name \"*.php\"";
    $cmd[]="-o -iname \"*.inc\"";
    $cmd[]="-o -iname \"*.css\"";
    $cmd[]="-o -iname \"*.js\"";
    $cmd[]="-o -iname \"*.jpeg\"";
    $cmd[]="-o -iname \"*.png\"";
    $cmd[]="-o -iname \"*.ttf\"";
    $cmd[]="-o -iname \"*.woff\"";
    $cmd[]="-o -iname \"*.eot\"";
    $cmd[]="-o -iname \"*.svg\"";
    $cmd[]="-o -iname \"*.json\"";
    $cmd[]="-o -iname \"*.md\"";
    $cmd[]="2>&1";

    exec(@implode(" ",$cmd),$filelist);
    $NumberOfFiles=count($filelist);
    $c=0;
    $prcOutput=0;
    foreach ($filelist as $path){
        reset($NOREGEX);
        foreach ($NOREGEX as $pattern){
            if(preg_match("#$pattern#",$path)){
                echo "Skip $path\n";
                continue;
            }
        }

        $c++;
        $prc=$c/$NumberOfFiles;
        $prc=round($prc*100);


        if($prc>$prcOutput){
            if($prcstart>0){
                build_progress_upgrade("{lock} $c {files} {$prcOutput}%", $prcstart);
            }
            echo "Lock $c files ({$prc}%)\n";
            $prcOutput=$prc;
        }
        shell_exec("$chattr +i $path");

    }
}
function Clean_backups($ID){
    $ID                     = intval($ID);
    $unix                   = new unix();
    $backupdirectory        = "/home/wordpress_backup";
    $WordPressMaxDaysBackup = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressMaxDaysBackup"));
    $q                      = new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    if($ID==0){
        echo "Failed, wrong ID\n";
        build_progress_backup("{failed}",110);
        return;
    }
    $GLOBALS["INSTANCE_ID"]=$ID;

    if($WordPressMaxDaysBackup==0){$WordPressMaxDaysBackup=7;}

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        squid_admin_mysql(1,"Cleaning Backup failed (license error)",null,"backup",__LINE__);
        return;
    }

    $sql="SELECT * FROM wp_backup WHERE siteid='$ID' ORDER BY backuptime DESC";
    $results=$q->QUERY_SQL($sql);

    $c=0;
    foreach ($results as $index=>$ligne){
        $ID             = $ligne["ID"];
        $backuptime     = $ligne["backuptime"];
        $filename       = $ligne["filename"];
        $fullpath       = $ligne["fullpath"];
        $c++;

        if($c<$WordPressMaxDaysBackup){continue;}
        echo "REMOVE: [$ID]($c/$WordPressMaxDaysBackup) on ".date("Y-m-d H:i:s",$backuptime)." $filename - $fullpath";
        if(!is_file($fullpath)){$fullpath="$backupdirectory/$filename";}

        if(!is_file($fullpath)){
            $q->QUERY_SQL("DELETE FROM wp_backup WHERE ID=$ID");
            continue;
        }

        @unlink($fullpath);
        if(is_file($fullpath)){
            echo "Unable to remove wordpress backup $filename, permission denied or read-only\n";
            squid_admin_mysql(1,"Unable to remove wordpress backup $filename, permission denied or read-only","backup",__LINE__);
            continue;
        }

        $q->QUERY_SQL("DELETE FROM wp_backup WHERE ID=$ID");
    }

}
function backup_restore_website_progress($prc,$text,$siteid){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"wordpres.$siteid.restore.backup.progress");
}
function replace_content_progress($prc,$text,$siteid){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"wordpress.$siteid.replace.progress");
}
function replace_content($siteid){
    $unix=new unix();

    if(intval($siteid)==0){
        replace_content_progress(110,"Bad config -1",$siteid);
        return false;
    }

    $wpcliphar=$unix->find_program("wp-cli.phar");
    if(!is_file($wpcliphar)){
        replace_content_progress(110,"wp-cli {no_such_file}",$siteid);
        return false;
    }

    $tmp=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WPSITES_REPLACESTRS");
    $MAIN=unserialize(base64_decode($tmp));
    if(!isset( $MAIN[$siteid])){
        replace_content_progress(110,"Bad config",$siteid);
        return false;
    }
    $search = $unix->shellEscapeChars(trim($MAIN[$siteid]["search"]));
    $replace = $unix->shellEscapeChars(trim($MAIN[$siteid]["replace"]));
    if($search==null){
        replace_content_progress(110,"Bad config (2)",$siteid);
        return false;
    }
    if($replace==null){
        replace_content_progress(110,"Bad config (3)",$siteid);
        return false;
    }

    echo "Search: $search and replace to $replace\n";
    $sitepath="/home/wordpress_sites/site{$siteid}";
    replace_content_progress(50,"{content_replacement}",$siteid);
    exec("$wpcliphar search-replace \"$search\" \"$replace\" --allow-root --path=\"$sitepath\" 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        echo "Result: $line\n";
        if(preg_match("#^Error:#",$line)){
            replace_content_progress(110,"{content_replacement} {failed}",$siteid);
            return false;
        }

    }
    replace_content_progress(100,"{content_replacement} {success}",$siteid);
    return true;
}

function getDbCredsFromPath($directory):array{

        if(!is_file("$directory/wp-config.php")){
            echo "$directory/wp-config.php, no such file\n";
            return array("","","");
        }
    $DB_NAME=null;
    $DB_USER=null;
    $DB_PASSWORD=null;
        $f=explode("\n",@file_get_contents("$directory/wp-config.php"));

        foreach ($f as $line){

            if(preg_match("#define.*?DB_NAME.*?,(.+).*?\)#",$line,$re)){
                $DB_NAME=trim($re[1]);
                $DB_NAME=str_replace("'",'',$DB_NAME);
                $DB_NAME=str_replace('"','',$DB_NAME);
                continue;
            }
            if(preg_match("#define.*?DB_USER.*?,(.+).*?\)#",$line,$re)){
                $DB_USER=trim($re[1]);
                $DB_USER=str_replace("'",'',$DB_USER);
                $DB_USER=str_replace('"','',$DB_USER);
                continue;
            }
            if(preg_match("#define.*?DB_PASSWORD.*?,(.+).*?\)#",$line,$re)){
                $DB_PASSWORD=trim($re[1]);
                $DB_PASSWORD=str_replace("'",'',$DB_PASSWORD);
                $DB_PASSWORD=str_replace('"','',$DB_PASSWORD);
            }


        }

        return array($DB_NAME,$DB_USER,$DB_PASSWORD);
}

function backup_restore($backupid=0,$siteid=0):bool{
    $unix=new unix();
    $wpcliphar=$unix->find_program("wp-cli.phar");
    if(!is_file($wpcliphar)){
        backup_restore_website_progress(110,"wp-cli {no_such_file}",$siteid);
        return false;
    }

    if(intval($backupid)==0 OR intval($siteid)==0){
        backup_restore_website_progress(110,"$backupid/$siteid Wrong parameters",$siteid);
        return false;
    }
    $sitepath="/home/wordpress_sites/site{$siteid}";
    $wpconfig="$sitepath/wp-config.php";

    if(!is_file($wpconfig)){
        backup_restore_website_progress(110,"$wpconfig {no_such_file}",$siteid);
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT filename,fullpath,filesize FROM wp_backup WHERE ID=$backupid");
    $fullpath=$ligne["fullpath"];
    $filename=$ligne["filename"];
    if(!is_file($fullpath)){
        backup_restore_website_progress(110,"$filename {no_such_file}",$siteid);
        return false;
    }
    $tar=$unix->find_program("tar");
    $extractDir=$unix->TEMP_DIR()."/$siteid-$backupid";
    $rm=$unix->find_program("rm");
    $rmdir="$rm -rf $extractDir";
    if(!is_dir($extractDir)){@mkdir($extractDir,0755,true);}
    backup_restore_website_progress(10,"$filename {extracting}",$siteid);
    shell_exec("$tar -xf $fullpath -C $extractDir/");
    $database_dump="$extractDir/database.sql";
    if(!is_file($database_dump)){
        backup_restore_website_progress(110,"database.sql {no_such_file}",$siteid);
        shell_exec($rmdir);
        return false;
    }
    if(!is_dir("$extractDir/wp-admin")){
        shell_exec($rmdir);
        backup_restore_website_progress(110,"{extracting} {failed} ({no_such_file} /wp-admin directory)",$siteid);
        return false;
    }

    list($DB_NAME,$DB_USER,$DB_PASSWORD)=getDbCredsFromPath($sitepath);

    echo "MySQL settings $DB_USER@$DB_NAME\n";

    if($DB_NAME<>null) {
        $qlite=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $q=new mysql();
        if (!$q->DATABASE_EXISTS($DB_NAME)) {
            echo "Creating database $DB_NAME\n";
            $q->CREATE_DATABASE($DB_NAME);
        }
        if (!$q->DATABASE_EXISTS($DB_NAME)) {
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='unable to create database' WHERE ID=$siteid");
            backup_restore_website_progress(110,"unable to create database",$siteid);
            return false;
        }

        echo "Creating database privieleges $DB_USER on $DB_NAME\n";

        if (!$q->PRIVILEGES($DB_USER, $DB_PASSWORD, $DB_NAME)) {
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='unable to assign privileges' WHERE ID=$siteid");
            backup_restore_website_progress(110,"unable to assign privileges",$siteid);
            return false;

        }
    }


    backup_restore_website_progress(15,"{remove_databases}",$siteid);
    system("$wpcliphar db reset --yes --allow-root --path=\"$sitepath\"");
    backup_restore_website_progress(30,"{restore_database}",$siteid);
    $cmd="$wpcliphar db import $database_dump --allow-root --path=\"$sitepath\"";
    echo "$cmd\n";
    system($cmd);
    backup_restore_website_progress(40,"{remove} {readonly}",$siteid);
    $chattr=$unix->find_program("chattr");
    wordpress_unlock($siteid);
    shell_exec("$chattr +i $wpconfig");
    backup_restore_website_progress(50,"{remove} temporary $sitepath",$siteid);
    shell_exec($rmdir);
    backup_restore_website_progress(50,"{remove} destination $sitepath",$siteid);
    shell_exec("$rm -rf $sitepath/*");
    backup_restore_website_progress(60,"{restore} $sitepath",$siteid);
    shell_exec("$tar -xf $fullpath -C $sitepath/");

    $torem[]="database.sql";
    $torem[]="row.sql";
    foreach ($torem as $fname){
        if(is_file("$sitepath/$fname")){
            @unlink("$sitepath/$fname");
        }
    }
    shell_exec("$chattr -i $wpconfig");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteid");
    $readonly=$ligne["readonly"];
    backup_restore_website_progress(70,"{checking}",$siteid);
    CheckSiteName($ligne);
    ChmodSite($ligne,30);

    if($readonly==1){
        echo "Website is on readonly mode...\n";
        backup_restore_website_progress(75,"{readonly}...",$siteid);
        wordpress_lock($siteid);
    }

    backup_restore_website_progress(100,"{success}...",$siteid);
    return true;
}
function backup_restore_website($FilenameEncoded,$siteid){
    $unix=new unix();
    $filename=base64_decode($FilenameEncoded);
    $uploaddir = "/usr/share/artica-postfix/ressources/conf/upload";
    $uploaddirfile="$uploaddir/$filename";
    $backupdirectory="/home/wordpress_backup";
    $backupcontainer="$backupdirectory/$filename";

    if(!is_file($uploaddirfile)){
        backup_restore_website_progress(110,"$filename no such file",$siteid);
        return false;
    }
    if(is_file($backupcontainer)){
        @unlink($uploaddirfile);
        backup_restore_website_progress(110,"$filename already exists in backups",$siteid);
        return false;

    }

    $filetemp=$unix->FILE_TEMP().".tar.gz";
    backup_restore_website_progress(10,"Copy $filename",$siteid);
    if(!@copy($uploaddirfile,$filetemp)){
        @unlink($uploaddirfile);
        if(is_file($filetemp)){@unlink($filetemp);}
        backup_restore_website_progress(110,"Copy $filename {failed}",$siteid);
        return false;
    }
    @unlink($uploaddirfile);
    $tar=$unix->find_program("tar");
    $extractDir=$unix->TEMP_DIR()."/$siteid";
    $rm=$unix->find_program("rm");
    $rmdir="$rm -rf $extractDir";
    backup_restore_website_progress(20,"{extracting} $filetemp",$siteid);
    if(!is_dir($extractDir)){@mkdir($extractDir,0755,true);}
    shell_exec("$tar -xf $filetemp -C $extractDir/");
    backup_restore_website_progress(50,"{extracting} $filetemp",$siteid);
    if(!is_file("$extractDir/database.sql")){
        @unlink($filetemp);
        shell_exec($rmdir);
        backup_restore_website_progress(110,"{extracting} {failed} (missing database.sql)",$siteid);
        return false;
    }
    if(!is_dir("$extractDir/wp-admin")){
        @unlink($filetemp);
        shell_exec($rmdir);
        backup_restore_website_progress(110,"{extracting} {failed} (missing /wp-admin directory)",$siteid);
        return false;
    }
    $time=filemtime("$extractDir/database.sql");
    $BDsize=@filesize("$extractDir/database.sql");
    $Tarsize=@filesize($filetemp);
    shell_exec($rmdir);
    if(!is_dir($backupdirectory)) {@mkdir($backupdirectory, 0755, true);}
    backup_restore_website_progress(70,"{restoring} $filetemp",$siteid);
    if(!@copy($filetemp,$backupcontainer)){
        backup_restore_website_progress(110,"Copy to $backupcontainer {failed}",$siteid);
        @unlink($filetemp);
        return false;
    }
    @unlink($filetemp);
    backup_restore_website_progress(90,"{importing}",$siteid);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    backup_create_tables();
    $backupcontainer_filename=basename($backupcontainer);
    $hostname=hostname_from_id($siteid);
    $sql="INSERT INTO wp_backup (siteid,backuptime,hostname,filename,filesize,dbsize,fullpath)
    VALUES ('$siteid','$time','$hostname','$backupcontainer_filename','$Tarsize','$BDsize','$backupcontainer')";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        @unlink($backupcontainer);
        build_progress_backup("{backup} {failed} SQL Error",110);
        return false;
    }
    backup_restore_website_progress(100,"{importing} {success}",$siteid);
    return true;


}
function hostname_from_id($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    return $ligne["hostname"];
}

function backup_delete($ID):bool{
    $ID=intval($ID);
    $unix=new unix();
    if($ID==0){
        echo "Failed, wrong ID\n";
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT filename FROM wp_backup WHERE ID=$ID");
    $filename=$ligne["filename"];
    if(is_file($filename)){
        squid_admin_mysql(1,"Wordpress Backup $filename was removed",null,__FILE__,__LINE__);
        @unlink($filename);
    }
    $q->QUERY_SQL("DELETE FROM wp_backup WHERE ID=$ID");
    return true;
}

function backup_website($ID){
    $ID=intval($ID);
    $unix=new unix();
    if($ID==0){
        echo "Failed, wrong ID\n";
        build_progress_backup("{failed}",110);
        return false;
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        build_progress_backup("{failed} {license_error}",110);
        squid_admin_mysql(1,"Peform Backup failed ({license_error})",null,"backup",__LINE__);
        return false;
    }

    $GLOBALS["INSTANCE_ID"]=$ID;

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $row_sql=base64_encode(serialize($ligne));
    $sitepath="/home/wordpress_sites/site{$ID}";
    $hostname=$ligne["hostname"];
    $mysqldump=$unix->find_program("mysqldump");

    if(!is_dir($sitepath)){
        echo "$sitepath, no such directory\n";
        build_progress_backup("{backup_database} {failed} $sitepath no dir",110);
        return false;

    }

    if(!is_file($mysqldump)){
        echo "mysqldump, no such binary\n";
        build_progress_backup("{backup_database} {failed} mysqldump missing",110);
        return false;
    }

    CleanViruses();
    $backupdirectory="/home/wordpress_backup";
    $backupcontainer="$backupdirectory/$hostname-".time().".tar.gz";
    $MAIN=GetConfigFromWPConfig("$sitepath/wp-config.php");
    $DB_NAME=$MAIN["DB_NAME"];
    $DB_USER=$MAIN["DB_USER"];
    $DB_PASSWORD=$MAIN["DB_PASSWORD"];
    $tar=$unix->find_program("tar");
    if(!is_dir($backupdirectory)) {
        @mkdir($backupdirectory, 0755, true);
    }

    if(!is_file($tar)){
        echo "tar, no such binary\n";
        build_progress_backup("{backup_database} {failed} tar missing",110);
        return false;
    }


    echo "Database $DB_NAME with user $DB_USER\n";
    build_progress_backup("{backup_database}",30);
    $q=new mysql();
    $MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
    $cmdline="$mysqldump --add-drop-table --skip-comments --insert-ignore $MYSQL_CMDLINES $DB_NAME >  $sitepath/database.sql";

    exec($cmdline,$results);
    if($unix->MYSQL_BIN_PARSE_ERROR($results)){
        echo "backup Database failed\n";
        build_progress_backup("{backup_database} {failed}",110);
        @unlink("$sitepath/database.sql");
        return false;
    }

    if(!is_file("$sitepath/database.sql")){
        echo "backup Database failed\n";
        build_progress_backup("{backup_database} {failed}",110);
        @unlink("$sitepath/database.sql");
        return false;
    }


    $BDsize=@filesize("$sitepath/database.sql");
    if($BDsize==0){
        echo "backup Database failed\n";
        build_progress_backup("{backup_database} {failed}",110);
        @unlink("$sitepath/database.sql");
        return false;
    }

    @file_put_contents("$sitepath/row.sql",$row_sql);


    build_progress_backup("{compress_directory}",50);
    @chdir($sitepath);
    system("cd $sitepath");
    system("$tar -czf $backupcontainer *");
    $Tarsize=@filesize($backupcontainer);

    @unlink("$sitepath/database.sql");
    @unlink("$sitepath/row.sql");

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    backup_create_tables();
    $backupcontainer_filename=basename($backupcontainer);
    $sql="INSERT INTO wp_backup (siteid,backuptime,hostname,filename,filesize,dbsize,fullpath)
    VALUES ('$ID','".time()."','$hostname','$backupcontainer_filename','$Tarsize','$BDsize','$backupcontainer')";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        @unlink($backupcontainer);
        build_progress_backup("{backup} {failed} SQL Error",110);
        return false;
    }
    build_progress_backup("{backup} {success}",100);
    return true;
}
function backup_create_tables(){
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    @chmod("/home/artica/SQLITE/wordpress.db", 0644);
    @chown("/home/artica/SQLITE/wordpress.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);

    $sql="CREATE TABLE IF NOT EXISTS `wp_backup` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`siteid` INTEGER,
		`backuptime` INTEGER,
		`hostname` TEXT,
		`filename` TEXT,
		`dbsize` INTEGER,
		`filesize` INTEGER,
		`fullpath` TEXT
		)
		";
    $q->QUERY_SQL($sql);

    if(!$q->FIELD_EXISTS("wp_backup","dbsize")){
        $q->QUERY_SQL("ALTER table wp_backup ADD `dbsize` INTEGER");
    }
}



function GetConfigFromWPConfig($path){
    $f=explode("\n",@file_get_contents($path));
    foreach ($f as $line){

        if(preg_match("#^define\((.*?),(.*?)\)#",$line,$re)){
            $key=trim($re[1]);
            $key=str_replace("'","",$key);
            $key=str_replace('"','',$key);
            $value=trim($re[2]);
            $value=str_replace("'","",$value);
            $value=str_replace('"','',$value);
            $MAIN[$key]=$value;
        }
    }
    return $MAIN;

}

function enable_checks(){
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->QUERY_SQL("SELECT * FROM wp_sites WHERE enabled=0");
    if(!$q->ok){echo $q->mysql_error."\n";}
    $UPDATE=false;
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $nginx_config="/etc/nginx/wordpress/{$ID}.conf";
        echo "Remove $ID\n";
        remove_phpini($ID);
        if(is_file($nginx_config)) {
            echo "Remove $nginx_config\n";
            @unlink($nginx_config);
            remove_phpini($ID);
            $UPDATE=true;
        }

    }

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->QUERY_SQL("SELECT * FROM wp_sites WHERE enabled=1");
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $nginx_config = "/etc/nginx/wordpress/{$ID}.conf";
        if(!is_file($nginx_config)) {
            echo "create_nginx_config $ID ($nginx_config)\n";
            if(!create_nginx_config($ligne)){continue;}
            $UPDATE=true;
        }
    }


    build_disabled_websites();
    remove_bad_files();


    if($UPDATE){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        $nginx=$unix->find_program("nginx");
        system("$nginx -c /etc/nginx/nginx.conf -s reload 2>&1");
        system("$php /usr/share/artica-postfix/exec.lighttpd.php --fpm-reload");
    }


}



function build_disabled_websites()
{
    $unix=new unix();
    $WordPressListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressListenInterface"));
    if($WordPressListenInterface==null){$WordPressListenInterface="eth0";}

    $WordPressListenIP=$unix->InterfaceToIPv4($WordPressListenInterface);
    if($WordPressListenIP==null){$WordPressListenIP="0.0.0.0";}
    echo "Listen $WordPressListenInterface: $WordPressListenIP\n";

    $sitepath = "/home/wordpress_sites/DisabledSites";
    $f[] = "server {";
    $f[] = "\taccess_log /var/log/apache2/access.log;";
    $f[] = "\tlisten $WordPressListenIP:80;";

    $c = 0;
    $hosts = array();
    $q = new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results = $q->QUERY_SQL("SELECT * FROM wp_sites WHERE enabled=0");
    foreach ($results as $index => $ligne) {
        $c++;
        $ID = $ligne["ID"];
        $temps = create_nginx_aliases($ID);
        $hosts = clean_array($temps);
    }

    $f[] = "\tserver_name " . @implode(" ", $hosts) . ";";
    $f[] = "\tclient_max_body_size 32m;";
    $f[] = "\troot $sitepath;";
    $f[] = "\tindex 404.html;";
    $f[] = "\tlocation / {";
    $f[] = "\t\ttry_files \$uri /404.html;";
    $f[] = "\t}";
    $f[] = "\tlocation = /404.html {";
    $f[] = "\t\texpires 30s;";
    $f[] = "\t}";
    $f[] = "}";

    @mkdir("$sitepath/css", 0755, true);
    @mkdir("$sitepath/js", 0755, true);

    if (!is_file("$sitepath/css/bootstrap.min.css")) {
        @copy("/usr/share/artica-postfix/angular/bootstrap.min.css",
            "$sitepath/css/bootstrap.min.css");
    }

    if (!is_file("$sitepath/css/animate.css")) {
        @copy("/usr/share/artica-postfix/angular/animate.css",
            "$sitepath/css/animate.css");
    }

    if (!is_file("$sitepath/css/style.css")) {
        @copy("/usr/share/artica-postfix/angular/style.css",
            "$sitepath/css/animate.css");
    }
    if (!is_file("$sitepath/js/bootstrap.min.js")) {
        @copy("/usr/share/artica-postfix/angular/js/bootstrap/bootstrap.min.js",
            "$sitepath/js/bootstrap.min.js");
    }

    $jqueryToUse=$GLOBALS["CLASS_SOCKETS"]->jQueryToUse();


    if (!is_file("$sitepath/js/$jqueryToUse")) {
        @copy("/usr/share/artica-postfix/angular/js/jquery/$jqueryToUse", "$sitepath/js/$jqueryToUse");
    }

    $WordPressDisabledSitePage = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressDisabledSitePage"));
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $WordPressDisabledSitePage = null;
    }
    if (strlen($WordPressDisabledSitePage) < 10) {
        $WordPressDisabledSitePage = "PCFET0NUWVBFIGh0bWw+CjxodG1sPgo8aGVhZD4KICAgIDxtZXRhIGNoYXJzZXQ9InV0Zi04Ij4KICAgIDxtZXRhIG5hbWU9InZpZXdwb3J0IiBjb250ZW50PSJ3aWR0aD1kZXZpY2Utd2lkdGgsIGluaXRpYWwtc2NhbGU9MS4wIj4KICAgIDx0aXRsZT40MDQgRG9tYWluIGRpc2FibGVkPC90aXRsZT4KICAgIDxsaW5rIGhyZWY9ImNzcy9ib290c3RyYXAubWluLmNzcyIgcmVsPSJzdHlsZXNoZWV0Ij4KICAgICA8bGluayBocmVmPSJjc3MvYW5pbWF0ZS5jc3MiIHJlbD0ic3R5bGVzaGVldCI+CiAgICA8bGluayBocmVmPSJjc3Mvc3R5bGUuY3NzIiByZWw9InN0eWxlc2hlZXQiPgo8L2hlYWQ+Cjxib2R5IGNsYXNzPSJncmF5LWJnIj4KIDxkaXYgY2xhc3M9Im1pZGRsZS1ib3ggdGV4dC1jZW50ZXIgYW5pbWF0ZWQgZmFkZUluRG93biI+CiAgICAgICAgPGgxPjUwMDwvaDE+CiAgICAgICAgPGgzIGNsYXNzPSJmb250LWJvbGQiPkludGVybmFsIFNlcnZlciBFcnJvcjwvaDM+CgogICAgICAgIDxkaXYgY2xhc3M9ImVycm9yLWRlc2MiPgogICAgICAgICAgICBUaGUgc2VydmVyIGlzIGN1cnJlbnRseSBkaXNhYmxlZCwgc29tZXRoaW5nIHVuZXhwZWN0ZWQgdGhhdCBkaWRuJ3QgYWxsb3cgaXQgdG8gY29tcGxldGUgdGhlIHJlcXVlc3QuIFdlIGFwb2xvZ2l6ZS48YnIvPgogICAgICAgICAgICAKICAgICAgICA8L2Rpdj4KICAgIDwvZGl2PgoKICAgIDwhLS0gTWFpbmx5IHNjcmlwdHMgLS0+CiAgICA8c2NyaXB0IHNyYz0ianMvanF1ZXJ5LTMuMS4xLm1pbi5qcyI+PC9zY3JpcHQ+CiAgICA8c2NyaXB0IHNyYz0ianMvYm9vdHN0cmFwLm1pbi5qcyI+PC9zY3JpcHQ+CjwvYm9keT4KPC9odG1sPgoKCgo=";
    }

    @file_put_contents("$sitepath/404.html", base64_decode($WordPressDisabledSitePage));

    echo "Number of disabled sites: $c\n";

    if($c==0){
        if(is_file("/etc/nginx/wordpress/disabledsites.conf")){
            echo "Remove /etc/nginx/wordpress/disabledsites.conf\n";
            @unlink("/etc/nginx/wordpress/disabledsites.conf");
        }
    }else {
        echo "Saving /etc/nginx/wordpress/disabledsites.conf\n";
        @file_put_contents("/etc/nginx/wordpress/disabledsites.conf", @implode("\n", $f));
    }

}

function remove_phpini($ID){

    $psiidbledirs[]="/etc/php/7.3/fpm/conf.d";
    $psiidbledirs[]="/etc/php/7.0/fpm/conf.d";


    foreach ($psiidbledirs as $dir){
        if(is_dir($dir)){
            if(is_file("$dir/$ID.ini")){@unlink("$dir/$ID.ini");}

        }
    }



}


function remove_website($ID){
    $ID=intval($ID);
    $unix=new unix();
    if($ID==0){
        echo "Failed, wrong ID\n";
        build_progress_remove("{failed}",110);
        return;
    }
    $rm=$unix->find_program("rm");
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    if(!$q->ok){
        echo "$q->mysql_error\n";
        build_progress_remove("SQL Err",110);
        return;
    }

    $nginx_bin=$unix->find_program("nginx");
    $sitepath=get_sitepath($ID);
    $hostname=$ligne["hostname"];
    $nginx_config="/etc/nginx/wordpress/{$ID}.conf";
    echo "Site: $hostname\n";
    echo "Path: $sitepath\n";
    echo "Conf: $nginx_config\n";
    if(!is_dir($sitepath)) {
        @mkdir("$sitepath", 0755, true);
    }

    if(!is_file("$sitepath/wp-config.php")){
        $wp_config=base64_decode($ligne["wp_config"]);
        $chattr=$unix->find_program("chattr");
        shell_exec("$chattr -i $sitepath/wp-config.php");
        @file_put_contents("$sitepath/wp-config.php",$wp_config);
        shell_exec("$chattr +i $sitepath/wp-config.php");
    }


    $MAIN=GetConfigFromWPConfig("$sitepath/wp-config.php");

    $DB_NAME=$MAIN["DB_NAME"];
    $DB_USER=$MAIN["DB_USER"];
    if($DB_USER=="root"){$DB_USER=null;}
    if($DB_USER<>null){
        build_progress_remove("{remove} $DB_USER",10);
        echo "Removing $DB_USER member in database...\n";
        $q=new mysql();
        $q->DELETE_USER_INMYSQL($DB_USER);

    }

    if($DB_NAME<>null){
        if($q->DATABASE_EXISTS($DB_NAME,true)){
            build_progress_remove("{remove} $DB_NAME",30);
            echo "Removing $DB_NAME database...\n";
            $q->DELETE_DATABASE($DB_NAME);
            if(!$q->ok){
                echo $q->mysql_error."\n";
                build_progress_remove("{remove} $DB_NAME {failed}",110);
                return;
            }
        }
    }

    if(is_dir($sitepath)){
        build_progress_remove("{remove} $sitepath",50);
        wordpress_unlock($ID);
        system("$rm -rf $sitepath");
        if(is_file("$sitepath/wp-config.php")){
            build_progress_remove("{remove} $sitepath {failed}",110);
            return;
        }
    }

    build_progress_remove("{remove} $hostname",80);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $q->QUERY_SQL("DELETE FROM wp_sites WHERE ID=$ID");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress_remove("{remove} $hostname {failed}",110);
        return;
    }

    remove_phpini($ID);
    build_disabled_websites();
    remove_bad_files();

    if(is_file($nginx_config)){
        build_progress_remove("{remove} config",90);
        @unlink($nginx_config);
        shell_exec("$nginx_bin -c /etc/nginx/nginx.conf -s reload");
    }
    build_progress_remove("{remove} $hostname {success}",100);

}

function remove_bad_files(){
    $BaseWorkDir="/etc/nginx/wordpress";
    if(!is_dir($BaseWorkDir)){return true;}
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->QUERY_SQL("SELECT ID FROM wp_sites");
    $WP_SITES=array();
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $WP_SITES[$ID]=true;
    }

    if (!$handle = opendir($BaseWorkDir)) {
        echo "$BaseWorkDir handle issue\n";
        return false;
    }

    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(!preg_match("#^(cache|fastcgi)\.([0-9]+)\.#",$filename,$re)){continue;}
        $ID=$re[2];
        if(!isset($WP_SITES[$ID])){
            @unlink("$BaseWorkDir/$filename");
            continue;
        }
    }
    return true;
}


function build_new_sites(){
    $WORDPRESS_TO_CREATE_UU=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WORDPRESS_TO_CREATE"));
    $WORDPRESS_TO_CREATE=unserialize($WORDPRESS_TO_CREATE_UU);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $unix=new unix();
    build_progress_build("{starting}",25);
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    remove_bad_files();

    if(!is_file($wp_cli_phar)){
        build_progress_build("wp-cli.phar no such binary file",110);
        return false;
    }
    $FAILED=false;
    foreach ($WORDPRESS_TO_CREATE as $zmd5=>$none){
        echo "Creating Wordpress uuid=[$zmd5]\n";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE zmd5='$zmd5'");
        if(!$q->ok){
            echo $q->mysql_error."\n";
            build_progress_build("Failed SQL Error $zmd5",110);
            return false;
        }
        $hostname=$ligne["hostname"];
        $ID=intval($ligne["ID"]);
        if($hostname==null){
            print_r($ligne);
            echo "$zmd5: Hostname [$hostname] is null!!\n";
            $q->QUERY_SQL("DELETE FROM wp_sites WHERE zmd5='$zmd5'");
            continue;
        }
        if($ID==0){
            echo "ID is 0!!\n";
            $q->QUERY_SQL("DELETE FROM wp_sites WHERE zmd5='$zmd5'");
            continue;
        }
        build_progress_build("{starting} $hostname",30);
        echo "Create Reverse-Proxy configuration file for $hostname\n";
        if(!create_nginx_config($ligne)){$FAILED=true;continue;}

        $sitepath="/home/wordpress_sites/site$ID";

        if(!is_dir("$sitepath")){
            echo "{warning}  $sitepath doesn't exists, installing the core module\n";
            if(!isset($GLOBALS["create_site($ID)"])){
                $GLOBALS["create_site($ID)"]=True;
                if(!create_site($ligne)){
                    continue;
                }
            }
        }

        CheckUser($ligne);
        ChmodSite($ligne);


    }

    if($FAILED){
        build_progress_build("{failed}",110);
        return false;
    }
    fix_miss_config_db();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_TO_CREATE",base64_encode(serialize(array())));
    build_disabled_websites();
    build_progress_build("{success}",100);

    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    echo "Reloading Nginx service...\n";
    system("$nginx -c /etc/nginx/nginx.conf -s reload 2>&1");

    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup $php /usr/share/artica-postfix/exec.wordpress.daily.php >/dev/null 2>&1 &");
    return true;
}
function build(){
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    CleanViruses();
    $rm=$unix->find_program("rm");
    build_progress_build("{starting}",25);
    system("$rm -f /etc/nginx/wordpress/*");
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    if(!is_file("/usr/share/wordpress-src/wp-includes/version.php")){
        build_progress_build("Corrupted install source",110);
        return;
    }
    if(!is_file($wp_cli_phar)){
        build_progress_build("wp-cli.phar no such binary file",110);
        return;
    }


    $results=$q->QUERY_SQL("SELECT * FROM wp_sites WHERE enabled=1 and (status=1 or status=2)");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress_build("{failed}",110);
        return;
    }
    $FAILED=false;

    foreach ($results as $index=>$ligne) {
        $hostname=$ligne["hostname"];
        build_progress_build("{starting} $hostname",30);
        echo "Create configuration file for $hostname\n";
        if(!create_nginx_config($ligne)){$FAILED=true;continue;}
        CheckUser($ligne);
        ChmodSite($ligne);

    }



    $results=$q->QUERY_SQL("SELECT * FROM wp_sites WHERE enabled=1 and (status=0 OR status=999)");

    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress_build("{failed}",110);
        return;
    }

    $max=count($results);
    $c=0;

    foreach ($results as $index=>$ligne) {
        $hostname=$ligne["hostname"];
        build_progress_build("{configuring} $hostname",40);
        echo "Installing $hostname\n";
        $c++;
        $prc=$c/$max;
        $prc=$prc*100;
        $prc=round($prc);
        if($prc>99){$prc=99;}
        build_progress_build($hostname,$prc);
        if(!create_site($ligne,$prc)){$FAILED=true;continue;}
        CheckUser($ligne);
        if(!create_nginx_config($ligne)){$FAILED=true;continue;}
        ChmodSite($ligne);

    }

    if($FAILED){
        build_progress_build("{failed}",110);
        return;
    }
    build_disabled_websites();
    remove_bad_files();
    build_progress_build("{success}",100);

    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    echo "Reloading Nginx service...\n";
    system("$nginx -c /etc/nginx/nginx.conf -s reload 2>&1");

    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup $php /usr/share/artica-postfix/exec.wordpress.daily.php >/dev/null 2>&1 &");

}

function build_ngnix($siteID){
    $nginx_config="/etc/nginx/wordpress/$siteID.conf";
    $siteID=intval($siteID);
    if($siteID==0){
        die();
    }
    $GLOBALS["INSTANCE_ID"]=$siteID;
    echo "Installing ID $siteID\n";
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    echo "Installing $hostname\n";

    if(is_file($nginx_config)){ $md5_first=md5_file($nginx_config); }

    build_progress_build("$hostname {configuring}",20);
    echo "Creating $nginx_config\n";
    if(!create_nginx_config($ligne)){
        build_progress_build("$hostname {failed}",110);
        return false;
    }
    $md5_second=md5_file($nginx_config);
    $unix=new unix();
    $nginx=$unix->find_program("nginx");

    if($GLOBALS["ONLYCONFIG"]){
        if($md5_first==$md5_second){
            build_progress_build("$hostname {success} {no_change}",100);
            return true;
        }
        echo "Reloading Nginx service...\n";
        build_progress_build("{reloading}....",99);
        system("$nginx -c /etc/nginx/nginx.conf -s reload 2>&1");
        build_progress_build("$hostname {success}",100);
        return true;
    }
    $readonly=$ligne["readonly"];
    build_progress_build("$hostname {configuring}",10);
    wordpress_unlock($siteID);

    build_progress_build("$hostname {apply_permissions}",30);
    ChmodSite($ligne,30);
    build_progress_build("$hostname {sitename}",51);
    Check_disable_wp_cron($ligne);
    build_progress_build("$hostname {sitename}",52);
    Check_wpconfig_include($ligne);
    build_progress_build("$hostname {sitename}",54);


    if($readonly==1){
        echo "Website is on readonly mode...\n";
        build_progress_build("$hostname {readonly}",60);
        wordpress_lock($siteID);
    }

    $unix=new unix();
    $nginx=$unix->find_program("nginx");


    if($md5_first==$md5_second){
        build_progress_build("$hostname {success} {no_change}",100);
        return true;
    }
    echo "Reloading Nginx service...\n";
    build_progress_build("{reloading}....",99);
    system("$nginx -c /etc/nginx/nginx.conf -s reload 2>&1");
    $unix->framework_exec("exec.lighttpd.php --fpm-reload");
    build_progress_build("$hostname {success}",100);
    return true;
}

function phpini($hostname,$sitepath){
    if(is_numeric($hostname)){
        echo "Hostname..........: phpini -> '$hostname' numeric!\n";
        return null;}
    $PHP_INI[]="[HOST=\"$hostname\"]";
    $PHP_INI[]="open_basedir=\"$sitepath/:/var/log/apache2/:/var/lighttpd/upload/\"";
    $PHP_INI[]="upload_tmp_dir=\"$sitepath/wp-content/uploads/\"";
    $PHP_INI[]="include_path=\"$sitepath\"";
    return @implode("\n",$PHP_INI);
}


function create_nginx_limit_wp_admin($ID,$sitepath){

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    if(!$q->TABLE_EXISTS("wp_admin_ip")){return;}
    $results=$q->QUERY_SQL("SELECT * FROM wp_admin_ip WHERE wpid=$ID and enabled=1 ORDER BY address");
    if(count($results)==0){return null;}
    $TRCLASS=null;
    $ips=array();
    foreach ($results as $index=>$ligne) {
        $ligne["address"]=trim($ligne["address"]);
        if($ligne["address"]==null){continue;}
        if($ligne["address"]=="*"){$ligne["address"]="all";}
        $ips[]="\t\tallow {$ligne["address"]};";


    }
    $t=time();
    if(count($ips)==0){return null;}
    $f[]="\tlocation /wp-admin/admin-ajax.php {";
    $f[]="\t\tallow all;";
    $f[]="\t}";
    $f[]="\tlocation ~ ^/(admin\.php|wp-login\.php) {";
    $f[]="\t\ttry_files \$uri \$uri/ /index.php?\$args;";
    $f[]="\t\tindex index.html index.htm index.php;";
    $f[]=@implode("\n",$ips);
    $f[]="\t\tdeny all;";
    $f[]="\t\terror_page 403 = @wp_admin_ban;";
    $f[]=php_wordpress($sitepath,true);
    $f[]="\t}";

    $f[]="\tlocation @wp_admin_ban {";
    $f[]="\t\trewrite ^(.*) /\$binary_remote_addr redirect;";
    $f[]="\t}";

    return @implode("\n",$f);
}

function create_nginx_aliases($ID):array{
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");

    $hostname=$ligne["hostname"];
    echo "Hostname..........: $hostname\n";
    $zaliases[]=$hostname;
    $aliases=unserialize(base64_decode($ligne["aliases"]));
    if(!is_array($aliases)){return $zaliases;}
    if(count($aliases)==0){return $zaliases;}
    foreach ($aliases as $sitename=>$none){
        echo "Hostname..........: $sitename\n";
        $zaliases[]=$sitename;
    }
    return $zaliases;
}

function get_sitepath($ID){
    return "/home/wordpress_sites/site{$ID}";
}
function nginx_pagespeed_global():bool{
    $f=explode("\n",@file_get_contents("/etc/nginx/nginx.conf"));
    foreach ($f as $line){
        if(preg_match("#^include\s+.*?pagespeed\.conf#",trim($line))){
            return true;
        }
    }
    return false;
}

function nginx_pagespeed($ID=0){
    $nginx_pagespeed_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
    if($nginx_pagespeed_installed==0){return "# Pagespeed not installed";}
    $nginx_pagespeed_enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));
    if($nginx_pagespeed_enabled==0){return "# Pagespeed not enabled";}
    if($ID==0){return "";}

    if(!nginx_pagespeed_global()){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.nginx.php --restart-build");
        if(!nginx_pagespeed_global()){
            return "# Unable to set main configuration...!\n";
        }
    }


    $f=array();
    $f[]="	### PageSpeed Settings";
    $f[]="	pagespeed on;";
    $f[]="	pagespeed XHeaderValue \"Optimized Artica\";";
    $f[]="	pagespeed RewriteLevel OptimizeForBandwidth;";
    $f[]="	pagespeed AllowVaryOn Auto;";
    $f[]="	pagespeed DisableRewriteOnNoTransform off;";
    $f[]="	pagespeed InPlaceResourceOptimization on;";
    $f[]="	pagespeed FileCacheCleanIntervalMs 60000;";
    $f[]="";
    $f[]="	### PageSpeed Compression";
    $f[]="	pagespeed HttpCacheCompressionLevel 6;";
    $f[]="";
    $f[]="	### PageSpeed Images";
    $f[]="	pagespeed EnableFilters dedup_inlined_images;";
    $f[]="	pagespeed EnableFilters lazyload_images;";
    $f[]="	pagespeed EnableFilters inline_preview_images;";
    $f[]="	pagespeed EnableFilters resize_mobile_images;";
    $f[]="	pagespeed EnableFilters responsive_images;";
    $f[]="	pagespeed EnableFilters responsive_images_zoom;";
    $f[]="	pagespeed ImageRecompressionQuality 75;";
    $f[]="	pagespeed ImageResolutionLimitBytes 32000000;";
    $f[]="";
    $f[]="	### PageSpeed Html";
    $f[]="	pagespeed EnableFilters insert_dns_prefetch;";
    $f[]="	pagespeed EnableFilters make_show_ads_async;";
    $f[]="	pagespeed EnableFilters make_google_analytics_async;";
    $f[]="	pagespeed EnableFilters collapse_whitespace;";
    $f[]="	pagespeed EnableFilters remove_comments;";
    $f[]="	pagespeed EnableFilters inline_google_font_css;";
    $f[]="	pagespeed EnableFilters remove_quotes;";
    $f[]="	pagespeed EnableFilters canonicalize_javascript_libraries;";

    $main_file_path="/etc/nginx/wordpress/pagespeed.{$ID}.module";
    @file_put_contents($main_file_path,@implode("\n",$f));
    return "include $main_file_path;";
}

function default_paths():string{

    $f[]="";
    $f[]="\tlocation ^~ /.well-known/acme-challenge/ {";
    $f[]="\t\tdefault_type \"text/plain\";";
    $f[]="\t\tproxy_set_header Accept-Encoding \"\";";
    $f[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $f[]="\t\tproxy_set_header X-Forwarded-For \$remote_addr;";
    $f[]="\t\tproxy_pass http://127.0.0.1:9554;";
    $f[]="\t}";
    $f[]="";
    return @implode("\n",$f);
}

function nginx_cache_browser():string{

    $f[]="\tlocation ~* ^.+\.(xml|ogg|ogv|svg|svgz|eot|otf|woff|mp4|ttf|css|rss|atom|js|jpg|jpeg|gif|png|ico|zip|tgz|gz|rar|bz2|doc|xls|exe|ppt|tar|mid|midi|wav|bmp|rtf|woff2|webp)$ {";
    $f[]="\t\taccess_log off;";
    $f[]="\t\tlog_not_found off;";
    $f[]="\t\tadd_header Cache-Control \"public, no-transform\";";
    $f[]="\t\texpires 365d;";
    $f[]="\t}";
    $f[]="";
    return @implode("\n",$f);

}

function isIncluded($ID){
    $f=explode("\n",@file_get_contents("/etc/nginx/wordpress/$ID.conf"));
    foreach ($f as $line){
        if(preg_match("#include.*?\/cache.[0-9]+\.#",$line)){
            return true;
        }
    }
    return false;
}
function isGlobalIncluded(){
    $f=explode("\n",@file_get_contents("/etc/nginx/nginx.conf"));
    foreach ($f as $line){
        if(preg_match("include.*?\/caches\.conf#",$line)){
            return true;
        }
    }
    return false;
}




function nginx_cache_dir_progress($prc,$text,$ID){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.cache.$ID.progress");
}

function nginx_cache_dir_build($ID){
    $qlite=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$qlite->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    nginx_cache_dir_progress(50,"{building}",$ID);
    create_nginx_config($ligne);
    if(!nginx_check_conf($ID)){
        nginx_cache_dir_progress(110,"{failed}",$ID);
        return false;
    }
    nginx_cache_dir_progress(100,"{success}",$ID);
    return true;
}

function nginx_check_conf($ID){
    $failedFile="/home/artica/wordpress_failed/$ID.conf";
    $qlite=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    echo "Testing configuration...\n";
    exec("$nginx -c /etc/nginx/nginx.conf -t 2>&1",$results);
    foreach ($results as $line){
        echo "'$line'\n";
        if(preg_match("#the configuration file.*?syntax is ok#",$line)){
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=1,database_error='' WHERE ID=$ID");
            if(is_file($failedFile)){@unlink($failedFile);}
            return true;
        }
        if(preg_match("#configuration file.*?test is successful#",$line)){
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=1,database_error='' WHERE ID=$ID");
            if(is_file($failedFile)){@unlink($failedFile);}
            return true;
        }

    }
    return false;
}


function fastcgi_cache($ID){
    $target_file="/etc/nginx/wordpress/fastcgi.$ID.module";
    $f=array();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT cgicache FROM wp_sites WHERE ID=$ID");
    $cgicache=intval($ligne["cgicache"]);
    echo "CGI Cache: [$cgicache]\n";
    if($cgicache==0){
        $f[]="# CGI Cache not enabled\n";
        echo "CGI Cache: Saving [$target_file] with nothing\n";
        @file_put_contents($target_file,"# CGI Cache not enabled\n");
        return false;
    }
    $f[] = "\tset \$skip_cache 0;";

    $f[] = "\tif (\$request_method = POST) {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    $f[] = "\tif (\$request_uri ~* \"/wp-admin/|/xmlrpc.php|wp-.*?.php|/feed/|index.php|sitemap(_index)?.xml\") {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    $f[] = "\t# Don't use the cache for logged in users or recent commenters";
    $f[] = "\tif (\$http_cookie ~* \"comment_author|wordpress_[a-f0-9]+|wp-postpass|wordpress_no_cache|wordpress_logged_in\") {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";

    $f[] = "\tif (\$http_cookie ~* \"PHPSESSID\"){";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";

    $f[]="\t\tfastcgi_no_cache \$skip_cache;";
    $f[]="\t\tfastcgi_cache php;";
    $f[]="\t\tfastcgi_cache_revalidate on;";
    $f[]="\t\tfastcgi_cache_key \"\$scheme\$request_method\$host\$request_uri\";";
    $f[]="\t\tfastcgi_cache_valid 200 302 380m;";
    $f[]="\t\tfastcgi_cache_bypass \$skip_cache;";
    $f[]="\t\tfastcgi_no_cache \$skip_cache;";
    @file_put_contents($target_file,@implode("\n",$f));
    return true;
}


function create_nginx_config($ligne):bool{
    $ID=intval($ligne["ID"]);
    $md5_first=null;
    $nginx_config="/etc/nginx/wordpress/$ID.conf";

    $paths[]="/home/letsencrypt";
    $paths[]="/home/letsencrypt/$ID";
    $paths[]="/home/letsencrypt/$ID/acme-challenge";

    foreach ($paths as $directory){
        if(!is_dir($directory)){
            @mkdir($directory,0755,true);
        }
        @chown("www-data",$directory);
        @chgrp("www-data",$directory);
    }

    if(is_file($nginx_config)){ $md5_first=md5_file($nginx_config); }

    $unix=new unix();
    remove_badfiles($ID);
    $PHP_INI=array();
    $sitepath=get_sitepath($ID);
    $hostname=$ligne["hostname"];
    $UseSSL=$ligne["ssl"];
    $letsencrypt=$ligne["letsencrypt"];
    $CertificateName=null;

    if($UseSSL==1){
        $CertificateName=$ligne["ssl_certificate"];
    }

    echo "Certificate Name....:$CertificateName\n";
    $ssl_certificate=new nginx_certificate($CertificateName);
    $ssl_certificate->letsencript=$letsencrypt;
    $ssl_config=$ssl_certificate->GetConf();
    $zaliases_clean=create_nginx_aliases($ID);
    $zaliases=clean_array($zaliases_clean);

    foreach ($zaliases as $index=>$sitename){
        echo "Hostname..........: ($index) phpini($sitename)\n";
        $PHP_INI[]=phpini($sitename,$sitepath);

    }

    $WordPressListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressListenInterface"));
    if($WordPressListenInterface==null){$WordPressListenInterface="eth0";}
    $WordPressListenIP=$unix->InterfaceToIPv4($WordPressListenInterface);
    if($WordPressListenIP==null){$WordPressListenIP="0.0.0.0";}




    $_php=php_wordpress($sitepath,false,$ID);
    $f[]="# Listen interface {$WordPressListenInterface}";
    $f[]="server {";
    $f[]="\tlisten {$WordPressListenIP}:80;";
    $f[]="\tserver_name ".@implode(" ",$zaliases).";";


    if($UseSSL==1){
        $f[]="\tlisten {$WordPressListenIP}:443 ssl;";
        $f[]=$ssl_config;
        $f[]="\tssl_session_cache shared:SSL:50m;";
        $f[]="\tssl_session_timeout  5m;";
    }


    if($ligne["pagespeed"]==1) {
        $f[] = nginx_pagespeed($ID);
    }else{
        $main_pagespeed_path="/etc/nginx/wordpress/pagespeed.{$ID}.module";
        if(is_file($main_pagespeed_path)){@unlink($main_pagespeed_path);}
    }
    $f[]="";

    $yoast=intval($ligne["yoast"]);

    $f[] = "\terror_page 404 = @notfound;";
    $f[] = "\t\tlocation @notfound {";
    $f[] = "\t\treturn 302 /;";
    $f[] = "\t}";
    
    $f[] = default_paths();

    $f[]="";
    $f[]="\tclient_max_body_size 32m;";
    $f[]="\troot $sitepath;";
    $f[]="\tindex index.php;";

    $f[]="\tlocation ~* \.(engine|inc|info|install|make|module|profile|test|po|sh|.*sql|theme|tpl(\.php)?|xtmpl)$|^(\..*|Entries.*|Repository|Root|Tag|Template)$|\.php_ {";
    $f[]="\treturn 444;";
    $f[]="\t}";

    $f[]="\tlocation = /wp-comments-post.php {";
    $f[]="\tif (\$http_cookie !~* \"abcdefghijklmnopqrstuvwxyz0123456789\") { return 444; }";
    $f[]="\t}";

    if($yoast==1){
        $f[]="#\tYoast plugin support";
        $f[]="\tlocation ~ ([^/]*)sitemap(.*).x(m|s)l$ {";
        $f[]="\t\trewrite ^/sitemap.xml$ /sitemap_index.xml permanent;";
        $f[]="\t\trewrite ^/([a-z]+)?-?sitemap.xsl$ /index.php?yoast-sitemap-xsl=$1 last;";
        $f[]="\t\trewrite ^/sitemap_index.xml$ /index.php?sitemap=1 last;";
        $f[]="\t\trewrite ^/([^/]+?)-sitemap([0-9]+)?.xml$ /index.php?sitemap=$1&sitemap_n=$2 last;";
        $f[]="\t\trewrite ^/news-sitemap.xml$ /index.php?sitemap=wpseo_news last;";
        $f[]="\t\trewrite ^/locations.kml$ /index.php?sitemap=wpseo_local_kml last;";
        $f[]="\t\trewrite ^/geo-sitemap.xml$ /index.php?sitemap=wpseo_local last;";
        $f[]="\t\trewrite ^/video-sitemap.xsl$ /index.php?yoast-sitemap-xsl=video last;";
        $f[]="\t}";
    }

    $f[]="\tlocation = /wp-admin/install.php { deny all; }";
	$f[]="\tlocation = /nginx.conf { deny all; }";
	$f[]="\tlocation ~ /\.htaccess$ { deny all; }";
	$f[]="\tlocation ~ /readme\.html$ { deny all; }";
	$f[]="\tlocation ~ /readme\.txt$ { deny all; }";
	$f[]="\tlocation ~ /wp-config.php$ { deny all; }";
	$f[]="\tlocation ~ ^/wp-admin/includes/ { deny all; }";
	$f[]="\tlocation ~ ^/wp-includes/[^/]+\.php$ { deny all; }";
	$f[]="\tlocation ~ ^/wp-includes/js/tinymce/langs/.+\.php$ { deny all; }";
	$f[]="\tlocation ~ ^/wp-includes/theme-compat/ { deny all; }";
	$f[]="\tlocation ~ ^.*/\.git/.*$ { deny all; }";
	$f[]="\tlocation ~ ^.*/\.svn/.*$ { deny all; }";
    $f[]="\tlocation ~ ^/wp\-content/plugins/.*\.(?:php[1-7]?|pht|phtml?|phps|ico)$ { deny all; }";
	$f[]="\tlocation ~ ^/wp\-content/themes/.*\.(?:php[1-7]?|pht|phtml?|phps|ico)$ { deny all; }";
    $f[]="\tlocation ~ ^/wp\-includes/sitemaps/providers/.*\.(?:php[1-7]?|pht|phtml?|phps|ico)$ { deny all; }";


    $f[]="\tlocation ~* \.(pl|cgi|py|sh|lua)$ {";
    $f[]="\treturn 444;";
    $f[]="\t}";

    $f[]="\tlocation ~* /xmlrpc.php$ {";
    $f[]="\tallow 172.0.1.1;";
    $f[]="\tdeny all;";
    $f[]="\t}";
    $f[]="";

    $f[]="\tlocation /wordpress/wp-content/uploads/ {";
    $f[]="\t\ttypes {";
    $f[]="\t\t\timage/gif       gif;";
    $f[]="\t\t\timage/jpeg      jpeg jpg;";
    $f[]="\t\t\timage/png       png;";
    $f[]="\t\t\ttext/plain      txt;";
    $f[]="\t\t}";
    $f[]="\t\tdefault_type    application/octet-stream;";
    $f[]="\t\tlocation ~ \.php$ {";
    $f[]="\t\t\tbreak;";
    $f[]="\t\t}";
    $f[]="\t}";
    $f[]="";
    $f[]="\tlocation ~* /(?:uploads|files)/.*.php$ {";
    $f[]="\t\treturn  444;";
    $f[]="\t}";
    $f[]="";
    $f[]="\tlocation ~* /wp-includes/.*.php$ {";
    $f[]="\t\treturn  444;";
    $f[]="\t}";

    $f[]="\t location ~* /wp-admin/images/.*.php$ {";
    $f[]="\t\treturn  444;";
    $f[]="\t}";
    $f[]="\t location ~* /src/field-template/.*.php$ {";
    $f[]="\t\treturn  444;";
    $f[]="\t}";

    $f[]="";

    $f[]=create_nginx_limit_wp_admin($ID,$sitepath);


    $f[]="\trewrite ^/sitemapindex\.xml$ /index.php?gxs_module=sitemapindex last;";
    $f[]="\trewrite ^/post\.xml$ /index.php?gxs_module=post last;";
    $f[]="\trewrite ^/page\.xml$ /index.php?gxs_module=page last;";
    $f[]="\trewrite ^/post_google_news\.xml$ /index.php?gxs_module=post_google_news last;";
    $f[]="\trewrite ^/taxonomy_category\.xml$ /index.php?gxs_module=taxonomy_category last;";
    $f[]="\trewrite /wp-admin$ \$scheme://\$host\$uri/ permanent;";
    $f[]="";
    $f[]=nginx_cache_browser();



    $f[]="\tlocation / {";
    $f[]="\t\ttry_files \$uri \$uri/ /index.php?\$args;";
    $f[]="\t}";

    $f[]="\tlocation ~ \.php$ {";
    $f[]=$_php;
    $f[]="\t}";
    $f[]="";

    $f[]="	";
    $f[]="\tlocation ~ /\.{ access_log off; log_not_found off; deny all; }";
    $f[]="\tlocation ~ ~$ { access_log off; log_not_found off; deny all; }";
    $f[]="\tlocation = /robots.txt { access_log off; log_not_found off; }";
    $f[]="\tlocation = /favicon.ico { access_log off; log_not_found off; }";
    $f[]="";
    $f[]="\tlocation ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {";
    $f[]="\t\ttry_files \$uri /index.php;";
    $f[]="\t\texpires max;";
    $f[]="\t\tlog_not_found off;";
    $f[]="\t}";
    $f[]="	";
    $f[]="\tlocation ^~ /bin/ { internal; }";
    $f[]="\tlocation ^~ /docs/ { internal; }";
    $f[]="\tlocation ^~ /extensions/ { internal; }";
    $f[]="\tlocation ^~ /includes/ { internal; }";
    $f[]="\tlocation ^~ /maintenance/ { internal; }";
    $f[]="\tlocation ^~ /resources/ { internal; } # Breaks Default Logo (mv logo to images)";
    $f[]="\tlocation ^~ /serialized/ { internal; }";
    $f[]="\tlocation ^~ /tests/ { internal; }";
    $f[]="\tlocation ^~ /skins/ { internal; }";
    $f[]="\tlocation ^~ /vendor/ { internal; }";
    $f[]="	";
    $f[]="\tlocation ~* ^/images/.*.(html|htm|shtml|php)$ {";
    $f[]="\t\ttypes { }";
    $f[]="\t\tdefault_type text/plain;";
    $f[]="\t}";
    $f[]="	";
    $f[]="\tlocation ^~ /images/ {";
    $f[]="\t\ttry_files \$uri /index.php;";
    $f[]="\t}";
    $f[]=srcache_redis($ID);

    $f[]="}\n";
    if(!is_dir("/etc/nginx/wordpress")){@mkdir("/etc/nginx/wordpress",0755,true);}
    echo "Saving file $nginx_config....\n";
    @file_put_contents($nginx_config,@implode("\n",$f));
    $md5_second=md5_file($nginx_config);
    echo "Change ? <$md5_first> = <$md5_second>\n";

    $psiidbledirs[]="/etc/php/7.3/fpm/conf.d";
    $psiidbledirs[]="/etc/php/7.0/fpm/conf.d";


    foreach ($psiidbledirs as $dir){
        if(is_dir($dir)){
            echo "Saving $dir/$ID.ini\n";
            @file_put_contents("$dir/$ID.ini",@implode("\n",$PHP_INI));
        }else{
            echo "Saving $dir\n";
            echo "No such directory $dir\n";
        }
    }

    if($md5_first==$md5_second){
        echo "$nginx_config no changes\n";
        return true;
    }

    $failedFile="/home/artica/wordpress_failed/$ID.conf";
    $qlite=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    echo "$hostname, testing configuration...\n";
    exec("$nginx -c /etc/nginx/nginx.conf -t 2>&1",$results);
    foreach ($results as $line){
        echo "$hostname, '$line'\n";
        if(preg_match("#the configuration file.*?syntax is ok#",$line)){
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=1,database_error='' WHERE ID=$ID");
            if(is_file($failedFile)){@unlink($failedFile);}
            return true;
        }
        if(preg_match("#configuration file.*?test is successful#",$line)){
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=1,database_error='' WHERE ID=$ID");
            if(is_file($failedFile)){@unlink($failedFile);}
            return true;
        }

    }

    echo "Removing file /etc/nginx/wordpress/$ID.conf\n";
    if(!is_dir("/home/artica/wordpress_failed")){
        @mkdir("/home/artica/wordpress_failed",0755,true);
    }
    if(is_file($failedFile)){@unlink($failedFile);}
    @copy("/etc/nginx/wordpress/$ID.conf",$failedFile);
    @unlink("/etc/nginx/wordpress/$ID.conf");
    echo "Copy bad configuration in $failedFile\n";

    $results_text=@implode("<br>",$results);
    $results_text=str_replace("'","`",$results_text);
    $qlite->QUERY_SQL("UPDATE wp_sites SET status=2,database_error='{$results_text}' WHERE ID=$ID");
    return false;
}

function clean_array($array){
    $zaliases_clean2=array();
    $zaliases=array();
    if(count($array)==0){return array();}

    foreach ($array as $index=>$sitename){
        $sitename=trim(strtolower($sitename));
        $zaliases_clean2[$sitename]=true;
    }

    foreach ($zaliases_clean2 as $sitename=>$none){
        $zaliases[]=$sitename;
    }
    return $zaliases;
}
function isCacheEnabled($ID){
    $APP_NGINX_SRCACHE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_SRCACHE"));
    if($APP_NGINX_SRCACHE==0){
        $GLOBALS["NGINX_PHP_CACHE_STATUS"]="#\t\tCaching Not implemented module;";
        return false;
    }
    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    if($NginxCacheRedis==0){
        $GLOBALS["NGINX_PHP_CACHE_STATUS"]="#\t\tCaching with redist is disabled;";
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT cgicache FROM wp_sites WHERE ID=$ID");
    if(intval($ligne["cgicache"])==0){
        $GLOBALS["NGINX_PHP_CACHE_STATUS"]="#\t\tCaching with redist is enabled but not for this site;";
        return false;
    }
    $GLOBALS["NGINX_PHP_CACHE_STATUS"]="";
    return true;
}

function srcache_redis_pwd():string{

    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    if($EnableRedisServer==0){$NginxCacheRedisLocal=0;}
    if($NginxCacheRedisLocal==1){
        $RedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
        if ($RedisPassword<>null){
            return "\t\tredis2_query auth $RedisPassword;";
        }
        return "";
    }
    $NginxCacheRedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPassword"));
    if ($NginxCacheRedisPassword<>null){
        return "\t\tredis2_query auth $NginxCacheRedisPassword;";
    }
    return "";
}
function srcache_redis_pass():string{
    $NginxCacheRedisHost=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisHost"));
    $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPort"));
    if($NginxCacheRedisPort==0){$NginxCacheRedisPort=6379;}
    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    if($EnableRedisServer==0){$NginxCacheRedisLocal=0;}

    if($NginxCacheRedisLocal==1){
        $RedisBindInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindInterface");
        if($RedisBindInterface==null){$RedisBindInterface="lo";}
        $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindPort"));
        if($RedisBindInterface=="lo"){
            $NginxCacheRedisHost="127.0.0.1";
        }else{
            $unix=new unix();
            $NginxCacheRedisHost=$unix->InterfaceToIPv4($RedisBindInterface);
        }
        return "$NginxCacheRedisHost:$NginxCacheRedisPort";
    }

    return "$NginxCacheRedisHost:$NginxCacheRedisPort";

}
function srcache_redis($ID){
    if(!isCacheEnabled($ID)){return $GLOBALS["NGINX_PHP_CACHE_STATUS"];}
    $srcache_redis_pwd=srcache_redis_pwd();
    $f[]="\tlocation /redis-fetch {";
    $f[]="\t\tinternal;";
    $f[]="\t\tset  \$redis_key \$args;";
    $f[]=$srcache_redis_pwd;
    
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $proxy_cache_min_uses=intval($ligne["proxy_cache_min_uses"]);
    if($proxy_cache_min_uses<5){
        $proxy_cache_min_uses=14400/60;
    }
    $redis_pass=srcache_redis_pass();
    $proxy_cache_min_uses=$proxy_cache_min_uses*60;
  	$f[]="\t\tredis_pass  $redis_pass;";
    $f[]="  }";
    $f[]="";
    $f[]="\tlocation /redis-store {";
    $f[]="\t\tinternal;";
    $f[]="\t\tset_unescape_uri \$key \$arg_key ;";
    $f[]="\t\tredis2_query  set \$key \$echo_request_body;";
	$f[]="\t\tredis2_query expire \$key $proxy_cache_min_uses;";
    $f[]=$srcache_redis_pwd;
    $f[]="\t\tredis2_pass  $redis_pass;";
    $f[]="  }";
    return @implode("\n",$f);
}
function php_cache($ID):string{
    if(!isCacheEnabled($ID)){return $GLOBALS["NGINX_PHP_CACHE_STATUS"];}

    $f[] = "\tset \$skip_cache 0;";

    $f[] = "\tif (\$request_method = POST) {";
    $f[]="\t\tmore_set_headers 'X-Cache-skip-Status POST proto';";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    $f[] = "\tif (\$request_uri ~* \"/wp-admin/|/xmlrpc.php|wp-.*?.php|/feed/|index.php|sitemap(_index)?.xml\") {";
    $f[]="\t\tmore_set_headers 'X-Cache-skip-Status 1';";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    /*
    $f[] = "\t# Don't use the cache for logged in users or recent commenters";
    $f[] = "\tif (\$http_cookie ~* \"comment_author|wordpress_[a-f0-9]+|wp-postpass|wordpress_no_cache|wordpress_logged_in\") {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[]="\t\tmore_set_headers 'X-Cache-skip-Status 2';";
    $f[] = "\t}";
    */

    $f[] = "\tif (\$http_cookie ~* \"PHPSESSID\"){";
    $f[]="\t\tmore_set_headers 'X-Cache-skip-Status PHPSESSID';";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    $f[]="set \$key \"nginx-cache:$ID:\$scheme\$request_method\$host\$request_uri\";";
    $f[]="\t\tsrcache_fetch_skip \$skip_cache;";
    $f[]="\t\tsrcache_store_skip \$skip_cache;";
    $f[]="\t\tsrcache_response_cache_control off;";
    $f[]="\t\tsrcache_store_statuses 200 201 308 404 503;";
    $f[]="\t\tset_escape_uri \$escaped_key \$key;";
    $f[]="\t\tsrcache_fetch GET /redis-fetch \$key;";
    $f[]="\t\tsrcache_store PUT /redis-store key=\$escaped_key;";
    $f[]="\t\tmore_set_headers 'X-Cache-Fetch-Status \$srcache_fetch_status';";
    $f[]="\t\tmore_set_headers 'X-Cache-Store-Status \$srcache_store_status';";
    $f[]="\t\tmore_set_headers 'X-Cache-Uri \$request_uri';";
    return @implode("\n",$f);
}

function php_wordpress($sitepath,$remove_try_files=false,$ID=0){
    $f[]="\t\tfastcgi_split_path_info ^(.+\.php)(/.+)$;";
    if(!$remove_try_files) {
        $f[] = "\t\ttry_files \$fastcgi_script_name =404;";
    }

    if($ID>0){
        $f[]=php_cache($ID);
    }


    $f[]="\t\tlimit_req zone=phpddos burst=10 nodelay;";
    $f[]="\t\tset \$path_info \$fastcgi_path_info;";
    $f[]="\t\tfastcgi_index index.php;";
    $f[]="\t\tfastcgi_intercept_errors on;";
    $f[]="\t\tfastcgi_buffer_size 128k;";
    $f[]="\t\tfastcgi_connect_timeout 60s;";
    $f[]="\t\tfastcgi_send_timeout 60s;";
    $f[]="\t\tfastcgi_read_timeout 60s;";
    $f[]="\t\tfastcgi_buffers 256 16k;";
    $f[]="\t\tfastcgi_busy_buffers_size 256k;";
    $f[]="\t\tfastcgi_temp_file_write_size 256k;";
    $f[]="\t\tfastcgi_pass unix:/var/run/wordpress-phpfpm.sock;";
    $f[]="\t\tfastcgi_param   HTTP_PROXY              \"\";";
    $f[]="\t\tfastcgi_param   QUERY_STRING            \$query_string;";
    $f[]="\t\tfastcgi_param   REQUEST_METHOD          \$request_method;";
    $f[]="\t\tfastcgi_param   CONTENT_TYPE            \$content_type;";
    $f[]="\t\tfastcgi_param   CONTENT_LENGTH          \$content_length;";
    $f[]="\t\tfastcgi_param   SCRIPT_FILENAME         \$document_root\$fastcgi_script_name;";
    $f[]="\t\tfastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;";
    $f[]="\t\tfastcgi_param   PATH_INFO               \$fastcgi_path_info;";
    $f[]="\t\tfastcgi_param   PATH_TRANSLATED   	  \$document_root\$fastcgi_path_info;";
    $f[]="\t\tfastcgi_param   REQUEST_URI             \$request_uri;";
    $f[]="\t\tfastcgi_param   DOCUMENT_URI            \$document_uri;";
    $f[]="\t\tfastcgi_param   DOCUMENT_ROOT           \$document_root;";
    $f[]="\t\tfastcgi_param   SERVER_PROTOCOL         \$server_protocol;";
    $f[]="\t\tfastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
    $f[]="\t\tfastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;";
    $f[]="\t\tfastcgi_param   REMOTE_ADDR             \$remote_addr;";
    $f[]="\t\tfastcgi_param   REMOTE_PORT             \$remote_port;";
    $f[]="\t\tfastcgi_param   SERVER_ADDR             \$server_addr;";
    $f[]="\t\tfastcgi_param   SERVER_PORT             \$server_port;";
    $f[]="\t\tfastcgi_param   SERVER_NAME             \$server_name;";
    $f[]="\t\tfastcgi_param   HTTPS                   \$https;";
    $f[]="\t\tfastcgi_param   REDIRECT_STATUS 	       200;";
    $f[]="\t\tfastcgi_param   PATH_INFO               \$path_info;";
    $f[]="\t\tfastcgi_param   SCRIPT_FILENAME          $sitepath\$fastcgi_script_name;";
    $f[]="\t\tfastcgi_param   PHP_VALUE                post_max_size=50M;";
    $f[]="\t\tfastcgi_param   PHP_VALUE                upload_max_filesize=50M;";
    $f[]="\t\tfastcgi_param   PHP_VALUE                open_basedir=\"$sitepath\";";

    return @implode("\n",$f);
}

function ChmodSite($ligne,$prc=0){
    $ID=intval($ligne["ID"]);
    $sitepath="/home/wordpress_sites/site$ID";
    $hostname=$ligne["hostname"];
    $unix=new unix();
    $APACHE_USER=$unix->APACHE_SRC_ACCOUNT();
    $APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
    echo "Apply permissions on $hostname ( $APACHE_USER:$APACHE_SRC_GROUP )\n";
    $find=$unix->find_program("find");
    $chmod=$unix->find_program("chmod");
    $chown=$unix->find_program("chown");
    $wpconfig_php="$sitepath/wp-config.php";
    $chattr=$unix->find_program("chattr");

    if(!is_dir("$sitepath")){
        echo "{warning}  $sitepath doesn't exists, installing the core module\n";
        if(!isset($GLOBALS["create_site($ID)"])){
            $GLOBALS["create_site($ID)"]=True;
            if(!create_site($ligne)){
                return false;
            }
        }

        return false;

    }


    echo "Apply permissions on $sitepath\n";
    system("$find $sitepath -type d -exec $chmod 755 {} +");
    if($prc>0){build_progress_build("$sitepath {apply_permissions} {directories}",$prc++);}
    system("$find $sitepath -type f -exec $chmod 644 {} +");
    if($prc>0){build_progress_build("$sitepath {apply_permissions} {files}",$prc++);}




    $wp_content_subs[]="upgrade";
    $wp_content_subs[]="themes";
    $wp_content_subs[]="uploads";
    $wp_content_subs[]="plugins";

    foreach ($wp_content_subs as $SubDir){
        if(!is_dir("$sitepath/wp-content/$SubDir")){continue;}
        echo "Apply permissions on $sitepath/wp-content/$SubDir\n";
        if($prc>0){build_progress_build("wp-content/$SubDir {apply_permissions} {directories}",$prc++);}
        system("$find $sitepath/wp-content/$SubDir -type d -exec $chmod 775 {} \;");
        system("$find $sitepath/wp-content/$SubDir -type f -exec $chmod 664 {} \;");
    }

    echo "Apply permissions on $sitepath/wp-content/uploads\n";
    if($prc>0){build_progress_build("wp-content/uploads {apply_permissions} {directories}",$prc++);}
    system("$find $sitepath/wp-content/uploads -type d -exec $chmod 775 {} \;");
    system("$chown -R $APACHE_USER:root $sitepath");
    system("$chmod 0400 $wpconfig_php");
    system("$chmod 0554 $sitepath");
    echo "Apply permissions done...\n";
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $wpconfig_content=@file_get_contents($wpconfig_php);
    $wpconfig_content=base64_encode($wpconfig_content);

    if(!$q->FIELD_EXISTS("wp_sites","wp_config")){$q->QUERY_SQL("ALTER TABLE wp_sites ADD wp_config TEXT");}
    $q->QUERY_SQL("UPDATE wp_sites SET wp_config='$wpconfig_content' WHERE ID=$ID");
    shell_exec("$chattr +i $wpconfig_php");



}
function remove_badfiles($ID){
    $sitepath="/home/wordpress_sites/site{$ID}";
    $f[]="license.php";
    $f[]="readme.html";
    $f[]="wp-config-sample.php";
    $f[]="phpinfo.php";

    foreach ($f as $filename){
        if(is_file("$sitepath/$filename")){@unlink("$sitepath/$filename");}
    }


}
function create_site($ligne,$prc=0){
    $qlite=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    if(!is_array($ligne)){
        if(is_numeric($ligne)){
            $ligne=$qlite->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ligne");
        }
    }

    $ID=intval($ligne["ID"]);
    if($ID==0){
        echo "$ID == 0 ????\n";
        return false;
    }
    $dbname=$ligne["database_name"];
    $dbuser="wpuser{$ID}";
    $dbpassword=md5(time());
    $hostname=$ligne["hostname"];
    $admin_email=$ligne["admin_email"];
    $admin_username=$ligne["admin_user"];
    $admin_password=$ligne["admin_password"];
    $sitepath="/home/wordpress_sites/site$ID";
    $admin_email=str_replace(";",".",$admin_email);

    $unix=new unix();

    if($admin_username==null){
        $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='Admin user is null' WHERE ID=$ID");
        return false;

    }


    $q=new mysql();
    build_progress_build("10% $hostname create database \"$dbname\"",$prc);
    if(!$q->DATABASE_EXISTS($dbname)){$q->CREATE_DATABASE($dbname);}
    if(!$q->DATABASE_EXISTS($dbname)){
        $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='unable to create database' WHERE ID=$ID");
        return false;
    }

    build_progress_build("20% $hostname create privileges",$prc);
    if(!$q->PRIVILEGES($dbuser,$dbpassword,$dbname)){
        $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='unable to assign privileges' WHERE ID=$ID");
        return false;

    }

    echo "Checking path $sitepath";

    if(!is_dir($sitepath)) {
        @mkdir($sitepath, 0755, true);
    }

    if(!is_dir($sitepath)){
        $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='$sitepath permission denied' WHERE ID=$ID");
        return false;
    }
    CleanViruses();

    build_progress_build("30% $hostname install website",$prc);
    if(!is_file("$sitepath/wp-admin/index.php")){
        $cp=$unix->find_program("cp");
        echo "Copy /usr/share/wordpress-src/* to $sitepath/\n";
        system("$cp -rfd /usr/share/wordpress-src/* $sitepath/");
    }
    $rm=$unix->find_program("rm");
    $wp_cli_phar=$unix->find_program("wp-cli.phar");

    if(is_file("$sitepath/wp-config.php")){
        @unlink("$sitepath/wp-config.php");
    }

    build_progress_build("40% building first configuration",$prc);
    $cmd="$wp_cli_phar config create --dbname=$dbname --dbuser=$dbuser --dbpass=$dbpassword --dbhost=localhost --dbprefix=prfx_ --allow-root --path=\"$sitepath\"";
    echo $cmd."\n";
    system($cmd);

    build_progress_build("50% installing $hostname",$prc);
    $admin_password=$unix->shellEscapeChars($admin_password);
    $cmd="$wp_cli_phar core install --url=$hostname --title=\"$hostname Title\" --admin_user=\"$admin_username\" --admin_password=$admin_password --admin_email=\"$admin_email\" --allow-root --path=\"$sitepath\" --skip-email 2>&1";



    echo $cmd."\n";
    exec($cmd,$results);

    foreach ($results as $line){
        if(preg_match("#Success.*?WordPress installed successfully#i",$line)){
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=1,database_error='' WHERE ID=$ID");
            return true;
        }
        if(preg_match("#WordPress is already installed#i",$line)){
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=1,database_error='' WHERE ID=$ID");
            return true;
        }
        if(preg_match("#Error:(.+)#",$line,$re)){
            shell_exec("$rm -rf $sitepath");
            $qlite->QUERY_SQL("UPDATE wp_sites SET status=999,database_error='{$re[1]}' WHERE ID=$ID");
            return false;
        }
    }
}
function Check_disable_wp_cron($ligne){

    $readonly=intval($ligne["readonly"]);
    $unix=new unix();
    $ID=intval($ligne["ID"]);
    if($ID==0){
        echo "$ID == 0 ????\n";
        return false;
    }
    $sitepath=get_sitepath($ID);
    $wp_config="$sitepath/wp-config.php";
    $wp_data=@file_get_contents($wp_config);
    $f=explode("\n",$wp_data);

    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#DISABLE_WP_CRON.*?true#i",$line)){
            echo "DISABLE_WP_CRON == TRUE\n";
            return true;
        }
    }

    echo "DISABLE_WP_CRON ==> PATCH\n";
    $wp_data=str_replace('$table_prefix',"define('DISABLE_WP_CRON', true);\n\$table_prefix",$wp_data);

    wp_config_openperms($wp_config);
    @file_put_contents($wp_config,$wp_data);
    wp_config_closeperms($wp_config);
    return true;
}
function wp_config_openperms($path){
    $unix=new unix();
    $chattr=$unix->find_program("chattr");
    shell_exec("$chattr -i $path");
    @chmod($path,0640);

}
function wp_config_closeperms($path){
    $unix=new unix();
    @chmod($path,0400);
    @chown($path,"www-data");
    $chattr=$unix->find_program("chattr");
    shell_exec("$chattr +i $path");
}
function Check_wpconfig_include($ligne){
    $unix=new unix();
    $ID=intval($ligne["ID"]);
    $sitepath=get_sitepath($ID);
    $wp_config="$sitepath/wp-config.php";
    $wp_data=@file_get_contents($wp_config);
    $f=explode("\n",$wp_data);
    if(!is_file("$sitepath/wp-artica.php")){@touch("$sitepath/wp-artica.php");}


    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#^require_once.*?wp-artica.php#i",$line)){
            echo "require_once == TRUE\n";
            return true;
        }
    }

    echo "Patching $wp_config for include Artica settings.\n";
    echo "\n\n";
    $newarray=array();
    $c=0;
    foreach ($f as $line){
        $c++;
        if(preg_match("#require_once#",$line)){
            echo "Patching $wp_config line $c\n";
            $newarray[]="require_once ABSPATH . 'wp-artica.php';";
        }
        $newarray[]=$line;
    }
    wp_config_openperms($wp_config);
    if(!@file_put_contents($wp_config,@implode("\n",$newarray))){
        echo "Saving $wp_config FAILED\n";
    }else{
        echo "Saving $wp_config SUCCESS\n";
    }
    wp_config_closeperms($wp_config);
    return true;

}
function ch_admin($siteID){
    $GLOBALS["INSTANCE_ID"]=$siteID;
    echo "ID $siteID\n";
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    build_progress_build("$hostname: {check_account}",40);
    if(!CheckUser($ligne)){
        build_progress_build("$hostname: {check_account} {failed}",110);
        return false;
    }
    build_progress_build("$hostname: {check_account} {success}",100);
    return true;
}
function upgrade_core($siteID,$version){
    $unix=new unix();
    $siteID=intval($siteID);
    if($siteID==0){
        build_progress( "$version no SiteID",110);
        return false;
    }

    $GLOBALS["INSTANCE_ID"]=$siteID;
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    build_progress_upgrade( "$version {upgrading}",20);
    $readonly=$ligne["readonly"];

    build_progress_upgrade( "$version {unlock} {directory}",30);
    wordpress_unlock($siteID);
    build_progress_upgrade( "$version {unlock} {directory} {done}",35);
    $tpmfile=$unix->FILE_TEMP();
    build_progress_upgrade( "$version tmpfile $tpmfile",36);
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$siteID";
    build_progress_upgrade( "$version {upgrading}",60);
    $cmd="$wp_cli_phar --allow-root core update --version=\"$version\" --path=$path >$tpmfile 2>&1";

    echo "$cmd\n";
    system($cmd);
    $f=explode("\n",@file_get_contents($tpmfile));
    foreach ($f as $line){echo "$line\n";}

    if($readonly==1){
        build_progress_upgrade( "{upgrading} {readonly} {lock}",70);
        wordpress_lock($siteID,70);
    }
    $GLOBALS["INSTANCE_ID"]=$siteID;
    build_progress_upgrade( "{checking} {version}",75);
    exec("$wp_cli_phar --allow-root core version --path=/home/wordpress_sites/site$siteID 2>&1",$results);
    $newvers=trim(@implode("",$results));
    build_progress_upgrade( "{checking} {version} $newvers",80);
    echo "Physical version:[{$GLOBALS["INSTANCE_ID"]}] <$newvers>\n";

    if($newvers==$version) {
        $q = new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $q->QUERY_SQL("UPDATE wp_sites SET wp_version='$newvers' WHERE ID=$siteID");
        echo "[{$GLOBALS["INSTANCE_ID"]}] Success!\n";
        $unix->framework_progress(100,"{success}","wordpress.single-install.$siteID");
        build_progress_upgrade( "{upgrading} $version {success}",100);
        return true;
    }
    $unix->framework_progress(110,"{upgrading} $version {failed} still $newvers","wordpress.single-install.$siteID");
    build_progress_upgrade("{upgrading} $version {failed} still $newvers",110);
    return false;
}
function uninstall_plugin($siteID,$plugin){
    $unix=new unix();
    $siteID=intval($siteID);
    if($siteID==0){
        build_progress( "$plugin no SiteID",110);
        return false;
    }
    $GLOBALS["INSTANCE_ID"]=$siteID;
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    $readonly=$ligne["readonly"];

    build_progress_install( "$hostname: $plugin {unlock} {directory}",20);
    wordpress_unlock($siteID);
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$siteID";
    build_progress_install( "$hostname: $plugin {uninstalling}",60);
    $cmd="$wp_cli_phar --allow-root plugin uninstall $plugin --deactivate --path=$path >/dev/null 2>&1";

    echo "$cmd\n";
    system($cmd);
    if($readonly==1){
        build_progress_install( "$hostname: {building} {readonly} {lock}",70);
        wordpress_lock($siteID);
    }
    build_progress_install(100, "$hostname: {uninstalling} $plugin {success}");
    return true;
}
function install_plugin($siteID,$fname){
    $unix=new unix();
    $filename=base64_decode($fname);
    $siteID=intval($siteID);
    if($siteID==0){
        build_progress( "$filename no SiteID",110);
        return false;
    }
    $GLOBALS["INSTANCE_ID"]=$siteID;

    $filepath = dirname(__FILE__) . "/ressources/conf/upload/$filename";
    if (!is_file($filepath)) {
        build_progress( "$filepath no such file",110);
        return false;
    }
    $TEMPDIR=$unix->TEMP_DIR();
    $srcfplug="$TEMPDIR/$filename";
    @copy($filepath,$srcfplug);
    @unlink($filepath);
    if(!is_file($srcfplug)){
        echo "$srcfplug No such file, permission denied or no space left\n";
        build_progress( "$filename {failed}",110);
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    $readonly=$ligne["readonly"];

    build_progress_install( "$hostname: $filename {unlock} {directory}",20);
    wordpress_unlock($siteID);
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$siteID";
    build_progress_install( "$hostname: $filename {installing}",60);
    $cmd="$wp_cli_phar --allow-root plugin install $srcfplug --force --activate --path=$path >/dev/null 2>&1";
    system($cmd);
    @unlink($srcfplug);
    if($readonly==1){
        build_progress_install( "$hostname: {building} {readonly} {lock}",70);
        wordpress_lock($siteID);
    }
    build_progress_install(100, "$hostname: $filename {success}");
    return true;
}
function install_theme($siteID,$fname){
    $unix=new unix();
    $filename=base64_decode($fname);
    $siteID=intval($siteID);
    if($siteID==0){
        build_progress( "$filename no SiteID",110);
        return false;
    }
    $GLOBALS["INSTANCE_ID"]=$siteID;

    $filepath = dirname(__FILE__) . "/ressources/conf/upload/$filename";
    if (!is_file($filepath)) {
        build_progress( "$filepath no such file",110);
        return false;
    }
    $TEMPDIR=$unix->TEMP_DIR();
    $srcfplug="$TEMPDIR/$filename";
    @copy($filepath,$srcfplug);
    @unlink($filepath);
    if(!is_file($srcfplug)){
        echo "$srcfplug No such file, permission denied or no space left\n";
        build_progress( "$filename {failed}",110);
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    $readonly=$ligne["readonly"];

    build_progress_install( "$hostname: $filename {unlock} {directory}",20);
    wordpress_unlock($siteID);
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$siteID";
    build_progress_install( "$hostname: $filename {installing}",60);
    $cmd="$wp_cli_phar --allow-root theme install $srcfplug --force --activate --path=$path >/dev/null 2>&1";
    system($cmd);
    @unlink($srcfplug);
    if($readonly==1){
        build_progress_install( "$hostname: {building} {readonly} {lock}",70);
        wordpress_lock($siteID);
    }
    build_progress_install(100, "$hostname: $filename {success}");
    return true;
}
function uninstall_theme($siteID,$theme){
    $unix=new unix();
    $siteID=intval($siteID);
    if($siteID==0){
        build_progress( "$theme no SiteID",110);
        return false;
    }
    $GLOBALS["INSTANCE_ID"]=$siteID;
    if($theme==null){
        build_progress( "$siteID no theme",110);
        return false;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$siteID");
    $hostname=$ligne["hostname"];
    $readonly=$ligne["readonly"];

    build_progress_install( "$hostname: $theme {unlock} {directory}",20);
    wordpress_unlock($siteID);
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    $path="/home/wordpress_sites/site$siteID";
    build_progress_install( "$hostname: $theme {uninstalling}",60);
    $cmd="$wp_cli_phar --allow-root theme uninstall $theme --force --path=$path >/dev/null 2>&1";

    echo "$cmd\n";
    system($cmd);
    if($readonly==1){
        build_progress_install( "$hostname: {building} {readonly} {lock}",70);
        wordpress_lock($siteID);
    }
    build_progress_install(100, "$hostname: {uninstalling} $theme {success}");
    return true;
}
function CheckUser($ligne){

    $ID=intval($ligne["ID"]);
    if($ID==0){
        echo "$ID == 0 ????\n";
        return false;
    }

    $admin_email=$ligne["admin_email"];
    $admin_email=str_replace(";",".",$admin_email);

    $admin_username=$ligne["admin_user"];
    $admin_password=$ligne["admin_password"];
    $sitepath="/home/wordpress_sites/site$ID";
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");

    $cmd="$wp_cli_phar --allow-root user list --format=csv --path=$sitepath 2>&1";
    echo "$cmd\n";
    exec("$cmd",$results);

    $admin_username_regex=$admin_username;
    $admin_username_regex=str_replace(".","\.",$admin_username_regex);
    $admin_username_regex=str_replace("$","\$",$admin_username_regex);
    $admin_username_regex=str_replace("/","\/",$admin_username_regex);
    $admin_username_regex=str_replace("!","\!",$admin_username_regex);
    $admin_username_regex=str_replace("*","\*",$admin_username_regex);
    $admin_password=$unix->shellEscapeChars($admin_password);

    foreach ($results as $line){
        if(preg_match("#([0-9]+),.*?$admin_username_regex#",$line,$re)){
            echo "Found user $admin_username_regex for ID {$re[1]}, update password $admin_password\n";
            system("$wp_cli_phar --allow-root user update {$re[1]} --user_pass=$admin_password --skip-email --path=$sitepath");
            return true;
        }else{
            echo "$line not found\n";
        }


    }

    echo "Creating user $admin_username\n";
    system("$wp_cli_phar user create $admin_username $admin_email --display_name=\"Full Administrator\" --user_pass=$admin_password --role=administrator --allow-root --path=$sitepath");
    
    return true;
}
// NOt used anymore.
function CheckSiteName($ligne){
    $hostname=$ligne["hostname"];
    $sitepath="/home/wordpress_sites/site{$ligne["ID"]}";
    $url="http://{$hostname}";
    $unix=new unix();
    $wp_cli_phar=$unix->find_program("wp-cli.phar");
    system("$wp_cli_phar --allow-root --path=$sitepath option update home \"$url\"");
    system("$wp_cli_phar --allow-root --path=$sitepath option update siteurl \"$url\"");

}

function download(){

    $unix=new unix();
    build_progress("{downloading}",50);
    $URI="http://wordpress.org/latest.tar.gz";

    $TMP_FILE=$unix->FILE_TEMP().".gz";
    $TMP_DIR=$unix->TEMP_DIR();
    echo "Downloading $URI\n";
    $curl=new ccurl($URI);
    $curl->WriteProgress=true;
    $curl->ProgressFunction="download_progress";
    if(!$curl->GetFile($TMP_FILE)){
        echo $curl->error;
        return false;
    }

    echo "Extracting $TMP_FILE in $TMP_DIR\n";
    $tar=$unix->find_program("tar");
    $cmd="$tar xf $TMP_FILE -C $TMP_DIR/";
    build_progress("{uncompress}",55);
    shell_exec($cmd);
    if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
    $dirs=$unix->dirdir($TMP_DIR);
    $WDP_DIR=null;
    foreach ($dirs as $ligne){
        if(!is_file("$ligne/wp-admin/install.php")){continue;}
        $WDP_DIR=$ligne;
        break;
        echo "Find Directory $ligne\n";
    }
    if(!is_dir($WDP_DIR)){
        build_progress("Find directory failed",110);
        echo "Find directory failed\n";
        return false;
    }
    build_progress("{installing}",80);
    @mkdir("/usr/share/wordpress-src",0755,true);
    $cp=$unix->find_program("cp");
    $rm=$unix->find_program("rm");
    shell_exec("cp -rfv $WDP_DIR/* /usr/share/wordpress-src/");
    if(is_dir($WDP_DIR)){
        echo "Removing $WDP_DIR\n";
        shell_exec("$rm -rf $WDP_DIR");
    }

    return true;

}

function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
    if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

    if ( $download_size == 0 ){
        $progress = 0;
    }else{
        $progress = round( $downloaded_size * 100 / $download_size );
    }

    if ( $progress > $GLOBALS["previousProgress"]){
        if($progress<95){
            echo "Downloading {$progress}% {$downloaded_size}/$download_size";
        }
        $GLOBALS["previousProgress"]=$progress;

    }
}