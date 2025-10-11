<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__)."/externals/adLDAP/adLDAP.php");
include_once(dirname(__FILE__)."/class.sockets.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])) {$GLOBALS["CLASS_SOCKETS"] = new sockets();}
class ActiveDirectory{
    var $ldapbind;
    var $ldap_connection;
    var $ldap_admin="";
    var $ldap_dn_user="";
    var $ldap_password="";
    var $suffix="";
    var $ldap_host="";
    var $ldap_host2="";
    var $ldap_port=389;
    var $ldap_last_error="";
    var $ldap_ipaddr="";
    var $ldap_ssl=0;
    var $ldapFailed=false;
    var $members=array();
    var $LDAP_RECURSIVE=0;
    var $ARRAY_MEMBERS=array();
    var $UseDynamicGroupsAcls=1;
    var $ADNETIPADDR="";
    var $ldap_failed=false;

    private $WINDOWS_SERVER_ADMIN=null;
    private $WINDOWS_DNS_SUFFIX=null;

    function __construct($AdSourceID=0,$FromHASH=array()){
        if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}
        if(!is_numeric($AdSourceID)){$AdSourceID=0;}
        if(count($FromHASH)>2){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>MergGroupFromHASH(array)</span><br>";}
            $this->MergGroupFromHASH($FromHASH);

        }else{

            if($AdSourceID==0){
                $this->LoadAdSettingsFromSquid();
            }
        }
    }

    public function TestsGoLDAP():array{

        if(!is_file("/home/artica/web_templates/hotspot.AdCnx")){
            return array(false,"{not_configured}");
        }
        $f=explode("\n",@file_get_contents("/home/artica/web_templates/hotspot.AdCnx"));
        $c=0;
        foreach ($f as $line){
            if(strpos(" $line","|")==0){continue;}
            $conf=explode("|",$line);
            $hostname=$conf[0];
            if(!$this->test_ldap_connection($hostname)){
                return array(false,"{error} CNX.$c");

            }
            $c++;
        }
        if($c==0){
            return array(false,"{not_configured}");
        }

        return array(true,null);
    }


    public function test_ldap_connection($cn): bool
    {
        $GLOBALS["LDAP_CONNECTION_FAILED"]=null;
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/ldap/test/connections?cn=$cn");
        $y=json_decode($resp,true);
        if(isset($y[0])){
            if (!$y[0]["status"]){
                VERBOSE("{$y[0]["message"]}",__LINE__);
                $GLOBALS["LDAP_CONNECTION_FAILED"]=$y[0]["message"];
                return false;
            }
        }

        $GLOBALS["LDAP_CONNECTION_FAILED"]=null;
        VERBOSE("$cn [OK]/SUCCESS",__LINE__);
        return true;
    }

    private function extended_error_transform($extended_error):string{
        if($GLOBALS["VERBOSE"]){
            if(function_exists("VERBOSE")){
                VERBOSE("extended_error_transform:$extended_error ",__LINE__);
            }
        }
        if(stripos(" $extended_error","DSID-0C090436")>0){
            return "{bdu}/{locked_account} DSID-0C090436";

        }




        return $extended_error;

    }



    private function MergGroupFromHASH($HASH){


        if($GLOBALS["VERBOSE"]){
            if(function_exists("VERBOSE")) {
                foreach ($HASH as $key => $val) {
                   VERBOSE("Loading $key=[$val]",__LINE__);
                }
            }
        }


        if(!isset($HASH["LDAP_SERVER2"])){$HASH["LDAP_SERVER2"]=null;}
        if(!isset($HASH["LDAP_SSL"])){$HASH["LDAP_SSL"]=0;}
        $this->ldap_dn_user=$HASH["LDAP_DN"];
        $this->ldap_password=$HASH["LDAP_PASSWORD"];
        $this->suffix=$HASH["LDAP_SUFFIX"];
        $this->ldap_host=$HASH["LDAP_SERVER"];
        $this->ldap_port=$HASH["LDAP_PORT"];
        $this->ldap_ssl=intval($HASH["LDAP_SSL"]);
        $this->ldap_host2=$HASH["LDAP_SERVER2"];
        if(isset($HASH["ADNETIPADDR"])){
            $this->ldap_ipaddr=$HASH["ADNETIPADDR"];
            if(preg_match("#^[0-9\.]+$#",$HASH["ADNETIPADDR"])){
                $HASH["LDAP_SERVER"]=$HASH["ADNETIPADDR"];
                $this->ldap_host=$HASH["ADNETIPADDR"];
            }
        }


        if($this->ldap_ssl==1){
            $this->ldap_port=636;
            $this->ldap_host="ldaps://{$HASH["LDAP_SERVER"]}/";
            if($this->ldap_host2<>null){
                $this->ldap_host2="ldaps://{$HASH["LDAP_SERVER2"]}/";
            }
        }

        if(strpos($HASH["LDAP_DN"], "@")>0){
            $str=explode("@",$HASH["LDAP_DN"]);
            $this->WINDOWS_SERVER_ADMIN=$str[0];
            $this->WINDOWS_DNS_SUFFIX=$str[1];
        }

        if($this->WINDOWS_DNS_SUFFIX==null){
            $str=$this->suffix;
            $str=str_ireplace("dc=", "",$str);
            $str=str_ireplace("cn=", "", $str);
            $str=str_ireplace("ou=", "", $str);
            $tt=explode(",",$str);
            $this->WINDOWS_DNS_SUFFIX=@implode(".", $tt);
        }



    }



    private function LoadAdSettingsFromSquid(): bool{

        if(isset($GLOBALS["LoadAdSettingsFromSquid"]["CACHE"])){
            foreach ($GLOBALS["LoadAdSettingsFromSquid"]["CACHE"] as $key=>$value){
                $this->$key=$value;
            }
            return true;
        }
        $OnlyKerb=false;
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        if($WindowsActiveDirectoryKerberos==1){$OnlyKerb=true;}


        if($LockActiveDirectoryToKerberos==1){$OnlyKerb=true;}

        if($EnableKerbAuth==0){
            if($GLOBALS["VERBOSE"]){writelogs("EnableKerbAuth is disabled", __CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);}
            return false;
        }

        $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        if(!is_array($array)){$array=array();}
        if($GLOBALS["VERBOSE"]){
            if(function_exists("VERBOSE")){
                foreach ($array as $key=>$value){VERBOSE("KerbAuthInfos[$key]={$value}",__LINE__);}
            }
        }


        if(!$OnlyKerb) {
            if ((!isset($array["LDAP_SUFFIX"])) OR (strlen(trim($array["LDAP_SUFFIX"])) < 3) OR (strlen(trim($array["WINDOWS_DNS_SUFFIX"])) < 3)) {
                $ch = false;
                $INFOS = $this->NetAdsInfo();
                if($GLOBALS["VERBOSE"]){
                    if(function_exists("VERBOSE")){
                        foreach ($INFOS as $key=>$value){VERBOSE("INFOS[$key]={$value}",__LINE__);}
                    }
                }
                if (!isset($INFOS["SUFFIX"])) {
                    $INFOS["SUFFIX"] = null;
                }
                if (!isset($INFOS["LDAP_PORT"])) {
                    $INFOS["LDAP_PORT"] = null;
                }
                if (!isset($INFOS["LDAP_SERVER"])) {
                    $INFOS["LDAP_SERVER"] = null;
                }
                if (!isset($INFOS["FQDN"])) {
                    $INFOS["FQDN"] = null;
                }
                if (!isset($INFOS["ADNETIPADDR"])) {
                    $INFOS["ADNETIPADDR"] = null;
                }
                if ($INFOS["ADNETIPADDR"] <> null) {
                    $array["ADNETIPADDR"] = $INFOS["ADNETIPADDR"];
                    $ch = true;
                }

                if ($INFOS["SUFFIX"] <> null) {
                    $array["LDAP_SUFFIX"] = $INFOS["SUFFIX"];
                    $ch = true;
                }
                if ($INFOS["LDAP_PORT"] <> null) {
                    $array["LDAP_PORT"] = $INFOS["LDAP_PORT"];
                    $ch = true;
                }
                if ($INFOS["SERVER_IP"] <> null) {
                    $array["LDAP_SERVER"] = $INFOS["SERVER_IP"];
                    $ch = true;
                }
                if ($INFOS["FQDN"] <> null) {
                    $array["FQDN"] = $INFOS["FQDN"];
                    $ch = true;
                }
                if ($ch) {
                    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
                }
            }

        }
        //cn=$this->ldap_admin,cn=Users,

        if(!isset($array["RECURSIVE_GROUPS"])){$array["RECURSIVE_GROUPS"]=0;}
        if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=null;}
        if(!isset($array["LDAP_RECURSIVE"])){$array["LDAP_RECURSIVE"]=0;}
        if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
        if(!isset($array["LDAP_SSL"])){$array["LDAP_SSL"]=0;}
        if(!isset($array["ADNETIPADDR"])){$array["ADNETIPADDR"]=null;}
        if(!isset($array["LDAP_SUFFIX"])){$array["LDAP_SUFFIX"]=null;}
        if(!isset($array["WINDOWS_DNS_SUFFIX"])){$array["WINDOWS_DNS_SUFFIX"]=null;}

        if($array["LDAP_PASSWORD"]==null){
            $this->ldap_password=$array["WINDOWS_SERVER_PASS"];
        }else{
            $this->ldap_password=$array["LDAP_PASSWORD"];
        }
        if(!isset($array["LDAP_SERVER2"])){
            $array["LDAP_SERVER2"]=null;
        }

        if($GLOBALS["VERBOSE"]){
            if(function_exists("VERBOSE")){
                foreach ($array as $key=>$value){VERBOSE("array[$key]={$value}",__LINE__);}
            }
        }

        if(!isset($array["LDAP_PORT"])){
            $array["LDAP_PORT"]=389;
            if($array["LDAP_SSL"]==1){$array["LDAP_PORT"]=636;}
        }

        $this->suffix=$array["LDAP_SUFFIX"];
        $this->ldap_host=$array["LDAP_SERVER"];
        $this->ldap_port=$array["LDAP_PORT"];
        $this->ldap_host2=$array["LDAP_SERVER2"];
        $this->LDAP_RECURSIVE=$array["LDAP_RECURSIVE"];
        $this->ldap_ssl=$array["LDAP_SSL"];
        $this->ldap_ipaddr=$array["ADNETIPADDR"];
        if($array["RECURSIVE_GROUPS"]==1){$this->LDAP_RECURSIVE=1;}

        if($this->ldap_ssl==1){
            $this->ldap_host="ldaps://$this->ldap_host/";
        }


        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $array["WINDOWS_DNS_SUFFIX"])){
            if($array["FQDN"]<>null){$FGH=explode(".",$array["FQDN"]);unset($FGH[0]);$array["WINDOWS_DNS_SUFFIX"]=@implode(".", $FGH);}
        }


        $this->WINDOWS_SERVER_ADMIN=$array["WINDOWS_SERVER_ADMIN"];
        $this->WINDOWS_DNS_SUFFIX=$array["WINDOWS_DNS_SUFFIX"];

        if(strpos($array["WINDOWS_SERVER_ADMIN"],"@")>0){
            $ldap_dn_user=$array["WINDOWS_SERVER_ADMIN"];

        }else{
            $ldap_dn_user="{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}";
            if($array["WINDOWS_DNS_SUFFIX"]<>null){
                $ldap_dn_user="$ldap_dn_user@{$array["WINDOWS_DNS_SUFFIX"]}";
            }
        }

        if($array["LDAP_DN"]==null){
            $this->ldap_dn_user=$ldap_dn_user;
        }else{

            if(!preg_match("#(.+?)@(.+)#", $array["LDAP_DN"])){
                if(preg_match("#^CN=(.+?),#i", $array["LDAP_DN"],$re)){
                    $array["LDAP_DN"]="{$re[1]}@{$array["WINDOWS_DNS_SUFFIX"]}";
                }
            }
            $this->ldap_dn_user=$array["LDAP_DN"];

        }

        $cache["ldap_dn_user"]=$this->ldap_dn_user;
        $cache["WINDOWS_SERVER_ADMIN"]=$this->WINDOWS_SERVER_ADMIN;
        $cache["WINDOWS_DNS_SUFFIX"]=$this->WINDOWS_DNS_SUFFIX;
        $cache["suffix"]=$this->suffix;
        $cache["ldap_host"]=$this->ldap_host;
        $cache["ldap_port"]=$this->ldap_port;
        $cache["ldap_host2"]=$this->ldap_host2;
        $cache["ldap_ssl"]=$this->ldap_ssl;
        $cache["LDAP_RECURSIVE"]=$this->LDAP_RECURSIVE;
        $cache["ADNETIPADDR"]=$this->ldap_ipaddr;
        $GLOBALS["LoadAdSettingsFromSquid"]["CACHE"]=$cache;
        return true;
    }


    public function DumpDN($dn): array{

        if(!$this->ldap_connection){
            return array();
        }

        $res=@ldap_read($this->ldap_connection,$dn,"(objectClass=*)",array(),null,null,20);
        if(!$res){
            $this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n" .
                ldap_err2str(ldap_errno($this->ldap_connection));
            return array();
        }

        $hash=ldap_get_entries($this->ldap_connection,$res);
        return $hash[0];
    }

    private function NetAdsInfo(): array{
        $array=array();
        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        if($UseNativeKerberosAuth==1){return array();}
        $v=unserializeb64($GLOBALS["CLASS_SOCKETS"]->getFrameWork("samba.php?netadsinfo=yes"));
        if(!is_array($v)){
            if($GLOBALS["VERBOSE"]){echo "<p><strong>samba.php?netadsinfo=yes == NOT AN ARRAY</strong></p>\n";}
        }
        foreach ($v as $num=>$ligne){
            if(preg_match("#LDAP server:\s+(.+)#i", $ligne,$re)){$array["SERVER_IP"]=trim($re[1]);continue;}
            if(preg_match("#Bind Path:\s+(.+)#i", $ligne,$re)){$array["SUFFIX"]=trim($re[1]);continue;}
            if(preg_match("#LDAP port:\s+(.+)#i", $ligne,$re)){$array["LDAP_PORT"]=trim($re[1]);continue;}
            if(preg_match("#Realm:\s+(.+)#i", $ligne,$re)){$array["WINDOWS_DNS_SUFFIX"]=trim(strtolower($re[1]));continue;}
            if(preg_match("#LDAP server name:\s+(.+)#i", $ligne,$re)){$array["FQDN"]=$re[1];}


        }

        if(!isset($array["SERVER_IP"])){
            $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
            if(!is_array($array)){$array=array();}
            $array["SERVER_IP"]=$array["LDAP_SERVER"];
            $array["SUFFIX"]=$array["LDAP_SUFFIX"];
            $array["FQDN"]=$array["LDAP_SERVER"];
        }

        return $array;


    }




    function Ldap_search($dn,$filter,$attrs,$maxRow=0): array{
        if(!$this->ldap_connection){
            writelogs("Could not search without proper ldap connection", __CLASS__ . "/" . __FUNCTION__,__FILE__);
            return array();
        }
        if(!is_array($attrs)){
            $sr =@ldap_search($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
        }else{$sr =@ldap_search($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
        if ($sr) {
            return ldap_get_entries($this->ldap_connection,$sr);
        }
         $this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$dn\n$filter\n" . ldap_err2str(ldap_errno($this->ldap_connection));
         writelogs("ERROR Ldap_search : $filter in $dn" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);
         return array();

    }

    function Ldap_list($dn,$filter,$attrs,$maxRow=0): array {
        if(!$this->ldap_connection){
            writelogs("Could not search without proper ldap connection", __CLASS__ . "/" . __FUNCTION__,__FILE__);
            return array();
        }
        if(!is_array($attrs)){
            $sr =@ldap_list($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
        }else{$sr =@ldap_list($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
        if ($sr) {
            return ldap_get_entries($this->ldap_connection,$sr);
        }
        $this->ldap_last_error='Error LDAP search number ' .
            ldap_errno($this->ldap_connection) .
            "\nAction:LDAP search\ndn:$dn\n$filter\n" .
            ldap_err2str(ldap_errno($this->ldap_connection));

       writelogs("ERROR Ldap_search : $filter in $dn" .
           ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ .
           "/" . __FUNCTION__,__FILE__);

       return array();

    }


    function UserSearch_formated($ou=null,$tofind=null,$maxRow=null): array
    {
        $FINAL=array();
        if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
        $hash=$this->find_users($ou,$tofind,$maxRow);

        for($i=0;$i<$hash["count"];$i++){
            $GroupName=utf8_decode($hash[$i]["samaccountname"][0]);
            writelogs("{$hash[$i]["samaccountname"][0]} = `$GroupName`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $dn=$hash[$i]["dn"];
            $description=utf8_decode($hash[$i]["displayname"][0]);
            $FINAL[$dn]=array($GroupName,$description,0);


        }
        return $FINAL;

    }



    function UserSearch($ou=null,$tofind=null,$maxRow=null): array
    {
        $hash=$this->find_users($ou,$tofind,$maxRow);
        $hash2=array();
        return array($hash,$hash2);

    }

    function _Get_dn_userid($userid):string{
        if($userid==null){return "";}
        $result="";
        if(isset($GLOBALS["LDAP_AD_FAILED"])) {
            if ($GLOBALS["LDAP_AD_FAILED"]) {
                return "";
            }
        }
        if($this->ldapFailed){
            return "";
        }

        if(!$this->ldap_connection){
            return "";
        }

        if(preg_match("#(.+?)@(.+)#",$userid,$re)){$userid=$re[1];}
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(sAMAccountName=$userid)");
        if($sr) {
            $result = ldap_get_entries($this->ldap_connection, $sr);
            return trim($result[0]["dn"]);
        }
        $this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . " Action:LDAP search\ndn:$this->suffix\n(sAMAccountName=$userid)" . ldap_err2str(ldap_errno($this->ldap_connection));
        if(function_exists("VERBOSE")){VERBOSE($this->ldap_last_error);}
        writelogs("Unable to find dn (sAMAccountName=$userid)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        return $result;

    }

    function hash_groups($ou,$fieldlist=0){
        $res=null;
        if($ou==null){
            writelogs("OU NULL !",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }
        $pos=strpos($ou,',');
        if($pos>0){$ou=substr($ou,0,$pos);}
        $ld =$this->ldap_connection;
        $cols=array("cn","description",'sAMAccountName',"dn");
        $dn="ou=$ou,$this->suffix";
        if($ou=="Domain Controllers"){$dn="CN=Users,$this->suffix";}
        if($fieldlist==1){$res=array();}
        if($fieldlist==2){$res=null;}
        if($fieldlist==0){$res=array();}

        if(!$ld){
            return array();
        }

        $sr = @ldap_search($ld,"$dn",'objectclass=group',$cols);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            if(!is_array($hash)){return array();}
            foreach ($hash as $num=>$ligne){
                if($ligne["samaccountname"][0]<>null){
                    switch ($fieldlist) {
                        case 1:$res[$ligne["dn"]]=$ligne["samaccountname"][0];break;
                        case 2;$res=$res . "{$ligne["dn"]};{$ligne["samaccountname"][0]}\n";break;
                        default:$res[$ligne["cn"][0]]=array("gid"=>$ligne["dn"],"description"=>$ligne["description"][0]);break;
                    }
                }

            }
        }else{
            writelogs("Unable to find groups in $dn",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }
        writelogs(count($res). " groups found for $ou ($dn)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        return $res;
    }


    function CountDeGroups($ou): int
    {
        return count($this->hash_groups($ou,null));

    }


    function search_users($pattern,$entriesNumber=50){



    }


    function search_groups($pattern,$entriesNumber=50): array
    {
        $this->ldap_last_error=null;$FINAL=array();
        if($pattern==null){$pattern="*";}
        if(strpos("  $pattern", '*')==0){$pattern=$pattern.'*';}
        if(!is_numeric($entriesNumber)){$entriesNumber=50;}
        $cols=array("cn","description",'sAMAccountName',"dn","member");
        $filter="(&(objectclass=group)(cn=$pattern))";
        //$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
        $sr = @ldap_search($this->ldap_connection,$this->suffix,$filter,$cols,null,$entriesNumber,15);
        if (!$sr) {
            $this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n$filter\n" .
                ldap_err2str(ldap_errno($this->ldap_connection));
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return array();}

        for($i=0;$i<$hash["count"];$i++){
            $GroupName=utf8_decode($hash[$i]["cn"][0]);
            writelogs("{$hash[$i]["cn"][0]} = `$GroupName` Maxentries:$entriesNumber",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

            $dn=$hash[$i]["dn"];
            $description=utf8_decode($hash[$i]["description"][0]);
            $FINAL[$dn]=array($GroupName,$description,$hash[$i]["member"]["count"]);


        }


        return $FINAL;
    }


    function dump_users_from_group($dn): array
    {
        $this->ldap_last_error=null;
        $recursive=false;$array=array();
        if($this->LDAP_RECURSIVE==1){$recursive=true;}

        if($GLOBALS["VERBOSE"]){echo "Dn = $dn<br>\n";}

        $props=$this->ObjectProperty($dn);
        $name=$props["name"];
        $use_ssl=false;
        if($this->ldap_ssl==1){$use_ssl=true;}
        if($this->ldap_port==636){$use_ssl=true;$this->ldap_ssl=1;}

        $ldap_host=$this->ldap_host;
        $ldap_host2=trim($this->ldap_host2);
        if(preg_match("#\/\/(.+?)\/#",$ldap_host,$re)){$ldap_host=$re[1];}
        $domain_controllers[]=$ldap_host;

        if($ldap_host2<>null) {
            if (preg_match("#\/\/(.+?)\/#", $ldap_host2, $re)) {
                $ldap_host2 = $re[1];
            }
        }

        $domain_controllers[]=$ldap_host2;
        if($ldap_host2<>null){
            $domain_controllers[]=$ldap_host2;
        }

        $options=array(
            'base_dn'=>$this->suffix,
            'ad_username'=>$this->WINDOWS_SERVER_ADMIN,
            'ad_password'=>$this->ldap_password,
            'recursive_groups'=>$recursive,
            'use_ssl'=>$use_ssl,
            'domain_controllers'=>$domain_controllers,
            'account_suffix'=>"@{$this->WINDOWS_DNS_SUFFIX}");


        try {
            $adldap = new adLDAP($options);
        }
        catch (Exception $e) {
            writelogs("Error $e $this->WINDOWS_SERVER_ADMIN@{$this->WINDOWS_DNS_SUFFIX} (ssl:$use_ssl)",__FUNCTION__,__FILE__,__LINE__);
            if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
            return array();
        }

        try {
            $adldap->authenticate($this->WINDOWS_SERVER_ADMIN,$this->ldap_password);}
        catch (Exception $e) {
            writelogs("Error $e $this->WINDOWS_SERVER_ADMIN@{$this->WINDOWS_DNS_SUFFIX} (ssl:$use_ssl)",__FUNCTION__,__FILE__,__LINE__);
            if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
            return array();
        }
        $MEMBERS=$adldap->group()->members($name);


        if(count($MEMBERS)==0){return array();}

        foreach ($MEMBERS as $num=>$uid){
            $uid=trim(strtolower($uid));
            $array[$uid]=true;
        }

        return $array;

    }



    function search_users_from_groupName($name): array
    {
        $this->ldap_last_error=null;
        $recursive=false;
        if($this->LDAP_RECURSIVE==1){$recursive=true;}

        if($GLOBALS["VERBOSE"]){echo "'base_dn'=>$this->suffix<br>\n";}
        if($GLOBALS["VERBOSE"]){echo "'ad_username'=>$this->WINDOWS_SERVER_ADMIN,<br>\n";}
        if($GLOBALS["VERBOSE"]){echo "'domain_controllers'=>array($this->ldap_host),<br>\n";}
        if($GLOBALS["VERBOSE"]){echo "'account_suffix'=>@{$this->WINDOWS_DNS_SUFFIX}<br>\n";}
        $use_ssl=false;
        if($this->ldap_ssl==1){
            $use_ssl=true;
        }
        $ldap_host=$this->ldap_host;
        $ldap_host2=trim($this->ldap_host2);
        if(preg_match("#\/\/(.+?)\/#",$ldap_host,$re)){$ldap_host=$re[1];}
        if($ldap_host2<>null) {
            if (preg_match("#\/\/(.+?)\/#", $ldap_host2, $re)) {
                $ldap_host2 = $re[1];
            }
        }

        $domain_controllers[]=$ldap_host;
        if($ldap_host2<>null) {$domain_controllers[]=$ldap_host2;}

        $options=array(
            'base_dn'=>$this->suffix,
            'ad_username'=>$this->WINDOWS_SERVER_ADMIN,
            'ad_password'=>$this->ldap_password,
            'recursive_groups'=>$recursive,
            'use_ssl'=>$use_ssl,
            'domain_controllers'=>$domain_controllers,
            'account_suffix'=>"@{$this->WINDOWS_DNS_SUFFIX}");


        try {
            $adldap = new adLDAP($options);
        }
        catch (Exception $e) {
            if($GLOBALS["VERBOSE"]){echo $e." $name:<br>\n";}
            return array();
        }

        try {
            $adldap->authenticate($this->WINDOWS_SERVER_ADMIN,$this->ldap_password);}
        catch (adLDAPException $e) {
            if($GLOBALS["VERBOSE"]){echo $e." $name: <br>\n";}
            return array();
        }
        if($GLOBALS["VERBOSE"]){echo "$name: authenticate $this->ldap_host/$this->WINDOWS_SERVER_ADMIN<br>\n";}
        $return= $adldap->group()->members($name);
        if(!is_array($return)){return array();}
        if(!$return){return array();}
        return $return;

    }

    function search_users_from_group($dn): array
    {
        $this->ldap_last_error=null;
        $recursive=false;$array=array();
        if($this->LDAP_RECURSIVE==1){$recursive=true;}

        if($GLOBALS["VERBOSE"]){echo "Dn = $dn<br>\n";}

        $props=$this->ObjectProperty($dn);
        $name=$props["name"];

        $use_ssl=false;
        if($this->ldap_ssl==1){$use_ssl=true;}

        $ldap_host=$this->ldap_host;
        $ldap_host2=trim($this->ldap_host2);
        if(preg_match("#\/\/(.+?)\/#",$ldap_host,$re)){$ldap_host=$re[1];}
        if($ldap_host2<>null) {
            if (preg_match("#\/\/(.+?)\/#", $ldap_host2, $re)) {
                $ldap_host2 = $re[1];
            }
        }

        $domain_controllers[]=$ldap_host;
        if($ldap_host2<>null) {$domain_controllers[]=$ldap_host2;}

        $options=array(
            'base_dn'=>$this->suffix,
            'ad_username'=>$this->WINDOWS_SERVER_ADMIN,
            'ad_password'=>$this->ldap_password,
            'recursive_groups'=>$recursive,
            'use_ssl'=>$use_ssl,
            'domain_controllers'=>$domain_controllers,
            'account_suffix'=>"@{$this->WINDOWS_DNS_SUFFIX}");


        try {
            $adldap = new adLDAP($options);
        }
        catch (Exception $e) {
            if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
            return array();
        }

        try {
            $adldap->authenticate($this->WINDOWS_SERVER_ADMIN,$this->ldap_password);}
        catch (adLDAPException $e) {
            if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
            return array();
        }
        $MEMBERS=$adldap->group()->members($name);
        if($GLOBALS["VERBOSE"]){echo "$name: FOUND ".count($MEMBERS)."\n";}

        if(count($MEMBERS)==0){return array();}

        foreach ($MEMBERS as $num=>$uid){
            $info=$adldap->user()->info($uid);
            $dn=$info[0]["dn"];
            if($GLOBALS["VERBOSE"]){echo $uid."->$dn\n";}
            $props=$this->ObjectProperty($dn);
            if(!is_array($props)){continue;}
            $array[$dn]=$props;
        }



        return $array;



    }



    public function ObjectProperty($dn): array{
        $this->ldap_failed=false;
        if(!$this->ldap_connection){
            $this->ldap_failed=true;
            return array();
        }


        $res=@ldap_read($this->ldap_connection,$dn,"(objectClass=*)",array(),null,null,20);
        if(!$res){
            $this->ldap_failed=true;
            $errstr=ldap_err2str(ldap_errno($this->ldap_connection));
            $this->ldap_last_error="Error LDAP search $errstr ($this->ldap_host:$this->ldap_port)";
            if(function_exists("VERBOSE")){VERBOSE("$dn $errstr",__LINE__);}
            @ldap_close($this->ldap_connection);
            return array();
        }

        $hash=ldap_get_entries($this->ldap_connection,$res);
        $Config=$hash[0];
        for($i=0;$i<$Config["objectclass"]["count"];$i++){
            $types[$Config["objectclass"][$i]]=true;

        }
        $ARRAY["SOURCE"]=$Config;
        $ARRAY["description"]=null;
        $ARRAY["mail"]=null;

        $cn=utf8_decode($Config["cn"][0]);
        $ARRAY["cn"]=$cn;


        if(isset($Config["description"])){$ARRAY["description"]=utf8_decode($Config["description"][0]);}
        if(isset($types["group"])){
            $ARRAY["TYPE"]="group";
            $ARRAY["MEMBERS"]=$Config["member"]["count"];
            $ARRAY["name"]=$Config["name"][0];
            return $ARRAY;
        }
        if(isset($types["foreignSecurityPrincipal"])){
            $ARRAY["TYPE"]="group";
            return array();
        }
        if(isset($types["user"])){
            $ARRAY["TYPE"]="user";
        }


        $ARRAY["name"]=$Config["name"][0];
        $ARRAY["uid"]=$Config["samaccountname"][0];
        $ARRAY["displayname"]=$Config["displayname"][0];
        if(isset($Config["userprincipalname"])){$ARRAY["userprincipalname"]=$Config["userprincipalname"][0];}
        if(isset($Config["mail"][0])){$ARRAY["mail"]=$Config["mail"][0];}
        return $ARRAY;
    }



    function hash_get_ou($has_key=false): array{
        $ld =$this->ldap_connection;
        if(!$this->ldap_connection){
            return array();
        }
        $suffix="$this->suffix";
        $res=array();
        $arr=array("ou");
        $sr = @ldap_list($ld,$suffix,'(&(objectclass=organizationalUnit)(ou=*))',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            if(!is_array($hash)){return array();}

            foreach ($hash as $num=>$ligne){
                $ou=trim($ligne["ou"][0]);
                if($ou==null){continue;}
                if($has_key){$res[$ou]=$ou;}else{$res[]=$ou;}
            }
        }



        return $res;

    }

    public function find_members($tofind){
        $hash=$this->find_users(null,$tofind);
        for($i=0;$i<$hash["count"];$i++){
            $this->members[]=$hash[$i]["samaccountname"][0];
            $this->ARRAY_MEMBERS[$hash[$i]["samaccountname"][0]]=$hash[$i]["samaccountname"][0];
        }

    }

    public function find_users($ou,$tofind=null,$maxRow=null): array
    {
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        $cc=new external_ad_search();
        $dn="$cc->suffix";
        if($maxRow==null){$maxRow=150;}
        $filter="(&(objectClass=user)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
        $attrs=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
        if(trim($ou)=="Domain Controllers"){$dn="CN=Users,$cc->suffix";}

        $hash=$cc->Ldap_search($dn,$filter,$attrs,$maxRow);
        writelogs("search $tofind in `$dn` {$hash["count"]} element(s)...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if(!is_array($hash)){return array();}
        return $hash;

    }


    public function find_members_groups($dn,$pattern){

    }

    public function COUNT_DE_USERS(){

    }
}