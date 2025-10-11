<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.docker.inc");
include_once("/usr/share/artica-postfix/ressources/class.webconsole.params.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if(!isset($argv[1])){
    help_output();exit;
}
if($argv[1]=="--backend"){
    build_reverse_proxy();
    exit();
}
if($argv[1]=="--webadmin"){
    build_webadmin();
    exit();
}

function help_output():bool{
    echo "--backend\tBuild the image of the backend\n";
    echo "--webadmin\tBuild the image of the Web administration\n";
    return true;
}

function build_webadmin():bool{
    $arp="/usr/share/artica-postfix/bin";
    $f[] = "FROM articatech/artica:adm1";
    $f[] = "LABEL \"com.articatech.artica.type\"=\"ADM\" \"com.articatech.artica.scope\"=\"xxx\"";
    $f[] = "EXPOSE 9000/tcp";
    $f[] = "EXPOSE 9005/tcp";
    $f[] = "RUN mkdir -p /etc/monit/conf.d && mkdir -p /etc/artica-postfix && touch /etc/artica-postfix/AS_DOCKER_SERVICE";
    $f[] = "RUN apt-get update";
    $f[] = "RUN apt-get -y remove systemd* python3-gi krb5-locales --purge";
    $f[] = "RUN apt-get install -y gnupg2 ca-certificates apt-transport-https software-properties-common libevent-2.1-6";
    $f[] = "RUN wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -";
    $f[] = "RUN echo \"deb https://packages.sury.org/php/ buster main\" | tee /etc/apt/sources.list.d/php.list";
    $f[] = "RUN apt-get update && apt-get install -y php8.2 php8.2-xml php8.2-sqlite3 php8.2-pgsql php8.2-curl php8.2-memcache php8.2-posix php8.2-zip php8.2-pdo php8.2-fpm php8.2-cli php8.2-readline php8.2-snmp php8.2-ssh2 php8.2-ldap php8.2-mbstring php8.2-redis php8.2-rrd php8.2-uploadprogress php8.2-memcached php8.2-dba php8.2-mysql php8.2-gd php8.2-pspell && apt-get remove -y --purge apache2 apache2-bin apache2-data apache2-utils libapache2-mod-php8.2";
    $f[] = "RUN apt-get install -y libestr0 libfastjson4";
    $f[] = "RUN apt-get -y clean && apt-get autoremove -y --purge";
    $f[] = "RUN wget http://articatech.net/LTS/artica-4.50.000000.tgz -O /tmp/artica-4.50.000000.tgz && tar -xf /tmp/artica-4.50.000000.tgz -C /usr/share/ && rm /tmp/artica-4.50.000000.tgz && wget http://mirror.articatech.com/download/Debian10-WebConsole/1.22.1.tar.gz -O /tmp/1.22.1.tar.gz && tar -xf /tmp/1.22.1.tar.gz -C /";
    $f[]="RUN wget http://mirror.articatech.com/download/Debian10-postgresql/15.1.tar.gz && tar -xf 15.1.tar.gz -C / && rm 15.1.tar.gz";

    $rm[]="rm -rf /usr/local/modsecurity";
    $rm[]="rm -f /usr/sbin/nginx || true";
    $rm[]="rm -rf /var/lib/apt/lists/* || true";
    $rm[]="rm -rf $arp/go-shield/client";
    $rm[]="rm -rf $arp/go-shield/server";
    $rm[]="rm -rf $arp/go-shield/ad";
    $rm[]="rm -rf $arp/go-shield/fs-watcher";
    $rm[]="rm -f $arp/artica-error-page";
    $rm[]="rm -f $arp/hotspot-web";
    $rm[]="rm -f $arp/adblock2privoxy";
    $rm[]="rm -f $arp/debian-mirror";
    $rm[]="rm -f $arp/HaClusterClient";
    $rm[]="rm -f $arp/proxy-watchdog";
    $rm[]="rm -f $arp/proxy-pac";
    $rm[]="rm -f $arp/artica-proxy-auth";
    $rm[]="rm -f $arp/percpu";

    if(is_file("/tmp/docker-client")){@unlink("/tmp/docker-client");}
    @copy("/usr/share/artica-postfix/bin/docker-client","/tmp/docker-client");
    $f[] = "RUN ".@implode(" && ",$rm);
    $f[] = "RUN /usr/share/artica-postfix/bin/articarest -phpini -debug";
    $f[] = "RUN php /usr/share/artica-postfix/exec.initslapd.php";
    $f[] = "RUN php /usr/share/artica-postfix/exec.go.exec.php || true";
    $f[] = "RUN php /usr/share/artica-postfix/exec.artica-php-fpm.php --install";
    $f[] = "RUN php /usr/share/artica-postfix/exec.convert-to-sqlite.php\n";
    $f[] = "COPY docker-client /usr/sbin/docker-client";
    $f[] = "RUN chmod 0755 /usr/sbin/docker-client";

    @file_put_contents("/tmp/Dockerfile",@implode("\n",$f));
    echo "docker build --no-cache --tag articatech/artica:adm2 /tmp\n";
    build_webadmin_compress_image();
    return true;
}
function build_webadmin_compress_image():bool{
    $time=time();
    $VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
    @copy("/usr/share/artica-postfix/bin/docker-client","/media/dockerhd/dockertemp/docker-client");
    $directory="/media/dockerhd/dockertemp";
    $filename="$directory/Dockerfile";
    $f[]="FROM  articatech/artica:adm2  as source";
    $f[]="FROM scratch";
    $f[]="COPY --from=source / /\n";
    $f[] = "ADD docker-client /usr/sbin/docker-client";
    $f[] = "RUN chmod 0755 /usr/sbin/docker-client";
    $f[] = "LABEL \"com.articatech.artica.version\"=\"$VERSION\"";
    $f[] = "LABEL \"com.articatech.artica.build\"=\"$time\"";
    @file_put_contents($filename,@implode("\n",$f));
    echo "docker image rm articatech/artica:adm1\n";
    echo "docker build --no-cache --tag articatech/artica:adm1 /media/dockerhd/dockertemp\n";
    echo "docker image rm articatech/artica:adm2\n";
    echo "docker image push articatech/artica:adm1\n\n";
    return true;
}


function build_reverse_proxy():bool{
    $arp="/usr/share/artica-postfix/bin";
    file_put_contents("/tmp/docker-method.conf","backend");
    $f[]="FROM articatech/artica:v1";
    $f[]="LABEL \"com.articatech.artica.type\"=\"LB\" \"com.articatech.artica.scope\"=\"XXX\"";
    $f[]="RUN mkdir -p /etc/monit/conf.d";
    $f[]="COPY --chmod=0644 docker-method.conf /etc/docker-method.conf";
    $f[]="ENTRYPOINT [\"/usr/sbin/docker-client\"]";
    $f[] = "EXPOSE 9005/tcp";
    $f[] = "COPY docker-client /usr/sbin/docker-client";
    $f[] = "RUN chmod 0755 /usr/sbin/docker-client";
    if(is_file("/tmp/docker-client")){@unlink("/tmp/docker-client");}
    @copy("/usr/share/artica-postfix/bin/docker-client","/tmp/docker-client");
    @file_put_contents("/tmp/Dockerfile",@implode("\n",$f));
    echo "docker build --no-cache --tag articatech/artica:v2 /tmp\n";
    return build_reverse_proxy_compress_image();
}
function build_reverse_proxy_compress_image():bool{
    $time=time();
    $VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
    $directory="/media/dockerhd/dockernode";
    if(!is_dir($directory)){@mkdir($directory,0755,true);}

    if(!is_file("$directory/docker-client")){@unlink("$directory/docker-client");}
    @copy("/usr/share/artica-postfix/bin/docker-client","$directory/docker-client");

    $filename="$directory/Dockerfile";
    $f[]="FROM  articatech/artica:v2  as source";
    $f[]="FROM scratch";
    $f[]="COPY --from=source / /\n";
    $f[] = "ADD docker-client /usr/sbin/docker-client";
    $f[] = "RUN chmod 0755 /usr/sbin/docker-client";
    $f[] = "LABEL \"com.articatech.artica.version\"=\"$VERSION\"";
    $f[] = "LABEL \"com.articatech.artica.build\"=\"$time\"";
    $f[] = "EXPOSE 9005/tcp";
    $f[] = "LABEL \"com.articatech.artica.type\"=\"LB\" \"com.articatech.artica.scope\"=\"XXX\"";
    @file_put_contents($filename,@implode("\n",$f));
    echo "docker image rm articatech/artica:v1\n";
    echo "docker build --no-cache --tag articatech/artica:v1 $directory\n";
    echo "docker image rm articatech/artica:v2\n";
    echo "docker image push articatech/artica:v1\n\n";
    return true;
}
