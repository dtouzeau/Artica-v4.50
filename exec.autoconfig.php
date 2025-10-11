<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
include_once(dirname(__FILE__) . '/ressources/externals/class.aesCrypt.inc');
include_once(dirname(__FILE__) . '/ressources/externals/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/externals/class.resolv.conf.inc');

include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



if($argv[1]=="--download"){download_conf();exit;}
if($argv[1]=="--decrypt"){decrypt_conf($argv[2]);exit;}
if($argv[1]=="--read"){read_conf();exit;}
if($argv[1]=="--reset-dhcp"){reset_dhcpd_conf();exit;}
if($argv[1]=="--network"){apply_networks();exit;}
if($argv[1]=="--adconnect"){adconnect();exit;}
if($argv[1]=="--license"){CheckLicense();exit;}
if($argv[1]=="--ufdb"){checkWebFiltering();exit;}
if($argv[1]=="--db"){ufdb_databases();exit;}

echo "Starting procedure\n";
download_conf();
decrypt_conf();
read_conf();
echo "Application du réseau\n";
apply_networks();
echo "Application de la license\n";
CheckLicense();
echo "Connection Active Directory\n";
adconnect();
echo "Paramétrage du filtrage web\n";
checkWebFiltering();
echo "Fin de la procédure\n";






function download_conf($count=0){
    @unlink("/etc/artica-postfix/MASTER_DISABLED");
    @unlink("/etc/artica-postfix/MASTER_STEP1");
    @unlink("/etc/artica-postfix/MASTER_STEP2");
    @unlink("/etc/artica-postfix/MASTER_STEP3");
    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    $sock=new sockets();
    $unix=new unix();


    $wget=$unix->find_program("wget");
    $MasterDuplicateUri=trim($sock->GET_INFO("MasterDuplicateUri"));
    $TMP_FILE=$unix->FILE_TEMP();
    //si crypted.txt -> alors rm crypted.tar.gz && tar czvf crypted.tar.gz crypted.txt
    $basename=basename($MasterDuplicateUri);
    echo "Try $MasterDuplicateUri.tar.gz\n";

    $dhclient=$unix->find_program("dhclient");
    system("$dhclient -v eth0");
    $cmd="$wget $MasterDuplicateUri.tar.gz -O $TMP_FILE 2>&1";
    echo "$cmd\n";
    shell_exec($cmd);
    $size=filesize($TMP_FILE);
    echo "$MasterDuplicateUri.tar.gz = $size Bytes\n";
    if($size>0){
        $tar=$unix->find_program("tar");
        echo "Extracting $TMP_FILE\n";
        system("$tar -xf $TMP_FILE -C /tmp");
        if(is_file("/tmp/$basename")){
            @unlink("/etc/artica-postfix/MASTER_STEP1");
            @copy("/tmp/$basename","/etc/artica-postfix/MASTER_STEP1");
            return;
        }
    }

    if($size==0){

        $cmd="$wget $MasterDuplicateUri -O $TMP_FILE 2>&1";
        echo "$cmd\n";
        shell_exec($cmd);
        $size=filesize($TMP_FILE);
    }

    echo "Taille URL 1 $size\n";

    if($size==0){
        echo "*******************************************************\n";
        $cmd="$wget $MasterDuplicateUri -O $TMP_FILE 2>&1";
        echo $cmd."\n";
        shell_exec($cmd);


        $size=filesize($TMP_FILE);
        echo "Taille URL 2 $size\n";
        echo __LINE__." :".basename($MasterDuplicateUri)." {$size} Bytes\n";
        if($size==0){
            @unlink($TMP_FILE);
            echo "Retry $count/3\n";
            $count++;
            if($count>3){return;}
            sleep(2);
            download_conf($count);
            return;
        }
    }
    echo "$TMP_FILE ---> /etc/artica-postfix/MASTER_STEP1\n";
    @copy($TMP_FILE,"/etc/artica-postfix/MASTER_STEP1");
    @unlink($TMP_FILE);

}

function decrypt_conf($sourcepath=null){
    $sock = new sockets();
    $unix = new unix();
    $DecryptFile=0;
    $MasterDuplicatePassword = $sock->GET_INFO("MasterDuplicatePassword");
    $fileTodecrypt="/etc/artica-postfix/MASTER_STEP1";
    if($sourcepath<>null) {
        if (is_file($sourcepath)) {
            $fileTodecrypt = $sourcepath;
        }
    }
    echo "Get content of /etc/artica-postfix/MASTER_STEP1\n";
    $data = @file_get_contents($fileTodecrypt);


    if($DecryptFile==1) {
        $crypt = new AESCrypt($MasterDuplicatePassword);
        if ($GLOBALS["VERBOSE"]) {
            echo "Passphrase: $MasterDuplicatePassword\n";
        }
        echo "Decrypt \"$fileTodecrypt\" into \"/etc/artica-postfix/MASTER_STEP2\"\n";
        $decyrpted = $crypt->decrypt($data);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP2", $decyrpted);
    }else{
        echo "Copy \"$fileTodecrypt\" into \"/etc/artica-postfix/MASTER_STEP2\"\n";
        @file_put_contents("/etc/artica-postfix/MASTER_STEP2", $data);
    }

    $ini = new Bs_IniHandler("/etc/artica-postfix/MASTER_STEP2");

    if (!isset($ini->_params["global"])) {
        echo "Decrypting Failed [global] Not found....\n";
        if($DecryptFile==1) {echo $crypt->error . "\n";}
        @file_put_contents("/etc/artica-postfix/MASTER_STEP2_ERROR", "Decrypting Failed [global] Not found\n");
        @unlink("/etc/artica-postfix/MASTER_STEP2");
        die();
    }
    if (!isset($ini->_params["global"]["enable"])) {
        echo "Missing enable parameters...\n";
        @file_put_contents("/etc/artica-postfix/MASTER_STEP2_ERROR", "Missing global/enable parameters");
        @unlink("/etc/artica-postfix/MASTER_STEP2");
        if($DecryptFile==1) {echo $crypt->error . "\n";}
        die();
    }
    if (!isset($ini->_params["global"]["GOLD_KEY"])) {
        echo "Missing Gold Key parameters...\n";
        @file_put_contents("/etc/artica-postfix/MASTER_STEP2_ERROR", "Missing global/GOLD_KEY parameters");
        @unlink("/etc/artica-postfix/MASTER_STEP2");
        if($DecryptFile==1) {echo $crypt->error . "\n";}
        die();
    }


    $enable=intval($ini->_params["global"]["enable"]);
    if($enable==0){
        $sock->SET_INFO("MasterDuplicateMode",0);
        @touch("/etc/artica-postfix/MASTER_DISABLED");
        @unlink("/etc/artica-postfix/MASTER_STEP2");
    }
}


function read_conf(){
    $unix=new unix();
    $ini = new Bs_IniHandler("/etc/artica-postfix/MASTER_STEP2");
    @unlink("/etc/artica-postfix/MASTER_STEP3");
    @unlink("/etc/artica-postfix/MASTER_STEP2_ERROR");
    $ipaddr=null;
    $ifconfig=$unix->find_program("ifconfig");
    $ipbin=$unix->find_program("ip");
    $dhclient=$unix->find_program("dhclient");

    echo "Found My current IP address....\n";

    echo "Reset Network again....\n";
    exec("$dhclient -v eth0 2>&1",$results);

    foreach ($results as $line) {
        echo "Parsing $line\n";
        if(preg_match("#bound to\s+([0-9\.]+)\s+#i",$line,$re)){
            $ipaddr=$re[1];
            if($ipaddr=="127.0.0.1"){continue;}
            break;
        }

    }

    echo "Found my IP address as * * * * [$ipaddr] * * * *\n";

    if($GLOBALS["VERBOSE"]){echo "checking _params[$ipaddr]\n";}
    if(isset($ini->_params[$ipaddr])){
        $serialize=serialize($ini->_params[$ipaddr]);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
        if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on $ipaddr\n";}
        return;
    }

    $zip=explode(".",$ipaddr);
    $subnet=$zip[0].".".$zip[1].".".$zip[2];
    echo "Found the IP address as $subnet\n";

    if(isset($ini->_params[$subnet])){
        if($GLOBALS["VERBOSE"]){echo "checking _params[$subnet]\n";}
        $serialize=serialize($ini->_params[$subnet]);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
        if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on $subnet\n";}
        return;
    }

    exec("$ipbin route 2>&1",$ipres);
    foreach ($ipres as $line){
        if(preg_match("#default via ([0-9\.]+) dev#",$line,$re)){
            $gateway=$re[1];
            echo "Found the gateway address as $gateway\n";
            if(isset($ini->_params[$gateway])){
                $serialize=serialize($ini->_params[$gateway]);
                @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
                if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on $gateway\n";}
                return;
            }
        }

    }


    $hostnamebin=$unix->find_program("hostname");
    $domainnamebin=$unix->find_program("domainname");
    $hostname=exec("$hostnamebin");
    $domainname=exec("$domainnamebin");
    $fqdn="$hostname.$domainname";
    echo "Found my hostname as $fqdn\n";
    if($GLOBALS["VERBOSE"]){echo "checking _params[$fqdn]\n";}
    if(isset($ini->_params[$fqdn])){
        $serialize=serialize($ini->_params[$fqdn]);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
        if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on $fqdn\n";}
        return;
    }
    if($GLOBALS["VERBOSE"]){echo "checking _params[$hostname]\n";}
    if(isset($ini->_params[$hostname])){
        $serialize=serialize($ini->_params[$hostname]);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
        if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on $hostname\n";}
        return;
    }

    if($GLOBALS["VERBOSE"]){echo "checking _params[$domainname]\n";}
    if(isset($ini->_params[$domainname])){
        $serialize=serialize($ini->_params[$domainname]);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
        if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on $domainname\n";}
        return;
    }
    if($GLOBALS["VERBOSE"]){echo "checking _params[*]\n";}
    if(isset($ini->_params["*"])){
        $serialize=serialize($ini->_params["*"]);
        @file_put_contents("/etc/artica-postfix/MASTER_STEP3",$serialize);
        if($GLOBALS["VERBOSE"]){echo "OK MASTER_STEP3 Will be based on *\n";}
        return;
    }


    if($GLOBALS["VERBOSE"]){echo "Error, Could not found any configuration Saving MASTER_STEP2_ERROR[$ipaddr,$subnet,$gateway,$fqdn,$hostname]\n";}
    file_put_contents("/etc/artica-postfix/MASTER_STEP2_ERROR","$ipaddr,$subnet,$gateway,$fqdn,$hostname,$domainname");

}

function apply_networks(){
    $unix=new unix();
    $sock=new sockets();
    $CONFIG=unserialize(@file_get_contents("/etc/artica-postfix/MASTER_STEP3"));

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("DELETE FROM `nics`");


    $nics=new system_nic("eth0",true);
    if(isset($CONFIG["ipaddr"])){
        $nics->IPADDR=$CONFIG["ipaddr"];
        if(isset($CONFIG["netmask"])){ $nics->NETMASK=$CONFIG["netmask"];}
        if(isset($CONFIG["gateway"])){ $nics->GATEWAY=$CONFIG["gateway"];}
        $nics->SaveNic();
    }

    if(isset($CONFIG["dns1"])){
        $resolv=new resolv_conf();
        $resolv->MainArray["DNS1"]=$CONFIG["dns1"];
        if(!isset($CONFIG["dns2"])){$resolv->MainArray["DNS2"]=$CONFIG["dns1"];}
        if(isset($CONFIG["dns2"])){$resolv->MainArray["DNS2"]=$CONFIG["dns2"];}
        if(isset($CONFIG["dns3"])){$resolv->MainArray["DNS3"]=$CONFIG["dns3"];}
        $resolv->save();
    }
    if(isset($CONFIG["hostname"])) {
        $nic = new system_nic();
        $nic->set_hostname($CONFIG["hostname"]);
        $sock->SET_INFO("SquidVisibleHostname",$CONFIG["hostname"]);
        $sock->SET_INFO("SquidUniqueHostname",$CONFIG["hostname"]);
    }

    $php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
    system("/usr/sbin/artica-phpfpm-service -restart-network");

    if(is_file("/etc/init.d/ssh")){
        shell_exec("/usr/sbin/artica-phpfpm-service -configure-ssh");
    }

}


function CheckLicense(){
    @unlink("/etc/artica-postfix/MASTER_LICENSE");
    @unlink("/etc/artica-postfix/MASTER_STEP2_ERROR");
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    if(!is_file("/etc/artica-postfix/MASTER_UUID_CHANGED")) {
        @unlink("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED");
        $uuid = $unix->GetUniqueID();
        $UUID_FIRST = $unix->CREATE_NEW_UUID();
        if ($UUID_FIRST <> $uuid) {
            @file_put_contents("/etc/artica-postfix/MASTER_UUID_CHANGED", time());
        }
    }else{
        $UUID_FIRST=$unix->GetUniqueID();
    }

    $ini = new Bs_IniHandler("/etc/artica-postfix/MASTER_STEP2");
    $GOLD_KEY=$ini->_params["global"]["GOLD_KEY"];
    $sock=new sockets();
    if(!$sock->IsGoldKey($GOLD_KEY)) {@file_put_contents("/etc/artica-postfix/MASTER_STEP2_ERROR","$GOLD_KEY corrupted\n");return;}
    $WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
    $WORKFILE=base64_decode('LmxpYw==');
    $WORKPATH="$WORKDIR/$WORKFILE";
    @file_put_contents($WORKPATH, "TRUE");
    $LicenseInfos=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/LicenseInfos")));
    $LicenseInfos["UUID"]=$UUID_FIRST;
    $LicenseInfos["TIME"]=time();
    $LicenseInfos["GoldKey"]=$GOLD_KEY;
    $sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/server");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/license");
    @file_put_contents("/etc/artica-postfix/MASTER_LICENSE",time());

}


function checkWebFiltering(){
    $unix=new unix();
    $CONFIG=unserialize(@file_get_contents("/etc/artica-postfix/MASTER_STEP3"));
    $EnableWebFiltering=intval($CONFIG["EnableWebFiltering"]);
    if($EnableWebFiltering==0){return;}
    $php5=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php5 /usr/share/artica-postfix/exec.ufdb.enable.php");

    $AdvancedWebFilteringPage=intval($CONFIG["AdvancedWebFilteringPage"]);
    $AdvancedWebFilteringPagePort=intval($CONFIG["AdvancedWebFilteringPagePort"]);
    $AdvancedWebFilteringPageHostname=$CONFIG["AdvancedWebFilteringPageHostname"];

    $ipaddr=$unix->NETWORK_IFNAME_TO_IP("eth0");
    $AdvancedWebFilteringPageHostname=str_ireplace("CurrentIpaddr",$ipaddr,$AdvancedWebFilteringPageHostname);




    ufdb_databases();




}

function adconnect(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $CONFIG=unserialize(@file_get_contents("/etc/artica-postfix/MASTER_STEP3"));
    if(intval($CONFIG["activedirectory"])==0){return;}
    $COMPUTER_BRANCH=null;
    $ADCONFIG["ADNETIPADDR"]=null;
    $adhostname=$CONFIG["adhostname"];
    $adusername=$CONFIG["adusername"];
    $workgroup=$CONFIG["workgroup"];
    $adpassword=$CONFIG["adpassword"];
    $COMPUTER_BRANCH=$CONFIG["computersbranchs"];
    $nativekerberos=intval($CONFIG["nativekerberos"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable", 1);

    if(!is_file("/etc/init.d/squid")){
        echo "Installing Squid-Cache.....\n";
        shell_exec("/usr/sbin/artica-phpfpm-service -install-proxy");
    }





    if(is_file("/etc/init.d/unbound")){
        echo "Reconfiguring DNS CACHE...\n";
        system("/usr/sbin/artica-phpfpm-service -install-unbound");
        system("/usr/sbin/artica-phpfpm-service -restart-unbound");
    }

    echo "Connecting to the Active Directory {$CONFIG["adipaddr"]}\n";



    if(isset($CONFIG["adipaddr"])){$ADCONFIG["ADNETIPADDR"]=$CONFIG["adipaddr"];}
    if($COMPUTER_BRANCH<>null){$ADCONFIG["COMPUTER_BRANCH"]=$COMPUTER_BRANCH;}

    $ADCONFIG["WINDOWS_SERVER_ADMIN"]=$adusername;

    $tb=explode(".",$adhostname);
    $ADCONFIG["WINDOWS_SERVER_NETBIOSNAME"]=$tb[0];
    $ADCONFIG["ADNETBIOSDOMAIN"]=$workgroup;
    $ADCONFIG["WINDOWS_SERVER_PASS"]=$adpassword;


    if(strpos($adusername, "@")>0){
        $trx=explode("@",$ADCONFIG["WINDOWS_SERVER_ADMIN"]);
        $ADCONFIG["WINDOWS_SERVER_ADMIN"]=$trx[0];
        $ADCONFIG["WINDOWS_DNS_SUFFIX"]=trim(strtolower($trx[1]));
    }else{
        $tre=explode(".",$adhostname);
        unset($tre[0]);
        $ADCONFIG["WINDOWS_DNS_SUFFIX"]=@implode(".", $tre);

    }

    if(!isset($ADCONFIG["WINDOWS_DNS_SUFFIX"])){
        $tb=explode(".",$ADCONFIG["fullhosname"]);
        unset($tb[0]);
        $ADCONFIG["WINDOWS_DNS_SUFFIX"]=@implode(".", $tb);
    }
    if($ADCONFIG["ADNETIPADDR"]==null){$ADCONFIG["ADNETIPADDR"]=gethostbyname($adhostname);}

    $ADCONFIG["LDAP_SERVER"]=$ADCONFIG["ADNETIPADDR"];
    $ADCONFIG["LDAP_DN"]=$ADCONFIG["WINDOWS_SERVER_ADMIN"]."@".$ADCONFIG["WINDOWS_DNS_SUFFIX"];
    $ADCONFIG["LDAP_PASSWORD"]=$ADCONFIG["WINDOWS_SERVER_PASS"];
    $ADCONFIG["WINDOWS_SERVER_TYPE"]="WIN_2008AES";
    $ADCONFIG["LDAP_PORT"]=389;


    $dse=new ad_rootdse($ADCONFIG["ADNETIPADDR"], 389, $ADCONFIG["LDAP_DN"], $ADCONFIG["WINDOWS_SERVER_PASS"]);
    $RootDSE=$dse->RootDSE();
    if($RootDSE<>null){
        $ADCONFIG["LDAP_SUFFIX"]=$RootDSE;
    }




    $sock=new sockets();
    $php=$unix->LOCATE_PHP5_BIN();


    $sock->SaveConfigFile(base64_encode(serialize($ADCONFIG)), "KerbAuthInfos");
    $sock->SET_INFO("KerbAuthSMBV2", 1);
    $sock->SET_INFO("EnableKerbAuth", 1);
    $sock->SET_INFO("WindowsActiveDirectoryKerberos", $nativekerberos);
    system("/usr/sbin/artica-phpfpm-service -ad-install");
    system("$php /usr/share/artica-postfix/exec.nltm.connect.php");



}

function ufdb_databases(){

    $KerbAuthInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $LDAP_SERVER=$KerbAuthInfos["LDAP_SERVER"];
    $LDAP_DN=$KerbAuthInfos["LDAP_DN"];
    $LDAP_PASSWORD=$KerbAuthInfos["LDAP_PASSWORD"];
    $LDAP_PORT=$KerbAuthInfos["LDAP_PORT"];
    $LDAP_SUFFIX=$KerbAuthInfos["LDAP_SUFFIX"];


    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT * FROM webfilter_group");
    if(!is_array($results)){return;}
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $localldap=$ligne["localldap"];
        if($localldap<>2){continue;}
        $dn=$ligne["dn"];
        $settings=unserialize(base64_decode($ligne["settings"]));
        if(!preg_match("#^(.+?),DC=#i",$dn,$re)){
            echo "Unable to check prefix in $dn\n";
            continue;
        }
        $prefix=$re[1];
        $newdn="$prefix,$LDAP_SUFFIX";
        $settings["dn"]=$newdn;
        $settings["LDAP_SERVER"]=$LDAP_SERVER;
        $settings["LDAP_PORT"]=$LDAP_PORT;
        $settings["LDAP_SUFFIX"]=$LDAP_SUFFIX;
        $settings["LDAP_DN"]=$LDAP_DN;
        $settings["LDAP_PASSWORD"]=$LDAP_PASSWORD;

        $new_settings=base64_encode(serialize($settings));
        echo "Updated Active Directory $newdn for group $ID for $LDAP_SERVER:$LDAP_PORT ($LDAP_DN)\n";
        $q->QUERY_SQL("UPDATE webfilter_group SET dn='$newdn',settings='$new_settings' WHERE ID=$ID");
        if(!$q->ok){echo $q->mysql_error."\n";}
    }



}

function reset_dhcpd_conf(){
    $unix=new unix();
    $ifconfig=$unix->find_program("ifconfig");
    $dhclient=$unix->find_program("dhclient");
    $rm=$unix->find_program("rm");
    @unlink("/etc/artica-postfix/settings/Daemons/Msftncsi.eth0");
    @unlink("/etc/artica-postfix/settings/Daemons/ETHCONFIG-eth0");
    @unlink("/etc/artica-postfix/settings/Daemons/NET_IPADDRS");
    @unlink("/etc/artica-postfix/settings/Daemons/ifconfig_all_ips");
    @unlink("/etc/artica-postfix/settings/Daemons/ADNETIPADDR");
    shell_exec("$ifconfig eth0 down");
    shell_exec("$dhclient -r -v eth0");
    shell_exec("$rm /var/lib/dhcp/dhclient.*");
    shell_exec("$dhclient -v eth0");
}
?>
