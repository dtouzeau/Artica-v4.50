<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--menu"){menu();exit;}
if($argv[1]=="--info"){info();exit;}

function menu()
{
    $ARTICAVERSION = @file_get_contents("/usr/share/artica-postfix/VERSION");
    $unix = new unix();
    $HOSTNAME = $unix->hostname_g();
    $DIALOG = $unix->find_program("dialog");
    $php = $unix->LOCATE_PHP5_BIN();
    $sock=new sockets();
    $LighttpdArticaCertificateName=$sock->GET_INFO("LighttpdArticaCertificateName");
    extract_cert();
    $ARRAY=unserialize(@file_get_contents("/etc/artica-postfix/certificate_extract.dmp"));
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");


    $ligne=$q->QUERY_SQL("SELECT ID FROM sslcertificates WHERE CommonName='$LighttpdArticaCertificateName'");
    if(!isset($ligne["ID"])){ $ligne["ID"]=0; }
    $ID=$ligne["ID"];

    $diag[] = "$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
    $diag[] = "--title \"[ C E R T I F I C A T E  -- M E N U ]\"";
    $diag[] = "--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";

    $diag[]="INFO \"$LighttpdArticaCertificateName status\"";

    if(isset($ARRAY["LETSENCRYPT"])){
        $diag[]="RENEW \"Renew Let's Encrypt certificate\"";
    }


    $diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

    $f[]="#!/bin/bash";
    $f[]="INPUT=/tmp/menu.sh.$$";
    $f[]="OUTPUT=/tmp/output.sh.$$";
    $f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
    $f[]="DIALOG=\${DIALOG=dialog}";

    $f[]="function INFO(){";
    $f[]="\t$php ".__FILE__." --info";
    $f[]="\t$DIALOG --textbox /var/log/lighttpd/certificate.log  25 100";
    $f[]="}";

    $f[]="function RENEW(){";
    $f[]="\t$php /usr/share/artica-postfix/exec.certbot.php --create-certificate $ID";
    $f[]="}";

    $f[]="while true";
    $f[]="do";
    $f[]=@implode(" ", $diag);
    $f[]="menuitem=$(<\"\${INPUT}\")";
    $f[]="case \$menuitem in";
    $f[]="INFO) INFO;;";
    $f[]="RENEW) RENEW;;";
    $f[]="Quit) break;;";
    $f[]="esac";
    $f[]="done\n";

    if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_apache_certificate_menu.sh\n";}
    @file_put_contents("/tmp/bash_apache_certificate_menu.sh", @implode("\n",$f));
    @chmod("/tmp/bash_apache_certificate_menu.sh",0755);

}

function extract_cert(){
    if($GLOBALS["VERBOSE"]){echo "Extracting informations....\n";}
    $unix=new unix();
    $openssl=$unix->find_program("openssl");
    $certificate_path=null;
    $t=explode("\n",@file_get_contents("/etc/artica-postfix/webconsole.conf"));
    foreach ($t as $line){
        $line=trim($line);
        if($line==null){continue;}
        if($GLOBALS["VERBOSE"]){echo "Scanning '$line'\n";}
        if(preg_match("#^ssl_certificate\s+(.+?);#",$line,$re)){
            $certificate_path=$re[1];
            break;
        }
    }
    if($GLOBALS["VERBOSE"]){echo "Certificate path = $certificate_path\n";}
    $cmd="$openssl x509 -in $certificate_path -text -noout 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    exec($cmd,$results);

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if($GLOBALS["VERBOSE"]){echo "Scanning '$line'\n";}
        if(preg_match("#Issuer:\s+(.+)#",$line,$re)){
            $ARRAY["Issuer"]=$re[1];
            continue;
        }
        if(preg_match("#Not After\s+:\s+(.+)#",$line,$re)){
            $ARRAY["EXPIRE"]=strtotime($re[1]);
            $ARRAY["EXPIRED"]=$re[1];
            continue;

        }

        if(preg_match("#Subject:\s+(.+)#",$line,$re)){
            $ARRAY["SUBJECTS"][]=$re[1];
            continue;

        }

        if(preg_match("#Public-Key:.*?([0-9]+)#",$line,$re)){
            $ARRAY["ENCRYPTION_LEVEL"]=$re[1];
            continue;

        }
        if(preg_match("#letsencrypt\.org#",$line,$re)){
            $ARRAY["LETSENCRYPT"]=true;
        }


    }

    @file_put_contents("/etc/artica-postfix/certificate_extract.dmp",serialize($ARRAY));




}

function info(){
    $unix=new unix();
    $ARRAY=unserialize(@file_get_contents("/etc/artica-postfix/certificate_extract.dmp"));
    if(isset($ARRAY["LETSENCRYPT"])){
        $f[]="Let's Encrypt certificate...................:";
    }
    $f[]="Expire in: {$ARRAY["EXPIRED"]} - " .date("Y-m-d H:i:s",$ARRAY["EXPIRE"]);
    $f[]="Will expire: ".$unix->distanceOfTimeInWords(time(),$ARRAY["EXPIRE"]);
    $f[]="Issuer: {$ARRAY["Issuer"]}";
    $f[]="Encryption level: {$ARRAY["ENCRYPTION_LEVEL"]}";
    $f[]="Subject:";
    foreach ($ARRAY["SUBJECTS"] as $ff){
        $f[]="\t$ff";
    }

    @file_put_contents("/var/log/lighttpd/certificate.log",@implode("\n",$f));

}