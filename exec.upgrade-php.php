<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ccurl.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

xstart();
function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"upgrade.php47.progress");
}
function xstart(){
    if(is_file("/etc/apt/sources.list.d/php.list")){
        @unlink("/etc/apt/sources.list.d/php.list");
    }
    $unix=new unix();
    $cp=$unix->find_program("cp");
    build_progress(20,"{downloading}...");
    $tar=$unix->find_program("tar");
    $tpm=$unix->TEMP_DIR()."/php";
    $tpmfile=$unix->FILE_TEMP().".tar.gz";
    $ftempsh=$unix->FILE_TEMP();
    $debian_version=$unix->DEBIAN_VERSION();
    if($debian_version==9){return false;}
    $curl=new ccurl("http://articatech.net/download/Debian10-php/7.40.tar.gz");
    @mkdir($tpm,0755,true);
    if(!$curl->GetFile($tpmfile)){
        build_progress(110,"{downloading} {failed}...");
        return false;
    }
    build_progress(30,"{extracting}...");
    shell_exec("$tar -xf $tpmfile -C $tpm/");
    build_progress(30,"{installing}...");
    $dirs[]="/usr/include";
    $dirs[]="/usr/lib";
    $dirs[]="/usr/share";
    $dirs[]="/usr/bin";
    $dirs[]="/usr/bin";
    $dirs[]="/etc/php";

    $f[]="#!/bin/sh";


    $php=$unix->LOCATE_PHP5_BIN();
    foreach ($dirs as $directory){
        echo "Copy $tpm$directory/* $directory/\n";
        $f[]="$cp -rf $tpm$directory/* $directory/";

    }


    echo "Checking /usr/bin/php7.4...\n";
    if(!is_file("/usr/bin/php7.4")){
        build_progress(110,"{failed}...");
        return false;
    }
    echo "Create symlink..\n";
    $f[]="/usr/bin/ln -sf /usr/bin/php7.4 /usr/bin/php";
    $DirsToRemove[]="/etc/php/7.2";
    $DirsToRemove[]="/etc/php/7.3";
    $DirsToRemove[]="/var/lib/php/modules/7.2";
    $DirsToRemove[]="/var/lib/php/modules/7.3";
    $DirsToRemove[]="/usr/lib/php/20151012";
    $DirsToRemove[]="/usr/lib/php/20170718";
    $DirsToRemove[]="/usr/lib/php/20180731";

    $rm=$unix->find_program("rm");
    foreach ($DirsToRemove as $dirname){
        if(!is_dir($dirname)){continue;}
        $f[]="$rm -rf $dirname || true";
    }
    $f[]="/usr/sbin/artica-phpfpm-service -build-pam";
    $f[]="/usr/sbin/artica-phpfpm-service -phpini -debug";
    $f[]="/etc/init.d/artica-phpfpm restart";
    $f[]="$rm -rf $tpm";
    $f[]="$rm -f $ftempsh";
    @file_put_contents("$ftempsh",@implode("\n",$f));
    @chmod($ftempsh,0755);
    $nohup=$unix->find_program("nohup");
    build_progress(100,"{success} 7.4...");
    shell_exec("$nohup $ftempsh >/dev/null 2>&1 &");


    build_progress(100,"{uninstalling} 7.3...");
    apt_mark_old();
    build_progress(100,"{uninstalling} 7.3...");
    purge_php73();
    return true;
}

function xstart_back(){

    $unix=new unix();
    build_progress(20,"{downloading}...");
    $curl=new ccurl("https://packages.sury.org/php/apt.gpg");
    $debian_version=$unix->DEBIAN_VERSION();


    if(!$curl->GetFile("/etc/apt/trusted.gpg.d/php.gpg")){
        @unlink("/etc/apt/trusted.gpg.d/php.gpg");
        echo $curl->error."\n";
        build_progress(110,"{downloading} {failed}...");
        return false;
    }
    $version_name=null;
    if($debian_version==10){$version_name="buster";}
    if($debian_version==9){$version_name="stretch";}
    if($version_name==null){
        build_progress(110,"{ERROR_OPERATING_SYSTEM_NOT_SUPPORTED} {failed}...");
        return false;
    }
    $aptget=$unix->find_program("apt-get");
    @file_put_contents("/etc/apt/sources.list.d/php.list","deb https://packages.sury.org/php/ $version_name main\n");
    build_progress(50,"{refreshing_status}...");
    system("DEBIAN_FRONTEND=noninteractive $aptget update -y --allow-releaseinfo-change");
    $c=60;

    $cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -y install php7.4 2>&1";
    system("$cmd");

    system("/usr/sbin/artica-phpfpm-service -apt-mark-hold");


    build_progress($c++,"{installing} php7.4...");
    $cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -y install php7.4 2>&1";
    system("$cmd");

    if(!is_file("/usr/bin/php7.4")) {
        build_progress(110,"{failed}...");
        return false;
    }

    if(is_file("/usr/sbin/pam-auth-update")){
        shell_exec("/usr/sbin/pam-auth-update --remove krb5 --force");
        shell_exec("/usr/sbin/pam-auth-update --remove ldap --force");
        shell_exec("/usr/sbin/pam-auth-update --remove winbind --force");
    }

    apt_mark_old();
    purge_php73();

    $f[]="php7.4-dev";
    $f[]="php7.4-fpm";
    foreach ($f as $package){
        build_progress($c++,"{installing} $package...");
        echo "Installing $package\n";
        $cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -y install $package 2>&1";
        system("$cmd");
    }


    $MINZ["/usr/lib/php/20190902/sqlite3.so"] = "php7.4-sqlite3";
    $MINZ["/usr/lib/php/20190902/ssh2.so"] = "php7.4-ssh2";
    $MINZ["/usr/lib/php/20190902/curl.so"] = "php7.4-curl";
    $MINZ["/usr/lib/php/20190902/geoip.so"] = "php7.4-geoip";
    $MINZ["/usr/lib/php/20190902/zip.so"] = "php7.4-zip";
    $MINZ["/usr/lib/php/20190902/mysqli.so"] = "php7.4-mysql";
    $MINZ["/usr/lib/php/20190902/memcached.so"] = "php7.4-memcached";
    $MINZ["/usr/lib/php/20190902/redis.so"] = "php7.4-redis";
    $MINZ["/usr/lib/php/20190902/uuid.so"]  = "php7.4-uuid";
    $MINZ["/usr/lib/php/20190902/ldap.so"]  = "php7.4-ldap";
    $MINZ["/usr/lib/php/20190902/pgsql.so"]="php7.4-pgsql";
    $MINZ["/usr/lib/php/20190902/simplexml.so"]="php7.4-xml";
    $MINZ["/usr/lib/php/20190902/dba.so"]="php7.4-dba";
    $MINZ["/usr/lib/php/20190902/xmlrpc.so"]="php7.4-xmlrpc";
    $MINZ["/usr/lib/php/20190902/snmp.so"]="php7.4-snmp";
    $MINZ["/usr/lib/php/20190902/gd.so"]="php7.4-gd";
    $MINZ["/usr/lib/php/20190902/uploadprogress.so"]="php7.4-uploadprogress";
    $MINZ["/usr/lib/php/20190902/msgpack.so"]="php7.4-msgpack";
    $MINZ["/usr/lib/php/20190902/igbinary.so"]="php7.4-igbinary";
    $MINZ["/usr/lib/php/20190902/rrd.so"]="php7.4-rrd";
    $MINZ["/usr/lib/php/20190902/pspell.so"]="php7.4-pspell";
    $MINZ["/usr/lib/php/20190902/mbstring.so"]="php7.4-mbstring";
    $MINZ["/usr/lib/php/20190902/mailparse.so"]="php7.4-mailparse";


    $CountofMinz=count($MINZ);
    $d=0;
    foreach ($MINZ as $library => $package){
        $d++;
        if(is_file($library)){continue;}
        build_progress($c++,"{installing} $package...$d/$CountofMinz");
        echo "Installing $package\n";
        $cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -y install $package 2>&1";
        system("$cmd");
    }
    build_progress(100,"{installing} {done}...");
    if(is_file("/usr/sbin/pam-auth-update")){
        shell_exec("/usr/sbin/pam-auth-update --remove krb5 --force");
        shell_exec("/usr/sbin/pam-auth-update --remove ldap --force");
        shell_exec("/usr/sbin/pam-auth-update --remove winbind --force");
    }


    echo "Sleeping.....\n";
    sleep(3);

    $php=$unix->find_program("php");
    $packages_failed=array();

    foreach ($MINZ as $library => $package){
        if(is_file($library)){continue;}
        $packages_failed[]=$package;
    }
    if(count($packages_failed)>0){
        squid_admin_mysql(0,count($packages_failed)." {packages} {failed_to_install}",@implode("\n",$packages_failed),__FILE__,__LINE__);
    }


    $DirsToRemove[]="/etc/php/7.2";
    $DirsToRemove[]="/etc/php/7.3";
    $DirsToRemove[]="/var/lib/php/modules/7.2";
    $DirsToRemove[]="/var/lib/php/modules/7.3";

    $rm=$unix->find_program("rm");
    foreach ($DirsToRemove as $dirname){
        if(!is_dir($dirname)){continue;}
        system("$rm -rf $dirname");
    }
    shell_exec("/usr/sbin/artica-phpfpm-service -build-pam");
    shell_exec("/usr/sbin/artica-phpfpm-service -phpini -debug");
    upgrade_php_fpm();
    return true;
}
function purge_php73(){

    $unix=new unix();
    $aptmark=$unix->find_program("apt-mark");
    $aptget=$unix->find_program("apt-get");


    $purge_a["apache2"]=true;
    $purge_a["apache2-data"]=true;
    $purge_a["firehol"]=true;
    $purge_a["php-memcached"]=true;
    $purge_a["php-redis"]=true;
    $purge_a["php-igbinary"]=true;
    $purge_a["php-msgpack"]=true;
    $purge_a["php-ssh2"]=true;
    $purge_a["php-uuid"]=true;
    $purge_a["php-zip"]=true;
    $CountOfPurge=count($purge_a);
    $d=0;
    foreach ($purge_a as $packagename=>$none){
        shell_exec("$aptmark unhold $packagename");
        $cmd="DEBIAN_FRONTEND=noninteractive $aptget remove -purge -yqq $packagename";
        system("$cmd");
    }
    $purge["php7.3-common"]=true;
    $purge["php7.3-cli"]=true;
    $purge["php7.3-curl"]=true;
    $purge["php7.3-dba"]=true;
    $purge["php7.3-fpm"]=true;
    $purge["php7.3-gd"]=true;
    $purge["php7.3-imap"]=true;
    $purge["php7.3-json"]=true;
    $purge["php7.3-ldap"]=true;
    $purge["php7.3-mbstring"]=true;
    $purge["php7.3-mysql"]=true;
    $purge["php7.3-opcache"]=true;
    $purge["php7.3-pgsql"]=true;
    $purge["php7.3-pspell"]=true;
    $purge["php7.3-readline"]=true;
    $purge["php7.3-snmp"]=true;
    $purge["php7.3-sqlite3"]=true;
    $purge["php7.3-xml"]=true;
    $purge["php7.3-xmlrpc"]=true;
    $purge["php7.3-zip"]=true;

    foreach ($purge as $packagename=>$none){
        shell_exec("$aptmark hold $packagename");
        $cmd="DEBIAN_FRONTEND=noninteractive $aptget remove --purge -y $packagename";
        system("$cmd");

    }
}

function apt_mark_old(){
    $unix=new unix();
    $aptmark=$unix->find_program("apt-mark");
    shell_exec("$aptmark hold php8.1-cli");
    shell_exec("$aptmark hold php8.1-common");
    shell_exec("$aptmark hold php8*");
    shell_exec("$aptmark hold libapache2-mod-php8*");
    shell_exec("$aptmark hold php8.2-cli");
    shell_exec("$aptmark hold php8.2-common");
    shell_exec("$aptmark hold php8.2-opcache");
    shell_exec("$aptmark hold php8.2-phpdbg");
    shell_exec("$aptmark hold php8.2-readline");
    shell_exec("$aptmark hold php8.2-uploadprogress");

    shell_exec("$aptmark hold php8.1-cli");
    shell_exec("$aptmark hold php8.1-common");
    shell_exec("$aptmark hold php8.1-opcache");
    shell_exec("$aptmark hold php8.1-phpdbg");
    shell_exec("$aptmark hold php8.1-readline");
    shell_exec("$aptmark hold php8.1-uploadprogress");

    shell_exec("$aptmark hold php8.3-cli");
    shell_exec("$aptmark hold php8.3-common");
    shell_exec("$aptmark hold php8.3-opcache");
    shell_exec("$aptmark hold php8.3-phpdbg");
    shell_exec("$aptmark hold php8.3-readline");
    shell_exec("$aptmark hold php8.3-uploadprogress");
}

function upgrade_php_fpm():bool{
    $unix=new unix();
    $tmpfile=$unix->FILE_TEMP().".tar.gz";
    $wget="/usr/bin/wget";
    $url="http://articatech.net/download/php-fpm/php7.4-fpm.tar.gz";
    shell_exec("$wget \"$url\" -O $tmpfile");
    if(!is_file($tmpfile)){return false;}
    shell_exec("/bin/tar -xf $tmpfile -C /usr/sbin/");
    shell_exec("/etc/init.d/artica-phpfpm restart");
    return true;

}


function remove_php_74(){
    $php74[]="/usr/lib/tmpfiles.d";
    $php74[]="/usr/lib/tmpfiles.d/php7.4-fpm.conf";
    $php74[]="/usr/lib/cgi-bin/php7.4";
    $php74[]="/usr/lib/debug/.build-id/f5/bcab25f94d12422c91bf3187fc285f8fc1f943.debug";
    $php74[]="/usr/lib/php/7.4/php.ini-development";
    $php74[]="/usr/lib/php/7.4/php.ini-production.cli";
    $php74[]="/usr/lib/php/7.4/php.ini-production";
    $php74[]="/usr/lib/php/7.4/sapi";
    $php74[]="/usr/lib/php/7.4/sapi/fpm";
    $php74[]="/usr/lib/php/7.4/sapi/cgi";
    $php74[]="/usr/lib/php/7.4/sapi/cli";
    $php74[]="/usr/lib/php/php7.4-fpm-reopenlogs";
    $php74[]="/usr/lib/php/20190902/pdo_mysql.so";
    $php74[]="/usr/lib/php/20190902/pdo.so";
    $php74[]="/usr/lib/php/20190902/bcmath.so";
    $php74[]="/usr/lib/php/20190902/soap.so";
    $php74[]="/usr/lib/php/20190902/json.so";
    $php74[]="/usr/lib/php/20190902/mbstring.so";
    $php74[]="/usr/lib/php/20190902/fileinfo.so";
    $php74[]="/usr/lib/php/20190902/exif.so";
    $php74[]="/usr/lib/php/20190902/posix.so";
    $php74[]="/usr/lib/php/20190902/tokenizer.so";
    $php74[]="/usr/lib/php/20190902/bz2.so";
    $php74[]="/usr/lib/php/20190902/ctype.so";
    $php74[]="/usr/lib/php/20190902/xmlwriter.so";
    $php74[]="/usr/lib/php/20190902/gd.so";
    $php74[]="/usr/lib/php/20190902/iconv.so";
    $php74[]="/usr/lib/php/20190902/sqlite3.so";
    $php74[]="/usr/lib/php/20190902/curl.so";
    $php74[]="/usr/lib/php/20190902/mysqli.so";
    $php74[]="/usr/lib/php/20190902/mysqlnd.so";
    $php74[]="/usr/lib/php/20190902/xsl.so";
    $php74[]="/usr/lib/php/20190902/sysvshm.so";
    $php74[]="/usr/lib/php/20190902/sysvmsg.so";
    $php74[]="/usr/lib/php/20190902/pgsql.so";
    $php74[]="/usr/lib/php/20190902/opcache.so";
    $php74[]="/usr/lib/php/20190902/gettext.so";
    $php74[]="/usr/lib/php/20190902/dom.so";
    $php74[]="/usr/lib/php/20190902/pdo_odbc.so";
    $php74[]="/usr/lib/php/20190902/ldap.so";
    $php74[]="/usr/lib/php/20190902/imap.so";
    $php74[]="/usr/lib/php/20190902/ftp.so";
    $php74[]="/usr/lib/php/20190902/calendar.so";
    $php74[]="/usr/lib/php/20190902/pdo_sqlite.so";
    $php74[]="/usr/lib/php/20190902/phar.so";
    $php74[]="/usr/lib/php/20190902/xmlrpc.so";
    $php74[]="/usr/lib/php/20190902/pdo_pgsql.so";
    $php74[]="/usr/lib/php/20190902/zip.so";
    $php74[]="/usr/lib/php/20190902/sockets.so";
    $php74[]="/usr/lib/php/20190902/xmlreader.so";
    $php74[]="/usr/lib/php/20190902/intl.so";
    $php74[]="/usr/lib/php/20190902/simplexml.so";
    $php74[]="/usr/lib/php/20190902/shmop.so";
    $php74[]="/usr/lib/php/20190902/build";
    $php74[]="/usr/lib/php/20190902/build/phpize.m4";
    $php74[]="/usr/lib/php/20190902/build/run-tests.php";
    $php74[]="/usr/lib/php/20190902/build/Makefile.global";
    $php74[]="/usr/lib/php/20190902/build/ax_gcc_func_attribute.m4";
    $php74[]="/usr/lib/php/20190902/build/ax_check_compile_flag.m4";
    $php74[]="/usr/lib/php/20190902/build/php_cxx_compile_stdcxx.m4";
    $php74[]="/usr/lib/php/20190902/build/php.m4";
    $php74[]="/usr/lib/php/20190902/sysvsem.so";
    $php74[]="/usr/lib/php/20190902/ffi.so";
    $php74[]="/usr/lib/php/20190902/pspell.so";
    $php74[]="/usr/lib/php/20190902/xml.so";
    $php74[]="/usr/lib/php/20190902/odbc.so";
    $php74[]="/usr/lib/php/20190902/snmp.so";
    $php74[]="/usr/lib/php/20190902/tidy.so";
    $php74[]="/usr/lib/php/20190902/dba.so";
    $php74[]="/usr/include/php/20190902/TSRM";
    $php74[]="/usr/include/php/20190902/TSRM/TSRM.h";
    $php74[]="/usr/include/php/20190902/TSRM/tsrm_win32.h";
    $php74[]="/usr/include/php/20190902/Zend";
    $php74[]="/usr/include/php/20190902/Zend/zend_hash.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_smart_string.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_multiply.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm_opcodes.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm_trace_map.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_variables.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_cpuinfo.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ini_parser.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ini_scanner_defs.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm_def.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_iterators.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_list.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_dtrace_gen.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_objects_API.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_execute.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_bitset.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_objects.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_istdiostream.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ts_hash.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_highlight.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_language_parser.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_object_handlers.h";
    $php74[]="/usr/include/php/20190902/Zend/zend.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_language_scanner_defs.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_multibyte.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_interfaces.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_alloc.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_constants.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_compile.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_language_scanner.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_strtod_int.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_strtod.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_dtrace.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_smart_str_public.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_generators.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ini_scanner.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_globals_macros.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_smart_str.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_arena.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_weakrefs.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_config.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_alloc_sizes.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_portability.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_build.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_operators.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_type_info.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_API.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_closures.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_string.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_errors.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_config.w32.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_gc.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_inheritance.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm_handlers.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_float.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_map_ptr.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_modules.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ast.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ini.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_extensions.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_signal.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_exceptions.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_long.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_smart_string_public.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_virtual_cwd.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_range_check.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_sort.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_stack.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_globals.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_llist.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm_trace_handlers.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_ptr_stack.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm_execute.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_stream.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_builtin_functions.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_vm.h";
    $php74[]="/usr/include/php/20190902/Zend/zend_types.h";
    $php74[]="/usr/include/php/20190902/include";
    $php74[]="/usr/include/php/20190902/main";
    $php74[]="/usr/include/php/20190902/main/php_ticks.h";
    $php74[]="/usr/include/php/20190902/main/php_globals.h";
    $php74[]="/usr/include/php/20190902/main/php_reentrancy.h";
    $php74[]="/usr/include/php/20190902/main/php_network.h";
    $php74[]="/usr/include/php/20190902/main/php_ini.h";
    $php74[]="/usr/include/php/20190902/main/php_streams.h";
    $php74[]="/usr/include/php/20190902/main/php_content_types.h";
    $php74[]="/usr/include/php/20190902/main/http_status_codes.h";
    $php74[]="/usr/include/php/20190902/main/SAPI.h";
    $php74[]="/usr/include/php/20190902/main/php_scandir.h";
    $php74[]="/usr/include/php/20190902/main/fastcgi.h";
    $php74[]="/usr/include/php/20190902/main/rfc1867.h";
    $php74[]="/usr/include/php/20190902/main/streams";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_mmap.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_streams_int.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_userspace.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_plain_wrapper.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_context.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_transport.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_glob_wrapper.h";
    $php74[]="/usr/include/php/20190902/main/streams/php_stream_filter_api.h";
    $php74[]="/usr/include/php/20190902/main/php_memory_streams.h";
    $php74[]="/usr/include/php/20190902/main/snprintf.h";
    $php74[]="/usr/include/php/20190902/main/php.h";
    $php74[]="/usr/include/php/20190902/main/spprintf.h";
    $php74[]="/usr/include/php/20190902/main/php_getopt.h";
    $php74[]="/usr/include/php/20190902/main/php_open_temporary_file.h";
    $php74[]="/usr/include/php/20190902/main/php_version.h";
    $php74[]="/usr/include/php/20190902/main/php_syslog.h";
    $php74[]="/usr/include/php/20190902/main/php_stdint.h";
    $php74[]="/usr/include/php/20190902/main/php_compat.h";
    $php74[]="/usr/include/php/20190902/main/build-defs.h";
    $php74[]="/usr/include/php/20190902/main/php_variables.h";
    $php74[]="/usr/include/php/20190902/main/php_main.h";
    $php74[]="/usr/include/php/20190902/main/fopen_wrappers.h";
    $php74[]="/usr/include/php/20190902/main/php_config.h";
    $php74[]="/usr/include/php/20190902/main/php_output.h";
    $php74[]="/usr/include/php/20190902/ext";
    $php74[]="/usr/include/php/20190902/ext/gd";
    $php74[]="/usr/include/php/20190902/ext/gd/gd_compat.h";
    $php74[]="/usr/include/php/20190902/ext/gd/php_gd.h";
    $php74[]="/usr/include/php/20190902/ext/xml";
    $php74[]="/usr/include/php/20190902/ext/xml/php_xml.h";
    $php74[]="/usr/include/php/20190902/ext/xml/expat_compat.h";
    $php74[]="/usr/include/php/20190902/ext/dom";
    $php74[]="/usr/include/php/20190902/ext/dom/xml_common.h";
    $php74[]="/usr/include/php/20190902/ext/pcre";
    $php74[]="/usr/include/php/20190902/ext/pcre/php_pcre.h";
    $php74[]="/usr/include/php/20190902/ext/simplexml";
    $php74[]="/usr/include/php/20190902/ext/simplexml/php_simplexml.h";
    $php74[]="/usr/include/php/20190902/ext/simplexml/php_simplexml_exports.h";
    $php74[]="/usr/include/php/20190902/ext/filter";
    $php74[]="/usr/include/php/20190902/ext/filter/php_filter.h";
    $php74[]="/usr/include/php/20190902/ext/iconv";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_have_bsd_iconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_have_glibc_iconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_have_libiconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_php_iconv_impl.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_have_iconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_iconv_supports_errno.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_iconv_aliased_libiconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_iconv_broken_ignore.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_have_ibm_iconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_iconv.h";
    $php74[]="/usr/include/php/20190902/ext/iconv/php_php_iconv_h_path.h";
    $php74[]="/usr/include/php/20190902/ext/session";
    $php74[]="/usr/include/php/20190902/ext/session/mod_files.h";
    $php74[]="/usr/include/php/20190902/ext/session/php_session.h";
    $php74[]="/usr/include/php/20190902/ext/session/mod_user.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring";
    $php74[]="/usr/include/php/20190902/ext/mbstring/php_mbregex.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/php_onig_compat.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/config.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_defs.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_allocators.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_string.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfilter_8bit.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfilter.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_convert.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_consts.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_ident.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_encoding.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_memory_device.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/eaw_table.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfilter_pass.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_filter_output.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfl_language.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/libmbfl/mbfl/mbfilter_wchar.h";
    $php74[]="/usr/include/php/20190902/ext/mbstring/mbstring.h";
    $php74[]="/usr/include/php/20190902/ext/libxml";
    $php74[]="/usr/include/php/20190902/ext/libxml/php_libxml.h";
    $php74[]="/usr/include/php/20190902/ext/date";
    $php74[]="/usr/include/php/20190902/ext/date/lib";
    $php74[]="/usr/include/php/20190902/ext/date/lib/timelib_config.h";
    $php74[]="/usr/include/php/20190902/ext/date/lib/timelib.h";
    $php74[]="/usr/include/php/20190902/ext/date/php_date.h";
    $php74[]="/usr/include/php/20190902/ext/gmp";
    $php74[]="/usr/include/php/20190902/ext/gmp/php_gmp_int.h";
    $php74[]="/usr/include/php/20190902/ext/standard";
    $php74[]="/usr/include/php/20190902/ext/standard/datetime.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_lcg.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_versioning.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_image.h";
    $php74[]="/usr/include/php/20190902/ext/standard/head.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_math.h";
    $php74[]="/usr/include/php/20190902/ext/standard/flock_compat.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_dir.h";
    $php74[]="/usr/include/php/20190902/ext/standard/microtime.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_uuencode.h";
    $php74[]="/usr/include/php/20190902/ext/standard/crc32.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_crypt_r.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_browscap.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_smart_string_public.h";
    $php74[]="/usr/include/php/20190902/ext/standard/dl.h";
    $php74[]="/usr/include/php/20190902/ext/standard/quot_print.h";
    $php74[]="/usr/include/php/20190902/ext/standard/url_scanner_ex.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_rand.h";
    $php74[]="/usr/include/php/20190902/ext/standard/crypt_freesec.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_filestat.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_http.h";
    $php74[]="/usr/include/php/20190902/ext/standard/winver.h";
    $php74[]="/usr/include/php/20190902/ext/standard/file.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_dns.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_crypt.h";
    $php74[]="/usr/include/php/20190902/ext/standard/scanf.h";
    $php74[]="/usr/include/php/20190902/ext/standard/sha1.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_password.h";
    $php74[]="/usr/include/php/20190902/ext/standard/proc_open.h";
    $php74[]="/usr/include/php/20190902/ext/standard/hrtime.h";
    $php74[]="/usr/include/php/20190902/ext/standard/basic_functions.h";
    $php74[]="/usr/include/php/20190902/ext/standard/css.h";
    $php74[]="/usr/include/php/20190902/ext/standard/streamsfuncs.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_iptc.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_mt_rand.h";
    $php74[]="/usr/include/php/20190902/ext/standard/fsock.h";
    $php74[]="/usr/include/php/20190902/ext/standard/exec.h";
    $php74[]="/usr/include/php/20190902/ext/standard/pageinfo.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_ext_syslog.h";
    $php74[]="/usr/include/php/20190902/ext/standard/url.h";
    $php74[]="/usr/include/php/20190902/ext/standard/cyr_convert.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_standard.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_random.h";
    $php74[]="/usr/include/php/20190902/ext/standard/info.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_metaphone.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_smart_string.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_ftok.h";
    $php74[]="/usr/include/php/20190902/ext/standard/credits.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_incomplete_class.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_array.h";
    $php74[]="/usr/include/php/20190902/ext/standard/crypt_blowfish.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_link.h";
    $php74[]="/usr/include/php/20190902/ext/standard/html_tables.h";
    $php74[]="/usr/include/php/20190902/ext/standard/base64.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_fopen_wrappers.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_string.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_net.h";
    $php74[]="/usr/include/php/20190902/ext/standard/md5.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_assert.h";
    $php74[]="/usr/include/php/20190902/ext/standard/credits_ext.h";
    $php74[]="/usr/include/php/20190902/ext/standard/html.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_var.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_type.h";
    $php74[]="/usr/include/php/20190902/ext/standard/pack.h";
    $php74[]="/usr/include/php/20190902/ext/standard/credits_sapi.h";
    $php74[]="/usr/include/php/20190902/ext/standard/php_mail.h";
    $php74[]="/usr/include/php/20190902/ext/standard/uniqid.h";
    $php74[]="/usr/include/php/20190902/ext/spl";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_engine.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_exceptions.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_observer.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_heap.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_array.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_dllist.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_directory.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_functions.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_iterators.h";
    $php74[]="/usr/include/php/20190902/ext/spl/php_spl.h";
    $php74[]="/usr/include/php/20190902/ext/spl/spl_fixedarray.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_enum_n_def.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysql_float_to_double.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_alloc.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/config-win.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_protocol_frame_codec.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_portability.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_debug.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_charset.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_ps.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_wireprotocol.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_commands.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_priv.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_libmysql_compat.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_connection.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_result_meta.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_read_buffer.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_plugin.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_auth.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_vio.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_block_alloc.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_result.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_reverse_api.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_ext_plugin.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_statistics.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd_structs.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/php_mysqlnd.h";
    $php74[]="/usr/include/php/20190902/ext/mysqlnd/mysqlnd.h";
    $php74[]="/usr/include/php/20190902/ext/json";
    $php74[]="/usr/include/php/20190902/ext/json/php_json_parser.h";
    $php74[]="/usr/include/php/20190902/ext/json/php_json.h";
    $php74[]="/usr/include/php/20190902/ext/json/php_json_scanner.h";
    $php74[]="/usr/include/php/20190902/ext/mysqli";
    $php74[]="/usr/include/php/20190902/ext/mysqli/mysqli_mysqlnd.h";
    $php74[]="/usr/include/php/20190902/ext/mysqli/php_mysqli_structs.h";
    $php74[]="/usr/include/php/20190902/ext/pdo";
    $php74[]="/usr/include/php/20190902/ext/pdo/php_pdo_driver.h";
    $php74[]="/usr/include/php/20190902/ext/pdo/php_pdo.h";
    $php74[]="/usr/include/php/20190902/ext/pdo/php_pdo_error.h";
    $php74[]="/usr/include/php/20190902/ext/sockets";
    $php74[]="/usr/include/php/20190902/ext/sockets/php_sockets.h";
    $php74[]="/usr/include/php/20190902/ext/hash";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_whirlpool.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_tiger.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_joaat.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_crc32.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_adler32.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_fnv.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_sha3.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_gost.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_md.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_sha.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_haval.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_snefru.h";
    $php74[]="/usr/include/php/20190902/ext/hash/php_hash_ripemd.h";
    $php74[]="/usr/include/php/20190902/ext/phar";
    $php74[]="/usr/include/php/20190902/ext/phar/php_phar.h";
    $php74[]="/usr/include/php/20190902/sapi";
    $php74[]="/usr/include/php/20190902/sapi/embed";
    $php74[]="/usr/include/php/20190902/sapi/embed/php_embed.h";
    $php74[]="/usr/include/php/20190902/sapi/cli";
    $php74[]="/usr/include/php/20190902/sapi/cli/cli.h";
    $php74[]="/usr/share/php7.4-zip";
    $php74[]="/usr/share/php7.4-zip/zip";
    $php74[]="/usr/share/php7.4-zip/zip/zip.ini";
    $php74[]="/usr/share/php7.4-pspell";
    $php74[]="/usr/share/php7.4-pspell/pspell";
    $php74[]="/usr/share/php7.4-pspell/pspell/pspell.ini";
    $php74[]="/usr/share/php7.4-json";
    $php74[]="/usr/share/php7.4-json/json";
    $php74[]="/usr/share/php7.4-json/json/json.ini";
    $php74[]="/usr/share/php7.4-mysql";
    $php74[]="/usr/share/php7.4-mysql/mysql";
    $php74[]="/usr/share/php7.4-mysql/mysql/mysqlnd.ini";
    $php74[]="/usr/share/php7.4-mysql/mysql/pdo_mysql.ini";
    $php74[]="/usr/share/php7.4-mysql/mysql/mysqli.ini";
    $php74[]="/usr/share/php7.4-bz2";
    $php74[]="/usr/share/php7.4-bz2/bz2";
    $php74[]="/usr/share/php7.4-bz2/bz2/bz2.ini";
    $php74[]="/usr/share/php7.4-curl";
    $php74[]="/usr/share/php7.4-curl/curl";
    $php74[]="/usr/share/php7.4-curl/curl/curl.ini";
    $php74[]="/usr/share/lintian";
    $php74[]="/usr/share/lintian/overrides";
    $php74[]="/usr/share/lintian/overrides/php7.4-zip";
    $php74[]="/usr/share/lintian/overrides/php7.4-pspell";
    $php74[]="/usr/share/lintian/overrides/php7.4-json";
    $php74[]="/usr/share/lintian/overrides/php7.4-mysql";
    $php74[]="/usr/share/lintian/overrides/php7.4-fpm";
    $php74[]="/usr/share/lintian/overrides/php7.4-bz2";
    $php74[]="/usr/share/lintian/overrides/php7.4-curl";
    $php74[]="/usr/share/lintian/overrides/php7.4-sqlite3";
    $php74[]="/usr/share/lintian/overrides/php7.4-xmlrpc";
    $php74[]="/usr/share/lintian/overrides/php7.4-snmp";
    $php74[]="/usr/share/lintian/overrides/php7.4-pgsql";
    $php74[]="/usr/share/lintian/overrides/php7.4-odbc";
    $php74[]="/usr/share/lintian/overrides/php7.4-cgi";
    $php74[]="/usr/share/lintian/overrides/php7.4-tidy";
    $php74[]="/usr/share/lintian/overrides/php7.4-ldap";
    $php74[]="/usr/share/lintian/overrides/php7.4-dev";
    $php74[]="/usr/share/lintian/overrides/php7.4-common";
    $php74[]="/usr/share/lintian/overrides/php7.4-bcmath";
    $php74[]="/usr/share/lintian/overrides/php7.4-opcache";
    $php74[]="/usr/share/lintian/overrides/php7.4-cli";
    $php74[]="/usr/share/lintian/overrides/php7.4-gd";
    $php74[]="/usr/share/lintian/overrides/php7.4-mbstring";
    $php74[]="/usr/share/lintian/overrides/php7.4-soap";
    $php74[]="/usr/share/lintian/overrides/php7.4-xml";
    $php74[]="/usr/share/lintian/overrides/php7.4-imap";
    $php74[]="/usr/share/lintian/overrides/php7.4-intl";
    $php74[]="/usr/share/lintian/overrides/php7.4-dba";
    $php74[]="/usr/share/php7.4-sqlite3";
    $php74[]="/usr/share/php7.4-sqlite3/sqlite3";
    $php74[]="/usr/share/php7.4-sqlite3/sqlite3/pdo_sqlite.ini";
    $php74[]="/usr/share/php7.4-sqlite3/sqlite3/sqlite3.ini";
    $php74[]="/usr/share/php7.4-xmlrpc";
    $php74[]="/usr/share/php7.4-xmlrpc/xmlrpc";
    $php74[]="/usr/share/php7.4-xmlrpc/xmlrpc/xmlrpc.ini";
    $php74[]="/usr/share/php7.4-snmp";
    $php74[]="/usr/share/php7.4-snmp/snmp";
    $php74[]="/usr/share/php7.4-snmp/snmp/snmp.ini";
    $php74[]="/usr/share/php7.4-pgsql";
    $php74[]="/usr/share/php7.4-pgsql/pgsql";
    $php74[]="/usr/share/php7.4-pgsql/pgsql/pdo_pgsql.ini";
    $php74[]="/usr/share/php7.4-pgsql/pgsql/pgsql.ini";
    $php74[]="/usr/share/php7.4-odbc";
    $php74[]="/usr/share/php7.4-odbc/odbc";
    $php74[]="/usr/share/php7.4-odbc/odbc/pdo_odbc.ini";
    $php74[]="/usr/share/php7.4-odbc/odbc/odbc.ini";
    $php74[]="/usr/share/php7.4-tidy";
    $php74[]="/usr/share/php7.4-tidy/tidy";
    $php74[]="/usr/share/php7.4-tidy/tidy/tidy.ini";
    $php74[]="/usr/share/php7.4-ldap";
    $php74[]="/usr/share/php7.4-ldap/ldap";
    $php74[]="/usr/share/php7.4-ldap/ldap/ldap.ini";
    $php74[]="/usr/share/php7.4-common";
    $php74[]="/usr/share/php7.4-common/common";
    $php74[]="/usr/share/php7.4-common/common/exif.ini";
    $php74[]="/usr/share/php7.4-common/common/sysvmsg.ini";
    $php74[]="/usr/share/php7.4-common/common/sockets.ini";
    $php74[]="/usr/share/php7.4-common/common/shmop.ini";
    $php74[]="/usr/share/php7.4-common/common/fileinfo.ini";
    $php74[]="/usr/share/php7.4-common/common/ffi.ini";
    $php74[]="/usr/share/php7.4-common/common/ctype.ini";
    $php74[]="/usr/share/php7.4-common/common/ftp.ini";
    $php74[]="/usr/share/php7.4-common/common/phar.ini";
    $php74[]="/usr/share/php7.4-common/common/pdo.ini";
    $php74[]="/usr/share/php7.4-common/common/sysvsem.ini";
    $php74[]="/usr/share/php7.4-common/common/gettext.ini";
    $php74[]="/usr/share/php7.4-common/common/calendar.ini";
    $php74[]="/usr/share/php7.4-common/common/sysvshm.ini";
    $php74[]="/usr/share/php7.4-common/common/tokenizer.ini";
    $php74[]="/usr/share/php7.4-common/common/iconv.ini";
    $php74[]="/usr/share/php7.4-common/common/posix.ini";
    $php74[]="/usr/share/php7.4-bcmath";
    $php74[]="/usr/share/php7.4-bcmath/bcmath";
    $php74[]="/usr/share/php7.4-bcmath/bcmath/bcmath.ini";
    $php74[]="/usr/share/php/7.4";
    $php74[]="/usr/share/php/7.4/fpm";
    $php74[]="/usr/share/php/7.4/fpm/status.html";
    $php74[]="/usr/share/php7.4-opcache";
    $php74[]="/usr/share/php7.4-opcache/opcache";
    $php74[]="/usr/share/php7.4-opcache/opcache/opcache.ini";
    $php74[]="/usr/share/php7.4-gd";
    $php74[]="/usr/share/php7.4-gd/gd";
    $php74[]="/usr/share/php7.4-gd/gd/gd.ini";
    $php74[]="/usr/share/php7.4-mbstring";
    $php74[]="/usr/share/php7.4-mbstring/mbstring";
    $php74[]="/usr/share/php7.4-mbstring/mbstring/mbstring.ini";
    $php74[]="/usr/share/php7.4-soap";
    $php74[]="/usr/share/php7.4-soap/soap";
    $php74[]="/usr/share/php7.4-soap/soap/soap.ini";
    $php74[]="/usr/share/php7.4-xml";
    $php74[]="/usr/share/php7.4-xml/xml";
    $php74[]="/usr/share/php7.4-xml/xml/xmlreader.ini";
    $php74[]="/usr/share/php7.4-xml/xml/dom.ini";
    $php74[]="/usr/share/php7.4-xml/xml/xmlwriter.ini";
    $php74[]="/usr/share/php7.4-xml/xml/simplexml.ini";
    $php74[]="/usr/share/php7.4-xml/xml/xml.ini";
    $php74[]="/usr/share/php7.4-xml/xml/xsl.ini";
    $php74[]="/usr/share/php7.4-imap";
    $php74[]="/usr/share/php7.4-imap/imap";
    $php74[]="/usr/share/php7.4-imap/imap/imap.ini";
    $php74[]="/usr/share/php7.4-intl";
    $php74[]="/usr/share/php7.4-intl/intl";
    $php74[]="/usr/share/php7.4-intl/intl/intl.ini";
    $php74[]="/usr/share/php7.4-dba";
    $php74[]="/usr/share/php7.4-dba/dba";
    $php74[]="/usr/share/php7.4-dba/dba/dba.ini";
    $php74[]="/usr/bin/phar7.4";
    $php74[]="/usr/bin/php-config7.4";
    $php74[]="/usr/bin/php-cgi7.4";
    $php74[]="/usr/bin/phar.phar7.4";
    $php74[]="/usr/bin/phpize7.4";
    $php74[]="/usr/bin/phar7.4.phar";
    $php74[]="/usr/bin/php7.4";
    $php74[]="/usr/sbin/php-fpm7.4";
    $php74[]="/etc/php/7.4/mods-available";
    $php74[]="/etc/php/7.4/fpm";
    $php74[]="/etc/php/7.4/fpm/php-fpm.conf";
    $php74[]="/etc/php/7.4/fpm/conf.d";
    $php74[]="/etc/php/7.4/fpm/pool.d";
    $php74[]="/etc/php/7.4/fpm/pool.d/www.conf";
    $php74[]="/etc/php/7.4/cgi";
    $php74[]="/etc/php/7.4/cgi/conf.d";
    $php74[]="/etc/php/7.4/cli";
    $php74[]="/etc/php/7.4/cli/conf.d";


    foreach ($php74 as $filepath){
        if(!is_file($filepath)){@unlink($filepath);}
    }

    if(is_dir("/usr/include/php/20190902")){
        shell_exec("/bin/rm -rf /usr/include/php/20190902");
    }
    if(is_dir("/usr/lib/php/20190902")){
        shell_exec("/bin/rm -rf /usr/lib/php/20190902");
    }
    if(is_dir("/etc/php/7.4")){
        shell_exec("/bin/rm -rf /etc/php/7.4");
    }


}






