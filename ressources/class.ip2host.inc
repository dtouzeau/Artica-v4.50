<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!isset($GLOBALS["NOCACHE"])){$GLOBALS["NOCACHE"]=false;}
include_once(dirname(__FILE__).'/class.modsectools.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__) .'/class.tcpip.inc');
include_once(dirname(__FILE__)."/class.memcached.inc");
include_once(dirname(__FILE__)."/class.ccurl.inc");
include_once(dirname(__FILE__)."/logs.inc");
include_once(dirname(__FILE__)."/externals/Net/DNS2.inc");
include_once(dirname(__FILE__)."/class.rtmm.tools.inc");

class ip2host{
    public $ipaddr=null;
    public $host=null;
    var $output;
    public function __construct($ip=null){
        if(!is_null($ip)) {
            $IpClass = new IP();
            if ($IpClass->isIPv4($ip)) {
                $this->ipaddr = $ip;
                if ($this->ipaddr == "127.0.0.1") {
                    $this->output = $ip;
                    return;
                }
                $this->LoadFromIP();
                return;
            }
            $this->output = $ip;

        }
    }

    public function ipinfoApi($ipaddr):array{
        if(isset($GLOBALS["ipinfoApi:$ipaddr"])){return $GLOBALS["ipinfoApi:$ipaddr"];}
        if($this->is_local_net($ipaddr)){
            $MAIN["continent"]="local";
            $MAIN["continentCode"]="N/A";
            $MAIN["country"]="N/A";
            $MAIN["countryName"]="N/A";
            $MAIN["subdivisions"]=array();
            $MAIN["city"]="N/A";
            $MAIN["timezone"]="N/A";
            $MAIN["isp"]="local";
            $MAIN["asnNumber"]="0";
            $MAIN["asnOrganization"]="local";
            $MAIN["organization"]="local";
            $MAIN["postalCode"]="0";
            $MAIN["flag"]="flags/info.png";
            $GLOBALS["ipinfoApi:$ipaddr"]=$MAIN;
            return $GLOBALS["ipinfoApi:$ipaddr"];
        }


        $modsec=new modesctools();
        $MAIN=$modsec->hostinfo($ipaddr);
        if($modsec->hostid>0){
            $MAIN["continent"]=$modsec->continent;
            $MAIN["continentCode"]="N/A";
            $MAIN["country"]=$modsec->country;
            $MAIN["countryName"]=$modsec->country_name;
            $MAIN["subdivisions"]=array();
            $MAIN["city"]=$modsec->city;
            $MAIN["timezone"]="N/A";
            $MAIN["isp"]=$modsec->isp;
            $MAIN["asnNumber"]=$modsec->asn_number;
            $MAIN["asnOrganization"]=$modsec->asn_organization;
            $MAIN["organization"]=$modsec->organization;
            $MAIN["postalCode"]="0";
            $MAIN["flag"]=GetFlags(strtolower($MAIN["countryName"]));
            return $MAIN;
        }

        $mem = new lib_memcached();
        if(!$GLOBALS["VERBOSE"]) {
            $data = $mem->getKey("ip2info:$ipaddr");
            if ($mem->MemCachedFound) {
                VERBOSE("MemCachedFound: $ipaddr",__LINE__);
                $MAIN=unserialize($data);
                $MAIN["ipaddr"]=$ipaddr;
                $this->UpdateTable($MAIN);
                return $MAIN;
            }
        }



        $available[]="http://ip-info.ff.avast.com/v2/info?ip=%addr";
        $available[]="http://ip-info-gcp.ff.avast.com/v2/info?ip=%addr";
        $urlindex=rand(0,count($available)-1);
        $url=$available[$urlindex];
        $url=str_replace("%addr",$ipaddr,$url);
        if($GLOBALS["VERBOSE"]) {VERBOSE("URL=$urlindex -> $url",__LINE__);}

        $curl=new ccurl($url);
        $curl->NoHTTP_POST=true;
        if(!$curl->get()){
            if($GLOBALS["VERBOSE"]) {VERBOSE("CURL ERROR $curl->error",__LINE__);}
            $GLOBALS["ipinfoApi:$ipaddr"]=array("flag"=>"flags/info.png");
        }
        if(!is_null($curl->data)) {
            if ($GLOBALS["VERBOSE"]) {
                VERBOSE("Data: " . strlen($curl->data) . " bytes", __LINE__);
            }
        }
        if(!is_null($curl->data)) {
            $json = json_decode($curl->data);
            if (!is_null($json)) {
                foreach ($json as $key => $value) {
                    if ($GLOBALS["VERBOSE"]) {
                        if (!is_array($value)) {
                            VERBOSE("$key -> $value", __LINE__);
                        }
                    }
                    $MAIN[$key] = $value;
                }
            }
        }

        if(count($MAIN)>2){
            $MAIN["flag"]="flags/info.png";
            if(!is_null($MAIN["countryName"])) {
                $MAIN["flag"] = GetFlags(strtolower($MAIN["countryName"]));
            }
            $MAIN["ipaddr"]=$ipaddr;
            $GLOBALS["ipinfoApi:$ipaddr"]=$MAIN;
            $mem->saveKey("ip2info:$ipaddr",serialize($MAIN));
            $this->UpdateTable($MAIN);
            return $MAIN;
        }
        $GLOBALS["ipinfoApi:$ipaddr"]=array("flag"=>"flags/info.png");
        return array();
    }

    private function UpdateTable($MAIN):bool{
        if($MAIN["ipaddr"]=="0.0.0.0"){
            return false;
        }
        $long=ip2long($MAIN["ipaddr"]);

        $q=new postgres_sql();

        $KEYS[]="ipaddr";
        $VALS[]="'".$MAIN["ipaddr"]."'";

        $KEYS[]="hton";
        $VALS[]=intval($long);

        $KEYS[]="hostname";
        $VALS[]="'".gethostbyaddr($MAIN["ipaddr"])."'";

        foreach ($MAIN as $key=>$val){
            $MAIN[$key]=str_replace("'","`", $val);
        }
        if(!isset($MAIN["continentCode"])){$MAIN["continentCode"]="";}
        if(!isset($MAIN["countryName"])){$MAIN["countryName"]="";}
        if(!isset($MAIN["asnNumber"])){$MAIN["asnNumber"]="";}
        if(!isset($MAIN["asnOrganization"])){$MAIN["asnOrganization"]="";}
        $KEYS[]="continent";
        $VALS[]="'".$MAIN["continent"]."'";

        $KEYS[]="continent_code";
        $VALS[]="'".$MAIN["continentCode"]."'";

        $KEYS[]="country";
        $VALS[]="'".$MAIN["country"]."'";

        $KEYS[]="country_name";
        $VALS[]="'".$MAIN["countryName"]."'";

        $KEYS[]="city";
        $VALS[]="'".$MAIN["city"]."'";

        $KEYS[]="timezone";
        $VALS[]="'".$MAIN["timezone"]."'";

        $KEYS[]="isp";
        $VALS[]="'".$MAIN["isp"]."'";

        $KEYS[]="asn_number";
        $VALS[]="'".intval($MAIN["asnNumber"])."'";

        $KEYS[]="asn_organization";
        $VALS[]="'".$MAIN["asnOrganization"]."'";

        $KEYS[]="organization";
        $VALS[]="'".$MAIN["organization"]."'";
$sql="INSERT INTO hotstinfos (".@implode(",",$KEYS).") 
        VALUES (".@implode(",",$VALS).") ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        return true;
    }

    private function is_local_net($ipaddr):bool
    {
        if ($this->ip_in_range($ipaddr, "127.0.0.0/8")) {
            return true;
        }
        if ($this->ip_in_range($ipaddr, "10.0.0.0/8")) {
            return true;
        }
        if ($this->ip_in_range($ipaddr, "172.16.0.0/12")) {
            return true;
        }
        if ($this->ip_in_range($ipaddr, "192.168.0.0/16")) {
            return true;
        }
        if ($this->ip_in_range($ipaddr, "169.254.0.0/16")) {
            return true;
        }
        return false;
    }

    private function ip_in_range( $ip, $range ) {
        if ( strpos( $range, '/' ) == false ) { $range .= '/32';}

        list( $range, $netmask ) = explode( '/', $range, 2 );
        $range_decimal = ip2long( $range );
        $ip_decimal = ip2long( $ip );
        $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
    }

    private function LoadFromIP(){
        $mem=new lib_memcached();
        $hostname=$mem->getKey("ip2host:$this->ipaddr");
        VERBOSE("IP2HOST checking ip in MemCache ".$this->ipaddr,__LINE__);
        if($mem->MemCachedFound){
            VERBOSE("IP2HOST found MemCachedFound = True -> ".unserialize($hostname),__LINE__);
            $this->output=$this->ipaddr.' ('.unserialize($hostname).')';
        }
        else{
            $DNS=$this->LoadMyDNS();
            $Config["nameservers"]=$GLOBALS["MY_DNSS"];
            $Config["timeout"]=1;
            $resolver = new Net_DNS2_Resolver($Config);
            try {
                $response = $resolver->query($this->ipaddr, 'PTR');
                if ($response) {
                    $ptr = $response->answer[0]->ptrdname;
                    $ttl = $response->answer[0]->ttl;
                    if($ptr=="localhost"){
                        $this->host=$this->ipaddr;
                        VERBOSE("IP2HOST is Localhost return ip",__LINE__);
                    }
                    else {
                        $mem->saveKey("ip2host:$this->ipaddr", serialize($ptr),$ttl);
                        VERBOSE("IP2HOST Net_DNS2_Resolver RETURN  = $ptr",__LINE__);
                        $this->host=$this->ipaddr.' ('.$ptr.')';
                    }
                }
                else {
                    $this->host=$this->ipaddr;
                    VERBOSE("IP2HOST Net_DNS2_Resolver FAILED",__LINE__);
                }
            } catch (Net_DNS2_Exception $e) {
                $this->host=$this->ipaddr;
                VERBOSE("IP2HOST Net_DNS2_Resolver ERROR $e",__LINE__);
            }
            $this->output=$this->host;
        }
    }

    private function LoadMyDNS(){
        if(isset($GLOBALS["MY_DNSS"])) {return $GLOBALS["MY_DNSS"];}
        $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('UnboundEnabled'));
        if($UnboundEnabled==1){
            $GLOBALS["MY_DNSS"][] = '127.0.0.1';
        }
        else{
            $f = explode("\n", @file_get_contents("/etc/resolv.conf"));
            foreach ($f as $line) {
                $line = trim($line);
                if ($line == null) {
                    continue;
                }
                if (!preg_match("#nameserver\s+([0-9\.]+)#", $line, $re)) {
                    continue;
                }
                $GLOBALS["MY_DNSS"][] = $re[1];
            }
        }



        return $GLOBALS["MY_DNSS"];
    }
}