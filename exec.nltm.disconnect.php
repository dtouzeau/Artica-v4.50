<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.privileges.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

LeaveDomain();

function build_progress($pourc,$text){
    echo "({$pourc}%): $text\n";
    $filename=PROGRESS_DIR."/ntlm.join.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($filename, serialize($array));
    @chmod($filename,0755);
}

function LeaveDomain(){
    $unix=new unix();
    build_progress(10,"{disconnecting}");
    net_ads_leave();
    build_progress(20,"{disconnecting}");
    $kdestroy=$unix->find_program("kdestroy");
    system("$kdestroy");
    build_progress(50,"{disconnecting}");
    uninstall_winbind_service();
    build_progress(60,"{disconnecting}");
    if(is_file("/etc/monit/conf.d/APP_WINBINDD.monitrc")){
        @unlink("/etc/monit/conf.d/APP_WINBINDD.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

    }
    if(is_file("/etc/monit/conf.d/ACTIVE_DIRECTORY_LINK.monitrc")) {
        @unlink("/etc/monit/conf.d/ACTIVE_DIRECTORY_LINK.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    }
    if(is_file("/etc/krb5.keytab")){
        @unlink("/etc/krb5.keytab");
    }
    if(is_file("/etc/init.d/k5start")){
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -uninstall-k5start");
    }

    build_progress(70,"{disconnecting}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbNTLM", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK1",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK2",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK3",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK4",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK5",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK6",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETADS_CHECK7",1);

    if(is_file("/etc/cron.d/ntlm-changetrustpw")){
        @unlink("/etc/cron.d/ntlm-changetrustpw");
        shell_exec("/etc/init.d/cron reload");
    }

    if(is_file("/etc/cron.d/monit-ntlm")){
        @unlink("/etc/cron.d/monit-ntlm");
        shell_exec("/etc/init.d/cron reload");
    }


    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $array["CONFIG_SAVED"]=0;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",base64_encode(serialize($array)));

    if(is_file("/etc/init.d/squid")){
        if(isSquidAuthenticated()){
            build_progress(80, "{APP_SQUID} {reconfigure}...");
            reconfigure_squid();
        }
    }
    write_ntlm_admin_mysql(2,"Success to leave the Active Directory server",null,__FUNCTION__,__LINE__);
    build_progress(100, "{disconnecting} {success}...");

}
function write_ntlm_admin_mysql($severity,$subject,$content=null,$function=null,$line=0){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $zdate=time();
    $file=basename(__FILE__);
    $unix=new unix();

    $content=str_replace("'", "`", $content);
    $subject=str_replace("'", "`", $subject);

    $q->QUERY_SQL("INSERT OR IGNORE INTO `ntlm_admin_mysql` 
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) 
			VALUES ('$zdate','$content','$subject','$function','$file','$line','$severity')");
    if(!$q->ok){$unix->_syslog($q->mysql_error,basename(__FILE__));}
}

function uninstall_winbind_service(){
    remove_service("/etc/init.d/winbind");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbNTLM", 0);


    if(is_file("/etc/monit/conf.d/APP_WINBINDD.monitrc")){
        @unlink("/etc/monit/conf.d/APP_WINBINDD.monitrc");
    }

    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    write_ntlm_admin_mysql(2,"Success to remove Winbind service",null,__FUNCTION__,__LINE__);

}
function isSquidAuthenticated(){

    $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));
    foreach ($f as $line){

        if(preg_match("#acl.*?AUTHENTICATED.*?REQUIRED#",$line)){return true;}
    }
    return false;
}

function reconfigure_squid(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --auth");
    if(isSquidAuthenticated()){
        write_ntlm_admin_mysql(2,"Success to remove Authentication service from the proxy",
            null,__FUNCTION__,__LINE__);
        return true;
    }
}


function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

    }

    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function ping_dc(){
    $unix=new unix();
    $wbinfo=$unix->find_program("wbinfo");
    exec("$wbinfo --ping-dc 2>&1",$results);

    foreach ($results as $line){
        if(preg_match("#connection to\s+(.+?)\s+succeeded#",$line,$re)){
            echo "ping_dc: Joined {$re[1]} SUCCESS!\n";
            return true;
        }
        echo $line."\n";
    }
}

function net_ads_leave(){
    $func=__FUNCTION__;
    $unix=new unix();
    $netbin=$unix->LOCATE_NET_BIN_PATH();
    if(!ping_dc()){return true;}
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $COMPUTER_BRANCH=$array["COMPUTER_BRANCH"];
    $COMPUTER_SLASH=LdapToSlash($COMPUTER_BRANCH);
    $adminpassword=$array["WINDOWS_SERVER_PASS"];
    $adminpassword=$unix->shellEscapeChars($adminpassword);

    $adminname=$array["WINDOWS_SERVER_ADMIN"];

    $NETADS_BRANCH=" createcomputer=\"$COMPUTER_SLASH\"";

    $cmd="$netbin ads leave -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    exec($cmd,$A2);

    foreach ($A2 as $line){
        echo "$func: $line\n";

    }
    return true;

}
function LdapToSlash($branch){
    $tt=explode(",",trim($branch));
    foreach ($tt as $index=>$field){
        if(preg_match("#^(.*?)=(.+)#", trim($field),$re)){
            $TT2[]=trim($re[2]);
        }
    }

    krsort($TT2);
    return @implode("/", $TT2);
}
function WINBIND_PID(){
    $pidfile="/var/run/samba/winbindd.pid";
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    if(!$unix->process_exists($pid)){
        $winbindbin=$unix->find_program("winbindd");
        $pid=$unix->PIDOF($winbindbin);
    }
    return $pid;
}

function install_winbind_monit(){

    $pidfile="/var/run/samba/winbindd.pid";
    $f[]="check process winbindd with pidfile $pidfile";
    $f[]="  start program = \"/etc/init.d/winbind start\"";
    $f[]="  stop  program = \"/etc/init.d/winbind stop\"";
    $f[]="  if failed host localhost port 139 type TCP then restart";
    $f[]="  if 5 restarts within 5 cycles then timeout";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_WINBINDD.monitrc",@implode("\n",$f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}

function krb5_kinit(){
    $func=__FUNCTION__;
    $unix=new unix();
    $kinit=$unix->find_program("kinit");
    $klist=$unix->find_program("klist");
    $echo=$unix->find_program("echo");
    $function=__FUNCTION__;
    if(!is_file($kinit)){echo2("Unable to stat kinit");return false;}
    exec("$klist 2>&1",$res);

    foreach ($res as $a){
        if(preg_match("#Default principal:\s+(.+)#",$a,$re)){
            echo "$func: Already done as {$re[1]}\n";
            return true;
        }
    }


    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $Username=$array["WINDOWS_SERVER_ADMIN"];
    $Password=$array["WINDOWS_SERVER_PASS"];

    $PasswordEscaped=$unix->shellEscapeChars($Password);
    $cmd="$echo $PasswordEscaped|$kinit {$Username} 2>&1";
    if($GLOBALS["VERBOSE"]){
        echo "$func: $cmd\n";
    }
    exec($cmd,$res);

    foreach ($res as $num=>$a){
        if(preg_match("#Password for#",$a,$re)){unset($res[$num]);}

        echo "$func: $a\n";
        if(preg_match("#Clock skew too great while#", $a)){
            echo "           * * * * * * * * * * * * * * * * * * *\n";
            echo "           * *                               * *\n";
            echo "           * * Please check the system clock ! *\n";
            echo "           * *   Time differ with the AD     * *\n";
            echo "           * *                               * *\n";
            echo "           * * * * * * * * * * * * * * * * * * *\n";
            return false;
        }

    }

    $res=array();
    exec("$klist 2>&1",$res);
    foreach ($res as $num=>$a){
        echo "$func: $a\n";
        if(preg_match("#Default principal:\s+(.+)#",$a,$re)){
            echo "$func: Done as {$re[1]}\n";
            return true;
        }
    }

}
function sync_time(){
    if(isset($GLOBALS[__FUNCTION__])){return;}
    $unix=new unix();
    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    if($NtpdateAD==0){return true;}
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
    $ipaddr=trim($array["ADNETIPADDR"]);
    $ntpdate=$unix->find_program("ntpdate");
    $hwclock=$unix->find_program("hwclock");
    if(!is_file($ntpdate)){
        echo "ntpdate, no such binary\n";
        return false;
    }

    if($ipaddr<>null){$cmd="$ntpdate -s -4 -v -u $ipaddr";}else{$cmd="$ntpdate -s -4 -v -u $hostname";}
    exec($cmd." 2>&1",$results);

    foreach ($results as $a){
        $unix->ToSyslog($a,false,"ntpd");
        echo $a."\n";
    }

    if(is_file($hwclock)){
        shell_exec("$hwclock --systohc");
    }
    return true;
}

function smb_conf(){
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
    $hostname=php_uname("n");
    if(strpos($hostname,".")>0){
        $tb=explode(".",$hostname);
        $netbiosName=$tb[0];
    }else{
        $netbiosName=$hostname;
    }
    $workgroup=$array["ADNETBIOSDOMAIN"];
    $netbiosName=strtoupper($netbiosName);
    $f[]="";
    $f[]="[global]";
    $f[]="   netbios name               = $netbiosName";
    $f[]="   workgroup                  = $workgroup";
    $f[]="   kerberos method            = dedicated keytab";
    $f[]="   dedicated keytab file      = /etc/krb5.keytab";
    $f[]="   realm                      = $domainUp";
    $f[]="   security                   = ads";
    $f[]="   winbind enum groups        = No";
    $f[]="   winbind enum users         = No";
    $f[]="   idmap config * : backend   = tdb";
    $f[]="   idmap config * : range     = 3000-7999";
    $f[]="   idmap config $workgroup:backend = ad";
    $f[]="   idmap config $workgroup:schema_mode = rfc2307";
    $f[]="   idmap config $workgroup:range = 10000-999999";
    $f[]="   idmap config $workgroup:unix_nss_info = yes";
    //$f[]="   map untrusted to domain    = Yes";
    $f[]="   client ntlmv2 auth         = Yes";
    $f[]="   client lanman auth         = No";
    $f[]="   client ldap sasl wrapping  = sign";
    $f[]="   winbind normalize names    = No";
    $f[]="   winbind separator          = /";
    $f[]="   winbind use default domain = yes";
    $f[]="   winbind nested groups      = Yes";
    $f[]="   winbind reconnect delay    = 30";
    $f[]="   winbind offline logon      = true";
    $f[]="   winbind cache time         = 1800";
    $f[]="   winbind refresh tickets    = true";
    $f[]="   winbind max clients        = 500";
    $f[]="   allow trusted domains      = Yes";
    $f[]="   server signing             = auto";
    $f[]="   client signing             = auto";
    $f[]="   lm announce                = No";
    $f[]="   ntlm auth                  = No";
    $f[]="   lanman auth                = No";
    $f[]="   preferred master           = No";
    $f[]="   local master               = No";
    $f[]="   wins support               = No";
    $f[]="   encrypt passwords          = yes";
    $f[]="   printing                   = bsd";
    $f[]="   load printers              = no";
    $f[]="   socket options             = TCP_NODELAY SO_RCVBUF=8192 SO_SNDBUF=8192";
    $f[]="   min protocol               = SMB2";
    $f[]="   load printers              = no";
    $f[]="   printing                   = bsd";
    $f[]="   printcap name              = /dev/null";
    $f[]="   disable spoolss            = yes";
    $f[]="";

    @file_put_contents("/etc/samba/smb.conf",@implode("\n",$f));
}

function krb5_conf(){
    $unix=new unix();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
    if($array["WINDOWS_SERVER_TYPE"]==null){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
    $hostname=strtolower(trim($array["fullhosname"]));
    $domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
    $domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
    $kinitpassword=$array["WINDOWS_SERVER_PASS"];
    $kinitpassword=$unix->shellEscapeChars($kinitpassword);
    $ipaddr=trim($array["ADNETIPADDR"]);

    $uname=posix_uname();
    $mydomain=$uname["domainname"];

    echo "Active Directory server...........: $hostname\n";
    echo "Active Directory Domain...........: $domainUp\n";
    echo "Active Directory Type.............: {$array["WINDOWS_SERVER_TYPE"]}\n";
    echo "Current Domain....................: {$mydomain}\n";

    if($array["WINDOWS_SERVER_TYPE"]=="WIN_2003"){

        $t[]="# For Windows 2003:";
        $t[]=" default_tgs_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
        $t[]=" default_tkt_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
        $t[]=" permitted_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
        $t[]="";

    }

    if($array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
        $t[]="; for Windows 2008 with AES";
        $t[]=" default_tgs_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
        $t[]=" default_tkt_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
        $t[]=" permitted_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
        $t[]="";
        $enctype=" --enctypes 28";

    }

    $dns_lookup_realm="yes";
    $dns_lookup_kdc="yes";
    $default_realm=$domainUp;
    $realms=$domainUp;
    $default_domain=$domainUp;

    $f[]="[logging]";
    $f[]="\tdefault = FILE:/var/log/krb5libs.log";
    $f[]="\tkdc = FILE:/var/log/krb5kdc.log";
    $f[]="\tadmin_server = FILE:/var/log/kadmind.log";
    $f[]="";
    $f[]="[libdefaults]";
    $f[]="\tdefault_keytab_name = /etc/squid3/krb5.keytab";
    $f[]="\tdefault_realm = $default_realm";
    $f[]="\tdns_lookup_realm = $dns_lookup_realm";
    $f[]="\tdns_lookup_kdc = $dns_lookup_kdc";
    $f[]="\tallow_weak_crypto = true";
    $f[]="\tticket_lifetime = 24h";
    $f[]="\tforwardable = true";
    $f[]="\tproxiable = true";
    $f[]="\tfcc-mit-ticketflags = true";
    $f[]="\tccache_type = 4";


    $conf[]="\tdefault_ccache_name = FILE:/etc/kerberos/tickets/krb5cc_%{euid}";

    $f[]="";
    if( count($t)>0){
        $f[]=@implode("\n", $t);
    }


    $IPClass=new IP();


    $f[]="[realms]";
    $f[]="\t$realms = {";
    $f[]="\t\tkdc = $hostname:88";
    if($IPClass->isValid($ipaddr)){
        //$f[]="\t\tkdc=$ipaddr:88";
    }

    if(count($array["Controllers"])>0){
        foreach ($array["Controllers"] as $md5=>$array2){
            $kdc_hostname=$array2["hostname"];
            $kdc_ipaddr=$array2["ipaddr"];
            $UseIPaddr=$array2["UseIPaddr"];
            if($UseIPaddr==1){
                $f[]="\t\tkdc = $kdc_ipaddr:88";
            }else{
                $f[]="\t\tkdc = $kdc_hostname:88";
            }
        }
    }

    $f[]="\t\tadmin_server = $hostname:749";
    if($default_domain<>null){$f[]="\t\tdefault_domain = $domaindow";}
    $f[]="\t}";
    $f[]="";
    $f[]="[domain_realm]";
    $f[]="\t.$domaindow = $domainUp";
    $f[]="\t$domaindow = $domainUp";

    $f[]="";
    $f[]="[appdefaults]";
    $f[]="\tpam = {";
    $f[]="\t\tdebug = false";
    $f[]="\t\tticket_lifetime = 36000";
    $f[]="\t\trenew_lifetime = 36000";
    $f[]="\t\tforwardable = true";
    $f[]="\tkrb4_convert = false";
    $f[]="\t}";
    $f[]="";




    $conf[]="";
    @mkdir("/etc/kerberos/tickets",0755,true);
    @file_put_contents("/etc/krb.conf", @implode("\n", $f));
    @file_put_contents("/etc/krb5.conf", @implode("\n", $f));
    unset($f);
    $f[]="lhs=.ns";
    $f[]="rhs=.$mydomain";
    $f[]="classes=IN,HS";
    @file_put_contents("/etc/hesiod.conf", @implode("\n", $f));

    unset($f);
    $f[]="[libdefaults]";
    $f[]="\t\tdebug = true";
    $f[]="[kdcdefaults]";
    //$f[]="\tv4_mode = nopreauth";
    $f[]="\tkdc_ports = 88,750";
    //$f[]="\tkdc_tcp_ports = 88";
    $f[]="[realms]";
    $f[]="\t$domainUp = {";
    $f[]="\t\tdatabase_name = /etc/krb5kdc/principal";
    $f[]="\t\tacl_file = /etc/kadm.acl";
    $f[]="\t\tdict_file = /usr/share/dict/words";
    $f[]="\t\tadmin_keytab = FILE:/etc/krb5.keytab";
    $f[]="\t\tkey_stash_file = /etc/krb5kdc/.k5.$domainUp";
    $f[]="\t\tmaster_key_type = des3-hmac-sha1";
    $f[]="\t\tsupported_enctypes = des3-hmac-sha1:normal des-cbc-crc:normal des:normal des:v4 des:norealm des:onlyrealm des:afs3";
    $f[]="\t\tdefault_principal_flags = +preauth";
    $f[]="\t}";
    $f[]="";
    if(!is_dir("/usr/share/krb5-kdc")){@mkdir("/usr/share/krb5-kdc",644,true);}
    @file_put_contents("/usr/share/krb5-kdc/kdc.conf", @implode("\n", $f));
    @file_put_contents("/etc/kdc.conf", @implode("\n", $f));

    unset($f);

    $config="*/admin *\n";
    @file_put_contents("/etc/kadm.acl"," ");
    @file_put_contents("/usr/share/krb5-kdc/kadm.acl"," ");
    @file_put_contents("/etc/krb5kdc/kadm5.acl"," ");
    return true;

}

