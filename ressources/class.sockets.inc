<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

define("OSPF_PID_PATH","/var/run/quagga/ospfd.pid");
define("ZEBRA_PID_PATH","/run/quagga/zebra.pid");
define("OSPF_CONF","/etc/quagga/ospfd.conf");
define("FPDF_FONTPATH",dirname(__FILE__).'/externals/fpdf/font');

if(!defined("ARTICA_ROOT")){define('ARTICA_ROOT', "/usr/share/artica-postfix");}
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
if(!defined("UPLOAD_DIR")){define("UPLOAD_DIR","/usr/share/artica-postfix/ressources/conf/upload");}
ini_set("memory_limit", "1024M");
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}

if(!isset($GLOBALS["DISABLE_MEMCACHE_SETTINGS"])){$GLOBALS["DISABLE_MEMCACHE_SETTINGS"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableMemCacheSettings"));}
if(isset($_SESSION["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
if(!isset($GLOBALS["DEBUG_INCLUDES"])){$GLOBALS["DEBUG_INCLUDES"]=false;}
if(!isset($GLOBALS["LOGON-PAGE"])){$GLOBALS["LOGON-PAGE"]=false;}
include_once(dirname(__FILE__)."/class.semaphores.php");
include_once(dirname(__FILE__)."/class.memcached.inc");
include_once(dirname(__FILE__)."/logs.inc");
include_once(dirname(__FILE__)."/class.stats-appliance.inc");
include_once(dirname(__FILE__)."/class.sqlite.inc");
if(function_exists("debug_mem")){debug_mem();}

class sockets{
    var $remote_ip;
    var $remote_port;
    var $error=false;
    var $semaphore_memory=9024000;
    var $ArticaMetaEnabled=0;
    public $SquidPerformance;
    public $EnableIntelCeleron;
    public $mysql_error=null;
    public $REST_API_TIMEOUT=13;

    function __construct(){

        if(isset($_GET["verbose"])){
            $GLOBALS["VERBOSE"]=true;
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
            ini_set('error_prepend_string',null);
            ini_set('error_append_string',null);
        }

        if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}

        if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        if(!isset($GLOBALS["VERBOSE_MASTER"])){$GLOBALS["VERBOSE_MASTER"]=false;}
        if(!isset($GLOBALS["DEBUG"])){$GLOBALS["DEBUG"]=false;}
        if(!isset($GLOBALS["DEBUG_SOCK"])){$GLOBALS["DEBUG_SOCK"]=false;}
        if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$this->GET_INFO("ArticaLogDir");}
        if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }
        $this->MEMCACHE_ENABLED();
        if(class_exists("lib_memcached")){
            if(!isset($GLOBALS["MEMCACHE_CLASS"])){$GLOBALS["MEMCACHE_CLASS"]=new lib_memcached();}
        }

        if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        $GLOBALS["posix_getuid"]=1000;
        if(function_exists("posix_getuid")){ $GLOBALS["posix_getuid"]=posix_getuid();}

        $this->ArticaMetaEnabled=$this->APC_GET("ArticaMetaEnabled");
        $this->SetTimeZone();
        $this->remote_ip="127.0.0.1";
        $this->SquidPerformance=intval($this->GET_INFO("SquidPerformance"));
        $this->EnableIntelCeleron=intval($this->GET_INFO("EnableIntelCeleron"));

    }
    public function CLUSTER_NGINX($service_id){
        $PowerDNSEnableClusterMaster=intval($this->GET_INFO("PowerDNSEnableClusterMaster"));
        if($PowerDNSEnableClusterMaster==0){
            $this->SET_INFO("CLUSTER_NGINX_ARRAY",base64_encode(serialize(array())));
            return true;
        }
        $this->SET_INFO("ClusterWaitNotify",time());
        $CLUSTER_NGINX_ARRAY=$this->unserializeb64($this->GET_INFO("CLUSTER_NGINX_ARRAY"));
        $CLUSTER_NGINX_ARRAY[$service_id]=true;
        $this->SET_INFO("CLUSTER_NGINX_ARRAY",base64_encode(serialize($CLUSTER_NGINX_ARRAY)));
        return true;
    }
    public function CLUSTER_PACKAGE(){
        $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
        if($PowerDNSEnableClusterMaster==0){
            return false;
        }
        $this->REST_API("/cluster/server/build");
        return true;
    }

    public function unserializeb64($data):array{
        if(is_null($data)){
            return array();
        }
        if(strlen($data)<3){
            return array();
        }
        $Decoded=base64_decode($data);
        if(!$Decoded){
            return array();
        }
        $Unser=@unserialize($Decoded);
        if(!$Unser){
            return array();
        }
        if(!is_array($Unser)){
            return array();
        }
        return $Unser;
    }
    function CATEGORIZED_WEBSITES():int{
        if($this->CORP_LICENSE()){
            $OFFICIALS_CATZ=intval($this->GET_INFO("OFFICIALS_CATZ"));
            return $OFFICIALS_CATZ;

        }
        
        return 0;

    }
    function build_progress($pourc,$text){
        $fname=$GLOBALS["GENPROGGNAME"];
        $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/$fname";
        echo "[{$pourc}%] $text\n";
        $array["POURC"]=$pourc;
        $array["TEXT"]=$text;
        if($GLOBALS["VERBOSE"]){echo "Saving {$GLOBALS["CACHEFILE"]}\n";}
        @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
        @chmod($GLOBALS["CACHEFILE"],0755);
        if($GLOBALS["OUTPUT"]){sleep(1);}
    }
    private function heads_exec_nice():bool{
        $Current_Niceness_Value = intval(explode(" ",file_get_contents("/proc/".getmypid()."/stat"))[18]);
        $cgroupsEnabled=trim($this->GET_INFO("cgroupsEnabled"));
        if($cgroupsEnabled==1){return true;}
        $ProcNice=intval($this->GET_INFO("ProcNice"));
        if($ProcNice==0){$ProcNice=19;}
        if($ProcNice==$Current_Niceness_Value){return true;}
        if(!function_exists("proc_nice")){ return true;}
        if(function_exists("proc_nice")){proc_nice($ProcNice);}
        return true;
    }
    private function go_exec_syslog($text):bool{
        if(!function_exists("openlog")){return false;}
        openlog("go-exec", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, "[PHP][class.sockets.inc] $text");
        closelog();
        return true;
    }
    private function is_curl_error($curl_error_num=0){
        if($curl_error_num==0){
            return false;
        }
        if($curl_error_num==7){
            $this->mysql_error="(7) Error Failed to connect() to host";
            return true;
        }
        if($curl_error_num==28){
            $this->mysql_error="(28) Error Timed Out";
            return true;
        }

        if($curl_error_num==52){
            $this->mysql_error="(52) Empty reply from server";
            return true;
        }
        VERBOSE("Curl ERR: $curl_error_num",__LINE__);
        return false;
    }
    public function KERNEL_GET($key):string{
        $key=urlencode($key);
        $sysctl=json_decode($this->REST_API("/system/kernel/get/$key"));
        if(property_exists($sysctl,"Value")){return $sysctl->Value;}
        return "";
    }
    public function KERNEL_SET($key,$value):bool{
        $key=urlencode($key);
        $value=urlencode($value);
        $this->REST_API("/system/kernel/set/$key/$value");
        return true;
    }
    public function REST_API_POST($endpoint,$array):string{
        if(!function_exists("curl_init")){
            $array["Status"]=false;
            $array["Error"]="curl_init no such function";
            return json_encode($array);
        }

        $cURLConnection=$this->REST_API_CLIENT($endpoint);
        curl_setopt($cURLConnection, CURLOPT_POST, true);
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, http_build_query($array));

        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        curl_close($cURLConnection);
        if($this->is_curl_error($curl_error_num)){
            VERBOSE("POST $endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            $array["Status"]=false;
            $array["Error"]=$this->mysql_error;
            return json_encode($array);
        }

        $json=json_decode($resp);
        if (json_last_error()> JSON_ERROR_NONE) {
            $this->mysql_error="Json Returned".json_last_error_msg()." ($resp)";
            $array["Status"]=false;
            $array["Error"]=$this->mysql_error;
            return json_encode($array);
        }
        if(!property_exists($json,"Status")){
            if(property_exists($json,"status")){
                $json->Status=$json->status;
            }
        }
        if(!property_exists($json,"Error")){
            if(property_exists($json,"error")){
                $json->Error=$json->error;
            }
        }
        $resp=json_encode($json);
        return $resp;
    }
    private function REST_WATCH_CLIENT($endpoint){
        $unixSocketPath="/usr/share/artica-postfix/bin/run/articawatch.sock";
        $cURLConnection = curl_init();
        if(substr($endpoint,0,1)=="/"){
            $endpoint=substr($endpoint,1,strlen($endpoint));
        }
        $Uri="http://localhost/$endpoint";
        $CURLOPT_TIMEOUT=13;
        if($this->REST_API_TIMEOUT>13){
            $CURLOPT_TIMEOUT=$this->REST_API_TIMEOUT;
        }

        curl_setopt($cURLConnection, CURLOPT_TIMEOUT, $CURLOPT_TIMEOUT);
        curl_setopt($cURLConnection, CURLOPT_UNIX_SOCKET_PATH, $unixSocketPath);
        curl_setopt($cURLConnection, CURLOPT_URL,$Uri);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        return $cURLConnection;

    }
    private function GENERIC_API_CLIENT($unixSocketPath,$endpoint){
        $cURLConnection = curl_init();
        if(substr($endpoint,0,1)=="/"){
            $endpoint=substr($endpoint,1,strlen($endpoint));
        }
        $Uri="http://localhost/$endpoint";
        $CURLOPT_TIMEOUT=13;
        if($this->REST_API_TIMEOUT>13){
            $CURLOPT_TIMEOUT=$this->REST_API_TIMEOUT;
        }

        curl_setopt($cURLConnection, CURLOPT_TIMEOUT, $CURLOPT_TIMEOUT);
        curl_setopt($cURLConnection, CURLOPT_UNIX_SOCKET_PATH, $unixSocketPath);
        curl_setopt($cURLConnection, CURLOPT_URL,$Uri);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        return $cURLConnection;

    }


    private function REST_API_CLIENT($endpoint){
        $unixSocketPath="/usr/share/artica-postfix/bin/run/articarest.sock";
        return $this->GENERIC_API_CLIENT($unixSocketPath,$endpoint);
    }
    private function GENERIC_API($endpoint,$cURLConnection){
        if(!function_exists("curl_init")){
            $array["Status"]=false;
            $array["Error"]="curl_init no such function";
            return json_encode($array);
        }
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="Error Connecting to the REST API server $curl_error_num $this->mysql_error";
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;
    }
    public function HACLUSTERCLIENT_API($endpoint){
        $unixSocketPath="/usr/share/artica-postfix/bin/run/haclusterclient.sock";
        $cURLConnection=$this->GENERIC_API_CLIENT($unixSocketPath,$endpoint);
        return $this->GENERIC_API($endpoint,$cURLConnection);
    }
    public function MILTER_API($endpoint):string{
        $unixSocketPath="/usr/share/artica-postfix/bin/run/artica-milter.sock";
        $cURLConnection=$this->GENERIC_API_CLIENT($unixSocketPath,$endpoint);
        return $this->GENERIC_API($endpoint,$cURLConnection);

    }
    public function REST_ARTWATCH($endpoint):string{
        if(!function_exists("curl_init")){
            $array["Status"] = false;
            $array["Error"] = "Error curl_init No such function available";
            return json_encode($array);
        }
        $cURLConnection=$this->REST_WATCH_CLIENT($endpoint);

        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="REST_ARTWATCH Error Connecting to the Watchdog API server $curl_error_num $this->mysql_error";
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(preg_match("#\/squidclient#",$endpoint)){
            return $resp;
        }

        if(function_exists("json_decode")) {
            json_decode($resp);
            if (json_last_error() > JSON_ERROR_NONE) {
                $array["Status"] = false;
                $array["Error"] = "Error parsing Json [$resp] HTTP CODE $HTTP_CODE " . json_last_error_msg();
                return json_encode($array);
            }
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;
    }

    public function REST_API_NFQUEUE($endpoint):string{
        if(!function_exists("curl_init")){
            $array["Status"] = false;
            $array["Error"] = "Error curl_init No such function available";
            return json_encode($array);
        }
        $cURLConnection=$this->GENERIC_API_CLIENT("/run/nfqueue.sock",$endpoint);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="Error Connecting to the REST API server $curl_error_num $this->mysql_error";
            $array["JsonFailed"]=true;
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $array["JsonFailed"]=true;
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(preg_match("#\/squidclient#",$endpoint)){
            return $resp;
        }

        if(function_exists("json_decode")) {
            json_decode($resp);
            if (json_last_error() > JSON_ERROR_NONE) {
                $array["Status"] = false;
                $array["Error"] = "Error parsing Json [$resp] HTTP CODE $HTTP_CODE " . json_last_error_msg();
                $array["JsonFailed"]=true;
                return json_encode($array);
            }
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;


    }
    public function REST_API($endpoint):string{
        if(!function_exists("curl_init")){
            $array["Status"] = false;
            $array["Error"] = "Error curl_init No such function available";
            $array["Info"] ="";
            return json_encode($array);
        }

        $cURLConnection=$this->REST_API_CLIENT($endpoint);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="Error Connecting to the REST API server $curl_error_num $this->mysql_error";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(preg_match("#\/squidclient#",$endpoint)){
            return $resp;
        }

        if(function_exists("json_decode")) {
            json_decode($resp);
            if (json_last_error() > JSON_ERROR_NONE) {
                $array["Status"] = false;
                $array["Error"] = "Error parsing Json [$resp] HTTP CODE $HTTP_CODE " . json_last_error_msg();
                $array["JsonFailed"]=true;
                $array["Info"] ="";
                return json_encode($array);
            }
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;
    }

    public function REST_API_NGINX($endpoint):string{
        if(!function_exists("curl_init")){
            $array["Status"] = false;
            $array["Error"] = "Error curl_init No such function available";
            return json_encode($array);
        }

        $cURLConnection=$this->GENERIC_API_CLIENT("/run/reverse-proxy.sock",$endpoint);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="Error Connecting to the REST API server $curl_error_num $this->mysql_error";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(preg_match("#\/squidclient#",$endpoint)){
            return $resp;
        }

        if(function_exists("json_decode")) {
            json_decode($resp);
            if (json_last_error() > JSON_ERROR_NONE) {
                $array["Status"] = false;
                $array["Error"] = "Error parsing Json [$resp] HTTP CODE $HTTP_CODE " . json_last_error_msg();
                $array["JsonFailed"]=true;
                $array["Info"] ="";
                return json_encode($array);
            }
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;
    }
    public function REST_API_FIRECR($endpoint):string{
        if(!function_exists("curl_init")){
            $array["Status"] = false;
            $array["Error"] = "Error curl_init No such function available";
            return json_encode($array);
        }

        $cURLConnection=$this->GENERIC_API_CLIENT("/run/firecracker-daemon.sock",$endpoint);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="Error Connecting to the REST API server $curl_error_num $this->mysql_error";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(preg_match("#\/squidclient#",$endpoint)){
            return $resp;
        }

        if(function_exists("json_decode")) {
            json_decode($resp);
            if (json_last_error() > JSON_ERROR_NONE) {
                $array["Status"] = false;
                $array["Error"] = "Error parsing Json [$resp] HTTP CODE $HTTP_CODE " . json_last_error_msg();
                $array["JsonFailed"]=true;
                $array["Info"] ="";
                return json_encode($array);
            }
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;
    }
    public function REST_API_SAMBA($endpoint):string{
        if(!function_exists("curl_init")){
            $array["Status"] = false;
            $array["Error"] = "Error curl_init No such function available";
            return json_encode($array);
        }

        $cURLConnection=$this->GENERIC_API_CLIENT("/run/file-sharing.sock",$endpoint);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        $HTTP_CODE=curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);
        if ($curl_error_num>0){
            writelogs("$endpoint Error $curl_error_num",__FUNCTION__,__FILE__,__LINE__);
        }

        if($this->is_curl_error($curl_error_num)){
            $array["Status"]=false;
            $array["Error"]="Error Connecting to the REST API server $curl_error_num $this->mysql_error";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            VERBOSE("$endpoint $this->mysql_error",__LINE__);
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return json_encode($array);
        }

        if($HTTP_CODE==404){
            $array["Status"]=false;
            $array["Error"]="$endpoint<br>404 Not found, did you use the Correct version of the Web API service?<br>If so, try restarting it to update it.";
            $array["JsonFailed"]=true;
            $array["Info"] ="";
            $this->go_exec_syslog("[FATAL] 404 Not found");
            return json_encode($array);
        }

        if(preg_match("#\/squidclient#",$endpoint)){
            return $resp;
        }

        if(function_exists("json_decode")) {
            json_decode($resp);
            if (json_last_error() > JSON_ERROR_NONE) {
                $array["Status"] = false;
                $array["Error"] = "Error parsing Json [$resp] HTTP CODE $HTTP_CODE " . json_last_error_msg();
                $array["JsonFailed"]=true;
                $array["Info"] ="";
                return json_encode($array);
            }
        }

        if(function_exists("VERBOSE")){
            VERBOSE("$endpoint= [$resp]",__LINE__);
        }
        return $resp;
    }
    public function go_exec_nowait($cmd){
       $cmd = base64_encode($cmd);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://127.0.0.1:3334/$cmd");
        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=curl_errno($cURLConnection);
        curl_close($cURLConnection);
        if($this->is_curl_error($curl_error_num)){
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            return false;
        }

        if ($resp == "TRUE") {
            return true;
        }
        return false;

    }
    public function go_exec($cmd){
        $this->error=false;
        writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
        $cmd = base64_encode($cmd);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://127.0.0.1:3334/out/$cmd");
        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($cURLConnection);

        $curl_error_num=intval(curl_errno($cURLConnection));
        curl_close($cURLConnection);
        if($this->is_curl_error($curl_error_num)){
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            $this->error=true;
            return array();
        }

        $array=explode("\n",$resp);
        if(!is_array($array)){return array();}
        return $array;
    }
    public function heads_exec_root($argv):bool{
        $GLOBALS["YESCGROUP"]=true;
        $GLOBALS["FORCE"]=false;
        $GLOBALS["DEBUG"]=false;
        $GLOBALS["VERBOSE"]=false;
        $GLOBALS["VERBOSE2"]=false;
        $GLOBALS["OUTPUT"]=false;
        $GLOBALS["SCHEDULE_ID"]=0;
        $GLOBALS["ULIMITED"]=false;
        $GLOBALS["DEBUG_MEMCACHE"]=false;
        $GLOBALS["BYPASS"]=true;
        $GLOBALS["REBUILD"]=false;
        $GLOBALS["OLD"]=false;
        $GLOBALS["AS_ROOT"]=false;
        $GLOBALS["MONIT"]=false;
        $GLOBALS["FORCE"]=false;
        $GLOBALS["RECONFIGURE"]=false;
        $GLOBALS["SWAPSTATE"]=false;
        $GLOBALS["NOSQUIDOUTPUT"]=true;
        $GLOBALS["BY_SYSLOG"]=false;
        $GLOBALS["ALL"]=false;
        $GLOBALS["SCHEDULE"]=false;
        $GLOBALS["DISABLE_WATCHDOG"]=false;
        $GLOBALS["MYPID"]=0;
        $GLOBALS["BYCONSOLE"]=false;
        $GLOBALS["RELOAD"]=false;
        $GLOBALS["AS_DOCKER_WEB"]=false;

        include_once("/usr/share/artica-postfix/framework/class.unix.inc");
        include_once("/usr/share/artica-postfix/framework/frame.class.inc");
        include_once("/usr/share/artica-postfix/framework/class.ini-frame.inc");
        include_once(dirname(__FILE__).'/class.os.system.inc');
        include_once(dirname(__FILE__).'/class.tcpip.inc');

        if(function_exists("posix_getuid")){
            if(posix_getuid()==0){
                $GLOBALS["AS_ROOT"]=true;
            }
        }
        $GLOBALS["COMMANDLINE"]=implode(" ",$argv);
        $this->heads_exec_nice();
        if(preg_match("#--reload#",$GLOBALS["COMMANDLINE"])){$GLOBALS["RELOAD"]=true;}
        if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
        if(preg_match("#schedule-id=([0-9]+)#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
        if(preg_match("#--reconfigure#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["RECONFIGURE"]=true;}
        if(preg_match("#--syslog#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["BY_SYSLOG"]=true;}
        if(preg_match("#--all#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["ALL"]=true;}
        if(preg_match("#--schedule#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["SCHEDULE"]=true;}
        if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
        if(preg_match("#--byconsole#",$GLOBALS["COMMANDLINE"])){$GLOBALS["BYCONSOLE"]=true;}

        if(is_file("/etc/docker-method.conf")){
            $type=trim(@file_get_contents("/etc/docker-method.conf"));
            if($type=="webadm"){$GLOBALS["AS_DOCKER_WEB"]=true;}
        }

        if(!isset($GLOBALS["resolveIP2HOST"])){
            $GLOBALS["resolveIP2HOST"]=intval($this->GET_INFO("resolveIP2HOST"));
        }

        if(!isset($GLOBALS["ARTICALOGDIR"])){
            $GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");
            if($GLOBALS["ARTICALOGDIR"]==null){
                $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix";
            }
        }

        if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
        $GLOBALS["AS_ROOT"]=true;
        if (function_exists("posix_getuid")) {
                if (posix_getuid()<>0) {
                    die("Cannot be used in web server mode\n\n");
                }
        }
        if (preg_match("#--force#", implode(" ", $argv))) {
            $GLOBALS["FORCE"]=true;
        }
        if (preg_match("#--monit#", implode(" ", $argv))) {
            $GLOBALS["MONIT"]=true;
        }
        if (preg_match("#--verbose-memcache#", implode(" ", $argv))) {
            $GLOBALS["DEBUG_MEMCACHE"]=true;
        }
        if (preg_match("#--verbose#", implode(" ", $argv))) {
            $GLOBALS["VERBOSE"]=true;
            $GLOBALS["DEBUG"]=true;
            ini_set('html_errors', 0);
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
        }
        if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
        if(function_exists("getmypid")){$GLOBALS["MYPID"]=getmypid();}

        if($GLOBALS["MYPID"]>0) {
            if (is_file("/usr/bin/cgclassify")) {
                if (is_dir("/cgroups/blkio/php")) {
                    shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php " . $GLOBALS["MYPID"]);
                }
            }
        }


        return true;

    }
    function file_ext($filename): string{
        return strtolower(str_replace(".", "", strrchr($filename, ".")));
    }
    public function isNextSP():int{
        if(isset( $GLOBALS["isNextSP"])){return $GLOBALS["isNextSP"];}
        $Myversion=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
        $ArticaUpdateServicesPacks=unserialize($this->GET_INFO("ArticaUpdateServicesPacks"));
        if(!is_array($ArticaUpdateServicesPacks)){return 0;}
        if(!isset($ArticaUpdateServicesPacks[$Myversion])){return 0;}
        if(!isset($ArticaUpdateServicesPacks[$Myversion]["VERSION"])){return 0;}
        $RemoteServicePack=$ArticaUpdateServicesPacks[$Myversion]["VERSION"];
        $CurrentServicePack=$this->getFrameWork("artica.php?SPVersion=yes");
        if($RemoteServicePack>$CurrentServicePack){
            $GLOBALS["isNextSP"]=$RemoteServicePack;
            return intval($GLOBALS["isNextSP"]);
        }
        $GLOBALS["isNextSP"]=0;
        return $GLOBALS["isNextSP"];
    }
    public function DNSDIST_WEBFILTER_ENABLED():bool{
        $DnsdistAsBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsdistAsBalancer"));
        if($DnsdistAsBalancer==1){return false;}
        $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
        if($Go_Shield_Server_Enable==1){return true;}
        return false;

    }
    public function IsKerbAuth() {
        $EnableKerbAuth=0;
        $time=date("YmdHm");
        if(!isset($_SESSION["EnableKerbAuth"][$time])){
            $EnableKerbAuth=intval($this->GET_INFO("EnableKerbAuth"));
            if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
            unset($_SESSION["EnableKerbAuth"]);
            $_SESSION["EnableKerbAuth"][$time]=$EnableKerbAuth;
        }

        if(!isset($_SESSION["EnableSambaActiveDirectory"][$time])){
            $sock=new sockets();
            $EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
            if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
            unset($_SESSION["EnableSambaActiveDirectory"]);
            $_SESSION["EnableSambaActiveDirectory"][$time]=$EnableSambaActiveDirectory;
        }

        if($_SESSION["EnableKerbAuth"][$time]==1){$EnableKerbAuth=1;}
        if($_SESSION["EnableSambaActiveDirectory"][$time]==1){$EnableKerbAuth=1;}
        return $EnableKerbAuth;

    }
    public function  Postfix_VersionBin():int{
        $POSTFIX_VERSION=$this->GET_INFO("POSTFIX_VERSION");
        if($POSTFIX_VERSION==null){return 0;}
        $EX=explode(".",$POSTFIX_VERSION);
        return intval("{$EX[0]}{$EX[1]}{$EX[2]}");
    }
    public function Bandwidthd_enabled(){
        $TempMemInstalled=0;
        if(!is_file("/etc/artica-postfix/settings/Daemons/EnableBandwidthd")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableBandwidthd",0);}
        $EnableBandwidthd=$this->GET_INFO("EnableBandwidthd");
        $EnableIntelCeleron=intval($this->GET_INFO("EnableIntelCeleron"));
        if(is_file("/etc/artica-postfix/settings/Daemons/TempMemInstalled")){
            $TempMemInstalled=intval($this->GET_INFO("TempMemInstalled"));
        }


        if($EnableIntelCeleron==1){$EnableBandwidthd=0;}
        if($TempMemInstalled>0){
            if($TempMemInstalled<624288){$EnableBandwidthd=0;}
        }
        return $EnableBandwidthd;

    }
    public FUNCTION GET_GOSHIELD_VERSION():string{
        $Go_Shield_Server_Addr = $this->GET_INFO("Go_Shield_Server_Addr");
        $Go_Shield_Server_Port = intval($this->GET_INFO("Go_Shield_Server_Port"));
        if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
        if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
        $Go_Shield_Server_Version = "0.0.0.0";
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/get-version");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        $resp = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        $jsonArrayResponse = json_decode($resp,true);
        if(isset($jsonArrayResponse["version"])){$Go_Shield_Server_Version = $jsonArrayResponse["version"];}
        VERBOSE("GO_SHIELD_SERVER_VERSION = [$Go_Shield_Server_Version]",__LINE__);
        return $Go_Shield_Server_Version;

    }
    function GET_GOSHIELD_CACHES_ENTRIES():int{
        if(!function_exists("curl_setopt")){return 0;}
        if(!function_exists("json_decode")){return 0;}
        $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
        $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
        $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
        if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
        if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

        if($Go_Shield_Server_Enable==0) {return 0;}
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/db/len");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        $resp = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        return intval(json_decode($resp,true));
    }
    public function HOTSPOT_IS_AUTH_EXISTS():bool{
        $VERBOSEOK=false;
        if(function_exists("VERBOSE")){$VERBOSEOK=true;}
        $HotSpotAuthentAD=intval($this->GET_INFO("HotSpotAuthentAD"));
        $EnableActiveDirectoryFeature=intval($this->GET_INFO("EnableActiveDirectoryFeature"));
        if (!$this->CORP_LICENSE()) { $EnableActiveDirectoryFeature=0;}
        if($EnableActiveDirectoryFeature==0){$HotSpotAuthentAD=0;}
        if($HotSpotAuthentAD==1){
            if($VERBOSEOK){VERBOSE("HotSpotAuthentAD = TRUE",__LINE__);}
            return true;
        }
        $HotSpotAuthentLocalLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentLocalLDAP"));
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        if($EnableOpenLDAP==0){$HotSpotAuthentLocalLDAP = 0;}
        if($HotSpotAuthentLocalLDAP==1){
            if($VERBOSEOK){VERBOSE("HotSpotAuthentLocalLDAP = TRUE",__LINE__);}
            return true;
        }
        $HotSpotAuthentVoucher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentVoucher"));
        if($HotSpotAuthentVoucher==1){
            if($VERBOSEOK){VERBOSE("HotSpotAuthentVoucher = TRUE",__LINE__);}
            return true;
        }
        $HotSpotAutoLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLogin"));
        if($HotSpotAutoLogin==1){
            if($VERBOSEOK){VERBOSE("HotSpotAutoLogin = TRUE",__LINE__);}
            return true;
        }
        $HotSpotWIFI4EU_ENABLE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_ENABLE"));
        if($HotSpotWIFI4EU_ENABLE==1){
            if($VERBOSEOK){VERBOSE("HotSpotWIFI4EU_ENABLE = TRUE",__LINE__);}
            return true;
        }
        return false;
    }
    public function GET_PROXY_STRING($form=null){
        $ini=new Bs_IniHandler($this->GET_INFO("ArticaProxySettings"));
        $ArticaProxyServerEnabled=$this->toBool($ini->_params["PROXY"]["ArticaProxyServerEnabled"]);
        if($ArticaProxyServerEnabled){
            $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
            $ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
            if($ArticaProxyServerPort==0){$ArticaProxyServerPort=3128;}
            $ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
            $ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
            $credentials=null;
            if($ArticaProxyServerUsername<>null){
                if($form==null){
                    return "$ArticaProxyServerUsername:$ArticaProxyServerUserPassword@$ArticaProxyServerName:$ArticaProxyServerPort";
                }
            }

            if($form==null){return "$ArticaProxyServerName:$ArticaProxyServerPort";}
            return "";

        }

        $SQUID_INSTALLED=intval($this->GET_INFO("SQUID_INSTALLED"));
        if($SQUID_INSTALLED==1){
            $SQUIDEnable=intval($this->GET_INFO("SQUIDEnable"));
            if($SQUIDEnable==1){
                $SquidMgrListenPort=intval($this->GET_INFO("SquidMgrListenPort"));
                if($SquidMgrListenPort>0){
                    return "127.0.0.1:$SquidMgrListenPort";
                }
            }
        }
        return null;
    }
    private function toBool($var) {
        if (!is_string($var)) return (bool) $var;
        switch (strtolower($var)) {
            case '1':
            case 'true':
            case 'on':
            case 'yes':
            case 'y':
                return true;
            default:
                return false;
        }
    }
    public function EnableUfdbGuard_local(){
        $EnableUfdbGuard=$this->EnableUfdbGuard();
        $UseRemoteUfdbguardService=intval($this->GET_INFO("UseRemoteUfdbguardService"));
        if($UseRemoteUfdbguardService==1){return 0;}
        return $EnableUfdbGuard;
    }
    public function ksrn_sockets($sitename=null):array{
        if($sitename==null){return array("STATUS"=>false, "ERROR"=> "No website returned", "URI-LENGTH"=>0, "URI"=>'');}
        $KSRNRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemote"));
        VERBOSE("KSRNRemote = $KSRNRemote",__LINE__);

        if(!function_exists("curl_setopt")){
            return array("STATUS"=>false,"ERROR"=> "Module curl not istalled");
        }
        $KSRNRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
        $KSRNRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));

        if($KSRNRemoteAddr==null){$KSRNRemoteAddr="127.0.0.1";}
        if($KSRNRemotePort==0){$KSRNRemotePort=3333;}

        $ch = curl_init();
        $MAIN_URI ="http://$KSRNRemoteAddr:$KSRNRemotePort/get-categories/$sitename";

        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_URL, $MAIN_URI);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOPROXY, '*');

        $response = trim(curl_exec($ch));
        VERBOSE("curl_exec: $MAIN_URI ",__LINE__);
        VERBOSE("RETURN [$response]",__LINE__);
        $errno = curl_errno($ch);
        if ($errno > 0) {
            $error="Error $errno " . curl_error($ch) . " $response";
            curl_close($ch);
            return array("STATUS"=>false,"ERROR"=> $error,"URI-LENGTH"=>strlen($MAIN_URI));

        }
        $CURLINFO_HTTP_CODE = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        if ($CURLINFO_HTTP_CODE <> 200) {
            return array("STATUS"=>false,
                "ERROR"=> "HTTP error code $CURLINFO_HTTP_CODE",
                "URI-LENGTH"=>strlen($MAIN_URI),
                "URI"=>$MAIN_URI
            );
        }
        return array("STATUS"=> true,"DESTINATION"=>"$KSRNRemoteAddr:$KSRNRemotePort", "RESPONSE"=>$response);
    }
    public function EnableUfdbGuard($aslog=false){
        if(!$aslog){
            if(isset($GLOBALS["EnableUfdbGuard"])){return intval($GLOBALS["EnableUfdbGuard"]);}
        }
        $SQUIDEnable=1;
        $SquidUFDBUrgency=0;
        $EnableUfdbGuard=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableUfdbGuard'));
        $EnableUfdbGuard2=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableUfdbGuard2'));
        $SquidUrgency=intval($this->GET_INFO("SquidUrgency"));
        $UseRemoteUfdbguardService=intval($this->GET_INFO("UseRemoteUfdbguardService"));
        $PDSNInUfdb=intval($this->GET_INFO("PDSNInUfdb"));
        if($PDSNInUfdb==1){$GLOBALS["EnableUfdbGuard"]=1;return 1;}
        if(is_file("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency")){
            $SquidUFDBUrgency=intval($this->GET_INFO("SquidUFDBUrgency"));
        }

        $isLog[]="UseRemoteUfdbguardService: $UseRemoteUfdbguardService";
        $isLog[]="EnableUfdbGuard: $EnableUfdbGuard";
        $isLog[]="EnableUfdbGuard2: $EnableUfdbGuard2";
        $isLog[]="SquidUrgency: $SquidUrgency";
        $isLog[]="SQUIDEnable: $SQUIDEnable";
        $isLog[]="SquidUFDBUrgency: $SquidUFDBUrgency";


        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: UseRemoteUfdbguardService: $UseRemoteUfdbguardService\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EnableUfdbGuard..........: $EnableUfdbGuard\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EnableUfdbGuard2.........: $EnableUfdbGuard2\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: SquidUrgency.............: $SquidUrgency\n";}

        if($UseRemoteUfdbguardService==1){return 1;}


        if($EnableUfdbGuard==0){
            if($EnableUfdbGuard2==1){
                $this->SET_INFO("EnableUfdbGuard", 1);
                $EnableUfdbGuard=1;
            }
        }


        $GLOBALS["EnableUfdbGuard"]=$EnableUfdbGuard;
        if($aslog){return $isLog;}
        return intval($EnableUfdbGuard);
    }
    public function SetTimeZone(){
        if(!function_exists("getLocalTimezone")){return;}
        if(isset($GLOBALS["TIMEZONES"])){
            @date_default_timezone_set($GLOBALS["TIMEZONES"]);
            return;
        }

        if(isset($_SESSION["TIMEZONES"])){
            @date_default_timezone_set($_SESSION["TIMEZONES"]);
            $GLOBALS["TIMEZONES"]=$_SESSION["TIMEZONES"];
            return;

        }

        if(isset($GLOBALS["GET_TIME_ZONE"])){
            $GLOBALS["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
            $_SESSION["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
            @date_default_timezone_set($GLOBALS["TIMEZONES"]);
            return;
        }


        $timezones=$this->GET_INFO("timezones");
        if($timezones==null){$timezones="Europe/Dublin";}
        $GLOBALS["TIMEZONES"]=$timezones;
        $_SESSION["TIMEZONES"]=$timezones;
        @date_default_timezone_set($GLOBALS["TIMEZONES"]);
    }
    public function shellEscapeChars($path){
        $path=str_replace(" ","\ ",$path);
        $path=str_replace('$','\$',$path);
        $path=str_replace("&","\&",$path);
        $path=str_replace("?","\?",$path);
        $path=str_replace("#","\#",$path);
        $path=str_replace("[","\[",$path);
        $path=str_replace("]","\]",$path);
        $path=str_replace("{","\{",$path);
        $path=str_replace("}","\}",$path);
        $path=str_replace("*","\*",$path);
        $path=str_replace('"','\\"',$path);
        $path=str_replace("'","\\'",$path);
        $path=str_replace("(","\(",$path);
        $path=str_replace(")","\)",$path);
        $path=str_replace("<","\<",$path);
        $path=str_replace(">","\>",$path);
        $path=str_replace("!","\!",$path);
        $path=str_replace("+","\+",$path);
        $path=str_replace(";","\;",$path);
        return $path;
    }
    function downloadFile($url,$hostname)  {
        $url_stuff="http://$hostname:$this->remote_port/$url";
        header("Content-type: image/png");
        $binary_data=@file_get_contents($url_stuff);
        if(strlen($binary_data)==0){
            $binary_data=@file_get_contents("/usr/share/artica-postfix/img/kas-graph-no-datas.png");
        }


        echo $binary_data;
    }
    function SaveConfigFile($datas,$keyConfig){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        $this->SET_INFO($keyConfig,$datas);
    }

    private function MEMCACHE_ENABLED():bool{
        if(isset($GLOBALS["MEMCACHE_ENABLED"])){return $GLOBALS["MEMCACHE_ENABLED"];}
        if(!class_exists("Memcached")){$GLOBALS["MEMCACHE_ENABLED"]=false;return false;}
        $GLOBALS["MEMCACHE_ENABLED"]=true;
        return true;
    }
    function MEMCACHE_GET($key):string{
        $ARRAY=array();
        if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
        if(!$GLOBALS["MEMCACHE_ENABLED"]){return "";}
        if(class_exists("lib_memcached")){$memcache = new lib_memcached();
            $data=trim($memcache->getKey('ARTICA_INFOS'));
            if(strlen($data)<2) {return "";}
            $ARRAY = unserialize($data);

        }
        if(!is_array($ARRAY)){return "";}
        if(isset($ARRAY[$key])){return trim($ARRAY[$key]);}
        return "";
    }
    function TOP_NOTIFY($content,$prio="warn"){
        $notify=$this->unserializeb64($this->GET_INFO("TOP_NOTIFY"));
        $notify[]=array("TIME"=>time(),"CONTENT"=>$content,"PRIO"=>$prio);
        $newArray=base64_encode(serialize($notify));
        $this->SaveConfigFile($newArray, "TOP_NOTIFY");

    }
    function EnableClamavDaemon(){
        $EnableClamavDaemon=intval($this->GET_INFO("EnableClamavDaemon"));
        if($EnableClamavDaemon==1){return 1;}

        if(class_exists("lib_memcached")){
            $lib_memcached=new lib_memcached();
            $EnableClamavDaemonCalculated=$lib_memcached->getKey("EnableClamavDaemonCalculated");

            if($lib_memcached->MemCachedFound){
                return $EnableClamavDaemonCalculated;
            }
        }
        $EnableClamavDaemonForced=intval($this->GET_INFO("EnableClamavDaemonForced"));
        $CicapEnabled=intval($this->GET_INFO("CicapEnabled"));

        if($CicapEnabled==1){
            $lib_memcached=new lib_memcached();
            $lib_memcached->saveKey("EnableClamavDaemonCalculated", "1",1880);
            return 1;
        }

        $ClamavMilterEnabled=intval($this->GET_INFO("ClamavMilterEnabled"));
        $MimeDefangClamav=intval($this->GET_INFO("MimeDefangClamav"));
        $MimeDefangEnabled=intval($this->GET_INFO("MimeDefangEnabled"));

        if($MimeDefangEnabled==0){$MimeDefangClamav=0;}
        if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
        if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}

        if($ClamavMilterEnabled==1){$EnableClamavDaemon=1;}
        if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
        if($MimeDefangClamav==1){$EnableClamavDaemon=1;}

        return $EnableClamavDaemon;
    }
    private function BuildTemplatesConfig_defaults(){
        $GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;
        $GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]=30;
        if(!isset($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=0;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"])){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["FontColor"])){$GLOBALS["UfdbGuardHTTP"]["FontColor"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"])){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"])){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"])){$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=1;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["Family"])){$GLOBALS["UfdbGuardHTTP"]["Family"]=null;}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["AllowUnblock"])){$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]=intval($this->GET_INFO("UfdbGuardHTTPAllowUnblock"));}
        if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"]=null;}


        if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=0;}

        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){
            if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]=$this->GET_INFO("UfdbGuardHTTPBackgroundColor");}
        }

        if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"])){
            $GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"]=0;
        }


        if($GLOBALS["UfdbGuardHTTP"]["Family"]==null){
            $GLOBALS["UfdbGuardHTTP"]["Family"]="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";

        }
        if(	$GLOBALS["UfdbGuardHTTP"]["FontColor"]==null){
            if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["FontColor"]=$this->GET_INFO("UfdbGuardHTTPFontColor");}
        }
        if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"])){
            if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=$this->GET_INFO("UfdbGuardHTTPEnablePostmaster");}
        }


        if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){
            if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=$this->GET_INFO("UfdbGuardHTTPNoVersion");}
        }

        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]==null){
            if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]=$this->GET_INFO("UfdbGuardHTTPBackgroundColorBLK");}
        }

        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]==null){
            if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]=$this->GET_INFO("UfdbGuardHTTPBackgroundColorBLKBT");}
        }


        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]==null){
            if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]=$this->GET_INFO("UFDBGUARD_TITLE_1");}
        }

        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]==null){
            if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]=$this->GET_INFO("UFDBGUARD_TITLE_2");}
        }

        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]==null){
            if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]=$this->GET_INFO("UFDBGUARD_PARA1");}

        }

        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]==null){
            if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]=$this->GET_INFO("UFDBGUARD_PARA2");}
        }

        if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["AllowUnblock"])){
            $GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]=intval($this->GET_INFO("UfdbGuardHTTPAllowUnblock"));
        }

        if($GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]==1){
            $GLOBALS["UfdbGuardHTTP"]["UnbblockText1"]=$this->GET_INFO("UfdbGuardHTTPUnbblockText1");
            $GLOBALS["UfdbGuardHTTP"]["UnbblockText2"]=$this->GET_INFO("UfdbGuardHTTPUnbblockText2");
            $GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]=intval($this->GET_INFO("UfdbGuardHTTPUnbblockMaxTime"));
            if($GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]<5){$GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]=30;}
        }

        // DEF DEF
        if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"])){$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=1;}
        if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=0;}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]="#8c1919";}
        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]="{UFDBGUARD_TITLE_1}";}
        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]="{UFDBGUARD_TITLE_2}";}
        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]="{UFDBGUARD_PARA1}";}
        if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]="{UFDBGUARD_PARA2}";}
        if(	$GLOBALS["UfdbGuardHTTP"]["FontColor"]==null){$GLOBALS["UfdbGuardHTTP"]["FontColor"]="#FFFFFF";}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]="#0300AC";}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]="#625FFD";}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]="#0300AC";}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]="#625FFD";}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]="#8c1919";}
        if($GLOBALS["UfdbGuardHTTP"]["Family"]==null){$GLOBALS["UfdbGuardHTTP"]["Family"]="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
        if($GLOBALS["UfdbGuardHTTP"]["FontColor"]==null){$GLOBALS["UfdbGuardHTTP"]["FontColor"]="#FFFFFF";}
        if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]="#8c1919";}

        if($GLOBALS["VERBOSE"]){echo "<hr><strong style='color:yellow'>CORP_LICENSE={$GLOBALS["CORP_LICENSE"]}</strong><hr>";}
        if(!$GLOBALS["CORP_LICENSE"]){
            $GLOBALS["UfdbGuardHTTP"]["FOOTER"]="
   			<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
   			<tr><td colspan=2>&nbsp;</td></tr>
   			<tr>
   			<td style='width:64px'><img src='img/logo-artica-64.png' alt='logo'></td>
   			<td style='font-size:14px;padding-left:10px;width:99%'>
   			You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
   					<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
   					</td>
   					</tr>
   					</table>
   					</div>";
        }

    }
    public function BuildTemplatesConfig($ARRAY=array()){
        if(!isset($GLOBALS["CORP_LICENSE"])){
            if(class_exists("usersMenus")){$users=new usersMenus();$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;}
        }
        if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");}

        if($GLOBALS["VERBOSE"]){echo "<p style='color:yellow'>BuildTemplatesConfig ARRAY=".count($ARRAY)." elements\n<br></p>";}

        $this->BuildTemplatesConfig_defaults();
        return $GLOBALS["UfdbGuardHTTP"];
    }

    private function parseHostsFile() {
        $hostsFile = '/etc/hosts';
        $entries = [];
        $lines = file($hostsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments
            if (strpos($line, '#') === 0) {
                continue;
            }
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 2) {
                $ip = array_shift($parts);
                foreach ($parts as $host) {
                    $entries[$host] = $ip;
                }
            }
        }
        return $entries;
    }

    public function gethostbyname($hostname):string{
        if(isset($GLOBALS["gethostbyname"][$hostname])){
            return $GLOBALS["gethostbyname"][$hostname];
        }
        $gethostbyname=urlencode($hostname);
        $json=json_decode($this->REST_API("/system/gethostbyname/$gethostbyname"));
        if($json->Status){
            $GLOBALS["gethostbyname"][$hostname]=$json->ipaddr;
            return $json->ipaddr;
        }

        if(!function_exists("dns_get_record")) {
            VERBOSE("->gethostbyname($hostname)",__LINE__);

            return gethostbyname($hostname);
        }
        $LighttpdArticaDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaDNS");

        $hosts=$this->parseHostsFile();
        if(isset($hosts[$hostname])) {
            $GLOBALS["gethostbyname"][$hostname]=$hosts[$hostname];
            return $hosts[$hostname];
        }
        if(strlen($LighttpdArticaDNS)>3){
            if(!class_exists("Net_DNS2_Resolver")){
                writelogs("Net_DNS2_Resolver doesn't exists",__FUNCTION__,__FILE__,__LINE__);
            }
            if(class_exists("Net_DNS2_Resolver")) {
                try {
                    $resolver = new Net_DNS2_Resolver(array(
                        'nameservers' => array($LighttpdArticaDNS), // Specify your DNS server here
                        'timeout' => 1,
                    ));
                    $result = $resolver->query($hostname, 'A');
                    if(count($result->answer)==0){
                        $GLOBALS["gethostbyname"][$hostname]=$hostname;
                        return $hostname;
                    }
                    foreach($result->answer as $record){
                        if($record->type=="CNAME"){return $hostname;}
                        if(preg_match("#^[0-9\.]+$#",$record->address)){
                            $GLOBALS["gethostbyname"][$hostname]=$record->address;
                            return $record->address;
                        }
                    }
                } catch (Exception $e) {
                    if(function_exists("VERBOSE")) {
                        VERBOSE($e->getMessage(), __LINE__);
                    }
                    writelogs($e->getMessage(),__FUNCTION__,__FILE__,__LINE__);
                }
                $GLOBALS["gethostbyname"][$hostname]=$hostname;
                return $hostname;
            }

        }
        stream_context_set_default(['socket' => [ 'timeout' => 1, ],]);
        $records = @dns_get_record($hostname, DNS_A);


        if (!empty($records)) {
            foreach ($records as $record) {
                VERBOSE("dns_get_record $hostname {$record['ip']}",__LINE__);
                if(!preg_match("#^127\.#",$record['ip'])){
                    $GLOBALS["gethostbyname"][$hostname]=$record['ip'];
                    return $record['ip'];
                }

            }
            $GLOBALS["gethostbyname"][$hostname]=$records[0]['ip'];
            return  $records[0]['ip'];
            
        }else{
            VERBOSE("dns_get_record EMPTY",__LINE__);
        }
        $GLOBALS["gethostbyname"][$hostname]=$hostname;
        if(is_null($hostname)){
            return "";
        }
        return $hostname;

    }


    function GET_INFO($key):string{
        $key=trim($key);
        $value="";
        if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
        if(!isset($GLOBALS["posix_getuid"])) {
            if (function_exists("posix_getuid")) {
                $GLOBALS["posix_getuid"] = posix_getuid();
            }
        }


        if($GLOBALS["DISABLE_MEMCACHE_SETTINGS"]==1){
            list($status,$value)=$this->GET_INFO_FILE($key);
            if(!$status){
                list($status,$value)=$this->GET_INFO_REST($key);
                if($status){
                    if($value=="!nil"){return "";}
                    return $value;
                }
            }else{
                if($value=="!nil"){return "";}
                return $value;
            }
        }

        if(!class_exists("lib_memcached")){
            include_once(dirname(__FILE__)."/class.memcached.inc");
        }
        $memcached=new lib_memcached();
        $X=trim($memcached->getSetting($key));

        if($memcached->ok){
            if($memcached->MemCachedFound) {
                if (strlen($X) > 0) {
                    if($X=="!nil"){
                        return "";
                    }
                    return $X;
                }
            }
        }

        list($status,$value)=$this->GET_INFO_FILE($key);

        if($status){
            if(strlen((string) $value)>0) {
                $memcached->SetSetting($key, $value);
                if($value=="!nil"){return "";}
                return $value;
            }
            return "";
        }

        list($status,$value)=$this->GET_INFO_REST($key);
        if($status){
            if (strlen((string)$value) > 0) {
                if($value=="!nil"){return "";}
                return $value;
            }
            return "";
        }
        return "";

    }
    private function GET_INFO_REST($key):array{
        $json=json_decode($this->REST_API("/getinfo/$key"));
        if(!$json->Status) {
            writelogs("[$key]: Status failed $json->Error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            return array(false,"");
        }
        if(!property_exists($json, "Data")) {
            writelogs("[$key]: Data failed $json->Error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            return array(false,"");
        }
        $value = trim($json->Data);
        if(property_exists($json, "Encoded")) {
            if($json->Encoded) {
                return array(true,trim(base64_decode($value)));
            }
        }
        return array(true,trim($value));
    }
    function GET_INFO_FILE($key):array{

        $FileKeyPath="/etc/artica-postfix/settings/Daemons/$key";
        if(!is_file($FileKeyPath)){return array(true,"");}

        if(!is_readable($FileKeyPath)) {
            return array(false,"");
        }

        $fp = fopen($FileKeyPath, "rb");//open file in read mode
        if(!$fp){
            $this->ToSyslog("Fatal!! $FileKeyPath fopen failed");
            return array(false,"");
        }

        $str = stream_get_contents($fp);
        fclose($fp);
        return array(true,trim((string) $str));

    }
    public function TheShieldEnabled():bool{
        $o_Shield_Server_Enable=intval($this->GET_INFO("Go_Shield_Server_Enable"));
        if($o_Shield_Server_Enable==0){return false;}
        $KSRNEnable=intval($this->GET_INFO("KSRNEnable"));
        if($KSRNEnable==0){return false;}
        if($this->CORP_GOLD()){return true;}
        $Val            = $this->GET_INFO("kInfos");
        $Val            = base64_decode($Val);
        $kInfos         = unserialize($Val);

        if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}
        if($kInfos["enable"]==0){if($kInfos["status"]=="{license_active}"){return true;}}

        return false;

    }
    function SQUID_IS_EXTERNAL_LDAP(){
        if(isset($GLOBALS["SQUID_IS_EXTERNAL_LDAP"])){return $GLOBALS["SQUID_IS_EXTERNAL_LDAP"];}
        $LDAP_EXTERNAL_AUTH=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/SquidExternLDAPAUTH'));
        if($LDAP_EXTERNAL_AUTH==1){
            $GLOBALS["SQUID_IS_EXTERNAL_LDAP"]=true;
        }else{
            $GLOBALS["SQUID_IS_EXTERNAL_LDAP"]=false;
        }
        return $GLOBALS["SQUID_IS_EXTERNAL_LDAP"];

    }
    private function SET_INFO_ROOT($key,$value):bool{
        if(!function_exists("posix_getuid")){
            return false;
        }
        if(posix_getuid()>0){
            return false;
        }
        return $this->SET_INFO_FILE($key,$value);
    }
    private function SET_INFO_MEMCACHED($key,$value):bool{

        $ClassMem=dirname(__FILE__) . "/class.memcached.inc";
        if (!class_exists("lib_memcached")) {include_once($ClassMem);}
        $mem = new lib_memcached();
        if(!$mem->SetSetting($key, $value)){
            if(function_exists("VERBOSE")){VERBOSE("$key: Failed -> Memcache...".$mem->mysql_error,__LINE__);}
            $this->ToSyslog("$key: Failed -> Memcache...".$mem->mysql_error);
            if(!is_null($mem->mysql_error)) {
                if (preg_match("#MEMCACHED_SERVER_TEMPORARILY_DISABLED#", $mem->mysql_error)) {
                    if (!isset($GLOBALS["MEMCACHED_RESTART"])) {
                        $this->REST_API("/memcached/restart");
                        $GLOBALS["MEMCACHED_RESTART"] = true;
                        return false;
                    }
                }
            }
            return false;
        }
        return true;

    }
    private function SET_INFO_REST($key,$value):bool{
        $TheValue=strval($value);
        if(strlen($TheValue)==0){
            $TheValue="!nil";
        }
        $key=str_replace("SET:SET:","",$key);
        $key=str_replace("SET:","",$key);
        $KeyEnc = urlencode(base64_encode((string)$key));
        $DataEnc = urlencode(base64_encode($TheValue));
        $data = json_decode($this->REST_API("/setinfo/$KeyEnc/$DataEnc"));
        if (!$data->Status) {
            writelogs("Error /setinfo/$KeyEnc/$DataEnc via API $data->Error", __FUNCTION__, __FILE__, __LINE__);
            return false;
        }
        return true;
    }
    private function SET_INFO_REST2($key,$value):bool{
        $KeyEnc = urlencode(base64_encode((string)$key));
        $path=PROGRESS_DIR;
        $tfile="$path/REST_$key";
        if(!$this->file_put_contents($tfile,$value)){
            return $this->SET_INFO_REST($key,$value);
        }
        $data = json_decode($this->REST_API("/setinfo2/$KeyEnc"));
        if (!$data->Status) {
            return $this->SET_INFO_REST($key,$value);
        }
        return true;
    }
    private function SET_INFO_FILE($key,$value):bool{
        $fname="/etc/artica-postfix/settings/Daemons/$key";

        if(is_file($fname)) {
            if (!is_writable($fname)) {
                return false;
            }
        }

        if(!$this->file_put_contents($fname,$value)) {
            if (function_exists("VERBOSE")) {
                VERBOSE("$fname FAILED", __LINE__);
            }
            return false;
        }
        @chmod($fname, 0755);
        @chown($fname,"www-data");
        @chgrp($fname,"www-data");
        return true;
    }
    function SET_INFO($key,$value):bool{

        if(!$this->SET_INFO_MEMCACHED($key,$value)){
            writelogs("Error SET_INFO_MEMCACHED($key)", __FUNCTION__, __FILE__, __LINE__);
        }

        $keyenc=urlencode($key);
        $this->REST_API("/flushinfo/$keyenc");

        if(!$this->SET_INFO_ROOT($key,$value)) {
            if(!$this->SET_INFO_FILE($key,$value)) {
                if(!$this->SET_INFO_REST2($key,$value)) {
                    return false;
                }
                return true;
            }
            return true;
        }
        return true;
    }
    function file_put_contents($filename, $data):bool {
        $datasize=strlen((string) $data);
        if(is_file($filename)){
            if(!is_writable($filename)){
                return false;
            }
        }else{
            if(!@touch($filename)){
                return false;
            }
        }

        $f = @fopen($filename, 'w');
        if (!$f) {
            return false;
        }
        if(!fwrite($f,(string) $data)){
            fclose($f);
            return false;
        }
        fclose($f);
        if($datasize>0) {
            $size = @filesize($filename);
            if ($size == 0) {
                $this->ToSyslog("Fatal: sock->file_put_contents $filename 0 bytes (L.".__LINE__.")");
                return false;
            }
        }
        return true;
    }
    function MYSQL_INFO($key){
        if(isset($GLOBALS["MYSQL_INFO"])){
            if(isset($GLOBALS["MYSQL_INFO"][$key])){
                if($GLOBALS["MYSQL_INFO"][$key]<>null){
                    return $GLOBALS["MYSQL_INFO"][$key];
                }
            }
        }
        $GLOBALS["MYSQL_INFO"][$key]=$this->GET_MYSQL_ROOT($key);
        return $GLOBALS["MYSQL_INFO"][$key];

    }
    function DeleteCache(){
        unset($GLOBALS["GET_INFO"]);
        unset($GLOBALS["MYSQL_INFO"]);
        unset($_SESSION["APC"]);

        if(!is_array($_SESSION)){return null;}
        foreach ($_SESSION as $num=>$val){
            if(preg_match('#CACHEINFOS_.+#',$num)){unset($_SESSION[$num]);}
            if(preg_match("#^cachepage_(.+)#",$num)){unset($_SESSION[$num]);}
        }

    }
    function GET_PERFS($key){
        $value=$this->APC_GET("PERFORMANCES_{$key}");
        if($value==null){
            if(!class_exists("Bs_IniHandler")){include_once(dirname(__FILE__)."/class.ini.inc");}
            $ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
            $value=$ini->get("PERFORMANCES",$key);

        }
        return $value;

    }
    function GET_NOTIFS($key){
        $value=$this->APC_GET("NOTIFS_{$key}");
        if($value==null){
            if(!class_exists("Bs_IniHandler")){include_once(dirname(__FILE__)."/class.ini.inc");}
            $ini=new Bs_IniHandler("/etc/artica-postfix/smtpnotif.conf");
            $value=$ini->get("SMTP",$key);

        }
        return $value;

    }
    private function stream_framework($uri,$called=null){
        $results=null;
        $hollodotme=false;
        $phversion=phpversion();
        $phpver=explode(".",phpversion());
        $MAJOR=$phpver[0];
        $MINOR=$phpver[1];
        if($MAJOR>6){
            if($MINOR>1){
                $hollodotme=true;
            }
        }
        if($GLOBALS["AS_ROOT"]){
            if(function_exists("writelogs")){writelogs("As root -> \"stream_framework($uri)\" $called",__CLASS__ . "=>" . __FUNCTION__,__FILE__);}
        }
        if($GLOBALS["VERBOSE"]){$uri="$uri&verbose=yes";}
        if($hollodotme) {
            include_once(dirname(__FILE__) . "/class.framework.inc");
            $frame = new fcgi_framework($uri);
            $results = $frame->Get();
            if (!$frame->ok) {
                if (function_exists("VERBOSE")) {
                    VERBOSE("$uri failed $frame->IOERROR", __LINE__);
                }
            }
            return $results;
        }

        if(function_exists("writelogs")){writelogs("ERROR: $uri phpv$MAJOR.$MINOR - $phversion not compatible",
            __CLASS__ . "=>" . __FUNCTION__,__FILE__);}
        return "";
    }
    private function xfile_time_min($path){
        if(!is_dir($path)){
            if(!is_file($path)){return 100000;}
        }
        $last_modified = filemtime($path);
        $data1 = $last_modified;
        $data2 = time();
        $difference = ($data2 - $data1);
        return round($difference/60);
    }
    function GET_MYSQL_ROOT($key){
        if(isset($GLOBALS["MYSQL_INFO_ROOT"][$key])){
            if($GLOBALS["MYSQL_INFO_ROOT"][$key]<>null){return $GLOBALS["MYSQL_INFO_ROOT"][$key];}
        }
        if($key=='server'){$key='mysql_server';}
        if($key=='root'){ $key='database_admin';}
        if($key=='password'){ $key='database_password';}
        if(is_file('/etc/artica-postfix/settings/Mysql/'.$key)){
            $datas=trim(@file_get_contents('/etc/artica-postfix/settings/Mysql/'.$key));
        }else{
            $ini=new Bs_IniHandler("/etc/artica-postfix/artica-mysql.conf");
            $datas=trim($ini->get("MYSQL",$key));
        }

        if ($key=='mysql_server'){if($datas==null){$datas="127.0.0.1";}}
        if ($key=='port'){if($datas==null){$datas="3306";}}
        if ($key=='database_admin'){if($datas==null){$datas="root";}}
        $GLOBALS["MYSQL_INFO_ROOT"][$key]=$datas;
        return $datas;
    }
    private function GetUniqueID():string{
        if(isset($GLOBALS["GetUniqueID"])){return $GLOBALS["GetUniqueID"];}
        $CACHE_KEY=md5("cmd.php?system-unique-id=yes");
        if(!$GLOBALS["AS_ROOT"]){
            if(isset($_SESSION["system-unique-id"])){return $_SESSION["system-unique-id"];}
        }
        $cachedatas=$this->MEMCACHE_GET($CACHE_KEY);
        if($cachedatas<>null){
            if(!$GLOBALS["AS_ROOT"]){$_SESSION["system-unique-id"]=$cachedatas;}
            return $cachedatas;
        }

        if($GLOBALS["AS_ROOT"]){
            if(!isset($GLOBALS["GetUniqueID"])){
                $unix=new unix();
                $GLOBALS["GetUniqueID"]=$unix->GetUniqueID();
            }

            return $GLOBALS["GetUniqueID"];
        }

        $data=trim($this->GET_INFO("SYSTEMID"));
        if($data<>null){
            if(!$GLOBALS["AS_ROOT"]){$_SESSION["system-unique-id"]=$data;}
            return $data;
        }

        $data=base64_decode($this->stream_framework("cmd.php?system-unique-id=yes"));
        if($data<>null){
            if(!$GLOBALS["AS_ROOT"]){$_SESSION["system-unique-id"]=$data;}
            return $data;
        }

        return "";
    }
    function IsGoldKey($key){
        $A=$this->unserializeb64("YTo2MDU6e3M6Mjc6IjJPWlRBWi1INU9HRzItWEQyRFJBLVlNN0lOWSI7YjoxO3M6Mjc6IldVRkhUNi1aT1VYTDktU00xWFFSLU9RTUVVQSI7YjoxO3M6Mjc6IktBRVFFMi1ZRUROUzEtSU5TSU9YLUJYNVdLNCI7YjoxO3M6Mjc6IlBQUEFUSi0ySDlWUkEtQTFMVktGLTlKTVZNMiI7YjoxO3M6Mjc6Ik9PVUk5TS1DN0RFUEEtSEdDRzFELUw2NEhKSSI7YjoxO3M6Mjc6IkNFUExGQi1GTjVMMTMtWFFVM1NBLVVWTEpLSiI7YjoxO3M6Mjc6Ik41NVlIUi1MQUYwQ1QtM0lWSlY5LTM5WllJQyI7YjoxO3M6Mjc6IkIxSklPSy1HWFNMUVMtRUFaMVhHLUxZOFNFUyI7YjoxO3M6Mjc6Ik9IVDZQVS00UElFUFgtOUZDTkJJLTlTREVWRiI7YjoxO3M6Mjc6Ikk5WEE2RC0yTk5BRjItNk1VQ0pPLTU5UFA4TCI7YjoxO3M6Mjc6IkU0TENXWi1WQlRKUVotTkFLWE5TLVNRT1JQMiI7YjoxO3M6Mjc6IlBaWE5LVS1VMkREQ1ktTUZPM0ZVLVFSRzhHTiI7YjoxO3M6Mjc6IlFETDVPRS0yMEhVOVctSlkyV05GLVpFR0JGRiI7YjoxO3M6Mjc6IlRBUk9EQS1TSkdRM0stR1lNRjBULThaSTlPVyI7YjoxO3M6Mjc6IlFBMUJBSy1YRFNWWFItWVUyRDdCLUNYNDdDWSI7YjoxO3M6Mjc6IkUwWlRZTi1PNlJSRkEtVDlNVTlaLUdaOFFBRCI7YjoxO3M6Mjc6IjhUM09OMS0xQTU5Qk0tR1dZRThULVRGR0ZXUyI7YjoxO3M6Mjc6IkZQWFZEMy1ORkMwR1YtVlFJVko5LVlJSFVMSyI7YjoxO3M6Mjc6IkJWUlRTSC1CSVdMVEUtQlJCMFhDLVJIV0RGQiI7YjoxO3M6Mjc6IkwxVlpJMC1VSlRTUU8tT1pFTkFHLVRVUVdPQiI7YjoxO3M6Mjc6IkhaVVFQRS1LV0VPRFctUUlDRE5SLUREV0pYNiI7YjoxO3M6Mjc6IklRRjUyRS1aTUZVOEUtMFdHQzdLLURRMktBQSI7YjoxO3M6Mjc6IjhEU1ZQWi03WTg2RUUtVUgxQTlULTVKSUhLQyI7YjoxO3M6Mjc6IlFRRElLUC1EOE5VSkYtOURGRFpaLUZJUk9QTyI7YjoxO3M6Mjc6IlpaUjg4OC1VRFdHVUgtRDhGTkxaLVNIT0ZHMyI7YjoxO3M6Mjc6IklEUDdTTS1INkJTUEotRlVSUUNMLUhBRjlNNCI7YjoxO3M6Mjc6IlJNR1VCVi1ESlJCTlMtWE1FVDlQLU5ISUlDUyI7YjoxO3M6Mjc6IlZOUFlOVS1RN1ZFWkItME5VOFBULUNaWEJEUCI7YjoxO3M6Mjc6IlBGREk2Uy1PQUVOV1UtNTFaNDFOLTI1TEk5WSI7YjoxO3M6Mjc6IjlLRUlCSC1FUkxIWUQtRkdDWk9VLVRVWEdWWSI7YjoxO3M6Mjc6IjNEOE5INy1IRzNMREktQkdSQU0yLTlGNUw1TiI7YjoxO3M6Mjc6Ikg4SVNVUS04T0dZTjktSUZPVU5OLVpOTVVPVSI7YjoxO3M6Mjc6IlpIVUhIUi00S05MNVotM0lZQVAwLVNCOVJENSI7YjoxO3M6Mjc6IlNWNVNQUi1JOU5VVFAtTkFMRE5JLUJOMkpVTiI7YjoxO3M6Mjc6IktOVU5HNC1OVktXQ04tWVI0WEk5LVZGRjYwRiI7YjoxO3M6Mjc6IllMTEtERi1KT0VYSTAtSUFTQTVaLVk1Q1BFRyI7YjoxO3M6Mjc6IklVWkhCRS1FUUZPUkEtT0xaUUI2LTFCQjNINCI7YjoxO3M6Mjc6Ik4ySzBaTi02SzhLSjAtOUpCRkZWLUY5VVNEVyI7YjoxO3M6Mjc6IlhGOFpROC1PRlpXUVgtV0xRQUZYLVNUVVNXTiI7YjoxO3M6Mjc6IkpTUlo1Vi1FRVBYU1gtMlNIRUhHLU5DOUlITyI7YjoxO3M6Mjc6IkVaUktQVS1FT1FVS0gtU1BTWEIxLVdTS1BQQSI7YjoxO3M6Mjc6IkZQRkUyQS02OUtGNkwtUEdOTFowLUlTMFlJQyI7YjoxO3M6Mjc6IjdCTU5UWC1FRUpWQUwtWUIzM0pLLUtOQlpGMSI7YjoxO3M6Mjc6IkJPOU5JSy1QUUJCNFotRkpLTVE0LVhTSzhERCI7YjoxO3M6Mjc6Ilg2UzZMRC01U1JYWlctUUhBNVFELVNCSDhBWSI7YjoxO3M6Mjc6IkdBVEE0Ui1SWDBJSkItRDFQV0ZFLTJUUDNVUCI7YjoxO3M6Mjc6IkJLMkdaTy1FQ0tXQU8tSFhBWU9RLTZYWUk3NiI7YjoxO3M6Mjc6IlZOVzBJVy1CTUs5VE4tWTkzR0FKLVdQUk1NUyI7YjoxO3M6Mjc6IkxaR0dPMC1COURITVItTTBQUDNCLU1ZRVlIVCI7YjoxO3M6Mjc6IjJVTzVMVC0yNkhXS0stNDVMR0RILU9BVlVWUyI7YjoxO3M6Mjc6Ik81SlAxVS1BRzlNUTQtWjlWRjVCLU1GTEFDUiI7YjoxO3M6Mjc6IjZYRFlDVy1LNFBISUEtNEYwVjVCLThXTVpRUCI7YjoxO3M6Mjc6Ik5UMVFOTC03WlVFTDQtOFBYSVdXLU1EWFRFUiI7YjoxO3M6Mjc6IlFENzhCVS01UklaU04tMTVHTEVCLVZIQVk4SCI7YjoxO3M6Mjc6IlRVSk1LRS1ZQ0hFRUwtRUZMTk1LLVhSVExQVyI7YjoxO3M6Mjc6IldIU0sxTC00VlhSV0YtTElQQ1dNLUFQUlhGUSI7YjoxO3M6Mjc6IkZBM1hQMi1USzgyRFItUFpXWUlELTgwVlA5TSI7YjoxO3M6Mjc6IklDQjZXSC1EU1hNWVEtTzJIMUVaLTVKSFpTViI7YjoxO3M6Mjc6IkhYRDlGRS01WFVBRFItQzROSFRJLUZXTE5aNSI7YjoxO3M6Mjc6IklXRk5FRi1OQUcyUDUtVU0yWFdTLVBFN1RTSyI7YjoxO3M6Mjc6IjE3TFpaQi00NDVYSFMtUElMVlhTLVQ3Nk9CRCI7YjoxO3M6Mjc6Iko2V1JUVy1NWVJOOEgtWFBCVzZNLUdVQ09ZTiI7YjoxO3M6Mjc6IlZCRUtXRi1OM1NEWUEtTjJZMU45LUxVTElOSCI7YjoxO3M6Mjc6IkJQS0RPSC1PRktKOU4tWUpRSE9ZLU1XRVdQWiI7YjoxO3M6Mjc6Ik9CR1o5US1SWUZCUlgtV1FQWEVQLVowRkJGUSI7YjoxO3M6Mjc6IlVZOU9RRS1CTTA0Q0wtVUNYTEhQLTZYQUQ0TiI7YjoxO3M6Mjc6IkFXR1lOUy1VQ1A4SEItQVcxRTJQLU9CR1hMWCI7YjoxO3M6Mjc6IksxQ0U0UC1JUTJGWTEtR1ZIVUJBLTFLTktGNCI7YjoxO3M6Mjc6IkFMU1lIUy1PRjk5TDktR1JWMlYwLVNIMkdaWSI7YjoxO3M6Mjc6IkFZSkM5TC1DT0lKRlItUElFWUpJLVcxVUxFUiI7YjoxO3M6Mjc6IkhXV0hKMS1WREZIUkYtTTJRUU1XLTNUU1ZHVSI7YjoxO3M6Mjc6Ikw5UUhPVS01OTU1OUwtTVhaWVc0LVpLUEpaTiI7YjoxO3M6Mjc6IldXQVhaTS1KM0oyTVEtMlI3SENJLTBDWkpPSyI7YjoxO3M6Mjc6IkRHWU1DMC0wR0dVRkctN0RPQVE0LU1XQ0ZZQyI7YjoxO3M6Mjc6IjlZQUk0Ri1XSUIxMUYtUFBTRENDLVM4N0dEOCI7YjoxO3M6Mjc6IlRIVkZWTS1BS1JXTk4tQkpQME1WLVpQWUVTMCI7YjoxO3M6Mjc6Ik9YRUM1Vi01S1NNRFQtWUpUVTA5LUpRUTcyRiI7YjoxO3M6Mjc6IlU4VFFPVS1ZQjFZM0otVUxSVVNDLVhWSVpCWSI7YjoxO3M6Mjc6IkVRTk9UNC1KS0cySzctRFAzUE5ULTdMUU5STyI7YjoxO3M6Mjc6IlRUVVI2Si1LTzNENDQtSUlXQUE5LVVKS1RNTiI7YjoxO3M6Mjc6IkVBRERUSy1MN0paTlUtWUdKSk1aLThBUDFaTSI7YjoxO3M6Mjc6IlZBUlhSVi05SVNUUTUtQUc0S09CLVgyTkM0QyI7YjoxO3M6Mjc6IlRVREdRMC1XS09TMlMtTEVESFdRLVVOQVdDViI7YjoxO3M6Mjc6IlBKS0c0VC1aUERNSFctV0JDUk9FLUhJSzhWVSI7YjoxO3M6Mjc6IlkyRUhFSi1RQVFRUU0tV1MzQk0yLUxZSFBLSSI7YjoxO3M6Mjc6IkpMRlBaOC1WOUdETkwtVDZJMVg5LUZYWjNZMyI7YjoxO3M6Mjc6IlhHVkNVQy1FTjROSkctMFdaWFVJLVhGSEJCVyI7YjoxO3M6Mjc6IlBEUzFKVS1UTkNXUFAtUU5CQTVMLUI3QktKSSI7YjoxO3M6Mjc6IkFEUDZJRS1XTU5JWUMtTFZSTk5LLTVITVhXTyI7YjoxO3M6Mjc6IkJTT1lNUC1DVDBGWVctSTRNTDVaLVQyQlE4UiI7YjoxO3M6Mjc6IlNBUk9FWi1LSUJUUEctUU5TSTk0LUE4QUNFUiI7YjoxO3M6Mjc6IkhMT1lUUC04VVNVS0stWldZNk1LLUpKOE8yTiI7YjoxO3M6Mjc6Ik80VkFZVy01SlQwSEYtTExVWEhYLVlZQzdKQiI7YjoxO3M6Mjc6IkxQRjNQVy1PNVBCR1UtMFA1RUtOLVhTQ09PWSI7YjoxO3M6Mjc6IlI5UE40Qi1JWFc4RVMtOUJJT0xHLThURFA0UiI7YjoxO3M6Mjc6IjhGVU5DVi1YVlNPMVQtTDNWUUdELUFKUEtQUCI7YjoxO3M6Mjc6IlJFUTJOSi0wWlRXWkwtNk9FUTlJLUpUWUFQRSI7YjoxO3M6Mjc6IkhGU0lBQy1NVElZTFQtWUlGUUlELVVKS1hHMiI7YjoxO3M6Mjc6IkxZQjFXVC1WVzNMNVktUEJMUExJLUdIRUNaUyI7YjoxO3M6Mjc6Ik1JSkdCMS1MTlQyRDItUk0wS0RKLVRaRVFMNyI7YjoxO3M6Mjc6IkJUV0VPTi0zWTNESVctVEdDV1NYLUtPRUhSTCI7YjoxO3M6Mjc6IlRPUEJBSS1QQ0tUUEctN0pSSlVKLUVTQU5HMSI7YjoxO3M6Mjc6IkY0QTNCTS1KTkZPUVktVFhOTUtTLTFMVjlHTiI7YjoxO3M6Mjc6IlM2Uk1QRS1GQ0I2Tk0tUkVDSVoyLTJOSlBNRCI7YjoxO3M6Mjc6IldUSU5YUS1BMVFCS0QtSVVQQ0swLUhTS1dDVyI7YjoxO3M6Mjc6IjJYSUJHMy1TVEJRSjgtTzRVQllYLVlTQ1hHQyI7YjoxO3M6Mjc6Ik4wVVVUNC1DUFgyOUQtT0VNQ0pMLURJOFdPOCI7YjoxO3M6Mjc6IkM5SkJLVS1MSFpXQVQtV0Y0WVhWLTMzNlhNUSI7YjoxO3M6Mjc6IjAyTkFIUS1FWTA3V1YtSk9NVEhULUxKVk1FOSI7YjoxO3M6Mjc6IjBZMUZMMi1NSDZKRUMtSlpDR05ILVpST0VUTiI7YjoxO3M6Mjc6Ikk1Sk5YTi1UQk5ITkstR1JBVDk2LUpRTzZJMSI7YjoxO3M6Mjc6IjU4V0pNTi1US1NUQkotSkdMWkcwLUVPU0NDMyI7YjoxO3M6Mjc6IjhGT0RXTy1SRk5GWDEtSFNLRldYLUZVS05QUCI7YjoxO3M6Mjc6Ik5aQ1pFWi1PRlUzWTItVzYwN0hELVVaUFlGVCI7YjoxO3M6Mjc6IlRBQ0dPSS01VEJQM1ktM1pCUk9PLUdCT1lTUCI7YjoxO3M6Mjc6IlEyUVVSQi1KMERBWUEtMTNWU1lRLTBKMEpVWSI7YjoxO3M6Mjc6IkhYUkNBWS00Q0owVk0tQlJJWVpTLVVWR04wRSI7YjoxO3M6Mjc6IkE4T1ZHNi1QWVNNTEEtTEJSWFZNLUpISTVJOCI7YjoxO3M6Mjc6IkFZOThaRi1WQ1ZEVU8tVjdDREVPLVRTTVZRNyI7YjoxO3M6Mjc6IlJZSkgzWC1IUUMwTU4tWkkyNjBKLUNTSUY2TSI7YjoxO3M6Mjc6IkUxRkNOWC1OV0NSMUQtTFlVNVRGLU8xREJBWCI7YjoxO3M6Mjc6IjRBTVNRVC1YQlowOEYtWU45WkVELTM4UkhIUSI7YjoxO3M6Mjc6Ik9URUZCSy1SSlBLN0wtOEVPRFg0LTlCTVZaMSI7YjoxO3M6Mjc6IkQxOUhYWC1SUEFaWkEtV1dVRUFXLVBTTjhBVyI7YjoxO3M6Mjc6Ik9LT0xLQy05Q0hKTkgtTE1BSEtFLVVISlpTRiI7YjoxO3M6Mjc6IlZXWlpWQy1VS1RaRlAtRU83MFFFLVpHTThDNiI7YjoxO3M6Mjc6IjJBRk4xUy1IREkySU8tSjRGWVhNLVVaMkdMRyI7YjoxO3M6Mjc6IllYRUJFSi1OMEhKVUMtWkJPWllRLVNEOUFXVCI7YjoxO3M6Mjc6IlJTVFlBRS1SUEVJU1MtVE9aQlFPLVBaUFE2QiI7YjoxO3M6Mjc6IjhCQVhGUi1LSVNJQUYtNVFVN0RYLTZPUlBYNCI7YjoxO3M6Mjc6IllWT1hLWi1UQ0EwWFItV0NWUk5YLVVFSVdYWSI7YjoxO3M6Mjc6IjBPQkxSVC1PTExBWkotQTlPMldILVhMQklNTyI7YjoxO3M6Mjc6IkROS1pSQS04MDNLSk4tVVU3WTRYLTg2RUdPNiI7YjoxO3M6Mjc6IkVSUUhZRi1HQkdDTkUtSk5QTEtLLVdWUENCVCI7YjoxO3M6Mjc6IlRHQ0ROSS1aVk1TRDktVzlKQlg0LTYwMEJQTCI7YjoxO3M6Mjc6Ik5FVUxRQi1YUk1VRlAtWEhMRE1JLVlJMFpaSSI7YjoxO3M6Mjc6Ikw1VjcxRy0wSEpUWlMtWUxNOU9OLUFaSExNOSI7YjoxO3M6Mjc6IjVFQ0dNRS1ZTktUQ0ktSkxYR0VILU5WWFJJTSI7YjoxO3M6Mjc6Ik5RQVVUTC1PREtHVTQtRVdQSVcyLUNHRU9YUSI7YjoxO3M6Mjc6IlFOSkRHOC1JRVRTSkotWFUwT1Q3LUJSNVlaRCI7YjoxO3M6Mjc6IlNFRkFIQy1ZUU9EQjAtUVFORUdPLVBTSEdFUyI7YjoxO3M6Mjc6IkxKUjhTUi1CSk1OQUMtWE04V0wzLUNZQ0dGRSI7YjoxO3M6Mjc6IkdPN1JORC1HSkk4RkYtQ1UzMUJQLUpETzdZSiI7YjoxO3M6Mjc6IkxGSkNLQy1USkdKMjAtTDlOSTI3LU9ONFVCSyI7YjoxO3M6Mjc6IkZCV0VCVS1CQ05NUEItQ1RQR0NULVNUUkdPViI7YjoxO3M6Mjc6IkFZVU5TUy1GVFpPQjUtWFJGSzQxLVBGR0ZIVCI7YjoxO3M6Mjc6Ik5MQU40Sy1KWEpEUU4tSU9XMk5ELVExN1JTUCI7YjoxO3M6Mjc6IldCVFhFTC1VTVhMUlotSENLRTdZLVhIMDVDRSI7YjoxO3M6Mjc6IjlIQ1NOMy1GU1M0UVctRElVNUFMLUEzT0dZUiI7YjoxO3M6Mjc6IkRBWEFSWi1UNEJaVVYtUEFNUkpJLVFKUDJPNyI7YjoxO3M6Mjc6IkpYMkNDWS1MNlZGWEEtQUJBS0hQLVpOMVBKQiI7YjoxO3M6Mjc6IktEWVVDTC1WUTJLWjgtM1FQWkhWLUFHV1NaMCI7YjoxO3M6Mjc6IlFYMkxOUi1RRVBHSFgtQ0lIUEdSLVg4WUNVSyI7YjoxO3M6Mjc6IlNLV1Q2TS1JR0FGNUEtTVpFWk1VLVZQU1dJTSI7YjoxO3M6Mjc6IklSQ1hUUC1TWDFCMUotRlhEQVk1LUZQUFJFTSI7YjoxO3M6Mjc6IjRQQ0hMRi1VQk4zSFgtTVNPSlMxLUM4WUNJRCI7YjoxO3M6Mjc6IlJITUhOSC1NRFdZWTMtOVBHSURGLVRLWlNTNyI7YjoxO3M6Mjc6IkxaTlVPOC1WM0lWMTgtUjRURk5GLVk4SlRNQSI7YjoxO3M6Mjc6IlBFUFZYNi1BVFozRUktUzgwNkNPLUpJRzJSSiI7YjoxO3M6Mjc6IkdTT0xLRy1aUFFLVVQtWlA3WllCLUhSUEtDMCI7YjoxO3M6Mjc6IlBTUkJRUS1ZRURZVUotSUdVWkZZLTBIR09VUCI7YjoxO3M6Mjc6IkVQRTlMWi1LSU1ENFotMkJOSFo1LUNEVVdXMSI7YjoxO3M6Mjc6IklaUEZTWS1FTVVDVE4tTzczS0JMLU9FSkFMTiI7YjoxO3M6Mjc6IlBSRUNCMC1CMFBXV0ctUllKUEJILTI1U1pPVSI7YjoxO3M6Mjc6IjVENlpTRy1HWlpTVlktR0ZVRFNNLU5VQjlTRSI7YjoxO3M6Mjc6IlNMVlRNTy1RQkFEMEwtVzJGWDNKLUdWSUZRTCI7YjoxO3M6Mjc6IktSQVlNUC1RUUJFMUMtQktIVVBZLUY3U0hVQSI7YjoxO3M6Mjc6IkxST0NHRC1SRVFLT08tNUozWkVaLU1WWUhEVSI7YjoxO3M6Mjc6IkNRQ0VTTi1STDJVQkotQ1I1U0VJLVdBUFVRWCI7YjoxO3M6Mjc6IjBDWldOSC1EQUJHV0ItRlFTUlAyLUs5UlNHSiI7YjoxO3M6Mjc6Ik1BRVFKRy1PRVBKODgtNUdQSVkyLUI5ODNaRiI7YjoxO3M6Mjc6IlRGVUZJNy1UUE9VNTAtNVNCSjNDLUJZRVlYSCI7YjoxO3M6Mjc6IkhIR1pZNi1DVElMNUstQk9OVkNILTk1WU5BVCI7YjoxO3M6Mjc6IjlFODJLRy04RTBaTUMtODNCV0RBLVY5TVhIQiI7YjoxO3M6Mjc6IkEyOVpCVy1USENDSDEtTFlQTVRJLU01RFBaUiI7YjoxO3M6Mjc6Ikg4SEdBWS1HVVQ3VlQtTE41NDlWLUpTTzVBSiI7YjoxO3M6Mjc6IjlPS0JGSC1KTkxRQ0wtTlVER1c5LUROV0s3VyI7YjoxO3M6Mjc6IllXR1VTVy1KR1hXQkItVDVNOTYxLUtPUlNINCI7YjoxO3M6Mjc6IkhVWUJINi1DUU1MQVMtTTdWTkpULUpVQ1QwMiI7YjoxO3M6Mjc6IkI0MElMUC1GSjVCVFctNUxIMThTLTgyVjFXTCI7YjoxO3M6Mjc6IjdXRjhQVC1URU9JT1ctSVc5NlVSLVI2R1BCNSI7YjoxO3M6Mjc6IkU1U0VEWi01TUNLN1AtS1JRT1hQLVBTQVZONiI7YjoxO3M6Mjc6Ilg2RU1JUy1TTThXSEwtSTJURE5RLVdNV1VPVCI7YjoxO3M6Mjc6Ik9UMzFDTi1BWUFISkYtRUcwUDZPLUJBOVNXWCI7YjoxO3M6Mjc6IloxQVdDQi0xUzZNQVYtQkVJS1ZHLUlETUNUUyI7YjoxO3M6Mjc6IkdNWlVaWC1XR1VJR1MtRlJPSDBLLTJRRzYwTyI7YjoxO3M6Mjc6IlVETVRORi1LUTNRVlgtRUJKNlFOLVhZNlA0RSI7YjoxO3M6Mjc6Ik5HUE1HNS00VFpaQ0UtRUlJMUVQLUZVNFlVQyI7YjoxO3M6Mjc6IjI4VUlaRC00SEdBTlctUFVTVUVRLUxRVFhSMCI7YjoxO3M6Mjc6IkJRV1lMVi1QSkJBNU0tSEdWTVZKLVZOTkdQTyI7YjoxO3M6Mjc6IkNaUklFTi01Vk1LRUQtNkxPTU01LVpPWUE4NiI7YjoxO3M6Mjc6IjNNVVhQMC1KR1pISVUtVEJMWENaLTdYSUJBVyI7YjoxO3M6Mjc6Ik02TElGRi1SWElGMVAtSFE2VVFCLTQ2STRDTSI7YjoxO3M6Mjc6IkNFWUpURS1ORkFWU1ctQUNQR1VJLVVTUlpTOSI7YjoxO3M6Mjc6IjdaT0pZQi1HOE5VNVAtRVlFNlVBLUFDRjJTRyI7YjoxO3M6Mjc6IlhSTEhSTS1PT01JUEYtVVQ4UDdLLVJFNkgzVCI7YjoxO3M6Mjc6IkpOS1NHQS1XQ0VJV00tR05JV1JNLVZMUDY4NSI7YjoxO3M6Mjc6IlgwV0dFVi1WSUNFS0UtTkRPQkpSLUxLUFFTTiI7YjoxO3M6Mjc6Ik5BQzFEOS1QQlJXTEctOTlZU1lCLVJVQ09XSSI7YjoxO3M6Mjc6IllXV1FGTi1RSlVMQlYtRlVUNlBQLUFYTlRFUyI7YjoxO3M6Mjc6IkhQVlVURC1XVTk3VEotTU1VUk1aLUZLOE1TVSI7YjoxO3M6Mjc6IkdQOFRSVC1aU1JENUUtVURBVVRMLVU4NlBZUiI7YjoxO3M6Mjc6IkdVWVdXRi0wRDE0WDktNUNCVVNaLVZHWVlOSSI7YjoxO3M6Mjc6Ik5MTUlUNC1ONlE3RTUtNkZLWTRFLThMWTFLMSI7YjoxO3M6Mjc6IllCSUVNUy1LSDNPUFUtVlFSUVJHLVlSOFY0RiI7YjoxO3M6Mjc6IlBDRkZYVi1SUUJXWTctODU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlIVjJRQS1BV0JXWTctMzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlNEOERIVy1RSEJXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNHUFdNSC1WUUJXWTctNzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRYUUpOWS1QNkJXWTctNzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IlJEUjhWTC01TUJXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IktZOUVDVy1ZN0JXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJTRDdNVS1MM0JXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6Iko0QUZLRC1HWkJXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ijc0UTdNSi1KVEJXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJVRENRQS1OWkJXWTctNjVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjdMNzJXSy1MR0JXWTctMjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJVTUE4WC1VWUJXWTctNzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdHVVUzOC1aM0JXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5RS0FVSC0yTEJXWTctNDU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdRNExVNC1LQkJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJKOTNBQy1VMzhXODctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IjhMQ1Q1Ni1IWEJXWTctMzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IlpaQ0ZOUC1QNkJXWTctNzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjkzVUY1OC1FV0JXWTctQjVZODY5LU5EVFRETiI7YjoxO3M6Mjc6IjlXSjgySy1VUUJXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZVRTU5NS00QTlXMjctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IjhHRlM1Qi04NTlXNjctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkJYWTJUTi0zTUJXWTctNTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZWTlFRSC1HSEJXWTctODU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjQ0VVM4RS1UUEJXWTctNDUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlE4UEZNSi0zMkJXWTctMjVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IlE2SE1NNS1XTEJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjhZWFZKNi02UEJXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5YVUFRSy1BVkJXWTctODUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlFEMlQ2US1GQUJXWTctMjU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZQWEdaVC1FRUJXWTctOTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IldVRFhMRC1SRkJXWTctODU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlpQQ001RC1DQUJXWTctMzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZEOFpQMy1DQ0JXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlM3TTJQTS0zNEJXWTctQjVZODM5LU5EVFRETiI7YjoxO3M6Mjc6IlJCM0czNy1ZVEJXWTctMzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IlRZVVg1Ti1CVEJXWTctOTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ijk5SzhKVS1EOEJXWTctQjVZODY5LU5EVFRETiI7YjoxO3M6Mjc6IllGMktBNC1LVkJXWTctMzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlI2R1dZQS1KU0JXWTctNzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkVQR1VKVi1DRUJXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IktWSllLQi1ZOUJXWTctNzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlpTNjQySy01V0JXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZSSERZTC1NM0JXWTctNTUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhYVjMyWC1IUUJXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjI5Q0gzVy1OREJXWTctQjVZOEI5LVlETlRUTiI7YjoxO3M6Mjc6IkZITjRLUC02SEJXWTctMjU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZQNVZGVy1aWDJXQTctWjU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRUNDRRSi1FMkJXWTctQTVaODI5LU5EVFRETiI7YjoxO3M6Mjc6Ilg0WE45Mi0zTEJXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRNRURUSi1TR0JXWTctNDU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkE4VzNYRi1VR0JXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhGNEY2VS1KN0JXWTctQTVaODQ5LU5EVFRETiI7YjoxO3M6Mjc6IjlXWEc0TS1OUkJXWTctNjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhXNkE3Vy0zVUJXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhDTUc3Qy1VSEJXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlMzWlhXVi1NVjJXQjctWTU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhQR1FQSi1BTUJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkozWlQ4Uy1HOUJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlpBQlBCRi1XSkJXWTctNTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkczTE5MUC1YRkJXWTctNTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkVONUdSTi1MWkJXWTctNjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJMSzc1Qi00V0JXWTctMjU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilk5QkhMSi1WSkJXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IlA2U04yQy1CWUJXWTctNDVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjNRNFZTRC1UOEJXWTctNDUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IllaV0RQVi04R0JXWTctOTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkdaRDNIMi1ESEJXWTctQjVZODY5LU5EVFRETiI7YjoxO3M6Mjc6IlNCU0o5WC1SWUJXWTctODU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5CNjJQVy1ZUkJXWTctNjVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjI5Q1JDRS1XV0JXWTctNDVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IlozSDdBOC0yNThXNzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IllaNEg3My0zOUJXWTctQjVZODI5LU5EVFRETiI7YjoxO3M6Mjc6IlpMVkVDOS02REJXWTctNDVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjhQVEQyNS1EOEJXWTctQTVaODM5LU5EVFRETiI7YjoxO3M6Mjc6IkQyVTVKVi0yTEJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IllKWE1FUy1QRkJXWTctOTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkNVVzlBTi1FV0JXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJNWkpUOC1UUkJXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IlRFRTQ2TS1NTUJXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZGWFBaVi1IOUJXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjNOOEhRRC1GSkJXWTctMjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlE3UkhUSi03SEJXWTctNDU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlVXSkc2WS1UVUJXWTctOTU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IldCWk5VUi1TQUJXWTctOTU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdZS1RCRC1ESEJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZBRlBIRC1RTEJXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlFGRVREOC1UUUJXWTctNjVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjJLTVZUNS1XV0JXWTctNzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkVUWkdRTi1MR0JXWTctODVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjQ3M0FVUi1LQkJXWTctQjVZODI5LU5EVFRETiI7YjoxO3M6Mjc6IjZFUzVINy1ZSkJXWTctMzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkJNMlozUy05UkJXWTctMzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZWVVhRWi1aNTJXQjctWTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjVVOEozQS0zSzlXODctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IjlOVU45Ny1BNUJXWTctQjVZODY5LU5EVFRETiI7YjoxO3M6Mjc6IlNITkhFTi01VkJXWTctNTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkNKWkxISy0zV0JXWTctNTU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkM4REUyUi02VkJXWTctQjVZODM5LU5EVFRETiI7YjoxO3M6Mjc6IkRWUTM5VS1UTEJXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IllFNEgzQi1TQ0JXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkpWUjJLQS1MMkJXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBYNUhHSi1EUEJXWTctNTUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZXTUhIRi1QS0JXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlQ0WFdUUi1SUDJXQTctWjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZRV1YyOC1ITUJXWTctNTUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkpXNVFHRS03UEJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhIUThYSi03R0JXWTctMzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilc3UDdFQi1DQ0JXWTctMjVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjRUVEJIVi05UUJXWTctNTU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IloyQUgyRy1CWEJXWTctMjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkFHM05HNS1RTEJXWTctMjVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkxHMjU4Ui1DUkJXWTctQjVZODg5LU5EVFRETiI7YjoxO3M6Mjc6IktIQUROQy1KM0JXWTctQjVZODc5LU5EVFRETiI7YjoxO3M6Mjc6IlBZQVAzNi1FV0JXWTctNDU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkFYUTNOOC1NTEJXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik41S1JEUi1CWkJXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjZKODZRTS00QUJXWTctQTVaOEI5LVlETlRUTiI7YjoxO3M6Mjc6IkdHTkVZWC0zWkJXWTctNzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilo3QTdFUS00VkJXWTctMzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilo4Q1BWVC1TQ0JXWTctODVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IlpCVDlXQi1ERkJXWTctNTU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlSMllLUi00WUJXWTctNTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRTV1YyVi05RkJXWTctNTUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlRCOVM4Ry1GMkJXWTctQjVZODg5LU5EVFRETiI7YjoxO3M6Mjc6IkRIODJQTC0zWUJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJXRjRWMi1IWUJXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZQU1E4Si1ONkJXWTctNDVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjVLNFNaRS1MS0JXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjVDTUtMUy0yREJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJTTlFQSC1MQ0JXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlKU1Q2QS1LNEJXWTctMjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkU0TVVUNy0zRUJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZLUDRRUi1QTUJXWTctNTU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNUVFFXRi00RkJXWTctNTUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlpIRFlOOS0zQUJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilg2WFUzRC1DVEJXWTctNTU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5HN0RZVy1KWEJXWTctNzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5NNTM1WS1LS0JXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlA0VUhSWS1FUUJXWTctNzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkxZUDJKVS1EUkJXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IksyVjVFVS03UkJXWTctMzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlgyWUdRQy1HSkJXWTctNTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlY2Mk04Ry1MNEJXWTctQjVZOEE5LVpETlRUTiI7YjoxO3M6Mjc6IjZLU05IWi1GVkJXWTctNzU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlRBR1NTNS1aN0JXWTctNTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlhCRldMVC1IWEJXWTctOTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkhaREVQUy1LQkJXWTctNjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlVIS0o5UC1TUUJXWTctNzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlFLRFlLSi1IV0JXWTctODVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6Iko1UlJZUy1aMkJXWTctNzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlQ3M1o3My1OR0JXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilk1NjRHSC04TkJXWTctQjVZODM5LU5EVFRETiI7YjoxO3M6Mjc6IkJBNUFFMy1ZSkJXWTctQTVaODY5LU5EVFRETiI7YjoxO3M6Mjc6IkpYM1dKWS02QkJXWTctNTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlVWNVdVQS1QWUJXWTctOTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZVRDI0Mi02SDhXOTctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkhBRURLNC0zRDlXNDctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IlcyUU5SUS1VOEJXWTctNzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkFRQks0OS1IV0JXWTctMjU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBEM0szRy1ZUEJXWTctMzU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5OOEFEUy1EWkJXWTctNTUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJITFFWSy1HU0JXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjdUUkpYWS1SWDJXQTctWjU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBESEFMUy1XVEJXWTctNzU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik01WENHUS01R0JXWTctMzU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNNRFBSSC0yWkJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlUyQzhMVi1BOUJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IllQTUZKUC02N0JXWTctNTUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjU1RTk3WC1HWUJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IldNRzVUSi02WkJXWTctNjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5LQjVKRy1DM0JXWTctQTVaODk5LU5EVFRETiI7YjoxO3M6Mjc6IlU0SlZVVy1aOUJXWTctOTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjNKVkxLTi1ZUUJXWTctNzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhEV0RTNC02SEJXWTctMzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkVHQlY2Ry1HRUJXWTctMjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkRSREdRTi1NMkJXWTctNDUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlXNTY2OS1KTEJXWTctQTVaODc5LU5EVFRETiI7YjoxO3M6Mjc6IjVQVEc1RS1WV0JXWTctNTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZUUzhQRy1ZQUJXWTctNjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJINkhMRS0zSEJXWTctQTVaODY5LU5EVFRETiI7YjoxO3M6Mjc6IkZXTE5VMi1YUUJXWTctODUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ilc2SEI0QS0yQjlXMzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkRZTFNONC1BVEJXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJCUjlYTC1BREJXWTctMzU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBIWVRBQi1RM0JXWTctNTU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ijk1VUQ0OS1WNEJXWTctQTVaODU5LU5EVFRETiI7YjoxO3M6Mjc6IlVZNjdXTS00U0JXWTctNjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6Iks5NEZLVi0yV0JXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5ORzhGVi1BQ0JXWTctMzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkNLUVJaRC1NRkJXWTctNzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkxEQVBONS1YWkJXWTctNjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlE5Qk5ZNC1FVkJXWTctNTUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhTNTRDWi0zTEJXWTctQjVZODY5LU5EVFRETiI7YjoxO3M6Mjc6IlA0WkxWNC1IOUJXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJGVkhMNi04QUJXWTctMzU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjQ5R0U1Qy1SOTlXNjctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6Ilo4RVNUVC03RkJXWTctNjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlNXVTZZQy02WkJXWTctODUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJBQUVCWi1TWUJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBFNUpOTi01RUJXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJEOFZRMy1ISkJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkdHMzhFNi1QOTlXNzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IlE4TkNFOC1ESkJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhISk1URS0zS0JXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkRKN05FSi1FRUJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlU1VloyOC00OEJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ijg3WVIyVy03QkJXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJIOVVBUy1OWkJXWTctNjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhIMzJWQi1URUJXWTctMjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5XUEpCVy1INkJXWTctNjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkE3WjlMNy1NWEJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkY4M0Y3VS1GNDlXNzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6Ik1FVlBVTC1QWjJXQTctWjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5LTVM3Ny1NREJXWTctNDUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNHS0dBUy1YU0JXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlA3RUZYOC1WVEJXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlFBV0E1NS1GVUJXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNXRzRGWi0zS0JXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZVM0c0US1OTUJXWTctNTU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlLMkcyQS1GNTdXOTctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkFNS0tZRy01RUJXWTctNDVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IlhESFU1Ti1SWEJXWTctODVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6Ilc5VlhUMy1aWDJXQTctWjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkYyVFg5UC1UVUJXWTctNzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZWVkM4Vy1QUEJXWTctNzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6Ik5QOEpWSC1BQUJXWTctNDUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkVLS0ZBNy0yUUJXWTctQjVZODQ5LU5EVFRETiI7YjoxO3M6Mjc6IkFDNUs2Ri1CQjlXQTctWjVOOFQ5LUREOVQ4TiI7YjoxO3M6Mjc6IjhVNVE0WC1EWUJXWTctNTUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlYWVRHWC1TSjJXQTctWjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjVSNDZETi1BNzlXNTctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IlBCOFlETS1VNEJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlpQOVhFNC02S0JXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlNZNUFCWS1WOEJXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6Ikc5TEpNNS0zMzlXODctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkxFSEpSWi1FMkJXWTctNDU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik1QVjU5Ni1DQkJXWTctMjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjlaVUtSVS0yS0JXWTctNzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6Ijg4SzM2Qy1CRjhXMzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IlVLS1BGOC1SNkJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjNQR040Ui1WTUJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjJEM0dXQi1YM0JXWTctQjVZODM5LU5EVFRETiI7YjoxO3M6Mjc6IkZRNVZBWS1CVUJXWTctNjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJGQ0dZOC1aWkJXWTctODUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkY3WkFYSC1KMkJXWTctMzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkVXSFUyMi1WTkJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjcyQjJIMi1XUkJXWTctQTVaOEE5LVpETlRUTiI7YjoxO3M6Mjc6IjJWU1M1Qi1ZV0JXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjZQR0VOSi1YOEJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZHM1lUQy1VWkJXWTctOTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkVaRExBUy1UVUJXWTctODVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IlRZUkFVTS1LSkJXWTctOTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkdDVTdQWS1XUEJXWTctODVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6Ilo2TFFVUi1GR0JXWTctNzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNNTlBUQi01OEJXWTctMjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRDWllXQS1SNUJXWTctNTU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJRTEg2UC1RR0JXWTctNDU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkY3ODdNUC1OUUJXWTctMzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjZWM0E0Ui04UkJXWTctQjVZODY5LU5EVFRETiI7YjoxO3M6Mjc6IkRNR0JZRy0zQUJXWTctMjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlM2REJOUi04Q0JXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRRR0Q3Ny03SDlXNzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IlFWUlQ0RC1YV0JXWTctOTU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IldGTjJNNC1BQUJXWTctQjVZODg5LU5EVFRETiI7YjoxO3M6Mjc6IkdTWEFVNy1YRUJXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkRHN1ZERy1BVkJXWTctMzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJLSk1UQi1TVEJXWTctODU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlpCMktONC1BS0JXWTctMjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkFCRjJTNy0zVEJXWTctQTVaODU5LU5EVFRETiI7YjoxO3M6Mjc6IkFGSFIyUC1HWUJXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJVNTJBSy1ER0JXWTctQTVaODc5LU5EVFRETiI7YjoxO3M6Mjc6IjlHTE5RWC1DVkJXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkE4WEFYNi00TEJXWTctMjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdaRkxBVy1WQ0JXWTctNjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdaQTVVVy0zVEJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkFOSENDOS1FR0JXWTctQjVZODM5LU5EVFRETiI7YjoxO3M6Mjc6IldUWEdNVS1MNUJXWTctODU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlVVjQ5Si1RMkJXWTctMzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IllKM1pTUy1QRUJXWTctODU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjY5VFFNUi1MNUJXWTctNDU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkdOM1paRi1GOUJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IlFVQktQTC1HVEJXWTctNzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IllOVkc5Ti1TRkJXWTctNzU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJFWU0zVC1RS0JXWTctNTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkY5SFlTWS1GS0JXWTctNzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkcySlFDVS01WUJXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRWVVUzOS1FUkJXWTctNDU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkdEREhURy1WTkJXWTctNTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhaRE5FNC1QUkJXWTctNTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJKOTU0Si1XNDlXNDctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IjVSQVpQWi1IV0JXWTctODU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ijk5SEJDVi1aQkJXWTctMzU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJINVBWVi1SUUJXWTctNjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlhQQlQ3Ry1RRUJXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjVYNFIyNC0yUUJXWTctQTVaODM5LU5EVFRETiI7YjoxO3M6Mjc6IjY1WVhCNi1WRUJXWTctNDVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IjVTWFVEVy1DS0JXWTctNzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkxROEhaWS1BS0JXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IllaOThCQi02WEJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlE5QkpaVC1WREJXWTctNzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjVTTkJWVS0yWUJXWTctNjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IllZQkY0Vi1ISkJXWTctNjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBKUkQ4NC1LQUJXWTctMjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlRZNEI4My1TQkJXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhGMlMzTS1OS0JXWTctMzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6Ikg4RjY4Qy1IQTlXMzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6Iks1N0I5Vy1ZNkJXWTctMjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkxKV1IzRi0yTUJXWTctNDVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IllFWk1IVC1RUDJXQTctWjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlU2N0JKOC1SREJXWTctMjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkhYUU1aOC1QSkJXWTctODU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdQVktBMi1YU0JXWTctNTU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBES0tOWi1RTkJXWTctODU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkpBN1c4Sy1VREJXWTctMzU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjI1R1E3Ni1TWEJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlM1NE5GSi1CMkJXWTctQTVaODc5LU5EVFRETiI7YjoxO3M6Mjc6IlI3U0FWNy1ZSEJXWTctNjVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IlNOWldKVy1STDJXQjctWTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlXRjhHQy1IRkJXWTctMjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJXWlY1VC1YTEJXWTctOTU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlhCMkdUOS1FNEJXWTctQjVZODg5LU5EVFRETiI7YjoxO3M6Mjc6IjgzRVdXRS1TNUJXWTctMzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhWWTVERS1STUJXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJMRFVSSy00WEJXWTctNTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6Ilo0RDdaWS02MkJXWTctMzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkdRS1dIUC1BUEJXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IllMNURWSC1ROUJXWTctNTU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJBUVMzWS03S0JXWTctMzVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkhOTkZUTC1WWkJXWTctOTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRVTkhFOC00RkJXWTctQjVZODU5LU5EVFRETiI7YjoxO3M6Mjc6IlFSS0tHUy02TkJXWTctNjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IllIRE5BNy1HOUJXWTctMjU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjJWTjQ1Vi02Q0JXWTctQjVZODQ5LU5EVFRETiI7YjoxO3M6Mjc6IjI2Qko1Mi1DUDhXMjctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkJSVTlTOS1NTkJXWTctNTU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkxQSEVaWC1OQ0JXWTctODVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6Ik1VWkEzUS1VU0JXWTctODU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRRU0hBUy04UUJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkhTNVE3Vy1ZVkJXWTctODVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkpQRFRaNC1DN0JXWTctNDU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkczV1pLMy1DUEJXWTctNDU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ikc2UTZVQi1NMkJXWTctQjVZODk5LU5EVFRETiI7YjoxO3M6Mjc6IkZFSzRENS1ONDlXODctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkRSOUdFQi1BWUJXWTctMzU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IktGOVdWTi1NU0JXWTctODVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkZOTDdWOS00TEJXWTctMzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IjI0SksyQy1ORzlXODctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IlRINVU5NC1NV0JXWTctNDU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZGUE5EUy1aU0JXWTctOTU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkE0QURYRy1LNUJXWTctQjVZOEI5LVlETlRUTiI7YjoxO3M6Mjc6IjJKSDRGNC1NNDhXNzctTjVUOEQ5LTlEOFQ1TiI7YjoxO3M6Mjc6IkFEUjM2TS1VWUJXWTctNDU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlMzNjlGTS03WEJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkUzWFdKRS1MVkJXWTctNjU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjVNTDJVQy1DWUJXWTctMzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlhTUEFGWC1DTUJXWTctNzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlRSSlVFTi1WSkJXWTctOTUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlE1WFc5OC1TNkJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZDTkYzUi1DUEJXWTctNDU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkZGQVBYVC1XQ0JXWTctNzVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkI0RUxLMi1VUkJXWTctMjU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IllBNlhTNy00M0JXWTctMjU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlZQNlk5Uy1MV0JXWTctODU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjY3TVBKWC1XNkJXWTctNTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6IkZOUVU3SC1TOEJXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjdOUVM1Si1XOEJXWTctNDU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNRMzlKNC1LVEJXWTctQjVZODM5LU5EVFRETiI7YjoxO3M6Mjc6IlkzUVBFQy1LS0JXWTctNTU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik5aNTk3Ry1HTEJXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNaRkM0TC1CR0JXWTctQjVZODc5LU5EVFRETiI7YjoxO3M6Mjc6Ik5XQTJBRS1ZTUJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjhCN1VBVS1MVkJXWTctNDU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlUyQkdTNi1URUJXWTctMzU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjlBTkxHOC04QUJXWTctQTVaODU5LU5EVFRETiI7YjoxO3M6Mjc6IjQyQ1lKTC02NkJXWTctQTVaODQ5LU5EVFRETiI7YjoxO3M6Mjc6IlRONFRZVS03TkJXWTctNzU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRTM0pZTi1SWUJXWTctNzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkRWM0JBUS1VOEJXWTctMzUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlJYUVNDOS00NkJXWTctNDU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6IllETVlWSi1GOUJXWTctNzU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRIV0IzUS1UMkJXWTctMjU0OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlBFRjg3RC1NSkJXWTctMjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkNaNDlXOC1ZVEJXWTctNjUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IlRCWllBSC1QQ0JXWTctNzUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IkJOSExHTS03WUJXWTctNTVCOFk5LU5EVFRETiI7YjoxO3M6Mjc6IkpBSFhQUi0yQ0JXWTctNDU1OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ik1DNjZRSi1ENUJXWTctQTVaODk5LU5EVFRETiI7YjoxO3M6Mjc6IkcyTks4NC1DUkJXWTctQjVZODI5LU5EVFRETiI7YjoxO3M6Mjc6IldHSERMVC1HWUJXWTctNzU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkwzQ1pHSi0yTEJXWTctMjU5OE45LVRERFQ5TiI7YjoxO3M6Mjc6IjRVTERUTS1KQ0JXWTctNTVBOFo5LU5EVFRETiI7YjoxO3M6Mjc6Ilc5TVIyTi0yUUJXWTctNDUzOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjNMVlk3Ri1LWkJXWTctNjU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlAyWFQ2OC1GVEJXWTctNDU3OE45LVRERFQ5TiI7YjoxO3M6Mjc6Ikg1NFQ2TC1WRkJXWTctMjU4OE45LVRERFQ5TiI7YjoxO3M6Mjc6IlNGUzNSSy0zV0JXWTctNTU2OE45LVRERFQ5TiI7YjoxO3M6Mjc6IkxZWEpRTi1ZQzJXQTctWjUyOE45LVRERFQ5TiI7YjoxO3M6Mjc6IjVaVVFLUi1EQ0JXWTctNjU4OE45LVRERFQ5TiI7YjoxO30=");
        if(!isset($A[$key])){return false;}
        return $A[$key];
    }
    function isFirehol($output=false){
        if(intval($this->GET_INFO("FireHolEnable"))==0){
            if($output){echo "FireHolEnable = 0\n";}
            return false;}
        return true;

    }
    public function go_exec_version():string{
        if(!function_exists("curl_init")){return "";}
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://127.0.0.1:3334/version");
        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=intval(curl_errno($cURLConnection));
        curl_close($cURLConnection);
        if($curl_error_num==7){
            $this->mysql_error="Error Failed to connect() to host";
            return "0.0.0";
        }
        if($curl_error_num==52){
            $this->mysql_error="Empty reply from server";
            return "0.0.0";
        }

        return $resp;

    }
    function jQueryToUse(){
       $jqueryToUse="jquery-3.6.0.min.js";
        return $jqueryToUse;
    }
    function _getFrameWorkGO($uri):string{

        if(!is_file("/usr/bin/php-cgi")){
            writelogs("Unable to found /usr/bin/php-cgi",__FUNCTION__,__FILE__,__LINE__);
            return "";
        }

        $uri="http://www.domain.com/$uri";
        $url=parse_url($uri);
        $path=$url["path"];
        if(!isset($url["query"])){$url["query"]=null;}
        $url["query"]=str_replace("&"," ",$url["query"]);
        $fullpath="/usr/share/artica-postfix/framework".$path;
        $cmdline="/usr/bin/php-cgi -f $fullpath {$url["query"]}";
        $results=$this->go_exec($cmdline);
        return @implode("\n",$results);


    }
    public function getGoFramework($cmd){
        $ROOT=ARTICA_ROOT;
        $php="/usr/bin/php";
        if(strpos(">",$cmd)>0) {
            $tmpfile = PROGRESS_DIR . "/" . md5($cmd) . ".sh";
            $f[] = "#!/bin/sh";
            $f[] = "$php $ROOT/$cmd";
            $f[] = "rm -f $tmpfile\n";
            @file_put_contents($tmpfile, @implode("\n", $f));
            chmod($tmpfile, 0755);
            $this->go_exec($tmpfile);
            return true;
        }
        $this->go_exec("$php $ROOT/$cmd");
        return true;
    }
    function getFrameWork($uri):string{
        $line=null;
        $CACHE_KEY=md5($uri);

        if($GLOBALS["AS_ROOT"]){
            if(!class_exists("unix")){
                include_once("/usr/share/artica-postfix/framework/class.unix.inc");
                include_once("/usr/share/artica-postfix/framework/frame.class.inc");
            }

            if($uri=="squid.php?IsIcapClient=yes"){
                $unix=new unix();
                if($unix->SQUID_ICAP_ENABLED()){ return "TRUE"; }
                $this->Runned=true;
                return "";
            }

            include_once(dirname(__FILE__)."/class.socketAsRoot.inc");
            $socketAsRoot=new SocketsAsRoot($uri);
            $Val=$socketAsRoot->SocketsAsRoot_perform();
            if($socketAsRoot->Runned){return $Val;}
        }

        if($uri=="cmd.php?ldap-restart=yes"){
            if($this->SquidPerformance>2){return "";}
            if($this->EnableIntelCeleron==1){return "";}
            $timeF=dirname(__FILE__)."logs/web/ldap-restart.time";
            if($this->xfile_time_min($timeF)<3){return "";}
            @unlink($timeF);
            $this->file_put_contents($timeF, time());
        }
        if($uri=="cmd.php?uri=artica_version"){
            if(isset($GLOBALS["artica_version"])){return $GLOBALS["artica_version"];}
            $GLOBALS["artica_version"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
            return $GLOBALS["artica_version"];
        }

        if($uri=="artica.php?SPVersion=yes"){
            $VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
            $SP=intval(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
            return $SP;
        }




        if($uri=="cmd.php?system-unique-id=yes"){
            $GetUniqueID=$this->GetUniqueID();
            if($GetUniqueID<>null){return base64_encode($GetUniqueID);}
        }

        if($uri=="system.php?MEM_TOTAL_INSTALLEE=yes"){
            $cachedatas=$this->MEMCACHE_GET("MEM_TOTAL_INSTALLEE");
            if($cachedatas<>null){return $cachedatas;}
        }


        if($uri=="cmd.php?uri=artica_version"){
            $cachedatas=$this->MEMCACHE_GET($CACHE_KEY);
            if($cachedatas<>null){return $cachedatas;}
            if($GLOBALS["AS_ROOT"]){return @file_get_contents("/usr/share/artica-postfix/VERSION");}

        }

        $PowerDNSEnableClusterMaster=intval($this->GET_INFO("PowerDNSEnableClusterMaster"));
        $CLUSTER=true;
        if(preg_match("#(GetMyHostId|cachelogs|-real=|setinfos|getinfos|full-hostname|fullversion=|[\?|-]status=)#",
            $uri)){$CLUSTER=false;}

        if(preg_match("#(CertificateConfigFile|disks-list|CPU-NUMBER|disks-inodes|disk-get-label|SessionPathInMemoryInfos|files-descriptors|TCP_NICS|extract-infos|walk-local|searchdebugs=)#",
            $uri)){$CLUSTER=false;}

        if(preg_match("#(recursor-infos|nicstatus|nic-infos|syslog=|create-cluster-master|smtp-transactions|history|ospfd\.php)#",
            $uri)){$CLUSTER=false;}



        $NOCOMMANDS["cmd.php?ifconfig-all=yes"]=true;
        $NOCOMMANDS["cmd.php?mempy=yes"]=true;
        $NOCOMMANDS["cmd.php?status-forced=yes"]=true;
        $NOCOMMANDS["cmd.php?squid-GetOrginalSquidConf"]=true;
        $NOCOMMANDS["cmd.php?LdapdbSize=yes"]=true;
        $NOCOMMANDS["squid.php?build-schedules=yes"]=true;
        $NOCOMMANDS["artica.php?SPVersion=yes"]=true;
        $NOCOMMANDS["postgres.php?PostGresSQLDatabaseDirectory=yes"]=true;
        $NOCOMMANDS["services.php?nsswitch=yes"]=true;
        $NOCOMMANDS["cmd.php?postfix-hash-aliases=yes"]=true;
        $NOCOMMANDS["monit.php?allstatus=yes"]=true;
        $NOCOMMANDS["cmd.php?resolv-conf"]=true;
        $NOCOMMANDS["squid2.php?sprivs=yes"]=true;
        $NOCOMMANDS["services.php?license-register=yes"]=true;
        $NOCOMMANDS["nginx.php?reverse-fs=yes"]=true;
        if(isset($NOCOMMANDS[trim($uri)])){$CLUSTER=false;}

        $NOCOMMANDX["system\.php\?install-artica-tgz=yes"]=true;
        $NOCOMMANDX["network\.php\?ifconfig-array="]=true;

        foreach ($NOCOMMANDX as $pattern=>$none){
            if(preg_match("#$pattern#",$uri)){$CLUSTER=false;break;}
        }



        if($CLUSTER) {
            writelogs("CLUSTER: [$uri]", __FUNCTION__, __FILE__);
            if ($PowerDNSEnableClusterMaster == 1) {
                $time = time();
                $CLUSTER_COMMANDS = $this->unserializeb64($this->GET_INFO("CLUSTER_COMMANDS"));
                if (count($CLUSTER_COMMANDS) > 300) {
                    $CLUSTER_COMMANDS = array();
                }

                $CLUSTER_COMMANDS[$time] = $uri;
                $this->SET_INFO("CLUSTER_COMMANDS",
                    base64_encode(serialize($CLUSTER_COMMANDS)));
            }
        }


        if(preg_match("#^API:(.+)#",$uri,$re)){
            $this->REST_API($re[1]);
            return "";
        }


        if(function_exists("VERBOSE")) {VERBOSE("stream_framework($uri)", __LINE__);}
        $hollodotme=false;
        $phpver=explode(".",phpversion());
        $MAJOR=$phpver[0];
        $MINOR=$phpver[1];
        if($MAJOR>6){
            if($MINOR>1){
                $hollodotme=true;
            }
        }
        if($GLOBALS["VERBOSE"]){echo "***********\nPHP VERSION ->$MAJOR/$MINOR\n***********\n";}
        if(!$hollodotme){
            $line=$this->_getFrameWorkGO($uri);
        }else{
            $line=$this->stream_framework($uri);
        }

        if($GLOBALS["VERBOSE"]){echo "***********\n$line\n***********\n";}
        if(trim($line)==null){return "";}
        if(trim($line)=="<articadatascgi></articadatascgi>"){return "";}

        if(preg_match('#<articadatascgi>(.*?)</articadatascgi>#s',$line,$regs)){
            $results=trim($regs[1]);
            if(function_exists("VERBOSE")) {VERBOSE("$uri:(1) = ". strlen($results), __LINE__);}
            return $results;
        }

        if(preg_match('#<articadatascgi>(.*?)</articadata#s',$line,$regs)){
            $results=trim($regs[1]);
            if(function_exists("VERBOSE")) {VERBOSE("$uri:(2) = ". strlen($results), __LINE__);}
            return $results;
        }

        if(preg_match("#500 - Internal Server Error#is", $line)){
            if($GLOBALS["AS_ROOT"]){
                $this->ToSyslog("Restarting framwork !!! 500 - Internal Server Error");
                writelogs("Restarting framework !!! 500 - Internal Server Error");
                shell_exec("/etc/init.d/php5-fpm restart >/dev/null 2>&1");
                shell_exec("/etc/init.d/artica-phpfpm restart");
                die("DIE " .__FILE__." Line: ".__LINE__);
            }
        }

        if(preg_match("#503 - Service Not Available#is", $line)){
            if($GLOBALS["AS_ROOT"]){
                writelogs("Restarting framework !!! 500 - Internal Server Error");
                $this->ToSyslog("Restarting framework !!! 500 - Internal Server Error");
                shell_exec("/etc/init.d/artica-phpfpm restart");
                die("DIE " .__FILE__." Line: ".__LINE__);
            }
        }

        if(preg_match('#<articadatascgi>(.+)</\s+articadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</articadat#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</articada\s+tascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</articad\s+atascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</artica\s+datascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</articada\s+scgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</artic\s+adatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</arti\s+cadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</art\s+icadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</ar\s+ticadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
        if(preg_match('#<articadatascgi>(.+)</a\s+rticadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}


        if(preg_match("#cmd\.php\?SaveConfigFile#", $uri)){return "";}

        return "";

    }
    private function ToSyslog($text){
        if(strlen($text)>750){$text=substr($text,0,749)."...";}
        $LOG_SEV=LOG_INFO;
        if(function_exists("openlog")){openlog("class.sockets.inc", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
    }
    public function isNtpdateAD():bool{
        $NtpdateAD=intval($this->GET_INFO("NtpdateAD"));
        if($NtpdateAD==0){return false;}

        $SQUIDEnable=intval($this->GET_INFO("SQUIDEnable"));
        $EnableActiveDirectoryFeature=$this->GET_INFO("EnableActiveDirectoryFeature");
        if($SQUIDEnable==0 || $EnableActiveDirectoryFeature == 0){return false;}

        $UseNativeKerberosAuth=intval($this->GET_INFO("UseNativeKerberosAuth"));
        $LockActiveDirectoryToKerberos=intval($this->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        $EnableKerbAuth=intval($this->GET_INFO("EnableKerbAuth"));

        if($UseNativeKerberosAuth==1){return true;}
        if($LockActiveDirectoryToKerberos==1){return true;}
        if($EnableKerbAuth==1){return true;}
        return false;

    }
    public function CORP_GOLD():bool{

        if(intval($this->GET_INFO(base64_decode("aXNDb3JwR29sZA==")))==1){
            return true;
        }
        return false;
    }
    public function CORP_LICENSE():bool{
        $verbose=false;
        $FINAL_TIME=0;
        if(isset($_GET["LIC-VERBOSE"]) OR isset($GLOBALS["LIC-VERBOSE"])){
            VERBOSE("CORP LICENSE DEBUGEED",__LINE__);
            $verbose=true;
        }

        if($GLOBALS["VERBOSE"]){
            unset($GLOBALS["FUNC".__FUNCTION__]);
        }

        if(isset($GLOBALS["FUNC".__FUNCTION__])){
            if(function_exists("VERBOSE")){VERBOSE("CORP_GOLD CACHE = {$GLOBALS["FUNC".__FUNCTION__]}",__LINE__);}
            return boolval($GLOBALS["FUNC".__FUNCTION__]);
        }

        if($this->CORP_GOLD()){
            if(function_exists("VERBOSE")){VERBOSE("CORP_GOLD = True",__LINE__);}
            $GLOBALS["FUNC".__FUNCTION__]=true;
            return true;
        }
        $LicenseInfos=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $LicenseINGP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
        if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}

        if($FINAL_TIME>0){
            $ExpiresSoon=intval($this->time_between_day_Web($FINAL_TIME));
            if ($verbose) {VERBOSE("LicenseInfos: FINAL_TIME == ($FINAL_TIME) Expiresoon=$ExpiresSoon", __LINE__);}
            if($ExpiresSoon>0){
                if ($verbose) {VERBOSE("LicenseInfos: OK!",__LINE__);}
                return true;
            }
            if($LicenseINGP>0){if($LicenseINGP>time()) { return true; }}
            return false;
        }

        $fname=base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ==");
        if(is_file($fname)) {
            $fdata = trim(@file_get_contents($fname));
            if ($verbose) {VERBOSE("$fname == ($fdata)", __LINE__);}

            if ($fdata <> "VFJVRQ==") {
                $GLOBALS["FUNC" . __FUNCTION__] = false;
                if (function_exists("VERBOSE")) {VERBOSE("CORP LICENSE = False", __LINE__);}
                $GLOBALS["FUNC".__FUNCTION__]= false;
                return false;
            }
            $GLOBALS["FUNC".__FUNCTION__]=true;
            if ($verbose) {VERBOSE("! LICENSE TRUE !", __LINE__);}
            return true;
        }

        $LicenseInfos           = $this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        if(!is_array($LicenseInfos)){$LicenseInfos=array();}
        if(!isset($LicenseInfos["FINAL_TIME"])){$LicenseInfos["FINAL_TIME"]=0;}
        if(!isset($LicenseInfos["license_status"])){$LicenseInfos["license_status"]=null;}

        if($LicenseInfos["license_status"]=="{license_active}"){
            if(intval($LicenseInfos["FINAL_TIME"])>time()){
                if(function_exists("VERBOSE")){VERBOSE("CORP LICENSE(LicenseInfos) = True",__LINE__);}
                $GLOBALS["FUNC".__FUNCTION__]=true;
                return true;
            }
        }
        if(function_exists("VERBOSE")){VERBOSE("CORP LICENSE(final) = False",__LINE__);}
        return false;
    }
    private function time_between_day_Web($xtime){
        $now = time(); // or your date as well
        $your_date = $xtime;
        $datediff = $your_date - $now ;
        return floor($datediff/(60*60*24));
    }
    function LANGUAGE_CACHE_IMPORT($array_keys,$lang,$keyname=null):bool{
        if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>FINAL: Saveing $lang $keyname</strong><br>\n";}
        $memcache=new lib_memcached();
        $Serialized=serialize($array_keys);
        $memcache->saveKey($keyname, $Serialized,18000);
        return true;
    }
    public function LANGUAGE_DUMP($lang): array{
        if($GLOBALS["AS_ROOT"]){return array();}
        if($lang=="undefined"){return array();}
        if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
        $MEMCACHED_KEY=strtoupper("TRANSLATE_LANG_$lang");

        if($GLOBALS["MEMCACHE_ENABLED"]){
            $memcache=new lib_memcached();
            $ARRAY=unserialize($memcache->getKey($MEMCACHED_KEY));
            if(!is_array($ARRAY)){$ARRAY=array();}
            if(count($ARRAY)>100){return $ARRAY;}
        }

        if(!$GLOBALS["MEMCACHE_ENABLED"]){
            if(!isset($GLOBALS["translation_$lang"])){
                if(isset($_SESSION["translation_$lang"])){
                    $GLOBALS["translation_$lang"]=$_SESSION["translation_$lang"];
                }
            }

            if(isset($GLOBALS["translation_$lang"])){
                if(count($GLOBALS["translation_$lang"])>100){
                    return $GLOBALS["translation_$lang"];
                }
            }
        }
        $filepath=dirname(__FILE__)."/language/$lang.db";

        if(!is_file($filepath)){
            if($lang=="pt"){
                if($GLOBALS["DEBUG"]){echo "LANGUAGE_DUMP switch to br.db instead, $filepath no such file\n";}
                $lang="br";
                $filepath=dirname(__FILE__)."/language/br.db";
            }
        }

        if(!is_file($filepath)){
            $filepath=dirname(__FILE__)."/language/en.db";
        }
        $file=@file_get_contents($filepath);
        $data=unserialize($file);

        if(!is_array($data)){
            writelogs("[$lang]:: Get language file from framework Not an array !",__CLASS__."/".__FUNCTION__,__FILE__);
            return array();
        }
        if(count($data)<500){ writelogs("[$lang]:: Get language file from framework Not an array !",__CLASS__."/".__FUNCTION__,__FILE__); }

        if($GLOBALS["MEMCACHE_ENABLED"]){
            $this->LANGUAGE_CACHE_IMPORT($lang,$data,$MEMCACHED_KEY);
            return $data;
        }

        $GLOBALS["translation_$lang"]=$data;
        $_SESSION["translation_$lang"]=$data;
        return $GLOBALS["translation_$lang"];

    }
    function APC_GET($key){
        return $this->MEMCACHE_GET($key);
    }
    function getfile($uri){
        $function="";$line="";$file="";
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            if(isset($trace[1])){
                $file=basename($trace[1]["file"]);
                $function=$trace[1]["function"];
                $line=$trace[1]["line"];

            }

        }
        $uid="Root";
        if(isset($_SESSION["uid"])){
            $uid=$_SESSION["uid"];
        }
        error_log("[$uid]::Fatal: $file/$function line:$line -> ASK an non-supported order $uri");

    }
    function TestArticaPort(){}
    function RandomPort():int{
        $min=1024;
        $max=65534;
        for($i=0;$i<2048;$i++){
            $port=rand($min,$max);
            if(!$this->PortExists($port)){
                return $port;
            }

        }
        return 0;
    }
    function PortExists($port_number) :bool{
        $fp =@fsockopen("127.0.0.1", $port_number);
        if ($fp){
            fclose($fp);
            return true;
        }
        return false;
    }
}
