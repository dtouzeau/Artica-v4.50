<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.privileges.inc');
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if(isset($argv[1])){
    if($argv[1]=="--monit"){
        install_winbind_monit();
        build_monit();
        exit;
    }

    if($argv[1]=="--krb5-renew"){
        krb5_renew();
        exit;
    }


    if($argv[1]=="--winbind-privs"){
        write_ntlm_admin_mysql(1,
            "Execute Winbind files and folders privileges procedure.",
            null,__FUNCTION__,__LINE__);
        winbindd_privileges();
        exit;
    }
    if($argv[1]=="--ntpad"){
        sync_time();
        exit;
    }

    if($argv[1]=="--restart"){
        restart();
        exit;
    }

}

joinDomain();


function restart(){
    build_progress_restart(20, "{APP_SAMBA_WINBIND} (Build Conf)...");
    smb_conf();
    winbindd_privileges();
    shell_exec("/etc/init.d/winbind stop");
    build_progress_restart(40, "{APP_SAMBA_WINBIND} (restart)...");
    shell_exec("/etc/init.d/winbind start");
    build_progress_restart(100, "{APP_SAMBA_WINBIND} {success}");
}

function build_progress_restart($pourc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"ntlm.join.progress");


    return true;
}
function build_progress($pourc,$text){
    echo "({$pourc}%): $text\n";
    $filename=PROGRESS_DIR."/ntlm.join.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($filename, serialize($array));
    @chmod($filename,0755);
}



function joinDomain():bool{
    $unix=new unix();

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        build_progress(110,"{kerberaus_authentication} {failed} {license_error}");
        return false;
    }

    build_progress(10,"{kerberaus_authentication}");
    if(!krb5_conf()){
        build_progress(110,"{kerberaus_authentication} {failed}");
        return false;
    }
    build_progress(15,"{sync_time_ad} {initialize}");
    if(!sync_time()){
        write_ntlm_admin_mysql(0,"{sync_time_ad} {failed}",null,__FUNCTION__,__LINE__);
        build_progress(110,"{sync_time_ad} {failed}");
        return false;
    }

    build_progress(20,"{kerberaus_authentication} {initialize}");
    if(!krb5_kinit()){
        build_progress(110,"{kerberaus_authentication} {failed}");
        return false;
    }

    build_progress(25,"{APP_SAMBA_WINBIND}...");
    smb_conf();
    winbindd_privileges();

    build_progress(35,"{join_activedirectory_domain}...");

    if(!net_ads()){
        build_progress(110,"{join_activedirectory_domain} {failed}");
        return false;
    }

    build_progress(36, "{APP_SAMBA_WINBIND}...");
    install_winbind_service();


    if(!is_file("/etc/init.d/winbind")){
        build_progress(110, "ERR.".__LINE__." {APP_SAMBA_WINBIND} /etc/init.d/winbind no such file...");
        return false;
    }

    build_progress(35, "{APP_SAMBA_WINBIND}...");
    winbindd_privileges();
    $WINBIND_PID=WINBIND_PID();



    if(!$unix->process_exists($WINBIND_PID)){
        shell_exec("/etc/init.d/winbind start");
        build_progress(35, "{APP_SAMBA_WINBIND} (start)...");
    }else{
        shell_exec("/etc/init.d/winbind stop");
        build_progress(40, "{APP_SAMBA_WINBIND} (restart)...");
        shell_exec("/etc/init.d/winbind start");
    }

    if(!ping_dc()){
        uninstall_winbind_service();
        build_progress(110, "{APP_SAMBA_WINBIND} {failed}...");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbNTLM", 1);

    if(is_file("/etc/init.d/squid")){
        if(!isSquidAuthenticated()){
            build_progress(50, "{APP_SQUID} {reconfigure}...");
            if(!reconfigure_squid()){
                build_progress(50, "{APP_SQUID} {reconfigure} {failed}...");
            }
        }
    }
    write_ntlm_admin_mysql(2,"Active directory Success to establish NTLM link",null,__FUNCTION__,__LINE__);


    $potential[]="/etc/squid3/krb5.keytab";
    $potential[]="/etc/squid3/krb5.keytab";
    $potential[]="/etc/krb5.keytab";
    foreach ($potential as $keytab){
        if(is_file($keytab)){
            @unlink($keytab);
        }
    }

    if(is_file("/etc/init.d/k5start")){
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -uninstall-k5start");
    }




    build_progress(100, "{join_activedirectory_domain} {success}...");
    return true;
}



function uninstall_winbind_service(){
    remove_service("/etc/init.d/winbind");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbNTLM", 0);

    if(is_file("/etc/monit/conf.d/APP_WINBINDD.monitrc")){
        @unlink("/etc/monit/conf.d/APP_WINBINDD.monitrc");
    }

    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

}

function write_ntlm_admin_mysql($severity,$subject,$content,$function,$line){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $content=str_replace("'", "`", $content);
    $subject=str_replace("'", "`", $subject);
    $zdate=time();
    $file=basename(__FILE__);
    $unix=new unix();
    $q->QUERY_SQL("INSERT OR IGNORE INTO `ntlm_admin_mysql` 
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) 
			VALUES ('$zdate','$content','$subject','$function','$file','$line','$severity')");
    if(!$q->ok){$unix->_syslog($q->mysql_error,basename(__FILE__));}
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
        return true;
    }
}


function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    echo "Removing $INITD_PATH\n";
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

    if(!is_file("/etc/init.d/winbind")){
        echo "/etc/init.d/winbind no such file\n";
        return false;
    }

    exec("$wbinfo --ping-dc 2>&1",$results);

    foreach ($results as $line){

        if(preg_match("#WBC_ERR_WINBIND_NOT_AVAILABLE#",$line,$re)){
            if(!isset($GLOBALS["PINGDC_COUNT"])){
                $GLOBALS["PINGDC_COUNT"]=1;
            }
            echo "ping_dc: WBC_ERR_WINBIND_NOT_AVAILABLE ({$GLOBALS["PINGDC_COUNT"]})\n";
            shell_exec("/etc/init.d/winbind start");
            sleep(1);
            $GLOBALS["PINGDC_COUNT"]++;
            if($GLOBALS["PINGDC_COUNT"]<6) {
                return ping_dc();
            }

        }

        if(preg_match("#connection to\s+(.+?)\s+succeeded#",$line,$re)){
            echo "ping_dc: Joined {$re[1]} SUCCESS!\n";
            return true;
        }
        echo $line."\n";
    }
}

function net_ads(){
    $func=__FUNCTION__;
    $unix=new unix();
    $netbin=$unix->LOCATE_NET_BIN_PATH();
    if(ping_dc()){return true;}
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $COMPUTER_BRANCH=$array["COMPUTER_BRANCH"];
    $COMPUTER_SLASH=LdapToSlash($COMPUTER_BRANCH);
    $adminpassword=$array["WINDOWS_SERVER_PASS"];
    $adminpassword=$unix->shellEscapeChars($adminpassword);
    $GLOBALS["ADMIN_PASS_FOR_LOGS"]=$adminpassword;
    $adminname=$array["WINDOWS_SERVER_ADMIN"];
    $hostname=strtoupper(trim($array["fullhosname"]));
    $NETADS_BRANCH=null;
    $HOTSS=null;
    $HOTSSS=null;

    if(strlen($hostname)>2){
        $HOTSS=" -S $hostname";
    }

    if(isset($array["ADNETIPADDR"])){
        $IP=new IP();
        if($IP->isValid($array["ADNETIPADDR"])){
            $HOTSSS=" -I {$array["ADNETIPADDR"]}";
        }
    }


    echo "Computer Branch.........: $COMPUTER_BRANCH\n";
    if(strtolower($COMPUTER_BRANCH)<>"cn=computers") {
        $NETADS_BRANCH = " createcomputer=\"$COMPUTER_SLASH\"";
    }

    $cmd="$netbin ads join$HOTSS$HOTSSS -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    exec($cmd,$A2);

    foreach ($A2 as $line){
        echo "$func: $line\n";
        if(preg_match("#Joined#i", $line)) {
            $NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
            $KDC_SERVER=$NetADSINFOS["KDC server"];
            if($KDC_SERVER==null){
                echo "[$adminname]: unable to join the domain (KDC server is null)\n";
                return false;
            }
            write_ntlm_admin_mysql(2,"Active directory Success to connect to AD",
                @implode("\n", $A2),__FUNCTION__,__LINE__);
            return true;

        }


        if(preg_match("#Unable to find a suitable server for domain#i", $line)){
            write_ntlm_admin_mysql(0,"Active directory Unable to find a suitable server for domain [action: None]",@implode("\n", $A2),__FUNCTION__,__LINE__);
            return false;
        }
        if(!isset($GLOBALS[__FUNCTION__."-restart"])){
            if(preg_match("#Invalid configuration.*?workgroup.*?set to\s+'(.+?)', should be '(.+?)'.*?and#",           $line,$re)) {
                echo "RECONFIGURE PARAMETERS TO MATCHES {$re[2]} INSTEAD OF {$re[1]}\n";
                $array["ADNETBIOSDOMAIN"] = $re[1];
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",
                    base64_encode(serialize($array)));
                smb_conf();
                $GLOBALS[__FUNCTION__ . "-restart"] = true;
                return net_ads();
            }
        }
        if(preg_match("#Clock skew too great#",$line)){
            echo "* * * * * Clock skew too great * * * * *\n";
            return false;
        }
    }


}
function LdapToSlash($branch){
    $tt=explode(",",trim($branch));
    foreach ($tt as $field){
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






function krb5_kinit(){
    return true;
    $func=__FUNCTION__;
    $unix=new unix();
    $kinit=$unix->find_program("kinit");
    $klist=$unix->find_program("klist");
    $echo=$unix->find_program("echo");
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
            write_ntlm_admin_mysql(0,"Failed to kinit",@implode("\n",$a),__FUNCTION__,__LINE__);
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
            write_ntlm_admin_mysql(2,"Success to kinit",@implode("\n",$a),__FUNCTION__,__LINE__);
            return true;
        }
    }

    write_ntlm_admin_mysql(0,"Failed to kinit",@implode("\n",$a),__FUNCTION__,__LINE__);

}
function sync_time():bool{
    if(isset($GLOBALS[__FUNCTION__])){return true;}
    $unix=new unix();
    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    if($NtpdateAD==0){return true;}
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
    $ipaddr=trim($array["ADNETIPADDR"]);
    $ntpdate=$unix->find_program("ntpdate");
    $hwclock=$unix->find_program("hwclock");
    $mv=$unix->find_program("mv");
    if(!is_file($ntpdate)){
        echo "ntpdate, no such binary\n";
        return false;
    }


    if($ipaddr<>null){$cmd="$ntpdate -s -4 -v -u $ipaddr";}else{$cmd="$ntpdate -s -4 -v -u $hostname";}
    $unix->ToSyslog("Running $cmd",false,"ntpd");

    if(is_file("/etc/ntp.conf")) {shell_exec("$mv /etc/ntp.conf /etc/ntp.conf.bak");}
    exec($cmd." 2>&1",$results);
    if(is_file("/etc/ntp.conf.bak")) {shell_exec("$mv /etc/ntp.conf.bak /etc/ntp.conf");}

    foreach ($results as $a){
        $unix->ToSyslog($a,false,"ntpd");
        echo $a."\n";
    }

    if(is_file($hwclock)){
        shell_exec("$hwclock --systohc");
    }
    return true;
}

function smb_conf():bool{
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
    $smb_log_level=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMB_LOG_LEVEL"));

    $ADNETIPADDR=null;
    if(isset($array["ADNETIPADDR"])){
        $ADNETIPADDR=$array["ADNETIPADDR"];
    }

    echo "Active Directory Workgroup........: $workgroup smb_conf::".__LINE__."\n";
    echo "Current Netbios name..............: $hostname/$netbiosName smb_conf::".__LINE__."\n";
    echo "SAMBA Log Level...................: $smb_log_level smb_conf::".__LINE__."\n";
    echo "Active Directory IP address.......: $ADNETIPADDR smb_conf::".__LINE__."\n";

    $f[]="";
    $f[]="[global]";
    $f[]="   netbios name               = $netbiosName";
    $f[]="   log level                  = $smb_log_level";
    $f[]="   workgroup                  = $workgroup";
    $f[]="   kerberos method            = dedicated keytab";
    $f[]="   dedicated keytab file      = /etc/krb5.keytab";
    $f[]="   realm                      = $domainUp";
    if($ADNETIPADDR<>null) {
        $f[] = "   password server            = $ADNETIPADDR";
    }

    $f[]="   security                   = ads";
    $f[]="   winbind enum groups        = No";
    $f[]="   winbind enum users         = No";
    $f[]="   idmap config * : backend   = tdb";
    $f[]="   idmap config * : range     = 3000-7999";
    $f[]="   idmap config $workgroup:backend = ad";
    $f[]="   idmap config $workgroup:schema_mode = rfc2307";
    $f[]="   idmap config $workgroup:range = 10000-999999";
    $f[]="   idmap config $workgroup:unix_nss_info = yes";
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
    return true;
}


function krb5_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"krb5.renew.progress");
}

function krb5_renew():bool{
    
    krb5_progress(15,"{kdestroy}");
    $unix=new unix();
    $kdestroy=$unix->find_program("kdestroy");
    shell_exec($kdestroy);
    krb5_progress(30,"{reconfiguration}");
    krb5_conf();
    krb5_progress(50,"{kerberaus_authentication}");
    if(!krb5_kinit()){
        krb5_progress(110,"{kerberaus_authentication} {failed}");
        return false;
    }
    krb5_progress(100,"{success}");
    return true;
}

function krb5_conf():bool{


    $files[]="/etc/krb.conf";
    $files[]="/etc/krb5.conf";
    $files[]="/etc/hesiod.conf";
    foreach ($files as $filepath){
        if(is_file($filepath)) {
            @unlink($filepath);
        }
    }

    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
    if($array["WINDOWS_SERVER_TYPE"]==null){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
    $hostname=strtolower(trim($array["fullhosname"]));
    $domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
    $domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
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
    $f[]="\tdefault_ccache_name = FILE:/etc/kerberos/tickets/krb5cc_%{euid}";

    $f[]="";
    if( count($t)>0){
        $f[]=@implode("\n", $t);
    }


    $IPClass=new IP();


    $f[]="[realms]";
    $f[]="\t$realms = {";
    if($IPClass->isValid($ipaddr)){
        $f[]="\t\tkdc=$ipaddr:88";
        $f[]="\t\tadmin_server = $ipaddr:749";
    }else{
        $f[]="\t\tadmin_server = $hostname:749";
        $f[]="\t\tkdc = $hostname:88";
    }
    if(!isset($array["Controllers"])){$array["Controllers"]=array();}
    if(!is_array($array["Controllers"])){$array["Controllers"]=array();}

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


    @file_put_contents("/etc/kadm.acl"," ");
    @file_put_contents("/usr/share/krb5-kdc/kadm.acl"," ");
    @file_put_contents("/etc/krb5kdc/kadm5.acl"," ");
    return true;
}

