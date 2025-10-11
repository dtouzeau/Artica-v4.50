<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
    $GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once("/usr/share/artica-postfix/ressources/class.ldap.inc");

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--build"){build();exit;}

function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/raccoon.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function install(){


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRacoon",1);
    build_progress(10,"{installing} Racoon...");
    install_service_racoon();
    etc_default_racoon();
    build();
    build_progress(20,"{installing} SetKey...");
    install_service_setkey();
    build_progress(30,"{installing}...");
    install_monit();
    build_progress(100,"{installing} {raccon} {done}...");

}

function uninstall(){

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRacoon",0);

    build_progress(10,"{uninstalling} Racoon...");
    remove_service("/etc/init.d/racoon");
    remove_service("/etc/init.d/setkey");
    build_progress(20,"{uninstalling} Racoon...");
    @unlink("/etc/monit/conf.d/APP_RACOON.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    build_progress(100,"{uninstalling} Racoon {done}");

}

function build(){
    $unix=new unix();

    $RacoonListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RacoonListenInterface");
    $RacoonCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RacoonCertificate");
    $RacconFirstIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RacconFirstIP");
    $RacconMAXIP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RacconMAXIP"));
    $RacconClientNetMask=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RacconClientNetMask");
    $RacconClientDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RacconClientDNS");
    if($RacconFirstIP==null){$RacconFirstIP="192.168.254.130";}
    if($RacconMAXIP==0){$RacconMAXIP=100;}
    if($RacconClientNetMask==null){$RacconClientNetMask="255.255.0.0";}

    if($RacoonListenInterface==null){$RacoonListenInterface="eth0";}
    $RacoonListenIPAddr=$unix->InterfaceToIPv4($RacoonListenInterface);

    if($RacoonCertificate==null){

    }

    @mkdir("/etc/racoon/certs",0755,true);

    echo "Starting......: ".date("H:i:s")." [INIT]: Certificate: $RacoonCertificate\n";
    if($RacoonCertificate<>null) {
        $q = new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $sql = "SELECT `srca`,`crt`,`privkey`,`Squidkey`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`  
        FROM sslcertificates WHERE CommonName='$RacoonCertificate'";
        $ligne = $q->mysqli_fetch_array($sql);
        if(!$q->ok) {
            echo "Starting......: ".date("H:i:s")." [INIT]: ".$q->mysql_error."\n";
        }
        $Expose_content = $ligne["srca"];
        $Expose_content = str_replace("\\n", "\n", $Expose_content);
        @file_put_contents("/etc/racoon/certs/vpngw_ca.pem", $Expose_content);
        echo "Starting......: ".date("H:i:s")." [INIT]: Saving: /etc/racoon/certs/vpngw_ca.pem\n";
        $field="privkey";

        if($ligne["UsePrivKeyCrt"]==0){
            $field="Squidkey";
            if(strlen($ligne[$field])<10){
                if(strlen($ligne["privkey"])>10){$field="privkey";}
            }
        }
        $ligne[$field] = str_replace("\\n", "\n", $ligne[$field]);
        @file_put_contents("/etc/racoon/certs/vpngw_key.pem", $ligne[$field]);
        echo "Starting......: ".date("H:i:s")." [INIT]: Saving: /etc/racoon/certs/vpngw_key.pem\n";

        $field = "crt";
        if ($ligne["UsePrivKeyCrt"] == 0) {
            $field = "SquidCert";
        }
        $ligne[$field] = str_replace("\\n", "\n", $ligne[$field]);
        @file_put_contents("/etc/racoon/certs/vpngw_cert.pem", $ligne[$field]);
        echo "Starting......: ".date("H:i:s")." [INIT]: Saving: /etc/racoon/certs/vpngw_cert.pem\n";
    }

    $f[]="# Répertoire où sont stockés les certificats";
    $f[]="path certificate \"/etc/racoon/certs\";";
    $f[]="";
    $f[]="# Les IP sur lesquelles Racoon écoute";
    $f[]="listen {";
    $f[]="\tisakmp $RacoonListenIPAddr [500];";
    $f[]="\tisakmp_natt $RacoonListenIPAddr [4500];";
    $f[]="}";
    $f[]="";
    $f[]="# Définition d'un noeud distant";
    $f[]="# Ici, on ne sait pas d'où viennent les connexions...";
    $f[]="# On peut venir de n'importe où en roadwarrior";
    $f[]="remote anonymous {";
    $f[]="\t# Définition du mode pour la phase 1";
    $f[]="\t# Doit être à aggressive pour une configuration roadwarrior";
    $f[]="\texchange_mode aggressive;";
    $f[]="\t# le certificat et sa clef associée que l'on a généré pour notre concentrateur";
    $f[]="\tcertificate_type x509 \"vpngw_cert.pem\" \"vpngw_key.pem\";";
    $f[]="\t# La facon de s'identifier. Racoon prendra le ASN.1 distinguished name dans le certificat";
    $f[]="\tmy_identifier asn1dn;";
    $f[]="\t# Pour que le concentrateur soit \"maitre\" sur les paramètres de la phase 1";
    $f[]="\tproposal_check claim;";
    $f[]="\t# Racoon génère les \"routes\" pour savoir quels paquets doivent passer par le VPN";
    $f[]="\t# On ne peut pas les définir d'avance car en roadwarrior, on ne connait pas l'IP source";
    $f[]="\tgenerate_policy on;";
    $f[]="\t# On active le NAT traversal avec détection automatique";
    $f[]="\tnat_traversal on;";
    $f[]="\t# Le delai pour la détection de defaillance de l'hôte distant";
    $f[]="\tdpd_delay 20;";
    $f[]="\t# workaround pour les firewalls qui posent problème avec les paquets IKE fragmentés.";
    $f[]="\tike_frag on;";
    $f[]="\t# Proposition pour l'encryption et l'authentification";
    $f[]="\tproposal {";
    $f[]="\t\t# Encryptage en AES";
    $f[]="\t\tencryption_algorithm aes;";
    $f[]="\t\t# hashage en SHA1";
    $f[]="\t\thash_algorithm sha1;";
    $f[]="\t\t# le type d'authentification";
    $f[]="\t\tauthentication_method hybrid_rsa_server;";
    $f[]="\t\t# le groupe Diffie-Hellman utilisé";
    $f[]="\t\tdh_group 2;";
    $f[]="\t}";
    $f[]="}";
    $f[]="";
    $f[]="# Configuration du mode_cfg";
    $f[]="mode_cfg {";
    $f[]="\t# Configuration du pool d'adresse IP";
    $f[]="\t# La première addresse allouable";
    $f[]="\tnetwork4 $RacconFirstIP;";
    $f[]="\t# La nombre d'adresse et donc le nombre de clients simultanés maximum";
    $f[]="\tpool_size $RacconMAXIP;";
    $f[]="\t# le netmask du réseau";
    $f[]="\tnetmask4 $RacconClientNetMask;";
    $f[]="\t# Le DNS que les clients doivent utiliser";
    if($RacconClientDNS<>null) {
        $f[] = "\tdns4 $RacconClientDNS;";
    }
    $f[]="\t# La source pour authentifier les utilisateurs";
    $f[]="\tauth_source ldap;";
    $f[]="\t# La banière que les clients recoivent à la connexion";
    $f[]="\tbanner \"/etc/racoon/motd\";";
    $f[]="\t# PFS Group pour le client Cisco VPN";
    $f[]="\tpfs_group 2;";
    $f[]="}";
    $f[]="";
    $f[]="# Spécifications pour le SA \"anonymous\"";
    $f[]="# Comme précedement, en roadwarrior on connait pas la source";
    $f[]="sainfo anonymous {";
    $f[]="        # le groupe Diffie-Hellman utilisé";
    $f[]="        pfs_group 2;";
    $f[]="        # Durée de vie d'une association";
    $f[]="        lifetime time 1 hour;";
    $f[]="        # Encryption en AES";
    $f[]="        encryption_algorithm aes;";
    $f[]="        # Authentification par SHA1";
    $f[]="        authentication_algorithm hmac_sha1;";
    $f[]="        # Compression activée";
    $f[]="        compression_algorithm deflate;";
    $f[]="}";
$ldap=new clladp();
    $f[]="ldapcfg {";
    $f[]="\tversion 3;";
    $f[]="\thost \"127.0.0.1\";";
    $f[]="\tport 389;";
    $f[]="\tbase \"$ldap->suffix\";";
    $f[]="\tsubtree on;";
    $f[]="\tbind_dn \"cn=$ldap->ldap_admin,$ldap->suffix\";";
    $f[]="\tbind_pw \"$ldap->ldap_password\";";
    $f[]="\tattr_user \"uid\";";
    $f[]="}";

    @file_put_contents("/etc/racoon/racoon.conf",@implode("\n",$f));
    echo "Starting......: ".date("H:i:s")." [INIT]: /etc/racoon/racoon.conf done...\n";
}

function install_monit(){
    $f=array();
    $f[]="check process APP_RACOON";
    $f[]="with pidfile /var/run/racoon.pid";
    $f[]="start program = \"/etc/init.d/racoon start\"";
    $f[]="stop program =  \"/etc/init.d/racoon stop\"";
    $f[]="if 5 restarts within 5 cycles then timeout";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_RACOON.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function etc_default_racoon(){
    $f[]="# Defaults for racoon initscript";
    $f[]="# sourced by /etc/init.d/racoon";
    $f[]="# installed at /etc/default/racoon by the maintainer scripts";
    $f[]=" ";
    $f[]="#";
    $f[]="# This is a POSIX shell fragment";
    $f[]="#";
    $f[]=" ";
    $f[]="# Which configuration mode shall we use for racoon?";
    $f[]="#       Should be either \"direct\" (edit racoon.conf by hand)";
    $f[]="#       or \"racoon-tool\" (use this tool to do it).";
    $f[]="#       Unknown values are treated as if \"direct\" was given.";
    $f[]="CONFIG_MODE=\"direct\"";
    $f[]="# Arguments to pass to racoon (ignored when config mode is racoon-tool)";
    $f[]="RACOON_ARGS=\"\"";

    @file_put_contents("/etc/default/racoon",@implode("\n",$f));
}

function install_service_setkey(){


    $INITD_PATH="/etc/init.d/setkey";

    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          setkey";
    $f[]="# Required-Start:    \$remote_fs";
    $f[]="# Required-Stop:     \$remote_fs";
    $f[]="# Default-Start:     S";
    $f[]="# Default-Stop:";
    $f[]="# Short-Description: option to manually manipulate the IPsec SA/SP database ";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
    $f[]="SETKEY=/usr/sbin/setkey";
    $f[]="SETKEY_CONF=/etc/ipsec-tools.conf";
    $f[]="SETKEY_CONF_DIR=/etc/ipsec-tools.d";
    $f[]="NAME=setkey";
    $f[]="";
    $f[]="";
    $f[]="RUN_SETKEY=\"yes\"";
    $f[]="if [ -f /etc/default/setkey ] ; then";
    $f[]="	. /etc/default/setkey";
    $f[]="fi";
    $f[]="";
    $f[]="test -x \$SETKEY -a -f \$SETKEY_CONF || exit 0";
    $f[]="";
    $f[]="if [ \$RUN_SETKEY != \"yes\" ] ; then";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="set -e";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";
    $f[]="	log_begin_msg \"Loading IPsec SA/SP database: \"";
    $f[]="	err=0";
    $f[]="	for file in \$SETKEY_CONF \$SETKEY_CONF_DIR/*.conf ; do";
    $f[]="	if [ -r \"\$file\" ] ; then";
    $f[]="	# Insert a manual newline until lsb-base amends its code.";
    $f[]="	echo";
    $f[]="	log_progress_msg \" - \${file}\"";
    $f[]="	\$SETKEY -f \$file || err=1";
    $f[]="	fi";
    $f[]="	done";
    $f[]="	log_end_msg \$err";
    $f[]="	;;";
    $f[]="  stop)";
    $f[]="	log_begin_msg \"Flushing IPsec SA/SP database: \"";
    $f[]="";
    $f[]="	err=0";
    $f[]="	\$SETKEY -F || err=1";
    $f[]="	\$SETKEY -FP || err=1";
    $f[]="	log_end_msg \$err";
    $f[]="	;;";
    $f[]="  restart|force-reload)";
    $f[]="	\$0 stop";
    $f[]="	\$0 start";
    $f[]="	echo \"done.\"";
    $f[]="	;;";
    $f[]="  *)";
    $f[]="	N=/etc/init.d/\$NAME";
    $f[]="	log_success_msg \"Usage: \$N {start|stop|restart|force-reload}\" >&2";
    $f[]="	exit 1";
    $f[]="	;;";
    $f[]="esac";
    $f[]="";
    $f[]="exit 0";
    $f[]="";

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}

    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
}

function install_service_racoon(){

    $INITD_PATH="/etc/init.d/racoon";


    $f[]="#! /bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          racoon";
    $f[]="# Required-Start:    \$remote_fs setkey";
    $f[]="# Required-Stop:";
    $f[]="# Should-Start:	     \$portmap";
    $f[]="# Should-Stop:	     \$portmap";
    $f[]="# Default-Start:     S";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# X-Stop-After:	     sendsigs";
    $f[]="# Short-Description: start the ipsec key exchange server ";
    $f[]="### END INIT INFO";
    $f[]="#";
    $f[]="#		Written by Miquel van Smoorenburg <miquels@cistron.nl>.";
    $f[]="#		Modified for Debian GNU/Linux";
    $f[]="#		by Ian Murdock <imurdock@gnu.ai.mit.edu>.";
    $f[]="#		Modified from /etc/init.d/skeleton";
    $f[]="#		by Matthew Grant <grantma@anathoth.gen.nz>";
    $f[]="#";
    $f[]="";
    $f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
    $f[]="TOOL=/usr/sbin/racoon-tool";
    $f[]="DAEMON=/usr/sbin/racoon";
    $f[]="NAME=racoon";
    $f[]="DESC=\"IKE (ISAKMP/Oakley) server\"";
    $f[]="DEF_CFG=\"/etc/default/racoon\"";
    $f[]="PID_FILE=\"/var/run/racoon.pid\"";
    $f[]="PROC_FILE=\"/proc/net/pfkey\"";
    $f[]="";
    $f[]="test -f \$TOOL || exit 0";
    $f[]="test -f \$DAEMON || exit 0";
    $f[]="";
    $f[]="CONFIG_MODE=\"direct\"";
    $f[]="RACOON_ARGS=\"\"";
    $f[]="";
    $f[]="[ -f \"\$DEF_CFG\" ] && . \$DEF_CFG";
    $f[]="";
    $f[]="if [ ! -d /var/run/racoon ]; then";
    $f[]="	mkdir -p /var/run/racoon";
    $f[]="fi";
    $f[]="";
    $f[]="check_kernel () {";
    $f[]="	local MOD_DIR=/lib/modules/`uname -r`";
    $f[]="	local FOUT";
    $f[]="";
    $f[]="	[ -f \"\$PROC_FILE\" ] && return 0";
    $f[]="	[ ! -d \"\$MOD_DIR\" ] && return 1";
    $f[]="	FOUT=`find \$MOD_DIR -name \"*af_key*\"`";
    $f[]="	[ -z \"\$FOUT\" ] && return 1";
    $f[]="	return 0";
    $f[]="}";
    $f[]="";
    $f[]="if [ \"\$(uname -s)\" = \"Linux\" ] && ! check_kernel ; then";
    $f[]="        echo \"racoon - IKE keying daemon will not be started as \$PROC_FILE is not\" 1>&2";
    $f[]="        echo \"         available or a suitable 2.6 (or 2.4 with IPSEC backport)\" 1>&2";
    $f[]="        echo \"         kernel with af_key.[k]o module installed.\" 1>&2";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="if [ \"\$(uname -s)\" = \"GNU/kFreeBSD\" ]; then";
    $f[]="	result=0";
    $f[]="	setkey -DP >/dev/null || result=\$?";
    $f[]="	if [ \$result -ne 0 ]; then";
    $f[]="		echo \"racoon - IKE keying daemon will not be started as this kFreeBSD kernel\" 1>&2";
    $f[]="		echo \"is not compiled with support for IPsec.\" 1>&2";
    $f[]="		exit 0;";
    $f[]="	fi";
    $f[]="fi";
    $f[]="";
    $f[]=". /lib/init/vars.sh";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="do_start () {";
    $f[]="        start-stop-daemon --start --quiet --pidfile \$PID_FILE --exec \$DAEMON --test > /dev/null || return 1";
$f[]="	start-stop-daemon --start --quiet --exec \${DAEMON} -- \${RACOON_ARGS} || return 2";
$f[]="}";
$f[]="";
$f[]="do_stop () {";
$f[]="	start-stop-daemon --stop --quiet --retry=TERM/30/KILL/5 --pidfile \$PID_FILE --name \$NAME";
$f[]="	RETVAL=\"\$?\"";
$f[]="	[ \"\$RETVAL\" = 2 ] && return 2";
$f[]="	start-stop-daemon --stop --quiet --oknodo --retry=0/30/KILL/5 --exec \$DAEMON";
$f[]="	[ \"\$?\" = 2 ] && return 2";
$f[]="        rm -f \$PID_FILE /var/run/racoon/racoon.sock";
$f[]="	return \"\$RETVAL\"";
$f[]="}";
$f[]="";
$f[]="";
$f[]="";
$f[]="case  \$CONFIG_MODE in";
$f[]="  racoon-tool)";
$f[]="  # /usr/sbin/racoon-tool command complies with Debian Policy so just do this:";
$f[]="  # NB the following makes lintian happy";
$f[]="	case \"\$1\" in";
$f[]="	  start|stop|reload|force-reload|restart)";
$f[]="		\$TOOL \$*";
$f[]="		;;";
$f[]="	  status)";
$f[]="        	status_of_proc \"\$DAEMON\" \"\$NAME\" && exit 0 || exit \$?";
$f[]="		;;";
$f[]="	  *)";
$f[]="		\$TOOL \$*";
$f[]="		;;";
$f[]="	esac";
$f[]="	;;";
$f[]="  *)";
$f[]="	case \"\$1\" in";
$f[]="        start)";
$f[]="                [ \"\$VERBOSE\" != no ] && log_begin_msg \"Starting \$DESC\" \"\$NAME\"";
$f[]="		do_start";
$f[]="		case \"\$?\" in";
$f[]="			0|1) [ \"\$VERBOSE\" != no ] && log_end_msg 0 ;;";
$f[]="			2) [ \"\$VERBOSE\" != no ] && log_end_msg 1 ;;";
$f[]="		esac";
$f[]="                ;;";
$f[]="         ";
$f[]="	stop)	";
$f[]="		[ \"\$VERBOSE\" != no ] && log_begin_msg \"Stopping \$DESC\" \"\$NAME\"";
$f[]="		do_stop";
$f[]="		case \"\$?\" in";
$f[]="			0|1) [ \"\$VERBOSE\" != no ] && log_end_msg 0 ;;";
$f[]="			2) [ \"\$VERBOSE\" != no ] && log_end_msg 1 ;;";
$f[]="		esac";
$f[]="                ;;";
$f[]="         ";
$f[]="	reload)";
$f[]="                racoonctl reload-config";
$f[]="	        ;;";
$f[]="        ";
$f[]="	status)";
$f[]="        	status_of_proc \"\$DAEMON\" \"\$NAME\" && exit 0 || exit \$?";
$f[]="        	;;";
$f[]="";
$f[]="	restart|force-reload)";
$f[]="		log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
$f[]="		do_stop";
$f[]="		case \"\$?\" in";
$f[]="			0|1)";
$f[]="				do_start";
$f[]="                		case \"\$?\" in";
$f[]="                        		0) log_end_msg 0 ;;";
$f[]="                        		1) log_end_msg 1 ;; # Old process is still running";
$f[]="                        		*) log_end_msg 1 ;; # Failed to start";
$f[]="                		esac";
$f[]="			;;";
$f[]="		*)";
$f[]="			log_end_msg 1";
$f[]="			;;";
$f[]="		esac";
$f[]="		;;";
$f[]="        *)";
$f[]="                log_success_msg \"Usage: \$0 {start|stop|status|reload|force-reload|restart}\" >&2";
$f[]="	        exit 1";
$f[]="	esac";
$f[]="	;;";
$f[]="esac";
$f[]="";
$f[]="exit 0";

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}

    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

}