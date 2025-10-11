#!/usr/bin/php
<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');

@mkdir("/etc/ssl/certs/postfix",0755,true);
@mkdir("/usr/share/artica-postfix/certs",0755,true);
system("/bin/rm -rf /etc/ssl/certs/postfix/*");


$f[]="server.key";
$f[]="ca.key";
$f[]="ca.csr";
$f[]="ca.crt";

$CertificateMaxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CertificateMaxDays"));
if($CertificateMaxDays==0){$CertificateMaxDays=730;}
$default_certificate_path=default_certificate_path();
$LicenseInfos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
$WizardSavedSettings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}

$unix=new unix();
echo "Certificate: OPEN $default_certificate_path\n";
$ini=new Bs_IniHandler($default_certificate_path);

$HostName=$unix->hostname_g();
$openssl=$unix->find_program("openssl");
$ldap=new clladp();

$Password=$ldap->ldap_password;
$countryName=$ini->get('default_ca','countryName_value','US');
$stateOrProvinceName=$ini->get('default_ca','stateOrProvinceName_value','Delaware');
$localityName=$ini->get('default_ca','localityName_value','Wilmington');
$organizationName=$LicenseInfos["COMPANY"];
$emailAddress=$LicenseInfos["EMAIL"];

	$CertificateIniFile=$unix->FILE_TEMP();
	echo "Certificate: SAVE $CertificateIniFile\n";

	$ini->set('CA_default','policy','policy_match');
	$ini->set('ca','unique_subject','no');
	$ini->set('default_ca','unique_subject','no');
	$ini->set('default_db','policy','policy_match');
	$ini->set('policy_match','countryName','match');
	$ini->set('policy_match','stateOrProvinceName','match');
	$ini->set('policy_match','organizationName','match');
	$ini->set('policy_match','organizationalUnitName','optional');
	$ini->set('policy_match','commonName','supplied');
	$ini->set('policy_match','emailAddress','optional');
	$ini->set('policy_anything','countryName','optional');
	$ini->set('policy_anything','stateOrProvinceName','optional');
	$ini->set('policy_anything','localityName','optional');
	$ini->set('policy_anything','organizationName','optional');
	$ini->set('policy_anything','organizationalUnitName','optional');
	$ini->set('policy_anything','commonName','supplied');
	$ini->set('policy_anything','emailAddress','optional');
	$ini->set('v3_ca','subjectKeyIdentifier','hash');
	$ini->set('v3_ca','authorityKeyIdentifier','keyid:always,issuer:always');
	$ini->set('v3_ca','basicConstraints','critical,CA:false');
	$ini->set('req','distinguished_name','default_ca');
	
	echo "Certificate: countryName...........: $countryName\n";
	echo "Certificate: stateOrProvinceName...: $stateOrProvinceName\n";
	echo "Certificate: localityName..........: $localityName\n";
	echo "Certificate: organizationName......: $organizationName\n";
	echo "Certificate: HostName..............: $HostName\n";
	echo "Certificate: emailAddress..........: $emailAddress\n";
	

	$ini->set('default_ca','countryName_value',$countryName);
	$ini->set('default_ca','countryName_min','2');
	$ini->set('default_ca','countryName_max','2');
	$ini->set('default_ca','stateOrProvinceName','State Name');
	$ini->set('default_ca','stateOrProvinceName_value',$stateOrProvinceName);
	$ini->set('default_ca','localityName','Locality Name');
	$ini->set('default_ca','localityName_value',$localityName);
	$ini->set('default_ca','organizationName','organization Name');
	$ini->set('default_ca','organizationName_value',$organizationName);
	$ini->set('req_distinguished_name','organizationalUnitName_default','Mailserver');
	$ini->set('default_ca','commonName','common Name');
	$ini->set('default_ca','commonName_value',$HostName);
	$ini->set('default_ca','emailAddress','email Address');
	$ini->set('default_ca','emailAddress_value',$emailAddress);
	$ini->saveFile($CertificateIniFile);

  	 $cmd=$openssl.' genrsa -out /etc/ssl/certs/postfix/server.key 2048';
     echo "$cmd\n";system($cmd);
     $cmd=$openssl.' req -new -key /etc/ssl/certs/postfix/server.key -batch -config '.$CertificateIniFile.' -out /etc/ssl/certs/postfix/server.csr';
     echo "$cmd\n";system($cmd);
     $cmd=$openssl.' genrsa -out /etc/ssl/certs/postfix/ca.key 1024';
     echo "$cmd\n";system($cmd);
     $cmd=$openssl.' req -new -x509 -days '.$CertificateMaxDays.' -key /etc/ssl/certs/postfix/ca.key -batch -config '.$CertificateIniFile.' -out /etc/ssl/certs/postfix/ca.csr';
     echo "$cmd\n";system($cmd);
     $cmd=$openssl.' x509 -extfile '.$CertificateIniFile.' -x509toreq -days '.$CertificateMaxDays.' -in /etc/ssl/certs/postfix/ca.csr -signkey /etc/ssl/certs/postfix/ca.key -out /etc/ssl/certs/postfix/ca.req';
     echo "$cmd\n";system($cmd);
     $cmd=$openssl.' x509 -extfile '.$CertificateIniFile.' -req -days '.$CertificateMaxDays.' -in /etc/ssl/certs/postfix/ca.req -signkey /etc/ssl/certs/postfix/ca.key -out /etc/ssl/certs/postfix/ca.crt';
     echo "$cmd\n";system($cmd);

	$passfile=$unix->FILE_TEMP();
	@file_put_contents($passfile, $Password);
    
     
   
     $cmd="$openssl pkcs12 -export -password file:$passfile -in /etc/ssl/certs/postfix/ca.crt -inkey /etc/ssl/certs/postfix/ca.key -out /usr/share/artica-postfix/certs/OutlookSMTP.p12";
     echo "$cmd\n";system($cmd);
     $cmd="$openssl x509 -in /etc/ssl/certs/postfix/ca.crt -outform DER -out /usr/share/artica-postfix/certs/smtp.der";
     echo "$cmd\n";system($cmd);
     system('/bin/chmod 755 /usr/share/artica-postfix/certs');
     system('/bin/chmod -R 666 /usr/share/artica-postfix/certs/*');
     
     @unlink($passfile);
     @unlink($CertificateIniFile);
     if(is_file("/etc/init.d/postfix")) {
         system("/etc/init.d/postfix reload");
     }



function default_certificate_path(){
	if(is_file("/etc/artica-postfix/settings/Daemons/PostfixSSLCert")){ return "/etc/artica-postfix/settings/Daemons/PostfixSSLCert";}
	if(is_file("/etc/artica-postfix/ssl.certificate.conf")){ return "/etc/artica-postfix/ssl.certificate.conf";}
	if(is_file("/usr/share/artica-postfix/bin/install/DEFAULT-CERTIFICATE-DB.txt")){ return "/usr/share/artica-postfix/bin/install/DEFAULT-CERTIFICATE-DB.txt";}
	if(is_file("/etc/ssl/openssl.cnf")){ return "/etc/ssl/openssl.cnf";}
	if(is_file("/usr/lib/ssl/openssl.cnf")){ return "/usr/lib/ssl/openssl.cnf";}
	if(is_file("/usr/share/ssl/openssl.cnf")){ return "/usr/share/ssl/openssl.cnf";}

}