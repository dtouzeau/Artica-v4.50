<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.certificate.inc');
$GLOBALS["server-conf"]=false;
$GLOBALS["IPTABLES_ETH"]=null;

if($argv[1]=="--server"){build_server();exit;}
if($argv[1]=="--cert"){build_certificate($argv[2]);exit;}

xrun($argv[1]);


function xrun($commonname){

	if($commonname=="OpenVPN-MASTER"){build_server();exit;}
	if(isset($_GET["site-id"])){$site_id=$_GET["site-id"];}
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$zipbin=$unix->find_program("zip");
	if(!is_file($zipbin)){$zipbin="/usr/bin/zip";}
	if(!is_executable($zipbin)){
		build_progress(110,"{failed}");
		echo "ERROR: unable to stat the binary tool \"zip\" aka[$zipbin], please advise your Administrator\n";
		exit;
	}
	
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	
	$ligne=$q->mysqli_fetch_array("SELECT ComputerOS FROM openvpn_clients WHERE uid='$commonname'");
	if(!$q->ok){
		build_progress(110,"MySQL {failed}");
		echo "ERROR: $q->mysql_error\n";
		exit;
		
	}
	


	
	build_progress(20,"{building_certificates}");
	
	if(!build_certificate($commonname)){
		return;
	}
	
	$workingDir="/etc/artica-postfix/openvpn/$commonname";
	
	build_progress(15,"{building_configuration}");
	$vpn=new openvpn();
	$vpn->ComputerOS=$ligne["ComputerOS"];
	echo "Operating system: $vpn->ComputerOS\n";
	$config=$vpn->BuildClientconf($commonname,$workingDir);
	
	@file_put_contents("$workingDir/$commonname.ovpn", $config);
	

	
	$filesize=filesize("$workingDir/$commonname.ovpn");
	
	echo "$commonname.ovpn === > {$filesize}Bytes\n";
	
	if($filesize==0){
		build_progress(110,"{failed}");
		echo "ERROR: corrupted \"$commonname.ovpn\" 0 bytes, please advise your Administrator\n";
		exit;
	}	
	
	$zippedFile="$workingDir/$commonname.zip";
	if(is_file($zippedFile)){@unlink($zippedFile);}
	
	$mycurrentdir=getcwd();
	chdir($workingDir);
	
	
	$cmd[]="$zipbin $zippedFile";
	$cmd[]="$commonname.ovpn";
	
	$cmd=@implode(" ", $cmd);
	echo "$cmd\n";
    system($cmd);
    chdir($mycurrentdir);
    @chmod($zippedFile,0777);
    @chmod($workingDir,0777);
   	$filesize=@filesize($zippedFile);
   	echo "$zippedFile === > {$filesize}Bytes\n";
   	
   	if($filesize==0){
   		@unlink($zippedFile);
   		system("$rm -rf $workingDir");
   		build_progress(110,"{compress} {failed}");
   		return;
   	}
   	  
	   $zipbincontent=base64_encode(@file_get_contents("$zippedFile"));
	   //writelogs("zipcontentzip: {$zipbincontent}",__CLASS__.'/'.__FUNCTION__,__FILE__);
    $sql = "UPDATE openvpn_clients SET `zipcontent`='$zipbincontent', `zipsize`='$filesize' WHERE uid='$commonname'";
   	  
   	 
      $q->QUERY_SQL($sql);
      if(!$q->ok){
      	@unlink($zippedFile);
      	system("$rm -rf $workingDir");
	  	echo $q->mysql_error;build_progress(110,"{failed}");
	  	return;
      }
      
      
  
   build_progress(100,"{success}...");

}


function build_certificate($uid){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$tar=$unix->find_program("tar");
	$openssl=$unix->find_program("openssl");
	$chmod=$unix->find_program("chmod");
	$TARGET_DIR="/etc/artica-postfix/openvpn/$uid";
	if(is_dir($TARGET_DIR)){system("$rm -rf $TARGET_DIR");}
	@mkdir($TARGET_DIR,0755,true);
	
	$q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
	$OpenVPNCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNCertificate");
	$T=explode(".",$OpenVPNCertificate);
	unset($T[0]);
	$MasterCommonName=@implode(".", $T);
	
	
	$sql="SELECT stateOrProvinceName,localityName,OrganizationName,emailAddress,OrganizationalUnit,levelenc,`easyrsabackup` FROM sslcertificates WHERE CommonName='$OpenVPNCertificate'";
	$ligne=$q->mysqli_fetch_array($sql);
	
	echo "Server Certificate..: $OpenVPNCertificate\n";
	echo "CommonName..........: $uid\n";
	echo "CountryName.........: {$ligne["CountryName"]}\n";
	echo "stateOrProvinceName.: {$ligne["stateOrProvinceName"]}\n";
	echo "OrganizationName....: {$ligne["OrganizationName"]}\n";
	
	$AsProxyCertificate=intval($ligne["AsProxyCertificate"]);

	
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if($ligne["levelenc"]<1024){$ligne["levelenc"]=4096;}
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}else{$C=$ligne["CountryName"];}
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress("MYSQL Error On easyrsabackup",110);
		echo $q->mysql_error."\n$sql\n";
		return false;
	}
	
	$Size=strlen(base64_decode($ligne["easyrsabackup"]));
	
	if($Size==0){
		echo "Package size: $Size (" . FormatBytes($Size/1024).")\n";
		build_progress("Package Error On easyrsabackup",110);
		return;
	}
	
	echo "Package size: $Size (" . FormatBytes($Size/1024).")\n";
	build_progress(25,"{extracting}...");
	$filetemp=$unix->FILE_TEMP().".tar.gz";
	@file_put_contents($filetemp, base64_decode($ligne["easyrsabackup"]));
	system("$tar -xhf $filetemp -C $TARGET_DIR/");
	
	$dirs[]="$TARGET_DIR/pki";
	$dirs[]="$TARGET_DIR/pki/private";
	$dirs[]="$TARGET_DIR/pki/issued";
	$dirs[]="$TARGET_DIR/pki/reqs";

    foreach ($dirs as $num=>$directory){
		if(!is_dir($directory)){
			echo "Missing directory $directory\n";
			build_progress(110,"{extracting} {failed}...");
			return false;
		}
		echo "Directory $directory OK\n";
		@chmod($directory,0755);
	}
	
	
	$f[]="$TARGET_DIR/pki/serial.old";
	$f[]="$TARGET_DIR/pki/index.txt.attr";
	$f[]="$TARGET_DIR/pki/serial";
	$f[]="$TARGET_DIR/pki/private/ca.key";
	$f[]="$TARGET_DIR/pki/dh.pem";
	$f[]="$TARGET_DIR/pki/index.txt.old";
	$f[]="$TARGET_DIR/pki/index.txt";
	$f[]="$TARGET_DIR/pki/ca.crt";
	
	while (list ($num, $filepath) = each ($f) ){
		if(!is_file($filepath)){
			echo "Missing File $filepath\n";
			build_progress(110,"{extracting} {failed}...");
			return false;
		}
		echo "File $filepath OK\n";
		@chmod($filepath,0755);
	}	
	
	system("$chmod -R 0755 $TARGET_DIR/*");
	
	
	$RSA_ROOT="/usr/share/artica-postfix/bin/EasyRSA-3.0.0";
	$f[]="set_var EASYRSA	\"$RSA_ROOT\"";
	$f[]="set_var EASYRSA_OPENSSL	\"$openssl\"";
	$f[]="set_var EASYRSA_PKI		\"$TARGET_DIR/pki\"";
	$f[]="# Choices are:";
	$f[]="#   cn_only  - use just a CN value";
	$f[]="#   org      - use the \"traditional\" Country/Province/City/Org/OU/email/CN format";
	$f[]="set_var EASYRSA_DN	\"org\"";
	$f[]="set_var EASYRSA_REQ_COUNTRY	\"$C\"";
	$f[]="set_var EASYRSA_REQ_PROVINCE	\"{$ligne["stateOrProvinceName"]}\"";
	$f[]="set_var EASYRSA_REQ_CITY	\"{$ligne["localityName"]}\"";
	$f[]="set_var EASYRSA_REQ_ORG	\"{$ligne["OrganizationName"]}\"";
	$f[]="set_var EASYRSA_REQ_EMAIL	\"{$ligne["emailAddress"]}\"";
	$f[]="set_var EASYRSA_REQ_OU		\"{$ligne["OrganizationalUnit"]}\"";
	$f[]="set_var EASYRSA_KEY_SIZE	{$ligne["levelenc"]}";
	$f[]="set_var EASYRSA_ALGO		rsa";
	$f[]="set_var EASYRSA_CURVE		secp384r1";
	$f[]="set_var EASYRSA_CA_EXPIRE	3650";
	$f[]="set_var EASYRSA_CERT_EXPIRE	3650";
	$f[]="set_var EASYRSA_CRL_DAYS	180";
	$f[]="set_var EASYRSA_NS_SUPPORT	\"no\"";
	$f[]="set_var EASYRSA_NS_COMMENT	\"Artica Generated Certificate\"";
	$f[]="set_var EASYRSA_TEMP_FILE	\"\$EASYRSA_PKI/extensions.temp\"";
	$f[]="#alias awk=\"/alt/bin/awk\"";
	$f[]="#alias cat=\"/alt/bin/cat\"";
	$f[]="set_var EASYRSA_EXT_DIR	\"\$EASYRSA/x509-types\"";
	$f[]="set_var EASYRSA_SSL_CONF	\"\$EASYRSA/openssl-1.0.cnf\"";
	$f[]="set_var EASYRSA_REQ_CN		\"$MasterCommonName\"";
	$f[]="set_var EASYRSA_DIGEST		\"sha256\"";
	$f[]="set_var EASYRSA_SUBJ		\"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU\"";
	$f[]="";
	@file_put_contents("/usr/share/artica-postfix/bin/EasyRSA-3.0.0/vars", @implode("\n", $f));
	echo "EasyRSA-3.0.0/vars OK\n";
	
	@chmod("$RSA_ROOT/easyrsa",0755);
	chdir($RSA_ROOT);
	system("./easyrsa build-client-full $uid nopass");
	
	$PrivateKey="$TARGET_DIR/pki/private/$uid.key";
	$Certificate="$TARGET_DIR/pki/issued/$uid.crt";
	
	if(!is_file($PrivateKey)){
		echo "$PrivateKey no such file\n";
		build_progress(110,"{failed}...");
		return false;
	}
	
	if(!is_file($Certificate)){
		echo "$Certificate no such file\n";
		build_progress(110,"{failed}...");
		return false;
	}	
	
	@copy($PrivateKey, "$TARGET_DIR/$uid.key");
	@copy($Certificate, "$TARGET_DIR/$uid.crt");
	@copy("$TARGET_DIR/pki/ca.crt","$TARGET_DIR/$uid.ca");
	return true;
	//$commonname.crt $commonname.csr $commonname.key $commonname.ovpn $commonname.ca
	
	
}

function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/openvpn/openvpn-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("openvpn");
	return $unix->PIDOF_PATTERN("$Masterbin --port.+?--dev");

}




function build_progress($pourc,$text){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/openvpn.client.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}
}


function build_config($directory,$CommonName){
	$sock=new sockets();
	
	$ldap=new clladp();
	$unix=new unix();
	
	$cp=$unix->find_program("cp");
	
	$ligne=unserialize($sock->GET_INFO("OpenVPNCertificateSettings"));
	$CertificateMaxDays=intval($ligne["CertificateMaxDays"]);
	if($CertificateMaxDays<5){$CertificateMaxDays=730;}
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if(trim($ligne["password"])==null){$ligne["password"]=$ldap->ldap_password;}
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];
	@unlink("$directory/.rnd");
	@unlink("$directory/serial.old");
	@unlink("$directory/index.txt.attr");
	@unlink("$directory/index.txt.old");
	@unlink("$directory/rnd");
	
	
	@file_put_contents("$directory/serial.txt", "01");
	@file_put_contents("$directory/serial", "01");
	shell_exec("$cp /dev/null $directory/index.txt");
	
	$f[]="HOME			= $directory";
	$f[]="RANDFILE		= $directory/rnd";
	$f[]="oid_section		= new_oids";
	$f[]="";
	$f[]="[ new_oids ]";
	$f[]="";
	$f[]="[ ca ]";
	$f[]="default_ca	= CA_default		# The default ca section";
	$f[]="[ CA_default ]";
	$f[]="dir			= /etc/artica-postfix/openvpn/keys		# Where everything is kept";
	$f[]="certs			= /etc/artica-postfix/openvpn/keys		# Where the issued certs are kept";
	$f[]="crl_dir		= /etc/artica-postfix/openvpn/keys		# Where the issued crl are kept";
	$f[]="database		= /etc/artica-postfix/openvpn/keys/index.txt	# database index file.";
	$f[]="new_certs_dir	= /etc/artica-postfix/openvpn/keys		# default place for new certs.";
	$f[]="certificate	= /etc/artica-postfix/openvpn/keys/ca.crt 	# The CA certificate";
	$f[]="serial		= /etc/artica-postfix/openvpn/keys/serial 		# The current serial number";
	$f[]="crlnumber		= /etc/artica-postfix/openvpn/keys/crlnumber	# the current crl number";
	$f[]="crl			= /etc/artica-postfix/openvpn/keys/crl.pem 		# The current CRL";
	$f[]="private_key	= /etc/artica-postfix/openvpn/keys/ca.key";
	$f[]="RANDFILE		= /etc/artica-postfix/openvpn/keys/.rand";
	

	$f[]="x509_extensions	= usr_cert		# The extentions to add to the cert";
	$f[]="name_opt 	= ca_default		# Subject Name options";
	$f[]="cert_opt 	= ca_default		# Certificate field options";
	$f[]="default_days	= $CertificateMaxDays";
	$f[]="default_crl_days= 30			# how long before next CRL";
	$f[]="default_md	= sha1			# which md to use.";
	$f[]="preserve	= no			# keep passed DN ordering";
	$f[]="policy		= policy_match";
	$f[]="";
	$f[]="[ policy_match ]";
	$f[]="countryName			= optional";
	$f[]="stateOrProvinceName	= optional";
	$f[]="organizationName		= optional";
	$f[]="organizationalUnitName	= optional";
	$f[]="localityName			= optional";
	$f[]="commonName			= supplied";
	$f[]="emailAddress			= optional";
	$f[]="";
	$f[]="[ policy_anything ]";
	$f[]="countryName			= optional";
	$f[]="stateOrProvinceName	= optional";
	$f[]="localityName			= optional";
	$f[]="organizationName		= optional";
	$f[]="organizationalUnitName	= optional";
	$f[]="commonName			= supplied";
	$f[]="emailAddress			= optional";
	$f[]="";
	$f[]="[ req ]";
	$f[]="default_bits		= 2048";
	$f[]="default_keyfile 	= privkey.pem";
	$f[]="distinguished_name	= req_distinguished_name";
	$f[]="attributes		= req_attributes";
	$f[]="x509_extensions	= v3_ca	# The extentions to add to the self signed cert";
	$f[]="input_password = {$ligne["password"]}";
	$f[]="output_password = {$ligne["password"]}";
	$f[]="string_mask = nombstr";
	$f[]="";
	$f[]="[ req_distinguished_name ]";
	$f[]="countryName				= $C";
	$f[]="countryName_default		= $C";
	$f[]="countryName_min			= 2";
	$f[]="countryName_max			= 2";
	$f[]="stateOrProvinceName		= {$ligne["stateOrProvinceName"]}";
	$f[]="localityName				= {$ligne["localityName"]}";
	$f[]="organizationName			= {$ligne["OrganizationName"]}";
	$f[]="organizationName_default	= {$ligne["OrganizationName"]}";
	$f[]="organizationalUnitName	= {$ligne["OrganizationalUnit"]}";
	$f[]="commonName				= $CommonName";
	$f[]="commonName_default		= $CommonName";
	$f[]="commonName_max			= ".strlen($CommonName);
	$f[]="emailAddress				= {$ligne["emailAddress"]}";
	$f[]="emailAddress_max		= ".strlen($ligne["emailAddress"]);
	$f[]="";
	$f[]="[ req_attributes ]";
	$f[]="challengePassword		= A challenge password";
	$f[]="challengePassword_min		= 4";
	$f[]="challengePassword_max		= 20";
	$f[]="unstructuredName		= An optional company name";
	$f[]="";
	$f[]="[ usr_cert ]";
	$f[]="basicConstraints=CA:FALSE";
	$f[]="nsComment			= \"OpenSSL Generated Certificate\"";
	$f[]="subjectKeyIdentifier=hash";
	$f[]="authorityKeyIdentifier=keyid,issuer";
	$f[]="[ v3_req ]";
	$f[]="basicConstraints = CA:FALSE";
	$f[]="keyUsage = nonRepudiation, digitalSignature, keyEncipherment";
	$f[]="";
	$f[]="[ v3_ca ]";
	$f[]="subjectKeyIdentifier=hash";
	$f[]="authorityKeyIdentifier=issuer:always";
	$f[]="basicConstraints = CA:true";
	$f[]="[ crl_ext ]";
	$f[]="authorityKeyIdentifier=keyid:always,issuer:always";
	$f[]="";
	$f[]="[ proxy_cert_ext ]";
	$f[]="basicConstraints=CA:FALSE";
	$f[]="nsComment			= \"OpenSSL Generated Certificate\"";
	$f[]="subjectKeyIdentifier=hash";
	$f[]="authorityKeyIdentifier=keyid,issuer:always";
	$f[]="proxyCertInfo=critical,language:id-ppl-anyLanguage,pathlen:3,policy:foo";
	$f[]="[ server ]";
	$f[]="basicConstraints=CA:FALSE'";
	$f[]="nsCertType=server";
	$f[]="nsComment=\"Easy-RSA Generated Server Certificate\"";
	$f[]="subjectKeyIdentifier=hash";
	$f[]="authorityKeyIdentifier=keyid,issuer:always";
	$f[]="extendedKeyUsage=serverAuth";
	$f[]="keyUsage=digitalSignature, keyEncipherment";
	$f[]="";
	
	
	
	
	if($C<>null){$TT[]="C=$C";}
	if($ST<>null){$TT[]="ST=$ST";}
	if($O<>null){$TT[]="O=$O";}
	if($OU<>null){$TT[]="OU=$OU";}
	if($L<>null){$TT[]="L=$L";}
	
	
	$TT[]="CN=$CommonName";
	
	$subj= "-subj \"/". @implode("/", $TT)."\"";
	$subj=str_replace("//","/",$subj);
	
	
	echo "[".__LINE__."] Writing $directory/openssl.cf\n";
	@file_put_contents("$directory/openssl.cf", @implode("\n",$f));
	
	echo "[".__LINE__."] Writing $directory/subj.cf\n";
	@file_put_contents("$directory/subj.cf", $subj);
	
	$vars[]="export EASY_RSA=\"/etc/artica-postfix/openvpn\"";
	$vars[]="export OPENSSL=\"openssl\"";
	$vars[]="export PKCS11TOOL=\"pkcs11-tool\"";
	$vars[]="export GREP=\"grep\"";
	$vars[]="export KEY_CONFIG=\"$directory/openssl.cf\"";
	$vars[]="export KEY_DIR=\"\$EASY_RSA/keys\"";
	$vars[]="export PKCS11_MODULE_PATH=\"dummy\"";
	$vars[]="export PKCS11_PIN=\"dummy\"";
	$vars[]="export KEY_SIZE=2048";
	$vars[]="export CA_EXPIRE=$CertificateMaxDays";
	$vars[]="export KEY_EXPIRE=$CertificateMaxDays";
	$vars[]="export KEY_COUNTRY=\"$C\"";
	$vars[]="export KEY_PROVINCE=\"{$ligne["stateOrProvinceName"]}\"";
	$vars[]="export KEY_CITY=\"{$ligne["localityName"]}\"";
	$vars[]="export KEY_ORG=\"{$ligne["OrganizationName"]}\"";
	$vars[]="export KEY_EMAIL=\"{$ligne["emailAddress"]}\"";
	
	@file_put_contents("$directory/vars", @implode("\n", $vars));
	@chmod("$directory/vars",0755);
	
}

function check_file($ca_path){
	
	if(!is_file($ca_path)){
		echo "$ca_path no such file\n";
		return;
		 
	}
	
	$size=@filesize($ca_path);
	if($size<5){
		echo "$ca_path $size < 5bytes\n";
		return;
	
	}
	
	return true;
	
}

function build_server(){
	$ldap=new clladp();
	$unix=new unix();
	$sock=new sockets();
	$ca="/etc/artica-postfix/openvpn/keys/ca.key";
	$cacrt="/etc/artica-postfix/openvpn/keys/ca.crt";
	
	$open_vpn_ca_crt="/etc/artica-postfix/openvpn/keys/openvpn-ca.crt";
	$openvpn_ca_csr="/etc/artica-postfix/openvpn/keys/openvpn-ca.csr";
	
	
	$dh='/etc/artica-postfix/openvpn/keys/dh2048.pem';
	$vpn_server_key="/etc/artica-postfix/openvpn/keys/vpn-server.key";
	$vpn_server_crt="/etc/artica-postfix/openvpn/keys/vpn-server.crt";
	$vpn_server_csr="/etc/artica-postfix/openvpn/keys/vpn-server.csr";
	
	$open_vpn_ca_key="/etc/artica-postfix/openvpn/keys/openvpn-ca.key";
	$cacrt1="/etc/artica-postfix/openvpn/keys/openvpn-ca.crt";
	$allca="/etc/artica-postfix/openvpn/keys/allca.crt";
	
	
	$ligne=unserialize($sock->GET_INFO("OpenVPNCertificateSettings"));

	$hostname=$unix->hostname_g();
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$php=$unix->LOCATE_PHP5_BIN();
	$openssl=$unix->find_program("openssl");
	$CertificateMaxDays=intval($ligne["CertificateMaxDays"]);
	if($CertificateMaxDays<5){$CertificateMaxDays=730;}
	

	if(trim($ligne["password"])==null){$ligne["password"]=$ldap->ldap_password;}
	
	
	
	$directory="/etc/artica-postfix/openvpn/keys";
	@mkdir($directory,0755,true);
	system("$rm -rf $directory/*");
	
	build_progress(25,"{building_configuration}");
	build_config($directory,$hostname);
	
	$subj=@file_get_contents("$directory/subj.cf");
	
	$subjAndConfig="$subj -config $directory/openssl.cf";
	$Config="-config $directory/openssl.cf";
	$passout="-passout pass:{$ligne["password"]}";
	$passin="-passin pass:{$ligne["password"]}";
	
// -------------------------------------------------------------------------------------------	
	$cmd="$openssl req -new -x509 -keyout $ca -out $cacrt $subjAndConfig $passout -batch -days $CertificateMaxDays";
	@chmod($ca,0600);
	
	
	build_progress(10,"$hostname 1/5");
	echo "1)\n";
	echo $cmd."\n";
	system($cmd);
	if(!check_file($ca)){
		build_progress(110,"$hostname {failed}");
		system("$rm -rf $directory/*");
		return;
	}
	if(!check_file($cacrt)){
		build_progress(110,"$hostname {failed}");
		system("$rm -rf $directory/*");
		return;
	}	
	

	
// -------------------------------------------------------------------------------------------	
	$cmd="$openssl req -new -keyout $open_vpn_ca_key -out $openvpn_ca_csr -batch  $subjAndConfig $passout";
	
	
	
	
	echo "2)\n";
	echo $cmd."\n";
	build_progress(20,"$hostname 2/5");
	system($cmd);
	
	
	if(!check_file($open_vpn_ca_key)){
		build_progress(110,"$hostname {failed}");
		system("$rm -rf $directory/*");
		return;
	}
	if(!check_file($openvpn_ca_csr)){
		build_progress(110,"$hostname {failed}");
		system("$rm -rf $directory/*");
		return;
	}
// -------------------------------------------------------------------------------------------	

	$cmd="$openssl ca -extensions v3_ca -days $CertificateMaxDays -out $open_vpn_ca_crt -in $openvpn_ca_csr -batch $subjAndConfig $passin";;
	
	echo "3)\n";
	echo $cmd."\n";
	build_progress(30,"$hostname 3/5");
	system($cmd);
	if(!check_file($open_vpn_ca_crt)){
		build_progress(110,"$hostname {failed}");
		//system("$rm -rf $directory/*");
		return;
	}

// -------------------------------------------------------------------------------------------	
	system("/bin/cat $cacrt $open_vpn_ca_crt > $allca");
// -------------------------------------------------------------------------------------------	
	     
	$cmd="$openssl req -nodes -new -keyout $vpn_server_key -out $vpn_server_csr -batch $subjAndConfig";
	echo "4)\n";
	build_progress(40,"$hostname 4/5");
	echo $cmd."\n";
	system($cmd);
	if(!check_file($vpn_server_key)){
		build_progress(110,"$hostname {failed}");
		system("$rm -rf $directory/*");
		return;
	}
	if(!check_file($vpn_server_csr)){
		build_progress(110,"$hostname {failed}");
		system("$rm -rf $directory/*");
		return;
	}	
	
	
	@unlink("/etc/artica-postfix/openvpn/keys/index.txt");
	@touch("/etc/artica-postfix/openvpn/keys/index.txt");
	$cmd="$openssl ca -keyfile $open_vpn_ca_key -cert $open_vpn_ca_crt -out $vpn_server_crt -in $vpn_server_csr -extensions server -batch $subjAndConfig $passin";
	chmod($vpn_server_key,0600);
	
	
	echo "4)\n";
	echo $cmd."\n";
	system($cmd);
	
	build_progress(50,"$hostname 5/5");
	if(!is_file("/etc/artica-postfix/openvpn/keys/dh2048.pem")){
		$cmd="$openssl dhparam -out /etc/artica-postfix/openvpn/keys/dh2048.pem 2048";
		echo "5)\n";
		echo $cmd."\n";
		system($cmd);
	}
	
	
	build_progress(60,"{restarting_service}");
	system("$php /usr/share/artica-postfix/exec.openvpn.enable.php");
	build_progress(100,"$hostname {success}");
	
	
	
	
	
}


?>