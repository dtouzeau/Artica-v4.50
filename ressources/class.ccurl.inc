<?php
include_once(dirname(__FILE__)."/class.system.network.inc");
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.squid.familysites.inc");

if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
if (!defined('CURLOPTTYPE_OBJECTPOINT')){define('CURLOPTTYPE_OBJECTPOINT', 10000);}
if (!defined('CURLOPT_NOPROXY')){define('CURLOPT_NOPROXY', CURLOPTTYPE_OBJECTPOINT + 177);}
if (!defined('CURLOPT_MAX_SEND_SPEED_LARGE')){define('CURLOPT_MAX_SEND_SPEED_LARGE', 145);}
if (!defined('CURLOPT_MAX_RECV_SPEED_LARGE')){define('CURLOPT_MAX_RECV_SPEED_LARGE', 146);}
if(!isset($GLOBALS["NOT_FORCE_PROXY"])){$GLOBALS["NOT_FORCE_PROXY"]=false;}
//scandir($dir,SCANDIR_SORT_NONE);

class ccurl{
    var $uri;
    var $orginal_data=null;
    var $UseDirect=false;
    var $parms=array();
    private $additonal_headers=array();
    var $data='';
    var $NoHTTP_POST=false;
    var $GET_HEADERS=false;
    var $DISABLE_SSL_SECURITY=false;
    var $error;
    var $errors;
    public $ArticaProxyServerEnabled="no";
    public $ArticaProxyServerName='';
    public $ArticaProxyServerPort=3128;
    public $ArticaProxyServerUsername=null;
    public $ArticaProxyServerUserPassword=null;
    public $interface=null;
    var $noproxyload=false;
    var $authname=null;
    var $authpass=null;
    var $WgetBindIpAddress=null;
    var $FollowLocation=false;
    var $UserAgent=null;
    var $Timeout=120;
    var $error_num=0;
    var $WriteProgress=false;
    var $DebugProgress=true;
    var $ProgressFile=null;
    var $ProgressFunction=null;
    var $x_www_form_urlencoded=true;
    var $CURLOPT_NOPROXY="localhost,127.0.0.1";
    var $CURLOPT_HEADER=true;
    var $CURLINFO_HTTP_CODE=0;
    public $CURLINFO_SIZE=0;
    var $CURL_ALL_INFOS=array();
    var $CurlBandwith=0;
    public $buildErrors;
    public $curl_error_num=0;
    var $forceCache=false;
    private $called=null;
    private $headers=null;
    private $NoCheckSquid=false;
    private $ForceInterfaceTo=null;
    private $ch;
    // CURLOPT_INTERFACE
    function __construct($uri=null,$noproxyload=false,$interface=null){
        if(function_exists("posix_getuid")){
        if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
        }
        $trace=@debug_backtrace();if(isset($trace[1])){$this->called="called by source ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
        if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
        $this->uri=$uri;
        $this->interface=$interface;
        $this->noproxyload=$noproxyload;
        $this->NoCheckSquid=false;
        $this->OutPutlogs("*");
        $this->OutPutlogs("******************************");
        $this->OutPutlogs("*");

        if($interface<>null){$GLOBALS["WgetBindIpAddress"]=$interface;}
        if(!isset($GLOBALS["WgetBindIpAddress"])){$GLOBALS["WgetBindIpAddress"]=$sock->GET_INFO("WgetBindIpAddress");}

        $this->UserAgent=$sock->GET_INFO("CurlUserAgent");
        $this->CurlBandwith=$sock->GET_INFO("CurlBandwith");
        $this->Timeout=$sock->GET_INFO("CurlTimeOut");
        $NoCheckSquid=intval($sock->GET_INFO("NoCheckSquid"));
        $SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));
        if($this->UserAgent==null){$this->UserAgent="Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0";}

        if($SQUIDEnable==0){$NoCheckSquid=1;}

        if($NoCheckSquid==1){
            $this->OutPutlogs("NoCheckSquid: TRUE");
            $this->NoCheckSquid=true;
        }

        if(!is_numeric($this->CurlBandwith)){$this->CurlBandwith=0;}
        if(!is_numeric($this->Timeout)){$this->Timeout=3600;}
        if($this->Timeout<720){$this->Timeout=3600;}

        $this->OutPutlogs("CurlBandwith: $this->CurlBandwith");
        $this->OutPutlogs("Timeout.....: $this->Timeout seconds");
        $this->OutPutlogs("Bind Network: {$GLOBALS["WgetBindIpAddress"]}");
        $this->WgetBindIpAddress=$GLOBALS["WgetBindIpAddress"];
        $this->LoadProxy();


    }

    public function NO_SSL_VERIFY(){
        $this->DISABLE_SSL_SECURITY=True;
    }
    public function add_header($header){
        $this->additonal_headers[]=$header;
    }
    private function LoadProxy(){
        if($GLOBALS["NOT_FORCE_PROXY"]){
            $this->OutPutlogs("NOT_FORCE_PROXY: TRUE");
            $this->ArticaProxyServerEnabled="no";
            $this->ArticaProxyServerUsername=null;
            $this->ArticaProxyServerPort=null;
            return;
        }




        $ini=new Bs_IniHandler();
        $sock=new sockets();
        $datas=$sock->GET_INFO("ArticaProxySettings");
        if(trim($datas)==null){$this->OutPutlogs("LoadProxy(): ArticaProxySettings is null, aborting... Seems no proxy");return;}
        $ini->loadString($datas);
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerEnabled"])){$ini->_params["PROXY"]["ArticaProxyServerEnabled"]="no";}
        $this->OutPutlogs("ArticaProxyServerEnabled: {$ini->_params["PROXY"]["ArticaProxyServerEnabled"]}");
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerName"])){$ini->_params["PROXY"]["ArticaProxyServerName"]="";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerEnabled"])){$ini->_params["PROXY"]["ArticaProxyServerEnabled"]="no";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerPort"])){$ini->_params["PROXY"]["ArticaProxyServerPort"]=3128;}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerUsername"])){$ini->_params["PROXY"]["ArticaProxyServerUsername"]=null;}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerUserPassword"])){$ini->_params["PROXY"]["ArticaProxyServerUserPassword"]=null;}


        $this->ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
        $this->ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
        $this->ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
        if(!is_null($ini->_params["PROXY"]["ArticaProxyServerUsername"])) {
            $this->ArticaProxyServerUsername = trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
        }
        $this->ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];

        if(is_numeric($this->ArticaProxyServerEnabled)){
            if($this->ArticaProxyServerEnabled==1){$this->ArticaProxyServerEnabled="yes";}
        }


        if($this->ArticaProxyServerEnabled=="yes"){
            $this->OutPutlogs("Using a rempote proxy == YES...");

            if($this->ArticaProxyServerName==null){
                $this->OutPutlogs("Proxy name is null: Aborting using a proxy");
                $this->ArticaProxyServerEnabled="no";
                return;
            }
            if($this->ArticaProxyServerPort==0){
                $this->OutPutlogs("Proxy Port is 0: Aborting using a proxy");
                $this->ArticaProxyServerEnabled="no";
                return;
            }

            return;
        }



        if($this->NoCheckSquid){
            $this->OutPutlogs("NoCheckSquid - TRUE, abort");
            return;
        }


        if(!$GLOBALS["AS_ROOT"]){return;}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        $unix=new unix();
        $squid=$unix->LOCATE_SQUID_BIN();
        if(!is_file($squid)){return;}
        $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
        if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
        $this->OutPutlogs("SQUIDEnable=$SQUIDEnable");
        if($SQUIDEnable==0){return;}
        $SquidMgrListenPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
        if(!is_numeric($SquidMgrListenPort)){return;}
        $this->OutPutlogs("Using Proxy 127.0.0.1:$SquidMgrListenPort");
        VERBOSE("Use the local SQUID PROXY ON 127.0.0.1:$SquidMgrListenPort", __LINE__);
        $this->ArticaProxyServerName="127.0.0.1";
        $this->ArticaProxyServerPort=$SquidMgrListenPort;
        curl_setopt($this->ch, CURLOPT_INTERFACE,"127.0.0.1");

        $this->OutPutlogs("Testing new Proxy Parameters: $this->ArticaProxyServerName:$this->ArticaProxyServerPort");

        if($this->TestProxy()){
            $this->ArticaProxyServerEnabled="yes";
            $this->ArticaProxyServerUsername=null;
        }else{
            $this->ArticaProxyServerName=null;
            $this->ArticaProxyServerPort=0;
        }
    }
    private function locate_wget(){
        if(is_file("/usr/bin/wget")){return "/usr/bin/wget";}
        return false;

    }
    private function wget():bool{
        $curlPost=null;
        $cmds[]=$this->locate_wget();

        if(isset($this->parms)){
            if(count($this->parms)>0){
                foreach ($this->parms as $num=>$ligne){
                    $curlPost .='&'.$num.'=' . urlencode($ligne);}
            }
        }

        if($this->interface<>null){$cmds[]="--bind-address=$this->interface";}else{
            if($this->WgetBindIpAddress<>null){$cmds[]="--bind-address=$this->WgetBindIpAddress";}
        }

        $cmds[]="-q --output-document=/tmp/artica-wget.txt";
        $cmd=@implode(" ", $cmds);
        if($GLOBALS["VERBOSE"]){writelogs($cmd,__CLASS__.'/'.__FUNCTION__,__FILE__);}
        shell_exec($cmd);
        $this->data=@file_get_contents("/tmp/artica-wget.txt");
        @unlink("/tmp/artica-wget.txt");
        return true;
    }
    private function OutPutlogs($text){
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            if(isset($trace[1])){
                $function=$trace[1]["function"];
                $line=$trace[1]["line"];

            }

        }

        $GLOBALS["OutPutlogs"][]=date("H:i:s")." $function/$line: ".$text;
        if($GLOBALS["VERBOSE"]){VERBOSE($text, $line);}
        if(!isset($GLOBALS["CURLDEBUG"])){$GLOBALS["CURLDEBUG"]=array();}
        $GLOBALS["CURLDEBUG"][]=$text;

    }
    public function BuildCurlInfos(){
        reset($this->CURL_ALL_INFOS);
        foreach ($this->CURL_ALL_INFOS as $index=>$ligne){
            if(is_array($ligne)){
                foreach ($ligne as $a=>$b){
                    $errors[]="INFO: `$index`:$a = `$b`";
                }
                continue;
            }
            $errors[]="INFO: `$index` = `$ligne`";
        }

        return @implode("\n", $errors);

    }
    private function buildErrors():bool{
        foreach ($GLOBALS["OutPutlogs"] as $ligne){
            $this->errors[]=$ligne;
        }
        foreach ($this->CURL_ALL_INFOS as $index=>$ligne){
            if(is_array($ligne)){
                foreach ($ligne as $a=>$b){
                    $this->errors[]="INFO: `$index`:$a = `$b`";
                }
                continue;
            }
            $this->errors[]="INFO: `$index` = `$ligne`";
        }
        $GLOBALS["OutPutlogs"]=array();
        return false;
    }
    function ToSyslog($text){
        if(function_exists("openlog")){openlog("libcurl", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog(LOG_INFO, $text);}
        if(function_exists("closelog")){closelog();}
    }
    private function getdirect_HTTP_Request2(){

        $config = array(
            'adapter'           => 'HTTP_Request2_Adapter_Socket',
            'connect_timeout'   => $this->Timeout,
            'timeout'           => 0,
            'use_brackets'      => true,
            'protocol_version'  => '1.1',
            'buffer_size'       => 16384,
            'store_body'        => true,
            'ssl_verify_peer'   => true,
            'ssl_verify_host'   => true,
            'ssl_cafile'        => null,
            'ssl_capath'        => null,
            'ssl_local_cert'    => null,
            'ssl_passphrase'    => null,

            'digest_compat_ie'  => false,

            'follow_redirects'  => false,
            'max_redirects'     => 5,
            'strict_redirects'  => false
        );

        $this->OutPutlogs("getdirect_HTTP_Request2() -> $this->uri");
        $request = new Http_Request2($this->uri);
        $request->setConfig($config);


        try {
            $response = $request->send();
        } catch (Exception $exc) {
            $es = $exc->getTraceAsString();
            $ets=$exc->__toString();
            $egc=$exc->getCode();
            $egl=$exc->getLine();
            $egm=$exc->getMessage();
            $egt=$exc->getTrace();
            $response = null;
            $this->OutPutlogs("getdirect_HTTP_Request2() -> $es $ets $egc $egl $egm $egt");
            $this->error="$es $ets $egc $egl $egm $egt";
            return $this->buildErrors();;

        }
        $this->data = $response->getBody();
        return true;


    }
    private function pear_install_HTTP_Request2(){
        if(!$GLOBALS["AS_ROOT"]){return $this->buildErrors();}
        $unix=new unix();
        $pear=$unix->find_program("pear");
        if(!is_file($pear)){if($GLOBALS["VERBOSE"]){echo "pear_install_HTTP_Request2() `pear` no such binary\n";}return false;}
        $hohup=$unix->find_program("nohup");
        shell_exec("$hohup $pear install HTTP_Request2 >/dev/null 2>&1 &");
        return true;
    }
    private function  pear_install_Net_URL2(){
        if(!$GLOBALS["AS_ROOT"]){return $this->buildErrors();}
        $unix=new unix();
        $pear=$unix->find_program("pear");
        if(!is_file($pear)){if($GLOBALS["VERBOSE"]){echo "pear_install_Net_URL2() `pear` no such binary\n";}return false;}
        $hohup=$unix->find_program("nohup");
        shell_exec("$hohup $pear install Net_URL2 >/dev/null 2>&1 &");
        return true;
    }
    private function getdirect(){

        if($GLOBALS["VERBOSE"]){echo "getdirect() -> HTTP/Request2.php\n";}
        include_once(dirname(__FILE__).'/HTTP/Request2.php');




        if($GLOBALS["VERBOSE"]){echo "getdirect() -> Tests classes\n";}
        if(!class_exists("Http_Request2")){
            $this->OutPutlogs("getdirect() -> Class Http_Request2 did not exists");
            if($GLOBALS["VERBOSE"]){echo " Class Http_Request2 did not exists -> pear_install_HTTP_Request2\n";}
            $this->pear_install_HTTP_Request2();
        }else{
            if($GLOBALS["VERBOSE"]){echo " Class Http_Request2 OK\n";}
        }

        if(!class_exists("Net_URL2")){
            $this->OutPutlogs("getdirect() -> Class Net_URL2 did not exists");
            if($GLOBALS["VERBOSE"]){echo " Class Net_URL2 did not exists -> pear_install_Net_URL2\n";}
            $this->pear_install_Net_URL2();
        }else{
            $this->OutPutlogs("getdirect() -> Class Net_URL2 OK");
            if($GLOBALS["VERBOSE"]){echo " Class Net_URL2 OK\n";}
        }

        if(class_exists("Http_Request2")){
            $this->OutPutlogs("getdirect() -> Class getdirect_HTTP_Request2()");
            return $this->getdirect_HTTP_Request2();}
        //$auth = base64_encode('user:password');
        //$header = array("Authorization: Basic $auth");
        $socket=array();


        if($this->interface<>null){
            $this->OutPutlogs("getdirect() -> Interface:$this->interface");
            $socket=array('socket' => array('bindto' => "$this->interface:0"));
        }


        $opts = array('http' =>
            array(
                'method'  => 'GET',
                'header'=>"Content-Type: text/html; charset=utf-8",
                //  "Authorization: Basic ".base64_encode("$https_user:$https_password")."\r\n",
                $socket,
                'timeout' => $this->Timeout
            )
        );
        $context = stream_context_create($opts);
        $this->OutPutlogs("getdirect() -> $this->uri");


        try {
            $result =file_get_contents($this->uri,false,$context);
        }
        catch (Exception $e) {

            $this->error=$e->getMessage();
            $this->OutPutlogs("getdirect() -> $this->error");
            return $this->buildErrors();;
        }



        if(!$result){
            $this->error=$this->error ." Failed to get $this->uri in context (timeout ($this->Timeout)";
            if($GLOBALS["VERBOSE"]){echo "Failed to get $this->uri in context\n";}
            return $this->buildErrors();;
        }

        $this->OutPutlogs("getdirect() -> ".strlen($result)." bytes");
        $this->data=$result;

        if(!$this->ParseTextError()){
            $this->error="ParseTextError() -> Return False data:".strlen($result)." bytes";
            $this->OutPutlogs("ParseTextError() -> Return False....");
            return $this->buildErrors();;
        }

        return true;
    }
    public function getHeaders($timeout2=0){

        $this->ch = curl_init();
        $this->CheckInterface();




        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->UserAgent);
        if($this->FollowLocation){curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION,true);}

        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');
        curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');
        $headers[]="Pragma: no-cache,must-revalidate";
        $headers[]="Cache-Control: no-cache,must revalidate";
        $headers[]="Expect:";
        if( count($this->additonal_headers) > 0){
            foreach($this->additonal_headers as $header){
                if(!preg_match("#.+?:.+#",$header)){continue;}
                $headers[]=$header;
}
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_URL, $this->uri);
        curl_setopt($this->ch, CURLOPT_FILETIME, true);
        curl_setopt($this->ch, CURLOPT_NOBODY, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        if($this->authname<>null){curl_setopt($this->ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");}

        if(intval($timeout2)>0){$this->Timeout=$timeout2;}
        if($GLOBALS["VERBOSE"]){echo "CURLOPT_TIMEOUT, $this->Timeout\n";}
        curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
        $this->CheckProxy();


        $this->header = curl_exec($this->ch);
        $Infos= curl_getinfo($this->ch);




        $f=explode("\n",$this->header);
        foreach ($f as $ligne){
            $tr=explode(":",$ligne);
            if(count($tr)<1){continue;}
            if(isset($tr[0])){
                if(!isset($tr[1])){$tr[1]=null;}
                $Infos[trim($tr[0])]=trim($tr[1]);
            }
        }




        curl_close($this->ch);
        return $Infos;
    }
    private function TestProxy(){

        if(isset($GLOBALS["CCURL_TEST_PROXY"])){return $GLOBALS["CCURL_TEST_PROXY"];}



        $this->OutPutlogs("TestProxy() \"$this->ArticaProxyServerName:$this->ArticaProxyServerPort\" Line ".__LINE__);
        $f=@fsockopen ($this->ArticaProxyServerName, $this->ArticaProxyServerPort, $errno, $errstr, 2);

        if(!$f){
            $this->OutPutlogs("$this->ArticaProxyServerName:$this->ArticaProxyServerPort failed $errno $errstr");
            $GLOBALS["CCURL_TEST_PROXY"]=false;
            $this->buildErrors();
            return false;
        }

        fclose($f);
        $this->OutPutlogs("CCURL_TEST_PROXY $this->ArticaProxyServerName:$this->ArticaProxyServerPort OK");
        $GLOBALS["CCURL_TEST_PROXY"]=true;
        return true;
    }
    public function getFiletime()
    {
        return $this->info['filetime'];
    }
    public function NoLocalProxy(){
        $this->OutPutlogs("NoCheckSquid: TRUE");
        $this->NoCheckSquid=true;
    }

    public function interface_force($ipaddr):bool{
        $this->WgetBindIpAddress=$ipaddr;
        $this->ForceInterfaceTo=$ipaddr;
        return true;
    }

    function get():bool{
        if (!extension_loaded('curl')) {
            $this->OutPutlogs("curl extension is not loaded -> wget()");
            return $this->wget();
        }
        if($this->UseDirect){
            $this->OutPutlogs( __CLASS__."/".__FUNCTION__.":: ".__LINE__.":: ->getdirect()");
            return $this->getdirect();
        }

        $this->ch = curl_init();
        curl_reset($this->ch);
        $curlPost=null;
        if(isset($this->parms)){
            if(count($this->parms)>0){
                foreach ($this->parms as $num=>$ligne){
                    if ($num == "graphsql4searchwiki") {
                        $curlPost = $ligne;
                    } else {
                        $curlPost .= '&' . $num . '=' . urlencode($ligne);
                    }

                }
            }
        }
        $this->OutPutlogs("PHP: ".PHP_VERSION);
        $this->OutPutlogs("CURLOPT_TIMEOUT:[GET] {$this->Timeout}s");

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
        curl_setopt($this->ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
        $headers[]="Pragma: no-cache,must-revalidate";
        $headers[]="Cache-Control: no-cache,must revalidate";
        $headers[]="Expect:";
        if( count($this->additonal_headers) > 0){
            foreach($this->additonal_headers as $header){
                if(!preg_match("#.+?:.+#",$header)){continue;}
                $headers[]=$header;
            }
        }
        curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
        curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
        curl_setopt($this->ch, CURLOPT_HEADER, $this->CURLOPT_HEADER);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->UserAgent);
        if($this->FollowLocation){curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION,true);}
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');


        if($this->authname<>null){
            $this->OutPutlogs("Enable website authentication has $this->authname");
            curl_setopt($this->ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");
        }

        $this->CheckInterface();
        $this->CheckProxy();


        if($this->CurlBandwith>0){
            $this->OutPutlogs("CURLOPT_MAX_RECV_SPEED_LARGE = $this->CurlBandwith KB/S");
            curl_setopt($this->ch,CURLOPT_MAX_RECV_SPEED_LARGE,$this->CurlBandwith*1024);
        }
        if($GLOBALS["VERBOSE"]){curl_setopt($this->ch, CURLINFO_HEADER_OUT, 1);}

        if($curlPost<>null){
            $this->OutPutlogs("Add posts datas $curlPost....");
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $curlPost);
        }
        if($this->NoHTTP_POST){curl_setopt($this->ch, CURLOPT_POST, 0);}
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        $this->data=curl_exec($this->ch);
        $this->orginal_data=$this->data;
        $this->CURLINFO_HTTP_CODE=curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        $this->CURL_ALL_INFOS=curl_getinfo($this->ch);
        $this->curl_error_num=curl_errno($this->ch);

        if($GLOBALS["VERBOSE"]){
            echo "[GET] exec = $this->curl_error_num (".curl_strerror($this->curl_error_num).")\n";
            echo curl_getinfo($this->ch, CURLINFO_HEADER_OUT);
            echo "curl_getinfo ***** $this->CURLINFO_HTTP_CODE *****\n";
        }


        if(!$this->ParseCodeError()){
            if($this->CURLINFO_HTTP_CODE==0) {
                $error=$this->curl_error_num;
                return $this->ParseError($error);
            }
            $this->OutPutlogs("ParseCodeError return $this->buildErrors(); -> Failed");
            return $this->buildErrors();
        }

        $header_size = curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);
        $this->headers=substr($this->data, 0, $header_size);
        $this->data=substr( $this->data, $header_size );
        $this->OutPutlogs($this->headers);
        $error=curl_errno($this->ch);
        VERBOSE("$this->uri - Error:$error",__LINE__);
        if($error>0){
            VERBOSE("Error:$error",__LINE__);
            $this->OutPutlogs(__CLASS__."/".__FUNCTION__."(): Error:$error: datas: $this->uri (". strlen($this->data).
                " bytes) ");
        }
        curl_close($this->ch);
        if(!$this->ParseTextError()){
            $this->OutPutlogs("Return False....");
            return $this->buildErrors();
        }
        return $this->ParseError($error);
    }
    private function ParseCodeError():bool{

        if($this->CURLINFO_HTTP_CODE==0){
            if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE == 0 return false\n";}
            return false;
        }

        if($this->CURLINFO_HTTP_CODE==200){
            if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE == 200 return true\n";}
            return true;
        }

        if($this->CURLINFO_HTTP_CODE==300){
            if($GLOBALS["VERBOSE"]){
                echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE == 300 (300 Multiple Choices) return this->buildErrors();\n";
            }
            $this->data=null;
            $this->error="503 Multiple Choices";
            $this->error_num=300;
            $this->buildErrors();
            return false;
        }

        if($this->CURLINFO_HTTP_CODE==405){
            if($GLOBALS["VERBOSE"]){
                echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE == 405 (405 Not Allowed) return this->buildErrors();\n";
            }
            $this->data=null;
            $this->error="405 Not Allowed";
            $this->error_num=405;
            $this->buildErrors();
            return false;
        }

        if($this->CURLINFO_HTTP_CODE<400){
            if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE < 400 return true\n";}
            return true;
        }

        if($this->CURLINFO_HTTP_CODE==503){
            $this->data=null;
            $this->error="503 Service Unavailable";
            $this->error_num=503;
            $this->buildErrors();
            return false;
        }

        if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: Parsing CURLINFO_HTTP_CODE:$this->CURLINFO_HTTP_CODE\n";}

        $this->OutPutlogs("Parsing code $this->CURLINFO_HTTP_CODE");
        $array[407]="Proxy Authentication Required";
        $array[404]="Not found";
        $array[400]="Bad Request";
        $array[403]="Access denied";
        $array[500]="Internal Server Error";
        if(isset($array[$this->CURLINFO_HTTP_CODE])){
            $this->OutPutlogs($array[$this->CURLINFO_HTTP_CODE]);
            $this->data=null;
            $this->error_num=$this->CURLINFO_HTTP_CODE;
            $this->error=$array[$this->CURLINFO_HTTP_CODE];
            $this->buildErrors();
            return false;
        }

        return true;

    }
    public function ParseTextError(){
        if($this->CURLINFO_HTTP_CODE<400){return true;}

        if($this->CURLINFO_HTTP_CODE==503){
            $this->data=null;
            $this->error="503 Service Unavailable";
            $this->error_num=503;
            return $this->buildErrors();;
        }

        if($this->CURLINFO_HTTP_CODE==401){
            $this->data=null;
            $this->error="401: Unauthorized";
            $this->error_num=401;
            return $this->buildErrors();;
        }

        if($this->CURLINFO_HTTP_CODE==403){
            $this->data=null;
            $this->error="403: Access denied";
            $this->error_num=403;
            return $this->buildErrors();;
        }


        if(strpos($this->headers, "407 Proxy Authentication Required")){
            $this->data=null;
            $this->error="407 Proxy Authentication Required";
            $this->error_num=407;
            return $this->buildErrors();;
        }


        if(strpos(strtolower($this->data), strtolower("<title>Artica-postfix 404</title>"))>0){
            $this->error="404 Not found ".strlen($this->data)." bytes length";
            writelogs("404 Not found !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->data=null;
            $this->error_num=404;
            return $this->buildErrors();;
        }

        if(strpos(strtolower($this->data), strtolower("<title><title>413 Request Entity Too Large</title>"))>0){
            $this->error="413 Request Entity Too Large ".strlen($this->data)." bytes length";
            writelogs("413 Request Entity Too Large !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->data=null;
            $this->error_num=413;
            return $this->buildErrors();;
        }

        if(strpos(strtolower($this->data), strtolower("<title>400 - Bad Request</title>"))>0){
            $this->error="400 Bad Request ".strlen($this->data)." bytes length";
            writelogs("400 Bad Request !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->data=null;
            $this->error_num=400;
            return $this->buildErrors();;

        }

        if(strpos(strtolower($this->data), strtolower("<title>Access Denied"))>0){
            $this->error="Access denied ".strlen($this->data)." bytes length";
            writelogs("Access Denied !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->data=null;
            return $this->buildErrors();;
        }
        if(strpos(strtolower($this->data), strtolower("<title>403 Forbidden"))>0){
            $this->error="403: Access denied ".strlen($this->data)." bytes length";
            if(preg_match("#please try again in\s+([0-9]+)\s+(.*?)[\.|\s+]#is", $this->data,$re)){
                $this->error="Error 403: Access denied please try again in {$re[1]} {$re[2]}";
            }
            $this->error_num=403;
            return $this->buildErrors();;
        }

        if(strpos(strtolower($this->data), strtolower("<title>500 - Internal Server Error"))>0){
            $this->error="500 - Internal Server Error ".strlen($this->data)." bytes length";
            $this->error_num=500;
            $this->data=null;
            return $this->buildErrors();;
        }

        if(strpos(strtolower($this->data), strtolower("X-Squid-Error:"))>0){

            if(preg_match("#X-Squid-Error:\s+([0-9]+)\s+(.+?)$#is", $this->data,$re)){
                $this->error="Error {$re[1]}: {$re[2]}";
            }
            $this->error_num=$re[1];
            $this->data=null;
            if($this->error_num<400){return true;}
            return $this->buildErrors();;
        }



        return true;

    }
    public function ParseError($error){
        $called=null;
        $error=intval($error);
        if($error==0){
            $this->error=null;
            return true;
        }


        VERBOSE("Parse Error -$error-",__LINE__);
        if($GLOBALS["VERBOSE"]){echo "ccurl/ParseError($error)\n";}
        $trace=@debug_backtrace();

        if(isset($trace[1])) {
            $called = "called by " . basename($trace[1]["file"]) . " {$trace[1]["function"]}() line {$trace[1]["line"]}";
        }

        if($error==45){
            $this->error="Interface error.A specified outgoing interface could not be used. Set which interface to use for outgoing connections' source IP address with $this->WgetBindIpAddress";
        }

        if($error==6){
            writelogs("error 6 Couldn't resolve host. The given remote host was not resolved $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_COULDNT_RESOLVE_HOST}";
            return $this->buildErrors();;
        }

        if($error==7){
            writelogs("error 7 Failed to connect() to host or proxy (Proxy: $this->ArticaProxyServerEnabled, $this->ArticaProxyServerName:$this->ArticaProxyServerPort) $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_COULDNT_CONNECT} (Proxy: $this->ArticaProxyServerEnabled,$this->ArticaProxyServerName:$this->ArticaProxyServerPort)";
            return $this->buildErrors();;
        }

        if($error==23){
            writelogs("Curl:: error 23 An error occurred when writing received data to a local file. $trace",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_WRITE_ERROR}";
            return $this->buildErrors();;
        }

        if($error==26){
            writelogs("Curl:: error 26 There was a problem reading a local file or an error returned by the read callback. $trace",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_READ_ERROR}";
            return $this->buildErrors();;
        }

        if($error==60){
            writelogs("Curl:: error 60 SSL certificate problem: unable to get local issuer certificate $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_PEER_FAILED_VERIFICATION}";
            return $this->buildErrors();
        }

        if($error==56){
            writelogs("Curl:: error 56 Failure with receiving network data. $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_RECV_ERROR}";
            return $this->buildErrors();;
        }
        if($error==52){
            writelogs("Curl:: error 52 Nothing was returned from the server. $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_52_ERROR}";
            return $this->buildErrors();;
        }


        if($error==28){
            writelogs("Curl:: error 28 Operation timeout. The specified time-out period was reached according to the conditions. $this->called $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_OPERATION_TIMEDOUT} - {$this->Timeout}s";
            return $this->buildErrors();;
        }

        if($error==18){
            writelogs("Curl:: error 18 Partial file. ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->error="{CURLE_CURLE_PARTIAL_FILE}";
            return $this->buildErrors();;
        }

        return true;


    }
    private function ChooseInterface(){
        if(isset($GLOBALS["CURL_ChooseInterface"])){return $GLOBALS["CURL_ChooseInterface"];}
        $ips=new networking();
        $sock=new sockets();
        $f=array();
        $MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
        if($MikrotikTransparent==1){
            $MikrotikVirtualIP=$sock->GET_INFO('MikrotikVirtualIP');
            $this->OutPutlogs("Mikrotik is enabled on $MikrotikVirtualIP");
            $GLOBALS["CURL_ChooseInterface"]=$MikrotikVirtualIP;
            return $GLOBALS["CURL_ChooseInterface"];
        }

        $WgetBindIpAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WgetBindIpAddress"));
        if($WgetBindIpAddress==1){$WgetBindIpAddress=null;}

        if($WgetBindIpAddress<>null){
            $this->OutPutlogs("WgetBindIpAddress = $WgetBindIpAddress");
            $unix=new unix();
            if($unix->IS_IPADDR_EXISTS($WgetBindIpAddress)) {
                $this->OutPutlogs("WgetBindIpAddress = $WgetBindIpAddress Exists");
                $GLOBALS["CURL_ChooseInterface"] = $WgetBindIpAddress;
                return $WgetBindIpAddress;
            }
        }
        $GLOBALS["CURL_ChooseInterface"]=null;
        return  $GLOBALS["CURL_ChooseInterface"];
    }
    private function EXEC_NO_PROXY(){
        $this->OutPutlogs("EXEC_NO_PROXY()");
        $this->ArticaProxyServerEnabled="no";
        $this->ArticaProxyServerName=null;
        $this->ArticaProxyServerPort=null;
        $this->OutPutlogs("CURLOPT_NOPROXY = TRUE");
        curl_setopt($this->ch, CURLOPT_NOPROXY,"*");
        curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, false);
        $this->CheckInterface();

    }
    private function EXEC_PROXY_USERNAME(){
        if($this->ArticaProxyServerUsername==null){return;}

        curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        if($this->ArticaProxyServerUserPassword<>null){
            curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
            return;
        }
        curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername);


    }
    private function EXEC_PROXY(){
        curl_setopt($this->ch,CURLOPT_HTTPPROXYTUNNEL,FALSE);
        curl_setopt ($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        if($this->forceCache){
            if($GLOBALS["VERBOSE"]){echo "$this->ArticaProxyServerName:$this->ArticaProxyServerPort Force cache...\n";}
            $headers[]="Pragma: no-cache,must-revalidate";
            $headers[]="Cache-Control: no-cache,must revalidate";
            $headers[]="Expect:";
            if( count($this->additonal_headers) > 0){
                foreach($this->additonal_headers as $header){
                    if(!preg_match("#.+?:.+#",$header)){continue;}
                    $headers[]=$header;
                }
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }
        $this->OutPutlogs("CURLOPT_PROXY = $this->ArticaProxyServerName:$this->ArticaProxyServerPort");
        curl_setopt ($this->ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");

        if($this->ArticaProxyServerName<>"127.0.0.1"){$this->CheckInterface();}
        $this->EXEC_PROXY_USERNAME();


    }
    private function CheckProxy(){


        $this->OutPutlogs("Artica Proxy Server Enabled: $this->ArticaProxyServerEnabled");
        if($this->ArticaProxyServerEnabled<>"yes"){$this->ArticaProxyServerName=null;}

        if(!$this->NoCheckSquid){
            $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
            if($SQUIDEnable==1){
                $this->OutPutlogs("SQUIDEnable=$SQUIDEnable");
                $SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
                if($SquidMgrListenPort>0){
                    $this->OutPutlogs("Using the local proxy squid-cache on $SquidMgrListenPort port...");
                    $this->ArticaProxyServerEnabled="yes";
                    $this->ArticaProxyServerName="127.0.0.1";
                    $this->ArticaProxyServerPort=$SquidMgrListenPort;
                    $this->WgetBindIpAddress="127.0.0.1";
                }
            }

        }

        if($this->ArticaProxyServerName==null){
            $this->OutPutlogs("CURLOPT_PROXY_ENABLED = FALSE ( ArticaProxyServerEnabled = `$this->ArticaProxyServerEnabled`)");
            $this->EXEC_NO_PROXY();
            return;
        }




        if(!$this->TestProxy()){
            $this->OutPutlogs("CURLOPT_PROXY_ENABLED = FALSE -> * * * * * UNAVAILABLE * * * * *");
            $this->EXEC_NO_PROXY();
            return;
        }
        $this->OutPutlogs("CURLOPT_PROXY_ENABLED = $this->ArticaProxyServerEnabled");
        $this->OutPutlogs("CURLOPT_PROXY_SERVER = $this->ArticaProxyServerName");
        $this->OutPutlogs("CURLOPT_PROXY_PORT = $this->ArticaProxyServerPort");
        $this->EXEC_PROXY();


    }
    private function CheckInterface():bool{

        VERBOSE("CURLOPT_INTERFACE: \"WgetBindIpAddress\": \"$this->WgetBindIpAddress\" ", __LINE__);

        if($this->WgetBindIpAddress<>null){
            $this->interface=$this->WgetBindIpAddress;
            $this->OutPutlogs("CURLOPT_INTERFACE: \"$this->interface\"");
        }

        if(is_null($this->interface)){
            VERBOSE("CURLOPT_INTERFACE: NONE", __LINE__);
            return true;
        }
        if(strlen($this->interface)<3){
            VERBOSE("CURLOPT_INTERFACE: \"$this->interface\" Corrupted => NONE ", __LINE__);
            return true;
        }
        if($this->interface==null){
            VERBOSE("CURLOPT_INTERFACE: NONE", __LINE__);
            return true;
        }
        if($this->interface=="127.0.0.1"){
            VERBOSE("CURLOPT_INTERFACE: NONE", __LINE__);
            return true;
        }
        if($this->interface=="none"){
            VERBOSE("CURLOPT_INTERFACE: NONE", __LINE__);
            return true;
        }

        $this->OutPutlogs("CURLOPT_INTERFACE: \"$this->interface\" Line ". __LINE__);
        VERBOSE("CURLOPT_INTERFACE: $this->interface", __LINE__);
        $GLOBALS["CURLDEBUG"][]="CURLOPT_INTERFACE: \"$this->interface\" Line ". __LINE__;
        curl_setopt($this->ch, CURLOPT_INTERFACE,$this->interface);
        return true;

    }
    function GetHeads(){

        if (!extension_loaded('curl') or ($this->ch = curl_init($this->uri)) === false) {echo "No curl extentions can be loaded\n";return;}
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($this->ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($this->ch, CURLOPT_FILETIME, TRUE);
        $headers[]="Pragma: no-cache,must-revalidate";
        $headers[]="Cache-Control: no-cache,must revalidate";
        $headers[]="Expect:";
        if( count($this->additonal_headers) > 0){
            foreach($this->additonal_headers as $header){
                if(!preg_match("#.+?:.+#",$header)){continue;}
                $headers[]=$header;
            }
        }
        if(!is_numeric($this->Timeout)){$this->Timeout=120;}
        if(preg_match("#https:#",$this->uri)){
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');
            curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');
        }

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
        curl_setopt($this->ch, CURLOPT_HEADER, TRUE);
        $result=curl_exec($this->ch);
        if ($result === false) {
            $Error=curl_error($this->ch);
            $ErrorNum=curl_errno($this->ch);
            $this->error=$ErrorNum;
            $this->error_num=$Error;
            curl_close($this->ch);
            return false;
        }


        $this->CURL_ALL_INFOS=curl_getinfo($this->ch);
        $this->CURLINFO_HTTP_CODE=curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        $this->CURLINFO_SIZE = curl_getinfo($this->ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $error=curl_errno($this->ch);
        if($GLOBALS["VERBOSE"]){echo "GetHeads:: Error N.$error\n";}

        curl_close($this->ch);
        if(!$this->ParseError($error)){return $this->buildErrors();}
        return $this->ParseCodeError();
    }
    function GetFile($targetpath){
        $GLOBALS["OutPutlogs"]=array();
        $SUFFIX_VERBOSE=__CLASS__."/".__FUNCTION__;
        if (!extension_loaded('curl') or ($this->ch = curl_init($this->uri)) === false) {
            $this->error="No curl extention can be loaded\n";
            echo "No curl extention can be loaded\n";
            return false;
        }
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);
        $this->OutPutlogs("CURLOPT_FILE = $targetpath");

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
        curl_setopt($this->ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);

        if(!is_dir(dirname($targetpath))){
            @mkdir(dirname($targetpath),0755,true);
            if(!is_dir(dirname($targetpath)) ){
                $this->errors[]=dirname($targetpath)." permission denied!";
                $this->error=dirname($targetpath)." permission denied!";
                return false;
            }
        }

        curl_setopt($this->ch, CURLOPT_FILE, $fp = fopen($targetpath,'w'));
        curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->UserAgent);
        $headers[]="Pragma: no-cache,must-revalidate";
        $headers[]="Cache-Control: no-cache,must revalidate";
        $headers[]="Expect:";
        if( count($this->additonal_headers) > 0){
            foreach($this->additonal_headers as $header){
                if(!preg_match("#.+?:.+#",$header)){continue;}
                $headers[]=$header;
            }
        }
        if(!is_numeric($this->Timeout)){$this->Timeout=120;}

        if($GLOBALS["DEBUG"]){
            curl_setopt($this->ch, CURLINFO_HEADER_OUT,TRUE);
        }else{
            curl_setopt($this->ch, CURLINFO_HEADER_OUT,FALSE);
        }




        if(preg_match("#https:#",$this->uri)){
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');
        }

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->Timeout);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->Timeout);

        if($this->WriteProgress){
            if($this->ProgressFile<>null){
                $callBack="CurlprogressCallback";
                $GLOBALS["CurlprogressCallbackFile"]=$this->ProgressFile;
            }
            if($this->ProgressFunction<>null){$callBack=$this->ProgressFunction;}
            curl_setopt( $this->ch, CURLOPT_NOPROGRESS, false );
            curl_setopt( $this->ch, CURLOPT_PROGRESSFUNCTION, $callBack);
        }

        $this->CheckInterface();
        $this->CheckProxy();

        curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
        if($this->authname<>null){
            $this->OutPutlogs("CURLOPT_USERPWD = $this->authname:******");
            curl_setopt($this->ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");
        }

        if($this->CurlBandwith>0){
            $this->OutPutlogs("CURLOPT_MAX_RECV_SPEED_LARGE = $this->CurlBandwith KB/S");
            curl_setopt($this->ch,CURLOPT_MAX_RECV_SPEED_LARGE,$this->CurlBandwith*1024);
        }

        $this->OutPutlogs("CURL_EXEC: \"$this->uri\" [".__LINE__."]");
        $result=curl_exec($this->ch);
        if(!$result){
            fclose($fp);
            $this->error=curl_errno($this->ch)." (".curl_error($this->ch).")";
            return false;
        }


        $this->CURLINFO_HTTP_CODE=curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        $this->OutPutlogs("CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]");


        $this->CURL_ALL_INFOS=curl_getinfo($this->ch);
        $GLOBALS["CURL_LAST_SIZE_DOWNLOAD"]=$this->CURL_ALL_INFOS["size_download"];



        fclose($fp);
        $error=curl_errno($this->ch);
        $this->OutPutlogs("CURL_ERROR_TEXT: $error");
        curl_close($this->ch);


        if($this->CURLINFO_HTTP_CODE==200){
            return true;
        }


        if(function_exists("openlog")){openlog("ArticaCurl", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog(LOG_ERR, "[$this->uri]: FAILED");}
        if(function_exists("closelog")){closelog();}



        if($GLOBALS["VERBOSE"]){echo "Check \"$targetpath\" CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]\n";}

        if(!$this->ParseTextError()){
            $this->OutPutlogs("* * * ParseTextError() return $this->buildErrors(); * * * ");
            @unlink($targetpath);
            $this->OutPutlogs("Return False....");
            writelogs("*** FAILED *** datas: $this->uri Error.$this->CURLINFO_HTTP_CODE",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return $this->buildErrors();;
        }


        if($this->CURLINFO_HTTP_CODE==400){
            $this->OutPutlogs("* * * 404 ERROR * * * ");
            @unlink($targetpath);
            $this->error_num=400;
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
            writelogs("*** FAILED *** datas: $this->uri 400 not Found error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return $this->buildErrors();
        }


        if($this->CURLINFO_HTTP_CODE==404){
            $this->OutPutlogs("* * * 404 not found * * * ");
            @unlink($targetpath);
            $this->error_num=404;
            $this->error="404 Not found";
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
            writelogs("*** FAILED *** datas: $this->uri 404 not Found error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return $this->buildErrors();;
        }

        if($this->CURLINFO_HTTP_CODE==407){
            $this->OutPutlogs("* * * 407 Denied * * * ");
            @unlink($targetpath);
            $this->error_num=407;
            $this->error="407 Access denied";
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
            writelogs("*** FAILED *** datas: $this->uri 407 Denied error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return $this->buildErrors();;
        }

        if($this->CURLINFO_HTTP_CODE==408){
            $this->OutPutlogs("* * * 408 Timed-Out * * * ");
            @unlink($targetpath);
            $this->error_num=408;
            $this->error="408 Timed-Out ";
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
            writelogs("*** FAILED *** datas: $this->uri 408 Timed-Out: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return $this->buildErrors();
        }

        if($this->CURLINFO_HTTP_CODE==509){
            $this->OutPutlogs("* * * 509 Unassigned * * * ");
            @unlink($targetpath);
            $this->error_num=509;
            $this->error="509 Unassigned";
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
            writelogs("*** FAILED *** datas: $this->uri 509 Denied error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return $this->buildErrors();
        }






        $this->OutPutlogs("Check \"$targetpath\" CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]");

        if(!is_file($targetpath)){
            $this->OutPutlogs("* * * $targetpath no such file * * * ");
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]\n";}
            if($GLOBALS["VERBOSE"]){echo "path \"$targetpath\" CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE no such file\n";}
            return $this->buildErrors();;
        }

        $this->OutPutlogs("Check \"ParseError($error) - ERR: $this->CURLINFO_HTTP_CODE\" [".__LINE__."]");

        if(!$this->ParseError($error)){
            $this->OutPutlogs("* * * $error return $this->buildErrors(); * * * ");
            if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
            writelogs("*** FAILED *** datas: $this->uri (". strlen($this->data)." bytes) error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);

            return $this->buildErrors();;
        }

        $filesize=filesize($targetpath);
        $filesizeText=$filesize;
        $filesizeUnit="Bytes";
        if($filesize>1024){$filesizeText=$filesize/1024;$filesizeUnit="Ko";}

        if($error>0){
            $this->OutPutlogs("* * * $error > 0 * * * ");
            writelogs("datas: $this->uri (". strlen($result)." bytes) filesize=$filesizeText $filesizeUnit error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
        }

        if($filesize<10000){
            $this->data=@file_get_contents($targetpath);
            if(!$this->ParseTextError()){$this->data=null;return $this->buildErrors();;}
            $this->data=null;
            return true;
        }
        //@file_put_contents($this->data,$targetpath);
        if($GLOBALS["VERBOSE"]){echo "SUCCESS [".__LINE__."]\n";}
        return true;
    }
    function postFile($fieldname,$filepath,$array_fields=array()){
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, "$this->uri");

        if($GLOBALS["VERBOSE"]){
            curl_setopt($this->ch, CURLOPT_HEADER, 1);
            curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
        }else{
            curl_setopt($this->ch, CURLOPT_HEADER, 0);
            curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
        }
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->UserAgent);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_SSLVERSION,'all');

        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
        $this->CheckInterface();



        // same as <input type="file" name="file_box">
        if(count($array_fields)>0){
            foreach ($array_fields as $num=>$line){$post[$num]=$line;}}
        if($this->x_www_form_urlencoded){
            $post[$fieldname]=base64_encode(@file_get_contents($filepath));
        }else{
            $post[$fieldname]="@$filepath";
        }

        if($this->x_www_form_urlencoded){
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($post,"","&"));
        }else{
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        }
        $headers[]="Pragma: no-cache,must-revalidate";
        $headers[]="Cache-Control: no-cache,must revalidate";
        $headers[]="Expect:";
        if( count($this->additonal_headers) > 0){
            foreach($this->additonal_headers as $header){
                if(!preg_match("#.+?:.+#",$header)){continue;}
                $headers[]=$header;
            }
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);


        if($this->DebugProgress){
            curl_setopt( $this->ch, CURLOPT_NOPROGRESS, 0 );
            curl_setopt( $this->ch, CURLOPT_PROGRESSFUNCTION, "CurlprogressCallbackDebug");
        }



        $size=@filesize("$filepath");
        $size=$size/1024;
        $UNIT="KB";
        if($size>1000){$UNIT="MB";$size=$size/1024;}
        $size=round($size,2);
        if($GLOBALS["VERBOSE"]){echo "CURLOPT_TIMEOUT: {$this->Timeout}s\n";}
        if($GLOBALS["VERBOSE"]){echo "$this->uri: Executing posting _FILE(S)[$fieldname] $filepath ($size $UNIT)\n";}
        $this->data = curl_exec($this->ch);
        $this->CURL_ALL_INFOS=curl_getinfo($this->ch);


        if($GLOBALS["VERBOSE"]){
            print_r(curl_getinfo($this->ch));
        }
        $error=curl_errno($this->ch);

        if(!$this->ParseError($error)){
            writelogs("Returned datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
            curl_close($this->ch);
            return $this->buildErrors();
        }

        curl_close($this->ch);
        return true;
    }

}
function CurlprogressCallbackDebug( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
    if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]=0;}

    if ( $download_size == 0 )
        $progress = 0;
    else
        $progress = round( $downloaded_size * 100 / $download_size );


    if ( $progress > $GLOBALS["previousProgress"]){
        echo $progress."%\n";
        $GLOBALS["previousProgress"] = $progress;
    }
}

function CurlprogressCallback( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
    static $previousProgress = 0;

    if ( $download_size == 0 )
        $progress = 0;
    else
        $progress = round( $downloaded_size * 100 / $download_size );


    if ( $progress > $previousProgress){
        if($GLOBALS["VERBOSE"]){echo $progress."\n";}
        $previousProgress = $progress;
        $fp = fopen( $GLOBALS["CurlprogressCallbackFile"], 'w' );
        fputs( $fp, "$progress\n" );
        fclose( $fp );
    }
}