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
        include_once(dirname(__FILE__)."/class.certificates-db.inc");
        $cdb=new CertificatesDB();
        $rows=$cdb->dropdown([
            "NoProxy"=>(bool)$NoProxy,
            "IncludeSub"=>true,
        ]);
        $sslcertificates[null]="{none}";
        foreach ($rows as $r){
            $kind=(string)($r->Kind ?? "cert");
            if($kind==="cert"){
                if($IncludeServerCert==0 && intval($r->ServerCert ?? 0)===1){
                    VERBOSE("ServerCert == 1 --> CONTINUE",__LINE__);
                    continue;
                }
                $client=null;
                if(intval($r->IsClientCert ?? 0)===1){$client=" (client)";}
                $ck=(string)($r->clientkey ?? "");
                if(strlen($ck)>50){$client=" (client)";}
                $cn=(string)($r->CommonName ?? "");
                $sslcertificates[$cn]=$cn.$client;
            }else{
                $cn=(string)($r->commonName ?? "");
                $ID=intval($r->ID ?? 0);
                $sslcertificates["SUB:$ID"]="Server: $cn-$ID";
            }
        }
        return $sslcertificates;
	}
	private function patchtables(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: patching tables...\n";}
		$q=new mysql_squid_builder();
		$this->database=$q->database;
	}
}