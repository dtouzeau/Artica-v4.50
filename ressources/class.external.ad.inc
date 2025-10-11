<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/logs.inc');
include_once(dirname(__FILE__).'/class.ad-agent.inc');
include_once(dirname(__FILE__).'/class.reverse-proxy.privs.inc');
class external_ad_search{
    private $ldap_connection;
    public $ou;
    public $error;
    public $suffix=null;
    public $VERBOSED_ARRAY=array();
    private $connectionEncoded=null;
    public $IsError=false;
    private $UserConnected=null;
    private $debug=false;
    private $verbose=false;
    public $ldap_error;
    private $ldap_password;
    private $adagenthaport = 8080;
    private $ActiveDirectoryIndex="Null";
    private $ActiveDirectoryIndexOnly=false;
    public $ADUserCanConnect=0;
    private $SquidAddkerAlernates=array();
    public $KerbAuthInfos=array();
    public $ACTIVE_DIRECTORY_INFO=null;
    public $TESTED_SERVERS=array();
    public $userDN=null;
    function __construct($connectionEncoded=null,$ActiveDirectoryIndex="Null"){
        if(!isset($GLOBALS["AS_ROOT"])){
            if(posix_geteuid()>0){$GLOBALS["AS_ROOT"]=false;}else{$GLOBALS["AS_ROOT"]=true;}
        }
        $this->SquidAddkerAlernates=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAddkerAlernates"));
        if($connectionEncoded<>null){$this->connectionEncoded=$connectionEncoded;}

        if(is_numeric($ActiveDirectoryIndex)){
            if(function_exists("VERBOSE")){VERBOSE("this->ActiveDirectoryIndex=$ActiveDirectoryIndex",__LINE__);}
            $this->ActiveDirectoryIndex=$ActiveDirectoryIndex;
        }
        $AD_Agent_LBL_Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AD_Agent_LBL_Port"));
        if($AD_Agent_LBL_Port==0){
            $AD_Agent_LBL_Port=8080;
        }
        $this->adagenthaport = $AD_Agent_LBL_Port;
        $this->VERBOSED_ARRAY=array();
        $this->ADLdap_init();
        $this->BuildDefault();
    }

    public function set_verbose(){
        $this->verbose=true;
    }
    private function ADLdap_init():bool{
        if($GLOBALS["VERBOSE"]){
            ini_set('display_errors', 1);
            ini_set('html_errors',0);
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
        }
        if(class_exists("adLDAP")){return false;}
        $lib=dirname(__FILE__)."/externals/adLDAP/adLDAP.php";
        if(!is_file($lib)){return false;}
        if($GLOBALS["VERBOSE"]){echo "Include $lib\n";}
        include($lib);
        return true;
    }
    private function ADLdap_options(){

        $array=$this->KerbAuthInfos;
        $GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
        $array["ADUserCanConnect"]=intval($this->KerbAuthInfos["ADUserCanConnect"]);
        $this->ADUserCanConnect=intval($array["ADUserCanConnect"]);

        if(trim($array["LDAP_SERVER"])==null){
            $array["LDAP_SERVER"]=$array["fullhosname"];
        }

        $recursive=false;
        if(!isset($array["RECURSIVE_GROUPS"])){$array["RECURSIVE_GROUPS"]=0;}
        if($array["RECURSIVE_GROUPS"]==1){
            if($GLOBALS["VERBOSE"]){echo "RECURSIVE_GROUPS = TRUE\n";}
            $recursive=true;
        }

        if(strpos($array["WINDOWS_SERVER_ADMIN"],"@")>0){
            $TR=explode("@",$array["WINDOWS_SERVER_ADMIN"]);
            $array["WINDOWS_SERVER_ADMIN"]=$TR[0];
            $array["WINDOWS_DNS_SUFFIX"]=$TR[1];
        }

        $use_ssl=false;

        if(isset($array["LDAP_PORT"])){
            if(intval($array["LDAP_PORT"])==636){
                $array["LDAP_SSL"]=1;
            }
        }


        if(isset($array["LDAP_SSL"])){
            if(intval($array["LDAP_SSL"])==1){
                $use_ssl=true;
            }
        }



        $domain_controllers[]=$array["LDAP_SERVER"];
        if($array["LDAP_SERVER2"]<>null){
            $domain_controllers[]=$array["LDAP_SERVER2"];
        }



        $options=array(
            'base_dn'=>$GLOBALS["SUFFIX"],
            'ad_username'=>$array["WINDOWS_SERVER_ADMIN"],
            'ad_password'=>$array["WINDOWS_SERVER_PASS"],
            'recursive_groups'=>$recursive,
            'use_ssl'=>$use_ssl,
            'domain_controllers'=>$domain_controllers,
            'account_suffix'=>"@{$array["WINDOWS_DNS_SUFFIX"]}");

        return $options;

    }

    public function FindParametersByDN($dn){
        $ARRAY=array();
        $sock=new sockets();
        if($this->connect()){
            $sr=@ldap_read($this->ldap_connection, $dn, "objectclass=*",array());
            if($sr){
                $entries=ldap_get_entries($this->ldap_connection,$sr);
                if(intval($entries["count"])>0){
                    $ARRAY["dn"]=$dn;
                    $ARRAY["description"]=$entries[0]["description"][0];
                    $ARRAY["samaccountname"]=$entries[0]["samaccountname"][0];
                    $ARRAY["LDAP_SERVER"]=$this->KerbAuthInfos["LDAP_SERVER"];
                    $ARRAY["LDAP_PORT"]=$this->KerbAuthInfos["LDAP_PORT"];
                    $ARRAY["LDAP_SUFFIX"]=$this->KerbAuthInfos["LDAP_SUFFIX"];
                    $ARRAY["LDAP_DN"]=$this->BuildAdUser();
                    $ARRAY["LDAP_PASSWORD"]=$this->KerbAuthInfos["WINDOWS_SERVER_PASS"];
                    return $ARRAY;
                }
            }
        }

        $ActiveDirectoryConnections=unserialize($sock->GET_INFO("ActiveDirectoryConnections"));
        if(count($ActiveDirectoryConnections)==0){VERBOSE("ActiveDirectoryConnections === 0",__LINE__);return array();}
        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            if($GLOBALS["VERBOSE"]){VERBOSE("Find: Connect {$ligne["LDAP_SERVER"]}....",__LINE__);}
            if(!$this->connect($ligne)){continue;}
            $sr=@ldap_read($this->ldap_connection, $dn, "objectclass=*",array());
            if($sr){
                $entries=ldap_get_entries($this->ldap_connection,$sr);
                if(intval($entries["count"])>0){
                    $ARRAY["dn"]=$dn;
                    $ARRAY["description"]=null;
                    if(isset($entries[0]["description"][0])) {
                        $ARRAY["description"] = $entries[0]["description"][0];
                    }
                    $ARRAY["samaccountname"]=$entries[0]["samaccountname"][0];
                    $ARRAY["LDAP_SERVER"]=$ligne["LDAP_SERVER"];
                    $ARRAY["LDAP_PORT"]=$ligne["LDAP_PORT"];
                    $ARRAY["LDAP_SUFFIX"]=$ligne["LDAP_SUFFIX"];
                    $ARRAY["LDAP_DN"]=$ligne["LDAP_DN"];
                    $ARRAY["LDAP_PASSWORD"]=$ligne["LDAP_PASSWORD"];
                    if(isset($ligne["LDAP_SSL"])){$ARRAY["LDAP_SSL"]=$ligne["LDAP_SSL"];}
                    if(isset($ligne["ADNETIPADDR"])){$ARRAY["ADNETIPADDR"]=$ligne["ADNETIPADDR"];}
                    VERBOSE("Find: Saving Encoded Connection",__LINE__);
                    $this->connectionEncoded=base64_decode(serialize($ARRAY));
                    return $ARRAY;
                }
            }

        }

        return $ARRAY;

    }

    public function active_directory_ListOus($ouName){
        if(!$this->connect()){

            return false;

        }
        $suffix=$this->suffix;
        $pattern="(&(objectClass=organizationalUnit)(ou=$ouName))";
        if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Search $pattern in Suffix = $suffix</span><br>\n";}
        $sr=@ldap_search($this->ldap_connection, $suffix, "$pattern",array("dn","ou"));
        if(!$sr){
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $this->error=$error;
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>SR IS FALSE!! ($error)</span><br>\n";}
            $this->CloseConnection();
            return false;
        }
        $entries=ldap_get_entries($this->ldap_connection,$sr);

        if(!isset($entries["count"])){
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $this->error="No entries,$error";
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>No COUNT, $error</span><br>\n";}
            return false;
        }


        if($entries["count"]==0){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>COUNT==0</span><br>\n";}
            $this->error="Count=0";
            $this->CloseConnection();
            return false;
        }

        if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Entries: {$entries["count"]}</span><br>\n";}
        $RESULTS=array();
        foreach ($entries as $key=>$array){
            if(!is_numeric($key)){continue;}
            if(isset($array["ou"][0])){
                $ouName=$array["ou"][0];
                $RESULTS[$array["dn"]]=$ouName;
                continue;
            }
            if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Entry $key...</span><br>\n";}


        }

        return $RESULTS;

    }


    public function member_infos($dn):array{
        if($this->connect()){
            $filter=array();
            $sr=@ldap_read($this->ldap_connection, $dn, "objectclass=*");
            if($sr){$entries=ldap_get_entries($this->ldap_connection,$sr);
                $this->VERBOSE("$dn OK entries:".count($entries),__LINE__);

                if($entries["count"]>0){
                    $this->CloseConnection();
                    return $entries;
                }
                if(!isset($entries["count"])){
                    $error=ldap_err2str(ldap_errno($this->ldap_connection));
                    $this->VERBOSE("$error",__LINE__);
                }
            }
        }

        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(count($ActiveDirectoryConnections)==0){
            $this->VERBOSE("No ActiveDirectoryConnections return none",__LINE__);
            return array();
        }
        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Connect {$ligne["LDAP_SERVER"]}....</span><br>\n";}
            if(!$this->connect($ligne)){continue;}
            $sr=@ldap_read($this->ldap_connection, $dn, "objectclass=*",array());
            if($sr){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>ldap_get_entries($this->ldap_connection,$sr)</span><br>\n";}
                $entries=ldap_get_entries($this->ldap_connection,$sr);
                if($entries["count"]>0){
                    $this->CloseConnection();
                    return $entries;
                }
                $error=ldap_err2str(ldap_errno($this->ldap_connection));
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$error</span><br>\n";}
                return array();
            }
        }

        return array();

    }

    private function Connect2($username,$password){

        if($this->KerbAuthInfos["LDAP_SERVER"]==null){
            $this->Debuglogs("Failed to connect to DC Could not obtain `LDAP_SERVER`",__FUNCTION__,__LINE__);
            $this->error="Failed to connect to DC Could not obtain `LDAP_SERVER`";
            $this->VERBOSE("$this->error",__LINE__);
            return;
        }

        if(intval($this->KerbAuthInfos["LDAP_PORT"])==636){
            $this->KerbAuthInfos["LDAP_SSL"]=1;
        }

        $ServerDN="ldap://{$this->KerbAuthInfos["LDAP_SERVER"]}:{$this->KerbAuthInfos["LDAP_PORT"]}";
        if(isset($this->KerbAuthInfos["LDAP_SSL"])){
            if(intval($this->KerbAuthInfos["LDAP_SSL"])==1){
                if(!preg_match("#^ldaps:#",$this->KerbAuthInfos["LDAP_SERVER"])) {
                    $ServerDN= "ldaps://{$this->KerbAuthInfos["LDAP_SERVER"]}/";
                    $this->KerbAuthInfos["LDAP_PORT"] = null;

                }
            }
        }

        $this->ldap_connection=@ldap_connect($ServerDN);
        if(!$this->ldap_connection){
            $this->error="Failed to connect to DC $ServerDN";
            $GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$this->error";
            $this->VERBOSE("$this->error",__LINE__);
            $this->CloseConnection();
            return false;
        }
        if(!isset($this->KerbAuthInfos["LDAP_SSL"])){
            $this->KerbAuthInfos["LDAP_SSL"]=0;
        }
        ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
        if(intval($this->KerbAuthInfos["LDAP_SSL"])==1) {
            ldap_set_option($this->ldap_connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        }

        if(!preg_match("#.*?@.+?#", $username)){
            $GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}:$password";
            $username="$username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}";
        }

        $this->VERBOSE("ldap_bind as <strong>$username</strong>",__LINE__);
        $bind=ldap_bind($this->ldap_connection, $username, $password);

        if(!$bind){
            $this->SyslogAd("{$this->KerbAuthInfos["LDAP_SERVER"]}:{$this->KerbAuthInfos["LDAP_PORT"]} $username bind failed");
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$error";
            $this->Debuglogs("Failed to login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} - $username `$error`",__FUNCTION__,__LINE__);
            $this->error="Failed to login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} - $username `$error`";
            $this->VERBOSE($this->error,__LINE__);
            return false;
        }
        $this->VERBOSE("login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} TRUE",__LINE__);
        return true;

    }
    private function SyslogAd($text){
        if(!function_exists("openlog")){return true;}
        $f=basename(__FILE__);
        $text="[$f]: $text";
        openlog("activedirectory", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }
    function CheckUserAuth($username,$password){
        $password=trim($password);
        if($password==null){return false;}


        if(!$this->Connect2($username,$password)){
            $this->VERBOSE("Connect2 return failed",__LINE__);
            return false;
        }



        $this->VERBOSE("Patch: 2019-12-05 (3): LoadMember($username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]})",__LINE__);
        $Hash=$this->LoadMember("$username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}",$username,$password);
        if(isset($Hash["ARTICA_DN"])){$this->userDN=$Hash["ARTICA_DN"];}
        $this->ACTIVE_DIRECTORY_INFO=$Hash;
        return true;


    }

    public function checkAuthADAgent($data=null,$username=null):bool{
        $encdata=urlencode($data);
        $adagent=new ADAgent();
        $res=$adagent->checkAuthADAgent($encdata);
//        $uri="http://127.0.0.1:{$this->adagenthaport}/validate/auth/$encdata";
//        $this->VERBOSE(" $uri",__LINE__);
//        $curl=new ccurl($uri);
//        $curl->NoLocalProxy();
//        if(!$curl->get()){
//            $this->VERBOSE("HTTP Engine error $curl->error");
//            return false;
//
//        }
//        $res=$curl->data;

        if($res=="true"){

            $Hash=$this->getMemberInfoADAgent($username);
            if($Hash==null){
                return false;
            }
            $this->VERBOSE("Patch: 2019-12-05 (3): getMemberInfoADAgent($username)",__LINE__);
            if(isset($Hash["ARTICA_DN"])){$this->userDN=$Hash["ARTICA_DN"];}
            $this->ACTIVE_DIRECTORY_INFO=$Hash;
            return true;
        }
        return false;

    }

    public function getMemberInfoADAgent($username=null){
        $KeySession=md5(__CLASS__.__FUNCTION__.$username);

        if(isset($GLOBALS[$KeySession])){
            if(isset($GLOBALS[$KeySession]["ARTICA_OU"])){
                $this->ou=$GLOBALS[$KeySession]["ARTICA_OU"];
                return $GLOBALS[$KeySession];
            }
        }
        if($username==null){
            if (!$this->connect()) {
                $this->VERBOSE("LoadMember::Connection failed, aborting..", __LINE__);
                return null;
            }
        }
        $encuser=urlencode($username);
        $adagent=new ADAgent();
        $res=$adagent->getMemberInfo($encuser);
//        $uri="http://127.0.0.1:{$this->adagenthaport}/get/member/info/$encuser";
//        $this->VERBOSE(" $uri",__LINE__);
//        $curl=new ccurl($uri);
//        $curl->NoLocalProxy();
//        if(!$curl->get()){
//            $this->VERBOSE("HTTP Engine error $curl->error");
//            return "";
//
//        }
//        $res=$curl->data;
        if ($res=="false"){
            return "";
        }
        $attrs=json_decode($res,true);
        if (is_array($attrs)){
            if($this->verbose){
                foreach ($attrs as $key=>$array){
                    if(is_numeric($key)){continue;}
                    $this->VERBOSE("$key={$array[0]}",__LINE__);
                }
            }

            $DN=$attrs["data"]["distinguishedName"];
            $attrs["data"]["ARTICA_DN"]=$DN;

            $EXPLODED=explode(",",$DN);
            $prefix=$EXPLODED[0].",";
            $DN_PREFIXED=str_replace($prefix, "", $DN);
            if($this->verbose){$this->VERBOSE("$username=ARTICA_DN[$DN] (DN)",__LINE__);}
//            $dnfromusername="";
//            if(preg_match("#^(.+?)@(.+)#",$username,$re)){
//                $dom= $re[2];
//                $x=explode(".",$dom);
//                foreach($x as $key) {
//                    $upper=strtoupper($key);
//                    $dnfromusername.="DC=$upper,";
//                }
//                $dnfromusername= rtrim($dnfromusername, ",");
//
//            }

//            if(strtolower(trim($DN_PREFIXED))==strtolower(trim("CN=Users,{$dnfromusername}"))){
//                $this->ou=$dnfromusername;
//                $attrs["ARTICA_OU"]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
//            }else{
//                $EXPLODED=explode(",",$DN);
//                foreach ($EXPLODED as $a=>$b){if(preg_match("#OU=(.+)#i", $b,$re)){$attrs["ARTICA_OU"]=$re[1];$this->ou=$re[1];}}
//            }
               $EXPLODED=explode(",",$DN);
                foreach ($EXPLODED as $a=>$b){if(preg_match("#OU=(.+)#i", $b,$re)){$attrs["ARTICA_OU"]=$re[1];$this->ou=$re[1];}}

            $GLOBALS[$KeySession]=$attrs["data"];
            return $attrs["data"];
        }
        return "";

    }





    function BuildDefault_ldap_server(){
        if(isset($this->KerbAuthInfos["ADNETIPADDR"])){return $this->KerbAuthInfos["ADNETIPADDR"];}
        if(isset($this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"])){$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
        $SMB=$this->SAMBA_GetNetAdsInfos();
        return $SMB["LDAP server"];

    }
    private function timeminSince($fromtime){
        if(!is_numeric($fromtime)){return 1000000;}
        $data2 = time();
        $difference = ($data2 - $fromtime);
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        return $results;
    }

    public function NumUsers(){
        $NET_RPC_INFOS=$this->NET_RPC_INFOS();
        return trim($NET_RPC_INFOS["Num users"]);

    }

    function NET_RPC_INFOS(){
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        $sock=new sockets();
        if(isset($GLOBALS["NET_RPC_INFOS_MEM"])){
            if(!is_array($GLOBALS["NET_RPC_INFOS_MEM"])){
                unset($GLOBALS["NET_RPC_INFOS_MEM"]);}else{
                if(count($GLOBALS["NET_RPC_INFOS_MEM"])==1){unset($GLOBALS["NET_RPC_INFOS_MEM"]);}
            }
        }

        if(!isset($GLOBALS["NET_RPC_INFOS_MEM"])){
            $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NET_RPC_INFOS"));
            if(!is_array($array)){$array=array();}
        }else{
            $array=$GLOBALS["NET_RPC_INFOS_MEM"];
        }

        if($GLOBALS["VERBOSE"]){echo "NET_RPC_INFOS count: ".count($array)."\n";}
        if(is_array($array)){
            if(count($array)>1){
                $time=$this->timeminSince($array["TIME"]);
                if($time<240){return $array;}
            }
        }
        $unix=new unix();
        $net=$unix->find_program("net");
        $array["USER"]=$this->BuildAdUser();
        $array["PASSWD"]=$unix->shellEscapeChars($this->KerbAuthInfos["WINDOWS_SERVER_PASS"]);

        if(!is_file($net)){return array();}
        $cmd="$net rpc info -U {$array["USER"]}%{$array["PASSWD"]} 2>&1";
        if($GLOBALS["VERBOSE"]){echo "NET_RPC_INFOS $cmd\n";}
        exec($cmd,$results);
        foreach ($results as $key=>$line){
            if(!preg_match("#(.+?):(.+)#", $line,$re)){continue;}
            $ARRAY[trim($re[1])]=trim($re[2]);

        }


        $ARRAY["TIME"]=time();
        $GLOBALS["NET_RPC_INFOS_MEM"]=$ARRAY;

        $sock->SaveConfigFile(base64_encode(serialize($ARRAY)), "NET_RPC_INFOS");
        return $ARRAY;

    }

    function PaseEncodedConnection():array{
        if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
        $MainConfig=array();
        $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($this->connectionEncoded);
        if(!is_array($array)){$array=array();}
        if(!isset($array["DEBUG"])){$array["DEBUG"]=false;}
        foreach ($array as $key=>$line){
            if($GLOBALS["VERBOSE"]) {
                if (function_exists("VERBOSE")) {
                    if(is_array($line)){
                        foreach ($line as $k=>$v){
                            VERBOSE("$key = $k => $v", __LINE__);
                        }
                    }else{
                        VERBOSE("$key = $line", __LINE__);
                    }

                }
            }
            $MainConfig[$key]=$line;
        }
        if(!isset($array["LDAP_SERVER"])){return $this->KerbAuthInfos;}
        if($array["LDAP_SERVER"]==null){return $this->KerbAuthInfos;}

        $this->debug=$array["DEBUG"];
        $this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
        $this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
        $this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["LDAP_PASSWORD"];
        $this->KerbAuthInfos["LDAP_DN"]=$array["LDAP_DN"];
        if(isset($array["LDAP_FILTER"])){
            if(preg_match("#^\?(.*)#", $array["LDAP_FILTER"],$re)){
                if(strpos($re[1], "?")>0){$re[1]=substr($re[1], 0,strpos($re[1], "?"));}
                $array["LDAP_FILTER"]="({$re[1]}=%s)";
                $this->KerbAuthInfos["LDAP_FILTER"]=$array["LDAP_FILTER"];
                $this->KerbAuthInfos["LDAP_ATTR"]=$re[1];
            }else{
                if($GLOBALS["VERBOSE"]){echo "{$array["LDAP_FILTER"]} #^\?(.+?)[\?$]#  no match\n";}
            }

        }


        if(isset($array["LDAP_PORT"])){$this->KerbAuthInfos["LDAP_PORT"]=$array["LDAP_PORT"];}
        $this->VERBOSE("PaseEncodedConnection: {$this->KerbAuthInfos["LDAP_SERVER"]}:{$this->KerbAuthInfos["LDAP_PORT"]} / {$this->KerbAuthInfos["LDAP_SUFFIX"]}",__LINE__);
        return $MainConfig;
    }


    private function BuildExternalAD(){
        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        $ActiveDirectoryConnections=$ActiveDirectoryConnections[$this->ActiveDirectoryIndex];

        if($GLOBALS["VERBOSE"]){
            foreach ($ActiveDirectoryConnections as $key=>$val){
                $this->VERBOSE("BuildExternalAD(): [$key]=[$val]",__LINE__);
            }
        }

        if(!isset($ActiveDirectoryConnections["LDAP_SERVER"])){return false;}
        if($ActiveDirectoryConnections["LDAP_SERVER"]==null){return false;}

        if(!isset($ActiveDirectoryConnections["LDAP_DN"])){return false;}
        if($ActiveDirectoryConnections["LDAP_DN"]==null){return false;}

        if(!isset($ActiveDirectoryConnections["LDAP_PASSWORD"])){return false;}
        if($ActiveDirectoryConnections["LDAP_PASSWORD"]==null){return false;}

        if(!isset($ActiveDirectoryConnections["LDAP_SUFFIX"])){return false;}
        if($ActiveDirectoryConnections["LDAP_SUFFIX"]==null){return false;}

        $this->connectionEncoded=base64_encode(serialize($ActiveDirectoryConnections));
        if(preg_match("#^(.+?)@(.+)#", $ActiveDirectoryConnections["LDAP_DN"],$re)){
            $ActiveDirectoryConnections["LDAP_DN"]=$re[1];
            $ActiveDirectoryConnections["LDAP_DOMAIN"]=$re[2];
        }

        $this->KerbAuthInfos["LDAP_SERVER"]=$ActiveDirectoryConnections["LDAP_SERVER"];
        $this->KerbAuthInfos["LDAP_SUFFIX"]=$ActiveDirectoryConnections["LDAP_SUFFIX"];
        $this->KerbAuthInfos["ADNETIPADDR"]=$ActiveDirectoryConnections["LDAP_SERVER"];
        $this->KerbAuthInfos["LDAP_PORT"]=$ActiveDirectoryConnections["LDAP_PORT"];
        $this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$ActiveDirectoryConnections["LDAP_PASSWORD"];
        $this->KerbAuthInfos["LDAP_PASSWORD"]=$ActiveDirectoryConnections["LDAP_PASSWORD"];
        $this->KerbAuthInfos["RECURSIVE_GROUPS"]=1;
        $this->KerbAuthInfos["LDAP_DN"]="{$ActiveDirectoryConnections["LDAP_DN"]}@{$ActiveDirectoryConnections["LDAP_DOMAIN"]}";
        $this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]="{$ActiveDirectoryConnections["LDAP_DN"]}";
        $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$ActiveDirectoryConnections["LDAP_DOMAIN"];
        $this->VERBOSE("BuildExternalAD: {$this->KerbAuthInfos["LDAP_SERVER"]}:{$this->KerbAuthInfos["LDAP_PORT"]} / {$this->KerbAuthInfos["LDAP_SUFFIX"]}",__LINE__);
        return true;
    }

    private function VERBOSE($text,$line=0){
        if($GLOBALS["VERBOSE"]){
            if(function_exists("VERBOSE")){
                VERBOSE($text,$line);
            }
        }
        $this->VERBOSED_ARRAY[]="$text class.external.ad.inc [$line]";
    }

    private function BuildDefault(){

        if($this->connectionEncoded<>null){
            $this->VERBOSE("BuildDefault() PaseEncodedConnection",__LINE__);
            if($this->PaseEncodedConnection()){return;}
        }

        $this->VERBOSE("BuildDefault() ActiveDirectoryIndex == $this->ActiveDirectoryIndex",__LINE__);

        if(is_numeric($this->ActiveDirectoryIndex)){
            if($this->BuildExternalAD()){
                $this->ActiveDirectoryIndexOnly=True;
                return;
            }
        }


        if(!isset($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"])){$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=null;}
        if(!isset($this->KerbAuthInfos["ADUserCanConnect"])){$this->KerbAuthInfos["ADUserCanConnect"]=0;}
        $this->LoadSambaInformations();
        $this->ADUserCanConnect=intval($this->KerbAuthInfos["ADUserCanConnect"]);
        if($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]==null){return;}
        if(!isset($this->KerbAuthInfos["LDAP_SERVER"])){$this->KerbAuthInfos["LDAP_SERVER"]=$this->BuildDefault_ldap_server();}
        if(!isset($this->KerbAuthInfos["LDAP_PORT"])){$this->KerbAuthInfos["LDAP_PORT"]=389;}
        if(!isset($this->KerbAuthInfos["LDAP_SUFFIX"])){
            $this->VERBOSE("LDAP_SUFFIX not set ->SAMBA_GetNetAdsInfos() ",__LINE__);
            $SMB=$this->SAMBA_GetNetAdsInfos();
            $this->KerbAuthInfos["LDAP_SUFFIX"]=$SMB["Bind Path"];
        }
        if($this->KerbAuthInfos["ADNETIPADDR"]==null){
            $SMB=$this->SAMBA_GetNetAdsInfos();
            $this->KerbAuthInfos["ADNETIPADDR"]=$SMB["KDC server"];
        }


        if(!isset($this->KerbAuthInfos["KerbAuthInfos"]["RECURSIVE_GROUPS"])){$this->KerbAuthInfos["KerbAuthInfos"]["RECURSIVE_GROUPS"]=0;}
        $this->suffix=$this->KerbAuthInfos["LDAP_SUFFIX"];

    }

    function Ldap_search($dn,$filter,$attrs,$maxRow=0){
        if(!$this->connect()){return array();}

        if(!is_array($attrs)){
            $sr =@ldap_search($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
        }else{$sr =@ldap_search($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
        if (!$sr) {
            $this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$dn\n$filter\n" . ldap_err2str(ldap_errno($this->ldap_connection));
            $this->VERBOSE("ERROR Ldap_search : $filter in $dn" . ldap_err2str(ldap_errno($this->ldap_connection)),__LINE__);
            return array();
        }

        return ldap_get_entries($this->ldap_connection,$sr);

    }


    public function GroupsOfMember($dn){
        $res=array();
        $hash=array();
        $sock=new sockets();
        $UserAR=$this->member_infos($dn);
        $dn=$UserAR[0]["distinguishedname"][0];
        $sAMAccountName=$UserAR[0]["samaccountname"][0];
        $this->VERBOSE("GroupsOfMember: [AD] GroupsOfMember() $dn -> $sAMAccountName",__LINE__);

        if($sAMAccountName==null){return array();}
        $dnenc=ldap_escape($dn);
        $searchFilter="(&(objectClass=group)(member=$dnenc))";
        $filter=array("cn","description",'sAMAccountName',"dn","member","memberOf");


        if($this->connect()){
            $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter);
            if($sr){
                $entries=ldap_get_entries($this->ldap_connection,$sr);
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>{$this->KerbAuthInfos["LDAP_SUFFIX"]}....{$entries["count"]} Entries</span><br>\n";}
                if($entries["count"]>0){
                    foreach ($entries as $num=>$ligne){
                        if(!isset($ligne["samaccountname"][0])){continue;}
                        if($ligne["samaccountname"][0]==null){continue;}
                        $dn=$ligne["dn"];
                        $GroupName=$ligne["samaccountname"][0];
                        if($GLOBALS["VERBOSE"]){echo "[AD] GroupsOfMember() $dn -> $GroupName<br>\n";}
                        $res[$dn]=$GroupName;
                    }
                }
            }
        }

        if($this->ActiveDirectoryIndexOnly){return $res;}

        $ActiveDirectoryConnections=unserialize($sock->GET_INFO("ActiveDirectoryConnections"));
        if(count($ActiveDirectoryConnections)==0){
            VERBOSE("ActiveDirectoryConnections === 0",__LINE__);
            return $res;
        }

        if(!is_array($ActiveDirectoryConnections)){
            VERBOSE("ActiveDirectoryConnections === 0",__LINE__);
            return $res;

        }

        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Connect {$ligne["LDAP_SERVER"]}....</span><br>\n";}
            if(!$this->connect($ligne)){continue;}
            $sr =@ldap_search($this->ldap_connection,$ligne["LDAP_SUFFIX"],"$searchFilter",$filter);
            if($sr){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>ldap_get_entries($this->ldap_connection,$sr)</span><br>\n";}
                $entries=ldap_get_entries($this->ldap_connection,$sr);
                if($entries["count"]>0){
                    foreach ($entries as $num=>$ligne){
                        if(!isset($ligne["samaccountname"][0])){continue;}
                        if($ligne["samaccountname"][0]==null){continue;}
                        $dn=$ligne["dn"];
                        $GroupName=$ligne["samaccountname"][0];
                        if($GLOBALS["VERBOSE"]){echo "[AD] GroupsOfMember() $dn -> $GroupName<br>\n";}
                        $res[$dn]=$GroupName;
                    }
                    $this->CloseConnection();
                    continue;
                }
                $error=ldap_err2str(ldap_errno($this->ldap_connection));
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$error</span><br>\n";}
            }
        }






        return $res;
    }


    public function LoadMember($uid,$username=null,$password=null){
        $KeySession=md5(__CLASS__.__FUNCTION__.$uid);

        if(isset($GLOBALS[$KeySession])){
            if(isset($GLOBALS[$KeySession]["ARTICA_OU"])){
                $this->ou=$GLOBALS[$KeySession]["ARTICA_OU"];
                return $GLOBALS[$KeySession];
            }
        }

        if($username<>null){
            if(!$this->Connect2($username,$password)){
                $this->VERBOSE("LoadMember:: Connection failed with $username, aborting..", __LINE__);
                return null;
            }
        }else {

            if (!$this->connect()) {
                $this->VERBOSE("LoadMember::Connection failed, aborting..", __LINE__);
                return null;
            }
        }

        if(preg_match("#^(.+?)@(.+)#",$uid,$re)){
            $this->VERBOSE("Switch $uid to {$re[1]}",__LINE__);
            $uid=$re[1];
        }

        $searchFilter="(&(objectClass=user)(sAMAccountName=$uid))";
        $filter=array();
        $DN=$this->KerbAuthInfos["LDAP_SUFFIX"];
        if(strpos($uid, ",")>0){
            $DN=$uid;
            $searchFilter="(&(objectClass=user)(sAMAccountName=*))";
        }

        $this->VERBOSE("LoadMember::Search $searchFilter in DN:$DN",__LINE__);

        $sr =@ldap_search($this->ldap_connection,$DN,$searchFilter,$filter,null, null, 10);
        if (!$sr) {
            $this->VERBOSE("LoadMember::Bad search SUFFIX:{$DN} / $searchFilter",__LINE__);
            return null;
        }
        $entry_id = ldap_first_entry($this->ldap_connection,$sr);
        if(!$entry_id){
            $this->VERBOSE("LoadMember:: FATAL !$uid entry_id return FALSE!",__LINE__);
            if(strpos($uid, "@")>0){
                $tb=explode("@",$uid);
                $this->VERBOSE("LoadMember($tb[0])",__LINE__);
                return $this->LoadMember($tb[0]);
            }
            return null;
        }
        $attrs = ldap_get_attributes($this->ldap_connection, $entry_id);

        if($this->verbose){
            foreach ($attrs as $key=>$array){
                if(is_numeric($key)){continue;}
                if(!is_array($array)){
                    $this->VERBOSE("$key=$array",__LINE__);
                    continue;
                }
                $this->VERBOSE("$key=$array[0]",__LINE__);
            }
        }

        $DN=$attrs["distinguishedName"][0];
        $attrs["ARTICA_DN"]=$DN;

        $EXPLODED=explode(",",$DN);
        $prefix=$EXPLODED[0].",";
        $DN_PREFIXED=str_replace($prefix, "", $DN);
        if($this->verbose){$this->VERBOSE("$uid=ARTICA_DN[$DN] (DN)",__LINE__);}
        if(!isset($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){$this->KerbAuthInfos["ADNETBIOSDOMAIN"]=null;}

        if(strtolower(trim($DN_PREFIXED))==strtolower(trim("CN=Users,{$this->KerbAuthInfos["LDAP_SUFFIX"]}"))){
            $this->ou=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
            $attrs["ARTICA_OU"]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
        }else{
            $EXPLODED=explode(",",$DN);
            foreach ($EXPLODED as $a=>$b){if(preg_match("#OU=(.+)#i", $b,$re)){$attrs["ARTICA_OU"]=$re[1];$this->ou=$re[1];}}
        }

        $GLOBALS[$KeySession]=$attrs;
        return $attrs;
    }

    private function mem_getusage(){
        $time=0;
        $unit="KB";
        $mem=round(memory_get_usage(true)/1024);
        if($mem>1024){$mem=round($mem/1000,2);$unit="MB";}

        $xtime=microtime(true);
        if(!isset($GLOBALS["VERBOSE_MICROTIME"])){
            $GLOBALS["VERBOSE_MICROTIME"]=$xtime;
        }else{
            $time = $xtime - $GLOBALS["VERBOSE_MICROTIME"];
            $GLOBALS["VERBOSE_MICROTIME"]=$xtime;
            $time=round($time,2);
        }
        return $time."s ".$mem.$unit;
    }

    private function ConnectionIndexFromDN($dn){

        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(count($ActiveDirectoryConnections)==0){VERBOSE("ActiveDirectoryConnections === 0",__LINE__);return false;}
        foreach ($ActiveDirectoryConnections as $index=>$ligne) {
            if (!is_numeric($index)) {continue;}
            if (!isset($ligne["LDAP_SERVER"])) {continue;}
            $LDAP_SUFFIX=$ligne["LDAP_SUFFIX"];
            if(preg_match("#$LDAP_SUFFIX#i",$dn)){
                VERBOSE("($dn) Found Connection ID $index",__LINE__);
                return $ligne;}

        }

        return false;
    }


    public function DNDUMP($dn){
        $conf=$this->ConnectionIndexFromDN($dn);
        if(!$conf) {
            if (!$this->connect()) {
                return null;
            }
        }else{
            if (!$this->connect($conf)) {
                return null;
            }
        }
        $sr=@ldap_read($this->ldap_connection, $dn, "(objectClass=*)",array());
        if (!$sr) {
            if($GLOBALS["VERBOSE"]){echo "BAD $dn SR!<br>\n";}
            $this->VERBOSE("Bad search $dn ",__LINE__);return null;}
        $ARR=ldap_get_entries($this->ldap_connection,$sr);
        return $ARR[0];
    }


    public function all_mails($dn){
        $emailz=array();
        $DNDUMP=$this->DNDUMP($dn);
        if(isset($DNDUMP["userprincipalname"]["count"])){
            for($i=0;$i<$DNDUMP["userprincipalname"]["count"];$i++){
                $emailz[strtolower($DNDUMP["userprincipalname"][$i])]=$DNDUMP["userprincipalname"][$i];
            }
        }
        if(isset($DNDUMP["mail"]["count"])){
            for($i=0;$i<$DNDUMP["mail"]["count"];$i++){
                $emailz[strtolower($DNDUMP["mail"][$i])]=$DNDUMP["mail"][$i];
            }
        }

        if(isset($DNDUMP["proxyaddresses"]["count"])){
            for($i=0;$i<$DNDUMP["proxyaddresses"]["count"];$i++){
                if(!preg_match("#smtp:(.+?)#i", $DNDUMP["proxyaddresses"][$i],$re)){continue;}
                $DNDUMP["proxyaddresses"][$i]=strtolower($re[1]);
                $emailz[$DNDUMP["proxyaddresses"][$i]]=$DNDUMP["proxyaddresses"][$i];
            }
        }

        if(count($emailz)==0){return array();}


        foreach ($emailz as $index=>$HASH){
            $f[]=$index;
        }

        return $f;

    }


    private function MemberInfoByDN($dn,$recursive=false){
        if(!$this->connect()){return null;}
        $searchFilter="(objectClass=*)";

        $filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","objectClass","member","memberOf");
        $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
        if (!$sr) {$this->VERBOSE("Bad search $dn / $searchFilter",__LINE__);return null;}
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){$this->VERBOSE("Not an array $dn / $searchFilter",__LINE__);return null;}
        $AsGroup=false;




        for($i=0;$i<$hash[0]["objectclass"]["count"];$i++){
            $class=$hash[0]["objectclass"][$i];
            if($class=="group"){$AsGroup=true;break;}
        }




        if($AsGroup){
            if(!$recursive){return null;}
            $MembersCount=$hash[0]["member"]["count"];
            if($GLOBALS["VERBOSE"]){echo "MemberInfoByDN MembersCount -> $MembersCount\n";}
            for($i=0;$i<$MembersCount;$i++){
                if($GLOBALS["VERBOSE"]){echo "MemberInfoByDN $i = {$hash[0]["member"][$i]}\n";}
                $member=$this->MemberInfoByDN($hash[0]["member"][$i]);
                if(is_array($member)){
                    foreach ($member as $a=>$b){
                        if(trim($b)==null){continue;}
                        $f[$b]=$b;
                    }
                }else{
                    $f[$member]=$member;
                }

                return $f;
            }
        }


        if(!isset($hash[0]["samaccountname"][0])){
            $this->VERBOSE("samaccountname no such attribute",__LINE__);
            return null;}
        return $hash[0]["samaccountname"][0];
    }





    Public function MembersFromGroupName($GroupName){

        $this->ADLdap_init();
        $options=$this->ADLdap_options();
        try {
            $adldap = new adLDAP($options);
        }
        catch (adLDAPException $e) {
            if($GLOBALS["VERBOSE"]){echo "$e\n";}
            return array();
        }

        try {
            $adldap->authenticate($options["ad_username"],$options["ad_password"]);}
        catch (adLDAPException $e) {
            if($GLOBALS["VERBOSE"]){echo "$e\n";}
            return array();
        }

        $array=$adldap->group()->members($GroupName);
        if($GLOBALS["VERBOSE"]){echo "MembersFromGroupName $GroupName -> ".count($array)."\n";}
        return $array;


    }


    public function HashUsersFromGroupDN($dn,$recursive=false,$ReturnHash=false){
        if(function_exists("VERBOSE")){VERBOSE("$dn",__FILE__);}
        $ORGDN=$dn;
        $Tosend=array();
        if(isset($GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"])){return $GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"];}
        if(!$this->connect()){$this->VERBOSE("Error: connection failed",__LINE__);return null;}
        $f=array();
        $searchFilter="(objectClass=*)";

        $filter=array("member","memberOf");
        $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
        if (!$sr) {$this->VERBOSE("Bad search $dn / $searchFilter",__LINE__);return array();}
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){$this->VERBOSE("Not an array...$dn / $searchFilter",__LINE__);return array();}

        if(!isset($hash[0]["member"]["count"])){$hash[0]["member"]["count"]=0;}

        $MembersCount=$hash[0]["member"]["count"];
        $this->VERBOSE("MembersCount:$MembersCount in `$dn`",__LINE__);


        if($GLOBALS["VERBOSE"]){echo "MembersCount:$MembersCount in `$dn` line ". __LINE__."\n";}
        for($i=0;$i<$MembersCount;$i++){
            $MemberName=$this->MemberInfoByDN($hash[0]["member"][$i],$recursive);
            if($GLOBALS["VERBOSE"]){echo "{$hash[0]["member"][$i]} => $MemberName line ". __LINE__."\n";}
            if($MemberName==null){continue;}
            if(is_array($MemberName)){
                foreach ($MemberName as $a=>$b){
                    if(trim($b)==null){continue;}
                    $f[$b]=$b;
                }
            }else{
                $f[$MemberName]=$MemberName;
            }


        }



        if(isset($hash[0]["memberof"]["count"])){
            for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
                $dn1=$hash[0]["memberof"][0];
                $ff=$this->HashUsersFromGroupDN($dn1,$recursive);
                if(count($ff)>0){
                    foreach ($ff as $a=>$b){
                        if(trim($b)==null){continue;}
                        $f[$b]=$b;
                    }
                }

            }
        }

        if($ReturnHash){
            $GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"]=$f;
            return $f;}


        foreach ($f as $a=>$b){
            if(trim($b)==null){continue;}
            $Tosend[]=$b;
        }


        $GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"]=$Tosend;
        return $Tosend;

    }

    private function _DNinfos_local($dn){
        $f=array();
        $func=__FUNCTION__;
        if(!$this->connect()){
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}Connection failed</strong><br>\n";}
            return array();
        }
        $searchFilter="(objectClass=*)";
        $sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$f);
        if(!$sr){
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$dn -> $searchFilter failed</strong><br>\n";}
            return array();
        }
        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$dn -> ". count($hash)."</strong><br>\n";}
        return $hash;

    }

    public function DNinfos($dn){
        $f=array();
        $func=__FUNCTION__;
        if($GLOBALS["VERBOSE"]){echo "<hr>\n";}
        if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$dn</strong><br>\n";}
        $hash=$this->_DNinfos_local($dn);

        if(count($hash)>1){
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#46a346'>OK Active Directory LOCAL</strong><br>\n";}
            return $hash;
        }


        $searchFilter="(objectClass=*)";
        if(count($this->SquidAddkerAlernates)>0){
            reset($this->SquidAddkerAlernates);
            foreach ($this->SquidAddkerAlernates as $workgroup=>$MainArray){
                if(!$this->connect($MainArray)){
                    if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} $workgroup/Connection failed</strong><br>\n";}
                    continue;
                }
                $sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$f);
                $hash=@ldap_get_entries($this->ldap_connection,$sr);
                if(count($hash)>1){
                    if($GLOBALS["VERBOSE"]){echo "<strong style='color:#46a346'>OK Active Directory $workgroup</strong><br>\n";}
                    return $hash;}
                if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} $workgroup/{$MainArray["LDAP_SERVER"]} no such DN $dn</strong><br>\n";}
            }
        }



        return array();
    }

    public function SearchOuSimple($dn=null){
        $func=__CLASS__."/".__FUNCTION__."::";
        if(!$this->connect()){
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}Connection failed</strong><br>\n";}
            return array();
        }
        if($this->KerbAuthInfos["LDAP_SUFFIX"]==null){ $this->LoadSambaInformations(); }

        if($dn==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
        $searchFilter="(|(objectClass=organizationalUnit)(objectClass=container))";

        $filter=array();
        if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} DN &laquo;$dn&raquo; -$searchFilter</strong><br>\n";}
        $sr =@ldap_list($this->ldap_connection,$dn,"$searchFilter",$filter);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}`$error` ($errstr)</strong><br>\n";}
            return array();
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$hash["count"]} items</strong><br>\n";}

        $FINAL=array();
        if(!is_array($hash)){return array();}
        for($i=0;$i<$hash["count"];$i++){
            $tempar=$hash[$i];
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$tempar["dn"]}</strong><br>\n";}
            $dn=$tempar["dn"];
            $objectClass=array();
            for($z=0;$z<$tempar["objectclass"]["count"];$z++){
                $FINAL[$dn]["objectClass"][$tempar["objectclass"][$z]]=true;

            }

            if(isset($tempar["ou"])){
                $FINAL[$dn]["NAME"]=$tempar["ou"][0];
                continue;
            }

            if(isset($tempar["name"])){
                $FINAL[$dn]["NAME"]=$tempar["name"][0];
            }

        }
        return $FINAL;
    }

    public function searchGroupSimple($dn=null,$search=null){
        $func=__CLASS__."/".__FUNCTION__."::";
        if(!$this->connect()){
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}Connection failed</strong><br>\n";}
            return array();
        }
        if($dn==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
        $searchFilter="(objectClass=group)";
        if($search<>null){
            $searchFilter="(objectClass=group)";
        }
        $filter=array();
        if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}$dn -$searchFilter</strong><br>\n";}
        $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}`$error` ($errstr)</strong><br>\n";}
            return array();
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);

        if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$hash["count"]} items</strong><br>\n";}
        $FINAL=array();
        if(!is_array($hash)){return array();}
        for($i=0;$i<$hash["count"];$i++){
            $tempar=$hash[$i];
            if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$tempar["dn"]}</strong><br>\n";}

            $dn=$tempar["dn"];
            if(isset($tempar["samaccountname"])){
                $FINAL[$dn]=$tempar["samaccountname"][0];
            }
        }
        return $FINAL;
    }


    public function SearchOu($ou){
        if(!$this->connect()){return false;}
        if(!$this->$this->ldap_connection){return false;}
        if(strtolower($ou)==strtolower($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){return true;}
        $searchFilter="(&(objectClass=organizationalUnit)(name=$ou))";
        $filter=array();
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            return false;
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return false;}
        return true;
    }

    public function CountDeUSerOu($ou,$dn=null){

        if($dn<>null){
            if(!$GLOBALS["AS_ROOT"]){
                $q=new mysql();
                $sql="SELECT CountDeUsers FROM activedirectory_ou WHERE `dn`='$dn' AND CountDeUsers>0";
                $this->VERBOSE("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                $ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
                return $ligne["CountDeUsers"];
            }

        }

        if(isset($GLOBALS["hash_get_ou"][$ou]["CountDeUsers"])){return $GLOBALS["hash_get_ou"][$ou]["CountDeUsers"];}
        if(isset($GLOBALS[__FUNCTION__.__CLASS__.$ou])){return $GLOBALS[__FUNCTION__.__CLASS__.$ou];}
        if(!$GLOBALS["AS_ROOT"]){
            $q=new mysql();

            if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
                $ou=utf8_encode($ou);
                $sql="SELECT CountDeUsers FROM activedirectory_ou WHERE `name`='$ou'";
                $this->VERBOSE("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                $ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
                if($q->ok){
                    $GLOBALS[__FUNCTION__.__CLASS__.$ou]=$ligne["CountDeUsers"];
                    return $ligne["CountDeUsers"];
                }else{
                    $this->VERBOSE("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                }
            }else{
                $this->VERBOSE("No rows in activedirectory_ou for $ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

            }

        }



        if(strpos($ou, ",")==0){
            $dn=$this->GetOuDN($ou);
        }else{
            $dn=$ou;
        }


        if($dn==null){return 0;}
        $pattern="*";
        $searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
        $filter=array("samaccountname","dn");


        if(!$this->$this->ldap_connection){return 0;}
        $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:$dn $searchFilter",__LINE__);
            }
            return 0;
        }

        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return 0;}
        $GLOBALS[__FUNCTION__.__CLASS__.$ou]=$hash["count"];
        return $hash["count"];


    }

    public function find_computers($tofind,$maxentries=0){
        if(!$this->connect()){$this->VERBOSE("Unable to connect",__LINE__);return array();}
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        $dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
        if($dn==null){return array();}
        $searchFilter="(&(objectClass=computer)(|(cn=$tofind)(serviceprincipalname=$tofind)(distinguishedname=$tofind)(sAMAccountName=$tofind)(name=$tofind) (dnshostname=$tofind)))";
        $attrs=array("cn","operatingsystem","sAMAccountName","name","serviceprincipalname");
        $this->VERBOSE("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if(!$this->ldap_connection){return array();}
        if($maxentries>0){
            $sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
        }else{
            $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
        }
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:$dn $searchFilter",__LINE__);
            }
            $this->VERBOSE("Error:Unknown suffix:$dn $searchFilter",__LINE__);
            return array();
        }
        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            $this->VERBOSE("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }


        $this->VERBOSE("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

        return $hash;
    }

    public function GetDNFromUserid($userid){
        $array=$this->find_DN($userid);
        return $array[0]["dn"];

    }

    public function find_DN($tofind,$maxentries=0){

        $KeySession=md5(__CLASS__.__FUNCTION__.$tofind.$maxentries);
        if(!$GLOBALS["AS_ROOT"]){
            if(isset($_SESSION[$KeySession])){
                return $_SESSION[$KeySession];
            }
        }


        if(!$this->connect()){$this->VERBOSE("Unable to connect",__LINE__);return array();}
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        $dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
        if($dn==null){return array();}
        if(!$this->ldap_connection){return array();}

        $searchFilter="(&(objectClass=person)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
        $attrs=array("dn","distinguishedname","sAMAccountName","uid");


        $this->VERBOSE("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if($maxentries>0){
            $sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
        }else{
            $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
        }
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:$dn $searchFilter",__LINE__);
            }
            $this->VERBOSE("Error:Unknown suffix:$dn $searchFilter",__LINE__);
            return array();
        }
        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            $this->VERBOSE("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }


        $this->VERBOSE("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if(!$GLOBALS["AS_ROOT"]){$_SESSION[$KeySession]=$hash;}
        return $hash;


    }

    public function if_user_in_dn_ad_agent($dn,$username){
        $encdn=urlencode($dn);
        $enduser=urlencode($username);
        $adagent=new ADAgent();
        $res=$adagent->getDNMember($encdn,$enduser);
//        $uri="http://127.0.0.1:{$this->adagenthaport}/get/member/$encdn/$enduser";
//        $this->VERBOSE(" $uri",__LINE__);
//        $curl=new ccurl($uri);
//        $curl->NoLocalProxy();
//        if(!$curl->get()){
//            $this->VERBOSE("HTTP Engine error $curl->error");
//            return false;
//
//        }
//        $res=$curl->data;
        if($res=="true"){
            return true;
        }
        return false;

    }
    public function if_user_in_dn($dn,$username){
        if(!$this->connect()){
            $this->error="Unable to Connect";
            return false;
        }
        if(!$this->ldap_connection){return false;}
        $searchFilter="(&(objectClass=person)(|(cn=$username)(sAMAccountName=$username) ))";
        $attrs=array("displayName","sAMAccountName","mail","givenName","telephoneNumber","title","sn","userPrincipalName","distinguishedName");
        $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))) {
                $error = ldap_errno($this->ldap_connection);
                $errstr = @ldap_err2str($error);
                $this->error="Err.$error $errstr";

            }
            return false;
        }

        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            if(is_numeric(ldap_errno($this->ldap_connection))) {
                $error = ldap_errno($this->ldap_connection);
                $errstr = @ldap_err2str($error);
                $this->error="Err.$error $errstr";

            }
            return false;

        }

        if($hash["count"]>0){
            return true;
        }
        $this->error="Not found in $dn -->'{$hash["count"]}''";
        return false;

    }

    public function find_users($ou,$tofind=null,$maxentries=0){
        if(!$this->connect()){$this->VERBOSE("Unable to connect",__LINE__);return array();}
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        if($ou==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
        if($dn==null){$dn=$this->GetOuDN($ou);}
        if(strpos($ou, ",")>0){$dn=$ou;}
        if($dn==null){return array();}


        $searchFilter="(&(objectClass=person)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
        $attrs=array("displayName","sAMAccountName","mail","givenName","telephoneNumber","title","sn","userPrincipalName","distinguishedName");
        if($this->ldap_connection){return array();}
        $this->VERBOSE("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if($maxentries>0){
            $sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
        }else{
            $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
        }
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:$dn $searchFilter",__LINE__);
            }
            $this->VERBOSE("Error:Unknown suffix:$dn $searchFilter",__LINE__);
            return array();
        }
        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            $this->VERBOSE("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }


        $this->VERBOSE("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

        return $hash;


    }

    function LoadGroupDataByDN($dn):array{
        $DebugWebConsoleAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth"));
        $dn=trim($dn);
        $ArticaGroupPrivileges=null;
        $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
        $dn=$q->sqlite_escape_string2($dn);
        $sql="SELECT content FROM adgroupsprivs WHERE `DN`='$dn'";
        $this->VERBOSE($sql,__LINE__);
        $ligne=$q->mysqli_fetch_array($sql);
        if(!$q->ok){VERBOSE($q->mysql_error,__LINE__);}
        if(isset($ligne["content"])) {
            $ArticaGroupPrivileges = base64_decode($ligne["content"]);
        }

        if($GLOBALS["VERBOSE"]) {
            VERBOSE("LoadGroupDataByDN: ArticaGroupPrivileges " . strlen($ArticaGroupPrivileges) . " bytes", __LINE__);
        }
        $hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
        if(!isset($GLOBALS["CLASS_SOCKETS"])){
            $GLOBALS["CLASS_SOCKETS"]=new sockets();
        }


        $nginx=new NginxPrivs($dn);
        $NginxPrivs=$nginx->NginxPrivs($dn);
         if(strlen($NginxPrivs)>2){
           $hash["ArticaGroupPrivileges"]="{$hash["ArticaGroupPrivileges"]}\n[NginxPrivs]=\"$NginxPrivs\"";
         }

        $SimpleAclsPrivsText=$this->SimpleAclsPrivs($dn);
         if($DebugWebConsoleAuth==1){
             writelogs("SimpleAclsPrivsText=[$SimpleAclsPrivsText]", __FUNCTION__, __FILE__, __LINE__);
         }
        if(strlen($SimpleAclsPrivsText)>0){
            $hash["ArticaGroupPrivileges"]="{$hash["ArticaGroupPrivileges"]}\n[SimpleAclsPrivs]=\"$SimpleAclsPrivsText\"";
        }
         return $hash;
    }
    function SimpleAclsPrivs($dn):string{
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");

        $results = $q->QUERY_SQL("SELECT aclid FROM webfilters_simpleacls_privs WHERE dngroup='$dn'");
        $f=array();
        foreach ($results as $index => $ligne) {
            $f[]=$ligne["aclid"];
        }
        return @implode(",", $f);
    }

    private function NginxPrivs($dn):string{
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        if($EnableNginx==0){
            return "";
        }
        $AP=array();
        $q=new lib_sqlite(NginxGetDB());
        if(!$q->TABLE_EXISTS("adminprivs")){
            return "";
        }
        $results=$q->QUERY_SQL("SELECT * FROM adminprivs WHERE item='$dn'");
        if(!is_array($results)){return "";}

        foreach ($results as $index=>$ligne){
            $AP[]=intval($ligne["serviceid"]);
        }
        if(count($AP)>0){
            return @implode(",",$AP);
        }
        return "";
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


    function SaveGroupPrivileges($content,$dn){
        $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
        $sql="CREATE TABLE IF NOT EXISTS `adgroupsprivs` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `DN` text UNIQUE, `content` TEXT NOT NULL )";
        $q->QUERY_SQL($sql);
        $content=base64_encode($content);
        $dn=$q->sqlite_escape_string2($dn);
        $sql="SELECT `DN` FROM adgroupsprivs WHERE `DN`='$dn'";
        $ligne=$q->mysqli_fetch_array($sql);

        $sql="UPDATE adgroupsprivs SET content='$content' WHERE DN='$dn'";
        if(!isset($ligne["DN"])){$ligne["DN"]=null;}
        if($ligne["DN"]==null){
            $sql="INSERT INTO adgroupsprivs (DN,content) VALUES ('$dn','$content')";
        }
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return false;}
        return true;
    }



    function hash_groups($ou,$fieldlist=0,$maxitems=0,$tofind=null){
        if(!$this->connect()){
            $this->VERBOSE("Unable to connect",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }
        if(strpos($ou,",")>0){$dn=$ou;}else{
            $dn=$this->GetOuDN($ou);
        }
        if($dn==null){
            $this->VERBOSE("$ou $tofind, dn=null ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();}
        $res=array();
        if($tofind==null){$tofind="*";}
        $this->VERBOSE("Search Groups in $dn",__LINE__);
        $filter=array("cn","description",'sAMAccountName',"dn","member","memberOf");
        $searchFilter="(&(objectClass=group)(sAMAccountName=$tofind))";
        $this->VERBOSE("$searchFilter in $dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if($maxitems==0){$maxitems=null;}
        $sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:$dn $searchFilter",__LINE__);
            }
            $this->VERBOSE("Unknown error...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            $this->VERBOSE("Not an array ($searchFilter)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();}

        $NOTCOUNT[strtolower("Domain Computers")]=true;
        $NOTCOUNT[strtolower("Domain Controllers")]=true;
        $NOTCOUNT[strtolower("Domain Users")]=true;
        $NOTCOUNT[strtolower("Domain Guests")]=true;
        $NOTCOUNT[strtolower("RAS and IAS Servers")]=true;


        writelogs(count($hash)." items ($searchFilter)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

        foreach ($hash as $num=>$ligne){
            if(!isset($ligne["samaccountname"][0])){continue;}
            if($ligne["samaccountname"][0]==null){continue;}
            $MembersCount=0;
            $GroupName=$ligne["samaccountname"][0];
            if(isset($ligne["member"]["count"])){$MembersCount=$ligne["member"]["count"];}
            if($MembersCount==0){
                if(isset($NOTCOUNT[strtolower($GroupName)])){$MembersCount="-";}}




            switch ($fieldlist) {
                case 1:$res[$ligne["dn"]]=$ligne["samaccountname"][0];break;
                case 2;$res=$res . "{$ligne["dn"]};{$ligne["samaccountname"][0]}\n";break;
                default:$res[$ligne["cn"][0]]=array(
                    "groupname"=>$ligne["samaccountname"][0],
                    "gid"=>urlencode($ligne["dn"]),
                    "description"=>$ligne["description"][0],
                    "UsersCount"=>$this->CountDeMemberGroupDN($ligne["dn"])
                );
                    break;
            }



        }

        return $res;

    }

    public function CountDeUsersByGroupName($groupname){
        if(!$this->connect()){return 0;}
        $filter=array("distinguishedName");
        $searchFilter="(&(objectClass=group)(sAMAccountName=$groupname))";
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            return 0;
        }

        $hash=ldap_get_entries($this->ldap_connection,$sr);
        $DN=$hash[0]["distinguishedname"][0];
        return $this->CountDeMemberGroupDN($DN);
    }

    public function CountDeUsersByGroupDN($dn){
        return $this->CountDeMemberGroupDN($dn);
    }


    private function CountDeMemberGroupDN($dn){
        if(isset($GLOBALS["CountDeMemberGroupDN($dn)"])){return $GLOBALS["CountDeMemberGroupDN($dn)"];}
        $hash=$this->HashUsersFromGroupDN($dn);
        if($GLOBALS["VERBOSE"]){echo "`$dn` -> ".count($hash)." members line ".__LINE__."\n";}
        $GLOBALS["CountDeMemberGroupDN($dn)"]=count($hash);
        return $GLOBALS["CountDeMemberGroupDN($dn)"];

    }


    private function GetOuDN($ou){
        if(!$this->connect()){return null;}
        if(strtolower($ou)==strtolower($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){
            return "CN=Users,{$this->KerbAuthInfos["LDAP_SUFFIX"]}";
        }
        $searchFilter="(&(objectClass=organizationalUnit)(name=$ou))";
        $filter=array();
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            return false;
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return false;}
        if(isset($hash[0]["dn"])){return $hash[0]["dn"];	}
        if(isset($hash["dn"])){return $hash["dn"];}
    }


    public function hash_get_ou($has_key=false,$array=array(),$FullHash=false):array{
        if(!$this->connect()){return $array;}
        if($has_key){$array[$this->KerbAuthInfos["ADNETBIOSDOMAIN"]]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];}else{$array[]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];}


        $searchFilter="(&(objectClass=organizationalUnit)(name=*))";
        $filter=array("name","dn");
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            return $array;
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return $array;}
        $this->VERBOSE("{$hash["count"]} organization(s) found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__LINE__);
        for($i=0;$i<$hash["count"];$i++){
            $dn=$hash[$i]["dn"];
            $ou=$hash[$i]["name"][0];
            if(strtolower($ou)==strtolower("Domain Controllers")){
                $dn="CN=Users,".$this->KerbAuthInfos["LDAP_SUFFIX"];
                $ff=explode(",",$this->KerbAuthInfos["LDAP_SUFFIX"]);
                if(preg_match("#(.+?)=(.+)#", $ff[0],$re)){$ou=$re[2];}
            }



            if($FullHash){
                $array[$dn]=$ou;
                continue;
            }


            if($has_key){$array[$ou]=$ou;}else{$array[]=$ou;}

        }

        return $array;


    }

    public function CountDeGroups($ou,$mysql=false){
        if(isset($GLOBALS["hash_get_ou"][$ou]["CountDeGroups"])){return $GLOBALS["hash_get_ou"][$ou]["CountDeGroups"];}

        if(isset($GLOBALS[__FUNCTION__.__CLASS__.$ou])){return $GLOBALS[__FUNCTION__.__CLASS__.$ou];}
        if(!$GLOBALS["AS_ROOT"]){
            $q=new mysql();
            if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
                $ou=utf8_encode($ou);
                $sql="SELECT CountDeGroups FROM activedirectory_ou WHERE `name`='$ou'";
                $this->VERBOSE("$sql",__CLASS__.'/'.__FUNCTION__);
                $ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
                if($q->ok){
                    $GLOBALS[__FUNCTION__.__CLASS__.$ou]=$ligne["CountDeGroups"];
                    return $ligne["CountDeGroups"];
                }else{
                    $this->VERBOSE("$q->mysql_error",__CLASS__.'/'.__FUNCTION__);
                }
            }else{
                $this->VERBOSE("No rows in activedirectory_ou for $ou",__CLASS__.'/'.__FUNCTION__);

            }
        }



        if(strpos($ou, ",")==0){$dn=$this->GetOuDN($ou);}else{$dn=$ou;}

        if(!$this->connect()){
            if($GLOBALS["VERBOSE"]){echo "!!! *** Error:connect failed in line ".__LINE__."\n";}
            return 0;
        }
        $pattern="*";
        $searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
        $filter=array("samaccountname","dn","description");

        $sr =@ldap_search($this->ldap_connection,$dn,$searchFilter,$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "!!! *** Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter\n";}
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            return 0;
        }


        $dn=addslashes($dn);
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            if($GLOBALS["VERBOSE"]){echo "!!! *** Error: Hash is not an array in line ".__LINE__."\n";}
            return 0;
        }


        $f=array();

        if($mysql){
            for($i=0;$i<$hash["count"];$i++){
                $dnG=addslashes($hash[$i]["dn"]);

                $samaccountname=addslashes($hash[$i]["samaccountname"][0]);
                if(!isset($hash[$i]["description"][0])){$hash[$i]["description"][0]=null;}

                $description=addslashes($hash[$i]["description"][0]);
                $CountDeUsers=$this->CountDeMemberGroupDN($hash[$i]["dn"]);
                $md5=md5($dn);
                if($GLOBALS["VERBOSE"]){echo "('$dnG','$samaccountname','$CountDeUsers','$description','$dn')\n";}
                $f[]="('$dnG','$samaccountname','$CountDeUsers','$description','$dn')";
                if($GLOBALS["VERBOSE"]){echo "$samaccountname $CountDeUsers $dnG\n";}
            }

            if(count($f)>0){
                $q=new mysql();
                $sql="INSERT IGNORE INTO activedirectory_groupsNames 
						(`dn`,`groupname`,`UsersCount`,`description`,`oudn`) VALUES ".
                    @implode(",", $f);

                $q->QUERY_SQL($sql,"artica_backup");

                if(!$q->ok){echo "!!! $q->mysql_error\n";}
            }
        }else{
            if($GLOBALS["VERBOSE"]){echo "Mysql = FALSE line ".__LINE__."\n";}
        }



        return $hash["count"];
    }


    private function LoadSambaInformations(){
        if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $EnableSambaActiveDirectory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSambaActiveDirectory"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}


        if($EnableKerbAuth==1){
            VERBOSE("GET_INFO(KerbAuthInfos)",__LINE__);
            $KerbAuthInfos=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos");
            $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($KerbAuthInfos);
            if(!is_array($array)){$array=array();}

            if(!isset($array["WINDOWS_DNS_SUFFIX"])){
                $array["WINDOWS_DNS_SUFFIX"]="";
            }
            if(!isset($array["WINDOWS_SERVER_PASS"])){
                $array["WINDOWS_SERVER_PASS"]="";
            }
            if(!isset($array["LDAP_SUFFIX"])){
                $array["LDAP_SUFFIX"]="";
            }
            if($GLOBALS["VERBOSE"]){
                foreach ($array as $key=>$value){
                    VERBOSE("KerbAuthInfos: [$key]=\"{$value}\"",__LINE__);
                }
            }
            if(is_null($array["WINDOWS_DNS_SUFFIX"])){
                $array["WINDOWS_DNS_SUFFIX"]="";
            }

            if(!preg_match("#dc=(.+?)#i",$array["LDAP_SUFFIX"])){
                $tt=explode(".",$array["WINDOWS_DNS_SUFFIX"]);
                $suff=array();
                foreach ($tt as $num=>$ligne){
                    $ligne=trim($ligne);
                    if($ligne==null){continue;}
                    $suff[]="DC=$ligne";

                }

                $array["LDAP_SUFFIX"]=@implode(",", $suff);
                VERBOSE("KerbAuthInfos: Fixing LDAP_SUFFIX to \"{$array["LDAP_SUFFIX"]}\"",__LINE__);
            }

            $checks[]="LDAP_DN";
            $checks[]="LDAP_SUFFIX";
            $checks[]="LDAP_SERVER";
            $checks[]="fullhosname";
            $COUNTKEYS=0;

            foreach ($checks as $KEY){if(isset($array[$KEY])){$COUNTKEYS++;}}
            if($COUNTKEYS==0){
                VERBOSE("KerbAuthInfos not correctly filled, try AdConnection[0]",__LINE__);
                $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
                $array=$ActiveDirectoryConnections[0];
            }
            if(isset($array["ADUserCanConnect"])){
                $this->KerbAuthInfos["ADUserCanConnect"]=intval($array["ADUserCanConnect"]);
            }

            if(!isset($array["fullhosname"])){$array["fullhosname"]=null;}
            if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
            if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=null;}
            if(!isset($array["LDAP_SERVER"])){$array["LDAP_SERVER"]=null;}
            if($array["LDAP_SERVER"]==null){if($array["fullhosname"]<>null){$array["LDAP_SERVER"]=$array["fullhosname"];}}

            if(isset($array["WINDOWS_DNS_SUFFIX"])){$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$array["WINDOWS_DNS_SUFFIX"];}
            if(isset($array["WINDOWS_SERVER_NETBIOSNAME"])){$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=$array["WINDOWS_SERVER_NETBIOSNAME"];}
            if(isset($array["ADNETBIOSDOMAIN"])){$this->KerbAuthInfos["ADNETBIOSDOMAIN"]=$array["ADNETBIOSDOMAIN"];}
            if(isset($array["ADNETIPADDR"])){$this->KerbAuthInfos["ADNETIPADDR"]=$array["ADNETIPADDR"];}
            if(isset($array["LDAP_SSL"])){$this->KerbAuthInfos["LDAP_SSL"]=$array["LDAP_SSL"];}

            if(isset($array["WINDOWS_SERVER_ADMIN"])){
                $this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$array["WINDOWS_SERVER_ADMIN"];
            }
            if(isset($array["WINDOWS_SERVER_PASS"])){
                $this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["WINDOWS_SERVER_PASS"];
            }

            if(!isset($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=null;}

            if(strpos($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"],"@")>0){
                $TR=explode("@",$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]);
                $this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$TR[0];
                $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$TR[1];
            }

            $this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
            $this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
            $this->KerbAuthInfos["LDAP_DN"]=$array["LDAP_DN"];

            if(isset($array["LDAP_PASSWORD"])){
                $this->KerbAuthInfos["LDAP_PASSWORD"]=$array["LDAP_PASSWORD"];
                if($this->KerbAuthInfos["WINDOWS_SERVER_PASS"]==null){
                    $this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["LDAP_PASSWORD"];
                }
            }

        }

        if(!isset($this->KerbAuthInfos["LDAP_DN"])){$this->KerbAuthInfos["LDAP_DN"]=null;}
        if(!isset($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=null;}
        if(!isset($this->KerbAuthInfos["ADNETIPADDR"])){$this->KerbAuthInfos["ADNETIPADDR"]=null;}
        if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}

        if($this->KerbAuthInfos["LDAP_DN"]<>null){
            if($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]==null){
                if(preg_match("#(.+?)@(.+)#", $array["LDAP_DN"],$re)){
                    $this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$re[1];
                    $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$re[2];
                }
            }
        }

        if(isset($array["LDAP_SERVER"])){
            if($this->KerbAuthInfos["ADNETIPADDR"]==null){
                $this->KerbAuthInfos["ADNETIPADDR"]=$array["LDAP_SERVER"];
            }
        }




        if($EnableSambaActiveDirectory==1){
            $Save=false;
            $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos"));
            if(!is_array($array)){$array=array();}
            $this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=$array["ADSERVER"];
            $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$array["ADDOMAIN"];
            $this->KerbAuthInfos["ADNETBIOSDOMAIN"]=$array["WORKGROUP"];
            $this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$array["ADADMIN"];
            $this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["PASSWORD"];
            $this->KerbAuthInfos["ADNETIPADDR"]=$array["ADSERVER_IP"];
            if(!isset($array["LDAP_SUFFIX"])){$SMB=$this->SAMBA_GetNetAdsInfos();if($SMB["Bind Path"]<>null){$array["LDAP_SUFFIX"]=$SMB["Bind Path"];$Save=true;}}
            if(!isset($array["LDAP_SERVER"])){$SMB=$this->SAMBA_GetNetAdsInfos();if($SMB["LDAP server"]<>null){$array["LDAP_SERVER"]=$SMB["LDAP server"];$Save=true;}}
            if($Save){$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($array)), "SambaAdInfos");}
            $this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
            $this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
        }


        if(!is_null($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])) {
            if (preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])) {
                $SMB = $this->SAMBA_GetNetAdsInfos();
                $array["FQDN"] = $SMB["LDAP server name"];
                $this->KerbAuthInfos["FQDN"] = $array["FQDN"];
                if ($array["FQDN"] <> null) {
                    $FGH = explode(".", $array["FQDN"]);
                    unset($FGH[0]);
                    $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"] = @implode(".", $FGH);
                }
            }
        }

        if($this->KerbAuthInfos["ADNETIPADDR"]<>null){
            $ipaddrZ=explode(".",$this->KerbAuthInfos["ADNETIPADDR"]);
            foreach ($ipaddrZ as $num=>$a){$ipaddrZ[$num]=intval($a);}
            $this->KerbAuthInfos["ADNETIPADDR"]=@implode(".", $ipaddrZ);
        }

    }

    public function SearchGroups($pattern,$dn=null,$maxitems=0){
        if($dn==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
        if($maxitems==0){$maxitems=null;}
        if(!$this->connect()){return array();}
        if($pattern==null){$pattern="*";}
        $searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";

        if($GLOBALS["VERBOSE"]){
            echo "searchFilter: $searchFilter in $dn<br>\n";}

        $filter=array();
        $sr =@ldap_list($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 20);

        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                $this->error="flexRTGroups: Err.`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
                if($GLOBALS["VERBOSE"]){echo "$this->error<br>\n";}
                return array();
            }
        }

        return @ldap_get_entries($this->ldap_connection,$sr);

    }

    public function SearchUsers($pattern,$dn,$maxitems=0){
        if($maxitems==0){$maxitems=null;}
        if(!$this->connect()){return array();}
        if($pattern==null){$pattern="*";}
        $searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
        $filter=array();
        $sr =@ldap_list($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 20);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                $this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
                return array();
            }
        }

        return @ldap_get_entries($this->ldap_connection,$sr);

    }

    public function flexRTGroups($pattern,$maxitems=0,$objectClass="group"){
        $this->TESTED_SERVERS=array();
        $array=$this->flexRTGroups_local($pattern,$maxitems,$objectClass,array());

        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(count($ActiveDirectoryConnections)==0){return $array;}

        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}

            $subarray=$this->flexRTGroups_local($pattern,$maxitems,$objectClass,$ligne);
            foreach ($subarray as $dn=>$samaccountname){
                $array[$dn]=$samaccountname;
            }

        }
        return $array;


    }


    private function flexRTGroups_local($pattern,$maxitems=0,$objectClass="group",$ldap_config=array()){
        $remote_config=false;
        if(!isset($ldap_config["LDAP_SERVER"])){
            $LDAP_SUFFIX=$this->KerbAuthInfos["LDAP_SUFFIX"];
            if(!$this->connect()){return false;}
        }else{
            $remote_config=true;
            $LDAP_SUFFIX=$ldap_config["LDAP_SUFFIX"];
            if(!$this->connect($ldap_config)){return false;}

        }
        if($pattern==null){$pattern="*";}
        $searchFilter="(&(objectClass=$objectClass)(sAMAccountName=$pattern))";
        $filter=array("samaccountname","dn");
        $Attribute="samaccountname";

        if(!$remote_config){
            if(isset($this->KerbAuthInfos["LDAP_FILTER"])){
                $searchFilter="(&(objectClass=$objectClass)(cn=$pattern))";
                $filter=array("cn","dn");
                $Attribute="cn";
            }
        }

        if($maxitems==0){$maxitems=null;}
        $sr =@ldap_search($this->ldap_connection,$LDAP_SUFFIX,"$searchFilter",$filter,null, $maxitems, 20);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>`$error` ($errstr) suffix:{$LDAP_SUFFIX} $searchFilter</span>\n<br>";}
                $this->error="flexRTGroups:`$error` ($errstr) suffix:{$LDAP_SUFFIX} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
                $this->CloseConnection();
                return array();
            }
        }

        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            $error=ldap_errno($this->ldap_connection);
            if($error>1){
                $errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>`$error` ($errstr) suffix:{$LDAP_SUFFIX} $searchFilter</span>\n<br>";}
                $this->error="flexRTGroups:`$error` ($errstr) suffix:{$LDAP_SUFFIX} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
            }
            $this->CloseConnection();
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>@ldap_get_entries( return not an array)</span>\n<br>";}
            return array();
        }

        if($hash["count"]==0){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>count == 0</span>\n<br>";}
            if(!$remote_config){
                if(isset($this->KerbAuthInfos["LDAP_FILTER"])){
                    $this->CloseConnection();
                    return $this->flexRTGroups($pattern,$maxitems=0,$objectClass="posixGroup");

                }
            }
        }

        if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>{$hash["count"]} items in {$LDAP_SUFFIX} $searchFilter</span>\n<br>";}
        $this->CloseConnection();

        for($i=0;$i<$hash["count"];$i++){
            $groupid=$hash[$i][$Attribute][0];
            if(is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"][0];}
            if(!is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"];}


            if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
            $array[$dn]="$groupid";

        }

        return $array;

    }

    public function flexRTUsers($pattern,$maxitems=0){

        if(!$this->connect()){return false;}
        if($pattern==null){$pattern="*";}
        $searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
        $filter=array("samaccountname","dn");


        if(isset($this->KerbAuthInfos["LDAP_FILTER"])){
            $searchFilter=str_replace("%s", $pattern, $this->KerbAuthInfos["LDAP_FILTER"]);
            $filter=array($this->KerbAuthInfos["LDAP_ATTR"],"dn");
        }


        if($maxitems==0){$maxitems=null;}
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                $this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
                return array();
            }
        }

        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            $error=ldap_errno($this->ldap_connection);
            if($error>1){
                $errstr=@ldap_err2str($error);
                $this->error="flexRTUsers:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
            }
            return array();
        }

        $this->VERBOSE("{$hash["count"]} in {$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);

        for($i=0;$i<$hash["count"];$i++){
            $groupid=$hash[$i][strtolower($this->KerbAuthInfos["LDAP_ATTR"])][0];
            if(is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"][0];}
            if(!is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"];}


            if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
            $array[$dn]="$groupid";

        }

        return $array;


    }



    public function searchGroup($pattern,$array,$maxitems=0){
        if(!$this->connect()){
            $this->IsError=true;
            return $array;
        }
        if($pattern==null){$pattern="*";}
        $searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
        $filter=array("samaccountname");




        if($maxitems==0){$maxitems=null;}
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->IsError=true;
                $this->error="$errstr User:`$this->UserConnected` $searchFilter";
                $this->VERBOSE("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__LINE__);
            }
            return $array;
        }

        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return $array;}
        $this->VERBOSE("{$hash["count"]} Group(s) found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__LINE__);

        for($i=0;$i<$hash["count"];$i++){

            $groupid=$hash[$i]["samaccountname"][0];
            $dn=$hash[$i]["dn"];
            if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
            $array["@$groupid"]="@$groupid:$dn";

        }

        return $array;

    }

    public function GetGroupDN($groupname){
        $maxitems=null;
        if(!$this->connect()){return null;}
        $searchFilter="(&(objectClass=group)(sAMAccountName=$groupname))";
        $filter=array("samaccountname","dn");
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->error="`$error` ($errstr) $this->UserConnected $searchFilter";
                $this->VERBOSE("Error:`$error` ($errstr) $this->UserConnected $searchFilter",__LINE__);
            }
            return null;
        }
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return null;}
        return $hash[0]["dn"];

    }



    public function searchUser($pattern,$array){
        if(!$this->connect()){return $array;}
        if($pattern==null){$pattern="*";}
        $searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
        $filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
        $sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
                $this->VERBOSE("$errstr) $this->UserConnected $searchFilter",__LINE__);
            }
            return $array;
        }

        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){return $array;}
        $this->VERBOSE("{$hash["count"]} members found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__LINE__);

        for($i=0;$i<$hash["count"];$i++){
            $userid=$hash[$i]["samaccountname"][0];
            if(substr($userid,strlen($userid)-1,1)=='$'){continue;}
            $mail=$hash[$i]["mail"][0];
            $userprincipalname=$hash[$i]["userprincipalname"][0];
            $displayname=$hash[$i]["displayname"][0];
            if($displayname==null){$displayname=$userid;}
            $array[$userid]=$displayname;
        }

        return $array;

    }

    function AllSearchFormat($results,$ZARRAY=array(),$SUFFIX=null){

        $count=$results["count"];

        for($i=0;$i<$count;$i++){
            $main=$results[$i];
            $KEY=$main["dn"];
            $ycount=$main["count"];
            for($z=0;$z<$ycount;$z++){
                $zkey=$main[$z];
                $ZARRAY[$KEY][$zkey]=$main[$zkey];

            }
            $ZARRAY[$KEY]["SUFFIX"]=$SUFFIX;

        }

        return $ZARRAY;

    }

    function AllGroups($tofind,$maxentries=200){
        if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        $attrs=array();
        $searchFilter="(&(objectClass=group)(cn=$tofind))";
        if($this->connect()){
            $dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
            VERBOSE("search $searchFilter in $dn max:$maxentries",__LINE__);
            $results=$this->zldap_search($tofind,$dn, $searchFilter, $attrs,$maxentries);
            if(is_array($results)){$hash=$this->AllSearchFormat($results,array(),$dn);}
            VERBOSE(count($hash)." items found ",__LINE__);
            if($this->ActiveDirectoryIndexOnly){return $hash;}
        }else{
            VERBOSE("Failed to connect!");
            if($this->ActiveDirectoryIndexOnly){return array();}
        }

        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
        if(count($ActiveDirectoryConnections)==0){return $hash;}
        if(!is_array($ActiveDirectoryConnections)){return $hash;}
        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            $dn=$ligne["LDAP_SUFFIX"];
            if(!$this->connect($ligne)){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$dn Connection failed</span><br>\n";}
                continue;}

            $results=$this->zldap_search($tofind,$dn, $searchFilter, $attrs,$maxentries);
            if(!$results){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>{$ligne["LDAP_SERVER"]} $tofind -> FALSE</span><br>\n";}
                continue;}
            $hash=$this->AllSearchFormat($results,$hash,$dn);

        }
        return $hash;


    }
    function AllUsers($tofind,$maxentries=200){
        $hash=array();
        if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        $attrs=array();
        $searchFilter="(&(  |(objectClass=person)(objectClass=user) )(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(samaccountname=$tofind) (givenname=$tofind) ) )";
        $attrs=array();

        if($this->connect()){
            $dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
            VERBOSE("search $searchFilter in $dn max:$maxentries",__LINE__);
            $results=$this->zldap_search($tofind,$dn, $searchFilter, $attrs,$maxentries);
            if(is_array($results)){$hash=$this->AllSearchFormat($results,array(),$dn);}
            VERBOSE(count($hash)." items found ",__LINE__);
            if($this->ActiveDirectoryIndexOnly){return $hash;}
        }else{
            VERBOSE("Failed to connect!");
            if($this->ActiveDirectoryIndexOnly){return array();}
        }
        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(!is_array($ActiveDirectoryConnections)){return $hash;}
        if(count($ActiveDirectoryConnections)==0){return $hash;}

        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            $dn=$ligne["LDAP_SUFFIX"];
            if(!$this->connect($ligne)){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$dn Connection failed</span><br>\n";}
                continue;}

            $results=$this->zldap_search($tofind,$dn, $searchFilter, $attrs,$maxentries);
            if(!$results){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>{$ligne["LDAP_SERVER"]} $tofind -> FALSE</span><br>\n";}
                continue;}
            $hash=$this->AllSearchFormat($results,$hash,$dn);

        }
        return $hash;


    }

    function AllSearch($tofind,$maxentries=0){
        if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
        $tofind=str_replace("***", "*", $tofind);
        $tofind=str_replace("**", "*", $tofind);
        $searchFilter="(&(  |(objectClass=group)(objectClass=person)(objectClass=user) )(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(samaccountname=$tofind) (givenname=$tofind) ) )";
        $attrs=array("displayName","samaccountname","mail","dn","givenname","telephoneNumber","mobile","title","sn","mozillaSecondEmail","employeeNumber","objectclass");

        if($this->connect()){
            $dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
            VERBOSE("search $searchFilter in $dn max:$maxentries",__LINE__);
            $results=$this->zldap_search($tofind,$dn, $searchFilter, $attrs,$maxentries);
            if(is_array($results)){$hash=$this->AllSearchFormat($results,array(),$dn);}
            VERBOSE(count($hash)." items found ",__LINE__);
            if($this->ActiveDirectoryIndexOnly){return $hash;}
        }else{
            VERBOSE("Failed to connect!");
            if($this->ActiveDirectoryIndexOnly){return array();}
        }

        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(count($ActiveDirectoryConnections)==0){return $hash;}
        if(!is_array($ActiveDirectoryConnections)){return $hash;}
        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(!is_numeric($index)){continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            $dn=$ligne["LDAP_SUFFIX"];
            if(!$this->connect($ligne)){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$dn Connection failed</span><br>\n";}
                continue;}

            $results=$this->zldap_search($tofind,$dn, $searchFilter, $attrs,$maxentries);
            if(!$results){
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>{$ligne["LDAP_SERVER"]} $tofind -> FALSE</span><br>\n";}
                continue;}
            $hash=$this->AllSearchFormat($results,$hash,$dn);

        }
        return $hash;

    }


    private function zldap_search($tofind,$dn,$searchFilter,$attrs,$maxentries=0){

        if(!$this->ldap_connection){
            return array();
        }

        if($maxentries>0){
            $sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
        }else{
            $sr =@ldap_search($this->ldap_connection,$dn,$searchFilter,$attrs,null, null, 20);
        }
        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>Error:`$error` ($errstr) suffix:$dn $searchFilter</span><br>\n";}
                $this->VERBOSE("Error:`$error` ($errstr) suffix:$dn $searchFilter",__LINE__);
            }
            $this->VERBOSE("Error:Unknown suffix:$dn $searchFilter",__LINE__);
            return array();
        }

        if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>ldap_get_entries($this->ldap_connection,$sr) $dn</span><br>\n";}
        $hash=@ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($hash)){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>Not found $tofind in $dn max:$maxentries = 0 items</span><br>\n";}
            $this->VERBOSE("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
        return $hash;
    }


    function UserSearch($ou=null,$tofind=null,$max=0){
        if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
        $hash=$this->find_users($ou,$tofind,$max);
        $hash2=array();
        return array($hash,$hash2);

    }

    function SaveUserPhoto($content,$DN){
        if(!$this->connect()){return false;}
        $upd=array();
        $upd["thumbnailPhoto"]=$content;
        //$r=ldap_mod_add($this->ldap_connection,$DN,$upd);
        $r=@ldap_modify($this->ldap_connection,$DN,$upd);

        if(!$r){

            $ldap_errno=ldap_errno($this->ldap_connection);
            $ldap_err2str=ldap_err2str($ldap_errno);
            $this->ldap_error="$ldap_err2str lenght() = ".strlen($content);
            return false;
        }


        return true;
    }


    private function SAMBA_GetNetAdsInfos(){
        $sock=new sockets();

        if(isset($GLOBALS["CACHE_NETADS"])){
            if(trim($GLOBALS["CACHE_NETADS"]["KDC server"])<>null){
                return $GLOBALS["CACHE_NETADS"];
            }
        }

        if(isset($_SESSION["CACHE_NETADS"])){
            if(trim($_SESSION["CACHE_NETADS"]["KDC server"])<>null){
                return $_SESSION["CACHE_NETADS"];
            }
        }

        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        if($LockActiveDirectoryToKerberos==1) {
            $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
            if(!is_array($array)){$array=array();}
            return $array;

        }


        $GLOBALS["CACHE_NETADS"]=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->getFrameWork("samba.php?GetNetAdsInfos=yes"));
        $_SESSION["CACHE_NETADS"]=$GLOBALS["CACHE_NETADS"];
        return $GLOBALS["CACHE_NETADS"];
    }

    private function CloseConnection(){


    }

    private function connect($MainConfig=array()){
        if(count($MainConfig)<2) {
            if(!is_null($this->connectionEncoded)) {
                if (strlen($this->connectionEncoded) > 20) {
                    $MainConfig = $this->PaseEncodedConnection();
                }
            }
        }
        $Username="";
        $LDAP_SERVER=null;
        $this->CloseConnection();
        $LDAP_PORT=389;
        if(!isset($MainConfig["LDAP_SERVER"])){$MainConfig["LDAP_SERVER"]=null;}
        if(!isset($MainConfig["LDAP_PORT"])){$MainConfig["LDAP_PORT"]=0;}
        if(!isset($MainConfig["LDAP_SSL"])){$MainConfig["LDAP_SSL"]=0;}
        if(!isset($this->KerbAuthInfos["LDAP_SSL"])){$this->KerbAuthInfos["LDAP_SSL"]=0;}
        if(!isset($this->KerbAuthInfos["LDAP_SERVER"])){$this->KerbAuthInfos["LDAP_SERVER"]=null;}
        if(!isset($this->KerbAuthInfos["LDAP_PORT"])){$this->KerbAuthInfos["LDAP_PORT"]=389;}

        if($MainConfig["LDAP_SERVER"]==null){
            VERBOSE("Fix MainConfig[\"LDAP_SERVER\"] is null to {$this->KerbAuthInfos["LDAP_SERVER"]}",__LINE__);
            $LDAP_SERVER=$this->KerbAuthInfos["LDAP_SERVER"];
            $MainConfig["LDAP_SSL"] = $this->KerbAuthInfos["LDAP_SSL"];

        }
        if($MainConfig["LDAP_PORT"]==0){
            VERBOSE("Fix MainConfig[\"LDAP_PORT\"] is null to {$this->KerbAuthInfos["LDAP_PORT"]}",__LINE__);
            $LDAP_PORT=$this->KerbAuthInfos["LDAP_PORT"];
        }
        if(!isset($MainConfig["LDAP_SSL"])){
            VERBOSE("Fix MainConfig[\"LDAP_SSL\"] is empty to {$this->KerbAuthInfos["LDAP_SSL"]}",__LINE__);
            if(isset($this->KerbAuthInfos["LDAP_SSL"])) {
                $MainConfig["LDAP_SSL"] = $this->KerbAuthInfos["LDAP_SSL"];
            }
        }
        if(!isset($MainConfig["LDAP_PASSWORD"])){
            $MainConfig["LDAP_PASSWORD"]=null;
        }
        if(!isset($MainConfig["LDAP_DN"])){
            $MainConfig["LDAP_DN"]=null;
        }
        if($MainConfig["LDAP_PASSWORD"]==null) {
            $MainConfig["LDAP_PASSWORD"]=$this->KerbAuthInfos["WINDOWS_SERVER_PASS"];
        }
        if(!isset($MainConfig["LDAP_DN"])) {
            $MainConfig["LDAP_DN"] = $this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]."@".$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"];
        }





        if($LDAP_PORT==636){$MainConfig["LDAP_SSL"]=1;}
        $LDAP_SSL=intval($MainConfig["LDAP_SSL"]);


        if($GLOBALS["VERBOSE"]){
            VERBOSE(" - - - - - - AVAILABLE CONFIG - - - - - -",__LINE__);
            foreach ($MainConfig as $KEY=>$val){
                if(is_array($val)){
                    foreach ($val as $a=>$b){
                        VERBOSE("[$KEY]= [$a] = [$b]",__LINE__);
                    }
                    continue;}

                VERBOSE("[$KEY]=-$val-",__LINE__);
            }

        }
        if(isset($MainConfig["LDAP_SERVER"])){$LDAP_SERVER=$MainConfig["LDAP_SERVER"];}
        if(isset($MainConfig["LDAP_PORT"])){$LDAP_PORT=intval($MainConfig["LDAP_PORT"]);}
        if(isset($MainConfig["LDAP_SSL"])){$LDAP_SSL=intval($MainConfig["LDAP_SSL"]);}

        if($LDAP_PORT==0){$LDAP_PORT=389;}
        if($LDAP_PORT==636){$LDAP_SSL=1;}
        $this->connectionEncoded=base64_encode(serialize($MainConfig));

        if($LDAP_SERVER==null){
            $this->error="connect:: Failed to connect to DC Could not obtain `LDAP_SERVER` ".
                __CLASS__."/".__FUNCTION__." line:".__LINE__;
            VERBOSE("Failed to connect to DC Could not obtain `LDAP_SERVER`",__FILE__,__LINE__);
            return false;
        }

        if(isset($MainConfig["ADNETIPADDR"])){
            VERBOSE("Checking IP address {$MainConfig["ADNETIPADDR"]}",__FILE__,__LINE__);
            $ip=new IP();
            if($ip->isValid($MainConfig["ADNETIPADDR"])){
                $LDAP_SERVER=$MainConfig["ADNETIPADDR"];
            }
        }

        $prefix="ldap";
        if($LDAP_SSL==1){
            $prefix="ldaps";
            $LDAP_PORT=636;
        }
        $LDAP_SERVER="$prefix://$LDAP_SERVER:$LDAP_PORT/";
        VERBOSE("LDAP_SSL == $LDAP_SSL == ldap_connect($LDAP_SERVER)",__LINE__);
        $ldap_connection=@ldap_connect($LDAP_SERVER);


        if(!$ldap_connection){
            $this->error="Failed to connect to DC $LDAP_SERVER in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
            VERBOSE("Connect:: Failed to connect to DC $LDAP_SERVER",__LINE__);
            $this->TESTED_SERVERS[]="$LDAP_SERVER(<strong>{failed_connect_ldap}</strong>)";
            $this->CloseConnection();
            return false;
        }

        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

        if($LDAP_SSL==1) {
            ldap_set_option($this->ldap_connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        }

        if(isset($MainConfig["LDAP_DN"])){
            if($MainConfig["LDAP_DN"]<>null){$Username=$MainConfig["LDAP_DN"];}
        }else{
            $Username=$this->BuildAdUser();
            $Password=$this->KerbAuthInfos["WINDOWS_SERVER_PASS"];
        }

        if(isset($MainConfig["LDAP_PASSWORD"])){
            if($MainConfig["LDAP_PASSWORD"]<>null){$Password=$MainConfig["LDAP_PASSWORD"];}
        }
        if(is_null($Username)){
            $Username="";
        }

        if(trim($Username)==null){
            VERBOSE("Failed to connect to DC $LDAP_SERVER without any username set",__LINE__);
            $this->error="connect:: Failed to connect to DC without any username set".
                __CLASS__."/".__FUNCTION__." line:".__LINE__;
            return false;
        }
        $this->UserConnected=$Username;


        VERBOSE("ldap_bind(CONN,$Username,$Password)",__LINE__);
        $bind=ldap_bind($ldap_connection, $Username, $Password);

        if(!$bind){
            $this->SyslogAd("{$LDAP_SERVER}:{$LDAP_PORT} $Username bind failed");
            $this->TESTED_SERVERS[]="$LDAP_SERVER (<strong>{login_Failed_to_connect_to_DC}</strong>)";
            @ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
            $error=ldap_errno($ldap_connection);
            $errstr=@ldap_err2str($error);
            VERBOSE("Error: $error Failed to login as $Username to DC {$LDAP_SERVER}:{$LDAP_PORT} &laquo;$errstr - $extended_error",__LINE__);
            $this->error="$error Failed to login as $Username to DC {$LDAP_SERVER} `$errstr - $extended_error` in ".
                __CLASS__."/".__FUNCTION__." line:".__LINE__;
            return false;
        }

        $this->TESTED_SERVERS[]="$LDAP_SERVER";
        $this->ldap_connection=$ldap_connection;

        VERBOSE(" - - - - - - SUCCESS - - - - - -",__LINE__);
        return true;


    }

    public function BuildAdUser(){
        if(!isset($this->KerbAuthInfos["LDAP_DN"])){$this->KerbAuthInfos["LDAP_DN"]="";}
        if(!isset($this->KerbAuthInfos["LDAP_PASSWORD"])){$this->KerbAuthInfos["LDAP_PASSWORD"]="";}
        if(is_null($this->KerbAuthInfos["LDAP_DN"])){$this->KerbAuthInfos["LDAP_DN"]="";}

        $this->KerbAuthInfos["LDAP_DN"]=trim($this->KerbAuthInfos["LDAP_DN"]);

        if(trim($this->KerbAuthInfos["LDAP_DN"])==null){
            $this->VERBOSE("Err LDAP_DN:{$this->KerbAuthInfos["LDAP_DN"]} == NULL`",__LINE__);
            $error_plus="LDAP_DN = null";
            if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){
                $SMB=$this->SAMBA_GetNetAdsInfos();
                $array["FQDN"]=$SMB["LDAP server name"];
                $this->KerbAuthInfos["FQDN"]=$array["FQDN"];
                if($array["FQDN"]<>null){$FGH=explode(".",$array["FQDN"]);unset($FGH[0]);
                    $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=@implode(".", $FGH);}
            }

            if(($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]<>null) && ($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]<>null)){
                $Username="{$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]}@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}";
            }
            $this->ldap_password=$this->KerbAuthInfos["WINDOWS_SERVER_PASS"];
        }else{
            $Username=$this->KerbAuthInfos["LDAP_DN"];
            $this->ldap_password=$this->KerbAuthInfos["LDAP_PASSWORD"];
            if(!preg_match("#(.+?)@#", $Username)){$Username="$Username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}";}
        }

        Return $Username;

    }

    private function Debuglogs($text=null,$function=null,$line=null){
        if(!$this->debug){return;}
        if($text==null){return;}
        $linetext=null;

        if($function==null){
            if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}
            if(is_array($trace)){
                $filename=basename($trace[1]["file"]);
                $function=$trace[1]["function"];
                $line=$trace[1]["line"];
            }
        }

        $linetext=$text;
        if($function<>null){$linetext="$function/$line $linetext";}else{
            if($line<>null){
                $linetext="$line $linetext";
            }
        }


        if(function_exists("syslog")){
            $LOG_SEV=LOG_INFO;
            openlog("authenticator", LOG_PID , LOG_SYSLOG);
            syslog($LOG_SEV, $linetext);
            closelog();

        }
    }

}