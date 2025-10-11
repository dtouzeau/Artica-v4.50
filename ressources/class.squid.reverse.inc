<?php
include_once(dirname(__FILE__).'/class.nginx-sources.inc');
$GLOBALS["TYPES"][0]="{local_ldap}";
$GLOBALS["TYPES"][1]="{ldap}";
$GLOBALS["TYPES"][2]="{ActiveDirectory}";
$GLOBALS["TYPES"][3]="{trace_connexions}";
$GLOBALS["TYPES"][4]="{redirect_uri}";
$GLOBALS["TYPES"][5]="{CAS_AUTH_LABEL}";

$GLOBALS["SOURCE_TYPE"][0]="{all_cases}";
$GLOBALS["SOURCE_TYPE"][1]="{network_text}";
$GLOBALS["SOURCE_TYPE"][2]="{country}";
$GLOBALS["SOURCE_TYPE"][3]="{cookie}";

$GLOBALS["DEST_TYPES"][0]="{local_ldap}";
$GLOBALS["DEST_TYPES"][1]="{ldap}";
$GLOBALS["DEST_TYPES"][2]="{ActiveDirectory}";

class squid_reverse{
	private $hostname=null;
	private $port=80;
	private $ipaddr=null;
	private $aclname=0;
	private $database="squidlogs";

	public $errors_page=array(400,401,403,404,500,501,502,503,504,505,507,509,510);
	
	public function __construct($hostname=null){
		if($hostname<>null){
			$this->hostname=$hostname;
			$this->loadparams();
		}
		$this->patchtables();
	}
	
	public function compile(){}
	
	
	private function loadparams(){
		$q=new mysql_squid_builder();
		$this->database=$q->database;
		$sql="SELECT * FROM reverse_www WHERE `servername`='$this->hostname`";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$this->ipaddr=$ligne["ipaddr"];
		$this->port=$ligne["port"];
		$this->aclname="www".$ligne["aclname"];
	}
	
	
	public function ssl_certificates_list($NoProxy=false,$IncludeServerCert=0):array{

        $db="/home/artica/SQLITE/certificates.db";
        if(isset($_SESSION["HARMPID"])){
            $gpid=intval($_SESSION["HARMPID"]);
            if($gpid>0){
                $db="/home/artica/SQLITE/certificates.$gpid.db";
            }
        }

        $sqladdon=null;
		if($NoProxy){$sqladdon=" WHERE `AsProxyCertificate`=0";}
		$sql="SELECT CommonName,IsClientCert,clientkey,ServerCert FROM sslcertificates{$sqladdon} ORDER BY CommonName";
		$q=new lib_sqlite($db);
        $results=$q->QUERY_SQL($sql);
        $sslcertificates[null]="{none}";

		foreach ($results as $index=>$ligneZ){
			$client=null;
            if($IncludeServerCert==0) {
                if ($ligneZ["ServerCert"] == 1) {
                    VERBOSE("ServerCert == 1 --> CONTINUE", __LINE__);
                    continue;
                }
            }
			if($ligneZ["IsClientCert"]==1){$client=" (client)";}
			if(strlen($ligneZ["clientkey"])>50){$client=" (client)";}
			$sslcertificates[$ligneZ["CommonName"]]=$ligneZ["CommonName"].$client;
		}
        $results=$q->QUERY_SQL("SELECT ID,DateTo,commonName,organizationName,emailAddress,subjectAltName FROM subcertificates WHERE Certype=1 ORDER BY commonName ASC");
        foreach ($results as $index=>$ligneZ){
            $commonName=$ligneZ["commonName"];
            $ID=$ligneZ["ID"];
            $sslcertificates["SUB:$ID"]="Server: $commonName-$ID";
        }

		return $sslcertificates;
	}
	private function patchtables(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: patching tables...\n";}
		$q=new mysql_squid_builder();
		$this->database=$q->database;
	}
}