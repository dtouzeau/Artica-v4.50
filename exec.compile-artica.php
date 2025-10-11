#!/usr/bin/php

<?php
/**
 * Created by PhpStorm.
 * User: dtouzeau
 * Date: 17/11/18
 * Time: 08:13
 */

$MainVer="4.50";
$compiledate=date("mdH");
$compiledate="000000";
$VERSION="$MainVer.$compiledate";
system('rm -rf /usr/share/artica-postfix/ressources/backup');
system('rm -rf /usr/share/artica-postfix/ressources/conf/*');
system('rm -rf /usr/share/artica-postfix/ressources/logs/*');
system('rm -rf /usr/share/artica-postfix/ressources/userdb/*');
system('rm -rf /usr/share/artica-postfix/ressources/settings.inc');
system('rm -rf /usr/share/artica-postfix/ressources/install/*');
system('rm -rf /usr/share/artica-postfix/ressources/databases/*.cache');
system('rm -f  /usr/share/artica-postfix/ressources/usb.scan.inc');
system('rm -rf /usr/share/artica-postfix/ressources/settings.inc');
system('rm -rf /usr/share/artica-postfix/ressources/settings.new.inc');
system('rm -rf /usr/share/artica-postfix/PATCH');
system('rm -rf /usr/share/artica-postfix/PATCHS_HISTORY');
system('rm -rf /usr/share/artica-postfix/ressources/logs/*');
system('rm -rf /usr/share/artica-postfix/ressources/dar_collection');
system('rm -rf /usr/share/artica-postfix/artica-install');
system('rm -rf /usr/share/artica-postfix/class.templates.inc');
system('rm -rf /usr/share/artica-postfix/user-backup/ressources/profiles/icons/*.*');
system('rm -rf /usr/share/artica-postfix/user-backup/ressources/conf/upload/*.*');
system('rm -f /usr/share/artica-postfix/nohup.out');
system('/bin/rm -f /usr/share/artica-postfix/ressources/settings.inc');
system('/bin/rm -f /usr/share/artica-postfix/ressources/settings.new.inc');

$langues["fr"]=true;
$langues["it"]=true;
$langues["de"]=true;
$langues["po"]=true;
$langues["es"]=true;
$langues["br"]=true;
$langues["pol"]=true;

foreach ($langues as $lang=>$none){

    echo "Compile $lang\n";
    system("wget http://www.artica.fr/export.lang.php?lang=$lang -O /dev/null");

}
foreach ($langues as $lang=>$none){
    echo "Downloading $lang\n";
    system("wget http://www.artica.fr/languages/download/$lang/$lang.tar -O /tmp/$lang.tar");
    echo "Installing $lang\n";
    if(!is_dir("/usr/share/artica-postfix/ressources/language/$lang")){
    mkdir("/usr/share/artica-postfix/ressources/language/$lang",0755,true);}
    system("tar -xf /tmp/$lang.tar -C /usr/share/artica-postfix/ressources/language/$lang/");
    @unlink("/tmp/$lang.tar");
}


system('php /usr/share/artica-postfix/compile-lang.php');

$srcgo="/home/dtouzeau/go/src/github.com/dtouzeau";
shell_exec("/bin/cp -f $srcgo/proxy-watchdog/proxy-watchdog /usr/share/artica-postfix/bin/proxy-watchdog");
shell_exec("/bin/cp -f $srcgo/proxy-pac/proxy-pac /usr/share/artica-postfix/bin/proxy-pac");


@file_put_contents("/home/dtouzeau/PhpstormProjects/Articav4/VERSION",$VERSION);

if(is_dir("/home/dtouzeau/artica-compile/$VERSION")) {
    echo "Removing /home/dtouzeau/artica-compile/$VERSION\n";
    shell_exec("rm -rf /home/dtouzeau/artica-compile/$VERSION");
}

$TARGET_PATH="/home/dtouzeau/artica-compile/$VERSION/usr/share/artica-postfix";

mkdir($TARGET_PATH, 0755, true);

$DIRSDEL[]="$TARGET_PATH/bin/go-shield/client/.idea";
$DIRSDEL[]="$TARGET_PATH/bin/go-shield/server/.idea";
$DIRSDEL[]="$TARGET_PATH/.idea";

foreach ($DIRSDEL as $t_dir){
    if(is_dir("$t_dir")){
        echo "Remove $t_dir\n";
        shell_exec("rm -rf $t_dir");
    }
}
$frm[]="client/external_acl_first/main.go";
$frm[]="client/external_acl_first/go.sum";
$frm[]="client/external_acl_first/go.mod";
$frm[]="client/external_acl_first/internal/handlers.go";
$frm[]="server/main.go";
$frm[]="server/go.sum";
$frm[]="server/go.mod";
$frm[]="server/internal/bigcache.go";
$frm[]="server/internal/handlers.go";
$frm[]="server/categorization/categorization.go";
$frm[]="bin/go-shield/ad/ad.go";
$frm[]="bin/go-shield/ad/go.sum";
$frm[]="bin/go-shield/ad/go.mod";
$frm[]="bin/go-shield/client/external_acl_first/main.go";
$frm[]="bin/go-shield/client/external_acl_first/go.sum";
$frm[]="bin/go-shield/client/external_acl_first/go.mod";
$frm[]="bin/go-shield/client/external_acl_first/internal/memcached.go";
$frm[]="bin/go-shield/client/external_acl_first/internal/handlers.go";
$frm[]="bin/go-shield/handlers/tokens.go";
$frm[]="bin/go-shield/handlers/license.go";
$frm[]="bin/go-shield/handlers/handlers.go";
$frm[]="bin/go-shield/handlers/go.sum";
$frm[]="bin/go-shield/handlers/loggin.go";
$frm[]="bin/go-shield/handlers/go.mod";
$frm[]="bin/go-shield/server/itchart/intchart.go";
$frm[]="bin/go-shield/server/main.go";
$frm[]="bin/go-shield/server/ufdbguard/ufdbguard.go";
$frm[]="bin/go-shield/server/shields/shields.go";
$frm[]="bin/go-shield/server/go.sum";
$frm[]="bin/go-shield/server/go.mod";
$frm[]="bin/go-shield/server/internal/bigcache.go";
$frm[]="bin/go-shield/server/internal/memcached.go";
$frm[]="bin/go-shield/server/internal/handlers.go";
$frm[]="bin/go-shield/server/categorization";
$frm[]="bin/go-shield/server/categorization/categorization.go";
$frm[]="ressources/br.db";
$frm[]="ressources/de.db";
$frm[]="ressources/en.db";
$frm[]="ressources/es.db";
$frm[]="ressources/fr.db";
$frm[]="ressources/it.db";

$frm[]="ldap.py";
$frm[]="proxy-pac.py";
$frm[]="downloader.tgz";
$frm[]="install.tar.gz";
$frm[]="pyphishtank.py";
$frm[]="bin/malwareurls.py";
$frm[]="bin/malwareurls";
$frm[]="bin/dns-firewall.py";
$frm[]="bin/k9.py";
$frm[]="ldap-tests.py";
$frm[]="bin/cloudflare_query.py";
$frm[]="bin/external_acl_krsn.py";
$frm[]="bin/external_acl_itchart.py";
$frm[]="bin/external_acl_first.py";
$frm[]="bin/install/squid/external_acl_first";
$frm[]="bin/srnquery.py";
$frm[]="googlesafebrowsing.py";
$frm[]="bin/goldlic.py";
$frm[]="bin/nginx-stats.py";
$frm[]="bin/sbserver-daemon";
$frm[]="ubound-srn.py";
$frm[]="srn-smtp.py";
$frm[]="bin/install/passtrough.py";
$frm[]="bin/install/passtrough-old.py";
$frm[]="bin/install/dnsfw-example.py";
$frm[]="bin/dnsfw-example2.py";
$frm[]="ressources/categorizeclass.py";
$frm[]="ressources/downloadclass.py";
$frm[]="ressources/publicsuffix.py";
$frm[]="bin/k9.py";
$frm[]="ressources/k9.py";
$frm[]="ressources/K9Query.py";
$frm[]="ressources/activedirectoryclass.py";
$frm[]="ressources/classgeoip2free.py";
$frm[]="ressources/classartmem.py";
$frm[]="ressources/dnsfirewallclass.py";
$frm[]="ressources/theshieldsclient.py";
$frm[]="ressources/ufdbclass.py";
$frm[]="ressources/theshieldsservice.py";
$frm[]="ressources/classhttp.py";
$frm[]="ressources/theshieldsclass.py";
$frm[]="bin/external_acl_first.oldv2.py";
$frm[]="bin/external_acl_first.oldv1.py";
$frm[]="bin/external_acl_first.py";
$frm[]="ressources/threadobject.py";
$frm[]="bin/external_acl_first.bak.py";
$frm[]="bin/external_acl_krsn.py";
$frm[]="exec.rpz-master.php";
$frm[]="exec.atomi.php";
$frm[]="compile-go.php";
$frm[]="test.py";
$frm[]="bin/StatsCommunicator.py";
$frm[]="bin/go-shield-server";
$frm[]="bin/StatsCommunicator";
$frm[]="exec.StatsCommunicator.php";
$frm[]="auth-tail.py";
$frm[]="ufdbgweb.py";
$frm[]="ufdbgclient.php";
$frm[]="exec.statsredis.php";
$frm[]="js/jquery-1.1.3.1.pack.js";
$frm[]="js/jquery-1.6.1.min.js";
$frm[]="js/jquery-1.6.2.min.js";
$frm[]="js/jquery-1.7.2.min.js";
$frm[]="js/jquery-1.8.0.min.js";
$frm[]="js/jquery-1.8.3.js";
$frm[]="angular/js/jquery/jquery-2.1.1.min.js";
$frm[]="angular/js/jquery/jquery-3.1.1.min.js";
$frm[]="test.txt";
$frm[]="xtables-4.19.0-10-amd64.tar.gz";
$frm[]="xtables-4.19.0-13-amd64.tar.gz";
$frm[]="xtables-4.19.0-16-amd64.tar.gz";
$frm[]="js/tiny_mce/jquery.tinymce.min.js";
$frm[]="exec.init-tail-hotspot.php";
$frm[]="squid.hostspot.emergency.enable.progress.php";
$frm[]="squid.hostspot.restart.web.progress.php";
$frm[]="squid.hostspot.emergency.disable.progress.php";
$frm[]="squid.hostspot.reconfigure.php";
$frm[]="squid.webauth.activedirectory.php";
$frm[]="external_acl_microhotspot.php";
$frm[]="angular/js/plugins/colorpicker/jquery.colorpicker.css";
$frm[]="angular/js/plugins/colorpicker/jquery.colorpicker.js";
$frm[]="js/colorpicker.js";
$frm[]="css/colorpicker.css";
$frm[]="css/images/colorpicker_background.png";
$frm[]="css/images/colorpicker_hex.png";
$frm[]="css/images/colorpicker_hsb_b.png";
$frm[]="css/images/colorpicker_hsb_h.png";
$frm[]="css/images/colorpicker_hsb_s.png";
$frm[]="css/images/colorpicker_indic.gif";
$frm[]="css/images/colorpicker_overlay.png";
$frm[]="css/images/colorpicker_rgb_b.png";
$frm[]="css/images/colorpicker_rgb_g.png";
$frm[]="css/images/colorpicker_rgb_r.png";
$frm[]="css/images/colorpicker_select.gif";
$frm[]="css/images/colorpicker_submit.png";

// server mode
$frm[]="exec.ipset.master.compile.php";
$frm[]="exec.nrds.master.compile.php";
$frm[]="compile-go-shield-server.php";
$frm[]="compile-go.php";
$frm[]="exec.dshield.php";

echo "Copy files to [$TARGET_PATH]\n";
system("cp -rf /home/dtouzeau/PhpstormProjects/Articav4/* $TARGET_PATH/");
system("rm -rf $TARGET_PATH/bin/src");
system("rm -rf $TARGET_PATH/bin/certs");
system("rm -rf $TARGET_PATH/bin/install/cups/drivers");
system("rm -rf $TARGET_PATH/bin/install/awstats");
system("rm -rf $TARGET_PATH/bin/install/distributions");
system("rm -rf $TARGET_PATH/ressources/language/en");
system("rm -rf $TARGET_PATH/css/kavweb");
system("rm -rf $TARGET_PATH/css/artica-theme");
system("rm -rf $TARGET_PATH/css/my-cosi");
system("rm -rf $TARGET_PATH/css/android-theme");
system("rm -rf $TARGET_PATH/css/templates.users.ressources");
system("rm -rf $TARGET_PATH/angular/js/plugins/nggrid");
system("rm -rf $TARGET_PATH/angular/js/plugins/pdfjs");
system("rm -rf $TARGET_PATH/.git");

foreach ($frm as $filepath){
    $tpath="$TARGET_PATH/$filepath";
    if(!is_file($tpath)){continue;}
    echo "Removing $tpath\n";
    @unlink($tpath);
}

$dirs[]=".settings";
$dirs[]="bin/go-shield/.git";
$dirs[]="bin/go-shield/handlers";
$dirs[]="bin/go-shield/server/.idea";

foreach ($dirs as $directory){
    if(is_dir("$TARGET_PATH/$directory")){
        shell_exec("rm -rf $TARGET_PATH/$directory");
    }
}

foreach ($langues as $lang=>$none){
    echo "Removing artica-postfix/ressources/language/$lang\n";
    system("rm -rf /home/dtouzeau/artica-compile/$VERSION/usr/share/artica-postfix/ressources/language/$lang");
}


echo "Apply security to [/home/dtouzeau/artica-compile/$VERSION/usr/share/artica-postfix]\n";
system("chown -R www-data:www-data /home/dtouzeau/artica-compile/$VERSION/usr/share/artica-postfix");
system("chmod -R 0755 /home/dtouzeau/artica-compile/$VERSION/usr/share/artica-postfix");

echo "Compressing /home/dtouzeau/artica-compile/$VERSION/usr/share/artica-postfix\n";

if(is_file("/home/dtouzeau/artica-compile/artica-$VERSION.tgz")){@unlink("/home/dtouzeau/artica-compile/artica-$VERSION.tgz");}

    $WorkDir="/home/dtouzeau/artica-compile/$VERSION/usr/share";
    $cmd[]="cd $WorkDir && tar -czf /home/dtouzeau/artica-compile/artica-$VERSION.tgz artica-postfix";


    $excludes[]="artica-postfix/bin/src";
    $excludes[]="artica-postfix/bin/install/kas-linux-install";
    $excludes[]="artica-postfix/bin/install/kav4mailservers-linux-install";
    $excludes[]="artica-postfix/bin/install/roundcubemail";
    $excludes[]="artica-postfix/bin/install/kas-linux-mp1";
    $excludes[]="artica-postfix/LocalDatabases";
    $excludes[]="artica-postfix/bin/oldlibs";
    $excludes[]="artica-postfix/ressources/profiles";
    $excludes[]="artica-postfix/ressources/ldap-back";
    $excludes[]="artica-postfix/webmail";
    $excludes[]="artica-postfix/amavis";
    $excludes[]="artica-postfix/ldap";
    $excludes[]="artica-postfix/mysql";
    $excludes[]="artica-postfix/certs";
    $excludes[]="artica-postfix/sql";
    $excludes[]="artica-postfix/.git";
    $excludes[]="artica-postfix/roundcube";
    $excludes[]="artica-postfix/computers";
    $excludes[]="artica-postfix/oma";
    $excludes[]="artica-postfix/etc";
    $excludes[]="artica-postfix/computers";
    $excludes[]="artica-postfix/virtualbox";
    $excludes[]="artica-postfix/groupware";
    $excludes[]="artica-postfix/certs";
    $excludes[]="artica-postfix/bin/artica-compile";
    $excludes[]="artica-postfix/bin/setup-centos";
    $excludes[]="artica-postfix/bin/setup-mandrake";
    $excludes[]="artica-postfix/bin/setup-fedora";
    $excludes[]="artica-postfix/bin/setup-debian";
    $excludes[]="artica-postfix/bin/setup-suse";
    $excludes[]="artica-postfix/bin/artica-bogom";
    $excludes[]="artica-postfix/bin/install-sql";
    $excludes[]="artica-postfix/bin/mini-sendmail";
    $excludes[]="artica-postfix/bin/amavis-logwatch";
    $excludes[]="artica-postfix/bin/clear";
    $excludes[]="artica-postfix/bin/artica-mimedefang-pipe";
    $excludes[]="artica-postfix/ressources/sessions/SessionData";
    $excludes[]="artica-postfix/ressources/isoqlog";
    $excludes[]="artica-postfix/ressources/psps.inc";
    $excludes[]="artica-postfix/ressources/scan.printers.drivers.inc";
    $excludes[]="artica-postfix/ressources/processes.inc";
    $excludes[]="artica-postfix/ressources/logs/";
    $excludes[]="artica-postfix/ressources/language/en";
    $excludes[]="artica-postfix/ressources/language/fr";
    $excludes[]="artica-postfix/ressources/language/it";
    $excludes[]="artica-postfix/ressources/language/po";
    $excludes[]="artica-postfix/ressources/language/de";
    $excludes[]="artica-postfix/ressources/language/es";
    $excludes[]="artica-postfix/ressources/language/pol";
    $excludes[]="artica-postfix/artica-install";
    $excludes[]="artica-postfix/user-backup/.cache";
    $excludes[]="artica-postfix/user-backup/.settings";
    $excludes[]="artica-postfix/user-backup/php_logs";
    $excludes[]="artica-postfix/.settings";
    $excludes[]="artica-postfix/default.js.bak";
    $excludes[]="artica-postfix/ressources/ldap-back";
    $excludes[]="artica-postfix/ressources/sock";
    $excludes[]="artica-postfix/ressources/logs/web";
    $excludes[]="artica-postfix/ressources/kayaco";
    $excludes[]="artica-postfix/computers";
    $excludes[]="artica-postfix/user-backup";
    $excludes[]="artica-postfix/zabbix";
    $excludes[]="artica-postfix/exec.dshield.php";
    $excludes[]="artica-postfix/PDFs";
    $excludes[]="artica-postfix/tests.py";
    $excludes[]="artica-postfix/.eric4project";
    $excludes[]="artica-postfix/.settings";
    $excludes[]="artica-postfix/.mldonkey";
    $excludes[]="artica-postfix/exec.compile-official-ufdb.php";
    $excludes[]="artica-postfix/exec.malware-domains.php";
    $excludes[]="artica-postfix/exec.c-icap.cloud.php";
    $excludes[]="artica-postfix/exec.phistank.cloud.php";
    $excludes[]="artica-postfix/.git";

    foreach ($excludes as $path){

        $target="$WorkDir/$path";
        if(is_dir($target)){
            echo "Removing directory $path\n";
            shell_exec("rm -rf $target");
            continue;
        }
        if(is_file($target)){
            echo "Removing file $target\n";
            @unlink($target);
            continue;
        }

    }


$cmdline=@implode(" ",$cmd);
echo $cmdline."\n";
system($cmdline);
system("rm -rf /home/dtouzeau/artica-compile/$VERSION");
system("chown dtouzeau:dtouzeau /home/dtouzeau/artica-compile/*");
echo "/home/dtouzeau/artica-compile/artica-$VERSION.tgz done...\n";
