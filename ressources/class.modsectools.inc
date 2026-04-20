<?php
include_once ( dirname(__FILE__)."/class.rtmm.tools.inc");
include_once ( dirname(__FILE__)."/class.ip2host.inc");
class modesctools{
    public $ipaddr="0.0.0.0";
    public $hostname=null;
    public $threats=0;
    public $mysql_error="";
    public $continent;
    public $hostid=0;
    public $continent_code;
    public $country;
    public $country_name;
    public $city;
    public $timezone;
    public $flag;
    public $isp;
    public $asn_number;
    public $asn_organization;
    public $organization;
    public $Flag256;

    public function __contruct(){


    }

    public function modsecurity_explains($explainid) {
        $q=new postgres_sql();
        $sql="SELECT report from modsecurity_explains WHERE id=$explainid";
        $ligne=$q->mysqli_fetch_array($sql);
        $vals=htmlentities($ligne["report"]);
        $vals=str_replace("'","`",$vals);
        return $vals;
    }

    function get_servicename($ID):string{
        $ID=intval($ID);
        if($ID==0){return "Unknown";}
        if(isset($GLOBALS["reverse-proxy-service"][$ID])){
            return $GLOBALS["reverse-proxy-service"][$ID];
        }
        $sock=new socksngix($ID);
        $GLOBALS["reverse-proxy-service"][$ID]=$sock->GetServiceName();
        return $GLOBALS["reverse-proxy-service"][$ID];
    }
    private function isHarmpID():bool{
        if(!isset($_SESSION["HARMPID"])){
            return false;
        }
        if(intval($_SESSION["HARMPID"])==0){
            return false;
        }

        return true;
    }
    private function NginxGetDB():string{
        if(!$this->isHarmpID()){
            return "/home/artica/SQLITE/nginx.db";
        }
        $Gpid=$_SESSION["HARMPID"];
        return "/home/artica/SQLITE/nginx.$Gpid.db";
    }

    public function rule_is_global_white($ruleid):bool{
        $ruleid=intval($ruleid);
        if($ruleid==0){return false;}
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$ruleid&serviceid=0"),true);
        if(!is_array($json) || !$json["Status"]){return false;}
        foreach($json["entries"] as $e){
            if(strlen($e["spath"])==0){return true;}
        }
        return false;
    }
    function rule_is_serviceid_white($ruleid,$serviceid):bool{
        $ruleid=intval($ruleid);
        if($ruleid==0){return false;}
        $serviceid=intval($serviceid);
        if($serviceid==0){return false;}
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$ruleid&serviceid=$serviceid"),true);
        if(!is_array($json) || !$json["Status"]){return false;}
        foreach($json["entries"] as $e){
            if(strlen($e["spath"])==0){return true;}
        }
        return false;
    }
    function rule_is_path_white($ruleid,$serviceid,$path){
        $ruleid=intval($ruleid);
        if($ruleid==0){return false;}
        $serviceid=intval($serviceid);
        if($serviceid==0){return false;}
        if(strlen($path)<1){return false;}
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$ruleid&serviceid=$serviceid&path=".urlencode($path)),true);
        if(!is_array($json) || !$json["Status"]){return false;}
        return count($json["entries"])>0;
    }

    private function modsecWhitelistAPI($action,$ruleid,$serviceid=0,$path=""):bool{
        $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/modsec/whitelist", [
            "action"    => $action,
            "ruleid"    => intval($ruleid),
            "serviceid" => intval($serviceid),
            "path"      => $path
        ]),true);
        if(!is_array($result) || !$result["Status"]){
            $this->mysql_error=is_array($result) ? $result["Error"] : "daemon unavailable";
            return false;
        }
        return true;
    }

    public function unwhite_global($ruleid):bool{
        $ruleid=intval($ruleid);
        if($ruleid==0){$this->mysql_error="Rule ID = 0 ??";return false;}
        if(!$this->modsecWhitelistAPI("delete",$ruleid)){return false;}
        $sock=new sockets();
        $sock->REST_API_NGINX("/reverse-proxy/wafnohup");
        return admin_tracks("Remove WAF rule $ruleid To global whitelist");
    }
    public function unwhite_serviceid($ruleid,$serviceid):bool{
        $ruleid=intval($ruleid);
        if($ruleid==0){$this->mysql_error="Rule ID = 0 ??";return false;}
        $serviceid=intval($serviceid);
        if($serviceid==0){$this->mysql_error="Service ID = 0 ??";return false;}
        if(!$this->modsecWhitelistAPI("delete",$ruleid,$serviceid)){return false;}
        $servicename=$this->get_servicename($serviceid);
        $sock=new sockets();
        $sock->REST_API_NGINX("/reverse-proxy/wafnohup");
        return admin_tracks("Remove WAF rule $ruleid from $servicename");
    }
    public function whitelist_global_rule($ruleid):bool{
        $ruleid=intval($ruleid);
        if($ruleid==0){$this->mysql_error="Rule ID = 0 ??";return false;}
        if(!$this->modsecWhitelistAPI("add",$ruleid)){return false;}
        return admin_tracks("Add WAF rule $ruleid To global whitelist");
    }
    public function LoadAllWhitelists():array{
        $MAIN=array();
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist"),true);
        if(!is_array($json) || !$json["Status"]){return $MAIN;}
        foreach ($json["entries"] as $ligne){
            $ruleid=intval($ligne["wfrule"]);
            $serviceid=intval($ligne["serviceid"]);
            $spath=$ligne["spath"];
            if (strlen($spath)==0){
                $MAIN[$ruleid][$serviceid]=true;
                continue;
            }
            $MAIN[$ruleid][$serviceid]=$spath;
        }
        return $MAIN;
    }

    public function whitelist_serviceid_rule($ruleid,$serviceid):bool{
        $ruleid=intval($ruleid);
        $serviceid=intval($serviceid);
        if($ruleid==0){$this->mysql_error="Rule ID = 0 ??";return false;}
        if($serviceid==0){$this->mysql_error="Service ID = 0 ??";return false;}
        if(!$this->modsecWhitelistAPI("add",$ruleid,$serviceid)){return false;}
        $sitename=$this->get_servicename($serviceid);
        return admin_tracks("Add WAF rule $ruleid To $sitename");
    }
    function whitelist_path_rule($ruleid,$serviceid,$path):bool{
        $ruleid=intval($ruleid);
        $serviceid=intval($serviceid);
        if($ruleid==0){$this->mysql_error="Rule ID = 0 ??";return false;}
        if($serviceid==0){$this->mysql_error="Service ID = 0 ??";return false;}
        if(strlen($path)==0){$this->mysql_error="Path = empty ??";return false;}
        if(!$this->modsecWhitelistAPI("add",$ruleid,$serviceid,$path)){return false;}
        $sitename=$this->get_servicename($serviceid);
        $sock=new sockets();
        $sock->REST_API_NGINX("/reverse-proxy/wafnohup");
        return admin_tracks("Add WAF rule $ruleid To $sitename [$path]");
    }
    function unwhite_path($ruleid,$serviceid,$path):bool{
        $ruleid=intval($ruleid);
        $serviceid=intval($serviceid);
        if($ruleid==0){$this->mysql_error="Rule ID = 0 ??";return false;}
        if($serviceid==0){$this->mysql_error="Service ID = 0 ??";return false;}
        if(strlen($path)==0){$this->mysql_error="Path = empty ??";return false;}
        if(!$this->modsecWhitelistAPI("delete",$ruleid,$serviceid,$path)){return false;}
        $sitename=$this->get_servicename($serviceid);
        $sock=new sockets();
        $sock->REST_API_NGINX("/reverse-proxy/wafnohup");
        return admin_tracks("Delete WAF rule $ruleid from $sitename [$path]");
    }


    public function PathFromEventID($eventid):string{
        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT mainurl FROM modsecurity_audit WHERE id=$eventid");
        $mainurl=$ligne["mainurl"];

        if(!preg_match("#^http.*?:\/\/#",$mainurl)){
            $mainurl="http://$mainurl";
        }
        VERBOSE("MAINURL = $mainurl",__LINE__);
        $parse=parse_url($mainurl);
        if(!isset($parse["path"])){return "";}
        if($parse["path"]=="/"){return "";}
        return $parse["path"];
    }




    public function CountOfOccurenceSite($ruleid,$serviceid):int{
        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM modsecurity_audit where ruleid='$ruleid' and serviceid=$serviceid");
        if(!isset($ligne["tcount"])){$ligne["tcount"]=0;}
        return intval($ligne["tcount"]);

    }
    public function CountOfOccurence($ruleid){
        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM modsecurity_audit where ruleid='$ruleid'");
        if(!isset($ligne["tcount"])){$ligne["tcount"]=0;}
        return intval($ligne["tcount"]);
    }

    public function modsecurity_titles($ruleid){
        if(!isset($GLOBALS["modsecurity_titles"][$ruleid])){
            $q=new postgres_sql();
            $ligne=$q->mysqli_fetch_array("SELECT * FROM modsecurity_titles where ruleid='$ruleid'");
            $GLOBALS["modsecurity_titles"][$ruleid]=$ligne;
        }else{
            $ligne=$GLOBALS["modsecurity_titles"][$ruleid];
        }

        return $ligne["msg"];

    }

    private function isLocalIP($ip) {
        $privateIPs = [
            ['10.0.0.0',    '10.255.255.255'],
            ['172.16.0.0',  '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255']
        ];

        $ipLong = ip2long($ip);

        foreach ($privateIPs as $range) {
            $low = ip2long($range[0]);
            $high = ip2long($range[1]);

            if ($ipLong >= $low && $ipLong <= $high) {
                return true;
            }
        }

        return false;
    }

    private function GetCached($hostid){
        if(isset($GLOBALS["hton"][$hostid])) {
            return $GLOBALS["hton"][$hostid];
        }

        $mem = new lib_memcached();
        $val = $mem->getKey($hostid);
        if (strlen($val) > 10) {
            $ligne=unserialize(base64_decode($val));
            VERBOSE("HIT: $hostid MEM-CACHED: " . strlen($val) . " bytes", __LINE__);
            if($ligne && count($ligne)>2) {
                $GLOBALS["hton"][$hostid] = unserialize(base64_decode($val));
                return $GLOBALS["hton"][$hostid];
            }
        }

        $q = new postgres_sql();
        $ligne = $q->mysqli_fetch_array("SELECT * FROM hotstinfos where hton='$hostid'");
        if(!$ligne){
            return array();
        }
        if(!isset($ligne["hton"])){
            return array();
        }
        if($ligne["hton"]>0) {
            $GLOBALS["hton"][$hostid]=$ligne;
            $mem->saveKey($hostid,base64_encode(serialize($ligne)));
            return $ligne;
        }

        return array();
    }

    public function hostinfo($hostid,$updateIfNotFound=false):array{
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#",$hostid)) {
            if ($this->isLocalIP($hostid)) {
                $this->ipaddr = $hostid;
                $this->hostname = "";
                $this->continent = "local";
                $this->continent_code = "N/A";
                $this->country = "local";
                $this->country_name = "local";
                $this->city = "local";
                $this->timezone = "";
                $this->isp = "";
                $this->asn_number = "";
                $this->asn_organization = "";
                $this->organization = "";
                $this->flag = GetFlags("");
                $this->Flag256 = GetFlags256("");
                return array();
            }
            $ipaddr=$hostid;
            $hostid=ip2long($ipaddr);
        }

            $ligne=$this->GetCached($hostid);
            if(count($ligne)==0){
                if(!isset($GLOBALS["IPINFO-$this->hostid"])) {
                    $GLOBALS["IPINFO-$this->hostid"]=true;
                    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ipinfo/$this->hostid");
                }
                VERBOSE("[$hostid] !!! MISS",__LINE__);
                return array();
            }
           $this->hostid = $ligne["hton"];

        if(!isset($ligne["ipaddr"])){
            if(!is_array($ligne)){$ligne=array();}
            VERBOSE("ipaddr !isset",__LINE__);
            return $ligne;
        }
        $this->ipaddr=$ligne["ipaddr"];
        $this->hostname=$ligne["hostname"];
        $this->continent=$ligne["continent"];
        $this->continent_code=$ligne["continent_code"];
        $this->country=$ligne["country"];
        $this->country_name=$ligne["country_name"];
        $this->city=$ligne["city"];
        $this->timezone=$ligne["timezone"];
        $this->isp=$ligne["isp"];
        $this->asn_number=$ligne["asn_number"];
        $this->asn_organization=$ligne["asn_organization"];
        $this->organization=$ligne["organization"];


        $this->flag=GetFlags($ligne["country_name"]);
        VERBOSE("GetFlags({$ligne["country_name"]}) == [ $this->flag]",__LINE__);
        $this->Flag256=GetFlags256($ligne["country_name"]);
        if(!is_array($ligne)){$ligne=array();}
        return $ligne;
    }





}