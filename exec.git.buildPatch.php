#!/usr/bin/php
<?php
$GLOBALS["VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
$GLOBALS["useraccount"]="dtouzeau";
$GLOBALS["FORCE_VERSION"]=0;
$GLOBALS["FORCE_COMMIT"]=null;
$GLOBALS["NO_UPLOAD"] = false;
$GLOBALS["NOGIT"]=false;
$GLOBALS["NO_MAIL"]=false;
$GLOBALS["basedir"]="/home/{$GLOBALS["useraccount"]}/Bureau/Patchs";
$GLOBALS["PATCHDIR"]="{$GLOBALS["basedir"]}/{$GLOBALS["VERSION"]}";
include_once(dirname(__FILE__)."/ressources/class.ssh.client.inc");
include_once(dirname(__FILE__)."/ressources/smtp/class.smtp.loader.inc");
if(!isset($argv[3])){$argv[3]=null;}
if(!isset($argv[1])){help();die();}
if(preg_match("#-(no-upload|noupload|no-uploads|nopush)#",@implode(" ",$argv),$re)){
    $GLOBALS["NO_UPLOAD"]=true;
}
if(preg_match("#--nomail#",@implode(" ",$argv),$re)){
    $GLOBALS["NO_MAIL"]=true;
}
if(preg_match("#--(nogit|no-git)#",@implode(" ",$argv),$re)){
    $GLOBALS["NOGIT"]=true;
}

if(preg_match("#--force-version=([0-9]+)#",@implode(" ",$argv),$re)){
    $GLOBALS["FORCE_VERSION"]=$re[1];
}
if(preg_match("#--commit=(.+)#",@implode(" ",$argv),$re)){
    $GLOBALS["FORCE_COMMIT"]=$re[1];
}
if(!isset($argv[3])){
    $argv[3]=null;
}

if($argv[1]=="--create-sp"){create_ServicePackFree($argv[2],$argv[3]);exit;}
if($argv[1]=="--smtp-recall"){sendmail_recall($argv[2],$argv[3]);exit;}
if($argv[1]=="--patch"){build_ServicePack();exit;}
if($argv[1]=="--sp"){build_ServicePack();exit;}
if($argv[1]=="--sp-indexes"){RebuildIndexes($argv[2]);exit;}
if($argv[1]=="--rebuild-sp"){RebuildSP();exit;}


if($argv[1]=="--service-pack"){build_ServicePack();exit;}
if($argv[1]=="--index"){SoftIndex();exit;}
if($argv[1]=="--hotfix"){hotfix($argv[2]);exit;}
if($argv[1]=="--recall"){recall($argv[2]);exit;}
if($argv[1]=="--whats"){echo GetDetailsWhatsnew($argv[2]);exit;}
if($argv[1]=="--help"){help();exit;}
if($argv[1]=="--hotfix-sp210"){hotfix_430_sp210();exit;}
if($argv[1]=="--hotfix-off45"){official_hotfix();exit;}
if($argv[1]=="--hotfix-dev45"){dev_hotfix();exit;}
if($argv[1]=="--changes"){make_changes();exit;}
if($argv[1]=="--langs"){compile_langs();exit;}
if($argv[1]=="--articarest"){compile_articarest($argv[2]);exit;}
if($argv[1]=="--after-network"){compile_after_network($argv[2]);exit;}
if($argv[1]=="--smtprelay"){compile_smtprelay($argv[2]);exit;}
if($argv[1]=="--metasrv"){compile_metasrv($argv[2],$argv[3]);exit;}
if($argv[1]=="--articawatch"){compile_articawatch($argv[2]);exit;}
if($argv[1]=="--artwatch"){compile_articawatch($argv[2]);exit;}
if($argv[1]=="--articainstall"){compile_articainstall($argv[2]);exit;}
if($argv[1]=="--netmonix"){compile_netmonix($argv[2]);exit;}
if($argv[1]=="--hacluster"){compile_hacluster($argv[2]);exit;}
if($argv[1]=="--nginx"){compile_nginx($argv[2]);exit;}
if($argv[1]=="--reverse"){compile_nginx($argv[2]);exit;}
if($argv[1]=="--reverse-proxy"){compile_nginx($argv[2]);exit;}
if($argv[1]=="--lighthouse"){compile_lightouse($argv[2]);exit;}
if($argv[1]=="--fire"){compile_firecracker($argv[2]);exit;}
if($argv[1]=="--firecrack"){compile_firecracker($argv[2]);exit;}
if($argv[1]=="--samba"){compile_FileSharing($argv[2]);exit;}



if($argv[1]=="--hotspot"){compile_hotspot($argv[2]);exit;}
if($argv[1]=="--weberror"){compile_weberror($argv[2]);exit;}
if($argv[1]=="--error-page"){compile_weberror($argv[2]);exit;}
if($argv[1]=="--web-error"){compile_weberror($argv[2]);exit;}
if($argv[1]=="--proxypac"){compile_proxypac($argv[2]);exit;}
if($argv[1]=="--squidwatch"){compile_squidwatch($argv[2]);exit;}
if($argv[1]=="--nfqueue"){compile_nfqueue($argv[2]);exit;}
if($argv[1]=="--proxywatch"){compile_squidwatch($argv[2]);exit;}
if($argv[1]=="--reputation"){compile_reputation_checked($argv[2]);exit;}
if($argv[1]=="--authhook"){compile_authhook($argv[2]);exit;}
if($argv[1]=="--dhcp"){compile_dhcpclient($argv[2]);exit;}
if($argv[1]=="--external_acl_urlsdb"){compile_external_acl_urlsdb($argv[2]);exit;}
if($argv[1]=="--vts-exporter"){compile_vts_exporter($argv[2]);exit;}
if($argv[1]=="--artica-update"){compile_artica_update($argv[2]);exit;}
if($argv[1]=="--artica-milter"){artica_milter();exit;}
if($argv[1]=="--milter"){artica_milter();exit;}
if($argv[1]=="--php-reverse"){compile_phpreverse();exit;}
if($argv[1]=="--php-categories"){compile_phpcategories();exit;}
if($argv[1]=="--php-system"){compile_phpsystems();exit;}
if($argv[1]=="--php-docker"){compile_phpdocker();exit;}
if($argv[1]=="--php-hotspot"){compile_phphotspot();exit;}
if($argv[1]=="--php-postgres"){compile_phppostgres();exit;}
if($argv[1]=="--php-parts-components"){duplicate_common_components();exit;}
if($argv[1]=="--testssh"){TestSSH();exit;}
help();

function help(){
    echo "Use --patch to Build a patch\n";
    echo "Use --index to scan {$GLOBALS["PATCHDIR"]}\n";
    echo "Use --create-sp 3 To create Service Pack 3 from {$GLOBALS["PATCHDIR"]}\n";
    echo "Use --no-upload| --noupload to not upload Service Pack to repository server\n";
    echo "Use --nomail to not Send email notification\n";
    echo "--force-version=[INT] to force patch version.\n";
    echo "--commit=[STRING] to add a label on git server.( must be the last parameter)\n";
    echo "--smtp-recall [SP] [COMMIT] to send a notification mail\n";
    echo "--recall [SP] stamps old files and inject the again\n";
    echo "--create-sp [SP] [directory] : Create a new Service Pack SP with sources based on the [Directory]\n";
    echo "--hotfix [directory]: Create an Hotfix from the defined directory\n";
    echo "--hotfix-off45: Build an official Hotfix for 4.50x\n";
    echo "--hotfix-dev45: Build a developpement Hotfix for 4.50x\n";
    echo "--changes: Create an official changes packages\n";
    echo "--langs: Download and compile languages\n";
    echo "--hacluster: Compile HaCluster client\n";
    echo "Build a Service pack\n";
    echo "php /usr/share/artica-postfix/exec.git.buildPatch.php --service-pack --noupload --force-version=2\n";
echo "\n\n";
}

function compile_langs(){

    $langues["fr"]=true;
    $langues["it"]=true;
    $langues["de"]=true;
    $langues["po"]=true;
    $langues["es"]=true;
    $langues["br"]=true;
    $langues["pol"]=true;
    $time=time();
    foreach ($langues as $lang=>$none){
        echo "Compile $lang: export.lang.php?lang=$lang&t=$time -O\n";
        system("wget \"http://www.artica.fr/export.lang.php?lang=$lang&t=$time\" -O /dev/null");

    }
    foreach ($langues as $lang=>$none){
        echo "Downloading $lang\n";
        system("wget \"http://www.artica.fr/languages/download/$lang/$lang.tar?t=$time\" -O /tmp/$lang.tar");
        echo "Installing $lang\n";
        if(!is_dir("/usr/share/artica-postfix/ressources/language/$lang")){
            mkdir("/usr/share/artica-postfix/ressources/language/$lang",0755,true);}
        system("tar -xf /tmp/$lang.tar -C /usr/share/artica-postfix/ressources/language/$lang/");
        @unlink("/tmp/$lang.tar");
    }
    system('php /usr/share/artica-postfix/compile-lang.php');
}
function make_changes(){

    $Time=time();
    $MAIN_PATH="/usr/share/artica-postfix";
    $TARGET_DIR="/home/artica-changes/$Time/artica-postfix";

    if(!is_dir($TARGET_DIR)){@mkdir($TARGET_DIR,0755,true);}

    shell_exec("php /usr/share/artica-postfix/compile-lang.php");
    $git = "/usr/bin/git -C $MAIN_PATH";

    system("export LANG=en_GB");

    exec("$git status 2>&1", $results);



    foreach ($results as $line) {
        $line = trim($line);

        if(preg_match("#(Project_Default|workspace|deployment)\.xml#",$line)){continue;}
        if(preg_match("#language\/.*?\.txt$#",$line)){continue;}
        if ($line == null) {
            continue;
        }
        if (preg_match("#^(new file|nouveau fichier|modifi).*?:\s+(.+)#", $line, $re)) {
            $re[2]=str_replace("\r\n","",$re[2]);
            $re[2]=str_replace("\n","",$re[2]);
            $re[2]=str_replace("\r","",$re[2]);
            $final=trim($re[2]);
            echo "F-->'$final'\n";
            $Files[] = trim($re[2]);
        }

    }
    foreach ($Files as $srcpath){
        $target="$TARGET_DIR/$srcpath";
        $dirname=dirname($target);
        if(!is_dir($dirname)){
            @mkdir($dirname,0755,true);
        }
        if(!@copy("$MAIN_PATH/$srcpath",$target)){
            echo "Failed to copy $MAIN_PATH/$srcpath - $target\n";
            return false;
        }
    }
    shell_exec("choxn -R {$GLOBALS["useraccount"]} $TARGET_DIR");
    echo "packages inside $TARGET_DIR\n";
}


function RebuildIndexes($TARGET_DIR=null){

    if($TARGET_DIR==null){
        $TARGET_DIR=$GLOBALS["PATCHDIR"];
    }

    if(!is_dir("$TARGET_DIR/artica-postfix/SP")){
        echo "$TARGET_DIR/artica-postfix/SP no such directory";
        die();
    }
    $GLOBALS["PATCHDIR"]=$TARGET_DIR;
    @file_put_contents("$TARGET_DIR/artica-postfix/SP/index.pf",SoftIndex());
    echo "$TARGET_DIR/artica-postfix/SP/index.pf Done\n";

}

function hotfix_430_sp210():bool{
    $gitbin="/usr/bin/git";
    $hotfixTime=date("Ymd-H");
    $ROOT_DIR="/home/dtouzeau/Bureau/Hotfix";
    $WORKDIR="$ROOT_DIR/4.30.000000";
    $MAIN_PATH="/home/dtouzeau/Bureau/v4SP210/artica-postfix";
    $PACKAGE_DIR="$ROOT_DIR/Compiled/4.30SP210";
    $TARGET_DIR="$WORKDIR/artica-postfix";
    $TARGET_PATCH_FILE="$PACKAGE_DIR/artica-4.30.000000.tgz";

    $dirs[]=$PACKAGE_DIR;
    $dirs[]=$WORKDIR;
    $dirs[]=$TARGET_DIR;
    foreach ($dirs as $directory){
        if(!is_dir($directory)){@mkdir($directory,0755,true);}
    }
    if(is_file("$TARGET_DIR/fw.updates.php")){
        @unlink("$TARGET_DIR/fw.updates.php");
        @copy("$MAIN_PATH/fw.updates.php","$TARGET_DIR/fw.updates.php");
    }
    if(!is_file("$TARGET_DIR/fw.updates.php")){
        echo "$TARGET_DIR/fw.updates.php no such file!\n";
        return false;
    }


    if(!PatchfwUpdatesPHP($TARGET_DIR,$hotfixTime)){
        echo "Not Patched in $TARGET_DIR/fw.updates.php!!!\n";
        return false;
    }

    $TOPATCH=GetFilesToPatch($MAIN_PATH);
    if(count($TOPATCH)==0){
        echo "Nothing to patch!\n";
        return true;
    }

    foreach ($TOPATCH as $srcfile){
        if($srcfile=="artica-postfix/"){continue;}
        $srcfile=trim(str_replace("artica-postfix/","",$srcfile));
        if($srcfile==null){continue;}
        echo "$srcfile ?\n";
        if($srcfile=="fw.updates.php"){continue;}
        if(preg_match("#language\.txt$#",$srcfile)){continue;}
        $srcPath="$MAIN_PATH/$srcfile";
        $DestPath="$TARGET_DIR/$srcfile";
        $dirname=dirname($DestPath);
        if(!is_dir($dirname)){
            @mkdir($dirname,0755,true);
        }
        if(is_file($DestPath)){@unlink($DestPath);}
        if(!is_file($srcPath)){
            echo "$srcPath, no such file\n";
            return false;

        }
        @copy($srcPath,$DestPath);
        if(!is_file($DestPath)){
            echo "$DestPath -- FAILED!\n";
            return true;
        }
        echo "Patching $srcPath\n";

    }

    $TODELETE=FilesToDeleteBefore($TARGET_DIR);
    foreach ($TODELETE as $destfile){
        if(is_file($destfile)){
            echo "Removing $destfile\n";
            @unlink($destfile);}
    }
    chdir($WORKDIR);
    system("cd $WORKDIR");

    if(is_file($TARGET_PATCH_FILE)){
        @unlink($TARGET_PATCH_FILE);
    }
    system("tar -czvf $TARGET_PATCH_FILE *");
    echo "$TARGET_PATCH_FILE ($hotfixTime) Done\n";

    $params=ssh_parse_config();
    $remotebase="/home/www.artica.fr/download/wiki/4.30/hotfix-sp212";
    $ssh_client=new ssh_client($params["hostname"],$params["port"],$params["user"],$params["password"]);

    if(!$ssh_client->connect()){
        echo "SSH Connection failed\n";
        die();
    }

    $remotepatch="$remotebase/".basename($TARGET_PATCH_FILE);

    echo "Copy patch to $remotepatch\n";
    echo "URL: ". str_replace("/home/www.artica.fr/","http://articatech.net/",$remotepatch)."\n";
    if(!$ssh_client->copyfile($TARGET_PATCH_FILE,$remotepatch,0755)){
        echo "Copy patch to $remotepatch Failed\n";
        $ssh_client->disconnect();
        return false;
    }


    return true;

}
function FilesToDeleteBefore($TARGET_DIR):array{
    $TODELETE[]="$TARGET_DIR/TestsClasses.php";
    $TODELETE[]="$TARGET_DIR/VERSION";
    $TODELETE[]="$TARGET_DIR/WHATSNEW";
    $TODELETE[]="$TARGET_DIR/exec.git.buildPatch.php";
    $TODELETE[]="$TARGET_DIR/exec.fix.urllib3.php";
    $TODELETE[]="$TARGET_DIR/bin/export.lang.php?lang=br";
    $TODELETE[]="$TARGET_DIR/ressources/language/en/c.langage.txt";
    $TODELETE[]="$TARGET_DIR/ressources/language/en/language.txt";
    $TODELETE[]="$TARGET_DIR/ressources/class.status.statscom.inc";
    $TODELETE[]="$TARGET_DIR/ressources/class.status.rsyslogd.inc";
    $TODELETE[]="$TARGET_DIR/bin/install/docker-client";
    $TODELETE[]="$TARGET_DIR/bin/reverse-tests";
    $TODELETE[]="$TARGET_DIR/SP/4.50.000000";
    $TODELETE[]="$TARGET_DIR/.fw.proxy.status.php.swp";
    $TODELETE[]="$TARGET_DIR/bin/goclone";
    $TODELETE[]="$TARGET_DIR/bin/authlogs";
    $TODELETE[]="$TARGET_DIR/bin/getdisks";
    $TODELETE[]="$TARGET_DIR/bin/ArticaStats";
    $TODELETE[]="$TARGET_DIR/bin/articarest.tar.gz";
    $TODELETE[]="$TARGET_DIR/bin/squid-users";
    $TODELETE[]="$TARGET_DIR/bin/export.lang.php?lang=es";
    $TODELETE[]="$TARGET_DIR/bin/export.lang.php?lang=fr";
    $TODELETE[]="$TARGET_DIR/bin/export.lang.php?lang=it";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/deployment.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/external_acl_first.iml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/vcs.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/webServers.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/modules.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/workspace.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/.gitignore";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/.idea/sshConfigs.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/handlers/go.sum";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/handlers/go.mod";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/handlers/tokens.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/handlers/loggin.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/handlers/handlers.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/handlers/license.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/go.sum";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/go.mod";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/internal/handlers.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/internal/memcached.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/internal/bigcache.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/main.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/categorization/categorization.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/deployment.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/server.iml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/vcs.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/webServers.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/modules.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/workspace.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/.gitignore";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/.idea/sshConfigs.xml";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/ad/go.sum";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/ad/go.mod";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/ad/ad.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/ufdbguard/ufdbguard.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/itchart/intchart.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/server/shields/shields.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/go.sum";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/go.mod";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/internal/handlers.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/internal/memcached.go";
    $TODELETE[]="$TARGET_DIR/bin/go-shield/client/external_acl_first/main.go";
    $TODELETE[]="$TARGET_DIR/js/tiny_mce/langs/README.md";
    $TODELETE[]="$TARGET_DIR/bin/go-failover-checker";
    $TODELETE[]="$TARGET_DIR/tmp.js";
    $TODELETE[]="$TARGET_DIR/exec.status-init.php";

    $langiles[]="index.gateway.php.txt";
    $langiles[]="RTMMailConfig.php.txt";
    $langiles[]="domains.edit.domains.php.txt";
    $langiles[]="kas.engine.settings.php.txt";
    $langiles[]="artica.wizard.php.txt";
    $langiles[]="postfix.index.php.txt";
    $langiles[]="actions.apply.configs.php.txt";
    $langiles[]="postfix.plugins.php.txt";
    $langiles[]="html.blocker.ou.php.txt";
    $langiles[]="logon.php.js";
    $langiles[]="global-filtering.php.js";
    $langiles[]="system.index.php.txt";
    $langiles[]="domains.sendmail.php.txt";
    $langiles[]="dnsmasq.index.php.txt";
    $langiles[]="user.php.txt";
    $langiles[]="browse.usb.php.txt";
    $langiles[]="mailboxes.php.js";
    $langiles[]="kas.group.rules.php.txt";
    $langiles[]="automount.php.txt";
    $langiles[]="edit.thumbnail.php.txt";
    $langiles[]="logon.php.txt";
    $langiles[]="system.nic.static.dns.php.txt";
    $langiles[]="artica.log.php.txt";
    $langiles[]="mysql.index.php.txt";
    $langiles[]="sshd.php.txt";
    $langiles[]="users.index.php.txt";
    $langiles[]="rsync.client.php.txt";
    $langiles[]="index.retranslator.php.txt";
    $langiles[]="milter.index.php.txt";
    $langiles[]="artica.wizard.fetchmail.php.txt";
    $langiles[]="mailgraph.php.txt";
    $langiles[]="virtualbox.php.txt";
    $langiles[]="domains.manage.org.index.php.txt";
    $langiles[]="system.nic.dynamicdns.php.txt";
    $langiles[]="iptables.index.php.txt";
    $langiles[]="users.edit.php.txt";
    $langiles[]="aveserver.php.js";
    $langiles[]="statistics.index.php.txt";
    $langiles[]="postfix.master.cf.php.txt";
    $langiles[]="system.applications.php.txt";
    $langiles[]="pdns.php.txt";
    $langiles[]="postfix.sasl.php.txt";
    $langiles[]="instantsearch.php.txt";
    $langiles[]="mailspy.index.php.txt";
    $langiles[]="users.kas.php.js";
    $langiles[]="crossroads.index.php.txt";
    $langiles[]="statistics.awstats.php.txt";
    $langiles[]="system.nic.staticdns.php.txt";
    $langiles[]="wizard.smtp.php.txt";
    $langiles[]="users.backup.php.txt";
    $langiles[]="domains.manage.users.index.php.txt";
    $langiles[]="system.hardware.php.txt";
    $langiles[]="user.quarantine.query.php.txt";
    $langiles[]="pommo.index.php.txt";
    $langiles[]="domains.php.txt";
    $langiles[]="mailfromd.index.php.txt";
    $langiles[]="setup.index.php.txt";
    $langiles[]="wizard.fetchmail.newbee.php.txt";
    $langiles[]="langage.kpf";
    $langiles[]="artica.repositories.php.txt";
    $langiles[]="dotclear.index.php.txt";
    $langiles[]="backuphtml.ou.php.txt";
    $langiles[]="user.fetchmail.index.php.txt";
    $langiles[]="san.cluster.php.txt";
    $langiles[]="index.bind9.php.txt";
    $langiles[]="rsync.server.php.txt";
    $langiles[]="pureftp.index.php.txt";
    $langiles[]="language2.php.txt";
    $langiles[]="fetchmail.index.php.txt";
    $langiles[]="computer-browse.php.txt";
    $langiles[]="support.php.txt";
    $langiles[]="admin.tabs.php.txt";
    $langiles[]="system.nic.infos.php.txt";
    $langiles[]="samba.usb.php.txt";
    $langiles[]="bogofilter.ou.php.txt";
    $langiles[]="configure.server.php.txt";
    $langiles[]="artica.backup.index.php.txt";
    $langiles[]="user.messaging.php.txt";
    $langiles[]="obm.index.php.txt";
    $langiles[]="computer.scan.php.txt";
    $langiles[]="global-countries-rbl.ou.php.txt";
    $langiles[]="quarantine.ou.php.txt";
    $langiles[]="domains.www.php.txt";
    $langiles[]="policyd-weight.php.txt";
    $langiles[]="domains.edit.group.php.txt";
    $langiles[]="postfix.backup.monitoring.php.txt";
    $langiles[]="users.aswb.php.txt";
    $langiles[]="users.openvpn.index.php.txt";
    $langiles[]="postfix.restrictions.classes.php.txt";
    $langiles[]="system.nic.config.php.txt";
    $langiles[]="fetchmail.daemon.settings.php.txt";
    $langiles[]="system.internal.disks.php.txt";
    $langiles[]="artica.performances.php.txt";
    $langiles[]="my.addressbook.php.txt";
    $langiles[]="spamassassin.index.php.txt";
    $langiles[]="dnsmasq.dns.settings.php.txt";
    $langiles[]="users.quarantine.php.txt";
    $langiles[]="cyrus.backup.php.txt";
    $langiles[]="cyrus.clusters.php.txt";
    $langiles[]="domains.edit.user.backup.php.txt";
    $langiles[]="auto-account.php.txt";
    $langiles[]="quarantine.php.txt";
    $langiles[]="fileshares.index.php.txt";
    $langiles[]="menus.builder.php.txt";
    $langiles[]="index.php.js";
    $langiles[]="kav4fs.index.php.txt";
    $langiles[]="squid.popups.php.txt";
    $langiles[]="mysql.cluster.php.txt";
    $langiles[]="global-countries-filters.ou.php.txt";
    $langiles[]="contact.php.txt";
    $langiles[]="d.langage.txt";
    $langiles[]="cyrus.index.php.txt";
    $langiles[]="domains.php.js";
    $langiles[]="users.addressbook.index.php.txt";
    $langiles[]="pptpd.php.txt";
    $langiles[]="dstat.cpu.php.txt";
    $langiles[]="TreeBrowse.php.txt";
    $langiles[]="cyrus.murder.php.txt";
    $langiles[]="firstwizard.php.txt";
    $langiles[]="fdm.index.php.txt";
    $langiles[]="domains.white.list.robots.php.txt";
    $langiles[]="milter.greylist.index.php.txt";
    $langiles[]="openvpn.artica.php.txt";
    $langiles[]="domains.joomla.php.txt";
    $langiles[]="mailbox.settings.php.txt";
    $langiles[]="mail.log.php.txt";
    $langiles[]="artica-meta.php.txt";
    $langiles[]="wizard.retranslator.php.txt";
    $langiles[]="users.hotmail.index.php.txt";
    $langiles[]="mailman.index.php.txt";
    $langiles[]="cups.index.php.txt";
    $langiles[]="collectd.index.php.txt";
    $langiles[]="users.out-of-office.php.txt";
    $langiles[]="wizard.samba.domain.php.txt";
    $langiles[]="postfix.main.cf.edit.php.txt";
    $langiles[]="global-filtering.php.txt";
    $langiles[]="system_statistics.php.txt";
    $langiles[]="mailbox.settings.php.js";
    $langiles[]="mailman.lists.php.txt";
    $langiles[]="artica.update.php.txt";
    $langiles[]="postfix.tls.php.txt";
    $langiles[]="postfix.other.php.txt";
    $langiles[]="gluster.php.txt";
    $langiles[]="domains.import.members.php.txt";
    $langiles[]="domains.ad.import.php.txt";
    $langiles[]="dar.restorembx.php.txt";
    $langiles[]="index.pflogsumm.txt";
    $langiles[]="index.graphdefang.php.txt";
    $langiles[]="global-settings.php.txt";
    $langiles[]="aveserver.php.txt";
    $langiles[]="kas.user.rules.php.txt";
    $langiles[]="storage.center.php.txt";
    $langiles[]="domains.edit.user.php.txt";
    $langiles[]="domains.quarantine.php.txt";
    $langiles[]="services.status.php.txt";
    $langiles[]="index.troubleshoot.php.txt";
    $langiles[]="nmap.index.php.txt";
    $langiles[]="dkim.index.php.txt";
    $langiles[]="admin.index.php.txt";
    $langiles[]="index.openvpn.php.txt";
    $langiles[]="sqlgrey.index.php.txt";
    $langiles[]="kaspersky.index.php.txt";
    $langiles[]="squid.simple.php.txt";
    $langiles[]="global-blacklist.ou.php.txt";
    $langiles[]="dansguardian.users.index.php.txt";
    $langiles[]="mailman.settings.php.txt";
    $langiles[]="index.remoteinstall.php.txt";
    $langiles[]="users.account.php.js";
    $langiles[]="index.bind-stats.php.txt";
    $langiles[]="usb.browse.php.txt";
    $langiles[]="artica.settings.php.txt";
    $langiles[]="domains.sugarcrm.php.txt";
    $langiles[]="postfix.network.php.txt";
    $langiles[]="computer.backup.php.txt";
    $langiles[]="global-filters.ou.php.txt";
    $langiles[]="users.fetchmail.index.php.txt";
    $langiles[]=".txt";
    $langiles[]="mailboxes.php.txt";
    $langiles[]="wizard.kaspersky.appliance.php.txt";
    $langiles[]="backuppcc.php.txt";
    $langiles[]="domains.edit.php.txt";
    $langiles[]="sender.settings.php.txt";
    $langiles[]="admin.index.services.status.php.txt";
    $langiles[]="user.content.rules.php.txt";
    $langiles[]="assp.php.txt";
    $langiles[]="cyrus.rebuild.php.txt";
    $langiles[]="samba.index.php.txt";
    $langiles[]="dar.index.php.txt";
    $langiles[]="mimedefang.index.php.txt";
    $langiles[]="imap.index.php.txt";
    $langiles[]="system.harddisk.php.txt";
    $langiles[]="ocs.ng.php.txt";
    $langiles[]="c-icap.index.php.txt";
    $langiles[]="a.language.php.txt";
    $langiles[]="jchkmail.popup.php.txt";
    $langiles[]="smtp.rules.php.txt";
    $langiles[]="whitelists.admin.php.txt";
    $langiles[]="mysql.settings.php.txt";
    $langiles[]="postfix.relayssl.php.txt";
    $langiles[]="global-countries-surbl.ou.php.txt";
    $langiles[]="aveserver.settings.php.txt";
    $langiles[]="postfix.messages.restriction.php.txt";
    $langiles[]="domains.edit.php.js";
    $langiles[]="users.sieve.php.txt";
    $langiles[]="usb.index.php.txt";
    $langiles[]="global-ext-filters.ou.php.txt";
    $langiles[]="auto-compress.php.txt";
    $langiles[]="postfix.security.rules.php.txt";
    $langiles[]="dansguardian.index.php.txt";
    $langiles[]="postfix.audit.domains.php.txt";
    $langiles[]="squid.index.php.txt";
    $langiles[]="roundcube.index.php.txt";
    $langiles[]="master-cf.php.txt";
    $langiles[]="wizard.cyrus.cluster.php.txt";
    $langiles[]="users.kas.php.txt";
    $langiles[]="exec.quarantine.reports.php.txt";
    $langiles[]="postfix.queue.monitoring.php.txt";
    $langiles[]="ntpd.index.php.txt";
    $langiles[]="clamav.index.php.txt";
    $langiles[]="index.export.php.txt";
    $langiles[]="amavis.index.php.txt";
    $langiles[]="index.pflogsumm.php.txt";
    $langiles[]="c.langage.txt";
    $langiles[]="index.php.txt";
    $langiles[]="user.messages.statistics.php.txt";
    $langs=array("en","de","fr","po","it","es","br","pol");
    foreach ($langs as $langa){
        foreach ($langiles as $filename){
            $TODELETE[]="$TARGET_DIR/ressources/language/$langa/$filename";
        }
    }
    

    return $TODELETE;
}
function GetFilesToPatch($MAIN_PATH):array{
    $gitbin="/usr/bin/git";
    $TOPATCH=array();
    exec("$gitbin -C $MAIN_PATH add .");

    exec("$gitbin -C $MAIN_PATH status --porcelain 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#^([A-Z])+\s+(.+)#",$line,$re)){
            if($re[1]=="D"){continue;}
            $TOPATCH[]=trim($re[2]);
        }
    }
    $TOPATCH[]="bin/articarest";
    $TOPATCH[]="bin/go-shield/server/bin/go-shield-server";
    $TOPATCH[]="bin/go-shield/client/external_acl_first/bin/go-shield-connector";
    $TOPATCH[]="bin/go-shield/client/external_acls_ldap/bin/go-squid-auth";
    $TOPATCH[]="bin/go-shield/client/external_acls_gc/bin/external_acls_ad_agent";

    return $TOPATCH;
}
function PatchfwUpdatesPHP($TARGET_DIR,$PatchVersion):bool{
    if(!is_file("$TARGET_DIR/fw.updates.php")){return false;}

    $f=explode("\n",@file_get_contents("$TARGET_DIR/fw.updates.php"));
    foreach ($f as $index=>$line){
        if(!preg_match("#GLOBALS\[.*?HOTFIX.*?\]#",$line)){
            echo "NOT FOUND[$index] $line\n";
            continue;
        }
        $f[$index]="\$GLOBALS[\"HOTFIX\"]=\"$PatchVersion\";";
        @file_put_contents("$TARGET_DIR/fw.updates.php",@implode("\n",$f));
        return true;

    }
    return false;
}

function compile_articarest_version($MainDir):bool{

    $main="$MainDir/version.go";
    $f=explode("\n",@file_get_contents($main));
    $n=array();
    $res=false;
    foreach ($f as $line){
        if(preg_match("#(var|const)\s+version.*?=.*?\"([0-9\.]+)\"#",$line,$re)){
            $type=$re[1];
            $version=$re[2];
            $Rev=0;
            echo "Current version $version\n";
            $tb=explode(".",$version);
            $Major=$tb[0];
            $Minor=$tb[1];
            if(isset($tb[2])) {
                $Rev = intval($tb[2]);
            }
            $Rev++;
            if($Rev==100){
                $Minor++;
                $Rev=0;
            }
            $version="$Major.$Minor.$Rev";
            echo "Next version $version\n";
            $n[]="$type version = \"$version\"";
            $res=true;
            continue;
        }
        $n[]=$line;
    }
    @file_put_contents($main,@implode("\n",$n));
    return $res;

}
function compile_generic_version($MainDir):string{

    $main="$MainDir/version.go";
    $f=explode("\n",@file_get_contents($main));
    $n=array();
    foreach ($f as $line){
        if(preg_match("#(var|const)\s+version.*?=.*?\"([0-9\.]+)\"#",$line,$re)){
            $type=$re[1];
            $version=$re[2];
            $Rev=0;
            echo "Current version $version\n";
            $tb=explode(".",$version);
            $Major=$tb[0];
            $Minor=$tb[1];
            if(isset($tb[2])) {
                $Rev = intval($tb[2]);
            }
            $Rev++;
            if($Rev==100){
                $Minor++;
                $Rev=0;
            }
            $version="$Major.$Minor.$Rev";
            echo "Next version $version\n";
            $n[]="$type version = \"$version\"";
            $res=true;
            continue;
        }
        $n[]=$line;
    }
    @file_put_contents($main,@implode("\n",$n));
    return $version;

}
function compile_hotspot_version($MainDir):string{

    $main="$MainDir/main.go";
    $f=explode("\n",@file_get_contents($main));
    $n=array();
    $res="";
    foreach ($f as $line){
        if(preg_match("#var\s+version.*?=.*?\"([0-9\.]+)\"#",$line,$re)){
            $version=$re[1];
            echo "Current version $version\n";
            $tb=explode(".",$version);
            $Major=$tb[0];
            $Minor=$tb[1];
            $Rev=intval($tb[2]);
            $Rev++;
            if($Rev==100){
                $Minor++;
                $Rev=0;
            }
            $version="$Major.$Minor.$Rev";
            echo "Next version $version\n";
            $n[]="var version = \"$version\"";
            $res=$version;
            continue;
        }
        $n[]=$line;
    }
    @file_put_contents($main,@implode("\n",$n));
    return $res;

}
function compile_hotspot($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/hotspot-web";
    $targetbin="/usr/share/artica-postfix/bin/hotspot-web";
    $ArticaPartDir="/home/dtouzeau/developpement/artica-postfix-parts/hotspot";

    $Version=compile_hotspot_version($MainDir);
    if(strlen($Version)==0){
        echo "Unable to increment version\n";
        return false;
    }
    if(!is_dir("$ArticaPartDir/bin")){
        @mkdir("$ArticaPartDir/bin");
    }

    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    system("cp -f $targetbin $ArticaPartDir/bin/");

    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $Version'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }

    return true;
}


function  compile_vts_exporter($targetIP=""):bool{
    $date=date("Y-m-dTH:i:s");
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/vtx-exporter";
    $targetbin="/usr/share/artica-postfix/bin/vts-exporter";
    $promot="github.com/prometheus/common";
    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w -X $promot/version.Version=1.4.1 -X $promot/version.BuildUser=support@articatech.com -X $promot/version.BuildDate=$date\" ";
    echo "$cmd\n";
    system($cmd);

    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
return true;
}


function compile_external_acl_urlsdb($targetIP=""):bool
{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/external_acl_urlsdb";
    $targetbin="/usr/share/artica-postfix/bin/external_acl_urlsdb";
    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);

    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }

    return true;
}
function compile_dhcpclient($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/dhcpclient";
    $targetbin="/usr/share/artica-postfix/bin/dhcpclient";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date'");
    system("git push origin master");

    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}
function compile_authhook($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/authhook";
    $targetbin="/usr/share/artica-postfix/bin/authhook";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date'");
    system("git push origin master");

    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}
function compile_nfqueue($targetIP=""):bool{
    $goBin = "/usr/local/go/bin/go";
    $MainDir = "/home/dtouzeau/go/src/github.com/dtouzeau/artica-nfqueue";
    $targetbin = "/usr/share/artica-postfix/bin/artica-nfqueue";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);

    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}
function compile_reputation_checked($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/reputation-injecter";
    $targetbin="/usr/share/artica-postfix/bin/reputation-injecter";

    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);


    $curver=compile_articarupdatever($targetbin);
    if (strlen($curver)<3){
        echo "Unable to get compiled version\n";
        return false;
    }
    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $curver'");
    system("git push origin master");

    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}
function compile_squidwatch($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/squidwatch";
    $targetbin="/usr/share/artica-postfix/bin/squidwatch";

    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }

    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);

    $curver=compile_articarupdatever($targetbin);
    if (strlen($curver)<3){
        echo "Unable to get compiled version\n";
        return false;
    }

    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $curver'");
    system("git push origin master");

    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}
function compile_proxypac($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/proxy-pac";
    $targetbin="/usr/share/artica-postfix/bin/proxy-pac";

    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);


    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }

    return true;
}
function compile_weberror($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/artica-error-page";
    $targetbin="/usr/share/artica-postfix/bin/artica-error-page";


    $Version=compile_articarest_version($MainDir);
    if(strlen($Version)==0){
        echo "Unable to increment version\n";
        return false;
    }


    $cmd="$goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);


    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $Version'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }

    return true;
}
function compile_nginx($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/reverse-proxy";
    $targetbin="/usr/share/artica-postfix/bin/reverse-proxy";
    $Version=compile_generic_version($MainDir);
    if(strlen($Version)==0){
        echo "Unable to increment version\n";
        return false;
    }


    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);


    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $Version'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        $sport="-P 22";
        if(strpos($targetIP,":")>0){
            $tb=explode(":",$targetIP);
            $targetIP=$tb[0];
            $sport="-P $tb[1]";
        }
        echo "Send scp $sport $targetbin root@$targetIP:$targetbin\n";
        system("scp $sport $targetbin root@$targetIP:$targetbin");
    }

    return true;
}
function compile_FileSharing($targetIP=""){
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/file-sharing";
    $targetbin="/usr/share/artica-postfix/bin/file-sharing";
    $Version=compile_generic_version($MainDir);
    if(strlen($Version)==0){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 6 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);

    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $Version'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}
function compile_firecracker($targetIP=""){
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/firecrack-daemon";
    $targetbin="/usr/share/artica-postfix/bin/firecrack-daemon";
    $Version=compile_generic_version($MainDir);
    if(strlen($Version)==0){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);


    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $Version'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;

}

function compile_lightouse($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/artica-lightouse";
    $targetbin="/home/dtouzeau/go/src/github.com/dtouzeau/artica-lightouse";
    $Version=compile_generic_version($MainDir);
    if(strlen($Version)==0){
        echo "Unable to increment version\n";
        return false;
    }


    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);


    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date - $Version'");
    system("git push origin master");
    if(strlen($targetIP)>3){
        echo "Send $targetbin to $targetIP\n";
        system("scp $targetbin/artica-lighthouse ichecker@$targetIP:/home/ichecker/");
    }
    return true;
}

function artica_milter(){

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/artica-milter";
    $targetbin="/usr/share/artica-postfix/bin/artica-milter";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }



    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date'");
    system("git push origin master");
    return true;

}

function compile_hacluster($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/HaClusterClient";
    $targetbin="/usr/share/artica-postfix/bin/HaClusterClient";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    $date=date("Y-m-d H:i:s");
    shell_exec("cd $MainDir");
    chdir($MainDir);
    echo "Push changes to git\n";
    system("git add .");
    system("git commit -m 'David - $date'");
    system("git push origin master");
    echo "/usr/share/artica-postfix/bin/HaClusterClient done\n";
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}

function compile_articainstall($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/install-450";
    $targetbin="/root/install-450-debian12";
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}

function compile_metasrv($targetIP="",$sshpassword):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/metasrv";
    $targetbin="/usr/share/artica-postfix/bin/metasrv";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }

    return true;
}
function compile_after_network($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/after-network";
    $targetbin="/usr/share/artica-postfix/bin/after-network";

    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }

    return true;
}
function compile_articawatch($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/artwatch";
    $targetbin="/usr/share/artica-postfix/bin/artwatch";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }
return true;
}
function compile_netmonix($targetIP=""):bool{
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/NetMonix";
    $targetbin="/usr/share/artica-postfix/bin/netmonix";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }
    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }
    return true;
}


function compile_smtprelay_version($MainDir):bool{

    $main="$MainDir/config.go";
    if(!is_file($main)){
        echo "Unable to locate $main\n";
        return false;
    }
    $f=explode("\n",@file_get_contents($main));
    $n=array();
    $res=false;
    foreach ($f as $line){
        if(preg_match("#appVersion.*?=.*?\"([0-9\.]+)\"#",$line,$re)){
            $version=$re[1];
            echo "Current version $version\n";
            $tb=explode(".",$version);
            $Major=$tb[0];
            $Minor=$tb[1];
            $Rev=intval($tb[2]);
            $Rev++;
            $version="$Major.$Minor.$Rev";
            echo "Next version $version\n";
            $n[]="appVersion = \"$version\"";
            $res=true;
            continue;
        }
        $n[]=$line;
    }
    @file_put_contents($main,@implode("\n",$n));
    return $res;

}
function compile_smtprelay($targetIP=""){
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/smtprelay";
    $targetbin="/usr/share/artica-postfix/bin/artica-smtpd";
    if(!compile_smtprelay_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }

    if(!$GLOBALS["NOGIT"]) {
        $date = date("Y-m-d H:i:s");
        shell_exec("cd $MainDir");
        chdir($MainDir);
        echo "Push changes to git\n";
        system("git add .");
        system("git commit -m 'David - $date'");
        system("git push origin master");
    }

    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }

}
function compile_artica_update($targetIP=""):bool{

    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/artica-update";
    $targetbin="/usr/share/artica-postfix/bin/artica-update";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }

    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\" ";
    echo "$cmd\n";
    system($cmd);

    $curver=compile_articarupdatever();
    if (strlen($curver)<3){
        echo "Unable to get compiled version\n";
        return false;
    }

    if(!$GLOBALS["NOGIT"]) {
        $date = date("Y-m-d H:i:s");
        shell_exec("cd $MainDir");
        chdir($MainDir);
        echo "Push changes to git\n";
        system("git add .");
        system("git commit -m 'David - $date - $curver'");
        system("git push origin master");
    }
    if(strlen($targetIP)>3){
        system("scp $targetbin root@$targetIP:$targetbin");
    }


    return true;



}
function compile_articarest($targetIP=""):bool{
    $partPath="/home/dtouzeau/developpement/artica-postfix-parts/reverse-proxy";
    $goBin="/usr/local/go/bin/go";
    $MainDir="/home/dtouzeau/go/src/github.com/dtouzeau/articarest";
    $targetbin="/usr/share/artica-postfix/bin/articarest";
    if(!compile_articarest_version($MainDir)){
        echo "Unable to increment version\n";
        return false;
    }





    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -a -installsuffix cgo -p 4 -o $targetbin  -ldflags=\"-s -w -linkmode external -extldflags='-static'\"";

    $cmd="CGO_ENABLED=1 GOOS=linux GOARCH=amd64 $goBin build -C $MainDir -p 4 -o $targetbin  -ldflags=\"-s -w\"";

    echo "$cmd\n";
    system($cmd);
    if(!is_dir("$partPath/bin")){
        @mkdir("$partPath/bin",0755,true);
    }
    system("cp $targetbin $partPath/bin/");

    $curver=compile_articarestgetver();
    if (strlen($curver)<3){
        echo "Unable to get compiled version\n";
        return false;
    }

    if(!$GLOBALS["NOGIT"]) {
        $date = date("Y-m-d H:i:s");
        shell_exec("cd $MainDir");
        chdir($MainDir);
        echo "Push changes to git\n";
        system("git add .");
        system("git commit -m 'David - $date - $curver'");
        system("git push origin master");
    }
    if(strlen($targetIP)>3){
        $GLOBALS["NO_UPLOAD"]=true;
    }

    $COMPILEDIR="/home/dtouzeau/artica-rest-compiled";
    $WORKDIR="$COMPILEDIR/usr/share/artica-postfix/bin";
    if(!is_dir($WORKDIR)){
        mkdir($WORKDIR,0755,true);

    }
    $tfile="$WORKDIR/articarest";

    if(is_file($tfile)){@unlink($tfile);}
    copy($targetbin,$tfile);
    @chmod($tfile,0755);

    if( $GLOBALS["NO_UPLOAD"]){
        echo "Uploading denied\n";
        if(strlen($targetIP)>3){
            $tpor="";
            if(strpos($targetIP,":")>0){
                $tb=explode(":",$targetIP);
                $targetIP=$tb[0];
                $tpor=" -P $tb[1]";
            }

            system("scp$tpor $targetbin root@$targetIP:$targetbin");
        }
        return false;
    }

    echo "Compressing \"/home/dtouzeau/$curver.tar.gz\"\n";
    system("cd $COMPILEDIR");
    chdir($COMPILEDIR);
    system("tar -czvf /home/dtouzeau/$curver.tar.gz *");

    system("cd $WORKDIR");
    chdir($WORKDIR);
    if(is_file("/home/dtouzeau/articarest-current.tar.gz")){
        @unlink("/home/dtouzeau/articarest-current.tar.gz");
    }
    system("tar -czvf /home/dtouzeau/articarest-current.tar.gz articarest");

    $remotebase="/home/www.artica.fr/download";
    if(!sshCopy("/home/dtouzeau/$curver.tar.gz","$remotebase/Debian10-articarest/$curver.tar.gz")){
        echo "Failed to Copy to remote ssh resource.\n";
        return false;
    }
    if(!sshCopy("/home/dtouzeau/articarest-current.tar.gz","$remotebase/articarest-current.tar.gz")){
        echo "Failed to Copy articarest-current.tar.gz to remote ssh resource.\n";
        return false;
    }

    return true;
}
function CopySSH($path,$ipaddr){

}

function sshCopy($sourcefile,$destfile):bool{

    $params = ssh_parse_config();
    $ssh_client = new ssh_client($params["hostname"], $params["port"], $params["user"], $params["password"]);

    if (!$ssh_client->connect()) {
        echo "SSH Connection failed\n";
        return false;
    }


    echo "Copy patch to {$params["hostname"]}:$destfile\n";
    if (!$ssh_client->copyfile($sourcefile, $destfile, 0755)) {
        echo "Copy patch to {$params["hostname"]}:$destfile Failed\n";
        $ssh_client->disconnect();
        return false;
    }
    echo "Copy patch to {$params["hostname"]}:$destfile Success\n";
    $ssh_client->disconnect();
    return true;
}
function compile_articarupdatever():string{
    exec("/usr/share/artica-postfix/bin/artica-update-manu -version 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#([0-9\.]+)#",$line,$re)){
            return $re[1];
        }
    }
    return "";
}
function compile_articarestgetver($binpath=null):string{
    if(is_null($binpath)){
        $binpath="/usr/share/artica-postfix/bin/articarest";
    }
    exec("$binpath -version 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#([0-9\.]+)#",$line,$re)){
            return $re[1];
        }
    }
    return "";
}

function duplicate_common_components():bool{
    $ROOTS[]="/home/dtouzeau/developpement/artica-postfix-parts/reverse-proxy";
    $ROOTS[]="/home/dtouzeau/developpement/artica-postfix-parts/systems";
    $ROOTS[]="/home/dtouzeau/developpement/artica-postfix-parts/docker";
    $ROOTS[]="/home/dtouzeau/developpement/artica-postfix-parts/hotspot";
    $ROOTS[]="/home/dtouzeau/developpement/artica-postfix-parts/category-server";
    $ROOTS[]="/home/dtouzeau/developpement/artica-postfix-parts/postgresql";
    foreach ($ROOTS as $MainPath){
        _duplicate_common_components($MainPath);
    }
    return true;
}
function _duplicate_common_components($DestDir){
    $SrcDir="/usr/share/artica-postfix";
    $Files[]="ressources/class.template-admin.inc";
    $Files[]="ressources/class.templates.inc";
    $Files[]="ressources/class.icons.inc";
    $Files[]="ressources/class.sockets.inc";
    $Files[]="ressources/class.sqlite.inc";
    $Files[]="ressources/class.users.menus.inc";
    $Files[]="ressources/class.postgres.inc";
    $Files[]="ressources/class.left-menus.inc";
    $Files[]="ressources/language/en/c.langage.txt";

    if(!is_dir("$DestDir/ressources")){
        @mkdir("$DestDir/ressources",0755,true);
    }
    if(!is_dir("$DestDir/language/en")){
        @mkdir("$DestDir/language/en",0755,true);
    }

    foreach ($Files as $fpath){
        $bb=basename($DestDir);
        echo "Copy $fpath To $bb\n";
        @copy("$SrcDir/$fpath","$DestDir/$fpath");
    }
}
function compile_phpsystems():bool{
    $ROOT_DIR="/home/dtouzeau/developpement/artica-postfix-parts/systems";
    return compile_php_to_mainstore($ROOT_DIR);
}
function compile_phpdocker():bool{
    $ROOT_DIR="/home/dtouzeau/developpement/artica-postfix-parts/docker";
    return compile_php_to_mainstore($ROOT_DIR);
}
function compile_phpcategories():bool{
    $ROOT_DIR="/home/dtouzeau/developpement/artica-postfix-parts/category-server";
    return compile_php_to_mainstore($ROOT_DIR);
}
function compile_phpreverse():bool{
    $ROOT_DIR="/home/dtouzeau/developpement/artica-postfix-parts/reverse-proxy";
    return compile_php_to_mainstore($ROOT_DIR);
}
function compile_phphotspot():bool{
    $ROOT_DIR="/home/dtouzeau/developpement/artica-postfix-parts/hotspot";
    return compile_php_to_mainstore($ROOT_DIR);
}
function compile_phppostgres():bool{
    $ROOT_DIR="/home/dtouzeau/developpement/artica-postfix-parts/postgresql";
    return compile_php_to_mainstore($ROOT_DIR);
}
function compile_php_to_mainstore($ROOT_DIR):bool{
    $hotfixTime=date("Ymd-H");
    $TARGET_PATH="/usr/share/artica-postfix";
    $gitbin="/usr/bin/git";
    echo "Listing $ROOT_DIR\n";
    $TOPATCH=GetFilesToPatch($ROOT_DIR);
    foreach ($TOPATCH as $fname){
        if(preg_match("#\.idea#",$fname)){continue;}
        $SourceFilename="$ROOT_DIR/$fname";
        $DestinationFilename="$TARGET_PATH/$fname";
        $md5=md5_file($SourceFilename);
        $md52=md5_file($DestinationFilename);
        if($md5==$md52){
            continue;
        }
        echo "Patching $DestinationFilename\n";
        if(!@copy($SourceFilename,$DestinationFilename)){
            echo "Patching $DestinationFilename FAILED\n";
            return false;
        }
    }

    system("cd $ROOT_DIR");
    chdir($ROOT_DIR);
    echo "Commit $hotfixTime\n";
    shell_exec("$gitbin -C $ROOT_DIR commit -m '$hotfixTime'");
    shell_exec("$gitbin -C $ROOT_DIR push origin master");
    echo "Done\n";
    duplicate_common_components();
    return true;
}
function dev_hotfix(){
    official_hotfix(true);
}
function TestSSH(){
    $params=ssh_parse_config();
    $remotebase=$params["basename"];
    $ssh_client=new ssh_client($params["hostname"],$params["port"],$params["user"],$params["password"]);

    if(!$ssh_client->connect()){
        echo "SSH Connection {$params["user"]}@{$params["hostname"]}:{$params["port"]} failed ssh_client->connect()\n";
        echo $ssh_client->GetLogs()."\n";
        die();
    }

    $ssh_client->disconnect();
}
function official_hotfix($dev=false):bool{
    $hotfixTime=date("Ymd-H");
    $ROOT_DIR="/home/dtouzeau/Bureau/Hotfix";
    $MAIN_PATH="/usr/share/artica-postfix";
    $VERSION=@file_get_contents("$MAIN_PATH/VERSION");
    $PACKAGE_DIR="$ROOT_DIR/Compiled/$VERSION";
    $WORKDIR="$ROOT_DIR/$VERSION";
    $SPVER=0;
    if(is_file("$MAIN_PATH/SP/$VERSION")){
        $SPVER=intval(@file_get_contents("$MAIN_PATH/SP/$VERSION"));
    }
    if(!is_dir($PACKAGE_DIR)){@mkdir($PACKAGE_DIR,0755,true);}
    $TARGET_PATCH_FILE="$PACKAGE_DIR/$hotfixTime.tgz";
    $TARGET_DIR="$WORKDIR/artica-postfix";
    if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}
    if(!is_dir($TARGET_DIR)){@mkdir($TARGET_DIR,0755,true);}


    if(is_file("$TARGET_DIR/fw.updates.php")){
        @unlink("$TARGET_DIR/fw.updates.php");
    }
    @copy("$MAIN_PATH/fw.updates.php","$TARGET_DIR/fw.updates.php");
    if(!PatchfwUpdatesPHP($TARGET_DIR,$hotfixTime)){
        echo "Not Patched in $TARGET_DIR/fw.updates.php!!!\n";
        return false;
    }

    $TOPATCH=GetFilesToPatch($MAIN_PATH);

    if(count($TOPATCH)==0){
        echo "Nothing to patch!\n";
        return true;
    }
    system("mkdir -p $TARGET_DIR/ressources/externals/fpdf");
    system("chmod 0755 $TARGET_DIR/ressources/externals/fpdf");
    system("cp -rf $MAIN_PATH/ressources/externals/fpdf/* $TARGET_DIR/ressources/externals/fpdf/");

    if(!is_dir("$TARGET_DIR/ressources/externals/fpdf")){
        echo "$TARGET_DIR/ressources/externals/fpdf no such dir\n";
        exit(1);
    }

    foreach ($TOPATCH as $srcfile){
        if($srcfile=="fw.updates.php"){continue;}
        if(preg_match("#language\.txt$#",$srcfile)){continue;}
        if(preg_match("#\/SP\/#",$srcfile)){continue;}
        $srcPath="$MAIN_PATH/$srcfile";
        $DestPath="$TARGET_DIR/$srcfile";
        $dirname=dirname($DestPath);
        if(!is_dir($dirname)){
            @mkdir($dirname,0755,true);
        }
        if(is_file($DestPath)){@unlink($DestPath);}
        if(!is_file($srcPath)){continue;}
        echo "Copy to $DestPath\n";
        @copy($srcPath,$DestPath);
        if(!is_file($DestPath)){
            echo "$DestPath -- FAILED!\n";
            return true;
        }
        echo "Patching $srcPath\n";

    }

    $TODELETE=FilesToDeleteBefore($TARGET_DIR);
    foreach ($TODELETE as $destfile){
        if(is_file($destfile)){@unlink($destfile);}
    }
    if(!PatchfwUpdatesPHP($TARGET_DIR,$hotfixTime)) {
        echo "Not Patched in $TARGET_DIR/fw.updates.php!!!\n";
        return false;
    }


    shell_exec("rmdir $TARGET_DIR/SP");
    shell_exec("cp -f $MAIN_PATH/bin/reputation-injecter $TARGET_DIR/bin/reputation-injecter");
    shell_exec("cp -f $MAIN_PATH/bin/reverse-proxy $TARGET_DIR/bin/reverse-proxy");
    shell_exec("cp -f $MAIN_PATH/bin/hotspot-web $TARGET_DIR/bin/hotspot-web");
    shell_exec("cp -f $MAIN_PATH/bin/articarest $TARGET_DIR/bin/articarest");
    shell_exec("cp -f $MAIN_PATH/bin/pogocache $TARGET_DIR/bin/pogocache");
    shell_exec("cp -f $MAIN_PATH/bin/go-shield/server/bin/go-shield-server $TARGET_DIR/bin/go-shield/server/bin/go-shield-server");

    if(!is_dir("$TARGET_DIR/ressources/externals/fpdf")){
        echo "$TARGET_DIR/ressources/externals/fpdf no such dir\n";
        exit(1);
    }

    chdir($WORKDIR);
    system("cd $WORKDIR");

    if(is_file($TARGET_PATCH_FILE)){
        @unlink($TARGET_PATCH_FILE);
    }
    system("tar -czvf $TARGET_PATCH_FILE *");
    echo "$TARGET_PATCH_FILE ($hotfixTime) Done\n";

    if($GLOBALS["NO_UPLOAD"]){
        echo "SSH Connection Denied\n";
        return true;
    }

    $params=ssh_parse_config();
    $remotebase=$params["basename"];
    $ssh_client=new ssh_client($params["hostname"],$params["port"],$params["user"],$params["password"]);

    if(!$ssh_client->connect()){
        echo "SSH Connection {$params["user"]}@{$params["hostname"]}:{$params["port"]} failed ssh_client->connect()\n";
        echo $ssh_client->GetLogs()."\n";
        die();
    }

    $remotepatch=$remotebase."/$VERSION/hotfix/testing/$SPVER/".basename($TARGET_PATCH_FILE);
    if($dev){
        $remotepatch=$remotebase."/$VERSION/hotfix/dev/$SPVER/".basename($TARGET_PATCH_FILE);
    }
    echo "Copy patch to $remotepatch\n";
    echo "URL: ". str_replace("/home/www.artica.fr/","http://articatech.net/",$remotepatch)."\n";
    if(!$ssh_client->copyfile($TARGET_PATCH_FILE,$remotepatch,0755)){
        echo "Copy patch to $remotepatch Failed\n";
        $ssh_client->disconnect();
        return false;
    }
    echo "$TARGET_PATCH_FILE DONE\n";
    echo "Copy patch to $remotepatch Success\n";
    echo "Refresh Cloud system...\n";
    shell_exec("curl http://articatech.net/artica.update4.php?verbose=yes >/dev/null 2>&1");
    //duplicate_common_components();
    return true;

}

function hotfix($mainDir):bool{
    $CrcDir=$mainDir;
    if(!is_dir($mainDir)){
        echo "$mainDir, no such directory !\n";
        return false;
    }
    $mainDir="$mainDir/artica-postfix";
    if(!is_dir($mainDir)){
        echo "$mainDir, no such directory !\n";
        return false;
    }
    $VERSION_PATH="$mainDir/VERSION";
    if(!is_file($VERSION_PATH)){
        echo "$VERSION_PATH, no such file !\n";
        return false;
    }
    $VERSION=trim(@file_get_contents($VERSION_PATH));
    echo "Building patch for $VERSION\n";
    $TARGET_TGZ="$CrcDir/$VERSION.tgz";
    if(is_file($TARGET_TGZ)){@unlink($TARGET_TGZ);}
    echo "Copy languages packs...\n";
    if(!is_dir("$mainDir/ressources/language")){@mkdir("$mainDir/ressources/language",0755,true);}
    shell_exec("/bin/cp /usr/share/artica-postfix/ressources/language/*.db $mainDir/ressources/language/");
    chdir("$CrcDir");
    shell_exec("cd $CrcDir");
    echo "Compressing $TARGET_TGZ\n";
    shell_exec("tar --exclude=.gitea --exclude=$VERSION.tgz -czvf $TARGET_TGZ *");
    return true;
}

function RebuildSP(){
    $PATCHDIR=$GLOBALS["PATCHDIR"];
    echo "Using directory $PATCHDIR\n";
    $TARGET_DIR="$PATCHDIR/artica-postfix";
    $CURRENT_PF="$TARGET_DIR/SP/current.pf";
    if(!is_file($CURRENT_PF)){
        die("$CURRENT_PF no such file");
    }
    $MAIN_VERSION=@file_get_contents($CURRENT_PF);
    $CURPATCH=intval(@file_get_contents("$TARGET_DIR/SP/$MAIN_VERSION"));
    echo "Current SP ==> $CURPATCH\n";
    $GLOBALS["FORCE_VERSION"]=$CURPATCH;
    build_ServicePack();
}

function create_ServicePackFree($SPNumber,$Directory=null){
    if(!is_numeric($SPNumber)){
        echo "$SPNumber Not numeric\n";
        die();
    }
    if($Directory==null){
        $Directory=$GLOBALS["PATCHDIR"];
    }

    echo "Scanning $Directory/artica-postfix\n";
    $DirectoryFiles="$Directory/artica-postfix";
    if(!is_dir($DirectoryFiles)){
        echo "$DirectoryFiles No such dir\n";
        die();
    }
    if(!is_file("$DirectoryFiles/SP/current.pf")){
        echo "$DirectoryFiles/SP/current.pf, no such file\n";
        die();
    }
    $MAIN_VERSION=@file_get_contents("$DirectoryFiles/SP/current.pf");
    echo "Building Service Pack For Artica v$MAIN_VERSION\n";
    if(!create_ServicePackFree_index($Directory)){
        echo "Unable to create index file\n";
        die();
    }
    $LanguageDir="/usr/share/artica-postfix/ressources/language";
    $langs[]="br.db";
    $langs[]="de.db";
    $langs[]="en.db";
    $langs[]="es.db";
    $langs[]="fr.db";
    $langs[]="it.db";
    $langs[]="po.db";
    $langs[]="pol.db";

    echo "Copy Language packs\n";

    foreach ($langs as $language){
        $srcfile="$LanguageDir/$language";
        $destfile="$DirectoryFiles/ressources/language/$language";
        if(is_file($destfile)){@unlink($destfile);}

        if(!@copy($srcfile,$destfile)){
            echo "$srcfile -> $destfile [FAILED]\n";
        }else{
            echo "$srcfile -> $destfile [SUCCESS]\n";
        }
    }

    echo "Patching $Directory with Service Pack $SPNumber\n";
    @file_put_contents("$DirectoryFiles/SP/$MAIN_VERSION",$SPNumber);
    echo "Compressing to ArticaSP$SPNumber.tgz\n";
    system("cd $Directory");
    chdir($Directory);
    if(is_file("$Directory/--exclude=.gitea")){
        @unlink("$Directory/--exclude=.gitea");
    }
    echo "Compressing $Directory/ArticaP$SPNumber.tgz\n";
    $excludes=array();
    $excludes[]="--exclude=.gitea";
    $excludes[]="--exclude='ArticaP[0-9]*.tgz'";


    system("tar ".@implode(" ",$excludes)." -czf $Directory/ArticaP$SPNumber.tgz *");
    echo "Done\n\n";

}

function build_ServicePack(){
    $useraccount=$GLOBALS["useraccount"];
    $VERSION=$GLOBALS["VERSION"];
    $PATCHDIR=$GLOBALS["PATCHDIR"];
    $TARGET_DIR="$PATCHDIR/artica-postfix";
    $CURPATCH=intval(@file_get_contents("$TARGET_DIR/SP/$VERSION"));
    echo "Curpatch ==> $CURPATCH\n";
    $NEXTPATCH=$CURPATCH+1;
    $MAIN_PATH="/usr/share/artica-postfix";

    //$uri="https://raw.githubusercontent.com/CpanelInc/tech-CSI/master/suspicious_files.txt";
    //shell_exec("curl $uri --output $MAIN_PATH/ressources/suspicious_files.txt");

    $srcgo="/home/dtouzeau/go/src/github.com/dtouzeau";
    @unlink("$MAIN_PATH/bin/proxy-watchdog");

    if($GLOBALS["FORCE_VERSION"]>0){
        $NEXTPATCH=$GLOBALS["FORCE_VERSION"];
    }

    echo " -------- $VERSION $CURPATCH NEW PATCH v$NEXTPATCH --------\n";
    if(!is_dir($TARGET_DIR)){@mkdir($TARGET_DIR,0755,true);}

    shell_exec("php /usr/share/artica-postfix/compile-lang.php");
    $git = "/usr/bin/git -C $MAIN_PATH";
    $Files[]="ressources/language/en.db";

    $results=array();
    system("export LANG=en_GB");
    exec("$git config --local http.postBuffer 157286400");
    exec("$git add . 2>&1", $results);
    exec("$git status 2>&1", $results);



    foreach ($results as $line) {
        $line = trim($line);

        if(preg_match("#(Project_Default|workspace|deployment)\.xml#",$line)){continue;}
        if(preg_match("#language\/.*?\.txt$#",$line)){continue;}
        if ($line == null) {
            continue;
        }
        if (preg_match("#^(new file|nouveau fichier|modifi).*?:\s+(.+)#", $line, $re)) {
            $re[2]=str_replace("\r\n","",$re[2]);
            $re[2]=str_replace("\n","",$re[2]);
            $re[2]=str_replace("\r","",$re[2]);
            $final=trim($re[2]);
            echo "F-->'$final'\n";
            $Files[] = trim($re[2]);
        }

    }
    $time=time();
    $modified_files_path="{$GLOBALS["basedir"]}/modified-$NEXTPATCH-$time.txt";
    echo "Saving $modified_files_path\n";
    @file_put_contents($modified_files_path,@implode("\n",$Files));

    foreach ($Files as $line){
        $Source="$MAIN_PATH/$line";
        if (preg_match("#\"img\/#",$Source)){
            continue;
        }
        if(!is_file($Source)){
            echo "\"$Source\" no such file\n";
            die();
        }


        $Destination="$TARGET_DIR/$line";
        $Dirname=dirname($Destination);
        if(!is_dir($Dirname)){@mkdir($Dirname,0755,true);}
        if(is_file($Destination)){@unlink($Destination);}
        if(!@copy($Source,$Destination)){
            echo "Failed to copy $Source to \"$Destination\"\n";
            print_r(error_get_last());
            die();
        }
    }

    $Mandatories[]="bin/articarest";
    $Mandatories[]="bin/reverse-proxy";
    $Mandatories[]="bin/artica-nfqueue";
    $Mandatories[]="bin/artica-error-page";
    $Mandatories[]="bin/go-shield/server/bin/go-shield-server";

    foreach ($Mandatories as $mandatory){
        $Source="/usr/share/artica-postfix/$mandatory";
        $Destination="$TARGET_DIR/$mandatory";
        $Dirname=dirname($Destination);
        if(!is_dir($Dirname)){@mkdir($Dirname,0755,true);}
        if(is_file($Destination)){@unlink($Destination);}
        echo __LINE__."] Copy $Source -> $Destination\n";
        if(!@copy($Source,$Destination)){
            echo "Failed to copy $Source to \"$Destination\"\n";
            print_r(error_get_last());
            die();
        }

    }

    $DIRSDEL[]="$TARGET_DIR/bin/go-shield/client/.idea";
    $DIRSDEL[]="$TARGET_DIR/bin/go-shield/server/.idea";
    $DIRSDEL[]="$TARGET_DIR/.idea";

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
    $frm[]="ressources/categorizeclass.py";
    $frm[]="compile-cd-deb10-kasfaleia.php";
    $frm[]="ressources/categorizeclass.py";
    $frm[]="ressources/downloadclass.py";
    $frm[]="ressources/publicsuffix.py";
    $frm[]="ressources/k9.py";
    $frm[]="bin/proxy-watchdog";
    $frm[]="ressources/K9Query.py";
    $frm[]="bin/k9.py";
    $frm[]="pptpd.php";
    $frm[]="compile-nagios.sh";
    $frm[]="toto.json";
    $frm[]="mailspy.index.php";
    $frm[]="compile-kopia.sh";
    $frm[]="TestsClasses.php";
    $frm[]="compile-ufdb-debian10-debian12.sh";
    $frm[]="computers.ocs.single.php";
    $frm[]="exec.git.buildPatch.php";
    $frm[]="wizard.fetchmail.newbee.php";
    $frm[]="ressources/itchartclass.py";
    $frm[]="ressources/activedirectoryclass.py";
    $frm[]="ressources/theshieldsclass.py";
    $frm[]="ressources/classartmem.py";
    $frm[]="ressources/itchartclass.py";
    $frm[]="ressources/classgeoip2free.py";
    $frm[]="ressources/unix.py";
    $frm[]="bin/install/dnsfw-example.py";
    $frm[]="ressources/dnsfirewallclass.py";
    $frm[]="ressources/theshieldsclient.py";
    $frm[]="ressources/ufdbclass.py";
    $frm[]="ressources/theshieldsservice.py";
    $frm[]="ressources/classhttp.py";
    $frm[]="bin/artica-smtpd.py";
    $frm[]="bin/dnsfw-example2.py";
    $frm[]="bin/squid-service.py";
    $frm[]="bin/squid-service";
    $frm[]="bin/StatsCommunicator.py";
    $frm[]="bin/artica-smtpd.py";
    $frm[]="bin/squid-service.py";
    $frm[]="bin/getdisks";
    $frm[]="bin/StatsCommunicator.py";
    $frm[]="test.py";
    $frm[]="proxmox-iptables.sh";
    $frm[]="exec.rpz-master.php";
    $frm[]="bin/external_acl_first.oldv2.py";
    $frm[]="bin/external_acl_first.oldv1.py";
    $frm[]="bin/external_acl_first.py";
    $frm[]="ressources/threadobject.py";
    $frm[]="bin/external_acl_first.bak.py";
    $frm[]="bin/external_acl_krsn.py";
    $frm[]="exec.rpz-master.php";
    $frm[]="exec.atomi.php";
    $frm[]="ressources/br.db";
    $frm[]="ressources/de.db";
    $frm[]="ressources/en.db";
    $frm[]="ressources/es.db";
    $frm[]="ressources/fr.db";
    $frm[]="ressources/it.db";
    $frm[]="compile-go.php";
    $frm[]="bin/authlogs";
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
    $frm[]="proxy-pac.py";
    $frm[]="exec.statsredis.php";
    $frm[]="bin/StatsCommunicator.py";
    $frm[]="bin/StatsCommunicator";
    $frm[]="bin/go-shield-server";
    $frm[]="exec.StatsCommunicator.php";
    $frm[]="auth-tail.py";
    $frm[]="ufdbgclient.php";
    $frm[]="ufdbgweb.py";
    $frm[]="test.txt";
    $frm[]="xtables-4.19.0-10-amd64.tar.gz";
    $frm[]="xtables-4.19.0-13-amd64.tar.gz";
    $frm[]="xtables-4.19.0-16-amd64.tar.gz";
    $frm[]="js/tiny_mce/jquery.tinymce.min.js";

    $frm[]="js/jquery-1.1.3.1.pack.js";
    $frm[]="js/jquery-1.6.1.min.js";
    $frm[]="js/jquery-1.6.2.min.js";
    $frm[]="js/jquery-1.7.2.min.js";
    $frm[]="js/jquery-1.8.0.min.js";
    $frm[]="js/jquery-1.8.3.js";
    $frm[]="angular/js/jquery/jquery-2.1.1.min.js";
    $frm[]="angular/js/jquery/jquery-3.1.1.min.js";
    $frm[]="exec.ipset.master.compile.php";
    $frm[]="exec.nrds.master.compile.php";
    $frm[]="compile-go-shield-server.php";
    $frm[]="compile-go.php";
    $frm[]="exec.git.buildPatch.php";
    $dirs[]=".settings";
    $dirs[]="bin/go-shield/.git";
    $dirs[]="bin/go-shield/handlers";
    $dirs[]="bin/go-shield/server/.idea";
    // server mode
    $frm[]="exec.ipset.master.compile.php";
    $frm[]="exec.nrds.master.compile.php";
    $frm[]="exec.dshield.php";
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

    $tt=explode("\n",file_get_contents("/usr/share/artica-postfix/ressources/databases/ToDelete.db"));
    foreach ($tt as $t) {
        $t=trim($t);
        if($t==""){
            continue;
        }
        $frm[]=$t;
    }
    foreach ($dirs as $directory){
        if(is_dir("$TARGET_DIR/$directory")){
            shell_exec("rm -rf $TARGET_DIR/$directory");
        }
    }
    foreach ($frm as $fname){
        if(is_file("$TARGET_DIR/$fname")){
            echo "Remove $TARGET_DIR/$fname\n";
            @unlink("$TARGET_DIR/$fname");}
    }
    @unlink("$TARGET_DIR/SP/index.pf");
    echo "Creating $TARGET_DIR/SP/index.pf\n";
    @file_put_contents("$TARGET_DIR/SP/index.pf",SoftIndex());
    @file_put_contents("$TARGET_DIR/SP/current.pf",$GLOBALS["VERSION"]);

    @chdir("$PATCHDIR");
    $PatchPath="$PATCHDIR/ArticaP{$NEXTPATCH}.tgz";
    echo "Compressing $PatchPath\n";
    shell_exec("tar -czvf $PatchPath artica-postfix");
    echo "$PatchPath done\n";
    shell_exec("chown $useraccount:$useraccount $PATCHDIR");
    shell_exec("chown $useraccount:$useraccount $PatchPath");
    if(!is_dir("$TARGET_DIR/SP")){@mkdir("$TARGET_DIR/SP",0755,true);}
    @file_put_contents("$TARGET_DIR/SP/$VERSION",$NEXTPATCH);

    if($GLOBALS["FORCE_COMMIT"]<>null){
        $GLOBALS["FORCE_COMMIT"]=" ({$GLOBALS["FORCE_COMMIT"]})";
    }
    echo "Push to git...\n";
    exec("$git commit -m \"$VERSION SP {$NEXTPATCH}{$GLOBALS["FORCE_COMMIT"]}\" 2>&1", $results);
    exec("$git push origin master");

    $params=ssh_parse_config();
    $remotebase=$params["basename"];
    $remotepatch=$remotebase."/$VERSION/".basename($PatchPath);
    if($GLOBALS["NO_UPLOAD"] ){
        echo "Uploading Service Pack aborted\n";
        echo "Don't forget to push the SP here: $remotepatch\n";
        echo "/home/www.artica.fr/download/patchs/$VERSION\n";
        echo "Reset Patch Table:http://articatech.net/service-packs-unstable.php?verbose=yes\n";
        die();
    }

    $params=ssh_parse_config();
    $ssh_client=new ssh_client($params["hostname"],$params["port"],$params["user"],$params["password"]);

    if(!$ssh_client->connect()){
        echo "SSH Connection failed\n";
        die();
    }
    echo "Copy patch to {$params["hostname"]}:$remotepatch\n";
    if(!$ssh_client->copyfile($PatchPath,$remotepatch,0755)){
        echo "Copy patch to {$params["hostname"]}:$remotepatch Failed\n";
        $ssh_client->disconnect();
        return false;
    }
    echo "Copy patch to {$params["hostname"]}:$remotepatch Success\n";

    $remotepatch=$remotebase."/$VERSION/WHATSNEW";
    if(!$ssh_client->copyfile("/usr/share/artica-postfix/WHATSNEW",$remotepatch,0755)){
        echo "Copy patch to {$params["hostname"]}:$remotepatch Failed\n";
        $ssh_client->disconnect();
        return false;
    }
    echo "Copy patch to {$params["hostname"]}:$remotepatch Success\n";

    $ssh_client->disconnect();

    echo "Reset the patch table...\n";
    system("wget \"http://articatech.net/service-packs-unstable.php?verbose=yes\" -O /dev/null");
    sendmail($VERSION,$NEXTPATCH,$GLOBALS["FORCE_COMMIT"],$modified_files_path,$PatchPath);
    return true;
}

function ssh_parse_config():array{
    $params=array();
    $user=$GLOBALS["useraccount"];
    $filename="/home/$user/.articassh.conf";
    $f=explode("\n",@file_get_contents($filename));

    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^username=(.+)#",$line,$re)){$params["user"]=$re[1];}
        if(preg_match("#^password=(.+)#",$line,$re)){$params["password"]=$re[1];}
        if(preg_match("#^hostname=(.+)#",$line,$re)){$params["hostname"]=$re[1];}
        if(preg_match("#^port=([0-9]+)#",$line,$re)){$params["port"]=$re[1];}
        if(preg_match("#^basename=(.+)#",$line,$re)){$params["basename"]=$re[1];}


    }
    return $params;

}
function create_ServicePackFree_index($PATCHDIR=null){

    if($PATCHDIR==null){
        $PATCHDIR=$GLOBALS["PATCHDIR"];
    }

    $TARGET_DIR="$PATCHDIR/artica-postfix";
    $SOFTPATH="/usr/share/artica-postfix";
    if(!is_dir($TARGET_DIR)){
        echo "$TARGET_DIR no such dir\n";
        return false;
    }
    if(!is_dir("$TARGET_DIR/SP")){
        echo "$TARGET_DIR/SP no such dir\n";
        return false;
    }

    exec("find $TARGET_DIR 2>&1",$results);
    foreach ($results as $line){
        if(is_dir($line)){continue;}
        $md5=md5_file($line);
        $line=str_replace($TARGET_DIR,$SOFTPATH,$line);
        $MAIN[$line]=$md5;
    }

    $data=serialize($MAIN);
    @unlink("$TARGET_DIR/SP/index.pf");
    @file_put_contents("$TARGET_DIR/SP/index.pf",$data);
    return true;
}

function SoftIndex(){
    $PATCHDIR=$GLOBALS["PATCHDIR"];
    $TARGET_DIR="$PATCHDIR/artica-postfix";
    $SOFTPATH="/usr/share/artica-postfix";

    exec("find $TARGET_DIR 2>&1",$results);
    foreach ($results as $line){
        if(is_dir($line)){continue;}
        $md5=md5_file($line);
        $line=str_replace($TARGET_DIR,$SOFTPATH,$line);
        $MAIN[$line]=$md5;
    }

    return serialize($MAIN);

}


function recall($NEXTPATCH){
    $VERSION=$GLOBALS["VERSION"];
    $PATCHDIR=$GLOBALS["PATCHDIR"];
    echo "$VERSION Service Pack $NEXTPATCH\n";
    $modified_files_path=null;

    $dir_handle = @opendir($GLOBALS["basedir"]);
    while ($file = readdir($dir_handle)) {
        if($file=='.'){continue;}
        if($file=='..'){continue;}
        if(preg_match("#^modified-$NEXTPATCH-#",$file)){
            $modified_files_path="{$GLOBALS["basedir"]}/$file";
            echo "Scanning $modified_files_path\n";
            if(!Stampfiels($modified_files_path,$NEXTPATCH)){return false;}
            break;
        }
    }

}

function  Stampfiels($modified_files_path,$NEXTPATCH){
    if(!is_file($modified_files_path)){return false;}
    $PATH="/usr/share/artica-postfix";
    $f=explode("\n",@file_get_contents("$modified_files_path"));
    foreach ($f as $line){
        if(!preg_match("#\.(php|inc)$#",$line)){continue;}
        $fullpath="$PATH/$line";
        echo "Patching $fullpath\n";
        xtsamp($fullpath,"MOD-$NEXTPATCH");
    }
    return true;
}

function xtsamp($fullpath,$mod){
    $f=@file_get_contents($fullpath);
    $f=str_replace("<?php\n","<?php\n"."/"."/$mod\n",$f);
    @file_put_contents($fullpath,$f);
}

function sendmail_recall($NEXTPATCH,$FORCE_COMMIT){
    $VERSION=$GLOBALS["VERSION"];
    $PATCHDIR=$GLOBALS["PATCHDIR"];
    echo "$VERSION Service Pack $NEXTPATCH\n";


    $dir_handle = @opendir($GLOBALS["basedir"]);
    while ($file = readdir($dir_handle)) {
        if($file=='.'){continue;}
        if($file=='..'){continue;}
        if(preg_match("#^modified-$NEXTPATCH-#",$file)){
            $modified_files_path="{$GLOBALS["basedir"]}/$file";
            break;
        }
    }
    $PatchPath="$PATCHDIR/ArticaP{$NEXTPATCH}.tgz";
    echo "Modified files = $modified_files_path\n";
    echo "PatchPath = $PatchPath\n";
    echo "FORCE_COMMIT = $FORCE_COMMIT\n";

    sendmail($VERSION,$NEXTPATCH,$FORCE_COMMIT,$modified_files_path,$PatchPath);
}

function GetDetailsWhatsnew($SP){
    $slines=array();
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/WHATSNEW"));

    foreach ($f as $line){
        if(!preg_match("#^SP{$SP}:(.+)#",$line,$re)){continue;}
        $slines[]=$line;
    }
    if(count($slines)==0){return "";}
    return @implode("\r\n",$slines);
}

function sendmail($VERSION,$SP,$obs,$modified_files_path,$PatchPath){
    if($GLOBALS["NO_MAIL"]){return false;}
    $obs=trim($obs);
    $infos=explode("\n",@file_get_contents("/root/git-smtp.conf"));
    $recipients=$infos[0];
    $Auth=$infos[1];
    $Password=$infos[2];
    $topic_sub=null;
    $size_text=null;
    $smtp_sender="david@articatech.com";
    $modified_files_data=@file_get_contents($modified_files_path);
    $modified_files_data=str_replace("\n","\r\n",$modified_files_data);
    if(is_file($PatchPath)){
        $size=@filesize($PatchPath);
        $size_text=" (".FormatBytes($size/1024).")";
    }

    $uri="http://articatech.net/download/UPatchs/$VERSION/ArticaP$SP.tgz";
    $whatsnew="http://articatech.net/service-packs-unstable-new.php?patch=$SP&main=$VERSION";
    $modified_text="\r\r----------------------------------------------------\r\nHere the modified/added files in this commit:\r\n$modified_files_data";
    if($obs<>null){$topic_sub="The main topic on git is : \"$obs\"";}
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAutoTLS=false;
    $zrecipients=explode(",",$recipients);
    $mail->AddAddress(trim("david@articatech.com"), trim("david@articatech.com"));
    foreach ($zrecipients as $to) {
        $mail->addBCC(trim($to), trim($to));
    }
    $whatlines=GetDetailsWhatsnew($SP);
    if($whatlines<>null){
        $whatlines="\r\n$whatlines\r\n";
    }

    $mail->AddReplyTo($smtp_sender,$smtp_sender);
    $mail->From=$smtp_sender;
    $mail->FromName=$smtp_sender;
    $mail->Subject="New Artica v$VERSION Service Pack $SP";

    $sbody[]="Dear customer,";
    $sbody[]="A new Artica Service pack as been uploaded to articatech";
    $sbody[]="Download link: $uri$size_text";
    $sbody[]="";
    $sbody[]="Fixes and new features can displayed here";
    $sbody[]=$whatsnew;
    $sbody[]=$topic_sub;
    $sbody[]="";
    $sbody[]="Note: The 4.40 is currently a Release Candidate version which, when stabilized, will release the 4.50 LTS.";

    $sbody[]="Changes between the 4.30 LTS and the 4.40:";
    $sbody[]="https://wiki.articatech.com/en/maintenance/whatsnew-430-440";
    $sbody[]="";
    $sbody[]="About the Current 4.30 LTS:";
    $sbody[]="The current LTS (Long Term support) is the 4.30 Service Pack 208 version.";
    $sbody[]="An Hotfix is available for the LTS:";
    $sbody[]="https://wiki.articatech.com/en/maintenance/upgrade-artica/hotfix-430-sp208";
    $sbody[]="";
    $sbody[]=$whatlines;
    $sbody[]="";
    $sbody[]=$modified_text;

    $sbody[]="";
    $sbody[]="* If you no longer wish to receive this type of message, simply reply to this email with \"Unsubscribe\" in the subject or body *";
    $sbody[]="";
    

    $mail->Body=@implode("\r\n",$sbody);
    $mail->Host="mail.articatech.com";
    $mail->Port=25;
    $mail->Username=$Auth;
    $mail->SMTPAuth=true;
    echo "$Auth  -  <$Password>\n";
    $mail->Password=$Password;
    if(!$mail->Send()) {
        $Error = $mail->ErrorInfo;
        echo "$Error\n";
    }

}

function FormatBytes($kbytes){

    $spacer= " ";
    if($kbytes>1048576){
        $value=round($kbytes/1048576, 2);
        if($value>1000){
            $value=round($value/1000, 2);
            return "$value{$spacer}TB";
        }
        return "$value{$spacer}GB";
    }
    elseif ($kbytes>=1024){
        $value=round($kbytes/1024, 2);
        return "$value{$spacer}MB";
    }
    else{
        $value=round($kbytes, 2);
        return "$value{$spacer}KB";
    }
}